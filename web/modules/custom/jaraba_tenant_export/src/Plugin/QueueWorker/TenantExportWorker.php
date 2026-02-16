<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_tenant_export\Service\TenantExportService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa exportaciones de tenant en background.
 *
 * Cada item de la cola contiene un record_id que referencia un
 * TenantExportRecord. El worker llama a processExport() para
 * recopilar datos y generar el ZIP.
 *
 * @QueueWorker(
 *   id = "jaraba_tenant_export",
 *   title = @Translation("Tenant Export Worker"),
 *   cron = {"time" = 55}
 * )
 */
class TenantExportWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Máximo de reintentos antes de marcar como failed.
   */
  protected const MAX_ATTEMPTS = 3;

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
    $recordId = $data['record_id'] ?? NULL;
    $attempt = $data['attempt'] ?? 0;

    if (!$recordId) {
      $this->logger->error('Tenant export queue item missing record_id.');
      return;
    }

    try {
      $this->exportService->processExport($recordId);
    }
    catch (\Exception $e) {
      $this->logger->error('Export @id failed on attempt @attempt: @msg', [
        '@id' => $recordId,
        '@attempt' => $attempt + 1,
        '@msg' => $e->getMessage(),
      ]);

      if ($attempt < self::MAX_ATTEMPTS - 1) {
        // Re-enqueue with incremented attempt.
        $queue = \Drupal::service('queue')->get('jaraba_tenant_export');
        $queue->createItem([
          'record_id' => $recordId,
          'group_id' => $data['group_id'] ?? 0,
          'tenant_entity_id' => $data['tenant_entity_id'] ?? 0,
          'sections' => $data['sections'] ?? [],
          'attempt' => $attempt + 1,
        ]);
      }
      else {
        $this->logger->error('Export @id permanently failed after @max attempts.', [
          '@id' => $recordId,
          '@max' => self::MAX_ATTEMPTS,
        ]);
      }
    }
  }

}
