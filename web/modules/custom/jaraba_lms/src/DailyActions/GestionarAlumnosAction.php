<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: manage enrolled students.
 *
 * Badge shows count of active enrollments for the instructor's courses.
 * User-scoped: finds courses by author_id, then counts enrollments.
 */
class GestionarAlumnosAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'instructor_lms.alumnos';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'instructor_lms';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Gestionar alumnos');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisar inscripciones y progreso');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'users', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_lms.instructor.courses';
  }

  /**
   *
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   *
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   *
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return TRUE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    $active = 0;
    try {
      // Get instructor's course IDs.
      $courseIds = $this->entityTypeManager->getStorage('lms_course')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('author_id', (int) $this->currentUser->id())
        ->execute();

      if (!empty($courseIds) && $this->entityTypeManager->hasDefinition('lms_enrollment')) {
        $active = (int) $this->entityTypeManager->getStorage('lms_enrollment')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('course_id', $courseIds, 'IN')
          ->condition('status', 'active')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $active > 0 ? $active : NULL,
      'badge_type' => $active > 10 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
