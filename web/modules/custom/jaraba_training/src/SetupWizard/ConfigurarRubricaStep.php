<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Wizard step: Configurar rúbrica del Método Jaraba.
 */
class ConfigurarRubricaStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getId(): string {
    return 'certificacion.configurar_rubrica';
  }

  public function getWizardId(): string {
    return '__global__';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Configurar rúbrica del Método');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Define los indicadores observables de las 4 competencias (Pedir, Evaluar, Iterar, Integrar) por cada nivel.');
  }

  public function getWeight(): int {
    return 80;
  }

  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'certificate', 'variant' => 'duotone'];
  }

  public function getRoute(): string {
    return 'entity.certification_program.collection';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function isComplete(int $tenantId): bool {
    try {
      $count = $this->entityTypeManager->getStorage('certification_program')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('rubric_config', NULL, 'IS NOT NULL')
        ->count()
        ->execute();
      return $count > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  public function getCompletionData(int $tenantId): array {
    return [
      'count' => $this->isComplete($tenantId) ? 1 : 0,
      'label' => (string) $this->t('programas con rúbrica configurada'),
      'progress' => $this->isComplete($tenantId) ? 100 : 0,
    ];
  }

  public function isOptional(): bool {
    return TRUE;
  }

}
