<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: validate business hypotheses.
 *
 * Simple action without badge or slide-panel.
 */
class ValidarHipotesisAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'emprendedor.validar';
  }

  public function getDashboardId(): string {
    return 'emprendedor';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Validar hipótesis');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisa y valida tus hipótesis de negocio');
  }

  public function getIcon(): array {
    return ['category' => 'ai', 'name' => 'lightbulb', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'jaraba_copilot_v2.hypothesis_manager';
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
