SISTEMA SERVICES CORE
Arquitectura de Servicios Profesionales Digitales
Vertical ServiciosConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	82_ServiciosConecta_Services_Core
Dependencias:	01_Core_Entidades, 06_Core_Flujos_ECA, 07_MultiTenant
Base:	62_ComercioConecta_Commerce_Core (adaptación ~70%)
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del núcleo de servicios profesionales para la vertical ServiciosConecta del Ecosistema Jaraba. El Services Core es la base sobre la que se construye la "Plataforma de Confianza Digital", conectando profesionales liberales (abogados, médicos, consultores, técnicos) con clientes que buscan expertise especializado.
A diferencia de AgroConecta y ComercioConecta donde se comercializan productos físicos, ServiciosConecta opera bajo un paradigma fundamentalmente distinto: "No vendemos stock, vendemos confianza, tiempo y conocimiento". Esta diferencia impacta toda la arquitectura: desde el modelo de datos hasta los flujos de pago y la experiencia de usuario.
1.1 Objetivos del Sistema
•	Marketplace multi-profesional local: Múltiples profesionales de diferentes especialidades en una plataforma unificada por zona
•	Gestión de citas inteligente: Booking engine con pagos anticipados, sincronización calendario y recordatorios multicanal
•	Confianza digital: Buzón cifrado para documentos sensibles, firma electrónica avanzada, área cliente privada
•	Automatización con IA: Triaje de casos, generación de presupuestos, recomendación de profesionales
•	Split payments automático: Distribución de pagos profesional/plataforma vía Stripe Connect
•	Professional SEO optimizado: Schema.org ProfessionalService + LocalBusiness para búsquedas especializadas
1.2 Stack Tecnológico
Componente	Tecnología
Core Servicios	Drupal 11 con módulo jaraba_services custom
Catálogo	Entidad service_offering con modalidades (presencial/online/hybrid) + tarifas configurables
Booking Engine	Entidad booking + availability_slot con lógica anti-colisión y buffers configurables
Calendarios	Google Calendar API v3 + Microsoft Graph API para sincronización bidireccional
Pagos	Stripe Connect (Express) con Destination Charges + pagos anticipados de citas
Videollamadas	Jitsi Meet IFrame API para consultas online embebidas
Documentos	Buzón de Confianza con cifrado AES-256-GCM + versionado
Firma Digital	Integración AutoFirma/cl@ve para firma PAdES cualificada (eIDAS)
IA/Copilots	Gemini/Claude API para triaje de casos y presupuestador automático
Notificaciones	Email (SendGrid) + SMS (Twilio) + WhatsApp Business API
Búsqueda	Search API + Solr con facetas: especialidad, ubicación, precio, disponibilidad, valoración
SEO/GEO	Schema.org ProfessionalService/Physician/Attorney/LocalBusiness + JSON-LD dinámico

