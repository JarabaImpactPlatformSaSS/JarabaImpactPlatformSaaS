<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Wizard step: Configurar al menos una promocion activa.
 *
 * Wizard ID: __global__ (inyectado en todos los wizards admin).
 * Weight: 90 (despues de CompletarQuiz en 85).
 *
 * Se completa cuando existe al menos 1 PromotionConfig activa.
 * Asegura que el copilot publico tiene contexto de promociones.
 */
class ConfigurarPromocionesStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.configurar_promociones';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Configurar promociones activas');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea al menos una promoción para que el copilot IA informe a los visitantes sobre tus ofertas y programas.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 90;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'marketing',
      'name' => 'megaphone',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.promotion_config.collection';
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
    try {
      $storage = $this->entityTypeManager->getStorage('promotion_config');
      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
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
    try {
      $storage = $this->entityTypeManager->getStorage('promotion_config');
      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->count()
        ->execute();
      return [
        'label' => $this->t('@count promoción(es) activa(s)', ['@count' => $count]),
        'count' => $count,
      ];
    }
    catch (\Throwable) {
      return ['label' => $this->t('Sin datos'), 'count' => 0];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
