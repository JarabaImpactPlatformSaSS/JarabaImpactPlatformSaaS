<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de control de acceso granular y comparticion de documentos.
 *
 * ESTRUCTURA:
 * Gestiona la comparticion de documentos mediante tokens unicos,
 * re-cifrado de DEK para destinatarios, limites de descarga,
 * expiracion y revocacion de accesos.
 *
 * LOGICA:
 * - shareDocument(): Crea grant de acceso con token unico.
 * - revokeAccess(): Revoca un acceso especifico.
 * - revokeAll(): Revoca todos los accesos de un documento (RGPD).
 * - validateToken(): Valida token y verifica limites/expiracion.
 * - getSharedWithMe(): Documentos compartidos con el usuario actual.
 *
 * RELACIONES:
 * - DocumentAccessService -> VaultEncryptionService: tokens y re-cifrado.
 * - DocumentAccessService -> VaultAuditLogService: registra comparticiones.
 * - DocumentAccessService <- VaultApiController: invocado desde API.
 */
class DocumentAccessService {

  /**
   * Construye una nueva instancia de DocumentAccessService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected VaultEncryptionService $encryption,
    protected VaultAuditLogService $auditLog,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Comparte un documento con un destinatario.
   *
   * @param int $documentId
   *   ID del documento.
   * @param int|null $granteeId
   *   ID del usuario destinatario (NULL si es por email).
   * @param string|null $granteeEmail
   *   Email del destinatario (acceso externo).
   * @param array $permissions
   *   Permisos: ['view', 'download', 'sign'].
   * @param int|null $maxDownloads
   *   Limite de descargas (NULL = ilimitado).
   * @param string|null $expiresAt
   *   Fecha de expiracion ISO 8601 (NULL = sin expiracion).
   * @param bool $requiresAuth
   *   Si requiere autenticacion para acceder.
   *
   * @return array{success: bool, access: object|null, token: string|null, error: string|null}
   *   Resultado con token de acceso.
   */
  public function shareDocument(
    int $documentId,
    ?int $granteeId = NULL,
    ?string $granteeEmail = NULL,
    array $permissions = ['view', 'download'],
    ?int $maxDownloads = NULL,
    ?string $expiresAt = NULL,
    bool $requiresAuth = TRUE,
  ): array {
    try {
      $docStorage = $this->entityTypeManager->getStorage('secure_document');
      $document = $docStorage->load($documentId);

      if (!$document) {
        return ['success' => FALSE, 'access' => NULL, 'token' => NULL, 'error' => 'Document not found.'];
      }

      // Generar token de acceso y re-cifrar DEK.
      $token = $this->encryption->generateAccessToken();
      $reEncryptedDek = $this->encryption->reEncryptDekForRecipient(
        $document->get('encrypted_dek')->value
      );

      $accessStorage = $this->entityTypeManager->getStorage('document_access');
      $access = $accessStorage->create([
        'document_id' => $documentId,
        'grantee_id' => $granteeId,
        'grantee_email' => $granteeEmail ?? '',
        'access_token' => $token,
        'encrypted_dek' => $reEncryptedDek,
        'permissions' => $permissions,
        'max_downloads' => $maxDownloads,
        'download_count' => 0,
        'expires_at' => $expiresAt,
        'requires_auth' => $requiresAuth,
        'is_revoked' => FALSE,
        'granted_by' => $this->currentUser->id(),
      ]);
      $access->save();

      // Registrar en audit log.
      $this->auditLog->log($documentId, 'shared', [
        'grantee_id' => $granteeId,
        'grantee_email' => $granteeEmail,
        'permissions' => $permissions,
        'max_downloads' => $maxDownloads,
        'expires_at' => $expiresAt,
      ]);

      return ['success' => TRUE, 'access' => $access, 'token' => $token, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error compartiendo doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'access' => NULL, 'token' => NULL, 'error' => $e->getMessage()];
    }
  }

