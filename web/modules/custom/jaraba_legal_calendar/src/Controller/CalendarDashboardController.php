<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller para el dashboard de agenda juridica (zero-region).
 */
class CalendarDashboardController extends ControllerBase {

  /**
   * Dashboard principal de agenda juridica.
   *
   * Retorna markup vacio — variables via hook_preprocess_page().
   * Template: page--legal-calendar.html.twig.
   *
   * @return array
   *   Render array vacio (ZERO-REGION-001).
   */
  public function dashboard(): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
      '#attached' => [
        'library' => ['jaraba_legal_calendar/dashboard'],
      ],
    ];
  }

}
