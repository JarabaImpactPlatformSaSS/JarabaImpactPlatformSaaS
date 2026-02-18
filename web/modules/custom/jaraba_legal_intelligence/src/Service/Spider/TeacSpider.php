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
   * @note Production deployment requires validation against live API responses.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.teac.base_url') ?? 'https://serviciostelematicos.minhap.gob.es/DYCteac';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for TEAC/DYCteac.
    // El sistema DYCteac del Ministerio de Hacienda expone un buscador de
    // resoluciones con parametros de formulario. Las fechas se envian en
    // formato DD/MM/YYYY. El endpoint buscarResolucion acepta parametros
    // GET para fechaDesde, fechaHasta, criterio (texto libre), concepto
    // (impuesto) y voces (terminos de indice). La respuesta es HTML con
    // una tabla de resultados paginada.
    $dateFromFormatted = date('d/m/Y', strtotime($dateFrom));
    $dateToFormatted = date('d/m/Y', strtotime($dateTo));

    $searchUrl = rtrim($baseUrl, '/') . '/buscarResolucion.html?' . http_build_query([
      'fechaDesde' => $dateFromFormatted,
      'fechaHasta' => $dateToFormatted,
      'criterio' => '',
      'concepto' => '',
      'voces' => '',
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
   * AUDIT-TODO-RESOLVED: Implemented DOM parsing for TEAC/DYCteac.
   * El sistema telematico del TEAC presenta resoluciones en una tabla HTML
   * con columnas: Num. Resolucion, Concepto/Criterio, Fecha, Sala/Ponente.
   * La tabla puede identificarse por clase 'resultados', 'listado', o por
   * su posicion en la pagina. Los enlaces al texto completo de cada
   * resolucion apuntan a documentos PDF dentro del mismo sistema DYCteac.
   *
   * @note Production deployment requires validation against live API responses.
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

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for TEAC/DYCteac.
    // Usar DOMDocument para extraer entradas de resoluciones del TEAC.
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for TEAC/DYCteac.
    // Selectores XPath para la tabla de resoluciones del DYCteac.
    // El sistema presenta resoluciones en filas de tabla con diversas
    // posibles clases: 'resultados', 'listado', 'resoluciones'.
    // Se buscan filas (tr) excluyendo la primera fila de cabecera.
    // Tambien se buscan bloques div con clase 'resolucion' como fallback.
    $rows = $xpath->query(
      "//table[contains(@class, 'resultado')]//tr[position() > 1]"
      . " | //table[contains(@class, 'listado')]//tr[position() > 1]"
      . " | //table[contains(@class, 'resoluciones')]//tr[position() > 1]"
      . " | //div[contains(@class, 'resolucion')]"
    );

    // Fallback: buscar cualquier tabla significativa en la pagina.
    if ($rows === FALSE || $rows->length === 0) {
      $rows = $xpath->query(
        "//table[.//th or .//thead]//tr[position() > 1]"
      );
    }

    if ($rows === FALSE || $rows->length === 0) {
      $this->logger->notice('TEAC spider: No se encontraron resoluciones en la respuesta. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($rows as $row) {
      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for TEAC/DYCteac.
      // Extraer datos de cada fila de la tabla de resoluciones.
      // Estructura esperada de las celdas:
      //   td[0]: Numero de resolucion (referencia externa, ej: 00/01234/2024)
      //   td[1]: Concepto / Criterio (descripcion de la doctrina aplicada)
      //   td[2]: Fecha de resolucion (formato DD/MM/YYYY)
      //   td[3]: Sala / Vocalias / Ponente (opcional)
      // Los div con clase 'resolucion' usan campos semanticos con clases.
      $cells = $xpath->query(".//td", $row);

      $externalRef = '';
      $criterio = '';
      $dateIssued = '';
      $sala = '';

      if ($cells && $cells->length >= 3) {
        // Formato tabla: celdas con datos posicionales.
        $externalRef = trim($cells->item(0)->textContent);
        $criterio = trim($cells->item(1)->textContent);
        $dateIssued = trim($cells->item(2)->textContent);

        // Sala/Ponente en celda 4 si existe.
        if ($cells->length > 3) {
          $sala = trim($cells->item(3)->textContent);
        }
      }
      else {
        // Formato bloque div: buscar por clases semanticas.
        $refNode = $xpath->query(
          ".//*[contains(@class, 'numero')]"
          . " | .//*[contains(@class, 'referencia')]"
          . " | .//span[contains(@class, 'num')]",
          $row,
        );
        if ($refNode && $refNode->length > 0) {
          $externalRef = trim($refNode->item(0)->textContent);
        }

        $critNode = $xpath->query(
          ".//*[contains(@class, 'criterio')]"
          . " | .//*[contains(@class, 'concepto')]"
          . " | .//*[contains(@class, 'descripcion')]",
          $row,
        );
        if ($critNode && $critNode->length > 0) {
          $criterio = trim($critNode->item(0)->textContent);
        }

        $dateNode = $xpath->query(
          ".//*[contains(@class, 'fecha')]",
          $row,
        );
        if ($dateNode && $dateNode->length > 0) {
          $dateIssued = trim($dateNode->item(0)->textContent);
        }

        $salaNode = $xpath->query(
          ".//*[contains(@class, 'sala')]"
          . " | .//*[contains(@class, 'ponente')]",
          $row,
        );
        if ($salaNode && $salaNode->length > 0) {
          $sala = trim($salaNode->item(0)->textContent);
        }
      }

      // Normalizar fecha DD/MM/YYYY a Y-m-d.
      if (!empty($dateIssued) && preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dateIssued, $dm)) {
        $dateIssued = $dm[3] . '-' . $dm[2] . '-' . $dm[1];
      }
      elseif (!empty($dateIssued)) {
        $ts = strtotime($dateIssued);
        $dateIssued = $ts ? date('Y-m-d', $ts) : $dateIssued;
      }

      // Extraer URL del enlace al texto completo.
      $linkNode = $xpath->query(".//a/@href", $row);
      $originalUrl = '';
      if ($linkNode && $linkNode->length > 0) {
        $href = trim($linkNode->item(0)->nodeValue);
        // Convertir URLs relativas a absolutas.
        $originalUrl = str_starts_with($href, 'http') ? $href : rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
      }

      if (empty($externalRef)) {
        continue;
      }

      // Construir titulo descriptivo a partir del criterio y referencia.
      $title = !empty($criterio)
        ? sprintf('Resolucion TEAC %s - %s', $externalRef, $criterio)
        : sprintf('Resolucion TEAC %s', $externalRef);

      // Anotar la sala/ponente en el titulo si esta disponible.
      if (!empty($sala)) {
        $title .= ' [' . $sala . ']';
      }

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
