# Auditoria: Flujo de Onboarding, Checkout y Creacion de Comercio — Clase Mundial

> **Fecha:** 2026-03-05
> **Version:** 1.0.0
> **Clasificacion:** CONFIDENCIAL — Uso Interno
> **Autor:** Claude Opus 4.6 (Analisis multi-dimensional)
> **Roles:** Consultor de negocio senior, arquitecto SaaS senior, ingeniero UX senior, ingeniero Drupal senior, analista financiero senior, desarrollador de theming senior

## 1. Resumen Ejecutivo

Este documento presenta un analisis exhaustivo del flujo completo que experimenta un tenant desde su registro hasta la operacion de su comercio en Jaraba Impact Platform. El analisis cubre el onboarding SaaS (registro, seleccion de plan, pago, bienvenida), el checkout del marketplace ComercioConecta, y la integracion con Stripe.

**Veredicto:** El flujo de onboarding de suscripciones SaaS tiene una arquitectura backend solida y bien integrada con Stripe. Sin embargo, hay una brecha significativa entre "el codigo existe" y "el usuario lo experimenta". El flujo de ComercioConecta (marketplace checkout) tiene gaps criticos que lo hacen no funcional para pagos reales.

## 2. Flujo SaaS Onboarding — Evaluacion por Capa

### 2.1 Backend (PHP): 9/10

- `TenantOnboardingService` (`ecosistema_jaraba_core/src/Service/TenantOnboardingService.php`): Completo. Validacion robusta, rollback si falla creacion de tenant, email de bienvenida, transliteracion de dominios DNS-safe.
- `StripeController` (`ecosistema_jaraba_core/src/Controller/StripeController.php`): Real y production-ready. Stripe API directa (no stubs), idempotency keys (AUDIT-PERF-007), 3D Secure, mensajes de error localizados al espanol.
- `completeOnboarding()`: Implementado correctamente — guarda stripe_customer_id, stripe_subscription_id, activa suscripcion.
- Ciclo de vida: trial -> active -> past_due -> suspended -> cancelled bien orquestado via `TenantSubscriptionService`.

### 2.2 Frontend JS: 8/10

- `ecosistema-jaraba-stripe.js`: Completo. Stripe Elements montado con estilos custom, validacion en tiempo real, 3D Secure via `confirmCardPayment()`, billing toggle mensual/anual.
- `ecosistema-jaraba-onboarding.js`: Completo. Validacion client-side con feedback por campo, confetti en bienvenida, formateo automatico de dominio.

### 2.3 Templates HTML: 7/10 — GAPS IDENTIFICADOS

**GAP CRITICO 1: Beneficios hardcoded para AgroConecta**
El template `ecosistema-jaraba-register-form.html.twig` tiene beneficios genericos de "trazabilidad" y "lotes de produccion" que solo aplican a AgroConecta. Para ComercioConecta deberia decir "marketplace local", "gestion de productos", etc. El template NO es vertical-aware en sus beneficios.

- Linea 53: "Trazabilidad completa de lotes"
- Linea 57: "Certificados con firma digital"
- Linea 61: "Codigos QR verificables"
- Linea 47: "Gestiona tu organizacion con herramientas profesionales de trazabilidad"

**GAP CRITICO 2: Next steps hardcoded para AgroConecta**
`OnboardingController::getNextSteps()` (linea 369-401) devuelve pasos de "catalogo de productos para trazabilidad" y "registra un lote de produccion" — solo tiene sentido para AgroConecta, no para los otros 9 verticales.

**GAP 3: URLs hardcoded sin `path()`**
Viola ROUTE-LANGPREFIX-001 (el sitio usa `/es/` prefix):
- `/admin/people/invite` — OnboardingController linea 383
- `/admin/content/product` — OnboardingController linea 390
- `/node/add/lote_produccion` — OnboardingController linea 397
- `/user/login` — register template linea 228

### 2.4 CSS: 5/10 — GAP GRAVE

**GAP CRITICO 4: CSS de onboarding NO se carga correctamente**

La library `onboarding` en `ecosistema_jaraba_core.libraries.yml` carga `css/ecosistema-jaraba-core.css`, pero los estilos de onboarding (`.ej-register-*`, `.ej-setup-payment-*`, `.ej-onboarding-progress-*`) estan en `css/main.css` (linea 1331+). La library NO incluye `main.css`.

Resultado: **las paginas de registro, seleccion de plan y pago se renderizan SIN estilos** — el usuario ve HTML plano sin formato.

La library `stripe` depende de `onboarding`, heredando el mismo problema.

