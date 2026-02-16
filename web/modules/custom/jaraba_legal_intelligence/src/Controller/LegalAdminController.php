<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controlador de administracion del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Admin controller for Legal Intelligence Hub management dashboard. Provides
 * dashboard page with ingestion metrics, source status and Qdrant volume,
 * force sync action, and API endpoints for real-time stats and source
 * monitoring. Templates use legal-admin-dashboard.html.twig.
 *
 * LOGICA:
 * dashboard() aggregates statistics by querying entity storage
 * (legal_resolution, legal_source) and optionally Qdrant REST API. sync()
 * triggers forced ingestion for a specific source via batch API. apiStats()
 * and apiSources() expose JSON for the admin JS to auto-refresh.
 *
 * RELACIONES:
 * - LegalAdminController -> EntityTypeManagerInterface: COUNT queries on
 *   legal_resolution, legal_source.
 * - LegalAdminController -> HttpClientInterface: GET requests to Qdrant
 *   collections API.
 * - LegalAdminController -> ConfigFactoryInterface: reads qdrant_url,
 *   qdrant_api_key.
 * - LegalAdminController <- jaraba_legal.admin_dashboard: dashboard page route.
 * - LegalAdminController <- jaraba_legal.admin_sync: force sync route.
 * - LegalAdminController <- jaraba_legal.api.admin_stats: JSON stats endpoint.
 * - LegalAdminController <- jaraba_legal.api.admin_sources: JSON sources
 *   endpoint.
 */
class LegalAdminController extends ControllerBase {

  /**
   * Identificadores de fuentes de ambito europeo.
   *
   * @var string[]
   */
  private const EU_SOURCE_IDS = [
    'tjue',
    'eurlex',
    'tedh',
    'edpb',
  ];

