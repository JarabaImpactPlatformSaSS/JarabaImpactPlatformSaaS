<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service;

use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;

/**
 * Bidirectional format conversion: Facturae 3.2.2 <-> UBL 2.1.
 *
 * Converts between invoice formats using the neutral EN16931Model as
 * intermediate representation. This ensures round-trip fidelity:
 *   Facturae -> EN16931Model -> UBL -> EN16931Model -> Facturae
 *
 * Methods:
 *   - detectFormat(): Identifies XML format from root namespace.
 *   - toNeutralModel(): Parses any supported format to EN16931Model.
 *   - convertToUbl(): Facturae -> UBL via neutral model.
 *   - convertToFacturae(): UBL -> Facturae via neutral model.
 *
 * Spec: Doc 181, Section 3.2.
 * Plan: FASE 9, entregable F9-6.
 */
class EInvoiceFormatConverterService {

  /**
   * Known format namespaces.
   */
  protected const NS_FACTURAE = 'http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml';
  protected const NS_UBL_INVOICE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
  protected const NS_UBL_CREDIT_NOTE = 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2';
  protected const NS_CII = 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100';

  /**
   * The UBL service.
   *
   * @var \Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService
   */
  protected EInvoiceUblService $ublService;

  /**
   * Constructs an EInvoiceFormatConverterService.
   *
   * @param \Drupal\jaraba_einvoice_b2b\Service\EInvoiceUblService $ubl_service
   *   The UBL generation service.
   */
  public function __construct(EInvoiceUblService $ubl_service) {
    $this->ublService = $ubl_service;
  }

  /**
   * Detects the format of an XML string.
   *
   * @param string $xml
   *   The XML string to analyze.
   *
   * @return string
   *   One of: 'facturae_3.2.2', 'ubl_2.1', 'cii', 'unknown'.
   */
  public function detectFormat(string $xml): string {
    $dom = new \DOMDocument();
    if (!@$dom->loadXML($xml)) {
      return 'unknown';
    }

    $root = $dom->documentElement;
    $ns = $root->namespaceURI;
    $localName = $root->localName;

    if ($ns === self::NS_FACTURAE || $localName === 'Facturae') {
      return 'facturae_3.2.2';
    }

    if ($ns === self::NS_UBL_INVOICE || $ns === self::NS_UBL_CREDIT_NOTE) {
      return 'ubl_2.1';
    }

    if ($ns === self::NS_CII) {
      return 'cii';
    }

    return 'unknown';
  }

  /**
   * Extracts an EN16931Model from any supported XML format.
   *
   * @param string $xml
   *   The XML string.
   *
   * @return \Drupal\jaraba_einvoice_b2b\Model\EN16931Model
   *   The neutral semantic model.
   *
   * @throws \RuntimeException
   *   If the format is unsupported or XML is invalid.
   */
  public function toNeutralModel(string $xml): EN16931Model {
    $format = $this->detectFormat($xml);

    return match ($format) {
      'ubl_2.1' => $this->ublService->parseUblToModel($xml),
      'facturae_3.2.2' => $this->parseFacturaeToModel($xml),
      default => throw new \RuntimeException("Unsupported format: {$format}. Only UBL 2.1 and Facturae 3.2.2 are supported."),
    };
  }

  /**
   * Converts Facturae XML to UBL 2.1 via neutral model.
   *
   * @param string $facturaeXml
   *   The Facturae 3.2.2 XML string.
   *
   * @return string
   *   The UBL 2.1 XML string.
   */
  public function convertToUbl(string $facturaeXml): string {
    $model = $this->parseFacturaeToModel($facturaeXml);
    return $this->ublService->generateFromModel($model);
  }

  /**
   * Converts UBL XML to Facturae 3.2.2 via neutral model.
   *
   * @param string $ublXml
   *   The UBL 2.1 XML string.
   *
   * @return string
   *   The Facturae 3.2.2 XML string.
   */
  public function convertToFacturae(string $ublXml): string {
    $model = $this->ublService->parseUblToModel($ublXml);
    return $this->generateFacturaeFromModel($model);
  }

