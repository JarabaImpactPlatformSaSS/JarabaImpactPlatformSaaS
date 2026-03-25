<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: chat with AI Copilot for job search assistance.
 *
 * Simple action without badge or slide-panel.
 */
class ChatCopilotCandidatoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'candidato_empleo.copilot';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'candidato_empleo';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Chat con Copilot');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Asistente de IA para tu búsqueda de empleo');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'ai', 'name' => 'sparkles', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_candidate.dashboard';
  }

  /**
   *
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   *
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   *
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 40;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