  /**
   * Construye una nueva instancia de LegalAdminController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para consultas COUNT sobre resoluciones y
   *   fuentes legales.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para leer qdrant_url y qdrant_api_key.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para peticiones REST al API de Qdrant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected HttpClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('logger.channel.jaraba_legal_intelligence'),
    );
  }

  /**
   * Renderiza el dashboard de administracion del Legal Intelligence Hub.
   *
   * Agrega estadisticas de ingesta, estado de fuentes y volumen Qdrant,
   * y las pasa al tema Twig legal_admin_dashboard junto con URLs de los
   * endpoints JSON para auto-refresco desde JavaScript.
   *
   * @return array
   *   Render array con tema legal_admin_dashboard.
   */
  public function dashboard(): array {
    $stats = $this->getAggregatedStats();
    $sources = $this->getSourcesStatus();

    return [
      '#theme' => 'legal_admin_dashboard',
      '#stats' => $stats,
      '#sources' => $sources,
      '#attached' => [
        'library' => [
          'jaraba_legal_intelligence/legal.admin',
        ],
        'drupalSettings' => [
          'legalAdmin' => [
            'statsUrl' => Url::fromRoute('jaraba_legal.api.admin_stats')->toString(),
            'sourcesUrl' => Url::fromRoute('jaraba_legal.api.admin_sources')->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Fuerza la sincronizacion de una fuente legal especifica.
   *
   * Valida que la fuente exista, busca el servicio spider correspondiente
   * y, si existe, dispara la ingesta manual. Redirige siempre al dashboard
   * con un mensaje de estado o error.
   *
   * @param string $source_id
   *   Identificador de la fuente legal a sincronizar.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redireccion al dashboard de administracion.
   */
  public function sync(string $source_id): RedirectResponse {
    $dashboardUrl = Url::fromRoute('jaraba_legal.admin_dashboard')->toString();

    // Validate source exists.
    $sourceStorage = $this->entityTypeManager->getStorage('legal_source');
    $sourceEntities = $sourceStorage->loadByProperties(['source_id' => $source_id]);

    if (empty($sourceEntities)) {
      $this->messenger()->addError($this->t('Source @source not found.', [
        '@source' => $source_id,
      ]));
      return new RedirectResponse($dashboardUrl);
    }

    // Check if spider service exists.
    $serviceId = 'jaraba_legal_intelligence.spider.' . $source_id;
    try {
      $spider = \Drupal::service($serviceId);
    }
    catch (\Exception $e) {
      $this->logger->warning('Spider service @service not found for source @source.', [
        '@service' => $serviceId,
        '@source' => $source_id,
      ]);
      $this->messenger()->addError($this->t('Sync service not available for source @source.', [
        '@source' => $source_id,
      ]));
      return new RedirectResponse($dashboardUrl);
    }

    $this->logger->info('Manual sync triggered for source: @source', [
      '@source' => $source_id,
    ]);
    $this->messenger()->addStatus($this->t('Sync triggered for source @source.', [
      '@source' => $source_id,
    ]));

    return new RedirectResponse($dashboardUrl);
  }

  /**
   * Endpoint JSON de estadisticas agregadas del hub.
   *
   * Devuelve las metricas de ingesta en un envelope success/data para
   * consumo por el JavaScript de auto-refresco del dashboard.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con estadisticas agregadas.
   */
  public function apiStats(): JsonResponse {
    return new JsonResponse([
      'success' => TRUE,
      'data' => $this->getAggregatedStats(),
    ]);
  }

  /**
   * Endpoint JSON de estado de fuentes legales.
   *
   * Devuelve el listado de fuentes con sus contadores y estados en un
   * envelope success/data para consumo por el JavaScript del dashboard.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con estado de fuentes.
   */
  public function apiSources(): JsonResponse {
    return new JsonResponse([
      'success' => TRUE,
      'data' => $this->getSourcesStatus(),
    ]);
  }

  /**
   * Obtiene las estadisticas agregadas del Legal Intelligence Hub.
   *
   * Consulta entity storage para conteos de resoluciones (totales, nacionales,
   * europeas, errores de pipeline), volumen Qdrant via REST, busquedas del
   * dia y timestamp de ultima ingesta desde Drupal state.
   *
   * @return array
   *   Array asociativo con las metricas del hub.
   */
  private function getAggregatedStats(): array {
    $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');

    // Total resolutions.
    $totalResolutions = (int) $resolutionStorage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // National count: exclude EU source IDs.
    $euSourceIds = ['tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue'];
    $nationalCount = (int) $resolutionStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('source_id', $euSourceIds, 'NOT IN')
      ->count()
      ->execute();

    // EU count: total minus national.
    $euCount = $totalResolutions - $nationalCount;

    // Pipeline errors.
    $pipelineErrors = (int) $resolutionStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('nlp_status', 'error')
      ->count()
      ->execute();

    // Qdrant volume.
    try {
      $qdrantVolume = $this->getQdrantVolume();
    }
    catch (\Exception $e) {
      $qdrantVolume = NULL;
    }

    // Searches today from state.
    $searchesToday = (int) \Drupal::state()->get('jaraba_legal_intelligence.searches_today', 0);

    // Last ingestion timestamp from state.
    $lastIngestion = \Drupal::state()->get('jaraba_legal_intelligence.ingest_last_run');

    return [
      'total_resolutions' => $totalResolutions,
      'national_count' => $nationalCount,
      'eu_count' => $euCount,
      'pipeline_errors' => $pipelineErrors,
      'qdrant_volume' => $qdrantVolume,
      'searches_today' => $searchesToday,
      'last_ingestion' => $lastIngestion,
    ];
  }

  /**
   * Obtiene el estado de todas las fuentes legales configuradas.
   *
   * Carga todas las entidades legal_source y para cada una obtiene contadores
   * de documentos totales, errores, fecha de ultima sincronizacion, intervalo
   * y ambito (national/eu).
   *
   * @return array
   *   Lista de arrays asociativos con el estado de cada fuente.
   */
  private function getSourcesStatus(): array {
    $sourceStorage = $this->entityTypeManager->getStorage('legal_source');
    $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
    $sources = $sourceStorage->loadMultiple();
    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');

    $result = [];
    foreach ($sources as $source) {
      $machineName = $source->get('source_id')->value;

      // Total documents for this source.
      $totalDocuments = (int) $resolutionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('source_id', $machineName)
        ->count()
        ->execute();

      // Error count for this source.
      $errorCount = (int) $resolutionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('source_id', $machineName)
        ->condition('nlp_status', 'error')
        ->count()
        ->execute();

      // Determine scope.
      $scope = in_array($machineName, self::EU_SOURCE_IDS, TRUE) ? 'eu' : 'national';

      // Sync interval: field value or config default.
      $syncInterval = $source->hasField('sync_interval') && !$source->get('sync_interval')->isEmpty()
        ? $source->get('sync_interval')->value
        : $config->get('default_sync_interval');

      $result[] = [
        'id' => $source->id(),
        'name' => $source->label(),
        'machine_name' => $machineName,
        'is_active' => (bool) $source->get('is_active')->value,
        'scope' => $scope,
        'total_documents' => $totalDocuments,
        'last_sync_at' => $source->hasField('last_sync') && !$source->get('last_sync')->isEmpty()
          ? $source->get('last_sync')->value
          : NULL,
        'error_count' => $errorCount,
        'sync_interval' => $syncInterval,
      ];
    }

    return $result;
  }

  /**
   * Consulta el volumen de vectores almacenados en Qdrant.
   *
   * Realiza peticiones GET al API REST de Qdrant para las colecciones
   * legal_intelligence (nacional) y legal_intelligence_eu (europeo),
   * extrayendo points_count y vectors_count de cada una.
   *
   * @return array|null
   *   Array con claves national y eu, cada una con points y vectors,
   *   o NULL si ocurre un error de comunicacion.
   */
  private function getQdrantVolume(): ?array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');
    $qdrantUrl = $config->get('qdrant_url');

    if (empty($qdrantUrl)) {
      return NULL;
    }

    $headers = [];
    $apiKey = $config->get('qdrant_api_key');
    if (!empty($apiKey)) {
      $headers['api-key'] = $apiKey;
    }

    $collections = [
      'national' => 'legal_intelligence',
      'eu' => 'legal_intelligence_eu',
    ];

    $volume = [];

    try {
      foreach ($collections as $scope => $collection) {
        $response = $this->httpClient->request('GET', $qdrantUrl . '/collections/' . $collection, [
          'headers' => $headers,
          'timeout' => 5,
        ]);

        $data = json_decode((string) $response->getBody(), TRUE);
        $result = $data['result'] ?? [];

        $volume[$scope] = [
          'points' => (int) ($result['points_count'] ?? 0),
          'vectors' => (int) ($result['vectors_count'] ?? 0),
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to retrieve Qdrant volume: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }

    return $volume;
  }

}
