<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Agent\AgentInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio orquestador para Agentes IA.
 *
 * PROPÓSITO:
 * Gestiona el descubrimiento, registro, ejecución y logging de agentes IA.
 * Actúa como el punto central de coordinación para todas las operaciones
 * de agentes en el sistema.
 *
 * RESPONSABILIDADES:
 * - Registro y descubrimiento de agentes disponibles
 * - Validación de acciones y parámetros requeridos antes de ejecutar
 * - Establecimiento de contexto de tenant para personalización
 * - Logging de ejecuciones para observabilidad
 * - Enriquecimiento de respuestas con metadata
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class AgentOrchestrator
{

    /**
     * El gestor de proveedores IA.
     *
     * @var \Drupal\ai\AiProviderPluginManager
     */
    protected AiProviderPluginManager $aiProvider;

    /**
     * La factoría de configuración.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * El servicio de Brand Voice por tenant.
     *
     * @var \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService
     */
    protected TenantBrandVoiceService $brandVoice;

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Agentes registrados.
     *
     * Mapa de agent_id => instancia AgentInterface.
     *
     * @var array<string, \Drupal\jaraba_ai_agents\Agent\AgentInterface>
     */
    protected array $agents = [];

    /**
     * Construye un AgentOrchestrator.
     *
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   El gestor de proveedores IA.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoría de configuración.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     * @param \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService $brandVoice
     *   El servicio de Brand Voice por tenant.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        EntityTypeManagerInterface $entityTypeManager,
    ) {
        $this->aiProvider = $aiProvider;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
        $this->brandVoice = $brandVoice;
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Registra un agente en el orquestador.
     *
     * Añade el agente al registro interno para que esté disponible
     * para ejecución via execute() o consulta via getAgent().
     *
     * @param \Drupal\jaraba_ai_agents\Agent\AgentInterface $agent
     *   El agente a registrar.
     */
    public function registerAgent(AgentInterface $agent): void
    {
        $this->agents[$agent->getAgentId()] = $agent;
    }

    /**
     * Obtiene todos los agentes registrados.
     *
     * @return array<string, \Drupal\jaraba_ai_agents\Agent\AgentInterface>
     *   Array de agentes indexados por ID.
     */
    public function getAgents(): array
    {
        return $this->agents;
    }

    /**
     * Obtiene un agente específico por ID.
     *
     * @param string $agentId
     *   El ID del agente a buscar.
     *
     * @return \Drupal\jaraba_ai_agents\Agent\AgentInterface|null
     *   El agente encontrado o NULL si no existe.
     */
    public function getAgent(string $agentId): ?AgentInterface
    {
        return $this->agents[$agentId] ?? NULL;
    }

    /**
     * Ejecuta una acción de un agente.
     *
     * Proceso de ejecución:
     * 1. Valida que el agente exista
     * 2. Verifica que la acción esté disponible
     * 3. Valida campos requeridos en el contexto
     * 4. Establece contexto de tenant si se proporciona
     * 5. Ejecuta la acción y registra métricas
     * 6. Enriquece respuesta con metadata
     *
     * @param string $agentId
     *   El ID del agente a ejecutar.
     * @param string $action
     *   El ID de la acción a ejecutar.
     * @param array $context
     *   Contexto y parámetros para la ejecución.
     * @param string|null $tenantId
     *   ID del tenant para personalización (opcional).
     * @param string|null $vertical
     *   Vertical del tenant (opcional).
     *
     * @return array
     *   Resultado de la ejecución con:
     *   - 'success': bool
     *   - 'data' o 'error': Datos de respuesta o mensaje de error
     *   - 'metadata': Información de la ejecución
     */
    public function execute(
        string $agentId,
        string $action,
        array $context,
        ?string $tenantId = NULL,
        ?string $vertical = NULL,
    ): array {
        $agent = $this->getAgent($agentId);

        if (!$agent) {
            $this->logger->error('Agente no encontrado: @agent', ['@agent' => $agentId]);
            return [
                'success' => FALSE,
                'error' => "Agente no encontrado: {$agentId}",
            ];
        }

        // Verificar que la acción esté disponible.
        $availableActions = $agent->getAvailableActions();
        if (!isset($availableActions[$action])) {
            return [
                'success' => FALSE,
                'error' => "Acción no disponible: {$action}",
                'available_actions' => array_keys($availableActions),
            ];
        }

        // Validar campos requeridos.
        $required = $availableActions[$action]['requires'] ?? [];
        $missing = [];
        foreach ($required as $field) {
            if (!isset($context[$field]) || empty($context[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return [
                'success' => FALSE,
                'error' => 'Campos requeridos faltantes: ' . implode(', ', $missing),
                'required_fields' => $required,
            ];
        }

        // Establecer contexto de tenant para personalización.
        if ($tenantId && $vertical) {
            $agent->setTenantContext($tenantId, $vertical);
        }

        // Ejecutar y medir tiempo.
        $startTime = microtime(TRUE);
        $result = $agent->execute($action, $context);
        $duration = microtime(TRUE) - $startTime;

        // Registrar ejecución en logs.
        $this->logExecution($agentId, $action, $result['success'], $duration, $tenantId);

        // Añadir metadata de la ejecución.
        $result['metadata'] = [
            'agent_id' => $agentId,
            'action' => $action,
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => date('c'),
        ];

        return $result;
    }

    /**
     * Obtiene todas las acciones disponibles de todos los agentes.
     *
     * Útil para generar documentación o interfaces de descubrimiento.
     *
     * @return array
     *   Acciones agrupadas por agente, cada una con:
     *   - 'label': Etiqueta del agente
     *   - 'description': Descripción del agente
     *   - 'actions': Array de acciones disponibles
     */
    public function getAllActions(): array
    {
        $actions = [];

        foreach ($this->agents as $agentId => $agent) {
            $actions[$agentId] = [
                'label' => $agent->getLabel(),
                'description' => $agent->getDescription(),
                'actions' => $agent->getAvailableActions(),
            ];
        }

        return $actions;
    }

    /**
     * Registra una ejecución en el log.
     *
     * @param string $agentId
     *   El ID del agente ejecutado.
     * @param string $action
     *   La acción ejecutada.
     * @param bool $success
     *   Si la ejecución fue exitosa.
     * @param float $duration
     *   Duración en segundos.
     * @param string|null $tenantId
     *   El ID del tenant (si aplica).
     */
    protected function logExecution(
        string $agentId,
        string $action,
        bool $success,
        float $duration,
        ?string $tenantId,
    ): void {
        $this->logger->info('Ejecución de agente: @agent/@action - @status en @duration ms (tenant: @tenant)', [
            '@agent' => $agentId,
            '@action' => $action,
            '@status' => $success ? 'ÉXITO' : 'FALLO',
            '@duration' => round($duration * 1000, 2),
            '@tenant' => $tenantId ?? 'global',
        ]);
    }

}
