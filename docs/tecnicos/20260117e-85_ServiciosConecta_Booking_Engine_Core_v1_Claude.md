BOOKING ENGINE CORE
Motor de Reservas Inteligente para Servicios Profesionales
Vertical ServiciosConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	85_ServiciosConecta_Booking_Engine_Core
Dependencias:	82_Services_Core, 86_Calendar_Sync, 87_Payment_Booking
Prioridad:	CRÍTICA - Componente diferenciador principal
 
1. Resumen Ejecutivo
El Booking Engine es el componente central y diferenciador de ServiciosConecta. A diferencia de soluciones genéricas como Calendly o Cal.com, este motor está integrado nativamente con el ecosistema Jaraba: pagos anticipados vía Stripe Connect, sincronización bidireccional con calendarios externos, videollamadas embebidas, y automatización completa del ciclo de vida de la cita.
El objetivo principal es reducir los "no-shows" (actualmente ~15% en profesionales liberales) mediante pago anticipado obligatorio/opcional y recordatorios multicanal inteligentes, mientras se mantiene una experiencia de usuario fluida con Time-to-Value < 45 segundos para completar una reserva.
1.1 Objetivos del Sistema
•	Reducción de no-shows: Del 15% al <3% mediante pago anticipado y recordatorios inteligentes
•	Time-to-Value: Reserva completada en <45 segundos desde selección de slot
•	Sincronización perfecta: Cero conflictos entre agenda interna y calendarios externos
•	Flexibilidad modal: Soporte nativo para citas presenciales, online y a domicilio
•	Automatización completa: Confirmaciones, recordatorios, facturas y solicitud de reviews sin intervención
•	Multi-tenant ready: Cada marketplace puede configurar políticas propias de reserva y cancelación
1.2 Diferenciadores vs. Competencia
Característica	Calendly	Cal.com	Jaraba Booking
Pagos integrados	Stripe básico	Stripe básico	Stripe Connect + Split
Pago anticipado flexible	Todo o nada	Todo o nada	% configurable (señal)
Videollamada nativa	Integraciones externas	Cal Video (básico)	Jitsi embebido + grabación
Multi-tenant/Marketplace	No	Teams (limitado)	Full multi-tenant
Comisiones automáticas	No	No	Destination Charges
Buzón documentos	No	No	Cifrado AES-256
Firma digital	No	No	AutoFirma/eIDAS
IA Triaje	No	No	Clasificación automática
Schema.org nativo	No	Básico	ProfessionalService completo

 
2. Arquitectura del Booking Engine
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────────┐
│                        BOOKING ENGINE CORE                              │
├─────────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │ Availability │    │   Booking    │    │   Payment    │              │
│  │   Service    │───▶│   Service    │───▶│   Service    │              │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘              │
│         │                   │                   │                      │
│         ▼                   ▼                   ▼                      │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │   Calendar   │    │ Notification │    │    Stripe    │              │
│  │    Sync      │    │   Service    │    │   Connect    │              │
│  └──────┬───────┘    └──────┬───────┘    └──────────────┘              │
│         │                   │                                          │
│         ▼                   ▼                                          │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │Google Calendar│    │   Twilio     │    │  WhatsApp    │              │
│  │   Outlook    │    │   SMS        │    │  Business    │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Flujo Principal de Reserva
El flujo de reserva optimizado para TTV < 45 segundos:
1.	Cliente selecciona profesional y servicio (ya autenticado o guest checkout)
2.	Sistema calcula slots disponibles (cruzando agenda interna + calendarios externos)
3.	Cliente selecciona fecha/hora preferida
4.	Sistema bloquea slot temporalmente (5 minutos TTL) para evitar doble reserva
5.	Cliente completa datos y realiza pago (Stripe Payment Element)
6.	Sistema confirma pago → crea booking → sincroniza calendarios → envía confirmaciones
7.	Se programan recordatorios automáticos (24h, 2h antes)
 
