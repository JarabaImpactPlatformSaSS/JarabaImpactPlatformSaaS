<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para BOE (Boletin Oficial del Estado).
 *
 * ESTRUCTURA:
 * Conector que extrae legislacion y disposiciones oficiales del BOE a traves
 * de su API de datos abiertos (https://www.boe.es/datosabiertos/). El BOE
 * publica diariamente leyes organicas, reales decretos, ordenes ministeriales,
 * resoluciones administrativas y anuncios oficiales de todos los ministerios
 * y organismos del Estado.
 *
 * LOGICA:
 * Utiliza la API REST del BOE Open Data que devuelve XML/JSON con las
 * disposiciones publicadas en un rango de fechas. Cada disposicion tiene
 * un identificador unico BOE-A-xxxx-xxxxx. El spider parsea la respuesta
 * XML para extraer: titulo oficial, departamento emisor, rango normativo,
 * fecha de publicacion y URL del texto completo en PDF/HTML. El texto
 * completo se extrae posteriormente via Apache Tika en el pipeline NLP.
 *
 * RELACIONES:
 * - BoeSpider -> SpiderInterface: implementa el contrato del spider.
 * - BoeSpider -> GuzzleHttp\ClientInterface: peticiones HTTP a la API BOE.
 * - BoeSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url configurada.
 * - BoeSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.boe.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 */
class BoeSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones a la API del BOE.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Factoria de configuracion de Drupal.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye una nueva instancia de BoeSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones a la API del BOE.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para acceder a las fuentes configuradas.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
  ) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'boe';
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequency(): string {
    return 'daily';
  }

  /**
   * {@inheritdoc}
   */
  public function supports(string $sourceId): bool {
    return $sourceId === 'boe';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de disposiciones del BOE via API de datos abiertos.
   *
   * Construye una peticion GET a la API del BOE Open Data con filtros de
   * fecha. Parsea la respuesta XML para extraer las disposiciones publicadas
   * en el rango indicado, incluyendo: identificador BOE-A, titulo oficial,
   * departamento, rango normativo, fecha de publicacion y URL del texto.
   *
   * @todo Refinar el parseo XML una vez se valide con el formato real
   *   de la API del BOE Open Data. La estructura actual es un scaffold
   *   basado en la documentacion publica de la API.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.boe.base_url') ?? 'https://www.boe.es/datosabiertos/api/';

    $dateFrom = $options['date_from'] ?? date('Ymd', strtotime('-1 day'));
    $dateTo = $options['date_to'] ?? date('Ymd');

    // La API del BOE acepta fecha en formato YYYYMMDD.
    // Normalizar formato si viene como Y-m-d.
    $dateFrom = str_replace('-', '', $dateFrom);
    $dateTo = str_replace('-', '', $dateTo);

    // TODO: Ajustar endpoint y parametros al formato exacto de la API BOE.
    // Endpoint para sumario (disposiciones) de un dia concreto.
    $searchUrl = $baseUrl . 'boe/dias/' . $dateFrom;

    try {
      $response = $this->httpClient->request('GET', $searchUrl, [
        'timeout' => 60,
        'headers' => [
          'Accept' => 'application/xml',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $xml = (string) $response->getBody();
      $resolutions = $this->parseResponse($xml);

      $this->logger->info('BOE spider: @count disposiciones extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('BOE spider: Error HTTP al rastrear @url: @message', [
        '@url' => $searchUrl,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('BOE spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea la respuesta XML de la API del BOE y extrae disposiciones.
   *
   * TODO: Esta implementacion es un scaffold. El parseo real depende del
   * esquema XML exacto de la API del BOE Open Data. Los nombres de elementos
   * son aproximaciones basadas en la documentacion publica. Refinar una vez
   * se valide con respuestas reales de la API.
   *
   * @param string $xml
   *   Contenido XML de la respuesta de la API del BOE.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada disposicion.
   */
  protected function parseResponse(string $xml): array {
    $resolutions = [];

    if (empty($xml)) {
      return $resolutions;
    }

    // TODO: Implementar parseo real del XML del BOE Open Data.
    // Scaffold: usar SimpleXML para extraer disposiciones.
    libxml_use_internal_errors(TRUE);

    try {
      $doc = new \SimpleXMLElement($xml);
    }
    catch (\Exception $e) {
      $this->logger->warning('BOE spider: Error parseando XML: @message', [
        '@message' => $e->getMessage(),
      ]);
      libxml_clear_errors();
      return $resolutions;
    }

    // TODO: Ajustar la ruta XPath al esquema real del BOE.
    // El BOE Open Data agrupa disposiciones en secciones.
    // Estructura esperada: sumario > diario > seccion > departamento > item.
    $items = $doc->xpath('//item') ?: [];

    if (empty($items)) {
      $this->logger->notice('BOE spider: No se encontraron disposiciones en la respuesta XML. Posible cambio de esquema.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($items as $item) {
      // TODO: Extraer datos reales de cada nodo XML.
      $externalRef = (string) ($item->identificador ?? $item->id ?? '');
      $title = (string) ($item->titulo ?? '');
      $departamento = (string) ($item->departamento ?? '');
      $rango = (string) ($item->rango ?? '');
      $fechaPublicacion = (string) ($item->fecha_publicacion ?? '');
      $urlPdf = (string) ($item->url_pdf ?? $item->urlPdf ?? '');
      $urlHtml = (string) ($item->url_html ?? $item->urlHtml ?? '');

      if (empty($externalRef) || empty($title)) {
        continue;
      }

      // Mapear rango normativo del BOE a resolution_type del sistema.
      $resolutionType = $this->mapRangoToType($rango);

      $resolutions[] = [
        'source_id' => 'boe',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => $resolutionType,
        'issuing_body' => $departamento,
        'jurisdiction' => '',
        'date_issued' => $fechaPublicacion,
        'date_published' => $fechaPublicacion,
        'original_url' => $urlHtml ?: $urlPdf,
        // Texto completo se extrae posteriormente via Apache Tika.
        'full_text' => '',
      ];
    }

    libxml_clear_errors();

    return $resolutions;
  }

  /**
   * Mapea el rango normativo del BOE al tipo de resolucion del sistema.
   *
   * El BOE clasifica disposiciones con rangos normativos propios
   * (Ley Organica, Real Decreto, Orden, Resolucion, etc.). Este metodo
   * los traduce a los tipos del sistema (sentencia, resolucion, etc.).
   *
   * @param string $rango
   *   Rango normativo del BOE.
   *
   * @return string
   *   Tipo de resolucion normalizado para el sistema.
   */
  protected function mapRangoToType(string $rango): string {
    $rango = mb_strtolower(trim($rango));

    // TODO: Completar mapeo con todos los rangos normativos del BOE.
    return match (TRUE) {
      str_contains($rango, 'ley orgánica'), str_contains($rango, 'ley organica') => 'ley_organica',
      str_contains($rango, 'ley') => 'ley',
      str_contains($rango, 'real decreto-ley') => 'real_decreto_ley',
      str_contains($rango, 'real decreto') => 'real_decreto',
      str_contains($rango, 'orden') => 'orden_ministerial',
      str_contains($rango, 'directiva') => 'directiva',
      str_contains($rango, 'reglamento') => 'reglamento',
      str_contains($rango, 'resolución'), str_contains($rango, 'resolucion') => 'resolucion',
      default => 'disposicion',
    };
  }

}
