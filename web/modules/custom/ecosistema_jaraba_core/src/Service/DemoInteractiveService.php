<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;

/**
 * Servicio de demo interactiva para Time-to-Value < 60 segundos.
 *
 * PROPÓSITO:
 * Permite a usuarios anónimos experimentar la plataforma con datos
 * sintéticos sin necesidad de registro, logrando el "Magic Moment"
 * en menos de 60 segundos.
 *
 * ALMACENAMIENTO (S1-04):
 * Usa la tabla `demo_sessions` en lugar de State API (blob único).
 * Cada sesión es una fila individual → no DoS, limpieza eficiente por cron.
 *
 * ICONOS (ICON-EMOJI-001):
 * Usa nombres del sistema de iconos Jaraba, no Unicode emojis.
 *
 * Q1 2027 - Gap P0: Instant Value
 */
class DemoInteractiveService
{

    /**
     * TTL de sesiones de demo en segundos (1 hora).
     */
    protected const SESSION_TTL = 3600;

    /**
     * Perfiles de demo disponibles.
     *
     * ICON-EMOJI-001: Iconos del sistema Jaraba (categoría/nombre).
     */
    public const DEMO_PROFILES = [
        'producer' => [
            'id' => 'producer',
            'name' => 'Productor de Aceite',
            'description' => 'Experimenta cómo sería gestionar tu cooperativa de aceite de oliva',
            'icon_category' => 'agro',
            'icon_name' => 'olive',
            'vertical' => 'agroconecta',
            'demo_data' => [
                'products_count' => 12,
                'orders_last_month' => 34,
                'revenue_last_month' => 4250.00,
                'customers_count' => 89,
            ],
        ],
        'winery' => [
            'id' => 'winery',
            'name' => 'Bodega de Vinos',
            'description' => 'Descubre cómo digitalizar tu bodega y llegar a más clientes',
            'icon_category' => 'agro',
            'icon_name' => 'wine',
            'vertical' => 'agroconecta',
            'demo_data' => [
                'products_count' => 24,
                'orders_last_month' => 67,
                'revenue_last_month' => 8900.00,
                'customers_count' => 156,
            ],
        ],
        'cheese' => [
            'id' => 'cheese',
            'name' => 'Quesería Artesanal',
            'description' => 'Visualiza el potencial de tu quesería en el marketplace',
            'icon_category' => 'agro',
            'icon_name' => 'cheese',
            'vertical' => 'agroconecta',
            'demo_data' => [
                'products_count' => 8,
                'orders_last_month' => 45,
                'revenue_last_month' => 3200.00,
                'customers_count' => 112,
            ],
        ],
        'buyer' => [
            'id' => 'buyer',
            'name' => 'Comprador',
            'description' => 'Explora el marketplace como cliente final',
            'icon_category' => 'commerce',
            'icon_name' => 'cart',
            'vertical' => 'comercioconecta',
            'demo_data' => [
                'products_available' => 150,
                'tenants_active' => 23,
                'categories' => 12,
            ],
        ],
        // -- Empleabilidad --------------------------------------------------
        'jobseeker' => [
            'id' => 'jobseeker',
            'name' => 'Buscador de Empleo',
            'description' => 'Descubre cómo encontrar tu próximo empleo con IA',
            'icon_category' => 'verticals',
            'icon_name' => 'empleo',
            'vertical' => 'empleabilidad',
            'demo_data' => [
                'jobs_available' => 245,
                'applications_sent' => 12,
                'interviews_scheduled' => 3,
                'profile_views' => 67,
            ],
        ],
        // -- Emprendimiento --------------------------------------------------
        'startup' => [
            'id' => 'startup',
            'name' => 'Startup / Emprendedor',
            'description' => 'Gestiona y lanza tu startup con herramientas inteligentes',
            'icon_category' => 'verticals',
            'icon_name' => 'rocket',
            'vertical' => 'emprendimiento',
            'demo_data' => [
                'monthly_revenue' => 12500.00,
                'active_clients' => 45,
                'projects_in_progress' => 8,
                'conversion_rate' => 23,
            ],
        ],
        // -- JarabaLex -------------------------------------------------------
        'lawfirm' => [
            'id' => 'lawfirm',
            'name' => 'Despacho de Abogados',
            'description' => 'Digitaliza tu despacho legal con IA y gestión avanzada',
            'icon_category' => 'verticals',
            'icon_name' => 'legal',
            'vertical' => 'jarabalex',
            'demo_data' => [
                'active_cases' => 34,
                'clients_managed' => 120,
                'consultations_month' => 48,
                'revenue_last_month' => 15600.00,
            ],
        ],
        // -- ServiciosConecta ------------------------------------------------
        'servicepro' => [
            'id' => 'servicepro',
            'name' => 'Profesional de Servicios',
            'description' => 'Conecta con clientes y gestiona tus servicios profesionales',
            'icon_category' => 'business',
            'icon_name' => 'handshake',
            'vertical' => 'serviciosconecta',
            'demo_data' => [
                'services_offered' => 15,
                'bookings_last_month' => 38,
                'clients_active' => 72,
                'revenue_last_month' => 6800.00,
            ],
        ],
        // -- Andalucía EI ----------------------------------------------------
        'socialimpact' => [
            'id' => 'socialimpact',
            'name' => 'Empresa de Impacto Social',
            'description' => 'Mide y comunica el impacto social de tu organización',
            'icon_category' => 'business',
            'icon_name' => 'ecosystem',
            'vertical' => 'andalucia_ei',
            'demo_data' => [
                'beneficiaries_reached' => 340,
                'active_programs' => 6,
                'funding_secured' => 45000,
                'volunteer_hours' => 1200,
            ],
        ],
        // -- Content Hub -----------------------------------------------------
        'creator' => [
            'id' => 'creator',
            'name' => 'Creador de Contenido',
            'description' => 'Publica y gestiona tu blog o portal de contenidos',
            'icon_category' => 'actions',
            'icon_name' => 'edit',
            'vertical' => 'jaraba_content_hub',
            'demo_data' => [
                'articles_published' => 78,
                'monthly_views' => 12400,
                'subscribers' => 340,
                'engagement_rate' => 8,
            ],
        ],
        // -- Formación -------------------------------------------------------
        'academy' => [
            'id' => 'academy',
            'name' => 'Academia de Formación',
            'description' => 'Crea y vende cursos online con tu propia plataforma',
            'icon_category' => 'education',
            'icon_name' => 'graduation-cap',
            'vertical' => 'formacion',
            'demo_data' => [
                'courses_available' => 18,
                'students_enrolled' => 456,
                'completion_rate' => 78,
                'revenue_last_month' => 9200.00,
            ],
        ],
    ];

