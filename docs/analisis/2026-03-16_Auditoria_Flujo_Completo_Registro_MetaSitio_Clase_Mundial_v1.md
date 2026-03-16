# Auditoria: Flujo Completo Registro -> MetaSitio — Clase Mundial v1

**Fecha:** 2026-03-16
**Scope:** Journey completo desde primer contacto hasta metasitio operativo
**Cross-refs:** Directrices v133.0.0, Arquitectura v121.0.0, Flujo v86.0.0
**Metodologia:** Auditoria de codigo real (no teorica) — verificacion "codigo existe" vs "usuario lo experimenta"

---

## 1. MAPA DEL JOURNEY COMPLETO

```
FASE 0          FASE 1          FASE 2              FASE 3           FASE 4            FASE 5           FASE 6
Descubrir       Registrar       Provisionar         Pagar            Onboarding        Setup Wizard     MetaSitio
-----------     -----------     ----------------    -----------      Wizard            + Daily Actions  Activo
Landing /       /registro/{v}   User+Tenant         Stripe           7 pasos           Per-vertical     https://sub.ped.es
Demo /demo      Google OAuth    +Group+Domain       Embedded         jaraba_onboarding 52 steps/9 vert  SiteConfig+Pages
Sandbox         POST procesar   TenantOnboarding    Checkout         TenantOnboarding  SetupWizard      MetaSiteResolver
                                Service             jaraba_billing   Progress entity   Registry         Service
```

---

## 2. FASE 0 — DESCUBRIMIENTO (Visitante anonimo)

### 2.1 Landing Page
- **Ruta:** `/` (front page via `system.site`)
- **Template:** `page--front.html.twig` (zero-region pattern)
- **Libraries:** scroll-animations, progressive-profiling, visitor-journey, funnel-analytics, metasite-tracking
- **Parciales:** Hero con CTAs por vertical, selector de audiencia, showcase de verticales

### 2.2 Demo Interactivo (sin registro)
- **Ruta:** `/demo` -> `/demo/start/{profileId}` -> `/demo/dashboard/{sessionId}`
- **Controller:** `DemoController`
- **Service:** `DemoInteractiveService`
- **Rate limiting:** 10 starts/min, 30 tracks/min por IP
- **Conversion nudge (S5-02):** POST `/api/v1/demo/convert` -> redirige a `/registro/{vertical}`

### 2.3 Sandbox (tenant temporal)
- **Ruta:** POST `/api/v1/sandbox/create`
- **Service:** `SandboxTenantService`
- **Duracion:** 30-60 dias trial
- **Conversion:** POST `/api/v1/sandbox/{id}/convert`

### ESTADO: IMPLEMENTADO Y FUNCIONAL

---

## 3. FASE 1 — REGISTRO (Creacion de cuenta)

### 3.1 Registro Manual
- **Ruta:** GET `/registro/{vertical}` (publica)
- **Controller:** `OnboardingController::registerForm()`
- **Renderizado:** NO es un Drupal Form — es un render array con `#theme => 'ecosistema_jaraba_register_form'` + JS que POSTea a la API
- **Procesamiento:** POST `/registro/procesar` -> `OnboardingController::processRegistration()` -> JsonResponse
- **CSRF:** `_csrf_request_header_token: 'TRUE'`

### 3.2 Google OAuth
- **Rutas:** `/user/login/google` -> Google -> `/user/login/google/callback`
- **Controller:** `GoogleOAuthController`
- **Service:** `GoogleOAuthService` (credenciales via `social_auth_google.settings` en settings.secrets.php)
- **Seguridad:** CSRF state en sesion + `hash_equals()` en callback

### 3.3 Validaciones (TenantOnboardingService::validateRegistrationData)
| Campo | Regla |
|-------|-------|
| organization_name | Requerido |
| domain | Requerido, regex `^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$`, unico |
| admin_email | Requerido, formato valido, no duplicado |
| admin_name | Requerido |
| password | Min 8 chars, 1 mayuscula, 1 digito |
| vertical_id | Requerido, debe existir y estar publicado |

### ESTADO: IMPLEMENTADO Y FUNCIONAL

---

## 4. FASE 2 — PROVISIONING AUTOMATICO

### 4.1 Flujo de creacion (TenantOnboardingService::processRegistration)

