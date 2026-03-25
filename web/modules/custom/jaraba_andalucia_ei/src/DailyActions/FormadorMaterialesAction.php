<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Materials management for the formador.
 *
 * Badge shows count of materials uploaded by the formador.
 */
class FormadorMaterialesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'formador_ei.materiales';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'formador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Materiales didácticos');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Gestiona los materiales de tus sesiones');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'content', 'name' => 'book-open', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.formador_dashboard';
  }

  /**
   *
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   *
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   *
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 30;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    $count = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('material_didactico_ei')) {
        $userId = (int) $this->currentUser->id();
        $count = (int) $this->entityTypeManager->getStorage('material_didactico_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
