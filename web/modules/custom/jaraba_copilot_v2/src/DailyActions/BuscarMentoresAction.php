<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: find specialized mentors.
 *
 * Simple action without badge or slide-panel.
 */
class BuscarMentoresAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'emprendedor.mentores';
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
    return $this->t('Buscar mentores');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Conecta con mentores especializados');
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
    return 'jaraba_copilot_v2.dashboard';
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