1.3 Filosofía 'Sin Humo'
•	Reutilización máxima: ~70% del código base de ComercioConecta (portales, reviews, notificaciones)
•	Componentes exclusivos: Booking Engine, Buzón de Confianza, Firma Digital, Triaje IA, Presupuestador
•	Sin over-engineering: Usar librerías probadas (FullCalendar, Jitsi) en lugar de reinventar
•	Cumplimiento normativo: RGPD, eIDAS, LOPD desde el diseño (privacy by design)
1.4 Diferencias Clave vs. Verticales de Comercio
Aspecto	AgroConecta/ComercioConecta	ServiciosConecta
Modelo de negocio	Venta de productos físicos/digitales	Venta de tiempo, conocimiento y confianza
Inventario	Stock de unidades físicas	Disponibilidad horaria (slots de agenda)
Unidad de venta	Producto/variación	Servicio/sesión (hora o paquete)
Entrega	Envío físico o Click & Collect	Cita (presencial, online o hybrid)
Pricing	Precio fijo por unidad	Tarifa horaria + presupuestos personalizados
Documentación	Ficha técnica, certificaciones	Contratos, informes, expedientes cifrados
Credenciales	Sellos de calidad, denominaciones	Colegiación, licencias, seguros profesionales
SEO Schema	Product + LocalBusiness/Store	ProfessionalService + especialización (Attorney, Physician...)
Confianza	Reviews de productos	Reviews + credenciales verificadas + firma digital

 
2. Arquitectura de Entidades
El Services Core introduce entidades Drupal personalizadas específicas para servicios profesionales. Se integran con el esquema base definido en 01_Core_Entidades.
2.1 Entidad: provider_profile
Perfil completo del profesional que ofrece servicios. Es el equivalente a merchant_profile en ComercioConecta pero con campos específicos para credenciales profesionales, especialidades y configuración de agenda.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno autoincremental	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
user_id	INT	Usuario Drupal asociado	FK users.uid, UNIQUE, NOT NULL
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
display_name	VARCHAR(255)	Nombre profesional público	NOT NULL
slug	VARCHAR(128)	URL amigable única	UNIQUE, NOT NULL, INDEX
profession_tid	INT	Profesión principal	FK taxonomy_term.tid (vocab: profession)
specialties	JSON	Array de especialidades	NULLABLE, ej: ["Derecho civil", "Familia"]
license_number	VARCHAR(64)	Número de colegiación	NULLABLE, INDEX
license_issuer	VARCHAR(128)	Colegio/organismo emisor	NULLABLE
license_verified	BOOLEAN	Licencia verificada	DEFAULT FALSE
insurance_policy	VARCHAR(64)	Póliza RC profesional	NULLABLE
insurance_expiry	DATE	Vencimiento póliza	NULLABLE
bio	TEXT	Biografía profesional (HTML)	NOT NULL, max 2000 chars
headline	VARCHAR(200)	Tagline SEO	NOT NULL
years_experience	INT	Años de experiencia	NULLABLE, CHECK >= 0
hourly_rate	DECIMAL(8,2)	Tarifa base por hora €	NOT NULL, DEFAULT 0
currency	VARCHAR(3)	Moneda ISO 4217	DEFAULT 'EUR'
service_modalities	JSON	Modalidades ofrecidas	DEFAULT ["presencial"]
service_area_km	INT	Radio de servicio (km)	NULLABLE, para visitas a domicilio
address	JSON	Dirección estructurada	Schema.org PostalAddress format
coordinates	POINT	Geolocalización	SPATIAL INDEX
phone	VARCHAR(20)	Teléfono profesional	E.164 format
email	VARCHAR(255)	Email profesional	NOT NULL
website	VARCHAR(255)	Web personal	NULLABLE, URL válida
linkedin_url	VARCHAR(255)	Perfil LinkedIn	NULLABLE
stripe_account_id	VARCHAR(64)	Stripe Connect Account	NULLABLE, INDEX
stripe_onboarded	BOOLEAN	Onboarding Stripe completo	DEFAULT FALSE
calendar_google_id	VARCHAR(255)	ID calendario Google	NULLABLE
calendar_outlook_id	VARCHAR(255)	ID calendario Outlook	NULLABLE
booking_buffer_mins	INT	Buffer entre citas (min)	DEFAULT 15
advance_booking_days	INT	Días antelación máxima	DEFAULT 60
min_notice_hours	INT	Horas mínimas aviso	DEFAULT 24
cancellation_policy	VARCHAR(32)	Política cancelación	ENUM: flexible|moderate|strict
average_rating	DECIMAL(3,2)	Valoración media (1-5)	NULLABLE, COMPUTED
total_reviews	INT	Número total reseñas	DEFAULT 0
total_bookings	INT	Total citas completadas	DEFAULT 0
status	VARCHAR(16)	Estado del perfil	ENUM: draft|pending|active|suspended
featured	BOOLEAN	Destacado en listados	DEFAULT FALSE
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL

 
2.2 Entidad: service_offering
Define un servicio específico que ofrece el profesional. Es el equivalente a product en verticales de comercio, pero representa una "sesión de servicio" con duración, modalidad y precio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
provider_id	INT	Profesional que ofrece	FK provider_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
title	VARCHAR(255)	Nombre del servicio	NOT NULL
slug	VARCHAR(128)	URL amigable	UNIQUE per provider
description	TEXT	Descripción detallada	NOT NULL
summary	VARCHAR(300)	Resumen para SEO/cards	NOT NULL
category_tid	INT	Categoría de servicio	FK taxonomy_term.tid (vocab: service_category)
service_type	VARCHAR(32)	Tipo de servicio	ENUM: consultation|session|package|retainer
modality	VARCHAR(16)	Modalidad de prestación	ENUM: presencial|online|hybrid|domicilio
duration_mins	INT	Duración en minutos	NOT NULL, DEFAULT 60
price	DECIMAL(10,2)	Precio del servicio €	NOT NULL
price_type	VARCHAR(16)	Tipo de precio	ENUM: fixed|hourly|from|custom
currency	VARCHAR(3)	Moneda ISO 4217	DEFAULT 'EUR'
sessions_included	INT	Sesiones en paquete	DEFAULT 1 (para paquetes)
validity_days	INT	Días de validez paquete	NULLABLE
requires_prepayment	BOOLEAN	Requiere pago anticipado	DEFAULT TRUE
deposit_percent	INT	% de depósito requerido	DEFAULT 100 (pago completo)
is_featured	BOOLEAN	Servicio destacado	DEFAULT FALSE
max_attendees	INT	Máx. asistentes (grupales)	DEFAULT 1
status	VARCHAR(16)	Estado	ENUM: draft|active|paused|archived
sort_weight	INT	Orden en listado	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL

 
2.3 Entidad: booking
Representa una reserva de cita entre un cliente y un profesional. Es la entidad transaccional central de ServiciosConecta, equivalente a commerce_order en verticales de comercio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
booking_number	VARCHAR(16)	Número legible (SVC-XXXXX)	UNIQUE, NOT NULL, INDEX
provider_id	INT	Profesional	FK provider_profile.id, NOT NULL, INDEX
client_id	INT	Cliente	FK users.uid, NOT NULL, INDEX
service_id	INT	Servicio reservado	FK service_offering.id, NOT NULL
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
start_datetime	DATETIME	Inicio de la cita	NOT NULL, INDEX
end_datetime	DATETIME	Fin de la cita	NOT NULL
timezone	VARCHAR(64)	Zona horaria	DEFAULT 'Europe/Madrid'
modality	VARCHAR(16)	Modalidad efectiva	ENUM: presencial|online|domicilio
location_address	TEXT	Dirección (si presencial)	NULLABLE
meeting_url	VARCHAR(500)	URL videollamada (si online)	NULLABLE
meeting_room_id	VARCHAR(64)	ID sala Jitsi	NULLABLE
price_charged	DECIMAL(10,2)	Precio cobrado €	NOT NULL
deposit_amount	DECIMAL(10,2)	Depósito pagado €	DEFAULT 0
balance_due	DECIMAL(10,2)	Saldo pendiente €	COMPUTED
currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
payment_intent_id	VARCHAR(64)	Stripe PaymentIntent	NULLABLE, INDEX
payment_status	VARCHAR(24)	Estado del pago	ENUM: pending|deposit_paid|paid|refunded|failed
status	VARCHAR(24)	Estado de la cita	ENUM: pending|confirmed|in_progress|completed|cancelled|no_show
client_notes	TEXT	Notas del cliente	NULLABLE
provider_notes	TEXT	Notas del profesional	NULLABLE (privadas)
cancellation_reason	TEXT	Motivo cancelación	NULLABLE
cancelled_by	VARCHAR(16)	Quién canceló	ENUM: client|provider|system, NULLABLE
cancelled_at	DATETIME	Fecha cancelación	NULLABLE
reminder_sent	BOOLEAN	Recordatorio enviado	DEFAULT FALSE
google_event_id	VARCHAR(255)	ID evento Google Cal	NULLABLE
outlook_event_id	VARCHAR(255)	ID evento Outlook	NULLABLE
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL

 
2.4 Entidad: availability_slot
Define los slots de disponibilidad recurrente del profesional. Es el "inventario" de ServiciosConecta: en lugar de unidades físicas, se gestionan franjas horarias disponibles.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
provider_id	INT	Profesional	FK provider_profile.id, NOT NULL, INDEX
day_of_week	INT	Día de la semana (1-7)	NOT NULL, CHECK 1-7 (ISO 8601)
start_time	TIME	Hora de inicio	NOT NULL
end_time	TIME	Hora de fin	NOT NULL
modality	VARCHAR(16)	Modalidad en este slot	ENUM: presencial|online|any
location_id	INT	Ubicación específica	FK service_location.id, NULLABLE
valid_from	DATE	Vigente desde	NULLABLE (indefinido si NULL)
valid_until	DATE	Vigente hasta	NULLABLE
is_active	BOOLEAN	Slot activo	DEFAULT TRUE

