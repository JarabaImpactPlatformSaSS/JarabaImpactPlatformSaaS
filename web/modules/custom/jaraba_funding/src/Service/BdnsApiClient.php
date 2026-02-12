<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Psr\Log\LoggerInterface;

/**
 * API client for Spanish BDNS (Base de Datos Nacional de Subvenciones).
 *
 * Provides methods for building API URLs, parsing JSON responses,
 * and normalizing convocatoria data from the BDNS public API.
 *
 * ARQUITECTURA:
 * - Construye URLs con parametros de busqueda para el API de BDNS.
 * - Parsea respuestas JSON de la API con manejo de errores.
 * - Normaliza datos crudos de convocatorias a formato interno estandar.
 *
 * RELACIONES:
 * - BdnsApiClient -> LoggerInterface (logging)
 * - BdnsApiClient <- Controllers / Services (consumido por)
 * - BdnsApiClient -> FundingNormalizerService (datos normalizados)
 */
class BdnsApiClient {

  /**
   * Base URL of the BDNS API.
   */
  protected const BASE_URL = 'https://www.pap.hacienda.gob.es/bdnstrans/api';

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
   * Returns the base URL for the BDNS API.
   *
   * @return string
   *   The BDNS API base URL.
   */
  public function getBaseUrl(): string {
    return self::BASE_URL;
  }

  /**
   * Builds a full URL from an endpoint path and query parameters.
   *
   * @param string $path
   *   The API endpoint path (e.g., '/api/convocatorias').
   * @param array $params
   *   Associative array of query parameters.
   *
   * @return string
   *   The fully constructed URL with encoded query parameters.
   */
  public function buildUrl(string $path, array $params = []): string {
    $url = self::BASE_URL . $path;

    if (!empty($params)) {
      $queryString = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
      $url .= '?' . $queryString;
    }

    return $url;
  }

  /**
   * Parses a JSON response string into an associative array.
   *
   * Returns an empty array on invalid or empty JSON input,
   * logging a warning for malformed responses.
   *
   * @param string $json
   *   The raw JSON response string.
   *
   * @return array
   *   Parsed data as an associative array, or empty array on failure.
   */
  public function parseJsonResponse(string $json): array {
    if ($json === '') {
      return [];
    }

    try {
      $data = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
      return is_array($data) ? $data : [];
    }
    catch (\JsonException $e) {
      $this->logger->warning('Error parsing BDNS JSON response: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Normalizes a raw convocatoria record from BDNS to internal format.
   *
   * Maps Spanish field names from the BDNS API to standardized English
   * field names used internally. Provides safe defaults for optional fields.
   *
   * @param array $raw
   *   Raw convocatoria data from the BDNS API with Spanish field names.
   *
   * @return array
   *   Normalized convocatoria with standardized field names:
   *   - id: (int) BDNS identifier.
   *   - title: (string) Convocatoria title.
   *   - description: (string) Full description.
   *   - organization: (string) Issuing organization.
   *   - region: (string) Geographic scope.
   *   - start_date: (string) Start date in ISO format.
   *   - deadline: (string) Application deadline in ISO format.
   *   - amount: (float) Total available amount.
   *   - beneficiary_type: (string) Target beneficiary type.
   *   - status: (string) Current status (abierta, cerrada, etc.).
   *   - url: (string) URL to official publication.
   */
  public function normalizeConvocatoria(array $raw): array {
    return [
      'id' => (int) ($raw['id'] ?? 0),
      'title' => (string) ($raw['titulo'] ?? ''),
      'description' => (string) ($raw['descripcion'] ?? ''),
      'organization' => (string) ($raw['organo'] ?? ''),
      'region' => (string) ($raw['region'] ?? ''),
      'start_date' => (string) ($raw['fecha_inicio'] ?? ''),
      'deadline' => (string) ($raw['fecha_fin'] ?? ''),
      'amount' => (float) ($raw['importe_total'] ?? 0),
      'beneficiary_type' => (string) ($raw['tipo_beneficiario'] ?? ''),
      'status' => (string) ($raw['estado'] ?? 'desconocido'),
      'url' => (string) ($raw['url_bases'] ?? ''),
    ];
  }

}
