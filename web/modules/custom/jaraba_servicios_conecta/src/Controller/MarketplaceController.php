<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_servicios_conecta\Service\ProviderService;
use Drupal\jaraba_servicios_conecta\Service\ServiceOfferingService;
use Drupal\jaraba_servicios_conecta\Service\AvailabilityService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller del marketplace público de servicios profesionales.
 *
 * Estructura: Gestiona las páginas públicas del marketplace:
 *   listado de profesionales, perfil individual y página de reserva.
 *
 * Lógica: Cada método obtiene datos del ProviderService, prepara
 *   variables y retorna un render array con #theme que delega
 *   la presentación al template Twig correspondiente.
 */
class MarketplaceController extends ControllerBase {

  /**
   * El servicio de profesionales.
   */
  protected ProviderService $providerService;

  /**
   * El servicio de servicios ofertados.
   */
  protected ServiceOfferingService $offeringService;

  /**
   * El servicio de disponibilidad.
   */
  protected AvailabilityService $availabilityService;

  /**
   * Constructor.
   */
  public function __construct(
    ProviderService $provider_service,
    ServiceOfferingService $offering_service,
    AvailabilityService $availability_service,
  ) {
    $this->providerService = $provider_service;
    $this->offeringService = $offering_service;
    $this->availabilityService = $availability_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_servicios_conecta.provider'),
      $container->get('jaraba_servicios_conecta.service_offering'),
      $container->get('jaraba_servicios_conecta.availability'),
    );
  }

  /**
   * Página principal del marketplace de servicios.
   */
  public function marketplace(Request $request): array {
    $filters = [
      'category' => $request->query->get('category'),
      'city' => $request->query->get('city'),
      'search' => $request->query->get('search'),
    ];
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = 12;
    $offset = ($page - 1) * $limit;

    $result = $this->providerService->getMarketplaceProviders($filters, $limit, $offset);

    // Obtener categorías y ciudades para filtros
    $categories = $this->entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('servicios_category', 0, 1);
    $cities = $this->providerService->getActiveCities();

    return [
      '#theme' => 'servicios_marketplace',
      '#providers' => $result['providers'],
      '#categories' => $categories,
      '#modalities' => [],
      '#current_filters' => array_filter($filters),
      '#total_results' => $result['total'],
      '#pager' => [
        'current' => $page,
        'total_pages' => ceil($result['total'] / $limit),
        'total_results' => $result['total'],
      ],
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/marketplace'],
      ],
      '#cache' => [
        'contexts' => ['url.query_args'],
        'tags' => ['provider_profile_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Página de detalle de un profesional.
   */
  public function providerPage(string $provider_slug): array {
    $provider = $this->providerService->getProviderBySlug($provider_slug);
    if (!$provider) {
      throw new NotFoundHttpException();
    }

    $services = $this->offeringService->getProviderOfferings((int) $provider->id());
    $slots_by_day = $this->availabilityService->getProviderSlots((int) $provider->id());

    // Generar JSON-LD para SEO (Schema.org ProfessionalService)
    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'ProfessionalService',
      'name' => $provider->get('display_name')->value,
      'description' => strip_tags($provider->get('description')->value ?? ''),
      'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $provider->get('address_street')->value ?? '',
        'addressLocality' => $provider->get('address_city')->value ?? '',
        'postalCode' => $provider->get('address_postal_code')->value ?? '',
        'addressRegion' => $provider->get('address_province')->value ?? '',
        'addressCountry' => $provider->get('address_country')->value ?? 'ES',
      ],
      'telephone' => $provider->get('phone')->value ?? '',
    ];

    $rating = $provider->get('average_rating')->value;
    if ($rating && $rating > 0) {
      $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $rating,
        'reviewCount' => $provider->get('total_reviews')->value ?? 0,
      ];
    }

    return [
      '#theme' => 'servicios_provider_detail',
      '#provider' => $provider,
      '#services' => $services,
      '#availability' => $slots_by_day,
      '#reviews' => [],
      '#review_summary' => NULL,
      '#schema_json_ld' => json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/marketplace'],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['provider_profile:' . $provider->id()],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Página de reserva de cita.
   */
  public function bookingPage(string $service_offering): array {
    $offering = $this->entityTypeManager()
      ->getStorage('service_offering')
      ->load($service_offering);

    if (!$offering || !$offering->get('is_published')->value) {
      throw new NotFoundHttpException();
    }

    $provider = $offering->get('provider_id')->entity;
    if (!$provider || !$provider->get('is_active')->value) {
      throw new NotFoundHttpException();
    }

    return [
      '#markup' => '<div class="servicios-booking-page" data-offering-id="' . $offering->id() . '" data-provider-id="' . $provider->id() . '">'
        . '<p>' . $this->t('Selector de fecha y hora para reservar cita. Implementación completa en Fase 2.') . '</p>'
        . '</div>',
      '#attached' => [
        'library' => ['jaraba_servicios_conecta/booking'],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'user.roles:authenticated'],
        'tags' => ['service_offering:' . $offering->id(), 'provider_profile:' . $provider->id()],
        'max-age' => 0,
      ],
    ];
  }

}
