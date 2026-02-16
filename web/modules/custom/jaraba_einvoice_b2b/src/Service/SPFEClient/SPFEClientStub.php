<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service\SPFEClient;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFEStatus;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFESubmission;
use Psr\Log\LoggerInterface;

/**
 * Stub implementation of SPFEClientInterface for development/testing.
 *
 * Simulates SPFE responses without making actual API calls. Used when:
 *   - AEAT has not yet published the SPFE API specification.
 *   - einvoice_tenant_config.spfe_environment = 'stub'.
 *   - Running tests that need predictable responses.
 *
 * All stub responses include a simulated delay (50ms) and return
 * deterministic data based on input.
 *
 * Spec: Doc 181, Section 3.6.
 * Plan: FASE 10, entregable F10-4.
 */
class SPFEClientStub implements SPFEClientInterface {

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs an SPFEClientStub.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('jaraba_einvoice_b2b');
  }

  /**
   * {@inheritdoc}
   */
  public function submitInvoice(string $xml, int $tenantId): SPFESubmission {
    $this->logger->info('SPFE Stub: submitInvoice for tenant @tid (XML length: @len)', [
      '@tid' => $tenantId,
      '@len' => strlen($xml),
    ]);

    // Simulate processing delay.
    usleep(50000);

    // Generate a deterministic submission ID.
    $submissionId = 'SPFE-STUB-' . strtoupper(substr(md5($xml . $tenantId), 0, 16));

    return SPFESubmission::accepted(
      submissionId: $submissionId,
      timestamp: date('c'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function querySubmission(string $submissionId): SPFEStatus {
    $this->logger->info('SPFE Stub: querySubmission @id', [
      '@id' => $submissionId,
    ]);

    usleep(50000);

    return SPFEStatus::fromResponse(
      submissionId: $submissionId,
      status: 'accepted',
      lastUpdated: date('c'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaymentStatus(int $eventId, int $tenantId): SPFESubmission {
    $this->logger->info('SPFE Stub: submitPaymentStatus event @eid for tenant @tid', [
      '@eid' => $eventId,
      '@tid' => $tenantId,
    ]);

    usleep(50000);

    $submissionId = 'SPFE-PAY-STUB-' . strtoupper(substr(md5((string) $eventId . $tenantId), 0, 12));

    return SPFESubmission::accepted(
      submissionId: $submissionId,
      timestamp: date('c'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function queryReceivedInvoices(int $tenantId, array $filters = []): array {
    $this->logger->info('SPFE Stub: queryReceivedInvoices for tenant @tid', [
      '@tid' => $tenantId,
    ]);

    usleep(50000);

    // Return an empty array for stub — no simulated inbound invoices.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(int $tenantId): bool {
    $this->logger->info('SPFE Stub: testConnection for tenant @tid — always returns TRUE.', [
      '@tid' => $tenantId,
    ]);

    usleep(50000);

    return TRUE;
  }

}
