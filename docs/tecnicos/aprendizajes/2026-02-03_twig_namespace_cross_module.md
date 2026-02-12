# Namespace Twig para Parciales Cross-Module en Drupal 11

**Fecha:** 2026-02-03  
**Categoría:** Arquitectura Frontend  
**Módulos Afectados:** `jaraba_i18n`, `jaraba_page_builder`

---

## Problema

Al integrar el selector de idioma i18n como parcial reutilizable en el canvas-editor del Page Builder, se necesitaba incluir un template Twig desde un módulo diferente usando la sintaxis `{% include '@jaraba_i18n/...' %}`.

Por defecto, Drupal no registra namespaces Twig para módulos custom, lo que causa:
```
Twig\Error\LoaderError: Template "@jaraba_i18n/i18n-selector.html.twig" is not defined.
```

## Solución

### 1. Crear TwigLoader Service

Crear una clase que extienda `FilesystemLoader` y registre el namespace:

```php
// src/TwigLoader/JarabaI18nTwigLoader.php
namespace Drupal\jaraba_i18n\TwigLoader;

use Twig\Loader\FilesystemLoader;

class JarabaI18nTwigLoader extends FilesystemLoader {

  public function __construct() {
    parent::__construct();
    
    $modulePath = \Drupal::service('extension.list.module')->getPath('jaraba_i18n');
    $templatesPath = DRUPAL_ROOT . '/' . $modulePath . '/templates';
    
    if (is_dir($templatesPath)) {
      $this->addPath($templatesPath, 'jaraba_i18n');
    }
  }
}
```

### 2. Registrar como Servicio Tagged

En `jaraba_i18n.services.yml`:

```yaml
jaraba_i18n.twig.loader.filesystem:
  class: Drupal\jaraba_i18n\TwigLoader\JarabaI18nTwigLoader
  tags:
    - { name: twig.loader }
```

### 3. Uso en Otros Módulos

Ahora se puede incluir el parcial desde cualquier template:

```twig
{{ attach_library('jaraba_i18n/i18n-selector') }}
{% include '@jaraba_i18n/i18n-selector.html.twig' with {
  entity_id: page.id(),
  entity_type: 'page_content',
  current_language: current_language|default('es'),
} only %}
```

## Puntos Críticos

1. **Tag Obligatorio**: El servicio DEBE tener el tag `twig.loader` para que Drupal lo reconozca
2. **Limpieza de Cache**: Ejecutar `drush cr` después de crear el servicio
3. **Attach Library**: Si el parcial tiene JS, usar `{{ attach_library() }}` antes del include
4. **Variables con `only`**: Usar `only` para aislar el scope de variables

## Patrón Estándar

Este patrón se debe aplicar a cualquier módulo que exporte parciales Twig reutilizables:

| Módulo | Namespace | Uso |
|--------|-----------|-----|
| `jaraba_i18n` | `@jaraba_i18n` | Selector idioma, traducción IA |
| `jaraba_page_builder` | `@jaraba_page_builder` | Template picker, section editor |
| `ecosistema_jaraba_core` | `@jaraba_core` | Icons, utilidades |

## Relación con Hook Theme

El namespace Twig es **complementario** al `hook_theme()`:

- **hook_theme()**: Registra el template para renderizado vía `#theme`
- **Twig Namespace**: Permite `{% include %}` desde otros templates

Ambos pueden coexistir y se recomienda usar ambos para máxima flexibilidad.

---

**Referencias:**
- [Drupal Twig Namespaces](https://www.drupal.org/docs/theming-drupal/twig-in-drupal/twig-namespaces)
- Conversación: Gap E i18n UI Integration
