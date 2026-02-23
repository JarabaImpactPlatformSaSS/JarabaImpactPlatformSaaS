<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drupal\ai\AiProviderPluginManager;
use Drupal\jaraba_tenant_knowledge\Entity\TenantFaq;
use Drupal\jaraba_tenant_knowledge\Entity\TenantPolicy;
use Psr\Log\LoggerInterface;


/**
 * SERVICIO INDEXADOR DE CONOCIMIENTO EN QDRANT
 *
 * PROPÓSITO:
 * Indexa FAQs, políticas y otros conocimientos del tenant en Qdrant
 * para permitir retrieval semántico durante las conversaciones.
 *
 * ARQUITECTURA:
 * - Genera embeddings via AI module (OpenAI text-embedding-3-small)
 * - Almacena en colección 'jaraba_knowledge' de Qdrant
 * - El payload incluye tipo, tenant_id, categoría para filtrado
 *
 * COLECCIÓN:
 * jaraba_knowledge (separada de jaraba_skills)
 * - type: 'faq' | 'policy' | 'document' | 'business_info'
 * - tenant_id: int
 * - category: string
 * - content: string (texto original para display)
 */
class KnowledgeIndexerService
{

    /**
     * Nombre de la colección en Qdrant.
     */
    protected const COLLECTION_NAME = 'jaraba_knowledge';

