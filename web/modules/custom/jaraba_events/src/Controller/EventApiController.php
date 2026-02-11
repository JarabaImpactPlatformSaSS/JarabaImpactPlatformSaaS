<?php

namespace Drupal\jaraba_events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_events\Exception\DuplicateRegistrationException;
use Drupal\jaraba_events\Exception\EventFullException;
use Drupal\jaraba_events\Exception\EventNotOpenException;
use Drupal\jaraba_events\Service\EventAnalyticsService;
use Drupal\jaraba_events\Service\EventRegistrationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para eventos de marketing.
 *
 * ESTRUCTURA:
 * API JSON para operaciones programáticas sobre eventos. Los endpoints
 * siguen la convención /api/v1/events/* y requieren autenticación.
 * Las respuestas siguen el formato estándar JSON:API con claves
 * 'data', 'meta', y 'errors'.
 *
 * LÓGICA:
 * Todos los endpoints validan permisos mediante las anotaciones de routing.
 * Los datos se serializan a JSON con las claves mínimas necesarias.
 * Los errores devuelven códigos HTTP apropiados (400, 404, 409, 422).
 *
 * RELACIONES:
 * - EventApiController -> EventRegistrationService (registro, check-in)
 * - EventApiController -> EventAnalyticsService (métricas, stats)
 * - EventApiController -> EntityTypeManager (consultas de entidad)
 *
 * @package Drupal\jaraba_events\Controller
 */
class EventApiController extends ControllerBase {

  /**
   * Servicio de registro de asistentes.
   *
   * @var \Drupal\jaraba_events\Service\EventRegistrationService|null
   */
  protected ?EventRegistrationService $registrationService = NULL;

  /**
   * Servicio de analítica de eventos.
   *
   * @var \Drupal\jaraba_events\Service\EventAnalyticsService|null
   */
  protected ?EventAnalyticsService $analyticsService = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->registrationService = $container->get('jaraba_events.registration');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    try {
      $instance->analyticsService = $container->get('jaraba_events.analytics');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    return $instance;
  }

  /**
   * Lista los eventos publicados y futuros (GET /api/v1/events).
   *
   * LÓGICA:
   * Retorna los eventos publicados con fecha futura, ordenados por
   * fecha de inicio. Soporta paginación y filtros por tipo y formato.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con parámetros opcionales:
   *   - 'type' (string): Filtrar por event_type.
   *   - 'format' (string): Filtrar por formato.
   *   - 'page' (int): Número de página (0-based).
   *   - 'limit' (int): Elementos por página (máx 50).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con 'data' y 'meta' de paginación.
   */
  public function listEvents(Request $request): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('marketing_event');

    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status_event', 'published')
      ->condition('start_date', date('Y-m-d\TH:i:s'), '>=')
      ->sort('start_date', 'ASC')
      ->range($page * $limit, $limit);

    // Filtros
    $type = $request->query->get('type');
    if ($type) {
      $query->condition('event_type', $type);
    }

    $format = $request->query->get('format');
    if ($format) {
      $query->condition('format', $format);
    }

    // Contar total
    $count_query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status_event', 'published')
      ->condition('start_date', date('Y-m-d\TH:i:s'), '>=');

    if ($type) {
      $count_query->condition('event_type', $type);
    }
    if ($format) {
      $count_query->condition('format', $format);
    }

    $total = (int) $count_query->count()->execute();
    $ids = $query->execute();
    $events = !empty($ids) ? $storage->loadMultiple($ids) : [];

