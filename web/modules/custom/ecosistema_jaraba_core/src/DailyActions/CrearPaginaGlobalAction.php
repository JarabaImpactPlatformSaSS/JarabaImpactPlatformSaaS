<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily Action global: Crear pagina web.
 *
 * Accion cross-vertical inyectada en TODOS los dashboards via __global__.
 * Visible solo si jaraba_page_builder esta instalado (resolveRoute guard).
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * __global__: Aparece en perfil de usuario de cualquier avatar.
 */
class CrearPaginaGlobalAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.crear_pagina';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return DailyActionsRegistry::GLOBAL_DASHBOARD_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Crear pagina web');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea una landing page profesional con el editor visual');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'layout-template',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'verde-innovacion';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_page_builder.dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 80;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    // Solo visible si el modulo Page Builder esta instalado.
    $visible = \Drupal::moduleHandler()->moduleExists('jaraba_page_builder');

    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => $visible,
    ];
  }

}
