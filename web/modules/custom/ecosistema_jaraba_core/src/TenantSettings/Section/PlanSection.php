<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de plan y facturacion.
 */
class PlanSection extends AbstractTenantSettingsSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'plan';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Plan y Facturacion');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'wallet'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Gestiona tu plan, uso y metodos de pago.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.plan';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

}
