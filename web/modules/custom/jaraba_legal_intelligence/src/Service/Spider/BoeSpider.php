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
   * @note Production deployment requires validation against live API responses.
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

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
    // La API del BOE Open Data expone sumarios diarios en el endpoint
    // /boe/dias/{YYYY}/{MM}/{DD}. La fecha se descompone en componentes
    // de ruta separados (anio/mes/dia) siguiendo la documentacion publica.
    $year = substr($dateFrom, 0, 4);
    $month = substr($dateFrom, 4, 2);
    $day = substr($dateFrom, 6, 2);
    $searchUrl = rtrim($baseUrl, '/') . '/boe/dias/' . $year . '/' . $month . '/' . $day;

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
   * AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
   * Parsea la respuesta XML de la API del BOE Open Data utilizando
   * SimpleXMLElement. La estructura XML del sumario diario es:
   *   <sumario>
   *     <metadatos>...</metadatos>
   *     <diario nbo="...">
   *       <sumario_nbo>
   *         <seccion num="..." nombre="...">
   *           <departamento nombre="...">
   *             <epigrafe nombre="...">
   *               <item id="BOE-A-...">
   *                 <titulo>...</titulo>
   *                 <urlPdf>...</urlPdf>
   *                 <urlHtml>...</urlHtml>
   *               </item>
   *             </epigrafe>
   *           </departamento>
   *         </seccion>
   *       </sumario_nbo>
   *     </diario>
   *   </sumario>
   *
   * @note Production deployment requires validation against live API responses.
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

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
    // Usar SimpleXML para extraer disposiciones del sumario diario.
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

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
    // Recorrer la estructura jerarquica del sumario: diario > sumario_nbo
    // > seccion > departamento > epigrafe > item. Se extrae la fecha de
    // publicacion de los metadatos y el departamento de cada nivel padre.
    $fechaPublicacion = (string) ($doc->metadatos->fecha_publicacion ?? '');
    if (empty($fechaPublicacion)) {
      // Intentar extraer del atributo del diario.
      $fechaPublicacion = (string) ($doc->diario->attributes()->fecha ?? '');
    }

    // Buscar items en la jerarquia correcta del BOE.
    // Ruta completa: sumario > diario > sumario_nbo > seccion > departamento > epigrafe > item.
    $items = $doc->xpath('//item') ?: [];

    // Si la ruta generica no encuentra items, intentar la ruta jerarquica.
    if (empty($items)) {
      $items = $doc->xpath('//diario//seccion//departamento//item') ?: [];
    }

    // Ultimo intento: items directos o bajo epigrafe.
    if (empty($items)) {
      $items = $doc->xpath('//epigrafe/item') ?: [];
    }

    if (empty($items)) {
      $this->logger->notice('BOE spider: No se encontraron disposiciones en la respuesta XML. Posible cambio de esquema.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($items as $item) {
      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
      // Extraer datos de cada <item> del sumario BOE. El id del item sigue
      // el formato BOE-A-YYYY-NNNNN. Los campos disponibles son:
      //   <titulo>, <urlPdf>, <urlHtml>, <urlXml> dentro de cada <item>.
      // El departamento y rango se obtienen de los atributos de los nodos
      // padre o de sub-elementos del propio item.
      $itemAttrs = $item->attributes();
      $externalRef = (string) ($itemAttrs['id'] ?? '');

      // Fallback: buscar el identificador en un sub-elemento.
      if (empty($externalRef)) {
        $externalRef = (string) ($item->identificador ?? $item->id ?? '');
      }

      $title = (string) ($item->titulo ?? '');

      // El departamento se puede encontrar como atributo del nodo padre
      // <departamento nombre="..."> o como sub-elemento del item.
      $departamento = '';
      $itemDom = dom_import_simplexml($item);
      if ($itemDom && $itemDom->parentNode) {
        $parentNode = $itemDom->parentNode;
        // Subir niveles: epigrafe -> departamento.
        while ($parentNode && $parentNode->nodeName !== 'departamento') {
          $parentNode = $parentNode->parentNode;
        }
        if ($parentNode && $parentNode->nodeName === 'departamento') {
          $departamento = $parentNode->getAttribute('nombre') ?: '';
        }
      }
      if (empty($departamento)) {
        $departamento = (string) ($item->departamento ?? '');
      }

      // El rango normativo se extrae del epigrafe padre o del propio item.
      $rango = '';
      $epigrafeParent = $itemDom ? $itemDom->parentNode : NULL;
      if ($epigrafeParent && $epigrafeParent->nodeName === 'epigrafe') {
        $rango = $epigrafeParent->getAttribute('nombre') ?: '';
      }
      if (empty($rango)) {
        $rango = (string) ($item->rango ?? '');
      }

      // URLs del texto completo.
      $urlPdf = (string) ($item->urlPdf ?? $item->url_pdf ?? '');
      $urlHtml = (string) ($item->urlHtml ?? $item->url_html ?? '');
      $urlXml = (string) ($item->urlXml ?? '');

      // Completar URLs relativas con el dominio del BOE.
      $urlPdf = $this->resolveBoUrl($urlPdf);
      $urlHtml = $this->resolveBoUrl($urlHtml);

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
   * Resuelve URLs relativas del BOE al dominio completo.
   *
   * Las URLs en la API del BOE pueden ser relativas (empezando por /)
   * o absolutas. Este metodo asegura que siempre se devuelve una URL
   * absoluta con el dominio https://www.boe.es.
   *
   * @param string $url
   *   URL potencialmente relativa.
   *
   * @return string
   *   URL absoluta, o cadena vacia si la entrada esta vacia.
   */
  protected function resolveBoUrl(string $url): string {
    if (empty($url)) {
      return '';
    }
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
      return $url;
    }
    return 'https://www.boe.es' . (str_starts_with($url, '/') ? '' : '/') . $url;
  }

  /**
   * Mapea el rango normativo del BOE al tipo de resolucion del sistema.
   *
   * El BOE clasifica disposiciones con rangos normativos propios
   * (Ley Organica, Real Decreto, Orden, Resolucion, etc.). Este metodo
   * los traduce a los tipos del sistema (sentencia, resolucion, etc.).
   *
   * AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
   * Mapeo ampliado con todos los rangos normativos conocidos del BOE,
   * incluyendo instrucciones, circulares, convenios y acuerdos.
   *
   * @param string $rango
   *   Rango normativo del BOE.
   *
   * @return string
   *   Tipo de resolucion normalizado para el sistema.
   */
  protected function mapRangoToType(string $rango): string {
    $rango = mb_strtolower(trim($rango));

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for BOE.
    // Mapeo completo de rangos normativos del BOE. El orden importa:
    // los rangos mas especificos deben evaluarse antes que los genericos.
    return match (TRUE) {
      str_contains($rango, 'ley orgánica'), str_contains($rango, 'ley organica') => 'ley_organica',
      str_contains($rango, 'real decreto-ley'), str_contains($rango, 'real decreto-ley') => 'real_decreto_ley',
      str_contains($rango, 'real decreto legislativo') => 'real_decreto_legislativo',
      str_contains($rango, 'real decreto') => 'real_decreto',
      str_contains($rango, 'ley') => 'ley',
      str_contains($rango, 'orden ministerial'), str_contains($rango, 'orden') => 'orden_ministerial',
      str_contains($rango, 'directiva') => 'directiva',
      str_contains($rango, 'reglamento') => 'reglamento',
      str_contains($rango, 'instrucción'), str_contains($rango, 'instruccion') => 'instruccion',
      str_contains($rango, 'circular') => 'circular',
      str_contains($rango, 'convenio') => 'convenio',
      str_contains($rango, 'acuerdo') => 'acuerdo',
      str_contains($rango, 'decreto') => 'decreto',
      str_contains($rango, 'resolución'), str_contains($rango, 'resolucion') => 'resolucion',
      str_contains($rango, 'corrección'), str_contains($rango, 'correccion') => 'correccion_errores',
      str_contains($rango, 'anuncio') => 'anuncio',
      default => 'disposicion',
    };
  }

}
