<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_tenant_knowledge\Service\FaqBotService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller para el FAQ Bot público (G114-4).
 *
 * Endpoints públicos sin autenticación con rate limiting:
 * - POST /api/v1/help/chat — Chat con el FAQ Bot
 * - POST /api/v1/help/chat/feedback — Feedback de respuestas
 *
 * Patrón basado en PublicCopilotController pero enfocado
 * en respuestas grounded en la KB del tenant.
 */
class FaqBotApiController extends ControllerBase {

  /**
   * Rate limit: requests por IP por minuto.
   */
  protected const RATE_LIMIT = 10;

  /**
   * Constructor.
   */
  public function __construct(
    protected FloodInterface $flood,
    protected FaqBotService $faqBotService,
    EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('flood'),
      $container->get('jaraba_tenant_knowledge.faq_bot'),
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Endpoint de chat del FAQ Bot.
   *
   * POST /api/v1/help/chat
   * Body: { message: string, session_id?: string, tenant_id: int }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON.
   */
  public function chat(Request $request): JsonResponse {
    $clientIp = $request->getClientIp();
    $floodName = 'faq_bot_chat';

    // Rate limiting: 10 req/min por IP.
    if (!$this->flood->isAllowed($floodName, self::RATE_LIMIT, 60, $clientIp)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('Has alcanzado el límite de consultas. Inténtalo de nuevo en un minuto.'),
        'rate_limited' => TRUE,
      ], 429);
    }

    $this->flood->register($floodName, 60, $clientIp);

    // Parsear request.
    $content = json_decode($request->getContent(), TRUE);
    $message = trim($content['message'] ?? '');
    $sessionId = $content['session_id'] ?? NULL;

    // AUDIT-SEC-N07: Resolver tenant_id desde el contexto del servidor (host),
    // NO desde el body del cliente. Previene IDOR cross-tenant en KB.
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();
    if ($tenantId <= 0) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('No se pudo determinar el tenant.'),
      ], 403);
    }

    // Validar mensaje.
    if (empty($message)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('El mensaje no puede estar vacío.'),
      ], 400);
    }

    if (mb_strlen($message) > 500) {
      $message = mb_substr($message, 0, 500);
    }

    // Llamar al servicio.
    $result = $this->faqBotService->chat($message, $tenantId, $sessionId ?: NULL);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $result,
    ]);
  }

  /**
   * Endpoint de feedback del FAQ Bot.
   *
   * POST /api/v1/help/chat/feedback
   * Body: { rating: 'up'|'down', session_id: string, message_text: string }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON.
   */
  public function feedback(Request $request): JsonResponse {
    // AUDIT-SEC-N15: Rate limiting en feedback para prevenir spam/flood.
    $clientIp = $request->getClientIp();
    if (!$this->flood->isAllowed('faq_bot_feedback', 20, 60, $clientIp)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('Demasiadas solicitudes. Inténtalo de nuevo en un minuto.'),
      ], 429);
    }
    $this->flood->register('faq_bot_feedback', 60, $clientIp);

    $content = json_decode($request->getContent(), TRUE);

    $rating = $content['rating'] ?? NULL;
    $sessionId = $content['session_id'] ?? '';
    $messageText = $content['message_text'] ?? '';

    if (!in_array($rating, ['up', 'down'], TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => (string) $this->t('Rating no válido.'),
      ], 400);
    }

    // Log para análisis.
    \Drupal::logger('jaraba_tenant_knowledge')->info(
      'FAQ Bot feedback: @rating | Session: @session | Message: @message',
      [
        '@rating' => $rating,
        '@session' => mb_substr($sessionId, 0, 50),
        '@message' => mb_substr($messageText, 0, 200),
      ]
    );

    return new JsonResponse([
      'success' => TRUE,
      'message' => (string) $this->t('¡Gracias por tu feedback!'),
    ]);
  }

}
