<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio orquestador del marketplace AgroConecta.
 *
 * Combina ProductAgroService y ProducerProfileService para
 * proporcionar funcionalidades completas del marketplace.
 */
class MarketplaceService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected ProductAgroService $productService,
        protected ProducerProfileService $producerService,
    ) {
    }

    /**
     * Obtiene productos publicados para el marketplace.
     *
     * @param int $limit
     *   Número de productos.
     * @param int $offset
     *   Offset para paginación.
     *
     * @return array
     *   Array de entidades ProductAgro.
     */
    public function getPublishedProducts(int $limit = 12, int $offset = 0): array
    {
        return $this->productService->getPublishedProducts($limit, $offset);
    }

    /**
     * Cuenta productos publicados.
     *
     * @return int
     *   Total de productos publicados.
     */
    public function countPublishedProducts(): int
    {
        return $this->productService->countPublishedProducts();
    }

    /**
     * Obtiene datos completos de un producto con su productor.
     *
     * @param int $productId
     *   ID del producto.
     *
     * @return array|null
     *   Array con 'product' y 'producer', o NULL.
     */
    public function getProductWithProducer(int $productId): ?array
    {
        $product = $this->entityTypeManager->getStorage('product_agro')->load($productId);
        if (!$product) {
            return NULL;
        }

        $producerId = $product->get('producer_id')->target_id;
        $producer = $producerId
            ? $this->entityTypeManager->getStorage('producer_profile')->load($producerId)
            : NULL;

        return [
            'product' => $product,
            'producer' => $producer,
        ];
    }

    /**
     * Obtiene estadísticas básicas del marketplace.
     *
     * @return array
     *   Array con estadísticas: total_products, total_producers, etc.
     */
    public function getMarketplaceStats(): array
    {
        $totalProducts = $this->productService->countPublishedProducts();

        $producerStorage = $this->entityTypeManager->getStorage('producer_profile');
        $totalProducers = (int) $producerStorage->getQuery()
            ->condition('status', 1)
            ->count()
            ->accessCheck(TRUE)
            ->execute();

        return [
            'total_products' => $totalProducts,
            'total_producers' => $totalProducers,
        ];
    }

}
