# 📚 ÍNDICE GENERAL DE DOCUMENTACIÓN

> **Documento auto-actualizable**: Este índice se mantiene sincronizado con la estructura de carpetas y documentos del proyecto.

**Fecha de creación:** 2026-01-09 15:28
**Última actualización:** 2026-02-23
**Versión:** 80.0.0 (Plan de Remediación v1.1 Recalibrado)

> **🛠️ PLAN DE REMEDIACIÓN AUDITORÍA LÓGICA/TÉCNICA (2026-02-23)** (2026-02-23)
> - **Documento:** `docs/implementacion/20260223-Plan_Remediacion_Auditoria_Logica_Negocio_Tecnica_SaaS_v1_Codex.md`.
> - **Versión actual:** v1.1.0 (recalibrada tras contra-auditoría).
> - **Estructura:** 6 workstreams (Tenant, Seguridad/Aislamiento, Plan/Billing, Quotas, QA/CI, Observabilidad).
> - **Backlog:** IDs `REM-*` priorizados P0/P1/P2 con estimación recalibrada (180-240h) y plan temporal 60-75 días.
> - **Calidad:** estrategia Unit+Kernel+Functional para flujos críticos y KPIs de salida.
> - **Dependencias:** auditoría base `20260223-Auditoria_Profunda_Logica_Negocio_Tecnica_SaaS_v1_Codex.md` + contra-auditoría `20260223b-Contra_Auditoria_Claude_Codex_SaaS_v1.md`.

> **🔎 AUDITORÍA PROFUNDA LÓGICA DE NEGOCIO Y TÉCNICA (2026-02-23)** (2026-02-23)
> - **Documento:** `docs/tecnicos/auditorias/20260223-Auditoria_Profunda_Logica_Negocio_Tecnica_SaaS_v1_Codex.md`.
> - **Ámbito:** Revisión multidisciplinar senior (negocio, finanzas, mercado, producto, arquitectura SaaS, ingeniería, UX, Drupal, GrapesJS, SEO/GEO, IA).
> - **Hallazgos críticos priorizados:** inconsistencia de contrato tenant (`tenant` vs `group`), drift de catálogo de planes, mismatch de métodos de pricing, errores de tipo en trial, enforcement de cuotas no canónico.
> - **Entregables:** Matriz de riesgo P0/P1, plan de remediación 30-60-90, KPIs de recuperación y tabla de referencias técnicas/documentales.
> - **Estado:** Auditoría estática completada con trazabilidad por archivo/línea.

> **🔒 SECURE MESSAGING: IMPLEMENTACION COMPLETA (Doc 178)** (2026-02-20)
> - **Doc 178:** Modulo `jaraba_messaging` implementado al completo — mensajeria segura con cifrado server-side AES-256-GCM.
> - **104 archivos creados:** 13 YAML foundation, 4 entidades, 3 modelos (DTO/Value Objects), 6 excepciones, 18 servicios, 7 access checks, 7 controladores, 2 formularios, 4 WebSocket, 2 queue workers, 8 ECA plugins, 3 Symfony events, 1 Drush command, 1 list builder, 9 Twig templates, 11 SCSS, 4 JS, 1 package.json.
> - **5 Entidades:** SecureConversation (ContentEntity), SecureMessage (custom table DTO), ConversationParticipant, MessageAuditLog (append-only hash chain), MessageReadReceipt.
> - **18 Servicios:** MessagingService, ConversationService, MessageService, MessageEncryptionService, TenantKeyService, MessageAuditService, NotificationBridgeService, AttachmentBridgeService, PresenceService, SearchService, RetentionService, + 7 Access Checks.
> - **20+ Endpoints API REST** + WebSocket (Ratchet dev / Swoole prod) + Redis pub/sub + cursor-based pagination.
> - **Cifrado AES-256-GCM** con Argon2id key derivation, per-tenant key isolation, SHA-256 hash chain audit inmutable.
> - **8 ECA Plugins:** 3 eventos (MessageSent, MessageRead, ConversationCreated), 3 condiciones (IsFirstMessage, RecipientNotOnline, NotificationNotMuted), 2 acciones (SendAutoReply, SendNotification).
> - **13 Permisos**, 8 roles, 104 archivos implementados, 6 sprints completados. Todos los PHP pasan validacion de sintaxis.
> - **Reglas nuevas:** MSG-ENC-001, MSG-WS-001, MSG-RATE-001. Aprendizaje #106.
> - **Directrices v61.0.0, Arquitectura v61.0.0, Flujo v15.0.0, Desarrollo v3.3, Indice v77.0.0**

