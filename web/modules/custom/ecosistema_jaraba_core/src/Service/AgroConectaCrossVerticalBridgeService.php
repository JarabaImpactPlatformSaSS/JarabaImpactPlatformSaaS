<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical value bridges for the AgroConecta vertical.
 *
 * Evaluates and presents bridges between AgroConecta and other
 * SaaS verticals (Emprendimiento, Fiscal, Formacion, Empleabilidad)
 * to maximize customer LTV.
 *
 * Bridges:
 * - emprendimiento: >50 ventas totales AND rating >4.5 (priority 10)
 * - fiscal: plan >= profesional AND >20 ventas/mes (priority 20)
 * - formacion: >100 ventas totales (priority 30)
 * - empleabilidad: journey_state = at_risk AND inactivo 30 dias (priority 40)
 *
 * Plan Elevacion AgroConecta Clase Mundial v1 — Fase 8.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityCrossVerticalBridgeService
 */
class AgroConectaCrossVerticalBridgeService {

  /**
   * Bridge definitions keyed by bridge ID.
   */
  protected const BRIDGES = [
    'emprendimiento' => [
      'id' => 'emprendimiento',
      'vertical' => 'emprendimiento',
      'icon_category' => 'business',
      'icon_name' => 'rocket',
      'color' => 'var(--ej-color-emprendimiento, #f59e0b)',
      'message' => 'Tu negocio agro demuestra potencial emprendedor. El 18% de productores como tu han escalado con herramientas de Emprendimiento.',
      'cta_label' => 'Diagnostico Emprendedor',
      'cta_url' => '/emprendimiento/diagnostico',
      'condition' => 'high_sales_and_rating',
      'priority' => 10,
    ],
    'fiscal' => [
      'id' => 'fiscal',
      'vertical' => 'fiscal',
      'icon_category' => 'finance',
      'icon_name' => 'invoice',
      'color' => 'var(--ej-color-fiscal, #233D63)',
      'message' => 'Con tu volumen de ventas, necesitas facturacion profesional. Cumple con VeriFactu y FACe automaticamente.',
      'cta_label' => 'Activar Facturacion',
      'cta_url' => '/fiscal/onboarding',
      'condition' => 'pro_plan_high_volume',
      'priority' => 20,
    ],
    'formacion' => [
      'id' => 'formacion',
      'vertical' => 'formacion',
      'icon_category' => 'general',
      'icon_name' => 'graduation',
      'color' => 'var(--ej-color-formacion, #0ea5e9)',
      'message' => 'Has superado las 100 ventas. Potencia tu negocio con formacion en agroecologia, marketing y gestion.',
      'cta_label' => 'Catalogo Formativo',
      'cta_url' => '/courses',
      'condition' => 'milestone_100_sales',
      'priority' => 30,
    ],
    'empleabilidad' => [
      'id' => 'empleabilidad',
      'vertical' => 'empleabilidad',
      'icon_category' => 'business',
      'icon_name' => 'target',
      'color' => 'var(--ej-color-empleabilidad, #2563eb)',
      'message' => 'Tu actividad ha bajado. Mientras reactivas tu tienda, explora oportunidades laborales en el sector agroalimentario.',
      'cta_label' => 'Diagnostico de Empleabilidad',
      'cta_url' => '/empleabilidad/diagnostico',
      'condition' => 'at_risk_inactive_30_days',
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
   *   cta_label, icon_category, icon_name, color, priority.
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

    $key = "agroconecta_bridge_impressions_{$userId}";
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
    $key = "agroconecta_bridge_responses_{$userId}";
    $responses = $this->state->get($key, []);
    $responses[] = [
      'bridge_id' => $bridgeId,
      'response' => $response,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    $this->state->set($key, array_slice($responses, -100));

    if ($response === 'dismissed') {
      $dismissedKey = "agroconecta_bridge_dismissed_{$userId}";
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
      'high_sales_and_rating' => $this->checkHighSalesAndRating($userId),
      'pro_plan_high_volume' => $this->checkProPlanHighVolume($userId),
      'milestone_100_sales' => $this->checkMilestone100Sales($userId),
      'at_risk_inactive_30_days' => $this->checkAtRiskInactive30Days($userId),
      default => FALSE,
    };
  }

  /**
   * Checks if producer has >50 total sales AND rating >4.5.
   */
  protected function checkHighSalesAndRating(int $userId): bool {
    try {
      $producers = $this->entityTypeManager
        ->getStorage('agroconecta_producer')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($producers)) {
        return FALSE;
      }

      $producer = reset($producers);
      $totalSales = (int) ($producer->get('total_sales')->value ?? 0);
      $avgRating = (float) ($producer->get('avg_rating')->value ?? 0);

      return $totalSales > 50 && $avgRating > 4.5;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if producer has plan >= profesional AND >20 sales/month.
   */
  protected function checkProPlanHighVolume(int $userId): bool {
    try {
      $producers = $this->entityTypeManager
        ->getStorage('agroconecta_producer')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($producers)) {
        return FALSE;
      }

      $producer = reset($producers);
      $plan = $producer->get('plan')->value ?? 'gratuito';
      $monthlySales = (int) ($producer->get('monthly_sales')->value ?? 0);

      return $plan === 'profesional' && $monthlySales > 20;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if producer has >100 total sales.
   */
  protected function checkMilestone100Sales(int $userId): bool {
    try {
      $producers = $this->entityTypeManager
        ->getStorage('agroconecta_producer')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($producers)) {
        return FALSE;
      }

      $producer = reset($producers);
      $totalSales = (int) ($producer->get('total_sales')->value ?? 0);

      return $totalSales > 100;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if producer journey_state is at_risk AND inactive for 30 days.
   */
  protected function checkAtRiskInactive30Days(int $userId): bool {
    try {
      $producers = $this->entityTypeManager
        ->getStorage('agroconecta_producer')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($producers)) {
        return FALSE;
      }

      $producer = reset($producers);
      $journeyState = $producer->get('journey_state')->value ?? 'active';

      if ($journeyState !== 'at_risk') {
        return FALSE;
      }

      $lastActivity = (int) ($producer->get('last_activity')->value ?? 0);
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
    return $this->state->get("agroconecta_bridge_dismissed_{$userId}", []);
  }

}
