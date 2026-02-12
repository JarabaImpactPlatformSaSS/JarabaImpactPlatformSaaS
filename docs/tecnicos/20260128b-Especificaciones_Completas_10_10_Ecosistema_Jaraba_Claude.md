
ESPECIFICACIONES TÉCNICAS COMPLETAS
ECOSISTEMA JARABA IMPACT PLATFORM
Paquete de Cierre de Gaps para Puntuación 10/10
Contenido:
178_Visitor_Journey_Complete_v1 • 179_Tenant_Onboarding_Wizard_v1
180_Landing_Pages_Verticales_v1 • 181_SaaS_Admin_UX_Complete_v1
182_Entity_Admin_Dashboard_v1 • 183_Freemium_Trial_Model_v1
184_Merchant_Copilot_v1 • 185_ECA_Registry_Master_v1
186_B2B_Sales_Flow_v1 • 187_Scaling_Infrastructure_v1
Preparado para: EDI Google Antigravity
Enero 2026 • Versión 1.0
 
DOCUMENTO 178
VISITOR JOURNEY COMPLETE
Especificación del Flujo Completo del Usuario No Autenticado
1. Resumen Ejecutivo
Este documento especifica el journey completo del visitante anónimo desde el primer contacto hasta la conversión a cliente de pago. Define cada touchpoint, decisión del usuario, respuesta del sistema e intervención de IA.
Campo	Valor
Código	178_Visitor_Journey_Complete_v1
Horas Estimadas	40-56 horas de desarrollo
Dependencias	100_Frontend_Architecture, 110_Onboarding_ProductLed, 149_Marketing_AI_Stack
Prioridad	CRÍTICA - Bloquea conversión PLG
2. Modelo AIDA del Funnel
El funnel sigue el modelo AIDA adaptado a SaaS con métricas específicas por etapa:
Etapa	Objetivo	Métrica Principal	Target
AWARENESS	Captar atención	Visitantes únicos/mes	> 10,000
INTEREST	Generar interés	Bounce rate	< 40%
DESIRE	Crear deseo	Lead magnet conversion	> 15%
ACTION	Provocar registro	Visitor-to-signup rate	> 5%
ACTIVATION	Primera acción de valor	Activation rate	> 60%
CONVERSION	Pago	Trial-to-paid rate	> 25%
3. Journey Map Detallado por Etapa
3.1 AWARENESS: Primer Contacto
Canal	Punto de Entrada	Landing Destino
SEO Orgánico	Búsqueda: 'digitalización pymes rurales'	/empleabilidad o /emprendimiento según keyword
GEO (IA Search)	ChatGPT/Perplexity pregunta sobre formación	Answer Capsule → Link a vertical
Social Ads	Facebook/Instagram segmentado por avatar	Landing específica por campaña
Referral	Link de referido: /r/{code}	Landing con código pre-aplicado
QR Físico	Evento, feria, folleto	Landing contextual del QR
Email Marketing	Newsletter, secuencia nurturing	Landing con UTM tracking
Trigger de Detección de Vertical
El sistema debe detectar automáticamente el vertical más relevante para el visitante:
// visitor_vertical_detection.js const detectVertical = (context) => {   // 1. UTM explícito tiene prioridad máxima   if (context.utm_vertical) return context.utm_vertical;      // 2. Keyword de búsqueda   const keywordMap = {     'empleo': 'empleabilidad',     'trabajo': 'empleabilidad',      'cv': 'empleabilidad',     'negocio': 'emprendimiento',     'empresa': 'emprendimiento',     'tienda': 'comercioconecta',     'comercio': 'comercioconecta',     'agricultor': 'agroconecta',     'productor': 'agroconecta',     'consultor': 'serviciosconecta',     'profesional': 'serviciosconecta'   };      // 3. Geolocalización para Agro   if (context.isRuralArea && context.regionType === 'agricultural') {     return 'agroconecta';   }      // 4. Default: mostrar selector   return 'selector'; };
3.2 INTEREST: Exploración
Una vez en la landing, el visitante explora. Cada acción tiene tracking y respuesta del sistema:
Acción Usuario	Evento Trackeado	Respuesta Sistema
Scroll > 50%	page_scroll_depth	Mostrar sticky CTA inferior
30s en pricing	pricing_hesitation	Popup: '¿Tienes dudas? Chatea con nosotros'
Click en caso de éxito	social_proof_click	Abrir modal con detalles + CTA
Hover en feature	feature_interest	Tooltip expandido con beneficio
Exit intent	exit_intent_detected	Modal: Lead magnet de última oportunidad
Volver sin conversión	return_visitor	Banner: 'Bienvenido de nuevo' + oferta
3.3 DESIRE: Lead Magnets por Vertical
Cada vertical tiene lead magnets específicos que generan valor inmediato:
Vertical	Lead Magnet Principal	Tiempo Valor	Conversión Target
Empleabilidad	Diagnóstico Express TTV (Time to Value)	< 3 minutos	> 20%
Emprendimiento	Calculadora de Madurez Digital	< 5 minutos	> 18%
AgroConecta	Guía: 'Vende Online sin Intermediarios'	Inmediato (PDF)	> 15%
ComercioConecta	Auditoría SEO Local Gratuita	< 2 minutos	> 22%
ServiciosConecta	Template: Propuesta Profesional	Inmediato (Docx)	> 12%
Flujo Post-Lead Magnet
1.	Usuario completa diagnóstico/descarga → Captura email obligatorio
2.	Sistema envía resultado por email + CTA 'Ver más detalles'
3.	Email 2 (24h): 'Basado en tu resultado, te recomendamos...'
4.	Email 3 (72h): Caso de éxito similar a su perfil
5.	Email 4 (7d): Oferta de prueba gratuita limitada
3.4 ACTION: Registro
Formulario de Registro Optimizado
Campos mínimos para reducir fricción, datos adicionales se capturan progresivamente:
Campo	Obligatorio	Validación	UX Hint
Email	Sí	Formato + dominio existente	Autocompletar de lead magnet
Nombre	Sí	Min 2 caracteres	'¿Cómo te llamamos?'
Contraseña	Sí	Min 8 chars, 1 número	Strength meter visual
Vertical	Auto-detectado	—	Selector visual si no detectado
Teléfono	No	Formato ES	'Para soporte prioritario'
Opciones de Registro Social
•	Google OAuth: Preferido, pre-rellena nombre y foto
•	LinkedIn OAuth: Para Empleabilidad, importa perfil profesional
•	Apple Sign-In: Requerido para iOS
3.5 ACTIVATION: Primer Valor
El 'Aha! Moment' varía por vertical. El onboarding guía hacia él:
Vertical	Aha! Moment	Tiempo Target
Empleabilidad	Ver ofertas de empleo recomendadas basadas en CV	< 5 minutos
Emprendimiento	Completar Business Model Canvas con IA	< 10 minutos
AgroConecta	Publicar primer producto con descripción IA	< 8 minutos
ComercioConecta	Crear QR dinámico para escaparate	< 5 minutos
ServiciosConecta	Configurar calendario y recibir primera reserva test	< 7 minutos
3.6 CONVERSION: Upgrade a Pago
Triggers de Upgrade
El sistema detecta señales de que el usuario está listo para pagar:
Señal	Acción Sistema	Conversión Esperada
Alcanza límite freemium	Modal: 'Has llegado al límite. Upgrade para continuar'	35%
Intenta usar feature Pro	Preview + 'Desbloquea con plan Pro'	28%
7 días de uso activo	Email: 'Tu trial termina en 7 días'	22%
Primera venta/match exitoso	Celebración + 'Maximiza resultados con Pro'	40%
Invita a colaborador	'Añade usuarios ilimitados con plan Growth'	30%
4. Especificación de Páginas Clave
4.1 Homepage Universal (selector de vertical)
Cuando no se detecta vertical, se muestra selector visual:
Estructura:
6.	Hero: 'Digitaliza tu futuro' + subtítulo por contexto
7.	Selector visual: 5 cards con icono, título, descripción corta
8.	Social proof: Logos de clientes + contador de usuarios
9.	Testimonios rotatorios por vertical
10.	CTA final: 'Empieza gratis' + 'Agenda una demo'
Wireframe: Selector de Vertical
┌─────────────────────────────────────────────────────────────┐ │  LOGO                      [Blog] [Recursos] [Contacto] [ES▾]│ ├─────────────────────────────────────────────────────────────┤ │                                                             │ │            DIGITALIZA TU FUTURO                             │ │     La plataforma que impulsa PYMEs rurales                 │ │                                                             │ │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐│ │  │  👤     │ │  🚀     │ │  🌾     │ │  🏪     │ │  💼     ││ │  │ EMPLEO  │ │EMPRENDE │ │  AGRO   │ │COMERCIO │ │SERVICIOS││ │  │Encuentra│ │ Lanza   │ │ Vende   │ │Digitaliza│ │ Gestiona││ │  │trabajo  │ │tu idea  │ │ online  │ │tu tienda│ │ citas   ││ │  │ [IR →]  │ │ [IR →]  │ │ [IR →]  │ │ [IR →]  │ │ [IR →]  ││ │  └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘│ │                                                             │ │  "Más de 2,500 profesionales ya confían en nosotros"       │ │  [Logo1] [Logo2] [Logo3] [Logo4] [Logo5]                    │ │                                                             │ ├─────────────────────────────────────────────────────────────┤ │  ⭐⭐⭐⭐⭐ "Encontré trabajo en 3 semanas" - María, 52    │ │  ← →                                                        │ ├─────────────────────────────────────────────────────────────┤ │  [EMPIEZA GRATIS]        [AGENDA UNA DEMO]                  │ └─────────────────────────────────────────────────────────────┘
5. Tracking y Analytics
Evento	Propiedades	Uso
page_view	url, referrer, vertical, utm_*	Funnel analysis
cta_click	cta_id, cta_text, position	A/B testing CTAs
lead_magnet_start	magnet_type, vertical	Engagement
lead_magnet_complete	magnet_type, result, time_spent	Conversion funnel
signup_start	method, vertical, source	Signup funnel
signup_complete	user_id, vertical, plan	Attribution
activation_milestone	milestone_id, time_to_reach	Product analytics
upgrade_trigger	trigger_type, context	Revenue optimization
6. Roadmap de Implementación
Sprint	Entregables	Horas	Dependencias
1	Tracking infrastructure + eventos base	12-16h	—
2	Homepage universal + selector vertical	16-20h	Sprint 1
3	Lead magnets integration	12-16h	Sprint 2
4	Signup flow optimizado + social auth	12-16h	Sprint 2
5	Upgrade triggers + pricing page	16-20h	Sprint 4
 
