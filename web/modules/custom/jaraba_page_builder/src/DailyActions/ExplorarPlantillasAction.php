<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily Action: Explorar plantillas.
 *
 * Accion estatica que lleva al marketplace de plantillas.
 * Siempre visible — no requiere estado del tenant.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 */
class ExplorarPlantillasAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.explorar_plantillas';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'page_builder';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Explorar plantillas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Mas de 40 bloques profesionales listos para usar');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'palette',
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
    return 'jaraba_page_builder.template_picker';
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
    return 30;
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
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
