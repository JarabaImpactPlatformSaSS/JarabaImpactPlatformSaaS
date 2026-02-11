<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Marketplace central.
 *
 * PROPÓSITO:
 * Proporciona una landing page centralizada con productos de todos los tenants,
 * permitiendo a los visitantes descubrir ofertas del ecosistema completo.
 *
 * FASE 13 ROADMAP:
 * - Cross-tenant product visibility
 * - Category navigation
 * - Search functionality
 * - Tenant attribution
 */
class MarketplaceController extends ControllerBase
{

    /**
     * Database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('database')
        );
    }

    /**
     * Landing page principal del Marketplace.
     *
     * @return array
     *   Render array con el marketplace.
     */
    public function landing(): array
    {
        // Obtener productos destacados de todos los tenants.
        $featuredProducts = $this->getFeaturedProducts(8);

        // Obtener categorías con conteo.
        $categories = $this->getCategories();

        // Obtener tenants activos (tiendas).
        $activeTenants = $this->getActiveTenants(6);

        // Estadísticas del marketplace.
        $stats = $this->getMarketplaceStats();

        $build = [
            '#theme' => 'marketplace_landing',
            '#featured_products' => $featuredProducts,
            '#categories' => $categories,
            '#active_tenants' => $activeTenants,
            '#stats' => $stats,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/marketplace',
                ],
            ],
            '#cache' => [
                'max-age' => 300, // 5 minutos.
            ],
        ];

        return $build;
    }

    /**
     * Obtiene productos destacados de todos los tenants.
     */
    protected function getFeaturedProducts(int $limit = 8): array
    {
        $products = [];

        try {
            // Intentar obtener de commerce_product si existe.
            if ($this->entityTypeManager->hasDefinition('commerce_product')) {
                $storage = $this->entityTypeManager->getStorage('commerce_product');
                $query = $storage->getQuery()
                    ->accessCheck(TRUE)
                    ->condition('status', 1)
                    ->sort('created', 'DESC')
                    ->range(0, $limit);

                $ids = $query->execute();
                $entities = $storage->loadMultiple($ids);

                foreach ($entities as $product) {
                    $products[] = $this->formatProduct($product);
                }
            }
        } catch (\Exception $e) {
            // Fallback: demo products.
        }

        // Si no hay productos reales, usar demo.
        if (empty($products)) {
            $products = $this->getDemoProducts($limit);
        }

        return $products;
    }

    /**
     * Formatea un producto para display.
     */
    protected function formatProduct($product): array
    {
        // Mapeo de keywords a imágenes temáticas de Unsplash (UX Premium).
        $productImages = [
            'aceite' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&h=300&fit=crop',
            'oliva' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&h=300&fit=crop',
            'queso' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?w=400&h=300&fit=crop',
            'vino' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=400&h=300&fit=crop',
            'miel' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=400&h=300&fit=crop',
            'jamón' => 'https://images.unsplash.com/photo-1600891964599-f61ba0e24092?w=400&h=300&fit=crop',
            'jamon' => 'https://images.unsplash.com/photo-1600891964599-f61ba0e24092?w=400&h=300&fit=crop',
            'azafrán' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400&h=300&fit=crop',
            'azafran' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400&h=300&fit=crop',
            'almendra' => 'https://images.unsplash.com/photo-1508061253366-f7da158b6d46?w=400&h=300&fit=crop',
            'chorizo' => 'https://images.unsplash.com/photo-1626200419199-391ae4be7a41?w=400&h=300&fit=crop',
            'embutido' => 'https://images.unsplash.com/photo-1626200419199-391ae4be7a41?w=400&h=300&fit=crop',
            'pan' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=400&h=300&fit=crop',
            'cerveza' => 'https://images.unsplash.com/photo-1535958636474-b021ee887b13?w=400&h=300&fit=crop',
            'fruta' => 'https://images.unsplash.com/photo-1619566636858-adf3ef46400b?w=400&h=300&fit=crop',
            'verdura' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=400&h=300&fit=crop',
            'carne' => 'https://images.unsplash.com/photo-1603048297172-c92544798d5a?w=400&h=300&fit=crop',
            'pescado' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=400&h=300&fit=crop',
            'rag' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&h=300&fit=crop',
            'test' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&h=300&fit=crop',
        ];

        // Imagen por defecto temática de productos gourmet.
        $defaultImage = 'https://images.unsplash.com/photo-1606923829579-0cb981a83e2e?w=400&h=300&fit=crop';

        // Buscar imagen basada en el título del producto.
        $title = strtolower($product->label() ?? '');
        $imageUrl = $defaultImage;

        foreach ($productImages as $keyword => $image) {
            if (strpos($title, $keyword) !== FALSE) {
                $imageUrl = $image;
                break;
            }
        }

        // Intentar obtener imagen real del producto si existe.
        if ($product->hasField('field_image') && !$product->get('field_image')->isEmpty()) {
            $image = $product->get('field_image')->entity;
            if ($image) {
                $imageUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($image->getFileUri());
            }
        }

        // Obtener precio.
        $price = '€0.00';
        if ($product->hasField('variations') && !$product->get('variations')->isEmpty()) {
            $variation = $product->get('variations')->entity;
            if ($variation && $variation->hasField('price')) {
                $priceValue = $variation->get('price')->first();
                if ($priceValue) {
                    $price = '€' . number_format((float) $priceValue->number, 2, ',', '.');
                }
            }
        }

        // Obtener tenant.
        $tenantName = 'Tienda Jaraba';
        if ($product->hasField('field_tenant') && !$product->get('field_tenant')->isEmpty()) {
            $tenantName = $product->get('field_tenant')->entity?->label() ?? $tenantName;
        }

        // Obtener categoría.
        $category = 'General';
        if ($product->hasField('field_category') && !$product->get('field_category')->isEmpty()) {
            $category = $product->get('field_category')->entity?->label() ?? 'General';
        }

        return [
            'id' => $product->id(),
            'title' => $product->label(),
            'image' => $imageUrl,
            'price' => $price,
            'tenant' => $tenantName,
            'category' => $category,
            'url' => $product->toUrl()->toString(),
        ];
    }

    /**
     * Genera productos demo para visualización.
     */
    protected function getDemoProducts(int $limit): array
    {
        // Productos demo con imágenes temáticas de Unsplash (UX Premium).
        $demoProducts = [
            [
                'title' => 'Aceite de Oliva Virgen Extra',
                'price' => '€15.90',
                'tenant' => 'Finca Olivares',
                'category' => 'Alimentación',
                // Imagen de aceite de oliva
                'image' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Queso Manchego Artesano',
                'price' => '€22.50',
                'tenant' => 'Quesería López',
                'category' => 'Lácteos',
                // Imagen de queso
                'image' => 'https://images.unsplash.com/photo-1452195100486-9cc805987862?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Vino Tinto Reserva 2020',
                'price' => '€28.00',
                'tenant' => 'Bodega del Valle',
                'category' => 'Bebidas',
                // Imagen de vino tinto
                'image' => 'https://images.unsplash.com/photo-1553361371-9b22f78e8b1d?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Miel de Romero Ecológica',
                'price' => '€12.75',
                'tenant' => 'ApiJaraba',
                'category' => 'Dulces',
                // Imagen de miel
                'image' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Jamón Ibérico de Bellota',
                'price' => '€189.00',
                'tenant' => 'Dehesa Serrana',
                'category' => 'Embutidos',
                // Imagen de jamón ibérico
                'image' => 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Azafrán de La Mancha',
                'price' => '€35.00',
                'tenant' => 'Especias del Sol',
                'category' => 'Especias',
                // Imagen de azafrán
                'image' => 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Almendras Marcona Premium',
                'price' => '€18.50',
                'tenant' => 'Frutos Selectos',
                'category' => 'Snacks',
                // Imagen de almendras
                'image' => 'https://images.unsplash.com/photo-1574570173583-e0eaf7eda2b1?w=400&h=300&fit=crop',
            ],
            [
                'title' => 'Chorizo Ibérico Curado',
                'price' => '€14.90',
                'tenant' => 'Embutidos Sierra',
                'category' => 'Embutidos',
                // Imagen de chorizo
                'image' => 'https://images.unsplash.com/photo-1622973536968-3ead9e780960?w=400&h=300&fit=crop',
            ],
        ];

        $products = [];
        foreach (array_slice($demoProducts, 0, $limit) as $i => $demo) {
            $productId = $i + 1;
            $products[] = [
                'id' => $productId,
                'title' => $demo['title'],
                'image' => $demo['image'],
                'price' => $demo['price'],
                'tenant' => $demo['tenant'],
                'category' => $demo['category'],
                // URL directa al producto (cuando existan productos reales usar /product/{id})
                'url' => '/es/marketplace/product/' . $productId,
            ];
        }

        return $products;
    }

    /**
     * Obtiene categorías con conteo.
     */
    protected function getCategories(): array
    {
        return [
            ['name' => 'Alimentación', 'count' => 45, 'icon' => '🥖', 'slug' => 'alimentacion'],
            ['name' => 'Bebidas', 'count' => 23, 'icon' => '🍷', 'slug' => 'bebidas'],
            ['name' => 'Embutidos', 'count' => 18, 'icon' => '🥓', 'slug' => 'embutidos'],
            ['name' => 'Lácteos', 'count' => 15, 'icon' => '🧀', 'slug' => 'lacteos'],
            ['name' => 'Especias', 'count' => 12, 'icon' => '🌿', 'slug' => 'especias'],
            ['name' => 'Dulces', 'count' => 20, 'icon' => '🍯', 'slug' => 'dulces'],
        ];
    }

    /**
     * Obtiene tenants activos.
     */
    protected function getActiveTenants(int $limit = 6): array
    {
        $tenants = [];

        try {
            if ($this->entityTypeManager->hasDefinition('tenant')) {
                $storage = $this->entityTypeManager->getStorage('tenant');
                $query = $storage->getQuery()
                    ->accessCheck(TRUE)
                    ->condition('status', 1)
                    ->condition('subscription_status', ['active', 'trial'], 'IN')
                    ->range(0, $limit);

                $ids = $query->execute();
                $entities = $storage->loadMultiple($ids);

                foreach ($entities as $tenant) {
                    $tenants[] = [
                        'id' => $tenant->id(),
                        'name' => $tenant->label(),
                        'logo' => 'https://ui-avatars.com/api/?name=' . urlencode($tenant->label()) . '&background=3b82f6&color=fff&size=80',
                        'url' => '/marketplace/search?q=' . urlencode($tenant->label()),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fallback.
        }

        // Demo tenants si no hay reales.
        if (empty($tenants)) {
            $demoTenants = ['Finca Olivares', 'Quesería López', 'Bodega del Valle', 'ApiJaraba', 'Dehesa Serrana', 'Especias del Sol'];
            foreach (array_slice($demoTenants, 0, $limit) as $i => $name) {
                $tenants[] = [
                    'id' => $i + 1,
                    'name' => $name,
                    'logo' => 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=' . dechex(rand(0x3b82f6, 0x8b5cf6)) . '&color=fff&size=80',
                    'url' => '/marketplace/search?q=' . urlencode($name),
                ];
            }
        }

        return $tenants;
    }

    /**
     * Obtiene estadísticas del marketplace.
     */
    protected function getMarketplaceStats(): array
    {
        return [
            'products' => [
                'value' => '150+',
                'label' => 'Productos',
                'icon' => '📦',
            ],
            'tenants' => [
                'value' => '25+',
                'label' => 'Tiendas',
                'icon' => '🏪',
            ],
            'categories' => [
                'value' => '12',
                'label' => 'Categorías',
                'icon' => '🏷️',
            ],
            'customers' => [
                'value' => '1K+',
                'label' => 'Clientes',
                'icon' => '👥',
            ],
        ];
    }

    /**
     * Página de búsqueda del marketplace.
     *
     * @return array
     *   Render array con resultados de búsqueda.
     */
    public function search(): array
    {
        $query = \Drupal::request()->query->get('q', '');
        $category = \Drupal::request()->query->get('category', '');

        // Obtener todos los productos (filtrados si hay query).
        $products = $this->searchProducts($query, $category);

        return [
            '#theme' => 'marketplace_search',
            '#query' => $query,
            '#category' => $category,
            '#products' => $products,
            '#categories' => $this->getCategories(),
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/marketplace',
                ],
            ],
            // CRÍTICO: Cache contexts para que varíe por parámetros de URL.
            '#cache' => [
                'contexts' => ['url.query_args:q', 'url.query_args:category'],
                'max-age' => 0, // Deshabilitar cache temporalmente para debug.
            ],
        ];
    }

    /**
     * Busca productos.
     */
    protected function searchProducts(string $query, string $category): array
    {
        // Por ahora, usar demo products filtrados.
        $allProducts = $this->getDemoProducts(20);

        if (!empty($query)) {
            $allProducts = array_filter($allProducts, function ($p) use ($query) {
                return stripos($p['title'], $query) !== FALSE || stripos($p['tenant'], $query) !== FALSE;
            });
        }

        if (!empty($category)) {
            // Mapeo de slugs a nombres de categoría
            $categoryMap = [
                'alimentacion' => 'Alimentación',
                'bebidas' => 'Bebidas',
                'embutidos' => 'Embutidos',
                'lacteos' => 'Lácteos',
                'especias' => 'Especias',
                'dulces' => 'Dulces',
                'snacks' => 'Snacks',
            ];
            $categoryName = $categoryMap[strtolower($category)] ?? $category;

            $allProducts = array_filter($allProducts, function ($p) use ($categoryName, $category) {
                if (!isset($p['category'])) {
                    return FALSE;
                }
                // Comparar tanto con nombre como con slug
                return strtolower($p['category']) === strtolower($categoryName)
                    || strtolower($p['category']) === strtolower($category);
            });
        }

        return array_values($allProducts);
    }

    /**
     * Vista de producto individual.
     *
     * @param int $id
     *   ID del producto.
     *
     * @return array
     *   Render array con información del producto.
     */
    public function viewProduct(int $id): array
    {
        // Buscar producto por ID en los demo products.
        $products = $this->getDemoProducts(20);
        $product = NULL;

        foreach ($products as $p) {
            if ((int) $p['id'] === $id) {
                $product = $p;
                break;
            }
        }

        if (!$product) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(
                'Producto no encontrado'
            );
        }

        // Obtener productos relacionados (misma categoría).
        $related = array_filter($products, function ($p) use ($product) {
            return $p['category'] === $product['category'] && $p['id'] !== $product['id'];
        });

        return [
            '#theme' => 'marketplace_product',
            '#product' => $product,
            '#related' => array_slice(array_values($related), 0, 4),
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/marketplace',
                ],
            ],
        ];
    }

}
