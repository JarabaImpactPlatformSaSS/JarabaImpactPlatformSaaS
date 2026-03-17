<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Complete entrepreneur profile.
 *
 * User-scoped — checks if the user has a profile or started a canvas.
 */
class EntrepreneurPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'entrepreneur_tools.perfil';
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
    return $this->t('Perfil emprendedor');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Completa tu perfil con tu idea de negocio');
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
      'category' => 'business',
      'name' => 'briefcase',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.settings';
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
        'label' => $this->t('Sin perfil emprendedor'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('Perfil activo'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Checks if the user has started a canvas (proxy for profile activity).
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