    /**
     * Productos sintéticos para demos.
     */
    protected const SYNTHETIC_PRODUCTS = [
        'producer' => [
            [
                'name' => 'Aceite Virgen Extra Premium',
                'price' => 15.90,
                'stock' => 120,
                'image' => 'demo/olive-oil-1',
                'rating' => 4.8,
                'reviews' => 34,
            ],
            [
                'name' => 'Aceite de Oliva Picual',
                'price' => 12.50,
                'stock' => 85,
                'image' => 'demo/olive-oil-2',
                'rating' => 4.6,
                'reviews' => 28,
            ],
            [
                'name' => 'Aceite Ecológico Arbequina',
                'price' => 18.90,
                'stock' => 45,
                'image' => 'demo/olive-oil-3',
                'rating' => 4.9,
                'reviews' => 52,
            ],
        ],
        'winery' => [
            [
                'name' => 'Tinto Reserva 2020',
                'price' => 24.90,
                'stock' => 180,
                'image' => 'demo/wine-red-1',
                'rating' => 4.7,
                'reviews' => 89,
            ],
            [
                'name' => 'Blanco Verdejo Crianza',
                'price' => 14.50,
                'stock' => 220,
                'image' => 'demo/wine-white-1',
                'rating' => 4.5,
                'reviews' => 67,
            ],
        ],
        'cheese' => [
            [
                'name' => 'Queso Manchego Curado',
                'price' => 22.00,
                'stock' => 35,
                'image' => 'demo/cheese-1',
                'rating' => 4.9,
                'reviews' => 78,
            ],
            [
                'name' => 'Queso de Cabra Semicurado',
                'price' => 16.50,
                'stock' => 42,
                'image' => 'demo/cheese-2',
                'rating' => 4.7,
                'reviews' => 45,
            ],
        ],
        // -- Empleabilidad: ofertas de empleo destacadas ---------------------
        'jobseeker' => [
            [
                'name' => 'Desarrollador Full Stack',
                'price' => 42000.00,
                'stock' => 3,
                'image' => 'demo/job-dev',
                'rating' => 4.8,
                'reviews' => 23,
            ],
            [
                'name' => 'Marketing Digital Manager',
                'price' => 35000.00,
                'stock' => 2,
                'image' => 'demo/job-marketing',
                'rating' => 4.5,
                'reviews' => 15,
            ],
            [
                'name' => 'Consultor de Sostenibilidad',
                'price' => 38000.00,
                'stock' => 1,
                'image' => 'demo/job-sustainability',
                'rating' => 4.7,
                'reviews' => 8,
            ],
        ],
        // -- Emprendimiento: servicios para startups -------------------------
        'startup' => [
            [
                'name' => 'Consultoría Estratégica',
                'price' => 150.00,
                'stock' => 20,
                'image' => 'demo/startup-consulting',
                'rating' => 4.9,
                'reviews' => 34,
            ],
            [
                'name' => 'Desarrollo MVP Express',
                'price' => 2500.00,
                'stock' => 5,
                'image' => 'demo/startup-mvp',
                'rating' => 4.7,
                'reviews' => 18,
            ],
            [
                'name' => 'Pack Marketing Digital',
                'price' => 890.00,
                'stock' => 15,
                'image' => 'demo/startup-marketing',
                'rating' => 4.6,
                'reviews' => 42,
            ],
        ],
        // -- JarabaLex: servicios legales ------------------------------------
        'lawfirm' => [
            [
                'name' => 'Consulta Legal Inicial',
                'price' => 75.00,
                'stock' => 30,
                'image' => 'demo/legal-consultation',
                'rating' => 4.9,
                'reviews' => 67,
            ],
            [
                'name' => 'Asesoría Mercantil',
                'price' => 200.00,
                'stock' => 15,
                'image' => 'demo/legal-corporate',
                'rating' => 4.8,
                'reviews' => 45,
            ],
            [
                'name' => 'Gestión Laboral Completa',
                'price' => 350.00,
                'stock' => 10,
                'image' => 'demo/legal-labor',
                'rating' => 4.7,
                'reviews' => 32,
            ],
        ],
        // -- ServiciosConecta: servicios profesionales -----------------------
        'servicepro' => [
            [
                'name' => 'Reforma Integral Baño',
                'price' => 3500.00,
                'stock' => 4,
                'image' => 'demo/service-reform',
                'rating' => 4.8,
                'reviews' => 56,
            ],
            [
                'name' => 'Instalación Placas Solares',
                'price' => 5200.00,
                'stock' => 6,
                'image' => 'demo/service-solar',
                'rating' => 4.9,
                'reviews' => 38,
            ],
            [
                'name' => 'Diseño de Jardín',
                'price' => 890.00,
                'stock' => 12,
                'image' => 'demo/service-garden',
                'rating' => 4.6,
                'reviews' => 24,
            ],
        ],
        // -- Andalucía EI: programas sociales --------------------------------
        'socialimpact' => [
            [
                'name' => 'Programa de Inclusión Digital',
                'price' => 0.00,
                'stock' => 50,
                'image' => 'demo/impact-digital',
                'rating' => 4.9,
                'reviews' => 89,
            ],
            [
                'name' => 'Huertos Urbanos Comunitarios',
                'price' => 0.00,
                'stock' => 25,
                'image' => 'demo/impact-garden',
                'rating' => 4.8,
                'reviews' => 67,
            ],
            [
                'name' => 'Taller de Emprendimiento Social',
                'price' => 0.00,
                'stock' => 30,
                'image' => 'demo/impact-workshop',
                'rating' => 4.7,
                'reviews' => 45,
            ],
        ],
        // -- Content Hub: artículos destacados -------------------------------
        'creator' => [
            [
                'name' => 'Guía SEO para Emprendedores',
                'price' => 0.00,
                'stock' => 1,
                'image' => 'demo/content-seo',
                'rating' => 4.8,
                'reviews' => 134,
            ],
            [
                'name' => 'Tendencias Marketing 2027',
                'price' => 0.00,
                'stock' => 1,
                'image' => 'demo/content-marketing',
                'rating' => 4.7,
                'reviews' => 89,
            ],
            [
                'name' => 'Caso de Éxito: Cooperativa Verde',
                'price' => 0.00,
                'stock' => 1,
                'image' => 'demo/content-case',
                'rating' => 4.9,
                'reviews' => 56,
            ],
        ],
        // -- Formación: cursos online ----------------------------------------
        'academy' => [
            [
                'name' => 'Desarrollo Web con Drupal',
                'price' => 149.00,
                'stock' => 100,
                'image' => 'demo/course-drupal',
                'rating' => 4.9,
                'reviews' => 234,
            ],
            [
                'name' => 'Marketing Digital Avanzado',
                'price' => 99.00,
                'stock' => 200,
                'image' => 'demo/course-marketing',
                'rating' => 4.7,
                'reviews' => 178,
            ],
            [
                'name' => 'IA Aplicada a Negocios',
                'price' => 199.00,
                'stock' => 50,
                'image' => 'demo/course-ai',
                'rating' => 4.8,
                'reviews' => 145,
            ],
        ],
    ];

