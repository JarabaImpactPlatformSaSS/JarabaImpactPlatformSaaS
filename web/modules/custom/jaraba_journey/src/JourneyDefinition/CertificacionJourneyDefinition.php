<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para Certificación (3 avatares).
 *
 * Según Doc 103:
 * - Estudiante: Completion Rate >70%, Certification Rate >60%
 * - Formador: Tasa aprobados
 * - Admin LMS: Engagement rate
 */
class CertificacionJourneyDefinition
{

    /**
     * Journey del Estudiante.
     *
     * KPI Target: Completion Rate >70%, Certification Rate >60%
     */
    const ESTUDIANTE_JOURNEY = [
        'avatar' => 'estudiante',
        'vertical' => 'certificacion',
        'kpi_target' => 'completion_70_cert_60',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'explore_catalog',
                        'label' => 'Explorar catálogo de cursos',
                        'ia_intervention' => 'Recomendar según perfil y objetivos',
                    ],
                ],
                'triggers' => ['course_recommendations'],
                'transition_event' => 'catalog_explored',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'enroll_course',
                        'label' => 'Matricularse en curso',
                        'ia_intervention' => 'Onboarding, personalizar learning path',
                    ],
                ],
                'triggers' => ['learning_path_personalization'],
                'transition_event' => 'enrolled',
            ],
            'engagement' => [
                'steps' => [
                    3 => [
                        'action' => 'consume_content',
                        'label' => 'Consumir contenido',
                        'ia_intervention' => 'Adaptar ritmo según engagement',
                    ],
                    4 => [
                        'action' => 'complete_exercises',
                        'label' => 'Completar ejercicios/quizzes',
                        'ia_intervention' => 'Feedback inmediato, áreas de refuerzo',
                    ],
                ],
                'triggers' => ['pace_adaptation', 'reinforcement_areas'],
                'transition_event' => 'content_completed',
            ],
            'conversion' => [
                'steps' => [
                    5 => [
                        'action' => 'take_certification_exam',
                        'label' => 'Realizar examen certificación',
                        'ia_intervention' => 'Preguntas adaptativas, proctored',
                    ],
                ],
                'triggers' => ['exam_simulation'],
                'transition_event' => 'exam_passed',
            ],
            'retention' => [
                'steps' => [
                    6 => [
                        'action' => 'obtain_credential',
                        'label' => 'Obtener credencial',
                        'ia_intervention' => 'Badge Open Badges 3.0, sugerir siguiente',
                    ],
                ],
                'triggers' => ['next_certification_suggestion'],
                'transition_event' => 'certified',
            ],
        ],
        'cross_sell' => [
            ['after' => 'enrolled', 'offer' => 'Recursos adicionales curso'],
            ['after' => 'exam_passed', 'offer' => 'Certificación avanzada'],
            ['after' => 'certified', 'offer' => 'Learning path especializado'],
        ],
    ];

    /**
     * Journey del Formador.
     */
    const FORMADOR_JOURNEY = [
        'avatar' => 'formador',
        'vertical' => 'certificacion',
        'kpi_target' => 'approval_rate',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'create_course',
                        'label' => 'Crear curso',
                        'ia_intervention' => 'Asistente estructuración contenido',
                    ],
                ],
                'triggers' => ['content_structure'],
                'transition_event' => 'course_created',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'design_evaluations',
                        'label' => 'Diseñar evaluaciones',
                        'ia_intervention' => 'Generación automática preguntas',
                    ],
                    3 => [
                        'action' => 'monitor_students',
                        'label' => 'Monitorear estudiantes',
                        'ia_intervention' => 'Alertas + sugerencias intervención',
                    ],
                ],
                'triggers' => ['question_generation', 'student_difficulty_alert'],
                'transition_event' => 'cohort_active',
            ],
            'conversion' => [
                'steps' => [
                    4 => [
                        'action' => 'cohort_report',
                        'label' => 'Informe de cohorte',
                        'ia_intervention' => 'Resultados y mejoras sugeridas',
                    ],
                ],
                'triggers' => ['improvement_suggestions'],
                'transition_event' => 'cohort_completed',
            ],
        ],
    ];

    /**
     * Journey del Admin LMS.
     */
    const ADMIN_LMS_JOURNEY = [
        'avatar' => 'admin_lms',
        'vertical' => 'certificacion',
        'kpi_target' => 'engagement_rate',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'mass_user_import',
                        'label' => 'Importación masiva usuarios',
                        'ia_intervention' => 'Importación inteligente con validación',
                    ],
                ],
                'triggers' => ['smart_import'],
                'transition_event' => 'users_imported',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'compliance_monitoring',
                        'label' => 'Monitorear compliance',
                        'ia_intervention' => 'Alertas formaciones obligatorias',
                    ],
                    3 => [
                        'action' => 'boost_participation',
                        'label' => 'Impulsar participación',
                        'ia_intervention' => 'Sugerencias gamificación',
                    ],
                ],
                'triggers' => ['compliance_alert', 'gamification_suggestions'],
                'transition_event' => 'lms_active',
            ],
            'conversion' => [
                'steps' => [
                    4 => [
                        'action' => 'audit_report',
                        'label' => 'Informe auditoría',
                        'ia_intervention' => 'Auto-generación informes xAPI',
                    ],
                ],
                'triggers' => ['xapi_report'],
                'transition_event' => 'audit_completed',
            ],
        ],
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public static function getJourneyDefinition(string $avatar): ?array
    {
        return match ($avatar) {
            'estudiante' => self::ESTUDIANTE_JOURNEY,
            'formador' => self::FORMADOR_JOURNEY,
            'admin_lms' => self::ADMIN_LMS_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de Certificación.
     */
    public static function getAvatars(): array
    {
        return ['estudiante', 'formador', 'admin_lms'];
    }

}
