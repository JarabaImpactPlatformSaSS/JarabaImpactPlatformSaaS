<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Servicio de tours guiados contextuales.
 *
 * PROPÓSITO:
 * Proporciona tours interactivos adaptados al tipo de usuario
 * y su progreso en el onboarding.
 *
 * Q2 2026 - Sprint 5-6: Predictive Onboarding
 */
class GuidedTourService
{

    use StringTranslationTrait;

    /**
     * Tours disponibles.
     */
    protected const TOURS = [
        'seller_welcome' => [
            'id' => 'seller_welcome',
            'name' => 'Bienvenida para Vendedores',
            'target_intent' => 'seller',
            'steps' => [
                [
                    'target' => '.tenant-dashboard',
                    'title' => '¡Bienvenido a tu Dashboard!',
                    'content' => 'Aquí puedes ver todas tus métricas de un vistazo.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.tenant-metrics-grid',
                    'title' => 'Tus Métricas',
                    'content' => 'Ventas, clientes y MRR actualizados en tiempo real.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.tenant-quick-links',
                    'title' => 'Acciones Rápidas',
                    'content' => 'Accede a las funciones más usadas con un clic.',
                    'position' => 'top',
                ],
            ],
        ],
        'first_product' => [
            'id' => 'first_product',
            'name' => 'Añadir tu Primer Producto',
            'target_intent' => 'seller',
            'steps' => [
                [
                    'target' => '#add-product-btn',
                    'title' => 'Crea tu Primer Producto',
                    'content' => 'Haz clic aquí para añadir tu primer producto al marketplace.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '#product-title',
                    'title' => 'Título del Producto',
                    'content' => 'Un buen título ayuda a que te encuentren más fácil.',
                    'position' => 'right',
                ],
                [
                    'target' => '#product-price',
                    'title' => 'Precio',
                    'content' => 'Establece un precio competitivo para tu mercado.',
                    'position' => 'right',
                ],
            ],
        ],
        'buyer_marketplace' => [
            'id' => 'buyer_marketplace',
            'name' => 'Explora el Marketplace',
            'target_intent' => 'buyer',
            'steps' => [
                [
                    'target' => '.marketplace-search-form',
                    'title' => 'Busca Productos',
                    'content' => 'Encuentra productos de productores locales.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.marketplace-categories',
                    'title' => 'Categorías',
                    'content' => 'Explora por categorías para descubrir nuevos productos.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.marketplace-tenants',
                    'title' => 'Tiendas',
                    'content' => 'Conoce a los productores del ecosistema.',
                    'position' => 'top',
                ],
            ],
        ],
        'stripe_connect' => [
            'id' => 'stripe_connect',
            'name' => 'Conectar Stripe',
            'target_intent' => 'seller',
            'steps' => [
                [
                    'target' => '#stripe-connect-btn',
                    'title' => 'Conecta tu cuenta Stripe',
                    'content' => 'Necesitas una cuenta Stripe para recibir pagos.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '.stripe-onboarding-info',
                    'title' => 'Verificación',
                    'content' => 'Stripe verificará tus datos para proteger a todos.',
                    'position' => 'bottom',
                ],
            ],
        ],
        // S3-06: Tour para la demo interactiva con data-tour-step selectors.
        'demo_welcome' => [
            'id' => 'demo_welcome',
            'name' => 'Bienvenida a la Demo',
            'target_intent' => 'demo',
            'steps' => [
                [
                    'target' => '[data-tour-step="demo-metrics"]',
                    'title' => 'Tus Métricas',
                    'content' => 'Aquí puedes ver las métricas clave de tu negocio de un vistazo.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '[data-tour-step="demo-magic-moment"]',
                    'title' => 'Tu Primer Paso',
                    'content' => 'Estas acciones rápidas te ayudan a descubrir el valor de la plataforma.',
                    'position' => 'bottom',
                ],
                [
                    'target' => '[data-tour-step="demo-chart"]',
                    'title' => 'Tendencia de Ventas',
                    'content' => 'Visualiza la evolución de tus ingresos con gráficos interactivos.',
                    'position' => 'top',
                ],
                [
                    'target' => '[data-tour-step="demo-convert"]',
                    'title' => '¿Te gusta lo que ves?',
                    'content' => 'Convierte esta demo en tu cuenta real y accede a todas las funcionalidades.',
                    'position' => 'top',
                ],
            ],
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Obtiene el tour apropiado para el usuario actual.
     */
    public function getRecommendedTour(string $userIntent, array $completedEvents = []): ?array
    {
        // Determinar qué tour mostrar basándose en intención y progreso.
        if ($userIntent === 'seller') {
            if (!in_array('seller_welcome', $this->getCompletedTours())) {
                return self::TOURS['seller_welcome'];
            }
            if (!in_array('first_product', $completedEvents)) {
                return self::TOURS['first_product'];
            }
            if (!in_array('payment_connected', $completedEvents)) {
                return self::TOURS['stripe_connect'];
            }
        } elseif ($userIntent === 'buyer') {
            if (!in_array('buyer_marketplace', $this->getCompletedTours())) {
                return self::TOURS['buyer_marketplace'];
            }
        }

        return NULL;
    }

    /**
     * Obtiene todos los tours disponibles.
     *
     * S6-02: Traduce títulos y contenido de pasos.
     */
    public function getAllTours(): array
    {
        return array_map([$this, 'translateTour'], self::TOURS);
    }

    /**
     * Obtiene un tour específico.
     *
     * S6-02: Traduce títulos y contenido de pasos.
     */
    public function getTour(string $tourId): ?array
    {
        $tour = self::TOURS[$tourId] ?? NULL;
        return $tour ? $this->translateTour($tour) : NULL;
    }

    /**
     * Traduce las cadenas de un tour.
     */
    protected function translateTour(array $tour): array
    {
        $tour['name'] = (string) $this->t($tour['name']);
        foreach ($tour['steps'] as &$step) {
            $step['title'] = (string) $this->t($step['title']);
            $step['content'] = (string) $this->t($step['content']);
        }
        return $tour;
    }

    /**
     * Marca un tour como completado.
     */
    public function completeTour(string $tourId): void
    {
        $userId = $this->currentUser->id();

        // S5-14: Guard — no persistir tours para usuarios anónimos (uid=0).
        if ($userId <= 0) {
            return;
        }

        $this->database->merge('user_completed_tours')
            ->keys([
                    'user_id' => $userId,
                    'tour_id' => $tourId,
                ])
            ->fields([
                    'completed_at' => time(),
                ])
            ->execute();
    }

    /**
     * Obtiene tours completados por el usuario.
     */
    public function getCompletedTours(): array
    {
        $userId = $this->currentUser->id();

        if ($userId <= 0) {
            return [];
        }

        $results = $this->database->select('user_completed_tours', 'uct')
            ->fields('uct', ['tour_id'])
            ->condition('user_id', $userId)
            ->execute()
            ->fetchCol();

        return $results ?: [];
    }

    /**
     * Obtiene el progreso del usuario en los tours.
     */
    public function getTourProgress(): array
    {
        $completed = $this->getCompletedTours();
        $total = count(self::TOURS);
        $completedCount = count($completed);

        return [
            'completed' => $completed,
            'total_tours' => $total,
            'completed_count' => $completedCount,
            'progress_percent' => $total > 0 ? round(($completedCount / $total) * 100) : 0,
        ];
    }

    /**
     * Genera el JavaScript necesario para mostrar un tour.
     */
    public function getTourDriverJS(array $tour): string
    {
        $steps = array_map(function ($step) {
            return [
                'element' => $step['target'],
                'popover' => [
                    'title' => $step['title'],
                    'description' => $step['content'],
                    'position' => $step['position'],
                ],
            ];
        }, $tour['steps']);

        return json_encode([
            'tourId' => $tour['id'],
            'steps' => $steps,
            'showProgress' => TRUE,
            'showButtons' => ['next', 'previous', 'close'],
        ]);
    }

}
