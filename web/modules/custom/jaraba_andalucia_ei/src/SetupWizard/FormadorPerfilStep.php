<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Formador profile — complete StaffProfileEi.
 *
 * Checks if the formador has a staff_profile_ei entity with
 * at least titulacion filled.
 */
class FormadorPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'formador_ei.perfil';
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
    return $this->t('Perfil profesional');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Completa tu perfil con titulación y especialidades');
  }

  /**
   *
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   *
   */
  public function getIcon(): array {
    return [
      'category' => 'users',
      'name' => 'user-edit',
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
      if (!$this->entityTypeManager->hasDefinition('staff_profile_ei')) {
        return FALSE;
      }
      $userId = (int) $this->currentUser->id();
      return (int) $this->entityTypeManager->getStorage('staff_profile_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->condition('rol_programa', 'formador')
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
    $complete = $this->isComplete($tenantId);
    return [
      'count' => $complete ? 1 : 0,
      'label' => $complete
        ? $this->t('Perfil completado')
        : $this->t('Perfil pendiente'),
    ];
  }

  /**
   *
   */
  public function isOptional(): bool {
    return FALSE;
  }

}
