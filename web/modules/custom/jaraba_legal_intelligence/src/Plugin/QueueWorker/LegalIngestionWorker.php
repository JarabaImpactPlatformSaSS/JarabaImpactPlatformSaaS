<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa resoluciones juridicas encoladas para el pipeline NLP.
 *
 * ESTRUCTURA:
 * Plugin QueueWorker que consume items de la cola jaraba_legal_intelligence_nlp.
 * Cada item contiene el ID de una entidad LegalResolution recien ingestada que
 * necesita procesamiento por el pipeline NLP de 9 etapas. Se ejecuta durante
 * cron con un tiempo maximo de 120 segundos por ejecucion.
 *
 * LOGICA:
 * Para cada item encolado: 1) Carga la entidad LegalResolution por ID.
 * 2) Si no existe (borrada entre ingesta y procesamiento), registra warning
 * y retorna sin error para que el item se elimine de la cola. 3) Invoca
 * processResolution() del servicio NLP pipeline, que ejecuta las 9 etapas:
 * extraccion Tika, clasificacion Gemini, NER spaCy, resumen, key holdings,
 * topics, legislacion citada, vectorizacion Qdrant y grafo de citas.
 * 4) Actualiza el timestamp last_nlp_processed de la entidad.
 *
 * RELACIONES:
 * - LegalIngestionWorker -> EntityTypeManagerInterface: carga LegalResolution.
 * - LegalIngestionWorker -> LegalNlpPipelineService: ejecuta pipeline NLP.
 * - LegalIngestionWorker <- QueueFactory (jaraba_legal_intelligence_nlp):
 *   consume items encolados por LegalIngestionService.
 * - LegalIngestionWorker <- Drupal cron: ejecutado por el sistema de colas.
 *
 * @QueueWorker(
 *   id = "jaraba_legal_intelligence_nlp",
 *   title = @Translation("Legal NLP Processing Queue"),
 *   cron = {"time" = 120}
 * )
 */
class LegalIngestionWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio del pipeline NLP de 9 etapas.
   *
   * @var \Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService
   */
  protected LegalNlpPipelineService $nlpPipeline;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye una nueva instancia de LegalIngestionWorker.
   *
   * @param array $configuration
   *   Configuracion del plugin.
   * @param string $plugin_id
   *   ID del plugin.
   * @param mixed $plugin_definition
   *   Definicion del plugin.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para cargar LegalResolution.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalNlpPipelineService $nlpPipeline
   *   Servicio del pipeline NLP para procesar resoluciones.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    LegalNlpPipelineService $nlpPipeline,
    LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->nlpPipeline = $nlpPipeline;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('jaraba_legal_intelligence.nlp_pipeline'),
      $container->get('logger.channel.jaraba_legal_intelligence'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de la cola NLP.
   *
   * Carga la entidad LegalResolution indicada, ejecuta el pipeline NLP
   * completo y actualiza el timestamp de ultimo procesamiento.
   *
   * @param mixed $data
   *   Datos del item de cola. Array con clave 'resolution_id'.
   */
  public function processItem($data): void {
    $resolutionId = $data['resolution_id'] ?? NULL;

    if ($resolutionId === NULL) {
      $this->logger->warning('LegalIngestionWorker: Item de cola sin resolution_id. Descartado.');
      return;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
    }
    catch (\Exception $e) {
      $this->logger->error('LegalIngestionWorker: Error accediendo al storage de legal_resolution: @message', [
        '@message' => $e->getMessage(),
      ]);
      return;
    }

    /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null $entity */
    $entity = $storage->load($resolutionId);

    if ($entity === NULL) {
      $this->logger->warning('LegalIngestionWorker: Resolucion @id no encontrada. Posiblemente eliminada tras la ingesta.', [
        '@id' => $resolutionId,
      ]);
      return;
    }

    try {
      // Ejecutar pipeline NLP de 9 etapas.
      $this->nlpPipeline->processResolution($entity);

      // Actualizar timestamp de ultimo procesamiento NLP.
      $entity->set('last_nlp_processed', time());
      $entity->save();

      $this->logger->info('LegalIngestionWorker: Resolucion @ref (ID: @id) procesada por pipeline NLP.', [
        '@ref' => $entity->get('external_ref')->value,
        '@id' => $resolutionId,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('LegalIngestionWorker: Error procesando resolucion @id via NLP: @message', [
        '@id' => $resolutionId,
        '@message' => $e->getMessage(),
      ]);

      // Re-lanzar la excepcion para que el item vuelva a la cola
      // y se reintente en la siguiente ejecucion de cron.
      throw $e;
    }
  }

}
