
VERTICAL PRICING MATRIX
Arquitectura de Precios por Vertical + Add-ons
Con Analisis Competitivo y Justificacion de Mercado


Campo	Valor
Version	2.0 — Enriquecida con inteligencia competitiva (18 marzo 2026)
Codigo	158_Platform_Vertical_Pricing_Matrix_v2
Estado	Especificacion Tecnica para Implementacion — Auditada contra mercado
Dependencias	134_Stripe_Billing, 111_UsageBased_Pricing, 104_SaaS_Admin_Center
Integracion	Stripe Products, Prices, Subscriptions con Add-ons
Fuentes competitivas	60+ competidores analizados en 10 verticales (investigacion 18-03-2026)
Regla de Oro #131	Precios = Doc 158 SSOT. Jaraba NO hardcodea precios, siempre Config Entities editables
 
INDICE
INDICE	1
1. Resumen Ejecutivo	1
1.1 Principios del Modelo	1
1.2 Tamano de Mercado Accesible	1
2. Planes Base por Vertical	1
2.1 EMPLEABILIDAD — LMS + Job Board + Matching + Credenciales	1
2.2 EMPRENDIMIENTO — Diagnostico + Canvas + Validacion + Mentoria + AI Copilot v2	1
2.3 AGROCONECTA — Marketplace + Trazabilidad QR + Storytelling + Certificaciones	1
2.4 COMERCIOCONECTA — Sistema Operativo de Barrio Phygital	1
2.5 SERVICIOSCONECTA — Plataforma Integral para Profesionales de Servicios	1
3. Catalogo de Add-ons de Marketing (9 items)	1
3.1 Add-ons Principales	1
3.2 Add-ons de Extension	1
3.3 Bundles de Marketing (4 paquetes)	1
4. Matriz de Compatibilidad Add-ons x Verticales	1
4.1 Justificacion de Recomendaciones Clave	1
5. Canal Kit Digital — Financiacion Publica de Suscripciones	1
6. Pricing Institucional (Andalucia +ei / PIIL)	1
7. Verticales Composables — Arquitectura Cross-Vertical	1
7.1 Escenarios de Upsell Cross-Vertical	1
8. Implementacion Tecnica en Stripe	1
8.1 Estructura de Productos Stripe	1
9. Descuentos y Promociones	1
10. Metricas de Revenue	1

 
1. Resumen Ejecutivo
Este documento define la arquitectura de precios modular de la Jaraba Impact Platform. Cada vertical tiene planes base independientes con funcionalidades especificas de su dominio, y los modulos de Marketing se ofrecen como Add-ons opcionales. Esta version v2 enriquece cada vertical con inteligencia competitiva real de 60+ competidores analizados el 18 de marzo de 2026, justificando cada punto de precio contra el mercado.
1.1 Principios del Modelo
•	Cada vertical tiene planes independientes ajustados a su mercado y validados contra competidores reales
•	Las funcionalidades core del vertical estan en el plan base — no detras de add-ons
•	Marketing AI Stack se ofrece como 9 Add-ons modulares, precio fijo, vertical-independiente
•	Compatibilidad definida: no todos los add-ons aplican a todos los verticales
•	Descuentos por bundle (4 paquetes pre-configurados)
•	Kit Digital como canal de financiacion: el bono digital del cliente financia la suscripcion
•	Cross-vertical composable: un tenant puede activar verticales adicionales como addon (TenantVerticalService)
•	Stripe es SSOT para billing; Drupal Admin UI es SSOT para features y limites — zero hardcoded prices
1.2 Tamano de Mercado Accesible
Mercado	Tamano 2026	Relevancia Pricing
Transformacion digital Espana	48.960M USD (CAGR 17,63%)	PYMEs creciendo al 19,80% — target principal
Cloud SaaS PYMEs Espana	5.000M USD	30% PYMEs en zonas rurales — desatendidas
Kit Digital (presupuesto)	3.067M EUR	Bono financia directamente la suscripcion
E-commerce B2C Espana	39.810M USD (CAGR 8,33%)	ComercioConecta + AgroConecta
Freelance platforms global	8.900M USD (CAGR 16,32%)	ServiciosConecta
Legal case management	916M USD	JarabaLex
LMS global	28.100M+ USD (~14% CAGR)	Formacion transversal
 