  /**
   * Converts any supported XML to a target format.
   *
   * @param string $xml
   *   The source XML string.
   * @param string $targetFormat
   *   Target: 'ubl_2.1' or 'facturae_3.2.2'.
   *
   * @return string
   *   The converted XML string.
   *
   * @throws \RuntimeException
   *   If the source or target format is unsupported.
   */
  public function convertTo(string $xml, string $targetFormat): string {
    $model = $this->toNeutralModel($xml);

    return match ($targetFormat) {
      'ubl_2.1' => $this->ublService->generateFromModel($model),
      'facturae_3.2.2' => $this->generateFacturaeFromModel($model),
      default => throw new \RuntimeException("Unsupported target format: {$targetFormat}."),
    };
  }

  /**
   * Parses Facturae 3.2.2 XML into an EN16931Model.
   *
   * @param string $xml
   *   The Facturae XML string.
   *
   * @return \Drupal\jaraba_einvoice_b2b\Model\EN16931Model
   *   The parsed semantic model.
   *
   * @throws \RuntimeException
   *   If the XML cannot be parsed.
   */
  protected function parseFacturaeToModel(string $xml): EN16931Model {
    $dom = new \DOMDocument();
    if (!$dom->loadXML($xml)) {
      throw new \RuntimeException('Invalid Facturae XML: cannot parse document.');
    }

    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('fe', self::NS_FACTURAE);

    // Try with namespace prefix first, fall back to without.
    $invoiceNumber = $this->facturaeXpath($xpath, [
      '//fe:Invoices/fe:Invoice/fe:InvoiceHeader/fe:InvoiceNumber',
      '//Invoices/Invoice/InvoiceHeader/InvoiceNumber',
    ]);
    $issueDate = $this->facturaeXpath($xpath, [
      '//fe:Invoices/fe:Invoice/fe:InvoiceIssueData/fe:IssueDate',
      '//Invoices/Invoice/InvoiceIssueData/IssueDate',
    ]);

    // Seller.
    $sellerNif = $this->facturaeXpath($xpath, [
      '//fe:Parties/fe:SellerParty/fe:TaxIdentification/fe:TaxIdentificationNumber',
      '//Parties/SellerParty/TaxIdentification/TaxIdentificationNumber',
    ]);
    $sellerName = $this->facturaeXpath($xpath, [
      '//fe:Parties/fe:SellerParty/fe:LegalEntity/fe:CorporateName',
      '//Parties/SellerParty/LegalEntity/CorporateName',
    ]);

    // Buyer.
    $buyerNif = $this->facturaeXpath($xpath, [
      '//fe:Parties/fe:BuyerParty/fe:TaxIdentification/fe:TaxIdentificationNumber',
      '//Parties/BuyerParty/TaxIdentification/TaxIdentificationNumber',
    ]);
    $buyerName = $this->facturaeXpath($xpath, [
      '//fe:Parties/fe:BuyerParty/fe:LegalEntity/fe:CorporateName',
      '//Parties/BuyerParty/LegalEntity/CorporateName',
    ]);

    // Totals.
    $totalBeforeTax = $this->facturaeXpath($xpath, [
      '//fe:InvoiceTotals/fe:TotalGrossAmountBeforeTaxes',
      '//InvoiceTotals/TotalGrossAmountBeforeTaxes',
    ]) ?? '0.00';
    $totalTax = $this->facturaeXpath($xpath, [
      '//fe:InvoiceTotals/fe:TotalTaxOutputs',
      '//InvoiceTotals/TotalTaxOutputs',
    ]) ?? '0.00';
    $totalAmount = $this->facturaeXpath($xpath, [
      '//fe:InvoiceTotals/fe:InvoiceTotal',
      '//InvoiceTotals/InvoiceTotal',
    ]) ?? '0.00';

    // Invoice type: Facturae uses InvoiceDocumentType (FC, FA, etc.).
    $docType = $this->facturaeXpath($xpath, [
      '//fe:InvoiceHeader/fe:InvoiceDocumentType',
      '//InvoiceHeader/InvoiceDocumentType',
    ]);
    $isCorrectiveStr = $this->facturaeXpath($xpath, [
      '//fe:InvoiceHeader/fe:Corrective',
      '//InvoiceHeader/Corrective',
    ]);
    $invoiceTypeCode = $isCorrectiveStr !== NULL ? 381 : 380;

    // Lines.
    $lines = [];
    $lineNodes = $xpath->query('//fe:Items/fe:InvoiceLine|//Items/InvoiceLine');
    if ($lineNodes !== FALSE) {
      foreach ($lineNodes as $lineNode) {
        $lines[] = [
          'description' => $this->nodeChildValue($lineNode, 'ItemDescription') ?? '',
          'quantity' => $this->nodeChildValue($lineNode, 'Quantity') ?? '1',
          'price' => $this->nodeChildValue($lineNode, 'UnitPriceWithoutTax') ?? '0.00',
          'net_amount' => $this->nodeChildValue($lineNode, 'TotalCost') ?? '0.00',
          'tax_percent' => $this->facturaeLineTaxRate($lineNode),
          'tax_category' => 'S',
        ];
      }
    }

    // Tax breakdown.
    $taxTotals = [];
    $taxNodes = $xpath->query('//fe:TaxesOutputs/fe:Tax|//TaxesOutputs/Tax');
    if ($taxNodes !== FALSE) {
      foreach ($taxNodes as $taxNode) {
        $taxTotals[] = [
          'taxable_amount' => $this->nodeChildValue($taxNode, 'TaxableBase/TotalAmount') ?? '0.00',
          'tax_amount' => $this->nodeChildValue($taxNode, 'TaxAmount/TotalAmount') ?? '0.00',
          'category_id' => 'S',
          'percent' => $this->nodeChildValue($taxNode, 'TaxRate') ?? '21.00',
        ];
      }
    }

    // Payment terms (IBAN).
    $iban = $this->facturaeXpath($xpath, [
      '//fe:PaymentDetails/fe:Installment/fe:AccountToBeCredited/fe:IBAN',
      '//PaymentDetails/Installment/AccountToBeCredited/IBAN',
    ]);
    $paymentMeans = $iban !== NULL ? ['code' => '30', 'iban' => $iban] : [];

    return new EN16931Model(
      invoiceNumber: $invoiceNumber ?? '',
      issueDate: $issueDate ?? '',
      invoiceTypeCode: $invoiceTypeCode,
      currencyCode: 'EUR',
      taxPointDate: NULL,
      dueDate: $this->facturaeXpath($xpath, [
        '//fe:PaymentDetails/fe:Installment/fe:InstallmentDueDate',
        '//PaymentDetails/Installment/InstallmentDueDate',
      ]),
      buyerReference: NULL,
      projectReference: NULL,
      contractReference: NULL,
      precedingInvoiceReference: NULL,
      seller: [
        'name' => $sellerName ?? '',
        'tax_id' => $sellerNif ?? '',
        'tax_scheme' => 'ES:VAT',
      ],
      buyer: [
        'name' => $buyerName ?? '',
        'tax_id' => $buyerNif ?? '',
        'tax_scheme' => 'ES:VAT',
      ],
      paymentMeans: $paymentMeans,
      paymentTerms: [],
      lines: $lines,
      taxTotals: $taxTotals,
      totalWithoutTax: $totalBeforeTax,
      totalTax: $totalTax,
      totalWithTax: $totalAmount,
      amountDue: $totalAmount,
      note: NULL,
    );
  }

