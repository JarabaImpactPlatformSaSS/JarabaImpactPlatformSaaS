<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical value bridges for the ServiciosConecta vertical.
 *
 * Evaluates and presents bridges between ServiciosConecta and other
 * SaaS verticals (Emprendimiento, Fiscal, Formacion, Empleabilidad)
 * to maximize customer LTV.
 *
 * Bridges:
 * - emprendimiento: >20 bookings AND rating >4.5 (priority 10)
 * - fiscal: plan >= starter AND >10 bookings/month (priority 20)
 * - formacion: >50 total bookings (priority 30)
 * - empleabilidad: journey_state = at_risk AND inactivo 30 dias (priority 40)
 *
 * Plan Elevacion ServiciosConecta Clase Mundial v1 — Fase 8.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AgroConectaCrossVerticalBridgeService
 */
class ServiciosConectaCrossVerticalBridgeService {

  /**
   * Bridge definitions keyed by bridge ID.
   */
  protected const BRIDGES = [
    'emprendimiento' => [
      'id' => 'emprendimiento',
      'vertical' => 'emprendimiento',
      'icon' => 'rocket',
      'color' => 'var(--ej-color-impulse, #FF8C42)',
      'message' => 'Escala tu consulta: crea tu propia marca profesional con herramientas de emprendimiento.',
      'cta_label' => 'Explorar Emprendimiento',
      'cta_url' => '/emprender',
      'condition' => 'high_volume_high_rating',
      'priority' => 10,
    ],
    'fiscal' => [
      'id' => 'fiscal',
      'vertical' => 'fiscal',
      'icon' => 'building',
      'color' => 'var(--ej-color-corporate, #233D63)',
      'message' => 'Automatiza tu facturacion profesional con VeriFactu y Facturae.',
      'cta_label' => 'Ir a Fiscal',
      'cta_url' => '/fiscal/dashboard',
      'condition' => 'starter_plus_active',
      'priority' => 20,
    ],
    'formacion' => [
      'id' => 'formacion',
      'vertical' => 'formacion',
      'icon' => 'star',
      'color' => 'var(--ej-color-innovation, #00A9A5)',
      'message' => 'Comparte tu conocimiento: crea cursos online para multiplicar tu alcance.',
      'cta_label' => 'Crear Curso',
      'cta_url' => '/courses',
      'condition' => 'expert_level',
      'priority' => 30,
    ],
    'empleabilidad' => [
      'id' => 'empleabilidad',
      'vertical' => 'empleabilidad',
      'icon' => 'briefcase',
      'color' => 'var(--ej-color-success, #10B981)',
      'message' => 'Mientras reactivas tu consulta, explora oportunidades de empleo en tu sector.',
      'cta_label' => 'Ver Ofertas',
      'cta_url' => '/empleabilidad',
      'condition' => 'at_risk_inactive',
      'priority' => 40,
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evaluates available bridges for a user based on their current state.
   *
   * @return array
   *   List of bridges (max 2) with: id, vertical, message, cta_url,
   *   cta_label, icon, color, priority.
   */
  public function evaluateBridges(int $userId): array {
    $bridges = [];

    foreach (self::BRIDGES as $bridgeId => $bridge) {
      if ($this->evaluateCondition($userId, $bridge['condition'])) {
        $dismissed = $this->getDismissedBridges($userId);
        if (in_array($bridgeId, $dismissed, TRUE)) {
          continue;
        }
        $bridges[] = $bridge;
      }
    }

    usort($bridges, fn(array $a, array $b) => $a['priority'] <=> $b['priority']);

    return array_slice($bridges, 0, 2);
  }

  /**
   * Presents a bridge to the user and tracks impression.
   */
  public function presentBridge(int $userId, string $bridgeId): array {
    $bridge = self::BRIDGES[$bridgeId] ?? NULL;
    if (!$bridge) {
      return [];
    }

    $key = "serviciosconecta_bridge_impressions_{$userId}";
    $impressions = $this->state->get($key, []);
    $impressions[] = [
      'bridge_id' => $bridgeId,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    $this->state->set($key, array_slice($impressions, -50));

    return $bridge;
  }

  /**
   * Tracks user response to a bridge (accepted or dismissed).
   */
  public function trackBridgeResponse(int $userId, string $bridgeId, string $response): void {
    $key = "serviciosconecta_bridge_responses_{$userId}";
    $responses = $this->state->get($key, []);
    $responses[] = [
      'bridge_id' => $bridgeId,
      'response' => $response,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    $this->state->set($key, array_slice($responses, -100));

    if ($response === 'dismissed') {
      $dismissedKey = "serviciosconecta_bridge_dismissed_{$userId}";
      $dismissed = $this->state->get($dismissedKey, []);
      if (!in_array($bridgeId, $dismissed, TRUE)) {
        $dismissed[] = $bridgeId;
        $this->state->set($dismissedKey, $dismissed);
      }
    }

    $this->logger->info('Bridge @bridge @response by user @uid', [
      '@bridge' => $bridgeId,
      '@response' => $response,
      '@uid' => $userId,
    ]);
  }

  /**
   * Evaluates a single bridge condition.
   */
  protected function evaluateCondition(int $userId, string $condition): bool {
    return match ($condition) {
      'high_volume_high_rating' => $this->checkHighVolumeHighRating($userId),
      'starter_plus_active' => $this->checkStarterPlusActive($userId),
      'expert_level' => $this->checkExpertLevel($userId),
      'at_risk_inactive' => $this->checkAtRiskInactive($userId),
      default => FALSE,
    };
  }

  /**
   * Checks if professional has >20 total bookings AND average_rating > 4.5.
   */
  protected function checkHighVolumeHighRating(int $userId): bool {
    try {
      $professionals = $this->entityTypeManager
        ->getStorage('serviciosconecta_professional')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($professionals)) {
        return FALSE;
      }

      $professional = reset($professionals);
      $totalBookings = (int) ($professional->get('total_bookings')->value ?? 0);
      $avgRating = (float) ($professional->get('avg_rating')->value ?? 0);

      return $totalBookings > 20 && $avgRating > 4.5;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if professional has plan >= starter AND >10 bookings/month.
   */
  protected function checkStarterPlusActive(int $userId): bool {
    try {
      $professionals = $this->entityTypeManager
        ->getStorage('serviciosconecta_professional')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($professionals)) {
        return FALSE;
      }

      $professional = reset($professionals);
      $plan = $professional->get('plan')->value ?? 'gratuito';
      $monthlyBookings = (int) ($professional->get('monthly_bookings')->value ?? 0);

      $paidPlans = ['starter', 'profesional', 'premium'];
      return in_array($plan, $paidPlans, TRUE) && $monthlyBookings > 10;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if professional has >50 total bookings.
   */
  protected function checkExpertLevel(int $userId): bool {
    try {
      $professionals = $this->entityTypeManager
        ->getStorage('serviciosconecta_professional')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($professionals)) {
        return FALSE;
      }

      $professional = reset($professionals);
      $totalBookings = (int) ($professional->get('total_bookings')->value ?? 0);

      return $totalBookings > 50;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if professional journey_state is at_risk AND inactive for 30 days.
   */
  protected function checkAtRiskInactive(int $userId): bool {
    try {
      $professionals = $this->entityTypeManager
        ->getStorage('serviciosconecta_professional')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($professionals)) {
        return FALSE;
      }

      $professional = reset($professionals);
      $journeyState = $professional->get('journey_state')->value ?? 'active';

      if ($journeyState !== 'at_risk') {
        return FALSE;
      }

      $lastActivity = (int) ($professional->get('last_activity')->value ?? 0);
      if (!$lastActivity) {
        return FALSE;
      }

      $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);
      return $lastActivity < $thirtyDaysAgo;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets list of dismissed bridge IDs for a user.
   */
  protected function getDismissedBridges(int $userId): array {
    return $this->state->get("serviciosconecta_bridge_dismissed_{$userId}", []);
  }

}