2. Planes Base por Vertical
2.1 EMPLEABILIDAD — LMS + Job Board + Matching + Credenciales
Plataforma de empleo, formacion y desarrollo de carrera para buscadores de empleo y empresas.
Funcionalidad	Starter 29 EUR	Pro 79 EUR	Enterprise 149 EUR	Competidor ref.
LMS / Formacion	5 cursos	50 cursos	Ilimitado	Coursera: 399 USD/user/ano
Learning Paths	No	Si	Si + Custom	Coursera: solo Enterprise
Certificados auto (Open Badges 3.0)	No	Si	Si	UNICO con OB 3.0
Job Board ofertas activas	3	25	Ilimitado	InfoJobs: freemium/premium
Candidaturas/mes	50	500	Ilimitado	Workable: 299 USD/mes
Matching Engine IA	No	Basico	IA avanzada	Greenhouse: 6K-70K USD/ano
CV Builder	1 plantilla	10 plantillas	Custom branding	UNICO integrado
AI Copilot	No	50 consultas/mes	Ilimitado	UNICO con SEPE/Fundae
Usuarios admin	2	10	Ilimitado	Factorial: ~4 EUR/emp
API Access	No	Read-only	Full CRUD	iCIMS: 15K+ USD/ano
White Label	No	No	Si	Greenhouse: solo Enterprise
Soporte	Email	Chat + Email	Dedicado + SLA	

Justificacion de precio vs mercado:
El rango 29-149 EUR/mes posiciona a Empleabilidad como la opcion mas accesible del mercado con funcionalidad comparable. Greenhouse cobra 6.000-70.000 USD/ano, Workable 299 USD/mes, Coursera Business 399 USD/user/ano. Jaraba es la UNICA plataforma que combina LMS certificable SEPE/Fundae + Job Board + AI Matching + Open Badges 3.0 por menos de 149 EUR/mes. Kit Digital (bono hasta 12.000 EUR) puede financiar 6+ anos de suscripcion Enterprise.
Metrica norte: Tasa de insercion laboral verificada >40%.
 
2.2 EMPRENDIMIENTO — Diagnostico + Canvas + Validacion + Mentoria + AI Copilot v2
Plataforma de apoyo a emprendedores con diagnosticos, planes de accion, mentoria y validacion Lean.
Funcionalidad	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR	Competidor ref.
Diagnostico Madurez Digital	Basico	Completo + IA	Custom + Benchmark	UNICO (nadie lo ofrece)
Analisis Competitivo	No	3/mes	Ilimitado	CB Insights: 1000+ USD/mes
Business Model Canvas	Si	Si + Versiones	Si + Colaborativo	Upmetrics: 14-49 USD
Proyecciones Financieras	No	Basicas	Avanzadas + Escenarios	LivePlan: 20-40 USD
MVP Validation (44 exp. Osterwalder)	No	Si	Si + A/B Testing	IdeaFloat: solo validation
Mentoria	No	Grupal (2h/mes)	Individual (4h/mes)	UNICO hibrido IA+humano
AI Business Copilot v2 (5 modos)	No	100 consultas/mes	Ilimitado	UNICO: 5 modos + 44 exp
Digital Kits	3 basicos	Todos	Todos + Custom	Kit Digital: canal financiacion
Networking Events	Acceso basico	Completo	VIP + Organizar	F6S: gratis pero sin tools
Usuarios equipo	1	5	Ilimitado	monday.com: 12 USD/user
API Access	No	No	Full	

Justificacion de precio vs mercado:
Combinar LivePlan (40 USD) + Coursera (400 USD/user/ano) + servicio mentoring (200+ USD/mes) + herramienta validacion costaria 700+ USD/mes. Jaraba Enterprise a 199 EUR/mes ofrece TODO integrado. El Kit Digital paga la suscripcion: un emprendedor con bono de 6.000 EUR tiene 2,5 anos de plan Pro financiado.
Metrica norte: Tasa de supervivencia de negocios a 12 meses >60%.
 
