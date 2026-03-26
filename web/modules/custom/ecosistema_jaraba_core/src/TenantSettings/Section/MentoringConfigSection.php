<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de administracion de mentoria en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_mentoring es modulo opcional.
 */
class MentoringConfigSection extends AbstractTenantSettingsSection {

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
    return 'mentoring_config';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Administracion de Mentoria');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'users'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Configura sesiones de mentoria, mentores y programas.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 62;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_mentoring.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_mentoring')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer mentoring');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
