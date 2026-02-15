<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Servicio de triggers para intervenciones IA del Journey Engine.
 *
 * Gestiona los triggers de intervención según Doc 103:
 * - Triggers Temporales: inactividad, aniversario, estacionalidad
 * - Triggers Comportamentales: abandono, hesitación, patrón
 * - Triggers Transaccionales: post-compra, reorden, upgrade
 * - Triggers Contextuales: ubicación, evento, social
 */
class JourneyTriggerService
{

    /**
     * Tipos de triggers disponibles.
     */
    const TRIGGER_TYPES = [
        // Temporales
        'inactivity_7d' => [
            'type' => 'temporal',
            'name' => 'Inactividad 7 días',
            'action' => 'send_reengagement_email',
            'channel' => 'email',
        ],
        'anniversary_1y' => [
            'type' => 'temporal',
            'name' => 'Aniversario 1 año',
            'action' => 'send_special_offer',
            'channel' => 'email',
        ],
        'season_start' => [
            'type' => 'temporal',
            'name' => 'Inicio temporada',
            'action' => 'send_preparation_alert',
            'channel' => 'push',
        ],
        // Comportamentales
        'cart_abandoned' => [
            'type' => 'behavioral',
            'name' => 'Carrito abandonado',
            'action' => 'send_cart_recovery',
            'channel' => 'email',
        ],
        'hesitation_pricing' => [
            'type' => 'behavioral',
            'name' => 'Hesitación en precios',
            'action' => 'open_proactive_chat',
            'channel' => 'in_app',
        ],
        'search_no_results' => [
            'type' => 'behavioral',
            'name' => 'Búsqueda sin resultados',
            'action' => 'suggest_alternatives',
            'channel' => 'in_app',
        ],
        // Transaccionales
        'post_purchase' => [
            'type' => 'transactional',
            'name' => 'Post-compra',
            'action' => 'send_confirmation_crosssell',
            'channel' => 'email',
        ],
        'reorder_cycle' => [
            'type' => 'transactional',
            'name' => 'Ciclo de reorden',
            'action' => 'suggest_reorder',
            'channel' => 'push',
        ],
        'usage_near_limit' => [
            'type' => 'transactional',
            'name' => 'Uso cerca del límite',
            'action' => 'offer_upgrade',
            'channel' => 'in_app',
        ],
        // Contextuales
        'near_provider' => [
            'type' => 'contextual',
            'name' => 'Cerca de proveedor',
            'action' => 'notify_availability',
            'channel' => 'push',
        ],
        'sector_event' => [
            'type' => 'contextual',
            'name' => 'Evento sectorial',
            'action' => 'send_relevant_content',
            'channel' => 'email',
        ],
    ];

    /**
     * Reglas de no-intrusión según Doc 103.
     */
    const NO_INTRUSION_RULES = [
        'max_push_per_day' => 3,
        'max_email_per_week' => 2,
        'chat_inactivity_threshold_seconds' => 30,
        'quiet_hours_start' => 22,
        'quiet_hours_end' => 8,
    ];

