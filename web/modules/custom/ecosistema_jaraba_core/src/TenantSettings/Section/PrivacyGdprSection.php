<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de privacidad y GDPR en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_privacy es modulo opcional.
 */
class PrivacyGdprSection extends AbstractTenantSettingsSection {

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
    return 'privacy_gdpr';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Privacidad y GDPR');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'shield-lock'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Politicas de privacidad, consentimiento y cumplimiento GDPR.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 55;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.privacy_policy.collection';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_privacy')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer privacy policies');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Configurar privacidad');
  }

}
