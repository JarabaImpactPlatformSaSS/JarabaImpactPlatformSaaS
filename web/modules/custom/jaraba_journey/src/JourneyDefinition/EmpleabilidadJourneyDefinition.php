<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para Empleabilidad (3 avatares).
 *
 * Según Doc 103:
 * - Job Seeker: Tasa inserción >40%, Time to Employment <90 días
 * - Employer: Time to Hire <30 días
 * - Orientador: Candidatos activos/día
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 */
class EmpleabilidadJourneyDefinition
{

    /**
     * Journey del Job Seeker.
     */
    const JOB_SEEKER_JOURNEY = [
        'avatar' => 'job_seeker',
        'vertical' => 'empleabilidad',
        'kpi_target' => 'insertion_rate_40_percent',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'create_profile',
                        'label' => 'Crear perfil / subir CV',
                        'ia_intervention' => 'Parser CV automático, extraer skills',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['cv_analysis'],
                'transition_event' => 'profile_created',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'complete_skills_assessment',
                        'label' => 'Completar evaluación de skills',
                        'ia_intervention' => 'Tests adaptativos, identificar gaps',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'receive_job_recommendations',
                        'label' => 'Recibir recomendaciones de ofertas',
                        'ia_intervention' => 'Matching Score visible',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['skills_gaps_detected', 'high_match_alert'],
                'transition_event' => 'recommendations_received',
            ],
            'engagement' => [
                'steps' => [
                    4 => [
                        'action' => 'apply_to_jobs',
                        'label' => 'Aplicar a ofertas',
                        'ia_intervention' => 'One-click apply, carta personalizada',
                        'video_url' => '',
                    ],
                    5 => [
                        'action' => 'complete_training',
                        'label' => 'Completar formación recomendada',
                        'ia_intervention' => 'Learning path según objetivo',
                        'video_url' => '',
                    ],
                    6 => [
                        'action' => 'prepare_interview',
                        'label' => 'Preparar entrevista',
                        'ia_intervention' => 'Simulador de entrevista IA',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['application_assistance', 'interview_prep'],
                'transition_event' => 'interview_scheduled',
            ],
            'conversion' => [
                'steps' => [
                    7 => [
                        'action' => 'get_job',
                        'label' => 'Conseguir empleo',
                        'ia_intervention' => 'Celebración + encuesta cierre',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['job_offer_celebration'],
                'transition_event' => 'employment_achieved',
            ],
        ],
        'cross_sell' => [
            ['after' => 'profile_created', 'offer' => 'Curso LinkedIn optimization'],
            ['after' => 'application_rejected', 'offer' => 'Sesión con orientador'],
            ['after' => 'skills_gap_detected', 'offer' => 'Certificación específica'],
            ['after' => 'interview_scheduled', 'offer' => 'Pack preparación premium'],
        ],
    ];

    /**
     * Journey del Employer.
     */
    const EMPLOYER_JOURNEY = [
        'avatar' => 'employer',
        'vertical' => 'empleabilidad',
        'kpi_target' => 'time_to_hire_30_days',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'publish_job_offer',
                        'label' => 'Publicar oferta de empleo',
                        'ia_intervention' => 'Templates por sector, optimizar redacción',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['job_posting_optimization'],
                'transition_event' => 'job_published',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'receive_applications',
                        'label' => 'Recibir candidaturas',
                        'ia_intervention' => 'Ranking automático por fit',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'review_candidates',
                        'label' => 'Revisar candidatos preseleccionados',
                        'ia_intervention' => 'Perfiles enriquecidos, fortalezas/riesgos',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['candidate_ranking', 'profile_insights'],
                'transition_event' => 'candidates_shortlisted',
            ],
            'engagement' => [
                'steps' => [
                    4 => [
                        'action' => 'schedule_interviews',
                        'label' => 'Agendar entrevistas',
                        'ia_intervention' => 'Calendario integrado',
                        'video_url' => '',
                    ],
                    5 => [
                        'action' => 'conduct_interviews',
                        'label' => 'Realizar entrevistas',
                        'ia_intervention' => 'Guía por competencias, scorecard',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['interview_scheduling', 'scorecard_generation'],
                'transition_event' => 'interviews_completed',
            ],
            'conversion' => [
                'steps' => [
                    6 => [
                        'action' => 'make_offer_and_hire',
                        'label' => 'Hacer oferta y contratar',
                        'ia_intervention' => 'Sugerir salario competitivo',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['salary_benchmark'],
                'transition_event' => 'hire_completed',
            ],
        ],
    ];

    /**
     * Journey del Orientador.
     */
    const ORIENTADOR_JOURNEY = [
        'avatar' => 'orientador',
        'vertical' => 'empleabilidad',
        'kpi_target' => 'active_candidates_managed',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'receive_case_assignment',
                        'label' => 'Recibir asignación de caso',
                        'ia_intervention' => 'Resumen ejecutivo automático',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['case_summary_generation'],
                'transition_event' => 'case_assigned',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'first_session',
                        'label' => 'Primera sesión con candidato',
                        'ia_intervention' => 'Preparar contexto de la sesión',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'track_progress',
                        'label' => 'Seguimiento de progreso',
                        'ia_intervention' => 'Alertas de estancamiento',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['candidate_stalled_alert', 'opportunity_match'],
                'transition_event' => 'regular_tracking',
            ],
            'conversion' => [
                'steps' => [
                    4 => [
                        'action' => 'candidate_placed',
                        'label' => 'Candidato colocado',
                        'ia_intervention' => 'Generar informe de cierre',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['case_closure_report'],
                'transition_event' => 'placement_achieved',
            ],
        ],
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public static function getJourneyDefinition(string $avatar): ?array
    {
        return match ($avatar) {
            'job_seeker' => self::JOB_SEEKER_JOURNEY,
            'employer' => self::EMPLOYER_JOURNEY,
            'orientador' => self::ORIENTADOR_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de Empleabilidad.
     */
    public static function getAvatars(): array
    {
        return ['job_seeker', 'employer', 'orientador'];
    }

}
