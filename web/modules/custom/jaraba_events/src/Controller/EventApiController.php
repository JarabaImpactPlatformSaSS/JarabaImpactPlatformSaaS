<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_events\Exception\DuplicateRegistrationException;
use Drupal\jaraba_events\Exception\EventFullException;
use Drupal\jaraba_events\Exception\EventNotOpenException;
use Drupal\jaraba_events\Service\EventAnalyticsService;
use Drupal\jaraba_events\Service\EventCertificateService;
use Drupal\jaraba_events\Service\EventRegistrationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para eventos de marketing.
 *
 * ESTRUCTURA:
 * API JSON para operaciones CRUD sobre eventos, gestión de registros,
 * check-in de asistentes, estadísticas y generación de certificados.
 * Los endpoints siguen la convención /api/v1/events/* y requieren
 * autenticación. Las respuestas usan el formato estándar con claves
 * 'success', 'data' y 'error'.
 *
 * LÓGICA:
 * Todos los endpoints validan permisos mediante las anotaciones de routing.
 * Los datos se serializan a JSON con las claves mínimas necesarias.
 * Los errores devuelven códigos HTTP apropiados (400, 404, 409, 422, 503).
 * El tenant_id se obtiene del grupo activo del usuario actual.
 *
 * RELACIONES:
 * - EventApiController -> EntityTypeManager (consultas de entidad)
 * - EventApiController -> EventRegistrationService (registro, check-in)
 * - EventApiController -> EventAnalyticsService (métricas, stats)
 * - EventApiController -> EventCertificateService (certificados)
 * - EventApiController -> LoggerInterface (logging)
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
   * Servicio de certificados de eventos.
   *
   * @var \Drupal\jaraba_events\Service\EventCertificateService|null
   */
  protected ?EventCertificateService $certificateService = NULL;

  /**
   * Canal de log dedicado para el módulo de eventos.
   *
   * @var \Psr\Log\LoggerInterface|null
   */
  protected ?LoggerInterface $eventLogger = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->registrationService = $container->get('jaraba_events.registration');
    }
    catch (\Exception $e) {
      // Servicio puede no estar disponible todavía.
    }

    try {
      $instance->analyticsService = $container->get('jaraba_events.analytics');
    }
    catch (\Exception $e) {
      // Servicio puede no estar disponible todavía.
    }

    try {
      $instance->certificateService = $container->get('jaraba_events.certificate');
    }
    catch (\Exception $e) {
      // Servicio puede no estar disponible todavía.
    }

    try {
      $instance->eventLogger = $container->get('logger.channel.jaraba_events');
    }
    catch (\Exception $e) {
      // Canal de log puede no estar disponible todavía.
    }

    return $instance;
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   *
   * LÓGICA:
   * Intenta obtener el tenant_id del servicio de contexto de tenant.
   * Si no está disponible, intenta obtenerlo del primer grupo del usuario.
   * Devuelve NULL si no se puede determinar el tenant.
   *
   * @return int|null
   *   ID del tenant actual o NULL si no se puede determinar.
   */
  protected function getCurrentTenantId(): ?int {
    try {
      $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenant_id = $tenant_context->getCurrentTenantId();
      if ($tenant_id) {
        return (int) $tenant_id;
      }
    }
    catch (\Exception $e) {
      // Servicio de tenant no disponible.
    }
    return NULL;
  }

  /**
   * Lista los eventos del tenant actual (GET /api/v1/events).
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
   *   Respuesta JSON con {success, data, meta}.
   */
  public function listEvents(Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager()->getStorage('marketing_event');

      $page = max(0, (int) $request->query->get('page', 0));
      $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status_event', 'published')
        ->condition('start_date', date('Y-m-d\TH:i:s'), '>=')
        ->sort('start_date', 'ASC')
        ->range($page * $limit, $limit);

      // Filtro por tenant.
      $tenant_id = $this->getCurrentTenantId();
      if ($tenant_id) {
        $query->condition('tenant_id', $tenant_id);
      }

      // Filtros opcionales.
      $type = $request->query->get('type');
      if ($type) {
        $query->condition('event_type', $type);
      }

      $format = $request->query->get('format');
      if ($format) {
        $query->condition('format', $format);
      }

      // Contar total.
      $count_query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status_event', 'published')
        ->condition('start_date', date('Y-m-d\TH:i:s'), '>=');

      if ($tenant_id) {
        $count_query->condition('tenant_id', $tenant_id);
      }
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
        'success' => TRUE,
        'data' => $data,
        'meta' => [
          'total' => $total,
          'page' => $page,
          'limit' => $limit,
          'total_pages' => (int) ceil($total / $limit),
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => [],
        'error' => 'Error al listar eventos: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Obtiene el detalle de un evento (GET /api/v1/events/{id}).
   *
   * @param string $id
   *   ID del evento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con {success, data} o {success, error}.
   */
  public function getEvent(string $id): JsonResponse {
    $event = $this->entityTypeManager()->getStorage('marketing_event')->load($id);

    if (!$event) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Evento no encontrado.',
      ], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $this->serializeEvent($event, TRUE),
    ]);
  }

  /**
   * Crea un nuevo evento (POST /api/v1/events).
   *
   * LÓGICA:
   * Recibe los datos del evento en el body JSON, valida los campos
   * requeridos (title, event_type, start_date) y crea la entidad.
   * Asigna el tenant_id del usuario actual automáticamente.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con body JSON conteniendo los datos del evento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el evento creado o errores de validación.
   */
  public function createEvent(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);

    if (empty($body)) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Body JSON inválido.',
      ], 400);
    }

    // Validar campos requeridos.
    $errors = [];
    if (empty($body['title'])) {
      $errors[] = 'El campo "title" es obligatorio.';
    }
    if (empty($body['event_type'])) {
      $errors[] = 'El campo "event_type" es obligatorio.';
    }
    if (empty($body['start_date'])) {
      $errors[] = 'El campo "start_date" es obligatorio.';
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => implode(' ', $errors),
      ], 422);
    }

    try {
      $tenant_id = $this->getCurrentTenantId();
      $storage = $this->entityTypeManager()->getStorage('marketing_event');

      $values = [
        'title' => trim($body['title']),
        'event_type' => $body['event_type'],
        'format' => $body['format'] ?? 'online',
        'start_date' => $body['start_date'],
        'end_date' => $body['end_date'] ?? NULL,
        'description' => $body['description'] ?? '',
        'short_desc' => $body['short_desc'] ?? '',
        'max_attendees' => (int) ($body['max_attendees'] ?? 0),
        'is_free' => (bool) ($body['is_free'] ?? TRUE),
        'price' => $body['price'] ?? NULL,
        'status_event' => $body['status_event'] ?? 'draft',
        'uid' => $this->currentUser()->id(),
      ];

      if ($tenant_id) {
        $values['tenant_id'] = $tenant_id;
      }

      $event = $storage->create($values);
      $event->save();

      if ($this->eventLogger) {
        $this->eventLogger->info('Evento creado via API: @title (ID: @id)', [
          '@title' => $event->label(),
          '@id' => $event->id(),
        ]);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeEvent($event, TRUE),
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Error al crear evento: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Actualiza un evento existente (PUT /api/v1/events/{id}).
   *
   * LÓGICA:
   * Recibe los campos a actualizar en el body JSON. Solo se actualizan
   * los campos presentes en el body; los no incluidos mantienen su valor.
   *
   * @param string $id
   *   ID del evento a actualizar.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con body JSON conteniendo los campos a actualizar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el evento actualizado o errores.
   */
  public function updateEvent(string $id, Request $request): JsonResponse {
    $event = $this->entityTypeManager()->getStorage('marketing_event')->load($id);

    if (!$event) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Evento no encontrado.',
      ], 404);
    }

    $body = json_decode($request->getContent(), TRUE);

    if (empty($body)) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Body JSON inválido.',
      ], 400);
    }

    // Campos actualizables.
    $updatable_fields = [
      'title', 'event_type', 'format', 'start_date', 'end_date',
      'description', 'short_desc', 'max_attendees', 'is_free',
      'price', 'status_event', 'location', 'meeting_url', 'speakers',
      'featured', 'meta_description',
    ];

    try {
      foreach ($updatable_fields as $field) {
        if (array_key_exists($field, $body)) {
          $event->set($field, $body[$field]);
        }
      }

      $event->save();

      if ($this->eventLogger) {
        $this->eventLogger->info('Evento actualizado via API: @title (ID: @id)', [
          '@title' => $event->label(),
          '@id' => $event->id(),
        ]);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $this->serializeEvent($event, TRUE),
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Error al actualizar evento: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Elimina un evento (DELETE /api/v1/events/{id}).
   *
   * LÓGICA:
   * Elimina la entidad del evento. No elimina los registros asociados;
   * estos quedan huérfanos intencionalmente para mantener histórico.
   *
   * @param string $id
   *   ID del evento a eliminar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con confirmación o error.
   */
  public function deleteEvent(string $id): JsonResponse {
    $event = $this->entityTypeManager()->getStorage('marketing_event')->load($id);

    if (!$event) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Evento no encontrado.',
      ], 404);
    }

    try {
      $title = $event->label();
      $event->delete();

      if ($this->eventLogger) {
        $this->eventLogger->info('Evento eliminado via API: @title (ID: @id)', [
          '@title' => $title,
          '@id' => $id,
        ]);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['deleted' => TRUE, 'id' => (int) $id],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Error al eliminar evento: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Lista los registros de un evento (GET /api/v1/events/{id}/registrations).
   *
   * LÓGICA:
   * Retorna los registros del evento con paginación y filtros opcionales
   * delegando al EventRegistrationService.
   *
   * @param string $id
   *   ID del evento.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con parámetros opcionales:
   *   - 'status' (string): Filtrar por registration_status.
   *   - 'page' (int): Número de página.
   *   - 'limit' (int): Elementos por página.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con {success, data, meta}.
   */
  public function listRegistrations(string $id, Request $request): JsonResponse {
    if (!$this->registrationService) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => [],
        'error' => 'Servicio de registro no disponible.',
      ], 503);
    }

    // Verificar que el evento existe.
    $event = $this->entityTypeManager()->getStorage('marketing_event')->load($id);
    if (!$event) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => [],
        'error' => 'Evento no encontrado.',
      ], 404);
    }

    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $filters = [];

    $status = $request->query->get('status');
    if ($status) {
      $filters['status'] = $status;
    }

    $result = $this->registrationService->getRegistrations(
      (int) $id,
      $filters,
      $limit,
      $page * $limit
    );

    $data = [];
    foreach ($result['registrations'] as $registration) {
      $data[] = [
        'id' => (int) $registration->id(),
        'attendee_name' => $registration->get('attendee_name')->value,
        'attendee_email' => $registration->get('attendee_email')->value,
        'registration_status' => $registration->get('registration_status')->value,
        'ticket_code' => $registration->get('ticket_code')->value,
        'checked_in' => (bool) $registration->get('checked_in')->value,
        'created' => $registration->get('created')->value,
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => $result['total'],
        'page' => $page,
        'limit' => $limit,
      ],
    ]);
  }

  /**
   * Registra un asistente en un evento (POST /api/v1/events/{id}/register).
   *
   * LÓGICA:
   * Recibe los datos del asistente en el body JSON, valida los campos
   * requeridos (name, email) y delega al EventRegistrationService.
   *
   * @param string $id
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
  public function registerForEvent(string $id, Request $request): JsonResponse {
    if (!$this->registrationService) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Servicio de registro no disponible.',
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);

    if (empty($body)) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Body JSON inválido.',
      ], 400);
    }

    // Validar campos requeridos.
    $errors = [];
    if (empty($body['name'])) {
      $errors[] = 'El campo "name" es obligatorio.';
    }
    if (empty($body['email']) || !filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Se requiere un "email" válido.';
    }

    if (!empty($errors)) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => implode(' ', $errors),
      ], 422);
    }

    $attendee_data = [
      'name' => trim($body['name']),
      'email' => trim($body['email']),
      'phone' => trim($body['phone'] ?? ''),
      'source' => 'api',
      'utm_source' => $body['utm_source'] ?? '',
    ];

    try {
      $registration = $this->registrationService->register((int) $id, $attendee_data);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $registration->id(),
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
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 400);
    }
    catch (DuplicateRegistrationException $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 409);
    }
    catch (EventFullException $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 409);
    }
  }

  /**
   * Check-in de un asistente (POST /api/v1/events/{id}/check-in).
   *
   * LÓGICA:
   * Recibe el registration_id o ticket_code en el body JSON y
   * marca al asistente como presente en el evento.
   *
   * @param string $id
   *   ID del evento.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con body JSON:
   *   - 'registration_id' (int): ID del registro a hacer check-in.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el registro actualizado o errores.
   */
  public function checkIn(string $id, Request $request): JsonResponse {
    if (!$this->registrationService) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Servicio de registro no disponible.',
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);

    if (empty($body) || empty($body['registration_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'El campo "registration_id" es obligatorio.',
      ], 400);
    }

    try {
      $registration = $this->registrationService->checkIn((int) $body['registration_id']);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $registration->id(),
          'attendee_name' => $registration->get('attendee_name')->value,
          'registration_status' => $registration->get('registration_status')->value,
          'checked_in' => (bool) $registration->get('checked_in')->value,
          'checkin_time' => $registration->get('checkin_time')->value,
        ],
      ]);
    }
    catch (\RuntimeException $e) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $e->getMessage(),
      ], 404);
    }
  }

  /**
   * Estadísticas de un evento (GET /api/v1/events/{id}/stats).
   *
   * LÓGICA:
   * Obtiene métricas de rendimiento y funnel de conversión del evento
   * delegando al EventAnalyticsService.
   *
   * @param string $id
   *   ID del evento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con {success, data} conteniendo performance y funnel.
   */
  public function getEventStats(string $id): JsonResponse {
    if (!$this->analyticsService) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Servicio de analítica no disponible.',
      ], 503);
    }

    $performance = $this->analyticsService->getEventPerformance((int) $id);

    if (empty($performance)) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Evento no encontrado.',
      ], 404);
    }

    $funnel = $this->analyticsService->getConversionFunnel((int) $id);

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'performance' => $performance,
        'funnel' => $funnel,
      ],
    ]);
  }

  /**
   * Genera un certificado para un registro (POST /api/v1/events/{id}/certificate).
   *
   * LÓGICA:
   * Recibe el registration_id en el body JSON y genera un certificado
   * de asistencia delegando al EventCertificateService.
   *
   * @param string $id
   *   ID del evento.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Solicitud HTTP con body JSON:
   *   - 'registration_id' (int, requerido): ID del registro.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con el certificado generado o errores.
   */
  public function generateCertificate(string $id, Request $request): JsonResponse {
    if (!$this->certificateService) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'Servicio de certificados no disponible.',
      ], 503);
    }

    $body = json_decode($request->getContent(), TRUE);

    if (empty($body) || empty($body['registration_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => 'El campo "registration_id" es obligatorio.',
      ], 400);
    }

    $result = $this->certificateService->generateCertificate((int) $body['registration_id']);

    if (!$result['success']) {
      return new JsonResponse([
        'success' => FALSE,
        'data' => NULL,
        'error' => $result['error'] ?? 'Error al generar certificado.',
      ], 400);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'certificate_url' => $result['certificate_url'],
        'certificate_data' => $result['certificate_data'],
      ],
    ], 201);
  }

  /**
   * Serializa una entidad MarketingEvent a array JSON.
   *
   * @param mixed $event
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
