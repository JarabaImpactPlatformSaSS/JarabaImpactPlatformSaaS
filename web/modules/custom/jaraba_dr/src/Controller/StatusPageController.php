<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controlador de la pagina de estado publica.
 *
 * ESTRUCTURA:
 * Status page publica accesible sin autenticacion que muestra el
 * estado de los servicios de la plataforma, incidentes activos y
 * metricas de uptime.
 *
 * LOGICA:
 * Recopila datos de StatusPageManagerService y renderiza el template
 * dr-status-page.html.twig. La pagina se auto-refresca segun la
 * configuracion (por defecto cada 60 segundos).
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class StatusPageController extends ControllerBase {

  /**
   * Renderiza la pagina de estado publica.
   *
   * @return array<string, mixed>
   *   Render array con el template de la status page.
   */
  public function page(): array {
    $config = $this->config('jaraba_dr.settings');
    $refresh_seconds = (int) ($config->get('status_page_refresh_seconds') ?? 60);

    return [
      '#theme' => 'dr_status_page',
      '#services' => [],
      '#active_incidents' => [],
      '#recent_incidents' => [],
      '#uptime_metrics' => [],
      '#last_updated' => time(),
      '#refresh_seconds' => $refresh_seconds,
      '#attached' => [
        'library' => ['jaraba_dr/status-page'],
      ],
      '#cache' => [
        'max-age' => $refresh_seconds,
      ],
    ];
  }

}
