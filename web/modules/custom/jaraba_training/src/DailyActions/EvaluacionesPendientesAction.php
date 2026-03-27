<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: Evaluaciones de portfolio pendientes.
 */
class EvaluacionesPendientesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getId(): string {
    return 'certificacion.evaluaciones_pendientes';
  }

  public function getDashboardId(): string {
    return '__global__';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Evaluaciones pendientes');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Portfolios de certificación esperando evaluación por un formador certificado.');
  }

  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'certificate', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'entity.user_certification.collection';
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
    return 'large';
  }

  public function getWeight(): int {
    return 30;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    try {
      $count = $this->entityTypeManager->getStorage('user_certification')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('certification_status', 'in_progress')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      $count = 0;
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 0 ? 'warning' : 'default',
      'visible' => TRUE,
    ];
  }

}
