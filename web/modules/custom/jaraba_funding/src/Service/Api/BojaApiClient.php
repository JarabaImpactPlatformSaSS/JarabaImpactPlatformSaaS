<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Api;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para la API de BOJA (Boletin Oficial de la Junta de Andalucia).
 *
 * Consulta el BOJA mediante su API publica para obtener convocatorias
 * de subvenciones andaluzas. Similar a BdnsApiClient pero orientado
 * al ambito autonomico andaluz y con respuestas en formato XML.
 *
 * ARQUITECTURA:
 * - Base URL configurable via jaraba_funding.settings.
 * - Reintentos automaticos (hasta 3) con backoff lineal.
 * - Timeout de 30 segundos por peticion.
 * - Respuestas parseadas desde XML.
 *
 * RELACIONES:
 * - BojaApiClient -> FundingIngestionService (consumido por)
 * - BojaApiClient -> jaraba_funding.settings (configuracion)
 *
 * @see https://www.juntadeandalucia.es/boja/
 */
class BojaApiClient {

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
   *   Cliente HTTP para peticiones a la API del BOJA.
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
   * Busca convocatorias de subvenciones en el BOJA.
   *
   * @param array $filters
   *   Filtros de busqueda. Claves soportadas:
   *   - fecha_desde: (string) Fecha inicio en formato YYYY-MM-DD.
   *   - fecha_hasta: (string) Fecha fin en formato YYYY-MM-DD.
   *   - consejeria: (string) Consejeria convocante.
   *   - materia: (string) Materia/sector.
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
    if (!empty($filters['consejeria'])) {
      $params['consejeria'] = $filters['consejeria'];
    }
    if (!empty($filters['materia'])) {
      $params['materia'] = $filters['materia'];
    }

    try {
      $url = $this->buildUrl('/convocatorias', $params);
      $response = $this->executeRequest($url);

      $data = [];
      foreach ($response as $raw) {
        $data[] = $this->normalizeConvocatoria($raw);
      }

      return [
        'data' => $data,
        'total' => count($data),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error buscando convocatorias en BOJA: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['data' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene detalle de una convocatoria por BOJA ID.
   *
   * @param string $bojaId
   *   Identificador BOJA de la convocatoria.
   *
   * @return array|null
   *   Datos normalizados de la convocatoria o NULL si no se encuentra.
   */
  public function fetchConvocatoria(string $bojaId): ?array {
    try {
      $url = $this->buildUrl('/convocatorias/' . urlencode($bojaId), []);
      $response = $this->executeRequest($url);

      if (empty($response)) {
        return NULL;
      }

      // Si la respuesta es una lista, tomar el primer elemento.
      $raw = isset($response[0]) ? $response[0] : $response;

      return $this->normalizeConvocatoria($raw);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo convocatoria BOJA @id: @error', [
        '@id' => $bojaId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene la URL base de la API del BOJA desde configuracion.
   *
   * @return string
   *   URL base sin barra final.
   */
  public function getBaseUrl(): string {
    $config = $this->configFactory->get('jaraba_funding.settings');
    $baseUrl = $config->get('boja_api_url') ?: 'https://www.juntadeandalucia.es/boja/api/';

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
   * Espera respuestas XML del BOJA y las convierte a array.
   *
   * @param string $url
   *   URL completa a consultar.
   *
   * @return array
   *   Respuesta convertida de XML a array asociativo.
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
            'Accept' => 'application/xml, text/xml',
          ],
        ]);

        $body = (string) $response->getBody();

        return $this->parseXmlResponse($body);
      }
      catch (RequestException $e) {
        $lastException = $e;
        $this->logger->warning('BOJA API intento @attempt/@max fallo para @url: @error', [
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
      'BOJA API fallo despues de ' . self::MAX_RETRIES . ' intentos: ' .
      ($lastException ? $lastException->getMessage() : 'Error desconocido')
    );
  }

  /**
   * Parsea respuesta XML del BOJA a array asociativo.
   *
   * @param string $body
   *   Cuerpo de la respuesta HTTP en formato XML.
   *
   * @return array
   *   Array de convocatorias extraidas del XML.
   *
   * @throws \RuntimeException
   *   Si la respuesta no es XML valido.
   */
  public function parseXmlResponse(string $body): array {
    if (empty($body)) {
      return [];
    }

    libxml_use_internal_errors(TRUE);
    $xml = simplexml_load_string($body);

    if ($xml === FALSE) {
      $errors = libxml_get_errors();
      libxml_clear_errors();
      $errorMsg = !empty($errors) ? $errors[0]->message : 'XML invalido';
      throw new \RuntimeException('Respuesta XML invalida del BOJA: ' . trim($errorMsg));
    }

    // Convertir SimpleXMLElement a array recursivamente.
    $json = json_encode($xml);
    $data = json_decode($json, TRUE);

    if ($data === NULL) {
      return [];
    }

    // Si hay un nodo raiz con items, extraerlos.
    if (isset($data['convocatoria'])) {
      $items = $data['convocatoria'];
      // Asegurar que siempre sea un array de items.
      if (isset($items['id']) || isset($items['titulo'])) {
        return [$items];
      }
      return $items;
    }

    // Si la raiz ya es una lista de items.
    if (isset($data[0])) {
      return $data;
    }

    return [$data];
  }

  /**
   * Normaliza datos crudos de una convocatoria BOJA al formato interno.
   *
   * @param array $raw
   *   Datos crudos de la API del BOJA.
   *
   * @return array
   *   Datos normalizados con claves:
   *   - source_id: (string) Identificador BOJA.
   *   - source: (string) 'boja'.
   *   - title: (string) Titulo de la convocatoria.
   *   - description: (string) Descripcion.
   *   - organism: (string) Organo convocante (consejeria).
   *   - region: (string) 'andalucia'.
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
      'source_id' => (string) ($raw['id'] ?? $raw['boja_id'] ?? ''),
      'source' => 'boja',
      'title' => (string) ($raw['titulo'] ?? $raw['title'] ?? ''),
      'description' => (string) ($raw['descripcion'] ?? $raw['extracto'] ?? ''),
      'organism' => (string) ($raw['consejeria'] ?? $raw['organo'] ?? ''),
      'region' => 'andalucia',
      'beneficiary_types' => (array) ($raw['tipos_beneficiario'] ?? $raw['destinatarios'] ?? []),
      'sectors' => (array) ($raw['sectores'] ?? $raw['materias'] ?? []),
      'amount_min' => (float) ($raw['importe_minimo'] ?? 0),
      'amount_max' => (float) ($raw['importe_maximo'] ?? $raw['dotacion'] ?? 0),
      'deadline' => $raw['fecha_fin_solicitud'] ?? $raw['plazo_fin'] ?? NULL,
      'publication_date' => $raw['fecha_publicacion'] ?? NULL,
      'url' => (string) ($raw['url'] ?? $raw['enlace'] ?? ''),
      'status' => (string) ($raw['estado'] ?? 'abierta'),
    ];
  }

}
