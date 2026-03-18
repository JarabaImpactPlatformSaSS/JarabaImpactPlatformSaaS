<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: view course reviews from students.
 *
 * Badge shows count of reviews in the last 7 days for the instructor's courses.
 * User-scoped: finds courses by author_id, then counts recent reviews.
 */
class VerResenasLmsAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return 'instructor_lms.resenas';
  }

  public function getDashboardId(): string {
    return 'instructor_lms';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Ver resenas');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Opiniones de tus alumnos');
  }

  public function getIcon(): array {
    return ['category' => 'social', 'name' => 'star', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'jaraba_lms.instructor.courses';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 30;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    $recent = 0;
    try {
      // Get instructor's course IDs.
      $courseIds = $this->entityTypeManager->getStorage('lms_course')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('author_id', (int) $this->currentUser->id())
        ->execute();

      if (!empty($courseIds) && $this->entityTypeManager->hasDefinition('course_review')) {
        $sevenDaysAgo = \Drupal::time()->getRequestTime() - (7 * 86400);
        $recent = (int) $this->entityTypeManager->getStorage('course_review')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('course_id', $courseIds, 'IN')
          ->condition('created', $sevenDaysAgo, '>=')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $recent > 0 ? $recent : NULL,
      'badge_type' => $recent > 5 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
