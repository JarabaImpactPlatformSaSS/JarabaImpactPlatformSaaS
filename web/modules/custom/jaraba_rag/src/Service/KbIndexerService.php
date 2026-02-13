<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;

/**
 * Servicio de indexación de entidades en la Knowledge Base (Qdrant).
 *
 * Este servicio:
 * - Extrae contenido de entidades Drupal
 * - Divide en chunks optimizados
 * - Genera embeddings via OpenAI
 * - Upsert/Delete en Qdrant
 *
 * @see docs/tecnicos/20260111-Guia_Tecnica_KB_RAG_Qdrant.md (Sección 2.3)
 */
class KbIndexerService
{

    /**
     * Constructs a KbIndexerService object.
     */
    public function __construct(
        protected AiProviderPluginManager $aiProvider,
        protected QdrantDirectClient $qdrantClient,
        protected RagTenantFilterService $tenantContext,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerChannelFactoryInterface $loggerFactory,
        protected ConfigFactoryInterface $configFactory,
        protected ?CacheBackendInterface $embeddingCache = NULL,
    ) {
    }

    /**
     * Indexa una entidad en la Knowledge Base.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   La entidad a indexar.
     */
    public function indexEntity(EntityInterface $entity): void
    {
        $entityType = $entity->getEntityTypeId();
        $entityId = $entity->id();
        $config = $this->configFactory->get('jaraba_rag.settings');

        $this->log("Indexando entidad", [
            'type' => $entityType,
            'id' => $entityId,
        ]);

        try {
            // 1. Extraer contenido textual
            $content = $this->extractContent($entity);

            if (empty($content['text'])) {
                $this->log("Entidad sin contenido indexable", [
                    'type' => $entityType,
                    'id' => $entityId,
                ], 'warning');
                return;
            }

            // 2. Dividir en chunks
            // IMPORTANTE: Usar fallbacks robustos porque los config overrides pueden no cargarse
            $chunkSize = (int) ($config->get('embeddings.chunk_size') ?: 0);
            $chunkOverlap = (int) ($config->get('embeddings.chunk_overlap') ?: 0);

            // Fallback a valores por defecto si la configuración no está disponible
            if ($chunkSize <= 0) {
                $chunkSize = 500; // Default: 500 tokens
            }
            if ($chunkOverlap <= 0) {
                $chunkOverlap = 100; // Default: 100 tokens
            }

            $chunks = $this->chunkContent($content, $chunkSize, $chunkOverlap);

            // 3. Obtener contexto del tenant
            $tenantContext = $this->getTenantContextForEntity($entity);

            // 4. Para cada chunk, generar embedding y upsert
            foreach ($chunks as $index => $chunk) {
                $pointId = "{$entityType}_{$entityId}_chunk_{$index}";

                // Generar embedding
                $embedding = $this->generateEmbedding($chunk['text']);

                if (empty($embedding)) {
                    $this->log("Error generando embedding", [
                        'point_id' => $pointId,
                    ], 'error');
                    continue;
                }

                // Construir payload
                $payload = $this->buildPayload($entity, $chunk, $tenantContext, $index);

                // Upsert en Qdrant
                $this->upsertPoint($pointId, $embedding, $payload);
            }

            $this->log("Entidad indexada exitosamente", [
                'type' => $entityType,
                'id' => $entityId,
                'chunks' => count($chunks),
            ]);

        } catch (\Exception $e) {
            $this->log("Error indexando entidad: " . $e->getMessage(), [
                'type' => $entityType,
                'id' => $entityId,
            ], 'error');
            throw $e;
        }
    }

    /**
     * Elimina una entidad de la Knowledge Base.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   La entidad a eliminar.
     */
    public function deleteEntity(EntityInterface $entity): void
    {
        $entityType = $entity->getEntityTypeId();
        $entityId = $entity->id();

        $this->log("Eliminando entidad de KB", [
            'type' => $entityType,
            'id' => $entityId,
        ]);

        try {
            // Eliminar todos los chunks de esta entidad
            $this->deletePointsByFilter([
                'must' => [
                    ['key' => 'drupal_entity_type', 'match' => ['value' => $entityType]],
                    ['key' => 'drupal_entity_id', 'match' => ['value' => (int) $entityId]],
                ],
            ]);

            $this->log("Entidad eliminada de KB", [
                'type' => $entityType,
                'id' => $entityId,
            ]);

        } catch (\Exception $e) {
            $this->log("Error eliminando entidad de KB: " . $e->getMessage(), [
                'type' => $entityType,
                'id' => $entityId,
            ], 'error');
        }
    }