2.5 Entidad: availability_exception
Excepciones puntuales a la disponibilidad: vacaciones, días festivos, o disponibilidad extra en fechas específicas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
provider_id	INT	Profesional	FK provider_profile.id, NOT NULL
exception_date	DATE	Fecha de la excepción	NOT NULL, INDEX
exception_type	VARCHAR(16)	Tipo de excepción	ENUM: blocked|available|modified
start_time	TIME	Hora inicio (si modified)	NULLABLE
end_time	TIME	Hora fin (si modified)	NULLABLE
reason	VARCHAR(255)	Motivo (opcional)	NULLABLE
is_recurring	BOOLEAN	Se repite anualmente	DEFAULT FALSE (para festivos)

 
3. Servicios PHP Principales
3.1 ProviderService
Servicio central para gestión de perfiles de profesionales:
<?php namespace Drupal\jaraba_services\Service;

class ProviderService {
  // Gestión de perfil
  public function getProfile(int $providerId): ?ProviderProfile;
  public function createProfile(int $userId, array $data): ProviderProfile;
  public function updateProfile(int $providerId, array $data): ProviderProfile;
  public function verifyLicense(int $providerId, string $licenseNumber): bool;
  
  // Stripe Connect
  public function initiateStripeOnboarding(int $providerId): string; // Returns URL
  public function completeStripeOnboarding(int $providerId, string $code): bool;
  public function getStripeAccountStatus(int $providerId): array;
  
