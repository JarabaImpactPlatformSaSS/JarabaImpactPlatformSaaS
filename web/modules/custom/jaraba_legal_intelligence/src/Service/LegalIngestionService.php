<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_legal_intelligence\Entity\LegalSource;
use Drupal\jaraba_legal_intelligence\Service\Spider\SpiderInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Servicio de orquestacion de ingesta de resoluciones juridicas.
 *
 * ESTRUCTURA:
 * Servicio central que coordina la ingesta de resoluciones desde todas las
 * fuentes juridicas configuradas (CENDOJ, BOE, DGT, TEAC). Gestiona el ciclo
 * completo: verificacion de programacion, invocacion de spiders, deduplicacion
 * por external_ref y content_hash, creacion de entidades LegalResolution y
 * encolado de items para procesamiento NLP posterior.
 *
 * LOGICA:
 * El metodo runScheduledIngestion() se invoca desde hook_cron() y evalua
 * cada LegalSource activa para determinar si es momento de ejecutar su spider
 * segun la frecuencia configurada (daily=86400s, weekly=604800s, monthly=2592000s).
 * Para cada fuente pendiente, ingestFromSource() ejecuta el spider, deduplica
 * resultados y crea entidades. La deduplicacion opera en dos niveles:
 * 1) external_ref (clave de negocio unica) y 2) content_hash (SHA-256 del
 * texto completo). Las resoluciones nuevas se encolan en
 * jaraba_legal_intelligence_nlp para procesamiento por el pipeline NLP de 9
 * etapas (Fase 2). Los spiders se resuelven dinamicamente desde el contenedor
 * de servicios usando el patron jaraba_legal_intelligence.spider.{sourceId}.
 *
 * RELACIONES:
 * - LegalIngestionService -> SpiderInterface: invoca crawl() de cada spider.
 * - LegalIngestionService -> LegalSource: lee entidades para programacion.
 * - LegalIngestionService -> LegalResolution: crea entidades con datos crudos.
 * - LegalIngestionService -> QueueFactory: encola items para pipeline NLP.
 * - LegalIngestionService -> ContainerInterface: resuelve spiders dinamicamente.
 * - LegalIngestionService <- hook_cron(): invocado periodicamente por Drupal.
 * - LegalIngestionService <- LegalIngestionWorker: procesa items encolados.
 */
class LegalIngestionService {