> **🔧 SERVICIOSCONECTA SPRINT S3: BOOKING ENGINE OPERATIVO** (2026-02-20)
> - **Booking API Fix:** `createBooking()` corregido — field mapping (booking_date, offering_id, uid), validaciones (provider activo+aprobado, offering ownership, advance_booking_min), client data (name/email/phone desde user), price desde offering, meeting_url Jitsi.
> - **AvailabilityService:** +3 metodos — `isSlotAvailable()` (verifica slot recurrente + sin colision), `markSlotBooked()` (auditoria), `hasCollision()` (refactored privado).
> - **State Machine Fix:** `updateBooking()` con transiciones correctas (cancelled_client/cancelled_provider), role enforcement (solo provider confirma/completa), cancellation_reason.
> - **Cron Reminders Fix:** Flags `reminder_24h_sent`/`reminder_1h_sent` verificadas en query y actualizadas tras envio. Sin duplicados.
> - **hook_entity_update Fix:** booking_date, getOwnerId(), str_starts_with('cancelled_').
> - **3 archivos modificados:** AvailabilityService.php, ServiceApiController.php, jaraba_servicios_conecta.module.
> - **Reglas nuevas:** API-FIELD-001, STATE-001, CRON-FLAG-001.
> - **Aprendizaje #105.** ServiciosConecta Booking Engine — Field Mapping, State Machine & Cron Idempotency.
> - **Directrices v60.0.0, Arquitectura v59.0.0, Flujo v14.0.0, Desarrollo v3.2, Indice v75.0.0**

> **🎯 VERTICAL RETENTION PLAYBOOKS: IMPLEMENTACIÓN COMPLETA** (2026-02-20)
> - **Doc 179:** Motor de retencion verticalizado implementado al completo en `jaraba_customer_success`.
> - **25 archivos nuevos:** 14 PHP (entidades, servicios, controladores, QueueWorker) + 5 config YAML + 4 Twig + 1 JS + 1 SCSS.
> - **11 archivos modificados:** routing, services, permissions, links, libraries, module hooks, install, schema, theme SCSS.
> - **2 Entidades:** `VerticalRetentionProfile` (16 campos JSON, config por vertical) y `SeasonalChurnPrediction` (append-only, 11 campos).
> - **2 Servicios:** `VerticalRetentionService` (health score + ajuste estacional + senales + clasificacion riesgo) y `SeasonalChurnService` (predicciones mensuales ajustadas).
> - **5 Perfiles verticales:** AgroConecta (cosecha 60d), ComercioConecta (rebajas 21d), ServiciosConecta (ROI 30d), Empleabilidad (exito 14d), Emprendimiento (fase 30d).
> - **7 Endpoints API REST** + 1 dashboard FOC `/customer-success/retention` con heatmap estacional.
> - **Reglas nuevas:** ENTITY-APPEND-001, CONFIG-SEED-001.
> - **Aprendizaje #104.** Vertical Retention Implementation — Append-Only Entities & Config Seeding.
> - **Directrices v58.0.0, Arquitectura v58.0.0, Flujo v12.0.0, Desarrollo v3.1, Indice v74.0.0**

