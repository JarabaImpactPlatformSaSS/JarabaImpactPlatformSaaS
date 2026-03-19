<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Daily Action: Rendimiento de paginas.
 *
 * Muestra badge con numero de paginas publicadas.
 * Solo visible si el tenant tiene al menos una pagina publicada.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * TENANT-001: Filtra por tenant_id.
 */
class RendimientoPaginasAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.rendimiento';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'page_builder';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Rendimiento de paginas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Visitas, tiempo en pagina y conversiones');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'charts',
      'name' => 'bar-chart',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_page_builder.analytics_dashboard';
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
    return 40;
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
    $publishedCount = 0;

    if ($this->entityTypeManager !== NULL) {
      try {
        /** @var int $publishedCount */
        $publishedCount = $this->entityTypeManager
          ->getStorage('page_content')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('status', TRUE)
          ->count()
          ->execute();
      }
      catch (\Throwable) {
        // Graceful degradation.
      }
    }

    return [
      'badge' => $publishedCount > 0 ? $publishedCount : NULL,
      'badge_type' => 'info',
      'visible' => $publishedCount > 0,
    ];
  }

}
