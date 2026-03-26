<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: today's sessions for participante.
 *
 * Badge shows count of sesion_programada_ei scheduled for today
 * where the participante has an inscripcion.
 * User-scoped via uid.
 */
class ParticipanteSesionesHoyAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'participante_ei.sesiones_hoy';
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
    return $this->t('Sesiones de hoy');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Consulta las sesiones formativas programadas para hoy');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'azul-corporativo';
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
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    $count = 0;
    try {
      $participante = $this->getParticipanteActivo();
      if ($participante !== NULL && $this->entityTypeManager->hasDefinition('inscripcion_sesion_ei')) {
        $today = (new \DateTime())->format('Y-m-d');
        $inscripcionIds = $this->entityTypeManager->getStorage('inscripcion_sesion_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('participante_id', $participante->id())
          ->condition('fecha_sesion', $today . 'T00:00:00', '>=')
          ->condition('fecha_sesion', $today . 'T23:59:59', '<=')
          ->execute();

        $count = count($inscripcionIds);
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 3 ? 'warning' : 'info',
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
