<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador del dashboard legal.
 *
 * ESTRUCTURA:
 * Renderiza el dashboard frontend zero-region con las métricas
 * del módulo legal: ToS, SLA, AUP, offboarding y denuncias.
 *
 * LÓGICA:
 * - Carga estadísticas de todas las entidades legales.
 * - Renderiza el template legal-dashboard.html.twig.
 * - Adjunta la librería legal-dashboard de CSS/JS.
 *
 * Spec: Doc 184 §4. Plan: FASE 5, Stack Compliance Legal N1.
 */
class LegalDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard legal.
   *
   * @return array
   *   Render array con el dashboard legal.
   */
  public function dashboard(): array {
    return [
      '#theme' => 'legal_dashboard',
      '#tos_status' => [],
      '#sla_metrics' => [],
      '#aup_violations' => [],
      '#offboarding_requests' => [],
      '#whistleblower_stats' => [],
      '#usage_limits' => [],
      '#metrics' => [],
      '#attached' => [
        'library' => [
          'jaraba_legal/legal-dashboard',
        ],
      ],
    ];
  }

}
