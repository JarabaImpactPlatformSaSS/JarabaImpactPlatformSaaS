<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Service;

use Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult;

/**
 * 4-layer validation service for e-invoice XML.
 *
 * Validation layers:
 *   1. XSD schema: Validates against UBL-Invoice-2.1.xsd.
 *   2. Schematron EN 16931: Semantic business rules (CEN/TS 16931-3-2).
 *   3. Spanish CIUS: Country-specific rules (NIF, tax codes, IRPF).
 *   4. Business rules: Amounts balance, dates valid, mandatory fields.
 *
 * Spec: Doc 181, Section 3.5.
 * Plan: FASE 10, entregable F10-3.
 */
class EInvoiceValidationService {

  /**
   * Module path for schema files.
   */
  protected const MODULE_PATH = 'modules/custom/jaraba_einvoice_b2b';

  /**
   * Validates XML through all 4 layers.
   *
   * @param string $xml
   *   The XML content to validate.
   * @param string $format
   *   The format: 'ubl_2.1' or 'facturae_3.2.2'.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult
   *   The aggregated validation result.
   */
  public function validate(string $xml, string $format = 'ubl_2.1'): ValidationResult {
    // Layer 1: XSD schema.
    $xsdResult = $this->validateXsd($xml, $format);
    if (!$xsdResult->valid) {
      return $xsdResult;
    }

    // Layer 2: Schematron EN 16931 (UBL only).
    $schematronResult = ValidationResult::valid('schematron');
    if ($format === 'ubl_2.1') {
      $schematronResult = $this->validateSchematron($xml);
    }

    // Layer 3: Spanish CIUS rules.
    $ciusResult = $this->validateSpanishRules($xml, $format);

    // Layer 4: Business rules.
    $businessResult = $this->validateBusinessRules($xml, $format);

    // Merge all results.
    return $xsdResult
      ->merge($schematronResult)
      ->merge($ciusResult)
      ->merge($businessResult);
  }

  /**
   * Layer 1: XSD schema validation.
   *
   * @param string $xml
   *   The XML content.
   * @param string $format
   *   The format identifier.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult
   */
  public function validateXsd(string $xml, string $format = 'ubl_2.1'): ValidationResult {
    $dom = new \DOMDocument();
    $prevUseErrors = libxml_use_internal_errors(TRUE);

    if (!$dom->loadXML($xml)) {
      $errors = $this->collectLibxmlErrors();
      libxml_use_internal_errors($prevUseErrors);
      return ValidationResult::invalid($errors, 'xsd');
    }

    $xsdPath = $this->resolveXsdPath($format, $dom);
    if ($xsdPath !== NULL && file_exists($xsdPath)) {
      if (!$dom->schemaValidate($xsdPath)) {
        $errors = $this->collectLibxmlErrors();
        libxml_use_internal_errors($prevUseErrors);
        return ValidationResult::invalid($errors, 'xsd');
      }
    }

    libxml_use_internal_errors($prevUseErrors);
    return ValidationResult::valid('xsd');
  }

