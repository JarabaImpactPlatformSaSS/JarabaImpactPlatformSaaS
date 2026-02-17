<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller para el dashboard frontend de la boveda documental.
 *
 * Estructura: Controller zero-region que retorna markup vacio.
 *   Las variables del template se inyectan via hook_preprocess_page().
 *
 * Logica: ZERO-REGION-001 — El controller NO inyecta variables al
 *   template. Solo retorna render array minimo.
 */
class VaultDashboardController extends ControllerBase {

  /**
   * Dashboard de la boveda documental.
   *
   * @return array
   *   Render array minimo (ZERO-REGION-001).
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
