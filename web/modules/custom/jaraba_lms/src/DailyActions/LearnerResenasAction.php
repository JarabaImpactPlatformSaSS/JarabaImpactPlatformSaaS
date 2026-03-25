<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: leave a course review.
 *
 * Links to the learner dashboard for reviewing completed courses.
 */
class LearnerResenasAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'learner_lms.resenas';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'learner_lms';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Dejar reseña');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Valora los cursos que has completado');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'communication', 'name' => 'star', 'variant' => 'duotone'];
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
    return 'jaraba_lms.my_learning';
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
    return 40;
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
