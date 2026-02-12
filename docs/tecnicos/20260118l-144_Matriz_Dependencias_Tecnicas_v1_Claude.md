MATRIZ DE DEPENDENCIAS
Orden de Implementación y Dependencias Técnicas

Leyenda	Significado
🔴 BLOQUEANTE	No se puede empezar sin este módulo
🟡 RECOMENDADO	Mejor si está listo, pero se puede avanzar parcialmente
🟢 INDEPENDIENTE	Se puede desarrollar en paralelo
→	Depende de (dirección de la flecha)
 
1. Grafo de Dependencias Core
┌─────────────────────────────────────────────────────────────┐
                                 │                     DEPENDENCIAS CORE                        │
                                 └─────────────────────────────────────────────────────────────┘
                                                            │
                                                            ▼
                                               ┌────────────────────────┐
                                               │   01_Esquema_BD        │
                                               │   (FUNDACIÓN)          │
                                               └───────────┬────────────┘
                                                           │
                         ┌─────────────────────────────────┼─────────────────────────────────┐
                         │                                 │                                 │
                         ▼                                 ▼                                 ▼
              ┌──────────────────┐            ┌──────────────────┐            ┌──────────────────┐
              │  02_Modulos      │            │  04_Permisos     │            │  05_Theming      │
              │  Personalizados  │            │  RBAC            │            │  (PARALELO)      │
              └────────┬─────────┘            └────────┬─────────┘            └──────────────────┘
                       │                               │
                       │         ┌─────────────────────┘
                       │         │
                       ▼         ▼
              ┌──────────────────────────┐
              │  07_MultiTenant          │
              │  (CRÍTICO)               │
              └───────────┬──────────────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
          ▼               ▼               ▼
  ┌───────────────┐ ┌───────────────┐ ┌───────────────┐
  │ 03_APIs       │ │ 06_ECA_Flows  │ │ 131_Infra     │
  │ Contratos     │ │               │ │ Deployment    │
  └───────┬───────┘ └───────────────┘ └───────┬───────┘
          │                                   │
          │                   ┌───────────────┼───────────────┐
          │                   │               │               │
          │                   ▼               ▼               ▼
          │           ┌───────────────┐ ┌───────────────┐ ┌───────────────┐
          │           │ 132_CICD      │ │ 133_Monitor   │ │ 135_Testing   │
          │           │ Pipeline      │ │ Alerting      │ │ Strategy      │
          │           └───────────────┘ └───────────────┘ └───────────────┘
          │
          └─────────────────────────┐
                                    │
                                    ▼
                         ┌──────────────────┐
                         │ 134_Stripe       │
                         │ Billing          │
                         └────────┬─────────┘
                                  │
                                  ▼
                         ┌──────────────────┐
                         │ 139_GoLive       │
                         │ Runbook          │
                         └──────────────────┘
 
2. Matriz de Dependencias por Módulo
2.1 Core Platform
Doc	Módulo	Depende de	Habilita	Prioridad
01	Esquema_BD	-	TODOS	🔴 Sprint 1
02	Modulos_Personalizados	01	03, 06, 07	🔴 Sprint 1
04	Permisos_RBAC	01	07, Verticales	🔴 Sprint 1
05	Theming	-	100 Frontend	🟢 Sprint 1-2
07	MultiTenant	01, 02, 04	TODAS verticales	🔴 Sprint 2
03	APIs_Contratos	01, 02, 07	137 API Gateway	🔴 Sprint 2
06	Flujos_ECA	01, 02	Automatizaciones	🟡 Sprint 2-3
2.2 Infraestructura
Doc	Módulo	Depende de	Habilita	Prioridad
131	Infrastructure_Deployment	-	132, 133, 139	🔴 Sprint 1
132	CICD_Pipeline	131	Deploys automáticos	🔴 Sprint 2
133	Monitoring_Alerting	131	Observabilidad	🔴 Sprint 2
134	Stripe_Billing	01, 03, 07	Revenue, Marketplace	🔴 Sprint 3
135	Testing_Strategy	01-07	QA	🔴 Sprint 2
139	GoLive_Runbook	131-135	Go-Live	🔴 Sprint 6
2.3 Empleabilidad
Doc	Módulo	Depende de	Habilita	Prioridad
08	LMS_Core	Core completo	09, 10, 17	🔴
09	Learning_Paths	08	10	🔴
10	Progress_Tracking	08, 09	Certificaciones	🟡
11	Job_Board_Core	Core completo	12, 13, 14, 19	🔴
12	Application_System	11	13	🔴
13	Employer_Portal	11, 12	23	🔴
15	Candidate_Profile	Core completo	16, 19, 22	🔴
16	CV_Builder	15	22	🔴
19	Matching_Engine	11, 15	20, 21	🔴
20	AI_Copilot	15, 19, 128-130	UX mejorada	🟡
 
