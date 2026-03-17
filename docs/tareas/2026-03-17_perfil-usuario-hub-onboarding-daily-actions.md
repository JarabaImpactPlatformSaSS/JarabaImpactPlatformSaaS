# Perfil de Usuario como Hub Inteligente de Onboarding y Acciones Diarias

**Fecha:** 2026-03-17
**Estado:** ✅ FUNCIONAL (Sprint 1-3 completados 2026-03-17)
**Prioridad:** P0 (UX crítica — primer contacto del usuario con el SaaS)
**Rama:** main
**Módulo principal:** `ecosistema_jaraba_core`
**Módulos relacionados:** `ecosistema_jaraba_theme`, todos los módulos verticales con Setup Wizard + Daily Actions
**Directrices aplicables:** SETUP-WIZARD-DAILY-001, PIPELINE-E2E-001, ZERO-REGION-001, CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ROUTE-LANGPREFIX-001, TWIG-INCLUDE-ONLY-001, OPTIONAL-CROSSMODULE-001, TENANT-001, ZEIGARNIK-PRELOAD-001, PREMIUM-FORMS-PATTERN-001, SCSS-COMPILE-VERIFY-001, OBSERVER-SCROLL-ROOT-001

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnóstico del Gap Actual](#2-diagnostico-del-gap-actual)
   - 2.1 [Estado actual del perfil de usuario](#21-estado-actual-del-perfil-de-usuario)
   - 2.2 [Estado actual del Setup Wizard + Daily Actions](#22-estado-actual-del-setup-wizard--daily-actions)
   - 2.3 [El gap: "el código existe" vs "el usuario lo experimenta"](#23-el-gap-el-codigo-existe-vs-el-usuario-lo-experimenta)
3. [Arquitectura Propuesta](#3-arquitectura-propuesta)
   - 3.1 [Nuevo servicio: AvatarWizardBridgeService](#31-nuevo-servicio-avatarwizardbridgeservice)
   - 3.2 [Mapping Avatar → Wizard/Dashboard/Context](#32-mapping-avatar--wizarddashboardcontext)
   - 3.3 [Flujo de datos E2E (PIPELINE-E2E-001)](#33-flujo-de-datos-e2e-pipeline-e2e-001)
   - 3.4 [Decisiones de UX](#34-decisiones-de-ux)
4. [Especificaciones Técnicas Detalladas](#4-especificaciones-tecnicas-detalladas)
   - 4.1 [AvatarWizardBridgeService.php](#41-avatarwizardbridgeservicephp)
   - 4.2 [Registro en services.yml](#42-registro-en-servicesyml)
   - 4.3 [Preprocess: inyección de variables](#43-preprocess-inyeccion-de-variables)
   - 4.4 [Template: page--user.html.twig](#44-template-page--userhtmltwig)
   - 4.5 [SCSS: adaptaciones visuales](#45-scss-adaptaciones-visuales)
   - 4.6 [JS: behavior de refresh y transiciones](#46-js-behavior-de-refresh-y-transiciones)
5. [Inventario de Archivos](#5-inventario-de-archivos)
   - 5.1 [Archivos a crear](#51-archivos-a-crear)
   - 5.2 [Archivos a modificar](#52-archivos-a-modificar)
   - 5.3 [Archivos reutilizados (sin cambios)](#53-archivos-reutilizados-sin-cambios)
6. [Tabla de Correspondencia con Directrices](#6-tabla-de-correspondencia-con-directrices)
7. [Plan de Ejecución](#7-plan-de-ejecucion)
   - 7.1 [Sprint 1: Servicio bridge + preprocess + template](#71-sprint-1-servicio-bridge--preprocess--template)
   - 7.2 [Sprint 2: SCSS + JS + polish UX](#72-sprint-2-scss--js--polish-ux)
   - 7.3 [Sprint 3: Tests + verificación runtime](#73-sprint-3-tests--verificacion-runtime)
8. [Testing y Verificación](#8-testing-y-verificacion)
   - 8.1 [Tests unitarios](#81-tests-unitarios)
   - 8.2 [Tests kernel](#82-tests-kernel)
   - 8.3 [Verificación RUNTIME-VERIFY-001](#83-verificacion-runtime-verify-001)
   - 8.4 [Verificación PIPELINE-E2E-001](#84-verificacion-pipeline-e2e-001)
9. [Criterios de Aceptación](#9-criterios-de-aceptacion)
10. [Riesgos y Mitigaciones](#10-riesgos-y-mitigaciones)
11. [Apéndice: Inventario completo de Wizard IDs y Dashboard IDs](#11-apendice-inventario-completo-de-wizard-ids-y-dashboard-ids)

---

## 1. Resumen Ejecutivo

### Problema

El perfil de usuario (`/user/{uid}`) es actualmente una página estática de información de cuenta con accesos rápidos organizados en secciones extensibles (via `UserProfileSectionRegistry`). **No guía al usuario nuevo hacia su primer valor ni muestra las acciones relevantes para el día a día.** El Setup Wizard y las Daily Actions — un sistema maduro con 50 steps y 54 actions en 10 verticales — solo se renderizan en los dashboards verticales individuales, a los que el usuario tiene que navegar manualmente.

### Solución

Convertir la vista de perfil en un **hub inteligente** que:

1. **Detecta el avatar** del usuario (jobseeker, entrepreneur, merchant, etc.) usando `AvatarDetectionService`
2. **Resuelve dinámicamente** el wizard_id y dashboard_id correcto usando un nuevo `AvatarWizardBridgeService`
3. **Renderiza el Setup Wizard** (progreso de onboarding) y las **Daily Actions** (acciones vivas con badges de estado) directamente en el perfil
4. **Reutiliza al 100%** los partials Twig probados (`_setup-wizard.html.twig` y `_daily-actions.html.twig`) y los registries existentes (`SetupWizardRegistry`, `DailyActionsRegistry`)

### Impacto esperado

- **Onboarding:** El usuario ve su progreso al 33-50% desde el primer momento (efecto Zeigarnik — ZEIGARNIK-PRELOAD-001), con CTA claro al siguiente paso
- **Retención diaria:** Las acciones con badges dinámicos ("3 pedidos pendientes", "2 borradores") crean urgencia y guían al flujo natural de trabajo
- **Coherencia:** El mismo patrón premium de los dashboards verticales se experimenta desde el primer punto de contacto (el perfil)

### Principio rector

> "Sin Humo" — No creamos infraestructura nueva. Conectamos dos sistemas maduros (perfil extensible + wizard/actions) con un servicio bridge de ~120 líneas y ~40 líneas de preprocess.

---

## 2. Diagnóstico del Gap Actual

### 2.1 Estado actual del perfil de usuario

**Ruta:** `entity.user.canonical` → `/user/{uid}`
**Template:** `page--user.html.twig` (390 líneas)
**Preprocess:** `ecosistema_jaraba_theme_preprocess_page__user()` (200 líneas)

**Componentes renderizados actualmente:**

| Componente | Descripción | Estado |
|-----------|-------------|--------|
| Hero Profile Card | Avatar + nombre + email + roles + "Miembro desde" + botones | Funcional |
| Account Info Cards | Grid 3 columnas: Email, Roles, Último acceso | Funcional |
| Quick Access Sections | Secciones extensibles via `UserProfileSectionRegistry` (5 built-in + 1 Andalucía +ei) | Funcional |
| Profile Completeness | Ring SVG con checklist (solo para `professional_profile` section) | Funcional |
| Andalucía +ei Stats | Mini-dashboard por rol (participante/orientador/coordinador) | Funcional |

**Arquitectura de extensibilidad (ya implementada):**

```
UserProfileSectionInterface → tagged services → UserProfileSectionPass → UserProfileSectionRegistry
     ↓                                                                            ↓
5 secciones built-in:                                                    buildSectionsArray($uid)
  - ProfessionalProfileSection (w=10)                                           ↓
  - MyVerticalSection (w=20)                                          $variables['user_quick_sections']
  - MyBusinessSection (w=30)                                                    ↓
  - AdministrationSection (w=80)                                     page--user.html.twig consume
  - AccountSection (w=100)
```

**Estilos:** `scss/_user-pages.scss` (1.532 líneas) con clases premium (`.user-profile`, `.profile-hero`, `.account-info`, `.quick-access-sections`, etc.)

### 2.2 Estado actual del Setup Wizard + Daily Actions

**Sistema maduro y probado**, operativo en 10 verticales:

#### Inventario de Wizard IDs (13 wizards)

| wizard_id | Vertical | Módulo | # Steps |
|-----------|---------|--------|---------|
| `candidato_empleo` | empleabilidad | jaraba_candidate | 5 (perfil, experiencia, formacion, habilidades, idiomas) |
| `entrepreneur_tools` | emprendimiento | jaraba_business_tools | 3 (perfil, diagnostico, canvas) |
| `merchant_comercio` | comercioconecta | jaraba_comercio_conecta | 5 (perfil, pagos, envio, qr, catalogo) |
| `producer_agro` | agroconecta | jaraba_agroconecta_core | 5 (perfil, certificacion, envio, trazabilidad, catalogo) |
| `provider_servicios` | serviciosconecta | jaraba_servicios_conecta | 4 (perfil, servicio, paquete, disponibilidad) |
| `legal_professional` | jarabalex | jaraba_legal_intelligence | 3 (areas, favoritos, alertas) |
| `learner_lms` | formacion | jaraba_lms | 3 (perfil, primer_curso, primera_leccion) |
| `instructor_lms` | formacion | jaraba_lms | 3 (perfil, curso, leccion) |
| `editor_content_hub` | jaraba_content_hub | jaraba_content_hub | 3 (autor, categoria, articulo) |
| `mentor` | empleabilidad | jaraba_mentoring | 3 (perfil, disponibilidad, areas) |
| `coordinador_ei` | andalucia_ei | jaraba_andalucia_ei | 4 (acciones_formativas, plan, sesiones, validacion) |
| `orientador_ei` | andalucia_ei | jaraba_andalucia_ei | 3 (perfil, participantes, sesion) |
| `emprendedor` | emprendimiento | jaraba_business_tools | 3 (perfil, diagnostico, canvas) — alias legacy |

#### Inventario de Dashboard IDs (13 dashboards — paridad 1:1 con wizards)

| dashboard_id | # Actions | Ejemplos de acciones |
|-------------|-----------|---------------------|
| `candidato_empleo` | 4 | Actualizar CV, Buscar empleos, Ver postulaciones, Chat Copilot |
| `entrepreneur_tools` | 4 | Aprendizaje, Canvas, Herramientas, KPIs |
| `merchant_comercio` | 5 | Pedidos pendientes, Stock, Ofertas, Nuevo producto, Analíticas |
| `producer_agro` | 4 | Alertas calidad, Pedidos, Registrar lote, Nuevo producto |
| `provider_servicios` | 4 | Reservas pendientes, Nuevo servicio, Horario, Reseñas |
| `legal_professional` | 4 | Favoritos, Jurisprudencia, Alertas normativas, Consultar IA |
| `learner_lms` | 4 | Continuar curso, Certificados, Explorar catálogo, Reseñas |
| `instructor_lms` | 4 | Nueva lección, Analíticas, Gestionar alumnos, Ver reseñas |
| `editor_content_hub` | 4 | Nuevo artículo, Borradores, Comentarios, Generar con IA |
| `mentor` | 4 | Sesiones pendientes, Nueva sesión, Reseñas, Estadísticas |
| `coordinador_ei` | 9 | Solicitudes, Sesiones, Acciones formativas, Compliance, etc. |
| `orientador_ei` | 8 | Hojas de seguimiento, Sesiones, Participantes, etc. |

**Componentes compartidos (partials Twig):**

| Partial | Archivo | Líneas | Funcionalidades |
|---------|---------|--------|----------------|
| `_setup-wizard.html.twig` | `templates/partials/` | 203 | Ring SVG, stepper horizontal, collapse/expand, slide-panel, animaciones escalonadas, WCAG AA |
| `_daily-actions.html.twig` | `templates/partials/` | 88 | Grid responsive, badges dinámicos, primary card span, stagger entrance |

**ZEIGARNIK-PRELOAD-001:** 2 steps globales (`__global__`) siempre completados:
- `AutoCompleteAccountStep`: "Cuenta creada" (w=-20)
- `AutoCompleteVerticalStep`: "Vertical configurada" (w=-10)

Resultado: el wizard arranca al 33-50% visualmente, lo que incrementa un 12-28% la tasa de completitud.

### 2.3 El gap: "el código existe" vs "el usuario lo experimenta"

**El flujo del usuario ACTUAL:**

```
Registro → /user/{uid} → Ve info estática + links genéricos
                                    ↓
                          "¿Y ahora qué hago?"
                                    ↓
                          Tiene que descubrir manualmente su dashboard vertical
                                    ↓
                          /mi-comercio | /candidate/dashboard | /legal/dashboard | ...
                                    ↓
                          AHORA sí ve el Setup Wizard + Daily Actions
```

**El flujo DESEADO:**

```
Registro → /user/{uid} → Ve su Setup Wizard personalizado (33-50% ya completado)
                                    ↓
                          Ve sus Daily Actions con badges vivos
                                    ↓
                          CTA "Ir a mi panel" → Dashboard vertical
                                    ↓
                          Ve Quick Access Sections (links contextuales)
```

**Gap identificado:** No existe un servicio que mapee `avatarType` → `wizard_id` + `dashboard_id` + `contextId`. Cada dashboard vertical lo hardcodea en su controller. El perfil de usuario no consume ninguno de estos sistemas.

---

## 3. Arquitectura Propuesta

### 3.1 Nuevo servicio: AvatarWizardBridgeService

**Responsabilidad única:** Dado un `avatarType` (string), resolver los identificadores necesarios para obtener wizard steps y daily actions del usuario en su perfil.

**Ubicación:** `ecosistema_jaraba_core/src/Service/AvatarWizardBridgeService.php`

**Dependencias:**
- `@ecosistema_jaraba_core.avatar_detection` (opcional `@?` — si no existe, el servicio devuelve null)
- `@?ecosistema_jaraba_core.tenant_context` (opcional — para resolver contextId en wizards tenant-scoped)
- `@current_user`

**No depende de** `SetupWizardRegistry` ni `DailyActionsRegistry`. Solo resuelve los IDs — el preprocess llama a los registries directamente. Esto evita acoplar el bridge al rendering.

**Método principal:**

```php
public function resolveForCurrentUser(): ?AvatarWizardMapping
```

Devuelve un value object `AvatarWizardMapping` con:
- `wizardId` (string|null) — NULL si el avatar no tiene wizard registrado
- `dashboardId` (string|null) — NULL si el avatar no tiene daily actions
- `contextId` (int) — user id o tenant id según el scope del avatar
- `avatarType` (string) — para debugging/display
- `vertical` (string|null) — para contextualizar títulos
- `dashboardRoute` (string|null) — ruta del dashboard vertical para CTA "Ir a mi panel"

### 3.2 Mapping Avatar → Wizard/Dashboard/Context

Esta es la tabla central del bridge service. Encapsula el conocimiento de negocio que hoy está disperso en 13 controllers de dashboard:

| avatarType | wizard_id | dashboard_id | Context Scope | Título Wizard | Título Acciones |
|-----------|-----------|-------------|---------------|---------------|----------------|
| `jobseeker` | `candidato_empleo` | `candidato_empleo` | **user** (`$uid`) | "Completa tu perfil profesional" | "Tu día en empleabilidad" |
| `recruiter` | `null` | `null` | — | — | — |
| `entrepreneur` | `entrepreneur_tools` | `entrepreneur_tools` | **tenant** | "Configura tu proyecto" | "Tu día como emprendedor" |
| `producer` | `producer_agro` | `producer_agro` | **tenant** | "Configura tu perfil de productor" | "Tu día en AgroConecta" |
| `buyer` | `null` | `null` | — | — | — |
| `merchant` | `merchant_comercio` | `merchant_comercio` | **tenant** | "Configura tu comercio" | "Tu día en ComercioConecta" |
| `service_provider` | `provider_servicios` | `provider_servicios` | **tenant** | "Configura tus servicios" | "Tu día en ServiciosConecta" |
| `profesional` | `provider_servicios` | `provider_servicios` | **tenant** | "Configura tus servicios" | "Tu día en ServiciosConecta" |
| `student` | `learner_lms` | `learner_lms` | **user** (`$uid`) | "Comienza tu formación" | "Tu día en formación" |
| `mentor` | `mentor` | `mentor` | **user** (`$uid`) | "Configura tu perfil de mentor" | "Tu día como mentor" |
| `legal_professional` | `legal_professional` | `legal_professional` | **user** (`$uid`) | "Configura tu espacio legal" | "Tu día en JarabaLex" |
| `tenant_admin` | `null` | `null` | — | — | — |
| `admin` | `null` | `null` | — | — | — |
| `general` | `null` | `null` | — | — | — |
| `anonymous` | `null` | `null` | — | — | — |

**Reglas de Context Scope (SETUP-WIZARD-DAILY-001 + ZEIGARNIK-PRELOAD-001):**

- **User-scoped** (candidate, legal, content_hub, mentor, student): `contextId = $currentUser->id()`
- **Tenant-scoped** (agro, comercio, servicios, lms, emprendimiento, andalucia_ei): `contextId = TenantContextService::getCurrentTenantId()`
- Si `TenantContextService` no está disponible o devuelve 0, fallback a `$currentUser->id()` (usuario sin tenant = su perfil es su "tenant")

**¿Por qué `recruiter`, `buyer`, `tenant_admin`, `admin`, `general` no tienen wizard?**
- `recruiter`: Dashboard de empleador tiene workflow propio sin wizard steps registrados
- `buyer`: Comprador B2B en AgroConecta — flujo simplificado sin setup
- `tenant_admin`/`admin`: Roles administrativos, no necesitan onboarding SaaS
- `general`: Avatar default cuando no se detecta nada — sin contexto vertical

### 3.3 Flujo de datos E2E (PIPELINE-E2E-001)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                    PIPELINE-E2E-001 — 4 CAPAS                            │
├──────────┬───────────────────────────────────────────────────────────────┤
│  L1      │  ecosistema_jaraba_theme_preprocess_page__user()             │
│ SERVICE  │    ↓                                                          │
│ INJECT   │  AvatarDetectionService::detect() → AvatarDetectionResult    │
│          │    ↓                                                          │
│          │  AvatarWizardBridgeService::resolveForCurrentUser()           │
│          │    → AvatarWizardMapping { wizardId, dashboardId, contextId } │
│          │    ↓                                                          │
│          │  SetupWizardRegistry::getStepsForWizard($wizardId, $ctxId)    │
│          │    → $variables['profile_setup_wizard']                       │
│          │    ↓                                                          │
│          │  DailyActionsRegistry::getActionsForDashboard($dashId, $ctxId)│
│          │    → $variables['profile_daily_actions']                      │
│          │    ↓                                                          │
│          │  Metadata adicional:                                          │
│          │    → $variables['profile_wizard_title']                       │
│          │    → $variables['profile_actions_title']                      │
│          │    → $variables['profile_dashboard_route']                    │
│          │    → $variables['profile_avatar_type']                        │
├──────────┼───────────────────────────────────────────────────────────────┤
│  L2      │  No aplica — no es un controller con #theme render array.    │
│ RENDER   │  La página usa hook_preprocess directo (Zero Region Pattern). │
│ ARRAY    │  Variables van de preprocess → template directamente.        │
├──────────┼───────────────────────────────────────────────────────────────┤
│  L3      │  hook_preprocess_page__user() declara las variables:         │
│ THEME    │  $variables['profile_setup_wizard'], etc.                    │
│ DECLARE  │  (no hay hook_theme() separado — usa template suggestion     │
│          │  page__user que hereda de page)                               │
├──────────┼───────────────────────────────────────────────────────────────┤
│  L4      │  page--user.html.twig:                                       │
│ TEMPLATE │    {% include '_setup-wizard.html.twig' with {               │
│          │      wizard: profile_setup_wizard, ... } only %}              │
│          │    {% include '_daily-actions.html.twig' with {               │
│          │      daily_actions: profile_daily_actions, ... } only %}      │
└──────────┴───────────────────────────────────────────────────────────────┘
```

**Nota sobre L2/L3:** La página de perfil NO pasa por controller propio — usa la ruta core de Drupal `entity.user.canonical` con template suggestion `page--user`. El preprocess inyecta variables directamente en `$variables`, que Twig consume sin necesidad de `hook_theme()` adicional. Esto es correcto y coherente con el Zero Region Pattern donde `page--user.html.twig` es el page template (no un theme hook custom).

### 3.4 Decisiones de UX

#### 3.4.1 Orden visual del perfil

```
┌─────────────────────────────────────────────────┐
│                  HERO CARD                       │
│  Avatar + Nombre + Email + Roles + "Editar"     │
├─────────────────────────────────────────────────┤
│          SETUP WIZARD (si aplica)                │
│  Ring % + Stepper horizontal premium             │
│  Colapsado si 100% completo                      │
│  "Ir a mi panel" CTA al dashboard vertical       │
├─────────────────────────────────────────────────┤
│          DAILY ACTIONS (si aplica)               │
│  Grid 2-4 cols de action cards con badges        │
│  Badge dinámico: "3 pedidos", "2 borradores"     │
├─────────────────────────────────────────────────┤
│          ACCOUNT INFO                            │
│  Email + Roles + Último acceso (3 cards)         │
├─────────────────────────────────────────────────┤
│          QUICK ACCESS SECTIONS                   │
│  Secciones extensibles via registry              │
│  (Professional Profile, My Vertical, etc.)       │
└─────────────────────────────────────────────────┘
```

**Justificación del orden:**
- **Wizard primero:** El onboarding es la prioridad para usuarios nuevos. Efecto Zeigarnik + CTA inmediato
- **Daily Actions segundo:** Para usuarios que ya completaron el wizard, las acciones diarias son lo primero relevante que ven
- **Account Info tercero:** Información estática, útil pero no urgente — desplazada por contenido dinámico
- **Quick Sections al final:** Links de navegación profunda — no compiten con el wizard/actions por atención

#### 3.4.2 Comportamiento cuando wizard 100% completado

**Opción elegida: Colapso inteligente con CTA al dashboard**

- Wizard completado → se muestra la barra colapsada (verde, check, "100%") con botón "Revisar configuración"
- Adicionalmente, se muestra un **CTA prominente** "Ir a mi panel de {Vertical}" que enlaza al dashboard vertical correspondiente
- Esto mantiene coherencia con el comportamiento existente de `_setup-wizard.html.twig` (que ya implementa auto-collapse)

#### 3.4.3 Avatar no detectado (general/anonymous)

- **No se renderiza** ni wizard ni daily actions
- El perfil se muestra exactamente como ahora: hero + info + quick sections
- Degradación grácil — cero impacto en usuarios sin avatar detectado

#### 3.4.4 CTA "Ir a mi panel" — Acceso al dashboard vertical

Cuando el bridge resuelve un `dashboardRoute`, se renderiza un botón contextual:

```html
<a href="{{ path(profile_dashboard_route) }}" class="profile-hub__dashboard-cta">
  {% trans %}Ir a mi panel{% endtrans %}
  <span class="profile-hub__cta-arrow">→</span>
</a>
```

Se ubica justo debajo del wizard (si existe) o como primer elemento del bloque de daily actions. Usa el color de vertical resuelto por `AvatarDetectionService`.

---

## 4. Especificaciones Técnicas Detalladas

### 4.1 AvatarWizardBridgeService.php

**Ubicación:** `web/modules/custom/ecosistema_jaraba_core/src/Service/AvatarWizardBridgeService.php`

**Responsabilidad:** Mapear el avatar detectado al wizard_id, dashboard_id y contextId correctos. NO ejecuta queries ni renderiza — solo resuelve IDs.

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping;

/**
 * Bridge entre AvatarDetection y los registries de Wizard/DailyActions.
 *
 * Resuelve qué wizard_id y dashboard_id corresponden al avatar del usuario
 * actual, así como el contextId necesario para evaluar completitud.
 *
 * SETUP-WIZARD-DAILY-001 + ZEIGARNIK-PRELOAD-001
 * OPTIONAL-CROSSMODULE-001: Dependencias opcionales con @?
 */
class AvatarWizardBridgeService {

  /**
   * Mapping central Avatar → Wizard/Dashboard/Context.
   *
   * Estructura: avatarType => [wizard_id, dashboard_id, scope, dashboard_route]
   * - scope: 'user' (contextId = uid) | 'tenant' (contextId = tenantId)
   * - wizard_id: NULL si el avatar no tiene wizard steps
   * - dashboard_route: ruta Drupal del dashboard vertical
   */
  protected const AVATAR_MAPPING = [
    'jobseeker' => [
      'wizard_id' => 'candidato_empleo',
      'dashboard_id' => 'candidato_empleo',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_candidate.dashboard',
    ],
    'entrepreneur' => [
      'wizard_id' => 'entrepreneur_tools',
      'dashboard_id' => 'entrepreneur_tools',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_business_tools.entrepreneur_dashboard',
    ],
    'producer' => [
      'wizard_id' => 'producer_agro',
      'dashboard_id' => 'producer_agro',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_agroconecta.producer_portal',
    ],
    'merchant' => [
      'wizard_id' => 'merchant_comercio',
      'dashboard_id' => 'merchant_comercio',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_comercio_conecta.merchant_portal',
    ],
    'service_provider' => [
      'wizard_id' => 'provider_servicios',
      'dashboard_id' => 'provider_servicios',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_servicios_conecta.provider_portal',
    ],
    'profesional' => [
      'wizard_id' => 'provider_servicios',
      'dashboard_id' => 'provider_servicios',
      'scope' => 'tenant',
      'dashboard_route' => 'jaraba_servicios_conecta.provider_portal',
    ],
    'student' => [
      'wizard_id' => 'learner_lms',
      'dashboard_id' => 'learner_lms',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_lms.learner_dashboard',
    ],
    'mentor' => [
      'wizard_id' => 'mentor',
      'dashboard_id' => 'mentor',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_mentoring.mentor_dashboard',
    ],
    'legal_professional' => [
      'wizard_id' => 'legal_professional',
      'dashboard_id' => 'legal_professional',
      'scope' => 'user',
      'dashboard_route' => 'jaraba_legal_intelligence.dashboard',
    ],
  ];

  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected ?AvatarDetectionService $avatarDetection = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * Resuelve el mapping para el usuario actual.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping|null
   *   NULL si: no hay avatar detection, avatar no detectado, o avatar sin mapping.
   */
  public function resolveForCurrentUser(): ?AvatarWizardMapping {
    if (!$this->avatarDetection || !$this->currentUser->isAuthenticated()) {
      return NULL;
    }

    $result = $this->avatarDetection->detect();
    if (!$result->isDetected()) {
      return NULL;
    }

    $mapping = self::AVATAR_MAPPING[$result->avatarType] ?? NULL;
    if (!$mapping) {
      return NULL;
    }

    // Resolver contextId según scope.
    $contextId = $this->resolveContextId($mapping['scope']);

    return new AvatarWizardMapping(
      wizardId: $mapping['wizard_id'],
      dashboardId: $mapping['dashboard_id'],
      contextId: $contextId,
      avatarType: $result->avatarType,
      vertical: $result->vertical,
      dashboardRoute: $mapping['dashboard_route'],
    );
  }

  /**
   * Resuelve contextId: uid para user-scoped, tenantId para tenant-scoped.
   */
  protected function resolveContextId(string $scope): int {
    if ($scope === 'tenant' && $this->tenantContext) {
      $tenantId = $this->tenantContext->getCurrentTenantId();
      if ($tenantId > 0) {
        return $tenantId;
      }
    }
    // Fallback para user-scoped o tenant no disponible.
    return (int) $this->currentUser->id();
  }

}
```

**Notas de diseño:**
- Const `AVATAR_MAPPING` centraliza todo el conocimiento que hoy está disperso en 13 controllers
- `resolveContextId()` sigue la regla SETUP-WIZARD-DAILY-001 de user-scoped vs tenant-scoped
- NO depende de los registries (SRP) — solo resuelve IDs
- Dependencias opcionales (`@?`) para `AvatarDetectionService` y `TenantContextService` (OPTIONAL-CROSSMODULE-001)

### 4.1.1 Value Object: AvatarWizardMapping

**Ubicación:** `web/modules/custom/ecosistema_jaraba_core/src/ValueObject/AvatarWizardMapping.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\ValueObject;

/**
 * Immutable result of AvatarWizardBridgeService::resolveForCurrentUser().
 *
 * Contiene los identificadores necesarios para que el preprocess del perfil
 * pueda consultar SetupWizardRegistry y DailyActionsRegistry.
 */
final class AvatarWizardMapping {

  public function __construct(
    public readonly ?string $wizardId,
    public readonly ?string $dashboardId,
    public readonly int $contextId,
    public readonly string $avatarType,
    public readonly ?string $vertical,
    public readonly ?string $dashboardRoute,
  ) {}

  /**
   * Indica si hay wizard disponible para este mapping.
   */
  public function hasWizard(): bool {
    return $this->wizardId !== NULL;
  }

  /**
   * Indica si hay daily actions disponibles para este mapping.
   */
  public function hasDailyActions(): bool {
    return $this->dashboardId !== NULL;
  }

}
```

### 4.2 Registro en services.yml

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml`

**Adición:**

```yaml
ecosistema_jaraba_core.avatar_wizard_bridge:
  class: Drupal\ecosistema_jaraba_core\Service\AvatarWizardBridgeService
  arguments:
    - '@current_user'
    - '@?ecosistema_jaraba_core.avatar_detection'
    - '@?ecosistema_jaraba_core.tenant_context'
```

**Cumplimiento de directrices:**
- `@?` para servicios opcionales cross-módulo (OPTIONAL-CROSSMODULE-001)
- `@current_user` es core, permite `@` directo
- Constructor params coinciden exactamente con args (PHANTOM-ARG-001)
- Sin dependencia circular (CONTAINER-DEPS-002) — bridge no requiere registries, solo avatar_detection

### 4.3 Preprocess: inyección de variables

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme`
**Función:** `ecosistema_jaraba_theme_preprocess_page__user()`
**Ubicación:** Después del bloque de Quick Access Sections (línea ~3639), dentro del `if ($page_type === 'profile' ...)`

**Código a añadir:**

```php
// -- Setup Wizard + Daily Actions (via AvatarWizardBridgeService) ----------
// Inyecta el wizard de onboarding y las acciones diarias del avatar
// detectado, reutilizando los partials _setup-wizard.html.twig y
// _daily-actions.html.twig probados en los dashboards verticales.
$variables['profile_setup_wizard'] = NULL;
$variables['profile_daily_actions'] = [];
$variables['profile_wizard_title'] = '';
$variables['profile_wizard_subtitle'] = '';
$variables['profile_actions_title'] = '';
$variables['profile_dashboard_route'] = NULL;
$variables['profile_avatar_type'] = NULL;

if (\Drupal::hasService('ecosistema_jaraba_core.avatar_wizard_bridge')) {
  try {
    /** @var \Drupal\ecosistema_jaraba_core\Service\AvatarWizardBridgeService $bridge */
    $bridge = \Drupal::service('ecosistema_jaraba_core.avatar_wizard_bridge');
    $mapping = $bridge->resolveForCurrentUser();

    if ($mapping) {
      $variables['profile_avatar_type'] = $mapping->avatarType;
      $variables['profile_dashboard_route'] = $mapping->dashboardRoute;

      // Setup Wizard.
      if ($mapping->hasWizard()
          && \Drupal::hasService('ecosistema_jaraba_core.setup_wizard_registry')) {
        /** @var \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry $wizardRegistry */
        $wizardRegistry = \Drupal::service('ecosistema_jaraba_core.setup_wizard_registry');
        if ($wizardRegistry->hasWizard($mapping->wizardId)) {
          $variables['profile_setup_wizard'] = $wizardRegistry->getStepsForWizard(
            $mapping->wizardId,
            $mapping->contextId,
          );
        }
      }

      // Daily Actions.
      if ($mapping->hasDailyActions()
          && \Drupal::hasService('ecosistema_jaraba_core.daily_actions_registry')) {
        /** @var \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry $actionsRegistry */
        $actionsRegistry = \Drupal::service('ecosistema_jaraba_core.daily_actions_registry');
        if ($actionsRegistry->hasDashboard($mapping->dashboardId)) {
          $variables['profile_daily_actions'] = $actionsRegistry->getActionsForDashboard(
            $mapping->dashboardId,
            $mapping->contextId,
          );
        }
      }

      // Títulos traducibles contextuales al vertical.
      $vertical_label = $mapping->vertical
        ? ucfirst(str_replace('_', ' ', $mapping->vertical))
        : '';
      $variables['profile_wizard_title'] = t('Primeros pasos');
      $variables['profile_wizard_subtitle'] = $vertical_label
        ? t('Configura tu espacio en @vertical', ['@vertical' => $vertical_label])
        : t('Configura tu espacio');
      $variables['profile_actions_title'] = t('Acciones de hoy');
    }
  }
  catch (\Throwable $e) {
    \Drupal::logger('ecosistema_jaraba_theme')->error(
      'Error loading wizard/actions for profile: @error',
      ['@error' => $e->getMessage()],
    );
  }
}
```

**Cumplimiento:**
- `\Drupal::hasService()` antes de cada servicio (PRESAVE-RESILIENCE-001)
- `try-catch (\Throwable)` envuelve todo (UPDATE-HOOK-CATCH-001)
- `t()` para todos los strings visibles (i18n)
- Variables inicializadas a valores safe antes del if (evita undefined en template)
- Registries consultados solo si el mapping tiene wizard/dashboard (performance)

### 4.4 Template: page--user.html.twig

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--user.html.twig`

**Cambio:** Insertar el bloque de wizard + daily actions **entre el hero card y el account info cards**, dentro del `{% if user_page_type == 'profile' %}`.

**Código nuevo (después de `</div>` del `.profile-hero` y antes de `.account-info`):**

```twig
{# ── Onboarding Hub: Setup Wizard + Daily Actions ─────────────── #}
{# Resuelto via AvatarWizardBridgeService según avatar del usuario. #}
{# Reutiliza los partials probados de los dashboards verticales.    #}
{% if profile_setup_wizard or profile_daily_actions is not empty %}
  <div class="profile-hub" data-avatar="{{ profile_avatar_type }}">

    {# Setup Wizard — Onboarding personalizado al avatar #}
    {% if profile_setup_wizard %}
      {% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
        wizard: profile_setup_wizard,
        wizard_title: profile_wizard_title,
        wizard_subtitle: profile_wizard_subtitle,
        collapsed_label: 'Configuración completada'|t,
      } only %}
    {% endif %}

    {# CTA al dashboard vertical #}
    {% if profile_dashboard_route %}
      <div class="profile-hub__dashboard-link">
        <a href="{{ path(profile_dashboard_route) }}" class="profile-hub__dashboard-cta">
          {{ jaraba_icon('verticals', 'rocket', { variant: 'duotone', color: 'naranja-impulso', size: '18px' }) }}
          {% trans %}Ir a mi panel{% endtrans %}
          <span class="profile-hub__cta-arrow" aria-hidden="true">&rarr;</span>
        </a>
      </div>
    {% endif %}

    {# Daily Actions — Acciones vivas con badges de estado #}
    {% if profile_daily_actions is not empty %}
      {% include '@ecosistema_jaraba_theme/partials/_daily-actions.html.twig' with {
        daily_actions: profile_daily_actions,
        actions_title: profile_actions_title,
      } only %}
    {% endif %}

  </div>
{% endif %}
```

**Cumplimiento:**
- `{% include ... only %}` (TWIG-INCLUDE-ONLY-001)
- `path()` para URLs (ROUTE-LANGPREFIX-001)
- `jaraba_icon()` con duotone + paleta Jaraba (ICON-DUOTONE-001, ICON-COLOR-001)
- `{% trans %}` para todos los textos visibles (i18n)
- `|t` filter para string literal inline (TranslatableMarkup)
- Wrapper `profile-hub` con `data-avatar` para personalización CSS condicional
- Degradación grácil: si no hay wizard ni actions, el `<div>` no se renderiza

### 4.5 SCSS: adaptaciones visuales

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/scss/_user-pages.scss`

**Adición:** Sección `.profile-hub` para el contenedor y CTA al dashboard.

```scss
// ==========================================================================
// PROFILE HUB — Wizard + Daily Actions en perfil de usuario
// SETUP-WIZARD-DAILY-001: reutiliza los estilos existentes de
// .setup-wizard y .daily-actions sin duplicar.
// Solo define el wrapper y el CTA al dashboard vertical.
// ==========================================================================

.profile-hub {
  display: flex;
  flex-direction: column;
  gap: var(--ej-spacing-lg, 1.5rem);
  margin-top: var(--ej-spacing-xl, 2rem);
  animation: fadeInUp 0.5s ease-out both;
  animation-delay: 0.2s;

  // Setup wizard adaptación: ancho completo en perfil
  .setup-wizard {
    border-radius: var(--ej-radius-xl, 16px);
  }

  // Daily actions adaptación: limitar a 3 columnas en perfil
  .daily-actions__grid {
    @include respond-to('desktop') {
      grid-template-columns: repeat(3, 1fr);
    }
  }
}

.profile-hub__dashboard-link {
  display: flex;
  justify-content: center;
  padding: var(--ej-spacing-xs, 0.25rem) 0;
}

.profile-hub__dashboard-cta {
  display: inline-flex;
  align-items: center;
  gap: var(--ej-spacing-xs, 0.5rem);
  padding: var(--ej-spacing-sm, 0.75rem) var(--ej-spacing-lg, 1.5rem);
  color: var(--ej-color-primary, #FF8C42);
  font-weight: 600;
  font-size: var(--ej-font-size-sm, 0.875rem);
  text-decoration: none;
  border: 2px solid var(--ej-color-primary, #FF8C42);
  border-radius: var(--ej-radius-full, 999px);
  transition: all 0.2s ease;

  &:hover,
  &:focus-visible {
    background: var(--ej-color-primary, #FF8C42);
    color: var(--ej-color-on-primary, #fff);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px color-mix(in srgb, var(--ej-color-primary, #FF8C42) 30%, transparent);
  }

  &:focus-visible {
    outline: 2px solid var(--ej-color-primary, #FF8C42);
    outline-offset: 2px;
  }
}

.profile-hub__cta-arrow {
  font-size: 1.1em;
  transition: transform 0.2s ease;

  .profile-hub__dashboard-cta:hover & {
    transform: translateX(3px);
  }
}
```

**Cumplimiento:**
- `var(--ej-*)` para TODOS los colores, spacing, radios (CSS-VAR-ALL-COLORS-001)
- `color-mix()` en lugar de `rgba()` para alpha (SCSS-COLORMIX-001)
- `@include respond-to()` para breakpoints (mixin del tema)
- `focus-visible` para accesibilidad (WCAG 2.1 AA)
- NO duplica estilos de `.setup-wizard` ni `.daily-actions` — solo define el wrapper

**Compilación obligatoria:**

```bash
# Dentro del contenedor Lando:
cd web/themes/custom/ecosistema_jaraba_theme && npm run build
```

Verificar que timestamp CSS > SCSS (SCSS-COMPILE-VERIFY-001).

### 4.6 JS: behavior de refresh y transiciones

**NO se necesita nuevo JavaScript.** Los partials `_setup-wizard.html.twig` y `_daily-actions.html.twig` ya incluyen su propio JS via las libraries de los dashboards verticales:

- `ecosistema_jaraba_theme/setup-wizard` (library existente)
- `ecosistema_jaraba_theme/daily-actions` (library existente)

Estas libraries se adjuntan automáticamente porque los partials usan clases CSS que los behaviors buscan (`[data-setup-wizard]`, `.daily-actions`).

**Sin embargo**, debemos asegurar que las libraries se attachen en el preprocess:

```php
// En el preprocess, dentro del bloque donde se inyectan wizard/actions:
if ($variables['profile_setup_wizard'] || !empty($variables['profile_daily_actions'])) {
  $variables['#attached']['library'][] = 'ecosistema_jaraba_theme/setup-wizard';
}
```

Si la library `setup-wizard` no existe aún como library separada (los behaviors pueden estar inlined en el JS global del tema), verificar si los `data-wizard-toggle` y `data-wizard-panel` requieren JS adicional. El JS del wizard (toggle collapse/expand) está en `js/setup-wizard.js` como behavior `Drupal.behaviors.setupWizard`.

---

## 5. Inventario de Archivos

### 5.1 Archivos a crear

| # | Archivo | Descripción | Líneas est. |
|---|---------|-------------|-------------|
| 1 | `ecosistema_jaraba_core/src/Service/AvatarWizardBridgeService.php` | Servicio bridge: mapea avatar → wizard_id/dashboard_id/contextId | ~120 |
| 2 | `ecosistema_jaraba_core/src/ValueObject/AvatarWizardMapping.php` | Value object inmutable con resultado del mapping | ~40 |

### 5.2 Archivos a modificar

| # | Archivo | Cambio | Líneas afectadas |
|---|---------|--------|-----------------|
| 1 | `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Registrar `avatar_wizard_bridge` service | +5 líneas |
| 2 | `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | Inyectar wizard/actions en `preprocess_page__user()` | +50 líneas (~3640-3690) |
| 3 | `ecosistema_jaraba_theme/templates/page--user.html.twig` | Insertar bloque hub (wizard+CTA+actions) entre hero y account-info | +25 líneas (~111-135) |
| 4 | `ecosistema_jaraba_theme/scss/_user-pages.scss` | Estilos `.profile-hub` + `.profile-hub__dashboard-cta` | +60 líneas |

### 5.3 Archivos reutilizados (sin cambios)

| Archivo | Rol |
|---------|-----|
| `ecosistema_jaraba_theme/templates/partials/_setup-wizard.html.twig` | Partial del wizard (203 líneas) — reutilizado tal cual |
| `ecosistema_jaraba_theme/templates/partials/_daily-actions.html.twig` | Partial de daily actions (88 líneas) — reutilizado tal cual |
| `ecosistema_jaraba_core/src/SetupWizard/SetupWizardRegistry.php` | Registry del wizard — API existente |
| `ecosistema_jaraba_core/src/DailyActions/DailyActionsRegistry.php` | Registry de daily actions — API existente |
| `ecosistema_jaraba_core/src/Service/AvatarDetectionService.php` | Detección de avatar — API existente |
| `ecosistema_jaraba_core/src/Service/TenantContextService.php` | Contexto de tenant — API existente |
| `ecosistema_jaraba_core/src/SetupWizard/AutoCompleteAccountStep.php` | ZEIGARNIK step global — automático |
| `ecosistema_jaraba_core/src/SetupWizard/AutoCompleteVerticalStep.php` | ZEIGARNIK step global — automático |
| 50 SetupWizardStep classes | Steps de wizard en 10 verticales |
| 54 DailyAction classes | Actions en 10 verticales |

---

## 6. Tabla de Correspondencia con Directrices

| # | Directriz | Aplicación | Verificación |
|---|----------|-----------|-------------|
| 1 | **SETUP-WIZARD-DAILY-001** | Reutiliza patrón completo: registries, partials, ZEIGARNIK. Wizard + Daily Actions en perfil | Template usa partials existentes |
| 2 | **ZEIGARNIK-PRELOAD-001** | Los 2 global steps (`__global__`) se inyectan automáticamente por `SetupWizardRegistry::getStepsForWizard()` | Verificar 33-50% en primer render |
| 3 | **PIPELINE-E2E-001** | L1: preprocess inyecta → L3: variables declaradas → L4: template consume | 4 capas verificadas |
| 4 | **ZERO-REGION-001** | Variables via `hook_preprocess_page()`. Template consume `profile_setup_wizard` etc. | Sin regiones Drupal |
| 5 | **CSS-VAR-ALL-COLORS-001** | TODOS los colores en `.profile-hub` usan `var(--ej-*)` con fallback | Grep por hex sin var() |
| 6 | **ICON-CONVENTION-001** | `jaraba_icon()` para CTA "Ir a mi panel" | Duotone, paleta Jaraba |
| 7 | **ICON-DUOTONE-001** | Variante `duotone` por defecto en CTA icon | Verificar en template |
| 8 | **ICON-COLOR-001** | Solo colores de paleta: `naranja-impulso` para CTA | Sin hex hardcoded |
| 9 | **ROUTE-LANGPREFIX-001** | `path(profile_dashboard_route)` en Twig, `Url::fromRoute()` sería alternativa | Sin URLs hardcoded |
| 10 | **TWIG-INCLUDE-ONLY-001** | `{% include ... only %}` en ambos partials | Keyword `only` presente |
| 11 | **OPTIONAL-CROSSMODULE-001** | `@?ecosistema_jaraba_core.avatar_detection`, `@?ecosistema_jaraba_core.tenant_context` | services.yml verificado |
| 12 | **PHANTOM-ARG-001** | 3 args en services.yml = 3 params en constructor PHP | Coincidencia exacta |
| 13 | **CONTAINER-DEPS-002** | Bridge → avatar_detection (unidireccional). Sin ciclos | `validate-circular-deps.php` |
| 14 | **TENANT-001** | `contextId` resuelto via `TenantContextService::getCurrentTenantId()` para tenant-scoped | Filtrado por tenant |
| 15 | **PRESAVE-RESILIENCE-001** | `\Drupal::hasService()` antes de cada servicio en preprocess | Patrón aplicado |
| 16 | **UPDATE-HOOK-CATCH-001** | `catch (\Throwable)` en preprocess | No `\Exception` |
| 17 | **SCSS-COMPILE-VERIFY-001** | `npm run build` tras editar SCSS. Verificar timestamps | Script post-build |
| 18 | **SCSS-COLORMIX-001** | `color-mix(in srgb, ...)` en lugar de `rgba()` | En `.profile-hub__dashboard-cta:hover` |
| 19 | **SCSS-001** | `@use '../variables' as *;` en parcial si se crea archivo SCSS separado | Integrado en `_user-pages.scss` |
| 20 | **PREMIUM-FORMS-PATTERN-001** | No aplica — no se crean formularios nuevos | N/A |
| 21 | **UPDATE-HOOK-REQUIRED-001** | No aplica — no se crean entidades ni campos nuevos | N/A |
| 22 | **OBSERVER-SCROLL-ROOT-001** | El wizard collapse/expand puede estar en slide-panel. IntersectionObserver usa `.slide-panel__body` como root | Verificar JS existente |
| 23 | **WCAG 2.1 AA** | `aria-label` en wrapper hub, `focus-visible` en CTA, `role` en wizard stepper | Heredado de partials |
| 24 | **i18n** | `{% trans %}`, `|t`, `t()` en preprocess. Todos los strings traducibles | 0 strings hardcoded |
| 25 | **NO-HARDCODE-PRICE-001** | No aplica — no hay precios en este cambio | N/A |
| 26 | **DOC-GUARD-001** | Este plan en `docs/tareas/`, NO en master docs. Master docs se actualizan en commit separado | Commit scope respetado |

---

## 7. Plan de Ejecución

### 7.1 Sprint 1: Servicio bridge + preprocess + template

**Estimación:** ~2-3 horas de implementación

| # | Tarea | Archivo(s) | Directrices |
|---|-------|-----------|-------------|
| 1.1 | Crear `AvatarWizardMapping` value object | `ValueObject/AvatarWizardMapping.php` | Inmutable, `readonly` |
| 1.2 | Crear `AvatarWizardBridgeService` | `Service/AvatarWizardBridgeService.php` | OPTIONAL-CROSSMODULE-001, PHANTOM-ARG-001 |
| 1.3 | Registrar servicio en services.yml | `ecosistema_jaraba_core.services.yml` | `@?` para opcionales |
| 1.4 | Añadir bloque de preprocess | `ecosistema_jaraba_theme.theme` | PRESAVE-RESILIENCE-001, i18n |
| 1.5 | Insertar bloque hub en template | `page--user.html.twig` | TWIG-INCLUDE-ONLY-001, ROUTE-LANGPREFIX-001 |
| 1.6 | Verificar que `\Drupal::hasService()` protege degradación | Manual en Lando | Acceder `/user/{uid}` |

**Criterio de completitud:** El perfil muestra wizard + daily actions para un usuario con avatar detectado. Para usuarios sin avatar, el perfil se muestra sin cambios.

### 7.2 Sprint 2: SCSS + JS + polish UX

**Estimación:** ~1-2 horas

| # | Tarea | Archivo(s) | Directrices |
|---|-------|-----------|-------------|
| 2.1 | Añadir estilos `.profile-hub` | `scss/_user-pages.scss` | CSS-VAR-ALL-COLORS-001, SCSS-COLORMIX-001 |
| 2.2 | Compilar SCSS → CSS | `npm run build` en contenedor Lando | SCSS-COMPILE-VERIFY-001 |
| 2.3 | Verificar library attachment del wizard JS | `ecosistema_jaraba_theme.theme` | `#attached` en preprocess |
| 2.4 | Verificar responsive (mobile-first) | Navegador en 375px, 768px, 1200px | WCAG, mobile-first |
| 2.5 | Verificar animaciones escalonadas | Navegador → Setup Wizard stepper | `fadeInUp`, stagger delays |
| 2.6 | Verificar collapse/expand del wizard completado | Navegador → wizard 100% | `data-wizard-toggle` |

**Criterio de completitud:** Visual premium en mobile y desktop. Animaciones suaves. CTA "Ir a mi panel" funcional.

### 7.3 Sprint 3: Tests + verificación runtime

**Estimación:** ~2 horas

| # | Tarea | Archivo(s) | Directrices |
|---|-------|-----------|-------------|
| 3.1 | Test unitario: `AvatarWizardBridgeServiceTest` | `tests/src/Unit/Service/` | MOCK-DYNPROP-001 |
| 3.2 | Test unitario: `AvatarWizardMappingTest` | `tests/src/Unit/ValueObject/` | Value object tests |
| 3.3 | Verificar RUNTIME-VERIFY-001 (5 checks) | Manual | CSS, DB, routes, selectors, drupalSettings |
| 3.4 | Verificar PIPELINE-E2E-001 (4 capas) | Manual | L1-L4 |
| 3.5 | Ejecutar `validate-all.sh` | Scripts de validación | Safeguard Layer 1 |
| 3.6 | Verificar con validate-optional-deps.php | Script validación | OPTIONAL-CROSSMODULE-001 |
| 3.7 | Verificar con validate-circular-deps.php | Script validación | CONTAINER-DEPS-002 |

---

## 8. Testing y Verificación

### 8.1 Tests unitarios

**Test 1: `AvatarWizardBridgeServiceTest`**

```
Ubicación: web/modules/custom/ecosistema_jaraba_core/tests/src/Unit/Service/AvatarWizardBridgeServiceTest.php
```

Casos de test:
1. **Usuario autenticado con avatar detectado** → Devuelve mapping correcto
2. **Avatar sin mapping** (ej: `recruiter`) → Devuelve NULL
3. **Avatar `general`** → Devuelve NULL (no detectado)
4. **Usuario no autenticado** → Devuelve NULL
5. **Sin AvatarDetectionService** (NULL) → Devuelve NULL (degradación grácil)
6. **User-scoped** (jobseeker) → contextId = uid
7. **Tenant-scoped** (merchant) → contextId = tenantId
8. **Tenant-scoped sin TenantContextService** → fallback a uid
9. **Todos los avatares en AVATAR_MAPPING** → Verificar wizard_id + dashboard_id correctos

**Test 2: `AvatarWizardMappingTest`**

Casos:
1. `hasWizard()` devuelve TRUE cuando wizardId != NULL
2. `hasWizard()` devuelve FALSE cuando wizardId == NULL
3. `hasDailyActions()` devuelve TRUE cuando dashboardId != NULL
4. Inmutabilidad del value object (readonly properties)

### 8.2 Tests kernel

**No se requieren tests kernel** para este cambio porque:
- No se crean entidades nuevas
- No se modifican esquemas de base de datos
- No se añaden campos
- Los registries ya tienen sus propios tests kernel

### 8.3 Verificación RUNTIME-VERIFY-001

| # | Check | Cómo verificar | Resultado esperado |
|---|-------|----------------|-------------------|
| 1 | **CSS compilado** | `stat --format=%Y css/_user-pages.css` vs `scss/_user-pages.scss` | CSS timestamp > SCSS |
| 2 | **Tablas DB** | N/A — no se crean tablas | — |
| 3 | **Rutas accesibles** | `lando drush router:debug entity.user.canonical` | Ruta existe y funcional |
| 4 | **data-* selectores** | Inspeccionar DOM: `[data-setup-wizard]`, `.daily-actions`, `[data-avatar]` | Presentes en HTML |
| 5 | **drupalSettings** | N/A — no se inyectan drupalSettings nuevos (datos van en Twig) | — |

### 8.4 Verificación PIPELINE-E2E-001

| Capa | Qué verificar | Cómo |
|------|--------------|------|
| L1 | `AvatarWizardBridgeService` resuelve mapping | `drush eval` con usuario mock |
| L2/L3 | Variables presentes en template | `dump()` en Twig con `twig_debug: true` |
| L4 | Wizard y daily actions visibles en DOM | Navegador → inspeccionar `.profile-hub` |

---

## 9. Criterios de Aceptación

### Funcionales

| # | Criterio | Verificación |
|---|---------|-------------|
| CA-1 | Un usuario con avatar `jobseeker` ve el Setup Wizard "Completa tu perfil profesional" en su perfil | Navegador: `/user/{uid}` |
| CA-2 | El wizard muestra progreso >= 33% por ZEIGARNIK-PRELOAD-001 (2 global steps completos) | Ring SVG muestra >= 33% |
| CA-3 | Un usuario `merchant` ve sus Daily Actions con badges de pedidos pendientes | Navegador: badge numérico visible |
| CA-4 | El CTA "Ir a mi panel" enlaza a la ruta correcta del dashboard vertical | Click → redirige al dashboard |
| CA-5 | Un usuario sin avatar detectado (`general`) NO ve wizard ni daily actions | Perfil idéntico al actual |
| CA-6 | Un usuario con wizard 100% ve la barra colapsada (verde) con opción de expandir | Click en "Revisar" → expande |
| CA-7 | Mobile (375px): wizard en stepper vertical, actions en 1 columna | Responsive verificado |
| CA-8 | Todos los textos visibles están en español ({% trans %}) | Inspeccionar texto → traducido |
| CA-9 | Todos los colores usan `var(--ej-*)`, zero hex hardcoded | Inspeccionar CSS computado |
| CA-10 | `validate-optional-deps.php` y `validate-circular-deps.php` pasan sin errores | Scripts ejecutados |

### No funcionales

| # | Criterio | Verificación |
|---|---------|-------------|
| NF-1 | Preprocess del perfil completa en < 100ms (wizard + actions = ~50ms máximo adicional) | Performance no degradada |
| NF-2 | Sin errores PHP con servicios opcionales no disponibles | Desactivar módulo de un vertical → perfil sigue funcionando |
| NF-3 | 0 regresiones en tests existentes | `phpunit --testsuite Unit,Kernel` |

---

## 10. Riesgos y Mitigaciones

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|--------|-------------|---------|-----------|
| R-1 | **Performance:** getContext() de daily actions lento en algún vertical | Media | Bajo | getContext() ya tiene requisito < 50ms. Bridge solo llama si hasDashboard(). Monitorizar |
| R-2 | **Cache:** Wizard data cached por page cache → badge desactualizado | Media | Medio | Las daily actions muestran data real via AJAX refresh (JS existente `setupWizardRefresh` behavior). Cache tags `user:{uid}` en template |
| R-3 | **Avatar detection incorrecta** en dominios multi-tenant | Baja | Medio | avatar_detection ya está probado en producción. AvatarWizardBridge hereda su robustez |
| R-4 | **Library JS del wizard no attached** → collapse/expand no funciona | Baja | Bajo | Verificar `#attached` en preprocess. Si no existe library separada, el JS está en global |
| R-5 | **Colisión de wizard_id cuando usuario tiene multiples roles** (ej: mentor + entrepreneur) | Baja | Bajo | AvatarDetection resuelve por prioridad (cascading). Solo un avatar = solo un wizard |

---

## 11. Apéndice: Inventario completo de Wizard IDs y Dashboard IDs

### Tabla de paridad Wizard ↔ Dashboard

| # | wizard_id | dashboard_id | Módulo fuente | # Steps | # Actions | Avatar(s) asociados |
|---|-----------|-------------|---------------|---------|-----------|-------------------|
| 1 | `candidato_empleo` | `candidato_empleo` | jaraba_candidate | 5 | 4 | jobseeker |
| 2 | `entrepreneur_tools` | `entrepreneur_tools` | jaraba_business_tools | 3 | 4 | entrepreneur |
| 3 | `merchant_comercio` | `merchant_comercio` | jaraba_comercio_conecta | 5 | 5 | merchant |
| 4 | `producer_agro` | `producer_agro` | jaraba_agroconecta_core | 5 | 4 | producer |
| 5 | `provider_servicios` | `provider_servicios` | jaraba_servicios_conecta | 4 | 4 | service_provider, profesional |
| 6 | `legal_professional` | `legal_professional` | jaraba_legal_intelligence | 3 | 4 | legal_professional |
| 7 | `learner_lms` | `learner_lms` | jaraba_lms | 3 | 4 | student |
| 8 | `instructor_lms` | `instructor_lms` | jaraba_lms | 3 | 4 | (via rol, no avatar) |
| 9 | `editor_content_hub` | `editor_content_hub` | jaraba_content_hub | 3 | 4 | (via rol, no avatar) |
| 10 | `mentor` | `mentor` | jaraba_mentoring | 3 | 4 | mentor |
| 11 | `coordinador_ei` | `coordinador_ei` | jaraba_andalucia_ei | 4 | 9 | (via rol coordinador_ei) |
| 12 | `orientador_ei` | `orientador_ei` | jaraba_andalucia_ei | 3 | 8 | (via rol orientador_ei) |
| 13 | `emprendedor` | (legacy) | jaraba_business_tools | 3 | — | (alias de entrepreneur_tools) |

### Avatares SIN mapping (degradación grácil)

| Avatar | Razón | Comportamiento en perfil |
|--------|-------|------------------------|
| `recruiter` | Dashboard propio sin wizard steps registrados | Perfil sin wizard/actions |
| `buyer` | Flujo simplificado B2B sin setup | Perfil sin wizard/actions |
| `tenant_admin` | Rol administrativo, no onboarding SaaS | Perfil sin wizard/actions |
| `admin` | Superadmin | Perfil sin wizard/actions |
| `general` | Avatar default, sin contexto vertical | Perfil sin wizard/actions |
| `anonymous` | No autenticado | No accede a `/user/{uid}` |

### Global Steps (inyectados en TODOS los wizards)

| Step ID | wizard_id | Label | Weight | Siempre completo |
|---------|-----------|-------|--------|-----------------|
| `__global__.cuenta_creada` | `__global__` | "Cuenta creada" | -20 | Sí |
| `__global__.vertical_configurado` | `__global__` | "Vertical configurada" | -10 | Sí |

---

*Fin del plan de implementación.*
*Próximo paso: Implementar Sprint 1 (servicio bridge + preprocess + template).*
