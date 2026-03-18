<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Configurar alertas — Legal Professional Setup Wizard.
 *
 * Checks if the user has at least one active legal_alert.
 * User-scoped via provider_id + is_active filter.
 */
class LegalAlertasStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'legal_professional.alertas';
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
    return $this->t('Configurar alertas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Recibe notificaciones de nuevas resoluciones relevantes');
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
      'category' => 'status',
      'name' => 'bell',
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
    return $this->getActiveAlertCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getActiveAlertCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin alertas activas'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count alertas activas', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of active legal alerts for the current user.
   */
  protected function getActiveAlertCount(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_alert');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', (int) $this->currentUser->id())
        ->condition('is_active', TRUE)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