DOCUMENTO 179
TENANT ONBOARDING WIZARD
Flujo Completo de Configuración Inicial del Tenant
1. Resumen Ejecutivo
Este documento especifica el wizard de configuración que guía a un nuevo Tenant Admin desde el momento del registro hasta tener su espacio completamente operativo.
Campo	Valor
Código	179_Tenant_Onboarding_Wizard_v1
Horas Estimadas	32-40 horas de desarrollo
Dependencias	110_Onboarding_ProductLed, 06_Core_Flujos_ECA, Stripe Connect
KPI Principal	Onboarding completion rate > 70%
2. Estructura del Wizard: 7 Pasos
#	Paso	Objetivo	Tiempo	Obligatorio
1	Bienvenida	Confirmar vertical y expectativas	30s	Sí
2	Identidad	Logo, nombre comercial, colores	2 min	Sí
3	Datos Fiscales	NIF/CIF, dirección fiscal	2 min	Sí*
4	Pagos	Conectar Stripe Connect	3 min	Sí*
5	Equipo	Invitar colaboradores	1 min	No
6	Contenido Inicial	Crear primer producto/servicio	3 min	Sí
7	Lanzamiento	Publicar y celebrar	30s	Sí
* Obligatorio solo para verticales comerciales (Agro, Comercio, Servicios)
3. Especificación de Cada Paso
3.1 Paso 1: Bienvenida
Objetivo: Confirmar que el usuario está en el vertical correcto y establecer expectativas.
┌─────────────────────────────────────────────────────────────┐ │  [1]──[2]──[3]──[4]──[5]──[6]──[7]                          │ │   ●────○────○────○────○────○────○                           │ ├─────────────────────────────────────────────────────────────┤ │                                                             │ │   🎉 ¡Bienvenido a AgroConecta!                             │ │                                                             │ │   Estás a punto de crear tu tienda online para              │ │   vender tus productos directamente a consumidores.         │ │                                                             │ │   En los próximos 10 minutos configurarás:                  │ │   ✓ Tu marca e identidad visual                             │ │   ✓ Tus datos para facturar                                 │ │   ✓ Tu cuenta para recibir pagos                            │ │   ✓ Tu primer producto                                      │ │                                                             │ │   ┌─────────────────────────────────────────────────┐       │ │   │ ¿Es [AgroConecta] tu vertical? [Sí] [Cambiar]  │       │ │   └─────────────────────────────────────────────────┘       │ │                                                             │ │                                    [EMPEZAR →]              │ └─────────────────────────────────────────────────────────────┘
3.2 Paso 2: Identidad de Marca
Campos:
Campo	Tipo	Validación/UX
Logo	File upload	PNG/JPG/SVG, max 2MB. Preview en tiempo real. 'Puedes cambiarlo después'
Nombre comercial	Text	Max 60 chars. Check disponibilidad de subdominio
Descripción corta	Textarea	Max 160 chars. IA sugiere basándose en vertical
Color primario	Color picker	Extrae automático del logo si hay. Preview instantáneo
Color secundario	Color picker	Sugerencia complementaria automática
Agente IA: Si el usuario sube logo, la IA:
•	Extrae paleta de colores dominantes
•	Sugiere nombre comercial si está vacío (basado en metadata)
•	Valida contraste de colores para accesibilidad
3.3 Paso 3: Datos Fiscales
Campo	Tipo	Validación
Tipo persona	Radio	Física / Jurídica. Cambia campos siguientes
NIF/CIF	Text	Algoritmo validación español. Lookup en AEAT si API disponible
Razón social	Text	Solo si jurídica. Auto-fill de AEAT si match
Dirección fiscal	Address	Google Places autocomplete. Validar CP español
Email facturación	Email	Default: email de registro. Editable
3.4 Paso 4: Configuración de Pagos (Stripe Connect)
Flujo:
11.	Mostrar beneficios: 'Recibe pagos en 24-48h directamente en tu cuenta'
12.	Click 'Conectar Stripe' → Redirect a Stripe Connect Onboarding
13.	Stripe recoge: datos bancarios, verificación identidad, términos
14.	Callback a plataforma con account_id
15.	Verificar status: pending/active/restricted
// ECA: Stripe Connect Callback events:   - plugin: 'stripe_connect:account_updated' actions:   - plugin: 'eca:condition'     condition: '[stripe_account:status] == "active"'     then:       - plugin: 'eca_content:entity_update'         entity: '[tenant]'         values:           stripe_account_id: '[stripe_account:id]'           payment_status: 'active'       - plugin: 'eca:notify'         channel: 'email'         template: 'stripe_connected_success'     else:       - plugin: 'eca:notify'         channel: 'email'         template: 'stripe_pending_verification'
3.5 Paso 5: Invitar Equipo
Campos:
Campo	Tipo	Comportamiento
Email colaborador	Email (multi)	Añadir múltiples con Enter. Validar formato
Rol	Select	Admin / Editor / Viewer. Tooltip con permisos
Este paso es saltable con link 'Hazlo después'. Genera tag 'skipped_team_invite' para nurturing.
3.6 Paso 6: Contenido Inicial
Varía por vertical:
Vertical	Contenido a Crear	Asistencia IA
AgroConecta	Primer producto: nombre, foto, precio, descripción	Genera descripción de foto
ComercioConecta	3 productos destacados o categoría	Importar de Instagram si conecta
ServiciosConecta	Servicio principal con tarifa y duración	Templates por tipo profesional
Empleabilidad	Primer curso o ruta formativa	Estructura sugerida por IA
Emprendimiento	Primer diagnóstico o recurso	Clonar de biblioteca
3.7 Paso 7: Lanzamiento
Celebración:
•	Confetti animation al completar
•	Mensaje: '¡Tu [tienda/servicio] está lista!'
•	Preview de cómo se ve públicamente
•	Botones: 'Compartir en redes' + 'Ir a mi dashboard'
•	Email automático de bienvenida con checklist de próximos pasos
4. Progreso y Persistencia
El progreso se guarda en cada paso para permitir abandono y retorno:
// Entidad: tenant_onboarding_progress {   tenant_id: INT FK,   current_step: INT (1-7),   completed_steps: JSON [], // [1, 2, 3]   step_data: JSON {     step_2: { logo_fid: 123, name: 'Mi Tienda', colors: {...} },     step_3: { nif: 'B12345678', address: {...} },     ...   },   started_at: DATETIME,   completed_at: DATETIME NULL,   time_spent_seconds: INT,   skipped_steps: JSON [] // ['step_5'] }
5. Métricas de Onboarding
Métrica	Cálculo	Target
Completion Rate	Completados / Iniciados	> 70%
Average Time to Complete	Promedio time_spent	< 12 min
Drop-off por Paso	% abandono en cada step	< 15% por paso
Stripe Connect Rate	Conectados / Intentos	> 80%
Team Invite Rate	Invitaciones / Completados	> 30%
 