**CSS Pricing:** Sincronizado (CSS compilado 2026-03-05 10:57, posterior al SCSS 10:50). Gap resuelto.

## 3. Flujo Marketplace Checkout (ComercioConecta) — CRITICO

### 3.1 Backend: 6/10

- `CheckoutService` (`jaraba_comercio_conecta/src/Service/CheckoutService.php`): Crea ordenes locales correctamente, calcula comisiones con cascade (tenant -> platform_default -> 10%).
- **PERO**: `StripePaymentRetailService` **SIMULA pagos** si `jaraba_billing.stripe_client` no existe (fallback a `pi_simulated_*`).
- NO usa `StripeConnectService::createDestinationCharge()` que ya existe y funciona en el backend.
- No hay integracion real entre `CheckoutController.processPayment()` y Stripe.

### 3.2 Frontend JS: 3/10 — BLOQUEADOR

- `checkout.js` envia `fetch('/checkout/procesar')` con `payment_method: 'stripe'` generico.
- **NO carga Stripe.js** — la tarjeta NUNCA se captura client-side.
- NO usa `stripe.confirmPayment()` — el pago nunca se ejecuta realmente.
- URLs hardcoded sin `path()`: `/checkout/procesar`, `/checkout/confirmacion/`, `/comercio-local`, `/mis-pedidos`.

### 3.3 Template: 5/10

- Estructura funcional pero **NO hay formulario de tarjeta**.
- No hay Stripe Elements.
- No hay indicadores de seguridad (SSL badge, Stripe badge).
- Confirmacion es un placeholder: no hay recibo, no hay email preview, no hay timeline de entrega.

## 4. Problemas Transversales

### 4.1 Vertical-Awareness (2/10)

El onboarding es monolitico y generico. No se adapta al vertical:

| Elemento | Estado | Deberia |
|----------|--------|---------|
| Beneficios en registro | Hardcoded AgroConecta | Dinamicos por vertical |
| Next steps en bienvenida | Hardcoded AgroConecta | Configurables por vertical |
| Iconos y colores | Solo colores CSS custom properties | Iconos, microcopy, CTA vertical-specific |
| Onboarding wizard | Existe (`onboarding-wizard.html.twig`) | NO conectado al flujo principal |
| Stripe Connect prompt | Ausente | Automatico para ComercioConecta/AgroConecta |

### 4.2 Gaps UX Clase Mundial

| Gap | Severidad | Benchmark Shopify/Stripe |
|-----|-----------|--------------------------|
| Sin email verification | Alta | Shopify verifica antes de activar |
| Sin indicador de fortaleza de contrasena visual | Media | Todos los SaaS tier-1 lo tienen |
| Sin auto-complete en formularios | Media | Stripe recomienda autocomplete="cc-*" |
| Sin social login (Google/Apple) | Media | 40% de conversion adicional |
| Sin pre-fill datos facturacion desde registro | Media | Reduce friccion 1 paso |
| Dashboard charts = canvas vacios sin Chart.js | Alta | Datos reales o empty state guiado |
| Sin notificacion trial expiring (3 dias) | Alta | Todos los SaaS B2B lo implementan |
| Sin Stripe Connect auto-prompt para merchants | Alta | Shopify lo hace en setup wizard |
| Checkout marketplace sin Stripe real | Bloqueador | Sin esto, 0 ventas posibles |

### 4.3 Diferencia "Codigo Existe" vs "Usuario Experimenta"

| Capa | Existe | Se experimenta | Gap |
|------|--------|----------------|-----|
| Backend onboarding | Completo | Funcional | Bajo |
| Backend Stripe suscripciones | Completo | Funcional | Bajo |
| Backend Stripe Connect | Completo | No se auto-invoca | Medio |
| Backend marketplace checkout | Parcial (simulado) | Pagos no funcionan | CRITICO |
| CSS onboarding | Existe en main.css | NO se carga (library mal) | CRITICO |
| CSS pricing | SCSS modificado | CSS no compilado (+2 dias) | ALTO |
| Charts dashboard | Canvas HTML existe | Vacio (sin Chart.js) | ALTO |
| Wizard onboarding | Template completo | NO conectado al flujo | ALTO |
| Next steps vertical-specific | Hardcoded agro | Solo agro tiene sentido | MEDIO |

## 5. Plan de Remediacion Priorizado

### Sprint P0 — Bloqueadores (Semana 1)

**P0-01: Corregir library CSS de onboarding**
- Anadir `main.css` a la library `onboarding` o mover estilos a `ecosistema-jaraba-core.css`
- Impacto: 4 paginas de onboarding sin estilos actualmente

