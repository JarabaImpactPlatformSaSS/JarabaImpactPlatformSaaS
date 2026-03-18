<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily Action condicional: sugiere revisar suscripción y add-ons.
 *
 * Aparece condicionalmente cuando:
 * - El usuario NO es Enterprise (ya tiene todo)
 * - Uso > 60% de algún límite O 30+ días sin revisión
 *
 * SETUP-WIZARD-DAILY-001, PLG-UPGRADE-UI-001.
 */
class ReviewSubscriptionAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * Constructs a ReviewSubscriptionAction.
   */
  public function __construct(
    protected ?object $subscriptionContext,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.review_subscription';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Explora add-ons para tu negocio');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Amplifica tu plataforma con funcionalidades premium, marketing IA y herramientas avanzadas.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'tag', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'impulse';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_addons.catalog';
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
  public function getHrefOverride(): ?string {
    return NULL;
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
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 80;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    if ($this->subscriptionContext === NULL || !method_exists($this->subscriptionContext, 'getContextForUser')) {
      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }

    try {
      $uid = $this->currentUser->id();
      $context = $this->subscriptionContext->getContextForUser($uid);
      if (!isset($context['plan']) || $context['plan'] === NULL) {
        return ['visible' => TRUE, 'badge' => NULL, 'badge_type' => ''];
      }

      // Hide for Enterprise users (already maximized).
      if (($context['plan']['tier'] ?? '') === 'enterprise') {
        return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
      }

      // Show if any usage bar > 60%.
      foreach ($context['usage'] ?? [] as $item) {
        if (($item['percentage'] ?? 0) >= 60) {
          return ['visible' => TRUE, 'badge' => NULL, 'badge_type' => ''];
        }
      }

      // Show if user has no addons and there are recommendations.
      $activeAddons = $context['addons']['active'] ?? [];
      $recommendedAddons = $context['addons']['recommended'] ?? [];
      if ($activeAddons === [] && $recommendedAddons !== []) {
        return ['visible' => TRUE, 'badge' => NULL, 'badge_type' => ''];
      }

      // Default: show for free/starter users.
      $tier = $context['plan']['tier'] ?? 'free';
      if (in_array($tier, ['free', 'starter'], TRUE)) {
        return ['visible' => TRUE, 'badge' => NULL, 'badge_type' => ''];
      }

      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }
    catch (\Throwable) {
      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }
  }

}
