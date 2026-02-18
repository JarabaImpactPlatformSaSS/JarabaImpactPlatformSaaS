<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Proactive AI journey progression for the Empleabilidad vertical.
 *
 * Evaluates user state and triggers proactive actions in the copilot FAB:
 * - Motivational messages on inactivity
 * - Profile completion nudges
 * - Job suggestions for ready candidates
 * - CV coaching after application frustration
 * - Interview prep activation
 * - Offer negotiation help
 * - Post-employment cross-vertical bridges
 *
 * Plan Elevación Empleabilidad v1 — Fase 9.
 */
class EmployabilityJourneyProgressionService {

  /**
   * Proactive rules keyed by rule ID.
   *
   * Each rule maps a journey state + condition to an action.
   * Priority: lower = higher priority (evaluated first).
   */
  protected const PROACTIVE_RULES = [
    'inactivity_discovery' => [
      'state' => 'discovery',
      'condition' => 'inactivity_3_days',
      'message' => '¡Hola! Han pasado unos días. Completar tu perfil te acerca a tu próximo empleo. ¿Empezamos?',
      'cta_label' => 'Completar perfil',
      'cta_url' => '/my-profile/edit',
      'channel' => 'fab_dot',
      'mode' => 'profile_coach',
      'priority' => 10,
    ],
    'incomplete_profile' => [
      'state' => 'activation',
      'condition' => 'profile_below_50',
      'message' => 'Tu perfil necesita más contenido. Añadir experiencia y habilidades aumentará tu visibilidad significativamente.',
      'cta_label' => 'Mejorar perfil',
      'cta_url' => '/my-profile/edit',
      'channel' => 'fab_expand',
      'mode' => 'profile_coach',
      'priority' => 20,
    ],
    'ready_but_inactive' => [
      'state' => 'activation',
      'condition' => 'profile_complete_zero_applications',
      'message' => '¡Tu perfil está listo! He encontrado ofertas con alto match para ti. ¿Las revisamos?',
      'cta_label' => 'Ver ofertas',
      'cta_url' => '/jobs',
      'channel' => 'fab_badge',
      'mode' => 'job_advisor',
      'priority' => 15,
    ],
    'application_frustration' => [
      'state' => 'engagement',
      'condition' => 'five_apps_no_response',
      'message' => 'Llevas varias aplicaciones sin respuesta. ¿Te ayudo a optimizar tu CV y estrategia de búsqueda?',
      'cta_label' => 'Optimizar CV',
      'cta_url' => '/my-profile/cv',
      'channel' => 'fab_dot',
      'mode' => 'application_helper',
      'priority' => 25,
    ],
    'interview_prep' => [
      'state' => 'engagement',
      'condition' => 'has_interview',
      'message' => '¡Tienes una entrevista próxima! Vamos a prepararte para dar lo mejor de ti.',
      'cta_label' => 'Preparar entrevista',
      'cta_url' => '/copilot?mode=interview_prep',
      'channel' => 'fab_expand',
      'mode' => 'interview_prep',
      'priority' => 5,
    ],
    'offer_negotiation' => [
      'state' => 'conversion',
      'condition' => 'has_offer',
      'message' => '¡Felicidades por tu oferta! ¿Necesitas ayuda con la negociación salarial?',
      'cta_label' => 'Consejos de negociación',
      'cta_url' => '/copilot?mode=job_advisor',
      'channel' => 'fab_expand',
      'mode' => 'job_advisor',
      'priority' => 3,
    ],
    'post_employment_expansion' => [
      'state' => 'retention',
      'condition' => 'hired_30_days_ago',
      'message' => '¿Cómo va tu nuevo puesto? Explora oportunidades de formación avanzada y networking.',
      'cta_label' => 'Explorar formación',
      'cta_url' => '/courses',
      'channel' => 'fab_dot',
      'mode' => 'learning_guide',
      'priority' => 30,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly TimeInterface $time,
    protected readonly StateInterface $state,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly ?object $profileCompletion = NULL,
  ) {}

  /**
   * Evaluates all proactive rules for a user and returns the first match.
   *
   * @return array|null
   *   The matching rule with rule_id added, or NULL if no match.
   */
  public function evaluate(int $userId): ?array {
    $journeyState = $this->getJourneyState($userId);
    if (!$journeyState) {
      return NULL;
    }

    $currentState = $journeyState->get('journey_state')->value ?? 'discovery';
    $dismissed = $this->getDismissedRules($userId);

    $sortedRules = self::PROACTIVE_RULES;
    uasort($sortedRules, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

    foreach ($sortedRules as $ruleId => $rule) {
      if ($rule['state'] !== $currentState) {
        continue;
      }
      if (in_array($ruleId, $dismissed, TRUE)) {
        continue;
      }
      if ($this->checkCondition($userId, $rule['condition'], $journeyState)) {
        return array_merge($rule, ['rule_id' => $ruleId]);
      }
    }

    return NULL;
  }

  /**
   * Gets the cached pending action or evaluates rules.
   *
   * Caches evaluation results for 1 hour to avoid expensive checks on
   * every API poll (FAB checks every 5 minutes).
   */
  public function getPendingAction(int $userId): ?array {
    $cacheKey = "employability_proactive_pending_{$userId}";
    $cached = $this->state->get($cacheKey);

    if ($cached) {
      $age = $this->time->getRequestTime() - ($cached['evaluated_at'] ?? 0);
      if ($age < 3600) {
        return $cached['action'];
      }
    }

    $action = $this->evaluate($userId);
    $this->state->set($cacheKey, [
      'action' => $action,
      'evaluated_at' => $this->time->getRequestTime(),
    ]);

    return $action;
  }

  /**
   * Dismisses a proactive rule for a user.
   */
  public function dismissAction(int $userId, string $ruleId): void {
    $key = "employability_proactive_dismissed_{$userId}";
    $dismissed = $this->state->get($key, []);
    if (!in_array($ruleId, $dismissed, TRUE)) {
      $dismissed[] = $ruleId;
      $this->state->set($key, $dismissed);
    }

    $this->state->delete("employability_proactive_pending_{$userId}");

    $this->logger->info('Proactive rule @rule dismissed by user @uid', [
      '@rule' => $ruleId,
      '@uid' => $userId,
    ]);
  }

  /**
   * Batch evaluates proactive actions for all empleabilidad users.
   *
   * Intended to run from cron. Populates the state cache so that
   * subsequent API polls are fast.
   *
   * @return int
   *   Number of users processed.
   */
  public function evaluateBatch(): int {
    $processed = 0;

    try {
      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties(['vertical' => 'empleabilidad']);

      foreach ($states as $state) {
        $userId = (int) ($state->get('user_id')->target_id ?? 0);
        if (!$userId) {
          continue;
        }

        $action = $this->evaluate($userId);
        $this->state->set("employability_proactive_pending_{$userId}", [
          'action' => $action,
          'evaluated_at' => $this->time->getRequestTime(),
        ]);
        $processed++;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error in proactive batch evaluation: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $processed;
  }

  /**
   * Checks a single condition for a user.
   */
  protected function checkCondition(int $userId, string $condition, $journeyState): bool {
    return match ($condition) {
      'inactivity_3_days' => $this->checkInactivity($journeyState, 3),
      'profile_below_50' => $this->checkProfileBelow($userId, 50),
      'profile_complete_zero_applications' => $this->checkProfileCompleteNoApps($userId),
      'five_apps_no_response' => $this->checkAppsNoResponse($userId, 5),
      'has_interview' => $this->checkHasStatusApplication($userId, 'interviewed'),
      'has_offer' => $this->checkHasStatusApplication($userId, 'offered'),
      'hired_30_days_ago' => $this->checkHiredDaysAgo($userId, 30),
      default => FALSE,
    };
  }

  /**
   * Checks if user has been inactive for N days.
   */
  protected function checkInactivity($journeyState, int $days): bool {
    $contextData = $journeyState->get('context_data')->value ?? '';
    $context = !empty($contextData) ? json_decode($contextData, TRUE) : [];
    $lastAction = $context['last_action_timestamp'] ?? NULL;

    $reference = $lastAction
      ? (int) $lastAction
      : (int) ($journeyState->get('changed')->value ?? $journeyState->get('created')->value ?? 0);

    if (!$reference) {
      return FALSE;
    }

    $elapsed = $this->time->getRequestTime() - $reference;
    return $elapsed > ($days * 86400);
  }

  /**
   * Checks if user profile completion is below a threshold.
   */
  protected function checkProfileBelow(int $userId, int $threshold): bool {
    if (!$this->profileCompletion) {
      return FALSE;
    }

    try {
      $completion = $this->profileCompletion->calculateCompletion($userId);
      return ($completion['percentage'] ?? 0) < $threshold;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if profile is >=70% complete but user has zero applications.
   */
  protected function checkProfileCompleteNoApps(int $userId): bool {
    if (!$this->profileCompletion) {
      return FALSE;
    }

    try {
      $completion = $this->profileCompletion->calculateCompletion($userId);
      if (($completion['percentage'] ?? 0) < 70) {
        return FALSE;
      }

      $appCount = (int) $this->entityTypeManager
        ->getStorage('job_application')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('candidate_id', $userId)
        ->count()
        ->execute();

      return $appCount === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if user has N+ applications with no positive response.
   */
  protected function checkAppsNoResponse(int $userId, int $minApps): bool {
    try {
      $applications = $this->entityTypeManager
        ->getStorage('job_application')
        ->loadByProperties(['candidate_id' => $userId]);

      if (count($applications) < $minApps) {
        return FALSE;
      }

      $positiveStatuses = ['shortlisted', 'interviewed', 'offered', 'hired'];
      foreach ($applications as $app) {
        if (in_array($app->get('status')->value ?? '', $positiveStatuses, TRUE)) {
          return FALSE;
        }
      }

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if user has an application in a specific status.
   */
  protected function checkHasStatusApplication(int $userId, string $status): bool {
    try {
      $count = (int) $this->entityTypeManager
        ->getStorage('job_application')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('candidate_id', $userId)
        ->condition('status', $status)
        ->count()
        ->execute();

      return $count > 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if user was hired within the last N days.
   */
  protected function checkHiredDaysAgo(int $userId, int $days): bool {
    try {
      $applications = $this->entityTypeManager
        ->getStorage('job_application')
        ->loadByProperties([
          'candidate_id' => $userId,
          'status' => 'hired',
        ]);

      if (empty($applications)) {
        return FALSE;
      }

      $cutoff = $this->time->getRequestTime() - ($days * 86400);
      foreach ($applications as $app) {
        $changed = (int) ($app->get('changed')->value ?? 0);
        if ($changed > $cutoff) {
          return TRUE;
        }
      }
    }
    catch (\Exception $e) {
      // Entity may not exist.
    }

    return FALSE;
  }

  /**
   * Loads the journey state entity for a user.
   */
  protected function getJourneyState(int $userId) {
    if (!$this->moduleHandler->moduleExists('jaraba_journey')) {
      return NULL;
    }

    try {
      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties([
          'user_id' => $userId,
          'vertical' => 'empleabilidad',
        ]);

      return !empty($states) ? reset($states) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Gets list of dismissed rule IDs for a user.
   */
  protected function getDismissedRules(int $userId): array {
    return $this->state->get("employability_proactive_dismissed_{$userId}", []);
  }

}
