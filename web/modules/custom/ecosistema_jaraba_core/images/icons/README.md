# Jaraba Icon Library

Sistema de iconos premium para la plataforma Jaraba SaaS.

## Uso en Twig

```twig
{# Icono básico #}
{{ jaraba_icon('business', 'diagnostic') }}

{# Con opciones #}
{{ jaraba_icon('analytics', 'gauge', {
  variant: 'filled',
  size: '32px',
  class: 'my-custom-class'
}) }}

{# Solo obtener la ruta #}
<img src="{{ jaraba_icon_path('ai', 'brain', 'duotone') }}" alt="IA">
```

## Categorías

| Categoría | Iconos |
|-----------|--------|
| `business` | diagnostic, pathway, progress, achievement, company |
| `analytics` | gauge, radar, chart-bar, chart-line, trend-up |
| `actions` | check, arrow-right, download, refresh, plus, edit, trash |
| `ai` | brain, sparkle, copilot, automation, assistant |
| `ui` | menu, close, search, filter, settings, user, bell |
| `verticals` | agro, employment, entrepreneurship |

## Variantes

- `outline` (default) - Trazo fino 1.5px
- `outline-bold` - Trazo grueso 2.5px  
- `filled` - Relleno sólido
- `duotone` - Dos tonos (primario + secundario)

## Estilo Visual

- Color primario: Indigo (#4F46E5)
- Color secundario: Violeta (#7C3AED)
- Esquinas redondeadas: 2-3px
- Base: 24×24px (escalable)

## Estructura de Archivos

```
images/icons/
├── business/
│   ├── diagnostic.svg
│   ├── diagnostic-bold.svg
│   ├── diagnostic-filled.svg
│   └── diagnostic-duotone.svg
├── analytics/
├── actions/
├── ai/
├── ui/
└── verticals/
```