DOCUMENTO 180
LANDING PAGES POR VERTICAL
1. Resumen Ejecutivo
Este documento especifica las 5 landing pages específicas por vertical, optimizadas para conversión y SEO/GEO.
Campo	Valor
Código	180_Landing_Pages_Verticales_v1
Horas Estimadas	48-64 horas (5 landings × ~10-12h cada una)
Dependencias	100_Frontend_Architecture, 164_SEO_GEO_PageBuilder
2. Estructura Común de Landing
Todas las landings siguen estructura probada para conversión:
Sección	Componente	Contenido
1	Hero	Headline + Subheadline + CTA principal + Imagen/Video
2	Pain Points	3-4 problemas que resuelve (iconos + texto)
3	Solution	Cómo funciona en 3 pasos simples
4	Features	6-8 features con iconos y descripciones cortas
5	Social Proof	Testimonios + Logos + Métricas de impacto
6	Lead Magnet	CTA secundario: diagnóstico/guía gratuita
7	Pricing Preview	Desde X€/mes + 'Ver planes completos'
8	FAQ	5-7 preguntas frecuentes (Schema.org)
9	Final CTA	Repetición del CTA principal
3. Landing: AgroConecta
URL: /agroconecta
Meta Title: AgroConecta | Vende tus productos del campo directamente al consumidor
Target: Productores agroalimentarios, agricultores, ganaderos, artesanos rurales
Hero Section
Headline: "Vende tus productos del campo sin intermediarios" Subheadline: "Tu tienda online en 10 minutos. Cobra directamente.                Sin comisiones ocultas." CTA: [CREA TU TIENDA GRATIS] Imagen: Productor sonriente con caja de verduras + mockup de tienda en móvil
Pain Points
•	'Los intermediarios se quedan con el 40% de tu margen'
•	'No tienes tiempo para gestionar una web complicada'
•	'Tus clientes no saben que existes'
•	'Cobrar es un lío: transferencias, efectivo, recibos...'
Solution: 3 Pasos
16.	Sube tus productos: 'Con una foto, la IA escribe la descripción'
17.	Comparte tu tienda: 'Un link, un QR, redes sociales'
18.	Cobra al instante: 'El dinero llega a tu cuenta en 48h'
Features Destacadas
Feature	Beneficio para Productor
Producer Copilot (IA)	Escribe descripciones atractivas de tus productos automáticamente
QR de Trazabilidad	Tus clientes escanean y ven de dónde viene cada producto
Gestión de Stock	Actualiza disponibilidad desde el móvil mientras trabajas
Pedidos WhatsApp	Recibe notificaciones de pedidos donde ya estás
Cobro Seguro	Stripe procesa pagos. Sin preocuparte de fraudes
Certificaciones	Muestra tus sellos eco, denominación de origen, etc.
Testimonios
'Antes vendía solo en el mercado del pueblo. Ahora envío a toda España y facturo 3x más.' — Antonio, olivarero en Jaén
'La IA me escribió las descripciones mejor de lo que yo podría. En una tarde tenía 20 productos online.' — María, quesera en Extremadura
4. Landing: ComercioConecta
URL: /comercioconecta
Meta Title: ComercioConecta | Digitaliza tu comercio local y vende online
Target: Comercios de proximidad, tiendas de barrio, boutiques
Hero Section
Headline: "Tu tienda de barrio, ahora también en el móvil de tus clientes" Subheadline: "Ofertas flash, click & collect, pedidos online.                Todo integrado con tu TPV." CTA: [DIGITALIZA TU COMERCIO] Imagen: Comerciante en tienda mostrando QR a cliente
Pain Points
•	'Las grandes superficies se llevan a tus clientes'
•	'Tu tienda no aparece en Google cuando buscan cerca'
•	'No puedes competir con el ecommerce de los grandes'
•	'Gestionar redes sociales te quita tiempo de atender'
Features Específicas ComercioConecta
•	Ofertas Flash: Crea descuentos de última hora para productos que caducan
•	QR de Escaparate: Clientes ven precios y compran aunque esté cerrado
•	Click & Collect: Reservan online, recogen en tienda
•	Integración TPV: Sync con tu sistema de caja actual
•	SEO Local Automático: Apareces en 'tiendas cerca de mí'
•	Programa de Fidelización: Puntos por compra que retienen clientes
5. Landing: ServiciosConecta
URL: /serviciosconecta
Meta Title: ServiciosConecta | Gestiona tu consulta y cobra tus servicios online
Target: Profesionales liberales, consultores, coaches, terapeutas
Hero Section
Headline: "Más clientes, menos papeleo. Tu consulta digitalizada." Subheadline: "Agenda online, videollamadas, cobro automático,                firma digital. Todo en un solo lugar." CTA: [PROFESIONALIZA TU SERVICIO] Imagen: Profesional en videollamada con cliente
Features Específicas ServiciosConecta
•	Booking Engine: Tus clientes reservan 24/7 sin llamarte
•	Videoconsulta Integrada: Jitsi Meet sin salir de la plataforma
•	Firma Digital PAdES: Contratos con validez legal
•	Buzón de Confianza: Intercambio seguro de documentos
•	Presupuestador Automático: IA genera presupuestos personalizados
•	Facturación Automática: Emite facturas al confirmar el servicio
6. Landing: Empleabilidad
URL: /empleabilidad
Meta Title: Impulso Empleo | Formación y orientación laboral para encontrar trabajo
Target: Desempleados +45, profesionales en transición, personas con barreras digitales
Hero Section
Headline: "Nunca es tarde para reinventarte profesionalmente" Subheadline: "Formación práctica, CV que destaca, ofertas que encajan contigo.               Con ayuda de IA y mentores reales." CTA: [HAZ EL DIAGNÓSTICO GRATUITO] Imagen: Persona madura en entrevista exitosa
Features Específicas Empleabilidad
•	Diagnóstico Express: En 3 minutos sabes por dónde empezar
•	Rutas Formativas Personalizadas: Cursos adaptados a tu perfil
•	CV Builder con IA: Genera CV profesional de tus respuestas
•	Matching Inteligente: Ofertas que encajan con tu experiencia real
•	Simulador de Entrevistas: Practica con IA antes de la real
•	Credenciales Verificables: Certificados con blockchain
7. Landing: Emprendimiento
URL: /emprendimiento
Meta Title: Acelera Digital | Lanza y haz crecer tu negocio con metodología probada
Target: Emprendedores, PYMEs que quieren digitalizarse, startups rurales
Hero Section
Headline: "De idea a negocio rentable. Sin humo, sin atajos." Subheadline: "Metodología validada + herramientas digitales + mentoría experta.               Todo lo que necesitas para emprender con garantías." CTA: [EVALÚA TU MADUREZ DIGITAL] Imagen: Emprendedor trabajando en laptop con gráficos de crecimiento
Features Específicas Emprendimiento
•	Calculadora de Madurez Digital: Diagnóstico de tu nivel actual
•	Business Model Canvas con IA: La IA te ayuda a completarlo
•	Validación de MVP: Herramientas para testear antes de invertir
•	Mentoring 1:1: Sesiones con empresarios experimentados
•	Proyecciones Financieras: Plantillas para tu plan de negocio
•	Acceso a Financiación: Conexión con programas públicos y privados
 