    /**
     * Reindexar todas las entidades de un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     */
    public function reindexTenant(int $tenantId): void
    {
        $this->log("Reindexando tenant", ['tenant_id' => $tenantId]);

        try {
            // 1. Eliminar todos los puntos del tenant en Qdrant.
            $this->deletePointsByFilter([
                'must' => [
                    ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
                ],
            ]);

            // 2. Obtener el tenant y su grupo para cargar entidades asociadas.
            $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
            if (!$tenant) {
                $this->log("Tenant no encontrado", ['tenant_id' => $tenantId], 'error');
                return;
            }

            $group = $tenant->getGroup();
            if (!$group) {
                $this->log("Tenant sin grupo asociado", ['tenant_id' => $tenantId], 'warning');
                return;
            }

            // 3. Obtener entidades vinculadas al grupo vía group_relationship.
            $config = $this->configFactory->get('jaraba_rag.settings');
            $indexableConfig = $config->get('indexable_entities') ?? [];
            $indexableTypes = array_keys($indexableConfig);

            foreach ($indexableTypes as $entityType) {
                $relationships = $this->entityTypeManager
                    ->getStorage('group_relationship')
                    ->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('gid', $group->id())
                    ->condition('plugin_id', "group_{$entityType}:%", 'LIKE')
                    ->execute();

                if (!empty($relationships)) {
                    $relationshipEntities = $this->entityTypeManager
                        ->getStorage('group_relationship')
                        ->loadMultiple($relationships);

                    foreach ($relationshipEntities as $relationship) {
                        $entity = $relationship->getEntity();
                        if ($entity) {
                            $this->indexEntity($entity);
                        }
                    }
                }
            }

            $this->log("Reindexación completada", ['tenant_id' => $tenantId]);

        } catch (\Exception $e) {
            $this->log("Error reindexando tenant: " . $e->getMessage(), [
                'tenant_id' => $tenantId,
            ], 'error');
        }
    }

    /**
     * Extrae contenido textual de una entidad.
     */
    protected function extractContent(EntityInterface $entity): array
    {
        $entityType = $entity->getEntityTypeId();
        $config = $this->configFactory->get('jaraba_rag.settings');
        $indexableConfig = $config->get('indexable_entities');

        $content = [
            'text' => '',
            'title' => '',
            'answer_capsule' => '',
        ];

        // Obtener título - método universal
        if ($entity->hasField('title')) {
            $content['title'] = $entity->get('title')->value ?? '';
        } elseif (method_exists($entity, 'label')) {
            $content['title'] = $entity->label() ?? '';
        }

        // Campos por defecto según tipo de entidad
        $defaultFields = [
            'commerce_product' => ['body', 'field_body', 'field_description', 'field_short_description'],
            'node' => ['body'],
            'taxonomy_term' => ['description'],
        ];

        // Obtener configuración de campos para este tipo
        $fieldsConfig = [];
        if ($entityType === 'node') {
            $bundle = $entity->bundle();
            $fieldsConfig = $indexableConfig['node'][$bundle]['fields'] ?? $defaultFields['node'];
        } elseif (isset($defaultFields[$entityType])) {
            $fieldsConfig = $indexableConfig[$entityType]['fields'] ?? $defaultFields[$entityType];
        } else {
            $fieldsConfig = $indexableConfig[$entityType]['fields'] ?? ['title', 'body'];
        }

        // Extraer campos configurados
        $textParts = [$content['title']];

        foreach ($fieldsConfig as $fieldName) {
            if ($entity->hasField($fieldName) && !$entity->get($fieldName)->isEmpty()) {
                $fieldValue = $entity->get($fieldName)->value ?? '';

                // Limpiar HTML
                $fieldValue = strip_tags($fieldValue);
                $fieldValue = html_entity_decode($fieldValue);
                $fieldValue = trim($fieldValue);

                if (!empty($fieldValue)) {
                    $textParts[] = $fieldValue;

                    // Detectar Answer Capsule
                    if ($fieldName === 'field_ai_summary') {
                        $content['answer_capsule'] = $fieldValue;
                    }
                }
            }
        }

        $content['text'] = implode("\n\n", array_filter($textParts));

        // Log para debugging
        $this->log("Contenido extraído", [
            'type' => $entityType,
            'id' => $entity->id(),
            'title' => $content['title'],
            'text_length' => strlen($content['text']),
            'fields_checked' => implode(',', $fieldsConfig),
        ]);

        return $content;
    }

