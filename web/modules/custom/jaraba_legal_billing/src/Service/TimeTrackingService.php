<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de control de tiempo (Time Tracking).
 *
 * Estructura: Gestiona registros de tiempo vinculados a expedientes.
 * Logica: CRUD de TimeEntry, calculo de horas no facturadas, totales por caso.
 */
class TimeTrackingService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra una entrada de tiempo.
   */
  public function logTime(array $data): array {
    try {
      $storage = $this->entityTypeManager->getStorage('time_entry');
      $entry = $storage->create([
        'uid' => $data['user_id'] ?? $this->currentUser->id(),
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'case_id' => $data['case_id'],
        'user_id' => $data['user_id'] ?? $this->currentUser->id(),
        'description' => $data['description'],
        'date' => $data['date'],
        'duration_minutes' => $data['duration_minutes'],
        'billing_rate' => $data['billing_rate'] ?? NULL,
        'is_billable' => $data['is_billable'] ?? TRUE,
      ]);
      $entry->save();

      return [
        'id' => (int) $entry->id(),
        'uuid' => $entry->uuid(),
        'duration_minutes' => (int) $entry->get('duration_minutes')->value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error logging time: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Lista entradas de tiempo de un expediente.
   */
  public function getTimeByCase(int $caseId, int $limit = 50, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('time_entry');
      $query = $storage->getQuery()
        ->condition('case_id', $caseId)
        ->accessCheck(TRUE)
        ->sort('date', 'DESC')
        ->range($offset, $limit);
      $ids = $query->execute();

      return array_map(fn($e) => $this->serializeTimeEntry($e), $storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting time by case: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Obtiene horas no facturadas de un expediente.
   */
  public function getUnbilledTime(int $caseId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('time_entry');
      $query = $storage->getQuery()
        ->condition('case_id', $caseId)
        ->condition('is_billable', TRUE)
        ->notExists('invoice_id')
        ->accessCheck(TRUE)
        ->sort('date', 'ASC');
      $ids = $query->execute();

      return array_map(fn($e) => $this->serializeTimeEntry($e), $storage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting unbilled time: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Calcula total de horas y coste de un expediente.
   */
  public function calculateTotal(int $caseId, bool $unbilledOnly = FALSE): array {
    try {
      $storage = $this->entityTypeManager->getStorage('time_entry');
      $query = $storage->getQuery()
        ->condition('case_id', $caseId)
        ->condition('is_billable', TRUE)
        ->accessCheck(TRUE);

      if ($unbilledOnly) {
        $query->notExists('invoice_id');
      }

      $ids = $query->execute();
      $entries = $storage->loadMultiple($ids);

      $totalMinutes = 0;
      $totalAmount = 0.0;
      foreach ($entries as $entry) {
        $minutes = (int) $entry->get('duration_minutes')->value;
        $rate = (float) ($entry->get('billing_rate')->value ?? 0);
        $totalMinutes += $minutes;
        $totalAmount += ($minutes / 60) * $rate;
      }

      return [
        'total_minutes' => $totalMinutes,
        'total_hours' => round($totalMinutes / 60, 2),
        'total_amount' => round($totalAmount, 2),
        'entry_count' => count($entries),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculating total: @msg', ['@msg' => $e->getMessage()]);
      return ['total_minutes' => 0, 'total_hours' => 0, 'total_amount' => 0, 'entry_count' => 0];
    }
  }

  /**
   * Serializa una entrada de tiempo.
   */
  public function serializeTimeEntry($entry): array {
    return [
      'id' => (int) $entry->id(),
      'uuid' => $entry->uuid(),
      'case_id' => (int) ($entry->get('case_id')->target_id ?? 0),
      'user_id' => (int) ($entry->get('user_id')->target_id ?? 0),
      'description' => $entry->get('description')->value ?? '',
      'date' => $entry->get('date')->value ?? '',
      'duration_minutes' => (int) ($entry->get('duration_minutes')->value ?? 0),
      'billing_rate' => (float) ($entry->get('billing_rate')->value ?? 0),
      'is_billable' => (bool) $entry->get('is_billable')->value,
      'invoice_id' => $entry->get('invoice_id')->target_id ? (int) $entry->get('invoice_id')->target_id : NULL,
      'created' => $entry->get('created')->value ?? '',
    ];
  }

}
