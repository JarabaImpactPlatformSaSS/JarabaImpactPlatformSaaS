<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para EDPB (European Data Protection Board).
 *
 * ESTRUCTURA:
 * Conector que extrae directrices, opiniones y recomendaciones del Comite
 * Europeo de Proteccion de Datos (EDPB). Accede a las publicaciones oficiales
 * del EDPB, incluyendo Guidelines (directrices sobre la interpretacion del
 * RGPD), Opinions (opiniones sobre cuestiones de proteccion de datos),
 * Recommendations (recomendaciones de buenas practicas), Binding Decisions
 * (decisiones vinculantes en disputas transfronterizas) y Statements
 * (declaraciones sobre temas de actualidad en proteccion de datos).
 *
 * LOGICA:
 * Implementa una estrategia dual de extraccion: primero intenta obtener el
 * feed RSS de la pagina de directrices y recomendaciones del EDPB; si el RSS
 * falla o no devuelve resultados, recurre al rastreo HTML de la pagina de
 * listado como fallback. Parsea las entradas para extraer titulo, enlace,
 * fecha de publicacion y tipo de documento. Cada resultado se mapea a la
 * estructura normalizada del pipeline de ingesta del Legal Intelligence Hub.
 * El texto completo se obtendra en fase posterior via Apache Tika sobre el
 * PDF del documento. En caso de error HTTP o de parseo, registra el error
 * y devuelve array vacio. El rango de fechas por defecto es de 30 dias
 * (frecuencia mensual).
 *
 * RELACIONES:
 * - EdpbSpider -> SpiderInterface: implementa el contrato del spider.
 * - EdpbSpider -> GuzzleHttp\ClientInterface: peticiones HTTP al sitio web
 *   del EDPB (feed RSS y pagina de listado HTML).
 * - EdpbSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url configurada del EDPB.
 * - EdpbSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada mensual de fuentes europeas (Fase 4).
 * - EdpbSpider -> LegalResolution: los datos extraidos se normalizan y
 *   persisten como entidades LegalResolution con source_id 'edpb'.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.edpb.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 * Frecuencia de ejecucion: mensual (monthly), dado el ritmo de publicacion
 * de directrices y opiniones del EDPB.
 */
class EdpbSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones al sitio web del EDPB.
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
   * Construye una nueva instancia de EdpbSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones al sitio web del EDPB.
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
    return 'edpb';
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequency(): string {
    return 'monthly';
  }

  /**
   * {@inheritdoc}
   */
  public function supports(string $sourceId): bool {
    return $sourceId === 'edpb';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de directrices, opiniones y recomendaciones del EDPB.
   *
   * Implementa una estrategia dual de extraccion: primero intenta obtener
   * el feed RSS de la pagina de directrices y recomendaciones del EDPB.
   * Si el RSS falla o no devuelve resultados, recurre al rastreo HTML de
   * la pagina de listado como fallback. El rango de fechas por defecto es
   * de 30 dias (frecuencia mensual).
   *
   * @todo Ajustar las URLs del feed RSS y del listado HTML una vez se
   *   valide con el formato real del sitio web del EDPB. La estructura
   *   actual es un scaffold basado en la estructura conocida del sitio.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.edpb.base_url') ?? 'https://edpb.europa.eu/';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');

    // Estrategia 1: Feed RSS del EDPB.
    $resolutions = $this->crawlRssFeed($baseUrl, $dateFrom);

    // Estrategia 2: Rastreo HTML como fallback si RSS no devuelve resultados.
    if (empty($resolutions)) {
      $resolutions = $this->crawlHtmlListing($baseUrl, $dateFrom);
    }

    $this->logger->info('EDPB spider: @count resoluciones extraidas para el rango @from - @to.', [
      '@count' => count($resolutions),
      '@from' => $dateFrom,
      '@to' => $dateTo,
    ]);

    return $resolutions;
  }

  /**
   * Intenta obtener resoluciones del EDPB via feed RSS.
   *
   * Construye la URL del feed RSS de la pagina de directrices y
   * recomendaciones del EDPB y parsea el XML de respuesta para extraer
   * las entradas de documentos publicados.
   *
   * @param string $baseUrl
   *   URL base del sitio web del EDPB.
   * @param string $dateFrom
   *   Fecha de inicio del rango en formato Y-m-d.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   *   Vacio si el feed no esta disponible o no contiene resultados.
   */
  protected function crawlRssFeed(string $baseUrl, string $dateFrom): array {
    // TODO: Ajustar URL del feed RSS al formato exacto de EDPB.
    $feedUrl = rtrim($baseUrl, '/') . '/our-work-tools/general-guidance/guidelines-recommendations-best-practices_en';

    try {
      $response = $this->httpClient->request('GET', $feedUrl, [
        'query' => ['rss' => '1'],
        'timeout' => 60,
        'headers' => [
          'Accept' => 'application/rss+xml, application/xml, text/xml',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $xml = (string) $response->getBody();
      return $this->parseRssFeed($xml, $dateFrom);
    }
    catch (GuzzleException $e) {
      $this->logger->warning('EDPB spider: Error HTTP al obtener feed RSS: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->warning('EDPB spider: Error inesperado al parsear feed RSS: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Intenta obtener resoluciones del EDPB via rastreo HTML.
   *
   * Accede a la pagina de listado de directrices y recomendaciones del
   * EDPB y parsea el HTML para extraer las entradas de documentos. Este
   * metodo se utiliza como fallback cuando el feed RSS no esta disponible
   * o no devuelve resultados.
   *
   * @param string $baseUrl
   *   URL base del sitio web del EDPB.
   * @param string $dateFrom
   *   Fecha de inicio del rango en formato Y-m-d.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   *   Vacio si la pagina no esta disponible o no contiene resultados.
   */
  protected function crawlHtmlListing(string $baseUrl, string $dateFrom): array {
    // TODO: Ajustar URL de la pagina de listado al formato exacto de EDPB.
    $listingUrl = rtrim($baseUrl, '/') . '/our-work-tools/general-guidance/guidelines-recommendations-best-practices_en';

    try {
      $response = $this->httpClient->request('GET', $listingUrl, [
        'timeout' => 60,
        'headers' => [
          'Accept' => 'text/html,application/xhtml+xml',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $html = (string) $response->getBody();
      return $this->parseHtmlListing($html, $dateFrom);
    }
    catch (GuzzleException $e) {
      $this->logger->error('EDPB spider: Error HTTP al rastrear listado HTML: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('EDPB spider: Error inesperado al rastrear listado HTML: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea el feed RSS del EDPB y extrae resoluciones.
   *
   * Procesa el XML del feed RSS (o Atom) del sitio web del EDPB. Soporta
   * tanto el formato RSS 2.0 (channel/item) como Atom (entry). Filtra las
   * entradas por fecha de publicacion y extrae titulo, enlace, fecha,
   * descripcion y tipo de documento de cada entrada.
   *
   * Descarta entradas sin titulo o sin enlace, ya que son campos
   * obligatorios para la creacion de la entidad LegalResolution.
   *
   * @param string $xml
   *   Contenido XML del feed RSS del EDPB.
   * @param string $dateFrom
   *   Fecha de inicio del rango en formato Y-m-d para filtrar entradas.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   *   Cada elemento incluye los campos estandar del pipeline mas campos
   *   especificos del EDPB: language_original.
   */
  protected function parseRssFeed(string $xml, string $dateFrom): array {
    $resolutions = [];

    if (empty($xml)) {
      return $resolutions;
    }

    libxml_use_internal_errors(TRUE);

    try {
      $feed = new \SimpleXMLElement($xml);
    }
    catch (\Exception $e) {
      $this->logger->notice('EDPB spider: Error parseando RSS feed: @message', [
        '@message' => $e->getMessage(),
      ]);
      libxml_clear_errors();
      return $resolutions;
    }

    // Soportar tanto RSS 2.0 (channel/item) como Atom (entry).
    $items = $feed->channel->item ?? $feed->entry ?? [];

    foreach ($items as $item) {
      $title = (string) ($item->title ?? '');
      $link = (string) ($item->link ?? $item->guid ?? '');
      $pubDate = (string) ($item->pubDate ?? $item->published ?? '');
      $description = (string) ($item->description ?? $item->summary ?? '');

      if (empty($title) || empty($link)) {
        continue;
      }

      // Filtrar por fecha de publicacion.
      $dateIssued = $this->normalizeDate($pubDate);
      if ($dateIssued && $dateIssued < $dateFrom) {
        continue;
      }

      // Generar referencia externa a partir de la URL o el titulo.
      $externalRef = $this->extractReference($link, $title);
      if (empty($externalRef)) {
        continue;
      }

      $resolutions[] = [
        'source_id' => 'edpb',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => $this->classifyDocumentType($title),
        'issuing_body' => 'EDPB',
        'jurisdiction' => 'proteccion_datos',
        'date_issued' => $dateIssued,
        'original_url' => $link,
        // Texto completo se extrae posteriormente via Apache Tika.
        'full_text' => '',
        // Campos especificos del EDPB.
        'language_original' => 'en',
      ];
    }

    libxml_clear_errors();

    return $resolutions;
  }

  /**
   * Parsea la pagina de listado HTML del EDPB y extrae resoluciones.
   *
   * Procesa el HTML de la pagina de directrices y recomendaciones del EDPB
   * utilizando DOMDocument y DOMXPath. Busca entradas de documentos en
   * bloques article con clase 'node' o en filas de vistas Drupal con clase
   * 'views-row'. Extrae titulo, enlace, fecha y tipo de cada entrada.
   *
   * TODO: Esta implementacion es un scaffold. El parseo real depende del
   * formato exacto del HTML devuelto por el sitio web del EDPB. Se debe
   * refinar una vez se analice la estructura DOM de la pagina de listado.
   * Los selectores XPath son aproximaciones basadas en la estructura
   * conocida del sitio (basado en Drupal).
   *
   * @param string $html
   *   Contenido HTML de la pagina de listado del EDPB.
   * @param string $dateFrom
   *   Fecha de inicio del rango en formato Y-m-d para filtrar entradas.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   */
  protected function parseHtmlListing(string $html, string $dateFrom): array {
    $resolutions = [];

    if (empty($html)) {
      return $resolutions;
    }

    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // TODO: Ajustar los selectores XPath al formato real del sitio EDPB.
    // El sitio del EDPB esta basado en Drupal 8+, por lo que los bloques
    // de contenido pueden ser article.node o div.views-row.
    // Intentar primero con article.node, luego con views-row.
    $entries = $xpath->query("//article[contains(@class, 'node')]");

    if ($entries === FALSE || $entries->length === 0) {
      $entries = $xpath->query("//div[contains(@class, 'view-content')]//div[contains(@class, 'views-row')]");
    }

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('EDPB spider: No se encontraron entradas en la pagina de listado HTML. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // Extraer titulo del documento.
      $titleNode = $xpath->query(".//h2//a | .//h3//a | .//a[contains(@class, 'title')]", $entry);
      $title = '';
      $link = '';

      if ($titleNode && $titleNode->length > 0) {
        $title = trim($titleNode->item(0)->textContent);
        $link = trim($titleNode->item(0)->getAttribute('href') ?? '');
      }

      if (empty($title)) {
        continue;
      }

      // Construir URL completa si el enlace es relativo.
      if (!empty($link) && strpos($link, 'http') !== 0) {
        $link = rtrim('https://edpb.europa.eu', '/') . '/' . ltrim($link, '/');
      }

      if (empty($link)) {
        continue;
      }

      // Extraer fecha de publicacion del documento.
      $dateNode = $xpath->query(".//*[contains(@class, 'date')] | .//*[contains(@class, 'field--name-created')] | .//time", $entry);
      $rawDate = '';

      if ($dateNode && $dateNode->length > 0) {
        // Intentar primero el atributo datetime de <time>.
        $datetimeAttr = $dateNode->item(0)->getAttribute('datetime');
        if (!empty($datetimeAttr)) {
          $rawDate = $datetimeAttr;
        }
        else {
          $rawDate = trim($dateNode->item(0)->textContent);
        }
      }

      $dateIssued = $this->normalizeDate($rawDate);

      // Filtrar por fecha si se pudo extraer.
      if ($dateIssued && $dateIssued < $dateFrom) {
        continue;
      }

      // Generar referencia externa a partir de la URL o el titulo.
      $externalRef = $this->extractReference($link, $title);
      if (empty($externalRef)) {
        continue;
      }

      $resolutions[] = [
        'source_id' => 'edpb',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => $this->classifyDocumentType($title),
        'issuing_body' => 'EDPB',
        'jurisdiction' => 'proteccion_datos',
        'date_issued' => $dateIssued,
        'original_url' => $link,
        // Texto completo se extrae posteriormente via Apache Tika.
        'full_text' => '',
        // Campos especificos del EDPB.
        'language_original' => 'en',
      ];
    }

    libxml_clear_errors();

    return $resolutions;
  }

  /**
   * Clasifica el tipo de documento del EDPB a partir del titulo.
   *
   * Analiza el titulo del documento para determinar su tipo segun la
   * taxonomia de publicaciones del EDPB. Los tipos principales son:
   * directrices (Guidelines), opiniones (Opinions), recomendaciones
   * (Recommendations), decisiones vinculantes (Binding Decisions) y
   * declaraciones (Statements).
   *
   * @param string $title
   *   Titulo del documento del EDPB.
   *
   * @return string
   *   Tipo de documento normalizado: 'guideline_edpb', 'opinion',
   *   'recomendacion', 'decision', 'declaracion'.
   */
  protected function classifyDocumentType(string $title): string {
    $titleLower = strtolower($title);

    if (str_contains($titleLower, 'guideline') || str_contains($titleLower, 'guidelines')) {
      return 'guideline_edpb';
    }
    if (str_contains($titleLower, 'opinion')) {
      return 'opinion';
    }
    if (str_contains($titleLower, 'recommendation')) {
      return 'recomendacion';
    }
    if (str_contains($titleLower, 'binding decision') || str_contains($titleLower, 'decision')) {
      return 'decision';
    }
    if (str_contains($titleLower, 'statement')) {
      return 'declaracion';
    }

    return 'guideline_edpb';
  }

  /**
   * Extrae una referencia unica del documento a partir de la URL o el titulo.
   *
   * Intenta extraer el numero de documento del EDPB del titulo (por ejemplo,
   * "Guidelines 01/2024") o genera una referencia a partir del slug de la URL.
   * El formato de referencia resultante es "EDPB-{numero}" para facilitar
   * la identificacion unica de cada documento en el pipeline de ingesta.
   *
   * @param string $url
   *   URL original del documento en el sitio web del EDPB.
   * @param string $title
   *   Titulo del documento del EDPB.
   *
   * @return string
   *   Referencia unica del documento (ej: 'EDPB-01-2024', 'EDPB-slug-url').
   *   Cadena vacia si no se pudo generar una referencia.
   */
  protected function extractReference(string $url, string $title): string {
    // Intentar extraer numero del titulo (ej: "Guidelines 01/2024").
    if (preg_match('/(\d+\/\d{4})/', $title, $matches)) {
      return 'EDPB-' . str_replace('/', '-', $matches[1]);
    }

    // Fallback: extraer slug de la ruta de la URL.
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $slug = basename($path);

    return !empty($slug) ? 'EDPB-' . $slug : '';
  }

  /**
   * Normaliza una cadena de fecha al formato Y-m-d.
   *
   * Convierte diversos formatos de fecha (RSS pubDate, ISO 8601, formatos
   * europeos, etc.) al formato estandar Y-m-d utilizado en el pipeline de
   * ingesta. Utiliza strtotime() para la conversion, que soporta la mayoria
   * de formatos de fecha en ingles.
   *
   * @param string $date
   *   Cadena de fecha en cualquier formato soportado por strtotime().
   *
   * @return string
   *   Fecha normalizada en formato Y-m-d, o cadena vacia si la fecha
   *   no se pudo parsear.
   */
  protected function normalizeDate(string $date): string {
    if (empty($date)) {
      return '';
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('Y-m-d', $timestamp) : '';
  }

}
