---
description: Lecciones aprendidas en implementación de módulos custom Drupal con Content Entities
---

# Flujo de Trabajo: Módulos Custom Drupal con Content Entities

## 🔍 VERIFICACIÓN ARQUITECTÓNICA OBLIGATORIA

> [!CAUTION]
> **ANTES de descartar un componente o afirmar que "requiere infraestructura nueva", VERIFICAR:**
> 1. **KIs existentes** - Buscar en Knowledge Items información sobre el componente
> 2. **Documentos técnicos** - Revisar `/docs/tecnicos/` para especificaciones existentes
> 3. **Módulos implementados** - Verificar `/web/modules/custom/` para servicios relacionados
> 4. **Directrices del proyecto** - Consultar `00_DIRECTRICES_PROYECTO.md`

### Infraestructura Base Verificada del Proyecto

| Componente | Módulo | Estado | Documentación |
|------------|--------|--------|---------------|
| **Qdrant** | jaraba_rag | ✅ Operativo | 20260111-Guia_Tecnica_KB_RAG_Qdrant.md |
| **Embeddings** | jaraba_ai_core | ✅ Operativo | AI Copilot, KB AI-Nativa |
| **Redis Cache** | ecosistema_jaraba_core | ✅ Disponible | Arquitectura base SaaS |
| **H5P** | Contrib | ✅ Disponible | Para contenido interactivo/video |
| **xAPI** | jaraba_lms | ✅ Especificado | Progress tracking |

> **Ejemplo de error a evitar:**
> ❌ "El Matching Engine requiere Qdrant → DESCARTAR"
> ✅ "Verificar si Qdrant ya está implementado → Sí (jaraba_rag) → PROCEDER"

---
## 🎯 DECISIÓN ARQUITECTÓNICA: ConfigEntity vs ContentEntity

**Antes de crear una entidad, decidir el tipo correcto:**

| Criterio | ConfigEntity | ContentEntity |
|----------|--------------|---------------|
| **¿Datos de usuario/operativos?** | No | Sí |
| **¿Exportable a Git (YAML)?** | Sí | No |
| **¿Field UI (añadir campos desde admin)?** | ❌ No | ✅ Sí |
| **¿Views completas?** | Limitado | ✅ Sí |
| **¿Igual para todos los tenants?** | Sí | Depende |
| **¿Necesita versionado de config?** | Sí | No |

### Ejemplos de uso correcto

| Entidad | Tipo | Justificación |
|---------|------|---------------|
| **Feature** | ConfigEntity | Catálogo del producto - definido en código |
| **AIAgent** | ConfigEntity | Catálogo del producto - definido en código |
| **Vertical** | ContentEntity | Admin puede crear nuevas verticales |
| **Tenant** | ContentEntity | Datos operativos de clientes |
| **SaasPlan** | ContentEntity | Admin puede crear planes personalizados |
| **Course, JobPosting** | ContentEntity | Datos de usuario, necesitan Field UI |

> [!IMPORTANT]
> **Si el administrador SaaS necesita poder añadir campos personalizados sin tocar código → usa ContentEntity.**
> **Si los campos son fijos y se definen en código → usa ConfigEntity.**

---

## ⚠️ CRÍTICO: Ubicación de Entidades en Navegación Admin

**Las Content Entities NO van en `/admin/config`**. La ubicación correcta depende del tipo:

| Tipo de Entidad | Ubicación Correcta | Ejemplo |
|-----------------|-------------------|---------|
| **Content Entities** (datos de usuario) | `/admin/content` | Cursos, Ofertas de empleo, Perfiles |
| **Config Entities** (tipos, vocabularios) | `/admin/structure` | Tipos de contenido, Taxonomías |
| **Ajustes de módulo** (settings, API keys) | `/admin/config` | Configuración del LMS, claves API |

### Estructura Correcta de Rutas

```yaml
# ✅ CORRECTO: Content Entities en /admin/content
entity.lms_course.collection:
  path: '/admin/content/courses'

entity.job_posting.collection:
  path: '/admin/content/jobs'

# ✅ CORRECTO: Settings en /admin/config
jaraba_lms.settings:
  path: '/admin/config/empleabilidad/lms/settings'

# ❌ INCORRECTO: Content Entities en /admin/config
entity.lms_course.collection:
  path: '/admin/config/empleabilidad/lms/courses'  # NO
```

