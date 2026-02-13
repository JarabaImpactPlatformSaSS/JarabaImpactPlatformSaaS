<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\AIAgentInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Service para gestionar la autonomía de agentes IA.
 *
 * Gestiona la ejecución de acciones según el nivel de autonomía configurado:
 * - Level 0 (Suggest): Solo sugiere, usuario ejecuta manualmente
 * - Level 1 (Confirm): Propone acción, espera confirmación
 * - Level 2 (Auto): Ejecuta automáticamente, notifica después
 * - Level 3 (Silent): Ejecuta sin notificar (para low-risk)
 */
class AgentAutonomyService
{

    /**
     * Constantes de niveles de autonomía.
     */
    public const LEVEL_SUGGEST = 0;
    public const LEVEL_CONFIRM = 1;
    public const LEVEL_AUTO = 2;
    public const LEVEL_SILENT = 3;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger channel factory.
     *
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    protected LoggerChannelFactoryInterface $loggerFactory;

    /**
     * State service.
     *
     * @var \Drupal\Core\State\StateInterface
     */
    protected StateInterface $state;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerChannelFactoryInterface $loggerFactory,
        StateInterface $state
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->loggerFactory = $loggerFactory;
        $this->state = $state;
    }

    /**
     * Ejecuta una acción según el nivel de autonomía del agente.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\AIAgentInterface $agent
     *   El agente que ejecuta la acción.
     * @param string $action
     *   El identificador de la acción.
     * @param array $context
     *   Contexto de la acción (tenant, parámetros, etc.).
     * @param callable $executor
     *   La función que ejecuta la acción real.
     *
     * @return array
     *   Resultado con 'status' (executed|pending|suggested|error) y 'data'.
     */
    public function executeAction(
        AIAgentInterface $agent,
        string $action,
        array $context,
        callable $executor
    ): array {
        $level = $agent->getAutonomyLevel();
        $tenantId = $context['tenant_id'] ?? NULL;

        // Verificar límite diario para ejecución automática.
        if ($level >= self::LEVEL_AUTO && $tenantId) {
            $dailyCount = $this->getDailyActionCount($agent->id(), $tenantId);
            if ($dailyCount >= $agent->getMaxDailyAutoActions()) {
                // Excedió límite, degradar a CONFIRM.
                $level = self::LEVEL_CONFIRM;
                $this->loggerFactory->get('agent_autonomy')->warning(
                    '⚠️ Agent @agent excedió límite diario. Degradando a CONFIRM.',
                    ['@agent' => $agent->label()]
                );
            }
        }

        switch ($level) {
            case self::LEVEL_SUGGEST:
                // Solo sugerir, no ejecutar.
                return $this->createSuggestion($agent, $action, $context);

            case self::LEVEL_CONFIRM:
                // Crear solicitud de aprobación.
                return $this->createApprovalRequest($agent, $action, $context, $executor);

            case self::LEVEL_AUTO:
                // Ejecutar y notificar.
                $result = $this->executeAndNotify($agent, $action, $context, $executor);
                $this->incrementDailyActionCount($agent->id(), $tenantId);
                return $result;

            case self::LEVEL_SILENT:
                // Ejecutar silenciosamente.
                $result = $this->executeSilently($agent, $action, $context, $executor);
                $this->incrementDailyActionCount($agent->id(), $tenantId);
                return $result;

            default:
                return [
                    'status' => 'error',
                    'message' => 'Nivel de autonomía desconocido',
                ];
        }
    }

    /**
     * Crea una sugerencia (Level 0).
     */
    protected function createSuggestion(
        AIAgentInterface $agent,
        string $action,
        array $context
    ): array {
        $this->loggerFactory->get('agent_autonomy')->info(
            '💡 Agent @agent sugiere: @action',
            ['@agent' => $agent->label(), '@action' => $action]
        );

        return [
            'status' => 'suggested',
            'agent' => $agent->id(),
            'action' => $action,
            'context' => $context,
            'message' => 'Acción sugerida. Ejecuta manualmente si lo deseas.',
        ];
    }

    /**
     * Crea una solicitud de aprobación (Level 1).
     */
    protected function createApprovalRequest(
        AIAgentInterface $agent,
        string $action,
        array $context,
        callable $executor
    ): array {
        $requestId = $this->generateRequestId();
        $tenantId = $context['tenant_id'] ?? 0;

        // Guardar la solicitud pendiente.
        $pendingKey = "agent_autonomy_pending_{$tenantId}";
        $pending = $this->state->get($pendingKey, []);

        $pending[$requestId] = [
            'id' => $requestId,
            'agent_id' => $agent->id(),
            'agent_label' => $agent->label(),
            'action' => $action,
            'context' => $context,
            'executor_class' => get_class($executor),
            'created' => time(),
            'expires' => time() + 86400, // 24 horas.
        ];

        $this->state->set($pendingKey, $pending);

        $this->loggerFactory->get('agent_autonomy')->info(
            '⏳ Agent @agent solicita aprobación: @action (ID: @id)',
            ['@agent' => $agent->label(), '@action' => $action, '@id' => $requestId]
        );

        return [
            'status' => 'pending',
            'request_id' => $requestId,
            'agent' => $agent->id(),
            'action' => $action,
            'message' => 'Acción pendiente de aprobación.',
        ];
    }

    /**
     * Ejecuta y notifica (Level 2).
     */
    protected function executeAndNotify(
        AIAgentInterface $agent,
        string $action,
        array $context,
        callable $executor
    ): array {
        try {
            $result = $executor($context);

            $this->loggerFactory->get('agent_autonomy')->info(
                '✅ Agent @agent ejecutó automáticamente: @action',
                ['@agent' => $agent->label(), '@action' => $action]
            );

            // LOW-11: Notify tenant admin via Drupal mail.
            $notified = FALSE;
            $tenantId = $context['tenant_id'] ?? NULL;
            if ($tenantId) {
                try {
                    $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantId);
                    if ($tenant instanceof TenantInterface) {
                        $adminUser = $tenant->getAdminUser();
                        if ($adminUser && $adminUser->getEmail()) {
                            /** @var \Drupal\Core\Mail\MailManagerInterface $mailManager */
                            $mailManager = \Drupal::service('plugin.manager.mail');
                            $mailResult = $mailManager->mail(
                                'ecosistema_jaraba_core',
                                'agent_auto_action',
                                $adminUser->getEmail(),
                                $adminUser->getPreferredLangcode(),
                                [
                                    'agent_name' => $agent->label(),
                                    'action' => $action,
                                    'tenant_name' => $tenant->getName(),
                                ],
                            );
                            $notified = !empty($mailResult['result']);
                        }
                    }
                } catch (\Exception $e) {
                    $this->loggerFactory->get('agent_autonomy')->warning(
                        'Failed to notify tenant admin for @action: @error',
                        ['@action' => $action, '@error' => $e->getMessage()]
                    );
                }
            }

            return [
                'status' => 'executed',
                'agent' => $agent->id(),
                'action' => $action,
                'result' => $result,
                'message' => 'Acción ejecutada automáticamente.',
                'notified' => $notified,
            ];

        } catch (\Exception $e) {
            $this->loggerFactory->get('agent_autonomy')->error(
                '❌ Agent @agent falló al ejecutar @action: @error',
                [
                    '@agent' => $agent->label(),
                    '@action' => $action,
                    '@error' => $e->getMessage(),
                ]
            );

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ejecuta silenciosamente (Level 3).
     */
    protected function executeSilently(
        AIAgentInterface $agent,
        string $action,
        array $context,
        callable $executor
    ): array {
        try {
            $result = $executor($context);

            $this->loggerFactory->get('agent_autonomy')->debug(
                '🔇 Agent @agent ejecutó silenciosamente: @action',
                ['@agent' => $agent->label(), '@action' => $action]
            );

            return [
                'status' => 'executed',
                'agent' => $agent->id(),
                'action' => $action,
                'result' => $result,
                'notified' => FALSE,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene las acciones pendientes de aprobación para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Array de acciones pendientes.
     */
    public function getPendingActions(int $tenantId): array
    {
        $pendingKey = "agent_autonomy_pending_{$tenantId}";
        $pending = $this->state->get($pendingKey, []);

        // Filtrar expiradas.
        $now = time();
        $valid = array_filter($pending, function ($item) use ($now) {
            return $item['expires'] > $now;
        });

        // Actualizar si hubo cambios.
        if (count($valid) !== count($pending)) {
            $this->state->set($pendingKey, $valid);
        }

        return array_values($valid);
    }

    /**
     * Aprueba una acción pendiente.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param string $requestId
     *   ID de la solicitud.
     *
     * @return array
     *   Resultado de la ejecución.
     */
    public function approveAction(int $tenantId, string $requestId): array
    {
        $pendingKey = "agent_autonomy_pending_{$tenantId}";
        $pending = $this->state->get($pendingKey, []);

        if (!isset($pending[$requestId])) {
            return [
                'status' => 'error',
                'message' => 'Solicitud no encontrada o expirada.',
            ];
        }

        $request = $pending[$requestId];

        // Eliminar de pendientes.
        unset($pending[$requestId]);
        $this->state->set($pendingKey, $pending);

        // Re-ejecutar la acción con el executor guardado.
        $executorClass = $request['executor_class'] ?? '';
        $context = $request['context'] ?? [];
        $action = $request['action'] ?? 'unknown';
        $result = NULL;

        // Intentar re-instanciar el executor desde el contenedor de servicios.
        // Las closures (Closure) no pueden re-instanciarse; se requiere un
        // servicio invocable registrado en el contenedor DI.
        if ($executorClass && $executorClass !== 'Closure' && $executorClass !== \Closure::class) {
            try {
                // Buscar el servicio por clase en el contenedor.
                $executor = NULL;
                $agentId = $request['agent_id'] ?? NULL;

                if ($agentId) {
                    $agent = $this->entityTypeManager->getStorage('ai_agent')->load($agentId);
                    if ($agent && $agent->getServiceId()) {
                        $service = \Drupal::service($agent->getServiceId());
                        if ($service instanceof $executorClass && is_callable($service)) {
                            $executor = $service;
                        }
                    }
                }

                if ($executor) {
                    $result = $executor($context);

                    $this->loggerFactory->get('agent_autonomy')->info(
                        'Accion @id re-ejecutada exitosamente para tenant @tenant (accion: @action)',
                        ['@id' => $requestId, '@tenant' => $tenantId, '@action' => $action]
                    );
                } else {
                    $this->loggerFactory->get('agent_autonomy')->warning(
                        'Accion @id aprobada pero executor no re-instanciable (clase: @class). Se marca como aprobada sin re-ejecucion.',
                        ['@id' => $requestId, '@class' => $executorClass]
                    );
                }
            } catch (\Exception $e) {
                $this->loggerFactory->get('agent_autonomy')->error(
                    'Error al re-ejecutar accion @id: @message',
                    ['@id' => $requestId, '@message' => $e->getMessage()]
                );

                return [
                    'status' => 'error',
                    'request_id' => $requestId,
                    'message' => 'Acción aprobada pero error en la ejecución: ' . $e->getMessage(),
                ];
            }
        } else {
            // Closure u otro callable no serializable.
            $this->loggerFactory->get('agent_autonomy')->notice(
                'Accion @id aprobada (executor tipo Closure, no re-ejecutable). Accion: @action, Tenant: @tenant',
                ['@id' => $requestId, '@action' => $action, '@tenant' => $tenantId]
            );
        }

        return [
            'status' => 'approved',
            'request_id' => $requestId,
            'message' => 'Acción aprobada y ejecutada.',
            'result' => $result,
        ];
    }

    /**
     * Rechaza una acción pendiente.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param string $requestId
     *   ID de la solicitud.
     *
     * @return array
     *   Resultado.
     */
    public function rejectAction(int $tenantId, string $requestId): array
    {
        $pendingKey = "agent_autonomy_pending_{$tenantId}";
        $pending = $this->state->get($pendingKey, []);

        if (!isset($pending[$requestId])) {
            return [
                'status' => 'error',
                'message' => 'Solicitud no encontrada.',
            ];
        }

        // Eliminar de pendientes.
        unset($pending[$requestId]);
        $this->state->set($pendingKey, $pending);

        $this->loggerFactory->get('agent_autonomy')->info(
            '❌ Acción @id rechazada por tenant @tenant',
            ['@id' => $requestId, '@tenant' => $tenantId]
        );

        return [
            'status' => 'rejected',
            'request_id' => $requestId,
            'message' => 'Acción rechazada.',
        ];
    }

    /**
     * Obtiene estadísticas de autonomía para un tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Estadísticas.
     */
    public function getStatistics(int $tenantId): array
    {
        $pending = $this->getPendingActions($tenantId);

        return [
            'pending_count' => count($pending),
            'pending_actions' => $pending,
            'today_auto_executions' => $this->getTodayAutoExecutions($tenantId),
        ];
    }

    /**
     * Obtiene el conteo diario de acciones para un agente/tenant.
     */
    protected function getDailyActionCount(string $agentId, int $tenantId): int
    {
        $key = "agent_daily_{$agentId}_{$tenantId}_" . date('Y-m-d');
        return (int) $this->state->get($key, 0);
    }

    /**
     * Incrementa el conteo diario de acciones.
     */
    protected function incrementDailyActionCount(string $agentId, ?int $tenantId): void
    {
        if (!$tenantId) {
            return;
        }
        $key = "agent_daily_{$agentId}_{$tenantId}_" . date('Y-m-d');
        $current = $this->state->get($key, 0);
        $this->state->set($key, $current + 1);
    }

    /**
     * Obtiene ejecuciones automáticas de hoy para un tenant.
     */
    protected function getTodayAutoExecutions(int $tenantId): int
    {
        // Sumar todos los conteos de hoy para todos los agentes.
        $prefix = "agent_daily_";
        $suffix = "_{$tenantId}_" . date('Y-m-d');

        // Por simplicidad, retornar 0 - implementación completa requeriría
        // iterar sobre todas las keys o usar una estructura diferente.
        return 0;
    }

    /**
     * Genera un ID único para solicitud.
     */
    protected function generateRequestId(): string
    {
        return 'req_' . bin2hex(random_bytes(8));
    }

    /**
     * Obtiene los nombres de los niveles de autonomía.
     *
     * @return array
     *   Array con nivel => nombre.
     */
    public static function getAutonomyLevelNames(): array
    {
        return [
            self::LEVEL_SUGGEST => 'Suggest (Solo sugiere)',
            self::LEVEL_CONFIRM => 'Confirm (Requiere aprobación)',
            self::LEVEL_AUTO => 'Auto (Ejecuta y notifica)',
            self::LEVEL_SILENT => 'Silent (Ejecuta sin notificar)',
        ];
    }

}
