---
description: Cómo manejar traducciones en Drupal (i18n)
---

# Directrices de Internacionalización (i18n)

## Regla Principal

**Todos los textos visibles al usuario deben ser traducibles.**

## En Controladores PHP

Usar `$this->t()` para textos en controladores:

```php
// ✅ Correcto
return [
  '#title' => $this->t('Panel de Salud'),
  '#labels' => [
    'refresh' => $this->t('Actualizar'),
    'latency' => $this->t('Latencia'),
  ],
];

// ❌ Incorrecto
return [
  '#title' => 'Panel de Salud',
];
```

## En Templates Twig

Usar `{% trans %}` para textos en templates:

```twig
{# ✅ Correcto #}
<h1>{% trans %}Panel de Salud{% endtrans %}</h1>
<button title="{% trans %}Actualizar{% endtrans %}">{% trans %}Actualizar{% endtrans %}</button>

{# ❌ Incorrecto #}
<h1>Panel de Salud</h1>
```

## En JavaScript

Usar `Drupal.t()`:

```javascript
// ✅ Correcto
const message = Drupal.t('Datos actualizados');

// ❌ Incorrecto
const message = 'Datos actualizados';
```

## Gestión de Traducciones

Las traducciones se gestionan desde:
- `/admin/config/regional/translate`

## Prioridad

1. **Preferir pasar textos desde el controlador** usando `$this->t()` 
2. Si no es posible, usar `{% trans %}` en Twig
3. Para textos dinámicos en JS, usar `Drupal.t()`