---

## 📋 CHECKLIST OBLIGATORIO: Content Entity Implementation

Antes de dar por completada una Content Entity, verificar **TODOS** estos elementos:

### 1. Definición de Entidad (Entity.php)
- [ ] Annotation `@ContentEntityType` completa
- [ ] Handler `list_builder` definido → `"list_builder" = "Drupal\mymodule\MyEntityListBuilder"`
- [ ] Handler `views_data` definido → `"views_data" = "Drupal\views\EntityViewsData"` 
- [ ] Handler `form` definido (default, add, edit, delete)
- [ ] Handler `access` definido
- [ ] Handler `route_provider` → `"html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"`
- [ ] Entity keys correctos (id, uuid, label, owner)
- [ ] Links correctos (canonical, add-form, edit-form, delete-form, collection)
- [ ] `field_ui_base_route` definido → apunta a `entity.myentity.settings`

### 2. Handlers Requeridos
- [ ] **ListBuilder** (`src/MyEntityListBuilder.php`) → extiende `EntityListBuilder`
- [ ] **Form** (`src/Form/MyEntityForm.php`) → extiende `ContentEntityForm`
- [ ] **AccessControlHandler** (`src/MyEntityAccessControlHandler.php`)

### 3. Routing (*.routing.yml)
- [ ] `entity.myentity.collection` → `/admin/content/myentities`
- [ ] `entity.myentity.canonical` → `/admin/content/myentity/{myentity}`
- [ ] `entity.myentity.add_form` → `/admin/content/myentities/add`
- [ ] `entity.myentity.edit_form` → `/admin/content/myentity/{myentity}/edit`
- [ ] `entity.myentity.delete_form` → `/admin/content/myentity/{myentity}/delete`
- [ ] `entity.myentity.settings` → `/admin/structure/myentity` (para Field UI)
- [ ] Settings route → `/admin/config/module/settings`

### 4. Navigation Links

#### Estructura (Field UI) - enlaces en `/admin/structure`
- [ ] `*.links.menu.yml` → parent: `system.admin_structure` con `route_name: entity.myentity.settings`

#### Contenido (Pestañas) - tabs en `/admin/content`
- [ ] `*.links.task.yml` → `base_route: system.admin_content` para pestañas como Contenido/Bloques/Comentarios
```yaml
entity.myentity.collection:
  title: 'Mi Entidad'
  route_name: entity.myentity.collection
  base_route: system.admin_content
  weight: 20
```

#### Botones de Acción
- [ ] `*.links.action.yml` → botón "Add" en collection

> [!CAUTION]
> **Los 4 Archivos YAML Obligatorios** (aprendizaje 2026-01-19)
> 
> Cada Content Entity **DEBE** tener estos 4 archivos para navegación completa:
> | Archivo | Si falta... |
> |---------|-------------|
> | `*.routing.yml` | URLs no funcionan |
> | `*.links.menu.yml` | No aparece en Structure |
> | `*.links.task.yml` | No aparece como tab en Content |
> | `*.links.action.yml` | No hay botón "Añadir" |
> 
> Además, la entidad debe tener `"add-form"` en sus links annotation.

### 5. Field UI y Views Integration
- [ ] Handler `views_data` en anotación de entidad
- [ ] `field_ui_base_route` apuntando a ruta settings existente
- [ ] Ruta `entity.myentity.settings` creada en routing.yml con Form específico
- [ ] Form Settings (`src/Form/MyEntitySettingsForm.php`) extiende `FormBase`
- [ ] Verificar que aparece pestaña "Administrar campos" en settings

### 6. Post-Creación
- [ ] `composer dump-autoload -o` ejecutado
- [ ] `drush cr` ejecutado
- [ ] **Docker restart** si hay cambios de clases (OPcache)
- [ ] Script `install_entities.php` ejecutado (si tablas no existen)
- [ ] Verificar ruta en navegador
- [ ] Verificar que aparece en `/admin/structure`