3. Motor de Disponibilidad
El AvailabilityEngine es responsable de calcular los slots disponibles en tiempo real, considerando múltiples fuentes de datos y restricciones configurables.
3.1 Fuentes de Disponibilidad (Layered)
Capa	Fuente	Descripción
1	availability_slot	Horarios recurrentes configurados por el profesional (Lun-Vie 9:00-14:00, etc.)
2	availability_exception	Excepciones puntuales: vacaciones, festivos, disponibilidad extra
3	booking (existentes)	Citas ya reservadas que bloquean esos slots
4	Google Calendar	Eventos externos sincronizados (reuniones, compromisos personales)
5	Outlook Calendar	Eventos externos de Microsoft 365
6	temporary_hold	Slots bloqueados temporalmente durante checkout (TTL 5 min)

3.2 Algoritmo de Cálculo de Slots
<?php namespace Drupal\jaraba_booking\Engine;

class AvailabilityEngine {
  
  public function getAvailableSlots(
    int $providerId,
    int $serviceId,
    DateTime $from,
    DateTime $to
  ): array {
    $service = $this->serviceRepository->find($serviceId);
    $duration = $service->getDurationMins();
    $buffer = $this->getProviderBuffer($providerId);
    
    // 1. Obtener horario base recurrente
    $baseSlots = $this->getRecurringSlots($providerId, $from, $to);
    
    // 2. Aplicar excepciones (bloqueos y disponibilidad extra)
    $withExceptions = $this->applyExceptions($providerId, $baseSlots, $from, $to);
    
    // 3. Restar bookings existentes
    $afterBookings = $this->subtractBookings($providerId, $withExceptions, $from, $to);
    
    // 4. Restar eventos de calendarios externos
    $afterExternal = $this->subtractExternalEvents($providerId, $afterBookings, $from, $to);
    
    // 5. Restar holds temporales
    $afterHolds = $this->subtractTemporaryHolds($providerId, $afterExternal);
    
    // 6. Dividir en slots del tamaño del servicio + buffer
    $slots = $this->splitIntoSlots($afterHolds, $duration, $buffer);
    
    // 7. Filtrar por tiempo mínimo de antelación
    $minNotice = $this->getMinNoticeHours($providerId);
    $filtered = $this->filterByMinNotice($slots, $minNotice);
    
    // 8. Filtrar por antelación máxima
    $maxAdvance = $this->getMaxAdvanceDays($providerId);
    return $this->filterByMaxAdvance($filtered, $maxAdvance);
  }
}

3.3 Parámetros Configurables por Profesional
Parámetro	Tipo	Default	Descripción
booking_buffer_mins	INT	15	Minutos entre citas consecutivas
advance_booking_days	INT	60	Días máximos de antelación
min_notice_hours	INT	24	Horas mínimas de aviso previo
slot_increment_mins	INT	30	Incremento de slots (15/30/60)
allow_same_day	BOOL	FALSE	Permitir reservas para hoy
max_daily_bookings	INT	NULL	Límite de citas por día (NULL=sin límite)
require_confirmation	BOOL	FALSE	Requiere confirmación manual del profesional

 
4. Sistema de Bloqueo Temporal (Hold)
Para evitar dobles reservas durante el proceso de checkout, se implementa un sistema de "holds" temporales con TTL configurable.
4.1 Entidad: temporary_hold
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
provider_id	INT	Profesional	FK provider_profile.id, NOT NULL, INDEX
service_id	INT	Servicio	FK service_offering.id, NOT NULL
client_id	INT	Cliente que holdea	FK users.uid, NULLABLE (guest)
session_id	VARCHAR(64)	Session ID (guest)	NOT NULL, INDEX
start_datetime	DATETIME	Inicio del slot	NOT NULL, INDEX
end_datetime	DATETIME	Fin del slot	NOT NULL
expires_at	DATETIME	Expiración del hold	NOT NULL, INDEX (TTL)
status	VARCHAR(16)	Estado	ENUM: active|converted|expired|released
created	DATETIME	Fecha creación	NOT NULL

4.2 TemporaryHoldService
<?php namespace Drupal\jaraba_booking\Service;

class TemporaryHoldService {
  
  private const DEFAULT_TTL_MINUTES = 5;
  
