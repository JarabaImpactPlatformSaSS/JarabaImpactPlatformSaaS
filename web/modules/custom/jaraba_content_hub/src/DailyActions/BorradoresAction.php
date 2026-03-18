<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: manage draft articles pending publication.
 *
 * Badge shows count of draft articles for the tenant.
 */
class BorradoresAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getId(): string {
    return 'editor_content_hub.borradores';
  }

  public function getDashboardId(): string {
    return 'editor_content_hub';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Borradores');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Articulos pendientes de publicar');
  }

  public function getIcon(): array {
    return ['category' => 'content', 'name' => 'file-text', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'jaraba_content_hub.dashboard.frontend';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 20;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    $drafts = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('content_article')) {
        $query = $this->entityTypeManager->getStorage('content_article')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('status', 'draft')
          ->count();
        $drafts = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $drafts > 0 ? $drafts : NULL,
      'badge_type' => $drafts > 5 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
