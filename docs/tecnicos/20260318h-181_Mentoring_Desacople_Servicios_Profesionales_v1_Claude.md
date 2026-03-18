
DESACOPLE DE MENTORIAS
Servicios Profesionales Independientes del Plan SaaS
Correccion arquitectonica + Especificacion tecnica para Claude Code


Campo	Valor
Codigo	181_Mentoring_Desacople_Servicios_Profesionales_v1
Version	1.0 (18 marzo 2026)
Estado	Correccion critica — Ejecutable por Claude Code
Modifica	Doc 158 v2 (Pricing), Doc 31-33 (Mentoring), Doc 134 (Stripe Billing)
Problema	Mentoria humana 1:1 incluida en plan SaaS destruye margen y no escala
Solucion	Desacoplar mentoria como servicio profesional con pricing independiente
Principio	Sin Humo: 'el codigo existe' vs 'el usuario lo experimenta'
Equipo	Claude Code (integramente)
 
INDICE
INDICE	1
1. Diagnostico del Problema	1
1.1 Modelo actual en Doc 158 (INCORRECTO)	1
1.2 Analisis economico detallado	1
2. Correccion del Pricing — Doc 158 v3	1
2.1 Emprendimiento — Planes SaaS corregidos	1
2.2 Cambios respecto a Doc 158 v2	1
2.3 Logica del Grupo Mastermind incluido en Pro	1
3. Aplicacion Cross-Vertical	1
4. Catalogo de Servicios Profesionales	1
4.1 Mentoria Individual 1:1	1
4.2 Programas Estructurados	1
4.3 Formatos Grupales (add-on, no incluido en plan)	1
4.4 Servicios Institucionales (PIIL/SAE)	1
4.5 Modelo economico — capacidad y revenue	1
5. Entidades Drupal	1
5.1 Nueva entidad: professional_service	1
5.2 Nueva entidad: service_booking	1
5.3 Entidad existente reutilizada: mentoring_session (Doc 32)	1
6. Productos Stripe	1
6.1 Productos de Servicios Profesionales (nuevos)	1
6.2 Stripe Connect para marketplace de mentores (Fase 2, 2027)	1
7. ProfessionalServiceManager — Servicio PHP	1
7.1 Interfaz del servicio	1
7.2 Flujo de compra — Stripe Checkout	1
7.3 Politica de reembolso	1
8. Rutas	1
9. Permisos	1
10. Flujos ECA	1
10.1 ECA-SVC-001: Compra completada	1
10.2 ECA-SVC-002: Sesion completada	1
10.3 ECA-SVC-003: Booking a punto de expirar	1
10.4 ECA-SVC-004: Upsell desde AI Copilot	1
11. Integracion con AI Business Copilot	1
11.1 Patron de escalacion IA -> Humano	1
11.2 Contexto compartido IA <-> Mentor	1
12. Experiencia de Usuario	1
12.1 Catalogo de servicios — /es/servicios-profesionales	1
12.2 Dashboard usuario — /es/mis-servicios	1
12.3 Dashboard mentor — /es/mentor/dashboard	1
13. Metricas FOC Especificas	1
14. Directrices para Claude Code	1
15. Cronograma de Implementacion	1

 
PARTE I — DIAGNOSTICO Y CORRECCION DEL PRICING

1. Diagnostico del Problema
ALERTA SIN HUMO: La mentoria humana 1:1 incluida en el plan Pro/Enterprise destruye el margen del fundador y crea una promesa no entregable a escala. Este documento corrige el modelo ANTES del lanzamiento.

1.1 Modelo actual en Doc 158 (INCORRECTO)
El Doc 158 v1/v2 define para Emprendimiento:
Feature	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR
Mentoria	NO	Grupal (2h/mes)	Individual (4h/mes)
AI Copilot	NO	100 consultas/mes	Ilimitado

