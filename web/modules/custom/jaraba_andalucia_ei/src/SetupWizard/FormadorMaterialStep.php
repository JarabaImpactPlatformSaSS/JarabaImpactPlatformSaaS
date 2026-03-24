<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Upload first teaching material.
 *
 * Complete when formador has at least 1 material_didactico_ei entity.
 */
class FormadorMaterialStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return 'formador_ei.material';
  }

  public function getWizardId(): string {
    return 'formador_ei';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Subir material didáctico');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Sube tu primer material de formación al programa');
  }

  public function getWeight(): int {
    return 30;
  }

  public function getIcon(): array {
    return [
      'category' => 'content',
      'name' => 'book-open',
      'variant' => 'duotone',
    ];
  }

  public function getRoute(): string {
    return 'jaraba_andalucia_ei.formador_dashboard';
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
      if (!$this->entityTypeManager->hasDefinition('material_didactico_ei')) {
        return FALSE;
      }
      $userId = (int) $this->currentUser->id();
      return (int) $this->entityTypeManager->getStorage('material_didactico_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $userId)
        ->count()
        ->execute() > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  public function getCompletionData(int $tenantId): array {
    $count = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('material_didactico_ei')) {
        $userId = (int) $this->currentUser->id();
        $count = (int) $this->entityTypeManager->getStorage('material_didactico_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'count' => $count,
      'label' => $count > 0
        ? $this->t('@count material(es) subido(s)', ['@count' => $count])
        : $this->t('Sin materiales'),
    ];
  }

  public function isOptional(): bool {
    return TRUE;
  }

}