  /**
   * Intervalos de frecuencia en segundos.
   *
   * daily = 86400s (24h), weekly = 604800s (7d), monthly = 2592000s (30d).
   */
  protected const FREQUENCY_INTERVALS = [
    'daily' => 86400,
    'weekly' => 604800,
    'monthly' => 2592000,
  ];

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Factoria de colas de Drupal.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * Cliente HTTP para peticiones externas.
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
   * Contenedor de servicios para resolucion dinamica de spiders.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * Construye una nueva instancia de LegalIngestionService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder a legal_source y legal_resolution.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Factoria de colas para encolar items para procesamiento NLP.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para peticiones externas si se necesitan fuera del spider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para acceder a parametros del modulo.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Contenedor de servicios para resolucion dinamica de spiders.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    QueueFactory $queueFactory,
    ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
    ContainerInterface $container,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->queueFactory = $queueFactory;
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
    $this->container = $container;
  }

  /**
   * Ejecuta la ingesta programada para todas las fuentes activas.
   *
   * Evalua cada LegalSource con is_active=TRUE para determinar si es momento
   * de ejecutar su spider segun la frecuencia configurada y el timestamp de
   * la ultima sincronizacion. Para cada fuente pendiente, invoca
   * ingestFromSource() que ejecuta el ciclo completo de ingesta.
   *
   * @param int $currentTime
   *   Timestamp actual (Unix epoch). Se pasa como parametro para facilitar
   *   testing con tiempos controlados.
   */
  public function runScheduledIngestion(int $currentTime): void {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_source');
    }
    catch (\Exception $e) {
      $this->logger->error('LegalIngestionService: Error accediendo al storage de legal_source: @message', [
        '@message' => $e->getMessage(),
      ]);
      return;
    }

    // Cargar todas las fuentes activas ordenadas por prioridad (menor = mas prioritaria).
    $sourceIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('is_active', TRUE)
      ->sort('priority', 'ASC')
      ->execute();

    if (empty($sourceIds)) {
      $this->logger->info('LegalIngestionService: No hay fuentes activas configuradas.');
      return;
    }

    /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalSource[] $sources */
    $sources = $storage->loadMultiple($sourceIds);

    $totalIngested = 0;

    foreach ($sources as $source) {
      if (!$this->isDue($source, $currentTime)) {
        $this->logger->debug('LegalIngestionService: Fuente @name no requiere sincronizacion aun.', [
          '@name' => $source->get('machine_name')->value,
        ]);
        continue;
      }

      $this->logger->info('LegalIngestionService: Iniciando ingesta para fuente @name.', [
        '@name' => $source->get('machine_name')->value,
      ]);

      try {
        $count = $this->ingestFromSource($source);
        $totalIngested += $count;

        $this->logger->info('LegalIngestionService: Fuente @name completada: @count nuevas resoluciones.', [
          '@name' => $source->get('machine_name')->value,
          '@count' => $count,
        ]);
      }
      catch (\Exception $e) {
        // Registrar error en la fuente para monitorizacion.
        $errorCount = (int) ($source->get('error_count')->value ?? 0);
        $source->set('error_count', $errorCount + 1);
        $source->set('last_error', $e->getMessage());
        $source->save();

        $this->logger->error('LegalIngestionService: Error en fuente @name: @message', [
          '@name' => $source->get('machine_name')->value,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    $this->logger->info('LegalIngestionService: Ingesta programada completada. Total nuevas resoluciones: @total.', [
      '@total' => $totalIngested,
    ]);
  }

  /**
   * Ejecuta la ingesta completa desde una fuente juridica.
   *
   * Ciclo completo: obtiene el spider correspondiente, ejecuta crawl(),
   * deduplica por external_ref y content_hash, crea entidades LegalResolution
   * para las resoluciones nuevas, y encola cada una para procesamiento NLP.
   * Actualiza last_sync_at y total_documents de la fuente tras completar.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalSource $source
   *   Entidad LegalSource de la fuente a ingestar.
   *
   * @return int
   *   Numero de nuevas resoluciones creadas.
   */
  public function ingestFromSource(LegalSource $source): int {
    $machineName = $source->get('machine_name')->value;
    $spider = $this->getSpider($machineName);

    if ($spider === NULL) {
      $this->logger->warning('LegalIngestionService: No se encontro spider para fuente @name.', [
        '@name' => $machineName,
      ]);
      return 0;
    }

    // Preparar opciones de fecha para el crawl.
    $frequency = $source->get('frequency')->value ?? 'daily';
    $interval = self::FREQUENCY_INTERVALS[$frequency] ?? self::FREQUENCY_INTERVALS['daily'];
    $dateFrom = date('Y-m-d', (int) (time() - $interval));
    $dateTo = date('Y-m-d');

    $rawResults = $spider->crawl([
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
    ]);

    if (empty($rawResults)) {
      $this->logger->info('LegalIngestionService: Spider @name no devolvio resultados.', [
        '@name' => $machineName,
      ]);
      // Actualizar last_sync_at aunque no haya resultados.
      $source->set('last_sync_at', time());
      $source->save();
      return 0;
    }

    $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
    $nlpQueue = $this->queueFactory->get('jaraba_legal_intelligence_nlp');
    $newCount = 0;

    foreach ($rawResults as $rawData) {
      // Deduplicacion nivel 1: por external_ref (clave de negocio).
      $externalRef = $rawData['external_ref'] ?? '';
      if (empty($externalRef)) {
        $this->logger->debug('LegalIngestionService: Resolucion sin external_ref omitida.');
        continue;
      }

      $existing = $resolutionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('external_ref', $externalRef)
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        $this->logger->debug('LegalIngestionService: Resolucion @ref ya existe (external_ref). Omitida.', [
          '@ref' => $externalRef,
        ]);
        continue;
      }

      // Deduplicacion nivel 2: por content_hash (SHA-256 del texto completo).
      $contentHash = hash('sha256', $rawData['full_text'] ?? '');

      if (!empty($rawData['full_text'])) {
        $hashDuplicate = $resolutionStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('content_hash', $contentHash)
          ->range(0, 1)
          ->execute();

        if (!empty($hashDuplicate)) {
          $this->logger->debug('LegalIngestionService: Resolucion @ref duplicada por content_hash. Omitida.', [
            '@ref' => $externalRef,
          ]);
          continue;
        }
      }

      // Crear entidad LegalResolution con los datos crudos del spider.
      $sourceId = $rawData['source_id'] ?? $machineName;
      $isEu = in_array($sourceId, ['tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue'], TRUE);

      $entityData = [
        'source_id' => $sourceId,
        'external_ref' => $externalRef,
        'content_hash' => $contentHash,
        'title' => $rawData['title'] ?? '',
        'resolution_type' => $rawData['resolution_type'] ?? '',
        'issuing_body' => $rawData['issuing_body'] ?? '',
        'jurisdiction' => $rawData['jurisdiction'] ?? '',
        'date_issued' => $rawData['date_issued'] ?? NULL,
        'date_published' => $rawData['date_published'] ?? NULL,
        'original_url' => $rawData['original_url'] ?? '',
        'full_text' => $rawData['full_text'] ?? '',
        'status_legal' => $rawData['status_legal'] ?? 'vigente',
        'language_original' => $rawData['language_original'] ?? 'es',
        'qdrant_collection' => $isEu ? 'legal_intelligence_eu' : 'legal_intelligence',
      ];

      // Campos EU-especificos (Fase 4): pasar si el spider los proporciona.
      $euFields = [
        'celex_number', 'ecli', 'case_number', 'procedure_type',
        'respondent_state', 'cedh_articles', 'eu_legal_basis',
        'advocate_general', 'importance_level',
      ];
      foreach ($euFields as $field) {
        if (isset($rawData[$field]) && $rawData[$field] !== '') {
          $entityData[$field] = $rawData[$field];
        }
      }

      $entity = $resolutionStorage->create($entityData);

      try {
        $entity->save();

        // Encolar para procesamiento NLP (pipeline de 9 etapas).
        $nlpQueue->createItem([
          'resolution_id' => $entity->id(),
        ]);

        $newCount++;
      }
      catch (\Exception $e) {
        $this->logger->error('LegalIngestionService: Error guardando resolucion @ref: @message', [
          '@ref' => $externalRef,
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // Actualizar metadatos de la fuente tras la sincronizacion.
    $totalDocuments = (int) ($source->get('total_documents')->value ?? 0);
    $source->set('last_sync_at', time());
    $source->set('total_documents', $totalDocuments + $newCount);
    $source->save();

    return $newCount;
  }

  /**
   * Resuelve dinamicamente el spider asociado a una fuente.
   *
   * Busca el servicio jaraba_legal_intelligence.spider.{$sourceId} en el
   * contenedor de dependencias de Drupal. Este patron permite anadir
   * nuevos spiders simplemente registrandolos en services.yml sin
   * modificar la logica del servicio de ingesta.
   *
   * @param string $sourceId
   *   Identificador maquina de la fuente (machine_name de LegalSource).
   *
   * @return \Drupal\jaraba_legal_intelligence\Service\Spider\SpiderInterface|null
   *   Instancia del spider si existe, NULL si no se encontro el servicio.
   */
  public function getSpider(string $sourceId): ?SpiderInterface {
    $serviceId = 'jaraba_legal_intelligence.spider.' . $sourceId;

    if (!$this->container->has($serviceId)) {
      $this->logger->warning('LegalIngestionService: Servicio spider @service no registrado.', [
        '@service' => $serviceId,
      ]);
      return NULL;
    }

    $spider = $this->container->get($serviceId);

    if (!$spider instanceof SpiderInterface) {
      $this->logger->error('LegalIngestionService: Servicio @service no implementa SpiderInterface.', [
        '@service' => $serviceId,
      ]);
      return NULL;
    }

    return $spider;
  }

  /**
   * Determina si una fuente necesita sincronizacion.
   *
   * Compara el timestamp de la ultima sincronizacion (last_sync_at) con
   * el intervalo de la frecuencia configurada. Si nunca se ha sincronizado
   * (last_sync_at = NULL o 0), siempre retorna TRUE.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalSource $source
   *   Entidad LegalSource a evaluar.
   * @param int $currentTime
   *   Timestamp actual (Unix epoch).
   *
   * @return bool
   *   TRUE si la fuente necesita sincronizacion.
   */
  public function isDue(LegalSource $source, int $currentTime): bool {
    $lastSyncAt = (int) ($source->get('last_sync_at')->value ?? 0);

    // Si nunca se ha sincronizado, es momento de hacerlo.
    if ($lastSyncAt === 0) {
      return TRUE;
    }

    $frequency = $source->get('frequency')->value ?? 'daily';
    $interval = self::FREQUENCY_INTERVALS[$frequency] ?? self::FREQUENCY_INTERVALS['daily'];

    return ($currentTime - $lastSyncAt) >= $interval;
  }

}
