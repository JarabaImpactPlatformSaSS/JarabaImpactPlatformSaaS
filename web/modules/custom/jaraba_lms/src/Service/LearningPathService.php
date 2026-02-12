<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing learning paths.
 *
 * Learning paths group courses into structured sequences aligned with
 * the Diagnostic Express profiles. They guide users through a curated
 * progression of courses based on their identified skill gaps.
 *
 * Storage: Uses the lms_learning_path schema table (see jaraba_lms.install).
 *
 * @see jaraba_lms_schema()
 */
class LearningPathService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The course service.
   */
  protected CourseService $courseService;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\jaraba_lms\Service\CourseService $courseService
   *   The course service for loading course data.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CourseService $courseService,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->courseService = $courseService;
    $this->logger = $loggerFactory->get('jaraba_lms');
  }

  /**
   * Gets a learning path by its ID.
   *
   * @param int $pathId
   *   The learning path ID.
   *
   * @return array|null
   *   The learning path data with resolved course details, or NULL if not found.
   *   Returned array keys:
   *   - id, title, machine_name, description
   *   - target_profile, target_gap
   *   - courses: Array of course data (id, title, etc.)
   *   - estimated_duration_hours, total_credits
   *   - is_active, created, changed
   */
  public function getPath(int $pathId): ?array {
    $database = \Drupal::database();

    try {
      $row = $database->select('lms_learning_path', 'lp')
        ->fields('lp')
        ->condition('id', $pathId)
        ->execute()
        ->fetchAssoc();

      if (!$row) {
        return NULL;
      }

      return $this->enrichPathData($row);
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading learning path @id: @error', [
        '@id' => $pathId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets a learning path by its machine name.
   *
   * @param string $machineName
   *   The machine name (e.g., 'digital_marketing_basics').
   *
   * @return array|null
   *   The learning path data, or NULL if not found.
   */
  public function getPathByMachineName(string $machineName): ?array {
    $database = \Drupal::database();

    try {
      $row = $database->select('lms_learning_path', 'lp')
        ->fields('lp')
        ->condition('machine_name', $machineName)
        ->execute()
        ->fetchAssoc();

      if (!$row) {
        return NULL;
      }

      return $this->enrichPathData($row);
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading learning path @name: @error', [
        '@name' => $machineName,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets user progress for a specific learning path.
   *
   * Calculates completion status for each course in the path and overall
   * path progress.
   *
   * @param int $userId
   *   The user ID.
   * @param int $pathId
   *   The learning path ID.
   *
   * @return array
   *   Progress data with keys:
   *   - path_id: The learning path ID.
   *   - user_id: The user ID.
   *   - total_courses: Number of courses in the path.
   *   - completed_courses: Number of completed courses.
   *   - completion_percentage: Overall completion (0.0 - 100.0).
   *   - courses: Array of per-course progress info.
   *   - current_course: The next incomplete course, or NULL.
   */
  public function getUserProgress(int $userId, int $pathId): array {
    $path = $this->getPath($pathId);

    if (!$path) {
      return [
        'path_id' => $pathId,
        'user_id' => $userId,
        'total_courses' => 0,
        'completed_courses' => 0,
        'completion_percentage' => 0.0,
        'courses' => [],
        'current_course' => NULL,
      ];
    }

    $courseProgress = [];
    $completedCount = 0;
    $currentCourse = NULL;

    foreach ($path['courses'] as $course) {
      $courseId = (int) ($course['id'] ?? 0);
      if ($courseId === 0) {
        continue;
      }

      $enrollment = $this->getEnrollmentForCourse($userId, $courseId);
      $isCompleted = $enrollment && $this->isEnrollmentCompleted($enrollment);
      $progressPercent = $enrollment ? $this->getEnrollmentProgress($enrollment) : 0.0;

      if ($isCompleted) {
        $completedCount++;
      }
      elseif ($currentCourse === NULL) {
        $currentCourse = $course;
      }

      $courseProgress[] = [
        'course_id' => $courseId,
        'title' => $course['title'] ?? '',
        'enrolled' => $enrollment !== NULL,
        'completed' => $isCompleted,
        'progress_percent' => $progressPercent,
      ];
    }

    $totalCourses = count($path['courses']);

    return [
      'path_id' => $pathId,
      'user_id' => $userId,
      'total_courses' => $totalCourses,
      'completed_courses' => $completedCount,
      'completion_percentage' => $this->calculateCompletionPercentage($userId, $pathId),
      'courses' => $courseProgress,
      'current_course' => $currentCourse,
    ];
  }

  /**
   * Advances a user in a learning path by marking a module complete.
   *
   * This is a convenience method that records completion through the
   * enrollment system and returns the updated path progress.
   *
   * @param int $userId
   *   The user ID.
   * @param int $pathId
   *   The learning path ID.
   * @param int $moduleId
   *   The course/module ID to mark as complete.
   *
   * @return array
   *   Result array with keys:
   *   - success: Whether the advancement was recorded.
   *   - message: Human-readable status message.
   *   - progress: Updated progress data from getUserProgress().
   *   - path_completed: Whether the entire path is now complete.
   */
  public function advanceUser(int $userId, int $pathId, int $moduleId): array {
    $path = $this->getPath($pathId);

    if (!$path) {
      return [
        'success' => FALSE,
        'message' => 'Learning path not found.',
        'progress' => [],
        'path_completed' => FALSE,
      ];
    }

    // Verify the module belongs to this path.
    $courseIds = array_column($path['courses'], 'id');
    if (!in_array($moduleId, $courseIds)) {
      return [
        'success' => FALSE,
        'message' => 'Module does not belong to this learning path.',
        'progress' => [],
        'path_completed' => FALSE,
      ];
    }

    // Check enrollment and mark progress.
    $enrollment = $this->getEnrollmentForCourse($userId, $moduleId);
    if (!$enrollment) {
      $this->logger->warning('User @user not enrolled in course @course for path @path advancement', [
        '@user' => $userId,
        '@course' => $moduleId,
        '@path' => $pathId,
      ]);
      return [
        'success' => FALSE,
        'message' => 'User is not enrolled in this course.',
        'progress' => $this->getUserProgress($userId, $pathId),
        'path_completed' => FALSE,
      ];
    }

    // Mark enrollment as completed if not already.
    if (!$this->isEnrollmentCompleted($enrollment)) {
      try {
        $enrollment->set('status', 'completed');
        $enrollment->set('progress_percent', 100);
        $enrollment->set('completed_at', \Drupal::time()->getRequestTime());
        $enrollment->save();

        $this->logger->info('User @user advanced in path @path: completed module @module', [
          '@user' => $userId,
          '@path' => $pathId,
          '@module' => $moduleId,
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Failed to advance user in path: @error', ['@error' => $e->getMessage()]);
        return [
          'success' => FALSE,
          'message' => 'Failed to record completion.',
          'progress' => $this->getUserProgress($userId, $pathId),
          'path_completed' => FALSE,
        ];
      }
    }

    $progress = $this->getUserProgress($userId, $pathId);
    $pathCompleted = $progress['completion_percentage'] >= 100.0;

    return [
      'success' => TRUE,
      'message' => $pathCompleted
        ? 'Congratulations! Learning path completed.'
        : 'Module completed successfully.',
      'progress' => $progress,
      'path_completed' => $pathCompleted,
    ];
  }

  /**
   * Gets recommended learning paths for a user.
   *
   * Recommendations are based on the user's Diagnostic Express profile
   * and identified skill gaps (target_profile and target_gap fields in
   * lms_learning_path).
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   Array of recommended learning path data, sorted by relevance.
   *   Each entry includes path data plus a 'relevance_score' key.
   */
  public function getRecommendedPaths(int $userId): array {
    $database = \Drupal::database();

    // Load user diagnostic profile data if available.
    $userProfile = $this->loadDiagnosticProfile($userId);

    // Load all active learning paths.
    try {
      $query = $database->select('lms_learning_path', 'lp')
        ->fields('lp')
        ->condition('is_active', 1)
        ->orderBy('created', 'DESC');

      $rows = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading learning paths for recommendations: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }

    $recommendations = [];

    foreach ($rows as $row) {
      $pathData = $this->enrichPathData($row);
      $relevanceScore = $this->calculateRelevanceScore($pathData, $userProfile, $userId);

      // Skip paths the user has already fully completed.
      $completion = $this->calculateCompletionPercentage($userId, (int) $row['id']);
      if ($completion >= 100.0) {
        continue;
      }

      $pathData['relevance_score'] = $relevanceScore;
      $pathData['user_completion'] = $completion;
      $recommendations[] = $pathData;
    }

    // Sort by relevance score (highest first).
    usort($recommendations, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

    return $recommendations;
  }

  /**
   * Calculates the completion percentage for a user on a learning path.
   *
   * @param int $userId
   *   The user ID.
   * @param int $pathId
   *   The learning path ID.
   *
   * @return float
   *   Completion percentage (0.0 - 100.0).
   */
  public function calculateCompletionPercentage(int $userId, int $pathId): float {
    $path = $this->getPath($pathId);

    if (!$path || empty($path['courses'])) {
      return 0.0;
    }

    $totalCourses = count($path['courses']);
    $completedCourses = 0;

    foreach ($path['courses'] as $course) {
      $courseId = (int) ($course['id'] ?? 0);
      if ($courseId === 0) {
        continue;
      }

      $enrollment = $this->getEnrollmentForCourse($userId, $courseId);
      if ($enrollment && $this->isEnrollmentCompleted($enrollment)) {
        $completedCourses++;
      }
    }

    return $totalCourses > 0
      ? round(($completedCourses / $totalCourses) * 100, 2)
      : 0.0;
  }

  /**
   * Gets all active learning paths.
   *
   * @return array
   *   Array of learning path data.
   */
  public function getActivePaths(): array {
    $database = \Drupal::database();

    try {
      $rows = $database->select('lms_learning_path', 'lp')
        ->fields('lp')
        ->condition('is_active', 1)
        ->orderBy('title', 'ASC')
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);

      return array_map([$this, 'enrichPathData'], $rows);
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading active learning paths: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Enriches raw path data with resolved course information.
   *
   * @param array $row
   *   Raw database row from lms_learning_path.
   *
   * @return array
   *   Enriched path data with course details.
   */
  protected function enrichPathData(array $row): array {
    $courseIds = json_decode($row['courses'] ?? '[]', TRUE) ?: [];

    $courses = [];
    foreach ($courseIds as $courseId) {
      $course = $this->courseService->getCourse((int) $courseId);
      if ($course) {
        $courses[] = [
          'id' => $course->id(),
          'title' => $course->label(),
          'difficulty_level' => $course->get('difficulty_level')->value ?? 'beginner',
        ];
      }
      else {
        // Keep reference even if course entity is missing.
        $courses[] = [
          'id' => $courseId,
          'title' => '(Course #' . $courseId . ')',
          'difficulty_level' => 'unknown',
        ];
      }
    }

    return [
      'id' => (int) $row['id'],
      'uuid' => $row['uuid'] ?? '',
      'title' => $row['title'] ?? '',
      'machine_name' => $row['machine_name'] ?? '',
      'description' => $row['description'] ?? '',
      'target_profile' => $row['target_profile'] ?? NULL,
      'target_gap' => $row['target_gap'] ?? NULL,
      'courses' => $courses,
      'course_count' => count($courses),
      'estimated_duration_hours' => (int) ($row['estimated_duration_hours'] ?? 0),
      'total_credits' => (int) ($row['total_credits'] ?? 0),
      'is_active' => (bool) ($row['is_active'] ?? TRUE),
      'created' => (int) ($row['created'] ?? 0),
      'changed' => (int) ($row['changed'] ?? 0),
    ];
  }

  /**
   * Gets an enrollment entity for a user and course.
   *
   * @param int $userId
   *   The user ID.
   * @param int $courseId
   *   The course ID.
   *
   * @return object|null
   *   The enrollment entity, or NULL.
   */
  protected function getEnrollmentForCourse(int $userId, int $courseId): ?object {
    try {
      $enrollments = $this->entityTypeManager
        ->getStorage('lms_enrollment')
        ->loadByProperties([
          'user_id' => $userId,
          'course_id' => $courseId,
        ]);

      return !empty($enrollments) ? reset($enrollments) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Checks if an enrollment is completed.
   *
   * @param object $enrollment
   *   The enrollment entity.
   *
   * @return bool
   *   TRUE if the enrollment status is 'completed'.
   */
  protected function isEnrollmentCompleted(object $enrollment): bool {
    if (method_exists($enrollment, 'isCompleted')) {
      return $enrollment->isCompleted();
    }
    $status = $enrollment->get('status')->value ?? '';
    return $status === 'completed';
  }

  /**
   * Gets the progress percentage from an enrollment.
   *
   * @param object $enrollment
   *   The enrollment entity.
   *
   * @return float
   *   Progress percentage.
   */
  protected function getEnrollmentProgress(object $enrollment): float {
    if (method_exists($enrollment, 'getProgressPercent')) {
      return (float) $enrollment->getProgressPercent();
    }
    return (float) ($enrollment->get('progress_percent')->value ?? 0);
  }

  /**
   * Loads the user's diagnostic profile for recommendation scoring.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   Profile data with 'profile_type' and 'gaps' keys, or empty array.
   */
  protected function loadDiagnosticProfile(int $userId): array {
    try {
      $profiles = $this->entityTypeManager
        ->getStorage('entrepreneur_profile')
        ->loadByProperties(['user_id' => $userId]);

      if (empty($profiles)) {
        return [];
      }

      $profile = reset($profiles);
      return [
        'profile_type' => $profile->get('profile_type')->value ?? NULL,
        'gaps' => json_decode($profile->get('diagnostic_gaps')->value ?? '[]', TRUE) ?: [],
      ];
    }
    catch (\Exception $e) {
      // Profile entity may not exist in this context.
      return [];
    }
  }

  /**
   * Calculates a relevance score for a path relative to a user profile.
   *
   * @param array $pathData
   *   Enriched learning path data.
   * @param array $userProfile
   *   User diagnostic profile data.
   * @param int $userId
   *   The user ID (for enrollment checks).
   *
   * @return float
   *   Relevance score (0.0 - 100.0). Higher is more relevant.
   */
  protected function calculateRelevanceScore(array $pathData, array $userProfile, int $userId): float {
    $score = 50.0; // Base score.

    // Boost if path matches user's diagnostic profile type.
    if (!empty($userProfile['profile_type']) && $pathData['target_profile'] === $userProfile['profile_type']) {
      $score += 30.0;
    }

    // Boost if path addresses one of the user's identified gaps.
    if (!empty($userProfile['gaps']) && in_array($pathData['target_gap'], $userProfile['gaps'])) {
      $score += 20.0;
    }

    // Slight boost for paths the user has started but not finished.
    $completion = $this->calculateCompletionPercentage($userId, $pathData['id']);
    if ($completion > 0 && $completion < 100) {
      $score += 10.0; // User already invested effort.
    }

    return min($score, 100.0);
  }

}
