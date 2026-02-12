<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_lms\Entity\EnrollmentInterface;
use Drupal\jaraba_lms\Service\EnrollmentService;
use Drupal\jaraba_lms\Service\ProgressTrackingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for learner-facing LMS pages.
 */
class LearnerController extends ControllerBase
{

    /**
     * The enrollment service.
     */
    protected EnrollmentService $enrollmentService;

    /**
     * The progress service.
     */
    protected ProgressTrackingService $progressService;

    /**
     * Constructor.
     */
    public function __construct(EnrollmentService $enrollment_service, ProgressTrackingService $progress_service)
    {
        $this->enrollmentService = $enrollment_service;
        $this->progressService = $progress_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_lms.enrollment'),
            $container->get('jaraba_lms.progress')
        );
    }

    /**
     * Displays learner dashboard.
     */
    public function dashboard(): array
    {
        $user_id = (int) $this->currentUser()->id();
        $enrollments = $this->enrollmentService->getUserEnrollments($user_id);

        $active = [];
        $completed = [];

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->getCourse();
            $item = [
                'id' => $enrollment->id(),
                'course_id' => $enrollment->getCourseId(),
                'course_title' => $course ? $course->getTitle() : $this->t('(Course removed)'),
                'progress' => $enrollment->getProgressPercent(),
                'status' => $enrollment->getStatus(),
            ];

            if ($enrollment->isCompleted()) {
                $completed[] = $item;
            } else {
                $active[] = $item;
            }
        }

        return [
            '#theme' => 'lms_dashboard',
            '#active_courses' => $active,
            '#completed_courses' => $completed,
            '#stats' => $this->progressService->getUserLearningStats($user_id),
            '#attached' => [
                'library' => ['jaraba_lms/dashboard'],
            ],
        ];
    }

    /**
     * Displays course player.
     */
    public function player(EnrollmentInterface $lms_enrollment): array
    {
        $course = $lms_enrollment->getCourse();

        if (!$course) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        return [
            '#theme' => 'activity_player',
            '#course' => [
                'id' => $course->id(),
                'title' => $course->getTitle(),
            ],
            '#enrollment' => [
                'id' => $lms_enrollment->id(),
                'progress' => $lms_enrollment->getProgressPercent(),
            ],
            '#attached' => [
                'library' => ['jaraba_lms/player'],
            ],
        ];
    }

    /**
     * Title callback for player.
     */
    public function playerTitle(EnrollmentInterface $lms_enrollment): string
    {
        $course = $lms_enrollment->getCourse();
        return $course ? $course->getTitle() : $this->t('Course');
    }

    /**
     * Displays certificates.
     */
    public function certificates(): array
    {
        $user_id = (int) $this->currentUser()->id();
        $enrollments = $this->enrollmentService->getUserEnrollments($user_id, 'completed');

        $certificates = [];
        foreach ($enrollments as $enrollment) {
            if ($enrollment->isCertificateIssued()) {
                $course = $enrollment->getCourse();
                $certificates[] = [
                    'id' => $enrollment->id(),
                    'course_title' => $course ? $course->getTitle() : $this->t('(Course removed)'),
                    'completed_at' => $enrollment->getCompletedAt(),
                ];
            }
        }

        return [
            '#theme' => 'lms_certificates',
            '#certificates' => $certificates,
            '#attached' => [
                'library' => ['jaraba_lms/certificates'],
            ],
        ];
    }

}
