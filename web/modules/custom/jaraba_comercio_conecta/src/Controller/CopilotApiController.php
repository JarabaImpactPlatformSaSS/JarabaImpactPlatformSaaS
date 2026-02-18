<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller REST API para el copiloto de ComercioConecta.
 *
 * Estructura: Expone el endpoint /api/v1/copilot/comercio/proactive
 *   para que el frontend (Copilot FAB) consulte acciones proactivas
 *   pendientes del JourneyProgressionService.
 *
 * Logica:
 * - GET: obtiene la accion proactiva pendiente para el usuario.
 * - POST: descarta (dismiss) una accion proactiva por rule_id.
 *
 * Plan Elevacion ComercioConecta Clase Mundial v1 — Gap fix.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\ComercioConectaJourneyProgressionService
 * @see \Drupal\jaraba_candidate\Controller\CopilotApiController
 */
class CopilotApiController extends ControllerBase {

  /**
   * GET|POST /api/v1/copilot/comercio/proactive
   *
   * GET: Checks for pending proactive actions for the current user.
   * POST: Dismisses a proactive action.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   GET: { has_action: bool, action?: array }
   *   POST: { success: bool }
   */
  public function proactive(Request $request): JsonResponse {
    $userId = (int) $this->currentUser()->id();
    if (!$userId) {
      return new JsonResponse(['has_action' => FALSE]);
    }

    // POST: Dismiss a proactive action.
    if ($request->isMethod('POST')) {
      $data = json_decode($request->getContent(), TRUE);
      $ruleId = $data['rule_id'] ?? '';
      if ($ruleId && \Drupal::hasService('ecosistema_jaraba_core.comercioconecta_journey_progression')) {
        \Drupal::service('ecosistema_jaraba_core.comercioconecta_journey_progression')
          ->dismissAction($userId, $ruleId);
      }
      return new JsonResponse(['success' => TRUE]);
    }

    // GET: Check for pending proactive action.
    if (!\Drupal::hasService('ecosistema_jaraba_core.comercioconecta_journey_progression')) {
      return new JsonResponse(['has_action' => FALSE]);
    }

    try {
      $action = \Drupal::service('ecosistema_jaraba_core.comercioconecta_journey_progression')
        ->getPendingAction($userId);

      if ($action) {
        return new JsonResponse([
          'has_action' => TRUE,
          'action' => $action,
        ]);
      }
    }
    catch (\Exception $e) {
      // Non-critical — proactive actions are optional.
    }

    return new JsonResponse(['has_action' => FALSE]);
  }

}