2.3 AGROCONECTA — Marketplace + Trazabilidad QR + Storytelling + Certificaciones
Marketplace de productos agroalimentarios de proximidad con trazabilidad phy-gital.
Funcionalidad	Starter 49 EUR	Pro 129 EUR	Enterprise 249 EUR	Competidor ref.
Productos en catalogo	50	500	Ilimitado	CrowdFarming: comision, no catalogo
Pedidos/mes	100	1.000	Ilimitado	Local Line: ~50-200 USD/mes
Comision plataforma	8%	5%	3% (negociable)	CrowdFarming: comision opaca
Trazabilidad QR (nivel lote)	No	Si	Si + Blockchain	SourceTrace: solo enterprise
Certificaciones DOP/IGP/Eco	Basicas	Todas	Custom + Verificacion	UNICO con DOP/IGP integrado
Storytelling productor	Basico	Completo + Video	Custom + Landing	CrowdFarming: basico
Shipping Integration	Manual	Auto (3 carriers)	Custom + Frio	Barn2Door: solo EEUU
Partner Document Hub	No	Si	Si + Workflows	UNICO
Multi-tienda/finca	No	No	Si	Shopify: generico, sin agri
ProducerCopilotAgent	No	Basico	Avanzado	UNICO IA agri-especifico
API Access	No	Read-only	Full CRUD	

Justificacion de precio vs mercado — El argumento 10x:
Un productor vende naranjas a 0,25 EUR/kg al intermediario. La misma naranja se vende a 2-3 EUR/kg en venta directa. Con AgroConecta a 49 EUR/mes, el productor recupera la inversion vendiendo 2-3 cajas directamente. CrowdFarming (51M EUR facturacion, 320 productores) centraliza; AgroConecta descentraliza — cada productor es dueno de su tienda y sus datos. La venta directa aun representa menos del 2% del mercado alimentario — oceano azul masivo.
Metrica norte: Margen neto incremental del productor 3-10x vs intermediario.
 
2.4 COMERCIOCONECTA — Sistema Operativo de Barrio Phygital
Sistema operativo de barrio para comercio de proximidad con experiencia phygital.
Funcionalidad	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR	Competidor ref.
Productos en catalogo	100	1.000	Ilimitado	Shopify: 5-299 USD/mes
Pedidos/mes	200	2.000	Ilimitado	Square: gratis pero sin e-comm
Comision plataforma	6%	4%	2% (negociable)	Shopify: 2,6%+15c txn
POS Integration	No	Si (basico)	Si (avanzado)	SumUp: 1,5% EU, sin tienda
Flash Offers (countdown)	5/mes	30/mes	Ilimitado	UNICO: nadie ofrece esto
QR Dinamico	Si	Si + Analytics	Si + Custom	UNICO: cambian por hora/dia
Local SEO automatizado	Basico	Optimizado	Premium + GMB auto	UNICO: sync auto Google
Click & Collect	Si	Si	Si + Lockers	El Corte Ingles: solo grande
Reviews & Ratings	Si	Si + Respuestas	Si + Moderacion IA	Google Business: gratis
Comunidad de Barrio digital	Si	Si	Si + Eventos	Nextdoor: solo social, no commerce
MerchantCopilotAgent	No	Basico	Avanzado	UNICO IA para comercio local
API Access	No	Read-only	Full CRUD	

Justificacion de precio vs mercado:
El Gobierno espanol lanzo la plataforma 'Comercio Conectado' — valida el concepto pero solo ofrece formacion, NO herramientas SaaS. El comercio minorista es 5% del PIB espanol (1,9M afiliados). Los compradores omnicanal gastan 1,5x mas (Deloitte). Ningun competidor combina POS + QR Dinamicos + Ofertas Flash + Local SEO auto + Resenas + Comunidad Barrio + IA a <200 EUR/mes. El bono Kit Digital (hasta 12.000 EUR) financia 5+ anos de plan Pro.
Metrica norte: Conversion QR-to-sale >5%.
 
