<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Define hypotheses.
 *
 * Checks if the user has at least one hypothesis entity.
 * User-scoped — filters by uid.
 */
class EmprendedorHipotesisStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'emprendedor.hipotesis';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'emprendedor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Define hipótesis');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Formula las hipótesis clave de tu modelo de negocio');
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
      'category' => 'ai',
      'name' => 'lightbulb',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_copilot_v2.hypothesis_manager';
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
    return $this->getHypothesisCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getHypothesisCount();

    return [
      'count' => $count,
      'label' => $count > 0
        ? $this->t('@count hipótesis', ['@count' => $count])
        : $this->t('Sin hipótesis definidas'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of hypotheses for the current user.
   */
  protected function getHypothesisCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('hypothesis')) {
        return 0;
      }
      $storage = $this->entityTypeManager->getStorage('hypothesis');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', (int) $this->currentUser->id())
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
