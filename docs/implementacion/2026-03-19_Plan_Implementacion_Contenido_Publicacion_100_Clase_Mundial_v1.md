# Plan de Implementacion: Creacion y Publicacion de Contenido — Elevacion a 100/100 Clase Mundial

**Fecha de creacion:** 2026-03-19 16:00
**Ultima actualizacion:** 2026-03-19 16:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion / Auditoria / Elevacion Clase Mundial
**Codigo:** AUD-CONTENT-PUB-001
**Documentos fuente:**
- `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` (5 capas CSS tokens, v3.0)
- `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md` (Page Builder spec, v1.0)
- `docs/implementacion/2026-02-26_Plan_Consolidacion_ContentHub_Blog_Clase_Mundial_v1.md` (Content Hub consolidacion)
- `docs/implementacion/2026-02-26_Plan_Implementacion_GrapesJS_Content_Hub_v1.md` (GrapesJS + Content Hub)
- `docs/implementacion/2026-03-19_Plan_Implementacion_Demo_Elevacion_Conversion_Clase_Mundial_v1.md` (Demo conversion, patron de referencia)
**Hallazgos nuevos:** 31 (5 criticos, 9 altos, 12 medios, 5 bajos)
**Objetivo:** Alcanzar 100/100 en TODAS las dimensiones de creacion y publicacion de contenido: Page Builder, Content Hub, onboarding, PLG, conversión, salvaguardas

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto Estrategico](#2-contexto-estrategico)
3. [Auditoria Integral — Scorecard Actual](#3-auditoria-integral--scorecard-actual)
4. [Inventario Completo de Hallazgos](#4-inventario-completo-de-hallazgos)
   - 4.1 [Hallazgos Criticos (P0)](#41-hallazgos-criticos-p0)
   - 4.2 [Hallazgos Altos (P1)](#42-hallazgos-altos-p1)
   - 4.3 [Hallazgos Medios (P2)](#43-hallazgos-medios-p2)
   - 4.4 [Hallazgos Bajos (P3)](#44-hallazgos-bajos-p3)
5. [Arquitectura Actual — Mapa Completo](#5-arquitectura-actual--mapa-completo)
   - 5.1 [Dual-Track de Contenido: ContentArticle vs PageContent](#51-dual-track-de-contenido)
   - 5.2 [Page Builder — Gating por Plan](#52-page-builder--gating-por-plan)
   - 5.3 [Content Hub — Funcionalidad Basica por Tenant](#53-content-hub--funcionalidad-basica-por-tenant)
   - 5.4 [GrapesJS — Arquitectura de 14 Plugins](#54-grapesjs--arquitectura-de-14-plugins)
   - 5.5 [Perfil de Usuario — Hub de Acceso](#55-perfil-de-usuario--hub-de-acceso)
   - 5.6 [Onboarding — Setup Wizard + Daily Actions](#56-onboarding--setup-wizard--daily-actions)
   - 5.7 [Navegacion — Command Bar + Avatar Nav](#57-navegacion--command-bar--avatar-nav)
6. [Sprint 1: Page Builder — Onboarding Clase Mundial (P0)](#6-sprint-1-page-builder-onboarding-p0)
   - 6.1 [S1-01: Crear 4 SetupWizard Steps para Page Builder](#61-s1-01-crear-4-setupwizard-steps)
   - 6.2 [S1-02: Crear 4 DailyActions para Page Builder](#62-s1-02-crear-4-dailyactions)
   - 6.3 [S1-03: Integrar Wizard + Daily Actions en PageBuilderDashboardController (L1-L4)](#63-s1-03-integrar-wizard--daily-actions-en-dashboard)
   - 6.4 [S1-04: Crear page--page-builder.html.twig (Zero Region)](#64-s1-04-crear-template-zero-region)
   - 6.5 [S1-05: Registrar hook_theme() con variables wizard + daily_actions](#65-s1-05-registrar-hook-theme)
7. [Sprint 2: PLG Page Builder — Triggers de Conversion (P0)](#7-sprint-2-plg-page-builder-p0)
   - 7.1 [S2-01: Crear 9 FreemiumVerticalLimit para max_pages (free)](#71-s2-01-freemium-limits-free)
   - 7.2 [S2-02: Crear 9 FreemiumVerticalLimit para max_pages (starter)](#72-s2-02-freemium-limits-starter)
   - 7.3 [S2-03: Crear 9 FreemiumVerticalLimit para premium_blocks](#73-s2-03-freemium-limits-premium-blocks)
   - 7.4 [S2-04: Upgrade nudge modal en QuotaManagerService](#74-s2-04-upgrade-nudge-modal)
   - 7.5 [S2-05: Daily Action "Mejorar tu plan" contextual a Page Builder](#75-s2-05-daily-action-mejorar-plan)
8. [Sprint 3: Content Hub — Avatar Mapping + Completar Gaps (P1)](#8-sprint-3-content-hub-avatar-mapping-p1)
   - 8.1 [S3-01: Anadir avatar `editor_content_hub` en AvatarWizardBridgeService](#81-s3-01-avatar-mapping)
   - 8.2 [S3-02: Integrar wizard + daily_actions en page--user.html.twig para editor](#82-s3-02-integrar-en-perfil)
   - 8.3 [S3-03: Daily Action "Crear pagina" desde Content Hub dashboard](#83-s3-03-daily-action-crear-pagina)
   - 8.4 [S3-04: Cross-link entre Content Hub y Page Builder](#84-s3-04-cross-link)
9. [Sprint 4: Salvaguardas y Validadores (P0)](#9-sprint-4-salvaguardas-p0)
   - 9.1 [S4-01: validate-page-builder-onboarding.php — Validador de integridad wizard/daily](#91-s4-01-validador-onboarding)
   - 9.2 [S4-02: validate-content-pipeline-e2e.php — Pipeline E2E L1-L4](#92-s4-02-validador-pipeline)
   - 9.3 [S4-03: validate-plg-triggers.php — FreemiumVerticalLimit coverage](#93-s4-03-validador-plg)
   - 9.4 [S4-04: Canvas pre-save backup service (SAFEGUARD-CANVAS-001 Layer 1)](#94-s4-04-canvas-backup)
   - 9.5 [S4-05: Canvas integrity validator (SAFEGUARD-CANVAS-001 refuerzo)](#95-s4-05-canvas-integrity)
10. [Sprint 5: Conversion 10/10 — CTA + Micro-interacciones (P1)](#10-sprint-5-conversion-p1)
    - 10.1 [S5-01: Upgrade CTA contextual en PageBuilderDashboardController](#101-s5-01-upgrade-cta)
    - 10.2 [S5-02: Empty state premium con preview de bloques premium](#102-s5-02-empty-state-premium)
    - 10.3 [S5-03: Onboarding tour interactivo (OnboardingController existente)](#103-s5-03-onboarding-tour)
    - 10.4 [S5-04: Analytics de uso Page Builder via data-track-*](#104-s5-04-analytics-uso)
    - 10.5 [S5-05: Template starter pack (3 plantillas pre-creadas para nuevos tenants)](#105-s5-05-template-starter-pack)
11. [Tabla de Correspondencia con Especificaciones Tecnicas](#11-tabla-de-correspondencia)
12. [Tabla de Cumplimiento con Directrices del Proyecto](#12-tabla-de-cumplimiento-directrices)
13. [Estructura de Archivos](#13-estructura-de-archivos)
14. [Verificacion y Testing](#14-verificacion-y-testing)
15. [Salvaguardas Futuras Recomendadas](#15-salvaguardas-futuras)
16. [Riesgos y Mitigaciones](#16-riesgos-y-mitigaciones)
17. [Registro de Cambios](#17-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Problema

La plataforma Jaraba Impact tiene una arquitectura **dual-track de contenido** madura:
- **Content Hub** (`jaraba_content_hub`): Articulos y blog con 3 wizard steps + 4 daily actions + dashboard frontend completo
- **Page Builder** (`jaraba_page_builder`): Landing pages con GrapesJS, 14 plugins, quotas por plan, templates marketplace

Sin embargo, la auditoria integral revela que el Page Builder tiene un **deficit critico de onboarding**: **cero wizard steps, cero daily actions, sin avatar mapping**. Esto crea una brecha entre "el codigo existe" y "el usuario lo experimenta" (RUNTIME-VERIFY-001).

### 1.2 Diagnostico (19 marzo 2026)

| Dimension | Score Actual | Target | Gap |
|-----------|-------------|--------|-----|
| **Arquitectura multi-tenant** | 95/100 | 100 | Completo |
| **Gating por plan** | 85/100 | 100 | Sin FreemiumVerticalLimit para PB |
| **Content Hub onboarding** | 90/100 | 100 | Sin avatar mapping en AvatarWizardBridgeService |
| **Page Builder onboarding** | 30/100 | 100 | **0 wizard steps, 0 daily actions, 0 avatar mapping** |
| **PLG Page Builder** | 20/100 | 100 | **0 FreemiumVerticalLimit rules para PB** |
| **Canvas safeguards** | 75/100 | 100 | Layer 1 (pre-save backup) incompleta |
| **Conversion 10/10** | 60/100 | 100 | Sin upgrade nudges, sin starter pack |
| **Navegacion descubrimiento** | 80/100 | 100 | Solo profile hub + command bar |
| **Analytics conversion** | 40/100 | 100 | Sin data-track en flujo Page Builder |

### 1.3 Estrategia

5 sprints que elevan TODAS las dimensiones a 100/100:

| Sprint | Foco | Items | Impacto |
|--------|------|-------|---------|
| **S1** | Page Builder Onboarding (P0) | 5 | Wizard + Daily Actions + Zero Region template |
| **S2** | PLG Triggers (P0) | 5 | FreemiumVerticalLimit + upgrade nudges |
| **S3** | Content Hub Gaps (P1) | 4 | Avatar mapping + cross-links |
| **S4** | Salvaguardas (P0) | 5 | 3 validadores + canvas backup + integrity |
| **S5** | Conversion 10/10 (P1) | 5 | CTA contextual + starter pack + analytics |

### 1.4 Filosofia

Este plan sigue la filosofia **"Sin Humo"** del proyecto:
- Onboarding mediante valor demostrado, no popups agresivos
- Upgrade nudges contextuales (cuando el usuario alcanza un limite real, no antes)
- Page Builder accesible desde el primer dia para TODOS los planes
- Conversion PLG: el usuario experimenta el valor antes de pagar
- Textos siempre en espanol, traducibles via `{% trans %}` o `Drupal.t()`

---

## 2. Contexto Estrategico

### 2.1 Arquitectura Dual-Track de Contenido

La plataforma tiene dos sistemas complementarios de creacion de contenido, cada uno con su proposito y arquitectura:

**ContentArticle (jaraba_content_hub)** — Sistema de publicacion editorial:
- Entity translatable, revisionable, con workflow editorial (draft → review → scheduled → published → archived)
- Dual layout: `legacy` (textarea) y `canvas` (GrapesJS visual)
- SEO completo: seo_title, seo_description, answer_capsule (para IA)
- Engagement tracking: views_count, reading_time, sentiment_score
- Tenant isolation via `tenant_id` field
- Ruta publica: `/blog/{slug}` | Dashboard: `/content-hub`
- **Score onboarding: 90/100** (3 wizard + 4 daily actions + dashboard, falta avatar mapping)

**PageContent (jaraba_page_builder)** — Constructor de landing pages:
- Entity translatable, revisionable, puro visual (GrapesJS)
- 40+ bloques (hero, cta, pricing, testimonials, faq, stats, etc.)
- 14 plugins custom: canvas, blocks, icons, ai, assets, seo, reviews, marketplace, multipage, etc.
- Quota por plan (QuotaManagerService + PlanValidator)
- Ruta publica: `/page/{id}` o alias (`/pricing`, `/about`) | Dashboard: `/page-builder`
- **Score onboarding: 30/100** (SOLO dashboard, SIN wizard/daily/avatar)

### 2.2 Impacto de Negocio del Gap

El Page Builder es la funcionalidad **mas visual y diferenciadora** del SaaS. Sin embargo, la investigacion revela:

1. **Descubrimiento**: Un tenant nuevo llega a `/user/{uid}` → ve "Mis paginas" en la seccion PageBuilderProfileSection (weight: 35) → puede hacer clic. Pero **no recibe guia de onboarding**.

2. **Activacion**: Sin wizard steps, el tenant con plan Professional (25 paginas incluidas, €79/mes) no recibe ninguna secuencia "Crea tu primera landing", "Elige una plantilla", "Publica". El 80% probable que no active Page Builder.

3. **Conversion PLG**: Sin FreemiumVerticalLimit rules para Page Builder, no hay triggers de conversion cuando un tenant Free/Starter alcanza el limite de 5 paginas. El sistema PLG es ciego a este modulo.

4. **Retencion**: Sin daily actions ("Edita tu pagina principal", "Revisa el rendimiento"), el Page Builder se convierte en "set and forget" — el usuario no vuelve.

### 2.3 Referencia de Patron Exitoso

El vertical Demo (implementado Sprint 11, 19-mar-2026) demuestra el patron completo:
- 3 wizard steps (`DemoExplorarDashboardStep`, `DemoGenerarContenidoIAStep`, `DemoConvertirCuentaRealStep`)
- 3 daily actions (`ExplorarVerticalDemoAction`, `ChatCopilotDemoAction`, `ConvertirCuentaDemoAction`)
- Inyeccion L1-L4 verificada en `DemoController`
- `hook_theme()` con variables `wizard` y `daily_actions`
- Templates con `{% include ... only %}`

Este plan replica el mismo patron para Page Builder.

---

## 3. Auditoria Integral — Scorecard Actual

### 3.1 Scorecard por Dimension (Pre-Implementacion)

| # | Dimension | Score | Evidencia | Gap principal |
|---|-----------|-------|-----------|---------------|
| 1 | Arquitectura multi-tenant | 95/100 | TenantBridge, tenant_id en ambas entities, AccessControlHandler | Falta verificacion end-to-end en canvas editor multi-tenant |
| 2 | Gating por plan (QuotaManager) | 85/100 | PlanValidator, SaasPlanFeatures, add-ons | 0 FreemiumVerticalLimit para PB |
| 3 | GrapesJS (14 plugins) | 92/100 | Modular, deferred, dual-architecture | Plugin loading no aislado (1 fallo mata todos) |
| 4 | Content Hub onboarding | 90/100 | 3 wizard + 4 daily + dashboard frontend | Sin avatar mapping `editor_content_hub` |
| 5 | **Page Builder onboarding** | **30/100** | Solo dashboard controller | **0 wizard, 0 daily, 0 avatar** |
| 6 | **PLG Page Builder** | **20/100** | QuotaManager existe | **0 FreemiumVerticalLimit rules** |
| 7 | Canvas safeguards | 75/100 | Revisions + JSON validation + HTML sanitization | Layer 1 (pre-save backup) pendiente |
| 8 | Profile hub integracion | 85/100 | PageBuilderProfileSection (weight:35), RecentActivitySection | Solo link, sin resumen de estado |
| 9 | Navegacion descubrimiento | 80/100 | Command Bar, Avatar Nav | Sin shortcut bottom-nav / sidebar |
| 10 | Conversion 10/10 | 60/100 | Quota check en form, slide-panel | Sin upgrade nudge, sin starter pack |
| 11 | Analytics conversion | 40/100 | Basico en demo | Sin data-track en Page Builder |
| 12 | Salvaguardas (scripts) | 70/100 | 42 scripts, 0 para PB onboarding | Necesita 3 nuevos validadores |
| 13 | i18n textos interfaz | 95/100 | {% trans %} en templates existentes | Verificar nuevos templates |
| 14 | SCSS/CSS tokens | 95/100 | var(--ej-*) en 290+ properties | Verificar nuevos componentes |
| 15 | Zero Region pattern | 95/100 | page--content-hub.html.twig modelo | Falta page--page-builder.html.twig |

**Score global actual: 72/100** | **Target: 100/100**

### 3.2 Confrontacion con Mejores Practicas SaaS

| Referencia SaaS Clase Mundial | Jaraba (actual) | Gap |
|------|------|-----|
| Canva: onboarding guiado "Crea tu primer diseno" | Solo link "Mis paginas" | Wizard + empty state |
| Webflow: upgrade trigger al publicar 2a pagina free | Sin trigger FreemiumVerticalLimit | PLG rules |
| Squarespace: daily tips "Optimiza tu pagina" | 0 daily actions PB | Daily actions |
| Wix: starter templates pre-populados | Sin starter pack | Template seeding |
| Notion: command palette para crear paginas | Command bar existe, bueno | Minimal gap |
| HubSpot: analytics de pagina embebidos | Sin data-track en flujo PB | Analytics |

---

## 4. Inventario Completo de Hallazgos

### 4.1 Hallazgos Criticos (P0)

| ID | Hallazgo | Modulo | Impacto | Sprint |
|----|----------|--------|---------|--------|
| H-01 | **Page Builder sin Setup Wizard steps** — viola SETUP-WIZARD-DAILY-001 | jaraba_page_builder | Onboarding inexistente; activacion baja | S1 |
| H-02 | **Page Builder sin Daily Actions** — viola SETUP-WIZARD-DAILY-001 | jaraba_page_builder | Sin engagement recurrente; retencion baja | S1 |
| H-03 | **0 FreemiumVerticalLimit para Page Builder** — PLG ciego | ecosistema_jaraba_core | Sin triggers conversion; revenue leak | S2 |
| H-04 | **Page Builder dashboard sin template Zero Region** — depende del layout admin | jaraba_page_builder | UX inconsistente con Content Hub | S1 |
| H-05 | **Canvas pre-save backup (SAFEGUARD-CANVAS-001 Layer 1)** parcialmente implementado | jaraba_page_builder | Riesgo perdida de datos en edicion | S4 |

### 4.2 Hallazgos Altos (P1)

| ID | Hallazgo | Modulo | Impacto | Sprint |
|----|----------|--------|---------|--------|
| H-06 | Content Hub sin avatar mapping en AvatarWizardBridgeService | ecosistema_jaraba_core | Wizard steps no resuelven para avatar "editor" | S3 |
| H-07 | Sin cross-link Page Builder ↔ Content Hub | ambos | Descubrimiento fragmentado | S3 |
| H-08 | Sin upgrade nudge modal cuando se alcanza quota | jaraba_page_builder | Conversion PLG silenciosa | S2 |
| H-09 | Sin starter pack de templates para nuevos tenants | jaraba_page_builder | Primera experiencia vacia | S5 |
| H-10 | PageBuilderDashboardController no consume SetupWizardRegistry | jaraba_page_builder | Pipeline E2E L1 roto | S1 |
| H-11 | Sin data-track-* en flujo Page Builder | jaraba_page_builder | Analytics de conversion ciego | S5 |
| H-12 | Plugin GrapesJS loading no aislado (1 fallo mata todos) | jaraba_page_builder | Riesgo de editor inutilizable | S4 |
| H-13 | Sin Daily Action "Mejorar plan" contextual a cuota PB | jaraba_billing | PLG passivo (no proactivo) | S2 |
| H-14 | RecentActivitySection solo muestra "Mis paginas" link, sin conteo ni status | ecosistema_jaraba_core | Informacion pobre en perfil | S3 |

### 4.3 Hallazgos Medios (P2)

| ID | Hallazgo | Modulo | Impacto | Sprint |
|----|----------|--------|---------|--------|
| H-15 | Sin validador de integridad wizard/daily para Page Builder | scripts/validation | No detecta regresiones | S4 |
| H-16 | Sin validador Pipeline E2E para Content + PB | scripts/validation | No verifica L1-L4 | S4 |
| H-17 | Sin validador FreemiumVerticalLimit coverage para PB | scripts/validation | PLG gaps no detectados | S4 |
| H-18 | Page Builder OnboardingController (tour) no activado en produccion | jaraba_page_builder | Tour interactivo muerto | S5 |
| H-19 | Sin empty state premium con preview de bloques | jaraba_page_builder | Plan free sin aspiracion visual | S5 |
| H-20 | Canvas integrity validator no verifica component tree | jaraba_page_builder | JSON valido pero componentes rotos | S4 |
| H-21 | Sin hook_theme_suggestions_page_alter para rutas /page-builder/* | jaraba_page_builder | Template suggestion no aplicada | S1 |
| H-22 | Sin hook_preprocess_html() con body classes page-builder | jaraba_page_builder | CSS targeting imposible | S1 |
| H-23 | Content Hub "editor_content_hub" wizard — contextId usa fallback user_id, no tenant_id | jaraba_content_hub | Wizard aislado por usuario, no por tenant | S3 |
| H-24 | Sin SCSS dedicado para Page Builder dashboard frontend | ecosistema_jaraba_theme | Sin estilos de onboarding | S1 |
| H-25 | TemplateRegistryService sin filtro explicito de bloques premium para plan free | jaraba_page_builder | Posible exposicion de previews premium | S2 |
| H-26 | Sin test de regresion para quota enforcement | tests/ | Regresiones silenciosas | S4 |

### 4.4 Hallazgos Bajos (P3)

| ID | Hallazgo | Modulo | Impacto | Sprint |
|----|----------|--------|---------|--------|
| H-27 | beneficiario_ei sin wizard/daily (posiblemente intencional) | jaraba_andalucia_ei | Rol pasivo, aceptable | N/A |
| H-28 | Page Builder revision diff no integrado en daily actions | jaraba_page_builder | Funcionalidad avanzada, no critica | S5 |
| H-29 | Sin hint "Usa el canvas" en ContentArticle legacy mode | jaraba_content_hub | Adopcion de canvas lenta | S5 |
| H-30 | Sin Schema.org WebPage para PageContent publicadas | jaraba_page_builder | SEO parcial | S5 |
| H-31 | GrapesJS spec doc referencia CDN (v0.21.13) pero codigo usa vendor local (v5.7) | docs/ | Doc desactualizado | S5 |

---

## 5. Arquitectura Actual — Mapa Completo

### 5.1 Dual-Track de Contenido

```
                    CREACION Y PUBLICACION DE CONTENIDO
                    ====================================

    ┌─────────────────────────┐    ┌─────────────────────────┐
    │   CONTENT HUB           │    │   PAGE BUILDER           │
    │   jaraba_content_hub    │    │   jaraba_page_builder    │
    ├─────────────────────────┤    ├─────────────────────────┤
    │ Entity: ContentArticle  │    │ Entity: PageContent      │
    │ Layout: legacy | canvas │    │ Layout: canvas only      │
    │ Workflow: 5 estados     │    │ Workflow: draft/published│
    │ SEO: completo           │    │ SEO: via plugin          │
    │ Blog: /blog/{slug}      │    │ Page: /page/{id}         │
    │ Dashboard: /content-hub │    │ Dashboard: /page-builder │
    │ Wizard: 3 steps [OK]    │    │ Wizard: 0 steps [GAP]   │
    │ Daily: 4 actions [OK]   │    │ Daily: 0 actions [GAP]  │
    │ Avatar: pendiente       │    │ Avatar: inexistente      │
    │ PLG: N/A (ilimitado)    │    │ PLG: 0 triggers [GAP]   │
    └────────────┬────────────┘    └────────────┬────────────┘
                 │                               │
                 └───────────┬───────────────────┘
                             │
                 ┌───────────▼───────────────────┐
                 │     GRAPESJS ENGINE            │
                 │     14 plugins, v5.7           │
                 │     Dual: editor + behaviors   │
                 │     40+ bloques, 8 devices     │
                 └───────────┬───────────────────┘
                             │
                 ┌───────────▼───────────────────┐
                 │     PERFIL DE USUARIO          │
                 │     11 secciones extensibles   │
                 │     PageBuilderSection (w:35)  │
                 │     RecentActivity (w:70)      │
                 │     AvatarWizardBridge         │
                 └───────────────────────────────┘
```

### 5.2 Page Builder — Gating por Plan

Cascada de resolucion de limites Page Builder:

```
1. SaasPlanFeatures ConfigEntity ({vertical}_{tier}) → max_pages, premium_blocks, ab_test_limit
2. _default_{tier} → Fallback si vertical no tiene config especifica
3. jaraba_page_builder.settings.yml → default_plans_limit (starter:5, pro:25, enterprise:-1)
4. QuotaManagerService::getPlanCapabilities() → Hardcoded ultimmo fallback

Limites por tier (SaasPlanFeatures _default_*):
┌──────────────┬──────────┬─────────────────┬───────────┬─────────┬───────────┐
│ Tier         │ max_pages│ basic_templates │ premium_t │ basic_b │ premium_b │
├──────────────┼──────────┼─────────────────┼───────────┼─────────┼───────────┤
│ Free         │ 5*       │ 10              │ 0         │ 15      │ 0         │
│ Starter      │ 5        │ 10              │ 0         │ 15      │ 0         │
│ Professional │ 25       │ 25              │ 8         │ 35      │ 10        │
│ Enterprise   │ -1 (∞)   │ 55              │ 22        │ 45      │ 22        │
└──────────────┴──────────┴─────────────────┴───────────┴─────────┴───────────┘
* Free hereda fallback de QuotaManagerService si no hay SaasPlanFeatures

GAP CRITICO: FreemiumVerticalLimit (217 reglas existentes, 0 para Page Builder)
- 0 reglas para max_pages
- 0 reglas para premium_blocks
- 0 reglas para page_builder_*
→ Sin triggers de conversion PLG cuando se alcanza limite
```

### 5.3 Content Hub — Funcionalidad Basica por Tenant

```
TODOS los planes incluyen:
  [OK] Articulos ilimitados en modo legacy (textarea)
  [OK] Categorias, tags, vertical
  [OK] Workflow editorial (draft→published)
  [OK] SEO basico (title, description, answer_capsule)
  [OK] Blog publico: /blog/{slug}
  [OK] Dashboard frontend: /content-hub (Zero Region)
  [OK] Command Bar integration (busqueda + acciones rapidas)
  [OK] Setup Wizard: 3 steps (autor → categoria → articulo)
  [OK] Daily Actions: 4 (nuevo articulo, borradores, comentarios, generar IA)

Plans Premium desbloquean:
  [OK] Canvas editor (GrapesJS visual) — permiso 'use article canvas editor'
  [OK] AI Writing Assistant — permiso 'use content ai assistant'
```

### 5.4 GrapesJS — Arquitectura de 14 Plugins

```
Plugin                          | Tamano | Funcion
grapesjs-jaraba-canvas.js      | 76KB   | Editor core, 8 device presets, style manager
grapesjs-jaraba-blocks.js      | 263KB  | 40+ bloques (hero, cta, pricing, faq, etc.)
grapesjs-jaraba-icons.js       | —      | Picker SVG (duotone/outline)
grapesjs-jaraba-ai.js          | 33KB   | Generacion contenido IA
grapesjs-jaraba-assets.js      | 29KB   | Media manager + Unsplash
grapesjs-jaraba-seo.js         | 33KB   | Panel SEO (meta, og:image, keywords)
grapesjs-jaraba-reviews.js     | 12KB   | Widget de resenas
grapesjs-jaraba-marketplace.js | 37KB   | Browser de templates
grapesjs-jaraba-multipage.js   | 38KB   | IDE-like tabs multi-pagina
grapesjs-jaraba-legal-blocks.js| 42KB   | Bloques compliance legal
grapesjs-jaraba-thumbnails.js  | —      | Preview generation
grapesjs-jaraba-command-palette| —      | Cmd+K search
grapesjs-jaraba-partials.js    | —      | Componentes reutilizables
grapesjs-jaraba-*-premium      | —      | Aceternity UI + Magic UI adapters

Dual Architecture:
- Editor: GrapesJS plugins (script functions inline)
- Frontend publicado: Drupal.behaviors (6 archivos .behavior.js)
  → stats-counter, pricing-toggle, tabs, countdown, timeline, navigation
- CERO dependencia GrapesJS en produccion
```

### 5.5 Perfil de Usuario — Hub de Acceso

```
/user/{uid} → UserProfileSectionRegistry (11 secciones)

Peso | Seccion                    | Content Creation?
 5   | SubscriptionProfileSection | No (plan info + upgrade CTA)
10   | ProfessionalProfileSection | No (completeness ring)
20   | MyVerticalSection          | Indirecto (nav avatar-specific)
20   | ProfessionalServicesSection| No (mentoring/workshops)
30   | MyBusinessSection          | No (tenant admin)
35   | PageBuilderProfileSection  | SI → "Mis paginas" link
40   | BillingProfileSection      | No (facturas, Kit Digital)
70   | RecentActivitySection      | SI → "Mis articulos" + "Mis paginas"
80   | SecurityProfileSection     | No
80   | AdministrationSection      | No (solo admin)
100  | AccountSection             | No (edit profile + logout)

GAP: PageBuilderProfileSection solo muestra un link basico.
     No muestra: conteo de paginas, % cuota usado, ultima edicion.
```

### 5.6 Onboarding — Setup Wizard + Daily Actions

**Estado actual por modulo:**

```
Modulo                  | Wizard Steps | Daily Actions | Avatar Mapped | Dashboard L1-L4
jaraba_candidate        | 5            | 4             | jobseeker     | OK
jaraba_business_tools   | 3            | 4             | entrepreneur  | OK
jaraba_copilot_v2       | 4            | 4             | —             | OK
jaraba_comercio_conecta | 5            | 5             | merchant      | OK
jaraba_agroconecta_core | 5            | 4             | producer      | OK
jaraba_servicios_conecta| 4            | 4             | profesional   | OK
jaraba_content_hub      | 3            | 4             | PENDIENTE     | OK
jaraba_legal_intel.     | 3            | 4             | legal_prof.   | OK
jaraba_lms              | 6            | 8             | student+inst. | OK
jaraba_mentoring        | 3            | 4             | mentor        | OK
jaraba_andalucia_ei     | 7            | 10            | coord+orient. | OK
ecosistema_jaraba_core  | 5 (demo+glob)| 4 (demo)      | —             | OK
jaraba_billing          | 2            | 3             | —             | OK
jaraba_page_builder     | 0 [GAP]      | 0 [GAP]       | INEXISTENTE   | NO [GAP]
────────────────────────┼──────────────┼───────────────┼───────────────┼──────
TOTAL                   | 55           | 62            | 11/12 avatar  | 13/14 modulos
```

### 5.7 Navegacion — Command Bar + Avatar Nav

```
Acceso a creacion de contenido desde:

1. Profile Hub → PageBuilderProfileSection (link "Mis paginas")
2. Profile Hub → RecentActivitySection (links "Mis articulos" + "Mis paginas")
3. Command Bar → "Content Hub" (jaraba_content_hub.dashboard.frontend)
4. Command Bar → "Create Article" (action)
5. Command Bar → Entity search (articulos + paginas)
6. Setup Wizard CTA → "Ir al dashboard" (avatar-specific)
7. Daily Actions → Acciones contextuales (crear, editar, revisar)

NOTA: No hay shortcut en bottom-nav ni sidebar-nav dedicados.
El Command Bar (Cmd+K) es el mecanismo principal de acceso rapido.
```

---

## 6. Sprint 1: Page Builder — Onboarding Clase Mundial (P0)

**Objetivo:** Implementar el patron SETUP-WIZARD-DAILY-001 completo para Page Builder, igualando la madurez de los demas verticales.

**Patron de referencia:** DemoController + DemoExplorarDashboardStep (Sprint 11 demo)

### 6.1 S1-01: Crear 4 SetupWizard Steps para Page Builder

**Archivos a crear:**

```
web/modules/custom/jaraba_page_builder/src/SetupWizard/
├── CrearPrimeraPaginaStep.php      (weight: 10)
├── ElegirPlantillaStep.php         (weight: 20)
├── PersonalizarContenidoStep.php   (weight: 30)
└── PublicarPaginaStep.php          (weight: 40)
```

**Logica de negocio de cada step:**

**CrearPrimeraPaginaStep** (`page_builder.crear_primera_pagina`):
- Wizard ID: `page_builder`
- `isComplete()`: Verifica si el tenant tiene al menos 1 PageContent entity (cualquier estado)
- `getIcon()`: `jaraba_icon('ui', 'layout-template', ['variant' => 'duotone'])`
- `getLabel()`: `'Crea tu primera pagina'`
- `getDescription()`: `'Empieza con una pagina en blanco o elige una plantilla profesional'`
- `getRoute()`: `'jaraba_page_builder.my_pages'`

**ElegirPlantillaStep** (`page_builder.elegir_plantilla`):
- `isComplete()`: Verifica si alguna PageContent del tenant tiene `canvas_data` no vacio (indicando uso de plantilla o edicion visual)
- `getIcon()`: `jaraba_icon('ui', 'palette', ['variant' => 'duotone'])`
- `getLabel()`: `'Elige una plantilla'`
- `getDescription()`: `'Mas de 40 bloques profesionales listos para arrastrar y soltar'`
- `getRoute()`: `'jaraba_page_builder.templates'`

**PersonalizarContenidoStep** (`page_builder.personalizar_contenido`):
- `isComplete()`: Verifica si alguna PageContent tiene `canvas_data` con al menos 3 componentes (indicando personalizacion real, no solo template vacio)
- `getIcon()`: `jaraba_icon('ui', 'sparkles', ['variant' => 'duotone'])`
- `getLabel()`: `'Personaliza tu contenido'`
- `getDescription()`: `'Anade tu logo, textos y colores de marca usando el editor visual'`
- `getRoute()`: Primera PageContent del tenant → ruta de edicion canvas

**PublicarPaginaStep** (`page_builder.publicar_pagina`):
- `isComplete()`: Verifica si al menos 1 PageContent del tenant tiene `status = TRUE` (publicada)
- `getIcon()`: `jaraba_icon('ui', 'rocket', ['variant' => 'duotone'])`
- `getLabel()`: `'Publica tu pagina'`
- `getDescription()`: `'Tu pagina estara disponible en tu dominio personalizado'`
- `getRoute()`: `'jaraba_page_builder.my_pages'`

**Patron de implementacion (PHP):**
- Cada step implementa `SetupWizardStepInterface`
- Constructor recibe `?EntityTypeManagerInterface` via DI
- `isComplete()` usa entity query con `accessCheck(TRUE)` + filtro `tenant_id`
- Scope: **tenant-scoped** (usa `TenantContextService::getCurrentTenantId()`)
- Importante: usar `@?ecosistema_jaraba_core.tenant_context` (OPTIONAL-CROSSMODULE-001)

**Registro en services.yml:**

```yaml
  jaraba_page_builder.setup_wizard.crear_primera_pagina:
    class: Drupal\jaraba_page_builder\SetupWizard\CrearPrimeraPaginaStep
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_page_builder.setup_wizard.elegir_plantilla:
    class: Drupal\jaraba_page_builder\SetupWizard\ElegirPlantillaStep
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_page_builder.setup_wizard.personalizar_contenido:
    class: Drupal\jaraba_page_builder\SetupWizard\PersonalizarContenidoStep
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_page_builder.setup_wizard.publicar_pagina:
    class: Drupal\jaraba_page_builder\SetupWizard\PublicarPaginaStep
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }
```

**Directrices de cumplimiento:**
- SETUP-WIZARD-DAILY-001: Tagged services con CompilerPass
- OPTIONAL-CROSSMODULE-001: `@?` para servicios cross-modulo
- PHANTOM-ARG-001: Arguments coinciden con constructor
- ICON-DUOTONE-001: Variante duotone por defecto
- ICON-COLOR-001: Colores de paleta Jaraba

### 6.2 S1-02: Crear 4 DailyActions para Page Builder

**Archivos a crear:**

```
web/modules/custom/jaraba_page_builder/src/DailyActions/
├── NuevaPaginaAction.php           (weight: 10, primary: true)
├── EditarPaginaPrincipalAction.php  (weight: 20)
├── ExplorarPlantillasAction.php     (weight: 30)
└── RendimientoPaginasAction.php     (weight: 40)
```

**Logica de cada action:**

**NuevaPaginaAction** (`page_builder.nueva_pagina`):
- Dashboard ID: `page_builder`
- `isPrimary()`: TRUE (accion principal resaltada)
- `getIcon()`: `jaraba_icon('ui', 'add-circle', ['variant' => 'duotone'])`
- `getLabel()`: `'Crear nueva pagina'`
- `getDescription()`: `'Crea una landing page profesional con el editor visual'`
- `getRoute()`: `'jaraba_page_builder.add'`
- Color: `naranja-impulso`
- Badge: count de paginas restantes vs limite (`"3 restantes"`)

**EditarPaginaPrincipalAction** (`page_builder.editar_principal`):
- `isPrimary()`: FALSE
- `getIcon()`: `jaraba_icon('ui', 'edit', ['variant' => 'duotone'])`
- `getLabel()`: `'Editar pagina principal'`
- `getDescription()`: `'Actualiza el contenido de tu pagina mas visitada'`
- `getRoute()`: Ruta de edicion de la PageContent mas reciente del tenant
- `isVisible()`: Solo si tenant tiene al menos 1 pagina
- Color: `azul-corporativo`

**ExplorarPlantillasAction** (`page_builder.explorar_plantillas`):
- `isPrimary()`: FALSE
- `getIcon()`: `jaraba_icon('ui', 'palette', ['variant' => 'duotone'])`
- `getLabel()`: `'Explorar plantillas'`
- `getDescription()`: `'Mas de 40 bloques profesionales listos para usar'`
- `getRoute()`: `'jaraba_page_builder.templates'`
- Color: `verde-innovacion`

**RendimientoPaginasAction** (`page_builder.rendimiento`):
- `isPrimary()`: FALSE
- `getIcon()`: `jaraba_icon('charts', 'bar-chart', ['variant' => 'duotone'])`
- `getLabel()`: `'Rendimiento de paginas'`
- `getDescription()`: `'Visitas, tiempo en pagina y conversiones'`
- `getRoute()`: `'jaraba_page_builder.analytics'` (o dashboard si analytics no existe)
- Badge: `badge_type: 'info'`, badge con count de paginas publicadas
- Color: `azul-corporativo`

**Registro en services.yml:**

```yaml
  jaraba_page_builder.daily_action.nueva_pagina:
    class: Drupal\jaraba_page_builder\DailyActions\NuevaPaginaAction
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
      - '@?jaraba_page_builder.quota_manager'
    tags:
      - { name: ecosistema_jaraba_core.daily_action }

  jaraba_page_builder.daily_action.editar_principal:
    class: Drupal\jaraba_page_builder\DailyActions\EditarPaginaPrincipalAction
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.daily_action }

  jaraba_page_builder.daily_action.explorar_plantillas:
    class: Drupal\jaraba_page_builder\DailyActions\ExplorarPlantillasAction
    tags:
      - { name: ecosistema_jaraba_core.daily_action }

  jaraba_page_builder.daily_action.rendimiento:
    class: Drupal\jaraba_page_builder\DailyActions\RendimientoPaginasAction
    arguments:
      - '@?entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.daily_action }
```

### 6.3 S1-03: Integrar Wizard + Daily Actions en PageBuilderDashboardController (L1-L4)

**Pipeline E2E completo:**

**L1 — Service Injection (constructor):**

Modificar `PageBuilderDashboardController` para inyectar los registries:

```php
public function __construct(
    // ... servicios existentes ...
    protected ?SetupWizardRegistry $wizardRegistry = NULL,
    protected ?DailyActionsRegistry $dailyActionsRegistry = NULL,
) {}

public static function create(ContainerInterface $container): static {
    return new static(
        // ... servicios existentes ...
        $container->has('ecosistema_jaraba_core.setup_wizard_registry')
            ? $container->get('ecosistema_jaraba_core.setup_wizard_registry') : NULL,
        $container->has('ecosistema_jaraba_core.daily_actions_registry')
            ? $container->get('ecosistema_jaraba_core.daily_actions_registry') : NULL,
    );
}
```

**L2 — Controller (render array):**

```php
public function dashboard(): array {
    $contextId = $this->resolveContextId(); // tenant-scoped

    $setupWizard = $this->wizardRegistry?->hasWizard('page_builder')
        ? $this->wizardRegistry->getStepsForWizard('page_builder', $contextId)
        : NULL;
    $dailyActions = $this->dailyActionsRegistry
        ?->getActionsForDashboard('page_builder', $contextId) ?? [];

    return [
        '#theme' => 'page_builder_dashboard',
        '#setup_wizard' => $setupWizard,
        '#daily_actions' => $dailyActions,
        // ... datos existentes del dashboard ...
    ];
}
```

**L3 — hook_theme() (variables):**

En `jaraba_page_builder.module`, verificar/anadir:

```php
function jaraba_page_builder_theme(): array {
    return [
        'page_builder_dashboard' => [
            'variables' => [
                'setup_wizard' => NULL,
                'daily_actions' => [],
                // ... variables existentes ...
            ],
            'template' => 'page-builder-dashboard',
        ],
    ];
}
```

**L4 — Template (partials):**

En `page-builder-dashboard.html.twig`:

```twig
{# Setup Wizard — SETUP-WIZARD-DAILY-001 #}
{% if setup_wizard %}
  {% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
    wizard: setup_wizard,
    wizard_title: 'Configura tu Page Builder'|t,
  } only %}
{% endif %}

{# Daily Actions #}
{% if daily_actions is not empty %}
  {% include '@ecosistema_jaraba_theme/partials/_daily-actions.html.twig' with {
    daily_actions: daily_actions,
    actions_title: 'Acciones rapidas'|t,
  } only %}
{% endif %}
```

**Directrices de cumplimiento:**
- PIPELINE-E2E-001: 4 capas verificadas
- TWIG-INCLUDE-ONLY-001: Keyword `only` en includes
- CONTROLLER-READONLY-001: No readonly en propiedades heredadas
- OPTIONAL-CROSSMODULE-001: `$container->has()` antes de `get()`
- ZEIGARNIK-PRELOAD-001: Global steps auto-inyectados por registry

### 6.4 S1-04: Crear page--page-builder.html.twig (Zero Region)

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--page-builder.html.twig`

**Patron:** Identico a `page--content-hub.html.twig` (Zero Region):

```twig
{# page--page-builder.html.twig — Zero Region Pattern #}
{# Pagina frontend limpia para el dashboard de Page Builder #}
{# Sin page.content, sin bloques heredados, sin sidebar admin #}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/page-builder-dashboard') }}

<a href="#main-content" class="skip-link">{% trans %}Saltar al contenido principal{% endtrans %}</a>

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  site_name: site_name,
  logo: logo,
  logged_in: logged_in,
  theme_settings: theme_settings,
} only %}

<main id="main-content" class="dashboard-main dashboard-main--page-builder">
  {% if clean_messages %}
    <div class="highlighted container">
      {{ clean_messages }}
    </div>
  {% endif %}

  <div class="dashboard-wrapper">
    {{ clean_content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
  site_name: site_name,
  logo: logo,
  theme_settings: theme_settings,
} only %}
```

**Hooks necesarios:**

En `jaraba_page_builder.module`:

```php
/**
 * Implements hook_theme_suggestions_page_alter().
 */
function jaraba_page_builder_theme_suggestions_page_alter(array &$suggestions, array $variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route_name, 'jaraba_page_builder.')
      && !str_contains($route_name, '.api.')
      && !str_contains($route_name, '.canvas_editor')) {
    $suggestions[] = 'page__page_builder';
  }
}

/**
 * Implements hook_preprocess_html().
 */
function jaraba_page_builder_preprocess_html(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route_name, 'jaraba_page_builder.')) {
    $variables['attributes']['class'][] = 'page-page-builder';
    $variables['attributes']['class'][] = 'page--clean-layout';
  }
}

/**
 * Implements hook_preprocess_page().
 */
function jaraba_page_builder_preprocess_page(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';
  if (str_starts_with($route_name, 'jaraba_page_builder.')) {
    if (isset($variables['page']['content'])) {
      $variables['clean_content'] = $variables['page']['content'];
    }
    $themeConfig = \Drupal::config('ecosistema_jaraba_theme.settings');
    $variables['theme_settings'] = $themeConfig->get() ?: [];
    $variables['site_name'] = \Drupal::config('system.site')->get('name');
    $variables['logged_in'] = \Drupal::currentUser()->isAuthenticated();
  }
}
```

**Directrices:**
- ZERO-REGION-001: `{{ clean_content }}` en vez de `{{ page.content }}`
- TWIG-INCLUDE-ONLY-001: `only` en todos los includes
- Body classes via `hook_preprocess_html()` (NUNCA `attributes.addClass()` en template)

### 6.5 S1-05: Registrar SCSS + Library para Page Builder Dashboard

**SCSS:** `scss/routes/page-builder-dashboard.scss`

```scss
@use '../variables' as *;
@use '../mixins' as *;

// Page Builder Dashboard — Zero Region frontend
// Patron: var(--ej-*, fallback) SIEMPRE (CSS-VAR-ALL-COLORS-001)

.dashboard-main--page-builder {
  min-height: 100vh;
  background: var(--ej-bg-page, #f8f9fa);
}

.dashboard-wrapper {
  max-width: var(--ej-container-xl, 1200px);
  margin: 0 auto;
  padding: var(--ej-spacing-lg, 2rem) var(--ej-spacing-md, 1rem);
}
```

**Library:** En `ecosistema_jaraba_theme.libraries.yml`:

```yaml
page-builder-dashboard:
  css:
    theme:
      css/routes/page-builder-dashboard.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global
```

**Compilacion:** `npm run build` desde ecosistema_jaraba_theme → SCSS-COMPILE-VERIFY-001

---

## 7. Sprint 2: PLG Page Builder — Triggers de Conversion (P0)

**Objetivo:** Crear los FreemiumVerticalLimit rules que permitan al sistema PLG detectar y actuar cuando un tenant alcanza limites de Page Builder.

### 7.1 S2-01: Crear 9 FreemiumVerticalLimit para max_pages (free)

**ConfigEntities a crear** (1 por vertical comercial):

```yaml
# config/install/ecosistema_jaraba_core.freemium_vertical_limit.{vertical}_free_max_pages.yml
id: '{vertical}_free_max_pages'
label: '{Vertical} Free: Paginas web'
vertical: '{vertical}'
plan: 'free'
feature_key: 'max_pages'
limit_value: 3
description: '3 paginas web en plan gratuito {Vertical}.'
upgrade_message: 'Has usado @current de @limit paginas. Mejora tu plan para crear paginas ilimitadas y desbloquear bloques premium.'
expected_conversion: 0.18
weight: 25
status: true
```

Verticales: empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta, formacion, andalucia_ei, jaraba_content_hub

**Logica de negocio:** Free = 3 paginas (no 5, para incentivar upgrade temprano). El `expected_conversion` de 0.18 (18%) es conservador — basado en benchmarks SaaS de conversion por feature lock.

### 7.2 S2-02: Crear 9 FreemiumVerticalLimit para max_pages (starter)

```yaml
id: '{vertical}_starter_max_pages'
label: '{Vertical} Starter: Paginas web'
vertical: '{vertical}'
plan: 'starter'
feature_key: 'max_pages'
limit_value: 5
upgrade_message: 'Tienes @current de @limit paginas. Con el plan Profesional tendras 25 paginas y bloques premium.'
expected_conversion: 0.12
weight: 26
```

### 7.3 S2-03: Crear 9 FreemiumVerticalLimit para premium_blocks

```yaml
id: '{vertical}_free_premium_blocks'
label: '{Vertical} Free: Bloques premium'
vertical: '{vertical}'
plan: 'free'
feature_key: 'premium_blocks'
limit_value: 0
upgrade_message: 'Los bloques premium incluyen efectos glassmorphism, 3D y animaciones. Disponibles desde el plan Profesional.'
expected_conversion: 0.22
weight: 27
```

### 7.4 S2-04: Upgrade nudge modal en QuotaManagerService

Modificar `QuotaManagerService::checkCanCreatePage()` para que, cuando `allowed = FALSE`, incluya datos para el frontend:

```php
return [
    'allowed' => FALSE,
    'message' => $this->t('Has alcanzado el limite de @count paginas de tu plan.', [
        '@count' => $limit,
    ]),
    'remaining' => 0,
    'upgrade' => [
        'show_modal' => TRUE,
        'current_plan' => $planId,
        'recommended_plan' => $this->getRecommendedUpgrade($planId),
        'route' => 'jaraba_billing.plan_upgrade',
        'features_unlocked' => ['25 paginas', 'Bloques premium', 'SEO avanzado', 'A/B testing'],
    ],
];
```

El frontend (JS) detecta `upgrade.show_modal === TRUE` y muestra un slide-panel con la oferta de upgrade. Patron: SLIDE-PANEL-RENDER-001.

### 7.5 S2-05: Daily Action "Mejorar tu plan" contextual a cuota PB

Crear en `jaraba_billing/src/DailyActions/UpgradePlanPageBuilderAction.php`:
- Solo visible si tenant esta al >80% de cuota de paginas
- Badge type: `warning`
- Route: `jaraba_billing.plan_upgrade`
- Tag: `ecosistema_jaraba_core.daily_action`
- Dashboard ID: `page_builder` (aparece en dashboard Page Builder cuando es relevante)

---

## 8. Sprint 3: Content Hub — Avatar Mapping + Completar Gaps (P1)

### 8.1 S3-01: Anadir avatar `editor_content_hub` en AvatarWizardBridgeService

Modificar `AVATAR_MAPPING` en `/web/modules/custom/ecosistema_jaraba_core/src/Service/AvatarWizardBridgeService.php`:

```php
'editor_content_hub' => [
    'wizard_id' => 'editor_content_hub',
    'dashboard_id' => 'editor_content_hub',
    'dashboard_route' => 'jaraba_content_hub.dashboard.frontend',
    'scope' => 'tenant',   // Contenido pertenece al tenant
    'label' => 'Editor de contenidos',
],
```

**Impacto:** Cuando un usuario tiene avatar "editor_content_hub" (asignable desde AvatarDetectionService o JourneyState), el sistema resolvera wizard + daily actions automaticamente en el perfil de usuario.

### 8.2 S3-02: Integrar wizard + daily_actions en page--user.html.twig para editor

El preprocess hook de `page--user.html.twig` ya resuelve via `AvatarWizardBridgeService::resolveForCurrentUser()`. Con S3-01, esto funcionara automaticamente para el avatar "editor_content_hub".

**Verificacion L1-L4:**
- L1: `ContentHubDashboardController` ya inyecta registries ✓
- L2: Ya pasa `#setup_wizard` y `#daily_actions` ✓
- L3: `hook_theme()` ya declara variables ✓
- L4: Template ya incluye partials con `only` ✓

### 8.3 S3-03: Daily Action "Crear pagina" desde Content Hub dashboard

Crear `jaraba_content_hub/src/DailyActions/CrearPaginaAction.php`:
- Dashboard ID: `editor_content_hub` (aparece en Content Hub)
- Solo visible si `jaraba_page_builder` esta instalado (resolveRoute guard)
- `getLabel()`: `'Crear pagina web'`
- `getDescription()`: `'Abre el Page Builder para crear una landing page profesional'`
- Route: `jaraba_page_builder.add`
- Weight: 50 (despues de las 4 acciones principales)
- OPTIONAL-CROSSMODULE-001: `@?jaraba_page_builder.quota_manager`

### 8.4 S3-04: Cross-link entre Content Hub y Page Builder

**En Content Hub dashboard template**, anadir seccion:

```twig
{% if page_builder_available %}
  <section class="dashboard-panel dashboard-panel--crosslink">
    <h3>{% trans %}Paginas web{% endtrans %}</h3>
    <p>{% trans %}Crea landing pages profesionales con el editor visual drag-and-drop.{% endtrans %}</p>
    <a href="{{ path('jaraba_page_builder.dashboard') }}" class="btn btn--outline">
      {% trans %}Ir al Page Builder{% endtrans %}
    </a>
  </section>
{% endif %}
```

**En Page Builder dashboard template**, anadir seccion reciproca:

```twig
{% if content_hub_available %}
  <section class="dashboard-panel dashboard-panel--crosslink">
    <h3>{% trans %}Blog y articulos{% endtrans %}</h3>
    <p>{% trans %}Publica contenido editorial optimizado para SEO con el Content Hub.{% endtrans %}</p>
    <a href="{{ path('jaraba_content_hub.dashboard.frontend') }}" class="btn btn--outline">
      {% trans %}Ir al Content Hub{% endtrans %}
    </a>
  </section>
{% endif %}
```

Variables inyectadas desde preprocess con `resolveRoute()` guard (graceful degradation si modulo no instalado).

---

## 9. Sprint 4: Salvaguardas y Validadores (P0)

### 9.1 S4-01: validate-page-builder-onboarding.php

**Ubicacion:** `scripts/validation/validate-page-builder-onboarding.php`

**Checks:**
1. jaraba_page_builder/src/SetupWizard/ contiene al menos 3 clases
2. Cada clase implementa SetupWizardStepInterface
3. jaraba_page_builder/src/DailyActions/ contiene al menos 3 clases
4. Cada clase implementa DailyActionInterface
5. services.yml tiene tags `ecosistema_jaraba_core.setup_wizard_step` para cada step
6. services.yml tiene tags `ecosistema_jaraba_core.daily_action` para cada action
7. hook_theme() declara variables `setup_wizard` y `daily_actions`
8. Template incluye `_setup-wizard.html.twig` y `_daily-actions.html.twig`
9. Controller consume SetupWizardRegistry y DailyActionsRegistry

**Output:** PASS/FAIL con detalle por check. Integrar en `validate-all.sh --checklist`.

### 9.2 S4-02: validate-content-pipeline-e2e.php

**Checks para AMBOS modulos (Content Hub + Page Builder):**
1. L1: Service injection en controller (SetupWizardRegistry, DailyActionsRegistry)
2. L2: Render array incluye `#setup_wizard` y `#daily_actions`
3. L3: hook_theme() declara ambas variables
4. L4: Template usa `{% include ... only %}`
5. Template Zero Region: `{{ clean_content }}`, no `{{ page.content }}`
6. body classes via `hook_preprocess_html()`
7. SCSS compilado existe y timestamp > SCSS source

### 9.3 S4-03: validate-plg-triggers.php

**Checks:**
1. FreemiumVerticalLimit tiene reglas para `max_pages` en al menos 9 verticales
2. FreemiumVerticalLimit tiene reglas para `premium_blocks` en al menos 9 verticales
3. Cada regla tiene `upgrade_message` no vacio
4. Cada regla tiene `expected_conversion` > 0
5. QuotaManagerService::checkCanCreatePage() retorna `upgrade` data cuando `allowed = FALSE`

### 9.4 S4-04: Canvas pre-save backup service (SAFEGUARD-CANVAS-001 Layer 1)

Crear `CanvasBackupService` en jaraba_page_builder:

```php
class CanvasBackupService {
    /**
     * Almacena snapshot del canvas_data ANTES de cada save.
     * Usa State API (key_value) con key: 'canvas_backup:{entity_id}:{timestamp}'.
     * Retiene ultimos 5 backups por entity.
     */
    public function createBackup(PageContentInterface $page): void;
    public function getBackups(int $entityId, int $limit = 5): array;
    public function restoreBackup(int $entityId, string $timestamp): ?string;
}
```

**Integracion:** Hook `jaraba_page_builder_page_content_presave()` llama a `CanvasBackupService::createBackup()` antes del save.

### 9.5 S4-05: Canvas integrity validator

Crear metodo `validateCanvasIntegrity()` en `CanvasApiController`:

```php
private function validateCanvasIntegrity(array $data): array {
    $errors = [];
    // 1. components debe ser array
    if (!is_array($data['components'] ?? NULL)) {
        $errors[] = 'components must be an array';
    }
    // 2. styles debe ser array
    if (!is_array($data['styles'] ?? NULL)) {
        $errors[] = 'styles must be an array';
    }
    // 3. html debe ser string no vacio
    if (empty($data['html']) || !is_string($data['html'])) {
        $errors[] = 'html must be a non-empty string';
    }
    // 4. css debe ser string (puede ser vacio)
    if (isset($data['css']) && !is_string($data['css'])) {
        $errors[] = 'css must be a string';
    }
    // 5. components no debe tener refs a IDs inexistentes
    // 6. Profundidad maxima de anidamiento: 20 niveles
    return $errors;
}
```

---

## 10. Sprint 5: Conversion 10/10 — CTA + Micro-interacciones (P1)

### 10.1 S5-01: Upgrade CTA contextual en PageBuilderDashboardController

Inyectar informacion de cuota en el dashboard:

```php
$quotaInfo = $this->quotaManager?->getQuotaInfo() ?? [];
$variables['quota'] = [
    'current' => $quotaInfo['current'] ?? 0,
    'limit' => $quotaInfo['limit'] ?? 5,
    'percentage' => $quotaInfo['percentage'] ?? 0,
    'plan' => $quotaInfo['plan_label'] ?? 'Free',
    'can_upgrade' => $quotaInfo['can_upgrade'] ?? FALSE,
];
```

Template muestra barra de progreso con CTA:

```twig
{% if quota.can_upgrade %}
  <div class="quota-indicator" style="--quota-pct: {{ quota.percentage }}%">
    <div class="quota-indicator__bar"></div>
    <span class="quota-indicator__text">
      {% trans %}{{ quota.current }} de {{ quota.limit }} paginas usadas{% endtrans %}
    </span>
    {% if quota.percentage > 80 %}
      <a href="{{ path('jaraba_billing.plan_upgrade') }}" class="btn btn--accent btn--sm"
         data-track-cta="page_builder_upgrade" data-track-position="dashboard_quota">
        {% trans %}Mejorar plan{% endtrans %}
      </a>
    {% endif %}
  </div>
{% endif %}
```

### 10.2 S5-02: Empty state premium con preview de bloques premium

Cuando un tenant free/starter visita el marketplace de bloques premium:

```twig
<div class="premium-blocks-preview">
  <div class="premium-blocks-preview__grid">
    {# Mostrar 3 bloques premium con efecto blur/lock #}
    {% for block in premium_blocks_preview %}
      <div class="premium-blocks-preview__card premium-blocks-preview__card--locked">
        <img src="{{ block.preview_image }}" alt="{{ block.label }}"
             loading="lazy" class="premium-blocks-preview__img">
        <div class="premium-blocks-preview__overlay">
          {{ jaraba_icon('ui', 'lock', { variant: 'duotone', color: 'naranja-impulso' }) }}
          <span>{% trans %}Plan Profesional{% endtrans %}</span>
        </div>
      </div>
    {% endfor %}
  </div>
  <a href="{{ path('jaraba_billing.plan_upgrade') }}" class="btn btn--primary"
     data-track-cta="premium_blocks_upgrade" data-track-position="marketplace">
    {% trans %}Desbloquear bloques premium{% endtrans %}
  </a>
</div>
```

### 10.3 S5-03: Onboarding tour interactivo

El `OnboardingController` ya existe en `/api/v1/page-builder/onboarding` con `OnboardingStateService`. Activar el tour con 5 pasos:

1. "Este es tu editor visual" → resaltar canvas
2. "Arrastra bloques desde aqui" → resaltar panel lateral
3. "Personaliza colores y tipografia" → resaltar style manager
4. "Vista previa en movil" → resaltar device toggle
5. "Publica cuando estes listo" → resaltar boton publicar

### 10.4 S5-04: Analytics de uso Page Builder via data-track-*

Todos los CTAs de Page Builder deben incluir:

```html
data-track-cta="{action}" data-track-position="{location}"
```

Acciones a rastrear:
- `page_builder_create` (crear pagina)
- `page_builder_edit` (editar)
- `page_builder_publish` (publicar)
- `page_builder_template_use` (usar plantilla)
- `page_builder_premium_block_attempt` (intentar usar bloque premium)
- `page_builder_upgrade` (clic en upgrade)

Validacion: `php scripts/validation/validate-funnel-tracking.php` (FUNNEL-COMPLETENESS-001)

### 10.5 S5-05: Template starter pack

Para nuevos tenants, crear automaticamente 3 paginas draft con plantillas pre-configuradas:

1. "Pagina de inicio" — Hero + features + testimonials + CTA
2. "Sobre nosotros" — About section + team + contact
3. "Servicios/Productos" — Grid de cards + pricing + FAQ

Implementar en `jaraba_page_builder_group_insert()` (hook al crear tenant/group) o via DailyAction "Usar starter pack".

---

## 11. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Referencia | Items del Plan | Estado |
|---------------|-----------|----------------|--------|
| SETUP-WIZARD-DAILY-001 | CLAUDE.md §Patterns | S1-01, S1-02, S1-03, S1-05 | A implementar |
| PIPELINE-E2E-001 | CLAUDE.md §Implementation Checklist | S1-03 (L1-L4), S4-02 | A implementar |
| ZEIGARNIK-PRELOAD-001 | CLAUDE.md §Patterns | S1-01 (auto via registry) | Automatico |
| ZERO-REGION-001 | CLAUDE.md §Frontend | S1-04 | A implementar |
| PREMIUM-FORMS-PATTERN-001 | CLAUDE.md §Entity Forms | Existente en PageContentForm | Verificar |
| TENANT-BRIDGE-001 | CLAUDE.md §Multi-Tenant | QuotaManagerService existente | OK |
| TENANT-ISOLATION-ACCESS-001 | CLAUDE.md §Multi-Tenant | PageContentAccessControlHandler | OK |
| OPTIONAL-CROSSMODULE-001 | CLAUDE.md §Modulos | S1-01, S1-02 (services.yml @?) | A implementar |
| PHANTOM-ARG-001 | CLAUDE.md §Modulos | S1-01, S1-02 (args = constructor) | A verificar |
| ICON-DUOTONE-001 | CLAUDE.md §Iconos | S1-01, S1-02 (iconos duotone) | A implementar |
| ICON-COLOR-001 | CLAUDE.md §Iconos | S1-02 (colores paleta Jaraba) | A implementar |
| CSS-VAR-ALL-COLORS-001 | CLAUDE.md §CSS | S1-05, S5-01, S5-02 | A implementar |
| SCSS-COMPILE-VERIFY-001 | CLAUDE.md §SCSS | S1-05 (compilar + verificar) | A ejecutar |
| TWIG-INCLUDE-ONLY-001 | CLAUDE.md §Twig | S1-03, S1-04, S3-04 | A implementar |
| ROUTE-LANGPREFIX-001 | CLAUDE.md §JS | S3-04, S5-04 (URLs via path()) | A implementar |
| NO-HARDCODE-PRICE-001 | CLAUDE.md §Precios | S2-04, S5-01 (desde MetaSitePricingService) | A verificar |
| SLIDE-PANEL-RENDER-001 | CLAUDE.md §Modales | S2-04 (upgrade modal) | A implementar |
| FUNNEL-COMPLETENESS-001 | CLAUDE.md §Quiz | S5-04 (data-track en todos CTAs) | A implementar |
| SAFEGUARD-CANVAS-001 | CLAUDE.md §GrapesJS | S4-04, S4-05 | A implementar |
| RUNTIME-VERIFY-001 | CLAUDE.md §Runtime | Todos los sprints (verificar post-impl) | Continuo |
| IMPLEMENTATION-CHECKLIST-001 | CLAUDE.md §Checklist | S4-01, S4-02, S4-03 | A implementar |
| DOC-GUARD-001 | CLAUDE.md §Documentacion | Este documento | OK |
| CONTROLLER-READONLY-001 | CLAUDE.md §PHP | S1-03 (controller DI) | A verificar |
| UPDATE-HOOK-REQUIRED-001 | CLAUDE.md §DB | N/A (no hay nuevas entities) | N/A |
| INNERHTML-XSS-001 | CLAUDE.md §JS | S5-03 (tour tooltips) | A verificar |
| CSRF-JS-CACHE-001 | CLAUDE.md §JS | S5-04 (analytics fetch) | A verificar |
| AUDIT-SEC-002 | CLAUDE.md §Seguridad | Rutas PB con _permission | OK |
| TWIG-URL-RENDER-ARRAY-001 | CLAUDE.md §Twig | S3-04 (usar path(), no url()) | A implementar |

---

## 12. Tabla de Cumplimiento con Directrices del Proyecto

| Directriz | Requisito | Como se cumple | Sprint |
|-----------|-----------|----------------|--------|
| Textos siempre traducibles | `{% trans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS | Todos los textos nuevos usan traduccion | Todos |
| Modelo SASS con SCSS compilados | Variables via `@use '../variables' as *`, compilar con `npm run build` | Nuevo SCSS en `scss/routes/page-builder-dashboard.scss` | S1 |
| Variables CSS inyectables desde UI | `var(--ej-*, fallback)` — valores desde TenantThemeConfig | Todos los colores y espaciados usan tokens | S1, S5 |
| Dart Sass moderno | `@use` (no @import), `color-mix()` (no rgba) | Todos los nuevos SCSS | S1 |
| Templates Twig limpias (Zero Region) | `{{ clean_content }}`, no `{{ page.content }}`, sin bloques | `page--page-builder.html.twig` | S1 |
| Parciales Twig reutilizables | `{% include '@theme/partials/_*.html.twig' with {...} only %}` | Reusar `_setup-wizard`, `_daily-actions`, `_header`, `_footer` | S1 |
| Config de tema desde UI Drupal | Footer, header, colores, CTA configurables sin codigo | Variables inyectadas via preprocess desde theme settings | S1 |
| Layout full-width, mobile-first | `max-width: var(--ej-container-xl)`, responsive breakpoints | SCSS con `@include respond-to()` | S1, S5 |
| Acciones en slide-panel/modal | `data-slide-panel` en links de crear/editar/ver | Links a forms de PB usan slide-panel | S1, S2 |
| Body classes via hook_preprocess_html() | NUNCA `attributes.addClass()` en template | `jaraba_page_builder_preprocess_html()` | S1 |
| Tenant sin acceso a admin theme | `_admin_route: FALSE` en routing.yml | Rutas /page-builder/* son frontend | S1 |
| Field UI + Views integration | `views_data` en entity, `field_ui_base_route` | PageContent ya tiene ambos | OK |
| Admin navigation | Config en /admin/structure, contenido en /admin/content | PageContent en /admin/content/pages | OK |
| Icons: `jaraba_icon()` con duotone | Variante duotone por defecto, colores de paleta | Todos los wizard steps y daily actions | S1, S2 |
| Precios desde MetaSitePricingService | NUNCA hardcodear EUR en templates | Upgrade CTAs usan `ped_pricing` variable | S2, S5 |
| SCSS compilado: timestamp > SCSS | SCSS-COMPILE-VERIFY-001: verificar tras cada edicion | `npm run build` + validacion post-build | S1 |
| declare(strict_types=1) | En todos los archivos PHP nuevos | Todas las clases nuevas | Todos |
| PHPStan Level 6 | Sin errores nuevos introducidos | Verificar con `phpstan analyse` | Todos |
| Tags de servicio correctos | `ecosistema_jaraba_core.setup_wizard_step`, `.daily_action` | services.yml de jaraba_page_builder | S1 |
| No dependencias circulares | CONTAINER-DEPS-002 | Verificar con `validate-circular-deps.php` | S1 |

---

## 13. Estructura de Archivos

### Archivos a Crear (Nuevos)

```
web/modules/custom/jaraba_page_builder/
├── src/SetupWizard/
│   ├── CrearPrimeraPaginaStep.php
│   ├── ElegirPlantillaStep.php
│   ├── PersonalizarContenidoStep.php
│   └── PublicarPaginaStep.php
├── src/DailyActions/
│   ├── NuevaPaginaAction.php
│   ├── EditarPaginaPrincipalAction.php
│   ├── ExplorarPlantillasAction.php
│   └── RendimientoPaginasAction.php
└── src/Service/
    └── CanvasBackupService.php

web/modules/custom/jaraba_billing/src/DailyActions/
└── UpgradePlanPageBuilderAction.php

web/modules/custom/jaraba_content_hub/src/DailyActions/
└── CrearPaginaAction.php

web/themes/custom/ecosistema_jaraba_theme/
├── templates/page--page-builder.html.twig
├── scss/routes/page-builder-dashboard.scss
└── css/routes/page-builder-dashboard.css  (compilado)

config/install/ecosistema_jaraba_core.freemium_vertical_limit.*.yml  (27 archivos)

scripts/validation/
├── validate-page-builder-onboarding.php
├── validate-content-pipeline-e2e.php
└── validate-plg-triggers.php
```

### Archivos a Modificar (Existentes)

```
web/modules/custom/jaraba_page_builder/
├── jaraba_page_builder.services.yml     (anadir 8 tagged services)
├── jaraba_page_builder.module           (anadir 3 hooks: theme_suggestions, preprocess_html, preprocess_page)
├── src/Controller/PageBuilderDashboardController.php  (inyectar registries, L1-L2)
├── src/Controller/CanvasApiController.php             (canvas integrity validator)
└── src/Service/QuotaManagerService.php                (upgrade data en response)

web/modules/custom/jaraba_content_hub/
├── jaraba_content_hub.services.yml      (anadir 1 daily action)
└── templates/content-hub-dashboard-frontend.html.twig (cross-link)

web/modules/custom/ecosistema_jaraba_core/
├── src/Service/AvatarWizardBridgeService.php (anadir editor_content_hub)
└── src/UserProfile/Section/RecentActivitySection.php (enriquecer con conteo)

web/themes/custom/ecosistema_jaraba_theme/
├── ecosistema_jaraba_theme.libraries.yml (anadir library page-builder-dashboard)
└── scss/main.scss                        (anadir @use routes/page-builder-dashboard si aplica)

scripts/validation/validate-all.sh (anadir 3 nuevos validadores)
```

---

## 14. Verificacion y Testing

### 14.1 Tests a Crear

| Test | Tipo | Suite | Prioridad |
|------|------|-------|-----------|
| `CrearPrimeraPaginaStepTest` | Unit | Unit | P0 |
| `PublicarPaginaStepTest` | Unit | Unit | P0 |
| `NuevaPaginaActionTest` | Unit | Unit | P0 |
| `QuotaManagerUpgradeDataTest` | Unit | Unit | P0 |
| `CanvasBackupServiceTest` | Kernel | Kernel | P1 |
| `PageBuilderOnboardingIntegrationTest` | Kernel | Kernel | P1 |
| `FreemiumVerticalLimitPageBuilderTest` | Kernel | Kernel | P1 |

### 14.2 RUNTIME-VERIFY-001 Checklist

Tras cada sprint, verificar las 5 dependencias runtime:

1. **CSS compilado**: `ls -la css/routes/page-builder-dashboard.css` → timestamp > SCSS
2. **Rutas accesibles**: Visitar `/page-builder` → template Zero Region aplicado
3. **data-* selectores**: `data-slide-panel` en links → slide-panel abre correctamente
4. **drupalSettings**: Verificar que quota info llega al JS
5. **DOM final**: Setup wizard y daily actions visibles en dashboard

### 14.3 Validacion Automatizada

```bash
# Tras Sprint 1
lando php scripts/validation/validate-page-builder-onboarding.php
lando php scripts/validation/validate-content-pipeline-e2e.php

# Tras Sprint 2
lando php scripts/validation/validate-plg-triggers.php

# Integral
lando bash scripts/validation/validate-all.sh --checklist web/modules/custom/jaraba_page_builder
```

---

## 15. Salvaguardas Futuras Recomendadas

### 15.1 Nuevas Salvaguardas Propuestas

| ID | Nombre | Tipo | Descripcion |
|----|--------|------|-------------|
| SAFEGUARD-PB-ONBOARDING-001 | Page Builder Onboarding Integrity | Script validacion | Verifica wizard steps, daily actions, L1-L4 pipeline |
| SAFEGUARD-PLG-COVERAGE-001 | PLG Trigger Coverage | Script validacion | Verifica FreemiumVerticalLimit para features clave |
| SAFEGUARD-CONTENT-E2E-001 | Content Pipeline E2E | Script validacion | Verifica ambos modulos de contenido end-to-end |
| SAFEGUARD-CANVAS-BACKUP-001 | Canvas Pre-Save Backup | Runtime | State API backup antes de cada save |
| SAFEGUARD-CANVAS-INTEGRITY-001 | Canvas Data Integrity | Runtime | Validacion estructura JSON del canvas |
| SAFEGUARD-UPGRADE-NUDGE-001 | Upgrade Nudge Consistency | Script validacion | Verifica que TODOS los limit-check retornan upgrade data |
| SAFEGUARD-CROSS-CONTENT-001 | Cross-Content Discovery | Script validacion | Verifica cross-links PB↔CH en ambas direcciones |

### 15.2 Pre-commit Hook Ampliacion

Anadir en lint-staged:

```
web/modules/custom/jaraba_page_builder/**/*.services.yml:
  - validate-phantom-args.php
  - validate-optional-deps.php
  - validate-page-builder-onboarding.php
```

### 15.3 CI Pipeline Gate

Anadir en `fitness-functions.yml`:

```yaml
- name: "PB Onboarding Integrity"
  command: "php scripts/validation/validate-page-builder-onboarding.php"

- name: "PLG Trigger Coverage"
  command: "php scripts/validation/validate-plg-triggers.php"

- name: "Content Pipeline E2E"
  command: "php scripts/validation/validate-content-pipeline-e2e.php"
```

---

## 16. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| SetupWizardRegistry no resuelve `page_builder` wizard_id | Baja | Alto | Unit test + integration test |
| FreemiumVerticalLimit config no cargada en produccion | Media | Alto | hook_update_N() + drush config:import |
| Canvas backup llena State API | Baja | Medio | Retener solo ultimos 5 backups, cron limpieza |
| Template Zero Region no aplicado por cache | Media | Medio | Flush cache tras deploy, test con ?cache=0 |
| Plugin GrapesJS failure cascading | Baja | Alto | S4-05: try-catch wrapper por plugin |
| Cross-link CH→PB muestra ruta inexistente | Baja | Bajo | resolveRoute() guard con fallback NULL |

---

## 17. Registro de Cambios

| Version | Fecha | Cambios |
|---------|-------|---------|
| 1.0.0 | 2026-03-19 | Documento inicial: auditoria completa, 31 hallazgos, 5 sprints, 24 items |

---

**Fin del documento.**

**Score objetivo tras implementacion completa: 100/100 en todas las dimensiones.**

| Dimension | Antes | Despues |
|-----------|-------|---------|
| Arquitectura multi-tenant | 95 | 100 |
| Gating por plan | 85 | 100 |
| Content Hub onboarding | 90 | 100 |
| Page Builder onboarding | 30 | 100 |
| PLG Page Builder | 20 | 100 |
| Canvas safeguards | 75 | 100 |
| Conversion 10/10 | 60 | 100 |
| Navegacion descubrimiento | 80 | 100 |
| Analytics conversion | 40 | 100 |
| Salvaguardas (scripts) | 70 | 100 |
| i18n textos interfaz | 95 | 100 |
| SCSS/CSS tokens | 95 | 100 |
| Zero Region pattern | 95 | 100 |
| Profile hub integracion | 85 | 100 |
| **GLOBAL** | **72** | **100** |