  // Búsqueda
  public function search(array $filters): SearchResults;
  public function findNearby(float $lat, float $lng, int $radiusKm): array;
  public function getByProfession(int $professionTid): array;
  
  // Estadísticas
  public function recalculateStats(int $providerId): void;
  public function getAnalytics(int $providerId, DateRange $range): ProviderAnalytics;
}

3.2 AvailabilityService
Motor de gestión de disponibilidad y slots:
<?php namespace Drupal\jaraba_services\Service;

class AvailabilityService {
  // Consulta de disponibilidad
  public function getAvailableSlots(
    int $providerId,
    int $serviceId,
    DateTime $from,
    DateTime $to
  ): array;
  
  public function isSlotAvailable(
    int $providerId,
    DateTime $start,
    int $durationMins
  ): bool;
  
  // Gestión de horarios
  public function setWeeklySchedule(int $providerId, array $slots): void;
  public function getWeeklySchedule(int $providerId): array;
  
  // Excepciones
  public function addException(int $providerId, DateTime $date, string $type): void;
  public function removeException(int $exceptionId): void;
  public function getExceptions(int $providerId, DateTime $from, DateTime $to): array;
  
  // Sincronización con calendarios externos
  public function syncFromGoogle(int $providerId): SyncResult;
  public function syncFromOutlook(int $providerId): SyncResult;
  public function detectConflicts(int $providerId, DateTime $start, DateTime $end): array;
}

 
3.3 BookingService
Orquestador principal del flujo de reservas:
<?php namespace Drupal\jaraba_services\Service;

class BookingService {
  // Ciclo de vida de reservas
  public function create(BookingRequest $request): Booking;
  public function confirm(int $bookingId): Booking;
  public function cancel(int $bookingId, string $reason, string $cancelledBy): Booking;
  public function reschedule(int $bookingId, DateTime $newStart): Booking;
  public function complete(int $bookingId): Booking;
  public function markNoShow(int $bookingId): Booking;
  
  // Consultas
  public function getById(int $bookingId): ?Booking;
  public function getByNumber(string $bookingNumber): ?Booking;
  public function getForProvider(int $providerId, DateRange $range): array;
  public function getForClient(int $clientId, DateRange $range): array;
  public function getUpcoming(int $userId, int $limit = 10): array;
  
  // Pagos
  public function processPayment(int $bookingId, string $paymentMethodId): PaymentResult;
  public function processRefund(int $bookingId, float $amount): RefundResult;
  public function calculateCancellationFee(int $bookingId): float;
  
  // Videollamadas
  public function createMeetingRoom(int $bookingId): string; // Returns Jitsi URL
  public function getMeetingUrl(int $bookingId): ?string;
  
  // Recordatorios
  public function sendReminder(int $bookingId): void;
  public function getPendingReminders(): array;
}

3.4 ServiceOfferingService
<?php namespace Drupal\jaraba_services\Service;

class ServiceOfferingService {
  // CRUD
  public function create(int $providerId, array $data): ServiceOffering;
  public function update(int $serviceId, array $data): ServiceOffering;
  public function delete(int $serviceId): void;
  public function archive(int $serviceId): void;
  
  // Consultas
  public function getByProvider(int $providerId): array;
  public function getActive(int $providerId): array;
  public function search(array $filters): SearchResults;
  
