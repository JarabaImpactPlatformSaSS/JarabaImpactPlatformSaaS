<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_agroconecta_core\Entity\ProductAgro;
use Drupal\jaraba_agroconecta_core\Service\ProductAgroService;
use Drupal\jaraba_agroconecta_core\Service\ProducerProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de la API REST de AgroConecta.
 *
 * Proporciona endpoints JSON para consulta de productos y productores.
 * Incluye rate limiting y sanitización de parámetros.
 */
class AgroApiController extends ControllerBase
{

    /**
     * Servicio de productos.
     */
    protected ProductAgroService $productService;

    /**
     * Servicio de productores.
     */
    protected ProducerProfileService $producerService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->productService = $container->get('jaraba_agroconecta.product_service');
        $instance->producerService = $container->get('jaraba_agroconecta.producer_service');
        return $instance;
    }

    /**
     * Listado de productos (API).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con los productos.
     */
    public function listProducts(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 20), 50);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $products = $this->productService->getPublishedProducts($limit, $offset);
        $total = $this->productService->countPublishedProducts();

        $data = [
            'meta' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'data' => array_map([$this, 'serializeProduct'], $products),
        ];

        return new JsonResponse($data);
    }

    /**
     * Detalle de un producto (API).
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\ProductAgro $product_agro
     *   La entidad del producto.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con el producto.
     */
    public function getProduct(ProductAgro $product_agro): JsonResponse
    {
        return new JsonResponse([
            'data' => $this->serializeProduct($product_agro),
        ]);
    }

    /**
     * Listado de productores (API).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con los productores.
     */
    public function listProducers(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 20), 50);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $producers = $this->producerService->getActiveProducers($limit, $offset);

        $data = [
            'data' => array_map([$this, 'serializeProducer'], $producers),
        ];

        return new JsonResponse($data);
    }

    /**
     * Serializa un producto a array JSON.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\ProductAgro $product
     *   La entidad del producto.
     *
     * @return array
     *   Array serializado del producto.
     */
    protected function serializeProduct(ProductAgro $product): array
    {
        return [
            'id' => (int) $product->id(),
            'uuid' => $product->uuid(),
            'name' => $product->get('name')->value,
            'sku' => $product->get('sku')->value,
            'description_short' => $product->get('description_short')->value,
            'price' => (float) $product->get('price')->value,
            'currency_code' => $product->get('currency_code')->value,
            'stock' => (int) $product->get('stock')->value,
            'unit' => $product->get('unit')->value,
            'category' => $product->get('category')->value,
            'origin' => $product->get('origin')->value,
            'is_published' => $product->isPublished(),
            'has_stock' => $product->hasStock(),
            'formatted_price' => $product->getFormattedPrice(),
            'created' => $product->get('created')->value,
        ];
    }

    /**
     * Serializa un productor a array JSON.
     *
     * @param mixed $producer
     *   La entidad del productor.
     *
     * @return array
     *   Array serializado del productor.
     */
    protected function serializeProducer(mixed $producer): array
    {
        return [
            'id' => (int) $producer->id(),
            'uuid' => $producer->uuid(),
            'farm_name' => $producer->get('farm_name')->value,
            'producer_name' => $producer->get('producer_name')->value,
            'location' => $producer->get('location')->value,
            'production_type' => $producer->get('production_type')->value,
            'is_active' => $producer->isActive(),
        ];
    }

}
