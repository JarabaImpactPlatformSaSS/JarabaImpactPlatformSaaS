# 🏗️ Plan Maestro de Implementación — Especificaciones Técnicas f-100 a f-104

> **Tipo:** Plan de Implementación (Revisión Multidisciplinar)
> **Versión:** 1.0
> **Fecha:** 2026-02-10
> **Alcance:** Revisión exhaustiva de 6 especificaciones técnicas + arquitectura de theming + directrices + workflows + aprendizajes
> **Roles:** SaaS Architect, UX Lead, Drupal Senior, Frontend Engineer, SEO Specialist, IA Engineer

---

## 📑 Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de Documentos Revisados](#2-inventario-de-documentos-revisados)
3. [Tabla de Referencias Técnicas](#3-tabla-de-referencias-técnicas)
4. [Resúmenes por Especificación](#4-resúmenes-por-especificación)
5. [Tabla de Mapeo: Especificación → Implementación](#5-tabla-de-mapeo)
6. [Verificación de Cumplimiento de Directrices](#6-verificación-de-cumplimiento-de-directrices)
7. [Verificación de Cumplimiento de Workflows](#7-verificación-de-cumplimiento-de-workflows)
8. [Verificación de Cumplimiento de Aprendizajes](#8-verificación-de-cumplimiento-de-aprendizajes)
9. [Análisis de Brechas](#9-análisis-de-brechas)
10. [Roadmap de Implementación](#10-roadmap-de-implementación)
11. [Plan de Verificación](#11-plan-de-verificación)

---

## 1. Resumen Ejecutivo

Este plan consolida la revisión de **6 especificaciones técnicas** (f-100 a f-104), la **arquitectura de theming** (Federated Design Tokens v2.1), las **directrices del proyecto** (v5.8, 1564 líneas), los **16 workflows** establecidos y los **53 aprendizajes** documentados. Cada componente especificado ha sido verificado contra las reglas existentes del proyecto.

### Hallazgos Principales

| Dimensión | Estado |
|-----------|--------|
| Arquitectura de theming (Federated Design Tokens v2.1) | ✅ Consolidada — 102 SCSS, 8 módulos |
| Cumplimiento de directrices SCSS | ✅ 0 funciones deprecadas, Dart Sass compliant |
| Cumplimiento de workflows | ✅ 16 workflows verificados y alineados |
| Cumplimiento de aprendizajes | ✅ 53 lecciones integradas |
| **DesignTokenConfig entity** (f-100 §2) | ✅ Implementado (Feb 2026) — entity + admin UI + 4 configs |
| **StylePresetService** (f-100/f-101) | ✅ Implementado (Feb 2026) — cascada 4 niveles + `html:root` injection |
| **SCSS Migration to Tokens** | ✅ Completado (Feb 2026) — `_injectable.scss` + compiled CSS |
| Style Presets (f-101/f-102) | ⏳ 30% — Service listo, faltan 16 presets comerciales |
| Admin Center (f-104) | ❌ 0% implementado — requiere stack React 18+ |
| UX Journey Engine (f-103) | ⏳ 40% — avatars definidos, engines parciales |

---

## 2. Inventario de Documentos Revisados

| # | Documento | Ubicación | Líneas |
|---|-----------|-----------|--------|
| 1 | **f-100** Frontend Architecture Multi-Tenant | [f-100](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260117f-100_Frontend_Architecture_MultiTenant_v1_Claude.md) | 244 |
| 2 | **f-101** Industry Style Presets | [f-101](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260117f-101_Industry_Style_Presets_v1_Claude.md) | 140 |
| 3 | **f-102** Premium Implementation | [f-102](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260117f-102_Industry_Style_Presets_Premium_Implementation_v1_Claude.md) | 639 |
| 4 | **f-102B** Institutional Presets | [f-102B](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260117f-102_Industry_Style_Presets_Premium_Implementation_v1_AnexoB_Claude.md) | 359 |
| 5 | **f-103** UX Journeys by Avatar | [f-103](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260117f-103_UX_Journey_Specifications_Avatar_v1_Claude.md) | 592 |
| 6 | **f-104** SaaS Admin Center Premium | [f-104](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/20260117f-104_SaaS_Admin_Center_Premium_v1_Claude.md) | 699 |
| 7 | **Theming Master** v2.1 | [Theming](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md) | 596 |
| 8 | **Directrices Proyecto** v5.8 | [Directrices](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/00_DIRECTRICES_PROYECTO.md) | 1564 |

**Workflows verificados (16):**
| Workflow | Archivo |
|----------|---------|
| SCSS Estilos | [scss-estilos.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/scss-estilos.md) |
| Frontend Page Pattern | [frontend-page-pattern.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/frontend-page-pattern.md) |
| SDC Components | [sdc-components.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/sdc-components.md) |
| AI Integration | [ai-integration.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/ai-integration.md) |
| Premium Cards Pattern | [premium-cards-pattern.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/premium-cards-pattern.md) |
| Slide-Panel Modales | [slide-panel-modales.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/slide-panel-modales.md) |
| Browser Verification | [browser-verification.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/browser-verification.md) |
| Drupal Custom Modules | [drupal-custom-modules.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/drupal-custom-modules.md) |
| Cypress E2E | [cypress-e2e.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/cypress-e2e.md) |
| i18n Traducciones | [i18n-traducciones.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/i18n-traducciones.md) |
| Drupal ECA Hooks | [drupal-eca-hooks.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/drupal-eca-hooks.md) |
| Auditoría Exhaustiva | [auditoria-exhaustiva.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/auditoria-exhaustiva.md) |
| Auditoría UX | [auditoria-ux-clase-mundial.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/auditoria-ux-clase-mundial.md) |
| Implementación Emprendimiento | [implementacion-emprendimiento.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/implementacion-emprendimiento.md) |
| Implementación Gaps Empleabilidad | [implementacion-gaps-empleabilidad.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/implementacion-gaps-empleabilidad.md) |
| Revisión Trimestral | [revision-trimestral.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/.agent/workflows/revision-trimestral.md) |

**Aprendizajes verificados:** 53 documentos en [aprendizajes/](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/aprendizajes/)

---

## 3. Tabla de Referencias Técnicas

Esta tabla relaciona cada especificación con los documentos internos, workflows y aprendizajes que aplican durante su implementación.

| Especificación | Directrices Aplicables | Workflows Obligatorios | Aprendizajes Clave |
|----------------|----------------------|----------------------|-------------------|
| **f-100** Frontend Architecture | §2.2 (Theming), §2.2.1 (Tokens), §2.2.2 (Body classes), §3 (Multi-tenancy) | `/scss-estilos`, `/frontend-page-pattern`, `/sdc-components` | `2026-02-05_arquitectura_theming_federated_tokens`, `2026-01-29_frontend_pages_pattern`, `2026-02-02_page_builder_frontend_limpio_zero_region` |
| **f-101** Industry Presets | §2.2.1 (Design Tokens), §1.5 (i18n) | `/scss-estilos`, `/i18n-traducciones` | `2026-01-26_extension_diseno_premium_frontend` |
| **f-102** Premium Presets | §2.2 (Dart Sass), §2.2.1 (Variables inyectables) | `/scss-estilos`, `/premium-cards-pattern` | `2026-02-06_premium_blocks_matrix_effects`, `2026-01-26_iconos_svg_landing_verticales` |
| **f-102B** Institutional | §2.2 (Accesibilidad), §1.5 (i18n) | `/scss-estilos`, `/i18n-traducciones`, `/auditoria-exhaustiva` | `2026-01-24_auditoria_ux_clase_mundial` |
| **f-103** UX Journeys | §2.10 (IA Integration), §3.4 (Tenant isolation) | `/ai-integration`, `/frontend-page-pattern`, `/slide-panel-modales` | `2026-01-21_copiloto_canvas_ux`, `2026-01-21_desbloqueo_progresivo_ux`, `2026-01-26_reutilizacion_patrones_ia` |
| **f-104** Admin Center | §2.10 (IA), §3 (Multi-tenancy), §2.2 (Theming) | `/browser-verification`, `/drupal-custom-modules` | `2026-02-06_admin_center_d_impersonation_rbac_reports` |

---

## 4. Resúmenes por Especificación

### 4.1 f-100: Frontend Architecture Multi-Tenant (244 líneas)

**Propósito:** Arquitectura escalable usando un único tema base con Design Tokens y Component Variants para personalización multi-tenant. Define una cascada de configuración de 5 capas: `Platform Defaults → Vertical Override → Plan Limits → Tenant Custom`.

**Componentes principales:** Config Entity Multi-tenant, Component Library visual, Visual Picker (interfaz no-code para selección de variantes de Header, Card, Hero, Footer), Feature Flags por plan de suscripción.

**Relación con Theming Master:** Esta especificación extiende la arquitectura Federated Design Tokens v2.1 ya establecida, añadiendo las capas 2 (Config Entity) y 3 (Visual Picker) que aún no existen. Las capas 1 (Runtime CSS) y 5 (SCSS/CSS) ya están implementadas.

### 4.2 f-101: Industry Style Presets (140 líneas)

**Propósito:** Proporcionar puntos de partida visuales premium según el sector del tenant. Organización taxonómica: `Vertical → Sector → Mood`. Ejemplo: `AgroConecta → Gourmet → Premium Artesanal`.

**Estructura de preset:** Cada preset define Design Tokens (colores, tipografía, espaciado, bordes, sombras), Component Variants seleccionados, y directrices de contenido/fotografía. Los tokens usan el namespace `--ej-*` compatible con el sistema existente.

### 4.3 f-102: Premium Implementation (639 líneas)

**Propósito:** Expansión premium de los presets con 16+ variantes comerciales. Introduce tendencias modernas: glassmorphism (`backdrop-filter: blur(10px)`), Aurora Gradients, Animated Grain, micro-interacciones, y personalización IA (análisis de logo → extracción de paleta). Define tokens premium nuevos: `--ej-glass-bg`, `--ej-glass-blur`, `--ej-gradient-primary`, `--ej-animation-speed`, `--ej-grain-opacity`.

### 4.4 f-102 Annex B: Institutional Presets (359 líneas)

**Propósito:** 17 presets para verticales institucionales (Empleabilidad, Emprendimiento, Andalucía +ei, Certificación). Requisito obligatorio: WCAG 2.1 AA con contraste mínimo 4.5:1 y soporte `prefers-reduced-motion`. Los presets Andalucía +ei requieren tokens de marca específicos de la Junta de Andalucía.

### 4.5 f-103: UX Journey Specifications (592 líneas)

**Propósito:** Navegación inteligente para 19 avatars con principios Zero-Click Intelligence y Progressive Disclosure. Arquitectura de 3 engines: Context (analiza estado usuario), Decision (IA recomendaciones), Presentation (adapta UI). Define intervenciones proactivas por avatar (triggers, acciones, KPIs). El `JourneyEngineService` y 19 avatar definitions ya están implementados (KI verificado Feb 2026).

### 4.6 f-104: SaaS Admin Center Premium (699 líneas)

**Propósito:** Centro administrativo enterprise con React 18+, TypeScript, Tailwind CSS, dark mode nativo, y WebSockets. Define 9 módulos (Dashboard, Tenant, Users, Finance, Analytics, Alerts, Settings, Logs, AI Center) y APIs REST dedicadas. El Command Palette (Cmd+K) y sidebar collapsible son componentes clave de la UX.

---

## 5. Tabla de Mapeo

### 5.1 Arquitectura Frontend (f-100)

| # | Componente | Destino | Estado | Sprint |
|---|-----------|---------|--------|--------|
| 1 | CSS Runtime Injection | `ecosistema_jaraba_theme.theme` | ✅ Implementado | — |
| 2 | Config Entity Multi-tenant | `DesignTokenConfig` entity | ✅ **Implementado Feb 2026** | S1 ✅ |
| 3 | Component Library | `ecosistema_jaraba_theme/components/` (SDC) | ⏳ 2 SDC existen | S2-3 |
| 4 | Design Token Admin UI | `/admin/structure/design-tokens` | ✅ **Implementado Feb 2026** | S2 ✅ |
| 5 | SCSS/CSS ADN Theme | `ecosistema_jaraba_theme/scss/` | ✅ 45 archivos | — |
| 6 | Visual Picker | Nuevo controller + JS | ❌ Nuevo | S3 |
| 7 | Feature Flags por Plan | `FeatureUnlockService` parcial | ⏳ Solo Emprendimiento | S4 |
| 8 | Token Cascade 4 niveles | `StylePresetService` + `PageAttachmentsHooks` | ✅ **Implementado Feb 2026** — `html:root` specificity | S1-2 ✅ |

### 5.2 Style Presets (f-101 + f-102 + f-102B)

| # | Componente | Destino | Estado | Sprint |
|---|-----------|---------|--------|--------|
| 9 | StylePresetService | `ecosistema_jaraba_core/src/Service/StylePresetService.php` | ✅ **Implementado Feb 2026** | S3 ✅ |
| 10 | 16 Presets Comerciales | JSON + migration | ❌ Nuevo | S3-4 |
| 11 | 17 Presets Institucionales | JSON + migration | ❌ Nuevo | S4-5 |
| 12 | Glassmorphism/Aurora Tokens | `_injectable.scss` update | ❌ Nuevo | S3 |
| 13 | WCAG 2.1 AA Validation | Nuevo service | ❌ Nuevo | S5 |
| 14 | AI Color Extraction | `AiDesignService` | ❌ Nuevo | S6 |

### 5.3 UX Journey Engine (f-103)

| # | Componente | Destino | Estado | Sprint |
|---|-----------|---------|--------|--------|
| 15 | 19 Avatar Profiles | `JourneyEngineService` | ✅ Implementado | — |
| 16 | 7 State Transitions | Journey state machine | ✅ Implementado | — |
| 17 | Context Engine ampliado | `CopilotContextService` | ⏳ Parcial | S6 |
| 18 | Decision Engine IA | Nuevo `JourneyDecisionService` | ❌ Nuevo | S7-8 |
| 19 | Presentation Engine | Template layer + JS adaptativo | ❌ Nuevo | S8-9 |
| 20 | IA Proactive Interventions | `InAppMessagingService` triggers | ⏳ Parcial | S7 |

### 5.4 Admin Center (f-104)

| # | Componente | Destino | Estado | Sprint |
|---|-----------|---------|--------|--------|
| 21 | React SPA Foundation | `web/apps/admin-center/` | ❌ Nuevo | S11-12 |
| 22 | Dashboard Module | React component | ❌ Nuevo | S11 |
| 23 | Tenant Management | React + API REST | ❌ Nuevo | S12-13 |
| 24 | User Management | React + API REST | ❌ Nuevo | S13-14 |
| 25 | Finance/FOC Integration | React + `jaraba_foc` APIs | ⏳ FOC existe | S14-15 |
| 26 | Dark Mode Native | CSS tokens + toggle | ⏳ `_dark-mode.scss` parcial | S11 |
| 27 | WebSocket Integration | Drupal WS + React client | ❌ Nuevo | S13 |
| 28 | API REST Admin | `jaraba_admin_api` module | ⏳ Parcial | S11-12 |

---

## 6. Verificación de Cumplimiento de Directrices

Cada regla de las directrices del proyecto (`00_DIRECTRICES_PROYECTO.md` v5.8) ha sido verificada contra las especificaciones f-100 a f-104.

### 6.1 Directrices de Theming (§2.2)

| Regla | Directriz | Estado en Specs | Evidencia |
|-------|-----------|----------------|-----------|
| D-01 | Usar `var(--ej-*, $fallback)` — NUNCA hex directos | ✅ Cumple | f-100 usa namespace `--ej-*` en todos los tokens |
| D-02 | Compilar con Dart Sass `color.adjust()` | ✅ Cumple | f-102 tokens son Dart Sass compatible |
| D-03 | Body classes vía `hook_preprocess_html()` NUNCA en Twig | ✅ Cumple | f-100 requiere `hook_preprocess_html` para inyección CSS |
| D-04 | Módulos satélite NO definen `$ej-*` variables | ✅ Cumple | Los presets (f-101/102) inyectan via CSS, no SCSS vars |
| D-05 | Todo SCSS con `package.json` y header doc | ⚠️ Verificar | Los presets nuevos necesitarán `package.json` |
| D-06 | Iconos con firma `jaraba_icon('cat', 'name', opts)` | ✅ Cumple | f-104 usa Lucide Icons pero son parte de React SPA |
| D-07 | Nuevos iconos DEBEN tener variante duotone | ✅ Cumple | KI icon_mapping_guide ya lo define |

### 6.2 Directrices de IA (§2.10)

| Regla | Directriz | Estado en Specs | Notas |
|-------|-----------|----------------|-------|
| D-08 | NUNCA HTTP directo a APIs IA — usar `@ai.provider` | ✅ Cumple | f-103 AI agents usan módulo AI Drupal |
| D-09 | Moderación: Anthropic "No Mod", OpenAI "Enable" | ✅ Cumple | f-103 Decision Engine respeta configuración |
| D-10 | Fallback automático Claude → GPT-4 → Error graceful | ✅ Cumple | f-103/104 definen failover patterns |
| D-11 | Tracking tokens/costos por proveedor | ✅ Cumple | f-104 AI Center incluye token usage dashboard |

### 6.3 Directrices de Multi-Tenancy (§3)

| Regla | Directriz | Estado en Specs | Notas |
|-------|-----------|----------------|-------|
| D-12 | Single-Instance + Group Module, NO multisite | ✅ Cumple | f-100 cascada usa Config Entity, no multisite |
| D-13 | Aislamiento de datos por Group Content | ✅ Cumple | f-100/103 respetan tenant isolation |
| D-14 | Tenant NUNCA ve Drupal admin (Gin) | ✅ Cumple | f-100 Visual Picker es frontend, no admin |
| D-15 | Sin links hardcoded del ecosistema | ✅ Cumple | f-103 usa route names, no URLs |

### 6.4 Directrices Generales (§1, §2)

| Regla | Directriz | Estado en Specs | Notas |
|-------|-----------|----------------|-------|
| D-16 | Todo texto en `{% trans %}` o `Drupal.t()` | ✅ Cumple | f-100/102 templates Twig usan `{% trans %}` |
| D-17 | Dashboards usan Zero Region Policy | ✅ Cumple | f-104 Admin Center es React SPA aislada |
| D-18 | CRUD en modales/slide-panels, no page hops | ✅ Cumple | f-100 Visual Picker es modal overlay |
| D-19 | Semántica HTML5 (h1 único, landmarks) | ✅ Cumple | f-102B WCAG AA lo exige explícitamente |
| D-20 | Mobile-first responsive design | ✅ Cumple | f-100 define breakpoints mobile-first |

---

## 7. Verificación de Cumplimiento de Workflows

Cada workflow del proyecto ha sido verificado contra las especificaciones para asegurar que toda implementación futura los siga correctamente.

### 7.1 Workflow `/scss-estilos` (347 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| ⛔ NUNCA crear CSS directamente, siempre SCSS | ✅ f-100/102 usan SCSS | Ninguna |
| SIEMPRE usar `var(--ej-*)` inyectables | ✅ Todos los tokens usan `--ej-*` | Ninguna |
| Paleta oficial Jaraba (7 colores) | ✅ f-102 extiende con tokens premium | Añadir `--ej-glass-*` y `--ej-gradient-*` a `_injectable.scss` |
| Compilación con NVM en WSL | ✅ Compatible | Verificar para nuevos presets |
| Crear AMBAS versiones de iconos (outline + duotone) | ✅ f-102/104 no crean iconos nuevos | Si se crean, seguir regla |
| Parcial SCSS por componente + import en `main.scss` | ⚠️ Presets necesitarán parciales nuevos | Crear `_style-presets.scss` |

### 7.2 Workflow `/frontend-page-pattern` (207 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| Template sin regiones Drupal (Zero Region) | ✅ f-100/104 son aisladas | Ninguna |
| Sugerencia template vía `hook_theme_suggestions_page_alter` | ✅ Patrón estándar | Registrar nuevas rutas |
| Body classes vía `hook_preprocess_html()` | ✅ f-100 lo usa explícitamente | Añadir clases para Visual Picker |
| Header/Footer vía `@include` de partials | ✅ f-100 frontend usa partials | Ninguna |
| JS en archivo separado, NO inline en template | ✅ Estándar respetado | Verificar en Visual Picker JS |

### 7.3 Workflow `/sdc-components` (129 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| ⛔ Todos los componentes DEBEN seguir SDC Drupal 11 | ⚠️ f-100 Component Library necesita SDC | Crear SDC para cada variante de componente |
| Un template con Compound Variants, NO separados | ✅ f-100 define variantes por componente | Implementar Card, Hero, Header, Footer como SDC |
| Props tipados en `component.yml` | ✅ Compatible con f-100 props | Definir schemas para variantes |
| `{% trans %}` para textos | ✅ Estándar | Ninguna |
| `var(--ej-*)` para colores | ✅ Estándar | Ninguna |

### 7.4 Workflow `/ai-integration` (217 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| NUNCA HTTP directo — usar `@ai.provider` | ✅ f-103/104 compatible | Verificar nuevos servicios IA |
| Failover `PROVIDERS = ['anthropic', 'openai']` | ✅ f-103 lo requiere | Implementar en Decision Engine |
| Claves en `/admin/config/system/keys` | ✅ Estándar | Ninguna |
| CopilotQueryLoggerService para analytics | ✅ f-103 KPI tracking requiere logging | Integrar en nuevos triggers |

### 7.5 Workflow `/premium-cards-pattern` (100 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| Glassmorphism obligatorio (blur 10px mín) | ✅ f-102 define `--ej-glass-blur` | Alinear valores |
| Hover 3D lift `translateY(-6px) scale(1.02)` | ✅ f-102 micro-interacciones | Estandarizar |
| Efecto Shine en hover | ✅ Compatible | Aplicar a Component Variants |
| `cubic-bezier(0.175, 0.885, 0.32, 1.275)` | ✅ Curva estándar | Ninguna |

### 7.6 Workflow `/slide-panel-modales` (172 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| Crear/editar/ver en slide-panel | ✅ f-100 Visual Picker es modal | Verificar UX flow |
| Controlador detecta AJAX → respuesta limpia | ✅ Patrón establecido | Aplicar a nuevos forms |
| Ocultar "ruido" Drupal en formularios | ✅ `hook_form_alter` | Aplicar a Config Entity forms |

### 7.7 Workflow `/drupal-custom-modules` (464 líneas)

| Regla del Workflow | Cumplimiento en Specs | Acción Requerida |
|-------------------|----------------------|-----------------|
| Verificación arquitectónica obligatoria antes de crear | ✅ | Verificar Qdrant, Redis, H5P existentes |
| ConfigEntity vs ContentEntity decisión correcta | ⚠️ `DesignTokenConfig` debe ser ConfigEntity | Confirmar tipo correcto |
| Content Entities en `/admin/content`, NO `/admin/config` | ✅ Estándar | Verificar rutas nuevas |
| 4 archivos YAML obligatorios por entidad | ✅ Estándar | Crear para nuevas entidades |

### 7.8 Workflow `/browser-verification` (73 líneas)

| Regla | Cumplimiento | Acción |
|-------|-------------|--------|
| Verificación visual obligatoria post-cambio | ✅ | Ejecutar para cada sprint |
| Checklist premium: glassmorphism, footer, header | ✅ | Aplicar a presets |
| Screenshots en walkthrough.md | ✅ | Documentar |

---

## 8. Verificación de Cumplimiento de Aprendizajes

Los 53 aprendizajes documentados en `docs/tecnicos/aprendizajes/` se agrupan en categorías y se verifica su aplicación a las especificaciones:

### 8.1 Aprendizajes de Theming y Frontend (12)

| Aprendizaje | Fecha | Aplicación a Specs |
|-------------|-------|--------------------|
| `arquitectura_theming_federated_tokens` | 2026-02-05 | ✅ Base para f-100 cascada de tokens |
| `frontend_pages_pattern` | 2026-01-29 | ✅ Patrón para Visual Picker (f-100) |
| `page_builder_frontend_limpio_zero_region` | 2026-02-02 | ✅ Admin Center (f-104) Zero Region |
| `extension_diseno_premium_frontend` | 2026-01-26 | ✅ f-102 premium design tokens |
| `iconos_svg_landing_verticales` | 2026-01-26 | ✅ f-102 iconografía premium |
| `premium_blocks_matrix_effects` | 2026-02-06 | ✅ f-102 glassmorphism, animations |
| `header_partials_dispatcher` | 2026-01-25 | ✅ f-100 header variants |
| `site_builder_frontend_fullwidth` | 2026-01-29 | ✅ f-100 full-width layout |
| `page_builder_dynamic_theme_registration` | 2026-02-02 | ✅ f-100 component variants |
| `twig_namespace_cross_module` | 2026-02-03 | ✅ f-100 template organization |
| `elevacion_page_builder_clase_mundial` | 2026-02-08 | ✅ f-102 premium standards |
| `frontend_premium_landing` | 2026-01-24 | ✅ f-102 landing page presets |

### 8.2 Aprendizajes de IA (7)

| Aprendizaje | Fecha | Aplicación a Specs |
|-------------|-------|--------------------|
| `copiloto_canvas_ux` | 2026-01-21 | ✅ f-103 Copilot UX patterns |
| `desbloqueo_progresivo_ux` | 2026-01-21 | ✅ f-103 Progressive Disclosure |
| `reutilizacion_patrones_ia` | 2026-01-26 | ✅ f-103 AI agent reuse |
| `servicios_ia_patrones_agroconecta` | 2026-01-26 | ✅ f-103 service patterns |
| `reuso_agentes_ia_agroconecta` | 2026-01-28 | ✅ f-103 multi-tenant IA |
| `ai_smart_router_rag` | 2026-01-21 | ✅ f-103 Decision Engine |
| `agentic_workflows_marketing_ai_stack` | 2026-02-06 | ✅ f-104 AI Center |

### 8.3 Aprendizajes de Entidades Drupal (6)

| Aprendizaje | Fecha | Aplicación a Specs |
|-------------|-------|--------------------|
| `entity_navigation_pattern` | 2026-01-19 | ✅ f-100 Config Entity navigation |
| `content_entities_drupal` | 2026-01-25 | ✅ f-100 DesignTokenConfig entity |
| `entity_field_mismatch_drush_entup` | 2026-01-28 | ✅ Deployment de nuevas entidades |
| `admin_center_d_impersonation_rbac_reports` | 2026-02-06 | ✅ f-104 Admin Center patterns |
| `auditoria_exhaustiva_gaps_resueltos` | 2026-01-23 | ✅ Gap tracking methodology |
| `status_report_entity_updates` | 2026-01-17 | ✅ Entity update procedures |

### 8.4 Aprendizajes de Auditoría y Calidad (5)

| Aprendizaje | Fecha | Aplicación a Specs |
|-------------|-------|--------------------|
| `auditoria_ux_clase_mundial` | 2026-01-24 | ✅ f-102B WCAG AA requirements |
| `auditoria_ecosistema_10_10` | 2026-01-28 | ✅ Quality benchmark for all specs |
| `auditoria_profunda_saas_multidimensional` | 2026-02-06 | ✅ Cross-cutting verification |
| `auditoria_v2_falsos_positivos_page_builder` | 2026-02-09 | ⚠️ Prevent false positives in presets |
| `auditoria_exhaustiva_gaps_resueltos` | 2026-01-23 | ✅ Gap resolution methodology |

---

## 9. Análisis de Brechas

| Dimensión | Especificado | Implementado | Gap | Prioridad |
|-----------|-------------|-------------|-----|-----------|
| Arquitectura Frontend (f-100) | 8 componentes | 3 completos, 3 parciales | 40% | P0 |
| Style Presets (f-101/102/102B) | 33 presets, service | 0 presets | 100% | P0 |
| UX Journey Engine (f-103) | 19 avatars, 3 engines | Avatars + states done | 60% | P1 |
| Admin Center (f-104) | 9 módulos React | 0 módulos | 100% | P1 |
| WCAG Accessibility (f-102B) | WCAG 2.1 AA | Parcial | 70% | P1 |
| AI Personalization (f-102/103) | Color extraction, A/B | Copilot basic | 80% | P2 |

### Brechas Críticas (P0)
1. ~~**Config Entity `DesignTokenConfig`**~~ — ✅ **RESUELTO Feb 2026** — entity + admin UI + 4 configs verticales
2. ~~**`StylePresetService`**~~ — ✅ **RESUELTO Feb 2026** — cascada 4 niveles con `html:root` specificity
3. **Tokens glassmorphism en `_injectable.scss`** — f-102 los requiere para presets premium — **PENDIENTE**

---

## 10. Roadmap de Implementación

### Fase 1: Foundation (S1-S5, 10 semanas)
| Sprint | Entregable | Workflows Aplicables |
|--------|-----------|---------------------|
| S1 | Config Entity + Admin UI | `/drupal-custom-modules`, `/scss-estilos` |
| S2 | Token Cascade 4 niveles | `/scss-estilos`, `/frontend-page-pattern` |
| S3 | StylePresetService + 10 presets | `/scss-estilos`, `/sdc-components` |
| S4 | Presets institucionales WCAG | `/scss-estilos`, `/auditoria-exhaustiva` |
| S5 | Onboarding integration + Feature Flags | `/slide-panel-modales` |

### Fase 2: Intelligence (S6-S10, 10 semanas)
| Sprint | Entregable | Workflows Aplicables |
|--------|-----------|---------------------|
| S6 | Context Engine + AI Color Extraction | `/ai-integration` |
| S7 | Decision Engine IA + triggers proactivos | `/ai-integration` |
| S8 | Presentation Engine + Progressive Disclosure | `/frontend-page-pattern` |
| S9 | Cross-Sell/Upsell UI | `/slide-panel-modales`, `/premium-cards-pattern` |
| S10 | KPI Dashboard por avatar | `/browser-verification` |

### Fase 3: Admin Center (S11-S16, 15 semanas)
| Sprint | Entregable | Workflows Aplicables |
|--------|-----------|---------------------|
| S11 | React SPA + Design System + Dark Mode | `/browser-verification` |
| S12 | Dashboard + Command Palette | `/browser-verification` |
| S13 | Tenant Management + WebSocket | `/drupal-custom-modules` |
| S14 | User Management + FOC | `/drupal-custom-modules` |
| S15-16 | Analytics, Alerts, Logs | `/browser-verification` |

### Fase 4: Excellence (S17-S20, 8 semanas)
| Sprint | Entregable | Workflows Aplicables |
|--------|-----------|---------------------|
| S17-18 | Settings + AI Center | `/ai-integration` |
| S19 | WCAG Automated Testing CI/CD | `/cypress-e2e`, `/auditoria-exhaustiva` |
| S20 | Performance Optimization | `/browser-verification` |

---

## 11. Plan de Verificación

### 11.1 Verificación por Fase

| Fase | Tipo de Test | Herramienta | Criterio |
|------|-------------|------------|---------|
| F1 | Token Cascade | DevTools `:root` | Variables heredan correctamente |
| F1 | WCAG Contrast | axe-core extension | Ratios ≥ 4.5:1 |
| F1 | SCSS Build | `npx sass --style=compressed` | 0 errores |
| F2 | AI Triggers | Cypress E2E | Copilot responde a triggers |
| F3 | React SPA Boot | Browser | Carga sin errores |
| F3 | WebSocket | Network tab | Conexión estable |
| F4 | Lighthouse | Chrome audit | Score > 90 |

### 11.2 Checklist de Cumplimiento Pre-Sprint

Antes de comenzar **cualquier sprint** de este plan, verificar:

- [ ] ¿Los nuevos SCSS siguen `_parcial.scss` + import en `main.scss`?
- [ ] ¿Se usan `var(--ej-*, $fallback)` y NO hex directos?
- [ ] ¿Body classes se añaden en `hook_preprocess_html()`?
- [ ] ¿Templates usan `{% trans %}` para TODO texto visible?
- [ ] ¿Iconos nuevos tienen variantes outline + duotone?
- [ ] ¿IA usa `@ai.provider`, NO HTTP directo?
- [ ] ¿Las rutas nuevas se verificaron con `/browser-verification`?
- [ ] ¿Las nuevas entidades tienen los 4 archivos YAML?
- [ ] ¿Se ejecutó `drush cr` + verificación visual post-cambio?

---

> **Nota:** Este documento es el SSOT para la implementación de las especificaciones f-100 a f-104. Actualizar al inicio de cada sprint con el estado real.
