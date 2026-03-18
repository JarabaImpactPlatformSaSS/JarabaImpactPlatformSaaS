<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: search legal jurisprudence.
 *
 * No badge — always visible as the main entry point.
 */
class BuscarJurisprudenciaAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'legal_professional.buscar';
  }

  public function getDashboardId(): string {
    return 'legal_professional';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Buscar jurisprudencia');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Buscar resoluciones judiciales');
  }

  public function getIcon(): array {
    return ['category' => 'legal', 'name' => 'search', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'azul-corporativo';
  }

  public function getRoute(): string {
    return 'jaraba_legal.dashboard';
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
