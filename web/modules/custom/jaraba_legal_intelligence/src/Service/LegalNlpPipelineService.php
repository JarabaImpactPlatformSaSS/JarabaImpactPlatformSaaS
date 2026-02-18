<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Component\Uuid\Php as UuidGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Pipeline NLP de 9 etapas para procesamiento de resoluciones juridicas.
 *
 * ESTRUCTURA:
 * Servicio central que orquesta el procesamiento completo de una resolucion
 * legal recien ingestada. Implementa un pipeline secuencial de 9 etapas que
 * transforma texto crudo en datos estructurados, resumenes IA y vectores
 * de busqueda semantica.
 *
 * LOGICA:
 * 1. Extraccion (Apache Tika): PDF/HTML -> texto plano limpio.
 * 2. Normalizacion: Limpieza encoding, BOM, saltos de linea, whitespace.
 * 3. Segmentacion (spaCy via FastAPI): Division en antecedentes, fundamentos, fallo.
 * 4. NER Juridico (spaCy custom via FastAPI): Extraccion de leyes, articulos, tribunales.
 * 5. Clasificacion (AI Provider chat): Jurisdiccion, temas, tipo, importancia.
 * 6. Resumen (AI Provider chat): Abstract 3-5 lineas + ratio decidendi.
 * 7. Embeddings (AI Provider embeddings): Vectorizacion de chunks de 512 tokens.
 * 8. Indexacion (Qdrant REST API): Insercion con payload filtrable.
 * 9. Grafo de citas (MariaDB): Construccion de red de citas legal_citation_graph.
 *
 * Cada etapa es independiente y tolerante a fallos: si una etapa falla, se
 * registra el error y se continua con la siguiente (excepto extraccion, que
 * es prerequisito). Los prompts de IA son configurables desde la UI de admin.
 *
 * RELACIONES:
 * - LegalNlpPipelineService -> AiProviderPluginManager: genera embeddings y
 *   llama al modelo de chat para clasificacion y resumen.
 * - LegalNlpPipelineService -> ClientInterface: HTTP a Tika, Python NLP, Qdrant.
 * - LegalNlpPipelineService -> Connection: inserta relaciones en legal_citation_graph.
 * - LegalNlpPipelineService -> EntityTypeManagerInterface: busca resoluciones
 *   citadas por external_ref para construir el grafo.
 * - LegalNlpPipelineService <- LegalIngestionWorker (QueueWorker): invocado
 *   desde processItem() para cada resolucion encolada.
 * - LegalNlpPipelineService <- ConfigFactory: lee URLs, prompts y thresholds
 *   desde jaraba_legal_intelligence.settings.
 */
class LegalNlpPipelineService {

  /**
   * Fuentes europeas que usan coleccion Qdrant EU y prompts EU.
   *
   * @var string[]
   */
  private const EU_SOURCES = [
    'tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue',
  ];

