<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de webhooks.
 */
class WebhooksSection extends AbstractTenantSettingsSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'webhooks';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Webhooks');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'webhook'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Configura notificaciones automaticas a sistemas externos.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 50;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.webhooks';
  }

}
