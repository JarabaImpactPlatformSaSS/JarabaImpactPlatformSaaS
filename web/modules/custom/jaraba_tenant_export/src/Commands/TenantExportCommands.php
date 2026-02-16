<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_tenant_export\Service\TenantExportService;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;

/**
 * Drush commands for tenant export and backup management.
 */
class TenantExportCommands extends DrushCommands {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantExportService $exportService,
    protected ?LoggerInterface $logger,
  ) {
    parent::__construct();
  }

  /**
   * Trigger export for a specific tenant.
   *
   * @command tenant-export:backup
   * @aliases te-backup
   * @param int $tenantId
   *   The group/tenant ID to export.
   * @option sections Comma-separated list of sections to export.
   * @option type Export type: full, partial, or gdpr_portability.
   * @usage tenant-export:backup 42
   *   Export all data for tenant 42.
   * @usage tenant-export:backup 42 --sections=core,analytics --type=partial
   *   Partial export with specific sections.
   */
  public function backup(int $tenantId, array $options = ['sections' => '', 'type' => 'full']): void {
    $this->io()->title("Tenant Export — Tenant $tenantId");

    // Validate tenant exists.
    $group = $this->entityTypeManager->getStorage('group')->load($tenantId);
    if (!$group) {
      $this->io()->error("Tenant (group) with ID $tenantId not found.");
      return;
    }

    $sections = !empty($options['sections'])
      ? array_map('trim', explode(',', $options['sections']))
      : [];

    $type = $options['type'] ?: 'full';
    $userId = 1; // Admin for CLI exports.

    // Look for tenant entity.
    $tenantEntityId = 0;
    try {
      $tenantIds = $this->entityTypeManager->getStorage('tenant')->getQuery()
        ->accessCheck(FALSE)
        ->condition('group_id', $tenantId)
        ->range(0, 1)
        ->execute();
      $tenantEntityId = !empty($tenantIds) ? (int) reset($tenantIds) : 0;
    }
    catch (\Exception) {
      // Tenant entity might not exist.
    }

    $this->io()->text("Requesting $type export for tenant $tenantId...");

    $result = $this->exportService->requestExport($tenantId, $tenantEntityId, $userId, $type, $sections);

    if ($result['success']) {
      $this->io()->text('Export queued with record ID: ' . $result['record_id']);
      $this->io()->text('Processing export now...');

      // Process immediately in CLI context.
      $this->exportService->processExport($result['record_id']);

      // Check result.
      $record = $this->entityTypeManager->getStorage('tenant_export_record')->load($result['record_id']);
      if ($record && $record->get('status')->value === 'completed') {
        $filePath = $record->get('file_path')->value;
        $fileSize = (int) $record->get('file_size')->value;
        $this->io()->success("Export completed! File: $filePath ($fileSize bytes)");
      }
      else {
        $error = $record ? ($record->get('error_message')->value ?? 'Unknown') : 'Record not found';
        $this->io()->error("Export failed: $error");
      }
    }
    else {
      $this->io()->error($result['message'] ?? 'Export request failed.');
    }
  }

  /**
   * Force cleanup of expired exports.
   *
   * @command tenant-export:cleanup
   * @aliases te-cleanup
   * @usage tenant-export:cleanup
   *   Remove all expired exports and their ZIP files.
   */
  public function cleanup(): void {
    $this->io()->title('Tenant Export — Cleanup Expired');

    $cleaned = $this->exportService->cleanupExpiredExports();

    if ($cleaned > 0) {
      $this->io()->success("Cleaned up $cleaned expired exports.");
    }
    else {
      $this->io()->text('No expired exports to clean up.');
    }
  }

  /**
   * Show statistics about exports and backups.
   *
   * @command tenant-export:status
   * @aliases te-status
   * @usage tenant-export:status
   *   Display export and backup statistics.
   */
  public function status(): void {
    $this->io()->title('Tenant Export — Status');

    $storage = $this->entityTypeManager->getStorage('tenant_export_record');

    // Count by status.
    $statuses = ['queued', 'collecting', 'packaging', 'completed', 'failed', 'expired', 'cancelled'];
    $rows = [];

    foreach ($statuses as $status) {
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', $status)
        ->count()
        ->execute();
      $rows[] = [$status, $count];
    }

    $this->io()->section('Export Records by Status');
    $this->io()->table(['Status', 'Count'], $rows);

    // Total size of active exports.
    $completedIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'completed')
      ->execute();

    $totalSize = 0;
    if (!empty($completedIds)) {
      $records = $storage->loadMultiple($completedIds);
      foreach ($records as $record) {
        $totalSize += (int) ($record->get('file_size')->value ?? 0);
      }
    }

    $this->io()->section('Storage');
    $this->io()->table(['Metric', 'Value'], [
      ['Active export files', count($completedIds)],
      ['Total size', $this->formatBytes($totalSize)],
    ]);

    $this->io()->success('Status report generated.');
  }

  /**
   * Formats bytes to human-readable string.
   */
  protected function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) {
      return number_format($bytes / 1073741824, 2) . ' GB';
    }
    if ($bytes >= 1048576) {
      return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
      return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
  }

}