  /**
   * Generates Facturae 3.2.2 XML from an EN16931Model.
   *
   * @param \Drupal\jaraba_einvoice_b2b\Model\EN16931Model $model
   *   The neutral semantic model.
   *
   * @return string
   *   The Facturae 3.2.2 XML string.
   */
  protected function generateFacturaeFromModel(EN16931Model $model): string {
    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = TRUE;

    $root = $dom->createElementNS(self::NS_FACTURAE, 'Facturae');
    $dom->appendChild($root);

    // FileHeader.
    $fileHeader = $dom->createElement('FileHeader');
    $this->addTextNode($dom, $fileHeader, 'SchemaVersion', '3.2.2');
    $this->addTextNode($dom, $fileHeader, 'Modality', 'I');

    $batch = $dom->createElement('Batch');
    $this->addTextNode($dom, $batch, 'BatchIdentifier', $model->seller['tax_id'] . $model->invoiceNumber);
    $this->addTextNode($dom, $batch, 'InvoicesCount', '1');

    $totalInvoicesAmount = $dom->createElement('TotalInvoicesAmount');
    $this->addTextNode($dom, $totalInvoicesAmount, 'TotalAmount', $model->totalWithTax);
    $batch->appendChild($totalInvoicesAmount);

    $totalOutstandingAmount = $dom->createElement('TotalOutstandingAmount');
    $this->addTextNode($dom, $totalOutstandingAmount, 'TotalAmount', $model->amountDue);
    $batch->appendChild($totalOutstandingAmount);

    $this->addTextNode($dom, $batch, 'InvoiceCurrencyCode', $model->currencyCode);
    $fileHeader->appendChild($batch);
    $root->appendChild($fileHeader);

    // Parties.
    $parties = $dom->createElement('Parties');
    $this->buildFacturaeParty($dom, $parties, 'SellerParty', $model->seller);
    $this->buildFacturaeParty($dom, $parties, 'BuyerParty', $model->buyer);
    $root->appendChild($parties);

    // Invoices.
    $invoices = $dom->createElement('Invoices');
    $invoice = $dom->createElement('Invoice');

    // InvoiceHeader.
    $header = $dom->createElement('InvoiceHeader');
    $this->addTextNode($dom, $header, 'InvoiceNumber', $model->invoiceNumber);
    $this->addTextNode($dom, $header, 'InvoiceSeriesCode', '');
    $this->addTextNode($dom, $header, 'InvoiceDocumentType', $model->isCreditNote() ? 'RA' : 'FC');
    $this->addTextNode($dom, $header, 'InvoiceClass', 'OO');
    $invoice->appendChild($header);

    // InvoiceIssueData.
    $issueData = $dom->createElement('InvoiceIssueData');
    $this->addTextNode($dom, $issueData, 'IssueDate', $model->issueDate);
    $this->addTextNode($dom, $issueData, 'InvoiceCurrencyCode', $model->currencyCode);
    $this->addTextNode($dom, $issueData, 'TaxCurrencyCode', $model->currencyCode);
    $this->addTextNode($dom, $issueData, 'LanguageCode', 'es');
    $invoice->appendChild($issueData);

    // TaxesOutputs.
    $taxesOutputs = $dom->createElement('TaxesOutputs');
    foreach ($model->taxTotals as $taxItem) {
      $tax = $dom->createElement('Tax');
      $this->addTextNode($dom, $tax, 'TaxTypeCode', '01');
      $this->addTextNode($dom, $tax, 'TaxRate', $taxItem['percent'] ?? '21.00');

      $taxableBase = $dom->createElement('TaxableBase');
      $this->addTextNode($dom, $taxableBase, 'TotalAmount', $taxItem['taxable_amount'] ?? '0.00');
      $tax->appendChild($taxableBase);

      $taxAmount = $dom->createElement('TaxAmount');
      $this->addTextNode($dom, $taxAmount, 'TotalAmount', $taxItem['tax_amount'] ?? '0.00');
      $tax->appendChild($taxAmount);

      $taxesOutputs->appendChild($tax);
    }
    $invoice->appendChild($taxesOutputs);

    // InvoiceTotals.
    $totals = $dom->createElement('InvoiceTotals');
    $this->addTextNode($dom, $totals, 'TotalGrossAmount', $model->totalWithoutTax);
    $this->addTextNode($dom, $totals, 'TotalGrossAmountBeforeTaxes', $model->totalWithoutTax);
    $this->addTextNode($dom, $totals, 'TotalTaxOutputs', $model->totalTax);
    $this->addTextNode($dom, $totals, 'TotalTaxesWithheld', '0.00');
    $this->addTextNode($dom, $totals, 'InvoiceTotal', $model->totalWithTax);
    $this->addTextNode($dom, $totals, 'TotalOutstandingAmount', $model->amountDue);
    $this->addTextNode($dom, $totals, 'TotalExecutableAmount', $model->amountDue);
    $invoice->appendChild($totals);

    // Items.
    $items = $dom->createElement('Items');
    foreach ($model->lines as $line) {
      $invoiceLine = $dom->createElement('InvoiceLine');
      $this->addTextNode($dom, $invoiceLine, 'ItemDescription', $line['description'] ?? '');
      $this->addTextNode($dom, $invoiceLine, 'Quantity', (string) ($line['quantity'] ?? '1'));
      $this->addTextNode($dom, $invoiceLine, 'UnitPriceWithoutTax', $line['price'] ?? $line['net_amount'] ?? '0.00');
      $this->addTextNode($dom, $invoiceLine, 'TotalCost', $line['net_amount'] ?? '0.00');
      $this->addTextNode($dom, $invoiceLine, 'GrossAmount', $line['net_amount'] ?? '0.00');

      // Line tax.
      $lineTaxes = $dom->createElement('TaxesOutputs');
      $lineTax = $dom->createElement('Tax');
      $this->addTextNode($dom, $lineTax, 'TaxTypeCode', '01');
      $this->addTextNode($dom, $lineTax, 'TaxRate', $line['tax_percent'] ?? '21.00');

      $lineTaxBase = $dom->createElement('TaxableBase');
      $this->addTextNode($dom, $lineTaxBase, 'TotalAmount', $line['net_amount'] ?? '0.00');
      $lineTax->appendChild($lineTaxBase);

      $lineTaxAmount = $dom->createElement('TaxAmount');
      $taxCalc = bcmul($line['net_amount'] ?? '0', bcdiv($line['tax_percent'] ?? '21', '100', 4), 2);
      $this->addTextNode($dom, $lineTaxAmount, 'TotalAmount', $taxCalc);
      $lineTax->appendChild($lineTaxAmount);

      $lineTaxes->appendChild($lineTax);
      $invoiceLine->appendChild($lineTaxes);

      $items->appendChild($invoiceLine);
    }
    $invoice->appendChild($items);

    // Payment details.
    if (!empty($model->paymentMeans['iban'])) {
      $paymentDetails = $dom->createElement('PaymentDetails');
      $installment = $dom->createElement('Installment');
      $this->addTextNode($dom, $installment, 'InstallmentDueDate', $model->dueDate ?? $model->issueDate);
      $this->addTextNode($dom, $installment, 'InstallmentAmount', $model->amountDue);
      $this->addTextNode($dom, $installment, 'PaymentMeans', '04');

      $account = $dom->createElement('AccountToBeCredited');
      $this->addTextNode($dom, $account, 'IBAN', $model->paymentMeans['iban']);
      if (!empty($model->paymentMeans['bic'])) {
        $this->addTextNode($dom, $account, 'BankCode', $model->paymentMeans['bic']);
      }
      $installment->appendChild($account);

      $paymentDetails->appendChild($installment);
      $invoice->appendChild($paymentDetails);
    }

    $invoices->appendChild($invoice);
    $root->appendChild($invoices);

    return $dom->saveXML();
  }

