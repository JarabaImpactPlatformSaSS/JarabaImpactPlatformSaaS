<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: normative alerts with badge for unread alerts.
 *
 * Badge shows count of active alerts that have been triggered recently.
 * User-scoped via provider_id.
 */
class AlertasNormativasLegalAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'legal_professional.alertas_diarias';
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
    return $this->t('Alertas normativas');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Nuevas resoluciones que coinciden con tus alertas');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'status', 'name' => 'bell', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'naranja-impulso';
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
    return 20;
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
    $unread = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('legal_alert')) {
        $query = $this->entityTypeManager->getStorage('legal_alert')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('provider_id', (int) $this->currentUser->id())
          ->condition('is_active', TRUE)
          ->condition('last_triggered', \Drupal::time()->getRequestTime() - 86400, '>=')
          ->count();
        $unread = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $unread > 0 ? $unread : NULL,
      'badge_type' => $unread > 5 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
