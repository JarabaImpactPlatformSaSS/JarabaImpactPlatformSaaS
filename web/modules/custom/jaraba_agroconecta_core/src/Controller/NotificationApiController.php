<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\NotificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para notificaciones de AgroConecta.
 *
 * PROPÓSITO:
 * Expone endpoints JSON para envío de notificaciones, gestión de
 * preferencias del usuario y consulta de logs de auditoría.
 *
 * ENDPOINTS AUTENTICADOS:
 * - GET  /api/v1/agro/notifications/preferences  → Mis preferencias
 * - POST /api/v1/agro/notifications/preferences  → Actualizar preferencias
 *
 * ENDPOINTS ADMIN:
 * - POST /api/v1/agro/notifications/send  → Enviar notificación
 * - GET  /api/v1/agro/notifications/logs  → Logs de envío
 * - GET  /api/v1/agro/notifications/metrics  → Métricas de notificaciones
 */
class NotificationApiController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected NotificationService $notificationService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.notification_service'), // AUDIT-CONS-N05: canonical prefix
        );
    }

    /**
     * Obtiene las preferencias de notificación del usuario autenticado.
     *
     * GET /api/v1/agro/notifications/preferences?type=order_confirmed
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con canales habilitados.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $type = $request->query->get('type', '');

        if (empty($type)) {
            return new JsonResponse([
                'error' => 'Parámetro type es requerido.',
            ], 400);
        }

        $userId = (int) $this->currentUser()->id();
        $channels = $this->notificationService->getEnabledChannels($userId, $type);

        return new JsonResponse([
            'data' => [
                'notification_type' => $type,
                'enabled_channels' => $channels,
                'all_channels' => [
                    'email' => in_array('email', $channels),
                    'push' => in_array('push', $channels),
                    'sms' => in_array('sms', $channels),
                    'in_app' => in_array('in_app', $channels),
                ],
            ],
        ]);
    }

    /**
     * Actualiza las preferencias de notificación del usuario.
     *
     * POST /api/v1/agro/notifications/preferences
     * Body: { type: "order_confirmed", channels: { email: true, push: false, sms: false, in_app: true } }
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['type']) || !isset($data['channels'])) {
            return new JsonResponse([
                'error' => 'Campos type y channels son requeridos.',
            ], 400);
        }

        $userId = (int) $this->currentUser()->id();
        $success = $this->notificationService->updatePreferences(
            $userId,
            $data['type'],
            $data['channels']
        );

        if (!$success) {
            return new JsonResponse([
                'error' => 'Error al actualizar las preferencias.',
            ], 500);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Preferencias actualizadas correctamente.',
        ]);
    }

    /**
     * Envía una notificación (endpoint administrativo).
     *
     * POST /api/v1/agro/notifications/send
     * Body: { type: "order_confirmed", recipient_id: 123, context: {...} }
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con IDs de logs creados.
     */
    public function send(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['type']) || empty($data['recipient_id'])) {
            return new JsonResponse([
                'error' => 'Campos type y recipient_id son requeridos.',
            ], 400);
        }

        $logIds = $this->notificationService->send(
            $data['type'],
            (int) $data['recipient_id'],
            $data['context'] ?? [],
            $data['channel'] ?? NULL
        );

        return new JsonResponse([
            'success' => TRUE,
            'message' => count($logIds) . ' notificación(es) enviada(s).',
            'log_ids' => $logIds,
        ]);
    }

    /**
     * Obtiene logs de notificaciones con paginación y filtros.
     *
     * GET /api/v1/agro/notifications/logs?type=X&status=Y&limit=50&offset=0
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con logs paginados.
     */
    public function logs(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 50), 100);
        $offset = max((int) $request->query->get('offset', 0), 0);
        $type = $request->query->get('type') ?: NULL;
        $status = $request->query->get('status') ?: NULL;

        $result = $this->notificationService->getLogs($limit, $offset, $type, $status);

        return new JsonResponse($result);
    }

    /**
     * Obtiene métricas de notificaciones.
     *
     * GET /api/v1/agro/notifications/metrics
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con KPIs.
     */
    public function metrics(): JsonResponse
    {
        $metrics = $this->notificationService->getMetrics();

        return new JsonResponse(['data' => $metrics]);
    }

}
