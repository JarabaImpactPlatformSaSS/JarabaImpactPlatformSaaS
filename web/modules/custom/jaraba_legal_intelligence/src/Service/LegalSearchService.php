<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de busqueda semantica del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Servicio central que orquesta las busquedas de resoluciones juridicas
 * usando busqueda vectorial en Qdrant. Gestiona el ciclo completo:
 * embedding de la query del usuario, busqueda por similitud coseno,
 * filtrado facetado, lookup exacto por referencia, busqueda de resoluciones
 * similares, verificacion de limites por plan SaaS e hidratacion de
 * resultados desde el entity storage de Drupal.
 *
 * LOGICA:
 * El flujo principal es: query -> embedding -> Qdrant search -> filtrar
 * por facetas -> hidratar con entidad -> aplicar merge & rank si scope=all.
 * Para lookup exacto (V0123-24, STS 1234/2024), se busca directamente
 * en el entity storage por external_ref sin pasar por Qdrant.
 * Los limites por plan se verifican antes de cada busqueda: Starter=50/mes,
 * Pro/Enterprise=ilimitado. El rate limit global es 100 busquedas/hora.
 *
 * RELACIONES:
 * - LegalSearchService -> AiProviderPluginManager: genera embeddings de
 *   la query del usuario para busqueda por similitud coseno.
 * - LegalSearchService -> ClientInterface: HTTP a Qdrant para busqueda vectorial.
 * - LegalSearchService -> TenantContextService: obtiene tenant actual y plan
 *   para verificar limites de busquedas.
 * - LegalSearchService -> EntityTypeManagerInterface: hidrata resultados Qdrant
 *   con datos completos de la entidad LegalResolution.
 * - LegalSearchService -> ConfigFactory: lee URLs, thresholds y limites por plan.
 * - LegalSearchService <- LegalSearchController: invocado desde search() y
 *   apiSearch() para busquedas frontend y API REST.
 * - LegalSearchService <- LegalMergeRankService: proporciona resultados crudos
 *   que luego se fusionan y re-rankean con boost UE y frescura.
 */
class LegalSearchService {

  /**
   * Patrones regex para detectar referencias legales exactas en la query.
   *
   * Si la query del usuario coincide con uno de estos patrones, se ejecuta
   * un lookup exacto por external_ref en vez de busqueda semantica.
   *
   * @var string[]
   */
  private const REFERENCE_PATTERNS = [
    // Consultas vinculantes DGT: V0123-24, V1234-23.
    '/^V\d{4}-\d{2}$/i',
    // Sentencias TS: STS 1234/2024, STS 567/2023.
    '/^STS\s+\d+\/\d{4}$/i',
    // Sentencias TC: STC 1/2024, STC 123/2023.
    '/^STC\s+\d+\/\d{4}$/i',
    // Resoluciones TEAC: RG 1234/2024.
    '/^RG\s+\d+\/\d{4}$/i',
    // ECLI europeo: ECLI:EU:C:2013:164.
    '/^ECLI:[A-Z]{2}:[A-Z]:\d{4}:\d+$/i',
    // Asuntos TJUE: C-415/11, C-127/12.
    '/^C-\d+\/\d{2}$/i',
    // CELEX: 62011CJ0415.
    '/^\d{5}[A-Z]{2}\d{4}$/i',
  ];

