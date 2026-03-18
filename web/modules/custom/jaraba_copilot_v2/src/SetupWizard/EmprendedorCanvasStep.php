<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Business Model Canvas.
 *
 * Checks if the user has at least one copilot_conversation entity,
 * indicating canvas work has started.
 * User-scoped — filters by uid.
 */
class EmprendedorCanvasStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'emprendedor.canvas';
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
    return $this->t('Business Model Canvas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Define tu modelo de negocio con el canvas interactivo');
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
    return $this->getConversationCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getConversationCount();

    return [
      'count' => $count,
      'label' => $count > 0
        ? $this->t('Canvas en progreso')
        : $this->t('Sin iniciar'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of copilot conversations for the current user.
   */
  protected function getConversationCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('copilot_conversation')) {
        return 0;
      }
      $storage = $this->entityTypeManager->getStorage('copilot_conversation');
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
