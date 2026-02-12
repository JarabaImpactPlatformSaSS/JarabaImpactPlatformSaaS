VIDEO CONFERENCING
Videollamadas Integradas para Consultas Online
Jitsi Meet + Sala Privada + Grabación + Sin Instalación
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	87_ServiciosConecta_Video_Conferencing
Dependencias:	85_Booking_Engine, 86_Calendar_Sync
Tecnología:	Jitsi Meet (self-hosted o JaaS)
Prioridad:	ALTA - Diferenciador clave para servicios online
 
1. Resumen Ejecutivo
El módulo Video Conferencing integra videollamadas nativas en ServiciosConecta para consultas online. Cuando un cliente reserva una cita con modalidad 'online', el sistema genera automáticamente una sala privada de videollamada usando Jitsi Meet, envía los enlaces a ambas partes, y permite unirse con un solo clic desde la plataforma sin instalar software adicional.
A diferencia de enviar un enlace de Zoom o Google Meet externo, la integración nativa ofrece: salas privadas por cita con contraseña, interfaz embebida en la plataforma, registro de asistencia automático, y opcionalmente grabación de la sesión con consentimiento. Esto posiciona a ServiciosConecta como una solución completa 'todo en uno' para profesionales que ofrecen consultas telemáticas.
1.1 ¿Por qué Jitsi Meet?
Criterio	Jitsi Meet	Alternativas (Zoom, Meet)
Coste	Gratis (self-hosted) o JaaS desde 0€	Planes de pago para funciones avanzadas
Instalación cliente	No requiere - funciona en navegador	Zoom requiere app, Meet no
Integración/Embed	IFrame API nativa - embebible	Limitado o no disponible
Privacidad	Self-hosted = control total	Datos en servidores externos
Personalización	Branding completo, UI personalizable	Limitada al logo
Grabación	Sí (con Jibri)	Sí (planes de pago)
Open Source	Sí - Apache 2.0	No

1.2 Flujo de Videollamada
┌─────────────────────────────────────────────────────────────────────────┐
│                    FLUJO DE VIDEOLLAMADA                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │   Reserva    │───▶│    Auto      │───▶│    Email     │              │
│  │   Online     │    │  Crear Sala  │    │  con Link    │              │
│  └──────────────┘    └──────────────┘    └───────┬──────┘              │
│                                                   │                     │
│                                                   ▼                     │
│                                        ┌──────────────────┐             │
│                                        │  Recordatorio    │             │
│                                        │  15 min antes    │             │
│                                        └────────┬─────────┘             │
│                                                 │                       │
│  ┌──────────────┐    ┌──────────────┐    ┌──────┴───────┐              │
│  │   Sesión     │◀───│   Unirse     │◀───│  Clic en     │              │
│  │   Activa     │    │   a Sala     │    │  "Entrar"    │              │
│  └───────┬──────┘    └──────────────┘    └──────────────┘              │
│          │                                                              │
│          ▼                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │  Fin Sesión  │───▶│   Registro   │───▶│  Solicitar   │              │
│  │              │    │  Asistencia  │    │  Feedback    │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
└─────────────────────────────────────────────────────────────────────────┘
 
2. Modelo de Datos
2.1 Entidad: video_room (Sala de Videollamada)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
booking_id	INT	Reserva asociada	FK booking.id, NOT NULL, UNIQUE
tenant_id	INT	Tenant	FK tenant.id, NOT NULL
room_name	VARCHAR(100)	Nombre único de sala	jrb-{tenant}-{uuid_short}
room_password	VARCHAR(32)	Contraseña de acceso	Generada automáticamente
jitsi_jwt	TEXT	JWT para autenticación	Si usa JaaS o self-hosted con JWT
provider_join_url	VARCHAR(500)	URL para profesional	Con token de moderador
client_join_url	VARCHAR(500)	URL para cliente	Con token de participante
scheduled_start	DATETIME	Hora programada inicio	NOT NULL
scheduled_end	DATETIME	Hora programada fin	NOT NULL
actual_start	DATETIME	Hora real de inicio	NULLABLE
actual_end	DATETIME	Hora real de fin	NULLABLE
duration_minutes	INT	Duración real (min)	Calculado
status	VARCHAR(16)	Estado de la sala	scheduled|active|ended|cancelled
recording_enabled	BOOLEAN	¿Grabación habilitada?	DEFAULT FALSE
recording_url	VARCHAR(500)	URL de grabación	NULLABLE
recording_consent	JSON	Consentimientos grabación	{provider: bool, client: bool}
created	DATETIME	Fecha creación	NOT NULL

