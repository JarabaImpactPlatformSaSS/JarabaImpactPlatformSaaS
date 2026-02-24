<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for ComercioConecta routes.
 */
class ComercioConectaBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs a ComercioConectaBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    $route_name = $route_match->getRouteName() ?? '';
    return str_starts_with($route_name, 'jaraba_comercio_conecta.');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    // Home.
    $breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));

    $route_name = $route_match->getRouteName();

    // --- Marketplace routes ---
    if ($route_name === 'jaraba_comercio_conecta.marketplace') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ComercioConecta'),
        'jaraba_comercio_conecta.marketplace'
      ));
      return $breadcrumb;
    }

    if ($route_name === 'jaraba_comercio_conecta.marketplace.merchant') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ComercioConecta'),
        'jaraba_comercio_conecta.marketplace'
      ));
      $merchant_name = $route_match->getParameter('merchant_slug') ?? '';
      $breadcrumb->addLink(Link::createFromRoute(
        $merchant_name,
        $route_name,
        ['merchant_slug' => $merchant_name]
      ));
      return $breadcrumb;
    }

    if ($route_name === 'jaraba_comercio_conecta.marketplace.product') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ComercioConecta'),
        'jaraba_comercio_conecta.marketplace'
      ));
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Producto'),
        $route_name,
        ['product_retail' => $route_match->getParameter('product_retail')]
      ));
      return $breadcrumb;
    }

    // --- Search route ---
    if ($route_name === 'jaraba_comercio_conecta.search') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ComercioConecta'),
        'jaraba_comercio_conecta.marketplace'
      ));
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Buscar'),
        'jaraba_comercio_conecta.search'
      ));
      return $breadcrumb;
    }

    // --- Checkout route ---
    if ($route_name === 'jaraba_comercio_conecta.checkout') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ComercioConecta'),
        'jaraba_comercio_conecta.marketplace'
      ));
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Checkout'),
        'jaraba_comercio_conecta.checkout'
      ));
      return $breadcrumb;
    }

    // --- Merchant portal routes ---
    if (str_contains($route_name, '.merchant_portal')) {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Mi Comercio'),
        'jaraba_comercio_conecta.merchant_portal'
      ));

      $merchantSections = [
        'jaraba_comercio_conecta.merchant_portal.products' => $this->t('Productos'),
        'jaraba_comercio_conecta.merchant_portal.stock' => $this->t('Inventario'),
        'jaraba_comercio_conecta.merchant_portal.analytics' => $this->t('Analíticas'),
        'jaraba_comercio_conecta.merchant_portal.orders' => $this->t('Pedidos'),
        'jaraba_comercio_conecta.merchant_portal.payments' => $this->t('Pagos'),
        'jaraba_comercio_conecta.merchant_portal.settings' => $this->t('Configuración'),
      ];

      if (isset($merchantSections[$route_name])) {
        $breadcrumb->addLink(Link::createFromRoute(
          $merchantSections[$route_name],
          $route_name
        ));
      }

      return $breadcrumb;
    }

    // --- Customer portal routes ---
    if (str_contains($route_name, '.customer_portal')) {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Mi Portal'),
        'jaraba_comercio_conecta.customer_portal'
      ));

      $customerSections = [
        'jaraba_comercio_conecta.customer_portal.wishlist' => $this->t('Lista de deseos'),
      ];

      if (isset($customerSections[$route_name])) {
        $breadcrumb->addLink(Link::createFromRoute(
          $customerSections[$route_name],
          $route_name
        ));
      }

      return $breadcrumb;
    }

    // --- My orders route ---
    if ($route_name === 'jaraba_comercio_conecta.my_orders') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Mis Pedidos'),
        'jaraba_comercio_conecta.my_orders'
      ));
      return $breadcrumb;
    }

    return $breadcrumb;
  }

}
