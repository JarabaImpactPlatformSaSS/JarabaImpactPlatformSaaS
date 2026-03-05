<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de personalizacion visual (colores, tipografia, layout).
 */
class DesignSection extends AbstractTenantSettingsSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'design';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Diseno Visual');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'layout-template'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Colores, tipografia, header, footer y componentes.');
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
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.design';
  }

}