Tres problemas criticos:
1.	MARGEN NEGATIVO: 1 sesion individual = 1,25h reales (45 min + 30 min contextualizacion). A 150 EUR/h valor mercado = 187,50 EUR de coste. Plan Enterprise cobra 199 EUR/mes e incluye 4h/mes = 750 EUR de coste de tiempo. Perdida neta: -551 EUR/usuario/mes.
2.	NO ESCALA: Con 20 usuarios Enterprise, Pepe dedicaria 80h/mes solo a mentorias. No quedan horas para dirigir la empresa.
3.	SUPPLY = CERO: El marketplace de mentores (Docs 31-33) requiere 20+ mentores activos. PED tiene cero mentores reclutados. El usuario ve un directorio vacio — viola el patron 'codigo existe vs usuario experimenta'.

1.2 Analisis economico detallado
Escenario	Usuarios	Horas mentoria/mes	Coste tiempo (150 EUR/h)	Ingreso SaaS	Resultado
5 Enterprise	5	20h	3.000 EUR	995 EUR	-2.005 EUR
10 Pro + 5 Enterprise	15	30h	4.500 EUR	1.985 EUR	-2.515 EUR
20 Pro + 10 Enterprise	30	60h	9.000 EUR	3.970 EUR	-5.030 EUR
50 Pro + 20 Enterprise	70	120h	18.000 EUR	8.930 EUR	-9.070 EUR

Conclusion: Cuantos mas clientes, mas dinero se pierde. El modelo actual es anti-escalable.
 
2. Correccion del Pricing — Doc 158 v3
El principio fundamental: el SaaS vende PLATAFORMA + IA. La mentoria humana se vende como SERVICIO PROFESIONAL independiente con precio que refleje valor real.

2.1 Emprendimiento — Planes SaaS corregidos
Funcionalidad	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR
Diagnostico Digital	Basico	Completo + IA	Custom + Benchmark
Analisis Competitivo	NO	3/mes	Ilimitado
Planes de Accion	1 activo	5 activos	Ilimitado
Business Model Canvas	SI	SI + Versiones	SI + Colaborativo
Proyecciones Financieras	NO	Basicas	Avanzadas + Escenarios
MVP Validation	NO	SI	SI + A/B Testing
44 Experimentos Osterwalder	5 primeros	Todos	Todos + Custom
AI Business Copilot	Modo learn (50 cons/mes)	5 modos (200 cons/mes)	5 modos (Ilimitado)
Grupo Mastermind mensual	NO	SI (1 sesion grupal/mes)	SI + Prioridad
Networking Events	Acceso basico	Acceso completo	VIP + Organizar
Digital Kits	3 basicos	Todos	Todos + Custom
Mentoria humana 1:1	NO INCLUIDA	NO INCLUIDA	NO INCLUIDA
Usuarios equipo	1	5	Ilimitado
API Access	NO	NO	Full CRUD
White Label	NO	NO	SI
Soporte	Email	Chat + Email	Dedicado + SLA

2.2 Cambios respecto a Doc 158 v2
Campo	ANTES (Doc 158 v2)	AHORA (Doc 181)	Razon
Mentoria Pro	Grupal (2h/mes) INCLUIDA	NO INCLUIDA en plan	Servicio profesional aparte
Mentoria Enterprise	Individual (4h/mes) INCLUIDA	NO INCLUIDA en plan	Servicio profesional aparte
AI Copilot Starter	NO	Modo learn (50 cons/mes)	Todos los planes deben tener IA — es el diferencial
AI Copilot Pro	100 consultas/mes	5 modos (200 cons/mes)	Ampliar: la IA reemplaza 80% de consultas humanas
Grupo Mastermind Pro	No existia	SI (1 sesion grupal/mes)	Sustituye mentoria individual por formato escalable
Experimentos Osterwalder	No diferenciado por plan	5/Todos/Todos+Custom	Valor progresivo claro

2.3 Logica del Grupo Mastermind incluido en Pro
El Grupo Mastermind mensual es la unica interaccion humana grupal incluida en el plan Pro. No es una mentoria individual — es una sesion grupal de 90 minutos con hasta 8 emprendedores, facilitada por Pepe Jaraba.
Concepto	Calculo	Resultado
Duracion sesion	90 minutos	1,5 horas
Participantes maximos	8 emprendedores	8
Frecuencia	1 vez al mes	12 al ano
Ingresos del grupo	8 personas x 99 EUR/mes = participacion proporcional	~792 EUR ingreso grupal
Coste tiempo Pepe	1,5h x 150 EUR/h	225 EUR
Margen por sesion grupal	792 - 225	567 EUR POSITIVO
Capacidad con 3 grupos/mes	24 emprendedores Pro, 4,5h/mes de Pepe	~2.376 EUR MRR

