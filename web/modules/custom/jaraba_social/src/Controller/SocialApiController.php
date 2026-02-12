<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Controller;

use Drupal\Core\Controller\ControllerBase;
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
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_social.post_service'),
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
            'tenant_id' => $data['tenant_id'] ?? NULL,
        ]);

        return new JsonResponse($result);
    }

    /**
     * Estadísticas de posts.
     */
    public function stats(Request $request): JsonResponse
    {
        $tenantId = $request->query->get('tenant_id');

        $stats = $this->postService->getStats(
            $tenantId ? (int) $tenantId : NULL
        );

        return new JsonResponse([
            'success' => TRUE,
            'data' => $stats,
        ]);
    }

}
