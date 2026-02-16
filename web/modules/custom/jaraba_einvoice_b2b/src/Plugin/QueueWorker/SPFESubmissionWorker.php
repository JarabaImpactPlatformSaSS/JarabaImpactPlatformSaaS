<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes queued SPFE invoice submissions.
 *
 * @QueueWorker(
 *   id = "einvoice_spfe_submission",
 *   title = @Translation("E-Invoice SPFE Submission"),
 *   cron = {"time" = 60}
 * )
 *
 * Spec: Doc 181, Section 3.6.
 * Plan: FASE 10 (ECA-EI-005).
 */
class SPFESubmissionWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SPFEClientInterface $spfeClient,
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
      $container->get('entity_type.manager'),
      $container->get('jaraba_einvoice_b2b.spfe_client'),
      $container->get('logger.factory')->get('jaraba_einvoice_b2b'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $documentId = $data['document_id'] ?? NULL;
    $tenantId = $data['tenant_id'] ?? NULL;

    if (!$documentId || !$tenantId) {
      $this->logger->error('SPFESubmissionWorker: Missing document_id or tenant_id.');
      return;
    }

    $document = $this->entityTypeManager->getStorage('einvoice_document')->load($documentId);
    if (!$document) {
      $this->logger->error('SPFESubmissionWorker: Document @id not found.', ['@id' => $documentId]);
      return;
    }

    $xml = $document->get('xml_signed')->value ?: $document->get('xml_content')->value;
    if (empty($xml)) {
      $this->logger->error('SPFESubmissionWorker: No XML content for document @id.', ['@id' => $documentId]);
      return;
    }

    try {
      $result = $this->spfeClient->submitInvoice($xml, $tenantId);

      $document->set('spfe_submission_id', $result->submissionId);
      $document->set('spfe_status', $result->success ? 'sent' : 'error');
      $document->set('spfe_response_json', json_encode($result->toArray()));
      $document->save();

      $this->logger->info('SPFESubmissionWorker: Document @id submitted to SPFE: @status', [
        '@id' => $documentId,
        '@status' => $result->success ? 'accepted' : 'rejected',
      ]);
    }
    catch (\Throwable $e) {
      $document->set('spfe_status', 'error');
      $document->set('spfe_response_json', json_encode(['error' => $e->getMessage()]));
      $document->save();

      $this->logger->error('SPFESubmissionWorker: Error for document @id: @error', [
        '@id' => $documentId,
        '@error' => $e->getMessage(),
      ]);

      throw $e;
    }
  }

}
