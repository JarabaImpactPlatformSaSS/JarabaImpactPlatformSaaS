<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller del dashboard de Predicciones y Analytics.
 *
 * ESTRUCTURA:
 *   Implementa el patron zero-region (ZERO-REGION-001).
 *   El controller retorna SOLO markup vacio. Todo el contenido
 *   visual se inyecta via template page--predictions.html.twig.
 *
 * LOGICA:
 *   El template page__predictions se activa via
 *   hook_theme_suggestions_page_alter() en jaraba_predictive.module.
 *   Las variables de template se inyectan via
 *   jaraba_predictive_preprocess_page() en .module.
 *
 * RELACIONES:
 *   - Activado por: jaraba_predictive.dashboard route.
 *   - Template: page--predictions.html.twig (via hook_theme_suggestions).
 *   - Variables: jaraba_predictive.module (preprocess).
 */
class PredictiveDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de predicciones y analytics.
   *
   * ESTRUCTURA: Metodo principal del controller que retorna render array.
   * LOGICA: Retorna markup vacio siguiendo el patron zero-region.
   *   Todo el contenido se inyecta via preprocess en el template.
   *
   * @return array
   *   Render array con markup vacio.
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
