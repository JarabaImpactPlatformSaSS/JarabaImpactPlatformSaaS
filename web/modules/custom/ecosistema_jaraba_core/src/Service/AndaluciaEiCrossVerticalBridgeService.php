<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Cross-vertical value bridges for the Andalucía +ei vertical.
 *
 * Evaluates and presents bridges between Andalucía +ei and other
 * SaaS verticals (Emprendimiento, Empleabilidad, Servicios, Formación)
 * to maximize customer LTV.
 *
 * Bridges:
 * - emprendimiento_avanzado: Participante en inserción + >50h + interés emprendedor
 * - empleabilidad_express: Participante sin inserción tras 90 días
 * - servicios_freelance: Participante con skills digitales (carril Impulso Digital)
 * - formacion_continua: Participante insertado < 30 días
 *
 * Plan Elevación Andalucía +ei v1 — Fase 6.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\EmployabilityCrossVerticalBridgeService
 */
class AndaluciaEiCrossVerticalBridgeService {

  /**
   * Bridge definitions keyed by bridge ID.
   */
  protected const BRIDGES = [
    'emprendimiento_avanzado' => [
      'id' => 'emprendimiento_avanzado',
      'vertical' => 'emprendimiento',
      'icon_category' => 'business',
      'icon_name' => 'rocket',
      'color' => 'var(--ej-color-impulse, #FF8C42)',
      'message' => 'Tu perfil emprendedor destaca. El 23% de participantes como tú han lanzado su propio negocio.',
      'cta_label' => 'Diagnóstico de Negocio',
      'cta_url' => '/emprendimiento/diagnostico',
      'condition' => 'insertion_plus_entrepreneur_interest',
      'priority' => 10,
    ],
    'empleabilidad_express' => [
      'id' => 'empleabilidad_express',
      'vertical' => 'empleabilidad',
      'icon_category' => 'business',
      'icon_name' => 'target',
      'color' => 'var(--ej-color-innovation, #00A9A5)',
      'message' => 'Complementa tu formación con herramientas de búsqueda activa de empleo y preparación de entrevistas.',
      'cta_label' => 'Diagnóstico de Empleabilidad',
      'cta_url' => '/empleabilidad/diagnostico',
      'condition' => 'no_insertion_90_days',
      'priority' => 20,
    ],
    'servicios_freelance' => [
      'id' => 'servicios_freelance',
      'vertical' => 'servicios',
      'icon_category' => 'business',
      'icon_name' => 'briefcase',
      'color' => 'var(--ej-color-earth, #556B2F)',
      'message' => 'Tus habilidades digitales tienen alta demanda como freelance. Mira estas oportunidades.',
      'cta_label' => 'Perfil Freelancer',
      'cta_url' => '/servicios/perfil-freelancer',
      'condition' => 'digital_skills_track',
      'priority' => 30,
    ],
    'formacion_continua' => [
      'id' => 'formacion_continua',
      'vertical' => 'formacion',
      'icon_category' => 'general',
      'icon_name' => 'graduation',
      'color' => 'var(--ej-color-corporate, #233D63)',
      'message' => 'Felicidades por tu inserción. Potencia tu carrera con formación avanzada certificada.',
      'cta_label' => 'Catálogo Formativo',
      'cta_url' => '/courses',
      'condition' => 'recently_inserted',
      'priority' => 15,
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
   *   icon_category, icon_name, color, priority.
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

    $key = "andalucia_ei_bridge_impressions_{$userId}";
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
    $key = "andalucia_ei_bridge_responses_{$userId}";
    $responses = \Drupal::state()->get($key, []);
    $responses[] = [
      'bridge_id' => $bridgeId,
      'response' => $response,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    \Drupal::state()->set($key, array_slice($responses, -100));

    if ($response === 'dismissed') {
      $dismissedKey = "andalucia_ei_bridge_dismissed_{$userId}";
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
      'insertion_plus_entrepreneur_interest' => $this->checkInsertionPlusEntrepreneur($userId),
      'no_insertion_90_days' => $this->checkNoInsertion90Days($userId),
      'digital_skills_track' => $this->checkDigitalSkillsTrack($userId),
      'recently_inserted' => $this->checkRecentlyInserted($userId),
      default => FALSE,
    };
  }

  /**
   * Checks if participant is in insertion phase with >50h and entrepreneur interest.
   */
  protected function checkInsertionPlusEntrepreneur($userId): bool {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($participants)) {
        return FALSE;
      }

      $participant = reset($participants);
      $fase = $participant->get('fase_actual')->value ?? 'atencion';

      if ($fase !== 'insercion') {
        return FALSE;
      }

      // Check >50h total orientation.
      $totalHoras = (float) ($participant->get('horas_mentoria_ia')->value ?? 0)
        + (float) ($participant->get('horas_mentoria_humana')->value ?? 0)
        + (float) ($participant->get('horas_orientacion_ind')->value ?? 0)
        + (float) ($participant->get('horas_orientacion_grup')->value ?? 0);

      if ($totalHoras < 50) {
        return FALSE;
      }

      // Check entrepreneur interest via colectivo or perfil field.
      $colectivo = $participant->get('colectivo')->value ?? '';
      return str_contains(strtolower($colectivo), 'emprendedor')
        || str_contains(strtolower($colectivo), 'autoempleo');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if participant has been in atencion without insertion for 90+ days.
   */
  protected function checkNoInsertion90Days(int $userId): bool {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($participants)) {
        return FALSE;
      }

      $participant = reset($participants);
      $fase = $participant->get('fase_actual')->value ?? 'atencion';

      if ($fase !== 'atencion') {
        return FALSE;
      }

      $created = (int) ($participant->get('created')->value ?? 0);
      if (!$created) {
        return FALSE;
      }

      $elapsed = \Drupal::time()->getRequestTime() - $created;
      return $elapsed > (90 * 86400);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if participant is in the "Impulso Digital" track (digital skills).
   */
  protected function checkDigitalSkillsTrack(int $userId): bool {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($participants)) {
        return FALSE;
      }

      $participant = reset($participants);
      $colectivo = strtolower($participant->get('colectivo')->value ?? '');
      return str_contains($colectivo, 'digital')
        || str_contains($colectivo, 'tecnol');
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Checks if participant was recently inserted (within last 30 days).
   */
  protected function checkRecentlyInserted(int $userId): bool {
    try {
      $participants = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($participants)) {
        return FALSE;
      }

      $participant = reset($participants);
      $fase = $participant->get('fase_actual')->value ?? 'atencion';

      if ($fase !== 'insercion') {
        return FALSE;
      }

      $changed = (int) ($participant->get('changed')->value ?? 0);
      if (!$changed) {
        return FALSE;
      }

      $thirtyDaysAgo = \Drupal::time()->getRequestTime() - (30 * 86400);
      return $changed > $thirtyDaysAgo;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Gets list of dismissed bridge IDs for a user.
   */
  protected function getDismissedBridges(int $userId): array {
    return \Drupal::state()->get("andalucia_ei_bridge_dismissed_{$userId}", []);
  }

}
