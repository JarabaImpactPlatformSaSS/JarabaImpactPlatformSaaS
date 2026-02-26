<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Psr\Log\LoggerInterface;

/**
 * Agent Long-Term Memory Service (FIX-039/GAP-07).
 *
 * ESTRUCTURA:
 *   Proporciona memoria persistente cross-sesion para agentes,
 *   respaldada por BD (structured facts) y Qdrant (semantic recall).
 *
 * LOGICA:
 *   - remember(): Almacena en BD + indexa embedding en Qdrant.
 *   - recall(): Merge de memorias cronologicas (BD) + semanticas (Qdrant).
 *   - buildMemoryPrompt(): Genera seccion XML con memorias relevantes.
 *
 * GAP-07: Implementa indexInQdrant() real con generateEmbedding() +
 *         upsert en coleccion 'agent_memory'. Implementa semanticRecall()
 *         real con vectorSearch en Qdrant. Merge inteligente: top-N
 *         cronologicas + top-N semanticas, deduplicadas.
 *
 * Memory types: fact, preference, interaction_summary, correction.
 */
class AgentLongTermMemoryService {

  /**
   * Tipos de memoria validos.
   */
  protected const MEMORY_TYPES = ['fact', 'preference', 'interaction_summary', 'correction'];

  /**
   * Nombre de la coleccion Qdrant para memoria de agentes.
   */
  protected const QDRANT_COLLECTION = 'agent_memory';

  /**
   * Threshold de similitud para semantic recall (GAP-07).
   *
   * Mas bajo que cache (0.92) porque es recall de memoria,
   * no deduplicacion — queremos resultados mas amplios.
   */
  protected const SEMANTIC_THRESHOLD = 0.75;

