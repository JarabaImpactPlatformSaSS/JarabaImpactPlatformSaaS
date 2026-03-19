<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_page_builder\Service\QuotaManagerService;

/**
 * Daily Action: Crear nueva pagina.
 *
 * Accion primaria del dashboard Page Builder.
 * Muestra badge con paginas restantes segun cuota del plan.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * TENANT-001: Filtra por tenant_id.
 */
class NuevaPaginaAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?TenantContextService $tenantContext = NULL,
    protected ?QuotaManagerService $quotaManager = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.nueva_pagina';
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
    return $this->t('Crear nueva pagina');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea una landing page profesional con el editor visual');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'add-circle',
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
    return 'jaraba_page_builder.template_picker';
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
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    $remaining = NULL;

    if ($this->quotaManager !== NULL) {
      try {
        $quotaCheck = $this->quotaManager->checkCanCreatePage();
        $remaining = $quotaCheck['remaining'] ?? NULL;
      }
      catch (\Throwable) {
        // Graceful degradation.
      }
    }

    return [
      'badge' => is_int($remaining) && $remaining >= 0 ? $remaining : NULL,
      'badge_type' => match (TRUE) {
        $remaining !== NULL && $remaining === 0 => 'critical',
        $remaining !== NULL && $remaining <= 2 => 'warning',
        default => 'info',
      },
      'visible' => TRUE,
    ];
  }

}
