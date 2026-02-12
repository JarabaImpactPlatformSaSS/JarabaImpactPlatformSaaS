<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente Qdrant especializado para Matching Engine.
 *
 * Extiende la funcionalidad de QdrantDirectClient de jaraba_rag
 * para las colecciones específicas de matching (job_vectors, candidate_vectors).
 *
 * ARQUITECTURA:
 * - Reutiliza QdrantDirectClient de jaraba_rag
 * - Gestiona colecciones separadas para jobs y candidates
 * - Aplica filtros multi-tenant automáticamente
 */
class QdrantMatchingClient
{

    /**
     * Colección para vectores de jobs.
     */
    const COLLECTION_JOBS = 'matching_jobs';

    /**
     * Colección para vectores de candidates.
     */
    const COLLECTION_CANDIDATES = 'matching_candidates';

    /**
     * HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;

    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Qdrant client from jaraba_rag.
     *
     * @var \Drupal\jaraba_rag\Client\QdrantDirectClient|null
     */
    protected $qdrantClient;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        $logger_factory
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_matching');
    }

    /**
     * Obtiene el cliente Qdrant de jaraba_rag.
     *
     * @return \Drupal\jaraba_rag\Client\QdrantDirectClient|null
     */
    protected function getQdrantClient(): ?QdrantDirectClient
    {
        if ($this->qdrantClient === NULL) {
            if (\Drupal::hasService('jaraba_rag.qdrant_client')) {
                $this->qdrantClient = \Drupal::service('jaraba_rag.qdrant_client');
            }
        }
        return $this->qdrantClient;
    }

    /**
     * Asegura que las colecciones de matching existen.
     *
     * @return bool
     *   TRUE si ambas colecciones están listas.
     */
    public function ensureCollections(): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            $this->logger->error('Qdrant client not available from jaraba_rag');
            return FALSE;
        }

        $jobsOk = $client->ensureCollection(self::COLLECTION_JOBS);
        $candidatesOk = $client->ensureCollection(self::COLLECTION_CANDIDATES);

        if ($jobsOk && $candidatesOk) {
            $this->logger->info('Matching collections ensured');
        }

        return $jobsOk && $candidatesOk;
    }

    /**
     * Indexa un job posting en Qdrant.
     *
     * @param int $jobId
     *   ID del job.
     * @param array $vector
     *   Vector de embedding.
     * @param array $payload
     *   Metadatos del job para filtros.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexJob(int $jobId, array $vector, array $payload): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        $points = [
            [
                'id' => "job_{$jobId}",
                'vector' => $vector,
                'payload' => array_merge($payload, [
                    'entity_type' => 'job_posting',
                    'entity_id' => $jobId,
                ]),
            ],
        ];

        return $client->upsertPoints($points, self::COLLECTION_JOBS);
    }

    /**
     * Indexa un candidate profile en Qdrant.
     *
     * @param int $candidateId
     *   ID del candidato.
     * @param array $vector
     *   Vector de embedding.
     * @param array $payload
     *   Metadatos del candidato para filtros.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexCandidate(int $candidateId, array $vector, array $payload): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        $points = [
            [
                'id' => "candidate_{$candidateId}",
                'vector' => $vector,
                'payload' => array_merge($payload, [
                    'entity_type' => 'candidate_profile',
                    'entity_id' => $candidateId,
                ]),
            ],
        ];

        return $client->upsertPoints($points, self::COLLECTION_CANDIDATES);
    }

    /**
     * Busca candidatos similares a un vector de job.
     *
     * @param array $jobVector
     *   Vector del job.
     * @param int $tenantId
     *   ID del tenant para filtrar.
     * @param int $limit
     *   Número máximo de resultados.
     * @param float $threshold
     *   Score mínimo (0-1).
     *
     * @return array
     *   Array de candidatos con score.
     */
    public function searchCandidatesForJob(
        array $jobVector,
        int $tenantId = 0,
        int $limit = 20,
        float $threshold = 0.6
    ): array {
        $client = $this->getQdrantClient();
        if (!$client || empty($jobVector)) {
            return [];
        }

        $filter = [];
        if ($tenantId > 0) {
            $filter = [
                'must' => [
                    ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
                ],
            ];
        }

        $results = $client->vectorSearch(
            $jobVector,
            $filter,
            $limit,
            $threshold,
            self::COLLECTION_CANDIDATES
        );

        // Formatear resultados
        return array_map(function ($hit) {
            return [
                'candidate_id' => $hit['payload']['entity_id'] ?? NULL,
                'semantic_score' => round($hit['score'] * 100, 2),
                'payload' => $hit['payload'],
            ];
        }, $results);
    }

    /**
     * Busca jobs similares a un vector de candidato.
     *
     * @param array $candidateVector
     *   Vector del candidato.
     * @param int $tenantId
     *   ID del tenant para filtrar.
     * @param int $limit
     *   Número máximo de resultados.
     * @param float $threshold
     *   Score mínimo (0-1).
     *
     * @return array
     *   Array de jobs con score.
     */
    public function searchJobsForCandidate(
        array $candidateVector,
        int $tenantId = 0,
        int $limit = 20,
        float $threshold = 0.6
    ): array {
        $client = $this->getQdrantClient();
        if (!$client || empty($candidateVector)) {
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

        $results = $client->vectorSearch(
            $candidateVector,
            $filter,
            $limit,
            $threshold,
            self::COLLECTION_JOBS
        );

        return array_map(function ($hit) {
            return [
                'job_id' => $hit['payload']['entity_id'] ?? NULL,
                'semantic_score' => round($hit['score'] * 100, 2),
                'payload' => $hit['payload'],
            ];
        }, $results);
    }

    /**
     * Elimina un job del índice.
     *
     * @param int $jobId
     *   ID del job.
     */
    public function deleteJob(int $jobId): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        return $client->deletePoints(["job_{$jobId}"], self::COLLECTION_JOBS);
    }

    /**
     * Elimina un candidato del índice.
     *
     * @param int $candidateId
     *   ID del candidato.
     */
    public function deleteCandidate(int $candidateId): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        return $client->deletePoints(["candidate_{$candidateId}"], self::COLLECTION_CANDIDATES);
    }

    /**
     * Verifica si Qdrant está disponible.
     */
    public function isAvailable(): bool
    {
        $client = $this->getQdrantClient();
        if (!$client) {
            return FALSE;
        }

        return $client->ping();
    }

}
