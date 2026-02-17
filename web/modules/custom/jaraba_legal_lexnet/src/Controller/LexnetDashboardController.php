<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Dashboard de LexNET — Zero-Region pattern.
 *
 * Estructura: ZERO-REGION-001. Retorna markup vacio; datos inyectados via
 *   hook_preprocess_page en drupalSettings.jarabaLegalLexnet.
 */
class LexnetDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de LexNET.
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
