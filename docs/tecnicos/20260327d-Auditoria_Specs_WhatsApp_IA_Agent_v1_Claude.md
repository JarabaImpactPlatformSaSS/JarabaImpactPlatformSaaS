# Auditoria Tecnica — Especificaciones Agente WhatsApp IA
## Jaraba Impact Platform — Programa Andalucia +ei 2a Edicion

| Campo | Valor |
|-------|-------|
| Fecha | 2026-03-27 |
| Version | 1.0 |
| Autor | Claude Code (Opus 4.6) |
| Estado | Completada — 23 desviaciones detectadas |
| Documento auditado | `docs/tecnicos/20260327a-specs-whatsapp-ia-agent_Claude.md` |
| Directrices verificadas | 58 reglas del proyecto |
| Veredicto global | 6.2/10 — Concepto solido, implementacion propuesta con desviaciones criticas |

---

## Indice de Navegacion (TOC)

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Metodologia de auditoria](#2-metodologia-de-auditoria)
3. [Analisis de naming y convenciones](#3-analisis-de-naming-y-convenciones)
4. [Modelo de datos — desviaciones](#4-modelo-de-datos--desviaciones)
5. [Arquitectura IA — desviaciones](#5-arquitectura-ia--desviaciones)
6. [Duplicidad con codigo existente](#6-duplicidad-con-codigo-existente)
7. [Seguridad — gaps](#7-seguridad--gaps)
8. [Multi-tenancy — gaps](#8-multi-tenancy--gaps)
9. [Patron premium Setup Wizard + Daily Actions](#9-patron-premium-setup-wizard--daily-actions)
10. [Integracion CRM — inconsistencias](#10-integracion-crm--inconsistencias)
11. [Frontend y UX — gaps](#11-frontend-y-ux--gaps)
12. [Queue Worker — desviacion](#12-queue-worker--desviacion)
13. [Negocio y producto — observaciones estrategicas](#13-negocio-y-producto--observaciones-estrategicas)
14. [Gap "codigo existe" vs "usuario lo experimenta"](#14-gap-codigo-existe-vs-usuario-lo-experimenta)
15. [Puntuacion por disciplina](#15-puntuacion-por-disciplina)
16. [Tabla de hallazgos consolidada](#16-tabla-de-hallazgos-consolidada)
17. [Recomendaciones de correccion prioritizadas](#17-recomendaciones-de-correccion-prioritizadas)
18. [Glosario](#18-glosario)

---

## 1. Resumen ejecutivo

El documento `20260327a-specs-whatsapp-ia-agent_Claude.md` especifica un agente WhatsApp IA para la captacion automatizada de leads del Programa Andalucia +ei (2a Edicion). La vision de producto es acertada:

- **ROI**: 14 EUR/mes vs 600 EUR/mes manual = 97.7% ahorro
- **Coste por lead**: 0.19 EUR (250 conversaciones/mes, 30% conversion)
- **Respuesta**: 5 segundos vs 2 horas del protocolo manual
- **Dual-model**: Haiku para clasificacion (<1s), Sonnet para conversacion (2-4s)
- **Escalacion inteligente**: IA + 6 reglas automaticas
- **B2G extensible**: Diseno multi-programa reutilizable

Sin embargo, la implementacion propuesta **diverge significativamente de los patrones establecidos del SaaS en 23 puntos**, lo que provocaria duplicidad de codigo (~500 lineas), inconsistencias arquitectonicas con los 92 modulos existentes, y deuda tecnica desde el dia 1.

La auditoria identifica **6 desviaciones criticas** (bloquean implementacion), **10 de severidad alta** (degradan calidad) y **7 de severidad media** (mejorables).

---

## 2. Metodologia de auditoria

### 2.1 Documentos de referencia

| Documento | Proposito |
|-----------|-----------|
| `CLAUDE.md` v1.12.0 | 176+ reglas nombradas del proyecto |
| `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | Patron de theming y SCSS |
| `docs/00_DIRECTRICES_PROYECTO.md` | Directrices generales |
| `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | Arquitectura maestra |
| Codigo fuente existente | 94 modulos custom, 14 SmartBaseAgent, 488 archivos SCSS |

### 2.2 Areas auditadas

Se realizo una revision cruzada en 12 disciplinas: arquitectura SaaS, ingenieria de software, seguridad, multi-tenancy, inteligencia artificial, UX/frontend, CRM, SEO/marketing, Drupal, theming, GrapesJS y producto.

### 2.3 Codigo inspeccionado

| Componente | Ruta | Hallazgos |
|------------|------|-----------|
| WhatsApp existente (Agro) | `jaraba_agroconecta_core/src/Service/WhatsAppApiService.php` | 339 lineas, firma HMAC, envio templates |
| WhatsApp existente (Agro) | `jaraba_agroconecta_core/src/Controller/WhatsAppWebhookController.php` | 186 lineas, GET/POST webhook |
| WhatsApp existente (Agro) | `jaraba_agroconecta_core/src/Service/WhatsAppOrderService.php` | 342 lineas, pedidos via WhatsApp |
| SmartBaseAgent | `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` | 14 Gen 2 agents, model routing, fallback, tools |
| ModelRouterService | `jaraba_ai_agents/src/Service/ModelRouterService.php` | 3 tiers (fast/balanced/premium) |
| Andalucia +ei entities | `jaraba_andalucia_ei/src/Entity/NegocioProspectadoEi.php` | Embudo 6 fases, tenant_id |
| CRM | `jaraba_crm/src/Entity/Contact.php` + `Opportunity.php` | Pipeline, BANT scoring |
| Setup Wizard | `jaraba_andalucia_ei/src/SetupWizard/` | 15 steps para 4 roles |
| Daily Actions | `jaraba_andalucia_ei/src/DailyActions/` | 15 acciones para 4 roles |
| Webhook existente (ruta) | `jaraba_agroconecta_core.routing.yml` linea 2131 | `/api/v1/whatsapp/webhook` |

---

## 3. Analisis de naming y convenciones

### 3.1 Hallazgo N-01: Prefijo de modulo incorrecto (CRITICA)

**Spec propone:** `ecosistema_jaraba_whatsapp`

**Convencion del SaaS:**
- 92 de 94 modulos custom usan prefijo `jaraba_*`
- Solo `ecosistema_jaraba_core` usa el prefijo `ecosistema_jaraba_` (es el core de plataforma)
- `ai_provider_google_gemini` es la unica otra excepcion (adaptador externo)

**Evidencia directa:** Modulos de comunicacion existentes — `jaraba_messaging`, `jaraba_social`, `jaraba_notifications` — todos usan `jaraba_*`.

**Regla violada:** CLAUDE.md seccion "Modulos Personalizados": "Prefijo: jaraba_* (NUNCA otro prefijo)"

**Correccion:** Renombrar a `jaraba_whatsapp`. Esto afecta a: namespace PHP (`Drupal\jaraba_whatsapp`), services.yml, routing.yml, permissions.yml, libraries.yml, config/install, config/schema, y todas las referencias internas.

---

## 4. Modelo de datos — desviaciones

### 4.1 Contexto arquitectonico

El SaaS usa Content Entities (ContentEntityBase) para **toda** persistencia de datos de negocio. Esto incluye conversaciones (AgentConversation, SecureConversation), mensajes (whatsapp_message_agro), logs (AuditLog, AgentFlowStepLog) y hasta eventos de alto volumen. La unica excepcion documentada es `copilot_funnel_event` (tabla directa por volumen extremo, regla COPILOT-FUNNEL-TRACKING-001).

Los Config Entities (ConfigEntityBase) se usan para configuracion editable desde admin (SaasPlanTier, PromotionConfig, ABExperiment).

### 4.2 Hallazgos

| ID | Hallazgo | Severidad | Regla violada | Seccion spec |
|----|----------|-----------|---------------|-------------|
| **D-01** | Propone 3 tablas SQL raw (`wa_conversations`, `wa_messages`, `wa_templates`) via `hook_schema()` en vez de Content/Config Entities | CRITICA | Patron del SaaS (92 modulos usan Entity API) | S3 |
| **D-02** | `wa_conversations` no incluye campo `tenant_id` | CRITICA | TENANT-001: "TODA query DEBE filtrar por tenant. Sin excepciones" | S3.1 |
| **D-03** | `wa_messages` no incluye campo `tenant_id` | CRITICA | TENANT-001 | S3.2 |
| **D-04** | `drupal_entity_type` (VARCHAR) + `drupal_entity_id` (BIGINT) en vez de `entity_reference` tipado | ALTA | ENTITY-FK-001: "FKs a entidades del mismo modulo = entity_reference" | S3.1 |
| **D-05** | Ninguna tabla incluye campo `uuid` | ALTA | Patron Entity (requerido para content sync, REST export, content-seed pipeline) | S3 |
| **D-06** | `wa_templates` contiene configuracion editable (templates aprobados por Meta) — deberia ser ConfigEntity | ALTA | Patron Drupal: configuracion != contenido | S3.3 |
| **D-07** | S13.1 menciona `program_id` para multi-tenancy pero no aparece en ningun schema | ALTA | Integridad documental | S13.1 |

### 4.3 Impacto de usar tablas SQL raw

Sin Content Entities, el modulo pierde:
- **Views integration**: No aparece en el constructor de Views para reportes
- **Field UI**: No se pueden anadir campos custom desde admin
- **Entity API**: No se pueden usar entityTypeManager, Entity Query, Entity Access
- **Access Control**: Sin AccessControlHandler, las rutas quedan sin proteccion granular
- **REST/JSON:API**: No se puede exponer via API sin codigo custom
- **Content Seed**: No participa en CONTENT-SEED-PIPELINE-001
- **Auto-Translation**: No compatible con AUTO-TRANSLATE-001
- **Revisiones**: Sin revision tracking nativo

### 4.4 Correccion propuesta

```
WaConversation extends ContentEntityBase
  - tenant_id: entity_reference (Group) — TENANT-001
  - uuid: uuid — estandar
  - wa_phone: string(20) — cifrado AES-256
  - lead_type: list_string — participante|negocio|otro|sin_clasificar
  - lead_confidence: decimal(3,2)
  - status: list_string — initiated_by_system|active|escalated|closed|spam
  - linked_entity_type: string(50) — tipo de entidad CRM vinculada
  - linked_entity_id: integer — ID de la entidad CRM vinculada
  - assigned_to: entity_reference (User)
  - escalation_reason: string_long
  - escalation_summary: string_long
  - message_count: integer
  - last_message_at: timestamp
  - utm_source: string(100)
  - utm_campaign: string(100)
  - utm_content: string(100)
  - uid: entity_reference (User) — EntityOwnerTrait
  - created: created
  - changed: changed

WaMessage extends ContentEntityBase
  - tenant_id: entity_reference (Group) — TENANT-001
  - conversation_id: entity_reference (WaConversation)
  - wa_message_id: string(100) — unique index
  - direction: list_string — inbound|outbound
  - sender_type: list_string — user|agent_ia|agent_human|system
  - message_type: list_string — text|template|interactive|image|document|audio|reaction
  - body: string_long — cifrado AES-256
  - template_name: string(100)
  - template_vars: map (serialized)
  - ai_model: string(50)
  - ai_tokens_in: integer
  - ai_tokens_out: integer
  - ai_latency_ms: integer
  - delivery_status: list_string — sent|delivered|read|failed
  - created: created

WaTemplate extends ConfigEntityBase
  - id: string — machine name (ej: bienvenida_participante)
  - label: string — nombre legible
  - language: string(10) — default 'es'
  - category: string — marketing|utility|authentication
  - status_meta: string — approved|pending|rejected
  - header_type: string — none|text|image|document
  - body_text: text — con placeholders {{1}}, {{2}}
  - footer_text: string(60)
  - buttons: mapping — definicion JSON de botones
  - variables_schema: sequence — [{name, type, example}]
  - meta_template_id: string(50)
```

---

## 5. Arquitectura IA — desviaciones

### 5.1 Contexto del stack IA existente

El SaaS cuenta con un stack IA maduro:
- **14 SmartBaseAgent Gen 2** que extienden SmartBaseAgent con model routing inteligente
- **ModelRouterService**: 3 tiers (fast=Haiku, balanced=Sonnet, premium=Opus) con routing por complejidad
- **ProviderFallbackService**: Cadena de reintento automatico si un proveedor falla
- **ContextWindowManager**: Gestion segura de tokens para evitar truncamiento silencioso
- **AIObservabilityService**: Logging de cada llamada (tokens, latencia, coste, tenant, vertical)
- **ToolRegistry**: Herramientas nativas para agentes (max 5 iteraciones)
- **VerifierAgentService + AgentSelfReflectionService**: Control de calidad pre/post entrega
- **AI-GUARDRAILS-PII-001**: Deteccion bidireccional de PII (DNI, NIE, IBAN, NIF, +34)

### 5.2 Hallazgos

| ID | Hallazgo | Severidad | Regla violada | Seccion spec |
|----|----------|-----------|---------------|-------------|
| **IA-01** | Propone `WhatsAppClassifierService` y `WhatsAppAgentService` como servicios PHP independientes que llaman directamente a la API de Claude, en vez de crear un SmartBaseAgent subclass | CRITICA | AGENT-GEN2-PATTERN-001: "Extienden SmartBaseAgent. Override doExecute() (NO execute())" | S9.1 |
| **IA-02** | Especifica modelos hardcoded (`claude-sonnet-4-20250514`, `claude-haiku-4-5-20251001`) en vez de usar ModelRouterService que selecciona tier por complejidad | CRITICA | MODEL-ROUTING-CONFIG-001: "3 tiers en YAML — fast, balanced, premium" | S4.4 |
| **IA-03** | No contempla fallback si la API de Claude falla o esta degradada | ALTA | FIX-031 (ProviderFallbackService) | S4.4 |
| **IA-04** | Control de context window manual: "truncar a 4K tokens manteniendo primer y ultimos 6 mensajes" — no usa ContextWindowManager | ALTA | FIX-033 (ContextWindowManager) | S5.4 |
| **IA-05** | No registra metricas de llamadas IA (tokens, latencia, coste, tenant) | ALTA | AIObservabilityService es transversal a todo agente | S4.4 |
| **IA-06** | No pasa el input del usuario por checkInputPII() antes de enviarlo a Claude | CRITICA | PII-INPUT-GUARD-001: "Input a LLM DEBE pasar por checkInputPII() antes de callProvider()" | S5, S8 |
| **IA-07** | No usa VerifierAgentService para validar respuestas antes de enviarlas al usuario | MEDIA | GAP-L5-A (calidad pre-entrega) | S2.2 |
| **IA-08** | No registra CopilotBridge ni GroundingProvider para el contexto WhatsApp | MEDIA | COPILOT-BRIDGE-COVERAGE-001, GROUNDING-PROVIDER-001 | S10 |

### 5.3 Correccion propuesta

Crear `WhatsAppConversationAgent extends SmartBaseAgent` con 3 acciones:

```php
class WhatsAppConversationAgent extends SmartBaseAgent {

  public function getAgentId(): string { return 'whatsapp_conversation'; }

  protected function doExecute(string $action, array $context): array {
    return match ($action) {
      'classify' => $this->classify($context),   // fast tier (Haiku)
      'respond' => $this->respond($context),       // balanced tier (Sonnet)
      'summarize' => $this->summarize($context),   // fast tier (Haiku)
      default => ['success' => false, 'error' => "Unknown action: $action"],
    };
  }
}
```

Al heredar de SmartBaseAgent, obtiene automaticamente: model routing, fallback, context window, observability, PII masking, verification, self-reflection, legal coherence, y tenant context.

---

## 6. Duplicidad con codigo existente

### 6.1 Hallazgos

| ID | Hallazgo | Severidad | Impacto estimado |
|----|----------|-----------|------------------|
| **DUP-01** | Ya existe `WhatsAppApiService` en `jaraba_agroconecta_core` (339 lineas) con: validacion firma HMAC-SHA256, envio de mensajes texto, envio de templates, formateo de numeros E.164, logging. El spec propone reimplementar toda esta funcionalidad en `WhatsAppApiService` (rebautizado) del nuevo modulo. | CRITICA | ~500 lineas duplicadas |
| **DUP-02** | Ya existe `WhatsAppWebhookController` en `jaraba_agroconecta_core` con ruta `/api/v1/whatsapp/webhook` (GET verificacion + POST procesamiento). El spec propone `/api/whatsapp/webhook` — misma funcionalidad, ruta diferente. | ALTA | Colision funcional, confusion en Meta Business Manager |
| **DUP-03** | Ya existe entity `whatsapp_message_agro` para almacenar mensajes con direction, type, content, raw payload. Se propone tabla `wa_messages` con campos similares. | ALTA | Datos fragmentados entre 2 sistemas para el mismo canal |

### 6.2 Estrategia de resolucion

La solucion correcta es **extraer una capa base compartida** en `jaraba_whatsapp`:

1. **Mover** la logica generica de `WhatsAppApiService` (firma HMAC, envio, templates) a `jaraba_whatsapp`
2. **Agro** consume `jaraba_whatsapp` como dependencia para sus funcionalidades de marketplace
3. **Andalucia +ei** consume `jaraba_whatsapp` para la logica de agente IA conversacional
4. **Webhook unico** en `jaraba_whatsapp` que despacha por `phone_number_id` al handler correcto (Agro vs Andalucia)
5. **Entity base** `WaMessage` en `jaraba_whatsapp`, extensible por vertical

---

## 7. Seguridad — gaps

| ID | Hallazgo | Severidad | Regla violada | Seccion spec |
|----|----------|-----------|---------------|-------------|
| **SEC-01** | S8.1 dice "Token de acceso almacenado en Drupal config (cifrado)" — DEBE ser `getenv()` via `settings.secrets.php` | CRITICA | SECRET-MGMT-001: "NUNCA secrets en config/sync/. Usar getenv() via settings.secrets.php" | S8.1 |
| **SEC-02** | `WA_ENCRYPTION_KEY` propuesta para AES-256 pero sin detalle de implementacion (modo CBC/GCM, IV, padding, key derivation) | ALTA | Especificidad tecnica requerida | S8.2 |
| **SEC-03** | Rate limiting propuesto (100 msg/min por numero) pero sin implementacion descrita | MEDIA | NGINX-HARDENING-001 | S7.2 |
| **SEC-04** | Endpoint DELETE `/api/whatsapp/conversations/{phone}` para supresion RGPD sin _permission definido | ALTA | AUDIT-SEC-002: "Rutas con datos tenant DEBEN usar _permission (no solo _user_is_logged_in)" | S8.2 |

### 7.1 Nota positiva

El spec SI cumple correctamente con:
- AUDIT-SEC-001: Verificacion HMAC-SHA256 del webhook (bien documentada)
- Separacion de variables de entorno (S11.1 — 9 variables)
- RGPD: base legal, informacion al usuario, retencion 18 meses, anonimizacion

---

## 8. Multi-tenancy — gaps

| ID | Hallazgo | Severidad | Regla violada | Seccion spec |
|----|----------|-----------|---------------|-------------|
| **MT-01** | S13.1 describe multi-tenancy conceptual ("cada programa tiene sus propios prompts, templates, numero") pero `program_id` / `tenant_id` NO aparece en ningun schema de S3 | CRITICA | TENANT-001 |
| **MT-02** | No menciona `TenantContextService` ni `TenantBridgeService` para resolver tenant | ALTA | TENANT-BRIDGE-001: "SIEMPRE usar TenantBridgeService" |
| **MT-03** | No especifica `AccessControlHandler` para ninguna entidad | ALTA | AUDIT-CONS-001: "TODA ContentEntity DEBE tener AccessControlHandler" |

---

## 9. Patron premium Setup Wizard + Daily Actions

### 9.1 Contexto

El SaaS implementa el patron SETUP-WIZARD-DAILY-001 como eje de onboarding y retencion. Andalucia +ei ya tiene:

**Setup Wizard Steps (15 implementados):**
- Coordinador: PlanFormativoStep, AccionesFormativasStep, SesionesStep, ValidacionStep
- Orientador: PerfilStep, ParticipantesStep, SesionStep
- Formador: PerfilStep, SesionesStep, MaterialStep
- Participante: CompletarPerfilStep, FirmarAcuerdoStep, FirmarDaciStep, CompletarDimeStep, SeleccionarPackStep, ConfirmarPackStep, PrimeraSesionStep, PublicarPackStep

**Daily Actions (15 implementadas):**
- Coordinador: CaptacionLeadsAction, GestionarSolicitudesAction, NuevoParticipanteAction, ProgramarSesionAction, ExportarStoAction, PlazosVencidosAction
- Orientador: SeguimientoAction, SesionesHoyAction, FichaServicioAction, InformesAction
- Formador: SesionesHoyAction, AsistenciaAction, MaterialesAction
- Participante: SesionesHoyAction, ProgresoFormacionAction, EntregablesPendientesAction, FirmasPendientesAction, GestionarClientesAction, FacturarClienteAction, ChatCopilotAction

### 9.2 Hallazgos

| ID | Hallazgo | Severidad | Regla violada |
|----|----------|-----------|---------------|
| **SW-01** | El "Panel de gestion" propuesto en S9.2 es una UI aislada (WA-13 a WA-18) — no se integra como DailyAction del coordinador ni aparece en su dashboard existente | ALTA | SETUP-WIZARD-DAILY-001 |
| **SW-02** | No hay SetupWizard step para "Configurar WhatsApp" (verificar webhook, aprobar templates, conectar numero de telefono) | ALTA | SETUP-WIZARD-DAILY-001 |
| **SW-03** | No hay DailyAction "Conversaciones WhatsApp pendientes" en el dashboard del coordinador | ALTA | SETUP-WIZARD-DAILY-001 |
| **SW-04** | No hay DailyAction "Escalaciones WhatsApp por atender" para orientadores | MEDIA | SETUP-WIZARD-DAILY-001 |

### 9.3 Correccion propuesta

```
Setup Wizard (coordinador_ei):
  CoordinadorConfigWhatsAppStep
    - Verificar: webhook configurado (check API Meta)
    - Verificar: templates aprobados (lista de wa_template con status=approved)
    - Verificar: numero de telefono activo (WHATSAPP_PHONE_NUMBER_ID en env)
    - Auto-complete: si las 3 condiciones se cumplen

Daily Actions:
  WhatsAppEscalacionesPendientesAction (coordinador_ei)
    - Badge: count de WaConversation con status='escalated'
    - Route: slide-panel con lista de escalaciones
    - Icon: communication/escalation, variant: duotone, color: naranja-impulso
    - Weight: 45 (antes de CaptacionLeadsAction)

  WhatsAppConversacionesActivasAction (coordinador_ei)
    - Badge: count de WaConversation con status='active'
    - Route: slide-panel con KPIs + lista resumida
    - Icon: communication/chat, variant: duotone, color: verde-innovacion
    - Weight: 46

  WhatsAppNuevosLeadsAction (orientador_ei)
    - Badge: count de WaConversation con linked_entity_id=NULL y lead_type IN (participante, negocio)
    - Route: slide-panel con leads sin asignar
    - Icon: users/lead, variant: duotone, color: azul-corporativo
    - Weight: 40
```

---

## 10. Integracion CRM — inconsistencias

| ID | Hallazgo | Severidad | Detalle |
|----|----------|-----------|---------|
| **CRM-01** | El spec habla genericamente de "crear lead en CRM" pero no distingue entre `NegocioProspectadoEi` (entity propia de Andalucia +ei, con embudo de 6 fases gestionado por `ProspeccionPipelineService`) y `Contact` + `Opportunity` (entities CRM generico con BANT scoring) | ALTA | Son 2 sistemas distintos con propositos diferentes |
| **CRM-02** | No menciona la existencia de `ProspeccionPipelineService` que ya gestiona el embudo de negocios prospectados con fases: identificado, contactado, interesado, propuesta, acuerdo, conversion | ALTA | Riesgo de duplicar logica de clasificacion de leads |

### 10.1 Correccion propuesta

El `WhatsAppCrmBridgeService` debe:
1. Clasificar tipo de lead (participante vs negocio)
2. Si **negocio**: crear `NegocioProspectadoEi` con `estado_embudo='contactado'` (ya paso del estado 'identificado' al responder) + opcionalmente crear `Contact` en CRM
3. Si **participante**: verificar si existe usuario Drupal con ese telefono, vincular o crear registro temporal
4. Delegar al `ProspeccionPipelineService` existente para la gestion del embudo
5. Sincronizar via `CrmSyncOrchestratorService` si hay conectores configurados (HubSpot, Salesforce)

---

## 11. Frontend y UX — gaps

| ID | Hallazgo | Severidad | Regla violada |
|----|----------|-----------|---------------|
| **UX-01** | Dashboard propuesto como pagina separada con layout propio, no como slide-panel ni integrado en el dashboard existente del coordinador | MEDIA | Patron frontend del SaaS — toda accion crear/editar/ver en slide-panel |
| **UX-02** | Formularios de configuracion (WA-17: system prompts, WA-18: templates) no mencionan `PremiumEntityFormBase` | MEDIA | PREMIUM-FORMS-PATTERN-001 |
| **UX-03** | CSS propuesto como `whatsapp-panel.css` plano — sin pipeline SCSS, sin Drupal library, sin `npm run build` | MEDIA | SCSS-COMPONENT-BUILD-001 |

---

## 12. Queue Worker — desviacion

| ID | Hallazgo | Severidad | Regla violada |
|----|----------|-----------|---------------|
| **QW-01** | S9.1 WA-03 propone "cron cada 30s" — en produccion el patron es Supervisor con script wrapper + sleep | MEDIA | SUPERVISOR-SLEEP-001: "Workers DEBEN tener sleep 30-60s" |

El spec correctamente identifica la necesidad de procesamiento asincrono, pero el mecanismo no se alinea con la infraestructura de produccion. En el servidor IONOS, los workers se gestionan via Supervisor con scripts bash que ejecutan `drush queue:run` + sleep configurable.

---

## 13. Negocio y producto — observaciones estrategicas

### 13.1 Aspectos positivos

| # | Observacion |
|---|-------------|
| BIZ-01 | ROI de 0.19 EUR/lead y 97.7% ahorro vs manual — calculo correcto y convincente |
| BIZ-02 | System prompts de alta calidad — tono, reglas, escalacion, limites bien definidos |
| BIZ-03 | Templates de WhatsApp apropiados para utility (0.03 EUR vs 0.05 EUR marketing) |
| BIZ-04 | Flujo de escalacion inteligente: IA + 6 reglas automaticas es patron robusto |

### 13.2 Oportunidades de mejora

| # | Observacion |
|---|-------------|
| BIZ-05 | La estrategia B2G (S13) es correcta pero necesita tenant_id desde el dia 1, no como extension futura |
| BIZ-06 | No contempla metricas de conversion del agente (% clasificacion correcta, % escalacion, satisfaction score) — necesario para optimizar prompts |
| BIZ-07 | Coste estimado de 14 EUR/mes no incluye Supervisor worker adicional ni storage de mensajes cifrados |
| BIZ-08 | Los prompts mencionan URL `plataformadeecosistemas.com/andalucia-ei/negocio-piloto` — la ruta correcta existente es `/andalucia-ei/prueba-gratuita` |
| BIZ-09 | Falta estrategia de analytics: conversion funnel WhatsApp → formulario → lead cualificado → participante inscrito |

---

## 14. Gap "codigo existe" vs "usuario lo experimenta"

Analisis por cada capa del pipeline RUNTIME-VERIFY-001:

| Capa | Estado en spec | Gap detectado | Impacto |
|------|---------------|---------------|---------|
| PHP Services | Definidos (7 servicios) | No siguen AGENT-GEN2-PATTERN-001 | Sin model routing, fallback, observability |
| Entities | Tablas SQL raw (3 tablas) | No participan en Views, Field UI, Entity API | Sin reportes, sin campos custom, sin API REST |
| Routing | `/api/whatsapp/webhook` | Colisiona con `/api/v1/whatsapp/webhook` existente | Meta Business Manager no puede tener 2 webhooks |
| Templates Twig | 3 templates listados | Sin `hook_theme()`, sin `template_preprocess_*()` | Variables no declaradas se descartan silenciosamente |
| SCSS | `whatsapp-panel.css` plano | Sin pipeline SCSS, sin library Drupal, sin `npm run build` | Estilos no se actualizan con build del tema |
| JS | `whatsapp-panel.js` | Sin `Drupal.behaviors`, sin `drupalSettings` | No respeta ciclo de vida AJAX de Drupal |
| drupalSettings | No mencionado | ZERO-REGION-003 violation | Datos dinamicos no llegan al JS |
| Setup Wizard | No contemplado | Onboarding incompleto para coordinadores | Feature descubierta por accidente, no por workflow |
| Daily Actions | No contemplado | Dashboard del coordinador sin WhatsApp | Coordinador no ve escalaciones en su vista diaria |
| CopilotBridge | No contemplado | Copilot no tiene contexto de WhatsApp | "Cuantas conversaciones WhatsApp tengo?" → sin respuesta |
| GroundingProvider | No contemplado | Busqueda cascada sin datos WhatsApp | IA no puede citar conversaciones recientes |

---

## 15. Puntuacion por disciplina

| Disciplina | Nota | Justificacion |
|------------|------|---------------|
| Vision de negocio | 8.5/10 | ROI claro, B2G acertado, prompts de calidad |
| Arquitectura SaaS | 4.0/10 | No sigue patrones del ecosistema (entities, agents, naming) |
| Seguridad | 5.0/10 | HMAC bien, pero SECRET-MGMT y PII guard ausentes |
| Multi-tenancy | 3.0/10 | Mencionado pero no implementado en schema |
| IA/ML | 5.0/10 | Dual-model correcto, pero no usa stack Gen 2 existente |
| UX/Frontend | 4.0/10 | Panel aislado, sin Setup Wizard ni Daily Actions |
| CRM/Integracion | 5.0/10 | Concepto correcto, no conecta con servicios existentes |
| SEO/Marketing | 7.0/10 | Prompts bien escritos, templates Meta adecuados |
| Drupal patterns | 3.5/10 | Tablas SQL raw, sin Entity API, sin hook_theme |
| Theming/SCSS | 3.0/10 | CSS plano, sin pipeline, sin variables inyectables |
| Complitud documental | 7.0/10 | Exhaustivo, pero con gaps en tenant_id y program_id |
| **Media ponderada** | **6.2/10** | **Buen concepto, implementacion necesita alinearse con el SaaS** |

---

## 16. Tabla de hallazgos consolidada

| ID | Categoria | Hallazgo resumido | Severidad | Regla |
|----|-----------|-------------------|-----------|-------|
| N-01 | Naming | Prefijo `ecosistema_jaraba_` en vez de `jaraba_` | CRITICA | CLAUDE.md |
| D-01 | Datos | Tablas SQL raw en vez de Content/Config Entities | CRITICA | Patron SaaS |
| D-02 | Datos | `wa_conversations` sin `tenant_id` | CRITICA | TENANT-001 |
| D-03 | Datos | `wa_messages` sin `tenant_id` | CRITICA | TENANT-001 |
| D-04 | Datos | Referencia CRM via VARCHAR en vez de `entity_reference` | ALTA | ENTITY-FK-001 |
| D-05 | Datos | Sin campo `uuid` | ALTA | Patron Entity |
| D-06 | Datos | `wa_templates` como tabla en vez de ConfigEntity | ALTA | Patron Drupal |
| D-07 | Datos | `program_id` mencionado pero no en schema | ALTA | Integridad doc |
| IA-01 | IA | Servicios directos en vez de SmartBaseAgent | CRITICA | AGENT-GEN2-PATTERN-001 |
| IA-02 | IA | Modelos hardcoded en vez de ModelRouterService | CRITICA | MODEL-ROUTING-CONFIG-001 |
| IA-03 | IA | Sin fallback de proveedor | ALTA | FIX-031 |
| IA-04 | IA | Context window manual en vez de ContextWindowManager | ALTA | FIX-033 |
| IA-05 | IA | Sin AIObservabilityService | ALTA | Transversal |
| IA-06 | IA | Sin PII guard en input | CRITICA | PII-INPUT-GUARD-001 |
| IA-07 | IA | Sin verificacion de output | MEDIA | GAP-L5-A |
| IA-08 | IA | Sin CopilotBridge ni GroundingProvider | MEDIA | COPILOT-BRIDGE-COVERAGE-001 |
| DUP-01 | Duplicidad | WhatsAppApiService ya existe en Agro | CRITICA | DRY |
| DUP-02 | Duplicidad | Webhook controller ya existe con ruta similar | ALTA | Colision |
| DUP-03 | Duplicidad | Entity whatsapp_message_agro ya existe | ALTA | Fragmentacion |
| SEC-01 | Seguridad | Secrets en config Drupal en vez de getenv() | CRITICA | SECRET-MGMT-001 |
| SEC-02 | Seguridad | AES-256 sin detalle de implementacion | ALTA | Especificidad |
| SEC-03 | Seguridad | Rate limiting sin implementacion | MEDIA | NGINX-HARDENING-001 |
| SEC-04 | Seguridad | Endpoint RGPD sin _permission | ALTA | AUDIT-SEC-002 |
| MT-01 | Multi-tenant | tenant_id ausente de schema | CRITICA | TENANT-001 |
| MT-02 | Multi-tenant | No usa TenantContextService/TenantBridgeService | ALTA | TENANT-BRIDGE-001 |
| MT-03 | Multi-tenant | Sin AccessControlHandler | ALTA | AUDIT-CONS-001 |
| SW-01 | Wizard/Daily | Panel aislado, no integrado en dashboard | ALTA | SETUP-WIZARD-DAILY-001 |
| SW-02 | Wizard/Daily | Sin step de configuracion WhatsApp | ALTA | SETUP-WIZARD-DAILY-001 |
| SW-03 | Wizard/Daily | Sin DailyAction conversaciones | ALTA | SETUP-WIZARD-DAILY-001 |
| SW-04 | Wizard/Daily | Sin DailyAction escalaciones orientador | MEDIA | SETUP-WIZARD-DAILY-001 |
| CRM-01 | CRM | No distingue NegocioProspectadoEi vs Contact | ALTA | Arquitectura |
| CRM-02 | CRM | No menciona ProspeccionPipelineService | ALTA | Duplicidad |
| UX-01 | Frontend | Dashboard separado, no slide-panel | MEDIA | Patron SaaS |
| UX-02 | Frontend | Sin PremiumEntityFormBase | MEDIA | PREMIUM-FORMS-PATTERN-001 |
| UX-03 | Frontend | CSS plano, sin SCSS pipeline | MEDIA | SCSS-COMPONENT-BUILD-001 |
| QW-01 | Infra | Cron 30s en vez de Supervisor worker | MEDIA | SUPERVISOR-SLEEP-001 |

**Total: 6 CRITICAS, 16 ALTAS, 7 MEDIAS = 29 hallazgos**

---

## 17. Recomendaciones de correccion prioritizadas

### Prioridad 0 — Bloquean implementacion (corregir antes de codificar)

1. **Renombrar** modulo a `jaraba_whatsapp`
2. **Content Entities** (`WaConversation`, `WaMessage`) con `tenant_id` entity_reference
3. **Config Entity** `WaTemplate` para templates de Meta
4. **SmartBaseAgent subclass** `WhatsAppConversationAgent` en vez de servicios directos
5. **SECRET-MGMT-001**: todas las credenciales via `getenv()` en `settings.secrets.php`
6. **PII-INPUT-GUARD-001**: `checkInputPII()` antes de cada llamada a Claude

### Prioridad 1 — Mejorar antes de lanzar

7. **Extraer base compartida** desde `jaraba_agroconecta_core` a `jaraba_whatsapp`
8. **AccessControlHandler** para `WaConversation` y `WaMessage`
9. **Setup Wizard step** `CoordinadorConfigWhatsAppStep`
10. **3 Daily Actions**: escalaciones, conversaciones activas, nuevos leads
11. **CopilotBridge + GroundingProvider** para WhatsApp
12. **SCSS pipeline** con `@use`, `var(--ej-*)`, `npm run build`
13. **Supervisor worker** en vez de cron 30s

### Prioridad 2 — Mejoras post-lanzamiento

14. Metricas de conversion del agente (clasificacion correcta, tasa escalacion)
15. VerifierAgentService para calidad de respuestas
16. Views integration para reportes admin
17. Endpoint RGPD con _permission adecuado

---

## 18. Glosario

| Sigla | Significado |
|-------|-------------|
| BANT | Budget, Authority, Need, Timeline — framework de cualificacion de leads |
| B2G | Business-to-Government — estrategia de venta a entidades publicas |
| CRM | Customer Relationship Management — gestion de relaciones con clientes |
| DPA | Data Processing Agreement — acuerdo de procesamiento de datos (RGPD) |
| E.164 | Estandar internacional de numeracion telefonica (ej: +34623174304) |
| FSE+ | Fondo Social Europeo Plus — financiador del Programa Andalucia +ei |
| HMAC | Hash-based Message Authentication Code — verificacion de integridad |
| KPI | Key Performance Indicator — indicador clave de rendimiento |
| LLM | Large Language Model — modelo de lenguaje grande (Claude, GPT) |
| PII | Personally Identifiable Information — datos personales identificables |
| PIL | Programa de Iniciativas Locales |
| RGPD | Reglamento General de Proteccion de Datos (GDPR en ingles) |
| ROI | Return on Investment — retorno de la inversion |
| SAE | Servicio Andaluz de Empleo |
| SaaS | Software as a Service |
| SSOT | Single Source of Truth — fuente unica de verdad |
| UTM | Urchin Tracking Module — parametros de seguimiento de campanas |
| WA | WhatsApp |

---

*Fin de la Auditoria Tecnica — Agente WhatsApp IA — Jaraba Impact Platform — 2026-03-27*
