# PLAN DE IMPLEMENTACION: 10/10 CLASE MUNDIAL EN TODAS LAS DIMENSIONES
# Fecha: 2026-03-18 | Version: 1.0
# Basado en: Auditoria Integral SaaS (8.8/10 actual → 10/10 target)
# Alineado con: Doc 158 v2 (20260318f) — Pricing Matrix SSOT enriquecida con 60+ competidores
# Fuente: 7 market research docs, 4 auditorias, codebase completo (94 modulos), Doc 158 v2
# Autor: Claude Opus 4.6 (1M context)

---

## INDICE DE NAVEGACION (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnostico: De 8.8 a 10.0](#2-diagnostico-de-88-a-100)
3. [Sprint 1 — Verificacion Runtime y Documentacion (P0)](#3-sprint-1--verificacion-runtime-y-documentacion-p0)
   - 3.1 [Verificacion MetaSitios (MR-01 a MR-06)](#31-verificacion-metasitios-mr-01-a-mr-06)
   - 3.2 [Verificacion Landings (GL-01 a GL-04)](#32-verificacion-landings-gl-01-a-gl-04)
   - 3.3 [Correccion Documento Consolidado v2](#33-correccion-documento-consolidado-v2)
   - 3.4 [Actualizacion CLAUDE.md (O1 a O7)](#34-actualizacion-claudemd-o1-a-o7)
4. [Sprint 2 — PLG Completo y Stripe Customer Portal (P1)](#4-sprint-2--plg-completo-y-stripe-customer-portal-p1)
   - 4.1 [Stripe Customer Portal](#41-stripe-customer-portal)
   - 4.2 [Add-on Cards en Subscription UI](#42-add-on-cards-en-subscription-ui)
   - 4.3 [User-Facing Alerts Pre-Limite](#43-user-facing-alerts-pre-limite)
   - 4.4 [Proration Preview](#44-proration-preview)
   - 4.5 [Revenue Dashboard (MRR/Churn/LTV)](#45-revenue-dashboard-mrrchurnltv)
5. [Sprint 3 — Entity Compliance y AccessControlHandlers (P1)](#5-sprint-3--entity-compliance-y-accesscontrolhandlers-p1)
   - 5.1 [37 Entities sin AccessControlHandler](#51-37-entities-sin-accesscontrolhandler)
   - 5.2 [22 Entities sin field_ui_base_route](#52-22-entities-sin-field_ui_base_route)
   - 5.3 [21 ConfigEntities sin Access Handler](#53-21-configentities-sin-access-handler)
   - 5.4 [Template Preprocess Sistematico](#54-template-preprocess-sistematico)
6. [Sprint 4 — SEO/GEO Clase Mundial](#6-sprint-4--seogeo-clase-mundial)
   - 6.1 [GEO: Answer Capsules Operativas](#61-geo-answer-capsules-operativas)
   - 6.2 [Schema.org Completo por Vertical](#62-schemaorg-completo-por-vertical)
   - 6.3 [Hreflang Verificado E2E](#63-hreflang-verificado-e2e)
   - 6.4 [Core Web Vitals en CI](#64-core-web-vitals-en-ci)
7. [Sprint 5 — Analytics y Attribution Pipeline](#7-sprint-5--analytics-y-attribution-pipeline)
   - 7.1 [Conversion Funnel Tracking](#71-conversion-funnel-tracking)
   - 7.2 [Attribution Pipeline (QR→Venta, Demo→Trial)](#72-attribution-pipeline-qrventa-demotrial)
   - 7.3 [North Star Metrics Dashboards](#73-north-star-metrics-dashboards)
8. [Sprint 6 — Accesibilidad WCAG 2.1 AA 100%](#8-sprint-6--accesibilidad-wcag-21-aa-100)
9. [Sprint 7 — PHPStan Baseline Reduction](#9-sprint-7--phpstan-baseline-reduction)
10. [Tabla de Correspondencia: Especificaciones Tecnicas](#10-tabla-de-correspondencia-especificaciones-tecnicas)
11. [Tabla de Cumplimiento: Directrices del Proyecto](#11-tabla-de-cumplimiento-directrices-del-proyecto)
12. [Cronograma y Dependencias](#12-cronograma-y-dependencias)
13. [Criterios de Aceptacion Global](#13-criterios-de-aceptacion-global)
14. [Alineacion con Doc 158 v2 (Pricing Matrix SSOT)](#14-alineacion-con-doc-158-v2-pricing-matrix-ssot)
    - 14.1 [Estructura de Precios Canonicos](#141-estructura-de-precios-canonicos-doc-158-v2--ssot)
    - 14.2 [Stripe Products (28 productos)](#142-stripe-products-28-productos-doc-158-v2)
    - 14.3 [Kit Digital como Canal Financiero](#143-kit-digital-como-canal-financiero-doc-158-v2-seccion-5)
    - 14.4 [Descuentos Canonicos](#144-descuentos-canonicos-doc-158-v2-seccion-9)
    - 14.5 [Metricas Revenue Targets](#145-metricas-revenue-targets-doc-158-v2-seccion-10)
    - 14.6 [Pricing Institucional](#146-pricing-institucional-doc-158-v2-seccion-6)

---

## 1. RESUMEN EJECUTIVO

### Objetivo

Llevar TODAS las dimensiones de la Jaraba Impact Platform de la puntuacion actual (8.8/10 media ponderada) a 10/10 clase mundial, cerrando 52 gaps identificados en la auditoria integral del 2026-03-18.

### Estado Actual vs Target

| Dimension | Actual | Target | Delta | Sprint |
|-----------|:------:|:------:|:-----:|:------:|
| Estrategia de mercado | 9.5 | 10.0 | +0.5 | 5, 7 |
| Arquitectura multi-tenant | 9.0 | 10.0 | +1.0 | 3 |
| Setup Wizard + Daily Actions | 10.0 | 10.0 | 0 | Mantenimiento |
| PLG + Stripe | 9.0 | 10.0 | +1.0 | 2 |
| Landings verticales | 8.5 | 10.0 | +1.5 | 1, 4 |
| MetaSitios | 7.5 | 10.0 | +2.5 | 1 |
| Theme + UX + A11y | 9.5 | 10.0 | +0.5 | 6 |
| SEO/GEO | 8.0 | 10.0 | +2.0 | 4 |

### Principios de Implementacion

Cada tarea de este plan DEBE cumplir:

1. **ZERO-REGION-001/002/003:** Paginas frontend limpias, clean_content, drupalSettings via preprocess
2. **CSS-VAR-ALL-COLORS-001:** CADA color = `var(--ej-*, fallback)`. NUNCA hex hardcoded
3. **SCSS-COMPILE-VERIFY-001:** Tras CADA .scss editado, `npm run build` + verificar timestamp CSS > SCSS
4. **PREMIUM-FORMS-PATTERN-001:** Toda entity form extiende PremiumEntityFormBase
5. **{% trans %}:** TODOS los textos de interfaz en bloques `{% trans %}...{% endtrans %}` (Twig) o `Drupal.t()` (JS)
6. **TWIG-INCLUDE-ONLY-001:** Parciales con `{% include ... only %}` para aislar contexto
7. **ICON-CONVENTION-001:** `jaraba_icon('category', 'name', {variant: 'duotone'})`, NUNCA emojis en frontend
8. **SLIDE-PANEL-RENDER-001:** Toda accion crear/editar/ver abre slide-panel (renderPlain, no render)
9. **TENANT-001:** TODA query filtra por tenant. Sin excepciones
10. **ROUTE-LANGPREFIX-001:** URLs via `Url::fromRoute()`, NUNCA paths hardcoded
11. **OPTIONAL-CROSSMODULE-001:** Servicios cross-module con `@?`
12. **DART-SASS-MODERN:** `@use` (NO `@import`), `color-mix()` (NO `rgba()`), SCSS-COMPILETIME-001
13. **ENTITY-PREPROCESS-001:** Toda entity con view mode tiene `template_preprocess_{type}()` en .module
14. **AUDIT-CONS-001:** TODA entity tiene AccessControlHandler en anotacion
15. **hook_preprocess_html():** Body classes SIEMPRE via hook, NUNCA `attributes.addClass()` en template

---

## 2. DIAGNOSTICO: DE 8.8 A 10.0

### 52 Gaps Consolidados (sin duplicados)

| Prioridad | Cantidad | Esfuerzo | Sprint |
|-----------|:--------:|:--------:|:------:|
| P0 Critico | 9 | M-L | 1 |
| P1 Alto | 18 | M-XL | 2, 3 |
| P2 Medio | 13 | S-M | 4, 5, 6 |
| P3 Bajo | 12 | S | 1 (doc) |

### Grafo de Dependencias Criticas

```
Sprint 1 (P0: Verificacion + Docs)
    ├── MR-01..06 (MetaSitios runtime) — paralelo
    ├── GL-01..04 (Landings runtime) — paralelo
    ├── DOC-CONSOLIDATE-001 (18 correcciones) — independiente
    └── CLAUDE.md updates (O1..O7) — independiente

Sprint 2 (P1: PLG)
    ├── GP-06 Stripe Customer Portal — independiente
    ├── GP-02 Add-on cards — depende de _subscription-card.html.twig existente
    ├── GP-05 Alerts pre-limite — depende de FairUsePolicyService
    ├── GP-03 Proration preview — depende de Stripe API
    └── GP-04 Revenue dashboard — depende de jaraba_foc

Sprint 3 (P1: Entities) — paralelo con Sprint 2
    ├── 37 AccessControlHandlers — independiente por modulo
    ├── 22 field_ui_base_route — independiente por modulo
    └── template_preprocess — sistematico

Sprint 4 (P2: SEO/GEO) — depende de Sprint 1
    ├── Answer Capsules — depende de content_hub
    ├── Schema.org — depende de landings verificadas
    └── Core Web Vitals CI — independiente

Sprint 5 (P2: Analytics) — depende de Sprint 2
    └── Revenue dashboard → funnel tracking → attribution

Sprint 6 (P2: A11y) — independiente
Sprint 7 (P3: PHPStan) — independiente, gradual
```

---

## 3. SPRINT 1 — VERIFICACION RUNTIME Y DOCUMENTACION (P0)

**Duracion estimada:** 1-2 semanas
**Impacto:** MetaSitios 7.5→9.0, Landings 8.5→9.0, Arquitectura docs 8→10

### 3.1 Verificacion MetaSitios (MR-01 a MR-06)

**Contexto:** La auditoria detecto que la infraestructura de MetaSitios (Domain entities, TenantThemeConfig, SiteConfig) tiene el codigo correcto pero no se ha verificado que el usuario realmente lo experimenta. Este es el gap mas critico: "el codigo existe" ≠ "el usuario lo experimenta".

#### MR-01: HomepageContent entities por metasitio

**Que verificar:** Cada metasitio (plataformadeecosistemas.com, pepejaraba.com, jarabaimpact.com) necesita al menos 1 HomepageContent entity vinculada a su Group/tenant.

**Logica tecnica:**
- `HomepageContent` entity tiene campo `tenant_id` (entity_reference a Group)
- Sin un HomepageContent, la homepage renderiza el flujo generico de page--front.html.twig sin datos dinamicos
- La deteccion de metasitio usa `meta_site.group_id` en el template — si no hay entity, los bloques (hero, features, stats) quedan vacios

**Comandos de verificacion:**
```bash
# Dentro del contenedor Lando
lando drush eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('homepage_content');
  \$entities = \$storage->loadMultiple();
  foreach (\$entities as \$e) {
    echo \$e->id() . ' | tenant: ' . \$e->get('tenant_id')->target_id . ' | name: ' . \$e->label() . PHP_EOL;
  }
"
```

**Si no existen:** Crear via UI en `/admin/content/homepage` o programaticamente en un hook_update_N() con datos seed para cada metasitio.

**Directrices aplicables:** TENANT-001 (tenant_id obligatorio), UPDATE-HOOK-REQUIRED-001 (si se crea entity nueva).

#### MR-02: SiteConfig entities por metasitio

**Que verificar:** Cada metasitio necesita un SiteConfig que defina homepage_id, site_name, site_logo.

**Logica:** SiteConfig es el nivel 5 (fallback) de THEMING-UNIFY-001. Sin SiteConfig, `UnifiedThemeResolverService` usa defaults del tema (ecosistema_jaraba_theme.settings) para todo — lo cual funciona pero impide personalizacion por dominio.

**Comandos:**
```bash
lando drush eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('site_config');
  \$entities = \$storage->loadMultiple();
  foreach (\$entities as \$e) {
    echo \$e->id() . ' | tenant: ' . \$e->get('tenant_id')->target_id . ' | name: ' . \$e->get('site_name')->value . PHP_EOL;
  }
"
```

#### MR-03: Ficheros og:image fallback

**Que verificar:** La cascada de og:image (SEO-OG-IMAGE-FALLBACK-001) busca ficheros fisicos como fallback final:
1. `content_data` keys (hero_image, og_image, image, background_image)
2. `/images/og-image-dynamic.png`
3. `/images/og-image-dynamic.webp`
4. `/logo.svg`

**Comandos:**
```bash
ls -la web/themes/custom/ecosistema_jaraba_theme/images/og-image-dynamic.{png,webp} 2>/dev/null
ls -la web/themes/custom/ecosistema_jaraba_theme/logo.svg 2>/dev/null
```

**Si no existen:** Crear og-image-dynamic.png (1200x630px, marca corporativa con logo centrado, fondo --ej-color-corporate). Formato WebP como alternativa.

#### MR-04: Hreflang tags en landings

**Que verificar:** Cada landing vertical debe renderizar `<link rel="alternate" hreflang="es" href="...">` en el `<head>`.

**Logica:** `_hreflang-meta.html.twig` se incluye en `html.html.twig`. Controla via variable `is_page_content` (HREFLANG-CONDITIONAL-001): en rutas page_content, HreflangService lo gestiona; en landings, el parcial lo hace.

**Verificacion browser:**
```bash
# Desde Lando
lando ssh -c "curl -s https://jaraba-saas.lndo.site/es/agroconecta | grep -i 'hreflang'"
```

**Si no renderiza:** Verificar que `hook_preprocess_html()` inyecta `is_page_content = FALSE` para rutas de landing, y que `available_languages` esta poblado.

#### MR-05: TenantThemeConfig resolucion visual multi-dominio

**Que verificar:** Cargar la misma ruta desde dominios diferentes debe mostrar colores/logo diferentes segun el TenantThemeConfig de cada tenant.

**Logica:** `UnifiedThemeResolverService` resuelve primero por hostname → Domain entity → Group → TenantThemeConfig. Si el hostname no tiene Domain entity, usa el default (DOMAIN-ROUTE-CACHE-001).

**Verificacion:**
```bash
# Comparar CSS vars entre dominios en Lando
lando ssh -c "curl -s https://jaraba-saas.lndo.site/ | grep 'ej-color-primary'"
lando ssh -c "curl -s https://pepejaraba.jaraba-saas.lndo.site/ | grep 'ej-color-primary'"
```

**Si son iguales:** Crear TenantThemeConfig entity para cada tenant con colores diferenciados. Verificar que `jaraba_theming_page_attachments()` inyecta el CSS de tokens.

#### MR-06: SSL para plataformadeecosistemas.es

**Que verificar:** El dominio .es reporto ERR_SSL_PROTOCOL_ERROR en diagnosticos previos.

**Verificacion:**
```bash
# Test SSL desde servidor
openssl s_client -connect plataformadeecosistemas.es:443 -servername plataformadeecosistemas.es </dev/null 2>/dev/null | head -20
```

**Si falla:** Verificar certificado Let's Encrypt/IONOS, DNS apuntando correctamente, y que Nginx tiene el server_name con .es incluido.

### 3.2 Verificacion Landings (GL-01 a GL-04)

#### GL-01: drupalSettings.verticalContext en landing JS

**Contexto:** VerticalLandingController pasa datos al render array, pero la verificacion de que `drupalSettings.verticalContext` llega al JS del frontend es pendiente.

**Logica tecnica:** El controller puede pasar datos via `#attached`, pero ZERO-REGION-003 dice que `#attached` del controller NO se procesa. Los datos deben inyectarse en `hook_preprocess_page()`.

**Verificacion:**
```bash
lando ssh -c "curl -s https://jaraba-saas.lndo.site/es/agroconecta | grep 'drupalSettings'"
```

**Si no aparece:** Anadir en `ecosistema_jaraba_theme_preprocess_page()`:
```php
if ($route_match && str_starts_with($route_name, 'ecosistema_jaraba_core.landing.')) {
  $variables['#attached']['drupalSettings']['verticalContext'] = [
    'key' => $variables['elements']['#vertical_key'] ?? '',
    'color' => $variables['elements']['#vertical_color'] ?? '',
  ];
}
```

**Directrices:** ZERO-REGION-003, ROUTE-LANGPREFIX-001.

#### GL-02: Progressive profiling JS

**Verificacion:** Abrir landing en browser, verificar que la library `ecosistema_jaraba_core/progressive-profiling` carga sin errores en console.

**Si falla:** Verificar path en `.libraries.yml`, que el JS usa `Drupal.behaviors` con `once()`, y que no depende de `core/once` bloqueado por CSP (JS-STANDALONE-MULTITENANT-001 en subdominios).

#### GL-03: Lead Magnet form submission E2E

**Verificacion:** Rellenar formulario de lead magnet en una landing → verificar que:
1. Datos se guardan (entity o tabla custom)
2. Email de confirmacion se envia (MailHog en Lando)
3. Follow-up campaign se activa (si ECA configurado)

**Directrices:** FORM-CACHE-001 (no setCached incondicional), SLIDE-PANEL-RENDER-001 si el form abre en modal.

#### GL-04: Search backend del marketplace

**Clarificacion:** Meilisearch NO esta en el stack (E2 de auditoria). El search usa:
- Search API con DB backend para busquedas textuales
- Qdrant para busqueda semantica (RAG)

**Accion:** Documentar en README del modulo que Meilisearch fue planificado pero nunca implementado.

### 3.3 Correccion Documento Consolidado v2

**Fichero:** `docs/tecnicos/20260318b-Documento_Maestro_Consolidado_Jaraba_v2_Claude.md`

**18 correcciones a aplicar (Edit incremental, DOC-GUARD-001):**

| # | Seccion | Correccion |
|---|---------|-----------|
| 1 | 6.1 Capa 6 | "Stack nativo Ubuntu" → "Docker Compose blue-green (web-blue/web-green)" |
| 2 | 6.1 Capa 5 | "meilisearch" → "phpmyadmin" |
| 3 | 6.1 nota | Eliminar nota MariaDB CI 11.2 (es 10.11, alineado) |
| 4 | 4.3 | GAP-01 a GAP-05: "Pendiente" → "Implementado" |
| 5 | 5.2, 5.4 | SEPE SOAP: "PENDIENTE" → "IMPLEMENTADO" |
| 6 | 7.4 | "jaraba_foc + 4 submodulos" → "jaraba_foc (1 modulo)" |
| 7 | 3.2 titulo | "8 Marketing Add-ons" → "9 Marketing Add-ons" |
| 8 | 5.4 | "SCORM/xAPI" → "xAPI + H5P" |
| 9 | 11.4 | "46+ CSS vars" → "35+ CSS custom properties runtime + 100+ SCSS variables" |
| 10 | 6.1, 15 | Qdrant: aclarar "Cloud produccion, Docker dev" |
| 11 | 9 | "202 bloques" → "202 bloques (per Template Registry SSOT)" |
| 12 | 10.2 | Webhooks: "Pendiente" → "IMPLEMENTADO (11 eventos)" |
| 13 | 10.2 | Dunning: "Pendiente" → "IMPLEMENTADO (6 etapas)" |
| 14 | 10.2 | Auto-provisioning: "Pendiente" → "IMPLEMENTADO" |
| 15 | 12.3 | Anadir STRIPE_WEBHOOK_SECRET como variable requerida |
| 16 | 11.3 | Anadir PHANTOM-ARG-001 v2 bidireccional |
| 17 | 12.3 | Anadir CSRF-LOGIN-FIX-001 v2 (patch-settings-csrf.php) |
| 18 | 11.3 | Anadir ACCESS-RETURN-TYPE-001 (68 handlers) |

**Directrices:** DOC-GUARD-001 (Edit incremental, NUNCA Write), COMMIT-SCOPE-001 (commit separado con prefijo `docs:`).

### 3.4 Actualizacion CLAUDE.md (O1 a O7)

**7 omisiones a incorporar (Edit incremental):**

| ID | Contenido | Seccion destino CLAUDE.md |
|----|-----------|--------------------------|
| O1 | STRIPE_WEBHOOK_SECRET en settings.secrets.php | SEGURIDAD |
| O2 | PHANTOM-ARG-001 v2 bidireccional (12 tests regresion) | CONVENCIONES - Modulos |
| O3 | CSRF-LOGIN-FIX-001 v2 (patch-settings-csrf.php cada deploy) | SEGURIDAD |
| O4 | DemoCopilotBridgeService, LegalCopilotBridgeService | IA - STACK |
| O5 | ZEIGARNIK-PRELOAD-001 (2 global steps, 25-33% pre-completion) | FRONTEND |
| O6 | ACCESS-RETURN-TYPE-001 (AccessResultInterface, 68 handlers) | PHP |
| O7 | SAFEGUARD-CANVAS-001 (4-layer canvas protection) | GRAPESJS |

---

## 4. SPRINT 2 — PLG COMPLETO Y STRIPE CUSTOMER PORTAL (P1)

**Duracion estimada:** 3-4 semanas
**Impacto:** PLG 9.0→10.0
**Dependencias:** Sprint 1 completado (docs corregidos)

### 4.1 Stripe Customer Portal

**Objetivo:** Permitir self-service para cancel/upgrade/payment method sin intervencion manual.

**Logica de negocio:** Stripe ofrece un portal pre-construido (`billing_portal/sessions`) donde el usuario puede:
- Ver facturas pasadas
- Actualizar metodo de pago
- Cambiar de plan (upgrade/downgrade)
- Cancelar suscripcion

**Implementacion:**

1. **Servicio:** `StripePortalService` en `jaraba_billing`
   - Metodo `createPortalSession(string $stripeCustomerId, string $returnUrl): string`
   - Llama a `$stripe->billingPortal->sessions->create()`
   - Return URL: `/user/billing` (ROUTE-LANGPREFIX-001: usar `Url::fromRoute()`)
   - Inyeccion: `@?jaraba_billing.stripe_connect` (OPTIONAL-CROSSMODULE-001)

2. **Controller:** Anadir metodo `portalRedirect()` en `BillingController`
   - Ruta: `/user/billing/portal`
   - Resuelve Stripe Customer ID del tenant actual (TenantBridgeService)
   - Crea session y redirige con `TrustedRedirectResponse`
   - Permission: `_user_is_logged_in: 'TRUE'` + tenant match (TENANT-ISOLATION-ACCESS-001)

3. **Template:** Boton en `_subscription-card.html.twig`
   ```twig
   <a href="{{ path('jaraba_billing.portal') }}" class="btn btn--outline btn--sm">
     {{ jaraba_icon('ui', 'settings', {variant: 'duotone', color: 'azul-corporativo', size: '16px'}) }}
     {% trans %}Gestionar suscripcion{% endtrans %}
   </a>
   ```

4. **SCSS:** Estilos del boton usan `var(--ej-color-corporate)` (CSS-VAR-ALL-COLORS-001)

**Directrices aplicables:** STRIPE-ENV-UNIFY-001 (keys via getenv), ROUTE-LANGPREFIX-001, OPTIONAL-CROSSMODULE-001, CSRF-API-001, `{% trans %}`.

### 4.2 Add-on Cards en Subscription UI

**Objetivo:** Mostrar add-ons activos y recomendados en el perfil del usuario.

**Logica:** `SubscriptionContextService` ya devuelve `addons.active[]` y `addons.recommended[]` (hasta 3). Falta la capa visual.

**Implementacion:**

1. **Parcial Twig:** `_addon-card.html.twig` en templates/partials/
   - Reutilizable en: subscription card, pricing page, dashboard
   - Variables: `addon_name`, `addon_price`, `addon_icon`, `addon_status`, `is_recommended`
   - Usar `{% include 'partials/_addon-card.html.twig' with {...} only %}` (TWIG-INCLUDE-ONLY-001)
   - Textos: `{% trans %}` para todos los labels

2. **Integracion:** En `_subscription-card.html.twig`, debajo de usage bars:
   ```twig
   {% if addons.active is not empty %}
     <h3>{% trans %}Complementos activos{% endtrans %}</h3>
     {% for addon in addons.active %}
       {% include 'partials/_addon-card.html.twig' with {addon: addon} only %}
     {% endfor %}
   {% endif %}
   {% if addons.recommended is not empty %}
     <h3>{% trans %}Complementos recomendados{% endtrans %}</h3>
     {% for addon in addons.recommended %}
       {% include 'partials/_addon-card.html.twig' with {addon: addon, is_recommended: true} only %}
     {% endfor %}
   {% endif %}
   ```

3. **SCSS:** `scss/components/_addon-card.scss`
   - `@use '../variables' as *;` (SCSS-001)
   - Colores via `var(--ej-*)` (CSS-VAR-ALL-COLORS-001)
   - Responsive: mobile-first con `@include respond-to(md)` para grid
   - Add to `main.scss` via `@use 'components/addon-card';`
   - Compilar: `npm run build` (SCSS-COMPILE-VERIFY-001)

**Directrices:** TWIG-INCLUDE-ONLY-001, CSS-VAR-ALL-COLORS-001, SCSS-001, `{% trans %}`, ICON-CONVENTION-001.

### 4.3 User-Facing Alerts Pre-Limite

**Objetivo:** Alertar al usuario cuando el uso se acerca al limite de su plan (antes de que FairUsePolicyService lo bloquee).

**Logica de negocio:** FairUsePolicyService ya evalua niveles (allow/warn/throttle/soft_block/hard_block). Cuando el nivel es `warn` (70-85%), el usuario debe recibir:
1. Email automatico (una vez al cruzar el umbral)
2. Banner en dashboard (persistente hasta upgrade o ciclo nuevo)

**Implementacion:**

1. **Email:** Nuevo key `usage_warning` en `jaraba_billing.module` hook_mail():
   - Subject: `{% trans %}Tu uso de @resource esta al @percentage%{% endtrans %}`
   - Body: Metricas de uso + CTA upgrade
   - Trigger: `FairUsePolicyService::evaluate()` cuando devuelve `warn` y no se ha enviado previamente (State API flag)

2. **Dashboard Banner:** Parcial `_usage-warning-banner.html.twig`
   - Solo visible si `usage_status == 'warning'` o `'danger'`
   - Colores: `var(--ej-color-warning)` para 70-85%, `var(--ej-color-danger)` para 85%+
   - CTA: Link a `/planes/{vertical}` (upgrade page)
   - Iconos: `jaraba_icon('ui', 'alert-triangle', {variant: 'duotone', color: 'warning'})`

3. **Inyeccion:** En `hook_preprocess_page()` para rutas dashboard:
   ```php
   if ($fair_use_service && $tenant_id) {
     $enforcement = $fair_use_service->getActiveEnforcement($tenant_id, 'ai_queries');
     if ($enforcement && in_array($enforcement['level'], ['warn', 'throttle'])) {
       $variables['usage_warning'] = $enforcement;
     }
   }
   ```

**Directrices:** ZERO-REGION-001 (variables via preprocess), `{% trans %}`, CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, OPTIONAL-CROSSMODULE-001 (`@?`).

### 4.4 Proration Preview

**Objetivo:** Mostrar al usuario cuanto pagara/ahorrara al cambiar de plan antes de confirmar.

**Logica:** Stripe calcula proration automaticamente. La API `Invoice.upcoming()` con `subscription_proration_date` devuelve el coste exacto.

**Implementacion:**

1. **Servicio:** `StripeProrationService::preview(string $subscriptionId, string $newPriceId): array`
   - Llama a `$stripe->invoices->upcoming(['subscription' => $id, 'subscription_items' => [...]])`
   - Retorna: `['amount_due' => int, 'credit_balance' => int, 'proration_date' => timestamp]`

2. **API Endpoint:** `POST /api/v1/billing/proration-preview` (ya definido en routing.yml)
   - Input: `{ plan_id, billing_cycle }`
   - Output: `{ amount_due, credit, effective_date, currency }`
   - CSRF: `_csrf_request_header_token: 'TRUE'` (CSRF-API-001)

3. **JS Frontend:** Fetch al endpoint cuando usuario selecciona plan diferente en pricing page
   - Mostrar modal de confirmacion con: precio actual → nuevo precio → diferencia
   - `Drupal.t('Pagaras @amount EUR ahora')` (i18n)

**Directrices:** STRIPE-URL-PREFIX-001 (NO /v1/), CSRF-API-001, ROUTE-LANGPREFIX-001, Drupal.t().

### 4.5 Revenue Dashboard (MRR/Churn/LTV)

**Objetivo:** Dashboard administrativo con metricas SaaS financieras por vertical.

**Logica:** jaraba_foc existe (1 modulo, 32 archivos PHP). Necesita expansion con charts visuales.

**Implementacion:**

1. **Controller:** `RevenueInsightsController` en `jaraba_foc`
   - Ruta: `/admin/jaraba/revenue-insights` (admin route)
   - Page template: `page--admin-center.html.twig` (reutilizar admin layout existente)
   - Datos: `['#type' => 'markup', '#markup' => '']` + drupalSettings via preprocess (ZERO-REGION-001)

2. **Servicio:** `RevenueMetricsService`
   - `getMRR()`: Suma subscription_plan.price WHERE status = 'active'
   - `getChurnRate(int $months)`: Cancelled / Active al inicio del periodo
   - `getLTV()`: ARPU / churn_rate
   - `getARPU()`: MRR / active_tenants
   - `getUpgradeRate()`: Plan changes UP / total active
   - Filtros: por vertical, por periodo
   - Inyeccion: `@?jaraba_billing.stripe_connect` (OPTIONAL-CROSSMODULE-001)

3. **Frontend:** Charts via CSS puro (NO Chart.js CDN — CSP):
   - Bar charts con `<div>` y `height: {percentage}%`
   - Colores: `var(--ej-color-primary)`, `var(--ej-color-success)`, `var(--ej-color-danger)`
   - Grid responsive: mobile stack, desktop 2x2

4. **SCSS:** `scss/routes/revenue-insights.scss`
   - Route-specific (no carga en otras paginas)
   - Library: `ecosistema_jaraba_theme/route-revenue-insights`
   - Attach via `hook_page_attachments_alter()`

**Directrices:** ZERO-REGION-001, CSS-VAR-ALL-COLORS-001, ROUTE-LANGPREFIX-001, SCSS-COMPILE-VERIFY-001.

---

## 5. SPRINT 3 — ENTITY COMPLIANCE Y ACCESSCONTROLHANDLERS (P1)

**Duracion estimada:** 2-3 semanas
**Impacto:** Arquitectura 9.0→10.0
**Paralelizable:** Con Sprint 2

### 5.1 37 Entities sin AccessControlHandler

**Contexto critico:** La auditoria detecto 37 ContentEntities sin AccessControlHandler declarado en su anotacion. Esto significa que Views puede exponer datos sin verificacion de permisos. Es una violacion de AUDIT-CONS-001.

**Patron a aplicar:** `DefaultEntityAccessControlHandler` (ya existe en `ecosistema_jaraba_core/src/Access/`) como handler generico. Para entities con `tenant_id`, verificar tenant match en update/delete (TENANT-ISOLATION-ACCESS-001).

**Implementacion por entity:**

1. Anadir en anotacion `@ContentEntityType`:
   ```php
   handlers = {
     "access" = "Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler",
     ...
   }
   ```

2. Si la entity tiene `tenant_id`, crear handler especifico:
   ```php
   class {EntityType}AccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {
     protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
       // ACCESS-RETURN-TYPE-001: retorna AccessResultInterface, NO AccessResult
       if (in_array($operation, ['update', 'delete'])) {
         $entity_tenant = (int) $entity->get('tenant_id')->target_id;
         $user_tenant = (int) $this->tenantContext->getCurrentTenantId();
         if ($entity_tenant !== $user_tenant) {
           return AccessResult::forbidden('Tenant mismatch'); // ACCESS-STRICT-001: === comparacion
         }
       }
       return parent::checkAccess($entity, $operation, $account);
     }
   }
   ```

3. hook_update_N() para registrar el cambio de handler (UPDATE-HOOK-REQUIRED-001)

**Entities afectadas (37):** AlertRule, ImpersonationAuditLog, ScheduledReport, NegotiationSession, CustomerPreferenceAgro, SalesMessageAgro, AIUsageLog, AggregatedInsight, AiAuditEntry, AutonomousSession, BrowserTask, CausalAnalysis, PendingApproval, PromptImprovement, RemediationLog, VerificationResult, y 21 mas.

**Directrices:** AUDIT-CONS-001, ACCESS-RETURN-TYPE-001, TENANT-ISOLATION-ACCESS-001, ACCESS-STRICT-001, UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001 (\Throwable).

### 5.2 22 Entities sin field_ui_base_route

**Contexto:** 22 entities no tienen `field_ui_base_route` en su anotacion, lo que impide usar Field UI para anadir campos desde la interfaz de administracion.

**Patron:**
```php
field_ui_base_route = "entity.{entity_type_id}.settings",
```

**Requiere:** Ruta `.settings` en routing.yml + local task tab (FIELD-UI-SETTINGS-TAB-001):
```yaml
entity.{type}.settings:
  path: '/admin/structure/{type}/settings'
  defaults:
    _form: '\Drupal\{module}\Form\{Type}SettingsForm'
    _title: '{Type} settings'
  requirements:
    _permission: 'administer site configuration'
```

**Entities afectadas (22):** A2ATask, AgentBenchmarkResult, AggregatedInsight, AiAuditEntry, AiFeedback, AutonomousSession, BrandVoiceProfile, BrowserTask, CausalAnalysis, ProactiveInsight, PromptImprovement, RemediationLog, VerificationResult, ProductMetricSnapshot, CandidateExperience, NotificationPreference, AgentSavedView, ResponseTemplate, TicketAttachment, TicketEventLog, TicketMessage, TicketWatcher.

### 5.3 21 ConfigEntities sin Access Handler

**Contexto:** 21 ConfigEntities no tienen AccessControlHandler explicito. Las ConfigEntities usan permisos de modulo (`administer site configuration`) por defecto, pero handlers explicitos permiten control granular.

**Evaluacion:** Para ConfigEntities de configuracion pura (SaasPlanTier, FairUsePolicy, DesignTokenConfig), el permiso de modulo es suficiente. Para las que contienen datos sensibles (AIWorkflow, AiRiskAssessment), anadir handler.

**Accion:** Evaluar caso por caso. Priorizar: AIWorkflow, AiRiskAssessment, PromptTemplate, SlaPolicy.

### 5.4 Template Preprocess Sistematico

**Contexto critico:** 420 de 440 ContentEntities (96%) no tienen `template_preprocess_{type}()`. Sin embargo, ENTITY-PREPROCESS-001 aplica solo a entities con view modes registrados.

**Evaluacion:** Muchas entities son API-only (sin templates Twig). La regla aplica a las que tienen `view_builder` y templates registrados.

**Accion sistematica:**
1. Listar entities con `view_builder` handler declarado
2. Para cada una, verificar que existe `template_preprocess_{type}()` en el .module correspondiente
3. Crear la funcion si falta, extrayendo campos utiles de `$variables['elements']['#{entity_type}']`

**Script de deteccion:**
```bash
php scripts/validation/validate-entity-integrity.php --check-preprocess
```

---

## 6. SPRINT 4 — SEO/GEO CLASE MUNDIAL

**Duracion estimada:** 2-3 semanas
**Impacto:** SEO/GEO 8.0→10.0
**Dependencias:** Sprint 1 (landings verificadas)

### 6.1 GEO: Answer Capsules Operativas

**Contexto estrategico:** GEO (Generative Engine Optimization) es clave para que LLMs (ChatGPT Search, Perplexity, Google AI Overviews) citen contenido de Jaraba. Y Combinator predice -25% trafico busqueda tradicional para 2026.

**Logica:** Una "Answer Capsule" es un bloque de contenido estructurado que un LLM puede extraer y citar directamente. Formato: pregunta clara + respuesta concisa + datos de soporte + fuente citada.

**Implementacion:**

1. **Campo en ContentArticle:** `field_answer_capsule` (text_long, translatable)
   - Formato: JSON con `{ question, answer, supporting_data, source_url }`
   - AI-generated via ECA-CH-001 trigger

2. **Schema.org:** `FAQPage` schema en articulos con answer capsules

3. **HTML semantico:** `<section itemscope itemtype="https://schema.org/Question">` con `<meta itemprop="name">` y `<div itemprop="acceptedAnswer">`

4. **robots.txt:** Permitir AI crawlers (Perplexity, ChatGPT) acceso a `/content-hub/*`

### 6.2 Schema.org Completo por Vertical

**Estado actual:** Organization + WebApplication schemas en homepage. FAQPage en landings (seccion FAQ).

**Faltante por vertical:**

| Vertical | Schema.org requerido | Estado |
|----------|---------------------|--------|
| AgroConecta | Product, LocalBusiness, AggregateRating | Parcial |
| ComercioConecta | LocalBusiness, Store, Product, Offer | Parcial |
| JarabaLex | LegalService, ProfessionalService | Pendiente |
| ServiciosConecta | ProfessionalService, Service | Pendiente |
| Empleabilidad | JobPosting, EducationalOrganization | Parcial |
| Formacion | Course, EducationalOrganization | Pendiente |

**Implementacion:** Parcial Twig `_schema-vertical.html.twig` con switch por `vertical_key`, incluido en `html.html.twig` (no en page template para evitar duplicacion).

### 6.3 Hreflang Verificado E2E

**Accion:** Completar verificacion MR-04 del Sprint 1 y asegurar que:
- Cada landing tiene hreflang correcto para ES (y EN/PT-BR cuando traducido)
- `x-default` apunta a ES
- URLs son absolutas (`'absolute' => TRUE` en `Url::fromRoute()`)

### 6.4 Core Web Vitals en CI

**Objetivo:** Integrar Lighthouse CI en GitHub Actions para monitorear LCP, CLS, FID/INP.

**Implementacion:**
- Nuevo job en `fitness-functions.yml`
- `npx lighthouse-ci` contra staging URL
- Thresholds: Performance >=90, Accessibility >=95, SEO >=95
- Bloqueo en PR si scores caen por debajo

---

## 7. SPRINT 5 — ANALYTICS Y ATTRIBUTION PIPELINE

**Duracion estimada:** 2-3 semanas
**Impacto:** Estrategia 9.5→10.0
**Dependencias:** Sprint 2 (revenue dashboard), Sprint 4 (SEO/GEO)

### 7.1 Conversion Funnel Tracking

**Eventos a trackear:**
- `wizard_started` (Setup Wizard iniciado)
- `wizard_step_completed` (cada step completado)
- `wizard_completed` (100% completado)
- `plan_viewed` (pricing page visitada)
- `plan_selected` (click en "Elegir plan")
- `checkout_started` (Stripe checkout abierto)
- `checkout_completed` (pago exitoso)
- `addon_activated` (add-on habilitado)

**Implementacion:** Custom events via `jaraba_analytics` module (nativo, no GTM).

### 7.2 Attribution Pipeline (QR→Venta, Demo→Trial)

**QR Attribution (ComercioConecta):**
- QR code genera URL con UTM: `?utm_source=qr&utm_campaign={product_id}`
- Landing registra event `qr_scan` con product_id
- Si el usuario completa compra, atribuir a QR
- Metrica: QR-to-sale conversion rate (North Star comercioconecta)

**Demo Attribution:**
- Demo vertical genera session_id
- Si usuario se registra dentro de 7 dias, atribuir a demo
- Metrica: Demo-to-trial conversion rate (North Star demo)

### 7.3 North Star Metrics Dashboards

**Por vertical:** Crear widget en dashboard de cada vertical mostrando su North Star metric con trend line.

**Implementacion:** Parcial `_north-star-metric.html.twig` reutilizable, datos via preprocess, colores via `var(--ej-*)`.

---

## 8. SPRINT 6 — ACCESIBILIDAD WCAG 2.1 AA 100%

**Duracion estimada:** 1-2 semanas
**Impacto:** Theme+UX+A11y 9.5→10.0

**Gaps pendientes (5%):**
1. SVGs decorativos en auth page sin `aria-hidden="true"`
2. Verificar contraste cuando tenants personalizan colores via TenantThemeConfig
3. Screen reader testing con NVDA/VoiceOver en flujos criticos (registro, checkout, wizard)
4. Verificar `prefers-reduced-motion` en animaciones (CSS `@media (prefers-reduced-motion: reduce)`)
5. `lang` attribute en `<html>` dinamico segun idioma activo

**Herramientas:** axe-core plugin, WAVE, Lighthouse Accessibility score >=95.

---

## 9. SPRINT 7 — PHPSTAN BASELINE REDUCTION

**Duracion estimada:** Continuo (gradual)
**Impacto:** Calidad de codigo

**Plan gradual:**
- 41K entradas actuales → Target 30K en Q2, 20K en Q3, 10K en Q4
- Priorizar: errores de tipo en servicios core (ecosistema_jaraba_core, jaraba_billing)
- Eliminar 1.000 entradas por sprint
- Regenerar baseline: `vendor/bin/phpstan analyse --generate-baseline`

---

## 10. TABLA DE CORRESPONDENCIA: ESPECIFICACIONES TECNICAS

| Componente | Directriz | Fichero clave | Validacion |
|------------|-----------|---------------|------------|
| Multi-tenant queries | TENANT-001 | Cada *.php con entityQuery | `validate-tenant-isolation.php` |
| Tenant Bridge | TENANT-BRIDGE-001 | TenantBridgeService.php | Manual code review |
| Domain routing | DOMAIN-ROUTE-CACHE-001 | config/sync/domain.record.*.yml | DB query Domain entities |
| Vary Host | VARY-HOST-001 | SecurityHeadersSubscriber.php | `curl -I` response headers |
| Access handlers | AUDIT-CONS-001 | Entity annotations @ContentEntityType | `validate-entity-integrity.php` |
| Entity forms | PREMIUM-FORMS-PATTERN-001 | PremiumEntityFormBase.php | grep -r "extends ContentEntityForm" |
| Entity preprocess | ENTITY-PREPROCESS-001 | *.module template_preprocess_* | `validate-entity-integrity.php --check-preprocess` |
| Field UI | FIELD-UI-SETTINGS-TAB-001 | *.links.task.yml | grep field_ui_base_route annotations |
| Views data | views_data annotation | Entity annotations | grep "views_data" annotations |
| hook_update_N | UPDATE-HOOK-REQUIRED-001 | *.install files | `validate-entity-integrity.php` CHECK 7 |
| CSS colores | CSS-VAR-ALL-COLORS-001 | scss/**/*.scss | grep hardcoded hex in compiled CSS |
| SCSS compile | SCSS-COMPILE-VERIFY-001 | package.json, css/*.css | `validate-compiled-assets.php` |
| SCSS @use | SCSS-001 | scss/**/_*.scss | check @import vs @use |
| color-mix | SCSS-COLORMIX-001 | scss/**/*.scss | grep rgba() in SCSS |
| Textos i18n | {% trans %} | templates/**/*.html.twig | grep hardcoded strings without trans |
| URLs | ROUTE-LANGPREFIX-001 | *.php, *.js | grep hardcoded paths |
| Icons | ICON-CONVENTION-001 | JarabaTwigExtension.php | grep emoji in templates |
| Slide-panel | SLIDE-PANEL-RENDER-001 | Controllers with modal forms | grep render() vs renderPlain() |
| Zero Region | ZERO-REGION-001/002/003 | page--*.html.twig | grep page.content in templates |
| Body classes | hook_preprocess_html | ecosistema_jaraba_theme.theme | grep attributes.addClass in templates |
| Secrets | SECRET-MGMT-001 | settings.secrets.php | grep getenv() vs hardcoded keys |
| CSRF | CSRF-API-001 | *.routing.yml | grep _csrf_request_header_token |
| GrapesJS | CANVAS-ARTICLE-001 | jaraba_page_builder module | verify vendor local |
| Canvas icons | ICON-CANVAS-INLINE-001 | canvas_data JSON | grep currentColor in canvas |
| Optional DI | OPTIONAL-CROSSMODULE-001 | *.services.yml | `validate-optional-deps.php` |
| Circular deps | CONTAINER-DEPS-002 | *.services.yml | `validate-circular-deps.php` |
| Logger DI | LOGGER-INJECT-001 | *.services.yml + constructors | `validate-logger-injection.php` |
| Phantom args | PHANTOM-ARG-001 | *.services.yml + constructors | `validate-phantom-args.php` |
| Stripe keys | STRIPE-ENV-UNIFY-001 | settings.secrets.php | grep Stripe in config/sync |
| Stripe URLs | STRIPE-URL-PREFIX-001 | StripeConnectService.php | grep /v1/ in endpoints |
| Stripe checkout | STRIPE-CHECKOUT-001 | CheckoutSessionService.php | verify ui_mode: embedded |
| Pricing | NO-HARDCODE-PRICE-001 | templates/**/*.html.twig | `validate-no-hardcoded-prices.php` |
| Docs | DOC-GUARD-001 | docs/master/00_*.md | `verify-doc-integrity.sh` |
| PHP readonly | CONTROLLER-READONLY-001 | Controllers extending ControllerBase | grep "protected readonly" |
| PHP Throwable | UPDATE-HOOK-CATCH-001 | *.install hook_update_N | grep "catch(\\Exception" |
| Access return | ACCESS-RETURN-TYPE-001 | *AccessControlHandler.php | grep ": AccessResult " |

---

## 11. TABLA DE CUMPLIMIENTO: DIRECTRICES DEL PROYECTO

| # | Directriz | Descripcion | Como se cumple en este plan |
|---|-----------|-------------|----------------------------|
| 1 | ZERO-REGION-001 | clean_content, no page.content | Toda nueva pagina usa template limpio |
| 2 | ZERO-REGION-002 | No entity objects como non-# keys | Render arrays correctos en controllers |
| 3 | ZERO-REGION-003 | drupalSettings via preprocess, no controller | Verificado en GL-01 |
| 4 | CSS-VAR-ALL-COLORS-001 | var(--ej-*, fallback) siempre | Cada SCSS nuevo usa tokens |
| 5 | SCSS-001 | @use, no @import | Dart Sass moderno en todo build |
| 6 | SCSS-COMPILE-VERIFY-001 | npm run build tras cada .scss | Validado por pre-commit hook |
| 7 | SCSS-COLORMIX-001 | color-mix() no rgba() | Runtime alpha con color-mix |
| 8 | SCSS-COMPILETIME-001 | hex estatico para color.scale | Variables SCSS separadas de CSS vars |
| 9 | PREMIUM-FORMS-PATTERN-001 | PremiumEntityFormBase | Todas las entity forms nuevas |
| 10 | {% trans %} | Textos siempre traducibles | Verificado en cada template nuevo |
| 11 | Drupal.t() | JS strings traducibles | Verificado en cada JS nuevo |
| 12 | TWIG-INCLUDE-ONLY-001 | Parciales con `only` | Cada include nuevo usa only |
| 13 | ICON-CONVENTION-001 | jaraba_icon() duotone default | Cada icono usa la funcion |
| 14 | ICON-EMOJI-001 | No emojis en frontend | SVG inline, NUNCA emojis |
| 15 | SLIDE-PANEL-RENDER-001 | renderPlain() en modales | Toda accion crear/editar/ver |
| 16 | TENANT-001 | Toda query filtra tenant | Cada nueva query verificada |
| 17 | TENANT-BRIDGE-001 | TenantBridgeService | Nunca getStorage('group') |
| 18 | ROUTE-LANGPREFIX-001 | Url::fromRoute() | Nunca paths hardcoded |
| 19 | OPTIONAL-CROSSMODULE-001 | @? cross-module | Validado por pre-commit |
| 20 | AUDIT-CONS-001 | AccessControlHandler en toda entity | Sprint 3: 37+21 entities |
| 21 | ENTITY-PREPROCESS-001 | template_preprocess_{type} | Sprint 3: sistematico |
| 22 | UPDATE-HOOK-REQUIRED-001 | hook_update_N para cambios entity | Cada entity nueva/modificada |
| 23 | SECRET-MGMT-001 | getenv() via settings.secrets.php | Stripe webhook secret incluido |
| 24 | DOC-GUARD-001 | Edit incremental, nunca Write | Sprint 1: correcciones doc |
| 25 | CONTROLLER-READONLY-001 | No readonly en props heredadas | Verificado en cada controller |
| 26 | ACCESS-RETURN-TYPE-001 | AccessResultInterface | Sprint 3: handlers nuevos |
| 27 | DOMAIN-ROUTE-CACHE-001 | Domain entity por hostname | Sprint 1: verificacion MR-05 |
| 28 | NO-HARDCODE-PRICE-001 | Precios via MetaSitePricingService | Pricing pages y landings |
| 29 | SETUP-WIZARD-DAILY-001 | 52 steps + 39 actions | Mantenimiento: 100% |
| 30 | PIPELINE-E2E-001 | L1→L2→L3→L4 | Verificado en cada feature |
| 31 | RUNTIME-VERIFY-001 | CSS→DB→Routes→Selectors→drupalSettings | Sprint 1: verificacion completa |
| 32 | hook_preprocess_html | Body classes via hook, no template | Verificado en cada pagina |

---

## 12. CRONOGRAMA Y DEPENDENCIAS

```
Semana 1-2:  Sprint 1 (P0: Runtime verify + Docs)
             ├── MR-01..06 en paralelo
             ├── GL-01..04 en paralelo
             └── Doc corrections + CLAUDE.md updates

Semana 3-6:  Sprint 2 (P1: PLG) + Sprint 3 (P1: Entities) EN PARALELO
             ├── S2: Stripe Portal → Add-on cards → Alerts → Proration → Revenue
             └── S3: 37 ACH → 22 field_ui → preprocess

Semana 7-9:  Sprint 4 (P2: SEO/GEO)
             ├── Answer Capsules
             ├── Schema.org por vertical
             ├── Hreflang E2E
             └── Core Web Vitals CI

Semana 10-12: Sprint 5 (P2: Analytics) + Sprint 6 (P2: A11y) EN PARALELO
              ├── S5: Funnel tracking → Attribution → North Star dashboards
              └── S6: aria-hidden + contrast + screen reader + motion

Continuo:     Sprint 7 (P3: PHPStan baseline reduction)
```

**Duracion total estimada: 12 semanas (3 meses)**

**Resultado esperado:**
- Semana 2: MetaSitios 9.0, Landings 9.0, Docs 10.0
- Semana 6: PLG 10.0, Arquitectura 10.0
- Semana 9: SEO/GEO 10.0
- Semana 12: Theme+UX+A11y 10.0, Estrategia 10.0
- **Media final: 10.0/10.0**

---

## 13. CRITERIOS DE ACEPTACION GLOBAL

Para considerar que se ha alcanzado 10/10 en TODAS las dimensiones:

### MetaSitios (10/10)
- [ ] HomepageContent entity existe para cada metasitio (MR-01)
- [ ] SiteConfig entity existe para cada metasitio (MR-02)
- [ ] og:image fallback files existen fisicamente (MR-03)
- [ ] Hreflang tags renderizados en todas las landings (MR-04)
- [ ] TenantThemeConfig resuelve visualmente diferente por dominio (MR-05)
- [ ] SSL operativo en plataformadeecosistemas.es (MR-06)

### PLG + Stripe (10/10)
- [ ] Stripe Customer Portal integrado y funcional (GP-06)
- [ ] Add-on cards renderizados en subscription card (GP-02)
- [ ] User-facing alerts cuando uso >70% (GP-05)
- [ ] Proration preview funcional (GP-03)
- [ ] Revenue dashboard con MRR/Churn/LTV charts (GP-04)

### Arquitectura (10/10)
- [ ] 0 entities sin AccessControlHandler (Sprint 3.1)
- [ ] 0 entities sin field_ui_base_route que lo requieran (Sprint 3.2)
- [ ] template_preprocess_{type}() para toda entity con view_builder (Sprint 3.4)
- [ ] Documento consolidado v2 corregido con 18 fixes (Sprint 1.3)
- [ ] CLAUDE.md actualizado con 7 omisiones (Sprint 1.4)

### Landings (10/10)
- [ ] drupalSettings verificado en browser (GL-01)
- [ ] Progressive profiling JS funcional (GL-02)
- [ ] Lead magnet form E2E funcional (GL-03)
- [ ] Search backend documentado (GL-04)

### SEO/GEO (10/10)
- [ ] Answer Capsules operativas en content hub (Sprint 4.1)
- [ ] Schema.org completo por vertical (Sprint 4.2)
- [ ] Hreflang E2E verificado (Sprint 4.3)
- [ ] Core Web Vitals en CI con thresholds (Sprint 4.4)

### Theme + UX + A11y (10/10)
- [ ] WCAG 2.1 AA compliance 100% (Sprint 6)
- [ ] Lighthouse Accessibility >=95 en CI
- [ ] prefers-reduced-motion respetado
- [ ] Screen reader tested (NVDA/VoiceOver)

### Estrategia (10/10)
- [ ] Conversion funnel tracking operativo (Sprint 5.1)
- [ ] Attribution pipeline funcional (Sprint 5.2)
- [ ] North Star metrics dashboards por vertical (Sprint 5.3)

### Setup Wizard + Daily Actions (10/10) — MANTENIMIENTO
- [ ] 100% cobertura mantenida (52 steps + 39 actions)
- [ ] ZEIGARNIK-PRELOAD-001 activo (25-33% pre-completion)
- [ ] Cada nuevo vertical recibe wizard + daily actions automaticamente

---

---

## 14. ALINEACION CON DOC 158 v2 (PRICING MATRIX SSOT)

**Referencia:** `docs/tecnicos/20260318f-158_Platform_Vertical_Pricing_Matrix_v2_Claude.md`

El Doc 158 v2 es la SSOT de precios (Regla de Oro #131). Este plan se alinea completamente con su contenido actualizado (18 marzo 2026, enriquecido con 60+ competidores).

### 14.1 Estructura de Precios Canonicos (Doc 158 v2 = SSOT)

**15 Planes Base (5 verticales x 3 tiers):**

| Vertical | Starter | Pro | Enterprise | Metrica Norte |
|----------|---------|-----|------------|---------------|
| Empleabilidad | 29 EUR | 79 EUR | 149 EUR | Insercion >40% |
| Emprendimiento | 39 EUR | 99 EUR | 199 EUR | Supervivencia >60% 12m |
| AgroConecta | 49 EUR | 129 EUR | 249 EUR | Margen productor 3-10x |
| ComercioConecta | 39 EUR | 99 EUR | 199 EUR | Conversion QR→venta >5% |
| ServiciosConecta | 29 EUR | 79 EUR | 149 EUR | No-shows <5% |

**9 Marketing Add-ons (precio fijo, vertical-independiente):**

| Add-on | Precio/mes | Tipo |
|--------|-----------|------|
| jaraba_crm | 19 EUR | Principal |
| jaraba_email | 29 EUR | Principal |
| jaraba_email_plus | 59 EUR | Principal |
| jaraba_social | 25 EUR | Principal |
| paid_ads_sync | 15 EUR | Extension |
| retargeting_pixels | 12 EUR | Extension |
| events_webinars | 19 EUR | Extension |
| ab_testing | 15 EUR | Extension |
| referral_program | 19 EUR | Extension |

**4 Bundles (NO 3 — Doc 158 v2 anade Growth Engine):**

| Bundle | Incluye | Precio | Ahorro |
|--------|---------|--------|--------|
| Marketing Starter | email + retargeting_pixels | 35 EUR | -15% |
| Marketing Pro | crm + email + social | 59 EUR | -20% |
| Growth Engine | email_plus + ab_testing + referral_program | 79 EUR | -15% |
| Marketing Complete | Todos los add-ons | 99 EUR | -30% |

### 14.2 Stripe Products (28 productos Doc 158 v2)

**Mapeo Stripe canonico (seccion 8.1 Doc 158 v2):**
- 15 productos Base Plan: `prod_{vertical}_{tier}` (ej: `prod_agroconecta_pro`)
- 9 productos Add-on: `prod_addon_{code}` (ej: `prod_addon_crm`)
- 4 productos Bundle: `prod_bundle_{name}` (ej: `prod_bundle_growth`)

**Variables criticas settings.secrets.php (Doc 158 v2 seccion 8.1):**
- `STRIPE_SECRET_KEY` — API secret
- `STRIPE_PUBLISHABLE_KEY` — Frontend key
- `STRIPE_WEBHOOK_SECRET` — HMAC verificacion (AUDIT-SEC-001)

### 14.3 Kit Digital como Canal Financiero (Doc 158 v2 seccion 5)

**Impacto en implementacion:**

| Segmento | Bono | Meses Pro financiados | Accion plan |
|----------|------|----------------------|-------------|
| Segmento I (10-49 emp) | 12.000 EUR | ~152 meses | Sprint 5: Attribution |
| Segmento II (3-9 emp) | 6.000 EUR | ~76 meses | Sprint 5: Attribution |
| Segmento III (0-2 emp) | 2.000-3.000 EUR | ~25-38 meses | Sprint 5: Attribution |
| Segmento IV (50-99 emp) | 25.000 EUR | >10 anos | Sprint 5: Revenue dashboard |
| Segmento V (100-249 emp) | 29.000 EUR | >10 anos | Sprint 5: Revenue dashboard |

**Accion requerida (GE-01 en Sprint 5):** Registrarse como Agente Digitalizador en AceleraPyme. Sin este registro, los clientes NO pueden usar el bono para financiar la suscripcion. Es el canal de adquisicion de mayor potencia (3.067M EUR presupuesto total).

### 14.4 Descuentos Canonicos (Doc 158 v2 seccion 9)

| Tipo | Descuento | Stripe Implementation |
|------|-----------|----------------------|
| Anual (plan base) | 2 meses gratis | Price interval=year |
| Anual (add-ons) | 15% | Price anual separado |
| Bundle | 15-30% | Product con price propio |
| Codigo promo | Variable | Stripe Coupons |
| Referido | 1er mes gratis | Coupon 100% off, duration=once |
| Early adopter | 30% lifetime | Coupon duration=forever |
| Colegio Abogados | 20% colectivo | Coupon por organizacion |
| Barrio Digital | 15% municipio | Coupon por programa |

**Impacto en Sprint 2 (PLG):** El checkout flow DEBE soportar:
1. Seleccion plan base + add-ons (Stripe subscription_items multiples)
2. Toggle anual/mensual (2 Prices por Product)
3. Codigo promocional (Stripe Coupons en embedded checkout: `allow_promotion_codes: true`)
4. Bundle pricing (Product tipo bundle con precio unico)

### 14.5 Metricas Revenue Targets (Doc 158 v2 seccion 10)

| Metrica | Target Q4 2026 | Dashboard (Sprint 5) |
|---------|---------------|---------------------|
| ARPU | >80 EUR | RevenueMetricsService.getARPU() |
| Addon Attach Rate | >30% | RevenueMetricsService.getAddonAttachRate() |
| Avg Addons per Tenant | >1.5 | RevenueMetricsService.getAvgAddonsPerTenant() |
| Plan Mix (S/P/E) | 40/45/15 | RevenueMetricsService.getPlanMix() |
| Upgrade Rate (90d) | >15% | RevenueMetricsService.getUpgradeRate() |
| Net Revenue Retention | >110% | RevenueMetricsService.getNRR() |
| Cross-Vertical Rate | >10% | TenantVerticalService.getCrossVerticalRate() |
| Kit Digital Conversion | >40% | AttributionService.getKitDigitalConversion() |

### 14.6 Pricing Institucional (Doc 158 v2 seccion 6)

**Andalucia +ei / PIIL:** NO tiene pricing SaaS comercial — financiado via subvencion publica.

| Concepto | Importe | Estado (auditado 18-03-2026) |
|----------|---------|------------------------------|
| Subvencion/persona atendida | 3.500 EUR | GAP-01 a GAP-05 IMPLEMENTADOS |
| Subvencion/persona insertada | 2.500 EUR | FaseTransitionManager operativo |
| Incentivo participante | 528 EUR | Campo en entity |
| Objetivo insercion minimo | 40% | Target Jaraba: >60% |
| Expediente activo | SC/ICJ/0050/2024 | 640 participantes, 900K EUR |
| Convocatoria 2026 | 31.100 desempleados | Feb 2026 — Jun 2027 |

**Impacto en plan:** El pricing institucional NO requiere implementacion Stripe. Se gestiona via facturacion manual (jaraba_legal_billing) + justificacion de subvencion ante el SAE.

---

*Plan generado el 2026-03-18 por Claude Opus 4.6 (1M context).*
*Basado en auditoria integral de 94 modulos, 1.131 servicios, 275+ entidades, 7 market research docs, 4 auditorias tecnicas.*
*Todas las tareas cumplen las 32 directrices verificadas en seccion 11.*
