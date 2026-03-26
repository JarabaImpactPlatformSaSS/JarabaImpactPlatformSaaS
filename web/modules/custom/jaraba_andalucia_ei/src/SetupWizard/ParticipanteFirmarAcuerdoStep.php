<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Firmar acuerdo de participación — Participante Setup Wizard.
 *
 * Checks if acuerdo_participacion_firmado is TRUE.
 * User-scoped via uid.
 */
class ParticipanteFirmarAcuerdoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'participante_ei.firmar_acuerdo';
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
    return $this->t('Firmar acuerdo de participación');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Firma el acuerdo de participación en el programa');
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
      'category' => 'document',
      'name' => 'signature',
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
    $participante = $this->getParticipanteActivo();
    if ($participante === NULL) {
      return FALSE;
    }

    return (bool) ($participante->get('acuerdo_participacion_firmado')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $participante = $this->getParticipanteActivo();
    if ($participante === NULL) {
      return [
        'count' => 0,
        'label' => $this->t('Sin participante registrado'),
      ];
    }

    $signed = (bool) ($participante->get('acuerdo_participacion_firmado')->value ?? FALSE);

    return [
      'count' => $signed ? 1 : 0,
      'label' => $signed ? $this->t('Acuerdo firmado') : $this->t('Pendiente de firma'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
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
