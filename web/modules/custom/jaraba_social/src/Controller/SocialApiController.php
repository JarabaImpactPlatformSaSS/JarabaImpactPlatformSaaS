<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\jaraba_social\Service\SocialPostService;

/**
 * API Controller para Social Manager.
 */
class SocialApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected SocialPostService $postService,
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_social.post_service'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Genera contenido para un post usando IA.
     */
    public function generate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE) ?? [];

        $prompt = $data['prompt'] ?? '';
        $platform = $data['platform'] ?? 'linkedin';

        if (empty($prompt)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Prompt is required.',
            ], 400);
        }

        $result = $this->postService->generateContent($prompt, $platform, [
            'tenant_id' => $this->tenantContext->getCurrentTenantId() ?? ($data['tenant_id'] ?? NULL),
        ]);

        return new JsonResponse($result);
    }

    /**
     * Calendario de publicaciones programadas.
     */
    public function calendar(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? NULL;
        $posts = $this->postService->getScheduledPosts($tenantId);

        return new JsonResponse(['success' => TRUE, 'data' => $posts]);
    }

    /**
     * Reprogramar publicacion.
     */
    public function reschedule(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE) ?? [];
        $postId = $data['post_id'] ?? NULL;
        $newDate = $data['scheduled_at'] ?? NULL;

        if (!$postId || !$newDate) {
            return new JsonResponse(['success' => FALSE, 'error' => 'post_id and scheduled_at are required.'], 422);
        }

        $result = $this->postService->reschedulePost((int) $postId, $newDate);

        return new JsonResponse(['success' => TRUE, 'data' => $result]);
    }

    /**
     * Metricas de analytics de redes sociales.
     */
    public function analyticsMetrics(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? NULL;
        $days = (int) $request->query->get('days', '30');
        $metrics = $this->postService->getAnalyticsMetrics($tenantId, $days);

        return new JsonResponse(['success' => TRUE, 'data' => $metrics]);
    }

    /**
     * Rendimiento de un post individual.
     */
    public function postPerformance(int $post_id): JsonResponse
    {
        $metrics = $this->postService->getPostPerformance($post_id);

        return new JsonResponse(['success' => TRUE, 'data' => $metrics]);
    }

    /**
     * Posts con mejor rendimiento.
     */
    public function topPosts(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? NULL;
        $limit = min(50, max(1, (int) $request->query->get('limit', '10')));
        $posts = $this->postService->getTopPosts($tenantId, $limit);

        return new JsonResponse(['success' => TRUE, 'data' => $posts]);
    }

    /**
     * Publicar via Make.com (Integromat).
     */
    public function makecomPublish(int $post_id): JsonResponse
    {
        $result = $this->postService->publishViaMakecom($post_id);

        return new JsonResponse(['success' => TRUE, 'data' => $result]);
    }

    /**
     * Webhook de Make.com para recibir resultados.
     */
    public function makecomWebhook(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE) ?? [];
        $this->postService->processMakecomWebhook($data);

        return new JsonResponse(['success' => TRUE]);
    }

    /**
     * Estadísticas de posts.
     */
    public function stats(Request $request): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? ($request->query->get('tenant_id') ? (int) $request->query->get('tenant_id') : NULL);

        $stats = $this->postService->getStats(
            $tenantId
        );

        return new JsonResponse([
            'success' => TRUE,
            'data' => $stats,
        ]);
    }

}
