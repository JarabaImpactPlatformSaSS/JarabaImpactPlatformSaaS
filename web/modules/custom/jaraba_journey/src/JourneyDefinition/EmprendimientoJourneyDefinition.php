<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para Emprendimiento (3 avatares).
 *
 * Según Doc 103:
 * - Emprendedor: Hitos completados >70%, Supervivencia 1 año >80%
 * - Mentor: Mentees activos
 * - Gestor: Tasa supervivencia programa
 */
class EmprendimientoJourneyDefinition
{

    /**
     * Journey del Emprendedor.
     *
     * Integrado con Copiloto v3 Osterwalder.
     */
    const EMPRENDEDOR_JOURNEY = [
        'avatar' => 'emprendedor',
        'vertical' => 'emprendimiento',
        'kpi_target' => 'milestones_70_survival_80',
        'copilot_integration' => TRUE,
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'register_business_idea',
                        'label' => 'Registrar idea de negocio',
                        'ia_intervention' => 'Análisis inicial de viabilidad',
                        'copilot_mode' => 'consultor',
                    ],
                ],
                'triggers' => ['viability_analysis', 'onboarding_tour'],
                'transition_event' => 'idea_registered',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'complete_diagnostic',
                        'label' => 'Completar diagnóstico de madurez',
                        'ia_intervention' => 'Evaluación 360º, identificar gaps',
                        'copilot_mode' => 'consultor',
                    ],
                    3 => [
                        'action' => 'receive_action_plan',
                        'label' => 'Recibir plan de acción personalizado',
                        'ia_intervention' => 'Roadmap con hitos, priorizar por impacto',
                        'copilot_mode' => 'consultor',
                    ],
                ],
                'triggers' => ['diagnostic_completed', 'action_plan_generated'],
                'transition_event' => 'plan_received',
            ],
            'engagement' => [
                'steps' => [
                    4 => [
                        'action' => 'work_on_bmc',
                        'label' => 'Trabajar en Business Model Canvas',
                        'ia_intervention' => 'Editor guiado, sugerir mejoras',
                        'copilot_mode' => 'vpc_designer',
                    ],
                    5 => [
                        'action' => 'validate_vpc',
                        'label' => 'Validar Value Proposition Canvas',
                        'ia_intervention' => 'VPC Designer con Fit Score',
                        'copilot_mode' => 'vpc_designer',
                    ],
                    6 => [
                        'action' => 'customer_discovery',
                        'label' => 'Realizar Customer Discovery',
                        'ia_intervention' => 'Guión entrevista, Mom Test',
                        'copilot_mode' => 'customer_discovery',
                    ],
                    7 => [
                        'action' => 'validate_mvp',
                        'label' => 'Validar MVP',
                        'ia_intervention' => 'Test Cards, analizar feedback',
                        'copilot_mode' => 'sparring',
                    ],
                ],
                'triggers' => ['bmc_suggestions', 'vpc_fit_score', 'field_exit_reminder'],
                'transition_event' => 'mvp_validated',
            ],
            'conversion' => [
                'steps' => [
                    8 => [
                        'action' => 'connect_with_mentor',
                        'label' => 'Conectar con mentor',
                        'ia_intervention' => 'Matching según necesidad',
                        'copilot_mode' => 'coach',
                    ],
                    9 => [
                        'action' => 'apply_for_funding',
                        'label' => 'Solicitar financiación',
                        'ia_intervention' => 'Match con ayudas elegibles',
                        'copilot_mode' => 'cfo',
                    ],
                ],
                'triggers' => ['mentor_matching', 'funding_opportunity'],
                'transition_event' => 'funding_secured',
            ],
            'retention' => [
                'steps' => [
                    10 => [
                        'action' => 'scale_business',
                        'label' => 'Escalar negocio',
                        'ia_intervention' => 'Detectar patrones BMG',
                        'copilot_mode' => 'pattern_expert',
                    ],
                ],
                'triggers' => ['pattern_detection', 'pivot_signals'],
                'transition_event' => 'scaling',
            ],
        ],
        'cross_sell' => [
            ['after' => 'diagnostic_completed', 'offer' => 'Curso modelo de negocio'],
            ['after' => 'before_mvp', 'offer' => 'Kit de validación'],
            ['after' => 'funding_search', 'offer' => 'Preparación de pitch'],
            ['after' => 'launch', 'offer' => 'Membresía comunidad'],
        ],
    ];

    /**
     * Journey del Mentor.
     */
    const MENTOR_JOURNEY = [
        'avatar' => 'mentor',
        'vertical' => 'emprendimiento',
        'kpi_target' => 'active_mentees',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'receive_mentee_assignment',
                        'label' => 'Recibir asignación de mentee',
                        'ia_intervention' => 'Resumen ejecutivo del proyecto',
                    ],
                ],
                'triggers' => ['mentee_summary'],
                'transition_event' => 'mentee_assigned',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'prepare_session',
                        'label' => 'Preparar sesión',
                        'ia_intervention' => 'Agenda sugerida según avances',
                    ],
                    3 => [
                        'action' => 'conduct_session',
                        'label' => 'Realizar sesión',
                        'ia_intervention' => 'Notas automáticas, próximos pasos',
                    ],
                ],
                'triggers' => ['session_agenda', 'notes_generation'],
                'transition_event' => 'sessions_active',
            ],
            'retention' => [
                'steps' => [
                    4 => [
                        'action' => 'track_mentee_progress',
                        'label' => 'Seguimiento de progreso',
                        'ia_intervention' => 'Alertas si mentee estancado',
                    ],
                ],
                'triggers' => ['mentee_stalled_alert'],
                'transition_event' => 'mentee_graduated',
            ],
        ],
    ];

    /**
     * Journey del Gestor de Programa.
     */
    const GESTOR_PROGRAMA_JOURNEY = [
        'avatar' => 'gestor_programa',
        'vertical' => 'emprendimiento',
        'kpi_target' => 'program_survival_rate',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'setup_cohort',
                        'label' => 'Configurar cohorte',
                        'ia_intervention' => 'Dashboard pre-configurado',
                    ],
                ],
                'triggers' => ['cohort_dashboard'],
                'transition_event' => 'cohort_started',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'monitor_kpis',
                        'label' => 'Monitorear KPIs',
                        'ia_intervention' => 'Alertas tempranas',
                    ],
                    3 => [
                        'action' => 'intervene_at_risk',
                        'label' => 'Intervenir en casos de riesgo',
                        'ia_intervention' => 'Acciones sugeridas',
                    ],
                ],
                'triggers' => ['kpi_risk_alert', 'intervention_suggestions'],
                'transition_event' => 'cohort_active',
            ],
            'conversion' => [
                'steps' => [
                    4 => [
                        'action' => 'generate_impact_report',
                        'label' => 'Generar informe de impacto',
                        'ia_intervention' => 'Auto-generación de informe',
                    ],
                ],
                'triggers' => ['impact_report_generation'],
                'transition_event' => 'cohort_completed',
            ],
        ],
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public static function getJourneyDefinition(string $avatar): ?array
    {
        return match ($avatar) {
            'emprendedor' => self::EMPRENDEDOR_JOURNEY,
            'mentor' => self::MENTOR_JOURNEY,
            'gestor_programa' => self::GESTOR_PROGRAMA_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de Emprendimiento.
     */
    public static function getAvatars(): array
    {
        return ['emprendedor', 'mentor', 'gestor_programa'];
    }

}
