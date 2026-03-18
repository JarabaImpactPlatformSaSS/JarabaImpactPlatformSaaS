<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Completa tu perfil — Learner LMS Setup Wizard.
 *
 * Checks if the current user has filled profile interests.
 * User-scoped via uid.
 */
class LearnerPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'learner_lms.perfil';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'learner_lms';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Completa tu perfil');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Rellena tus intereses y nivel de formación');
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
    return 'entity.user.edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return ['user' => (int) $this->currentUser->id()];
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
    return $this->hasProfileWithInterests();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    if (!$this->hasProfileWithInterests()) {
      return [
        'count' => 0,
        'label' => $this->t('Perfil incompleto'),
      ];
    }

    return [
      'count' => 1,
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
   * Checks if the user has a profile with interests filled.
   */
  protected function hasProfileWithInterests(): bool {
    try {
      $userId = (int) $this->currentUser->id();
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user) {
        return FALSE;
      }

      // Check if user has field_interests or similar profile field filled.
      if ($user->hasField('field_interests') && !$user->get('field_interests')->isEmpty()) {
        return TRUE;
      }

      // Fallback: check if user has field_learning_level.
      if ($user->hasField('field_learning_level') && !$user->get('field_learning_level')->isEmpty()) {
        return TRUE;
      }

      return FALSE;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

}