**P0-02: Compilar SCSS pricing**
- `npm run build` en theme directory
- Los templates de pricing usan clases cuyo CSS esta 2+ dias desincronizado

**P0-03: Checkout marketplace -> Stripe real**
- Integrar `StripeConnectService::createDestinationCharge()` en `CheckoutController`
- Anadir Stripe.js a `checkout.js` con `stripe.confirmPayment()`
- Crear Stripe Elements en template de checkout
- Sin esto: 0 ventas posibles en ComercioConecta

**P0-04: URLs hardcoded -> `path()`**
- Todas las URLs en templates y JS deben usar `Url::fromRoute()` / `path()` / `drupalSettings`
- Violacion directa de ROUTE-LANGPREFIX-001

### Sprint P1 — Alta Friccion (Semanas 2-3)

**P1-01: Vertical-awareness en onboarding**
- Crear `VerticalOnboardingConfig` (ConfigEntity o campo en Vertical entity) con:
  - `benefits[]`: Lista de beneficios por vertical
  - `next_steps[]`: Pasos siguientes personalizados
  - `cta_text`: Texto del boton primario
  - `connect_required`: Boolean si necesita Stripe Connect

**P1-02: Conectar wizard al flujo principal**
- El template `onboarding-wizard.html.twig` existe completo con 7 pasos
- Pero `OnboardingController` NO lo usa — va directo a formulario simple

**P1-03: Auto-prompt Stripe Connect para merchants**
- Tras completar onboarding de ComercioConecta/AgroConecta, redirigir automaticamente a Stripe Connect
- El endpoint `POST /api/v1/stripe/connect/onboard` ya existe y funciona

**P1-04: Dashboard charts reales**
- Integrar Chart.js via library
- Conectar con `RevenueMetricsService::getDashboardSnapshot()`
- O si no hay datos: empty state guiado

### Sprint P2 — Clase Mundial (Semanas 4-6)

- P2-01: Password strength indicator visual
- P2-02: Pre-fill billing details desde datos de registro
- P2-03: Email verification con magic link
- P2-04: Trial expiration notifications (3 dias, 1 dia, expirado)
- P2-05: Social login (Google OAuth)
- P2-06: Confetti animation con `prefers-reduced-motion` check
- P2-07: Schema.org Organization en pagina de registro

## 6. Lo que SI es Clase Mundial

- **Stripe integration backend**: Idempotency keys, 3D Secure, error messages localizados, customer dedup
- **Webhook handling**: 11 eventos procesados con deduplicacion y lock anti-race-condition
- **Fiscal delegation**: VeriFactu + Facturae automatico — cumplimiento legal espanol completo
- **Pricing pages**: Glassmorphism, particles, Schema.org, toggle anual -17%, LSSI-CE compliance
- **Product detail page**: Schema.org Product+Offer+BreadcrumbList, galeria interactiva, variaciones, 4 tiers IVA
- **Security**: HMAC webhooks, CSRF tokens, idempotency keys, env-based secrets

## 7. Archivos Clave Auditados

### Controladores
- `ecosistema_jaraba_core/src/Controller/OnboardingController.php` — Flujo principal (4 pasos)
- `ecosistema_jaraba_core/src/Controller/StripeController.php` — Pagos + Connect (8 endpoints)
- `ecosistema_jaraba_core/src/Controller/PricingController.php` — Paginas publicas de precios
- `ecosistema_jaraba_core/src/Controller/TenantSelfServiceController.php` — Post-onboarding
- `jaraba_comercio_conecta/src/Controller/CheckoutController.php` — Marketplace checkout

### Servicios
- `ecosistema_jaraba_core/src/Service/TenantOnboardingService.php` — Orquestacion completa
- `ecosistema_jaraba_core/src/Service/TenantManager.php` — Gestion tenants
- `jaraba_billing/src/Service/StripeSubscriptionService.php` — Suscripciones Stripe
- `jaraba_billing/src/Service/StripeCustomerService.php` — Clientes Stripe
- `jaraba_comercio_conecta/src/Service/CheckoutService.php` — Checkout marketplace
- `jaraba_comercio_conecta/src/Service/StripePaymentRetailService.php` — Pagos marketplace (STUB)

### Templates
- `ecosistema-jaraba-register-form.html.twig` — Formulario registro
- `ecosistema-jaraba-select-plan.html.twig` — Seleccion de plan
- `ecosistema-jaraba-setup-payment.html.twig` — Configuracion pago Stripe
- `ecosistema-jaraba-welcome.html.twig` — Bienvenida post-onboarding
- `comercio-checkout.html.twig` — Checkout marketplace (sin Stripe Elements)
- `comercio-checkout-confirmation.html.twig` — Confirmacion (placeholder)
- `pricing-page.html.twig` — Precios por vertical
- `pricing-hub-page.html.twig` — Hub de planes

