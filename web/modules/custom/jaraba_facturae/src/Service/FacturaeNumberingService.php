<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de numeracion secuencial para facturas Facturae.
 *
 * Implementa numeracion con bloqueo pesimista (SELECT FOR UPDATE) para
 * garantizar unicidad incluso bajo concurrencia. El patron de numeracion
 * es configurable por tenant con tokens: {SERIE}, {YYYY}, {MM}, {NUM:N}.
 *
 * Spec: Doc 180, Seccion 3.5 (NumberingService).
 * Plan: FASE 6, entregable F6-5.
 */
class FacturaeNumberingService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets the next sequential invoice number for a tenant and series.
   *
   * Uses pessimistic locking to prevent duplicate numbers under concurrency.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $serie
   *   The invoice series code (e.g., 'FA', 'FB').
   *
   * @return string
   *   The formatted invoice number.
   *
   * @throws \RuntimeException
   *   If the tenant config is not found or the lock cannot be acquired.
   */
  public function getNextNumber(int $tenantId, string $serie = ''): string {
    $storage = $this->entityTypeManager->getStorage('facturae_tenant_config');

    // Load tenant config.
    $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);
    if (empty($configs)) {
      throw new \RuntimeException("Facturae tenant config not found for tenant $tenantId.");
    }
    $config = reset($configs);

    if (empty($serie)) {
      $serie = $config->get('default_series')->value ?? 'FA';
    }

    $pattern = $config->get('numbering_pattern')->value ?? '{SERIE}{YYYY}-{NUM:5}';

    // Pessimistic lock via transaction + SELECT FOR UPDATE.
    $transaction = $this->database->startTransaction();

    try {
      // Lock the row to prevent concurrent reads.
      $currentNumber = $this->database->query(
        'SELECT next_number FROM {facturae_tenant_config} WHERE id = :id FOR UPDATE',
        [':id' => $config->id()]
      )->fetchField();

      if ($currentNumber === FALSE) {
        throw new \RuntimeException("Failed to lock tenant config row for tenant $tenantId.");
      }

      $nextNumber = (int) $currentNumber;

      // Increment the counter.
      $this->database->update('facturae_tenant_config')
        ->fields(['next_number' => $nextNumber + 1])
        ->condition('id', $config->id())
        ->execute();

      $formatted = $this->formatNumber($nextNumber, $pattern, $serie);

      $this->logger->info('Generated Facturae number @number for tenant @tenant, series @serie.', [
        '@number' => $formatted,
        '@tenant' => $tenantId,
        '@serie' => $serie,
      ]);

      return $formatted;
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      $this->logger->error('Failed to generate Facturae number for tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Formats a sequence number according to the pattern.
   *
   * Supported tokens:
   * - {SERIE}: Invoice series code.
   * - {YYYY}: Current 4-digit year.
   * - {MM}: Current 2-digit month.
   * - {NUM:N}: Sequence number with N-digit zero padding.
   *
   * @param int $sequence
   *   The raw sequence number.
   * @param string $pattern
   *   The numbering pattern with tokens.
   * @param string $serie
   *   The invoice series code.
   *
   * @return string
   *   The formatted invoice number.
   */
  public function formatNumber(int $sequence, string $pattern, string $serie = ''): string {
    $result = $pattern;

    $result = str_replace('{SERIE}', $serie, $result);
    $result = str_replace('{YYYY}', date('Y'), $result);
    $result = str_replace('{MM}', date('m'), $result);

    // Handle {NUM:N} token with variable padding.
    if (preg_match('/\{NUM:(\d+)\}/', $result, $matches)) {
      $padding = (int) $matches[1];
      $paddedNumber = str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
      $result = preg_replace('/\{NUM:\d+\}/', $paddedNumber, $result);
    }
    elseif (str_contains($result, '{NUM}')) {
      $result = str_replace('{NUM}', (string) $sequence, $result);
    }

    return $result;
  }

}
