<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Publicar curso — Instructor LMS Setup Wizard.
 *
 * Checks if the user has at least one published course (is_published = TRUE).
 * User-scoped via author_id.
 */
class InstructorPublicarStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'instructor_lms.publicar';
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
    return $this->t('Publicar curso');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Haz visible tu curso para los alumnos');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 30;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'actions',
      'name' => 'rocket',
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
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getPublishedCourseCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getPublishedCourseCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin cursos publicados'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count cursos publicados', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of published courses owned by the current user.
   */
  protected function getPublishedCourseCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('lms_course');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('author_id', (int) $this->currentUser->id())
        ->condition('is_published', TRUE)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
