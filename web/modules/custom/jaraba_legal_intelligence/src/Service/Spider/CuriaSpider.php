<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para CURIA (Tribunal de Justicia de la Union Europea).
 *
 * ESTRUCTURA:
 * Conector que extrae jurisprudencia del buscador de CURIA, portal oficial del
 * TJUE. Accede a sentencias, conclusiones del Abogado General y autos del
 * Tribunal de Justicia (TJ), Tribunal General (TG) y Tribunal de la Funcion
 * Publica (TFP). Cubre cuestiones prejudiciales, recursos por incumplimiento,
 * recursos de anulacion, recursos de casacion y dictamenes.
 *
 * LOGICA:
 * Construye URL de busqueda con parametros de fecha contra el formulario de
 * CURIA. Parsea el HTML para extraer entradas de resoluciones (numero de
 * asunto, ECLI, tipo de documento, fecha, procedimiento y Abogado General).
 * El texto completo se extrae via Apache Tika en el pipeline NLP.
 *
 * RELACIONES:
 * - CuriaSpider -> SpiderInterface: implementa el contrato del spider.
 * - CuriaSpider -> GuzzleHttp\ClientInterface: peticiones HTTP a CURIA.
 * - CuriaSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources.
 * - CuriaSpider <- LegalIngestionService: invocado via crawl().
 *
 * SINTAXIS:
 * Servicio registrado como jaraba_legal_intelligence.spider.curia.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 */
class CuriaSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones al buscador de CURIA.
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
   * Construye una nueva instancia de CuriaSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones al buscador de CURIA.
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
    return 'curia';
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
    return $sourceId === 'curia';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de jurisprudencia del TJUE via buscador de CURIA.
   *
   * @todo Refinar parseo HTML con el formato real de respuesta de CURIA.
   *   Scaffold basado en la estructura conocida del buscador del TJUE.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.curia.base_url') ?? 'https://curia.europa.eu/juris/';

    $dateFrom = $options['date_from'] ?? date('d/m/Y', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('d/m/Y');

    // Normalizar formato de fechas a DD/MM/YYYY para CURIA.
    $dateFrom = $this->normalizeDateForCuria($dateFrom);
    $dateTo = $this->normalizeDateForCuria($dateTo);

    // Construir URL de busqueda con parametros del formulario de CURIA.
    // TODO: Ajustar parametros al formato exacto del buscador CURIA.
    $searchUrl = $baseUrl . 'liste.jsf?' . http_build_query([
      'td' => 'ALL',
      'dates' => $dateFrom . ' - ' . $dateTo,
      'language' => 'es',
      'jur' => 'C',
      'page' => 1,
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

      $this->logger->info('CURIA spider: @count resoluciones extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('CURIA spider: Error HTTP al rastrear @url: @message', [
        '@url' => $searchUrl,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('CURIA spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea la respuesta HTML del buscador de CURIA y extrae resoluciones.
   *
   * TODO: Scaffold. Refinar selectores XPath con la estructura DOM real.
   *
   * @param string $html
   *   Contenido HTML de la respuesta del buscador de CURIA.
   *
   * @return array
   *   Array de arrays asociativos con datos crudos de cada resolucion.
   */
  protected function parseResponse(string $html): array {
    $resolutions = [];
    if (empty($html)) {
      return $resolutions;
    }

    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // TODO: Ajustar selectores al formato real de CURIA.
    $entries = $xpath->query("//table[contains(@class, 'detail_table_documents')]//tr[position() > 1]");

    // Si no hay entradas en formato tabla, intentar con divs.
    if ($entries === FALSE || $entries->length === 0) {
      $entries = $xpath->query("//div[contains(@class, 'result_list')]//div[contains(@class, 'result')]");
    }

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('CURIA spider: No se encontraron entradas en la respuesta HTML. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // Numero de asunto (ej: C-415/11).
      $caseNumber = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_aff')]")
        ?: $this->extractText($xpath, $entry, ".//span[contains(@class, 'affaire')]");

      // Identificador ECLI.
      $ecli = $this->extractEcli($xpath, $entry);

      if (empty($caseNumber) && empty($ecli)) {
        continue;
      }

      // Titulo o asunto.
      $title = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_nom_usuel')]")
        ?: $this->extractText($xpath, $entry, ".//span[contains(@class, 'nom_usuel')]");

      // Fecha — normalizar a Y-m-d.
      $dateIssued = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_date')]")
        ?: $this->extractText($xpath, $entry, ".//span[contains(@class, 'date')]");
      $dateIssued = $this->normalizeDateFromCuria($dateIssued);

      // Tipo de documento (Sentencia, Conclusiones, Auto).
      $docType = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_type')]")
        ?: $this->extractText($xpath, $entry, ".//span[contains(@class, 'type_doc')]");

      // Tipo de procedimiento.
      $procedureType = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_type_procedure')]")
        ?: $this->extractText($xpath, $entry, ".//span[contains(@class, 'type_procedure')]");

      // Abogado General.
      $advocateGeneral = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_avocat_general')]")
        ?: $this->extractText($xpath, $entry, ".//span[contains(@class, 'avocat_general')]");

      // URL del documento original.
      $originalUrl = $this->extractOriginalUrl($xpath, $entry);

      if (empty($title)) {
        $title = $this->buildFallbackTitle($docType, $caseNumber, $dateIssued);
      }

      $resolutions[] = [
        'source_id' => 'tjue',
        'external_ref' => $ecli ?: $caseNumber,
        'title' => $title,
        'resolution_type' => $this->mapDocumentType($docType),
        'issuing_body' => 'TJUE',
        'jurisdiction' => $this->mapProcedureType($procedureType),
        'date_issued' => $dateIssued,
        'original_url' => $originalUrl,
        'full_text' => '',
        // Campos especificos de la UE.
        'ecli' => $ecli,
        'case_number' => $caseNumber,
        'procedure_type' => $procedureType,
        'advocate_general' => $advocateGeneral,
        'language_original' => 'es',
      ];
    }

    libxml_clear_errors();
    return $resolutions;
  }

  /**
   * Extrae texto de un nodo DOM via XPath relativo al contexto.
   */
  protected function extractText(\DOMXPath $xpath, \DOMNode $context, string $expression): string {
    $nodes = $xpath->query($expression, $context);
    if ($nodes && $nodes->length > 0) {
      return trim($nodes->item(0)->textContent);
    }
    return '';
  }

  /**
   * Extrae el identificador ECLI de una entrada de resultados de CURIA.
   * Formato ECLI:EU:C:YYYY:NNN. Busca en DOM y via regex en el texto.
   */
  protected function extractEcli(\DOMXPath $xpath, \DOMNode $entry): string {
    $ecli = $this->extractText($xpath, $entry, ".//td[contains(@class, 'table_cell_ecli')]");
    if (!empty($ecli)) {
      return trim($ecli);
    }
    $ecli = $this->extractText($xpath, $entry, ".//span[contains(@class, 'ecli')]");
    if (!empty($ecli)) {
      return trim($ecli);
    }
    // Buscar patron ECLI:EU:* en el texto completo de la entrada.
    if (preg_match('/ECLI:EU:[A-Z]:\d{4}:\d+/', $entry->textContent, $matches)) {
      return $matches[0];
    }
    return '';
  }

  /**
   * Extrae la URL del documento original de una entrada de CURIA.
   * TODO: Ajustar selectores XPath al formato real de CURIA.
   */
  protected function extractOriginalUrl(\DOMXPath $xpath, \DOMNode $entry): string {
    $linkNodes = $xpath->query(".//td[contains(@class, 'table_cell_aff')]//a/@href", $entry);
    if ($linkNodes && $linkNodes->length > 0) {
      return $this->resolveUrl(trim($linkNodes->item(0)->nodeValue));
    }
    $linkNodes = $xpath->query(".//span[contains(@class, 'affaire')]//a/@href", $entry);
    if ($linkNodes && $linkNodes->length > 0) {
      return $this->resolveUrl(trim($linkNodes->item(0)->nodeValue));
    }
    $linkNodes = $xpath->query(".//a[contains(@href, 'document')]/@href", $entry);
    if ($linkNodes && $linkNodes->length > 0) {
      return $this->resolveUrl(trim($linkNodes->item(0)->nodeValue));
    }
    return '';
  }

  /**
   * Resuelve una URL relativa contra la base de CURIA.
   */
  protected function resolveUrl(string $url): string {
    if (empty($url) || str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
      return $url;
    }
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.curia.base_url') ?? 'https://curia.europa.eu/juris/';
    if (str_starts_with($url, '/')) {
      $parsed = parse_url($baseUrl);
      return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'curia.europa.eu') . $url;
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
  }

  /**
   * Mapea el tipo de documento de CURIA al tipo de resolucion del sistema.
   *
   * Sentencia/Judgment/Arret -> sentencia_tjue, Conclusiones/Opinion ->
   * opinion_ag, Auto/Order -> auto, default -> resolucion.
   */
  protected function mapDocumentType(string $type): string {
    $type = mb_strtolower(trim($type));
    return match (TRUE) {
      str_contains($type, 'sentencia'),
      str_contains($type, 'judgment'),
      str_contains($type, 'arret') => 'sentencia_tjue',
      str_contains($type, 'conclusiones'),
      str_contains($type, 'opinion') => 'opinion_ag',
      str_contains($type, 'auto'),
      str_contains($type, 'order') => 'auto',
      default => 'resolucion',
    };
  }

  /**
   * Mapea el tipo de procedimiento de CURIA a la jurisdiccion del sistema.
   *
   * Todos los procedimientos del TJUE (prejudicial, incumplimiento,
   * anulacion) se clasifican bajo jurisdiccion 'eu_general'.
   */
  protected function mapProcedureType(string $type): string {
    $type = mb_strtolower(trim($type));
    return match (TRUE) {
      str_contains($type, 'prejudicial'),
      str_contains($type, 'preliminary') => 'eu_general',
      str_contains($type, 'incumplimiento'),
      str_contains($type, 'infringement') => 'eu_general',
      str_contains($type, 'anulacion'),
      str_contains($type, 'annulment') => 'eu_general',
      default => 'eu_general',
    };
  }

  /**
   * Normaliza fecha al formato DD/MM/YYYY para consultas a CURIA.
   */
  protected function normalizeDateForCuria(string $date): string {
    if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $date)) {
      return $date;
    }
    $timestamp = strtotime($date);
    if ($timestamp !== FALSE) {
      return date('d/m/Y', $timestamp);
    }
    return $date;
  }

  /**
   * Normaliza fecha extraida de CURIA al formato Y-m-d.
   */
  protected function normalizeDateFromCuria(string $date): string {
    if (empty($date)) {
      return '';
    }
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $date, $m)) {
      return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    if (preg_match('#^(\d{2})\.(\d{2})\.(\d{4})$#', $date, $m)) {
      return $m[3] . '-' . $m[2] . '-' . $m[1];
    }
    $timestamp = strtotime($date);
    if ($timestamp !== FALSE) {
      return date('Y-m-d', $timestamp);
    }
    return $date;
  }

  /**
   * Construye un titulo descriptivo por defecto para una resolucion.
   */
  protected function buildFallbackTitle(string $docType, string $caseNumber, string $dateIssued): string {
    $parts = [];
    $parts[] = !empty($docType) ? $docType : 'Resolucion TJUE';
    if (!empty($caseNumber)) {
      $parts[] = 'Asunto ' . $caseNumber;
    }
    if (!empty($dateIssued)) {
      $parts[] = 'de ' . $dateIssued;
    }
    return implode(' - ', $parts);
  }

}
