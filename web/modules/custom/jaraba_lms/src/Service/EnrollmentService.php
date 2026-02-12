<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_lms\Entity\Enrollment;
use Drupal\jaraba_lms\Entity\EnrollmentInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Service for managing course enrollments.
 *
 * Gestiona las matrículas de usuarios a cursos, incluyendo:
 * - Creación de nuevas matrículas
 * - Actualización de progreso
 * - Verificación de acceso
 * - Envío de recordatorios
 */
class EnrollmentService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The course service.
     */
    protected CourseService $courseService;

    /**
     * The progress tracking service.
     */
    protected ProgressTrackingService $progressService;

    /**
     * The logger.
     */
    protected $logger;

    /**
     * The event dispatcher.
     */
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        CourseService $course_service,
        ProgressTrackingService $progress_service,
        LoggerChannelFactoryInterface $logger_factory,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->courseService = $course_service;
        $this->progressService = $progress_service;
        $this->logger = $logger_factory->get('jaraba_lms');
        $this->eventDispatcher = $event_dispatcher;
    }

    /**
     * Enrolls a user to a course.
     *
     * @param int $user_id
     *   The user ID.
     * @param int $course_id
     *   The course ID.
     * @param string $type
     *   Enrollment type (free, paid, grant, etc.).
     * @param array $metadata
     *   Additional metadata for the enrollment.
     *
     * @return \Drupal\jaraba_lms\Entity\EnrollmentInterface|null
     *   The created enrollment or NULL if already enrolled.
     */
    public function enroll(int $user_id, int $course_id, string $type = 'free', array $metadata = []): ?EnrollmentInterface
    {
        // Check if already enrolled
        if ($this->isEnrolled($user_id, $course_id)) {
            $this->logger->notice('User @user already enrolled in course @course', [
                '@user' => $user_id,
                '@course' => $course_id,
            ]);
            return $this->getEnrollment($user_id, $course_id);
        }

        // Check prerequisites
        if (!$this->checkPrerequisites($user_id, $course_id)) {
            $this->logger->warning('User @user does not meet prerequisites for course @course', [
                '@user' => $user_id,
                '@course' => $course_id,
            ]);
            return NULL;
        }

        // Create enrollment
        $enrollment = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->create([
                'user_id' => $user_id,
                'course_id' => $course_id,
                'enrollment_type' => $type,
                'status' => Enrollment::STATUS_ACTIVE,
                'source' => $metadata['source'] ?? 'organic',
                'metadata' => !empty($metadata) ? json_encode($metadata) : NULL,
            ]);

        $enrollment->save();

        $this->logger->info('User @user enrolled in course @course (type: @type)', [
            '@user' => $user_id,
            '@course' => $course_id,
            '@type' => $type,
        ]);

        // Dispatch enrollment event for ECA.
        $event = new \Symfony\Component\EventDispatcher\GenericEvent($enrollment, [
            'type' => 'enrollment_created',
            'user_id' => $user_id,
            'course_id' => $course_id,
            'enrollment_type' => $type,
        ]);
        $this->eventDispatcher->dispatch($event, 'jaraba_lms.enrollment.created');

        // Send welcome email.
        try {
            $user = $this->entityTypeManager->getStorage('user')->load($user_id);
            $course = $this->entityTypeManager->getStorage('lms_course')->load($course_id);
            if ($user && $course && $user->getEmail()) {
                \Drupal::service('plugin.manager.mail')->mail('jaraba_lms', 'enrollment_created', $user->getEmail(), $user->getPreferredLangcode(), [
                    'user_name' => $user->getDisplayName(),
                    'course_name' => $course->label(),
                    'course_id' => $course_id,
                ]);
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Failed to send enrollment email: @error', ['@error' => $e->getMessage()]);
        }

        return $enrollment;
    }

    /**
     * Checks if a user is enrolled in a course.
     *
     * @param int $user_id
     *   The user ID.
     * @param int $course_id
     *   The course ID.
     *
     * @return bool
     *   TRUE if enrolled with active status.
     */
    public function isEnrolled(int $user_id, int $course_id): bool
    {
        $enrollment = $this->getEnrollment($user_id, $course_id);
        return $enrollment !== NULL && $enrollment->isActive();
    }

    /**
     * Gets an enrollment by user and course.
     *
     * @param int $user_id
     *   The user ID.
     * @param int $course_id
     *   The course ID.
     *
     * @return \Drupal\jaraba_lms\Entity\EnrollmentInterface|null
     *   The enrollment or NULL.
     */
    public function getEnrollment(int $user_id, int $course_id): ?EnrollmentInterface
    {
        $enrollments = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->loadByProperties([
                'user_id' => $user_id,
                'course_id' => $course_id,
            ]);

        return !empty($enrollments) ? reset($enrollments) : NULL;
    }

    /**
     * Gets all enrollments for a user.
     *
     * @param int $user_id
     *   The user ID.
     * @param string|null $status
     *   Optional status filter.
     *
     * @return \Drupal\jaraba_lms\Entity\EnrollmentInterface[]
     *   Array of enrollments.
     */
    public function getUserEnrollments(int $user_id, ?string $status = NULL): array
    {
        $properties = ['user_id' => $user_id];
        if ($status !== NULL) {
            $properties['status'] = $status;
        }

        return $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->loadByProperties($properties);
    }

    /**
     * Updates enrollment progress.
     *
     * @param int $enrollment_id
     *   The enrollment ID.
     *
     * @return float
     *   The calculated progress percentage.
     */
    public function recalculateProgress(int $enrollment_id): float
    {
        $enrollment = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->load($enrollment_id);

        if (!$enrollment) {
            return 0;
        }

        // Get progress from tracking service
        $progress = $this->progressService->calculateCourseProgress($enrollment);

        $enrollment->setProgressPercent($progress);
        $enrollment->set('last_activity_at', \Drupal::time()->getRequestTime());

        // Check if completed
        if ($progress >= 100 && !$enrollment->isCompleted()) {
            $enrollment->markCompleted();
            $this->logger->info('Enrollment @id completed', ['@id' => $enrollment_id]);

            // Dispatch completion event for certificate issuance.
            $completionEvent = new \Symfony\Component\EventDispatcher\GenericEvent($enrollment, [
                'type' => 'enrollment_completed',
                'user_id' => $enrollment->get('user_id')->target_id,
                'course_id' => $enrollment->get('course_id')->target_id,
                'completion_date' => date('Y-m-d\TH:i:s'),
            ]);
            $this->eventDispatcher->dispatch($completionEvent, 'jaraba_lms.enrollment.completed');

            // Send completion email.
            try {
                $user = $this->entityTypeManager->getStorage('user')->load($enrollment->get('user_id')->target_id);
                $course = $this->entityTypeManager->getStorage('lms_course')->load($enrollment->get('course_id')->target_id);
                if ($user && $course && $user->getEmail()) {
                    \Drupal::service('plugin.manager.mail')->mail('jaraba_lms', 'enrollment_completed', $user->getEmail(), $user->getPreferredLangcode(), [
                        'user_name' => $user->getDisplayName(),
                        'course_name' => $course->label(),
                        'course_id' => $enrollment->get('course_id')->target_id,
                    ]);
                }
            }
            catch (\Exception $e) {
                $this->logger->error('Failed to send completion email: @error', ['@error' => $e->getMessage()]);
            }
        }

        $enrollment->save();

        return $progress;
    }

    /**
     * Checks if user meets course prerequisites.
     *
     * @param int $user_id
     *   The user ID.
     * @param int $course_id
     *   The course ID.
     *
     * @return bool
     *   TRUE if prerequisites are met.
     */
    public function checkPrerequisites(int $user_id, int $course_id): bool
    {
        $course = $this->entityTypeManager
            ->getStorage('lms_course')
            ->load($course_id);

        if (!$course) {
            return FALSE;
        }

        $prerequisites = $course->getPrerequisites();
        if (empty($prerequisites)) {
            return TRUE;
        }

        // Check each prerequisite
        foreach ($prerequisites as $prereq_id) {
            $enrollment = $this->getEnrollment($user_id, $prereq_id);
            if (!$enrollment || !$enrollment->isCompleted()) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Sends reminders for abandoned courses.
     *
     * Called from cron. Sends emails to users who haven't accessed
     * their courses in 7+ days.
     */
    public function sendAbandonedCourseReminders(): void
    {
        $threshold = \Drupal::time()->getRequestTime() - (7 * 24 * 60 * 60);

        $query = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', Enrollment::STATUS_ACTIVE)
            ->condition('progress_percent', 100, '<')
            ->condition('last_activity_at', $threshold, '<')
            ->range(0, 50);

        $enrollment_ids = $query->execute();

        $enrollments = $this->entityTypeManager->getStorage('lms_enrollment')->loadMultiple($enrollment_ids);
        $mailManager = \Drupal::service('plugin.manager.mail');

        foreach ($enrollments as $enrollment) {
            try {
                $user = $this->entityTypeManager->getStorage('user')->load($enrollment->get('user_id')->target_id);
                $course = $this->entityTypeManager->getStorage('lms_course')->load($enrollment->get('course_id')->target_id);
                if ($user && $course && $user->getEmail()) {
                    $mailManager->mail('jaraba_lms', 'enrollment_reminder', $user->getEmail(), $user->getPreferredLangcode(), [
                        'user_name' => $user->getDisplayName(),
                        'course_name' => $course->label(),
                        'progress' => $enrollment->get('progress_percent')->value ?? 0,
                    ]);
                    $this->logger->debug('Queued reminder for enrollment @id', ['@id' => $enrollment->id()]);
                }
            }
            catch (\Exception $e) {
                $this->logger->error('Failed to send reminder for enrollment @id: @error', [
                    '@id' => $enrollment->id(),
                    '@error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Gets enrollment statistics for a course.
     *
     * @param int $course_id
     *   The course ID.
     *
     * @return array
     *   Statistics array.
     */
    public function getCourseStats(int $course_id): array
    {
        $storage = $this->entityTypeManager->getStorage('lms_enrollment');

        $total = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('course_id', $course_id)
            ->count()
            ->execute();

        $completed = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('course_id', $course_id)
            ->condition('status', Enrollment::STATUS_COMPLETED)
            ->count()
            ->execute();

        $active = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('course_id', $course_id)
            ->condition('status', Enrollment::STATUS_ACTIVE)
            ->count()
            ->execute();

        return [
            'total_enrollments' => (int) $total,
            'completed' => (int) $completed,
            'active' => (int) $active,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }

}
