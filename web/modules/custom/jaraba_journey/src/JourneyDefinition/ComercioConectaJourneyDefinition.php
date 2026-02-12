<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para ComercioConecta (2 avatares).
 *
 * Según Doc 103:
 * - Comerciante: Ventas online +30% MoM, Store Setup <30 min
 * - Comprador Local: Click-to-Reserve <60s, Return Rate >40%
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 */
class ComercioConectaJourneyDefinition
{

    /**
     * Journey del Comerciante Local.
     *
     * KPI Target: First Sale Online <7 días, Store Setup <30 min
     */
    const COMERCIANTE_JOURNEY = [
        'avatar' => 'comerciante',
        'vertical' => 'comercioconecta',
        'kpi_target' => 'first_sale_7_days_setup_30min',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'register_business',
                        'label' => 'Registrar negocio',
                        'ia_intervention' => 'Wizard 3 pasos, importar Google My Business',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['gmb_import'],
                'transition_event' => 'business_registered',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'configure_online_store',
                        'label' => 'Configurar tienda online',
                        'ia_intervention' => 'Plantillas por sector pre-configuradas',
                        'video_url' => '',
                    ],
                    3 => [
                        'action' => 'upload_products_mobile',
                        'label' => 'Subir productos (fotos móvil)',
                        'ia_intervention' => 'Procesamiento batch, fichas automáticas IA',
                        'video_url' => '',
                    ],
                    4 => [
                        'action' => 'connect_pos',
                        'label' => 'Conectar TPV/POS (opcional)',
                        'ia_intervention' => 'Sincronización automática stock',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['sector_template', 'batch_upload', 'pos_sync'],
                'transition_event' => 'store_configured',
            ],
            'engagement' => [
                'steps' => [
                    5 => [
                        'action' => 'publish_flash_offer',
                        'label' => 'Publicar primera oferta flash',
                        'ia_intervention' => 'Sugerir horario óptimo publicación',
                        'video_url' => '',
                    ],
                    6 => [
                        'action' => 'generate_dynamic_qr',
                        'label' => 'Generar QR dinámico',
                        'ia_intervention' => 'Tracking escaneos tiempo real',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['optimal_timing', 'qr_tracking'],
                'transition_event' => 'first_promotion',
            ],
            'conversion' => [
                'steps' => [
                    7 => [
                        'action' => 'first_online_sale',
                        'label' => 'Primera venta online',
                        'ia_intervention' => 'Celebración + cross-sell',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['first_sale_celebration'],
                'transition_event' => 'first_sale_completed',
            ],
            'retention' => [
                'steps' => [
                    8 => [
                        'action' => 'collect_reviews',
                        'label' => 'Recolectar reseñas',
                        'ia_intervention' => 'Solicitud automática post-venta',
                        'video_url' => '',
                    ],
                    9 => [
                        'action' => 'optimize_seo_local',
                        'label' => 'Optimizar SEO local',
                        'ia_intervention' => 'Auditoría SEO + acciones sugeridas',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['review_request', 'seo_audit'],
                'transition_event' => 'established_presence',
            ],
        ],
        'cross_sell' => [
            ['after' => 'store_configured', 'offer' => 'Fotografía profesional'],
            ['after' => 'first_sale_completed', 'offer' => 'Curso marketing digital'],
            ['after' => 'reach_50_products', 'offer' => 'Plan analytics avanzado'],
            ['after' => 'flash_offers_10', 'offer' => 'Sistema fidelización clientes'],
        ],
    ];

    /**
     * Journey del Comprador Local.
     *
     * KPI Target: Click-to-Reserve <60 segundos, Return Rate >40%
     */
    const COMPRADOR_LOCAL_JOURNEY = [
        'avatar' => 'comprador_local',
        'vertical' => 'comercioconecta',
        'kpi_target' => 'click_reserve_60s_return_40',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'scan_qr_storefront',
                        'label' => 'Escanear QR en escaparate',
                        'ia_intervention' => 'Landing tienda con ofertas activas',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['time_limited_offers'],
                'transition_event' => 'store_discovered',
            ],
            'activation' => [
                'steps' => [
                    2 => [
                        'action' => 'explore_catalog',
                        'label' => 'Explorar catálogo desde casa',
                        'ia_intervention' => 'Ordenar por relevancia personal',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['personalized_order'],
                'transition_event' => 'catalog_explored',
            ],
            'engagement' => [
                'steps' => [
                    3 => [
                        'action' => 'reserve_for_pickup',
                        'label' => 'Reservar producto para recoger',
                        'ia_intervention' => 'Recordatorio antes de cierre',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['pickup_reminder'],
                'transition_event' => 'reservation_made',
            ],
            'conversion' => [
                'steps' => [
                    4 => [
                        'action' => 'pickup_in_store',
                        'label' => 'Recoger en tienda',
                        'ia_intervention' => 'Check-in digital + puntos fidelidad',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['loyalty_points', 'related_products'],
                'transition_event' => 'pickup_completed',
            ],
            'retention' => [
                'steps' => [
                    5 => [
                        'action' => 'leave_review',
                        'label' => 'Dejar reseña',
                        'ia_intervention' => 'Incentivar con puntos/descuento',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['review_incentive'],
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
            'comerciante' => self::COMERCIANTE_JOURNEY,
            'comprador_local' => self::COMPRADOR_LOCAL_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de ComercioConecta.
     */
    public static function getAvatars(): array
    {
        return ['comerciante', 'comprador_local'];
    }

}
