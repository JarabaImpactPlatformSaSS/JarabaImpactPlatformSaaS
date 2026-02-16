<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Delegates billing invoices to the appropriate fiscal compliance module.
 *
 * Detects the invoice type (B2G, B2B, nacional) and delegates processing
 * to the corresponding fiscal module:
 * - VeriFactu: ALL invoices (RD 1007/2023 universal obligation).
 * - Facturae B2G: If buyer is public administration (Ley 25/2013).
 * - E-Factura B2B: If buyer is a business entity (Ley 18/2022).
 *
 * Uses optional DI — modules that are not installed result in NULL services
 * and their processing step is silently skipped.
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 11, F11-5.
 */
class FiscalInvoiceDelegationService {

  /**
   * NIF prefixes that indicate public administration entities.
   *
   * Q = Organismos públicos, S = Órganos autonómicos, P = Corporaciones locales.
   */
  protected const PUBLIC_ADMIN_NIF_PREFIXES = ['Q', 'S', 'P'];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $verifactuRecordService = NULL,
    protected ?object $facturaeXmlService = NULL,
    protected ?object $einvoiceDeliveryService = NULL,
  ) {}

  /**
   * Processes a finalized billing invoice through the fiscal stack.
   *
   * Called when a BillingInvoice transitions to 'finalized' status.
   * Determines invoice type and delegates to appropriate fiscal modules.
   *
   * @param object $billingInvoice
   *   The BillingInvoice entity.
   *
   * @return array
   *   Processing results with keys for each module attempted.
   */
  public function processFinalizedInvoice(object $billingInvoice): array {
    $results = [];
    $invoiceType = $this->detectInvoiceType($billingInvoice);

    $this->logger->info('Fiscal delegation for invoice @id: type=@type', [
      '@id' => $billingInvoice->id(),
      '@type' => $invoiceType,
    ]);

    // Step 1: VeriFactu — ALL invoices (universal obligation).
    if ($this->verifactuRecordService !== NULL) {
      $results['verifactu'] = $this->delegateToVerifactu($billingInvoice);
    }
    else {
      $results['verifactu'] = ['status' => 'skipped', 'reason' => 'Module not installed.'];
    }

    // Step 2: Facturae B2G — Only for public administration buyers.
    if ($invoiceType === 'b2g') {
      if ($this->facturaeXmlService !== NULL) {
        $results['facturae'] = $this->delegateToFacturae($billingInvoice);
      }
      else {
        $results['facturae'] = ['status' => 'skipped', 'reason' => 'Facturae module not installed.'];
        $this->logger->warning('B2G invoice @id requires Facturae module but it is not installed.', [
          '@id' => $billingInvoice->id(),
        ]);
      }
    }

    // Step 3: E-Factura B2B — Only for business-to-business invoices.
    if ($invoiceType === 'b2b') {
      if ($this->einvoiceDeliveryService !== NULL) {
        $results['einvoice_b2b'] = $this->delegateToEinvoice($billingInvoice);
      }
      else {
        $results['einvoice_b2b'] = ['status' => 'skipped', 'reason' => 'E-Invoice B2B module not installed.'];
        $this->logger->warning('B2B invoice @id requires E-Invoice module but it is not installed.', [
          '@id' => $billingInvoice->id(),
        ]);
      }
    }

    return $results;
  }

  /**
   * Detects the invoice type based on buyer NIF and context.
   *
   * @param object $billingInvoice
   *   The BillingInvoice entity.
   *
   * @return string
   *   One of: 'b2g', 'b2b', 'nacional'.
   */
  public function detectInvoiceType(object $billingInvoice): string {
    try {
      $buyerNif = $billingInvoice->get('buyer_nif')->value ?? '';

      // B2G: Buyer NIF starts with Q, S, or P (public administration).
      if ($buyerNif !== '' && in_array(strtoupper($buyerNif[0]), self::PUBLIC_ADMIN_NIF_PREFIXES, TRUE)) {
        return 'b2g';
      }

      // B2B: Buyer has a valid business NIF (CIF starting with A-H, J, U, V, N, W).
      if ($buyerNif !== '' && preg_match('/^[A-HJ-NP-SUVW]/i', $buyerNif)) {
        return 'b2b';
      }

      // Nacional: Consumer invoices or unclassifiable.
      return 'nacional';
    }
    catch (\Throwable $e) {
      $this->logger->error('Error detecting invoice type for @id: @msg', [
        '@id' => $billingInvoice->id(),
        '@msg' => $e->getMessage(),
      ]);
      return 'nacional';
    }
  }

  /**
   * Delegates invoice to VeriFactu for record creation.
   */
  protected function delegateToVerifactu(object $billingInvoice): array {
    try {
      $result = $this->verifactuRecordService->createFromBillingInvoice($billingInvoice);
      return [
        'status' => 'success',
        'record_id' => is_object($result) ? $result->id() : NULL,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('VeriFactu delegation failed for invoice @id: @msg', [
        '@id' => $billingInvoice->id(),
        '@msg' => $e->getMessage(),
      ]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  /**
   * Delegates invoice to Facturae for B2G XML generation.
   */
  protected function delegateToFacturae(object $billingInvoice): array {
    try {
      $result = $this->facturaeXmlService->generateFromBillingInvoice($billingInvoice);
      return [
        'status' => 'success',
        'invoice_id' => is_object($result) ? $result->id() : NULL,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Facturae delegation failed for invoice @id: @msg', [
        '@id' => $billingInvoice->id(),
        '@msg' => $e->getMessage(),
      ]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

  /**
   * Delegates invoice to E-Invoice B2B for UBL delivery.
   */
  protected function delegateToEinvoice(object $billingInvoice): array {
    try {
      $result = $this->einvoiceDeliveryService->createFromBillingInvoice($billingInvoice);
      return [
        'status' => 'success',
        'document_id' => is_object($result) ? $result->id() : NULL,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('E-Invoice delegation failed for invoice @id: @msg', [
        '@id' => $billingInvoice->id(),
        '@msg' => $e->getMessage(),
      ]);
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }

}
