<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_journey\Entity\JourneyState;

/**
 * Servicio principal del Journey Engine.
 *
 * Gestiona el estado del journey de usuarios, transiciones entre estados,
 * y proporciona la lógica central del motor de navegación inteligente.
 *
 * Implementa la especificación Doc 103:
 * - 7 estados de journey
 * - 19 avatares en 7 verticales
 * - Transiciones basadas en eventos
 */
class JourneyEngineService
{

    /**
     * Transiciones válidas entre estados.
     */
    const VALID_TRANSITIONS = [
        'discovery' => ['activation', 'at_risk'],
        'activation' => ['engagement', 'at_risk'],
        'engagement' => ['conversion', 'retention', 'at_risk'],
        'conversion' => ['retention', 'at_risk'],
        'retention' => ['expansion', 'at_risk'],
        'expansion' => ['advocacy', 'at_risk'],
        'advocacy' => ['at_risk'],
        'at_risk' => ['discovery', 'activation', 'engagement'],
    ];

    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountProxyInterface $currentUser;
    protected LoggerChannelInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Obtiene el estado del journey de un usuario.
     *
     * @param int|null $userId
     *   ID del usuario.
     * @param string|null $avatarType
     *   Tipo de avatar (opcional, usa el primero encontrado).
     *
     * @return \Drupal\jaraba_journey\Entity\JourneyState|null
     *   El estado del journey o NULL si no existe.
     */
    public function getState(?int $userId = NULL, ?string $avatarType = NULL): ?JourneyState
    {
        $userId = $userId ?? (int) $this->currentUser->id();

        if (!$userId) {
            return NULL;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('journey_state');
            $conditions = ['user_id' => $userId];

            if ($avatarType) {
                $conditions['avatar_type'] = $avatarType;
            }

            $states = $storage->loadByProperties($conditions);

            if (!empty($states)) {
                return reset($states);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getting journey state: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        return NULL;
    }

    /**
     * Obtiene o crea el estado del journey de un usuario.
     */
    public function getOrCreateState(int $userId, string $avatarType, string $vertical = ''): JourneyState
    {
        $state = $this->getState($userId, $avatarType);

        if ($state) {
            return $state;
        }

        // Crear nuevo estado
        $storage = $this->entityTypeManager->getStorage('journey_state');
        $state = $storage->create([
            'user_id' => $userId,
            'avatar_type' => $avatarType,
            'journey_state' => 'discovery',
            'current_step' => 1,
            'vertical' => $vertical ?: $this->inferVerticalFromAvatar($avatarType),
            'context_data' => json_encode([
                'last_action' => 'created',
                'time_in_state' => 0,
                'interactions_today' => 0,
                'risk_score' => 0,
            ]),
        ]);
        $state->save();

        $this->logger->info('Created journey state for user @user as @avatar', [
            '@user' => $userId,
            '@avatar' => $avatarType,
        ]);

        return $state;
    }

    /**
     * Infiere la vertical a partir del avatar.
     */
    protected function inferVerticalFromAvatar(string $avatarType): string
    {
        $avatarVerticals = [
            'productor' => 'agroconecta',
            'comprador_b2b' => 'agroconecta',
            'consumidor' => 'agroconecta',
            'comerciante' => 'comercioconecta',
            'comprador_local' => 'comercioconecta',
            'profesional' => 'serviciosconecta',
            'cliente_servicios' => 'serviciosconecta',
            'job_seeker' => 'empleabilidad',
            'employer' => 'empleabilidad',
            'orientador' => 'empleabilidad',
            'emprendedor' => 'emprendimiento',
            'mentor' => 'emprendimiento',
            'gestor_programa' => 'emprendimiento',
            'beneficiario_ei' => 'andalucia_ei',
            'tecnico_sto' => 'andalucia_ei',
            'admin_ei' => 'andalucia_ei',
            'estudiante' => 'certificacion',
            'formador' => 'certificacion',
            'admin_lms' => 'certificacion',
        ];

        return $avatarVerticals[$avatarType] ?? '';
    }

    /**
     * Registra un evento y potencialmente transiciona el estado.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $eventType
     *   Tipo de evento (e.g., 'profile_completed', 'first_purchase').
     * @param array $eventData
     *   Datos adicionales del evento.
     *
     * @return array
     *   Resultado con información de la transición.
     */
    public function recordEvent(int $userId, string $eventType, array $eventData = []): array
    {
        $state = $this->getState($userId);

        if (!$state) {
            return ['success' => FALSE, 'error' => 'No journey state found'];
        }

        // Actualizar contexto
        $context = $state->getContext();
        $context['last_action'] = $eventType;
        $context['last_action_time'] = time();
        $context['interactions_today'] = ($context['interactions_today'] ?? 0) + 1;
        $state->setContext($context);

        // Evaluar si debe transicionar
        $transition = $this->evaluateTransition($state, $eventType, $eventData);

        if ($transition['should_transition']) {
            $this->transitionState($state, $transition['new_state']);
        }

        $state->save();

        return [
            'success' => TRUE,
            'current_state' => $state->getJourneyState(),
            'transitioned' => $transition['should_transition'],
            'new_state' => $transition['new_state'] ?? NULL,
        ];
    }

    /**
     * Evalúa si un evento debe causar una transición de estado.
     */
    protected function evaluateTransition(JourneyState $state, string $eventType, array $eventData): array
    {
        $currentState = $state->getJourneyState();

        // Reglas de transición por evento
        $transitionRules = [
            'profile_completed' => [
                'from' => ['discovery'],
                'to' => 'activation',
            ],
            'first_action_completed' => [
                'from' => ['activation'],
                'to' => 'engagement',
            ],
            'first_purchase' => [
                'from' => ['engagement'],
                'to' => 'conversion',
            ],
            'repeat_purchase' => [
                'from' => ['conversion'],
                'to' => 'retention',
            ],
            'upgraded_plan' => [
                'from' => ['retention'],
                'to' => 'expansion',
            ],
            'referred_user' => [
                'from' => ['expansion'],
                'to' => 'advocacy',
            ],
            'inactive_7_days' => [
                'from' => ['discovery', 'activation', 'engagement', 'retention'],
                'to' => 'at_risk',
            ],
            'reactivated' => [
                'from' => ['at_risk'],
                'to' => 'engagement',
            ],
        ];

        if (isset($transitionRules[$eventType])) {
            $rule = $transitionRules[$eventType];
            if (in_array($currentState, $rule['from'])) {
                return [
                    'should_transition' => TRUE,
                    'new_state' => $rule['to'],
                ];
            }
        }

        return ['should_transition' => FALSE];
    }

    /**
     * Transiciona el estado del journey.
     */
    protected function transitionState(JourneyState $state, string $newState): void
    {
        $oldState = $state->getJourneyState();

        // Validar transición
        $validTransitions = self::VALID_TRANSITIONS[$oldState] ?? [];
        if (!in_array($newState, $validTransitions)) {
            $this->logger->warning('Invalid transition from @old to @new', [
                '@old' => $oldState,
                '@new' => $newState,
            ]);
            return;
        }

        $state->setJourneyState($newState);
        $state->set('current_step', 1);
        $state->set('last_transition', time());

        $this->logger->info('User @user transitioned from @old to @new', [
            '@user' => $state->getOwnerId(),
            '@old' => $oldState,
            '@new' => $newState,
        ]);
    }

    /**
     * Obtiene las próximas acciones sugeridas para el usuario.
     */
    public function getNextActions(int $userId): array
    {
        $state = $this->getState($userId);

        if (!$state) {
            return [];
        }

        $currentState = $state->getJourneyState();
        $avatarType = $state->getAvatarType();

        // Acciones por estado y avatar (simplificado)
        $actions = $this->getActionsForStateAndAvatar($currentState, $avatarType);

        return [
            'current_state' => $currentState,
            'current_step' => $state->getCurrentStep(),
            'next_actions' => $actions,
            'pending_triggers' => $state->getPendingTriggers(),
        ];
    }

    /**
     * Obtiene acciones específicas para estado y avatar.
     */
    protected function getActionsForStateAndAvatar(string $state, string $avatar): array
    {
        // Acciones genéricas por estado
        $stateActions = [
            'discovery' => [
                ['action' => 'complete_profile', 'label' => 'Completa tu perfil', 'priority' => 'high'],
                ['action' => 'explore_features', 'label' => 'Explora las funcionalidades', 'priority' => 'medium'],
            ],
            'activation' => [
                ['action' => 'first_action', 'label' => 'Realiza tu primera acción', 'priority' => 'high'],
                ['action' => 'connect_services', 'label' => 'Conecta servicios externos', 'priority' => 'medium'],
            ],
            'engagement' => [
                ['action' => 'regular_use', 'label' => 'Usa la plataforma regularmente', 'priority' => 'medium'],
                ['action' => 'explore_advanced', 'label' => 'Descubre funciones avanzadas', 'priority' => 'low'],
            ],
            'conversion' => [
                ['action' => 'complete_transaction', 'label' => 'Completa tu transacción', 'priority' => 'high'],
            ],
            'retention' => [
                ['action' => 'invite_others', 'label' => 'Invita a otros', 'priority' => 'medium'],
                ['action' => 'upgrade_plan', 'label' => 'Mejora tu plan', 'priority' => 'low'],
            ],
            'at_risk' => [
                ['action' => 'reactivate', 'label' => 'Vuelve a la plataforma', 'priority' => 'high'],
                ['action' => 'contact_support', 'label' => 'Contacta soporte', 'priority' => 'medium'],
            ],
        ];

        return $stateActions[$state] ?? [];
    }

    /**
     * Obtiene KPIs del journey del usuario.
     */
    public function getKpis(int $userId): array
    {
        $state = $this->getState($userId);

        if (!$state) {
            return [];
        }

        $context = $state->getContext();

        return [
            'user_id' => $userId,
            'avatar_type' => $state->getAvatarType(),
            'current_state' => $state->getJourneyState(),
            'current_step' => $state->getCurrentStep(),
            'completed_steps' => count($state->getCompletedSteps()),
            'time_in_state' => $context['time_in_state'] ?? 0,
            'interactions_today' => $context['interactions_today'] ?? 0,
            'risk_score' => $context['risk_score'] ?? 0,
            'days_since_created' => $state->get('created')->value
                ? round((time() - $state->get('created')->value) / 86400)
                : 0,
        ];
    }

}
