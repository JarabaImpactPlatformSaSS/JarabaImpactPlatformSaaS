<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\AgroAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Panel de administración unificado para AgroConecta.
 *
 * ENDPOINTS:
 * GET  /api/v1/agro/admin/overview         → Resumen ejecutivo global
 * GET  /api/v1/agro/admin/health           → Health-check del marketplace
 * GET  /api/v1/agro/admin/activity         → Actividad reciente
 * GET  /api/v1/agro/admin/reports/products  → Reporte de productos
 * GET  /api/v1/agro/admin/reports/orders    → Reporte de pedidos
 * GET  /api/v1/agro/admin/reports/producers → Reporte de productores
 */
class AgroAdminController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected AgroAnalyticsService $analyticsService,
    ) {
    }

    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.analytics_service'), // AUDIT-CONS-N05: canonical prefix
        );
    }

    /**
     * Resumen ejecutivo global del marketplace.
     * Combina KPIs, conteos de entidades y estado general.
     */
    public function overview(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);

        // Conteos de entidades principales.
        $counts = $this->getEntityCounts();

        // KPIs rápidos (últimos 7 días).
        $dashboard = $this->analyticsService->getDashboardData($tenantId, '7d');

        // Alertas activas.
        $alerts = $this->analyticsService->getActiveAlerts($tenantId);

        return new JsonResponse([
            'marketplace' => [
                'products' => $counts['products'],
                'producers' => $counts['producers'],
                'orders' => $counts['orders'],
                'users' => $counts['users'],
                'reviews' => $counts['reviews'],
                'categories' => $counts['categories'],
                'promotions' => $counts['promotions'],
                'batches' => $counts['batches'],
                'qr_codes' => $counts['qr_codes'],
            ],
            'kpis' => $dashboard['kpis'] ?? [],
            'alerts' => [
                'active' => count($alerts),
                'critical' => count(array_filter($alerts, fn($a) => ($a['severity'] ?? '') === 'critical')),
                'items' => array_slice($alerts, 0, 5),
            ],
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Health-check: estado de componentes del marketplace.
     */
    public function health(): JsonResponse
    {
        $checks = [];

        // Verificar que las entidades principales existen y son accesibles.
        $entityTypes = [
            'product_agro' => 'Productos',
            'producer_profile' => 'Productores',
            'order_agro' => 'Pedidos',
            'agro_batch' => 'Lotes',
            'qr_code_agro' => 'QR Codes',
            'analytics_daily_agro' => 'Analytics',
            'alert_rule_agro' => 'Alertas',
        ];

        foreach ($entityTypes as $type => $label) {
            try {
                $storage = $this->entityTypeManager()->getStorage($type);
                $count = (int) $storage->getQuery()->accessCheck(FALSE)->count()->execute();
                $checks[$type] = [
                    'label' => $label,
                    'status' => 'ok',
                    'count' => $count,
                ];
            } catch (\Exception $e) {
                \Drupal::logger('jaraba_agroconecta_core')->error('Health check failed for @type: @msg', ['@type' => $type, '@msg' => $e->getMessage()]);
                $checks[$type] = [
                    'label' => $label,
                    'status' => 'error',
                    'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
                ];
            }
        }

        $allOk = !array_filter($checks, fn($c) => $c['status'] !== 'ok');

        return new JsonResponse([
            'status' => $allOk ? 'healthy' : 'degraded',
            'components' => $checks,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Actividad reciente del marketplace.
     */
    public function activity(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 20), 50);
        $activities = [];

        // Pedidos recientes.
        try {
            $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
            $orderIds = $orderStorage->getQuery()
                ->accessCheck(FALSE)
                ->sort('created', 'DESC')
                ->range(0, $limit)
                ->execute();

            $orders = $orderStorage->loadMultiple($orderIds);
            foreach ($orders as $order) {
                $activities[] = [
                    'type' => 'order',
                    'id' => (int) $order->id(),
                    'label' => 'Pedido #' . $order->id(),
                    'status' => $order->get('status')->value ?? 'unknown',
                    'total' => (float) ($order->get('total')->value ?? 0),
                    'timestamp' => (int) ($order->get('created')->value ?? 0),
                ];
            }
        } catch (\Exception $e) {
            // Entity may not exist yet.
        }

        // Productos recientes.
        try {
            $productStorage = $this->entityTypeManager()->getStorage('product_agro');
            $productIds = $productStorage->getQuery()
                ->accessCheck(FALSE)
                ->sort('created', 'DESC')
                ->range(0, 5)
                ->execute();

            $products = $productStorage->loadMultiple($productIds);
            foreach ($products as $product) {
                $activities[] = [
                    'type' => 'product',
                    'id' => (int) $product->id(),
                    'label' => $product->label() ?? 'Producto #' . $product->id(),
                    'timestamp' => (int) ($product->get('created')->value ?? 0),
                ];
            }
        } catch (\Exception $e) {
            // Entity may not exist yet.
        }

        // Reseñas recientes.
        try {
            $reviewStorage = $this->entityTypeManager()->getStorage('review_agro');
            $reviewIds = $reviewStorage->getQuery()
                ->accessCheck(FALSE)
                ->sort('created', 'DESC')
                ->range(0, 5)
                ->execute();

            $reviews = $reviewStorage->loadMultiple($reviewIds);
            foreach ($reviews as $review) {
                $activities[] = [
                    'type' => 'review',
                    'id' => (int) $review->id(),
                    'label' => 'Reseña #' . $review->id(),
                    'rating' => (int) ($review->get('rating')->value ?? 0),
                    'timestamp' => (int) ($review->get('created')->value ?? 0),
                ];
            }
        } catch (\Exception $e) {
            // Entity may not exist yet.
        }

        // Ordenar por timestamp descendente.
        usort($activities, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
        $activities = array_slice($activities, 0, $limit);

        return new JsonResponse([
            'activities' => $activities,
            'total' => count($activities),
        ]);
    }

    /**
     * Reporte de productos.
     */
    public function reportProducts(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $limit = min((int) $request->query->get('limit', 50), 200);

        $products = $this->analyticsService->getTopProducts($tenantId, $limit);

        return new JsonResponse([
            'report' => 'products',
            'data' => $products,
            'total' => count($products),
            'generated_at' => date('c'),
        ]);
    }

    /**
     * Reporte de pedidos.
     */
    public function reportOrders(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);

        try {
            $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
            $totalOrders = (int) $orderStorage->getQuery()->accessCheck(FALSE)->count()->execute();

            // Desglose por estado.
            $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
            $byStatus = [];
            foreach ($statuses as $status) {
                $byStatus[$status] = (int) $orderStorage->getQuery()
                    ->condition('status', $status)
                    ->accessCheck(FALSE)
                    ->count()
                    ->execute();
            }

            return new JsonResponse([
                'report' => 'orders',
                'total' => $totalOrders,
                'by_status' => $byStatus,
                'generated_at' => date('c'),
            ]);
        } catch (\Exception $e) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Entity not available']], 500);
        }
    }

    /**
     * Reporte de productores.
     */
    public function reportProducers(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query->get('tenant_id', 1);
        $limit = min((int) $request->query->get('limit', 50), 200);

        $producers = $this->analyticsService->getTopProducers($tenantId, $limit);

        return new JsonResponse([
            'report' => 'producers',
            'data' => $producers,
            'total' => count($producers),
            'generated_at' => date('c'),
        ]);
    }

    // ===================================================
    // Métodos internos
    // ===================================================

    protected function getEntityCounts(): array
    {
        $entities = [
            'products' => 'product_agro',
            'producers' => 'producer_profile',
            'orders' => 'order_agro',
            'users' => 'user',
            'reviews' => 'review_agro',
            'categories' => 'agro_category',
            'promotions' => 'promotion_agro',
            'batches' => 'agro_batch',
            'qr_codes' => 'qr_code_agro',
        ];

        $counts = [];
        foreach ($entities as $key => $type) {
            try {
                $counts[$key] = (int) $this->entityTypeManager()
                    ->getStorage($type)
                    ->getQuery()
                    ->accessCheck(FALSE)
                    ->count()
                    ->execute();
            } catch (\Exception $e) {
                $counts[$key] = 0;
            }
        }

        return $counts;
    }
}
