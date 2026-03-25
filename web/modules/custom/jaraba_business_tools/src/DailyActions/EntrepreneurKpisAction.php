<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: review KPI progress metrics.
 */
class EntrepreneurKpisAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'entrepreneur_tools.kpis';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'entrepreneur_tools';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Mis KPIs');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisa tus métricas de progreso');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'gauge', 'variant' => 'duotone'];
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
    return 'jaraba_business_tools.entrepreneur_dashboard';
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
    // Ancla a la sección de KPIs del dashboard.
    return '/es/entrepreneur/dashboard#kpis';
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