    /**
     * AI-06: Recursive character text splitter con boundaries semánticos.
     *
     * Orden de prioridad para splits:
     * 1. Doble salto de línea (párrafos)
     * 2. Headings markdown (# ## ###)
     * 3. Salto de línea simple
     * 4. Punto seguido de espacio (fin de oración)
     * 5. Coma
     * 6. Carácter fijo (fallback)
     */
    protected function chunkContent(array $content, int $chunkSize, int $overlap): array
    {
        $chunks = [];
        $text = trim($content['text'] ?? '');

        $this->log("Iniciando chunking", [
            'text_length' => strlen($text),
            'chunk_size' => $chunkSize,
            'overlap' => $overlap,
        ]);

        if (empty($text)) {
            return [];
        }

        if (!empty($content['answer_capsule'])) {
            $chunks[] = [
                'text' => $content['answer_capsule'],
                'type' => 'answer_capsule',
                'priority' => 1.5,
            ];
        }

        // AI-14: Improved token estimation for Spanish text.
        // Spanish averages ~3.5 chars/token (more morphology than English ~4).
        // Use word-based estimate: ~1.3 tokens/word avg in Spanish.
        $avgCharsPerToken = 3.5;
        $chunkChars = (int) round($chunkSize * $avgCharsPerToken);
        $overlapChars = (int) round($overlap * $avgCharsPerToken);

        if (strlen($text) <= $chunkChars) {
            $chunks[] = [
                'text' => $text,
                'type' => 'description',
                'priority' => 1.0,
                'position' => 0,
            ];
            return $chunks;
        }

        // Separadores ordenados por prioridad semántica.
        $separators = ["\n\n", "\n# ", "\n## ", "\n### ", "\n", ". ", ", "];

        $rawChunks = $this->recursiveSplit($text, $separators, $chunkChars);

        // Ensamblar chunks con overlap.
        $position = 0;
        foreach ($rawChunks as $i => $chunkText) {
            $chunkText = trim($chunkText);
            if (empty($chunkText)) {
                continue;
            }

            // Añadir overlap del chunk anterior.
            if ($i > 0 && $overlapChars > 0 && isset($rawChunks[$i - 1])) {
                $overlapText = mb_substr($rawChunks[$i - 1], -$overlapChars);
                $chunkText = trim($overlapText) . ' ' . $chunkText;
            }

            $chunks[] = [
                'text' => $chunkText,
                'type' => 'description',
                'priority' => 0.8,
                'position' => $position,
            ];

            $position += strlen($rawChunks[$i]);

            if (count($chunks) > 50) {
                break;
            }
        }

        return $chunks;
    }

    /**
     * Recursively splits text using semantic separators.
     */
    protected function recursiveSplit(string $text, array $separators, int $maxChars): array
    {
        if (strlen($text) <= $maxChars) {
            return [$text];
        }

        foreach ($separators as $idx => $separator) {
            $parts = explode($separator, $text);
            if (count($parts) <= 1) {
                continue;
            }

            $chunks = [];
            $current = '';
            foreach ($parts as $part) {
                $candidate = $current === '' ? $part : $current . $separator . $part;

                if (strlen($candidate) <= $maxChars) {
                    $current = $candidate;
                } else {
                    if ($current !== '') {
                        $chunks[] = $current;
                    }
                    if (strlen($part) > $maxChars) {
                        $remaining = array_slice($separators, $idx + 1);
                        if (!empty($remaining)) {
                            $chunks = array_merge($chunks, $this->recursiveSplit($part, $remaining, $maxChars));
                            $current = '';
                        } else {
                            while (strlen($part) > $maxChars) {
                                $chunks[] = substr($part, 0, $maxChars);
                                $part = substr($part, $maxChars);
                            }
                            $current = $part;
                        }
                    } else {
                        $current = $part;
                    }
                }
            }
            if ($current !== '') {
                $chunks[] = $current;
            }
            return $chunks;
        }

        // Fallback: hard cut.
        $chunks = [];
        while (strlen($text) > $maxChars) {
            $chunks[] = substr($text, 0, $maxChars);
            $text = substr($text, $maxChars);
        }
        if (strlen($text) > 0) {
            $chunks[] = $text;
        }
        return $chunks;
    }

    /**
     * Genera embedding usando OpenAI con caché por hash de contenido.
     *
     * AI-05: Cachea embeddings por SHA-256 del texto para evitar llamadas
     * redundantes a la API cuando el contenido no ha cambiado.
     * Ahorro estimado: ~30-40% de llamadas a la API de embeddings.
     */
    protected function generateEmbedding(string $text): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $model = $config->get('embeddings.model') ?? 'text-embedding-3-small';

        // AI-05: Verificar caché por hash de contenido.
        $contentHash = hash('sha256', $text . '|' . $model);
        $cacheKey = 'embedding:' . $contentHash;