El formato grupal escala: 3 grupos de 8 = 24 usuarios Pro, solo 4,5h/mes de Pepe, margen >70%.
 
3. Aplicacion Cross-Vertical
El desacople de mentorias aplica a TODOS los verticales que prometan interaccion humana en sus planes. Revision vertical por vertical:
Vertical	Tiene mentoria en plan actual?	Accion	Impacto pricing
Empleabilidad	No (solo AI Copilot)	Sin cambio	Ninguno
Emprendimiento	SI — Grupal Pro + Individual Enterprise	CORREGIR: sacar mentoria, dejar solo Mastermind grupal en Pro	Quitar mentoria individual de Enterprise
ComercioConecta	No	Sin cambio	Ninguno
AgroConecta	No	Sin cambio	Ninguno
JarabaLex	No (solo IA Copilot legal)	Sin cambio	Ninguno
ServiciosConecta	No (los proveedores SON los mentores de sus clientes)	Sin cambio	Ninguno

Solo Emprendimiento se ve afectado. El resto de verticales ya tenian un modelo correcto (plataforma + IA, sin mentoria humana incluida).
 
PARTE II — CATALOGO DE SERVICIOS PROFESIONALES

4. Catalogo de Servicios Profesionales
Los servicios profesionales son una linea de revenue independiente del SaaS. Se venden como productos Stripe separados. Cualquier usuario de la plataforma (incluyendo plan Starter) puede contratar servicios profesionales.

4.1 Mentoria Individual 1:1
Producto	Precio	Duracion	Incluye	Stripe Product
Sesion suelta 1:1	175 EUR	45 min videollamada	Preparacion + sesion + notas + 1 tarea seguimiento	prod_mentoring_single
Pack 4 sesiones	595 EUR (149 EUR/sesion)	4 x 45 min	Todo lo de sesion suelta x4 + revision progreso	prod_mentoring_pack4
Pack 8 sesiones	1.095 EUR (137 EUR/sesion)	8 x 45 min	Todo x8 + informe de progreso trimestral	prod_mentoring_pack8

4.2 Programas Estructurados
Programa	Precio	Duracion	Estructura	Stripe Product
Programa Lanzamiento Digital	1.950 EUR	12 semanas	1 sesion/semana (12 total) + Copilot Pro + plan accion personalizado	prod_program_launch
Programa Aceleracion	2.950 EUR	12 semanas	2 sesiones/semana (24 total) + Canvas review + MVP coaching	prod_program_accelerate
Advisory Trimestral	1.200 EUR	3 meses	1 sesion cada 2 semanas (6 total) + revision estrategica	prod_program_advisory

4.3 Formatos Grupales (add-on, no incluido en plan)
Formato	Precio por persona	Participantes	Frecuencia	Stripe Product
Workshop tematico (2h)	79 EUR	6-12 personas	Puntual	prod_workshop_single
Mastermind Premium (trimestral)	295 EUR	6-8 personas	1 sesion/mes x 3 meses	prod_mastermind_premium
Bootcamp Emprendimiento (5 dias)	495 EUR	10-15 personas	Intensivo 1 semana	prod_bootcamp

4.4 Servicios Institucionales (PIIL/SAE)
Servicio	Precio	Contexto	Stripe Product
Mentoria en programa PIIL	528 EUR/participante	Precio regulado por PIIL (recibo 528 EUR)	prod_piil_mentoring
Programa a medida para incubadoras/CADE	A presupuesto	Precio por cohorte negociado con la institucion	Manual (no Stripe)
Formacion para AILs Puntos Vuela	A presupuesto	Formacion de formadores en digitalizacion	Manual (no Stripe)

