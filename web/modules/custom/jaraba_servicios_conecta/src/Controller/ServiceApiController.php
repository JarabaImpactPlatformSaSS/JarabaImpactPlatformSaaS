<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
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
   * The tenant context service.
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor.
   */
  public function __construct(
    ProviderService $provider_service,
    ServiceOfferingService $offering_service,
    AvailabilityService $availability_service,
    TenantContextService $tenant_context,
  ) {
    $this->providerService = $provider_service;
    $this->offeringService = $offering_service;
    $this->availabilityService = $availability_service;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_servicios_conecta.provider'),
      $container->get('jaraba_servicios_conecta.service_offering'),
      $container->get('jaraba_servicios_conecta.availability'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
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
      'tenant_id' => $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id'),
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

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => [
        'total' => $result['total'],
        'limit' => $limit,
        'offset' => $offset,
      ]]);
  }

  /**
   * GET /api/v1/servicios/providers/{id} - Detalle de profesional.
   */
  public function providerDetail(string $provider_profile): JsonResponse {
    $provider = $this->entityTypeManager()
      ->getStorage('provider_profile')
      ->load($provider_profile);

    if (!$provider) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Provider not found']], 404);
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

    return new JsonResponse(['success' => TRUE, 'data' => [
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
      'data' => $data, 'meta' => ['total' => count($data)]]);
  }

  /**
   * GET /api/v1/servicios/offerings/{id} - Detalle de servicio.
   */
  public function offeringDetail(string $service_offering): JsonResponse {
    $offering = $this->entityTypeManager()
      ->getStorage('service_offering')
      ->load($service_offering);

    if (!$offering) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Service not found']], 404);
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
    $data = json_decode($request->getContent(), TRUE);

    // Validate required fields.
    $required = ['provider_id', 'offering_id', 'datetime'];
    foreach ($required as $field) {
      if (empty($data[$field])) {
        return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'VALIDATION', 'message' => "Missing required field: {$field}"]], 400);
      }
    }

    $providerId = (int) $data['provider_id'];
    $offeringId = (int) $data['offering_id'];
    $datetime = $data['datetime'];

    // Validate provider exists and is active + approved.
    $provider = $this->entityTypeManager()->getStorage('provider_profile')->load($providerId);
    if (!$provider) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Provider not found']], 404);
    }
    if (!$provider->get('is_active')->value || $provider->get('verification_status')->value !== 'approved') {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Provider is not active or not approved']], 403);
    }

    // Load the service offering and validate it belongs to the provider.
    $offering = $this->entityTypeManager()->getStorage('service_offering')->load($offeringId);
    if (!$offering) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Service offering not found']], 404);
    }
    if ((int) $offering->get('provider_id')->target_id !== $providerId) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'VALIDATION', 'message' => 'Offering does not belong to the specified provider']], 422);
    }

    $duration = (int) $offering->get('duration_minutes')->value;
    $price = (float) $offering->get('price')->value;

    // Validate datetime is in the future and meets advance_booking_min.
    $requestedTimestamp = strtotime($datetime);
    $now = time();
    if ($requestedTimestamp === FALSE || $requestedTimestamp <= $now) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'VALIDATION', 'message' => 'Booking datetime must be in the future']], 422);
    }
    $advanceMinHours = (int) ($offering->get('advance_booking_min')->value ?? 2);
    if (($requestedTimestamp - $now) < ($advanceMinHours * 3600)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'VALIDATION', 'message' => "Booking requires at least {$advanceMinHours} hours advance notice"]], 422);
    }

    // Verify availability.
    if (!$this->availabilityService->isSlotAvailable($providerId, $datetime, $duration)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'CONFLICT', 'message' => 'Selected time slot is not available']], 409);
    }

    // Load client data from current user.
    $currentUser = $this->currentUser();
    $userEntity = $this->entityTypeManager()->getStorage('user')->load($currentUser->id());
    $clientName = $data['client_name'] ?? $userEntity->getDisplayName();
    $clientEmail = $data['client_email'] ?? $userEntity->getEmail();
    $clientPhone = $data['client_phone'] ?? '';

    // Generate meeting URL for online modality.
    $modality = $data['modality'] ?? $offering->get('modality')->value ?? 'in_person';
    $meetingUrl = NULL;

    // Create the booking entity.
    try {
      $booking = $this->entityTypeManager()->getStorage('booking')->create([
        'provider_id' => $providerId,
        'offering_id' => $offeringId,
        'uid' => $currentUser->id(),
        'client_name' => $clientName,
        'client_email' => $clientEmail,
        'client_phone' => $clientPhone,
        'booking_date' => $datetime,
        'duration_minutes' => $duration,
        'price' => $price,
        'status' => 'pending_confirmation',
        'payment_status' => 'not_required',
        'modality' => $modality,
        'client_notes' => $data['notes'] ?? '',
      ]);
      $booking->save();

      // Set meeting_url after save so we have the booking ID.
      if ($modality === 'online') {
        $meetingUrl = 'https://meet.jit.si/jaraba-booking-' . $booking->id();
        $booking->set('meeting_url', $meetingUrl);
        $booking->save();
      }
    }
    catch (\Exception $e) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Failed to create booking']], 500);
    }

    // Mark availability slot as booked.
    $this->availabilityService->markSlotBooked($providerId, $datetime, $duration);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'booking_id' => (int) $booking->id(),
        'status' => 'pending_confirmation',
        'datetime' => $datetime,
        'duration_minutes' => $duration,
        'price' => $price,
        'meeting_url' => $meetingUrl,
      ],
    ], 201);
  }

  /**
   * PATCH /api/v1/servicios/bookings/{id} - Actualizar reserva.
   */
  public function updateBooking(string $booking, Request $request): JsonResponse {
    $bookingEntity = $this->entityTypeManager()->getStorage('booking')->load($booking);

    if (!$bookingEntity) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'NOT_FOUND', 'message' => 'Booking not found']], 404);
    }

    // Verify the current user has permission (provider or client).
    $currentUserId = (int) $this->currentUser()->id();
    $providerProfileId = (int) $bookingEntity->get('provider_id')->target_id;
    $providerProfile = $this->entityTypeManager()->getStorage('provider_profile')->load($providerProfileId);
    if (!$providerProfile) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Provider not found']], 404);
    }
    $providerOwnerUid = (int) $providerProfile->getOwnerId();
    $clientUid = (int) $bookingEntity->getOwnerId();

    if ($currentUserId !== $providerOwnerUid && $currentUserId !== $clientUid) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Access denied']], 403);
    }

    $data = json_decode($request->getContent(), TRUE);
    $newStatus = $data['status'] ?? NULL;

    if (!$newStatus) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'VALIDATION', 'message' => 'Missing status field']], 400);
    }

    $isProvider = ($currentUserId === $providerOwnerUid);

    // Map generic 'cancelled' to role-specific status.
    if ($newStatus === 'cancelled') {
      $newStatus = $isProvider ? 'cancelled_provider' : 'cancelled_client';
    }

    // Validate state transition.
    $currentStatus = $bookingEntity->get('status')->value;
    $allowedTransitions = [
      'pending_confirmation' => ['confirmed', 'cancelled_client', 'cancelled_provider'],
      'confirmed' => ['completed', 'cancelled_client', 'cancelled_provider', 'no_show'],
    ];

    if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus], TRUE)) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'VALIDATION', 'message' => "Invalid transition: {$currentStatus} -> {$newStatus}"]], 422);
    }

    // Only providers can confirm or mark completed/no_show.
    if (in_array($newStatus, ['confirmed', 'completed', 'no_show'], TRUE) && !$isProvider) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Only the provider can perform this action']], 403);
    }

    $bookingEntity->set('status', $newStatus);

    if (!empty($data['cancellation_reason'])) {
      $bookingEntity->set('cancellation_reason', $data['cancellation_reason']);
    }

    // If cancelled, release the availability slot.
    if (str_starts_with($newStatus, 'cancelled_')) {
      $this->availabilityService->releaseSlot(
        $providerProfileId,
        $bookingEntity->get('booking_date')->value ?? '',
        (int) $bookingEntity->get('duration_minutes')->value
      );
    }

    $bookingEntity->save();

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'booking_id' => (int) $bookingEntity->id(),
        'status' => $newStatus,
        'previous_status' => $currentStatus,
      ],
    ]);
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
