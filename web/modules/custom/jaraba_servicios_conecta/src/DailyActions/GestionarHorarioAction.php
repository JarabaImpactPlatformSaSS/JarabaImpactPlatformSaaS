<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: manage availability time slots.
 *
 * Opens availability slot form for schedule management.
 */
class GestionarHorarioAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'provider_servicios.horario';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'provider_servicios';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Gestionar horario');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Configurar franjas horarias disponibles');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'];
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
    return 'entity.availability_slot.add_form';
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
    return 30;
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
