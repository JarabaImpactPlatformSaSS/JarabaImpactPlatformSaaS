<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drupal\jaraba_skills\Entity\AiSkill;
use Drupal\jaraba_skills\Entity\AiSkillEmbedding;
use Drupal\ai\AiProviderPluginManager;
use Psr\Log\LoggerInterface;

/**
 * SERVICIO DE EMBEDDINGS PARA SKILLS IA
 *
 * PROPÓSITO:
 * Orquesta la generación de embeddings vectoriales para skills y su
 * indexación en Qdrant para permitir búsqueda semántica.
 *
 * ESTRUCTURA:
 * Este servicio actúa como puente entre el sistema de Skills y el
 * módulo jaraba_rag. Usa el módulo AI de Drupal para abstracción
 * de proveedores de embeddings.
 *
 * LÓGICA:
 * 1. Detecta cambios en skill.content vía hash MD5
 * 2. Genera embedding vía OpenAI text-embedding-3-small (1536D)
 * 3. Almacena embedding en entidad AiSkillEmbedding
 * 4. Indexa en Qdrant con metadatos de contexto (vertical, agent_type, tenant)
 * 5. Permite búsqueda semántica de skills por query de usuario
 *
 * DEPENDENCIAS:
 * - @entity_type.manager: Gestión de entidades Drupal
 * - @jaraba_rag.qdrant_client: Cliente Qdrant para vectores
 * - @ai.provider: Módulo AI de Drupal para embeddings
 * - @logger.channel.jaraba_skills: Logging
 *
 * @see AiSkill Entidad de habilidad
 * @see AiSkillEmbedding Entidad de embedding
 * @see QdrantDirectClient Cliente vectorial
 */
class SkillEmbeddingService
{

    /**
     * Nombre de la colección en Qdrant para skills.
     */
    protected const COLLECTION_NAME = 'jaraba_skills';

    /**
     * Dimensiones del vector (OpenAI text-embedding-3-small).
     */
    protected const VECTOR_DIMENSIONS = 1536;

