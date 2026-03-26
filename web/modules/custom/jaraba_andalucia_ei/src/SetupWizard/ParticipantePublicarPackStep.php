<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 8: Publicar pack — Participante Setup Wizard.
 *
 * Checks if the participante's pack_servicio_ei entity has publicado=TRUE.
 * User-scoped via uid.
 */
class ParticipantePublicarPackStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'participante_ei.publicar_pack';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'participante_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Publicar pack de servicios');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Publica tu pack de servicios para que sea visible');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 80;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'marketing',
      'name' => 'launch',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.dashboard';
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
    return $this->getPublishedPackCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getPublishedPackCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin pack publicado'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('Pack de servicios publicado'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets count of published pack_servicio_ei for the current user.
   */
  protected function getPublishedPackCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('pack_servicio_ei')) {
        return 0;
      }

      $participante = $this->getParticipanteActivo();
      if ($participante === NULL) {
        return 0;
      }

      return (int) $this->entityTypeManager->getStorage('pack_servicio_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participante->id())
        ->condition('publicado', TRUE)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Gets the active participante entity for the current user.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The participante entity, or NULL if not found.
   */
  protected function getParticipanteActivo(): ?object {
    try {
      if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
        return NULL;
      }
      $ids = $this->entityTypeManager->getStorage('programa_participante_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $this->currentUser->id())
        ->range(0, 1)
        ->execute();

      if ($ids === []) {
        return NULL;
      }

      return $this->entityTypeManager->getStorage('programa_participante_ei')
        ->load(reset($ids));
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
