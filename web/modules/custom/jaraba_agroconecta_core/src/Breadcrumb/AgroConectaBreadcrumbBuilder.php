<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for AgroConecta routes.
 */
class AgroConectaBreadcrumbBuilder implements BreadcrumbBuilderInterface
{
    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    public function applies(RouteMatchInterface $route_match): bool
    {
        $route_name = $route_match->getRouteName() ?? '';
        return str_starts_with($route_name, 'jaraba_agroconecta_core.')
            || str_starts_with($route_name, 'jaraba_agroconecta.');
    }

    /**
     * {@inheritdoc}
     */
    public function build(RouteMatchInterface $route_match): Breadcrumb
    {
        $breadcrumb = new Breadcrumb();
        $breadcrumb->addCacheContexts(['route']);

        // Home.
        $breadcrumb->addLink(Link::createFromRoute($this->t('Inicio'), '<front>'));

        // Marketplace root.
        $breadcrumb->addLink(Link::createFromRoute(
            $this->t('Marketplace'),
            'jaraba_agroconecta_core.marketplace'
        ));

        $route_name = $route_match->getRouteName();

        // Producer portal routes.
        if (str_contains($route_name, '.producer.')) {
            $breadcrumb->addLink(Link::createFromRoute(
                $this->t('Mi Tienda'),
                'jaraba_agroconecta_core.producer.dashboard'
            ));

            $producerSections = [
                'jaraba_agroconecta_core.producer.orders' => $this->t('Pedidos'),
                'jaraba_agroconecta_core.producer.order_detail' => $this->t('Detalle Pedido'),
                'jaraba_agroconecta_core.producer.products' => $this->t('Productos'),
                'jaraba_agroconecta_core.producer.payouts' => $this->t('Finanzas'),
                'jaraba_agroconecta_core.producer.settings' => $this->t('Configuración'),
            ];

            if (isset($producerSections[$route_name])) {
                $breadcrumb->addLink(Link::createFromRoute(
                    $producerSections[$route_name],
                    $route_name
                ));
            }
        }

        // Customer portal routes.
        if (str_contains($route_name, '.customer.')) {
            $breadcrumb->addLink(Link::createFromRoute(
                $this->t('Mi Cuenta'),
                'jaraba_agroconecta_core.customer.dashboard'
            ));

            $customerSections = [
                'jaraba_agroconecta_core.customer.orders' => $this->t('Pedidos'),
                'jaraba_agroconecta_core.customer.order_detail' => $this->t('Detalle Pedido'),
                'jaraba_agroconecta_core.customer.addresses' => $this->t('Direcciones'),
                'jaraba_agroconecta_core.customer.favorites' => $this->t('Favoritos'),
            ];

            if (isset($customerSections[$route_name])) {
                $breadcrumb->addLink(Link::createFromRoute(
                    $customerSections[$route_name],
                    $route_name
                ));
            }
        }

        // Search and category.
        if ($route_name === 'jaraba_agroconecta_core.search_page') {
            $breadcrumb->addLink(Link::createFromRoute(
                $this->t('Buscar'),
                'jaraba_agroconecta_core.search_page'
            ));
        }

        if ($route_name === 'jaraba_agroconecta_core.category_page') {
            $breadcrumb->addLink(Link::createFromRoute(
                $this->t('Categoría'),
                $route_name
            ));
        }

        return $breadcrumb;
    }
}
