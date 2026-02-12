VERTICAL PRICING MATRIX
Arquitectura de Precios por Vertical + Add-ons
Sistema Modular de Planes Base con Extensiones de Marketing
Versión:	1.0
Fecha:	Enero 2026
Código:	158_Platform_Vertical_Pricing_Matrix_v1
Estado:	Especificación Técnica para Implementación
Dependencias:	134_Stripe_Billing, 111_UsageBased_Pricing, 104_SaaS_Admin_Center
Integración:	Stripe Products, Prices, Subscriptions con Add-ons
1. Resumen Ejecutivo
Este documento define la arquitectura de precios modular de la Jaraba Impact Platform, donde cada vertical tiene sus propios planes base con funcionalidades específicas de su dominio, y los módulos de Marketing se ofrecen como Add-ons opcionales que pueden añadirse a cualquier plan. Este modelo permite máxima flexibilidad para el cliente y optimiza el revenue por cuenta mediante upsells naturales.
1.1 Principios del Modelo
•	Cada vertical tiene planes independientes ajustados a su mercado
•	Las funcionalidades core del vertical están en el plan base
•	Marketing AI Stack se ofrece como Add-ons modulares
•	Los Add-ons tienen precio fijo independiente del vertical
•	Compatibilidad definida: no todos los add-ons aplican a todos los verticales
•	Descuentos por bundle (Plan + múltiples Add-ons)
1.2 Estructura General
┌─────────────────────────────────────────────────────────────┐ │              SUSCRIPCIÓN TENANT                              │ ├─────────────────────────────────────────────────────────────┤ │  PLAN BASE VERTICAL          │  ADD-ONS MARKETING           │ │  ┌─────────────────────┐     │  ┌─────────────────────┐     │ │  │ Empleabilidad Pro   │     │  │ □ jaraba_crm        │     │ │  │ €79/mes             │  +  │  │ □ jaraba_email      │     │ │  │ Features del LMS,   │     │  │ □ jaraba_social     │     │ │  │ Job Board, etc.     │     │  │ □ events_webinars   │     │ │  └─────────────────────┘     │  │ □ ab_testing        │     │ │                              │  │ □ referral_program  │     │ │                              │  └─────────────────────┘     │ ├─────────────────────────────────────────────────────────────┤ │  TOTAL = Plan Base + Σ(Add-ons seleccionados)               │ └─────────────────────────────────────────────────────────────┘
 
2. Planes Base por Vertical
2.1 EMPLEABILIDAD
Plataforma de empleo, formación y desarrollo de carrera para buscadores de empleo y empresas.
Funcionalidad	Starter €29	Pro €79	Enterprise €149
LMS / Formación	5 cursos	50 cursos	Ilimitado
Learning Paths	❌	✓	✓ + Custom
Certificados automáticos	❌	✓	✓
Job Board	3 ofertas activas	25 ofertas	Ilimitado
Candidaturas/mes	50	500	Ilimitado
Employer Portal	Básico	Completo	+ API
CV Builder	1 plantilla	10 plantillas	Custom branding
Matching Engine	❌	✓ (básico)	✓ (IA avanzada)
AI Copilot	❌	50 consultas/mes	Ilimitado
Usuarios admin	2	10	Ilimitado
API Access	❌	Read-only	Full CRUD
White Label	❌	❌	✓
Soporte	Email	Chat + Email	Dedicado + SLA
2.2 EMPRENDIMIENTO
Plataforma de apoyo a emprendedores con diagnósticos, planes de acción y mentoría.
Funcionalidad	Starter €39	Pro €99	Enterprise €199
Diagnóstico Digital	Básico	Completo + IA	Custom + Benchmark
Análisis Competitivo	❌	3/mes	Ilimitado
Planes de Acción	1 activo	5 activos	Ilimitado
Business Model Canvas	✓	✓ + Versiones	✓ + Colaborativo
Proyecciones Financieras	❌	Básicas	Avanzadas + Escenarios
MVP Validation	❌	✓	✓ + A/B Testing
Mentoría	❌	Grupal (2h/mes)	Individual (4h/mes)
Networking Events	Acceso básico	Acceso completo	VIP + Organizar
AI Business Copilot	❌	100 consultas/mes	Ilimitado
Digital Kits	3 básicos	Todos	Todos + Custom
Usuarios equipo	1	5	Ilimitado
API Access	❌	❌	Full
 