### 7. Hooks
- [ ] `hook_cron()` NO llama a servicios no implementados
- [ ] `hook_entity_presave()` NO llama a servicios no implementados
- [ ] `hook_entity_access()` retorna `AccessResultInterface`, no `int`

> [!IMPORTANT]
> **OPcache en Lando/Docker**: Si las clases existen (confirmado via `drush scr`) pero
> Drupal sigue reportando "non-existent class", reiniciar el contenedor Docker
> para limpiar OPcache:
> ```bash
> docker restart <container_name>
> ```

---

## Problema Común: Error 500 al limpiar cache

### Causa Raíz
Los módulos custom que definen servicios en `*.services.yml` pueden causar error 500 si:
1. Las clases de servicio no existen en el autoload de Composer
2. Los hooks `hook_cron()` o `hook_entity_presave()` llaman a servicios no implementados

### Solución
1. **Verificar que clases PHP existen en contenedor Docker**:
   ```bash
   docker exec <container> ls -la /path/to/Service/
   ```

2. **Regenerar autoload de Composer** (no solo drush cr):
   ```bash
   docker exec <container> composer dump-autoload -o
   docker exec <container> drush cr
   ```

3. **Comentar llamadas a servicios no implementados** en hooks críticos:
   ```php
   function mymodule_cron(): void {
     // TODO: Re-enable when services are fully implemented
     // $service = \Drupal::service('mymodule.service');
   }
   ```

---

## Problema: Entidades instaladas pero tablas no creadas

### Causa Raíz
Los módulos pueden quedar en estado "installed" sin que las tablas de entidades se creen en la base de datos.

### Solución
Crear y ejecutar script PHP para forzar instalación:
```php
<?php
// web/install_entities.php
// Run with: drush scr install_entities.php

$entity_type_manager = \Drupal::entityTypeManager();
$entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

$entity_types = ['my_entity_type'];

foreach ($entity_types as $entity_type_id) {
  $definition = $entity_type_manager->getDefinition($entity_type_id, FALSE);
  if ($definition) {
    $entity_definition_update_manager->installEntityType($definition);
    echo "Installed: $entity_type_id\n";
  }
}
```

---

## Problema: Menú admin no aparece

### Causa Raíz
Los menús definidos en `.links.menu.yml` requieren:
1. Que la ruta referenciada exista en `*.routing.yml`
2. Que el usuario tenga el permiso especificado
3. Que las rutas de colección de entidades estén definidas

### Checklist para integración en navegación admin

1. **Crear routing.yml con rutas**:
   - `mymodule.admin` → página principal admin
   - `entity.my_entity.collection` → lista de entidades
   - `entity.my_entity.add_form` → formulario crear
   - `entity.my_entity.edit_form` → formulario editar
   - `entity.my_entity.delete_form` → formulario borrar
   - `entity.my_entity.canonical` → vista de entidad

2. **Crear .links.menu.yml** → entradas de menú admin

3. **Crear .links.action.yml** → botones "Add" en colecciones

4. **Crear .links.task.yml** → tabs View/Edit/Delete

5. **Usar permiso genérico** si el permiso custom no existe:
   ```yaml
   requirements:
     _permission: 'access administration pages'
   ```

---

## Problema: setDefaultValueCallback error

### Causa Raíz
En Drupal 10+, `setDefaultValueCallback` requiere formato string:
```php
// ❌ Incorrecto
->setDefaultValueCallback([static::class, 'getCurrentTime'])

// ✅ Correcto
->setDefaultValueCallback(static::class . '::getCurrentTime')
```

---

## Problema: hook_entity_access retorna tipo incorrecto

### Causa Raíz
El hook debe retornar `AccessResultInterface`, no `int`.

```php
// ❌ Incorrecto
function mymodule_entity_access(...): int {
  return AccessResult::neutral()->getCacheMaxAge();
}

// ✅ Correcto
function mymodule_entity_access(...): AccessResultInterface {
  return AccessResult::neutral();
}
```

---

## 🎨 Visualizaciones SVG en Twig

### Problema: Filtros matemáticos inexistentes

```twig
{# ❌ ERROR: 'rad' filter does not exist #}
{% set x = 150 + 100 * (angle|rad|cos) %}

{# ✅ CORRECTO: Coordenadas precalculadas #}
<line x1="150" y1="150" x2="150" y2="50"/>  {# Pre-calculado #}
```

