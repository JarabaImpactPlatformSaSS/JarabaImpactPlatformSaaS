<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Crear curso — Instructor LMS Setup Wizard.
 *
 * Checks if the current user (instructor) has created at least one lms_course.
 * User-scoped via author_id.
 */
class InstructorCursoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'instructor_lms.curso';
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
    return $this->t('Crear curso');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea tu primer curso de formacion');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'education',
      'name' => 'book-open',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.lms_course.add_form';
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
    return $this->getCourseCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getCourseCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin cursos creados'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count cursos creados', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of courses owned by the current user.
   */
  protected function getCourseCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('lms_course');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('author_id', (int) $this->currentUser->id())
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
