<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Complete business diagnostic.
 *
 * User-scoped — checks if the user has a business_diagnostic entity.
 */
class EntrepreneurDiagnosticoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'entrepreneur_tools.diagnostico';
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
    return $this->t('Diagnóstico empresarial');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Evalúa la madurez de tu proyecto');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 20;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'analytics',
      'name' => 'chart-bar',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_diagnostic.wizard.start';
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
    return $this->getDiagnosticCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getDiagnosticCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin diagnóstico'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('Diagnóstico completado'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Counts business diagnostics for the current user.
   */
  protected function getDiagnosticCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('business_diagnostic')) {
        return 0;
      }
      $storage = $this->entityTypeManager->getStorage('business_diagnostic');
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
