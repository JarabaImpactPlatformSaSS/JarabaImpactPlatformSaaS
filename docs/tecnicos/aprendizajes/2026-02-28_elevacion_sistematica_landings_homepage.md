# Aprendizaje #155 — Elevacion Sistematica Landing Pages + Homepage — Patron 3 Niveles

**Fecha:** 2026-02-28
**Contexto:** Elevacion de Empleabilidad (capa config), Talento (landing + Schema.org) y Homepage (6 Twig partials + SEO)
**Reglas de oro:** #92 (landing elevation 3-level pattern), #93 (METRICS-HONESTY: solo datos verificables de plataforma)

---

## Problema

Las landing pages verticales y la homepage del SaaS presentaban informacion desactualizada, inconsistente o incompleta:
- Verticales con 4-6 features cuando la infraestructura real soporta 10-12
- Homepage con "6 verticales" cuando existen 10
- Stats fabricados o no verificables (1500+ candidatos, 120+ empresas)
- Ausencia de meta description, OG tags y Twitter cards en homepage
- Schema.org con highPrice=99 cuando Enterprise cuesta 199
- FAQs insuficientes (4 genericas en lugar de 10+ especificas)
- Testimonios genericos reutilizados entre verticales

## Solucion: Patron de Elevacion en 3 Niveles

### Nivel 1 — Landing Content (VerticalLandingController o Twig partials)

Para **verticales** (VerticalLandingController::buildLanding()):
- Features: auditar infraestructura real (entities, services, agents) y reflejar en features array
- FAQs: expandir a 10+ con preguntas especificas del vertical, mencionando features reales
- Stats: solo metricas verificables desde el codebase (entidades, servicios, algoritmos)
- Testimonials: persona-especificos (recruiter para talento, agricultor para agro, etc.)
- URLs: SIEMPRE `Url::fromRoute()`, NUNCA hardcodear `/planes` o `/user/register`

Para **homepage** (Twig partials template-driven):
- Arquitectura diferente: `page--front.html.twig` → 13 `{% include %}` partials
- Editar directamente los Twig partials con fallback data (`{% set default_* = [...] %}`)
- NO es un controller PHP — los datos estan en templates como fallbacks

### Nivel 2 — Schema.org + SEO (PageAttachmentsHooks.php)

- Schema.org SoftwareApplication JSON-LD con featureList completo
- Meta description con propuesta de valor especifica del vertical
- OG tags (og:title, og:description, og:type, og:url)
- Twitter cards (twitter:card, twitter:title, twitter:description)
- Para homepage: deteccion via `\Drupal::service('path.matcher')->isFrontPage()`
- Para verticales: deteccion via route name match

### Nivel 3 — Config Layer (FreemiumVerticalLimit + SaasPlan + PlanFeatures)

- FreemiumVerticalLimit: 6+ feature_keys × 3 planes = 18+ configs por vertical
- SaasPlan: 3 tiers (Starter/Pro/Enterprise) con precios, features_preview, Stripe price IDs
- PlanFeatures: limits coherentes con FVL source of truth (-1 = ilimitado para paid)
- Coherencia: Starter limits > Free limits para cada metrica
- **No aplica a Talento** (hereda de Empleabilidad — es recruiter persona, no vertical independiente)

## Descubrimientos Clave

### Talento NO es un vertical canonico

Talento es una landing de persona recruiter dentro del vertical Empleabilidad:
- Avatar: `recruiter` (no vertical independiente)
- Config: hereda de `empleabilidad_*` FVL/SaasPlan/PlanFeatures
- NO necesita FVL/SaasPlan/PlanFeatures propios
- PERO SI necesita landing elevada con features de `jaraba_job_board` (RecruiterAssistantAgent, MatchingService 5D, ATS 8 estados, WebPush, fraud detection)

### Homepage es template-driven, NO controller-driven

- Homepage usa `page--front.html.twig` con 13 partials incluidos
- Datos fallback estan hardcodeados en los templates Twig (`{% set default_stats = [...] %}`)
- NO hay metodo PHP que devuelva datos — HomepageDataService es minimal
- Para actualizar: editar directamente los Twig partials
- Para SEO: usar `isFrontPage()` en PageAttachmentsHooks (no route name match)

### METRICS-HONESTY — Solo datos verificables

La directiva METRICS-HONESTY en `_stats.html.twig` exige solo datos verificables:
- 10 verticales — verificable: VERTICAL-CANONICAL-001 en BaseAgent
- 11 agentes IA Gen 2 — verificable: listado en CLAUDE.md
- 80+ modulos custom — verificable: `ls web/modules/custom/ | wc -l`
- 100% recomendarian — verificable: encuesta programa Andalucia+ei
- NUNCA: "1500+ candidatos", "120+ empresas" sin fuente verificable

## Archivos Modificados

### Empleabilidad (config layer)
- `PageAttachmentsHooks.php` — Schema.org SoftwareApplication 15 featureList + meta/OG/Twitter
- 12 nuevos FVL YAML (3 starter + 3 profesional + 6 enterprise)
- 2 FVL bugs corregidos (starter diagnostics -1→3, offers -1→25)
- `plan_features.empleabilidad_professional.yml` — todos limits → -1
- 3 SaasPlan YAML (starter 29€, pro 79€, enterprise 199€)

### Talento (landing + Schema.org)
- `VerticalLandingController.php` — talento() reescrito: 4→12 features, 4→10 FAQs, 3→4 stats
- `PageAttachmentsHooks.php` — Schema.org + meta/OG/Twitter para talento

### Homepage (6 Twig partials + SEO)
- `_seo-schema.html.twig` — highPrice 99→199, featureList 7→12
- `_hero.html.twig` — subtitle "10 verticales especializados, 11 agentes IA"
- `_features.html.twig` — "11 Agentes IA Gen 2", badge "10 verticales"
- `_stats.html.twig` — metricas verificables (10/11/80+/100%)
- `_cross-pollination.html.twig` — eyebrow "10 verticales"
- `PageAttachmentsHooks.php` — meta/OG/Twitter para frontpage via isFrontPage()

## Reglas Derivadas

- **LANDING-ELEVATION-001** (P1): Elevacion de landing pages sigue patron 3 niveles obligatorio: (1) contenido, (2) SEO/Schema.org, (3) config layer
- **METRICS-HONESTY-001** (P0): Stats y metricas SOLO datos verificables desde el codebase o documentacion del programa. NUNCA fabricar numeros de usuarios/clientes/conversiones

## Verificacion

Cada nivel debe verificarse independientemente:
1. Landing: contar features, FAQs, stats, testimonials con grep
2. Schema.org: verificar featureList count, highPrice, meta tags
3. Config: verificar FVL matrix completeness, PlanFeatures coherencia