4.5 Modelo economico — capacidad y revenue
Escenario mensual	Horas Pepe	Revenue servicios	Revenue SaaS (30 users)	Revenue TOTAL	Margen servicios
Conservador: 4 sesiones + 1 mastermind	6,5h	700 + 350 = 1.050 EUR	2.430 EUR	3.480 EUR	>80%
Moderado: 8 sesiones + 2 masterminds + 1 workshop	14h	1.190 + 700 + 790 = 2.680 EUR	4.860 EUR	7.540 EUR	>75%
Ambicioso: 12 sesiones + 3 masterminds + 1 programa	25h	1.785 + 1.050 + 1.950 = 4.785 EUR	7.290 EUR	12.075 EUR	>70%

Con 25h/mes dedicadas a servicios profesionales, el revenue es 4.785 EUR netos — vs los -5.030 EUR del modelo anterior con la misma dedicacion. Diferencia: 9.815 EUR/mes.
 
PARTE III — IMPLEMENTACION TECNICA PARA CLAUDE CODE

5. Entidades Drupal
5.1 Nueva entidad: professional_service
Representa un servicio profesional disponible para compra. NO es una sesion individual — es el 'producto' (equivalente a mentoring_package del Doc 31, pero desacoplado del plan SaaS).
Campo	Tipo	Descripcion	Restricciones
id	serial	ID interno	PK
uuid	uuid	UUID	UNIQUE
title	string(255)	Nombre del servicio	NOT NULL
type	list_string	single_session | pack | program | workshop | mastermind | bootcamp | institutional	NOT NULL
description	text_long	Descripcion completa del servicio	NOT NULL
price	decimal(10,2)	Precio en EUR	NOT NULL, > 0
currency	string(3)	Moneda	DEFAULT 'EUR'
duration_minutes	integer	Duracion de cada sesion en minutos	NOT NULL
total_sessions	integer	Numero total de sesiones incluidas	NOT NULL, >= 1
max_participants	integer	Maximo participantes (1 = individual)	DEFAULT 1
mentor_id	entity_reference(user)	Mentor que imparte el servicio	NOT NULL
stripe_product_id	string	Stripe Product ID	NOT NULL
stripe_price_id	string	Stripe Price ID	NOT NULL
vertical	list_string	emprendimiento | empleabilidad | cross_vertical	DEFAULT 'emprendimiento'
is_active	boolean	Servicio visible y comprable	DEFAULT TRUE
requires_plan	list_string	none | starter | pro | enterprise	DEFAULT 'none'
created	created	Timestamp creacion	AUTO
changed	changed	Timestamp modificacion	AUTO

5.2 Nueva entidad: service_booking
Representa la compra y reserva de un servicio profesional por un usuario. Vincula al comprador con el servicio, el pago Stripe y las sesiones individuales.
Campo	Tipo	Descripcion	Restricciones
id	serial	ID interno	PK
uuid	uuid	UUID	UNIQUE
service_id	entity_reference(professional_service)	Servicio comprado	FK, NOT NULL
buyer_uid	entity_reference(user)	Usuario que compra	FK, NOT NULL
tenant_id	entity_reference(group)	Tenant del comprador	FK, NOT NULL
mentor_id	entity_reference(user)	Mentor asignado	FK, NOT NULL
stripe_payment_intent_id	string	Stripe PaymentIntent o Checkout Session	NOT NULL
stripe_charge_id	string	Stripe Charge ID tras pago exitoso	NULLABLE
amount_paid	decimal(10,2)	Importe pagado en EUR	NOT NULL
platform_fee	decimal(10,2)	Comision plataforma (15-20%)	NOT NULL
mentor_payout	decimal(10,2)	Pago neto al mentor	NOT NULL
sessions_total	integer	Sesiones incluidas en la compra	NOT NULL
sessions_used	integer	Sesiones ya realizadas	DEFAULT 0
sessions_remaining	integer	Sesiones pendientes	COMPUTED
status	list_string	pending_payment | paid | active | completed | cancelled | refunded	DEFAULT 'pending_payment'
start_date	datetime	Fecha inicio del servicio	NULLABLE
expiry_date	datetime	Fecha limite para usar sesiones	NULLABLE
created	created	Timestamp	AUTO
changed	changed	Timestamp	AUTO