2.2 Entidad: video_participant (Asistencia)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
room_id	INT	Sala	FK video_room.id, NOT NULL
user_id	INT	Usuario	FK users.uid, NOT NULL
role	VARCHAR(16)	Rol en la sala	moderator|participant
display_name	VARCHAR(100)	Nombre mostrado	NOT NULL
joined_at	DATETIME	Cuándo se unió	NULLABLE
left_at	DATETIME	Cuándo salió	NULLABLE
total_time_minutes	INT	Tiempo total en sala	Calculado
connection_quality	VARCHAR(16)	Calidad de conexión	excellent|good|fair|poor

 
3. Servicios Principales
3.1 VideoRoomService
<?php namespace Drupal\jaraba_video\Service;

class VideoRoomService {
  
  private string $jitsiDomain = 'meet.jaraba.es'; // Self-hosted o 8x8.vc para JaaS
  
  public function createRoomForBooking(Booking $booking): VideoRoom {
    // Generar nombre único de sala
    $roomName = $this->generateRoomName($booking);
    $password = $this->generatePassword();
    
    $room = VideoRoom::create([
      'booking_id' => $booking->id(),
      'tenant_id' => $booking->getTenantId(),
      'room_name' => $roomName,
      'room_password' => $password,
      'scheduled_start' => $booking->getStartTime(),
      'scheduled_end' => $booking->getEndTime(),
      'status' => 'scheduled',
      'recording_enabled' => false,
    ]);
    
    // Generar URLs de acceso
    $room->setProviderJoinUrl($this->generateJoinUrl($room, 'moderator'));
    $room->setClientJoinUrl($this->generateJoinUrl($room, 'participant'));
    
    // Crear participantes
    $this->createParticipant($room, $booking->getProvider(), 'moderator');
    $this->createParticipant($room, $booking->getClient(), 'participant');
    
    $room->save();
    return $room;
  }
  
  private function generateJoinUrl(VideoRoom $room, string $role): string {
    $baseUrl = "https://{$this->jitsiDomain}/{$room->getRoomName()}";
    
    // Si usamos JWT para autenticación
    if ($this->config->get('use_jwt')) {
      $jwt = $this->generateJWT($room, $role);
      return "{$baseUrl}?jwt={$jwt}";
    }
    
    // Con contraseña simple
    $params = [
      'config.prejoinPageEnabled' => 'false',
      'config.startWithAudioMuted' => $role === 'participant' ? 'true' : 'false',
      'config.startWithVideoMuted' => 'false',
      'userInfo.displayName' => urlencode($this->getDisplayName($role)),
    ];
    
    return $baseUrl . '#' . http_build_query($params);
  }
  
  private function generateJWT(VideoRoom $room, string $role): string {
    $payload = [
      'aud' => 'jitsi',
      'iss' => $this->config->get('jwt_app_id'),
      'sub' => $this->jitsiDomain,
      'room' => $room->getRoomName(),
      'exp' => $room->getScheduledEnd()->getTimestamp() + 3600,
      'moderator' => $role === 'moderator',
      'context' => [
        'user' => [
          'name' => $this->getDisplayName($role),
          'email' => $this->getEmail($role),
        ],
        'features' => [
          'recording' => $role === 'moderator',
          'livestreaming' => false,
        ],
      ],
    ];
    
    return JWT::encode($payload, $this->config->get('jwt_secret'), 'HS256');
  }
  
  public function startRoom(VideoRoom $room): void {
    $room->setStatus('active');
    $room->setActualStart(new \DateTime());
    $room->save();
    
    $this->eventDispatcher->dispatch(new VideoRoomStartedEvent($room));
  }
  
  public function endRoom(VideoRoom $room): void {
    $room->setStatus('ended');
    $room->setActualEnd(new \DateTime());
    
    // Calcular duración real
    $duration = $room->getActualStart()->diff($room->getActualEnd());
    $room->setDurationMinutes($duration->i + ($duration->h * 60));
    
    $room->save();
    
    // Actualizar booking con confirmación de asistencia
    $this->bookingService->markAsCompleted($room->getBooking());
    
    $this->eventDispatcher->dispatch(new VideoRoomEndedEvent($room));
  }
}

 
4. Integración con Jitsi Meet
4.1 Opciones de Despliegue
Opción	Ventajas	Consideraciones
meet.jit.si (público)	Gratis, sin mantenimiento, inmediato	Sin branding, límites de uso, sin JWT
JaaS (8x8)	SLA garantizado, JWT, grabación cloud	Coste por minuto (~0.002€/min), dependencia
Self-hosted	Control total, branding, privacidad, JWT	Requiere servidor dedicado, mantenimiento

