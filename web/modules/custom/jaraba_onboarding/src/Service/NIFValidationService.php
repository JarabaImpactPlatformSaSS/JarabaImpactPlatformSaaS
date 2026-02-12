<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

/**
 * Service for validating Spanish tax identification numbers (NIF/NIE/CIF).
 *
 * Implements the official algorithms defined by the Agencia Tributaria for:
 * - NIF (Numero de Identificacion Fiscal): 8 digits + control letter.
 * - NIE (Numero de Identidad de Extranjero): X/Y/Z + 7 digits + control letter.
 * - CIF (Codigo de Identificacion Fiscal): letter + 7 digits + control char.
 *
 * @see https://www.interior.gob.es/opencms/ca/servicios-al-ciudadano/tramites-y-gestiones/dni/calculo-del-digito-de-control-del-nif-nie/
 */
class NIFValidationService {

  /**
   * NIF control letter lookup table (modulo 23).
   *
   * Position 0 = 'T', position 1 = 'R', ..., position 22 = 'E'.
   */
  private const NIF_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

  /**
   * NIE prefix replacements for numeric calculation.
   *
   * X -> 0, Y -> 1, Z -> 2.
   */
  private const NIE_PREFIX_MAP = [
    'X' => '0',
    'Y' => '1',
    'Z' => '2',
  ];

  /**
   * CIF entity type prefixes.
   *
   * A = Sociedades anonimas
   * B = Sociedades de responsabilidad limitada
   * C = Sociedades colectivas
   * D = Sociedades comanditarias
   * E = Comunidades de bienes
   * F = Sociedades cooperativas
   * G = Asociaciones y fundaciones
   * H = Comunidades de propietarios
   * J = Sociedades civiles
   * K = Espanoles menores de 14 anos (NIF-K)
   * L = Espanoles residentes en el extranjero (NIF-L)
   * M = Extranjeros sin NIE (NIF-M)
   * N = Entidades extranjeras
   * P = Corporaciones locales
   * Q = Organismos autonomos
   * R = Congregaciones e instituciones religiosas
   * S = Organos de la Administracion del Estado
   * U = Uniones Temporales de Empresas
   * V = Otros tipos
   * W = Establecimientos permanentes de entidades no residentes
   */
  private const CIF_PREFIXES = 'ABCDEFGHJKLMNPQRSUVW';

  /**
   * CIF types where control character MUST be a letter.
   */
  private const CIF_LETTER_CONTROL = ['P', 'Q', 'R', 'S', 'W'];

  /**
   * CIF types where control character MUST be a digit.
   */
  private const CIF_DIGIT_CONTROL = ['A', 'B', 'E', 'H'];

  /**
   * CIF control letter lookup table.
   */
  private const CIF_CONTROL_LETTERS = 'JABCDEFGHI';

  /**
   * Validates a Spanish identification document (NIF, NIE, or CIF).
   *
   * @param string $document
   *   The document number to validate.
   *
   * @return array{valid: bool, type: ?string, error: ?string}
   *   An associative array with:
   *   - 'valid': Whether the document is valid.
   *   - 'type': The document type ('NIF', 'NIE', 'CIF') or NULL if invalid.
   *   - 'error': An error message or NULL if valid.
   */
  public function validate(string $document): array {
    // Normalize: remove whitespace, dashes, dots; convert to uppercase.
    $document = strtoupper(trim(str_replace([' ', '-', '.'], '', $document)));

    if ($document === '') {
      return [
        'valid' => FALSE,
        'type' => NULL,
        'error' => 'El documento no puede estar vacio.',
      ];
    }

    // Detect document type by first character.
    $firstChar = $document[0];

    // NIE: starts with X, Y, or Z.
    if (isset(self::NIE_PREFIX_MAP[$firstChar])) {
      if ($this->validateNie($document)) {
        return ['valid' => TRUE, 'type' => 'NIE', 'error' => NULL];
      }
      return [
        'valid' => FALSE,
        'type' => 'NIE',
        'error' => 'NIE invalido. Formato esperado: X/Y/Z + 7 digitos + letra de control.',
      ];
    }

    // CIF: starts with a letter in the CIF prefix set.
    if (ctype_alpha($firstChar) && str_contains(self::CIF_PREFIXES, $firstChar)) {
      // But first check if it could be a K/L/M personal NIF (same format as NIF).
      if (in_array($firstChar, ['K', 'L', 'M'], TRUE)) {
        // K, L, M personal NIFs: letter + 7 digits + NIF control letter.
        if (preg_match('/^[KLM]\d{7}[A-Z]$/i', $document)) {
          $numericPart = substr($document, 1, 7);
          $controlLetter = $document[8];
          $expectedLetter = self::NIF_LETTERS[(int) $numericPart % 23];
          if ($controlLetter === $expectedLetter) {
            return ['valid' => TRUE, 'type' => 'NIF', 'error' => NULL];
          }
          return [
            'valid' => FALSE,
            'type' => 'NIF',
            'error' => 'NIF especial (K/L/M) invalido: letra de control incorrecta.',
          ];
        }
      }

      // Standard CIF validation.
      if ($this->validateCif($document)) {
        return ['valid' => TRUE, 'type' => 'CIF', 'error' => NULL];
      }
      return [
        'valid' => FALSE,
        'type' => 'CIF',
        'error' => 'CIF invalido. Formato esperado: letra + 7 digitos + digito/letra de control.',
      ];
    }

    // NIF: starts with a digit.
    if (ctype_digit($firstChar)) {
      if ($this->validateNif($document)) {
        return ['valid' => TRUE, 'type' => 'NIF', 'error' => NULL];
      }
      return [
        'valid' => FALSE,
        'type' => 'NIF',
        'error' => 'NIF invalido. Formato esperado: 8 digitos + letra de control.',
      ];
    }

    return [
      'valid' => FALSE,
      'type' => NULL,
      'error' => 'Formato de documento no reconocido.',
    ];
  }

