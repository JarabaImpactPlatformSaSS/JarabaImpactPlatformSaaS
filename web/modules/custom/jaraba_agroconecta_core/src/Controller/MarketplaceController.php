<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_agroconecta_core\Entity\ProductAgro;
use Drupal\jaraba_agroconecta_core\Service\MarketplaceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador del marketplace público de AgroConecta.
 *
 * Gestiona las páginas frontend del marketplace: listado de productos
 * y detalle del producto. Usa templates limpias sin page.content.
 */
class MarketplaceController extends ControllerBase
{

    /**
     * Servicio del marketplace.
     */
    protected MarketplaceService $marketplace;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->marketplace = $container->get('jaraba_agroconecta.marketplace_service');
        return $instance;
    }

    /**
     * Página principal del marketplace.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request HTTP.
     *
     * @return array
     *   Render array con los productos del marketplace.
     */
    public function index(Request $request): array
    {
        $config = $this->config('jaraba_agroconecta_core.settings');
        $perPage = (int) ($config->get('products_per_page') ?? 12);
        $page = (int) $request->query->get('page', 0);

        $products = $this->marketplace->getPublishedProducts($perPage, $page * $perPage);
        $totalProducts = $this->marketplace->countPublishedProducts();

        return [
            '#theme' => 'agro_marketplace',
            '#products' => $products,
            '#marketplace_name' => $config->get('marketplace_name') ?? $this->t('AgroConecta'),
            '#marketplace_description' => $config->get('marketplace_description') ?? '',
            '#total_products' => $totalProducts,
            '#pager' => [
                '#type' => 'pager',
            ],
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.marketplace'],
            ],
            '#cache' => [
                'tags' => ['product_agro_list'],
                'contexts' => ['url.query_args:page'],
            ],
        ];
    }

    /**
     * Detalle de un producto agro.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\ProductAgro $product_agro
     *   La entidad del producto.
     *
     * @return array
     *   Render array con el detalle del producto.
     */
    public function productDetail(ProductAgro $product_agro): array
    {
        $producer = NULL;
        $producerId = $product_agro->get('producer_id')->target_id;
        if ($producerId) {
            $producer = $this->entityTypeManager()->getStorage('producer_profile')->load($producerId);
        }

        return [
            '#theme' => 'agro_product_detail',
            '#product' => $product_agro,
            '#producer' => $producer,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.frontend'],
            ],
            '#cache' => [
                'tags' => $product_agro->getCacheTags(),
            ],
        ];
    }

}