  public function createHold(
    int $providerId,
    int $serviceId,
    DateTime $start,
    DateTime $end,
    ?int $clientId,
    string $sessionId
  ): TemporaryHold {
    // Verificar que el slot sigue disponible
    if (!$this->availabilityEngine->isSlotAvailable($providerId, $start, $end)) {
      throw new SlotNotAvailableException('Slot ya no está disponible');
    }
    
    // Verificar que el cliente no tiene otro hold activo para este provider
    $this->releaseExistingHolds($providerId, $clientId, $sessionId);
    
    $ttl = $this->config->get('hold_ttl_minutes') ?? self::DEFAULT_TTL_MINUTES;
    $expiresAt = (new DateTime())->modify("+{$ttl} minutes");
    
    return $this->repository->create([
      'provider_id' => $providerId,
      'service_id' => $serviceId,
      'client_id' => $clientId,
      'session_id' => $sessionId,
      'start_datetime' => $start,
      'end_datetime' => $end,
      'expires_at' => $expiresAt,
      'status' => 'active',
    ]);
  }
  
  public function convertToBooking(int $holdId, Booking $booking): void {
    $hold = $this->repository->find($holdId);
    $hold->setStatus('converted');
    $hold->setBookingId($booking->id());
    $this->repository->save($hold);
  }
  
  public function cleanupExpired(): int {
    // Ejecutar vía cron cada minuto
    return $this->repository->updateExpired();
  }
}

 
5. Ciclo de Vida de la Reserva
5.1 Estados y Transiciones
                    ┌─────────────────────────────────────────────────────┐
                    │           CICLO DE VIDA - BOOKING                   │
                    └─────────────────────────────────────────────────────┘
                                                                           
                              ┌──────────┐                                 
         Pago recibido        │ PENDING  │                                 
              ┌───────────────│ (pago)   │                                 
              │               └────┬─────┘                                 
              ▼                    │ Pago fallido                          
        ┌───────────┐              ▼                                       
        │ CONFIRMED │        ┌──────────┐                                  
        └─────┬─────┘        │  FAILED  │                                  
              │               └──────────┘                                  
   ┌──────────┼──────────┐                                                 
   │          │          │                                                 
   ▼          ▼          ▼                                                 
┌──────┐ ┌───────────┐ ┌───────────┐                                       
│CANCEL│ │IN_PROGRESS│ │ RESCHEDULD│                                       
└──────┘ └─────┬─────┘ └───────────┘                                       
              │                                                            
       ┌──────┴──────┐                                                     
       ▼              ▼                                                    
 ┌───────────┐  ┌─────────┐                                                
 │ COMPLETED │  │ NO_SHOW │                                                
 └───────────┘  └─────────┘                                                
5.2 Definición de Estados
Estado	Descripción	Acciones Disponibles
pending	Reserva creada, esperando confirmación de pago	confirm, cancel
confirmed	Pago recibido, cita confirmada	cancel, reschedule, start
in_progress	La cita está en curso (hora de inicio alcanzada)	complete, no_show
completed	Cita finalizada exitosamente	- (estado final)
cancelled	Cita cancelada (por cliente o profesional)	- (estado final)
no_show	Cliente no se presentó	- (estado final)
rescheduled	Cita reagendada (estado transitorio)	- (crea nueva booking)
failed	Pago fallido o error en proceso	retry, cancel

 
5.3 BookingStateMachine
<?php namespace Drupal\jaraba_booking\StateMachine;

class BookingStateMachine {
  
  private const TRANSITIONS = [
    'pending' => ['confirmed', 'cancelled', 'failed'],
    'confirmed' => ['in_progress', 'cancelled', 'rescheduled'],
    'in_progress' => ['completed', 'no_show'],
    'completed' => [], // Estado final
    'cancelled' => [], // Estado final
    'no_show' => [],   // Estado final
    'rescheduled' => [], // Crea nueva booking
    'failed' => ['pending', 'cancelled'],
  ];
  
  public function canTransition(Booking $booking, string $newStatus): bool {
    $current = $booking->getStatus();
    return in_array($newStatus, self::TRANSITIONS[$current] ?? []);
  }
  
  public function transition(Booking $booking, string $newStatus): Booking {
    if (!$this->canTransition($booking, $newStatus)) {
      throw new InvalidTransitionException(
        "Cannot transition from {$booking->getStatus()} to {$newStatus}"
      );
    }
    
    $booking->setStatus($newStatus);
    $booking->setChanged(new DateTime());
    
    // Disparar evento para ECA
    $this->eventDispatcher->dispatch(
      new BookingStatusChangedEvent($booking, $newStatus)
    );
    
    return $booking;
  }
}

