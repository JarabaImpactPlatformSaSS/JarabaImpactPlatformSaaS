<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\jaraba_ai_agents\Agent\AgentInterface;
use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para Agentes IA.
 *
 * PROPÓSITO:
 * Expone endpoints REST para interactuar con los agentes IA del sistema.
 * Permite listar agentes disponibles, consultar sus acciones y ejecutar
 * operaciones IA con contexto multi-tenant.
 *
 * ENDPOINTS:
 * - GET /api/v1/agents: Lista todos los agentes disponibles
 * - GET /api/v1/agents/{agent_id}: Detalle de un agente específico
 * - GET /api/v1/agents/{agent_id}/actions: Acciones disponibles del agente
 * - POST /api/v1/agents/{agent_id}/execute: Ejecuta una acción del agente
 * AUDIT-CONS-N07: Added API versioning prefix.
 *
 * ARQUITECTURA:
 * - Utiliza AgentOrchestrator para gestión centralizada de agentes
 * - Soporta header X-Tenant-ID para contexto multi-tenant
 * - Retorna respuestas JSON estandarizadas
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class AgentApiController extends ControllerBase
{

    /**
     * El orquestador de agentes.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AgentOrchestrator
     */
    protected AgentOrchestrator $orchestrator;

    /**
     * Rate limiter service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\RateLimiterService
     */
    protected RateLimiterService $rateLimiter;

    /**
     * The tenant context service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Array de agentes disponibles.
     *
     * @var array<string, \Drupal\jaraba_ai_agents\Agent\AgentInterface>
     */
    protected array $agents = [];

    /**
     * Construye un AgentApiController.
     */
    public function __construct(
        AgentOrchestrator $orchestrator,
        RateLimiterService $rateLimiter,
        AgentInterface $marketingAgent,
        AgentInterface $storytellingAgent,
        AgentInterface $customerExperienceAgent,
        AgentInterface $supportAgent,
        TenantContextService $tenantContext,
    ) {
        $this->orchestrator = $orchestrator;
        $this->rateLimiter = $rateLimiter;
        $this->tenantContext = $tenantContext;

        // Registrar agentes en el orquestador.
        $this->agents = [
            'marketing_multi' => $marketingAgent,
            'storytelling' => $storytellingAgent,
            'customer_experience' => $customerExperienceAgent,
            'support' => $supportAgent,
        ];

        foreach ($this->agents as $agent) {
            $this->orchestrator->registerAgent($agent);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.orchestrator'),
            $container->get('ecosistema_jaraba_core.rate_limiter'),
            $container->get('jaraba_ai_agents.marketing_agent'),
            $container->get('jaraba_ai_agents.storytelling_agent'),
            $container->get('jaraba_ai_agents.customer_experience_agent'),
            $container->get('jaraba_ai_agents.support_agent'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Lista todos los agentes disponibles.
     *
     * Retorna un listado resumido de agentes con ID, etiqueta,
     * descripción y número de acciones disponibles.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con lista de agentes.
     */
    public function listAgents(): JsonResponse
    {
        $agents = [];

        foreach ($this->orchestrator->getAgents() as $agentId => $agent) {
            $agents[] = [
                'id' => $agentId,
                'label' => $agent->getLabel(),
                'description' => $agent->getDescription(),
                'actions_count' => count($agent->getAvailableActions()),
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $agents,
            'count' => count($agents),
        ]);
    }

    /**
     * Obtiene los detalles de un agente específico.
     *
     * Incluye todas las acciones disponibles con sus parámetros.
     *
     * @param string $agent_id
     *   El ID del agente a consultar.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con detalles del agente o error 404.
     */
    public function getAgent(string $agent_id): JsonResponse
    {
        $agent = $this->orchestrator->getAgent($agent_id);

        if (!$agent) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => "Agente no encontrado: {$agent_id}",
            ], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'id' => $agent->getAgentId(),
                'label' => $agent->getLabel(),
                'description' => $agent->getDescription(),
                'actions' => $agent->getAvailableActions(),
            ],
        ]);
    }

    /**
     * Obtiene las acciones disponibles de un agente.
     *
     * Útil para interfaces que necesitan mostrar opciones
     * al usuario antes de ejecutar.
     *
     * @param string $agent_id
     *   El ID del agente.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con lista de acciones o error 404.
     */
    public function getActions(string $agent_id): JsonResponse
    {
        $agent = $this->orchestrator->getAgent($agent_id);

        if (!$agent) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => "Agente no encontrado: {$agent_id}",
            ], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'agent_id' => $agent_id,
            'actions' => $agent->getAvailableActions(),
        ]);
    }

    /**
     * Ejecuta una acción de un agente.
     *
     * Procesa la petición JSON con la acción y contexto, establece
     * el tenant si se proporciona via header o body, y retorna
     * el resultado de la ejecución.
     *
     * Formato del body JSON:
     * {
     *   "action": "generate_content",
     *   "context": { ... },
     *   "tenant_id": "123",  // opcional
     *   "vertical": "empleo" // opcional
     * }
     *
     * @param string $agent_id
     *   El ID del agente a ejecutar.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con datos JSON.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado de la ejecución.
     */
    public function execute(string $agent_id, Request $request): JsonResponse
    {
        // AI-01: Rate limiting para proteger contra abuso y costes excesivos.
        $userId = (string) $this->currentUser()->id();
        $rateLimitResult = $this->rateLimiter->consume($userId, 'ai', 2);
        if (!$rateLimitResult['allowed']) {
            $response = new JsonResponse([
                'success' => FALSE,
                'error' => 'Demasiadas solicitudes. Por favor, inténtalo de nuevo más tarde.',
            ], 429);
            foreach ($this->rateLimiter->getHeaders($rateLimitResult) as $header => $value) {
                $response->headers->set($header, $value);
            }
            return $response;
        }

        $content = $request->getContent();
        $data = json_decode($content, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'JSON inválido en el cuerpo de la petición.',
            ], 400);
        }

        $action = $data['action'] ?? '';
        if (empty($action)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'El campo "action" es requerido.',
            ], 400);
        }

        $context = $data['context'] ?? [];
        // Soportar tenant via header X-Tenant-ID o body.
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->headers->get('X-Tenant-ID') ?? $data['tenant_id'] ?? NULL;
        $vertical = $data['vertical'] ?? 'general';

        $result = $this->orchestrator->execute(
            $agent_id,
            $action,
            $context,
            $tenantId,
            $vertical
        );

        $statusCode = $result['success'] ? 200 : 400;

        return new JsonResponse($result, $statusCode);
    }

}
