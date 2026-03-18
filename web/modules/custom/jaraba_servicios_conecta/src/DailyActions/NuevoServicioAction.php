<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: publish a new service offering.
 *
 * Opens in slide-panel (large) for quick service creation.
 */
class NuevoServicioAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'provider_servicios.servicio';
  }

  public function getDashboardId(): string {
    return 'provider_servicios';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevo servicio');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Publicar un nuevo servicio profesional');
  }

  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'briefcase', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'verde-innovacion';
  }

  public function getRoute(): string {
    return 'entity.service_offering.add_form';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return TRUE;
  }

  public function getSlidePanelSize(): string {
    return 'large';
  }

  public function getWeight(): int {
    return 20;
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