1. **Crear User** -> username desde email, status=1, rol `tenant_admin`, `field_nombre_completo`
2. **Cargar Vertical + Plan** -> plan starter/default del vertical
3. **Crear Tenant** -> name, domain, vertical_id, admin_user_id, subscription_status='pending'
4. **Tenant::postSave()** (automatico):
   - Crear Group type='tenant', label=tenant.name, add admin as member -> group_id
   - Crear Domain hostname=`{slug}.{base_domain}`, scheme=https, status=active -> domain_id
5. **TenantManager::startTrial()** -> subscription_status='trial', trial_ends=now+14d
6. **Enviar email bienvenida** -> URL acceso, info trial

### 4.2 Entities creadas (4 en total)
| Entity | Tipo | SSOT de |
|--------|------|---------|
| User | Base | Cuenta admin |
| Tenant | Content | Facturacion, Stripe, estado suscripcion |
| Group | Base | Aislamiento contenido (TENANT-BRIDGE-001) |
| Domain | Config | Enrutamiento hostname (DOMAIN-ROUTE-CACHE-001) |

### 4.3 Campos clave del Tenant
| Campo | Tipo | Proposito |
|-------|------|-----------|
| subscription_status | list_string | pending/trial/active/past_due/suspended/cancelled |
| stripe_customer_id | string | Customer ID en Stripe |
| stripe_subscription_id | string | Subscription ID en Stripe |
| stripe_connect_id | string | Stripe Connect (marketplaces) |
| trial_ends | datetime | Fin del trial (14 dias) |
| grace_period_ends | datetime | Gracia post-fallo pago |
| theme_overrides | JSON | Branding custom |

### ESTADO: IMPLEMENTADO Y FUNCIONAL

---

## 5. FASE 3 — PAGO (Stripe Embedded Checkout)

### 5.1 Flujo de checkout
1. GET `/checkout/{saas_plan}?cycle=monthly` -> `CheckoutController::checkoutPage()`
2. Frontend `stripe-checkout.js` -> fetch CSRF token -> POST `/api/v1/billing/checkout-session`
3. `CheckoutSessionService::createSession()`:
   - mode: subscription, ui_mode: embedded (STRIPE-CHECKOUT-001)
   - return_url con {CHECKOUT_SESSION_ID}
   - trial_period_days: 14 (configurable)
   - metadata: plan_id, vertical, business_name, email
   - allow_promotion_codes: true
4. `stripe.initEmbeddedCheckout({clientSecret})` -> monta formulario
5. Post-pago -> redirect `/checkout/success`

### 5.2 Webhooks (BillingWebhookController)
| Evento | Accion |
|--------|--------|
| checkout.session.completed | Auto-provisionar tenant (idempotente, lock 60s) |
| customer.subscription.created | Activar tenant existente |
| customer.subscription.updated | Sync estado |
| invoice.payment_failed | Marcar past_due, iniciar dunning |

### 5.3 SaasPlan entity
- Campos: price_monthly, price_yearly, stripe_price_id, stripe_price_yearly_id, features, limits
- Sync automatico: `StripeProductSyncService` crea Product+Prices en Stripe al guardar
- Admin: `/admin/structure/saas-plan`
- Pricing service: `MetaSitePricingService` (NO-HARDCODE-PRICE-001)

### 5.4 Reverse Trial (modelo freemium inteligente)
- `ReverseTrialService`: 14 dias plan Profesional -> auto-downgrade a Starter si no paga
- Notificaciones dias [7, 3, 1, 0]

### ESTADO: IMPLEMENTADO Y FUNCIONAL

---

## 6. FASE 4 — ONBOARDING WIZARD (7 pasos post-registro)

### 6.1 Flujo del wizard
| Step | Ruta | Obligatorio | Tiempo est. |
|------|------|-------------|-------------|
| 1. Bienvenida | /onboarding/wizard/welcome | Si | 30s |
| 2. Identidad | /onboarding/wizard/identity | Si | 2min |
| 3. Fiscal | /onboarding/wizard/fiscal | Solo commerce | 2min |
| 4. Pagos | /onboarding/wizard/payments | Solo commerce | 3min |
| 5. Equipo | /onboarding/wizard/team | Saltable | 1min |
| 6. Contenido | /onboarding/wizard/content | Si | 3min |
| 7. Lanzamiento | /onboarding/wizard/launch | Si | 30s |

