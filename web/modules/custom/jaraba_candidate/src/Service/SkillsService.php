<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for managing candidate skills and assessments.
 *
 * Gestiona evaluaciones de competencias, perfiles de habilidades,
 * análisis de gaps y recomendaciones de formación.
 */
class SkillsService {

  /**
   * Skill categories with their subcategories.
   */
  private const SKILL_CATEGORIES = [
    'technical' => ['programming', 'data_analysis', 'design', 'project_management', 'cloud', 'cybersecurity'],
    'soft' => ['communication', 'leadership', 'teamwork', 'problem_solving', 'creativity', 'adaptability'],
    'digital' => ['office_suite', 'social_media', 'ecommerce', 'seo_sem', 'ai_tools', 'no_code'],
    'languages' => ['spanish', 'english', 'french', 'german', 'portuguese', 'chinese'],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
  ) {}

  /**
   * Assesses skills from questionnaire answers.
   *
   * @param int $userId
   *   The user ID.
   * @param array $answers
   *   Answers keyed by skill_id with numeric scores (1-5).
   *
   * @return array
   *   Assessment result with 'skills', 'category_scores', 'overall_score'.
   */
  public function assessSkills(int $userId, array $answers): array {
    $skills = [];
    $categoryScores = [];

    foreach ($answers as $skillId => $score) {
      $score = max(1, min(5, (int) $score));
      $category = $this->getSkillCategory($skillId);

      $skills[$skillId] = [
        'skill_id' => $skillId,
        'score' => $score,
        'level' => $this->scoreToLevel($score),
        'category' => $category,
      ];

      $categoryScores[$category][] = $score;
    }

    // Calculate category averages.
    $categoryAverages = [];
    foreach ($categoryScores as $cat => $scores) {
      $categoryAverages[$cat] = round(array_sum($scores) / count($scores), 2);
    }

    $overall = !empty($categoryAverages)
      ? round(array_sum($categoryAverages) / count($categoryAverages), 2)
      : 0.0;

    // Save assessment.
    $this->saveAssessment($userId, $skills, $categoryAverages, $overall);

    return [
      'user_id' => $userId,
      'skills' => $skills,
      'category_scores' => $categoryAverages,
      'overall_score' => $overall,
      'assessed_at' => date('Y-m-d\TH:i:s'),
      'total_skills_assessed' => count($skills),
    ];
  }

  /**
   * Gets a user's skill profile.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   Skill profile with categories and scores.
   */
  public function getSkillProfile(int $userId): array {
    $storage = $this->entityTypeManager->getStorage('candidate_skill_assessment');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return [
        'user_id' => $userId,
        'skills' => [],
        'category_scores' => [],
        'overall_score' => 0.0,
        'has_assessment' => FALSE,
      ];
    }

    $assessment = $storage->load(reset($ids));
    if (!$assessment) {
      return ['user_id' => $userId, 'skills' => [], 'has_assessment' => FALSE];
    }

    $skills = json_decode($assessment->get('skills_data')->value ?? '{}', TRUE) ?: [];
    $categories = json_decode($assessment->get('category_scores')->value ?? '{}', TRUE) ?: [];

