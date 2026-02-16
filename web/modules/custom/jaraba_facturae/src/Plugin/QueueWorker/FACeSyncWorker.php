<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_facturae\Service\FACeClientService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Synchronizes FACe invoice statuses periodically.
 *
 * Processes queued items to query FACe for the latest status of invoices
 * that have been sent. Updates the local facturae_document entity with
 * the current FACe tramitacion and anulacion status.
 *
 * Cron queues items every 4 hours for documents in 'sent' or 'registered'
 * FACe status that were sent more than 1 hour ago.
 *
 * Spec: Doc 180, Seccion 5.2.
 * Plan: FASE 7, entregable F7-5.
 *
 * @QueueWorker(
 *   id = "jaraba_facturae_face_sync",
 *   title = @Translation("FACe Status Sync"),
 *   cron = {"time" = 120}
 * )
 */
class FACeSyncWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly FACeClientService $faceClient,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
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
      $container->get('jaraba_facturae.face_client'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_facturae'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $documentId = $data['document_id'] ?? NULL;
    $registryNumber = $data['registry_number'] ?? '';
    $tenantId = $data['tenant_id'] ?? 0;

    if (empty($documentId) || empty($registryNumber) || empty($tenantId)) {
      $this->logger->warning('FACe sync: invalid queue item — missing document_id, registry_number, or tenant_id.');
      return;
    }

    $storage = $this->entityTypeManager->getStorage('facturae_document');
    $document = $storage->load($documentId);

    if ($document === NULL) {
      $this->logger->warning('FACe sync: document @id not found.', ['@id' => $documentId]);
      return;
    }

    // Query FACe for current status.
    $status = $this->faceClient->queryInvoice($registryNumber, (int) $tenantId);

    $tramitacionCode = $status->tramitacionCode;
    if (empty($tramitacionCode)) {
      $this->logger->info('FACe sync: no tramitacion update for document @id.', ['@id' => $documentId]);
      return;
    }

    // Update local entity.
    $entityStatus = $status->toEntityStatus();
    $document->set('face_status', $entityStatus);
    $document->set('face_tramitacion_status', $tramitacionCode);
    $document->set('face_tramitacion_date', date('Y-m-d\TH:i:s'));

    // Handle cancellation status.
    if ($status->hasCancellation()) {
      $document->set('face_anulacion_status', $status->anulacionCode);
    }

    // Store full response.
    $document->set('face_response_json', json_encode($status->toArray()));
    $document->save();

    $this->logger->info('FACe sync: document @id updated to status @status (FACe code @code).', [
      '@id' => $documentId,
      '@status' => $entityStatus,
      '@code' => $tramitacionCode,
    ]);
  }

}
