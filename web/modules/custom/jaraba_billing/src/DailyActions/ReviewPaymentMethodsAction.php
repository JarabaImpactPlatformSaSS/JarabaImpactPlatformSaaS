<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily Action: nudge para activar más métodos de pago.
 *
 * Siempre visible como recordatorio para marketplace tenants.
 * Los métodos de pago rápidos (Bizum, Apple Pay, Google Pay) mejoran
 * la conversión de checkout significativamente.
 *
 * SETUP-WIZARD-DAILY-001, PLG-UPGRADE-UI-001.
 */
class ReviewPaymentMethodsAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.review_payment_methods';
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
    return $this->t('Activa más métodos de pago');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Bizum, Apple Pay y Google Pay aumentan la conversión de checkout hasta un 30%.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'finance', 'name' => 'credit-card', 'variant' => 'duotone'];
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
    return 85;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    return ['visible' => TRUE, 'badge' => NULL, 'badge_type' => ''];
  }

}
