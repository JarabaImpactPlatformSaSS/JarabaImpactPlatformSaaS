<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

/**
 * Definición de journeys para AgroConecta (3 avatares).
 *
 * Según Doc 103:
 * - Productor: GMV mensual, primeros productos
 * - Comprador B2B: Pedido recurrente, aprovisionamiento
 * - Consumidor: Ticket medio, compra local
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 */
class AgroConectaJourneyDefinition
{

    /**
     * Journey del Productor Agrícola.
     *
     * KPI Target: Time to First Product Published < 5 minutos
     */
    const PRODUCTOR_JOURNEY = [
        'avatar' => 'productor',
        'vertical' => 'agroconecta',
        'kpi_target' => 'first_product_published_under_5min',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'landing_view',
                        'label' => 'Ver landing de AgroConecta',
                        'ia_intervention' => 'Detectar origen → personalizar mensaje',
                        'video_url' => '',
                    ],
                    2 => [
                        'action' => 'click_start_selling',
                        'label' => 'Click en Empezar a Vender',
                        'ia_intervention' => 'Mostrar formulario mínimo',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['welcome_producer'],
                'transition_event' => 'profile_started',
            ],
            'activation' => [
                'steps' => [
                    3 => [
                        'action' => 'upload_first_photo',
                        'label' => 'Subir primera foto de producto',
                        'ia_intervention' => 'Producer Copilot: genera descripción, sugiere precio',
                        'video_url' => '',
                    ],
                    4 => [
                        'action' => 'confirm_product',
                        'label' => 'Revisar y confirmar producto',
                        'ia_intervention' => 'Celebración + sugerir más productos',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['product_photo_uploaded', 'suggest_more_products'],
                'transition_event' => 'first_product_published',
            ],
            'engagement' => [
                'steps' => [
                    5 => [
                        'action' => 'receive_first_order',
                        'label' => 'Recibir primer pedido',
                        'ia_intervention' => 'Guía de preparación de envío',
                        'video_url' => '',
                    ],
                    6 => [
                        'action' => 'complete_first_shipment',
                        'label' => 'Completar primer envío',
                        'ia_intervention' => 'Solicitar review + cross-sell',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['first_order_received', 'shipment_guide'],
                'transition_event' => 'first_sale_completed',
            ],
            'conversion' => [
                'steps' => [
                    7 => [
                        'action' => 'reach_10_sales',
                        'label' => 'Alcanzar 10 ventas',
                        'ia_intervention' => 'Ofrecer certificación premium',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['upsell_certification'],
                'transition_event' => 'sales_milestone_10',
            ],
            'retention' => [
                'steps' => [
                    8 => [
                        'action' => 'maintain_catalog',
                        'label' => 'Mantener catálogo actualizado',
                        'ia_intervention' => 'Alertas de stock bajo',
                        'video_url' => '',
                    ],
                    9 => [
                        'action' => 'respond_reviews',
                        'label' => 'Responder reviews',
                        'ia_intervention' => 'Sugerir respuestas',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['stock_low_alert', 'review_response_helper'],
                'transition_event' => 'consistent_sales',
            ],
        ],
        'cross_sell' => [
            ['after' => 'first_product_published', 'offer' => 'Kit Digital fotografía producto'],
            ['after' => 'first_sale_completed', 'offer' => 'Curso packaging y envío'],
            ['after' => 'reach_10_products', 'offer' => 'Plan Profesional analytics'],
            ['after' => 'first_review', 'offer' => 'Certificación calidad DOP/IGP'],
        ],
    ];

    /**
     * Journey del Comprador B2B.
     *
     * KPI Target: Time to First Order < 48h desde registro
     */
    const COMPRADOR_B2B_JOURNEY = [
        'avatar' => 'comprador_b2b',
        'vertical' => 'agroconecta',
        'kpi_target' => 'first_order_under_48h',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'search_ingredient',
                        'label' => 'Buscar ingrediente específico',
                        'ia_intervention' => 'Ordenar por match con historial',
                        'video_url' => '',
                    ],
                    2 => [
                        'action' => 'view_b2b_filters',
                        'label' => 'Usar filtros B2B (volumen, precio/kg)',
                        'ia_intervention' => 'Activar vista profesional',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['b2b_mode_activated'],
                'transition_event' => 'b2b_search_initiated',
            ],
            'activation' => [
                'steps' => [
                    3 => [
                        'action' => 'compare_suppliers',
                        'label' => 'Comparar proveedores',
                        'ia_intervention' => 'Destacar diferenciadores clave',
                        'video_url' => '',
                    ],
                    4 => [
                        'action' => 'request_sample',
                        'label' => 'Solicitar muestra',
                        'ia_intervention' => 'Autocompletar datos empresa',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['supplier_comparison', 'sample_request'],
                'transition_event' => 'sample_requested',
            ],
            'engagement' => [
                'steps' => [
                    5 => [
                        'action' => 'approve_sample',
                        'label' => 'Aprobar muestra',
                        'ia_intervention' => 'Sugerir cantidad óptima según temporada',
                        'video_url' => '',
                    ],
                    6 => [
                        'action' => 'first_wholesale_order',
                        'label' => 'Realizar primer pedido mayorista',
                        'ia_intervention' => 'Mostrar descuentos por volumen',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['sample_approved', 'wholesale_discounts'],
                'transition_event' => 'first_order_completed',
            ],
            'retention' => [
                'steps' => [
                    7 => [
                        'action' => 'setup_recurring_order',
                        'label' => 'Configurar pedido recurrente',
                        'ia_intervention' => 'Ajustar según patrones de consumo',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['reorder_cycle', 'consumption_patterns'],
                'transition_event' => 'recurring_enabled',
            ],
        ],
        'cross_sell' => [
            ['after' => 'first_order_completed', 'offer' => 'Descuento proveedor preferente'],
            ['after' => 'recurring_enabled', 'offer' => 'Plan B2B Premium'],
        ],
    ];

    /**
     * Journey del Consumidor Final.
     *
     * KPI Target: Conversion Rate > 3%, AOV > 45€
     */
    const CONSUMIDOR_JOURNEY = [
        'avatar' => 'consumidor',
        'vertical' => 'agroconecta',
        'kpi_target' => 'conversion_3_percent_aov_45',
        'states' => [
            'discovery' => [
                'steps' => [
                    1 => [
                        'action' => 'discover_via_social',
                        'label' => 'Descubrir via RRSS/búsqueda',
                        'ia_intervention' => 'Personalizar según origen',
                        'video_url' => '',
                    ],
                    2 => [
                        'action' => 'explore_categories',
                        'label' => 'Explorar categorías',
                        'ia_intervention' => 'Destacar productos cercanos y populares',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['personalize_by_origin'],
                'transition_event' => 'category_explored',
            ],
            'activation' => [
                'steps' => [
                    3 => [
                        'action' => 'read_producer_story',
                        'label' => 'Leer historia del productor',
                        'ia_intervention' => 'Generar confianza con trazabilidad',
                        'video_url' => '',
                    ],
                    4 => [
                        'action' => 'add_to_cart',
                        'label' => 'Añadir al carrito',
                        'ia_intervention' => 'Recomendar maridajes, cestas',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['traceability_info', 'product_recommendations'],
                'transition_event' => 'cart_created',
            ],
            'conversion' => [
                'steps' => [
                    5 => [
                        'action' => 'checkout',
                        'label' => 'Completar checkout',
                        'ia_intervention' => 'Guardar preferencias para futuro',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['checkout_assistance', 'save_preferences'],
                'transition_event' => 'purchase_completed',
            ],
            'retention' => [
                'steps' => [
                    6 => [
                        'action' => 'receive_order',
                        'label' => 'Recibir pedido',
                        'ia_intervention' => 'Enviar recetas, consejos conservación',
                        'video_url' => '',
                    ],
                    7 => [
                        'action' => 'leave_review',
                        'label' => 'Dejar valoración',
                        'ia_intervention' => 'Incentivar con puntos/descuento',
                        'video_url' => '',
                    ],
                ],
                'triggers' => ['post_purchase_content', 'review_request'],
                'transition_event' => 'review_submitted',
            ],
        ],
        'cross_sell' => [
            ['after' => 'cart_created', 'offer' => 'Cesta gourmet completa'],
            ['after' => 'purchase_completed', 'offer' => 'Suscripción caja mensual'],
            ['after' => 'review_submitted', 'offer' => '10% próxima compra'],
        ],
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public static function getJourneyDefinition(string $avatar): ?array
    {
        return match ($avatar) {
            'productor' => self::PRODUCTOR_JOURNEY,
            'comprador_b2b' => self::COMPRADOR_B2B_JOURNEY,
            'consumidor' => self::CONSUMIDOR_JOURNEY,
            default => NULL,
        };
    }

    /**
     * Obtiene todos los avatares de AgroConecta.
     */
    public static function getAvatars(): array
    {
        return ['productor', 'comprador_b2b', 'consumidor'];
    }

    /**
     * Obtiene los steps para un estado específico de un avatar.
     */
    public static function getStepsForState(string $avatar, string $state): array
    {
        $journey = self::getJourneyDefinition($avatar);
        return $journey['states'][$state]['steps'] ?? [];
    }

    /**
     * Obtiene el cross-sell relevante para un evento.
     */
    public static function getCrossSellForEvent(string $avatar, string $event): ?array
    {
        $journey = self::getJourneyDefinition($avatar);

        if (!$journey) {
            return NULL;
        }

        foreach ($journey['cross_sell'] ?? [] as $offer) {
            if ($offer['after'] === $event) {
                return $offer;
            }
        }

        return NULL;
    }

}
