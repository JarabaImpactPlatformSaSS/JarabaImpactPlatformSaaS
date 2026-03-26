<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de Andalucia +ei en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_andalucia_ei es modulo opcional.
 */
class AndaluciaEiConfigSection extends AbstractTenantSettingsSection {

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
    return 'andalucia_ei_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Andalucia +ei');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'verticals', 'name' => 'andalucia-ei'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Configuracion del programa Andalucia Emprende e Innova.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 75;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_andalucia_ei')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer andalucia ei');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