> **🎨 PAGE BUILDER PREVIEW AUDIT: 66 IMÁGENES PREMIUM GENERADAS** (2026-02-20)
> - **Auditoría 4 Escenarios:** Biblioteca de Plantillas, Canvas Editor, Canvas Insert, Página Pública.
> - **66 imágenes glassmorphism 3D** generadas y desplegadas para 6 verticales.
> - **Inventario Canvas Editor:** 219 bloques, 31 categorías, 4 duplicados detectados.
> - **Reglas nuevas:** PB-PREVIEW-002 (preview obligatorio por vertical), PB-DUP-001 (no duplicar bloques).
> - **Aprendizaje #103.** Page Builder Preview Image Audit & Generation.
> - **Directrices v58.0.0, Arquitectura v58.0.0, Flujo v12.0.0, Indice v73.0.0**

> **🔒 GEMINI REMEDIATION: CSRF, XSS, AUDIT & FIX** (2026-02-20)
> - **Auditoria Multi-IA:** ~40 archivos modificados por Gemini auditados. 21 revertidos, 15 corregidos, ~15 conservados.
> - **Seguridad CSRF API:** Patron `_csrf_request_header_token` implementado en Copilot v2. JS con cache de token via `/session/token`.
> - **XSS Prevention:** `|raw` → `|safe_html` en 4 campos de ofertas empleo. `Html::escape()` en emails. TranslatableMarkup cast.
> - **PWA Fix:** Meta tags duales restaurados (apple-mobile-web-app-capable + mobile-web-app-capable).
> - **Reglas nuevas:** CSRF-API-001, TWIG-XSS-001, TM-CAST-001, PWA-META-001.
> - **Aprendizaje #102.** Remediacion Gemini y Protocolo Multi-IA.
> - **Directrices v56.0.0, Arquitectura v56.0.0, Flujo v11.0.0, Desarrollo v3.0, Indice v71.0.0**

> **🎨 PAGE BUILDER TEMPLATE CONSISTENCY: 129 TEMPLATES RESYNCED** (2026-02-18)
> - **Preview Images:** 88 templates vinculados a sus PNGs existentes + 4 PNGs placeholder creados para serviciosconecta.
> - **Metadatos:** Typo "Seccion seccion" corregido en 15 ficheros, tildes en 59 descripciones verticales, label duplicado corregido.
> - **Preview Data Rico:** 55 templates verticales enriquecidos con datos de dominio (features[], testimonials[], faqs[], stats[], plans[]).
> - **Pipelines Unificados:** Status filter en TemplatePickerController, icon keys en CanvasApiController, categoría default a 'content'.
> - **Drupal 10+ Fix:** `applyUpdates()` eliminado reemplazado en Legal Intelligence update_10004.
> - **Reglas nuevas:** PB-PREVIEW-001, PB-DATA-001, PB-CAT-001, DRUPAL-ENTUP-001.
> - **Aprendizaje #101.** Page Builder Template Consistency & Drupal 10+ Entity Updates.
> - **Directrices v55.0.0, Arquitectura v55.0.0, Flujo v10.0.0, Indice v70.0.0**

> **🔧 CI/CD HARDENING: SECURITY SCAN & DEPLOY ESTABILIZADOS** (2026-02-18)
> - **Trivy Config Fix:** Corregidas claves inválidas (`exclude-dirs` → `scan.skip-dirs`). Exclusiones de vendor/core/contrib ahora operativas.
> - **Deploy Resiliente:** Smoke test con fallback SSH cuando `PRODUCTION_URL` no está configurado.
> - **Reglas nuevas:** CICD-TRIVY-001 (estructura config Trivy), CICD-DEPLOY-001 (fallback en smoke tests).
> - **Aprendizaje #100.** Estabilización Trivy Config y Deploy Smoke Test.
> - **Directrices v54.0.0, Arquitectura v54.0.0, Indice v69.0.0**

