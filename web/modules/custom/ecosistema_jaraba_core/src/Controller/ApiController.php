<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API REST Controller para Jaraba Impact Platform.
 *
 * Proporciona endpoints REST versionados para integración externa.
 * Documentación: /api/docs
 */
class ApiController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        return new static();
    }

    /**
     * Swagger UI documentation page.
     */
    public function docs(): array
    {
        return [
            '#theme' => 'api_docs',
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/swagger-ui',
                ],
            ],
        ];
    }

    /**
     * Serve OpenAPI spec.
     */
    public function openApiSpec(): Response
    {
        $modulePath = \Drupal::service('extension.list.module')->getPath('ecosistema_jaraba_core');
        $specPath = $modulePath . '/openapi/openapi.yaml';

        if (!file_exists($specPath)) {
            return new JsonResponse(['error' => 'OpenAPI spec not found'], 404);
        }

        $content = file_get_contents($specPath);

        return new Response($content, 200, [
            'Content-Type' => 'application/x-yaml',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * GET /api/v1/tenants - Listar tenants del usuario.
     */
    public function listTenants(Request $request): JsonResponse
    {
        $tenantId = $this->getTenantFromApiKey($request);

        if (!$tenantId) {
            return $this->unauthorizedResponse();
        }

        try {
            $tenant = $this->entityTypeManager()->getStorage('tenant')->load($tenantId);

            if (!$tenant) {
                return $this->notFoundResponse('Tenant');
            }

            $data = [
                'data' => [
                    $this->formatTenant($tenant),
                ],
                'meta' => [
                    'total' => 1,
                    'limit' => 20,
                    'offset' => 0,
                ],
            ];

            return new JsonResponse($data);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /api/v1/tenants/{tenantId} - Obtener tenant específico.
     */
    public function getTenant(Request $request, int $tenantId): JsonResponse
    {
        $authenticatedTenantId = $this->getTenantFromApiKey($request);

        if (!$authenticatedTenantId) {
            return $this->unauthorizedResponse();
        }

        // Solo puede ver su propio tenant
        if ($authenticatedTenantId != $tenantId) {
            return new JsonResponse([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'No tienes acceso a este recurso',
                ],
            ], 403);
        }

        try {
            $tenant = $this->entityTypeManager()->getStorage('tenant')->load($tenantId);

            if (!$tenant) {
                return $this->notFoundResponse('Tenant');
            }

            return new JsonResponse($this->formatTenant($tenant));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /api/v1/tenants/{tenantId}/usage - Métricas de uso.
     */
    public function getTenantUsage(Request $request, int $tenantId): JsonResponse
    {
        $authenticatedTenantId = $this->getTenantFromApiKey($request);

        if (!$authenticatedTenantId || $authenticatedTenantId != $tenantId) {
            return $this->unauthorizedResponse();
        }

        try {
            $tenant = $this->entityTypeManager()->getStorage('tenant')->load($tenantId);

            if (!$tenant) {
                return $this->notFoundResponse('Tenant');
            }

            $plan = $tenant->getSubscriptionPlan();
            $limits = $plan ? $plan->getLimits() : [];

            // TODO: Obtener métricas reales del TenantMeteringService
            $usage = [
                'tenant_id' => (int) $tenantId,
                'period' => date('Y-m'),
                'producers_count' => 0,
                'products_count' => 0,
                'storage_mb' => 0,
                'ai_queries' => 0,
                'api_requests' => 0,
                'limits' => $limits,
            ];

            return new JsonResponse($usage);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /api/v1/plans - Listar planes disponibles.
     */
    public function listPlans(Request $request): JsonResponse
    {
        try {
            $planStorage = $this->entityTypeManager()->getStorage('saas_plan');
            $query = $planStorage->getQuery()->accessCheck(FALSE);

            // Filtro por vertical
            $vertical = $request->query->get('vertical');
            if ($vertical) {
                $query->condition('vertical.entity.machine_name', $vertical);
            }

            $planIds = $query->execute();
            $plans = $planStorage->loadMultiple($planIds);

            $data = [];
            foreach ($plans as $plan) {
                $data[] = [
                    'id' => (int) $plan->id(),
                    'name' => $plan->getName(),
                    'price_monthly' => (float) $plan->getPriceMonthly(),
                    'price_yearly' => (float) $plan->getPriceYearly(),
                    'features' => $plan->getFeatures(),
                    'limits' => $plan->getLimits(),
                ];
            }

            return new JsonResponse(['data' => $data]);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /api/v1/marketplace/products - Listar productos.
     */
    public function listMarketplaceProducts(Request $request): JsonResponse
    {
        try {
            $limit = min((int) $request->query->get('limit', 20), 100);
            $offset = (int) $request->query->get('offset', 0);
            $category = $request->query->get('category');
            $search = $request->query->get('q');

            // Usar el MarketplaceController existente para obtener productos
            /** @var \Drupal\ecosistema_jaraba_core\Controller\MarketplaceController $marketplaceController */
            $marketplaceController = \Drupal::classResolver()->getInstanceFromDefinition(
                '\Drupal\ecosistema_jaraba_core\Controller\MarketplaceController'
            );

            // Por ahora, retornar productos demo
            $products = $this->getDemoProducts($category, $search, $limit);

            return new JsonResponse([
                'data' => $products,
                'meta' => [
                    'total' => count($products),
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /api/v1/marketplace/categories - Listar categorías.
     */
    public function listMarketplaceCategories(): JsonResponse
    {
        $categories = [
            ['slug' => 'alimentacion', 'name' => 'Alimentación', 'icon' => '🥖', 'count' => 45],
            ['slug' => 'vinos', 'name' => 'Vinos y Bebidas', 'icon' => '🍷', 'count' => 28],
            ['slug' => 'artesania', 'name' => 'Artesanía', 'icon' => '🎨', 'count' => 15],
            ['slug' => 'cosmetica', 'name' => 'Cosmética Natural', 'icon' => '🌿', 'count' => 12],
        ];

        return new JsonResponse(['data' => $categories]);
    }

    /**
     * Formatea un tenant para JSON.
     */
    protected function formatTenant($tenant): array
    {
        $plan = $tenant->getSubscriptionPlan();

        return [
            'id' => (int) $tenant->id(),
            'name' => $tenant->getName(),
            'domain' => $tenant->getDomain(),
            'vertical' => $tenant->getVertical() ? $tenant->getVertical()->get('machine_name')->value : NULL,
            'subscription_status' => $tenant->getSubscriptionStatus(),
            'plan' => $plan ? [
                'id' => (int) $plan->id(),
                'name' => $plan->getName(),
            ] : NULL,
            'created_at' => date('c', $tenant->get('created')->value),
            'trial_ends_at' => $tenant->getTrialEndsAt() ? $tenant->getTrialEndsAt()->format('c') : NULL,
        ];
    }

    /**
     * Obtiene el tenant ID desde la API Key.
     */
    protected function getTenantFromApiKey(Request $request): ?int
    {
        $apiKey = $request->headers->get('X-API-Key');

        if (!$apiKey) {
            return NULL;
        }

        // Buscar tenant por API key
        try {
            $tenantStorage = $this->entityTypeManager()->getStorage('tenant');
            $tenants = $tenantStorage->loadByProperties(['api_key' => $apiKey]);

            if (!empty($tenants)) {
                $tenant = reset($tenants);
                return (int) $tenant->id();
            }

            // Fallback: Para desarrollo, aceptar tenant ID directamente
            if (preg_match('/^dev-tenant-(\d+)$/', $apiKey, $matches)) {
                return (int) $matches[1];
            }

        } catch (\Exception $e) {
            // Error buscando tenant
        }

        return NULL;
    }

    /**
     * Productos demo para la API.
     */
    protected function getDemoProducts(?string $category, ?string $search, int $limit): array
    {
        $products = [
            [
                'id' => 1,
                'title' => 'Aceite de Oliva Virgen Extra',
                'price' => '€15.90',
                'image' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400',
                'tenant' => 'Finca Olivares',
                'category' => 'Alimentación',
                'url' => '/marketplace/product/1',
            ],
            [
                'id' => 2,
                'title' => 'Queso Manchego Curado',
                'price' => '€24.50',
                'image' => 'https://images.unsplash.com/photo-1452195100486-9cc805987862?w=400',
                'tenant' => 'Quesería Manchega',
                'category' => 'Alimentación',
                'url' => '/marketplace/product/2',
            ],
            [
                'id' => 3,
                'title' => 'Vino Tinto Reserva 2019',
                'price' => '€18.00',
                'image' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=400',
                'tenant' => 'Bodega Sierra Nevada',
                'category' => 'Vinos y Bebidas',
                'url' => '/marketplace/product/3',
            ],
            [
                'id' => 4,
                'title' => 'Miel de Romero Ecológica',
                'price' => '€12.00',
                'image' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=400',
                'tenant' => 'Apiario del Valle',
                'category' => 'Alimentación',
                'url' => '/marketplace/product/4',
            ],
        ];

        // Filtrar por categoría
        if ($category) {
            $products = array_filter($products, function ($p) use ($category) {
                return strtolower($p['category']) === strtolower($category) ||
                    strpos(strtolower($p['category']), strtolower($category)) !== FALSE;
            });
        }

        // Filtrar por búsqueda
        if ($search) {
            $products = array_filter($products, function ($p) use ($search) {
                return stripos($p['title'], $search) !== FALSE ||
                    stripos($p['tenant'], $search) !== FALSE;
            });
        }

        return array_values(array_slice($products, 0, $limit));
    }

    /**
     * Respuesta de error no autorizado.
     */
    protected function unauthorizedResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'API Key inválida o no proporcionada',
            ],
        ], 401);
    }

    /**
     * Respuesta de recurso no encontrado.
     */
    protected function notFoundResponse(string $resource): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => "$resource no encontrado",
            ],
        ], 404);
    }

    /**
     * Respuesta de error genérico.
     */
    protected function errorResponse(string $message): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'message' => $message,
            ],
        ], 500);
    }

}
