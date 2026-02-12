# 🌱 AgroConecta Fase 1 — Commerce Core Foundation

**Fecha:** 2026-02-08  
**Módulo:** `jaraba_agroconecta_core`  
**Patrón replicado:** `jaraba_credentials` (Content Entity + admin routing + Field UI)  
**Estado:** ✅ Habilitado en producción (40 archivos)

---

## 1. Patrón Content Entity completo (Drupal 11)

Se replicó el patrón exacto de `jaraba_credentials` para crear 3 Content Entities con su infraestructura completa:

```
Entity → ListBuilder → AccessControlHandler → EntityForm → SettingsForm
  ↓
routing.yml → links.menu.yml → links.task.yml → links.action.yml
  ↓
collection: /admin/content/
settings:   /admin/structure/
canonical:  /admin/content/{entity}
```

### Lección clave: Anotación `@ContentEntityType`
- Los handlers (list_builder, form, access) DEBEN estar en la anotación PHP, no en `routing.yml`
- `field_ui_base_route` conecta Field UI automáticamente
- `links` en la anotación corresponden con las rutas del `routing.yml`

---

## 2. Config Install — Bug de "dotted key"

### ❌ Error cometido
```yaml
# INCORRECTO — Drupal rechaza keys con punto
jaraba_agroconecta_core.settings:
  marketplace_name: 'AgroConecta'
```

### ✅ Corrección
```yaml
# CORRECTO — El filename define el config name
marketplace_name: 'AgroConecta'
marketplace_description: 'Marketplace de productos agroalimentarios...'
products_per_page: 12
```

### Regla
> En archivos `config/install/*.yml`, el **nombre del archivo** ya define el config object name. El contenido YAML debe tener keys planas, NUNCA un wrapper con punto en el nombre.

---

## 3. Patrón SCSS — Federated Design Tokens para verticales

```scss
// ✅ CORRECTO — Solo consume tokens del ecosistema
.agro-product-card {
  background: var(--ej-bg-card, #FFFFFF);
  color: var(--ej-text-primary, #1F2937);
}

// ✅ Nuevo token vertical
&__price {
  color: var(--ej-color-agro, #556B2F);  // verde oliva para agro
}
```

### Regla
- Cada vertical puede introducir **tokens semánticos propios** (ej: `--ej-color-agro`)
- Siempre con **fallback explícito** en el mismo módulo
- NUNCA definir `$variables` SCSS — solo `var(--token, $fallback)`

---

## 4. Multi-tenancy en Content Entities

Todas las entidades incluyen `tenant_id` como campo base con entity reference al Group module:

```php
$fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Tenant'))
    ->setSetting('target_type', 'group')
    ->setDisplayConfigurable('form', TRUE);
```

### Regla
- `tenant_id` siempre como `entity_reference` a `group`, no string libre
- Los servicios filtran por `tenant_id` cuando corresponde
- En listados admin se muestra el tenant para super-admins

---

## 5. Checklist para nuevos módulos de vertical

1. **Estudiar patrón existente**: `jaraba_credentials` como referencia canónica
2. **Crear 9 YAML files**: info, permissions, routing, libraries, services, links.{menu,task,action}
3. **Crear Content Entities**: Con anotación completa, todos los handlers declarados
4. **Config install**: Keys planas, sin dots, nombre archivo = config name
5. **Services**: Con `@entity_type.manager`, `@current_user`, `@logger.channel`
6. **SCSS**: Solo `var(--ej-*, $fallback)`, BEM naming, mobile-first
7. **hook_preprocess_html()**: Body classes para frontend pages (NUNCA `attributes.addClass()` en Twig)
8. **hook_theme()**: Templates sin `page.content`, variables explícitas
9. **Habilitar**: `lando drush en module -y && lando drush entity-updates -y`

---

## 6. API REST — Patrón de serialización

```php
// Rate-limit en query params
$limit = min((int) $request->query->get('limit', 20), 50);
$offset = max((int) $request->query->get('offset', 0), 0);

// Respuesta con meta para paginación
return new JsonResponse([
    'meta' => ['total' => $total, 'limit' => $limit, 'offset' => $offset],
    'data' => array_map([$this, 'serializeProduct'], $products),
]);
```