> **✅ FASE 5: CONSOLIDACIÓN & ESTABILIZACIÓN (THE GREEN MILESTONE)** (2026-02-18)
> - **Estabilización Masiva:** 370+ tests unitarios corregidos en 17 módulos (Core, IA, Fiscal, Billing, PWA, etc.).
> - **Stack Cumplimiento Fiscal N1:** Integración de `jaraba_privacy`, `jaraba_legal` y `jaraba_dr`. `ComplianceAggregator` operacional.
> - **Refactorización DI:** Soporte para mocking de clases contrib `final` mediante inyección flexible.
> - **Directrices v53.0.0, Arquitectura v53.0.0, Indice v68.0.0**

> **🌌 FASE 4: LA FRONTERA FINAL — BLOQUES O + P (LIVING SAAS)** (2026-02-18)
> - **Bloque O: ZKP Intelligence:** Módulo `jaraba_zkp`. `ZkOracleService` implementado con Privacidad Diferencial (Laplace Noise).
> - **Bloque P: Generative Liquid UI:** Módulo `jaraba_ambient_ux`. `IntentToLayoutService` implementado. La interfaz muta (Crisis/Growth) vía `hook_preprocess_html`.
> - **Estado Final:** Plataforma Soberana Autoadaptativa.
> - **Aprendizaje #99.** Inteligencia ZK y UX Ambiental.
> - **Directrices v52.0.0, Arquitectura v52.0.0, Indice v67.0.0**

> **🤖 FASE 3: LA ECONOMÍA AGÉNTICA IMPLEMENTADA — BLOQUES M + N** (2026-02-18)
> - **Bloque M: Identidad Soberana (DID):** Módulo `jaraba_identity` implementado. Entidad `IdentityWallet` Ed25519.
> - **Bloque N: Mercado de Agentes:** Módulo `jaraba_agent_market` implementado. Protocolo JDTP y Ledger inmutable.
> - **Directrices v51.0.0, Arquitectura v51.0.0, Indice v66.0.0**

...

