# Bloque G: AI Skills System - Plan de Implementación v2
## Sistema de Enseñanza y Especialización Continua de Agentes IA

**Fecha de creación:** 2026-01-23 20:50  
**Última actualización:** 2026-02-04 11:51  
**Autor:** IA Asistente (Claude)  
**Versión:** 2.0.0  
**Estado:** 📋 PLANIFICADO (0% implementado)

---

## 📑 Tabla de Contenidos (TOC)

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Directrices Obligatorias del Proyecto](#2-directrices-obligatorias-del-proyecto)
3. [Sprint G1: Módulo Base + Entidades](#3-sprint-g1-módulo-base--entidades)
4. [Sprint G2: SkillManager Service](#4-sprint-g2-skillmanager-service)
5. [Sprint G3: APIs REST + Frontend](#5-sprint-g3-apis-rest--frontend)
6. [Sprint G4: Skills Core Predefinidas](#6-sprint-g4-skills-core-predefinidas)
7. [Estilos SCSS](#7-estilos-scss)
8. [Checklist Pre-Commit](#8-checklist-pre-commit)
9. [Verificación](#9-verificación)
10. [Registro de Cambios](#10-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 Documento de Referencia Principal

| Doc | Archivo | Contenido Clave |
|-----|---------|-----------------|
| 129 | [20260118i1-129_Platform_AI_Skills_System_v1_Claude.md](../tecnicos/20260118i1-129_Platform_AI_Skills_System_v1_Claude.md) | Arquitectura completa 50KB |
| 129-Anexo | [20260118i2-129_Platform_AI_Skills_System_v1_AnexoA_Claude.md](../tecnicos/20260118i2-129_Platform_AI_Skills_System_v1_AnexoA_Claude.md) | Skills de ejemplo |

### 1.2 Propuesta de Valor

| Aspecto | RAG Tradicional | Skills System |
|---------|-----------------|---------------|
| Conocimiento | Información factual (QUÉ) | Procedimental (CÓMO) |
| Personalización | Por tenant (datos) | Tenant + Vertical + Agente + Tarea |
| Aprendizaje | Estático (indexación) | Dinámico (versiones, A/B) |
| Diferenciación | Commodity | **Ventaja competitiva única** |

### 1.3 🔄 Estrategia de Reuso

> ⚠️ **VERIFICACIÓN PREVIA**: Antes de cada paso, ejecutar análisis de reuso.

| Componente Reutilizable | Módulo Origen | Acción |
|-------------------------|---------------|--------|
| Claude API Client | `jaraba_copilot_v2` | Extender |
| Qdrant Integration | `jaraba_rag` | Referenciar |
| Design Tokens | `jaraba_theming` | Integrar |
| Slide-Panel | `ecosistema_jaraba_theme` | Dependencia |

### 1.4 Estimación de Inversión

| Sprint | Horas | Entregable |
|--------|-------|------------|
| G1 | 40h | Módulo + 2 entidades + navegación completa |
| G2 | 30h | SkillManager + resolución jerárquica |
| G3 | 20h | APIs REST + Frontend dashboard premium |
| G4 | 25h | 7 Skills Core predefinidas |
| **TOTAL** | **115h** | **MVP funcional** |

---

## 2. Directrices Obligatorias del Proyecto

> [!CAUTION]
> **TODAS estas directrices son de cumplimiento obligatorio.** El código que no las cumpla será rechazado.

### 2.1 Content Entity Navigation (workflow: `/drupal-custom-modules`)

| Ubicación | Ruta | Propósito |
|-----------|------|-----------|
| `/admin/content/ai-skills` | Listado de skills | Pestaña en Content (como Contenido/Bloques) |
| `/admin/structure/ai-skill` | Field UI | Administrar campos de la entidad |
| `/admin/config/jaraba/skills/settings` | Settings | Configuración del módulo |

**4 Archivos YAML Obligatorios:**
- [ ] `jaraba_skills.routing.yml` → URLs
- [ ] `jaraba_skills.links.menu.yml` → Menú en Structure
- [ ] `jaraba_skills.links.task.yml` → Pestaña en Content
- [ ] `jaraba_skills.links.action.yml` → Botón "Añadir"

### 2.2 SCSS + Variables Inyectables (workflow: `/scss-estilos`)

```scss
// ❌ PROHIBIDO
color: #233D63;
padding: 24px;

// ✅ OBLIGATORIO
color: var(--ej-color-corporate, #{$ej-color-corporate-fallback});
padding: var(--ej-spacing-lg, #{$ej-spacing-lg-fallback});
```

**Paleta Jaraba Obligatoria:**

| Variable | Hex | Uso Semántico |
|----------|-----|---------------|
| `corporate` | #233D63 | Base corporativa |
| `innovation` | #00A9A5 | Empleabilidad, IA |
| `impulse` | #FF8C42 | CTAs, acciones |
| `agro` | #556B2F | AgroConecta |

### 2.3 Iconografía SVG (workflow: `/scss-estilos`)

- [ ] Crear `ai/skill.svg` - Versión outline
- [ ] Crear `ai/skill-duotone.svg` - Versión duotone

**Uso en Twig:**
```twig
{{ jaraba_icon('ai', 'skill', { color: 'innovation', size: '24px' }) }}
{{ jaraba_icon('ai', 'skill', { variant: 'duotone', color: 'corporate' }) }}
```

### 2.4 i18n - Textos Traducibles (workflow: `/i18n-traducciones`)

```php
// ✅ En Controladores
$this->t('Habilidades IA')

// ✅ En Twig
{% trans %}Nueva Habilidad{% endtrans %}

// ✅ En JavaScript
Drupal.t('Habilidad guardada correctamente')
```

### 2.5 Frontend Page Pattern (workflow: `/frontend-page-pattern`)

**Template Twig limpio sin regiones Drupal:**
```twig
{# page--ai-skills.html.twig #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main id="main-content" class="skills-main">
  <div class="skills-wrapper">
    {{ page.content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

**Clases de body via hook (NO en template):**
```php
// ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  if (str_starts_with($route, 'jaraba_skills.')) {
    $variables['attributes']['class'][] = 'skills-page';
    $variables['attributes']['class'][] = 'page-skills';
  }
}
```

### 2.6 Slide-Panel para CRUD (workflow: `/slide-panel-modales`)

> [!IMPORTANT]
> **Todas las acciones de crear/editar/ver en frontend abren en modal slide-panel.**

```html
<button data-slide-panel="skill-form"
        data-slide-panel-url="/api/v1/skills/add"
        data-slide-panel-title="{% trans %}Nueva Habilidad{% endtrans %}">
  + {% trans %}Crear Habilidad{% endtrans %}
</button>
```

### 2.7 Layout Mobile-First + Full-Width

```scss
.skills-wrapper {
    max-width: 1400px;
    margin-inline: auto;
    padding: var(--ej-spacing-xl) var(--ej-spacing-lg);
    
    @media (max-width: 767px) {
        padding: var(--ej-spacing-lg) var(--ej-spacing-md);
    }
}
```

### 2.8 Tenant Isolation

- [ ] Tenant NO accede a `/admin/appearance`
- [ ] Tenant NO accede a tema de administración
- [ ] Tenant accede solo a `/skills` (frontend limpio)
- [ ] Admin SaaS accede a `/admin/content/ai-skills`

---

## 3. Sprint G1: Módulo Base + Entidades

### 3.1 Estructura del Módulo

```
web/modules/custom/jaraba_skills/
├── jaraba_skills.info.yml
├── jaraba_skills.module
├── jaraba_skills.services.yml
├── jaraba_skills.routing.yml           # ✅ Rutas
├── jaraba_skills.permissions.yml
├── jaraba_skills.links.menu.yml        # ✅ Menú Structure
├── jaraba_skills.links.task.yml        # ✅ Tab Content
├── jaraba_skills.links.action.yml      # ✅ Botón Add
├── src/
│   ├── Entity/
│   │   ├── AiSkill.php                 # ✅ field_ui_base_route
│   │   ├── AiSkillListBuilder.php
│   │   └── AiSkillUsage.php
│   ├── Service/
│   │   ├── SkillManager.php
│   │   └── SkillPromptBuilder.php
│   ├── ValueObject/
│   │   ├── SkillContext.php
│   │   └── ResolvedSkillSet.php
│   ├── Controller/
│   │   └── SkillApiController.php      # ✅ Detecta AJAX
│   └── Form/
│       ├── AiSkillForm.php
│       └── AiSkillSettingsForm.php     # ✅ Para Field UI
├── templates/
│   └── ai-skills-dashboard.html.twig   # ✅ Sin regiones
└── config/install/
    └── jaraba_skills.settings.yml
```

### 3.2 Entity: AiSkill con Field UI

```php
/**
 * @ContentEntityType(
 *   id = "ai_skill",
 *   label = @Translation("Habilidad IA"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_skills\AiSkillListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_skills\Form\AiSkillForm",
 *       "add" = "Drupal\jaraba_skills\Form\AiSkillForm",
 *       "edit" = "Drupal\jaraba_skills\Form\AiSkillForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_skills\AiSkillAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ai_skill",
 *   admin_permission = "administer ai skills",
 *   field_ui_base_route = "entity.ai_skill.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ai-skill/{ai_skill}",
 *     "add-form" = "/admin/content/ai-skills/add",
 *     "edit-form" = "/admin/content/ai-skill/{ai_skill}/edit",
 *     "delete-form" = "/admin/content/ai-skill/{ai_skill}/delete",
 *     "collection" = "/admin/content/ai-skills",
 *   },
 * )
 */
```

### 3.3 Campos de la Entidad

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | SERIAL | PRIMARY KEY |
| uuid | UUID | Identificador único |
| machine_name | VARCHAR(64) | Nombre máquina único por scope |
| label | VARCHAR(255) | Nombre legible |
| description | TEXT | Descripción |
| scope | list_string | core, vertical, agent, tenant |
| scope_id | VARCHAR(64) | ID del scope específico |
| tenant_id | entity_reference | FK groups.id (solo scope=tenant) |
| task_types | JSON | Array de task_type strings |
| content | text_long | Markdown de la skill (max 50KB) |
| priority | integer | Mayor = más prioridad |
| requires_skills | JSON | Dependencias |
| is_active | boolean | DEFAULT TRUE |
| is_locked | boolean | DEFAULT FALSE |
| created | created | Timestamp |
| changed | changed | Timestamp |
| user_id | entity_reference | Owner |

### 3.4 Navigation YAML Files

**jaraba_skills.links.menu.yml** (Structure):
```yaml
entity.ai_skill.settings:
  title: 'Habilidades IA'
  description: 'Administrar campos de Habilidades IA'
  route_name: entity.ai_skill.settings
  parent: system.admin_structure
  weight: 50
```

**jaraba_skills.links.task.yml** (Content tab):
```yaml
entity.ai_skill.collection:
  title: 'Habilidades IA'
  route_name: entity.ai_skill.collection
  base_route: system.admin_content
  weight: 25
```

**jaraba_skills.links.action.yml** (Add button):
```yaml
entity.ai_skill.add_form:
  title: 'Añadir Habilidad IA'
  route_name: entity.ai_skill.add_form
  appears_on:
    - entity.ai_skill.collection
```

### 3.5 Checklist Sprint G1

- [ ] Crear módulo `jaraba_skills.info.yml`
- [ ] Crear entidad `AiSkill.php` con annotation completa
- [ ] Crear `AiSkillListBuilder.php`
- [ ] Crear `AiSkillAccessControlHandler.php`
- [ ] Crear `AiSkillForm.php`
- [ ] Crear `AiSkillSettingsForm.php`
- [ ] Crear `jaraba_skills.routing.yml` con rutas settings
- [ ] Crear `jaraba_skills.links.menu.yml`
- [ ] Crear `jaraba_skills.links.task.yml`
- [ ] Crear `jaraba_skills.links.action.yml`
- [ ] Ejecutar `composer dump-autoload -o`
- [ ] Ejecutar `drush en jaraba_skills`
- [ ] Verificar pestaña en `/admin/content`
- [ ] Verificar Field UI en `/admin/structure/ai-skill`

---

## 4. Sprint G2: SkillManager Service

### 4.1 Interfaz del Servicio

```php
class SkillManager {
    /**
     * Resuelve skills aplicables para un contexto.
     * Pipeline: Core → Vertical → Agent → Tenant
     *
     * @param \Drupal\jaraba_skills\ValueObject\SkillContext $context
     *   Contexto de resolución.
     *
     * @return \Drupal\jaraba_skills\ValueObject\ResolvedSkillSet
     *   Conjunto de skills resueltas.
     */
    public function resolveSkills(SkillContext $context): ResolvedSkillSet;
    
    /**
     * Genera bloque <skills> para inyectar en prompt.
     *
     * @param \Drupal\jaraba_skills\ValueObject\ResolvedSkillSet $skillSet
     *   Conjunto de skills resueltas.
     *
     * @return string
     *   Bloque XML para el prompt.
     */
    public function generatePromptSection(ResolvedSkillSet $skillSet): string;
    
    /**
     * Registra uso de skills para analytics.
     */
    public function logSkillUsage(
        ResolvedSkillSet $skillSet,
        string $tenantId,
        string $agentType,
        string $taskType,
        int $inputTokens,
        int $outputTokens
    ): void;
}
```

### 4.2 Value Objects

```php
// SkillContext.php
final class SkillContext {
    public function __construct(
        private ?string $tenantId,
        private ?string $vertical,
        private ?string $agentType,
        private string $taskType,
        private array $additionalContext = []
    ) {}
}

// ResolvedSkillSet.php
final class ResolvedSkillSet {
    /** @param AiSkill[] $skills */
    public function __construct(private array $skills) {}
    
    public function getSkills(): array;
    public function getTotalTokens(): int;
    public function getMachineNames(): array;
}
```

### 4.3 Checklist Sprint G2

- [ ] Crear `SkillContext.php` value object
- [ ] Crear `ResolvedSkillSet.php` value object
- [ ] Crear `SkillManager.php` con `resolveSkills()`
- [ ] Implementar pipeline jerárquico Core → Vertical → Agent → Tenant
- [ ] Crear `SkillPromptBuilder.php` para generar XML
- [ ] Registrar servicios en `jaraba_skills.services.yml`
- [ ] Crear tests PHPUnit para resolución
- [ ] Verificar integración con `jaraba_ai_agents`

---

## 5. Sprint G3: APIs REST + Frontend

### 5.1 Endpoints API

| Método | Endpoint | Modal | Descripción |
|--------|----------|-------|-------------|
| GET | `/api/v1/skills` | N/A | Listar skills del tenant |
| POST | `/api/v1/skills` | slide-panel | Crear skill |
| GET | `/api/v1/skills/{uuid}` | slide-panel | Ver skill |
| PATCH | `/api/v1/skills/{uuid}` | slide-panel | Actualizar |
| DELETE | `/api/v1/skills/{uuid}` | confirm modal | Desactivar |
| **POST** | **`/api/v1/skills/resolve`** | N/A | **Resolver skills para contexto** |

### 5.2 Endpoint Crítico: /resolve

**Request:**
```json
{
  "tenant_id": "bodega_robles",
  "agent_type": "marketing_multi",
  "vertical": "agroconecta",
  "task_type": "social_post"
}
```

**Response:**
```json
{
  "resolved_skills": [...],
  "merged_prompt_section": "<skills>...</skills>",
  "total_tokens": 1847,
  "resolution_time_ms": 45
}
```

### 5.3 Frontend Dashboard

**Template:** `page--skills.html.twig`

```twig
{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('ecosistema_jaraba_theme/slide-panel') }}
{{ attach_library('jaraba_skills/dashboard') }}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main id="main-content" class="skills-main">
  <header class="dashboard-header dashboard-header--premium dashboard-header--innovation">
    <canvas id="skills-particles" class="dashboard-header__particles"></canvas>
    <div class="dashboard-header__content">
      {{ jaraba_icon('ai', 'skill', { variant: 'duotone', color: 'white', size: '48px' }) }}
      <div class="dashboard-header__text">
        <h1>{% trans %}Habilidades IA{% endtrans %}</h1>
        <p>{% trans %}Sistema de especialización de agentes{% endtrans %}</p>
      </div>
    </div>
  </header>
  
  <div class="skills-wrapper">
    {{ page.content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

### 5.4 Checklist Sprint G3

- [ ] Crear `SkillApiController.php` con detección AJAX
- [ ] Implementar endpoint `/resolve`
- [ ] Crear template `page--skills.html.twig` limpio
- [ ] Registrar template suggestion en `.theme`
- [ ] Añadir clases body via `hook_preprocess_html()`
- [ ] Implementar botones con `data-slide-panel`
- [ ] Verificar slide-panel funciona correctamente

---

## 6. Sprint G4: Skills Core Predefinidas

### 6.1 Skills Core (7)

| machine_name | Label | Propósito | Aplica a |
|--------------|-------|-----------|----------|
| `tone_guidelines` | Tono de Voz Jaraba | Voz "Sin Humo" | Todas |
| `gdpr_handling` | Manejo GDPR | Datos personales | Interacciones con datos |
| `escalation_protocol` | Protocolo de Escalado | Cuándo escalar a humano | Conversaciones problemáticas |
| `answer_capsule` | Técnica Answer Capsule | GEO para respuestas | Contenido público |
| `accessibility_writing` | Escritura Accesible | WCAG 2.1 AA | Todo contenido |
| `error_recovery` | Recuperación de Errores | Respuestas cuando falla | Errores |
| `feedback_collection` | Recolección de Feedback | Solicitar sin intrusión | Final de interacciones |

### 6.2 Checklist Sprint G4

- [ ] Crear skill `tone_guidelines` scope=core
- [ ] Crear skill `gdpr_handling` scope=core
- [ ] Crear skill `escalation_protocol` scope=core
- [ ] Crear skill `answer_capsule` scope=core
- [ ] Crear skill `accessibility_writing` scope=core
- [ ] Crear skill `error_recovery` scope=core
- [ ] Crear skill `feedback_collection` scope=core
- [ ] Marcar todas como `is_locked = TRUE`
- [ ] Verificar resolución en endpoint `/resolve`

---

## 7. Estilos SCSS

**Ubicación:** `ecosistema_jaraba_core/scss/_skills-dashboard.scss`

```scss
@use 'variables' as *;

// ============================================
// AI Skills Dashboard - Premium Design
// ============================================

.skills-page {
  min-height: 100vh;
  background: var(--ej-bg-body, #{$ej-bg-body-fallback});
  
  // Override hero si existe
  &.hero--split {
    display: block !important;
  }
}

.skills-main {
  width: 100%;
  min-height: calc(100vh - 160px);
}

.skills-wrapper {
  max-width: 1400px;
  margin-inline: auto;
  padding: var(--ej-spacing-xl, #{$ej-spacing-xl-fallback}) 
           var(--ej-spacing-lg, #{$ej-spacing-lg-fallback});
  
  @media (max-width: 767px) {
    padding: var(--ej-spacing-lg, #{$ej-spacing-lg-fallback}) 
             var(--ej-spacing-md, #{$ej-spacing-md-fallback});
  }
}

// Skill Cards - Premium Glassmorphism
.skill-card {
  background: linear-gradient(135deg, 
    rgba(255,255,255,0.95), 
    rgba(248,250,252,0.9));
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,0.8);
  border-radius: var(--ej-radius-lg, 12px);
  box-shadow: 
    0 4px 24px rgba(0,0,0,0.04),
    0 1px 2px rgba(0,0,0,0.02),
    inset 0 1px 0 rgba(255,255,255,0.9);
  transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  
  &:hover {
    transform: translateY(-6px) scale(1.02);
  }
  
  &__scope {
    font-size: var(--ej-font-size-xs);
    font-weight: 600;
    text-transform: uppercase;
    padding: 4px 8px;
    border-radius: 4px;
    
    &--core { 
      background: var(--ej-color-innovation, #00A9A5); 
      color: white; 
    }
    &--vertical { 
      background: var(--ej-color-impulse, #FF8C42); 
      color: white; 
    }
    &--tenant { 
      background: var(--ej-color-corporate, #233D63); 
      color: white; 
    }
  }
}
```

**Importar en main.scss:**
```scss
@use 'skills-dashboard';
```

---

## 8. Checklist Pre-Commit

### 8.1 Content Entity Navigation
- [ ] ¿Tiene los 4 archivos YAML de navegación?
- [ ] ¿`field_ui_base_route` apunta a ruta settings?
- [ ] ¿Aparece en `/admin/content` como pestaña?
- [ ] ¿Aparece en `/admin/structure` para Field UI?

### 8.2 Internacionalización
- [ ] ¿Todos los textos usan `$this->t()` en PHP?
- [ ] ¿Todos los textos usan `{% trans %}` en Twig?
- [ ] ¿JavaScript usa `Drupal.t()`?

### 8.3 Estilos
- [ ] ¿SCSS usa variables inyectables `var(--ej-*)`?
- [ ] ¿Colores usan paleta Jaraba (corporate, innovation, impulse)?
- [ ] ¿Layout es mobile-first?

### 8.4 Iconografía
- [ ] ¿Icono tiene versión outline?
- [ ] ¿Icono tiene versión duotone?

### 8.5 UX
- [ ] ¿Acciones CRUD abren en slide-panel?
- [ ] ¿Template frontend es limpio (sin regiones Drupal)?
- [ ] ¿Clases de body añadidas via `hook_preprocess_html`?

### 8.6 Técnico
- [ ] ¿`composer dump-autoload -o` ejecutado?
- [ ] ¿`drush cr` ejecutado?
- [ ] ¿SCSS compilado con Dart Sass?

---

## 9. Verificación

### 9.1 Comandos de Verificación

```bash
# Verificar entidad registrada
docker exec jarabasaas_appserver_1 drush entity:types | grep ai_skill

# Verificar rutas
docker exec jarabasaas_appserver_1 drush route:list | grep skills

# Compilar SCSS
cd web/modules/custom/ecosistema_jaraba_core
source ~/.nvm/nvm.sh && nvm use --lts && npm run build
lando drush cr
```

### 9.2 Verificación Manual

1. Navegar a `/admin/content` → debe aparecer tab "Habilidades IA"
2. Navegar a `/admin/structure/ai-skill` → debe aparecer "Administrar campos"
3. Navegar a `/skills` → debe mostrar dashboard premium con partículas
4. Clic en "Añadir Habilidad" → debe abrir slide-panel

---

## 10. Registro de Cambios

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2026-01-23 | 1.0.0 | Documento original de implementación |
| **2026-02-04** | **2.0.0** | **Incorporación de directrices obligatorias del proyecto:** SCSS inyectable, i18n, Content Entity navigation, slide-panel, frontend limpio, iconografía duotone, hooks preprocess. Añadidos checklists de verificación. |

---

## Workflows Relevantes

| Workflow | Uso |
|----------|-----|
| `/drupal-custom-modules` | Estructura de entidades y navegación |
| `/scss-estilos` | Variables inyectables y paleta |
| `/i18n-traducciones` | Textos traducibles |
| `/frontend-page-pattern` | Templates limpios |
| `/slide-panel-modales` | CRUD en modales |
| `/ai-integration` | Integración con LLMs |

---

*Documento actualizado: 2026-02-04 con todas las directrices del proyecto*