  /**
   * Builds a Facturae party element (SellerParty/BuyerParty).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $parent
   *   The Parties element.
   * @param string $partyTag
   *   'SellerParty' or 'BuyerParty'.
   * @param array $partyData
   *   Party data with name, tax_id keys.
   */
  protected function buildFacturaeParty(\DOMDocument $dom, \DOMElement $parent, string $partyTag, array $partyData): void {
    $partyEl = $dom->createElement($partyTag);

    $taxId = $dom->createElement('TaxIdentification');
    $this->addTextNode($dom, $taxId, 'PersonTypeCode', 'J');
    $this->addTextNode($dom, $taxId, 'ResidenceTypeCode', 'R');
    $nif = preg_replace('/^ES/i', '', $partyData['tax_id'] ?? '');
    $this->addTextNode($dom, $taxId, 'TaxIdentificationNumber', $nif);
    $partyEl->appendChild($taxId);

    $legalEntity = $dom->createElement('LegalEntity');
    $this->addTextNode($dom, $legalEntity, 'CorporateName', $partyData['name'] ?? '');

    if (!empty($partyData['address'])) {
      $addrInSpain = $dom->createElement('AddressInSpain');
      $this->addTextNode($dom, $addrInSpain, 'Address', $partyData['address']['street'] ?? '');
      $this->addTextNode($dom, $addrInSpain, 'PostCode', $partyData['address']['postal_code'] ?? '');
      $this->addTextNode($dom, $addrInSpain, 'Town', $partyData['address']['city'] ?? '');
      $this->addTextNode($dom, $addrInSpain, 'Province', $partyData['address']['subdivision'] ?? '');
      $this->addTextNode($dom, $addrInSpain, 'CountryCode', 'ESP');
      $legalEntity->appendChild($addrInSpain);
    }

    $partyEl->appendChild($legalEntity);
    $parent->appendChild($partyEl);
  }

