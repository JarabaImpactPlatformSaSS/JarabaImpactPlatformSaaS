PRESUPUESTO CONSOLIDADO 2026
Ecosistema Jaraba Impact Platform - Actualizado

Métrica	Valor
Total Horas Estimadas:	4,200 - 5,400 horas
Inversión Estimada (€45/h):	€189,000 - €243,000
Timeline:	18 meses (Q1 2026 - Q2 2027)
Documentos Técnicos:	170+
Fecha Actualización:	Enero 2026
 
1. Resumen Ejecutivo
Fase	Período	Horas	Coste (€45/h)	% Total
Fase 1: Core Platform	Q1 2026	580-760	€26,100-34,200	14%
Fase 2: Verticales	Q2 2026	1,420-1,820	€63,900-81,900	34%
Fase 3: Scale	Q3-Q4 2026	1,450-1,900	€65,250-85,500	35%
Fase 4: Expand	Q1-Q2 2027	750-920	€33,750-41,400	17%
TOTAL		4,200-5,400	€189,000-243,000	100%
1.1 Contingencia Recomendada
Se recomienda añadir un 15-20% de contingencia para imprevistos técnicos, cambios de alcance y dependencias externas.
Escenario	Base	Contingencia 15%	Contingencia 20%
Conservador	€189,000	€217,350	€226,800
Agresivo	€243,000	€279,450	€291,600
 
2. Fase 1: Core Platform (Q1 2026)
Infraestructura base que habilita todas las verticales. Bloquea el resto del desarrollo.
Doc	Módulo	Horas Min	Horas Max	Coste €
01	Core_Entidades_Esquema_BD	40	50	1,800-2,250
02	Core_Modulos_Personalizados	60	80	2,700-3,600
03	Core_APIs_Contratos	50	60	2,250-2,700
04	Core_Permisos_RBAC	30	40	1,350-1,800
05	Core_Theming_jaraba_theme	40	50	1,800-2,250
06	Core_Flujos_ECA	40	50	1,800-2,250
07	Core_Configuracion_MultiTenant	50	60	2,250-2,700
131	Infrastructure_Deployment	40	50	1,800-2,250
132	CICD_Pipeline	30	40	1,350-1,800
133	Monitoring_Alerting	25	35	1,125-1,575
134	Stripe_Billing_Integration	100	120	4,500-5,400
135	Testing_Strategy	35	45	1,575-2,025
139	GoLive_Runbook	20	30	900-1,350
	SUBTOTAL FASE 1	560	710	€25,200-31,950
 
3. Fase 2: Verticales Comerciales (Q2 2026)
3.1 Empleabilidad
Docs	Módulo	Horas	Coste €
08-10	LMS Core + Learning Paths + Progress	105-135	4,725-6,075
11-14	Job Board + Applications + Employer + Alerts	145-185	6,525-8,325
15-18	Candidate Profile + CV + Credentials + Cert	130-170	5,850-7,650
19-24	Matching + AI Copilot + Recommendations + Dashboards	195-255	8,775-11,475
	SUBTOTAL EMPLEABILIDAD	575-745	€25,875-33,525
3.2 Emprendimiento
Docs	Módulo	Horas	Coste €
25-27	Diagnostic + Maturity + Competitive	105-135	4,725-6,075
28-30	Digitalization Paths + Action Plans + Milestones	90-120	4,050-5,400
31-35	Mentoring System completo	160-210	7,200-9,450
36-44	Business Tools + Dashboards + AI Copilot	175-235	7,875-10,575
	SUBTOTAL EMPRENDIMIENTO	530-700	€23,850-31,500
3.3 AgroConecta (Vertical Modelo)
Docs	Módulo	Horas	Coste €
47-50	Commerce Core + Catalog + Orders + Checkout	175-215	7,875-9,675
51-54	Shipping + Producer Portal + Customer + Reviews	140-180	6,300-8,100
55-61	Search + Promotions + Analytics + Admin + API	145-195	6,525-8,775
80-82	Traceability + QR + Partner Hub	95-125	4,275-5,625
	SUBTOTAL AGROCONECTA	555-715	€24,975-32,175
 
4. Fase 3: Enterprise Ready (Q3-Q4 2026)
4.1 ComercioConecta
Reutiliza 65-70% de AgroConecta, reduciendo costes significativamente.
Docs	Módulo	Horas	Coste €
62-65	Commerce Core (fork) + POS + Flash Offers + QR	135-175	6,075-7,875
66-72	Catalog + Orders + Checkout + Search + SEO + Promos	160-210	7,200-9,450
73-79	Reviews + Portals + Notifications + Mobile + Admin	145-195	6,525-8,775
	SUBTOTAL COMERCIOCONECTA	440-580	€19,800-26,100
