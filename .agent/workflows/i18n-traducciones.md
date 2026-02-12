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

---

## Lecciones Aprendidas (2026-01-13)

### 1. Texto Base en Idioma Principal

Para proyectos con un solo idioma (ej. español), usar el texto final directamente:

```twig
{# ✅ Preferido para proyectos hispanoparlantes #}
{% trans %}Analítica de Inquilinos{% endtrans %}

{# ⚠️ Evitar si no vas a gestionar traducciones activamente #}
{% trans %}Tenant Analytics{% endtrans %}
```

**Razón**: El sistema de traducciones de Drupal requiere configuración adicional. Si el texto base está en español, funciona inmediatamente.

### 2. Abreviaturas y Unidades

Las abreviaturas deben estar traducidas:

| ❌ Incorrecto | ✅ Correcto |
|--------------|-------------|
| `5.1 mo` | `5.1 {% trans %}meses{% endtrans %}` |
| `1 tenant` | `1 {% trans %}inquilino{% endtrans %}` |
| `Healthy` | `{% trans %}Saludable{% endtrans %}` |
| `At Risk` | `{% trans %}En Riesgo{% endtrans %}` |

### 3. Glosarios Explicativos

Para acrónimos técnicos (MRR, LTV, CAC), incluir un glosario visible:

```html
<div class="finops-legend-box">
  <div class="finops-legend-box__title">📖 Glosario de Métricas</div>
  <div class="finops-legend-box__item">
    <strong>MRR</strong>: {% trans %}Ingresos Recurrentes Mensuales{% endtrans %}
  </div>
</div>
```

### 4. Dark Themes y Legibilidad

En dashboards con tema oscuro, asegurar color de texto explícito:

```scss
// ⚠️ El texto puede heredar colores oscuros del tema Drupal
.finops-table td {
    font-size: $ej-font-size-sm;
    color: $finops-text; // ✅ Explícito: blanco
}
```

### 5. Variables en Render Array

Las variables para Twig deben añadirse **explícitamente** al render array:

```php
// ❌ El método genera datos pero no llegan al template
$unit_economics = $this->getUnitEconomics($tenants);

// ✅ Deben añadirse al render array
return [
    '#unit_economics' => $finops_data['unit_economics'],
    '#vertical_profitability' => $finops_data['vertical_profitability'],
];
```

### 6. Compilación SCSS

Los cambios en archivos `.scss` no surten efecto hasta compilarlos:

```bash
# Comando de compilación
cd web/modules/custom/ecosistema_jaraba_core
npx sass scss/main.scss:css/ecosistema-jaraba-core.css --style=compressed
```

Después de compilar, limpiar caché de Drupal Y del navegador.
