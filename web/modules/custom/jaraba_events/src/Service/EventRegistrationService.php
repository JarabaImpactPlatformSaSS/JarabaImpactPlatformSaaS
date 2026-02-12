<?php

namespace Drupal\jaraba_events\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_email\Service\SequenceManagerService;
use Drupal\jaraba_events\Entity\EventRegistration;
use Drupal\jaraba_events\Entity\MarketingEvent;
use Drupal\jaraba_events\Exception\DuplicateRegistrationException;
use Drupal\jaraba_events\Exception\EventFullException;
use Drupal\jaraba_events\Exception\EventNotOpenException;
use Psr\Log\LoggerInterface;

/**
 * Servicio de registro de asistentes a eventos de marketing.
 *
 * ESTRUCTURA:
 * Servicio central que orquesta el flujo completo de registro, confirmación,
 * cancelación, check-in y gestión de lista de espera para eventos de marketing.
 * Depende de EntityTypeManager para CRUD de entidades, TenantContextService
 * para aislamiento multi-tenant, y del canal de log dedicado.
 *
 * LÓGICA:
 * El flujo de registro sigue estas reglas de negocio:
 * 1. Un usuario solo puede registrarse UNA vez por evento (unicidad email+evento).
 * 2. Si el evento tiene aforo y está lleno, el registro pasa a lista de espera.
 * 3. Eventos gratuitos se confirman automáticamente; de pago quedan pendientes.
 * 4. Double opt-in: se genera un token de confirmación de 64 caracteres.
 * 5. Al cancelar, se promueve al primer usuario de la lista de espera.
 * 6. El check-in marca la asistencia real y registra la hora.
 *
 * RELACIONES:
 * - EventRegistrationService -> EntityTypeManager (dependencia)
 * - EventRegistrationService -> TenantContextService (dependencia)
 * - EventRegistrationService <- EventFrontendController (consumido por)
 * - EventRegistrationService <- EventApiController (consumido por)
 *
 * @package Drupal\jaraba_events\Service
 */
