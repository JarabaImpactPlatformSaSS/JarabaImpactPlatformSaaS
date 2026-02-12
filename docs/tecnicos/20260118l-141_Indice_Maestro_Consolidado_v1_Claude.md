ÍNDICE MAESTRO CONSOLIDADO
Ecosistema Jaraba Impact Platform
170+ Documentos Técnicos

Métrica	Valor
Total Documentos:	170+
Verticales:	6 (Core, Empleabilidad, Emprendimiento, Agro, Comercio, Servicios)
Horas Desarrollo Est.:	3,500 - 4,500 horas
Timeline:	18 meses (Q1 2026 - Q2 2027)
Fecha Consolidación:	Enero 2026
Estado:	✅ Documentación Completa - Ready for Development
 
1. Mapa del Ecosistema
Estructura jerárquica de los 170+ documentos técnicos organizados por vertical y función.
Categoría	Rango Docs	Cantidad	Estado
🏗️ Core Platform	01-07	7	✅ Completo
💼 Empleabilidad	08-24	17	✅ Completo
🚀 Emprendimiento	25-44	20	✅ Completo
🌾 AgroConecta	47-61, 80-82	18	✅ Completo
🏪 ComercioConecta	62-79	18	✅ Completo
👔 ServiciosConecta	82-99	18	✅ Completo
🎨 Frontend & UX	100-104	5	✅ Completo
📜 SEPE Homologación	105-107	3	✅ Completo
⚙️ Platform Features	108-117	10	✅ Completo
📊 Estrategia	118-127	10	✅ Completo
🤖 AI Trilogy	128-130	5	✅ Completo
🔧 Infraestructura	131-140	10	✅ Completo
 
2. Core Platform (Docs 01-07)
Infraestructura base que habilita todas las verticales. DEBE implementarse primero.
Doc	Nombre	Dependencias	Horas	Prioridad
01	Core_Entidades_Esquema_BD	Ninguna	40-50	🔴 P0
02	Core_Modulos_Personalizados	01	60-80	🔴 P0
03	Core_APIs_Contratos	01, 02	50-60	🔴 P0
04	Core_Permisos_RBAC	01, 02	30-40	🔴 P0
05	Core_Theming_jaraba_theme	Ninguna	40-50	🟡 P1
06	Core_Flujos_ECA	01, 02	40-50	🔴 P0
07	Core_Configuracion_MultiTenant	01-04	50-60	🔴 P0
Orden de Lectura Recomendado:
•	1. Doc 01 → Entender modelo de datos base
•	2. Doc 07 → Arquitectura multi-tenant
•	3. Doc 04 → Sistema de permisos
•	4. Doc 02 → Módulos custom
•	5. Doc 03 → Contratos de API
•	6. Doc 06 → Automatizaciones ECA
•	7. Doc 05 → Theming (paralelo)
 
3. Empleabilidad (Docs 08-24)
Vertical de empleo: LMS, job board, matching, CV builder. Integración con PIIL Junta de Andalucía.
Doc	Nombre	Dependencias	Horas	Prioridad
08	Empleabilidad_LMS_Core	Core	40-50	🔴 P0
09	Empleabilidad_Learning_Paths	08	35-45	🔴 P0
10	Empleabilidad_Progress_Tracking	08, 09	30-40	🟡 P1
11	Empleabilidad_Job_Board_Core	Core	45-55	🔴 P0
12	Empleabilidad_Application_System	11	35-45	🔴 P0
13	Empleabilidad_Employer_Portal	11, 12	40-50	🔴 P0
14	Empleabilidad_Job_Alerts	11	25-35	🟡 P1
15	Empleabilidad_Candidate_Profile	Core	35-45	🔴 P0
16	Empleabilidad_CV_Builder	15	40-50	🔴 P0
17	Empleabilidad_Credentials_System	08	30-40	🟡 P1
18	Empleabilidad_Certification_Workflow	17	25-35	🟡 P1
19	Empleabilidad_Matching_Engine	11, 15	50-60	🔴 P0
20	Empleabilidad_AI_Copilot	15, 19	45-55	🟡 P1
21	Empleabilidad_Recommendation_System	19	35-45	🟡 P1
22	Empleabilidad_Dashboard_JobSeeker	15, 19	30-40	🔴 P0
23	Empleabilidad_Dashboard_Employer	13	30-40	🔴 P0
24	Empleabilidad_Impact_Metrics	All	25-35	🟢 P2
Tracks de Implementación:
•	Track A (LMS): 08 → 09 → 10 → 17 → 18
•	Track B (Job Board): 11 → 12 → 13 → 14
•	Track C (Candidato): 15 → 16 → 22
•	Track D (Matching/AI): 19 → 20 → 21
 
