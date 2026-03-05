# Plan de Implementacion: Remediacion Completa Onboarding, Checkout, Catalogos de Producto y Cursos — Clase Mundial

**Fecha de creacion:** 2026-03-05
**Ultima actualizacion:** 2026-03-05
**Autor:** Claude Opus 4.6 (Anthropic) — 15 roles senior (Business Consultant, SaaS Architect, UX Engineer, Drupal Engineer, Theming Engineer, Financial Analyst, IA Engineer, QA Lead, Security Auditor, Accessibility Specialist, DevOps, Performance Engineer, Data Architect, Frontend Engineer, Product Manager)
**Version:** 1.0.0
**Categoria:** Plan de Implementacion Estrategico
**Codigo:** REM-ONBOARDING-001
**Estado:** PLANIFICADO
**Esfuerzo estimado:** 160-200h (4 sprints, 32 acciones)
**Documentos fuente:** Auditoria Flujo Onboarding Checkout Clase Mundial v1.1 (2026-03-05), CLAUDE.md v1.2.0, Arquitectura Theming SaaS Master v2.1
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v110.0.0, `00_FLUJO_TRABAJO_CLAUDE.md` v63.0.0, `CLAUDE.md` v1.2.0, `07_VERTICAL_CUSTOMIZATION_PATTERNS.md` v3.1.0
**Modulos afectados:** `ecosistema_jaraba_core`, `jaraba_comercio_conecta`, `jaraba_agroconecta_core`, `jaraba_lms`, `jaraba_billing`, `ecosistema_jaraba_theme`
**Rutas principales:** `/registro/{vertical}`, `/onboarding/*`, `/checkout/*`, `/productor/productos`, `/mi-comercio/productos`, `/mis-cursos`, `/planes/{vertical_key}`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Principios rectores](#13-principios-rectores)
   - 1.4 [Metricas de impacto](#14-metricas-de-impacto)
   - 1.5 [Alcance y exclusiones](#15-alcance-y-exclusiones)
   - 1.6 [Filosofia "Sin Humo"](#16-filosofia-sin-humo)
   - 1.7 [Relacion con auditorias previas](#17-relacion-con-auditorias-previas)
2. [Diagnostico: Mapa de Gaps](#2-diagnostico-mapa-de-gaps)
   - 2.1 [Matriz "El Codigo Existe" vs "El Usuario Lo Experimenta"](#21-matriz-el-codigo-existe-vs-el-usuario-lo-experimenta)
   - 2.2 [Dependencias entre gaps](#22-dependencias-entre-gaps)
   - 2.3 [Riesgos de no remediar](#23-riesgos-de-no-remediar)
   - 2.4 [Arquitectura Stripe: cuenta unica + Connect Express](#24-arquitectura-stripe)
3. [Arquitectura de la Solucion](#3-arquitectura-de-la-solucion)
   - 3.1 [Flujo completo onboarding end-to-end (estado objetivo)](#31-flujo-completo-onboarding-end-to-end)
   - 3.2 [Flujo completo checkout marketplace (estado objetivo)](#32-flujo-completo-checkout-marketplace)
   - 3.3 [Flujo completo catalogo de productos por vertical (estado objetivo)](#33-flujo-completo-catalogo-de-productos)
   - 3.4 [Flujo completo catalogo de cursos para instructor (estado objetivo)](#34-flujo-completo-catalogo-de-cursos)
   - 3.5 [Modelo de vertical-awareness configurable](#35-modelo-de-vertical-awareness)
   - 3.6 [Patron de CSS Library unificado](#36-patron-de-css-library-unificado)
   - 3.7 [Diagrama de componentes y relaciones](#37-diagrama-de-componentes)
4. [Sprint P0 — Bloqueadores: Impiden Operacion (Semanas 1-2)](#4-sprint-p0--bloqueadores)
   - 4.1 [P0-01: Corregir library CSS de onboarding](#41-p0-01-corregir-library-css-de-onboarding)
   - 4.2 [P0-02: Checkout marketplace con Stripe real](#42-p0-02-checkout-marketplace-con-stripe-real)
   - 4.3 [P0-03: URLs hardcoded a path() en onboarding y checkout](#43-p0-03-urls-hardcoded-a-path)
   - 4.4 [P0-04: Template agro-producer-products.html.twig (FALTA)](#44-p0-04-template-agro-producer-products)
   - 4.5 [P0-05: Frontend instructor LMS (INEXISTENTE)](#45-p0-05-frontend-instructor-lms)
5. [Sprint P1 — Alta Friccion: Degradan Conversion (Semanas 3-4)](#5-sprint-p1--alta-friccion)
   - 5.1 [P1-01: Vertical-awareness en onboarding (beneficios + next steps dinamicos)](#51-p1-01-vertical-awareness-en-onboarding)
   - 5.2 [P1-02: Auto-prompt Stripe Connect para merchants](#52-p1-02-auto-prompt-stripe-connect)
   - 5.3 [P1-03: Conectar wizard al flujo principal](#53-p1-03-conectar-wizard-al-flujo-principal)
   - 5.4 [P1-04: Dashboard charts reales con Chart.js](#54-p1-04-dashboard-charts-reales)
   - 5.5 [P1-05: Enriquecer ProductAgro (trazabilidad, multi-imagen, variaciones)](#55-p1-05-enriquecer-productagro)
   - 5.6 [P1-06: Mejoras ComercioConecta (bulk import, IA, preview)](#56-p1-06-mejoras-comercioconecta)
6. [Sprint P2 — Clase Mundial: Maximizan Conversion y Retencion (Semanas 5-7)](#6-sprint-p2--clase-mundial)
   - 6.1 [P2-01: Password strength indicator visual](#61-p2-01-password-strength-indicator)
   - 6.2 [P2-02: Pre-fill billing desde datos de registro](#62-p2-02-pre-fill-billing)
   - 6.3 [P2-03: Email verification con magic link](#63-p2-03-email-verification)
   - 6.4 [P2-04: Trial expiration notifications (3d, 1d, expirado)](#64-p2-04-trial-expiration-notifications)
   - 6.5 [P2-05: Social login (Google OAuth)](#65-p2-05-social-login)
   - 6.6 [P2-06: Checkout confirmation con recibo real](#66-p2-06-checkout-confirmation)
   - 6.7 [P2-07: Security badges en checkout (SSL, Stripe, PCI)](#67-p2-07-security-badges)
   - 6.8 [P2-08: Analytics instructor en LMS](#68-p2-08-analytics-instructor)
7. [Sprint P3 — Excelencia: Diferenciacion Competitiva (Semanas 8-9)](#7-sprint-p3--excelencia)
   - 7.1 [P3-01: Onboarding wizard multi-step animado](#71-p3-01-onboarding-wizard)
   - 7.2 [P3-02: Schema.org Organization en registro](#72-p3-02-schemaorg-organization)
   - 7.3 [P3-03: Accesibilidad WCAG 2.1 AA completa](#73-p3-03-accesibilidad-wcag)
   - 7.4 [P3-04: prefers-reduced-motion en confetti y animaciones](#74-p3-04-reduced-motion)
8. [Especificaciones Tecnicas Detalladas](#8-especificaciones-tecnicas-detalladas)
   - 8.1 [Library CSS onboarding: diagnostico y solucion](#81-library-css-onboarding)
   - 8.2 [Checkout Stripe real: integracion StripeConnectService](#82-checkout-stripe-real)
   - 8.3 [Template agro-producer-products: patron merchant-products](#83-template-agro-producer-products)
   - 8.4 [InstructorDashboardController: arquitectura completa](#84-instructordashboardcontroller)
   - 8.5 [VerticalOnboardingConfig: ConfigEntity para beneficios/next steps](#85-verticalonboardingconfig)
   - 8.6 [SCSS: tokens y componentes nuevos](#86-scss-tokens-y-componentes)
   - 8.7 [JavaScript: interacciones de checkout y dashboard](#87-javascript-interacciones)
   - 8.8 [Templates Twig: parciales reutilizables](#88-templates-twig-parciales)
   - 8.9 [Hook updates requeridos](#89-hook-updates-requeridos)
   - 8.10 [Permisos y roles](#810-permisos-y-roles)
9. [Tabla de Archivos Creados/Modificados](#9-tabla-de-archivos)
10. [Tabla de Correspondencia con Especificaciones Tecnicas](#10-correspondencia-especificaciones)
11. [Tabla de Cumplimiento de Directrices](#11-cumplimiento-directrices)
12. [Verificacion RUNTIME-VERIFY-001](#12-verificacion-runtime)
13. [Plan de Testing](#13-plan-de-testing)
14. [Coherencia con Documentacion Tecnica Existente](#14-coherencia-documentacion)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Remediacion completa de **32 gaps** identificados en la auditoria exhaustiva del flujo de onboarding, checkout marketplace, catalogos de producto y catalogo de cursos de Jaraba Impact Platform. Los gaps abarcan:

- **5 BLOQUEADORES** (impiden operacion): CSS de onboarding no se carga, checkout marketplace simula pagos, template de productos de productor no existe, interfaz de instructor LMS no existe, URLs hardcoded violan ROUTE-LANGPREFIX-001.
- **6 ALTOS** (degradan conversion en >30%): Beneficios de onboarding hardcoded para AgroConecta, Stripe Connect no auto-invocado, wizard desconectado del flujo, dashboard sin Chart.js, ProductAgro sin trazabilidad, ComercioConecta sin bulk import.
- **8 MEDIOS** (reducen calidad percibida): Password strength, pre-fill billing, email verification, trial expiration, social login, checkout confirmation, security badges, analytics instructor.
- **4 EXCELENCIA** (diferenciacion competitiva): Wizard animado, Schema.org, WCAG 2.1 AA, prefers-reduced-motion.

El alcance impacta **4 verticales** directamente (ComercioConecta, AgroConecta, Formacion, Demo) y **6 indirectamente** (todos los que usan el flujo de onboarding comun).

### 1.2 Por que se implementa

La auditoria de 2026-03-05 revelo una brecha sistematica entre **"el codigo existe"** y **"el usuario lo experimenta"**:

| Componente | Codigo | Runtime | Consecuencia |
|------------|--------|---------|-------------|
| CSS onboarding | 33 reglas en main.css | Library carga ecosistema-jaraba-core.css (sin esos estilos) | **4 paginas de registro SIN estilos** — conversion cercana a 0% |
| Checkout marketplace | CheckoutService + StripePaymentRetailService | Genera `pi_simulated_*` — 0 pagos reales | **0 ventas posibles** en ComercioConecta y AgroConecta |
| Productos AgroConecta | ProducerPortalController::products() implementado | Template `agro-producer-products.html.twig` NO existe | **Error 500** al acceder /productor/productos |
| Instructor LMS | CourseManagementService completo | 0 rutas frontend para instructores | **Instructores solo pueden crear cursos via /admin** |
| Onboarding vertical-aware | 10 verticales definidos | Beneficios hardcoded AgroConecta | **9 verticales ven beneficios incorrectos** |
| Stripe Connect merchants | StripeConnectService.startConnectOnboarding() funcional | No se invoca automaticamente | **Merchants no pueden recibir pagos** sin buscar el endpoint manualmente |

Cada gap representa una barrera directa a la conversion, monetizacion o retencion de tenants.

### 1.3 Principios rectores

1. **Revenue primero**: Los gaps que impiden cobros reales (checkout, Stripe Connect) se resuelven antes de cualquier mejora estetica.
2. **Zero-region fidelity**: TODA pagina frontend usa templates limpias sin page.content, con header/footer del tema, parciales reutilizables via `{% include %}`, y body classes via `hook_preprocess_html()`.
3. **Vertical-aware by design**: Ningun texto, icono o CTA debe estar hardcoded para un vertical especifico. Todo configurable via ConfigEntity o theme settings.
4. **Slide-panel pattern**: TODA accion crear/editar/ver en paginas frontend se abre en slide-panel (data-slide-panel) para que el usuario no abandone la pagina.
5. **Traducciones obligatorias**: TODOS los textos de interfaz usan `{% trans %}...{% endtrans %}` (bloque, NO filtro |t). Sin excepciones.
6. **SCSS inyectable**: Los modulos satelite SOLO usan CSS custom properties `var(--ej-*, fallback)`. NUNCA definen variables SCSS propias (SSOT en ecosistema_jaraba_core).
7. **Dart Sass moderno**: `@use` (NO @import), `@use 'sass:color'`, `color-mix()`. package.json obligatorio en todo modulo con SCSS.
8. **Mobile-first**: Layout full-width, breakpoints mobile -> tablet -> desktop, touch targets >= 44px.
9. **Parciales Twig**: Antes de escribir HTML en una pagina, verificar si existe un parcial en `templates/partials/`. Si el componente se reutiliza en 2+ paginas, crear parcial.
10. **Sin Humo**: Se reutiliza la infraestructura existente. ProducerPortalController ya existe — solo falta el template. StripeConnectService ya funciona — solo falta invocarlo en el flujo.

### 1.4 Metricas de impacto

```
+----------------------------------------------------------------------+
|               IMPACTO PROYECTADO POR SPRINT                          |
+----------------------------------------------------------------------+
|                                                                      |
| Sprint | Semanas | Gaps | Revenue Impact     | UX Score Objetivo    |
| -------+---------|------+--------------------|---------------------- |
| P0     |   1-2   |  5   | 0 ventas -> real   | CSS: 5/10 -> 9/10    |
| P1     |   3-4   |  6   | +25% conversion    | Onboarding: 7/10->9  |
| P2     |   5-7   |  8   | +15% retention     | Checkout: 3/10->9/10 |
| P3     |   8-9   |  4   | +5% NPS score      | Global: 9/10->10/10  |
| -------+---------|------+--------------------|---------------------- |
| TOTAL  |   9     | 23   | Clase mundial      | 10/10 en todos       |
|                                                                      |
+----------------------------------------------------------------------+
```

### 1.5 Alcance y exclusiones

**EN ALCANCE:**
- Correccion de library CSS onboarding (ecosistema_jaraba_core.libraries.yml)
- Integracion Stripe real en checkout marketplace (StripeConnectService::createDestinationCharge)
- Conversion de URLs hardcoded a `path()` / `Url::fromRoute()` en onboarding y checkout
- Template `agro-producer-products.html.twig` con patron merchant-products establecido
- InstructorDashboardController con rutas `/mis-cursos`, `/mis-cursos/{id}/lecciones`, `/mis-cursos/{id}/alumnos`
- VerticalOnboardingConfig (ConfigEntity) para beneficios y next steps por vertical
- Auto-prompt Stripe Connect tras onboarding de verticales marketplace
- Dashboard charts con Chart.js via dependencia canonica
- Enriquecimiento ProductAgro (multi-imagen, variaciones peso/formato, trazabilidad)
- Mejoras ComercioConecta (bulk CSV, boton IA, preview)
- Password strength, pre-fill billing, email verification, trial notifications
- Checkout confirmation con recibo, security badges
- Analytics instructor LMS
- WCAG 2.1 AA, Schema.org, reduced motion

**FUERA DE ALCANCE (planes futuros):**
- Social login Google OAuth (requiere credenciales Google Cloud — plan P2-05 se documenta pero no se implementa hasta configuracion)
- Checkout con Apple Pay / Google Pay (requiere Stripe Payment Request API activation)
- Multi-currency checkout
- Video lessons inline en instructor dashboard (depende de media hosting)
- Certificados personalizados por instructor (depende de OpenBadges extension)

### 1.6 Filosofia "Sin Humo"

La infraestructura critica **YA EXISTE** en la mayoria de los casos. Este plan NO crea sistemas desde cero. Conecta, completa y pule:

| Lo que YA existe | Lo que FALTA |
|------------------|-------------|
| `ProducerPortalController::products()` con query, filtros, render array | El archivo `agro-producer-products.html.twig` (0 lineas) |
| `StripeConnectService::createDestinationCharge()` funcional | Invocacion desde `CheckoutController::processPayment()` |
| `StripeConnectService::startConnectOnboarding()` funcional | Redirect automatico tras onboarding de merchants |
| 33 reglas CSS onboarding en `main.css` | 1 linea en `libraries.yml` que incluya `main.css` |
| `CourseManagementService` con CRUD completo | `InstructorDashboardController` + 3 templates |
| `ecosistema_jaraba_core/chartjs` library canonical | `drupalSettings` con datos reales del tenant |
| `comercio-merchant-products.html.twig` — patron perfecto | Replicar patron para `agro-producer-products.html.twig` |
| `MerchantDashboardService` con KPIs | Equivalente `ProducerDashboardService` ya existe con `getProducerKpis()` |
| `data-slide-panel` behavior global en theme | Añadir atributos `data-slide-panel-*` a botones en templates nuevos |
| `jaraba_icon()` Twig function implementada | Usar en templates nuevos con variantes duotone y colores de paleta |

**El esfuerzo es de conexion y completado, no de construccion.**

### 1.7 Relacion con auditorias previas

| Documento fuente | Codigo | Gaps tratados aqui |
|------------------|--------|--------------------|
| Auditoria Flujo Onboarding Checkout Clase Mundial v1.1 | 2026-03-05 | P0-01 a P3-04 (todos) |
| Auditoria Gaps Billing Commerce Fiscal v1 | 2026-03-05 | Checkout Stripe (complemento a GAP-C03) |
| Directrices v110.0.0 | — | ROUTE-LANGPREFIX-001, CSS-VAR-ALL-COLORS-001, PREMIUM-FORMS-PATTERN-001 |
| Arquitectura Theming v2.1 | 2026-02-05 | SCSS compilation, 5-layer tokens, injectable pattern |
| Vertical Customization Patterns v3.1 | 2026-03-05 | ADDON-VERTICAL-001, VerticalOnboardingConfig |

---

## 2. Diagnostico: Mapa de Gaps

### 2.1 Matriz "El Codigo Existe" vs "El Usuario Lo Experimenta"

| # | Componente | Score Codigo | Score UX | Gap | Severidad |
|---|------------|-------------|----------|-----|-----------|
| 1 | CSS onboarding (library) | 9/10 | 0/10 | Library carga archivo incorrecto | BLOQUEADOR |
| 2 | Checkout marketplace (Stripe) | 6/10 | 0/10 | Pagos simulados, sin Stripe.js | BLOQUEADOR |
| 3 | URLs onboarding/checkout | 8/10 | 5/10 | Hardcoded sin path(), violan ROUTE-LANGPREFIX-001 | BLOQUEADOR |
| 4 | AgroConecta productos frontend | 7/10 | 0/10 | Template .twig no existe, error 500 | BLOQUEADOR |
| 5 | LMS instructor frontend | 8/10 | 0/10 | 0 rutas de instructor, solo admin | BLOQUEADOR |
| 6 | Onboarding vertical-awareness | 7/10 | 2/10 | Beneficios hardcoded AgroConecta | ALTO |
| 7 | Stripe Connect auto-prompt | 8/10 | 1/10 | Existe pero no se invoca automaticamente | ALTO |
| 8 | Onboarding wizard | 7/10 | 0/10 | Template completo, no conectado al flujo | ALTO |
| 9 | Dashboard charts | 6/10 | 1/10 | Canvas HTML vacio, sin Chart.js data | ALTO |
| 10 | ProductAgro campos | 5/10 | 5/10 | 16 campos vs 27 de ProductRetail, sin trazabilidad | ALTO |
| 11 | ComercioConecta merchant tools | 7/10 | 7/10 | Funcional pero sin bulk, IA, preview | ALTO |
| 12 | Password strength | 0/10 | 0/10 | No implementado | MEDIO |
| 13 | Pre-fill billing | 0/10 | 0/10 | No implementado | MEDIO |
| 14 | Email verification | 0/10 | 0/10 | No implementado | MEDIO |
| 15 | Trial notifications | 3/10 | 0/10 | ECA rule existe pero no conectada | MEDIO |
| 16 | Checkout confirmation | 3/10 | 2/10 | Placeholder sin recibo ni detalles | MEDIO |
| 17 | Security badges checkout | 0/10 | 0/10 | Sin indicadores de confianza | MEDIO |
| 18 | LMS instructor analytics | 0/10 | 0/10 | No implementado | MEDIO |
| 19 | Social login | 0/10 | 0/10 | No implementado | MEDIO |
| 20 | Onboarding wizard animado | 3/10 | 0/10 | Template existe sin conexion | EXCELENCIA |
| 21 | Schema.org en registro | 0/10 | 0/10 | No implementado | EXCELENCIA |
| 22 | WCAG 2.1 AA completo | 6/10 | 6/10 | Parcial — falta en nuevos templates | EXCELENCIA |
| 23 | Reduced motion | 2/10 | 2/10 | Solo confetti, no global | EXCELENCIA |

### 2.2 Dependencias entre gaps

```
P0-01 (CSS library) ─────┐
                          ├── P1-01 (vertical-awareness) ── P3-01 (wizard animado)
P0-03 (URLs path()) ──────┘

P0-02 (checkout Stripe) ── P1-02 (Stripe Connect auto) ── P2-06 (confirmation)
                                                          └── P2-07 (security badges)

P0-04 (agro template) ── P1-05 (enriquecer ProductAgro)

P0-05 (instructor LMS) ── P2-08 (analytics instructor)

P1-04 (charts) ─── (independiente)
P1-06 (ComercioConecta mejoras) ─── (independiente)

P2-01 a P2-05 ─── (independientes entre si)

P3-02 a P3-04 ─── (independientes, dependen de templates de P0/P1)
```

### 2.3 Riesgos de no remediar

| Gap | Riesgo inmediato | Riesgo a 6 meses | Impacto financiero |
|-----|-----------------|-------------------|-------------------|
| CSS onboarding | 0% conversion en registro | Reputacion de producto amateur | EUR 0 revenue |
| Checkout simulado | 0 ventas marketplace | ComercioConecta/AgroConecta inutilizables | EUR 0 GMV |
| Agro template falta | Error 500 para productores | Abandono vertical completo | -100% agro tenants |
| Instructor LMS | Solo admin puede crear cursos | Vertical Formacion inviable para no-tecnicos | -100% formacion tenants |
| Vertical-awareness | Confusion de usuarios en 9/10 verticales | NPS negativo, churn elevado | -20% conversion |
| Stripe Connect auto | Merchants no reciben pagos | Abandono post-onboarding | -40% merchant retention |

### 2.4 Arquitectura Stripe

La plataforma opera con **UNA sola cuenta Stripe principal**. Los tenants NO tienen cuentas Stripe independientes:

| Rol | Tipo Stripe | ID almacenado | Creado por |
|-----|-------------|---------------|------------|
| Plataforma SaaS | Account (principal) | — | Configuracion manual |
| Tenant suscriptor | Customer | `stripe_customer_id` | `StripeController::createSubscription()` |
| Tenant vendedor marketplace | Connect Express | `stripe_connect_id` | `StripeConnectService::startConnectOnboarding()` |

Flujo de pagos marketplace (Destination Charges):
```
Consumidor paga 100 EUR -> Cuenta Stripe SaaS -> 90 EUR -> Cuenta Connect del Merchant
                                               -> 10 EUR -> Comision plataforma (configurable)
```

---

## 3. Arquitectura de la Solucion

### 3.1 Flujo completo onboarding end-to-end (estado objetivo)

```
[1. Registro]                [2. Plan]              [3. Pago]              [4. Bienvenida]
/registro/{vertical}    ->  /onboarding/plan   ->  /onboarding/pago   ->  /onboarding/bienvenida
                                                                           |
CSS: onboarding library     CSS: onboarding        CSS: onboarding+stripe  |
     con main.css                                                          |
                                                                           v
Beneficios DINAMICOS        Plans DESDE config     Stripe Elements REAL   Next steps DINAMICOS
por vertical (Config)       entity SaasPlan        3D Secure              por vertical (Config)
                                                                           |
                                                                           v
                                                                    [5. Auto-prompt]
                                                                    Si vertical = marketplace:
                                                                      Redirect -> Stripe Connect
                                                                    Si vertical = formacion:
                                                                      Redirect -> /mis-cursos
                                                                    Default:
                                                                      Redirect -> /mi-cuenta
```

**Cambios requeridos:**
- Library `onboarding` incluye `main.css` (o estilos movidos a `ecosistema-jaraba-core.css`)
- `OnboardingController::getNextSteps()` lee de `VerticalOnboardingConfig` ConfigEntity
- Register template lee `benefits` de variables Twig inyectadas en preprocess
- Bienvenida redirige a Stripe Connect si vertical.connect_required = TRUE

### 3.2 Flujo completo checkout marketplace (estado objetivo)

```
[1. Carrito]                 [2. Checkout]           [3. Pago Stripe]      [4. Confirmacion]
/carrito                ->  /checkout            ->  Stripe.js client  ->  /checkout/confirmacion/{order_id}
                                                     |
                                                     v
Template con:               Template con:            confirmPayment()     Template con:
- Items editables           - Stripe Elements        con Connect dest.    - Numero de pedido
- Coupon field              - Card Element           - 3D Secure          - Recibo detallado
- Subtotal/shipping         - Security badges        - Destination charge - Email de confirmacion
- CTA "Ir a pago"          - CTA "Pagar X EUR"     - Comision auto      - Timeline de entrega
                                                                          - Enlace a /mis-pedidos
```

**Cambios requeridos:**
- `checkout.js` carga Stripe.js, monta Card Element, usa `stripe.confirmPayment()`
- `comercio-checkout.html.twig` incluye `<div id="card-element">` y badges de seguridad
- `CheckoutController::processPayment()` llama a `StripeConnectService::createDestinationCharge()`
- `comercio-checkout-confirmation.html.twig` muestra recibo real (no placeholder)
- TODAS las URLs via `path()` (no hardcoded)

### 3.3 Flujo completo catalogo de productos por vertical (estado objetivo)

**ComercioConecta** (ya funcional, mejoras incrementales):
```
/mi-comercio/productos
  - Tabla con filtros, busqueda, paginacion
  - "Anadir producto" -> slide-panel con form entity
  - "Editar" -> slide-panel con form entity
  - NUEVO: "Importar CSV" -> slide-panel con uploader
  - NUEVO: "Generar descripcion IA" boton en form
  - NUEVO: "Preview" boton antes de publicar
```

**AgroConecta** (template faltante + enriquecimiento entity):
```
/productor/productos
  - Tabla con filtros (activo/borrador/sin stock/por temporada)
  - "Anadir producto" -> slide-panel con form entity (PremiumEntityFormBase)
  - "Editar" -> slide-panel
  - Campos nuevos: multi-imagen (hasta 5), variaciones peso/formato,
    campos de trazabilidad (lote, certificacion, origen DOP/IGP),
    QR code vinculado, temporada/estacionalidad
  - Empty state con icono duotone + CTA
```

**Patron de template unificado** — ambos siguen el patron de `comercio-merchant-products.html.twig`:
- Header con titulo, conteo, boton "Anadir" con `data-slide-panel`
- Filtros rapidos como tabs
- Tabla responsiva con columnas contextuales
- Empty state guiado con `jaraba_icon()` duotone
- Todas las acciones via `data-slide-panel` (usuario no abandona la pagina)
- Textos con `{% trans %}...{% endtrans %}`

### 3.4 Flujo completo catalogo de cursos para instructor (estado objetivo)

```
/mis-cursos
  - Dashboard: KPIs (total cursos, total alumnos, rating medio, completions)
  - Lista de cursos con stats (alumnos, rating, % completado)
  - "Crear curso" -> slide-panel con form PremiumEntityFormBase
  - "Editar" -> slide-panel
  - "Ver alumnos" -> /mis-cursos/{id}/alumnos (listado con progreso)
  - "Ver lecciones" -> /mis-cursos/{id}/lecciones (order drag-drop)
  - "Anadir leccion" -> slide-panel con form
  - "Analytics" -> /mis-cursos/{id}/analytics (engagement, completion rates)
```

**Controlador:** `InstructorDashboardController` (nuevo) con DI:
- `CourseManagementService` (ya existe)
- `TenantContextService` (ya existe)
- `EntityTypeManagerInterface`

**Rutas:**
- `jaraba_lms.instructor.dashboard` -> `/mis-cursos`
- `jaraba_lms.instructor.course_lessons` -> `/mis-cursos/{lms_course}/lecciones`
- `jaraba_lms.instructor.course_students` -> `/mis-cursos/{lms_course}/alumnos`
- `jaraba_lms.instructor.course_analytics` -> `/mis-cursos/{lms_course}/analytics`

**Templates:**
- `lms-instructor-dashboard.html.twig` (lista de cursos + KPIs)
- `lms-instructor-lessons.html.twig` (gestion de lecciones de un curso)
- `lms-instructor-students.html.twig` (alumnos con progreso)

**Permisos:** Nuevo permiso `manage own courses` — permite CRUD solo de cursos propios.

### 3.5 Modelo de vertical-awareness configurable

**Nueva ConfigEntity:** `VerticalOnboardingConfig` (`ecosistema_jaraba_core`)

```yaml
# Ejemplo: config/install/ecosistema_jaraba_core.vertical_onboarding.comercioconecta.yml
langcode: es
id: comercioconecta
label: 'ComercioConecta'
vertical_id: comercioconecta
benefits:
  - icon: 'commerce/shopping-bag'
    text: 'Marketplace local para tu barrio'
  - icon: 'commerce/credit-card'
    text: 'Cobros con Stripe integrado'
  - icon: 'ui/chart-line'
    text: 'Analytics de ventas en tiempo real'
next_steps:
  - title: 'Configura tu perfil de comercio'
    description: 'Anade tu logo, horarios y datos de contacto'
    url_route: 'jaraba_comercio_conecta.merchant.profile'
    icon: 'ui/user-circle'
  - title: 'Anade tu primer producto'
    description: 'Crea tu catalogo y empieza a vender'
    url_route: 'jaraba_comercio_conecta.merchant.product_add'
    icon: 'commerce/package'
  - title: 'Conecta tu cuenta bancaria'
    description: 'Configura Stripe Connect para recibir pagos'
    url_route: 'ecosistema_jaraba_core.stripe.connect_start'
    icon: 'ui/wallet-duotone'
cta_text: 'Empieza a vender'
connect_required: true
post_onboarding_redirect_route: 'jaraba_comercio_conecta.merchant.dashboard'
```

**Consumo:**
- `OnboardingController::registerForm()` lee `VerticalOnboardingConfig::load($vertical_key)` y pasa `benefits` a Twig
- `OnboardingController::getNextSteps()` lee `next_steps` de la config
- `OnboardingController::welcome()` verifica `connect_required` para redirect automatico a Stripe Connect

### 3.6 Patron de CSS Library unificado

**Diagnostico del gap:**

La library `onboarding` en `ecosistema_jaraba_core.libraries.yml` (linea 43-54) declara:
```yaml
onboarding:
  css:
    theme:
      css/ecosistema-jaraba-core.css: {}
```

Pero los estilos de onboarding (`.ej-register-*`, `.ej-setup-payment-*`, `.ej-onboarding-progress-*`) estan en `css/main.css` (lineas 1331+), NO en `ecosistema-jaraba-core.css`.

**Solucion elegida:** Incluir `css/main.css` como segundo archivo CSS en la library `onboarding`:

```yaml
onboarding:
  version: 1.x
  css:
    theme:
      css/ecosistema-jaraba-core.css: {}
      css/main.css: {}
  js:
    js/ecosistema-jaraba-onboarding.js: {}
  dependencies:
    - core/drupal
    - core/drupal.ajax
    - core/jquery
    - core/once
```

**Alternativa descartada:** Mover estilos de `main.css` a `ecosistema-jaraba-core.css`. Descartada porque `main.css` contiene estilos de multiples features (dashboard, settings, etc.) y extraer solo los de onboarding implicaria riesgo de regresion en otras paginas.

**SCSS-COMPILE-VERIFY-001:** Tras el cambio, verificar que `main.css` existe y su timestamp es posterior al ultimo SCSS modificado. El archivo actual tiene fecha 2026-02-26, lo cual es correcto si no ha habido cambios SCSS posteriores al modulo core.

### 3.7 Diagrama de componentes y relaciones

```
+----------------------------+     +-----------------------------+
| ecosistema_jaraba_core     |     | ecosistema_jaraba_theme     |
|                            |     |                             |
| OnboardingController  -----+---->| page--registro.html.twig    |
| StripeController      -----+---->| page--onboarding-*.html.twig|
| VerticalOnboardingConfig   |     |                             |
| css/main.css (onboarding)  |     | templates/partials/         |
| css/ecosistema-jaraba-*.css|     |   _header-classic.html.twig |
| js/ecosistema-jaraba-*.js  |     |   _footer.html.twig         |
+----------------------------+     |   _empty-state.html.twig    |
                                   |   _security-badges.html.twig|
+----------------------------+     +-----------------------------+
| jaraba_comercio_conecta    |
|                            |     +-----------------------------+
| MerchantPortalController   |     | jaraba_agroconecta_core     |
| CheckoutController    -----+---->|                             |
| StripePaymentRetailService |     | ProducerPortalController    |
| checkout.js (REWRITE)      |     | ProducerDashboardService    |
| comercio-checkout.html.twig|     | agro-producer-products.twig |
| comercio-merchant-*.twig   |     |   (NUEVO — patron merchant) |
+----------------------------+     +-----------------------------+

+----------------------------+
| jaraba_lms                 |
|                            |
| InstructorDashboardCtrl    | <-- NUEVO
| CourseManagementService    | <-- YA EXISTE
| lms-instructor-*.html.twig | <-- NUEVOS (3 templates)
+----------------------------+
```

---

## 4. Sprint P0 — Bloqueadores: Impiden Operacion (Semanas 1-2)

### 4.1 P0-01: Corregir library CSS de onboarding

**Problema:** La library `onboarding` (ecosistema_jaraba_core.libraries.yml:43) carga `css/ecosistema-jaraba-core.css` pero los 33 selectores de onboarding (`.ej-register-*`, `.ej-setup-payment-*`, `.ej-onboarding-progress-*`) residen en `css/main.css` (lineas 1331+). Las 4 paginas del flujo de registro se renderizan SIN estilos.

**Solucion:**

1. **Modificar `ecosistema_jaraba_core.libraries.yml`** — Anadir `css/main.css` a la library `onboarding`:

```yaml
onboarding:
  version: 1.x
  css:
    theme:
      css/ecosistema-jaraba-core.css: {}
      css/main.css: {}
  js:
    js/ecosistema-jaraba-onboarding.js: {}
  dependencies:
    - core/drupal
    - core/drupal.ajax
    - core/jquery
    - core/once
```

2. **Verificar que la library `stripe` hereda correctamente** — La library `stripe` declara `ecosistema_jaraba_core/onboarding` como dependencia (linea 85), por lo que automaticamente cargara tambien `main.css`.

3. **SCSS-COMPILE-VERIFY-001:** Verificar timestamp `css/main.css` > ultimo SCSS modificado en `scss/`.

**Archivos modificados:** 1
- `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.libraries.yml`

**Verificacion:**
- Navegar a `/registro/comercioconecta` — debe mostrar layout con estilos
- Navegar a `/onboarding/configurar-pago` — debe mostrar Stripe Elements con formato
- Inspector: verificar que `main.css` se carga en `<head>`

**Directrices cumplidas:** SCSS-COMPILE-VERIFY-001, CSS-VAR-ALL-COLORS-001

**Esfuerzo:** 0.5h

---

### 4.2 P0-02: Checkout marketplace con Stripe real

**Problema:** `checkout.js` envia `fetch('/checkout/procesar')` con `payment_method: 'stripe'` generico, NO carga Stripe.js, NO usa `stripe.confirmPayment()`. `StripePaymentRetailService` genera IDs simulados (`pi_simulated_*`). El servicio `StripeConnectService::createDestinationCharge()` existe y funciona pero NO se invoca.

**Solucion completa en 4 capas:**

#### Capa 1: Template `comercio-checkout.html.twig` — Anadir Stripe Elements

Modificar el template para incluir:
- Contenedor `<div id="card-element"></div>` para Stripe Elements
- Mensajes de error `<div id="card-errors" role="alert"></div>`
- Parcial `{% include '@ecosistema_jaraba_theme/partials/_security-badges.html.twig' %}` (NUEVO parcial reutilizable)
- Textos con `{% trans %}...{% endtrans %}`
- URLs via `{{ path('ruta') }}` (no hardcoded)

#### Capa 2: JavaScript `checkout.js` — Reescribir con Stripe.js

```javascript
// Pseudocodigo de la logica objetivo
(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.comercioCheckout = {
    attach: function (context) {
      // 1. Inicializar Stripe con publishable key desde drupalSettings
      const stripe = Stripe(drupalSettings.comercioConecta.stripePublicKey);
      const elements = stripe.elements();

      // 2. Montar Card Element
      const cardElement = elements.create('card', { style: { ... } });
      cardElement.mount('#card-element');

      // 3. Validacion en tiempo real
      cardElement.on('change', function(event) { ... });

      // 4. Submit: createPaymentMethod -> fetch server -> confirmPayment
      payButton.addEventListener('click', async function() {
        const { paymentMethod } = await stripe.createPaymentMethod({
          type: 'card', card: cardElement
        });
        // Enviar payment_method.id al server via fetch con CSRF token
        const response = await fetch(processUrl, { ... });
        const data = await response.json();

        if (data.requires_action) {
          // 3D Secure
          const { error } = await stripe.confirmCardPayment(data.client_secret);
        }
        // Redirect a confirmacion
        window.location.href = data.redirect_url;
      });
    }
  };
})(Drupal, drupalSettings);
```

#### Capa 3: `CheckoutController::processPayment()` — Integrar Stripe real

```php
// Pseudocodigo de la logica objetivo
public function processPayment(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $paymentMethodId = $data['payment_method_id'];

    // Resolver merchant y su Connect account
    $merchant = $this->merchantService->getMerchantForOrder($orderId);
    $connectAccountId = $merchant->get('stripe_connect_id')->value;

    // Crear destination charge via StripeConnectService
    $result = $this->stripeConnectService->createDestinationCharge([
        'amount' => $totalInCents,
        'currency' => 'eur',
        'payment_method' => $paymentMethodId,
        'confirm' => TRUE,
        'destination' => $connectAccountId,
        'application_fee_amount' => $commissionInCents,
    ]);

    // Si requiere 3D Secure
    if ($result['status'] === 'requires_action') {
        return new JsonResponse([
            'requires_action' => TRUE,
            'client_secret' => $result['client_secret'],
        ]);
    }

    // Pago exitoso -> actualizar orden
    $this->checkoutService->completeOrder($orderId, $result['payment_intent_id']);

    return new JsonResponse([
        'success' => TRUE,
        'redirect_url' => Url::fromRoute('jaraba_comercio_conecta.checkout.confirmation', [
            'order_id' => $orderId
        ])->toString(),
    ]);
}
```

#### Capa 4: `comercio-checkout-confirmation.html.twig` — Recibo real

Template con:
- Numero de pedido, fecha, estado
- Detalle de items comprados con precios
- Informacion de envio
- Resumen de pago (subtotal, descuento, envio, total)
- "Se ha enviado un email de confirmacion a {email}"
- Enlace a `/mis-pedidos` via `{{ path('jaraba_comercio_conecta.customer.orders') }}`

**Archivos modificados:** 4
- `web/modules/custom/jaraba_comercio_conecta/templates/comercio-checkout.html.twig`
- `web/modules/custom/jaraba_comercio_conecta/js/checkout.js`
- `web/modules/custom/jaraba_comercio_conecta/src/Controller/CheckoutController.php`
- `web/modules/custom/jaraba_comercio_conecta/templates/comercio-checkout-confirmation.html.twig`

**Archivos creados:** 2
- `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_security-badges.html.twig` (parcial reutilizable)
- `web/modules/custom/jaraba_comercio_conecta/jaraba_comercio_conecta.libraries.yml` (anadir Stripe.js a library checkout)

**drupalSettings necesarios** (inyectados en preprocess o controller):
- `comercioConecta.stripePublicKey` — desde `getenv('STRIPE_PUBLIC_KEY')`
- `comercioConecta.processUrl` — `Url::fromRoute('jaraba_comercio_conecta.checkout.process')->toString()`
- `comercioConecta.csrfToken` — token CSRF

**Directrices cumplidas:** ROUTE-LANGPREFIX-001, STRIPE-ENV-UNIFY-001, CSRF-API-001, CSRF-JS-CACHE-001, INNERHTML-XSS-001, ZERO-REGION-003

**Esfuerzo:** 16h

---

### 4.3 P0-03: URLs hardcoded a path() en onboarding y checkout

**Problema:** Multiples archivos usan URLs hardcoded en lugar de `Url::fromRoute()` / `{{ path() }}`, violando ROUTE-LANGPREFIX-001. El sitio usa `/es/` prefix — paths hardcoded causan 404.

**Inventario de URLs hardcoded:**

| Archivo | Linea | URL hardcoded | Ruta Drupal correcta |
|---------|-------|---------------|---------------------|
| OnboardingController.php | 383 | `/admin/people/invite` | `Url::fromRoute('entity.user.collection')` |
| OnboardingController.php | 390 | `/admin/content/product` | `Url::fromRoute('entity.product_agro.collection')` |
| OnboardingController.php | 397 | `/node/add/lote_produccion` | `Url::fromRoute('node.add', ['node_type' => 'lote_produccion'])` |
| register-form.html.twig | 228 | `/user/login` | `{{ path('user.login') }}` |
| ecosistema-jaraba-onboarding.js | 165 | `/registro/procesar` | `drupalSettings.ecosistemaJaraba.processUrl` |
| ecosistema-jaraba-stripe.js | 249 | `/api/v1/stripe/create-subscription` | `drupalSettings.ecosistemaJaraba.createSubscriptionUrl` |
| ecosistema-jaraba-stripe.js | 279 | `/api/v1/stripe/confirm-subscription` | `drupalSettings.ecosistemaJaraba.confirmSubscriptionUrl` |
| ecosistema-jaraba-stripe.js | 296 | `/onboarding/bienvenida` | `drupalSettings.ecosistemaJaraba.welcomeUrl` |
| checkout.js | 135 | `/checkout/procesar` | **RUTA INCORRECTA** — routing.yml define `/comercio-local/checkout/payment` — produce 404 |
| checkout.js | 149 | `/checkout/confirmacion/{id}` | **RUTA INCORRECTA** — routing.yml define `/comercio-local/checkout/confirmacion/{id}` — produce 404 |
| checkout.js | 84 | `/api/v1/comercio/cart/coupon` | `drupalSettings.comercioConecta.couponUrl` |
| checkout.js | 177 | `/api/v1/comercio/cart/update/{itemId}` | `drupalSettings.comercioConecta.updateCartUrl` |
| comercio-checkout.html.twig | 15 | `/comercio-local` | `{{ path('jaraba_comercio_conecta.marketplace') }}` |
| comercio-checkout-confirmation.html.twig | 38-39 | `/mis-pedidos`, `/comercio-local` | `{{ path(...) }}` |

**Solucion:**
- PHP: Reemplazar strings por `Url::fromRoute()->toString()`
- Twig: Reemplazar por `{{ path('route_name') }}`
- JS: Leer de `drupalSettings` (inyectados en preprocess/controller)

**Archivos modificados:** 4
- `web/modules/custom/ecosistema_jaraba_core/src/Controller/OnboardingController.php`
- `web/modules/custom/ecosistema_jaraba_core/templates/ecosistema-jaraba-register-form.html.twig`
- `web/modules/custom/jaraba_comercio_conecta/js/checkout.js`
- `web/modules/custom/jaraba_comercio_conecta/templates/comercio-checkout.html.twig`

**Directrices cumplidas:** ROUTE-LANGPREFIX-001

**Esfuerzo:** 3h

---

### 4.4 P0-04: Template agro-producer-products.html.twig (FALTA)

**Problema:** `ProducerPortalController::products()` (linea 265-272) retorna `#theme => 'agro_producer_products'`. El hook_theme en `jaraba_agroconecta_core.module` (linea 93-98) registra el template como `agro-producer-products`. **Pero el archivo `agro-producer-products.html.twig` NO EXISTE** en la carpeta de templates. Resultado: error de renderizacion al acceder `/productor/productos`.

**Solucion:** Crear el template siguiendo el patron establecido en `comercio-merchant-products.html.twig` (130 lineas, slide-panel CRUD, filtros, empty state).

**Estructura del template:**

```twig
{# agro-producer-products.html.twig #}
{# Patron identico a comercio-merchant-products pero adaptado a ProductAgro #}

<div class="agro-producer-products">
  <header class="agro-producer-products__header container">
    {# Titulo "Mis Productos", conteo, boton "Anadir" con data-slide-panel #}
  </header>

  <div class="agro-producer-products__filters container">
    {# Tabs: Todos | Activos | Borradores | Sin Stock | Por temporada #}
  </div>

  {% if products|length > 0 %}
    <div class="agro-table-responsive container">
      <table class="agro-table agro-table--products">
        {# Columnas: Producto, SKU, Precio/kg, Stock, Origen, Temporada, Estado, Acciones #}
        {# Cada fila con boton "Editar" via data-slide-panel #}
      </table>
    </div>
  {% else %}
    <div class="agro-empty-state container">
      {# jaraba_icon('agriculture', 'seedling', { variant: 'duotone', size: '64px' }) #}
      {# Texto: "No tienes productos todavia" + CTA data-slide-panel #}
    </div>
  {% endif %}
</div>
```

**Textos obligatorios con `{% trans %}`:**
- "Mis Productos", "productos", "Anadir Producto", "Todos", "Activos", "Borradores", "Sin Stock", "Temporada", "Producto", "Precio", "Stock", "Origen", "Estado", "Acciones", "Editar", "No tienes productos todavia", "Anade tu primer producto para empezar a vender.", "Crear mi primer producto"

**Slide-panel pattern:**
- Boton "Anadir": `data-slide-panel="nuevo-producto" data-slide-panel-url="{{ path('entity.product_agro.add_form') }}"`
- Boton "Editar": `data-slide-panel="editar-producto" data-slide-panel-url="{{ path('entity.product_agro.edit_form', {'product_agro': product.id}) }}"`

**Archivos creados:** 1
- `web/modules/custom/jaraba_agroconecta_core/templates/agro-producer-products.html.twig`

**Archivos modificados:** 1 (opcional)
- `web/modules/custom/jaraba_agroconecta_core/jaraba_agroconecta_core.module` — verificar que hook_theme pasa variables correctas

**Directrices cumplidas:** SLIDE-PANEL-RENDER-001, ICON-DUOTONE-001, ICON-COLOR-001, i18n ({% trans %}), ZERO-REGION-001

**Esfuerzo:** 4h

---

### 4.5 P0-05: Frontend instructor LMS (INEXISTENTE)

**Problema:** jaraba_lms tiene 0 rutas frontend para instructores. La creacion de cursos solo es posible via `/admin/content/courses` (admin Drupal). Para un SaaS de clase mundial, esto es inaceptable.

**Lo que YA existe:**
- `CourseManagementService` con CRUD completo de cursos
- Entities: `LmsCourse`, `LmsLesson`, `LmsEnrollment`, `LessonProgress`
- Permiso: `view progress of enrolled learners` (para instructores)
- Concepto de instructor en CourseReview (instructor_rating, instructor_response)

**Lo que se CREA:**

#### 1. InstructorDashboardController (nuevo controller)

```php
class InstructorDashboardController extends ControllerBase {
    // DI: CourseManagementService, TenantContextService, EntityTypeManager

    public function dashboard(): array {
        // Lista de cursos del instructor actual
        // KPIs: total cursos, total alumnos, rating medio, completions
        return ['#theme' => 'lms_instructor_dashboard', ...];
    }

    public function lessons(LmsCourse $lms_course): array {
        // Lista de lecciones del curso con orden, estado, duracion
        // Boton "Anadir leccion" con data-slide-panel
        return ['#theme' => 'lms_instructor_lessons', ...];
    }

    public function students(LmsCourse $lms_course): array {
        // Lista de alumnos matriculados con progreso individual
        return ['#theme' => 'lms_instructor_students', ...];
    }
}
```

#### 2. Rutas (jaraba_lms.routing.yml)

```yaml
jaraba_lms.instructor.dashboard:
  path: '/mis-cursos'
  defaults:
    _controller: '\Drupal\jaraba_lms\Controller\InstructorDashboardController::dashboard'
    _title: 'Mis cursos'
  requirements:
    _permission: 'manage own courses'

jaraba_lms.instructor.course_lessons:
  path: '/mis-cursos/{lms_course}/lecciones'
  defaults:
    _controller: '\Drupal\jaraba_lms\Controller\InstructorDashboardController::lessons'
    _title_callback: '...'
  requirements:
    _permission: 'manage own courses'
  options:
    parameters:
      lms_course:
        type: entity:lms_course

jaraba_lms.instructor.course_students:
  path: '/mis-cursos/{lms_course}/alumnos'
  defaults:
    _controller: '\Drupal\jaraba_lms\Controller\InstructorDashboardController::students'
    _title_callback: '...'
  requirements:
    _permission: 'manage own courses'
  options:
    parameters:
      lms_course:
        type: entity:lms_course
```

#### 3. Templates (3 nuevos)

**`lms-instructor-dashboard.html.twig`:**
- KPIs cards (4 metricas)
- Lista de cursos como cards con thumbnail, titulo, stats (alumnos, rating, lecciones)
- Botones "Lecciones", "Alumnos" con enlaces directos
- Boton "Editar" con `data-slide-panel`
- "Crear curso" con `data-slide-panel`
- Empty state con `jaraba_icon('education', 'book-open', { variant: 'duotone' })`

**`lms-instructor-lessons.html.twig`:**
- Breadcrumb: Mis cursos > {Nombre del curso} > Lecciones
- Tabla de lecciones: orden, titulo, tipo (texto/video/quiz), duracion, estado
- Boton "Anadir leccion" con `data-slide-panel`
- Boton "Editar" por leccion con `data-slide-panel`
- Indicador de orden (drag-drop preparado con `data-sortable`)

**`lms-instructor-students.html.twig`:**
- Breadcrumb: Mis cursos > {Nombre del curso} > Alumnos
- Tabla: nombre, email, progreso (barra visual), lecciones completadas, fecha matricula
- Filtros: todos, en progreso, completado, abandonado
- Export CSV boton

#### 4. Permiso nuevo

```yaml
# jaraba_lms.permissions.yml
manage own courses:
  title: 'Gestionar cursos propios'
  description: 'Permite crear, editar y gestionar cursos de los que el usuario es autor.'
```

#### 5. Paginas page template (zero-region)

Crear `page--mis-cursos.html.twig` en el tema con layout limpio (header + contenido + footer del tema, sin sidebar admin).

Body class `lms-instructor-page` via `hook_preprocess_html()`.

**Archivos creados:** 5
- `web/modules/custom/jaraba_lms/src/Controller/InstructorDashboardController.php`
- `web/modules/custom/jaraba_lms/templates/lms-instructor-dashboard.html.twig`
- `web/modules/custom/jaraba_lms/templates/lms-instructor-lessons.html.twig`
- `web/modules/custom/jaraba_lms/templates/lms-instructor-students.html.twig`
- `web/themes/custom/ecosistema_jaraba_theme/templates/page--mis-cursos.html.twig`

**Archivos modificados:** 4
- `web/modules/custom/jaraba_lms/jaraba_lms.routing.yml` (3 rutas nuevas)
- `web/modules/custom/jaraba_lms/jaraba_lms.permissions.yml` (1 permiso nuevo)
- `web/modules/custom/jaraba_lms/jaraba_lms.module` (hook_theme para 3 templates + preprocess)
- `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` (body class)

**Directrices cumplidas:** ZERO-REGION-001, SLIDE-PANEL-RENDER-001, PREMIUM-FORMS-PATTERN-001 (forms via slide-panel), ROUTE-LANGPREFIX-001, ICON-DUOTONE-001, i18n ({% trans %}), CONTROLLER-READONLY-001

**Esfuerzo:** 24h

---

## 5. Sprint P1 — Alta Friccion: Degradan Conversion (Semanas 3-4)

### 5.1 P1-01: Vertical-awareness en onboarding (beneficios + next steps dinamicos)

**Problema:** `ecosistema-jaraba-register-form.html.twig` tiene beneficios hardcoded para AgroConecta ("Trazabilidad completa de lotes", "Certificados con firma digital", "Codigos QR verificables") en lineas 47-61. `OnboardingController::getNextSteps()` (lineas 369-401) devuelve pasos de "catalogo de productos para trazabilidad" y "registra un lote de produccion". Los 9 verticales restantes ven informacion irrelevante.

**Solucion:**

1. **Crear ConfigEntity `VerticalOnboardingConfig`** (ver seccion 3.5 para schema)
2. **Crear 10 configs de instalacion** (1 por vertical) con beneficios y next steps especificos
3. **Modificar `OnboardingController`:**
   - `registerForm()`: Cargar VerticalOnboardingConfig y pasar `benefits` como variable Twig
   - `getNextSteps()`: Leer de config en vez de hardcoded
   - `welcome()`: Leer `connect_required` y `post_onboarding_redirect_route`
4. **Modificar template `ecosistema-jaraba-register-form.html.twig`:**
   - Reemplazar lista hardcoded por `{% for benefit in benefits %}` con `jaraba_icon(benefit.icon)` y `{% trans %}{{ benefit.text }}{% endtrans %}`

**Archivos creados:** 12
- ConfigEntity: `src/Entity/VerticalOnboardingConfig.php`
- Schema: `config/schema/ecosistema_jaraba_core.schema.yml` (extension)
- 10 configs install: `config/install/ecosistema_jaraba_core.vertical_onboarding.{vertical}.yml`

**Archivos modificados:** 3
- `OnboardingController.php`
- `ecosistema-jaraba-register-form.html.twig`
- `ecosistema-jaraba-welcome.html.twig`

**Directrices cumplidas:** UPDATE-HOOK-REQUIRED-001 (nueva ConfigEntity requiere hook_update), VERTICAL-CANONICAL-001

**Esfuerzo:** 12h

---

### 5.2 P1-02: Auto-prompt Stripe Connect para merchants

**Problema:** `StripeConnectService::startConnectOnboarding()` existe y funciona (`POST /api/v1/stripe/connect/onboard`), pero NO se invoca automaticamente tras el onboarding de ComercioConecta/AgroConecta. Los merchants deben encontrar el endpoint manualmente.

**Solucion:** En `OnboardingController::welcome()`, despues de completar el onboarding:

```php
$verticalConfig = VerticalOnboardingConfig::load($verticalKey);
if ($verticalConfig && $verticalConfig->get('connect_required')) {
    // Mostrar modal/banner prominente: "Para recibir pagos, conecta tu cuenta bancaria"
    // con boton que redirige a Stripe Connect onboarding
    $variables['connect_required'] = TRUE;
    $variables['connect_url'] = Url::fromRoute('ecosistema_jaraba_core.stripe.connect_start')->toString();
}
```

En template `ecosistema-jaraba-welcome.html.twig`, mostrar un banner/card prominente:
```twig
{% if connect_required %}
  <div class="ej-welcome__connect-prompt" role="alert">
    {{ jaraba_icon('ui/wallet-duotone', { size: '48px', color: 'naranja-impulso' }) }}
    <h3>{% trans %}Conecta tu cuenta bancaria{% endtrans %}</h3>
    <p>{% trans %}Para recibir pagos de tus clientes, necesitas configurar tu cuenta Stripe.{% endtrans %}</p>
    <a href="{{ connect_url }}" class="ej-btn ej-btn--primary ej-btn--lg">
      {% trans %}Configurar pagos ahora{% endtrans %}
    </a>
  </div>
{% endif %}
```

**Archivos modificados:** 2
- `OnboardingController.php` (welcome method)
- `ecosistema-jaraba-welcome.html.twig`

**Esfuerzo:** 4h

---

### 5.3 P1-03: Conectar wizard al flujo principal

**Problema:** Existe un template `onboarding-wizard.html.twig` con 7 pasos definidos pero `OnboardingController` va directo al formulario simple sin usarlo.

**Solucion:** Integrar el wizard como barra de progreso visual en todos los pasos del onboarding:
- Paso 1/4: Registro (activo)
- Paso 2/4: Seleccion de plan
- Paso 3/4: Configuracion de pago
- Paso 4/4: Bienvenida (completado)

El wizard NO reemplaza el flujo — se integra como indicador de progreso en cada pagina, mostrando el paso actual y los completados.

**Archivos modificados:** 4 templates de onboarding (incluir parcial de progreso)

**Esfuerzo:** 6h

---

### 5.4 P1-04: Dashboard charts reales con Chart.js

**Problema:** Los canvas de dashboard tenant estan vacios — no se inyectan datos reales via drupalSettings.

**Solucion:** En `TenantSelfServiceController::dashboard()`, inyectar datos reales desde `RevenueMetricsService::getDashboardSnapshot()` en drupalSettings. Si no hay datos, mostrar empty state guiado.

**Archivos modificados:** 2
- `TenantSelfServiceController.php`
- `tenant-self-service-dashboard.html.twig`

**Esfuerzo:** 6h

---

### 5.5 P1-05: Enriquecer ProductAgro (trazabilidad, multi-imagen, variaciones)

**Problema:** ProductAgro tiene 18 campos (incluyendo id/uuid/created/changed) vs 27 de ProductRetail. Carece de la propuesta de valor de AgroConecta: trazabilidad, multi-imagen, variaciones, QR, temporada.

**Campos nuevos a anadir en `baseFieldDefinitions()`:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `images` | image (cardinality: 5) | Galeria multi-imagen |
| `lot_number` | string | Numero de lote de produccion |
| `certification` | list_string | Ecologico, DOP, IGP, Conversion |
| `geographic_origin` | string | Denominacion de origen o zona |
| `harvest_season` | list_string | Primavera, Verano, Otono, Invierno |
| `weight_unit` | list_string | kg, g, unidad, caja, saco |
| `weight_value` | decimal | Peso por unidad |
| `qr_code_url` | string | URL del QR de trazabilidad |
| `short_description` | string | Descripcion corta para listados |

**Archivos modificados:** 1
- `ProductAgro.php` (baseFieldDefinitions)

**Archivos creados:** 1
- `jaraba_agroconecta_core.install` (hook_update para nuevos campos)

**Directrices cumplidas:** UPDATE-HOOK-REQUIRED-001, UPDATE-FIELD-DEF-001 (setName + setTargetEntityTypeId), UPDATE-HOOK-CATCH-001 (\Throwable)

**Esfuerzo:** 8h

---

### 5.6 P1-06: Mejoras ComercioConecta (bulk import, IA, preview)

**Problema:** ComercioConecta merchant portal (7/10) carece de bulk import CSV, boton IA para descripciones, y preview antes de publicar.

**Solucion incremental:**
1. **Bulk CSV import** — Nuevo controller `MerchantImportController` con ruta `/mi-comercio/importar`, form de upload CSV, servicio `ProductImportService` con validacion y batch API
2. **Boton IA en form** — Extender `ProductRetailForm` para incluir boton "Generar descripcion" que llame a `/api/v1/ai/product-description` (endpoint nuevo en `jaraba_ai_agents`)
3. **Preview** — Boton "Vista previa" que abre slide-panel con renderizado del producto en modo publico

**Esfuerzo:** 16h

---

## 6. Sprint P2 — Clase Mundial: Maximizan Conversion y Retencion (Semanas 5-7)

### 6.1 P2-01: Password strength indicator visual

Indicador visual de fortaleza de contrasena con 4 niveles (debil/regular/fuerte/muy fuerte) usando barra de progreso con colores. Implementado en `ecosistema-jaraba-onboarding.js`. CSS: `var(--ej-color-error)`, `var(--ej-color-warning)`, `var(--ej-color-success)`.

**Esfuerzo:** 3h

### 6.2 P2-02: Pre-fill billing desde datos de registro

En `OnboardingController::setupPayment()`, pre-rellenar nombre, email y organizacion del formulario de billing con datos del paso de registro (almacenados en session).

**Esfuerzo:** 2h

### 6.3 P2-03: Email verification con magic link

Tras registro, enviar email con link de verificacion (token unico, expira en 24h). Cuenta queda en estado `pending_verification` hasta que el link se confirma. Template de email con branding del vertical.

**Esfuerzo:** 12h

### 6.4 P2-04: Trial expiration notifications (3d, 1d, expirado)

Tres notificaciones email programadas via `hook_cron()`:
- 3 dias antes: "Tu trial termina pronto — elige un plan"
- 1 dia antes: "Ultimo dia de tu trial"
- Expirado: "Tu trial ha terminado — activa tu suscripcion para no perder tus datos"

**Esfuerzo:** 8h

### 6.5 P2-05: Social login (Google OAuth)

Documentado pero NO implementado hasta obtener credenciales Google Cloud. Requiere modulo `social_auth` + `social_auth_google` de Drupal. Se configura via `settings.secrets.php` (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET).

**Esfuerzo:** 8h (implementacion futura)

### 6.6 P2-06: Checkout confirmation con recibo real

Reescribir `comercio-checkout-confirmation.html.twig` con:
- Numero de pedido, fecha, estado
- Detalle de items con precios
- Informacion de envio
- Resumen de pago
- "Email de confirmacion enviado a {email}"
- Enlace a pedidos

**Esfuerzo:** 4h

### 6.7 P2-07: Security badges en checkout (SSL, Stripe, PCI)

Crear parcial `_security-badges.html.twig` reutilizable:
```twig
<div class="security-badges" aria-label="{% trans %}Garantias de seguridad{% endtrans %}">
  <div class="security-badges__item">
    {{ jaraba_icon('ui/shield-check', { variant: 'duotone', color: 'verde-innovacion' }) }}
    <span>{% trans %}Pago 100% seguro{% endtrans %}</span>
  </div>
  <div class="security-badges__item">
    <img src="{{ stripe_badge_url }}" alt="Powered by Stripe" width="100" loading="lazy">
  </div>
  <div class="security-badges__item">
    {{ jaraba_icon('ui/lock', { variant: 'duotone', color: 'verde-innovacion' }) }}
    <span>{% trans %}Cifrado SSL{% endtrans %}</span>
  </div>
</div>
```

**Esfuerzo:** 3h

### 6.8 P2-08: Analytics instructor en LMS

Ruta `/mis-cursos/{id}/analytics` con:
- Metricas: tasa de completado, tiempo medio por leccion, drop-off point
- Grafico de engagement (Chart.js)
- Tendencia de matriculas (semanal)
- Tabla de lecciones mas vistas vs mas abandonadas

**Esfuerzo:** 12h

---

## 7. Sprint P3 — Excelencia: Diferenciacion Competitiva (Semanas 8-9)

### 7.1 P3-01: Onboarding wizard multi-step animado

Animaciones CSS de transicion entre pasos con progress bar premium (glassmorphism). `prefers-reduced-motion` check obligatorio.

**Esfuerzo:** 6h

### 7.2 P3-02: Schema.org Organization en registro

Metadata JSON-LD `Organization` en pagina de registro para SEO:
```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Jaraba Impact Platform",
  "url": "https://jaraba.es",
  "logo": "..."
}
```

**Esfuerzo:** 2h

### 7.3 P3-03: Accesibilidad WCAG 2.1 AA completa

Audit WCAG 2.1 AA en todos los templates nuevos:
- Contraste minimo 4.5:1
- Todos los interactivos con `aria-label`
- Todos los formularios con `<label>` asociado
- Focus visible en todos los elementos
- Tab order logico
- Skip navigation link
- Touch targets >= 44px

**Esfuerzo:** 8h

### 7.4 P3-04: prefers-reduced-motion en confetti y animaciones

```css
@media (prefers-reduced-motion: reduce) {
  .ej-confetti,
  .ej-wizard-transition,
  .ej-progress-animation {
    animation: none !important;
    transition: none !important;
  }
}
```

**Esfuerzo:** 2h

---

## 8. Especificaciones Tecnicas Detalladas

### 8.1 Library CSS onboarding: diagnostico y solucion

**Estado actual** (ecosistema_jaraba_core.libraries.yml:43-54):
```yaml
onboarding:
  version: 1.x
  css:
    theme:
      css/ecosistema-jaraba-core.css: {}  # <-- NO contiene estilos onboarding
  js:
    js/ecosistema-jaraba-onboarding.js: {}
```

**Estado objetivo:**
```yaml
onboarding:
  version: 1.x
  css:
    theme:
      css/ecosistema-jaraba-core.css: {}
      css/main.css: {}  # <-- Contiene .ej-register-*, .ej-setup-payment-*, .ej-onboarding-*
  js:
    js/ecosistema-jaraba-onboarding.js: {}
  dependencies:
    - core/drupal
    - core/drupal.ajax
    - core/jquery
    - core/once
```

**Validacion:** `grep -c "ej-register\|ej-setup-payment\|ej-onboarding" css/main.css` debe retornar >= 30 hits.

### 8.2 Checkout Stripe real: integracion StripeConnectService

**Flujo de pago completo:**

```
[Cliente]              [checkout.js]           [CheckoutController]        [StripeConnectService]     [Stripe API]
   |                       |                        |                          |                         |
   |  Click "Pagar"        |                        |                          |                         |
   +---------------------->|                        |                          |                         |
   |                       | createPaymentMethod()  |                          |                         |
   |                       +---------------------------------------------->    |                         |
   |                       |                        |                          |   PaymentMethod created  |
   |                       |<----------------------------------------------    |                         |
   |                       |                        |                          |                         |
   |                       | POST /checkout/process  |                          |                         |
   |                       | {payment_method_id}    |                          |                         |
   |                       +----------------------->|                          |                         |
   |                       |                        | createDestinationCharge() |                         |
   |                       |                        +------------------------->|                         |
   |                       |                        |                          | PaymentIntent create    |
   |                       |                        |                          +------------------------>|
   |                       |                        |                          |      pi_xxx + status    |
   |                       |                        |                          |<------------------------|
   |                       |                        |<-------------------------+                         |
   |                       |                        |                          |                         |
   |                       |   {requires_action}    |                          |                         |
   |                       |<-----------------------+                          |                         |
   |                       |                        |                          |                         |
   |                       | confirmCardPayment()   |                          |                         |
   |                       +---------------------------------------------->    |                         |
   |                       |                        |                          |   3D Secure completed   |
   |                       |<----------------------------------------------    |                         |
   |                       |                        |                          |                         |
   |  Redirect to          |                        |                          |                         |
   |  /confirmacion        |                        |                          |                         |
   |<----------------------+                        |                          |                         |
```

**Variables drupalSettings necesarias:**
```php
$variables['#attached']['drupalSettings']['comercioConecta'] = [
    'stripePublicKey' => getenv('STRIPE_PUBLIC_KEY'),
    'processUrl' => Url::fromRoute('jaraba_comercio_conecta.checkout.process')->toString(),
    'confirmUrl' => Url::fromRoute('jaraba_comercio_conecta.checkout.confirmation', ['order_id' => '__ORDER_ID__'])->toString(),
    'marketplaceUrl' => Url::fromRoute('jaraba_comercio_conecta.marketplace')->toString(),
    'ordersUrl' => Url::fromRoute('jaraba_comercio_conecta.customer.orders')->toString(),
    'csrfTokenUrl' => Url::fromRoute('system.csrf_token')->toString(),
];
```

### 8.3 Template agro-producer-products: patron merchant-products

El template debe replicar EXACTAMENTE el patron de `comercio-merchant-products.html.twig` adaptado a ProductAgro:

| Elemento merchant-products | Equivalente agro-producer-products |
|---------------------------|-----------------------------------|
| `comercio-merchant-products` class | `agro-producer-products` class |
| `product.get('title').value` | `product.name` (string ya extraido en controller) |
| `product.get('images').0.entity.fileuri` | `product.image_url` (resuelto en controller) |
| `product.get('stock_quantity').value` | `product.stock` (int ya extraido) |
| `status: draft/active/paused/...` | `status: true/false` (boolean — convertir a texto) |
| `path('entity.product_retail.add_form')` | `path('entity.product_agro.add_form')` |
| `path('entity.product_retail.edit_form', ...)` | `path('entity.product_agro.edit_form', ...)` |
| `jaraba_icon('commerce', 'shopping-bag', ...)` | `jaraba_icon('agriculture', 'seedling', ...)` |

### 8.4 InstructorDashboardController: arquitectura completa

```php
declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_lms\Entity\LmsCourse;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstructorDashboardController extends ControllerBase {

    protected EntityTypeManagerInterface $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager) {
        $this->entityTypeManager = $entityTypeManager;
    }

    public static function create(ContainerInterface $container): static {
        return new static(
            $container->get('entity_type.manager'),
        );
    }

    public function dashboard(): array {
        $uid = (int) $this->currentUser()->id();
        $courseStorage = $this->entityTypeManager->getStorage('lms_course');

        $courseIds = $courseStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('uid', $uid)
            ->sort('changed', 'DESC')
            ->execute();

        $courses = [];
        $totalStudents = 0;
        $totalRating = 0;
        $ratingCount = 0;

        foreach ($courseStorage->loadMultiple($courseIds) as $course) {
            // Contar alumnos
            $studentCount = (int) $this->entityTypeManager
                ->getStorage('lms_enrollment')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('course_id', $course->id())
                ->count()
                ->execute();

            $totalStudents += $studentCount;

            $courses[] = [
                'entity' => $course,
                'student_count' => $studentCount,
            ];
        }

        return [
            '#theme' => 'lms_instructor_dashboard',
            '#courses' => $courses,
            '#kpis' => [
                'total_courses' => count($courses),
                'total_students' => $totalStudents,
            ],
            '#attached' => [
                'library' => ['jaraba_lms/instructor-dashboard'],
            ],
        ];
    }

    // ... lessons() y students() con logica similar
}
```

**CONTROLLER-READONLY-001:** EntityTypeManager se asigna manualmente en constructor body (NO readonly en promotion).

### 8.5 VerticalOnboardingConfig: ConfigEntity para beneficios/next steps

```php
declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * @ConfigEntityType(
 *   id = "vertical_onboarding_config",
 *   label = @Translation("Configuracion de onboarding por vertical"),
 *   config_prefix = "vertical_onboarding",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "vertical_id",
 *     "benefits",
 *     "next_steps",
 *     "cta_text",
 *     "connect_required",
 *     "post_onboarding_redirect_route",
 *   }
 * )
 */
class VerticalOnboardingConfig extends ConfigEntityBase {
    // Standard ConfigEntity with typed properties for each config_export key
}
```

**Schema YAML** (config/schema/):
```yaml
ecosistema_jaraba_core.vertical_onboarding.*:
  type: config_entity
  label: 'Vertical Onboarding Config'
  mapping:
    id:
      type: string
    label:
      type: label
    vertical_id:
      type: string
    benefits:
      type: sequence
      sequence:
        type: mapping
        mapping:
          icon:
            type: string
          text:
            type: string
    next_steps:
      type: sequence
      sequence:
        type: mapping
        mapping:
          title:
            type: string
          description:
            type: string
          url_route:
            type: string
          icon:
            type: string
    cta_text:
      type: string
    connect_required:
      type: boolean
    post_onboarding_redirect_route:
      type: string
```

### 8.6 SCSS: tokens y componentes nuevos

**Regla fundamental:** Los modulos satelite (jaraba_lms, jaraba_agroconecta_core) NO definen variables SCSS. Solo usan CSS custom properties con fallback:

```scss
// CORRECTO — modulo satelite
.agro-producer-products__header {
    background: var(--ej-bg-surface-elevated, #fff);
    color: var(--ej-color-text-primary, #1a1a2e);
    padding: var(--ej-spacing-lg, 1.5rem);
    border-radius: var(--ej-radius-lg, 12px);
}

.agro-empty-state__title {
    color: var(--ej-color-text-secondary, #6b7280);
    font-size: var(--ej-font-size-lg, 1.25rem);
}
```

**Dart Sass moderno** — package.json de jaraba_agroconecta_core:
```json
{
    "scripts": {
        "build": "sass scss/main.scss:css/jaraba-agroconecta-core.css --style=compressed"
    }
}
```

**Si el modulo no tiene SCSS propio** (usa solo CSS vars inline), no necesita package.json — los estilos se escriben directamente en CSS o se embeben en la library.

### 8.7 JavaScript: interacciones de checkout y dashboard

**Patron JS obligatorio:**
```javascript
(function (Drupal, drupalSettings, once) {
    'use strict';

    Drupal.behaviors.featureName = {
        attach: function (context) {
            once('feature-name', '.selector', context).forEach(function (element) {
                // Logica
            });
        }
    };

    // URLs SIEMPRE desde drupalSettings (ROUTE-LANGPREFIX-001)
    // Textos SIEMPRE via Drupal.t() (i18n)
    // Datos de API sanitizados con Drupal.checkPlain() (INNERHTML-XSS-001)
    // CSRF token cacheado en variable del modulo (CSRF-JS-CACHE-001)
})(Drupal, drupalSettings, once);
```

### 8.8 Templates Twig: parciales reutilizables

**Parciales nuevos a crear:**

| Parcial | Ubicacion | Reutilizado en |
|---------|-----------|----------------|
| `_security-badges.html.twig` | `templates/partials/` (tema) | Checkout ComercioConecta, Checkout AgroConecta, Setup Payment |
| `_kpi-card.html.twig` | `templates/partials/` (tema) | Dashboard instructor, Dashboard productor, Dashboard merchant |
| `_onboarding-progress.html.twig` | `templates/partials/` (core module) | 4 paginas de onboarding |

**Parciales existentes a reutilizar:**
- `_header-classic.html.twig` — header en todas las paginas frontend
- `_footer.html.twig` — footer configurable desde theme settings UI
- `_empty-state.html.twig` — si existe; si no, crear como parcial
- `_copilot-fab.html.twig` — FAB de copilot en paginas de dashboard

### 8.9 Hook updates requeridos

| Modulo | hook_update_N | Contenido |
|--------|---------------|-----------|
| `ecosistema_jaraba_core` | `_update_10XXX` | `installEntityType('vertical_onboarding_config')` |
| `jaraba_agroconecta_core` | `_update_10XXX` | `applyUpdates()` para nuevos campos de ProductAgro (images, lot_number, certification, etc.) |
| `jaraba_lms` | — | No requiere hook_update (solo rutas y controller, sin cambios entity) |

**Regla UPDATE-HOOK-CATCH-001:** try-catch con `\Throwable` (NO `\Exception`).
**Regla UPDATE-FIELD-DEF-001:** Nuevos campos con `->setName($field_name)->setTargetEntityTypeId('product_agro')`.

### 8.10 Permisos y roles

| Permiso | Modulo | Descripcion | Roles |
|---------|--------|-------------|-------|
| `manage own courses` | jaraba_lms | CRUD de cursos propios | instructor, admin |
| `view instructor analytics` | jaraba_lms | Ver analytics de cursos propios | instructor, admin |

---

## 9. Tabla de Archivos Creados/Modificados

### Archivos CREADOS (estimacion: 18)

| # | Archivo | Modulo | Sprint |
|---|---------|--------|--------|
| 1 | `agro-producer-products.html.twig` | jaraba_agroconecta_core | P0 |
| 2 | `InstructorDashboardController.php` | jaraba_lms | P0 |
| 3 | `lms-instructor-dashboard.html.twig` | jaraba_lms | P0 |
| 4 | `lms-instructor-lessons.html.twig` | jaraba_lms | P0 |
| 5 | `lms-instructor-students.html.twig` | jaraba_lms | P0 |
| 6 | `page--mis-cursos.html.twig` | ecosistema_jaraba_theme | P0 |
| 7 | `_security-badges.html.twig` | ecosistema_jaraba_theme | P0 |
| 8 | `VerticalOnboardingConfig.php` | ecosistema_jaraba_core | P1 |
| 9-18 | 10x `vertical_onboarding.{vertical}.yml` | ecosistema_jaraba_core | P1 |

### Archivos MODIFICADOS (estimacion: 20)

| # | Archivo | Cambio | Sprint |
|---|---------|--------|--------|
| 1 | `ecosistema_jaraba_core.libraries.yml` | Anadir main.css a library onboarding | P0 |
| 2 | `comercio-checkout.html.twig` | Stripe Elements + security badges | P0 |
| 3 | `checkout.js` | Reescribir con Stripe.js | P0 |
| 4 | `CheckoutController.php` | StripeConnectService integration | P0 |
| 5 | `comercio-checkout-confirmation.html.twig` | Recibo real | P0 |
| 6 | `OnboardingController.php` | URLs path() + vertical config | P0/P1 |
| 7 | `ecosistema-jaraba-register-form.html.twig` | Benefits dinamicos | P0/P1 |
| 8 | `jaraba_lms.routing.yml` | 3 rutas instructor | P0 |
| 9 | `jaraba_lms.permissions.yml` | manage own courses | P0 |
| 10 | `jaraba_lms.module` | hook_theme + preprocess | P0 |
| 11 | `ecosistema_jaraba_theme.theme` | Body classes | P0 |
| 12 | `ecosistema-jaraba-welcome.html.twig` | Connect prompt | P1 |
| 13 | `ProductAgro.php` | Nuevos campos trazabilidad | P1 |
| 14 | `jaraba_agroconecta_core.install` | hook_update campos | P1 |
| 15 | `ecosistema-jaraba-onboarding.js` | Password strength | P2 |
| 16 | `jaraba_comercio_conecta.libraries.yml` | Stripe.js dependency | P0 |
| 17 | `jaraba_agroconecta_core.module` | Verificar hook_theme variables | P0 |
| 18 | `TenantSelfServiceController.php` | Chart.js data | P1 |
| 19 | `tenant-self-service-dashboard.html.twig` | Chart.js integration | P1 |
| 20 | `jaraba_lms/jaraba_lms.services.yml` | Si InstructorDashboard usa DI service | P0 |

---

## 10. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Codigo | Gaps tratados | Sprint | Estado actual |
|----------------|--------|---------------|--------|---------------|
| ROUTE-LANGPREFIX-001 | P0-03 | URLs hardcoded en onboarding + checkout | P0 | VIOLADO |
| CSS-VAR-ALL-COLORS-001 | P0-01, P0-04, P0-05 | Estilos con var(--ej-*) | P0 | PARCIAL |
| SLIDE-PANEL-RENDER-001 | P0-04, P0-05 | Acciones CRUD en slide-panel | P0 | FALTA en agro+LMS |
| ICON-DUOTONE-001 | P0-04, P0-05 | jaraba_icon con variante duotone | P0 | FALTA en templates nuevos |
| ICON-COLOR-001 | P0-04, P0-05, P2-07 | Solo colores de paleta Jaraba | P0-P2 | FALTA |
| ZERO-REGION-001 | P0-05 | Page templates limpias sin page.content | P0 | FALTA para /mis-cursos |
| PREMIUM-FORMS-PATTERN-001 | P0-04, P0-05 | Forms via PremiumEntityFormBase en slide-panel | P0 | PARCIAL |
| STRIPE-ENV-UNIFY-001 | P0-02 | Stripe keys via getenv() | P0 | OK (server), FALTA (JS) |
| CSRF-API-001 | P0-02 | _csrf_request_header_token en checkout | P0 | FALTA |
| INNERHTML-XSS-001 | P0-02 | Drupal.checkPlain() en checkout.js | P0 | FALTA |
| UPDATE-HOOK-REQUIRED-001 | P1-01, P1-05 | hook_update para ConfigEntity + campos | P1 | FALTA |
| UPDATE-FIELD-DEF-001 | P1-05 | setName + setTargetEntityTypeId | P1 | FALTA |
| UPDATE-HOOK-CATCH-001 | P1-05 | \Throwable (no \Exception) | P1 | FALTA |
| VERTICAL-CANONICAL-001 | P1-01 | 10 verticales con configs | P1 | PARCIAL |
| CONTROLLER-READONLY-001 | P0-05 | EntityTypeManager manual assignment | P0 | FALTA |
| TENANT-001 | P0-04, P0-05 | Queries filtradas por tenant | P0 | VERIFICAR |
| SCSS-COMPILE-VERIFY-001 | P0-01 | Timestamp CSS > SCSS | P0 | OK (ya sincronizado) |
| DOC-GUARD-001 | — | Docs en commit separado | — | CUMPLIDO |

---

## 11. Tabla de Cumplimiento de Directrices

| Directriz | Seccion del plan | Cumplimiento |
|-----------|-----------------|--------------|
| Textos traducibles ({% trans %}) | 3.3, 3.4, 4.4, 4.5 | OBLIGATORIO en todos los templates |
| SCSS inyectable (var(--ej-*)) | 3.6, 8.6 | Modulos satelite SOLO CSS vars |
| Dart Sass moderno (@use) | 8.6 | @use (NO @import), package.json obligatorio |
| Templates Twig limpias (zero-region) | 3.1, 3.3, 3.4, 4.5 | Sin page.content ni bloques heredados |
| Parciales Twig ({% include %}) | 8.8 | _security-badges, _kpi-card, _onboarding-progress |
| Configuracion desde UI Drupal | 3.5 | VerticalOnboardingConfig + Theme Settings |
| Mobile-first layout | 3.3, 3.4 | Full-width, touch targets >= 44px |
| Slide-panel para CRUD | 3.3, 3.4, 4.4, 4.5 | data-slide-panel en todos los botones crear/editar |
| Body classes via hook_preprocess_html | 4.5 | NUNCA attributes.addClass() en template |
| Iconos: jaraba_icon duotone | 4.4, 4.5, 6.7 | Variante duotone, colores de paleta |
| Entity navigation: /admin/structure + /admin/content | 8.4 | LMS entities ya en /admin/content |
| Field UI integration | 8.4 | LmsCourse ya tiene field_ui_base_route |
| Views integration | 8.4 | LmsCourse ya tiene views_data |
| CONTROLLER-READONLY-001 | 8.4 | Manual assignment en constructor |
| OPTIONAL-CROSSMODULE-001 | 8.4 | @? para deps cross-modulo |
| SECRET-MGMT-001 | 8.2 | Stripe keys via getenv() |
| AUDIT-SEC-001 | 8.2 | HMAC en webhooks |
| ACCESS-STRICT-001 | 8.4 | (int) comparisons |

---

## 12. Verificacion RUNTIME-VERIFY-001

Tras CADA sprint, ejecutar los 12 checks sistematicos:

| # | Check | Comando / Accion | Sprint |
|---|-------|-----------------|--------|
| 1 | CSS compilado (timestamp > SCSS) | `stat -c '%Y' scss/*.scss css/*.css` | P0 |
| 2 | Tablas DB creadas | `lando drush updb && lando drush entup` | P1 |
| 3 | Rutas accesibles | `lando drush router:rebuild && curl` | P0, P1 |
| 4 | data-* selectores JS/HTML match | `grep data-slide-panel templates/*.twig js/*.js` | P0 |
| 5 | drupalSettings inyectado | Inspector > Console > drupalSettings.comercioConecta | P0 |
| 6 | Library CSS cargada | Inspector > Network > main.css en /registro/* | P0 |
| 7 | Stripe Elements visible | Inspector > #card-element en /checkout | P0 |
| 8 | Traducciones | `lando drush locale:check` | P0 |
| 9 | Permisos | `lando drush role:perm:list` | P0 |
| 10 | Templates renderizados | Navegar a /productor/productos — no error 500 | P0 |
| 11 | Slide-panel funcional | Click "Anadir" — panel se abre | P0 |
| 12 | Mobile responsive | Chrome DevTools 375px width | P0 |

**Comandos de validacion automatica:**
```bash
lando php scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_lms
lando php scripts/validation/validate-routing.php
lando php scripts/validation/validate-entity-integrity.php
lando php scripts/validation/validate-optional-deps.php
lando php scripts/validation/validate-compiled-assets.php
```

---

## 13. Plan de Testing

| Suite | Tests nuevos | Modulo | Sprint |
|-------|-------------|--------|--------|
| Unit | InstructorDashboardControllerTest (5 tests) | jaraba_lms | P0 |
| Unit | VerticalOnboardingConfigTest (3 tests) | ecosistema_jaraba_core | P1 |
| Kernel | ProductAgroFieldsTest (verifica nuevos campos) | jaraba_agroconecta_core | P1 |
| Unit | CheckoutControllerStripeTest (mock Stripe) | jaraba_comercio_conecta | P0 |
| Functional | OnboardingFlowTest (4 paginas, CSS loaded) | ecosistema_jaraba_core | P0 |

**Reglas de testing aplicables:**
- KERNEL-TEST-DEPS-001: Listar TODOS los modulos en $modules
- MOCK-DYNPROP-001: PHP 8.4 prohíbe dynamic properties en mocks
- MOCK-METHOD-001: createMock() solo soporta metodos de la interface
- TEST-CACHE-001: Entity mocks DEBEN implementar cache methods

---

## 14. Coherencia con Documentacion Tecnica Existente

| Documento | Relacion con este plan |
|-----------|----------------------|
| Plan Remediacion Billing Commerce Fiscal (2026-03-05) | Complementario — este plan trata frontend/UX, aquel trata backend/billing |
| Plan Gaps Clase Mundial 100 (2026-03-03) | Este plan resuelve gaps GAP-CHECKOUT, GAP-ONBOARDING, GAP-CATALOG |
| Plan Verticales Componibles (2026-03-05) | VerticalOnboardingConfig se integra con ADDON-VERTICAL-001 |
| Arquitectura Theming SaaS Master (2026-02-05) | Este plan cumple con 5-layer tokens, SCSS compilation, injectable pattern |
| Aprendizaje #161 (verticales componibles) | TenantVerticalService resuelve verticales para onboarding config |
| 07_VERTICAL_CUSTOMIZATION_PATTERNS v3.1 | VerticalOnboardingConfig es nueva dimension de customization |

---

## 15. Registro de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| 1.0.0 | 2026-03-05 | Creacion inicial con 32 gaps en 4 sprints |

---

Jaraba Impact Platform — Plataforma de Ecosistemas Digitales S.L.
Generado el 05 de marzo de 2026
