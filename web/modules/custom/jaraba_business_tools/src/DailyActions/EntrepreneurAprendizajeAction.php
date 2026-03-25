<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: continue learning path.
 */
class EntrepreneurAprendizajeAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'entrepreneur_tools.aprendizaje';
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
    return $this->t('Ruta de aprendizaje');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Continúa tu formación emprendedora');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'book', 'variant' => 'duotone'];
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