4. Emprendimiento (Docs 25-44)
Vertical de apoyo a emprendedores: diagnóstico, mentoría, herramientas de negocio.
Doc	Nombre	Dependencias	Horas	Prioridad
25	Emprendimiento_Business_Diagnostic_Core	Core	40-50	🔴 P0
26	Emprendimiento_Digital_Maturity_Assessment	25	35-45	🔴 P0
27	Emprendimiento_Competitive_Analysis_Tool	25	30-40	🟡 P1
28	Emprendimiento_Digitalization_Paths	26	35-45	🔴 P0
29	Emprendimiento_Action_Plans	28	30-40	🔴 P0
30	Emprendimiento_Progress_Milestones	29	25-35	🟡 P1
31	Emprendimiento_Mentoring_Core	Core	40-50	🔴 P0
32	Emprendimiento_Mentoring_Sessions	31	35-45	🔴 P0
33	Emprendimiento_Mentor_Dashboard	31, 32	30-40	🟡 P1
34	Emprendimiento_Collaboration_Groups	31	25-35	🟡 P1
35	Emprendimiento_Networking_Events	34	30-40	🟢 P2
36	Emprendimiento_Business_Model_Canvas	25	35-45	🔴 P0
37	Emprendimiento_MVP_Validation	36	30-40	🟡 P1
38	Emprendimiento_Financial_Projections	36	35-45	🟡 P1
39	Emprendimiento_Digital_Kits	28	25-35	🟡 P1
40	Emprendimiento_Membership_System	Core	30-40	🟢 P2
41	Emprendimiento_Dashboard_Entrepreneur	All	35-45	🔴 P0
42	Emprendimiento_Dashboard_Program	All	30-40	🟡 P1
43	Emprendimiento_Impact_Metrics	All	25-35	🟢 P2
44	Emprendimiento_AI_Business_Copilot	25, 36	45-55	🟡 P1
 
5. AgroConecta (Docs 47-61, 80-82)
Marketplace agroalimentario: catálogo, pedidos, envíos, trazabilidad. Vertical modelo.
Doc	Nombre	Dependencias	Horas	Prioridad
47	AgroConecta_Commerce_Core	Core	50-60	🔴 P0
48	AgroConecta_Product_Catalog	47	40-50	🔴 P0
49	AgroConecta_Order_System	47, 48	45-55	🔴 P0
50	AgroConecta_Checkout_Flow	49	40-50	🔴 P0
51	AgroConecta_Shipping_Logistics	49	45-55	🔴 P0
52	AgroConecta_Producer_Portal	47, 48	40-50	🔴 P0
53	AgroConecta_Customer_Portal	49	35-45	🟡 P1
54	AgroConecta_Reviews_System	49	25-35	🟡 P1
55	AgroConecta_Search_Discovery	48	35-45	🔴 P0
56	AgroConecta_Promotions_Coupons	47	30-40	🟡 P1
57	AgroConecta_Analytics_Dashboard	All	35-45	🟡 P1
58	AgroConecta_Admin_Panel	All	30-40	🟡 P1
59	AgroConecta_Notifications_System	49	25-35	🟡 P1
60	AgroConecta_Mobile_App	All	60-80	🟢 P2
61	AgroConecta_API_Integration_Guide	All	20-30	🟡 P1
80	AgroConecta_Traceability_System	48	40-50	🔴 P0
81	AgroConecta_QR_Dynamic	80	25-35	🔴 P0
82	AgroConecta_Partner_Document_Hub	47	30-40	🟡 P1
 