6. Políticas de Cancelación
Las políticas de cancelación determinan los reembolsos aplicables según el momento en que se cancela la cita. Se configuran a nivel de profesional con valores por defecto del tenant.
6.1 Tipos de Política Predefinidos
Política	> 48h antes	24-48h antes	< 24h antes
flexible	100% reembolso	100% reembolso	50% reembolso
moderate	100% reembolso	50% reembolso	0% reembolso
strict	50% reembolso	0% reembolso	0% reembolso
custom	Configurable	Configurable	Configurable

6.2 CancellationService
<?php namespace Drupal\jaraba_booking\Service;

class CancellationService {
  
  public function calculateRefund(Booking $booking): RefundCalculation {
    $policy = $this->getPolicy($booking->getProviderId());
    $hoursUntilStart = $this->getHoursUntilStart($booking);
    $amountPaid = $booking->getDepositAmount();
    
    $refundPercent = match(true) {
      $hoursUntilStart > 48 => $policy->getRefundOver48h(),
      $hoursUntilStart > 24 => $policy->getRefund24to48h(),
      default => $policy->getRefundUnder24h(),
    };
    
    $refundAmount = $amountPaid * ($refundPercent / 100);
    $retainedAmount = $amountPaid - $refundAmount;
    
    return new RefundCalculation(
      refundAmount: $refundAmount,
      retainedAmount: $retainedAmount,
      refundPercent: $refundPercent,
      policy: $policy->getName()
    );
  }
  
  public function processCancellation(
    Booking $booking,
    string $reason,
    string $cancelledBy // 'client' | 'provider' | 'system'
  ): CancellationResult {
    $refund = $this->calculateRefund($booking);
    
    // Si cancela el profesional, siempre 100% reembolso
    if ($cancelledBy === 'provider') {
      $refund = new RefundCalculation(
        refundAmount: $booking->getDepositAmount(),
        retainedAmount: 0,
        refundPercent: 100,
        policy: 'provider_cancellation'
      );
    }
    
    // Procesar reembolso en Stripe
    if ($refund->refundAmount > 0) {
      $this->stripeService->refund(
        $booking->getPaymentIntentId(),
        $refund->refundAmount
      );
    }
    
    // Actualizar booking
    $booking->setCancellationReason($reason);
    $booking->setCancelledBy($cancelledBy);
    $booking->setCancelledAt(new DateTime());
    
    // Transición de estado
    $this->stateMachine->transition($booking, 'cancelled');
    
    // Liberar slot
    $this->calendarService->releaseSlot($booking);
    
    return new CancellationResult($booking, $refund);
  }
}

 
7. Sistema de Recordatorios Multicanal
Los recordatorios son clave para reducir no-shows. El sistema envía notificaciones en momentos estratégicos a través de múltiples canales según las preferencias del cliente.
7.1 Timeline de Recordatorios
Momento	Canal Primario	Canal Secundario	Acción del Cliente
Inmediato	Email	-	Confirmación + ICS
24h antes	Email + WhatsApp	SMS (si no WhatsApp)	Confirmar / Cancelar
2h antes	WhatsApp / SMS	Push notification	Ver ubicación / URL
15min antes	Push notification	-	Unirse (si online)
Hora de inicio	-	-	Auto-start meeting
24h después	Email	WhatsApp	Dejar reseña

7.2 Entidad: reminder_schedule
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
booking_id	INT	Reserva asociada	FK booking.id, NOT NULL, INDEX
reminder_type	VARCHAR(24)	Tipo de recordatorio	ENUM: confirmation|reminder_24h|reminder_2h|reminder_15m|review
scheduled_at	DATETIME	Cuándo enviar	NOT NULL, INDEX
channels	JSON	Canales a usar	['email', 'whatsapp', 'sms']
status	VARCHAR(16)	Estado	ENUM: pending|sent|failed|cancelled
sent_at	DATETIME	Cuándo se envió	NULLABLE
delivery_status	JSON	Estado por canal	{email: 'delivered', sms: 'sent'}

