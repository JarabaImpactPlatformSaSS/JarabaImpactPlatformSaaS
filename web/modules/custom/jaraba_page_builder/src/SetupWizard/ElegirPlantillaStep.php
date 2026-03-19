<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Setup Wizard step: Elegir plantilla.
 *
 * Segundo paso del wizard del Page Builder.
 * Se completa cuando alguna PageContent del tenant tiene canvas_data no vacio,
 * indicando que el usuario ha utilizado el editor visual o una plantilla.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * TENANT-001: Filtra por tenant_id.
 */
class ElegirPlantillaStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.elegir_plantilla';
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
    return $this->t('Elige una plantilla');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Mas de 40 bloques profesionales listos para arrastrar y soltar');
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
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'palette',
      'variant' => 'duotone',
    ];
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
        ->condition('canvas_data', '', '<>')
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
        ? $this->t('Plantilla aplicada')
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
