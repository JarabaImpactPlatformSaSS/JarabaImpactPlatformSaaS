<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: work on the Business Model Canvas.
 */
class EntrepreneurCanvasAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'entrepreneur_tools.canvas';
  }

  public function getDashboardId(): string {
    return 'entrepreneur_tools';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Mi Canvas');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Trabaja en tu modelo de negocio');
  }

  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'canvas', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'jaraba_copilot_v2.bmc_dashboard';
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
    return 10;
  }

  public function isPrimary(): bool {
    return TRUE;
  }

  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
