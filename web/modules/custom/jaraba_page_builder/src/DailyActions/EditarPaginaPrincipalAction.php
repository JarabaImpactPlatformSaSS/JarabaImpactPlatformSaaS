<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Daily Action: Editar pagina principal.
 *
 * Enlaza a la PageContent mas reciente del tenant para edicion.
 * Solo visible si el tenant tiene al menos una pagina.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * TENANT-001: Filtra por tenant_id.
 */
class EditarPaginaPrincipalAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.editar_principal';
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
    return $this->t('Editar pagina principal');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Actualiza el contenido de tu pagina mas reciente');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'edit',
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
    return 'jaraba_page_builder.my_pages';
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
    $hasPages = FALSE;

    if ($this->entityTypeManager !== NULL) {
      try {
        $count = $this->entityTypeManager
          ->getStorage('page_content')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->count()
          ->execute();
        $hasPages = $count > 0;
      }
      catch (\Throwable) {
        // Graceful degradation.
      }
    }

    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => $hasPages,
    ];
  }

}
