# Aprendizaje #128: Meta-Site Nav Fix + Copilot Link Buttons

**Fecha:** 2026-02-26
**Sesion:** Continuacion de sesiones de meta-sitio jarabaimpact.com
**Contexto:** Navegacion invisible en meta-sitios + Copilot sin botones de accion directos

---

## Problema 1: Navegacion de Meta-Sitios No Visible

### Sintomas
- Los meta-sitios pepejaraba.com y jarabaimpact.com no mostraban menu de navegacion
- MetaSiteResolverService retornaba datos correctos (verificado con debug logging)
- `theme_preprocess_page()` establecia `$variables['theme_settings']['navigation_items']` correctamente
- La variable `meta_site` era truthy en el template

### Root Cause (Doble)

**1. Header Inline Hardcodeado en Page Template:**
El template `page--page-builder.html.twig` tenia un header inline con clase `landing-header--tenant` que solo renderizaba logo + acciones de login. NO usaba `_header.html.twig` partial, por lo que ignoraba completamente las variables de navegacion establecidas en preprocess.

```twig
{# ANTES — header inline SIN navegacion #}
<header class="landing-header landing-header--tenant" role="banner">
  <div class="landing-header__container">
    <a href="{{ path('<front>') }}" class="landing-header__brand">...</a>
    <div class="landing-header__actions">
      {# Solo login/logout, SIN <nav> #}
    </div>
  </div>
</header>
```

**2. header_type = 'minimal' en SiteConfig:**
Incluso despues de corregir el template para usar `_header.html.twig`, el layout `minimal` (definido en `_header-minimal.html.twig`) solo muestra logo + hamburguesa — NO renderiza `<nav class="landing-header__nav">` con items horizontales.

### Solucion

**Fix 1 — Template condicional:**
```twig
{% if meta_site %}
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    site_slogan: site_slogan|default(''),
    logo: logo,
    logged_in: logged_in,
    theme_settings: theme_settings
  } %}
{% else %}
  {# Header limpio para page-builder dashboard/preview #}
  <header class="landing-header landing-header--tenant">...</header>
{% endif %}
```

**Fix 2 — header_type a classic:**
```sql
UPDATE site_config SET header_type = 'classic' WHERE id IN (1, 2);
```

### Cadena Completa de Navegacion
```
theme_preprocess_page()
  → Route match: entity.page_content.canonical
  → $page_content = \Drupal::routeMatch()->getParameter('page_content')
  → MetaSiteResolverService::resolveFromPageContent($page_content)
  → Returns: site_name, navigation_items, header_layout, CTA, footer, logo
  → Override $variables['theme_settings']['navigation_items']
  → Override $variables['meta_site'] = TRUE

_header.html.twig (dispatcher)
  → Parse navigation_items: "Texto|URL\n" → array [{text, url}]
  → Validate layout against whitelist ['classic', 'minimal', 'transparent']
  → Include _header-{layout}.html.twig

_header-classic.html.twig
  → Renders <nav class="landing-header__nav"> with nav_items
  → Shows CTA button if enable_header_cta
  → Mobile hamburger toggle
```

### Verificacion
- pepejaraba: Inicio, Manifiesto, Metodo, Casos de exito, Blog, Contacto + CTA "Acceder al Ecosistema"
- jarabaimpact: Inicio, Plataforma, Certificacion, Impacto, Programas, Contacto + CTA "Solicita una Demo"
- jaraba-saas: Empleo, Talento, Emprender, Comercio, Instituciones (default SaaS nav)

---

## Problema 2: Copilot Sin Botones de Accion Directos

### Sintomas
- Las respuestas del copilot solo mostraban sugerencias como texto clickeable que se enviaba como mensaje
- No habia CTAs directos tipo "Crear cuenta gratis" → /user/register

### Solucion: Formato Dual String/URL

**Frontend (JS v1 — contextual-copilot.js):**
```javascript
function showSuggestionButtons(suggestions) {
  suggestions.forEach(s => {
    const item = typeof s === 'string' ? { label: s } : s;
    if (item.url) {
      // Render como <a> link
      const link = document.createElement('a');
      link.className = 'suggestion-btn suggestion-btn--link';
      link.href = item.url;
      link.textContent = item.label;
      if (isExternal(item.url)) {
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
      }
    } else {
      // Render como <button> que envia mensaje
    }
  });
}
```

**Frontend (JS v2 — copilot-chat-widget.js):**
Misma logica pero con template string HTML + `escapeHtml()` + icono SVG flecha.

**Backend (CopilotOrchestratorService.php):**
```php
protected function getContextualActionButtons(string $mode): array
{
    if (!$isAuthenticated) {
        return [['label' => 'Crear cuenta gratis', 'url' => '/user/register']];
    }
    $modeActions = [
        'coach' => [['label' => 'Mi perfil', 'url' => '/user']],
        'consultor' => [['label' => 'Mi dashboard', 'url' => '/user']],
        'cfo' => [['label' => 'Panel financiero', 'url' => '/emprendimiento/dashboard']],
        'landing_copilot' => [['label' => 'Explorar plataforma', 'url' => '/']],
    ];
    return $modeActions[$mode] ?? [];
}
```

**CSS (ambos modulos):**
```scss
.suggestion-btn--link {
    background: var(--ej-color-impulse, #FF8C42);
    color: #ffffff;
    border-color: var(--ej-color-impulse, #FF8C42);
    font-weight: 600;
    &:hover { filter: brightness(1.1); transform: translateY(-1px); }
}
```

---

## Reglas Nuevas

| ID | Prioridad | Descripcion |
|----|-----------|-------------|
| META-SITE-NAV-001 | P1 | page--page-builder DEBE incluir _header.html.twig para meta-sites |
| COPILOT-LINK-001 | P1 | Sugerencias copilot soportan {label, url} + CTAs contextuales |
| HEADER-LAYOUT-NAV-001 | P1 | header_type classic para nav visible, minimal solo hamburguesa |

## Reglas de Oro

- #46: Meta-site nav requiere header partial + classic layout
- #47: Copilot sugerencias con URL action buttons

---

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `page--page-builder.html.twig` | Condicional `{% if meta_site %}` para header partial |
| `contextual-copilot.js` | Soporte `{label, url}` en sugerencias |
| `copilot-chat-widget.js` | Soporte `{label, url}` en sugerencias |
| `_contextual-copilot.scss` | Estilos `.suggestion-btn--link`, `.chat-suggestions` |
| `_copilot-chat-widget.scss` | Estilos `.copilot-chat__suggestion-btn--link` |
| `ecosistema-jaraba-core.css` | Recompilado |
| `copilot-v2.css` | Recompilado |
| `CopilotOrchestratorService.php` | `getContextualActionButtons()`, merge en `formatResponse()` |
| `PublicCopilotController.php` | `getDefaultSuggestions()` migrado a `{label, url}` |

## Tecnicas de Debug Utiles

1. **File-based logging en preprocess:** Escribir a `/tmp/debug.log` dentro del contenedor Lando, leer con `lando ssh -c "cat /tmp/debug.log"`
2. **SQL directo para SiteConfig:** `lando mysql --database=drupal_jaraba -e "SELECT id, header_type FROM site_config"`
3. **Verificar header_type antes de asumir bug en nav:** El layout minimal es una causa silenciosa de nav ausente

## Cross-refs
- Directrices v76.0.0
- Flujo v31.0.0
- Indice v101.0.0