    return [
      'user_id' => $userId,
      'skills' => $skills,
      'category_scores' => $categories,
      'overall_score' => (float) ($assessment->get('overall_score')->value ?? 0),
      'assessed_at' => $assessment->get('created')->value ?? '',
      'has_assessment' => TRUE,
    ];
  }

  /**
   * Analyzes skill gaps between user profile and job requirements.
   *
   * @param int $userId
   *   The user ID.
   * @param int $jobId
   *   The job posting ID.
   *
   * @return array
   *   Gap analysis with 'match_score', 'gaps', 'strengths'.
   */
  public function getSkillGaps(int $userId, int $jobId): array {
    $profile = $this->getSkillProfile($userId);
    $jobStorage = $this->entityTypeManager->getStorage('job_posting');
    $job = $jobStorage->load($jobId);

    if (!$job) {
      return ['error' => 'Job not found'];
    }

    $requiredSkills = json_decode($job->get('required_skills')->value ?? '[]', TRUE) ?: [];

    $gaps = [];
    $strengths = [];
    $matchPoints = 0;
    $totalPoints = 0;

    foreach ($requiredSkills as $required) {
      $skillId = $required['skill_id'] ?? '';
      $requiredLevel = $required['level'] ?? 3;
      $userScore = $profile['skills'][$skillId]['score'] ?? 0;
      $totalPoints += $requiredLevel;

      if ($userScore >= $requiredLevel) {
        $strengths[] = [
          'skill_id' => $skillId,
          'required' => $requiredLevel,
          'actual' => $userScore,
          'surplus' => $userScore - $requiredLevel,
        ];
        $matchPoints += $requiredLevel;
      }
      else {
        $gaps[] = [
          'skill_id' => $skillId,
          'required' => $requiredLevel,
          'actual' => $userScore,
          'gap' => $requiredLevel - $userScore,
          'priority' => $requiredLevel >= 4 ? 'high' : 'medium',
        ];
        $matchPoints += $userScore;
      }
    }

    $matchScore = $totalPoints > 0 ? round(($matchPoints / $totalPoints) * 100, 1) : 0.0;

    // Sort gaps by priority and gap size.
    usort($gaps, fn($a, $b) => $b['gap'] <=> $a['gap']);

    return [
      'user_id' => $userId,
      'job_id' => $jobId,
      'match_score' => $matchScore,
      'gaps' => $gaps,
      'strengths' => $strengths,
      'total_required_skills' => count($requiredSkills),
      'skills_met' => count($strengths),
    ];
  }

  /**
   * Suggests training based on skill gaps.
   *
   * @param int $userId
   *   The user ID.
   * @param array $gaps
   *   Skill gaps from getSkillGaps().
   *
   * @return array
   *   Training recommendations.
   */
  public function suggestTraining(int $userId, array $gaps): array {
    $recommendations = [];

    foreach ($gaps as $gap) {
      $skillId = $gap['skill_id'] ?? '';
      $category = $this->getSkillCategory($skillId);

      $recommendations[] = [
        'skill_id' => $skillId,
        'category' => $category,
        'gap_level' => $gap['gap'] ?? 1,
        'priority' => $gap['priority'] ?? 'medium',
        'suggested_content_type' => $gap['gap'] >= 3 ? 'course' : 'workshop',
        'estimated_hours' => max(2, ($gap['gap'] ?? 1) * 8),
      ];
    }

    // Sort by priority.
    usort($recommendations, function ($a, $b) {
      $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
      return ($priorityOrder[$a['priority']] ?? 2) <=> ($priorityOrder[$b['priority']] ?? 2);
    });

    return [
      'user_id' => $userId,
      'recommendations' => $recommendations,
      'total_estimated_hours' => array_sum(array_column($recommendations, 'estimated_hours')),
    ];
  }

  /**
   * Gets market demand data for a skill.
   *
   * @param string $skill
   *   The skill identifier.
   *
   * @return array
   *   Market demand with 'demand_level', 'trend', 'avg_salary_impact'.
   */
  public function getMarketDemand(string $skill): array {
    // Query job postings that require this skill.
    $storage = $this->entityTypeManager->getStorage('job_posting');

    try {
      $totalJobs = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'published')
        ->count()
        ->execute();

      $jobsWithSkill = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'published')
        ->condition('required_skills', '%' . $skill . '%', 'LIKE')
        ->count()
        ->execute();
    }
    catch (\Exception) {
      $totalJobs = 0;
      $jobsWithSkill = 0;
    }

    $demandRatio = $totalJobs > 0 ? $jobsWithSkill / $totalJobs : 0;

    return [
      'skill' => $skill,
      'jobs_requiring' => $jobsWithSkill,
      'total_jobs' => $totalJobs,
      'demand_ratio' => round($demandRatio, 4),
      'demand_level' => match (TRUE) {
        $demandRatio > 0.3 => 'very_high',
        $demandRatio > 0.15 => 'high',
        $demandRatio > 0.05 => 'medium',
        default => 'low',
      },
      'trend' => 'stable',
    ];
  }

  /**
   * Determines the category for a skill.
   */
  protected function getSkillCategory(string $skillId): string {
    foreach (self::SKILL_CATEGORIES as $category => $skills) {
      if (in_array($skillId, $skills, TRUE)) {
        return $category;
      }
    }
    return 'other';
  }

  /**
   * Converts numeric score to level label.
   */
  protected function scoreToLevel(int $score): string {
    return match (TRUE) {
      $score >= 5 => 'expert',
      $score >= 4 => 'advanced',
      $score >= 3 => 'intermediate',
      $score >= 2 => 'basic',
      default => 'beginner',
    };
  }

  /**
   * Saves skill assessment to entity storage.
   */
  protected function saveAssessment(int $userId, array $skills, array $categories, float $overall): void {
    try {
      $storage = $this->entityTypeManager->getStorage('candidate_skill_assessment');
      $assessment = $storage->create([
        'user_id' => $userId,
        'skills_data' => json_encode($skills, JSON_THROW_ON_ERROR),
        'category_scores' => json_encode($categories, JSON_THROW_ON_ERROR),
        'overall_score' => $overall,
      ]);
      $assessment->save();
    }
    catch (\Exception) {
      // Silently fail — assessment data is available in return value.
    }
  }

}
