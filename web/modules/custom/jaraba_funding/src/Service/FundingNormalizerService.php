<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Psr\Log\LoggerInterface;

/**
 * Data normalizer for funding data from heterogeneous sources.
 *
 * Handles parsing and normalization of amounts (European/Spanish formats),
 * dates (dd/mm/yyyy and ISO), regions (accent/case variants), beneficiary
 * types, and industry sectors extracted from free-text descriptions.
 *
 * ARQUITECTURA:
 * - Cada metodo es una funcion pura sin estado (stateless).
 * - Los metodos de extraccion usan diccionarios de palabras clave.
 * - Las fechas se normalizan siempre a formato ISO (yyyy-mm-dd).
 * - Los importes se normalizan a float (punto como separador decimal).
 *
 * RELACIONES:
 * - FundingNormalizerService -> LoggerInterface (logging)
 * - FundingNormalizerService <- BdnsApiClient (consumido por)
 * - FundingNormalizerService <- Controllers (consumido por)
 */
class FundingNormalizerService {

  /**
   * Known region names for normalization (lowercase, no accents).
   */
  protected const KNOWN_REGIONS = [
    'andalucia',
    'aragon',
    'asturias',
    'baleares',
    'canarias',
    'cantabria',
    'castilla_la_mancha',
    'castilla_y_leon',
    'cataluna',
    'ceuta',
    'comunidad_valenciana',
    'extremadura',
    'galicia',
    'la_rioja',
    'madrid',
    'melilla',
    'murcia',
    'navarra',
    'pais_vasco',
    'nacional',
    'europeo',
  ];

  /**
   * Keywords for detecting beneficiary types in free text.
   */
  protected const BENEFICIARY_KEYWORDS = [
    'pymes' => ['pymes', 'pyme', 'pequenas y medianas'],
    'autonomos' => ['autonomos', 'autonomo', 'trabajador autonomo', 'trabajadores autonomos'],
    'grandes_empresas' => ['grandes empresas', 'gran empresa'],
    'microempresas' => ['microempresas', 'microempresa'],
    'asociaciones' => ['asociaciones', 'asociacion'],
    'fundaciones' => ['fundaciones', 'fundacion'],
    'ong' => ['ong', 'organizaciones sin animo de lucro', 'entidades sin animo de lucro', 'sin animo de lucro'],
    'administraciones_publicas' => ['administraciones publicas', 'administracion publica', 'entidades publicas'],
    'universidades' => ['universidades', 'universidad'],
    'personas_fisicas' => ['personas fisicas', 'persona fisica', 'particulares'],
  ];

