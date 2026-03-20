<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Event\DemoSessionEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    use StringTranslationTrait;

    /**
     * TTL de sesiones de demo en segundos (1 hora).
     */
    protected const SESSION_TTL = 3600;

    /**
     * Perfiles de demo disponibles.
     *
     * ICON-EMOJI-001: Iconos del sistema Jaraba (categoría/nombre).
     */
    /**
     * Perfiles de demo ordenados por potencialidad de mercado y conversion.
     *
     * Criterios de priorizacion:
     * 1. Ticket medio mensual (mayor = primero)
     * 2. TAM espanol/andaluz (mayor = primero)
     * 3. Urgencia de digitalizacion del sector
     * 4. Viralidad y efecto red del vertical
     *
     * @see docs/implementacion/2026-03-19_Plan_Implementacion_Demo_Elevacion_Conversion_Clase_Mundial_v1.md §2.3
     */
    public const DEMO_PROFILES = [
        // -- JarabaLex (ticket alto: 200-350 EUR/mes, 147K despachos ES) ----
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
        // -- Emprendimiento (ticket medio-alto, alta viralidad) -------------
        'startup' => [
            'id' => 'startup',
            'name' => 'Emprendedor',
            'description' => 'Gestiona y lanza tu negocio con herramientas inteligentes',
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
        // -- Formacion (ingresos recurrentes alumnos x cursos) --------------
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
        // -- ServiciosConecta (alto volumen autonomos Andalucia) -------------
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
        // -- AgroConecta: Bodega (D.O. andaluzas, ticket medio) -------------
        'winery' => [
            'id' => 'winery',
            'name' => 'Bodega de Vinos',
            'description' => 'Descubre cómo digitalizar tu bodega y llegar a más clientes',
            'icon_category' => 'verticals',
            'icon_name' => 'wine',
            'vertical' => 'agroconecta',
            'demo_data' => [
                'products_count' => 24,
                'orders_last_month' => 67,
                'revenue_last_month' => 8900.00,
                'customers_count' => 156,
            ],
        ],
        // -- AgroConecta: Aceite (Jaen lider mundial) -----------------------
        'producer' => [
            'id' => 'producer',
            'name' => 'Productor de Aceite',
            'description' => 'Experimenta cómo sería gestionar tu cooperativa de aceite de oliva',
            'icon_category' => 'verticals',
            'icon_name' => 'olive',
            'vertical' => 'agroconecta',
            'demo_data' => [
                'products_count' => 12,
                'orders_last_month' => 34,
                'revenue_last_month' => 4250.00,
                'customers_count' => 89,
            ],
        ],
        // -- Empleabilidad (alto volumen, freemium) -------------------------
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
        // -- ComercioConecta: Tienda Gourmet (ticket alto, turismo, cross-sell AgroConecta) --
        'gourmet' => [
            'id' => 'gourmet',
            'name' => 'Tienda Gourmet',
            'description' => 'Vende productos locales premium con tu propia tienda online',
            'icon_category' => 'business',
            'icon_name' => 'storefront',
            'vertical' => 'comercioconecta',
            'demo_data' => [
                'products_count' => 48,
                'orders_last_month' => 89,
                'revenue_last_month' => 7400.00,
                'customers_count' => 234,
            ],
        ],
        // -- ComercioConecta: Boutique Moda (maximo volumen, supervivencia digital) --
        'boutique' => [
            'id' => 'boutique',
            'name' => 'Boutique de Moda',
            'description' => 'Digitaliza tu tienda de moda y compite con las grandes marcas',
            'icon_category' => 'commerce',
            'icon_name' => 'store',
            'vertical' => 'comercioconecta',
            'demo_data' => [
                'products_count' => 120,
                'orders_last_month' => 67,
                'revenue_last_month' => 8900.00,
                'customers_count' => 312,
            ],
        ],
        // -- ComercioConecta: Estetica y Bienestar (crecimiento, alta frecuencia) --
        'beautypro' => [
            'id' => 'beautypro',
            'name' => 'Centro de Estética',
            'description' => 'Gestiona reservas, clientes y ventas de tu centro de belleza',
            'icon_category' => 'commerce',
            'icon_name' => 'star',
            'vertical' => 'comercioconecta',
            'demo_data' => [
                'bookings_last_month' => 156,
                'revenue_last_month' => 12400.00,
                'clients_active' => 89,
                'products_count' => 35,
            ],
        ],
        // -- Andalucia EI (nicho institucional, funding publico) ------------
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
        // -- Content Hub (soporte transversal) ------------------------------
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
        // -- AgroConecta: Queso (nicho reducido) ----------------------------
        'cheese' => [
            'id' => 'cheese',
            'name' => 'Quesería Artesanal',
            'description' => 'Visualiza el potencial de tu quesería en el catálogo digital',
            'icon_category' => 'verticals',
            'icon_name' => 'cheese',
            'vertical' => 'agroconecta',
            'demo_data' => [
                'products_count' => 8,
                'orders_last_month' => 45,
                'revenue_last_month' => 3200.00,
                'customers_count' => 112,
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
        // -- ComercioConecta: Tienda Gourmet -----------------------------------
        'gourmet' => [
            [
                'name' => 'Cesta Gourmet Andaluza Premium',
                'price' => 59.90,
                'stock' => 35,
                'image' => 'demo/gourmet-cesta',
                'rating' => 4.9,
                'reviews' => 67,
            ],
            [
                'name' => 'Selección Ibérico D.O. Los Pedroches',
                'price' => 89.90,
                'stock' => 20,
                'image' => 'demo/gourmet-iberico',
                'rating' => 4.8,
                'reviews' => 45,
            ],
            [
                'name' => 'Pack Conservas Artesanas Mar y Tierra',
                'price' => 34.50,
                'stock' => 60,
                'image' => 'demo/gourmet-conservas',
                'rating' => 4.7,
                'reviews' => 38,
            ],
        ],
        // -- ComercioConecta: Boutique Moda ------------------------------------
        'boutique' => [
            [
                'name' => 'Vestido Lino Artesanal Colección Primavera',
                'price' => 89.00,
                'stock' => 15,
                'image' => 'demo/boutique-vestido',
                'rating' => 4.8,
                'reviews' => 34,
            ],
            [
                'name' => 'Bolso Cuero Natural Hecho a Mano',
                'price' => 125.00,
                'stock' => 8,
                'image' => 'demo/boutique-bolso',
                'rating' => 4.9,
                'reviews' => 56,
            ],
            [
                'name' => 'Camiseta Algodón Orgánico Diseño Local',
                'price' => 39.90,
                'stock' => 45,
                'image' => 'demo/boutique-camiseta',
                'rating' => 4.7,
                'reviews' => 28,
            ],
        ],
        // -- ComercioConecta: Centro de Estética -------------------------------
        'beautypro' => [
            [
                'name' => 'Tratamiento Facial Antiedad Premium',
                'price' => 75.00,
                'stock' => 999,
                'image' => 'demo/beauty-facial',
                'rating' => 4.9,
                'reviews' => 89,
            ],
            [
                'name' => 'Pack Masaje Descontracturante + Aromaterapia',
                'price' => 65.00,
                'stock' => 999,
                'image' => 'demo/beauty-masaje',
                'rating' => 4.8,
                'reviews' => 67,
            ],
            [
                'name' => 'Sérum Vitamina C Profesional 30ml',
                'price' => 42.00,
                'stock' => 24,
                'image' => 'demo/beauty-serum',
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
                'image' => 'demo/legal-consulta',
                'rating' => 4.9,
                'reviews' => 67,
            ],
            [
                'name' => 'Asesoría Mercantil',
                'price' => 200.00,
                'stock' => 15,
                'image' => 'demo/legal-mercantil',
                'rating' => 4.8,
                'reviews' => 45,
            ],
            [
                'name' => 'Gestión Laboral Completa',
                'price' => 350.00,
                'stock' => 10,
                'image' => 'demo/legal-laboral',
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
        protected ?EventDispatcherInterface $eventDispatcher = NULL,
    ) {
    }

    /**
     * Obtiene todos los perfiles de demo disponibles.
     *
     * S6-01: Nombres y descripciones traducidos via StringTranslationTrait.
     */
    public function getDemoProfiles(): array
    {
        return array_map([$this, 'translateProfile'], self::DEMO_PROFILES);
    }

    /**
     * Obtiene un perfil de demo específico.
     *
     * S6-01: Traduce nombre y descripción.
     */
    public function getDemoProfile(string $profileId): ?array
    {
        $profile = self::DEMO_PROFILES[$profileId] ?? NULL;
        return $profile ? $this->translateProfile($profile) : NULL;
    }

    /**
     * Traduce las cadenas de un perfil de demo.
     *
     * S6-01: Separa datos estáticos (constante) de la traducción (runtime).
     */
    protected function translateProfile(array $profile): array
    {
        $profile['name'] = (string) $this->t($profile['name']);
        $profile['description'] = (string) $this->t($profile['description']);
        return $profile;
    }

    /**
     * HAL-DEMO-V3-I18N-003: Cadenas literales para extracción PO.
     *
     * Los extractores PO (potx, drush locale:export) solo detectan llamadas
     * a $this->t() con string literals, no con variables. Este método declara
     * todas las cadenas de los perfiles demo para que sean extraíbles.
     *
     * @return array
     *   Array de TranslatableMarkup (no usado en runtime, solo para PO).
     *
     * @codeCoverageIgnore
     */
    protected function getTranslatableStrings(): array {
        return [
            // Profile names (ordenados por potencialidad de mercado).
            $this->t('Despacho de Abogados'),
            $this->t('Emprendedor'),
            $this->t('Academia de Formación'),
            $this->t('Profesional de Servicios'),
            $this->t('Bodega de Vinos'),
            $this->t('Productor de Aceite'),
            $this->t('Buscador de Empleo'),
            $this->t('Tienda Gourmet'),
            $this->t('Boutique de Moda'),
            $this->t('Centro de Estética'),
            $this->t('Empresa de Impacto Social'),
            $this->t('Creador de Contenido'),
            $this->t('Quesería Artesanal'),
            // Profile descriptions (mismo orden).
            $this->t('Digitaliza tu despacho legal con IA y gestión avanzada'),
            $this->t('Gestiona y lanza tu negocio con herramientas inteligentes'),
            $this->t('Crea y vende cursos online con tu propia plataforma'),
            $this->t('Conecta con clientes y gestiona tus servicios profesionales'),
            $this->t('Descubre cómo digitalizar tu bodega y llegar a más clientes'),
            $this->t('Experimenta cómo sería gestionar tu cooperativa de aceite de oliva'),
            $this->t('Descubre cómo encontrar tu próximo empleo con IA'),
            $this->t('Vende productos locales premium con tu propia tienda online'),
            $this->t('Digitaliza tu tienda de moda y compite con las grandes marcas'),
            $this->t('Gestiona reservas, clientes y ventas de tu centro de belleza'),
            $this->t('Mide y comunica el impacto social de tu organización'),
            $this->t('Publica y gestiona tu blog o portal de contenidos'),
            $this->t('Visualiza el potencial de tu quesería en el catálogo digital'),
        ];
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
    public function generateDemoSession(string $profileId, string $sessionId, string $clientIp = '', array $abVariants = []): array
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
            'gourmet' => ['Selecta Andaluza Gourmet', 'Delicias del Sur', 'La Despensa de María'],
            'boutique' => ['Moda Atelier Sevilla', 'Boutique La Flamenca', 'Estilo Andaluz Urban'],
            'beautypro' => ['Centro Belleza Armonía', 'Estética Natural Sevilla', 'Beauty Lab Málaga'],
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

        // Productos con imagen específica por producto > perfil > SVG fallback.
        $vertical = $profile['vertical'];
        $products = self::SYNTHETIC_PRODUCTS[$profileId] ?? [];
        $themeImgDir = 'themes/custom/ecosistema_jaraba_theme/images/demo/';
        $basePath = base_path();
        $productIndex = 0;
        foreach ($products as &$product) {
            // 1. Imagen específica del producto (ej: legal-consulta.webp).
            $productImageKey = $product['image'];
            $productSlug = preg_replace('/^demo\//', '', $productImageKey);
            $productImgPath = $themeImgDir . $productSlug . '.webp';
            if ($productSlug !== '' && file_exists(DRUPAL_ROOT . '/' . $productImgPath)) {
                $product['image'] = $basePath . $productImgPath;
            }
            // 2. Imagen del perfil (ej: lawfirm.webp).
            elseif (file_exists(DRUPAL_ROOT . '/' . $themeImgDir . $profileId . '.webp')) {
                $product['image'] = $basePath . $themeImgDir . $profileId . '.webp';
            }
            // 3. SVG placeholder.
            else {
                $product['image'] = $this->getPlaceholderSvg($vertical, $product['name']);
            }
            $productIndex++;
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
            'ab_variants' => $abVariants,
        ];

        $this->saveDemoSession($sessionId, $profileId, $clientIp, $sessionData);

        // S7-02: Dispatch session created event.
        $this->dispatchEvent(DemoSessionEvent::CREATED, $sessionId, $profileId, [
            'vertical' => $vertical,
            'tenant_name' => $randomName,
        ]);

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
                    'icon_category' => 'analytics',
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
                    'icon_name' => 'catalog',
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
                    'icon_category' => 'analytics',
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
                    'label' => 'Ver tus Vinos',
                    'description' => 'Explora tu catálogo de vinos',
                    'icon_category' => 'commerce',
                    'icon_name' => 'catalog',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            'cheese' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu quesería',
                    'icon_category' => 'analytics',
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
            'gourmet' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu tienda gourmet',
                    'icon_category' => 'analytics',
                    'icon_name' => 'chart-bar',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia con IA',
                    'description' => 'La IA cuenta la historia de tu tienda',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver tu Catálogo',
                    'description' => 'Explora tus productos premium',
                    'icon_category' => 'commerce',
                    'icon_name' => 'catalog',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            'boutique' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu boutique',
                    'icon_category' => 'analytics',
                    'icon_name' => 'chart-bar',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Lookbook con IA',
                    'description' => 'La IA crea tu lookbook',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver tu Colección',
                    'description' => 'Explora tu catálogo de moda',
                    'icon_category' => 'commerce',
                    'icon_name' => 'catalog',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
                ],
            ],
            'beautypro' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu centro',
                    'icon_category' => 'analytics',
                    'icon_name' => 'chart-bar',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Promoción con IA',
                    'description' => 'La IA crea promociones personalizadas',
                    'icon_category' => 'ai',
                    'icon_name' => 'sparkles',
                    'url' => '__storytelling__',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver tus Servicios',
                    'description' => 'Explora tu catálogo de tratamientos',
                    'icon_category' => 'commerce',
                    'icon_name' => 'catalog',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
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
                    'id' => 'view_products',
                    'label' => 'Ver Ofertas',
                    'description' => 'Explora ofertas de empleo',
                    'icon_category' => 'business',
                    'icon_name' => 'briefcase',
                    'url' => '#products',
                    'highlight' => FALSE,
                    'scroll_target' => TRUE,
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

        $profileActions = $actions[$profileId] ?? [];

        // S6-01: Traducir etiquetas y descripciones de acciones.
        return array_map(function (array $action): array {
            $action['label'] = (string) $this->t($action['label']);
            $action['description'] = (string) $this->t($action['description']);
            return $action;
        }, $profileActions);
    }

    /**
     * Guarda una sesión de demo en la tabla demo_sessions.
     *
     * S1-04: Almacenamiento individual por fila (no blob State API).
     */
    protected function saveDemoSession(string $sessionId, string $profileId, string $clientIp, array $data): void
    {
        try {
            // S6-09: Anonimizar IP con hash diario (GDPR).
            $hashedIp = $clientIp ? hash('sha256', $clientIp . date('Y-m-d') . Settings::getHashSalt()) : '';

            $this->database->merge('demo_sessions')
                ->keys(['session_id' => $sessionId])
                ->fields([
                    'profile_id' => $profileId,
                    'client_ip' => $hashedIp,
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
        // S6-10: Transaction para evitar race conditions en read-modify-write.
        $transaction = $this->database->startTransaction();
        try {
            $row = $this->database->select('demo_sessions', 'ds')
                ->fields('ds', ['session_data', 'expires'])
                ->condition('session_id', $sessionId)
                ->condition('expires', time(), '>')
                ->execute()
                ->fetchObject();

            if (!$row) {
                return;
            }

            $session = json_decode($row->session_data, TRUE);
            if (!is_array($session)) {
                return;
            }

            $session['actions'][] = [
                'action' => $action,
                'timestamp' => time(),
                'metadata' => $metadata,
            ];

            $this->database->update('demo_sessions')
                ->fields([
                    'session_data' => json_encode($session, JSON_UNESCAPED_UNICODE),
                ])
                ->condition('session_id', $sessionId)
                ->execute();

            // S7-02: Dispatch value action event for qualifying actions.
            if (in_array($action, DemoSessionEvent::VALUE_ACTIONS, TRUE)) {
                $profileId = $session['profile_id'] ?? $session['profile']['id'] ?? 'unknown';
                $this->dispatchEvent(DemoSessionEvent::VALUE_ACTION, $sessionId, $profileId, [
                    'action' => $action,
                    'metadata' => $metadata,
                ]);
            }
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

        // S6-11: HMAC token temporal — no exponer email en query params.
        $demoToken = hash_hmac('sha256', $sessionId . '|' . $email, Settings::getHashSalt());

        $registrationUrl = Url::fromRoute('ecosistema_jaraba_core.onboarding.register', [
            'vertical' => $vertical,
        ], [
            'query' => [
                'demo_token' => $demoToken,
                'demo_session' => $sessionId,
            ],
        ])->toString();

        // Registrar acción de conversión.
        $this->trackDemoAction($sessionId, 'click_cta', [
            'type' => 'conversion',
            'email' => $email,
        ]);

        // S7-02: Dispatch conversion event.
        $profileId = $session['profile_id'] ?? $session['profile']['id'] ?? 'unknown';
        $this->dispatchEvent(DemoSessionEvent::CONVERSION, $sessionId, $profileId, [
            'vertical' => $vertical,
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

    /**
     * Genera la historia demo por perfil.
     *
     * S6-12: Extraído de DemoController::demoAiStorytelling() a servicio.
     *
     * @param string $profileId
     *   ID del perfil de demo.
     * @param string $tenantName
     *   Nombre del tenant simulado.
     *
     * @return string
     *   Historia generada (string traducido).
     */
    public function getDemoStory(string $profileId, string $tenantName): string
    {
        $stories = [
            'producer' => (string) $this->t(
                '**@name** representa la tradición olivarera de más de tres generaciones. En las laderas de Sierra Mágina, donde el sol y la brisa mediterránea crean el microclima perfecto, nuestros olivos centenarios producen un aceite de oliva virgen extra de calidad excepcional. Cada gota cuenta la historia de una familia comprometida con la excelencia.',
                ['@name' => $tenantName],
            ),
            'winery' => (string) $this->t(
                '**@name** nace de la pasión por el terruño y la tradición vinícola. En nuestros viñedos, cultivados con métodos sostenibles, las variedades autóctonas encuentran la expresión perfecta de un territorio único. Cada botella es un viaje sensorial que captura la esencia de nuestra tierra.',
                ['@name' => $tenantName],
            ),
            'cheese' => (string) $this->t(
                'En **@name**, cada queso es el resultado de un proceso artesanal transmitido de generación en generación. Nuestros maestros queseros seleccionan la mejor leche de ganaderías locales para crear productos únicos que honran la tradición y deleitan los paladares más exigentes.',
                ['@name' => $tenantName],
            ),
            'gourmet' => (string) $this->t(
                '**@name** es referencia en productos gourmet de Andalucía. Desde selecciones de ibérico con D.O. hasta conservas artesanas del litoral, cada producto ha sido elegido por su calidad excepcional y trazabilidad completa. La tienda online extiende la experiencia de la tienda física a clientes de toda España y turistas que quieren repetir sabores desde su país.',
                ['@name' => $tenantName],
            ),
            'boutique' => (string) $this->t(
                '**@name** demuestra que la moda independiente tiene un espacio único frente a las grandes cadenas. Con diseños propios, materiales sostenibles y una atención personalizada que ninguna marca global puede replicar, ha encontrado en la plataforma digital el escaparate perfecto para llegar a clientas de toda España que valoran la autenticidad.',
                ['@name' => $tenantName],
            ),
            'beautypro' => (string) $this->t(
                '**@name** ha transformado la gestión de su centro de estética con la plataforma digital. De coordinar citas por WhatsApp a tener un sistema de reservas online con recordatorios automáticos, venta cruzada de productos y un programa de fidelización que mantiene a sus clientas mes tras mes. La IA sugiere promociones personalizadas que han aumentado la frecuencia de visita un 18%.',
                ['@name' => $tenantName],
            ),
            'jobseeker' => (string) $this->t(
                '**@name** está construyendo una carrera profesional orientada al impacto. Con herramientas de IA que optimizan su currículum y sugieren itinerarios formativos, cada paso es más estratégico. La plataforma conecta talento con empresas que comparten valores de sostenibilidad e innovación social.',
                ['@name' => $tenantName],
            ),
            'startup' => (string) $this->t(
                '**@name** nació con la misión de transformar su sector a través de la tecnología y la innovación. Desde la validación de la idea hasta la captación de clientes, nuestra plataforma acompaña cada fase del emprendimiento con métricas inteligentes, marketing automatizado y una comunidad de mentores.',
                ['@name' => $tenantName],
            ),
            'lawfirm' => (string) $this->t(
                '**@name** combina la solidez de la tradición jurídica con la eficiencia de las herramientas digitales. Gestión inteligente de expedientes, análisis de jurisprudencia con IA y comunicación segura con clientes: así es como un despacho moderno marca la diferencia en Andalucía.',
                ['@name' => $tenantName],
            ),
            'servicepro' => (string) $this->t(
                '**@name** ofrece servicios profesionales de alta calidad respaldados por la confianza de sus clientes. La plataforma le permite gestionar citas, generar presupuestos inteligentes con IA y construir una reputación sólida basada en reseñas verificadas y trabajo bien hecho.',
                ['@name' => $tenantName],
            ),
            'socialimpact' => (string) $this->t(
                '**@name** trabaja cada día para generar un impacto positivo en la comunidad. Con herramientas de medición de impacto social, gestión de programas y comunicación transparente, nuestra plataforma ayuda a organizaciones como esta a amplificar su labor y atraer colaboradores comprometidos.',
                ['@name' => $tenantName],
            ),
            'creator' => (string) $this->t(
                '**@name** crea contenido que inspira, educa y conecta. Con un editor avanzado, analíticas de audiencia y optimización SEO asistida por IA, cada publicación alcanza a más lectores. La plataforma es el hogar perfecto para creadores que quieren profesionalizar su labor editorial.',
                ['@name' => $tenantName],
            ),
            'academy' => (string) $this->t(
                '**@name** forma a los profesionales del mañana con cursos online de primer nivel. Desde la creación de contenido didáctico con IA hasta el seguimiento del progreso de cada alumno, nuestra plataforma LMS ofrece una experiencia de aprendizaje que transforma conocimiento en oportunidades.',
                ['@name' => $tenantName],
            ),
        ];

        return $stories[$profileId] ?? (string) $this->t('Historia generada por IA para @name.', ['@name' => $tenantName]);
    }

    /**
     * Obtiene los escenarios del AI Playground.
     *
     * S6-12: Extraído de DemoController::aiPlayground() a servicio.
     *
     * @return array
     *   Array de escenarios con id, title, description, icon, prompt.
     */
    public function getAiScenarios(): array
    {
        return [
            [
                'id' => 'marketing',
                'title' => (string) $this->t('Marketing Digital'),
                'description' => (string) $this->t('Genera ideas de campañas, contenido para redes sociales y estrategias de marketing.'),
                'icon' => 'automation',
                'prompt' => (string) $this->t('Necesito ideas para una campaña en redes sociales para una marca de alimentación ecológica dirigida a millennials.'),
            ],
            [
                'id' => 'legal',
                'title' => (string) $this->t('Consulta Legal'),
                'description' => (string) $this->t('Obtén orientación sobre cuestiones legales para emprendedores y empresas.'),
                'icon' => 'screening',
                'prompt' => (string) $this->t('¿Cuáles son los requisitos legales para crear una cooperativa en España?'),
            ],
            [
                'id' => 'employment',
                'title' => (string) $this->t('Empleabilidad'),
                'description' => (string) $this->t('Optimiza tu CV, prepara entrevistas y descubre itinerarios profesionales.'),
                'icon' => 'chat',
                'prompt' => (string) $this->t('Ayúdame a optimizar mi CV para un puesto de marketing digital. Tengo 3 años de experiencia.'),
            ],
            [
                'id' => 'entrepreneurship',
                'title' => (string) $this->t('Emprendimiento'),
                'description' => (string) $this->t('Valida ideas de negocio, construye tu canvas y planifica tu lanzamiento.'),
                'icon' => 'rocket',
                'prompt' => (string) $this->t('Quiero validar una idea SaaS para gestión de restaurantes. ¿Por dónde empiezo?'),
            ],
        ];
    }

    /**
     * Obtiene la conversación demo del copilot IA adaptada al perfil.
     *
     * Cada vertical tiene una pregunta y respuesta que demuestra el valor
     * específico del copilot para ese tipo de profesional.
     *
     * @param string $profileId
     *   ID del perfil demo.
     *
     * @return array{question: string, answer: string}
     *   Pregunta y respuesta adaptadas.
     */
    public function getCopilotDemoChat(string $profileId): array
    {
        $chats = [
            'lawfirm' => [
                'question' => (string) $this->t('¿Cuáles son los plazos para contestar una demanda laboral?'),
                'answer' => (string) $this->t('Según el art. 82.1 LRJS, tienes 10 días hábiles desde la notificación para contestar. Te recomiendo: (1) Revisa la fecha de notificación en tu expediente, (2) Prepara la documentación con el módulo de gestión documental, (3) Agenda la vista oral usando el calendario integrado con LexNET.'),
            ],
            'startup' => [
                'question' => (string) $this->t('¿Cómo valido si mi idea de negocio tiene mercado?'),
                'answer' => (string) $this->t('Te guío paso a paso: (1) Usa el Canvas de Modelo de Negocio para mapear tu propuesta de valor, (2) Ejecuta el Diagnóstico de Madurez para identificar gaps, (3) Con la IA de proyecciones financieras, simula escenarios de ingresos a 12 meses con diferentes tasas de conversión.'),
            ],
            'academy' => [
                'question' => (string) $this->t('¿Cómo creo mi primer curso online con certificado?'),
                'answer' => (string) $this->t('Con el editor de cursos puedes: (1) Estructurar módulos con vídeo, texto y quiz interactivos, (2) Configurar certificados automáticos al completar el 80% del contenido, (3) Publicar en tu catálogo con pasarela de pago integrada. La IA te sugiere la estructura óptima según tu temática.'),
            ],
            'servicepro' => [
                'question' => (string) $this->t('¿Cómo organizo mi agenda de servicios esta semana?'),
                'answer' => (string) $this->t('Veo que tienes 8 citas pendientes. Te recomiendo: (1) Agrupa las reformas del barrio Nervión para el martes (ahorras 45 min de desplazamiento), (2) El presupuesto de placas solares lleva 3 días sin respuesta — activa el recordatorio automático, (3) Genera la factura del jardín completado ayer con un clic.'),
            ],
            'winery' => [
                'question' => (string) $this->t('¿Cómo mejoro las ventas de mi bodega online?'),
                'answer' => (string) $this->t('Analizando tus datos: (1) Tu Tinto Reserva tiene un 4.8 de valoración — destácalo como "Más Vendido" en tu catálogo, (2) Activa la trazabilidad QR para la nueva añada — los clientes premium valoran la transparencia, (3) Programa una campaña de email para clientes que compraron hace más de 3 meses.'),
            ],
            'producer' => [
                'question' => (string) $this->t('¿Cómo puedo predecir la demanda de aceite para la próxima campaña?'),
                'answer' => (string) $this->t('Basándome en tu historial: (1) La demanda de Virgen Extra Premium creció un 23% interanual — aumenta producción, (2) El Picual ecológico tiene lista de espera — considera priorizar 500 unidades, (3) Activa alertas de stock mínimo para no perder ventas en temporada alta.'),
            ],
            'jobseeker' => [
                'question' => (string) $this->t('¿Cómo optimizo mi CV para posiciones de marketing digital?'),
                'answer' => (string) $this->t('He analizado tu perfil: (1) Añade métricas concretas — "Aumenté el tráfico orgánico un 120% en 6 meses" impacta más que "Gestión de SEO", (2) El simulador de entrevistas detectó 3 preguntas frecuentes para tu perfil, (3) LinkedIn Import puede sincronizar tu experiencia en un clic.'),
            ],
            'socialimpact' => [
                'question' => (string) $this->t('¿Cómo mido el impacto real de nuestro programa de inclusión?'),
                'answer' => (string) $this->t('Con el módulo de métricas de impacto: (1) Configura indicadores ODS alineados con tu programa, (2) El dashboard muestra participantes activos, empleos generados y horas de formación en tiempo real, (3) Genera informes automáticos para financiadores con datos verificables.'),
            ],
            'creator' => [
                'question' => (string) $this->t('¿Cómo posiciono mi próximo artículo en Google?'),
                'answer' => (string) $this->t('La IA de SEO analiza tu borrador: (1) Tu keyword principal "marketing sostenible 2027" tiene baja competencia — buen nicho, (2) Añade 3 subtítulos H2 que responden preguntas frecuentes de Google, (3) El asistente de co-escritura sugiere ampliar la sección de casos prácticos para superar las 1.500 palabras.'),
            ],
            'gourmet' => [
                'question' => (string) $this->t('¿Cómo aumento las ventas de cestas gourmet en temporada de Navidad?'),
                'answer' => (string) $this->t('Analizando tus datos: (1) Las cestas Premium tuvieron +34% de demanda en diciembre — crea un pack "Navidad Andaluza" con tus 3 productos más vendidos, (2) Activa la preventa con descuento del 10% para clientes recurrentes vía email, (3) El copilot puede generar descripciones SEO para cada cesta y publicar automáticamente en tu tienda.'),
            ],
            'boutique' => [
                'question' => (string) $this->t('¿Cómo compito con Zara y Shein teniendo solo 120 productos?'),
                'answer' => (string) $this->t('Tu ventaja es la diferenciación: (1) Activa las fichas de producto con historia del diseñador y materiales — el 68% de compradoras millennials valoran la transparencia, (2) Crea un lookbook estacional con el generador IA y publícalo en redes con un clic, (3) Configura la recomendación "Completa tu look" que aumenta el ticket medio un 23% según tus datos.'),
            ],
            'beautypro' => [
                'question' => (string) $this->t('¿Cómo reduzco las cancelaciones de última hora en mi centro?'),
                'answer' => (string) $this->t('Analizando tus datos: (1) Las cancelaciones bajan un 40% con recordatorio SMS 24h antes — actívalo en Ajustes > Notificaciones, (2) Implementa la política de cancelación con cargo del 20% (ya configurada en la plataforma, solo activa el toggle), (3) Las clientas con bono de 5 sesiones cancelan un 65% menos — crea un bono con descuento del 15% como incentivo.'),
            ],
            'cheese' => [
                'question' => (string) $this->t('¿Cómo configuro la trazabilidad por lote de mi queso curado?'),
                'answer' => (string) $this->t('Con el módulo de trazabilidad: (1) Genera códigos QR únicos por lote vinculados a fecha de elaboración y materia prima, (2) El cliente escanea y ve origen, proceso de curación y certificaciones, (3) Activa alertas de stock mínimo para no quedarte sin tu curado más vendido en temporada alta.'),
            ],
        ];

        // Fallback genérico.
        $default = [
            'question' => (string) $this->t('¿Cómo puedo hacer crecer mi negocio este mes?'),
            'answer' => (string) $this->t('Basándome en tus datos, te recomiendo 3 estrategias: (1) Optimiza tu presencia online con el Page Builder, (2) Activa el CRM para hacer seguimiento de tus contactos, (3) Usa el copilot IA para automatizar tareas repetitivas y ganar 2 horas al día.'),
        ];

        return $chats[$profileId] ?? $default;
    }

    /**
     * Obtiene el contexto personalizado por vertical para el dashboard demo.
     *
     * Cada vertical recibe: titular, features destacadas, etiqueta de productos
     * y CTA narrativo. Esto transforma el dashboard genérico en una experiencia
     * personalizada que muestra el producto real del vertical.
     *
     * @param string $profileId
     *   ID del perfil demo.
     *
     * @return array<string, mixed>
     *   Contexto vertical con headline, features, products_label, narrative_cta.
     */
    public function getVerticalContext(string $profileId): array {
        $contexts = [
            'lawfirm' => [
                'headline' => (string) $this->t('Tu despacho legal, bajo control'),
                'products_label' => (string) $this->t('Tus Servicios Legales'),
                'narrative_cta' => (string) $this->t('Gestiona tu despacho completo con IA'),
                'features' => [
                    [
                        'icon_category' => 'verticals',
                        'icon_name' => 'legal',
                        'title' => (string) $this->t('Gestión de expedientes'),
                        'description' => (string) $this->t('Numeración automática, estados, plazos y asignación a abogados. Todo el ciclo de vida del caso.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('IA jurídica especializada'),
                        'description' => (string) $this->t('Analiza jurisprudencia, sugiere estrategias y genera borradores de contratos con cláusulas RGPD.'),
                    ],
                    [
                        'icon_category' => 'compliance',
                        'icon_name' => 'signature',
                        'title' => (string) $this->t('Firma digital eIDAS'),
                        'description' => (string) $this->t('Firma cualificada con validez legal en toda la UE. Contratos, poderes y documentos procesales.'),
                    ],
                    [
                        'icon_category' => 'legal',
                        'icon_name' => 'gavel',
                        'title' => (string) $this->t('Presentación de escritos'),
                        'description' => (string) $this->t('Conexión con juzgados para presentar escritos y notificaciones electrónicas. Integración con LexNET.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'briefcase',
                        'title' => (string) $this->t('Gestión de clientes y facturación'),
                        'description' => (string) $this->t('CRM integrado con historial de consultas, facturación automática y seguimiento de cobros.'),
                    ],
                ],
            ],
            'startup' => [
                'headline' => (string) $this->t('Tu negocio, desde la idea hasta la facturación'),
                'products_label' => (string) $this->t('Tus Servicios'),
                'narrative_cta' => (string) $this->t('Lanza y gestiona tu negocio con IA'),
                'features' => [
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'canvas',
                        'title' => (string) $this->t('Modelo de negocio con IA'),
                        'description' => (string) $this->t('Plantillas por sector, análisis de competencia y refinamiento automático de tu propuesta de valor.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'chart-bar',
                        'title' => (string) $this->t('Proyecciones financieras'),
                        'description' => (string) $this->t('Modela tus ingresos, gastos y flujo de caja a 5 años con múltiples escenarios.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'gauge',
                        'title' => (string) $this->t('Diagnóstico de madurez'),
                        'description' => (string) $this->t('Evalúa tu nivel de desarrollo empresarial y recibe una hoja de ruta personalizada.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'cart',
                        'title' => (string) $this->t('Facturación y cobros'),
                        'description' => (string) $this->t('Genera facturas, cobra con tarjeta o Bizum y lleva la contabilidad básica integrada.'),
                    ],
                ],
            ],
            'academy' => [
                'headline' => (string) $this->t('Tu academia, lista para vender cursos'),
                'products_label' => (string) $this->t('Tus Cursos'),
                'narrative_cta' => (string) $this->t('Crea y vende cursos con tu propia marca'),
                'features' => [
                    [
                        'icon_category' => 'education',
                        'icon_name' => 'book-open',
                        'title' => (string) $this->t('Creación de cursos completa'),
                        'description' => (string) $this->t('Lecciones en vídeo, cuestionarios, materiales descargables y certificados automáticos.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'gauge',
                        'title' => (string) $this->t('Seguimiento de progreso'),
                        'description' => (string) $this->t('Monitoriza el avance de cada alumno en tiempo real con analíticas por curso y lección.'),
                    ],
                    [
                        'icon_category' => 'achievement',
                        'icon_name' => 'trophy',
                        'title' => (string) $this->t('Insignias y certificados'),
                        'description' => (string) $this->t('Sistema de logros que motiva al alumno. Certificados verificables con código QR.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'cart',
                        'title' => (string) $this->t('Cobro integrado'),
                        'description' => (string) $this->t('Suscripciones, paquetes de cursos y cupones de descuento. Cobra desde el primer día.'),
                    ],
                ],
            ],
            'servicepro' => [
                'headline' => (string) $this->t('Gestiona tus servicios con tu propia marca'),
                'products_label' => (string) $this->t('Tus Servicios'),
                'narrative_cta' => (string) $this->t('Agenda, presupuestos y reseñas en una sola plataforma'),
                'features' => [
                    [
                        'icon_category' => 'ui',
                        'icon_name' => 'calendar',
                        'title' => (string) $this->t('Agenda inteligente'),
                        'description' => (string) $this->t('Calendario de citas con confirmación automática, recordatorios y gestión de disponibilidad.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Presupuestos con IA'),
                        'description' => (string) $this->t('Genera presupuestos profesionales adaptados a cada cliente en segundos.'),
                    ],
                    [
                        'icon_category' => 'compliance',
                        'icon_name' => 'signature',
                        'title' => (string) $this->t('Contratos digitales'),
                        'description' => (string) $this->t('Firma digital de contratos de servicio con validez legal. Sin papel ni desplazamientos.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'star',
                        'title' => (string) $this->t('Reseñas verificadas'),
                        'description' => (string) $this->t('Reputación profesional construida con opiniones reales de tus clientes.'),
                    ],
                ],
            ],
            'winery' => [
                'headline' => (string) $this->t('Tu bodega digital, del viñedo a la mesa'),
                'products_label' => (string) $this->t('Tus Vinos'),
                'narrative_cta' => (string) $this->t('Vende tus vinos directamente al consumidor'),
                'features' => [
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'store',
                        'title' => (string) $this->t('Tienda digital con tu marca'),
                        'description' => (string) $this->t('Catálogo de vinos con fichas de cata, añadas, maridajes y precios personalizados.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'qr-code',
                        'title' => (string) $this->t('Trazabilidad QR del viñedo a la botella'),
                        'description' => (string) $this->t('El cliente escanea y ve origen, variedad, proceso de vinificación y certificaciones.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'truck',
                        'title' => (string) $this->t('Gestión de pedidos y envíos'),
                        'description' => (string) $this->t('Pedidos online con tracking, embalaje seguro y alertas de stock automáticas.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'chart-bar',
                        'title' => (string) $this->t('Analíticas de ventas por temporada'),
                        'description' => (string) $this->t('Dashboard con tendencias de demanda, productos estrella y previsión de campañas.'),
                    ],
                ],
            ],
            'producer' => [
                'headline' => (string) $this->t('Tu cooperativa digital, del olivar a tu mesa'),
                'products_label' => (string) $this->t('Tus Aceites'),
                'narrative_cta' => (string) $this->t('Vende aceite premium directamente al consumidor'),
                'features' => [
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'store',
                        'title' => (string) $this->t('Tienda digital propia con tu marca'),
                        'description' => (string) $this->t('Catálogo con variedades, formatos, precios y certificaciones ecológicas.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'qr-code',
                        'title' => (string) $this->t('Trazabilidad QR del olivar a la botella'),
                        'description' => (string) $this->t('Códigos QR en cada botella con origen, cosecha, análisis de calidad y finca.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'package',
                        'title' => (string) $this->t('Gestión de pedidos con alertas'),
                        'description' => (string) $this->t('Pedidos online, alertas de stock mínimo y gestión de envíos nacionales e internacionales.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Previsión de demanda con IA'),
                        'description' => (string) $this->t('La IA analiza tu historial de ventas para predecir demanda y optimizar producción.'),
                    ],
                ],
            ],
            'cheese' => [
                'headline' => (string) $this->t('Tu quesería artesanal en el mundo digital'),
                'products_label' => (string) $this->t('Tus Quesos'),
                'narrative_cta' => (string) $this->t('Lleva tus quesos artesanales a toda España'),
                'features' => [
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'catalog',
                        'title' => (string) $this->t('Catálogo con certificaciones'),
                        'description' => (string) $this->t('Fichas de producto con tipo de leche, curación, denominación de origen y alérgenos.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'qr-code',
                        'title' => (string) $this->t('Trazabilidad QR por lote'),
                        'description' => (string) $this->t('Cada lote trazable: fecha de elaboración, materia prima, proceso de curación.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'truck',
                        'title' => (string) $this->t('Logística de frío integrada'),
                        'description' => (string) $this->t('Gestión de pedidos con envío refrigerado y control de cadena de frío.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Historia generada por IA'),
                        'description' => (string) $this->t('La IA narra la tradición de tu quesería para conectar emocionalmente con el comprador.'),
                    ],
                ],
            ],
            'gourmet' => [
                'headline' => (string) $this->t('Tu tienda gourmet, abierta al mundo'),
                'products_label' => (string) $this->t('Tu Catálogo Gourmet'),
                'narrative_cta' => (string) $this->t('Vende productos premium con tu propia marca online'),
                'features' => [
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'storefront',
                        'title' => (string) $this->t('Tienda online profesional'),
                        'description' => (string) $this->t('Catálogo con fotos, fichas de producto, trazabilidad y certificaciones de calidad.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'truck',
                        'title' => (string) $this->t('Gestión de pedidos y envíos'),
                        'description' => (string) $this->t('Pedidos online con tracking, envío refrigerado y alertas de stock automáticas.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Copilot IA para tu tienda'),
                        'description' => (string) $this->t('Genera descripciones de producto, responde clientes y optimiza precios con IA.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'chart-bar',
                        'title' => (string) $this->t('Analíticas de ventas'),
                        'description' => (string) $this->t('Dashboard con revenue, productos estrella, tendencias de demanda por temporada.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'star',
                        'title' => (string) $this->t('Reseñas verificadas'),
                        'description' => (string) $this->t('Reputación construida con opiniones reales. Confianza que convierte visitantes en compradores.'),
                    ],
                ],
            ],
            'boutique' => [
                'headline' => (string) $this->t('Tu boutique online, con tu estilo único'),
                'products_label' => (string) $this->t('Tu Colección'),
                'narrative_cta' => (string) $this->t('Vende moda con personalidad propia'),
                'features' => [
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'store',
                        'title' => (string) $this->t('Tienda con tu marca'),
                        'description' => (string) $this->t('Catálogo visual con tallas, colores, lookbooks y guía de tallas integrada.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Lookbook generado por IA'),
                        'description' => (string) $this->t('Crea combinaciones de outfits, descripciones de producto y contenido para redes en segundos.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'gauge',
                        'title' => (string) $this->t('Gestión de stock inteligente'),
                        'description' => (string) $this->t('Control de tallas, colores y temporadas. Alertas de reposición automáticas.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'cart',
                        'title' => (string) $this->t('Checkout optimizado'),
                        'description' => (string) $this->t('Pago con tarjeta, Bizum y contrareembolso. Devolución fácil en 14 días.'),
                    ],
                ],
            ],
            'beautypro' => [
                'headline' => (string) $this->t('Tu centro de belleza, digitalizado al completo'),
                'products_label' => (string) $this->t('Tus Servicios y Productos'),
                'narrative_cta' => (string) $this->t('Reservas, ventas y fidelización sin comisiones'),
                'features' => [
                    [
                        'icon_category' => 'ui',
                        'icon_name' => 'calendar',
                        'title' => (string) $this->t('Agenda online de reservas'),
                        'description' => (string) $this->t('Reservas 24/7 con confirmación automática, recordatorios SMS y gestión de disponibilidad.'),
                    ],
                    [
                        'icon_category' => 'commerce',
                        'icon_name' => 'cart',
                        'title' => (string) $this->t('Venta de productos en cabina'),
                        'description' => (string) $this->t('Vende cremas, sérums y tratamientos domiciliarios directamente tras el servicio.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'star',
                        'title' => (string) $this->t('Programa de fidelización'),
                        'description' => (string) $this->t('Puntos, bonos de sesiones y descuentos por recurrencia que retienen clientas.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Promociones inteligentes con IA'),
                        'description' => (string) $this->t('Promociones personalizadas según historial de cada clienta. Aumenta la frecuencia de visita.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'chart-bar',
                        'title' => (string) $this->t('Analíticas del centro'),
                        'description' => (string) $this->t('Revenue por servicio, tasa de ocupación, ratio de cancelación y clientas activas.'),
                    ],
                ],
            ],
            'jobseeker' => [
                'headline' => (string) $this->t('Tu carrera profesional, impulsada por IA'),
                'products_label' => (string) $this->t('Ofertas Destacadas'),
                'narrative_cta' => (string) $this->t('Encuentra tu próximo empleo con IA'),
                'features' => [
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'cv-optimized',
                        'title' => (string) $this->t('CV inteligente en 5 plantillas'),
                        'description' => (string) $this->t('Crea tu currículum profesional optimizado para ATS con diseño visual atractivo.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('IA que detecta habilidades'),
                        'description' => (string) $this->t('Analiza tu perfil, sugiere itinerarios formativos y detecta competencias transferibles.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'interview',
                        'title' => (string) $this->t('Simulador de entrevistas'),
                        'description' => (string) $this->t('Prepara entrevistas con preguntas reales por sector y feedback personalizado.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'career-connect',
                        'title' => (string) $this->t('Importación desde LinkedIn'),
                        'description' => (string) $this->t('Sincroniza tu experiencia profesional con un clic y mantén tu perfil actualizado.'),
                    ],
                ],
            ],
            'socialimpact' => [
                'headline' => (string) $this->t('Mide y amplifica tu impacto social'),
                'products_label' => (string) $this->t('Tus Programas'),
                'narrative_cta' => (string) $this->t('Gestiona programas sociales con datos de impacto'),
                'features' => [
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'chart-bar',
                        'title' => (string) $this->t('Panel de métricas de impacto'),
                        'description' => (string) $this->t('Dashboard con indicadores ODS, beneficiarios alcanzados y horas de formación.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'group',
                        'title' => (string) $this->t('Gestión de participantes'),
                        'description' => (string) $this->t('Inscripción, seguimiento y formación adaptativa para cada participante del programa.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Memoria de impacto con IA'),
                        'description' => (string) $this->t('Genera informes de impacto automáticos para financiadores con datos verificables.'),
                    ],
                    [
                        'icon_category' => 'business',
                        'icon_name' => 'career-connect',
                        'title' => (string) $this->t('Seguimiento de inserción laboral'),
                        'description' => (string) $this->t('Rastrea la evolución profesional de participantes tras completar tus programas.'),
                    ],
                ],
            ],
            'creator' => [
                'headline' => (string) $this->t('Publica, posiciona y conecta con tu audiencia'),
                'products_label' => (string) $this->t('Tus Artículos'),
                'narrative_cta' => (string) $this->t('Escribe con IA y aparece en Google'),
                'features' => [
                    [
                        'icon_category' => 'actions',
                        'icon_name' => 'edit',
                        'title' => (string) $this->t('Editor con co-escritura IA'),
                        'description' => (string) $this->t('Escribe artículos con asistencia IA que sugiere estructura, titulares y ampliaciones.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'gauge',
                        'title' => (string) $this->t('Optimización SEO automática'),
                        'description' => (string) $this->t('Cada artículo se optimiza para Google: keywords, meta tags, headings y legibilidad.'),
                    ],
                    [
                        'icon_category' => 'ai',
                        'icon_name' => 'sparkles',
                        'title' => (string) $this->t('Recomendaciones semánticas'),
                        'description' => (string) $this->t('Sugiere artículos relacionados a tus lectores basándose en su historial de lectura.'),
                    ],
                    [
                        'icon_category' => 'analytics',
                        'icon_name' => 'active',
                        'title' => (string) $this->t('Analíticas de audiencia'),
                        'description' => (string) $this->t('Visitas, tiempo de lectura, tasa de interacción y suscriptores por artículo.'),
                    ],
                ],
            ],
        ];

        return $contexts[$profileId] ?? [
            'headline' => (string) $this->t('Tu negocio digital'),
            'products_label' => (string) $this->t('Tus Productos'),
            'narrative_cta' => (string) $this->t('Gestiona tu negocio con IA'),
            'features' => [],
        ];
    }

    /**
     * Obtiene el número de sesiones demo activas.
     *
     * S7-05: Social proof counter para la landing.
     */
    public function getActiveDemoCount(): int
    {
        try {
            return (int) $this->database->select('demo_sessions', 's')
                ->condition('s.expires', time(), '>')
                ->countQuery()
                ->execute()
                ->fetchField();
        }
        catch (\Exception) {
            return 0;
        }
    }

    /**
     * Limpia sesiones expiradas con agregación previa a demo_analytics.
     *
     * S7-01/S7-08: Lee sesiones expiradas → agrega por fecha/vertical/perfil
     * → UPSERT en demo_analytics → elimina sesiones expiradas.
     */
    public function cleanupExpiredSessions(): int
    {
        $logger = $this->loggerFactory->get('demo_interactive');

        try {
            $now = time();

            // 1. Leer sesiones expiradas antes de eliminar.
            $expired = $this->database->select('demo_sessions', 's')
                ->fields('s', ['session_id', 'profile_id', 'session_data', 'created'])
                ->condition('s.expires', $now, '<')
                ->execute()
                ->fetchAll();

            if (empty($expired)) {
                return 0;
            }

            // S7-02: Dispatch EXPIRED event per session.
            foreach ($expired as $expiredRow) {
                $expData = json_decode($expiredRow->session_data, TRUE) ?? [];
                $this->dispatchEvent(
                    DemoSessionEvent::EXPIRED,
                    $expiredRow->session_id,
                    $expiredRow->profile_id,
                    ['vertical' => $expData['profile']['vertical'] ?? 'unknown'],
                );
            }

            // 2. Agregar métricas por fecha + perfil.
            $aggregated = [];
            foreach ($expired as $row) {
                $data = json_decode($row->session_data, TRUE) ?? [];
                $profile = $data['profile'] ?? [];
                $vertical = $profile['vertical'] ?? 'unknown';
                $date = date('Y-m-d', (int) $row->created);
                $key = "{$date}|{$vertical}|{$row->profile_id}";

                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = [
                        'date' => $date,
                        'vertical' => $vertical,
                        'profile_id' => $row->profile_id,
                        'sessions_started' => 0,
                        'conversions' => 0,
                        'ttfv_values' => [],
                        'funnel_dashboard_view' => 0,
                        'funnel_value_action' => 0,
                    ];
                }

                $aggregated[$key]['sessions_started']++;

                // Calcular TTFV: tiempo desde creación hasta primera acción de valor.
                $actions = $data['actions'] ?? [];
                $valueActions = ['generate_story', 'browse_marketplace', 'view_products'];
                foreach ($actions as $action) {
                    if (in_array($action['action'] ?? '', $valueActions, TRUE)) {
                        $ttfv = ($action['timestamp'] ?? $row->created) - $row->created;
                        $aggregated[$key]['ttfv_values'][] = max(0, $ttfv);
                        break;
                    }
                }

                // Funnel.
                foreach ($actions as $action) {
                    $actionName = $action['action'] ?? '';
                    if ($actionName === 'view_dashboard') {
                        $aggregated[$key]['funnel_dashboard_view']++;
                    }
                    if (in_array($actionName, $valueActions, TRUE)) {
                        $aggregated[$key]['funnel_value_action']++;
                    }
                }
            }

            // 3. UPSERT en demo_analytics.
            foreach ($aggregated as $agg) {
                $ttfvValues = $agg['ttfv_values'];
                sort($ttfvValues);
                $count = count($ttfvValues);
                $ttfvAvg = $count > 0 ? array_sum($ttfvValues) / $count : 0;
                $ttfvP50 = $count > 0 ? $ttfvValues[(int) floor($count * 0.5)] : 0;
                $ttfvP95 = $count > 0 ? $ttfvValues[(int) floor($count * 0.95)] : 0;

                $this->database->merge('demo_analytics')
                    ->keys([
                        'date' => $agg['date'],
                        'vertical' => $agg['vertical'],
                        'profile_id' => $agg['profile_id'],
                    ])
                    ->expressions([
                        'sessions_started' => 'sessions_started + :sessions',
                        'conversions' => 'conversions + :conversions',
                        'funnel_dashboard_view' => 'funnel_dashboard_view + :fdv',
                        'funnel_value_action' => 'funnel_value_action + :fva',
                    ], [
                        ':sessions' => $agg['sessions_started'],
                        ':conversions' => $agg['conversions'],
                        ':fdv' => $agg['funnel_dashboard_view'],
                        ':fva' => $agg['funnel_value_action'],
                    ])
                    ->fields([
                        'date' => $agg['date'],
                        'vertical' => $agg['vertical'],
                        'profile_id' => $agg['profile_id'],
                        'sessions_started' => $agg['sessions_started'],
                        'ttfv_avg_seconds' => $ttfvAvg,
                        'ttfv_p50_seconds' => $ttfvP50,
                        'ttfv_p95_seconds' => $ttfvP95,
                        'conversions' => $agg['conversions'],
                        'funnel_landing' => 0,
                        'funnel_profile_select' => $agg['sessions_started'],
                        'funnel_dashboard_view' => $agg['funnel_dashboard_view'],
                        'funnel_value_action' => $agg['funnel_value_action'],
                        'funnel_conversion_attempt' => 0,
                        'funnel_conversion_success' => 0,
                    ])
                    ->execute();
            }

            // 4. Eliminar sesiones expiradas.
            $deleted = (int) $this->database->delete('demo_sessions')
                ->condition('expires', $now, '<')
                ->execute();

            $logger->info(
                'Demo cleanup: @deleted sessions deleted, @aggregated groups aggregated.',
                ['@deleted' => $deleted, '@aggregated' => count($aggregated)]
            );

            return $deleted;
        }
        catch (\Exception $e) {
            $logger->error(
                'Error cleaning up demo sessions: @error',
                ['@error' => $e->getMessage()]
            );
            return 0;
        }
    }

    /**
     * Dispatches a demo funnel event (público para controllers).
     *
     * S10-03: Permite al controller despachar eventos pre-sesión
     * (LANDING_VIEW, LEAD_CAPTURED, LEAD_SKIPPED) donde no hay sessionId.
     *
     * @param string $eventName
     *   Constante de DemoSessionEvent (e.g., DemoSessionEvent::LANDING_VIEW).
     * @param string $profileId
     *   Perfil demo (puede ser vacío para LANDING_VIEW).
     * @param array<string, mixed> $context
     *   Datos adicionales del evento.
     */
    public function dispatchFunnelEvent(string $eventName, string $profileId = '', array $context = []): void {
        $this->dispatchEvent($eventName, '', $profileId, $context);
    }

    /**
     * Dispatches a demo session event if the dispatcher is available.
     *
     * S7-02: Helper centralizado para despacho de eventos.
     */
    protected function dispatchEvent(string $eventName, string $sessionId, string $profileId, array $context = []): void
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                new DemoSessionEvent($sessionId, $profileId, $context),
                $eventName,
            );
        }
    }

}