2.5 SERVICIOSCONECTA — Plataforma Integral para Profesionales de Servicios
Plataforma para profesionales de servicios con reservas, facturacion VeriFactu, firma PAdES y gestion documental.
Funcionalidad	Starter 29 EUR	Pro 79 EUR	Enterprise 149 EUR	Competidor ref.
Servicios publicados	5	30	Ilimitado	Calendly: solo scheduling
Reservas/mes	50	500	Ilimitado	Acuity: 20-61 USD/mes
Comision plataforma	10%	7%	4% (negociable)	HoneyBook: 19-79 USD
Booking Engine	Basico	+ Calendar Sync	+ Multi-recurso	Calendly: 0-20 USD/user
Video Conferencing	No	Si (Zoom/Meet)	Si + Grabacion	Zoom: 13 USD/mes aparte
Buzon de Confianza	No	Si	Si + Workflows	UNICO: cifrado E2E
Firma Digital PAdES	No	10/mes	Ilimitado	DocuSign: 25+ EUR/mes aparte
Portal Cliente Documental	No	Si	Si + Personalizado	UNICO integrado
AI Triaje de Casos	No	No	Si	UNICO
Presupuestador Automatico	No	Si (basico)	Si (IA)	HoneyBook: smart files
Facturacion	Manual	Semi-auto	Automatica + VeriFactu	Holded: 29+ EUR (solo facturacion)
API Access	No	No	Full CRUD	

Justificacion de precio vs mercado:
Un profesional que usa Calendly (12 EUR) + Holded (29 EUR) + DocuSign (25 EUR) + Zoom (13 EUR) + Google Drive paga 79+ EUR/mes SIN triaje IA ni presupuestador. ServiciosConecta a 79 EUR/mes (Pro) reemplaza todo con features superiores. El 65% de los despachos espanoles no tiene plan digital (CGAE). 3,4 millones de autonomos en Espana como TAM.
Metrica norte: Reduccion no-shows (<5%) + horas admin ahorradas (3h/semana).
 
3. Catalogo de Add-ons de Marketing (9 items)
Los modulos de Marketing AI Stack se ofrecen como add-ons independientes. Precio fijo independiente del vertical, compatibilidad variable.
3.1 Add-ons Principales
Add-on	Precio/mes	Funcionalidades Incluidas
jaraba_crm	19 EUR	Pipeline B2B, contactos ilimitados, lead scoring, forecasting, actividades, integracion FOC
jaraba_email	29 EUR	5.000 emails/mes, 50 secuencias, 150 templates MJML, A/B en subject, analytics
jaraba_email_plus	59 EUR	25.000 emails/mes, secuencias ilimitadas, IA contenido, IP dedicada
jaraba_social	25 EUR	5 cuentas sociales, calendario editorial, variantes IA, scheduling, analytics
3.2 Add-ons de Extension
Add-on	Precio/mes	Funcionalidades Incluidas
paid_ads_sync	15 EUR	Sync Meta Ads + Google Ads, ROAS tracking, audiencias, budget alerts
retargeting_pixels	12 EUR	Pixel Manager multi-plataforma, server-side tracking, consent management
events_webinars	19 EUR	5 eventos/mes, landing pages, Calendly + Zoom, certificados, replays
ab_testing	15 EUR	Experimentos ilimitados, significancia estadistica, auto-stop, segmentacion
referral_program	19 EUR	Codigos referido, recompensas configurables, leaderboard, niveles embajador
3.3 Bundles de Marketing (4 paquetes)
Bundle	Incluye	Precio	Ahorro	Precio individual
Marketing Starter	jaraba_email + retargeting_pixels	35 EUR	15%	41 EUR
Marketing Pro	jaraba_crm + jaraba_email + jaraba_social	59 EUR	20%	73 EUR
Marketing Complete	Todos los add-ons principales + extensiones	99 EUR	30%	~142 EUR
Growth Engine	jaraba_email_plus + ab_testing + referral_program	79 EUR	15%	93 EUR
 
4. Matriz de Compatibilidad Add-ons x Verticales
Leyenda: RECOMENDADO (alto impacto) | Si (disponible) | No (no aplicable)
Add-on	Empleab.	Emprend.	Agro	Comercio	Servicios
jaraba_crm	Si	RECOMENDADO	Si	Si	RECOMENDADO
jaraba_email	RECOMENDADO	RECOMENDADO	RECOMENDADO	RECOMENDADO	RECOMENDADO
jaraba_social	Si	RECOMENDADO	RECOMENDADO	RECOMENDADO	Si
paid_ads_sync	Si	Si	RECOMENDADO	RECOMENDADO	Si
retargeting_pixels	Si	Si	RECOMENDADO	RECOMENDADO	Si
events_webinars	RECOMENDADO	RECOMENDADO	Si	Si	RECOMENDADO
ab_testing	Si	Si	Si	RECOMENDADO	Si
referral_program	RECOMENDADO	RECOMENDADO	RECOMENDADO	RECOMENDADO	Si

