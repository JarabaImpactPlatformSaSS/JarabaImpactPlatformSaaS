<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings\Section;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\TenantSettings\AbstractTenantSettingsSection;

/**
 * Seccion de contenido interactivo en tenant settings hub.
 *
 * OPTIONAL-CROSSMODULE-001: jaraba_interactive_content es modulo opcional.
 */
class InteractiveContentSection extends AbstractTenantSettingsSection {

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
    return 'interactive_content';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->t('Contenido Interactivo');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'lightbulb'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->t('Quizzes, encuestas y contenido dinamico para engagement.');
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
    return 'entity.interactive_content.collection';
  }

  /**
   * {@inheritdoc}
   */
  public function isAccessible(): bool {
    if ($this->moduleHandler && !$this->moduleHandler->moduleExists('jaraba_interactive_content')) {
      return FALSE;
    }
    return $this->currentUser->hasPermission('administer interactive content');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->t('Disponible');
  }

}
