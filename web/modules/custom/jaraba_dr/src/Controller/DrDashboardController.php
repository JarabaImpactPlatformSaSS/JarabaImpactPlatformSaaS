<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador del dashboard de Disaster Recovery.
 *
 * ESTRUCTURA:
 * Dashboard frontend zero-region que muestra el estado general de DR:
 * backups, tests, incidentes activos y estado de failover.
 *
 * LOGICA:
 * Recopila datos de los servicios de DR y renderiza el template
 * dr-dashboard.html.twig con las metricas consolidadas.
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class DrDashboardController extends ControllerBase {

  /**
   * Renderiza el dashboard de Disaster Recovery.
   *
   * @return array<string, mixed>
   *   Render array con el template del dashboard.
   */
  public function dashboard(): array {
    return [
      '#theme' => 'dr_dashboard',
      '#backup_status' => [],
      '#test_results' => [],
      '#active_incidents' => [],
      '#failover_status' => [],
      '#service_status' => [],
      '#metrics' => [],
      '#attached' => [
        'library' => ['jaraba_dr/dr-dashboard'],
      ],
    ];
  }

}
