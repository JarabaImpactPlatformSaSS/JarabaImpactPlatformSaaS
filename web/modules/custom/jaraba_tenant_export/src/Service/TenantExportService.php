<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\ecosistema_jaraba_core\Service\AuditLogService;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Orquesta el ciclo completo de exportación de datos del tenant.
 *
 * Responsabilidades:
 * - Validar rate limits y crear records.
 * - Procesar exportaciones (colectar + empaquetar ZIP).
 * - Gestionar descargas via StreamedResponse.
 * - Limpieza de exportaciones expiradas.
 */
class TenantExportService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantDataCollectorService $dataCollector,
    protected RateLimiterService $rateLimiter,
    protected AuditLogService $auditLog,
    protected FileSystemInterface $fileSystem,
    protected ConfigFactoryInterface $configFactory,
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Solicita una nueva exportación.
   *
   * @param int $groupId
   *   ID del grupo tenant.
   * @param int $tenantEntityId
   *   ID de la entidad tenant.
   * @param int $userId
   *   ID del usuario solicitante.
   * @param string $type
   *   Tipo: full|partial|gdpr_portability.
   * @param array $sections
   *   Secciones a exportar.
   *
   * @return array
   *   {success, record_id, message} o {success: false, error, retry_after}.
   */
  public function requestExport(int $groupId, int $tenantEntityId, int $userId, string $type = 'full', array $sections = []): array {
    // Validar rate limit.
    $canRequest = $this->canRequestExport($groupId);
    if (!$canRequest['allowed']) {
      return [
        'success' => FALSE,
        'error' => 'rate_limited',
        'message' => (string) t('Límite de exportaciones alcanzado. Intenta de nuevo en @time.', [
          '@time' => $canRequest['retry_after_formatted'],
        ]),
        'retry_after' => $canRequest['retry_after'],
      ];
    }

    $config = $this->configFactory->get('jaraba_tenant_export.settings');
    $expirationHours = (int) ($config->get('export_expiration_hours') ?? 48);

    if (empty($sections)) {
      $sections = $config->get('default_sections') ?? ['core', 'analytics', 'knowledge', 'operational', 'files'];
    }

    // Generar token de descarga (UUID).
    /** @var \Drupal\Component\Uuid\UuidInterface $uuidService */
    $uuidService = \Drupal::service('uuid');
    $downloadToken = $uuidService->generate();

    // Crear TenantExportRecord.
    $storage = $this->entityTypeManager->getStorage('tenant_export_record');
    $record = $storage->create([
      'tenant_id' => $groupId,
      'tenant_entity_id' => $tenantEntityId,
      'requested_by' => $userId,
      'export_type' => $type,
      'status' => 'queued',
      'progress' => 0,
      'requested_sections' => json_encode($sections),
      'download_token' => $downloadToken,
      'expires_at' => time() + ($expirationHours * 3600),
    ]);
    $record->save();

    // Encolar procesamiento.
    $queue = $this->queueFactory->get('jaraba_tenant_export');
    $queue->createItem([
      'record_id' => (int) $record->id(),
      'group_id' => $groupId,
      'tenant_entity_id' => $tenantEntityId,
      'sections' => $sections,
      'attempt' => 0,
    ]);

    // Registrar en audit log.
    $this->auditLog->log('tenant_export.requested', [
      'tenant_id' => $groupId,
      'target_type' => 'tenant_export_record',
      'target_id' => (int) $record->id(),
      'severity' => 'info',
      'details' => [
        'type' => $type,
        'sections' => $sections,
        'requested_by' => $userId,
      ],
    ]);

    $this->logger->info('Export requested for tenant @tid, record @rid.', [
      '@tid' => $groupId,
      '@rid' => $record->id(),
    ]);

    return [
      'success' => TRUE,
      'record_id' => (int) $record->id(),
      'message' => (string) t('Exportación solicitada. Recibirás una notificación cuando esté lista.'),
    ];
  }

  /**
   * Procesa una exportación completa.
   *
   * @param int $recordId
   *   ID del TenantExportRecord.
   */
  public function processExport(int $recordId): void {
    $storage = $this->entityTypeManager->getStorage('tenant_export_record');
    $record = $storage->load($recordId);

    if (!$record) {
      $this->logger->error('Export record @id not found.', ['@id' => $recordId]);
      return;
    }

    $status = $record->get('status')->value;
    if (!in_array($status, ['queued', 'collecting'], TRUE)) {
      $this->logger->info('Skipping export @id with status @status.', [
        '@id' => $recordId,
        '@status' => $status,
      ]);
      return;
    }

    $groupId = (int) $record->get('tenant_id')->target_id;
    $tenantEntityId = (int) ($record->get('tenant_entity_id')->target_id ?? 0);
    $sections = json_decode($record->get('requested_sections')->value ?? '[]', TRUE) ?: [];

    // Actualizar a collecting.
    $record->set('status', 'collecting');
    $record->save();

    try {
      // Recopilar datos con callback de progreso.
      $sectionCounts = [];
      $data = $this->dataCollector->collectAll(
        $groupId,
        $tenantEntityId,
        $sections,
        function (int $percent, string $phase) use ($record) {
          $record->set('progress', min($percent, 90));
          $record->set('current_phase', $phase);
          $record->save();
        }
      );

      // Contar registros por sección.
      foreach ($data as $section => $sectionData) {
        $sectionCounts[$section] = $this->countRecords($sectionData);
      }

      // Empaquetar ZIP.
      $record->set('status', 'packaging');
      $record->set('progress', 90);
      $record->set('current_phase', 'packaging');
      $record->save();

      $zipResult = $this->buildZipPackage($record, $data);

      // Completar.
      $record->set('status', 'completed');
      $record->set('progress', 100);
      $record->set('current_phase', 'complete');
      $record->set('file_path', $zipResult['path']);
      $record->set('file_size', $zipResult['size']);
      $record->set('file_hash', $zipResult['hash']);
      $record->set('section_counts', json_encode($sectionCounts));
      $record->set('completed_at', time());
      $record->save();

      $this->auditLog->log('tenant_export.completed', [
        'tenant_id' => $groupId,
        'target_type' => 'tenant_export_record',
        'target_id' => $recordId,
        'severity' => 'info',
        'details' => [
          'file_size' => $zipResult['size'],
          'sections' => $sectionCounts,
        ],
      ]);

      $this->logger->info('Export @id completed. Size: @size bytes.', [
        '@id' => $recordId,
        '@size' => $zipResult['size'],
      ]);
    }
    catch (\Exception $e) {
      $record->set('status', 'failed');
      $record->set('error_message', $e->getMessage());
      $record->save();

      $this->logger->error('Export @id failed: @msg', [
        '@id' => $recordId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Construye el paquete ZIP de exportación.
   *
   * @return array
   *   {path, size, hash}.
   */
  public function buildZipPackage(object $record, array $data): array {
    $groupId = (int) $record->get('tenant_id')->target_id;
    $exportDir = "private://tenant_exports/{$groupId}";
    $this->fileSystem->prepareDirectory($exportDir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $tenantName = 'tenant_' . $groupId;
    $date = date('Ymd_His');
    $filename = "tenant_export_{$tenantName}_{$date}.zip";
    $zipPath = $exportDir . '/' . $filename;
    $realPath = $this->fileSystem->realpath($zipPath) ?: $this->fileSystem->realpath($exportDir) . '/' . $filename;

    $zip = new \ZipArchive();
    if ($zip->open($realPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
      throw new \RuntimeException("Cannot create ZIP at {$realPath}");
    }

    // Manifest.
    $manifest = [
      'platform' => 'Jaraba Impact Platform SaaS',
      'export_type' => $record->get('export_type')->value,
      'tenant_id' => $groupId,
      'generated_at' => date('c'),
      'record_id' => (int) $record->id(),
      'sections' => array_keys($data),
      'gdpr_article' => 'Art. 20 — Right to Data Portability',
    ];
    $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    // README.
    $readme = $this->generateReadme($manifest);
    $zip->addFromString('README.txt', $readme);

    // Datos por sección.
    $sectionDirs = [
      'core' => 'core',
      'analytics' => 'analytics',
      'knowledge' => 'knowledge',
      'operational' => 'operational',
      'vertical' => 'vertical',
      'files' => 'files',
    ];

    foreach ($data as $section => $sectionData) {
      $dir = $sectionDirs[$section] ?? $section;

      if ($section === 'analytics' && isset($sectionData['events'])) {
        // Events as CSV for large datasets.
        $csvContent = $this->arrayToCsv($sectionData['events']);
        $zip->addFromString("{$dir}/events.csv", $csvContent);
        unset($sectionData['events'], $sectionData['events_truncated']);
        // Remaining analytics data as JSON.
        if (!empty($sectionData)) {
          $zip->addFromString("{$dir}/dashboards.json", json_encode($sectionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
      }
      elseif ($section === 'files' && isset($sectionData['index'])) {
        // File index.
        $zip->addFromString("{$dir}/index.json", json_encode($sectionData['index'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        // Copy actual files.
        foreach ($sectionData['index'] as $fileInfo) {
          $uri = $fileInfo['uri'] ?? '';
          $realFilePath = $this->fileSystem->realpath($uri);
          if ($realFilePath && file_exists($realFilePath)) {
            $zip->addFile($realFilePath, "{$dir}/" . basename($fileInfo['filename']));
          }
        }
      }
      else {
        // Standard JSON export.
        foreach ($sectionData as $key => $items) {
          if (str_starts_with($key, '_')) {
            continue;
          }
          $jsonContent = json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
          $zip->addFromString("{$dir}/{$key}.json", $jsonContent);
        }
      }
    }

    $zip->close();

    $size = filesize($realPath);
    $hash = hash_file('sha256', $realPath);

    return [
      'path' => $zipPath,
      'size' => $size,
      'hash' => $hash,
    ];
  }

  /**
   * Devuelve una StreamedResponse para descargar un export por token.
   */
  public function getDownloadResponse(string $token): ?StreamedResponse {
    $storage = $this->entityTypeManager->getStorage('tenant_export_record');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('download_token', $token)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    $record = $storage->load(reset($ids));
    if (!$record || $record->get('status')->value !== 'completed') {
      return NULL;
    }

    // Check expiration.
    $expiresAt = (int) $record->get('expires_at')->value;
    if ($expiresAt && time() > $expiresAt) {
      return NULL;
    }

    $filePath = $record->get('file_path')->value;
    $realPath = $this->fileSystem->realpath($filePath);

    if (!$realPath || !file_exists($realPath)) {
      return NULL;
    }

    // Increment download count.
    $count = (int) ($record->get('download_count')->value ?? 0);
    $record->set('download_count', $count + 1);
    $record->save();

    $filename = basename($realPath);
    $fileSize = filesize($realPath);

    return new StreamedResponse(function () use ($realPath) {
      $handle = fopen($realPath, 'rb');
      while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
      }
      fclose($handle);
    }, 200, [
      'Content-Type' => 'application/zip',
      'Content-Disposition' => "attachment; filename=\"{$filename}\"",
      'Content-Length' => $fileSize,
      'Cache-Control' => 'no-cache, no-store, must-revalidate',
      'Pragma' => 'no-cache',
    ]);
  }

  /**
   * Limpia exportaciones expiradas.
   */
  public function cleanupExpiredExports(): int {
    $storage = $this->entityTypeManager->getStorage('tenant_export_record');
    $now = time();

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('expires_at', $now, '<')
      ->condition('status', 'completed')
      ->execute();

    $count = 0;
    if (!empty($ids)) {
      $records = $storage->loadMultiple($ids);
      foreach ($records as $record) {
        // Delete ZIP file.
        $filePath = $record->get('file_path')->value;
        if ($filePath) {
          $realPath = $this->fileSystem->realpath($filePath);
          if ($realPath && file_exists($realPath)) {
            @unlink($realPath);
          }
        }
        $record->set('status', 'expired');
        $record->set('file_path', NULL);
        $record->save();
        $count++;
      }
    }

    if ($count > 0) {
      $this->logger->info('Cleaned up @count expired exports.', ['@count' => $count]);
    }

    return $count;
  }

  /**
   * Verifica si un tenant puede solicitar una exportación.
   *
   * @return array
   *   {allowed, retry_after, retry_after_formatted}.
   */
  public function canRequestExport(int $groupId): array {
    $config = $this->configFactory->get('jaraba_tenant_export.settings');
    $dailyLimit = (int) ($config->get('rate_limit_per_day') ?? 3);

    $result = $this->rateLimiter->check("tenant:{$groupId}", 'export');

    if ($result['allowed']) {
      return [
        'allowed' => TRUE,
        'retry_after' => 0,
        'retry_after_formatted' => '',
      ];
    }

    $retryAfter = $result['retry_after'] ?? 0;
    $hours = (int) ($retryAfter / 3600);
    $minutes = (int) (($retryAfter % 3600) / 60);

    return [
      'allowed' => FALSE,
      'retry_after' => $retryAfter,
      'retry_after_formatted' => $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes} minutos",
    ];
  }

  /**
   * Obtiene el historial de exportaciones de un tenant.
   */
  public function getExportHistory(int $groupId, int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('tenant_export_record');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $groupId)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $records = $storage->loadMultiple($ids);
    $history = [];
    foreach ($records as $record) {
      $history[] = [
        'id' => (int) $record->id(),
        'type' => $record->get('export_type')->value,
        'status' => $record->get('status')->value,
        'status_label' => $record->getStatusLabel(),
        'progress' => $record->getProgress(),
        'current_phase' => $record->get('current_phase')->value ?? '',
        'file_size' => (int) ($record->get('file_size')->value ?? 0),
        'download_token' => $record->get('download_token')->value,
        'download_count' => (int) ($record->get('download_count')->value ?? 0),
        'is_downloadable' => $record->isDownloadable(),
        'created' => (int) $record->get('created')->value,
        'completed_at' => (int) ($record->get('completed_at')->value ?? 0),
        'expires_at' => (int) ($record->get('expires_at')->value ?? 0),
      ];
    }

    return $history;
  }

  /**
   * Genera el contenido README.txt para el ZIP.
   */
  protected function generateReadme(array $manifest): string {
    $lines = [
      'EXPORTACIÓN DE DATOS — JARABA IMPACT PLATFORM',
      str_repeat('=', 50),
      '',
      'Plataforma: ' . $manifest['platform'],
      'Tipo de exportación: ' . $manifest['export_type'],
      'Tenant ID: ' . $manifest['tenant_id'],
      'Generado: ' . $manifest['generated_at'],
      'Base legal: ' . $manifest['gdpr_article'],
      '',
      'ESTRUCTURA DEL PAQUETE:',
      str_repeat('-', 30),
      'manifest.json    — Metadatos de la exportación',
      'core/            — Datos principales (tenant, billing, whitelabel)',
      'analytics/       — Eventos y dashboards de analytics',
      'knowledge/       — Documentos y base de conocimiento',
      'operational/     — Auditoría, email, CRM',
      'vertical/        — Datos específicos del vertical',
      'files/           — Archivos originales del tenant',
      '',
      'FORMATO:',
      str_repeat('-', 30),
      'Los datos se proporcionan en JSON (legible) y CSV (tabular).',
      'Los archivos mantienen su nombre original.',
      '',
      'SOPORTE:',
      'Para cualquier duda sobre esta exportación, contacta con',
      'soporte@plataformadeecosistemas.com',
    ];

    return implode("\n", $lines);
  }

  /**
   * Convierte un array a contenido CSV.
   */
  protected function arrayToCsv(array $rows): string {
    if (empty($rows)) {
      return '';
    }

    $output = fopen('php://temp', 'r+');
    $first = reset($rows);
    if (is_array($first)) {
      fputcsv($output, array_keys($first));
      foreach ($rows as $row) {
        fputcsv($output, array_values($row));
      }
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv;
  }

  /**
   * Cuenta registros recursivamente en datos de sección.
   */
  protected function countRecords(mixed $data): int {
    if (!is_array($data)) {
      return 0;
    }
    $count = 0;
    foreach ($data as $key => $value) {
      if (str_starts_with((string) $key, '_')) {
        continue;
      }
      if (is_array($value) && !empty($value)) {
        if (isset($value[0]) && is_array($value[0])) {
          $count += count($value);
        }
        else {
          $count += $this->countRecords($value);
        }
      }
    }
    return $count ?: (empty($data) ? 0 : 1);
  }

}
