<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para el dashboard frontend de expedientes juridicos.
 *
 * Estructura: Controller zero-region que retorna markup vacio.
 *   Las variables del template se inyectan via hook_preprocess_page().
 *
 * Logica: ZERO-REGION-001 — El controller NO inyecta variables al
 *   template. Solo retorna render array minimo. Las variables y
 *   drupalSettings se inyectan en jaraba_legal_cases_preprocess_page().
 */
class CasesDashboardController extends ControllerBase {

  /**
   * Dashboard de expedientes juridicos.
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
