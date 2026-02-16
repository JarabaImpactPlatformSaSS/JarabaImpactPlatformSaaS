<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuEventLog;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio de registro de eventos SIF (Sistema Informatico de Facturacion).
 *
 * Registra eventos inmutables en el log VeriFactu conforme RD 1007/2023.
 * Mantiene su propia cadena de hash independiente de la cadena de registros
 * de facturas, garantizando trazabilidad completa del SIF.
 *
 * RESTRICCIONES:
 * - El log es append-only: no se permite edicion ni borrado.
 * - Cada entrada tiene su hash SHA-256 encadenado al anterior.
 * - Un fallo en el logging NUNCA debe interrumpir el flujo principal.
 *
 * Spec: Doc 179, Seccion 3.4. Plan: FASE 2, entregable F2-4.
 */
class VeriFactuEventLogService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registers a SIF event in the VeriFactu event log.
   *
   * @param string $eventType
   *   One of the 12 defined event types (SYSTEM_START, RECORD_CREATE, etc.).
   * @param int $tenantId
   *   The tenant ID associated with this event.
   * @param int|null $recordId
   *   The related VeriFactuInvoiceRecord ID, if applicable.
   * @param array $details
   *   Structured event details. Optional keys:
   *   - 'severity': 'info'|'warning'|'error'|'critical' (default: 'info')
   *   - 'description': Human-readable event description.
   *   - Additional keys are serialized as JSON in the details field.
   *
   * @return \Drupal\jaraba_verifactu\Entity\VeriFactuEventLog|null
   *   The created event log entity, or NULL if logging failed.
   */
  public function logEvent(string $eventType, int $tenantId, ?int $recordId, array $details = []): ?VeriFactuEventLog {
    try {
      $request = $this->requestStack->getCurrentRequest();

      // Get the previous event hash for this tenant's log chain.
      $previousEventHash = $this->getLastEventHash($tenantId);

      // Build the event description.
      $severity = $details['severity'] ?? 'info';
      $description = $details['description'] ?? '';
      unset($details['severity'], $details['description']);

      // Compute hash for this event entry.
      $eventHash = $this->computeEventHash(
        $eventType,
        $tenantId,
        $severity,
        $previousEventHash,
      );

      // Serialize remaining details as JSON.
      $detailsJson = !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : NULL;

      $values = [
        'event_type' => $eventType,
        'severity' => $severity,
        'description' => $description,
        'details' => $detailsJson,
        'hash_event' => $eventHash,
        'hash_previous_event' => $previousEventHash,
        'record_id' => $recordId,
        'actor_id' => (int) $this->currentUser->id() ?: NULL,
        'ip_address' => $request?->getClientIp() ?? '',
        'tenant_id' => $tenantId,
      ];

      /** @var \Drupal\jaraba_verifactu\Entity\VeriFactuEventLog $entity */
      $entity = $this->entityTypeManager
        ->getStorage('verifactu_event_log')
        ->create($values);
      $entity->save();

      return $entity;
    }
    catch (\Exception $e) {
      // Event logging MUST NEVER break the application flow.
      $this->logger->error('Failed to write VeriFactu event log for @event (tenant @tenant): @message', [
        '@event' => $eventType,
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets the hash of the last event log entry for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return string|null
   *   The last event hash, or NULL if no events exist.
   */
  protected function getLastEventHash(int $tenantId): ?string {
    $storage = $this->entityTypeManager->getStorage('verifactu_event_log');
    $ids = $storage->getQuery()
      ->condition('tenant_id', $tenantId)
      ->sort('id', 'DESC')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $lastEvent = $storage->load(reset($ids));
    return $lastEvent?->get('hash_event')->value;
  }

  /**
   * Computes the SHA-256 hash for an event log entry.
   *
   * Independent chain from invoice records. Uses event metadata
   * to create a unique hash per entry.
   *
   * @param string $eventType
   *   The event type.
   * @param int $tenantId
   *   The tenant ID.
   * @param string $severity
   *   The event severity.
   * @param string|null $previousHash
   *   Hash of the previous event, or NULL if first.
   *
   * @return string
   *   64-character hexadecimal SHA-256 hash.
   */
  protected function computeEventHash(
    string $eventType,
    int $tenantId,
    string $severity,
    ?string $previousHash,
  ): string {
    $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s');

    $hashInput = implode(',', [
      $eventType,
      (string) $tenantId,
      $severity,
      $timestamp,
      $previousHash ?? '',
    ]);

    return hash('sha256', $hashInput);
  }

}
