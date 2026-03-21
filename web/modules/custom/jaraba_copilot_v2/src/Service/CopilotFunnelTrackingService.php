<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Servicio de tracking del embudo de ventas desde el copilot.
 *
 * Loguea eventos de interaccion copilot para attribution y analytics.
 * Usa tabla directa (no ContentEntity) por alto volumen y rendimiento.
 *
 * Tabla: copilot_funnel_event
 */
class CopilotFunnelTrackingService {

  public function __construct(
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un evento del embudo de ventas.
   *
   * @param string $sessionId
   *   ID de sesion del copilot.
   * @param string $eventType
   *   Tipo: copilot_message_received, copilot_intent_detected, etc.
   * @param array<string, mixed> $data
   *   Datos del evento: vertical_detected, intent_type, promotion_mentioned,
   *   cta_generated, crm_contact_id, crm_opportunity_id, metadata.
   * @param string $ipHash
   *   Hash del IP (GDPR compliant).
   */
  public function logEvent(string $sessionId, string $eventType, array $data, string $ipHash): void {
    try {
      $this->database->insert('copilot_funnel_event')
        ->fields([
          'session_id' => $sessionId,
          'event_type' => $eventType,
          'vertical_detected' => $data['vertical_detected'] ?? NULL,
          'intent_type' => $data['intent_type'] ?? NULL,
          'promotion_mentioned' => $data['promotion_mentioned'] ?? NULL,
          'cta_generated' => $data['cta_generated'] ?? NULL,
          'crm_contact_id' => $data['crm_contact_id'] ?? NULL,
          'crm_opportunity_id' => $data['crm_opportunity_id'] ?? NULL,
          'ip_hash' => $ipHash,
          'created' => \Drupal::time()->getRequestTime(),
          'metadata' => isset($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : NULL,
        ])
        ->execute();
    }
    catch (\Throwable $e) {
      $this->logger->warning('Funnel event logging failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene estadisticas de eventos por tipo en un rango de fechas.
   *
   * @param int $fromTimestamp
   *   Timestamp inicio.
   * @param int $toTimestamp
   *   Timestamp fin.
   *
   * @return array<string, int>
   *   Mapa event_type => count.
   */
  public function getEventStats(int $fromTimestamp, int $toTimestamp): array {
    try {
      $query = $this->database->select('copilot_funnel_event', 'e')
        ->fields('e', ['event_type'])
        ->condition('created', $fromTimestamp, '>=')
        ->condition('created', $toTimestamp, '<=')
        ->groupBy('event_type');
      $query->addExpression('COUNT(*)', 'count');
      $result = $query->execute();

      $stats = [];
      if ($result !== NULL) {
        foreach ($result as $row) {
          $stats[$row->event_type] = (int) $row->count;
        }
      }

      return $stats;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Funnel stats query failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
