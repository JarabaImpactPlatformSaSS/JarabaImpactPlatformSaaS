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
   * @note Production deployment requires validation against live API responses.
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
    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for EDPB RSS feed.
    // El sitio web del EDPB (basado en Drupal) expone un feed RSS en la
    // seccion de directrices y recomendaciones. La URL sigue el patron
    // del modulo views de Drupal con argumento de formato RSS. Se intenta
    // primero la URL canonica con sufijo /rss, luego con parametro ?rss=1.
    // @note Production deployment requires validation against live API responses.
    $feedUrl = rtrim($baseUrl, '/') . '/our-work-tools/general-guidance/guidelines-recommendations-best-practices_en/rss';

    try {
      $response = $this->httpClient->request('GET', $feedUrl, [
        'timeout' => 60,
        'headers' => [
          'Accept' => 'application/rss+xml, application/xml, text/xml',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $contentType = $response->getHeaderLine('Content-Type');
      $xml = (string) $response->getBody();

      // Verificar que la respuesta es realmente XML/RSS y no una pagina HTML.
      if (str_contains($contentType, 'text/html') && !str_contains($xml, '<rss') && !str_contains($xml, '<feed')) {
        $this->logger->notice('EDPB spider: El feed RSS devolvio HTML en lugar de XML. Intentando URL alternativa.');

        // Intentar URL alternativa con parametro de query.
        $feedUrlAlt = rtrim($baseUrl, '/') . '/our-work-tools/general-guidance/guidelines-recommendations-best-practices_en';
        $response = $this->httpClient->request('GET', $feedUrlAlt, [
          'query' => ['_format' => 'rss'],
          'timeout' => 60,
          'headers' => [
            'Accept' => 'application/rss+xml, application/xml, text/xml',
            'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
          ],
        ]);
        $xml = (string) $response->getBody();
      }

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
    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for EDPB HTML listing.
    // La pagina de listado del EDPB sigue la estructura de un sitio Drupal 9+.
    // Los documentos estan disponibles en varias secciones del sitio:
    // - /our-work-tools/general-guidance/ (directrices y recomendaciones)
    // - /our-work-tools/consistency-findings/ (decisiones vinculantes)
    // - /our-work-tools/our-documents/ (todos los documentos)
    // @note Production deployment requires validation against live API responses.
    $listingUrl = rtrim($baseUrl, '/') . '/our-work-tools/our-documents_en';

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

      // Enlace: en RSS 2.0 es <link>, en Atom puede ser <link href="...">.
      $link = (string) ($item->link ?? '');
      if (empty($link) && isset($item->link)) {
        // Atom: <link href="..."/>.
        $linkAttrs = $item->link->attributes();
        $link = (string) ($linkAttrs['href'] ?? '');
      }
      if (empty($link)) {
        $link = (string) ($item->guid ?? '');
      }

      $pubDate = (string) ($item->pubDate ?? $item->published ?? $item->updated ?? '');
      $description = (string) ($item->description ?? $item->summary ?? $item->content ?? '');

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
   * AUDIT-TODO-RESOLVED: Implemented DOM parsing for EDPB HTML listing.
   * Procesa el HTML de la pagina de documentos del EDPB utilizando
   * DOMDocument y DOMXPath. El sitio del EDPB esta basado en Drupal 9+
   * y presenta documentos en bloques con estructura semantica: article.node
   * para nodos completos, div.views-row para listados de vistas, o
   * div.ecl-content-block para bloques de contenido del European Component
   * Library (ECL). Cada bloque contiene titulo con enlace, fecha de
   * publicacion y, opcionalmente, tipo de documento y resumen.
   *
   * @note Production deployment requires validation against live API responses.
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
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for EDPB HTML listing.
    // Selectores XPath para la pagina de documentos del EDPB.
    // El sitio del EDPB utiliza varias estructuras posibles:
    // 1. article.node: nodos Drupal renderizados como articulos HTML5.
    // 2. div.views-row: filas de vistas Drupal (Views module).
    // 3. div.ecl-content-block: bloques ECL (European Component Library).
    // 4. div.ecl-card: tarjetas ECL para listados compactos.
    // Se intentan todos los selectores en orden de especificidad.
    $entries = $xpath->query(
      "//article[contains(@class, 'node')]"
      . " | //div[contains(@class, 'ecl-content-block')]"
      . " | //div[contains(@class, 'ecl-card')]"
    );

    if ($entries === FALSE || $entries->length === 0) {
      $entries = $xpath->query(
        "//div[contains(@class, 'view-content')]//div[contains(@class, 'views-row')]"
      );
    }

    // Ultimo intento: buscar bloques de contenido genericos con enlaces.
    if ($entries === FALSE || $entries->length === 0) {
      $entries = $xpath->query(
        "//div[contains(@class, 'field--name-title')]/.."
        . " | //div[contains(@class, 'node--type')]"
      );
    }

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('EDPB spider: No se encontraron entradas en la pagina de listado HTML. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // Extraer titulo del documento.
      // Buscar en orden: titulo en heading con enlace, titulo ECL,
      // enlace con clase title, heading directo.
      $titleNode = $xpath->query(
        ".//h2//a | .//h3//a"
        . " | .//a[contains(@class, 'ecl-content-block__title')]"
        . " | .//a[contains(@class, 'ecl-card__title')]"
        . " | .//a[contains(@class, 'title')]"
        . " | .//div[contains(@class, 'field--name-title')]//a"
        . " | .//h2 | .//h3",
        $entry,
      );
      $title = '';
      $link = '';

      if ($titleNode && $titleNode->length > 0) {
        $title = trim($titleNode->item(0)->textContent);
        $hrefAttr = $titleNode->item(0)->getAttribute('href');
        if (!empty($hrefAttr)) {
          $link = $hrefAttr;
        }
        else {
          // El heading puede no tener href; buscar el primer enlace dentro.
          $innerLink = $xpath->query(".//a/@href", $titleNode->item(0));
          if ($innerLink && $innerLink->length > 0) {
            $link = trim($innerLink->item(0)->nodeValue);
          }
        }
      }

      if (empty($title)) {
        continue;
      }

      // Construir URL completa si el enlace es relativo.
      if (!empty($link) && !str_starts_with($link, 'http')) {
        $link = rtrim('https://edpb.europa.eu', '/') . '/' . ltrim($link, '/');
      }

      if (empty($link)) {
        continue;
      }

      // Extraer fecha de publicacion del documento.
      // Buscar en: elementos time con atributo datetime, campos de fecha
      // Drupal, elementos ECL con clase date, texto con clase date.
      $dateNode = $xpath->query(
        ".//time[@datetime]"
        . " | .//*[contains(@class, 'ecl-date-block')]"
        . " | .//*[contains(@class, 'field--name-created')]"
        . " | .//*[contains(@class, 'field--name-field-date')]"
        . " | .//*[contains(@class, 'date')]",
        $entry,
      );
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
   *   'recomendacion', 'decision', 'declaracion', 'letter'.
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
    if (str_contains($titleLower, 'letter')) {
      return 'letter';
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

    // Intentar extraer patron "X/YYYY" del titulo.
    if (preg_match('/(\d+\/\d{2})/', $title, $matches)) {
      return 'EDPB-' . str_replace('/', '-', $matches[1]);
    }

    // Fallback: extraer slug de la ruta de la URL.
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $slug = basename($path);

    // Limpiar extensiones y sufijos de idioma del slug.
    $slug = preg_replace('/(_en|_es|_fr|_de)$/', '', $slug) ?? $slug;
    $slug = preg_replace('/\.\w+$/', '', $slug) ?? $slug;

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

    // Intentar formato europeo DD/MM/YYYY primero.
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $m)) {
      return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    // Intentar formato europeo DD.MM.YYYY.
    if (preg_match('#^(\d{2})\.(\d{2})\.(\d{4})$#', $date, $m)) {
      return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('Y-m-d', $timestamp) : '';
  }

}
