<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Guardar resoluciones — Legal Professional Setup Wizard.
 *
 * Optional step. Checks if the user has at least one legal_bookmark.
 * User-scoped via user_id.
 */
class LegalFavoritosStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'legal_professional.favoritos';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'legal_professional';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Guardar resoluciones');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Marca resoluciones como favoritas para acceso rapido');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 30;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'actions',
      'name' => 'bookmark',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_legal.dashboard';
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
    return $this->getBookmarkCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getBookmarkCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin resoluciones guardadas'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count resoluciones guardadas', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

  /**
   * Gets the count of bookmarks for the current user.
   */
  protected function getBookmarkCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_bookmark');
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
