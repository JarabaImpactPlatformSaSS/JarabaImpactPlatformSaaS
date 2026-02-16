<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Model;

/**
 * Neutral semantic model conforming to EN 16931 standard.
 *
 * Intermediate representation for bidirectional format conversion
 * (Facturae 3.2.2 <-> UBL 2.1). Every field maps to an EN 16931
 * Business Term (BT-n). The model holds only validated, format-agnostic
 * invoice data — no XML, no format-specific quirks.
 *
 * Usage:
 *   $model = EN16931Model::fromArray([...]);
 *   $xml   = $ublService->generateFromModel($model);
 *
 * Spec: Doc 181, Section 3.2.
 * Plan: FASE 9, entregable F9-7.
 */
final class EN16931Model {

  /**
   * Constructs an EN16931Model.
   *
   * @param string $invoiceNumber
   *   BT-1: Invoice number.
   * @param string $issueDate
   *   BT-2: Issue date (Y-m-d).
   * @param int $invoiceTypeCode
   *   BT-3: 380=Invoice, 381=CreditNote, 383=DebitNote, 386=Prepayment.
   * @param string $currencyCode
   *   BT-5: ISO 4217 currency code.
   * @param string|null $taxPointDate
   *   BT-7: VAT accounting date (Y-m-d).
   * @param string|null $dueDate
   *   BT-9: Payment due date (Y-m-d).
   * @param string|null $buyerReference
   *   BT-10: Buyer reference / PO number.
   * @param string|null $projectReference
   *   BT-11: Project reference.
   * @param string|null $contractReference
   *   BT-12: Contract reference.
   * @param string|null $precedingInvoiceReference
   *   BT-25: Preceding invoice reference (for credit notes).
   * @param array $seller
   *   BT-27..BT-43: Seller party data.
   * @param array $buyer
   *   BT-44..BT-63: Buyer party data.
   * @param array $paymentMeans
   *   BG-16: Payment means data.
   * @param array $paymentTerms
   *   BG-17: Payment terms.
   * @param array $lines
   *   BG-25: Invoice line items.
   * @param array $taxTotals
   *   BG-23: Tax breakdown totals.
   * @param string $totalWithoutTax
   *   BT-109: Sum of invoice line net amounts.
   * @param string $totalTax
   *   BT-110: Invoice total VAT amount.
   * @param string $totalWithTax
   *   BT-112: Invoice total with VAT.
   * @param string $amountDue
   *   BT-115: Amount due for payment.
   * @param string|null $note
   *   BT-22: Invoice note.
   */
  public function __construct(
    public readonly string $invoiceNumber,
    public readonly string $issueDate,
    public readonly int $invoiceTypeCode,
    public readonly string $currencyCode,
    public readonly ?string $taxPointDate,
    public readonly ?string $dueDate,
    public readonly ?string $buyerReference,
    public readonly ?string $projectReference,
    public readonly ?string $contractReference,
    public readonly ?string $precedingInvoiceReference,
    public readonly array $seller,
    public readonly array $buyer,
    public readonly array $paymentMeans,
    public readonly array $paymentTerms,
    public readonly array $lines,
    public readonly array $taxTotals,
    public readonly string $totalWithoutTax,
    public readonly string $totalTax,
    public readonly string $totalWithTax,
    public readonly string $amountDue,
    public readonly ?string $note,
  ) {}

  /**
   * Creates an EN16931Model from an associative array.
   *
   * @param array $data
   *   Keyed array matching constructor parameters.
   *
   * @return self
   */
  public static function fromArray(array $data): self {
    return new self(
      invoiceNumber: $data['invoice_number'],
      issueDate: $data['issue_date'],
      invoiceTypeCode: (int) ($data['invoice_type_code'] ?? 380),
      currencyCode: $data['currency_code'] ?? 'EUR',
      taxPointDate: $data['tax_point_date'] ?? NULL,
      dueDate: $data['due_date'] ?? NULL,
      buyerReference: $data['buyer_reference'] ?? NULL,
      projectReference: $data['project_reference'] ?? NULL,
      contractReference: $data['contract_reference'] ?? NULL,
      precedingInvoiceReference: $data['preceding_invoice_reference'] ?? NULL,
      seller: $data['seller'] ?? [],
      buyer: $data['buyer'] ?? [],
      paymentMeans: $data['payment_means'] ?? [],
      paymentTerms: $data['payment_terms'] ?? [],
      lines: $data['lines'] ?? [],
      taxTotals: $data['tax_totals'] ?? [],
      totalWithoutTax: $data['total_without_tax'] ?? '0.00',
      totalTax: $data['total_tax'] ?? '0.00',
      totalWithTax: $data['total_with_tax'] ?? '0.00',
      amountDue: $data['amount_due'] ?? '0.00',
      note: $data['note'] ?? NULL,
    );
  }