    /**
     * Modelo de embedding.
     */
    protected const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * Dimensiones del vector.
     */
    protected const VECTOR_DIMENSIONS = 1536;

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected QdrantDirectClient $qdrantClient,
        protected AiProviderPluginManager $aiProvider,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Indexa una FAQ en Qdrant.
     *
     * FLUJO:
     * 1. Genera texto de embedding
     * 2. Llama API de embeddings
     * 3. Upsert en Qdrant con payload
     * 4. Actualiza qdrant_point_id en la entidad
     *
     * @param TenantFaq $faq
     *   La FAQ a indexar.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexFaq(TenantFaq $faq): bool
    {
        // Solo indexar FAQs publicadas.
        if (!$faq->isPublished()) {
            $this->logger->info('FAQ @id no indexada (no publicada).', ['@id' => $faq->id()]);
            return FALSE;
        }

        try {
            // Asegurar que la colección existe.
            $this->ensureCollection();

            // Generar embedding.
            $text = $faq->getEmbeddingText();
            $vector = $this->generateEmbedding($text);

            if (empty($vector)) {
                $this->logger->error('Embedding vacío para FAQ @id.', ['@id' => $faq->id()]);
                return FALSE;
            }

            // Generar point ID.
            $pointId = $this->qdrantClient->generatePointId('faq_' . $faq->id());

            // Preparar payload.
            $payload = [
                'type' => 'faq',
                'entity_type' => 'tenant_faq',
                'entity_id' => (int) $faq->id(),
                'tenant_id' => $faq->getTenantId(),
                'category' => $faq->getCategory(),
                'question' => $faq->getQuestion(),
                'answer' => substr($faq->getAnswer(), 0, 500), // Truncar para payload
                'priority' => (int) ($faq->get('priority')->value ?? 0),
            ];

            // Upsert en Qdrant.
            $point = [
                'id' => $pointId,
                'vector' => $vector,
                'payload' => $payload,
            ];

            $this->qdrantClient->upsertPoints([$point], self::COLLECTION_NAME);

            // Actualizar entidad con point ID.
            $faq->set('qdrant_point_id', $pointId);
            $faq->save();

            $this->logger->info('FAQ @id indexada correctamente en Qdrant.', [
                '@id' => $faq->id(),
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error indexando FAQ @id: @error', [
                '@id' => $faq->id(),
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Elimina una FAQ de Qdrant.
     *
     * @param TenantFaq $faq
     *   La FAQ a eliminar del índice.
     */
    public function deleteFaq(TenantFaq $faq): void
    {
        $pointId = $faq->get('qdrant_point_id')->value;

        if (!$pointId) {
            return;
        }

        try {
            $this->qdrantClient->deletePoints([$pointId], self::COLLECTION_NAME);
            $this->logger->info('FAQ @id eliminada de Qdrant.', ['@id' => $faq->id()]);
        } catch (\Exception $e) {
            $this->logger->warning('Error eliminando FAQ @id de Qdrant: @error', [
                '@id' => $faq->id(),
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Busca conocimiento similar mediante query semántica.
     *
     * @param string $query
     *   Texto de búsqueda.
     * @param int $tenantId
     *   ID del tenant para filtrar.
     * @param array $options
     *   Opciones: 'type', 'category', 'limit', 'threshold'.
     *
     * @return array
     *   Resultados ordenados por score.
     */
    public function searchKnowledge(string $query, int $tenantId, array $options = []): array
    {
        $limit = $options['limit'] ?? 5;
        $threshold = $options['threshold'] ?? 0.7;
        $type = $options['type'] ?? NULL;
        $category = $options['category'] ?? NULL;

        try {
            // Generar embedding del query.
            $vector = $this->generateEmbedding($query);

            if (empty($vector)) {
                return [];
            }

            // Construir filtro.
            $filter = $this->buildFilter($tenantId, $type, $category);

            // Buscar en Qdrant.
            $results = $this->qdrantClient->vectorSearch(
                $vector,
                $filter,
                $limit,
                $threshold,
                self::COLLECTION_NAME
            );

            return $results;
        } catch (\Exception $e) {
            $this->logger->error('Error buscando conocimiento: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Reindexar todas las FAQs de un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return int
     *   Número de FAQs indexadas.
     */
    public function reindexAllFaqs(int $tenantId): int
    {
        $storage = $this->entityTypeManager->getStorage('tenant_faq');

        $faqIds = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('tenant_id', $tenantId)
            ->condition('is_published', TRUE)
            ->execute();

        $count = 0;
        foreach ($storage->loadMultiple($faqIds) as $faq) {
            if ($this->indexFaq($faq)) {
                $count++;
            }
        }

        $this->logger->info('Reindexadas @count FAQs para tenant @tenant.', [
            '@count' => $count,
            '@tenant' => $tenantId,
        ]);

        return $count;
    }

    /**
     * Asegura que la colección Qdrant existe.
     */
    protected function ensureCollection(): void
    {
        $this->qdrantClient->ensureCollection(
            self::COLLECTION_NAME,
            self::VECTOR_DIMENSIONS
        );
    }

    /**
     * Genera embedding para un texto.
     *
     * @param string $text
     *   Texto a convertir en vector.
     *
     * @return array
     *   Vector de embedding.
     */
    protected function generateEmbedding(string $text): array
    {
        try {
            // Obtener provider de embeddings.
            $provider = $this->aiProvider->createInstance('openai');

            // Generar embedding usando el patrón correcto del Drupal AI module.
            $result = $provider->embeddings($text, self::EMBEDDING_MODEL);

            if ($result && method_exists($result, 'getNormalized')) {
                $vector = $result->getNormalized();
                if (!empty($vector) && is_array($vector)) {
                    return $vector;
                }
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error generando embedding: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Construye filtro Qdrant.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param string|null $type
     *   Tipo de conocimiento.
     * @param string|null $category
     *   Categoría.
     *
     * @return array
     *   Filtro Qdrant.
     */
    protected function buildFilter(int $tenantId, ?string $type, ?string $category): array
    {
        $must = [
            [
                'key' => 'tenant_id',
                'match' => ['value' => $tenantId],
            ],
        ];

        if ($type) {
            $must[] = [
                'key' => 'type',
                'match' => ['value' => $type],
            ];
        }

        if ($category) {
            $must[] = [
                'key' => 'category',
                'match' => ['value' => $category],
            ];
        }

        return ['must' => $must];
    }

    // ===========================================================================
    // MÉTODOS PARA POLICIES
    // ===========================================================================

    /**
     * Indexa una política en Qdrant.
     *
     * @param \Drupal\jaraba_tenant_knowledge\Entity\TenantPolicy $policy
     *   La política a indexar.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexPolicy(TenantPolicy $policy): bool
    {
        // Solo indexar políticas publicadas.
        if (!$policy->isPublished()) {
            $this->logger->info('Política @id no indexada (no publicada).', ['@id' => $policy->id()]);
            return FALSE;
        }

        try {
            $this->ensureCollection();

            $text = $policy->getEmbeddingText();
            $vector = $this->generateEmbedding($text);

            if (empty($vector)) {
                $this->logger->error('Embedding vacío para política @id.', ['@id' => $policy->id()]);
                return FALSE;
            }

            $pointId = $this->qdrantClient->generatePointId('policy_' . $policy->id());

            $payload = [
                'type' => 'policy',
                'entity_type' => 'tenant_policy',
                'entity_id' => (int) $policy->id(),
                'tenant_id' => $policy->getTenantId(),
                'policy_type' => $policy->getPolicyType(),
                'title' => $policy->getTitle(),
                'content_preview' => substr($policy->getContent(), 0, 500),
                'version' => $policy->getVersionNumber(),
            ];

            $point = [
                'id' => $pointId,
                'vector' => $vector,
                'payload' => $payload,
            ];

            $this->qdrantClient->upsertPoints([$point], self::COLLECTION_NAME);

            $policy->set('qdrant_point_id', $pointId);
            $policy->save();

            $this->logger->info('Política @id indexada en Qdrant.', ['@id' => $policy->id()]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error indexando política @id: @error', [
                '@id' => $policy->id(),
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Elimina una política de Qdrant.
     *
     * @param \Drupal\jaraba_tenant_knowledge\Entity\TenantPolicy $policy
     *   La política a eliminar.
     */
    public function deletePolicy(TenantPolicy $policy): void
    {
        $pointId = $policy->get('qdrant_point_id')->value;

        if (!$pointId) {
            return;
        }

        try {
            $this->qdrantClient->deletePoints([$pointId], self::COLLECTION_NAME);
            $this->logger->info('Política @id eliminada de Qdrant.', ['@id' => $policy->id()]);
        } catch (\Exception $e) {
            $this->logger->warning('Error eliminando política @id de Qdrant: @error', [
                '@id' => $policy->id(),
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reindexar todas las políticas de un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return int
     *   Número de políticas indexadas.
     */
    public function reindexAllPolicies(int $tenantId): int
    {
        $storage = $this->entityTypeManager->getStorage('tenant_policy');

        $policyIds = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('tenant_id', $tenantId)
            ->condition('is_published', TRUE)
            ->execute();

        $count = 0;
        foreach ($storage->loadMultiple($policyIds) as $policy) {
            if ($this->indexPolicy($policy)) {
                $count++;
            }
        }

        $this->logger->info('Reindexadas @count políticas para tenant @tenant.', [
            '@count' => $count,
            '@tenant' => $tenantId,
        ]);

        return $count;
    }

}

