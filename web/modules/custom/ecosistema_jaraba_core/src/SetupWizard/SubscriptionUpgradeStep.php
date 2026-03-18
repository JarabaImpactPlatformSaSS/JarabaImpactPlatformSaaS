<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Setup Wizard step: sugiere elegir un plan de suscripción.
 *
 * Se registra como __global__ para inyectarse en TODOS los wizards.
 * Se marca como "complete" cuando el usuario tiene un plan paid.
 * Efecto Zeigarnik: junto con los 2 auto-complete steps existentes
 * (Account + Vertical), este paso aumenta el progreso inicial a ~50%.
 *
 * SETUP-WIZARD-DAILY-001, PLG-UPGRADE-UI-001, ZEIGARNIK-PRELOAD-001.
 */
class SubscriptionUpgradeStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * Constructs a SubscriptionUpgradeStep.
   */
  public function __construct(
    protected ?object $subscriptionContext,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.suscripcion_activa';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Elige tu plan');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Desbloquea todas las funcionalidades de tu vertical con un plan profesional.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'credit-card', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.pricing.page';
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
  public function getWeight(): int {
    return 90;
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    if ($this->subscriptionContext === NULL || !method_exists($this->subscriptionContext, 'getContextForUser')) {
      return FALSE;
    }

    try {
      $uid = $this->currentUser->id();
      $context = $this->subscriptionContext->getContextForUser($uid);
      if (!isset($context['plan']) || $context['plan'] === NULL) {
        return FALSE;
      }
      // Complete if user has any paid plan (not free).
      return !($context['plan']['is_free'] ?? TRUE);
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