    protected EntityTypeManagerInterface $entityTypeManager;
    protected JourneyEngineService $engineService;
    protected LoggerChannelInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        JourneyEngineService $engine_service,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->engineService = $engine_service;
        $this->logger = $logger;
    }

    /**
     * Evalúa qué triggers deben dispararse para un usuario.
     */
    public function evaluateTriggers(int $userId): array
    {
        $state = $this->engineService->getState($userId);

        if (!$state) {
            return [];
        }

        $triggersToFire = [];

        // Evaluar cada tipo de trigger
        foreach (self::TRIGGER_TYPES as $triggerId => $trigger) {
            if ($this->shouldFireTrigger($userId, $triggerId, $state)) {
                $triggersToFire[] = [
                    'id' => $triggerId,
                    'trigger' => $trigger,
                    'priority' => $this->calculateTriggerPriority($triggerId, $state),
                ];
            }
        }

        // Ordenar por prioridad
        usort($triggersToFire, fn($a, $b) => $b['priority'] <=> $a['priority']);

        // Aplicar reglas de no-intrusión
        return $this->applyNoIntrusionRules($userId, $triggersToFire);
    }

    /**
     * Determina si un trigger debe dispararse.
     */
    protected function shouldFireTrigger(int $userId, string $triggerId, $state): bool
    {
        $context = $state->getContext();

        switch ($triggerId) {
            case 'inactivity_7d':
                $lastAction = $context['last_action_time'] ?? 0;
                return $lastAction > 0 && (time() - $lastAction) > (7 * 86400);

            case 'cart_abandoned':
                // Verificar si hay carrito abandonado
                return FALSE; // Implementar con commerce

            case 'hesitation_pricing':
                // Verificar tiempo en página de precios
                return FALSE; // Implementar con analytics

            case 'usage_near_limit':
                // Verificar uso vs límite del plan
                return FALSE; // Implementar con quotas

            default:
                return FALSE;
        }
    }

    /**
     * Calcula la prioridad de un trigger.
     */
    protected function calculateTriggerPriority(string $triggerId, $state): int
    {
        $basePriority = [
            'cart_abandoned' => 90,
            'inactivity_7d' => 80,
            'usage_near_limit' => 75,
            'post_purchase' => 70,
            'reorder_cycle' => 65,
            'hesitation_pricing' => 60,
            'anniversary_1y' => 50,
            'season_start' => 45,
            'search_no_results' => 40,
            'near_provider' => 35,
            'sector_event' => 30,
        ];

        $priority = $basePriority[$triggerId] ?? 50;

        // Boost si el usuario está en riesgo
        if ($state->getJourneyState() === 'at_risk') {
            $priority += 20;
        }

        return min(100, $priority);
    }

    /**
     * Aplica reglas de no-intrusión.
     */
    protected function applyNoIntrusionRules(int $userId, array $triggers): array
    {
        // Verificar horario silencioso
        $hour = (int) date('G');
        if (
            $hour >= self::NO_INTRUSION_RULES['quiet_hours_start'] ||
            $hour < self::NO_INTRUSION_RULES['quiet_hours_end']
        ) {
            // Filtrar push notifications en horario silencioso
            $triggers = array_filter($triggers, function ($t) {
                return $t['trigger']['channel'] !== 'push';
            });
        }

        // Limitar por canal (simplificado, en producción usar tracking)
        $channelCounts = [];
        $filtered = [];

        foreach ($triggers as $trigger) {
            $channel = $trigger['trigger']['channel'];
            $channelCounts[$channel] = ($channelCounts[$channel] ?? 0) + 1;

            $maxAllowed = match ($channel) {
                'push' => self::NO_INTRUSION_RULES['max_push_per_day'],
                'email' => self::NO_INTRUSION_RULES['max_email_per_week'],
                default => 10,
            };

            if ($channelCounts[$channel] <= $maxAllowed) {
                $filtered[] = $trigger;
            }
        }

        return $filtered;
    }

    /**
     * Dispara un trigger específico.
     */
    public function fireTrigger(int $userId, string $triggerId): array
    {
        if (!isset(self::TRIGGER_TYPES[$triggerId])) {
            return ['success' => FALSE, 'error' => 'Unknown trigger'];
        }

        $trigger = self::TRIGGER_TYPES[$triggerId];

        // Registrar el evento
        $this->engineService->recordEvent($userId, "trigger_{$triggerId}", [
            'trigger_type' => $trigger['type'],
            'channel' => $trigger['channel'],
        ]);

        // En producción, aquí se dispararía la acción real
        $this->logger->info('Fired trigger @trigger for user @user via @channel', [
            '@trigger' => $triggerId,
            '@user' => $userId,
            '@channel' => $trigger['channel'],
        ]);

        return [
            'success' => TRUE,
            'trigger' => $triggerId,
            'action' => $trigger['action'],
            'channel' => $trigger['channel'],
        ];
    }

    /**
     * Obtiene todos los tipos de trigger disponibles.
     */
    public function getAllTriggerTypes(): array
    {
        return self::TRIGGER_TYPES;
    }

    /**
     * Evalua triggers especificos para emprendedores.
     *
     * Extiende la evaluacion generica con logica especifica del vertical
     * de emprendimiento: inactividad en el programa, hitos pendientes,
     * sesiones de mentoria sin agendar.
     *
     * @param int $userId
     *   ID del usuario emprendedor.
     * @param array $profileContext
     *   Contexto del perfil de emprendedor con:
     *   - program_week: (int) Semana actual del programa.
     *   - impact_points: (int) Puntos de impacto acumulados.
     *   - pending_hypotheses: (int) Hipotesis pendientes de validar.
     *   - last_copilot_use: (int) Timestamp del ultimo uso del copiloto.
     *
     * @return array
     *   Triggers especificos de emprendimiento a disparar.
     */
    public function evaluateEntrepreneurTriggers(int $userId, array $profileContext = []): array
    {
        $triggersToFire = [];

        // Evaluar triggers genericos primero.
        $genericTriggers = $this->evaluateTriggers($userId);
        $triggersToFire = array_merge($triggersToFire, $genericTriggers);

        // Trigger: emprendedor no usa el copiloto en 3+ dias.
        $lastCopilotUse = $profileContext['last_copilot_use'] ?? 0;
        if ($lastCopilotUse > 0 && (time() - $lastCopilotUse) > (3 * 86400)) {
            $triggersToFire[] = [
                'id' => 'copilot_inactive_3d',
                'trigger' => [
                    'type' => 'behavioral',
                    'name' => 'Copiloto sin uso 3 dias',
                    'action' => 'send_copilot_nudge',
                    'channel' => 'in_app',
                ],
                'priority' => 70,
            ];
        }

        // Trigger: hipotesis pendientes sin avance en semana actual.
        $pendingHypotheses = $profileContext['pending_hypotheses'] ?? 0;
        $programWeek = $profileContext['program_week'] ?? 0;
        if ($pendingHypotheses > 0 && $programWeek >= 4) {
            $triggersToFire[] = [
                'id' => 'stalled_hypotheses',
                'trigger' => [
                    'type' => 'behavioral',
                    'name' => 'Hipotesis estancadas',
                    'action' => 'suggest_experiment',
                    'channel' => 'in_app',
                ],
                'priority' => 65,
            ];
        }

        // Aplicar reglas de no-intrusion.
        return $this->applyNoIntrusionRules($userId, $triggersToFire);
    }

}
