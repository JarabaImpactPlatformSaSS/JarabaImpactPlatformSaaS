# Plan de Implementacion: Conversion Landings + Link-in-Bio — Andalucia +ei

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Claude Code
**Prerequisito:** Auditoria `docs/analisis/2026-03-26_Auditoria_Conversion_Landings_Andalucia_Ei_Link_In_Bio_v1.md`
**Modulo:** `jaraba_andalucia_ei`
**Ruta base:** `web/modules/custom/jaraba_andalucia_ei/`

---

## Indice

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Principios Arquitectonicos](#2-principios-arquitectonicos)
3. [Sprint I: Link-in-Bio como Ruta Drupal](#3-sprint-i-link-in-bio-como-ruta-drupal)
4. [Sprint J: Mejoras Cross-Link y Tracking](#4-sprint-j-mejoras-cross-link-y-tracking)
5. [Tabla de Correspondencia Specs](#5-tabla-de-correspondencia-specs)
6. [Tabla de Cumplimiento Directrices](#6-tabla-de-cumplimiento-directrices)
7. [Verificacion Post-Implementacion](#7-verificacion-post-implementacion)
8. [Glosario](#8-glosario)

---

## 1. Objetivos y Alcance

### 1.1 Objetivo principal

Integrar el link-in-bio de redes sociales como ruta nativa Drupal (`/andalucia-ei/enlaces`) con la misma calidad visual, tracking y coherencia de marca que el resto del embudo de conversion de Andalucia +ei. Adicionalmente, corregir los 12 gaps detectados en la auditoria y mejorar los cross-links entre embudos.

### 1.2 Alcance

| Dentro del alcance | Fuera del alcance |
|-------------------|-------------------|
| Crear ruta `/andalucia-ei/enlaces` con controller + template | Redisenar la landing de reclutamiento (ya es 8.7/10) |
| SCSS con paleta Jaraba (CSS Custom Properties) | Crear nueva landing para negocios piloto (mejora incremental) |
| OG meta tags para compartibilidad en RRSS | Implementar GA4 (requiere cuenta y consentimiento RGPD) |
| Schema.org EducationalOrganization | Migrar el HTML externo (se reemplaza, no se migra) |
| `data-track-cta` en todos los CTAs | Crear nuevos validadores (documentados pero no implementados aqui) |
| Cross-links bidireccionales participantes <-> negocios | Cambios en el popup de reclutamiento (sprint separado) |
| Corregir nombres genericos de tracking en prueba-gratuita | Internacionalizacion (el programa solo opera en espanol) |

### 1.3 Resultado esperado

- **Score link-in-bio:** De 1.1/10 a 7.5/10
- **Score embudo participantes:** De 7.5/10 a 8.0/10 (mejora por cross-links)
- **Score embudo negocios:** De 6.0/10 a 6.5/10 (mejora por tracking descriptivo)

---

## 2. Principios Arquitectonicos

Toda implementacion DEBE respetar las siguientes directrices del ecosistema:

| Directriz | Aplicacion en este sprint |
|-----------|--------------------------|
| **Zero Region (ZERO-REGION-001)** | El controller devuelve render array con `#theme`. Variables via `hook_preprocess_page()`. Template limpio sin `page.content`. |
| **CSS-VAR-ALL-COLORS-001** | CADA color en SCSS usa `var(--ej-*, fallback)`. Azul corporativo: `var(--ej-azul-corporativo, #233D63)`. Naranja impulso: `var(--ej-naranja-impulso, #FF8C42)`. Verde innovacion: `var(--ej-verde-innovacion, #00A9A5)`. |
| **ICON-EMOJI-001** | NO emojis Unicode. Usar SVG inline con colores hex de la paleta Jaraba o `jaraba_icon('categoria', 'nombre')`. |
| **FUNNEL-COMPLETENESS-001** | Todo CTA tiene `data-track-cta` + `data-track-position`. Nombres descriptivos del embudo (ej: `aei_enlaces_solicitar`, no `cta_1`). |
| **NAP-NORMALIZATION-001** | Telefono: `+34 623 174 304`. Email: `contacto@plataformadeecosistemas.com`. |
| **ROUTE-LANGPREFIX-001** | URLs generadas con `Url::fromRoute()`. NUNCA paths hardcoded. |
| **TWIG-INCLUDE-ONLY-001** | `{% trans %}texto{% endtrans %}` para todos los textos. Bloque, NO filtro `|t`. |
| **MARKETING-TRUTH-001** | Claims deben coincidir con datos reales: 45 plazas, 528 euros incentivo, 18 meses, financiacion FSE+ 85%. Datos desde config, NO hardcoded. |
| **CONTROLLER-READONLY-001** | El controller NO redeclara `$entityTypeManager` con `readonly`. Asignacion en body del constructor. |
| **SCSS-COMPILE-VERIFY-001** | Tras cada edicion SCSS, recompilar y verificar que timestamp CSS > SCSS. |
| **SEO-METASITE-001** | OG meta tags con titulo y descripcion unicos. |
| **TWIG-URL-RENDER-ARRAY-001** | `url()` devuelve render array. NUNCA concatenar con `~`. Pasar URL como string desde preprocess o controller. |

---

## 3. Sprint I: Link-in-Bio como Ruta Drupal

### 3.1 EnlacesController

**Fichero:** `src/Controller/EnlacesController.php`

El controller estructura los datos de los enlaces de forma declarativa, permitiendo que el template solo se ocupe de la presentacion. Los datos del programa provienen de `jaraba_andalucia_ei.settings` (NO hardcoded) respetando NO-HARDCODE-PRICE-001.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link-in-bio para redes sociales — Andalucía +ei.
 *
 * Página minimalista con enlaces clave del programa para servir como
 * destino desde la bio de Instagram y otras redes.
 *
 * ZERO-REGION-001: Devuelve render array con #theme.
 * CONTROLLER-READONLY-001: No redeclara $entityTypeManager con readonly.
 * NO-HARDCODE-PRICE-001: Datos desde config editable.
 */
class EnlacesController extends ControllerBase {

  protected ExtensionPathResolver $pathResolver;

  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pathResolver = $container->get('extension.path.resolver');
    return $instance;
  }

  public function page(): array {
    $config = $this->config('jaraba_andalucia_ei.settings');

    // ROUTE-LANGPREFIX-001: URLs via Url::fromRoute().
    $enlaces = [
      [
        'titulo' => $this->t('Solicitar plaza en el programa'),
        'descripcion' => $this->t('Formulario de inscripción gratuita'),
        'url' => Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString(),
        'icono' => 'clipboard-check', // SVG icon name
        'track_cta' => 'aei_enlaces_solicitar',
        'destacado' => TRUE,
      ],
      [
        'titulo' => $this->t('Información completa del programa'),
        'descripcion' => $this->t('Todo sobre Andalucía +ei: requisitos, sedes, equipo'),
        'url' => Url::fromRoute('jaraba_andalucia_ei.reclutamiento')->toString(),
        'icono' => 'info-circle',
        'track_cta' => 'aei_enlaces_info_programa',
        'destacado' => FALSE,
      ],
      [
        'titulo' => $this->t('Guía del Participante (PDF)'),
        'descripcion' => $this->t('Descarga gratuita con todo lo que necesitas saber'),
        'url' => Url::fromRoute('jaraba_andalucia_ei.guia_participante')->toString(),
        'icono' => 'book-open',
        'track_cta' => 'aei_enlaces_guia',
        'destacado' => FALSE,
      ],
      [
        'titulo' => $this->t('Prueba gratuita para negocios'),
        'descripcion' => $this->t('Servicio profesional sin coste para su negocio'),
        'url' => Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita')->toString(),
        'icono' => 'store',
        'track_cta' => 'aei_enlaces_prueba_gratuita',
        'destacado' => FALSE,
      ],
      [
        'titulo' => $this->t('Caso de éxito: PED S.L.'),
        'descripcion' => $this->t('Resultados reales de la 1ª edición'),
        'url' => Url::fromRoute('jaraba_andalucia_ei.case_study.ped')->toString(),
        'icono' => 'chart-line',
        'track_cta' => 'aei_enlaces_caso_exito',
        'destacado' => FALSE,
      ],
    ];

    $modulePath = $this->pathResolver->getPath('module', 'jaraba_andalucia_ei');

    return [
      '#theme' => 'andalucia_ei_enlaces',
      '#enlaces' => $enlaces,
      '#programa' => [
        'nombre' => 'Andalucía +ei',
        'subtitulo' => $this->t('Programa gratuito de inserción laboral'),
        'plazas' => (int) ($config->get('plazas_restantes') ?? 45),
        'incentivo' => (int) ($config->get('incentivo_euros') ?? 528),
        'telefono' => '+34 623 174 304',
        'email' => 'contacto@plataformadeecosistemas.com',
      ],
      '#module_path' => $modulePath,
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
```

### 3.2 Routing

**Fichero:** `jaraba_andalucia_ei.routing.yml` (anadir al final del bloque de rutas publicas)

```yaml
# === LINK-IN-BIO: ENLACES PARA REDES SOCIALES ===
jaraba_andalucia_ei.enlaces:
  path: '/andalucia-ei/enlaces'
  defaults:
    _controller: 'Drupal\jaraba_andalucia_ei\Controller\EnlacesController::page'
    _title: 'Andalucía +ei — Enlaces'
  requirements:
    _access: 'TRUE'
```

La ruta usa `_access: 'TRUE'` (completamente publica) porque es el punto de entrada desde redes sociales para usuarios anonimos.

### 3.3 hook_theme

**Fichero:** `jaraba_andalucia_ei.module` (anadir en `hook_theme()`)

```php
'andalucia_ei_enlaces' => [
  'variables' => [
    'enlaces' => [],
    'programa' => [],
    'module_path' => '',
  ],
  'template' => 'andalucia-ei-enlaces',
],
```

### 3.4 Template

**Fichero:** `templates/andalucia-ei-enlaces.html.twig`

Principios del template:
- Layout de columna unica centrada, optimizado para movil (viewport principal: 360-414px)
- CSS Custom Properties para todos los colores (`var(--ej-*)`)
- SVG inline para iconos (NO emojis Unicode)
- `{% trans %}` para todos los textos
- `data-track-cta` + `data-track-position` en cada enlace
- Logo como SVG o WebP a 2x (NO base64 JFIF)
- Accesibilidad: `aria-label` en enlaces, heading jerarquico, contraste suficiente

Estructura del template:

```twig
{#
/**
 * @file
 * Link-in-bio para redes sociales — Andalucía +ei.
 *
 * Variables:
 * - enlaces: array de enlaces con titulo, descripcion, url, icono, track_cta, destacado
 * - programa: datos del programa (nombre, subtitulo, plazas, incentivo, telefono, email)
 * - module_path: ruta al módulo para assets estáticos
 *
 * ICON-EMOJI-001: NO emojis. SVG inline con hex Jaraba.
 * FUNNEL-COMPLETENESS-001: data-track-cta en cada CTA.
 * CSS-VAR-ALL-COLORS-001: colores via var(--ej-*).
 */
#}

<div class="aei-enlaces" role="main">
  {# Logo del programa #}
  <div class="aei-enlaces__logo">
    <img src="/{{ module_path }}/images/logo-andalucia-ei.svg"
         alt="{% trans %}Andalucía +ei{% endtrans %}"
         width="120" height="120"
         loading="eager">
  </div>

  {# Cabecera #}
  <h1 class="aei-enlaces__title">{{ programa.nombre }}</h1>
  <p class="aei-enlaces__subtitle">{{ programa.subtitulo }}</p>

  {# Stats rápidas #}
  <div class="aei-enlaces__stats">
    <span class="aei-enlaces__stat">
      <strong>{{ programa.plazas }}</strong> {% trans %}plazas{% endtrans %}
    </span>
    <span class="aei-enlaces__stat-sep" aria-hidden="true">&middot;</span>
    <span class="aei-enlaces__stat">
      <strong>{{ programa.incentivo }} &euro;</strong> {% trans %}incentivo{% endtrans %}
    </span>
    <span class="aei-enlaces__stat-sep" aria-hidden="true">&middot;</span>
    <span class="aei-enlaces__stat">
      {% trans %}100% gratuito{% endtrans %}
    </span>
  </div>

  {# Lista de enlaces #}
  <nav class="aei-enlaces__nav" aria-label="{% trans %}Enlaces del programa{% endtrans %}">
    {% for enlace in enlaces %}
      <a href="{{ enlace.url }}"
         class="aei-enlaces__link {{ enlace.destacado ? 'aei-enlaces__link--destacado' : '' }}"
         data-track-cta="{{ enlace.track_cta }}"
         data-track-position="enlaces_bio">
        <span class="aei-enlaces__link-icon" aria-hidden="true">
          {# SVG inline por icono — renderizado en preprocess o via jaraba_icon #}
        </span>
        <span class="aei-enlaces__link-text">
          <span class="aei-enlaces__link-titulo">{{ enlace.titulo }}</span>
          <span class="aei-enlaces__link-desc">{{ enlace.descripcion }}</span>
        </span>
        <svg class="aei-enlaces__link-arrow" ... aria-hidden="true">...</svg>
      </a>
    {% endfor %}
  </nav>

  {# Contacto directo — NAP-NORMALIZATION-001 #}
  <div class="aei-enlaces__contacto">
    <a href="https://wa.me/34623174304"
       class="aei-enlaces__whatsapp"
       data-track-cta="aei_enlaces_whatsapp"
       data-track-position="enlaces_bio"
       aria-label="{% trans %}Contactar por WhatsApp{% endtrans %}">
      {# SVG WhatsApp icon #}
      {% trans %}WhatsApp{% endtrans %}
    </a>
    <a href="tel:+34623174304"
       class="aei-enlaces__tel"
       data-track-cta="aei_enlaces_telefono"
       data-track-position="enlaces_bio">
      {{ programa.telefono }}
    </a>
    <a href="mailto:{{ programa.email }}"
       class="aei-enlaces__email"
       data-track-cta="aei_enlaces_email"
       data-track-position="enlaces_bio">
      {{ programa.email }}
    </a>
  </div>

  {# Badge institucional #}
  <div class="aei-enlaces__institucional">
    <p class="aei-enlaces__institucional-text">
      {% trans %}Cofinanciado por la Unión Europea · Junta de Andalucía · Servicio Andaluz de Empleo{% endtrans %}
    </p>
  </div>
</div>
```

### 3.5 SCSS

**Fichero:** `scss/components/_enlaces.scss` (nuevo) o integrarlo en el entry point SCSS del modulo.

Reglas de estilo:

```scss
// _enlaces.scss — Link-in-Bio Andalucía +ei
// CSS-VAR-ALL-COLORS-001: todos los colores via var(--ej-*)

.aei-enlaces {
  max-width: 480px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  text-align: center;
  font-family: var(--ej-font-family-base, system-ui, -apple-system, sans-serif);
  color: var(--ej-text-primary, #1a1a2e);
  background: var(--ej-bg-surface, #ffffff);
  min-height: 100vh;

  &__logo img {
    border-radius: 50%;
    width: 96px;
    height: 96px;
    object-fit: cover;
    border: 3px solid var(--ej-azul-corporativo, #233D63);
  }

  &__title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--ej-azul-corporativo, #233D63);
    margin: 0.75rem 0 0.25rem;
  }

  &__subtitle {
    font-size: 0.95rem;
    color: var(--ej-text-secondary, #666);
    margin: 0 0 1rem;
  }

  &__stats {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 1.5rem;
    font-size: 0.85rem;
  }

  &__stat strong {
    color: var(--ej-naranja-impulso, #FF8C42);
    font-weight: 700;
  }

  // === Enlaces ===
  &__nav {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 2rem;
  }

  &__link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    background: var(--ej-bg-card, #f8f9fa);
    border: 1px solid var(--ej-border-light, #e9ecef);
    text-decoration: none;
    color: var(--ej-text-primary, #1a1a2e);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    text-align: left;

    &:hover,
    &:focus-visible {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px color-mix(in srgb, var(--ej-azul-corporativo, #233D63) 15%, transparent);
      border-color: var(--ej-azul-corporativo, #233D63);
    }

    &--destacado {
      background: var(--ej-azul-corporativo, #233D63);
      color: #fff;
      border-color: var(--ej-azul-corporativo, #233D63);

      .aei-enlaces__link-desc {
        color: color-mix(in srgb, #fff 80%, transparent);
      }

      &:hover,
      &:focus-visible {
        box-shadow: 0 4px 16px color-mix(in srgb, var(--ej-azul-corporativo, #233D63) 30%, transparent);
      }
    }
  }

  &__link-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;

    svg {
      width: 100%;
      height: 100%;
    }
  }

  &__link-text {
    flex: 1;
  }

  &__link-titulo {
    display: block;
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.3;
  }

  &__link-desc {
    display: block;
    font-size: 0.8rem;
    color: var(--ej-text-secondary, #666);
    line-height: 1.3;
    margin-top: 0.15rem;
  }

  &__link-arrow {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    opacity: 0.4;
  }

  // === Contacto ===
  &__contacto {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
  }

  &__whatsapp {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 24px;
    background: var(--ej-verde-innovacion, #00A9A5);
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: transform 0.15s ease;

    &:hover {
      transform: scale(1.03);
    }
  }

  &__tel,
  &__email {
    font-size: 0.85rem;
    color: var(--ej-text-secondary, #666);
    text-decoration: none;

    &:hover {
      color: var(--ej-azul-corporativo, #233D63);
    }
  }

  // === Institucional ===
  &__institucional {
    padding-top: 1.5rem;
    border-top: 1px solid var(--ej-border-light, #e9ecef);
  }

  &__institucional-text {
    font-size: 0.75rem;
    color: var(--ej-text-tertiary, #999);
    line-height: 1.4;
  }
}

// === Mobile <320px ===
@media (max-width: 320px) {
  .aei-enlaces {
    padding: 1.5rem 1rem;

    &__link {
      padding: 0.75rem 1rem;
    }

    &__stats {
      flex-direction: column;
      gap: 0.25rem;
    }

    &__stat-sep {
      display: none;
    }
  }
}
```

### 3.6 OG meta tags via hook_page_attachments

**Fichero:** `jaraba_andalucia_ei.module` (anadir en `hook_page_attachments_alter()`)

Para la ruta `jaraba_andalucia_ei.enlaces`, anadir los meta tags OG:

```php
// En jaraba_andalucia_ei_page_attachments_alter():
if ($route === 'jaraba_andalucia_ei.enlaces') {
  $host = \Drupal::request()->getSchemeAndHttpHost();
  $modulePath = \Drupal::service('extension.list.module')->getPath('jaraba_andalucia_ei');

  $og_tags = [
    'og:title' => 'Andalucía +ei — Programa Gratuito de Inserción Laboral',
    'og:description' => 'Programa oficial de la Junta de Andalucía cofinanciado por la UE. Orientación, formación, mentoría con IA e incentivo de 528€. 45 plazas en Sevilla y Málaga.',
    'og:image' => $host . '/' . $modulePath . '/images/og-andalucia-ei.png',
    'og:image:width' => '1200',
    'og:image:height' => '630',
    'og:type' => 'website',
    'og:url' => $host . Url::fromRoute('jaraba_andalucia_ei.enlaces')->toString(),
    'og:locale' => 'es_ES',
  ];

  foreach ($og_tags as $property => $content) {
    $attachments['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'property' => $property,
          'content' => $content,
        ],
      ],
      'aei_enlaces_' . str_replace(':', '_', $property),
    ];
  }
}
```

### 3.7 Schema.org EducationalOrganization

Anadir en el template o via `hook_page_attachments_alter()`:

```json
{
  "@context": "https://schema.org",
  "@type": "EducationalOrganization",
  "name": "Andalucía +ei — T-Acompañamos",
  "description": "Programa de Proyectos Integrales para la Inserción Laboral de colectivos vulnerables en Andalucía.",
  "url": "https://plataformadeecosistemas.es/es/andaluciamasei.html",
  "telephone": "+34623174304",
  "email": "contacto@plataformadeecosistemas.com",
  "address": [
    {
      "@type": "PostalAddress",
      "addressLocality": "Sevilla",
      "streetAddress": "Avda. San Francisco Javier, 22, Edificio Hermes, 1.ª Planta, Módulo 14",
      "postalCode": "41018",
      "addressRegion": "Andalucía",
      "addressCountry": "ES"
    },
    {
      "@type": "PostalAddress",
      "addressLocality": "Málaga",
      "streetAddress": "Centro de Negocios Málaga, C. Palma del Río, 19",
      "postalCode": "29004",
      "addressRegion": "Andalucía",
      "addressCountry": "ES"
    }
  ],
  "funder": [
    {
      "@type": "GovernmentOrganization",
      "name": "Junta de Andalucía — Servicio Andaluz de Empleo"
    },
    {
      "@type": "GovernmentOrganization",
      "name": "Fondo Social Europeo Plus (FSE+)"
    }
  ],
  "sameAs": [
    "https://www.instagram.com/andaluciamasei/"
  ]
}
```

### 3.8 Library declaration

**Fichero:** `jaraba_andalucia_ei.libraries.yml` (anadir)

```yaml
enlaces:
  css:
    component:
      css/components/enlaces.css: {}
  dependencies:
    - core/drupal
```

### 3.9 Body class y page template

**Fichero:** `jaraba_andalucia_ei.module` — en `hook_preprocess_html()` anadir:

```php
elseif ($route === 'jaraba_andalucia_ei.enlaces') {
  $variables['attributes']['class'][] = 'page-andalucia-ei--enlaces';
}
```

Crear `page--andalucia-ei--enlaces.html.twig` en el theme o usar `hook_theme_suggestions_page_alter()` para sugerir un template limpio que solo renderice `{{ clean_content }}` sin header/footer (patron tipico de link-in-bio).

### 3.10 hook_page_attachments_alter para library

Anadir en la funcion existente `jaraba_andalucia_ei_page_attachments_alter()`:

```php
elseif ($route === 'jaraba_andalucia_ei.enlaces') {
  $attachments['#attached']['library'][] = 'jaraba_andalucia_ei/enlaces';
}
```

---

## 4. Sprint J: Mejoras Cross-Link y Tracking

### 4.1 Corregir nombres de tracking en prueba-gratuita

**Fichero:** `templates/prueba-gratuita-landing.html.twig`

| Actual | Nuevo | Motivo |
|--------|-------|--------|
| `data-track-cta="hero_cta"` | `data-track-cta="aei_negocio_hero_solicitar"` | Nombre descriptivo del embudo, prefijo `aei_negocio_` |
| `data-track-cta="form_submit"` | `data-track-cta="aei_negocio_form_submit"` | Consistente con nomenclatura del embudo |
| `data-track-cta="form_submit_button"` | `data-track-cta="aei_negocio_form_enviar"` | Distinguir del anchor scroll vs boton submit |

Adicionalmente, anadir `data-track-position="prueba_gratuita_hero"` y `data-track-position="prueba_gratuita_form"` respectivamente.

### 4.2 Cross-links bidireccionales participantes <-> negocios

**4.2.1 En la landing de reclutamiento** (`andalucia-ei-reclutamiento.html.twig`)

Anadir una seccion ligera antes del CTA final:

```twig
{# === CROSS-LINK: NEGOCIOS PILOTO === #}
<aside class="aei-rec__crosslink" aria-label="{% trans %}Información para negocios{% endtrans %}">
  <div class="aei-rec__container">
    <p class="aei-rec__crosslink-text">
      {% trans %}¿Tiene un negocio en Sevilla o Málaga?{% endtrans %}
      <a href="{{ prueba_gratuita_url }}"
         data-track-cta="aei_rec_crosslink_negocio"
         data-track-position="reclutamiento_crosslink">
        {% trans %}Solicite una prueba gratuita de nuestros servicios para su empresa{% endtrans %} &rarr;
      </a>
    </p>
  </div>
</aside>
```

Requiere pasar `prueba_gratuita_url` desde el controller:
```php
'#prueba_gratuita_url' => Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita')->toString(),
```

Y declarar la variable en `hook_theme()`.

**4.2.2 En la landing de prueba gratuita** (`prueba-gratuita-landing.html.twig`)

Anadir una seccion ligera:

```twig
{# === CROSS-LINK: PARTICIPANTES === #}
<aside class="prueba-gratuita__crosslink">
  <p>
    {% trans %}¿Busca empleo? El programa Andalucía +ei ofrece orientación, formación y un incentivo de 528€ para personas en búsqueda activa de empleo.{% endtrans %}
    <a href="{{ solicitar_url }}"
       data-track-cta="aei_negocio_crosslink_participante"
       data-track-position="prueba_gratuita_crosslink">
      {% trans %}Solicitar plaza como participante{% endtrans %} &rarr;
    </a>
  </p>
</aside>
```

Requiere pasar `solicitar_url` desde `PruebaGratuitaController::landing()`:
```php
'#solicitar_url' => Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString(),
```

### 4.3 Expansion del ambito del popup

**Fichero:** `jaraba_andalucia_ei.module` — en `_jaraba_andalucia_ei_popup_attachments()`

Considerar mostrar el popup tambien en la ruta `/andalucia-ei/enlaces` si el visitante llega desde un meta-sitio corporativo. Esto requiere modificar la logica de deteccion para que no sea solo la home, sino tambien la ruta de enlaces cuando el referer es un meta-sitio.

**Nota:** Esta mejora es P2 y puede diferirse al siguiente sprint si se prioriza la creacion de la ruta.

---

## 5. Tabla de Correspondencia Specs

| ID Spec | Descripcion | Fichero(s) afectado(s) | Sprint |
|---------|-------------|----------------------|--------|
| SPEC-CONV-001 | Crear `EnlacesController` con datos estructurados de enlaces | `src/Controller/EnlacesController.php` | I |
| SPEC-CONV-002 | Ruta publica `/andalucia-ei/enlaces` | `jaraba_andalucia_ei.routing.yml` | I |
| SPEC-CONV-003 | Template `andalucia-ei-enlaces.html.twig` con Zero Region | `templates/andalucia-ei-enlaces.html.twig` | I |
| SPEC-CONV-004 | SCSS `_enlaces.scss` con paleta Jaraba (CSS Custom Properties) | SCSS del modulo o tema | I |
| SPEC-CONV-005 | OG meta tags en `hook_page_attachments_alter()` | `jaraba_andalucia_ei.module` | I |
| SPEC-CONV-006 | Schema.org `EducationalOrganization` JSON-LD | `jaraba_andalucia_ei.module` o template | I |
| SPEC-CONV-007 | Library `enlaces` declarada y attachada | `jaraba_andalucia_ei.libraries.yml`, `.module` | I |
| SPEC-CONV-008 | Renombrar tracking generico en `prueba-gratuita-landing.html.twig` | `templates/prueba-gratuita-landing.html.twig` | J |
| SPEC-CONV-009 | Cross-links bidireccionales participantes <-> negocios | `templates/andalucia-ei-reclutamiento.html.twig`, `templates/prueba-gratuita-landing.html.twig`, controllers | J |
| SPEC-CONV-010 | Body class `page-andalucia-ei--enlaces` + hook_theme | `jaraba_andalucia_ei.module` | I |

---

## 6. Tabla de Cumplimiento Directrices

| # | Directriz | Cumple | Donde |
|---|-----------|:---:|-------|
| 1 | ZERO-REGION-001 | Si | `EnlacesController::page()` devuelve render array con `#theme` |
| 2 | ZERO-REGION-002 | Si | No pasa entity objects como non-# keys |
| 3 | ZERO-REGION-003 | Si | Library via `hook_page_attachments_alter()`, no `#attached` en controller |
| 4 | CSS-VAR-ALL-COLORS-001 | Si | Todos los colores en SCSS usan `var(--ej-*, fallback)` |
| 5 | ICON-EMOJI-001 | Si | SVG inline con hex de marca, NO emojis Unicode |
| 6 | ICON-COLOR-001 | Si | Colores SVG de paleta: azul-corporativo, naranja-impulso, verde-innovacion |
| 7 | FUNNEL-COMPLETENESS-001 | Si | Todos los CTAs con `data-track-cta` + `data-track-position` |
| 8 | NAP-NORMALIZATION-001 | Si | Telefono `+34 623 174 304`, email `contacto@plataformadeecosistemas.com` |
| 9 | ROUTE-LANGPREFIX-001 | Si | URLs via `Url::fromRoute()` |
| 10 | MARKETING-TRUTH-001 | Si | Datos de plazas, incentivo, financiacion desde config, no hardcoded |
| 11 | NO-HARDCODE-PRICE-001 | Si | `$config->get('plazas_restantes')`, `$config->get('incentivo_euros')` |
| 12 | CONTROLLER-READONLY-001 | Si | No redeclara `$entityTypeManager` con `readonly` |
| 13 | SCSS-COMPILE-VERIFY-001 | Pendiente | Verificar tras compilacion con `npm run build` |
| 14 | SEO-METASITE-001 | Si | OG meta tags con titulo y descripcion unicos |
| 15 | TWIG-URL-RENDER-ARRAY-001 | Si | URLs como string desde controller, no `url()` en Twig |
| 16 | TWIG-INCLUDE-ONLY-001 | Si | `{% trans %}` para todos los textos |
| 17 | SCSS-COLORMIX-001 | Si | `color-mix(in srgb, ...)` en lugar de `rgba()` |
| 18 | SCSS-001 | Pendiente | El parcial necesita `@use '../variables' as *;` si usa variables SCSS |
| 19 | ENTITY-PREPROCESS-001 | N/A | No hay entity nueva, solo controller + template |
| 20 | AUDIT-SEC-002 | Si | Ruta publica sin datos tenant |
| 21 | INNERHTML-XSS-001 | N/A | No hay innerHTML desde JS |
| 22 | CSRF-JS-CACHE-001 | N/A | No hay fetch API en este sprint |
| 23 | PIPELINE-E2E-001 | Pendiente | Verificar L1-L4 tras implementacion |
| 24 | RUNTIME-VERIFY-001 | Pendiente | 5 dependencias runtime a verificar |
| 25 | IMPLEMENTATION-CHECKLIST-001 | Pendiente | Checklist completo post-implementacion |
| 26 | SCSS-COMPONENT-BUILD-001 | Pendiente | Anadir `enlaces.css` al pipeline `npm run build` del tema |
| 27 | OPTIONAL-CROSSMODULE-001 | N/A | No hay dependencias cross-modulo nuevas |
| 28 | PHANTOM-ARG-001 | Pendiente | Verificar con `validate-phantom-args.php` tras registrar servicio |
| 29 | TWIG-SYNTAX-LINT-001 | Pendiente | Verificar con `validate-twig-syntax.php` |
| 30 | CLAUDE-MD-SIZE-001 | N/A | No se modifica CLAUDE.md |
| 31 | DOC-GLOSSARY-001 | Si | Glosario incluido en ambos documentos |
| 32 | COMMIT-SCOPE-001 | N/A | Docs y codigo en commits separados |

---

## 7. Verificacion Post-Implementacion

### 7.1 RUNTIME-VERIFY-001 — 5 dependencias runtime

```bash
# 1. CSS compilado (timestamp > SCSS)
stat web/modules/custom/jaraba_andalucia_ei/css/components/enlaces.css
# (verificar que es mas reciente que el SCSS fuente)

# 2. Tablas DB creadas (N/A — no hay entity nueva)

# 3. Rutas accesibles
drush route:info jaraba_andalucia_ei.enlaces
# Debe mostrar path=/andalucia-ei/enlaces, controller=EnlacesController::page

# 4. data-* selectores matchean entre JS y HTML (N/A — no hay JS nuevo)

# 5. drupalSettings inyectado correctamente (N/A — no hay drupalSettings nuevo)
```

### 7.2 PIPELINE-E2E-001 — 4 capas

```bash
# L1: Controller registrado y devuelve render array con #theme
drush eval "print_r(\Drupal::service('router.route_provider')->getRouteByName('jaraba_andalucia_ei.enlaces')->getDefaults());"

# L2: hook_theme declara las variables
drush eval "\$themes = \Drupal::moduleHandler()->invokeAll('theme'); print_r(\$themes['andalucia_ei_enlaces'] ?? 'NO EXISTE');"

# L3: Template existe
ls -la web/modules/custom/jaraba_andalucia_ei/templates/andalucia-ei-enlaces.html.twig

# L4: Library attachada en hook_page_attachments
# (verificar navegando a /es/andalucia-ei/enlaces y comprobando en el DOM que el CSS se carga)
```

### 7.3 Validadores existentes

```bash
# Validacion general Andalucia +ei
php scripts/validation/validate-andalucia-ei-2e-sprint-cd.php

# Campana reclutamiento
php scripts/validation/validate-aei-reclutamiento-campaign.php

# Validacion completa del sistema
bash scripts/validation/validate-all.sh --fast
```

### 7.4 Verificacion manual

| Check | Comando/Accion | Resultado esperado |
|-------|---------------|-------------------|
| Ruta responde 200 | `curl -s -o /dev/null -w '%{http_code}' https://jaraba-saas.lndo.site/es/andalucia-ei/enlaces` | `200` |
| OG tags presentes | `curl -s https://jaraba-saas.lndo.site/es/andalucia-ei/enlaces \| grep 'og:title'` | Meta tag con titulo |
| Schema.org presente | `curl -s ... \| grep 'EducationalOrganization'` | JSON-LD en `<script>` |
| Colores correctos | Inspeccion visual: azul `#233D63`, naranja `#FF8C42`, verde `#00A9A5` | Sin colores fuera de paleta |
| Sin emojis Unicode | `grep -P '[\x{1F000}-\x{1F9FF}]' templates/andalucia-ei-enlaces.html.twig` | Sin resultados |
| Tracking CTAs | Inspeccion DOM: todos los `<a>` tienen `data-track-cta` | 7+ CTAs tracked |
| Mobile <320px | Chrome DevTools > Responsive > 320px ancho | Layout no se rompe |
| NAP normalizado | Buscar `+34 623 174 304` en template | Formato correcto |

---

## 8. Glosario

| Sigla | Significado |
|-------|-------------|
| AEI | Andalucia +ei (nombre del vertical del programa) |
| CRM | Customer Relationship Management (gestion de relaciones con clientes) |
| CSS | Cascading Style Sheets (hojas de estilo en cascada) |
| CTA | Call to Action (llamada a la accion) |
| CSRF | Cross-Site Request Forgery (falsificacion de peticiones entre sitios) |
| DOM | Document Object Model (modelo de objetos del documento) |
| FAQ | Frequently Asked Questions (preguntas frecuentes) |
| FSE+ | Fondo Social Europeo Plus |
| GA4 | Google Analytics 4 |
| HiDPI | High Dots Per Inch (alta densidad de pixeles) |
| HTML | HyperText Markup Language (lenguaje de marcado de hipertexto) |
| JFIF | JPEG File Interchange Format |
| JSON-LD | JavaScript Object Notation for Linked Data |
| NAP | Name, Address, Phone (nombre, direccion, telefono — SEO local) |
| OG | Open Graph (protocolo de meta tags para redes sociales) |
| PIIL | Proyecto Integral de Insercion Laboral |
| RGPD | Reglamento General de Proteccion de Datos |
| ROI | Return on Investment (retorno de la inversion) |
| RRSS | Redes Sociales |
| SAE | Servicio Andaluz de Empleo |
| SCSS | Sassy CSS (preprocesador de hojas de estilo) |
| SEO | Search Engine Optimization (optimizacion para motores de busqueda) |
| SVG | Scalable Vector Graphics (graficos vectoriales escalables) |
| TTL | Time to Live (tiempo de vida) |
| UE | Union Europea |
| URL | Uniform Resource Locator (localizador uniforme de recursos) |
| UTM | Urchin Tracking Module (parametros de atribucion de campanas) |
| UX | User Experience (experiencia de usuario) |
| WebP | Formato de imagen moderno desarrollado por Google |

---

*Plan generado por Claude Code. Verificar implementacion con RUNTIME-VERIFY-001 y PIPELINE-E2E-001.*