  /**
   * Validates a Spanish NIF (Numero de Identificacion Fiscal).
   *
   * Format: 8 digits + 1 control letter.
   * Algorithm: letter = NIF_LETTERS[number % 23].
   *
   * @param string $nif
   *   The NIF string to validate (already normalized to uppercase, no spaces).
   *
   * @return bool
   *   TRUE if the NIF is valid, FALSE otherwise.
   */
  public function validateNif(string $nif): bool {
    // Must match exactly 8 digits followed by 1 letter.
    if (!preg_match('/^\d{8}[A-Z]$/', $nif)) {
      return FALSE;
    }

    $number = (int) substr($nif, 0, 8);
    $letter = $nif[8];
    $expectedLetter = self::NIF_LETTERS[$number % 23];

    return $letter === $expectedLetter;
  }

  /**
   * Validates a Spanish NIE (Numero de Identidad de Extranjero).
   *
   * Format: X/Y/Z + 7 digits + 1 control letter.
   * Algorithm: Replace prefix (X=0, Y=1, Z=2), then apply NIF algorithm.
   *
   * @param string $nie
   *   The NIE string to validate (already normalized to uppercase, no spaces).
   *
   * @return bool
   *   TRUE if the NIE is valid, FALSE otherwise.
   */
  public function validateNie(string $nie): bool {
    // Must match X/Y/Z followed by 7 digits and 1 letter.
    if (!preg_match('/^[XYZ]\d{7}[A-Z]$/', $nie)) {
      return FALSE;
    }

    $prefix = $nie[0];
    $numericPart = self::NIE_PREFIX_MAP[$prefix] . substr($nie, 1, 7);
    $controlLetter = $nie[8];
    $expectedLetter = self::NIF_LETTERS[(int) $numericPart % 23];

    return $controlLetter === $expectedLetter;
  }

  /**
   * Validates a Spanish CIF (Codigo de Identificacion Fiscal).
   *
   * Format: 1 letter (entity type) + 7 digits + 1 control character.
   * The control character can be a digit or a letter depending on entity type.
   *
   * Algorithm:
   * 1. Sum digits in odd positions (1, 3, 5, 7) after doubling and summing digits.
   * 2. Sum digits in even positions (2, 4, 6).
   * 3. Control = (10 - (sum % 10)) % 10.
   * 4. Some entity types require a letter, others a digit, some accept both.
   *
   * @param string $cif
   *   The CIF string to validate (already normalized to uppercase, no spaces).
   *
   * @return bool
   *   TRUE if the CIF is valid, FALSE otherwise.
   */
  public function validateCif(string $cif): bool {
    // Must match: letter + 7 digits + digit or letter.
    if (!preg_match('/^[A-Z]\d{7}[A-Z0-9]$/', $cif)) {
      return FALSE;
    }

    $prefix = $cif[0];

    // Verify the prefix is a valid CIF entity type.
    if (!str_contains(self::CIF_PREFIXES, $prefix)) {
      return FALSE;
    }

    $digits = substr($cif, 1, 7);
    $control = $cif[8];

    // Calculate control value using the official algorithm.
    $sumEven = 0;
    $sumOdd = 0;

    for ($i = 0; $i < 7; $i++) {
      $digit = (int) $digits[$i];

      if ($i % 2 === 0) {
        // Odd positions (1-indexed: 1, 3, 5, 7) -> double and sum digits.
        $doubled = $digit * 2;
        $sumOdd += intdiv($doubled, 10) + ($doubled % 10);
      }
      else {
        // Even positions (1-indexed: 2, 4, 6) -> sum directly.
        $sumEven += $digit;
      }
    }

    $totalSum = $sumEven + $sumOdd;
    $controlDigit = (10 - ($totalSum % 10)) % 10;
    $controlLetter = self::CIF_CONTROL_LETTERS[$controlDigit];

    // Determine whether control must be digit, letter, or either.
    if (in_array($prefix, self::CIF_LETTER_CONTROL, TRUE)) {
      // Must be a letter.
      return $control === $controlLetter;
    }

    if (in_array($prefix, self::CIF_DIGIT_CONTROL, TRUE)) {
      // Must be a digit.
      return $control === (string) $controlDigit;
    }

    // For other entity types, accept either digit or letter.
    return $control === (string) $controlDigit || $control === $controlLetter;
  }

}
