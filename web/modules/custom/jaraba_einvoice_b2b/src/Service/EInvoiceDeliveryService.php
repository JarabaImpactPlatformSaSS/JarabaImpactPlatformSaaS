<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_einvoice_b2b\Exception\EInvoiceDeliveryException;
use Drupal\jaraba_einvoice_b2b\Service\SPFEClient\SPFEClientInterface;
use Drupal\jaraba_einvoice_b2b\ValueObject\DeliveryResult;
use Psr\Log\LoggerInterface;

/**
 * Multi-channel delivery orchestrator for e-invoices.
 *
 * Routes outbound e-invoices to the appropriate delivery channel:
 *   - SPFE: Mandatory copy to Solucion Publica (when API available).
 *   - Platform: Internal delivery between Jaraba tenants.
 *   - Email: Fallback with UBL XML attachment.
 *   - Peppol: European e-invoicing network (future).
 *
 * Each delivery attempt is logged in EInvoiceDeliveryLog (append-only).
 *
 * Spec: Doc 181, Section 3.3.
 * Plan: FASE 10, entregable F10-1.
 */
class EInvoiceDeliveryService {

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SPFEClientInterface $spfeClient,
    protected MailManagerInterface $mailManager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->logger = $logger_factory->get('jaraba_einvoice_b2b');
  }

  /**
   * Delivers an e-invoice document through the optimal channel.
   *
   * @param int $documentId
   *   The EInvoiceDocument entity ID.
   * @param string|null $channel
   *   Force a specific channel. NULL = auto-resolve.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\DeliveryResult
   *   The delivery result.
   */
  public function deliver(int $documentId, ?string $channel = NULL): DeliveryResult {
    $storage = $this->entityTypeManager->getStorage('einvoice_document');
    $document = $storage->load($documentId);
    if (!$document) {
      return DeliveryResult::failure('unknown', "Document {$documentId} not found.");
    }

    $resolvedChannel = $channel ?? $this->resolveChannel($document);
    $startTime = hrtime(TRUE);

    try {
      $result = match ($resolvedChannel) {
        'spfe' => $this->sendToSPFE($document),
        'platform' => $this->sendViaPlatform($document),
        'email' => $this->sendViaEmail($document),
        'peppol' => DeliveryResult::failure('peppol', 'Peppol delivery not yet implemented. Requires network certification.'),
        default => DeliveryResult::failure($resolvedChannel, "Unknown channel: {$resolvedChannel}"),
      };
    }
    catch (\Throwable $e) {
      $result = DeliveryResult::failure($resolvedChannel, $e->getMessage());
      $this->logger->error('E-Invoice delivery failed for document @id via @channel: @error', [
        '@id' => $documentId,
        '@channel' => $resolvedChannel,
        '@error' => $e->getMessage(),
      ]);
    }

    $durationMs = (int) ((hrtime(TRUE) - $startTime) / 1_000_000);

    // Log the delivery attempt.
    $this->logDelivery($document, $resolvedChannel, $result, $durationMs);

    // Update document delivery status.
    $document->set('delivery_status', $result->success ? 'delivered' : 'failed');
    $document->set('delivery_method', $resolvedChannel);
    if ($result->success) {
      $document->set('delivery_timestamp', date('Y-m-d\TH:i:s'));
    }
    $document->set('delivery_response_json', json_encode($result->toArray()));
    $document->save();

    return $result;
  }

  /**
   * Resolves the best delivery channel for a document.
   *
   * Priority: platform (internal) > email > spfe.
   *
   * @param object $document
   *   The EInvoiceDocument entity.
   *
   * @return string
   *   The channel name.
   */
  public function resolveChannel(object $document): string {
    $tenantId = (int) $document->get('tenant_id')->target_id;

    // Check if buyer is also a Jaraba tenant (platform delivery).
    $buyerNif = $document->get('buyer_nif')->value ?? '';
    if ($this->isTenantNif($buyerNif)) {
      return 'platform';
    }

    // Check tenant config for SPFE.
    $config = $this->loadTenantConfig($tenantId);
    if ($config && (bool) $config->get('spfe_enabled')->value) {
      return 'spfe';
    }

    // Fallback: email.
    return 'email';
  }

  /**
   * Sends an e-invoice to the SPFE.
   *
   * @param object $document
   *   The EInvoiceDocument entity.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\DeliveryResult
   */
  protected function sendToSPFE(object $document): DeliveryResult {
    $xml = $document->get('xml_signed')->value ?: $document->get('xml_content')->value;
    if (empty($xml)) {
      return DeliveryResult::failure('spfe', 'No XML content available for SPFE submission.');
    }

    $tenantId = (int) $document->get('tenant_id')->target_id;
    $submission = $this->spfeClient->submitInvoice($xml, $tenantId);

    if ($submission->success) {
      $document->set('spfe_submission_id', $submission->submissionId);
      $document->set('spfe_status', 'sent');
      $document->set('spfe_response_json', json_encode($submission->toArray()));

      return DeliveryResult::success('spfe', $submission->submissionId, [
        'spfe_submission_id' => $submission->submissionId,
      ]);
    }

    $document->set('spfe_status', 'error');
    $document->set('spfe_response_json', json_encode($submission->toArray()));

    return DeliveryResult::failure('spfe', $submission->errorMessage ?? 'SPFE submission rejected.');
  }

  /**
   * Sends an e-invoice internally between Jaraba tenants.
   *
   * @param object $document
   *   The EInvoiceDocument entity.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\DeliveryResult
   */
  protected function sendViaPlatform(object $document): DeliveryResult {
    $buyerNif = $document->get('buyer_nif')->value ?? '';
    $buyerConfig = $this->findTenantConfigByNif($buyerNif);

    if (!$buyerConfig) {
      return DeliveryResult::failure('platform', "Buyer NIF {$buyerNif} is not a registered tenant.");
    }

    // Create inbound document for the buyer tenant.
    $inboundStorage = $this->entityTypeManager->getStorage('einvoice_document');
    $inbound = $inboundStorage->create([
      'tenant_id' => $buyerConfig->get('tenant_id')->target_id,
      'direction' => 'inbound',
      'format' => $document->get('format')->value,
      'xml_content' => $document->get('xml_content')->value,
      'xml_signed' => $document->get('xml_signed')->value,
      'invoice_number' => $document->get('invoice_number')->value,
      'invoice_date' => $document->get('invoice_date')->value,
      'due_date' => $document->get('due_date')->value,
      'seller_nif' => $document->get('seller_nif')->value,
      'seller_name' => $document->get('seller_name')->value,
      'buyer_nif' => $document->get('buyer_nif')->value,
      'buyer_name' => $document->get('buyer_name')->value,
      'currency_code' => $document->get('currency_code')->value,
      'total_without_tax' => $document->get('total_without_tax')->value,
      'total_tax' => $document->get('total_tax')->value,
      'total_amount' => $document->get('total_amount')->value,
      'delivery_status' => 'delivered',
      'delivery_method' => 'platform',
      'status' => 'pending',
    ]);
    $inbound->save();

    return DeliveryResult::success('platform', 'PLATFORM-' . $inbound->id(), [
      'inbound_document_id' => (int) $inbound->id(),
    ]);
  }

  /**
   * Sends an e-invoice via email with XML attachment.
   *
   * @param object $document
   *   The EInvoiceDocument entity.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\DeliveryResult
   */
  protected function sendViaEmail(object $document): DeliveryResult {
    $tenantId = (int) $document->get('tenant_id')->target_id;
    $config = $this->loadTenantConfig($tenantId);

    // Determine recipient email.
    $buyerNif = $document->get('buyer_nif')->value ?? '';
    $buyerConfig = $this->findTenantConfigByNif($buyerNif);
    $email = $buyerConfig ? ($buyerConfig->get('inbound_email')->value ?? '') : '';

    if (empty($email)) {
      return DeliveryResult::failure('email', 'No email address available for buyer.');
    }

    $invoiceNumber = $document->get('invoice_number')->value ?? '';
    $params = [
      'subject' => "E-Invoice: {$invoiceNumber}",
      'document_id' => $document->id(),
      'invoice_number' => $invoiceNumber,
      'xml_content' => $document->get('xml_signed')->value ?: $document->get('xml_content')->value,
    ];

    $result = $this->mailManager->mail(
      'jaraba_einvoice_b2b',
      'einvoice_delivery',
      $email,
      'es',
      $params,
    );

    if ($result['result'] ?? FALSE) {
      return DeliveryResult::success('email', 'EMAIL-' . $document->id(), [
        'recipient' => $email,
      ]);
    }

    return DeliveryResult::failure('email', "Failed to send email to {$email}.");
  }

  /**
   * Logs a delivery attempt in the append-only delivery log.
   */
  protected function logDelivery(object $document, string $channel, DeliveryResult $result, int $durationMs): void {
    try {
      $logStorage = $this->entityTypeManager->getStorage('einvoice_delivery_log');
      $log = $logStorage->create([
        'tenant_id' => $document->get('tenant_id')->target_id,
        'einvoice_document_id' => $document->id(),
        'operation' => 'send',
        'channel' => $channel,
        'request_payload' => json_encode(['document_id' => $document->id(), 'channel' => $channel]),
        'response_payload' => json_encode($result->toArray()),
        'response_code' => $result->success ? 'OK' : 'ERROR',
        'http_status' => $result->httpStatus,
        'duration_ms' => $durationMs,
        'error_detail' => $result->errorMessage,
      ]);
      $log->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create delivery log: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Checks if a NIF belongs to a registered tenant.
   */
  protected function isTenantNif(string $nif): bool {
    return $this->findTenantConfigByNif($nif) !== NULL;
  }

  /**
   * Finds a tenant config by NIF.
   */
  protected function findTenantConfigByNif(string $nif): ?object {
    if (empty($nif)) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('einvoice_tenant_config');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('nif_emisor', $nif)
      ->condition('active', TRUE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Loads tenant config for a given tenant ID.
   */
  protected function loadTenantConfig(int $tenantId): ?object {
    $storage = $this->entityTypeManager->getStorage('einvoice_tenant_config');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('active', TRUE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

}
