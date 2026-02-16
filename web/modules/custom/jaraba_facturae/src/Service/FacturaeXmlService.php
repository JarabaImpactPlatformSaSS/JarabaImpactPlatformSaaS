<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_facturae\Entity\FacturaeDocument;
use Psr\Log\LoggerInterface;

/**
 * Construye XML Facturae 3.2.2 conforme al esquema XSD oficial.
 *
 * Genera el documento XML completo con FileHeader, Parties (SellerParty,
 * BuyerParty con TaxIdentification y AdministrativeCentres DIR3),
 * Invoices (InvoiceHeader, InvoiceIssueData, TaxesOutputs, TaxesWithheld,
 * InvoiceTotals, Items, PaymentDetails, LegalLiterals).
 *
 * La firma XAdES-EPES es responsabilidad de FacturaeXAdESService (FASE 7).
 *
 * Spec: Doc 180, Seccion 3.1.
 * Plan: FASE 6, entregable F6-4.
 */
class FacturaeXmlService {

  /**
   * Facturae 3.2.2 namespace URI.
   */
  private const FACTURAE_NS = 'http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml';

  /**
   * XML Digital Signature namespace.
   */
  private const DSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly FacturaeValidationService $validationService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Builds a complete Facturae 3.2.2 XML from a FacturaeDocument entity.
   *
   * @param \Drupal\jaraba_facturae\Entity\FacturaeDocument $document
   *   The Facturae document entity.
   *
   * @return string
   *   The generated XML string.
   *
   * @throws \RuntimeException
   *   If the document cannot be serialized to valid XML.
   */
  public function buildFacturaeXml(FacturaeDocument $document): string {
    $doc = new \DOMDocument('1.0', 'UTF-8');
    $doc->formatOutput = TRUE;

    // Root element: fe:Facturae.
    $root = $doc->createElementNS(self::FACTURAE_NS, 'fe:Facturae');
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', self::DSIG_NS);
    $doc->appendChild($root);

    // FileHeader.
    $root->appendChild($this->buildFileHeader($doc, $document));

    // Parties.
    $root->appendChild($this->buildParties($doc, $document));

    // Invoices.
    $root->appendChild($this->buildInvoices($doc, $document));

    $xml = $doc->saveXML();
    if ($xml === FALSE) {
      throw new \RuntimeException('Failed to serialize Facturae XML.');
    }

    return $xml;
  }

