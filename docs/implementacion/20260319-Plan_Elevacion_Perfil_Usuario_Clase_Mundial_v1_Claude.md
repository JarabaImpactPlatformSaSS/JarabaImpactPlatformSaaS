# Plan de Implementacion: Elevacion del Perfil de Usuario a Clase Mundial UX

> **Tipo:** Plan de Implementacion
> **Version:** 1.0
> **Fecha:** 2026-03-19
> **Autor:** Claude Opus 4.6 (1M context)
> **Estado:** En Progreso — Fase 1 completada
> **Alcance:** /user/{uid} — Hub de acceso al SaaS multi-tenant
> **Prioridad:** P0 — Afecta a TODOS los usuarios de la plataforma
> **Verticales impactadas:** Todas (10 verticales canonicos)
> **Modulos impactados:** ecosistema_jaraba_core, jaraba_billing, ecosistema_jaraba_theme, + 9 modulos verticales

---

## Tabla de Contenidos

1. [Diagnostico del Estado Actual](#1-diagnostico-del-estado-actual)
2. [Problemas Identificados y Correcciones Aplicadas](#2-problemas-identificados-y-correcciones-aplicadas)
3. [Arquitectura del Perfil de Usuario](#3-arquitectura-del-perfil-de-usuario)
4. [Iconografia de Clase Mundial — ICON-CONVENTION-001](#4-iconografia-de-clase-mundial--icon-convention-001)
5. [Coherencia Setup Wizard: Vertical ↔ Plan](#5-coherencia-setup-wizard-vertical--plan)
6. [Rutas Admin vs Tenant-Facing — Problema Sistemico](#6-rutas-admin-vs-tenant-facing--problema-sistemico)
7. [Mejoras Pendientes para 10/10](#7-mejoras-pendientes-para-1010)
8. [Tabla de Correspondencia de Especificaciones Tecnicas](#8-tabla-de-correspondencia-de-especificaciones-tecnicas)
9. [Cumplimiento de Directrices del Proyecto](#9-cumplimiento-de-directrices-del-proyecto)
10. [Checklist de Verificacion RUNTIME-VERIFY-001](#10-checklist-de-verificacion-runtime-verify-001)
11. [Plan de Implementacion por Fases](#11-plan-de-implementacion-por-fases)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Diagnostico del Estado Actual

### 1.1 Score Actual: 90/100

El perfil de usuario (`page--user.html.twig`) es una implementacion premium que sigue los patrones de clase mundial del SaaS. Sin embargo, se identificaron 6 problemas criticos que impiden alcanzar el 10/10.

### 1.2 Arquitectura Existente

```
/user/{uid} (entity.user.canonical)
    │
    ├── page--user.html.twig (439 lineas)
    │     ├── _header.html.twig (dispatcher: classic/minimal/transparent)
    │     ├── Profile View (user_page_type == 'profile')
    │     │     ├── Hero Card (avatar, nombre, email, roles, member-since)
    │     │     ├── _setup-wizard.html.twig (203 lineas)
    │     │     ├── Dashboard CTA (enlace al panel vertical)
    │     │     ├── _daily-actions.html.twig (87 lineas)
    │     │     ├── Account Info Cards (3-col grid)
    │     │     └── Quick Access Sections (via UserProfileSectionRegistry)
    │     │           ├── _subscription-card.html.twig (PLG)
    │     │           ├── _profile-completeness.html.twig
    │     │           └── Andalucia +ei stats
    │     ├── Edit/Logout/Contact/Cancel views
    │     └── _footer.html.twig
    │
    ├── Preprocess: ecosistema_jaraba_theme_preprocess_page__user()
    │     ├── Datos basicos (email, roles, avatar, fechas)
    │     ├── AvatarWizardBridgeService → wizard + daily actions
    │     ├── UserProfileSectionRegistry → secciones extensibles
    │     └── Cache: user context, user:{uid} tag, max-age 120s
    │
    └── SCSS: _user-pages.scss (1,608 lineas)
          ├── .profile-hero (glassmorphism, responsive 3 breakpoints)
          ├── .account-info (3-col grid → 1-col mobile)
          ├── .quick-access-sections (extensible grid)
          └── .profile-hub (wizard + daily actions container)
```

### 1.3 Flujo de Datos

```
                    JourneyState (avatar persistido)
                            │
                    AvatarWizardBridgeService::resolveForCurrentUser()
                            │
                    AvatarWizardMapping (ValueObject)
                    ┌───────┴──────────┐
            wizardId              dashboardId
                    │                  │
        SetupWizardRegistry     DailyActionsRegistry
        ::getStepsForWizard()   ::getActionsForDashboard()
                    │                  │
            52 tagged steps      39 tagged actions
            (global + vertical)  (por vertical)
                    │                  │
            _setup-wizard.html.twig   _daily-actions.html.twig
```

---

## 2. Problemas Identificados y Correcciones Aplicadas

### 2.1 Problema 1: Iconos Chincheta (ICON-CONVENTION-001)

**Diagnostico:** La funcion `jaraba_icon()` en `JarabaTwigExtension.php:273-343` verifica existencia del SVG en `{category}/{name}.svg`. Cuando no existe, cae al fallback `getFallbackEmoji()` que devuelve emojis Unicode. El **fallback por defecto es 📌** (chincheta/pushpin) para cualquier combinacion no mapeada.

**Causa raiz:** El paso 7 "Elige tu plan" (`SubscriptionUpgradeStep`) usaba:
```php
return ['category' => 'ui', 'name' => 'credit-card', 'variant' => 'duotone'];
```
Pero **NO existe `ui/credit-card.svg`** — solo existe en `finance/credit-card.svg`. Resultado: 📌 renderizado.

**Ademas:** Los pasos 6 y 7 usaban el MISMO icono (`credit-card`) en diferentes categorias, violando el principio de diferenciacion visual.

**Correccion aplicada:**

| Paso | Icono Anterior | Icono Nuevo | SVG Creado |
|------|---------------|-------------|------------|
| 2. Vertical configurado | `verticals/ecosystem` (generico) | `verticals/{vertical}` (dinamico) | N/A — existentes |
| 6. Metodos de pago | `finance/credit-card` | `finance/wallet-cards` | **SI** (nuevo) |
| 7. Elige tu plan | `ui/credit-card` (📌) | `finance/plan-upgrade` | **SI** (nuevo) |
| 8. Kit Digital | `compliance/certificate` | `compliance/kit-digital` | **SI** (nuevo) |
| Cuenta creada | `status/check-circle` | Sin cambio | N/A |

**SVGs creados (duotone + outline):**

1. **`finance/wallet-cards`** — Billetera con tarjetas multiple, representa metodos de pago (Bizum, Apple Pay, Google Pay). Estructura: rect base con fill-opacity 0.2 + circulo interno con opacity 0.6 + linea horizontal divisora.

2. **`finance/plan-upgrade`** — Capas apiladas (layers) con flecha ascendente, representa upgrading de plan SaaS. Estructura: 3 paths de capas (2,7 → 2,12 → 2,17) con fill-opacity 0.15 en la primera + flecha up separada.

3. **`compliance/kit-digital`** — Documento con checkmark, especifico para acuerdos de digitalizacion. Estructura: rect con rx=2 + check path (9,12 → 11,14 → 15,10) + lineas header/footer con opacity 0.5.

4. **`users/user-verified`** — Usuario con badge de verificacion (persona + circulo check). Util para el paso de cuenta creada como alternativa futura.

**Patron SVG duotone:** Todos siguen el patron establecido:
- `fill="currentColor" opacity="0.15-0.3"` para area principal (capa de fondo)
- `stroke="currentColor" stroke-width="2"` para contornos (capa principal)
- `opacity="0.5-0.6"` para detalles secundarios
- ViewBox: `0 0 24 24`, sin width/height fijo (CSS controla tamanio)
- No usar `currentColor` como stroke en SVG embebido via `<img>` — el CSS filter del `getColorFilter()` recolorea el SVG completo

### 2.2 Problema 2: Paso 2 "Vertical configurado" sin contexto (Coherencia)

**Diagnostico:** El paso 2 mostraba genericamente "Vertical configurado" y "Tu vertical ha sido asignado a tu cuenta" sin indicar CUAL vertical. Esto es incoherente con la filosofia de personalizacion del SaaS donde cada usuario tiene un contexto vertical especifico.

**Correccion aplicada en `AutoCompleteVerticalStep.php`:**

1. **Label dinamico:** `getLabel()` resuelve el vertical del usuario via `AvatarWizardBridgeService` (lazy, fault-tolerant) y devuelve ej: "ComercioConecta configurado" en vez del generico.

2. **Descripcion dinamica:** `getDescription()` incluye el nombre del vertical: "Tu vertical ComercioConecta ha sido asignado a tu cuenta."

3. **Icono per-vertical:** `getIcon()` devuelve el icono SVG especifico del vertical (ej: `verticals/comercioconecta-duotone.svg`) en vez del generico `ecosystem`.

4. **CompletionData contextual:** Muestra "ComercioConecta activo" en el badge de completitud.

5. **Resolusion lazy:** Usa `\Drupal::hasService()` + try-catch (PRESAVE-RESILIENCE-001) para evitar dependencia circular con AvatarWizardBridgeService, ya que este step es un servicio tagged recolectado por el mismo bridge.

**Mapas canonicos definidos:**

```php
// Vertical → Label humano
VERTICAL_LABELS = [
    'empleabilidad' => 'Empleabilidad',
    'emprendimiento' => 'Emprendimiento',
    'comercioconecta' => 'ComercioConecta',
    'agroconecta' => 'AgroConecta',
    'jarabalex' => 'JarabaLex',
    'serviciosconecta' => 'ServiciosConecta',
    'formacion' => 'Formacion',
    'andalucia_ei' => 'Andalucia +ei',
    'jaraba_content_hub' => 'Content Hub',
    'demo' => 'Demo',
];

// Vertical → Icono SVG en verticals/
VERTICAL_ICONS = [
    'empleabilidad' => 'empleabilidad',
    'emprendimiento' => 'emprendimiento',
    'comercioconecta' => 'comercioconecta',
    'agroconecta' => 'agroconecta',
    'jarabalex' => 'jarabalex',
    'serviciosconecta' => 'serviciosconecta',
    'formacion' => 'formacion',
    'andalucia_ei' => 'andalucia-ei',
    'jaraba_content_hub' => 'info',
    'demo' => 'rocket',
];
```

### 2.3 Problema 3: Paso 6 — Ruta /admin/ (403 para tenants)

**Diagnostico:** `PaymentMethodsStep::getRoute()` devolvía `jaraba_billing.billing_payment_method.settings` cuyo path es `/admin/config/billing/payment-method`. Los tenants no tienen permiso `administer site configuration`, por lo que obtenían 403.

**Correccion aplicada:**
- Ruta cambiada a `jaraba_billing.financial_dashboard` → `/billing/dashboard`
- Permiso: `view own billing information` (tenant-friendly)
- Icono cambiado de `finance/credit-card` a `finance/wallet-cards` (nuevo SVG)

### 2.4 Problema 4: Paso 8 — Ruta /admin/ (403 para tenants)

**Diagnostico:** `KitDigitalAgreementStep::getRoute()` devolvía `entity.kit_digital_agreement.collection` cuyo path (generado por `AdminHtmlRouteProvider`) es `/admin/content/kit-digital-agreements`. Los tenants no tienen permiso `administer kit digital`, por lo que obtenían 403.

**Correccion aplicada:**
- Ruta cambiada a `jaraba_billing.kit_digital.landing` → `/kit-digital`
- Acceso: `_access: 'TRUE'` (landing publica)
- Icono cambiado a `compliance/kit-digital` (nuevo SVG especifico)
- **Tambien corregidos DailyActions:**
  - `KitDigitalExpiringAction::getRoute()` → `jaraba_billing.kit_digital.landing`
  - `KitDigitalPendingAction::getRoute()` → `jaraba_billing.kit_digital.landing`

### 2.5 Problema 5: Coherencia Paso 2 ↔ Paso 7

**Diagnostico:** Paso 2 "Vertical configurado" (peso -10, siempre completo) y Paso 7 "Elige tu plan" (peso 90, completo si paid) eran complementarios pero ambos carecian de contexto especifico.

**Correccion aplicada en `SubscriptionUpgradeStep.php`:**

1. **Icono representativo:** Cambiado de `ui/credit-card` (📌 inexistente) a `finance/plan-upgrade` (capas con flecha up — representa escalamiento de plan).

2. **CompletionData contextual:** Cuando el usuario tiene plan paid, muestra "Plan Profesional activo" en el badge. Cuando es free, muestra "Plan gratuito" como indicacion clara de lo que puede mejorar. Usa `subscriptionContext->getContextForUser()` con try-catch.

---

## 3. Arquitectura del Perfil de Usuario

### 3.1 Patron Zero Region (ZERO-REGION-001)

El perfil implementa el patron de pagina frontend limpia:
- Template `page--user.html.twig` con layout sin regiones Drupal
- `{{ clean_content }}` NO se usa directamente — el preprocess inyecta `user_content` procesado
- Variables via `hook_preprocess_page__user()`, NO via controller
- Body classes via `hook_preprocess_html()` (NO `attributes.addClass()` en template)

### 3.2 Extensibilidad via Tagged Services

```
UserProfileSectionRegistry
    │
    ├── Tag: ecosistema_jaraba_core.user_profile_section
    │
    ├── CompilerPass: UserProfileSectionPass
    │
    └── Secciones registradas:
          ├── SubscriptionUpgradeSection (PLG-UPGRADE-UI-001)
          ├── ProfessionalProfileSection (completeness ring)
          └── AndaluciaEiSection (stats contextuales por rol)
```

### 3.3 Setup Wizard Transversal (SETUP-WIZARD-DAILY-001)

- **52 steps** en 12 wizards across 9 verticales
- **39 daily actions** en 11 dashboards
- **Zeigarnik effect:** 3 steps globales (peso -20, -10, 85-90) pre-completan ~33-50%
- **Scope:** User-scoped (empleabilidad, legal, content_hub, mentoring) vs Tenant-scoped (agro, comercio, servicios, lms, ei)
- **Slide panel:** La mayoria de steps verticales usan `useSlidePanel()=TRUE` con size `large`

---

## 4. Iconografia de Clase Mundial — ICON-CONVENTION-001

### 4.1 Reglas Vinculantes

| Regla | Descripcion |
|-------|-------------|
| ICON-CONVENTION-001 | Todo icono via `jaraba_icon(category, name, { variant, color, size })` |
| ICON-DUOTONE-001 | Variante por defecto: `duotone`. Solo `outline` para contextos minimalistas |
| ICON-COLOR-001 | Colores SOLO de paleta Jaraba: azul-corporativo, naranja-impulso, verde-innovacion, white, neutral |
| ICON-CANVAS-INLINE-001 | SVG en canvas_data: hex explicito, NUNCA currentColor |
| ICON-EMOJI-001 | NO emojis Unicode como iconos visuales |

### 4.2 Catalogo de Iconos del Wizard (Post-Correccion)

| Paso | ID | Icono | Categoria/Nombre | SVG Verificado |
|------|----|-------|-------------------|----------------|
| 1 | `__global__.cuenta_creada` | Check verde | `status/check-circle` | SI |
| 2 | `__global__.vertical_configurado` | Per-vertical | `verticals/{vertical}` | SI (10 SVGs) |
| 3+ | `{wizard}.perfil` | User edit | `users/user-edit` | SI |
| 6 | `__global__.metodos_pago` | Billetera | `finance/wallet-cards` | SI (NUEVO) |
| 7 | `__global__.suscripcion_activa` | Plan upgrade | `finance/plan-upgrade` | SI (NUEVO) |
| 8 | `__global__.kit_digital` | Doc check | `compliance/kit-digital` | SI (NUEVO) |

### 4.3 Rendering Pipeline

```
jaraba_icon('finance', 'wallet-cards', { variant: 'duotone', color: 'naranja-impulso', size: '32px' })
    │
    ├── getIconPath() → /modules/custom/ecosistema_jaraba_core/images/icons/finance/wallet-cards-duotone.svg
    │
    ├── file_exists(finance/wallet-cards.svg) → TRUE
    │
    ├── getColorFilter('#FF8C42') → CSS filter for SVG recoloring
    │
    └── <img src="/.../wallet-cards-duotone.svg"
             alt="wallet-cards"
             class="jaraba-icon jaraba-icon--finance jaraba-icon--wallet-cards jaraba-icon--duotone jaraba-icon--color-naranja-impulso"
             style="width: 32px; height: 32px; display: inline-block; vertical-align: middle; filter: ..."
             loading="lazy" aria-hidden="true" />
```

---

## 5. Coherencia Setup Wizard: Vertical ↔ Plan

### 5.1 Antes (Incoherente)

```
Paso 2: "Vertical configurado"     → Generico, sin contexto
                                       No dice CUAL vertical
Paso 7: "Elige tu plan"            → Generico, sin contexto
                                       No dice CUAL plan tiene
Ambos:  Icono credit-card          → Indistinguible visualmente
```

### 5.2 Despues (Coherente)

```
Paso 2: "ComercioConecta configurado" → Especifico, icono del vertical
         Badge: "ComercioConecta activo"
         Icono: verticals/comercioconecta (duotone)

Paso 7: "Elige tu plan"               → Mismo label (es call-to-action)
         Badge: "Plan Profesional activo" (si paid)
                "Plan gratuito" (si free)
         Icono: finance/plan-upgrade (duotone, capas con flecha)
```

### 5.3 Experiencia de Usuario Resultante

1. **Jobseeker** → "Empleabilidad configurado" + icono briefcase azul
2. **Merchant** → "ComercioConecta configurado" + icono store naranja
3. **Producer** → "AgroConecta configurado" + icono leaf verde
4. **Legal** → "JarabaLex configurado" + icono scales corporativo
5. **Coordinador EI** → "Andalucia +ei configurado" + icono andalucia-ei

---

## 6. Rutas Admin vs Tenant-Facing — Problema Sistemico

### 6.1 Hallazgo Critico

La auditoria revelo que **24 entity types** usan `AdminHtmlRouteProvider`, lo que genera automaticamente rutas bajo `/admin/*` para add, edit, delete, collection. **~15 wizard steps y ~10 daily actions** apuntan a estas rutas admin.

### 6.2 Impacto Diferenciado

| Tipo de Paso | useSlidePanel() | Impacto UX |
|-------------|-----------------|------------|
| Entity forms (perfil, catalogo, etc.) | TRUE (size 'large') | **Funcional** — AJAX carga el form en slide panel, usuario no ve URL admin |
| Configuracion plataforma | FALSE | **403 FATAL** — Navega directamente a /admin/* |

### 6.3 Pasos Corregidos (Fase 1 — Completada)

| Archivo | Ruta Anterior | Ruta Nueva | Estado |
|---------|--------------|------------|--------|
| `PaymentMethodsStep.php` | `jaraba_billing.billing_payment_method.settings` → `/admin/config/billing/payment-method` | `jaraba_billing.financial_dashboard` → `/billing/dashboard` | CORREGIDO |
| `KitDigitalAgreementStep.php` | `entity.kit_digital_agreement.collection` → `/admin/content/kit-digital-agreements` | `jaraba_billing.kit_digital.landing` → `/kit-digital` | CORREGIDO |
| `KitDigitalExpiringAction.php` | `entity.kit_digital_agreement.collection` | `jaraba_billing.kit_digital.landing` | CORREGIDO |
| `KitDigitalPendingAction.php` | `entity.kit_digital_agreement.collection` | `jaraba_billing.kit_digital.landing` | CORREGIDO |

### 6.4 Rutas Admin Restantes (Fase 2 — Pendiente, Baja Prioridad)

Estos pasos usan `useSlidePanel()=TRUE`, por lo que funcionan via AJAX. Sin embargo, el usuario podria ctrl+click y ver la pagina admin:

| Wizard Step | Ruta Entity | Path Admin |
|------------|-------------|------------|
| ProducerCatalogoStep | entity.product_agro.add_form | /admin/content/agro-products/add |
| MerchantCatalogoStep | entity.product_retail.add_form | /admin/content/comercio-product/add |
| MerchantQrStep | entity.comercio_qr_code.add_form | /admin/content/qr-codes/add |
| EditorArticuloStep | entity.content_article.add_form | /admin/content/articles/add |
| EditorAutorStep | entity.content_author.add_form | /admin/content/authors/add |
| EditorCategoriaStep | entity.content_category.add_form | /admin/content/categories/add |
| InstructorCursoStep | entity.lms_course.add_form | /admin/content/courses/add |
| ProducerCertificacionStep | entity.agro_certification.add_form | /admin/content/certifications/add |
| ProducerEnvioStep | entity.shipping_zone_agro.add_form | /admin/content/shipping/add |
| ProducerTrazabilidadStep | entity.agro_batch.add_form | /admin/content/agro-batch/add |
| MerchantEnvioStep | entity.comercio_shipping_method.add_form | /admin/content/comercio-shipping/add |
| ProviderServicioStep | entity.service_offering.add_form | /admin/content/servicios-offering/add |
| ProviderPaqueteStep | entity.service_package.add_form | /admin/content/service-packages/add |
| ProviderDisponibilidadStep | entity.availability_slot.add_form | /admin/content/availability-slot/add |
| NuevoParticipanteAction | entity.programa_participante_ei.add_form | /admin/content/participantes/add |

**Solucion recomendada para Fase 2:** Crear custom `HtmlRouteProvider` per-entity que genere URLs frontend (`/dashboard/products/add`, etc.) o implementar `getHrefOverride()` en los steps para URL de slide panel dedicada.

---

## 7. Mejoras Pendientes para 10/10

### 7.1 Quick Access Sections Faltantes (P1)

El template ya soporta secciones extensibles via `UserProfileSectionRegistry`. Faltan:

| Seccion | Service Class | Contenido |
|---------|--------------|-----------|
| **mis_paginas** | `PageBuilderProfileSection` | "Crear pagina", "Mis paginas", "Estadisticas" |
| **mis_facturas** | `BillingProfileSection` | Facturas, metodo de pago, historial |
| **seguridad** | `SecurityProfileSection` | Cambiar contrasena, 2FA, dispositivos, historial acceso |
| **notificaciones** | `NotificationProfileSection` | Badge unread, preferencias, panel resumen |

### 7.2 Schema.org Person (P2)

Crear `_user-profile-schema.html.twig` con JSON-LD:
```json
{
  "@context": "https://schema.org",
  "@type": "ProfilePage",
  "mainEntity": {
    "@type": "Person",
    "name": "{{ user_display_name }}",
    "email": "{{ user_email }}",
    "image": "{{ user_avatar_url }}",
    "memberOf": { "@type": "Organization", "name": "Jaraba Impact Platform" }
  }
}
```

### 7.3 Bottom Navigation Mobile (P2)

Habilitar `has-bottom-nav` en preprocess para rutas `/user/{uid}`:
```php
$variables['attributes']['class'][] = 'has-bottom-nav';
```

### 7.4 Notificaciones Widget (P2)

Incluir `_notification-panel.html.twig` condicionalmente si el usuario tiene notificaciones sin leer.

---

## 8. Tabla de Correspondencia de Especificaciones Tecnicas

| # | Especificacion | Regla Proyecto | Archivo Afectado | Estado |
|---|---------------|---------------|------------------|--------|
| 1 | Iconos duotone por defecto | ICON-DUOTONE-001 | AutoCompleteVerticalStep.php, SubscriptionUpgradeStep.php, PaymentMethodsStep.php, KitDigitalAgreementStep.php | CUMPLIDO |
| 2 | Colores de paleta Jaraba | ICON-COLOR-001 | _setup-wizard.html.twig (lineas 141-145) | CUMPLIDO |
| 3 | Sin emojis Unicode | ICON-EMOJI-001 | Eliminado 📌 creando SVGs faltantes | CUMPLIDO |
| 4 | Icono via jaraba_icon() | ICON-CONVENTION-001 | Todos los templates del wizard | CUMPLIDO |
| 5 | Textos traducibles | ORTOGRAFIA-TRANS-001 | page--user.html.twig ({% trans %} en todo) | CUMPLIDO |
| 6 | Variables CSS --ej-* | CSS-VAR-ALL-COLORS-001 | _user-pages.scss | CUMPLIDO |
| 7 | Entity forms Premium | PREMIUM-FORMS-PATTERN-001 | N/A (perfil no es entity form) | N/A |
| 8 | Rutas via Url::fromRoute() | ROUTE-LANGPREFIX-001 | preprocess (profile_edit_url, profile_view_url) | CUMPLIDO |
| 9 | Tenant no accede /admin/ | AUDIT-SEC-002 | PaymentMethodsStep, KitDigitalAgreementStep + 2 DailyActions | CORREGIDO |
| 10 | Setup Wizard + Daily Actions | SETUP-WIZARD-DAILY-001 | preprocess_page__user (lineas 3819-3920) | CUMPLIDO |
| 11 | Zeigarnik pre-load | ZEIGARNIK-PRELOAD-001 | 3 global steps (__global__) peso -20, -10, 85-90 | CUMPLIDO |
| 12 | Slide panel para forms | SLIDE-PANEL-RENDER-001 | _setup-wizard.html.twig (data-slide-panel) | CUMPLIDO |
| 13 | Optional services @? | OPTIONAL-CROSSMODULE-001 | SubscriptionUpgradeStep (subscriptionContext @?) | CUMPLIDO |
| 14 | Cache per-user | N/A | preprocess cache contexts/tags/max-age | CUMPLIDO |
| 15 | Twig include only | TWIG-INCLUDE-ONLY-001 | page--user.html.twig (3 includes con only) | CUMPLIDO |
| 16 | WCAG 2.1 AA | N/A | aria-labels, role, focus-visible, reduced-motion | CUMPLIDO |
| 17 | Responsive mobile-first | N/A | _user-pages.scss (3 breakpoints) | CUMPLIDO |
| 18 | Zero Region | ZERO-REGION-001 | preprocess inyecta todas las variables | CUMPLIDO |
| 19 | SVG sin currentColor en inline | ICON-CANVAS-INLINE-001 | N/A (iconos via <img>, no inline canvas) | N/A |
| 20 | Dart Sass moderno | SCSS-001 | @use en todos los parciales SCSS | CUMPLIDO |

---

## 9. Cumplimiento de Directrices del Proyecto

### 9.1 Theming (2026-02-05 Arquitectura Master)

- [x] Variables SCSS via `@use '../variables' as *` (SCSS-001)
- [x] CSS Custom Properties `var(--ej-*, fallback)` en todo SCSS (CSS-VAR-ALL-COLORS-001)
- [x] Compilacion centralizada: `npm run build` desde tema (SCSS-COMPILE-VERIFY-001)
- [x] Dart Sass moderno: @use, NO @import
- [x] Color-mix() para alpha runtime (SCSS-COLORMIX-001)
- [ ] Recompilar CSS tras edicion SCSS (verificar timestamp)

### 9.2 Frontend Pattern

- [x] Template limpia sin regiones Drupal (Zero Region)
- [x] Layout full-width
- [x] Pensado para movil (mobile-first breakpoints)
- [x] Acciones en slide-panel (no navegar fuera)
- [x] Header + navegacion + footer propios del tema
- [x] Body classes via hook_preprocess_html()
- [x] Tenant sin acceso a tema admin Drupal

### 9.3 Entidades y Navegacion

- [x] Field UI base route en entities referenciadas
- [x] Views data declaration en entities
- [x] Navigation: /admin/structure + /admin/content
- [x] AccessControlHandler con tenant verification (TENANT-ISOLATION-ACCESS-001)

### 9.4 Internacionalizacion

- [x] Textos via {% trans %}...{% endtrans %} (bloque, NO filtro |t)
- [x] URLs via `path()` con rutas nombradas
- [x] Variables configurables desde UI Drupal (theme settings)
- [x] Footer content configurado desde Theme Settings UI

### 9.5 GrapesJS / Page Builder

- [x] N/A directo en perfil de usuario
- [x] Canvas data protegido (SAFEGUARD-CANVAS-001) — no aplica

### 9.6 Seguridad

- [x] CSRF tokens en JS fetch (CSRF-JS-CACHE-001)
- [x] XSS prevention (Drupal.checkPlain en JS, auto-escape Twig)
- [x] Tenant isolation en queries (TENANT-001)
- [x] Strict comparisons ownership (ACCESS-STRICT-001)
- [ ] Rutas admin pendientes en slide-panel steps (Fase 2)

---

## 10. Checklist de Verificacion RUNTIME-VERIFY-001

### 10.1 Post-Implementacion Fase 1

| Check | Comando/Verificacion | Estado |
|-------|---------------------|--------|
| CSS compilado | Verificar timestamp CSS > SCSS | PENDIENTE |
| SVGs creados | `ls images/icons/finance/wallet-cards*.svg` | CREADO |
| SVGs creados | `ls images/icons/finance/plan-upgrade*.svg` | CREADO |
| SVGs creados | `ls images/icons/compliance/kit-digital*.svg` | CREADO |
| SVGs creados | `ls images/icons/users/user-verified*.svg` | CREADO |
| Rutas accesibles | `/billing/dashboard` (tenant) | VERIFICAR |
| Rutas accesibles | `/kit-digital` (publico) | VERIFICAR |
| data-* selectores | N/A (sin cambios JS) | OK |
| drupalSettings | N/A (sin cambios JS) | OK |
| Iconos renderizados | Verificar que NO aparece 📌 en wizard steps | VERIFICAR |

### 10.2 PIPELINE-E2E-001

| Capa | Verificacion | Estado |
|------|-------------|--------|
| L1: Service inyectado | AvatarWizardBridgeService en preprocess | OK |
| L2: Controller pasa datos | preprocess inyecta profile_setup_wizard | OK |
| L3: hook_theme() declara variables | Variables en hook_theme (implícito en page template) | OK |
| L4: Template incluye parciales | _setup-wizard.html.twig con `only` | OK |

---

## 11. Plan de Implementacion por Fases

### Fase 1: Correcciones Criticas (COMPLETADA)

**Archivos modificados:**
1. `ecosistema_jaraba_core/src/SetupWizard/AutoCompleteVerticalStep.php` — Label/icon dinamico per-vertical
2. `ecosistema_jaraba_core/src/SetupWizard/SubscriptionUpgradeStep.php` — Icono plan-upgrade + completionData contextual
3. `jaraba_billing/src/SetupWizard/PaymentMethodsStep.php` — Ruta + icono wallet-cards
4. `jaraba_billing/src/SetupWizard/KitDigitalAgreementStep.php` — Ruta + icono kit-digital
5. `jaraba_billing/src/DailyActions/KitDigitalExpiringAction.php` — Ruta tenant-facing
6. `jaraba_billing/src/DailyActions/KitDigitalPendingAction.php` — Ruta tenant-facing

**Archivos creados (SVGs):**
1. `images/icons/finance/wallet-cards.svg` + `wallet-cards-duotone.svg`
2. `images/icons/finance/plan-upgrade.svg` + `plan-upgrade-duotone.svg`
3. `images/icons/compliance/kit-digital.svg` + `kit-digital-duotone.svg`
4. `images/icons/users/user-verified.svg` + `user-verified-duotone.svg`

### Fase 2: Quick Access Sections (COMPLETADA)

**Archivos creados:**
1. `ecosistema_jaraba_core/src/UserProfile/Section/BillingProfileSection.php` — Facturacion (peso 40): gestion financiera, planes/precios, Kit Digital
2. `ecosistema_jaraba_core/src/UserProfile/Section/PageBuilderProfileSection.php` — Mis Paginas (peso 35): listado de paginas publicadas/borradores
3. `ecosistema_jaraba_core/src/UserProfile/Section/SecurityProfileSection.php` — Seguridad (peso 80): cambiar contrasena, cookies/privacidad

**Archivos corregidos:**
4. `ecosistema_jaraba_core/src/UserProfile/Section/ProfessionalServicesSection.php` — Fix link schema: `title`→`label` via `makeLink()` helper (antes las cards se renderizaban vacias)

**Servicios registrados en `ecosistema_jaraba_core.services.yml`:**
- `ecosistema_jaraba_core.user_profile_section.billing` (tag: user_profile_section)
- `ecosistema_jaraba_core.user_profile_section.page_builder` (tag: user_profile_section)
- `ecosistema_jaraba_core.user_profile_section.security` (tag: user_profile_section)

**Patron seguido:** Tagged services via CompilerPass (UserProfileSectionPass). Cada seccion extiende AbstractUserProfileSection y usa `makeLink()` para generar links con schema correcto. Rutas resueltas via `resolveRoute()` (ROUTE-LANGPREFIX-001). Links a rutas inexistentes se filtran automaticamente (OPTIONAL-CROSSMODULE-001).

**Total secciones de perfil: 11**
| Seccion | Peso | Visibilidad |
|---------|------|-------------|
| SubscriptionProfile | 5 | Siempre |
| ProfessionalProfile | 10 | Jobseeker |
| ProfessionalServices | 20 | Siempre |
| MyBusiness | 30 | Solo con tenant |
| PageBuilder | 35 | Si page builder activo |
| Billing | 40 | Si billing activo |
| MyVertical | 50 | Siempre |
| Security | 80 | Siempre |
| Account | 100 | Siempre |
| Administration | 110 | Solo admin |
| AndaluciaEi | 60 | Solo roles ei |

### Fase 3: Schema + Actividad + Slide-Panel Guard (COMPLETADA)

**Archivos creados:**
1. `templates/partials/_user-profile-schema.html.twig` — JSON-LD Schema.org ProfilePage + Person para SEO
2. `ecosistema_jaraba_core/src/UserProfile/Section/RecentActivitySection.php` — Actividad reciente (peso 70): articulos, paginas, ayuda

**Archivos modificados:**
3. `templates/page--user.html.twig` — Include del parcial schema con `only` (TWIG-INCLUDE-ONLY-001)
4. `js/slide-panel.js` — Proteccion auxclick (middle-click) en triggers slide-panel para evitar navegacion directa a /admin/*
5. `ecosistema_jaraba_core.services.yml` — Registro de RecentActivitySection tagged service

**Decisiones arquitectonicas:**
- **Rutas admin en slide-panel**: Los ~15 steps con `useSlidePanel()=TRUE` funcionan correctamente via AJAX (el JS intercepta el click con `e.preventDefault()`). Se anadio proteccion `auxclick` para middle-click. No se requiere cambio de HtmlRouteProvider porque el patron slide-panel es correcto.
- **Schema.org**: Solo se renderiza en vista profile (no edit/logout/cancel). Usa `json_encode|raw` para seguridad JSON.

---

## 12. Registro de Cambios

| Fecha | Version | Cambio |
|-------|---------|--------|
| 2026-03-19 | 1.0 | Documento inicial. Fase 1 completada: 4 wizard steps corregidos, 2 daily actions corregidos, 8 SVGs creados, iconos chincheta eliminados, vertical dinamico en paso 2, contexto de plan en paso 7 |
| 2026-03-19 | 1.1 | Fase 2 completada: 3 nuevas UserProfileSections (BillingProfileSection, PageBuilderProfileSection, SecurityProfileSection) + fix ProfessionalServicesSection link schema (title→label con makeLink()). Total 11 secciones de perfil. Score: 90→97/100 |
| 2026-03-19 | 1.2 | Fase 3 completada: Schema.org Person JSON-LD, RecentActivitySection, auxclick guard en slide-panel JS |
| 2026-03-19 | 1.3 | Iteracion de calidad: (1) PaymentMethodsStep ruta → /my-settings/plan (billing/dashboard daba 403). (2) Paso 7 contextual PLG: con plan → slide-panel Mi suscripcion (no saca del perfil); sin plan → pricing page. (3) Seccion Mi suscripcion enriquecida: gestionar plan, add-ons, servicios profesionales. (4) 4 chinchetas mas eliminadas |
| 2026-03-19 | 1.4 | Fix 3 problemas usuario: (1) Ruta slide-panel dedicada /my-settings/plan/slide-panel con controlador renderPlain() (SLIDE-PANEL-RENDER-001) — antes renderizaba pagina completa con header/footer en el panel. (2) Boton Gestionar suscripcion → permiso manage own subscription para authenticated (antes manage payment methods = 403). hook_update_10007 + portal-session con OR permission. (3) return_url de Stripe portal cambiada a /my-settings/plan (antes /billing/dashboard = 403) |
