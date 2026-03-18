<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Areas de practica — Legal Professional Setup Wizard.
 *
 * Checks if the user has created at least one legal_alert (implies configured
 * areas of practice). User-scoped via provider_id.
 */
class LegalAreasStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'legal_professional.areas';
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
    return $this->t('Areas de practica');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Selecciona tus areas juridicas de especializacion');
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
      'category' => 'legal',
      'name' => 'scales',
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
    return $this->getAlertCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getAlertCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin alertas configuradas'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count alertas configuradas', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of legal alerts for the current user.
   */
  protected function getAlertCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', (int) $this->currentUser->id())
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
