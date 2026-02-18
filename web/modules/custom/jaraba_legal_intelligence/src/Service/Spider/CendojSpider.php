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
   * Mapeo de codigos de organo judicial a nombres legibles.
   *
   * @var array<string, string>
   */
  protected const COURT_CODES = [
    'STS' => 'Tribunal Supremo',
    'SAN' => 'Audiencia Nacional',
    'STSJ' => 'Tribunal Superior de Justicia',
    'SAP' => 'Audiencia Provincial',
    'ATS' => 'Tribunal Supremo (Auto)',
    'AAN' => 'Audiencia Nacional (Auto)',
    'ATSJ' => 'Tribunal Superior de Justicia (Auto)',
    'AAP' => 'Audiencia Provincial (Auto)',
  ];

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
   * @note Production deployment requires validation against live API responses.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.cendoj.base_url') ?? 'https://www.poderjudicial.es/search';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-1 day'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');
    $maxResults = $options['max_results'] ?? 100;

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
    // Parametros del buscador indexAN.jsp del CENDOJ: fechas en formato
    // DD/MM/YYYY, NUM_REGISTRO para paginar, TIPO_DOC para filtrar tipo.
    // El buscador utiliza FECHA_RESOLUCION_DESDE y FECHA_RESOLUCION_HASTA
    // con formato DD/MM/YYYY para las fechas de resolucion.
    $dateFromFormatted = date('d/m/Y', strtotime($dateFrom));
    $dateToFormatted = date('d/m/Y', strtotime($dateTo));

    $searchUrl = $baseUrl . '/indexAN.jsp?' . http_build_query([
      'FECHA_RESOLUCION_DESDE' => $dateFromFormatted,
      'FECHA_RESOLUCION_HASTA' => $dateToFormatted,
      'NUM_REGISTRO' => $maxResults,
      'TIPO_DOC' => 'sentencias',
      'IDIOMA' => 'es',
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
   * AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
   * Parsea la pagina de resultados del buscador del CENDOJ utilizando
   * DOMDocument y DOMXPath. La estructura del CENDOJ presenta resultados
   * en un contenedor principal con clase 'listadoDocumentos'. Cada resultado
   * es un bloque con la informacion de la resolucion organizada en campos
   * etiquetados: ROJ, organo, fecha, jurisdiccion, ponente, etc.
   *
   * @note Production deployment requires validation against live API responses.
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

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
    // Usar DOMDocument para extraer entradas de resultados del buscador.
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
    // El CENDOJ presenta resultados en un contenedor 'listadoDocumentos'.
    // Cada resultado se encuentra dentro de un bloque con id que comienza
    // por 'ROJ' o dentro de divs con clase 'documento'. Tambien se buscan
    // filas de tabla en la seccion de resultados y bloques 'resultado'.
    $entries = $xpath->query(
      "//div[contains(@class, 'listadoDocumentos')]//div[contains(@class, 'documento')]"
      . " | //div[contains(@id, 'ROJ')]"
      . " | //table[contains(@class, 'resultado')]//tr[position() > 1]"
      . " | //div[contains(@class, 'resultado')]"
    );

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('CENDOJ spider: No se encontraron entradas en la respuesta HTML. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
      // Extraer datos de cada nodo DOM del resultado CENDOJ.

      // Titulo: enlace principal del resultado, contenido en <a> dentro
      // de h3, h4, o un enlace con clase 'titulo' o 'doctrina'.
      $titleNode = $xpath->query(
        ".//h3//a | .//h4//a | .//a[contains(@class, 'titulo')]"
        . " | .//a[contains(@class, 'doctrina')]"
        . " | .//span[contains(@class, 'titulo')]//a"
        . " | .//a[1]",
        $entry,
      );
      $title = '';
      $originalUrl = '';
      if ($titleNode && $titleNode->length > 0) {
        $title = trim($titleNode->item(0)->textContent);
        $hrefAttr = $titleNode->item(0)->getAttribute('href');
        if (!empty($hrefAttr)) {
          // Resolver URLs relativas al dominio del CENDOJ.
          $originalUrl = str_starts_with($hrefAttr, 'http')
            ? $hrefAttr
            : 'https://www.poderjudicial.es' . (str_starts_with($hrefAttr, '/') ? '' : '/') . $hrefAttr;
        }
      }

      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
      // Extraer ROJ como external_ref. El ROJ se encuentra en un campo
      // etiquetado como 'ROJ:', con formato ROJ: STS 1234/2024. Se busca
      // en elementos con clase 'roj', 'ROJ', o mediante regex en el texto.
      $externalRef = '';
      $refNode = $xpath->query(
        ".//*[contains(@class, 'roj')] | .//*[contains(@class, 'ROJ')]"
        . " | .//span[contains(text(), 'ROJ')]/.."
        . " | .//td[contains(text(), 'ROJ')]",
        $entry,
      );
      if ($refNode && $refNode->length > 0) {
        $refText = trim($refNode->item(0)->textContent);
        // Limpiar prefijo 'ROJ:' si existe.
        $externalRef = preg_replace('/^ROJ:\s*/', '', $refText) ?? $refText;
        $externalRef = trim($externalRef);
      }

      // Fallback: buscar patron ROJ en el texto completo del bloque.
      if (empty($externalRef)) {
        $blockText = $entry->textContent;
        if (preg_match('/ROJ:\s*((?:STS|SAN|STSJ|SAP|ATS|AAN|ATSJ|AAP)\s+\d+\/\d{4})/', $blockText, $rojMatch)) {
          $externalRef = trim($rojMatch[1]);
        }
      }

      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
      // Extraer organo emisor (TS, AN, TSJ, AP). Se busca en un campo
      // etiquetado 'Organo:' o similar, o se infiere del codigo ROJ.
      $issuingBody = '';
      $bodyNode = $xpath->query(
        ".//*[contains(@class, 'organo')]"
        . " | .//span[contains(text(), 'rgano')]/.."
        . " | .//td[contains(text(), 'rgano')]",
        $entry,
      );
      if ($bodyNode && $bodyNode->length > 0) {
        $bodyText = trim($bodyNode->item(0)->textContent);
        // Limpiar la etiqueta 'Organo:' si existe.
        $issuingBody = preg_replace('/^.*[Oo]rgano:\s*/', '', $bodyText) ?? $bodyText;
        $issuingBody = trim($issuingBody);
      }

      // Fallback: inferir organo del codigo ROJ.
      if (empty($issuingBody) && !empty($externalRef)) {
        $issuingBody = $this->inferCourtFromRoj($externalRef);
      }

      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
      // Extraer fecha de la resolucion. El CENDOJ muestra la fecha en
      // formato DD/MM/YYYY en un campo etiquetado 'Fecha:' o similar.
      $dateIssued = '';
      $dateNode = $xpath->query(
        ".//*[contains(@class, 'fecha')]"
        . " | .//span[contains(text(), 'Fecha')]/.."
        . " | .//td[contains(text(), 'Fecha')]",
        $entry,
      );
      if ($dateNode && $dateNode->length > 0) {
        $dateText = trim($dateNode->item(0)->textContent);
        // Limpiar la etiqueta 'Fecha:' si existe.
        $dateText = preg_replace('/^.*[Ff]echa:\s*/', '', $dateText) ?? $dateText;
        $dateText = trim($dateText);
        // Normalizar formato DD/MM/YYYY a Y-m-d.
        if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dateText, $dm)) {
          $dateIssued = $dm[3] . '-' . $dm[2] . '-' . $dm[1];
        }
        else {
          $ts = strtotime($dateText);
          $dateIssued = $ts ? date('Y-m-d', $ts) : $dateText;
        }
      }

      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for CENDOJ.
      // Extraer jurisdiccion y tipo de resolucion. El CENDOJ clasifica
      // las resoluciones por jurisdiccion (civil, penal, contencioso-
      // administrativo, social, militar) y tipo (sentencia, auto, etc.).
      $jurisdiction = '';
      $jurisdictionNode = $xpath->query(
        ".//*[contains(@class, 'jurisdiccion')]"
        . " | .//span[contains(text(), 'Jurisdicci')]/.."
        . " | .//td[contains(text(), 'Jurisdicci')]",
        $entry,
      );
      if ($jurisdictionNode && $jurisdictionNode->length > 0) {
        $jurText = trim($jurisdictionNode->item(0)->textContent);
        $jurText = preg_replace('/^.*[Jj]urisdicci[oó]n:\s*/', '', $jurText) ?? $jurText;
        $jurisdiction = mb_strtolower(trim($jurText));
      }

      // Determinar tipo de resolucion: sentencia o auto (del ROJ).
      $resolutionType = 'sentencia';
      if (!empty($externalRef)) {
        if (preg_match('/^A/', $externalRef)) {
          $resolutionType = 'auto';
        }
      }

      // Extraer tipo de resolucion de un campo explicito si existe.
      $typeNode = $xpath->query(
        ".//*[contains(@class, 'tipo')]"
        . " | .//span[contains(text(), 'Tipo')]/.."
        . " | .//td[contains(text(), 'Tipo')]",
        $entry,
      );
      if ($typeNode && $typeNode->length > 0) {
        $typeText = trim($typeNode->item(0)->textContent);
        $typeText = preg_replace('/^.*[Tt]ipo[^:]*:\s*/', '', $typeText) ?? $typeText;
        $typeText = mb_strtolower(trim($typeText));
        if (str_contains($typeText, 'auto')) {
          $resolutionType = 'auto';
        }
        elseif (str_contains($typeText, 'sentencia')) {
          $resolutionType = 'sentencia';
        }
      }

      if (empty($externalRef) || empty($title)) {
        continue;
      }

      $resolutions[] = [
        'source_id' => 'cendoj',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => $resolutionType,
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

  /**
   * Infiere el organo judicial a partir del codigo ROJ.
   *
   * El formato ROJ codifica el tribunal emisor en las letras iniciales:
   * STS = Tribunal Supremo, SAN = Audiencia Nacional, STSJ = Tribunal
   * Superior de Justicia, SAP = Audiencia Provincial. Los autos utilizan
   * A como prefijo (ATS, AAN, ATSJ, AAP).
   *
   * @param string $roj
   *   Codigo ROJ sin el prefijo 'ROJ:', por ejemplo 'STS 1234/2024'.
   *
   * @return string
   *   Nombre del organo judicial, o cadena vacia si no se puede inferir.
   */
  protected function inferCourtFromRoj(string $roj): string {
    // Extraer el prefijo del codigo ROJ (antes del espacio).
    if (preg_match('/^(STS|SAN|STSJ|SAP|ATS|AAN|ATSJ|AAP)\b/', $roj, $matches)) {
      return self::COURT_CODES[$matches[1]] ?? '';
    }
    return '';
  }

}
