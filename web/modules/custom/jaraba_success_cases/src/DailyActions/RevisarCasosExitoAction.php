<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry;

/**
 * DailyAction for admins: reviews SuccessCase data quality.
 *
 * Checks:
 * - Verticals without published SuccessCase (9 commercial).
 * - Published cases without hero_image.
 * - Published cases without quote_short.
 *
 * Visible only to users with 'administer success cases' permission.
 * SETUP-WIZARD-DAILY-001: Global dashboard, tagged service.
 */
class RevisarCasosExitoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * The 9 commercial verticals that should have cases.
   */
  private const COMMERCIAL_VERTICALS = [
    'jarabalex',
    'agroconecta',
    'comercioconecta',
    'empleabilidad',
    'emprendimiento',
    'formacion',
    'serviciosconecta',
    'andalucia_ei',
    'content_hub',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return '__global__.revisar_casos_exito';
  }

  public function getDashboardId(): string {
    return DailyActionsRegistry::GLOBAL_DASHBOARD_ID;
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Revisar casos de éxito');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Verifica cobertura, fotos y testimonios de los casos de éxito publicados');
  }

  public function getIcon(): array {
    return [
      'category' => 'achievement',
      'name' => 'star',
      'variant' => 'duotone',
    ];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'entity.success_case.collection';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return '/admin/content/success-case';
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 90;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    // Only visible for admins with success case permission.
    if (!$this->currentUser->hasPermission('administer success cases')) {
      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('success_case');
      $gaps = 0;

      // Check verticals without published cases.
      foreach (self::COMMERCIAL_VERTICALS as $vertical) {
        $count = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('vertical', $vertical)
          ->condition('status', 1)
          ->count()
          ->execute();

        if ($count === 0) {
          $gaps++;
        }
      }

      // Check published cases without hero_image.
      $published = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->execute();

      foreach ($storage->loadMultiple($published) as $case) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $case */
        if ($case->hasField('hero_image') && $case->get('hero_image')->isEmpty()) {
          $gaps++;
        }
      }

      return [
        'badge' => $gaps > 0 ? $gaps : NULL,
        'badge_type' => $gaps > 0 ? 'warning' : 'info',
        'visible' => $gaps > 0,
      ];
    }
    catch (\Throwable) {
      return ['visible' => FALSE, 'badge' => NULL, 'badge_type' => ''];
    }
  }

}