  /**
   * Layer 2: Schematron EN 16931 validation (programmatic).
   *
   * Since PHP has no native Schematron processor, this implements
   * the critical EN 16931 business rules programmatically.
   *
   * @param string $xml
   *   The UBL XML content.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult
   */
  public function validateSchematron(string $xml): ValidationResult {
    $dom = new \DOMDocument();
    if (!@$dom->loadXML($xml)) {
      return ValidationResult::invalid(['Cannot parse XML for Schematron validation.'], 'schematron');
    }

    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $errors = [];
    $warnings = [];

    // BR-01: Invoice number must exist.
    if (!$this->xpathExists($xpath, '//cbc:ID')) {
      $errors[] = 'BR-01: An Invoice shall have an Invoice number (BT-1).';
    }

    // BR-02: Issue date must exist.
    if (!$this->xpathExists($xpath, '//cbc:IssueDate')) {
      $errors[] = 'BR-02: An Invoice shall have an Invoice issue date (BT-2).';
    }

    // BR-04: Invoice type code must exist.
    if (!$this->xpathExists($xpath, '//cbc:InvoiceTypeCode')) {
      $errors[] = 'BR-04: An Invoice shall have an Invoice type code (BT-3).';
    }

    // BR-05: Currency code must exist.
    if (!$this->xpathExists($xpath, '//cbc:DocumentCurrencyCode')) {
      $errors[] = 'BR-05: An Invoice shall have an Invoice currency code (BT-5).';
    }

    // BR-06: Seller name must exist.
    if (!$this->xpathExists($xpath, '//cac:AccountingSupplierParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName')) {
      $errors[] = 'BR-06: An Invoice shall contain the Seller name (BT-27).';
    }

    // BR-07: Buyer name must exist.
    if (!$this->xpathExists($xpath, '//cac:AccountingCustomerParty/cac:Party/cac:PartyLegalEntity/cbc:RegistrationName')) {
      $errors[] = 'BR-07: An Invoice shall contain the Buyer name (BT-44).';
    }

    // BR-CO-10: Verify total amounts sum correctly.
    $lineExtension = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:LineExtensionAmount');
    $taxExclusive = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount');
    if ($lineExtension !== NULL && $taxExclusive !== NULL) {
      if (bccomp($lineExtension, $taxExclusive, 2) !== 0) {
        $warnings[] = 'BR-CO-10: LineExtensionAmount and TaxExclusiveAmount should match when no allowances/charges.';
      }
    }

    // BR-CO-15: Verify tax total matches sum of subtotals.
    $taxTotal = $this->xpathValue($xpath, '//cac:TaxTotal/cbc:TaxAmount');
    $subtotalNodes = $xpath->query('//cac:TaxTotal/cac:TaxSubtotal/cbc:TaxAmount');
    if ($taxTotal !== NULL && $subtotalNodes !== FALSE && $subtotalNodes->length > 0) {
      $subtotalSum = '0.00';
      foreach ($subtotalNodes as $node) {
        $subtotalSum = bcadd($subtotalSum, $node->textContent, 2);
      }
      if (bccomp($taxTotal, $subtotalSum, 2) !== 0) {
        $errors[] = 'BR-CO-15: Invoice total VAT amount shall equal the sum of tax subtotal amounts.';
      }
    }

    // BR-16: At least one invoice line.
    $lineNodes = $xpath->query('//cac:InvoiceLine|//cac:CreditNoteLine');
    if ($lineNodes === FALSE || $lineNodes->length === 0) {
      $errors[] = 'BR-16: An Invoice shall have at least one Invoice line (BG-25).';
    }

    if (!empty($errors)) {
      return ValidationResult::invalid($errors, 'schematron', $warnings);
    }

    return new ValidationResult(valid: TRUE, errors: [], warnings: $warnings, layer: 'schematron');
  }

  /**
   * Layer 3: Spanish CIUS rules.
   *
   * @param string $xml
   *   The XML content.
   * @param string $format
   *   The format identifier.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult
   */
  public function validateSpanishRules(string $xml, string $format = 'ubl_2.1'): ValidationResult {
    $dom = new \DOMDocument();
    if (!@$dom->loadXML($xml)) {
      return ValidationResult::invalid(['Cannot parse XML for Spanish rules.'], 'cius');
    }

    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $errors = [];
    $warnings = [];

    // ES-01: Seller VAT identifier must start with ES.
    $sellerVat = $this->xpathValue($xpath, '//cac:AccountingSupplierParty//cac:PartyTaxScheme/cbc:CompanyID');
    if ($sellerVat !== NULL) {
      if (!preg_match('/^ES[A-Z0-9]{8,9}$/i', $sellerVat)) {
        $errors[] = 'ES-01: Seller VAT identifier must be a valid Spanish NIF/CIF prefixed with ES.';
      }
      else {
        $nif = substr($sellerVat, 2);
        if (!$this->isValidNif($nif)) {
          $errors[] = 'ES-01: Seller NIF/CIF fails checksum validation.';
        }
      }
    }

    // ES-02: Buyer VAT identifier format.
    $buyerVat = $this->xpathValue($xpath, '//cac:AccountingCustomerParty//cac:PartyTaxScheme/cbc:CompanyID');
    if ($buyerVat !== NULL && preg_match('/^ES/i', $buyerVat)) {
      $nif = substr($buyerVat, 2);
      if (!$this->isValidNif($nif)) {
        $warnings[] = 'ES-02: Buyer NIF/CIF fails checksum validation.';
      }
    }

    // ES-03: Currency should be EUR for domestic invoices.
    $currency = $this->xpathValue($xpath, '//cbc:DocumentCurrencyCode');
    if ($currency !== NULL && $currency !== 'EUR') {
      $warnings[] = 'ES-03: Non-EUR currency used. Spanish domestic invoices typically use EUR.';
    }

    if (!empty($errors)) {
      return ValidationResult::invalid($errors, 'cius', $warnings);
    }

    return new ValidationResult(valid: TRUE, errors: [], warnings: $warnings, layer: 'cius');
  }

