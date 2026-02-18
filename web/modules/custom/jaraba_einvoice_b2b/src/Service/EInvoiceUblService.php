<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service;

use Drupal\jaraba_einvoice_b2b\Entity\EInvoiceDocument;
use Drupal\jaraba_einvoice_b2b\Model\EN16931Model;

/**
 * Generates UBL 2.1 XML conforming to EN 16931 standard.
 *
 * Complete mapping of EN 16931 Business Terms (BT-1 to BT-115) to UBL
 * Invoice/CreditNote elements. Uses native PHP DOMDocument with no
 * external Composer dependencies.
 *
 * Supported invoice types:
 *   - 380: Invoice (standard)
 *   - 381: Credit Note
 *   - 383: Debit Note
 *   - 386: Prepayment Invoice
 *
 * Spec: Doc 181, Section 3.1.
 * Plan: FASE 9, entregable F9-5.
 */
class EInvoiceUblService {

  /**
   * UBL 2.1 namespace constants.
   */
  protected const NS_UBL_INVOICE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
  protected const NS_UBL_CREDIT_NOTE = 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2';
  protected const NS_CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
  protected const NS_CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

  /**
   * EN 16931 Peppol BIS 3.0 customization ID.
   */
  protected const CUSTOMIZATION_ID = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';
  protected const PROFILE_ID = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

  /**
   * Generates UBL 2.1 XML from an EInvoiceDocument entity.
   *
   * @param \Drupal\jaraba_einvoice_b2b\Entity\EInvoiceDocument $document
   *   The e-invoice document entity.
   *
   * @return string
   *   The generated UBL XML string.
   */
  public function generateUbl(EInvoiceDocument $document): string {
    $model = EN16931Model::fromDocument($document);
    return $this->generateFromModel($model);
  }

