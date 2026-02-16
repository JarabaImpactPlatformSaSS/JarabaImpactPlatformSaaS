<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para EUR-Lex (Oficina de Publicaciones de la Union Europea).
 *
 * ESTRUCTURA:
 * Conector que extrae legislacion y jurisprudencia europea del repositorio
 * Cellar de la Oficina de Publicaciones de la UE a traves de su endpoint
 * SPARQL. Accede a directivas (DIR), reglamentos (REG), decisiones (DEC) y
 * sentencias del TJUE (JUDG) utilizando la ontologia CDM (Common Data Model)
 * que estructura todos los actos juridicos publicados en EUR-Lex. Solicita
 * preferentemente la expresion en lengua espanola de cada acto.
 *
 * LOGICA:
 * Construye una consulta SPARQL contra el endpoint publico del Cellar
 * (publications.europa.eu/webapi/rdf/sparql) utilizando los predicados CDM
 * para filtrar por tipo de recurso, rango de fechas y disponibilidad en
 * espanol. Parsea la respuesta JSON (application/sparql-results+json) para
 * extraer los bindings con CELEX, titulo, fecha, tipo y estado de vigencia.
 * Cada resultado se mapea a la estructura normalizada del pipeline de ingesta.
 * El texto completo se obtendra en fase posterior via el servicio REST de
 * EUR-Lex o via Apache Tika sobre el PDF del acto. En caso de error HTTP o
 * de parseo SPARQL, registra el error y devuelve array vacio.
 *
 * RELACIONES:
 * - EurLexSpider -> SpiderInterface: implementa el contrato del spider.
 * - EurLexSpider -> GuzzleHttp\ClientInterface: peticiones HTTP al endpoint
 *   SPARQL del Cellar.
 * - EurLexSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url del endpoint SPARQL configurada.
 * - EurLexSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada semanal de fuentes europeas (Fase 4).
 * - EurLexSpider -> LegalResolution: los datos extraidos se normalizan y
 *   persisten como entidades LegalResolution con source_id 'eurlex'.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.eurlex.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 * Frecuencia de ejecucion: semanal (weekly), dado el volumen y estabilidad
 * de las publicaciones del Diario Oficial de la Union Europea.
 */
class EurLexSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones al endpoint SPARQL del Cellar.
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
   * Construye una nueva instancia de EurLexSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones al endpoint SPARQL del Cellar.
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
    return 'eurlex';
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
    return $sourceId === 'eurlex';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de legislacion y jurisprudencia europea via SPARQL.
   *
   * Construye una consulta SPARQL contra el endpoint del Cellar de la Oficina
   * de Publicaciones de la UE. Filtra por tipo de recurso (directivas,
   * reglamentos, decisiones, sentencias TJUE), rango de fechas y
   * disponibilidad de la expresion en espanol. Parsea la respuesta JSON
   * SPARQL para extraer CELEX, titulo, fecha, tipo y estado de vigencia.
   *
   * Por defecto consulta los ultimos 7 dias con un limite de 500 resultados.
   * Estos valores son configurables via las opciones 'date_from', 'date_to'
   * y 'max_results'.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.eurlex.base_url') ?? 'https://publications.europa.eu/webapi/rdf/sparql';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');
    $maxResults = $options['max_results'] ?? 500;

    // Construir consulta SPARQL utilizando la ontologia CDM.
    // Se consultan directivas (DIR), reglamentos (REG), decisiones (DEC)
    // y sentencias del TJUE (JUDG), filtrando por fecha y solicitando
    // la expresion en espanol cuando este disponible.
    $sparql = $this->buildSparqlQuery($dateFrom, $dateTo, (int) $maxResults);

    try {
      $response = $this->httpClient->request('GET', $baseUrl, [
        'query' => [
          'query' => $sparql,
          'format' => 'application/json',
        ],
        'timeout' => 60,
        'headers' => [
          'Accept' => 'application/sparql-results+json',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $json = (string) $response->getBody();
      $resolutions = $this->parseSparqlResponse($json);

      $this->logger->info('EUR-Lex spider: @count resoluciones extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('EUR-Lex spider: Error HTTP al consultar SPARQL en @url: @message', [
        '@url' => $baseUrl,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('EUR-Lex spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Construye la consulta SPARQL para el endpoint del Cellar.
   *
   * Utiliza la ontologia CDM (Common Data Model) de la Oficina de
   * Publicaciones para consultar actos juridicos publicados en EUR-Lex.
   * Filtra por tipo de recurso (directivas, reglamentos, decisiones y
   * sentencias del TJUE), rango de fechas del documento y disponibilidad
   * de la expresion en lengua espanola.
   *
   * Campos solicitados:
   * - ?work: URI del trabajo (work) en el Cellar.
   * - ?celex: Numero CELEX del acto juridico.
   * - ?title: Titulo en espanol de la expresion.
   * - ?date: Fecha del documento.
   * - ?rtype: URI del tipo de recurso (DIR, REG, DEC, JUDG).
   * - ?force: Estado de vigencia (in-force) del acto.
   *
   * @param string $dateFrom
   *   Fecha de inicio del rango en formato Y-m-d.
   * @param string $dateTo
   *   Fecha de fin del rango en formato Y-m-d.
   * @param int $maxResults
   *   Numero maximo de resultados a devolver.
   *
   * @return string
   *   Consulta SPARQL lista para enviar al endpoint.
   */
  protected function buildSparqlQuery(string $dateFrom, string $dateTo, int $maxResults): string {
    return <<<SPARQL
PREFIX cdm: <http://publications.europa.eu/ontology/cdm#>
PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

SELECT DISTINCT ?work ?celex ?title ?date ?rtype ?force
WHERE {
  ?work cdm:work_has_resource-type ?rtype .
  FILTER(?rtype IN (
    <http://publications.europa.eu/resource/authority/resource-type/DIR>,
    <http://publications.europa.eu/resource/authority/resource-type/REG>,
    <http://publications.europa.eu/resource/authority/resource-type/DEC>,
    <http://publications.europa.eu/resource/authority/resource-type/JUDG>
  ))
  ?work cdm:resource_legal_id_celex ?celex .
  ?work cdm:work_date_document ?date .
  FILTER(?date >= "{$dateFrom}"^^xsd:date && ?date <= "{$dateTo}"^^xsd:date)
  OPTIONAL { ?work cdm:resource_legal_in-force ?force . }
  ?expr cdm:expression_belongs_to_work ?work .
  ?expr cdm:expression_uses_language <http://publications.europa.eu/resource/authority/language/SPA> .
  ?expr cdm:expression_title ?title .
}
ORDER BY DESC(?date)
LIMIT {$maxResults}
SPARQL;
  }

  /**
   * Parsea la respuesta JSON SPARQL y extrae resoluciones normalizadas.
   *
   * Procesa la estructura estandar de application/sparql-results+json
   * (RFC: SPARQL 1.1 Query Results JSON Format). Itera sobre los bindings
   * del resultado y mapea cada fila a la estructura normalizada del pipeline
   * de ingesta del Legal Intelligence Hub.
   *
   * Descarta filas sin CELEX o sin titulo, ya que son campos obligatorios
   * para la creacion de la entidad LegalResolution.
   *
   * @param string $json
   *   Contenido JSON de la respuesta SPARQL del Cellar.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   *   Cada elemento incluye los campos estandar del pipeline mas campos
   *   especificos de EUR-Lex: celex_number, status_legal, language_original.
   */
  protected function parseSparqlResponse(string $json): array {
    $resolutions = [];

    if (empty($json)) {
      return $resolutions;
    }

    $data = json_decode($json, TRUE);
    if (!is_array($data) || empty($data['results']['bindings'])) {
      $this->logger->notice('EUR-Lex spider: No se encontraron resultados en la respuesta SPARQL.');
      return $resolutions;
    }

    foreach ($data['results']['bindings'] as $row) {
      $celex = $row['celex']['value'] ?? '';
      $title = $row['title']['value'] ?? '';

      // CELEX y titulo son campos obligatorios para crear LegalResolution.
      if (empty($celex) || empty($title)) {
        continue;
      }

      $inForce = ($row['force']['value'] ?? 'true') === 'true';
      $resourceType = $row['rtype']['value'] ?? '';

      $resolutions[] = [
        'source_id' => 'eurlex',
        'external_ref' => $celex,
        'title' => $title,
        'resolution_type' => $this->mapResourceType($resourceType),
        'issuing_body' => $this->mapIssuingBody($resourceType),
        'jurisdiction' => $this->mapJurisdiction($resourceType),
        'date_issued' => $row['date']['value'] ?? '',
        'original_url' => sprintf(
          'https://eur-lex.europa.eu/legal-content/ES/TXT/?uri=CELEX:%s',
          $celex
        ),
        // Texto completo se extrae posteriormente via EUR-Lex REST API
        // o Apache Tika sobre el PDF del acto juridico.
        'full_text' => '',
        // Campos especificos de EUR-Lex.
        'celex_number' => $celex,
        'status_legal' => $inForce ? 'vigente' : 'derogada',
        'language_original' => 'es',
      ];
    }

    return $resolutions;
  }

  /**
   * Mapea la URI de tipo de recurso CDM al tipo de resolucion normalizado.
   *
   * Convierte las URIs del vocabulario controlado de tipos de recurso de la
   * Oficina de Publicaciones a los valores normalizados utilizados en el
   * campo resolution_type de LegalResolution.
   *
   * Mapeo:
   * - DIR (Directiva) -> 'directiva'
   * - REG (Reglamento) -> 'reglamento'
   * - DEC (Decision) -> 'decision'
   * - JUDG (Sentencia TJUE) -> 'sentencia_tjue'
   * - Otros -> 'otro'
   *
   * @param string $uri
   *   URI completa del tipo de recurso CDM.
   *
   * @return string
   *   Tipo de resolucion normalizado.
   */
  protected function mapResourceType(string $uri): string {
    if (str_contains($uri, 'DIR')) {
      return 'directiva';
    }
    if (str_contains($uri, 'REG')) {
      return 'reglamento';
    }
    if (str_contains($uri, 'DEC')) {
      return 'decision';
    }
    if (str_contains($uri, 'JUDG')) {
      return 'sentencia_tjue';
    }

    return 'otro';
  }

  /**
   * Mapea la URI de tipo de recurso CDM al organo emisor.
   *
   * Determina el organo emisor del acto juridico en funcion del tipo de
   * recurso. Las directivas y reglamentos se atribuyen al Parlamento Europeo
   * y Consejo (procedimiento legislativo ordinario), las decisiones a la
   * Comision Europea, y las sentencias al Tribunal de Justicia de la Union
   * Europea (TJUE).
   *
   * Nota: Esta es una aproximacion simplificada. En la realidad, directivas
   * y reglamentos pueden ser emitidos solo por el Consejo o solo por la
   * Comision (actos delegados/de ejecucion). Un refinamiento futuro puede
   * extraer el autor real desde los metadatos CDM (cdm:work_created_by_agent).
   *
   * @param string $uri
   *   URI completa del tipo de recurso CDM.
   *
   * @return string
   *   Nombre del organo emisor.
   */
  protected function mapIssuingBody(string $uri): string {
    if (str_contains($uri, 'DIR') || str_contains($uri, 'REG')) {
      return 'Parlamento Europeo y Consejo';
    }
    if (str_contains($uri, 'DEC')) {
      return 'Comision Europea';
    }
    if (str_contains($uri, 'JUDG')) {
      return 'TJUE';
    }

    return 'Union Europea';
  }

  /**
   * Mapea la URI de tipo de recurso CDM a la jurisdiccion.
   *
   * Clasifica la jurisdiccion del acto juridico segun su tipo. Los actos
   * legislativos (directivas, reglamentos, decisiones) se clasifican como
   * 'eu_legislacion', mientras que las sentencias del TJUE se clasifican
   * como 'eu_general' (jurisdiccion judicial europea).
   *
   * @param string $uri
   *   URI completa del tipo de recurso CDM.
   *
   * @return string
   *   Identificador de jurisdiccion normalizado.
   */
  protected function mapJurisdiction(string $uri): string {
    if (str_contains($uri, 'JUDG')) {
      return 'eu_general';
    }

    return 'eu_legislacion';
  }

}
