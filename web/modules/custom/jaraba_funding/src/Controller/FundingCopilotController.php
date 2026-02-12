<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_funding\Service\Intelligence\FundingCopilotService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del Copilot de Funding Intelligence.
 *
 * PROPOSITO:
 * Renderiza la pagina del copilot de subvenciones y expone los endpoints
 * API para el chat conversacional y el historial de conversaciones.
 *
 * FUNCIONALIDADES:
 * - Pagina frontend del copilot con chat interactivo
 * - API de chat con respuesta IA, sugerencias y matches relacionados
 * - API de historial de conversaciones recientes
 * - Multi-tenant: datos filtrados por tenant del usuario actual
 *
 * RUTAS:
 * - GET /funding/copilot -> chat()
 * - POST /api/v1/funding/copilot -> apiChat()
 * - GET /api/v1/funding/copilot/history -> apiHistory()
 *
 * @package Drupal\jaraba_funding\Controller
 */
class FundingCopilotController extends ControllerBase {

  /**
   * El servicio copilot de funding.
   *
   * @var \Drupal\jaraba_funding\Service\Intelligence\FundingCopilotService
   */
  protected FundingCopilotService $copilotService;

  /**
   * El usuario actual.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->copilotService = $container->get('jaraba_funding.copilot');
    $instance->currentUser = $container->get('current_user');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Renderiza la pagina del copilot de subvenciones.
   *
   * Pagina frontend (no admin) que muestra el chat interactivo
   * del copilot de subvenciones con historial y sugerencias.
   *
   * @return array
   *   Render array con #theme => 'page__funding'.
   */
  public function chat(): array {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenant_id = $tenant ? (int) $tenant->id() : 0;

    return [
      '#theme' => 'page__funding',
      '#tenant_id' => $tenant_id,
      '#labels' => [
        'title' => $this->t('Copilot de Subvenciones'),
        'subtitle' => $this->t('Asistente inteligente para encontrar y analizar subvenciones'),
        'chat_placeholder' => $this->t('Escriba su consulta sobre subvenciones...'),
        'submit' => $this->t('Enviar'),
        'suggestions_title' => $this->t('Sugerencias'),
        'history_title' => $this->t('Historial de conversaciones'),
        'related_matches' => $this->t('Convocatorias relacionadas'),
        'loading' => $this->t('Procesando consulta...'),
        'no_history' => $this->t('No hay conversaciones previas.'),
      ],
      '#urls' => [
        'api_chat' => Url::fromRoute('jaraba_funding.api.copilot.chat')->toString(),
        'api_history' => Url::fromRoute('jaraba_funding.api.copilot.history')->toString(),
        'dashboard' => Url::fromRoute('jaraba_funding.dashboard')->toString(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_funding/funding-dashboard',
        ],
        'drupalSettings' => [
          'jarabaFunding' => [
            'tenantId' => $tenant_id,
            'apiChatUrl' => Url::fromRoute('jaraba_funding.api.copilot.chat')->toString(),
            'apiHistoryUrl' => Url::fromRoute('jaraba_funding.api.copilot.history')->toString(),
            'copilotMode' => TRUE,
          ],
        ],
      ],
    ];
  }

  /**
   * Endpoint API: Chat del copilot de subvenciones.
   *
   * POST /api/v1/funding/copilot
   *
   * Recibe un mensaje del usuario, lo procesa con el servicio copilot
   * y devuelve la respuesta IA con sugerencias y matches relacionados.
   *
   * Body JSON esperado:
   * {
   *   "message": "Consulta del usuario sobre subvenciones",
   *   "context": {
   *     "previous_messages": [...],
   *     "current_filters": {...}
   *   }
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con body JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {response, suggestions, matches}}.
   */
  public function apiChat(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;
      $uid = (int) $this->currentUser->id();

      // Decodificar el body JSON.
      $content = json_decode($request->getContent(), TRUE);

      if (!$content || empty($content['message'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere el campo message.'),
        ], 400);
      }

      $message = mb_substr(trim($content['message']), 0, 2000);

      if (mb_strlen($message) < 3) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('El mensaje es demasiado corto. Proporcione al menos 3 caracteres.'),
        ], 400);
      }

      // Parsear contexto opcional.
      $context = [];
      if (!empty($content['context']) && is_array($content['context'])) {
        $context = $content['context'];
      }

      // Procesar via copilot service.
      $result = $this->copilotService->processMessage($message, $uid, $tenant_id, $context);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'response' => $result['response'] ?? '',
          'suggestions' => $result['suggestions'] ?? [],
          'matches' => $result['matches'] ?? [],
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error en copilot de funding: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al procesar la consulta del copilot.'),
      ], 500);
    }
  }

  /**
   * Endpoint API: Historial de conversaciones del copilot.
   *
   * GET /api/v1/funding/copilot/history
   *
   * Devuelve las conversaciones recientes del usuario actual
   * con el copilot de subvenciones.
   *
   * Query params:
   * - limit: int (maximo de conversaciones, default 10, max 50)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con query params.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {history: [...]}}.
   */
  public function apiHistory(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;
      $uid = (int) $this->currentUser->id();

      $limit = min(50, max(1, (int) $request->query->get('limit', 10)));

      // Obtener historial via copilot service.
      $history = $this->copilotService->getHistory($uid, $tenant_id, $limit);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'history' => $history,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error al obtener historial del copilot: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener el historial de conversaciones.'),
      ], 500);
    }
  }

}
