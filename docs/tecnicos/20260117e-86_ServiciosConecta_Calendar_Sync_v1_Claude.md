CALENDAR SYNC
Sincronización Bidireccional con Calendarios Externos
Google Calendar + Microsoft Outlook
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	86_ServiciosConecta_Calendar_Sync
Dependencias:	82_Services_Core, 85_Booking_Engine_Core
Prioridad:	ALTA - Evita conflictos de agenda
 
1. Resumen Ejecutivo
El módulo Calendar Sync permite a los profesionales mantener sincronizada su disponibilidad en ServiciosConecta con sus calendarios externos (Google Calendar y Microsoft Outlook). La sincronización es bidireccional: los eventos externos bloquean slots en ServiciosConecta, y las citas creadas en la plataforma se reflejan automáticamente en el calendario personal del profesional.
Este componente es crítico para evitar el problema más frustrante en booking de servicios: las dobles reservas. Un profesional que usa Google Calendar para compromisos personales y ServiciosConecta para citas de clientes necesita que ambos sistemas "hablen" entre sí en tiempo real.
1.1 Objetivos del Sistema
•	Zero conflictos: Eliminar al 100% las dobles reservas entre agenda interna y calendarios externos
•	Sincronización < 30 segundos: Latencia máxima entre evento externo y bloqueo de slot
•	Onboarding OAuth simple: Conexión en menos de 3 clics sin conocimientos técnicos
•	Multi-calendario: Soporte para múltiples calendarios por profesional
•	Privacidad preservada: Solo se importa disponibilidad, no contenido de eventos externos
•	Resiliencia: Funcionamiento degradado si APIs externas fallan temporalmente
1.2 Plataformas Soportadas
Plataforma	API	Capacidades
Google Calendar	Calendar API v3	Lectura, escritura, webhooks (push notifications)
Microsoft Outlook	Microsoft Graph API	Lectura, escritura, webhooks (change notifications)
Apple Calendar	CalDAV	Futuro: Solo lectura vía CalDAV (sin webhooks nativos)

1.3 Flujos de Sincronización
Dirección	Descripción
Externo → Jaraba	Eventos en Google/Outlook bloquean slots de disponibilidad. Solo se importa busy/free, no detalles.
Jaraba → Externo	Citas confirmadas se crean como eventos en el calendario del profesional con detalles completos.

 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────┐
│                    CALENDAR SYNC MODULE                             │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────┐         ┌─────────────────┐                   │
│  │  CalendarSync   │         │   Availability  │                   │
│  │    Service      │────────▶│     Engine      │                   │
│  └────────┬────────┘         └─────────────────┘                   │
│           │                                                        │
│     ┌─────┴─────┐                                                  │
│     │           │                                                  │
│     ▼           ▼                                                  │
│  ┌──────────┐  ┌──────────┐                                        │
│  │  Google  │  │ Microsoft│                                        │
│  │ Adapter  │  │ Adapter  │                                        │
│  └────┬─────┘  └────┬─────┘                                        │
│       │             │                                              │
└───────┼─────────────┼──────────────────────────────────────────────┘
        │             │                                               
        ▼             ▼                                               
 ┌────────────┐  ┌────────────┐                                       
 │  Google    │  │ Microsoft  │                                       
 │Calendar API│  │ Graph API  │                                       
 └────────────┘  └────────────┘                                       
2.2 Patrón Adapter (Strategy)
Se utiliza el patrón Adapter/Strategy para abstraer las diferencias entre APIs de calendario:
<?php namespace Drupal\jaraba_calendar\Adapter;

interface CalendarAdapterInterface {
  
  // Autenticación OAuth
  public function getAuthorizationUrl(string $redirectUri): string;
  public function exchangeCodeForTokens(string $code): TokenSet;
  public function refreshTokens(TokenSet $tokens): TokenSet;
  
  // Lectura de eventos
  public function listCalendars(): array;
  public function getEvents(string $calendarId, DateTime $from, DateTime $to): array;
  public function getFreeBusy(string $calendarId, DateTime $from, DateTime $to): array;
  
  // Escritura de eventos
  public function createEvent(string $calendarId, CalendarEvent $event): string;
  public function updateEvent(string $calendarId, string $eventId, CalendarEvent $event): void;
  public function deleteEvent(string $calendarId, string $eventId): void;
  
