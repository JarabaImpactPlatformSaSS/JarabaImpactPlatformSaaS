<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador de la API REST de Disaster Recovery.
 *
 * ESTRUCTURA:
 * Proporciona 8 endpoints REST para integracion con herramientas externas
 * de monitoring, alerting y automatizacion de DR.
 *
 * LOGICA:
 * Endpoints stub que seran implementados en fases posteriores con
 * logica completa de verificacion, failover y comunicacion.
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class DrApiController extends ControllerBase {

  /**
   * Devuelve el estado general de DR.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Estado de DR en formato JSON.
   */
  public function status(): JsonResponse {
    return new JsonResponse([
      'status' => 'operational',
      'timestamp' => time(),
      'message' => 'DR API operativa. Implementacion completa en fases posteriores.',
    ]);
  }

}
