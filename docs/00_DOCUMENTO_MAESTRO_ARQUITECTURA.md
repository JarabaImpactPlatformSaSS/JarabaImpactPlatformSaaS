# 🏗️ DOCUMENTO MAESTRO DE ARQUITECTURA
## Jaraba Impact Platform SaaS v69.0

**Fecha:** 2026-02-24
**Versión:** 69.0.0 (Auditoria Horizontal — Strict Equality + CAN-SPAM MJML)
**Estado:** Produccion (Horizontal Audit Complete + Empleabilidad Premium Complete + Entity Admin UI 100% + Andalucia +ei 2a Edicion Ready + AI Identity Hardened + Precios Configurables v2.1 + Security Hardened + Secure Messaging)
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

┌─────────────────────────────────────────────────────────────────────────┐
│                      VERTICAL: EMPLEABILIDAD ⭐                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_candidate (v2.0 — Profile Premium) ⭐                     │
│   ├── Entidades (6): CandidateProfile, CandidateSkill,                │
│   │   CandidateExperience, CandidateEducation (NEW),                  │
│   │   CandidateLanguage, CopilotConversation + CopilotMessage         │
│   ├── Premium /my-profile: 7 secciones glassmorphism                  │
│   │   ├── Hero: Avatar, nombre, headline, ubicacion, badge            │
│   │   ├── About: Summary (|safe_html XSS-safe) + nivel educacion     │
│   │   ├── Experience: Timeline cronologica descendente                 │
│   │   ├── Education: Grid de registros CandidateEducation              │
│   │   ├── Skills: Pills con badge de verificacion                     │
│   │   ├── Links: LinkedIn, GitHub, Portfolio, Website                  │
│   │   └── CTA: Completion ring SVG + enlace a edicion                 │
│   ├── Empty State: Glassmorphism card + benefit cards + CTA            │
│   ├── ProfileController: Carga resiliente (try/catch por entidad)     │
│   ├── SCSS: 920 lineas, design tokens --ej-*, BEM cp-*, responsive   │
│   ├── Iconos: 15 pares jaraba_icon() duotone verificados              │
│   └── Admin: /admin/content/candidate-educations con Field UI         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      ICON SYSTEM: ZERO CHINCHETAS ⭐                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 ecosistema_jaraba_core (Icon Engine)                              │
│   ├── JarabaTwigExtension: jaraba_icon() Twig function                │
│   │   ├── Firma: jaraba_icon(category, name, {variant, color, size})  │
│   │   ├── Resolucion: {modulePath}/images/icons/{category}/{name}     │
│   │   │   [-variant].svg                                               │
│   │   ├── Variantes: outline (default), outline-bold, filled, duotone │
│   │   ├── Fallback: emoji via getFallbackEmoji() → 📌 (chincheta)    │
│   │   └── Inline SVG: stroke/fill inherits CSS currentColor           │
│   │                                                                     │
│   ├── Categorias primarias (6):                                        │
│   │   ├── actions/ (download, check, search, sparkles, etc.)          │
│   │   ├── fiscal/ (invoice, balance, treasury, etc.)                  │
│   │   ├── media/ (play-circle, image, camera)                         │
│   │   ├── micro/ (arrow-right, chevron-down, dot — 12px)             │
│   │   ├── ui/ (settings, globe, lock, file-text, etc.)               │
│   │   └── users/ (user, group, id-card)                               │
│   │                                                                     │
│   ├── Bridge categories (7 — symlinks a categorias primarias):        │
│   │   ├── achievement/ → actions/ (trophy, medal, target, etc.)       │
│   │   ├── finance/ → fiscal/ (wallet, credit-card, coins, etc.)      │
│   │   ├── general/ → ui/ (settings, info, alert-triangle, etc.)      │
│   │   ├── legal/ → ui/ (scale, shield, file-text, etc.)              │
│   │   ├── navigation/ → ui/ (home, menu, compass, etc.)              │
│   │   ├── status/ → ui/ (check-circle, clock, alert-circle, etc.)    │
│   │   └── tools/ → ui/ (wrench, code, terminal, etc.)                │
│   │                                                                     │
│   ├── SVGs: ~340 iconos (outline + duotone por cada)                  │
│   │   ├── Outline: stroke-only, stroke-width="2"                      │
│   │   └── Duotone: stroke + fill con opacity="0.2" para capas fondo  │
│   │                                                                     │
│   └── Auditoria: 305 pares unicos verificados, 0 chinchetas          │
│       ├── 32 llamadas con convencion rota corregidas (4 modulos)      │
│       ├── ~170 SVGs/symlinks creados para bridge categories           │
│       ├── 2 symlinks circulares corregidos (ui/save, bookmark)        │
│       └── 1 symlink roto reparado (general/alert-duotone)            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      SEGURIDAD: ACCESS HANDLERS ⭐                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ACCESS-STRICT-001 (Auditoria Horizontal)                             │
│   ├── 52 instancias de == (loose equality) → (int) === (int)          │
│   ├── 39 access handlers en 21 modulos                                 │
│   ├── Patrones corregidos:                                             │
│   │   ├── $entity->getOwnerId() == $account->id()                    │
│   │   ├── $entity->get('field')->target_id == $account->id()          │
│   │   └── $merchant->getOwnerId() == $account->id()                  │
│   ├── Fix universal: (int) LHS === (int) $account->id()              │
│   ├── Previene type juggling: "0"==false, null==0, ""==0             │
│   └── Verificacion: grep "== $account->id()" | grep -v "===" → 0    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                      EMAIL: CAN-SPAM COMPLIANCE ⭐                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   📦 jaraba_email (28 plantillas horizontales)                         │
│   ├── Grupos: base (1) + auth (5) + billing (7) + marketplace (6)    │
│   │   + fiscal (3) + andalucia_ei (6)                                 │
│   ├── CAN-SPAM Compliance:                                             │
│   │   ├── <mj-preview>: Preheader unico por plantilla (28/28)        │
│   │   ├── Direccion postal: Juncaril, Albolote (28/28)               │
│   │   └── Opt-out: {{ unsubscribe_url }} (ya existia)                │
│   ├── Brand Consistency:                                               │
│   │   ├── Font: Outfit, Arial, Helvetica, sans-serif (28/28)         │
│   │   ├── Azul primario: #1565C0 (unificado desde 4 variantes)       │
│   │   ├── Body text: #333333, Muted: #666666, BG: #f8f9fa            │
│   │   ├── Dividers: #E0E0E0, Disclaimer: #999999                     │
│   │   └── Headings: #1565C0                                           │
│   └── Colores semanticos preservados:                                  │
│       ├── Error: #dc2626 (payment_failed, dunning_notice)             │
│       ├── Exito: #16a34a (subscription_created, orders)               │
│       ├── Warning: #f59e0b (trial_ending), #D97706 (fiscal)          │
│       └── Andalucia EI: #FF8C42 (naranja), #00A9A5 (teal)           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

