<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: pending entregables for participante.
 *
 * Visible from fase 'atencion' onwards.
 * Badge shows count of entregables not yet completed.
 * User-scoped via uid.
 */
class ParticipanteEntregablesPendientesAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * Phases where this action becomes visible.
   */
  private const VISIBLE_PHASES = [
    'atencion',
    'formacion',
    'acompanamiento',
    'insercion',
    'seguimiento',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'participante_ei.entregables_pendientes';
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
    return $this->t('Entregables pendientes');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Entregables formativos que necesitan completarse');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'document', 'name' => 'checklist', 'variant' => 'duotone'];
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
    return 20;
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
    $count = 0;
    $visible = FALSE;
    try {
      $participante = $this->getParticipanteActivo();
      if ($participante !== NULL) {
        $fase = $participante->get('fase_actual')->value ?? 'acogida';
        $visible = in_array($fase, self::VISIBLE_PHASES, TRUE);

        if ($visible && $this->entityTypeManager->hasDefinition('entregable_formativo_ei')) {
          $count = (int) $this->entityTypeManager->getStorage('entregable_formativo_ei')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('participante_id', $participante->id())
            ->condition('estado', 'pendiente')
            ->count()
            ->execute();
        }
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 10 ? 'warning' : 'info',
      'visible' => $visible,
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
