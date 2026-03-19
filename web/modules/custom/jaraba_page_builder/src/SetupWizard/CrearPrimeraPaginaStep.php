<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Setup Wizard step: Crear primera pagina.
 *
 * Primer paso del wizard del Page Builder.
 * Se completa cuando el tenant tiene al menos 1 PageContent entity.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * TENANT-001: Filtra por tenant_id.
 * OPTIONAL-CROSSMODULE-001: Servicios opcionales via @?.
 */
class CrearPrimeraPaginaStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.crear_primera_pagina';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'page_builder';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Crea tu primera pagina');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Empieza con una pagina en blanco o elige una plantilla profesional');
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
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'layout-template',
      'variant' => 'duotone',
    ];
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
  public function isComplete(int $tenantId): bool {
    if ($this->entityTypeManager === NULL) {
      return FALSE;
    }

    try {
      $count = $this->entityTypeManager
        ->getStorage('page_content')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->count()
        ->execute();

      return $count > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $complete = $this->isComplete($tenantId);
    return [
      'label' => $complete
        ? $this->t('Pagina creada')
        : $this->t('Pendiente'),
      'count' => $complete ? 1 : 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

}