    /**
     * Modelo de embedding a usar.
     */
    protected const EMBEDDING_MODEL = 'text-embedding-3-small';

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Gestor de tipos de entidad.
     * @param \Drupal\jaraba_rag\Client\QdrantDirectClient $qdrantClient
     *   Cliente de Qdrant.
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   Gestor de proveedores AI.
     * @param \Psr\Log\LoggerInterface $logger
     *   Canal de logging.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected QdrantDirectClient $qdrantClient,
        protected AiProviderPluginManager $aiProvider,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Genera y almacena embedding para una skill.
     *
     * FLUJO:
     * 1. Verifica si existe embedding previo
     * 2. Compara hash para detectar cambios
     * 3. Genera embedding vía OpenAI si es necesario
     * 4. Guarda en entidad AiSkillEmbedding
     * 5. Indexa en Qdrant
     *
     * @param \Drupal\jaraba_skills\Entity\AiSkill $skill
     *   La skill a procesar.
     * @param bool $force
     *   Si es TRUE, regenera aunque no haya cambios.
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkillEmbedding|null
     *   El embedding generado/existente, o NULL si falla.
     */
    public function generateAndStore(AiSkill $skill, bool $force = FALSE): ?AiSkillEmbedding
    {
        $skillId = $skill->id();
        $content = $skill->getContent();

        if (empty($content)) {
            $this->logger->warning('Skill @id tiene contenido vacío, no se generará embedding.', [
                '@id' => $skillId,
            ]);
            return NULL;
        }

        // Buscar embedding existente.
        $existingEmbedding = $this->getEmbeddingForSkill($skill);
        $currentHash = md5($content);

        // Si existe y no hay cambios (y no forzamos), retornar existente.
        if ($existingEmbedding && !$force) {
            $storedHash = $existingEmbedding->get('content_hash')->value ?? '';
            if ($storedHash === $currentHash) {
                $this->logger->debug('Skill @id no ha cambiado, usando embedding existente.', [
                    '@id' => $skillId,
                ]);
                return $existingEmbedding;
            }
        }

        // Generar nuevo embedding.
        try {
            $vector = $this->generateEmbedding($content);
            if (empty($vector)) {
                $this->logger->error('No se pudo generar embedding para skill @id.', [
                    '@id' => $skillId,
                ]);
                return NULL;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error generando embedding para skill @id: @error', [
                '@id' => $skillId,
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }

        // Crear o actualizar entidad AiSkillEmbedding.
        $storage = $this->entityTypeManager->getStorage('ai_skill_embedding');

        if ($existingEmbedding) {
            $embeddingEntity = $existingEmbedding;
        } else {
            $embeddingEntity = $storage->create([
                'skill_id' => $skillId,
            ]);
        }

        // Generar ID único para Qdrant.
        $qdrantPointId = $this->qdrantClient->generatePointId("ai_skill_{$skillId}");

        // Actualizar campos.
        $embeddingEntity->setVector($vector);
        $embeddingEntity->set('content_hash', $currentHash);
        $embeddingEntity->set('qdrant_point_id', $qdrantPointId);
        $embeddingEntity->set('embedding_model', self::EMBEDDING_MODEL);
        $embeddingEntity->save();

        // Indexar en Qdrant.
        $this->indexInQdrant($skill, $vector, $qdrantPointId);

        $this->logger->info('Embedding generado para skill @id (@name).', [
            '@id' => $skillId,
            '@name' => $skill->label(),
        ]);

        return $embeddingEntity;
    }

    /**
     * Busca skills por similitud semántica.
     *
     * FLUJO:
     * 1. Genera embedding del query de búsqueda
     * 2. Busca en Qdrant por similitud vectorial
     * 3. Filtra por contexto (vertical, agent_type, tenant_id)
     * 4. Retorna skills ordenadas por score de similitud
     *
     * @param string $query
     *   Texto de búsqueda del usuario.
     * @param array $context
     *   Contexto de filtrado:
     *   - 'vertical': ID de la vertical (opcional)
     *   - 'agent_type': Tipo de agente (opcional)
     *   - 'tenant_id': ID del tenant (opcional)
     * @param int $limit
     *   Número máximo de resultados.
     * @param float $scoreThreshold
     *   Score mínimo de similitud (0-1).
     *
     * @return array
     *   Array asociativo con:
     *   - 'skills': Array de entidades AiSkill
     *   - 'scores': Array de scores por skill_id
     */
    public function searchSimilar(
        string $query,
        array $context = [],
        int $limit = 5,
        float $scoreThreshold = 0.7
    ): array {
        // Generar embedding del query.
        try {
            $queryVector = $this->generateEmbedding($query);
            if (empty($queryVector)) {
                return ['skills' => [], 'scores' => []];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error generando embedding para query: @error', [
                '@error' => $e->getMessage(),
            ]);
            return ['skills' => [], 'scores' => []];
        }

        // Construir filtro Qdrant.
        $filter = $this->buildQdrantFilter($context);

        // Buscar en Qdrant.
        $results = $this->qdrantClient->vectorSearch(
            $queryVector,
            $filter,
            $limit,
            $scoreThreshold,
            self::COLLECTION_NAME
        );

        if (empty($results)) {
            return ['skills' => [], 'scores' => []];
        }

        // Cargar skills desde los resultados.
        $skills = [];
        $scores = [];
        $storage = $this->entityTypeManager->getStorage('ai_skill');

        foreach ($results as $result) {
            $skillId = $result['payload']['skill_id'] ?? NULL;
            if ($skillId) {
                $skill = $storage->load($skillId);
                if ($skill && $skill->isActive()) {
                    $skills[] = $skill;
                    $scores[$skillId] = $result['score'];
                }
            }
        }

        return [
            'skills' => $skills,
            'scores' => $scores,
        ];
    }

    /**
     * Elimina el embedding de una skill de Qdrant.
     *
     * Se llama cuando una skill se elimina o desactiva.
     *
     * @param \Drupal\jaraba_skills\Entity\AiSkill $skill
     *   La skill a eliminar.
     *
     * @return bool
     *   TRUE si se eliminó correctamente.
     */
    public function deleteEmbedding(AiSkill $skill): bool
    {
        $skillId = $skill->id();

        // Eliminar de Qdrant.
        $pointId = "ai_skill_{$skillId}";
        $this->qdrantClient->deletePoints([$pointId], self::COLLECTION_NAME);

        // Eliminar entidad AiSkillEmbedding.
        $existingEmbedding = $this->getEmbeddingForSkill($skill);
        if ($existingEmbedding) {
            $existingEmbedding->delete();
        }

        $this->logger->info('Embedding eliminado para skill @id.', ['@id' => $skillId]);

        return TRUE;
    }

    /**
     * Asegura que la colección de skills existe en Qdrant.
     *
     * @return bool
     *   TRUE si la colección existe o se creó correctamente.
     */
    public function ensureCollection(): bool
    {
        return $this->qdrantClient->ensureCollection(
            self::COLLECTION_NAME,
            self::VECTOR_DIMENSIONS
        );
    }

    /**
     * Reindexar todas las skills en Qdrant.
     *
     * Útil para migraciones o reconstrucción del índice.
     *
     * @return int
     *   Número de skills indexadas.
     */
    public function reindexAll(): int
    {
        $this->ensureCollection();

        $storage = $this->entityTypeManager->getStorage('ai_skill');
        $skills = $storage->loadByProperties(['is_active' => TRUE]);

        $count = 0;
        foreach ($skills as $skill) {
            if ($this->generateAndStore($skill, TRUE)) {
                $count++;
            }
        }

        $this->logger->info('Reindexadas @count skills en Qdrant.', ['@count' => $count]);

        return $count;
    }

