<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_tenant_export\Service\TenantExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Limpia exportaciones expiradas y sus archivos ZIP.
 *
 * Encolado cada 6h via hook_cron() con State API guard.
 *
 * @QueueWorker(
 *   id = "jaraba_tenant_export_cleanup",
 *   title = @Translation("Tenant Export Cleanup"),
 *   cron = {"time" = 30}
 * )
 */
class TenantExportCleanupWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected TenantExportService $exportService,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_tenant_export.export_service'),
      $container->get('logger.channel.jaraba_tenant_export'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $this->logger->info('Running tenant export cleanup.');

    try {
      $cleaned = $this->exportService->cleanupExpiredExports();
      $this->logger->info('Cleanup completed: @count expired exports removed.', [
        '@count' => $cleaned,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Cleanup failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

}
