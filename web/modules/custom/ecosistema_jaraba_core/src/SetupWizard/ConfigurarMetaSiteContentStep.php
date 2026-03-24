<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Wizard step: Configurar contenido de los metasitios del ecosistema.
 *
 * METASITE-CONTENT-001: Verifica que al menos 2 de las 4 variantes
 * de metasitio tienen hero_headline configurado en theme settings.
 *
 * Wizard ID: __global__ (inyectado en todos los wizards admin).
 * Weight: 95 (después de ConfigurarPromocionesStep en 90).
 */
class ConfigurarMetaSiteContentStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.configurar_metasite_content';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Configurar contenido de metasitios');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Personaliza hero, estadísticas y CTA de cada dominio del ecosistema (PED, Pepe Jaraba, Jaraba Impact).');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 95;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'globe',
      'variant' => 'duotone',
    ];
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
  public function isComplete(int $tenantId): bool {
    $config = $this->configFactory->get('ecosistema_jaraba_theme.settings');
    $configured = 0;
    foreach (['generic', 'pde', 'jarabaimpact', 'pepejaraba'] as $variant) {
      $headline = $config->get("{$variant}_hero_headline");
      if ($headline !== NULL && $headline !== '') {
        $configured++;
      }
    }
    return $configured >= 2;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $config = $this->configFactory->get('ecosistema_jaraba_theme.settings');
    $configured = 0;
    foreach (['generic', 'pde', 'jarabaimpact', 'pepejaraba'] as $variant) {
      $val = $config->get("{$variant}_hero_headline");
      if ($val !== NULL && $val !== '') {
        $configured++;
      }
    }
    return [
      'label' => $this->t('@count/4 metasitios configurados', ['@count' => $configured]),
      'count' => $configured,
      'progress' => (int) ($configured / 4 * 100),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