6. ComercioConecta (Docs 62-79)
Comercio local: POS, flash offers, QR dinámico, SEO local. Reutiliza 70% de AgroConecta.
Doc	Nombre	Dependencias	Horas	Prioridad
62	ComercioConecta_Commerce_Core	47 (fork)	30-40	🔴 P0
63	ComercioConecta_POS_Integration	62	45-55	🔴 P0
64	ComercioConecta_Flash_Offers	62	35-45	🔴 P0
65	ComercioConecta_Dynamic_QR	64	25-35	🔴 P0
66	ComercioConecta_Product_Catalog	62	25-35	🟡 P1
67	ComercioConecta_Order_System	62	30-40	🟡 P1
68	ComercioConecta_Checkout_Flow	67	25-35	🟡 P1
69	ComercioConecta_Shipping_Logistics	67	30-40	🟡 P1
70	ComercioConecta_Search_Discovery	66	25-35	🟡 P1
71	ComercioConecta_Local_SEO	62	35-45	🔴 P0
72	ComercioConecta_Promotions_Coupons	64	25-35	🟡 P1
73	ComercioConecta_Reviews_Ratings	67	20-30	🟢 P2
74	ComercioConecta_Merchant_Portal	62	35-45	🔴 P0
75	ComercioConecta_Customer_Portal	67	25-35	🟡 P1
76	ComercioConecta_Notifications_System	67	20-30	🟡 P1
77	ComercioConecta_Mobile_App	All	50-60	🟢 P2
78	ComercioConecta_Admin_Panel	All	25-35	🟡 P1
79	ComercioConecta_API_Integration_Guide	All	15-25	🟢 P2
 
7. ServiciosConecta (Docs 82-99)
Servicios profesionales: reservas, videoconsulta, firma digital, buzón de confianza.
Doc	Nombre	Dependencias	Horas	Prioridad
82	ServiciosConecta_Services_Core	Core	40-50	🔴 P0
83	ServiciosConecta_Provider_Profile	82	30-40	🔴 P0
84	ServiciosConecta_Service_Offerings	83	35-45	🔴 P0
85	ServiciosConecta_Booking_Engine_Core	84	45-55	🔴 P0
86	ServiciosConecta_Calendar_Sync	85	30-40	🟡 P1
87	ServiciosConecta_Video_Conferencing	85	40-50	🔴 P0
88	ServiciosConecta_Buzon_Confianza	82	35-45	🔴 P0
89	ServiciosConecta_Firma_Digital_PAdES	88	40-50	🔴 P0
90	ServiciosConecta_Portal_Cliente_Documental	88, 89	35-45	🟡 P1
91	ServiciosConecta_AI_Triaje_Casos	82	40-50	🟡 P1
92	ServiciosConecta_Presupuestador_Auto	84	35-45	🟡 P1
93	ServiciosConecta_Copilot_Servicios	82	45-55	🟡 P1
94	ServiciosConecta_Dashboard_Profesional	All	35-45	🔴 P0
95	ServiciosConecta_Dashboard_Admin	All	30-40	🟡 P1
96	ServiciosConecta_Sistema_Facturacion	85	40-50	🔴 P0
97	ServiciosConecta_Reviews_Ratings	85	20-30	🟢 P2
98	ServiciosConecta_Notificaciones_Multicanal	All	25-35	🟡 P1
99	ServiciosConecta_API_Integration_Guide	All	20-30	🟢 P2
 
8. Platform Features (Docs 100-117)
Funcionalidades transversales: frontend, admin, PWA, pricing, analytics.
Doc	Nombre	Dependencias	Horas	Prioridad
100	Frontend_Architecture_MultiTenant	Core	128-176	🔴 P0
101	Industry_Style_Presets	100	40-50	🟡 P1
102	Industry_Style_Presets_Premium	101	60-80	🟢 P2
103	UX_Journey_Specifications_Avatar	100	45-55	🟡 P1
104	SaaS_Admin_Center_Premium	Core	80-100	🔴 P0
105	Homologacion_Teleformacion_SEPE	08	25-35	🔴 P0 (B2G)
106	Modulo_SEPE_Teleformacion	105	40-50	🔴 P0 (B2G)
107	SEPE_Kit_Validacion	106	20-30	🔴 P0 (B2G)
108	Platform_AI_Agent_Flows	Core, 128	65-85	🟡 P1
109	Platform_PWA_Mobile	100	180-240	🔴 P0
110	Platform_Onboarding_ProductLed	100	155-205	🔴 P0
111	Platform_UsageBased_Pricing	134	95-125	🔴 P0
112	Platform_Integration_Marketplace	Core	120-160	🟡 P1
113	Platform_Customer_Success	104	85-115	🟡 P1
114	Platform_Knowledge_Base	Core	250-340	🔴 P0
115	Platform_Security_Compliance	Core	60-80	🔴 P0
116	Platform_Advanced_Analytics	All	95-125	🟡 P1
117	Platform_WhiteLabel	100	80-100	🟢 P2
 