    /**
     * Genera embedding usando el módulo AI de Drupal.
     *
     * @param string $text
     *   Texto a convertir en embedding.
     *
     * @return array
     *   Vector de 1536 dimensiones.
     *
     * @throws \Exception
     *   Si falla la generación.
     */
    protected function generateEmbedding(string $text): array
    {
        // Usar el módulo AI de Drupal para abstracción de proveedores.
        // Esto permite failover automático y gestión centralizada de claves.
        try {
            $provider = $this->aiProvider->createInstance('openai');
            $result = $provider->embeddings($text, self::EMBEDDING_MODEL);

            // EmbeddingsOutput::getNormalized() returns the vector array.
            if ($result && method_exists($result, 'getNormalized')) {
                $vector = $result->getNormalized();
                if (!empty($vector) && is_array($vector)) {
                    return $vector;
                }
            }

            return [];
        } catch (\Exception $e) {
            $this->logger->error('Error en AI Provider para embeddings: @error', [
                '@error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Indexa una skill en Qdrant.
     *
     * @param \Drupal\jaraba_skills\Entity\AiSkill $skill
     *   La skill a indexar.
     * @param array $vector
     *   Vector embedding.
     * @param string $pointId
     *   ID del punto en Qdrant.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    protected function indexInQdrant(AiSkill $skill, array $vector, string $pointId): bool
    {
        // Asegurar que la colección existe.
        $this->ensureCollection();

        // Construir payload con metadatos para filtrado.
        $payload = [
            'skill_id' => (int) $skill->id(),
            'skill_name' => $skill->label(),
            'skill_type' => $skill->getSkillType(),
            'vertical_id' => $skill->get('vertical_id')->value ?? NULL,
            'agent_type' => $skill->get('agent_type')->value ?? NULL,
            'tenant_id' => $skill->get('tenant_id')->target_id ?? NULL,
            'is_active' => $skill->isActive(),
            'priority' => (int) ($skill->get('priority')->value ?? 0),
            'updated_at' => date('c'),
        ];

        // Eliminar valores NULL del payload.
        $payload = array_filter($payload, fn($v) => $v !== NULL);

        // Preparar punto para Qdrant.
        $point = [
            'id' => $pointId,
            'vector' => $vector,
            'payload' => $payload,
        ];

        return $this->qdrantClient->upsertPoints([$point], self::COLLECTION_NAME);
    }

    /**
     * Construye filtro Qdrant basado en contexto.
     *
     * @param array $context
     *   Contexto de filtrado.
     *
     * @return array
     *   Filtro en formato Qdrant.
     */
    protected function buildQdrantFilter(array $context): array
    {
        $mustConditions = [];

        // Siempre filtrar por skills activas.
        $mustConditions[] = [
            'key' => 'is_active',
            'match' => ['value' => TRUE],
        ];

        // Filtrar por vertical (incluir core + vertical específica).
        if (!empty($context['vertical'])) {
            $mustConditions[] = [
                'should' => [
                    ['key' => 'skill_type', 'match' => ['value' => 'core']],
                    ['key' => 'vertical_id', 'match' => ['value' => $context['vertical']]],
                ],
            ];
        }

        // Filtrar por tipo de agente.
        if (!empty($context['agent_type'])) {
            $mustConditions[] = [
                'should' => [
                    ['key' => 'skill_type', 'match' => ['value' => 'core']],
                    ['key' => 'skill_type', 'match' => ['value' => 'vertical']],
                    ['key' => 'agent_type', 'match' => ['value' => $context['agent_type']]],
                ],
            ];
        }

        // Filtrar por tenant.
        if (!empty($context['tenant_id'])) {
            $mustConditions[] = [
                'should' => [
                    ['key' => 'skill_type', 'match' => ['value' => 'core']],
                    ['key' => 'skill_type', 'match' => ['value' => 'vertical']],
                    ['key' => 'skill_type', 'match' => ['value' => 'agent']],
                    ['key' => 'tenant_id', 'match' => ['value' => (int) $context['tenant_id']]],
                ],
            ];
        }

        if (empty($mustConditions)) {
            return [];
        }

        return ['must' => $mustConditions];
    }

    /**
     * Obtiene el embedding existente para una skill.
     *
     * @param \Drupal\jaraba_skills\Entity\AiSkill $skill
     *   La skill.
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkillEmbedding|null
     *   El embedding o NULL si no existe.
     */
    protected function getEmbeddingForSkill(AiSkill $skill): ?AiSkillEmbedding
    {
        $storage = $this->entityTypeManager->getStorage('ai_skill_embedding');
        $embeddings = $storage->loadByProperties(['skill_id' => $skill->id()]);

        return $embeddings ? reset($embeddings) : NULL;
    }

}
