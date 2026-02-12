# Plan de Implementacion: F4 — Landing Pages Verticales (Doc 180)
## 5 Landings con Estructura de 9 Secciones, Schema.org, SEO/GEO

**Fecha de creacion:** 2026-02-12
**Ultima actualizacion:** 2026-02-12
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Plan de Implementacion — Cierre de Gap F4
**Codigo:** IMPL-F4-LANDING-VERTICALES-v1
**Fase del Plan Maestro:** F4 de 12 (PLAN-20260128-GAPS-v1, §9)
**Documento de Especificacion:** `180_Landing_Pages_Verticales_v1`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Analisis de Estado Actual](#2-analisis-de-estado-actual)
3. [Gaps Identificados](#3-gaps-identificados)
4. [Arquitectura de la Solucion](#4-arquitectura-de-la-solucion)
5. [Componente 1: 9 Parciales Twig Reutilizables](#5-componente-1-9-parciales-twig-reutilizables)
6. [Componente 2: Controller + Rutas (5 Verticales)](#6-componente-2-controller--rutas-5-verticales)
7. [Componente 3: SCSS Secciones Landing](#7-componente-3-scss-secciones-landing)
8. [Componente 4: Schema.org JSON-LD + Meta SEO](#8-componente-4-schemaorg-json-ld--meta-seo)
9. [Contenido por Vertical](#9-contenido-por-vertical)
10. [Tabla de Correspondencia con Especificaciones](#10-tabla-de-correspondencia-con-especificaciones)
11. [Checklist de Cumplimiento de Directrices](#11-checklist-de-cumplimiento-de-directrices)
12. [Plan de Verificacion](#12-plan-de-verificacion)

---

## 1. Resumen Ejecutivo

### Proposito

Esta fase implementa las 5 landing pages verticales completas con una estructura de 9 secciones optimizada para conversion y SEO. Es el puente entre la Homepage (F3) y el Registro, y representa la pieza clave del funnel AIDA para cada vertical.

### Que resuelve

| Problema Actual | Solucion F4 |
|-----------------|-------------|
| Landings existentes solo tienen 3 secciones (Hero, Benefits, CTA) | Estructura completa de 9 secciones orientada a conversion |
| No existen rutas /agroconecta, /comercioconecta, /serviciosconecta, /empleabilidad, /emprendimiento | 5 nuevas rutas publicas con contenido especifico |
| Sin Schema.org para SEO | JSON-LD FAQPage + Service/Product schema por vertical |
| Sin lead magnet integrado en landing | Seccion 6 enlaza directamente con los lead magnets de F3 |
| Sin social proof | Testimonios, metricas de impacto, logos |
| Sin pricing preview | Vista previa de planes con CTA a /planes |
| Sin FAQ | 5-7 preguntas frecuentes indexables por Google |

### Metricas de Exito (Doc 180)

| Vertical | Visitor-to-Lead | Lead-to-Trial | Trial-to-Paid |
|----------|----------------|---------------|---------------|
| AgroConecta | 8% | 25% | 35% |
| ComercioConecta | 10% | 30% | 42% |
| ServiciosConecta | 6% | 22% | 28% |
| Empleabilidad | 7% | 20% | 18% |
| Emprendimiento | 9% | 28% | 32% |

---

## 2. Analisis de Estado Actual

### 2.1 Infraestructura Existente

| Componente | Ubicacion | Estado |
|-----------|-----------|--------|
| VerticalLandingController | `ecosistema_jaraba_core/src/Controller/` | 5 metodos basicos (/empleo, /talento, /emprender, /comercio, /instituciones) |
| page--vertical-landing.html.twig | Theme templates | Operativo (shell limpio con header/footer) |
| vertical-landing-content.html.twig | Theme templates/partials | Solo 3 secciones: Hero, Benefits, BottomCTA |
| _vertical-landing.scss | ecosistema_jaraba_core/scss/ | Estilos basicos para 3 secciones |
| Lead Magnets (F3) | 4 rutas publicas | Operativos |
| Tracking AIDA (F3) | visitor-journey + aida-tracking.js | Operativo |
| FreemiumVerticalLimit (F2) | 45 configuraciones | Operativo |

### 2.2 Que Falta

| Componente | Estado | Prioridad |
|-----------|--------|-----------|
| Rutas /empleabilidad, /emprendimiento, /agroconecta, /comercioconecta, /serviciosconecta | No existen | P0 |
| 6 secciones adicionales por landing (Pain Points, Solution, Features, Social Proof, Lead Magnet, Pricing, FAQ) | No existen | P0 |
| 9 parciales Twig reutilizables | No existen | P0 |
| Schema.org JSON-LD (FAQPage) | No existe | P1 |
| Meta SEO por vertical (og:title, og:description) | No existe | P1 |
| Contenido completo por vertical (copy, testimonios, FAQs) | No existe | P0 |

---

## 3. Gaps Identificados

| Gap ID | Seccion Doc 180 | Descripcion | Impacto |
|--------|----------------|-------------|---------|
| G1 | §1 Estructura | Solo 3 de 9 secciones implementadas | Landing no convierte (faltan pain points, social proof, FAQ) |
| G2 | §2 Rutas | Las 5 rutas verticales principales no existen | Visitantes del selector vertical llegan a 404 |
| G3 | §3 SEO | Sin Schema.org ni meta tags por vertical | Invisible para Google Rich Results |
| G4 | §4 Contenido | Sin copy especifico por vertical | Mensaje generico que no conecta con el visitante |
| G5 | §5 Lead Magnet | Sin integracion de lead magnets F3 en landings | Se pierde el paso DESIRE del funnel AIDA |

---

## 4. Arquitectura de la Solucion

### Jerarquia de Templates

```
page--vertical-landing.html.twig (shell: html, head, body, header, footer)
  └── vertical-landing-content.html.twig (orquestador de 9 secciones)
       ├── _landing-hero.html.twig
       ├── _landing-pain-points.html.twig
       ├── _landing-solution-steps.html.twig
       ├── _landing-features-grid.html.twig
       ├── _landing-social-proof.html.twig
       ├── _landing-lead-magnet.html.twig
       ├── _landing-pricing-preview.html.twig
       ├── _landing-faq.html.twig
       └── _landing-final-cta.html.twig
```

### Flujo de Datos

```
[Ruta publica] → VerticalLandingController::agroconecta()
  → buildLanding9(data_completo_9_secciones)
    → #theme = 'vertical_landing_content'
    → #vertical_data = { hero, pain_points, solution, features, social_proof, lead_magnet, pricing, faq, final_cta }
      → Template orquestador incluye 9 parciales con datos
```

### Integracion con Fases Anteriores

- **F2**: pricing_preview referencia planes de FreemiumVerticalLimit
- **F3**: lead_magnet seccion enlaza con rutas de LeadMagnetController
- **F3**: data-track-cta en todos los CTAs para AIDA tracking

---

## 5. Componente 1: 9 Parciales Twig Reutilizables

### Ubicacion

Todos en: `web/themes/custom/ecosistema_jaraba_theme/templates/partials/`

### Parciales

| Parcial | Variables | Responsabilidad |
|---------|-----------|-----------------|
| `_landing-hero.html.twig` | hero.headline, hero.subheadline, hero.cta, hero.icon, color | Hero full-width con CTA principal |
| `_landing-pain-points.html.twig` | pain_points[] (icon, text) | Grid 2x2 de problemas que resolvemos |
| `_landing-solution-steps.html.twig` | steps[] (number, title, description) | 3 pasos simples horizontales |
| `_landing-features-grid.html.twig` | features[] (icon, title, description) | Grid 3x2 de features con iconos |
| `_landing-social-proof.html.twig` | testimonials[], metrics[] | Testimonios + numeros de impacto |
| `_landing-lead-magnet.html.twig` | lead_magnet.title, .url, .type | Banner CTA al lead magnet de F3 |
| `_landing-pricing-preview.html.twig` | pricing.from_price, .cta_url | Vista previa de precio + link a planes |
| `_landing-faq.html.twig` | faq[] (question, answer) | Acordeon accesible + Schema.org |
| `_landing-final-cta.html.twig` | final_cta.headline, .cta | Repeticion del CTA principal |

---

## 6. Componente 2: Controller + Rutas

### Nuevos Metodos

5 nuevos metodos en VerticalLandingController:
- `empleabilidad()` → /empleabilidad
- `emprendimiento()` → /emprendimiento
- `agroconecta()` → /agroconecta
- `comercioconecta()` → /comercioconecta
- `serviciosconecta()` → /serviciosconecta

Cada metodo retorna datos completos de 9 secciones via `buildLanding()` actualizado.

---

## 7. Componente 3: SCSS Secciones Landing

### Archivo

`_landing-sections.scss` en el theme (ya que los parciales estan ahi).

### Secciones a estilizar

- `.landing-pain-points` - Grid 2x2 con iconos y texto
- `.landing-solution-steps` - 3 pasos horizontales con numero
- `.landing-features-grid` - Grid 3x2 responsive
- `.landing-social-proof` - Testimonios en carousel + metricas
- `.landing-lead-magnet` - Banner CTA full-width
- `.landing-pricing-preview` - Card con precio + CTA
- `.landing-faq` - Acordeon accesible
- `.landing-final-cta` - Banner de cierre

---

## 8. Componente 4: Schema.org JSON-LD

### FAQPage Schema

Inyectado via `<script type="application/ld+json">` en _landing-faq.html.twig:

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Pregunta",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Respuesta"
      }
    }
  ]
}
```

### Meta Tags SEO

Inyectados via hook_preprocess_html o metatag module:
- og:title, og:description, og:image por vertical
- twitter:card = summary_large_image

---

## 9. Contenido por Vertical

Contenido detallado en el controller, siguiendo Doc 180 §2-6:
- 5 heroes con headline/subheadline/CTA especificos
- 5 conjuntos de 3-4 pain points
- 5 conjuntos de 3 solution steps
- 5 conjuntos de 6 features
- 5 conjuntos de testimonios + metricas
- 5 links a lead magnets de F3
- 5 pricing previews
- 5 conjuntos de 5-7 FAQs

---

## 10. Tabla de Correspondencia con Especificaciones

| Seccion Doc 180 | Componente F4 | Archivo(s) |
|-----------------|--------------|------------|
| §1 Estructura 9 secciones | 9 parciales Twig | templates/partials/_landing-*.html.twig |
| §2.1 AgroConecta | Controller + datos | VerticalLandingController::agroconecta() |
| §2.2 ComercioConecta | Controller + datos | VerticalLandingController::comercioconecta() |
| §2.3 ServiciosConecta | Controller + datos | VerticalLandingController::serviciosconecta() |
| §2.4 Empleabilidad | Controller + datos | VerticalLandingController::empleabilidad() |
| §2.5 Emprendimiento | Controller + datos | VerticalLandingController::emprendimiento() |
| §3 SEO/Schema.org | JSON-LD en FAQ + meta tags | _landing-faq.html.twig + hook_preprocess |
| §4 Conversion | data-track-cta en CTAs | Todos los parciales |
| §5 Responsive | SCSS mobile-first | _landing-sections.scss |

---

## 11. Checklist de Cumplimiento de Directrices

| Directriz | Detalle |
|-----------|---------|
| i18n `$this->t()` en PHP | Todos los textos del controller |
| i18n `{% trans %}` en Twig | Textos estaticos de parciales |
| SCSS `var(--ej-*)` | Todos los colores, spacing, tipografia con fallbacks |
| Dart Sass moderno `@use` | Sin `@import` |
| Templates limpios sin regiones | page--vertical-landing.html.twig ya cumple |
| Parciales `{% include %}` | 9 parciales reutilizables |
| `jaraba_icon()` para iconos | SVG duotone en todas las secciones |
| Mobile-first layout | Breakpoints progresivos (480px, 768px, 1024px) |
| data-track-cta para AIDA | Todos los CTAs con tracking attributes |
| Rutas publicas `_access: TRUE` | 5 nuevas rutas sin autenticacion |

---

## 12. Plan de Verificacion

1. **Rutas**: 5 URLs responden 200 OK
2. **Secciones**: Cada landing muestra las 9 secciones
3. **Schema.org**: Validar con Google Rich Results Test
4. **Mobile**: Verificar en 375px, 768px, 1024px
5. **SCSS**: Compila sin errores
6. **Tracking**: data-track-cta en todos los CTAs
7. **i18n**: Ningun texto hardcodeado
8. **Lead Magnets**: Links funcionales a F3