9. AI Trilogy & Infraestructura (Docs 128-140)
Sistema de IA y operaciones: content hub, skills, knowledge training, DevOps.
Doc	Nombre	Dependencias	Horas	Prioridad
128	Platform_AI_Content_Hub	Core	170-230	🔴 P0
128b	Platform_AI_Content_Hub_Frontend	128	80-110	🔴 P0
128c	Platform_AI_Content_Hub_Editor	128	90-120	🟡 P1
129	Platform_AI_Skills_System	128	145-195	🔴 P0
129A	Platform_AI_Skills_System_AnexoA	129	-	Anexo
130	Platform_Tenant_Knowledge_Training	128, 129	430-545	🔴 P0
131	Platform_Infrastructure_Deployment	Ninguna	40-50	🔴 P0
132	Platform_CICD_Pipeline	131	30-40	🔴 P0
133	Platform_Monitoring_Alerting	131	25-35	🔴 P0
134	Platform_Stripe_Billing_Integration	Core	300-360	🔴 P0
135	Platform_Testing_Strategy	All	35-45	🔴 P0
136	Platform_Email_Templates	Core	30-40	🟡 P1
137	Platform_API_Gateway_Developer_Portal	03	40-50	🟡 P1
138	Platform_Security_Audit_Procedures	115	25-35	🟡 P1
139	Platform_GoLive_Runbook	131-135	20-30	🔴 P0
140	Platform_User_Manuals	All	40-50	🟢 P2
 
10. Estrategia & Negocio (Docs 118-127)
Documentación estratégica: roadmap, pitch, presupuesto, marca personal.
Doc	Nombre	Propósito
118	Roadmap_Implementacion_v1	Timeline 18 meses con fases y costos
119	Pitch_Deck_OnePager_v1	Material para inversores/partners
120	Presupuesto_Consolidado_v1	Desglose financiero completo
121	Casos_de_Uso_Avatares_v1	User personas y journeys
122	Auditoria_Coherencia_v1	Verificación de consistencia entre docs
123	PepeJaraba_Personal_Brand_Plan	Estrategia de marca personal
124	PepeJaraba_Content_Ready	Contenido listo para publicar
125	Blog_Articulos_v1	Pipeline de artículos
126	Personal_Brand_Tenant_Config	Config técnica del tenant personal
127	PED_Corporate_Website	Especificación web corporativa
 
11. Orden de Lectura Recomendado
Para CTO / Tech Lead:
•	1. Doc 01 (Esquema BD) → Entender el modelo de datos
•	2. Doc 07 (Multi-Tenant) → Arquitectura de aislamiento
•	3. Doc 131 (Infraestructura) → Stack de producción
•	4. Doc 134 (Stripe Billing) → Modelo de monetización
•	5. Doc 118 (Roadmap) → Timeline de implementación
Para Developer Backend:
•	1. Docs 01-07 (Core) → Fundamentos
•	2. Doc 47 (AgroConecta Commerce) → Vertical de referencia
•	3. Doc 128-130 (AI Trilogy) → Sistema de IA
•	4. Doc 132-133 (CI/CD, Monitoring) → DevOps
Para Developer Frontend:
•	1. Doc 100 (Frontend Architecture) → Sistema de componentes
•	2. Doc 05 (Theming) → Sistema de estilos
•	3. Doc 101-102 (Style Presets) → Customización por industria
•	4. Doc 109 (PWA) → Mobile-first
Para Product Owner:
•	1. Doc 118 (Roadmap) → Prioridades
•	2. Doc 121 (Casos de Uso) → User journeys
•	3. Docs verticales P0 → Features core de cada vertical
•	4. Doc 120 (Presupuesto) → Inversión requerida
Para Inversores/Partners:
•	1. Doc 119 (Pitch Deck) → Overview ejecutivo
•	2. Doc 118 (Roadmap) → Timeline y fases
•	3. Doc 120 (Presupuesto) → Financials

--- Fin del Índice Maestro ---