5.3 Entidad existente reutilizada: mentoring_session (Doc 32)
La entidad mentoring_session del Doc 32 se mantiene INTACTA. Lo unico que cambia es que ahora se vincula a service_booking en lugar de a mentoring_engagement. Esto permite reutilizar toda la logica de videollamadas (Jitsi), notas, tareas, reviews y calendario ya especificada.
Campo modificado	ANTES (Doc 32)	AHORA (Doc 181)	Razon
engagement_id	FK a mentoring_engagement	FK a service_booking	Desacople del plan SaaS
Todo lo demas	Sin cambio	Sin cambio	FullCalendar, Jitsi, notas, tareas, reviews — todo valido
 
6. Productos Stripe
6.1 Productos de Servicios Profesionales (nuevos)
Estos productos se crean en Stripe Dashboard o via API. Son pagos unicos (one-time), NO suscripciones. El SaaS (plan base + add-ons) sigue siendo suscripcion recurrente.
Stripe Product ID	Nombre	Precio	Tipo pago	Lookup key
prod_mentoring_single	Mentoria Individual - Sesion Suelta	175 EUR	One-time	mentoring_single_175
prod_mentoring_pack4	Mentoria Individual - Pack 4 Sesiones	595 EUR	One-time	mentoring_pack4_595
prod_mentoring_pack8	Mentoria Individual - Pack 8 Sesiones	1.095 EUR	One-time	mentoring_pack8_1095
prod_program_launch	Programa Lanzamiento Digital (12 sem)	1.950 EUR	One-time	program_launch_1950
prod_program_accelerate	Programa Aceleracion (12 sem)	2.950 EUR	One-time	program_accelerate_2950
prod_program_advisory	Advisory Trimestral (3 meses)	1.200 EUR	One-time	program_advisory_1200
prod_workshop_single	Workshop Tematico (2h)	79 EUR/persona	One-time	workshop_single_79
prod_mastermind_premium	Mastermind Premium (trimestral)	295 EUR/persona	One-time	mastermind_premium_295
prod_bootcamp	Bootcamp Emprendimiento (5 dias)	495 EUR/persona	One-time	bootcamp_495

6.2 Stripe Connect para marketplace de mentores (Fase 2, 2027)
Cuando se incorporen mentores externos (no Pepe), se usa Stripe Connect Destination Charges — exactamente como ya especifica el Doc 31. La comision de PED es 15-20%. En Fase 0 (solo Pepe), el pago va directamente a la cuenta de PED S.L. sin split.
Fase	Modelo Stripe	Split	Implementacion
Fase 0 (Pepe unico mentor)	PaymentIntent directo a cuenta PED	100% a PED	Stripe Checkout Session con product + price
Fase 1 (mentores PIIL peer)	Stripe Connect Express	80% mentor / 20% PED	Destination Charges: on_behalf_of + application_fee_amount
Fase 2 (marketplace abierto)	Stripe Connect Express	80-85% mentor / 15-20% PED	Mismo que Fase 1, mentores fijan precio
 
7. ProfessionalServiceManager — Servicio PHP
Nuevo servicio en jaraba_billing que gestiona el ciclo completo de servicios profesionales.
7.1 Interfaz del servicio
Metodo	Parametros	Retorno	Descripcion
listAvailableServices	?vertical, ?type, ?mentor_id	array<ProfessionalService>	Listar servicios activos con filtros
getServiceDetail	service_id	ProfessionalService	Detalle completo con disponibilidad del mentor
initiateBooking	service_id, buyer_uid, tenant_id	ServiceBooking	Crear booking + Stripe Checkout Session
confirmPayment	stripe_session_id	ServiceBooking	Webhook: confirmar pago, activar booking
scheduleSession	booking_id, slot_datetime	MentoringSession	Reservar sesion dentro de un booking activo
completeSession	session_id, notes, tasks	MentoringSession	Marcar sesion completada, crear tareas
cancelBooking	booking_id, reason	ServiceBooking	Cancelar con politica de reembolso
getBookingDashboard	user_uid	array	Resumen: bookings activos, sesiones pendientes, historial
getMentorDashboard	mentor_uid	array	Para el mentor: proximas sesiones, ingresos, reviews
generateInvoice	booking_id	string (PDF)	Generar factura del servicio profesional

