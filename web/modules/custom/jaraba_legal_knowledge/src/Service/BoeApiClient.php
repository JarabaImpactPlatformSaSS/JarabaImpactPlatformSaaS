<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Cliente para la API de Datos Abiertos del BOE.
 *
 * Consulta el Boletin Oficial del Estado (BOE) mediante su API publica
 * de datos abiertos para obtener normas, textos completos y cambios
 * recientes. Incluye reintentos automaticos y timeout configurable.
 *
 * ARQUITECTURA:
 * - Base URL configurable via jaraba_legal_knowledge.settings.
 * - Reintentos automaticos (hasta 3) con backoff lineal.
 * - Timeout de 30 segundos por peticion.
 * - Respuestas parseadas desde JSON.
 *
 * @see https://www.boe.es/datosabiertos/
 */
class BoeApiClient {

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
   *   Cliente HTTP para peticiones a la API del BOE.
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
   * Busca normas en la API del BOE con filtros.
   *
   * @param array $filters
   *   Filtros de busqueda. Claves soportadas:
   *   - fecha_desde: (string) Fecha inicio en formato YYYYMMDD.
   *   - fecha_hasta: (string) Fecha fin en formato YYYYMMDD.
   *   - departamento: (string) Codigo del departamento emisor.
   *   - rango: (string) Tipo de norma (ley, real_decreto, orden, etc.).
   * @param int $page
   *   Numero de pagina para paginacion (base 1).
   *
   * @return array
   *   Array con claves:
   *   - data: (array) Lista de normas encontradas.
   *   - total: (int) Total de resultados.
   *   - page: (int) Pagina actual.
   */
  public function searchNorms(array $filters, int $page = 1): array {
    $params = ['page' => $page];

    if (!empty($filters['fecha_desde'])) {
      $params['fecha_desde'] = $filters['fecha_desde'];
    }
    if (!empty($filters['fecha_hasta'])) {
      $params['fecha_hasta'] = $filters['fecha_hasta'];
    }
    if (!empty($filters['departamento'])) {
      $params['departamento'] = $filters['departamento'];
    }
    if (!empty($filters['rango'])) {
      $params['rango'] = $filters['rango'];
    }

    try {
      $response = $this->makeRequest('/buscar', $params);

      return [
        'data' => $response['data'] ?? [],
        'total' => (int) ($response['total'] ?? 0),
        'page' => $page,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando normas en BOE: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['data' => [], 'total' => 0, 'page' => $page];
    }
  }

  /**
   * Obtiene datos de una norma especifica por su ID de BOE.
   *
   * @param string $boeId
   *   Identificador BOE de la norma (e.g., "BOE-A-2006-20764").
   *
   * @return array|null
   *   Datos de la norma o NULL si no se encuentra.
   */
  public function getNormById(string $boeId): ?array {
    try {
      $response = $this->makeRequest('/documento/' . urlencode($boeId), []);

      return $response['data'] ?? $response;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo norma @id del BOE: @error', [
        '@id' => $boeId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Descarga el texto completo de una norma (XML/HTML).
   *
   * @param string $boeId
   *   Identificador BOE de la norma.
   *
   * @return string|null
   *   Texto completo de la norma o NULL si no se puede obtener.
   */
  public function getNormFullText(string $boeId): ?string {
    $baseUrl = $this->getBaseUrl();

    try {
      $response = $this->httpClient->request('GET', $baseUrl . '/documento/' . urlencode($boeId) . '/texto', [
        'timeout' => self::HTTP_TIMEOUT,
        'headers' => [
          'Accept' => 'text/html, application/xml',
        ],
      ]);

      $body = (string) $response->getBody();
      if (empty($body)) {
        $this->logger->warning('Texto completo vacio para norma @id.', [
          '@id' => $boeId,
        ]);
        return NULL;
      }

      // Limpiar HTML/XML para extraer texto plano.
      $text = strip_tags($body);
      $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $text = preg_replace('/\s+/', ' ', $text);
      $text = trim($text);

      return $text;
    }
    catch (\Exception $e) {
      $this->logger->error('Error descargando texto completo de norma @id: @error', [
        '@id' => $boeId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene normas publicadas desde una fecha determinada.
   *
   * @param string $dateFrom
   *   Fecha de inicio en formato YYYYMMDD.
   *
   * @return array
   *   Lista de normas publicadas desde la fecha indicada.
   */
  public function getRecentChanges(string $dateFrom): array {
    $dateTo = date('Ymd');

    try {
      $response = $this->makeRequest('/buscar', [
        'fecha_desde' => $dateFrom,
        'fecha_hasta' => $dateTo,
      ]);

      return $response['data'] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo cambios recientes desde @date: @error', [
        '@date' => $dateFrom,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Realiza una peticion HTTP GET a la API del BOE con reintentos.
   *
   * @param string $endpoint
   *   Ruta del endpoint (e.g., '/buscar', '/documento/BOE-A-2006-20764').
   * @param array $params
   *   Parametros de query string.
   *
   * @return array
   *   Respuesta decodificada como array asociativo.
   *
   * @throws \RuntimeException
   *   Si la peticion falla despues de todos los reintentos.
   */
  protected function makeRequest(string $endpoint, array $params): array {
    $baseUrl = $this->getBaseUrl();
    $url = $baseUrl . $endpoint;

    $lastException = NULL;

    for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
      try {
        $response = $this->httpClient->request('GET', $url, [
          'query' => $params,
          'timeout' => self::HTTP_TIMEOUT,
          'headers' => [
            'Accept' => 'application/json',
          ],
        ]);

        $body = (string) $response->getBody();
        $data = json_decode($body, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
          throw new \RuntimeException('Respuesta JSON invalida del BOE: ' . json_last_error_msg());
        }

        return $data;
      }
      catch (RequestException $e) {
        $lastException = $e;
        $this->logger->warning('BOE API intento @attempt/@max fallo para @endpoint: @error', [
          '@attempt' => $attempt,
          '@max' => self::MAX_RETRIES,
          '@endpoint' => $endpoint,
          '@error' => $e->getMessage(),
        ]);

        // Backoff lineal entre reintentos.
        if ($attempt < self::MAX_RETRIES) {
          usleep($attempt * 500000);
        }
      }
    }

    throw new \RuntimeException(
      'BOE API fallo despues de ' . self::MAX_RETRIES . ' intentos: ' .
      ($lastException ? $lastException->getMessage() : 'Error desconocido')
    );
  }

  /**
   * Obtiene la URL base de la API del BOE desde configuracion.
   *
   * @return string
   *   URL base sin barra final.
   */
  protected function getBaseUrl(): string {
    $config = $this->configFactory->get('jaraba_legal_knowledge.settings');
    $baseUrl = $config->get('boe_api_base_url') ?: 'https://www.boe.es/datosabiertos/api';

    return rtrim($baseUrl, '/');
  }

}
