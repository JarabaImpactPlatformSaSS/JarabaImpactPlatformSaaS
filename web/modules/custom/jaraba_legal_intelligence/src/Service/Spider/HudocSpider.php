<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para HUDOC (base de datos del Tribunal Europeo de Derechos Humanos).
 *
 * ESTRUCTURA:
 * Conector que extrae sentencias y decisiones del sistema HUDOC, la base de
 * datos oficial del Tribunal Europeo de Derechos Humanos (TEDH/ECHR). Accede
 * a la API REST de HUDOC para descargar resoluciones en las que Espanha es
 * Estado demandado (respondent:"ESP"), incluyendo sentencias (judgments),
 * decisiones (decisions) y opiniones consultivas (advisory opinions).
 * El TEDH vela por el cumplimiento del Convenio Europeo de Derechos Humanos
 * y sus protocolos adicionales.
 *
 * LOGICA:
 * Construye una peticion GET a la API REST de HUDOC con filtro de Estado
 * demandado (ESP) y rango de fechas. La API devuelve resultados en formato
 * JSON con campos como itemid, docname, appno (numero de demanda), ecli,
 * doctype, kpdate (fecha), importance, article (articulos del CEDH invocados)
 * y conclusion. Se parsea el JSON para extraer los datos crudos de cada
 * resolucion. El campo external_ref usa preferentemente el numero de demanda
 * (appno) y como fallback el itemid interno de HUDOC. El campo source_id
 * devuelve 'tedh' (codigo de institucion) en lugar de 'hudoc' (clave del
 * spider). En caso de error HTTP o de parseo JSON, registra el error y
 * devuelve array vacio.
 *
 * RELACIONES:
 * - HudocSpider -> SpiderInterface: implementa el contrato del spider.
 * - HudocSpider -> GuzzleHttp\ClientInterface: peticiones HTTP a la API HUDOC.
 * - HudocSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url configurada.
 * - HudocSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada semanal.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.hudoc.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 */
class HudocSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones a la API HUDOC.
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
   * Construye una nueva instancia de HudocSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones a la API REST de HUDOC.
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
    return 'hudoc';
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
    return $sourceId === 'hudoc';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de sentencias y decisiones del TEDH via API HUDOC.
   *
   * Construye una peticion GET a la API REST de HUDOC con filtro de Estado
   * demandado (ESP) y rango de fechas. La API devuelve resultados en formato
   * JSON que se parsean para extraer los datos basicos de cada resolucion:
   * numero de demanda, titulo, tipo de documento, articulos CEDH, ECLI,
   * fecha de emision, nivel de importancia y URL del documento original.
   *
   * El campo source_id devuelve 'tedh' (codigo de institucion del Tribunal
   * Europeo de Derechos Humanos) en lugar de 'hudoc' (clave del spider),
   * ya que el entity source_id usa el codigo institucional.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.hudoc.base_url') ?? 'https://hudoc.echr.coe.int/eng';

    // Rango de fechas por defecto: ultima semana (frecuencia semanal).
    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');

    // Limite de resultados por consulta. HUDOC permite hasta 500 por pagina.
    $maxResults = $options['max_results'] ?? 100;

    // Endpoint de la API REST de HUDOC para consulta de resultados.
    $apiUrl = 'https://hudoc.echr.coe.int/app/query/results';

    // Construir parametros de consulta.
    // Filtro principal: Estado demandado Espanha (ESP).
    $query = [
      'query' => 'respondent:"ESP"',
      'select' => $this->buildSelectFields(),
      'sort' => 'kpdate Descending',
      'start' => 0,
      'length' => $maxResults,
    ];

    // Anadir filtro de rango de fechas si se especifica.
    if ($dateFrom) {
      $query['query'] .= sprintf(
        ' AND kpdate:["%sT00:00:00Z" TO "%sT23:59:59Z"]',
        $dateFrom,
        $dateTo
      );
    }

    try {
      $response = $this->httpClient->request('GET', $apiUrl, [
        'timeout' => 60,
        'query' => $query,
        'headers' => [
          'Accept' => 'application/json',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      // La API HUDOC devuelve JSON con estructura:
      // { "resultcount": N, "results": [ { "columns": { ... } }, ... ] }
      $json = (string) $response->getBody();
      $resolutions = $this->parseResponse($json);

      $this->logger->info('HUDOC spider: @count resoluciones extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('HUDOC spider: Error HTTP al rastrear la API HUDOC: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('HUDOC spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea la respuesta JSON de la API HUDOC y extrae resoluciones.
   *
   * La API de HUDOC devuelve un JSON con un array 'results', donde cada
   * elemento contiene un objeto 'columns' con los campos solicitados via
   * el parametro 'select'. Se extraen: itemid, docname, appno, doctype,
   * ecli, kpdate, importance, article y respondent.
   *
   * El campo external_ref usa preferentemente el numero de demanda (appno),
   * que es el identificador mas reconocido en la jurisprudencia del TEDH
   * (ej: 12345/06). Como fallback se usa el itemid interno de HUDOC.
   *
   * El nivel de importancia de HUDOC (1-4) se mapea a nuestro sistema (1-3):
   * - HUDOC 1 (Key case) -> 1 (Alta)
   * - HUDOC 2 (Medium) -> 2 (Media)
   * - HUDOC 3-4 (Low) -> 3 (Baja)
   *
   * @param string $json
   *   Contenido JSON de la respuesta de la API HUDOC.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada resolucion.
   *   Cada elemento incluye campos estandar (source_id, external_ref, title,
   *   resolution_type, etc.) y campos especificos del TEDH (ecli,
   *   case_number, respondent_state, cedh_articles, importance_level,
   *   language_original).
   */
  protected function parseResponse(string $json): array {
    $resolutions = [];

    if (empty($json)) {
      return $resolutions;
    }

    $data = json_decode($json, TRUE);

    if (!is_array($data)) {
      $this->logger->notice('HUDOC spider: Respuesta JSON invalida.');
      return $resolutions;
    }

    $results = $data['results'] ?? [];

    foreach ($results as $item) {
      $columns = $item['columns'] ?? [];

      $itemId = $columns['itemid'] ?? '';
      $appno = $columns['appno'] ?? '';
      $docname = $columns['docname'] ?? '';

      // external_ref: preferir appno (numero de demanda), fallback a itemid.
      // El appno es el identificador mas reconocido en jurisprudencia TEDH
      // (ej: 12345/06), mientras que itemid es un identificador interno.
      $externalRef = !empty($appno) ? $appno : $itemId;

      if (empty($externalRef) || empty($docname)) {
        continue;
      }

      // Parsear articulos del CEDH desde el campo 'article'.
      // El campo puede ser un string separado por punto y coma o un array.
      $articles = $columns['article'] ?? [];
      if (is_string($articles)) {
        $articles = array_filter(array_map('trim', explode(';', $articles)));
      }

      // Determinar nivel de importancia.
      // HUDOC usa escala 1-4 donde 1 es "Key case" y 4 es "Low importance".
      // Nuestro sistema usa escala 1-3 (1=Alta, 2=Media, 3=Baja).
      // Se colapsan los niveles 3 y 4 de HUDOC en nivel 3 (Baja).
      $importance = (int) ($columns['importance'] ?? 3);
      $importanceLevel = match (TRUE) {
        $importance <= 1 => 1,
        $importance === 2 => 2,
        default => 3,
      };

      // Determinar idioma original a partir del nombre del documento.
      // Las sentencias contra Espanha suelen tener titulo en frances o ingles.
      // Si el titulo contiene "c. Espa" se infiere que esta en castellano.
      $languageOriginal = str_contains($docname, 'c. Espa') || str_contains($docname, 'c. España') ? 'es' : 'en';

      // Construir URL al documento en HUDOC.
      // Se usa el subdominio /spa para la version en castellano del interfaz.
      $originalUrl = 'https://hudoc.echr.coe.int/spa?i=' . urlencode($itemId);

      $resolutions[] = [
        // IMPORTANTE: source_id es 'tedh' (codigo institucional), no 'hudoc'.
        'source_id' => 'tedh',
        'external_ref' => $externalRef,
        'title' => $docname,
        'resolution_type' => $this->mapDocType($columns['doctype'] ?? ''),
        'issuing_body' => 'TEDH',
        'jurisdiction' => 'eu_derechos_humanos',
        'date_issued' => $this->normalizeDate($columns['kpdate'] ?? ''),
        'original_url' => $originalUrl,
        // Texto completo se extrae posteriormente via Apache Tika.
        'full_text' => '',
        // Campos especificos del TEDH (fuente europea).
        'ecli' => $columns['ecli'] ?? '',
        'case_number' => $appno,
        'respondent_state' => 'ESP',
        'cedh_articles' => json_encode($articles),
        'importance_level' => $importanceLevel,
        'language_original' => $languageOriginal,
      ];
    }

    return $resolutions;
  }

  /**
   * Mapea el tipo de documento HUDOC al tipo de resolucion interno.
   *
   * HUDOC usa codigos internos para los tipos de documento:
   * - HEJUD: Judgment (sentencia).
   * - HEDEC: Decision (decision de admisibilidad o inadmisibilidad).
   * - HEADV: Advisory opinion (opinion consultiva de la Gran Sala).
   * Tambien puede devolver los nombres en ingles directamente.
   *
   * @param string $type
   *   Tipo de documento tal como lo devuelve la API de HUDOC.
   *
   * @return string
   *   Tipo de resolucion normalizado para el sistema interno.
   */
  protected function mapDocType(string $type): string {
    return match ($type) {
      'HEJUD', 'Judgment' => 'sentencia',
      'HEDEC', 'Decision' => 'decision',
      'HEADV', 'Advisory opinion' => 'opinion_consultiva',
      default => 'resolucion',
    };
  }

  /**
   * Normaliza formatos de fecha de HUDOC al formato Y-m-d.
   *
   * Las fechas de HUDOC pueden venir en formato ISO 8601 con zona horaria
   * (ej: "2024-01-15T00:00:00Z") o en formato europeo (ej: "15/01/2024").
   * Se normalizan al formato Y-m-d que usa el sistema interno.
   *
   * @param string $date
   *   Fecha en el formato original de HUDOC.
   *
   * @return string
   *   Fecha normalizada en formato Y-m-d, o la cadena original si no se
   *   puede parsear, o cadena vacia si la entrada esta vacia.
   */
  protected function normalizeDate(string $date): string {
    if (empty($date)) {
      return '';
    }

    // Las fechas HUDOC pueden ser ISO 8601 ("2024-01-15T00:00:00Z")
    // o formato europeo ("15/01/2024"). strtotime maneja ambos formatos.
    $timestamp = strtotime($date);

    return $timestamp ? date('Y-m-d', $timestamp) : $date;
  }

  /**
   * Construye la cadena de campos para el parametro 'select' de la API HUDOC.
   *
   * Devuelve la lista de campos que se solicitan a la API de HUDOC en cada
   * consulta. Estos campos corresponden a los metadatos necesarios para
   * construir la entidad de resolucion legal en el sistema interno.
   *
   * Campos solicitados:
   * - itemid: Identificador interno de HUDOC.
   * - docname: Nombre del documento (titulo de la sentencia/decision).
   * - appno: Numero de demanda (ej: 12345/06).
   * - importance: Nivel de importancia (1=Key case, 2=Medium, 3-4=Low).
   * - respondent: Estado demandado (ESP para Espanha).
   * - kpdate: Fecha de la resolucion en formato ISO 8601.
   * - doctype: Tipo de documento (HEJUD, HEDEC, HEADV).
   * - ecli: Identificador europeo de jurisprudencia.
   * - article: Articulos del CEDH invocados en el caso.
   * - conclusion: Conclusion del tribunal sobre cada articulo.
   * - scl: Clasificacion tematica del caso.
   *
   * @return string
   *   Cadena con los nombres de campos separados por comas.
   */
  protected function buildSelectFields(): string {
    return 'itemid,docname,appno,importance,respondent,kpdate,doctype,ecli,article,conclusion,scl';
  }

}
