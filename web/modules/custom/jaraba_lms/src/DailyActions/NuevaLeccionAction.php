<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: add a new lesson to a course.
 *
 * No badge — simple navigation to instructor courses.
 */
class NuevaLeccionAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'instructor_lms.nueva_leccion';
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
    return $this->t('Nueva leccion');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Anadir contenido a un curso');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'plus-circle', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'verde-innovacion';
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
    return 'large';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 20;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