### 6.2 Componentes
- **Modulo:** `jaraba_onboarding`
- **Controller:** `TenantOnboardingWizardController`
- **Entity tracking:** `TenantOnboardingProgress` (persiste progreso por step)
- **Service:** `TenantOnboardingWizardService`
- **LogoColorExtractorService:** Extrae paleta de colores del logo subido

### 6.3 APIs
- POST `/api/v1/onboarding/wizard/advance` — avanzar step
- POST `/api/v1/onboarding/wizard/skip` — saltar step opcional
- POST `/api/v1/onboarding/wizard/logo-colors` — extraer colores del logo
- GET `/api/v1/onboarding/progress` — progreso actual

### ESTADO: IMPLEMENTADO Y FUNCIONAL

---

## 7. FASE 5 — SETUP WIZARD + DAILY ACTIONS (Per-vertical)

### 7.1 Arquitectura
- **Patron:** SETUP-WIZARD-DAILY-001
- **Registry:** `SetupWizardRegistry` + `DailyActionsRegistry` via CompilerPass (tagged services)
- **API:** GET `/api/v1/setup-wizard/{wizard_id}/status` (JSON con steps + completion %)
- **Template:** `_setup-wizard.html.twig` + `_daily-actions.html.twig` (parciales reutilizables)
- **JS:** `setup-wizard.js` (316 lineas) — API refresh, animaciones, celebraciones, localStorage

### 7.2 Cobertura (9 verticales, 52 steps, 39 daily actions)

| Vertical | Wizard ID | Steps | Daily Actions | tenantId |
|----------|-----------|-------|---------------|----------|
| jaraba_candidate | candidato_empleo | 5 | 4 | **0 (HARDCODED)** |
| jaraba_content_hub | editor_content_hub | 3 | 3 | **0 (HARDCODED)** |
| jaraba_legal_intelligence | legal_professional | 3 | 3 | **0 (HARDCODED)** |
| jaraba_agroconecta_core | producer_agro | 5 | 5 | $tenantId OK |
| jaraba_servicios_conecta | provider_servicios | 4 | 4 | $tenantId OK |
| jaraba_copilot_v2 | emprendedor | 4 | 4 | $tenantId OK |
| jaraba_lms | instructor_lms | 3 | 3 | $tenantId OK |
| jaraba_comercio_conecta | merchant_comercio | 5 | 5 | $tenantId OK |
| jaraba_andalucia_ei | coordinador_ei | 4 | 4 | $tenantId OK |

### 7.3 Features verificadas
- Progress ring SVG animado (0-100%)
- Stepper horizontal (desktop) / vertical (mobile)
- Completion badges con contador
- Collapse/expand con localStorage persistence
- Daily action cards con badge system (info/warning/critical)
- Slide-panel -> API refresh loop automatico
- Efectos de celebracion (particles, scale, glow)
- i18n via {% trans %}, WCAG 2.1 AA accesible
- CSS tokens via var(--ej-*)

### ESTADO: IMPLEMENTADO — CON GAPS CRITICOS (ver seccion 9)

---

## 8. FASE 6 — METASITIO ACTIVO

### 8.1 Resolucion MetaSite (3 niveles)
1. **Domain Access exact match** — Domain entity hostname = request.host
2. **Tenant.domain exact match** — fallback
3. **Subdomain prefix match** — dev local

### 8.2 Contexto cargado (MetaSiteResolverService::buildMetaSiteContext)
- `site_config` -> SiteConfig entity (nombre sitio, logo, footer)
- `nav_items` -> SitePageTree con show_in_navigation=true
- `footer_items` -> SitePageTree con show_in_footer=true
- `tenant_name`, `group_id`

### 8.3 Email sequences post-onboarding (MetaSiteEmailSequenceService)
| Secuencia | Timing | Contenido |
|-----------|--------|-----------|
| SEQ_META_001 | Inmediato | Bienvenida + Kit Impulso |
| SEQ_META_002 | Dia 2 | Caso de exito |
| SEQ_META_003 | Dia 5 | Guias del vertical |
| SEQ_META_004 | Dia 8 | Demo + pricing |
| SEQ_META_005 | Dia 15 | Re-engagement (si no convirtio) |