4.1 Justificacion de Recomendaciones Clave
•	Empleabilidad + events_webinars: Webinars orientacion laboral, talleres CV, ferias empleo virtuales
•	Emprendimiento + jaraba_crm: Pipeline inversores, seguimiento contactos, oportunidades financiacion
•	AgroConecta + retargeting: Recuperar carritos abandonados, audiencias compradores recurrentes
•	ComercioConecta + ab_testing: Optimizar landing pages ofertas flash, CTAs de reserva
•	ServiciosConecta + jaraba_crm: Gestion clientes, seguimiento casos, pipeline presupuestos
 
5. Canal Kit Digital — Financiacion Publica de Suscripciones
El programa Kit Digital (3.067M EUR, NextGenerationEU) es el canal de adquisicion mas potente para los verticales comerciales V1-V6. El bono digital del cliente financia directamente la suscripcion Jaraba.
Segmento	Empleados	Bono maximo	Meses Pro financiados	Meses Enterprise
Segmento I	10-49	12.000 EUR	~152 meses (Empleab.)	~80 meses
Segmento II	3-9	6.000 EUR	~76 meses	~40 meses
Segmento III	0-2	2.000-3.000 EUR	~25-38 meses	~13-20 meses
Segmento IV (medianas)	50-99	25.000 EUR	> 10 anos	> 5 anos
Segmento V (medianas)	100-249	29.000 EUR	> 10 anos	> 6 anos

Accion requerida: Registrarse como Agente Digitalizador en AceleraPyme (Q2 2026).
Kit Consulting complementario: bonos de 12.000-24.000 EUR para asesoria digital especializada (consultores pueden usar Emprendimiento + ServiciosConecta).
 
6. Pricing Institucional (Andalucia +ei / PIIL)
El vertical Andalucia +ei no tiene pricing SaaS comercial — se financia via subvencion publica del programa PIIL.
Concepto	Importe	Condicion
Subvencion por persona atendida	3.500 EUR	Min 10h orientacion + 50h formacion
Subvencion por persona insertada	2.500 EUR	Insercion laboral documentada
Incentivo participante	528 EUR	Percibido por el participante
Objetivo insercion minimo	40%	Regulatorio (target Jaraba: >60%)
Expediente activo	SC/ICJ/0050/2024	640 participantes | 900K EUR
Convocatoria 2026	31.100 desempleados	Feb 2026 - Jun 2027, toda Andalucia

Estado implementacion (auditado 18-03-2026): GAP-01 a GAP-05 IMPLEMENTADOS en codebase. SEPE SOAP 6 operaciones IMPLEMENTADAS. GAP-06 parcial, GAP-07/08 pendientes.
 
7. Verticales Composables — Arquitectura Cross-Vertical
Un tenant puede activar verticales adicionales como addon mediante TenantVerticalService. Esto es el diferenciador competitivo mas profundo del ecosistema: ningun Shopify, Clio, LivePlan o CrowdFarming puede ofrecer cross-vertical nativo.
7.1 Escenarios de Upsell Cross-Vertical
Tenant base	Activa addon	Caso de uso	Revenue incremental
Emprendimiento Pro	+ AgroConecta Starter	Emprendedor valida idea de producto local	+49 EUR/mes
Emprendimiento Pro	+ ComercioConecta Starter	Emprendedor abre tienda online	+39 EUR/mes
AgroConecta Pro	+ ComercioConecta Pro	Productor con tienda fisica + online	+99 EUR/mes
JarabaLex Pro	+ Emprendimiento Starter	Abogado que asesora startups	+39 EUR/mes
ServiciosConecta Pro	+ Formacion Addon	Coach que vende cursos online	+addon LMS
Empleabilidad Enterprise	+ Emprendimiento Pro	Programa empleo → autoempleo	+99 EUR/mes

Implementacion: SaasPlanTier y SaasPlanFeatures como Config Entities editables desde admin UI. TenantVerticalService gestiona la activacion/desactivacion de verticales con Stripe subscription_items.
 
