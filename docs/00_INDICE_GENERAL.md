# 📚 ÍNDICE GENERAL DE DOCUMENTACIÓN

> **Documento auto-actualizable**: Este índice se mantiene sincronizado con la estructura de carpetas y documentos del proyecto.

**Fecha de creación:** 2026-01-09 15:28
**Última actualización:** 2026-02-25
**Versión:** 97.0.0 (Premium Forms Migration 237 + USR-004 User Edit Redirect)

> **🏗️ PREMIUM FORMS MIGRATION: 237 FORMULARIOS EN 50 MODULOS + USR-004 FIX** (2026-02-25)
> - **Contexto:** Migracion global de todos los formularios de entidad ContentEntity a `PremiumEntityFormBase`. Correccion de bug de redirect en user edit form.
> - **HAL-05 (Premium Forms Global Migration):** 237 formularios migrados a `PremiumEntityFormBase` en 8 fases, 50 modulos. 4 patrones de migracion: A (Simple — solo secciones e icono), B (Computed Fields — #disabled = TRUE), C (DI — patron parent::create()), D (Custom Logic — override buildForm/save manteniendo secciones). Glass-card UI con navigation pills, sticky action bar, SCSS `_premium-forms.scss`. 0 `ContentEntityForm` restantes en modulos custom. Verificado con grep + PHPUnit (2899 tests passing).
> - **HAL-06 (USR-004 User Edit Redirect):** Fix de redirect en `/user/{id}/edit`: Drupal core `ProfileForm::save()` no establece redirect, causando que el formulario se recargue en la misma pagina. Nuevo submit handler `_ecosistema_jaraba_core_user_profile_redirect` que redirige a `entity.user.canonical`. El handler de password reset (`_ecosistema_jaraba_core_password_set_redirect`) se ejecuta despues y tiene prioridad, redirigiendo a `/empleabilidad`.
> - **1 regla nueva:** PREMIUM-FORMS-PATTERN-001 (P1, todo formulario de entidad DEBE extender PremiumEntityFormBase). Regla de oro #42. Aprendizaje #125.
> - **Cross-refs:** Directrices v73.0.0, Arquitectura v74.0.0, Flujo v28.0.0, Indice v97.0.0.

> **🎨 META-SITIO PEPEJARABA: EMOJI→SVG ICON REMEDIATION + CONTRAST FIX** (2026-02-25)
> - **Contexto:** Verificacion visual post-remediacion del meta-sitio pepejaraba.com detecto 11 emojis Unicode usados como iconos y falta de contraste en hero/CTA de la homepage. Las directrices del SaaS prohiben emojis en produccion (ICON-CONVENTION-001).
> - **HAL-01 (Auditoria Emoji):** Scan completo de canvas_data en 9 paginas (IDs 57-65). 11 emojis detectados en 4 paginas: 6 en homepage (57), 1 en Casos de Exito (60), 1 en Blog (61), 3 en Contacto (62). Metodo: `preg_match_all` + `class="*icon*"` content extraction via PHP script.
> - **HAL-02 (Iconos SVG Custom):** 12 ficheros SVG creados en `business/` (6 regular + 6 duotone): `time-pressure` (reloj alarma urgencia), `launch-idea` (bombilla con chispas), `talent-spotlight` (persona con rayos visibilidad), `career-connect` (maletin con check matching), `seed-momentum` (plantula con flecha ascendente), `store-digital` (tienda con WiFi). Convenciones: `viewBox 0 0 24`, `stroke-width: 2`, colores hex `#233D63` (no `currentColor` para canvas_data).
> - **HAL-03 (Reemplazo Canvas Data):** Script PHP ejecutado via `drush scr` reemplazo 22 instancias de emoji por SVGs inline en `html` + `rendered_html` de canvas_data. Pages 60-62 usan iconos estandar inlined (clipboard, edit, mail, smartphone, map-pin).
> - **HAL-04 (Contrast Fix):** Hero `.pj-hero` y CTA `.pj-final-cta` con gradiente azul oscuro `linear-gradient(135deg, #233D63, #1a3050, #162740)`. Overlay opacidad 0.15→0.25. WCAG 2.1 AA cumplido.
> - **2 reglas nuevas:** ICON-EMOJI-001 (P0, prohibicion emojis en canvas_data), ICON-CANVAS-INLINE-001 (P0, hex explicito en SVGs inline). Regla de oro #38. Aprendizaje #124.
> - **Cross-refs:** Directrices v72.0.0, Arquitectura v73.0.0, Flujo v27.0.0, Indice v96.0.0.

> **🚀 ELEVACION EMPLEABILIDAD + ANDALUCIA EI PLAN MAESTRO + META-SITE RENDERING** (2026-02-25)
> - **Contexto:** Sprint de elevacion de 3 verticales y rendering multi-tenant. Perfil de candidato con formularios premium, Andalucia EI con portal completo de participante, y meta-sitios con branding tenant-aware.
> - **HAL-01 (Empleabilidad Elevation):** `CandidateProfileForm` premium con 6 secciones y labels en espanol. `ProfileSectionForm` generico para CRUD slide-panel (education, experience, language). Campo photo migrado a image type (update_10004). Campos date migrados a datetime (update_10005). 5 CV preview PNGs. Seccion idiomas con ruta dedicada. `ProfileCompletionService` refactorizado con entity queries. `profile_section_manager.js` para delete confirmation.
> - **HAL-02 (Andalucia EI Plan Maestro):** 8 fases implementadas: P0/P1 fixes (AiMentorshipTracker, StoExportService, tenant isolation, DI), 11 bloques Page Builder verticales con config YAMLs + templates, landing conversion `/andalucia-ei/programa` (Zero Region + OG + JSON-LD), portal participante `/andalucia-ei/mi-participacion` (health gauge, timeline, training, badges, PDF), `ExpedienteDocumento` entity (19 categorias, vault cifrado, revision IA, firmas digitales, STO compliance), mensajeria integration (CONTEXT_ANDALUCIA_EI), AI automation (CopilotContextProvider, AdaptiveDifficultyEngine, 4 nudges proactivos), SEO (sitemap, lead magnet guide). 71 ficheros, +6644 lineas.
> - **HAL-03 (CRM Premium Forms):** 5 formularios CRM (Company, Contact, Opportunity, Activity, PipelineStage) migrados a `PremiumEntityFormBase` con glass-card UI y section navigation pills.
> - **HAL-04 (Meta-Site Tenant-Aware):** `MetaSiteResolverService` como punto unico de resolucion meta-sitio. Schema.org Organization tenant-aware en `jaraba_geo`. Title tag con `meta_title_suffix`. Header/footer/nav override desde SiteConfig + SitePageTree. Body class `meta-site`. Fix SitePageTree status `1` (int).
> - **Cross-refs:** Directrices v71.0.0, Arquitectura v72.0.0, Flujo v26.0.0, Indice v95.0.0. Aprendizaje #123.

> **🔧 REMEDIACION TENANT 11 FASES: TENANTBRIDGE + BILLING + ISOLATION + CI** (2026-02-25)
> - **Contexto:** Remediacion sistematica de la confusion Tenant vs Group detectada en la auditoria profunda. 11 fases ejecutadas en 2 commits (`96dc2bb4` + `0bd84663`). Corrige 14 bugs de billing, acceso cross-tenant, y ausencia de Kernel tests en CI.
> - **HAL-01 (TenantBridgeService):** Nuevo servicio `TenantBridgeService` (`ecosistema_jaraba_core.tenant_bridge`) con 4 metodos (`getTenantForGroup`, `getGroupForTenant`, `getTenantIdForGroup`, `getGroupIdForTenant`). Registrado en `services.yml`. Error handling con `\InvalidArgumentException`. Consumido por QuotaManagerService, BillingController, BillingService, StripeWebhookController, SaasPlan.
> - **HAL-02 (Billing Entity Type Fix):** 14 correcciones de entity type en 6 ficheros. `QuotaManagerService` migrado de `getStorage('group')` a `TenantBridgeService` bridge pattern. `BillingController`, `BillingService`, `StripeWebhookController` y `SaasPlan` corregidos de `getStorage('group')` a `getStorage('tenant')`.
> - **HAL-03 (Tenant Isolation):** `PageContentAccessControlHandler` refactorizado con `EntityHandlerInterface` + DI, inyecta `TenantContextService`, implementa `isSameTenant()` para update/delete. `DefaultAccessControlHandler` renombrado a `DefaultEntityAccessControlHandler`. `PathProcessorPageContent` tenant-aware con `TenantContextService` opcional. `TenantContextService` enhanced con `getCurrentTenantId()` nullable.
> - **HAL-04 (CI + Tests + Cleanup):** Job `kernel-test` en CI con MariaDB 10.11. 5 tests nuevos: `TenantBridgeServiceTest` (Unit), `QuotaManagerServiceTest` actualizado (Unit), `PageContentAccessTest` (Kernel), `PathProcessorPageContentTest` (Kernel), `DefaultEntityAccessControlHandlerTest` (Unit rename). Scripts movidos de `web/` a `scripts/maintenance/`. `test_paths.sh` limpiado.
> - **3 reglas nuevas:** TENANT-BRIDGE-001 (P0), TENANT-ISOLATION-ACCESS-001 (P0), CI-KERNEL-001 (P0). Reglas de oro #35, #36, #37. Aprendizaje #122.
> - **Cross-refs:** Directrices v70.0.0, Arquitectura v71.0.0, Flujo v25.0.0, Indice v94.0.0

> **🌐 META-SITIO JARABAIMPACT.COM: PATHPROCESSOR + CONTENIDO INSTITUCIONAL** (2026-02-24)
> - **Contexto:** Construccion del meta-sitio institucional `jarabaimpact.com` usando Page Builder. Los path_alias de entidades PageContent no se registraban como rutas Drupal, devolviendo 404.
> - **HAL-01 (PathProcessor):** Nuevo `PathProcessorPageContent` (`InboundPathProcessorInterface`, prioridad 200). Resuelve path_alias → `/page/{id}`. Skip list de prefijos de sistema (/api/, /admin/, /user/). Static cache. Sin filtro status (delega en AccessControlHandler).
> - **HAL-02 (Titulos/Publicacion):** 7 paginas con titulos institucionales actualizados via `PATCH /api/v1/pages/{id}/config` y publicadas via `POST /api/v1/pages/{id}/publish`.
> - **HAL-03 (Contenido):** 5/7 paginas con contenido en espanol completo (Homepage, Plataforma, Verticales, Impacto, Contacto). Programas y Recursos con contenido parcial.
> - **2 archivos creados/modificados.** Regla nueva: PATH-ALIAS-PROCESSOR-001. Regla de oro #34. Aprendizaje #120.
> - **Directrices v69.0.0, Arquitectura v70.0.0, Flujo v24.0.0, Indice v93.0.0**

> **🔒 AUDITORIA HORIZONTAL: SEGURIDAD ACCESS HANDLERS + CAN-SPAM EMAILS** (2026-02-24)
> - **Contexto:** Primera auditoria cross-cutting del SaaS tras 6 auditorias verticales. Revisa flujos horizontales que cruzan los 21 modulos: strict equality en access handlers (seguridad) y compliance CAN-SPAM en plantillas MJML transaccionales.
> - **Sprint 1 — Seguridad P0:** 52 instancias de `==` (loose equality) reemplazadas por `(int) === (int)` en 39 access handlers de 21 modulos. Previene type juggling en comparaciones de ownership (`getOwnerId()`, `target_id`, `id()`). TenantAccessControlHandler (ruta `src/` sin subdirectorio `Access/`) corregido manualmente.
> - **Sprint 2 — CAN-SPAM P0:** 28 plantillas MJML horizontales (base + auth + billing + marketplace + fiscal + andalucia_ei) actualizadas con: (1) `<mj-preview>` con preheader unico por plantilla, (2) direccion postal CAN-SPAM en footer (Juncaril, Albolote), (3) font `Outfit` como primario, (4) 6 colores universales off-brand reemplazados (#374151→#333333, #6b7280→#666666, #f3f4f6→#f8f9fa, #e5e7eb→#E0E0E0, #9ca3af→#999999, #111827→#1565C0), (5) colores de grupo unificados a #1565C0 (#2563eb, #1A365D, #553C9A, #233D63). Colores semanticos preservados (error red, success green, warning amber, Andalucia EI naranja/teal).
> - **Verificacion:** 0 loose equality restantes, 28/28 con mj-preview + Juncaril + Outfit, 0 colores off-brand, PHPUnit 19 tests OK.
> - **5 reglas nuevas:** ACCESS-STRICT-001, EMAIL-PREVIEW-001, EMAIL-POSTAL-001, BRAND-FONT-001, BRAND-COLOR-001. Regla de oro #33. Aprendizaje #119.
> - **4 commits, ~67 ficheros.** Directrices v68.0.0, Arquitectura v69.0.0, Flujo v23.0.0, Desarrollo v3.8, Indice v92.0.0.

> **🎯 EMPLEABILIDAD /MY-PROFILE PREMIUM — FASE FINAL** (2026-02-24)
> - **Contexto:** Sesion anterior implemento 90% del perfil premium (7 secciones glassmorphism, `jaraba_icon()` duotone, stagger animations, timeline experiencia, skill pills, completion ring SVG). Faltaba la entidad `CandidateEducation`, fix XSS y cleanup del controller.
> - **Nueva entidad `CandidateEducation`:** ContentEntity con `AdminHtmlRouteProvider`, `field_ui_base_route`, 6 rutas admin, 6 campos (institution, degree, field_of_study, start_date, end_date + user_id). Interface con getters tipados. SettingsForm + settings tab + collection tab "Educacion". Update hook `10002`. Permiso `administer candidate educations`.
> - **Fix XSS (TWIG-XSS-001):** `{{ profile.summary|raw }}` → `{{ profile.summary|safe_html }}` en template de perfil. Verificado: elimina `<script>`, preserva `<b>`.
> - **Controller cleanup:** HTML hardcodeado en fallback de `editProfile()` → render array con `#theme => 'my_profile_empty'`.
> - **3 ficheros creados, 6 modificados.** Reglas aplicadas: TWIG-XSS-001, FIELD-UI-SETTINGS-TAB-001. Aprendizaje #118.

> **🏗️ ENTITY ADMIN UI REMEDIATION COMPLETA: P0-P5 + CI GREEN** (2026-02-24)
> - **Problema:** 286 entidades auditadas en 62 modulos. Faltaban views_data, collection links, field_ui_base_route, routes, menu links. Ademas, 175 entidades tenian `field_ui_base_route` pero sin default settings tab, impidiendo que Field UI mostrase pestanas "Administrar campos" / "Administrar visualizacion de formulario".
> - **P0 (COMPLETADO):** views_data handler anadido a 19 entidades → 286/286 (100%).
> - **P1 (COMPLETADO):** collection link + routing + task tabs a 41 entidades.
> - **P2 (COMPLETADO):** field_ui_base_route + SettingsForm a entidades sin gestion de campos.
> - **P3 (COMPLETADO):** Normalizacion de rutas al patron `/admin/content/`.
> - **P4 (COMPLETADO):** links.menu.yml a 19 modulos.
> - **P5 (COMPLETADO):** Default settings tabs anadidos a 175 entidades en 46 modulos — habilita Field UI tabs.
> - **CI estabilizado:** 12 Unit test errors + 18 Kernel test errors corregidos. 0 errors en Unit (2859 tests) y Kernel (211 tests).
> - **Reglas nuevas:** KERNEL-TEST-DEPS-001, OPTIONAL-SERVICE-DI-001, FIELD-UI-SETTINGS-TAB-001. Directrices v3.7 (secciones 29, 30, 31). Aprendizaje #116.

> **🎨 ICON SYSTEM: ZERO CHINCHETAS — 305 PARES VERIFICADOS** (2026-02-24)
> - **Problema:** Templates en 4 modulos usaban convenciones rotas de `jaraba_icon()` (path-style, args invertidos, args posicionales) que causaban fallback a emoji 📌 (chincheta). Faltaban ~170 SVGs en categorias bridge y primarias.
> - **Auditoria completa:** 305 pares unicos `jaraba_icon('category', 'name')` extraidos de todo el codebase. Cada par verificado contra filesystem.
> - **32 llamadas corregidas en 4 modulos:** jaraba_interactive (17 path-style), jaraba_i18n (9 args invertidos), jaraba_facturae (8 invertidos), jaraba_resources (13 posicionales).
> - **~170 SVGs/symlinks creados:** 8 bridge categories (achievement, finance, general, legal, navigation, status, tools, media, users) con symlinks a categorias primarias (actions, fiscal, ui).
> - **6 iconos nuevos:** ui/maximize, ui/infinity (outline + duotone), media/play-circle, users/group, ui/certificate, ui/device-phone.
> - **3 symlinks reparados:** ui/save.svg circular, bookmark.svg circular, general/alert-duotone.svg roto.
> - **177 templates Page Builder** verificados (1 symlink circular corregido).
> - **Resultado:** 0 chinchetas en toda la plataforma.
> - **4 commits, ~230 ficheros.** Reglas nuevas: ICON-CONVENTION-001 (P0), ICON-DUOTONE-001 (P1), ICON-COLOR-001 (P1). Regla de oro #32. Aprendizaje #117.
> - **Directrices v66.0.0, Arquitectura v66.0.0, Flujo v20.0.0, Indice v90.0.0**

> **📧 EMAILS PREMIUM HTML + DASHBOARD URLS CORREGIDAS** (2026-02-24)
> - **Problema:** Los 3 correos de Andalucia +ei usaban texto plano dentro del wrapper branded. Las tarjetas de acceso rapido en el dashboard enlazaban a landing pages publicas en vez de paginas funcionales.
> - **P1 (Email):** Cuerpo plano en `hook_mail()` sin estructura visual. Fix: HTML con `Markup::create()`, inline CSS, table layout, tarjetas de resumen, CTAs con colores Jaraba, triaje IA color-coded.
> - **P2 (URLs):** `/empleo`→`/jobs`, `/talento`→`/my-profile`, `/emprender`→`/emprendimiento`. Ahora via `{{ path('route.name') }}` con prefijo de idioma automatico.
> - **3 ficheros modificados.** Reglas nuevas: EMAIL-HTML-PREMIUM-001, TWIG-ROUTE-PATH-001. Directrices v3.6 (secciones 27, 28). Aprendizaje #115.

> **🔬 SOLICITUD VIEW + TRIAJE IA END-TO-END OPERATIVO** (2026-02-24)
> - **Problema:** La pagina canonica `/admin/content/andalucia-ei/solicitudes/{id}` no renderizaba ningun campo. El triaje IA no se ejecutaba por incompatibilidad con la API del modulo Drupal AI.
> - **P1 (Critica):** Entidad sin `view_builder` handler + campos sin `setDisplayOptions('view', ...)`. Solo `setDisplayConfigurable()` no basta.
> - **P2/P3 (Alta):** API de chat requiere `ChatInput`/`ChatMessage` (no arrays planos). Respuesta via `getNormalized()->getText()` (no `getText()`).
> - **P4 (Media):** Modelo `claude-haiku-4-5-latest` no soportado → `claude-3-haiku-20240307`.
> - **P5 (Media):** Key module: `base64_encoded: "false"` (string) es truthy en PHP → corrompe API keys.
> - **P6 (Media):** CRLF en `.env` anade `\r` a valores en containers Linux. Fix: `sed` + `lando rebuild`.
> - **Resultado:** Triaje IA operativo (Anthropic → OpenAI failover). Score 75, recomendacion "admitir". 22 campos visibles en vista admin.
> - **2 ficheros modificados.** Reglas nuevas: ENTITY-VIEW-DISPLAY-001, DRUPAL-AI-CHAT-001, KEY-MODULE-BOOL-001, ENV-FILE-CRLF-001. Aprendizaje #114.

> **🧪 SOLICITUD FORM: 4 RUNTIME BUGS CORREGIDOS VIA BROWSER TESTING** (2026-02-24)
> - **Contexto:** Browser testing del recorrido de candidato en Andalucia +ei descubre 4 bugs de runtime que bloqueaban el envio del formulario. Ninguno era detectable por inspeccion estatica.
> - **BUG-1 (Critica):** Time-gate anti-spam bloquea permanentemente — `#value` se regenera en form rebuild. Fix: leer de `getUserInput()` (POST raw).
> - **BUG-2 (Critica):** `entityTypeManager()` no existe en FormBase (solo en ControllerBase). Fix: DI explicita via `create()`.
> - **BUG-3 (Alta):** `catch (\Exception)` no captura `TypeError` del modulo AI. Fix: `catch (\Throwable)`.
> - **BUG-4 (Media):** `tenant_context` (por usuario) en formulario anonimo → siempre NULL. Fix: usar `tenant_manager` (por dominio).
> - **1 fichero modificado.** Reglas nuevas: DRUPAL-HIDDEN-VALUE-001, DRUPAL-FORMBASE-DI-001, PHP-THROWABLE-001, TENANT-RESOLVER-001. Aprendizaje #112.

> **🔒 ANDALUCIA +EI DEEP FLOW REVIEW: 2 P0 + 6 P1 CORREGIDOS** (2026-02-24)
> - **Auditoria:** Revision exhaustiva del flujo Andalucia +ei desde 9 perspectivas senior revelo 2 hallazgos P0 (criticos) y 6 P1 (altos).
> - **P0-1 (CSRF):** Ruta DELETE API sin `_csrf_request_header_token`. Corregido en routing.yml.
> - **P0-2 (Tenant):** Entidad `SolicitudEi` sin campo `tenant_id` — viola Regla de Oro #4. Campo anadido + resolucion en formulario + update hook 10001.
> - **P1-3 (Emojis email):** Eliminados emojis Unicode del email de confirmacion (subject + body).
> - **P1-4 (SCSS):** Literal `⚠` reemplazado por escape `\26A0` en 2 selectores CSS.
> - **P1-5/P1-6 (DI):** Inyeccion de dependencias corregida en 2 controladores (renderer + 3 servicios opcionales).
> - **P1-7 (user_id/uid):** Eliminado campo `user_id` redundante en `ProgramaParticipanteEi`, unificado en `uid` (EntityOwnerTrait) + update hook 10002 migracion.
> - **P1-8 (_format):** Anadido `_format: 'json'` a 3 rutas API GET.
> - **9 ficheros modificados, +113/-21 lineas.** Reglas reforzadas: CSRF-API-001, DI-CONTROLLER-001. Reglas nuevas: API-FORMAT-001, ENTITY-OWNER-001. Aprendizaje #111.

> **🚀 ANDALUCIA +EI LAUNCH READINESS: 8 INCIDENCIAS CORREGIDAS** (2026-02-23)
> - **Problema:** El formulario de solicitud del programa Andalucia +ei tragaba silenciosamente errores de validacion y mensajes de exito. Ademas, usaba emojis Unicode, las paginas legales devolvian 404, y el badge de features decia "4 verticales" en vez de "6".
> - **C1 (Critica):** Template `solicitud-ei-page.html.twig` no tenia `{{ messages }}`. Anadido preprocess hook que inyecta `['#type' => 'status_messages']`.
> - **C2 (Critica):** 6 emojis Unicode reemplazados por `jaraba_icon()` SVG duotone.
> - **C3 (Critica):** Enlace de privacidad corregido de `/privacidad` a `/politica-privacidad`.
> - **A1-A3 (Alta):** 5 rutas nuevas en `ecosistema_jaraba_core` con controladores que leen contenido de `theme_get_setting()`. 3 templates zero-region. Footer actualizado con URLs canonicas en espanol.
> - **M1 (Media):** Badge corregido a "6 verticales" con descripcion actualizada.
> - **M2 (Media):** Aplazado — requiere assets de diseno.
> - **Theme Settings:** TAB 14 "Paginas Legales" con campos para contenido de privacidad, terminos, cookies, sobre nosotros, contacto.
> - **13 ficheros modificados.** Reglas FORM-MSG-001, LEGAL-ROUTE-001, LEGAL-CONFIG-001. Regla de oro #28. Aprendizaje #110.
> - **Directrices v64.0.0, Arquitectura v64.0.0, Flujo v18.0.0, Desarrollo v3.4, Indice v84.0.0**

> **🔧 STICKY HEADER MIGRATION: FIXED → STICKY GLOBAL** (2026-02-23)
> - **Problema:** `.landing-header` con `position: fixed` causa solapamiento sobre contenido cuando la altura del header es variable (botones de accion wrappean a 2 lineas). Un `padding-top` fijo nunca compensa correctamente una altura variable.
> - **Solucion:** Migrar a `position: sticky` como default global. El header participa en el flujo del documento y nunca solapa contenido.
> - **Override:** Solo `body.landing-page` y `body.page-front` mantienen `position: fixed` (hero fullscreen).
> - **4 archivos SCSS modificados:** `_landing-page.scss` (sticky default + fixed override + toolbar top), `_page-premium.scss` (padding 1.5rem), `_error-pages.scss` (padding 1.5rem), `_user-pages.scss` (padding 1.5rem).
> - **Toolbar admin:** `top: 39px` (cerrado) / `top: 79px` (horizontal abierto) definido una unica vez en `_landing-page.scss`.
> - **Regla nueva:** CSS-STICKY-001. Regla de oro #27. Aprendizaje #109.
> - **Directrices v63.0.0, Arquitectura v63.0.0, Flujo v17.1.0, Indice v83.0.0**

> **🛡️ AI IDENTITY ENFORCEMENT + COMPETITOR ISOLATION** (2026-02-23)
> - **Problema:** El copiloto FAB del landing se identificaba como "Claude" en vez de como Jaraba. Ningun prompt de IA tenia regla de identidad ni prohibia mencionar competidores.
> - **Regla AI-IDENTITY-001:** Identidad IA inquebrantable — todo agente/copiloto DEBE identificarse como "Asistente de Jaraba Impact Platform". NUNCA revelar modelo subyacente (Claude, ChatGPT, Gemini, etc.).
> - **Regla AI-COMPETITOR-001:** Aislamiento de competidores — ningun prompt DEBE mencionar ni recomendar plataformas competidoras ni modelos de IA externos.
> - **Implementacion centralizada:** `BaseAgent.buildSystemPrompt()` inyecta regla como parte #0 (14+ agentes heredan). `CopilotOrchestratorService` antepone `$identityRule` a 8 modos. `PublicCopilotController` incluye bloque IDENTIDAD INQUEBRANTABLE. Servicios standalone (FaqBotService, ServiciosConectaCopilotAgent, CoachIaService) con antepuesto manual.
> - **Competidores eliminados de prompts:** ChatGPT, Perplexity en AiContentGeneratorService; LinkedIn/Discord en CoachIaService; HubSpot, Zapier en RecommendationEngineService; ChatGPT en DiagnosticWizardController.
> - **12 archivos modificados.** Reglas de oro #25, #26. Aprendizaje #108.
> - **Directrices v63.0.0, Arquitectura v63.0.0, Flujo v17.0.0, Indice v82.0.0**

> **💰 PRECIOS CONFIGURABLES v2.1: ARQUITECTURA IMPLEMENTADA** (2026-02-23)
> - **Objetivo:** Eliminar hardcodeo de capacidades de plan en 3 puntos (QuotaManager, SaasPlan, PlanValidator).
> - **2 ConfigEntities nuevas:** `SaasPlanTier` (tiers con aliases y Stripe Price IDs) + `SaasPlanFeatures` (features y limites por vertical+tier).
> - **21 seed YAMLs:** 3 tiers (starter, professional, enterprise) + 18 features configs (5 verticales x 3 tiers + 3 defaults).
> - **PlanResolverService:** Broker central con cascade especifico→default→NULL, normalizacion por aliases, resolucion Stripe Price ID→tier.
> - **Integraciones:** QuotaManagerService (lee de ConfigEntity, fallback hardcoded), PlanValidator (fuente adicional en cascade), BillingWebhookController (resolucion tier desde Stripe).
> - **Drush command:** `jaraba:validate-plans` para verificar completitud de configuraciones.
> - **8 contract tests:** PlanConfigContractTest (tier CRUD, features CRUD, normalize, cascade, checkLimit, hasFeature, Stripe resolution, getPlanCapabilities).
> - **Admin UI:** `/admin/config/jaraba/plan-tiers` y `/admin/config/jaraba/plan-features` con AdminHtmlRouteProvider.
> - **SCSS:** `_plan-admin.scss` con `var(--ej-*, fallback)`, body class `page-plan-admin`.
> - **Reglas nuevas:** PLAN-CASCADE-001, PLAN-RESOLVER-001. Aprendizaje #107.
> - **Directrices v62.0.0, Arquitectura v62.0.0, Flujo v16.0.0, Vertical Patterns v2.2.0, Indice v81.0.0**

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
| 2026-02-25 | **95.0.0** | **Elevacion Empleabilidad + Andalucia EI Plan Maestro + Meta-Site Rendering:** Bloque HAL-01 a HAL-04: Empleabilidad (CandidateProfileForm premium 6 secciones, ProfileSectionForm CRUD, photo→image, date→datetime, CV PNGs, idiomas, entity queries), Andalucia EI Plan Maestro (8 fases, 11 bloques PB, landing, portal, ExpedienteDocumento, mensajeria, AI, SEO — 71 ficheros), CRM (5 forms a PremiumEntityFormBase), Meta-Site (MetaSiteResolverService, Schema.org, title, header/footer, nav). Reglas de oro #38-#41. Aprendizaje #123. |
| 2026-02-25 | **94.0.0** | **Remediacion Tenant 11 Fases:** Bloque destacado HAL-01 a HAL-04: TenantBridgeService (4 metodos, services.yml, error handling), Billing Entity Type Fix (14 correcciones, 6 ficheros, QuotaManagerService bridge), Tenant Isolation (access handler con DI, isSameTenant, PathProcessor tenant-aware, TenantContextService enhanced), CI + Tests + Cleanup (kernel-test job MariaDB, 5 tests, rename handler, scripts maintenance). 3 reglas: TENANT-BRIDGE-001, TENANT-ISOLATION-ACCESS-001, CI-KERNEL-001. Aprendizaje #122. |
| 2026-02-24 | **93.0.0** | **Meta-Sitio jarabaimpact.com — PathProcessor + Content:** Nuevo `PathProcessorPageContent` (InboundPathProcessorInterface, prioridad 200) para resolver path_alias de PageContent a rutas /page/{id}. 7 paginas institucionales creadas. Contenido espanol en 5/7 paginas via GrapesJS API. Regla PATH-ALIAS-PROCESSOR-001. Aprendizaje #120. |
| 2026-02-24 | **92.0.0** | **Auditoria Horizontal — Strict Equality + CAN-SPAM MJML:** Primera auditoria cross-cutting del SaaS. Sprint 1: 52 instancias de `==` reemplazadas por `(int) === (int)` en 39 access handlers de 21 modulos (previene type juggling en ownership checks). Sprint 2: 28 plantillas MJML horizontales con mj-preview, postal CAN-SPAM, font Outfit, y paleta de marca unificada (6 colores universales + 4 de grupo reemplazados, semanticos preservados). 5 reglas nuevas: ACCESS-STRICT-001, EMAIL-PREVIEW-001, EMAIL-POSTAL-001, BRAND-FONT-001, BRAND-COLOR-001. Regla de oro #33. Aprendizaje #119. |
| 2026-02-24 | **91.0.0** | **Empleabilidad /my-profile Premium — Fase Final:** Nueva entidad `CandidateEducation` (ContentEntity con AdminHtmlRouteProvider, field_ui_base_route, 6 rutas admin, SettingsForm, collection tab, update hook 10002). Fix XSS `\|raw` → `\|safe_html` en template de perfil (TWIG-XSS-001). Controller cleanup: HTML hardcodeado → `#theme => 'my_profile_empty'`. Permiso `administer candidate educations`. 3 ficheros creados, 6 modificados. Aprendizaje #118. |
| 2026-02-24 | **90.0.0** | **Icon System — Zero Chinchetas:** 305 pares `jaraba_icon()` auditados en todo el codebase. 0 chinchetas restantes. ~170 SVGs/symlinks nuevos en 8 bridge categories. 32 llamadas con convencion rota corregidas en 4 modulos. 177 templates Page Builder verificados. 3 symlinks reparados (2 circulares, 1 roto). Reglas ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001. Regla de oro #32. Aprendizaje #117. |
| 2026-02-24 | **88.0.0** | **Premium HTML Emails + Dashboard URLs:** 3 emails Andalucia +ei reescritos de texto plano a HTML premium con `Markup::create()`, inline CSS, table layout. Tarjetas resumen, CTAs naranjas, triaje IA color-coded (verde/amarillo/rojo). URLs dashboard corregidas: `/empleo`→`/jobs`, `/talento`→`/my-profile` via `path()`. Reglas EMAIL-HTML-PREMIUM-001, TWIG-ROUTE-PATH-001. Aprendizaje #115. |
| 2026-02-24 | **87.0.0** | **SolicitudEi Entity View + AI Triage E2E:** Vista canonica vacia por falta de `view_builder` handler y `setDisplayOptions('view')` en 22 campos. Triaje IA roto por API incompatible (ChatInput/ChatMessage, getNormalized()->getText(), modelo explicito). Key module: `base64_encoded` string truthy corrupts keys. CRLF en .env + lando rebuild. Reglas ENTITY-VIEW-DISPLAY-001, DRUPAL-AI-CHAT-001, KEY-MODULE-BOOL-001, ENV-FILE-CRLF-001. Aprendizaje #114. |
| 2026-02-24 | **86.0.0** | **SolicitudEiPublicForm Runtime Bugs:** Browser testing descubre 4 bugs que bloqueaban el formulario. BUG-1: time-gate `#value` se regenera en rebuild → `getUserInput()`. BUG-2: `entityTypeManager()` no existe en FormBase → DI explicita. BUG-3: `catch (\Exception)` no captura TypeError → `\Throwable`. BUG-4: `tenant_context` (por usuario) en form anonimo → `tenant_manager` (por dominio). Reglas DRUPAL-HIDDEN-VALUE-001, DRUPAL-FORMBASE-DI-001, PHP-THROWABLE-001, TENANT-RESOLVER-001. Aprendizaje #112. |
| 2026-02-24 | **85.0.0** | **Andalucia +ei Deep Flow Review P0/P1:** Auditoria profunda desde 9 perspectivas. P0-1: CSRF en DELETE API + _format json en 4 rutas. P0-2: tenant_id en SolicitudEi (campo + form submit + update hook 10001). P1-3: emojis eliminados de email. P1-4: SCSS Unicode escape \26A0. P1-5/P1-6: DI corregida en 2 controladores (renderer + 3 servicios opcionales nullable). P1-7: user_id eliminado, unificado en uid (EntityOwnerTrait) + update hook 10002 migracion. 9 ficheros, +113/-21. Reglas API-FORMAT-001, ENTITY-OWNER-001. Aprendizaje #111. |
| 2026-02-23 | **84.0.0** | **Andalucia +ei Launch Readiness:** 8 incidencias corregidas para 2a edicion. Fix critico: `{{ messages }}` + preprocess hook para formulario que tragaba errores. 6 emojis → `jaraba_icon()`. 5 rutas legales/info nuevas con URLs canonicas espanol. Controladores con `theme_get_setting()`. 3 templates zero-region. Footer actualizado. Badge "6 verticales". TAB 14 theme settings. 13 ficheros. Reglas FORM-MSG-001, LEGAL-ROUTE-001, LEGAL-CONFIG-001. Regla de oro #28. Aprendizaje #110. |
| 2026-02-23 | **83.0.0** | **Sticky Header Migration:** `.landing-header` migrado de `position: fixed` a `position: sticky` por defecto. Solo `body.landing-page`/`body.page-front` mantienen `fixed`. Eliminados padding-top compensatorios fragiles. Toolbar admin `top: 39px/79px` global. 4 archivos SCSS. Regla CSS-STICKY-001. Regla de oro #27. Aprendizaje #109. |
| 2026-02-23 | **82.0.0** | **AI Identity Enforcement + Competitor Isolation:** Blindaje de identidad IA en toda la plataforma. Regla inquebrantable en BaseAgent (14+ agentes), CopilotOrchestratorService (8 modos), PublicCopilotController (landing FAB), FaqBotService, ServiciosConectaCopilotAgent, CoachIaService. Eliminadas 5 menciones de competidores en prompts. 12 archivos modificados. Reglas AI-IDENTITY-001, AI-COMPETITOR-001. Reglas de oro #25, #26. Aprendizaje #108. |
| 2026-02-23 | **81.0.0** | **Precios Configurables v2.1 Implementado:** 2 ConfigEntities (`SaasPlanTier` + `SaasPlanFeatures`), `PlanResolverService` como broker central, 21 seed YAMLs, update hook 9019, integracion en QuotaManagerService + PlanValidator + BillingWebhookController, Drush `jaraba:validate-plans`, 8 contract tests, SCSS `_plan-admin.scss`, body class `page-plan-admin`. Reglas PLAN-CASCADE-001, PLAN-RESOLVER-001. Aprendizaje #107. |
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
