<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de configuracion del Copiloto IA en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_copilot_v2 es modulo opcional.
 */
class CopilotConfigSection extends AbstractTenantSettingsSection {

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
    return 'copilot_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Copiloto IA');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ai', 'name' => 'sparkles'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Configura el asistente inteligente y sus capacidades.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 70;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_copilot_v2.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_copilot_v2')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer copilot');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
