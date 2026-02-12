<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Api;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para la API de BDNS (Base de Datos Nacional de Subvenciones).
 *
 * Consulta la BDNS mediante su API publica para obtener convocatorias
 * de subvenciones, detalle de convocatorias y busquedas por texto.
 * Incluye reintentos automaticos y timeout configurable.
 *
 * ARQUITECTURA:
 * - Base URL configurable via jaraba_funding.settings.
 * - Reintentos automaticos (hasta 3) con backoff lineal.
 * - Timeout de 30 segundos por peticion.
 * - Respuestas parseadas desde JSON.
 *
 * RELACIONES:
 * - BdnsApiClient -> FundingIngestionService (consumido por)
 * - BdnsApiClient -> jaraba_funding.settings (configuracion)
 *
 * @see https://www.pap.hacienda.gob.es/bdnstrans/
 */
class BdnsApiClient {

  /**
   * Timeout HTTP en segundos.
   */
  protected const HTTP_TIMEOUT = 30;

  /**
   * Numero maximo de reintentos por peticion.
   */
  protected const MAX_RETRIES = 3;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para peticiones a la API de BDNS.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Busca convocatorias en la BDNS con filtros.
   *
   * @param array $filters
   *   Filtros de busqueda. Claves soportadas:
   *   - fecha_desde: (string) Fecha inicio en formato YYYY-MM-DD.
   *   - fecha_hasta: (string) Fecha fin en formato YYYY-MM-DD.
   *   - ccaa: (string) Codigo de comunidad autonoma.
   *   - sector: (string) Sector de actividad.
   *
   * @return array
   *   Array con claves:
   *   - data: (array) Lista de convocatorias encontradas.
   *   - total: (int) Total de resultados.
   */
  public function fetchConvocatorias(array $filters = []): array {
    $params = [];

    if (!empty($filters['fecha_desde'])) {
      $params['fecha_desde'] = $filters['fecha_desde'];
    }
    if (!empty($filters['fecha_hasta'])) {
      $params['fecha_hasta'] = $filters['fecha_hasta'];
    }
    if (!empty($filters['ccaa'])) {
      $params['ccaa'] = $filters['ccaa'];
    }
    if (!empty($filters['sector'])) {
      $params['sector'] = $filters['sector'];
    }

    try {
      $url = $this->buildUrl('/convocatorias', $params);
      $response = $this->executeRequest($url);

      $data = [];
      foreach ($response['data'] ?? [] as $raw) {
        $data[] = $this->normalizeConvocatoria($raw);
      }

      return [
        'data' => $data,
        'total' => (int) ($response['total'] ?? count($data)),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando convocatorias en BDNS: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['data' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene detalle de una convocatoria por BDNS ID.
   *
   * @param string $bdnsId
   *   Identificador BDNS de la convocatoria.
   *
   * @return array|null
   *   Datos normalizados de la convocatoria o NULL si no se encuentra.
   */
  public function fetchConvocatoria(string $bdnsId): ?array {
    try {
      $url = $this->buildUrl('/convocatorias/' . urlencode($bdnsId), []);
      $response = $this->executeRequest($url);

      $raw = $response['data'] ?? $response;

      return $this->normalizeConvocatoria($raw);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo convocatoria BDNS @id: @error', [
        '@id' => $bdnsId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Busqueda de convocatorias por texto libre.
   *
   * @param string $keyword
   *   Palabra clave o texto de busqueda.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Lista de convocatorias normalizadas que coinciden con el texto.
   */
  public function searchByKeyword(string $keyword, int $limit = 50): array {
    try {
      $url = $this->buildUrl('/convocatorias', [
        'q' => $keyword,
        'limit' => $limit,
      ]);
      $response = $this->executeRequest($url);

      $data = [];
      foreach ($response['data'] ?? [] as $raw) {
        $data[] = $this->normalizeConvocatoria($raw);
      }

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando por keyword "@keyword" en BDNS: @error', [
        '@keyword' => $keyword,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene la URL base de la API de BDNS desde configuracion.
   *
   * @return string
   *   URL base sin barra final.
   */
  public function getBaseUrl(): string {
    $config = $this->configFactory->get('jaraba_funding.settings');
    $baseUrl = $config->get('bdns_api_url') ?: 'https://www.pap.hacienda.gob.es/bdnstrans/api/';

    return rtrim($baseUrl, '/');
  }

  /**
   * Construye URL completa con query params.
   *
   * @param string $endpoint
   *   Ruta del endpoint (e.g., '/convocatorias').
   * @param array $params
   *   Parametros de query string.
   *
   * @return string
   *   URL completa con parametros codificados.
   */
  public function buildUrl(string $endpoint, array $params = []): string {
    $url = $this->getBaseUrl() . $endpoint;

    if (!empty($params)) {
      $url .= '?' . http_build_query($params);
    }

    return $url;
  }

  /**
   * Ejecuta peticion HTTP GET con reintentos y manejo de errores.
   *
   * @param string $url
   *   URL completa a consultar.
   *
   * @return array
   *   Respuesta decodificada como array asociativo.
   *
   * @throws \RuntimeException
   *   Si la peticion falla despues de todos los reintentos.
   */
  public function executeRequest(string $url): array {
    $lastException = NULL;

    for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
      try {
        $response = $this->httpClient->request('GET', $url, [
          'timeout' => self::HTTP_TIMEOUT,
          'headers' => [
            'Accept' => 'application/json',
          ],
        ]);

        $body = (string) $response->getBody();

        return $this->parseJsonResponse($body);
      }
      catch (RequestException $e) {
        $lastException = $e;
        $this->logger->warning('BDNS API intento @attempt/@max fallo para @url: @error', [
          '@attempt' => $attempt,
          '@max' => self::MAX_RETRIES,
          '@url' => $url,
          '@error' => $e->getMessage(),
        ]);

        // Backoff lineal entre reintentos.
        if ($attempt < self::MAX_RETRIES) {
          usleep($attempt * 500000);
        }
      }
    }

    throw new \RuntimeException(
      'BDNS API fallo despues de ' . self::MAX_RETRIES . ' intentos: ' .
      ($lastException ? $lastException->getMessage() : 'Error desconocido')
    );
  }

  /**
   * Parsea respuesta JSON del cuerpo HTTP.
   *
   * @param string $body
   *   Cuerpo de la respuesta HTTP.
   *
   * @return array
   *   Array decodificado de la respuesta JSON.
   *
   * @throws \RuntimeException
   *   Si la respuesta no es JSON valido.
   */
  public function parseJsonResponse(string $body): array {
    $data = json_decode($body, TRUE);

    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException('Respuesta JSON invalida de BDNS: ' . json_last_error_msg());
    }

    return $data ?? [];
  }

  /**
   * Normaliza datos crudos de una convocatoria al formato interno.
   *
   * @param array $raw
   *   Datos crudos de la API de BDNS.
   *
   * @return array
   *   Datos normalizados con claves:
   *   - source_id: (string) Identificador BDNS.
   *   - source: (string) 'bdns'.
   *   - title: (string) Titulo de la convocatoria.
   *   - description: (string) Descripcion.
   *   - organism: (string) Organo convocante.
   *   - region: (string) Comunidad autonoma.
   *   - beneficiary_types: (array) Tipos de beneficiario.
   *   - sectors: (array) Sectores de actividad.
   *   - amount_min: (float) Importe minimo.
   *   - amount_max: (float) Importe maximo.
   *   - deadline: (string|null) Fecha limite en formato Y-m-d.
   *   - publication_date: (string|null) Fecha de publicacion.
   *   - url: (string) URL de la convocatoria.
   *   - status: (string) Estado de la convocatoria.
   */
  public function normalizeConvocatoria(array $raw): array {
    return [
      'source_id' => (string) ($raw['id'] ?? $raw['bdns_id'] ?? ''),
      'source' => 'bdns',
      'title' => (string) ($raw['titulo'] ?? $raw['title'] ?? ''),
      'description' => (string) ($raw['descripcion'] ?? $raw['description'] ?? ''),
      'organism' => (string) ($raw['organo'] ?? $raw['organismo'] ?? ''),
      'region' => (string) ($raw['ccaa'] ?? $raw['region'] ?? 'nacional'),
      'beneficiary_types' => (array) ($raw['tipos_beneficiario'] ?? []),
      'sectors' => (array) ($raw['sectores'] ?? []),
      'amount_min' => (float) ($raw['importe_minimo'] ?? 0),
      'amount_max' => (float) ($raw['importe_maximo'] ?? $raw['importe_total'] ?? 0),
      'deadline' => $raw['fecha_fin_solicitud'] ?? $raw['deadline'] ?? NULL,
      'publication_date' => $raw['fecha_publicacion'] ?? $raw['publication_date'] ?? NULL,
      'url' => (string) ($raw['url'] ?? ''),
      'status' => (string) ($raw['estado'] ?? $raw['status'] ?? 'abierta'),
    ];
  }

}
