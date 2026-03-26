<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de facturacion avanzada en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_billing es modulo opcional.
 */
class BillingConfigSection extends AbstractTenantSettingsSection {

  public function __construct(
    AccountProxyInterface $currentUser,
    protected ?ModuleHandlerInterface $moduleHandler = NULL,
  ) {
    parent::__construct($currentUser);
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'billing_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Facturacion avanzada');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'finance', 'name' => 'sliders'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Dashboard de ingresos, facturas y configuracion de pagos.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 15;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_billing.revenue_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_billing')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer billing');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
