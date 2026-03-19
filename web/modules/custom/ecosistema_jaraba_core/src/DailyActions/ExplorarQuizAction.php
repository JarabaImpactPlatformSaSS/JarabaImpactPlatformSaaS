<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily action global: Descubre tu vertical ideal (quiz).
 *
 * Aparece en TODOS los dashboards para usuarios que NO han
 * completado el quiz. Se oculta automáticamente tras completarlo.
 *
 * SETUP-WIZARD-DAILY-001: Dashboard ID '__global__'.
 * getContext(): visible solo si no hay QuizResult para el uid.
 */
class ExplorarQuizAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.explorar_quiz';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Descubre tu vertical ideal');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Test de 30 segundos con IA para encontrar la mejor solución para ti.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ai',
      'name' => 'sparkles',
      'variant' => 'duotone',
    ];
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
    return 'ecosistema_jaraba_core.quiz_vertical';
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
    return 80;
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
    $uid = (int) $this->currentUser->id();
    if ($uid <= 0) {
      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }

    try {
      $count = $this->entityTypeManager
        ->getStorage('quiz_result')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->count()
        ->execute();

      // Solo visible si el usuario NO ha completado el quiz.
      return [
        'visible' => $count === 0,
        'badge' => $count === 0 ? 1 : NULL,
        'badge_type' => 'info',
      ];
    }
    catch (\Throwable) {
      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }
  }

}
