<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry;
use Drupal\jaraba_lms\Entity\EnrollmentInterface;
use Drupal\jaraba_lms\Service\EnrollmentService;
use Drupal\jaraba_lms\Service\ProgressTrackingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for learner-facing LMS pages.
 */
class LearnerController extends ControllerBase {

  /**
   * The enrollment service.
   */
  protected EnrollmentService $enrollmentService;

  /**
   * The progress service.
   */
  protected ProgressTrackingService $progressService;

  /**
   * The setup wizard registry.
   */
  protected ?SetupWizardRegistry $wizardRegistry;

  /**
   * The daily actions registry.
   */
  protected ?DailyActionsRegistry $dailyActionsRegistry;

  /**
   * Constructor.
   */
  public function __construct(
    EnrollmentService $enrollment_service,
    ProgressTrackingService $progress_service,
    ?SetupWizardRegistry $wizard_registry = NULL,
    ?DailyActionsRegistry $daily_actions_registry = NULL,
  ) {
    $this->enrollmentService = $enrollment_service;
    $this->progressService = $progress_service;
    $this->wizardRegistry = $wizard_registry;
    $this->dailyActionsRegistry = $daily_actions_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
          $container->get('jaraba_lms.enrollment'),
          $container->get('jaraba_lms.progress'),
          $container->has('ecosistema_jaraba_core.setup_wizard_registry')
              ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
          $container->has('ecosistema_jaraba_core.daily_actions_registry')
              ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
      );
  }

  /**
   * Displays learner dashboard.
   */
  public function dashboard(): array {
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
      }
      else {
        $active[] = $item;
      }
    }

    // SETUP-WIZARD-DAILY-001: Wizard + daily actions data.
    $setupWizard = NULL;
    $dailyActions = [];
    if ($this->wizardRegistry) {
      // Learner wizard is user-scoped; tenantId=0 as fallback.
      $tenantId = 0;
      $setupWizard = $this->wizardRegistry->hasWizard('learner_lms')
                ? $this->wizardRegistry->getStepsForWizard('learner_lms', $tenantId)
                : NULL;
      $dailyActions = $this->dailyActionsRegistry?->getActionsForDashboard('learner_lms', $tenantId) ?? [];
    }

    return [
      '#theme' => 'lms_dashboard',
      '#active_courses' => $active,
      '#completed_courses' => $completed,
      '#stats' => $this->progressService->getUserLearningStats($user_id),
      '#setup_wizard' => $setupWizard,
      '#daily_actions' => $dailyActions,
      '#attached' => [
        'library' => ['jaraba_lms/dashboard'],
      ],
    ];
  }

  /**
   * Displays course player.
   */
  public function player(EnrollmentInterface $lms_enrollment): array {
    $course = $lms_enrollment->getCourse();

    if (!$course) {
      throw new NotFoundHttpException();
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
  public function playerTitle(EnrollmentInterface $lms_enrollment): string {
    $course = $lms_enrollment->getCourse();
    return $course ? $course->getTitle() : $this->t('Course');
  }

  /**
   * Displays certificates.
   */
  public function certificates(): array {
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
