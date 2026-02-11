<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de recomendaciones de productos cross-tenant.
 *
 * PROPÓSITO:
 * Proporciona recomendaciones personalizadas de productos del marketplace
 * basándose en historial de compras, categorías preferidas y comportamiento.
 *
 * PHASE 13: Marketplace & Network Effects
 */
class MarketplaceRecommendationService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Obtiene productos recomendados para un usuario.
     *
     * @param int $limit
     *   Número de recomendaciones.
     * @param string|null $currentProductId
     *   ID del producto actual (para "productos similares").
     *
     * @return array
     *   Array de productos recomendados.
     */
    public function getRecommendations(int $limit = 6, ?string $currentProductId = NULL): array
    {
        $recommendations = [];

        // Estrategia 1: Productos similares (misma categoría).
        if ($currentProductId) {
            $recommendations = array_merge(
                $recommendations,
                $this->getSimilarProducts($currentProductId, $limit)
            );
        }

        // Estrategia 2: Basado en historial del usuario.
        if ($this->currentUser->isAuthenticated()) {
            $recommendations = array_merge(
                $recommendations,
                $this->getUserBasedRecommendations($limit)
            );
        }

        // Estrategia 3: Productos populares (fallback).
        if (count($recommendations) < $limit) {
            $recommendations = array_merge(
                $recommendations,
                $this->getPopularProducts($limit - count($recommendations))
            );
        }

        // Eliminar duplicados y limitar.
        $seen = [];
        $unique = [];
        foreach ($recommendations as $product) {
            if (!isset($seen[$product['id']])) {
                $seen[$product['id']] = TRUE;
                $unique[] = $product;
            }
        }

        return array_slice($unique, 0, $limit);
    }

    /**
     * Obtiene productos similares al actual.
     */
    protected function getSimilarProducts(string $productId, int $limit): array
    {
        // Demo: En producción usaríamos embeddings vectoriales.
        return $this->getDemoProducts($limit, 'similar');
    }

    /**
     * Recomendaciones basadas en historial del usuario.
     */
    protected function getUserBasedRecommendations(int $limit): array
    {
        // Obtener categorías preferidas del usuario.
        $userId = $this->currentUser->id();

        // Demo: En producción analizaríamos órdenes pasadas.
        return $this->getDemoProducts($limit, 'personalized');
    }

    /**
     * Productos populares (más vendidos).
     */
    protected function getPopularProducts(int $limit): array
    {
        return $this->getDemoProducts($limit, 'popular');
    }

    /**
     * Cross-selling: productos complementarios.
     */
    public function getCrossSellProducts(string $productId, int $limit = 4): array
    {
        return $this->getDemoProducts($limit, 'crosssell');
    }

    /**
     * Upselling: productos premium similares.
     */
    public function getUpsellProducts(string $productId, int $limit = 3): array
    {
        return $this->getDemoProducts($limit, 'upsell');
    }

    /**
     * Productos de tenants colaboradores.
     */
    public function getPartnerProducts(string $tenantId, int $limit = 4): array
    {
        return $this->getDemoProducts($limit, 'partner');
    }

    /**
     * Genera productos demo.
     */
    protected function getDemoProducts(int $limit, string $type = 'generic'): array
    {
        $products = [
            'similar' => [
                ['id' => 'sim1', 'title' => 'Aceite de Oliva Premium', 'price' => '€18.90', 'tenant' => 'Olivares del Sur', 'reason' => 'Similar al que estás viendo'],
                ['id' => 'sim2', 'title' => 'Aceite Arbequina', 'price' => '€21.00', 'tenant' => 'Finca El Olivo', 'reason' => 'Misma categoría'],
                ['id' => 'sim3', 'title' => 'Aceite Picual Ecológico', 'price' => '€24.50', 'tenant' => 'Bio Andalucía', 'reason' => 'Producto ecológico'],
            ],
            'personalized' => [
                ['id' => 'per1', 'title' => 'Queso Curado Reserva', 'price' => '€28.00', 'tenant' => 'Quesería Manchega', 'reason' => 'Basado en tu historial'],
                ['id' => 'per2', 'title' => 'Miel de Azahar', 'price' => '€14.50', 'tenant' => 'ApiJaraba', 'reason' => 'Te gustará esto'],
            ],
            'popular' => [
                ['id' => 'pop1', 'title' => 'Jamón Ibérico de Bellota', 'price' => '€189.00', 'tenant' => 'Dehesa Serrana', 'reason' => 'Más vendido'],
                ['id' => 'pop2', 'title' => 'Vino Reserva 2019', 'price' => '€32.00', 'tenant' => 'Bodega del Valle', 'reason' => 'Más valorado'],
                ['id' => 'pop3', 'title' => 'Azafrán La Mancha DOP', 'price' => '€45.00', 'tenant' => 'Especias del Sol', 'reason' => 'Tendencia'],
            ],
            'crosssell' => [
                ['id' => 'cs1', 'title' => 'Pan Artesano', 'price' => '€4.50', 'tenant' => 'Panadería Rural', 'reason' => 'Complementa tu compra'],
                ['id' => 'cs2', 'title' => 'Vinagre Balsámico', 'price' => '€12.00', 'tenant' => 'Viñedos Premium', 'reason' => 'Perfecto para combinar'],
            ],
            'upsell' => [
                ['id' => 'up1', 'title' => 'Pack Gourmet Completo', 'price' => '€89.00', 'tenant' => 'Marketplace Jaraba', 'reason' => 'Mejor valor'],
                ['id' => 'up2', 'title' => 'Selección Premium', 'price' => '€120.00', 'tenant' => 'Marketplace Jaraba', 'reason' => 'Edición especial'],
            ],
            'partner' => [
                ['id' => 'ptr1', 'title' => 'Surtido Mixto', 'price' => '€55.00', 'tenant' => 'Tienda Colaboradora', 'reason' => 'De nuestros partners'],
            ],
        ];

        $result = $products[$type] ?? $products['popular'];

        foreach ($result as &$product) {
            $product['image'] = 'https://picsum.photos/seed/' . $product['id'] . '/400/300';
            $product['url'] = '#';
        }

        return array_slice($result, 0, $limit);
    }

}
