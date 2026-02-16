<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para TEAC (Tribunal Economico-Administrativo Central).
 *
 * ESTRUCTURA:
 * Conector que extrae resoluciones del Tribunal Economico-Administrativo
 * Central (TEAC), el organo maximo de la via economico-administrativa en
 * Espanha. El TEAC resuelve reclamaciones en materia tributaria, catastral
 * y de gestion recaudatoria, y su doctrina es vinculante para los tribunales
 * economico-administrativos regionales (TEAR) y locales (TEAL).
 *
 * LOGICA:
 * Accede al sistema telematico del TEAC (DYCteac) del Ministerio de Hacienda.
 * Construye peticiones con filtros de fecha y parsea la respuesta para
 * extraer: numero de resolucion, criterio, fecha de resolucion, ponente
 * y URL del texto completo. La jurisdiccion es siempre 'fiscal' y el tipo
 * es siempre 'resolucion'. El organo emisor es siempre 'TEAC'. El texto
 * completo se procesa via Apache Tika en el pipeline NLP.
 *
 * RELACIONES:
 * - TeacSpider -> SpiderInterface: implementa el contrato del spider.
 * - TeacSpider -> GuzzleHttp\ClientInterface: peticiones HTTP a DYCteac.
 * - TeacSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url configurada.
 * - TeacSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada semanal.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.teac.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 */
class TeacSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones al TEAC.
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
   * Construye una nueva instancia de TeacSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones al sistema DYCteac.
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
    return 'teac';
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequency(): string {
    return 'weekly';
  }

  /**
   * {@inheritdoc}
   */
  public function supports(string $sourceId): bool {
    return $sourceId === 'teac';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de resoluciones del TEAC.
   *
   * Construye una peticion al sistema telematico DYCteac del Ministerio
   * de Hacienda con filtros de fecha para obtener las ultimas resoluciones
   * publicadas. Parsea la respuesta para extraer el numero de resolucion,
   * criterio, fecha, ponente y URL del texto completo.
   *
   * @todo Refinar el parseo una vez se valide con el formato real de
   *   respuesta del sistema DYCteac. La estructura actual es un scaffold.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.teac.base_url') ?? 'https://serviciostelematicos.minhap.gob.es/DYCteac';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');

    // TODO: Ajustar endpoint y parametros al formato exacto de DYCteac.
    // El sistema telematico del TEAC permite busqueda por rango de fechas.
    $searchUrl = $baseUrl . '/buscarResolucion.html' . '?' . http_build_query([
      'fechaDesde' => $dateFrom,
      'fechaHasta' => $dateTo,
      'criterio' => '',
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
      $resolutions = $this->parseResponse($html, $baseUrl);

      $this->logger->info('TEAC spider: @count resoluciones extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('TEAC spider: Error HTTP al rastrear @url: @message', [
        '@url' => $searchUrl,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('TEAC spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea la respuesta HTML del sistema DYCteac y extrae resoluciones.
   *
   * TODO: Esta implementacion es un scaffold. El parseo real depende del
   * formato exacto del HTML devuelto por el sistema telematico del TEAC.
   * Refinar una vez se analice la estructura DOM de la pagina de resultados
   * de busqueda de resoluciones.
   *
   * @param string $html
   *   Contenido HTML de la respuesta de DYCteac.
   * @param string $baseUrl
   *   URL base del TEAC para construir URLs absolutas.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   */
  protected function parseResponse(string $html, string $baseUrl): array {
    $resolutions = [];

    if (empty($html)) {
      return $resolutions;
    }

    // TODO: Implementar parseo real del HTML de DYCteac.
    // Scaffold: usar DOMDocument para extraer entradas de resoluciones.
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // TODO: Ajustar selectores XPath al formato real de DYCteac.
    // Las resoluciones del TEAC se presentan en filas de tabla.
    $rows = $xpath->query("//table[contains(@class, 'resultados')]//tr[position() > 1] | //div[contains(@class, 'resolucion')]");

    if ($rows === FALSE || $rows->length === 0) {
      $this->logger->notice('TEAC spider: No se encontraron resoluciones en la respuesta. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($rows as $row) {
      // TODO: Extraer datos reales de cada fila de la tabla.
      // Estructura esperada de la tabla: numero | criterio | fecha | ponente.
      $cells = $xpath->query(".//td", $row);

      if ($cells === FALSE || $cells->length < 3) {
        continue;
      }

      $externalRef = trim($cells->item(0)->textContent);
      $criterio = $cells->length > 1 ? trim($cells->item(1)->textContent) : '';
      $dateIssued = $cells->length > 2 ? trim($cells->item(2)->textContent) : '';

      // Extraer URL del enlace al texto completo.
      $linkNode = $xpath->query(".//a/@href", $row);
      $originalUrl = '';
      if ($linkNode && $linkNode->length > 0) {
        $href = trim($linkNode->item(0)->nodeValue);
        // Convertir URLs relativas a absolutas.
        $originalUrl = str_starts_with($href, 'http') ? $href : $baseUrl . '/' . ltrim($href, '/');
      }

      if (empty($externalRef)) {
        continue;
      }

      // Construir titulo descriptivo a partir del criterio y referencia.
      $title = !empty($criterio)
        ? sprintf('Resolucion TEAC %s - %s', $externalRef, $criterio)
        : sprintf('Resolucion TEAC %s', $externalRef);

      $resolutions[] = [
        'source_id' => 'teac',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => 'resolucion',
        'issuing_body' => 'TEAC',
        'jurisdiction' => 'fiscal',
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
