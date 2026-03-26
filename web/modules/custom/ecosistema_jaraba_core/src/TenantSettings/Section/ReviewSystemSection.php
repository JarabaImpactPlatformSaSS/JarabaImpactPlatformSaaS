<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de sistema de resenas en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_reviews es modulo opcional.
 */
class ReviewSystemSection extends AbstractTenantSettingsSection {

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
    return 'review_system';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Sistema de Resenas');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'star'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Gestiona resenas, valoraciones y social proof.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 72;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.review.collection';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_reviews')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer reviews');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
