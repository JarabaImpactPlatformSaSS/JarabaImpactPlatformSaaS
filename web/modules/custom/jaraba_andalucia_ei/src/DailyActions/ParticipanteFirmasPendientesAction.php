<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: pending document signatures for participante.
 *
 * Badge shows count of unsigned documents (acuerdo, DACI).
 * User-scoped via uid.
 */
class ParticipanteFirmasPendientesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'participante_ei.firmas_pendientes';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'participante_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Firmas pendientes');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Documentos que requieren tu firma');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'document', 'name' => 'signature', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'naranja-impulso';
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
  public function getHrefOverride(): ?string {
    return NULL;
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
  public function getWeight(): int {
    return 15;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    $pending = 0;
    try {
      $participante = $this->getParticipanteActivo();
      if ($participante !== NULL) {
        $acuerdo = (bool) ($participante->get('acuerdo_participacion_firmado')->value ?? FALSE);
        $daci = (bool) ($participante->get('daci_firmado')->value ?? FALSE);

        if (!$acuerdo) {
          $pending++;
        }
        if (!$daci) {
          $pending++;
        }
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $pending > 0 ? $pending : NULL,
      'badge_type' => $pending > 0 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
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
