<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para CENDOJ (Centro de Documentacion Judicial).
 *
 * ESTRUCTURA:
 * Conector que extrae jurisprudencia del buscador del CENDOJ, la base de datos
 * oficial del Consejo General del Poder Judicial. Accede a sentencias y autos
 * del Tribunal Supremo (TS), Audiencia Nacional (AN), Tribunales Superiores
 * de Justicia (TSJ) y Audiencias Provinciales (AP) en todas las jurisdicciones:
 * civil, penal, contencioso-administrativo, social y militar.
 *
 * LOGICA:
 * Construye una URL de busqueda con parametros de fecha (date_from, date_to)
 * contra el endpoint de busqueda de CENDOJ. Parsea el HTML de respuesta para
 * extraer las entradas de resoluciones (ROJ, ponente, organo, fecha, etc.).
 * El texto completo se extrae posteriormente via Apache Tika en el pipeline
 * NLP, aqui solo se captura el resumen y la URL del documento original.
 * En caso de error HTTP o de parseo, registra el error y devuelve array vacio.
 *
 * RELACIONES:
 * - CendojSpider -> SpiderInterface: implementa el contrato del spider.
 * - CendojSpider -> GuzzleHttp\ClientInterface: peticiones HTTP al CENDOJ.
 * - CendojSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url configurada.
 * - CendojSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.cendoj.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 */
class CendojSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones al CENDOJ.
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
   * Construye una nueva instancia de CendojSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones al CENDOJ.
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
    return 'cendoj';
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
    return $sourceId === 'cendoj';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de jurisprudencia del CENDOJ.
   *
   * Construye una peticion GET al buscador de CENDOJ con filtros de fecha.
   * Parsea el HTML de respuesta para extraer los datos basicos de cada
   * resolucion: ROJ, ponente, organo emisor, tipo, jurisdiccion, fecha y URL.
   * El texto completo se obtendra en fase posterior via Apache Tika.
   *
   * @todo Refinar el parseo HTML una vez se valide con el formato real
   *   de respuesta del CENDOJ. La estructura actual es un scaffold basado
   *   en la estructura conocida del buscador de jurisprudencia.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.cendoj.base_url') ?? 'https://www.poderjudicial.es/search';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-1 day'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');
    $maxResults = $options['max_results'] ?? 100;

    // Construir URL de busqueda con parametros de fecha.
    // TODO: Ajustar parametros de query al formato exacto del CENDOJ.
    $searchUrl = $baseUrl . '?' . http_build_query([
      'FECHA_RESOLUCION_DESDE' => $dateFrom,
      'FECHA_RESOLUCION_HASTA' => $dateTo,
      'NUM_REGISTRO' => $maxResults,
      'TIPO_DOC' => 'sentencias',
    ]);

    try {
      $response = $this->httpClient->request('GET', $searchUrl, [
        'timeout' => 60,
        'headers' => [
          'Accept' => 'text/html,application/xhtml+xml',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $html = (string) $response->getBody();
      $resolutions = $this->parseResponse($html);

      $this->logger->info('CENDOJ spider: @count resoluciones extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('CENDOJ spider: Error HTTP al rastrear @url: @message', [
        '@url' => $searchUrl,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('CENDOJ spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea la respuesta HTML del CENDOJ y extrae resoluciones.
   *
   * TODO: Esta implementacion es un scaffold. El parseo real depende del
   * formato exacto del HTML devuelto por el buscador del CENDOJ. Se debe
   * refinar una vez se analice la estructura DOM de la pagina de resultados.
   * Los selectores CSS/XPath son aproximaciones basadas en la estructura
   * conocida del buscador.
   *
   * @param string $html
   *   Contenido HTML de la respuesta del CENDOJ.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   */
  protected function parseResponse(string $html): array {
    $resolutions = [];

    if (empty($html)) {
      return $resolutions;
    }

    // TODO: Implementar parseo real del HTML del CENDOJ.
    // Scaffold: usar DOMDocument para extraer entradas de resultados.
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // TODO: Ajustar el selector XPath al formato real de resultados del CENDOJ.
    // El CENDOJ presenta resultados en bloques con clase 'listadoDocumentos'
    // o similar. Cada bloque contiene: ROJ, fecha, organo, etc.
    $entries = $xpath->query("//div[contains(@class, 'resultado')]");

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('CENDOJ spider: No se encontraron entradas en la respuesta HTML. Posible cambio de formato.');
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // TODO: Extraer datos reales de cada nodo DOM.
      // Scaffold: estructura esperada de cada entrada.
      $titleNode = $xpath->query(".//a[contains(@class, 'titulo')]", $entry);
      $title = $titleNode && $titleNode->length > 0
        ? trim($titleNode->item(0)->textContent)
        : '';

      $linkNode = $xpath->query(".//a[contains(@class, 'titulo')]/@href", $entry);
      $originalUrl = $linkNode && $linkNode->length > 0
        ? trim($linkNode->item(0)->nodeValue)
        : '';

      // TODO: Extraer ROJ como external_ref (ej: ROJ: STS 1234/2024).
      $refNode = $xpath->query(".//*[contains(@class, 'roj')]", $entry);
      $externalRef = $refNode && $refNode->length > 0
        ? trim($refNode->item(0)->textContent)
        : '';

      // TODO: Extraer organo emisor (TS, AN, TSJ, AP).
      $bodyNode = $xpath->query(".//*[contains(@class, 'organo')]", $entry);
      $issuingBody = $bodyNode && $bodyNode->length > 0
        ? trim($bodyNode->item(0)->textContent)
        : '';

      // TODO: Extraer fecha de la resolucion.
      $dateNode = $xpath->query(".//*[contains(@class, 'fecha')]", $entry);
      $dateIssued = $dateNode && $dateNode->length > 0
        ? trim($dateNode->item(0)->textContent)
        : '';

      // TODO: Extraer jurisdiccion y tipo de resolucion.
      $jurisdictionNode = $xpath->query(".//*[contains(@class, 'jurisdiccion')]", $entry);
      $jurisdiction = $jurisdictionNode && $jurisdictionNode->length > 0
        ? trim($jurisdictionNode->item(0)->textContent)
        : '';

      if (empty($externalRef) || empty($title)) {
        continue;
      }

      $resolutions[] = [
        'source_id' => 'cendoj',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => 'sentencia',
        'issuing_body' => $issuingBody,
        'jurisdiction' => $jurisdiction,
        'date_issued' => $dateIssued,
        'original_url' => $originalUrl,
        // Texto completo se extrae posteriormente via Apache Tika.
        'full_text' => '',
      ];
    }

    libxml_clear_errors();

    return $resolutions;
  }

}
