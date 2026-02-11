<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\SandboxTenantService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para la API de Sandbox/Demo.
 *
 * Endpoints para crear y gestionar sandboxes temporales.
 */
class SandboxController extends ControllerBase
{

    /**
     * Sandbox service.
     */
    protected SandboxTenantService $sandboxService;

    /**
     * Constructor.
     */
    public function __construct(SandboxTenantService $sandboxService)
    {
        $this->sandboxService = $sandboxService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        return new static(
            $container->get('ecosistema_jaraba_core.sandbox_tenant')
        );
    }

    /**
     * POST /api/sandbox/create - Crear nuevo sandbox.
     */
    public function createSandbox(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $template = $data['template'] ?? 'agroconecta';

        $sandbox = $this->sandboxService->createSandbox($template);

        return new JsonResponse([
            'success' => TRUE,
            'sandbox' => $sandbox,
            'message' => 'Sandbox created successfully. Expires in 24 hours.',
        ]);
    }

    /**
     * GET /api/sandbox/{id} - Obtener datos del sandbox.
     */
    public function get(string $id): JsonResponse
    {
        $sandbox = $this->sandboxService->getSandbox($id);

        if (!$sandbox) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Sandbox not found or expired',
            ], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'sandbox' => $sandbox,
        ]);
    }

    /**
     * POST /api/sandbox/{id}/track - Registrar engagement.
     */
    public function track(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $action = $data['action'] ?? 'unknown';
        $metadata = $data['metadata'] ?? [];

        $this->sandboxService->trackEngagement($id, $action, $metadata);

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Action tracked',
        ]);
    }

    /**
     * POST /api/sandbox/{id}/convert - Convertir a cuenta real.
     */
    public function convert(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        $result = $this->sandboxService->convertToAccount($id, $data);

        return new JsonResponse($result);
    }

    /**
     * GET /api/sandbox/templates - Listar templates disponibles.
     */
    public function templates(): JsonResponse
    {
        $templates = $this->sandboxService->getAvailableTemplates();

        return new JsonResponse([
            'success' => TRUE,
            'templates' => $templates,
        ]);
    }

    /**
     * GET /api/sandbox/stats - Estadísticas de sandboxes.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->sandboxService->getStatistics();

        return new JsonResponse([
            'success' => TRUE,
            'statistics' => $stats,
        ]);
    }

}