    /**
     * Constructor.
     *
     * S1-04: Reemplaza EntityTypeManagerInterface + StateInterface
     * por Connection (base de datos directa).
     */
    public function __construct(
        protected Connection $database,
        protected LoggerChannelFactoryInterface $loggerFactory,
    ) {
    }

    /**
     * Obtiene todos los perfiles de demo disponibles.
     */
    public function getDemoProfiles(): array
    {
        return self::DEMO_PROFILES;
    }

    /**
     * Obtiene un perfil de demo específico.
     */
    public function getDemoProfile(string $profileId): ?array
    {
        return self::DEMO_PROFILES[$profileId] ?? NULL;
    }

    /**
     * Genera datos completos para una sesión de demo.
     *
     * @param string $profileId
     *   ID del perfil de demo.
     * @param string $sessionId
     *   ID de sesión único para la demo.
     * @param string $clientIp
     *   IP del cliente (para tracking y rate limiting en DB).
     *
     * @return array
     *   Datos generados para la demo.
     */
    public function generateDemoSession(string $profileId, string $sessionId, string $clientIp = ''): array
    {
        $profile = $this->getDemoProfile($profileId);

        if (!$profile) {
            return ['error' => 'Perfil no encontrado'];
        }

        // Generar nombre de demo aleatorio.
        $demoNames = [
            'producer' => ['Cooperativa del Valle', 'Olivos de Jaén', 'Finca El Milagro'],
            'winery' => ['Bodegas La Mancha', 'Viñedos del Sur', 'Vinos Artesanos'],
            'cheese' => ['Quesería La Tradición', 'Quesos del Pastor', 'Artesanos del Queso'],
            'buyer' => ['Cliente Demo'],
            'jobseeker' => ['María García', 'Carlos López', 'Ana Martínez'],
            'startup' => ['TechVerde Innovation', 'ImpactHub Andalucía', 'NovaSeed Labs'],
            'lawfirm' => ['Bufete García & Asociados', 'Despacho Legal Andalucía', 'JurisDigital'],
            'servicepro' => ['Servicios Martínez Pro', 'Reformas del Sur', 'Digital Solutions Jaén'],
            'socialimpact' => ['Fundación Impulso Social', 'Asociación Raíces Verdes', 'ONG Futuro Andaluz'],
            'creator' => ['Blog Innovación Verde', 'Revista Digital Sur', 'Canal Emprendedores'],
            'academy' => ['Academia Digital Sur', 'EscuelaOnline Pro', 'FormaTech Andalucía'],
        ];

        $randomName = $demoNames[$profileId][array_rand($demoNames[$profileId])];

        // Generar métricas realistas con variación.
        $baseData = $profile['demo_data'];
        $variation = rand(-10, 20) / 100;

        $metrics = [];
        foreach ($baseData as $key => $value) {
            if (is_numeric($value)) {
                $metrics[$key] = $value * (1 + $variation);
                if (is_float($value)) {
                    $metrics[$key] = round($metrics[$key], 2);
                } else {
                    $metrics[$key] = (int) round($metrics[$key]);
                }
            } else {
                $metrics[$key] = $value;
            }
        }

        // Generar historial de ventas para gráficos.
        $salesHistory = $this->generateSalesHistory(30);

        // Obtener productos sintéticos con SVG placeholders de marca (S3-07).
        $vertical = $profile['vertical'];
        $products = self::SYNTHETIC_PRODUCTS[$profileId] ?? [];
        foreach ($products as &$product) {
            $product['image'] = $this->getPlaceholderSvg($vertical, $product['name']);
        }
        unset($product);

        $now = time();

        // Guardar sesión.
        $sessionData = [
            'session_id' => $sessionId,
            'profile_id' => $profileId,
            'profile' => $profile,
            'tenant_name' => $randomName,
            'metrics' => $metrics,
            'products' => $products,
            'sales_history' => $salesHistory,
            'created' => $now,
            'expires' => $now + self::SESSION_TTL,
            'magic_moment_actions' => $this->getMagicMomentActions($profileId),
            'actions' => [],
        ];

        $this->saveDemoSession($sessionId, $profileId, $clientIp, $sessionData);

        $this->loggerFactory->get('demo_interactive')->info(
            'Demo session created: @session for profile @profile',
            ['@session' => $sessionId, '@profile' => $profileId]
        );

        return $sessionData;
    }