  /**
   * Builds the FileHeader element.
   */
  public function buildFileHeader(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $header = $doc->createElementNS(self::FACTURAE_NS, 'FileHeader');

    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'SchemaVersion', $document->get('schema_version')->value ?? '3.2.2'));
    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Modality', 'I'));
    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceIssuerType', $document->get('issuer_type')->value ?? 'EM'));

    // Batch block.
    $batch = $doc->createElementNS(self::FACTURAE_NS, 'Batch');

    $batchId = ($document->get('seller_nif')->value ?? '')
      . ($document->get('facturae_number')->value ?? '');
    $batch->appendChild($doc->createElementNS(self::FACTURAE_NS, 'BatchIdentifier', $batchId));
    $batch->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoicesCount', '1'));

    // Total amounts.
    $totalInvoice = $document->get('total_invoice_amount')->value ?? '0.00';
    $totalOutstanding = $document->get('total_outstanding')->value ?? '0.00';
    $totalExecutable = $document->get('total_executable')->value ?? '0.00';

    $tia = $doc->createElementNS(self::FACTURAE_NS, 'TotalInvoicesAmount');
    $tia->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($totalInvoice)));
    $batch->appendChild($tia);

    $toa = $doc->createElementNS(self::FACTURAE_NS, 'TotalOutstandingAmount');
    $toa->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($totalOutstanding)));
    $batch->appendChild($toa);

    $tea = $doc->createElementNS(self::FACTURAE_NS, 'TotalExecutableAmount');
    $tea->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($totalExecutable)));
    $batch->appendChild($tea);

    $batch->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceCurrencyCode', $document->get('currency_code')->value ?? 'EUR'));

    $header->appendChild($batch);

    return $header;
  }

  /**
   * Builds the Parties element (SellerParty + BuyerParty).
   */
  public function buildParties(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $parties = $doc->createElementNS(self::FACTURAE_NS, 'Parties');

    $parties->appendChild($this->buildParty($doc, $document, 'Seller'));
    $parties->appendChild($this->buildParty($doc, $document, 'Buyer'));

    return $parties;
  }

  /**
   * Builds a SellerParty or BuyerParty element.
   */
  protected function buildParty(\DOMDocument $doc, FacturaeDocument $document, string $role): \DOMElement {
    $prefix = strtolower($role);
    $partyElement = $doc->createElementNS(self::FACTURAE_NS, $role . 'Party');

    // TaxIdentification.
    $taxId = $doc->createElementNS(self::FACTURAE_NS, 'TaxIdentification');
    $taxId->appendChild($doc->createElementNS(self::FACTURAE_NS, 'PersonTypeCode', $document->get($prefix . '_person_type')->value ?? 'J'));
    $taxId->appendChild($doc->createElementNS(self::FACTURAE_NS, 'ResidenceTypeCode', $document->get($prefix . '_residence_type')->value ?? 'R'));
    $taxId->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxIdentificationNumber', $document->get($prefix . '_nif')->value ?? ''));
    $partyElement->appendChild($taxId);

    // LegalEntity or Individual.
    $personType = $document->get($prefix . '_person_type')->value ?? 'J';
    $name = $document->get($prefix . '_name')->value ?? '';

    if ($personType === 'J') {
      $entity = $doc->createElementNS(self::FACTURAE_NS, 'LegalEntity');
      $entity->appendChild($doc->createElementNS(self::FACTURAE_NS, 'CorporateName', $this->truncate($name, 80)));
    }
    else {
      $entity = $doc->createElementNS(self::FACTURAE_NS, 'Individual');
      $parts = explode(' ', $name, 2);
      $entity->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Name', $this->truncate($parts[0] ?? '', 40)));
      $entity->appendChild($doc->createElementNS(self::FACTURAE_NS, 'FirstSurname', $this->truncate($parts[1] ?? '', 40)));
    }

    // Address.
    $addressJson = $document->get($prefix . '_address_json')->value ?? '{}';
    $address = json_decode($addressJson, TRUE) ?: [];
    $addressEl = $doc->createElementNS(self::FACTURAE_NS, 'AddressInSpain');
    $addressEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Address', $this->truncate($address['address'] ?? '', 80)));
    $addressEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'PostCode', $address['postal_code'] ?? '00000'));
    $addressEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Town', $this->truncate($address['town'] ?? '', 50)));
    $addressEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Province', $this->truncate($address['province'] ?? '', 20)));
    $addressEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'CountryCode', $address['country_code'] ?? 'ESP'));
    $entity->appendChild($addressEl);

    $partyElement->appendChild($entity);

    // AdministrativeCentres for BuyerParty (B2G).
    if ($role === 'Buyer') {
      $centresJson = $document->get('buyer_admin_centres_json')->value ?? '';
      if (!empty($centresJson)) {
        $centres = json_decode($centresJson, TRUE) ?: [];
        if (!empty($centres)) {
          $acElement = $doc->createElementNS(self::FACTURAE_NS, 'AdministrativeCentres');
          foreach ($centres as $centre) {
            $ac = $doc->createElementNS(self::FACTURAE_NS, 'AdministrativeCentre');
            $ac->appendChild($doc->createElementNS(self::FACTURAE_NS, 'CentreCode', $centre['code'] ?? ''));
            $ac->appendChild($doc->createElementNS(self::FACTURAE_NS, 'RoleTypeCode', $centre['role'] ?? ''));
            if (!empty($centre['name'])) {
              $ac->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Name', $centre['name']));
            }
            $acElement->appendChild($ac);
          }
          $partyElement->appendChild($acElement);
        }
      }
    }

    return $partyElement;
  }

  /**
   * Builds the Invoices element.
   */
  public function buildInvoices(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $invoices = $doc->createElementNS(self::FACTURAE_NS, 'Invoices');
    $invoice = $doc->createElementNS(self::FACTURAE_NS, 'Invoice');

    // InvoiceHeader.
    $invoice->appendChild($this->buildInvoiceHeader($doc, $document));

    // InvoiceIssueData.
    $invoice->appendChild($this->buildInvoiceIssueData($doc, $document));

    // TaxesOutputs.
    $invoice->appendChild($this->buildTaxesOutputs($doc, $document));

    // TaxesWithheld (if applicable).
    $withheldJson = $document->get('taxes_withheld_json')->value ?? '';
    if (!empty($withheldJson)) {
      $withheld = json_decode($withheldJson, TRUE) ?: [];
      if (!empty($withheld)) {
        $invoice->appendChild($this->buildTaxesWithheld($doc, $withheld));
      }
    }

    // InvoiceTotals.
    $invoice->appendChild($this->buildInvoiceTotals($doc, $document));

    // Items.
    $invoice->appendChild($this->buildItems($doc, $document));

    // PaymentDetails (optional).
    $paymentJson = $document->get('payment_details_json')->value ?? '';
    if (!empty($paymentJson)) {
      $payments = json_decode($paymentJson, TRUE) ?: [];
      if (!empty($payments)) {
        $invoice->appendChild($this->buildPaymentDetails($doc, $payments));
      }
    }

    // LegalLiterals (optional).
    $literalsJson = $document->get('legal_literals_json')->value ?? '';
    if (!empty($literalsJson)) {
      $literals = json_decode($literalsJson, TRUE) ?: [];
      if (!empty($literals)) {
        $invoice->appendChild($this->buildLegalLiterals($doc, $literals));
      }
    }

    $invoices->appendChild($invoice);
    return $invoices;
  }

  /**
   * Builds the InvoiceHeader element.
   */
  protected function buildInvoiceHeader(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $header = $doc->createElementNS(self::FACTURAE_NS, 'InvoiceHeader');

    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceNumber', $document->get('facturae_number')->value ?? ''));
    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceSeriesCode', $document->get('facturae_series')->value ?? ''));
    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceDocumentType', $document->get('invoice_type')->value ?? 'FC'));
    $header->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceClass', $document->get('invoice_class')->value ?? 'OO'));

    // Corrective data (if applicable).
    $correctiveJson = $document->get('corrective_json')->value ?? '';
    if (!empty($correctiveJson)) {
      $corrective = json_decode($correctiveJson, TRUE) ?: [];
      if (!empty($corrective)) {
        $correctiveEl = $doc->createElementNS(self::FACTURAE_NS, 'Corrective');
        if (!empty($corrective['invoice_number'])) {
          $correctiveEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceNumber', $corrective['invoice_number']));
        }
        if (!empty($corrective['reason_code'])) {
          $correctiveEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'ReasonCode', $corrective['reason_code']));
        }
        if (!empty($corrective['reason_description'])) {
          $correctiveEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'ReasonDescription', $corrective['reason_description']));
        }
        if (!empty($corrective['correction_method'])) {
          $correctiveEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'CorrectionMethod', $corrective['correction_method']));
        }
        $header->appendChild($correctiveEl);
      }
    }

    return $header;
  }

  /**
   * Builds the InvoiceIssueData element.
   */
  protected function buildInvoiceIssueData(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $data = $doc->createElementNS(self::FACTURAE_NS, 'InvoiceIssueData');

    $issueDate = $document->get('issue_date')->value ?? '';
    $data->appendChild($doc->createElementNS(self::FACTURAE_NS, 'IssueDate', $this->formatDate($issueDate)));

    $operationDate = $document->get('operation_date')->value ?? '';
    if (!empty($operationDate)) {
      $data->appendChild($doc->createElementNS(self::FACTURAE_NS, 'OperationDate', $this->formatDate($operationDate)));
    }

    $data->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceCurrencyCode', $document->get('currency_code')->value ?? 'EUR'));
    $data->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxCurrencyCode', $document->get('currency_code')->value ?? 'EUR'));
    $data->appendChild($doc->createElementNS(self::FACTURAE_NS, 'LanguageName', $document->get('language_code')->value ?? 'es'));

    return $data;
  }

  /**
   * Builds the TaxesOutputs element.
   */
  protected function buildTaxesOutputs(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $taxesOutputs = $doc->createElementNS(self::FACTURAE_NS, 'TaxesOutputs');

    $taxesJson = $document->get('taxes_outputs_json')->value ?? '[]';
    $taxes = json_decode($taxesJson, TRUE) ?: [];

    foreach ($taxes as $tax) {
      $taxEl = $doc->createElementNS(self::FACTURAE_NS, 'Tax');
      $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxTypeCode', $tax['type_code'] ?? '01'));
      $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxRate', $this->formatAmount($tax['rate'] ?? '0.00')));

      $taxableBase = $doc->createElementNS(self::FACTURAE_NS, 'TaxableBase');
      $taxableBase->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($tax['base'] ?? '0.00')));
      $taxEl->appendChild($taxableBase);

      $taxAmount = $doc->createElementNS(self::FACTURAE_NS, 'TaxAmount');
      $taxAmount->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($tax['amount'] ?? '0.00')));
      $taxEl->appendChild($taxAmount);

      // Equivalence surcharge (if applicable).
      if (!empty($tax['equivalence_surcharge'])) {
        $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'EquivalenceSurcharge', $this->formatAmount($tax['equivalence_surcharge'])));
        $surchargeAmount = $doc->createElementNS(self::FACTURAE_NS, 'EquivalenceSurchargeAmount');
        $surchargeAmount->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($tax['surcharge_amount'] ?? '0.00')));
        $taxEl->appendChild($surchargeAmount);
      }

      $taxesOutputs->appendChild($taxEl);
    }

    return $taxesOutputs;
  }

  /**
   * Builds the TaxesWithheld element.
   */
  protected function buildTaxesWithheld(\DOMDocument $doc, array $taxes): \DOMElement {
    $taxesWithheld = $doc->createElementNS(self::FACTURAE_NS, 'TaxesWithheld');

    foreach ($taxes as $tax) {
      $taxEl = $doc->createElementNS(self::FACTURAE_NS, 'Tax');
      $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxTypeCode', $tax['type_code'] ?? '04'));
      $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxRate', $this->formatAmount($tax['rate'] ?? '0.00')));

      $taxableBase = $doc->createElementNS(self::FACTURAE_NS, 'TaxableBase');
      $taxableBase->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($tax['base'] ?? '0.00')));
      $taxEl->appendChild($taxableBase);

      $taxAmount = $doc->createElementNS(self::FACTURAE_NS, 'TaxAmount');
      $taxAmount->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($tax['amount'] ?? '0.00')));
      $taxEl->appendChild($taxAmount);

      $taxesWithheld->appendChild($taxEl);
    }

    return $taxesWithheld;
  }

  /**
   * Builds the InvoiceTotals element.
   */
  protected function buildInvoiceTotals(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $totals = $doc->createElementNS(self::FACTURAE_NS, 'InvoiceTotals');

    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalGrossAmount', $this->formatAmount($document->get('total_gross_amount')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalGeneralDiscounts', $this->formatAmount($document->get('total_general_discounts')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalGeneralSurcharges', $this->formatAmount($document->get('total_general_surcharges')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalGrossAmountBeforeTaxes', $this->formatAmount($document->get('total_gross_amount_before_taxes')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalTaxOutputs', $this->formatAmount($document->get('total_tax_outputs')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalTaxesWithheld', $this->formatAmount($document->get('total_tax_withheld')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InvoiceTotal', $this->formatAmount($document->get('total_invoice_amount')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalOutstandingAmount', $this->formatAmount($document->get('total_outstanding')->value ?? '0.00')));
    $totals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalExecutableAmount', $this->formatAmount($document->get('total_executable')->value ?? '0.00')));

    return $totals;
  }

  /**
   * Builds the Items element from invoice_lines_json.
   */
  protected function buildItems(\DOMDocument $doc, FacturaeDocument $document): \DOMElement {
    $items = $doc->createElementNS(self::FACTURAE_NS, 'Items');

    $linesJson = $document->get('invoice_lines_json')->value ?? '[]';
    $lines = json_decode($linesJson, TRUE) ?: [];

    foreach ($lines as $line) {
      $invoiceLine = $doc->createElementNS(self::FACTURAE_NS, 'InvoiceLine');

      if (!empty($line['item_description'])) {
        $invoiceLine->appendChild($doc->createElementNS(self::FACTURAE_NS, 'ItemDescription', $this->truncate($line['item_description'], 2500)));
      }

      $invoiceLine->appendChild($doc->createElementNS(self::FACTURAE_NS, 'Quantity', $this->formatAmount($line['quantity'] ?? '1')));
      $invoiceLine->appendChild($doc->createElementNS(self::FACTURAE_NS, 'UnitPriceWithoutTax', $this->formatAmount($line['unit_price'] ?? '0.00')));
      $invoiceLine->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalCost', $this->formatAmount($line['total_cost'] ?? '0.00')));
      $invoiceLine->appendChild($doc->createElementNS(self::FACTURAE_NS, 'GrossAmount', $this->formatAmount($line['gross_amount'] ?? '0.00')));

      // Line-level taxes.
      if (!empty($line['taxes'])) {
        $lineTaxes = $doc->createElementNS(self::FACTURAE_NS, 'TaxesOutputs');
        foreach ($line['taxes'] as $tax) {
          $taxEl = $doc->createElementNS(self::FACTURAE_NS, 'Tax');
          $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxTypeCode', $tax['type_code'] ?? '01'));
          $taxEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TaxRate', $this->formatAmount($tax['rate'] ?? '0.00')));

          $taxableBase = $doc->createElementNS(self::FACTURAE_NS, 'TaxableBase');
          $taxableBase->appendChild($doc->createElementNS(self::FACTURAE_NS, 'TotalAmount', $this->formatAmount($tax['base'] ?? '0.00')));
          $taxEl->appendChild($taxableBase);

          $lineTaxes->appendChild($taxEl);
        }
        $invoiceLine->appendChild($lineTaxes);
      }

      // Line discounts (optional).
      if (!empty($line['discounts'])) {
        $discountsEl = $doc->createElementNS(self::FACTURAE_NS, 'DiscountsAndRebates');
        foreach ($line['discounts'] as $discount) {
          $discountEl = $doc->createElementNS(self::FACTURAE_NS, 'Discount');
          $discountEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'DiscountReason', $discount['reason'] ?? ''));
          $discountEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'DiscountRate', $this->formatAmount($discount['rate'] ?? '0.00')));
          $discountEl->appendChild($doc->createElementNS(self::FACTURAE_NS, 'DiscountAmount', $this->formatAmount($discount['amount'] ?? '0.00')));
          $discountsEl->appendChild($discountEl);
        }
        $invoiceLine->appendChild($discountsEl);
      }

      $items->appendChild($invoiceLine);
    }

    return $items;
  }

  /**
   * Builds the PaymentDetails element.
   */
  protected function buildPaymentDetails(\DOMDocument $doc, array $payments): \DOMElement {
    $paymentDetails = $doc->createElementNS(self::FACTURAE_NS, 'PaymentDetails');

    foreach ($payments as $payment) {
      $installment = $doc->createElementNS(self::FACTURAE_NS, 'Installment');

      if (!empty($payment['due_date'])) {
        $installment->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InstallmentDueDate', $this->formatDate($payment['due_date'])));
      }

      $installment->appendChild($doc->createElementNS(self::FACTURAE_NS, 'InstallmentAmount', $this->formatAmount($payment['amount'] ?? '0.00')));
      $installment->appendChild($doc->createElementNS(self::FACTURAE_NS, 'PaymentMeans', $payment['method'] ?? '04'));

      if (!empty($payment['iban'])) {
        $accountToBeCredited = $doc->createElementNS(self::FACTURAE_NS, 'AccountToBeCredited');
        $accountToBeCredited->appendChild($doc->createElementNS(self::FACTURAE_NS, 'IBAN', $payment['iban']));
        $installment->appendChild($accountToBeCredited);
      }

      $paymentDetails->appendChild($installment);
    }

    return $paymentDetails;
  }

  /**
   * Builds the LegalLiterals element.
   */
  protected function buildLegalLiterals(\DOMDocument $doc, array $literals): \DOMElement {
    $legalLiterals = $doc->createElementNS(self::FACTURAE_NS, 'LegalLiterals');

    foreach ($literals as $literal) {
      $legalLiterals->appendChild($doc->createElementNS(self::FACTURAE_NS, 'LegalReference', $literal));
    }

    return $legalLiterals;
  }

  /**
   * Formats a numeric value to 2 decimal places.
   */
  protected function formatAmount(string|float $value): string {
    return number_format((float) $value, 2, '.', '');
  }

  /**
   * Formats a date value to YYYY-MM-DD.
   */
  protected function formatDate(string $date): string {
    if (empty($date)) {
      return '';
    }
    $timestamp = strtotime($date);
    return $timestamp !== FALSE ? date('Y-m-d', $timestamp) : $date;
  }

  /**
   * Truncates a string to a maximum length.
   */
  protected function truncate(string $value, int $maxLength): string {
    return mb_substr($value, 0, $maxLength);
  }

}
