<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 5: Languages (optional).
 *
 * Checks if the candidate has at least one candidate_language entry.
 * User-scoped — filters by uid.
 */
class CandidatoIdiomasStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'candidato_empleo.idiomas';
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
    return $this->t('Idiomas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Indica los idiomas que dominas y tu nivel');
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
  public function getIcon(): array {
    return [
      'category' => 'general',
      'name' => 'globe',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_candidate.my_profile.languages';
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
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getLanguageCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getLanguageCount();

    return [
      'count' => $count,
      'label' => $count > 0
        ? $this->t('@count idioma(s)', ['@count' => $count])
        : $this->t('Sin idiomas añadidos'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

  /**
   * Gets the count of languages for the current user.
   */
  protected function getLanguageCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('candidate_language');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $this->currentUser->id())
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