  /**
   * Attempts multiple XPath expressions, returns first match.
   *
   * @param \DOMXPath $xpath
   *   The XPath object.
   * @param array $expressions
   *   Array of XPath expressions to try.
   *
   * @return string|null
   *   The text content or NULL.
   */
  protected function facturaeXpath(\DOMXPath $xpath, array $expressions): ?string {
    foreach ($expressions as $expr) {
      $nodes = $xpath->query($expr);
      if ($nodes !== FALSE && $nodes->length > 0) {
        return $nodes->item(0)->textContent;
      }
    }
    return NULL;
  }

  /**
   * Extracts the tax rate from a Facturae line node.
   *
   * @param \DOMNode $lineNode
   *   The InvoiceLine DOM node.
   *
   * @return string
   *   The tax rate percentage.
   */
  protected function facturaeLineTaxRate(\DOMNode $lineNode): string {
    foreach ($lineNode->childNodes as $child) {
      if ($child->localName === 'TaxesOutputs') {
        foreach ($child->childNodes as $tax) {
          if ($tax->localName === 'Tax') {
            $rate = $this->nodeChildValue($tax, 'TaxRate');
            if ($rate !== NULL) {
              return $rate;
            }
          }
        }
      }
    }
    return '21.00';
  }

  /**
   * Gets a child node's text value by tag path.
   *
   * @param \DOMNode $node
   *   The parent node.
   * @param string $path
   *   Slash-separated path of child tag names.
   *
   * @return string|null
   *   The text content or NULL.
   */
  protected function nodeChildValue(\DOMNode $node, string $path): ?string {
    $parts = explode('/', $path);
    $current = $node;

    foreach ($parts as $tagName) {
      $found = FALSE;
      foreach ($current->childNodes as $child) {
        if ($child->localName === $tagName) {
          $current = $child;
          $found = TRUE;
          break;
        }
      }
      if (!$found) {
        return NULL;
      }
    }

    return $current->textContent;
  }

  /**
   * Helper to add a text element.
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $parent
   *   The parent element.
   * @param string $tag
   *   The element tag name.
   * @param string $value
   *   The text value.
   */
  protected function addTextNode(\DOMDocument $dom, \DOMElement $parent, string $tag, string $value): void {
    $el = $dom->createElement($tag, htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
    $parent->appendChild($el);
  }

}