2.3 AGROCONECTA
Marketplace de productos agroalimentarios de proximidad con trazabilidad.
Funcionalidad	Starter €49	Pro €129	Enterprise €249
Productos en catálogo	50	500	Ilimitado
Pedidos/mes	100	1,000	Ilimitado
Comisión plataforma	8%	5%	3% (negociable)
Trazabilidad QR	❌	✓	✓ + Blockchain
Certificaciones	Básicas	Todas	Custom + Verificación
Producer Portal	Básico	Completo	+ Multi-finca
Shipping Integration	Manual	Auto (3 carriers)	Custom + Frío
Analytics	Básicos	Avanzados	Custom + Export
Promotions/Coupons	3 activas	20 activas	Ilimitado
Partner Document Hub	❌	✓	✓ + Workflows
Multi-tienda	❌	❌	✓
API Access	❌	Read-only	Full CRUD
2.4 COMERCIOCONECTA
Sistema operativo de barrio para comercio de proximidad con experiencia phygital.
Funcionalidad	Starter €39	Pro €99	Enterprise €199
Productos en catálogo	100	1,000	Ilimitado
Pedidos/mes	200	2,000	Ilimitado
Comisión plataforma	6%	4%	2% (negociable)
POS Integration	❌	✓ (básico)	✓ (avanzado)
Flash Offers	5/mes	30/mes	Ilimitado
QR Dinámico	✓	✓ + Analytics	✓ + Custom
Local SEO	Básico	Optimizado	Premium + GMB
Click & Collect	✓	✓	✓ + Lockers
Reviews & Ratings	✓	✓ + Respuestas	✓ + Moderación IA
Merchant Portal	Básico	Completo	+ Multi-local
Mobile App	❌	PWA	App nativa
API Access	❌	Read-only	Full CRUD
 
2.5 SERVICIOSCONECTA
Plataforma para profesionales de servicios con reservas, facturación y gestión documental.
Funcionalidad	Starter €29	Pro €79	Enterprise €149
Servicios publicados	5	30	Ilimitado
Reservas/mes	50	500	Ilimitado
Comisión plataforma	10%	7%	4% (negociable)
Booking Engine	Básico	+ Calendar Sync	+ Multi-recurso
Video Conferencing	❌	✓ (Zoom/Meet)	✓ + Grabación
Buzón de Confianza	❌	✓	✓ + Workflows
Firma Digital PAdES	❌	10/mes	Ilimitado
Portal Cliente Documental	❌	✓	✓ + Personalizado
AI Triaje de Casos	❌	❌	✓
Presupuestador Auto	❌	✓ (básico)	✓ (IA)
Facturación	Manual	Semi-auto	Automática + SII
API Access	❌	❌	Full CRUD
 
