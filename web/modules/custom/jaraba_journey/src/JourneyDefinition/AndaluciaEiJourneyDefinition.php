<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para Andalucía +ei (3 avatares).
 *
 * Según Doc 103:
 * - Beneficiario: Solicitudes completas >85%, Subsanaciones <20%
 * - Técnico STO: Expedientes/día
 * - Administrador: Ejecución presupuesto
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 */
class AndaluciaEiJourneyDefinition
{

    /**
     * Journey del Beneficiario.
     *
     * KPI Target: Solicitudes completas >85%, Subsanaciones <20%
     */
    const BENEFICIARIO_JOURNEY = [
        'avatar' => 'beneficiario_ei',
        'vertical' => 'andalucia_ei',
        'kpi_target' => 'complete_85_subsanacion_20',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'verify_eligibility',
                        'label' => 'Verificar elegibilidad',
                        'ia_intervention' => 'Checklist interactivo, pre-validar criterios',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['eligibility_check'],
                'transition_event' => 'eligibility_verified',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'complete_application',
                        'label' => 'Completar solicitud',
                        'ia_intervention' => 'Formulario guiado, validar campos tiempo real',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'attach_documentation',
                        'label' => 'Adjuntar documentación',
                        'ia_intervention' => 'Checklist visual, verificar completitud',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['field_validation', 'doc_completeness'],
                'transition_event' => 'application_submitted',
            ],
            'engagement' => [
                'steps' => [
                    4 => [
                        'action' => 'track_status',
                        'label' => 'Seguimiento de estado',
                        'ia_intervention' => 'Tracking tiempo real, notificar cambios',
                        'video_url' => '',
                    ],
                    5 => [
                        'action' => 'subsanation_if_needed',
                        'label' => 'Subsanar requerimientos',
                        'ia_intervention' => 'Explicar qué falta exactamente',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['status_notification', 'subsanation_guide'],
                'transition_event' => 'application_complete',
            ],
            'conversion' => [
                'steps' => [
                    6 => [
                        'action' => 'receive_resolution',
                        'label' => 'Recibir resolución',
                        'ia_intervention' => 'Guía siguiente fase',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['next_phase_guide'],
                'transition_event' => 'approved',
            ],
        ],
    ];

    /**
     * Journey del Técnico STO.
     */
    const TECNICO_STO_JOURNEY = [
        'avatar' => 'tecnico_sto',
        'vertical' => 'andalucia_ei',
        'kpi_target' => 'expedients_per_day',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'receive_application',
                        'label' => 'Recibir solicitud',
                        'ia_intervention' => 'Pre-validación automática documentación',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['auto_validation'],
                'transition_event' => 'application_received',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'review_documentation',
                        'label' => 'Revisar documentación',
                        'ia_intervention' => 'Generar requerimiento subsanación',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'process_expedient',
                        'label' => 'Procesar expediente',
                        'ia_intervention' => 'Alertas plazo próximo a vencer',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['subsanation_generation', 'deadline_alert'],
                'transition_event' => 'expedient_processed',
            ],
            'retention' => [
                'steps' => [
                    4 => [
                        'action' => 'daily_summary',
                        'label' => 'Resumen diario',
                        'ia_intervention' => 'Resumen expedientes pendientes',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['daily_summary'],
                'transition_event' => 'shift_completed',
            ],
        ],
    ];

    /**
     * Journey del Administrador de Programa.
     */
    const ADMIN_EI_JOURNEY = [
        'avatar' => 'admin_ei',
        'vertical' => 'andalucia_ei',
        'kpi_target' => 'budget_execution',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'dashboard_daily',
                        'label' => 'Dashboard diario',
                        'ia_intervention' => 'KPIs ejecución presupuestaria',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['budget_kpis'],
                'transition_event' => 'dashboard_viewed',
            ],
            'engagement' => [
                'steps' => [
                    2 => [
                        'action' => 'monitor_deviations',
                        'label' => 'Monitorear desviaciones',
                        'ia_intervention' => 'Alerta temprana + causa probable',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'sto_benchmarking',
                        'label' => 'Comparativa STOs',
                        'ia_intervention' => 'Benchmarking rendimiento entidades',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['deviation_alert', 'sto_comparison'],
                'transition_event' => 'monitoring_active',
            ],
            'conversion' => [
                'steps' => [
                    4 => [
                        'action' => 'generate_periodic_report',
                        'label' => 'Generar informe periódico',
                        'ia_intervention' => 'Auto-generación datos actualizados',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['report_generation'],
                'transition_event' => 'report_submitted',
            ],
        ],
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public static function getJourneyDefinition(string $avatar): ?array
    {
        return match ($avatar) {
            'beneficiario_ei' => self::BENEFICIARIO_JOURNEY,
            'tecnico_sto' => self::TECNICO_STO_JOURNEY,
            'admin_ei' => self::ADMIN_EI_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de Andalucía +ei.
     */
    public static function getAvatars(): array
    {
        return ['beneficiario_ei', 'tecnico_sto', 'admin_ei'];
    }

}