DOCUMENTO 181
SaaS ADMIN UX COMPLETE
1. Resumen Ejecutivo
Este documento complementa el 104_SaaS_Admin_Center_Premium con wireframes, flujos de usuario y especificaciones UX detalladas que faltaban.
Campo	Valor
Código	181_SaaS_Admin_UX_Complete_v1
Horas Estimadas	24-32 horas
Dependencias	104_SaaS_Admin_Center_Premium, 13_FOC
2. Día en la Vida del Super Admin
Este flujo describe las tareas típicas del administrador de la plataforma:
8:00 - Morning Check (5 min)
19.	Abrir Admin Center → Dashboard automáticamente
20.	Revisar KPIs overnight: MRR delta, nuevos signups, churn alerts
21.	Verificar alertas críticas (badge rojo en sidebar)
22.	Acción: Click en alerta → Ver detalle → Resolver o delegar
9:00 - Tenant Triage (15 min)
23.	Ir a Tenants → Health Monitor
24.	Filtrar por Health Score < 60
25.	Para cada tenant at-risk: Ver actividad reciente
26.	Iniciar playbook de retención si aplica
27.	Anotar para seguimiento en CRM
10:00 - Approvals (10 min)
28.	Ir a Tenants → Pending Approval
29.	Revisar datos de cada nuevo tenant
30.	Verificar Stripe Connect status
31.	Aprobar o solicitar información adicional
14:00 - Financial Review (20 min)
32.	Ir a Finance → Revenue Dashboard
33.	Revisar MRR/ARR trends
34.	Analizar Churn MRR vs New MRR
35.	Si hay programa institucional: verificar Grant Burn Rate
36.	Exportar reporte para stakeholders si es fin de mes
16:00 - Support Escalation (variable)
37.	Recibir ticket Tier 3 de soporte
38.	Buscar usuario en Users → Directory
39.	Usar 'Impersonate' para ver exactamente lo que ve el usuario
40.	Diagnosticar problema en su contexto
41.	Resolver o escalar a desarrollo
3. Wireframe: Dashboard Principal
┌────────────────────────────────────────────────────────────────────────────┐ │ ☰ JARABA ADMIN                    🔍 Search (⌘K)   🔔(3)  👤 Admin ▾      │ ├──────────┬─────────────────────────────────────────────────────────────────┤ │          │ Dashboard > Overview                              [Exportar ▾] │ │ 📊 Dashboard│                                                             │ │  ├ Overview│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐          │ │  ├ KPIs    │ │   MRR    │ │  Tenants │ │   MAU    │ │  Churn   │          │ │  └ Alerts  │ │ €47,350  │ │   234    │ │  1,847   │ │  2.3%    │          │ │ 🏢 Tenants │ │  ↑ 12%   │ │  ↑ 8     │ │  ↑ 156   │ │  ↓ 0.4%  │          │ │ 👥 Users   │ └──────────┘ └──────────┘ └──────────┘ └──────────┘          │ │ 💰 Finance │                                                              │ │ 📈 Analytics│ ┌─────────────────────────────────────────────────────────┐ │ │ 🔔 Alerts  │ │                  Revenue Trend (12 months)              │ │ │ ⚙️ Settings│ │    ████                                                 │ │ │ 📋 Logs    │ │   █████      ████                                       │ │ │            │ │  ██████     ██████     █████████                        │ │ │            │ │ ████████   ████████   ███████████                       │ │ │            │ └─────────────────────────────────────────────────────────┘ │ │            │                                                              │ │            │ ┌─────────────────────┐ ┌─────────────────────────────────┐ │ │            │ │ 🔴 ALERTAS ACTIVAS  │ │ TOP TENANTS BY REVENUE          │ │ │            │ │                     │ │                                 │ │ │            │ │ ⚠️ Pago fallido      │ │ 1. Bodega Carmona    €890/mo  │ │ │            │ │    Bodega Carmona   │ │ 2. Aceites del Sur   €650/mo  │ │ │            │ │    hace 2h [Ver]    │ │ 3. Carnicería López  €520/mo  │ │ │            │ │                     │ │ 4. Consultora Ruiz   €480/mo  │ │ │            │ │ ⚠️ Churn risk (5)    │ │ 5. Academia Norte    €420/mo  │ │ │            │ │    [Ver todos]      │ │                                 │ │ │            │ └─────────────────────┘ └─────────────────────────────────┘ │ └──────────┴─────────────────────────────────────────────────────────────────┘
4. Command Palette (⌘K)
Acceso rápido a cualquier función del Admin Center:
Comando	Acción	Shortcut
go tenants	Navegar a lista de tenants	G + T
go users	Navegar a usuarios	G + U
go finance	Navegar a finanzas	G + F
search [query]	Buscar tenant/usuario	/
create tenant	Abrir modal nuevo tenant	C + T
export [type]	Exportar datos	E
impersonate [email]	Login como usuario	I
alerts	Ver alertas activas	A
help	Mostrar todos los comandos	?
5. Wireframe: Detalle de Tenant
┌────────────────────────────────────────────────────────────────────────────┐ │ ← Tenants    Bodega Carmona S.L.                    [Impersonate] [Edit]  │ ├────────────────────────────────────────────────────────────────────────────┤ │ ┌─────────────────────────────────────────────────────────────────────┐   │ │ │  🍷 BODEGA CARMONA S.L.                          Health Score: 78   │   │ │ │  AgroConecta • Plan Professional • Desde Marzo 2025                 │   │ │ │  ███████████████████████░░░░░░                                      │   │ │ └─────────────────────────────────────────────────────────────────────┘   │ │                                                                            │ │ [Overview] [Activity] [Billing] [Users] [Config] [Logs]                    │ │ ───────────────────────────────────────────────────────                    │ │                                                                            │ │ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐            │ │ │     MRR          │ │    Products      │ │    Orders/mo     │            │ │ │     €89          │ │      47          │ │      23          │            │ │ │    ↑ 5%          │ │    ↑ 12          │ │    ↓ 3           │            │ │ └──────────────────┘ └──────────────────┘ └──────────────────┘            │ │                                                                            │ │ HEALTH SCORE BREAKDOWN                                                     │ │ ┌──────────────────────────────────────────────────────────────────────┐  │ │ │ Engagement    ████████████████░░░░  80%   Good                       │  │ │ │ Revenue       ████████████░░░░░░░░  60%   Needs attention            │  │ │ │ Feature Use   ██████████████████░░  90%   Excellent                  │  │ │ │ Support       ████████████████████  100%  No tickets                 │  │ │ └──────────────────────────────────────────────────────────────────────┘  │ │                                                                            │ │ RECENT ACTIVITY                                                            │ │ ┌──────────────────────────────────────────────────────────────────────┐  │ │ │ 🕐 Hoy 14:23    Producto actualizado: Aceite Virgen Extra 5L         │  │ │ │ 🕐 Hoy 10:15    Login: admin@bodegacarmona.es                        │  │ │ │ 🕐 Ayer 18:42   Pedido completado: #AGR-2024-0847 (€127.50)          │  │ │ │ 🕐 Ayer 11:30   Nuevo usuario invitado: ventas@bodegacarmona.es      │  │ │ └──────────────────────────────────────────────────────────────────────┘  │ └────────────────────────────────────────────────────────────────────────────┘
 