### 8.4 Self-service (usuario logueado)
- `/my-dashboard` — dashboard principal
- `/my-settings/domain` — dominio custom
- `/my-settings/api-keys` — claves API
- `/my-settings/branding` — marca
- `/my-settings/design` — customizer visual
- `/my-settings/plan` — plan y facturacion

### ESTADO: IMPLEMENTADO Y FUNCIONAL

---

## 9. GAPS CRITICOS — "CODIGO EXISTE" vs "USUARIO LO EXPERIMENTA"

### ~~GAP-FLOW-001: CSS del Setup Wizard~~ — VERIFICADO OK

**Resultado:** El CSS del Setup Wizard SI esta compilado correctamente en `ecosistema-jaraba-theme.css`.
- Pipeline: `main.scss` -> `ecosistema-jaraba-theme.css` (confirmado via `package.json` watch script)
- 20+ clases `.setup-wizard__*` presentes en CSS minificado
- Library `global-styling` carga `css/ecosistema-jaraba-theme.css` y `setup-wizard` library depende de `global-styling`
- **No hay gap.** El CSS se sirve correctamente.

### GAP-FLOW-002: tenantId=0 hardcodeado en 3 controllers (P1)

**Archivos afectados:**
- `jaraba_candidate/src/Controller/DashboardController.php:111` — `getStepsForWizard('candidato_empleo', 0)`
- `jaraba_content_hub/src/Controller/ContentHubDashboardController.php:85` — `getStepsForWizard('editor_content_hub', 0)`
- `jaraba_legal_intelligence/src/Controller/LegalDashboardController.php:116` — `getStepsForWizard('legal_professional', 0)`

**Contexto:** Estos son verticales user-scoped (el usuario es el "tenant" — no pertenece a una organizacion). Sin embargo:
- Si los steps de wizard consultan entities filtradas por tenant_id, pasar 0 puede devolver resultados incorrectos o vacios
- Los 6 controllers restantes resuelven $tenantId correctamente via TenantContextService

**Fix recomendado:** Resolver el user ID actual en lugar de 0, o usar un valor sentinel documentado como `USER_SCOPED = -1` que los steps interpreten como "filtrar por uid, no por tenant_id".

### GAP-FLOW-003: Falta conexion entre Onboarding Wizard (7 pasos) y Setup Wizard per-vertical (P2)

**Observacion:** El Onboarding Wizard (jaraba_onboarding, 7 pasos genericos) termina en `/onboarding/bienvenida` con "next steps". El Setup Wizard (per-vertical, 52 steps) vive en el dashboard del vertical. Pero:
- No hay redirect automatico de `/onboarding/bienvenida` al dashboard del vertical
- Los "next steps" de `welcome()` vienen de `VerticalOnboardingConfig` o fallback generico — no del Setup Wizard real
- El usuario podria quedarse en la pagina de bienvenida sin saber que hay un wizard de configuracion en su dashboard

**Fix recomendado:** El boton principal de `/onboarding/bienvenida` deberia redirigir al dashboard del vertical donde el Setup Wizard esta esperando.

---

## 10. SERVICIOS CLAVE DEL JOURNEY

| Servicio | Modulo | Responsabilidad |
|----------|--------|-----------------|
| TenantOnboardingService | ecosistema_jaraba_core | Orquesta registro completo |
| TenantManager | ecosistema_jaraba_core | CRUD lifecycle del tenant |
| TenantBridgeService | ecosistema_jaraba_core | Mapping Tenant <-> Group |
| TenantContextService | ecosistema_jaraba_core | Resuelve tenant actual |
| CheckoutSessionService | jaraba_billing | Crea sesiones Stripe |
| TenantSubscriptionService | jaraba_billing | Lifecycle de suscripcion |
| StripeProductSyncService | jaraba_billing | Sync planes -> Stripe |
| ReverseTrialService | jaraba_billing | Modelo freemium inteligente |
| MetaSiteResolverService | jaraba_site_builder | Resuelve contexto publico |
| MetaSitePricingService | ecosistema_jaraba_core | Precios para landing/twig |
| MetaSiteEmailSequenceService | ecosistema_jaraba_core | Nurturing post-registro |
| TenantOnboardingWizardService | jaraba_onboarding | Wizard 7 pasos |
| SetupWizardRegistry | ecosistema_jaraba_core | Wizard per-vertical (tagged services) |
| DailyActionsRegistry | ecosistema_jaraba_core | Acciones diarias per-vertical |
| GoogleOAuthService | ecosistema_jaraba_core | OAuth flow |
| DemoInteractiveService | ecosistema_jaraba_core | Demo sin registro |
| SandboxTenantService | ecosistema_jaraba_core | Tenant temporal |

