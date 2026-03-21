<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\DailyActions;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: Revisar leads generados por el copilot.
 *
 * Dashboard ID: __global__ (visible en todos los dashboards admin).
 * Visible solo si hay eventos copilot_intent_detected en ultimas 24h.
 */
class RevisarLeadsCopilotAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.revisar_leads_copilot';
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
    return $this->t('Revisar leads del copilot');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Hay visitantes que han expresado interés en la plataforma a través del copilot IA.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ai',
      'name' => 'copilot',
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
    return 'entity.crm_contact.collection';
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
    return 30;
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
    $count = $this->getRecentIntentCount();
    return [
      'visible' => $count > 0,
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => 'warning',
    ];
  }

  /**
   * Cuenta eventos de intencion detectada en ultimas 24h.
   */
  protected function getRecentIntentCount(): int {
    try {
      $since = \Drupal::time()->getRequestTime() - 86400;
      $count = $this->database->select('copilot_funnel_event', 'e')
        ->condition('event_type', 'copilot_intent_detected')
        ->condition('created', $since, '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
      return (int) $count;
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
