<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;

/**
 * Servicio de demo interactiva para Time-to-Value < 60 segundos.
 *
 * PROPÓSITO:
 * Permite a usuarios anónimos experimentar la plataforma con datos
 * sintéticos sin necesidad de registro, logrando el "Magic Moment"
 * en menos de 60 segundos.
 *
 * Q1 2027 - Gap P0: Instant Value
 */
class DemoInteractiveService
{

    /**
     * Perfiles de demo disponibles.
     */
    public const DEMO_PROFILES = [
        'producer' => [
            'id' => 'producer',
            'name' => 'Productor de Aceite',
            'description' => 'Experimenta cómo sería gestionar tu cooperativa de aceite de oliva',
            'icon' => '🫒',
            'vertical' => 'AgroConecta',
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
            'icon' => '🍷',
            'vertical' => 'AgroConecta',
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
            'icon' => '🧀',
            'vertical' => 'AgroConecta',
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
            'icon' => '🛒',
            'vertical' => 'General',
            'demo_data' => [
                'products_available' => 150,
                'tenants_active' => 23,
                'categories' => 12,
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
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/olive-oil-1.jpg',
                'rating' => 4.8,
                'reviews' => 34,
            ],
            [
                'name' => 'Aceite de Oliva Picual',
                'price' => 12.50,
                'stock' => 85,
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/olive-oil-2.jpg',
                'rating' => 4.6,
                'reviews' => 28,
            ],
            [
                'name' => 'Aceite Ecológico Arbequina',
                'price' => 18.90,
                'stock' => 45,
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/olive-oil-3.jpg',
                'rating' => 4.9,
                'reviews' => 52,
            ],
        ],
        'winery' => [
            [
                'name' => 'Tinto Reserva 2020',
                'price' => 24.90,
                'stock' => 180,
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/wine-red-1.jpg',
                'rating' => 4.7,
                'reviews' => 89,
            ],
            [
                'name' => 'Blanco Verdejo Crianza',
                'price' => 14.50,
                'stock' => 220,
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/wine-white-1.jpg',
                'rating' => 4.5,
                'reviews' => 67,
            ],
        ],
        'cheese' => [
            [
                'name' => 'Queso Manchego Curado',
                'price' => 22.00,
                'stock' => 35,
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/cheese-1.jpg',
                'rating' => 4.9,
                'reviews' => 78,
            ],
            [
                'name' => 'Queso de Cabra Semicurado',
                'price' => 16.50,
                'stock' => 42,
                'image' => '/modules/custom/ecosistema_jaraba_core/images/demo/cheese-2.jpg',
                'rating' => 4.7,
                'reviews' => 45,
            ],
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerChannelFactoryInterface $loggerFactory,
        protected StateInterface $state,
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
     *
     * @return array
     *   Datos generados para la demo.
     */
    public function generateDemoSession(string $profileId, string $sessionId): array
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

        // Obtener productos sintéticos.
        $products = self::SYNTHETIC_PRODUCTS[$profileId] ?? [];

        // Guardar sesión.
        $sessionData = [
            'session_id' => $sessionId,
            'profile_id' => $profileId,
            'profile' => $profile,
            'tenant_name' => $randomName,
            'metrics' => $metrics,
            'products' => $products,
            'sales_history' => $salesHistory,
            'created' => time(),
            'expires' => time() + 3600, // 1 hora de duración.
            'magic_moment_actions' => $this->getMagicMomentActions($profileId),
        ];

        $this->saveDemoSession($sessionId, $sessionData);

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
     */
    protected function getMagicMomentActions(string $profileId): array
    {
        $actions = [
            'producer' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Visualiza métricas en tiempo real',
                    'icon' => '📊',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia',
                    'description' => 'La IA cuenta tu historia',
                    'icon' => '✨',
                    'url' => '/demo/ai/storytelling',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_products',
                    'label' => 'Ver Productos',
                    'description' => 'Explora el catálogo de ejemplo',
                    'icon' => '🛠️',
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
                    'icon' => '📊',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia',
                    'description' => 'La IA cuenta tu historia',
                    'icon' => '✨',
                    'url' => '/demo/ai/storytelling',
                    'highlight' => TRUE,
                ],
            ],
            'cheese' => [
                [
                    'id' => 'view_dashboard',
                    'label' => 'Ver tu Dashboard',
                    'description' => 'Métricas de tu quesería',
                    'icon' => '📊',
                    'url' => '#metrics',
                    'highlight' => TRUE,
                    'scroll_target' => TRUE,
                ],
                [
                    'id' => 'generate_story',
                    'label' => 'Generar Historia',
                    'description' => 'La IA cuenta tu historia',
                    'icon' => '✨',
                    'url' => '/demo/ai/storytelling',
                    'highlight' => TRUE,
                ],
            ],
            'buyer' => [
                [
                    'id' => 'browse_marketplace',
                    'label' => 'Explorar Marketplace',
                    'description' => 'Descubre productos locales',
                    'icon' => '🔍',
                    'url' => '/marketplace',
                    'highlight' => TRUE,
                ],
                [
                    'id' => 'view_categories',
                    'label' => 'Ver Categorías',
                    'description' => 'Filtra por tipo de producto',
                    'icon' => '🎯',
                    'url' => '/marketplace/search',
                    'highlight' => FALSE,
                ],
            ],
        ];

        return $actions[$profileId] ?? [];
    }

    /**
     * Guarda una sesión de demo.
     */
    protected function saveDemoSession(string $sessionId, array $data): void
    {
        $sessions = $this->state->get('demo_sessions', []);
        $sessions[$sessionId] = $data;

        // Limpiar sesiones expiradas.
        $now = time();
        $sessions = array_filter($sessions, fn($s) => ($s['expires'] ?? 0) > $now);

        $this->state->set('demo_sessions', $sessions);
    }

    /**
     * Obtiene una sesión de demo.
     */
    public function getDemoSession(string $sessionId): ?array
    {
        $sessions = $this->state->get('demo_sessions', []);
        $session = $sessions[$sessionId] ?? NULL;

        if ($session && ($session['expires'] ?? 0) > time()) {
            return $session;
        }

        return NULL;
    }

    /**
     * Registra una acción del usuario en la demo.
     */
    public function trackDemoAction(string $sessionId, string $action, array $metadata = []): void
    {
        $sessions = $this->state->get('demo_sessions', []);

        if (isset($sessions[$sessionId])) {
            $sessions[$sessionId]['actions'][] = [
                'action' => $action,
                'timestamp' => time(),
                'metadata' => $metadata,
            ];
            $this->state->set('demo_sessions', $sessions);

            // Log para analytics.
            $this->loggerFactory->get('demo_interactive')->info(
                'Demo action: @action in session @session',
                ['@action' => $action, '@session' => $sessionId]
            );
        }
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
            if (in_array($action['action'], $valueActions)) {
                return $action['timestamp'] - $created;
            }
        }

        return NULL;
    }

    /**
     * Convierte una sesión de demo a registro real.
     */
    public function convertToRealAccount(string $sessionId, string $email): array
    {
        $session = $this->getDemoSession($sessionId);

        if (!$session) {
            return ['success' => FALSE, 'error' => 'Sesión no válida'];
        }

        // Aquí se integraría con el flujo de onboarding real.
        // Por ahora, retornamos datos para el formulario de registro.
        return [
            'success' => TRUE,
            'prefill' => [
                'business_name' => $session['tenant_name'],
                'vertical' => $session['profile']['vertical'],
                'profile_type' => $session['profile_id'],
            ],
            'message' => '¡Tu demo se puede convertir en cuenta real!',
        ];
    }

}
