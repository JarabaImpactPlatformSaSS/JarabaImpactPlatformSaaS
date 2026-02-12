<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_job_board\Service\WebPushService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para API de Web Push.
 */
class PushApiController extends ControllerBase
{

    /**
     * Web Push service.
     */
    protected WebPushService $pushService;

    /**
     * Constructor.
     */
    public function __construct(WebPushService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_job_board.web_push')
        );
    }

    /**
     * Suscribe al usuario a push notifications.
     */
    public function subscribe(Request $request): JsonResponse
    {
        if ($this->currentUser()->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $content = $request->getContent();
        $subscription = json_decode($content, TRUE);

        if (empty($subscription['endpoint'])) {
            return new JsonResponse(['error' => 'Invalid subscription'], 400);
        }

        $userId = (int) $this->currentUser()->id();
        $success = $this->pushService->subscribe($userId, $subscription);

        if ($success) {
            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Subscribed successfully',
            ]);
        }

        return new JsonResponse(['error' => 'Failed to subscribe'], 500);
    }

    /**
     * Desuscribe al usuario.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        if ($this->currentUser()->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $content = $request->getContent();
        $data = json_decode($content, TRUE);
        $endpoint = $data['endpoint'] ?? '';

        if (empty($endpoint)) {
            return new JsonResponse(['error' => 'Endpoint required'], 400);
        }

        $userId = (int) $this->currentUser()->id();
        $success = $this->pushService->unsubscribe($userId, $endpoint);

        return new JsonResponse([
            'success' => $success,
            'message' => $success ? 'Unsubscribed' : 'Not found',
        ]);
    }

    /**
     * Obtiene la VAPID public key.
     */
    public function getVapidKey(): JsonResponse
    {
        $publicKey = $this->pushService->getVapidPublicKey();

        if (!$publicKey) {
            return new JsonResponse(['error' => 'VAPID not configured'], 503);
        }

        return new JsonResponse([
            'publicKey' => $publicKey,
        ]);
    }

    /**
     * Test endpoint para enviar push de prueba.
     */
    public function testPush(): JsonResponse
    {
        if ($this->currentUser()->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = (int) $this->currentUser()->id();
        $sent = $this->pushService->sendToUser($userId, [
            'title' => '🎉 Push Test!',
            'body' => 'Las notificaciones push están funcionando correctamente.',
            'tag' => 'test-' . time(),
            'data' => [
                'url' => '/my-applications',
                'test' => TRUE,
            ],
        ]);

        return new JsonResponse([
            'success' => $sent > 0,
            'sent_count' => $sent,
        ]);
    }

}
