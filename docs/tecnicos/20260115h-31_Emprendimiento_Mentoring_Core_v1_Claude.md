SISTEMA CORE DE MENTORÍAS
Mentoring Core
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	31_Emprendimiento_Mentoring_Core
Dependencias:	04_Core_Permisos_RBAC, Stripe Connect, 25_Business_Diagnostic
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica del Sistema Core de Mentorías para la vertical de Emprendimiento. El sistema implementa un marketplace de mentorías donde consultores certificados (Método Jaraba™) y expertos pueden ofrecer sus servicios a emprendedores, con matching inteligente, pagos integrados y seguimiento de impacto.
1.1 Objetivos del Sistema
•	Marketplace de mentores: Directorio de expertos con perfiles, especialidades y disponibilidad
•	Matching inteligente: Algoritmo que conecta emprendedor-mentor según sector, fase y necesidades
•	Paquetes flexibles: Sesiones sueltas, packs, suscripciones y programas estructurados
•	Pagos integrados: Stripe Connect con Destination Charges para split automático
•	Métricas de impacto: Tracking de resultados para justificar ROI y reportes a financiadores
•	Certificación Jaraba™: Sistema de acreditación para mentores del ecosistema
1.2 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_mentoring custom
Perfiles	Custom entity mentor_profile extendiendo user
Calendario	FullCalendar.js + custom entity availability_slot
Pagos	Stripe Connect (Express Accounts) con Destination Charges
Videollamadas	Integración Jitsi Meet (self-hosted) o Zoom API
Matching	Algoritmo PHP custom con scoring multidimensional
Notificaciones	Push + Email + WhatsApp Business API
 
2. Modelo de Negocio de Mentorías
El sistema de mentorías opera como un marketplace donde la plataforma conecta oferta (mentores) con demanda (emprendedores), cobrando comisión por transacción.
2.1 Flujo Económico
Usando Stripe Connect con Destination Charges:
Concepto	Importe	Destino
Pago del Emprendedor	€100.00	Stripe procesa
Fee Stripe (~3.15%)	-€3.15	Stripe
Application Fee Jaraba (15%)	-€15.00	Cuenta Jaraba
Neto para Mentor	€81.85	Cuenta Express Mentor
2.2 Tipos de Producto de Mentoría
Tipo	machine_name	Descripción	Precio Típico
Sesión Suelta	single_session	Una sesión de 45-60 minutos	€50-150
Pack de Sesiones	session_pack	3, 5 o 10 sesiones con descuento	€120-900
Mentoría Mensual	monthly_subscription	4 sesiones/mes + soporte async	€200-500/mes
Programa Intensivo	intensive_program	Programa estructurado de 8-12 semanas	€500-2000
Consultoría Puntual	consulting_hour	Hora de consultoría sin compromiso	€75-200/hora
2.3 Niveles de Mentor
Nivel	Requisitos	Badge	Fee Plataforma
Mentor Base	Perfil completo + verificación	Verificado ✓	20%
Mentor Certificado	Certificación Método Jaraba™	Certificado ★	15%
Mentor Premium	Certificado + 50 sesiones + 4.5★ rating	Premium ★★	12%
Mentor Élite	Premium + 200 sesiones + caso éxito	Élite ★★★	10%
 
