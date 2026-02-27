<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Drupal\jaraba_support\Entity\TicketAttachment;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Ticket attachment management service.
 *
 * Handles file uploads to private storage, validates file types and sizes
 * against plan-based limits, and manages storage paths following the
 * convention: private://support_attachments/{tenant_id}/{ticket_id}/...
 */
final class AttachmentService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
    protected ?PlanResolverService $planResolver,
  ) {}

  /**
   * Uploads a file attachment to a ticket.
   *
   * Validates the file, stores it in the private filesystem,
   * and creates a TicketAttachment entity. Triggers virus scan.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket to attach the file to.
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   The uploaded file.
   * @param int $uploaderUid
   *   The UID of the user uploading the file.
   * @param int|null $messageId
   *   Optional message ID this attachment belongs to.
   *
   * @return \Drupal\jaraba_support\Entity\TicketAttachment|null
   *   The created attachment entity, or NULL on failure.
   */
  public function uploadAttachment(SupportTicketInterface $ticket, UploadedFile $file, int $uploaderUid, ?int $messageId = NULL): ?TicketAttachment {
    try {
      // Validate the file against allowed types, size limits, and security rules.
      $tenantId = $ticket->get('tenant_id')->target_id;
      $errors = $this->validateFile($file, $tenantId ? (int) $tenantId : NULL);
      if (!empty($errors)) {
        $this->logger->warning('Attachment validation failed for ticket @id: @errors', [
          '@id' => $ticket->id() ?? 'new',
          '@errors' => implode('; ', $errors),
        ]);
        return NULL;
      }

      // Prepare storage directory.
      $storagePath = $this->getStoragePath($ticket);
      $directoryReady = $this->fileSystem->prepareDirectory(
        $storagePath,
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
      );
      if (!$directoryReady) {
        $this->logger->error('Failed to prepare directory @path for ticket @id.', [
          '@path' => $storagePath,
          '@id' => $ticket->id() ?? 'new',
        ]);
        return NULL;
      }

      // Generate a safe filename: timestamp + random hex + sanitized original.
      $originalName = $file->getClientOriginalName();
      $safeName = time() . '_' . bin2hex(random_bytes(4)) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
      $destination = $storagePath . '/' . $safeName;

      // Save file data to private storage.
      $fileContents = file_get_contents($file->getRealPath());
      if ($fileContents === FALSE) {
        $this->logger->error('Failed to read uploaded file @name.', [
          '@name' => $originalName,
        ]);
        return NULL;
      }

      $savedPath = $this->fileSystem->saveData($fileContents, $destination, FileSystemInterface::EXISTS_RENAME);
      if ($savedPath === FALSE) {
        $this->logger->error('Failed to save file to @dest for ticket @id.', [
          '@dest' => $destination,
          '@id' => $ticket->id() ?? 'new',
        ]);
        return NULL;
      }

      // Create a Drupal managed file entity.
      /** @var \Drupal\file\FileInterface $fileEntity */
      $fileEntity = $this->entityTypeManager->getStorage('file')->create([
        'uri' => $savedPath,
        'filename' => $originalName,
        'filemime' => $file->getClientMimeType(),
        'filesize' => $file->getSize(),
        'status' => 1,
        'uid' => $uploaderUid,
      ]);
      $fileEntity->save();

      // Create TicketAttachment entity.
      $values = [
        'ticket_id' => $ticket->id(),
        'uploaded_by' => $uploaderUid,
        'filename' => $originalName,
        'storage_path' => $savedPath,
        'file_size' => $file->getSize(),
        'mime_type' => $file->getClientMimeType(),
        'file_id' => $fileEntity->id(),
        'scan_status' => 'pending',
      ];
      if ($messageId !== NULL) {
        $values['message_id'] = $messageId;
      }

      /** @var \Drupal\jaraba_support\Entity\TicketAttachment $attachment */
      $attachment = $this->entityTypeManager
        ->getStorage('ticket_attachment')
        ->create($values);
      $attachment->save();

      $this->logger->info('Attachment @aid created for ticket @tid: @name (@size bytes).', [
        '@aid' => $attachment->id(),
        '@tid' => $ticket->id(),
        '@name' => $originalName,
        '@size' => $file->getSize(),
      ]);

      return $attachment;
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception uploading attachment for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Validates a file against allowed types and size limits.
   *
   * Checks MIME type against allowed list, file size against plan-based
   * limits, and performs basic security checks (double extensions, etc.).
   *
   * @param \Symfony\Component\HttpFoundation\File\UploadedFile $file
   *   The file to validate.
   * @param int|null $tenantId
   *   Optional tenant ID for plan-based limit resolution.
   *
   * @return array
   *   Array of validation error messages. Empty if valid.
   */
  public function validateFile(UploadedFile $file, ?int $tenantId = NULL): array {
    $errors = [];

    $allowedMimeTypes = [
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/webp',
      'application/pdf',
      'text/plain',
      'text/csv',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/zip',
      'video/mp4',
    ];

    $allowedExtensions = [
      'jpg', 'jpeg', 'png', 'gif', 'webp',
      'pdf', 'txt', 'csv',
      'doc', 'docx', 'xls', 'xlsx',
      'zip', 'mp4',
    ];

    $dangerousExtensions = [
      'php', 'phtml', 'phar', 'sh', 'exe', 'bat', 'cmd', 'js', 'py',
      'pl', 'cgi', 'asp', 'aspx', 'jsp', 'war',
    ];

    // Determine max file size (default 10 MB).
    $maxSize = 10 * 1024 * 1024;
    if ($this->planResolver !== NULL && $tenantId !== NULL) {
      try {
        $planLimit = $this->planResolver->checkLimit('support', 'default', 'max_attachment_size', $maxSize);
        if ($planLimit > 0) {
          $maxSize = $planLimit;
        }
      }
      catch (\Throwable) {
        // Fall back to default max size.
      }
    }

    // Check file size.
    $fileSize = $file->getSize();
    if ($fileSize <= 0) {
      $errors[] = 'The uploaded file is empty.';
    }
    elseif ($fileSize > $maxSize) {
      $errors[] = sprintf(
        'File size (%s bytes) exceeds the maximum allowed (%s bytes).',
        number_format($fileSize),
        number_format($maxSize),
      );
    }

    // Check MIME type.
    $mimeType = $file->getClientMimeType();
    if (!in_array($mimeType, $allowedMimeTypes, TRUE)) {
      $errors[] = sprintf('File type "%s" is not allowed.', $mimeType);
    }

    // Check extension.
    $originalName = $file->getClientOriginalName();
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, TRUE)) {
      $errors[] = sprintf('File extension "%s" is not allowed.', $extension);
    }

    // Security: check for dangerous extensions anywhere in the filename.
    // Catches double extensions like "malware.php.jpg" or "script.phtml.png".
    $lowerName = strtolower($originalName);
    $nameParts = explode('.', $lowerName);
    // Remove the last part (already validated above) and the first part (the base name).
    if (count($nameParts) > 2) {
      $innerParts = array_slice($nameParts, 1, -1);
      foreach ($innerParts as $part) {
        if (in_array($part, $dangerousExtensions, TRUE)) {
          $errors[] = sprintf(
            'Filename contains a dangerous extension ".%s". Double extensions are not allowed.',
            $part,
          );
          break;
        }
      }
    }

    return $errors;
  }

  /**
   * Gets the private storage path for a ticket's attachments.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket.
   *
   * @return string
   *   The private file path (e.g., private://support_attachments/{tenant}/{ticket}/).
   */
  public function getStoragePath(SupportTicketInterface $ticket): string {
    $ticketId = $ticket->id() ?? '0';
    $tenantId = $ticket->get('tenant_id')->target_id ?? '0';
    $path = "private://support_attachments/{$tenantId}/{$ticketId}";

    $this->logger->info('AttachmentService::getStoragePath() returning @path.', [
      '@path' => $path,
    ]);

    return $path;
  }

}
