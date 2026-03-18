<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Primera sesion — Orientador Setup Wizard.
 *
 * Checks if the current user has created at least one sesion_programada_ei.
 * User-scoped via mentor_profile.
 */
class OrientadorSesionStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'orientador_ei.sesion';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'orientador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Primera sesión');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Programa tu primera sesión de orientación');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 30;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'education',
      'name' => 'calendar-clock',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.hub.sesion_programada.add';
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getSessionCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getSessionCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin sesiones programadas'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count sesión(es) programada(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of sessions for the current user's mentor profile.
   */
  protected function getSessionCount(): int {
    try {
      $userId = (int) $this->currentUser->id();

      if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
        return 0;
      }
      $mentorIds = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($mentorIds)) {
        return 0;
      }

      if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        // Fallback: count mentoring_session entities.
        if (!$this->entityTypeManager->hasDefinition('mentoring_session')) {
          return 0;
        }
        return (int) $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('mentor_id', $mentorIds, 'IN')
          ->count()
          ->execute();
      }

      // Primary: count sesion_programada_ei for this user.
      return (int) $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('orientador_id', $mentorIds, 'IN')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