4.2 Embeber Jitsi en la Plataforma
// Frontend React/Vue component
const VideoRoom = ({ roomName, jwt, displayName, onMeetingEnd }) => {
  const containerRef = useRef(null);
  const apiRef = useRef(null);
  
  useEffect(() => {
    const domain = 'meet.jaraba.es';
    const options = {
      roomName: roomName,
      jwt: jwt,
      parentNode: containerRef.current,
      width: '100%',
      height: 600,
      configOverwrite: {
        prejoinPageEnabled: false,
        disableDeepLinking: true,
        startWithAudioMuted: false,
        startWithVideoMuted: false,
        toolbarButtons: [
          'microphone', 'camera', 'desktop', 'chat',
          'raisehand', 'tileview', 'hangup'
        ],
      },
      interfaceConfigOverwrite: {
        SHOW_JITSI_WATERMARK: false,
        SHOW_BRAND_WATERMARK: true,
        BRAND_WATERMARK_LINK: 'https://jaraba.es',
        DEFAULT_LOGO_URL: '/logo-jaraba.png',
      },
      userInfo: {
        displayName: displayName,
      },
    };
    
    apiRef.current = new JitsiMeetExternalAPI(domain, options);
    
    // Event listeners
    apiRef.current.addListener('videoConferenceJoined', (data) => {
      console.log('Joined:', data);
      // Notificar al backend
      fetch('/api/v1/video-rooms/participant-joined', {
        method: 'POST',
        body: JSON.stringify({ room: roomName, participant: displayName })
      });
    });
    
    apiRef.current.addListener('videoConferenceLeft', () => {
      onMeetingEnd?.();
    });
    
    return () => apiRef.current?.dispose();
  }, [roomName, jwt, displayName]);
  
  return <div ref={containerRef} className="video-container" />;
};

 
5. APIs REST
Método	Endpoint	Descripción	Auth
GET	/api/v1/video-rooms/{booking_uuid}	Obtener sala de una reserva	User
GET	/api/v1/video-rooms/{uuid}/join-url	Obtener URL de acceso personalizada	User
POST	/api/v1/video-rooms/{uuid}/start	Iniciar sala (moderador)	Provider
POST	/api/v1/video-rooms/{uuid}/end	Finalizar sala	Provider
POST	/api/v1/video-rooms/participant-joined	Registrar entrada participante	User
POST	/api/v1/video-rooms/participant-left	Registrar salida participante	User
POST	/api/v1/video-rooms/{uuid}/recording/start	Iniciar grabación (con consentimiento)	Provider
GET	/api/v1/video-rooms/{uuid}/recording	Obtener URL de grabación	Provider

6. Flujos de Automatización (ECA)
Código	Evento	Acciones
VID-001	booking.confirmed (modalidad: online)	Crear video_room → Enviar emails con links de acceso
VID-002	video_room.scheduled_start - 15min	Enviar recordatorio con link → Push notification
VID-003	video_room.participant_joined (primero)	Marcar sala como 'active' → Registrar actual_start
VID-004	video_room.all_participants_left	Marcar sala como 'ended' → Calcular duración → Registrar asistencia
VID-005	video_room.ended	Actualizar booking como 'completed' → Disparar flujo de facturación
VID-006	video_room.recording_ready	Notificar profesional que grabación disponible → Enlace de descarga

7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 4.1	Semana 10	Entidad video_room + VideoRoomService + creación automática	85_Booking_Engine
Sprint 4.2	Semana 11	Integración Jitsi IFrame + JWT + componente React	Sprint 4.1
Sprint 4.3	Semana 12	Registro asistencia + ECA automations + grabación opcional	Sprint 4.2

7.1 Criterios de Aceptación
•	✓ Sala creada automáticamente al confirmar reserva online
•	✓ Profesional y cliente reciben links personalizados por email
•	✓ Videollamada funciona sin instalar software (solo navegador)
•	✓ Interfaz embebida en la plataforma (no redirección externa)
•	✓ Registro automático de asistencia y duración
•	✓ Recordatorio 15 minutos antes con link directo
•	✓ Grabación opcional con consentimiento de ambas partes

--- Fin del Documento ---
