# Aprendizaje: Patrón de Navegación de Entidades en Admin

**Fecha:** 2026-01-19  
**Contexto:** Implementación Vertical Emprendimiento  
**Error detectado:** Entidades no visibles en navegación admin

---

## El Problema

Al crear nuevas Content Entities (ej: `MentoringSession`, `CollaborationGroup`), las entidades:
- NO aparecían como tabs en `/admin/content`
- NO mostraban botón "Añadir" en páginas de colección
- NO eran descubribles desde `/admin/structure`

Aunque las entidades existían y las rutas funcionaban al acceder directamente por URL.

---

## La Causa Raíz

**El annotation `links` en la entidad define las URLs pero NO la navegación automática.**

Drupal requiere 4 archivos YAML separados para navegación completa:

| Archivo | Propósito | Si falta... |
|---------|-----------|-------------|
| `*.routing.yml` | Define las rutas | URLs no funcionan |
| `*.links.menu.yml` | Links en menús admin | No aparece en Structure |
| `*.links.task.yml` | Tabs en /admin/content | No aparece como pestaña |
| `*.links.action.yml` | Botón "Añadir" | No hay botón para crear |

---

## La Solución: Los 4 Archivos Obligatorios

### 1. *.links.task.yml - Pestañas en Content

```yaml
# mymodule.links.task.yml
entity.my_entity.collection:
  title: 'Mi Entidad'
  route_name: entity.my_entity.collection
  base_route: system.admin_content  # <- Clave para aparecer en /admin/content
  weight: 30
```

### 2. *.links.action.yml - Botones "Añadir"

```yaml
# mymodule.links.action.yml
entity.my_entity.add_form:
  title: 'Añadir entidad'
  route_name: entity.my_entity.add_form
  appears_on:
    - entity.my_entity.collection  # <- Aparece en página de colección
```

### 3. *.links.menu.yml - Menú Structure

```yaml
# mymodule.links.menu.yml
entity.my_entity.settings:
  title: 'Mi Entidad'
  description: 'Configuración de campos'
  route_name: entity.my_entity.settings
  parent: system.admin_structure
  weight: 10
```

### 4. Entity annotation - add-form link

Además de los YAML, la entidad debe tener `add-form` en sus links:

```php
 * links = {
 *   "collection" = "/admin/content/my-entities",
 *   "add-form" = "/admin/content/my-entities/add",  // <- Obligatorio para botón
 *   "canonical" = "/admin/content/my-entity/{my_entity}",
 *   ...
 * },
```

---

## Checklist para Nuevas Entidades

- [ ] Crear archivo `*.links.task.yml` con `base_route: system.admin_content`
- [ ] Crear archivo `*.links.action.yml` con `appears_on: entity.X.collection`
- [ ] Verificar que `*.links.menu.yml` tiene entrada para settings
- [ ] Verificar que Entity.php tiene `add-form` en `links` annotation
- [ ] Ejecutar `drush cr`
- [ ] Verificar tabs en `/admin/content`
- [ ] Verificar botón "Añadir" en página de colección

---

## Documentación Actualizada

- KI: `standard_vertical_implementation_pattern.md` - Sección 9.1 añadida
- Workflow: Se recomienda añadir a `drupal-custom-modules.md`

---

## Entidades Corregidas en Esta Sesión

| Entidad | Corrección |
|---------|------------|
| `MentoringSession` | Añadido `add-form` link |
| `MentoringEngagement` | Añadido `add-form` link |
| `jaraba_mentoring` | Creados links.task.yml y links.action.yml |