    $data = [];
    foreach ($events as $event) {
      $data[] = $this->serializeEvent($event);
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => (int) ceil($total / $limit),
      ],
    ]);
  }

  /**
   * Obtiene el detalle de un evento (GET /api/v1/events/{event_id}).
   *
   * @param int $event_id
   *   ID del evento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con los datos completos del evento.
   */
  public function getEvent(int $event_id): JsonResponse {
    $event = $this->entityTypeManager()->getStorage('marketing_event')->load($event_id);

    if (!$event) {
      return new JsonResponse([
        'errors' => [['status' => 404, 'title' => 'Not Found', 'detail' => 'Event not found.']],
      ], 404);
    }

    return new JsonResponse([
      'data' => $this->serializeEvent($event, TRUE),
    ]);
  }

  /**
   * Registra un asistente (POST /api/v1/events/{event_id}/register).
   *
   * LÓGICA:
   * Recibe los datos del asistente en el body JSON, valida los campos
   * requeridos y delega al EventRegistrationService. Retorna el registro
   * creado con el ticket code.
   *
   * @param int $event_id
   *   ID del evento.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con body JSON:
   *   - 'name' (string, requerido): Nombre del asistente.
   *   - 'email' (string, requerido): Email del asistente.
   *   - 'phone' (string, opcional): Teléfono.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el registro creado o errores.
   */
  public function registerAttendee(int $event_id, Request $request): JsonResponse {
    if (!$this->registrationService) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);

    if (empty($body)) {
      return new JsonResponse([
        'errors' => [['status' => 400, 'title' => 'Bad Request', 'detail' => 'Invalid JSON body.']],
      ], 400);
    }

    // Validar campos requeridos
    $errors = [];
    if (empty($body['name'])) {
      $errors[] = ['status' => 422, 'title' => 'Validation Error', 'detail' => 'Field "name" is required.', 'source' => ['pointer' => '/name']];
    }
    if (empty($body['email']) || !filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
      $errors[] = ['status' => 422, 'title' => 'Validation Error', 'detail' => 'A valid "email" is required.', 'source' => ['pointer' => '/email']];
    }

    if (!empty($errors)) {
      return new JsonResponse(['errors' => $errors], 422);
    }

    $attendee_data = [
      'name' => trim($body['name']),
      'email' => trim($body['email']),
      'phone' => trim($body['phone'] ?? ''),
      'source' => 'api',
      'utm_source' => $body['utm_source'] ?? '',
    ];

    try {
      $registration = $this->registrationService->register($event_id, $attendee_data);

      return new JsonResponse([
        'data' => [
          'id' => $registration->id(),
          'ticket_code' => $registration->get('ticket_code')->value,
          'registration_status' => $registration->get('registration_status')->value,
          'payment_status' => $registration->get('payment_status')->value,
          'attendee_name' => $registration->get('attendee_name')->value,
          'attendee_email' => $registration->get('attendee_email')->value,
        ],
      ], 201);
    }
    catch (EventNotOpenException $e) {
      return new JsonResponse([
        'errors' => [['status' => 400, 'title' => 'Event Not Open', 'detail' => $e->getMessage()]],
      ], 400);
    }
    catch (DuplicateRegistrationException $e) {
      return new JsonResponse([
        'errors' => [['status' => 409, 'title' => 'Duplicate Registration', 'detail' => $e->getMessage()]],
      ], 409);
    }
    catch (EventFullException $e) {
      return new JsonResponse([
        'errors' => [['status' => 409, 'title' => 'Event Full', 'detail' => $e->getMessage()]],
      ], 409);
    }
  }

  /**
   * Check-in de un asistente (PATCH /api/v1/events/{event_id}/registrations/{registration_id}/checkin).
   *
   * @param int $event_id
   *   ID del evento.
   * @param int $registration_id
   *   ID del registro.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el registro actualizado.
   */
  public function checkIn(int $event_id, int $registration_id): JsonResponse {
    if (!$this->registrationService) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    try {
      $registration = $this->registrationService->checkIn($registration_id);

      return new JsonResponse([
        'data' => [
          'id' => $registration->id(),
          'attendee_name' => $registration->get('attendee_name')->value,
          'registration_status' => $registration->get('registration_status')->value,
          'checked_in' => (bool) $registration->get('checked_in')->value,
          'checkin_time' => $registration->get('checkin_time')->value,
        ],
      ]);
    }
    catch (\RuntimeException $e) {
      return new JsonResponse([
        'errors' => [['status' => 404, 'title' => 'Not Found', 'detail' => $e->getMessage()]],
      ], 404);
    }
  }

  /**
   * Estadísticas de un evento (GET /api/v1/events/{event_id}/stats).
   *
   * @param int $event_id
   *   ID del evento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con métricas de rendimiento y funnel.
   */
  public function eventStats(int $event_id): JsonResponse {
    if (!$this->analyticsService) {
      return new JsonResponse([
        'errors' => [['status' => 503, 'title' => 'Service Unavailable']],
      ], 503);
    }

    $performance = $this->analyticsService->getEventPerformance($event_id);

    if (empty($performance)) {
      return new JsonResponse([
        'errors' => [['status' => 404, 'title' => 'Not Found', 'detail' => 'Event not found.']],
      ], 404);
    }

    $funnel = $this->analyticsService->getConversionFunnel($event_id);

    return new JsonResponse([
      'data' => [
        'performance' => $performance,
        'funnel' => $funnel,
      ],
    ]);
  }

  /**
   * Serializa una entidad MarketingEvent a array JSON.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento.
   * @param bool $full
   *   Si TRUE, incluye todos los campos. Si FALSE, solo datos de tarjeta.
   *
   * @return array
   *   Datos serializados del evento.
   */
  protected function serializeEvent($event, bool $full = FALSE): array {
    $data = [
      'id' => (int) $event->id(),
      'title' => $event->get('title')->value,
      'slug' => $event->get('slug')->value,
      'event_type' => $event->get('event_type')->value,
      'format' => $event->get('format')->value,
      'start_date' => $event->get('start_date')->value,
      'end_date' => $event->get('end_date')->value,
      'is_free' => (bool) $event->get('is_free')->value,
      'price' => (float) ($event->get('price')->value ?? 0),
      'spots_remaining' => $event->getSpotsRemaining(),
      'featured' => (bool) $event->get('featured')->value,
    ];

    if ($full) {
      $data += [
        'description' => $event->get('description')->value,
        'short_desc' => $event->get('short_desc')->value,
        'timezone' => $event->get('timezone')->value,
        'location' => $event->get('location')->value,
        'meeting_url' => $event->get('meeting_url')->uri ?? '',
        'speakers' => $event->get('speakers')->value,
        'max_attendees' => (int) $event->get('max_attendees')->value,
        'current_attendees' => (int) $event->get('current_attendees')->value,
        'early_bird_price' => (float) ($event->get('early_bird_price')->value ?? 0),
        'early_bird_deadline' => $event->get('early_bird_deadline')->value,
        'status' => $event->get('status_event')->value,
      ];
    }

    return $data;
  }

}
