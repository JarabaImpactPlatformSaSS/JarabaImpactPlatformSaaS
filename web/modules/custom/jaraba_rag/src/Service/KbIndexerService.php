<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\ai\AiProviderPluginManager;
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
        protected TenantContextService $tenantContext,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerChannelFactoryInterface $loggerFactory,
        protected ConfigFactoryInterface $configFactory,
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
        // @todo Implementar reindexación por tenant
        // Esto debería:
        // 1. Eliminar todos los puntos del tenant
        // 2. Obtener todas las entidades del tenant
        // 3. Indexar cada una
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
     * Divide contenido en chunks optimizados.
     */
    protected function chunkContent(array $content, int $chunkSize, int $overlap): array
    {
        $chunks = [];
        $text = trim($content['text'] ?? '');

        // Log para debugging
        $this->log("Iniciando chunking", [
            'text_length' => strlen($text),
            'chunk_size' => $chunkSize,
            'overlap' => $overlap,
        ]);

        // Si no hay texto, retornar vacío
        if (empty($text)) {
            return [];
        }

        // Si hay Answer Capsule, es el primer chunk con prioridad alta
        if (!empty($content['answer_capsule'])) {
            $chunks[] = [
                'text' => $content['answer_capsule'],
                'type' => 'answer_capsule',
                'priority' => 1.5,
            ];
        }

        // Aproximación: 1 token ≈ 4 caracteres en español
        $chunkChars = $chunkSize * 4;
        $overlapChars = $overlap * 4;

        $textLength = strlen($text);
        $position = 0;
        $chunkIndex = 0;

        // Si el texto es más corto que un chunk, crear un único chunk
        if ($textLength <= $chunkChars) {
            $chunks[] = [
                'text' => $text,
                'type' => 'description',
                'priority' => 1.0,
                'position' => 0,
            ];
            return $chunks;
        }

        // Dividir texto largo en múltiples chunks
        while ($position < $textLength) {
            $chunkText = substr($text, $position, $chunkChars);

            // Intentar cortar en un punto natural (fin de oración)
            if (strlen($chunkText) === $chunkChars) {
                $lastPeriod = strrpos($chunkText, '.');
                if ($lastPeriod !== FALSE && $lastPeriod > $chunkChars * 0.5) {
                    $chunkText = substr($chunkText, 0, $lastPeriod + 1);
                }
            }

            $chunkText = trim($chunkText);

            if (!empty($chunkText)) {
                $chunks[] = [
                    'text' => $chunkText,
                    'type' => 'description',
                    'priority' => 0.8,
                    'position' => $position,
                ];
            }

            // Avanzar posición con overlap
            $advance = strlen($chunkText) - $overlapChars;
            $position += max($advance, 1); // Siempre avanzar al menos 1

            $chunkIndex++;

            // Límite de seguridad
            if ($chunkIndex > 50) {
                break;
            }
        }

        return $chunks;
    }

    /**
     * Genera embedding usando OpenAI.
     */
    protected function generateEmbedding(string $text): array
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        $model = $config->get('embeddings.model') ?? 'text-embedding-3-small';

        try {
            // Obtener el proveedor por defecto para embeddings
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');

            if (!$defaults) {
                $this->log('No hay proveedor de embeddings configurado', [], 'error');
                return [];
            }

            /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            // Generar embedding
            $result = $provider->embeddings($text, $defaults['model_id'] ?? $model);

            return $result->getNormalized();
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
