<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical value bridges for the Emprendimiento vertical.
 *
 * Evaluates and presents bridges between Emprendimiento and other
 * SaaS verticals (Formación, Servicios, Comercio) to maximize
 * customer LTV.
 *
 * Bridges:
 * - formacion: Team skills training for scaling entrepreneurs
 * - servicios: Outsource MVP development for non-technical founders
 * - comercio: Sell product post-MVP validation
 *
 * Plan Elevación Emprendimiento v2 — Fase 5 (G5).
 */
class EmprendimientoCrossVerticalBridgeService {

  /**
   * Bridge definitions keyed by bridge ID.
   */
  protected const BRIDGES = [
    'formacion' => [
      'id' => 'formacion',
      'vertical' => 'formacion',
      'icon' => 'graduation-cap',
      'color' => 'var(--ej-color-formacion, #8b5cf6)',
      'message' => 'Tu negocio está creciendo. Forma a tu equipo con cursos especializados para escalar con solidez.',
      'cta_label' => 'Formar a tu equipo',
      'cta_url' => '/courses',
      'condition' => 'scaling_needs_team_skills',
      'priority' => 10,
    ],
    'servicios' => [
      'id' => 'servicios',
      'vertical' => 'servicios',
      'icon' => 'tools',
      'color' => 'var(--ej-color-servicios, #06b6d4)',
      'message' => 'Necesitas desarrollar tu MVP pero no tienes equipo técnico. Conecta con profesionales verificados.',
      'cta_label' => 'Contratar servicio',
      'cta_url' => '/servicios/marketplace',
      'condition' => 'needs_outsource_mvp',
      'priority' => 20,
    ],
    'comercio' => [
      'id' => 'comercio',
      'vertical' => 'comercio',
      'icon' => 'shopping-cart',
      'color' => 'var(--ej-color-comercio, #ec4899)',
      'message' => 'Tu producto está validado. Empieza a venderlo a través de ComercioConecta y llega a más clientes.',
      'cta_label' => 'Vender tu producto',
      'cta_url' => '/comercio',
      'condition' => 'has_product_post_mvp',
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
   * Evaluates available bridges for a user based on their current state.
   *
   * @return array
   *   List of bridges with: id, vertical, message, cta_url, cta_label,
   *   icon, color, priority. Max 2 bridges.
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

    $key = "emprendimiento_bridge_impressions_{$userId}";
    $impressions = \Drupal::state()->get($key, []);
    $impressions[] = [
      'bridge_id' => $bridgeId,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    \Drupal::state()->set($key, array_slice($impressions, -50));

    return $bridge;
  }

  /**
   * Tracks user response to a bridge (accepted or dismissed).
   */
  public function trackBridgeResponse(int $userId, string $bridgeId, string $response): void {
    $key = "emprendimiento_bridge_responses_{$userId}";
    $responses = \Drupal::state()->get($key, []);
    $responses[] = [
      'bridge_id' => $bridgeId,
      'response' => $response,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    \Drupal::state()->set($key, array_slice($responses, -100));

    if ($response === 'dismissed') {
      $dismissedKey = "emprendimiento_bridge_dismissed_{$userId}";
      $dismissed = \Drupal::state()->get($dismissedKey, []);
      if (!in_array($bridgeId, $dismissed, TRUE)) {
        $dismissed[] = $bridgeId;
        \Drupal::state()->set($dismissedKey, $dismissed);
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
      'scaling_needs_team_skills' => $this->checkScalingNeedsTeamSkills($userId),
      'needs_outsource_mvp' => $this->checkNeedsOutsourceMvp($userId),
      'has_product_post_mvp' => $this->checkHasProductPostMvp($userId),
      default => FALSE,
    };
  }

  /**
   * Checks if entrepreneur is scaling and has team that needs training.
   *
   * Condition: journey_state = retention + has_team_flag in profile.
   */
  protected function checkScalingNeedsTeamSkills(int $userId): bool {
    try {
      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties([
          'user_id' => $userId,
          'vertical' => 'emprendimiento',
        ]);

      if (empty($states)) {
        return FALSE;
      }

      $state = reset($states);
      $currentState = $state->get('journey_state')->value ?? '';
      if ($currentState !== 'retention') {
        return FALSE;
      }

      // Check for team flag in entrepreneur profile.
      $profiles = $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->loadByProperties(['uid' => $userId]);

      if (!empty($profiles)) {
        $profile = reset($profiles);
        if ($profile->hasField('has_team') && $profile->get('has_team')->value) {
          return TRUE;
        }
      }

      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if entrepreneur needs to outsource MVP development.
   *
   * Condition: journey_state = engagement + experiments > 0 + no tech_skills.
   */
  protected function checkNeedsOutsourceMvp(int $userId): bool {
    try {
      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties([
          'user_id' => $userId,
          'vertical' => 'emprendimiento',
        ]);

      if (empty($states)) {
        return FALSE;
      }

      $state = reset($states);
      $currentState = $state->get('journey_state')->value ?? '';
      if ($currentState !== 'engagement') {
        return FALSE;
      }

      // Check for experiments.
      $experiments = (int) $this->entityTypeManager
        ->getStorage('experiment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->count()
        ->execute();

      if ($experiments === 0) {
        return FALSE;
      }

      // Check for tech_skills flag (absence = needs outsourcing).
      $profiles = $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->loadByProperties(['uid' => $userId]);

      if (!empty($profiles)) {
        $profile = reset($profiles);
        if ($profile->hasField('tech_skills') && $profile->get('tech_skills')->value) {
          return FALSE;
        }
        return TRUE;
      }

      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if entrepreneur has a validated product ready for commerce.
   *
   * Condition: experiment with decision VALIDATED + physical/digital product.
   */
  protected function checkHasProductPostMvp(int $userId): bool {
    try {
      // Check for validated experiment.
      $validated = (int) $this->entityTypeManager
        ->getStorage('experiment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('decision', 'VALIDATED')
        ->count()
        ->execute();

      if ($validated === 0) {
        return FALSE;
      }

      // Check for product type in canvas context.
      $canvases = $this->entityTypeManager
        ->getStorage('business_model_canvas')
        ->loadByProperties(['user_id' => $userId]);

      if (!empty($canvases)) {
        $canvas = reset($canvases);
        $contextData = $canvas->get('context_data')->value ?? '';
        $context = !empty($contextData) ? json_decode($contextData, TRUE) : [];

        $productTypes = ['physical', 'digital', 'producto', 'bien'];
        $revenueStreams = strtolower($context['revenue_streams'] ?? '');
        foreach ($productTypes as $type) {
          if (str_contains($revenueStreams, $type)) {
            return TRUE;
          }
        }
      }

      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets list of dismissed bridge IDs for a user.
   */
  protected function getDismissedBridges(int $userId): array {
    return \Drupal::state()->get("emprendimiento_bridge_dismissed_{$userId}", []);
  }

}
