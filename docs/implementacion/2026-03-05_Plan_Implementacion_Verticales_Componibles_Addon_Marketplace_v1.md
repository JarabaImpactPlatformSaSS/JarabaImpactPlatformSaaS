# Plan de Implementacion: Verticales Componibles y Addon Marketplace — Clase Mundial

**Fecha de creacion:** 2026-03-05
**Ultima actualizacion:** 2026-03-05
**Autor:** Claude Opus 4.6 (Anthropic) — Consultor Senior Multi-Disciplinar
**Version:** 1.0.0
**Categoria:** Plan de Implementacion Estrategico
**Codigo:** VERT-ADDON-001
**Estado:** PLANIFICADO
**Esfuerzo estimado:** 65-85h (7 fases)
**Documentos fuente:** Doc 158 (Pricing Matrix), VERT-PRICING-001, Plan Remediacion v2.1, 2026-02-05_arquitectura_theming_saas_master.md
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v110.0.0, `00_FLUJO_TRABAJO_CLAUDE.md` v63.0.0, `CLAUDE.md` v1.2.0
**Modulos afectados:** `jaraba_addons`, `ecosistema_jaraba_core`, `jaraba_billing`, `ecosistema_jaraba_theme`
**Rutas principales:** `/addons` (catalogo), `/addons/{addon_id}` (detalle), `/my-dashboard` (quick links vertical-aware), `/my-settings` (hub configuracion)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Modelo de negocio: Verticales Componibles](#13-modelo-de-negocio-verticales-componibles)
   - 1.4 [Impacto financiero proyectado](#14-impacto-financiero-proyectado)
   - 1.5 [Alcance y exclusiones](#15-alcance-y-exclusiones)
   - 1.6 [Filosofia "Sin Humo"](#16-filosofia-sin-humo)
2. [Diagnostico: Estado Actual del Ecosistema](#2-diagnostico-estado-actual-del-ecosistema)
   - 2.1 [Inventario de verticales y sus portales](#21-inventario-de-verticales-y-sus-portales)
   - 2.2 [Infraestructura existente en jaraba_addons](#22-infraestructura-existente-en-jaraba_addons)
   - 2.3 [Feature gating existente](#23-feature-gating-existente)
   - 2.4 [Gaps entre "el codigo existe" y "el usuario lo experimenta"](#24-gaps-entre-el-codigo-existe-y-el-usuario-lo-experimenta)
3. [Arquitectura de la Solucion](#3-arquitectura-de-la-solucion)
   - 3.1 [Modelo conceptual: Hub + Spokes](#31-modelo-conceptual-hub--spokes)
   - 3.2 [Flujo de datos: Tenant -> Vertical primario + Addon Subscriptions](#32-flujo-de-datos)
   - 3.3 [Integracion con FeatureAccessService y FeatureGateRouterService](#33-integracion-con-feature-gates)
   - 3.4 [Cascade de resolucion de acceso](#34-cascade-de-resolucion-de-acceso)
   - 3.5 [Integracion Stripe: Subscription Items multi-linea](#35-integracion-stripe)
   - 3.6 [Quick Links vertical-aware en el Dashboard](#36-quick-links-vertical-aware)
   - 3.7 [Diagrama de entidades y relaciones](#37-diagrama-de-entidades)
4. [Fases de Implementacion](#4-fases-de-implementacion)
   - 4.1 [Fase 1: Vertical Addon Entities y Datos Seed](#41-fase-1-vertical-addon-entities-y-datos-seed)
   - 4.2 [Fase 2: TenantVerticalService — Resolucion Multi-Vertical](#42-fase-2-tenantverticalservice)
   - 4.3 [Fase 3: Dashboard Quick Links Vertical-Aware](#43-fase-3-dashboard-quick-links)
   - 4.4 [Fase 4: Catalogo de Verticales Premium (Frontend)](#44-fase-4-catalogo-frontend)
   - 4.5 [Fase 5: Integracion Stripe Subscription Items](#45-fase-5-integracion-stripe)
   - 4.6 [Fase 6: Feature Gate Unificado (Plan + Addons)](#46-fase-6-feature-gate-unificado)
   - 4.7 [Fase 7: Cross-Vertical AI Intelligence](#47-fase-7-cross-vertical-ai)
5. [Especificaciones Tecnicas Detalladas](#5-especificaciones-tecnicas-detalladas)
   - 5.1 [Entidad VerticalAddon: Definicion completa de campos](#51-entidad-verticaladdon)
   - 5.2 [TenantVerticalService: API publica](#52-tenantverticalservice-api)
   - 5.3 [Quick Links: Mapa completo vertical -> ruta -> icono -> etiqueta](#53-quick-links-mapa)
   - 5.4 [Templates Twig: Estructura y variables](#54-templates-twig)
   - 5.5 [SCSS: Tokens y componentes](#55-scss-tokens)
   - 5.6 [JavaScript: Interacciones del catalogo](#56-javascript)
   - 5.7 [hook_update_N(): Migraciones requeridas](#57-hook-update)
6. [Tabla de Archivos Creados/Modificados](#6-tabla-de-archivos)
7. [Tabla de Correspondencia con Especificaciones Tecnicas](#7-correspondencia-especificaciones)
8. [Tabla de Cumplimiento de Directrices](#8-cumplimiento-directrices)
9. [Verificacion RUNTIME-VERIFY-001](#9-verificacion-runtime)
10. [Coherencia con Documentacion Tecnica Existente](#10-coherencia-documentacion)
11. [Plan de Testing](#11-plan-de-testing)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Sistema de **Verticales Componibles** que permite a un tenant activar verticales adicionales como add-ons premium, manteniendo el modelo 1 tenant = 1 vertical primario pero ampliando con N verticales secundarios. El dashboard del tenant se contextualiza dinamicamente mostrando acciones rapidas relevantes para cada vertical activo. El catalogo de verticales disponibles se presenta como una experiencia premium de marketplace integrada en el portal self-service del tenant.

### 1.2 Por que se implementa

El diagnostico exhaustivo del ecosistema revela:

**Problema de negocio:**
- Un tenant de AgroConecta que quiere vender online necesita tambien ComercioConecta. Hoy debe crear 2 tenants separados con 2 facturas, 2 logins, 2 dominios.
- Un despacho de abogados (JarabaLex) que ofrece formacion necesita Formacion como segundo vertical. Hoy no puede.
- Cualquier negocio que necesite un blog/content marketing (Content Hub) junto a su vertical primario queda limitado.

**Problema tecnico:**
- El dashboard en `/my-dashboard` tenia enlaces de "Mis Productos" y "Soporte" como placeholders deshabilitados (`href="#"`, clase `--disabled`).
- El enlace "Mis Productos" no es contextual — un despacho de abogados no vende "productos", vende "casos legales". Una academia no vende "productos", vende "cursos".
- No existe mecanismo para que un tenant active verticales adicionales sin intervencion del administrador.

**Oportunidad de revenue:**
- El ARPU actual de un tenant es de ~€79/mes (plan Professional promedio).
- Con verticales componibles, un tenant puede contratar 2-3 verticales adicionales, elevando el ARPU a €110-€140/mes (+40-77%).
- Cross-sell natural: vertical primario → verticales complementarios → mayor stickiness → menor churn.

### 1.3 Modelo de negocio: Verticales Componibles

**Analogia de mercado:** HubSpot "Hubs" — una cuenta, multiples productos (Marketing Hub, Sales Hub, Service Hub, CMS Hub). El cliente compone su stack segun necesidades.

**Modelo Jaraba:**

```
Tenant "Cooperativa Aceites del Sur"
+-- Vertical primario: agroconecta (plan Profesional: EUR79/mes)
|   Incluye: ProductAgro, ProducerProfile, OrderAgro, marketplace
|
+-- Addon vertical: comercioconecta (EUR29/mes)
|   Desbloquea: ProductRetail, MerchantProfile, /mi-comercio/*
|
+-- Addon vertical: jaraba_content_hub (EUR15/mes)
|   Desbloquea: ContentArticle, BlogController, /content-hub
|
+-- Addon vertical: formacion (EUR19/mes)
|   Desbloquea: Courses, LearnerController, /my-courses
|
= ARPU total: EUR142/mes (vs EUR79 con modelo actual = +80%)
```

**Reglas del modelo:**
1. **Vertical primario** define la identidad del tenant, su plan base (Starter/Professional/Enterprise) y la facturacion principal via Stripe Subscription.
2. **Verticales secundarios** se activan como Addon entities con tipo `vertical` y precio mensual/anual propio.
3. **Feature gates** evaluan: plan base + addons activos. Si una feature pertenece a un vertical contratado, se desbloquea.
4. **Un solo login, un solo dominio, una sola factura** — los verticales secundarios se anaden como line items en la Stripe Subscription existente.
5. **El campo `vertical` del Tenant NO cambia** (sigue siendo entity_reference con cardinalidad 1). La multi-verticalidad se resuelve via AddonSubscription entities.

### 1.4 Impacto financiero proyectado

| Metrica | Modelo actual (1:1) | Modelo Componible | Delta |
|---------|---------------------|-------------------|-------|
| ARPU mensual | EUR79 | EUR110-140 | +40-77% |
| LTV (18 meses) | EUR1.422 | EUR1.980-2.520 | +39-77% |
| Churn mensual estimado | 5-7% | 3-4% | -30-50% |
| CAC payback | 4-6 meses | 2-3 meses | -50% |
| Cross-sell rate | 0% (imposible) | 15-25% (proyectado) | Nueva revenue |
| Revenue por vertical secundario | EUR0 | EUR15-29/mes | Nuevo flujo |

**Justificacion de menor churn:** Un tenant que usa 3 verticales tiene 3x mas integraciones, datos y flujos dependientes de la plataforma. El coste de cambio (switching cost) crece geometricamente con cada vertical adicional.

### 1.5 Alcance y exclusiones

**EN ALCANCE:**
- Creacion de Addon entities tipo `vertical` para los 9 verticales no-primarios
- Servicio `TenantVerticalService` que resuelve todos los verticales activos de un tenant
- Dashboard quick links dinamicos segun verticales activos
- Catalogo premium de verticales disponibles en `/addons` (filtro type=vertical)
- Integracion con FeatureAccessService para evaluar addons activos
- Templates Twig limpios (ZERO-REGION-001) con parciales reutilizables
- SCSS compilado con tokens `var(--ej-*)` (CSS-VAR-ALL-COLORS-001)
- Iconos duotone (ICON-CONVENTION-001) para cada vertical
- Textos traducibles ({% trans %})
- hook_update_N() para nuevos campos/entities

**FUERA DE ALCANCE (futuro):**
- Integracion Stripe real (Subscription Items) — se documenta arquitectura pero se implementa en Fase 5
- Cross-vertical AI intelligence — Fase 7
- Migracion de tenants existentes con multiples cuentas
- Pricing dynamico basado en uso

### 1.6 Filosofia "Sin Humo"

La infraestructura critica YA EXISTE:
- `jaraba_addons` tiene Addon + AddonSubscription entities con full CRUD
- `AddonSubscriptionService` tiene subscribe/cancel/renew/isAddonActive
- `FeatureAccessService` (jaraba_billing) ya evalua plan + addons activos
- `FeatureGateRouterService` ya despacha a 10 servicios por vertical
- `AddonCatalogController` ya renderiza `/addons` con grid de cards
- Templates `addons-catalog.html.twig` y `addons-detail.html.twig` ya existen

**Lo que falta es DATOS y CONEXION:**
1. No existen Addon entities de tipo `vertical` (solo hay addons de tipo feature/storage)
2. No hay servicio que unifique vertical primario + addon subscriptions
3. El dashboard no consulta addons activos para generar quick links
4. El catalogo no tiene seccion visual premium para verticales

---

## 2. Diagnostico: Estado Actual del Ecosistema

### 2.1 Inventario de verticales y sus portales

Cada vertical tiene un portal completo que se desbloquea cuando el tenant lo activa:

| Vertical (machine_name) | Portal Frontend | Ruta Principal | Entidades Propias | Estado |
|--------------------------|-----------------|----------------|-------------------|--------|
| `comercioconecta` | Merchant Portal | `/mi-comercio/productos` | ProductRetail, MerchantProfile, OrderRetail, CartItem (22 entities) | COMPLETO |
| `serviciosconecta` | Provider Portal | `/mi-servicio/servicios` | ProviderProfile, ServiceOffering, Booking, AvailabilitySlot | COMPLETO |
| `agroconecta` | Producer Portal | `/productor/productos` | ProductAgro, ProducerProfile, OrderAgro, AgroCertification (20 entities) | PARCIAL |
| `empleabilidad` | Employer Portal | `/employer/jobs` | JobPosting, JobApplication, JobAlert | COMPLETO |
| `emprendimiento` | Entrepreneur Dashboard | `/entrepreneur/dashboard` | BusinessModelCanvas, MvpHypothesis, FinancialProjection | COMPLETO |
| `jarabalex` | Legal Dashboard | `/legal/cases` | ClientCase, LegalDocument, TimeEntry, Invoice (7 modulos) | COMPLETO |
| `formacion` | Learner Dashboard | `/my-courses` | Course, Module, Lesson, Enrollment, Certificate | COMPLETO |
| `jaraba_content_hub` | Content Hub | `/content-hub` | ContentArticle, ContentAuthor, ContentCategory | COMPLETO |
| `andalucia_ei` | Program Dashboard | andalucia_ei.dashboard | ProgramaParticipanteEi, Solicitud | COMPLETO |
| `demo` | Demo Sandbox | N/A | N/A (testing) | N/A |

### 2.2 Infraestructura existente en jaraba_addons

**Entidades:**
- `Addon` (ContentEntity): id, label, machine_name, description, addon_type (feature|storage|api_calls|support|custom), price_monthly, price_yearly, is_active, features_included (JSON), limits (JSON), tenant_id
- `AddonSubscription` (ContentEntity): id, addon_id, tenant_id, status (active|cancelled|expired|trial), billing_cycle (monthly|yearly), start_date, end_date, price_paid

**Servicios:**
- `AddonCatalogService`: getAvailableAddons(), getAddonsByType($type), getAddonPrice($id, $cycle)
- `AddonSubscriptionService`: subscribe(), cancel(), renew(), getTenantSubscriptions(), isAddonActive()

**Rutas existentes:**
- `GET /addons` — Catalogo publico (AddonCatalogController::catalog)
- `GET /addons/{addon_id}` — Detalle addon (AddonCatalogController::detail)
- `POST /api/v1/addons/{addon_id}/subscribe` — Subscribirse
- `POST /api/v1/addons/subscriptions/{id}/cancel` — Cancelar
- `GET /api/v1/addons/subscriptions` — Mis suscripciones

**Templates existentes:**
- `addons-catalog.html.twig` — Grid con filtros por tipo, cards con precio y CTA
- `addons-detail.html.twig` — Hero + pricing toggle + features + limits + CTA

**Lo que falta:** El campo `addon_type` solo acepta `feature|storage|api_calls|support|custom`. No tiene el valor `vertical`. Hay que anadirlo.

### 2.3 Feature gating existente

**FeatureAccessService (jaraba_billing):**
```php
public function canAccess(int $tenantId, string $feature): bool
// 1. Verifica si la feature esta en el plan base (PlanValidator)
// 2. Verifica si hay addon activo para esa feature (FEATURE_ADDON_MAP)
// Devuelve TRUE si cualquiera de las dos condiciones se cumple
```

**FEATURE_ADDON_MAP existente:**
```
crm_pipeline -> jaraba_crm
email_campaigns -> jaraba_email
social_calendar -> jaraba_social
ads_sync -> paid_ads_sync
pixels_manager -> retargeting_pixels
events_create -> events_webinars
experiments -> ab_testing
referral_codes -> referral_program
premium_blocks -> page_builder_premium
legal_search -> jaraba_legal_intelligence
verifactu_records -> jaraba_verifactu
facturae_invoices -> jaraba_facturae
einvoice_documents -> jaraba_einvoice_b2b
```

**Lo que falta:** El FEATURE_ADDON_MAP no tiene entradas para verticales. Necesita mapear features de cada vertical al addon correspondiente. Ejemplo: `merchant_portal -> comercioconecta`, `legal_cases -> jarabalex`.

### 2.4 Gaps entre "el codigo existe" y "el usuario lo experimenta"

| Gap | Evidencia | Impacto | Solucion |
|-----|-----------|---------|----------|
| Quick links del dashboard deshabilitados | `href="#"` con clase `--disabled` en template | Tenant no puede navegar a su portal de productos | Resolucion dinamica via TenantVerticalService |
| Etiqueta "Mis Productos" generica | Hardcoded en Twig, no contextual | Un abogado ve "Mis Productos" en vez de "Mis Casos" | Mapa VERTICAL_PRODUCT_MAP en controller |
| Iconos de emojis Unicode | Metrica icons usaban strings emoji | ICON-CONVENTION-001 violation | Migrado a jaraba_icon() arrays |
| No hay addon tipo `vertical` | addon_type solo acepta 5 valores | No se pueden vender verticales como addons | Anadir `vertical` a allowed_values |
| FeatureAccessService no evalua verticales | FEATURE_ADDON_MAP sin entradas de vertical | Activar addon no desbloquea rutas del vertical | Ampliar mapa con entradas por vertical |
| Dashboard no consulta addon subscriptions | getQuickLinks() solo mira vertical primario | Tenant con addon activo no ve su portal | Integrar TenantVerticalService |
| Catalogo `/addons` sin seccion de verticales | Solo muestra addons feature/storage | No se pueden descubrir/comprar verticales | Crear seccion premium de verticales |

---

## 3. Arquitectura de la Solucion

### 3.1 Modelo conceptual: Hub + Spokes

```
                    +-------------------+
                    |  TENANT ENTITY    |
                    |  vertical: agro   |  <-- Vertical PRIMARIO (1:1)
                    |  plan: Professional|
                    +-------------------+
                             |
                 +-----------+-----------+
                 |                       |
     +-----------+--------+    +--------+-----------+
     | AddonSubscription  |    | AddonSubscription  |
     | addon: comercio    |    | addon: content_hub |
     | status: active     |    | status: active     |
     | billing: monthly   |    | billing: yearly    |
     +--------------------+    +--------------------+
                 |                       |
     +-----------+--------+    +--------+-----------+
     | Addon              |    | Addon              |
     | type: vertical     |    | type: vertical     |
     | machine: comercio  |    | machine: content   |
     | price: EUR29/mes   |    | price: EUR15/mes   |
     +--------------------+    +--------------------+
```

**Resolucion en runtime:**

```php
// TenantVerticalService::getActiveVerticals($tenant)
$primaryVertical = $tenant->getVertical();  // agroconecta
$addonVerticals = $this->getActiveVerticalAddons($tenant->id());
// Returns: ['agroconecta', 'comercioconecta', 'jaraba_content_hub']
```

### 3.2 Flujo de datos

```
1. Tenant accede a /my-dashboard
2. TenantSelfServiceController::dashboard()
3. $quickLinks = $this->getQuickLinks($tenant)
4.   -> TenantVerticalService::getActiveVerticals($tenant)
5.     -> Vertical primario: agroconecta (desde Tenant.vertical)
6.     -> AddonSubscription query: tenant_id=X, status IN (active,trial)
7.     -> Addon entities filtradas por type=vertical
8.     -> Merge: [agroconecta, comercioconecta, jaraba_content_hub]
9.   -> Para cada vertical activo: resolver ruta + icono + etiqueta
10.  -> VERTICAL_PRODUCT_MAP lookup con routeExists() guard
11. Template itera quick_links[] con path() dinamico
```

### 3.3 Integracion con Feature Gates

La cadena de evaluacion se extiende para incluir verticales como addons:

```
FeatureAccessService::canAccess($tenantId, 'merchant_portal')
  |
  +-> PlanValidator: Es 'merchant_portal' feature del plan base?
  |   -> SaasPlan.features contiene 'merchant_portal'? NO (es de otro vertical)
  |
  +-> AddonCheck: Hay addon activo que desbloquea 'merchant_portal'?
  |   -> FEATURE_ADDON_MAP: 'merchant_portal' => 'comercioconecta'
  |   -> AddonSubscriptionService::isAddonActive('comercioconecta', $tenantId)
  |   -> AddonSubscription con addon.machine_name='comercioconecta' y status='active'?
  |   -> SI -> PERMITIR
  |
  +-> Resultado: ACCESO PERMITIDO
```

**Nuevas entradas en FEATURE_ADDON_MAP:**

```php
// Verticales como features desbloqueables
'merchant_portal' => 'comercioconecta',
'merchant_products' => 'comercioconecta',
'provider_portal' => 'serviciosconecta',
'service_offerings' => 'serviciosconecta',
'producer_portal' => 'agroconecta',
'agro_products' => 'agroconecta',
'employer_portal' => 'empleabilidad',
'job_postings' => 'empleabilidad',
'business_canvas' => 'emprendimiento',
'entrepreneur_tools' => 'emprendimiento',
'legal_cases' => 'jarabalex',
'legal_billing' => 'jarabalex',
'lms_courses' => 'formacion',
'course_creation' => 'formacion',
'content_articles' => 'jaraba_content_hub',
'blog_management' => 'jaraba_content_hub',
'ei_program' => 'andalucia_ei',
```

### 3.4 Cascade de resolucion de acceso

Cuando un usuario intenta acceder a `/mi-comercio/productos`:

```
1. Symfony Router: ruta existe, permission: 'edit own merchant profile'
2. Drupal Access Check:
   a) User tiene permission directa? -> Evaluar
   b) Si no, FeatureGateMiddleware (si existe):
      - Vertical de la ruta: comercioconecta
      - Tenant del usuario: TenantContextService::getCurrentTenant()
      - TenantVerticalService::hasVertical($tenant, 'comercioconecta')
        -> Es el primario? NO (primario es agroconecta)
        -> Tiene addon activo? SI (AddonSubscription status=active)
      - PERMITIR
3. Controller ejecuta normalmente
```

### 3.5 Integracion Stripe: Subscription Items multi-linea

**Fase 5 (futuro):** Cuando un tenant activa un vertical secundario:

```
Stripe Subscription actual:
  - Item 1: Professional AgroConecta (price_id: price_agro_pro_monthly) = EUR79/mes

Despues de activar addon ComercioConecta:
  - Item 1: Professional AgroConecta = EUR79/mes
  - Item 2: Addon ComercioConecta (price_id: price_addon_comercio_monthly) = EUR29/mes
  - Total: EUR108/mes

API Call: Stripe::subscriptionItems()->create([
  'subscription' => $tenant->getStripeSubscriptionId(),
  'price' => $addonStripePriceId,
  'quantity' => 1,
])
```

### 3.6 Quick Links vertical-aware en el Dashboard

El controller `TenantSelfServiceController` ya implementa (commit actual):

**Constante VERTICAL_PRODUCT_MAP:**

| Vertical | Etiqueta | Ruta | Icono | Categoria Icono |
|----------|----------|------|-------|-----------------|
| comercioconecta | Mis Productos | jaraba_comercio_conecta.merchant_portal.products | package | ui |
| serviciosconecta | Mis Servicios | jaraba_servicios_conecta.provider_portal.offerings | briefcase | ui |
| agroconecta | Mis Productos | jaraba_agroconecta_core.producer.products | package | ui |
| empleabilidad | Mis Ofertas | jaraba_job_board.employer_jobs | briefcase | ui |
| emprendimiento | Mis Proyectos | jaraba_business_tools.entrepreneur_dashboard | lightbulb | ui |
| jarabalex | Mis Casos | jaraba_legal_cases.dashboard | scale-balance | legal |
| formacion | Mis Cursos | jaraba_lms.my_courses | graduation-cap | ui |
| jaraba_content_hub | Mi Contenido | jaraba_content_hub.dashboard.frontend | document | ui |
| andalucia_ei | Mi Programa | jaraba_andalucia_ei.dashboard | clipboard | ui |
| (fallback) | Mi Sitio | jaraba_site_builder.frontend.dashboard | layout-template | ui |

**Todas las 13 rutas verificadas como existentes** via `routeProvider->getRouteByName()`.

### 3.7 Diagrama de entidades y relaciones

```
+------------------+     1:N      +---------------------+
|    Tenant        |------------->| AddonSubscription   |
| - vertical (1:1) |              | - addon_id (ref)    |
| - plan (ref)     |              | - tenant_id (ref)   |
| - stripe_*       |              | - status            |
+------------------+              | - billing_cycle     |
        |                         | - price_paid        |
        |                         +---------------------+
        |                                  |
        | 1:1                         N:1  |
        v                                  v
+------------------+              +------------------+
|    Vertical      |              |     Addon        |
| - machine_name   |              | - machine_name   |
| - enabled_feats  |              | - addon_type     |  <-- NUEVO: 'vertical'
| - theme_settings |              | - price_monthly  |
+------------------+              | - price_yearly   |
                                  | - features_incl  |
                                  | - limits (JSON)  |
                                  | - vertical_ref   |  <-- NUEVO campo
                                  +------------------+
                                         |
                                    N:1  |
                                         v
                                  +------------------+
                                  | FreemiumVertical |
                                  |    Limit         |
                                  | - vertical       |
                                  | - plan           |
                                  | - feature_key    |
                                  | - limit_value    |
                                  +------------------+
```

---

## 4. Fases de Implementacion

### 4.1 Fase 1: Vertical Addon Entities y Datos Seed

**Objetivo:** Crear los Addon entities de tipo `vertical` para los 9 verticales comercializables y ampliar el esquema del Addon entity.

**Esfuerzo:** 8-10h

**Tareas:**

1. **Ampliar campo `addon_type` en Addon entity:**
   - Anadir valor `vertical` a la lista `allowed_values` en `baseFieldDefinitions()`
   - hook_update_N() con `updateFieldStorageDefinition()` (UPDATE-FIELD-DEF-001: requiere `setName()` + `setTargetEntityTypeId()`)
   - try-catch con `\Throwable` (UPDATE-HOOK-CATCH-001)

2. **Anadir campo `vertical_ref` al Addon entity:**
   - entity_reference a `vertical` entity, opcional (solo para addons tipo vertical)
   - Permite vincular un addon con la entidad Vertical canonica
   - hook_update_N() con `installFieldStorageDefinition()`

3. **Crear 9 Addon entities de tipo vertical:**
   - Uno por cada vertical comercializable (excluyendo `demo`)
   - Cada uno con: label, machine_name que matchea el vertical, descripcion orientada a venta, precios mensuales/anuales, features_included JSON con las capabilities que desbloquea, limits JSON con los limites del addon
   - Crear via config/install YAML o hook_update_N()

4. **Datos seed (precios sugeridos):**

| Vertical Addon | machine_name | Precio/mes | Precio/ano | Features Desbloqueadas |
|----------------|--------------|------------|------------|----------------------|
| ComercioConecta | comercioconecta | EUR29 | EUR290 | merchant_portal, merchant_products, checkout, inventory |
| ServiciosConecta | serviciosconecta | EUR25 | EUR250 | provider_portal, service_offerings, bookings, calendar |
| AgroConecta | agroconecta | EUR25 | EUR250 | producer_portal, agro_products, certifications, traceability |
| Empleabilidad | empleabilidad | EUR19 | EUR190 | employer_portal, job_postings, candidate_search, applications |
| Emprendimiento | emprendimiento | EUR15 | EUR150 | business_canvas, mvp_tracker, financial_projections |
| JarabaLex | jarabalex | EUR35 | EUR350 | legal_cases, legal_billing, legal_calendar, legal_vault |
| Formacion | formacion | EUR19 | EUR190 | lms_courses, course_creation, certificates, gamification |
| Content Hub | jaraba_content_hub | EUR15 | EUR150 | content_articles, blog_management, editorial_workflow, rss |
| Andalucia+ei | andalucia_ei | EUR19 | EUR190 | ei_program, participants, solicitudes, sto_export |

**Directrices aplicables:**
- UPDATE-FIELD-DEF-001: setName() + setTargetEntityTypeId() en updateFieldStorageDefinition
- UPDATE-HOOK-CATCH-001: try-catch con \Throwable
- UPDATE-HOOK-REQUIRED-001: hook_update_N() obligatorio
- ENTITY-FK-001: entity_reference para vertical_ref (mismo modulo = reference)

---

### 4.2 Fase 2: TenantVerticalService — Resolucion Multi-Vertical

**Objetivo:** Crear servicio central que resuelve todos los verticales activos de un tenant (primario + addons).

**Esfuerzo:** 6-8h

**Tareas:**

1. **Crear `TenantVerticalService` en ecosistema_jaraba_core:**

```php
namespace Drupal\ecosistema_jaraba_core\Service;

class TenantVerticalService {

  /**
   * Obtiene todos los verticales activos del tenant.
   *
   * Combina el vertical primario (campo Tenant.vertical) con
   * los addon subscriptions activos de tipo 'vertical'.
   *
   * @return string[]
   *   Array de machine_names de verticales activos.
   *   El primero es siempre el primario.
   */
  public function getActiveVerticals(TenantInterface $tenant): array;

  /**
   * Verifica si un tenant tiene acceso a un vertical especifico.
   *
   * Evalua: (a) es el vertical primario, o (b) tiene addon activo.
   */
  public function hasVertical(TenantInterface $tenant, string $verticalMachineName): bool;

  /**
   * Obtiene los addon subscriptions de tipo vertical activos.
   */
  public function getVerticalAddonSubscriptions(int $tenantId): array;

  /**
   * Obtiene los verticales disponibles para comprar.
   *
   * Excluye el primario y los ya activos.
   */
  public function getAvailableVerticals(TenantInterface $tenant): array;
}
```

2. **Registrar en services.yml:**
   - Dependencias: `@entity_type.manager`, `@?ecosistema_jaraba_core.tenant_context`, `@logger.channel.ecosistema_jaraba_core`
   - OPTIONAL-CROSSMODULE-001: usar `@?` para tenant_context si es cross-modulo
   - LOGGER-INJECT-001: `@logger.channel.*` con `LoggerInterface` en constructor

3. **Integrar con `TenantSelfServiceController::getQuickLinks()`:**
   - Inyectar TenantVerticalService via create()
   - Iterar sobre `getActiveVerticals()` en vez de solo el primario
   - Generar un quick link por cada vertical activo
   - Mantener "Cambiar Plan", "Configuracion" y "Soporte" como fijos

**Directrices aplicables:**
- OPTIONAL-CROSSMODULE-001: @? para dependencias cross-modulo
- LOGGER-INJECT-001: LoggerInterface directa, no LoggerChannelFactory
- PHANTOM-ARG-001: args en services.yml deben coincidir con constructor
- SERVICE-CALL-CONTRACT-001: firmas de metodo deben coincidir

---

### 4.3 Fase 3: Dashboard Quick Links Vertical-Aware

**Objetivo:** El dashboard muestra acciones rapidas para CADA vertical activo del tenant, no solo el primario.

**Esfuerzo:** 4-6h

**Tareas:**

1. **Actualizar `TenantSelfServiceController::getQuickLinks()`:**
   - Para cada vertical activo (primario + addons), generar un quick link
   - El primario se marca visualmente como principal
   - Los addons se muestran como secundarios
   - Verticales disponibles (no contratados) se muestran deshabilitados con CTA "Activar"

2. **Actualizar template `tenant-self-service-dashboard.html.twig`:**
   - Seccion "Acciones Rapidas" dividida en: "Tu Ecosistema" (activos) + "Amplia tu Plataforma" (disponibles)
   - Cards activas con enlace directo al portal
   - Cards disponibles con enlace a `/addons/{addon_id}` para activar
   - Usar `{% trans %}` para todas las etiquetas
   - Usar `jaraba_icon()` con variante duotone para todos los iconos

3. **Actualizar SCSS `routes/tenant-dashboard.scss`:**
   - Estilos para cards activas vs disponibles
   - Card disponible: opacity reducida, borde discontinuo, badge "Disponible"
   - `var(--ej-*)` para todos los colores (CSS-VAR-ALL-COLORS-001)
   - Responsive: 4 columnas desktop, 2 tablet, 1 mobile

4. **Compilar SCSS:**
   - `npx sass scss/routes/tenant-dashboard.scss css/routes/tenant-dashboard.css --style=compressed --no-source-map`
   - Verificar timestamp CSS > SCSS (SCSS-COMPILE-VERIFY-001)

**Template pattern (ejemplo):**
```twig
{# Ecosistema activo del tenant #}
<section class="tenant-quick-links">
  <h2 class="tenant-section-title">{% trans %}Tu Ecosistema{% endtrans %}</h2>
  <div class="tenant-quick-links__grid">
    {% for link in active_links %}
      <a href="{{ path(link.route, link.route_params) }}"
         class="tenant-quick-link{% if link.is_primary %} tenant-quick-link--primary{% endif %}">
        <span class="tenant-quick-link__icon" aria-hidden="true">
          {{ jaraba_icon(link.icon.category, link.icon.name, { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
        </span>
        <span class="tenant-quick-link__label">{{ link.label }}</span>
        {% if link.is_primary %}
          <span class="tenant-quick-link__badge">{% trans %}Principal{% endtrans %}</span>
        {% endif %}
      </a>
    {% endfor %}
  </div>
</section>

{# Verticales disponibles para activar #}
{% if available_links|length > 0 %}
<section class="tenant-quick-links tenant-quick-links--available">
  <h2 class="tenant-section-title">{% trans %}Amplia tu Plataforma{% endtrans %}</h2>
  <div class="tenant-quick-links__grid">
    {% for link in available_links %}
      <a href="{{ path('jaraba_addons.catalog.detail', { addon_id: link.addon_id }) }}"
         class="tenant-quick-link tenant-quick-link--available">
        <span class="tenant-quick-link__icon" aria-hidden="true">
          {{ jaraba_icon(link.icon.category, link.icon.name, { variant: 'duotone', color: 'neutral', size: '24px' }) }}
        </span>
        <span class="tenant-quick-link__label">{{ link.label }}</span>
        <span class="tenant-quick-link__price">{{ link.price }}</span>
      </a>
    {% endfor %}
  </div>
</section>
{% endif %}
```

**Directrices aplicables:**
- ZERO-REGION-001: sin CSS inline, sin regions
- CSS-VAR-ALL-COLORS-001: todos los colores via var(--ej-*)
- ICON-CONVENTION-001: jaraba_icon() con duotone
- ICON-COLOR-001: solo colores de paleta Jaraba
- i18n: {% trans %} bloque, NO filtro |t
- SCSS-COMPILE-VERIFY-001: verificar timestamp post-compilacion
- SCSS-COLORMIX-001: color-mix() en vez de rgba() con hex

---

### 4.4 Fase 4: Catalogo de Verticales Premium (Frontend)

**Objetivo:** Seccion premium en `/addons` que muestra los verticales disponibles como productos de alto valor, diferenciados visualmente de los addons simples (feature/storage).

**Esfuerzo:** 12-16h

**Tareas:**

1. **Actualizar `AddonCatalogController::catalog()`:**
   - Separar addons por tipo: verticales (type=vertical) vs features (otros tipos)
   - Pasar ambos arrays al template
   - Incluir datos del vertical entity referenciado (description extendida, icon, color)

2. **Crear parcial `_addon-vertical-card.html.twig`:**
   - Card premium con glassmorphism (patron establecido en pricing-hub)
   - Icono duotone del vertical en grande
   - Nombre + descripcion + precio
   - Badge con vertical color
   - CTA "Activar" o "Activo" segun estado
   - Reutilizable desde catalogo Y desde dashboard
   - Variables: addon, is_subscribed, vertical_icon, vertical_color

3. **Actualizar `addons-catalog.html.twig`:**
   - Seccion hero de verticales (cards grandes, 2 columnas)
   - Separador visual
   - Seccion de addons feature/storage (cards pequenas, grid 3-4 columnas)
   - Texto introductorio traducible para cada seccion

4. **Crear SCSS `routes/addons-catalog.scss`:**
   - Usar 5-layer token cascade (CSS-VAR-ALL-COLORS-001)
   - Glassmorphism cards (color-mix, backdrop-filter, springy transitions)
   - Shine sweep ::before (patron establecido en tenant-settings)
   - Responsive mobile-first
   - `@use '../variables' as *;` (SCSS-001)
   - Dart Sass moderno: @use, color-mix(), NO @import, NO rgba() con hex

5. **Registrar library `route-addons-catalog` en libraries.yml:**
   - CSS: `css/routes/addons-catalog.css`
   - Dependencias: `ecosistema_jaraba_theme/global-styling`

6. **Registrar en `hook_page_attachments_alter()`:**
   - Route match: `jaraba_addons.catalog` -> `route-addons-catalog`

7. **Crear page template `page--addons.html.twig` si no existe:**
   - Zero-region layout
   - {% include 'partials/_header.html.twig' %}
   - {{ clean_content }}
   - {% include 'partials/_footer.html.twig' %}

8. **Compilar SCSS y verificar:**
   - `npx sass scss/routes/addons-catalog.scss css/routes/addons-catalog.css --style=compressed --no-source-map`
   - Verificar timestamp CSS > SCSS
   - `lando drush cr`

**Directrices aplicables:**
- ZERO-REGION-001: template limpio sin page.content
- CSS-VAR-ALL-COLORS-001: todos los colores via tokens
- SCSS-001: @use crea scope aislado, cada parcial incluye variables
- SCSS-ENTRY-CONSOLIDATION-001: no name.scss + _name.scss en mismo directorio
- SCSS-COLORMIX-001: color-mix() para transparencias
- ICON-CONVENTION-001 + ICON-DUOTONE-001: jaraba_icon con duotone
- i18n: todos los textos traducibles
- ROUTE-LANGPREFIX-001: URLs via path() en Twig
- body class via hook_preprocess_html()

---

### 4.5 Fase 5: Integracion Stripe Subscription Items

**Objetivo:** Cuando un tenant activa un vertical secundario, se anade como line item a su Stripe Subscription existente.

**Esfuerzo:** 10-12h

**Tareas:**

1. **Anadir campo `stripe_price_id` al Addon entity (si no existe):**
   - String field para almacenar el Stripe Price ID del addon
   - hook_update_N() para instalar campo

2. **Crear `VerticalAddonBillingService` en jaraba_billing:**
   - `activateVerticalAddon($tenantId, $addonId)`:
     - Verificar tenant tiene Stripe subscription activa
     - Obtener stripe_price_id del Addon entity
     - Llamar Stripe API: `SubscriptionItem::create()`
     - Crear AddonSubscription entity (via AddonSubscriptionService)
     - Registrar FinancialTransaction en FOC
   - `deactivateVerticalAddon($subscriptionId)`:
     - Obtener Stripe subscription item ID
     - Llamar Stripe API: `SubscriptionItem::delete()`
     - Cancelar AddonSubscription
     - Registrar FinancialTransaction

3. **Webhooks Stripe:**
   - Manejar `invoice.payment_succeeded` para addons
   - Manejar `customer.subscription.updated` (item added/removed)
   - AUDIT-SEC-001: HMAC + hash_equals() para validacion de webhook

4. **Config via settings.secrets.php:**
   - STRIPE-ENV-UNIFY-001: Usar getenv() existente, sin nuevos env vars

**Directrices aplicables:**
- STRIPE-ENV-UNIFY-001: secrets via getenv() en settings.secrets.php
- AUDIT-SEC-001: webhooks con HMAC + hash_equals()
- SECRET-MGMT-001: nunca secrets en config/sync
- OPTIONAL-CROSSMODULE-001: @? para jaraba_foc dependency

---

### 4.6 Fase 6: Feature Gate Unificado (Plan + Addons)

**Objetivo:** FeatureAccessService evalua tanto el plan base como los addon subscriptions de tipo vertical para determinar acceso a features.

**Esfuerzo:** 8-10h

**Tareas:**

1. **Ampliar FEATURE_ADDON_MAP en FeatureAccessService:**
   - Anadir 18 entradas de features de verticales (ver seccion 3.3)

2. **Crear VerticalAccessMiddleware (opcional):**
   - Evalua en cada request si la ruta pertenece a un vertical
   - Verifica que el tenant tiene ese vertical activo (primario o addon)
   - Redirige a `/addons/{addon_id}` si no tiene acceso (upsell)

3. **Integrar con FeatureGateRouterService:**
   - Antes de despachar al gate vertical-especifico, verificar que el tenant tiene el vertical activo
   - Si no lo tiene, devolver FeatureGateResult con `upgrade_path` al addon

4. **Actualizar `_upgrade-trigger.html.twig`:**
   - Cuando un tenant intenta acceder a una feature de un vertical no contratado
   - Mostrar modal con: nombre del vertical, precio, features incluidas, CTA "Activar"
   - Usar parcial existente con variables configurables

**Directrices aplicables:**
- TENANT-001: toda query filtra por tenant
- ACCESS-STRICT-001: comparaciones con (int) === (int)
- TENANT-ISOLATION-ACCESS-001: verificar tenant match

---

### 4.7 Fase 7: Cross-Vertical AI Intelligence

**Objetivo:** Los agentes AI del tenant acceden a datos de todos los verticales activos, no solo del primario.

**Esfuerzo:** 12-16h

**Tareas:**

1. **Actualizar SmartBaseAgent::getContext():**
   - Incluir datos de todos los verticales activos del tenant
   - TenantVerticalService::getActiveVerticals() como fuente
   - Context window management para multiples verticales (ContextWindowManager)

2. **Actualizar ModelRouterService:**
   - Tier selection considera complejidad cross-vertical
   - Queries que cruzan verticales escalan a balanced/premium

3. **Agentes cross-vertical:**
   - SmartMarketingAgent: generar contenido para todos los canales del tenant
   - SmartContentWriter: escribir articulos sobre productos de cualquier vertical
   - CustomerExperience: responder preguntas sobre cualquier servicio del tenant

4. **SemanticCacheService:**
   - Namespace de cache incluye vertical context
   - Queries cross-vertical invalidan caches de verticales individuales

**Directrices aplicables:**
- AGENT-GEN2-PATTERN-001: override doExecute(), not execute()
- MODEL-ROUTING-CONFIG-001: tier config en YAML
- TOOL-USE-LOOP-001: max 5 iteraciones
- AI-GUARDRAILS-PII-001: detectar PII en datos cross-vertical

---

## 5. Especificaciones Tecnicas Detalladas

### 5.1 Entidad VerticalAddon: Definicion completa de campos

Reutiliza la entidad `Addon` existente con tipo `vertical`. Campos nuevos:

| Campo | Tipo | Descripcion | Requerido |
|-------|------|-------------|-----------|
| vertical_ref | entity_reference (vertical) | Vincula al Vertical entity canonico | Solo si type=vertical |
| stripe_price_id | string (100) | Stripe Price ID para billing | No (Fase 5) |

Campos existentes reutilizados:
| Campo | Uso para Vertical Addon |
|-------|------------------------|
| label | "ComercioConecta — Marketplace Local" |
| machine_name | "comercioconecta" (coincide con Vertical.machine_name) |
| description | Descripcion comercial orientada a conversion |
| addon_type | "vertical" (NUEVO valor) |
| price_monthly | EUR25-35 segun vertical |
| price_yearly | EUR250-350 (descuento ~16%) |
| is_active | TRUE para verticales publicados |
| features_included | JSON: ["merchant_portal","merchant_products","checkout","inventory"] |
| limits | JSON: {"products": 100, "orders_month": -1, "storage_gb": 10} |

### 5.2 TenantVerticalService: API publica

```php
namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Servicio central para resolucion de verticales activos de un tenant.
 *
 * Combina el vertical primario (Tenant.vertical) con los addon
 * subscriptions activos de tipo 'vertical' para determinar el
 * ecosistema completo de funcionalidades del tenant.
 *
 * USO PRINCIPAL:
 * - Dashboard quick links (TenantSelfServiceController)
 * - Feature gates (FeatureAccessService)
 * - AI agent context (SmartBaseAgent)
 * - Route access middleware
 *
 * REGLA: El vertical primario SIEMPRE aparece primero en el array.
 * REGLA: Los addons se resuelven via AddonSubscription con status
 *        IN ('active', 'trial') y Addon.addon_type = 'vertical'.
 */
class TenantVerticalService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?TenantContextService $tenantContext = NULL,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene todos los machine_names de verticales activos.
   *
   * @return string[]
   *   Array ordenado: [0] = primario, [1..N] = addons.
   */
  public function getActiveVerticals(TenantInterface $tenant): array;

  /**
   * Verifica si un tenant tiene acceso a un vertical.
   */
  public function hasVertical(TenantInterface $tenant, string $machineName): bool;

  /**
   * Obtiene addon subscriptions activas de tipo vertical.
   *
   * @return \Drupal\jaraba_addons\Entity\AddonSubscriptionInterface[]
   */
  public function getVerticalAddonSubscriptions(int $tenantId): array;

  /**
   * Obtiene verticales NO activos disponibles para comprar.
   *
   * Excluye el primario y los ya activos. Devuelve Addon entities
   * con type=vertical y is_active=TRUE.
   *
   * @return \Drupal\jaraba_addons\Entity\AddonInterface[]
   */
  public function getAvailableVerticals(TenantInterface $tenant): array;

  /**
   * Obtiene el Addon entity asociado a un vertical.
   *
   * Busca por Addon.machine_name = $verticalMachineName
   * y Addon.addon_type = 'vertical'.
   *
   * @return \Drupal\jaraba_addons\Entity\AddonInterface|null
   */
  public function getVerticalAddon(string $verticalMachineName): ?AddonInterface;
}
```

### 5.3 Quick Links: Mapa completo vertical -> ruta -> icono -> etiqueta

Definido como constante en `TenantSelfServiceController`:

```php
protected const VERTICAL_PRODUCT_MAP = [
    'comercioconecta' => [
        'route' => 'jaraba_comercio_conecta.merchant_portal.products',
        'label' => 'Mis Productos',
        'icon' => 'package',
        // icon_category default: 'ui'
    ],
    'serviciosconecta' => [
        'route' => 'jaraba_servicios_conecta.provider_portal.offerings',
        'label' => 'Mis Servicios',
        'icon' => 'briefcase',
    ],
    'agroconecta' => [
        'route' => 'jaraba_agroconecta_core.producer.products',
        'label' => 'Mis Productos',
        'icon' => 'package',
    ],
    'empleabilidad' => [
        'route' => 'jaraba_job_board.employer_jobs',
        'label' => 'Mis Ofertas',
        'icon' => 'briefcase',
    ],
    'emprendimiento' => [
        'route' => 'jaraba_business_tools.entrepreneur_dashboard',
        'label' => 'Mis Proyectos',
        'icon' => 'lightbulb',
    ],
    'jarabalex' => [
        'route' => 'jaraba_legal_cases.dashboard',
        'label' => 'Mis Casos',
        'icon' => 'scale-balance',
        'icon_category' => 'legal',
    ],
    'formacion' => [
        'route' => 'jaraba_lms.my_courses',
        'label' => 'Mis Cursos',
        'icon' => 'graduation-cap',
    ],
    'jaraba_content_hub' => [
        'route' => 'jaraba_content_hub.dashboard.frontend',
        'label' => 'Mi Contenido',
        'icon' => 'document',
    ],
    'andalucia_ei' => [
        'route' => 'jaraba_andalucia_ei.dashboard',
        'label' => 'Mi Programa',
        'icon' => 'clipboard',
    ],
];
```

Todos los iconos existen en formato duotone SVG verificado:
- `ui/package-duotone.svg`, `ui/briefcase-duotone.svg`, `ui/lightbulb-duotone.svg`
- `ui/graduation-cap-duotone.svg`, `ui/document-duotone.svg`, `ui/clipboard-duotone.svg`
- `legal/scale-balance-duotone.svg`

### 5.4 Templates Twig: Estructura y variables

**Reglas aplicadas a TODOS los templates de este plan:**
- `{% trans %}texto{% endtrans %}` para TODOS los textos visibles (NO filtro |t)
- `{{ jaraba_icon('category', 'name', { variant: 'duotone', ... }) }}` para TODOS los iconos
- `{{ path('route.name', params) }}` para TODAS las URLs (ROUTE-LANGPREFIX-001)
- Sin CSS inline (ZERO-REGION-001)
- Sin `{{ page.content }}` — usar `{{ clean_content }}`
- aria-labels en interactivos, headings jerarquicos
- `{% include %}` para parciales reutilizables

### 5.5 SCSS: Tokens y componentes

**Reglas SCSS para TODOS los archivos de este plan:**
- `@use '../variables' as *;` en CADA parcial (SCSS-001)
- `@use 'sass:color';` para funciones de color (Dart Sass moderno)
- `color-mix(in srgb, var(--ej-*) %, transparent)` para transparencias (SCSS-COLORMIX-001)
- NUNCA `@import` (deprecated)
- NUNCA `rgba(#hex, opacity)` — usar `color-mix()`
- NUNCA hex hardcoded — siempre `var(--ej-*, $fallback)`
- Responsive breakpoints via mixins existentes
- Mobile-first approach
- Compilacion: `npx sass file.scss output.css --style=compressed --no-source-map`
- Verificacion post-compilacion: timestamp CSS > SCSS (SCSS-COMPILE-VERIFY-001)

### 5.6 JavaScript: Interacciones del catalogo

**Reglas JS:**
- Vanilla JS + `Drupal.behaviors` (NO React/Vue/Angular)
- `Drupal.t('string')` para traducciones
- `drupalSettings` para URLs de API (ROUTE-LANGPREFIX-001)
- `Drupal.checkPlain()` para datos de API en innerHTML (INNERHTML-XSS-001)
- CSRF token cacheado via `/session/token` (CSRF-JS-CACHE-001)

### 5.7 hook_update_N(): Migraciones requeridas

```php
/**
 * Add 'vertical' value to addon_type field and install vertical_ref field.
 */
function jaraba_addons_update_10002(): string {
  try {
    $manager = \Drupal::entityDefinitionUpdateManager();

    // 1. Update addon_type allowed values
    $storage_def = $manager->getFieldStorageDefinition('addon_type', 'addon');
    $storage_def->setName('addon_type');
    $storage_def->setTargetEntityTypeId('addon');
    $settings = $storage_def->getSettings();
    $settings['allowed_values']['vertical'] = 'Vertical';
    $storage_def->setSettings($settings);
    $manager->updateFieldStorageDefinition($storage_def);

    // 2. Install vertical_ref field
    $vertical_ref = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Vertical asociado'))
      ->setDescription(t('Vincula este addon al vertical canonico.'))
      ->setSetting('target_type', 'vertical')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $manager->installFieldStorageDefinition(
      'vertical_ref', 'addon', 'jaraba_addons', $vertical_ref
    );

    return 'Added vertical type to addon_type and installed vertical_ref field.';
  }
  catch (\Throwable $e) {
    return 'Error: ' . $e->getMessage();
  }
}
```

---

## 6. Tabla de Archivos Creados/Modificados

| Archivo | Accion | Fase | Descripcion |
|---------|--------|------|-------------|
| `jaraba_addons/src/Entity/Addon.php` | MODIFICAR | 1 | Anadir `vertical` a addon_type, nuevo campo vertical_ref |
| `jaraba_addons/jaraba_addons.install` | MODIFICAR | 1 | hook_update_10002() para schema migration |
| `ecosistema_jaraba_core/src/Service/TenantVerticalService.php` | CREAR | 2 | Servicio resolucion multi-vertical |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | MODIFICAR | 2 | Registrar TenantVerticalService |
| `ecosistema_jaraba_core/src/Controller/TenantSelfServiceController.php` | MODIFICAR | 3 | Inyectar TenantVerticalService, multi-vertical quick links |
| `ecosistema_jaraba_core/templates/tenant-self-service-dashboard.html.twig` | MODIFICAR | 3 | Seccion "Tu Ecosistema" + "Amplia tu Plataforma" |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.module` | MODIFICAR | 3 | Variables: active_links, available_links |
| `ecosistema_jaraba_theme/scss/routes/tenant-dashboard.scss` | MODIFICAR | 3 | Cards activas vs disponibles |
| `ecosistema_jaraba_theme/css/routes/tenant-dashboard.css` | REGENERAR | 3 | Compilacion SCSS |
| `ecosistema_jaraba_theme/templates/partials/_addon-vertical-card.html.twig` | CREAR | 4 | Parcial reutilizable card vertical |
| `jaraba_addons/src/Controller/AddonCatalogController.php` | MODIFICAR | 4 | Separar verticales de features en catalog |
| `jaraba_addons/templates/addons-catalog.html.twig` | MODIFICAR | 4 | Seccion premium verticales + seccion features |
| `ecosistema_jaraba_theme/scss/routes/addons-catalog.scss` | CREAR | 4 | Estilos premium catalogo |
| `ecosistema_jaraba_theme/css/routes/addons-catalog.css` | CREAR | 4 | Compilacion SCSS |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml` | MODIFICAR | 4 | Library route-addons-catalog |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | MODIFICAR | 4 | Route library attachment + page template suggestion |
| `ecosistema_jaraba_theme/templates/page--addons.html.twig` | CREAR | 4 | Zero-region page template |
| `jaraba_billing/src/Service/VerticalAddonBillingService.php` | CREAR | 5 | Stripe subscription item management |
| `jaraba_billing/src/Service/FeatureAccessService.php` | MODIFICAR | 6 | Ampliar FEATURE_ADDON_MAP con verticales |
| `ecosistema_jaraba_core/src/Service/FeatureGateRouterService.php` | MODIFICAR | 6 | Verificar vertical activo antes de despachar |

---

## 7. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Codigo | Seccion del Plan | Estado |
|----------------|--------|------------------|--------|
| Doc 158 — Platform Vertical Pricing Matrix | PRICING-MATRIX-158 | 4.1 (Datos seed precios) | ALINEADO |
| REM-PRECIOS-001 — Arquitectura Precios Configurables | REM-PRECIOS-001 | 3.5 (Stripe) | EXTIENDE |
| VERT-PRICING-001 — Verticalizacion Planes | VERT-PRICING-001 | 3.3 (Feature gates) | COMPLEMENTA |
| Doc 177 — Federated Site Builder | DOC-177 | 3.6 (Quick links fallback) | COMPATIBLE |
| Doc 178 — MetaSite Architecture | DOC-178 | 4.4 (Catalogo frontend) | COMPATIBLE |
| Doc 183 — PLG Feature Upgrade Triggers | DOC-183 | 4.6 (Upgrade trigger) | REUTILIZA |
| Sprint 3 — Remediation Architecture | SPRINT-3 | 5.7 (Migrations) | CUMPLE |
| GrapesJS Spec — Canvas Architecture | GRAPESJS-SPEC | N/A (no afecta canvas) | NO IMPACTA |

---

## 8. Tabla de Cumplimiento de Directrices

| Directriz | Codigo | Cumplimiento | Notas |
|-----------|--------|--------------|-------|
| Multi-tenant isolation | TENANT-001 | OBLIGATORIO | Toda query filtra por tenant_id |
| Tenant bridge | TENANT-BRIDGE-001 | OBLIGATORIO | Usar TenantBridgeService |
| Access control | TENANT-ISOLATION-ACCESS-001 | OBLIGATORIO | Verificar tenant match en AccessControlHandler |
| CSS variables | CSS-VAR-ALL-COLORS-001 | OBLIGATORIO | Todos los colores via var(--ej-*) |
| Iconos duotone | ICON-CONVENTION-001 / ICON-DUOTONE-001 | OBLIGATORIO | jaraba_icon() con variant duotone |
| Colores de paleta | ICON-COLOR-001 | OBLIGATORIO | Solo azul-corporativo, naranja-impulso, verde-innovacion, white, neutral |
| Traducciones | i18n ({% trans %}) | OBLIGATORIO | Bloque, NO filtro |t |
| SCSS moderno | SCSS-001, SCSS-COLORMIX-001 | OBLIGATORIO | @use, color-mix(), NO @import, NO rgba(hex) |
| Compilacion SCSS | SCSS-COMPILE-VERIFY-001 | OBLIGATORIO | Verificar timestamp post-compilacion |
| Zero region | ZERO-REGION-001 | OBLIGATORIO | Sin page.content, sin CSS inline |
| Slide panel | SLIDE-PANEL-RENDER-001 | SI APLICA | Forms de addon subscription via slide-panel |
| Form cache | FORM-CACHE-001 | SI APLICA | No setCached(TRUE) incondicional |
| Premium forms | PREMIUM-FORMS-PATTERN-001 | SI APLICA | Si se crean forms de entity |
| Controller readonly | CONTROLLER-READONLY-001 | SI APLICA | No readonly en propiedades heredadas |
| Rutas con langprefix | ROUTE-LANGPREFIX-001 | OBLIGATORIO | path() en Twig, Url::fromRoute() en PHP |
| Optional cross-module | OPTIONAL-CROSSMODULE-001 | OBLIGATORIO | @? para dependencias cross-modulo |
| Logger injection | LOGGER-INJECT-001 | OBLIGATORIO | @logger.channel.* con LoggerInterface |
| Phantom args | PHANTOM-ARG-001 | OBLIGATORIO | Args YAML = params PHP |
| Circular deps | CONTAINER-DEPS-002 | OBLIGATORIO | Verificar con validate-circular-deps.php |
| Update hooks | UPDATE-HOOK-REQUIRED-001 | OBLIGATORIO | hook_update_N() para schema changes |
| Update field def | UPDATE-FIELD-DEF-001 | OBLIGATORIO | setName() + setTargetEntityTypeId() |
| Update hook catch | UPDATE-HOOK-CATCH-001 | OBLIGATORIO | \Throwable, no \Exception |
| Secret management | SECRET-MGMT-001 | OBLIGATORIO | Stripe via getenv() |
| Stripe env | STRIPE-ENV-UNIFY-001 | OBLIGATORIO | settings.secrets.php |
| Webhook HMAC | AUDIT-SEC-001 | OBLIGATORIO | hash_equals() para Stripe webhooks |
| XSS prevention | INNERHTML-XSS-001 | OBLIGATORIO | Drupal.checkPlain() en JS |
| CSRF API | CSRF-API-001 | OBLIGATORIO | _csrf_request_header_token en API routes |
| CSRF JS cache | CSRF-JS-CACHE-001 | OBLIGATORIO | Token cacheado en variable |
| Entity FK | ENTITY-FK-001 | OBLIGATORIO | entity_reference para vertical_ref |
| Service call contract | SERVICE-CALL-CONTRACT-001 | OBLIGATORIO | Firmas coinciden exactamente |
| Body classes | hook_preprocess_html | OBLIGATORIO | NO attributes.addClass() en template |
| Field UI | FIELD-UI-SETTINGS-TAB-001 | SI APLICA | Si Addon tiene field_ui_base_route |
| Views data | Views integration | OBLIGATORIO | EntityViewsData en anotacion |
| Entity preprocess | ENTITY-PREPROCESS-001 | SI APLICA | Si addon tiene view mode |
| Presave resilience | PRESAVE-RESILIENCE-001 | SI APLICA | hasService() + try-catch |
| Label nullsafe | LABEL-NULLSAFE-001 | SI APLICA | Verificar $entity->label() |
| Access return type | ACCESS-RETURN-TYPE-001 | OBLIGATORIO | : AccessResultInterface (no AccessResult) |

---

## 9. Verificacion RUNTIME-VERIFY-001

Tras completar CADA fase, verificar estas 12 capas:

| # | Verificacion | Comando | Esperado |
|---|-------------|---------|----------|
| 1 | CSS compilado | `ls -la css/routes/tenant-dashboard.css scss/routes/tenant-dashboard.scss` | CSS timestamp > SCSS |
| 2 | Tablas DB | `lando drush ev "print_r(\Drupal::database()->schema()->tableExists('addon'))"` | TRUE |
| 3 | Campo vertical_ref | `lando drush ev "\$def = \Drupal::entityDefinitionUpdateManager()->getFieldStorageDefinition('vertical_ref', 'addon'); echo \$def ? 'OK' : 'MISSING';"` | OK |
| 4 | Rutas accesibles | `lando drush route:list --path=/addons` | 200 OK |
| 5 | data-* selectores | Verificar que JS usa mismos selectores que HTML | Match |
| 6 | drupalSettings | Verificar inyeccion en hook_preprocess | Presente |
| 7 | Addon type vertical | `lando drush ev "\$s = \Drupal::entityTypeManager()->getStorage('addon'); echo count(\$s->loadByProperties(['addon_type' => 'vertical']));"` | 9 |
| 8 | TenantVerticalService | `lando drush ev "\Drupal::service('ecosistema_jaraba_core.tenant_vertical')"` | Sin error |
| 9 | Library attached | Visitar /addons, verificar CSS cargado en DevTools | route-addons-catalog.css |
| 10 | Template suggestion | Visitar /addons, verificar Twig debug `page--addons.html.twig` | Activo |
| 11 | Quick links | Visitar /my-dashboard como tenant, verificar enlaces activos | Links funcionales |
| 12 | Iconos duotone | Verificar que todos los iconos renderizan SVG, no emoji | SVG <img> tags |

**Automatizacion:**
```bash
bash scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_addons
php scripts/validation/validate-service-consumers.php
php scripts/validation/validate-compiled-assets.php
php scripts/validation/validate-tenant-isolation.php
php scripts/validation/validate-circular-deps.php
php scripts/validation/validate-optional-deps.php
php scripts/validation/validate-logger-injection.php
php scripts/validation/validate-entity-integrity.php
```

---

## 10. Coherencia con Documentacion Tecnica Existente

| Documento | Relacion | Alineamiento |
|-----------|----------|--------------|
| `00_DIRECTRICES_PROYECTO.md` v110 | Marco normativo | CUMPLE todas las directrices listadas en Seccion 8 |
| `00_ARQUITECTURA_TECNICA.md` v99 | Arquitectura de referencia | EXTIENDE multi-tenancy con addon subscriptions |
| `00_INDICE_MAESTRO.md` v139 | Indice de especificaciones | REFERENCIA Doc 158, 177, 178, 183 |
| `00_FLUJO_TRABAJO.md` v63 | Proceso de desarrollo | CUMPLE flujo de trabajo definido |
| `CLAUDE.md` v1.2.0 | Reglas de desarrollo | CUMPLE todas las reglas tecnicas |
| `2026-02-05_arquitectura_theming_saas_master.md` | 5-layer token cascade | CUMPLE: var(--ej-*), @use, color-mix() |
| `2026-02-05_especificacion_grapesjs_saas.md` | GrapesJS architecture | NO IMPACTA (este plan no modifica canvas) |
| `2026-03-03_Plan_Verticalizacion_Planes_Precios.md` | Pricing por vertical | COMPLEMENTA: este plan anade verticales como addons |
| `2026-03-03_Plan_Gaps_Clase_Mundial_100.md` | Gaps restantes | RESUELVE gaps #47 (vertical cross-sell) y #62 (addon billing) |

---

## 11. Plan de Testing

| Tipo | Archivo | Que Verifica |
|------|---------|--------------|
| Unit | `TenantVerticalServiceTest.php` | getActiveVerticals(), hasVertical() con mocks |
| Unit | `VerticalAddonBillingServiceTest.php` | activateVerticalAddon() logica de negocio |
| Kernel | `AddonVerticalTypeTest.php` | Campo vertical en addon_type funciona en DB |
| Kernel | `VerticalAddonSubscriptionTest.php` | Subscribe + cancel + isAddonActive con entities reales |
| Kernel | `FeatureAccessVerticalTest.php` | FEATURE_ADDON_MAP evalua verticales correctamente |
| Functional | `AddonCatalogVerticalTest.php` | /addons muestra seccion de verticales |
| Functional | `DashboardQuickLinksTest.php` | /my-dashboard muestra links de verticales activos |

**Reglas de testing:**
- KERNEL-TEST-DEPS-001: listar TODOS los modulos requeridos en $modules
- KERNEL-TEST-001: KernelTestBase solo cuando necesita DB
- MOCK-DYNPROP-001: sin dynamic properties en PHP 8.4
- MOCK-METHOD-001: createMock() solo soporta metodos de la interface
- TEST-CACHE-001: mocks implementan getCacheContexts/Tags/MaxAge
- KERNEL-TIME-001: tolerancia +/-1 segundo en timestamps

---

## 12. Registro de Cambios

| Fecha | Version | Cambio | Autor |
|-------|---------|--------|-------|
| 2026-03-05 | 1.0.0 | Creacion del plan completo con 7 fases | Claude Opus 4.6 |

---

**Nota:** Este documento sigue la convencion de nomenclatura del proyecto: `YYYY-MM-DD_titulo_descriptivo_vN.md` en `/docs/implementacion/`. No se han modificado master docs (DOC-GUARD-001). Cualquier actualizacion a master docs debe hacerse en commit separado con prefijo `docs:` (COMMIT-SCOPE-001).