---

## 11. REGLAS ARQUITECTONICAS APLICADAS

| Regla | Donde aplica | Estado |
|-------|-------------|--------|
| TENANT-BRIDGE-001 | Fase 2 — nunca resolver Group con Tenant IDs | OK |
| DOMAIN-ROUTE-CACHE-001 | Fase 2 — cada hostname necesita Domain entity | OK |
| STRIPE-CHECKOUT-001 | Fase 3 — Embedded Checkout, return_url | OK |
| NO-HARDCODE-PRICE-001 | Fase 3 — precios via MetaSitePricingService | OK |
| CSRF-API-001 | Fases 1-5 — todas las rutas POST con CSRF | OK |
| ROUTE-LANGPREFIX-001 | Todo el flujo — URLs via Url::fromRoute() | OK |
| PRESAVE-RESILIENCE-001 | Fase 3 — Stripe sync errores no bloquean save | OK |
| AUDIT-PERF-002 | Fase 3 — locks en webhooks (idempotencia) | OK |
| SETUP-WIZARD-DAILY-001 | Fase 5 — patron transversal | OK (con gaps) |
| PREMIUM-FORMS-PATTERN-001 | Fase 4 — forms via PremiumEntityFormBase | OK |

---

## 12. METRICAS DE CONVERSION OBJETIVO

| Metrica | Target | Donde se mide |
|---------|--------|---------------|
| Bounce rate homepage | < 40% | funnel-analytics.js |
| Lead magnet conversion | > 15% | visitor-journey.js |
| Visitor-to-signup | > 5% | funnel-analytics.js |
| Registration -> Active Tenant | < 5 min | TenantOnboardingService |
| First producer/content | < 10 min | Setup Wizard completion |
| Store operational | < 30 min | Full wizard + content |
| TTFV (Time to First Value) | < 60s | ReverseTrialService |

---

## 13. ACCIONES CORRECTIVAS PRIORIZADAS

| # | Gap | Prioridad | Esfuerzo | Impacto |
|---|-----|-----------|----------|---------|
| ~~1~~ | ~~GAP-FLOW-001: CSS setup-wizard~~ | ~~P0~~ | — | **Verificado OK — no hay gap** |
| ~~2~~ | ~~GAP-FLOW-002: tenantId=0 en 3 controllers~~ | ~~P1~~ | — | **CORREGIDO** — userId para user-scoped, TenantContext para content_hub |
| ~~3~~ | ~~GAP-FLOW-003: Next steps bienvenida~~ | ~~P2~~ | — | **CORREGIDO** — CTA principal "Ir a tu dashboard" + mapa 9 verticales + bug 3 URLs iguales |

### Correcciones aplicadas (2026-03-16):

**GAP-FLOW-002:**
- `jaraba_candidate/DashboardController.php:111` — `0` -> `$this->currentUser()->id()`
- `jaraba_content_hub/ContentHubDashboardController.php:85` — `0` -> `TenantContextService::getCurrentTenantId() ?? userId`
- `jaraba_legal_intelligence/LegalDashboardController.php:116` — `0` -> `$this->currentUser()->id()`
- **Hallazgo critico:** Content Hub steps SI usan `$tenantId` para queries (`getPublishedArticleCount($tenantId)`). Con `0`, el wizard nunca marcaba steps como completos.

**GAP-FLOW-003:**
- `OnboardingController::getNextSteps()` fallback reescrito: CTA principal "Ir a tu dashboard" con route map de 9 verticales
- Bug corregido: los 3 next steps apuntaban todos a `entity.tenant.edit_form` (placeholder nunca refinado)
- Nuevo: `resolveVerticalDashboardUrl()` con mapa vertical->route + try-catch defensivo
- Nuevo: `resolveRouteOrFallback()` helper reutilizable

---

*Documento generado por auditoria de codigo real — cada hallazgo verificado contra archivos fuente.*
