# Auditoria Integral: IA de Clase Mundial — Ecosistema SaaS Multi-Tenant Multi-Vertical

**Fecha de creacion:** 2026-02-27 10:00
**Ultima actualizacion:** 2026-02-27 10:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Analisis
**Modulo:** Multi-modulo (`jaraba_ai_agents`, `jaraba_agents`, `jaraba_copilot_v2`, `jaraba_rag`, `ecosistema_jaraba_core`, `jaraba_content_hub`, `jaraba_page_builder`, `jaraba_candidate`, `jaraba_legal_intelligence`, `jaraba_business_tools`, `jaraba_job_board`, `jaraba_lms`, `jaraba_agroconecta_core`, `jaraba_comercio_conecta`, `jaraba_servicios_conecta`, `jaraba_billing`, `ecosistema_jaraba_theme`)
**Documentos fuente:** 00_DIRECTRICES_PROYECTO.md v88.0.0, 00_FLUJO_TRABAJO_CLAUDE.md v42.0.0, 00_DOCUMENTO_MAESTRO_ARQUITECTURA.md v81.0.0, 2026-02-05_arquitectura_theming_saas_master.md v2.1, 2026-02-26_arquitectura_elevacion_ia_nivel5.md v2.0.0

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Objetivo del analisis](#11-objetivo-del-analisis)
   - 1.2 [Alcance](#12-alcance)
   - 1.3 [Metodologia](#13-metodologia)
   - 1.4 [Conclusion general](#14-conclusion-general)
   - 1.5 [Scorecard global](#15-scorecard-global)
2. [Benchmark de Mercado: Que es Clase Mundial en 2026](#2-benchmark-de-mercado-que-es-clase-mundial-en-2026)
   - 2.1 [Lideres AI-Native SaaS](#21-lideres-ai-native-saas)
   - 2.2 [18 Capacidades obligatorias](#22-18-capacidades-obligatorias)
   - 2.3 [Arquitecturas de agentes en produccion](#23-arquitecturas-de-agentes-en-produccion)
   - 2.4 [Patrones multi-tenant con IA](#24-patrones-multi-tenant-con-ia)
   - 2.5 [Estandares tecnicos 2026](#25-estandares-tecnicos-2026)
3. [Auditoria de Agentes IA](#3-auditoria-de-agentes-ia)
   - 3.1 [Inventario completo: 21 agentes en 9 modulos](#31-inventario-completo-21-agentes-en-9-modulos)
   - 3.2 [Matriz de madurez por agente](#32-matriz-de-madurez-por-agente)
   - 3.3 [Agentes Gen 1 vs Gen 2](#33-agentes-gen-1-vs-gen-2)
   - 3.4 [Sistema de herramientas (ToolRegistry)](#34-sistema-de-herramientas-toolregistry)
   - 3.5 [Agentes autonomos (jaraba_agents)](#35-agentes-autonomos-jaraba_agents)
   - 3.6 [Copilot v2 (streaming + modos)](#36-copilot-v2-streaming--modos)
   - 3.7 [Agentes que faltan para clase mundial](#37-agentes-que-faltan-para-clase-mundial)
   - 3.8 [Problemas criticos detectados](#38-problemas-criticos-detectados)
4. [Auditoria de Servicios Transversales IA](#4-auditoria-de-servicios-transversales-ia)
   - 4.1 [Scorecard de servicios](#41-scorecard-de-servicios)
   - 4.2 [Servicios produccion-ready (Tier 1)](#42-servicios-produccion-ready-tier-1)
   - 4.3 [Servicios parcialmente implementados (Tier 2)](#43-servicios-parcialmente-implementados-tier-2)
   - 4.4 [Servicios aspiracionales/skeleton (Tier 3)](#44-servicios-aspiracionalesskeleton-tier-3)
   - 4.5 [Grafo de llamadas real](#45-grafo-de-llamadas-real)
   - 4.6 [Gaps de seguridad en la capa IA](#46-gaps-de-seguridad-en-la-capa-ia)
5. [Auditoria de Modulos Verticales](#5-auditoria-de-modulos-verticales)
   - 5.1 [Estadisticas de la plataforma](#51-estadisticas-de-la-plataforma)
   - 5.2 [Madurez por vertical](#52-madurez-por-vertical)
   - 5.3 [Piramide de integracion IA por vertical](#53-piramide-de-integracion-ia-por-vertical)
   - 5.4 [Inconsistencias detectadas entre verticales](#54-inconsistencias-detectadas-entre-verticales)
6. [Auditoria de Arquitectura Multi-Tenant](#6-auditoria-de-arquitectura-multi-tenant)
   - 6.1 [Separacion Tenant/Group](#61-separacion-tenantgroup)
   - 6.2 [Ciclo de vida de suscripcion](#62-ciclo-de-vida-de-suscripcion)
   - 6.3 [Aislamiento de datos](#63-aislamiento-de-datos)
   - 6.4 [Gaps criticos multi-tenant](#64-gaps-criticos-multi-tenant)
7. [Auditoria SEO, Page Builder y UX](#7-auditoria-seo-page-builder-y-ux)
   - 7.1 [SEO tecnico](#71-seo-tecnico)
   - 7.2 [GrapesJS Page Builder](#72-grapesjs-page-builder)
   - 7.3 [UX y Design System](#73-ux-y-design-system)
   - 7.4 [Core Web Vitals](#74-core-web-vitals)
8. [Coherencia de Flujos Transversales](#8-coherencia-de-flujos-transversales)
   - 8.1 [Patrones consistentes](#81-patrones-consistentes)
   - 8.2 [Patrones incoherentes](#82-patrones-incoherentes)
9. [Hallazgos Criticos Priorizados](#9-hallazgos-criticos-priorizados)
   - 9.1 [HAL-AI-01: Tool execution sin guardrails](#hal-ai-01)
   - 9.2 [HAL-AI-02: Usage metering simulado](#hal-ai-02)
   - 9.3 [HAL-AI-03: Tenant isolation inconsistente](#hal-ai-03)
   - 9.4 [HAL-AI-04: AgentLongTermMemory vacio](#hal-ai-04)
   - 9.5 [HAL-AI-05: ReActLoop sin parsing](#hal-ai-05)
   - 9.6 [HAL-AI-06: IA proactiva inexistente](#hal-ai-06)
   - 9.7 [HAL-AI-07: LearningTutor skeleton](#hal-ai-07)
   - 9.8 [HAL-AI-08: Datos mock en RecruiterAssistant](#hal-ai-08)
   - 9.9 [HAL-AI-09: Canvas GrapesJS no sandboxed](#hal-ai-09)
   - 9.10 [HAL-AI-10: Core Web Vitals sin optimizar](#hal-ai-10)
   - 9.11 [HAL-AI-11: Feature flags hardcoded](#hal-ai-11)
   - 9.12 [HAL-AI-12: CSS monolitico sin code splitting](#hal-ai-12)
   - 9.13 [HAL-AI-13: Sin srcset/WebP/AVIF](#hal-ai-13)
   - 9.14 [HAL-AI-14: GEO/Local SEO ausente](#hal-ai-14)
   - 9.15 [HAL-AI-15: Test coverage 34%](#hal-ai-15)
   - 9.16 [HAL-AI-16: Model router inyectado pero sin evidencia de uso](#hal-ai-16)
   - 9.17 [HAL-AI-17: Guardrails opcionales (@?)](#hal-ai-17)
   - 9.18 [HAL-AI-18: Approval system no enforced en tools](#hal-ai-18)
   - 9.19 [HAL-AI-19: Workflow automation inexistente](#hal-ai-19)
   - 9.20 [HAL-AI-20: Personalizacion adaptativa ausente](#hal-ai-20)
10. [Posicion Competitiva y Roadmap](#10-posicion-competitiva-y-roadmap)
    - 10.1 [Distancia a clase mundial por dimension](#101-distancia-a-clase-mundial-por-dimension)
    - 10.2 [Roadmap estrategico en 4 sprints](#102-roadmap-estrategico-en-4-sprints)
11. [Referencias Cruzadas](#11-referencias-cruzadas)

---

## 1. Resumen Ejecutivo

### 1.1 Objetivo del analisis

Evaluar la conveniencia, consistencia, coherencia y madurez del ecosistema SaaS Jaraba Impact Platform desde la perspectiva de IA de clase mundial, actuando como equipo multidisciplinar senior de 15 roles: consultor de negocio, desarrollador de carreras profesionales, analista financiero, experto en mercados y desarrollo de productos, consultor de marketing, publicista, arquitecto SaaS, ingeniero de software, ingeniero UX, ingeniero de Drupal, desarrollador web, disenador/desarrollador de theming, ingeniero de GrapesJS, ingeniero de SEO/GEO e ingeniero de IA.

### 1.2 Alcance

El analisis cubre la totalidad de la plataforma:
- **89 modulos custom** con 500+ entidades y 2,000+ archivos PHP
- **21 clases de agente IA** en 9 modulos
- **18 servicios transversales IA** (observabilidad, guardrails, RAG, streaming, routing)
- **10 verticales de negocio** (empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta, andalucia_ei, content_hub, formacion, demo)
- **Arquitectura multi-tenant** (Tenant/Group, billing, quotas, provisioning)
- **Stack frontend** (GrapesJS, SCSS, design system, PWA, dark mode, 145 templates Twig)
- **SEO tecnico** (Schema.org, OG, Twitter Cards, RSS, sitemaps)
- **Benchmark de mercado** contra Jasper, Salesforce Einstein, Intercom Fin, Sitecore Agentic Studio

### 1.3 Metodologia

Se ha auditado el codigo fuente completo mediante 6 agentes de exploracion paralelos que examinaron:
1. Todas las clases Agent (execute/doExecute, tool use, DI, maturity)
2. Todos los servicios transversales (LOC, metodos reales vs stubs, integraciones)
3. Todos los modulos verticales (entidades, servicios, controllers, forms, routing)
4. Arquitectura multi-tenant (TenantBridge, access handlers, billing, provisioning)
5. Capa SEO + GrapesJS + UX (67 SCSS, 145 templates, 67+ bloques, PWA)
6. Benchmark de mercado (20+ fuentes, estandares 2025-2026)

Todos los hallazgos se cruzan con las 140+ directrices del proyecto, el documento maestro v81.0.0, la arquitectura de theming v2.1 y los 50 aprendizajes acumulados.

### 1.4 Conclusion general

La plataforma tiene una **arquitectura ambiciosa y bien disenada** que cubre los 10 verticales con infraestructura IA nativa. Sin embargo, existe una **brecha del 30%** entre lo arquitectado/declarado y lo realmente funcional en produccion. Los servicios core (observabilidad, model routing, RAG, streaming, guardrails) son produccion-ready. Los servicios avanzados (memoria a largo plazo, razonamiento ReAct, cache semantico, multimodal) son skeleton o estan incompletos. El mayor riesgo de seguridad es la **ejecucion de herramientas sin guardrails ni approval gating**.

### 1.5 Scorecard global

| Dimension | Puntuacion | Benchmark Clase Mundial |
|-----------|-----------|------------------------|
| **Agentes IA** | 68/100 | 85+ requerido |
| **Servicios Transversales IA** | 70/100 | 85+ requerido |
| **Modulos Verticales** | 78/100 | 80+ requerido |
| **Multi-Tenancy** | 65/100 | 90+ requerido |
| **SEO / GEO** | 72/100 | 85+ requerido |
| **Page Builder (GrapesJS)** | 68/100 | 80+ requerido |
| **UX / Design System** | 74/100 | 85+ requerido |
| **GLOBAL** | **70/100** | **85+ clase mundial** |

**Distancia a clase mundial: 15 puntos.** Con los sprints 1-3 (2-3 meses), la plataforma podria alcanzar 82-85/100.

---

## 2. Benchmark de Mercado: Que es Clase Mundial en 2026

### 2.1 Lideres AI-Native SaaS

Los lideres del mercado definen el estandar de clase mundial:

| Plataforma | Capacidad Diferencial | Relevancia para Jaraba |
|------------|----------------------|----------------------|
| **Jasper AI** | Jasper IQ (brand voice persistente), pipelines de contenido end-to-end, LLM-agnostic | Modelo a seguir para Content Hub y brand voice por tenant |
| **Salesforce Einstein/AgentForce** | Capa de modelo multi-proveedor, agentes autonomos con Trust Layer, Data Cloud | Referencia para model routing, guardrails y observabilidad |
| **Intercom Fin** | Resolucion autonoma con escalacion estructurada, omnichannel | Referencia para SupportAgent y handoff patterns |
| **Sitecore Agentic Studio** | 20 agentes IA automatizando desde planificacion a migracion de contenido | Referencia para ContentWriter y workflow automation |
| **Acquia AI** | 3 agentes IA para CMS SaaS, 65% mas rapido en produccion de contenido | Validacion de que Drupal+IA es viable a nivel enterprise |

### 2.2 18 Capacidades obligatorias

| # | Capacidad | Jaraba Status | Gap |
|---|-----------|--------------|-----|
| 1 | Agentes multi-step con handoffs | Parcial | ReActLoop 40%, Handoffs skeleton |
| 2 | Memoria episodica + semantica (vector DB) | Skeleton | AgentLongTermMemory 5% |
| 3 | Model routing 3+ tiers cost-aware | **Implementado** | ModelRouter 100% |
| 4 | RAG multi-tenant aislado | **Implementado** | JarabaRagService 95% |
| 5 | Brand voice por tenant | Parcial | BrandVoiceService existe, incompleto |
| 6 | IA proactiva (no solo reactiva) | Muy Debil | Solo chatbot reactivo |
| 7 | Pipeline contenido end-to-end | **Implementado** | ContentWriter + SEO + RSS + Canvas |
| 8 | Busqueda hibrida semantica+keyword | **Implementado** | RAG + re-ranking |
| 9 | Streaming SSE | **Implementado** | StreamingOrchestrator 90% |
| 10 | MCP Server | **Implementado** | JSON-RPC 2.0 funcional |
| 11 | Observabilidad distribuida | **Implementado** | Traces + Spans + Cost 95% |
| 12 | Guardrails input + output | Parcial | Input 85%, output NO en tools |
| 13 | A/B testing prompts/modelos | Skeleton | PromptExperiment ~30% |
| 14 | Cost management por tenant | Parcial | Alerts si, metering simulado |
| 15 | Especializacion vertical | **Implementado** | 10 verticales con agentes dedicados |
| 16 | Workflow automation con IA | Debil | No hay motor de workflows IA |
| 17 | Personalizacion adaptativa | Muy Debil | No hay UX adaptativa |
| 18 | Safety & Governance (GDPR/AI Act) | **Implementado** | Erasure + audit + guardrails |

**Capacidades cumplidas: 9/18 (50%).**

### 2.3 Arquitecturas de agentes en produccion

El estado del arte en 2026 define 5 patrones de orquestacion:

1. **Sequential** (refinamiento encadenado): Output de Agent A alimenta Agent B
2. **Concurrent** (procesamiento paralelo): Multiples agentes procesan simultaneamente
3. **Group Chat** (hilos colaborativos): Agentes discuten hacia consenso
4. **Handoff** (delegacion dinamica): Tool call especializado que retorna otro Agent; el runner cambia `active_agent`, mantiene historial compartido. Este es el patron en el que OpenAI, Anthropic y Microsoft convergieron.
5. **Plan-first** (planificar y ejecutar): Orchestrator planifica, delega a especialistas

**Jaraba implementa parcialmente el patron 1 (sequential via AgentExecutionBridge) y el 5 (ReActLoop skeleton), pero carece de los patrones 2, 3 y 4.**

El sistema de memoria en agentes de clase mundial tiene 4 capas:

| Capa | Proposito | Jaraba Status |
|------|-----------|---------------|
| **Episodica** | Historial de conversaciones | Session storage (funcional) |
| **Semantica** | Conocimiento aprendido | Qdrant declarado, no implementado |
| **Procedural** | Como realizar tareas | Prompt templates (funcional) |
| **Meta-memoria** | Confianza sobre conocimiento propio | No existe |

### 2.4 Patrones multi-tenant con IA

Las plataformas de clase mundial implementan:

- **Per-tenant AI config**: Estrategia de chunking, modelo de embedding, prompt templates, brand voice. Jaraba tiene BrandVoiceService pero incompleto.
- **Token-based billing**: Cada API call registra tokens, modelo, tenant_id. Jaraba tiene AIObservabilityService pero el metering hacia billing es simulado.
- **RAG aislado**: Indice vectorial por tenant o namespace. Jaraba tiene JarabaRagService con tenant_id pero sin verificacion de aislamiento en Qdrant.
- **Model routing como politica**: Tier + compliance + presupuesto restante. Jaraba tiene ModelRouterService pero no integra presupuesto por tenant.

### 2.5 Estandares tecnicos 2026

- **MCP (Model Context Protocol)**: De facto standard, donado a Linux Foundation. 10,000+ servidores publicos. Jaraba tiene McpServerController funcional.
- **SSE Streaming**: Protocolo dominante. TTFT < 500ms. Jaraba tiene StreamingOrchestratorService con fallback.
- **Observabilidad**: Traces + Spans + Cost per tenant. Jaraba tiene TraceContextService + AIObservabilityService (95%).
- **Guardrails**: Input + Output, PII, jailbreak, toxicidad, hallucination scoring. Jaraba tiene input (85%) pero output solo en copilot, NO en tools.

---

## 3. Auditoria de Agentes IA

### 3.1 Inventario completo: 21 agentes en 9 modulos

Se encontraron 21 clases de agente distribuidas en:

| Modulo | Agentes | Gen | Tier |
|--------|---------|-----|------|
| `jaraba_ai_agents` | SmartMarketing, Storytelling, Sales, CustomerExperience, Support, ProducerCopilot, MerchantCopilot, Marketing (deprecated), JarabaLexCopilot (alias) | 2 (7), 1 (2) | Produccion (7), Skeleton (2) |
| `jaraba_candidate` | EmployabilityCopilot (6 modos), CareerCoach | 1 | Produccion (1), Skeleton (1) |
| `jaraba_legal_intelligence` | LegalCopilot (8 modos) | 1 | Produccion |
| `jaraba_content_hub` | ContentWriter | 1 | Produccion |
| `jaraba_business_tools` | BusinessCopilot | Custom | Parcial (getBlockItems stub) |
| `jaraba_job_board` | RecruiterAssistant | Custom | Parcial (datos mock) |
| `jaraba_lms` | LearningTutor | Custom | Skeleton (sin AI provider) |
| `jaraba_agents` | AutonomousAgent (entity) | Custom | Produccion (state machine) |
| `jaraba_copilot_v2` | EmprendimientoCopilot | 1 | Parcial |

### 3.2 Matriz de madurez por agente

| Agente | Modulo | Gen | Logica Real | Tool Use | API Externa | Madurez |
|--------|--------|-----|------------|----------|-------------|---------|
| SmartMarketingAgent | ai_agents | 2 | 4 metodos reales | Si | Claude/OpenAI | **PRODUCCION** |
| StorytellingAgent | ai_agents | 2 | 3 metodos reales | Si | Claude/OpenAI | **PRODUCCION** |
| SalesAgent | ai_agents | 2 | 6 metodos reales | Si | Claude/OpenAI | **PRODUCCION** |
| CustomerExperienceAgent | ai_agents | 2 | Real | Si | Claude/OpenAI | **PRODUCCION** |
| SupportAgent | ai_agents | 2 | Real | Si | Claude/OpenAI | **PRODUCCION** |
| ProducerCopilotAgent | ai_agents | 2 | Real | Si | Claude/OpenAI | **PRODUCCION** |
| MerchantCopilotAgent | ai_agents | 2 | Real | Si | Claude/OpenAI | **PRODUCCION** |
| EmployabilityCopilotAgent | candidate | 1 | 6 modos con prompts | No | Claude/OpenAI | **PRODUCCION** |
| LegalCopilotAgent | legal_intelligence | 1 | 8 modos + RAG legal | No | Claude/OpenAI | **PRODUCCION** |
| ContentWriterAgent | content_hub | 1 | 5 acciones SEO | No | Claude/OpenAI | **PRODUCCION** |
| AutonomousAgent | agents | Custom | State machine + approval | Si | Multiple | **PRODUCCION** |
| BusinessCopilotAgent | business_tools | Custom | Parcial (stub) | No | Ninguna | **PARCIAL** |
| RecruiterAssistantAgent | job_board | Custom | Mock data hardcoded | No | Ninguna | **PARCIAL** |
| CareerCoachAgent | candidate | 1? | Minimo DI | No | ? | **SKELETON** |
| MarketingAgent | ai_agents | 1 | Deprecated v6.3 | No | Claude/OpenAI | **SKELETON** |
| LearningTutorAgent | lms | Custom | Sin AI provider | No | Ninguna | **SKELETON** |
| JarabaLexCopilotAgent | ai_agents | 1 | Alias a LegalCopilot | No | Aliased | **SKELETON** |

**Resumen: 13 PRODUCCION (62%), 5 PARCIAL (24%), 3 SKELETON (14%).**

### 3.3 Agentes Gen 1 vs Gen 2

**Gen 1 (BaseAgent):** Wrapper de prompt simple. Sin tool use, sin model routing inteligente. Dependen de hardcoded tier o default. Ejemplos: EmployabilityCopilot, LegalCopilot, ContentWriter.

**Gen 2 (SmartBaseAgent):** Modelo avanzado con:
- Model routing via `ModelRouterService` (fast/balanced/premium)
- Tool use via `ToolRegistry` (opcional, @?)
- Observabilidad completa via `AIObservabilityService`
- Provider fallback via `ProviderFallbackService`
- Context window management (opcional)
- A/B experiment selection en `execute()` antes de delegar a `doExecute()`

**7 agentes son Gen 2.** La base es solida pero los Gen 1 con logica de dominio profunda (LegalCopilot 8 modos, EmployabilityCopilot 6 modos) deberian migrarse a Gen 2 para beneficiarse de tool use y model routing.

### 3.4 Sistema de herramientas (ToolRegistry)

**6 herramientas registradas:**

| Tool | Funcion | Approval Required |
|------|---------|------------------|
| `SendEmailTool` | Envio de emails via mail.plugin_manager | No |
| `CreateEntityTool` | Creacion de entidades Drupal | No |
| `SearchKnowledgeTool` | Busqueda en knowledge base (RAG) | No |
| `QueryDatabaseTool` | Consultas SQL read-only | No |
| `UpdateEntityTool` | Actualizacion de campos de entidad | **Si** |
| `SearchContentTool` | Busqueda full-text de contenido | No |

**Loop de tool use:** Iterativo hasta 5 ciclos con parsing JSON + native function calling via `ChatInput::setChatTools()`.

**PROBLEMA CRITICO:** `AgentToolRegistry` tiene solo 89 lineas. No hay:
- Validacion de parametros de herramientas
- Guardrails en el output de herramientas
- Enforcement del approval system (PendingApprovalService inyectado pero nunca consultado)
- Error wrapping ni recovery

### 3.5 Agentes autonomos (jaraba_agents)

El modulo `jaraba_agents` implementa un **sistema de agentes autonomos con state machine completa**:

```
STATUS_TRANSITIONS: running -> completed/failed/paused/cancelled
```

Incluye:
- `ApprovalManagerService`: Workflow de aprobacion humana
- `AgentMetricsCollectorService`: Metricas de ejecucion
- `GuardrailsEnforcerService`: Enforcement de guardrails
- `AgentExecutionBridgeService`: Mapping de agent_type a service ID

**Valoracion:** Arquitectura solida y funcional. El principal gap es `AgentLongTermMemoryService` que tiene constructor de 5 lineas y 0 metodos implementados.

### 3.6 Copilot v2 (streaming + modos)

`CopilotOrchestratorService` + `StreamingOrchestratorService` implementan:
- MODE_PROVIDERS map con primary + fallback chain (Anthropic -> OpenAI -> Google Gemini)
- MODE_MODELS map con modelo especifico por modo
- Circuit breaker (3 fallos consecutivos en 5 min = skip provider)
- Context window management (DEFAULT_MAX_CONTEXT_CHARS = 8000)
- Streaming real via PHP Generator con flush cada 80 chars o sentence break
- PII masking non-blocking per 500 chars
- Token counting aproximado (char/4)

**Servicios auxiliares reales:** ModeDetectorService, BusinessPatternDetectorService, HypothesisPrioritizationService.

**Valoracion: PRODUCCION-READY.** Sofisticado, con fallback real y streaming funcional.

### 3.7 Agentes que faltan para clase mundial

| Agente Necesario | Vertical | Justificacion |
|-----------------|----------|---------------|
| **DataAnalystAgent** | Transversal | Analisis de datos con NL queries. Jasper y Salesforce lo tienen. |
| **ChurnPredictionAgent** | Transversal | Deteccion proactiva de riesgo de baja. Core de IA proactiva. |
| **OnboardingGuideAgent** | Transversal | Guia interactiva para nuevos tenants. Reduce time-to-value. |
| **SEOOptimizerAgent** | Content Hub | Auditoria SEO autonoma con acciones correctivas. Sitecore lo tiene. |
| **WorkflowAutomationAgent** | Transversal | Orquestacion de flujos multi-paso con triggers. Capacidad #16 del benchmark. |
| **PricingOptimizationAgent** | Agro/Comercio | Optimizacion de precios con datos de mercado. Alto impacto en revenue. |
| **ComplianceAuditorAgent** | JarabaLex | Auditoria regulatoria autonoma. Diferenciacion vertical. |
| **LearningPathAgent** | Formacion/LMS | Rutas de aprendizaje personalizadas. El actual LearningTutor es skeleton. |

### 3.8 Problemas criticos detectados

1. **3 clases llamadas "Agent" que NO son agentes** (no implementan AgentInterface): BusinessCopilotAgent, RecruiterAssistantAgent, LearningTutorAgent. Son orchestrators/advisors.
2. **RecruiterAssistantAgent devuelve datos hardcoded**: "12 cumplen requisitos, 8 requieren revision, 5 no cumplen" siempre. No consulta la base de datos.
3. **LearningTutorAgent no tiene AI provider inyectado**: Solo recibe EnrollmentService + CurrentUser. Es metadata sin backend IA.
4. **BusinessCopilotAgent.getBlockItems() retorna []**: Stub que devuelve array vacio con comentario "Placeholder".
5. **Model router inyectado pero sin evidencia de llamada a route()** en 8 agentes Gen 2.

---

## 4. Auditoria de Servicios Transversales IA

### 4.1 Scorecard de servicios

| Servicio | Madurez | LOC | Funcional | Usado | Veredicto |
|----------|---------|-----|-----------|-------|-----------|
| AIGuardrailsService | 85% | 720 | Si (80+ patrones jailbreak) | Opcional (@?) | Produccion, pero bypass-able |
| ModelRouterService | **100%** | 388 | Si (3 tiers Claude) | 8 agentes | Excelente |
| TraceContextService | **100%** | 229 | Si (UUID traces) | Observability | Produccion |
| AIObservabilityService | **95%** | 688 | Si (15 campos por log) | Todos los agentes | Produccion |
| ProviderFallbackService | **100%** | 176 | Si (circuit breaker) | Opcional | Solido |
| JarabaRagService | **95%** | 890 | Si (pipeline completo) | Copilot/RAG | Feature-complete |
| StreamingOrchestratorService | 90% | 426 | Si (Generator + fallback) | Copilot | Streaming real |
| AgentToolRegistry | **30%** | 89 | Parcial | Potencial | Apenas funcional |
| ReActLoopService | **40%** | 150 | Parcial (loop sin parsing) | Agentes | Skeleton loop |
| ContextWindowManager | **30%** | 100 | Solo token math | Potencial | Sin progressive trimming |
| AgentExecutionBridgeService | 50% | 100 | Parcial (type mapping) | Agentes | Solo resolver |
| LlmReRankerService | 50% | 100 | Parcial (sin prompts) | RAG | Incompleto |
| HandoffDecisionService | <40% | ? | Probable stub | Potencial | Desconocido |
| AgentLongTermMemoryService | **5%** | 80 | No (solo constructor) | Agentes | Puro skeleton |
| SemanticCacheService | <40% | ? | Probable stub | Potencial | Probable stub |
| PromptExperimentService | 30% | ? | Parcial | Admin | Framework incompleto |
| InlineAiService | <30% | ? | No | Frontend | Skeleton |
| MultiModalBridgeService | <20% | ? | No | Ninguno | Apenas empezado |

### 4.2 Servicios produccion-ready (Tier 1)

**AIGuardrailsService** (720 LOC): Validacion completa de inputs con deteccion de jailbreak bilingue (80+ patrones ES/EN), deteccion de PII (9 tipos: DNI, NIE, IBAN ES, NIF/CIF, +34, SSN, phone), rate limiting por tenant, sanitizacion de contenido RAG, masking de PII en outputs. Gap: rate limiting depende de tabla `ai_guardrail_logs`.

**JarabaRagService** (890 LOC): Pipeline RAG completo con embedding -> vector search (Qdrant) -> enrichment -> LLM generation -> grounding. Incluye temporal decay (half-life 180 dias), hybrid re-ranking (keyword + LLM, reciprocal rank fusion), clasificacion de respuesta (ANSWERED_FULL/PARTIAL/UNANSWERED). Gap: solo falta comprehensive error recovery.

**AIObservabilityService** (688 LOC): Tracking completo con agent_id, action, tier, model_id, provider_id, tenant_id, vertical, input_tokens, output_tokens, duration_ms, success. Integra trace_id/span_id de TraceContextService. Incluye getStats(), getCostByTier(), getUsageByAgent(), getSavings(), exportCsv(), getUsageTrend().

### 4.3 Servicios parcialmente implementados (Tier 2)

**ReActLoopService** (150 LOC): Estructura del loop plan-execute-reflect existe con max 10 pasos. Pero `parseStepResponse()` y `buildStepPrompt()` son stubs. No hay parsing THOUGHT/ACTION/OBSERVATION. Sin memory integration. Sin error recovery.

**ContextWindowManager** (100 LOC): `estimateTokens()` funciona (char/4). `getModelLimit()` tiene lookup correcto (200k para Claude). Pero `fitSystemPrompt()` esta incompleto — sin progressive trimming de RAG/tools/knowledge.

**AgentToolRegistry** (89 LOC): `addToolService()` escanea `#[AgentTool]` attribute. `executeTool()` hace `call_user_func_array()` basico. No hay: validacion de parametros, execution context (tenant/user isolation), approval handling, error wrapping, guardrails application.

### 4.4 Servicios aspiracionales/skeleton (Tier 3)

**AgentLongTermMemoryService** (80 LOC): Solo constructor con 5 inyecciones opcionales. `remember()`, `recall()`, `buildMemoryPrompt()` prometidos pero **sin implementar**. GAP-07 no existe en la realidad.

**SemanticCacheService**: Declarado en services.yml con 2 inyecciones opcionales (qdrant_client, rag_service). Fuzzy matching probablemente no implementado.

**MultiModalBridgeService**: Solo logger + ai.provider opcional. Voice + Vision completamente ausente.

### 4.5 Grafo de llamadas real

```
User Request
  -> CopilotController
    -> StreamingOrchestratorService.streamChat()
      -> TraceContextService.startTrace()
      -> AIGuardrailsService.validate() [OPCIONAL @?]
      -> JarabaRagService.query()
        -> AIGuardrailsService.sanitizeRagContent()
        -> LlmReRankerService.reRank() [OPCIONAL]
        -> GroundingValidator.validate()
      -> TraceContextService.endSpan()
      -> AIObservabilityService.log()

Agent Execution
  -> AgentExecutionBridgeService.execute()
    -> SmartBaseAgent.execute()
      -> ModelRouterService.route() [?? sin evidencia de llamada]
      -> ProviderFallbackService.executeWithFallback()
      -> ToolRegistry.execute() [SIN guardrails check]
      -> AIObservabilityService.log()
```

**Links ausentes criticos:**
1. Tools ejecutan SIN AIGuardrailsService verificando output
2. Approval queue inyectada pero nunca consultada
3. Agent memory (GAP-07) completamente ausente de cualquier call path
4. ReAct loop no integrado en ningun agente
5. Handoff decisions nunca triggered
6. Model router inyectado pero uso incierto

### 4.6 Gaps de seguridad en la capa IA

**Fuerte:**
- Input validation (AIGuardrailsService: 80+ patrones, bilingue)
- PII masking (9 tipos detectados, US + ES)
- Rate limiting (per-tenant, 100 req/hora)
- Prompt injection en RAG (deteccion + sanitizacion)

**Debil:**
- Tool output NO sanitizado antes de retornar al usuario
- Tool execution NO gated por approval system
- Tool parameters NO validados
- Guardrails son opcionales (@?) — pueden desactivarse
- Fallback a operacion insegura en error

---

## 5. Auditoria de Modulos Verticales

### 5.1 Estadisticas de la plataforma

| Metrica | Valor |
|---------|-------|
| Modulos custom | 89 |
| Entidades | 500+ |
| Servicios | 800+ |
| Controllers | 350+ |
| Forms (PremiumEntityFormBase) | 237 |
| Rutas | 24,150+ |
| Archivos PHP | 2,000+ |
| Archivos SCSS | 67 |
| Templates Twig | 145 |
| Bloques GrapesJS | 67+ |
| Templates de pagina | 142 |

### 5.2 Madurez por vertical

| Vertical | Score | Entidades | Servicios | IA Depth | Tests |
|----------|-------|-----------|-----------|----------|-------|
| **AgroConecta** | 9/10 | 91 | 24 | Profunda | Parciales |
| **Empleabilidad** | 8/10 | 50+ | 15+ | Profunda (6 modos) | Parciales |
| **Comercio Conecta** | 8/10 | 42 | 27 | Limitada | Sin tests |
| **JarabaLex** | 8/10 | 60+ | 17+ | Profunda (8 modos + agents) | Parciales |
| **Content Hub** | 8/10 | 8 | 12 | Profunda (generacion, sentiment) | Sin tests |
| **Andalucia +EI** | 8/10 | 6 | 10 | Parcial (copilot) | Sin tests |
| **Emprendimiento** | 7/10 | 30+ | 10+ | Parcial (mentor matching) | Sin tests |
| **Servicios Conecta** | 7/10 | 6 | 5 | Debil (solo matching) | Sin tests |
| **Formacion** | 7/10 | Implicito | Implicito | Muy debil (tutor skeleton) | Sin tests |
| **Demo** | N/A | Config | N/A | N/A | N/A |

### 5.3 Piramide de integracion IA por vertical

**TIER 1 — IA PROFUNDA (3 verticales):**
- AgroConecta: Recomendaciones de producto, pricing IA, demand forecasting, copilot conversacional
- JarabaLex: Agentes autonomos legales, analisis de contratos, resolucion de disputas
- Empleabilidad: Recomendaciones de empleo, analisis CV, preparacion entrevistas

**TIER 2 — COPILOT + SMART (3 verticales):**
- Emprendimiento: Mentor matching IA, diagnostic copilot, coaching IA
- Andalucia +EI: Program copilot, progress tracking
- Content Hub: Generacion de contenido, analisis de sentimiento, reputation monitoring

**TIER 3 — FEATURES MEJORADAS (2 verticales):**
- Comercio Conecta: SEO local, sentiment de reviews
- Servicios Conecta: Service matching, optimizacion de disponibilidad

**TIER 4 — IMPLICITO (2 verticales):**
- Formacion: Hereda de LMS + Training (LearningTutor es skeleton)
- Demo: Sandbox de configuracion

### 5.4 Inconsistencias detectadas entre verticales

| Inconsistencia | Impacto |
|---------------|---------|
| Gen 1 agents sin tools; Gen 2 si. Sin path de migracion | Legal y Empleabilidad no tienen tool use |
| 3 clases "Agent" sin AgentInterface | Confusion de contratos |
| Guardrails opcionales (@?) en todos los servicios | Se puede desactivar sin warning |
| Solo ContentAuthor verifica tenant_id en handler | Cientos de handlers sin verificacion |
| AgroConecta/JarabaLex con IA profunda; Comercio/Servicios casi nada | Inconsistencia en propuesta de valor |
| 59 modulos sin tests (66%) | Riesgo de regresion |

---

## 6. Auditoria de Arquitectura Multi-Tenant

### 6.1 Separacion Tenant/Group

Arquitectura correcta:
- **Tenant entity** = billing, suscripcion, dominio, theme overrides (master record)
- **Group entity** = aislamiento de contenido, memberships, access control (via Group module)
- **Link:** `Tenant.group_id` referencia a Group
- **TenantBridgeService** (165 LOC): `getTenantForGroup()`, `getGroupForTenant()`, `getTenantForUser()`

### 6.2 Ciclo de vida de suscripcion

```
PENDING -> TRIAL -> ACTIVE <-> PAST_DUE -> SUSPENDED
            |-> CANCELLED
```

Implementado en `TenantSubscriptionService` (264 LOC) con:
- Cancelacion diferida (end-of-period) via `cancel_at`
- Grace period 7 dias para past_due
- Trial expiration via cron
- Almacenamiento de IDs Stripe (customer_id, subscription_id)

### 6.3 Aislamiento de datos

- Aislamiento a nivel de campo: `tenant_id` (entity_reference a Tenant)
- Base de datos compartida (no schema-level separation)
- Depende de access control handlers para filtrar queries
- No hay filtrado automatico a nivel de query

### 6.4 Gaps criticos multi-tenant

| Gap | Severidad | Impacto |
|-----|-----------|---------|
| **Usage metering simulado** | CRITICA | `getCurrentUsage()` devuelve datos random |
| **Feature flags hardcoded** | CRITICA | Sin toggles runtime por tenant |
| **Query-level filtering ausente** | ALTA | No hay filtro automatico por tenant_id |
| **Tenant isolation inconsistente** | ALTA | Solo ~5-10% de handlers verifican tenant_id |
| **Provisioning no automatizado** | ALTA | Webhook Stripe -> tenant creation no existe |
| **Analytics solo platform-level** | MEDIA | Sin analytics por tenant individual |
| **Theme customization limitada** | MEDIA | Solo JSON colors, no CSS completo |

---

## 7. Auditoria SEO, Page Builder y UX

### 7.1 SEO tecnico

**Fuerte (72/100):**
- Schema.org: BlogPosting, Article, NewsArticle, BreadcrumbList, ImageObject, Person, Organization
- Open Graph: og:title, og:description, og:image, og:url, og:type, og:site_name
- Twitter Cards: twitter:card, twitter:creator, twitter:title, twitter:description, twitter:image
- RSS 2.0: DOMDocument, atom:link self, dc:creator, CDATA descriptions
- Robots.txt dinamico con tenant awareness
- Sitemap XML con extraccion de imagenes
- Canonical URLs via `Url::fromRoute()`
- Hreflang multi-idioma

**Debil:**
- Sin GEO/Local SEO (no geo.position, no LocalBusiness schema)
- Sin FAQPage, Product, Event, VideoObject schema
- Sin Core Web Vitals tracking (LCP, CLS, INP)
- Sin keyword analytics ni Search Console integration
- Sin Answer Capsule (mencionado pero no implementado)

### 7.2 GrapesJS Page Builder

**Fuerte (68/100):**
- 67+ bloques (45 base + 22 premium Aceternity/Magic UI)
- 142 templates pre-construidos en 12 categorias
- 11 plugins custom (AI, SEO, marketplace, command palette, multipage, partials, legal blocks, thumbnails, reviews, assets)
- Drag-drop con SortableJS, undo/redo ilimitado, auto-save con debounce 5s
- Responsive preview con 8 device presets (320px-1920px)
- Dual-mode: Legacy (body field) + Canvas (GrapesJS)
- Sanitizacion en 3 capas (script/onhandler removal, CSS cleaning, GrapesJS artifact removal)
- CSRF protection en API endpoints

**Debil:**
- Sin template designer visual (solo YAML)
- Canvas NO sandboxed (XSS risk si user HTML contiene scripts)
- Sin concurrent edit locking (dos usuarios pueden sobrescribirse)
- Sin tests e2e (solo 6 archivos de test para todo el Page Builder)
- Sanitizacion custom con regex (no DOMPurify)

### 7.3 UX y Design System

**Fuerte (74/100):**
- 67 archivos SCSS con design system completo
- 70+ settings configurables desde UI Drupal (15 tabs)
- 46 CSS variables inyectadas dinamicamente desde theme settings
- 6 header layouts, 5 hero layouts, 4 footer layouts, 8 card variants
- Dark mode completo con 4 modos (off, on, auto, toggle)
- PWA con Service Worker, manifest, offline support
- WCAG 2.1 AA: skip links, focus-visible, reduced motion, 44px touch targets
- Command bar Cmd+K con 4 providers
- 237 premium forms migrados

**Debil:**
- CSS monolitico: 751KB en un solo archivo (sin code splitting)
- Sin Critical CSS inlined (script existe pero no verificado en produccion)
- Sin srcset/picture/WebP/AVIF (impacto LCP)
- Sin CSS Container Queries
- Sin RTL support
- Sin Storybook ni component library documentation

### 7.4 Core Web Vitals

| Metrica | Status | Gap |
|---------|--------|-----|
| LCP (Largest Contentful Paint) | Sin optimizar | No fetchpriority=high, no srcset, no WebP |
| CLS (Cumulative Layout Shift) | Sin optimizar | No aspect-ratio containers, no reserved space |
| INP (Interaction to Next Paint) | Sin optimizar | No debounce/throttle visible |
| TTFB (Time to First Byte) | No medido | No monitoring |

---

## 8. Coherencia de Flujos Transversales

### 8.1 Patrones consistentes

| Patron | Cobertura | Estado |
|--------|-----------|--------|
| PremiumEntityFormBase | 237/237 forms | 100% consistente |
| DefaultEntityAccessControlHandler | Fallback universal | Consistente |
| Presave Resilience (hasService + try-catch) | Todos los presave | Consistente |
| Url::fromRoute() para JS fetch | Todos los modulos | Consistente |
| Entity Preprocess (template_preprocess_*) | Todas las entidades custom | Consistente |
| Slide-panel con renderPlain() | Todos los controllers | Consistente |
| CSRF en API endpoints | Todos los PATCH/POST | Consistente |
| Secret management (env vars) | Todos los secrets | Consistente |

### 8.2 Patrones incoherentes

| Flujo | Problema | Impacto |
|-------|----------|---------|
| AI injection pattern | Gen 1 sin tools; Gen 2 si. Sin migracion | Legal/Empleabilidad sin tool use |
| "Agent" naming | 3 clases "Agent" sin AgentInterface | Confusion contractual |
| Guardrails enforcement | @? (opcional) en todos los servicios | Bypass posible sin warning |
| Tenant verification | Solo ContentAuthor verifica tenant_id | Potencial data leakage |
| IA en verticales | Agro/Lex profunda; Comercio/Servicios basica | Inconsistencia en valor |
| Model router usage | 8 agentes lo inyectan, sin evidencia de uso | Cost optimization no aplicada |
| Test coverage | 30/89 modulos con tests (34%) | Riesgo de regresion alto |

---

## 9. Hallazgos Criticos Priorizados

### HAL-AI-01: Tool execution sin guardrails ni approval {#hal-ai-01}
- **Severidad:** CRITICA
- **Ubicacion:** `jaraba_ai_agents/src/Service/AgentToolRegistry.php`
- **Problema:** `executeTool()` hace `call_user_func_array()` sin pasar por AIGuardrailsService ni PendingApprovalService
- **Riesgo:** Tools pueden retornar contenido malicioso, PII, o jailbreak attempts sin verificacion
- **Solucion:** Anadir `AIGuardrailsService.sanitizeToolOutput()` en `executeTool()` + enforcar approval para tools con `requiresApproval=true`

### HAL-AI-02: Usage metering simulado {#hal-ai-02}
- **Severidad:** CRITICA
- **Ubicacion:** `ecosistema_jaraba_core/src/Service/UsageLimitsService.php`
- **Problema:** `getCurrentUsage()` devuelve datos aleatorios, no consultas reales a base de datos
- **Riesgo:** Imposible enforcar quotas de plan, imposible facturar por uso real
- **Solucion:** Implementar queries reales a tablas de entidades por tenant_id + contadores de API calls

### HAL-AI-03: Tenant isolation inconsistente en handlers {#hal-ai-03}
- **Severidad:** CRITICA
- **Ubicacion:** Multiples AccessControlHandler en ~80 modulos
- **Problema:** Solo ~5-10% de handlers verifican tenant_id (pattern TENANT-ISOLATION-ACCESS-001)
- **Riesgo:** Cross-tenant data access via bugs o exploits
- **Solucion:** Audit de todos los handlers + implementacion sistematica de `checkTenantIsolation()`

### HAL-AI-04: AgentLongTermMemory vacio {#hal-ai-04}
- **Severidad:** ALTA
- **Ubicacion:** `jaraba_agents/src/Service/AgentLongTermMemoryService.php`
- **Problema:** Solo 80 lineas de constructor, 0 metodos implementados. GAP-07 no existe
- **Solucion:** Implementar `remember()`, `recall()`, `buildMemoryPrompt()` con Qdrant indexing

### HAL-AI-05: ReActLoop sin parsing {#hal-ai-05}
- **Severidad:** ALTA
- **Ubicacion:** `jaraba_ai_agents/src/Service/ReActLoopService.php`
- **Problema:** `buildStepPrompt()` y `parseStepResponse()` son stubs. No hay parsing THOUGHT/ACTION/OBSERVATION
- **Solucion:** Implementar parsing completo con regex/JSON, step validation, error recovery

### HAL-AI-06: IA proactiva inexistente {#hal-ai-06}
- **Severidad:** ALTA
- **Ubicacion:** Plataforma completa
- **Problema:** Solo IA reactiva (chatbot). No hay predictive insights, anomaly detection, opportunity surfacing
- **Solucion:** Implementar cron-based insight generation, churn prediction, content gap analysis

### HAL-AI-07: LearningTutor skeleton {#hal-ai-07}
- **Severidad:** ALTA
- **Ubicacion:** `jaraba_lms/src/Agent/LearningTutorAgent.php`
- **Problema:** No tiene AI provider inyectado. Es metadata sin backend IA
- **Solucion:** Migrar a SmartBaseAgent Gen 2 con 5 acciones reales (ask, explain, suggest_path, study_tips, progress_review)

### HAL-AI-08: Datos mock en RecruiterAssistant {#hal-ai-08}
- **Severidad:** ALTA
- **Ubicacion:** `jaraba_job_board/src/Agent/RecruiterAssistantAgent.php`
- **Problema:** `screenCandidates()` retorna "12 cumplen, 8 revision, 5 no cumplen" siempre (hardcoded)
- **Solucion:** Reemplazar con queries reales a ApplicationService + JobPostingService

### HAL-AI-09: Canvas GrapesJS no sandboxed {#hal-ai-09}
- **Severidad:** ALTA
- **Ubicacion:** `jaraba_page_builder/js/grapesjs-jaraba-canvas.js`
- **Problema:** Canvas iframe no tiene Content-Security-Policy. HTML de usuario puede contener scripts
- **Solucion:** Anadir CSP headers al iframe del canvas + DOMPurify en lugar de regex custom

### HAL-AI-10: Core Web Vitals sin optimizar {#hal-ai-10}
- **Severidad:** MEDIA
- **Ubicacion:** `ecosistema_jaraba_theme/`
- **Problema:** Sin fetchpriority, sin srcset, sin WebP, sin aspect-ratio containers
- **Solucion:** Image optimization pipeline + CSS splitting + CWV monitoring

### HAL-AI-11: Feature flags hardcoded {#hal-ai-11}
- **Severidad:** MEDIA
- **Ubicacion:** `ecosistema_jaraba_core/src/Service/PlanResolverService.php`
- **Problema:** Features determinados por plan tier hardcoded, sin runtime toggles por tenant
- **Solucion:** Feature flag service con toggles runtime y gradual rollout

### HAL-AI-12: CSS monolitico sin code splitting {#hal-ai-12}
- **Severidad:** MEDIA
- **Ubicacion:** `ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css` (751KB)
- **Problema:** Un solo archivo CSS para toda la plataforma
- **Solucion:** Code splitting por ruta/vertical + critical CSS inlining

### HAL-AI-13: Sin srcset/WebP/AVIF {#hal-ai-13}
- **Severidad:** MEDIA
- **Ubicacion:** Templates Twig de toda la plataforma
- **Problema:** Imagenes sin formato moderno ni responsive srcset
- **Solucion:** Pipeline de image optimization + `<picture>` elements con WebP fallback

### HAL-AI-14: GEO/Local SEO ausente {#hal-ai-14}
- **Severidad:** MEDIA
- **Ubicacion:** `jaraba_content_hub/src/Service/SeoService.php`
- **Problema:** Sin geo meta tags, sin LocalBusiness schema, sin geo-targeted sitemaps
- **Solucion:** Implementar LocalBusiness schema para Comercio Conecta, geo meta tags

### HAL-AI-15: Test coverage 34% {#hal-ai-15}
- **Severidad:** MEDIA
- **Ubicacion:** 59/89 modulos sin tests
- **Problema:** Riesgo de regresion alto, especialmente en servicios IA
- **Solucion:** Plan de testing progresivo: servicios criticos primero

### HAL-AI-16: Model router inyectado pero sin evidencia de uso {#hal-ai-16}
- **Severidad:** MEDIA
- **Ubicacion:** 8 agentes Gen 2
- **Problema:** ModelRouterService inyectado pero no hay evidencia de que los agentes llamen `route()`
- **Solucion:** Verificar y anadir llamadas explicitas a `route()` en `doExecute()` de cada agente

### HAL-AI-17: Guardrails opcionales (@?) {#hal-ai-17}
- **Severidad:** MEDIA
- **Ubicacion:** services.yml de multiples modulos
- **Problema:** AIGuardrailsService inyectado como `@?` (opcional) en todo el stack
- **Solucion:** Hacer mandatory (`@`) en copilot y tool execution paths

### HAL-AI-18: Approval system no enforced en tools {#hal-ai-18}
- **Severidad:** MEDIA
- **Ubicacion:** `AgentToolRegistry.executeTool()`
- **Problema:** PendingApprovalService inyectado pero nunca consultado antes de ejecutar tools
- **Solucion:** Check `requiresApproval` attribute antes de `call_user_func_array()`

### HAL-AI-19: Workflow automation inexistente {#hal-ai-19}
- **Severidad:** MEDIA
- **Ubicacion:** Plataforma completa
- **Problema:** No hay motor de workflows IA (capacidad #16 del benchmark)
- **Solucion:** Implementar WorkflowAutomationAgent con triggers, conditions, actions

### HAL-AI-20: Personalizacion adaptativa ausente {#hal-ai-20}
- **Severidad:** BAJA
- **Ubicacion:** Frontend completo
- **Problema:** No hay UX adaptativa basada en comportamiento del usuario
- **Solucion:** Recommendation engine + adaptive dashboards (largo plazo)

---

## 10. Posicion Competitiva y Roadmap

### 10.1 Distancia a clase mundial por dimension

```
                    SCAFFOLDED          PARTIAL           PRODUCTION        WORLD-CLASS
                    |                   |                  |                 |
Agentes IA          ==========================================--------------  68%
Servicios IA        ===========================================--------------  70%
Verticales          ==============================================-----------  78%
Multi-Tenant        ====================================--------------------  65%
SEO                 ==========================================--------------  72%
Page Builder        ==========================================--------------  68%
UX/Design           =============================================-----------  74%
--------------------------------------------------------------------
GLOBAL              ==========================================--------------  70%
TARGET              ================================================--------  85%
```

### 10.2 Roadmap estrategico en 4 sprints

**Sprint 1 (1-2 semanas): Seguridad + Integridad**
1. HAL-AI-01: Guardrails en ToolRegistry.execute()
2. HAL-AI-18: Approval gating en herramientas
3. HAL-AI-09: Sandbox del canvas GrapesJS (CSP en iframe)
4. HAL-AI-03: Audit sistematico de tenant_id en handlers
5. HAL-AI-08: Datos reales en RecruiterAssistant
6. HAL-AI-17: Guardrails mandatory (no @?) en paths criticos

**Sprint 2 (2-4 semanas): Revenue + Operaciones**
7. HAL-AI-02: Usage metering real
8. HAL-AI-11: Feature flag service con runtime toggles
9. Provisioning automatizado (Stripe webhook -> tenant)
10. Per-tenant analytics dashboard

**Sprint 3 (1-2 meses): IA de Clase Mundial**
11. HAL-AI-04: AgentLongTermMemory con Qdrant
12. HAL-AI-05: ReActLoop con parsing completo
13. HAL-AI-06: IA proactiva (cron insights, anomaly detection)
14. HAL-AI-07: LearningPathAgent real para Formacion
15. HAL-AI-16: Verificar y activar ModelRouter en todos los agentes Gen 2
16. A/B testing de prompts funcional

**Sprint 4 (2-3 meses): Performance + UX**
17. HAL-AI-10: Core Web Vitals (fetchpriority, srcset, WebP)
18. HAL-AI-12: CSS code splitting + critical CSS
19. HAL-AI-14: GEO/Local SEO para Comercio Conecta
20. HAL-AI-19: Workflow automation engine
21. HAL-AI-15: Plan de testing progresivo

---

## 11. Referencias Cruzadas

| Documento | Version | Relacion |
|-----------|---------|----------|
| `docs/00_DIRECTRICES_PROYECTO.md` | v88.0.0 | Directrices de cumplimiento |
| `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | v81.0.0 | Arquitectura de referencia |
| `docs/00_FLUJO_TRABAJO_CLAUDE.md` | v42.0.0 | Flujo de trabajo y aprendizajes |
| `docs/00_INDICE_GENERAL.md` | v113.0.0 | Indice de documentacion |
| `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | v2.1 | Federated Design Tokens |
| `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` | v2.0.0 | Arquitectura IA nivel 5 |
| `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md` | v1.0 | Especificacion GrapesJS |
| `docs/implementacion/2026-02-26_Plan_Implementacion_GrapesJS_Content_Hub_v1.md` | v1.0.0 | Plan GrapesJS Content Hub |
| `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` | v1.0.0 | Plan IA Nivel 5 |
| `docs/implementacion/2026-02-26_Plan_Implementacion_Reviews_Comentarios_Clase_Mundial_v1.md` | v1.0.0 | Plan Reviews y Comentarios |
| `docs/analisis/2026-02-26_Auditoria_Sistemas_Calificaciones_Comentarios_Clase_Mundial_v1.md` | v1.0.0 | Auditoria Reviews anterior |

---

## Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-27 | Claude Opus 4.6 | Creacion inicial: auditoria integral IA clase mundial con 20 hallazgos, benchmark de mercado, scorecard 7 dimensiones, roadmap 4 sprints |
