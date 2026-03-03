# Plan de Implementacion: Verticalizacion Completa de Planes y Precios SaaS — Clase Mundial

**Fecha de creacion:** 2026-03-03
**Ultima actualizacion:** 2026-03-03
**Autor:** Claude (Anthropic) — Arquitecto SaaS / Implementacion
**Version:** 1.0.0
**Categoria:** Plan de Implementacion
**Codigo:** VERT-PRICING-001
**Estado:** EN IMPLEMENTACION
**Esfuerzo estimado:** 40-55h (6 fases)
**Documentos fuente:** Doc 158 (Pricing Matrix), REM-PRECIOS-001, Plan Remediacion v2.1
**Directrices aplicables:** `00_DIRECTRICES_PROYECTO.md` v105.0.0, `00_FLUJO_TRABAJO_CLAUDE.md` v58.0.0
**Modulos afectados:** `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`
**Rutas principales:** `/planes` (hub), `/{vertical}/planes` (7 verticales)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnostico: Gaps Identificados](#2-diagnostico-gaps-identificados)
3. [Matriz de Precios por Vertical](#3-matriz-de-precios-por-vertical)
4. [Arquitectura de la Solucion](#4-arquitectura-de-la-solucion)
5. [Fases de Implementacion](#5-fases-de-implementacion)
   - 5.1 [Fase 1: Datos Fundacionales (config/sync)](#51-fase-1-datos-fundacionales)
   - 5.2 [Fase 2: Correccion de Bugs Criticos](#52-fase-2-correccion-de-bugs-criticos)
   - 5.3 [Fase 3: Paginas de Pricing por Vertical](#53-fase-3-paginas-de-pricing-por-vertical)
   - 5.4 [Fase 4: Hub /planes Rediseñado](#54-fase-4-hub-planes-rediseñado)
   - 5.5 [Fase 5: Integracion Landing Pages + Onboarding](#55-fase-5-integracion-landing-pages--onboarding)
   - 5.6 [Fase 6: FreemiumVerticalLimit para 6 Verticales](#56-fase-6-freemiumverticallimit)
6. [Tabla de Archivos Modificados](#6-tabla-de-archivos-modificados)
7. [Tabla de Correspondencia con Especificaciones](#7-tabla-de-correspondencia-con-especificaciones)
8. [Tabla de Cumplimiento de Directrices](#8-tabla-de-cumplimiento-de-directrices)
9. [Verificacion RUNTIME-VERIFY-001](#9-verificacion-runtime-verify-001)
10. [Coherencia con Documentacion Tecnica Existente](#10-coherencia-con-documentacion-tecnica-existente)
11. [Registro de Cambios](#11-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Verticalizacion completa del sistema de planes y precios del SaaS para que cada uno de los 7 verticales con landing page tenga su propia pagina de pricing con features, limites y precios adaptados a su mercado. Transformacion de `/planes` de pagina generica a hub de navegacion entre verticales. Correccion de todos los gaps entre "el codigo existe" y "el usuario lo experimenta".

### 1.2 Por que se implementa

El diagnostico revela que:
- Solo 1 de 10 entidades Vertical esta creada en la BD (agroconecta)
- Los 3 SaasPlan existentes son genericos y modelados para agro (limites: "productores")
- 0 SaasPlanTier y 0 SaasPlanFeatures ConfigEntities existen en config/sync (pese a que la infraestructura de codigo esta completa)
- `/planes` cae a fallback hardcoded con precios genericos
- Landing pages enlazan a `/planes` generico rompiendo el funnel de conversion
- Stripe Price IDs vacios — no se puede cobrar
- Los datos de seed existen en config/install (24 SaasPlan, 21 PlanFeatures, 3 PlanTier) pero NUNCA fueron importados a produccion

### 1.3 Alcance

- **7 verticales prioritarios**: empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta, formacion
- **3 verticales secundarios** (solo entidad Vertical): andalucia_ei, jaraba_content_hub, demo
- **3 tiers por vertical**: Starter (gratis), Professional, Enterprise
- **Nuevas rutas**: `/{vertical}/planes` para cada vertical
- **Hub rediseñado**: `/planes` como comparador entre verticales

### 1.4 Filosofia

"Sin Humo" — La infraestructura de codigo ya existe (PlanResolverService cascade, MetaSitePricingService, SaasPlanTier/Features entities). El problema es 100% de DATOS y ROUTING. No necesitamos inventar nueva arquitectura, sino llenar los contenedores vacios y conectar las tuberias.

### 1.5 Relacion con documentacion tecnica existente

Este plan es una **continuacion natural** de:
- **Doc 158** (`20260119d-158_Platform_Vertical_Pricing_Matrix_v1_Claude.md`): Define la arquitectura de precios modulares con add-ons. Este plan implementa la capa de pricing pages publicas y datos fundacionales.
- **REM-PRECIOS-001** (`20260223c-Plan_Implementacion_Remediacion_Arquitectura_Precios_Configurables_v1_Claude.md`): Define la arquitectura de ConfigEntities (SaasPlanTier, SaasPlanFeatures) y PlanResolverService v2. La infraestructura de codigo de ese plan ya esta construida; este plan llena los datos y crea las rutas frontend.

**Principios heredados de ambos documentos:**
- Fuente de verdad financiera: **Stripe** (precios de billing). Las ConfigEntities almacenan metadata y limites.
- Fuente de verdad de features/limites: **SaasPlanFeatures ConfigEntity** con cascade `{vertical}_{tier}` -> `_default_{tier}`
- Normalizacion de planes: **PlanResolverService::normalize()** via aliases editables
- Separacion de responsabilidades: ConfigEntities no almacenan precios de billing, solo Stripe Price IDs
- Los precios EUR mostrados en pricing pages son **indicativos** y provienen de los SaasPlan ContentEntities (datos de seed)

### 1.6 Estimacion

| Fase | Descripcion | Horas est. |
|------|-------------|-----------|
| F1 | Datos fundacionales (config/sync + update hook) | 8-10h |
| F2 | Correccion de bugs criticos | 3-4h |
| F3 | Paginas de pricing por vertical | 10-14h |
| F4 | Hub /planes rediseñado | 6-8h |
| F5 | Integracion landing pages + onboarding | 5-7h |
| F6 | FreemiumVerticalLimit para 6 verticales | 8-12h |
| **Total** | | **40-55h** |

### 1.7 Riesgos y Mitigacion

| Riesgo | Impacto | Mitigacion |
|--------|---------|-----------|
| Vertical es ContentEntity — no se exporta via config/sync | ALTO | Update hook `_update_9024()` para crear entidades programaticamente |
| SaasPlan.features usa lista cerrada de 15 valores | MEDIO | Ampliar `allowed_values` en baseFieldDefinitions o migrar a text_long |
| config/install data desactualizada vs realidad del modulo | MEDIO | Revisar y ajustar cada YAML antes de copiar a config/sync |
| Precios indicativos vs Stripe real | BAJO | Doc 158 establece que Stripe es fuente de verdad financiera; pricing pages muestran precios indicativos con disclaimer |
| Cache de 1h en pricing pages | BAJO | Invalidacion via cache tags al importar config |

---

## 2. Diagnostico: Gaps Identificados

| ID | Severidad | Capa | Gap | Estado actual | Estado esperado |
|----|-----------|------|-----|---------------|-----------------|
| GAP-VERT-001 | CRITICO | Config | Solo 1/10 Vertical entities en BD | `agroconecta` unica | 10 verticales creados |
| GAP-TIER-001 | CRITICO | Config | 0 SaasPlanTier en config/sync | Fallback hardcoded en MetaSitePricingService | 3 tiers (starter/professional/enterprise) |
| GAP-FEAT-001 | CRITICO | Config | 0 SaasPlanFeatures en config/sync | Cascade siempre retorna NULL | 21+ configs (7 vert x 3 tiers) |
| GAP-PLAN-001 | CRITICO | Config | 3 SaasPlan genericos (modelo agro) | "Basico 29EUR" sin vertical | 21+ planes verticalizados |
| GAP-ROUTE-001 | ALTO | Routing | No existe `/{vertical}/planes` | Todas las landings enlazan a `/planes` generico | Pagina de pricing dedicada por vertical |
| GAP-HUB-001 | ALTO | UX | `/planes` muestra 3 tiers genericos | Confuso para 7 verticales distintos | Hub comparativo que enlaza a cada vertical |
| GAP-PRICE-001 | ALTO | Template | `pricing-page.html.twig` hardcodea "Desde 29EUR" | Precio estatico ignorando datos | Precios dinamicos desde SaasPlanTier + SaasPlan |
| GAP-URL-001 | MEDIO | Codigo | JarabaLex landing hardcodea `'/planes'` | ROUTE-LANGPREFIX-001 violado | Usar `Url::fromRoute()` |
| GAP-FREEM-001 | MEDIO | Config | FreemiumVerticalLimit solo para agroconecta (12) | 0 configs para otros 6 verticales | ~72 configs adicionales |
| GAP-STRIPE-001 | BAJO | Config | Todos los Stripe Price IDs vacios | No se puede cobrar | Configurar tras crear productos en Stripe Dashboard |
| GAP-FORM-001 | BAJO | Routing | No existe landing para formacion | Vertical activo sin landing publica | Landing de 9 secciones para LMS |

---

## 3. Matriz de Precios por Vertical

Precios de los SaasPlan seed data existentes en `config/install/` (fuente de verdad para pricing pages indicativos). Los precios reales de billing viven en Stripe (Doc 158, seccion 5).

| Vertical | Starter/mes | Professional/mes | Enterprise/mes | Prof/anual | Ent/anual |
|----------|------------|-----------------|---------------|-----------|----------|
| **empleabilidad** | 29EUR | 79EUR | 199EUR | 290EUR (seed) | 1990EUR (seed) |
| **emprendimiento** | 29EUR | 79EUR | 199EUR | 290EUR (seed) | 1990EUR (seed) |
| **comercioconecta** | 29EUR | 59EUR | 149EUR | 290EUR (seed) | 1490EUR (seed) |
| **agroconecta** | 29EUR | 59EUR | 149EUR | 290EUR (seed) | 1490EUR (seed) |
| **jarabalex** | 49EUR | 99EUR | 199EUR | 490EUR (seed) | 1990EUR (seed) |
| **serviciosconecta** | 29EUR | 59EUR | 149EUR | 290EUR (seed) | 1490EUR (seed) |
| **andalucia_ei** | 49EUR | 99EUR | 249EUR | 490EUR (seed) | 2490EUR (seed) |
| **formacion** | 39EUR | 99EUR | 199EUR | 390EUR (nuevo) | 1990EUR (nuevo) |

**Nota:** Estos precios provienen de los YAMLs de `config/install/ecosistema_jaraba_core.saas_plan.*.yml`. Los precios definitivos para Stripe se configuraran desde Stripe Dashboard y se vincularan via `stripe_price_id` en SaasPlanFeatures (REM-PRECIOS-001, seccion 7.2).

**Nota sobre Doc 158:** El Doc 158 define precios ligeramente diferentes en algunos verticales (ej: AgroConecta 49/129/249). Los datos de seed en config/install representan la decision mas reciente y prevalecen. Los add-ons de marketing (jaraba_crm, jaraba_email, etc.) definidos en Doc 158 son un sistema paralelo que no forma parte de este plan de implementacion.

---

## 4. Arquitectura de la Solucion

### 4.1 Flujo de Datos (ya existente, sin cambios — REM-PRECIOS-001 seccion 3.2)

```
SaasPlanTier (ConfigEntity)        SaasPlanFeatures (ConfigEntity)
  starter / professional / enterprise    {vertical}_{tier} -> _default_{tier}
          |                                        |
     PlanResolverService <-- cascade resolution -->
          |
     MetaSitePricingService::getPricingPreview($vertical)
          |
     PricingController -> pricing-page.html.twig
```

### 4.2 Cambio arquitectonico: nueva ruta `/{vertical_key}/planes`

```
ANTES:
  /agroconecta (landing) -> CTA "Ver planes" -> /planes (generico, sin contexto vertical)

DESPUES:
  /agroconecta (landing) -> CTA "Ver planes" -> /agroconecta/planes (pricing vertical-specific)
  /planes -> Hub comparativo -> enlaza a /{vertical}/planes
```

### 4.3 Entidades y ConfigEntities involucradas

| Tipo | Entity | config/install | config/sync actual | config/sync objetivo |
|------|--------|---------------|-------------------|---------------------|
| ConfigEntity | SaasPlanTier | 3 (starter, prof, ent) | 0 | 3 (copiar de install) |
| ConfigEntity | SaasPlanFeatures | 21 (7 vert x 3 tiers) | 0 | 27 (21 existentes + 6 nuevos formacion/jarabalex) |
| ContentEntity | Vertical | 2 (agro, jarabalex) | N/A (ContentEntity) | 10 (via update hook) |
| ContentEntity | SaasPlan | 21 per-vertical + 3 generic | N/A (ContentEntity) | Deprecar generics + crear missing via update hook |
| ConfigEntity | FreemiumVerticalLimit | 12 (agroconecta) | 12 (agroconecta) | +72 (6 vert adicionales) |

### 4.4 Patron Zero Region para `/{vertical}/planes`

La pagina de pricing por vertical seguira el mismo patron que la pagina `/planes` actual:
- Controller devuelve render array con `#theme => 'pricing_page'` (reutiliza template existente)
- Variables inyectadas via controller
- Template limpia sin `page.content` ni bloques
- Body class via `hook_preprocess_html()`: `page--vertical-pricing page--vertical-pricing--{key}`
- SCSS compilado en `scss/components/_pricing-page.scss` (ya existe)

### 4.5 Parciales Twig reutilizados

| Parcial existente | Uso en esta implementacion |
|-------------------|---------------------------|
| `_header.html.twig` | Header del hub /planes y /{vertical}/planes |
| `_footer.html.twig` | Footer configurable desde Theme Settings |
| `_landing-pricing-preview.html.twig` | Seccion 7 de landings (ya integrado) |
| `_copilot-fab.html.twig` | FAB copilot en todas las paginas |
| `pricing-page.html.twig` | Reutilizado para /{vertical}/planes (con mejoras) |

**Nuevo parcial necesario**:
- `pricing-hub-page.html.twig` — Template para el hub `/planes` rediseñado

---

## 5. Fases de Implementacion

### 5.1 Fase 1: Datos Fundacionales

**Objetivo**: Llenar los contenedores vacios de config/sync con los datos que el sistema ya soporta.

#### Tarea 1.1: Copiar SaasPlanTier a config/sync (1h)

Copiar 3 archivos de `config/install/` a `config/sync/`:
- `ecosistema_jaraba_core.plan_tier.starter.yml`
- `ecosistema_jaraba_core.plan_tier.professional.yml`
- `ecosistema_jaraba_core.plan_tier.enterprise.yml`

Sin modificaciones — el contenido de config/install es correcto.

#### Tarea 1.2: Copiar SaasPlanFeatures a config/sync (2h)

Copiar 21 archivos existentes de `config/install/` a `config/sync/`:
- 3 `_default_{tier}` (starter, professional, enterprise)
- 18 per-vertical (empleabilidad, emprendimiento, comercioconecta, agroconecta, serviciosconecta, andalucia_ei x 3 tiers)

**Crear 6 archivos NUEVOS** para los verticales sin config en install:
- `ecosistema_jaraba_core.plan_features.jarabalex_starter.yml`
- `ecosistema_jaraba_core.plan_features.jarabalex_professional.yml`
- `ecosistema_jaraba_core.plan_features.jarabalex_enterprise.yml`
- `ecosistema_jaraba_core.plan_features.formacion_starter.yml`
- `ecosistema_jaraba_core.plan_features.formacion_professional.yml`
- `ecosistema_jaraba_core.plan_features.formacion_enterprise.yml`

Estructura segun schema de REM-PRECIOS-001 seccion 6.2 (limits, feature_flags, stripe_prices, platform_fee_percent, sla).

#### Tarea 1.3: Update hook para Vertical ContentEntities (2h)

**Archivo**: `ecosistema_jaraba_core/ecosistema_jaraba_core.install`
**Hook**: `ecosistema_jaraba_core_update_9024()`

Crear 8 Vertical entities que faltan (agroconecta y jarabalex ya existen).
Idempotente: `loadByProperties(['machine_name' => $data['machine_name']])` antes de crear.

#### Tarea 1.4: Update hook para SaasPlan ContentEntities per-vertical (3h)

**Hook**: `ecosistema_jaraba_core_update_9025()`

Debe ejecutarse DESPUES de 9024 (las verticales deben existir para la FK).
Crear SaasPlan per-vertical con datos de `config/install/ecosistema_jaraba_core.saas_plan.*.yml`.
Crear 3 SaasPlan para formacion (no existen en config/install).
Ampliar `allowed_values` del campo `features` en SaasPlan si es necesario.

#### Tarea 1.5: Deprecar 3 SaasPlan genericos (0.5h)

Desactivar (status=FALSE) los 3 planes genericos obsoletos.
No eliminar — solo desactivar para preservar FK existentes de tenants.

### 5.2 Fase 2: Correccion de Bugs Criticos

#### Tarea 2.1: Verificar metodos SaasPlan (1h)

Verificar que `PricingController` llama a los metodos correctos. Añadir alias methods si hay mismatch.

#### Tarea 2.2: Fix precio hardcoded en pricing-page.html.twig (0.5h)

Reemplazar logica hardcoded por precios dinamicos desde datos del tier.

#### Tarea 2.3: Fix URL hardcoded en JarabaLex landing (0.5h)

Cambiar `'/planes'` por `Url::fromRoute()` (ROUTE-LANGPREFIX-001).

#### Tarea 2.4: Ampliar MetaSitePricingService con precios EUR (1.5h)

Consultar SaasPlan ContentEntities para incluir `price_monthly` y `price_yearly` en EUR reales en la respuesta de `getPricingPreview()`.

### 5.3 Fase 3: Paginas de Pricing por Vertical

#### Tarea 3.1: Nueva ruta `/{vertical_key}/planes` (1h)

Ruta con regex constraint para los 7 verticales prioritarios.

#### Tarea 3.2: Nuevo metodo `verticalPricingPage()` (3h)

Controller que reutiliza `#theme => 'pricing_page'` con datos del vertical.

#### Tarea 3.3: Mejorar pricing-page.html.twig (4h)

Precios dinamicos, toggle mensual/anual, CTAs contextuales, Schema.org, limits display, tabla comparativa.

#### Tarea 3.4: SCSS para toggle y comparativa (2h)

Extender SCSS existente. Dart Sass moderno, `var(--ej-*)`.

### 5.4 Fase 4: Hub /planes Rediseñado

#### Tarea 4.1: Nuevo template pricing-hub-page.html.twig (3h)

Grid de verticales con icono, nombre, tagline, "Desde X EUR", CTA.

#### Tarea 4.2: Hook theme + preprocess (1h)

Registrar `pricing_hub_page` en `hook_theme()` + body class.

#### Tarea 4.3: Refactorizar PricingController::pricingPage() (1.5h)

Cambiar de 3 tiers genericos a hub de verticales.

#### Tarea 4.4: SCSS para pricing-hub (1.5h)

Nuevo parcial SCSS para grid de verticales.

### 5.5 Fase 5: Integracion Landing Pages + Onboarding

#### Tarea 5.1: Modificar buildLanding() para CTAs verticalizados (1h)

Unico punto de cambio en `buildLanding()` para propagar URLs a las 7 landing pages.

#### Tarea 5.2: Actualizar parcial _landing-pricing-preview.html.twig (1h)

Verificar CTA y añadir atributos de tracking.

#### Tarea 5.3: Crear landing formacion (3h)

Nuevo metodo `formacion()` con 9 secciones estandar + nueva ruta.

#### Tarea 5.4: Actualizar onboarding selectPlan (1h)

Filtrar SaasPlan entities por vertical del tenant.

### 5.6 Fase 6: FreemiumVerticalLimit

#### Tarea 6.1: Crear configs para 6 verticales (8-12h)

~72 archivos YAML en `config/sync/`. Patron: `ecosistema_jaraba_core.freemium_vertical_limit.{vertical}_{tier}_{feature_key}.yml`

---

## 6. Tabla de Archivos Modificados

| # | Archivo | Accion | Fase |
|---|--------|--------|------|
| 1-3 | `config/sync/ecosistema_jaraba_core.plan_tier.{starter,professional,enterprise}.yml` | CREAR (copiar de install) | F1 |
| 4-24 | `config/sync/ecosistema_jaraba_core.plan_features.*.yml` (21 files) | CREAR (copiar de install) | F1 |
| 25-30 | `config/sync/ecosistema_jaraba_core.plan_features.{jarabalex,formacion}_{tier}.yml` (6 files) | CREAR nuevo | F1 |
| 31 | `ecosistema_jaraba_core/ecosistema_jaraba_core.install` | MODIFICAR (add update_9024, _9025) | F1 |
| 32 | `ecosistema_jaraba_core/src/Entity/SaasPlan.php` | MODIFICAR (alias methods) | F2 |
| 33 | `ecosistema_jaraba_core/src/Entity/SaasPlanInterface.php` | MODIFICAR (add signatures) | F2 |
| 34 | `ecosistema_jaraba_core/src/Service/MetaSitePricingService.php` | MODIFICAR (add EUR prices) | F2 |
| 35 | `ecosistema_jaraba_theme/templates/pricing-page.html.twig` | MODIFICAR (dynamic prices, toggle) | F3 |
| 36 | `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | MODIFICAR (add vertical pricing + formacion routes) | F3/F5 |
| 37 | `ecosistema_jaraba_core/src/Controller/PricingController.php` | MODIFICAR (add verticalPricingPage, refactor pricingPage) | F3/F4 |
| 38 | `ecosistema_jaraba_theme/templates/pricing-hub-page.html.twig` | CREAR nuevo template | F4 |
| 39 | `ecosistema_jaraba_core/ecosistema_jaraba_core.module` | MODIFICAR (add theme hook, preprocess) | F4 |
| 40 | `ecosistema_jaraba_theme/scss/components/_pricing-page.scss` | MODIFICAR (toggle, comparison) | F3 |
| 41 | `ecosistema_jaraba_theme/scss/components/_pricing-hub.scss` | CREAR nuevo parcial | F4 |
| 42 | `ecosistema_jaraba_core/src/Controller/VerticalLandingController.php` | MODIFICAR (fix URL, add formacion, modify buildLanding) | F2/F5 |
| 43 | `ecosistema_jaraba_theme/templates/partials/_landing-pricing-preview.html.twig` | MODIFICAR (analytics attrs) | F5 |
| 44 | `ecosistema_jaraba_core/src/Controller/OnboardingController.php` | MODIFICAR (filter plans by vertical) | F5 |
| 45-116 | `config/sync/ecosistema_jaraba_core.freemium_vertical_limit.*.yml` (~72 files) | CREAR nuevo | F6 |

---

## 7. Tabla de Correspondencia con Especificaciones

| Especificacion | Gap ID | Fase | Doc Fuente |
|---------------|--------|------|-----------|
| VERTICAL-CANONICAL-001 | GAP-VERT-001 | F1 | CLAUDE.md |
| PLAN-CASCADE-001 (resolution cascade) | GAP-FEAT-001, GAP-TIER-001 | F1 | REM-PRECIOS-001 sec 3.2, 7.1 |
| ROUTE-LANGPREFIX-001 | GAP-URL-001 | F2 | CLAUDE.md |
| ZERO-REGION-001 | GAP-ROUTE-001 | F3/F4 | CLAUDE.md |
| CSS-VAR-ALL-COLORS-001 | — | F3/F4 | CLAUDE.md |
| ICON-CONVENTION-001 | — | F4 | CLAUDE.md |
| Doc 158 Pricing Matrix | GAP-PLAN-001 | F1/F3 | 20260119d-158 |
| REM-PRECIOS-001 ConfigEntities | GAP-TIER-001, GAP-FEAT-001 | F1 | 20260223c |
| Doc 158 Add-ons | N/A (fuera de alcance) | — | 20260119d-158 sec 3 |

---

## 8. Tabla de Cumplimiento de Directrices

| Directriz | Estado | Detalle |
|-----------|--------|---------|
| VERTICAL-CANONICAL-001 | F1 | 10 verticales via update hook |
| ROUTE-LANGPREFIX-001 | F2/F3 | URLs via `Url::fromRoute()` |
| ZERO-REGION-001 | F3/F4 | Templates limpias |
| CSS-VAR-ALL-COLORS-001 | F3/F4 | `var(--ej-*, fallback)` |
| ICON-CONVENTION-001 | F4 | `jaraba_icon()` con duotone |
| SCSS-COMPILE-VERIFY-001 | F3/F4 | npm run build + verificar timestamps |
| SCSS-ENTRY-CONSOLIDATION-001 | F4 | Nuevo parcial importado en entry point |
| PREMIUM-FORMS-PATTERN-001 | N/A | SaasPlanForm ya extiende PremiumEntityFormBase |
| TENANT-BRIDGE-001 | F5 | Onboarding filtra por vertical del tenant |
| SECRET-MGMT-001 | N/A | Stripe Price IDs publicos en config |
| DOC-GUARD-001 | Post-impl | Master docs via Edit, commit separado |

---

## 9. Verificacion RUNTIME-VERIFY-001

### Tras Fase 1 (Datos):
- [ ] `drush config:import -y` ejecuta sin errores
- [ ] `/admin/config/jaraba/plan-tiers` muestra 3 tiers
- [ ] `/admin/config/jaraba/plan-features` muestra 27 configs
- [ ] `drush updb` ejecuta update_9024 y update_9025 sin errores
- [ ] `/admin/structure/verticales` muestra 10 verticales

### Tras Fase 2 (Bugs):
- [ ] PHPStan level 6 pasa en archivos modificados

### Tras Fase 3 (Pricing vertical):
- [ ] Ruta `/agroconecta/planes` retorna 200
- [ ] Ruta `/empleabilidad/planes` retorna 200
- [ ] Ruta `/jarabalex/planes` retorna 200
- [ ] Pricing cards muestran precios EUR dinamicos
- [ ] SCSS compilado: timestamp CSS > SCSS

### Tras Fase 4 (Hub):
- [ ] `/planes` muestra hub con 7 tarjetas
- [ ] Body class `page--pricing-hub` presente

### Tras Fase 5 (Landing integration):
- [ ] Landing `/agroconecta` pricing CTA enlaza a `/agroconecta/planes`
- [ ] Landing `/formacion` existe con 9 secciones

### Tras Fase 6 (Freemium):
- [ ] `drush config:import -y` importa ~72 FreemiumVerticalLimit configs

---

## 10. Coherencia con Documentacion Tecnica Existente

### 10.1 Doc 158 — Vertical Pricing Matrix

| Aspecto Doc 158 | Estado en este plan |
|-----------------|---------------------|
| Planes base por vertical (sec 2) | Implementado en F1 via SaasPlan seed + update hooks |
| Add-ons de marketing (sec 3) | FUERA DE ALCANCE — sistema paralelo para implementacion posterior |
| Matriz compatibilidad add-ons (sec 4) | FUERA DE ALCANCE |
| Estructura Stripe (sec 5) | Stripe Price IDs vacios; se configuraran post-implementacion |
| FeatureAccessService (sec 6) | Equivalente funcional: PlanResolverService + QuotaManagerService |
| UI selector add-ons (sec 7) | FUERA DE ALCANCE |
| ECA billing flows (sec 8) | FUERA DE ALCANCE |
| Metricas revenue (sec 10) | FUERA DE ALCANCE |

### 10.2 REM-PRECIOS-001 — Remediacion Arquitectura Precios

| Aspecto REM-PRECIOS | Estado en este plan |
|---------------------|---------------------|
| SaasPlanTier ConfigEntity (sec 6.1) | YA IMPLEMENTADO en codigo; este plan copia datos a config/sync |
| SaasPlanFeatures ConfigEntity (sec 6.2) | YA IMPLEMENTADO en codigo; este plan copia datos a config/sync |
| Formularios admin (sec 6.3) | YA IMPLEMENTADOS |
| Config install seed data (sec 6.4) | Este plan copia de install a sync + crea missing |
| PlanResolverService v2 (sec 7.1) | YA IMPLEMENTADO; este plan llena datos para que funcione |
| Mapping Stripe (sec 7.2) | Stripe IDs vacios; configuracion posterior |
| Tests de contrato (sec 9) | FUERA DE ALCANCE de este plan |
| Admin Center Plan UI (sec 10) | YA IMPLEMENTADO |
| Precios de Doc 158 como seed (sec 6.4) | Precios actualizados en config/install prevalecen sobre Doc 158 |

---

## 11. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-03 | 1.0.0 | Version inicial. 6 fases, ~116 archivos. Incorpora coherencia con Doc 158 y REM-PRECIOS-001. |