### Problema: SVG aparece negro (Blackout)

```html
<!-- ❌ ERROR: Sin estilos, el SVG se renderiza negro -->
<circle cx="100" cy="100" r="50" class="grid-line"/>

<!-- ✅ CORRECTO: Estilos inline como fallback -->
<circle cx="100" cy="100" r="50" style="fill:none; stroke:#e5e7eb;"/>
```

### Problema: Etiquetas truncadas

```html
<!-- ❌ ERROR: ViewBox demasiado ajustado -->
<svg viewBox="0 0 300 300">
  <text x="280" y="50">Etiqueta cortada</text>
</svg>

<!-- ✅ CORRECTO: ViewBox ampliado con buffer -->
<svg viewBox="0 0 400 350">
  <text x="320" y="60">Etiqueta visible</text>
</svg>
```

### Problema: Twig Sandbox bloquea métodos

```twig
{# ❌ ERROR: Calling "uuid" method is not allowed #}
{{ path('route', {uuid: entity.uuid()}) }}

{# ✅ CORRECTO: Acceso por propiedad #}
{{ path('route', {uuid: entity.uuid.value}) }}
```

---

## 📝 Formularios Multi-Paso con AJAX (Form State Storage)

> Aprendizaje 2026-01-25

### Problema: Datos no persisten entre rebuilds AJAX

En formularios multi-paso con AJAX, los datos almacenados con `$form_state->set()` pueden no persistir correctamente entre rebuilds.

```php
// ❌ INCORRECTO - Los datos pueden perderse
$form_state->set('current_step', $step);
$form_state->set('selected_items', $items);

// En siguiente rebuild:
$step = $form_state->get('current_step'); // Puede ser NULL
```

### Solución: Usar getStorage()/setStorage()

```php
// ✅ CORRECTO - Usar storage para persistencia garantizada
public function buildForm(array $form, FormStateInterface $form_state): array {
    $storage = $form_state->getStorage();
    
    // Inicializar si es nuevo
    if (!isset($storage['current_step'])) {
        $storage['current_step'] = 0;
        $storage['selected_items'] = [];
    }
    
    $currentStep = $storage['current_step'];
    
    // ... lógica del formulario ...
    
    return $form;
}

public function submitForm(array &$form, FormStateInterface $form_state): void {
    $storage = $form_state->getStorage();
    
    // Actualizar estado
    $storage['current_step'] = ($storage['current_step'] ?? 0) + 1;
    $storage['selected_items'][] = $form_state->getValue('selection');
    
    // IMPORTANTE: Guardar storage actualizado
    $form_state->setStorage($storage);
    
    // Rebuild para siguiente paso
    $form_state->setRebuild(TRUE);
}
```

### Patrón Completo para Quiz/Assessment

```php
public function ajaxSubmitHandler(array &$form, FormStateInterface $form_state): array {
    $storage = $form_state->getStorage();
    $step = $storage['current_step'];
    $totalSteps = $storage['total_steps'];
    
    if ($step < $totalSteps - 1) {
        // Avanzar al siguiente paso
        $storage['current_step'] = $step + 1;
        $form_state->setStorage($storage);
        $form_state->setRebuild(TRUE);
    } else {
        // Último paso - procesar resultados
        $this->processResults($storage['answers']);
    }
    
    return $form['assessment_container'];
}
```

> [!CAUTION]
> **Error común:** Olvidar llamar a `$form_state->setStorage($storage)` después de modificar el array storage.
> Esto causa que los cambios se pierdan en el siguiente rebuild.

---

## 🚀 DESPLIEGUE DE NUEVAS ENTIDADES

### Instalar tablas de entities nuevas

```bash
# ⚠️ drush entity:updates NO EXISTE en Drush 12+
# Usar devel-entity-updates (requiere módulo Devel):
lando drush devel-entity-updates -y

# Siempre rebuilder cache después:
lando drush cr
```

> [!CAUTION]
> **`drush updb`** NO instala tablas de entidades nuevas.
> Solo ejecuta hook_update_N. Para entidades nuevas usar `devel-entity-updates`.

### Compilar SCSS del módulo

