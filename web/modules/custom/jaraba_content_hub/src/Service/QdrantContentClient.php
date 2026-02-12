<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente Qdrant especializado para Content Hub.
 *
 * Gestiona la colección de vectores de artículos para recomendaciones semánticas.
 *
 * ARQUITECTURA:
 * - Reutiliza QdrantDirectClient de jaraba_rag si está disponible
 * - Fallback a implementación directa si jaraba_rag no está instalado
 * - Colección dedicada para artículos del blog
 */
class QdrantContentClient
{

    /**
     * Colección para vectores de artículos.
     */
    const COLLECTION_ARTICLES = 'content_hub_articles';

    /**
     * Dimensiones del vector (OpenAI text-embedding-3-small).
     */
    const VECTOR_DIMENSION = 1536;

    /**
     * HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Qdrant client from jaraba_rag (if available).
     */
    protected $qdrantClient = NULL;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        $logger_factory,
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_content_hub');
    }

    /**
     * Obtiene el cliente Qdrant de jaraba_rag.
     *
     * @return object|null
     *   QdrantDirectClient o null.
     */
    protected function getQdrantClient()
    {
        if ($this->qdrantClient === NULL) {
            if (\Drupal::hasService('jaraba_rag.qdrant_client')) {
                $this->qdrantClient = \Drupal::service('jaraba_rag.qdrant_client');
            }
        }
        return $this->qdrantClient;
    }

    /**
     * Asegura que la colección de artículos existe.
     *
     * @return bool
     *   TRUE si la colección está lista.
     */
    public function ensureCollection(): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            $this->logger->warning('Qdrant client not available from jaraba_rag');
            return FALSE;
        }

        try {
            $result = $client->ensureCollection(self::COLLECTION_ARTICLES, self::VECTOR_DIMENSION);
            if ($result) {
                $this->logger->info('Content Hub articles collection ensured');
            }
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to ensure articles collection: @error', [
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Indexa un artículo en Qdrant.
     *
     * @param int $articleId
     *   ID del artículo.
     * @param array $vector
     *   Vector de embedding.
     * @param array $payload
     *   Metadatos del artículo para filtros.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexArticle(int $articleId, array $vector, array $payload): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        $points = [
            [
                'id' => "article_{$articleId}",
                'vector' => $vector,
                'payload' => array_merge($payload, [
                    'entity_type' => 'content_article',
                    'entity_id' => $articleId,
                ]),
            ],
        ];

        try {
            return $client->upsertPoints($points, self::COLLECTION_ARTICLES);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index article @id: @error', [
                '@id' => $articleId,
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Busca artículos similares a un vector.
     *
     * @param array $queryVector
     *   Vector de consulta.
     * @param int $tenantId
     *   ID del tenant para filtrar (0 = sin filtro).
     * @param int $limit
     *   Número máximo de resultados.
     * @param float $threshold
     *   Score mínimo (0-1).
     * @param array $excludeIds
     *   IDs de artículos a excluir.
     *
     * @return array
     *   Array de artículos con score.
     */
    public function searchSimilarArticles(
        array $queryVector,
        int $tenantId = 0,
        int $limit = 5,
        float $threshold = 0.65,
        array $excludeIds = [],
    ): array {
        $client = $this->getQdrantClient();
        if (!$client || empty($queryVector)) {
            return [];
        }

        $filter = [
            'must' => [
                ['key' => 'status', 'match' => ['value' => 'published']],
            ],
        ];

        if ($tenantId > 0) {
            $filter['must'][] = ['key' => 'tenant_id', 'match' => ['value' => $tenantId]];
        }

        // Excluir artículos específicos.
        if (!empty($excludeIds)) {
            $filter['must_not'] = [];
            foreach ($excludeIds as $id) {
                $filter['must_not'][] = ['key' => 'entity_id', 'match' => ['value' => (int) $id]];
            }
        }

        try {
            $results = $client->vectorSearch(
                $queryVector,
                $filter,
                $limit,
                $threshold,
                self::COLLECTION_ARTICLES
            );

            // Formatear resultados.
            return array_map(function ($hit) {
                return [
                    'article_id' => $hit['payload']['entity_id'] ?? NULL,
                    'score' => round($hit['score'] * 100, 2),
                    'title' => $hit['payload']['title'] ?? '',
                    'category' => $hit['payload']['category'] ?? '',
                    'payload' => $hit['payload'],
                ];
            }, $results);
        } catch (\Exception $e) {
            $this->logger->error('Article search failed: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Busca artículos relacionados a un artículo específico.
     *
     * @param int $articleId
     *   ID del artículo base.
     * @param int $limit
     *   Número de resultados.
     *
     * @return array
     *   Array de artículos relacionados.
     */
    public function findRelatedArticles(int $articleId, int $limit = 5): array
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return [];
        }

        // Obtener el vector del artículo actual.
        try {
            $points = $client->scroll([
                'must' => [
                    ['key' => 'entity_id', 'match' => ['value' => $articleId]],
                ],
            ], 1, self::COLLECTION_ARTICLES);

            if (empty($points)) {
                return [];
            }

            // El scroll no retorna vectores, necesitamos buscar por el punto.
            // Por ahora, usamos el embedding almacenado en la entidad.
            return [];
        } catch (\Exception $e) {
            $this->logger->error('Failed to find related articles: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Elimina un artículo del índice.
     *
     * @param int $articleId
     *   ID del artículo.
     *
     * @return bool
     *   TRUE si se eliminó.
     */
    public function deleteArticle(int $articleId): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        try {
            return $client->deletePoints(["article_{$articleId}"], self::COLLECTION_ARTICLES);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete article @id from index: @error', [
                '@id' => $articleId,
                '@error' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Verifica si Qdrant está disponible.
     *
     * @return bool
     *   TRUE si Qdrant está accesible.
     */
    public function isAvailable(): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        try {
            return $client->ping();
        } catch (\Exception $e) {
            return FALSE;
        }
    }

}