  /**
   * Construye una nueva instancia de LegalSearchService.
   *
   * @param object $aiProvider
   *   Gestor de plugins de IA para generar embeddings de la query.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto de tenant para verificar plan y limites.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para hidratar resultados.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para comunicacion con Qdrant.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para leer URLs, thresholds y limites.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   * @param \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService $featureGate
   *   Servicio de feature gate para limites.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Proxy de cuenta del usuario actual.
   */
  public function __construct(
    protected object $aiProvider,
    protected TenantContextService $tenantContext,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
    protected JarabaLexFeatureGateService $featureGate,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Ejecuta busqueda semantica con filtros facetados opcionales.
   *
   * Punto de entrada principal para busquedas desde el frontend y la API.
   * Primero verifica limites del plan, luego detecta si la query es una
   * referencia exacta (V0123-24, STS 1234/2024) o texto libre. Para texto
   * libre, genera el embedding y busca en Qdrant con similitud coseno.
   * Los resultados se hidratan con datos completos de la entidad.
   *
   * @param string $query
   *   Texto de busqueda del usuario en lenguaje natural.
   * @param array $filters
   *   Filtros facetados opcionales:
   *   - source_id: string — Filtrar por fuente (cendoj, boe, dgt, etc.).
   *   - jurisdiction: string — Filtrar por jurisdiccion.
   *   - resolution_type: string — Filtrar por tipo.
   *   - date_from: string — Fecha minima (YYYY-MM-DD).
   *   - date_to: string — Fecha maxima (YYYY-MM-DD).
   *   - issuing_body: string — Filtrar por organo emisor.
   *   - importance_level: int — Filtrar por importancia (1-3).
   *   - status_legal: string — Filtrar por estado legal.
   * @param string $scope
   *   Ambito de busqueda: 'national', 'eu' o 'all'.
   * @param int $limit
   *   Numero maximo de resultados a devolver.
   *
   * @return array
   *   Array con claves:
   *   - success: bool — Si la busqueda fue exitosa.
   *   - results: array — Array de resultados hidratados.
   *   - total: int — Numero total de resultados.
   *   - facets: array — Facetas disponibles para refinamiento.
   *   - error: string|null — Mensaje de error si aplica.
   */
  public function search(string $query, array $filters = [], string $scope = 'all', int $limit = 0): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');
    $maxResults = $limit > 0 ? $limit : (int) ($config->get('max_results') ?: 20);

    // Verificar limites del plan SaaS.
    $limitCheck = $this->checkPlanLimits($config);
    if (!$limitCheck['allowed']) {
      return [
        'success' => FALSE,
        'results' => [],
        'total' => 0,
        'facets' => [],
        'error' => $limitCheck['message'],
      ];
    }

    $query = trim($query);
    if (empty($query)) {
      return [
        'success' => FALSE,
        'results' => [],
        'total' => 0,
        'facets' => [],
        'error' => 'Query is empty.',
      ];
    }

    // Detectar si la query es una referencia exacta.
    if ($this->isExactReference($query)) {
      return $this->lookupByReference($query);
    }

    try {
      // Generar embedding de la query del usuario.
      $queryEmbedding = $this->embedQuery($query);
      if (empty($queryEmbedding)) {
        return [
          'success' => FALSE,
          'results' => [],
          'total' => 0,
          'facets' => [],
          'error' => 'Could not generate query embedding.',
        ];
      }

      // Buscar en Qdrant segun el scope.
      $qdrantResults = [];
      $collections = $this->getCollectionsForScope($scope, $config);

      foreach ($collections as $collection) {
        $results = $this->searchQdrant(
          $collection,
          $queryEmbedding,
          $this->buildQdrantFilter($filters),
          $maxResults,
          $config,
        );
        $qdrantResults = array_merge($qdrantResults, $results);
      }

      // Deduplicar por resolution_id (distintos chunks de la misma resolucion).
      $deduped = $this->deduplicateResults($qdrantResults);

      // Ordenar por score descendente.
      usort($deduped, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

      // Limitar resultados.
      $deduped = array_slice($deduped, 0, $maxResults);

      // Hidratar con datos de la entidad.
      $hydrated = $this->hydrateResults($deduped);

      // Calcular facetas de los resultados.
      $facets = $this->buildFacets($hydrated);

      return [
        'success' => TRUE,
        'results' => $hydrated,
        'total' => count($hydrated),
        'facets' => $facets,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Search: Error en busqueda semantica: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'results' => [],
        'total' => 0,
        'facets' => [],
        'error' => 'An error occurred during search.',
      ];
    }
  }

  /**
   * Busca resoluciones similares a una resolucion dada por sus vectores.
   *
   * Usa los vector_ids de la resolucion para buscar en Qdrant puntos
   * cercanos en el espacio vectorial. Excluye la propia resolucion de los
   * resultados. Util para la seccion "Resoluciones similares" del detalle.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution.
   * @param int $limit
   *   Numero maximo de resoluciones similares.
   *
   * @return array
   *   Array de resultados hidratados (misma estructura que search()).
   */
  public function findSimilar(int $resolutionId, int $limit = 5): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');

    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null $entity */
      $entity = $storage->load($resolutionId);

      if (!$entity) {
        return [];
      }

      $vectorIds = $entity->getVectorIds();
      if (empty($vectorIds)) {
        return [];
      }

      $collection = $entity->get('qdrant_collection')->value ?: 'legal_intelligence';
      $qdrantUrl = $config->get('qdrant_url') ?: 'http://qdrant:6333';
      $apiKey = $config->get('qdrant_api_key') ?: '';
      $threshold = (float) ($config->get('score_threshold') ?: 0.65);

      // Usar el primer vector para buscar similares.
      $firstVectorId = $vectorIds[0];

      $headers = ['Content-Type' => 'application/json'];
      if (!empty($apiKey)) {
        $headers['api-key'] = $apiKey;
      }

      // Obtener el vector del primer punto.
      $vectorResponse = $this->httpClient->request('POST',
        "{$qdrantUrl}/collections/{$collection}/points",
        [
          'json' => [
            'ids' => [$firstVectorId],
            'with_vector' => TRUE,
          ],
          'headers' => $headers,
          'timeout' => 10,
        ]
      );

      $vectorData = json_decode($vectorResponse->getBody()->getContents(), TRUE);
      $pointVector = $vectorData['result'][0]['vector'] ?? NULL;

      if (empty($pointVector)) {
        return [];
      }

      // Buscar similares excluyendo la propia resolucion.
      $filter = [
        'must_not' => [
          ['key' => 'resolution_id', 'match' => ['value' => $resolutionId]],
        ],
      ];

      $response = $this->httpClient->request('POST',
        "{$qdrantUrl}/collections/{$collection}/points/search",
        [
          'json' => [
            'vector' => $pointVector,
            'filter' => $filter,
            'limit' => $limit * 3,
            'score_threshold' => $threshold,
            'with_payload' => TRUE,
          ],
          'headers' => $headers,
          'timeout' => 10,
        ]
      );

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $results = $data['result'] ?? [];

      $deduped = $this->deduplicateResults($results);
      $deduped = array_slice($deduped, 0, $limit);

      return $this->hydrateResults($deduped);
    }
    catch (\Exception $e) {
      $this->logger->warning('Search: Error buscando similares para @id: @msg', [
        '@id' => $resolutionId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Busca una resolucion exacta por referencia oficial (external_ref).
   *
   * Se activa cuando la query del usuario coincide con un patron de
   * referencia conocido (V0123-24, STS 1234/2024, C-415/11, ECLI, CELEX).
   * Busca directamente en el entity storage sin pasar por Qdrant.
   *
   * @param string $reference
   *   Referencia oficial exacta.
   *
   * @return array
   *   Array con la estructura estandar de respuesta de busqueda.
   */
  public function lookupByReference(string $reference): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
      $entities = $storage->loadByProperties(['external_ref' => $reference]);

      if (empty($entities)) {
        // Intentar busqueda parcial case-insensitive via query.
        $query = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('external_ref', '%' . $reference . '%', 'LIKE')
          ->range(0, 10);
        $ids = $query->execute();

        if (!empty($ids)) {
          $entities = $storage->loadMultiple($ids);
        }
      }

      if (empty($entities)) {
        return [
          'success' => TRUE,
          'results' => [],
          'total' => 0,
          'facets' => [],
          'error' => NULL,
        ];
      }

      $results = [];
      foreach ($entities as $entity) {
        $results[] = $this->entityToResult($entity, 1.0);
      }

      return [
        'success' => TRUE,
        'results' => $results,
        'total' => count($results),
        'facets' => $this->buildFacets($results),
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Search: Error en lookup por referencia @ref: @msg', [
        '@ref' => $reference,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'results' => [],
        'total' => 0,
        'facets' => [],
        'error' => 'Error looking up reference.',
      ];
    }
  }

  /**
   * Devuelve las facetas disponibles para la busqueda.
   *
   * Recupera valores unicos de source_id, jurisdiction, resolution_type,
   * issuing_body y status_legal desde las resoluciones indexadas.
   * Se usa para popular los filtros facetados en el frontend.
   *
   * @return array
   *   Array asociativo con las facetas y sus valores posibles.
   */
  public function getAvailableFacets(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
      $facets = [];

      // source_id.
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->groupBy('source_id')
        ->count();
      // Drupal entity query no soporta GROUP BY directo, asi que
      // usamos una query con aggregate.
      $facets['source_id'] = $this->getDistinctValues('source_id');
      $facets['jurisdiction'] = $this->getDistinctValues('jurisdiction');
      $facets['resolution_type'] = $this->getDistinctValues('resolution_type');
      $facets['issuing_body'] = $this->getDistinctValues('issuing_body');
      $facets['status_legal'] = [
        'vigente', 'derogada', 'anulada', 'superada', 'parcialmente_derogada',
      ];
      $facets['importance_level'] = [1, 2, 3];

      return $facets;
    }
    catch (\Exception $e) {
      $this->logger->warning('Search: Error obteniendo facetas: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  // =========================================================================
  // METODOS PRIVADOS: Embedding y busqueda Qdrant.
  // =========================================================================

  /**
   * Genera el embedding vectorial de la query del usuario.
   *
   * Usa el proveedor de embeddings configurado por defecto en el modulo AI.
   * El mismo modelo usado para indexar (text-embedding-3-large) se usa para
   * la query, garantizando la compatibilidad del espacio vectorial.
   *
   * @param string $query
   *   Texto de busqueda del usuario.
   *
   * @return array
   *   Vector de embeddings (float[]) o array vacio si falla.
   */
  private function embedQuery(string $query): array {
    $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');
    if (!$defaults) {
      $this->logger->error('Search: No hay proveedor de embeddings configurado.');
      return [];
    }

    try {
      /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
      $provider = $this->aiProvider->createInstance($defaults['provider_id']);
      $modelId = $defaults['model_id'] ?? 'text-embedding-3-large';

      $result = $provider->embeddings($query, $modelId);
      return $result->getNormalized();
    }
    catch (\Exception $e) {
      $this->logger->error('Search: Error generando embedding de query: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Busca vectores similares en una coleccion Qdrant.
   *
   * Ejecuta una busqueda por similitud coseno en Qdrant con el vector
   * de la query, filtros opcionales y threshold minimo de score.
   *
   * @param string $collection
   *   Nombre de la coleccion Qdrant.
   * @param array $vector
   *   Vector de embeddings de la query.
   * @param array $filter
   *   Filtros Qdrant en formato nativo (must, must_not, should).
   * @param int $limit
   *   Numero maximo de resultados.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return array
   *   Array de resultados Qdrant con score, id y payload.
   */
  private function searchQdrant(string $collection, array $vector, array $filter, int $limit, $config): array {
    $qdrantUrl = $config->get('qdrant_url') ?: 'http://qdrant:6333';
    $apiKey = $config->get('qdrant_api_key') ?: '';
    $threshold = (float) ($config->get('score_threshold') ?: 0.65);

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($apiKey)) {
      $headers['api-key'] = $apiKey;
    }

    $body = [
      'vector' => $vector,
      'limit' => $limit * 3,
      'score_threshold' => $threshold,
      'with_payload' => TRUE,
    ];

    if (!empty($filter)) {
      $body['filter'] = $filter;
    }

    try {
      $response = $this->httpClient->request('POST',
        "{$qdrantUrl}/collections/{$collection}/points/search",
        [
          'json' => $body,
          'headers' => $headers,
          'timeout' => 15,
        ]
      );

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['result'] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->warning('Search: Error buscando en Qdrant @collection: @msg', [
        '@collection' => $collection,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Construye filtros Qdrant a partir de los filtros del frontend.
   *
   * Convierte los filtros facetados del usuario en el formato de filtro
   * nativo de Qdrant usando condiciones 'must' (AND) para garantizar
   * aislamiento correcto de los resultados.
   *
   * @param array $filters
   *   Filtros del usuario (source_id, jurisdiction, date_from, etc.).
   *
   * @return array
   *   Filtro Qdrant con condiciones 'must'. Vacio si no hay filtros.
   */
  private function buildQdrantFilter(array $filters): array {
    $must = [];

    // Filtros de match exacto.
    $matchFields = [
      'source_id', 'jurisdiction', 'resolution_type',
      'issuing_body', 'status_legal',
    ];

    foreach ($matchFields as $field) {
      if (!empty($filters[$field])) {
        $must[] = [
          'key' => $field,
          'match' => ['value' => $filters[$field]],
        ];
      }
    }

    // Filtro de importancia (entero).
    if (isset($filters['importance_level']) && $filters['importance_level'] !== '') {
      $must[] = [
        'key' => 'importance_level',
        'match' => ['value' => (int) $filters['importance_level']],
      ];
    }

    // Filtros de rango de fecha.
    if (!empty($filters['date_from'])) {
      $must[] = [
        'key' => 'date_issued',
        'range' => ['gte' => $filters['date_from']],
      ];
    }

    if (!empty($filters['date_to'])) {
      $must[] = [
        'key' => 'date_issued',
        'range' => ['lte' => $filters['date_to']],
      ];
    }

    if (empty($must)) {
      return [];
    }

    return ['must' => $must];
  }

  /**
   * Devuelve las colecciones Qdrant a buscar segun el scope.
   *
   * @param string $scope
   *   Ambito: 'national', 'eu' o 'all'.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo.
   *
   * @return string[]
   *   Array de nombres de colecciones Qdrant.
   */
  private function getCollectionsForScope(string $scope, $config): array {
    $national = $config->get('qdrant_collection_national') ?: 'legal_intelligence';
    $eu = $config->get('qdrant_collection_eu') ?: 'legal_intelligence_eu';

    return match ($scope) {
      'national' => [$national],
      'eu' => [$eu],
      default => [$national, $eu],
    };
  }

  // =========================================================================
  // METODOS PRIVADOS: Hidratacion y deduplicacion.
  // =========================================================================

  /**
   * Deduplica resultados Qdrant por resolution_id.
   *
   * Multiples chunks de la misma resolucion pueden aparecer como resultados
   * separados. Esta funcion conserva solo el chunk con el score mas alto
   * para cada resolution_id unica.
   *
   * @param array $results
   *   Resultados crudos de Qdrant.
   *
   * @return array
   *   Resultados deduplicados, un resultado por resolucion.
   */
  private function deduplicateResults(array $results): array {
    $seen = [];

    foreach ($results as $result) {
      $resId = $result['payload']['resolution_id'] ?? NULL;
      if ($resId === NULL) {
        continue;
      }

      $score = $result['score'] ?? 0;

      if (!isset($seen[$resId]) || $score > ($seen[$resId]['score'] ?? 0)) {
        $seen[$resId] = $result;
      }
    }

    return array_values($seen);
  }

  /**
   * Hidrata resultados Qdrant con datos completos de la entidad.
   *
   * Carga las entidades LegalResolution por sus IDs y combina los datos
   * de la entidad con el score de Qdrant. Si la entidad no existe (fue
   * eliminada pero el vector persiste), se omite del resultado.
   *
   * @param array $qdrantResults
   *   Resultados deduplicados de Qdrant con payload.
   *
   * @return array
   *   Array de resultados hidratados con todos los campos de la entidad.
   */
  private function hydrateResults(array $qdrantResults): array {
    if (empty($qdrantResults)) {
      return [];
    }

    $resolutionIds = array_map(
      fn($r) => $r['payload']['resolution_id'] ?? 0,
      $qdrantResults
    );
    $resolutionIds = array_filter($resolutionIds);

    if (empty($resolutionIds)) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    $entities = $storage->loadMultiple(array_unique($resolutionIds));

    $hydrated = [];
    foreach ($qdrantResults as $result) {
      $resId = $result['payload']['resolution_id'] ?? 0;
      $entity = $entities[$resId] ?? NULL;

      if (!$entity) {
        continue;
      }

      $hydrated[] = $this->entityToResult($entity, $result['score'] ?? 0);
    }

    return $hydrated;
  }

  /**
   * Convierte una entidad LegalResolution a un array de resultado.
   *
   * Extrae todos los campos relevantes de la entidad y los formatea
   * como un array plano adecuado para serializar a JSON en la API REST
   * y para renderizar en las tarjetas del frontend.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity
   *   Entidad LegalResolution completa.
   * @param float $score
   *   Score de similitud de Qdrant (0.0-1.0).
   *
   * @return array
   *   Array con los campos de la resolucion y el score.
   */
  private function entityToResult(LegalResolution $entity, float $score): array {
    return [
      'id' => (int) $entity->id(),
      'title' => $entity->get('title')->value ?? '',
      'source_id' => $entity->get('source_id')->value ?? '',
      'external_ref' => $entity->get('external_ref')->value ?? '',
      'resolution_type' => $entity->get('resolution_type')->value ?? '',
      'issuing_body' => $entity->get('issuing_body')->value ?? '',
      'jurisdiction' => $entity->get('jurisdiction')->value ?? '',
      'date_issued' => $entity->get('date_issued')->value ?? '',
      'status_legal' => $entity->get('status_legal')->value ?? 'vigente',
      'abstract_ai' => $entity->get('abstract_ai')->value ?? '',
      'key_holdings' => $entity->get('key_holdings')->value ?? '',
      'topics' => $entity->getTopics(),
      'cited_legislation' => $entity->getCitedLegislation(),
      'original_url' => $entity->get('original_url')->value ?? '',
      'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
      'is_eu' => $entity->isEuSource(),
      'celex_number' => $entity->get('celex_number')->value ?? '',
      'ecli' => $entity->get('ecli')->value ?? '',
      'impact_spain' => $entity->get('impact_spain')->value ?? '',
      'seo_slug' => $entity->get('seo_slug')->value ?? '',
      'score' => round($score, 4),
    ];
  }

  // =========================================================================
  // METODOS PRIVADOS: Plan limits y facetas.
  // =========================================================================

  /**
   * Verifica si el usuario actual tiene busquedas disponibles en su plan.
   *
   * Delega la comprobacion real al JarabaLexFeatureGateService, que consulta
   * FreemiumVerticalLimit y la tabla jarabalex_feature_usage para verificar
   * el uso mensual contra los limites del plan.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   Configuracion del modulo (mantenido por compatibilidad de firma).
   *
   * @return array
   *   ['allowed' => bool, 'message' => string|null].
   */
  private function checkPlanLimits($config): array {
    $userId = (int) $this->currentUser->id();

    // Usuarios anonimos: permitir busqueda (sera filtrada por permisos de ruta).
    if ($userId === 0) {
      return ['allowed' => TRUE, 'message' => NULL];
    }

    $result = $this->featureGate->check($userId, 'searches_per_month');

    if (!$result->isAllowed()) {
      return [
        'allowed' => FALSE,
        'message' => $result->getUpgradeMessage(),
      ];
    }

    // Registrar uso tras verificacion exitosa.
    $this->featureGate->recordUsage($userId, 'searches_per_month');

    return ['allowed' => TRUE, 'message' => NULL];
  }

  /**
   * Construye facetas a partir de los resultados de busqueda.
   *
   * Agrega los valores de los campos source_id, jurisdiction, resolution_type,
   * issuing_body y status_legal de los resultados para mostrar counts
   * por valor en los filtros facetados del frontend.
   *
   * @param array $results
   *   Resultados hidratados de busqueda.
   *
   * @return array
   *   Array asociativo: [campo => [valor => count, ...], ...].
   */
  private function buildFacets(array $results): array {
    $facetFields = [
      'source_id', 'jurisdiction', 'resolution_type',
      'issuing_body', 'status_legal',
    ];

    $facets = [];
    foreach ($facetFields as $field) {
      $facets[$field] = [];
    }

    foreach ($results as $result) {
      foreach ($facetFields as $field) {
        $value = $result[$field] ?? '';
        if (!empty($value)) {
          $facets[$field][$value] = ($facets[$field][$value] ?? 0) + 1;
        }
      }
    }

    return $facets;
  }

  /**
   * Obtiene valores distintos de un campo de la entidad LegalResolution.
   *
   * @param string $field
   *   Nombre del campo.
   *
   * @return array
   *   Array de valores unicos (strings).
   */
  private function getDistinctValues(string $field): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->exists($field)
        ->range(0, 1000);
      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $values = [];

      foreach ($entities as $entity) {
        $value = $entity->get($field)->value ?? '';
        if (!empty($value)) {
          $values[$value] = TRUE;
        }
      }

      return array_keys($values);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Comprueba si la query es una referencia legal exacta.
   *
   * Evalua la query contra los patrones regex de referencias conocidas
   * (V0123-24, STS 1234/2024, ECLI, C-415/11, CELEX, etc.).
   *
   * @param string $query
   *   Texto de busqueda del usuario.
   *
   * @return bool
   *   TRUE si la query coincide con un patron de referencia exacta.
   */
  private function isExactReference(string $query): bool {
    $query = trim($query);
    foreach (self::REFERENCE_PATTERNS as $pattern) {
      if (preg_match($pattern, $query)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