  // Webhooks
  public function registerWebhook(string $calendarId, string $callbackUrl): WebhookSubscription;
  public function renewWebhook(WebhookSubscription $subscription): WebhookSubscription;
  public function deleteWebhook(WebhookSubscription $subscription): void;
}

 
3. Modelo de Datos
3.1 Entidad: calendar_connection
Almacena las conexiones OAuth de cada profesional con sus calendarios externos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
provider_id	INT	Profesional	FK provider_profile.id, NOT NULL, INDEX
platform	VARCHAR(16)	Plataforma de calendario	ENUM: google|microsoft|apple
account_email	VARCHAR(255)	Email de la cuenta	NOT NULL
access_token	TEXT	OAuth access token (cifrado)	NOT NULL, ENCRYPTED
refresh_token	TEXT	OAuth refresh token (cifrado)	NULLABLE, ENCRYPTED
token_expires_at	DATETIME	Expiración del access token	NOT NULL
scopes	JSON	Permisos OAuth concedidos	NOT NULL
status	VARCHAR(16)	Estado de la conexión	ENUM: active|expired|revoked|error
last_sync_at	DATETIME	Última sincronización	NULLABLE
sync_errors	INT	Errores consecutivos	DEFAULT 0
created	DATETIME	Fecha conexión	NOT NULL

