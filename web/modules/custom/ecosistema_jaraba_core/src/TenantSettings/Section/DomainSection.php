<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de configuracion de dominio personalizado.
 */
class DomainSection extends AbstractTenantSettingsSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'domain';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Dominio');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'globe'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Configura tu dominio personalizado y SSL.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.domain';
  }

}
