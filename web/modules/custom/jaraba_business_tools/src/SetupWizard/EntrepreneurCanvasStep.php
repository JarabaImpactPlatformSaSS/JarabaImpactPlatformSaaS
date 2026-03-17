<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Create Business Model Canvas.
 *
 * User-scoped — checks if the user has a business_model_canvas entity.
 */
class EntrepreneurCanvasStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'entrepreneur_tools.canvas';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'entrepreneur_tools';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Business Model Canvas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Define tu modelo de negocio');
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
      'category' => 'business',
      'name' => 'grid',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_copilot_v2.bmc_dashboard';
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
    return $this->getCanvasCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getCanvasCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin canvas'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count canvas creado(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Counts business model canvases for the current user.
   */
  protected function getCanvasCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('business_model_canvas')) {
        return 0;
      }
      $storage = $this->entityTypeManager->getStorage('business_model_canvas');
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