class EventRegistrationService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Proxy del usuario actual.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Servicio de contexto de tenant para aislamiento multi-tenant.
   *
   * @var object
   */
  protected $tenantContext;

  /**
   * Canal de log dedicado para el módulo de eventos.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Servicio de secuencias de email para notificaciones pre/post evento.
   *
   * @var \Drupal\jaraba_email\Service\SequenceManagerService|null
   */
  protected ?SequenceManagerService $sequenceManager;

  /**
   * Constructor del servicio de registro de eventos.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Proxy del usuario actual para asignación de propietario.
   * @param object $tenant_context
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones del módulo.
   * @param \Drupal\jaraba_email\Service\SequenceManagerService|null $sequence_manager
   *   Servicio de secuencias de email para notificaciones automáticas.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    $tenant_context,
    LoggerInterface $logger,
    ?SequenceManagerService $sequence_manager = NULL,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->tenantContext = $tenant_context;
    $this->logger = $logger;
    $this->sequenceManager = $sequence_manager;
  }

  /**
   * Registra un asistente en un evento de marketing.
   *
   * FLUJO DE EJECUCIÓN:
   * 1. Carga el evento y valida que existe y está publicado
   * 2. Verifica unicidad (mismo email no puede registrarse dos veces)
   * 3. Determina si hay capacidad o va a lista de espera
   * 4. Genera token de confirmación (64 chars hex) y código de ticket
   * 5. Crea la entidad event_registration con el estado apropiado
   * 6. Incrementa el contador de asistentes del evento
   * 7. Registra la operación en el log
   *
   * REGLAS DE NEGOCIO:
   * - Eventos gratuitos: registration_status = 'confirmed', payment_status = 'free'
   * - Eventos de pago: registration_status = 'pending', payment_status = 'pending_payment'
   * - Aforo lleno: registration_status = 'waitlisted' (sin incrementar contador)
   * - Double opt-in: token generado con random_bytes(32) para seguridad criptográfica
   *
   * @param int $event_id
   *   ID del marketing_event al que se registra.
   * @param array $attendee_data
   *   Datos del asistente con las siguientes claves:
   *   - 'name' (string): Nombre completo del asistente.
   *   - 'email' (string): Email de contacto (único por evento).
   *   - 'phone' (string, opcional): Teléfono de contacto.
   *   - 'utm_source' (string, opcional): Fuente de atribución UTM.
   *
   * @return \Drupal\jaraba_events\Entity\EventRegistration
   *   La entidad de registro creada con todos los campos rellenos.
   *
   * @throws \Drupal\jaraba_events\Exception\EventNotOpenException
   *   Si el evento no está en estado 'published'.
   * @throws \Drupal\jaraba_events\Exception\DuplicateRegistrationException
   *   Si el email ya está registrado en el mismo evento.
   * @throws \Drupal\jaraba_events\Exception\EventFullException
   *   Si el evento ha alcanzado el aforo máximo y no acepta lista de espera.
   */
  public function register(int $event_id, array $attendee_data): EventRegistration {
    $event_storage = $this->entityTypeManager->getStorage('marketing_event');

    /** @var \Drupal\jaraba_events\Entity\MarketingEvent|null $event */
    $event = $event_storage->load($event_id);

    if (!$event) {
      throw new EventNotOpenException('El evento solicitado no existe.');
    }

    // Verificar que el evento está publicado y abierto a registros
    if ($event->get('status_event')->value !== 'published') {
      throw new EventNotOpenException(
        sprintf('El evento "%s" no está abierto a registros (estado: %s).',
          $event->label(),
          $event->get('status_event')->value
        )
      );
    }

    // Verificar duplicados: mismo email en el mismo evento
    $email = $attendee_data['email'] ?? '';
    $duplicates = $this->entityTypeManager->getStorage('event_registration')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('event_id', $event_id)
      ->condition('attendee_email', $email)
      ->condition('registration_status', ['cancelled'], 'NOT IN')
      ->count()
      ->execute();

    if ($duplicates > 0) {
      throw new DuplicateRegistrationException(
        sprintf('El email %s ya está registrado en el evento "%s".',
          $email,
          $event->label()
        )
      );
    }

    // Determinar estado según capacidad
    $max_attendees = (int) $event->get('max_attendees')->value;
    $current_attendees = (int) $event->get('current_attendees')->value;
    $is_waitlisted = ($max_attendees > 0 && $current_attendees >= $max_attendees);

    // Determinar estado según gratuidad y capacidad
    $is_free = (bool) $event->get('is_free')->value;

    if ($is_waitlisted) {
      $registration_status = 'waitlisted';
      $payment_status = $is_free ? 'free' : 'pending_payment';
    }
    elseif ($is_free) {
      $registration_status = 'confirmed';
      $payment_status = 'free';
    }
    else {
      $registration_status = 'pending';
      $payment_status = 'pending_payment';
    }

    // Generar token de confirmación y código de ticket
    $confirmation_token = bin2hex(random_bytes(32));
    $ticket_code = sprintf('EVT-%d-%s', $event_id, strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)));

    // Obtener tenant_id del evento padre
    $tenant_id = $event->get('tenant_id')->target_id;

    // Crear la entidad de registro
    /** @var \Drupal\jaraba_events\Entity\EventRegistration $registration */
    $registration = $this->entityTypeManager->getStorage('event_registration')->create([
      'event_id' => $event_id,
      'tenant_id' => $tenant_id,
      'attendee_name' => $attendee_data['name'] ?? '',
      'attendee_email' => $email,
      'attendee_phone' => $attendee_data['phone'] ?? '',
      'registration_status' => $registration_status,
      'confirmation_token' => $confirmation_token,
      'ticket_code' => $ticket_code,
      'payment_status' => $payment_status,
      'source' => $attendee_data['source'] ?? 'web',
      'utm_source' => $attendee_data['utm_source'] ?? '',
      'uid' => $this->currentUser->id(),
    ]);

    $registration->save();

    // Incrementar contador de asistentes (solo si no es lista de espera)
    if (!$is_waitlisted) {
      $event->incrementAttendees();
      $event->save();
    }

    $this->logger->info('Registro creado: @name (@email) para evento @event [estado: @status, ticket: @ticket]', [
      '@name' => $attendee_data['name'] ?? 'N/A',
      '@email' => $email,
      '@event' => $event->label(),
      '@status' => $registration_status,
      '@ticket' => $ticket_code,
    ]);

    // Inscribir en secuencia de email post-registro si hay secuencia configurada.
    $this->enrollInEventEmailSequence($event, $registration);

    return $registration;
  }

  /**
   * Confirma un registro mediante token de double opt-in.
   *
   * LÓGICA:
   * Busca el registro por token, valida que no esté ya confirmado o cancelado,
   * y cambia su estado a 'confirmed'. Si el registro estaba en estado
   * 'pending', actualiza el estado. Si estaba en 'waitlisted', solo confirma
   * el opt-in pero mantiene en espera.
   *
   * @param string $token
   *   Token de confirmación de 64 caracteres hexadecimales.
   *
   * @return \Drupal\jaraba_events\Entity\EventRegistration
   *   El registro actualizado.
   *
   * @throws \RuntimeException
   *   Si el token no corresponde a ningún registro.
   */
  public function confirmByToken(string $token): EventRegistration {
    $registrations = $this->entityTypeManager->getStorage('event_registration')
      ->loadByProperties(['confirmation_token' => $token]);

    if (empty($registrations)) {
      throw new \RuntimeException('Token de confirmación no válido o expirado.');
    }

    /** @var \Drupal\jaraba_events\Entity\EventRegistration $registration */
    $registration = reset($registrations);

    $current_status = $registration->get('registration_status')->value;

    // Solo confirmar si está pendiente (no si ya está confirmado o cancelado)
    if ($current_status === 'pending') {
      $registration->set('registration_status', 'confirmed');
      $registration->save();

      $this->logger->info('Registro confirmado por token: @name para evento #@event', [
        '@name' => $registration->get('attendee_name')->value,
        '@event' => $registration->get('event_id')->target_id,
      ]);
    }

    return $registration;
  }

  /**
   * Cancela un registro y promueve al primer usuario de lista de espera.
   *
   * FLUJO DE EJECUCIÓN:
   * 1. Carga el registro y valida que puede cancelarse
   * 2. Cambia estado a 'cancelled'
   * 3. Decrementa contador de asistentes del evento
   * 4. Busca el primer registro en 'waitlisted' (ordenado por created ASC)
   * 5. Si existe, lo promueve a 'confirmed' e incrementa contador
   *
   * @param int $registration_id
   *   ID del registro a cancelar.
   * @param string $reason
   *   Motivo de la cancelación (opcional, se registra en log).
   */
  public function cancel(int $registration_id, string $reason = ''): void {
    /** @var \Drupal\jaraba_events\Entity\EventRegistration|null $registration */
    $registration = $this->entityTypeManager->getStorage('event_registration')->load($registration_id);

    if (!$registration) {
      return;
    }

    $current_status = $registration->get('registration_status')->value;
    $was_active = in_array($current_status, ['pending', 'confirmed']);

    // Cambiar estado a cancelado
    $registration->set('registration_status', 'cancelled');
    $registration->save();

    // Decrementar contador del evento si estaba activo (no en lista de espera)
    if ($was_active) {
      $event_id = $registration->get('event_id')->target_id;
      /** @var \Drupal\jaraba_events\Entity\MarketingEvent|null $event */
      $event = $this->entityTypeManager->getStorage('marketing_event')->load($event_id);

      if ($event) {
        $event->decrementAttendees();
        $event->save();

        // Promover al primer usuario de lista de espera
        $this->promoteFromWaitlist($event_id, $event);
      }
    }

    $this->logger->info('Registro #@id cancelado. Motivo: @reason', [
      '@id' => $registration_id,
      '@reason' => $reason ?: 'No especificado',
    ]);
  }

  /**
   * Registra el check-in de un asistente.
   *
   * LÓGICA:
   * Solo se puede hacer check-in a registros confirmados.
   * Registra la hora exacta del check-in y cambia el estado a 'attended'.
   *
   * @param int $registration_id
   *   ID del registro a hacer check-in.
   *
   * @return \Drupal\jaraba_events\Entity\EventRegistration
   *   El registro actualizado con check-in.
   */
  public function checkIn(int $registration_id): EventRegistration {
    /** @var \Drupal\jaraba_events\Entity\EventRegistration $registration */
    $registration = $this->entityTypeManager->getStorage('event_registration')->load($registration_id);

    if (!$registration) {
      throw new \RuntimeException('Registro no encontrado.');
    }

    $registration->set('checked_in', TRUE);
    $registration->set('checkin_time', date('Y-m-d\TH:i:s'));
    $registration->markAsAttended();
    $registration->save();

    $this->logger->info('Check-in realizado: @name (registro #@id)', [
      '@name' => $registration->get('attendee_name')->value,
      '@id' => $registration_id,
    ]);

    return $registration;
  }

  /**
   * Obtiene los registros de un evento con filtros opcionales.
   *
   * @param int $event_id
   *   ID del evento.
   * @param array $filters
   *   Filtros opcionales:
   *   - 'status' (string): Filtrar por registration_status.
   *   - 'search' (string): Búsqueda por nombre o email.
   * @param int $limit
   *   Número máximo de resultados (por defecto 50).
   * @param int $offset
   *   Desplazamiento para paginación (por defecto 0).
   *
   * @return array
   *   Array con claves:
   *   - 'registrations' (array): Entidades EventRegistration.
   *   - 'total' (int): Total sin paginación.
   */
  public function getRegistrations(int $event_id, array $filters = [], int $limit = 50, int $offset = 0): array {
    $storage = $this->entityTypeManager->getStorage('event_registration');

    // Consulta para contar total
    $count_query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('event_id', $event_id);

    // Consulta para resultados paginados
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('event_id', $event_id)
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    // Aplicar filtro de estado
    if (!empty($filters['status'])) {
      $query->condition('registration_status', $filters['status']);
      $count_query->condition('registration_status', $filters['status']);
    }

    $total = (int) $count_query->count()->execute();
    $ids = $query->execute();
    $registrations = !empty($ids) ? $storage->loadMultiple($ids) : [];

    return [
      'registrations' => array_values($registrations),
      'total' => $total,
    ];
  }

  /**
   * Genera estadísticas completas de un evento.
   *
   * LÓGICA:
   * Consulta todos los registros del evento y calcula métricas agregadas:
   * total de registros, confirmados, lista de espera, asistidos, no-shows,
   * tasa de asistencia, valoración media e ingresos totales.
   *
   * @param int $event_id
   *   ID del evento a analizar.
   *
   * @return array
   *   Estructura de métricas:
   *   - 'total_registrations' (int): Total de registros no cancelados.
   *   - 'confirmed' (int): Registros confirmados.
   *   - 'waitlisted' (int): En lista de espera.
   *   - 'attended' (int): Asistieron realmente.
   *   - 'no_show' (int): No asistieron.
   *   - 'cancelled' (int): Cancelados.
   *   - 'attendance_rate' (float): Porcentaje de asistencia real.
   *   - 'average_rating' (float): Valoración media (1-5).
   *   - 'revenue' (float): Ingresos totales del evento en EUR.
   */
  public function getEventStats(int $event_id): array {
    $storage = $this->entityTypeManager->getStorage('event_registration');

    $all_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('event_id', $event_id)
      ->execute();

    $registrations = !empty($all_ids) ? $storage->loadMultiple($all_ids) : [];

    $stats = [
      'total_registrations' => 0,
      'confirmed' => 0,
      'waitlisted' => 0,
      'attended' => 0,
      'no_show' => 0,
      'cancelled' => 0,
      'attendance_rate' => 0.0,
      'average_rating' => 0.0,
      'revenue' => 0.0,
    ];

    $ratings = [];

    /** @var \Drupal\jaraba_events\Entity\EventRegistration $reg */
    foreach ($registrations as $reg) {
      $status = $reg->get('registration_status')->value;
      if ($status !== 'cancelled') {
        $stats['total_registrations']++;
      }

      // Contar por estado
      switch ($status) {
        case 'confirmed':
          $stats['confirmed']++;
          break;

        case 'waitlisted':
          $stats['waitlisted']++;
          break;

        case 'attended':
          $stats['attended']++;
          break;

        case 'no_show':
          $stats['no_show']++;
          break;

        case 'cancelled':
          $stats['cancelled']++;
          break;
      }

      // Sumar ingresos (solo pagados)
      if ($reg->get('payment_status')->value === 'paid') {
        $stats['revenue'] += (float) ($reg->get('amount_paid')->value ?? 0);
      }

      // Recoger ratings para media
      $rating = (int) ($reg->get('rating')->value ?? 0);
      if ($rating > 0) {
        $ratings[] = $rating;
      }
    }

    // Calcular tasa de asistencia
    // Base: confirmados + attended + no_show (los que debían asistir)
    $expected = $stats['confirmed'] + $stats['attended'] + $stats['no_show'];
    if ($expected > 0) {
      $stats['attendance_rate'] = round(($stats['attended'] / $expected) * 100, 1);
    }

    // Calcular valoración media
    if (!empty($ratings)) {
      $stats['average_rating'] = round(array_sum($ratings) / count($ratings), 1);
    }

    return $stats;
  }

  /**
   * Promueve al primer usuario de la lista de espera a confirmado.
   *
   * LÓGICA INTERNA:
   * Busca el registro más antiguo (created ASC) con estado 'waitlisted'
   * para el evento dado. Si existe, lo promueve a 'confirmed' e
   * incrementa el contador del evento.
   *
   * @param int $event_id
   *   ID del evento.
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento para actualizar el contador.
   */
  protected function promoteFromWaitlist(int $event_id, MarketingEvent $event): void {
    $storage = $this->entityTypeManager->getStorage('event_registration');

    $waitlisted_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('event_id', $event_id)
      ->condition('registration_status', 'waitlisted')
      ->sort('created', 'ASC')
      ->range(0, 1)
      ->execute();

    if (!empty($waitlisted_ids)) {
      $first_id = reset($waitlisted_ids);
      /** @var \Drupal\jaraba_events\Entity\EventRegistration $waitlisted */
      $waitlisted = $storage->load($first_id);

      if ($waitlisted) {
        $waitlisted->set('registration_status', 'confirmed');
        $waitlisted->save();

        $event->incrementAttendees();
        $event->save();

        $this->logger->info('Promovido de lista de espera: @name para evento @event', [
          '@name' => $waitlisted->get('attendee_name')->value,
          '@event' => $event->label(),
        ]);
      }
    }
  }

  /**
   * Inscribe al asistente en la secuencia de email del evento.
   *
   * LÓGICA: Si el evento tiene una secuencia de email configurada
   *   (campo email_sequence_id), inscribe al suscriptor correspondiente
   *   al email del asistente en esa secuencia. Silencioso si no hay
   *   secuencia configurada o si el servicio de email no está disponible.
   *
   * @param \Drupal\jaraba_events\Entity\MarketingEvent $event
   *   Entidad del evento con posible secuencia de email.
   * @param \Drupal\jaraba_events\Entity\EventRegistration $registration
   *   Entidad de registro con datos del asistente.
   */
  protected function enrollInEventEmailSequence(MarketingEvent $event, EventRegistration $registration): void {
    if (!$this->sequenceManager) {
      return;
    }

    try {
      // Verificar si el evento tiene secuencia de email configurada.
      if (!$event->hasField('email_sequence_id') || $event->get('email_sequence_id')->isEmpty()) {
        return;
      }

      $sequenceId = (int) $event->get('email_sequence_id')->target_id;
      if ($sequenceId <= 0) {
        return;
      }

      // Buscar suscriptor por email del asistente.
      $email = $registration->get('attendee_email')->value;
      if (!$email) {
        return;
      }

      $subscriberStorage = $this->entityTypeManager->getStorage('email_subscriber');
      $subscribers = $subscriberStorage->loadByProperties(['email' => $email]);

      if (!empty($subscribers)) {
        $subscriber = reset($subscribers);
        $this->sequenceManager->enrollSubscriber((int) $subscriber->id(), $sequenceId);

        $this->logger->info('Asistente @email inscrito en secuencia de email #@seq para evento @event', [
          '@email' => $email,
          '@seq' => $sequenceId,
          '@event' => $event->label(),
        ]);
      }
    }
    catch (\Exception $e) {
      // No bloquear el registro por errores de email.
      $this->logger->warning('Error inscribiendo asistente en secuencia de email: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
