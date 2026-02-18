<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jaraba_candidate\Agent\EmployabilityCopilotAgent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * API controller para el Copilot de Empleabilidad.
 *
 * PROPOSITO:
 * Expone 2 endpoints REST para el copilot del frontend:
 * - POST /api/v1/copilot/employability/chat → Procesar mensaje
 * - GET /api/v1/copilot/employability/suggestions → Sugerencias contextuales
 *
 * ESTRUCTURA:
 * - chat(): Recibe mensaje y modo, ejecuta el agente, retorna respuesta
 * - suggestions(): Genera chips de sugerencia segun la pagina actual
 *
 * SPEC: 20260120b S10
 */
class CopilotApiController extends ControllerBase {


  /**
   * Tenant context service. // AUDIT-CONS-N10: Proper DI for tenant context.
   */
  protected TenantContextService $tenantContext;
  /**
   * El agente copilot de empleabilidad.
   */
  protected EmployabilityCopilotAgent $copilotAgent;

  /**
   * La ruta actual.
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->copilotAgent = $container->get('jaraba_candidate.agent.employability_copilot');
    $instance->routeMatch = $container->get('current_route_match');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context'); // AUDIT-CONS-N10: Proper DI for tenant context.

    return $instance;
  }

  /**
   * POST /api/v1/copilot/employability/chat
   *
   * Procesa un mensaje del usuario y retorna la respuesta del copilot.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Peticion con JSON body: { message: string, mode?: string }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta del copilot con texto y modo detectado.
   */
  public function chat(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['message'])) {
      return new JsonResponse([
        'error' => $this->t('El mensaje es obligatorio.'),
      ], 400);
    }

    $message = $data['message'];
    $mode = $data['mode'] ?? NULL;

    // Establecer contexto del tenant si disponible.
    if ($this->tenantContext !== NULL) {
      $tenantContext = $this->tenantContext;
      $tenant = $tenantContext->getCurrentTenant();
      if ($tenant) {
        $this->copilotAgent->setTenantContext(
          (string) $tenant->id(),
          'empleabilidad'
        );
      }
    }

    $result = $this->copilotAgent->execute('chat', [
      'message' => $message,
      'mode' => $mode,
    ]);

    if ($result['success']) {
      return new JsonResponse([
        'success' => TRUE,
        'response' => $result['data']['text'] ?? '',
        'mode' => $result['data']['mode'] ?? 'faq',
        'mode_label' => $result['data']['mode_label'] ?? '',
      ]);
    }

    return new JsonResponse([
      'success' => FALSE,
      'error' => $result['error'] ?? $this->t('Error procesando tu mensaje.'),
    ], 500);
  }

  /**
   * GET /api/v1/copilot/employability/suggestions
   *
   * Retorna sugerencias contextuales segun la pagina desde la que se llama.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Peticion con query param: route (nombre de ruta actual).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Array de chips con sugerencias.
   */
  public function suggestions(Request $request): JsonResponse {
    $currentRoute = $request->query->get('route', '');
    $suggestions = $this->copilotAgent->getSuggestions($currentRoute);

    return new JsonResponse([
      'suggestions' => $suggestions,
      'modes' => array_keys($this->copilotAgent->getAvailableActions()),
    ]);
  }

  /**
   * GET|POST /api/v1/copilot/employability/proactive
   *
   * GET: Checks for pending proactive actions for the current user.
   * POST: Dismisses a proactive action.
   *
   * Plan Elevación Empleabilidad v1 — Fase 9.
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
      if ($ruleId && \Drupal::hasService('ecosistema_jaraba_core.employability_journey_progression')) {
        \Drupal::service('ecosistema_jaraba_core.employability_journey_progression')
          ->dismissAction($userId, $ruleId);
      }
      return new JsonResponse(['success' => TRUE]);
    }

    // GET: Check for pending proactive action.
    if (!\Drupal::hasService('ecosistema_jaraba_core.employability_journey_progression')) {
      return new JsonResponse(['has_action' => FALSE]);
    }

    try {
      $action = \Drupal::service('ecosistema_jaraba_core.employability_journey_progression')
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
