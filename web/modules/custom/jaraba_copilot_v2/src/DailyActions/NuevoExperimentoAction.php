<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: design a new validation experiment.
 *
 * Opens in slide-panel for quick experiment creation.
 */
class NuevoExperimentoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'emprendedor.experimento';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'emprendedor';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevo experimento');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Diseña un nuevo experimento de validación');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'ai', 'name' => 'beaker', 'variant' => 'duotone'];
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
    return 'jaraba_copilot_v2.experiment_lifecycle';
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
    return TRUE;
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
