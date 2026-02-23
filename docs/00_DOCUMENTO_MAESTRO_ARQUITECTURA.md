# 🏗️ DOCUMENTO MAESTRO DE ARQUITECTURA
## Jaraba Impact Platform SaaS v63.0

**Fecha:** 2026-02-23
**Versión:** 63.0.0 (AI Identity Enforcement + Competitor Isolation)
**Estado:** Produccion (AI Identity Hardened + Precios Configurables v2.1 + Security Hardened + Secure Messaging)
**Nivel de Madurez:** 5.0 / 5.0 (Resiliencia & Cumplimiento Certificado)

---

## 3. Arquitectura de Alto Nivel

### 3.6 Stack de Cumplimiento Fiscal N1 ⭐
Integración unificada de soberanía legal y resiliencia técnica:
- **Soberanía de Datos (jaraba_privacy)**: Gestión automatizada de DPA y ARCO-POL SLA.
- **Transparencia Contractual (jaraba_legal)**: ToS Lifecycle y monitorización de SLA real.
- **Resiliencia & Recuperación (jaraba_dr)**: Verificación de backups SHA-256 y orquestación de DR Tests.

---

## 7. Módulos del Sistema

### 7.1 Módulos Core & Inteligencia

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      MÓDULOS DE INTELIGENCIA                             │
├─────────────────────────────────────────────────────────────────────────┤
...
│   📦 jaraba_ai_agents (v2.0) ⭐                                         │
│   ├── BaseAgent: Clase abstracta con DI flexible (Mock-ready)           │
│   │   └── buildSystemPrompt(): Inyecta regla identidad (parte #0)      │
│   ├── AgentOrchestrator: Enrutamiento dinámico de intenciones           │
│   └── JarabaLexCopilot: Asistente jurídico especializado                │
│                                                                         │
│   🛡️ AI IDENTITY ENFORCEMENT (AI-IDENTITY-001 + AI-COMPETITOR-001)     │
│   ├── BaseAgent.buildSystemPrompt(): Regla identidad como parte #0     │
│   │   (heredada por 14+ agentes: Emprendimiento, Empleabilidad,        │
│   │   JarabaLex, Legal, Sales, Merchant, Producer, Marketing, etc.)    │
│   ├── CopilotOrchestratorService.buildSystemPrompt(): $identityRule    │
│   │   antepuesto a los 8 modos (coach→landing_copilot)                 │
│   ├── PublicCopilotController: IDENTIDAD INQUEBRANTABLE en prompt      │
│   ├── FaqBotService: Regla en ambos prompts (KB + plataforma)          │
│   ├── ServiciosConectaCopilotAgent: Antepuesto a getSystemPromptFor()  │
│   ├── CoachIaService: Antepuesto a generateCoachingPrompt()            │
│   └── AiContentController: Identidad "copywriter de Jaraba"           │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      STACK CUMPLIMIENTO FISCAL                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Compliance)                                │
│   ├── ComplianceAggregator: Consolidación de 9 KPIs críticos             │
│   └── FiscalComplianceService: Score 0-100 unificado                    │
│                                                                         │
│   📦 jaraba_billing (Delegation)                                        │
│   └── FiscalInvoiceDelegation: Enrutamiento VeriFactu / Facturae / B2B  │
│                                                                         │
│   📦 jaraba_verifactu (SIF)                                             │
│   ├── HashChainService: Integridad irrefutable SHA-256                  │
│   └── EventLogService: Auditoría append-only RD 1007/2023               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      CUSTOMER SUCCESS & RETENCIÓN                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_customer_success (v2.0) ⭐                                  │
│   ├── Entidades (7): CustomerHealth, ChurnPrediction, CsPlaybook,      │
│   │   PlaybookExecution, ExpansionSignal, VerticalRetentionProfile,     │
│   │   SeasonalChurnPrediction (append-only)                             │
│   ├── Servicios (8): HealthScoreCalculator, ChurnPrediction,           │
│   │   PlaybookExecutor, EngagementScoring, NpsSurvey, LifecycleStage,  │
│   │   VerticalRetentionService, SeasonalChurnService                    │
│   ├── 5 Perfiles verticales: AgroConecta (cosecha), ComercioConecta    │
│   │   (rebajas), ServiciosConecta (ROI), Empleabilidad (exito),        │
│   │   Emprendimiento (fase)                                             │
│   ├── Dashboard FOC: /customer-success/retention (heatmap estacional)  │
│   ├── 13 Endpoints API REST (6 genericos + 7 verticalizados)           │
│   └── QueueWorker: VerticalRetentionCronWorker (cron diario 03:00)     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      VERTICAL: SERVICIOSCONECTA                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_servicios_conecta (v2.0 — Booking Engine) ⭐               │
│   ├── Entidades (5): ProviderProfile, ServiceOffering, Booking,        │
│   │   AvailabilitySlot, ServicePackage                                  │
│   ├── Servicios (4): ProviderService, ServiceOfferingService,          │
│   │   AvailabilityService (isSlotAvailable, hasCollision,              │
│   │   markSlotBooked, releaseSlot), ReviewService                       │
│   ├── API REST: ServiceApiController (6 endpoints)                     │
│   │   ├── GET  /providers (marketplace listing)                        │
│   │   ├── GET  /providers/{id} (detail + offerings)                    │
│   │   ├── GET  /offerings (listing)                                    │
│   │   ├── GET  /providers/{id}/availability (slots)                    │
│   │   ├── POST /bookings (create with validation)                      │
│   │   └── PATCH /bookings/{id} (state machine transitions)            │
│   ├── State Machine: pending_confirmation → confirmed →                │
│   │   completed / cancelled_client / cancelled_provider / no_show      │
│   ├── Cron: auto-cancel stale, reminders (24h/1h flags),              │
│   │   no-show detection, expired slot cleanup                          │
│   ├── Notifications: hook_mail (5 templates), hook_entity_update       │
│   └── Marketplace: Twig templates, zero-region preprocess              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      COMUNICACION: MENSAJERIA SEGURA (IMPLEMENTED)     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_messaging (v1.0 — Implemented) 🔒                         │
│   ├── Entidades (4): SecureConversation (ContentEntity),               │
│   │   ConversationParticipant (ContentEntity),                         │
│   │   + SecureMessage (custom table), MessageAuditLog (custom table)   │
│   ├── Modelos (3): SecureMessageDTO (readonly), EncryptedPayload      │
│   │   (Value Object), IntegrityReport (Value Object)                   │
│   ├── Servicios (18): MessagingService, ConversationService,           │
│   │   MessageService, MessageEncryptionService, TenantKeyService,      │
│   │   MessageAuditService, NotificationBridgeService,                  │
│   │   AttachmentBridgeService, PresenceService, SearchService,         │
│   │   RetentionService, + 7 Access Checks                             │
│   ├── Controladores (7): Conversation, Message, Presence, Search,     │
│   │   Audit, Export (RGPD Art.20), MessagingPage (frontend)            │
│   ├── Cifrado: AES-256-GCM + Argon2id KDF + per-tenant keys          │
│   ├── Audit: SHA-256 hash chain (append-only, inmutable)              │
│   ├── API REST: 20+ endpoints + cursor-based pagination               │
│   ├── WebSocket: Ratchet (dev) / Swoole (prod) + Redis pub/sub       │
│   ├── ECA Plugins (8): 3 eventos, 3 condiciones, 2 acciones          │
│   ├── Frontend: 9 templates Twig (zero-region), 11 SCSS, 4 JS        │
│   ├── Permisos (13): 8 roles (cliente → super_admin)                  │
│   └── Total: 104 archivos, 6 sprints completados                      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      PRECIOS CONFIGURABLES v2.1 ⭐                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Plan Config) ⭐                           │
│   ├── ConfigEntities (2):                                              │
│   │   ├── SaasPlanTier: tier_key, aliases, Stripe Price IDs, weight   │
│   │   └── SaasPlanFeatures: vertical+tier, features[], limits{}       │
│   ├── PlanResolverService (broker central):                            │
│   │   ├── normalize(): Alias → tier key canonico                      │
│   │   ├── getFeatures(): Cascade especifico → default → NULL          │
│   │   ├── checkLimit() / hasFeature(): Consultas atomicas             │
│   │   ├── resolveFromStripePriceId(): Resolucion inversa Stripe       │
│   │   └── getPlanCapabilities(): Array plano para QuotaManager        │
│   ├── Seed Data: 21 YAMLs (3 tiers + 3 defaults + 15 verticales)    │
│   ├── Admin UI: /admin/config/jaraba/plan-tiers + plan-features      │
│   ├── Drush: jaraba:validate-plans (completitud de configs)           │
│   ├── Update Hook: 9019 (FileStorage + CONFIG-SEED-001)              │
│   └── SCSS: _plan-admin.scss (body class page-plan-admin)            │
│                                                                         │
│   Integraciones cross-module (inyeccion @? opcional):                  │
│   ├── QuotaManagerService (jaraba_page_builder): PlanResolver first   │
│   │   con fallback a array hardcoded para backwards-compat            │
│   ├── PlanValidator (jaraba_billing): 3-source cascade                │
│   │   FVL → PlanFeatures → SaasPlan fallback                         │
│   └── BillingWebhookController: Stripe Price ID → tier resolution    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-23 | **63.0.0** | **AI Identity Enforcement + Competitor Isolation:** Blindaje de identidad IA implementado en toda la plataforma. `BaseAgent.buildSystemPrompt()` inyecta regla de identidad como parte #0 (heredada por 14+ agentes). `CopilotOrchestratorService` antepone `$identityRule` a los 8 modos. `PublicCopilotController` incluye bloque IDENTIDAD INQUEBRANTABLE. Servicios standalone (FaqBotService, ServiciosConectaCopilotAgent, CoachIaService) con regla manual. Eliminadas 5 menciones de competidores en prompts de IA. 12 archivos modificados. Reglas AI-IDENTITY-001, AI-COMPETITOR-001. |
| 2026-02-23 | **62.2.0** | **Sticky Header Global:** `.landing-header` migrado de `position: fixed` a `position: sticky` por defecto. Solo `body.landing-page`/`body.page-front` mantienen `fixed`. Eliminados padding-top compensatorios fragiles de `.main-content`, `.user-main`, `.error-page`. Toolbar admin ajustado globalmente (`top: 39px/79px`). 4 archivos SCSS modificados. Regla CSS-STICKY-001. |
| 2026-02-23 | **62.0.0** | **Precios Configurables v2.1:** 2 ConfigEntities (`SaasPlanTier` + `SaasPlanFeatures`) como fuente de verdad para tiers, features y limites. `PlanResolverService` broker central con cascade especifico→default→NULL. Integracion en QuotaManagerService, PlanValidator y BillingWebhookController. 21 seed YAMLs + update hook 9019. Admin UI en `/admin/config/jaraba/plan-tiers` y `plan-features`. Drush command `jaraba:validate-plans`. 14 archivos nuevos + 11 editados. |
| 2026-02-20 | **61.0.0** | **Secure Messaging Implementado (Doc 178):** Modulo `jaraba_messaging` implementado al completo con 104 archivos. 4 entidades PHP (SecureConversation + ConversationParticipant ContentEntities, SecureMessage + MessageAuditLog custom tables), 3 modelos (SecureMessageDTO readonly, EncryptedPayload, IntegrityReport), 18 servicios + 7 access checks, 7 controladores REST, 4 WebSocket (Ratchet server + ConnectionManager + MessageHandler + AuthMiddleware), 8 ECA plugins (3 eventos + 3 condiciones + 2 acciones), 9 Twig templates (zero-region), 11 SCSS + 4 JS. Cifrado AES-256-GCM server-side + Argon2id KDF. SHA-256 hash chain audit. RGPD Art.20 export. Cursor-based pagination. |
| 2026-02-20 | 60.0.0 | **Secure Messaging Plan (Doc 178):** Plan de implementacion para `jaraba_messaging`. 64+ archivos planificados en 6 sprints. |
| 2026-02-20 | 59.0.0 | **ServiciosConecta Sprint S3 — Booking Engine Operativo:** Fix critico de `createBooking()` API (field mapping booking_date/offering_id/uid, validaciones provider activo+aprobado, offering ownership, advance_booking_min, client data, price, meeting_url Jitsi). Implementacion de `isSlotAvailable()`, `markSlotBooked()` y `hasCollision()` (refactored) en AvailabilityService. Fix `updateBooking()` state machine (cancelled_client/cancelled_provider, role enforcement provider-only para confirm/complete/no_show). Fix cron reminder duplicates (flags reminder_24h_sent/reminder_1h_sent). Fix hook_entity_update (booking_date, getOwnerId, cancelled_ prefix). 3 archivos modificados, 0 nuevos. |
| 2026-02-20 | 58.0.0 | **Vertical Retention Playbooks (Doc 179):** Implementacion completa del motor de retencion verticalizado en `jaraba_customer_success`. 2 entidades nuevas (VerticalRetentionProfile con 16 campos JSON, SeasonalChurnPrediction append-only), 2 servicios (VerticalRetentionService con evaluacion estacional, SeasonalChurnService con predicciones ajustadas), 7 endpoints API REST, dashboard FOC con heatmap, 5 perfiles verticales con calendarios de 12 meses, QueueWorker cron. 25 archivos nuevos + 11 modificados. |
| 2026-02-20 | 57.0.0 | **Page Builder Preview Audit:** Auditoría completa de los 4 escenarios del Page Builder (Biblioteca, Canvas Editor, Canvas Insert, Página Pública). 66 imágenes de preview premium glassmorphism 3D generadas y desplegadas para 6 verticales (AgroConecta 11, ComercioConecta 11, Empleabilidad 11, Emprendimiento 11, ServiciosConecta 11, JarabaLex 11). Inventario: 219 bloques, 31 categorías, 4 duplicados detectados. |
| 2026-02-20 | 56.0.0 | **Gemini Remediation:** Auditoria y correccion de ~40 archivos. Restauracion de seguridad CSRF en APIs Copilot (patron `_csrf_request_header_token`), fix XSS en Twig (`\|safe_html`), PWA meta tags duales, TranslatableMarkup cast, role check granular, email XSS escape. Stack de seguridad API reforzado. |
| 2026-02-18 | 55.0.0 | **Page Builder Template Consistency:** 129 templates resynced con preview_image, metadatos corregidos, preview_data rico para 55 verticales. Pipelines Canvas Editor y Template Picker unificados (status filter, icon keys, default category). Update hook 9006 aplicado. Fix de `applyUpdates()` eliminado en Drupal 10+ para Legal Intelligence. |
| 2026-02-18 | 54.0.0 | **CI/CD Hardening:** Corrección de trivy.yaml (claves `scan.skip-dirs`), deploy resiliente con fallback SSH. Security Scan y Deploy en verde. |
| 2026-02-18 | 53.0.0 | **The Unified & Stabilized SaaS:** Consolidación final de las 5 fases. Implementación del Stack de Cumplimiento Fiscal N1. Estabilización masiva de 370+ tests unitarios. |
| 2026-02-18 | 52.0.0 | **The Living SaaS:** Lanzamiento de los Bloques O y P. Inteligencia ZKP con Privacidad Diferencial e Interfaz Adaptativa (Ambient UX). |

> **Versión:** 63.0.0 | **Fecha:** 2026-02-23 | **Autor:** IA Asistente