4.2 ServiciosConecta
Docs	Módulo	Horas	Coste €
82-87	Services Core + Provider + Offerings + Booking + Calendar + Video	220-280	9,900-12,600
88-93	Buzón Confianza + Firma Digital + AI Triaje + Presupuestador + Copilot	195-255	8,775-11,475
94-99	Dashboards + Facturación + Reviews + Notificaciones + API	150-200	6,750-9,000
	SUBTOTAL SERVICIOSCONECTA	565-735	€25,425-33,075
4.3 Platform Features
Docs	Módulo	Horas	Coste €
100-104	Frontend Architecture + Style Presets + Admin Center	313-411	14,085-18,495
108-110	AI Agent Flows + PWA + Onboarding ProductLed	400-530	18,000-23,850
111-116	Usage Pricing + Marketplace + Success + KB + Security + Analytics	605-825	27,225-37,125
	SUBTOTAL PLATFORM	1,318-1,766	€59,310-79,470
 
5. Fase 4: Expansion (Q1-Q2 2027)
5.1 SEPE Homologación (B2G)
Docs	Módulo	Horas	Coste €
105-107	Homologación + Módulo SEPE + Kit Validación	85-115	3,825-5,175
	SUBTOTAL SEPE	85-115	€3,825-5,175
5.2 AI Trilogy
Docs	Módulo	Horas	Coste €
128-128c	AI Content Hub + Frontend + Editor	340-460	15,300-20,700
129	AI Skills System	145-195	6,525-8,775
130	Tenant Knowledge Training	430-545	19,350-24,525
	SUBTOTAL AI TRILOGY	915-1,200	€41,175-54,000
5.3 Extras y Documentación
Docs	Módulo	Horas	Coste €
117	White Label	80-100	3,600-4,500
136-138	Email Templates + API Gateway + Security Audit	95-125	4,275-5,625
140	User Manuals	40-50	1,800-2,250
	SUBTOTAL EXTRAS	215-275	€9,675-12,375
 
6. Costes de Infraestructura (Mensual)
Servicio	Proveedor	Tier	Coste/mes	Coste/año
Servidor Dedicado	IONOS L-16	256GB RAM, NVMe	€289	€3,468
Backup Storage	IONOS/S3	500GB	€25	€300
Cloudflare	Pro + WAF	CDN + Security	€25	€300
Stripe	Variable	1.4% + €0.25/tx	~€200	~€2,400
SendGrid/ActiveCampaign	Pro	Email + Marketing	€89	€1,068
Claude API	Pay per use	~100K tokens/día	~€150	~€1,800
Dominios + SSL	Varios	jarabaimpact.com + subdom	€15	€180
Monitoring (Grafana Cloud)	Free/Pro	Métricas	€0-49	€0-588
TOTAL INFRAESTRUCTURA			€793-843	€9,516-10,104
 
7. Resumen de Inversión Total
7.1 Por Categoría
Categoría	Horas Min	Horas Max	Coste Min €	Coste Max €
Core Platform (01-07)	310	390	13,950	17,550
Infraestructura (131-140)	295	385	13,275	17,325
Empleabilidad (08-24)	575	745	25,875	33,525
Emprendimiento (25-44)	530	700	23,850	31,500
AgroConecta (47-82)	555	715	24,975	32,175
ComercioConecta (62-79)	440	580	19,800	26,100
ServiciosConecta (82-99)	565	735	25,425	33,075
Platform Features (100-117)	1,318	1,766	59,310	79,470
AI Trilogy (128-130)	915	1,200	41,175	54,000
SEPE + Extras	300	390	13,500	17,550
TOTAL DESARROLLO	5,803	7,606	€261,135	€342,270
7.2 Inversión Total Año 1
Concepto	Escenario Conservador	Escenario Agresivo
Desarrollo (horas)	€189,000 (4,200h)	€243,000 (5,400h)
Infraestructura (12 meses)	€9,500	€10,100
Contingencia (15%)	€28,350	€36,450
QA Externo / Auditorías	€5,000	€10,000
TOTAL AÑO 1	€231,850	€299,550
8. ROI Proyectado
Basado en el modelo de revenue del Triple Motor Económico.
Fuente	Año 1	Año 2	Año 3
Suscripciones SaaS (40%)	€60,000	€180,000	€400,000
Comisiones Marketplace (30%)	€30,000	€120,000	€300,000
Licencias/Franquicias (30%)	€20,000	€80,000	€200,000
TOTAL REVENUE	€110,000	€380,000	€900,000
Break-even	Mes 26	-	-

--- Fin del Presupuesto ---
