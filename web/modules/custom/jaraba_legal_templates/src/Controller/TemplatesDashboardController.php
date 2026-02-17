<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Dashboard de Plantillas y Documentos — Zero-Region pattern.
 *
 * Estructura: ZERO-REGION-001. Retorna markup vacio; datos inyectados via
 *   hook_preprocess_page en drupalSettings.jarabaLegalTemplates.
 */
class TemplatesDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de plantillas.
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
