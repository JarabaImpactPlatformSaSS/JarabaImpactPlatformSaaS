# Consistencia de 129 Templates del Page Builder y Fix Drupal 10+ Entity Updates

**Fecha:** 2026-02-18
**Sesion:** Auditoria exhaustiva y correccion masiva de templates PageBuilder + fix Legal Intelligence
**Reglas nuevas:** PB-PREVIEW-001, PB-DATA-001, PB-CAT-001, DRUPAL-ENTUP-001

---

## Contexto

Una auditoria de los 129 templates del Page Builder revelo deficiencias graves de metadatos e inconsistencias entre los pipelines de Canvas Editor (GrapesJS) y Template Picker. El 68% de los templates (88/129) tenian PNGs en disco pero sin campo `preview_image` en el YAML. Los 55 templates verticales tenian `preview_data` insuficiente (5 campos genericos) cuando los Twig templates esperaban arrays ricos. Adicionalmente, el update hook `jaraba_legal_intelligence_update_10004` usaba `applyUpdates()`, eliminado en Drupal 10+.

---

## Lecciones Aprendidas

### 1. Los YAMLs de config/install solo se procesan durante la instalacion del modulo

**Situacion:** Se corrigieron 129 YAMLs en `config/install/` pero los cambios no se reflejaban en la BD activa hasta ejecutar `drush updb`.

**Aprendizaje:** Drupal solo procesa los YAMLs de `config/install/` cuando el modulo se instala por primera vez. Para actualizar configs existentes, se DEBE crear un update hook que lea los YAMLs y los importe via `ConfigFactory::getEditable()->setData()`.

**Regla PB-PREVIEW-001:** Todo YAML de PageTemplate DEBE incluir `preview_image` con ruta al PNG correspondiente. Convencion: `{id_con_guiones}.png` en `/modules/custom/jaraba_page_builder/images/previews/`.

### 2. Los preview_data genericos son insuficientes para previews de calidad

**Situacion:** 55 templates verticales tenian solo 5 campos (`title`, `subtitle`, `description`, `cta_text`, `cta_url`). Los Twig templates esperan campos adicionales como `features[]`, `items[]`, `testimonials[]`, `stats[]`, `image`, `eyebrow`, etc. Las previews se renderizaban vacias o con datos de fallback.

**Aprendizaje:** Cada tipo de template (hero, features, testimonials, faq, pricing, etc.) necesita preview_data especifico que incluya arrays con 3-4 items representativos del dominio vertical. Los datos deben ser realistas y contextualmente apropiados (ej: AgroConecta habla de cosechas, ComercioConecta de ventas online).

**Regla PB-DATA-001:** Los `preview_data` de templates verticales DEBEN incluir arrays con 3+ items representativos del dominio, no solo campos genericos.

### 3. Los pipelines de Canvas Editor y Template Picker deben estar alineados

**Situacion:** El Canvas Editor (GrapesJS) filtraba por `condition('status', TRUE)` pero el Template Picker usaba `loadMultiple()` sin filtrar. El icon fallback del Canvas comparaba categorias con labels en espanol (`'Hero'`, `'CTA'`) pero `getCategory()` devuelve machine names (`'hero'`, `'cta'`). La categoria por defecto era diferente en 3 archivos.

**Aprendizaje:** Cuando multiples controladores renderizan los mismos datos, DEBEN usar la misma logica de filtrado, mismos keys de mapeo, y mismos defaults. Los machine names internos nunca deben compararse con labels visibles.

**Regla PB-CAT-001:** La categoria por defecto de PageTemplate DEBE ser `'content'` en todas las fuentes: `PageTemplate.php`, `CanvasApiController`, `TemplateRegistryService`.

### 4. applyUpdates() fue eliminado en Drupal 10+

**Situacion:** `jaraba_legal_intelligence_update_10004` llamaba a `EntityDefinitionUpdateManager::applyUpdates()` para migrar el campo `expediente_id` de integer a entity_reference. En Drupal 10+, este metodo ya no existe.

**Aprendizaje:** En Drupal 10+, las actualizaciones de esquema de entidades deben hacerse explicitamente. Usar `getFieldStorageDefinition()` para verificar el estado actual, `installFieldStorageDefinition()` para campos nuevos, y `updateFieldStorageDefinition()` para actualizaciones de metadata (sin cambio de tipo).

**Regla DRUPAL-ENTUP-001:** NO usar `applyUpdates()`. Usar `installFieldStorageDefinition()` / `updateFieldStorageDefinition()` explicitamente en update hooks. Verificar tipo de campo antes de intentar actualizarlo.

### 5. Validacion YAML desde CLI sin autoloader de Symfony

**Situacion:** Se intento validar los 129 YAMLs con `php -r "Symfony\Component\Yaml\Yaml::parse(...)"` pero la clase no esta disponible desde CLI sin el autoloader de Drupal.

**Aprendizaje:** Para validar sintaxis YAML desde CLI en un proyecto Drupal, usar Python: `python3 -c "import yaml; yaml.safe_load(open('file.yml'))"`. Python viene preinstalado en la mayoria de entornos y su parser YAML es estricto.

---

## Resumen de Cambios

| Archivo(s) | Cantidad | Cambio |
|------------|----------|--------|
| `config/install/jaraba_page_builder.template.*.yml` | 129 | preview_image vinculado, metadatos corregidos, preview_data enriquecido |
| `images/previews/serviciosconecta-*.png` | 4 nuevos | Placeholders profesionales 800x600 para templates sin PNG |
| `jaraba_page_builder.install` | 1 | update_9006: resync de 129 configs en BD activa |
| `TemplatePickerController.php` | 1 | Filtro `condition('status', TRUE)` + sort por weight |
| `CanvasApiController.php` | 1 | Icon fallback keys corregidos (lowercase machine names), default category 'content' |
| `PageTemplate.php` | 1 | Default category unificado a 'content' |
| `jaraba_legal_intelligence.install` | 1 | update_10004: applyUpdates() reemplazado por install/update explicito |

## Resultado

| Metrica | Antes | Despues |
|---------|-------|---------|
| Templates con preview_image | 41/129 (32%) | 129/129 (100%) |
| Templates con PNGs existentes | 125/129 | 129/129 (4 nuevos) |
| Descripciones con tildes | 0/59 verticales | 59/59 |
| Templates con preview_data rico | 6/55 verticales | 55/55 |
| Pipelines alineados (Canvas/Picker) | Parcialmente | Completamente |
| update_10004 (Legal Intelligence) | Error: applyUpdates() | Funcional |
| update_9006 aplicado | N/A | 0 new, 129 updated, 0 errors |
