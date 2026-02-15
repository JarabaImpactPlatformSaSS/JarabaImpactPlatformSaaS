<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Proactive AI journey progression for the Andalucía +ei vertical.
 *
 * Evaluates participant state and triggers proactive actions in the copilot FAB:
 * - Inactivity nudges for IA sessions
 * - Low training hours reminders
 * - Orientation milestone celebrations
 * - Training milestone prompts for phase transition
 * - Ready-for-insertion guidance
 * - Insertion preparation (tipo_insercion selection)
 * - Insertion stalled re-engagement
 * - Post-insertion cross-vertical expansion
 *
 * Plan Elevación Andalucía +ei v1 — Fase 7.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityJourneyProgressionService
 */
class AndaluciaEiJourneyProgressionService {

  /**
   * Proactive rules keyed by rule ID.
   *
   * Each rule maps a fase + condition to an action.
   * Priority: lower = higher priority (evaluated first).
   */
  protected const PROACTIVE_RULES = [
    'inactivity_atencion' => [
      'fase' => 'atencion',
      'condition' => 'no_ia_3_days',
      'message' => 'Han pasado unos dias sin hablar con tu tutor IA. Una sesion de 10 minutos puede ayudarte a avanzar.',
      'cta_label' => 'Hablar con Tutor IA',
      'cta_url' => '/copilot',
      'channel' => 'fab_dot',
      'mode' => 'tutor_ei',
      'priority' => 10,
    ],
    'low_training_hours' => [
      'fase' => 'atencion',
      'condition' => 'low_training_4_weeks',
      'message' => 'Llevas pocas horas de formacion. Recuerda que necesitas 50h minimo. Te recomiendo estos modulos.',
      'cta_label' => 'Ver Cursos',
      'cta_url' => '/lms',
      'channel' => 'fab_expand',
      'mode' => 'training_advisor',
      'priority' => 15,
    ],
    'orientation_milestone' => [
      'fase' => 'atencion',
      'condition' => 'orientation_10h_reached',
      'message' => 'Has alcanzado 10 horas de orientacion. Vas por buen camino hacia la insercion.',
      'cta_label' => 'Ver progreso',
      'cta_url' => '/andalucia-ei',
      'channel' => 'fab_expand',
      'mode' => 'tutor_ei',
      'priority' => 5,
    ],
    'training_milestone' => [
      'fase' => 'atencion',
      'condition' => 'training_50h_reached',
      'message' => 'Has completado las 50 horas minimas de formacion. Podrias estar listo para la fase de insercion.',
      'cta_label' => 'Consultar transicion',
      'cta_url' => '/copilot?mode=transition_advisor',
      'channel' => 'fab_expand',
      'mode' => 'transition_advisor',
      'priority' => 3,
    ],
    'ready_for_insertion' => [
      'fase' => 'atencion',
      'condition' => 'can_transit_to_insertion',
      'message' => 'Cumples todos los requisitos para avanzar a la fase de insercion. Tu orientador revisara tu caso.',
      'cta_label' => 'Ver requisitos',
      'cta_url' => '/andalucia-ei',
      'channel' => 'fab_expand',
      'mode' => 'tutor_ei',
      'priority' => 1,
    ],
    'insertion_preparation' => [
      'fase' => 'insercion',
      'condition' => 'no_tipo_insercion',
      'message' => 'Aun no has definido tu via de insercion. Hablemos sobre tus opciones: empleo, autoempleo o formacion.',
      'cta_label' => 'Definir via',
      'cta_url' => '/copilot?mode=insertion_planner',
      'channel' => 'fab_dot',
      'mode' => 'insertion_planner',
      'priority' => 8,
    ],
    'insertion_stalled' => [
      'fase' => 'insercion',
      'condition' => 'no_activity_30_days',
      'message' => 'Hace tiempo que no avanzas en tu proceso de insercion. Tu tutor IA puede ayudarte a retomar.',
      'cta_label' => 'Retomar progreso',
      'cta_url' => '/copilot',
      'channel' => 'fab_dot',
      'mode' => 'tutor_ei',
      'priority' => 20,
    ],
    'post_insertion_expansion' => [
      'fase' => 'insercion',
      'condition' => 'recently_inserted',
      'message' => 'Felicidades por tu insercion. Descubre como potenciar tu nuevo camino con nuestros programas.',
      'cta_label' => 'Explorar opciones',
      'cta_url' => '/emprendimiento/diagnostico',
      'channel' => 'fab_dot',
      'mode' => 'expansion_advisor',
      'priority' => 25,
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
    $participant = $this->getParticipant($userId);
    if (!$participant) {
      return NULL;
    }

    $currentFase = $participant->get('fase_actual')->value ?? 'atencion';
    $dismissed = $this->getDismissedRules($userId);

    $sortedRules = self::PROACTIVE_RULES;
    uasort($sortedRules, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

    foreach ($sortedRules as $ruleId => $rule) {
      if ($rule['fase'] !== $currentFase) {
        continue;
      }
      if (in_array($ruleId, $dismissed, TRUE)) {
        continue;
      }
      if ($this->checkCondition($userId, $rule['condition'], $participant)) {
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
    $cacheKey = "andalucia_ei_proactive_pending_{$userId}";
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
    $key = "andalucia_ei_proactive_dismissed_{$userId}";
    $dismissed = \Drupal::state()->get($key, []);
    if (!in_array($ruleId, $dismissed, TRUE)) {
      $dismissed[] = $ruleId;
      \Drupal::state()->set($key, $dismissed);
    }

    \Drupal::state()->delete("andalucia_ei_proactive_pending_{$userId}");

    $this->logger->info('Proactive rule @rule dismissed by user @uid', [
      '@rule' => $ruleId,
      '@uid' => $userId,
    ]);
  }

  /**
   * Batch evaluates proactive actions for all andalucia_ei participants.
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
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple();

      foreach ($participants as $participant) {
        $userId = (int) ($participant->getOwnerId() ?? 0);
        if (!$userId) {
          continue;
        }

        $action = $this->evaluate($userId);
        \Drupal::state()->set("andalucia_ei_proactive_pending_{$userId}", [
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
   * Checks a single condition for a participant.
   */
  protected function checkCondition(int $userId, string $condition, $participant): bool {
    return match ($condition) {
      'no_ia_3_days' => $this->checkNoIaActivity($participant, 3),
      'low_training_4_weeks' => $this->checkLowTraining($participant, 10, 28),
      'orientation_10h_reached' => $this->checkOrientationMilestone($participant, 10),
      'training_50h_reached' => $this->checkTrainingMilestone($participant, 50),
      'can_transit_to_insertion' => $this->checkCanTransit($participant),
      'no_tipo_insercion' => $this->checkNoTipoInsercion($participant),
      'no_activity_30_days' => $this->checkNoActivity($participant, 30),
      'recently_inserted' => $this->checkRecentlyInserted($participant, 30),
      default => FALSE,
    };
  }

  /**
   * Checks if participant has had no IA sessions for N days.
   */
  protected function checkNoIaActivity($participant, int $days): bool {
    $iaHours = (float) ($participant->get('horas_mentoria_ia')->value ?? 0);
    if ($iaHours <= 0) {
      // Never used IA — always trigger.
      $created = (int) ($participant->get('created')->value ?? 0);
      if (!$created) {
        return FALSE;
      }
      $elapsed = \Drupal::time()->getRequestTime() - $created;
      return $elapsed > ($days * 86400);
    }

    // Check last change timestamp as proxy for last activity.
    $changed = (int) ($participant->get('changed')->value ?? 0);
    if (!$changed) {
      return FALSE;
    }

    $elapsed = \Drupal::time()->getRequestTime() - $changed;
    return $elapsed > ($days * 86400);
  }

  /**
   * Checks if training hours are below threshold after N days enrolled.
   */
  protected function checkLowTraining($participant, float $minHours, int $minDays): bool {
    $created = (int) ($participant->get('created')->value ?? 0);
    if (!$created) {
      return FALSE;
    }

    $elapsed = \Drupal::time()->getRequestTime() - $created;
    if ($elapsed < ($minDays * 86400)) {
      return FALSE;
    }

    $training = (float) ($participant->get('horas_formacion')->value ?? 0);
    return $training < $minHours;
  }

  /**
   * Checks if total orientation hours have reached a milestone.
   */
  protected function checkOrientationMilestone($participant, float $milestone): bool {
    $total = (float) ($participant->get('horas_mentoria_ia')->value ?? 0)
      + (float) ($participant->get('horas_mentoria_humana')->value ?? 0)
      + (float) ($participant->get('horas_orientacion_ind')->value ?? 0)
      + (float) ($participant->get('horas_orientacion_grup')->value ?? 0);

    return $total >= $milestone;
  }

  /**
   * Checks if training hours have reached a milestone.
   */
  protected function checkTrainingMilestone($participant, float $milestone): bool {
    $training = (float) ($participant->get('horas_formacion')->value ?? 0);
    return $training >= $milestone;
  }

  /**
   * Checks if participant can transit to insertion phase.
   *
   * Delegates to FaseTransitionManager if available.
   */
  protected function checkCanTransit($participant): bool {
    if (!\Drupal::hasService('jaraba_andalucia_ei.fase_transition_manager')) {
      return FALSE;
    }

    try {
      $manager = \Drupal::service('jaraba_andalucia_ei.fase_transition_manager');
      return $manager->canTransitToInsercion($participant);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if participant in insertion has no tipo_insercion defined.
   */
  protected function checkNoTipoInsercion($participant): bool {
    if (!$participant->hasField('tipo_insercion')) {
      return FALSE;
    }
    $tipo = $participant->get('tipo_insercion')->value ?? '';
    return empty($tipo);
  }

  /**
   * Checks if participant has been inactive for N days.
   */
  protected function checkNoActivity($participant, int $days): bool {
    $changed = (int) ($participant->get('changed')->value ?? 0);
    if (!$changed) {
      return FALSE;
    }
    $elapsed = \Drupal::time()->getRequestTime() - $changed;
    return $elapsed > ($days * 86400);
  }

  /**
   * Checks if participant was recently inserted (within last N days).
   */
  protected function checkRecentlyInserted($participant, int $days): bool {
    if (!$participant->hasField('tipo_insercion')) {
      return FALSE;
    }
    $tipo = $participant->get('tipo_insercion')->value ?? '';
    if (empty($tipo)) {
      return FALSE;
    }

    $changed = (int) ($participant->get('changed')->value ?? 0);
    if (!$changed) {
      return FALSE;
    }

    $cutoff = \Drupal::time()->getRequestTime() - ($days * 86400);
    return $changed > $cutoff;
  }

  /**
   * Loads the participant entity for a user.
   */
  protected function getParticipant(int $userId) {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['user_id' => $userId]);

      return !empty($participants) ? reset($participants) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Gets list of dismissed rule IDs for a user.
   */
  protected function getDismissedRules(int $userId): array {
    return \Drupal::state()->get("andalucia_ei_proactive_dismissed_{$userId}", []);
  }

}
