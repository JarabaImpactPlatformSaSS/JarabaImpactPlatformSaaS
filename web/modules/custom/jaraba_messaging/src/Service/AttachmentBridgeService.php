<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Psr\Log\LoggerInterface;

/**
 * Puente hacia el Buzón de Confianza (doc 88) para adjuntos cifrados.
 *
 * PROPÓSITO:
 * Enruta archivos adjuntos al vault del Buzón de Confianza con
 * cifrado E2E. Degrada gracefully si el módulo no está instalado.
 *
 * DI OPCIONAL: @?jaraba_buzon_confianza.vault
 */
class AttachmentBridgeService {

  /**
   * Optional vault service from jaraba_buzon_confianza (doc 88).
   *
   * Injected via setter (optional DI with @?) to allow graceful
   * degradation when the Buzón de Confianza module is not installed.
   */
  protected ?object $vaultService = NULL;

  public function __construct(
    protected MessageAuditServiceInterface $auditService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sets the vault service (optional DI via @? in services.yml).
   *
   * @param object|null $vaultService
   *   The DocumentVaultService from jaraba_buzon_confianza, or NULL.
   */
  public function setVaultService(?object $vaultService): void {
    $this->vaultService = $vaultService;
  }

  /**
   * Stores an attachment securely via the vault.
   *
   * @param int $conversationId
   *   The conversation ID.
   * @param int $tenantId
   *   The tenant ID.
   * @param int $uploaderId
   *   The user ID of the uploader.
   * @param string $filename
   *   Original filename.
   * @param string $content
   *   File content (binary).
   * @param string $mimeType
   *   MIME type of the file.
   *
   * @return array
   *   Attachment metadata: ['id' => string, 'filename' => string, 'size' => int, 'mime_type' => string].
   */
  public function store(int $conversationId, int $tenantId, int $uploaderId, string $filename, string $content, string $mimeType): array {
    // Use injected vault service if available (optional DI via @?).
    if ($this->vaultService !== NULL) {
      try {
        $result = $this->vaultService->store($content, $filename, $mimeType, [
          'context' => 'messaging',
          'conversation_id' => $conversationId,
          'tenant_id' => $tenantId,
        ]);

        $this->auditService->log(
          $conversationId,
          $tenantId,
          'attachment.uploaded',
          NULL,
          ['filename' => $filename, 'vault_id' => $result['id'] ?? NULL],
        );

        return $result;
      }
      catch (\Throwable $e) {
        $this->logger->error('Vault storage failed, falling back to local: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Fallback: store in managed file system.
    $directory = 'private://messaging/attachments/' . $tenantId;
    \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

    $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $destination = $directory . '/' . time() . '_' . $safeFilename;
    $uri = \Drupal::service('file_system')->saveData($content, $destination);

    $attachmentId = hash('sha256', $uri . time());

    $this->auditService->log(
      $conversationId,
      $tenantId,
      'attachment.uploaded',
      NULL,
      ['filename' => $filename, 'local_uri' => $uri],
    );

    return [
      'id' => $attachmentId,
      'filename' => $filename,
      'size' => strlen($content),
      'mime_type' => $mimeType,
    ];
  }

  /**
   * Retrieves an attachment by ID.
   *
   * @param string $attachmentId
   *   The attachment identifier.
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array|null
   *   Attachment data or NULL if not found.
   */
  public function retrieve(string $attachmentId, int $tenantId): ?array {
    if ($this->vaultService !== NULL) {
      try {
        return $this->vaultService->retrieve($attachmentId);
      }
      catch (\Throwable $e) {
        $this->logger->warning('Vault retrieval failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return NULL;
  }

}
