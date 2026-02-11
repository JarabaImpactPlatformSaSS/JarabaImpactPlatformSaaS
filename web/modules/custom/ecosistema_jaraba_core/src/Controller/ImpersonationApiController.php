<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\ImpersonationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para API de impersonación.
 *
 * Endpoints:
 * - POST /api/v1/admin/impersonate/start - Iniciar sesión
 * - POST /api/v1/admin/impersonate/end - Terminar sesión
 * - GET /api/v1/admin/impersonate/status - Estado actual
 */
class ImpersonationApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected ImpersonationService $impersonationService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('ecosistema_jaraba_core.impersonation'),
        );
    }

    /**
     * Inicia sesión de impersonación.
     *
     * POST /api/v1/admin/impersonate/start
     * Body: { "target_uid": 123, "reason": "Diagnóstico técnico" }
     */
    public function start(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['target_uid'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('target_uid es requerido'),
            ], 400);
        }

        $result = $this->impersonationService->startSession(
            (int) $data['target_uid'],
            $data['reason'] ?? ''
        );

        $statusCode = $result['success'] ? 200 : 400;
        return new JsonResponse($result, $statusCode);
    }

    /**
     * Termina sesión de impersonación.
     *
     * POST /api/v1/admin/impersonate/end
     */
    public function end(): JsonResponse
    {
        $result = $this->impersonationService->endSession();

        $statusCode = $result['success'] ? 200 : 400;
        return new JsonResponse($result, $statusCode);
    }

    /**
     * Obtiene estado de sesión actual.
     *
     * GET /api/v1/admin/impersonate/status
     */
    public function status(): JsonResponse
    {
        $isImpersonating = $this->impersonationService->isImpersonating();
        $sessionData = $this->impersonationService->getCurrentSessionData();

        return new JsonResponse([
            'is_impersonating' => $isImpersonating,
            'session' => $sessionData,
        ]);
    }

    /**
     * Lista logs de impersonación.
     *
     * GET /api/v1/admin/impersonate/logs
     */
    public function logs(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 0);
        $limit = min((int) $request->query->get('limit', 50), 100);

        $storage = $this->entityTypeManager()->getStorage('impersonation_audit_log');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->sort('event_time', 'DESC')
            ->range($page * $limit, $limit);

        $ids = $query->execute();
        $logs = $storage->loadMultiple($ids);

        $items = [];
        foreach ($logs as $log) {
            $items[] = [
                'id' => $log->id(),
                'admin' => $log->getAdmin()?->getDisplayName() ?? 'Unknown',
                'target' => $log->getTargetUser()?->getDisplayName() ?? 'Unknown',
                'event_type' => $log->getEventType(),
                'event_time' => $log->get('event_time')->value,
                'duration' => $log->getFormattedDuration(),
                'reason' => $log->get('reason')->value ?? '',
            ];
        }

        return new JsonResponse([
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'total' => count($items),
        ]);
    }

}
