<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\PlatformPushService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST para la API de notificaciones push.
 *
 * PROPÓSITO:
 * Expone endpoints REST para gestionar suscripciones de notificaciones
 * push desde el navegador del usuario. Implementa la interfaz del servidor
 * para la Push API (W3C) del lado del cliente.
 *
 * ENDPOINTS:
 * - POST /api/v1/push/subscribe: Registra una nueva suscripción push.
 * - DELETE /api/v1/push/unsubscribe: Desregistra una suscripción por endpoint.
 * - POST /api/v1/push/test: Envía una notificación de prueba (solo admin).
 *
 * SEGURIDAD:
 * - Todos los endpoints requieren autenticación (usuario logueado).
 * - El endpoint de test requiere permiso 'administer tenants'.
 * - Las operaciones están limitadas al usuario actual.
 *
 * PHASE 5 - G109-3: Push Notifications
 */
class PushApiController extends ControllerBase {

  /**
   * Servicio de notificaciones push.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\PlatformPushService
   */
  protected PlatformPushService $pushService;

  /**
   * Canal de log para push notifications.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pushService = $container->get('ecosistema_jaraba_core.platform_push');
    $instance->currentUser = $container->get('current_user');
    $instance->logger = $container->get('logger.channel.ecosistema_jaraba_core');
    return $instance;
  }

  /**
   * Registra una suscripción push para el usuario actual.
   *
   * Recibe los datos de la suscripción del navegador (Push API)
   * y crea o reactiva una entidad PushSubscription.
   *
   * Body JSON esperado:
   * {
   *   "endpoint": "https://fcm.googleapis.com/fcm/send/...",
   *   "keys": {
   *     "auth": "base64url...",
   *     "p256dh": "base64url..."
   *   }
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con los datos de suscripción.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado de la operación.
   */
  public function subscribe(Request $request): JsonResponse {
    // Verificar autenticación.
    if ($this->currentUser->isAnonymous()) {
      return new JsonResponse([
        'error' => 'Se requiere autenticación.',
        'code' => 'UNAUTHENTICATED',
      ], 401);
    }

    // Decodificar el body JSON.
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data)) {
      return new JsonResponse([
        'error' => 'Cuerpo de la petición inválido. Se espera JSON.',
        'code' => 'INVALID_BODY',
      ], 400);
    }

    // Validar campos requeridos.
    if (empty($data['endpoint'])) {
      return new JsonResponse([
        'error' => 'El campo endpoint es obligatorio.',
        'code' => 'MISSING_ENDPOINT',
      ], 400);
    }

    if (empty($data['keys']['auth']) || empty($data['keys']['p256dh'])) {
      return new JsonResponse([
        'error' => 'Las claves auth y p256dh son obligatorias.',
        'code' => 'MISSING_KEYS',
      ], 400);
    }

    // Validar formato del endpoint (debe ser URL HTTPS).
    if (!filter_var($data['endpoint'], FILTER_VALIDATE_URL) || !str_starts_with($data['endpoint'], 'https://')) {
      return new JsonResponse([
        'error' => 'El endpoint debe ser una URL HTTPS válida.',
        'code' => 'INVALID_ENDPOINT',
      ], 400);
    }

    try {
      $userId = (int) $this->currentUser->id();

      // Añadir metadatos adicionales.
      $data['user_agent'] = $request->headers->get('User-Agent', '');

      $subscription = $this->pushService->subscribe($userId, $data);

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Suscripción push registrada correctamente.',
        'subscription_id' => $subscription->id(),
      ], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear suscripción push: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Error interno al registrar la suscripción.',
        'code' => 'INTERNAL_ERROR',
      ], 500);
    }
  }

  /**
   * Desregistra una suscripción push del usuario actual.
   *
   * Body JSON esperado:
   * {
   *   "endpoint": "https://fcm.googleapis.com/fcm/send/..."
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con el endpoint a desregistrar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado de la operación.
   */
  public function unsubscribe(Request $request): JsonResponse {
    // Verificar autenticación.
    if ($this->currentUser->isAnonymous()) {
      return new JsonResponse([
        'error' => 'Se requiere autenticación.',
        'code' => 'UNAUTHENTICATED',
      ], 401);
    }

    // Decodificar el body JSON.
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['endpoint'])) {
      return new JsonResponse([
        'error' => 'El campo endpoint es obligatorio.',
        'code' => 'MISSING_ENDPOINT',
      ], 400);
    }

    try {
      $userId = (int) $this->currentUser->id();
      $result = $this->pushService->unsubscribe($userId, $data['endpoint']);

      if ($result) {
        return new JsonResponse([
          'success' => TRUE,
          'message' => 'Suscripción push desactivada correctamente.',
        ]);
      }

      return new JsonResponse([
        'error' => 'No se encontró la suscripción para este endpoint.',
        'code' => 'NOT_FOUND',
      ], 404);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al desactivar suscripción push: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Error interno al desactivar la suscripción.',
        'code' => 'INTERNAL_ERROR',
      ], 500);
    }
  }

  /**
   * Envía una notificación de prueba al usuario actual.
   *
   * Solo accesible para administradores. Útil para verificar que
   * las claves VAPID y las suscripciones funcionan correctamente.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el resultado del envío.
   */
  public function test(Request $request): JsonResponse {
    // Verificar autenticación.
    if ($this->currentUser->isAnonymous()) {
      return new JsonResponse([
        'error' => 'Se requiere autenticación.',
        'code' => 'UNAUTHENTICATED',
      ], 401);
    }

    // Verificar permiso de administrador.
    if (!$this->currentUser->hasPermission('administer tenants')) {
      return new JsonResponse([
        'error' => 'Se requiere permiso de administración.',
        'code' => 'FORBIDDEN',
      ], 403);
    }

    try {
      $userId = (int) $this->currentUser->id();

      $sent = $this->pushService->sendToUser(
        $userId,
        'Notificación de Prueba',
        'Esta es una notificación push de prueba desde Jaraba Impact Platform.',
        [
          'url' => '/admin/config/push-subscriptions',
          'type' => 'test',
        ]
      );

      return new JsonResponse([
        'success' => TRUE,
        'message' => "Notificación de prueba enviada a {$sent} dispositivo(s).",
        'sent_count' => $sent,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al enviar notificación de prueba: @error', [
        '@error' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Error interno al enviar la notificación de prueba.',
        'code' => 'INTERNAL_ERROR',
      ], 500);
    }
  }

}
