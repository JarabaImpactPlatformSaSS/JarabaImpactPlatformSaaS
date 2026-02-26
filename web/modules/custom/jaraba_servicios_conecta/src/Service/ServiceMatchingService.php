<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service Matching Service for ServiciosConecta (FIX-046).
 *
 * Implements hybrid scoring (semantic + rules) to match service
 * seekers with service providers. Follows the pattern of
 * jaraba_matching/MatchingService.
 */
class ServiceMatchingService
{

    /**
     * Scoring weight configuration.
     */
    protected const WEIGHTS = [
        'semantic_similarity' => 0.35,
        'category_match' => 0.20,
        'location_proximity' => 0.15,
        'availability_match' => 0.10,
        'rating_score' => 0.10,
        'experience_level' => 0.10,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected ?object $embeddingService = NULL,
        protected ?object $qdrantClient = NULL,
    ) {
    }

    /**
     * Finds matching service providers for a service request.
     *
     * @param array $request
     *   The service request with: description, category, location, urgency.
     * @param int $limit
     *   Maximum results.
     * @param array $filters
     *   Additional filters (min_rating, max_distance_km, etc.).
     *
     * @return array
     *   Sorted array of matches with scores.
     */
    public function findMatches(array $request, int $limit = 10, array $filters = []): array
    {
        $candidates = $this->getCandidates($request, $filters);

        if (empty($candidates)) {
            return [];
        }

        // Score each candidate.
        $scored = [];
        foreach ($candidates as $candidate) {
            $score = $this->calculateScore($request, $candidate);

            // Apply minimum score filter.
            if ($score >= ($filters['min_score'] ?? 0.3)) {
                $candidate['match_score'] = round($score, 4);
                $candidate['score_breakdown'] = $this->getScoreBreakdown($request, $candidate);
                $scored[] = $candidate;
            }
        }

        // Sort by match score descending.
        usort($scored, fn($a, $b) => ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0));

        $this->logger->info('Service matching: @count results for category=@cat', [
            '@count' => count($scored),
            '@cat' => $request['category'] ?? 'any',
        ]);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Gets candidate providers.
     */
    protected function getCandidates(array $request, array $filters): array
    {
        $candidates = [];

        try {
            // Try semantic search first.
            if ($this->embeddingService && $this->qdrantClient && !empty($request['description'])) {
                $candidates = $this->getSemanticCandidates($request['description'], $filters);
            }

            // Fallback: database query.
            if (empty($candidates)) {
                $candidates = $this->getDatabaseCandidates($request, $filters);
            }

        } catch (\Exception $e) {
            $this->logger->warning('Candidate retrieval failed: @msg', ['@msg' => $e->getMessage()]);
        }

        return $candidates;
    }

    /**
     * Gets candidates via semantic search (Qdrant).
     */
    protected function getSemanticCandidates(string $description, array $filters): array
    {
        if (!method_exists($this->embeddingService, 'generateEmbedding')) {
            return [];
        }

        $embedding = $this->embeddingService->generateEmbedding($description);
        if (empty($embedding)) {
            return [];
        }

        if (method_exists($this->qdrantClient, 'vectorSearch')) {
            $results = $this->qdrantClient->vectorSearch('service_providers', $embedding, 50);
            return array_map(fn($r) => array_merge(
                $r['payload'] ?? [],
                ['semantic_score' => $r['score'] ?? 0]
            ), $results);
        }

        return [];
    }

    /**
     * Gets candidates from database.
     */
    protected function getDatabaseCandidates(array $request, array $filters): array
    {
        $candidates = [];

        if (!$this->entityTypeManager->hasDefinition('service_provider')) {
            return $candidates;
        }

        $storage = $this->entityTypeManager->getStorage('service_provider');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', TRUE)
            ->range(0, 50);

        if (!empty($request['category'])) {
            $query->condition('service_category', $request['category']);
        }

        $ids = $query->execute();
        if (!empty($ids)) {
            foreach ($storage->loadMultiple($ids) as $entity) {
                $candidates[] = [
                    'provider_id' => $entity->id(),
                    'name' => $entity->label() ?? '',
                    'category' => $entity->get('service_category')->value ?? '',
                    'rating' => (float) ($entity->get('rating')->value ?? 0),
                    'location' => $entity->get('location')->value ?? '',
                    'experience_years' => (int) ($entity->get('experience_years')->value ?? 0),
                    'semantic_score' => 0,
                ];
            }
        }

        return $candidates;
    }

    /**
     * Calculates hybrid score for a candidate.
     */
    protected function calculateScore(array $request, array $candidate): float
    {
        $score = 0.0;

        // Semantic similarity.
        $score += self::WEIGHTS['semantic_similarity'] * ($candidate['semantic_score'] ?? 0);

        // Category match.
        $categoryMatch = (($request['category'] ?? '') === ($candidate['category'] ?? '')) ? 1.0 : 0.0;
        $score += self::WEIGHTS['category_match'] * $categoryMatch;

        // Location proximity (simplified: same city = 1.0).
        $locationMatch = (($request['location'] ?? '') === ($candidate['location'] ?? '')) ? 1.0 : 0.5;
        $score += self::WEIGHTS['location_proximity'] * $locationMatch;

        // Rating (normalize to 0-1).
        $rating = min(($candidate['rating'] ?? 0) / 5.0, 1.0);
        $score += self::WEIGHTS['rating_score'] * $rating;

        // Experience (normalize: 10+ years = 1.0).
        $experience = min(($candidate['experience_years'] ?? 0) / 10.0, 1.0);
        $score += self::WEIGHTS['experience_level'] * $experience;

        // Availability (default: available).
        $score += self::WEIGHTS['availability_match'] * 0.8;

        return $score;
    }

    /**
     * Gets detailed score breakdown.
     */
    protected function getScoreBreakdown(array $request, array $candidate): array
    {
        $breakdown = [];
        foreach (self::WEIGHTS as $factor => $weight) {
            $breakdown[$factor] = [
                'weight' => $weight,
                'weighted_score' => round($weight * ($this->getFactorScore($request, $candidate, $factor)), 4),
            ];
        }
        return $breakdown;
    }

    /**
     * Gets individual factor score.
     */
    protected function getFactorScore(array $request, array $candidate, string $factor): float
    {
        return match ($factor) {
            'semantic_similarity' => $candidate['semantic_score'] ?? 0,
            'category_match' => (($request['category'] ?? '') === ($candidate['category'] ?? '')) ? 1.0 : 0.0,
            'location_proximity' => (($request['location'] ?? '') === ($candidate['location'] ?? '')) ? 1.0 : 0.5,
            'rating_score' => min(($candidate['rating'] ?? 0) / 5.0, 1.0),
            'experience_level' => min(($candidate['experience_years'] ?? 0) / 10.0, 1.0),
            'availability_match' => 0.8,
            default => 0,
        };
    }

}
