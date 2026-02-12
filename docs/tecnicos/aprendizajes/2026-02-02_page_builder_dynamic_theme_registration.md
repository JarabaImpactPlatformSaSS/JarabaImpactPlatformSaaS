# 📚 Page Builder: Registro Dinámico de Themes para Bloques

**Fecha de creación:** 2026-02-02 20:42  
**Última actualización:** 2026-02-02 20:42  
**Autor:** IA Asistente (Antigravity)  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Contexto del Problema](#1-contexto-del-problema)
2. [Causa Raíz Identificada](#2-causa-raíz-identificada)
3. [Patrón de Solución](#3-patrón-de-solución)
4. [Implementación Recomendada](#4-implementación-recomendada)
5. [Alternativa: Inline Template](#5-alternativa-inline-template)
6. [Verificación](#6-verificación)
7. [Lecciones Aprendidas](#7-lecciones-aprendidas)
8. [Registro de Cambios](#8-registro-de-cambios)

---

## 1. Contexto del Problema

El Page Builder de Jaraba SaaS tiene dos modos:
- **Previews de templates**: Funcionan correctamente ✅
- **Páginas reales** (Legacy y Multi-Block): No renderizan contenido ❌

El síntoma es que al visitar una página creada con el Page Builder (ej: `/pepejaraba`), la página aparece **completamente vacía**.

---

## 2. Causa Raíz Identificada

### El Bug

El `PageContentViewBuilder` genera render arrays con themes dinámicos que **nunca se registran**:

```php
// PageContentViewBuilder.php línea 79
$build['content']['section_0'] = [
    '#theme' => 'page_builder_block__' . $template_id,  // ← "split_hero"
    '#content' => $content_data,
    // ...
];
```

Drupal busca un theme llamado `page_builder_block__split_hero` que **no existe** en el registry porque `hook_theme()` solo registra templates estáticos.

### Por Qué Funcionan las Previews

El `TemplatePickerController` usa un enfoque diferente:

```php
// TemplatePickerController.php línea 268
$twig = \Drupal::service('twig');
return $twig->render($template_path, ['content' => $preview_data]);
```

Aquí se llama directamente al servicio Twig con la **ruta del archivo** (ej: `@jaraba_page_builder/blocks/hero/split-hero.html.twig`), bypaseando completamente el theme registry de Drupal.

### Diagrama Comparativo

```
╔══════════════════════════════════════════════════════════════════╗
║  PREVIEWS (Funcionan)                                            ║
║  TemplatePickerController → $twig->render($path, $data)          ║
║  • Usa servicio Twig directamente                                ║
║  • Path: @jaraba_page_builder/blocks/hero/split-hero.html.twig  ║
║  • No depende de hook_theme()                                    ║
╚══════════════════════════════════════════════════════════════════╝

╔══════════════════════════════════════════════════════════════════╗
║  PÁGINAS (No funcionan)                                          ║
║  PageContentViewBuilder → Render Array con #theme                ║
║  • Usa system de themes de Drupal                                ║
║  • Theme: page_builder_block__split_hero                         ║
║  • Drupal busca en registry → NO ENCONTRADO → Vacío              ║
╚══════════════════════════════════════════════════════════════════╝
```

---

## 3. Patrón de Solución

### Registro Dinámico de Themes

El patrón consiste en leer las Config Entities `PageTemplate` en `hook_theme()` y registrar automáticamente un theme para cada una.

```
Config Entity          →    Theme Registry Entry
────────────────────────────────────────────────
split_hero             →    page_builder_block__split_hero
hero_fullscreen        →    page_builder_block__hero_fullscreen
features_grid          →    page_builder_block__features_grid
...                    →    ...
```

### Consideraciones

- `hook_theme()` se ejecuta al reconstruir el registry (drush cr)
- Las entities pueden no existir durante la instalación inicial
- Envolver en try/catch para manejar edge cases

---

## 4. Implementación Recomendada

```php
/**
 * Implements hook_theme().
 *
 * REGISTRO DINÁMICO:
 * Registra automáticamente todos los themes de bloques
 * leyendo las Config Entities PageTemplate.
 */
function jaraba_page_builder_theme($existing, $type, $theme, $path) {
  // Templates estáticos existentes
  $themes = [
    'page_builder_page' => [
      'variables' => [
        'page_content' => NULL,
        'blocks' => [],
        'meta' => [],
      ],
      'template' => 'page-builder-page',
    ],
    'page_template_preview' => [
      'variables' => [
        'template' => NULL,
        'preview_data' => [],
        'usage_count' => NULL,
        'avg_engagement' => NULL,
        'preview_iframe_url' => NULL,
      ],
      'template' => 'page-template-preview',
    ],
    // ... otros templates estáticos
  ];
  
  // ═══════════════════════════════════════════════════════════════
  // REGISTRO DINÁMICO DE BLOQUES
  // ═══════════════════════════════════════════════════════════════
  try {
    $template_storage = \Drupal::entityTypeManager()
      ->getStorage('page_template');
    $templates = $template_storage->loadMultiple();
    
    foreach ($templates as $template) {
      /** @var \Drupal\jaraba_page_builder\PageTemplateInterface $template */
      $template_id = $template->id();
      $twig_template = $template->getTwigTemplate();
      
      if (empty($twig_template)) {
        continue;
      }
      
      // Convertir namespace path a path relativo
      // @jaraba_page_builder/blocks/hero/split-hero.html.twig
      // → blocks/hero/split-hero
      $template_path = preg_replace(
        '/^@jaraba_page_builder\/(.+)\.html\.twig$/', 
        '$1', 
        $twig_template
      );
      
      // Registrar theme dinámico
      $themes['page_builder_block__' . $template_id] = [
        'variables' => [
          'content' => [],
          'template_id' => '',
          'page' => NULL,
          'section_uuid' => '',
          'section_weight' => 0,
        ],
        'template' => $template_path,
      ];
    }
    
    \Drupal::logger('jaraba_page_builder')->info(
      'Registered @count dynamic block themes', 
      ['@count' => count($templates)]
    );
    
  } catch (\Exception $e) {
    // Durante instalación las entidades pueden no existir
    \Drupal::logger('jaraba_page_builder')->notice(
      'Skipping dynamic theme registration: @message', 
      ['@message' => $e->getMessage()]
    );
  }
  
  return $themes;
}
```

---

## 5. Alternativa: Inline Template

Si el registro dinámico causa problemas de caché o rendimiento, existe una alternativa más directa en el ViewBuilder:

```php
/**
 * Construye la vista legacy usando inline template.
 *
 * VENTAJA: No requiere modificar hook_theme()
 * DESVENTAJA: Menos controlable por el tema, sin suggestions
 */
protected function buildLegacyView(PageContentInterface $entity, array $build): array {
    $template_id = $entity->get('template_id')->value ?? '';
    $content_data = json_decode($entity->get('content_data')->value ?? '{}', TRUE) ?: [];
    
    if (empty($template_id)) {
        return $build;
    }
    
    // Cargar PageTemplate para obtener ruta Twig
    $template_entity = $this->entityTypeManager
        ->getStorage('page_template')
        ->load($template_id);
    
    if (!$template_entity) {
        \Drupal::logger('jaraba_page_builder')->warning(
            'Template @id not found for page @page', 
            ['@id' => $template_id, '@page' => $entity->id()]
        );
        return $build;
    }
    
    $twig_path = $template_entity->getTwigTemplate();
    
    // Usar inline_template con include
    $build['content']['section_0'] = [
        '#type' => 'inline_template',
        '#template' => "{% include '$twig_path' %}",
        '#context' => [
            'content' => $content_data,
            'template_id' => $template_id,
            'page' => $entity,
        ],
    ];
    
    return $build;
}
```

### Comparación de Enfoques

| Aspecto | Registro Dinámico | Inline Template |
|---------|-------------------|-----------------|
| Tema puede override | ✅ Sí | ❌ No |
| Template suggestions | ✅ Sí | ❌ No |
| Modificar hook_theme | ✅ Requerido | ❌ No necesario |
| Caché themes | ⚠️ Rebuild al añadir template | ✅ Sin impacto |
| Complejidad | Media | Baja |

---

## 6. Verificación

Después de implementar la solución:

```bash
# 1. Limpiar caché de themes
lando drush cr

# 2. Verificar que los themes se registraron
lando drush ev "print_r(array_keys(\Drupal::service('theme.registry')->get()));" | grep page_builder_block

# 3. Revisar logs de registro dinámico
lando drush ws --filter=jaraba_page_builder

# 4. Visitar la página en el navegador
# https://jaraba-saas.lndo.site/es/pepejaraba
```

---

## 7. Lecciones Aprendidas

> [!IMPORTANT]
> **Lección Principal**: Cuando un render array usa `#theme`, el theme **DEBE** existir en el registry. Si se genera dinámicamente, hay que registrarlo dinámicamente.

### Patrones Relevantes

1. **Twig Directo vs Theme Registry**
   - `$twig->render($path, $data)`: Bypasa registry, útil para previews
   - `#theme => 'nombre'`: Requiere registro en `hook_theme()`

2. **Config Entities como Fuente de Verdad**
   - Las `PageTemplate` contienen `twig_template` con path al archivo
   - Este path puede usarse para registrar themes dinámicamente

3. **Manejo de Edge Cases**
   - Durante instalación, las entities pueden no existir
   - Siempre usar try/catch en `hook_theme()` para nuevos módulos

---

## 8. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-02 | 1.0.0 | Documentación inicial del problema y soluciones |