DOCUMENTO 182
ENTITY ADMIN DASHBOARD
Dashboard para Avatar Elena (Administrador Institucional)
1. Resumen Ejecutivo
Este documento especifica el dashboard para el Avatar Elena: administradora de programas institucionales como 'Andalucía +ei'. Este perfil tiene necesidades únicas de reporting, justificación de fondos y gestión de cohortes.
Campo	Valor
Código	182_Entity_Admin_Dashboard_v1
Horas Estimadas	24-32 horas
Dependencias	13_FOC, 45_Andalucia_ei_Implementacion
Avatar Target	Elena - Administradora institucional 40-55 años
2. Necesidades Específicas del Avatar Elena
Necesidad	Solución en Dashboard
Justificar fondos públicos	Grant Burn Rate tracker + exportación automática de evidencias
Gestionar cohortes de alumnos	Vista de cohortes con progreso agregado y alertas de abandono
Reportes para auditoría	Generador de informes PDF con formato institucional
Supervisar tutores/mentores	Panel de actividad de formadores con métricas de calidad
Cumplir normativa SEPE	Checklist de compliance con alertas automáticas
Comunicación masiva	Herramienta de envío de notificaciones a cohortes
3. Estructura del Dashboard
┌────────────────────────────────────────────────────────────────────────────┐ │ 🏛️ ANDALUCÍA +ei                                    🔔  📊  👤 Elena ▾    │ ├──────────┬─────────────────────────────────────────────────────────────────┤ │          │                                                                 │ │ 📊 Panel │  PROGRAMA: ANDALUCÍA +ei 2025-2026                             │ │ 👥 Cohortes│ Convocatoria: Orden 15/2025 | Entidad: JARABA IMPACT SL       │ │ 📚 Formación│                                                              │ │ 👨‍🏫 Tutores │ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐         │ │ 📋 Evidencias│ │ ALUMNOS  │ │ PROGRESO │ │ BURN RATE│ │FINALIZ.  │         │ │ ⚠️ Alertas │ │   847    │ │   67%    │ │   54%    │ │   23%    │         │ │ 📈 Informes│ │ /900 obj │ │ media    │ │ del grant│ │ del prog │         │ │ ⚙️ Config  │ └──────────┘ └──────────┘ └──────────┘ └──────────┘         │ │           │                                                                │ │           │ ┌─────────────────────────────────────────────────────────┐   │ │           │ │ BURN RATE vs TIEMPO                    [Exportar PDF]  │   │ │           │ │                                                         │   │ │           │ │  100% ┤                              ╱─── Esperado      │   │ │           │ │       │                          ╱───                   │   │ │           │ │   50% ┤                      ╱───                       │   │ │           │ │       │  ████████████────────        ← Real (54%)       │   │ │           │ │    0% ┼───┬───┬───┬───┬───┬───┬───┬───┬                │   │ │           │ │       Ene Feb Mar Abr May Jun Jul Ago                   │   │ │           │ └─────────────────────────────────────────────────────────┘   │ │           │                                                                │ │           │ ⚠️ ALERTAS ACTIVAS                                             │ │           │ ┌─────────────────────────────────────────────────────────┐   │ │           │ │ 🔴 12 alumnos sin actividad >14 días [Ver lista]        │   │ │           │ │ 🟡 Documentación pendiente: 3 justificantes [Subir]     │   │ │           │ │ 🟢 Próxima auditoría en 45 días [Ver checklist]         │   │ │           │ └─────────────────────────────────────────────────────────┘   │ └──────────┴─────────────────────────────────────────────────────────────────┘
4. Módulo: Gestión de Cohortes
Vista	Contenido	Acciones
Lista de Cohortes	Nombre, fechas, alumnos, progreso medio	Crear, editar, archivar
Detalle de Cohorte	Lista alumnos con estado individual	Mover alumno, enviar comunicación
Comparativa	Métricas entre cohortes	Exportar comparativa
Riesgo Abandono	Alumnos con señales de churn	Iniciar intervención
5. Módulo: Grant Tracking
Seguimiento del gasto de subvención para justificación:
Métrica	Cálculo	Alerta si
Grant Burn Rate	Gastado / Total Grant × 100	Desvío >15% vs timeline
Coste por Alumno	Gasto total / Alumnos activos	Supera máximo autorizado
Documentación Pendiente	Gastos sin justificante	>5 sin documentar
Forecast Ejecución	Proyección a fin de programa	<90% o >110% del grant
6. Generador de Informes
Templates de informe pre-configurados para justificación:
•	Informe de Seguimiento Mensual: Alumnos, progreso, incidencias
•	Memoria Económica: Desglose de gastos por partida
•	Informe de Impacto: Métricas de inserción laboral / creación empresa
•	Justificación Técnica: Evidencias de actividad formativa
•	Certificados de Asistencia: Generación masiva por cohorte
 