  /**
   * Construye el servicio de memoria a largo plazo.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   * @param \Drupal\jaraba_rag\Client\QdrantDirectClient|null $qdrantClient
   *   Cliente Qdrant directo para indexacion/busqueda semantica.
   *   Opcional — si no disponible, solo funciona recall cronologico.
   * @param \Drupal\ai\AiProviderPluginManager|null $aiProvider
   *   Gestor de proveedores IA para generacion de embeddings (GAP-07).
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $configFactory
   *   Factoria de configuracion para modelo de embeddings (GAP-07).
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?QdrantDirectClient $qdrantClient = NULL,
    protected readonly ?AiProviderPluginManager $aiProvider = NULL,
    protected readonly ?ConfigFactoryInterface $configFactory = NULL,
  ) {}

  /**
   * Almacena una memoria para un agente/tenant.
   *
   * @param string $agentId
   *   ID del agente.
   * @param string $tenantId
   *   ID del tenant.
   * @param string $type
   *   Tipo de memoria: fact, preference, interaction_summary, correction.
   * @param string $content
   *   Contenido textual de la memoria.
   * @param array $metadata
   *   Metadatos adicionales.
   *
   * @return array
   *   Resultado con success y memory_id.
   */
  public function remember(string $agentId, string $tenantId, string $type, string $content, array $metadata = []): array {
    if (!in_array($type, self::MEMORY_TYPES, TRUE)) {
      return ['success' => FALSE, 'error' => "Tipo de memoria invalido: {$type}"];
    }

    try {
      $memoryId = NULL;

      // Almacenar en BD via entidad shared_memory.
      if ($this->entityTypeManager->hasDefinition('shared_memory')) {
        $storage = $this->entityTypeManager->getStorage('shared_memory');
        $memory = $storage->create([
          'agent_id' => $agentId,
          'tenant_id' => $tenantId,
          'memory_type' => $type,
          'content' => $content,
          'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
          'created' => time(),
        ]);
        $memory->save();
        $memoryId = $memory->id();
      }
      else {
        // Fallback: State API para almacenamiento simple.
        $key = "agent_memory:{$agentId}:{$tenantId}:" . time();
        \Drupal::state()->set($key, [
          'type' => $type,
          'content' => $content,
          'metadata' => $metadata,
          'created' => time(),
        ]);
        $memoryId = $key;
      }

      // GAP-07: Indexar en Qdrant para semantic recall.
      // PRESAVE-RESILIENCE-001: try-catch para que fallo de Qdrant no impida save en BD.
      if ($this->qdrantClient && $this->aiProvider) {
        try {
          $this->indexInQdrant($agentId, $tenantId, $type, $content, $metadata, $memoryId);
        }
        catch (\Exception $e) {
          $this->logger->warning('GAP-07: Qdrant indexing fallido para memoria (non-blocking): @msg', [
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('GAP-07: Memoria almacenada — agent=@agent, tenant=@tenant, type=@type.', [
        '@agent' => $agentId,
        '@tenant' => $tenantId,
        '@type' => $type,
      ]);

      return [
        'success' => TRUE,
        'memory_id' => $memoryId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error almacenando memoria: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Recupera memorias relevantes para un agente/tenant (GAP-07).
   *
   * Logica: Merge inteligente de memorias cronologicas (BD) +
   *         semanticas (Qdrant). Cuando hay query, busca en ambas
   *         fuentes y deduplica por ID.
   *
   * @param string $agentId
   *   ID del agente.
   * @param string $tenantId
   *   ID del tenant.
   * @param string|null $query
   *   Query opcional para busqueda semantica en Qdrant.
   * @param int $limit
   *   Maximo de memorias a retornar.
   *
   * @return array
   *   Array de items de memoria con id, type, content, metadata, created.
   */
  public function recall(string $agentId, string $tenantId, ?string $query = NULL, int $limit = 10): array {
    $chronological = $this->chronologicalRecall($agentId, $tenantId, $limit);

    // Sin query o sin Qdrant: retornar solo cronologicas.
    if ($query === NULL || !$this->qdrantClient || !$this->aiProvider) {
      return $chronological;
    }

    // GAP-07: Merge cronologico + semantico.
    $semantic = $this->semanticRecall($query, $agentId, $tenantId, $limit);

    return $this->mergeMemories($chronological, $semantic, $limit);
  }

  /**
   * Construye seccion de prompt con memorias del agente (GAP-07).
   *
   * @param string $agentId
   *   ID del agente.
   * @param string $tenantId
   *   ID del tenant.
   * @param string|null $currentQuery
   *   Query actual del usuario para recall semantico (GAP-07).
   *
   * @return string
   *   Seccion XML de memoria para inyeccion en system prompt.
   */
  public function buildMemoryPrompt(string $agentId, string $tenantId, ?string $currentQuery = NULL): string {
    $memories = $this->recall($agentId, $tenantId, $currentQuery, 5);

    if (empty($memories)) {
      return '';
    }

    $output = "<agent_memory>\n";
    foreach ($memories as $memory) {
      $type = htmlspecialchars($memory['type'], ENT_QUOTES, 'UTF-8');
      $content = htmlspecialchars($memory['content'], ENT_QUOTES, 'UTF-8');
      $score = isset($memory['score']) ? ' relevance="' . $memory['score'] . '"' : '';
      $output .= "  <memory type=\"{$type}\"{$score}>{$content}</memory>\n";
    }
    $output .= "</agent_memory>";

    return $output;
  }

  /**
   * Recall cronologico desde BD (GAP-07).
   *
   * @param string $agentId
   *   ID del agente.
   * @param string $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Maximo de memorias.
   *
   * @return array
   *   Array de items de memoria.
   */
  protected function chronologicalRecall(string $agentId, string $tenantId, int $limit): array {
    $memories = [];

    try {
      if (!$this->entityTypeManager->hasDefinition('shared_memory')) {
        return [];
      }

      $storage = $this->entityTypeManager->getStorage('shared_memory');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('agent_id', $agentId)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          $memories[] = [
            'id' => (string) $entity->id(),
            'type' => $entity->get('memory_type')->value ?? 'fact',
            'content' => $entity->get('content')->value ?? '',
            'metadata' => json_decode($entity->get('metadata')->value ?? '{}', TRUE),
            'created' => (int) ($entity->get('created')->value ?? 0),
            'source' => 'chronological',
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('GAP-07: Recall cronologico fallido: @msg', ['@msg' => $e->getMessage()]);
    }

    return $memories;
  }

  /**
   * Recall semantico desde Qdrant (GAP-07).
   *
   * Genera embedding de la query, busca en coleccion agent_memory
   * con filtro por agent_id y tenant_id.
   *
   * @param string $query
   *   Texto de busqueda.
   * @param string $agentId
   *   ID del agente.
   * @param string $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Maximo de resultados.
   *
   * @return array
   *   Array de items de memoria con score de relevancia.
   */
  protected function semanticRecall(string $query, string $agentId, string $tenantId, int $limit = 5): array {
    try {
      $embedding = $this->generateEmbedding($query);

      if (empty($embedding)) {
        return [];
      }

      // Filtro Qdrant: solo memorias de este agente + tenant.
      $filter = [
        'must' => [
          ['key' => 'agent_id', 'match' => ['value' => $agentId]],
          ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
        ],
      ];

      $results = $this->qdrantClient->vectorSearch(
        $embedding,
        $filter,
        $limit,
        self::SEMANTIC_THRESHOLD,
        self::QDRANT_COLLECTION
      );

      $memories = [];
      foreach ($results as $result) {
        $payload = $result['payload'] ?? [];
        $memories[] = [
          'id' => (string) ($payload['memory_id'] ?? $result['id']),
          'type' => $payload['memory_type'] ?? 'fact',
          'content' => $payload['content'] ?? '',
          'metadata' => json_decode($payload['metadata_json'] ?? '{}', TRUE),
          'created' => (int) ($payload['created_at'] ?? 0),
          'score' => round($result['score'], 3),
          'source' => 'semantic',
        ];
      }

      $this->logger->debug('GAP-07: Semantic recall — @count resultados para agente @agent.', [
        '@count' => count($memories),
        '@agent' => $agentId,
      ]);

      return $memories;
    }
    catch (\Exception $e) {
      $this->logger->warning('GAP-07: Semantic recall fallido (non-blocking): @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Indexa una memoria en Qdrant (GAP-07).
   *
   * Genera embedding del contenido y hace upsert en la coleccion
   * agent_memory con payload estructurado.
   *
   * @param string $agentId
   *   ID del agente.
   * @param string $tenantId
   *   ID del tenant.
   * @param string $type
   *   Tipo de memoria.
   * @param string $content
   *   Contenido textual.
   * @param array $metadata
   *   Metadatos adicionales.
   * @param mixed $memoryId
   *   ID de la memoria en BD.
   */
  protected function indexInQdrant(string $agentId, string $tenantId, string $type, string $content, array $metadata, mixed $memoryId): void {
    $embedding = $this->generateEmbedding($content);

    if (empty($embedding)) {
      $this->logger->warning('GAP-07: No se pudo generar embedding para indexar memoria.');
      return;
    }

    $pointId = "memory_{$agentId}_{$memoryId}";

    $point = [
      'id' => $pointId,
      'vector' => $embedding,
      'payload' => [
        'agent_id' => $agentId,
        'tenant_id' => $tenantId,
        'memory_type' => $type,
        'content' => $content,
        'metadata_json' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
        'memory_id' => (string) $memoryId,
        'created_at' => time(),
      ],
    ];

    $success = $this->qdrantClient->upsertPoints([$point], self::QDRANT_COLLECTION);

    if ($success) {
      $this->logger->debug('GAP-07: Memoria indexada en Qdrant — agent=@agent, point=@point.', [
        '@agent' => $agentId,
        '@point' => $pointId,
      ]);
    }
    else {
      $this->logger->warning('GAP-07: Fallo al indexar memoria en Qdrant — agent=@agent.', [
        '@agent' => $agentId,
      ]);
    }
  }

  /**
   * Genera embedding de un texto (GAP-07).
   *
   * Sigue el mismo patron que KbIndexerService::generateEmbedding().
   *
   * @param string $text
   *   Texto a embebir.
   *
   * @return array
   *   Vector de embedding, o array vacio si falla.
   */
  protected function generateEmbedding(string $text): array {
    if (!$this->aiProvider || !$this->configFactory) {
      return [];
    }

    try {
      $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');

      if (!$defaults) {
        $this->logger->warning('GAP-07: No hay proveedor de embeddings configurado.');
        return [];
      }

      $config = $this->configFactory->get('jaraba_rag.settings');
      $model = $config->get('embeddings.model') ?? $defaults['model_id'] ?? 'text-embedding-3-small';

      /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
      $provider = $this->aiProvider->createInstance($defaults['provider_id']);
      $result = $provider->embeddings($text, $model);

      return $result->getNormalized();
    }
    catch (\Exception $e) {
      $this->logger->error('GAP-07: Error generando embedding: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Merge inteligente de memorias cronologicas + semanticas (GAP-07).
   *
   * Deduplicacion por ID. Las memorias semanticas conservan su score
   * de relevancia. Orden: semanticas (por score DESC), luego cronologicas
   * (por created DESC), deduplicadas.
   *
   * @param array $chronological
   *   Memorias del recall cronologico.
   * @param array $semantic
   *   Memorias del recall semantico.
   * @param int $limit
   *   Maximo total.
   *
   * @return array
   *   Array mergeado y deduplicado.
   */
  protected function mergeMemories(array $chronological, array $semantic, int $limit): array {
    $seen = [];
    $merged = [];

    // Primero las semanticas (mas relevantes a la query actual).
    foreach ($semantic as $memory) {
      $id = $memory['id'];
      if (!isset($seen[$id])) {
        $seen[$id] = TRUE;
        $merged[] = $memory;
      }
    }

    // Luego las cronologicas (contexto temporal reciente).
    foreach ($chronological as $memory) {
      $id = $memory['id'];
      if (!isset($seen[$id])) {
        $seen[$id] = TRUE;
        $merged[] = $memory;
      }
    }

    return array_slice($merged, 0, $limit);
  }

}