## 15. Registro de Cambios (Hitos Recientes)

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-23 | **80.0.0** | **Plan de Remediación v1.1 Recalibrado:** Actualización del documento `docs/implementacion/20260223-Plan_Remediacion_Auditoria_Logica_Negocio_Tecnica_SaaS_v1_Codex.md` con integración de contra-auditoría, esfuerzo ajustado a 180-240h, horizonte 60-75 días, repriorización P0 (aislamiento tenant + contrato tenant + billing coherente) y referencias explícitas a specs 07/134/135/148/158/162. |
| 2026-02-23 | **79.0.0** | **Plan de Remediación Auditoría Lógica/Técnica SaaS:** Nuevo documento en `docs/implementacion/20260223-Plan_Remediacion_Auditoria_Logica_Negocio_Tecnica_SaaS_v1_Codex.md` con 6 workstreams, backlog `REM-*` P0/P1/P2, plan temporal 30-60-90, estrategia de testing Unit+Kernel+Functional, riesgos/dependencias y KPIs de cierre. |
| 2026-02-23 | **78.0.0** | **Auditoría Profunda Lógica de Negocio y Técnica SaaS:** Nuevo documento en `docs/tecnicos/auditorias/20260223-Auditoria_Profunda_Logica_Negocio_Tecnica_SaaS_v1_Codex.md` con diagnóstico ejecutivo, hallazgos P0/P1 (tenant model, planes, billing, cuotas, acceso), matriz de riesgo, roadmap 30-60-90, KPIs y tabla de referencias trazables. |
| 2026-02-20 | **77.0.0** | **Secure Messaging Implementado (Doc 178):** Modulo `jaraba_messaging` implementado al completo. 104 archivos creados (13 YAML, 4 entidades, 3 modelos, 6 excepciones, 18 servicios, 7 access checks, 7 controladores, 2 formularios, 4 WebSocket, 2 queue workers, 8 ECA plugins, 3 eventos Symfony, 9 templates, 11 SCSS, 4 JS). AES-256-GCM + Argon2id KDF, SHA-256 hash chain audit, Ratchet WebSocket, cursor-based pagination. Reglas MSG-ENC-001, MSG-WS-001, MSG-RATE-001. Aprendizaje #106. |
| 2026-02-20 | 76.0.0 | **Secure Messaging Plan (Doc 178):** Plan de implementacion completo para `jaraba_messaging`. 5 entidades, 12 servicios, 20+ endpoints API REST, WebSocket server (Ratchet/Swoole), cifrado AES-256-GCM, 8 ECA flows, 13 permisos, 64+ archivos planificados en 6 sprints. Documento v1.1.0 (1684 lineas, 18 secciones). |
| 2026-02-20 | 75.0.0 | **ServiciosConecta Sprint S3 — Booking Engine Operativo:** Fix createBooking() API (field mapping, 5 validaciones, client data, price, Jitsi URL). +3 metodos AvailabilityService (isSlotAvailable, markSlotBooked, hasCollision). Fix updateBooking() state machine (cancelled_client/cancelled_provider, role enforcement). Fix cron reminder flags (24h/1h idempotency). Fix hook_entity_update (booking_date, getOwnerId). Reglas API-FIELD-001, STATE-001, CRON-FLAG-001. Aprendizaje #105. |
| 2026-02-20 | 74.0.0 | **Vertical Retention Playbooks — Implementacion Completa (Doc 179):** 25 archivos nuevos + 11 modificados. 2 entidades (VerticalRetentionProfile, SeasonalChurnPrediction append-only), 2 servicios, 7 endpoints API REST, dashboard FOC con heatmap, 5 perfiles verticales, QueueWorker cron. Reglas ENTITY-APPEND-001, CONFIG-SEED-001. Aprendizaje #104. |
| 2026-02-20 | 73.0.0 | **Page Builder Preview Audit:** 66 imagenes premium glassmorphism 3D generadas para 6 verticales. Canvas Editor: 219 bloques, 31 categorias, 4 duplicados. Reglas PB-PREVIEW-002, PB-DUP-001. Aprendizaje #103. |
| 2026-02-20 | 72.0.0 | **Vertical Retention Playbooks (Doc 179):** Plan de implementacion para verticalizar motor de retencion. 2 entidades nuevas, 2 servicios, 7 endpoints API, 5 perfiles verticales, dashboard FOC con heatmap estacional, QueueWorker cron. 18 directrices verificadas. |
| 2026-02-20 | 71.0.0 | **Gemini Remediation:** Auditoria y correccion de ~40 archivos de otra IA. CSRF APIs (patron header token), XSS Twig (safe_html), PWA meta tags duales, TranslatableMarkup cast, role checks, email escape. 4 reglas nuevas. Aprendizaje #102. |
| 2026-02-18 | 70.0.0 | **Page Builder Template Consistency:** 129 templates resynced (preview_image, metadatos, preview_data rico, pipelines unificados). Fix applyUpdates() Drupal 10+. Aprendizaje #101. |
| 2026-02-18 | 69.0.0 | **CI/CD Hardening:** Fix de config Trivy (scan.skip-dirs) y deploy resiliente con fallback SSH. Reglas CICD-TRIVY-001 y CICD-DEPLOY-001. |
| 2026-02-18 | 68.0.0 | **Unified & Stabilized:** Consolidación final de las 5 fases. Estabilización masiva de tests unitarios e implementación del Stack Fiscal N1. |
| 2026-02-18 | 67.0.0 | **The Living SaaS:** Implementación de Inteligencia ZKP e Interfaz Líquida (Bloques O y P). Plataforma autoadaptativa. |
| 2026-02-18 | 66.0.0 | **Economía Agéntica:** Implementación de DID y Protocolo JDTP (Bloques M y N). |
| 2026-02-18 | 65.0.0 | **SaaS Golden Master:** Consolidación final de orquestación IA y Wallet SOC2. |
