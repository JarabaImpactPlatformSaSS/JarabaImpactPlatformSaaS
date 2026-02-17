<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller del dashboard de Fondos y Subvenciones.
 *
 * Estructura: Controller zero-region que retorna markup vacio.
 *   Todas las variables se inyectan via hook_preprocess_page()
 *   en jaraba_funding.module (ZERO-REGION-001).
 *
 * Logica: El controller NO debe inyectar datos ni variables.
 *   Solo retorna un render array vacio para que el sistema de
 *   temas de Drupal renderice el template page--funding.html.twig
 *   con las variables inyectadas desde el preprocess.
 */
class FundingDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de fondos (pagina zero-region).
   *
   * @return array
   *   Render array vacio — variables via hook_preprocess_page().
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
