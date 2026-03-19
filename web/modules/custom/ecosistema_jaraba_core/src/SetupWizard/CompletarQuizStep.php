<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Wizard step global opcional: Completar quiz de recomendación de vertical.
 *
 * Aparece en TODOS los wizards (global). Incentiva a logueados que se
 * registraron sin pasar por el quiz a descubrir su vertical ideal.
 * Se completa cuando el usuario tiene un QuizResult vinculado.
 *
 * ZEIGARNIK-PRELOAD-001: weight 85 (antes de SubscriptionUpgrade 90).
 * isOptional() = TRUE — no bloquea la completitud del wizard.
 */
class CompletarQuizStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.completar_quiz';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return AutoCompleteAccountStep::GLOBAL_WIZARD_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Descubre más verticales');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Haz el test de 30 segundos y descubre qué más puede hacer la plataforma por ti.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 85;
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
    $uid = (int) $this->currentUser->id();
    if ($uid <= 0) {
      return FALSE;
    }

    try {
      $count = $this->entityTypeManager
        ->getStorage('quiz_result')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->count()
        ->execute();
      return $count > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    if ($this->isComplete($tenantId)) {
      return ['label' => $this->t('Completado')];
    }
    return ['label' => $this->t('30 segundos')];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