3. Arquitectura de Entidades
3.1 Entidad: mentor_profile
Extiende el perfil de usuario con información específica del mentor/consultor.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario Drupal asociado	FK users.uid, UNIQUE
tenant_id	INT	Tenant/programa si aplica	FK tenant.id, NULLABLE
display_name	VARCHAR(255)	Nombre profesional	NOT NULL
headline	VARCHAR(255)	Titular profesional	NOT NULL
bio	TEXT	Biografía extendida	NOT NULL
avatar	INT	Foto profesional	FK file_managed.fid
video_intro_url	VARCHAR(500)	Video de presentación	NULLABLE
specializations	JSON	Áreas de especialización	NOT NULL, array de strings
sectors	JSON	Sectores de expertise	NOT NULL, array de machine_names
business_stages	JSON	Fases de negocio que atiende	ENUM array: idea|validacion|crecimiento|escalado
languages	JSON	Idiomas que domina	NOT NULL
years_experience	INT	Años de experiencia	NOT NULL, >= 0
certifications	JSON	Certificaciones obtenidas	NULLABLE
is_jaraba_certified	BOOLEAN	Certificación Método Jaraba™	DEFAULT FALSE
certification_level	VARCHAR(24)	Nivel de certificación	ENUM: base|certified|premium|elite
hourly_rate	DECIMAL(8,2)	Tarifa por hora base €	NOT NULL, > 0
currency	CHAR(3)	Moneda	DEFAULT 'EUR', ISO 4217
stripe_account_id	VARCHAR(64)	Cuenta Stripe Connect	NULLABLE, acct_*
stripe_onboarding_complete	BOOLEAN	KYC completado	DEFAULT FALSE
platform_fee_percent	DECIMAL(5,2)	Comisión aplicable	DEFAULT 15.00
total_sessions	INT	Sesiones completadas	DEFAULT 0
average_rating	DECIMAL(3,2)	Rating promedio	COMPUTED, 0-5
total_reviews	INT	Número de reviews	DEFAULT 0
response_time_hours	DECIMAL(4,1)	Tiempo medio respuesta	COMPUTED
is_available	BOOLEAN	Disponible para nuevos	DEFAULT TRUE
status	VARCHAR(16)	Estado del perfil	ENUM: draft|pending|active|suspended
created	DATETIME	Creación	NOT NULL
changed	DATETIME	Modificación	NOT NULL
 
3.2 Entidad: mentoring_package
Define los productos/servicios que ofrece cada mentor.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
mentor_id	INT	Mentor propietario	FK mentor_profile.id, NOT NULL
title	VARCHAR(255)	Nombre del paquete	NOT NULL
description	TEXT	Descripción detallada	NOT NULL
package_type	VARCHAR(24)	Tipo de paquete	ENUM: single_session|session_pack|monthly_subscription|...
sessions_included	INT	Número de sesiones incluidas	NOT NULL, >= 1
session_duration_minutes	INT	Duración de cada sesión	DEFAULT 60
validity_days	INT	Días de validez del pack	NULLABLE
includes_async_support	BOOLEAN	Incluye soporte asíncrono	DEFAULT FALSE
async_response_hours	INT	SLA respuesta async	NULLABLE
price	DECIMAL(10,2)	Precio del paquete €	NOT NULL, > 0
currency	CHAR(3)	Moneda	DEFAULT 'EUR'
discount_percent	DECIMAL(5,2)	Descuento vs. sesiones sueltas	DEFAULT 0
is_featured	BOOLEAN	Destacado en perfil	DEFAULT FALSE
max_active_clients	INT	Límite de clientes activos	NULLABLE
current_active_clients	INT	Clientes activos ahora	DEFAULT 0
total_sold	INT	Veces vendido	DEFAULT 0
is_published	BOOLEAN	Visible para compra	DEFAULT TRUE
created	DATETIME	Creación	NOT NULL
3.3 Entidad: mentoring_engagement
Representa la relación activa entre un mentor y un emprendedor (compra de un paquete).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
mentor_id	INT	Mentor asignado	FK mentor_profile.id, NOT NULL
mentee_id	INT	Emprendedor cliente	FK users.uid, NOT NULL
package_id	INT	Paquete adquirido	FK mentoring_package.id, NOT NULL
tenant_id	INT	Tenant del programa	FK tenant.id, NULLABLE
order_id	INT	Pedido de Commerce	FK commerce_order.id, NULLABLE
payment_intent_id	VARCHAR(64)	Stripe PaymentIntent	NULLABLE
sessions_total	INT	Sesiones contratadas	NOT NULL
sessions_used	INT	Sesiones consumidas	DEFAULT 0
sessions_remaining	INT	Sesiones disponibles	COMPUTED
start_date	DATE	Fecha de inicio	NOT NULL
expiry_date	DATE	Fecha de expiración	NULLABLE
goals	TEXT	Objetivos del engagement	NULLABLE
business_diagnostic_id	INT	Diagnóstico de referencia	FK business_diagnostic.id, NULLABLE
status	VARCHAR(16)	Estado	ENUM: pending|active|paused|completed|expired|cancelled
completion_notes	TEXT	Notas de cierre	NULLABLE
created	DATETIME	Creación	NOT NULL
 