  /**
   * Keywords for detecting industry sectors in free text.
   */
  protected const SECTOR_KEYWORDS = [
    'tecnologia' => ['tecnologia', 'tecnologico', 'tic', 'digital', 'digitalizacion', 'informatica', 'software'],
    'industria' => ['industria', 'industrial', 'manufactura', 'fabricacion'],
    'comercio' => ['comercio', 'comercial', 'retail', 'venta'],
    'servicios' => ['servicios', 'consultoria', 'asesoria'],
    'agroalimentario' => ['agroalimentario', 'agricultura', 'ganaderia', 'alimentacion', 'agrario'],
    'turismo' => ['turismo', 'turistico', 'hosteleria', 'hotelero'],
    'salud' => ['salud', 'sanitario', 'biomedico', 'farmaceutico', 'medicina'],
    'energia' => ['energia', 'energetico', 'renovable', 'renovables', 'eficiencia energetica'],
    'transporte' => ['transporte', 'logistica', 'movilidad'],
    'construccion' => ['construccion', 'edificacion', 'inmobiliario'],
    'educacion' => ['educacion', 'educativo', 'formacion'],
    'cultura' => ['cultura', 'cultural', 'artes', 'creativo'],
    'medioambiente' => ['medioambiente', 'medio ambiente', 'ambiental', 'sostenibilidad', 'sostenible'],
    'innovacion' => ['innovacion', 'i+d', 'investigacion', 'desarrollo'],
  ];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Parses an amount string into a float.
   *
   * Handles European/Spanish number formats:
   * - Dots as thousands separators: "1.500.000"
   * - Commas as decimal separators: "1.500.000,50"
   * - Currency symbols and labels: "250.000 EUR", "50.000,00 euros"
   * - Plain numbers: "50000"
   *
   * @param string $amount
   *   The raw amount string to parse.
   *
   * @return float
   *   The parsed amount as a float. Returns 0.0 for empty or unparseable input.
   */
  public function parseAmount(string $amount): float {
    $amount = trim($amount);

    if ($amount === '') {
      return 0.0;
    }

    // Remove currency symbols and text.
    $cleaned = preg_replace('/[€$]|\b(EUR|euros?|USD)\b/i', '', $amount);
    $cleaned = trim((string) $cleaned);

    if ($cleaned === '') {
      return 0.0;
    }

    // Detect European format: dots as thousands separator, comma as decimal.
    // Pattern: "1.500.000,50" or "250.000"
    if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $cleaned)) {
      // Remove dots (thousands sep), replace comma with dot (decimal sep).
      $cleaned = str_replace('.', '', $cleaned);
      $cleaned = str_replace(',', '.', $cleaned);
    }
    elseif (str_contains($cleaned, ',')) {
      // Single comma as decimal separator: "50000,50"
      $cleaned = str_replace(',', '.', $cleaned);
    }

    // Remove any remaining non-numeric characters except dot.
    $cleaned = preg_replace('/[^0-9.]/', '', (string) $cleaned);

    $value = (float) $cleaned;
    return $value;
  }

  /**
   * Parses a date string to ISO format (yyyy-mm-dd).
   *
   * Supports:
   * - Spanish format: dd/mm/yyyy
   * - ISO format: yyyy-mm-dd (returned as-is)
   * - Other formats parseable by strtotime
   *
   * @param string $date
   *   The raw date string.
   *
   * @return string
   *   Date in ISO format (yyyy-mm-dd), or empty string if unparseable.
   */
  public function parseDate(string $date): string {
    $date = trim($date);

    if ($date === '') {
      return '';
    }

    // Already ISO format.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $timestamp = strtotime($date);
      if ($timestamp !== FALSE) {
        return $date;
      }
      return '';
    }

    // Spanish format: dd/mm/yyyy.
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $date, $matches)) {
      $day = (int) $matches[1];
      $month = (int) $matches[2];
      $year = (int) $matches[3];

      if (checkdate($month, $day, $year)) {
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
      }
      return '';
    }

    // Try generic parsing as fallback.
    $timestamp = strtotime($date);
    if ($timestamp !== FALSE) {
      return date('Y-m-d', $timestamp);
    }

    return '';
  }

  /**
   * Normalizes a region name to a canonical lowercase form.
   *
   * Removes accents, trims whitespace, and lowercases. If the result
   * matches a known region, returns it; otherwise returns the cleaned string.
   *
   * @param string $region
   *   The raw region name.
   *
   * @return string
   *   Normalized region name in lowercase.
   */
  public function normalizeRegion(string $region): string {
    $region = trim($region);

    if ($region === '') {
      return '';
    }

    // Lowercase.
    $normalized = mb_strtolower($region);

    // Remove common Spanish accents for matching.
    $accents = ['a' => 'a', 'e' => 'e', 'i' => 'i', 'o' => 'o', 'u' => 'u', 'n' => 'n'];
    $from = array_keys($accents);
    $to = array_values($accents);
    // This handles the ASCII transliteration for common cases.
    // For full accent removal, use iconv or intl.
    $normalized = str_replace(
      ["\xC3\xA1", "\xC3\xA9", "\xC3\xAD", "\xC3\xB3", "\xC3\xBA", "\xC3\xB1"],
      ['a', 'e', 'i', 'o', 'u', 'n'],
      $normalized
    );

    // Trim again after normalization.
    $normalized = trim($normalized);

    return $normalized;
  }

  /**
   * Extracts beneficiary types from a free-text description.
   *
   * Scans the text for known beneficiary-type keywords and returns
   * a deduplicated list of detected types.
   *
   * @param string $text
   *   The free-text description to scan.
   *
   * @return array
   *   List of detected beneficiary type identifiers (e.g., ['pymes', 'autonomos']).
   */
  public function extractBeneficiaryTypes(string $text): array {
    $text = mb_strtolower(trim($text));
    $found = [];

    foreach (self::BENEFICIARY_KEYWORDS as $type => $keywords) {
      foreach ($keywords as $keyword) {
        if (str_contains($text, $keyword)) {
          $found[] = $type;
          break;
        }
      }
    }

    return array_values(array_unique($found));
  }

  /**
   * Extracts industry sectors from a free-text description.
   *
   * Scans the text for known sector keywords and returns
   * a deduplicated list of detected sectors.
   *
   * @param string $text
   *   The free-text description to scan.
   *
   * @return array
   *   List of detected sector identifiers (e.g., ['tecnologia', 'industria']).
   */
  public function extractSectors(string $text): array {
    $text = mb_strtolower(trim($text));
    $found = [];

    foreach (self::SECTOR_KEYWORDS as $sector => $keywords) {
      foreach ($keywords as $keyword) {
        if (str_contains($text, $keyword)) {
          $found[] = $sector;
          break;
        }
      }
    }

    return array_values(array_unique($found));
  }

}
