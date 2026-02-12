<?php

namespace Drupal\jaraba_servicios_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_servicios_conecta\Service\ProviderService;
use Drupal\jaraba_servicios_conecta\Service\ServiceOfferingService;
use Drupal\jaraba_servicios_conecta\Service\AvailabilityService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST de ServiciosConecta.
 *
 * Estructura: Endpoints JSON para profesionales, servicios,
 *   reservas y disponibilidad.
 *
 * Lógica: Cada endpoint retorna JsonResponse con la estructura
 *   estándar del ecosistema: {data, meta, links}.
 */
class ServiceApiController extends ControllerBase {

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
   * GET /api/v1/servicios/providers - Listado de profesionales.
   */
  public function providersList(Request $request): JsonResponse {
    $filters = [
      'category' => $request->query->get('category'),
      'city' => $request->query->get('city'),
      'search' => $request->query->get('search'),
      'tenant_id' => $request->query->get('tenant_id'),
    ];
    $limit = min(50, max(1, (int) $request->query->get('limit', 12)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $result = $this->providerService->getMarketplaceProviders(
      array_filter($filters),
      $limit,
      $offset
    );

    $data = [];
    foreach ($result['providers'] as $provider) {
      $data[] = [
        'id' => (int) $provider->id(),
        'display_name' => $provider->get('display_name')->value,
        'professional_title' => $provider->get('professional_title')->value ?? '',
        'slug' => $provider->get('slug')->value ?? '',
        'city' => $provider->get('address_city')->value ?? '',
        'average_rating' => (float) ($provider->get('average_rating')->value ?? 0),
        'total_reviews' => (int) ($provider->get('total_reviews')->value ?? 0),
        'accepts_online' => (bool) $provider->get('accepts_online')->value,
      ];
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'total' => $result['total'],
        'limit' => $limit,
        'offset' => $offset,
      ],
    ]);
  }

  /**
   * GET /api/v1/servicios/providers/{id} - Detalle de profesional.
   */
  public function providerDetail(string $provider_profile): JsonResponse {
    $provider = $this->entityTypeManager()
      ->getStorage('provider_profile')
      ->load($provider_profile);

    if (!$provider) {
      return new JsonResponse(['error' => 'Provider not found'], 404);
    }

    $services = $this->offeringService->getProviderOfferings((int) $provider->id());

    $services_data = [];
    foreach ($services as $service) {
      $services_data[] = [
        'id' => (int) $service->id(),
        'title' => $service->get('title')->value,
        'price' => (float) $service->get('price')->value,
        'price_type' => $service->get('price_type')->value,
        'duration_minutes' => (int) $service->get('duration_minutes')->value,
        'modality' => $service->get('modality')->value,
      ];
    }

    return new JsonResponse([
      'data' => [
        'id' => (int) $provider->id(),
        'display_name' => $provider->get('display_name')->value,
        'professional_title' => $provider->get('professional_title')->value ?? '',
        'slug' => $provider->get('slug')->value ?? '',
        'city' => $provider->get('address_city')->value ?? '',
        'average_rating' => (float) ($provider->get('average_rating')->value ?? 0),
        'total_reviews' => (int) ($provider->get('total_reviews')->value ?? 0),
        'services' => $services_data,
      ],
    ]);
  }

  /**
   * GET /api/v1/servicios/offerings - Listado de servicios.
   */
  public function offeringsList(Request $request): JsonResponse {
    $provider_id = $request->query->get('provider_id');

    if ($provider_id) {
      $offerings = $this->offeringService->getProviderOfferings((int) $provider_id);
    }
    else {
      $offerings = $this->offeringService->getFeaturedOfferings(20);
    }

    $data = [];
    foreach ($offerings as $offering) {
      $provider = $offering->get('provider_id')->entity;
      $data[] = [
        'id' => (int) $offering->id(),
        'title' => $offering->get('title')->value,
        'price' => (float) $offering->get('price')->value,
        'price_type' => $offering->get('price_type')->value,
        'duration_minutes' => (int) $offering->get('duration_minutes')->value,
        'modality' => $offering->get('modality')->value,
        'provider_name' => $provider ? $provider->get('display_name')->value : '',
      ];
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => ['total' => count($data)],
    ]);
  }

  /**
   * GET /api/v1/servicios/offerings/{id} - Detalle de servicio.
   */
  public function offeringDetail(string $service_offering): JsonResponse {
    $offering = $this->entityTypeManager()
      ->getStorage('service_offering')
      ->load($service_offering);

    if (!$offering) {
      return new JsonResponse(['error' => 'Service not found'], 404);
    }

    return new JsonResponse([
      'data' => [
        'id' => (int) $offering->id(),
        'title' => $offering->get('title')->value,
        'description' => $offering->get('description')->value ?? '',
        'price' => (float) $offering->get('price')->value,
        'price_type' => $offering->get('price_type')->value,
        'duration_minutes' => (int) $offering->get('duration_minutes')->value,
        'modality' => $offering->get('modality')->value,
        'max_participants' => (int) $offering->get('max_participants')->value,
      ],
    ]);
  }

  /**
   * POST /api/v1/servicios/bookings - Crear reserva.
   */
  public function createBooking(Request $request): JsonResponse {
    // TODO Fase 2: Implementar lógica completa de creación de reserva
    // con validación de disponibilidad, pago anticipado si aplica,
    // generación de meeting URL para consultas online
    return new JsonResponse([
      'error' => 'Not implemented yet. Coming in Phase 2.',
    ], 501);
  }

  /**
   * PATCH /api/v1/servicios/bookings/{id} - Actualizar reserva.
   */
  public function updateBooking(string $booking, Request $request): JsonResponse {
    // TODO Fase 2: Implementar actualización de estado de reserva
    return new JsonResponse([
      'error' => 'Not implemented yet. Coming in Phase 2.',
    ], 501);
  }

  /**
   * GET /api/v1/servicios/providers/{id}/availability - Disponibilidad.
   */
  public function providerAvailability(string $provider_profile, Request $request): JsonResponse {
    $date = $request->query->get('date', date('Y-m-d'));
    $duration = (int) $request->query->get('duration', 60);

    $available_slots = $this->availabilityService->getAvailableSlots(
      (int) $provider_profile,
      $date,
      $duration
    );

    return new JsonResponse([
      'data' => [
        'provider_id' => (int) $provider_profile,
        'date' => $date,
        'duration_minutes' => $duration,
        'available_slots' => $available_slots,
      ],
    ]);
  }

}
