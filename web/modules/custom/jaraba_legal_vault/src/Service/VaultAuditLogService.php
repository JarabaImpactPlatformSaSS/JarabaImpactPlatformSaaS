<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio de auditoria inmutable con hash chain SHA-256.
 *
 * ESTRUCTURA:
 * Registra todas las acciones sobre documentos del vault en una cadena
 * de hash inmutable. Cada entrada incluye SHA-256(prev_hash + record_data),
 * creando una cadena verificable que detecta cualquier modificacion.
 *
 * LOGICA:
 * - log(): Crea entrada append-only con hash chain.
 * - getAuditTrail(): Obtiene historial de un documento.
 * - verifyIntegrity(): Verifica la cadena completa de un documento.
 *
 * RELACIONES:
 * - VaultAuditLogService -> EntityTypeManager: carga DocumentAuditLog.
 * - VaultAuditLogService -> AccountProxy: actor actual.
 * - VaultAuditLogService -> RequestStack: IP del actor.
 * - VaultAuditLogService <- DocumentVaultService: registra operaciones.
 * - VaultAuditLogService <- DocumentAccessService: registra comparticiones.
 */
class VaultAuditLogService {

  /**
   * Construye una nueva instancia de VaultAuditLogService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra una accion en el audit log con hash chain.
   *
   * @param int $documentId
   *   ID del documento seguro.
   * @param string $action
   *   Tipo: created, viewed, downloaded, shared, signed, revoked, deleted.
   * @param array $details
   *   Detalles contextuales (JSON).
   * @param int|null $actorId
   *   ID del actor (NULL = usuario actual).
   *
   * @return int
   *   ID de la entrada creada.
   */
  public function log(int $documentId, string $action, array $details = [], ?int $actorId = NULL): int {
    try {
      $storage = $this->entityTypeManager->getStorage('document_audit_log');
      $actorId = $actorId ?? (int) $this->currentUser->id();
      $actorIp = $this->getClientIp();

      // Obtener hash anterior de la cadena.
      $prevHash = $this->getLastHash($documentId);

      // Construir datos del registro para hash.
      $recordData = implode('|', [
        $documentId,
        $action,
        $actorId,
        $actorIp,
        json_encode($details, JSON_UNESCAPED_UNICODE),
        (string) time(),
      ]);

      // Calcular hash chain: SHA-256(prev_hash + record_data).
      $hashChain = hash('sha256', $prevHash . $recordData);

      $entry = $storage->create([
        'document_id' => $documentId,
        'action' => $action,
        'actor_id' => $actorId ?: NULL,
        'actor_ip' => $actorIp,
        'details' => $details,
        'hash_chain' => $hashChain,
      ]);
      $entry->save();

      return (int) $entry->id();
    }
    catch (\Exception $e) {
      $this->logger->error('VaultAuditLog: Error registrando accion @action para doc @id: @msg', [
        '@action' => $action,
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Obtiene el historial de auditoria de un documento.
   *
   * @param int $documentId
   *   ID del documento.
   * @param int $limit
   *   Maximo de entradas.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array
   *   Array con 'entries' y 'total'.
   */
  public function getAuditTrail(int $documentId, int $limit = 50, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_audit_log');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('document_id', $documentId)
        ->sort('created', 'DESC')
        ->range($offset, $limit);

      $countQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('document_id', $documentId)
        ->count();

      $ids = $query->execute();
      $total = (int) $countQuery->execute();

      return [
        'entries' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('VaultAuditLog: Error obteniendo trail para doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return ['entries' => [], 'total' => 0];
    }
  }

  /**
   * Verifica la integridad de la cadena de hash de un documento.
   *
   * @param int $documentId
   *   ID del documento.
   *
   * @return array{valid: bool, entries_checked: int, error: string|null}
   *   Resultado de la verificacion.
   */
  public function verifyIntegrity(int $documentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_audit_log');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('document_id', $documentId)
        ->sort('id', 'ASC')
        ->execute();

      if (empty($ids)) {
        return ['valid' => TRUE, 'entries_checked' => 0, 'error' => NULL];
      }

      $entries = $storage->loadMultiple($ids);
      $prevHash = 'genesis';
      $checked = 0;

      foreach ($entries as $entry) {
        $actor = $entry->get('actor_id')->target_id;
        $recordData = implode('|', [
          $entry->get('document_id')->target_id,
          $entry->get('action')->value,
          $actor ?? '0',
          $entry->get('actor_ip')->value ?? '',
          json_encode($entry->get('details')->first()?->getValue() ?? [], JSON_UNESCAPED_UNICODE),
          (string) $entry->get('created')->value,
        ]);

        $expectedHash = hash('sha256', $prevHash . $recordData);
        $storedHash = $entry->get('hash_chain')->value;

        if ($expectedHash !== $storedHash) {
          return [
            'valid' => FALSE,
            'entries_checked' => $checked,
            'error' => sprintf('Hash mismatch at entry #%d (id=%d).', $checked + 1, $entry->id()),
          ];
        }

        $prevHash = $storedHash;
        $checked++;
      }

      return ['valid' => TRUE, 'entries_checked' => $checked, 'error' => NULL];
    }
    catch (\Exception $e) {
      return [
        'valid' => FALSE,
        'entries_checked' => 0,
        'error' => 'Verification error: ' . $e->getMessage(),
      ];
    }
  }

  /**
   * Obtiene el ultimo hash de la cadena de un documento.
   *
   * @param int $documentId
   *   ID del documento.
   *
   * @return string
   *   Ultimo hash o 'genesis' si es la primera entrada.
   */
  protected function getLastHash(int $documentId): string {
    try {
      $storage = $this->entityTypeManager->getStorage('document_audit_log');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('document_id', $documentId)
        ->sort('id', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return 'genesis';
      }

      $last = $storage->load(reset($ids));
      return $last ? ($last->get('hash_chain')->value ?? 'genesis') : 'genesis';
    }
    catch (\Exception $e) {
      return 'genesis';
    }
  }

  /**
   * Obtiene la IP del cliente actual.
   */
  protected function getClientIp(): string {
    $request = $this->requestStack->getCurrentRequest();
    return $request ? $request->getClientIp() : '0.0.0.0';
  }

}
