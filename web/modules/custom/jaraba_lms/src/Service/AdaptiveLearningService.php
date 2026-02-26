<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * Adaptive Learning Service (FIX-048 + GAP-AUD-019).
 *
 * Uses AI to recommend next lessons based on student progress,
 * quiz scores, and time invested. Creates personalized learning
 * paths that adapt to individual performance.
 *
 * GAP-AUD-019: AI agent now actively used for personalized
 * recommendations, explanations, and completion predictions.
 */
class AdaptiveLearningService
{

    /**
     * Difficulty levels.
     */
    protected const LEVELS = ['beginner', 'intermediate', 'advanced', 'expert'];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
        protected ?object $aiAgent = NULL,
    ) {
    }

    /**
     * Gets personalized next lesson recommendations.
     *
     * @param int $userId
     *   The student user ID.
     * @param int $courseId
     *   The course ID.
     * @param int $limit
     *   Number of recommendations.
     *
     * @return array
     *   Array of recommended lesson IDs with reasoning.
     */
    public function getNextLessons(int $userId, int $courseId, int $limit = 3): array
    {
        $profile = $this->buildLearnerProfile($userId, $courseId);
        $availableLessons = $this->getAvailableLessons($courseId, $profile);

        if (empty($availableLessons)) {
            return [];
        }

        // Score lessons based on learner profile (rule-based baseline).
        $scored = [];
        foreach ($availableLessons as $lesson) {
            $score = $this->scoreLessonForLearner($lesson, $profile);
            $scored[] = array_merge($lesson, [
                'recommendation_score' => $score,
                'reason' => $this->getRecommendationReason($lesson, $profile),
            ]);
        }

        // Sort by recommendation score descending.
        usort($scored, fn($a, $b) => ($b['recommendation_score'] ?? 0) <=> ($a['recommendation_score'] ?? 0));

        $topLessons = array_slice($scored, 0, $limit);

        // GAP-AUD-019: AI-enhanced personalization.
        if ($this->aiAgent !== NULL && !empty($topLessons)) {
            $topLessons = $this->aiEnhanceRecommendations($topLessons, $profile);
        }

        return $topLessons;
    }

    /**
     * Builds a learner profile from progress data.
     */
    protected function buildLearnerProfile(int $userId, int $courseId): array
    {
        $profile = [
            'user_id' => $userId,
            'course_id' => $courseId,
            'completed_lessons' => [],
            'average_score' => 0.0,
            'total_time_minutes' => 0,
            'current_level' => 'beginner',
            'strengths' => [],
            'weaknesses' => [],
        ];

        try {
            // Load enrollment/progress data.
            if ($this->entityTypeManager->hasDefinition('lms_enrollment')) {
                $storage = $this->entityTypeManager->getStorage('lms_enrollment');
                $ids = $storage->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('user_id', $userId)
                    ->condition('course_id', $courseId)
                    ->execute();

                if (!empty($ids)) {
                    $enrollment = $storage->load(reset($ids));
                    if ($enrollment) {
                        $progress = json_decode($enrollment->get('progress_data')->value ?? '{}', TRUE);
                        $profile['completed_lessons'] = $progress['completed'] ?? [];
                        $profile['average_score'] = (float) ($progress['average_score'] ?? 0);
                        $profile['total_time_minutes'] = (int) ($progress['total_time_minutes'] ?? 0);
                    }
                }
            }

            // Determine current level from average score.
            $avgScore = $profile['average_score'];
            if ($avgScore >= 90) {
                $profile['current_level'] = 'expert';
            }
            elseif ($avgScore >= 70) {
                $profile['current_level'] = 'advanced';
            }
            elseif ($avgScore >= 50) {
                $profile['current_level'] = 'intermediate';
            }

            // GAP-AUD-019: Populate strengths/weaknesses from per-category scores.
            $categoryScores = json_decode($enrollment?->get('progress_data')->value ?? '{}', TRUE);
            $categoryScores = $categoryScores['category_scores'] ?? [];
            foreach ($categoryScores as $category => $catScore) {
                $catScore = (float) $catScore;
                if ($catScore >= 80) {
                    $profile['strengths'][] = $category;
                }
                elseif ($catScore < 50) {
                    $profile['weaknesses'][] = $category;
                }
            }

        } catch (\Exception $e) {
            $this->logger->warning('Failed to build learner profile: @msg', ['@msg' => $e->getMessage()]);
        }

        return $profile;
    }

    /**
     * Gets available (uncompleted) lessons for a course.
     */
    protected function getAvailableLessons(int $courseId, array $profile): array
    {
        $lessons = [];
        $completedIds = $profile['completed_lessons'];

        try {
            if ($this->entityTypeManager->hasDefinition('lms_lesson')) {
                $storage = $this->entityTypeManager->getStorage('lms_lesson');
                $query = $storage->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('course_id', $courseId)
                    ->condition('status', TRUE)
                    ->sort('weight', 'ASC');

                if (!empty($completedIds)) {
                    $query->condition('id', $completedIds, 'NOT IN');
                }

                $ids = $query->execute();
                if (!empty($ids)) {
                    foreach ($storage->loadMultiple($ids) as $entity) {
                        $lessons[] = [
                            'lesson_id' => (int) $entity->id(),
                            'title' => $entity->label() ?? '',
                            'difficulty' => $entity->get('difficulty')->value ?? 'intermediate',
                            'estimated_minutes' => (int) ($entity->get('estimated_minutes')->value ?? 15),
                            'category' => $entity->get('category')->value ?? '',
                            'weight' => (int) ($entity->get('weight')->value ?? 0),
                            'prerequisites' => json_decode($entity->get('prerequisites')->value ?? '[]', TRUE),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get available lessons: @msg', ['@msg' => $e->getMessage()]);
        }

        return $lessons;
    }

    /**
     * Scores a lesson for a specific learner.
     */
    protected function scoreLessonForLearner(array $lesson, array $profile): float
    {
        $score = 0.0;

        // Difficulty alignment: match current level.
        $levelIndex = array_search($profile['current_level'], self::LEVELS);
        $lessonLevelIndex = array_search($lesson['difficulty'] ?? 'intermediate', self::LEVELS);

        if ($levelIndex !== FALSE && $lessonLevelIndex !== FALSE) {
            $diff = abs($levelIndex - $lessonLevelIndex);
            // Slightly above current level is ideal.
            if ($lessonLevelIndex === $levelIndex + 1) {
                $score += 0.4;
            }
            elseif ($diff === 0) {
                $score += 0.3;
            }
            elseif ($diff === 1) {
                $score += 0.2;
            }
        }

        // Prerequisite satisfaction.
        $prereqs = $lesson['prerequisites'] ?? [];
        if (empty($prereqs)) {
            $score += 0.3;
        }
        else {
            $completed = $profile['completed_lessons'];
            $satisfied = count(array_intersect($prereqs, $completed));
            $score += 0.3 * ($satisfied / count($prereqs));
        }

        // Sequential order bonus.
        $score += max(0, 0.3 - ($lesson['weight'] ?? 0) * 0.01);

        return min($score, 1.0);
    }

    /**
     * Gets a human-readable recommendation reason.
     */
    protected function getRecommendationReason(array $lesson, array $profile): string
    {
        $difficulty = $lesson['difficulty'] ?? 'intermediate';
        $level = $profile['current_level'];

        if ($difficulty === $level) {
            return 'Matches your current level.';
        }

        $levelIndex = array_search($level, self::LEVELS);
        $lessonIndex = array_search($difficulty, self::LEVELS);

        if ($lessonIndex > $levelIndex) {
            return 'Challenge yourself with slightly advanced content.';
        }

        return 'Reinforce foundational knowledge.';
    }

    /**
     * GAP-AUD-019: AI-enhanced personalization of recommendations.
     *
     * Uses AI to generate personalized reasons and re-order lessons
     * based on the learner's specific profile (strengths, weaknesses,
     * learning style).
     */
    protected function aiEnhanceRecommendations(array $lessons, array $profile): array
    {
        try {
            $lessonSummaries = array_map(fn($l) => [
                'lesson_id' => $l['lesson_id'],
                'title' => $l['title'],
                'difficulty' => $l['difficulty'],
                'category' => $l['category'],
                'estimated_minutes' => $l['estimated_minutes'],
            ], $lessons);

            $profileSummary = [
                'current_level' => $profile['current_level'],
                'average_score' => $profile['average_score'],
                'completed_count' => count($profile['completed_lessons']),
                'total_time_minutes' => $profile['total_time_minutes'],
                'strengths' => $profile['strengths'],
                'weaknesses' => $profile['weaknesses'],
            ];

            $prompt = AIIdentityRule::apply(
                'Given this learner profile and available lessons, provide personalized recommendations. ' .
                'Return JSON: [{"lesson_id": N, "reason": "personalized explanation in Spanish", "priority": 1-3, "completion_prediction": 0.0-1.0}]. ' .
                'Profile: ' . json_encode($profileSummary) . '. ' .
                'Lessons: ' . json_encode($lessonSummaries) . '.',
                TRUE
            );

            $result = $this->aiAgent->execute([
                'prompt' => $prompt,
                'tier' => 'fast',
                'max_tokens' => 512,
                'temperature' => 0.3,
            ]);

            $responseText = $result['response'] ?? $result['text'] ?? '';
            if (preg_match('/\[[\s\S]*\]/u', $responseText, $matches)) {
                $aiRecommendations = json_decode($matches[0], TRUE);
                if (json_last_error() === JSON_ERROR_NONE && is_array($aiRecommendations)) {
                    // Merge AI insights into the lesson data.
                    $aiMap = [];
                    foreach ($aiRecommendations as $rec) {
                        if (isset($rec['lesson_id'])) {
                            $aiMap[(int) $rec['lesson_id']] = $rec;
                        }
                    }

                    foreach ($lessons as &$lesson) {
                        $lid = (int) $lesson['lesson_id'];
                        if (isset($aiMap[$lid])) {
                            $lesson['reason'] = $aiMap[$lid]['reason'] ?? $lesson['reason'];
                            $lesson['completion_prediction'] = (float) ($aiMap[$lid]['completion_prediction'] ?? 0.0);
                            $lesson['ai_enhanced'] = TRUE;
                        }
                    }
                    unset($lesson);
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('AI enhancement failed, using rule-based: @msg', ['@msg' => $e->getMessage()]);
        }

        return $lessons;
    }

}
