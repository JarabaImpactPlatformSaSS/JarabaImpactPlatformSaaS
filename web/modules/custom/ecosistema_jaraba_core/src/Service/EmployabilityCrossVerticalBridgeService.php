<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical value bridges for the Empleabilidad vertical.
 *
 * Evaluates and presents bridges between Empleabilidad and other
 * SaaS verticals (Emprendimiento, Servicios, Formación, Comercio)
 * to maximize customer LTV.
 *
 * Plan Elevación Empleabilidad v1 — Fase 8.
 */
class EmployabilityCrossVerticalBridgeService {

  /**
   * Bridge definitions keyed by bridge ID.
   */
  protected const BRIDGES = [
    'emprendimiento' => [
      'id' => 'emprendimiento',
      'vertical' => 'emprendimiento',
      'icon' => 'rocket',
      'color' => 'var(--ej-color-emprendimiento, #f59e0b)',
      'message' => '¿Has considerado crear tu propio negocio? El 23% de nuestros emprendedores exitosos comenzaron aquí.',
      'cta_label' => 'Diagnóstico de Negocio',
      'cta_url' => '/diagnostico-empresarial',
      'condition' => 'time_in_state_90_days',
      'priority' => 10,
    ],
    'servicios' => [
      'id' => 'servicios',
      'vertical' => 'servicios',
      'icon' => 'briefcase',
      'color' => 'var(--ej-color-servicios, #8b5cf6)',
      'message' => 'Tus habilidades tienen alta demanda como freelance. Mira estas oportunidades.',
      'cta_label' => 'Perfil Freelancer',
      'cta_url' => '/servicios/perfil-freelancer',
      'condition' => 'has_freelance_skills',
      'priority' => 20,
    ],
    'formacion' => [
      'id' => 'formacion',
      'vertical' => 'formacion',
      'icon' => 'book',
      'color' => 'var(--ej-color-formacion, #0ea5e9)',
      'message' => '¡Felicidades por tu nuevo puesto! Potencia tu carrera con formación avanzada.',
      'cta_label' => 'Catálogo LMS Premium',
      'cta_url' => '/courses',
      'condition' => 'recently_hired',
      'priority' => 30,
    ],
    'comercio' => [
      'id' => 'comercio',
      'vertical' => 'comercio',
      'icon' => 'shop',
      'color' => 'var(--ej-color-comercio, #059669)',
      'message' => 'Conecta tu negocio con ComercioConecta para vender tus productos.',
      'cta_label' => 'Landing ComercioConecta',
      'cta_url' => '/comercio',
      'condition' => 'verified_employer',
      'priority' => 40,
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
   *   icon, color, priority.
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

    $key = "employability_bridge_impressions_{$userId}";
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
    $key = "employability_bridge_responses_{$userId}";
    $responses = \Drupal::state()->get($key, []);
    $responses[] = [
      'bridge_id' => $bridgeId,
      'response' => $response,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    \Drupal::state()->set($key, array_slice($responses, -100));

    if ($response === 'dismissed') {
      $dismissedKey = "employability_bridge_dismissed_{$userId}";
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
      'time_in_state_90_days' => $this->checkTimeInState($userId, 90),
      'has_freelance_skills' => $this->checkFreelanceSkills($userId),
      'recently_hired' => $this->checkRecentlyHired($userId),
      'verified_employer' => $this->checkVerifiedEmployer($userId),
      default => FALSE,
    };
  }

  /**
   * Checks if user has been in current journey state for more than N days.
   */
  protected function checkTimeInState(int $userId, int $days): bool {
    if (!\Drupal::moduleHandler()->moduleExists('jaraba_journey')) {
      return FALSE;
    }

    try {
      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($states)) {
        return FALSE;
      }

      $state = reset($states);
      $lastTransition = $state->get('last_transition')->value ?? 0;

      if (!$lastTransition) {
        return FALSE;
      }

      $elapsed = \Drupal::time()->getRequestTime() - (int) $lastTransition;
      return $elapsed > ($days * 86400);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if user has freelance-suitable skills.
   */
  protected function checkFreelanceSkills(int $userId): bool {
    $freelanceSkills = [
      'web_development', 'graphic_design', 'copywriting', 'marketing_digital',
      'data_analysis', 'video_editing', 'translation', 'consulting',
      'programming', 'ux_design', 'seo', 'social_media',
    ];

    try {
      $skills = $this->entityTypeManager
        ->getStorage('candidate_skill')
        ->loadByProperties(['user_id' => $userId]);

      foreach ($skills as $skill) {
        $skillName = strtolower($skill->get('skill_name')->value ?? '');
        foreach ($freelanceSkills as $freeSkill) {
          $readable = str_replace('_', ' ', $freeSkill);
          if (str_contains($skillName, $readable) || str_contains($skillName, $freeSkill)) {
            return TRUE;
          }
        }
      }
    }
    catch (\Exception $e) {
      // candidate_skill entity may not exist.
    }

    return FALSE;
  }

  /**
   * Checks if user was recently hired (within last 30 days).
   */
  protected function checkRecentlyHired(int $userId): bool {
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

      $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);
      foreach ($applications as $app) {
        $changed = (int) ($app->get('changed')->value ?? 0);
        if ($changed > $thirtyDaysAgo) {
          return TRUE;
        }
      }
    }
    catch (\Exception $e) {
      // job_application entity may not exist.
    }

    return FALSE;
  }

  /**
   * Checks if user is a verified employer.
   */
  protected function checkVerifiedEmployer(int $userId): bool {
    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user || !$user->hasRole('employer')) {
        return FALSE;
      }

      if ($user->hasField('field_employer_verified')) {
        return (bool) ($user->get('field_employer_verified')->value ?? FALSE);
      }

      return TRUE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets list of dismissed bridge IDs for a user.
   */
  protected function getDismissedBridges(int $userId): array {
    return \Drupal::state()->get("employability_bridge_dismissed_{$userId}", []);
  }

}