DOCUMENTO 183
FREEMIUM & TRIAL MODEL
1. Resumen Ejecutivo
Este documento define el modelo freemium y trial para cada vertical, especificando qué puede hacer un usuario sin pagar y cómo se incentiva el upgrade.
Campo	Valor
Código	183_Freemium_Trial_Model_v1
Horas Estimadas	16-24 horas
Dependencias	158_Vertical_Pricing_Matrix, 111_UsageBased_Pricing
2. Estrategia General
Modelo híbrido: Freemium con límites + Trial de 14 días de features premium:
Modelo	Descripción	Objetivo
Freemium Forever	Acceso limitado permanente sin pagar	Adquisición masiva, viralidad
Trial Premium	14 días de plan Pro completo	Mostrar valor antes de pedir pago
Hybrid	Freemium + trial de features específicas	Conversión gradual
3. Límites Freemium por Vertical
3.1 AgroConecta
Feature	Free	Starter+
Productos publicados	5	Ilimitados
Fotos por producto	1	10
Producer Copilot (IA)	3 usos/mes	Ilimitado
Pedidos/mes	10	Ilimitados
Comisión plataforma	10%	5-8%
Dominio personalizado	No	Sí (Pro+)
QR de trazabilidad	Básico	Dinámico
3.2 ComercioConecta
Feature	Free	Starter+
Productos en catálogo	10	Ilimitados
QR de escaparate	1	Ilimitados
Ofertas flash activas	1	10+
Integración TPV	No	Sí
SEO local	Básico	Avanzado
Programa fidelización	No	Sí
3.3 ServiciosConecta
Feature	Free	Starter+
Servicios publicados	3	Ilimitados
Reservas/mes	10	Ilimitadas
Videoconsulta	No	Sí
Firma digital	No	Sí
Facturación automática	No	Sí
Recordatorios SMS	No	Sí
3.4 Empleabilidad
Feature	Free	Pro
Diagnóstico Express	1 vez	Ilimitado
Cursos gratuitos	Acceso	Acceso
Cursos premium	No	Sí
CV Builder IA	1 CV básico	Ilimitados + templates
Ofertas visibles	10/día	Todas
Simulador entrevista	No	Sí
Mentoría 1:1	No	Sí
3.5 Emprendimiento
Feature	Free	Pro
Calculadora Madurez	1 vez	Ilimitado + histórico
Business Model Canvas	1 borrador	Ilimitados + IA
Plantillas financieras	Básicas	Completas
Validación MVP	No	Sí
Mentoría	No	Sí
Networking events	No	Acceso
4. Triggers de Upgrade
Momentos específicos donde el sistema sugiere upgrade:
Trigger	Mensaje	Conversión Esperada
Límite alcanzado	'Has llegado a 5 productos. Upgrade para añadir más'	35%
Feature bloqueada	'La IA puede escribir esto por ti [Desbloquear]'	28%
Primera venta	'¡Felicidades! Reduce tu comisión al 5% con Pro'	42%
Competencia visible	'3 negocios similares usan features Pro'	22%
Tiempo en plataforma	'Llevas 30 días. ¿Listo para el siguiente nivel?'	18%
 
