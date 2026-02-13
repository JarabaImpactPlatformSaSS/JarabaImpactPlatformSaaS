<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_matching\Service\MatchingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for Matching API endpoints.
 *
 * Expone la funcionalidad del MatchingService como API REST.
 */
class MatchingApiController extends ControllerBase
{

    /**
     * The matching service.
     *
     * @var \Drupal\jaraba_matching\Service\MatchingService
     */
    protected $matchingService;

    /**
     * The tenant context service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     */
    public function __construct(MatchingService $matching_service, TenantContextService $tenant_context)
    {
        $this->matchingService = $matching_service;
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_matching.matching_service'),
            $container->get('ecosistema_jaraba_core.tenant_context')
        );
    }

    /**
     * GET /api/v1/match/jobs/{job_id}/candidates
     *
     * Obtiene los top-N candidatos para un job.
     */
    public function getJobCandidates(int $job_id, Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        $tenant_id = (int) ($this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id', 0));

        try {
            $results = $this->matchingService->getTopCandidatesForJob($job_id, $limit, $tenant_id);

            return new JsonResponse([
                'success' => TRUE,
                'job_id' => $job_id,
                'count' => count($results),
                'matches' => $results,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/match/candidates/{profile_id}/jobs
     *
     * Obtiene las ofertas recomendadas para un candidato.
     */
    public function getCandidateJobs(int $profile_id, Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 20);
        $tenant_id = (int) ($this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id', 0));

        try {
            // Cargar candidato
            $candidate = $this->entityTypeManager()->getStorage('candidate_profile')->load($profile_id);

            if (!$candidate) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Candidate not found',
                ], 404);
            }

            // Obtener jobs activos y calcular score para cada uno
            $job_storage = $this->entityTypeManager()->getStorage('job_posting');
            $query = $job_storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', 'published');

            if ($tenant_id) {
                $query->condition('tenant_id', $tenant_id);
            }

            $job_ids = $query->execute();

            $candidate_data = $this->extractCandidateData($candidate);
            $results = [];

            foreach ($job_storage->loadMultiple($job_ids) as $job) {
                $job_data = $this->extractJobData($job);
                $score = $this->matchingService->calculateRuleScore($job_data, $candidate_data);

                $results[] = [
                    'job_id' => $job->id(),
                    'title' => $job->label(),
                    'score' => $score['total'],
                    'breakdown' => $score['breakdown'],
                ];
            }

            // Ordenar por score
            usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
            $results = array_slice($results, 0, $limit);

            return new JsonResponse([
                'success' => TRUE,
                'candidate_id' => $profile_id,
                'count' => count($results),
                'jobs' => $results,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/match/score
     *
     * Obtiene el score específico entre un job y un candidate.
     */
    public function getScore(Request $request): JsonResponse
    {
        $job_id = (int) $request->query->get('job');
        $candidate_id = (int) $request->query->get('candidate');

        if (!$job_id || !$candidate_id) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Both job and candidate parameters are required',
            ], 400);
        }

        try {
            $job = $this->entityTypeManager()->getStorage('job_posting')->load($job_id);
            $candidate = $this->entityTypeManager()->getStorage('candidate_profile')->load($candidate_id);

            if (!$job || !$candidate) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Job or candidate not found',
                ], 404);
            }

            $job_data = $this->extractJobData($job);
            $candidate_data = $this->extractCandidateData($candidate);
            $score = $this->matchingService->calculateRuleScore($job_data, $candidate_data);

            return new JsonResponse([
                'success' => TRUE,
                'job_id' => $job_id,
                'candidate_id' => $candidate_id,
                'score' => $score['total'],
                'breakdown' => $score['breakdown'],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/match/jobs/{job_id}/similar
     *
     * Obtiene jobs similares (para "También te puede interesar").
     */
    public function getSimilarJobs(int $job_id, Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 5);
        $tenantId = (int) ($this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id', 0));

        try {
            $job = $this->entityTypeManager()->getStorage('job_posting')->load($job_id);
            if (!$job) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Job not found',
                ], 404);
            }

            // Try Qdrant semantic search first.
            if (\Drupal::hasService('jaraba_matching.qdrant_client')
                && \Drupal::hasService('jaraba_matching.embedding_service')) {
                try {
                    /** @var \Drupal\jaraba_matching\Service\QdrantMatchingClient $qdrantClient */
                    $qdrantClient = \Drupal::service('jaraba_matching.qdrant_client');
                    /** @var \Drupal\jaraba_matching\Service\EmbeddingService $embeddingService */
                    $embeddingService = \Drupal::service('jaraba_matching.embedding_service');

                    if ($qdrantClient->isAvailable()) {
                        $text = $embeddingService->getJobEmbeddingText($job);
                        $embedding = $embeddingService->generate($text);

                        if (!empty($embedding)) {
                            $results = $qdrantClient->searchJobsForCandidate(
                                $embedding,
                                $tenantId,
                                $limit + 1, // +1 to exclude self
                                0.5
                            );

                            $similarJobs = [];
                            foreach ($results as $result) {
                                $resultJobId = $result['job_id'] ?? $result['payload']['entity_id'] ?? NULL;
                                // Exclude the source job itself.
                                if ($resultJobId && (int) $resultJobId !== $job_id) {
                                    $similarJob = $this->entityTypeManager()
                                        ->getStorage('job_posting')
                                        ->load($resultJobId);
                                    if ($similarJob) {
                                        $similarJobs[] = [
                                            'id' => (int) $similarJob->id(),
                                            'title' => $similarJob->label(),
                                            'similarity_score' => $result['semantic_score'] ?? 0,
                                        ];
                                    }
                                }
                                if (count($similarJobs) >= $limit) {
                                    break;
                                }
                            }

                            return new JsonResponse([
                                'success' => TRUE,
                                'job_id' => $job_id,
                                'count' => count($similarJobs),
                                'similar' => $similarJobs,
                                'meta' => ['method' => 'qdrant_semantic'],
                            ]);
                        }
                    }
                }
                catch (\Exception $e) {
                    // Fall through to tag-based fallback.
                }
            }

            // Fallback: tag-based similarity using shared skills.
            $jobSkills = $this->getFieldValues($job, 'skills_required');
            $similarJobs = [];

            if (!empty($jobSkills)) {
                $query = $this->entityTypeManager()->getStorage('job_posting')->getQuery()
                    ->accessCheck(TRUE)
                    ->condition('status', 'published')
                    ->condition('id', $job_id, '<>')
                    ->range(0, $limit);

                if ($tenantId) {
                    $query->condition('tenant_id', $tenantId);
                }

                $candidateIds = $query->execute();
                $candidateJobs = $this->entityTypeManager()
                    ->getStorage('job_posting')
                    ->loadMultiple($candidateIds);

                foreach ($candidateJobs as $candidateJob) {
                    $candidateSkills = $this->getFieldValues($candidateJob, 'skills_required');
                    $shared = array_intersect($jobSkills, $candidateSkills);
                    $total = array_unique(array_merge($jobSkills, $candidateSkills));
                    $jaccardScore = !empty($total)
                        ? round((count($shared) / count($total)) * 100, 1)
                        : 0;

                    if ($jaccardScore > 0) {
                        $similarJobs[] = [
                            'id' => (int) $candidateJob->id(),
                            'title' => $candidateJob->label(),
                            'similarity_score' => $jaccardScore,
                        ];
                    }
                }

                // Sort by similarity descending.
                usort($similarJobs, fn($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);
                $similarJobs = array_slice($similarJobs, 0, $limit);
            }

            return new JsonResponse([
                'success' => TRUE,
                'job_id' => $job_id,
                'count' => count($similarJobs),
                'similar' => $similarJobs,
                'meta' => ['method' => 'fallback_tags'],
            ]);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'job_id' => $job_id,
                'similar' => [],
                'meta' => ['error' => 'Similarity search unavailable'],
            ], 500);
        }
    }

    /**
     * POST /api/v1/match/feedback
     *
     * Registra feedback sobre un match.
     */
    public function submitFeedback(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['match_result_id']) || empty($data['outcome'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'match_result_id and outcome are required',
            ], 400);
        }

        try {
            $feedback = $this->entityTypeManager()
                ->getStorage('match_feedback')
                ->create([
                    'match_result_id' => $data['match_result_id'],
                    'outcome' => $data['outcome'],
                    'feedback_text' => $data['feedback_text'] ?? '',
                    'application_id' => $data['application_id'] ?? NULL,
                ]);

            $feedback->save();

            return new JsonResponse([
                'success' => TRUE,
                'feedback_id' => $feedback->id(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/match/reindex/job/{job_id}
     *
     * Fuerza re-indexación de un job en Qdrant.
     */
    public function reindexJob(int $job_id): JsonResponse
    {
        try {
            $job = $this->entityTypeManager()->getStorage('job_posting')->load($job_id);
            if (!$job) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Job not found',
                ], 404);
            }

            if (!\Drupal::hasService('jaraba_matching.qdrant_client')
                || !\Drupal::hasService('jaraba_matching.embedding_service')) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Qdrant indexing services not available',
                ], 503);
            }

            /** @var \Drupal\jaraba_matching\Service\QdrantMatchingClient $qdrantClient */
            $qdrantClient = \Drupal::service('jaraba_matching.qdrant_client');
            /** @var \Drupal\jaraba_matching\Service\EmbeddingService $embeddingService */
            $embeddingService = \Drupal::service('jaraba_matching.embedding_service');

            if (!$qdrantClient->isAvailable()) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Qdrant server is not reachable',
                ], 503);
            }

            // Generate embedding from job content.
            $text = $embeddingService->getJobEmbeddingText($job);
            $embedding = $embeddingService->generate($text);

            if (empty($embedding)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Failed to generate embedding for job',
                ], 500);
            }

            // Build payload with metadata for filtering.
            $skills = $this->getFieldValues($job, 'skills_required');
            $payload = [
                'tenant_id' => (int) ($job->get('tenant_id')->target_id ?? $job->get('tenant_id')->value ?? 0),
                'status' => $job->get('status')->value ?? 'draft',
                'title' => $job->label(),
                'skills' => $skills,
                'experience_level' => $job->get('experience_level')->value ?? '',
                'location_city' => $job->hasField('location_city') ? ($job->get('location_city')->value ?? '') : '',
                'remote_type' => $job->hasField('remote_type') ? ($job->get('remote_type')->value ?? '') : '',
                'indexed_at' => time(),
            ];

            // Upsert to Qdrant.
            $success = $qdrantClient->indexJob($job_id, $embedding, $payload);

            if ($success) {
                return new JsonResponse([
                    'success' => TRUE,
                    'job_id' => $job_id,
                    'message' => 'Job successfully indexed in Qdrant',
                    'vector_dimensions' => count($embedding),
                ]);
            }

            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Qdrant upsert failed',
            ], 500);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Indexing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extrae datos de job para scoring.
     */
    protected function extractJobData($job): array
    {
        return [
            'id' => $job->id(),
            'skills_required' => $this->getFieldValues($job, 'skills_required'),
            'skills_preferred' => $this->getFieldValues($job, 'skills_preferred'),
            'experience_level' => $job->get('experience_level')->value ?? 'mid',
            'location_city' => $job->get('location_city')->value ?? '',
            'remote_type' => $job->get('remote_type')->value ?? 'onsite',
            'salary_min' => (float) ($job->get('salary_min')->value ?? 0),
            'salary_max' => (float) ($job->get('salary_max')->value ?? 0),
        ];
    }

    /**
     * Extrae datos de candidate para scoring.
     */
    protected function extractCandidateData($candidate): array
    {
        return [
            'id' => $candidate->id(),
            'skills' => $this->getFieldValues($candidate, 'skills'),
            'experience_years' => (float) ($candidate->get('experience_years')->value ?? 0),
            'location_city' => $candidate->get('location_city')->value ?? '',
            'willing_to_relocate' => (bool) ($candidate->get('willing_to_relocate')->value ?? FALSE),
            'desired_salary_min' => (float) ($candidate->get('desired_salary_min')->value ?? 0),
            'desired_salary_max' => (float) ($candidate->get('desired_salary_max')->value ?? 0),
            'availability_date' => $candidate->get('availability_date')->value ?? NULL,
        ];
    }

    /**
     * Obtiene valores de campo multi-value.
     */
    protected function getFieldValues($entity, string $field_name): array
    {
        if (!$entity->hasField($field_name)) {
            return [];
        }
        $values = [];
        foreach ($entity->get($field_name) as $item) {
            $values[] = $item->target_id ?? $item->value;
        }
        return array_filter($values);
    }

}
