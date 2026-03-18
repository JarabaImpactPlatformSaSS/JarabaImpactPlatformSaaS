<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Participantes asignados — Orientador Setup Wizard.
 *
 * Checks if the current user has assigned participants via mentoring sessions.
 * User-scoped via mentor_profile → mentoring_session → programa_participante_ei.
 */
class OrientadorParticipantesStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'orientador_ei.participantes';
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
    return $this->t('Participantes asignados');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisa los participantes que tienes asignados');
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
      'category' => 'users',
      'name' => 'users',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.orientador_dashboard';
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
    return $this->getAssignedParticipantCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getAssignedParticipantCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin participantes asignados'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count participante(s) asignado(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of participants assigned to the current user via sessions.
   */
  protected function getAssignedParticipantCount(): int {
    try {
      $userId = (int) $this->currentUser->id();

      // Find mentor_profile for this user.
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

      // Count unique mentoring sessions (as proxy for assigned participants).
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
    catch (\Throwable) {
      return 0;
    }
  }

}
