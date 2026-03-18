<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\jaraba_andalucia_ei\Service\AlertasNormativasService;
use Drupal\jaraba_andalucia_ei\Service\PlazoEnforcementService;

/**
 * Daily action: plazos normativos vencidos o próximos a vencer.
 *
 * SETUP-WIZARD-DAILY-001: Tagged service via CompilerPass.
 * Muestra badge con el total de alertas CRITICO + ALTO de plazos.
 */
class PlazosVencidosAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly ?PlazoEnforcementService $plazoService = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'coordinador_ei.plazos_vencidos';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'coordinador_ei';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Plazos normativos');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Recibos, VoBo e incentivos con plazo vencido o próximo');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'status', 'name' => 'clock', 'variant' => 'duotone'];
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
    return 'jaraba_andalucia_ei.coordinador_dashboard';
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
    $urgentCount = 0;

    if ($this->plazoService) {
      try {
        $alertas = $this->plazoService->getAlertasPlazos($tenantId);
        foreach ($alertas as $alerta) {
          if (in_array($alerta['nivel'] ?? '', [AlertasNormativasService::NIVEL_CRITICO, AlertasNormativasService::NIVEL_ALTO], TRUE)) {
            $urgentCount++;
          }
        }
      }
      catch (\Throwable) {
      }
    }

    return [
      'badge' => $urgentCount > 0 ? $urgentCount : NULL,
      'badge_type' => $urgentCount > 5 ? 'critical' : ($urgentCount > 0 ? 'warning' : ''),
      'visible' => TRUE,
    ];
  }

}
