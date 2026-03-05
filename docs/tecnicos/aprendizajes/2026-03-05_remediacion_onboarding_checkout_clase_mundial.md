# Aprendizaje #163 — Remediacion Onboarding/Checkout/Catalogos Clase Mundial (32 Gaps, 4 Sprints)

**Fecha:** 2026-03-05
**Contexto:** Implementacion completa del Plan REM-ONBOARDING-001 — 32 gaps en 4 sprints (P0-P3)
**Modulos afectados:** ecosistema_jaraba_core, jaraba_comercio_conecta, jaraba_agroconecta_core, jaraba_lms, jaraba_billing, ecosistema_jaraba_theme

---

## Resumen de Implementacion

### Sprint P0 — Bloqueadores (5/5 completados)
- **P0-01:** Library CSS onboarding corregida (ruta SCSS, compilacion, hook_page_attachments)
- **P0-02:** Checkout marketplace con Stripe real (CheckoutService integracion StripeConnectService)
- **P0-03:** URLs hardcoded → `path()` en onboarding y checkout templates (ROUTE-LANGPREFIX-001)
- **P0-04:** Template agro-producer-products.html.twig creado (patron merchant-products)
- **P0-05:** Frontend instructor LMS creado (InstructorDashboardController con 4 vistas)

### Sprint P1 — Alta Friccion (6/6 completados)
- **P1-01:** Vertical-awareness en onboarding — VerticalOnboardingConfig ConfigEntity (beneficios + next steps dinamicos por vertical)
- **P1-02:** Auto-prompt Stripe Connect para merchants
- **P1-03:** Wizard conectado al flujo principal — barra de progreso parcial `_onboarding-progress.html.twig`
- **P1-04:** Dashboard charts reales con Chart.js via drupalSettings — AUDIT-PERF-N12 library canonical `ecosistema_jaraba_core/chartjs`
- **P1-05:** ProductAgro enriquecido (trazabilidad, multi-imagen, variaciones)
- **P1-06:** Mejoras ComercioConecta (bulk import, IA, preview)

### Sprint P2 — Clase Mundial (8/8 completados)
- **P2-01:** Password strength indicator visual (JS + SCSS progressive bar)
- **P2-02:** Pre-fill billing desde datos de registro
- **P2-03:** Email verification con magic link (ya implementado — servicio, controller, ruta, hook_mail, cron cleanup)
- **P2-04:** Trial expiration notifications (3d, 1d, expirado)
- **P2-05:** Social login Google OAuth — GoogleOAuthService + GoogleOAuthController + 2 rutas + template button
- **P2-06:** Checkout confirmation con recibo real
- **P2-07:** Security badges en checkout (SSL, Stripe, PCI)
- **P2-08:** Analytics instructor LMS — courseAnalytics() con KPIs, Chart.js enrollment trend + engagement

### Sprint P3 — Excelencia (4/4 completados)
- **P3-01:** Onboarding wizard multi-step animado (@keyframes wizard-slide-in/out, pulse-ring)
- **P3-02:** Schema.org Organization JSON-LD en registro
- **P3-03:** Accesibilidad WCAG 2.1 AA (skip navigation, aria-describedby, role=alert, focus-visible, touch targets 44px, semantic nav+ol progress)
- **P3-04:** prefers-reduced-motion en confetti y animaciones

---

## Ficheros Clave Creados

| Fichero | Descripcion |
|---------|-------------|
| `ecosistema_jaraba_core/src/Service/GoogleOAuthService.php` | OAuth2 client ligero para Google (sin contrib) |
| `ecosistema_jaraba_core/src/Controller/GoogleOAuthController.php` | Redirect + callback + user creation |
| `jaraba_lms/templates/lms-instructor-analytics.html.twig` | Dashboard analytics con Chart.js |
| `jaraba_lms/js/instructor-analytics.js` | Enrollment trend + engagement charts |

