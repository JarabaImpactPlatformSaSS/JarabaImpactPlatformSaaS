<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\ContextualCopilotService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para la API del Copilot Contextual.
 */
class CopilotController extends ControllerBase
{

    /**
     * Copilot service.
     */
    protected ContextualCopilotService $copilotService;

    /**
     * Constructor.
     */
    public function __construct(ContextualCopilotService $copilotService)
    {
        $this->copilotService = $copilotService;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        return new static(
            $container->get('ecosistema_jaraba_core.contextual_copilot')
        );
    }

    /**
     * GET /api/v1/copilot/context - Obtener contexto de página. (AUDIT-CONS-N07)
     */
    public function getContext(): JsonResponse
    {
        $context = $this->copilotService->analyzeCurrentContext();
        return new JsonResponse($context);
    }

    /**
     * POST /api/v1/copilot/generate - Generar contenido con IA. (AUDIT-CONS-N07)
     */
    public function generateContent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);
        $type = $data['type'] ?? 'description';
        $context = $data['context'] ?? [];

        $content = $this->copilotService->generateContent($type, $context);

        return new JsonResponse([
            'success' => TRUE,
            'content' => $content,
            'type' => $type,
        ]);
    }

    /**
     * GET /api/v1/copilot/autocomplete/{field} - Autocompletado inteligente. (AUDIT-CONS-N07)
     */
    public function autocomplete(Request $request, string $field): JsonResponse
    {
        $query = $request->query->get('q', '');
        $tenantId = (int) $request->query->get('tenant_id', 0);

        $suggestions = $this->copilotService->getAutocomplete($field, $query, $tenantId);

        return new JsonResponse([
            'suggestions' => $suggestions,
        ]);
    }

}
