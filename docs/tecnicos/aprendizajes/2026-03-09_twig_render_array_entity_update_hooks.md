# Aprendizaje #171 — Twig Render Array Safety + Entity Update Hook Hardening

**Fecha:** 2026-03-09
**Regla de Oro:** #111
**Reglas nuevas:** TWIG-URL-RENDER-ARRAY-001, TWIG-INCLUDE-ONLY-001, UPDATE-HOOK-FIELDABLE-001, TRANSLATABLE-FIELDS-INSTALL-001

---

## Contexto

Warning "Array to string conversion" en `_seo-schema.html.twig` al renderizar la homepage, y errores de entity/field definition mismatch en `/admin/reports/status` para `billing_usage_record` y `site_config`.

## Hallazgo 1 — TWIG-URL-RENDER-ARRAY-001 (P0)

### Problema

En Drupal 11, la funcion Twig `url()` **devuelve un render array**, no un string. Esto se ve en `TwigExtension::getUrl()` (lineas 244-247):

```php
// Return as render array, so we can bubble the bubbleable metadata.
$build = ['#markup' => $generated_url->getGeneratedUrl()];
$generated_url->applyTo($build);
return $build;
```

Cuando se usa `{{ url(...) }}`, Twig pasa el render array por `escapeFilter()` que invoca el renderer de Drupal, convirtiendo el array a string correctamente. Pero cuando se usa con el operador de concatenacion `~`:

```twig
{# ESTO FALLA: Array to string conversion #}
{% set site_url = url('<front>', {}, {absolute: true}) %}
{% set logo_url = site_url ~ 'themes/custom/tema/logo.svg' %}
```

El PHP compilado genera `$context["site_url"] . "themes/custom/..."` que intenta concatenar un array con un string.

### Solucion

- **NUNCA** concatenar `url()` con `~` en Twig
- Usar `url()` solo dentro de `{{ }}` para output directo
- Para URLs compuestas, usar paths relativos: `'/' ~ directory ~ '/logo.svg'`
- O pre-computar la URL como string en preprocess PHP

### Diagnostico

Leer el PHP compilado en `sites/default/files/php/twig/` y localizar la linea exacta del warning. Los comentarios `// line N` mapean al source Twig.

## Hallazgo 2 — TWIG-INCLUDE-ONLY-001 (P1)

### Problema

Sin la keyword `only` en `{% include %}`, TODAS las variables del template padre se filtran al parcial. Esto incluye render arrays de Drupal (como `logo`, `page`, etc.) que pueden colisionar con variables que el parcial espera como strings.

### Solucion

```twig
{# CORRECTO: contexto aislado #}
{% include '@tema/partials/_parcial.html.twig' with {
  site_name: site_name,
  theme_settings: theme_settings|default({}),
  directory: directory
} only %}
```

## Hallazgo 3 — UPDATE-HOOK-FIELDABLE-001 (P0)

### Problema

Los update hooks que usan `getBaseFieldDefinitions()` con `updateFieldableEntityType()` incluyen campos **computed** (como el campo `metatag` del modulo metatag, anadido via `hook_entity_base_field_info()`). Estos campos no tienen storage real, pero `updateFieldableEntityType()` los almacena en la key-value store como field storage definitions. Despues, `getFieldStorageDefinitions()` no los devuelve (porque no tienen storage), creando un mismatch permanente.

### Solucion

```php
// INCORRECTO: incluye campos computed sin storage
$fieldDefs = $fieldManager->getBaseFieldDefinitions('mi_entity');
$updateManager->updateFieldableEntityType($entityType, $fieldDefs, $sandbox);

// CORRECTO: solo campos con storage real
$fieldDefs = $fieldManager->getFieldStorageDefinitions('mi_entity');
$updateManager->updateFieldableEntityType($entityType, $fieldDefs, $sandbox);
```

### Fix para orphan entries existentes

```php
$metatagStorage = $updateManager->getFieldStorageDefinition('metatag', 'mi_entity');
if ($metatagStorage) {
  $updateManager->uninstallFieldStorageDefinition($metatagStorage);
}
```

## Hallazgo 4 — TRANSLATABLE-FIELDS-INSTALL-001 (P0)

### Problema

Hacer una entidad `translatable = TRUE` en un update hook solo actualiza la definicion del entity type. Los 6 campos de tracking del modulo `content_translation` (source, outdated, uid, status, created, changed) se proveen via `hook_entity_base_field_info()` pero **no se instalan automaticamente**. Requieren `installFieldStorageDefinition()` individual.

### Solucion

```php
$translationFields = [
  'content_translation_source', 'content_translation_outdated',
  'content_translation_uid', 'content_translation_status',
  'content_translation_created', 'content_translation_changed',
];

$fieldStorageDefs = $fieldManager->getFieldStorageDefinitions('mi_entity');
foreach ($translationFields as $fieldName) {
  if (isset($fieldStorageDefs[$fieldName])) {
    $fieldDef = $fieldStorageDefs[$fieldName];
    $fieldDef->setName($fieldName);
    $fieldDef->setTargetEntityTypeId('mi_entity');
    $updateManager->installFieldStorageDefinition(
      $fieldName, 'mi_entity', $fieldDef->getProvider(), $fieldDef
    );
  }
}
```

## Regla de Oro #111

> `url()` en Drupal 11 Twig devuelve un render array, NUNCA un string. NUNCA concatenar con `~`. El PHP compilado de Twig se encuentra en `sites/default/files/php/twig/` y es la fuente de verdad para diagnosticar warnings de templates.

## Archivos modificados

| Archivo | Cambio |
|---------|--------|
| `_seo-schema.html.twig` | url() solo en `{{ }}`, logo type-safe, directory explicito |
| `html.html.twig` | Include con `only` + `directory` |
| `page--front.html.twig` | Include con `only` + `directory` + `theme_settings` |
| `jaraba_billing.install` | `update_10005()` — uninstall orphan metatag field |
| `jaraba_site_builder.install` | `update_10009()` — uninstall metatag + install 6 translation fields |
| `CLAUDE.md` | 4 reglas nuevas + version refs actualizadas |

## Gap en Sistema de Salvaguarda

El script `validate-entity-integrity.php` (ENTITY-INTEG-001) verifica que exista `hook_update_N()` para cada entity, pero **no valida**:
1. Que `updateFieldableEntityType()` use `getFieldStorageDefinitions()` en vez de `getBaseFieldDefinitions()`
2. Que entidades translatable tengan los campos content_translation instalados
3. Que no existan orphan entries en la key-value store

**Recomendacion:** Anadir check `ENTITY-COMPUTED-ORPHAN-001` al sistema de validacion para detectar campos computed almacenados como field storage definitions.
