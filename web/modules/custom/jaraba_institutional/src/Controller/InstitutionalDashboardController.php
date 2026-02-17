<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller del dashboard de Programas Institucionales.
 *
 * ESTRUCTURA: Implementa el patron zero-region (ZERO-REGION-001).
 *   El controller retorna SOLO markup vacio. Las variables del
 *   template se inyectan desde hook_preprocess_page() en el .module.
 *
 * LOGICA: El template page__institutional se activa via
 *   hook_theme_suggestions_page_alter(). No hay logica de negocio
 *   en este controller — toda la carga de datos esta en el .module.
 */
class InstitutionalDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de programas institucionales.
   *
   * @return array
   *   Render array vacio (zero-region pattern).
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
