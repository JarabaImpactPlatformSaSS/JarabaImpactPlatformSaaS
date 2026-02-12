<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para indexar jobs y candidates en Qdrant.
 *
 * Proporciona métodos para indexación masiva y hooks para
 * indexación automática en insert/update.
 */
class IndexingService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Embedding service.
     *
     * @var \Drupal\jaraba_matching\Service\EmbeddingService
     */
    protected $embeddingService;

    /**
     * Qdrant client.
     *
     * @var \Drupal\jaraba_matching\Service\QdrantMatchingClient
     */
    protected $qdrantClient;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        EmbeddingService $embedding_service,
        QdrantMatchingClient $qdrant_client,
        $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->embeddingService = $embedding_service;
        $this->qdrantClient = $qdrant_client;
        $this->logger = $logger_factory->get('jaraba_matching');
    }

    /**
     * Indexa todos los jobs publicados.
     *
     * @param int $limit
     *   Número máximo de jobs a indexar (0 = todos).
     * @param callable|null $progress
     *   Callback para reportar progreso.
     *
     * @return array
     *   Resultado con 'indexed' y 'errors'.
     */
    public function indexAllJobs(int $limit = 0, ?callable $progress = NULL): array
    {
        // Asegurar que las colecciones existen
        $this->qdrantClient->ensureCollections();

        $storage = $this->entityTypeManager->getStorage('job_posting');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'published');

        if ($limit > 0) {
            $query->range(0, $limit);
        }

        $ids = $query->execute();
        $total = count($ids);
        $indexed = 0;
        $errors = 0;

        foreach ($storage->loadMultiple($ids) as $job) {
            try {
                $this->indexJob($job);
                $indexed++;
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Failed to index job @id: @error', [
                    '@id' => $job->id(),
                    '@error' => $e->getMessage(),
                ]);
            }

            if ($progress) {
                $progress($indexed + $errors, $total);
            }
        }

        $this->logger->info('Indexed @indexed jobs with @errors errors', [
            '@indexed' => $indexed,
            '@errors' => $errors,
        ]);

        return ['indexed' => $indexed, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Indexa todos los candidatos activos.
     *
     * @param int $limit
     *   Número máximo a indexar (0 = todos).
     * @param callable|null $progress
     *   Callback para reportar progreso.
     *
     * @return array
     *   Resultado con 'indexed' y 'errors'.
     */
    public function indexAllCandidates(int $limit = 0, ?callable $progress = NULL): array
    {
        $this->qdrantClient->ensureCollections();

        $storage = $this->entityTypeManager->getStorage('candidate_profile');
        $query = $storage->getQuery()
            ->accessCheck(FALSE);

        // Note: job_search_status field may not exist, index all candidates

        if ($limit > 0) {
            $query->range(0, $limit);
        }

        $ids = $query->execute();
        $total = count($ids);
        $indexed = 0;
        $errors = 0;

        foreach ($storage->loadMultiple($ids) as $candidate) {
            try {
                $this->indexCandidate($candidate);
                $indexed++;
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Failed to index candidate @id: @error', [
                    '@id' => $candidate->id(),
                    '@error' => $e->getMessage(),
                ]);
            }

            if ($progress) {
                $progress($indexed + $errors, $total);
            }
        }

        $this->logger->info('Indexed @indexed candidates with @errors errors', [
            '@indexed' => $indexed,
            '@errors' => $errors,
        ]);

        return ['indexed' => $indexed, 'errors' => $errors, 'total' => $total];
    }

    /**
     * Indexa un job posting individual.
     *
     * @param object $job
     *   La entidad job_posting.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexJob($job): bool
    {
        $text = $this->embeddingService->getJobEmbeddingText($job);
        $vector = $this->embeddingService->generate($text);

        if (empty($vector)) {
            throw new \Exception('Failed to generate embedding');
        }

        $payload = [
            'status' => $job->get('status')->value ?? 'published',
            'tenant_id' => (int) ($job->get('tenant_id')->target_id ?? 0),
            'location_city' => $job->get('location_city')->value ?? '',
            'remote_type' => $job->get('remote_type')->value ?? 'onsite',
            'experience_level' => $job->get('experience_level')->value ?? 'mid',
            'title' => $job->label(),
        ];

        return $this->qdrantClient->indexJob((int) $job->id(), $vector, $payload);
    }

    /**
     * Indexa un candidate profile individual.
     *
     * @param object $candidate
     *   La entidad candidate_profile.
     *
     * @return bool
     *   TRUE si se indexó correctamente.
     */
    public function indexCandidate($candidate): bool
    {
        $text = $this->embeddingService->getCandidateEmbeddingText($candidate);
        $vector = $this->embeddingService->generate($text);

        if (empty($vector)) {
            throw new \Exception('Failed to generate embedding');
        }

        $payload = [
            'status' => 'active',
            'tenant_id' => (int) ($candidate->get('tenant_id')->target_id ?? 0),
            'location_city' => $candidate->hasField('location_city') ? ($candidate->get('location_city')->value ?? '') : '',
            'experience_years' => $candidate->hasField('experience_years') ? ((float) ($candidate->get('experience_years')->value ?? 0)) : 0,
            'headline' => $candidate->hasField('headline') ? ($candidate->get('headline')->value ?? '') : ($candidate->label() ?? ''),
        ];

        return $this->qdrantClient->indexCandidate((int) $candidate->id(), $vector, $payload);
    }

    /**
     * Re-indexa un job (para usar en hooks).
     */
    public function reindexJob(int $jobId): bool
    {
        $storage = $this->entityTypeManager->getStorage('job_posting');
        $job = $storage->load($jobId);

        if (!$job) {
            return FALSE;
        }

        // Si está publicado, indexar; si no, eliminar del índice
        if ($job->get('status')->value === 'published') {
            return $this->indexJob($job);
        } else {
            return $this->qdrantClient->deleteJob($jobId);
        }
    }

    /**
     * Re-indexa un candidato (para usar en hooks).
     */
    public function reindexCandidate(int $candidateId): bool
    {
        $storage = $this->entityTypeManager->getStorage('candidate_profile');
        $candidate = $storage->load($candidateId);

        if (!$candidate) {
            return FALSE;
        }

        // Index all candidates
        return $this->indexCandidate($candidate);
    }

}
