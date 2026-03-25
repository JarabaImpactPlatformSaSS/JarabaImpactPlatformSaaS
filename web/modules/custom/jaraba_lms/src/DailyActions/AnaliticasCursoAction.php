<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: view course analytics and engagement metrics.
 *
 * No badge — simple navigation action.
 */
class AnaliticasCursoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'instructor_lms.analiticas';
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
    return $this->t('Analiticas curso');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Metricas de rendimiento y engagement');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart-line', 'variant' => 'duotone'];
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
