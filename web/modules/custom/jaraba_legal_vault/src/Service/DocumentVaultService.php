<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal de la boveda documental cifrada.
 *
 * ESTRUCTURA:
 * Orquesta el CRUD de documentos seguros: almacenamiento cifrado,
 * recuperacion con descifrado, versionado y soft-delete.
 *
 * LOGICA:
 * - store(): Cifra contenido con AES-256-GCM, almacena en disco,
 *   envuelve DEK con KEK, registra en DB y audit log.
 * - retrieve(): Desenvuelve DEK, lee archivo cifrado, descifra, retorna.
 * - createVersion(): Crea nueva version vinculada a la anterior.
 * - softDelete(): Cambia status a 'deleted', registra en audit log.
 * - getDocumentsByCase(): Documentos vinculados a un expediente.
 *
 * RELACIONES:
 * - DocumentVaultService -> VaultEncryptionService: cifrado/descifrado.
 * - DocumentVaultService -> VaultAuditLogService: registro de acciones.
 * - DocumentVaultService -> FileSystem: escritura/lectura de archivos.
 * - DocumentVaultService <- VaultApiController: invocado desde API.
 */
class DocumentVaultService {

  /**
   * Directorio base para almacenamiento cifrado.
   */
  private const VAULT_DIR = 'private://vault';

  /**
   * Construye una nueva instancia de DocumentVaultService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected VaultEncryptionService $encryption,
    protected VaultAuditLogService $auditLog,
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Almacena un documento cifrado en la boveda.
   *
   * @param string $content
   *   Contenido del archivo en claro (binario).
   * @param string $title
   *   Titulo del documento.
   * @param string $originalFilename
   *   Nombre original del archivo.
   * @param string $mimeType
   *   Tipo MIME.
   * @param int|null $caseId
   *   ID del expediente vinculado (opcional).
   * @param int|null $categoryTid
   *   ID del termino de categoria (opcional).
   *
   * @return array{success: bool, document: object|null, error: string|null}
   *   Resultado de la operacion.
   */
  public function store(
    string $content,
    string $title,
    string $originalFilename,
    string $mimeType,
    ?int $caseId = NULL,
    ?int $categoryTid = NULL,
  ): array {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');

      // 1. Hash del contenido original.
      $contentHash = $this->encryption->hashContent($content);

      // 2. Generar DEK y cifrar contenido.
      $dek = $this->encryption->generateDek();
      $encrypted = $this->encryption->encrypt($content, $dek);

      // 3. Envolver DEK con KEK.
      $wrappedDek = $this->encryption->wrapDek($dek);

      // Limpiar DEK de memoria.
      sodium_memzero($dek);

      // 4. Almacenar archivo cifrado en disco.
      $storagePath = $this->generateStoragePath($originalFilename);
      $this->fileSystem->prepareDirectory(dirname($storagePath), FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $this->fileSystem->saveData($encrypted['ciphertext'], $storagePath, FileSystemInterface::EXISTS_REPLACE);

      // 5. Crear entidad SecureDocument.
      $document = $storage->create([
        'owner_id' => $this->currentUser->id(),
        'case_id' => $caseId,
        'title' => $title,
        'original_filename' => $originalFilename,
        'mime_type' => $mimeType,
        'file_size' => strlen($content),
        'storage_path' => $storagePath,
        'content_hash' => $contentHash,
        'encrypted_dek' => $wrappedDek,
        'encryption_iv' => $encrypted['iv'],
        'encryption_tag' => $encrypted['tag'],
        'category_tid' => $categoryTid,
        'version' => 1,
        'status' => 'active',
        'uid' => $this->currentUser->id(),
      ]);
      $document->save();

      // 6. Registrar en audit log.
      $this->auditLog->log((int) $document->id(), 'created', [
        'filename' => $originalFilename,
        'size' => strlen($content),
        'content_hash' => $contentHash,
      ]);

      return ['success' => TRUE, 'document' => $document, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error almacenando documento "@title": @msg', [
        '@title' => $title,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'document' => NULL, 'error' => $e->getMessage()];
    }
  }

