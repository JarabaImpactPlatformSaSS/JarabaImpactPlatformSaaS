# 🎯 Plan de Auditoría Frontend Multidisciplinar

**Fecha:** 2026-01-26  
**Versión:** 1.0.0  
**Estado:** APROBADO PARA EJECUCIÓN

---

## 📋 Resumen Ejecutivo

Auditoría exhaustiva del frontend del SaaS para verificar:
1. Separación correcta de responsabilidades `jaraba_theming` (módulo) vs `ecosistema_jaraba_theme` (tema)
2. Cumplimiento de directrices de iconos SVG, paleta de colores, i18n, SCSS inyectable
3. Extensión del diseño premium de homepage a todas las páginas
4. Verificación del Copilot en todos los contextos
5. Actualización de documentación del proyecto

---

## 1. Arquitectura Módulo vs Tema

### Distribución Actual (CORRECTA ✅)

| Componente | Ubicación | Responsabilidad |
|------------|-----------|-----------------|
| **jaraba_theming** (Módulo) | `web/modules/custom/jaraba_theming/` | Lógica PHP de inyección: CSS tokens, clases dinámicas, context vertical |
| **ecosistema_jaraba_theme** (Tema) | `web/themes/custom/ecosistema_jaraba_theme/` | Assets visuales: Templates Twig, SCSS bundle, JS behaviors, iconos |

### Inventario jaraba_theming (Módulo)
- `ThemeTokenService.php` - Inyección de CSS variables
- `jaraba_theming.routing.yml` - Rutas de configuración del tema
- `css/` - CSS mínimo para funcionalidad (NO estilos visuales)
- `js/` - JS de comportamientos dinámicos

### Inventario ecosistema_jaraba_theme (Tema)
- **Templates**: `page--front.html.twig`, `page--dashboard.html.twig`, 12 partials
- **SCSS**: 13 componentes en `scss/components/`, main.scss entry point
- **JS**: `mobile-menu.js`, `scroll-animations.js`
- **70+ opciones** de configuración visual en UI

> **Regla Arquitectónica Verificada**: El módulo `jaraba_theming` maneja la LÓGICA de inyección, el tema `ecosistema_jaraba_theme` maneja los ASSETS visuales.

---

## 2. Matriz de Auditoría de Páginas Frontend

### Rutas Principales Identificadas (Público + Dashboards)

| Ruta | Módulo | Tipo Template | Iconos SVG | Paleta OK | i18n | Variables CSS | Estado |
|------|--------|---------------|------------|-----------|------|---------------|--------|
| `/` | Core | Clean Canvas ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Verificado |
| `/demo` | Core | Clean Canvas | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/marketplace` | Core | Clean Canvas ✅ | ✅ | ✅ | ✅ | ✅ | ✅ Verificado |
| `/jobseeker` | jaraba_candidate | Clean Canvas | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/my-profile` | jaraba_candidate | Standard | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/jobs` | jaraba_job_board | Standard | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/employer` | jaraba_job_board | Clean Canvas | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/my-company` | jaraba_job_board | Clean Canvas | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/entrepreneur/dashboard` | jaraba_business_tools | Clean Canvas | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/paths` | jaraba_paths | TBD | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/mentoring` | jaraba_mentoring | TBD | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/courses` | jaraba_lms | TBD | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/tenant/dashboard` | Core | Standard | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |
| `/my-dashboard` | Core | Standard | ⚠️ | ⚠️ | ⚠️ | ⚠️ | 🔍 Pendiente |

### Criterios de Auditoría

1. **Iconos SVG**: Uso de `jaraba_icon()` en lugar de emojis Unicode
2. **Paleta OK**: Colores solo via `var(--ej-*)` o paleta Jaraba (corporate, impulse, innovation, agro)
3. **i18n**: Textos con `{% trans %}` o `|t` filter
4. **Variables CSS**: Sin colores hardcodeados, uso de design tokens inyectables
5. **Template Type**: Clean Canvas (sin regiones Drupal) vs Standard (con regiones)

