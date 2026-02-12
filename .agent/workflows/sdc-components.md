---
description: Sistema SDC (Single Directory Components) con Compound Variants para componentes reutilizables
---

# Workflow: SDC Components con Compound Variants

> [!CAUTION]
> ## ⛔ REGLA INQUEBRANTABLE
> **Todos los componentes visuales DEBEN seguir el patrón SDC de Drupal 11.**
> Un solo template con múltiples variantes (Compound Variants), NO templates separados por variante.

## 📁 Estructura SDC

Cada componente vive en su propio directorio con 3 archivos:

```
ecosistema_jaraba_theme/components/{nombre}/
├── {nombre}.component.yml   ← Definición de props y slots
├── {nombre}.twig            ← Template unificado
└── {nombre}.scss            ← Estilos (NO .css)
```

## 📋 Archivo component.yml

Define props tipados y slots:

```yaml
$schema: 'https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json'
name: Card
status: stable
description: 'Componente Card con múltiples variantes'
group: Jaraba Components

props:
  type: object
  properties:
    variant:
      type: string
      title: Variante
      default: default
      enum:
        - default
        - product
        - profile
        # ... más variantes

slots:
  header:
    title: Header
  content:
    title: Content

libraryOverrides:
  css:
    component:
      {nombre}.scss: {}
```

## 🎨 Compound Variants en Twig

Un SOLO template maneja todas las variantes:

```twig
{# Construir clases dinámicamente #}
{% set classes = [
  'card',
  'card--' ~ (variant|default('default')),
  'card--' ~ (size|default('md')),
  elevated ? 'card--elevated',
] | filter(v => v) | join(' ') %}

<article class="{{ classes }}">
  {# Contenido condicional por variante #}
  {% if variant == 'product' %}
    {# Lógica específica de product #}
  {% elseif variant == 'profile' %}
    {# Lógica específica de profile #}
  {% else %}
    {# Lógica default #}
  {% endif %}
</article>
```

## ✅ Checklist SDC

Antes de crear un componente SDC:

- [ ] ¿Creé los 3 archivos (.yml, .twig, .scss)?
- [ ] ¿Usé `.scss` (NO .css)?
- [ ] ¿Definí props tipados en component.yml?
- [ ] ¿Usé slots para contenido flexible?
- [ ] ¿El template maneja todas las variantes con condicionales?
- [ ] ¿Usé `{% trans %}` para textos traducibles?
- [ ] ¿Usé `jaraba_icon('category', 'name', {options})`?
- [ ] ¿Usé paleta Jaraba (corporate, impulse, innovation, agro)?
- [ ] ¿Usé variables inyectables `var(--ej-*)`?

## 📦 Componentes SDC Disponibles

| Componente | Variantes | Ubicación |
|------------|-----------|-----------|
| Card | 8 (default, product, profile, metric, course, testimonial, cta, horizontal) | `components/card/` |
| Hero | 5 (split, fullscreen, compact, animated, slider) | `components/hero/` |

## 🔗 Uso en Templates

```twig
{# Incluir componente SDC #}
{% include 'ecosistema_jaraba_theme:card' with {
  variant: 'product',
  title: 'Aceite de Oliva',
  price: 12.50,
  image: '/path/to/image.jpg'
} %}

{# Con slots #}
{% embed 'ecosistema_jaraba_theme:card' with { variant: 'default' } %}
  {% block content %}
    <p>Contenido personalizado</p>
  {% endblock %}
{% endembed %}
```

## Lecciones Aprendidas (2026-01-23)

1. **Compound Variants > Templates separados**: Mejor mantenibilidad con un solo archivo.
2. **Props tipados**: Previenen errores y documentan automáticamente.
3. **Slots**: Máxima flexibilidad sin sacrificar estructura.