  /**
   * Layer 4: Business rules validation.
   *
   * @param string $xml
   *   The XML content.
   * @param string $format
   *   The format identifier.
   *
   * @return \Drupal\jaraba_einvoice_b2b\ValueObject\ValidationResult
   */
  public function validateBusinessRules(string $xml, string $format = 'ubl_2.1'): ValidationResult {
    $dom = new \DOMDocument();
    if (!@$dom->loadXML($xml)) {
      return ValidationResult::invalid(['Cannot parse XML for business rules.'], 'business');
    }

    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

    $errors = [];

    // BIZ-01: Total with tax must equal total without tax + tax.
    $taxExclusive = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:TaxExclusiveAmount');
    $taxAmount = $this->xpathValue($xpath, '//cac:TaxTotal/cbc:TaxAmount');
    $taxInclusive = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:TaxInclusiveAmount');

    if ($taxExclusive !== NULL && $taxAmount !== NULL && $taxInclusive !== NULL) {
      $expected = bcadd($taxExclusive, $taxAmount, 2);
      if (bccomp($expected, $taxInclusive, 2) !== 0) {
        $errors[] = "BIZ-01: Total with tax ({$taxInclusive}) does not equal base ({$taxExclusive}) + tax ({$taxAmount}) = {$expected}.";
      }
    }

    // BIZ-02: Issue date must be valid and not in the far future.
    $issueDate = $this->xpathValue($xpath, '//cbc:IssueDate');
    if ($issueDate !== NULL) {
      try {
        $date = new \DateTimeImmutable($issueDate);
        $maxFuture = new \DateTimeImmutable('+30 days');
        if ($date > $maxFuture) {
          $errors[] = 'BIZ-02: Issue date is more than 30 days in the future.';
        }
      }
      catch (\Exception) {
        $errors[] = 'BIZ-02: Issue date is not a valid date format.';
      }
    }

    // BIZ-03: Payable amount must not be negative.
    $payable = $this->xpathValue($xpath, '//cac:LegalMonetaryTotal/cbc:PayableAmount');
    if ($payable !== NULL && bccomp($payable, '0', 2) < 0) {
      $errors[] = 'BIZ-03: Payable amount cannot be negative.';
    }

    if (!empty($errors)) {
      return ValidationResult::invalid($errors, 'business');
    }

    return ValidationResult::valid('business');
  }

  /**
   * Validates a Spanish NIF/CIF.
   *
   * @param string $nif
   *   The NIF/CIF without country prefix.
   *
   * @return bool
   *   TRUE if valid.
   */
  public function isValidNif(string $nif): bool {
    $nif = strtoupper(trim($nif));
    if (strlen($nif) < 8 || strlen($nif) > 9) {
      return FALSE;
    }

    // DNI: 8 digits + letter.
    if (preg_match('/^[0-9]{8}[A-Z]$/', $nif)) {
      $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
      $expected = $letters[(int) substr($nif, 0, 8) % 23];
      return $nif[8] === $expected;
    }

    // NIE: X/Y/Z + 7 digits + letter.
    if (preg_match('/^[XYZ][0-9]{7}[A-Z]$/', $nif)) {
      $prefix = ['X' => '0', 'Y' => '1', 'Z' => '2'];
      $numericNif = $prefix[$nif[0]] . substr($nif, 1, 7);
      $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
      $expected = $letters[(int) $numericNif % 23];
      return $nif[8] === $expected;
    }

    // CIF: Letter + 7 digits + control.
    if (preg_match('/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/', $nif)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Resolves the XSD path for a format.
   */
  protected function resolveXsdPath(string $format, \DOMDocument $dom): ?string {
    $basePath = DRUPAL_ROOT . '/' . self::MODULE_PATH;

    if ($format === 'ubl_2.1') {
      $rootTag = $dom->documentElement->localName ?? '';
      if ($rootTag === 'CreditNote') {
        return $basePath . '/xsd/UBL-CreditNote-2.1.xsd';
      }
      return $basePath . '/xsd/UBL-Invoice-2.1.xsd';
    }

    return NULL;
  }

  /**
   * Collects libxml errors as string array.
   */
  protected function collectLibxmlErrors(): array {
    $errors = [];
    foreach (libxml_get_errors() as $error) {
      $errors[] = sprintf('Line %d: %s', $error->line, trim($error->message));
    }
    libxml_clear_errors();
    return $errors;
  }

  /**
   * Checks if an XPath expression has matches.
   */
  protected function xpathExists(\DOMXPath $xpath, string $expression): bool {
    $nodes = $xpath->query($expression);
    return $nodes !== FALSE && $nodes->length > 0;
  }

  /**
   * Gets the text value from an XPath expression.
   */
  protected function xpathValue(\DOMXPath $xpath, string $expression): ?string {
    $nodes = $xpath->query($expression);
    if ($nodes !== FALSE && $nodes->length > 0) {
      return $nodes->item(0)->textContent;
    }
    return NULL;
  }

}
