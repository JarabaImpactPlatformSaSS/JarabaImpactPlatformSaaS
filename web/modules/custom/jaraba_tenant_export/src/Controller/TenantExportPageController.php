<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador de la página frontend de exportación de datos.
 *
 * Directriz 3.4: Template zero-region, sin bloques, sin sidebar admin.
 * Variables and drupalSettings are injected via
 * jaraba_tenant_export_preprocess_page() because zero-region templates
 * do not render {{ page.content }}.
 */
class TenantExportPageController extends ControllerBase {

  /**
   * Página principal de exportación de datos del tenant.
   */
  public function page(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

}
