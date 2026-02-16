<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_verifactu\Exception\VeriFactuChainBreakException;
use Drupal\jaraba_verifactu\ValueObject\ChainIntegrityResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de hash SHA-256 encadenado VeriFactu (Anexo II RD 1007/2023).
 *
 * Calcula el hash SHA-256 de cada registro de factura, encadenandolo
 * con el hash del registro anterior para formar una cadena de integridad
 * inmutable. Tambien verifica la integridad completa de la cadena.
 *
 * Algoritmo (Anexo II):
 *   Hash = SHA-256(NIF_emisor + "," + numero_factura + "," +
 *          fecha_expedicion + "," + tipo_factura + "," +
 *          cuota_tributaria + "," + importe_total + "," +
 *          tipo_registro + "," + hash_anterior)
 *
 * Usa LockBackendInterface para prevenir condiciones de carrera
 * en la cadena (AUDIT-PERF-002).
 *
 * Spec: Doc 179, Seccion 3.1. Plan: FASE 2, entregable F2-1.
 */
class VeriFactuHashService {

  /**
   * Lock name prefix for hash chain operations.
   */
  const LOCK_PREFIX = 'verifactu_chain_';

  /**
   * Lock timeout in seconds.
   */
  const LOCK_TIMEOUT = 30;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LockBackendInterface $lock,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calculates the SHA-256 hash for an alta (new) record.
   *
   * @param array $fields
   *   Required keys: nif_emisor, numero_factura, fecha_expedicion,
   *   tipo_factura, cuota_tributaria, importe_total.
   * @param string|null $previousHash
   *   Hash of the previous record, or NULL if first in chain.
   *
   * @return string
   *   64-character hexadecimal SHA-256 hash.
   *
   * @throws \InvalidArgumentException
   *   If required fields are missing.
   */
  public function calculateAltaHash(array $fields, ?string $previousHash): string {
    $this->validateRequiredFields($fields);
    return $this->computeHash($fields, 'alta', $previousHash);
  }

  /**
   * Calculates the SHA-256 hash for an anulacion (cancellation) record.
   *
   * @param array $fields
   *   Required keys: nif_emisor, numero_factura, fecha_expedicion,
   *   tipo_factura, cuota_tributaria, importe_total.
   * @param string|null $previousHash
   *   Hash of the previous record, or NULL if first in chain.
   *
   * @return string
   *   64-character hexadecimal SHA-256 hash.
   *
   * @throws \InvalidArgumentException
   *   If required fields are missing.
   */
  public function calculateAnulacionHash(array $fields, ?string $previousHash): string {
    $this->validateRequiredFields($fields);
    return $this->computeHash($fields, 'anulacion', $previousHash);
  }

