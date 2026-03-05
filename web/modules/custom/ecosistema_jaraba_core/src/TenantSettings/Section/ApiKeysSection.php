<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de claves API.
 */
class ApiKeysSection extends AbstractTenantSettingsSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'api_keys';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Claves API');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'code'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Gestiona tus claves de acceso a la API.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 40;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.api_keys';
  }

}
