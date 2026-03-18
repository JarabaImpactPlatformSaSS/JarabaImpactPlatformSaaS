<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Perfil de orientador — Orientador Setup Wizard.
 *
 * Checks if the current user has a mentor_profile entity.
 * User-scoped via user_id.
 */
class OrientadorPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'orientador_ei.perfil';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'orientador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Perfil de orientador');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Completa tu perfil profesional de orientación');
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
  public function getIcon(): array {
    return [
      'category' => 'users',
      'name' => 'user-edit',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.orientador_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getProfileCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getProfileCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin perfil de orientador'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('Perfil completado'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of mentor profiles for the current user.
   */
  protected function getProfileCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
        return 0;
      }
      $storage = $this->entityTypeManager->getStorage('mentor_profile');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', (int) $this->currentUser->id())
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
