<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_job_board\Entity\JobApplicationInterface;
use Drupal\jaraba_job_board\Entity\JobPostingInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for matching candidates to jobs.
 *
 * Implementa un matching híbrido:
 * - Reglas determinísticas (skills, experiencia, ubicación)
 * - Embeddings vectoriales via Qdrant (semantic matching)
 */
class MatchingService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The database connection.
     */
    protected Connection $database;

    /**
     * The HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * The config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * The logger.
     */
    protected $logger;

    /**
     * Weights for score components.
     */
    protected const WEIGHTS = [
        'skills_match' => 0.35,
        'experience_match' => 0.20,
        'education_match' => 0.10,
        'location_match' => 0.15,
        'semantic_match' => 0.20,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        Connection $database,
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_job_board');
    }

    /**
     * Calculates match score for an application.
     *
     * @param \Drupal\jaraba_job_board\Entity\JobApplicationInterface $application
     *   The job application.
     *
     * @return float
     *   Match score (0-100).
     */
    public function calculateApplicationScore(JobApplicationInterface $application): float
    {
        $job = $application->getJob();
        if (!$job) {
            return 0;
        }

        $candidate_id = $application->getCandidateId();
        return $this->calculateCandidateJobScore($candidate_id, $job);
    }

    /**
     * Calculates match score between a candidate and a job.
     *
     * @param int $candidate_id
     *   The candidate user ID.
     * @param \Drupal\jaraba_job_board\Entity\JobPostingInterface $job
     *   The job posting.
     *
     * @return float
     *   Match score (0-100).
     */
    public function calculateCandidateJobScore(int $candidate_id, JobPostingInterface $job): float
    {
        $scores = [
            'skills_match' => $this->calculateSkillsMatch($candidate_id, $job),
            'experience_match' => $this->calculateExperienceMatch($candidate_id, $job),
            'education_match' => $this->calculateEducationMatch($candidate_id, $job),
            'location_match' => $this->calculateLocationMatch($candidate_id, $job),
            'semantic_match' => $this->calculateSemanticMatch($candidate_id, $job),
        ];

        $weighted_score = 0;
        foreach (self::WEIGHTS as $component => $weight) {
            $weighted_score += $scores[$component] * $weight;
        }

        return round($weighted_score, 2);
    }

    /**
     * Calculates skills match score.
     */
    protected function calculateSkillsMatch(int $candidate_id, JobPostingInterface $job): float
    {
        // Get candidate skills from profile
        $candidate_skills = $this->getCandidateSkills($candidate_id);
        $required_skills = $job->getSkillsRequired();

        if (empty($required_skills)) {
            return 100; // No requirements = full match
        }

        if (empty($candidate_skills)) {
            return 0;
        }

        // Calculate overlap
        $matched = array_intersect($candidate_skills, $required_skills);
        $match_percent = (count($matched) / count($required_skills)) * 100;

        return min(100, $match_percent);
    }

    /**
     * Calculates experience level match.
     */
    protected function calculateExperienceMatch(int $candidate_id, JobPostingInterface $job): float
    {
        $required_level = $job->get('experience_level')->value ?? 'mid';
        $candidate_level = $this->getCandidateExperienceLevel($candidate_id);

        $levels = ['entry' => 1, 'junior' => 2, 'mid' => 3, 'senior' => 4, 'executive' => 5];
        $required = $levels[$required_level] ?? 3;
        $candidate = $levels[$candidate_level] ?? 3;

        if ($candidate >= $required) {
            return 100; // Meets or exceeds requirement
        }

        // Partial credit for close matches
        $diff = $required - $candidate;
        return max(0, 100 - ($diff * 30));
    }

    /**
     * Calculates education match.
     */
    protected function calculateEducationMatch(int $candidate_id, JobPostingInterface $job): float
    {
        $required_level = $job->get('education_level')->value ?? 'none';
        if ($required_level === 'none') {
            return 100;
        }

        $candidate_level = $this->getCandidateEducationLevel($candidate_id);

        $levels = [
            'secondary' => 1,
            'vocational' => 2,
            'bachelor' => 3,
            'master' => 4,
            'phd' => 5,
        ];

        $required = $levels[$required_level] ?? 0;
        $candidate = $levels[$candidate_level] ?? 0;

        return $candidate >= $required ? 100 : max(0, 100 - (($required - $candidate) * 25));
    }

    /**
     * Calculates location match.
     */
    protected function calculateLocationMatch(int $candidate_id, JobPostingInterface $job): float
    {
        $remote_type = $job->getRemoteType();

        // Full remote always matches
        if ($remote_type === 'remote') {
            return 100;
        }

        // Get candidate location
        $candidate_city = $this->getCandidateCity($candidate_id);
        $job_city = $job->getLocationCity();

        if (empty($candidate_city)) {
            return 50; // Unknown location = partial match
        }

        // Same city = perfect match
        if (strtolower($candidate_city) === strtolower($job_city)) {
            return 100;
        }

        // Hybrid and flexible get partial credit
        if (in_array($remote_type, ['hybrid', 'flexible'], TRUE)) {
            return 70;
        }

        // On-site but different city
        return 30;
    }

    /**
     * Calculates semantic match using Qdrant embeddings.
     */
    protected function calculateSemanticMatch(int $candidate_id, JobPostingInterface $job): float
    {
        $config = $this->configFactory->get('jaraba_job_board.settings');
        $qdrant_url = $config->get('qdrant_url');

        if (empty($qdrant_url)) {
            return 50; // Fallback score if Qdrant not configured
        }

        try {
            // Search for similar profiles in Qdrant
            $response = $this->httpClient->post($qdrant_url . '/collections/job_matches/points/search', [
                'json' => [
                    'vector' => $this->getJobEmbedding($job),
                    'filter' => [
                        'must' => [
                            ['key' => 'candidate_id', 'match' => ['value' => $candidate_id]],
                        ],
                    ],
                    'top' => 1,
                    'with_payload' => FALSE,
                ],
                'timeout' => 5,
            ]);

            $data = json_decode($response->getBody()->getContents(), TRUE);
            if (!empty($data['result'][0]['score'])) {
                // Qdrant returns cosine similarity (0-1), convert to percentage
                return min(100, $data['result'][0]['score'] * 100);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Qdrant matching failed: @msg', ['@msg' => $e->getMessage()]);
        }

        return 50; // Default fallback
    }

    /**
     * Gets recommended jobs for a candidate.
     *
     * @param int $candidate_id
     *   The candidate user ID.
     * @param int $limit
     *   Maximum jobs to return.
     *
     * @return array
     *   Array of job IDs with scores.
     */
    public function getRecommendedJobs(int $candidate_id, int $limit = 10): array
    {
        // Get published jobs
        $jobs = $this->entityTypeManager
            ->getStorage('job_posting')
            ->loadByProperties(['status' => 'published']);

        $scored_jobs = [];
        foreach ($jobs as $job) {
            $score = $this->calculateCandidateJobScore($candidate_id, $job);
            if ($score >= 50) { // Minimum threshold
                $scored_jobs[$job->id()] = [
                    'job_id' => $job->id(),
                    'score' => $score,
                    'title' => $job->getTitle(),
                ];
            }
        }

        // Sort by score descending
        uasort($scored_jobs, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored_jobs, 0, $limit);
    }

    /**
     * Gets candidate skills from the candidate_skill entity.
     *
     * Consulta la entidad candidate_skill para obtener los skill_ids asociados
     * al candidato. Los IDs corresponden a términos de la taxonomía 'skills'.
     *
     * @param int $candidate_id
     *   The candidate user ID.
     *
     * @return array
     *   Array of skill term IDs (taxonomy term IDs from 'skills' vocabulary).
     */
    protected function getCandidateSkills(int $candidate_id): array
    {
        try {
            // Consultar la entidad candidate_skill por user_id
            $candidate_skills = $this->entityTypeManager
                ->getStorage('candidate_skill')
                ->loadByProperties(['user_id' => $candidate_id]);

            if (empty($candidate_skills)) {
                return [];
            }

            $skill_ids = [];
            foreach ($candidate_skills as $skill_entity) {
                /** @var \Drupal\jaraba_candidate\Entity\CandidateSkillInterface $skill_entity */
                $skill_id = $skill_entity->get('skill_id')->target_id;
                if ($skill_id) {
                    $skill_ids[] = (int) $skill_id;
                }
            }

            return $skill_ids;
        } catch (\Exception $e) {
            $this->logger->warning('Error getting candidate skills: @msg', ['@msg' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Gets candidate experience level.
     */
    protected function getCandidateExperienceLevel(int $candidate_id): string
    {
        try {
            $profiles = $this->entityTypeManager
                ->getStorage('candidate_profile')
                ->loadByProperties(['user_id' => $candidate_id]);

            if (!empty($profiles)) {
                $profile = reset($profiles);
                if ($profile->hasField('experience_level') && !$profile->get('experience_level')->isEmpty()) {
                    return $profile->get('experience_level')->value;
                }
            }
        } catch (\Exception $e) {
            // Fallback.
        }

        return 'mid';
    }

    /**
     * Gets candidate education level.
     */
    protected function getCandidateEducationLevel(int $candidate_id): string
    {
        try {
            $profiles = $this->entityTypeManager
                ->getStorage('candidate_profile')
                ->loadByProperties(['user_id' => $candidate_id]);

            if (!empty($profiles)) {
                $profile = reset($profiles);
                if ($profile->hasField('education_level') && !$profile->get('education_level')->isEmpty()) {
                    return $profile->get('education_level')->value;
                }
            }
        } catch (\Exception $e) {
            // Fallback.
        }

        return 'bachelor';
    }

    /**
     * Gets candidate city.
     */
    protected function getCandidateCity(int $candidate_id): string
    {
        try {
            $profiles = $this->entityTypeManager
                ->getStorage('candidate_profile')
                ->loadByProperties(['user_id' => $candidate_id]);

            if (!empty($profiles)) {
                $profile = reset($profiles);
                if ($profile->hasField('city') && !$profile->get('city')->isEmpty()) {
                    return $profile->get('city')->value;
                }
            }
        } catch (\Exception $e) {
            // Fallback.
        }

        return '';
    }

    /**
     * Generates embedding vector for a job.
     *
     * @todo EXTERNAL_API: Integrar con servicio de embeddings externo (OpenAI, Cohere).
     */
    protected function getJobEmbedding(JobPostingInterface $job): array
    {
        // Placeholder: generar vector básico basado en TF-IDF simplificado.
        $text = strtolower($job->getTitle() . ' ' . ($job->get('description')->value ?? ''));
        $words = array_count_values(str_word_count($text, 1));
        $totalWords = array_sum($words);

        // Generar vector de 384 dimensiones basado en hash de palabras.
        $vector = array_fill(0, 384, 0.0);
        foreach ($words as $word => $count) {
            $hash = crc32($word) % 384;
            $tfidf = $count / max(1, $totalWords);
            $vector[abs($hash)] += $tfidf;
        }

        // Normalizar el vector.
        $magnitude = sqrt(array_sum(array_map(fn($v) => $v * $v, $vector)));
        if ($magnitude > 0) {
            $vector = array_map(fn($v) => $v / $magnitude, $vector);
        }

        $this->logger->warning('Using placeholder embedding for job @id. Integrate external embedding service.', [
            '@id' => $job->id(),
        ]);

        return $vector;
    }

}