3. Catálogo de Add-ons de Marketing
Los módulos de Marketing AI Stack se ofrecen como add-ons independientes que pueden añadirse a cualquier plan base. El precio es fijo independientemente del vertical, pero la compatibilidad puede variar.
3.1 Add-ons Principales
Add-on	Precio/mes	Funcionalidades Incluidas
jaraba_crm	€19	Pipeline B2B, contactos ilimitados, lead scoring, forecasting, actividades, integración FOC
jaraba_email	€29	5,000 emails/mes, 50 secuencias, 150 templates MJML, A/B en subject, analytics
jaraba_email_plus	€59	25,000 emails/mes, secuencias ilimitadas, IA para contenido, IP dedicada
jaraba_social	€25	5 cuentas sociales, calendario editorial, variantes IA, scheduling, analytics
3.2 Add-ons de Extensión
Add-on	Precio/mes	Funcionalidades Incluidas
paid_ads_sync	€15	Sync Meta Ads + Google Ads, ROAS tracking, audiencias, budget alerts
retargeting_pixels	€12	Pixel Manager multi-plataforma, server-side tracking, consent management
events_webinars	€19	5 eventos/mes, landing pages, Calendly + Zoom, certificados, replays
ab_testing	€15	Experimentos ilimitados, significancia estadística, auto-stop, segmentación
referral_program	€19	Códigos referido, recompensas configurables, leaderboard, niveles embajador
3.3 Bundles de Marketing
Paquetes pre-configurados con descuento sobre precio individual:
Bundle	Incluye	Precio	Ahorro
Marketing Starter	jaraba_email + retargeting_pixels	€35	15%
Marketing Pro	jaraba_crm + jaraba_email + jaraba_social	€59	20%
Marketing Complete	Todos los add-ons principales + extensiones	€99	30%
Growth Engine	jaraba_email_plus + ab_testing + referral_program	€79	15%
 
4. Matriz de Compatibilidad Add-ons × Verticales
No todos los add-ons tienen sentido en todos los verticales. Esta matriz define qué add-ons están disponibles y recomendados para cada vertical:
Add-on	Empleab.	Emprend.	Agro	Comercio	Servicios
jaraba_crm	✓	⭐	✓	✓	⭐
jaraba_email	⭐	⭐	⭐	⭐	⭐
jaraba_social	✓	⭐	⭐	⭐	✓
paid_ads_sync	✓	✓	⭐	⭐	✓
retargeting_pixels	✓	✓	⭐	⭐	✓
events_webinars	⭐	⭐	✓	✓	⭐
ab_testing	✓	✓	✓	⭐	✓
referral_program	⭐	⭐	⭐	⭐	✓
Leyenda: ⭐ = Recomendado (alto impacto en el vertical) | ✓ = Disponible | ❌ = No aplicable
4.1 Justificación de Recomendaciones
•	Empleabilidad + events_webinars: Webinars de orientación laboral, talleres CV, ferias de empleo virtuales
•	Emprendimiento + jaraba_crm: Pipeline de inversores, seguimiento de contactos, oportunidades de financiación
•	AgroConecta + retargeting: Recuperar carritos abandonados, audiencias de compradores recurrentes
•	ComercioConecta + ab_testing: Optimizar landing pages de ofertas flash, CTAs de reserva
•	ServiciosConecta + jaraba_crm: Gestión de clientes, seguimiento de casos, pipeline de presupuestos
5. Implementación Técnica en Stripe
5.1 Estructura de Productos Stripe
Cada plan base y cada add-on es un Product independiente en Stripe:
Product ID	Nombre	Tipo
prod_empleabilidad_starter	Empleabilidad Starter	Base Plan
prod_empleabilidad_pro	Empleabilidad Pro	Base Plan
prod_empleabilidad_enterprise	Empleabilidad Enterprise	Base Plan
prod_addon_crm	Add-on: jaraba_crm	Add-on
prod_addon_email	Add-on: jaraba_email	Add-on
prod_addon_social	Add-on: jaraba_social	Add-on
prod_bundle_marketing_pro	Bundle: Marketing Pro	Bundle
 
