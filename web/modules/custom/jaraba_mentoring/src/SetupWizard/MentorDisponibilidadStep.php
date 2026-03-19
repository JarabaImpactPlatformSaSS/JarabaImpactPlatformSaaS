<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Configure availability slots.
 *
 * User-scoped — checks if the mentor has availability_slot entities.
 */
class MentorDisponibilidadStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'mentor.disponibilidad';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'mentor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Horario disponible');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Define tus franjas horarias para sesiones de mentoría');
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
      'category' => 'education',
      'name' => 'calendar-clock',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_mentoring.mentor_dashboard';
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
    return $this->getAvailabilitySlotCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getAvailabilitySlotCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin horario configurado'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count franja(s) configurada(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Counts availability slots for the current user's mentor profile.
   */
  protected function getAvailabilitySlotCount(): int {
    try {
      // First find the mentor profile for the current user.
      $profileStorage = $this->entityTypeManager->getStorage('mentor_profile');
      $profileIds = $profileStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', (int) $this->currentUser->id())
        ->range(0, 1)
        ->execute();

      if (empty($profileIds)) {
        return 0;
      }

      $mentorProfileId = reset($profileIds);

      // Count availability slots for that mentor.
      $slotStorage = $this->entityTypeManager->getStorage('availability_slot');
      return (int) $slotStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('mentor_id', $mentorProfileId)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
