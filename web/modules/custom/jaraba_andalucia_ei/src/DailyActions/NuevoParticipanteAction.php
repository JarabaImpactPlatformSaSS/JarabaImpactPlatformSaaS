<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: register a new program participant.
 *
 * Opens in slide-panel (large) for quick registration without leaving dashboard.
 */
class NuevoParticipanteAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'coordinador_ei.participante';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'coordinador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevo participante');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Registrar un nuevo participante en el programa');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'user-plus', 'variant' => 'duotone'];
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
    return 'entity.programa_participante_ei.add_form';
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