3.4 Entidad: availability_slot
Define los slots de disponibilidad del mentor para reservas.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
mentor_id	INT	Mentor propietario	FK mentor_profile.id, NOT NULL
day_of_week	INT	Día de la semana	0=Domingo, 1=Lunes... 6=Sábado
start_time	TIME	Hora de inicio	NOT NULL
end_time	TIME	Hora de fin	NOT NULL, > start_time
timezone	VARCHAR(64)	Zona horaria	DEFAULT 'Europe/Madrid'
is_recurring	BOOLEAN	Se repite semanalmente	DEFAULT TRUE
specific_date	DATE	Fecha específica si no recurring	NULLABLE
is_available	BOOLEAN	Slot activo	DEFAULT TRUE
 
4. Algoritmo de Matching Mentor-Emprendedor
El sistema implementa un algoritmo de scoring multidimensional para recomendar mentores ideales basado en el perfil del emprendedor.
4.1 Factores de Matching
Factor	Peso	Fuente	Cálculo
Sector	25%	business_diagnostic.sector vs mentor.sectors	100 si match exacto, 50 si relacionado, 0 si no
Fase de Negocio	20%	diagnostic.business_stage vs mentor.business_stages	100 si match, 0 si no
Especialización	20%	diagnostic.primary_gap vs mentor.specializations	100 si match directo, score parcial por similitud
Rating	15%	mentor.average_rating	(rating / 5) × 100
Disponibilidad	10%	availability_slots próximos 7 días	100 si > 5 slots, proporcional si menos
Precio	10%	mentor.hourly_rate vs presupuesto mentee	100 si dentro de rango, decrece exponencial
4.2 Fórmula de Score
Match_Score = Σ (Factor_Score × Peso) + Bonus_Certificación + Bonus_Idioma
•	Bonus Certificación Jaraba™: +15 puntos si is_jaraba_certified = true
•	Bonus Idioma: +10 puntos si idioma preferido del mentee en mentor.languages
•	Penalty Ocupación: -20 puntos si current_active_clients >= max_active_clients × 0.8
4.3 Output del Algoritmo
El sistema devuelve un ranking de mentores con:
•	Top 5 mentores recomendados con match_score descendente
•	Explicación de por qué cada mentor es buen fit
•	Disponibilidad próxima de cada mentor
•	Rango de precios y paquetes disponibles
 
5. Integración Stripe Connect para Pagos
5.1 Onboarding de Mentores
1.	Mentor completa perfil y solicita activar pagos
2.	Sistema crea cuenta Express en Stripe: createConnectedAccount()
3.	Genera link de onboarding KYC alojado en Stripe
4.	Mentor completa verificación de identidad y datos bancarios
5.	Webhook notifica account.updated → actualiza stripe_onboarding_complete
6.	Mentor puede recibir pagos automáticamente
5.2 Flujo de Pago (Destination Charges)
Pseudocódigo del servicio de pago:
function processMentoringPayment(package, mentee):   mentor = package.mentor   amount = package.price * 100  // céntimos   platformFee = amount * (mentor.platform_fee_percent / 100)      paymentIntent = stripe.paymentIntents.create({     amount: amount,     currency: 'eur',     application_fee_amount: platformFee,     transfer_data: { destination: mentor.stripe_account_id },     metadata: { package_id, mentee_id, mentor_id }   })      return paymentIntent.client_secret
5.3 Gestión de Reembolsos
Escenario	Política	Acción Técnica
Cancelación < 24h antes	Reembolso 100%	Full refund, reverse application_fee
Cancelación < 2h antes	Reembolso 50%	Partial refund
No-show del mentee	Sin reembolso	Sesión marcada como usada
No-show del mentor	Reembolso 100% + crédito	Full refund + bonus session
Pack no iniciado < 7 días	Reembolso 100%	Full refund, cancel engagement
 
