# Plan de Implementacion: Elevacion a Clase Mundial — Plataforma de Ecosistemas Jaraba

**Fecha:** 2026-02-27
**Version:** 1.0.0
**Estado:** Pendiente de aprobacion
**Alcance:** UX Polish, Activacion, Real-Time, Busqueda Global, Bottom Nav Mobile
**Directrices base:** v93.0.0 | **Flujo:** v46.0.0 | **Indice:** v120.0.0

---

## Indice de Navegacion (TOC)

1. [Contexto y Justificacion](#1-contexto-y-justificacion)
2. [Auditoria de Brechas vs Clase Mundial](#2-auditoria-de-brechas-vs-clase-mundial)
3. [Codigo Existente Reutilizable](#3-codigo-existente-reutilizable)
4. [FASE 1: Skeleton Screens](#4-fase-1-skeleton-screens)
5. [FASE 2: Empty States con CTAs Contextuales](#5-fase-2-empty-states-con-ctas-contextuales)
6. [FASE 3: Micro-interacciones y Transiciones CSS](#6-fase-3-micro-interacciones-y-transiciones-css)
7. [FASE 4: Error Recovery UX](#7-fase-4-error-recovery-ux)
8. [FASE 5: Bottom Navigation Mobile](#8-fase-5-bottom-navigation-mobile)
9. [FASE 6: Centro de Notificaciones](#9-fase-6-centro-de-notificaciones)
10. [FASE 7: Busqueda Global Mejorada](#10-fase-7-busqueda-global-mejorada)
11. [FASE 8: Onboarding Quick-Start Overlay](#11-fase-8-onboarding-quick-start-overlay)
12. [Tabla de Correspondencia — Especificaciones Tecnicas](#12-tabla-de-correspondencia)
13. [Tabla de Cumplimiento de Directrices](#13-tabla-de-cumplimiento-de-directrices)
14. [Verificacion End-to-End](#14-verificacion-end-to-end)
15. [Ficheros Criticos — Resumen de Cambios](#15-ficheros-criticos)

---

## 1. Contexto y Justificacion

### 1.1 Situacion Actual

La Plataforma de Ecosistemas Jaraba es un SaaS multi-vertical en Drupal 11 con 91 modulos custom, 406 entidades, 795+ servicios, 7 verticales de negocio, IA nativa (RAG/Qdrant, agentes autonomos, Copilot v2), y un ecosistema multi-dominio de 4 sitios. Tras una auditoria exhaustiva como 15 consultores senior simultaneos, comparando contra lideres del mercado (Salesforce, HubSpot, Shopify, Notion, Intercom, Sopact, Docebo, F6S, Teachable, LinkedIn Talent, Jasper), se identificaron brechas criticas en la capa de presentacion y experiencia de usuario que impiden alcanzar el nivel de clase mundial.

### 1.2 Problema

El backend y la logica de negocio de la plataforma son robustos (795+ servicios, IA nativa, multi-tenancy completa). Sin embargo, la capa de UX presenta deficit en areas que los usuarios perciben en los primeros 3-5 segundos de interaccion:

- **0% skeleton screens** — El usuario ve pantallas en blanco durante la carga de datos via fetch/AJAX
- **0% empty states informativos** — Dashboards vacios sin guia ni CTA que orienten al usuario
- **~30% micro-interacciones** — Solo hover basicos; faltan transiciones en modales, tabs, acordeones, cards
- **~40% error recovery** — Mensajes genericos sin boton "Reintentar" ni fallback contextual
- **Bottom nav mobile CSS existe pero sin template** — 127 lineas de CSS listas en `_mobile-components.scss` sin Twig que las renderice
- **Centro de notificaciones ausente** — El icono de campana (`_proactive-insights-bell.html.twig`) existe pero no hay panel desplegable
- **Busqueda global basica** — El command bar (`_command-bar.html.twig` + `command-bar.js`) existe pero la busqueda backend es limitada

### 1.3 Objetivo

Cerrar las brechas identificadas para elevar la plataforma al nivel de Stripe/HubSpot/Notion, priorizando:
1. Impacto visual inmediato (skeleton + empty states + micro-interacciones)
2. Reduccion de friction mobile (bottom nav)
3. Informacion proactiva (notificaciones)
4. Productividad power-user (busqueda global mejorada)

### 1.4 Principio de Diseno

Cada componente nuevo se implementa como:
- **Twig partial** (`_nombre.html.twig`) reutilizable via `{% include %}` desde cualquier pagina
- **SCSS parcial** (`_nombre.scss`) con `var(--ej-*)` + fallback SCSS, compilado via Dart Sass
- **JS behavior** (`Drupal.behaviors.nombre`) con patron `once()`, CSRF cache, XSS prevention
- **Sin regiones ni bloques Drupal** — layout limpio full-width
- **Textos siempre traducibles** — `{% trans %}...{% endtrans %}` en Twig, `Drupal.t()` en JS
- **Variables configurables desde UI** — Theme Settings Tab o SiteConfig entity

---

## 2. Auditoria de Brechas vs Clase Mundial

| Area | Estado Actual | Clase Mundial (Stripe/HubSpot/Notion) | Brecha | Fase |
|------|--------------|---------------------------------------|--------|------|
| Skeleton Screens | 0% — pantalla en blanco durante carga | 100% shimmer en toda carga async | CRITICA | 1 |
| Empty States | 0% — dashboards vacios sin guia | 100% con ilustracion + CTA contextual | CRITICA | 2 |
| Micro-interacciones | ~30% — solo hover basicos | 90%+ transiciones en modales, tabs, cards | ALTA | 3 |
| Error Recovery | ~40% — mensajes genericos Drupal | 95% retry inline, fallback contextual | ALTA | 4 |
| Bottom Nav Mobile | CSS 100% listo, template 0% | 100% bottom nav + FAB + badges | ALTA | 5 |
| Notificaciones | Bell icon existe, panel 0% | Panel con agrupacion, mark-read, acciones | MEDIA | 6 |
| Busqueda Global | Command bar basico, backend limitado | Fuzzy search cross-entity + recientes | MEDIA | 7 |
| Onboarding | Wizard 7 pasos existe, no hay quick-start | Overlay primera visita + checklist | MEDIA | 8 |

---

## 3. Codigo Existente Reutilizable

La auditoria revelo codigo ya implementado que solo necesita activarse o extenderse. Esto reduce drasticamente el esfuerzo:

| Recurso Existente | Ubicacion | Estado | Reutilizacion |
|-------------------|-----------|--------|---------------|
| Shimmer keyframes | `ecosistema_jaraba_theme/scss/components/_hero-landing.scss` | Listo | Fase 1 — base para skeleton screens |
| `.empty-state` class | `ecosistema_jaraba_core/scss/_jobseeker-dashboard.scss` | Parcial | Fase 2 — extender con variantes |
| `.bottom-nav` CSS completo | `ecosistema_jaraba_theme/scss/components/_mobile-components.scss:150-282` | CSS listo | Fase 5 — solo crear Twig partial |
| `.bottom-nav__badge` | `_mobile-components.scss:270-280` | CSS listo | Fase 5 — notification badge |
| `body.has-bottom-nav` padding | `_mobile-components.scss:280-282` | CSS listo | Fase 5 — body class via `hook_preprocess_html()` |
| Onboarding wizard 7 steps | `web/modules/custom/jaraba_onboarding/` | Funcional | Fase 8 — extender con quick-start |
| SSE streaming patron | `copilot-chat-widget.js` (EventSource) | Funcional | Fase 6 — patron para real-time |
| Toast notification system | `ecosistema_jaraba_core` | Funcional | Fase 4 — error recovery toasts |
| `_proactive-insights-bell.html.twig` | `templates/partials/` (1.5KB) | Funcional | Fase 6 — bell icon ya renderizado |
| `_command-bar.html.twig` + `command-bar.js` | `templates/partials/` + `js/` | Funcional | Fase 7 — extender backend |
| `modal-system` library | `ecosistema_jaraba_core.libraries.yml` | Funcional | Todas — CRUD en modal |
| Chart.js v4.4.1 | `ecosistema_jaraba_core.libraries.yml` (CDN canonical) | Funcional | Fase 6 — graficos si necesario |
| `jaraba_icon()` Twig function | `ecosistema_jaraba_core` | Funcional | Todas — iconos SVG |
| `Drupal.behaviors` + `once()` | Core Drupal | Estandar | Todas — JS attach pattern |

---

## 4. FASE 1: Skeleton Screens

### 4.1 Descripcion Funcional

Un skeleton screen es un placeholder animado que muestra la estructura de la pagina mientras los datos se cargan via AJAX/fetch. En lugar de una pantalla en blanco o un spinner generico, el usuario ve formas rectangulares con animacion shimmer que replican la forma del contenido que va a aparecer. Esto reduce la percepcion de espera en un 33% segun estudios de Nielsen Norman Group y es estandar en Stripe, HubSpot, Notion, y LinkedIn.

El componente se implementa como un partial Twig reutilizable con 4 variantes (card, stat, table, list) que se activa/desactiva via clase CSS `.is-loading` en el contenedor padre. No requiere cambios backend — es puramente frontend.

### 4.2 Variantes

| Variante | Uso | Estructura Visual |
|----------|-----|-------------------|
| `card` | Tarjetas de dashboard, marketplace, listados | Rectangulo imagen (ratio 16:9) + 3 lineas texto (ancho 100%, 80%, 60%) |
| `stat` | KPIs, metricas, counters | Circulo 48px + 2 lineas (ancho 60%, 40%) |
| `table` | Tablas de datos, admin listings | 5 filas x 4 columnas, header mas oscuro |
| `list` | Listas de items, notificaciones | 6 filas con circulo 32px + 2 lineas (ancho 70%, 50%) |

### 4.3 Ficheros a Crear

**4.3.1 Twig Partial: `_skeleton.html.twig`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_skeleton.html.twig`

Este partial recibe una variable `variant` (string) y renderiza la estructura shimmer correspondiente. Todos los textos de accesibilidad son traducibles con `{% trans %}`.

```twig
{# _skeleton.html.twig — Skeleton screen placeholder with shimmer animation #}
{# @param variant: 'card'|'stat'|'table'|'list' #}
{# @param count: number of skeleton items to render (default: 1) #}
{# Directivas: TWIG-XSS-001, BEM-NAMING-001, i18n translatable #}

{% set variant = variant|default('card') %}
{% set count = count|default(1) %}

<div class="ej-skeleton ej-skeleton--{{ variant }}" role="status" aria-label="{% trans %}Cargando contenido{% endtrans %}">
  <span class="ej-visually-hidden">{% trans %}Cargando...{% endtrans %}</span>

  {% for i in 1..count %}
    {% if variant == 'card' %}
      <div class="ej-skeleton__card">
        <div class="ej-skeleton__image"></div>
        <div class="ej-skeleton__line ej-skeleton__line--full"></div>
        <div class="ej-skeleton__line ej-skeleton__line--80"></div>
        <div class="ej-skeleton__line ej-skeleton__line--60"></div>
      </div>
    {% elseif variant == 'stat' %}
      <div class="ej-skeleton__stat">
        <div class="ej-skeleton__circle"></div>
        <div class="ej-skeleton__line ej-skeleton__line--60"></div>
        <div class="ej-skeleton__line ej-skeleton__line--40"></div>
      </div>
    {% elseif variant == 'table' %}
      <div class="ej-skeleton__table">
        <div class="ej-skeleton__table-header">
          {% for col in 1..4 %}
            <div class="ej-skeleton__cell ej-skeleton__cell--header"></div>
          {% endfor %}
        </div>
        {% for row in 1..5 %}
          <div class="ej-skeleton__table-row">
            {% for col in 1..4 %}
              <div class="ej-skeleton__cell"></div>
            {% endfor %}
          </div>
        {% endfor %}
      </div>
    {% elseif variant == 'list' %}
      <div class="ej-skeleton__list">
        {% for item in 1..6 %}
          <div class="ej-skeleton__list-item">
            <div class="ej-skeleton__circle ej-skeleton__circle--sm"></div>
            <div class="ej-skeleton__list-text">
              <div class="ej-skeleton__line ej-skeleton__line--70"></div>
              <div class="ej-skeleton__line ej-skeleton__line--50"></div>
            </div>
          </div>
        {% endfor %}
      </div>
    {% endif %}
  {% endfor %}
</div>
```

**4.3.2 SCSS Partial: `_skeleton.scss`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/scss/components/_skeleton.scss`

Principios SCSS: Usa `var(--ej-*)` con fallback SCSS segun `DESIGN-TOKEN-FEDERATED-001`. Reutiliza shimmer keyframes de `_hero-landing.scss`. Usa `@use 'sass:color'` para funciones modernas de Dart Sass (no `darken()`/`lighten()` — `COLOR-MIX-MIGRATION-001`). Sigue BEM naming (`BEM-NAMING-001`).

```scss
@use 'sass:color';

// Skeleton Screens — Shimmer loading placeholders
// Directivas: DESIGN-TOKEN-FEDERATED-001, BEM-NAMING-001, COLOR-MIX-MIGRATION-001
// Compilacion: npx sass scss/main.scss:css/main.css --style=compressed --no-source-map

$skeleton-bg: var(--ej-gray-200, #EEEEEE);
$skeleton-shimmer: var(--ej-gray-100, #F5F5F5);

@keyframes ej-shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

.ej-skeleton {
  // Container — hidden when content loads
  &.is-hidden { display: none; }
}

// Base skeleton element
%skeleton-element {
  background: linear-gradient(
    90deg,
    #{$skeleton-bg} 25%,
    #{$skeleton-shimmer} 50%,
    #{$skeleton-bg} 75%
  );
  background-size: 200% 100%;
  animation: ej-shimmer 1.5s ease-in-out infinite;
  border-radius: var(--ej-border-radius-sm, 6px);
}

.ej-skeleton__image {
  @extend %skeleton-element;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: var(--ej-border-radius, 10px) var(--ej-border-radius, 10px) 0 0;
  margin-bottom: var(--ej-spacing-sm, 8px);
}

.ej-skeleton__line {
  @extend %skeleton-element;
  height: 14px;
  margin-bottom: 8px;

  &--full { width: 100%; }
  &--80 { width: 80%; }
  &--70 { width: 70%; }
  &--60 { width: 60%; }
  &--50 { width: 50%; }
  &--40 { width: 40%; }
}

.ej-skeleton__circle {
  @extend %skeleton-element;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  flex-shrink: 0;

  &--sm {
    width: 32px;
    height: 32px;
  }
}

// Card variant
.ej-skeleton__card {
  background: var(--ej-bg-card, #ffffff);
  border-radius: var(--ej-border-radius, 10px);
  padding: var(--ej-spacing-md, 1rem);
  box-shadow: var(--ej-shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.06));
}

// Stat variant
.ej-skeleton__stat {
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-md, 1rem);
  padding: var(--ej-spacing-md, 1rem);
}

// Table variant
.ej-skeleton__table {
  width: 100%;
}

.ej-skeleton__table-header,
.ej-skeleton__table-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--ej-spacing-sm, 8px);
  padding: var(--ej-spacing-sm, 8px) 0;
}

.ej-skeleton__table-header {
  border-bottom: 1px solid var(--ej-border-color, #E0E0E0);
}

.ej-skeleton__cell {
  @extend %skeleton-element;
  height: 16px;

  &--header { height: 12px; opacity: 0.6; }
}

// List variant
.ej-skeleton__list-item {
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-sm, 8px);
  padding: var(--ej-spacing-sm, 8px) 0;
}

.ej-skeleton__list-text {
  flex: 1;
}

// Accessibility: screen reader text
.ej-visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
```

### 4.4 Ficheros a Modificar

**4.4.1 `ecosistema_jaraba_theme/scss/main.scss`**

Anadir import del nuevo partial despues de la linea de `_command-bar`:
```scss
@use 'components/skeleton';
```

**4.4.2 Dashboards que usan fetch (ejemplo de integracion)**

En cualquier template de dashboard que cargue datos via AJAX, el patron de uso es:

```twig
{# En el template del dashboard #}
<div class="dashboard-stats" id="dashboard-stats">
  {# Skeleton visible por defecto #}
  {% include '@ecosistema_jaraba_theme/partials/_skeleton.html.twig' with { variant: 'stat', count: 4 } %}
  {# Contenido real — oculto hasta que llegan datos #}
  <div class="dashboard-stats__content is-hidden"></div>
</div>
```

```javascript
// En el JS del dashboard (patron Drupal.behaviors)
Drupal.behaviors.dashboardStats = {
  attach: function(context) {
    const container = once('dashboard-stats', '#dashboard-stats', context);
    if (!container.length) return;

    const skeleton = container[0].querySelector('.ej-skeleton');
    const content = container[0].querySelector('.dashboard-stats__content');

    fetch(Drupal.url('api/v1/dashboard/stats'))
      .then(r => r.json())
      .then(data => {
        // Render real content
        content.innerHTML = renderStats(data); // con Drupal.checkPlain()
        content.classList.remove('is-hidden');
        skeleton.classList.add('is-hidden');
      });
  }
};
```

### 4.5 Compilacion SCSS

```bash
# Dentro del contenedor Docker (Lando):
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/main.css --style=compressed --no-source-map"
lando drush cr
```

### 4.6 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| DESIGN-TOKEN-FEDERATED-001 | `var(--ej-*)` con fallback SCSS en todos los valores |
| BEM-NAMING-001 | `.ej-skeleton`, `.ej-skeleton__line`, `.ej-skeleton__line--80` |
| COLOR-MIX-MIGRATION-001 | Sin `darken()`/`lighten()`, usa gradientes CSS |
| SCSS-COMPILE-001 | Dart Sass: `npx sass --style=compressed --no-source-map` |
| COMPILED-CSS-NEVER-EDIT-001 | Solo edita SCSS, nunca CSS directamente |
| i18n | `{% trans %}Cargando contenido{% endtrans %}` traducible |
| TWIG-XSS-001 | Sin `\|raw`, todo autoescape Twig |
| WCAG 2.1 AA | `role="status"`, `aria-label`, `.ej-visually-hidden` |
| ICON-EMOJI-001 | Sin emojis, solo CSS shapes |

---

## 5. FASE 2: Empty States con CTAs Contextuales

### 5.1 Descripcion Funcional

Un empty state es el componente que se muestra cuando una seccion del dashboard no tiene datos. En lugar de mostrar un espacio vacio (que confunde al usuario y genera abandono), se muestra una ilustracion SVG inline + titulo explicativo + descripcion orientativa + CTA primario que guia al usuario hacia la primera accion. El patron lo usan Notion ("No pages inside"), HubSpot ("Start by creating your first contact"), y Stripe ("No payments yet").

El componente se implementa como un partial Twig que recibe variables configurables (icono, titulo, descripcion, CTA). Cada vertical define sus propios empty states con textos y CTAs contextualizados.

### 5.2 Ficheros a Crear

**5.2.1 Twig Partial: `_empty-state.html.twig`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_empty-state.html.twig`

```twig
{# _empty-state.html.twig — Informative empty state with contextual CTA #}
{# @param icon: string — icon category/name for jaraba_icon() (e.g., 'ui', 'clipboard') #}
{# @param icon_name: string — icon name (second arg for jaraba_icon) #}
{# @param title: string — translated title (use {% trans %} in caller) #}
{# @param description: string — translated description #}
{# @param cta_label: string — button text (translated) #}
{# @param cta_url: string — button URL #}
{# @param cta_modal: boolean — if true, opens in modal (modal-system library) #}
{# @param secondary_label: string — optional secondary action text #}
{# @param secondary_url: string — optional secondary action URL #}
{# Directivas: ICON-CONVENTION-001, TWIG-XSS-001, BEM-NAMING-001, i18n #}

<div class="ej-empty-state">
  {% if icon is defined and icon_name is defined %}
    <div class="ej-empty-state__icon">
      {{ jaraba_icon(icon, icon_name, { variant: 'duotone', size: '64px' }) }}
    </div>
  {% endif %}

  {% if title is defined %}
    <h3 class="ej-empty-state__title">{{ title }}</h3>
  {% endif %}

  {% if description is defined %}
    <p class="ej-empty-state__description">{{ description }}</p>
  {% endif %}

  {% if cta_label is defined and cta_url is defined %}
    <div class="ej-empty-state__actions">
      <a href="{{ cta_url }}"
         class="ej-empty-state__cta ej-btn ej-btn--primary"
         {% if cta_modal is defined and cta_modal %}
           data-modal-trigger="true"
         {% endif %}>
        {{ cta_label }}
      </a>
      {% if secondary_label is defined and secondary_url is defined %}
        <a href="{{ secondary_url }}" class="ej-empty-state__secondary ej-btn ej-btn--ghost">
          {{ secondary_label }}
        </a>
      {% endif %}
    </div>
  {% endif %}
</div>
```

**5.2.2 SCSS Partial: `_empty-state.scss`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/scss/components/_empty-state.scss`

Extiende la clase `.empty-state` existente en `_jobseeker-dashboard.scss` con un diseno mas completo. Usa tokens inyectables para que cada tenant pueda personalizar colores via Drupal UI.

```scss
// Empty State — Informative placeholder when no data exists
// Directivas: DESIGN-TOKEN-FEDERATED-001, BEM-NAMING-001

.ej-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: var(--ej-spacing-2xl, 3rem) var(--ej-spacing-lg, 1.5rem);
  min-height: 280px;
  background: var(--ej-bg-card, #ffffff);
  border-radius: var(--ej-border-radius, 10px);
  border: 1px dashed var(--ej-border-color-light, #EEEEEE);

  &__icon {
    margin-bottom: var(--ej-spacing-lg, 1.5rem);
    color: var(--ej-color-primary, #FF8C42);
    opacity: 0.7;

    svg {
      width: 64px;
      height: 64px;
    }
  }

  &__title {
    font-family: var(--ej-font-family-heading, 'Outfit', sans-serif);
    font-size: var(--ej-font-size-xl, 1.25rem);
    font-weight: 600;
    color: var(--ej-text-primary, #334155);
    margin: 0 0 var(--ej-spacing-sm, 0.5rem);
  }

  &__description {
    font-size: var(--ej-font-size-sm, 0.875rem);
    color: var(--ej-text-muted, #64748B);
    max-width: 400px;
    margin: 0 0 var(--ej-spacing-lg, 1.5rem);
    line-height: 1.6;
  }

  &__actions {
    display: flex;
    gap: var(--ej-spacing-sm, 0.5rem);
    flex-wrap: wrap;
    justify-content: center;
  }

  &__cta {
    // Inherits from .ej-btn--primary via class
  }

  &__secondary {
    // Inherits from .ej-btn--ghost via class
  }
}
```

### 5.3 Ficheros a Modificar

**5.3.1 `ecosistema_jaraba_theme/scss/main.scss`** — Anadir import:
```scss
@use 'components/empty-state';
```

**5.3.2 Templates de dashboard que necesitan empty states**

Prioridad por volumen de usuarios:

| Dashboard | Template | Condicion de vacio | Empty State |
|-----------|----------|-------------------|-------------|
| Emprendimiento | `page--emprendimiento.html.twig` | Sin proyectos | icon: `business/rocket`, title: "Sin proyectos todavia", cta: "Crear mi primer proyecto" |
| Empleabilidad | `page--empleabilidad.html.twig` | Sin candidaturas | icon: `ui/briefcase`, title: "Tu carrera empieza aqui", cta: "Completar mi perfil" |
| AgroConecta | `page--agroconecta.html.twig` | Sin parcelas | icon: `verticals/harvest`, title: "Sin parcelas registradas", cta: "Anadir parcela" |
| Marketplace | Marketplace listing | Sin productos | icon: `commerce/package`, title: "Sin productos publicados", cta: "Publicar producto" |
| Content Hub | `page--content-hub.html.twig` | Sin articulos | icon: `general/book-open`, title: "Sin articulos", cta: "Escribir articulo" |
| Legal | `page--jarabalex.html.twig` | Sin casos | icon: `legal/briefcase`, title: "Sin casos activos", cta: "Nuevo caso" |
| CRM | `page--crm.html.twig` | Sin contactos | icon: `users/group`, title: "Tu CRM esta vacio", cta: "Importar contactos" |

Cada template envuelve la seccion de datos con un condicional:
```twig
{% if items|length > 0 %}
  {# Renderizar datos normales #}
{% else %}
  {% include '@ecosistema_jaraba_theme/partials/_empty-state.html.twig' with {
    icon: 'business',
    icon_name: 'rocket',
    title: 'Sin proyectos todavia'|trans,
    description: 'Crea tu primer proyecto para comenzar a medir tu impacto.'|trans,
    cta_label: 'Crear proyecto'|trans,
    cta_url: '/node/add/project',
    cta_modal: true
  } %}
{% endif %}
```

### 5.4 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| ICON-CONVENTION-001 | `jaraba_icon(category, name, { variant: 'duotone', size: '64px' })` — signature correcta con 2 args + objeto opciones |
| ICON-DUOTONE-001 | `variant: 'duotone'` para templates premium |
| ICON-COLOR-001 | Colores via CSS token `var(--ej-color-primary)`, no hex hardcoded en SVG |
| ICON-EMOJI-001 | Sin emojis Unicode, solo SVG icons |
| TWIG-XSS-001 | Todo autoescape, sin `\|raw` |
| MODAL-LIBRARY-001 | `data-modal-trigger="true"` para abrir CTA en modal |
| i18n | `\|trans` en variables, `{% trans %}` en partial |

---

## 6. FASE 3: Micro-interacciones y Transiciones CSS

### 6.1 Descripcion Funcional

Las micro-interacciones son transiciones visuales sutiles que proporcionan feedback inmediato al usuario: card hover lift (la tarjeta "sube" al pasar el raton), button click pulse (el boton se contrae brevemente al hacer clic), modal fade-in-scale (el modal aparece con desvanecimiento + escala), tab slide indicator (indicador animado que se desliza entre tabs). Estas transiciones usan exclusivamente CSS (transitions + animations), sin JavaScript adicional, y se basan en los tokens de transicion ya definidos: `$ej-transition-fast: 150ms`, `$ej-transition-normal: 250ms`, `$ej-transition-easing: cubic-bezier(0.4, 0, 0.2, 1)`.

### 6.2 Ficheros a Modificar

No se crean ficheros nuevos. Se modifican SCSS parciales existentes anadiendo las transiciones que faltan.

**6.2.1 `_cards.scss`** — Card hover lift

Anadir al selector `.ej-card` (o el selector equivalente que use el tema para tarjetas):

```scss
// Card hover lift — micro-interaction
// Usa tokens: $ej-transition-normal, $ej-hover-scale, $ej-hover-shadow
transition: transform var(--ej-transition, all 250ms cubic-bezier(0.4, 0, 0.2, 1)),
            box-shadow var(--ej-transition, all 250ms cubic-bezier(0.4, 0, 0.2, 1));

&:hover {
  transform: translateY(-2px) scale(1.01);
  box-shadow: var(--ej-shadow-lg, 0 10px 15px rgba(0, 0, 0, 0.1));
}

&:active {
  transform: translateY(0) scale(0.99);
}
```

**6.2.2 `_buttons.scss`** — Button click pulse

Anadir al selector base de botones:

```scss
// Button click feedback — micro-interaction
transition: transform 150ms ease, box-shadow 150ms ease;

&:active {
  transform: scale(0.97);
}
```

**6.2.3 `_slide-panel.scss` / Modal styles** — Modal entrance animation

Anadir o verificar que los modales tienen fade-in-scale:

```scss
// Modal entrance — micro-interaction
@keyframes ej-fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.ej-modal__content,
.slide-panel__content {
  animation: ej-fadeInScale 200ms cubic-bezier(0.4, 0, 0.2, 1);
}
```

### 6.3 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| DESIGN-TOKEN-FEDERATED-001 | Usa `var(--ej-transition)`, `var(--ej-shadow-lg)` |
| COMPILED-CSS-NEVER-EDIT-001 | Solo edita SCSS, recompila |
| WCAG 2.1 AA | `prefers-reduced-motion: reduce` media query para desactivar animaciones |

**Nota accesibilidad:** Anadir en `_base.scss` o `_accessibility.scss`:
```scss
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## 7. FASE 4: Error Recovery UX

### 7.1 Descripcion Funcional

Cuando una peticion fetch falla (timeout, 500, red caida), el usuario actual ve un error generico de Drupal o simplemente nada. El patron de clase mundial (Stripe, Notion) muestra un toast contextual con: icono de error, mensaje legible en lenguaje humano (no codigo HTTP), y boton "Reintentar" que re-ejecuta la misma peticion. Opcionalmente, sugerencias de accion alternativa ("Verificar tu conexion" o "Intentar mas tarde").

Se implementa como un utility JS (`fetchWithRetry`) + extension del toast system existente.

### 7.2 Ficheros a Crear

**7.2.1 JS Utility: `fetch-retry.js`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/js/fetch-retry.js`

```javascript
/**
 * @file
 * Fetch with automatic retry and error recovery UI.
 *
 * Directivas: CSRF-JS-CACHE-001, INNERHTML-XSS-001, DRUPAL-BEHAVIORS-001
 *
 * Usage:
 *   Drupal.jarabaFetch('/api/endpoint', { method: 'GET' })
 *     .then(data => renderContent(data))
 *     .catch(err => console.error(err));
 */
(function (Drupal) {
  'use strict';

  // CSRF token cache (CSRF-JS-CACHE-001)
  let csrfTokenPromise = null;
  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(r => r.text());
    }
    return csrfTokenPromise;
  }

  /**
   * Fetch with retry + toast error recovery.
   * @param {string} url - API endpoint (use Drupal.url() for lang prefix)
   * @param {object} options - fetch options
   * @param {number} maxRetries - max retry attempts (default: 2)
   * @returns {Promise}
   */
  Drupal.jarabaFetch = async function(url, options = {}, maxRetries = 2) {
    const fullUrl = url.startsWith('/') ? Drupal.url(url.substring(1)) : url;

    for (let attempt = 0; attempt <= maxRetries; attempt++) {
      try {
        // Add CSRF token for non-GET requests
        if (options.method && options.method !== 'GET') {
          const token = await getCsrfToken();
          options.headers = options.headers || {};
          options.headers['X-CSRF-Token'] = token;
        }

        const response = await fetch(fullUrl, options);

        if (!response.ok) {
          throw new Error(Drupal.t('Error del servidor (@status)', { '@status': response.status }));
        }

        return await response.json();
      } catch (error) {
        if (attempt === maxRetries) {
          // Show recovery toast
          Drupal.jarabaShowErrorToast(
            Drupal.t('No se pudo completar la accion'),
            Drupal.t('Verifica tu conexion e intentalo de nuevo.'),
            function() { Drupal.jarabaFetch(url, options, maxRetries); }
          );
          throw error;
        }
        // Wait before retry (exponential backoff)
        await new Promise(r => setTimeout(r, 1000 * (attempt + 1)));
      }
    }
  };

  /**
   * Show error toast with retry button.
   * @param {string} title - Error title (already translated)
   * @param {string} message - Error description (already translated)
   * @param {function} retryFn - Callback for retry button
   */
  Drupal.jarabaShowErrorToast = function(title, message, retryFn) {
    const toast = document.createElement('div');
    toast.className = 'ej-toast ej-toast--error';
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');

    const titleEl = document.createElement('strong');
    titleEl.className = 'ej-toast__title';
    titleEl.textContent = title; // textContent = XSS safe (INNERHTML-XSS-001)

    const msgEl = document.createElement('p');
    msgEl.className = 'ej-toast__message';
    msgEl.textContent = message;

    toast.appendChild(titleEl);
    toast.appendChild(msgEl);

    if (retryFn) {
      const retryBtn = document.createElement('button');
      retryBtn.className = 'ej-toast__retry ej-btn ej-btn--sm';
      retryBtn.textContent = Drupal.t('Reintentar');
      retryBtn.addEventListener('click', function() {
        toast.remove();
        retryFn();
      });
      toast.appendChild(retryBtn);
    }

    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'ej-toast__close';
    closeBtn.setAttribute('aria-label', Drupal.t('Cerrar'));
    closeBtn.textContent = '\u00D7'; // x character
    closeBtn.addEventListener('click', function() { toast.remove(); });
    toast.appendChild(closeBtn);

    // Insert into DOM
    let container = document.querySelector('.ej-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'ej-toast-container';
      document.body.appendChild(container);
    }
    container.appendChild(toast);

    // Auto-dismiss after 10s
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 10000);
  };

})(Drupal);
```

**7.2.2 SCSS Partial: `_toasts.scss`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/scss/components/_toasts.scss`

```scss
// Toast notifications — Error recovery with retry
// Directivas: DESIGN-TOKEN-FEDERATED-001, BEM-NAMING-001

.ej-toast-container {
  position: fixed;
  top: var(--ej-spacing-lg, 1.5rem);
  right: var(--ej-spacing-lg, 1.5rem);
  z-index: 1080; // Above modals (z-index-tooltip: 1070)
  display: flex;
  flex-direction: column;
  gap: var(--ej-spacing-sm, 0.5rem);
  max-width: 420px;

  @media (max-width: 768px) {
    left: var(--ej-spacing-sm, 0.5rem);
    right: var(--ej-spacing-sm, 0.5rem);
    max-width: none;
  }
}

.ej-toast {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: var(--ej-spacing-sm, 0.5rem);
  padding: var(--ej-spacing-md, 1rem);
  background: var(--ej-bg-card, #ffffff);
  border-radius: var(--ej-border-radius, 10px);
  box-shadow: var(--ej-shadow-lg, 0 8px 32px rgba(0, 0, 0, 0.14));
  border-left: 4px solid;
  animation: ej-fadeInScale 200ms ease-out;

  &--error {
    border-left-color: var(--ej-color-danger, #EF4444);
  }

  &--warning {
    border-left-color: var(--ej-color-warning, #F59E0B);
  }

  &--success {
    border-left-color: var(--ej-color-success, #10B981);
  }

  &--info {
    border-left-color: var(--ej-color-info, #1976D2);
  }

  &__title {
    flex: 1;
    font-weight: 600;
    color: var(--ej-text-primary, #334155);
    font-size: var(--ej-font-size-sm, 0.875rem);
  }

  &__message {
    width: 100%;
    margin: 0;
    color: var(--ej-text-muted, #64748B);
    font-size: var(--ej-font-size-sm, 0.875rem);
    line-height: 1.5;
  }

  &__retry {
    margin-top: var(--ej-spacing-xs, 0.25rem);
  }

  &__close {
    background: none;
    border: none;
    color: var(--ej-text-muted, #64748B);
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
    padding: 0;

    &:hover {
      color: var(--ej-text-primary, #334155);
    }
  }
}
```

### 7.3 Ficheros a Modificar

**7.3.1 `ecosistema_jaraba_theme.libraries.yml`** — Declarar nueva libreria:
```yaml
fetch-retry:
  js:
    js/fetch-retry.js: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

**7.3.2 `ecosistema_jaraba_theme/scss/main.scss`** — Anadir:
```scss
@use 'components/toasts';
```

**7.3.3 Adjuntar libreria globalmente** en `ecosistema_jaraba_theme.info.yml`:
```yaml
libraries:
  - ecosistema_jaraba_theme/global-styling
  - ecosistema_jaraba_theme/icons
  - ecosistema_jaraba_theme/fetch-retry   # <-- nuevo
```

### 7.4 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| CSRF-JS-CACHE-001 | Token cacheado como Promise, reutilizado entre peticiones |
| CSRF-API-001 | Header `X-CSRF-Token` en peticiones no-GET |
| INNERHTML-XSS-001 | Usa `textContent` (no innerHTML) para renderizar texto del servidor |
| DRUPAL-BEHAVIORS-001 | Patron `Drupal.jarabaFetch()` como utility global |
| i18n | `Drupal.t()` para todos los textos de la UI |
| WCAG 2.1 AA | `role="alert"`, `aria-live="assertive"` para screen readers |
| REST-PUBLIC-API-FALLBACK | try-catch con retry + toast informativo |

---

## 8. FASE 5: Bottom Navigation Mobile

### 8.1 Descripcion Funcional

El CSS para un bottom navigation bar completo ya existe en `_mobile-components.scss` lineas 150-282: `.bottom-nav`, `.bottom-nav__items`, `.bottom-nav__link`, `.bottom-nav__icon`, `.bottom-nav__label`, `.bottom-nav__badge`, y `body.has-bottom-nav` con padding compensatorio. Lo que falta es:

1. **Twig partial** que renderice el HTML con los 5 items de navegacion
2. **JS behavior** que marque el item activo basado en la ruta actual
3. **Inclusion en `page.html.twig`** (o los page templates del SaaS) para mobile
4. **Body class** `has-bottom-nav` inyectada via `hook_preprocess_html()` para activar el padding

El bottom nav se muestra SOLO en mobile (max-width: 768px, ya controlado por el CSS existente via `@media`). En desktop se oculta automaticamente.

### 8.2 Ficheros a Crear

**8.2.1 Twig Partial: `_bottom-nav.html.twig`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_bottom-nav.html.twig`

Items de navegacion configurables via theme_settings para que se puedan cambiar desde la UI de Drupal sin tocar codigo. Fallback a 5 items estandar si no configurado.

```twig
{# _bottom-nav.html.twig — Mobile bottom navigation bar #}
{# Directivas: ICON-CONVENTION-001, BEM-NAMING-001, i18n, WCAG 2.5.5 (touch targets) #}
{# CSS: Already exists in _mobile-components.scss lines 150-282 #}
{# Visibility: Only shown on mobile via CSS media query #}

{% set bottom_nav_items = bottom_nav_items|default([
  { icon_cat: 'ui', icon_name: 'dashboard', label: 'Inicio'|trans, url: '/dashboard', route: 'dashboard' },
  { icon_cat: 'actions', icon_name: 'search', label: 'Buscar'|trans, url: '/search', route: 'search' },
  { icon_cat: 'actions', icon_name: 'plus-circle', label: 'Crear'|trans, url: '#', route: 'create', is_fab: true },
  { icon_cat: 'general', icon_name: 'bell', label: 'Alertas'|trans, url: '/notifications', route: 'notifications' },
  { icon_cat: 'general', icon_name: 'user', label: 'Perfil'|trans, url: '/mi-cuenta', route: 'profile' },
]) %}

<nav class="bottom-nav" aria-label="{% trans %}Navegacion principal{% endtrans %}">
  <div class="bottom-nav__items">
    {% for item in bottom_nav_items %}
      <a href="{{ item.url }}"
         class="bottom-nav__link {{ item.route == active_bottom_nav ? 'is-active' : '' }} {{ item.is_fab is defined and item.is_fab ? 'bottom-nav__link--fab' : '' }}"
         {% if item.is_fab is defined and item.is_fab %}
           data-modal-trigger="create-action"
           aria-haspopup="dialog"
         {% endif %}
         aria-label="{{ item.label }}"
         {% if item.route == active_bottom_nav %}aria-current="page"{% endif %}>
        <span class="bottom-nav__icon">
          {{ jaraba_icon(item.icon_cat, item.icon_name, { size: '24px' }) }}
        </span>
        <span class="bottom-nav__label">{{ item.label }}</span>
        {% if item.badge is defined and item.badge > 0 %}
          <span class="bottom-nav__badge" aria-label="{{ item.badge }} {% trans %}notificaciones{% endtrans %}">
            {{ item.badge > 9 ? '9+' : item.badge }}
          </span>
        {% endif %}
      </a>
    {% endfor %}
  </div>
</nav>
```

**8.2.2 JS: `bottom-nav.js`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/js/bottom-nav.js`

```javascript
/**
 * @file
 * Bottom navigation — active state detection and create FAB action.
 *
 * Directivas: DRUPAL-BEHAVIORS-001, ONCE-PATTERN-001
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.bottomNav = {
    attach: function (context) {
      var navs = once('bottom-nav', '.bottom-nav', context);
      if (!navs.length) return;

      var nav = navs[0];
      var currentPath = window.location.pathname;

      // Mark active item based on current path
      var links = nav.querySelectorAll('.bottom-nav__link');
      links.forEach(function(link) {
        var href = link.getAttribute('href');
        if (href && href !== '#' && currentPath.indexOf(href) === 0) {
          link.classList.add('is-active');
          link.setAttribute('aria-current', 'page');
        }
      });

      // FAB create action — trigger modal with context-aware options
      var fabLink = nav.querySelector('.bottom-nav__link--fab');
      if (fabLink) {
        fabLink.addEventListener('click', function(e) {
          e.preventDefault();
          // Dispatch event for modal-system to handle
          document.dispatchEvent(new CustomEvent('jaraba:quick-create', {
            detail: { source: 'bottom-nav' }
          }));
        });
      }
    }
  };

})(Drupal, once);
```

### 8.3 Ficheros a Modificar

**8.3.1 `ecosistema_jaraba_theme.theme` — `hook_preprocess_html()`**

Anadir body class `has-bottom-nav` para usuarios autenticados (el bottom nav solo se muestra a usuarios logueados en el SaaS, no en meta-sites):

```php
// BOTTOM NAV — body class for padding compensation
// Solo para usuarios autenticados en el SaaS (no meta-sites)
if ($variables['logged_in'] && empty($variables['meta_site'])) {
  $variables['attributes']['class'][] = 'has-bottom-nav';
}
```

**IMPORTANTE:** Segun la directriz del usuario, `attributes.addClass()` NO funciona para el body. Se debe usar el array `$variables['attributes']['class'][]` dentro de `hook_preprocess_html()`.

**8.3.2 `page.html.twig`** — Incluir partial antes del cierre de `</body>`:

```twig
{# Bottom navigation mobile — only for authenticated SaaS users #}
{% if logged_in and not meta_site %}
  {% include '@ecosistema_jaraba_theme/partials/_bottom-nav.html.twig' %}
{% endif %}
```

**8.3.3 `ecosistema_jaraba_theme.libraries.yml`** — Declarar libreria:

```yaml
bottom-nav:
  js:
    js/bottom-nav.js: {}
  dependencies:
    - core/drupal
    - core/once
```

**8.3.4 Adjuntar condicionalmente en `hook_preprocess_page()`:**

```php
// Attach bottom-nav library for authenticated non-metasite users
if ($variables['logged_in'] && empty($variables['meta_site'])) {
  $variables['#attached']['library'][] = 'ecosistema_jaraba_theme/bottom-nav';
}
```

### 8.4 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| ICON-CONVENTION-001 | `jaraba_icon(category, name, { size: '24px' })` con 2 args |
| BEM-NAMING-001 | `.bottom-nav`, `.bottom-nav__link`, `.bottom-nav__link--fab` |
| MODAL-LIBRARY-001 | FAB central dispara `jaraba:quick-create` CustomEvent |
| WCAG 2.5.5 | Touch targets min 48px (CSS existente: `min-height: 56px`) |
| hook_preprocess_html | Body class `has-bottom-nav` via `$variables['attributes']['class'][]` |
| i18n | `\|trans` en labels, `{% trans %}` para aria-label |
| Zero-region | Incluido via `{% include %}` en page.html.twig limpio |
| CSS existente reutilizado | No se crea CSS nuevo, se usa `_mobile-components.scss:150-282` |

---

## 9. FASE 6: Centro de Notificaciones

### 9.1 Descripcion Funcional

El partial `_proactive-insights-bell.html.twig` ya renderiza un icono de campana con badge de conteo. Lo que falta es un panel desplegable que muestre las notificaciones agrupadas por tipo (sistema, social, workflow), con mark-as-read y acciones inline. Se implementa como:

1. **Entidad `Notification`** — ContentEntity ligera para almacenar notificaciones
2. **Service** — `NotificationService` para CRUD
3. **Controller** — Endpoints REST para listar, mark-read, dismiss
4. **Twig partial** — Panel desplegable desde la campana
5. **JS** — Fetch + render del panel

### 9.2 Ficheros a Crear

**9.2.1 Modulo: `jaraba_notifications`**

Ruta: `web/modules/custom/jaraba_notifications/`

Estructura:
```
jaraba_notifications/
  jaraba_notifications.info.yml
  jaraba_notifications.module
  jaraba_notifications.routing.yml
  jaraba_notifications.services.yml
  jaraba_notifications.libraries.yml
  src/
    Entity/
      Notification.php          # ContentEntity: type, message, link, read, user_id, created
    Service/
      NotificationService.php   # create(), listForUser(), markRead(), dismiss(), countUnread()
    Controller/
      NotificationApiController.php  # REST endpoints
    Access/
      NotificationAccessControlHandler.php  # Tenant + owner isolation
```

**Entidad Notification — campos:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | integer (auto) | PK |
| `uuid` | uuid | UUID unico |
| `uid` | entity_reference (user) | Usuario destinatario (EntityOwnerTrait) |
| `tenant_id` | entity_reference (group) | Aislamiento por tenant (TENANT-001) |
| `type` | list_string | 'system' / 'social' / 'workflow' / 'ai' |
| `title` | string (255) | Titulo corto |
| `message` | string_long | Descripcion |
| `link` | string (2048) | URL de accion |
| `read_status` | boolean | Leida/no leida |
| `created` | created | Timestamp |

Directrices entidad:
- **ENTITY-OWNER-PATTERN-001** — `uid` field con `EntityOwnerTrait`
- **ENTITY-PREPROCESS-001** — `template_preprocess_notification()` para Twig
- **ENTITY-APPEND-001** — Solo create, no update/delete (log inmutable), con excepcion de `read_status` que si se actualiza
- **TENANT-001** — Todas las queries filtran por `tenant_id`
- **FIELD-UI-SETTINGS-TAB-001** — `field_ui_base_route` para Manage Fields en admin
- Admin listing en `/admin/content/notifications` y `/admin/structure/notification`

**Endpoints REST:**

| Metodo | Ruta | Funcion | Auth |
|--------|------|---------|------|
| GET | `/api/v1/notifications` | Listar por usuario (paginado, filtrable por type) | CSRF |
| PATCH | `/api/v1/notifications/{id}/read` | Marcar como leida | CSRF |
| PATCH | `/api/v1/notifications/read-all` | Marcar todas como leidas | CSRF |
| DELETE | `/api/v1/notifications/{id}` | Dismiss (soft delete o flag) | CSRF |
| GET | `/api/v1/notifications/count` | Conteo de no leidas (para badge) | CSRF |

Directrices API:
- **CSRF-API-001** — `_csrf_request_header_token: 'TRUE'` en routing
- **API-NAMING-001** — No usar `create()` como nombre de metodo
- **REST-PUBLIC-API-001** — Rate limiting via Flood API (15 req/min para GET)
- **API-WHITELIST-001** — Campos permitidos en PATCH definidos como `ALLOWED_FIELDS`
- **INNERHTML-XSS-001** — Output sanitizado con `Html::escape()`

**9.2.2 Twig Partial: `_notification-panel.html.twig`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_notification-panel.html.twig`

```twig
{# _notification-panel.html.twig — Notification center dropdown panel #}
{# Rendered client-side via JS (this is the HTML shell) #}
{# Directivas: BEM-NAMING-001, WCAG 2.1 AA, i18n #}

<div class="ej-notification-panel" id="notification-panel"
     role="dialog" aria-modal="false"
     aria-label="{% trans %}Centro de notificaciones{% endtrans %}"
     hidden>

  <div class="ej-notification-panel__header">
    <h3 class="ej-notification-panel__title">{% trans %}Notificaciones{% endtrans %}</h3>
    <button class="ej-notification-panel__mark-all"
            id="notification-mark-all"
            aria-label="{% trans %}Marcar todas como leidas{% endtrans %}">
      {% trans %}Marcar todo{% endtrans %}
    </button>
  </div>

  <div class="ej-notification-panel__filters" role="tablist">
    <button class="ej-notification-panel__filter is-active" data-filter="all" role="tab" aria-selected="true">
      {% trans %}Todas{% endtrans %}
    </button>
    <button class="ej-notification-panel__filter" data-filter="system" role="tab" aria-selected="false">
      {% trans %}Sistema{% endtrans %}
    </button>
    <button class="ej-notification-panel__filter" data-filter="social" role="tab" aria-selected="false">
      {% trans %}Social{% endtrans %}
    </button>
    <button class="ej-notification-panel__filter" data-filter="workflow" role="tab" aria-selected="false">
      {% trans %}Workflow{% endtrans %}
    </button>
  </div>

  <div class="ej-notification-panel__list" id="notification-list" role="tabpanel">
    {# Items rendered via JS — skeleton shown while loading #}
    {% include '@ecosistema_jaraba_theme/partials/_skeleton.html.twig' with { variant: 'list' } %}
  </div>

  <div class="ej-notification-panel__empty is-hidden" id="notification-empty">
    {% include '@ecosistema_jaraba_theme/partials/_empty-state.html.twig' with {
      icon: 'general',
      icon_name: 'bell',
      title: 'Sin notificaciones'|trans,
      description: 'Cuando haya novedades, apareceran aqui.'|trans
    } %}
  </div>
</div>
```

### 9.3 SCSS

Ruta: `web/themes/custom/ecosistema_jaraba_theme/scss/components/_notification-panel.scss`

Panel desplegable con glassmorphism, anclado al icono de campana del header. Usa tokens inyectables.

### 9.4 Directrices Entidad Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| TENANT-001 | `tenant_id` entity reference con filtro obligatorio |
| ENTITY-OWNER-PATTERN-001 | `uid` field con `EntityOwnerTrait` + `EntityOwnerInterface` |
| ENTITYOWNERINTERFACE-TRAIT-001 | `implements EntityOwnerInterface, EntityChangedInterface` en class |
| ENTITY-PREPROCESS-001 | `template_preprocess_notification()` extrae datos para Twig |
| FIELD-UI-SETTINGS-TAB-001 | `field_ui_base_route` para admin/structure |
| KERNEL-OPTIONAL-AI-001 | Sin dependencias AI, pero si se integra con ProactiveInsight usa `@?` |
| PREMIUM-FORMS-PATTERN-001 | Form admin extiende `PremiumEntityFormBase` |
| PRESAVE-RESILIENCE-001 | `preSave()` sin servicios opcionales |

---

## 10. FASE 7: Busqueda Global Mejorada

### 10.1 Descripcion Funcional

El command bar (`_command-bar.html.twig` + `command-bar.js`) ya existe con soporte Cmd+K, navegacion por teclado, y XSS prevention. Lo que falta es un backend de busqueda mas robusto que busque cross-entity (usuarios, page_content, articulos, proyectos, marketplace items) con fuzzy matching.

### 10.2 Ficheros a Crear/Modificar

**10.2.1 Service: `GlobalSearchService.php`**

Ruta: `web/modules/custom/ecosistema_jaraba_core/src/Service/GlobalSearchService.php`

Busca across 5 tipos de entidad con `LIKE` SQL + ranking por relevancia. Si Qdrant esta disponible (`@?jaraba_ai_rag.search_service`), intenta semantic search primero, fallback a SQL.

```php
// Directivas: TENANT-001, KERNEL-OPTIONAL-AI-001, API-WHITELIST-001
class GlobalSearchService {

  private const SEARCHABLE_ENTITIES = [
    'user' => ['field' => 'name', 'label_field' => 'name'],
    'page_content' => ['field' => 'title', 'label_field' => 'title'],
    'content_article' => ['field' => 'title', 'label_field' => 'title'],
    'node' => ['field' => 'title', 'label_field' => 'title'],
    'marketplace_listing' => ['field' => 'title', 'label_field' => 'title'],
  ];

  public function search(string $query, int $tenantId, int $limit = 10): array {
    // 1. Try Qdrant semantic search if available
    // 2. Fallback to SQL LIKE across entities
    // 3. Merge, deduplicate, rank by relevance
    // 4. Return [{type, id, label, url, icon, relevance}]
  }
}
```

**10.2.2 Controller endpoint**

Ruta en `ecosistema_jaraba_core.routing.yml`:
```yaml
ecosistema_jaraba_core.global_search:
  path: '/api/v1/search'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\GlobalSearchController::search'
  requirements:
    _csrf_request_header_token: 'TRUE'
    _permission: 'access content'
  methods: [GET]
```

**10.2.3 Modificar `command-bar.js`**

Cambiar el endpoint de busqueda actual por el nuevo `/api/v1/search`. Anadir seccion "Recientes" (ultimas 5 paginas visitadas, almacenadas en `localStorage`).

### 10.3 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| TENANT-001 | Todas las queries filtran por `tenant_id` |
| KERNEL-OPTIONAL-AI-001 | Qdrant service inyectado como `@?` con fallback SQL |
| CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` |
| COMMAND-BAR-001 | Extiende `CommandRegistryService` existente |
| INNERHTML-XSS-001 | Resultados renderizados con `Drupal.checkPlain()` |
| ROUTE-LANGPREFIX-001 | URLs generadas con `Drupal.url()` para prefijo idioma |

---

## 11. FASE 8: Onboarding Quick-Start Overlay

### 11.1 Descripcion Funcional

Modal de bienvenida para nuevos usuarios (primera visita tras registro) con 3 acciones rapidas contextualizadas por vertical. Aparece una sola vez (flag en user data via State API o campo en user entity). El modulo `jaraba_onboarding` ya tiene 7 steps definidos — se extiende con un quick-start endpoint que devuelve las 3 acciones prioritarias segun el vertical del usuario.

### 11.2 Ficheros a Crear

**11.2.1 Twig Partial: `_quick-start-overlay.html.twig`**

Ruta: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_quick-start-overlay.html.twig`

```twig
{# _quick-start-overlay.html.twig — First-visit quick start modal #}
{# Directivas: MODAL-ACCESSIBILITY-001, MODAL-ARIA-001, i18n, BEM-NAMING-001 #}

<div class="ej-quick-start" id="quick-start-overlay"
     role="dialog" aria-modal="true"
     aria-labelledby="quick-start-title"
     hidden>
  <div class="ej-quick-start__backdrop"></div>
  <div class="ej-quick-start__content">
    <button class="ej-quick-start__close" aria-label="{% trans %}Cerrar{% endtrans %}">&times;</button>

    <h2 class="ej-quick-start__title" id="quick-start-title">
      {% trans %}Bienvenido a la Plataforma{% endtrans %}
    </h2>
    <p class="ej-quick-start__subtitle">
      {% trans %}Completa estas 3 acciones rapidas para empezar{% endtrans %}
    </p>

    <div class="ej-quick-start__actions" id="quick-start-actions">
      {# Rendered via JS from /api/v1/onboarding/quick-start #}
      {% include '@ecosistema_jaraba_theme/partials/_skeleton.html.twig' with { variant: 'list' } %}
    </div>

    <div class="ej-quick-start__footer">
      <button class="ej-btn ej-btn--ghost" id="quick-start-skip">
        {% trans %}Explorar por mi cuenta{% endtrans %}
      </button>
    </div>
  </div>
</div>
```

### 11.3 Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|-------------|
| MODAL-ACCESSIBILITY-001 | Keyboard nav: Esc close, Tab trapping, focus restoration |
| MODAL-ARIA-001 | `role="dialog"`, `aria-modal="true"`, `aria-labelledby` |
| METRICS-001 | Quick-start shown/dismissed/completed tracked via State API |
| FREEMIUM-TIER-001 | Quick-start acciones reflejan features del plan actual |

---

## 12. Tabla de Correspondencia — Especificaciones Tecnicas

| ID | Componente | Tipo | Ubicacion | Dependencias | SCSS Tokens | Breakpoint | Z-Index |
|----|-----------|------|-----------|--------------|-------------|------------|---------|
| SK-01 | Skeleton Screen | Twig Partial + SCSS | `partials/_skeleton.html.twig` + `components/_skeleton.scss` | Ninguna | `--ej-gray-200`, `--ej-gray-100`, `--ej-bg-card`, `--ej-border-radius` | Responsive (fluid) | N/A |
| ES-01 | Empty State | Twig Partial + SCSS | `partials/_empty-state.html.twig` + `components/_empty-state.scss` | `jaraba_icon()` | `--ej-color-primary`, `--ej-bg-card`, `--ej-text-primary`, `--ej-text-muted` | Responsive | N/A |
| MI-01 | Card Hover Lift | SCSS modification | `components/_cards.scss` | Ninguna | `--ej-transition`, `--ej-shadow-lg` | All | N/A |
| MI-02 | Button Click Pulse | SCSS modification | `components/_buttons.scss` | Ninguna | Transition values inline | All | N/A |
| MI-03 | Modal Fade-in-Scale | SCSS modification | `_slide-panel.scss` | Ninguna | Custom keyframes | All | 1050 |
| ER-01 | Fetch Retry | JS Library | `js/fetch-retry.js` | core/drupal, core/drupalSettings | N/A | N/A | N/A |
| ER-02 | Toast System | SCSS | `components/_toasts.scss` | fetch-retry.js | `--ej-color-danger`, `--ej-color-success`, `--ej-shadow-lg` | Mobile-first | 1080 |
| BN-01 | Bottom Nav | Twig Partial + JS | `partials/_bottom-nav.html.twig` + `js/bottom-nav.js` | core/drupal, core/once, modal-system | (CSS existente) `$ej-color-primary`, `$ej-bg-surface` | max-width: 768px | 900 |
| NP-01 | Notification Entity | Drupal Module | `jaraba_notifications/` | core, group | N/A | N/A | N/A |
| NP-02 | Notification Panel | Twig Partial + SCSS + JS | `partials/_notification-panel.html.twig` + `components/_notification-panel.scss` | jaraba_notifications, skeleton | `--ej-glass-bg`, `--ej-shadow-lg` | Responsive | 1060 |
| GS-01 | Global Search Service | PHP Service | `ecosistema_jaraba_core/src/Service/GlobalSearchService.php` | @?jaraba_ai_rag | N/A | N/A | N/A |
| GS-02 | Command Bar Enhancement | JS modification | `js/command-bar.js` | fetch-retry | N/A | All (mobile fullscreen) | N/A |
| QS-01 | Quick-Start Overlay | Twig Partial + JS | `partials/_quick-start-overlay.html.twig` + `js/quick-start.js` | jaraba_onboarding, skeleton | `--ej-bg-card`, `--ej-color-primary` | Mobile-first | 1050 |

---

## 13. Tabla de Cumplimiento de Directrices

### 13.1 Directrices P0 (Criticas)

| Codigo | Directriz | Aplicacion en este Plan | Estado |
|--------|-----------|------------------------|--------|
| DESIGN-TOKEN-FEDERATED-001 | SSOT en `_variables.scss` + `_injectable.scss` | Todos los SCSS nuevos usan `var(--ej-*)` con fallback | CUMPLE |
| ICON-EMOJI-001 | Prohibicion emojis en canvas_data | Ningun componente usa emojis Unicode | CUMPLE |
| ICON-CANVAS-INLINE-001 | SVG con hex explicitos en canvas_data | No se modifica canvas_data en este plan | N/A |
| ICON-CONVENTION-001 | `jaraba_icon(category, name, {options})` | Todos los iconos usan signature correcta con 2 args | CUMPLE |
| SCSS-COMPILE-001 | Dart Sass `npx sass --style=compressed` | Todos los comandos de compilacion usan Dart Sass | CUMPLE |
| CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` | Todos los endpoints REST nuevos | CUMPLE |
| CSRF-JS-CACHE-001 | Cache CSRF token como Promise | `fetch-retry.js` cachea token | CUMPLE |
| INNERHTML-XSS-001 | `Drupal.checkPlain()` o `textContent` | `fetch-retry.js` usa `textContent`; `command-bar.js` usa `checkPlain()` | CUMPLE |
| TWIG-XSS-001 | `\|safe_html` (nunca `\|raw`) | Ningun partial usa `\|raw` | CUMPLE |
| TENANT-001 | Filter por `tenant_id` en queries | Notification entity + GlobalSearchService | CUMPLE |
| AI-IDENTITY-001 | Identificar siempre como Jaraba | No hay prompts AI en este plan | N/A |
| KERNEL-OPTIONAL-AI-001 | AI services con `@?` | GlobalSearchService con `@?jaraba_ai_rag` | CUMPLE |
| DOC-GUARD-001 | No reescribir docs completos | No se modifican docs maestros | N/A |

### 13.2 Directrices P1 (Importantes)

| Codigo | Directriz | Aplicacion en este Plan | Estado |
|--------|-----------|------------------------|--------|
| BEM-NAMING-001 | `.component`, `__element`, `--modifier` | Todos: `.ej-skeleton__line--80`, `.ej-toast--error`, `.bottom-nav__link--fab` | CUMPLE |
| COLOR-MIX-MIGRATION-001 | Sin `darken()`/`lighten()` | SCSS usa gradientes CSS, no funciones deprecadas | CUMPLE |
| COMPILED-CSS-NEVER-EDIT-001 | Nunca editar .css directo | Solo se editan .scss y se recompilan | CUMPLE |
| DRUPAL-BEHAVIORS-001 | `Drupal.behaviors.{name}` | Todos los JS: `bottomNav`, `quickStart`, `notificationPanel` | CUMPLE |
| ONCE-PATTERN-001 | `once('{id}', selector, context)` | Todos los behaviors usan `once()` | CUMPLE |
| MODAL-ACCESSIBILITY-001 | Esc, Tab trap, focus restore | Quick-start overlay, notification panel | CUMPLE |
| MODAL-ARIA-001 | `role="dialog"`, `aria-modal`, `aria-labelledby` | Quick-start, notification panel | CUMPLE |
| ENTITY-OWNER-PATTERN-001 | `uid` + `EntityOwnerTrait` | Notification entity | CUMPLE |
| ENTITY-PREPROCESS-001 | `template_preprocess_{type}()` | Notification entity | CUMPLE |
| PREMIUM-FORMS-PATTERN-001 | `PremiumEntityFormBase` | Notification admin form | CUMPLE |
| FIELD-UI-SETTINGS-TAB-001 | `field_ui_base_route` en entity | Notification entity | CUMPLE |
| REST-PUBLIC-API-001 | Rate limiting Flood API | Notification endpoints + Global Search | CUMPLE |
| API-NAMING-001 | No usar `create()` | NotificationApiController usa `store()` | CUMPLE |
| API-WHITELIST-001 | `ALLOWED_FIELDS` constant | NotificationApiController PATCH endpoint | CUMPLE |
| HREFLANG-SEO-001 | No impactado | No se crean paginas publicas nuevas | N/A |
| COMMAND-BAR-001 | `CommandRegistryService` | GlobalSearchService se registra como provider | CUMPLE |

### 13.3 Directrices de Traducion (i18n)

| Contexto | Metodo | Ejemplo |
|----------|--------|---------|
| Twig static text | `{% trans %}Texto{% endtrans %}` | `{% trans %}Cargando contenido{% endtrans %}` |
| Twig variable text | `\|trans` filter | `'Sin proyectos todavia'\|trans` |
| JavaScript | `Drupal.t('Texto')` | `Drupal.t('Reintentar')` |
| JavaScript con placeholders | `Drupal.t('Error (@status)', {'@status': code})` | `Drupal.t('Error del servidor (@status)')` |
| ARIA labels | `{% trans %}` en attribute | `aria-label="{% trans %}Cerrar{% endtrans %}"` |

### 13.4 Directrices de Frontend Limpio

| Requisito | Implementacion |
|-----------|---------------|
| Sin `page.content` ni bloques Drupal | Todos los page templates usan `{{ clean_content }}` o sections manuales |
| Layout full-width | Todos los componentes usan `width: 100%` + `max-width` del container |
| Mobile-first | CSS media queries de menor a mayor; bottom nav mobile-only |
| Modales para CRUD | FAB del bottom nav dispara modal; empty state CTA con `data-modal-trigger` |
| Twig partials con `{% include %}` | Todos los componentes son partials reutilizables |
| Variables configurables desde UI | Theme Settings (7 tabs, 70+ opciones); SiteConfig entity para meta-sites |
| `hook_preprocess_html()` para body class | `has-bottom-nav` inyectada via `$variables['attributes']['class'][]` |
| Tenant sin acceso a admin theme | Body class condicional; bottom nav solo para SaaS, no meta-sites |

### 13.5 Directrices SCSS/Dart Sass

| Requisito | Implementacion |
|-----------|---------------|
| Dart Sass moderno | `@use 'sass:color'` (no `@import`); `npx sass --style=compressed` |
| Variables inyectables | `var(--ej-*)` con fallback SCSS en todos los componentes |
| `css-var` mixin disponible | Cada parcial puede usar `@include css-var(property, var-name, fallback)` |
| Sin `darken()`/`lighten()` | `color.adjust()` o gradientes CSS |
| package.json obligatorio | Cada modulo con SCSS tiene scripts de build |
| Header documentacion SCSS | Cada fichero nuevo incluye `@file`, directrices, comando compilacion |
| Compilacion dentro de Docker | `lando ssh -c "cd /app/... && npx sass ..."` |

---

## 14. Verificacion End-to-End

### 14.1 Compilacion SCSS

```bash
# Dentro del contenedor Docker (Lando):
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/main.css --style=compressed --no-source-map"
lando drush cr
```

Verificar que la compilacion no produce errores. Si hay errores preexistentes (ej. `$ej-color-text` undefined en `_reviews.scss`), documentar y aplicar patron SCSS-BYPASS: escribir en SCSS source + append CSS compilado al `css/main.css`.

### 14.2 Verificacion por Fase (Browser)

URL base: `https://jaraba-saas.lndo.site/`

| Fase | Ruta de Verificacion | Que Verificar |
|------|---------------------|---------------|
| 1 (Skeleton) | `/dashboard` | Al cargar, se ve shimmer animado antes de datos reales |
| 2 (Empty State) | Dashboard de nuevo usuario (sin datos) | Se ve icono + titulo + CTA contextual |
| 3 (Micro-int) | Cualquier pagina con cards | Hover lift en cards, click pulse en botones |
| 4 (Error Recovery) | Desconectar red → hacer accion | Toast con "Reintentar" aparece, retry funciona |
| 5 (Bottom Nav) | Mobile (375px) → `/dashboard` | Bottom nav visible con 5 items, item activo resaltado |
| 5 (Bottom Nav) | Desktop (1200px) → `/dashboard` | Bottom nav NO visible |
| 6 (Notificaciones) | Click icono campana | Panel desplegable con filtros y lista |
| 7 (Busqueda) | Cmd+K → escribir query | Resultados cross-entity, recientes, fuzzy match |
| 8 (Quick-Start) | Primer login nuevo usuario | Overlay con 3 acciones rapidas |

### 14.3 Checklist de Regresion

- [ ] Meta-sites (pepejaraba, jarabaimpact, PED) NO muestran bottom nav
- [ ] Meta-sites NO muestran quick-start overlay
- [ ] SaaS principal: header, nav, footer sin cambios visuales
- [ ] Copilot FAB sigue funcionando
- [ ] Command bar (Cmd+K) sigue funcionando
- [ ] Todas las paginas cargan sin errores JS en consola
- [ ] SCSS compila sin errores
- [ ] Lighthouse score >= 80 en mobile
- [ ] `prefers-reduced-motion: reduce` desactiva animaciones
- [ ] Todos los textos nuevos aparecen en espanol + traducibles

### 14.4 Herramientas de Verificacion

- **Browser DevTools** — Inspector, Network, Console
- **Lighthouse** — Performance, Accessibility, Best Practices
- **Responsive mode** — 375px (mobile), 768px (tablet), 1200px (desktop)
- **Screen reader** — VoiceOver/NVDA para verificar ARIA labels
- **`lando drush cr`** — Cache rebuild tras cada cambio de template/SCSS

---

## 15. Ficheros Criticos — Resumen de Cambios

### 15.1 Ficheros a CREAR

| Fichero | Fase | Tipo | Lineas Est. |
|---------|------|------|-------------|
| `templates/partials/_skeleton.html.twig` | 1 | Twig Partial | ~45 |
| `scss/components/_skeleton.scss` | 1 | SCSS | ~90 |
| `templates/partials/_empty-state.html.twig` | 2 | Twig Partial | ~35 |
| `scss/components/_empty-state.scss` | 2 | SCSS | ~55 |
| `scss/components/_toasts.scss` | 4 | SCSS | ~70 |
| `js/fetch-retry.js` | 4 | JavaScript | ~85 |
| `templates/partials/_bottom-nav.html.twig` | 5 | Twig Partial | ~40 |
| `js/bottom-nav.js` | 5 | JavaScript | ~35 |
| `jaraba_notifications/` (modulo completo) | 6 | Drupal Module | ~400 |
| `templates/partials/_notification-panel.html.twig` | 6 | Twig Partial | ~50 |
| `scss/components/_notification-panel.scss` | 6 | SCSS | ~80 |
| `js/notification-panel.js` | 6 | JavaScript | ~120 |
| `src/Service/GlobalSearchService.php` | 7 | PHP Service | ~100 |
| `src/Controller/GlobalSearchController.php` | 7 | PHP Controller | ~50 |
| `templates/partials/_quick-start-overlay.html.twig` | 8 | Twig Partial | ~40 |
| `js/quick-start.js` | 8 | JavaScript | ~80 |

### 15.2 Ficheros a MODIFICAR

| Fichero | Fase | Cambio |
|---------|------|--------|
| `scss/main.scss` (theme) | 1,2,4,6 | 4 nuevos `@use` imports |
| `scss/components/_cards.scss` | 3 | Hover lift transition (~6 lineas) |
| `scss/components/_buttons.scss` | 3 | Click pulse (~3 lineas) |
| `scss/components/_slide-panel.scss` | 3 | Modal animation (~10 lineas) |
| `ecosistema_jaraba_theme.libraries.yml` | 4,5,6,8 | 4 nuevas librerias |
| `ecosistema_jaraba_theme.info.yml` | 4 | 1 libreria global nueva |
| `ecosistema_jaraba_theme.theme` | 5 | Body class `has-bottom-nav` en preprocess_html (~4 lineas) + library attach en preprocess_page (~3 lineas) |
| `templates/page.html.twig` | 5,6,8 | 3 `{% include %}` condicionales (~9 lineas) |
| `ecosistema_jaraba_core.routing.yml` | 6,7 | 6 nuevas rutas API |
| `js/command-bar.js` | 7 | Cambio endpoint busqueda + seccion recientes (~30 lineas) |
| Templates de 7 dashboards | 2 | Empty state condicional (~8 lineas c/u) |

### 15.3 Funciones Existentes Reutilizadas

| Funcion/Recurso | Fichero | Reutilizacion |
|----------------|---------|---------------|
| `jaraba_icon(cat, name, options)` | `ecosistema_jaraba_core` | Iconos en empty states, bottom nav |
| `Drupal.behaviors` + `once()` | Core Drupal | Todos los JS nuevos |
| `Drupal.t()` + `Drupal.url()` | Core Drupal | Traduccion + lang prefix en JS |
| `Drupal.checkPlain()` | Core Drupal | XSS prevention en command-bar, toasts |
| Shimmer keyframes | `_hero-landing.scss` | Skeleton screens |
| `.empty-state` class | `_jobseeker-dashboard.scss` | Base para empty state component |
| `.bottom-nav` CSS completo | `_mobile-components.scss:150-282` | Bottom nav (0 CSS nuevo) |
| `body.has-bottom-nav` | `_mobile-components.scss:280` | Padding compensatorio |
| `modal-system` library | `ecosistema_jaraba_core` | CRUD en modales desde CTAs |
| `_proactive-insights-bell.html.twig` | Theme partials | Bell icon para notificaciones |
| `_command-bar.html.twig` + JS | Theme partials + JS | Shell del command bar |
| SSE streaming patron | `copilot-chat-widget.js` | Patron EventSource reutilizable |
| `CommandRegistryService` | `ecosistema_jaraba_core` | Global search se registra como provider |
| `PremiumEntityFormBase` | `ecosistema_jaraba_core` | Notification admin form |
| `jaraba_onboarding` module | Custom module | Quick-start extiende wizard existente |
| `StylePresetService` | `ecosistema_jaraba_core` | Inyeccion de tokens CSS runtime |
| Theme Settings (7 tabs) | `.theme` file | Configuracion UI para colores, fuentes, layout |
