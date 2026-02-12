<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Recommendation service using collaborative filtering for job matching.
 *
 * PROPÓSITO:
 * Implementa recomendaciones de jobs basadas en collaborative filtering:
 * - User-based: "Usuarios similares a ti aplicaron a estos jobs"
 * - Item-based: "Usuarios que aplicaron a X también aplicaron a Y"
 *
 * ALGORITMO:
 * 1. Construir matriz usuario-job de aplicaciones
 * 2. Calcular similitud entre usuarios (cosine similarity)
 * 3. Predecir score para jobs no vistos basado en vecinos
 * 4. Combinar con reglas y semántico para híbrido final
 */
class RecommendationService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->logger = $loggerFactory->get('jaraba_matching');
    }

    /**
     * Obtiene jobs recomendados para un usuario usando collaborative filtering.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $limit
     *   Número máximo de recomendaciones.
     *
     * @return array
     *   Array de jobs recomendados con scores.
     */
    public function getCollaborativeRecommendations(int $userId, int $limit = 10): array
    {
        // 1. Obtener jobs a los que ya aplicó el usuario
        $userJobs = $this->getUserApplications($userId);

        if (empty($userJobs)) {
            // Sin historial, usar fallback a popularidad
            return $this->getPopularJobs($limit);
        }

        // 2. Encontrar usuarios similares
        $similarUsers = $this->findSimilarUsers($userId, $userJobs);

        if (empty($similarUsers)) {
            return $this->getPopularJobs($limit);
        }

        // 3. Obtener jobs de usuarios similares que el usuario no ha visto
        $recommendations = $this->getJobsFromSimilarUsers($userId, $similarUsers, $userJobs, $limit);

        return $recommendations;
    }

    /**
     * Obtiene las aplicaciones de un usuario.
     */
    protected function getUserApplications(int $userId): array
    {
        $query = $this->database->select('job_application', 'ja');
        $query->fields('ja', ['job_id']);
        $query->condition('ja.user_id', $userId);

        return $query->execute()->fetchCol();
    }

    /**
     * Encuentra usuarios con patrones de aplicación similares.
     *
     * Usa Jaccard similarity: |A ∩ B| / |A ∪ B|
     */
    protected function findSimilarUsers(int $userId, array $userJobs, int $kNeighbors = 20): array
    {
        // Obtener todos los usuarios que aplicaron a los mismos jobs
        $query = $this->database->select('job_application', 'ja');
        $query->fields('ja', ['user_id', 'job_id']);
        $query->condition('ja.job_id', $userJobs, 'IN');
        $query->condition('ja.user_id', $userId, '!=');

        $results = $query->execute()->fetchAll();

        // Agrupar jobs por usuario
        $userJobSets = [];
        foreach ($results as $row) {
            $userJobSets[$row->user_id][] = $row->job_id;
        }

        // Calcular similitud Jaccard
        $similarities = [];
        $userJobSet = array_flip($userJobs);

        foreach ($userJobSets as $otherUserId => $otherJobs) {
            $intersection = count(array_intersect_key(array_flip($otherJobs), $userJobSet));
            $union = count(array_unique(array_merge($userJobs, $otherJobs)));

            if ($union > 0) {
                $similarities[$otherUserId] = $intersection / $union;
            }
        }

        // Ordenar por similitud y tomar top K
        arsort($similarities);
        return array_slice($similarities, 0, $kNeighbors, TRUE);
    }

    /**
     * Obtiene jobs recomendados de usuarios similares.
     */
    protected function getJobsFromSimilarUsers(int $userId, array $similarUsers, array $userJobs, int $limit): array
    {
        $similarUserIds = array_keys($similarUsers);

        // Obtener jobs de usuarios similares
        $query = $this->database->select('job_application', 'ja');
        $query->join('job_posting', 'jp', 'ja.job_id = jp.id');
        $query->fields('ja', ['job_id']);
        $query->addExpression('COUNT(DISTINCT ja.user_id)', 'application_count');
        $query->condition('ja.user_id', $similarUserIds, 'IN');
        $query->condition('ja.job_id', $userJobs, 'NOT IN');
        $query->condition('jp.status', 'published');
        $query->groupBy('ja.job_id');
        $query->orderBy('application_count', 'DESC');
        $query->range(0, $limit * 2);

        $results = $query->execute()->fetchAll();

        // Calcular score ponderado por similitud
        $jobScores = [];
        foreach ($results as $row) {
            $score = 0;

            // Sumar similitudes de usuarios que aplicaron a este job
            $usersWhoApplied = $this->getUsersWhoAppliedToJob($row->job_id, $similarUserIds);
            foreach ($usersWhoApplied as $uid) {
                $score += $similarUsers[$uid] ?? 0;
            }

            $jobScores[$row->job_id] = $score;
        }

        // Ordenar por score
        arsort($jobScores);
        $topJobIds = array_slice(array_keys($jobScores), 0, $limit);

        // Cargar entidades
        $recommendations = [];
        $jobStorage = $this->entityTypeManager->getStorage('job_posting');

        foreach ($topJobIds as $jobId) {
            $job = $jobStorage->load($jobId);
            if ($job) {
                $recommendations[] = [
                    'job_id' => $jobId,
                    'title' => $job->label(),
                    'score' => round($jobScores[$jobId] * 100, 1),
                    'reason' => 'collaborative',
                    'similar_users_applied' => count($this->getUsersWhoAppliedToJob($jobId, $similarUserIds)),
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Obtiene usuarios que aplicaron a un job específico.
     */
    protected function getUsersWhoAppliedToJob(int $jobId, array $userIds): array
    {
        $query = $this->database->select('job_application', 'ja');
        $query->fields('ja', ['user_id']);
        $query->condition('ja.job_id', $jobId);
        $query->condition('ja.user_id', $userIds, 'IN');

        return $query->execute()->fetchCol();
    }

    /**
     * Fallback: Jobs más populares (cuando no hay historial).
     */
    protected function getPopularJobs(int $limit): array
    {
        $query = $this->database->select('job_posting', 'jp');
        $query->leftJoin('job_application', 'ja', 'jp.id = ja.job_id');
        $query->fields('jp', ['id']);
        $query->addExpression('COUNT(ja.id)', 'application_count');
        $query->condition('jp.status', 'published');
        $query->groupBy('jp.id');
        $query->orderBy('application_count', 'DESC');
        $query->range(0, $limit);

        $results = $query->execute()->fetchAll();

        $recommendations = [];
        $jobStorage = $this->entityTypeManager->getStorage('job_posting');

        foreach ($results as $row) {
            $job = $jobStorage->load($row->id);
            if ($job) {
                $recommendations[] = [
                    'job_id' => $row->id,
                    'title' => $job->label(),
                    'score' => 50.0, // Score neutral para popularidad
                    'reason' => 'popular',
                    'application_count' => (int) $row->application_count,
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Obtiene recomendaciones híbridas combinando múltiples señales.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $limit
     *   Número de recomendaciones.
     *
     * @return array
     *   Array de jobs con scores híbridos.
     */
    public function getHybridRecommendations(int $userId, int $limit = 10): array
    {
        // 1. Collaborative filtering
        $collaborative = $this->getCollaborativeRecommendations($userId, $limit);

        // 2. Content-based (via MatchingService si disponible)
        $contentBased = $this->getContentBasedRecommendations($userId, $limit);

        // 3. Combinar scores
        $combined = $this->combineRecommendations($collaborative, $contentBased);

        // Ordenar y limitar
        usort($combined, fn($a, $b) => $b['hybrid_score'] <=> $a['hybrid_score']);

        return array_slice($combined, 0, $limit);
    }

    /**
     * Obtiene recomendaciones basadas en contenido (matching semántico).
     */
    protected function getContentBasedRecommendations(int $userId, int $limit): array
    {
        // Intentar usar el MatchingService existente
        try {
            if (!\Drupal::hasService('jaraba_matching.matching_service')) {
                return [];
            }

            $matchingService = \Drupal::service('jaraba_matching.matching_service');

            // Obtener perfil del candidato
            $candidateStorage = $this->entityTypeManager->getStorage('candidate_profile');
            $candidates = $candidateStorage->loadByProperties(['user_id' => $userId]);

            if (empty($candidates)) {
                return [];
            }

            $candidate = reset($candidates);

            // Obtener jobs publicados
            $jobStorage = $this->entityTypeManager->getStorage('job_posting');
            $query = $jobStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', 'published')
                ->range(0, $limit * 2);

            $jobIds = $query->execute();

            $recommendations = [];
            foreach ($jobStorage->loadMultiple($jobIds) as $job) {
                $candidateData = $this->extractCandidateData($candidate);
                $jobData = $this->extractJobData($job);

                $score = $matchingService->calculateRuleScore($jobData, $candidateData);

                $recommendations[] = [
                    'job_id' => $job->id(),
                    'title' => $job->label(),
                    'score' => $score['total'],
                    'reason' => 'content_based',
                ];
            }

            usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);
            return array_slice($recommendations, 0, $limit);
        } catch (\Exception $e) {
            $this->logger->warning('Content-based recommendations failed: @error', ['@error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Combina recomendaciones de múltiples fuentes.
     */
    protected function combineRecommendations(array $collaborative, array $contentBased): array
    {
        $combined = [];
        $seenJobs = [];

        // Pesos para combinación
        $weightCollab = 0.4;
        $weightContent = 0.6;

        // Procesar collaborative
        foreach ($collaborative as $rec) {
            $jobId = $rec['job_id'];
            $combined[$jobId] = [
                'job_id' => $jobId,
                'title' => $rec['title'],
                'collaborative_score' => $rec['score'],
                'content_score' => 0,
                'reason' => $rec['reason'],
            ];
            $seenJobs[$jobId] = TRUE;
        }

        // Procesar content-based
        foreach ($contentBased as $rec) {
            $jobId = $rec['job_id'];
            if (isset($combined[$jobId])) {
                $combined[$jobId]['content_score'] = $rec['score'];
                $combined[$jobId]['reason'] = 'hybrid';
            } else {
                $combined[$jobId] = [
                    'job_id' => $jobId,
                    'title' => $rec['title'],
                    'collaborative_score' => 0,
                    'content_score' => $rec['score'],
                    'reason' => $rec['reason'],
                ];
            }
        }

        // Calcular score híbrido
        foreach ($combined as &$rec) {
            $rec['hybrid_score'] = ($rec['collaborative_score'] * $weightCollab) +
                ($rec['content_score'] * $weightContent);
        }

        return array_values($combined);
    }

    /**
     * Extrae datos del candidato para matching.
     */
    protected function extractCandidateData(object $candidate): array
    {
        return [
            'skills' => $this->getFieldValues($candidate, 'skills'),
            'experience_years' => (float) ($candidate->get('experience_years')->value ?? 0),
            'location_city' => $candidate->get('city')->value ?? '',
        ];
    }

    /**
     * Extrae datos del job para matching.
     */
    protected function extractJobData(object $job): array
    {
        return [
            'skills_required' => $this->getFieldValues($job, 'skills_required'),
            'experience_level' => $job->get('experience_level')->value ?? 'mid',
            'location_city' => $job->get('location_city')->value ?? '',
        ];
    }

    /**
     * Obtiene valores de un campo multi-value.
     */
    protected function getFieldValues(object $entity, string $fieldName): array
    {
        if (!$entity->hasField($fieldName)) {
            return [];
        }
        $values = [];
        foreach ($entity->get($fieldName) as $item) {
            $values[] = $item->target_id ?? $item->value;
        }
        return array_filter($values);
    }

    // =========================================================================
    // FEEDBACK LOOP METHODS (Best Practices Gap Fix)
    // =========================================================================

    /**
     * Obtiene recomendaciones mejoradas usando feedback de contrataciones.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $limit
     *   Número de recomendaciones.
     *
     * @return array
     *   Recomendaciones ponderadas por éxitos históricos.
     */
    public function getRecommendationsWithFeedback(int $userId, int $limit = 10): array
    {
        // 1. Obtener recomendaciones híbridas base
        $recommendations = $this->getHybridRecommendations($userId, $limit * 2);

        // 2. Obtener matches exitosos para ajustar pesos
        $successfulPatterns = $this->getSuccessfulMatchPatterns();

        // 3. Boost scores basado en patrones de éxito
        foreach ($recommendations as &$rec) {
            $boost = $this->calculateFeedbackBoost($rec['job_id'], $successfulPatterns);
            $rec['feedback_boost'] = $boost;
            $rec['final_score'] = $rec['hybrid_score'] * (1 + $boost);
        }

        // 4. Re-ordenar por score final
        usort($recommendations, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Extrae patrones de matches exitosos (hired).
     */
    protected function getSuccessfulMatchPatterns(): array
    {
        // Verificar si existe la tabla
        if (!$this->database->schema()->tableExists('match_feedback')) {
            return [];
        }

        // Obtener feedbacks positivos (hired)
        $query = $this->database->select('match_feedback', 'mf');
        $query->join('job_posting', 'jp', 'mf.job_id = jp.id');
        $query->fields('mf', ['job_id', 'original_score']);
        $query->fields('jp', ['experience_level', 'remote_type']);
        $query->condition('mf.outcome', 'hired');
        $query->range(0, 500);

        $results = $query->execute()->fetchAll();

        // Agrupar por características del job
        $patterns = [
            'successful_jobs' => [],
            'by_experience_level' => [],
            'by_remote_type' => [],
            'avg_original_score' => 0,
        ];

        $totalScore = 0;
        foreach ($results as $row) {
            $patterns['successful_jobs'][$row->job_id] = TRUE;

            $expLevel = $row->experience_level ?? 'mid';
            $patterns['by_experience_level'][$expLevel] =
                ($patterns['by_experience_level'][$expLevel] ?? 0) + 1;

            $remoteType = $row->remote_type ?? 'onsite';
            $patterns['by_remote_type'][$remoteType] =
                ($patterns['by_remote_type'][$remoteType] ?? 0) + 1;

            $totalScore += (float) $row->original_score;
        }

        if (count($results) > 0) {
            $patterns['avg_original_score'] = $totalScore / count($results);
        }

        return $patterns;
    }

    /**
     * Calcula el boost basado en feedback para un job.
     */
    protected function calculateFeedbackBoost(int $jobId, array $patterns): float
    {
        if (empty($patterns)) {
            return 0;
        }

        $boost = 0;

        // Jobs que ya resultaron en contratación tienen boost alto
        if (isset($patterns['successful_jobs'][$jobId])) {
            $boost += 0.3;
        }

        // Cargar job para comparar características
        try {
            $job = $this->entityTypeManager->getStorage('job_posting')->load($jobId);
            if (!$job) {
                return $boost;
            }

            // Boost por nivel de experiencia con éxitos
            $expLevel = $job->get('experience_level')->value ?? 'mid';
            $expCount = $patterns['by_experience_level'][$expLevel] ?? 0;
            if ($expCount > 5) {
                $boost += min(0.15, $expCount * 0.01);
            }

            // Boost por tipo de trabajo remoto con éxitos
            $remoteType = $job->get('remote_type')->value ?? 'onsite';
            $remoteCount = $patterns['by_remote_type'][$remoteType] ?? 0;
            if ($remoteCount > 5) {
                $boost += min(0.1, $remoteCount * 0.01);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error calculating feedback boost: @error', ['@error' => $e->getMessage()]);
        }

        return min(0.5, $boost); // Cap at 50% boost
    }

    /**
     * Registra feedback de un match para aprendizaje futuro.
     *
     * @param int $applicationId
     *   ID de la aplicación.
     * @param string $outcome
     *   Resultado: 'hired', 'rejected_employer', 'rejected_candidate', etc.
     * @param array $metadata
     *   Datos adicionales del feedback.
     *
     * @return bool
     *   TRUE si se registró correctamente.
     */
    public function recordMatchFeedback(int $applicationId, string $outcome, array $metadata = []): bool
    {
        try {
            $feedbackStorage = $this->entityTypeManager->getStorage('match_feedback');

            // Obtener la aplicación
            $appStorage = $this->entityTypeManager->getStorage('job_application');
            $application = $appStorage->load($applicationId);

            if (!$application) {
                $this->logger->warning('Cannot record feedback: application @id not found', ['@id' => $applicationId]);
                return FALSE;
            }

            // Crear el feedback
            $feedback = $feedbackStorage->create([
                'application_id' => $applicationId,
                'job_id' => $application->get('job_id')->target_id ?? 0,
                'candidate_id' => $application->get('candidate_id')->target_id ?? 0,
                'outcome' => $outcome,
                'original_score' => $application->get('match_score')->value ?? 0,
                'days_to_outcome' => $metadata['days_to_outcome'] ?? NULL,
                'feedback_text' => $metadata['notes'] ?? NULL,
                'tenant_id' => $application->get('tenant_id')->target_id ?? NULL,
            ]);

            $feedback->save();

            $this->logger->info('Recorded match feedback: app @app, outcome @outcome', [
                '@app' => $applicationId,
                '@outcome' => $outcome,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error recording match feedback: @error', ['@error' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Obtiene métricas de precisión del sistema de matching.
     *
     * @return array
     *   Métricas de precisión.
     */
    public function getMatchingAccuracyMetrics(): array
    {
        if (!$this->database->schema()->tableExists('match_feedback')) {
            return ['error' => 'No feedback data available'];
        }

        // Total de feedbacks
        $total = (int) $this->database->select('match_feedback', 'mf')
            ->countQuery()
            ->execute()
            ->fetchField();

        // Contratados
        $hired = (int) $this->database->select('match_feedback', 'mf')
            ->condition('outcome', 'hired')
            ->countQuery()
            ->execute()
            ->fetchField();

        // Score promedio de contratados vs rechazados
        $avgScoreHired = $this->database->select('match_feedback', 'mf')
            ->condition('outcome', 'hired')
            ->addExpression('AVG(original_score)', 'avg_score')
            ->execute()
            ->fetchField();

        $avgScoreRejected = $this->database->select('match_feedback', 'mf')
            ->condition('outcome', ['rejected_employer', 'rejected_candidate'], 'IN')
            ->addExpression('AVG(original_score)', 'avg_score')
            ->execute()
            ->fetchField();

        return [
            'total_feedbacks' => $total,
            'hired_count' => $hired,
            'hire_rate' => $total > 0 ? round(($hired / $total) * 100, 1) : 0,
            'avg_score_hired' => $avgScoreHired ? round((float) $avgScoreHired, 1) : NULL,
            'avg_score_rejected' => $avgScoreRejected ? round((float) $avgScoreRejected, 1) : NULL,
            'score_discriminability' => ($avgScoreHired && $avgScoreRejected)
                ? round($avgScoreHired - $avgScoreRejected, 1)
                : NULL,
        ];
    }

}