5.2 Modelo de Datos: tenant_subscription
Campo	Tipo	Descripción
id	SERIAL	Primary key
tenant_id	INT FK	Referencia al tenant
vertical	VARCHAR(30)	empleabilidad|emprendimiento|agroconecta|comercioconecta|serviciosconecta
plan_tier	VARCHAR(20)	starter|pro|enterprise
stripe_subscription_id	VARCHAR(100)	ID de suscripción en Stripe
stripe_customer_id	VARCHAR(100)	ID del customer en Stripe
base_price	DECIMAL(10,2)	Precio del plan base
addons_price	DECIMAL(10,2)	Suma de precios de add-ons
total_price	DECIMAL(10,2)	Total mensual (base + addons)
billing_cycle	VARCHAR(10)	monthly|yearly
status	VARCHAR(20)	active|past_due|canceled|trialing
trial_ends_at	TIMESTAMP	Fin del periodo de prueba
current_period_start	TIMESTAMP	Inicio del periodo actual
current_period_end	TIMESTAMP	Fin del periodo actual
created_at	TIMESTAMP	Fecha de creación
updated_at	TIMESTAMP	Última actualización
5.3 Modelo de Datos: tenant_addon
Campo	Tipo	Descripción
id	SERIAL	Primary key
subscription_id	INT FK	Referencia a tenant_subscription
addon_code	VARCHAR(50)	jaraba_crm|jaraba_email|jaraba_social|etc
stripe_subscription_item_id	VARCHAR(100)	ID del item en la suscripción Stripe
price	DECIMAL(10,2)	Precio mensual del add-on
status	VARCHAR(20)	active|canceled
activated_at	TIMESTAMP	Fecha de activación
canceled_at	TIMESTAMP	Fecha de cancelación (si aplica)
5.4 API: Gestión de Add-ons
Método	Endpoint	Descripción
GET	/api/v1/subscription	Obtener suscripción actual con add-ons
GET	/api/v1/subscription/addons/available	Listar add-ons disponibles para el vertical
POST	/api/v1/subscription/addons	Añadir un add-on a la suscripción
DELETE	/api/v1/subscription/addons/{code}	Cancelar un add-on
POST	/api/v1/subscription/upgrade	Upgrade de plan base
GET	/api/v1/subscription/invoice/upcoming	Preview de próxima factura
 
6. Servicio de Verificación de Acceso
El sistema debe verificar en tiempo real qué funcionalidades tiene acceso cada tenant basándose en su plan base y add-ons activos.
6.1 FeatureAccessService
// jaraba_billing/src/Service/FeatureAccessService.php  class FeatureAccessService {          public function canAccess(string $tenant_id, string $feature): bool {         $subscription = $this->getSubscription($tenant_id);                  // 1. Check base plan features         if ($this->isBasePlanFeature($feature, $subscription->plan_tier)) {             return true;         }                  // 2. Check add-on features         $addon_code = $this->getAddonForFeature($feature);         if ($addon_code && $this->hasActiveAddon($subscription, $addon_code)) {             return true;         }                  return false;     }          public function getActiveAddons(string $tenant_id): array {         return TenantAddon::where('subscription.tenant_id', $tenant_id)             ->where('status', 'active')             ->pluck('addon_code')             ->toArray();     }          public function checkLimit(string $tenant_id, string $metric): LimitResult {         // Returns: { allowed: bool, current: int, limit: int, upgrade_path: string }     } }
6.2 Mapeo Feature → Add-on
Feature	Requiere Add-on
crm_pipeline, crm_contacts, lead_scoring	jaraba_crm
email_campaigns, email_sequences, email_templates	jaraba_email
social_calendar, social_posts, social_analytics	jaraba_social
ads_sync, ads_audiences, roas_tracking	paid_ads_sync
pixels_manager, server_tracking, consent_mgmt	retargeting_pixels
events_create, webinar_integration, certificates	events_webinars
experiments, ab_variants, statistical_analysis	ab_testing
referral_codes, rewards, leaderboard	referral_program
7. UI: Selector de Add-ons
Interfaz en el panel de administración del tenant para gestionar add-ons:
┌─────────────────────────────────────────────────────────────────┐ │  MI SUSCRIPCIÓN                                    [Ver Factura] │ ├─────────────────────────────────────────────────────────────────┤ │                                                                  │ │  Plan Base: EMPLEABILIDAD PRO                           €79/mes  │ │  ──────────────────────────────────────────────────────────────  │ │                                                                  │ │  ADD-ONS ACTIVOS                                                 │ │  ┌─────────────────┐  ┌─────────────────┐                       │ │  │ ✓ jaraba_email  │  │ ✓ events_webinars│                       │ │  │   €29/mes       │  │   €19/mes        │                       │ │  │  [Configurar]   │  │  [Configurar]    │                       │ │  └─────────────────┘  └─────────────────┘                       │ │                                                                  │ │  ADD-ONS DISPONIBLES                              [Ver bundles]  │ │  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │ │  │ jaraba_crm      │  │ jaraba_social   │  │ ab_testing      │  │ │  │ €19/mes    ⭐   │  │ €25/mes         │  │ €15/mes         │  │ │  │  [+ Añadir]     │  │  [+ Añadir]     │  │  [+ Añadir]     │  │ │  └─────────────────┘  └─────────────────┘  └─────────────────┘  │ │                                                                  │ │  ──────────────────────────────────────────────────────────────  │ │  TOTAL MENSUAL:                                        €127/mes  │ │  Próxima factura: 15 Feb 2026                                    │ └─────────────────────────────────────────────────────────────────┘
 
