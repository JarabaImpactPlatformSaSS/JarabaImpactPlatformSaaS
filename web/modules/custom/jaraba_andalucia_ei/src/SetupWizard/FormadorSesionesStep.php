<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Review assigned sessions.
 *
 * Complete when formador has at least 1 visible session.
 */
class FormadorSesionesStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'formador_ei.sesiones';
  }

  /**
   *
   */
  public function getWizardId(): string {
    return 'formador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Revisar sesiones asignadas');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Verifica las sesiones que tienes asignadas');
  }

  /**
   *
   */
  public function getWeight(): int {
    return 20;
  }

  /**
   *
   */
  public function getIcon(): array {
    return [
      'category' => 'education',
      'name' => 'clipboard',
      'variant' => 'duotone',
    ];
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.formador_dashboard';
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
  public function isComplete(int $tenantId): bool {
    try {
      if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        return FALSE;
      }
      $userId = (int) $this->currentUser->id();
      return (int) $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('formador_id', $userId)
        ->count()
        ->execute() > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   *
   */
  public function getCompletionData(int $tenantId): array {
    $count = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        $userId = (int) $this->currentUser->id();
        $count = (int) $this->entityTypeManager->getStorage('sesion_programada_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('formador_id', $userId)
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'count' => $count,
      'label' => $count > 0
        ? $this->t('@count sesión(es) asignada(s)', ['@count' => $count])
        : $this->t('Sin sesiones asignadas'),
    ];
  }

  /**
   *
   */
  public function isOptional(): bool {
    return FALSE;
  }

}
