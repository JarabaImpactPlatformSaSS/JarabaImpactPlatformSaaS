<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily action: Revisar contenido de metasitios del ecosistema.
 *
 * METASITE-CONTENT-001: Muestra badge warning si algún metasitio
 * tiene el hero_headline vacío. Solo visible para administradores.
 *
 * Dashboard ID: __global__ (visible en todos los dashboards admin).
 */
class RevisarContenidoMetaSitesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.revisar_contenido_metasites';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Revisar contenido de metasitios');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Verifica que los 4 dominios del ecosistema tienen hero, estadísticas y CTA final configurados.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'marketing',
      'name' => 'palette',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'system.theme_settings_theme';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return ['theme' => 'ecosistema_jaraba_theme'];
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
    return 85;
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
    // Solo visible para administradores de la plataforma.
    if (!$this->currentUser->hasPermission('administer themes')) {
      return ['badge' => NULL, 'badge_type' => 'info', 'visible' => FALSE];
    }

    $config = $this->configFactory->get('ecosistema_jaraba_theme.settings');
    $empty = 0;
    foreach (['generic', 'pde', 'jarabaimpact', 'pepejaraba'] as $variant) {
      $val = $config->get("{$variant}_hero_headline");
      if ($val === NULL || $val === '') {
        $empty++;
      }
    }

    return [
      'badge' => $empty > 0 ? $empty : NULL,
      'badge_type' => $empty > 0 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
