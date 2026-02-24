<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Breadcrumb builder for ServiciosConecta routes.
 */
class ServiciosConectaBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Constructs a ServiciosConectaBreadcrumbBuilder.
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
    return str_starts_with($route_name, 'jaraba_servicios_conecta.');
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
    if ($route_name === 'jaraba_servicios_conecta.marketplace') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ServiciosConecta'),
        'jaraba_servicios_conecta.marketplace'
      ));
      return $breadcrumb;
    }

    if ($route_name === 'jaraba_servicios_conecta.marketplace.provider') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ServiciosConecta'),
        'jaraba_servicios_conecta.marketplace'
      ));
      $provider_slug = $route_match->getParameter('provider_slug') ?? '';
      $breadcrumb->addLink(Link::createFromRoute(
        $provider_slug,
        $route_name,
        ['provider_slug' => $provider_slug]
      ));
      return $breadcrumb;
    }

    if ($route_name === 'jaraba_servicios_conecta.marketplace.booking') {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('ServiciosConecta'),
        'jaraba_servicios_conecta.marketplace'
      ));
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Reservar'),
        $route_name,
        ['service_offering' => $route_match->getParameter('service_offering')]
      ));
      return $breadcrumb;
    }

    // --- Provider portal routes ---
    if (str_contains($route_name, '.provider_portal')) {
      $breadcrumb->addLink(Link::createFromRoute(
        $this->t('Mi Servicio'),
        'jaraba_servicios_conecta.provider_portal'
      ));

      $portalSections = [
        'jaraba_servicios_conecta.provider_portal.offerings' => $this->t('Mis Servicios'),
        'jaraba_servicios_conecta.provider_portal.offering_add' => $this->t('Nuevo Servicio'),
        'jaraba_servicios_conecta.provider_portal.bookings' => $this->t('Mis Reservas'),
        'jaraba_servicios_conecta.provider_portal.calendar' => $this->t('Calendario'),
        'jaraba_servicios_conecta.provider_portal.profile' => $this->t('Perfil'),
      ];

      if (isset($portalSections[$route_name])) {
        $breadcrumb->addLink(Link::createFromRoute(
          $portalSections[$route_name],
          $route_name
        ));
      }

      return $breadcrumb;
    }

    return $breadcrumb;
  }

}