---

## 3. Plan de Implementación

### Fase 1: Verificación Browser (1-2h)

```bash
# Cache rebuild
docker exec jarabasaas_appserver_1 drush cr
```

Para cada ruta:
- Navegar a `https://jaraba-saas.lndo.site{ruta}`
- Inspeccionar: iconos, colores, traducciones, consola JS
- Documentar incumplimientos en matriz

### Fase 2: Remediation

#### Emojis → `jaraba_icon()`
```twig
{# ANTES #}
<span class="icon">🚀</span>

{# DESPUÉS #}
{{ jaraba_icon('actions', 'rocket', { color: 'impulse', size: '24px' }) }}
```

#### Colores hardcodeados → Variables CSS
```scss
// ANTES
.card { background: #FF8C42; }

// DESPUÉS
.card { background: var(--ej-color-impulse, #FF8C42); }
```

#### Textos sin traducir → i18n
```twig
{# ANTES #}
<h2>My Dashboard</h2>

{# DESPUÉS #}
<h2>{% trans %}My Dashboard{% endtrans %}</h2>
```

### Fase 3: Extensión Premium a Clean Canvas (2-3h)

Para páginas que requieren diseño premium, añadir theme suggestions en `.theme`:

```php
function ecosistema_jaraba_theme_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  $route = \Drupal::routeMatch()->getRouteName();
  
  $clean_routes = [
    'jaraba_candidate.dashboard' => 'page__jobseeker',
    'jaraba_job_board.employer_dashboard' => 'page__employer',
    'jaraba_business_tools.entrepreneur_dashboard' => 'page__entrepreneur__dashboard',
  ];
  
  if (isset($clean_routes[$route])) {
    $suggestions[] = $clean_routes[$route];
  }
}
```

### Fase 4: Copilot Proactividad (1h)

Verificar en cada contexto:
- `/` - Landing copilot (general)
- `/jobseeker` - Career copilot
- `/employer` - Recruiter copilot
- `/entrepreneur/dashboard` - Entrepreneur copilot (5 modos)

---

## 4. Verificación Automatizada

```bash
# Desde WSL
cd /home/PED/JarabaImpactPlatformSaaS

# Buscar emojis pendientes de migrar
grep -rn "📚\|🚀\|💼\|📆\|📊\|🎯\|🔧\|💡\|📈\|🏆" web/themes/custom/ecosistema_jaraba_theme/templates/
grep -rn "📚\|🚀\|💼\|📆\|📊\|🎯\|🔧\|💡\|📈\|🏆" web/modules/custom/*/templates/

# Buscar colores hardcodeados en SCSS
grep -rn "#[0-9A-Fa-f]\{6\}" web/themes/custom/ecosistema_jaraba_theme/scss/ | grep -v "_variables.scss"

# Verificar uso de jaraba_icon()
grep -rn "jaraba_icon" web/themes/custom/ecosistema_jaraba_theme/templates/
```

---

## 5. Archivos de Referencia

| Archivo | Descripción |
|---------|-------------|
| [page--front.html.twig](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig) | Homepage premium de referencia |
| [ecosistema_jaraba_theme.theme](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme) | 70+ opciones de configuración |
| [2026-01-25_arquitectura_frontend_extensible.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/tecnicos/2026-01-25_arquitectura_frontend_extensible.md) | Arquitectura extensible |
| [00_DIRECTRICES_PROYECTO.md](file:///z:/home/PED/JarabaImpactPlatformSaaS/docs/00_DIRECTRICES_PROYECTO.md) | Directrices del proyecto |

---

## 6. Estimación de Tiempo

| Fase | Tiempo |
|------|--------|
| Fase 1: Verificación Browser | 1-2h |
| Fase 2: Remediation | 2-4h |
| Fase 3: Extensión Premium | 2-3h |
| Fase 4: Copilot Proactividad | 1h |
| **TOTAL** | **7-11h** |

---

## Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-26 | 1.0.0 | Documento inicial - Matriz de 14 rutas, arquitectura verificada |
