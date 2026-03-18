<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Maturity diagnostic.
 *
 * Checks if the entrepreneur has a completed entrepreneur_profile
 * (indicating a diagnostic has been done).
 * User-scoped — filters by uid.
 */
class EmprendedorDiagnosticoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'emprendedor.diagnostico';
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
    return $this->t('Diagnóstico de madurez');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Evalúa el nivel de madurez de tu proyecto empresarial');
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
      'category' => 'analytics',
      'name' => 'chart-bar',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.lead_magnet.calculadora_madurez';
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

    return [
      'count' => $count,
      'label' => $count > 0
        ? $this->t('Diagnóstico completado')
        : $this->t('Pendiente de completar'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Checks if an entrepreneur_profile exists for the current user.
   *
   * The entrepreneur_profile entity is created after completing the
   * maturity diagnostic (DIME assessment).
   */
  protected function getDiagnosticCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('entrepreneur_profile')) {
        return 0;
      }
      $storage = $this->entityTypeManager->getStorage('entrepreneur_profile');
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
