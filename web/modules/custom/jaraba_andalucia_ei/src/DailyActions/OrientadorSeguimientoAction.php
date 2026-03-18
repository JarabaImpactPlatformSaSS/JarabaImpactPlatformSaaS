<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: participant follow-up for orientador.
 *
 * Links to the orientador dashboard participants section.
 */
class OrientadorSeguimientoAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'orientador_ei.seguimiento';
  }

  public function getDashboardId(): string {
    return 'orientador_ei';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Seguimiento participantes');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisar el progreso de tus participantes');
  }

  public function getIcon(): array {
    return ['category' => 'charts', 'name' => 'chart-line', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'azul-corporativo';
  }

  public function getRoute(): string {
    return 'jaraba_andalucia_ei.orientador_dashboard';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return '#participants-title';
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 30;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