### JavaScript
- `ecosistema-jaraba-stripe.js` — Stripe Elements + 3D Secure (FUNCIONAL)
- `ecosistema-jaraba-onboarding.js` — Validacion + confetti (FUNCIONAL)
- `checkout.js` — Checkout marketplace (SIN STRIPE.JS — BLOQUEADOR)

### CSS/SCSS
- `ecosistema_jaraba_core/css/main.css` — Contiene estilos onboarding (NO en library)
- `ecosistema_jaraba_core/css/ecosistema-jaraba-core.css` — Library cargada (SIN estilos onboarding)
- `ecosistema_jaraba_theme/scss/components/_pricing-page.scss` — DESINCRONIZADO
- `ecosistema_jaraba_theme/scss/components/_pricing-hub.scss` — DESINCRONIZADO

## 8. Arquitectura Stripe — Modelo de Cuentas

### 8.1 Modelo: Cuenta Unica + Connect Express

La plataforma opera con **UNA sola cuenta Stripe principal** (la del SaaS). Los tenants NO tienen cuentas Stripe independientes.

| Rol | Tipo Stripe | ID almacenado | Creado por |
|-----|-------------|---------------|------------|
| Plataforma SaaS | Account (principal) | — | Configuracion manual |
| Tenant suscriptor | Customer | `stripe_customer_id` | `StripeController::createSubscription()` |
| Tenant vendedor marketplace | Connect Express | `stripe_connect_id` | `StripeConnectService::startConnectOnboarding()` |

### 8.2 Flujo de Pagos Marketplace (Destination Charges)

```
Consumidor → paga 100 EUR → Cuenta Stripe SaaS → 90 EUR → Cuenta Connect del Merchant
                                                → 10 EUR → Comision plataforma
```

- `StripeConnectService::createDestinationCharge()` ya implementa este patron
- La comision se calcula via cascade: tenant config → platform_default → 10% fallback
- **GAP**: El onboarding NO auto-invoca Stripe Connect para merchants — deben encontrarlo manualmente

### 8.3 Servicios Stripe Clave

| Servicio | Funcion | Estado |
|----------|---------|--------|
| `StripeSubscriptionService` | Suscripciones SaaS (crear, cambiar plan, cancelar) | Funcional |
| `StripeCustomerService` | CRUD de Customers (dedup por email) | Funcional |
| `StripeConnectService` | Onboarding Express + destination charges | Funcional (no auto-invocado) |
| `StripePaymentRetailService` | Pagos marketplace retail | SIMULADO (`pi_simulated_*`) |
| `BillingWebhookController` | 11 eventos webhook con HMAC + dedup | Funcional |

## 9. Catalogo de Productos — Evaluacion por Vertical

### 9.1 ComercioConecta: Portal de Merchant (7/10)

**Interfaz**: `/mi-comercio/productos` via `MerchantDashboardController`

- Slide-panel CRUD (patron SLIDE-PANEL-RENDER-001)
- Entity `ProductRetail`: 22 campos, variaciones (talla/color), hasta 10 imagenes, SEO (meta_title, meta_description), IVA 4 tramos
- Listado con filtros, busqueda, paginacion
- Template `merchant-products.html.twig` funcional

**Gaps identificados:**
- Sin bulk import (CSV/Excel) — merchants con 100+ productos necesitan carga masiva
- Sin duplicar producto existente (clone) — reduce friccion para productos similares
- Sin preview antes de publicar
- Sin integracion con IA para generar descripciones (el campo `field_ai_summary` existe en la entity pero no hay boton en el form)
- Sin gestion de inventario/stock (campo `stock` existe pero no hay alertas de stock bajo)

### 9.2 AgroConecta: Interfaz de Productor (3/10) — CRITICO

**Entity**: `ProductAgro` — solo 13 campos, 1 imagen, sin variaciones, sin SEO

**GAP BLOQUEADOR**: El template frontend para gestion de productos del productor NO EXISTE, aunque el controller y las rutas SI existen.
- `ProducerPortalController::products()` (linea 265) retorna `#theme => 'agro_producer_products'`
- `hook_theme()` registra el template como `agro-producer-products` (linea 93)
- Ruta `/productor/productos` existe en routing.yml (linea 456)
- **PERO el archivo `agro-producer-products.html.twig` NO EXISTE** en la carpeta de templates
- Resultado: error de renderizacion al acceder `/productor/productos`