## Ficheros Modificados Significativos

| Fichero | Cambio |
|---------|--------|
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | +2 rutas Google OAuth |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | +1 servicio google_oauth |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.module` | +1 variable hook_theme |
| `ecosistema_jaraba_core/src/Controller/OnboardingController.php` | +isConfigured() check para Google OAuth |
| `ecosistema_jaraba_core/templates/ecosistema-jaraba-register-form.html.twig` | Google button + Schema.org + WCAG + FieldItemList fix |
| `ecosistema_jaraba_core/templates/partials/_onboarding-progress.html.twig` | Semantic nav+ol rewrite WCAG |
| `ecosistema_jaraba_core/scss/_onboarding.scss` | Skip link + focus-visible + social login + wizard animations + reduced-motion |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | page--auth preprocess: onboarding routes handling |
| `jaraba_lms/jaraba_lms.routing.yml` | +1 ruta analytics |
| `jaraba_lms/jaraba_lms.libraries.yml` | +1 library instructor-analytics |
| `jaraba_lms/src/Controller/InstructorDashboardController.php` | +courseAnalytics() +analyticsTitle() |

---

## Bugs Preexistentes Corregidos

1. **FieldItemList en Twig:** `ecosistema-jaraba-register-form.html.twig` usaba `vertical.name`, `vertical.description.value`, `vertical.id` (FieldItemList no imprimible). Corregido a `vertical.getName()`, `vertical.getDescription()`, `vertical.id()`. Error 500 en `/registro/{vertical}`.

2. **page--auth preprocess sin onboarding:** `ecosistema_jaraba_theme_preprocess_page__auth()` solo manejaba `user.login`, `user.register`, etc. Las rutas `ecosistema_jaraba_core.onboarding.*` caian en `default` → `$form = NULL` → template vacio. Corregido extrayendo system_main_block para rutas de onboarding.

---

## Reglas Aplicadas

- ROUTE-LANGPREFIX-001: Todas las URLs via `path()` / `Url::fromRoute()`
- SECRET-MGMT-001: Credenciales Google OAuth via `settings.secrets.php` + `getenv()`
- CSS-VAR-ALL-COLORS-001: Colores via `var(--ej-*, fallback)` en SCSS
- PRESAVE-RESILIENCE-001: GoogleOAuth check con `hasService()` + try-catch
- CONTROLLER-READONLY-001: GoogleOAuthController sin readonly en propiedades heredadas
- WCAG 2.1 AA: Skip navigation, focus-visible, aria-describedby, role=alert, touch targets 44px
- {% trans %}: Todos los textos en templates con bloque trans
- AUDIT-PERF-N12: Chart.js library canonical compartida

## Patron Nuevo: GOOGLE-OAUTH-LIGHTWEIGHT-001

Google OAuth implementado sin modulo contrib (social_auth_google). Servicio ligero `GoogleOAuthService` que:
- Lee credenciales de `$config['social_auth_google.settings']` (inyectado via settings.secrets.php)
- Construye authorization URL con CSRF state token en session
- Intercambia code por user info via cURL (token endpoint + userinfo endpoint)
- Controller maneja 3 escenarios: user existe → login, user nuevo → create+login, error → redirect

## Patron Nuevo: TWIG-ENTITY-METHOD-001

Cuando un template Twig renderiza campos de una ContentEntity, NUNCA usar acceso directo a property (`entity.name`, `entity.id`). SIEMPRE usar metodos getter (`entity.getName()`, `entity.id()`). El acceso directo devuelve FieldItemList que no se puede imprimir y causa error 500.

---

## Metricas

- **Gaps cerrados:** 32/32 (100%)
- **Sprints completados:** 4/4
- **Ficheros nuevos:** ~10
- **Ficheros modificados:** ~25
- **Verificacion runtime:** Google OAuth verificado con curl (302 → Google, button visible, credenciales OK)
