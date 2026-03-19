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
   *
   * Label contextual: si ya tiene plan paid → "Mi suscripcion".
   * Si no tiene plan → "Elige tu plan" (call-to-action).
   */
  public function getLabel(): TranslatableMarkup {
    if ($this->hasPaidPlan()) {
      return $this->t('Mi suscripción');
    }
    return $this->t('Elige tu plan');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    if ($this->hasPaidPlan()) {
      return $this->t('Gestiona tu plan de suscripción y facturación.');
    }
    return $this->t('Desbloquea todas las funcionalidades de tu vertical con un plan profesional.');
  }

  /**
   * {@inheritdoc}
   *
   * Usa icono 'plan-upgrade' en finance/ — distingue visualmente del paso
   * de métodos de pago que usa credit-card. Elimina la chincheta (📌)
   * causada por ui/credit-card.svg inexistente (ICON-CONVENTION-001).
   */
  public function getIcon(): array {
    return ['category' => 'finance', 'name' => 'plan-upgrade', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   *
   * Ruta contextual segun estado de suscripcion:
   * - Con plan paid → /tenant/change-plan (comparar planes, upgrade/downgrade).
   * - Sin plan → /planes (elegir plan, comparar, checkout).
   */
  public function getRoute(): string {
    if ($this->hasPaidPlan()) {
      return 'ecosistema_jaraba_core.tenant.change_plan';
    }
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
   *
   * Pricing page necesita full-page experience (comparador de planes,
   * checkout embebido, tabla de features). No es apropiado para slide-panel.
   */
  public function useSlidePanel(): bool {
    return FALSE;
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
  public function getWeight(): int {
    return 90;
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->hasPaidPlan();
  }

  /**
   * Determina si el usuario actual tiene un plan de pago (no gratuito).
   *
   * Reutilizado por isComplete(), getLabel(), getRoute(), getCompletionData().
   * PRESAVE-RESILIENCE-001: try-catch con graceful degradation.
   */
  protected function hasPaidPlan(): bool {
    if ($this->subscriptionContext === NULL || !method_exists($this->subscriptionContext, 'getContextForUser')) {
      return FALSE;
    }

    try {
      $uid = (int) $this->currentUser->id();
      $context = $this->subscriptionContext->getContextForUser($uid);
      if (!isset($context['plan']) || $context['plan'] === NULL) {
        return FALSE;
      }
      return !($context['plan']['is_free'] ?? TRUE);
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Muestra el nombre del plan actual si está disponible, para coherencia
   * con el paso 2 (vertical configurado) que indica CUÁL vertical.
   */
  public function getCompletionData(int $tenantId): array {
    if ($this->subscriptionContext === NULL || !method_exists($this->subscriptionContext, 'getContextForUser')) {
      return [];
    }

    try {
      $uid = (int) $this->currentUser->id();
      $context = $this->subscriptionContext->getContextForUser($uid);
      if (isset($context['plan']) && $context['plan'] !== NULL) {
        $planName = $context['plan']['name'] ?? '';
        $isFree = $context['plan']['is_free'] ?? TRUE;
        if (!$isFree && $planName) {
          return ['label' => $this->t('Plan @plan activo', ['@plan' => $planName])];
        }
        if ($isFree) {
          return ['label' => $this->t('Plan gratuito')];
        }
      }
    }
    catch (\Throwable) {
      // Graceful degradation.
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
