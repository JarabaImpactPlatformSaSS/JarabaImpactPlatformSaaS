<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de sincronizacion de notificaciones LexNET.
 *
 * Estructura: Obtiene notificaciones de LexNET, las almacena como entidades.
 * Logica: fetchNotifications() consulta la API y crea/actualiza entidades.
 *   acknowledgeNotification() envia acuse de recibo al CGPJ.
 *   downloadAttachments() descarga documentos adjuntos.
 */
class LexnetSyncService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LexnetApiClient $apiClient,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene notificaciones nuevas de LexNET.
   */
  public function fetchNotifications(): array {
    try {
      $response = $this->apiClient->request('GET', 'notifications');
      if (isset($response['error'])) {
        return ['count' => 0, 'error' => $response['error']];
      }

      $storage = $this->entityTypeManager->getStorage('lexnet_notification');
      $count = 0;

      $notifications = $response['data'] ?? $response['notifications'] ?? [];
      foreach ($notifications as $item) {
        $externalId = $item['id'] ?? $item['external_id'] ?? NULL;
        if (!$externalId) {
          continue;
        }

        // Verificar si ya existe.
        $existing = $storage->loadByProperties(['external_id' => $externalId]);
        if (!empty($existing)) {
          continue;
        }

        $entity = $storage->create([
          'uid' => $this->currentUser->id(),
          'external_id' => $externalId,
          'notification_type' => $item['type'] ?? 'notificacion_electronica',
          'court' => $item['court'] ?? $item['organo_judicial'] ?? '',
          'procedure_number' => $item['procedure_number'] ?? $item['numero_autos'] ?? '',
          'subject' => $item['subject'] ?? $item['asunto'] ?? '',
          'received_at' => $item['received_at'] ?? $item['fecha_recepcion'] ?? date('Y-m-d\TH:i:s'),
          'deadline_days' => $item['deadline_days'] ?? $item['plazo_dias'] ?? 0,
          'attachments' => $item['attachments'] ?? [],
          'raw_data' => json_encode($item),
          'status' => 'pending',
        ]);
        $entity->save();
        $count++;
      }

      return ['count' => $count, 'total_fetched' => count($notifications)];
    }
    catch (\Exception $e) {
      $this->logger->error('Fetch notifications error: @msg', ['@msg' => $e->getMessage()]);
      return ['count' => 0, 'error' => $e->getMessage()];
    }
  }

  /**
   * Envia acuse de recibo de una notificacion al CGPJ.
   */
  public function acknowledgeNotification(int $notificationId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_notification');
      $notification = $storage->load($notificationId);
      if (!$notification) {
        return ['error' => 'Notification not found.'];
      }

      $externalId = $notification->get('external_id')->value;
      $response = $this->apiClient->request('POST', "notifications/{$externalId}/acknowledge");

      if (isset($response['error'])) {
        return $response;
      }

      $notification->set('acknowledged_at', date('Y-m-d\TH:i:s'));
      if ($notification->get('status')->value === 'pending') {
        $notification->set('status', 'read');
      }
      $notification->save();

      return [
        'id' => $notificationId,
        'external_id' => $externalId,
        'acknowledged_at' => $notification->get('acknowledged_at')->value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Acknowledge error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Descarga adjuntos de una notificacion.
   */
  public function downloadAttachments(int $notificationId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_notification');
      $notification = $storage->load($notificationId);
      if (!$notification) {
        return ['error' => 'Notification not found.'];
      }

      $externalId = $notification->get('external_id')->value;
      $response = $this->apiClient->request('GET', "notifications/{$externalId}/attachments");

      if (isset($response['error'])) {
        return $response;
      }

      // AUDIT-TODO-RESOLVED: Download and store LexNET attachments in private file system.
      $attachments = $response['data'] ?? $response['attachments'] ?? [];
      $fileSystem = \Drupal::service('file_system');
      $savedFiles = [];
      $destinationDir = 'private://lexnet/attachments/' . $externalId;

      // Ensure the destination directory exists.
      $fileSystem->prepareDirectory($destinationDir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

      foreach ($attachments as $attachment) {
        try {
          $attachmentId = $attachment['id'] ?? $attachment['attachment_id'] ?? NULL;
          $filename = $attachment['filename'] ?? $attachment['name'] ?? ('attachment_' . ($attachmentId ?? bin2hex(random_bytes(4))));

          // Sanitize filename to prevent path traversal.
          $filename = basename($filename);

          // Get the file content: either base64-encoded in the response, or via a download URL.
          $fileContent = NULL;

          if (!empty($attachment['content'])) {
            // Content is base64-encoded in the API response.
            $fileContent = base64_decode($attachment['content'], TRUE);
          }
          elseif (!empty($attachment['download_url'])) {
            // Fetch from the provided download URL via the LexNET API client.
            $downloadResponse = $this->apiClient->request('GET', $attachment['download_url']);
            if (!isset($downloadResponse['error']) && !empty($downloadResponse['content'])) {
              $fileContent = base64_decode($downloadResponse['content'], TRUE);
            }
          }
          elseif ($attachmentId) {
            // Fetch by attachment ID.
            $downloadResponse = $this->apiClient->request(
              'GET',
              "notifications/{$externalId}/attachments/{$attachmentId}/download"
            );
            if (!isset($downloadResponse['error']) && !empty($downloadResponse['content'])) {
              $fileContent = base64_decode($downloadResponse['content'], TRUE);
            }
          }

          if ($fileContent === NULL || $fileContent === FALSE) {
            $this->logger->warning('Could not retrieve content for attachment @name (notification @nid).', [
              '@name' => $filename,
              '@nid' => $notificationId,
            ]);
            continue;
          }

          // Save the file to the private file system.
          $destination = $destinationDir . '/' . $filename;
          $savedPath = $fileSystem->saveData($fileContent, $destination, \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME);

          if ($savedPath) {
            $savedFiles[] = [
              'filename' => $filename,
              'path' => $savedPath,
              'size' => strlen($fileContent),
              'attachment_id' => $attachmentId,
            ];

            $this->logger->info('LexNET attachment saved: @file for notification @nid.', [
              '@file' => $savedPath,
              '@nid' => $notificationId,
            ]);
          }
          else {
            $this->logger->error('Failed to save LexNET attachment @name to @dest.', [
              '@name' => $filename,
              '@dest' => $destination,
            ]);
          }
        }
        catch (\Exception $attachmentEx) {
          $this->logger->error('Error downloading LexNET attachment: @msg', [
            '@msg' => $attachmentEx->getMessage(),
          ]);
        }
      }

      // Update the notification entity to record attachment downloads.
      if (!empty($savedFiles)) {
        $notification->set('attachments_downloaded', TRUE);
        $notification->save();
      }

      return [
        'id' => $notificationId,
        'attachments_count' => count($attachments),
        'downloaded_count' => count($savedFiles),
        'files' => $savedFiles,
        'status' => count($savedFiles) > 0 ? 'downloaded' : 'no_content',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Download attachments error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Lista notificaciones con filtros.
   */
  public function listNotifications(array $filters = [], int $limit = 25, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_notification');
      $query = $storage->getQuery()->accessCheck(TRUE);

      if (!empty($filters['status'])) {
        $query->condition('status', $filters['status']);
      }
      if (!empty($filters['case_id'])) {
        $query->condition('case_id', $filters['case_id']);
      }

      $total = (clone $query)->count()->execute();
      $ids = $query->sort('received_at', 'DESC')->range($offset, $limit)->execute();

      return [
        'items' => array_map(fn($n) => $this->serializeNotification($n), $storage->loadMultiple($ids)),
        'total' => (int) $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('List notifications error: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Serializa una notificacion.
   */
  public function serializeNotification($notification): array {
    return [
      'id' => (int) $notification->id(),
      'uuid' => $notification->uuid(),
      'external_id' => $notification->get('external_id')->value ?? '',
      'notification_type' => $notification->get('notification_type')->value ?? '',
      'court' => $notification->get('court')->value ?? '',
      'procedure_number' => $notification->get('procedure_number')->value ?? '',
      'subject' => $notification->get('subject')->value ?? '',
      'received_at' => $notification->get('received_at')->value ?? '',
      'acknowledged_at' => $notification->get('acknowledged_at')->value ?? NULL,
      'deadline_days' => (int) ($notification->get('deadline_days')->value ?? 0),
      'computed_deadline' => $notification->get('computed_deadline')->value ?? NULL,
      'status' => $notification->get('status')->value ?? 'pending',
      'case_id' => $notification->get('case_id')->target_id ? (int) $notification->get('case_id')->target_id : NULL,
      'created' => $notification->get('created')->value ?? '',
    ];
  }

}