    /**
     * Genera historial de ventas sintético.
     */
    protected function generateSalesHistory(int $days): array
    {
        $history = [];
        $baseValue = rand(80, 200);

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayOfWeek = date('N', strtotime("-{$i} days"));

            // Variación realista: más ventas en fin de semana.
            $weekendMultiplier = ($dayOfWeek >= 6) ? 1.3 : 1.0;
            $randomVariation = 1 + (rand(-20, 30) / 100);

            $history[] = [
                'date' => $date,
                'revenue' => round($baseValue * $weekendMultiplier * $randomVariation, 2),
                'orders' => rand(2, 12),
            ];
        }

        return $history;
    }

    /**
     * Obtiene acciones para el "Magic Moment".
     *
     * ICON-EMOJI-001: Usa icon_category/icon_name del sistema Jaraba.
     * S1-06: URLs generadas via Url::fromRoute() (ROUTE-LANGPREFIX-001).
     * Las acciones con scroll_target=TRUE usan anclas (#) — no necesitan ruta.
     */
    protected function getMagicMomentActions(string $profileId): array
    {
        $actions = [
            'producer' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Visualiza métricas en tiempo real',
                    'icon_category' => 'dashboard',
                    'icon_name' => 'chart-bar',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia',
                    'description' => 'La IA cuenta tu historia',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Productos',
                    'description' => 'Explora el catálogo de ejemplo',
                    'icon_category' => 'commerce',
                    'icon_name' => 'products',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            'winery' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu bodega',
                    'icon_category' => 'dashboard',
                    'icon_name' => 'chart-bar',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia',
                    'description' => 'La IA cuenta tu historia',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
            ],
            'cheese' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu quesería',
                    'icon_category' => 'dashboard',
                    'icon_name' => 'chart-bar',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia',
                    'description' => 'La IA cuenta tu historia',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
            ],
            'buyer' => [
                [
                    'id' => 'browse_marketplace',
                    'label' => 'Explorar Marketplace',
                    'description' => 'Descubre productos locales',
                    'icon_category' => 'commerce',
                    'icon_name' => 'search',
                    'url' => Url::fromRoute('ecosistema_jaraba_core.marketplace.landing')->toString(),
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_categories',
                    'label' => 'Ver Categorías',
                    'description' => 'Filtra por tipo de producto',
                    'icon_category' => 'navigation',
                    'icon_name' => 'filter',
                    'url' => Url::fromRoute('ecosistema_jaraba_core.marketplace.landing')->toString(),
                    'highlight' => FALSE,
                ],
            ],
            // -- Empleabilidad -----------------------------------------------
            'jobseeker' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Perfil',
                    'description' => 'Revisa tu perfil profesional',
                    'icon_category' => 'verticals',
                    'icon_name' => 'empleo',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Optimizar CV con IA',
                    'description' => 'La IA mejora tu currículum',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'browse_marketplace',
                    'label' => 'Ver Ofertas',
                    'description' => 'Explora ofertas de empleo',
                    'icon_category' => 'business',
                    'icon_name' => 'briefcase',
                    'url' => Url::fromRoute('ecosistema_jaraba_core.marketplace.landing')->toString(),
                    'highlight' => FALSE,
                ],
            ],
            // -- Emprendimiento ----------------------------------------------
            'startup' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu startup',
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Pitch con IA',
                    'description' => 'La IA crea tu pitch deck',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Servicios',
                    'description' => 'Explora tu catálogo',
                    'icon_category' => 'commerce',
                    'icon_name' => 'catalog',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            // -- JarabaLex ---------------------------------------------------
            'lawfirm' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu despacho',
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Informe con IA',
                    'description' => 'La IA resume tus casos',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Servicios Legales',
                    'description' => 'Explora tus servicios',
                    'icon_category' => 'legal',
                    'icon_name' => 'law-book',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            // -- ServiciosConecta --------------------------------------------
            'servicepro' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tus servicios',
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Crear Presupuesto IA',
                    'description' => 'La IA genera presupuestos',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Servicios',
                    'description' => 'Explora tu catálogo',
                    'icon_category' => 'business',
                    'icon_name' => 'handshake',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            // -- Andalucía EI ------------------------------------------------
            'socialimpact' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver Impacto',
                    'description' => 'Métricas de impacto social',
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Memoria con IA',
                    'description' => 'La IA genera tu memoria',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Programas',
                    'description' => 'Explora tus programas',
                    'icon_category' => 'business',
                    'icon_name' => 'ecosystem',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            // -- Content Hub -------------------------------------------------
            'creator' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver Métricas',
                    'description' => 'Estadísticas de tu contenido',
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Artículo IA',
                    'description' => 'La IA escribe tu artículo',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Artículos',
                    'description' => 'Explora tus publicaciones',
                    'icon_category' => 'actions',
                    'icon_name' => 'edit',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            // -- Formación ---------------------------------------------------
            'academy' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu academia',
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Crear Curso con IA',
                    'description' => 'La IA diseña tu curso',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Cursos',
                    'description' => 'Explora tu catálogo',
                    'icon_category' => 'education',
                    'icon_name' => 'book-open',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
        ];

        return $actions[$profileId] ?? [];
    }

    /**
     * Guarda una sesión de demo en la tabla demo_sessions.
     *
     * S1-04: Almacenamiento individual por fila (no blob State API).
     */
    protected function saveDemoSession(string $sessionId, string $profileId, string $clientIp, array $data): void
    {
        try {
            $this->database->merge('demo_sessions')
                ->keys(['session_id' => $sessionId])
                ->fields([
                    'profile_id' => $profileId,
                    'client_ip' => $clientIp,
                    'session_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'created' => $data['created'] ?? time(),
                    'expires' => $data['expires'] ?? time() + self::SESSION_TTL,
                ])
                ->execute();
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('demo_interactive')->error(
                'Error saving demo session @session: @error',
                ['@session' => $sessionId, '@error' => $e->getMessage()]
            );
        }
    }

    /**
     * Obtiene una sesión de demo de la tabla.
     *
     * S1-04: Lee de la tabla demo_sessions (no State API).
     */
    public function getDemoSession(string $sessionId): ?array
    {
        try {
            $row = $this->database->select('demo_sessions', 'ds')
                ->fields('ds', ['session_data', 'expires'])
                ->condition('session_id', $sessionId)
                ->condition('expires', time(), '>')
                ->execute()
                ->fetchObject();

            if ($row) {
                return json_decode($row->session_data, TRUE);
            }
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('demo_interactive')->error(
                'Error reading demo session @session: @error',
                ['@session' => $sessionId, '@error' => $e->getMessage()]
            );
        }

        return NULL;
    }

    /**
     * Registra una acción del usuario en la demo.
     *
     * S1-04: Lee la sesión, añade la acción, y la guarda de vuelta.
     */
    public function trackDemoAction(string $sessionId, string $action, array $metadata = []): void
    {
        $session = $this->getDemoSession($sessionId);

        if (!$session) {
            return;
        }

        $session['actions'][] = [
            'action' => $action,
            'timestamp' => time(),
            'metadata' => $metadata,
        ];

        // Actualizar datos de sesión en la tabla.
        try {
            $this->database->update('demo_sessions')
                ->fields([
                    'session_data' => json_encode($session, JSON_UNESCAPED_UNICODE),
                ])
                ->condition('session_id', $sessionId)
                ->execute();
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('demo_interactive')->error(
                'Error updating demo session @session: @error',
                ['@session' => $sessionId, '@error' => $e->getMessage()]
            );
        }

        $this->loggerFactory->get('demo_interactive')->info(
            'Demo action: @action in session @session',
            ['@action' => $action, '@session' => $sessionId]
        );
    }

    /**
     * Calcula el Time-to-First-Value de una sesión.
     */
    public function calculateTTFV(string $sessionId): ?int
    {
        $session = $this->getDemoSession($sessionId);

        if (!$session) {
            return NULL;
        }

        $created = $session['created'];
        $actions = $session['actions'] ?? [];

        // Buscar primera acción de valor.
        $valueActions = ['view_dashboard', 'generate_story', 'browse_marketplace'];

        foreach ($actions as $action) {
            if (in_array($action['action'], $valueActions, TRUE)) {
                return $action['timestamp'] - $created;
            }
        }

        return NULL;
    }

    /**
     * Convierte una sesión de demo a registro real.
     *
     * S1-03: NO crea usuarios directamente. Genera datos de prefill para
     * redirigir al flujo de onboarding existente (/registro/{vertical}).
     * La URL se genera con Url::fromRoute() (ROUTE-LANGPREFIX-001).
     */
    public function convertToRealAccount(string $sessionId, string $email): array
    {
        $session = $this->getDemoSession($sessionId);

        if (!$session) {
            return ['success' => FALSE, 'error' => 'Sesión no válida'];
        }

        $vertical = $session['profile']['vertical'] ?? 'agroconecta';

        // Generar URL de registro con prefill via query params.
        $registrationUrl = Url::fromRoute('ecosistema_jaraba_core.onboarding.register', [
            'vertical' => $vertical,
        ], [
            'query' => [
                'demo_session' => $sessionId,
                'email' => $email,
                'business_name' => $session['tenant_name'] ?? '',
            ],
        ])->toString();

        // Registrar acción de conversión.
        $this->trackDemoAction($sessionId, 'click_cta', [
            'type' => 'conversion',
            'email' => $email,
        ]);

        return [
            'success' => TRUE,
            'redirect_url' => $registrationUrl,
            'prefill' => [
                'business_name' => $session['tenant_name'],
                'vertical' => $vertical,
                'profile_type' => $session['profile_id'],
            ],
        ];
    }

    /**
     * Limpia sesiones expiradas de la tabla.
     *
     * S1-05: Llamado desde hook_cron.
     *
     * @return int
     *   Número de sesiones eliminadas.
     */
    /**
     * Genera un SVG placeholder data URI con colores de marca del vertical.
     *
     * S3-07: Reemplaza las referencias a imágenes inexistentes en
     * SYNTHETIC_PRODUCTS con SVGs generados dinámicamente que usan
     * la paleta de colores de cada vertical.
     *
     * @param string $vertical
     *   ID canónico del vertical (VERTICAL-CANONICAL-001).
     * @param string $productName
     *   Nombre del producto (se extraen las iniciales para el SVG).
     *
     * @return string
     *   Data URI del SVG (data:image/svg+xml,...).
     */
    protected function getPlaceholderSvg(string $vertical, string $productName): string
    {
        $colors = [
            'agroconecta' => ['bg' => '#556B2F', 'accent' => '#FF8C42'],
            'comercioconecta' => ['bg' => '#233D63', 'accent' => '#FF8C42'],
            'empleabilidad' => ['bg' => '#00A9A5', 'accent' => '#233D63'],
            'emprendimiento' => ['bg' => '#FF8C42', 'accent' => '#233D63'],
            'jarabalex' => ['bg' => '#233D63', 'accent' => '#00A9A5'],
            'serviciosconecta' => ['bg' => '#00A9A5', 'accent' => '#FF8C42'],
            'andalucia_ei' => ['bg' => '#3E4E23', 'accent' => '#FF8C42'],
            'jaraba_content_hub' => ['bg' => '#233D63', 'accent' => '#00A9A5'],
            'formacion' => ['bg' => '#00A9A5', 'accent' => '#233D63'],
        ];

        $c = $colors[$vertical] ?? ['bg' => '#233D63', 'accent' => '#FF8C42'];

        // Extraer las 2 primeras letras significativas (skip artículos/preposiciones).
        $words = preg_split('/\s+/', $productName);
        $initials = '';
        foreach ($words as $word) {
            $lower = mb_strtolower($word);
            if (in_array($lower, ['de', 'del', 'la', 'el', 'los', 'las', 'con', 'para', 'y'], TRUE)) {
                continue;
            }
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            if (mb_strlen($initials) >= 2) {
                break;
            }
        }
        if (mb_strlen($initials) < 2) {
            $initials = mb_strtoupper(mb_substr($productName, 0, 2));
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">'
            . '<rect width="400" height="300" rx="12" fill="' . $c['bg'] . '"/>'
            . '<text x="200" y="165" text-anchor="middle" font-family="Outfit,Arial,sans-serif" '
            . 'font-size="72" font-weight="700" fill="' . $c['accent'] . '">'
            . htmlspecialchars($initials, ENT_XML1, 'UTF-8')
            . '</text></svg>';

        return 'data:image/svg+xml,' . rawurlencode($svg);
    }

    public function cleanupExpiredSessions(): int
    {
        try {
            return (int) $this->database->delete('demo_sessions')
                ->condition('expires', time(), '<')
                ->execute();
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('demo_interactive')->error(
                'Error cleaning up demo sessions: @error',
                ['@error' => $e->getMessage()]
            );
            return 0;
        }
    }

}
