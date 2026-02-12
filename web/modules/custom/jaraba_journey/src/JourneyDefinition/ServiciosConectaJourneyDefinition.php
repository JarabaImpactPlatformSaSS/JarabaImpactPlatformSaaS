<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para ServiciosConecta (2 avatares).
 *
 * Según Doc 103:
 * - Profesional: First Booking <14 días, Booking Rate >70%
 * - Cliente: Time to First Booking <10 min, NPS >50
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 */
class ServiciosConectaJourneyDefinition
{

    /**
     * Journey del Profesional/Proveedor.
     *
     * KPI Target: First Booking <14 días, Booking Rate >70%
     */
    const PROFESIONAL_JOURNEY = [
        'avatar' => 'profesional',
        'vertical' => 'serviciosconecta',
        'kpi_target' => 'first_booking_14_days_rate_70',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'create_professional_profile',
                        'label' => 'Crear perfil profesional',
                        'ia_intervention' => 'Wizard, importación LinkedIn',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['linkedin_import'],
                'transition_event' => 'profile_created',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'define_services_rates',
                        'label' => 'Definir servicios y tarifas',
                        'ia_intervention' => 'Templates especialidad, precios mercado',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'configure_availability',
                        'label' => 'Configurar disponibilidad',
                        'ia_intervention' => 'Sync Google/Outlook, optimizar slots',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['market_pricing', 'calendar_sync'],
                'transition_event' => 'services_configured',
            ],
            'engagement' => [
                'steps' => [
                    4 => [
                        'action' => 'receive_first_booking',
                        'label' => 'Primera reserva entrante',
                        'ia_intervention' => 'Ficha cliente + preparar contexto',
                        'video_url' => '',
                    ],
                    5 => [
                        'action' => 'conduct_session',
                        'label' => 'Realizar sesión (presencial/video)',
                        'ia_intervention' => 'Notas automáticas, transcripción',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['client_context', 'session_notes'],
                'transition_event' => 'first_session_completed',
            ],
            'conversion' => [
                'steps' => [
                    6 => [
                        'action' => 'invoice_and_collect',
                        'label' => 'Facturar y cobrar',
                        'ia_intervention' => 'Facturación automática, recordatorios',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['payment_reminder'],
                'transition_event' => 'payment_received',
            ],
            'retention' => [
                'steps' => [
                    7 => [
                        'action' => 'request_review',
                        'label' => 'Solicitar valoración',
                        'ia_intervention' => '72h post-servicio',
                        'video_url' => '',
                    ],
                    8 => [
                        'action' => 'offer_package',
                        'label' => 'Ofrecer paquete sesiones',
                        'ia_intervention' => 'A cliente recurrente (>3 sesiones)',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['review_request_72h', 'package_upsell'],
                'transition_event' => 'recurring_client',
            ],
        ],
        'cross_sell' => [
            ['after' => 'profile_created', 'offer' => 'Curso optimización perfil'],
            ['after' => 'first_session_completed', 'offer' => 'Herramientas videollamada pro'],
            ['after' => 'recurring_client', 'offer' => 'Plan Premium analytics'],
        ],
    ];

    /**
     * Journey del Cliente de Servicios.
     *
     * KPI Target: Time to First Booking <10 min, NPS >50
     */
    const CLIENTE_SERVICIOS_JOURNEY = [
        'avatar' => 'cliente_servicios',
        'vertical' => 'serviciosconecta',
        'kpi_target' => 'first_booking_10min_nps_50',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'describe_need',
                        'label' => 'Describir necesidad',
                        'ia_intervention' => 'Formulario conversacional, triaje automático',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['need_triage'],
                'transition_event' => 'need_described',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'receive_matches',
                        'label' => 'Recibir matches de profesionales',
                        'ia_intervention' => 'Lista rankeada por fit, explicar match',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'compare_profiles',
                        'label' => 'Comparar perfiles',
                        'ia_intervention' => 'Vista comparativa, destacar diferenciadores',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['match_explanation', 'profile_comparison'],
                'transition_event' => 'professionals_compared',
            ],
            'engagement' => [
                'steps' => [
                    4 => [
                        'action' => 'book_appointment',
                        'label' => 'Reservar cita',
                        'ia_intervention' => 'Sugerir horario óptimo',
                        'video_url' => '',
                    ],
                    5 => [
                        'action' => 'receive_service',
                        'label' => 'Recibir servicio',
                        'ia_intervention' => 'Recordatorios, checklist pre-cita',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['optimal_slot', 'pre_appointment_checklist'],
                'transition_event' => 'service_received',
            ],
            'retention' => [
                'steps' => [
                    6 => [
                        'action' => 'evaluate_and_recommend',
                        'label' => 'Evaluar y recomendar',
                        'ia_intervention' => 'Review + programa referidos',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['referral_program'],
                'transition_event' => 'review_submitted',
            ],
        ],
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public static function getJourneyDefinition(string $avatar): ?array
    {
        return match ($avatar) {
            'profesional' => self::PROFESIONAL_JOURNEY,
            'cliente_servicios' => self::CLIENTE_SERVICIOS_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de ServiciosConecta.
     */
    public static function getAvatars(): array
    {
        return ['profesional', 'cliente_servicios'];
    }

}