**Gaps adicionales:**
- Sin campos de trazabilidad en ProductAgro (lote, certificacion, origen geografico) — contradice la propuesta de valor del vertical
- Sin galeria multi-imagen (solo 1 imagen)
- Sin variaciones (peso/formato)
- Sin QR de trazabilidad vinculado al producto
- Sin campo de temporada/estacionalidad

**Impacto**: Un productor agricola que contrata AgroConecta no tiene forma practica de crear su catalogo. La interfaz de administracion de Drupal no es aceptable para un usuario no tecnico.

### 9.3 Comparativa Clase Mundial

| Caracteristica | ComercioConecta | AgroConecta | Shopify (benchmark) |
|----------------|-----------------|-------------|---------------------|
| Frontend CRUD | Slide-panel | Controller+rutas SI, template NO | Full dashboard |
| Campos producto | 27 | 16 | 40+ |
| Imagenes | Hasta 10 | 1 | Ilimitadas |
| Variaciones | Si (talla/color) | No | Si (3 opciones) |
| SEO por producto | Si | No | Si |
| Bulk import | No | No | CSV nativo |
| AI description | Campo existe, sin UI | No | Shopify Magic |
| Inventario/alertas | Campo sin alertas | No | Completo |
| Preview | No | No | Si |

## 10. Catalogo de Cursos (Formacion) — Evaluacion

### 10.1 Experiencia del Alumno (9/10) — Clase Mundial

- Catalogo con filtros, busqueda semantica, cards con rating
- Detalle de curso con Schema.org Course, temario expandible, instructor bio
- Matriculacion con verificacion de plan/cuota
- Progreso con xAPI tracking, barra visual, estado por leccion
- Certificados con Open Badges 2.0 + firma digital
- Gamificacion: puntos, badges, leaderboard
- Foro de discusion por leccion

### 10.2 Experiencia del Instructor (2/10) — CRITICO

**GAP BLOQUEADOR**: No existe interfaz frontend para instructores.

- La creacion de cursos solo es posible via `/admin/content/course` (admin Drupal)
- No hay `/mis-cursos` ni dashboard de instructor
- No hay editor de lecciones frontend (solo node form admin)
- No hay vista de alumnos matriculados accesible al instructor
- No hay analytics de engagement por curso/leccion

**Lo que SI existe en backend:**
- Entities completas: `Course`, `Lesson`, `CourseEnrollment`, `LessonProgress`
- `CourseManagementService` con CRUD completo
- `CertificateService` con generacion automatica
- `GamificationService` con puntos y badges

**Impacto**: Un formador que contrata el vertical Formacion no puede crear cursos sin acceso admin. Para un SaaS de clase mundial, esto es inaceptable — Teachable, Thinkific y Kajabi ofrecen dashboards de instructor completos.

### 10.3 Plan de Remediacion Catalogo/Cursos

**P0-05: Frontend AgroConecta productor** (Semana 1)
- Crear ruta `/mi-finca/productos` con `ProducerDashboardController`
- Slide-panel CRUD para ProductAgro
- Ampliar ProductAgro: multi-imagen, variaciones peso/formato, campos trazabilidad

**P0-06: Frontend Formacion instructor** (Semana 1-2)
- Crear ruta `/mis-cursos` con `InstructorDashboardController`
- Slide-panel CRUD para Course + Lesson
- Vista de alumnos matriculados con progreso
- Analytics basicos por curso

**P1-05: Mejoras ComercioConecta** (Semana 3)
- Bulk import CSV
- Boton "Generar descripcion IA" en form de producto
- Preview antes de publicar
- Alertas de stock bajo

## 11. Cross-references

- Documento Comprensivo Billing/Commerce/Fiscal v1.0.0 (2026-03-05)
- **Plan de Implementacion: Remediacion Onboarding, Checkout, Catalogos v1.0.0 (2026-03-05)** — Plan detallado que resuelve todos los gaps de esta auditoria
- Plan de Implementacion: Remediacion Billing, Commerce, Fiscal v1.0.0 (2026-03-05) — Complementario (backend/billing)
- CLAUDE.md: ROUTE-LANGPREFIX-001, STRIPE-ENV-UNIFY-001, CSS-VAR-ALL-COLORS-001
- Directrices v111.0.0, Arquitectura v100.0.0

---

Jaraba Impact Platform — Plataforma de Ecosistemas Digitales S.L.
Generado el 05 de marzo de 2026 | Actualizado: 05 de marzo de 2026 (v1.1.0 — catalogo productos/cursos + Stripe architecture)
