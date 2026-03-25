<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: view mentoring impact statistics.
 */
class MentorEstadisticasAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'mentor.estadisticas';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'mentor';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Estadísticas');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Métricas de impacto de tu mentoría');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart-bar', 'variant' => 'duotone'];
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
    return 'jaraba_mentoring.mentor_dashboard';
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