2.4 AgroConecta (Modelo para Marketplaces)
Doc	Módulo	Depende de	Habilita	Prioridad
47	Commerce_Core	Core + 134 Stripe	48-61, 80-82	🔴
48	Product_Catalog	47	49, 55, 80	🔴
49	Order_System	47, 48	50, 51, 53, 54	🔴
50	Checkout_Flow	49, 134	Ventas	🔴
51	Shipping_Logistics	49	Fulfillment	🔴
52	Producer_Portal	47, 48	57	🔴
55	Search_Discovery	48	UX	🔴
80	Traceability_System	48	81	🔴
81	QR_Dynamic	80	Marketing físico	🟡
2.5 AI Trilogy
Doc	Módulo	Depende de	Habilita	Prioridad
128	AI_Content_Hub	Core, Claude API	128b, 128c, 129	🔴
128b	AI_Content_Hub_Frontend	128	UX de IA	🔴
128c	AI_Content_Hub_Editor	128	Workflow editorial	🟡
129	AI_Skills_System	128	130, Copilots	🔴
130	Knowledge_Training	128, 129	IA por tenant	🔴
 
3. Orden de Implementación Óptimo
3.1 Sprint Plan (2 semanas por sprint)
Sprint	Módulos	Horas	Entregable
Sprint 1	01, 02, 04, 131	180-220	Base de datos + infra inicial
Sprint 2	07, 03, 132, 133, 135	165-215	Multi-tenant + CI/CD
Sprint 3	05, 06, 134	180-220	Theming + Billing
Sprint 4	100, 109 (parcial)	150-200	Frontend base + PWA inicio
Sprint 5	08-10 (LMS)	105-135	Sistema de formación
Sprint 6	11-14 (Job Board)	145-185	Bolsa de empleo
Sprint 7	15-16, 19 (Matching)	125-165	Perfiles + Matching
Sprint 8	47-50 (Commerce)	175-215	Core de marketplace
Sprint 9	51-55 (Agro ops)	180-230	Envíos + búsqueda
Sprint 10	80-82, 139	115-155	Trazabilidad + Go-Live prep
Sprint 11	128-129 (AI base)	285-365	Sistema de IA
Sprint 12	130 (Knowledge)	430-545	IA por tenant
3.2 Camino Crítico
Estos módulos NO pueden retrasarse sin impactar el timeline completo:
01 → 02 → 07 → 134 → 47 → 49 → 50 → [GO-LIVE MARKETPLACE]
 │         │
 │         └→ 03 → 137 [API GATEWAY]
 │
 └→ 04 → 08 → 19 → [MATCHING ENGINE]
          │
          └→ 11 → 12 → 13 [EMPLOYER PORTAL]
4. Paralelización Posible
Estos tracks pueden desarrollarse en paralelo con equipos independientes:
Track	Módulos	Equipo Sugerido	Dependencia Principal
Track A: Core	01-07, 131-135	2 Backend Senior	Ninguna
Track B: Frontend	05, 100-103, 109	1 Frontend + 1 UX	Espera 01 para data
Track C: Empleabilidad	08-24	2 Backend	Core completo
Track D: AgroConecta	47-61, 80-82	2 Backend	Core + 134
Track E: AI	128-130	1 ML/AI + 1 Backend	Core + Claude API
5. Riesgos de Dependencias
Riesgo	Impacto	Mitigación
Retraso en 01 (BD)	Bloquea TODO	Prioridad absoluta, revisar diario
Retraso en 134 (Stripe)	Sin revenue, sin marketplace	Empezar con test mode en Sprint 2
Retraso en 07 (Multi-tenant)	Sin aislamiento de datos	MVP con single tenant si necesario
Claude API indisponible	AI features bloqueadas	Fallback a Gemini, cache de respuestas
Cambio de requisitos SEPE	Rehacer 105-107	Validar spec con SAE antes de implementar

--- Fin del Documento ---
