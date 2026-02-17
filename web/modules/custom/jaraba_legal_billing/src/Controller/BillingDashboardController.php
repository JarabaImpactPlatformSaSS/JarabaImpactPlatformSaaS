<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Dashboard de facturacion legal — Zero-Region pattern.
 *
 * Estructura: ZERO-REGION-001. Retorna markup vacio; datos inyectados via
 *   hook_preprocess_page en drupalSettings.jarabaLegalBilling.
 */
class BillingDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de facturacion.
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
