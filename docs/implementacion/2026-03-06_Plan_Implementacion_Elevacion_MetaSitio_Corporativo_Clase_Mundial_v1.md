# Plan de Implementacion: Elevacion del Meta-Sitio Corporativo a Clase Mundial

> **Codigo:** METASITE-ELEVATION-001
> **Version:** 1.0
> **Fecha:** 2026-03-06
> **Estado:** Especificacion Tecnica para Implementacion
> **Prioridad:** BLOCKER -- Prerrequisito para go-to-market efectivo
> **Dependencias:** 178_MetaSite_SaaS_Remediation_v1, MKTG-METASITE-001, UnifiedThemeResolverService, MetaSiteResolverService, AvatarDetectionService, SiteConfig, TenantThemeConfig
> **Directrices verificadas:** CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, SCSS-COLORMIX-001, SCSS-COMPILETIME-001, SCSS-001, ZERO-REGION-001/002/003, ROUTE-LANGPREFIX-001, SLIDE-PANEL-RENDER-001, DOC-GUARD-001, IMPLEMENTATION-CHECKLIST-001, RUNTIME-VERIFY-001

---

## Tabla de Contenidos (TOC)

1. [Contexto y Objetivos](#1-contexto-y-objetivos)
2. [Inventario de Archivos Afectados](#2-inventario-de-archivos-afectados)
3. [Componente 1: Activacion de Identidad Visual PED](#3-componente-1-activacion-de-identidad-visual-ped)
4. [Componente 2: Hero Dinamico por Audiencia](#4-componente-2-hero-dinamico-por-audiencia)
5. [Componente 3: Selector de Audiencia](#5-componente-3-selector-de-audiencia)
6. [Componente 4: Stats de Impacto](#6-componente-4-stats-de-impacto)
7. [Componente 5: Secciones de Verticales Destacados](#7-componente-5-secciones-de-verticales-destacados)
8. [Componente 6: Reorganizacion del Flujo de Conversion](#8-componente-6-reorganizacion-del-flujo-de-conversion)
9. [Componente 7: Partners Institucionales y Trust Bar](#9-componente-7-partners-institucionales-y-trust-bar)
10. [Componente 8: Navegacion por Audiencia](#10-componente-8-navegacion-por-audiencia)
11. [Componente 9: CTA Banner Final](#11-componente-9-cta-banner-final)
12. [Componente 10: Hero Personalizado por UTM/Cookie](#12-componente-10-hero-personalizado-por-utmcookie)
13. [Tabla de Correspondencia con Directrices](#13-tabla-de-correspondencia-con-directrices)
14. [Plan de Verificacion RUNTIME-VERIFY-001](#14-plan-de-verificacion-runtime-verify-001)
15. [Roadmap por Sprints](#15-roadmap-por-sprints)
16. [Registro de Cambios](#16-registro-de-cambios)

---

## 1. Contexto y Objetivos

### 1.1 Contexto

El documento de investigacion de mercado `MKTG-METASITE-001` (2026-03-06) identifica que el meta-sitio corporativo plataformadeecosistemas.es no conecta con los publicos objetivo en los primeros 3 segundos. La arquitectura tecnica es de clase mundial (12+ partials, tracking, Schema.org, A/B testing, 5 niveles de cascada tematica), pero el contenido y la priorizacion de audiencias no capitalizan los activos estrategicos del fundador ni los verticales con mayor TAM.

**Diagnostico critico**: El fichero `_ped-metasite.scss` (579 lineas) define una identidad visual premium con paleta azul-corporativo + dorado, pero `page--front.html.twig` usa los partials genericos con estilos naranja-impulso. El meta-sitio corporativo se ve identico a cualquier landing de vertical.

### 1.2 Objetivos

| Objetivo | Metrica | Umbral |
|----------|---------|--------|
| Conexion en 3 segundos | Time to first CTA click | < 8s |
| Segmentacion por audiencia | % visitantes que seleccionan audiencia | > 30% |
| Identidad corporativa PED | 100% secciones con paleta azul+dorado | 0 secciones con naranja generico |
| Verticales priorizados | JarabaLex, B2G, Empleabilidad destacados | 3 secciones especificas |
| Metricas de impacto | Stats muestran resultados humanos | 0 metricas tecnicas ("80+ modulos") |
| Flujo AIDA optimizado | Bounce rate homepage | < 50% (actual ~70%) |

### 1.3 Publicos Objetivo Priorizados (de MKTG-METASITE-001)

1. **Abogados y Despachos** (JarabaLex) -- TAM: 1.200M USD, 90.000 despachos, CAGR 18-28%
2. **Instituciones Publicas** (B2G) -- TAM: 20.000M EUR NGEU, 2.000+ Ayuntamientos con AEDL
3. **Profesionales en Transicion** (Empleabilidad) -- Funnel freemium, 3 casos de exito reales
4. **Emprendedores y PYMES** -- Addon cross-sell desde otros verticales

### 1.4 Alcance Tecnico

Este plan cubre EXCLUSIVAMENTE cambios en el frontend del meta-sitio (templates Twig, SCSS, JS, preprocess). NO incluye cambios en entidades, servicios PHP de backend ni routing nuevo. Toda la infraestructura necesaria ya existe (MetaSiteResolverService, AvatarDetectionService, HomepageDataService, A/B testing, tracking).

---

## 2. Inventario de Archivos Afectados

### 2.1 Archivos a Modificar

| Archivo | Tipo | Cambio |
|---------|------|--------|
| `templates/page--front.html.twig` | Twig | Reordenar includes, agregar nuevos partials, eliminar cross-pollination |
| `templates/partials/_hero.html.twig` | Twig | Agregar logica de audiencia, activar clases PED |
| `templates/partials/_stats.html.twig` | Twig | Cambiar metricas de producto a metricas de impacto |
| `templates/partials/_trust-bar.html.twig` | Twig | Logos SVG reales de tecnologia + credenciales institucionales |
| `scss/components/_ped-metasite.scss` | SCSS | Agregar estilos para nuevas secciones (audiencia-selector, vertical-highlight) |
| `ecosistema_jaraba_theme.theme` | PHP | Inyectar variable `audience` desde UTM/cookie en preprocess_page |
| `ecosistema_jaraba_theme.libraries.yml` | YAML | Registrar nueva library `audience-selector` si JS necesario |

### 2.2 Archivos Nuevos

| Archivo | Tipo | Proposito |
|---------|------|----------|
| `templates/partials/_audience-selector.html.twig` | Twig | 4 cards: Abogado, Institucion, Profesional, Empresa |
| `templates/partials/_vertical-highlight.html.twig` | Twig | Seccion destacada de un vertical con features + CTA (reutilizable) |
| `templates/partials/_partners-institucional.html.twig` | Twig | Logos de instituciones que han trabajado con Jose Jaraba |
| `templates/partials/_cta-banner-final.html.twig` | Twig | Banner de conversion final pre-footer |

### 2.3 Archivos NO Afectados (ya correctos)

| Archivo | Razon |
|---------|-------|
| `_header.html.twig` / `_header-classic.html.twig` | Megamenu ya segmenta por "Para Profesionales/Empresas/Instituciones" |
| `_testimonials.html.twig` | 3 casos reales, excelente calidad de contenido |
| `_product-demo.html.twig` | Mockup interactivo con 3 tabs, funciona bien |
| `_lead-magnet.html.twig` | Formulario con avatar, GDPR, funciona bien |
| `_copilot-fab.html.twig` | FAB de ventas con contexto de avatar |
| `_whatsapp-fab.html.twig` | WhatsApp sticky mobile |
| `_footer.html.twig` | Footer configurable desde UI |

---

## 3. Componente 1: Activacion de Identidad Visual PED

### 3.1 Problema

El `_ped-metasite.scss` define clases `.ped-hero`, `.ped-cifras`, `.ped-motores`, `.ped-audiencia`, `.ped-partners`, `.ped-cta-saas` con la paleta azul-corporativo (#233D63) + dorado (#C5A55A). Estas clases estan compiladas en `main.css` (linea 52 de `main.scss`: `@use 'components/ped-metasite'`), pero NO se aplican porque los partials de `page--front.html.twig` usan clases genericas (`.hero-landing`, `.features-section`, `.stats-section`).

Adicionalmente, el `.theme` ya inyecta la body class `meta-site-tenant-7` para el meta-sitio PED (linea 1343), y `_ped-metasite.scss` tiene un bloque `.meta-site-tenant-7 {}` (linea 557) que sobreescribe header/footer. Esto ya funciona.

### 3.2 Estrategia

**No duplicar clases ni crear conflictos.** El enfoque es:

1. El hero del meta-sitio PED usara una variante condicional dentro de `_hero.html.twig` que active las clases PED cuando se detecte `meta_site.group_id == 7`
2. Los nuevos parciales (_audience-selector, _vertical-highlight, _cta-banner-final) usaran directamente las clases `.ped-*` definidas en `_ped-metasite.scss`
3. Los parciales existentes (_stats, _trust-bar) se modificaran para aceptar una variante `ped` via variable Twig

### 3.3 Implementacion en _hero.html.twig

**Logica de decision**: Si estamos en el meta-sitio PED (`meta_site` variable inyectada en preprocess_page), aplicar clase `.ped-hero` como wrapper alternativo.

```twig
{# Detectar meta-sitio PED para aplicar identidad corporativa #}
{% set is_ped = meta_site is defined and meta_site and meta_site.group_id|default(0) == 7 %}

{% if is_ped %}
  <section class="ped-hero" aria-labelledby="hero-title">
    <div class="ped-hero__container">
      <span class="ped-hero__eyebrow">
        {% if h.eyebrow %}{{ h.eyebrow }}{% else %}
          {% trans %}25+ anos gestionando fondos europeos · 3 emprendedores lanzados · 11 agentes IA{% endtrans %}
        {% endif %}
      </span>
      <h1 id="hero-title">
        {% if h.title %}{{ h.title }}{% else %}
          {% trans %}Empleo, justicia y negocio digital — impulsados por IA, en una sola plataforma{% endtrans %}
        {% endif %}
      </h1>
      {# ... subtitulo, CTAs, trust bar PED ... #}
    </div>
  </section>
{% else %}
  {# Hero generico existente — sin cambios #}
  <section class="hero-landing hero-landing--{{ hero_layout }}" ...>
    {# ... contenido actual ... #}
  </section>
{% endif %}
```

**Importante**: La variable `meta_site` ya se inyecta en `ecosistema_jaraba_theme_preprocess_page()` (linea 2494 del `.theme`). No necesita codigo PHP nuevo.

### 3.4 SCSS -- Verificacion de Cumplimiento

El `_ped-metasite.scss` existente ya cumple la mayoria de directrices:
- Usa `@use 'sass:color'` (Dart Sass moderno, SCSS-001)
- Variables estaticas `$ped-azul`, `$ped-dorado` para funciones compile-time (SCSS-COMPILETIME-001)
- `var(--ej-*)` con fallbacks para propiedades runtime (CSS-VAR-ALL-COLORS-001)
- `color.adjust()` en vez de `darken()`/`lighten()`

**Gaps a corregir** en `_ped-metasite.scss`:
- Linea 72: `color: $ped-dorado;` --> Debe ser `color: var(--ej-color-gold, $ped-dorado);` o mantener estatico si solo aplica al meta-sitio PED (aceptable porque `.ped-*` solo se usa en PED)
- Lineas con `rgba($ped-dorado, 0.4)`: Migrar a `color-mix(in srgb, $ped-dorado 40%, transparent)` (SCSS-COLORMIX-001)

**Nota**: Dado que `$ped-azul` y `$ped-dorado` son variables locales del fichero (no tokens inyectables), su uso en `color.adjust()` y `rgba()` compile-time es VALIDO segun SCSS-COMPILETIME-001. Solo se deben migrar a `color-mix()` los `rgba()` que usan variables.

### 3.5 Directrices Aplicadas

| Directriz | Cumplimiento | Detalle |
|-----------|-------------|---------|
| CSS-VAR-ALL-COLORS-001 | Parcial | Variables PED locales aceptables; runtime properties usan `var(--ej-*)` |
| SCSS-COMPILETIME-001 | OK | `$ped-azul` es hex estatico para `color.adjust()` |
| SCSS-COLORMIX-001 | Pendiente | Migrar `rgba($ped-dorado, 0.4)` a `color-mix()` |
| SCSS-001 | OK | Cada parcial tiene `@use 'sass:color'` o `@use '../variables' as *` |
| ZERO-REGION-001 | OK | Hero condicional no usa `page.content` |

---

## 4. Componente 2: Hero Dinamico por Audiencia

### 4.1 Objetivo

El hero debe mostrar un mensaje diferente segun la audiencia detectada. La deteccion se hace en 3 niveles (sin JS, solo server-side):

1. **UTM parameter**: `?audience=legal`, `?audience=b2g`, `?audience=empleo`
2. **Cookie de sesion**: Si el visitante selecciono una audiencia en el Audiencia Selector
3. **Fallback**: Mensaje generico multi-audiencia

### 4.2 Inyeccion de Variable en Preprocess

**Archivo**: `ecosistema_jaraba_theme.theme` (funcion `ecosistema_jaraba_theme_preprocess_page`)

**Logica**: Agregar al bloque existente de `$is_front` (linea 2598):

```php
// Detectar audiencia del visitante para hero personalizado.
$audience = NULL;
$request = \Drupal::request();

// Nivel 1: UTM parameter.
$audience = $request->query->get('audience');

// Nivel 2: Cookie de sesion (set by audience selector JS).
if (empty($audience) && $request->cookies->has('jaraba_audience')) {
  $audience = $request->cookies->get('jaraba_audience');
}

// Nivel 3: AvatarDetectionService (si disponible).
if (empty($audience) && \Drupal::hasService('ecosistema_jaraba_core.avatar_detection')) {
  try {
    $detection = \Drupal::service('ecosistema_jaraba_core.avatar_detection')->detect();
    $avatarMap = [
      'lawyer' => 'legal',
      'government' => 'b2g',
      'jobseeker' => 'empleo',
      'entrepreneur' => 'emprendimiento',
    ];
    $audience = $avatarMap[$detection->getAvatarType()] ?? NULL;
  }
  catch (\Throwable) {}
}

// Whitelist de audiencias validas.
$validAudiences = ['legal', 'b2g', 'empleo', 'emprendimiento'];
$variables['audience'] = in_array($audience, $validAudiences, TRUE) ? $audience : NULL;
```

### 4.3 Template Twig -- Hero con Variantes

**Archivo**: `_hero.html.twig`

Se definen 4 variantes de contenido (eyebrow, h1, subtitle, trust badges) indexadas por audiencia. El fallback generico ya existe.

```twig
{% set audience = audience|default(null) %}

{# Contenido por audiencia — fallbacks i18n #}
{% set hero_variants = {
  'legal': {
    'eyebrow': '+154.000 abogados en Espana aun sin IA en su despacho'|t,
    'title': 'Tu despacho merece inteligencia legal de verdad — a precio de autonomo'|t,
    'subtitle': 'Busca en CENDOJ, BOE y EUR-Lex con IA. Gestiona expedientes, agenda, facturacion y LexNET. Desde 29 EUR/mes.'|t,
    'cta_primary_text': 'Prueba JarabaLex gratis'|t,
    'cta_primary_url': '/jarabalex',
    'cta_secondary_text': 'Ver demo de 2 minutos'|t,
  },
  'b2g': {
    'eyebrow': '25,9M EUR en programas FSE gestionados con exito'|t,
    'title': 'La plataforma digital para programas de empleo y emprendimiento con fondos europeos'|t,
    'subtitle': 'Gestion integral de participantes, fases, horas IA-tracked, justificacion FSE y copiloto para tecnicos.'|t,
    'cta_primary_text': 'Solicitar demo institucional'|t,
    'cta_primary_url': '/contacto',
    'cta_secondary_text': 'Descargar dossier'|t,
  },
  'empleo': {
    'eyebrow': '3 emprendedores lanzados en el programa Andalucia +ei'|t,
    'title': 'Tu proximo paso profesional empieza aqui — con IA que te guia'|t,
    'subtitle': 'Diagnostico de competencias, CV con IA, preparacion de entrevistas y matching inteligente. Gratis para empezar.'|t,
    'cta_primary_text': 'Empieza gratis'|t,
    'cta_primary_url': '/user/register',
    'cta_secondary_text': 'Conoce el metodo'|t,
  },
} %}

{% set variant = hero_variants[audience]|default(null) %}
```

Todos los textos usan `|t` para traducibilidad (directriz i18n). Los URLs usan paths internos que se resuelven con el prefijo de idioma.

### 4.4 Directrices Aplicadas

| Directriz | Cumplimiento | Detalle |
|-----------|-------------|---------|
| ROUTE-LANGPREFIX-001 | Los CTAs usan `path()` o paths que Drupal resuelve con prefijo | URLs no hardcodeados con /es/ |
| i18n (trans/t) | Todos los textos con `\|t` en arrays Twig o `{% trans %}` en bloques | |
| ZERO-REGION-003 | Variable `audience` inyectada en preprocess, no en controller | |
| INNERHTML-XSS-001 | No hay innerHTML; todo es Twig autoescaped | |

---

## 5. Componente 3: Selector de Audiencia

### 5.1 Objetivo

Reemplazar el actual `_vertical-selector.html.twig` (6 cards de verticales) por un selector de 4 audiencias: **Abogado, Institucion, Profesional, Empresa**. Al seleccionar, establece una cookie y hace scroll suave a la seccion del vertical correspondiente.

### 5.2 Nuevo Parcial: _audience-selector.html.twig

```twig
{#
/**
 * @file _audience-selector.html.twig
 *
 * Selector de audiencia para el meta-sitio corporativo PED.
 * 4 cards interactivas que segmentan al visitante y hacen scroll
 * a la seccion del vertical mas relevante.
 *
 * DIRECTRICES:
 * - i18n: {% trans %} en todos los textos
 * - ICON-CONVENTION-001: jaraba_icon() con variante duotone
 * - ICON-COLOR-001: Solo colores de la paleta Jaraba
 * - CSS: BEM .ped-audiencia__*, var(--ej-*)
 * - A11y: aria-label, role="list", focus visible
 * - Tracking: data-track-cta, data-track-position
 */
#}

<section class="ped-audiencia" id="audiencia" aria-labelledby="audiencia-title">
  <h2 id="audiencia-title" class="ped-audiencia__title">
    {% trans %}Encuentra tu solucion{% endtrans %}
  </h2>

  <div class="ped-audiencia__grid" role="list">
    {# Card: Abogados #}
    <a href="#vertical-legal"
       class="ped-audiencia__card ped-audiencia__card--legal"
       role="listitem"
       data-audience="legal"
       data-track-cta="audience_legal"
       data-track-position="homepage_audience_selector"
       aria-label="{% trans %}Soluciones para abogados y despachos{% endtrans %}">
      <div class="ped-audiencia__icon">
        {{ jaraba_icon('ui', 'legal', { variant: 'duotone', color: 'azul-corporativo', size: '32px' }) }}
      </div>
      <h3>{% trans %}Abogados{% endtrans %}</h3>
      <p>{% trans %}Inteligencia legal IA, gestion de expedientes y busqueda en CENDOJ/BOE{% endtrans %}</p>
      <span class="ped-audiencia__cta">{% trans %}Descubrir JarabaLex{% endtrans %}</span>
    </a>

    {# Card: Instituciones #}
    <a href="#vertical-b2g"
       class="ped-audiencia__card ped-audiencia__card--institucion"
       role="listitem"
       data-audience="b2g"
       data-track-cta="audience_b2g"
       data-track-position="homepage_audience_selector"
       aria-label="{% trans %}Soluciones para instituciones publicas{% endtrans %}">
      <div class="ped-audiencia__icon">
        {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '32px' }) }}
      </div>
      <h3>{% trans %}Instituciones{% endtrans %}</h3>
      <p>{% trans %}Gestion de programas de empleo y emprendimiento con fondos europeos{% endtrans %}</p>
      <span class="ped-audiencia__cta">{% trans %}Ver soluciones B2G{% endtrans %}</span>
    </a>

    {# Card: Profesionales #}
    <a href="#vertical-empleo"
       class="ped-audiencia__card ped-audiencia__card--profesional"
       role="listitem"
       data-audience="empleo"
       data-track-cta="audience_empleo"
       data-track-position="homepage_audience_selector"
       aria-label="{% trans %}Soluciones para profesionales en busqueda de empleo{% endtrans %}">
      <div class="ped-audiencia__icon">
        {{ jaraba_icon('users', 'user', { variant: 'duotone', color: 'verde-innovacion', size: '32px' }) }}
      </div>
      <h3>{% trans %}Profesionales{% endtrans %}</h3>
      <p>{% trans %}Diagnostico, CV con IA, matching inteligente y copiloto de carrera{% endtrans %}</p>
      <span class="ped-audiencia__cta">{% trans %}Impulsar mi carrera{% endtrans %}</span>
    </a>

    {# Card: Empresas #}
    <a href="#vertical-empresa"
       class="ped-audiencia__card ped-audiencia__card--empresa"
       role="listitem"
       data-audience="emprendimiento"
       data-track-cta="audience_empresa"
       data-track-position="homepage_audience_selector"
       aria-label="{% trans %}Soluciones para empresas y emprendedores{% endtrans %}">
      <div class="ped-audiencia__icon">
        {{ jaraba_icon('actions', 'rocket', { variant: 'duotone', color: 'naranja-impulso', size: '32px' }) }}
      </div>
      <h3>{% trans %}Empresas{% endtrans %}</h3>
      <p>{% trans %}Emprendimiento, comercio digital, agro y servicios profesionales{% endtrans %}</p>
      <span class="ped-audiencia__cta">{% trans %}Explorar verticales{% endtrans %}</span>
    </a>
  </div>
</section>
```

### 5.3 SCSS -- Reutilizar .ped-audiencia

Los estilos ya estan definidos en `_ped-metasite.scss` (lineas 305-394): `.ped-audiencia`, `.ped-audiencia__grid`, `.ped-audiencia__card`, `.ped-audiencia__icon`, `.ped-audiencia__cta`. Solo necesitamos agregar la variante de color por card-type (ya tiene `--inversor`, `--institucion`, `--prensa`, `--empleo`; ajustar a `--legal`, `--institucion`, `--profesional`, `--empresa`).

**Adicion al SCSS** (en `_ped-metasite.scss`):

```scss
// Variantes de color por audiencia (complementar las existentes)
.ped-audiencia {
  &__card--legal .ped-audiencia__icon {
    background: color-mix(in srgb, var(--ej-color-corporate, $ped-azul) 10%, transparent);
    color: var(--ej-color-corporate, $ped-azul);
  }
  &__card--profesional .ped-audiencia__icon {
    background: color-mix(in srgb, var(--ej-color-secondary, #00A9A5) 10%, transparent);
    color: var(--ej-color-secondary, #00A9A5);
  }
  &__card--empresa .ped-audiencia__icon {
    background: color-mix(in srgb, var(--ej-color-primary, #FF8C42) 10%, transparent);
    color: var(--ej-color-primary, #FF8C42);
  }
}
```

### 5.4 Comportamiento JS

El selector de audiencia necesita minimo JS para:
1. Establecer cookie `jaraba_audience` (valor: `legal|b2g|empleo|emprendimiento`, 30 dias)
2. Scroll suave al anchor correspondiente

Este JS puede integrarse en el `metasite-tracking.js` existente via un nuevo behavior `Drupal.behaviors.audienceSelector`, o como inline en el parcial. Dado que `metasite-tracking.js` ya trackea CTA clicks via `data-track-cta`, solo necesitamos el cookie setter:

```javascript
// Dentro de Drupal.behaviors o como script inline del parcial
document.querySelectorAll('[data-audience]').forEach(function(card) {
  card.addEventListener('click', function() {
    var audience = card.getAttribute('data-audience');
    document.cookie = 'jaraba_audience=' + audience + ';path=/;max-age=' + (30*86400) + ';SameSite=Lax';
  });
});
```

### 5.5 Directrices Aplicadas

| Directriz | Cumplimiento | Detalle |
|-----------|-------------|---------|
| ICON-CONVENTION-001 | `jaraba_icon('category', 'name', { variant: 'duotone', color: 'palette-color', size: 'Npx' })` | Firma correcta |
| ICON-DUOTONE-001 | `variant: 'duotone'` en todas las cards | Default premium |
| ICON-COLOR-001 | `azul-corporativo`, `verde-innovacion`, `naranja-impulso` | Solo paleta Jaraba |
| CSS-VAR-ALL-COLORS-001 | `var(--ej-color-corporate, $ped-azul)` en SCSS | Con fallback |
| SCSS-COLORMIX-001 | `color-mix(in srgb, ... 10%, transparent)` | Moderno, no rgba() |
| i18n | `{% trans %}` en todos los textos | Traducible |
| A11y | `aria-label`, `role="list"`, `role="listitem"` | WCAG 2.1 AA |

---

## 6. Componente 4: Stats de Impacto

### 6.1 Problema

Los stats actuales en `_stats.html.twig` muestran metricas tecnicas:
- 10 verticales especializados
- 11 agentes IA Gen 2
- 80+ modulos custom Drupal 11
- 100% recomendarian el programa

Estas metricas no conectan emocionalmente con ninguna audiencia. Un abogado no se impresiona por "80+ modulos".

### 6.2 Nuevas Metricas de Impacto

Cambiar los `default_stats` en `_stats.html.twig`:

```twig
{% set default_stats = [
  { value: 100, suffix: 'M+', label: 'EUR dinamizados en proyectos'|t },
  { value: 25, suffix: '+', label: 'anos gestionando fondos europeos'|t },
  { value: 3, suffix: '', label: 'emprendedores lanzados con exito'|t },
  { value: 11, suffix: '', label: 'agentes IA especializados'|t }
] %}
```

**Justificacion**: Todas las metricas son verificables:
- 100M+ EUR: documentado en CV (Campina Sur: 100M+ EUR dinamizados)
- 25+ anos: 1997-presente (Campina Sur + SODEPO + PED)
- 3 emprendedores: Marcela Calabia, Angel Martinez, Luis Miguel Criado
- 11 agentes: BaseAgent + SmartBaseAgent subclasses

### 6.3 Variante Visual PED

Agregar clase condicional para que los stats usen la estetica PED cuando estemos en el meta-sitio:

```twig
<section class="{% if is_ped %}ped-cifras{% else %}stats-section{% endif %} reveal-element reveal-fade-up"
         aria-labelledby="stats-title">
```

Los estilos `.ped-cifras` ya existen en `_ped-metasite.scss` (lineas 148-213) con glassmorphism, gradiente dorado en numeros y hover con elevation.

---

## 7. Componente 5: Secciones de Verticales Destacados

### 7.1 Objetivo

Crear un parcial reutilizable `_vertical-highlight.html.twig` que muestra un vertical en formato expandido: titulo, 3-4 feature cards, CTA especifico, y opcionalmente una comparativa de precios.

### 7.2 Parcial Reutilizable

```twig
{#
/**
 * @file _vertical-highlight.html.twig
 *
 * Seccion destacada de un vertical especifico.
 * Reutilizable: se invoca con datos diferentes para cada vertical.
 *
 * VARIABLES:
 * - highlight_id: string -- Anchor ID (ej: 'vertical-legal')
 * - highlight_eyebrow: string -- Texto superior pequeno
 * - highlight_title: string -- Titulo principal
 * - highlight_subtitle: string -- Descripcion
 * - highlight_features: array -- [{icon_cat, icon_name, title, description}]
 * - highlight_cta_text: string -- Texto del boton
 * - highlight_cta_url: string -- URL del boton
 * - highlight_variant: string -- 'dark'|'light'|'gold' (estilo visual)
 * - highlight_comparison: array|null -- [{competitor, price, features}]
 *
 * DIRECTRICES:
 * - i18n: Textos traducidos ANTES de pasar al parcial
 * - ICON-CONVENTION-001: jaraba_icon() en features
 * - CSS: BEM .ped-vertical-highlight__*
 */
#}

<section class="ped-vertical-highlight ped-vertical-highlight--{{ highlight_variant|default('light') }}"
         id="{{ highlight_id }}"
         aria-labelledby="{{ highlight_id }}-title">
  <div class="ped-vertical-highlight__container">

    {% if highlight_eyebrow is defined and highlight_eyebrow %}
      <span class="ped-vertical-highlight__eyebrow">{{ highlight_eyebrow }}</span>
    {% endif %}

    <h2 id="{{ highlight_id }}-title" class="ped-vertical-highlight__title">
      {{ highlight_title }}
    </h2>

    {% if highlight_subtitle is defined and highlight_subtitle %}
      <p class="ped-vertical-highlight__subtitle">{{ highlight_subtitle }}</p>
    {% endif %}

    <div class="ped-vertical-highlight__grid">
      {% for feature in highlight_features|default([]) %}
        <div class="ped-vertical-highlight__feature">
          <div class="ped-vertical-highlight__feature-icon">
            {{ jaraba_icon(feature.icon_cat, feature.icon_name, {
              variant: 'duotone',
              color: feature.icon_color|default('azul-corporativo'),
              size: '28px'
            }) }}
          </div>
          <h3>{{ feature.title }}</h3>
          <p>{{ feature.description }}</p>
        </div>
      {% endfor %}
    </div>

    {# Comparativa de precios (opcional, para JarabaLex) #}
    {% if highlight_comparison is defined and highlight_comparison %}
      <div class="ped-vertical-highlight__comparison">
        {% for item in highlight_comparison %}
          <div class="ped-vertical-highlight__comparison-item{% if item.is_jaraba|default(false) %} ped-vertical-highlight__comparison-item--featured{% endif %}">
            <span class="ped-vertical-highlight__comparison-name">{{ item.name }}</span>
            <span class="ped-vertical-highlight__comparison-price">{{ item.price }}</span>
            <span class="ped-vertical-highlight__comparison-desc">{{ item.description }}</span>
          </div>
        {% endfor %}
      </div>
    {% endif %}

    <div class="ped-vertical-highlight__cta">
      <a href="{{ highlight_cta_url }}"
         class="btn-gold"
         data-track-cta="vertical_highlight_{{ highlight_id }}"
         data-track-position="homepage_vertical_highlight">
        {{ highlight_cta_text }}
      </a>
    </div>

  </div>
</section>
```

### 7.3 Invocacion desde page--front.html.twig

```twig
{# VERTICAL DESTACADO: JarabaLex (audiencia: Abogados) #}
{% include '@ecosistema_jaraba_theme/partials/_vertical-highlight.html.twig' with {
  highlight_id: 'vertical-legal',
  highlight_variant: 'dark',
  highlight_eyebrow: 'JarabaLex — Inteligencia Legal IA'|t,
  highlight_title: 'Todo lo que tu despacho necesita. Al precio que mereces.'|t,
  highlight_subtitle: 'Busqueda semantica en 8 fuentes oficiales, gestion de expedientes, agenda, facturacion, LexNET y un copiloto legal que te ahorra el 30% de tu tiempo.'|t,
  highlight_features: [
    { icon_cat: 'ui', icon_name: 'search', icon_color: 'white', title: 'Busqueda IA multi-fuente'|t, description: 'CENDOJ, EUR-Lex, CURIA, HUDOC, BOE, DGT, TEAC, EDPB'|t },
    { icon_cat: 'ui', icon_name: 'folder', icon_color: 'white', title: 'Gestion integral'|t, description: 'Expedientes, agenda juridica, boveda documental, facturacion'|t },
    { icon_cat: 'ui', icon_name: 'link', icon_color: 'white', title: 'LexNET integrado'|t, description: 'Comunicaciones judiciales sin salir de la plataforma'|t },
    { icon_cat: 'ai', icon_name: 'copilot', icon_color: 'white', title: 'Copiloto legal IA'|t, description: 'Redaccion, analisis jurisprudencial, alertas normativas'|t },
  ],
  highlight_comparison: [
    { name: 'Aranzadi', price: '300+ EUR/mes'|t, description: 'Solo base de datos'|t },
    { name: 'vLex', price: '150+ EUR/mes'|t, description: 'Solo busqueda'|t },
    { name: 'JarabaLex', price: '59 EUR/mes'|t, description: 'TODO integrado con IA'|t, is_jaraba: true },
  ],
  highlight_cta_text: 'Prueba JarabaLex gratis 14 dias'|t,
  highlight_cta_url: '/jarabalex',
} %}
```

### 7.4 SCSS -- Nuevos Estilos en _ped-metasite.scss

```scss
// Vertical Highlight — Seccion destacada de vertical
.ped-vertical-highlight {
  padding: var(--ej-space-xxl, 5rem) var(--ej-space-lg, 2rem);

  &__container {
    max-width: 1100px;
    margin: 0 auto;
    text-align: center;
  }

  &__eyebrow {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: $ped-dorado;
    margin-bottom: var(--ej-space-md, 1.5rem);
  }

  &__title {
    font-family: var(--ej-font-heading, 'Outfit', sans-serif);
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 700;
    margin: 0 0 var(--ej-space-sm, 1rem);
  }

  &__subtitle {
    font-size: var(--ej-text-lg, 1.15rem);
    max-width: 700px;
    margin: 0 auto var(--ej-space-xl, 3rem);
    line-height: 1.7;
  }

  &__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--ej-space-md, 1.5rem);
    margin-bottom: var(--ej-space-xl, 3rem);

    @media (min-width: 640px) { grid-template-columns: repeat(2, 1fr); }
    @media (min-width: 1024px) { grid-template-columns: repeat(4, 1fr); }
  }

  &__feature {
    text-align: center;
    padding: var(--ej-space-lg, 2rem) var(--ej-space-md, 1rem);

    h3 {
      font-family: var(--ej-font-heading, 'Outfit', sans-serif);
      font-size: var(--ej-text-base, 1rem);
      font-weight: 700;
      margin: var(--ej-space-sm, 0.75rem) 0 var(--ej-space-xs, 0.5rem);
    }

    p {
      font-size: var(--ej-text-sm, 0.875rem);
      line-height: 1.5;
      margin: 0;
    }
  }

  &__feature-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--ej-radius-md, 0.75rem);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
  }

  // Variante oscura (para JarabaLex, B2G)
  &--dark {
    background: $ped-gradient;
    color: var(--ej-bg-surface, #fff);

    .ped-vertical-highlight__title { color: var(--ej-bg-surface, #fff); }
    .ped-vertical-highlight__subtitle { color: color-mix(in srgb, white 80%, transparent); }

    .ped-vertical-highlight__feature {
      background: color-mix(in srgb, white 6%, transparent);
      backdrop-filter: blur(16px);
      border: 1px solid color-mix(in srgb, white 10%, transparent);
      border-radius: var(--ej-radius-xl, 1.5rem);
      transition: all 0.25s ease;

      &:hover {
        background: color-mix(in srgb, white 10%, transparent);
        transform: translateY(-4px);
      }

      p { color: color-mix(in srgb, white 70%, transparent); }
    }

    .ped-vertical-highlight__feature-icon {
      background: color-mix(in srgb, $ped-dorado 15%, transparent);
      color: $ped-dorado;
    }
  }

  // Variante clara (para Empleabilidad, Empresas)
  &--light {
    background: var(--ej-bg-section-alt, #f8fafc);

    .ped-vertical-highlight__title { color: var(--ej-color-corporate, $ped-azul); }
    .ped-vertical-highlight__subtitle { color: var(--ej-color-muted, #6b7280); }

    .ped-vertical-highlight__feature {
      background: var(--ej-bg-glass, rgba(255, 255, 255, 0.8));
      backdrop-filter: blur(12px);
      border: 1px solid var(--ej-border-glass, rgba(255, 255, 255, 0.3));
      border-radius: var(--ej-radius-xl, 1.5rem);
      transition: all 0.2s ease;

      &:hover {
        transform: translateY(-4px);
        box-shadow: var(--ej-shadow-lg, 0 8px 32px rgba(0, 0, 0, 0.14));
      }
    }
  }

  // Comparativa de precios
  &__comparison {
    display: flex;
    gap: var(--ej-space-md, 1.5rem);
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: var(--ej-space-xl, 3rem);
  }

  &__comparison-item {
    padding: var(--ej-space-md, 1.5rem);
    border-radius: var(--ej-radius-xl, 1.5rem);
    min-width: 200px;
    text-align: center;

    &--featured {
      background: $ped-gradient-gold;
      color: $ped-azul;
      transform: scale(1.05);
      box-shadow: 0 8px 30px color-mix(in srgb, $ped-dorado 30%, transparent);

      .ped-vertical-highlight__comparison-price {
        font-size: 1.5rem;
        font-weight: 800;
      }
    }
  }

  &__comparison-name {
    display: block;
    font-weight: 700;
    margin-bottom: var(--ej-space-xs, 0.5rem);
  }

  &__comparison-price {
    display: block;
    font-family: var(--ej-font-heading, 'Outfit', sans-serif);
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--ej-space-xs, 0.5rem);
  }

  &__comparison-desc {
    font-size: var(--ej-text-sm, 0.875rem);
  }

  // CTA
  &__cta {
    .btn-gold {
      @extend .ped-cta-saas .btn-gold;
    }
  }
}
```

**Nota sobre `@extend`**: Si el `.btn-gold` no es extensible por scope, definir estilos inline o crear un mixin. En `_ped-metasite.scss` el `.btn-gold` esta anidado dentro de `.ped-cta-saas`, por lo que no es extensible directamente. Mejor duplicar los estilos del boton o crear un `%btn-gold` placeholder.

---

## 8. Componente 6: Reorganizacion del Flujo de Conversion

### 8.1 Orden Actual vs. Propuesto

| Posicion | Actual | Propuesto | Razon |
|----------|--------|-----------|-------|
| 1 | Hero (generico) | Hero PED (por audiencia) | Conexion 3 segundos |
| 2 | Trust Bar | **Audiencia Selector** | Segmentar antes de informar |
| 3 | Vertical Selector (6 cards) | **Stats de Impacto** | Credibilidad cuantificable |
| 4 | Features (3 cards) | **Vertical Highlight: JarabaLex** (dark) | Vertical #1 por TAM |
| 5 | Stats | **Vertical Highlight: B2G** (light) | Vertical #2 por ventaja competitiva |
| 6 | Product Demo | **Product Demo** (sin cambios) | Funciona bien |
| 7 | Lead Magnet | **Testimonios** (subidos) | Social proof antes de captura |
| 8 | Cross-Pollination | **Trust Bar + Partners** (movido) | Credibilidad post-social-proof |
| 9 | Testimonios | **Lead Magnet** (sin cambios) | Captura despues de convencer |
| 10 | -- | **CTA Banner Final** (nuevo) | Ultima oportunidad de conversion |
| 11 | -- | Footer (sin cambios) | -- |

### 8.2 Implementacion en page--front.html.twig

```twig
{# Deteccion de meta-sitio PED #}
{% set is_ped = meta_site is defined and meta_site and meta_site.group_id|default(0) == 7 %}

{# ============================================================ #}
{# FLUJO AIDA OPTIMIZADO (solo para meta-sitio PED)            #}
{# Atencion -> Interes -> Deseo -> Accion                       #}
{# ============================================================ #}

{% if is_ped %}

  {# 1. HERO PED — Atencion (3 segundos) #}
  {% include '@ecosistema_jaraba_theme/partials/_hero.html.twig' with {
    theme_settings: theme_settings,
    logged_in: logged_in,
    hero: homepage.hero|default({}),
    audience: audience|default(null),
    meta_site: meta_site,
  } %}

  {# 2. AUDIENCIA SELECTOR — Interes (5 segundos) #}
  {% include '@ecosistema_jaraba_theme/partials/_audience-selector.html.twig' %}

  {# 3. STATS DE IMPACTO — Interes (10 segundos) #}
  {% include '@ecosistema_jaraba_theme/partials/_stats.html.twig' with {
    stats: homepage.stats|default([]),
    is_ped: true,
  } %}

  {# 4. VERTICAL DESTACADO: JarabaLex — Deseo #}
  {% include '@ecosistema_jaraba_theme/partials/_vertical-highlight.html.twig' with {
    highlight_id: 'vertical-legal',
    highlight_variant: 'dark',
    {# ... datos JarabaLex ... #}
  } %}

  {# 5. VERTICAL DESTACADO: Instituciones — Deseo #}
  {% include '@ecosistema_jaraba_theme/partials/_vertical-highlight.html.twig' with {
    highlight_id: 'vertical-b2g',
    highlight_variant: 'light',
    {# ... datos B2G ... #}
  } %}

  {# 6. PRODUCT DEMO — Deseo (sin cambios) #}
  {% include '@ecosistema_jaraba_theme/partials/_product-demo.html.twig' %}

  {# 7. TESTIMONIOS — Deseo (subidos desde posicion 9) #}
  {% include '@ecosistema_jaraba_theme/partials/_testimonials.html.twig' %}

  {# 8. TRUST BAR + PARTNERS — Confianza #}
  {% include '@ecosistema_jaraba_theme/partials/_trust-bar.html.twig' %}
  {% include '@ecosistema_jaraba_theme/partials/_partners-institucional.html.twig' %}

  {# 9. LEAD MAGNET — Accion #}
  {% include '@ecosistema_jaraba_theme/partials/_lead-magnet.html.twig' with {
    lead_magnet_title: theme_settings.lead_magnet_title|default(null),
    lead_magnet_subtitle: theme_settings.lead_magnet_subtitle|default(null),
    lead_magnet_cta_text: theme_settings.lead_magnet_cta_text|default(null),
    lead_magnet_success_message: theme_settings.lead_magnet_success_message|default(null),
    lead_magnet_tenant_id: theme_settings.lead_magnet_tenant_id|default(5),
  } %}

  {# 10. CTA BANNER FINAL — Accion #}
  {% include '@ecosistema_jaraba_theme/partials/_cta-banner-final.html.twig' %}

{% else %}

  {# ============================================================ #}
  {# FLUJO GENERICO (SaaS principal, otros meta-sitios)           #}
  {# Se mantiene el orden actual sin cambios                      #}
  {# ============================================================ #}
  {% include '@ecosistema_jaraba_theme/partials/_hero.html.twig' with { ... } %}
  {% include '@ecosistema_jaraba_theme/partials/_trust-bar.html.twig' %}
  {% include '@ecosistema_jaraba_theme/partials/_vertical-selector.html.twig' with { ... } %}
  {% include '@ecosistema_jaraba_theme/partials/_features.html.twig' with { ... } %}
  {% include '@ecosistema_jaraba_theme/partials/_stats.html.twig' with { ... } %}
  {% include '@ecosistema_jaraba_theme/partials/_product-demo.html.twig' %}
  {% include '@ecosistema_jaraba_theme/partials/_lead-magnet.html.twig' with { ... } %}
  {% include '@ecosistema_jaraba_theme/partials/_cross-pollination.html.twig' %}
  {% include '@ecosistema_jaraba_theme/partials/_testimonials.html.twig' %}

{% endif %}
```

**Punto critico**: El flujo generico NO se modifica. El condicional `{% if is_ped %}` solo afecta al meta-sitio PED. Los demas meta-sitios y el SaaS principal mantienen el flujo actual.

---

## 9. Componente 7: Partners Institucionales y Trust Bar

### 9.1 Trust Bar -- Mejoras

Reemplazar logos de texto SVG por badges de credenciales verificables. El parcial actual (`_trust-bar.html.twig`) muestra "Drupal", "OpenAI", "Stripe", etc. como texto SVG. Para el meta-sitio PED, mostrar credenciales institucionales.

**Estrategia**: Agregar variable `is_ped` al parcial y renderizar contenido diferente.

### 9.2 Nuevo Parcial: _partners-institucional.html.twig

```twig
{#
/**
 * @file _partners-institucional.html.twig
 *
 * Logos de instituciones que han trabajado con Jose Jaraba / PED.
 * Solo se muestra en el meta-sitio corporativo PED.
 *
 * DIRECTRICES:
 * - i18n: {% trans %}
 * - A11y: aria-label, alt text en logos
 * - CSS: BEM .ped-partners__*
 */
#}

<section class="ped-partners" aria-label="{% trans %}Instituciones colaboradoras{% endtrans %}">
  <h2>{% trans %}Han confiado en nosotros{% endtrans %}</h2>
  <div class="ped-partners__grid">
    {# Logos representados como badges con nombre + tipo #}
    <div class="ped-partners__badge">
      {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
      <span>{% trans %}Junta de Andalucia{% endtrans %}</span>
    </div>
    <div class="ped-partners__badge">
      {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
      <span>{% trans %}Diputacion de Caceres{% endtrans %}</span>
    </div>
    <div class="ped-partners__badge">
      {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
      <span>{% trans %}Ayto. Alhaurin el Grande{% endtrans %}</span>
    </div>
    <div class="ped-partners__badge">
      {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
      <span>{% trans %}Ayto. Puente Genil{% endtrans %}</span>
    </div>
    <div class="ped-partners__badge">
      {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
      <span>{% trans %}Universidad de Cordoba{% endtrans %}</span>
    </div>
    <div class="ped-partners__badge">
      {{ jaraba_icon('ui', 'layers', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
      <span>{% trans %}Fundacion MADECA{% endtrans %}</span>
    </div>
  </div>
</section>
```

**SCSS**: Los estilos `.ped-partners` ya existen en `_ped-metasite.scss` (lineas 399-445). Agregar `.ped-partners__badge` como variante:

```scss
.ped-partners__badge {
  display: flex;
  align-items: center;
  gap: var(--ej-space-sm, 0.75rem);
  padding: var(--ej-space-sm, 0.75rem) var(--ej-space-md, 1.5rem);
  background: var(--ej-bg-glass, rgba(255, 255, 255, 0.8));
  backdrop-filter: blur(12px);
  border-radius: var(--ej-radius-pill, 50rem);
  border: 1px solid var(--ej-border-glass, rgba(255, 255, 255, 0.3));
  font-size: var(--ej-text-sm, 0.875rem);
  font-weight: 600;
  color: var(--ej-color-corporate, $ped-azul);
  transition: all 0.2s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: var(--ej-shadow-md, 0 4px 16px rgba(0, 0, 0, 0.1));
  }
}
```

---

## 10. Componente 8: Navegacion por Audiencia

### 10.1 Estado Actual

El header clasico (`_header-classic.html.twig`) ya tiene un megamenu con 3 columnas: "Para Profesionales", "Para Empresas", "Para Instituciones". Este megamenu se activa solo en el SaaS principal (linea 2591 de `.theme`: `header_megamenu = TRUE` cuando `meta_site` es NULL).

Para los meta-sitios, la navegacion usa `nav_items` planos inyectados por `MetaSiteResolverService`. El meta-sitio PED muestra: "Empleabilidad | Talento | Emprendimiento | ComercioConecta | Instituciones" (default del `_header.html.twig` linea 32).

### 10.2 Mejora Propuesta

**NO modificar el header template**. En su lugar, configurar los `navigation_items` del meta-sitio PED desde el SiteConfig o theme_settings para que reflejen las audiencias prioritarias:

```
Abogados|/jarabalex
Instituciones|/instituciones
Empleo|/empleabilidad
Emprendimiento|/emprendimiento
Precios|/planes
```

Esto se configura desde **Apariencia > Ecosistema Jaraba Theme > Encabezado > Elementos de Navegacion** (o desde SiteConfig del tenant PED). No requiere cambio de codigo.

### 10.3 Alternativa: Activar Megamenu en PED

Si se quiere activar el megamenu 3-columnas tambien en el meta-sitio PED, modificar la condicion en `.theme`:

```php
// Linea 2590 actual:
if (empty($variables['meta_site'])) {
    $variables['theme_settings']['header_megamenu'] = TRUE;
}

// Propuesta: Activar tambien para PED (group_id 7):
$groupId = $variables['meta_site']['group_id'] ?? 0;
if (empty($variables['meta_site']) || $groupId === 7) {
    $variables['theme_settings']['header_megamenu'] = TRUE;
}
```

**Recomendacion**: Empezar con navegacion plana configurada, evaluar si el megamenu aporta valor. El megamenu tiene sentido para el SaaS con muchos verticales; el meta-sitio PED necesita enfoque, no amplitud.

---

## 11. Componente 9: CTA Banner Final

### 11.1 Nuevo Parcial: _cta-banner-final.html.twig

```twig
{#
/**
 * @file _cta-banner-final.html.twig
 *
 * Banner de conversion final antes del footer.
 * Usa los estilos .ped-cta-saas de _ped-metasite.scss.
 *
 * DIRECTRICES: i18n (trans), ICON-CONVENTION-001, CSS var(--ej-*)
 */
#}

<section class="ped-cta-saas" aria-labelledby="cta-final-title">
  <div class="ped-cta-saas__container">
    <h2 id="cta-final-title">
      {% trans %}Empieza hoy. Sin compromiso.{% endtrans %}
    </h2>
    <p>
      {% trans %}Registro gratuito, sin tarjeta de credito. Acceso inmediato a tu vertical con copiloto IA incluido.{% endtrans %}
    </p>
    <div class="ped-cta-saas__actions">
      <a href="{{ path('user.register') }}"
         class="btn-gold"
         data-track-cta="final_cta_register"
         data-track-position="homepage_final_cta">
        {% trans %}Crear cuenta gratuita{% endtrans %}
      </a>
    </div>
  </div>
</section>
```

Los estilos `.ped-cta-saas` ya existen en `_ped-metasite.scss` (lineas 450-510). No se necesita SCSS nuevo.

**Nota ROUTE-LANGPREFIX-001**: El CTA usa `{{ path('user.register') }}` que Drupal resuelve automaticamente con el prefijo de idioma (`/es/user/register`).

---

## 12. Componente 10: Hero Personalizado por UTM/Cookie

### 12.1 Infraestructura Existente

El sistema de A/B testing (`ExperimentService`) ya gestiona variantes por cookie. El `metasite-experiments.js` ya lee `ab_variants` desde `drupalSettings` y aplica cambios DOM.

### 12.2 Enfoque Server-Side (Recomendado)

Para el hero por audiencia, usamos server-side rendering (no JS DOM manipulation) porque:
- Mejor SEO (Google ve el contenido correcto)
- Sin flash of content (FOUC)
- Mas simple: la variable `audience` se inyecta en preprocess y Twig decide

La logica esta descrita en el Componente 2 (seccion 4 de este documento). No se necesita JS adicional para la personalizacion del hero.

### 12.3 Tracking de Variantes

El JS de `metasite-tracking.js` ya trackea `data-track-cta` clicks. Para trackear que variante de hero se mostro, agregar un `data-hero-variant` en el hero:

```twig
<section class="ped-hero" data-hero-variant="{{ audience|default('default') }}" ...>
```

El tracking existente puede capturar esto via un evento de pageview personalizado.

---

## 13. Tabla de Correspondencia con Directrices

| Directriz | Componente(s) Afectado(s) | Cumplimiento | Nota |
|-----------|--------------------------|-------------|------|
| **CSS-VAR-ALL-COLORS-001** | Todos los SCSS | SI | Colores via `var(--ej-*, fallback)`, excepto variables PED locales para `color.adjust()` |
| **SCSS-COLORMIX-001** | _ped-metasite.scss | PENDIENTE | Migrar `rgba($ped-dorado, 0.4)` a `color-mix()` |
| **SCSS-COMPILETIME-001** | _ped-metasite.scss | OK | `$ped-azul`, `$ped-dorado` son hex estatico |
| **SCSS-001** | Todos los SCSS | OK | `@use 'sass:color'` en parciales que lo necesitan |
| **SCSS-COMPILE-VERIFY-001** | Post-implementacion | Obligatorio | `npm run build` + verificar timestamp CSS > SCSS |
| **SCSS-ENTRY-CONSOLIDATION-001** | N/A | OK | No hay conflictos de nombres |
| **ICON-CONVENTION-001** | _audience-selector, _vertical-highlight, _partners-institucional | SI | `jaraba_icon('category', 'name', { variant, color, size })` |
| **ICON-DUOTONE-001** | Todos los iconos nuevos | SI | `variant: 'duotone'` |
| **ICON-COLOR-001** | Todos los iconos nuevos | SI | Solo colores de paleta Jaraba |
| **ZERO-REGION-001** | page--front.html.twig | OK | Solo `{% include %}` de partials, no `page.content` |
| **ZERO-REGION-002** | N/A | OK | No se pasan entity objects como non-# keys |
| **ZERO-REGION-003** | .theme preprocess | OK | `audience` inyectado en preprocess, no controller |
| **ROUTE-LANGPREFIX-001** | CTAs en todos los partials | SI | `{{ path('route.name') }}` o paths internos |
| **i18n (trans/t)** | Todos los textos | SI | `{% trans %}` en bloques, `\|t` en arrays Twig |
| **DOC-GUARD-001** | Este documento | OK | Documento nuevo, no sobreescribe master docs |
| **IMPLEMENTATION-CHECKLIST-001** | Post-implementacion | Obligatorio | Ver seccion 14 |
| **RUNTIME-VERIFY-001** | Post-implementacion | Obligatorio | Ver seccion 14 |

---

## 14. Plan de Verificacion RUNTIME-VERIFY-001

Tras completar cada componente, verificar las 5 dependencias runtime:

### 14.1 CSS Compilado

```bash
# Dentro del contenedor Docker/Lando
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npm run build"
# Verificar timestamps
ls -la css/ecosistema-jaraba-theme.css scss/main.scss
# CSS timestamp DEBE ser > SCSS timestamp
```

### 14.2 Cache Drupal

```bash
lando drush cr
```

### 14.3 Rutas Accesibles

| Ruta | Esperado |
|------|----------|
| https://plataformadeecosistemas.jaraba-saas.lndo.site/es | Hero PED con paleta azul+dorado |
| https://plataformadeecosistemas.jaraba-saas.lndo.site/es?audience=legal | Hero variante abogados |
| https://plataformadeecosistemas.jaraba-saas.lndo.site/es?audience=b2g | Hero variante instituciones |
| https://plataformadeecosistemas.jaraba-saas.lndo.site/es?audience=empleo | Hero variante profesionales |

### 14.4 Selectores data-* Matchean

| Selector | Archivo HTML | Archivo JS |
|----------|-------------|-----------|
| `data-audience` | _audience-selector.html.twig | metasite-tracking.js (cookie setter) |
| `data-track-cta` | Todos los partials nuevos | metasite-tracking.js (ya existente) |
| `data-hero-variant` | _hero.html.twig | metasite-tracking.js (pageview) |

### 14.5 drupalSettings Inyectado

```javascript
// Verificar en consola del navegador:
console.log(drupalSettings.jarabaAnalytics); // Debe existir
console.log(drupalSettings.path.currentLanguage); // 'es'
```

### 14.6 Verificacion Visual

| Elemento | Verificacion |
|----------|-------------|
| Hero PED | Fondo gradiente azul (#233D63), textos blancos, CTAs dorados |
| Audiencia Selector | 4 cards con iconos duotone, hover con elevacion |
| Stats | Counter animation con numeros de impacto (100M+, 25+, 3, 11) |
| Vertical JarabaLex | Fondo oscuro, 4 features, comparativa precios |
| Testimonios | 3 cards con citas reales (Marcela, Angel, Luis Miguel) |
| CTA Final | Fondo gradiente azul, boton dorado "Crear cuenta gratuita" |
| Mobile | Todas las secciones responsive, grids colapsan a 1 columna |

---

## 15. Roadmap por Sprints

### Sprint 1 -- "Los 3 Segundos" (Componentes 1, 2, 3, 4, 6)

| Tarea | Archivos | Estimacion |
|-------|----------|-----------|
| 1.1 Condicional PED en _hero.html.twig | _hero.html.twig | Bajo |
| 1.2 Variable `audience` en preprocess | .theme | Bajo |
| 1.3 Hero variantes por audiencia | _hero.html.twig | Medio |
| 1.4 Parcial _audience-selector.html.twig | Nuevo | Medio |
| 1.5 SCSS audience-selector (variantes color) | _ped-metasite.scss | Bajo |
| 1.6 Cambiar stats a metricas de impacto | _stats.html.twig | Bajo |
| 1.7 Reorganizar page--front.html.twig (condicional PED) | page--front.html.twig | Medio |
| 1.8 Compilar SCSS + verificar | npm run build + drush cr | Bajo |
| 1.9 Verificacion RUNTIME-VERIFY-001 | Browser | Bajo |

### Sprint 2 -- Verticales + Social Proof (Componentes 5, 7, 9)

| Tarea | Archivos | Estimacion |
|-------|----------|-----------|
| 2.1 Parcial _vertical-highlight.html.twig | Nuevo | Alto |
| 2.2 SCSS vertical-highlight (dark/light) | _ped-metasite.scss | Medio |
| 2.3 Invocar 2 highlights (JarabaLex + B2G) | page--front.html.twig | Bajo |
| 2.4 Parcial _partners-institucional.html.twig | Nuevo | Bajo |
| 2.5 SCSS partners badge | _ped-metasite.scss | Bajo |
| 2.6 Parcial _cta-banner-final.html.twig | Nuevo | Bajo |
| 2.7 Migrar rgba() a color-mix() en _ped-metasite.scss | _ped-metasite.scss | Bajo |
| 2.8 Compilar + verificar | npm run build + drush cr | Bajo |

### Sprint 3 -- Refinamiento + Analytics (Componente 10)

| Tarea | Archivos | Estimacion |
|-------|----------|-----------|
| 3.1 data-hero-variant tracking | _hero.html.twig + JS | Bajo |
| 3.2 Cookie setter para audience selector | JS (inline o metasite-tracking) | Bajo |
| 3.3 Configurar nav_items del meta-sitio PED | SiteConfig o Theme Settings UI | Bajo |
| 3.4 Verificacion end-to-end con todas las audiencias | Browser | Medio |
| 3.5 Performance audit (Lighthouse) | Browser DevTools | Bajo |

---

## 16. Registro de Cambios

| Version | Fecha | Autor | Cambio |
|---------|-------|-------|--------|
| 1.0 | 2026-03-06 | Claude Opus 4.6 | Plan de implementacion inicial, 10 componentes, 3 sprints |