        if ($this->embeddingCache) {
            $cached = $this->embeddingCache->get($cacheKey);
            if ($cached && !empty($cached->data)) {
                $this->log('Embedding obtenido de caché', [
                    'hash' => substr($contentHash, 0, 12),
                ]);
                return $cached->data;
            }
        }

        try {
            // Obtener el proveedor por defecto para embeddings.
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');

            if (!$defaults) {
                $this->log('No hay proveedor de embeddings configurado', [], 'error');
                return [];
            }

            /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            // Generar embedding.
            $result = $provider->embeddings($text, $defaults['model_id'] ?? $model);
            $embedding = $result->getNormalized();

            // AI-05: Guardar en caché (TTL 7 días - los embeddings no cambian
            // para el mismo texto + modelo).
            if ($this->embeddingCache && !empty($embedding)) {
                $this->embeddingCache->set($cacheKey, $embedding, time() + 604800);
            }

            return $embedding;
        } catch (\Exception $e) {
            $this->log('Error generando embedding', [
                'error' => $e->getMessage(),
            ], 'error');
            return [];
        }
    }

    /**
     * Upsert un punto en Qdrant usando el cliente directo.
     */
    protected function upsertPoint(string $pointId, array $embedding, array $payload): void
    {
        try {
            // Usar el cliente Qdrant directo - API simple y limpia
            $point = [
                'id' => $pointId,
                'vector' => $embedding,
                'payload' => $payload,
            ];

            $success = $this->qdrantClient->upsertPoints([$point]);

            if ($success) {
                $this->log('Punto insertado en Qdrant', [
                    'point_id' => $pointId,
                ]);
            } else {
                $this->log('Error insertando punto en Qdrant', [
                    'point_id' => $pointId,
                ], 'error');
            }
        } catch (\Exception $e) {
            $this->log('Error insertando punto en Qdrant', [
                'point_id' => $pointId,
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * Elimina puntos de Qdrant por filtro usando el cliente directo.
     */
    protected function deletePointsByFilter(array $filter): void
    {
        try {
            // Usar el cliente Qdrant directo - API simple
            $success = $this->qdrantClient->deleteByFilter($filter);

            if ($success) {
                $this->log('Puntos eliminados de Qdrant por filtro');
            }
        } catch (\Exception $e) {
            $this->log('Error eliminando puntos de Qdrant', [
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * Construye el payload para un punto de Qdrant.
     */
    protected function buildPayload(EntityInterface $entity, array $chunk, array $tenantContext, int $chunkIndex): array
    {
        $entityType = $entity->getEntityTypeId();
        $entityId = $entity->id();

        // URL de la entidad
        $url = '';
        try {
            $url = $entity->toUrl()->toString();
        } catch (\Exception $e) {
            // Algunas entidades no tienen URL
        }

        return [
            // Identificación Drupal
            'drupal_entity_type' => $entityType,
            'drupal_entity_id' => (int) $entityId,
            'chunk_index' => $chunkIndex,
            'chunk_type' => $chunk['type'] ?? 'description',

            // Multi-tenancy
            'tenant_id' => $tenantContext['tenant_id'] ?? NULL,
            'vertical' => $tenantContext['vertical'] ?? 'platform',
            'plan_level' => $tenantContext['plan_level'] ?? 'starter',
            'shared_type' => $tenantContext['shared_type'] ?? 'tenant',

            // Contenido
            'text' => $chunk['text'],
            'source_url' => $url,
            'source_title' => $entity->label() ?? '',
            'priority' => $chunk['priority'] ?? 1.0,

            // Metadatos
            'indexed_at' => date('c'),
            'content_hash' => hash('sha256', $chunk['text']),
        ];
    }

    /**
     * Obtiene contexto del tenant para una entidad.
     */
    protected function getTenantContextForEntity(EntityInterface $entity): array
    {
        // Intentar obtener tenant_id de la entidad
        // Esto depende de cómo estén configurados los grupos

        // Por ahora, intentamos obtener del contexto actual
        try {
            return $this->tenantContext->getSearchFilters();
        } catch (\Exception $e) {
            // Si no hay contexto, es contenido de plataforma
            return [
                'tenant_id' => NULL,
                'vertical' => 'platform',
                'plan_level' => 'starter',
                'shared_type' => 'platform',
            ];
        }
    }

    /**
     * Helper para logging.
     */
    protected function log(string $message, array $context = [], string $level = 'info'): void
    {
        $this->loggerFactory->get('jaraba_rag')->{$level}($message . ' | @context', [
            '@context' => json_encode($context),
        ]);
    }

}
