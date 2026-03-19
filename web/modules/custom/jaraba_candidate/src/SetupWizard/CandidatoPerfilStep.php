<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Complete candidate profile.
 *
 * Checks if the candidate has a profile with filled name field.
 * User-scoped (NOT tenant-scoped) — filters by uid.
 */
class CandidatoPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'candidato_empleo.perfil';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'candidato_empleo';
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
    return $this->t('Rellena tus datos personales, contacto y ubicación');
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
    return 'jaraba_candidate.my_profile.edit';
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getProfileWithNameCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getFilledFieldsCount();

    if ($this->getProfileWithNameCount() === 0) {
      return [
        'count' => $count,
        'label' => $this->t('Faltan datos'),
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
   * Checks if a candidate_profile with filled name exists for current user.
   */
  protected function getProfileWithNameCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('candidate_profile');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', (int) $this->currentUser->id())
        ->condition('name', '', '<>')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Counts filled fields in the user's profile (approximate).
   */
  protected function getFilledFieldsCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('candidate_profile');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', (int) $this->currentUser->id())
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return 0;
      }

      $profile = $storage->load(reset($ids));
      if (!$profile) {
        return 0;
      }

      $count = 0;
      $fieldsToCheck = ['name', 'email', 'phone', 'location', 'headline', 'summary'];
      foreach ($fieldsToCheck as $field) {
        if ($profile->hasField($field) && !$profile->get($field)->isEmpty()) {
          $count++;
        }
      }

      return $count;
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
