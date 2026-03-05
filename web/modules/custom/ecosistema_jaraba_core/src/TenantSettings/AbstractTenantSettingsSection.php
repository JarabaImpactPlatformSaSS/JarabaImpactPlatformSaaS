<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class con defaults para secciones de tenant settings.
 */
abstract class AbstractTenantSettingsSection implements TenantSettingsSectionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    return $this->currentUser->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