---

## 15. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-24 | **69.0.0** | **Auditoria Horizontal — Strict Equality + CAN-SPAM MJML:** Primera auditoria cross-cutting del SaaS. 52 instancias de `==` reemplazadas por `(int) === (int)` en 39 access handlers de 21 modulos (ACCESS-STRICT-001). 28 plantillas MJML horizontales con compliance CAN-SPAM completo: mj-preview, postal Juncaril, font Outfit, paleta de marca unificada (#1565C0 como azul primario, 6 colores universales reemplazados). Colores semanticos preservados. Secciones de arquitectura: Access Handlers + Email CAN-SPAM. 5 reglas nuevas. Aprendizaje #119. |
| 2026-02-24 | **68.0.0** | **Empleabilidad Profile Premium — Fase Final:** Nueva entidad `CandidateEducation` (ContentEntity completa con AdminHtmlRouteProvider, field_ui_base_route, 6 rutas admin, SettingsForm, update hook 10002). Fix XSS `\|raw` → `\|safe_html` en template de perfil premium. Controller fallback cleanup → render array con template premium. Seccion de arquitectura Empleabilidad documentada (6 entidades, 7 secciones glassmorphism, ProfileController resiliente). 3 ficheros creados, 6 modificados. Aprendizaje #118. |
| 2026-02-24 | **67.0.0** | **Entity Admin UI Remediation Complete:** 286 entidades auditadas, 175 Field UI tabs, CI 100% green. |
| 2026-02-24 | **66.0.0** | **Icon System — Zero Chinchetas:** Sistema de iconos `jaraba_icon()` auditado y completado. 305 pares unicos verificados en todo el codebase con 0 chinchetas restantes. ~170 SVGs/symlinks nuevos en 8 bridge categories. 32 llamadas con convencion rota corregidas en 4 modulos (jaraba_interactive, jaraba_i18n, jaraba_facturae, jaraba_resources). 177 templates Page Builder verificados. 2 symlinks circulares y 1 roto reparados. Reglas ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001. |
| 2026-02-23 | **64.0.0** | **Andalucia +ei Launch Readiness:** Correccion de 8 incidencias bloqueantes para la 2a edicion. Fix critico: `{{ messages }}` en template de solicitud (formulario tragaba errores silenciosamente). 6 emojis reemplazados por `jaraba_icon()`. 5 rutas nuevas para paginas legales/informativas (`/politica-privacidad`, `/terminos-uso`, `/politica-cookies`, `/sobre-nosotros`, `/contacto`). Controladores con `theme_get_setting()` para contenido configurable. 3 templates zero-region. Footer con URLs canonicas en espanol. Badge "6 verticales" corregido. TAB 14 en theme settings para contenido legal. 13 ficheros modificados. Reglas FORM-MSG-001, LEGAL-ROUTE-001, LEGAL-CONFIG-001. Aprendizaje #110. |
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

> **Versión:** 68.0.0 | **Fecha:** 2026-02-24 | **Autor:** IA Asistente