  // Precios
  public function calculatePrice(int $serviceId, array $options): PriceCalculation;
  public function applyPromotion(int $serviceId, string $promoCode): ?Discount;
}

 
4. Taxonomías del Vertical
ServiciosConecta requiere vocabularios específicos para categorización de profesionales y servicios:
Vocabulario	Machine Name	Términos Ejemplo
Profesión	profession	Abogado, Médico, Arquitecto, Consultor, Fisioterapeuta, Psicólogo...
Especialidad Legal	specialty_legal	Derecho civil, Familia, Penal, Laboral, Mercantil, Extranjería...
Especialidad Salud	specialty_health	Medicina general, Pediatría, Traumatología, Psiquiatría...
Especialidad Técnica	specialty_technical	Edificación, Urbanismo, Peritaciones, Eficiencia energética...
Categoría de Servicio	service_category	Consulta inicial, Asesoramiento, Representación, Informe, Sesión terapia...
Modalidad	service_modality	Presencial, Online, Híbrido, A domicilio
Colegio Profesional	professional_college	ICACOR, ICOMCOR, COACM, COPCyL...

4.1 Mapeo Schema.org por Profesión
Profesión	Schema.org Type	Propiedades Adicionales
Abogado	Attorney	areaServed, availableLanguage
Médico	Physician	medicalSpecialty, hospitalAffiliation
Dentista	Dentist	medicalSpecialty
Arquitecto	ProfessionalService	serviceType: "Architecture"
Psicólogo	ProfessionalService	serviceType: "Psychology"
Fisioterapeuta	HealthAndBeautyBusiness	healthPlanNetworkId
Asesor fiscal	AccountingService	serviceType
Consultor	ProfessionalService	serviceType: "Consulting"

 
5. APIs REST
Las APIs REST del Services Core extienden las definidas en 03_Core_APIs_Contratos con endpoints específicos para servicios profesionales.
5.1 Endpoints de Profesionales
Método	Endpoint	Descripción	Auth
GET	/api/v1/providers	Listar profesionales con filtros	Público
GET	/api/v1/providers/{id}	Detalle de profesional	Público
GET	/api/v1/providers/{id}/services	Servicios del profesional	Público
GET	/api/v1/providers/{id}/availability	Slots disponibles	Público
GET	/api/v1/providers/{id}/reviews	Reseñas del profesional	Público
GET	/api/v1/providers/nearby	Profesionales cercanos (geoloc)	Público
PATCH	/api/v1/providers/{id}	Actualizar perfil	Provider
POST	/api/v1/providers/{id}/schedule	Configurar horarios	Provider
GET	/api/v1/providers/{id}/stats	Estadísticas del profesional	Provider

5.2 Endpoints de Reservas (Booking)
Método	Endpoint	Descripción	Auth
POST	/api/v1/bookings	Crear nueva reserva	Cliente
GET	/api/v1/bookings/{id}	Detalle de reserva	Propietario
GET	/api/v1/bookings/number/{number}	Buscar por número SVC-XXXXX	Propietario
PATCH	/api/v1/bookings/{id}	Actualizar reserva	Propietario
POST	/api/v1/bookings/{id}/confirm	Confirmar reserva	Provider
POST	/api/v1/bookings/{id}/cancel	Cancelar reserva	Propietario
POST	/api/v1/bookings/{id}/reschedule	Reagendar cita	Propietario
POST	/api/v1/bookings/{id}/complete	Marcar como completada	Provider
GET	/api/v1/bookings/{id}/meeting	Obtener URL videollamada	Propietario
GET	/api/v1/my/bookings	Mis reservas (cliente)	Cliente
GET	/api/v1/provider/bookings	Mis citas (profesional)	Provider

 
5.3 Endpoints de Servicios
Método	Endpoint	Descripción	Auth
GET	/api/v1/services	Listar servicios con filtros	Público
GET	/api/v1/services/{id}	Detalle de servicio	Público
POST	/api/v1/services	Crear servicio	Provider
PATCH	/api/v1/services/{id}	Actualizar servicio	Provider
DELETE	/api/v1/services/{id}	Eliminar/archivar servicio	Provider
GET	/api/v1/services/categories	Categorías de servicios	Público