3.2 Entidad: synced_calendar
Calendarios específicos seleccionados para sincronizar (un usuario puede tener varios calendarios en una cuenta).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
connection_id	INT	Conexión OAuth	FK calendar_connection.id
external_calendar_id	VARCHAR(255)	ID del calendario externo	NOT NULL, INDEX
calendar_name	VARCHAR(255)	Nombre del calendario	NOT NULL
sync_direction	VARCHAR(16)	Dirección de sync	ENUM: read|write|both
is_primary	BOOLEAN	Calendario principal para escritura	DEFAULT FALSE
color	VARCHAR(7)	Color del calendario (#HEX)	NULLABLE
webhook_subscription_id	VARCHAR(255)	ID de suscripción webhook	NULLABLE
webhook_expires_at	DATETIME	Expiración del webhook	NULLABLE
sync_token	VARCHAR(255)	Token para sync incremental	NULLABLE
is_enabled	BOOLEAN	Sincronización activa	DEFAULT TRUE

 
3.3 Entidad: external_event_cache
Caché local de eventos externos para cálculo rápido de disponibilidad sin llamadas API constantes.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
synced_calendar_id	INT	Calendario sincronizado	FK synced_calendar.id
external_event_id	VARCHAR(255)	ID del evento externo	NOT NULL, INDEX
start_datetime	DATETIME	Inicio del evento	NOT NULL, INDEX
end_datetime	DATETIME	Fin del evento	NOT NULL
is_all_day	BOOLEAN	Evento de día completo	DEFAULT FALSE
status	VARCHAR(16)	Estado del evento	ENUM: confirmed|tentative|cancelled
transparency	VARCHAR(16)	Ocupa tiempo o no	ENUM: opaque|transparent (busy/free)
etag	VARCHAR(255)	ETag para detectar cambios	NULLABLE
last_synced_at	DATETIME	Última sincronización	NOT NULL

Nota: Solo se almacena información de tiempo (start/end) y estado. No se almacena título, descripción ni asistentes por privacidad.
4. Google Calendar Adapter
4.1 Configuración OAuth 2.0
Parámetro	Valor
Auth Endpoint	https://accounts.google.com/o/oauth2/v2/auth
Token Endpoint	https://oauth2.googleapis.com/token
Scopes requeridos	https://www.googleapis.com/auth/calendar.readonly, https://www.googleapis.com/auth/calendar.events
Redirect URI	https://{domain}/api/v1/calendar/google/callback
Access Type	offline (para refresh tokens)
Prompt	consent (forzar pantalla de consentimiento)

4.2 GoogleCalendarAdapter
<?php namespace Drupal\jaraba_calendar\Adapter;

class GoogleCalendarAdapter implements CalendarAdapterInterface {
  
  private Google\Client $client;
  private Google\Service\Calendar $service;
  
  public function __construct(ConfigFactoryInterface $config) {
    $this->client = new Google\Client();
    $this->client->setClientId($config->get('google_client_id'));
    $this->client->setClientSecret($config->get('google_client_secret'));
    $this->client->setAccessType('offline');
    $this->client->setPrompt('consent');
  }
  
  public function getFreeBusy(string $calendarId, DateTime $from, DateTime $to): array {
    $request = new Google\Service\Calendar\FreeBusyRequest([
      'timeMin' => $from->format(DateTime::RFC3339),
      'timeMax' => $to->format(DateTime::RFC3339),
      'items' => [['id' => $calendarId]],
    ]);
    
    $response = $this->service->freebusy->query($request);
    $calendar = $response->getCalendars()[$calendarId];
    
    return array_map(fn($period) => [
      'start' => new DateTime($period->getStart()),
      'end' => new DateTime($period->getEnd()),
    ], $calendar->getBusy());
  }
  
  public function registerWebhook(string $calendarId, string $callbackUrl): WebhookSubscription {
    $channel = new Google\Service\Calendar\Channel([
      'id' => Uuid::uuid4()->toString(),
      'type' => 'web_hook',
      'address' => $callbackUrl,
      'token' => $this->generateSecurityToken(),
      'expiration' => (time() + 604800) * 1000, // 7 días en ms
    ]);
    
    $response = $this->service->events->watch($calendarId, $channel);
    
    return new WebhookSubscription(
      id: $response->getId(),
      resourceId: $response->getResourceId(),
      expiresAt: new DateTime('@' . ($response->getExpiration() / 1000))
    );
  }
}

 
5. Microsoft Graph Adapter
5.1 Configuración OAuth 2.0 (Azure AD)
Parámetro	Valor
Auth Endpoint	https://login.microsoftonline.com/common/oauth2/v2.0/authorize
Token Endpoint	https://login.microsoftonline.com/common/oauth2/v2.0/token
Scopes requeridos	Calendars.ReadWrite, offline_access, User.Read
Redirect URI	https://{domain}/api/v1/calendar/microsoft/callback
Tenant	common (multi-tenant: personal + work accounts)

5.2 MicrosoftGraphAdapter
<?php namespace Drupal\jaraba_calendar\Adapter;

class MicrosoftGraphAdapter implements CalendarAdapterInterface {
  
  private const GRAPH_URL = 'https://graph.microsoft.com/v1.0';
  
  public function getFreeBusy(string $calendarId, DateTime $from, DateTime $to): array {
    // Microsoft usa el endpoint /calendar/getSchedule
    $response = $this->httpClient->post(self::GRAPH_URL . '/me/calendar/getSchedule', [
      'json' => [
        'schedules' => [$calendarId],
        'startTime' => ['dateTime' => $from->format('c'), 'timeZone' => 'UTC'],
        'endTime' => ['dateTime' => $to->format('c'), 'timeZone' => 'UTC'],
        'availabilityViewInterval' => 30,
      ],
      'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
    ]);
    
    $data = json_decode($response->getBody(), true);
    $busySlots = [];
    
    foreach ($data['value'][0]['scheduleItems'] ?? [] as $item) {
      if ($item['status'] !== 'free') {
        $busySlots[] = [
          'start' => new DateTime($item['start']['dateTime']),
          'end' => new DateTime($item['end']['dateTime']),
        ];
      }
    }
    
    return $busySlots;
  }
  
  public function registerWebhook(string $calendarId, string $callbackUrl): WebhookSubscription {
    $response = $this->httpClient->post(self::GRAPH_URL . '/subscriptions', [
      'json' => [
        'changeType' => 'created,updated,deleted',
        'notificationUrl' => $callbackUrl,
        'resource' => '/me/calendars/' . $calendarId . '/events',
        'expirationDateTime' => (new DateTime('+3 days'))->format('c'),
        'clientState' => $this->generateSecurityToken(),
      ],
      'headers' => ['Authorization' => 'Bearer ' . $this->accessToken],
    ]);
    
    $data = json_decode($response->getBody(), true);
    
    return new WebhookSubscription(
      id: $data['id'],
      resourceId: $calendarId,
      expiresAt: new DateTime($data['expirationDateTime'])
    );
  }
}

 
6. CalendarSyncService
Servicio central que orquesta la sincronización bidireccional:
<?php namespace Drupal\jaraba_calendar\Service;

class CalendarSyncService {
  
  public function syncFromExternal(SyncedCalendar $calendar): SyncResult {
    $adapter = $this->getAdapter($calendar->getConnection());
    $from = new DateTime('-7 days');
    $to = new DateTime('+90 days');
    
    try {
      // Usar sync incremental si tenemos syncToken
      if ($calendar->getSyncToken()) {
        $events = $adapter->getEventsDelta($calendar->getExternalId(), $calendar->getSyncToken());
      } else {
        $events = $adapter->getEvents($calendar->getExternalId(), $from, $to);
      }
      
      $created = $updated = $deleted = 0;
      
      foreach ($events as $event) {
        $cached = $this->eventCacheRepository->findByExternalId($event->id);
        
        if ($event->deleted) {
          if ($cached) {
            $this->eventCacheRepository->delete($cached);
            $deleted++;
          }
        } elseif ($cached) {
          $this->updateCachedEvent($cached, $event);
          $updated++;
        } else {
          $this->createCachedEvent($calendar, $event);
          $created++;
        }
      }
      
      // Actualizar syncToken para próxima sincronización incremental
      $calendar->setSyncToken($events->getNextSyncToken());
      $calendar->getConnection()->setLastSyncAt(new DateTime());
      $calendar->getConnection()->setSyncErrors(0);
      
      return new SyncResult($created, $updated, $deleted);
      
    } catch (ApiException $e) {
      $this->handleSyncError($calendar->getConnection(), $e);
      throw $e;
    }
  }
  
  public function pushBookingToExternal(Booking $booking): void {
    $provider = $booking->getProvider();
    $calendar = $this->getPrimaryWriteCalendar($provider);
    
    if (!$calendar) {
      return; // No hay calendario configurado para escritura
    }
    
    $adapter = $this->getAdapter($calendar->getConnection());
    
    $event = new CalendarEvent(
      summary: 'Cita: ' . $booking->getService()->getTitle(),
      description: $this->buildEventDescription($booking),
      start: $booking->getStartDatetime(),
      end: $booking->getEndDatetime(),
      location: $this->getLocationString($booking),
      attendees: [$booking->getClient()->getEmail()],
      reminders: [
        ['method' => 'popup', 'minutes' => 30],
        ['method' => 'email', 'minutes' => 1440], // 24h
      ],
    );
    
    $externalEventId = $adapter->createEvent($calendar->getExternalId(), $event);
    
    // Guardar referencia para actualizaciones/cancelaciones
    if ($calendar->getPlatform() === 'google') {
      $booking->setGoogleEventId($externalEventId);
    } else {
      $booking->setOutlookEventId($externalEventId);
    }
  }
  
  public function deleteExternalEvent(Booking $booking): void {
    $provider = $booking->getProvider();
    
    // Eliminar de Google si existe
    if ($booking->getGoogleEventId()) {
      $googleCal = $this->getGoogleCalendar($provider);
      if ($googleCal) {
        $adapter = $this->getAdapter($googleCal->getConnection());
        $adapter->deleteEvent($googleCal->getExternalId(), $booking->getGoogleEventId());
      }
    }
    
    // Eliminar de Outlook si existe
    if ($booking->getOutlookEventId()) {
      $outlookCal = $this->getOutlookCalendar($provider);
      if ($outlookCal) {
        $adapter = $this->getAdapter($outlookCal->getConnection());
        $adapter->deleteEvent($outlookCal->getExternalId(), $booking->getOutlookEventId());
      }
    }
  }
}

 
7. Sistema de Webhooks
Los webhooks permiten sincronización casi en tiempo real (< 30 segundos) cuando se producen cambios en calendarios externos.
7.1 Endpoints de Webhook
Plataforma	Endpoint	Validación
Google	/api/v1/webhooks/google/calendar	X-Goog-Channel-Token header
Microsoft	/api/v1/webhooks/microsoft/calendar	clientState en body + validation token

7.2 WebhookController
<?php namespace Drupal\jaraba_calendar\Controller;

class CalendarWebhookController {
  
  public function handleGoogleWebhook(Request $request): Response {
    // Validar token de seguridad
    $channelToken = $request->headers->get('X-Goog-Channel-Token');
    if (!$this->validateGoogleToken($channelToken)) {
      return new Response('Unauthorized', 401);
    }
    
    $resourceId = $request->headers->get('X-Goog-Resource-Id');
    $calendar = $this->calendarRepository->findByResourceId($resourceId);
    
    if (!$calendar) {
      return new Response('Not found', 404);
    }
    
    // Encolar sincronización (no bloquear webhook)
    $this->queue->enqueue(new SyncCalendarJob($calendar->id()));
    
    return new Response('OK', 200);
  }
  
  public function handleMicrosoftWebhook(Request $request): Response {
    $body = json_decode($request->getContent(), true);
    
    // Microsoft requiere validación de URL en setup
    if (isset($body['validationToken'])) {
      return new Response($body['validationToken'], 200, [
        'Content-Type' => 'text/plain',
      ]);
    }
    
    foreach ($body['value'] ?? [] as $notification) {
      // Validar clientState
      if (!$this->validateMicrosoftClientState($notification['clientState'])) {
        continue;
      }
      
      $subscriptionId = $notification['subscriptionId'];
      $calendar = $this->calendarRepository->findBySubscriptionId($subscriptionId);
      
      if ($calendar) {
        $this->queue->enqueue(new SyncCalendarJob($calendar->id()));
      }
    }
    
    return new Response('', 202);
  }
}

7.3 Renovación Automática de Webhooks
Plataforma	Duración Máxima	Estrategia de Renovación
Google	7 días	Cron job diario renueva webhooks que expiran en < 24h
Microsoft	3 días (máx 4230 min)	Cron job cada 12h renueva webhooks que expiran en < 24h

 
8. APIs REST
Método	Endpoint	Descripción	Auth
GET	/api/v1/calendar/connections	Listar conexiones del profesional	Provider
GET	/api/v1/calendar/google/auth	Iniciar OAuth Google	Provider
GET	/api/v1/calendar/google/callback	Callback OAuth Google	Provider
GET	/api/v1/calendar/microsoft/auth	Iniciar OAuth Microsoft	Provider
GET	/api/v1/calendar/microsoft/callback	Callback OAuth Microsoft	Provider
DELETE	/api/v1/calendar/connections/{id}	Desconectar cuenta	Provider
GET	/api/v1/calendar/connections/{id}/calendars	Listar calendarios de una conexión	Provider
POST	/api/v1/calendar/sync/{calendarId}	Configurar calendario para sync	Provider
PATCH	/api/v1/calendar/sync/{calendarId}	Actualizar config de sync	Provider
DELETE	/api/v1/calendar/sync/{calendarId}	Dejar de sincronizar calendario	Provider
POST	/api/v1/calendar/sync/{calendarId}/refresh	Forzar sincronización manual	Provider

9. Flujos de Automatización (ECA)
Código	Evento	Acciones
CAL-001	booking.confirmed	Crear evento en calendario externo principal del profesional
CAL-002	booking.rescheduled	Actualizar evento externo con nueva fecha/hora
CAL-003	booking.cancelled	Eliminar evento del calendario externo
CAL-004	webhook.received	Encolar job de sincronización para el calendario afectado
CAL-005	cron.hourly	Sincronización periódica de todos los calendarios activos
CAL-006	cron.daily	Renovar webhooks próximos a expirar + limpiar cache de eventos pasados
CAL-007	connection.token_expired	Intentar refresh token, si falla marcar conexión como 'expired' y notificar
CAL-008	sync.error_threshold	Si sync_errors > 5 consecutivos, desactivar sync y notificar profesional

 
10. Manejo de Errores y Resiliencia
10.1 Errores Comunes y Mitigación
Error	Causa	Mitigación
401 Unauthorized	Access token expirado	Auto-refresh con refresh_token
403 Forbidden	Usuario revocó permisos en cuenta externa	Marcar conexión como 'revoked', notificar
404 Not Found	Calendario eliminado externamente	Marcar synced_calendar como inactivo
429 Rate Limited	Demasiadas llamadas API	Exponential backoff, reducir frecuencia sync
5xx Server Error	API externa caída temporalmente	Retry con backoff, usar caché mientras tanto
Token refresh failed	Refresh token inválido o expirado	Requerir re-autenticación OAuth

10.2 Modo Degradado
Si las APIs externas fallan, el sistema sigue funcionando con datos cacheados:
•	Cache válida: Se usa external_event_cache para calcular disponibilidad (datos de última sincronización exitosa)
•	Notificación: Se muestra warning al profesional indicando que la sincronización está degradada
•	Retry automático: Jobs de sincronización se reintentan con exponential backoff (1min, 5min, 15min, 1h)
•	Umbral de errores: Después de 5 errores consecutivos, se desactiva sync y se requiere intervención manual
11. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 4.1	Semana 7	CalendarAdapterInterface + GoogleCalendarAdapter completo (OAuth, CRUD, FreeBusy)	85_Booking_Engine
Sprint 4.2	Semana 8	MicrosoftGraphAdapter completo + modelo de datos (entidades de conexión y cache)	Sprint 4.1
Sprint 4.3	Semana 9	CalendarSyncService + sistema de webhooks + renovación automática	Sprint 4.2
Sprint 4.4	Semana 10	UI de configuración en Provider Portal + tests E2E + QA	Sprint 4.3

11.1 Criterios de Aceptación
•	✓ OAuth flow completo para Google Calendar (< 3 clics)
•	✓ OAuth flow completo para Microsoft Outlook
•	✓ Sincronización bidireccional funcional (latencia < 30 seg vía webhooks)
•	✓ Zero conflictos: eventos externos bloquean correctamente slots internos
•	✓ Bookings se reflejan en calendario externo con todos los detalles
•	✓ Modo degradado funciona correctamente cuando APIs fallan
•	✓ Tests de integración con mocks de APIs externas

--- Fin del Documento ---