7.2 Flujo de compra — Stripe Checkout
El flujo usa Stripe Checkout Session (modo payment, no subscription) para pagos unicos:
4.	Usuario navega al catalogo de servicios profesionales en /es/servicios-profesionales
5.	Selecciona un servicio (ej: Pack 4 Sesiones, 595 EUR)
6.	Sistema crea ServiceBooking con status = pending_payment
7.	Sistema crea Stripe Checkout Session: mode='payment', line_items=[{price: price_id, quantity: 1}]
8.	Usuario completa pago en Stripe Checkout (tarjeta, SEPA, etc.)
9.	Webhook checkout.session.completed: actualiza booking status = paid -> active
10.	Sistema envia email de confirmacion al usuario + notificacion al mentor
11.	Usuario accede a /es/mis-servicios para reservar su primera sesion

7.3 Politica de reembolso
Situacion	Politica	Implementacion Stripe
Cancelacion antes de primera sesion	100% reembolso	Refund completo via API
Cancelacion con sesiones usadas	Reembolso proporcional de sesiones NO usadas	Refund parcial: (sessions_remaining / sessions_total) * amount_paid
No-show del usuario	Se descuenta sesion, sin reembolso	No refund, incrementar sessions_used
No-show del mentor	Se reprograma sin coste, +1 sesion bonus	Crear sesion adicional en el booking
Despues de 6 meses sin usar	Sesiones expiran, sin reembolso	Cron: si expiry_date < NOW(), status = expired
 
8. Rutas
Ruta	Controller	Permiso	Descripcion
/es/servicios-profesionales	ProfessionalServiceController::catalog	access content	Catalogo publico de servicios
/es/servicios-profesionales/{id}	ProfessionalServiceController::detail	access content	Detalle de servicio con booking CTA
/es/servicios-profesionales/{id}/reservar	ProfessionalServiceController::checkout	authenticated	Iniciar Stripe Checkout
/es/mis-servicios	ServiceBookingController::myBookings	authenticated	Dashboard usuario: mis compras
/es/mis-servicios/{booking_id}	ServiceBookingController::bookingDetail	authenticated	Detalle booking con sesiones
/es/mis-servicios/{booking_id}/sesion/reservar	ServiceBookingController::scheduleSession	authenticated	Reservar slot de sesion
/es/admin/servicios-profesionales	ProfessionalServiceAdminController::dashboard	administer professional services	Admin: gestion servicios
/es/admin/servicios-profesionales/add	ProfessionalServiceAdminController::add	administer professional services	Crear nuevo servicio
/es/mentor/dashboard	MentorDashboardController::overview	mentor access	Dashboard del mentor
/es/mentor/sesiones	MentorDashboardController::sessions	mentor access	Proximas sesiones del mentor
/es/mentor/ingresos	MentorDashboardController::earnings	mentor access	Ingresos y pagos del mentor
 
9. Permisos
Permiso	Descripcion	Roles
access professional services	Ver catalogo de servicios profesionales	authenticated, anonymous
purchase professional services	Comprar servicios profesionales	authenticated
manage own bookings	Ver y gestionar mis bookings	authenticated
administer professional services	CRUD completo de servicios y bookings	administrator, platform_manager
mentor access	Acceso al dashboard de mentor	consultant (rol existente)
view mentor earnings	Ver ingresos propios como mentor	consultant
manage mentor availability	Gestionar disponibilidad de horarios	consultant

El rol consultant ya existe en el RBAC (Doc 04). Solo necesita los nuevos permisos asignados.
 
10. Flujos ECA
10.1 ECA-SVC-001: Compra completada
Trigger: Webhook checkout.session.completed (tipo = professional_service)
12.	Actualizar ServiceBooking: status = paid -> active
13.	Calcular expiry_date: created + 6 meses
14.	Enviar email al comprador: confirmacion + enlace a reservar primera sesion
15.	Enviar notificacion al mentor: nuevo cliente, perfil y diagnostico adjuntos
16.	Registrar en FOC: professional_service_purchased, amount, type
17.	Si comprador tiene plan Emprendimiento: enriquecer contexto del AI Copilot con datos del booking