7.3 ReminderService
<?php namespace Drupal\jaraba_booking\Service;

class ReminderService {
  
  public function scheduleReminders(Booking $booking): void {
    $startTime = $booking->getStartDatetime();
    $client = $booking->getClient();
    
    // Confirmación inmediata
    $this->schedule($booking, 'confirmation', new DateTime(), ['email']);
    
    // 24h antes
    $reminder24h = (clone $startTime)->modify('-24 hours');
    if ($reminder24h > new DateTime()) {
      $channels = $this->getClientChannels($client, 'reminder_24h');
      $this->schedule($booking, 'reminder_24h', $reminder24h, $channels);
    }
    
    // 2h antes
    $reminder2h = (clone $startTime)->modify('-2 hours');
    if ($reminder2h > new DateTime()) {
      $channels = $this->getClientChannels($client, 'reminder_2h');
      $this->schedule($booking, 'reminder_2h', $reminder2h, $channels);
    }
    
    // 15min antes (solo si es online)
    if ($booking->getModality() === 'online') {
      $reminder15m = (clone $startTime)->modify('-15 minutes');
      $this->schedule($booking, 'reminder_15m', $reminder15m, ['push']);
    }
    
    // Solicitud de review (24h después)
    $reviewTime = (clone $startTime)->modify('+24 hours');
    $this->schedule($booking, 'review', $reviewTime, ['email', 'whatsapp']);
  }
  
  public function processScheduled(): int {
    $pending = $this->repository->findDueReminders();
    $sent = 0;
    
    foreach ($pending as $reminder) {
      if ($reminder->getBooking()->getStatus() === 'cancelled') {
        $reminder->setStatus('cancelled');
        continue;
      }
      
      $this->sendReminder($reminder);
      $sent++;
    }
    
    return $sent;
  }
}

 
8. Integración de Videollamadas (Jitsi)
Para citas online, el Booking Engine integra Jitsi Meet como solución de videollamadas. Se utiliza el IFrame API para embeber las llamadas directamente en la plataforma, manteniendo la experiencia de usuario unificada.
8.1 Arquitectura de Integración
Componente	Configuración
Servidor Jitsi	meet.jit.si (público) o servidor propio (tenant enterprise)
Room naming	jaraba-{tenant_id}-{booking_number} (ej: jaraba-42-SVC-00123)
Autenticación	JWT tokens firmados para profesional y cliente
Moderación	Profesional es moderator, cliente es participant
Grabación	Opcional, con consentimiento explícito (RGPD)
Transcripción	Opcional, usando Jitsi Jigasi + Google Speech

8.2 JitsiMeetingService
<?php namespace Drupal\jaraba_booking\Service;

class JitsiMeetingService {
  
  public function createRoom(Booking $booking): MeetingRoom {
    $roomName = $this->generateRoomName($booking);
    
    // Generar JWT para el profesional (moderator)
    $providerToken = $this->generateJWT(
      $booking->getProvider(),
      $roomName,
      moderator: true
    );
    
    // Generar JWT para el cliente (participant)
    $clientToken = $this->generateJWT(
      $booking->getClient(),
      $roomName,
      moderator: false
    );
    
    $baseUrl = $this->config->get('jitsi_server') ?? 'https://meet.jit.si';
    
    return new MeetingRoom(
      roomName: $roomName,
      providerUrl: "{$baseUrl}/{$roomName}?jwt={$providerToken}",
      clientUrl: "{$baseUrl}/{$roomName}?jwt={$clientToken}",
      startTime: $booking->getStartDatetime(),
      endTime: $booking->getEndDatetime()
    );
  }
  