  /**
   * Creates an EN16931Model from an EInvoiceDocument entity.
   *
   * @param \Drupal\jaraba_einvoice_b2b\Entity\EInvoiceDocument $document
   *   The e-invoice document entity.
   *
   * @return self
   */
  public static function fromDocument($document): self {
    $lineItems = json_decode($document->get('line_items_json')->value ?? '[]', TRUE) ?: [];
    $taxBreakdown = json_decode($document->get('tax_breakdown_json')->value ?? '[]', TRUE) ?: [];
    $paymentTermsData = json_decode($document->get('payment_terms_json')->value ?? '[]', TRUE) ?: [];

    return new self(
      invoiceNumber: $document->get('invoice_number')->value,
      issueDate: $document->get('invoice_date')->value,
      invoiceTypeCode: 380,
      currencyCode: $document->get('currency_code')->value ?? 'EUR',
      taxPointDate: NULL,
      dueDate: $document->get('due_date')->value,
      buyerReference: NULL,
      projectReference: NULL,
      contractReference: NULL,
      precedingInvoiceReference: NULL,
      seller: [
        'name' => $document->get('seller_name')->value,
        'tax_id' => $document->get('seller_nif')->value,
        'tax_scheme' => 'ES:VAT',
        'endpoint_id' => $document->get('seller_nif')->value,
        'endpoint_scheme' => '9920',
      ],
      buyer: [
        'name' => $document->get('buyer_name')->value,
        'tax_id' => $document->get('buyer_nif')->value,
        'tax_scheme' => 'ES:VAT',
        'endpoint_id' => $document->get('buyer_nif')->value,
        'endpoint_scheme' => '9920',
      ],
      paymentMeans: [],
      paymentTerms: $paymentTermsData,
      lines: $lineItems,
      taxTotals: $taxBreakdown,
      totalWithoutTax: $document->get('total_without_tax')->value ?? '0.00',
      totalTax: $document->get('total_tax')->value ?? '0.00',
      totalWithTax: $document->get('total_amount')->value ?? '0.00',
      amountDue: $document->get('total_amount')->value ?? '0.00',
      note: NULL,
    );
  }

  /**
   * Exports the model to an associative array.
   *
   * @return array
   */
  public function toArray(): array {
    return [
      'invoice_number' => $this->invoiceNumber,
      'issue_date' => $this->issueDate,
      'invoice_type_code' => $this->invoiceTypeCode,
      'currency_code' => $this->currencyCode,
      'tax_point_date' => $this->taxPointDate,
      'due_date' => $this->dueDate,
      'buyer_reference' => $this->buyerReference,
      'project_reference' => $this->projectReference,
      'contract_reference' => $this->contractReference,
      'preceding_invoice_reference' => $this->precedingInvoiceReference,
      'seller' => $this->seller,
      'buyer' => $this->buyer,
      'payment_means' => $this->paymentMeans,
      'payment_terms' => $this->paymentTerms,
      'lines' => $this->lines,
      'tax_totals' => $this->taxTotals,
      'total_without_tax' => $this->totalWithoutTax,
      'total_tax' => $this->totalTax,
      'total_with_tax' => $this->totalWithTax,
      'amount_due' => $this->amountDue,
      'note' => $this->note,
    ];
  }

  /**
   * Returns whether this is a credit note (type 381).
   *
   * @return bool
   */
  public function isCreditNote(): bool {
    return $this->invoiceTypeCode === 381;
  }

  /**
   * Returns whether this is a standard invoice (type 380).
   *
   * @return bool
   */
  public function isInvoice(): bool {
    return $this->invoiceTypeCode === 380;
  }

  /**
   * Validates minimum required fields.
   *
   * @return array
   *   List of validation error strings (empty if valid).
   */
  public function validate(): array {
    $errors = [];

    if (empty($this->invoiceNumber)) {
      $errors[] = 'BT-1: Invoice number is required.';
    }
    if (empty($this->issueDate)) {
      $errors[] = 'BT-2: Issue date is required.';
    }
    if (!in_array($this->invoiceTypeCode, [380, 381, 383, 386], TRUE)) {
      $errors[] = 'BT-3: Invalid invoice type code.';
    }
    if (empty($this->currencyCode) || strlen($this->currencyCode) !== 3) {
      $errors[] = 'BT-5: Currency code must be 3-letter ISO 4217.';
    }
    if (empty($this->seller['name'])) {
      $errors[] = 'BT-27: Seller name is required.';
    }
    if (empty($this->seller['tax_id'])) {
      $errors[] = 'BT-31: Seller VAT identifier is required.';
    }
    if (empty($this->buyer['name'])) {
      $errors[] = 'BT-44: Buyer name is required.';
    }
    if (empty($this->buyer['tax_id'])) {
      $errors[] = 'BT-48: Buyer VAT identifier is required.';
    }
    if (empty($this->lines)) {
      $errors[] = 'BG-25: At least one invoice line is required.';
    }

    return $errors;
  }

}