DOCUMENTO 184
MERCHANT COPILOT
Copiloto IA para ComercioConecta
1. Resumen Ejecutivo
Este documento especifica el Merchant Copilot, equivalente al Producer Copilot de AgroConecta pero adaptado a las necesidades del comercio local.
Campo	Valor
Código	184_Merchant_Copilot_v1
Horas Estimadas	20-28 horas
Dependencias	20_AI_Copilot (Empleabilidad), 44_AI_Business_Copilot (Emprendimiento)
Modelo IA	Claude API con RAG sobre catálogo del comercio
2. Capacidades del Merchant Copilot
Capacidad	Descripción	Input → Output
Descripción de Producto	Genera descripción atractiva para venta	Foto + nombre → Texto SEO
Pricing Sugerido	Sugiere precio basado en mercado local	Categoría + zona → Rango €
Post para Redes	Crea post para Instagram/Facebook	Producto → Copy + hashtags
Oferta Flash	Sugiere descuentos para producto lento	Stock + ventas → % descuento
Respuesta a Reseña	Genera respuesta profesional	Reseña → Respuesta
Email Promocional	Crea email para campaña	Ocasión → Email completo
3. System Prompt del Merchant Copilot
Eres el Merchant Copilot de ComercioConecta, un asistente especializado  en ayudar a comercios locales a vender más online.  CONTEXTO DEL COMERCIO: - Nombre: {merchant.name} - Sector: {merchant.category} - Ubicación: {merchant.city} - Productos: {merchant.product_count} - Valoración media: {merchant.avg_rating}  REGLAS ESTRICTAS: 1. Solo puedes hablar de productos que existen en el catálogo del comercio 2. Los precios que sugieras deben estar en el rango ±20% del actual 3. No inventes características que el producto no tenga 4. Mantén el tono cercano y local (no corporativo) 5. Incluye siempre call-to-action hacia la tienda  FORMATO DE RESPUESTAS: - Descripciones: 2-3 párrafos, máx 150 palabras - Posts sociales: 1 párrafo + 5-7 hashtags locales - Emails: Subject + Preview + Body + CTA
4. Ejemplos de Uso
4.1 Generación de Descripción
Input: Foto de vestido floral + 'Vestido primavera'
Output:
'Vestido floral de temporada perfecto para dar la bienvenida a la primavera. Tejido ligero y cómodo que te acompañará en esas primeras tardes de sol. Estampado exclusivo en tonos pastel que combina con todo. Disponible en tienda y online. ¡Pásate a probártelo o pídelo con entrega en 24h!'
4.2 Sugerencia de Oferta Flash
Contexto: Producto con stock alto y pocas ventas en 30 días
Output:
'⚡ OFERTA FLASH sugerida: Este producto lleva 30 días sin ventas con 15 unidades en stock. Recomiendo descuento del 25% durante 48h. Esto generaría urgencia y liberaría inventario antes de temporada.'
 
DOCUMENTO 185
ECA REGISTRY MASTER
1. Resumen Ejecutivo
Registro centralizado de todos los flujos ECA del ecosistema con IDs únicos, dependencias y estado de implementación.
2. Convención de Nomenclatura
ECA-{DOMINIO}-{NUMERO}  Dominios: - USR: Usuarios - ORD: Pedidos/Commerce   - FIN: FOC/Financiero - TEN: Tenants - AI:  Inteligencia Artificial - WH:  Webhooks - MKT: Marketing - LMS: Learning - JOB: Empleabilidad - BIZ: Emprendimiento  Ejemplo: ECA-ORD-003 = Flujo #3 del dominio Pedidos
3. Catálogo Completo de Flujos ECA
3.1 Flujos Core (Transversales)
ID	Nombre	Trigger	Doc Ref
ECA-USR-001	Onboarding Usuario Nuevo	user_insert	06_Core
ECA-USR-002	Asignación Rol por Diagnóstico	diagnostic_completed	06_Core
ECA-USR-003	Welcome Email	user_insert	06_Core
ECA-TEN-001	Tenant Onboarding	group_insert	06_Core
ECA-TEN-002	Stripe Connect Completado	stripe_account_updated	06_Core
ECA-FIN-001	Alerta Churn Spike	metric_threshold	13_FOC
ECA-FIN-002	Revenue Acceleration	metric_opportunity	13_FOC
ECA-FIN-003	Grant Burn Rate Warning	metric_threshold	13_FOC
3.2 Flujos Marketing
ID	Nombre	Trigger	Doc Ref
ECA-MKT-001	Lead Magnet Completed	webform_submit	145_AC
ECA-MKT-002	Onboarding Email Day 1	user_insert + 0h	145_AC
ECA-MKT-003	Onboarding Email Day 3	user_insert + 72h	145_AC
ECA-MKT-004	Churn Risk Sequence	health_score < 40	145_AC
ECA-MKT-005	Referral Code Generated	user_insert	157_Referral
ECA-MKT-006	Referral Conversion	order_complete + ref_code	157_Referral
3.3 Flujos Commerce
ID	Nombre	Trigger	Doc Ref
ECA-ORD-001	Orden Completada	commerce_order_complete	49_Order
ECA-ORD-002	Carrito Abandonado	cron (24h)	49_Order
ECA-ORD-003	Reembolso Procesado	refund_created	49_Order
ECA-ORD-004	Stock Bajo	product_stock < threshold	48_Catalog
ECA-ORD-005	Review Recibida	review_created	54_Reviews
ECA-ORD-006	Payout Completado	stripe_payout_paid	50_Checkout
 
DOCUMENTO 186
B2B SALES FLOW COMPLETE
1. Resumen Ejecutivo
Este documento especifica el flujo de ventas B2B completo, desde la generación de lead hasta el cierre de contrato, con integración en jaraba_crm.
2. Pipeline de Ventas B2B
Etapa	Nombre	Criterio de Entrada	Probabilidad
1	Lead	Contacto identificado con interés inicial	10%
2	MQL	Completó lead magnet o solicitó info	20%
3	SQL	Cualificado por ventas (BANT validado)	40%
4	Demo	Demo programada o realizada	60%
5	Propuesta	Propuesta enviada y revisada	75%
6	Negociación	Discusión de términos activa	85%
7	Cerrado Ganado	Contrato firmado	100%
X	Cerrado Perdido	Oportunidad descartada	0%
3. Criterios BANT para Cualificación
Criterio	Pregunta Clave	Indicador Positivo
Budget	¿Tiene presupuesto asignado para esta solución?	Sí, o puede conseguirlo en <3 meses
Authority	¿Quién toma la decisión de compra?	Habla con decisor o influencer directo
Need	¿Qué problema urgente resuelve esto?	Problema identificado con impacto medible
Timeline	¿Cuándo necesita tenerlo implementado?	<6 meses, idealmente <3
4. Playbook de Ventas por Etapa
4.1 Lead → MQL
Objetivo: Nutriir hasta que muestre interés explícito
•	Secuencia de email automatizada (5 emails en 30 días)
•	Retargeting con casos de éxito del sector
•	Invitación a webinar sectorial
Exit Criteria: Solicita demo O descarga 2+ recursos O responde email
4.2 MQL → SQL
Objetivo: Validar BANT mediante llamada de descubrimiento
•	Llamada de 15-20 min con framework SPIN
•	Identificar pain points específicos
•	Confirmar timeline y presupuesto
Exit Criteria: BANT completo con score ≥ 3/4
4.3 SQL → Demo
Objetivo: Mostrar valor específico para su caso
•	Demo personalizada con datos de su sector
•	Incluir ROI estimado para su situación
•	Enviar recording + resumen por email
Exit Criteria: Solicita propuesta O pide segunda demo con más stakeholders
4.4 Demo → Propuesta
Objetivo: Propuesta irresistible y clara
•	Propuesta personalizada con 3 opciones de plan
•	Incluir testimonios de clientes similares
•	Oferta limitada si aplica (descuento early adopter)
Exit Criteria: Feedback positivo O negociación de términos
 