  /**
   * Recupera y descifra un documento de la boveda.
   *
   * @param int $documentId
   *   ID del documento seguro.
   *
   * @return array{success: bool, content: string|null, document: object|null, error: string|null}
   *   Resultado con contenido descifrado.
   */
  public function retrieve(int $documentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');
      $document = $storage->load($documentId);

      if (!$document) {
        return ['success' => FALSE, 'content' => NULL, 'document' => NULL, 'error' => 'Document not found.'];
      }

      if ($document->get('status')->value === 'deleted') {
        return ['success' => FALSE, 'content' => NULL, 'document' => NULL, 'error' => 'Document has been deleted.'];
      }

      // 1. Leer archivo cifrado del disco.
      $storagePath = $document->get('storage_path')->value;
      $ciphertext = file_get_contents($storagePath);

      if ($ciphertext === FALSE) {
        return ['success' => FALSE, 'content' => NULL, 'document' => $document, 'error' => 'Storage file not found.'];
      }

      // 2. Desenvolver DEK.
      $dek = $this->encryption->unwrapDek($document->get('encrypted_dek')->value);

      // 3. Descifrar contenido.
      $plaintext = $this->encryption->decrypt(
        $ciphertext,
        $dek,
        $document->get('encryption_iv')->value,
        $document->get('encryption_tag')->value
      );

      // Limpiar DEK de memoria.
      sodium_memzero($dek);

      // 4. Verificar integridad.
      $expectedHash = $document->get('content_hash')->value;
      $actualHash = $this->encryption->hashContent($plaintext);
      if ($expectedHash !== $actualHash) {
        $this->logger->error('DocumentVault: Hash mismatch for doc @id. Expected @exp, got @act.', [
          '@id' => $documentId,
          '@exp' => $expectedHash,
          '@act' => $actualHash,
        ]);
        return ['success' => FALSE, 'content' => NULL, 'document' => $document, 'error' => 'Integrity check failed.'];
      }

      // 5. Registrar acceso.
      $this->auditLog->log($documentId, 'viewed');

      return ['success' => TRUE, 'content' => $plaintext, 'document' => $document, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error recuperando doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'content' => NULL, 'document' => NULL, 'error' => $e->getMessage()];
    }
  }

  /**
   * Crea una nueva version de un documento.
   *
   * @param int $parentDocumentId
   *   ID del documento padre.
   * @param string $content
   *   Nuevo contenido en claro.
   * @param string $originalFilename
   *   Nombre del archivo.
   * @param string $mimeType
   *   Tipo MIME.
   *
   * @return array{success: bool, document: object|null, error: string|null}
   *   Resultado.
   */
  public function createVersion(int $parentDocumentId, string $content, string $originalFilename, string $mimeType): array {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');
      $parent = $storage->load($parentDocumentId);

      if (!$parent) {
        return ['success' => FALSE, 'document' => NULL, 'error' => 'Parent document not found.'];
      }

      $result = $this->store(
        $content,
        $parent->get('title')->value,
        $originalFilename,
        $mimeType,
        $parent->get('case_id')->target_id ? (int) $parent->get('case_id')->target_id : NULL,
        $parent->get('category_tid')->target_id ? (int) $parent->get('category_tid')->target_id : NULL,
      );

      if ($result['success'] && $result['document']) {
        $newVersion = (int) ($parent->get('version')->value ?? 1) + 1;
        $result['document']->set('version', $newVersion);
        $result['document']->set('parent_version_id', $parentDocumentId);
        $result['document']->save();
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error creando version para doc @id: @msg', [
        '@id' => $parentDocumentId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'document' => NULL, 'error' => $e->getMessage()];
    }
  }

  /**
   * Soft-delete de un documento.
   *
   * @param int $documentId
   *   ID del documento.
   *
   * @return array{success: bool, error: string|null}
   *   Resultado.
   */
  public function softDelete(int $documentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');
      $document = $storage->load($documentId);

      if (!$document) {
        return ['success' => FALSE, 'error' => 'Document not found.'];
      }

      $document->set('status', 'deleted');
      $document->save();

      $this->auditLog->log($documentId, 'deleted');

      return ['success' => TRUE, 'error' => NULL];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error eliminando doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtiene documentos por UUID.
   *
   * @param string $uuid
   *   UUID del documento.
   *
   * @return object|null
   *   Entidad SecureDocument o NULL.
   */
  public function getDocumentByUuid(string $uuid): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');
      $entities = $storage->loadByProperties(['uuid' => $uuid]);
      return !empty($entities) ? reset($entities) : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error cargando doc UUID @uuid: @msg', [
        '@uuid' => $uuid,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Lista documentos del usuario actual con filtros.
   *
   * @param array $filters
   *   Filtros: status, case_id.
   * @param int $limit
   *   Maximo de resultados.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array{documents: array, total: int}
   *   Resultado.
   */
  public function listDocuments(array $filters = [], int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'deleted', '<>')
        ->sort('created', 'DESC')
        ->range($offset, $limit);

      $countQuery = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'deleted', '<>')
        ->count();

      if (!empty($filters['status'])) {
        $query->condition('status', $filters['status']);
        $countQuery->condition('status', $filters['status']);
      }
      if (!empty($filters['case_id'])) {
        $query->condition('case_id', $filters['case_id']);
        $countQuery->condition('case_id', $filters['case_id']);
      }

      $ids = $query->execute();
      $total = (int) $countQuery->execute();

      return [
        'documents' => !empty($ids) ? $storage->loadMultiple($ids) : [],
        'total' => $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error listando documentos: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['documents' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene las versiones de un documento.
   *
   * @param int $documentId
   *   ID del documento.
   *
   * @return array
   *   Array de entidades SecureDocument ordenadas por version.
   */
  public function getVersions(int $documentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('secure_document');
      $document = $storage->load($documentId);

      if (!$document) {
        return [];
      }

      // Encontrar la raiz de la cadena de versiones.
      $rootId = $documentId;
      $current = $document;
      while ($current->get('parent_version_id')->target_id) {
        $rootId = (int) $current->get('parent_version_id')->target_id;
        $current = $storage->load($rootId);
        if (!$current) {
          break;
        }
      }

      // Obtener todas las versiones desde la raiz.
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'deleted', '<>')
        ->sort('version', 'ASC')
        ->execute();

      // Filtrar: solo las que pertenecen a la misma cadena.
      $versions = [];
      $chainIds = [$rootId];
      $all = $storage->loadMultiple($ids);
      foreach ($all as $doc) {
        if ((int) $doc->id() === $rootId || (int) ($doc->get('parent_version_id')->target_id ?? 0) === $rootId || in_array((int) ($doc->get('parent_version_id')->target_id ?? 0), $chainIds, TRUE)) {
          $versions[] = $doc;
          $chainIds[] = (int) $doc->id();
        }
      }

      return $versions;
    }
    catch (\Exception $e) {
      $this->logger->error('DocumentVault: Error obteniendo versiones del doc @id: @msg', [
        '@id' => $documentId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Genera ruta de almacenamiento unica para un archivo cifrado.
   */
  protected function generateStoragePath(string $originalFilename): string {
    $date = date('Y/m');
    $hash = bin2hex(random_bytes(16));
    $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
    return self::VAULT_DIR . "/{$date}/{$hash}.enc" . ($ext ? ".{$ext}" : '');
  }

}
