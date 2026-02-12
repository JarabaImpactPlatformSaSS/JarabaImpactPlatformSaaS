---
description: Patrón de slide-panel para CRUD sin abandonar la página actual
---

# Workflow: Slide-Panel para Modales CRUD

> [!IMPORTANT]
> **REGLA UX**: Todas las acciones de crear/editar/ver en páginas de frontend deben abrirse en un modal tipo slide-panel, para que el usuario no abandone la página en la que está trabajando.

## Tipo de Modal: Slide-Panel (Off-Canvas)

**Decisión arquitectónica**: Usamos slide-panels desde la derecha en lugar de modales centrados.

| Ventaja | Explicación |
|---------|-------------|
| Mobile-first | Se traduce perfectamente a móvil (100% ancho) |
| Contexto | Usuario ve la página debajo, no pierde contexto |
| Moderno | Patrón SaaS usado en Notion, Linear, Figma |
| Accesible | Focus trap, cierre con ESC, ARIA compliant |

## Archivos del Componente (PROMOCIONADO AL TEMA)

> [!IMPORTANT]
> El slide-panel ha sido **promocionado** de módulo específico a componente global del tema.

```
ecosistema_jaraba_theme/
├── templates/partials/_slide-panel.html.twig  # Partial reutilizable
├── js/slide-panel.js                          # Lógica singleton AJAX
├── scss/_slide-panel.scss                     # 600+ líneas de estilos premium
└── libraries.yml                              # Biblioteca 'slide-panel'
```

## Uso: Data Attributes (Preferido)

El slide-panel usa **data attributes** para declarar triggers sin escribir JS:

```html
<button class="btn"
        data-slide-panel="mi-formulario"
        data-slide-panel-url="/path/to/ajax/form"
        data-slide-panel-title="Nuevo Artículo">
  + Crear
</button>
```

| Atributo | Descripción |
|----------|-------------|
| `data-slide-panel` | ID único del panel |
| `data-slide-panel-url` | URL AJAX que devuelve solo HTML del contenido |
| `data-slide-panel-title` | Título a mostrar en el header del panel |

## Controlador: Respuesta AJAX Limpia

> [!CRITICAL]
> El controlador DEBE detectar AJAX y devolver **solo el HTML del formulario**, no la página completa.

```php
public function add(Request $request): array|Response {
    $entity = $this->entityTypeManager()->getStorage('my_entity')->create();
    $form = $this->entityFormBuilder()->getForm($entity, 'add');

    // Detectar AJAX → devolver solo el form
    if ($request->isXmlHttpRequest()) {
        $html = (string) $this->renderer->render($form);
        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    // Request normal → página completa
    return [
        '#theme' => 'my_template',
        '#form' => $form,
    ];
}
```

## Estilos Premium de Formulario (2026-01-29)

Para formularios Drupal dentro del slide-panel:

### Ocultar "Ruido" de Drupal

**PHP (hook_form_alter)** - Recomendado:
```php
function mymodule_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
  // Usar str_contains para capturar todas las variantes del form
  if (str_contains($form_id, 'my_entity')) {
    _mymodule_hide_format_guidelines($form);
  }
}

function _mymodule_hide_format_guidelines(array &$element): void {
  if (isset($element['format'])) {
    // Ocultar TODO el wrapper de formato
    $element['format']['#access'] = FALSE;
  }
  // Procesar hijos recursivamente
  foreach (array_keys($element) as $key) {
    if (is_array($element[$key]) && !str_starts_with((string) $key, '#')) {
      _mymodule_hide_format_guidelines($element[$key]);
    }
  }
}
```

**CSS (respaldo)**:
```scss
.slide-panel__body {
  // Ocultar ayuda de formato HTML
  .filter-wrapper,
  .filter-guidelines,
  a[href*="/filter/tips"],
  [id*="format-help"] {
    display: none !important;
  }
}
```

### Estilos de Label y Input

```scss
.slide-panel__body {
  .form-item {
    margin-bottom: 1.5rem;
  }
  
  label {
    font-weight: 600;
    color: var(--ej-color-text, #1F2937);
  }
  
  input[type="text"],
  textarea,
  select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--ej-border-color, #E5E7EB);
    border-radius: 8px;
    
    &:focus {
      border-color: var(--ej-color-primary, #FF8C42);
      box-shadow: 0 0 0 3px rgba(255, 140, 66, 0.15);
    }
  }
}
```

## Accesibilidad

- ✅ `role="dialog"` y `aria-modal="true"`
- ✅ `aria-labelledby` apuntando al título
- ✅ Focus trap en el primer input
- ✅ Cierre con tecla ESC
- ✅ Cierre con clic en overlay
- ✅ `body.slide-panel-open` bloquea scroll

## Lecciones Aprendidas (Actualizado 2026-01-29)

1. **Promoción al tema**: Componentes reutilizables deben vivir en el tema, no en módulos específicos.
2. **Drupal.detachBehaviors()**: Llamar antes de limpiar el body del panel para evitar acumulación de scripts admin.
3. **Rutas AJAX específicas**: Usar rutas dedicadas (ej. `mymodule.entity.add.frontend`) que detecten AJAX.
4. **Hook Form Alter amplio**: Usar `str_contains($form_id, 'entity_name')` en vez de array exacto.
5. **Formato completo oculto**: Usar `$element['format']['#access'] = FALSE` para ocultar todo el subtree.
6. **CSS como respaldo**: Siempre añadir CSS `display: none` para elementos persistentes.
7. **Library dependencies**: El módulo que usa slide-panel debe declarar dependencia:
   ```yaml
   dependencies:
     - ecosistema_jaraba_theme/slide-panel
   ```