6. Flujos de Automatización (ECA)
Eventos automatizados específicos de ServiciosConecta usando el módulo ECA:
Código	Evento	Acciones
SVC-001	booking.created	Enviar confirmación cliente + notificar provider + crear evento calendario + reservar slot
SVC-002	booking.confirmed	Enviar confirmación definitiva + generar QR acceso + programar recordatorios (24h, 2h)
SVC-003	booking.cancelled	Procesar reembolso según política + liberar slot + eliminar evento calendario + notificar
SVC-004	booking.reminder_due	Enviar recordatorio multicanal (email + SMS/WhatsApp) + solicitar confirmación
SVC-005	booking.started	Crear sala Jitsi (si online) + enviar enlace + actualizar estado a in_progress
SVC-006	booking.completed	Solicitar reseña (24h después) + actualizar estadísticas provider + generar factura
SVC-007	booking.no_show	Registrar no-show + aplicar cargo según política + notificar + actualizar métricas
SVC-008	provider.onboarded	Enviar welcome kit + activar perfil + notificar admin + programar tutorial
SVC-009	provider.license_expiring	Notificar renovación (30 días antes) + advertir (7 días) + suspender si expira
SVC-010	review.created	Notificar provider + recalcular average_rating + moderar si score < 2

 
7. Configuración Multi-Tenant
ServiciosConecta soporta múltiples instancias (tenants) donde cada marketplace local de profesionales tiene su propio catálogo, configuración y branding. Utiliza el modelo de 07_Core_Configuracion_MultiTenant con extensiones específicas.
7.1 Group Type: tenant_services
Nuevo Group Type específico para ServiciosConecta con campos adicionales:
Campo	Tipo	Descripción
field_professions_enabled	Entity Ref Multi	Profesiones habilitadas en este tenant
field_license_verification	Boolean	Requiere verificación de colegiación
field_insurance_required	Boolean	Requiere seguro RC profesional
field_default_booking_buffer	Integer	Buffer por defecto entre citas (minutos)
field_cancellation_policy	List	Política cancelación por defecto del tenant
field_platform_commission	Decimal	Comisión % del tenant sobre citas
field_jitsi_server	Text	Servidor Jitsi personalizado (opcional)
field_signature_enabled	Boolean	Habilitar firma digital AutoFirma

7.2 Aislamiento de Datos
Entidad	Aislamiento	Compartición
provider_profile	Por tenant_id (obligatorio)	Un profesional puede estar en múltiples tenants
service_offering	Por tenant_id (obligatorio)	Servicios específicos por marketplace
booking	Por tenant_id (obligatorio)	Nunca compartido
availability_slot	Por provider → tenant	Sincronizado si provider en múltiples tenants
secure_document	Por tenant_id (obligatorio)	Nunca compartido (cifrado por tenant)
Taxonomías (profesiones)	Global (sin tenant)	Compartido entre todos los tenants
Taxonomías (especialidades)	Por tenant (opcional)	Pueden ser globales o específicas

 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Módulo jaraba_services: entidades provider_profile, service_offering. Migraciones. Admin UI. Taxonomías base.	Core entities
Sprint 2	Semana 3-4	Entidades booking, availability_slot, availability_exception. AvailabilityService completo. Tests unitarios.	Sprint 1
Sprint 3	Semana 5-6	BookingService completo. Integración Stripe Connect para pagos. Flujo de confirmación y cancelación.	Sprint 2 + Stripe
Sprint 4	Semana 7-8	Integración Google Calendar + Outlook. Sincronización bidireccional. Detección de conflictos.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA completos (SVC-001 a SVC-010). Notificaciones multicanal. Schema.org JSON-LD.	Sprint 4 + ECA
Sprint 6	Semana 11-12	Frontend Provider Portal y Client Portal. Integración Jitsi. QA completo. Go-live MVP.	Sprint 5

8.1 Criterios de Aceptación Sprint 1
•	✓ CRUD completo de provider_profile desde Admin UI
•	✓ CRUD completo de service_offering con validación de precios y duraciones
•	✓ Taxonomías profession, specialty_*, service_category pobladas
•	✓ Filtrado por tenant_id funcional en todas las queries
•	✓ Tests unitarios con cobertura > 80%
8.2 Dependencias Externas
•	Drupal 11 Core + Commerce 3.x
•	Stripe PHP SDK ^10.0 (Connect + Payment Intents)
•	Google Calendar API PHP Client ^2.x
•	Microsoft Graph SDK PHP ^2.x
•	Jitsi Meet API (IFrame + External API)
•	ECA module + ECA Condition/Action plugins
•	Search API + Solr 8.x

--- Fin del Documento ---
