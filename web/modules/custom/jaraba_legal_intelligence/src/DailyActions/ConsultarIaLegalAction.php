<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: consult AI legal assistant.
 *
 * No badge — simple navigation action.
 */
class ConsultarIaLegalAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'legal_professional.ia_legal';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'legal_professional';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Consultar IA legal');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Asistente de inteligencia juridica');
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
    return 'jaraba_legal.dashboard';
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