```bash
# AgroConecta
lando ssh -c 'cd web/modules/custom/jaraba_agroconecta_core && npx sass scss/main.scss css/agroconecta.css --no-source-map --style=compressed'

# ServiciosConecta
lando ssh -c 'cd web/modules/custom/jaraba_servicios_conecta && npx sass scss/main.scss css/jaraba-servicios-conecta.css --no-source-map --style=compressed'
```

> [!CAUTION]
> **Dart Sass `@use` Module System (Aprendizaje 2026-02-09):**
> Cada parcial SCSS DEBE declarar sus propios `@use` imports. Las variables del archivo principal (`main.scss`) NO se heredan a los parciales cargados con `@use`.
> ```scss
> // ✅ CORRECTO — Cada parcial con sus imports
> @use 'sass:color';
> @use 'variables' as *;
>
> .my-component {
>   color: var(--ej-primary, $my-vertical-primary);
>   background: color.scale($my-vertical-primary, $lightness: 85%);
> }
> ```
> ```scss
> // ❌ INCORRECTO — Confiar en que main.scss propaga variables
> .my-component {
>   color: $my-vertical-primary; // ERROR: Undefined variable
> }
> ```

### URL del entorno local

```
https://jaraba-saas.lndo.site/
```

> [!WARNING]
> La URL correcta es `jaraba-saas.lndo.site`, NO `jaraba-impact-platform.lndo.site`.
> Verificar siempre `.lando.yml` para confirmar el nombre del proxy.

---

## 🏗️ PATRÓN: Nuevo Módulo Vertical (AgroConecta/ServiciosConecta/ComercioConecta)

> Aprendizaje 2026-02-09 — Validado en 3 verticales

### Checklist de Creación de Vertical

| Paso | Descripción | Ejemplo |
|------|-------------|---------|
| 1 | Crear directorio en `web/modules/custom/jaraba_<vertical>/` | `jaraba_servicios_conecta` |
| 2 | `.info.yml` con dependencias (drupal:system, drupal:user, drupal:taxonomy) | Tipo: module |
| 3 | Content Entities en `src/Entity/` con handlers completos | ProviderProfile, Booking... |
| 4 | Taxonomías via `config/install/taxonomy.vocabulary.*.yml` | servicios_category |
| 5 | Términos pre-cargados en `hook_install()` | Term::create([...]) |
| 6 | Controllers frontend en `src/Controller/` | MarketplaceController |
| 7 | Services en `src/Service/` | BookingService |
| 8 | SCSS con `_variables.scss` + parciales + `main.scss` | Dart Sass `@use` |
| 9 | `package.json` con script `build` para compilación SCSS | npm run build |
| 10 | Twig templates en `templates/` con BEM + `var(--ej-*)` | BEM naming |
| 11 | Permisos granulares en `*.permissions.yml` | Por rol y acción |
| 12 | Rutas: frontend (público) + portal (auth) + admin + API | 3+ grupos rutas |

### Estructura Estándar de Archivos SCSS por Vertical

```
scss/
├── _variables.scss          → Colores del vertical + fallback tokens
├── _marketplace.scss        → Página de listado público
├── _provider-detail.scss    → Página de detalle (perfil/producto)
├── _provider-dashboard.scss → Portal del usuario autenticado
├── _components.scss         → Cards, badges, botones reutilizables
└── main.scss               → Entry point con @use de cada parcial
```

### Patrón de Variables por Vertical

```scss
// _variables.scss de cada vertical
$vertical-primary: #2563EB;     // Color primario del vertical
$vertical-accent: #7C3AED;      // Color acento
$vertical-surface: #FFFFFF;     // Fondo de superficie

// Siempre usar con fallback de CSS Custom Properties
.my-element {
  color: var(--ej-primary, $vertical-primary);
}
```

### Verticales Implementados

| Vertical | Módulo | Entities | Estado |
|----------|--------|----------|--------|
| **AgroConecta** | `jaraba_agroconecta_core` | 20 | ✅ Producción |
| **ServiciosConecta** | `jaraba_servicios_conecta` | 5 | ✅ Fase 1 |
| **ComercioConecta** | `jaraba_comercio_conecta` | — | 📋 Planificado |
