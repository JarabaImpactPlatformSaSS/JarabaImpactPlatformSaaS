<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_mentoring\Entity\MentorProfile;

/**
 * Service for matching mentors with entrepreneurs.
 *
 * Implements intelligent matching based on:
 * - Sector compatibility (25%)
 * - Business stage alignment (20%)
 * - Specialization match (20%)
 * - Rating score (15%)
 * - Availability (10%)
 * - Price range (10%)
 */
class MentorMatchingService
{

    use StringTranslationTrait;

    /**
     * Weight factors for matching algorithm.
     */
    protected const WEIGHTS = [
        'sector' => 0.25,
        'stage' => 0.20,
        'specialization' => 0.20,
        'rating' => 0.15,
        'availability' => 0.10,
        'price' => 0.10,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
    ) {
    }

    /**
     * Finds matching mentors for an entrepreneur.
     *
     * @param array $criteria
     *   Matching criteria including:
     *   - sector: Business sector.
     *   - stage: Business stage (idea, launch, growth, etc.).
     *   - specializations: Desired areas of expertise.
     *   - max_price: Maximum hourly rate willing to pay.
     *   - diagnostic_id: Related diagnostic for context.
     * @param int $limit
     *   Maximum number of results.
     *
     * @return array
     *   Array of mentor matches with scores.
     */
    public function findMatches(array $criteria, int $limit = 10): array
    {
        $storage = $this->entityTypeManager->getStorage('mentor_profile');

        // Get active mentors with available capacity.
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 'active')
            ->condition('is_available', TRUE)
            ->condition('stripe_onboarding_complete', TRUE);

        // Filter by max price if specified.
        if (!empty($criteria['max_price'])) {
            $query->condition('hourly_rate', $criteria['max_price'], '<=');
        }

        $mentor_ids = $query->execute();
        if (empty($mentor_ids)) {
            return [];
        }

        /** @var \Drupal\jaraba_mentoring\Entity\MentorProfile[] $mentors */
        $mentors = $storage->loadMultiple($mentor_ids);

        $matches = [];
        foreach ($mentors as $mentor) {
            $score = $this->calculateMatchScore($mentor, $criteria);
            $matches[] = [
                'mentor' => $mentor,
                'score' => $score,
                'breakdown' => $this->getScoreBreakdown($mentor, $criteria),
            ];
        }

        // Sort by score descending.
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        // Return top matches.
        return array_slice($matches, 0, $limit);
    }

    /**
     * Calculates the match score for a mentor.
     */
    protected function calculateMatchScore(MentorProfile $mentor, array $criteria): float
    {
        $score = 0;

        // Sector match (25%).
        $score += $this->calculateSectorScore($mentor, $criteria) * self::WEIGHTS['sector'];

        // Stage match (20%).
        $score += $this->calculateStageScore($mentor, $criteria) * self::WEIGHTS['stage'];

        // Specialization match (20%).
        $score += $this->calculateSpecializationScore($mentor, $criteria) * self::WEIGHTS['specialization'];

        // Rating score (15%).
        $score += $this->calculateRatingScore($mentor) * self::WEIGHTS['rating'];

        // Availability score (10%).
        $score += $this->calculateAvailabilityScore($mentor) * self::WEIGHTS['availability'];

        // Price score (10%).
        $score += $this->calculatePriceScore($mentor, $criteria) * self::WEIGHTS['price'];

        // Bonus for certification level.
        $score += $this->getCertificationBonus($mentor);

        return min(100, $score);
    }

    /**
     * Calculates sector compatibility score.
     */
    protected function calculateSectorScore(MentorProfile $mentor, array $criteria): float
    {
        if (empty($criteria['sector'])) {
            return 50;
        }

        $mentor_sectors = array_column($mentor->get('sectors')->getValue(), 'value');

        if (in_array($criteria['sector'], $mentor_sectors, TRUE)) {
            return 100;
        }

        return 20;
    }

    /**
     * Calculates business stage alignment score.
     */
    protected function calculateStageScore(MentorProfile $mentor, array $criteria): float
    {
        if (empty($criteria['stage'])) {
            return 50;
        }

        $mentor_stages = array_column($mentor->get('business_stages')->getValue(), 'value');

        if (in_array($criteria['stage'], $mentor_stages, TRUE)) {
            return 100;
        }

        return 20;
    }

    /**
     * Calculates specialization match score.
     */
    protected function calculateSpecializationScore(MentorProfile $mentor, array $criteria): float
    {
        if (empty($criteria['specializations'])) {
            return 50;
        }

        $mentor_specs = array_column($mentor->get('specializations')->getValue(), 'value');
        $requested_specs = (array) $criteria['specializations'];

        $matches = array_intersect($mentor_specs, $requested_specs);
        $match_ratio = count($matches) / max(1, count($requested_specs));

        return $match_ratio * 100;
    }

    /**
     * Calculates rating score.
     */
    protected function calculateRatingScore(MentorProfile $mentor): float
    {
        $rating = $mentor->getAverageRating();
        return ($rating / 5) * 100;
    }

    /**
     * Calculates availability score.
     */
    protected function calculateAvailabilityScore(MentorProfile $mentor): float
    {
        // Query the mentor profile for availability_slots field.
        if (!$mentor->isAvailable()) {
            return 0;
        }

        try {
            if ($mentor->hasField('availability_slots') && !$mentor->get('availability_slots')->isEmpty()) {
                $slotsJson = $mentor->get('availability_slots')->value;
                $slots = json_decode($slotsJson, TRUE);

                if (is_array($slots) && !empty($slots)) {
                    // Score based on number of available slots (more slots = higher score).
                    $slotCount = count($slots);
                    return min(100, $slotCount * 20);
                }
            }
        }
        catch (\Exception $e) {
            // Fall through to default.
        }

        // Mentor is available but no detailed slots defined.
        return 50;
    }

    /**
     * Calculates price score (lower = better).
     */
    protected function calculatePriceScore(MentorProfile $mentor, array $criteria): float
    {
        if (empty($criteria['max_price'])) {
            return 50;
        }

        $rate = $mentor->getHourlyRate();
        $max = $criteria['max_price'];

        if ($rate <= $max * 0.5) {
            return 100;
        } elseif ($rate <= $max * 0.75) {
            return 75;
        } elseif ($rate <= $max) {
            return 50;
        }

        return 0;
    }

    /**
     * Gets certification bonus points.
     */
    protected function getCertificationBonus(MentorProfile $mentor): float
    {
        return match ($mentor->getCertificationLevel()) {
            'elite' => 10,
            'premium' => 7,
            'certified' => 5,
            default => 0,
        };
    }

    /**
     * Gets score breakdown for transparency.
     */
    protected function getScoreBreakdown(MentorProfile $mentor, array $criteria): array
    {
        return [
            'sector' => $this->calculateSectorScore($mentor, $criteria) * self::WEIGHTS['sector'],
            'stage' => $this->calculateStageScore($mentor, $criteria) * self::WEIGHTS['stage'],
            'specialization' => $this->calculateSpecializationScore($mentor, $criteria) * self::WEIGHTS['specialization'],
            'rating' => $this->calculateRatingScore($mentor) * self::WEIGHTS['rating'],
            'availability' => $this->calculateAvailabilityScore($mentor) * self::WEIGHTS['availability'],
            'price' => $this->calculatePriceScore($mentor, $criteria) * self::WEIGHTS['price'],
            'certification_bonus' => $this->getCertificationBonus($mentor),
        ];
    }

}