DOCUMENTO 187
SCALING INFRASTRUCTURE
1. Resumen Ejecutivo
Este documento especifica la estrategia de escalado horizontal de la infraestructura cuando se superen umbrales de uso.
Campo	Valor
Código	187_Scaling_Infrastructure_v1
Horas Estimadas	24-32 horas de documentación + config
Dependencias	131_Infrastructure_Deployment, 133_Monitoring_Alerting
2. Umbrales de Escalado
Métrica	Umbral Warn	Umbral Critical	Acción
Tenants activos	200	300	Escalar DB
Requests/segundo	100	200	Añadir nodo app
CPU servidor	70%	85%	Escalar vertical
RAM servidor	75%	90%	Escalar vertical
Storage usado	70%	85%	Expandir disco
Qdrant vectores	1M	2M	Escalar Qdrant
3. Arquitectura de Escalado Horizontal
FASE 1: Single Server (Actual) ┌─────────────────────────────────────┐ │  IONOS Dedicated L-16 NVMe          │ │  ├── Drupal 11 (PHP-FPM)            │ │  ├── MariaDB 10.6                   │ │  ├── Redis 7 (Cache)                │ │  ├── Qdrant (Vectores)              │ │  └── Nginx (Reverse Proxy)          │ └─────────────────────────────────────┘  FASE 2: Separated DB (300+ tenants) ┌─────────────────┐     ┌─────────────────┐ │  App Server     │────▶│  DB Server      │ │  Drupal + Redis │     │  MariaDB Master │ └─────────────────┘     └─────────────────┘                               │                         ┌─────────────────┐                         │  DB Replica     │                         │  MariaDB Slave  │                         └─────────────────┘  FASE 3: Load Balanced (500+ tenants)          ┌─────────────────┐          │  Load Balancer  │          │  (HAProxy)      │          └────────┬────────┘       ┌───────────┼───────────┐       ▼           ▼           ▼ ┌──────────┐ ┌──────────┐ ┌──────────┐ │  App 1   │ │  App 2   │ │  App 3   │ └──────────┘ └──────────┘ └──────────┘       │           │           │       └───────────┼───────────┘                   ▼          ┌─────────────────┐          │  DB Cluster     │          │  Galera / Aurora│          └─────────────────┘
4. Backup y Restore por Tenant
4.1 Estrategia de Backup
Tipo	Frecuencia	Retención	Storage
Full DB	Diario (3:00 AM)	30 días	S3
Incremental	Cada hora	7 días	Local + S3
Files (uploads)	Diario	30 días	S3
Config (YAML)	En cada cambio	Ilimitado	Git
4.2 Restore por Tenant Individual
Gracias al soft multi-tenancy, es posible restaurar datos de un solo tenant sin afectar a otros:
# Script: restore_tenant.sh #!/bin/bash TENANT_ID=\$1 BACKUP_DATE=\$2  # 1. Identificar registros del tenant en todas las tablas TABLES=\$(drush sql-query "SELECT table_name FROM information_schema.columns           WHERE column_name = 'tenant_id' AND table_schema = 'jaraba'")  # 2. Para cada tabla, restaurar solo registros del tenant for TABLE in \$TABLES; do   # Exportar registros actuales (por si rollback)   drush sql-dump --tables-list=\$TABLE --where="tenant_id=\$TENANT_ID" > /tmp/current_\$TABLE.sql      # Eliminar registros actuales del tenant   drush sql-query "DELETE FROM \$TABLE WHERE tenant_id = \$TENANT_ID"      # Importar desde backup   mysql jaraba < /backups/\$BACKUP_DATE/\$TABLE_tenant_\$TENANT_ID.sql done  # 3. Restaurar archivos del tenant rsync -av /backups/\$BACKUP_DATE/files/tenant_\$TENANT_ID/ /var/www/files/tenant_\$TENANT_ID/  # 4. Limpiar caches drush cr
5. Performance Testing
5.1 Escenarios de Test
Escenario	Usuarios Concur.	Duración	Métrica Target
Baseline	50	10 min	p95 < 200ms
Normal Load	200	30 min	p95 < 500ms
Peak Load	500	15 min	p95 < 1s
Stress Test	1000	10 min	Sin errores 5xx
Endurance	200	4 horas	No memory leak
5.2 Herramientas de Testing
•	k6: Load testing scriptable
•	Grafana: Visualización de métricas durante tests
•	Blackfire: Profiling de PHP
•	New Relic / Datadog: APM en producción
 
CONCLUSIÓN Y SIGUIENTES PASOS
Resumen de Entregables
Doc	Nombre	Horas	Gap Cerrado
178	Visitor Journey Complete	40-56h	UX Visitante
179	Tenant Onboarding Wizard	32-40h	UX Tenant
180	Landing Pages Verticales	48-64h	UX Visitante
181	SaaS Admin UX Complete	24-32h	UX Admin
182	Entity Admin Dashboard	24-32h	UX Tenant
183	Freemium/Trial Model	16-24h	UX Visitante
184	Merchant Copilot	20-28h	Consistencia
185	ECA Registry Master	8-12h	Consistencia
186	B2B Sales Flow	16-20h	Arq. Negocio
187	Scaling Infrastructure	24-32h	Arq. Técnica
Inversión Total Estimada
Concepto	Horas	Coste (€65/h)
Total Documentos 178-187	252-340h	€16,380-22,100
Implementación (×1.5 de spec)	378-510h	€24,570-33,150
Testing + QA	80-120h	€5,200-7,800
TOTAL PROYECTO	710-970h	€46,150-63,050
Nueva Puntuación Proyectada
Dimensión	Antes	Después	Delta
Arquitectura de Negocio	8.5	10.0	+1.5
Arquitectura Técnica	9.0	10.0	+1.0
Consistencia Funcional	7.5	10.0	+2.5
UX Admin SaaS	6.5	10.0	+3.5
UX Tenant Admin	7.0	10.0	+3.0
UX Usuario Visitante	6.0	10.0	+4.0
— Fin del Documento de Especificaciones —
