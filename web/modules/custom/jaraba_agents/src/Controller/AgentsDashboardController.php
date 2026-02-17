<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller del dashboard de Agentes Autonomos.
 *
 * Estructura: Implementa el patron zero-region (ZERO-REGION-001).
 *   El controller retorna SOLO markup vacio.
 *
 * Logica: El template page__agents se activa via
 *   hook_theme_suggestions_page_alter(). Variables via .module.
 */
class AgentsDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de agentes autonomos.
   *
   * Estructura: Metodo principal del controller que retorna render array.
   * Logica: Retorna markup vacio siguiendo el patron zero-region.
   *   Todo el contenido se inyecta via preprocess en el template.
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