10.2 ECA-SVC-002: Sesion completada
Trigger: Mentor marca sesion como completed
18.	Incrementar sessions_used en ServiceBooking
19.	Si sessions_remaining == 0: cambiar status a completed
20.	Enviar solicitud de review al comprador (delay 2h)
21.	Enviar recordatorio de notas al mentor (delay 24h si no completadas)
22.	Registrar en FOC: mentoring_session_completed
23.	Si sessions_remaining == 1: enviar notificacion 'Ultima sesion, renueva tu pack'

10.3 ECA-SVC-003: Booking a punto de expirar
Trigger: Cron diario, expiry_date < NOW() + 14 dias AND sessions_remaining > 0
24.	Enviar email al comprador: 'Te quedan X sesiones y Y dias para usarlas'
25.	Enviar notificacion push si PWA activo
26.	Si expiry_date < NOW(): cambiar status = expired, registrar en FOC

10.4 ECA-SVC-004: Upsell desde AI Copilot
Trigger: AI Copilot detecta pregunta compleja que requiere mentoria humana
27.	Copilot responde con su mejor conocimiento
28.	Al final de la respuesta, muestra CTA contextual: 'Para profundizar en este tema con un experto humano, reserva una sesion de mentoria'
29.	CTA enlaza a /es/servicios-profesionales con filtro por tema
30.	Registrar en FOC: copilot_mentoring_upsell_shown
31.	Si el usuario compra tras ver el CTA: registrar copilot_mentoring_conversion
 
11. Integracion con AI Business Copilot
El desacople crea una sinergia natural entre IA y mentoria humana. El Copilot es la primera linea de atencion (24/7, instantaneo, ilimitado en Pro). La mentoria humana es la segunda linea para casos que requieren experiencia, emocion o contexto profundo.

11.1 Patron de escalacion IA -> Humano
Situacion	Respuesta del Copilot	CTA de mentoria
Pregunta tecnica simple	Respuesta completa del Copilot	No mostrar CTA
Pregunta estrategica compleja	Respuesta del Copilot + 'Para una revision en profundidad...'	Mostrar CTA sesion suelta (175 EUR)
Crisis emocional del emprendedor	Empatia + recursos + 'Un mentor puede ayudarte...'	Mostrar CTA sesion suelta (175 EUR)
Revision de Canvas/Financieras	Feedback del Copilot + 'Un experto puede validar...'	Mostrar CTA pack 4 sesiones (595 EUR)
Emprendedor bloqueado >2 semanas	Detectar inactividad + 'Muchos emprendedores se desbloquean con...'	Mostrar CTA programa 12 semanas (1.950 EUR)

11.2 Contexto compartido IA <-> Mentor
Cuando un usuario tiene tanto suscripcion SaaS como servicios profesionales activos, el mentor recibe antes de cada sesion:
•	Resumen automatico del progreso en la plataforma (diagnostico, canvas, experimentos)
•	Ultimas 5 interacciones con el AI Copilot (temas, preguntas no resueltas)
•	Tareas pendientes de sesiones anteriores y su estado
•	Metricas del negocio del emprendedor (si las ha cargado)

Esto se implementa reutilizando el sistema de contexto del AI Copilot (Doc 44, Seccion 3.1) — los mismos datos que alimentan al Copilot alimentan el briefing del mentor.
 
12. Experiencia de Usuario
12.1 Catalogo de servicios — /es/servicios-profesionales
Pagina publica accesible sin login. Muestra tarjetas de servicios agrupadas por tipo. Cada tarjeta incluye: titulo, descripcion corta, precio, duracion, foto del mentor, rating, CTA 'Reservar'.
Estructura de la pagina:
•	Hero section: 'Mentoria experta para tu negocio — sesiones 1:1 con profesionales que han recorrido el camino'
•	Seccion 1: Sesiones individuales (single_session, packs)
•	Seccion 2: Programas estructurados (programs)
•	Seccion 3: Formatos grupales (workshops, masterminds, bootcamps)
•	Seccion FAQ: Preguntas frecuentes sobre mentorias
•	CTA final: 'No sabes que necesitas? Empieza con el Diagnostico Gratuito'

