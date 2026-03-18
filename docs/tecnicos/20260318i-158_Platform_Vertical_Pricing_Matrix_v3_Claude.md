
PRICING MATRIX v3
Matriz de Precios Corregida — 12 Correcciones del Modelo de Negocio
Plataforma de Ecosistemas Digitales S.L. — Jaraba Impact Platform


Campo	Valor
Documento	158_Platform_Vertical_Pricing_Matrix_v3
Version	3.0 (18 marzo 2026)
Sustituye	Doc 158 v2 (queda OBSOLETO)
Correcciones aplicadas	12 (listadas en Seccion 1)
Doc asociado	181_Mentoring_Desacople (detalle tecnico de correccion #0)
Principio	Sin Humo: solo se promete lo que se puede entregar en Day 0
Equipo	Claude Code (integramente)
 
INDICE
INDICE	1
1. Changelog v2 -> v3: 12 Correcciones	1
2. Arquitectura de Pricing	1
3. Planes Base por Vertical (CORREGIDOS)	1
3.1 Empleabilidad (29 / 79 / 149 EUR)	1
3.2 Emprendimiento (39 / 99 / 199 EUR)	1
3.3 AgroConecta (49 / 129 / 249 EUR)	1
3.4 ComercioConecta (39 / 99 / 199 EUR)	1
3.5 ServiciosConecta (29 / 79 / 149 EUR)	1
3.6 JarabaLex (39 / 99 / 199 EUR)	1
4. Catalogo de Add-ons	1
4.1 Add-ons de Marketing	1
4.2 Bundles de Marketing	1
4.3 Add-on White Label (NUEVO v3 — Correccion #4)	1
5. Servicios Profesionales (NUEVO v3 — Correccion #0)	1
5.1 Mentoria Individual	1
5.2 Programas Estructurados	1
5.3 Formatos Grupales	1
6. Comisiones Marketplace (CORREGIDO v3 — Correccion #11)	1
6.1 Modelo de comision por volumen	1
6.2 Verticales con marketplace	1
7. Descuentos y Promociones (CORREGIDO v3 — Correccion #10)	1
8. Catalogo Stripe Products Completo	1
8.1 Planes base (suscripciones recurrentes)	1
8.2 Add-ons (suscripciones recurrentes)	1
8.3 Servicios Profesionales (pagos one-time)	1
9. Directrices para Claude Code	1
10. Metricas de Revenue	1

 
1. Changelog v2 -> v3: 12 Correcciones
Cada correccion responde al test Sin Humo: ¿puede el usuario experimentar esta feature en Day 0 sin que PED pierda dinero ni haga promesas vacias?

#	Fallo	Gravedad	Correccion aplicada en v3
0	Mentoria humana incluida en plan SaaS	CRITICO	Desacoplada como servicio profesional (Doc 181). Solo Mastermind grupal en Pro
1	Matching Engine vacio en Empleabilidad Pro	CRITICO	Reframing: 'Matching activado con +20 ofertas'. Day 0 = AI Copilot + CV + formacion
2	Marketplace sin supply en Agro/Comercio	CRITICO	Vender 'tu tienda online propia', no 'marketplace'. Marketplace = bonus con masa critica
3	Video Conferencing coste oculto infra	CRITICO	Fase 0: enlaces Meet/Zoom gratuitos. Jitsi self-hosted cuando revenue lo justifique
4	White Label incluido en Enterprise	CRITICO	Movido a add-on: 500 EUR setup + 50 EUR/mes. Enterprise incluye solo colores+logo
5	Soporte Dedicado + SLA sin equipo	GRAVE	Rebajado: Starter=docs+AI, Pro=email 48h, Enterprise=email prioritario 24h
6	Cursos que no existen en Empleabilidad	GRAVE	Day 0: contenido curado (recursos externos). Produccion propia con revenue
7	AI Copilot ausente en TODOS los Starter	GRAVE	Todos los Starter incluyen AI Copilot limitado (25-50 consultas/mes)
8	JarabaLex sin tabla de pricing	MODERADO	Aniadida tabla completa alineada con ServiciosConecta
9	Networking Events no entregable	MODERADO	Eliminado de plan base. Movido a add-on events_webinars (19 EUR/mes)
10	Early adopter 30% forever	MODERADO	Cambiado: 30% ano 1, 15% ano 2, precio normal despues
11	Comision marketplace variable por plan	MODERADO	Comision por volumen (no por plan): 8% < 5K, 6% 5-20K, 4% > 20K
 
2. Arquitectura de Pricing
El modelo de revenue tiene TRES pilares independientes:

Pilar	Que incluye	Tipo pago Stripe	Ejemplo
1. Plan Base SaaS	Plataforma + IA + features por tier	Suscripcion recurrente (mensual/anual)	Emprendimiento Pro 99 EUR/mes
2. Add-ons	Modulos opcionales (marketing, eventos, etc)	Suscripcion recurrente	jaraba_email 29 EUR/mes
3. Servicios Profesionales	Mentoria humana, programas, workshops	Pago unico (one-time)	Sesion 1:1 175 EUR

Regla #0 (NUEVA v3): Ningun plan SaaS incluye horas de trabajo humano de Pepe ni de nadie. El tiempo humano se vende como Servicio Profesional independiente.

Regla #1 (NUEVA v3): TODOS los planes Starter incluyen AI Copilot limitado. La IA es el diferencial.

Regla #2 (NUEVA v3): Solo se promete como feature lo que funciona con UN solo usuario (sin depender de otros lados del marketplace).
 
3. Planes Base por Vertical (CORREGIDOS)
3.1 Empleabilidad (29 / 79 / 149 EUR)
Target: Organizaciones de empleo, programas PIIL, centros de formacion, ETTs.
Funcionalidad	Starter 29 EUR	Pro 79 EUR	Enterprise 149 EUR
LMS / Formacion	5 paths curados	50 paths + custom	Ilimitado + SCORM import
Contenido formativo	Curado (recursos externos)	Curado + 10 cursos propios	Ilimitado + produccion propia
Learning Paths	SI (pre-configurados)	SI + Custom	SI + Ramificados
Certificados digitales	Basico (PDF)	Open Badges 3.0	OB 3.0 + Custom branding
Job Board	3 ofertas activas	25 ofertas	Ilimitado
Fuente de ofertas Day 0	Agregacion externa (SAE/InfoJobs)	Agregacion + propias	Propias + API integracion
Candidaturas/mes	50	500	Ilimitado
CV Builder	1 plantilla	10 plantillas	Custom branding
Matching Engine	NO (se activa con +20 ofertas)	SI cuando haya masa critica	SI (IA avanzada)
AI Copilot Empleo	25 consultas/mes	100 consultas/mes	Ilimitado
Employer Portal	Basico	Completo	+ API ATS
Usuarios admin	2	10	Ilimitado
API Access	NO	Read-only	Full CRUD
Personalizacion visual	Colores + logo	Colores + logo + favicon	+ Dominio propio (add-on)
Soporte	Docs + AI chatbot	Email 48h habiles	Email prioritario 24h

Correccion #1: Matching Engine NO se promete hasta masa critica. Correccion #6: Contenido curado Day 0. Correccion #7: AI Copilot en Starter.
 
3.2 Emprendimiento (39 / 99 / 199 EUR)
Target: Emprendedores, nuevos autonomos, programas de emprendimiento, incubadoras.
Funcionalidad	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR
Diagnostico Digital	Basico	Completo + IA	Custom + Benchmark sectorial
Analisis Competitivo	NO	3/mes	Ilimitado
Planes de Accion	1 activo	5 activos	Ilimitado
Business Model Canvas	SI	SI + Versiones	SI + Colaborativo
44 Experimentos Osterwalder	5 primeros	Todos	Todos + Custom
Proyecciones Financieras	NO	Basicas	Avanzadas + Escenarios
MVP Validation	NO	SI	SI + A/B Testing
AI Business Copilot	Modo learn (50 cons/mes)	5 modos (200 cons/mes)	5 modos (Ilimitado)
Grupo Mastermind mensual	NO	SI (grupal, 8 pers, 90 min)	SI + Prioridad
Mentoria humana 1:1	NO INCLUIDA (servicio aparte)	NO INCLUIDA (servicio aparte)	NO INCLUIDA (servicio aparte)
Digital Kits	3 basicos	Todos	Todos + Custom
Usuarios equipo	1	5	Ilimitado
API Access	NO	NO	Full CRUD
Personalizacion visual	Colores + logo	Colores + logo	+ Dominio propio (add-on)
Soporte	Docs + AI chatbot	Email 48h	Email prioritario 24h

Correccion #0: Mentoria humana FUERA del plan. Correccion #7: AI Copilot en Starter. Correccion #9: Networking Events eliminado (add-on).
 
3.3 AgroConecta (49 / 129 / 249 EUR)
Target: Productores agroalimentarios, cooperativas, fincas ecologicas. NOTA: el pricing vende 'tu tienda online de productor', NO acceso a marketplace.
Funcionalidad	Starter 49 EUR	Pro 129 EUR	Enterprise 249 EUR
Tu tienda online propia	SI (subdominio)	SI (subdominio + SEO)	SI (dominio propio via add-on)
Productos en catalogo	25	200	Ilimitado
Fichas producto + storytelling	Basico	+ Video + origen	+ Storytelling IA
Trazabilidad QR	NO	SI (lote)	SI (unidad)
Certificaciones DOP/IGP/Eco	SI (manual)	SI (gestionable)	SI + Verificacion auto
Checkout + pagos Stripe	SI	SI	SI + SEPA + transfer
Shipping (MRW/SEUR/Correos)	1 carrier	Multi-carrier	+ Frio + Envio mismo dia
Marketplace cross-productor	Bonus (cuando +15 productores)	Bonus (incluido)	Bonus + Destacado
AI Copilot Productor	25 consultas/mes	100 consultas/mes	Ilimitado
Dashboard analitica	Basico	Completo	+ Exportar + API
Pedidos/mes	50	500	Ilimitado
Usuarios equipo	1	3	Ilimitado
Personalizacion visual	Colores + logo	Colores + logo	+ Dominio propio (add-on)
Soporte	Docs + AI chatbot	Email 48h	Email prioritario 24h

Correccion #2: Framing = 'tu tienda propia', marketplace = bonus. Correccion #7: AI Copilot en Starter.
 
3.4 ComercioConecta (39 / 99 / 199 EUR)
Target: Comercios minoristas, tiendas de barrio, boutiques. Framing: 'tu tienda online + herramientas de barrio'.
Funcionalidad	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR
Tu tienda online propia	SI (subdominio)	SI + SEO local auto	SI + dominio propio (add-on)
Productos en catalogo	50	500	Ilimitado
QR Dinamicos	5 activos	50 activos	Ilimitado
Ofertas Flash	3/mes	20/mes	Ilimitado
Local SEO automatizado	NO	SI (Google Business sync)	SI + Multi-location
POS Integration	NO	Basico	Avanzado + sync inventario
Checkout + pagos Stripe	SI	SI	SI + SEPA + TPV
Shipping multi-carrier	1 carrier	Multi-carrier	+ Puntos recogida + lockers
Marketplace Barrio Digital	Bonus (cuando +10 comercios)	Bonus (incluido)	Bonus + Destacado
AI Copilot Comercio	25 consultas/mes	100 consultas/mes	Ilimitado
Dashboard analitica	Basico	Completo	+ Exportar + API
Reviews y valoraciones	SI	SI + Moderacion	SI + Widget embebible
Usuarios equipo	1	5	Ilimitado
Personalizacion visual	Colores + logo	Colores + logo	+ Dominio propio (add-on)
Soporte	Docs + AI chatbot	Email 48h	Email prioritario 24h

Correccion #2: 'Tu tienda online' no 'marketplace'. Correccion #7: AI en Starter.
 
3.5 ServiciosConecta (29 / 79 / 149 EUR)
Target: Consultores, coaches, terapeutas, asesores, autonomos de servicios.
Funcionalidad	Starter 29 EUR	Pro 79 EUR	Enterprise 149 EUR
Servicios publicados	5	30	Ilimitado
Reservas/mes	50	500	Ilimitado
Booking Engine	Basico	+ Calendar Sync	+ Multi-recurso
Videollamada integrada	Enlace Meet/Zoom auto	Enlace Meet/Zoom auto	Enlace Meet/Zoom + grabacion
Buzon de Confianza	NO	SI	SI + Workflows
Firma Digital PAdES	NO	10/mes	Ilimitado
Portal Cliente Documental	NO	SI	SI + Personalizado
AI Triaje de Casos	NO	NO	SI
Presupuestador Auto	NO	SI (basico)	SI (IA)
Facturacion	Manual	Semi-auto	Automatica + SII
AI Copilot Servicios	25 consultas/mes	100 consultas/mes	Ilimitado
Usuarios equipo	1	5	Ilimitado
API Access	NO	NO	Full CRUD
Personalizacion visual	Colores + logo	Colores + logo	+ Dominio propio (add-on)
Soporte	Docs + AI chatbot	Email 48h	Email prioritario 24h

Correccion #3: Videollamada = enlaces Meet/Zoom (coste cero). Jitsi self-hosted diferido. Correccion #7: AI en Starter.
 
3.6 JarabaLex (39 / 99 / 199 EUR)
Target: Abogados individuales, despachos pequenos, asesorias juridicas rurales. NUEVA en v3 (Correccion #8).
Funcionalidad	Starter 39 EUR	Pro 99 EUR	Enterprise 199 EUR
Gestion expedientes	10 activos	100 activos	Ilimitado
Calendario judicial sync	NO	SI	SI + Multi-juzgado
Integracion LexNet	NO	Lectura	Lectura + escritura
Facturacion legal	Manual	Semi-auto + time tracking	Automatica + SII + AEAT
Firma Digital PAdES	NO	10/mes	Ilimitado
Buzon Confianza cifrado	NO	SI	SI + Workflows
Portal Cliente Documental	NO	SI	SI + Personalizado
Base conocimiento normativo	Basica	Completa	+ Custom + actualizacion auto
AI Copilot Legal (LCIS 9 capas)	25 consultas/mes	100 consultas/mes	Ilimitado
EU AI Act compliance	SI (high-risk)	SI	SI + Audit log
VeriFactu	NO	SI	SI + Multi-entidad
Usuarios equipo	1	5	Ilimitado
API Access	NO	NO	Full CRUD
Personalizacion visual	Colores + logo	Colores + logo	+ Dominio propio (add-on)
Soporte	Docs + AI chatbot	Email 48h	Email prioritario 24h

Correccion #8: Tabla NUEVA — no existia en v2. Alineada con ServiciosConecta + features legales especificas.
 
4. Catalogo de Add-ons
4.1 Add-ons de Marketing
Add-on	Precio/mes	Funcionalidades
jaraba_crm	19 EUR	Pipeline B2B, contactos ilimitados, lead scoring, forecasting, integr. FOC
jaraba_email	29 EUR	5.000 emails/mes, 50 secuencias, 150 templates MJML, A/B subject
jaraba_email_plus	59 EUR	25.000 emails/mes, secuencias ilimitadas, IA contenido, IP dedicada
jaraba_social	25 EUR	5 cuentas, calendario editorial, variantes IA, scheduling, analytics
paid_ads_sync	15 EUR	Sync Meta+Google Ads, ROAS tracking, audiencias, budget alerts
retargeting_pixels	12 EUR	Pixel Manager multi-plataforma, server-side, consent management
events_webinars	19 EUR	5 eventos/mes, landing pages, Zoom, certificados, replays
ab_testing	15 EUR	Experimentos ilimitados, significancia estadistica, auto-stop
referral_program	19 EUR	Codigos referido, recompensas configurables, leaderboard

4.2 Bundles de Marketing
Bundle	Incluye	Precio	Ahorro vs individual
Marketing Starter	jaraba_email + retargeting_pixels	35 EUR	15%
Marketing Pro	jaraba_crm + jaraba_email + jaraba_social	59 EUR	20%
Marketing Complete	Todos los add-ons principales + extensiones	99 EUR	30%
Growth Engine	jaraba_email_plus + ab_testing + referral_program	79 EUR	15%

4.3 Add-on White Label (NUEVO v3 — Correccion #4)
White Label ya NO esta incluido en Enterprise. Es un add-on independiente.
Componente	Precio	Descripcion
Setup inicial	500 EUR (one-time)	Config DNS, certificado SSL, tema custom, emails remitente propio
Mantenimiento mensual	50 EUR/mes	Renovacion SSL, soporte DNS, actualizaciones tema
Dominio propio	Incluido en setup	El tenant usa su propio dominio en lugar de subdominio PED
Eliminacion marca Jaraba	Incluido en setup	Sin 'Powered by Jaraba' en footer ni emails
Requisito minimo	Plan Enterprise activo	Solo disponible para clientes Enterprise
 
5. Servicios Profesionales (NUEVO v3 — Correccion #0)
Linea de revenue independiente del SaaS. Pagos one-time via Stripe Checkout. Especificacion tecnica completa en Doc 181.

5.1 Mentoria Individual
Producto	Precio	Sesiones	Stripe Product
Sesion suelta 1:1 (45 min)	175 EUR	1	prod_mentoring_single
Pack 4 sesiones	595 EUR (149/sesion)	4	prod_mentoring_pack4
Pack 8 sesiones	1.095 EUR (137/sesion)	8	prod_mentoring_pack8

5.2 Programas Estructurados
Programa	Precio	Duracion	Stripe Product
Programa Lanzamiento Digital	1.950 EUR	12 semanas (1 sesion/sem)	prod_program_launch
Programa Aceleracion	2.950 EUR	12 semanas (2 sesiones/sem)	prod_program_accelerate
Advisory Trimestral	1.200 EUR	3 meses (1 sesion/2 sem)	prod_program_advisory

5.3 Formatos Grupales
Formato	Precio/persona	Max participantes	Stripe Product
Workshop tematico (2h)	79 EUR	6-12	prod_workshop_single
Mastermind Premium (trimestral)	295 EUR	6-8	prod_mastermind_premium
Bootcamp Emprendimiento (5 dias)	495 EUR	10-15	prod_bootcamp
 
6. Comisiones Marketplace (CORREGIDO v3 — Correccion #11)
Las comisiones ya NO dependen del plan SaaS. Dependen del VOLUMEN de ventas del productor/comerciante.

6.1 Modelo de comision por volumen
GMV mensual del vendedor	Comision PED	Ejemplo
< 5.000 EUR	8%	Vendedor con 3.000 EUR GMV paga 240 EUR comision
5.000 - 20.000 EUR	6%	Vendedor con 10.000 EUR GMV paga 600 EUR comision
> 20.000 EUR	4%	Vendedor con 30.000 EUR GMV paga 1.200 EUR comision

Este modelo incentiva el crecimiento del vendedor (mas vende, menos comision) sin crear el incentivo perverso de pagar Enterprise solo por la comision baja.

6.2 Verticales con marketplace
Vertical	Tipo marketplace	Comision adicional a plan SaaS	Implementacion
AgroConecta	B2C: productor → consumidor	SI (modelo volumen arriba)	Stripe Connect Destination Charges
ComercioConecta	B2C: comercio → consumidor	SI (modelo volumen arriba)	Stripe Connect Destination Charges
ServiciosConecta	B2C: profesional → cliente	NO (el profesional cobra directamente)	No aplica
JarabaLex	B2C: abogado → cliente	NO (el abogado cobra directamente)	No aplica
Empleabilidad	B2B: empleador → candidato	NO (no hay transaccion)	No aplica
Emprendimiento	N/A	NO	No aplica
 
7. Descuentos y Promociones (CORREGIDO v3 — Correccion #10)
Tipo	Descuento	Implementacion Stripe	Cambio v3
Pago anual (plan base)	2 meses gratis	Price annual: billing_scheme='per_unit', interval='year'	Sin cambio
Pago anual (add-ons)	15%	Price anual separado por add-on	Sin cambio
Bundle discount	15-30%	Product tipo bundle con price propio	Sin cambio
Codigo promocional	Variable	Stripe Coupons en checkout	Sin cambio
Referido	1er mes gratis	Coupon 100% off, duration='once'	Sin cambio
Early adopter	30% ano 1, 15% ano 2, normal despues	Coupon 30% duration='repeating' 12 meses + Coupon 15% 12 meses	CAMBIADO (era 'forever')
Kit Digital	Bono cubre X meses de suscripcion	Subscription con trial period = meses cubiertos	Sin cambio
PIIL institucional	Precio especial por programa	Invoice manual o custom Price	Sin cambio
 
8. Catalogo Stripe Products Completo
8.1 Planes base (suscripciones recurrentes)
Stripe Product	Vertical	Tier	Precio mensual	Precio anual	Lookup key
prod_empleabilidad	Empleabilidad	Starter	29	290	empleabilidad_starter
prod_empleabilidad	Empleabilidad	Pro	79	790	empleabilidad_pro
prod_empleabilidad	Empleabilidad	Enterprise	149	1.490	empleabilidad_enterprise
prod_emprendimiento	Emprendimiento	Starter	39	390	emprendimiento_starter
prod_emprendimiento	Emprendimiento	Pro	99	990	emprendimiento_pro
prod_emprendimiento	Emprendimiento	Enterprise	199	1.990	emprendimiento_enterprise
prod_agroconecta	AgroConecta	Starter	49	490	agroconecta_starter
prod_agroconecta	AgroConecta	Pro	129	1.290	agroconecta_pro
prod_agroconecta	AgroConecta	Enterprise	249	2.490	agroconecta_enterprise
prod_comercioconecta	ComercioConecta	Starter	39	390	comercioconecta_starter
prod_comercioconecta	ComercioConecta	Pro	99	990	comercioconecta_pro
prod_comercioconecta	ComercioConecta	Enterprise	199	1.990	comercioconecta_enterprise
prod_serviciosconecta	ServiciosConecta	Starter	29	290	serviciosconecta_starter
prod_serviciosconecta	ServiciosConecta	Pro	79	790	serviciosconecta_pro
prod_serviciosconecta	ServiciosConecta	Enterprise	149	1.490	serviciosconecta_enterprise
prod_jarabalex	JarabaLex	Starter	39	390	jarabalex_starter
prod_jarabalex	JarabaLex	Pro	99	990	jarabalex_pro
prod_jarabalex	JarabaLex	Enterprise	199	1.990	jarabalex_enterprise

Total: 18 planes base (6 verticales x 3 tiers) = 36 Stripe Prices (mensual + anual).
 
8.2 Add-ons (suscripciones recurrentes)
9 add-ons de marketing + 4 bundles + 1 white label = 14 Stripe Products adicionales. Lookup keys: addon_{name}_monthly / addon_{name}_yearly.

8.3 Servicios Profesionales (pagos one-time)
9 Stripe Products one-time (detallados en seccion 5). Mode = payment, no subscription.
 
9. Directrices para Claude Code
Regla	Descripcion
PRICING-V3-001	NUNCA incluir horas de trabajo humano en un plan SaaS. El tiempo humano = Servicio Profesional (Doc 181)
PRICING-V3-002	TODOS los planes Starter incluyen AI Copilot limitado (25-50 consultas/mes). Sin excepcion
PRICING-V3-003	Solo prometer features que funcionen con UN SOLO usuario. No depender de 'el otro lado' del marketplace
PRICING-V3-004	Marketplace = feature BONUS que se activa con masa critica. No es el valor core del plan
PRICING-V3-005	White Label = add-on pagado (500 EUR setup + 50 EUR/mes). NO incluido en ningun plan
PRICING-V3-006	Soporte: Starter=docs+AI, Pro=email 48h, Enterprise=email 24h. NO 'dedicado', NO 'SLA' hasta tener equipo
PRICING-V3-007	Videollamadas Fase 0: enlaces Meet/Zoom gratuitos. Jitsi self-hosted diferido a cuando revenue >= 5K MRR
PRICING-V3-008	Comisiones marketplace: por VOLUMEN (8/6/4%), NO por plan. Desacoplado del tier SaaS
PRICING-V3-009	Early adopter: 30% ano 1 + 15% ano 2 + normal despues. NUNCA 'forever'
PRICING-V3-010	Precios = Config Entities editables desde admin. CERO hardcoded (regla heredada de v1)
PRICING-V3-011	Contenido formativo Day 0 = curado (recursos externos). Produccion propia solo con revenue para financiarla
PRICING-V3-012	Networking Events = add-on (events_webinars 19 EUR). NO feature de plan base
 
10. Metricas de Revenue
Metrica	Definicion	Target Q4 2026
MRR Total	Suma suscripciones activas (planes + add-ons)	5.000+ EUR
ARPU	MRR / Tenants activos	75+ EUR
Addon Attach Rate	% tenants con 1+ add-on	25%+
Services Revenue	Ingresos mensuales servicios profesionales	2.000+ EUR
Services/SaaS Ratio	Revenue servicios / Revenue SaaS	15-25%
Plan Mix	Starter / Pro / Enterprise	40% / 45% / 15%
Upgrade Rate (90d)	% Starter que upgradan a Pro en 90 dias	15%+
Marketplace GMV	Volumen bruto ventas marketplace (Agro+Comercio)	10.000+ EUR
Platform Take Rate	Comisiones / GMV	6-8%
Net Revenue Retention	(MRR inicio + expansion - churn - contraction) / MRR inicio	>100%
Churn Rate	Cancelaciones / Total inicio mes	< 5%
Kit Digital Revenue	Bonos cobrados via Kit Digital	20.000+ EUR
Copilot Usage Rate	% usuarios que usan AI Copilot al menos 1x/semana	60%+

Doc 158 v3 | Pricing Matrix Corregida | PED S.L. | 18 marzo 2026 | Sustituye Doc 158 v2
