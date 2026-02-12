# Aprendizajes: Dashboard Experimentos A/B (Gap 2)

**Fecha:** 2026-01-30  
**Módulo:** `jaraba_page_builder`  
**Contexto:** Implementación del dashboard frontend para el sistema de A/B Testing

---

## 1. Problema Resuelto

El dashboard de Experimentos A/B necesitaba:
- Layout full-width sin sidebar ni regiones de admin
- Header premium con partículas visible (no cortado por navbar fija)
- Iconos SVG representativos (no emojis fallback)
- KPIs interactivos con enlaces a vistas filtradas
- Botón "Nuevo Experimento" posicionado a la derecha del header

---

## 2. Soluciones Implementadas

### 2.1 Template Full-Width

**Archivo:** `ecosistema_jaraba_theme.theme`

```php
// Hook preprocess_html para añadir clases body
$page_builder_frontend_routes = [
    'jaraba_page_builder.template_picker',
    'jaraba_page_builder.template_preview',
    'jaraba_page_builder.create_from_template',
    'jaraba_page_builder.my_pages',
    'jaraba_page_builder.experiments_dashboard', // Añadido Gap 2
];

if (in_array($route, $page_builder_frontend_routes)) {
    $variables['attributes']['class'][] = 'dashboard-page';
    $variables['attributes']['class'][] = 'page-page-builder';
    if ($route === 'jaraba_page_builder.experiments_dashboard') {
        $variables['attributes']['class'][] = 'page-experiments';
        $variables['attributes']['class'][] = 'experiments-dashboard-page';
    }
}
```

### 2.2 Compensar Navbar Fija

**Archivo:** `_experiments-dashboard.scss`

```scss
.dashboard-header--premium {
    min-height: 200px;
    display: flex;
    align-items: center;
    // Compensar navbar fija (72px)
    margin-top: var(--ej-header-height, 72px);
    padding-top: 4rem;
}
```

### 2.3 Layout Horizontal del Header

**Problema:** El `flex-direction: column` de otro CSS causaba que el botón apareciera debajo del título.

**Solución:** Forzar con `!important`:

```scss
.dashboard-header__content {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    justify-content: space-between !important;
}
```

### 2.4 Iconos SVG vs Emoji Fallback

**Problema:** El icono `add` no existía, mostraba emoji 📌.

**Solución:** Usar icono existente `plus`:

```twig
{# ❌ Antes (no existía, mostraba fallback) #}
{{ jaraba_icon('actions', 'add', { color: 'white' }) }}

{# ✅ Después (SVG existente) #}
{{ jaraba_icon('actions', 'plus', { color: 'white' }) }}
```

**Verificar iconos disponibles:**
```bash
ls web/modules/custom/ecosistema_jaraba_core/images/icons/{category}/
```

### 2.5 KPIs Clickables

**Archivo:** `experiment-dashboard.html.twig`

```twig
{# Antes: div estático #}
<div class="kpi-card">...</div>

{# Después: enlace con filtro #}
<a href="?status=running" class="kpi-card kpi-card--clickable">
    ...
</a>
```

**SCSS para clickables:**

```scss
.kpi-card--clickable {
    text-decoration: none;
    cursor: pointer;
    
    &:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
    }
}
```

---

## 3. Reglas Clave Aprendidas

### 3.1 Clases Body = preprocess_html

> ⚠️ **NUNCA** usar `attributes.addClass()` en page.html.twig para el body.
> Drupal renderiza el `<body>` en `html.html.twig`, no en `page.html.twig`.
> **SIEMPRE usar `hook_preprocess_html()`**.

### 3.2 Verificar Iconos Antes de Usar

El sistema de iconos (`jaraba_icon()`) muestra un emoji fallback si el SVG no existe.
Verificar disponibilidad en:
```
web/modules/custom/ecosistema_jaraba_core/images/icons/{category}/{name}.svg
```

### 3.3 CSS de Múltiples Módulos

El proyecto compila SCSS por separado en:
- `ecosistema_jaraba_theme/` → tema principal
- `ecosistema_jaraba_core/` → estilos de componentes (dashboards, etc.)

**Recordar compilar ambos cuando se modifiquen estilos:**

```bash
# Tema
cd web/themes/custom/ecosistema_jaraba_theme && npm run build

# Módulo core
cd web/modules/custom/ecosistema_jaraba_core && npm run build
```

---

## 4. Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme.theme` | Añadida ruta experiments al array preprocess_html |
| `_experiments-dashboard.scss` | margin-top, padding-top, flex-direction, kpi-card--clickable |
| `experiment-dashboard.html.twig` | KPIs como enlaces, icono plus, variant duotone |

---

## 5. Verificación Visual

El dashboard final muestra:
- ✅ Título "Experimentos A/B" con icono duotone A/B
- ✅ Botón "Nuevo Experimento" a la derecha con icono +
- ✅ 4 KPIs clickables con iconos duotone
- ✅ Sección "Mis Experimentos" con estado vacío
- ✅ Layout full-width sin sidebar

---

## Referencias

- [Frontend Pages Pattern](./2026-01-29_frontend_pages_pattern.md)
- [Site Builder Frontend Full-Width](./2026-01-29_site_builder_frontend_fullwidth.md)
- [Page Builder Auditoría Clase Mundial](./2026-01-28_page_builder_auditoria_clase_mundial.md)
