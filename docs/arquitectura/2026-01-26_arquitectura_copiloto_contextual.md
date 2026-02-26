# 📖 Arquitectura del Copiloto Contextual

**Fecha:** 2026-01-26  
**Versión:** 1.1.0 (Action Buttons + URL Suggestions)
**Estado:** Aprobada

---

## 📑 Tabla de Contenidos

1. [Visión General](#1-visión-general)
2. [Problema y Solución](#2-problema-y-solución)
3. [Arquitectura Propuesta](#3-arquitectura-propuesta)
4. [Componentes](#4-componentes)
5. [Detección de Contexto](#5-detección-de-contexto)
6. [Integración con Templates](#6-integración-con-templates)
7. [Migración desde Bloques](#7-migración-desde-bloques)

---

## 1. Visión General

El **Copiloto Contextual** es un FAB (Floating Action Button) de IA que aparece en todas las páginas de la plataforma, contextualizándose automáticamente según:

- **Avatar del usuario** (jobseeker, recruiter, entrepreneur, producer, mentor, admin)
- **Vertical activa** (empleabilidad, emprendimiento, comercio, instituciones)
- **Tenant asociado** (organización, plan contratado)
- **Ruta actual** (landing, dashboard, formulario, etc.)

---

## 2. Problema y Solución

### Problema Anterior

```
┌─────────────────────────────────────────────────────────────┐
│  ARQUITECTURA ANTERIOR (Bloques Drupal)                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │ FAB Bloque A │  │ FAB Bloque B │  │ FAB Bloque C │       │
│  │ recruiter    │  │ recruiter    │  │ general      │       │
│  │ Region: X    │  │ Region: Y    │  │ Region: Z    │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│                                                              │
│  ❌ Configuración dispersa en BD                            │
│  ❌ Contexto incorrecto (ej: "Selección" en emprendedor)    │
│  ❌ Inconsistente con patrón "páginas limpias"              │
│  ❌ Difícil de mantener y auditar                            │
└─────────────────────────────────────────────────────────────┘
```

### Solución: Include Twig Global

```
┌─────────────────────────────────────────────────────────────┐
│  ARQUITECTURA NUEVA (Include Twig Global)                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  html.html.twig                                              │
│  └── {% include '_copilot-fab.html.twig' %}                 │
│      │                                                       │
│      ▼                                                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │           CopilotContextService                       │   │
│  │                                                        │   │
│  │  getContext() ─► avatar, vertical, tenant, plan       │   │
│  │                                                        │   │
│  │  Detección automática:                                 │   │
│  │  1. Roles del usuario → avatar                         │   │
│  │  2. Tenant asociado → vertical, plan                   │   │
│  │  3. Ruta actual → contexto de página                   │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                              │
│  ✅ Un único punto de inclusión                             │
│  ✅ Detección 100% automática                               │
│  ✅ Consistente con "páginas limpias"                       │
│  ✅ Fácil de mantener y auditar                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Arquitectura Propuesta

```
┌─────────────────────────────────────────────────────────────┐
│                     FLUJO DE DATOS                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Request HTTP                                                │
│       │                                                      │
│       ▼                                                      │
│  ┌──────────────────────┐                                    │
│  │ theme_preprocess_html │  (hook en .theme)                │
│  └──────────┬───────────┘                                    │
│             │                                                │
│             ▼                                                │
│  ┌──────────────────────┐                                    │
│  │ CopilotContextService│  (DI: current_user, route_match) │
│  │   ->getContext()     │                                    │
│  └──────────┬───────────┘                                    │
│             │                                                │
│             ▼                                                │
│  [ copilot_context: {...} ]  ── Variable Twig ──►            │
│                                                              │
│  ┌──────────────────────┐                                    │
│  │    html.html.twig    │                                    │
│  │                       │                                    │
│  │  {% include           │                                    │
│  │    '_copilot-fab.html.twig'                               │
│  │    with {             │                                    │
│  │      context: copilot_context                             │
│  │    }                  │                                    │
│  │  %}                   │                                    │
│  └──────────────────────┘                                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. Componentes

### 4.1 CopilotContextService

**Ubicación:** `ecosistema_jaraba_core/src/Service/CopilotContextService.php`

**Responsabilidades:**
- Detectar avatar por roles del usuario logado
- Detectar tenant, vertical y plan asociados
- Detectar contexto por ruta actual
- Construir prompt de contexto para la IA

**Interface:**
```php
interface CopilotContextServiceInterface {
    public function getContext(): array;
    public function getAvatar(): string;
    public function buildContextPrompt(): string;
}
```

**Retorno de `getContext()`:**
```php
[
    'avatar' => 'entrepreneur',      // jobseeker|recruiter|entrepreneur|producer|mentor|admin|general
    'vertical' => 'emprendimiento',  // empleabilidad|emprendimiento|comercio|instituciones|null
    'plan' => 'Premium',             // nombre del plan contratado o null
    'tenant_id' => 123,              // ID del tenant o null
    'tenant_name' => 'Org XYZ',      // nombre del tenant o null
    'user_id' => 456,                // ID del usuario
    'user_name' => 'Juan',           // nombre para personalizar greeting
    'is_authenticated' => true,      // si está logado
    'current_route' => 'jaraba_copilot_v2.entrepreneur_dashboard',
]
```

### 4.2 Partial Twig

**Ubicación:** `ecosistema_jaraba_theme/templates/partials/_copilot-fab.html.twig`

**Variables recibidas:**
- `copilot_context`: array del servicio
- `copilot_preset`: configuración del avatar (greeting, actions, color)

### 4.3 Preprocess Hook

**Ubicación:** `ecosistema_jaraba_theme.theme`

```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
    $copilot_service = \Drupal::service('ecosistema_jaraba_core.copilot_context');
    $variables['copilot_context'] = $copilot_service->getContext();
    $variables['copilot_preset'] = $copilot_service->getAvatarPreset($variables['copilot_context']['avatar']);
}
```

---

## 5. Detección de Contexto

### 5.1 Prioridad de Detección

1. **Usuario autenticado con roles específicos** → avatar por rol
2. **Usuario con tenant asociado** → vertical/plan del tenant
3. **Ruta de dashboard específica** → avatar por ruta
4. **Ruta de landing** → vertical por URL
5. **Fallback** → `general`

### 5.2 Mapeo de Roles a Avatares

| Rol Drupal | Avatar |
|------------|--------|
| `candidate`, `candidato`, `jobseeker` | `jobseeker` |
| `employer`, `recruiter`, `empleador` | `recruiter` |
| `entrepreneur`, `emprendedor` | `entrepreneur` |
| `producer`, `productor`, `comercio` | `producer` |
| `mentor` | `mentor` |
| `tenant_admin`, `admin` | `admin` |

### 5.3 Mapeo de Rutas a Avatares

| Ruta | Avatar |
|------|--------|
| `jaraba_candidate.dashboard` | `jobseeker` |
| `jaraba_employer.dashboard` | `recruiter` |
| `jaraba_copilot_v2.entrepreneur_dashboard` | `entrepreneur` |
| `jaraba_business_tools.entrepreneur_dashboard` | `entrepreneur` |
| `ecosistema_jaraba_core.producer_dashboard` | `producer` |

---

## 6. Integración con Templates

### 6.1 html.html.twig

```twig
{# Al final del body, antes de cerrar #}
{% if copilot_context %}
  {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
    context: copilot_context,
    preset: copilot_preset,
  } only %}
{% endif %}
```

### 6.2 Exclusión de Páginas Admin

```php
// En preprocess, excluir rutas admin
if (\Drupal::service('router.admin_context')->isAdminRoute()) {
    $variables['copilot_context'] = NULL;
}
```

---

## 7. Migración desde Bloques

### 7.1 Bloques a Eliminar

Los siguientes bloques en la BD deben desactivarse/eliminarse:
- `landing_copilot_fab`
- `ai_agent_fab` (si existe con configuración manual)
- Cualquier bloque `contextual_copilot` con avatar_type manual

### 7.2 Rollback

Si es necesario revertir:
1. Eliminar el include en `html.html.twig`
2. Eliminar el preprocess hook
3. Reactivar bloques anteriores

---

## 8. Sugerencias y Action Buttons (v1.1.0)

### 8.1 Formato de Sugerencias

Las sugerencias del copilot soportan dos formatos:

**String plano** — se envia como mensaje al chat:
```json
"Ver demo: Buscar empleo con IA"
```

**Objeto con URL** — se renderiza como link directo:
```json
{"label": "Crear cuenta gratis", "url": "/user/register"}
```

### 8.2 Backend: Action Buttons Contextuales

`CopilotOrchestratorService::getContextualActionButtons(string $mode)` genera CTAs segun el modo y estado de autenticacion:

```
Usuario Anonimo → [{label: "Crear cuenta gratis", url: "/user/register"}]
Coach           → [{label: "Mi perfil", url: "/user"}]
Consultor       → [{label: "Mi dashboard", url: "/user"}]
CFO             → [{label: "Panel financiero", url: "/emprendimiento/dashboard"}]
Landing         → [{label: "Explorar plataforma", url: "/"}]
```

`formatResponse()` fusiona sugerencias extraidas del texto IA + action buttons contextuales.

### 8.3 Frontend: Renderizado Dual

Ambas implementaciones JS normalizan el formato:

```javascript
var item = typeof s === 'string' ? { label: s } : s;
if (item.url) {
    // <a class="suggestion-btn--link" href="...">Label →</a>
} else {
    // <button class="suggestion-btn">Label</button>
}
```

| Clase CSS | Estilo | Uso |
|-----------|--------|-----|
| `.suggestion-btn` | Outline, borde naranja | Sugerencia de texto |
| `.suggestion-btn--link` | Fondo naranja, blanco, bold | Link directo con URL |

Links externos (`http` + diferente hostname) llevan `target="_blank" rel="noopener noreferrer"`.

### 8.4 Implementaciones

| Modulo | Fichero JS | Fichero SCSS |
|--------|------------|-------------|
| ecosistema_jaraba_core (v1) | `contextual-copilot.js` | `_contextual-copilot.scss` |
| jaraba_copilot_v2 (v2) | `copilot-chat-widget.js` | `_copilot-chat-widget.scss` |

Regla COPILOT-LINK-001: Ambas implementaciones DEBEN soportar el formato dual.

---

## Referencias

- [CopilotContextService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Service/CopilotContextService.php)
- [ContextualCopilotBlock.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/src/Plugin/Block/ContextualCopilotBlock.php)
- [contextual-copilot-fab.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ecosistema_jaraba_core/templates/contextual-copilot-fab.html.twig)
