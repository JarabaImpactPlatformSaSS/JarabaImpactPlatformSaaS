<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Proactive AI journey progression for the Emprendimiento vertical.
 *
 * Evaluates user state and triggers proactive actions in the copilot FAB:
 * - Inactivity nudge in discovery phase
 * - Canvas completion encouragement
 * - Hypothesis experiment design prompts
 * - Pivot suggestion after killed hypotheses
 * - Mentor connection post-MVP
 * - Funding opportunity alerts
 * - Post-scaling expansion to formación
 *
 * Plan Elevación Emprendimiento v2 — Fase 2 (G2).
 */
class EmprendimientoJourneyProgressionService {

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
      'message' => '¡Hola! Han pasado unos días. Registra tu idea de negocio para empezar a validarla. ¿Empezamos?',
      'cta_label' => 'Registrar idea',
      'cta_url' => '/emprendimiento/bmc',
      'channel' => 'fab_dot',
      'mode' => 'consultor',
      'priority' => 10,
    ],
    'canvas_incomplete' => [
      'state' => 'activation',
      'condition' => 'canvas_below_50',
      'message' => 'Tu BMC necesita más contenido para poder validar hipótesis. Completar los bloques clave te acerca a tu primer experimento.',
      'cta_label' => 'Completar canvas',
      'cta_url' => '/emprendimiento/bmc',
      'channel' => 'fab_expand',
      'mode' => 'vpc_designer',
      'priority' => 20,
    ],
    'hypothesis_stalled' => [
      'state' => 'engagement',
      'condition' => 'hypothesis_no_experiment_7d',
      'message' => 'Tienes hipótesis sin experimento hace 7 días. Diseñar un experimento te ayudará a validar o descartar rápidamente.',
      'cta_label' => 'Diseñar experimento',
      'cta_url' => '/emprendimiento/experimentos/gestion',
      'channel' => 'fab_dot',
      'mode' => 'sparring',
      'priority' => 25,
    ],
    'all_killed_no_pivot' => [
      'state' => 'engagement',
      'condition' => 'three_killed_no_pivot',
      'message' => 'Varias hipótesis han sido invalidadas. Consideremos un pivot para redirigir tu modelo de negocio.',
      'cta_label' => 'Explorar pivot',
      'cta_url' => '/copilot?mode=pivot_advisor',
      'channel' => 'fab_expand',
      'mode' => 'pivot_advisor',
      'priority' => 5,
    ],
    'mvp_validated_no_mentor' => [
      'state' => 'conversion',
      'condition' => 'mvp_no_mentor',
      'message' => 'Tu MVP está validado. ¡Enhorabuena! Un mentor experimentado puede acelerar tu siguiente fase.',
      'cta_label' => 'Conectar mentor',
      'cta_url' => '/mentors',
      'channel' => 'fab_badge',
      'mode' => 'coach',
      'priority' => 15,
    ],
    'funding_eligible' => [
      'state' => 'conversion',
      'condition' => 'eligible_not_applied',
      'message' => 'Hay convocatorias de financiación compatibles con tu perfil emprendedor. No dejes pasar la oportunidad.',
      'cta_label' => 'Ver financiación',
      'cta_url' => '/funding/opportunities',
      'channel' => 'fab_dot',
      'mode' => 'cfo',
      'priority' => 12,
    ],
    'post_scaling_expansion' => [
      'state' => 'retention',
      'condition' => 'scaling_30_days',
      'message' => 'Tu negocio está en marcha. Explora formación avanzada para potenciar las competencias de tu equipo.',
      'cta_label' => 'Explorar formación',
      'cta_url' => '/courses',
      'channel' => 'fab_dot',
      'mode' => 'pattern_expert',
      'priority' => 30,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
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
    $cacheKey = "emprendimiento_proactive_pending_{$userId}";
    $cached = \Drupal::state()->get($cacheKey);

    if ($cached) {
      $age = \Drupal::time()->getRequestTime() - ($cached['evaluated_at'] ?? 0);
      if ($age < 3600) {
        return $cached['action'];
      }
    }

    $action = $this->evaluate($userId);
    \Drupal::state()->set($cacheKey, [
      'action' => $action,
      'evaluated_at' => \Drupal::time()->getRequestTime(),
    ]);

    return $action;
  }

  /**
   * Dismisses a proactive rule for a user.
   */
  public function dismissAction(int $userId, string $ruleId): void {
    $key = "emprendimiento_proactive_dismissed_{$userId}";
    $dismissed = \Drupal::state()->get($key, []);
    if (!in_array($ruleId, $dismissed, TRUE)) {
      $dismissed[] = $ruleId;
      \Drupal::state()->set($key, $dismissed);
    }

    \Drupal::state()->delete("emprendimiento_proactive_pending_{$userId}");

    $this->logger->info('Proactive rule @rule dismissed by user @uid', [
      '@rule' => $ruleId,
      '@uid' => $userId,
    ]);
  }

  /**
   * Batch evaluates proactive actions for all emprendimiento users.
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
        ->loadByProperties(['vertical' => 'emprendimiento']);

      foreach ($states as $state) {
        $userId = (int) ($state->get('user_id')->target_id ?? 0);
        if (!$userId) {
          continue;
        }

        $action = $this->evaluate($userId);
        \Drupal::state()->set("emprendimiento_proactive_pending_{$userId}", [
          'action' => $action,
          'evaluated_at' => \Drupal::time()->getRequestTime(),
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
      'canvas_below_50' => $this->checkCanvasBelow50($userId),
      'hypothesis_no_experiment_7d' => $this->checkHypothesisNoExperiment($userId),
      'three_killed_no_pivot' => $this->checkThreeKilledNoPivot($userId),
      'mvp_no_mentor' => $this->checkMvpNoMentor($userId),
      'eligible_not_applied' => $this->checkEligibleNotApplied($userId),
      'scaling_30_days' => $this->checkInactivity($journeyState, 30),
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

    $elapsed = \Drupal::time()->getRequestTime() - $reference;
    return $elapsed > ($days * 86400);
  }

  /**
   * Checks if BMC canvas completeness is below 50%.
   */
  protected function checkCanvasBelow50(int $userId): bool {
    if (!\Drupal::hasService('jaraba_copilot_v2.bmc_validation')) {
      return FALSE;
    }

    try {
      $canvases = $this->entityTypeManager
        ->getStorage('business_model_canvas')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($canvases)) {
        return TRUE;
      }

      $canvas = reset($canvases);
      $validation = \Drupal::service('jaraba_copilot_v2.bmc_validation')
        ->validateCanvas($canvas);

      return ($validation['overall_percentage'] ?? 0) < 50;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if user has PENDING hypotheses with no experiment for 7+ days.
   */
  protected function checkHypothesisNoExperiment(int $userId): bool {
    try {
      $hypotheses = $this->entityTypeManager
        ->getStorage('hypothesis')
        ->loadByProperties([
          'user_id' => $userId,
          'status' => 'PENDING',
        ]);

      if (empty($hypotheses)) {
        return FALSE;
      }

      $sevenDaysAgo = \Drupal::time()->getRequestTime() - (7 * 86400);

      foreach ($hypotheses as $hypothesis) {
        $created = (int) ($hypothesis->get('created')->value ?? 0);
        if ($created > 0 && $created < $sevenDaysAgo) {
          // Check if this hypothesis has any linked experiment.
          $experiments = $this->entityTypeManager
            ->getStorage('experiment')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('hypothesis_id', $hypothesis->id())
            ->count()
            ->execute();

          if ((int) $experiments === 0) {
            return TRUE;
          }
        }
      }
    }
    catch (\Exception $e) {
      // Entity may not exist.
    }

    return FALSE;
  }

  /**
   * Checks if user has >=3 KILLED hypotheses with no pivot log.
   */
  protected function checkThreeKilledNoPivot(int $userId): bool {
    try {
      $killedCount = (int) $this->entityTypeManager
        ->getStorage('hypothesis')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('status', 'KILLED')
        ->count()
        ->execute();

      if ($killedCount < 3) {
        return FALSE;
      }

      // Check for pivot log in BMC context_data.
      $canvases = $this->entityTypeManager
        ->getStorage('business_model_canvas')
        ->loadByProperties(['user_id' => $userId]);

      if (!empty($canvases)) {
        $canvas = reset($canvases);
        $contextData = $canvas->get('context_data')->value ?? '';
        $context = !empty($contextData) ? json_decode($contextData, TRUE) : [];
        if (!empty($context['pivot_log'])) {
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
   * Checks if user has validated MVP but no mentoring engagement.
   */
  protected function checkMvpNoMentor(int $userId): bool {
    try {
      // Check for validated experiment (non-KILL decision).
      $validatedExperiments = (int) $this->entityTypeManager
        ->getStorage('experiment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('decision', 'KILL', '<>')
        ->exists('decision')
        ->count()
        ->execute();

      if ($validatedExperiments === 0) {
        return FALSE;
      }

      // Check for any mentoring engagement.
      $mentoring = (int) $this->entityTypeManager
        ->getStorage('mentoring_engagement')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();

      return $mentoring === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if user is eligible for funding but hasn't applied.
   */
  protected function checkEligibleNotApplied(int $userId): bool {
    if (!\Drupal::hasService('jaraba_funding.matching_engine')) {
      return FALSE;
    }

    try {
      $matches = \Drupal::service('jaraba_funding.matching_engine')
        ->findMatches($userId);

      $hasGoodMatch = FALSE;
      foreach ($matches as $match) {
        if (($match['score'] ?? 0) >= 60) {
          $hasGoodMatch = TRUE;
          break;
        }
      }

      if (!$hasGoodMatch) {
        return FALSE;
      }

      // Check for existing funding applications.
      $applications = (int) $this->entityTypeManager
        ->getStorage('funding_application')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();

      return $applications === 0;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Loads the journey state entity for a user.
   */
  protected function getJourneyState(int $userId) {
    if (!\Drupal::moduleHandler()->moduleExists('jaraba_journey')) {
      return NULL;
    }

    try {
      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties([
          'user_id' => $userId,
          'vertical' => 'emprendimiento',
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
    return \Drupal::state()->get("emprendimiento_proactive_dismissed_{$userId}", []);
  }

}