  /**
   * Generates UBL 2.1 XML from an EN16931Model.
   *
   * @param \Drupal\jaraba_einvoice_b2b\Model\EN16931Model $model
   *   The neutral semantic model.
   *
   * @return string
   *   The generated UBL XML string.
   */
  public function generateFromModel(EN16931Model $model): string {
    $isCreditNote = $model->isCreditNote();
    $rootNs = $isCreditNote ? self::NS_UBL_CREDIT_NOTE : self::NS_UBL_INVOICE;
    $rootTag = $isCreditNote ? 'CreditNote' : 'Invoice';

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->formatOutput = TRUE;

    $root = $dom->createElementNS($rootNs, $rootTag);
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::NS_CAC);
    $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::NS_CBC);
    $dom->appendChild($root);

    // BT-24: Specification identifier.
    $this->appendCbc($dom, $root, 'CustomizationID', self::CUSTOMIZATION_ID);

    // BT-23: Business process type.
    $this->appendCbc($dom, $root, 'ProfileID', self::PROFILE_ID);

    // BT-1: Invoice number.
    $this->appendCbc($dom, $root, 'ID', $model->invoiceNumber);

    // BT-2: Issue date.
    $this->appendCbc($dom, $root, 'IssueDate', $model->issueDate);

    // BT-9: Payment due date.
    if ($model->dueDate !== NULL) {
      $this->appendCbc($dom, $root, 'DueDate', $model->dueDate);
    }

    // BT-3: Invoice type code.
    $this->appendCbc($dom, $root, 'InvoiceTypeCode', (string) $model->invoiceTypeCode);

    // BT-22: Invoice note.
    if ($model->note !== NULL) {
      $this->appendCbc($dom, $root, 'Note', $model->note);
    }

    // BT-7: Tax point date.
    if ($model->taxPointDate !== NULL) {
      $this->appendCbc($dom, $root, 'TaxPointDate', $model->taxPointDate);
    }

    // BT-5: Invoice currency code.
    $this->appendCbc($dom, $root, 'DocumentCurrencyCode', $model->currencyCode);

    // BT-10: Buyer reference.
    if ($model->buyerReference !== NULL) {
      $this->appendCbc($dom, $root, 'BuyerReference', $model->buyerReference);
    }

    // BT-25: Preceding invoice reference (for credit notes).
    if ($model->precedingInvoiceReference !== NULL) {
      $billingRef = $dom->createElementNS(self::NS_CAC, 'cac:BillingReference');
      $invoiceDocRef = $dom->createElementNS(self::NS_CAC, 'cac:InvoiceDocumentReference');
      $this->appendCbc($dom, $invoiceDocRef, 'ID', $model->precedingInvoiceReference);
      $billingRef->appendChild($invoiceDocRef);
      $root->appendChild($billingRef);
    }

    // BT-12: Contract reference.
    if ($model->contractReference !== NULL) {
      $contractDocRef = $dom->createElementNS(self::NS_CAC, 'cac:ContractDocumentReference');
      $this->appendCbc($dom, $contractDocRef, 'ID', $model->contractReference);
      $root->appendChild($contractDocRef);
    }

    // BT-11: Project reference.
    if ($model->projectReference !== NULL) {
      $projectRef = $dom->createElementNS(self::NS_CAC, 'cac:ProjectReference');
      $this->appendCbc($dom, $projectRef, 'ID', $model->projectReference);
      $root->appendChild($projectRef);
    }

    // BG-4: Seller (BT-27..BT-43).
    $this->buildSellerParty($dom, $root, $model->seller);

    // BG-7: Buyer (BT-44..BT-63).
    $this->buildBuyerParty($dom, $root, $model->buyer);

    // BG-16: Payment means.
    if (!empty($model->paymentMeans)) {
      $this->buildPaymentMeans($dom, $root, $model->paymentMeans);
    }

    // BG-17: Payment terms.
    if (!empty($model->paymentTerms)) {
      $this->buildPaymentTerms($dom, $root, $model->paymentTerms);
    }

    // BG-23: Tax total.
    $this->buildTaxTotal($dom, $root, $model);

    // BG-22: Legal monetary total.
    $this->buildLegalMonetaryTotal($dom, $root, $model);

    // BG-25: Invoice lines.
    foreach ($model->lines as $index => $line) {
      $lineElement = $this->buildInvoiceLine($dom, $line, $index + 1, $isCreditNote);
      $root->appendChild($lineElement);
    }

    return $dom->saveXML();
  }

  /**
   * Builds the AccountingSupplierParty element (BG-4).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $root
   *   The root element to append to.
   * @param array $seller
   *   Seller data array.
   */
  protected function buildSellerParty(\DOMDocument $dom, \DOMElement $root, array $seller): void {
    $supplierParty = $dom->createElementNS(self::NS_CAC, 'cac:AccountingSupplierParty');
    $party = $dom->createElementNS(self::NS_CAC, 'cac:Party');

    // BT-34: Seller electronic address.
    if (!empty($seller['endpoint_id'])) {
      $endpointId = $dom->createElementNS(self::NS_CBC, 'cbc:EndpointID', $this->escapeXml($seller['endpoint_id']));
      $endpointId->setAttribute('schemeID', $seller['endpoint_scheme'] ?? '9920');
      $party->appendChild($endpointId);
    }

    // BT-28: Seller trading name.
    if (!empty($seller['trading_name'])) {
      $partyName = $dom->createElementNS(self::NS_CAC, 'cac:PartyName');
      $this->appendCbc($dom, $partyName, 'Name', $seller['trading_name']);
      $party->appendChild($partyName);
    }

    // BG-5: Seller postal address.
    if (!empty($seller['address'])) {
      $this->buildPostalAddress($dom, $party, $seller['address']);
    }

    // BT-31: Seller VAT identifier.
    if (!empty($seller['tax_id'])) {
      $taxScheme = $dom->createElementNS(self::NS_CAC, 'cac:PartyTaxScheme');
      $vatId = 'ES' . ltrim(strtoupper($seller['tax_id']), 'ES');
      $this->appendCbc($dom, $taxScheme, 'CompanyID', $vatId);
      $taxSchemeInner = $dom->createElementNS(self::NS_CAC, 'cac:TaxScheme');
      $this->appendCbc($dom, $taxSchemeInner, 'ID', 'VAT');
      $taxScheme->appendChild($taxSchemeInner);
      $party->appendChild($taxScheme);
    }

    // BT-27: Seller name (legal entity).
    $legalEntity = $dom->createElementNS(self::NS_CAC, 'cac:PartyLegalEntity');
    $this->appendCbc($dom, $legalEntity, 'RegistrationName', $seller['name'] ?? '');
    if (!empty($seller['tax_id'])) {
      $this->appendCbc($dom, $legalEntity, 'CompanyID', $seller['tax_id']);
    }
    $party->appendChild($legalEntity);

    // BG-6: Seller contact.
    if (!empty($seller['contact'])) {
      $this->buildContact($dom, $party, $seller['contact']);
    }

    $supplierParty->appendChild($party);
    $root->appendChild($supplierParty);
  }

  /**
   * Builds the AccountingCustomerParty element (BG-7).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $root
   *   The root element to append to.
   * @param array $buyer
   *   Buyer data array.
   */
  protected function buildBuyerParty(\DOMDocument $dom, \DOMElement $root, array $buyer): void {
    $customerParty = $dom->createElementNS(self::NS_CAC, 'cac:AccountingCustomerParty');
    $party = $dom->createElementNS(self::NS_CAC, 'cac:Party');

    // BT-49: Buyer electronic address.
    if (!empty($buyer['endpoint_id'])) {
      $endpointId = $dom->createElementNS(self::NS_CBC, 'cbc:EndpointID', $this->escapeXml($buyer['endpoint_id']));
      $endpointId->setAttribute('schemeID', $buyer['endpoint_scheme'] ?? '9920');
      $party->appendChild($endpointId);
    }

    // BT-45: Buyer trading name.
    if (!empty($buyer['trading_name'])) {
      $partyName = $dom->createElementNS(self::NS_CAC, 'cac:PartyName');
      $this->appendCbc($dom, $partyName, 'Name', $buyer['trading_name']);
      $party->appendChild($partyName);
    }

    // BG-8: Buyer postal address.
    if (!empty($buyer['address'])) {
      $this->buildPostalAddress($dom, $party, $buyer['address']);
    }

    // BT-48: Buyer VAT identifier.
    if (!empty($buyer['tax_id'])) {
      $taxScheme = $dom->createElementNS(self::NS_CAC, 'cac:PartyTaxScheme');
      $vatId = 'ES' . ltrim(strtoupper($buyer['tax_id']), 'ES');
      $this->appendCbc($dom, $taxScheme, 'CompanyID', $vatId);
      $taxSchemeInner = $dom->createElementNS(self::NS_CAC, 'cac:TaxScheme');
      $this->appendCbc($dom, $taxSchemeInner, 'ID', 'VAT');
      $taxScheme->appendChild($taxSchemeInner);
      $party->appendChild($taxScheme);
    }

    // BT-44: Buyer name (legal entity).
    $legalEntity = $dom->createElementNS(self::NS_CAC, 'cac:PartyLegalEntity');
    $this->appendCbc($dom, $legalEntity, 'RegistrationName', $buyer['name'] ?? '');
    if (!empty($buyer['tax_id'])) {
      $this->appendCbc($dom, $legalEntity, 'CompanyID', $buyer['tax_id']);
    }
    $party->appendChild($legalEntity);

    // BG-9: Buyer contact.
    if (!empty($buyer['contact'])) {
      $this->buildContact($dom, $party, $buyer['contact']);
    }

    $customerParty->appendChild($party);
    $root->appendChild($customerParty);
  }

  /**
   * Builds a postal address element (BG-5 / BG-8).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $parent
   *   The parent element to append to.
   * @param array $address
   *   Address data: street, city, postal_code, subdivision, country.
   */
  protected function buildPostalAddress(\DOMDocument $dom, \DOMElement $parent, array $address): void {
    $postalAddr = $dom->createElementNS(self::NS_CAC, 'cac:PostalAddress');

    if (!empty($address['street'])) {
      $this->appendCbc($dom, $postalAddr, 'StreetName', $address['street']);
    }
    if (!empty($address['additional_street'])) {
      $this->appendCbc($dom, $postalAddr, 'AdditionalStreetName', $address['additional_street']);
    }
    if (!empty($address['city'])) {
      $this->appendCbc($dom, $postalAddr, 'CityName', $address['city']);
    }
    if (!empty($address['postal_code'])) {
      $this->appendCbc($dom, $postalAddr, 'PostalZone', $address['postal_code']);
    }
    if (!empty($address['subdivision'])) {
      $this->appendCbc($dom, $postalAddr, 'CountrySubentity', $address['subdivision']);
    }

    $country = $dom->createElementNS(self::NS_CAC, 'cac:Country');
    $this->appendCbc($dom, $country, 'IdentificationCode', $address['country'] ?? 'ES');
    $postalAddr->appendChild($country);

    $parent->appendChild($postalAddr);
  }

  /**
   * Builds a contact element (BG-6 / BG-9).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $parent
   *   The parent element to append to.
   * @param array $contact
   *   Contact data: name, phone, email.
   */
  protected function buildContact(\DOMDocument $dom, \DOMElement $parent, array $contact): void {
    $contactEl = $dom->createElementNS(self::NS_CAC, 'cac:Contact');

    if (!empty($contact['name'])) {
      $this->appendCbc($dom, $contactEl, 'Name', $contact['name']);
    }
    if (!empty($contact['phone'])) {
      $this->appendCbc($dom, $contactEl, 'Telephone', $contact['phone']);
    }
    if (!empty($contact['email'])) {
      $this->appendCbc($dom, $contactEl, 'ElectronicMail', $contact['email']);
    }

    $parent->appendChild($contactEl);
  }

  /**
   * Builds the PaymentMeans element (BG-16).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $root
   *   The root element to append to.
   * @param array $paymentMeans
   *   Payment means data: code, iban, bic, payment_id.
   */
  protected function buildPaymentMeans(\DOMDocument $dom, \DOMElement $root, array $paymentMeans): void {
    $pmEl = $dom->createElementNS(self::NS_CAC, 'cac:PaymentMeans');

    // BT-81: Payment means type code (30=credit transfer, 58=SEPA).
    $code = $paymentMeans['code'] ?? '30';
    $this->appendCbc($dom, $pmEl, 'PaymentMeansCode', $code);

    // BT-83: Payment ID / remittance information.
    if (!empty($paymentMeans['payment_id'])) {
      $this->appendCbc($dom, $pmEl, 'PaymentID', $paymentMeans['payment_id']);
    }

    // BG-17: Credit transfer (BT-84: IBAN, BT-86: BIC).
    if (!empty($paymentMeans['iban'])) {
      $financialAccount = $dom->createElementNS(self::NS_CAC, 'cac:PayeeFinancialAccount');
      $this->appendCbc($dom, $financialAccount, 'ID', $paymentMeans['iban']);

      if (!empty($paymentMeans['bic'])) {
        $branch = $dom->createElementNS(self::NS_CAC, 'cac:FinancialInstitutionBranch');
        $this->appendCbc($dom, $branch, 'ID', $paymentMeans['bic']);
        $financialAccount->appendChild($branch);
      }

      $pmEl->appendChild($financialAccount);
    }

    $root->appendChild($pmEl);
  }

  /**
   * Builds the PaymentTerms element (BG-17).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $root
   *   The root element to append to.
   * @param array $paymentTerms
   *   Payment terms: note.
   */
  protected function buildPaymentTerms(\DOMDocument $dom, \DOMElement $root, array $paymentTerms): void {
    $ptEl = $dom->createElementNS(self::NS_CAC, 'cac:PaymentTerms');

    // BT-20: Payment terms text.
    $note = $paymentTerms['note'] ?? '';
    if (is_array($paymentTerms) && !isset($paymentTerms['note'])) {
      $note = json_encode($paymentTerms);
    }
    $this->appendCbc($dom, $ptEl, 'Note', $note);

    $root->appendChild($ptEl);
  }

  /**
   * Builds the TaxTotal element (BG-23).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $root
   *   The root element to append to.
   * @param \Drupal\jaraba_einvoice_b2b\Model\EN16931Model $model
   *   The EN 16931 model.
   */
  protected function buildTaxTotal(\DOMDocument $dom, \DOMElement $root, EN16931Model $model): void {
    $taxTotal = $dom->createElementNS(self::NS_CAC, 'cac:TaxTotal');

    // BT-110: Invoice total VAT amount.
    $taxAmountEl = $dom->createElementNS(self::NS_CBC, 'cbc:TaxAmount', $model->totalTax);
    $taxAmountEl->setAttribute('currencyID', $model->currencyCode);
    $taxTotal->appendChild($taxAmountEl);

    // BG-23: Tax subtotals.
    if (!empty($model->taxTotals)) {
      foreach ($model->taxTotals as $taxItem) {
        $subTotal = $dom->createElementNS(self::NS_CAC, 'cac:TaxSubtotal');

        // BT-116: Tax category taxable amount.
        $taxableEl = $dom->createElementNS(self::NS_CBC, 'cbc:TaxableAmount', $taxItem['taxable_amount'] ?? '0.00');
        $taxableEl->setAttribute('currencyID', $model->currencyCode);
        $subTotal->appendChild($taxableEl);

        // BT-117: Tax category tax amount.
        $taxAmtEl = $dom->createElementNS(self::NS_CBC, 'cbc:TaxAmount', $taxItem['tax_amount'] ?? '0.00');
        $taxAmtEl->setAttribute('currencyID', $model->currencyCode);
        $subTotal->appendChild($taxAmtEl);

        // BG-23: Tax category.
        $taxCategory = $dom->createElementNS(self::NS_CAC, 'cac:TaxCategory');
        $this->appendCbc($dom, $taxCategory, 'ID', $taxItem['category_id'] ?? 'S');
        $this->appendCbc($dom, $taxCategory, 'Percent', $taxItem['percent'] ?? '21.00');

        $taxScheme = $dom->createElementNS(self::NS_CAC, 'cac:TaxScheme');
        $this->appendCbc($dom, $taxScheme, 'ID', 'VAT');
        $taxCategory->appendChild($taxScheme);

        $subTotal->appendChild($taxCategory);
        $taxTotal->appendChild($subTotal);
      }
    }

    $root->appendChild($taxTotal);
  }

  /**
   * Builds the LegalMonetaryTotal element (BG-22).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $root
   *   The root element to append to.
   * @param \Drupal\jaraba_einvoice_b2b\Model\EN16931Model $model
   *   The EN 16931 model.
   */
  protected function buildLegalMonetaryTotal(\DOMDocument $dom, \DOMElement $root, EN16931Model $model): void {
    $lmt = $dom->createElementNS(self::NS_CAC, 'cac:LegalMonetaryTotal');
    $currency = $model->currencyCode;

    // BT-106: Sum of invoice line net amounts.
    $lineExtension = $dom->createElementNS(self::NS_CBC, 'cbc:LineExtensionAmount', $model->totalWithoutTax);
    $lineExtension->setAttribute('currencyID', $currency);
    $lmt->appendChild($lineExtension);

    // BT-109: Invoice total amount without VAT.
    $taxExclusive = $dom->createElementNS(self::NS_CBC, 'cbc:TaxExclusiveAmount', $model->totalWithoutTax);
    $taxExclusive->setAttribute('currencyID', $currency);
    $lmt->appendChild($taxExclusive);

    // BT-112: Invoice total amount with VAT.
    $taxInclusive = $dom->createElementNS(self::NS_CBC, 'cbc:TaxInclusiveAmount', $model->totalWithTax);
    $taxInclusive->setAttribute('currencyID', $currency);
    $lmt->appendChild($taxInclusive);

    // BT-115: Amount due for payment.
    $payable = $dom->createElementNS(self::NS_CBC, 'cbc:PayableAmount', $model->amountDue);
    $payable->setAttribute('currencyID', $currency);
    $lmt->appendChild($payable);

    $root->appendChild($lmt);
  }

  /**
   * Builds a single InvoiceLine / CreditNoteLine element (BG-25).
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param array $lineData
   *   Line item data: id, quantity, unit, net_amount, description,
   *   price, tax_percent, tax_category.
   * @param int $lineNumber
   *   Sequential line number.
   * @param bool $isCreditNote
   *   TRUE if building a credit note line.
   *
   * @return \DOMElement
   *   The built line element.
   */
  public function buildInvoiceLine(\DOMDocument $dom, array $lineData, int $lineNumber, bool $isCreditNote = FALSE): \DOMElement {
    $tag = $isCreditNote ? 'cac:CreditNoteLine' : 'cac:InvoiceLine';
    $qtyTag = $isCreditNote ? 'CreditedQuantity' : 'InvoicedQuantity';

    $line = $dom->createElementNS(self::NS_CAC, $tag);

    // BT-126: Invoice line identifier.
    $this->appendCbc($dom, $line, 'ID', (string) ($lineData['id'] ?? $lineNumber));

    // BT-129: Invoiced quantity.
    $quantity = $lineData['quantity'] ?? '1';
    $unit = $lineData['unit'] ?? 'C62';
    $qtyEl = $dom->createElementNS(self::NS_CBC, 'cbc:' . $qtyTag, (string) $quantity);
    $qtyEl->setAttribute('unitCode', $unit);
    $line->appendChild($qtyEl);

    // BT-131: Invoice line net amount.
    $netAmount = $lineData['net_amount'] ?? '0.00';
    $lineExtension = $dom->createElementNS(self::NS_CBC, 'cbc:LineExtensionAmount', $netAmount);
    $lineExtension->setAttribute('currencyID', $lineData['currency'] ?? 'EUR');
    $line->appendChild($lineExtension);

    // BG-30: Line tax information.
    $itemTax = $dom->createElementNS(self::NS_CAC, 'cac:Item');

    // BT-153: Item name.
    if (!empty($lineData['description'])) {
      $this->appendCbc($dom, $itemTax, 'Name', $lineData['description']);
    }

    // BG-30: Tax category.
    $classifiedTax = $dom->createElementNS(self::NS_CAC, 'cac:ClassifiedTaxCategory');
    $this->appendCbc($dom, $classifiedTax, 'ID', $lineData['tax_category'] ?? 'S');
    $this->appendCbc($dom, $classifiedTax, 'Percent', (string) ($lineData['tax_percent'] ?? '21.00'));
    $taxScheme = $dom->createElementNS(self::NS_CAC, 'cac:TaxScheme');
    $this->appendCbc($dom, $taxScheme, 'ID', 'VAT');
    $classifiedTax->appendChild($taxScheme);
    $itemTax->appendChild($classifiedTax);

    $line->appendChild($itemTax);

    // BG-29: Price details.
    $priceEl = $dom->createElementNS(self::NS_CAC, 'cac:Price');
    $priceAmount = $dom->createElementNS(self::NS_CBC, 'cbc:PriceAmount', (string) ($lineData['price'] ?? $netAmount));
    $priceAmount->setAttribute('currencyID', $lineData['currency'] ?? 'EUR');
    $priceEl->appendChild($priceAmount);
    $line->appendChild($priceEl);

    return $line;
  }

  /**
   * Parses a UBL 2.1 XML string into an EN16931Model.
   *
   * Used for inbound invoice reception.
   *
   * @param string $xml
   *   The UBL XML string to parse.
   *
   * @return \Drupal\jaraba_einvoice_b2b\Model\EN16931Model
   *   The parsed semantic model.
   *
   * @throws \RuntimeException
   *   If the XML cannot be parsed.
   */
  public function parseUblToModel(string $xml): EN16931Model {
    $dom = new \DOMDocument();
    if (!$dom->loadXML($xml)) {
      throw new \RuntimeException('Invalid UBL XML: cannot parse document.');
    }

    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('inv', self::NS_UBL_INVOICE);
    $xpath->registerNamespace('cn', self::NS_UBL_CREDIT_NOTE);
    $xpath->registerNamespace('cac', self::NS_CAC);
    $xpath->registerNamespace('cbc', self::NS_CBC);

    $isCreditNote = $dom->documentElement->localName === 'CreditNote';
    $prefix = $isCreditNote ? 'cn' : 'inv';

    $invoiceNumber = $this->xpathValue($xpath, "//{$prefix}:*/cbc:ID");
    $issueDate = $this->xpathValue($xpath, "//{$prefix}:*/cbc:IssueDate");
    $dueDate = $this->xpathValue($xpath, "//{$prefix}:*/cbc:DueDate");
    $typeCode = $this->xpathValue($xpath, "//{$prefix}:*/cbc:InvoiceTypeCode");
    $currency = $this->xpathValue($xpath, "//{$prefix}:*/cbc:DocumentCurrencyCode") ?: 'EUR';

    // Seller.
    $sellerName = $this->xpathValue($xpath, '//cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName');
    $sellerTaxId = $this->xpathValue($xpath, '//cac:AccountingSupplierParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID');

    // Buyer.
    $buyerName = $this->xpathValue($xpath, '//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName');
    $buyerTaxId = $this->xpathValue($xpath, '//cac:AccountingCustomerParty/cac:Party/cac:PartyTaxScheme/cbc:CompanyID');

    // Totals.
    $totalWithoutTax = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount') ?: '0.00';
    $totalTax = $this->xpathValue($xpath, '//cac:TaxTotal/cbc:TaxAmount') ?: '0.00';
    $totalWithTax = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount') ?: '0.00';
    $amountDue = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:PayableAmount') ?: '0.00';

    // Lines.
    $lineTag = $isCreditNote ? 'cac:CreditNoteLine' : 'cac:InvoiceLine';
    $lineNodes = $xpath->query("//{$lineTag}");
    $lines = [];
    if ($lineNodes !== FALSE) {
      foreach ($lineNodes as $lineNode) {
        $lines[] = [
          'id' => $this->xpathValueFromNode($xpath, 'cbc:ID', $lineNode),
          'description' => $this->xpathValueFromNode($xpath, 'cac:Item/cbc:Name', $lineNode),
          'net_amount' => $this->xpathValueFromNode($xpath, 'cbc:LineExtensionAmount', $lineNode) ?: '0.00',
          'tax_percent' => $this->xpathValueFromNode($xpath, 'cac:Item/cac:ClassifiedTaxCategory/cbc:Percent', $lineNode),
          'tax_category' => $this->xpathValueFromNode($xpath, 'cac:Item/cac:ClassifiedTaxCategory/cbc:ID', $lineNode) ?: 'S',
        ];
      }
    }

    // Tax totals.
    $taxSubtotals = $xpath->query('//cac:TaxTotal/cac:TaxSubtotal');
    $taxTotals = [];
    if ($taxSubtotals !== FALSE) {
      foreach ($taxSubtotals as $subtotal) {
        $taxTotals[] = [
          'taxable_amount' => $this->xpathValueFromNode($xpath, 'cbc:TaxableAmount', $subtotal) ?: '0.00',
          'tax_amount' => $this->xpathValueFromNode($xpath, 'cbc:TaxAmount', $subtotal) ?: '0.00',
          'category_id' => $this->xpathValueFromNode($xpath, 'cac:TaxCategory/cbc:ID', $subtotal) ?: 'S',
          'percent' => $this->xpathValueFromNode($xpath, 'cac:TaxCategory/cbc:Percent', $subtotal) ?: '21.00',
        ];
      }
    }

    // Payment means.
    $paymentMeansCode = $this->xpathValue($xpath, '//cac:PaymentMeans/cbc:PaymentMeansCode');
    $iban = $this->xpathValue($xpath, '//cac:PaymentMeans/cac:PayeeFinancialAccount/cbc:ID');
    $paymentMeans = $paymentMeansCode || $iban ? [
      'code' => $paymentMeansCode ?? '30',
      'iban' => $iban,
    ] : [];

    return new EN16931Model(
      invoiceNumber: $invoiceNumber ?? '',
      issueDate: $issueDate ?? '',
      invoiceTypeCode: (int) ($typeCode ?: ($isCreditNote ? 381 : 380)),
      currencyCode: $currency,
      taxPointDate: $this->xpathValue($xpath, "//{$prefix}:*/cbc:TaxPointDate"),
      dueDate: $dueDate,
      buyerReference: $this->xpathValue($xpath, "//{$prefix}:*/cbc:BuyerReference"),
      projectReference: $this->xpathValue($xpath, '//cac:ProjectReference/cbc:ID'),
      contractReference: $this->xpathValue($xpath, '//cac:ContractDocumentReference/cbc:ID'),
      precedingInvoiceReference: $this->xpathValue($xpath, '//cac:BillingReference/cac:InvoiceDocumentReference/cbc:ID'),
      seller: [
        'name' => $sellerName ?? '',
        'tax_id' => $sellerTaxId ?? '',
      ],
      buyer: [
        'name' => $buyerName ?? '',
        'tax_id' => $buyerTaxId ?? '',
      ],
      paymentMeans: $paymentMeans,
      paymentTerms: [],
      lines: $lines,
      taxTotals: $taxTotals,
      totalWithoutTax: $totalWithoutTax,
      totalTax: $totalTax,
      totalWithTax: $totalWithTax,
      amountDue: $amountDue,
      note: $this->xpathValue($xpath, "//{$prefix}:*/cbc:Note"),
    );
  }

  /**
   * Helper to get a single XPath value.
   *
   * @param \DOMXPath $xpath
   *   The XPath object.
   * @param string $expression
   *   The XPath expression.
   *
   * @return string|null
   *   The text content or NULL.
   */
  protected function xpathValue(\DOMXPath $xpath, string $expression): ?string {
    $nodes = $xpath->query($expression);
    if ($nodes !== FALSE && $nodes->length > 0) {
      return $nodes->item(0)->textContent;
    }
    return NULL;
  }

  /**
   * Helper to get a single XPath value relative to a context node.
   *
   * @param \DOMXPath $xpath
   *   The XPath object.
   * @param string $expression
   *   The XPath expression (relative).
   * @param \DOMNode $contextNode
   *   The context node.
   *
   * @return string|null
   *   The text content or NULL.
   */
  protected function xpathValueFromNode(\DOMXPath $xpath, string $expression, \DOMNode $contextNode): ?string {
    $nodes = $xpath->query($expression, $contextNode);
    if ($nodes !== FALSE && $nodes->length > 0) {
      return $nodes->item(0)->textContent;
    }
    return NULL;
  }

  /**
   * Appends a cbc: element to a parent.
   *
   * @param \DOMDocument $dom
   *   The DOM document.
   * @param \DOMElement $parent
   *   The parent element.
   * @param string $localName
   *   The local name (without namespace prefix).
   * @param string $value
   *   The text value.
   */
  protected function appendCbc(\DOMDocument $dom, \DOMElement $parent, string $localName, string $value): void {
    $el = $dom->createElementNS(self::NS_CBC, 'cbc:' . $localName, $this->escapeXml($value));
    $parent->appendChild($el);
  }

  /**
   * Escapes text for safe use in XML element content.
   *
   * @param string $text
   *   The raw text.
   *
   * @return string
   *   The escaped text.
   */
  protected function escapeXml(string $text): string {
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
  }

}