8. Flujos ECA de Billing
8.1 ECA: Activación de Add-on
Trigger: POST /api/v1/subscription/addons
1.	Verificar que add-on es compatible con el vertical del tenant
2.	Verificar que tenant no tiene ya ese add-on activo
3.	Llamar Stripe API: subscription_items.create con price del add-on
4.	Crear registro en tenant_addon con status = 'active'
5.	Actualizar tenant_subscription.addons_price y total_price
6.	Limpiar cache de permisos del tenant
7.	Enviar email de confirmación con guía de configuración
8.	Registrar evento en FOC: addon_activated
8.2 ECA: Cancelación de Add-on
Trigger: DELETE /api/v1/subscription/addons/{code}
9.	Confirmar con usuario (modal de confirmación)
10.	Llamar Stripe API: subscription_items.delete
11.	Marcar tenant_addon.status = 'canceled', guardar canceled_at
12.	El acceso continúa hasta current_period_end
13.	Enviar email de confirmación de cancelación
14.	Programar eliminación de acceso al finalizar periodo
8.3 ECA: Upgrade de Plan Base
Trigger: POST /api/v1/subscription/upgrade
15.	Calcular prorrateo del periodo actual
16.	Llamar Stripe API: subscriptions.update con nuevo price
17.	Actualizar tenant_subscription con nuevo plan_tier y base_price
18.	Activar nuevas features del plan inmediatamente
19.	Enviar email de bienvenida al nuevo plan
20.	Registrar en FOC: plan_upgraded
9. Descuentos y Promociones
Tipo	Descuento	Implementación Stripe
Pago anual (plan base)	2 meses gratis	Price con billing_scheme = 'per_unit', recurring.interval = 'year'
Pago anual (add-ons)	15%	Price anual separado por add-on
Bundle discount	15-30%	Product tipo bundle con price propio
Código promocional	Variable	Stripe Coupons aplicados en checkout
Referido	1er mes gratis	Coupon 100% off, duration = 'once'
Early adopter	30% de por vida	Coupon duration = 'forever'
10. Métricas de Revenue
KPIs específicos para el modelo de pricing modular:
Métrica	Definición
ARPU (Avg Revenue Per User)	MRR total / Tenants activos
Addon Attach Rate	% de tenants con al menos 1 add-on activo
Avg Addons per Tenant	Total add-ons activos / Tenants activos
Addon Revenue %	MRR de add-ons / MRR total
Plan Mix	Distribución % entre Starter/Pro/Enterprise
Upgrade Rate	% de tenants que hacen upgrade en 90 días
Expansion MRR	MRR adicional de upgrades + nuevos add-ons
Net Revenue Retention	(MRR start + expansion - churn - contraction) / MRR start