  public function generateJWT(User $user, string $room, bool $moderator): string {
    $payload = [
      'iss' => 'jaraba-booking',
      'sub' => $this->config->get('jitsi_app_id'),
      'room' => $room,
      'aud' => 'jitsi',
      'iat' => time(),
      'exp' => time() + 7200, // 2 horas
      'context' => [
        'user' => [
          'id' => $user->id(),
          'name' => $user->getDisplayName(),
          'email' => $user->getEmail(),
          'avatar' => $user->getAvatarUrl(),
        ],
        'features' => [
          'recording' => $moderator,
          'livestreaming' => false,
          'screen-sharing' => true,
        ],
      ],
      'moderator' => $moderator,
    ];
    
    return JWT::encode($payload, $this->config->get('jitsi_secret'), 'HS256');
  }
}

 
9. APIs REST del Booking Engine
9.1 Endpoints de Disponibilidad
Método	Endpoint	Descripción	Auth
GET	/api/v1/availability/{providerId}	Slots disponibles del profesional	Público
GET	/api/v1/availability/{providerId}/service/{serviceId}	Slots para servicio específico	Público
GET	/api/v1/availability/{providerId}/month/{year}/{month}	Vista mensual de disponibilidad	Público
POST	/api/v1/availability/{providerId}/check	Verificar slot específico	Público

9.2 Endpoints de Bloqueo Temporal (Hold)
Método	Endpoint	Descripción	Auth
POST	/api/v1/holds	Crear hold temporal (5 min TTL)	Cliente/Guest
GET	/api/v1/holds/{id}	Estado del hold	Propietario
DELETE	/api/v1/holds/{id}	Liberar hold manualmente	Propietario
POST	/api/v1/holds/{id}/extend	Extender TTL (+3 min, max 1 vez)	Propietario

9.3 Endpoints de Reservas
Método	Endpoint	Descripción	Auth
POST	/api/v1/bookings	Crear reserva (requiere hold válido)	Cliente
POST	/api/v1/bookings/{id}/pay	Procesar pago de reserva	Cliente
POST	/api/v1/bookings/{id}/cancel	Cancelar reserva	Propietario
POST	/api/v1/bookings/{id}/reschedule	Reagendar cita	Propietario
POST	/api/v1/bookings/{id}/confirm	Confirmar (si require_confirmation)	Provider
POST	/api/v1/bookings/{id}/complete	Marcar como completada	Provider
POST	/api/v1/bookings/{id}/no-show	Marcar no-show	Provider
GET	/api/v1/bookings/{id}/meeting	Obtener URL de videollamada	Propietario
GET	/api/v1/bookings/{id}/ics	Descargar archivo ICS	Propietario

 
10. Métricas y KPIs del Booking Engine
Métrica	Objetivo	Descripción
No-show rate	< 3%	Porcentaje de citas donde el cliente no se presenta
Time-to-Value (TTV)	< 45 seg	Tiempo desde selección de slot hasta confirmación de pago
Booking conversion rate	> 60%	Holds que se convierten en bookings confirmados
Calendar sync accuracy	100%	Sincronización perfecta sin conflictos
Reminder delivery rate	> 98%	Recordatorios entregados exitosamente
Payment success rate	> 95%	Pagos procesados sin errores al primer intento
Avg. booking lead time	Monitoreo	Días promedio de antelación en reservas
Cancellation rate	< 10%	Porcentaje de citas canceladas

11. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 2.1	Semana 3	AvailabilityEngine: cálculo de slots, gestión de horarios recurrentes y excepciones	82_Services_Core
Sprint 2.2	Semana 4	TemporaryHoldService + BookingStateMachine + flujo básico de reserva	Sprint 2.1
Sprint 2.3	Semana 5	Integración Stripe: pagos anticipados, Destination Charges, reembolsos	Sprint 2.2
Sprint 2.4	Semana 6	ReminderService multicanal + políticas de cancelación	Sprint 2.3
Sprint 2.5	Semana 7	JitsiMeetingService + Calendar Sync (Google + Outlook)	Sprint 2.4 + 86_Calendar
Sprint 2.6	Semana 8	APIs REST completas + Tests E2E + QA	Sprint 2.5

11.1 Criterios de Aceptación MVP
•	✓ Flujo completo: seleccionar slot → hold → pagar → confirmar en < 45 segundos
•	✓ Zero double-bookings: sistema de holds elimina conflictos
•	✓ Sincronización bidireccional con Google Calendar funcional
•	✓ Recordatorios enviados correctamente (24h y 2h antes)
•	✓ Videollamadas Jitsi funcionando con JWT auth
•	✓ Cancelaciones procesan reembolsos según política
•	✓ Tests con cobertura > 85%

--- Fin del Documento ---