  /**
   * Construye una nueva instancia de LegalNlpPipelineService.
   *
   * @param \Drupal\ai\AiProviderPluginManager $aiProvider
   *   Gestor de plugins de IA para generar embeddings y llamadas chat.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para comunicacion con Tika, Python NLP y Qdrant.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para leer URLs, prompts y thresholds.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   * @param \Drupal\Core\Database\Connection $database
   *   Conexion a base de datos para el grafo de citas.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para buscar resoluciones citadas.
   */
  public function __construct(
    protected object $aiProvider,
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Procesa una resolucion a traves del pipeline NLP completo de 9 etapas.
   *
   * Punto de entrada principal del servicio. Ejecuta secuencialmente las 9
   * etapas del pipeline, actualizando los campos de la entidad con los
   * resultados generados. La entidad se guarda al final con todos los campos
   * enriquecidos.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity
   *   Entidad LegalResolution recien ingestada a procesar.
   *
   * @throws \Exception
   *   Si ocurre un error critico no recuperable (se re-lanza para que
   *   el QueueWorker reintente).
   */
  public function processResolution(LegalResolution $entity): void {
    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');
    $externalRef = $entity->get('external_ref')->value ?? 'unknown';
    $sourceId = $entity->get('source_id')->value ?? 'unknown';
    $isEu = in_array($sourceId, self::EU_SOURCES, TRUE);

    $this->logger->info('NLP Pipeline: Iniciando procesamiento de @ref (@source)', [
      '@ref' => $externalRef,
      '@source' => $sourceId,
    ]);

    // === ETAPA 1: Extraccion de texto (Apache Tika) ===
    $fullText = $entity->get('full_text')->value;
    if (empty($fullText)) {
      $originalUrl = $entity->get('original_url')->value;
      if (!empty($originalUrl)) {
        $fullText = $this->extractWithTika($originalUrl, $config);
        if (!empty($fullText)) {
          $entity->set('full_text', $fullText);
        }
      }
    }

    if (empty($fullText)) {
      $this->logger->warning('NLP Pipeline: Sin texto disponible para @ref. Pipeline abortado.', [
        '@ref' => $externalRef,
      ]);
      return;
    }

    // === ETAPA 2: Normalizacion ===
    $normalizedText = $this->normalize($fullText);

    // Truncar texto para etapas de procesamiento (configuracion por defecto: 50000 chars).
    $maxTextLength = (int) ($config->get('nlp_max_text_length') ?: 50000);
    $processText = mb_substr($normalizedText, 0, $maxTextLength);

    // === ETAPA 3: Segmentacion (spaCy via FastAPI Python) ===
    $segments = $this->segment($processText, $sourceId, $config);

    // === ETAPA 4: NER Juridico (spaCy custom via FastAPI Python) ===
    $nerEntities = $this->extractEntities($processText, $config);

    // === ETAPA 5: Clasificacion (AI Provider chat) ===
    $classification = $this->classify($processText, $isEu, $config);
    $this->applyClassification($entity, $classification);

    // === ETAPA 6: Resumen (AI Provider chat) ===
    $summary = $this->summarize($processText, $isEu, $config);
    $this->applySummary($entity, $summary, $isEu);

    // === ETAPA 7: Embeddings (AI Provider embeddings) ===
    $chunks = $this->chunkText($normalizedText, $segments, $config);
    $embeddingsData = $this->generateEmbeddings($chunks, $entity);

    // === ETAPA 8: Indexacion en Qdrant ===
    $collection = $isEu
      ? ($config->get('qdrant_collection_eu') ?: 'legal_intelligence_eu')
      : ($config->get('qdrant_collection_national') ?: 'legal_intelligence');
    $vectorIds = $this->indexInQdrant($collection, $embeddingsData, $config);
    $entity->set('vector_ids', json_encode($vectorIds, JSON_UNESCAPED_UNICODE));
    $entity->set('qdrant_collection', $collection);

    // === ETAPA 9: Grafo de citas ===
    $this->buildCitationGraph((int) $entity->id(), $nerEntities);

    // Aplicar NER como legislacion citada.
    $citedLegislation = $this->extractCitedLegislation($nerEntities);
    $entity->set('cited_legislation', json_encode($citedLegislation, JSON_UNESCAPED_UNICODE));

    // Guardar entidad con todos los campos enriquecidos.
    $entity->save();

    $this->logger->info('NLP Pipeline: Procesamiento completo de @ref (@source). Chunks: @chunks, Vector IDs: @vids', [
      '@ref' => $externalRef,
      '@source' => $sourceId,
      '@chunks' => count($chunks),
      '@vids' => count($vectorIds),
    ]);
  }

  // =========================================================================
  // ETAPA 1: Extraccion de texto con Apache Tika.
  // =========================================================================

  /**
   * Extrae texto plano de un documento PDF/HTML usando Apache Tika.
   *
   * Descarga el documento desde la URL original y lo envia al servidor Tika
   * para conversion a texto plano. Soporta PDF, DOCX, HTML y otros formatos.
   *
   * @param string $url
   *   URL del documento original.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return string
   *   Texto plano extraido, o cadena vacia si falla.
   */
  private function extractWithTika(string $url, $config): string {
    $tikaUrl = $config->get('tika_url') ?: 'http://tika:9998';
    $timeout = (int) ($config->get('nlp_tika_timeout') ?: 60);

    try {
      // Descargar el documento original.
      $documentResponse = $this->httpClient->request('GET', $url, [
        'timeout' => $timeout,
        'headers' => [
          'User-Agent' => 'JarabaLegalIntelligence/1.0',
        ],
      ]);
      $documentBody = $documentResponse->getBody()->getContents();

      if (empty($documentBody)) {
        $this->logger->warning('Tika: Documento vacio descargado de @url', ['@url' => $url]);
        return '';
      }

      // Detectar Content-Type del documento descargado.
      $contentType = $documentResponse->getHeaderLine('Content-Type') ?: 'application/octet-stream';

      // Enviar a Tika para extraccion.
      $tikaResponse = $this->httpClient->request('PUT', $tikaUrl . '/tika', [
        'headers' => [
          'Content-Type' => $contentType,
          'Accept' => 'text/plain',
        ],
        'body' => $documentBody,
        'timeout' => $timeout,
      ]);

      return $tikaResponse->getBody()->getContents();
    }
    catch (\Exception $e) {
      $this->logger->error('Tika: Error extrayendo texto de @url: @msg', [
        '@url' => $url,
        '@msg' => $e->getMessage(),
      ]);
      return '';
    }
  }

  // =========================================================================
  // ETAPA 2: Normalizacion de texto.
  // =========================================================================

  /**
   * Normaliza el texto extraido para procesamiento uniforme.
   *
   * Limpia BOM, normaliza saltos de linea, convierte encoding a UTF-8,
   * elimina caracteres de control y reduce whitespace excesivo.
   *
   * @param string $text
   *   Texto crudo extraido por Tika o del campo full_text.
   *
   * @return string
   *   Texto normalizado listo para segmentacion y NER.
   */
  private function normalize(string $text): string {
    // Eliminar BOM (Byte Order Mark).
    $text = preg_replace('/\x{FEFF}/u', '', $text);

    // Normalizar saltos de linea a \n.
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // Asegurar encoding UTF-8.
    if (!mb_check_encoding($text, 'UTF-8')) {
      $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    }

    // Eliminar caracteres de control excepto \n y \t.
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

    // Reducir 3+ saltos de linea consecutivos a 2.
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Reducir espacios multiples a uno solo.
    $text = preg_replace('/ {2,}/', ' ', $text);

    // Eliminar espacios al inicio de cada linea.
    $text = preg_replace('/^ +/m', '', $text);

    return trim($text);
  }

  // =========================================================================
  // ETAPA 3: Segmentacion de texto (Python FastAPI + spaCy).
  // =========================================================================

  /**
   * Segmenta el texto en secciones estructurales usando spaCy.
   *
   * Envia el texto al microservicio FastAPI Python que usa spaCy es_core_news_lg
   * para identificar las secciones tipicas de una resolucion juridica:
   * antecedentes, hechos, fundamentos de derecho, fallo, voto particular.
   *
   * @param string $text
   *   Texto normalizado de la resolucion.
   * @param string $sourceId
   *   Identificador de la fuente (cendoj, boe, etc.).
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return array
   *   Array de segmentos: [['section' => 'fundamentos', 'text' => '...'], ...].
   *   Retorna segmento unico 'body' si el servicio Python falla.
   */
  private function segment(string $text, string $sourceId, $config): array {
    try {
      $result = $this->callPythonNlp('segment', [
        'text' => $text,
        'source_id' => $sourceId,
      ], $config);

      if (is_array($result) && !empty($result)) {
        return $result;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('NLP Segment: Error en segmentacion, usando texto completo como body: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    // Fallback: texto completo como una sola seccion 'body'.
    return [['section' => 'body', 'text' => $text]];
  }

  // =========================================================================
  // ETAPA 4: Extraccion de entidades nombradas (NER juridico).
  // =========================================================================

  /**
   * Extrae entidades juridicas del texto usando NER custom via Python.
   *
   * Envia el texto al microservicio FastAPI que usa patrones regex y spaCy
   * para identificar referencias a leyes, articulos, sentencias, consultas
   * vinculantes, directivas UE, reglamentos, ECLI y CELEX.
   *
   * @param string $text
   *   Texto normalizado de la resolucion.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return array
   *   Array de entidades: [['type' => 'legislation_ref', 'subtype' => 'ley',
   *   'reference' => 'Ley 35/2006', 'context' => '...'], ...].
   */
  private function extractEntities(string $text, $config): array {
    try {
      $result = $this->callPythonNlp('ner', ['text' => $text], $config);
      return $result['entities'] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->warning('NLP NER: Error en extraccion de entidades: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  // =========================================================================
  // ETAPA 5: Clasificacion con AI Provider (Gemini/GPT via @ai.provider).
  // =========================================================================

  /**
   * Clasifica la resolucion por jurisdiccion, temas, tipo e importancia.
   *
   * Usa el proveedor de IA configurado (via @ai.provider) para enviar el texto
   * con un prompt de clasificacion. Usa prompts diferentes para fuentes
   * nacionales y europeas, ambos configurables desde la UI de admin.
   *
   * @param string $text
   *   Texto normalizado (se trunca a nlp_classification_max_chars).
   * @param bool $isEu
   *   TRUE si la resolucion proviene de una fuente europea.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo con los prompts.
   *
   * @return array
   *   Array con claves: jurisdiction, topics, resolution_type, importance_level.
   *   Si procede de fuente UE, tambien: procedure_type.
   */
  private function classify(string $text, bool $isEu, $config): array {
    $maxChars = (int) ($config->get('nlp_classification_max_chars') ?: 8000);
    $truncatedText = mb_substr($text, 0, $maxChars);

    $promptKey = $isEu ? 'eu_classification_prompt' : 'classification_prompt';
    $prompt = $config->get($promptKey) ?: $config->get('classification_prompt') ?: '';

    if (empty($prompt)) {
      $this->logger->warning('NLP Classify: Prompt de clasificacion vacio.');
      return [];
    }

    try {
      $responseText = $this->callAiChat(
        $prompt,
        "TEXTO DE LA RESOLUCIÓN:\n\n" . $truncatedText,
        $config,
      );

      return $this->parseJsonResponse($responseText, 'clasificacion');
    }
    catch (\Exception $e) {
      $this->logger->error('NLP Classify: Error en clasificacion IA: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Aplica los resultados de clasificacion a la entidad.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity
   *   Entidad a actualizar.
   * @param array $classification
   *   Resultados de clasificacion del modelo IA.
   */
  private function applyClassification(LegalResolution $entity, array $classification): void {
    if (!empty($classification['topics'])) {
      $topics = is_array($classification['topics']) ? $classification['topics'] : [$classification['topics']];
      $entity->set('topics', json_encode($topics, JSON_UNESCAPED_UNICODE));
    }

    if (!empty($classification['jurisdiction'])) {
      $entity->set('jurisdiction', (string) $classification['jurisdiction']);
    }

    if (!empty($classification['resolution_type'])) {
      $entity->set('resolution_type', (string) $classification['resolution_type']);
    }

    if (isset($classification['importance_level'])) {
      $entity->set('importance_level', (int) $classification['importance_level']);
    }

    if (!empty($classification['procedure_type'])) {
      $entity->set('procedure_type', (string) $classification['procedure_type']);
    }
  }

  // =========================================================================
  // ETAPA 6: Resumen con AI Provider (Gemini/GPT via @ai.provider).
  // =========================================================================

  /**
   * Genera resumen IA, ratio decidendi y legislacion citada.
   *
   * Usa el proveedor de IA configurado para generar un abstract de 3-5 lineas,
   * la ratio decidendi (key holdings) y la legislacion citada. Para fuentes
   * europeas, tambien genera analisis de impacto en derecho espanol.
   *
   * @param string $text
   *   Texto normalizado (se trunca a nlp_summary_max_chars).
   * @param bool $isEu
   *   TRUE si la resolucion proviene de una fuente europea.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo con los prompts.
   *
   * @return array
   *   Array con claves: abstract, key_holdings, cited_legislation.
   *   Si isEu, tambien: impact_spain.
   */
  private function summarize(string $text, bool $isEu, $config): array {
    $maxChars = (int) ($config->get('nlp_summary_max_chars') ?: 12000);
    $truncatedText = mb_substr($text, 0, $maxChars);

    $promptKey = $isEu ? 'eu_summary_prompt' : 'summary_prompt';
    $prompt = $config->get($promptKey) ?: $config->get('summary_prompt') ?: '';

    if (empty($prompt)) {
      $this->logger->warning('NLP Summarize: Prompt de resumen vacio.');
      return [];
    }

    try {
      $responseText = $this->callAiChat(
        $prompt,
        "TEXTO DE LA RESOLUCIÓN:\n\n" . $truncatedText,
        $config,
      );

      return $this->parseJsonResponse($responseText, 'resumen');
    }
    catch (\Exception $e) {
      $this->logger->error('NLP Summarize: Error en resumen IA: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Aplica los resultados de resumen a la entidad.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity
   *   Entidad a actualizar.
   * @param array $summary
   *   Resultados de resumen del modelo IA.
   * @param bool $isEu
   *   TRUE si aplica campos EU.
   */
  private function applySummary(LegalResolution $entity, array $summary, bool $isEu): void {
    if (!empty($summary['abstract'])) {
      $entity->set('abstract_ai', (string) $summary['abstract']);
    }

    if (!empty($summary['key_holdings'])) {
      $entity->set('key_holdings', (string) $summary['key_holdings']);
    }

    if ($isEu && !empty($summary['impact_spain'])) {
      $entity->set('impact_spain', (string) $summary['impact_spain']);
    }
  }

  // =========================================================================
  // ETAPA 7: Generacion de embeddings via AI Provider.
  // =========================================================================

  /**
   * Divide el texto en chunks y genera embeddings para cada uno.
   *
   * Primero chunifica el texto segun los segmentos identificados por spaCy,
   * con ventana deslizante de tamanio configurable y solapamiento. Luego
   * genera el embedding de cada chunk via el proveedor de IA configurado
   * (text-embedding-3-large para nacional, multilingual-e5-large para EU).
   *
   * @param string $text
   *   Texto normalizado completo.
   * @param array $segments
   *   Segmentos de la etapa 3.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return array
   *   Array de chunks con texto, metadata y seccion.
   */
  private function chunkText(string $text, array $segments, $config): array {
    $maxTokens = (int) ($config->get('nlp_chunk_max_tokens') ?: 512);
    $overlap = (int) ($config->get('nlp_chunk_overlap_tokens') ?: 50);
    $chunks = [];

    foreach ($segments as $segment) {
      $segText = $segment['text'] ?? '';
      if (empty($segText)) {
        continue;
      }

      $section = $segment['section'] ?? 'body';
      $words = preg_split('/\s+/', $segText, -1, PREG_SPLIT_NO_EMPTY);
      $wordCount = count($words);
      $pos = 0;

      while ($pos < $wordCount) {
        $chunkWords = array_slice($words, $pos, $maxTokens);
        $chunkText = implode(' ', $chunkWords);

        if (mb_strlen($chunkText) > 10) {
          $chunks[] = [
            'text' => $chunkText,
            'section' => $section,
            'chunk_index' => count($chunks),
          ];
        }

        $pos += ($maxTokens - $overlap);
      }
    }

    // Garantizar al menos un chunk si hay texto.
    if (empty($chunks) && !empty($text)) {
      $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
      $chunks[] = [
        'text' => implode(' ', array_slice($words, 0, $maxTokens)),
        'section' => 'body',
        'chunk_index' => 0,
      ];
    }

    return $chunks;
  }

  /**
   * Genera embeddings para todos los chunks de una resolucion.
   *
   * Usa el proveedor de IA configurado por defecto para el tipo de operacion
   * 'embeddings'. Cada chunk se vectoriza individualmente y se anade metadata
   * de la resolucion para filtrado en Qdrant.
   *
   * @param array $chunks
   *   Array de chunks del texto.
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity
   *   Entidad para extraer metadata de payload.
   *
   * @return array
   *   Array de embeddings: [['vector' => float[], 'payload' => array], ...].
   */
  private function generateEmbeddings(array $chunks, LegalResolution $entity): array {
    if (empty($chunks)) {
      return [];
    }

    // Obtener proveedor de embeddings por defecto.
    $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');
    if (!$defaults) {
      $this->logger->error('NLP Embeddings: No hay proveedor de embeddings configurado.');
      return [];
    }

    try {
      /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
      $provider = $this->aiProvider->createInstance($defaults['provider_id']);
      $modelId = $defaults['model_id'] ?? 'text-embedding-3-large';
    }
    catch (\Exception $e) {
      $this->logger->error('NLP Embeddings: Error creando instancia del proveedor: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }

    // Metadata comun del payload Qdrant para filtrado facetado.
    $basePayload = [
      'resolution_id' => (int) $entity->id(),
      'source_id' => $entity->get('source_id')->value ?? '',
      'external_ref' => $entity->get('external_ref')->value ?? '',
      'date_issued' => $entity->get('date_issued')->value ?? '',
      'jurisdiction' => $entity->get('jurisdiction')->value ?? '',
      'issuing_body' => $entity->get('issuing_body')->value ?? '',
      'resolution_type' => $entity->get('resolution_type')->value ?? '',
      'status_legal' => $entity->get('status_legal')->value ?? 'vigente',
      'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
      'respondent_state' => $entity->get('respondent_state')->value ?? '',
    ];

    $embeddings = [];

    foreach ($chunks as $chunk) {
      try {
        $result = $provider->embeddings($chunk['text'], $modelId);
        $vector = $result->getNormalized();

        $embeddings[] = [
          'vector' => $vector,
          'payload' => array_merge($basePayload, [
            'section' => $chunk['section'],
            'chunk_index' => $chunk['chunk_index'],
            'text_preview' => mb_substr($chunk['text'], 0, 200),
          ]),
        ];
      }
      catch (\Exception $e) {
        $this->logger->warning('NLP Embeddings: Error generando embedding para chunk @idx: @msg', [
          '@idx' => $chunk['chunk_index'],
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    return $embeddings;
  }

  // =========================================================================
  // ETAPA 8: Indexacion en Qdrant.
  // =========================================================================

  /**
   * Indexa los embeddings en la coleccion Qdrant correspondiente.
   *
   * Envia los puntos vectoriales al servidor Qdrant via su API REST.
   * Cada punto tiene un UUID unico, el vector de embeddings y un payload
   * con metadata filtrable (source_id, date_issued, jurisdiction, etc.).
   *
   * @param string $collection
   *   Nombre de la coleccion Qdrant (legal_intelligence o legal_intelligence_eu).
   * @param array $embeddings
   *   Array de embeddings con vector y payload.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return array
   *   Array de UUIDs de los puntos insertados en Qdrant.
   */
  private function indexInQdrant(string $collection, array $embeddings, $config): array {
    if (empty($embeddings)) {
      return [];
    }

    $qdrantUrl = $config->get('qdrant_url') ?: 'http://qdrant:6333';
    $apiKey = $config->get('qdrant_api_key') ?: '';

    $points = [];
    $ids = [];

    foreach ($embeddings as $emb) {
      $uuid = UuidGenerator::generate();
      $points[] = [
        'id' => $uuid,
        'vector' => $emb['vector'],
        'payload' => $emb['payload'],
      ];
      $ids[] = $uuid;
    }

    // Enviar en batches de 100 puntos para evitar timeouts con volumenes grandes.
    $batchSize = 100;
    $batches = array_chunk($points, $batchSize);

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($apiKey)) {
      $headers['api-key'] = $apiKey;
    }

    foreach ($batches as $batchIndex => $batch) {
      try {
        $this->httpClient->request('PUT',
          "{$qdrantUrl}/collections/{$collection}/points",
          [
            'json' => ['points' => $batch],
            'headers' => $headers,
            'timeout' => 30,
          ]
        );
      }
      catch (\Exception $e) {
        $this->logger->error('NLP Qdrant: Error indexando batch @idx en @collection: @msg', [
          '@idx' => $batchIndex,
          '@collection' => $collection,
          '@msg' => $e->getMessage(),
        ]);
        // Eliminar IDs del batch fallido del resultado.
        $failedCount = count($batch);
        $ids = array_slice($ids, 0, -$failedCount);
      }
    }

    return $ids;
  }

  // =========================================================================
  // ETAPA 9: Construccion del grafo de citas.
  // =========================================================================

  /**
   * Construye relaciones de cita en la tabla legal_citation_graph.
   *
   * Para cada entidad juridica detectada por NER (tipo legislation_ref), busca
   * si existe una resolucion correspondiente en la base de datos. Si existe,
   * inserta una relacion de cita en el grafo dirigido.
   *
   * Usa MERGE (INSERT ... ON DUPLICATE KEY UPDATE) para idempotencia.
   *
   * @param int $sourceResolutionId
   *   ID de la resolucion que cita (origen del arco).
   * @param array $nerEntities
   *   Entidades detectadas por NER con type, reference y context.
   */
  private function buildCitationGraph(int $sourceResolutionId, array $nerEntities): void {
    if (empty($nerEntities)) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    $insertCount = 0;

    foreach ($nerEntities as $nerEntity) {
      // Solo procesar referencias a legislacion y sentencias.
      $type = $nerEntity['type'] ?? '';
      if ($type !== 'legislation_ref') {
        continue;
      }

      $reference = $nerEntity['reference'] ?? '';
      if (empty($reference)) {
        continue;
      }

      // Buscar la resolucion citada por external_ref.
      $cited = $storage->loadByProperties(['external_ref' => $reference]);
      if (empty($cited)) {
        continue;
      }

      $citedEntity = reset($cited);
      $targetResolutionId = (int) $citedEntity->id();

      // No crear auto-citas.
      if ($targetResolutionId === $sourceResolutionId) {
        continue;
      }

      // Determinar tipo de relacion segun subtipo NER.
      $relationType = $this->mapNerSubtypeToRelationType($nerEntity['subtype'] ?? '');

      // Contexto de la cita (fragmento donde aparece).
      $context = mb_substr($nerEntity['context'] ?? '', 0, 500);

      try {
        $this->database->merge('legal_citation_graph')
          ->keys([
            'source_resolution_id' => $sourceResolutionId,
            'target_resolution_id' => $targetResolutionId,
            'relation_type' => $relationType,
          ])
          ->fields([
            'citation_context' => $context,
            'created' => time(),
          ])
          ->execute();
        $insertCount++;
      }
      catch (\Exception $e) {
        $this->logger->warning('NLP CitationGraph: Error insertando cita @src -> @tgt: @msg', [
          '@src' => $sourceResolutionId,
          '@tgt' => $targetResolutionId,
          '@msg' => $e->getMessage(),
        ]);
      }
    }

    if ($insertCount > 0) {
      $this->logger->info('NLP CitationGraph: @count relaciones de cita creadas para resolucion @id', [
        '@count' => $insertCount,
        '@id' => $sourceResolutionId,
      ]);
    }
  }

  /**
   * Mapea el subtipo NER al tipo de relacion del grafo de citas.
   *
   * @param string $subtype
   *   Subtipo NER: ley, sentencia, consulta_dgt, directiva_ue, etc.
   *
   * @return string
   *   Tipo de relacion: cites, applies, follows, overrules, distinguishes.
   */
  private function mapNerSubtypeToRelationType(string $subtype): string {
    return match ($subtype) {
      'ley', 'rd', 'articulo', 'directiva_ue', 'reglamento_ue' => 'applies',
      'sentencia' => 'cites',
      'consulta_dgt' => 'follows',
      default => 'cites',
    };
  }

  // =========================================================================
  // Metodos auxiliares compartidos.
  // =========================================================================

  /**
   * Llama al microservicio Python NLP (FastAPI) via HTTP.
   *
   * @param string $task
   *   Endpoint: 'segment' o 'ner'.
   * @param array $data
   *   Payload JSON del request.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return array
   *   Respuesta decodificada del servicio Python.
   *
   * @throws \Exception
   *   Si el servicio Python no responde o devuelve error.
   */
  private function callPythonNlp(string $task, array $data, $config): array {
    $nlpUrl = $config->get('nlp_service_url') ?: 'http://legal-nlp:8001';
    $timeout = (int) ($config->get('nlp_python_timeout') ?: 120);

    $response = $this->httpClient->request('POST', "{$nlpUrl}/api/{$task}", [
      'json' => $data,
      'timeout' => $timeout,
      'headers' => ['Content-Type' => 'application/json'],
    ]);

    $body = $response->getBody()->getContents();
    $decoded = json_decode($body, TRUE);

    if ($decoded === NULL && !empty($body)) {
      throw new \RuntimeException("Respuesta Python NLP no es JSON valido para tarea '{$task}'");
    }

    return $decoded ?? [];
  }

  /**
   * Llama al proveedor de IA via chat para clasificacion o resumen.
   *
   * Usa el patron de @ai.provider: obtiene el proveedor por defecto para
   * el tipo de operacion 'chat', configura temperatura y max_tokens, y
   * envia el prompt + texto como ChatInput.
   *
   * @param string $systemPrompt
   *   Prompt del sistema (instrucciones de clasificacion o resumen).
   * @param string $userMessage
   *   Mensaje del usuario (texto de la resolucion).
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return string
   *   Texto de respuesta del modelo IA.
   *
   * @throws \Exception
   *   Si no hay proveedor configurado o el modelo falla.
   */
  private function callAiChat(string $systemPrompt, string $userMessage, $config): string {
    $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
    if (!$defaults) {
      throw new \RuntimeException('No hay proveedor de chat IA configurado.');
    }

    /** @var \Drupal\ai\OperationType\Chat\ChatInterface $provider */
    $provider = $this->aiProvider->createInstance($defaults['provider_id']);

    // Configurar parametros del modelo.
    $temperature = (float) ($config->get('nlp_ai_temperature') ?: 0.2);
    $maxTokens = (int) ($config->get('nlp_ai_max_tokens') ?: 2000);
    $provider->setConfiguration([
      'temperature' => $temperature,
      'max_tokens' => $maxTokens,
    ]);

    $chatInput = new ChatInput([
      new ChatMessage('system', $systemPrompt),
      new ChatMessage('user', $userMessage),
    ]);

    $modelId = $defaults['model_id'] ?? '';
    $result = $provider->chat($chatInput, $modelId, ['jaraba_legal_nlp']);

    return $result->getNormalized()->getText();
  }

  /**
   * Parsea la respuesta JSON del modelo IA con tolerancia a errores.
   *
   * Los modelos IA a veces envuelven el JSON en bloques de codigo markdown
   * (```json ... ```) o anaden texto antes/despues. Este metodo extrae el
   * JSON valido de la respuesta.
   *
   * @param string $responseText
   *   Texto crudo de respuesta del modelo.
   * @param string $context
   *   Nombre del contexto para logging (clasificacion, resumen).
   *
   * @return array
   *   Array asociativo decodificado del JSON.
   */
  private function parseJsonResponse(string $responseText, string $context): array {
    $text = trim($responseText);

    // Intentar decodificar directamente.
    $decoded = json_decode($text, TRUE);
    if (is_array($decoded)) {
      return $decoded;
    }

    // Extraer JSON de bloques markdown ```json ... ```.
    if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $matches)) {
      $decoded = json_decode(trim($matches[1]), TRUE);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    // Buscar el primer '{' y ultimo '}' para extraer JSON embebido.
    $firstBrace = strpos($text, '{');
    $lastBrace = strrpos($text, '}');
    if ($firstBrace !== FALSE && $lastBrace !== FALSE && $lastBrace > $firstBrace) {
      $jsonCandidate = substr($text, $firstBrace, $lastBrace - $firstBrace + 1);
      $decoded = json_decode($jsonCandidate, TRUE);
      if (is_array($decoded)) {
        return $decoded;
      }
    }

    $this->logger->warning('NLP parseJsonResponse: No se pudo parsear JSON de @context. Respuesta (primeros 500 chars): @text', [
      '@context' => $context,
      '@text' => mb_substr($text, 0, 500),
    ]);

    return [];
  }

  /**
   * Extrae legislacion citada del resultado NER para almacenamiento.
   *
   * Filtra y formatea las entidades NER relevantes para el campo
   * cited_legislation de la entidad (JSON array de referencias).
   *
   * @param array $nerEntities
   *   Entidades detectadas por NER.
   *
   * @return array
   *   Array de referencias a legislacion citada.
   */
  private function extractCitedLegislation(array $nerEntities): array {
    $legislation = [];
    $seen = [];

    foreach ($nerEntities as $ent) {
      $reference = $ent['reference'] ?? '';
      if (empty($reference) || isset($seen[$reference])) {
        continue;
      }

      $seen[$reference] = TRUE;
      $legislation[] = [
        'reference' => $reference,
        'type' => $ent['subtype'] ?? $ent['type'] ?? 'unknown',
      ];
    }

    return $legislation;
  }

}