12.2 Dashboard usuario — /es/mis-servicios
Zona autenticada del usuario que ha comprado servicios profesionales:
•	Card resumen: bookings activos, sesiones pendientes, proxima sesion
•	Lista de bookings con status visual (activo/completado/expirado)
•	Por cada booking activo: boton 'Reservar siguiente sesion' (abre FullCalendar)
•	Historial de sesiones pasadas con notas y tareas
•	CTA de renovacion cuando sessions_remaining <= 1

12.3 Dashboard mentor — /es/mentor/dashboard
Dashboard del mentor (usa el slide-panel con renderPlain() — patron existente):
•	Proximas sesiones con nombre del cliente, hora, enlace Jitsi
•	Briefing pre-sesion auto-generado (datos plataforma + Copilot)
•	Ingresos del mes / trimestre / anno
•	Reviews recibidas con rating medio
•	Gestion de disponibilidad (FullCalendar editable)
 
13. Metricas FOC Especificas
Metrica	Definicion	Target
professional_services_revenue_mrr	Ingresos mensuales de servicios profesionales	500+ EUR (mes 1)
services_bookings_total	Total de bookings creados	10+ (Q3 2026)
sessions_completed_total	Total de sesiones realizadas	30+ (Q3 2026)
mentor_rating_avg	Rating medio de reviews de sesiones	>= 4.5/5
copilot_to_mentoring_conversion	% de CTAs de upsell que convierten en compra	5%+
session_no_show_rate	% de sesiones no-show	< 10%
pack_renewal_rate	% de compradores que compran segundo pack	30%+
services_to_saas_ratio	Revenue servicios / Revenue SaaS total	15-25%
mastermind_fill_rate	Ocupacion media de sesiones mastermind	>= 75% (6/8)
 
14. Directrices para Claude Code
Regla	Descripcion
MENTORING-DECOUPLE-001	La mentoria humana 1:1 NUNCA es una feature del plan SaaS. Es un servicio profesional independiente.
MENTORING-DECOUPLE-002	professional_service y service_booking pertenecen a jaraba_billing (NO crear modulo nuevo).
MENTORING-DECOUPLE-003	La entidad mentoring_session del Doc 32 se reutiliza INTACTA. Solo cambia la FK de engagement a service_booking.
MENTORING-DECOUPLE-004	Los pagos de servicios profesionales son ONE-TIME (Stripe Checkout mode=payment), NO suscripciones.
MENTORING-DECOUPLE-005	En Fase 0, todos los pagos van a cuenta PED directamente. Stripe Connect solo se activa cuando haya mentores externos (Fase 1+).
MENTORING-DECOUPLE-006	El AI Copilot muestra CTAs de upsell a servicios profesionales de forma contextual (no spam). Maximo 1 CTA por conversacion.
MENTORING-DECOUPLE-007	El Grupo Mastermind incluido en Pro es formato GRUPAL (hasta 8 personas, 90 min). NO es mentoria individual.
MENTORING-DECOUPLE-008	Precios de servicios profesionales son Config Entities editables desde admin (regla Doc 158 #131).
MENTORING-DECOUPLE-009	TENANT-001 aplica: cada service_booking tiene tenant_id FK.
MENTORING-DECOUPLE-010	ROUTE-LANGPREFIX-001 aplica: todas las rutas con /es/ prefix.

15. Cronograma de Implementacion
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidades professional_service + service_booking. Migrations. Config Entities para precios.	jaraba_billing existente
Sprint 2	Semana 3-4	Catalogo publico /es/servicios-profesionales. Stripe Checkout integration (one-time payments). Webhook handler.	Sprint 1 + Stripe config
Sprint 3	Semana 5-6	Dashboard usuario /es/mis-servicios. Reserva de sesiones (reutilizar FullCalendar Doc 32). Integracion con mentoring_session.	Sprint 2
Sprint 4	Semana 7-8	Dashboard mentor. Briefing auto pre-sesion. Metricas FOC. CTAs de upsell en AI Copilot.	Sprint 3
Sprint 5	Semana 9-10	Flujos ECA completos. Politica de reembolso. Emails transaccionales. QA end-to-end.	Sprint 4

Doc 181 v1 | Desacople Mentorias | PED S.L. | 18 marzo 2026 | Ejecutable por Claude Code
