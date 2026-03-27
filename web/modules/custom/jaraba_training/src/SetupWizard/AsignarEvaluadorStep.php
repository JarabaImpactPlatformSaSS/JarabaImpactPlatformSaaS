<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Wizard step: Asignar al menos un evaluador certificado.
 */
class AsignarEvaluadorStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getId(): string {
    return 'certificacion.asignar_evaluador';
  }

  public function getWizardId(): string {
    return '__global__';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Asignar evaluador certificado');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Asigna al menos un formador con rol de evaluador para revisar portfolios y emitir certificaciones.');
  }

  public function getWeight(): int {
    return 82;
  }

  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'group', 'variant' => 'duotone'];
  }

  public function getRoute(): string {
    return 'entity.user.collection';
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
      $count = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'formador_certificado')
        ->condition('status', 1)
        ->count()
        ->execute();
      return $count > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  public function getCompletionData(int $tenantId): array {
    try {
      $count = $this->entityTypeManager->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'formador_certificado')
        ->condition('status', 1)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      $count = 0;
    }
    return [
      'count' => $count,
      'label' => (string) $this->t('evaluadores certificados'),
      'progress' => $count > 0 ? 100 : 0,
    ];
  }

  public function isOptional(): bool {
    return TRUE;
  }

}
