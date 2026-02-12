<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal de matching job-candidate.
 *
 * Implementa scoring basado en reglas (Fase 1) y scoring híbrido
 * con embeddings de Qdrant (Fase 2).
 *
 * ARQUITECTURA:
 * - Fase 1: Rule-based scoring con factores ponderados
 * - Fase 2: Hybrid scoring = (semantic_score × α) + (rule_score × β) + (boost × γ)
 *
 * DEPENDENCIAS EXISTENTES:
 * - Qdrant: via jaraba_rag (ya implementado)
 * - Embeddings: via jaraba_ai_core (ya implementado)
 */
class MatchingService
{

    /**
     * Pesos de factores para rule-based scoring.
     */
    const WEIGHT_SKILLS_REQUIRED = 0.35;
    const WEIGHT_EXPERIENCE = 0.20;
    const WEIGHT_LOCATION = 0.15;
    const WEIGHT_SALARY = 0.15;
    const WEIGHT_SKILLS_PREFERRED = 0.10;
    const WEIGHT_AVAILABILITY = 0.05;

    /**
     * Pesos para scoring híbrido.
     */
    const ALPHA_SEMANTIC = 0.40;
    const BETA_RULES = 0.50;
    const GAMMA_BOOST = 0.10;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Cache backend.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected $cache;

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
        Connection $database,
        CacheBackendInterface $cache,
        $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        $this->cache = $cache;
        $this->logger = $logger_factory->get('jaraba_matching');
    }

    /**
     * Calcula el score basado en reglas para un par job-candidate.
     *
     * @param array $job
     *   Datos del job posting.
     * @param array $candidate
     *   Datos del candidate profile.
     *
     * @return array
     *   Array con 'total' y 'breakdown' de scores.
     */
    public function calculateRuleScore(array $job, array $candidate): array
    {
        $breakdown = [];

        // 1. Skills Match (35%)
        $breakdown['skills_required'] = $this->calculateSkillsMatch(
            $job['skills_required'] ?? [],
            $candidate['skills'] ?? []
        );

        // 2. Experience Fit (20%)
        $breakdown['experience'] = $this->calculateExperienceFit(
            $job['experience_level'] ?? 'mid',
            $candidate['experience_years'] ?? 0
        );

        // 3. Location Match (15%)
        $breakdown['location'] = $this->calculateLocationMatch(
            $job['location_city'] ?? '',
            $job['remote_type'] ?? 'onsite',
            $candidate['location_city'] ?? '',
            $candidate['willing_to_relocate'] ?? FALSE
        );

        // 4. Salary Alignment (15%)
        $breakdown['salary'] = $this->calculateSalaryAlignment(
            $job['salary_min'] ?? 0,
            $job['salary_max'] ?? 0,
            $candidate['desired_salary_min'] ?? 0,
            $candidate['desired_salary_max'] ?? 0
        );

        // 5. Skills Preferred (10%)
        $breakdown['skills_preferred'] = $this->calculateSkillsMatch(
            $job['skills_preferred'] ?? [],
            $candidate['skills'] ?? []
        );

        // 6. Availability (5%)
        $breakdown['availability'] = $this->calculateAvailability(
            $candidate['availability_date'] ?? NULL
        );

        // Calcular total ponderado
        $total = ($breakdown['skills_required'] * self::WEIGHT_SKILLS_REQUIRED)
            + ($breakdown['experience'] * self::WEIGHT_EXPERIENCE)
            + ($breakdown['location'] * self::WEIGHT_LOCATION)
            + ($breakdown['salary'] * self::WEIGHT_SALARY)
            + ($breakdown['skills_preferred'] * self::WEIGHT_SKILLS_PREFERRED)
            + ($breakdown['availability'] * self::WEIGHT_AVAILABILITY);

        return [
            'total' => round($total, 2),
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calcula el match de skills requeridas.
     *
     * @param array $required
     *   IDs de skills requeridas.
     * @param array $candidate_skills
     *   IDs de skills del candidato.
     *
     * @return float
     *   Score 0-100.
     */
    protected function calculateSkillsMatch(array $required, array $candidate_skills): float
    {
        if (empty($required)) {
            return 100.0;
        }
        $intersection = array_intersect($required, $candidate_skills);
        return (count($intersection) / count($required)) * 100;
    }

    /**
     * Calcula el fit de experiencia con decay gaussiano.
     *
     * @param string $level
     *   Nivel requerido (junior, mid, senior).
     * @param float $years
     *   Años de experiencia del candidato.
     *
     * @return float
     *   Score 0-100.
     */
    protected function calculateExperienceFit(string $level, float $years): float
    {
        // Mapeo de nivel a años óptimos
        $optimal = [
            'entry' => 0,
            'junior' => 1,
            'mid' => 3,
            'senior' => 6,
            'lead' => 10,
        ];

        $target = $optimal[$level] ?? 3;
        $sigma = 2.0; // Desviación estándar

        // Decay gaussiano
        $diff = abs($years - $target);
        $score = exp(-pow($diff, 2) / (2 * pow($sigma, 2))) * 100;

        return round($score, 2);
    }

    /**
     * Calcula el match de ubicación.
     *
     * @param string $job_city
     *   Ciudad del trabajo.
     * @param string $remote_type
     *   Tipo de trabajo (onsite, hybrid, remote).
     * @param string $candidate_city
     *   Ciudad del candidato.
     * @param bool $willing_to_relocate
     *   Si el candidato está dispuesto a mudarse.
     *
     * @return float
     *   Score 0-100.
     */
    protected function calculateLocationMatch(
        string $job_city,
        string $remote_type,
        string $candidate_city,
        bool $willing_to_relocate
    ): float {
        // Remote siempre es match perfecto
        if ($remote_type === 'remote') {
            return 100.0;
        }

        // Ciudad exacta
        if (strtolower($job_city) === strtolower($candidate_city)) {
            return 100.0;
        }

        // Híbrido con disposición a mudarse
        if ($remote_type === 'hybrid' && $willing_to_relocate) {
            return 70.0;
        }

        // Onsite pero dispuesto a mudarse
        if ($willing_to_relocate) {
            return 50.0;
        }

        // Sin match de ubicación
        return 0.0;
    }

    /**
     * Calcula la alineación salarial.
     *
     * @param float $job_min
     *   Salario mínimo del trabajo.
     * @param float $job_max
     *   Salario máximo del trabajo.
     * @param float $candidate_min
     *   Expectativa salarial mínima.
     * @param float $candidate_max
     *   Expectativa salarial máxima (opcional).
     *
     * @return float
     *   Score 0-100.
     */
    protected function calculateSalaryAlignment(
        float $job_min,
        float $job_max,
        float $candidate_min,
        float $candidate_max = 0
    ): float {
        // Si no hay datos salariales, asumir neutral
        if ($job_max == 0 || $candidate_min == 0) {
            return 50.0;
        }

        // Calcular overlap
        $overlap_start = max($job_min, $candidate_min);
        $overlap_end = $candidate_max > 0 ? min($job_max, $candidate_max) : $job_max;

        if ($overlap_start > $overlap_end) {
            // Sin overlap - calcular distancia
            $gap = ($candidate_min > $job_max)
                ? ($candidate_min - $job_max) / $candidate_min
                : ($job_min - $candidate_max) / $job_min;
            return max(0, (1 - $gap) * 50);
        }

        // Con overlap - normalizar
        $job_range = $job_max - $job_min;
        $overlap_range = $overlap_end - $overlap_start;

        if ($job_range > 0) {
            return min(100, ($overlap_range / $job_range) * 100 + 50);
        }

        return 100.0;
    }

    /**
     * Calcula la disponibilidad del candidato.
     *
     * @param string|null $availability_date
     *   Fecha de disponibilidad ISO 8601.
     *
     * @return float
     *   Score 0-100.
     */
    protected function calculateAvailability(?string $availability_date): float
    {
        if (empty($availability_date)) {
            return 50.0; // Neutral si no especifica
        }

        $now = new \DateTime();
        $available = new \DateTime($availability_date);
        $days = $now->diff($available)->days;

        // Inmediato = 100, decay por semanas
        if ($days <= 0) {
            return 100.0;
        } elseif ($days <= 7) {
            return 90.0;
        } elseif ($days <= 14) {
            return 80.0;
        } elseif ($days <= 30) {
            return 60.0;
        } elseif ($days <= 60) {
            return 40.0;
        }

        return 20.0;
    }

    /**
     * Obtiene los top-N candidatos para un job.
     *
     * @param int $job_id
     *   ID del job posting.
     * @param int $limit
     *   Número máximo de resultados.
     * @param int $tenant_id
     *   ID del tenant.
     *
     * @return array
     *   Array de match results ordenados por score.
     */
    public function getTopCandidatesForJob(int $job_id, int $limit = 20, int $tenant_id = 0): array
    {
        $cache_key = "match:job:{$job_id}:top:{$limit}:tenant:{$tenant_id}";

        if ($cached = $this->cache->get($cache_key)) {
            return $cached->data;
        }

        // Cargar job
        $job_storage = $this->entityTypeManager->getStorage('job_posting');
        $job = $job_storage->load($job_id);

        if (!$job) {
            return [];
        }

        // Extraer datos del job
        $job_data = $this->extractJobData($job);

        // Cargar candidatos activos del tenant
        $candidate_storage = $this->entityTypeManager->getStorage('candidate_profile');
        $query = $candidate_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('job_search_status', 'not_looking', '!=');

        if ($tenant_id) {
            $query->condition('tenant_id', $tenant_id);
        }

        $candidate_ids = $query->execute();

        $results = [];
        foreach ($candidate_storage->loadMultiple($candidate_ids) as $candidate) {
            $candidate_data = $this->extractCandidateData($candidate);
            $score = $this->calculateRuleScore($job_data, $candidate_data);

            $results[] = [
                'candidate_id' => $candidate->id(),
                'score' => $score['total'],
                'breakdown' => $score['breakdown'],
            ];
        }

        // Ordenar por score descendente
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        // Limitar resultados
        $results = array_slice($results, 0, $limit);

        // Cachear por 5 minutos
        $this->cache->set($cache_key, $results, time() + 300);

        return $results;
    }

    /**
     * Extrae datos estructurados de un job posting.
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
     * Extrae datos estructurados de un candidate profile.
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
     * Obtiene valores de un campo multi-value.
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

    /**
     * Calcula el score híbrido combinando reglas y semántico.
     *
     * @param float $ruleScore
     *   Score de reglas (0-100).
     * @param float $semanticScore
     *   Score semántico de Qdrant (0-100).
     * @param array $boostFactors
     *   Factores de boost opcionales.
     *
     * @return array
     *   Array con 'total' y desglose.
     */
    public function calculateHybridScore(
        float $ruleScore,
        float $semanticScore,
        array $boostFactors = []
    ): array {
        // Calcular boost score
        $boostScore = 0.0;
        if (!empty($boostFactors)) {
            // Certificaciones del vertical (+10 cada una, max 30)
            $boostScore += min(30, ($boostFactors['certifications'] ?? 0) * 10);
            // Perfil completado (+10)
            if (($boostFactors['profile_complete'] ?? FALSE)) {
                $boostScore += 10;
            }
            // Cursos completados en plataforma (+5 cada uno, max 20)
            $boostScore += min(20, ($boostFactors['courses_completed'] ?? 0) * 5);
        }
        $boostScore = min(100, $boostScore); // Cap at 100

        // Fórmula híbrida
        $hybridScore = ($semanticScore * self::ALPHA_SEMANTIC)
            + ($ruleScore * self::BETA_RULES)
            + ($boostScore * self::GAMMA_BOOST);

        return [
            'total' => round($hybridScore, 2),
            'breakdown' => [
                'semantic_score' => round($semanticScore, 2),
                'rule_score' => round($ruleScore, 2),
                'boost_score' => round($boostScore, 2),
                'alpha' => self::ALPHA_SEMANTIC,
                'beta' => self::BETA_RULES,
                'gamma' => self::GAMMA_BOOST,
            ],
        ];
    }

    /**
     * Obtiene candidatos con scoring híbrido (Qdrant + Reglas).
     *
     * @param int $jobId
     *   ID del job.
     * @param int $limit
     *   Número máximo de resultados.
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Candidatos ordenados por score híbrido.
     */
    public function getTopCandidatesHybrid(int $jobId, int $limit = 20, int $tenantId = 0): array
    {
        $job_storage = $this->entityTypeManager->getStorage('job_posting');
        $job = $job_storage->load($jobId);

        if (!$job) {
            return [];
        }

        // Obtener servicios de embedding y qdrant
        $embeddingService = \Drupal::service('jaraba_matching.embedding_service');
        $qdrantClient = \Drupal::service('jaraba_matching.qdrant_client');

        // Generar embedding del job
        $jobText = $embeddingService->getJobEmbeddingText($job);
        $jobVector = $embeddingService->generate($jobText);

        if (empty($jobVector)) {
            // Fallback a solo rule-based
            $this->logger->warning('Could not generate job embedding, using rule-based only');
            return $this->getTopCandidatesForJob($jobId, $limit, $tenantId);
        }

        // Buscar candidatos similares en Qdrant
        $semanticMatches = $qdrantClient->searchCandidatesForJob($jobVector, $tenantId, $limit * 2);

        if (empty($semanticMatches)) {
            return $this->getTopCandidatesForJob($jobId, $limit, $tenantId);
        }

        // Extraer datos del job para rule scoring
        $jobData = $this->extractJobData($job);
        $candidateStorage = $this->entityTypeManager->getStorage('candidate_profile');

        $results = [];
        foreach ($semanticMatches as $match) {
            $candidate = $candidateStorage->load($match['candidate_id']);
            if (!$candidate) {
                continue;
            }

            $candidateData = $this->extractCandidateData($candidate);
            $ruleResult = $this->calculateRuleScore($jobData, $candidateData);

            // Calcular boost factors
            $boostFactors = [
                'profile_complete' => ($candidate->get('completion_percent')->value ?? 0) >= 80,
                // TODO: Añadir certifications y courses cuando estén implementados
            ];

            $hybridResult = $this->calculateHybridScore(
                $ruleResult['total'],
                $match['semantic_score'],
                $boostFactors
            );

            $results[] = [
                'candidate_id' => $match['candidate_id'],
                'score' => $hybridResult['total'],
                'score_type' => 'hybrid',
                'breakdown' => $hybridResult['breakdown'],
                'rule_breakdown' => $ruleResult['breakdown'],
            ];
        }

        // Ordenar por score híbrido
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($results, 0, $limit);
    }

}