6. Flujos de Automatización (ECA)
6.1 ECA-MNT-001: Alta de Nuevo Mentor
Trigger: Usuario con rol consultant crea mentor_profile
7.	Validar campos obligatorios completos
8.	Establecer status = 'pending' para revisión
9.	Notificar a admin para aprobación
10.	Enviar al mentor guía de buenas prácticas
6.2 ECA-MNT-002: Compra de Paquete
Trigger: payment_intent.succeeded para mentoring_package
11.	Crear mentoring_engagement con datos del pago
12.	Notificar a mentor: nuevo cliente asignado
13.	Notificar a mentee: paquete activado, enlace a reservar
14.	Crear financial_transaction con split correcto
15.	Incrementar package.total_sold y mentor.current_active_clients
6.3 ECA-MNT-003: Engagement Expirando
Trigger: Cron diario, engagement.expiry_date < NOW() + 7 días AND sessions_remaining > 0
16.	Notificar a mentee: quedan X sesiones y Y días
17.	Notificar a mentor: cliente por expirar
18.	Si expiry_date = TODAY: cambiar status a 'expired'
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/mentors	Listar mentores activos (filtros: sector, stage, price_range)
GET	/api/v1/mentors/{id}	Perfil completo del mentor con paquetes
GET	/api/v1/mentors/{id}/availability	Disponibilidad del mentor (próximos 30 días)
GET	/api/v1/mentors/{id}/reviews	Reviews del mentor paginadas
POST	/api/v1/mentors/match	Obtener matching de mentores para diagnóstico
GET	/api/v1/packages	Listar paquetes disponibles
GET	/api/v1/packages/{id}	Detalle de paquete
POST	/api/v1/packages/{id}/purchase	Iniciar compra de paquete (retorna client_secret)
GET	/api/v1/engagements	Mis engagements activos (mentee o mentor)
GET	/api/v1/engagements/{id}	Detalle de engagement con sesiones
PATCH	/api/v1/engagements/{id}	Actualizar engagement (goals, notes)
POST	/api/v1/mentor-profile	Crear perfil de mentor (rol consultant)
PATCH	/api/v1/mentor-profile	Actualizar mi perfil de mentor
POST	/api/v1/mentor-profile/stripe-onboarding	Iniciar onboarding Stripe
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades mentor_profile, mentoring_package. Migrations.	Stripe Connect config
Sprint 2	Semana 3-4	Sistema de disponibilidad. FullCalendar integration.	Sprint 1
Sprint 3	Semana 5-6	Algoritmo de matching. API de recomendaciones.	Sprint 2
Sprint 4	Semana 7-8	Flujo de compra. Stripe Destination Charges.	Sprint 3
Sprint 5	Semana 9-10	Engagements. Notificaciones. ECA rules. QA.	Sprint 4
8.1 KPIs de Éxito
KPI	Target	Medición
Mentores activos	> 20 en 6 meses	mentor_profile.status = 'active'
Tasa de matching	> 70%	% de búsquedas que resultan en compra
Rating medio	> 4.2/5	Promedio de reviews de sesiones
GMV de mentorías	> €10,000/mes	Volumen total procesado
Repeat rate	> 40%	% de mentees que compran segundo paquete
--- Fin del Documento ---
31_Emprendimiento_Mentoring_Core_v1.docx | Jaraba Impact Platform | Enero 2026