8. Implementacion Tecnica en Stripe
8.1 Estructura de Productos Stripe
Product ID	Nombre	Tipo
prod_empleabilidad_starter	Empleabilidad Starter	Base Plan
prod_empleabilidad_pro	Empleabilidad Pro	Base Plan
prod_empleabilidad_enterprise	Empleabilidad Enterprise	Base Plan
prod_emprendimiento_starter	Emprendimiento Starter	Base Plan
prod_emprendimiento_pro	Emprendimiento Pro	Base Plan
prod_emprendimiento_enterprise	Emprendimiento Enterprise	Base Plan
prod_agroconecta_starter	AgroConecta Starter	Base Plan
prod_agroconecta_pro	AgroConecta Pro	Base Plan
prod_agroconecta_enterprise	AgroConecta Enterprise	Base Plan
prod_comercioconecta_starter	ComercioConecta Starter	Base Plan
prod_comercioconecta_pro	ComercioConecta Pro	Base Plan
prod_comercioconecta_enterprise	ComercioConecta Enterprise	Base Plan
prod_serviciosconecta_starter	ServiciosConecta Starter	Base Plan
prod_serviciosconecta_pro	ServiciosConecta Pro	Base Plan
prod_serviciosconecta_enterprise	ServiciosConecta Enterprise	Base Plan
prod_addon_crm	Add-on: jaraba_crm	Add-on
prod_addon_email	Add-on: jaraba_email	Add-on
prod_addon_email_plus	Add-on: jaraba_email_plus	Add-on
prod_addon_social	Add-on: jaraba_social	Add-on
prod_addon_paid_ads	Add-on: paid_ads_sync	Add-on
prod_addon_retargeting	Add-on: retargeting_pixels	Add-on
prod_addon_events	Add-on: events_webinars	Add-on
prod_addon_ab_testing	Add-on: ab_testing	Add-on
prod_addon_referral	Add-on: referral_program	Add-on
prod_bundle_starter	Bundle: Marketing Starter	Bundle
prod_bundle_pro	Bundle: Marketing Pro	Bundle
prod_bundle_complete	Bundle: Marketing Complete	Bundle
prod_bundle_growth	Bundle: Growth Engine	Bundle

Variables criticas en settings.secrets.php:
•	STRIPE_SECRET_KEY — API secret key
•	STRIPE_PUBLISHABLE_KEY — Frontend key
•	STRIPE_WEBHOOK_SECRET — Verificacion HMAC de webhooks (AUDIT-SEC-001). Sin ella, checkout.session.completed e invoice.payment_failed no se verifican.
•	Regla #128: Stripe no /v1/ prefix en SDK calls
 
9. Descuentos y Promociones
Tipo	Descuento	Implementacion Stripe
Pago anual (plan base)	2 meses gratis	Price billing_scheme=per_unit, interval=year
Pago anual (add-ons)	15%	Price anual separado por add-on
Bundle discount	15-30%	Product tipo bundle con price propio
Codigo promocional	Variable	Stripe Coupons aplicados en checkout
Referido	1er mes gratis	Coupon 100% off, duration=once
Early adopter	30% de por vida	Coupon duration=forever
Colegio Abogados (JarabaLex)	20% colectivo	Coupon por organizacion
Programa Barrio Digital	15% municipio	Coupon por programa colectivo
 
10. Metricas de Revenue
Metrica	Definicion	Target Q4 2026
ARPU	MRR total / Tenants activos	> 80 EUR
Addon Attach Rate	% tenants con 1+ add-on	> 30%
Avg Addons per Tenant	Total add-ons / Tenants activos	> 1.5
Addon Revenue %	MRR add-ons / MRR total	> 20%
Plan Mix	Starter/Pro/Enterprise distribucion	40/45/15
Upgrade Rate (90d)	% tenants que hacen upgrade	> 15%
Expansion MRR	MRR adicional upgrades + add-ons	Creciente m/m
Net Revenue Retention	(MRR start+exp-churn-contr)/MRR start	> 110%
Cross-Vertical Rate	% tenants con 2+ verticales	> 10%
Kit Digital Conversion	% trials financiados por bono	> 40%

Doc 158 SSOT | Regla #131: Precios = este documento | Zero hardcoded prices | PED S.L. | 2026
