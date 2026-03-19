<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily Action: Hablar con el Copiloto IA (demo).
 *
 * S11-05: Acción secundaria que dirige al AI Playground.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 */
class ChatCopilotDemoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'demo_visitor.chat_copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'demo_visitor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Hablar con el Copiloto IA');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Pregúntale lo que necesites sobre tu negocio');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ai',
      'name' => 'chat',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'naranja-impulso';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.demo_ai_playground';
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
  public function getWeight(): int {
    return 20;
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
  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => '',
      'visible' => TRUE,
    ];
  }

}