  /**
   * Verifies the integrity of the entire hash chain for a tenant.
   *
   * Iterates over all VeriFactuInvoiceRecord entities for the given
   * tenant in creation order, recomputing each hash and comparing
   * it against the stored hash_record value.
   *
   * @param int $tenantId
   *   The tenant ID to verify.
   *
   * @return \Drupal\jaraba_verifactu\ValueObject\ChainIntegrityResult
   *   Result of the integrity verification.
   */
  public function verifyChainIntegrity(int $tenantId): ChainIntegrityResult {
    $lockName = self::LOCK_PREFIX . $tenantId;

    if (!$this->lock->acquire($lockName, self::LOCK_TIMEOUT)) {
      return ChainIntegrityResult::error(
        'Could not acquire lock for tenant ' . $tenantId . '. Another verification may be in progress.',
      );
    }

    try {
      $startTime = microtime(TRUE);

      $storage = $this->entityTypeManager->getStorage('verifactu_invoice_record');
      $ids = $storage->getQuery()
        ->condition('tenant_id', $tenantId)
        ->sort('id', 'ASC')
        ->accessCheck(FALSE)
        ->execute();

      if (empty($ids)) {
        $elapsed = (microtime(TRUE) - $startTime) * 1000;
        return ChainIntegrityResult::valid(0, $elapsed);
      }

      $totalRecords = count($ids);
      $validRecords = 0;
      $previousHash = NULL;

      // Process in batches to manage memory.
      $batches = array_chunk($ids, 100);

      foreach ($batches as $batchIds) {
        $records = $storage->loadMultiple($batchIds);

        foreach ($records as $record) {
          $fields = [
            'nif_emisor' => $record->get('nif_emisor')->value,
            'numero_factura' => $record->get('numero_factura')->value,
            'fecha_expedicion' => $record->get('fecha_expedicion')->value,
            'tipo_factura' => $record->get('tipo_factura')->value,
            'cuota_tributaria' => $record->get('cuota_tributaria')->value,
            'importe_total' => $record->get('importe_total')->value,
          ];

          $recordType = $record->get('record_type')->value;
          $expectedHash = $this->computeHash($fields, $recordType, $previousHash);
          $storedHash = $record->get('hash_record')->value;

          if ($expectedHash !== $storedHash) {
            $elapsed = (microtime(TRUE) - $startTime) * 1000;
            $this->logger->critical('Chain break detected for tenant @tenant at record @record.', [
              '@tenant' => $tenantId,
              '@record' => $record->id(),
            ]);

            return ChainIntegrityResult::broken(
              totalRecords: $totalRecords,
              validRecords: $validRecords,
              breakAtRecordId: (int) $record->id(),
              expectedHash: $expectedHash,
              actualHash: $storedHash,
              verificationTimeMs: $elapsed,
            );
          }

          $previousHash = $storedHash;
          $validRecords++;
        }
      }

      $elapsed = (microtime(TRUE) - $startTime) * 1000;

      $this->logger->info('Chain integrity verified for tenant @tenant: @count records, @time ms.', [
        '@tenant' => $tenantId,
        '@count' => $totalRecords,
        '@time' => round($elapsed, 2),
      ]);

      return ChainIntegrityResult::valid($totalRecords, $elapsed);
    }
    catch (\Exception $e) {
      $this->logger->error('Chain integrity verification failed for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return ChainIntegrityResult::error($e->getMessage());
    }
    finally {
      $this->lock->release($lockName);
    }
  }

  /**
   * Gets the last hash in the chain for a tenant.
   *
   * Used when creating a new record to chain it to the previous one.
   * Uses a lock to prevent race conditions.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return string|null
   *   The last hash, or NULL if no records exist.
   */
  public function getLastChainHash(int $tenantId): ?string {
    $storage = $this->entityTypeManager->getStorage('verifactu_tenant_config');
    $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

    if (empty($configs)) {
      return NULL;
    }

    $config = reset($configs);
    $hash = $config->get('last_chain_hash')->value;

    return $hash ?: NULL;
  }

  /**
   * Updates the last chain hash for a tenant after a new record.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param string $hash
   *   The new last hash.
   * @param int $recordId
   *   The entity ID of the last record.
   */
  public function updateLastChainHash(int $tenantId, string $hash, int $recordId): void {
    $storage = $this->entityTypeManager->getStorage('verifactu_tenant_config');
    $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

    if (empty($configs)) {
      $this->logger->error('No VeriFactu tenant config found for tenant @tenant when updating chain hash.', [
        '@tenant' => $tenantId,
      ]);
      return;
    }

    $config = reset($configs);
    $config->set('last_chain_hash', $hash);
    $config->set('last_record_id', $recordId);
    $config->save();
  }

  /**
   * Computes the SHA-256 hash per the Anexo II algorithm.
   *
   * @param array $fields
   *   The record fields.
   * @param string $recordType
   *   'alta' or 'anulacion'.
   * @param string|null $previousHash
   *   Previous hash or NULL for first record.
   *
   * @return string
   *   64-character hex SHA-256 hash.
   */
  protected function computeHash(array $fields, string $recordType, ?string $previousHash): string {
    $hashInput = implode(',', [
      $fields['nif_emisor'],
      $fields['numero_factura'],
      $fields['fecha_expedicion'],
      $fields['tipo_factura'],
      $fields['cuota_tributaria'],
      $fields['importe_total'],
      $recordType,
      $previousHash ?? '',
    ]);

    return hash('sha256', $hashInput);
  }

  /**
   * Validates that all required fields are present.
   *
   * @param array $fields
   *   The fields to validate.
   *
   * @throws \InvalidArgumentException
   *   If any required field is missing.
   */
  protected function validateRequiredFields(array $fields): void {
    $required = [
      'nif_emisor',
      'numero_factura',
      'fecha_expedicion',
      'tipo_factura',
      'cuota_tributaria',
      'importe_total',
    ];

    $missing = array_diff($required, array_keys($fields));
    if (!empty($missing)) {
      throw new \InvalidArgumentException(
        'Missing required fields for VeriFactu hash calculation: ' . implode(', ', $missing),
      );
    }
  }

}
