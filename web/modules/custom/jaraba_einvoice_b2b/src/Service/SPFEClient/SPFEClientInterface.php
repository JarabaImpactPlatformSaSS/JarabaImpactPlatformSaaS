<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service\SPFEClient;

use Drupal\jaraba_einvoice_b2b\ValueObject\SPFEStatus;
use Drupal\jaraba_einvoice_b2b\ValueObject\SPFESubmission;

/**
 * Interface for the SPFE (Solucion Publica de Facturacion Electronica) client.
 *
 * Defines the contract for communicating with the AEAT SPFE system.
 * Two implementations:
 *   - SPFEClientStub: For development/testing (simulates responses).
 *   - SPFEClientLive: For production (when AEAT publishes the API).
 *
 * The active implementation is selected via einvoice_tenant_config.spfe_environment:
 *   'stub' -> SPFEClientStub
 *   'test' -> SPFEClientLive (test environment)
 *   'production' -> SPFEClientLive (production environment)
 *
 * Spec: Doc 181, Section 3.6.
 * Plan: FASE 10, entregable F10-4.
 */
interface SPFEClientInterface {

  /**
   * Submits an e-invoice XML to the SPFE.
   *
   * @param string $xml
   *   The signed UBL XML content.
   * @param int $tenantId
   *   The tenant ID for credential lookup.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\SPFESubmission
   *   The submission result.
   *
   * @throws \Drupal\jaraba_einvoice_b2b\Exception\SPFEConnectionException
   *   If the connection fails.
   */
  public function submitInvoice(string $xml, int $tenantId): SPFESubmission;

  /**
   * Queries the status of a previous submission.
   *
   * @param string $submissionId
   *   The SPFE submission identifier.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\SPFEStatus
   *   The current status.
   */
  public function querySubmission(string $submissionId): SPFEStatus;

  /**
   * Submits a payment status event to the SPFE.
   *
   * Required by Ley 18/2022 (Crea y Crece) for payment tracking.
   *
   * @param int $eventId
   *   The payment event entity ID.
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\SPFESubmission
   *   The submission result.
   */
  public function submitPaymentStatus(int $eventId, int $tenantId): SPFESubmission;

  /**
   * Queries received (inbound) invoices from the SPFE.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param array $filters
   *   Optional filters: date_from, date_to, status.
   *
   * @return array
   *   Array of inbound invoice data.
   */
  public function queryReceivedInvoices(int $tenantId, array $filters = []): array;

  /**
   * Tests the connection to the SPFE.
   *
   * @param int $tenantId
   *   The tenant ID for credential lookup.
   *
   * @return bool
   *   TRUE if connection is successful.
   */
  public function testConnection(int $tenantId): bool;

}
