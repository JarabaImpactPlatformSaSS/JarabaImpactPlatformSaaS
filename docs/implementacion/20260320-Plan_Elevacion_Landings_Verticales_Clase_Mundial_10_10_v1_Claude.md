# Plan de Elevación: Landings Verticales → Clase Mundial 10/10

**Fecha:** 2026-03-20
**Autor:** Claude Opus 4.6 (arquitecto SaaS + UX + Drupal + theming + SEO)
**Estado:** PENDIENTE
**Prioridad:** P0 — Impacto directo en conversión
**Referencia:** LANDING-ELEVATION-002 (evolución de LANDING-ELEVATION-001)
**Versión docs:** v155 DIRECTRICES + v142 ARQUITECTURA + v183 INDICE + v107 FLUJO

---

## 📑 Tabla de Contenidos (TOC)

1. [Diagnóstico: Estado Actual vs. Clase Mundial](#1-diagnóstico)
2. [Bugs Críticos Detectados](#2-bugs-críticos)
3. [Arquitectura Actual del Sistema de Landing](#3-arquitectura-actual)
4. [Plan de Implementación por Fases](#4-fases)
   - 4.1 [FASE 0: Hotfixes Críticos (bugs renderizado)](#41-fase-0)
   - 4.2 [FASE 1: Sticky CTA + Trust Badges + Urgencia](#42-fase-1)
   - 4.3 [FASE 2: Pricing Tiers Inline + Comparativa Activa](#43-fase-2)
   - 4.4 [FASE 3: Social Proof Denso + Partner Logos](#44-fase-3)
   - 4.5 [FASE 4: Hero Split + Animaciones + Mobile CTA](#45-fase-4)
   - 4.6 [FASE 5: Video Hero + Demos Interactivas](#46-fase-5)
5. [Tabla de Correspondencia Técnica](#5-tabla-correspondencia)
6. [Directrices de Cumplimiento](#6-directrices)
7. [Validadores y Salvaguardas](#7-validadores)
8. [Definición de "Clase Mundial 10/10"](#8-definición-10-10)

---

## 1. Diagnóstico: Estado Actual vs. Clase Mundial {#1-diagnóstico}

### Auditoría Rendida (curl real sobre AgroConecta, Emprendimiento, Empleabilidad)

| Métrica | Valor actual | Clase mundial (Stripe/Linear) | Gap |
|---------|-------------|-------------------------------|-----|
| Secciones renderizadas | 9 (pero comparativa 0) | 10-12 | -1 sección invisible |
| Sticky CTAs | 0 | 2 (header + footer) | **CRÍTICO** |
| Vídeos/demos | 0 | 1-3 | **ALTO** |
| Trust badges | 0 | 4-6 | **ALTO** |
| Partner logos | 0 | 10-20 | **ALTO** |
| Pricing tiers inline | 1 card genérica | 3-4 cards con CTA | **ALTO** |
| Animaciones reveal | 2 | 8-12 | **MEDIO** |
| Pain point titles | VACÍOS (bug) | Siempre visibles | **BUG** |
| Urgencia visible | 0 | "14 días gratis" badge | **MEDIO** |
| Formulario en hero | No (modal) | Email-only inline | **MEDIO** |
| CTA tracked | 8 | 12-15 | **MEDIO** |
| Schema.org | FAQ + Review | + SoftwareApplication | **BAJO** |

### Score de Conversión

```
Estado actual:  ████████░░░░░░░░░░░░ 6/10
Objetivo:       ████████████████████ 10/10
```

### Benchmark: ¿Qué tiene Stripe que nosotros no?

1. **Sticky nav con CTA** siempre visible al hacer scroll
2. **3+ cards de pricing** comparativas con "Most popular" badge
3. **Logos de 50+ clientes** (Amazon, Google, Shopify...)
4. **Video hero** autoplaying silencioso
5. **"Start free"** con campo email directo en hero
6. **Trust signals**: SOC 2, PCI DSS, "99.999% uptime"
7. **Animaciones on-scroll** suaves en cada sección
8. **Comparativa** detallada vs. competidores
9. **Números específicos**: "$817B processed in 2023"
10. **Mobile-first** con bottom sticky CTA

---

## 2. Bugs Críticos Detectados {#2-bugs-críticos}

### BUG-001: Pain Points con `<h3>` Vacíos

**Severidad:** CRÍTICA
**Impacto:** Todas las landings. Los 4 pain point cards muestran solo descripción, sin título.
**Causa raíz:** El controller pasa `pain_points` como array de `{icon, text}` pero NO incluye key `title`. El template `_landing-pain-points.html.twig` renderiza `{{ item.title }}` que es `NULL` → `<h3></h3>` vacío.

**Evidencia HTML renderizada:**
```html
<h3 class="landing-pain-point__title"></h3>
<p class="landing-pain-point__description">Los intermediarios se quedan con el 40%...</p>
```

**Fix:** Añadir `'title'` key en el array del controller O adaptar template para usar `text` como título si no hay `title`.

### BUG-002: Comparativa No Se Renderiza

**Severidad:** ALTA
**Impacto:** 0 instancias de `landing-comparison` en DOM.
**Causa raíz:** El controller NO pasa `comparison` data para AgroConecta, Emprendimiento ni Empleabilidad. Solo JarabaLex tiene comparativa. El template condicional `{% if comparison %}` descarta la sección.

**Fix:** Añadir data de `comparison` para TODOS los verticales comerciales:
- AgroConecta: vs. Intermediarios vs. Marketplace genérico
- Emprendimiento: vs. Incubadora clásica vs. Consultora
- Empleabilidad: vs. InfoJobs vs. Headhunter
- ComercioConecta: vs. Shopify vs. WooCommerce
- ServiciosConecta: vs. Doctoralia vs. Gestión manual
- Formación: vs. Moodle vs. Teachable
- Content Hub: vs. WordPress vs. HubSpot CMS

### BUG-003: CSS Duplicado en `<head>`

**Severidad:** BAJA
**Impacto:** `ecosistema-jaraba-theme.css` cargado 2 veces.
**Causa raíz:** Library `landing-sections` referencia el CSS principal del tema. Además se carga por `global-styling` dependency.

---

## 3. Arquitectura Actual del Sistema de Landing {#3-arquitectura-actual}

### Pipeline Completo

```
VerticalLandingController::{vertical}()
  │
  ├─ Prepara array con 9 secciones (hero, pain_points, steps, features,
  │  social_proof, lead_magnet, pricing, faq, final_cta)
  │
  ├─ buildLanding($data) → enriquece con MetaSitePricingService
  │
  └─ Return render array:
       '#theme' => 'vertical_landing_content'
       '#vertical_data' => $data
       '#attached' => [libraries]
       '#cache' => [tags, max-age]
           │
           ▼
     vertical-landing-content.html.twig (orchestrator)
       │
       ├─ {% include '_landing-hero.html.twig' with {...} only %}
       ├─ {% include '_landing-pain-points.html.twig' with {...} only %}
       ├─ {% include '_landing-solution-steps.html.twig' with {...} only %}
       ├─ {% include '_landing-features-grid.html.twig' with {...} only %}
       ├─ {% include '_landing-comparison.html.twig' with {...} only %}  ← NO DATA
       ├─ {% include '_landing-social-proof.html.twig' with {...} only %}
       ├─ {% include '_landing-lead-magnet.html.twig' with {...} only %}
       ├─ {% include '_landing-pricing-preview.html.twig' with {...} only %}
       ├─ {% include '_landing-faq.html.twig' with {...} only %}
       └─ {% include '_landing-final-cta.html.twig' with {...} only %}
           │
           ▼
     page--vertical-landing.html.twig (zero-region wrapper)
       │
       ├─ _header.html.twig (classic/minimal/transparent)
       ├─ {{ clean_content }}
       ├─ _footer.html.twig
       └─ _copilot-fab.html.twig
```

### Archivos Involucrados

| Archivo | Líneas | Rol |
|---------|--------|-----|
| `VerticalLandingController.php` | 1.231 | Datos de 9 verticales + buildLanding() |
| `vertical-landing-content.html.twig` | 103 | Orchestrator de 10 parciales |
| `_landing-hero.html.twig` | 93 | Hero split con imagen opcional |
| `_landing-pain-points.html.twig` | 46 | 4 dolor cards |
| `_landing-solution-steps.html.twig` | 46 | 3 pasos |
| `_landing-features-grid.html.twig` | 42 | Grid 12-16 features |
| `_landing-comparison.html.twig` | 59 | Tabla comparativa 3 columnas |
| `_landing-social-proof.html.twig` | 140 | Testimonials + métricas + case study |
| `_landing-lead-magnet.html.twig` | 138 | Email capture + slide-panel |
| `_landing-pricing-preview.html.twig` | 69 | Preview pricing + CTA |
| `_landing-faq.html.twig` | 61 | FAQ con Schema.org |
| `_landing-final-cta.html.twig` | 51 | CTA bottom |
| `_vertical-landing.scss` | 1.310 | Estilos completos |
| `page--vertical-landing.html.twig` | 89 | Page wrapper zero-region |

---

## 4. Plan de Implementación por Fases {#4-fases}

### 4.1 FASE 0: Hotfixes Críticos {#41-fase-0}

**Objetivo:** Corregir bugs que rompen la experiencia actual.
**Esfuerzo:** 1-2 horas
**Impacto:** Corrige 3 bugs que afectan a TODAS las landings

#### F0-01: Fix Pain Point Titles Vacíos

**Archivos a modificar:**
- `VerticalLandingController.php` — Añadir `'title'` key a cada pain_point en los 9 verticales

**Lógica:** Actualmente el controller pasa:
```php
'pain_points' => [
  ['icon' => [...], 'text' => 'Los intermediarios se quedan con el 40%'],
]
```

Debe pasar:
```php
'pain_points' => [
  'title' => $this->t('¿Te suena esto?'),
  'items' => [
    ['icon' => [...], 'title' => $this->t('Márgenes comprimidos'), 'text' => $this->t('Los intermediarios se quedan con el 40%...')],
  ],
]
```

**Template:** `_landing-pain-points.html.twig` ya soporta `section.items` con fallback.

#### F0-02: Fix Comparativa No Renderizada

**Archivos a modificar:**
- `VerticalLandingController.php` — Añadir `comparison` data para los 9 verticales

**Lógica:** Cada vertical necesita 3 columnas:
- Columna 1: Jaraba (highlight=TRUE, features all TRUE)
- Columna 2: Competidor principal (mixed features)
- Columna 3: Alternativa manual (mostly FALSE)

**Ejemplo AgroConecta:**
```php
'comparison' => [
  'headline' => $this->t('¿Cómo se compara AgroConecta?'),
  'competitors' => [
    [
      'name' => 'AgroConecta',
      'highlight' => TRUE,
      'price' => $this->t('Desde 0 €/mes'),
      'features' => [
        ['text' => $this->t('Tienda propia del productor'), 'included' => TRUE],
        ['text' => $this->t('Trazabilidad QR'), 'included' => TRUE],
        ['text' => $this->t('Copilot IA'), 'included' => TRUE],
        ['text' => $this->t('Envío integrado MRW/SEUR'), 'included' => TRUE],
        ['text' => $this->t('Cobro directo via Stripe'), 'included' => TRUE],
        ['text' => $this->t('Analytics en tiempo real'), 'included' => TRUE],
      ],
    ],
    [
      'name' => $this->t('Marketplace genérico'),
      'highlight' => FALSE,
      'price' => $this->t('15-40% comisión'),
      'features' => [
        ['text' => $this->t('Tienda dentro del marketplace'), 'included' => TRUE],
        ['text' => $this->t('Sin trazabilidad propia'), 'included' => FALSE],
        ['text' => $this->t('Sin asistente IA'), 'included' => FALSE],
        ['text' => $this->t('Envío del marketplace'), 'included' => TRUE],
        ['text' => $this->t('Cobro via marketplace (30-60 días)'), 'included' => TRUE],
        ['text' => $this->t('Analytics limitados'), 'included' => FALSE],
      ],
    ],
    [
      'name' => $this->t('Venta tradicional'),
      'highlight' => FALSE,
      'price' => $this->t('Gratis (sin escala)'),
      'features' => [
        ['text' => $this->t('Boca a boca local'), 'included' => TRUE],
        ['text' => $this->t('Sin trazabilidad'), 'included' => FALSE],
        ['text' => $this->t('Sin tecnología'), 'included' => FALSE],
        ['text' => $this->t('Envío manual'), 'included' => FALSE],
        ['text' => $this->t('Cobro en efectivo/transferencia'), 'included' => TRUE],
        ['text' => $this->t('Sin datos de negocio'), 'included' => FALSE],
      ],
    ],
  ],
],
```

#### F0-03: Fix Library CSS Duplicada

**Archivo:** `ecosistema_jaraba_theme.libraries.yml`
**Fix:** Cambiar `landing-sections` CSS de `ecosistema-jaraba-theme.css` a `css/routes/landing.css` (que ya se carga por separado).

---

### 4.2 FASE 1: Sticky CTA + Trust Badges + Urgencia {#42-fase-1}

**Objetivo:** Los 3 componentes más impactantes en conversión.
**Esfuerzo:** 4-6 horas
**Impacto estimado:** +30-50% conversión (benchmark Stripe, Linear)

#### F1-01: Sticky Footer CTA Bar

**Nuevo parcial:** `_landing-sticky-cta.html.twig`

**Lógica:**
- Barra fija `position: sticky; bottom: 0` visible tras scroll 30vh
- Contenido: texto urgencia + CTA primario + CTA secundario
- Se oculta cuando el hero o el final-cta están visibles (IntersectionObserver)
- Mobile: full-width, z-index alto

**Estructura HTML:**
```twig
<div class="landing-sticky-cta" id="sticky-cta" aria-hidden="true">
  <div class="landing-sticky-cta__container">
    <span class="landing-sticky-cta__text">
      {% trans %}14 días gratis — Sin tarjeta de crédito{% endtrans %}
    </span>
    <a href="{{ register_url }}" class="btn btn--primary btn--sm"
       data-track-cta="sticky_{{ vertical_key }}"
       data-track-position="sticky_footer">
      {{ cta_text }}
    </a>
  </div>
</div>
```

**SCSS:** Añadir a `_vertical-landing.scss`:
```scss
.landing-sticky-cta {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 90;
  background: var(--ej-bg-card, #fff);
  border-top: 1px solid var(--ej-border-color, rgba(0,0,0,0.08));
  box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
  padding: 0.75rem 1rem;
  transform: translateY(100%);
  transition: transform 0.3s ease;

  &.is-visible { transform: translateY(0); }
}
```

**JS:** Comportamiento Drupal con IntersectionObserver:
- Observer en hero y final-cta → si AMBOS fuera de viewport → `is-visible`
- OBSERVER-SCROLL-ROOT-001: NO usar scroll-root custom, usar viewport default

#### F1-02: Trust Badges Strip

**Nuevo parcial:** `_landing-trust-badges.html.twig`

**Lógica:** Strip horizontal de badges debajo del hero:
- "14 días gratis" (clock icon)
- "Sin tarjeta de crédito" (credit-card-off icon)
- "Datos en la UE" (shield icon)
- "RGPD 100%" (shield-privacy icon)
- "Soporte en español" (chat icon)

**Integración:** Incluir entre hero y pain-points en `vertical-landing-content.html.twig`

**SCSS:**
```scss
.landing-trust-badges {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 1.5rem;
  padding: 1.5rem 0;
  border-bottom: 1px solid var(--ej-border-color, rgba(0,0,0,0.06));

  &__badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--ej-text-muted, #64748B);
  }
}
```

#### F1-03: Urgencia Badge en Hero

**Modificación:** `_landing-hero.html.twig`

**Lógica:** Añadir badge `<span class="landing-hero__urgency">` encima del título:
```twig
<span class="landing-hero__urgency">
  {{ jaraba_icon('ui', 'clock', { variant: 'duotone', size: '16px', color: 'verde-innovacion' }) }}
  {% trans %}14 días gratis — Sin tarjeta de crédito{% endtrans %}
</span>
```

**SCSS:**
```scss
.landing-hero__urgency {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.4rem 1rem;
  border-radius: 999px;
  background: color-mix(in srgb, var(--ej-color-success, #10b981) 12%, transparent);
  color: var(--ej-color-success, #10b981);
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 1rem;
}
```

---

### 4.3 FASE 2: Pricing Tiers Inline + Comparativa Activa {#43-fase-2}

**Objetivo:** Mostrar 3-4 planes con CTA por tier (no 1 card genérica).
**Esfuerzo:** 6-8 horas
**Impacto estimado:** +20-30% conversión en sección pricing

#### F2-01: Pricing Cards Inline (3-4 tiers)

**Refactorización:** `_landing-pricing-preview.html.twig` → nuevo layout multi-card

**Lógica:**
- Controller pasa `pricing.tiers` array con 3-4 planes
- Cada tier: name, price, period, features[], cta_text, cta_url, recommended (bool)
- El tier `recommended` recibe badge "Más popular" y estilo destacado

**Datos desde controller (NO hardcoded en template):**
```php
'pricing' => [
  'title' => $this->t('Planes para productores'),
  'tiers' => [
    [
      'name' => 'Free',
      'price' => '0',
      'period' => $this->t('mes'),
      'features' => [
        $this->t('5 productos'),
        $this->t('Copilot IA básico'),
        $this->t('Cobro via Stripe'),
      ],
      'cta_text' => $this->t('Empezar gratis'),
      'cta_url' => Url::fromRoute('user.register')->toString(),
      'recommended' => FALSE,
    ],
    [
      'name' => 'Starter',
      'price' => '29',
      'period' => $this->t('mes'),
      'features' => [
        $this->t('50 productos'),
        $this->t('Envío MRW/SEUR'),
        $this->t('QR trazabilidad'),
        $this->t('Analytics básicos'),
      ],
      'cta_text' => $this->t('Probar 14 días gratis'),
      'cta_url' => '/planes/agroconecta',
      'recommended' => TRUE,
    ],
    [
      'name' => 'Profesional',
      'price' => '79',
      'period' => $this->t('mes'),
      'features' => [
        $this->t('Productos ilimitados'),
        $this->t('Copilot IA avanzado'),
        $this->t('Previsión demanda'),
        $this->t('Hub B2B'),
        $this->t('API completa'),
      ],
      'cta_text' => $this->t('Probar 14 días gratis'),
      'cta_url' => '/planes/agroconecta',
      'recommended' => FALSE,
    ],
  ],
],
```

**SCSS:** Grid de cards responsive:
```scss
.landing-pricing__tiers {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  max-width: 960px;
  margin: 0 auto;
}

.landing-pricing__tier {
  background: var(--ej-bg-card, #fff);
  border-radius: var(--ej-border-radius, 12px);
  padding: 2rem;
  border: 1px solid var(--ej-border-color, rgba(0,0,0,0.08));

  &--recommended {
    border-color: var(--ej-vertical-color, var(--ej-color-primary));
    box-shadow: 0 8px 30px color-mix(in srgb, var(--ej-vertical-color) 15%, transparent);
    position: relative;
  }
}
```

**NO-HARDCODE-PRICE-001:** Los precios vienen del controller que consulta `MetaSitePricingService`. El template solo renderiza `{{ tier.price }}`.

#### F2-02: Comparativa Activa para 9 Verticales

**Archivo:** `VerticalLandingController.php` — Añadir `comparison` data
**Ver F0-02 para estructura de datos.**

Competidores por vertical:

| Vertical | Competidor 1 | Competidor 2 |
|----------|-------------|-------------|
| AgroConecta | Marketplace genérico (15-40% comisión) | Venta tradicional |
| ComercioConecta | Shopify (29-299 $/mes) | WooCommerce (hosting+plugins) |
| ServiciosConecta | Doctoralia (desde 99 €/mes) | Gestión manual |
| Empleabilidad | InfoJobs (desde 195 €/oferta) | LinkedIn Recruiter |
| Emprendimiento | Incubadora clásica (6-12 meses) | Consultora (150+ €/h) |
| JarabaLex | Aranzadi (~320 €/mes) | Manual + Excel |
| Formación | Teachable (39-199 $/mes) | Moodle (hosting+config) |
| Andalucía EI | Software genérico ERP | Excel + papel |
| Content Hub | HubSpot CMS (desde 25 $/mes) | WordPress (hosting+SEO tools) |

---

### 4.4 FASE 3: Social Proof Denso + Partner Logos {#44-fase-3}

**Objetivo:** Aumentar credibilidad y confianza.
**Esfuerzo:** 4-6 horas

#### F3-01: Partner Logo Strip

**Nuevo parcial:** `_landing-partner-logos.html.twig`

**Lógica:** Grid horizontal de logos de partners/tecnologías:
- Stripe (pagos), MRW (envío), SEUR (envío), Google Meet (videoconsultas)
- ENISA (financiación), Kit Digital (subvención), Junta de Andalucía
- Cada logo es SVG monocromo (gris) que se colorea al hover

**Integración:** Incluir entre social-proof y pricing.

**SCSS:**
```scss
.landing-partners {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 2rem;
  padding: 2rem 0;
  opacity: 0.6;

  &__logo {
    height: 28px;
    filter: grayscale(1);
    transition: filter 0.3s;
    &:hover { filter: grayscale(0); opacity: 1; }
  }
}
```

#### F3-02: Más Testimonials + Avatars

**Modificación:** `VerticalLandingController.php` — Subir de 2 a 4 testimonials por vertical.

**Nuevo campo:** `avatar_url` en cada testimonial (placeholder si no hay foto real).

**Template:** `_landing-social-proof.html.twig` — Añadir avatar circular antes de `<cite>`:
```twig
{% if testimonial.avatar_url %}
  <img src="{{ testimonial.avatar_url }}" alt="{{ testimonial.author }}"
       class="landing-testimonial__avatar" width="48" height="48" loading="lazy">
{% endif %}
```

#### F3-03: Metrics Prominentes

**Modificación:** Mover métricas ENCIMA de testimonials (actualmente debajo).
**Añadir animación:** Counter-up effect en números con IntersectionObserver.

---

### 4.5 FASE 4: Hero Split + Animaciones + Mobile CTA {#45-fase-4}

**Objetivo:** Impacto visual de primer segundo.
**Esfuerzo:** 6-8 horas

#### F4-01: Hero Split Layout con Imagen

**Estado:** El template ya soporta `hero.hero_image` (split layout implementado en cambios uncommitted). Falta:
1. Generar imágenes hero para cada vertical (6 WebP, 560×400)
2. Pasar `hero_image` + `hero_image_alt` desde controller
3. Asegurar responsive con `aspect-ratio: 7/5` en CSS

#### F4-02: Animaciones Reveal en Todas las Secciones

**Estado:** Solo 2 secciones tienen `reveal-element reveal-fade-up`.
**Fix:** Añadir a TODAS las secciones excepto hero (que es above-the-fold).

**Modificación:** `vertical-landing-content.html.twig` — pasar `reveal: true` a cada include.
**Template de cada sección:** Añadir `{{ reveal ? 'reveal-element reveal-fade-up' : '' }}` a `<section>`.

#### F4-03: Mobile Sticky Bottom CTA

**El sticky CTA de F1-01 ya cubre mobile.** Ajustes adicionales:
- Mobile: `padding: 0.5rem` (más compacto)
- Texto urgencia oculto en mobile (solo CTA)
- Touch target: mínimo 44×44px

#### F4-04: Hero Email Form Inline (Opcional)

**Alternativa al enlace de registro:** Campo email-only en hero.

**Lógica:**
```twig
<form class="landing-hero__form" action="{{ register_url }}" method="get">
  <input type="email" name="email" placeholder="{% trans %}Tu email{% endtrans %}"
         class="landing-hero__input" required>
  <button type="submit" class="btn btn--primary">
    {{ hero.cta.text }}
  </button>
</form>
```

**Decisión:** Este punto necesita alineación con el flujo de registro actual. Si el registro es Drupal standard form, el email pre-populated se pasa como query param.

---

### 4.6 FASE 5: Video Hero + Demos Interactivas {#46-fase-5}

**Objetivo:** Máximo impacto visual.
**Esfuerzo:** 8-12 horas (incluye generación de vídeos)
**Dependencia:** Vídeos generados con mcp__veo o similares

#### F5-01: Video Hero Autoplaying

**Lógica:**
- `<video autoplay muted loop playsinline>` de 10-15 segundos
- Fallback: imagen estática si `prefers-reduced-motion: reduce`
- Se pausa cuando no visible (IntersectionObserver)
- Mobile: poster image estática (ahorro datos)

**Campo nuevo en controller:** `hero.video_url` (MP4, WebM)

#### F5-02: Demo Interactiva (GIF animado o Lottie)

**Lógica:** En sección Features o como sección nueva `_landing-product-demo.html.twig`:
- 3-4 GIF/Lottie mostrando flujo: subir producto → QR → pago recibido
- Tab-switcher para navegar entre demos

---

## 5. Tabla de Correspondencia Técnica {#5-tabla-correspondencia}

| ID | Componente | Archivo(s) | Directiva | Prioridad |
|----|-----------|-----------|-----------|-----------|
| F0-01 | Fix pain point titles | `VerticalLandingController.php` | ENTITY-PREPROCESS-001 | P0 |
| F0-02 | Fix comparativa | `VerticalLandingController.php` | MARKETING-TRUTH-001 | P0 |
| F0-03 | Fix CSS duplicado | `libraries.yml` | SCSS-COMPILE-VERIFY-001 | P0 |
| F1-01 | Sticky CTA bar | `_landing-sticky-cta.html.twig`, `_vertical-landing.scss`, `landing-sticky.js` | FUNNEL-COMPLETENESS-001 | P0 |
| F1-02 | Trust badges | `_landing-trust-badges.html.twig`, `_vertical-landing.scss` | MARKETING-TRUTH-001 | P0 |
| F1-03 | Urgency badge | `_landing-hero.html.twig`, `_vertical-landing.scss` | i18n ({% trans %}) | P0 |
| F2-01 | Pricing tiers | `_landing-pricing-preview.html.twig`, `VerticalLandingController.php` | NO-HARDCODE-PRICE-001 | P1 |
| F2-02 | Comparativa 9 vert | `VerticalLandingController.php` | METRICS-HONESTY-001 | P1 |
| F3-01 | Partner logos | `_landing-partner-logos.html.twig`, `_vertical-landing.scss` | ICON-CONVENTION-001 | P1 |
| F3-02 | Más testimonials | `VerticalLandingController.php`, `_landing-social-proof.html.twig` | MARKETING-TRUTH-001 | P1 |
| F3-03 | Metrics counter | `_landing-social-proof.html.twig`, `landing-counters.js` | i18n, a11y | P1 |
| F4-01 | Hero images | `VerticalLandingController.php`, `_landing-hero.html.twig` | IMAGE-WEIGHT-001 | P2 |
| F4-02 | Reveal animations | `vertical-landing-content.html.twig` | CSS-ANIM-INLINE-001 | P2 |
| F4-03 | Mobile sticky | `_vertical-landing.scss` | Touch target 44px | P2 |
| F4-04 | Hero email form | `_landing-hero.html.twig` | CSRF-JS-CACHE-001 | P2 |
| F5-01 | Video hero | `_landing-hero.html.twig`, assets | prefers-reduced-motion | P2 |
| F5-02 | Demo interactiva | `_landing-product-demo.html.twig` | a11y | P2 |

---

## 6. Directrices de Cumplimiento {#6-directrices}

### Obligatorias en CADA Cambio

| Directriz | Regla | Aplicación |
|-----------|-------|-----------|
| CSS-VAR-ALL-COLORS-001 | CADA color = `var(--ej-*, fallback)` | Todo SCSS nuevo |
| ICON-CONVENTION-001 | `jaraba_icon('cat', 'name', { variant: 'duotone' })` | Nuevos iconos |
| NO-HARDCODE-PRICE-001 | Precios desde MetaSitePricingService | Pricing cards |
| i18n | `{% trans %}texto{% endtrans %}` (bloque, NO filtro) | Todo texto UI |
| TWIG-INCLUDE-ONLY-001 | `{% include 'partial' with {...} only %}` | Nuevos includes |
| SCSS-COMPILE-VERIFY-001 | `npm run build` + verificar timestamp | Tras cada edit SCSS |
| FUNNEL-COMPLETENESS-001 | `data-track-cta` + `data-track-position` | Cada nuevo CTA |
| ZERO-REGION-001 | `{{ clean_content }}`, sin `{{ page.content }}` | Page templates |
| OBSERVER-SCROLL-ROOT-001 | Viewport default para IntersectionObserver | Sticky CTA JS |
| MARKETING-TRUTH-001 | Claims verificables, 14d trial real | Trust badges, pricing |
| SCSS-001 | `@use '../variables' as *;` en cada parcial | Todo SCSS nuevo |
| Dart Sass moderno | `@use` (NO `@import`), `color-mix()` | Todo SCSS |
| ROUTE-LANGPREFIX-001 | `Url::fromRoute()` para URLs | CTAs nuevos |

### Pre-commit Checks Automáticos

Archivos `.html.twig` → `validate-twig-syntax.php` + `validate-twig-ortografia.php`
Archivos `.scss` → `validate-compiled-assets.php`
Archivos `.libraries.yml` → `validate-library-attachments.php`
Archivos `.routing.yml` → `validate-all.sh --fast`

---

## 7. Validadores y Salvaguardas {#7-validadores}

### Nuevos Validadores Propuestos

| Validador | ID | Checks |
|-----------|----|----|
| `validate-landing-sections-rendered.php` | LANDING-SECTIONS-RENDERED-001 | curl cada landing → grep 9+ `<section>` con clases `landing-*` |
| `validate-landing-pricing-tiers.php` | LANDING-PRICING-TIERS-001 | Verificar que pricing tiene 3+ tiers en controller |
| `validate-landing-comparison-data.php` | LANDING-COMPARISON-DATA-001 | Verificar que 9 verticales tienen `comparison` data |
| `validate-landing-sticky-cta.php` | LANDING-STICKY-CTA-001 | curl → grep `landing-sticky-cta` |
| `validate-landing-trust-badges.php` | LANDING-TRUST-BADGES-001 | curl → grep trust badges section |

### Safeguard Propuesto: LANDING-CONVERSION-SCORE-001

Meta-validador que calcula un "conversion score" por landing:
- +1 por cada sección renderizada (max 10)
- +1 por sticky CTA
- +1 por trust badges
- +1 por comparison table
- +1 por 3+ pricing tiers
- +1 por 3+ testimonials
- +1 por partner logos
- +1 por video/demo
- +1 por urgency badge
- +1 por tracked CTAs >= 10

**Score < 7:** FAIL
**Score 7-8:** WARN
**Score 9-10:** PASS

---

## 8. Definición de "Clase Mundial 10/10" {#8-definición-10-10}

Una landing de vertical alcanza 10/10 cuando:

1. **Hero impactante** con headline específica, subtítulo beneficio, 2 CTAs, badge urgencia, imagen/vídeo split
2. **Trust badges** visibles inmediatamente bajo hero (14d free, RGPD, sin tarjeta...)
3. **Pain points** con título + icono + descripción (NO vacíos)
4. **3 pasos** claros de cómo funciona
5. **12+ features** con iconos duotone de marca
6. **Comparativa** 3 columnas vs. competidores con checkmarks
7. **Social proof** denso: 4+ testimonials, 3+ metrics, partner logos, case study card
8. **Lead magnet** con email capture
9. **3-4 pricing tiers** inline con CTA por plan y "Más popular" badge
10. **FAQ** 10+ preguntas con Schema.org
11. **Final CTA** con urgencia
12. **Sticky CTA** visible en scroll (header o footer)
13. **Animaciones** reveal-fade-up en cada sección below-the-fold
14. **Tracking** completo: data-track-cta en CADA CTA, data-track-position en CADA sección
15. **Mobile-first**: sticky CTA bottom, touch targets 44px, layout responsive

**Verificación:** `php scripts/validation/validate-landing-conversion-score.php` → Score >= 9

---

## Apéndice A: Comandos de Verificación

```bash
# Rebuildar CSS tras cambios SCSS
cd web/themes/custom/ecosistema_jaraba_theme && npm run build && cd -

# Cache rebuild
lando drush cr

# Verificar HTTP 200 de todas las landings
for v in agroconecta comercioconecta serviciosconecta empleabilidad emprendimiento; do
  echo -n "$v: "; curl -sk -o /dev/null -w '%{http_code}' https://jaraba-saas.lndo.site/es/$v
  echo
done

# Verificar secciones renderizadas
curl -sk https://jaraba-saas.lndo.site/es/agroconecta | grep -c '<section'

# Verificar sticky CTA
curl -sk https://jaraba-saas.lndo.site/es/agroconecta | grep -c 'landing-sticky-cta'

# Verificar comparativa
curl -sk https://jaraba-saas.lndo.site/es/agroconecta | grep -c 'landing-comparison'

# Ejecutar validadores
php scripts/validation/validate-case-study-completeness.php
php scripts/validation/validate-vertical-cross-links.php
php scripts/validation/validate-marketing-truth.php
php scripts/validation/validate-no-hardcoded-prices.php
```

---

## Apéndice B: Archivos Nuevos a Crear

| Archivo | Fase | Tipo |
|---------|------|------|
| `_landing-sticky-cta.html.twig` | F1 | Parcial Twig |
| `_landing-trust-badges.html.twig` | F1 | Parcial Twig |
| `_landing-partner-logos.html.twig` | F3 | Parcial Twig |
| `landing-sticky.js` | F1 | JS Behavior |
| `landing-counters.js` | F3 | JS Behavior |
| `validate-landing-conversion-score.php` | F7 | Validador |

## Apéndice C: Archivos a Modificar

| Archivo | Fases | Cambios |
|---------|-------|---------|
| `VerticalLandingController.php` | F0, F2, F3, F4 | pain_points fix, comparison data, pricing tiers, hero images, más testimonials |
| `vertical-landing-content.html.twig` | F1, F3, F4 | Nuevos includes (trust-badges, partner-logos, sticky-cta), reveal classes |
| `_landing-hero.html.twig` | F1, F4, F5 | Urgency badge, hero form, video support |
| `_landing-pricing-preview.html.twig` | F2 | Multi-tier cards |
| `_landing-social-proof.html.twig` | F3 | Avatars, más testimonials, counter animation |
| `_vertical-landing.scss` | F1-F5 | Todos los nuevos estilos |
| `ecosistema_jaraba_theme.libraries.yml` | F0, F1 | Fix CSS duplicado, nueva library sticky |
| `validate-all.sh` | F7 | Nuevos validadores |

---

*Documento generado por Claude Opus 4.6 — Clase mundial o nada.*
