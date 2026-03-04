<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Re-vectoriza normas legales cuando su texto cambia.
 *
 * ESTRUCTURA:
 * Plugin QueueWorker que consume items de la cola
 * jaraba_legal_intelligence_vectorization. Se ejecuta durante cron
 * con un maximo de 120 segundos por ejecucion.
 *
 * LOGICA:
 * Cuando el pipeline de ingesta detecta un cambio en el full_text_hash
 * de una norma (ej: consolidacion legislativa, correccion de errores),
 * encola un item para que este worker:
 * 1) Cargue la LegalNorm por ID.
 * 2) Fragmente el texto en chunks (via ChunkingService).
 * 3) Genere embeddings para cada chunk (via EmbeddingService).
 * 4) Upsert los vectores en Qdrant (via la coleccion legal_norms).
 * 5) Actualice embedding_status y chunk_count en la entidad.
 *
 * DEFENSIVO: Si Qdrant no esta disponible, marca la norma como
 * embedding_status = 'pending' para reintento posterior.
 *
 * RELACIONES:
 * - NormVectorizationWorker -> EntityTypeManagerInterface: carga LegalNorm.
 * - NormVectorizationWorker -> ChunkingService: fragmenta texto (@?).
 * - NormVectorizationWorker -> EmbeddingService: genera embeddings (@?).
 * - NormVectorizationWorker <- QueueFactory: consume items encolados.
 * - NormVectorizationWorker <- Drupal cron: ejecutado por sistema de colas.
 *
 * @QueueWorker(
 *   id = "jaraba_legal_intelligence_vectorization",
 *   title = @Translation("Norm Vectorization Queue"),
 *   cron = {"time" = 120}
 * )
 */
class NormVectorizationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Servicio de chunking (opcional).
   *
   * @var object|null
   */
  protected ?object $chunkingService;

  /**
   * Servicio de embeddings (opcional).
   *
   * @var object|null
   */
  protected ?object $embeddingService;

  /**
   * Construye una nueva instancia de NormVectorizationWorker.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    LoggerInterface $logger,
    ?object $chunkingService = NULL,
    ?object $embeddingService = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->chunkingService = $chunkingService;
    $this->embeddingService = $embeddingService;
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
      $container->get('logger.channel.jaraba_legal_intelligence'),
      $container->has('jaraba_legal_knowledge.chunking')
        ? $container->get('jaraba_legal_knowledge.chunking')
        : NULL,
      $container->has('jaraba_legal_knowledge.embedding')
        ? $container->get('jaraba_legal_knowledge.embedding')
        : NULL,
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de re-vectorizacion de norma.
   *
   * @param mixed $data
   *   Datos del item de cola:
   *   - norm_id: (int) ID de la LegalNorm a re-vectorizar.
   *   - reason: (string) Razon de la re-vectorizacion.
   */
  public function processItem($data): void {
    $normId = $data['norm_id'] ?? NULL;

    if ($normId === NULL) {
      $this->logger->warning('NormVectorizationWorker: Item sin norm_id. Descartado.');
      return;
    }

    try {
      $normStorage = $this->entityTypeManager->getStorage('legal_norm');
    }
    catch (\Throwable $e) {
      $this->logger->error('NormVectorizationWorker: Error accediendo al storage de legal_norm: @message', [
        '@message' => $e->getMessage(),
      ]);
      return;
    }

    $norm = $normStorage->load($normId);
    if ($norm === NULL) {
      $this->logger->warning('NormVectorizationWorker: Norma @id no encontrada.', [
        '@id' => $normId,
      ]);
      return;
    }

    // Verificar que los servicios de chunking y embedding estan disponibles.
    if ($this->chunkingService === NULL || $this->embeddingService === NULL) {
      $this->logger->warning('NormVectorizationWorker: Servicios de chunking/embedding no disponibles. Norma @id marcada como pending.', [
        '@id' => $normId,
      ]);
      $norm->set('embedding_status', 'pending');
      $norm->save();
      return;
    }

    $fullText = $norm->get('full_text')->value ?? '';
    if (empty($fullText)) {
      $this->logger->info('NormVectorizationWorker: Norma @id sin texto completo. Omitida.', [
        '@id' => $normId,
      ]);
      return;
    }

    try {
      // 1. Fragmentar texto en chunks.
      $chunks = $this->chunkingService->chunk($fullText, [
        'norm_id' => $normId,
        'title' => $norm->get('title')->value ?? '',
      ]);

      // 2. Generar embeddings y upsert en Qdrant.
      $this->embeddingService->embedAndStore($chunks, [
        'entity_type' => 'legal_norm',
        'entity_id' => $normId,
        'collection' => 'legal_norms',
      ]);

      // 3. Actualizar metadata de la entidad.
      $norm->set('embedding_status', 'completed');
      $norm->set('chunk_count', count($chunks));
      $norm->save();

      $this->logger->info('NormVectorizationWorker: Norma @id re-vectorizada exitosamente (@chunks chunks). Razon: @reason', [
        '@id' => $normId,
        '@chunks' => count($chunks),
        '@reason' => $data['reason'] ?? 'unknown',
      ]);
    }
    catch (\Throwable $e) {
      // Defensivo: si Qdrant falla, marcar como pending para reintento.
      $this->logger->error('NormVectorizationWorker: Error vectorizando norma @id: @message. Marcada como pending.', [
        '@id' => $normId,
        '@message' => $e->getMessage(),
      ]);

      try {
        $norm->set('embedding_status', 'pending');
        $norm->save();
      }
      catch (\Throwable) {
        // Si ni siquiera podemos guardar el status, solo loggeamos.
      }
    }
  }

}
