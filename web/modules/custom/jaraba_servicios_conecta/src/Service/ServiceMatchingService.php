<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * Service Matching Service for ServiciosConecta (FIX-046 + GAP-AUD-022).
 *
 * Implements hybrid scoring (semantic + rules) to match service
 * seekers with service providers. Follows the pattern of
 * jaraba_matching/MatchingService.
 *
 * GAP-AUD-022: Enhanced with real location distance, availability
 * data, corrected embedding wiring, and AI re-ranking.
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
     * Earth radius in kilometers for Haversine formula.
     */
    protected const EARTH_RADIUS_KM = 6371.0;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Entity type manager.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger.
     * @param object|null $embeddingService
     *   Embedding service for vector search (optional).
     * @param object|null $qdrantClient
     *   Qdrant client for vector search (optional).
     * @param object|null $availabilityService
     *   Availability service for real availability data (optional).
     * @param object|null $aiAgent
     *   AI agent for re-ranking (optional).
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected ?object $embeddingService = NULL,
        protected ?object $qdrantClient = NULL,
        protected ?object $availabilityService = NULL,
        protected ?object $aiAgent = NULL,
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

        $topResults = array_slice($scored, 0, $limit);

        // GAP-AUD-022: AI re-ranking for top results.
        if ($this->aiAgent !== NULL && count($topResults) > 1 && !empty($request['description'])) {
            $topResults = $this->aiReRank($request, $topResults);
        }

        $this->logger->info('Service matching: @count results for category=@cat', [
            '@count' => count($topResults),
            '@cat' => $request['category'] ?? 'any',
        ]);

        return $topResults;
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
            $results = $this->qdrantClient->vectorSearch($embedding, [], 50, 0.7, 'service_providers');
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

        // GAP-AUD-022: Location proximity with Haversine distance calculation.
        $locationMatch = $this->calculateLocationProximity($request, $candidate);
        $score += self::WEIGHTS['location_proximity'] * $locationMatch;

        // Rating (normalize to 0-1).
        $rating = min(($candidate['rating'] ?? 0) / 5.0, 1.0);
        $score += self::WEIGHTS['rating_score'] * $rating;

        // Experience (normalize: 10+ years = 1.0).
        $experience = min(($candidate['experience_years'] ?? 0) / 10.0, 1.0);
        $score += self::WEIGHTS['experience_level'] * $experience;

        // GAP-AUD-022: Real availability data from AvailabilityService.
        $availabilityScore = $this->getAvailabilityScore($candidate);
        $score += self::WEIGHTS['availability_match'] * $availabilityScore;

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
            'location_proximity' => $this->calculateLocationProximity($request, $candidate),
            'rating_score' => min(($candidate['rating'] ?? 0) / 5.0, 1.0),
            'experience_level' => min(($candidate['experience_years'] ?? 0) / 10.0, 1.0),
            'availability_match' => $this->getAvailabilityScore($candidate),
            default => 0,
        };
    }

    /**
     * GAP-AUD-022: Calculates location proximity using Haversine formula.
     *
     * Falls back to city-name comparison if coordinates not available.
     */
    protected function calculateLocationProximity(array $request, array $candidate): float
    {
        $reqLat = (float) ($request['latitude'] ?? 0);
        $reqLng = (float) ($request['longitude'] ?? 0);
        $candLat = (float) ($candidate['latitude'] ?? 0);
        $candLng = (float) ($candidate['longitude'] ?? 0);

        // If both have coordinates, use Haversine.
        if ($reqLat !== 0.0 && $reqLng !== 0.0 && $candLat !== 0.0 && $candLng !== 0.0) {
            $distance = $this->haversineDistance($reqLat, $reqLng, $candLat, $candLng);

            // Score: 1.0 at 0km, decays to 0 at 100km.
            return max(0.0, 1.0 - ($distance / 100.0));
        }

        // Fallback: city-name comparison.
        $reqLocation = mb_strtolower(trim($request['location'] ?? ''));
        $candLocation = mb_strtolower(trim($candidate['location'] ?? ''));

        if (empty($reqLocation) || empty($candLocation)) {
            return 0.5;
        }

        if ($reqLocation === $candLocation) {
            return 1.0;
        }

        // Partial match (e.g., "Sevilla" in "Sevilla, Andalucía").
        if (str_contains($candLocation, $reqLocation) || str_contains($reqLocation, $candLocation)) {
            return 0.7;
        }

        return 0.3;
    }

    /**
     * Haversine formula for distance between two GPS coordinates.
     *
     * @return float
     *   Distance in kilometers.
     */
    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * GAP-AUD-022: Gets real availability score from AvailabilityService.
     *
     * Falls back to 0.8 if service unavailable.
     */
    protected function getAvailabilityScore(array $candidate): float
    {
        if ($this->availabilityService === NULL || empty($candidate['provider_id'])) {
            return 0.8;
        }

        try {
            if (method_exists($this->availabilityService, 'getAvailabilityScore')) {
                $score = $this->availabilityService->getAvailabilityScore((int) $candidate['provider_id']);
                return max(0.0, min(1.0, (float) $score));
            }

            // Alternative: check next available slot.
            if (method_exists($this->availabilityService, 'getNextAvailableSlot')) {
                $slot = $this->availabilityService->getNextAvailableSlot((int) $candidate['provider_id']);
                if (empty($slot)) {
                    return 0.2;
                }
                // Score based on how soon: within 24h=1.0, 48h=0.8, 72h=0.6, 7d=0.4.
                $hoursUntil = (strtotime($slot['start'] ?? 'now') - time()) / 3600;
                if ($hoursUntil <= 24) {
                    return 1.0;
                }
                if ($hoursUntil <= 48) {
                    return 0.8;
                }
                if ($hoursUntil <= 72) {
                    return 0.6;
                }
                return 0.4;
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('Availability check failed: @msg', ['@msg' => $e->getMessage()]);
        }

        return 0.8;
    }

    /**
     * GAP-AUD-022: AI re-ranking of top results for description relevance.
     */
    protected function aiReRank(array $request, array $candidates): array
    {
        try {
            $description = $request['description'] ?? '';
            $candidateSummaries = array_map(fn($c) => [
                'provider_id' => $c['provider_id'] ?? 0,
                'name' => $c['name'] ?? '',
                'category' => $c['category'] ?? '',
                'rating' => $c['rating'] ?? 0,
                'match_score' => $c['match_score'] ?? 0,
            ], $candidates);

            $prompt = AIIdentityRule::apply(
                "Re-rank these service providers by relevance to this request. " .
                "Request: \"$description\". " .
                "Providers: " . json_encode($candidateSummaries) . ". " .
                "Return JSON array of provider_id in order of best fit: [id1, id2, ...].",
                TRUE
            );

            $result = $this->aiAgent->execute([
                'prompt' => $prompt,
                'tier' => 'fast',
                'max_tokens' => 256,
                'temperature' => 0.2,
            ]);

            $responseText = $result['response'] ?? $result['text'] ?? '';
            if (preg_match('/\[[\s\S]*\]/u', $responseText, $matches)) {
                $rankedIds = json_decode($matches[0], TRUE);
                if (json_last_error() === JSON_ERROR_NONE && is_array($rankedIds)) {
                    $idMap = [];
                    foreach ($candidates as $c) {
                        $idMap[$c['provider_id'] ?? 0] = $c;
                    }

                    $reranked = [];
                    foreach ($rankedIds as $id) {
                        if (isset($idMap[(int) $id])) {
                            $entry = $idMap[(int) $id];
                            $entry['ai_reranked'] = TRUE;
                            $reranked[] = $entry;
                            unset($idMap[(int) $id]);
                        }
                    }

                    // Append any candidates not in AI response.
                    foreach ($idMap as $remaining) {
                        $reranked[] = $remaining;
                    }

                    return $reranked;
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('AI re-ranking failed: @msg', ['@msg' => $e->getMessage()]);
        }

        return $candidates;
    }

}
