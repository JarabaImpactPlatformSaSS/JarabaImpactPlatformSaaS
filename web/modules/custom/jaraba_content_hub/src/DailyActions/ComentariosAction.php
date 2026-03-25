<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: moderate reader comments.
 *
 * Badge shows count of comments in the last 7 days for the tenant.
 */
class ComentariosAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'editor_content_hub.comentarios';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'editor_content_hub';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Comentarios');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Modera los comentarios de lectores');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'social', 'name' => 'message-circle', 'variant' => 'duotone'];
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
    return 'jaraba_content_hub.dashboard.frontend';
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
    return 30;
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
    $recent = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('content_comment')) {
        $sevenDaysAgo = \Drupal::time()->getRequestTime() - (7 * 86400);
        $query = $this->entityTypeManager->getStorage('content_comment')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('created', $sevenDaysAgo, '>=')
          ->count();
        $recent = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $recent > 0 ? $recent : NULL,
      'badge_type' => $recent > 10 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
