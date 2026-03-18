<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Primera leccion — Instructor LMS Setup Wizard.
 *
 * Checks if the user's courses have at least one lesson.
 * User-scoped: finds courses by author_id, then checks lessons via course_id.
 */
class InstructorLeccionStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'instructor_lms.leccion';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'instructor_lms';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Primera leccion');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Anade contenido a tu curso');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 20;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'education',
      'name' => 'clipboard-list',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_lms.instructor.courses';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getLessonCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getLessonCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin lecciones creadas'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count lecciones creadas', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of lessons for courses owned by the current user.
   */
  protected function getLessonCount(): int {
    try {
      // First, get the user's course IDs.
      $courseStorage = $this->entityTypeManager->getStorage('lms_course');
      $courseIds = $courseStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('author_id', (int) $this->currentUser->id())
        ->execute();

      if (empty($courseIds)) {
        return 0;
      }

      // Then count lessons belonging to those courses.
      $lessonStorage = $this->entityTypeManager->getStorage('lms_lesson');
      return (int) $lessonStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('course_id', $courseIds, 'IN')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
