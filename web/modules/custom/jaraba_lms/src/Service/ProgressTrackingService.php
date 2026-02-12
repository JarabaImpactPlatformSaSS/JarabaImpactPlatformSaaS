<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_lms\Entity\EnrollmentInterface;

/**
 * Service for tracking learning progress.
 *
 * Gestiona el seguimiento granular del progreso por actividad,
 * implementando el estándar xAPI de forma simplificada.
 */
class ProgressTrackingService
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
     * The logger.
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_lms');
    }

    /**
     * Records activity completion.
     *
     * @param int $enrollment_id
     *   The enrollment ID.
     * @param int $activity_id
     *   The activity ID.
     * @param string $status
     *   Status: not_started, in_progress, completed, failed.
     * @param float|null $score
     *   Optional score (0-100).
     * @param array $response_data
     *   Optional response data (quiz answers, etc.).
     *
     * @return bool
     *   TRUE on success.
     */
    public function recordProgress(
        int $enrollment_id,
        int $activity_id,
        string $status = 'completed',
        ?float $score = NULL,
        array $response_data = []
    ): bool {
        try {
            // Check if progress record exists
            $existing = $this->database->select('lms_progress_record', 'pr')
                ->fields('pr', ['id', 'attempts'])
                ->condition('enrollment_id', $enrollment_id)
                ->condition('activity_id', $activity_id)
                ->execute()
                ->fetchAssoc();

            $now = \Drupal::time()->getRequestTime();

            if ($existing) {
                // Update existing record
                $this->database->update('lms_progress_record')
                    ->fields([
                        'status' => $status,
                        'score' => $score,
                        'attempts' => ((int) $existing['attempts']) + 1,
                        'last_access' => $now,
                        'completed_at' => $status === 'completed' ? $now : NULL,
                        'response_data' => !empty($response_data) ? json_encode($response_data) : NULL,
                    ])
                    ->condition('id', $existing['id'])
                    ->execute();
            } else {
                // Insert new record
                $this->database->insert('lms_progress_record')
                    ->fields([
                        'enrollment_id' => $enrollment_id,
                        'activity_id' => $activity_id,
                        'status' => $status,
                        'score' => $score,
                        'attempts' => 1,
                        'time_spent_seconds' => 0,
                        'first_access' => $now,
                        'last_access' => $now,
                        'completed_at' => $status === 'completed' ? $now : NULL,
                        'response_data' => !empty($response_data) ? json_encode($response_data) : NULL,
                    ])
                    ->execute();
            }

            $this->logger->debug('Progress recorded: enrollment=@e, activity=@a, status=@s', [
                '@e' => $enrollment_id,
                '@a' => $activity_id,
                '@s' => $status,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error recording progress: @message', ['@message' => $e->getMessage()]);
            return FALSE;
        }
    }

    /**
     * Records time spent on an activity.
     *
     * @param int $enrollment_id
     *   The enrollment ID.
     * @param int $activity_id
     *   The activity ID.
     * @param int $seconds
     *   Seconds to add.
     */
    public function recordTimeSpent(int $enrollment_id, int $activity_id, int $seconds): void
    {
        $this->database->query(
            'UPDATE {lms_progress_record} SET time_spent_seconds = time_spent_seconds + :seconds, last_access = :now WHERE enrollment_id = :eid AND activity_id = :aid',
            [
                ':seconds' => $seconds,
                ':now' => \Drupal::time()->getRequestTime(),
                ':eid' => $enrollment_id,
                ':aid' => $activity_id,
            ]
        );
    }

    /**
     * Calculates overall course progress for an enrollment.
     *
     * @param \Drupal\jaraba_lms\Entity\EnrollmentInterface $enrollment
     *   The enrollment.
     *
     * @return float
     *   Progress percentage (0-100).
     */
    public function calculateCourseProgress(EnrollmentInterface $enrollment): float
    {
        $course_id = $enrollment->getCourseId();
        $enrollment_id = $enrollment->id();

        // Get total required activities for the course
        $total = $this->database->query(
            'SELECT COUNT(a.id) FROM {lms_activity} a
       INNER JOIN {lms_lesson} l ON a.lesson_id = l.id
       WHERE l.course_id = :course_id AND a.is_required = 1',
            [':course_id' => $course_id]
        )->fetchField();

        if ($total == 0) {
            return 100; // No activities = complete by default
        }

        // Get completed activities
        $completed = $this->database->query(
            'SELECT COUNT(pr.id) FROM {lms_progress_record} pr
       INNER JOIN {lms_activity} a ON pr.activity_id = a.id
       INNER JOIN {lms_lesson} l ON a.lesson_id = l.id
       WHERE l.course_id = :course_id 
         AND pr.enrollment_id = :enrollment_id 
         AND pr.status = :status
         AND a.is_required = 1',
            [
                ':course_id' => $course_id,
                ':enrollment_id' => $enrollment_id,
                ':status' => 'completed',
            ]
        )->fetchField();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Gets detailed progress for an enrollment.
     *
     * @param int $enrollment_id
     *   The enrollment ID.
     *
     * @return array
     *   Array of progress records keyed by activity_id.
     */
    public function getEnrollmentProgress(int $enrollment_id): array
    {
        $results = $this->database->select('lms_progress_record', 'pr')
            ->fields('pr')
            ->condition('enrollment_id', $enrollment_id)
            ->execute()
            ->fetchAllAssoc('activity_id', \PDO::FETCH_ASSOC);

        return $results;
    }

    /**
     * Gets learning statistics for a user.
     *
     * @param int $user_id
     *   The user ID.
     *
     * @return array
     *   Statistics array.
     */
    public function getUserLearningStats(int $user_id): array
    {
        $enrollments = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->loadByProperties(['user_id' => $user_id]);

        $total_courses = count($enrollments);
        $completed_courses = 0;
        $total_time_seconds = 0;
        $total_activities_completed = 0;

        foreach ($enrollments as $enrollment) {
            if ($enrollment->isCompleted()) {
                $completed_courses++;
            }

            $progress = $this->getEnrollmentProgress($enrollment->id());
            foreach ($progress as $record) {
                $total_time_seconds += (int) ($record['time_spent_seconds'] ?? 0);
                if ($record['status'] === 'completed') {
                    $total_activities_completed++;
                }
            }
        }

        return [
            'total_courses_enrolled' => $total_courses,
            'courses_completed' => $completed_courses,
            'activities_completed' => $total_activities_completed,
            'total_time_hours' => round($total_time_seconds / 3600, 1),
            'completion_rate' => $total_courses > 0 ? round(($completed_courses / $total_courses) * 100, 1) : 0,
        ];
    }

}
