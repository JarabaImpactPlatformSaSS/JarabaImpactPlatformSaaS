<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Database\Connection;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Psr\Log\LoggerInterface;

/**
 * Servicio RAG mejorado para conocimiento normativo con Qdrant.
 *
 * Utiliza QdrantDirectClient existente para búsqueda vectorial y
 * OpenAI para generación de embeddings.
 *
 * Características:
 * - Búsqueda semántica con Qdrant
 * - Fallback a búsqueda por keywords
 * - Colección dedicada: normative_knowledge
 * - Multitenancy via filtros por dominio
 *
 * @see \Drupal\jaraba_rag\Client\QdrantDirectClient
 */
class NormativeRAGService
{

    /**
     * Nombre de la colección en Qdrant para normativa.
     */
    const COLLECTION_NAME = 'normative_knowledge';

    /**
     * Configuración por defecto.
     */
    const DEFAULT_CONFIG = [
        'top_k' => 5,
        'min_score' => 0.7,
        'embedding_model' => 'text-embedding-3-small',
    ];

    protected Connection $database;
    protected LoggerInterface $logger;
    protected ?AiProviderPluginManager $aiProvider;
    protected NormativeKnowledgeService $normativeService;
    protected ?QdrantDirectClient $qdrantClient;

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        LoggerInterface $logger,
        NormativeKnowledgeService $normativeService,
        ?AiProviderPluginManager $aiProvider = NULL,
        ?QdrantDirectClient $qdrantClient = NULL
    ) {
        $this->database = $database;
        $this->logger = $logger;
        $this->normativeService = $normativeService;
        $this->aiProvider = $aiProvider;
        $this->qdrantClient = $qdrantClient;
    }

    /**
     * Busca conocimiento normativo relevante usando RAG con Qdrant.
     *
     * @param string $query
     *   Consulta del usuario.
     * @param string $mode
     *   Modo del copiloto (fiscal, laboral).
     * @param array $options
     *   Opciones adicionales.
     *
     * @return array
     *   Documentos relevantes con scores.
     */
    public function retrieve(string $query, string $mode, array $options = []): array
    {
        $config = array_merge(self::DEFAULT_CONFIG, $options);

        // Intentar búsqueda semántica con Qdrant
        if ($this->qdrantAvailable()) {
            try {
                $results = $this->qdrantSearch($query, $mode, $config);
                if (!empty($results)) {
                    $this->logger->info('Qdrant RAG: @count results for mode @mode', [
                        '@count' => count($results),
                        '@mode' => $mode,
                    ]);
                    return $results;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Qdrant search failed, using keyword fallback: @error', [
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: búsqueda por keywords (NormativeKnowledgeService original)
        return $this->keywordSearch($query, $mode, $config);
    }

    /**
     * Búsqueda semántica usando Qdrant.
     */
    protected function qdrantSearch(string $query, string $mode, array $config): array
    {
        // Generar embedding de la query
        $queryEmbedding = $this->getEmbedding($query);
        if (empty($queryEmbedding)) {
            $this->logger->warning('Could not generate embedding for query');
            return [];
        }

        // Determinar dominio para filtrar
        $domain = $this->getDomain($mode);

        // Construir filtro por dominio
        $filter = [];
        if ($domain) {
            $filter = [
                'must' => [
                    [
                        'key' => 'domain',
                        'match' => ['value' => $domain],
                    ],
                ],
            ];
        }

        // Ejecutar búsqueda vectorial
        $results = $this->qdrantClient->vectorSearch(
            $queryEmbedding,
            $filter,
            $config['top_k'],
            $config['min_score'],
            self::COLLECTION_NAME
        );

        // Formatear resultados
        return array_map(function ($hit) {
            return [
                'id' => $hit['id'] ?? '',
                'content_key' => $hit['payload']['content_key'] ?? '',
                'content_text' => $hit['payload']['content_text'] ?? '',
                'legal_reference' => $hit['payload']['legal_reference'] ?? '',
                'last_verified' => $hit['payload']['last_verified'] ?? '',
                'score' => round($hit['score'] ?? 0, 4),
                'method' => 'qdrant',
            ];
        }, $results);
    }

    /**
     * Búsqueda por keywords (fallback).
     */
    protected function keywordSearch(string $query, string $mode, array $config): array
    {
        $context = $this->normativeService->enrichContext($mode, $query);

        return array_map(function ($item) {
            return [
                'content_key' => $item['content_key'] ?? '',
                'content_text' => $item['content_es'] ?? '',
                'legal_reference' => $item['legal_reference'] ?? '',
                'last_verified' => $item['last_verified'] ?? '',
                'score' => 0.75, // Score estimado para búsqueda por keywords
                'method' => 'keyword',
            ];
        }, $context);
    }

    /**
     * Genera embedding para un texto usando OpenAI.
     *
     * Usa el mismo patrón que JarabaRagService.generateEmbedding().
     */
    protected function getEmbedding(string $text): array
    {
        if (!$this->aiProvider) {
            $this->logger->warning('AI Provider not available for embeddings');
            return [];
        }

        try {
            // Obtener el proveedor por defecto para embeddings
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('embeddings');

            if (!$defaults) {
                $this->logger->warning('No hay proveedor de embeddings configurado');
                return [];
            }

            /** @var \Drupal\ai\OperationType\Embeddings\EmbeddingsInterface $provider */
            $provider = $this->aiProvider->createInstance($defaults['provider_id']);

            // Generar embedding
            $result = $provider->embeddings($text, $defaults['model_id'] ?? self::DEFAULT_CONFIG['embedding_model']);

            return $result->getNormalized();
        } catch (\Exception $e) {
            $this->logger->warning('Embedding generation failed: @error', ['@error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Indexa un documento normativo en Qdrant.
     *
     * @param array $document
     *   Documento con: content_key, content_text, domain, legal_reference.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexDocument(array $document): bool
    {
        if (!$this->qdrantAvailable()) {
            $this->logger->warning('Qdrant not available for indexing');
            return FALSE;
        }

        $text = $document['content_text'] ?? '';
        if (empty($text)) {
            return FALSE;
        }

        // Generar embedding
        $embedding = $this->getEmbedding($text);
        if (empty($embedding)) {
            $this->logger->warning('Could not generate embedding for document: @key', [
                '@key' => $document['content_key'] ?? 'unknown',
            ]);
            return FALSE;
        }

        // Preparar punto para Qdrant
        $documentId = $document['id'] ?? $document['content_key'] ?? md5($text);
        $point = [
            'id' => "normative_{$documentId}",
            'vector' => $embedding,
            'payload' => [
                'content_key' => $document['content_key'] ?? '',
                'content_text' => $text,
                'domain' => $document['domain'] ?? 'TAX',
                'topic' => $document['topic'] ?? 'GENERAL',
                'legal_reference' => $document['legal_reference'] ?? '',
                'last_verified' => $document['last_verified'] ?? date('Y-m-d'),
                'indexed_at' => date('Y-m-d H:i:s'),
            ],
        ];

        // Asegurar que la colección existe
        $this->qdrantClient->ensureCollection(self::COLLECTION_NAME);

        // Insertar punto
        $success = $this->qdrantClient->upsertPoints([$point], self::COLLECTION_NAME);

        if ($success) {
            $this->logger->info('Indexed normative document: @key in Qdrant', [
                '@key' => $document['content_key'] ?? $documentId,
            ]);
        }

        return $success;
    }

    /**
     * Indexa todos los documentos de la tabla normative_knowledge_base.
     *
     * @return array
     *   Estadísticas de indexación.
     */
    public function indexAllDocuments(): array
    {
        $stats = [
            'total' => 0,
            'indexed' => 0,
            'failed' => 0,
        ];

        // Obtener todos los documentos de la base
        try {
            $results = $this->database->select('normative_knowledge_base', 'n')
                ->fields('n')
                ->condition(
                    $this->database->select('normative_knowledge_base', 'n2')
                        ->orConditionGroup()
                        ->isNull('valid_until')
                        ->condition('valid_until', date('Y-m-d'), '>=')
                )
                ->execute()
                ->fetchAll(\PDO::FETCH_ASSOC);

            $stats['total'] = count($results);

            foreach ($results as $row) {
                $doc = [
                    'id' => $row['id'] ?? $row['content_key'],
                    'content_key' => $row['content_key'] ?? '',
                    'content_text' => $row['content_es'] ?? '',
                    'domain' => $row['domain'] ?? 'TAX',
                    'topic' => $row['topic'] ?? 'GENERAL',
                    'legal_reference' => $row['legal_reference'] ?? '',
                    'last_verified' => $row['last_verified'] ?? '',
                ];

                if ($this->indexDocument($doc)) {
                    $stats['indexed']++;
                } else {
                    $stats['failed']++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error indexing all documents: @error', ['@error' => $e->getMessage()]);
        }

        $this->logger->info('Batch indexing complete: @indexed/@total documents', [
            '@indexed' => $stats['indexed'],
            '@total' => $stats['total'],
        ]);

        return $stats;
    }

    /**
     * Verifica si Qdrant está disponible.
     */
    protected function qdrantAvailable(): bool
    {
        if (!$this->qdrantClient) {
            return FALSE;
        }

        try {
            return $this->qdrantClient->ping();
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Obtiene estadísticas del índice.
     */
    public function getIndexStats(): array
    {
        $stats = [
            'qdrant_available' => $this->qdrantAvailable(),
            'embeddings_available' => $this->aiProvider !== NULL,
            'collection' => self::COLLECTION_NAME,
            'documents_indexed' => 0,
        ];

        if ($this->qdrantAvailable()) {
            try {
                // Hacer scroll sin filtro para contar documentos
                $docs = $this->qdrantClient->scroll([], 1000, self::COLLECTION_NAME);
                $stats['documents_indexed'] = count($docs);
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return $stats;
    }

    /**
     * Obtiene dominio para un modo.
     */
    protected function getDomain(string $mode): ?string
    {
        return match ($mode) {
            'fiscal', 'TAX_EXPERT' => 'TAX',
            'laboral', 'SS_EXPERT' => 'SOCIAL_SECURITY',
            default => NULL,
        };
    }

}
