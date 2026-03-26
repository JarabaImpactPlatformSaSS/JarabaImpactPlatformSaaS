<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de Page Builder en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_page_builder es modulo opcional.
 */
class PageBuilderConfigSection extends AbstractTenantSettingsSection {

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
    return 'page_builder_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Page Builder');
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
    return (string) $this->t('Configuracion del editor visual de paginas.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 35;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_page_builder.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_page_builder')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer page builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
