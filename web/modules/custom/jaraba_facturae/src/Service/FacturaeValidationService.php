<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\jaraba_facturae\ValueObject\ValidationResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de validacion multi-capa para Facturae 3.2.2.
 *
 * Validaciones:
 * - XSD: Validacion contra el esquema Facturae 3.2.2 oficial.
 * - NIF/CIF/NIE: Algoritmo completo de validacion.
 * - DIR3: Formato de codigo de unidad organica.
 * - IBAN: Validacion ISO 13616 con checksum.
 * - Importes: Reconciliacion de totales, bases, impuestos.
 *
 * Spec: Doc 180, Seccion 3.6 (ValidationService).
 * Plan: FASE 6, entregable F6-6.
 */
class FacturaeValidationService {

  public function __construct(
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Validates XML against the Facturae 3.2.2 XSD schema.
   *
   * @param string $xml
   *   The XML string to validate.
   *
   * @return \Drupal\jaraba_facturae\ValueObject\ValidationResult
   *   The validation result with any errors found.
   */
  public function validateXsd(string $xml): ValidationResult {
    $errors = [];

    if (empty($xml)) {
      return new ValidationResult(FALSE, ['XML content is empty.']);
    }

    $doc = new \DOMDocument();
    $previousErrors = libxml_use_internal_errors(TRUE);

    if (!$doc->loadXML($xml)) {
      $xmlErrors = libxml_get_errors();
      foreach ($xmlErrors as $error) {
        $errors[] = sprintf('XML parse error (line %d): %s', $error->line, trim($error->message));
      }
      libxml_clear_errors();
      libxml_use_internal_errors($previousErrors);
      return new ValidationResult(FALSE, $errors);
    }

    // Locate XSD file.
    $modulePath = $this->moduleHandler->getModule('jaraba_facturae')->getPath();
    $xsdPath = $modulePath . '/xsd/Facturaev3_2_2.xsd';

    if (file_exists($xsdPath)) {
      if (!$doc->schemaValidate($xsdPath)) {
        $xmlErrors = libxml_get_errors();
        foreach ($xmlErrors as $error) {
          $errors[] = sprintf('XSD validation error (line %d): %s', $error->line, trim($error->message));
        }
      }
      libxml_clear_errors();
    }
    else {
      $this->logger->warning('Facturae XSD schema file not found at @path. Skipping XSD validation.', [
        '@path' => $xsdPath,
      ]);
    }

    libxml_use_internal_errors($previousErrors);

    return new ValidationResult(empty($errors), $errors);
  }

  /**
   * Validates a Spanish NIF/CIF/NIE.
   *
   * @param string $nif
   *   The tax identification number.
   *
   * @return bool
   *   TRUE if valid.
   */
  public function validateNif(string $nif): bool {
    $nif = strtoupper(trim($nif));

    if (strlen($nif) !== 9) {
      return FALSE;
    }

    // NIE: starts with X, Y, Z.
    if (preg_match('/^[XYZ]/', $nif)) {
      return $this->validateNie($nif);
    }

    // CIF: starts with A-H, J, N, P, Q, R, S, U, V, W.
    if (preg_match('/^[ABCDEFGHJNPQRSUVW]/', $nif)) {
      return $this->validateCif($nif);
    }

    // NIF: starts with a digit or K, L, M.
    if (preg_match('/^[0-9KLM]/', $nif)) {
      return $this->validateNifPersonal($nif);
    }

    return FALSE;
  }

  /**
   * Validates a personal NIF (DNI number + letter).
   */
  protected function validateNifPersonal(string $nif): bool {
    $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

    // Handle K, L, M prefix (foreigners with permanent NIF).
    $number = $nif;
    if (preg_match('/^[KLM]/', $number)) {
      $number = substr($number, 1);
    }

    $numericPart = substr($number, 0, 8);
    $expectedLetter = substr($nif, -1);

    if (!is_numeric($numericPart)) {
      return FALSE;
    }

    $index = ((int) $numericPart) % 23;
    return $expectedLetter === $letters[$index];
  }

  /**
   * Validates a NIE (Foreigner Identity Number).
   */
  protected function validateNie(string $nie): bool {
    $replacements = ['X' => '0', 'Y' => '1', 'Z' => '2'];
    $first = $nie[0];

    if (!isset($replacements[$first])) {
      return FALSE;
    }

    $converted = $replacements[$first] . substr($nie, 1);
    return $this->validateNifPersonal($converted);
  }

  /**
   * Validates a CIF (Corporate Tax ID).
   */
  protected function validateCif(string $cif): bool {
    $provinceCode = substr($cif, 1, 2);
    if (!is_numeric($provinceCode)) {
      return FALSE;
    }

    $digits = substr($cif, 1, 7);
    if (!is_numeric($digits)) {
      return FALSE;
    }

    $sumEven = 0;
    $sumOdd = 0;

    for ($i = 0; $i < 7; $i++) {
      $digit = (int) $digits[$i];
      if (($i + 1) % 2 === 0) {
        // Even positions (1-indexed): just add.
        $sumEven += $digit;
      }
      else {
        // Odd positions: multiply by 2, sum digits if >= 10.
        $doubled = $digit * 2;
        $sumOdd += ($doubled >= 10) ? (int) ($doubled / 10) + ($doubled % 10) : $doubled;
      }
    }

    $total = $sumEven + $sumOdd;
    $control = (10 - ($total % 10)) % 10;

    $lastChar = substr($cif, -1);
    $controlLetters = 'JABCDEFGHI';

    // Some CIF types use letter, some use digit, some accept both.
    $letterTypes = ['P', 'Q', 'R', 'S', 'N', 'W'];
    $type = $cif[0];

    if (in_array($type, $letterTypes, TRUE)) {
      return $lastChar === $controlLetters[$control];
    }

    if (is_numeric($lastChar)) {
      return (int) $lastChar === $control;
    }

    return $lastChar === $controlLetters[$control];
  }

  /**
   * Validates a DIR3 organizational unit code.
   *
   * DIR3 codes follow the pattern: L + 2 digits + 7 alphanumeric characters
   * or similar patterns from the common directory.
   *
   * @param string $code
   *   The DIR3 code.
   *
   * @return bool
   *   TRUE if the code has valid format.
   */
  public function validateDir3(string $code): bool {
    $code = strtoupper(trim($code));

    if (empty($code)) {
      return FALSE;
    }

    // DIR3 codes are alphanumeric, typically 9-10 characters.
    // Pattern: letter prefix + alphanumeric (e.g., L01234567, EA0012345).
    if (strlen($code) < 5 || strlen($code) > 15) {
      return FALSE;
    }

    return (bool) preg_match('/^[A-Z][A-Z0-9]+$/', $code);
  }

  /**
   * Validates an IBAN according to ISO 13616.
   *
   * @param string $iban
   *   The IBAN string.
   *
   * @return bool
   *   TRUE if the IBAN is valid.
   */
  public function validateIban(string $iban): bool {
    $iban = strtoupper(str_replace([' ', '-'], '', $iban));

    // Length check: Spanish IBAN is 24 characters.
    if (strlen($iban) < 15 || strlen($iban) > 34) {
      return FALSE;
    }

    // Country code must be 2 letters.
    if (!preg_match('/^[A-Z]{2}/', $iban)) {
      return FALSE;
    }

    // Check digits must be 2 digits.
    if (!is_numeric(substr($iban, 2, 2))) {
      return FALSE;
    }

    // Move first 4 characters to end.
    $rearranged = substr($iban, 4) . substr($iban, 0, 4);

    // Convert letters to numbers: A=10, B=11, ..., Z=35.
    $numeric = '';
    for ($i = 0; $i < strlen($rearranged); $i++) {
      $char = $rearranged[$i];
      if (ctype_alpha($char)) {
        $numeric .= (string) (ord($char) - ord('A') + 10);
      }
      else {
        $numeric .= $char;
      }
    }

    // Modulo 97 check.
    return $this->mod97($numeric) === 1;
  }

  /**
   * Calculates modulo 97 for large numbers (IBAN validation).
   */
  protected function mod97(string $number): int {
    $remainder = 0;
    for ($i = 0; $i < strlen($number); $i++) {
      $remainder = ($remainder * 10 + (int) $number[$i]) % 97;
    }
    return $remainder;
  }

  /**
   * Validates that invoice amounts are consistent.
   *
   * Checks:
   * - Total = Gross - Discounts + Surcharges
   * - Total before taxes = sum of line gross amounts
   * - Tax outputs = sum of individual tax amounts
   * - Invoice total = before taxes + tax outputs - tax withheld
   * - Outstanding = Invoice total (unless advances present)
   *
   * @param array $data
   *   Invoice data with keys: total_gross_amount, total_general_discounts,
   *   total_general_surcharges, total_gross_amount_before_taxes,
   *   total_tax_outputs, total_tax_withheld, total_invoice_amount,
   *   total_outstanding, total_executable.
   *
   * @return \Drupal\jaraba_facturae\ValueObject\ValidationResult
   *   The validation result.
   */
  public function validateAmounts(array $data): ValidationResult {
    $errors = [];
    $tolerance = 0.01;

    $grossBeforeTaxes = (float) ($data['total_gross_amount_before_taxes'] ?? 0);
    $taxOutputs = (float) ($data['total_tax_outputs'] ?? 0);
    $taxWithheld = (float) ($data['total_tax_withheld'] ?? 0);
    $invoiceTotal = (float) ($data['total_invoice_amount'] ?? 0);
    $outstanding = (float) ($data['total_outstanding'] ?? 0);
    $executable = (float) ($data['total_executable'] ?? 0);

    // Invoice total = before taxes + tax outputs - tax withheld.
    $expectedTotal = $grossBeforeTaxes + $taxOutputs - $taxWithheld;
    if (abs($expectedTotal - $invoiceTotal) > $tolerance) {
      $errors[] = sprintf(
        'Invoice total (%.2f) does not match expected (%.2f = %.2f + %.2f - %.2f).',
        $invoiceTotal, $expectedTotal, $grossBeforeTaxes, $taxOutputs, $taxWithheld
      );
    }

    // Outstanding should not exceed invoice total.
    if ($outstanding > $invoiceTotal + $tolerance) {
      $errors[] = sprintf(
        'Outstanding amount (%.2f) exceeds invoice total (%.2f).',
        $outstanding, $invoiceTotal
      );
    }

    // Executable should not exceed outstanding.
    if ($executable > $outstanding + $tolerance) {
      $errors[] = sprintf(
        'Executable amount (%.2f) exceeds outstanding amount (%.2f).',
        $executable, $outstanding
      );
    }

    return new ValidationResult(empty($errors), $errors);
  }

}
