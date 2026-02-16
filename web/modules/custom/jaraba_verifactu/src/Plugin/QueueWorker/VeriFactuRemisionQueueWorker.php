<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_verifactu\Service\VeriFactuRemisionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa lotes de remision VeriFactu en background.
 *
 * Cada item de la cola contiene un batch_id que referencia un
 * VeriFactuRemisionBatch entity. El worker carga el batch y lo
 * envia a la AEAT via VeriFactuRemisionService.
 *
 * Spec: Doc 179, Seccion 4. Plan: FASE 3, entregable F3-3.
 *
 * @QueueWorker(
 *   id = "verifactu_remision",
 *   title = @Translation("VeriFactu AEAT Remision"),
 *   cron = {"time" = 120}
 * )
 */
class VeriFactuRemisionQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected VeriFactuRemisionService $remisionService,
    protected EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('jaraba_verifactu.remision_service'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_verifactu'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $batchId = $data['batch_id'] ?? NULL;

    if (!$batchId) {
      $this->logger->error('VeriFactu queue item missing batch_id.');
      return;
    }

    $batch = $this->entityTypeManager
      ->getStorage('verifactu_remision_batch')
      ->load($batchId);

    if (!$batch) {
      $this->logger->error('VeriFactu remision batch @batch not found.', [
        '@batch' => $batchId,
      ]);
      return;
    }

    // Skip already processed batches.
    $status = $batch->get('status')->value;
    if ($status === 'sent') {
      $this->logger->info('Skipping already sent batch @batch.', ['@batch' => $batchId]);
      return;
    }

    $result = $this->remisionService->submitBatch($batch);

    if ($result->isSuccess) {
      $this->logger->info('Batch @batch submitted successfully in @time ms.', [
        '@batch' => $batchId,
        '@time' => round($result->durationMs, 2),
      ]);
    }
    else {
      $this->logger->warning('Batch @batch submission failed: @error', [
        '@batch' => $batchId,
        '@error' => $result->errorMessage,
      ]);
    }
  }

}