  /**
   * Revoca un acceso especifico.
   *
   * @param int $accessId
   *   ID del registro de acceso.
   *
   * @return array{success: bool, error: string|null}
   *   Resultado.
   */
  public function revokeAccess(int $accessId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_access');
      $access = $storage->load($accessId);

      if (!$access) {
        return ['success' => FALSE, 'error' => 'Access record not found.'];
      }

      $access->set('is_revoked', TRUE);
      $access->save();

      $documentId = (int) $access->get('document_id')->target_id;
      $this->auditLog->log($documentId, 'revoked', [
        'access_id' => $accessId,
        'grantee_email' => $access->get('grantee_email')->value,
      ]);

      return ['success' => TRUE, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error revocando acceso @id: @msg', [
        '@id' => $accessId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Revoca todos los accesos de un documento (derecho de oposicion RGPD).
   *
   * @param int $documentId
   *   ID del documento.
   *
   * @return array{success: bool, revoked_count: int, error: string|null}
   *   Resultado.
   */
  public function revokeAll(int $documentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_access');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('document_id', $documentId)
        ->condition('is_revoked', FALSE)
        ->execute();

      $count = 0;
      foreach ($storage->loadMultiple($ids) as $access) {
        $access->set('is_revoked', TRUE);
        $access->save();
        $count++;
      }

      $this->auditLog->log($documentId, 'revoked', [
        'bulk_revoke' => TRUE,
        'revoked_count' => $count,
      ]);

      return ['success' => TRUE, 'revoked_count' => $count, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error revocando todos los accesos del doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'revoked_count' => 0, 'error' => $e->getMessage()];
    }
  }

  /**
   * Valida un token de acceso y devuelve el grant.
   *
   * @param string $token
   *   Token de acceso.
   *
   * @return array{valid: bool, access: object|null, document: object|null, error: string|null}
   *   Resultado de la validacion.
   */
  public function validateToken(string $token): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_access');
      $entities = $storage->loadByProperties(['access_token' => $token]);
      $access = !empty($entities) ? reset($entities) : NULL;

      if (!$access) {
        return ['valid' => FALSE, 'access' => NULL, 'document' => NULL, 'error' => 'Invalid token.'];
      }

      // Verificar revocacion.
      if ($access->get('is_revoked')->value) {
        return ['valid' => FALSE, 'access' => $access, 'document' => NULL, 'error' => 'Access has been revoked.'];
      }

      // Verificar expiracion.
      $expiresAt = $access->get('expires_at')->value;
      if ($expiresAt && strtotime($expiresAt) < time()) {
        return ['valid' => FALSE, 'access' => $access, 'document' => NULL, 'error' => 'Access has expired.'];
      }

      // Verificar limite de descargas.
      $maxDownloads = $access->get('max_downloads')->value;
      $downloadCount = (int) $access->get('download_count')->value;
      if ($maxDownloads !== NULL && $maxDownloads > 0 && $downloadCount >= $maxDownloads) {
        return ['valid' => FALSE, 'access' => $access, 'document' => NULL, 'error' => 'Download limit reached.'];
      }

      // Cargar documento asociado.
      $docStorage = $this->entityTypeManager->getStorage('secure_document');
      $document = $docStorage->load($access->get('document_id')->target_id);

      if (!$document || $document->get('status')->value === 'deleted') {
        return ['valid' => FALSE, 'access' => $access, 'document' => NULL, 'error' => 'Document not available.'];
      }

      return ['valid' => TRUE, 'access' => $access, 'document' => $document, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error validando token: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['valid' => FALSE, 'access' => NULL, 'document' => NULL, 'error' => $e->getMessage()];
    }
  }

  /**
   * Incrementa el contador de descargas.
   *
   * @param int $accessId
   *   ID del acceso.
   */
  public function incrementDownloadCount(int $accessId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('document_access');
      $access = $storage->load($accessId);
      if ($access) {
        $current = (int) $access->get('download_count')->value;
        $access->set('download_count', $current + 1);
        $access->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error incrementando downloads para acceso @id: @msg', [
        '@id' => $accessId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Lista los accesos activos de un documento.
   *
   * @param int $documentId
   *   ID del documento.
   *
   * @return array
   *   Array de entidades DocumentAccess.
   */
  public function listAccessGrants(int $documentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_access');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('document_id', $documentId)
        ->condition('is_revoked', FALSE)
        ->sort('created', 'DESC')
        ->execute();

      return !empty($ids) ? array_values($storage->loadMultiple($ids)) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error listando accesos del doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Documentos compartidos con el usuario actual.
   *
   * @param int $limit
   *   Maximo de resultados.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array{documents: array, total: int}
   *   Resultado.
   */
  public function getSharedWithMe(int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('document_access');
      $userId = (int) $this->currentUser->id();

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('grantee_id', $userId)
        ->condition('is_revoked', FALSE)
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $countIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('grantee_id', $userId)
        ->condition('is_revoked', FALSE)
        ->count()
        ->execute();

      $grants = !empty($ids) ? $storage->loadMultiple($ids) : [];
      $documents = [];
      $docStorage = $this->entityTypeManager->getStorage('secure_document');

      foreach ($grants as $grant) {
        $doc = $docStorage->load($grant->get('document_id')->target_id);
        if ($doc && $doc->get('status')->value !== 'deleted') {
          $documents[] = $doc;
        }
      }

      return ['documents' => $documents, 'total' => (int) $countIds];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentAccess: Error obteniendo compartidos: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['documents' => [], 'total' => 0];
    }
  }

}
