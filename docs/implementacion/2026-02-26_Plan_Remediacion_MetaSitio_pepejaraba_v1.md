# Plan de Implementación: Remediación Integral Meta-Sitio pepejaraba.com

**Código:** 179_MetaSite_Remediation_Drupal11_v1  
**Versión:** 1.0  
**Fecha:** 2026-02-26  
**Estado:** Plan de Implementación para Aprobación  
**Entorno destino:** Drupal 11 SaaS (jaraba-saas.lndo.site → pepejaraba.com, Tenant ID=5)  
**Dependencias:** Doc 178 (Especificación ClaudeCode), Doc 05 (Theming), Doc 123 (Personal Brand), Doc 124 (Content Ready), Doc 146 (SendGrid ECA)  
**Prioridad:** BLOCKER — Prerrequisito para go-to-market  

---

## 📑 Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de Problemas y Correspondencia Doc 178](#2-inventario-de-problemas)
3. [Tabla de Cumplimiento de Directrices del Proyecto](#3-tabla-de-cumplimiento)
4. [Fase 1 — Lead Magnet: Kit de Impulso Digital](#4-fase-1-lead-magnet)
5. [Fase 2 — Casos de Éxito: Migración desde jarabaimpact.com](#5-fase-2-casos-de-exito)
6. [Fase 3 — Blog: 3 Artículos Reales](#6-fase-3-blog)
7. [Fase 4 — Pricing Dinámico desde ConfigEntities](#7-fase-4-pricing-dinamico)
8. [Fase 5 — Formulario de Contacto](#8-fase-5-formulario-contacto)
9. [Fase 6 — Mejoras de Conversión (Hero, CTAs, Testimonios)](#9-fase-6-mejoras-conversion)
10. [Fase 7 — SEO: Schema JSON-LD y Meta Tags](#10-fase-7-seo)
11. [Fase 8 — Cross-Pollination entre Dominios](#11-fase-8-cross-pollination)
12. [Fase 9 — Secuencia Email Post-Descarga Kit](#12-fase-9-email-sequence)
13. [Infraestructura Existente Confirmada](#13-infraestructura-existente)
14. [Roadmap de Sprints](#14-roadmap)
15. [Verificación y Criterios de Aceptación](#15-verificacion)

---

## 1. Resumen Ejecutivo

La auditoría multi-disciplinar del meta-sitio `pepejaraba.jaraba-saas.lndo.site/es` (futuro `pepejaraba.com`) identificó **14 problemas** (Doc 178) que impiden la conversión de visitantes en leads y clientes. Este plan detalla la implementación **definitiva en Drupal 11 SaaS**, aprovechando la infraestructura de módulos custom existente.

### Hallazgo clave: Todo existe ya

| Necesidad | Módulo | Entidad | Servicio/API |
|-----------|--------|---------|-------------|
| Lead capture | `jaraba_email` | `EmailSubscriber` | `/api/v1/email/subscribers/subscribe` |
| Blog | `jaraba_blog` | `BlogPost` | `BlogFrontendController` → `/blog/{slug}` |
| Pricing dinámico | `ecosistema_jaraba_core` | `SaasPlanTier` + `SaasPlanFeatures` | `PlanResolverService` |
| Homepage | `jaraba_page_builder` | `HomepageContent` | `HomepageDataService` |
| Casos de éxito | `jaraba_blog` | `BlogPost` (categoría) | `BlogFrontendController` |

### Contenido real extraído

Se extrajeron **3 casos de éxito reales** de `jarabaimpact.com` vía WP REST API:

| Caso | Protagonista | Programa | Cita clave |
|------|-------------|----------|-----------|
| Marcela Calabia | Coach Comunicación | Andalucía +ei | «Este curso es oro puro. Es el mejor tiempo invertido» |
| Ángel Martínez | Cofundador Camino Viejo | Andalucía +ei | «La formación Jaraba Impact de PED es oro puro… ES POSIBLE» |
| Luis Miguel Criado | Terapeuta autónomo | Andalucía +ei | «El programa me dio las herramientas para dar los pasos más difíciles» |

---

## 2. Inventario de Problemas

### Correspondencia con Doc 178

| ID Doc 178 | Severidad | Problema | Fase de este Plan | Módulo Drupal |
|-----------|-----------|---------|-------------------|--------------|
| BUG-001 | CRITICAL | CTA Kit Impulso href vacío | Fase 1 | `jaraba_email` |
| BUG-002 | CRITICAL | Página Casos de Éxito vacía | Fase 2 | `jaraba_blog` |
| BUG-003 | CRITICAL | Blog posts ficticios | Fase 3 | `jaraba_blog` |
| BUG-004 | HIGH | Formulario contacto no renderizado | Fase 5 | `ecosistema_jaraba_core` |
| BUG-005 | HIGH | No existe /servicios con Value Ladder | Fase 4 | `ecosistema_jaraba_core` |
| BUG-006 | HIGH | No hay secuencia email post-descarga | Fase 9 | `jaraba_email` |
| BUG-007 | HIGH | Testimonios reales solo en jarabaimpact | Fase 6 | `jaraba_page_builder` |
| BUG-008 | MEDIUM | Headline Hero infrautiliza credencial | Fase 6 | `jaraba_page_builder` |
| BUG-009 | MEDIUM | Botón Ecosistema sin contexto | Fase 6 | Theme settings |
| BUG-010 | MEDIUM | Schema JSON-LD no implementado | Fase 7 | Theme |
| BUG-011 | MEDIUM | Meta tags y OG tags ausentes | Fase 7 | Theme |
| BUG-012 | MEDIUM | Cross-pollination insuficiente | Fase 8 | Theme settings |
| BUG-013 | LOW | Kit PDF sin capturas | Fuera de alcance | — |
| BUG-014 | LOW | Falta WhatsApp sticky mobile | Fase 6 | Theme |

---

## 3. Tabla de Cumplimiento de Directrices del Proyecto

Cada fase de este plan **DEBE** cumplir con las siguientes directrices. La columna "Cómo se cumple" indica la acción concreta.

### 3.1 Directrices de Theming y SCSS

| Directriz | Referencia | Cómo se cumple |
|-----------|-----------|---------------|
| **SSOT Federated Design Tokens** | `2026-02-05_arquitectura_theming_saas_master.md` | Todo SCSS usa `var(--ej-*, $fallback)`. Ningún módulo define sus propias variables SCSS. |
| **Dart Sass moderno** | `/scss-estilos.md` workflow | `@use 'sass:color'`, `@use 'sass:math'`. Prohibido `@import`. |
| **CSS Custom Properties inyectables** | `_injectable.scss` | Valores configurables desde `/admin/appearance/settings/ecosistema_jaraba_theme` sin tocar código. |
| **Compilación SCSS** | `package.json` del tema | `npx sass scss/main.scss:css/main.css --style=compressed` dentro del contenedor Docker. |
| **Paleta de colores brand** | `_variables.scss` | Solo `--ej-naranja-impulso`, `--ej-azul-confianza`, `--ej-verde-crecimiento`, `--ej-gris-profesional`, etc. |

### 3.2 Directrices de Frontend y Templates

| Directriz | Referencia | Cómo se cumple |
|-----------|-----------|---------------|
| **Zero Region Policy** | `/frontend-page-pattern.md` | `{{ clean_content }}` en vez de `{{ page.content }}`. Sin `page.sidebar_*` ni bloques heredados. |
| **Full-width mobile-first** | `/frontend-page-pattern.md` | Layout 100vw, breakpoints `@media (min-width: 768px)` first. |
| **Reusable Twig partials** | `/frontend-page-pattern.md` | `{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}`. Antes de crear código, verificar si ya existe un partial. |
| **Body classes vía hook** | `/frontend-page-pattern.md` | `hook_preprocess_html()` para body classes. NO `attributes.addClass()` en templates. |
| **Header/footer propios** | `/frontend-page-pattern.md` | Cada `page--*.html.twig` incluye `_header.html.twig` y `_footer.html.twig` del tema. |
| **Modales para CRUD** | `/slide-panel-modales.md` | `data-slide-panel-url`, `data-slide-panel-width`. |
| **Sin admin theme para tenants** | `00_DIRECTRICES_PROYECTO.md` | Route subscribers + access control handlers. |

### 3.3 Directrices de i18n y Contenido

| Directriz | Referencia | Cómo se cumple |
|-----------|-----------|---------------|
| **Textos siempre traducibles** | `/i18n-traducciones.md` | Twig: `{% trans %}`, PHP: `$this->t()`, JS: `Drupal.t()` |
| **Theme settings para copy configurable** | Workflow SCSS | Títulos, CTA texts, mensajes de éxito configurables desde UI Drupal. |

### 3.4 Directrices de Iconos

| Directriz | Referencia | Cómo se cumple |
|-----------|-----------|---------------|
| **`jaraba_icon()` Twig function** | `2026-01-26_iconos_svg_landing_verticales.md` | `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'brand-color' }) }}` |
| **SVG en categorías** | `/images/icons/{category}/` | `business/`, `actions/`, `ui/` |
| **Prohibidos emojis Unicode** | ICON-EMOJI-001 | Solo SVG del icon system. |

### 3.5 Directrices de Entidades

| Directriz | Referencia | Cómo se cumple |
|-----------|-----------|---------------|
| **ContentEntity con Field UI** | `00_DIRECTRICES_PROYECTO.md` | `field_ui_base_route` en annotation, navegación en `/admin/structure` y `/admin/content`. |
| **Views integration** | `00_DIRECTRICES_PROYECTO.md` | `views_data` handler en todas las entidades. |
| **Tenant isolation** | `00_DIRECTRICES_PROYECTO.md` | Campo `tenant_id` en todas las entidades de contenido. |

### 3.6 Directrices de GrapesJS

| Directriz | Referencia | Cómo se cumple |
|-----------|-----------|---------------|
| **Interactividad dual** | `2026-02-05_especificacion_grapesjs_saas.md` | `script` property + `Drupal.behaviors` para páginas públicas. |
| **Theming con Design Tokens** | Spec GrapesJS | `--gjs-*` mapeados desde `--ej-*`. |
| **Sanitización onclick** | Spec GrapesJS | No usar `onclick` inline; usar `data-*` + behaviors. |

---

## 4. Fase 1 — Lead Magnet: Kit de Impulso Digital

> **Prioridad:** P0 CRITICAL · **BUG-001** · **Esfuerzo:** ~8h

### 4.1 Arquitectura

```
Hero CTA → ancla #kit-impulso
    ↓
_lead-magnet.html.twig (partial)
    ↓
Drupal.behaviors.jarabaLeadMagnet (JS)
    ↓
fetch() POST /api/v1/email/subscribers/subscribe
    ↓
EmailSubscriber entity (source: 'kit_impulso_digital', avatar_type tag)
    ↓
ECA trigger → jaraba_email sequence
    ↓
Redirect → /kit-impulso-gracias?avatar={type}
```

### 4.2 Archivos a crear/modificar

#### [NEW] `templates/partials/_lead-magnet.html.twig`

**Propósito:** Formulario glassmorphism de captura de email con segmentación por avatar.

**Variables desde theme_settings:**
- `lead_magnet_title` (default: `{% trans %}Descarga tu Kit de Impulso Digital Gratuito{% endtrans %}`)
- `lead_magnet_subtitle`
- `lead_magnet_cta_text` (default: `{% trans %}Descargar Kit →{% endtrans %}`)
- `lead_magnet_success_message`

**Estructura HTML:**
```twig
{# @file templates/partials/_lead-magnet.html.twig #}
{# Partial: Formulario Lead Magnet Kit de Impulso Digital #}
{# Requiere: library ecosistema_jaraba_theme/lead-magnet #}

<section id="kit-impulso" class="lead-magnet" aria-labelledby="lead-magnet-title">
  <div class="lead-magnet__container">
    <div class="lead-magnet__icon">
      {{ jaraba_icon('actions', 'download', { variant: 'duotone', color: 'naranja-impulso', size: 'xl' }) }}
    </div>
    <h2 id="lead-magnet-title" class="lead-magnet__title">
      {{ lead_magnet_title|default('Descarga tu Kit de Impulso Digital Gratuito'|t) }}
    </h2>
    <p class="lead-magnet__subtitle">
      {{ lead_magnet_subtitle|default('11 páginas con las 7 herramientas que uso con mis clientes. Sin humo.'|t) }}
    </p>

    <form class="lead-magnet__form" data-lead-magnet-form>
      <div class="lead-magnet__field">
        <label for="lead-magnet-email" class="visually-hidden">{{ 'Email'|t }}</label>
        <input type="email" id="lead-magnet-email" name="email" required
               placeholder="{{ 'Tu email profesional'|t }}"
               class="lead-magnet__input" data-lead-magnet-email>
      </div>
      <div class="lead-magnet__field">
        <label for="lead-magnet-avatar" class="visually-hidden">{{ '¿Qué te describe mejor?'|t }}</label>
        <select id="lead-magnet-avatar" name="avatar_type" required
                class="lead-magnet__select" data-lead-magnet-avatar>
          <option value="" disabled selected>{{ '¿Qué te describe mejor?'|t }}</option>
          <option value="job_seeker">{{ 'Busco empleo o quiero reciclarme'|t }}</option>
          <option value="entrepreneur">{{ 'Quiero emprender o tengo una idea'|t }}</option>
          <option value="business_owner">{{ 'Tengo un negocio y quiero digitalizarlo'|t }}</option>
        </select>
      </div>
      <div class="lead-magnet__consent">
        <label>
          <input type="checkbox" name="gdpr_consent" required data-lead-magnet-gdpr>
          {{ 'Acepto la <a href="/politica-de-privacidad">política de privacidad</a>'|t }}
        </label>
      </div>
      <button type="submit" class="lead-magnet__btn btn--primary" data-lead-magnet-submit>
        {{ lead_magnet_cta_text|default('Descargar Kit →'|t) }}
      </button>
    </form>

    <div class="lead-magnet__success" data-lead-magnet-success hidden>
      {{ jaraba_icon('ui', 'check-circle', { variant: 'duotone', color: 'verde-crecimiento' }) }}
      <p>{{ lead_magnet_success_message|default('¡Kit enviado a tu email! Revisa tu bandeja.'|t) }}</p>
    </div>

    <ul class="lead-magnet__checklist" aria-label="{{ 'Contenido del Kit'|t }}">
      <li>{{ jaraba_icon('ui', 'check', { color: 'verde-crecimiento', size: 'sm' }) }} {{ '7 herramientas gratuitas probadas'|t }}</li>
      <li>{{ jaraba_icon('ui', 'check', { color: 'verde-crecimiento', size: 'sm' }) }} {{ 'Hoja de ruta de 7 días'|t }}</li>
      <li>{{ jaraba_icon('ui', 'check', { color: 'verde-crecimiento', size: 'sm' }) }} {{ 'Sin tecnicismos, sin humo'|t }}</li>
    </ul>
  </div>
</section>
```

#### [NEW] `js/lead-magnet.js`

```javascript
/**
 * @file lead-magnet.js
 * Drupal.behaviors para el formulario de Lead Magnet.
 * Usa fetch() con CSRF token para POST a la API de EmailSubscriber.
 */
(function (Drupal, once) {
  'use strict';

  let csrfToken = null;

  Drupal.behaviors.jarabaLeadMagnet = {
    attach(context) {
      once('lead-magnet', '[data-lead-magnet-form]', context).forEach((form) => {
        form.addEventListener('submit', async (e) => {
          e.preventDefault();
          const btn = form.querySelector('[data-lead-magnet-submit]');
          const email = form.querySelector('[data-lead-magnet-email]').value;
          const avatar = form.querySelector('[data-lead-magnet-avatar]').value;
          const gdpr = form.querySelector('[data-lead-magnet-gdpr]').checked;

          if (!email || !avatar || !gdpr) return;

          btn.disabled = true;
          btn.textContent = Drupal.t('Enviando…');

          try {
            if (!csrfToken) {
              const tokenRes = await fetch('/session/token');
              csrfToken = await tokenRes.text();
            }

            const res = await fetch('/api/v1/email/subscribers/subscribe', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
              },
              body: JSON.stringify({
                email: email,
                source: 'kit_impulso_digital',
                tags: [avatar],
              }),
            });

            if (res.ok) {
              form.hidden = true;
              form.closest('.lead-magnet')
                .querySelector('[data-lead-magnet-success]').hidden = false;
            } else {
              btn.textContent = Drupal.t('Error. Inténtalo de nuevo.');
              btn.disabled = false;
            }
          } catch (err) {
            btn.textContent = Drupal.t('Error de conexión.');
            btn.disabled = false;
          }
        });
      });
    },
  };
})(Drupal, once);
```

#### [NEW] `scss/components/_lead-magnet.scss`

```scss
// @file _lead-magnet.scss
// Estilos del formulario Lead Magnet — Kit de Impulso Digital
// Cumple: SSOT Design Tokens, Dart Sass, var(--ej-*), mobile-first

@use 'sass:color';

.lead-magnet {
  background: var(--ej-bg-glass, rgba(255, 255, 255, 0.08));
  backdrop-filter: blur(20px);
  border: 1px solid var(--ej-border-glass, rgba(255, 255, 255, 0.15));
  border-radius: var(--ej-radius-xl, 1.5rem);
  padding: var(--ej-space-xl, 3rem) var(--ej-space-lg, 2rem);
  text-align: center;
  max-width: 600px;
  margin: var(--ej-space-xxl, 5rem) auto;

  &__title {
    font-family: var(--ej-font-heading, 'Outfit', sans-serif);
    font-size: var(--ej-text-2xl, 2rem);
    color: var(--ej-color-heading, #1a1a2e);
    margin-bottom: var(--ej-space-sm, 0.75rem);
  }

  &__subtitle {
    color: var(--ej-color-muted, #6b7280);
    margin-bottom: var(--ej-space-lg, 2rem);
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: var(--ej-space-md, 1rem);
  }

  &__input,
  &__select {
    padding: var(--ej-space-sm, 0.75rem) var(--ej-space-md, 1rem);
    border: 1px solid var(--ej-border-input, #d1d5db);
    border-radius: var(--ej-radius-md, 0.5rem);
    font-size: var(--ej-text-base, 1rem);
    transition: border-color 0.2s ease;

    &:focus {
      outline: none;
      border-color: var(--ej-naranja-impulso, #ff6b35);
      box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
    }
  }

  &__btn {
    background: var(--ej-naranja-impulso, #ff6b35);
    color: #fff;
    padding: var(--ej-space-sm, 0.75rem) var(--ej-space-lg, 2rem);
    border: none;
    border-radius: var(--ej-radius-md, 0.5rem);
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease;

    &:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(255, 107, 53, 0.35);
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }
  }

  &__success {
    padding: var(--ej-space-lg, 2rem);
    color: var(--ej-verde-crecimiento, #10b981);
    font-weight: 600;
  }

  &__checklist {
    list-style: none;
    padding: 0;
    margin-top: var(--ej-space-lg, 2rem);
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: var(--ej-space-xs, 0.5rem);
    color: var(--ej-color-muted, #6b7280);
    font-size: var(--ej-text-sm, 0.875rem);
  }
}
```

#### [MODIFY] `page--front.html.twig`

Añadir después de la sección de stats:
```twig
{% include '@ecosistema_jaraba_theme/partials/_lead-magnet.html.twig' with {
  lead_magnet_title: theme_settings.lead_magnet_title,
  lead_magnet_subtitle: theme_settings.lead_magnet_subtitle,
  lead_magnet_cta_text: theme_settings.lead_magnet_cta_text,
  lead_magnet_success_message: theme_settings.lead_magnet_success_message,
} %}
```

#### [MODIFY] `ecosistema_jaraba_theme.libraries.yml`

```yaml
lead-magnet:
  js:
    js/lead-magnet.js: {}
  dependencies:
    - core/drupal
    - core/once
```

#### [MODIFY] `ecosistema_jaraba_theme.theme`

Añadir theme_settings para lead_magnet_title, lead_magnet_subtitle, lead_magnet_cta_text, lead_magnet_success_message en `ecosistema_jaraba_theme_form_system_theme_settings_alter()`.

### 4.3 Verificación API

Verificar que la ruta `/api/v1/email/subscribers/subscribe` acepta POST público. Si requiere auth, crear ruta pública en `jaraba_email.routing.yml` con `_access: 'TRUE'` y validación CSRF.

---

## 5. Fase 2 — Casos de Éxito: Migración desde jarabaimpact.com

> **Prioridad:** P0 CRITICAL · **BUG-002** · **Esfuerzo:** ~6h

### 5.1 Estrategia

Usar el módulo `jaraba_blog` existente con `BlogPost` entity. Crear una `BlogCategory` "Casos de Éxito" y 3 posts con el contenido real extraído de jarabaimpact.com.

### 5.2 Contenido de los 3 Casos

| # | Protagonista | Título | Vertical | Programa | Video |
|---|-------------|--------|----------|----------|-------|
| 1 | Marcela Calabia | Cómo Marcela se Reinventó y Lanzó su Proyecto como Autónoma | Emprendimiento | Andalucía +ei | ✅ YouTube |
| 2 | Ángel Martínez | Del Estrés Corporativo al Éxito Rural: Camino Viejo | Emprendimiento | Andalucía +ei | ✅ YouTube `gk8MGO8ldLE` |
| 3 | Luis Miguel Criado | De la Parálisis Administrativa a la Acción | Emprendimiento | Andalucía +ei | ❌ |

Cada caso sigue la estructura: **Cita Hero → Desafío → Descubrimiento → Solución → Resultados → CTA**.

### 5.3 Implementación

Los posts se crean vía admin en `/admin/content/blog-posts/add` o mediante Drush script dentro del contenedor Docker:

```bash
lando drush eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('blog_post');
  // ... crear 3 BlogPost entities con contenido real
"
```

### 5.4 Template Twig para blog_detail

Verificar que `blog_detail.html.twig` soporta blockquotes estilizados, embeds de video YouTube, y columnas de imagen+texto. Si no, crear un partial `_case-study-layout.html.twig`.

---

## 6. Fase 3 — Blog: 3 Artículos Reales

> **Prioridad:** P0 CRITICAL · **BUG-003** · **Esfuerzo:** ~8h

### 6.1 Los 3 Artículos

| # | Título | Slug | Palabras | Keyword SEO |
|---|--------|------|----------|------------|
| 1 | Caso Camino Viejo: Del Estrés Corporativo al Éxito Rural | `/blog/caso-exito-camino-viejo` | ~1.500 | caso éxito emprendimiento rural |
| 2 | Las 7 Herramientas Gratuitas que Uso con Mis Clientes | `/blog/7-herramientas-gratuitas-transformacion-digital` | ~2.500 | herramientas gratuitas transformación digital pymes |
| 3 | Cómo Optimizar tu LinkedIn para Encontrar Trabajo en 2026 | `/blog/optimizar-linkedin-encontrar-trabajo-2026` | ~1.800 | optimizar linkedin empleo 2026 |

### 6.2 Estructura de cada artículo

Cada `BlogPost` incluye: `title`, `slug`, `excerpt` (150 chars), `body` (HTML formateado), `status: published`, `featured: true`, `reading_time` calculado, `tags`, `meta_title`, `meta_description`.

El contenido se redacta como experto, con tono "Sin Humo" (directo, práctico, sin tecnicismos innecesarios), CTAs internos al Kit de Impulso Digital, y referencias cruzadas a los Casos de Éxito.

---

## 7. Fase 4 — Pricing Dinámico desde ConfigEntities

> **Prioridad:** P1 HIGH · **BUG-005** · **Esfuerzo:** ~12h

### 7.1 Arquitectura

```
SaasPlanTier (ConfigEntity)     SaasPlanFeatures (ConfigEntity)
├── starter                     ├── _default_starter
├── professional                ├── _default_professional
└── enterprise                  └── _default_enterprise
         │                                │
         ▼                                ▼
PlanResolverService::getPlanCapabilities(vertical, tier)
         │
         ▼
PricingController (nuevo)
         │
         ▼
page--pricing.html.twig (Zero Region Policy)
   └── {% include '_pricing-table.html.twig' %}
```

### 7.2 Archivos a crear

#### [NEW] Controller: `PricingPageController.php`

En `ecosistema_jaraba_core` o `jaraba_page_builder`. Inyecta `PlanResolverService`, carga todos los `SaasPlanTier` ordenados por `weight`, obtiene `getPlanCapabilities()` para cada tier, y pasa al template como render array `#theme => 'pricing_page'`.

#### [NEW] Ruta en routing.yml

```yaml
jaraba.pricing_page:
  path: '/planes'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\PricingPageController::page'
    _title: 'Planes y Precios'
  requirements:
    _access: 'TRUE'
```

#### [NEW] `page--pricing.html.twig`

Template Zero Region: `{{ clean_content }}` con includes de `_header.html.twig` y `_footer.html.twig`.

#### [NEW] `templates/partials/_pricing-table.html.twig`

Grid de cards (1 por tier). Datos dinámicos del controller. Card recomendada con clase `.pricing-card--featured`. Textos `{% trans %}`.

#### [NEW] `scss/components/_pricing.scss`

Glassmorphism cards, gradiente en card featured, `var(--ej-*)`, mobile-first.

#### [MODIFY] `hook_preprocess_html()` y `hook_theme_suggestions_page_alter()`

Body class `page--pricing`, template suggestion.

---

## 8. Fase 5 — Formulario de Contacto

> **Prioridad:** P1 HIGH · **BUG-004** · **Esfuerzo:** ~6h

Formulario con campos: Nombre, Email, Teléfono (optional), ¿Qué te describe mejor? (select), Mensaje, GDPR. Layout 2 columnas en desktop: formulario izq | info contacto + Calendly + WhatsApp + Maps der.

---

## 9. Fase 6 — Mejoras de Conversión

> **Prioridad:** P1 · **BUG-007, 008, 009, 014** · **Esfuerzo:** ~6h

- **Hero copy mejorado** (BUG-008): Headline con credencial +100M€, CTA secundario al Método
- **Testimonios en homepage** (BUG-007): Sección "Historias de Transformación" con 3 cards
- **WhatsApp sticky mobile** (BUG-014): Partial `_whatsapp-fab.html.twig` condicionado a mobile
- **Botón Ecosistema** (BUG-009): Cambiar texto y añadir tooltip

---

## 10. Fase 7 — SEO: Schema JSON-LD y Meta Tags

> **Prioridad:** P2 · **BUG-010, 011** · **Esfuerzo:** ~4h

3 schemas (Person, Organization, LocalBusiness) en `hook_preprocess_html()`. Meta tags y OG tags por página desde `HomepageDataService` o theme settings.

---

## 11. Fase 8 — Cross-Pollination entre Dominios

> **Prioridad:** P2 · **BUG-012** · **Esfuerzo:** ~3h

Links bidireccionales con descripciones contextuales en footer. Design tokens compartidos para coherencia visual.

---

## 12. Fase 9 — Secuencia Email Post-Descarga Kit

> **Prioridad:** P1 · **BUG-006** · **Esfuerzo:** ~12h

7 emails en 14 días, segmentados por avatar_type. Implementar con `jaraba_email` module + ECA triggers. Templates de email con Design Tokens. Ver Doc 178 §7 para contenido completo de cada email.

---

## 13. Infraestructura Existente Confirmada

### 13.1 Módulo `jaraba_email`

- **Entidad:** `EmailSubscriber` (email, first_name, last_name, status, engagement_score, opens_count, clicks_count, tenant_id)
- **API:** `/api/v1/email/subscribers/subscribe` (POST)
- **Routing:** CRUD completo en `/admin/jaraba/email/subscribers`
- **Campos de estado:** pending → subscribed → unsubscribed → bounced → complained

### 13.2 Módulo `jaraba_blog`

- **Entidad:** `BlogPost` (title, slug, excerpt, body, status, featured, reading_time, tags, tenant_id, category_id, author_id, views_count, meta SEO)
- **Controller:** `BlogFrontendController` → `/blog`, `/blog/{slug}`, `/blog/categoria/{slug}`, `/blog/autor/{slug}`
- **Service:** `BlogService` (listPosts, getPostBySlug, getPopularPosts, getRelatedPosts, getAdjacentPosts, trackView)
- **SEO:** `BlogSeoService` (generateListingSeo, generatePostSeo con JSON-LD)

### 13.3 Módulo `ecosistema_jaraba_core` — Planes

- **ConfigEntity:** `SaasPlanTier` (id, label, tier_key, aliases, stripe_price_monthly, stripe_price_yearly, description, weight)
- **ConfigEntity:** `SaasPlanFeatures` (features array + limits map, cascade: `{vertical}_{tier}` → `_default_{tier}` → NULL)
- **Service:** `PlanResolverService` (normalize, getFeatures, checkLimit, hasFeature, getPlanCapabilities, resolveFromStripePriceId)

---

## 14. Roadmap de Sprints

| Sprint | Fases | Esfuerzo | Plazo |
|--------|-------|----------|-------|
| **S1: Emergency (48h)** | F1 Lead Magnet + F2 Casos + F3 Blog | 22h | 2 días |
| **S2: Core Funnel (1 sem)** | F4 Pricing + F5 Contacto + F6 Conversión | 24h | 5 días |
| **S3: Growth (1 sem)** | F7 SEO + F8 Cross-pollination + F9 Email sequence | 19h | 5 días |

**Total estimado: ~65h**

---

## 15. Verificación y Criterios de Aceptación

### 15.1 Comandos de verificación

Todos ejecutados dentro del contenedor Docker:

```bash
# Compilar SCSS
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/main.css --style=compressed"

# Cache clear
lando drush cr

# Verificar entidades
lando drush entity:updates

# Export config
lando drush config:export -y
```

### 15.2 Criterios por fase

| Fase | Criterio | Umbral |
|------|---------|--------|
| F1 | Email capturado + éxito mostrado | 100% submits |
| F2 | 3 casos visibles con foto y cita | 0 "No post found" |
| F3 | 3 posts >1.000 palabras con SEO | Sin imágenes stock |
| F4 | Tiers dinámicos desde ConfigEntities | Datos de `/admin/config/jaraba/plan-tiers` |
| F5 | Formulario submit + autorespuesta | 100% entrega |
| F7 | Schemas válidos en Rich Results Test | 0 errores |

### 15.3 Verificación en navegador

Cada fase se verifica en `https://jaraba-saas.lndo.site/es` con el navegador, comprobando:
- Responsive (mobile-first)
- Textos traducibles (cambiar idioma)
- Design tokens aplicados
- Sin errores JS en console
- Accesibilidad básica (ARIA, contraste)

---

*Fin del documento — Plan de Implementación v1*
