# Plan de Elevacion: Homepage SaaS + MetaSitios → Clase Mundial 10/10

**Codigo:** HOMEPAGE-ELEVATION-001
**Fecha:** 2026-03-21
**Autor:** Claude Opus 4.6 (arquitecto SaaS + UX + Drupal + theming + SEO + IA)
**Estado:** ESPECIFICACION TECNICA PARA IMPLEMENTACION
**Prioridad:** P0 — Impacto directo en conversion y primera impresion
**Version docs:** v157 DIRECTRICES + v144 ARQUITECTURA + v185 INDICE + v109 FLUJO
**Ultimo aprendizaje:** #208 | Ultima regla de oro: #146

**Dependencias verificadas:**
MetaSiteResolverService, HomepageDataService, MetaSitePricingService, UnifiedThemeResolverService,
AvatarDetectionService, SiteConfig, TenantThemeConfig, VerticalQuizService, MegaMenuBridgeService,
PublicSubscribeController (LEAD-MAGNET-CRM-001), VerticalLandingController (referencia 10/10)

**Directrices verificadas (42):**
CSS-VAR-ALL-COLORS-001, ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, ICON-EMOJI-001,
ICON-INTEGRITY-001, ICON-INTEGRITY-002, SCSS-001, SCSS-COMPILE-VERIFY-001, SCSS-COLORMIX-001,
SCSS-COMPILETIME-001, SCSS-ENTRY-CONSOLIDATION-001, SCSS-COMPONENT-BUILD-001, ZERO-REGION-001,
ZERO-REGION-002, ZERO-REGION-003, ROUTE-LANGPREFIX-001, TWIG-URL-RENDER-ARRAY-001,
TWIG-INCLUDE-ONLY-001, TWIG-SYNTAX-LINT-001, SLIDE-PANEL-RENDER-001, NO-HARDCODE-PRICE-001,
PRICING-4TIER-001, ANNUAL-DISCOUNT-001, ADDON-PRICING-001, MARKETING-TRUTH-001,
LANDING-CONVERSION-SCORE-001, VIDEO-HERO-001, LEAD-MAGNET-CRM-001, FUNNEL-COMPLETENESS-001,
CTA-DESTINATION-001, CASE-STUDY-PATTERN-001, OBSERVER-SCROLL-ROOT-001, INNERHTML-XSS-001,
CSRF-JS-CACHE-001, DOC-GUARD-001, IMPLEMENTATION-CHECKLIST-001, RUNTIME-VERIFY-001,
PIPELINE-E2E-001, SETUP-WIZARD-DAILY-001, TENANT-001, DOMAIN-ROUTE-CACHE-001

---

## Tabla de Contenidos (TOC)

1. [Contexto Estrategico y Objetivos](#1-contexto-estrategico-y-objetivos)
2. [Diagnostico: Auditoria Exhaustiva del Estado Actual](#2-diagnostico-auditoria-exhaustiva)
3. [Arquitectura Actual del Sistema de Homepage](#3-arquitectura-actual)
4. [Definicion de "Clase Mundial 10/10" para Homepage](#4-definicion-10-10)
5. [Plan de Implementacion por Fases](#5-plan-fases)
   - 5.1 [FASE 0: Gaps Criticos (Pain Points + Pricing Preview + Sticky CTA)](#51-fase-0)
   - 5.2 [FASE 1: Hero Upgrade (Urgencia + Trust Inline + Video)](#52-fase-1)
   - 5.3 [FASE 2: Social Proof Denso + Comparativa Ecosistema](#53-fase-2)
   - 5.4 [FASE 3: Features Grid Premium + Proposicion de Valor](#54-fase-3)
   - 5.5 [FASE 4: Mobile-First + Reveal Animations Completas](#55-fase-4)
   - 5.6 [FASE 5: MetaSitios Diferenciados por Dominio](#56-fase-5)
6. [Inventario de Archivos Afectados](#6-inventario)
7. [Especificaciones Tecnicas Detalladas](#7-especificaciones-tecnicas)
8. [Tabla de Correspondencia con Directrices](#8-tabla-correspondencia)
9. [Validadores y Salvaguardas Nuevas](#9-validadores-salvaguardas)
10. [Plan de Verificacion RUNTIME-VERIFY-001](#10-verificacion)
11. [Glosario](#11-glosario)

---

## 1. Contexto Estrategico y Objetivos {#1-contexto-estrategico-y-objetivos}

### 1.1 Contexto

La Jaraba Impact Platform ha alcanzado 10/10 en sus landing pages verticales (LANDING-ELEVATION-002,
completado 2026-03-21 con fases F0-F5). Las 9 landings comerciales cumplen los 15 criterios de
LANDING-CONVERSION-SCORE-001: hero con video autoplaying, urgencia, trust badges, pain points,
steps, features grid, comparativa, social proof con partner logos, lead magnet con pipeline CRM,
pricing preview, FAQ con Schema.org, final CTA, sticky CTA, reveal animations y tracking completo.

**Sin embargo, la homepage del SaaS y los 3 metasitios (pepejaraba.com, jarabaimpact.com,
plataformadeecosistemas.es) NO han recibido la misma elevacion.** La homepage es la primera
impresion del 80%+ del trafico y actualmente tiene una puntuacion de conversion de 5-6/10.

La homepage tiene una arquitectura bifurcada:
- **Flujo PED** (`is_ped = meta_site.group_id == 7`): AIDA optimizado para pepejaraba.com con
  variantes por audiencia (legal/b2g/empleo). Score actual: 5.5/10.
- **Flujo generico**: Para el SaaS principal y otros metasitios. Score actual: 4.9/10.

### 1.2 Diferencia entre Homepage y Landing Vertical

| Aspecto | Landing Vertical | Homepage SaaS |
|---------|-----------------|---------------|
| **Audiencia** | Conoce su sector (ya eligio) | No sabe que vertical necesita |
| **Objetivo** | Conversion directa (registro/trial) | Discovery + segmentacion + conversion |
| **Contenido** | 1 vertical, features especificas | Ecosistema completo, cross-sell |
| **Pricing** | 1 vertical con 4 tiers | Preview de "desde X EUR/mes" por vertical |
| **Social proof** | Testimonios del sector | Testimonios cross-vertical |
| **Complejidad** | Lineal (scroll down) | Hub (multiples caminos) |

### 1.3 Objetivos Medibles

| Objetivo | Metrica | Umbral actual | Objetivo |
|----------|---------|---------------|----------|
| Score de conversion | LANDING-CONVERSION-SCORE-001 | 5/10 | 10/10 |
| Primera impresion | Time to value (hero CTA visible) | ~4s | < 2s |
| Segmentacion | % visitantes que eligen vertical | ~5% | > 25% |
| Urgencia percibida | "14 dias gratis" visible | NO | SI (hero + sticky) |
| Trust verificable | Badges/logos en above-the-fold | 4 inline | 6+ con partner logos |
| Pricing transparencia | Visitante ve precios sin navegar | NO | SI (preview 3 verticales) |
| Mobile conversion | CTAs adaptados mobile-first | Parcial | Completo + sticky |
| Diferenciacion metasitio | Contenido por dominio | Solo PED/generico | 3 variantes |

### 1.4 Publicos Objetivo por MetaSitio

| Dominio | Audiencia primaria | Tono | Prioridad vertical |
|---------|--------------------|------|---------------------|
| plataformadeecosistemas.es | B2B SaaS, empresas, profesionales | Profesional, moderno | Todos (hub) |
| pepejaraba.com | Instituciones B2G, legal, FSE | Corporativo, autoridad | JarabaLex, B2G, Empleabilidad |
| jarabaimpact.com | Emprendedores, agro, comercio | Dinamico, accesible | Emprendimiento, AgroConecta, ComercioConecta |

### 1.5 Arquitectura de MetaSitios (como funciona)

```
Request: GET https://pepejaraba.com/
    │
    ├─ Nginx (nginx-metasites.conf) → /var/www/jaraba/web
    │
    ├─ PathProcessorPageContent::processInbound()
    │   ├─ Detecta "/" → llama resolveHomepage()
    │   ├─ MetaSiteResolverService::resolveFromRequest()
    │   │   ├─ Estrategia 1: Domain Access entity match
    │   │   ├─ Estrategia 2: Tenant.domain exact match
    │   │   └─ Estrategia 3: Subdomain prefix match
    │   └─ Devuelve SiteConfig.homepage_id → /page/{id}
    │
    ├─ hook_preprocess_page() inyecta:
    │   ├─ meta_site: { site_config, nav_items, group_id, tenant_name }
    │   ├─ homepage_data: { hero, features, stats, intentions, seo }
    │   ├─ ped_pricing: MetaSitePricingService::getPricingPreview()
    │   ├─ ped_urls: URL map con Url::fromRoute()
    │   ├─ verticals: Array de 9 definiciones
    │   └─ theme_settings: Cascada 5 niveles
    │
    └─ page--front.html.twig
        ├─ {% if is_ped %} → Flujo AIDA (PED)
        └─ {% else %} → Flujo generico (SaaS)
```

**Punto clave**: El mecanismo de diferenciacion por dominio ya existe. Solo necesitamos:
1. Ampliar las variantes de contenido (de 2 a 4: PED, JarabaImpact, PDE, generico)
2. Elevar ambos flujos a 10/10 incorporando los componentes que faltan

---

## 2. Diagnostico: Auditoria Exhaustiva del Estado Actual {#2-diagnostico-auditoria-exhaustiva}

### 2.1 Evaluacion por Criterio LANDING-CONVERSION-SCORE-001

**15 criterios aplicados a la homepage (NO a landings verticales)**

| # | Criterio | Flujo PED | Flujo Generico | Detalle |
|---|----------|-----------|----------------|---------|
| 1 | Hero + urgency | 7/10 | 6/10 | Falta badge "14 dias gratis" en hero. PED tiene variantes por audiencia pero sin urgencia visual. Generico no tiene urgency badge |
| 2 | Trust badges | 5/10 | 8/10 | Generico tiene trust bar inline en hero (RGPD, IA, Cifrado, Metodologia). PED NO tiene trust en hero |
| 3 | Pain points | 0/10 | 0/10 | **NO EXISTE** ninguna seccion de pain points en ningun flujo. El visitante no se identifica con un problema antes de ver la solucion |
| 4 | Solution steps | 8/10 | 8/10 | `_how-it-works.html.twig` con 3 pasos, iconos duotone, trust micro. Bien ejecutado |
| 5 | Features grid | 4/10 | 5/10 | Generico depende de datos dinamicos (HomepageDataService) que pueden estar vacios. PED no tiene features explicitas, solo 2 highlights de verticales |
| 6 | Comparison | 7/10 | 0/10 | PED tiene comparativa JarabaLex vs Aranzadi/vLex. Generico NO tiene comparativa del ecosistema |
| 7 | Social proof | 7/10 | 7/10 | 3 testimonios reales con resultados concretos. Pero sin diversidad de verticales (todos de Andalucia +ei) |
| 8 | Lead magnet | 9/10 | 9/10 | Kit de Impulso Digital con segmentacion avatar, GDPR, pipeline CRM. Excelente |
| 9 | Pricing preview | 0/10 | 0/10 | **NO EXISTE** preview de precios. El visitante debe navegar a /planes. Friccion critica |
| 10 | FAQ + Schema.org | 9/10 | 9/10 | 6 FAQs con JSON-LD FAQPage. Contenido relevante. Podria crecer a 10 preguntas |
| 11 | Final CTA | 8/10 | 8/10 | Social proof micro + badges. Falta comparativa de garantias |
| 12 | Sticky CTA | 0/10 | 0/10 | **NO EXISTE**. El parcial `_landing-sticky-cta.html.twig` existe pero NO se incluye en `page--front.html.twig` |
| 13 | Reveal animations | 6/10 | 6/10 | `reveal-element reveal-fade-up` en algunas secciones pero no en todas. Falta stagger en grids |
| 14 | Tracking | 8/10 | 8/10 | `data-track-cta` + `data-track-position` en la mayoria de CTAs. Faltan en algunos elementos |
| 15 | Mobile-first | 6/10 | 6/10 | Responsive pero sin optimizacion mobile-first verificada. Sticky CTA mobile no existe |

### 2.2 Puntuaciones Agregadas

```
Flujo PED:      ████████░░░░░░░░░░░░ 78/150 → 5.2/10
Flujo generico: ████████░░░░░░░░░░░░ 74/150 → 4.9/10
Objetivo:       ████████████████████ 150/150 → 10/10
```

### 2.3 Gaps Criticos (Score 0/10 — NO EXISTEN)

| Gap | Impacto | Solucion |
|-----|---------|----------|
| **Pain points** | El visitante no se identifica con un problema antes de ver la solucion. Sin dolor no hay urgencia | Crear `_homepage-pain-points.html.twig` con 4 problemas universales |
| **Pricing preview** | El visitante no sabe cuanto cuesta sin navegar a /planes. Barrera de conversion #1 | Crear `_homepage-pricing-preview.html.twig` con 3 verticales destacados desde MetaSitePricingService |
| **Sticky CTA** | El visitante pierde el CTA al hacer scroll. No hay recordatorio persistente | Incluir `_landing-sticky-cta.html.twig` (ya existe) en `page--front.html.twig` |

### 2.4 Gaps Importantes (Score < 7/10)

| Gap | Score | Problema | Solucion |
|-----|-------|----------|----------|
| Features grid | 4-5/10 | PED no tiene, generico depende de datos vacios | Crear features estaticas hardcoded i18n como fallback robusto |
| Trust PED hero | 5/10 | PED hero sin trust badges inline | Agregar trust bar al hero PED |
| Reveal animations | 6/10 | Inconsistente: algunos parciales sin `reveal-element` | Agregar `reveal-element reveal-fade-up` a TODOS los parciales |
| Mobile optimization | 6/10 | Sin sticky CTA mobile, sin bottom safe area | Optimizar con sticky CTA + padding-bottom |
| Comparison generico | 0/10 | Solo PED compara JarabaLex | Crear comparativa ecosistema (Jaraba vs competidores genericos) |
| Testimonials diversity | 7/10 | Solo testimonios de Andalucia +ei | Agregar testimonios de otros verticales |

### 2.5 Lo Que Ya Funciona (Score >= 8/10)

| Componente | Score | Razon |
|------------|-------|-------|
| Solution steps | 8/10 | `_how-it-works.html.twig` bien ejecutado con 3 pasos premium |
| Lead magnet | 9/10 | Segmentacion avatar, GDPR, pipeline CRM. Excelente |
| FAQ | 9/10 | 6 FAQs con Schema.org JSON-LD |
| Final CTA | 8/10 | Social proof micro + badges |
| Tracking | 8/10 | data-track-cta en la mayoria de CTAs |
| Product demo | 8/10 | 7 pestanas interactivas con mockups (dashboard, copilot, pagos, catalogo, SEO, growth) |

---

## 3. Arquitectura Actual del Sistema de Homepage {#3-arquitectura-actual}

### 3.1 Pipeline de Datos

```
1. MetaSiteResolverService::resolveFromRequest()
   └─ Resuelve dominio → group_id → SiteConfig → homepage_id

2. HomepageDataService::getHomepageData(tenant_id)
   └─ Devuelve: hero, features (FeatureCard entities), stats (StatItem), intentions (IntentionCard)

3. MetaSitePricingService::getPricingPreview(vertical)
   └─ Devuelve: tiers con features, precios, limites

4. hook_preprocess_page() inyecta:
   └─ meta_site, homepage_data, ped_pricing, ped_urls, verticals, theme_settings, mega_menu_columns

5. page--front.html.twig
   └─ 14-17 {% include %} de parciales con variables inyectadas
```

### 3.2 Bifurcacion Actual: PED vs Generico

**Flujo PED (pepejaraba.com, group_id=7):**
1. Hero PED (variantes por audiencia: legal/b2g/empleo)
2. Audience Selector
3. Stats de Impacto
4. Como Funciona
5. Vertical Highlight: JarabaLex (dark)
6. Vertical Highlight: B2G (light)
7. Product Demo
8. Testimonios
9. Trust Bar + Partners
10. Lead Magnet
11. FAQ
12. CTA Banner Final

**Flujo generico (SaaS principal):**
1. Hero (con trust bar inline)
2. Trust Bar
3. Vertical Selector (6 cards)
4. Features
5. Como Funciona
6. Stats
7. Product Demo
8. Lead Magnet
9. Cross-Pollination (8 verticales)
10. Testimonios
11. Partners
12. FAQ
13. CTA Banner Final

### 3.3 Parciales Existentes Reutilizables

| Parcial | Origen | Reutilizable en homepage |
|---------|--------|-------------------------|
| `_landing-sticky-cta.html.twig` | Landings verticales | SI — incluir directamente |
| `_landing-pain-points.html.twig` | Landings verticales | ADAPTAR — necesita version ecosistema (no vertical-especifica) |
| `_landing-pricing-preview.html.twig` | Landings verticales | ADAPTAR — necesita version multi-vertical |
| `_landing-trust-badges.html.twig` | Landings verticales | SI — incluir directamente |
| `_landing-social-proof.html.twig` | Landings verticales | ADAPTAR — necesita testimonios cross-vertical |
| `_landing-comparison.html.twig` | Landings verticales | ADAPTAR — necesita comparativa ecosistema |
| `_landing-partner-logos.html.twig` | Landings verticales | SI — incluir directamente |

### 3.4 CSS/SCSS Existente

| Archivo SCSS | Contenido | Estado |
|-------------|-----------|--------|
| `_hero-landing.scss` | Hero generico con gradient, animations | Activo, compilado en main.css |
| `_ped-metasite.scss` (579 lineas) | Identidad PED: azul+dorado, hero, cifras, CTA | Activo |
| `_how-it-works.scss` | 3 pasos con connectors | Activo |
| `_cross-pollination.scss` | Grid 8 verticales | Activo |
| `_product-demo.scss` | 7 tabs mockup | Activo |
| `_lead-magnet.scss` | Formulario Kit | Activo |
| `_vertical-selector.scss` | 6 cards detection | Activo |
| `_faq-homepage.scss` | Accordion nativo | Activo |
| `_testimonials.scss` | 3 cards con quotes | Activo |
| `scss/routes/landing.scss` | Estilos de landing verticales (pain points, pricing, etc.) | En bundle separado |

**Punto critico**: Los estilos de `_landing-pain-points`, `_landing-pricing-preview` y `_landing-sticky-cta`
estan en `scss/routes/landing.scss` que se compila como bundle separado (`css/routes/landing.css`).
Para la homepage necesitamos:
- **Opcion A**: Importar esos estilos tambien en `main.scss` (aumenta CSS global)
- **Opcion B**: Crear `scss/routes/homepage-elevation.scss` como bundle separado (recomendado)
- **Opcion C**: Adjuntar la library `route-landing` en `page--front.html.twig`

**Decision**: Opcion B — bundle separado `homepage-elevation.scss` que importa los componentes
necesarios de landing. Razon: no aumenta el CSS global y sigue el patron SCSS-COMPONENT-BUILD-001.

---

## 4. Definicion de "Clase Mundial 10/10" para Homepage {#4-definicion-10-10}

### 4.1 Los 15 Criterios Aplicados a Homepage

Para que la homepage alcance 10/10, CADA criterio debe cumplir su umbral de excelencia:

**Criterio 1 — Hero + Urgencia (10/10):**
- H1 con propuesta de valor unica (no generica)
- Badge de urgencia visible: "14 dias gratis — Sin tarjeta de credito"
- Video hero autoplaying (con fallback imagen estática)
- CTA primario que cambia segun autenticacion (registrar/dashboard)
- CTA secundario de baja friccion (ver demo, hacer quiz)
- Enlace al quiz de recomendacion de vertical
- Trust badges inline debajo de CTAs

**Criterio 2 — Trust Badges (10/10):**
- 4-6 badges verificables en above-the-fold (RGPD, IA, Cifrado, Metodologia, Servidor EU)
- Seccion de partner logos con 7+ SVGs reales
- Logos de tecnologia usada (Claude, Stripe, etc.)

**Criterio 3 — Pain Points (10/10):**
- 4 problemas universales que resonan con TODOS los visitantes
- Formato: icono + titulo + descripcion + pista de solucion
- Lenguaje emocional, no tecnico
- Conectados visualmente con la seccion de solucion

**Criterio 4 — Solution Steps (10/10):**
- 3 pasos claros con progression visual
- Conectores animados entre pasos
- Trust micro ("Sin compromiso", "9 verticales", "Resultados dia 1")
- CTA despues de los pasos

**Criterio 5 — Features Grid (10/10):**
- 8-12 features del ecosistema (no de un vertical)
- Formato rico: icono jaraba_icon + titulo + descripcion
- Organizadas por beneficio (productividad, ingresos, seguridad, IA)
- Reveal animation con stagger

**Criterio 6 — Comparativa (10/10):**
- Tabla: Jaraba vs "Herramientas sueltas" vs "Consultor/Agencia"
- 5-6 filas de diferenciacion
- Highlight visual en columna Jaraba
- Precio comparativo

**Criterio 7 — Social Proof (10/10):**
- 3-4 testimonios de DIFERENTES verticales (legal, emprendimiento, agro)
- Resultado concreto cuantificado por testimonio
- Avatar iniciales con color de vertical
- CTA a mas casos

**Criterio 8 — Lead Magnet (10/10):**
- Ya implementado a 9/10. Para 10/10:
- Agregar countdown o urgencia al formulario
- A/B test del CTA text

**Criterio 9 — Pricing Preview (10/10):**
- 3 verticales destacados con precio "desde X EUR/mes"
- Badge "Mas popular" en el segundo
- Enlace a /planes para ver todos
- Precios desde MetaSitePricingService (NO hardcoded)
- Badge "2 meses gratis" para anual

**Criterio 10 — FAQ (10/10):**
- Expandir de 6 a 10 preguntas
- Agregar preguntas sobre pricing, integraciones, migracion
- Schema.org FAQPage actualizado

**Criterio 11 — Final CTA (10/10):**
- Headline aspiracional
- Risk reversal: "14 dias gratis, sin tarjeta, cancela cuando quieras"
- Doble CTA: registrar + solicitar demo

**Criterio 12 — Sticky CTA (10/10):**
- Incluir `_landing-sticky-cta.html.twig` existente
- Configurar texto y URL desde theme_settings
- Visible tras scroll 30vh del hero

**Criterio 13 — Reveal Animations (10/10):**
- TODOS los parciales con `reveal-element reveal-fade-up`
- Stagger en grids: `--reveal-delay: {{ loop.index0 * 0.06 }}s`
- `prefers-reduced-motion` respetado

**Criterio 14 — Tracking (10/10):**
- TODOS los CTAs con `data-track-cta` + `data-track-position`
- Verificar con FUNNEL-COMPLETENESS-001

**Criterio 15 — Mobile-First (10/10):**
- Single-column en mobile
- Touch targets 48px+
- Sticky CTA adaptado mobile (padding-bottom safe area)
- No horizontal scroll
- Typography 16px+ body

### 4.2 Benchmark Visual: Stripe.com vs Homepage Jaraba

| Elemento Stripe | Equivalente Jaraba | Estado |
|----------------|-------------------|--------|
| Video hero fullscreen silencioso | Video hero autoplaying | Existe en verticales, falta en homepage |
| "Start now" con email inline | "Empezar gratis" button | Existe pero sin email inline |
| "$817B processed in 2023" | "+100M EUR gestionados" | Existe en stats |
| 4 pricing cards comparativas | 0 cards en homepage | **FALTA** |
| 50+ logos clientes | 0 logos en homepage generico | **FALTA** (PED tiene algunos) |
| SOC 2, PCI DSS badges | RGPD, Cifrado, Servidor EU | Existe parcialmente |
| Sticky nav con CTA | 0 sticky CTA | **FALTA** |
| Animaciones scroll suaves | Parciales, inconsistentes | Mejorar |
| "Built for developers" comparison | 0 comparativa ecosistema | **FALTA** |
| Mobile-first bottom CTA | 0 mobile sticky | **FALTA** |

---

## 5. Plan de Implementacion por Fases {#5-plan-fases}

### Principio rector

Cada fase es autonoma y entregable. Al completar cada fase, la homepage es mejor
que antes. No hay dependencias circulares entre fases.

### Resumen de fases

| Fase | Nombre | Criterios que cierra | Score esperado |
|------|--------|---------------------|----------------|
| F0 | Gaps criticos | 3 (pain points), 9 (pricing), 12 (sticky CTA) | 6.5/10 |
| F1 | Hero upgrade | 1 (hero urgencia), 2 (trust badges) | 7.5/10 |
| F2 | Social proof + comparativa | 6 (comparison), 7 (social proof) | 8.5/10 |
| F3 | Features premium | 5 (features grid), 10 (FAQ expandido) | 9.0/10 |
| F4 | Mobile-first + animations | 13 (reveal), 14 (tracking), 15 (mobile) | 9.5/10 |
| F5 | MetaSitios diferenciados | Personalización PED/JarabaImpact/PDE | 10/10 |

---

### 5.1 FASE 0: Gaps Criticos {#51-fase-0}

**Objetivo:** Cerrar los 3 criterios con score 0/10.
**Impacto:** De 5/10 a 6.5/10 (+30% conversion estimada)

#### 5.1.1 Componente: Homepage Pain Points

**Archivo nuevo:** `templates/partials/_homepage-pain-points.html.twig`

**Justificacion:** NO reutilizar `_landing-pain-points.html.twig` directamente porque:
- La version de landing espera `pain_points` como array desde el controller (vertical-especifica)
- La homepage necesita problemas universales que resonan con TODOS los visitantes
- La homepage no tiene controller propio (datos via preprocess)
- Crear parcial dedicado permite personalizar copy sin afectar landings

**Contenido (4 pain points universales):**

```
1. "Herramientas desconectadas"
   → "Usas 5-10 herramientas para gestionar tu negocio. Ninguna habla con las demas."
   → Icono: ui/puzzle

2. "Perdiendo tiempo en tareas repetitivas"
   → "Copiar datos entre Excel, email y WhatsApp. Horas que no facturas."
   → Icono: ui/clock

3. "Sin presencia digital profesional"
   → "Tu web es de 2015 o directamente no tienes. Tus clientes te buscan en Google y no te encuentran."
   → Icono: ui/globe

4. "Pagando de mas por menos"
   → "Shopify + Mailchimp + Calendly + Stripe + hosting = 200+ EUR/mes. Y aun no tienes IA."
   → Icono: commerce/wallet
```

**Estructura HTML:**
```twig
<section class="homepage-pain-points reveal-element reveal-fade-up" aria-labelledby="pain-points-title">
  <div class="homepage-pain-points__container">
    <header>
      <span class="homepage-pain-points__eyebrow">{% trans %}¿Te suena?{% endtrans %}</span>
      <h2 id="pain-points-title">{% trans %}Problemas que resolvemos cada dia{% endtrans %}</h2>
    </header>
    <div class="homepage-pain-points__grid" role="list">
      {% for item in pain_points %}
        <div class="homepage-pain-points__card" role="listitem" style="--reveal-delay: {{ loop.index0 * 0.08 }}s;">
          {{ jaraba_icon(item.icon_cat, item.icon_name, { variant: 'duotone', size: '36px', color: item.color }) }}
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
        </div>
      {% endfor %}
    </div>
  </div>
</section>
```

**SCSS:** Nuevos estilos en `scss/components/_homepage-pain-points.scss`:
- Grid 4 columnas desktop, 2 tablet, 1 mobile
- Tarjetas con borde sutil, hover elevation
- Iconos con fondo circular semi-transparente
- Colores via `var(--ej-*)` con fallbacks

**Directrices:**
- i18n: TODOS los textos con `{% trans %}` bloque
- ICON-CONVENTION-001: `jaraba_icon()` con variant duotone
- CSS-VAR-ALL-COLORS-001: Solo variables CSS, cero hex
- BEM: `.homepage-pain-points__*`

**Posicion en page--front.html.twig:**
- Flujo PED: despues de Hero, antes de Audience Selector
- Flujo generico: despues de Trust Bar, antes de Vertical Selector

#### 5.1.2 Componente: Homepage Pricing Preview

**Archivo nuevo:** `templates/partials/_homepage-pricing-preview.html.twig`

**Justificacion:** NO reutilizar `_landing-pricing-preview.html.twig` porque:
- La version de landing muestra 4 tiers de UN vertical
- La homepage necesita mostrar 3 verticales con su precio "desde"
- El objetivo es "discovery" (descubrir que existe pricing accesible), no "decision" (elegir tier)

**Contenido (3 verticales destacados con pricing "desde"):**

```
1. JarabaLex — "Desde {{ ped_pricing.jarabalex.starter_price }} EUR/mes"
   → "Busqueda legal IA, expedientes, facturacion. Todo en uno."
   → CTA: "Ver planes JarabaLex"
   → Badge: "El mas popular"

2. ComercioConecta — "Desde {{ ped_pricing.comercioconecta.starter_price }} EUR/mes"
   → "Tu tienda online con catálogo, pagos, copiloto IA."
   → CTA: "Ver planes ComercioConecta"

3. Emprendimiento — "Desde {{ ped_pricing.emprendimiento.starter_price }} EUR/mes"
   → "Lean Canvas, validacion, copiloto startup."
   → CTA: "Ver planes Emprendimiento"
```

**Datos:** Precios SIEMPRE desde `ped_pricing` (NO-HARDCODE-PRICE-001).
Los precios se inyectan via `hook_preprocess_page()` desde MetaSitePricingService.

**Estructura HTML:**
```twig
<section class="homepage-pricing reveal-element reveal-fade-up" aria-labelledby="pricing-preview-title">
  <div class="homepage-pricing__container">
    <header>
      <span class="homepage-pricing__eyebrow">{% trans %}Precios transparentes{% endtrans %}</span>
      <h2 id="pricing-preview-title">{% trans %}Empieza desde menos de lo que cuesta un cafe al dia{% endtrans %}</h2>
      <p>{% trans %}14 dias gratis en todos los planes. Sin tarjeta de credito.{% endtrans %}</p>
    </header>
    <div class="homepage-pricing__cards">
      {# 3 cards con datos desde ped_pricing #}
    </div>
    <div class="homepage-pricing__footer">
      <a href="{{ path('ecosistema_jaraba_core.pricing_hub') }}">
        {% trans %}Ver todos los planes y verticales{% endtrans %} →
      </a>
      <span class="homepage-pricing__badge">
        {% trans %}Ahorra 2 meses con el plan anual{% endtrans %}
      </span>
    </div>
  </div>
</section>
```

**SCSS:** `scss/components/_homepage-pricing.scss`
- 3 cards en fila desktop, stack mobile
- Card destacada (la del medio) con borde y badge "Mas popular"
- Precio grande con "desde" pequeño encima
- CTA por card
- Badge "2 meses gratis" para anual

**Regla MARKETING-TRUTH-001:** El texto "14 dias gratis" DEBE coincidir con el trial period
configurado en Stripe. Si cambia en Stripe, debe cambiar aqui.

**Regla NO-HARDCODE-PRICE-001:** NINGUN precio en EUR hardcoded en el template.
Usar `{{ ped_pricing.{vertical}.starter_price|default(29) }}` con fallback numerico.

#### 5.1.3 Componente: Sticky CTA en Homepage

**Archivo existente:** `templates/partials/_landing-sticky-cta.html.twig` (ya creado en F1 de landings)

**Accion:** Incluir en `page--front.html.twig` SIN crear parcial nuevo.

```twig
{# STICKY CTA — Conversion persistente al hacer scroll #}
{% include '@ecosistema_jaraba_theme/partials/_landing-sticky-cta.html.twig' with {
  sticky_cta: {
    text: theme_settings.homepage_sticky_text|default('14 dias gratis — Sin tarjeta de credito'|trans),
    cta: {
      text: logged_in ? 'Ir al dashboard'|trans : 'Empezar gratis'|trans,
      url: logged_in ? (ped_urls.dashboard|default('/user')) : (ped_urls.register|default(path('user.register')))
    }
  },
  vertical_key: 'homepage'
} only %}
```

**JS necesario:** `landing-sticky-cta.js` (ya existe) — usa IntersectionObserver para
mostrar/ocultar. Necesitamos verificar que la library esta adjunta. Si no, adjuntar:
```twig
{{ attach_library('ecosistema_jaraba_theme/landing-sticky-cta') }}
```

**CSS:** Ya incluido en `scss/routes/landing.scss`. Para la homepage, como usamos bundle
separado (decision seccion 3.4), importaremos los estilos del sticky en `homepage-elevation.scss`.

**Theme settings nuevos:** Agregar en `ecosistema_jaraba_theme.theme` form_alter:
- `homepage_sticky_text` (textfield): Texto de urgencia del sticky CTA
- `homepage_sticky_enabled` (checkbox): Activar/desactivar sticky en homepage

#### 5.1.4 Orden de Secciones Propuesto (Post-F0)

**Flujo generico actualizado:**
1. Hero (existente)
2. Trust Bar (existente)
3. **Pain Points (NUEVO)**
4. Vertical Selector (existente)
5. Solution Steps / Como Funciona (existente)
6. Features (existente)
7. Product Demo (existente)
8. **Pricing Preview (NUEVO)**
9. Lead Magnet (existente)
10. Cross-Pollination (existente)
11. Testimonios (existente)
12. Partners (existente)
13. FAQ (existente)
14. CTA Banner Final (existente)
15. **Sticky CTA (NUEVO)**

**Flujo PED actualizado:**
1. Hero PED (existente)
2. **Pain Points (NUEVO, adaptado PED)**
3. Audience Selector (existente)
4. Stats (existente)
5. Como Funciona (existente)
6. Vertical Highlight JarabaLex (existente)
7. Vertical Highlight B2G (existente)
8. Product Demo (existente)
9. **Pricing Preview (NUEVO, adaptado PED)**
10. Testimonios (existente)
11. Trust Bar + Partners (existente)
12. Lead Magnet (existente)
13. FAQ (existente)
14. CTA Banner Final (existente)
15. **Sticky CTA (NUEVO)**

---

### 5.2 FASE 1: Hero Upgrade {#52-fase-1}

**Objetivo:** Criterios 1 (hero+urgencia) y 2 (trust badges) a 10/10.
**Impacto:** De 6.5/10 a 7.5/10

#### 5.2.1 Badge de Urgencia en Hero

**Archivo:** `templates/partials/_hero.html.twig` (modificar)

Agregar en AMBOS flujos (PED y generico), justo encima del H1:

```twig
<span class="hero-landing__urgency">
  {{ jaraba_icon('ui', 'clock', { variant: 'duotone', size: '16px', color: 'verde-innovacion' }) }}
  {% trans %}14 dias gratis — Sin tarjeta de credito{% endtrans %}
</span>
```

**Nota:** Este patron ya existe en `_landing-hero.html.twig` de las landings verticales.
Reutilizamos el mismo patron CSS (`.landing-hero__urgency`).

#### 5.2.2 Trust Badges en Hero PED

El hero PED actualmente NO tiene trust badges. Agregar despues de `.ped-hero__actions`:

```twig
<div class="ped-hero__trust-bar" aria-label="{% trans %}Certificaciones{% endtrans %}">
  <span class="ped-hero__trust-badge">
    {# SVG shield + checkmark #}
    {% trans %}RGPD{% endtrans %}
  </span>
  <span class="ped-hero__trust-badge">
    {# SVG AI sparkles #}
    {% trans %}11 Agentes IA{% endtrans %}
  </span>
  <span class="ped-hero__trust-badge">
    {# SVG lock #}
    {% trans %}Servidor EU{% endtrans %}
  </span>
  <span class="ped-hero__trust-badge">
    {# SVG layers #}
    {% trans %}10 Verticales{% endtrans %}
  </span>
</div>
```

**SCSS:** Reutilizar estilos de `.hero-landing__trust-bar` del flujo generico, adaptado a paleta PED.

#### 5.2.3 Video Hero en Homepage (Opcional)

El VIDEO-HERO-001 ya esta implementado en 9 verticales con `landing-hero-video.js`.
Para la homepage, considerar un video "overview" del ecosistema (no vertical-especifico).

**Opciones:**
- A: Video compuesto con fragmentos de los 9 verticales (recomendado)
- B: Animacion CSS loop con gradientes (mas ligero, fallback)
- C: Hero estatico con imagen WebP de alta calidad

**Decision:** Dejamos para F5 si se genera un video con Veo. En F1, mejorar
el gradiente animado existente con mas profundidad visual.

---

### 5.3 FASE 2: Social Proof + Comparativa Ecosistema {#53-fase-2}

**Objetivo:** Criterios 6 (comparison) y 7 (social proof) a 10/10.
**Impacto:** De 7.5/10 a 8.5/10

#### 5.3.1 Comparativa Ecosistema

**Archivo nuevo:** `templates/partials/_homepage-comparison.html.twig`

**Contenido:** Tabla 3 columnas: Jaraba vs "Herramientas sueltas" vs "Contratar agencia"

| Caracteristica | Jaraba | Herramientas sueltas | Agencia/Consultor |
|---------------|--------|---------------------|-------------------|
| CRM + Facturacion + Web | Una plataforma | 3-5 tools (Mailchimp + Shopify + Calendly...) | Dependencia total |
| Copiloto IA sector | 11 agentes especializados | ChatGPT generico | Consultor humano (caro) |
| Precio mensual | Desde X EUR | 200+ EUR combinado | 500-2000+ EUR |
| Tiempo de setup | 30 segundos | Dias/semanas | Semanas/meses |
| Soporte | Email 24h + Copiloto | Cada tool por separado | Horario limitado |
| Escalabilidad | 10 verticales, activa lo que necesites | Migrar a otra tool | Renegociar contrato |

**Formato visual:** Tabla responsive que se convierte en cards apiladas en mobile.
Columna Jaraba destacada con borde `var(--ej-color-primary)` y badge "Recomendado".

**SCSS:** `scss/components/_homepage-comparison.scss`

#### 5.3.2 Testimonios Cross-Vertical

**Archivo:** `templates/partials/_testimonials.html.twig` (modificar)

**Problema actual:** Los 3 testimonios son todos de Andalucia +ei (programa de emprendimiento).
Esto limita la resonancia para visitantes de otros sectores.

**Solucion:** Agregar variable `homepage_testimonials` en `hook_preprocess_page()` con
testimonios de diferentes verticales. El template recibe la variable y la usa si existe.

**Testimonios propuestos (1 por vertical):**
1. Marcela Calabia — Emprendimiento (existente, mantener)
2. Testimonio JarabaLex — Despacho que ahorro X horas/mes con busqueda IA
3. Testimonio AgroConecta — Productor que vendio online por primera vez
4. Testimonio ComercioConecta — Comercio local que digitalizo su tienda

**Nota:** Si no hay testimonios reales de otros verticales, crear placeholders
con nota "Caso de exito en preparacion" y mantener los 3 actuales.

#### 5.3.3 Partner Logos en Flujo Generico

**Archivo existente:** `_landing-partner-logos.html.twig`

**Accion:** Incluir en el flujo generico (actualmente solo PED tiene `_partners-institucional.html.twig`).

```twig
{% include '@ecosistema_jaraba_theme/partials/_landing-partner-logos.html.twig' with {
  logos: homepage_partner_logos|default([]),
  title: 'Tecnologia de confianza'|trans,
} only %}
```

**Logos sugeridos (7 SVGs):**
Claude (Anthropic), Stripe, Drupal, MariaDB, Redis, IONOS, UE (fondos europeos)

---

### 5.4 FASE 3: Features Grid Premium {#54-fase-3}

**Objetivo:** Criterios 5 (features) y 10 (FAQ expandido) a 10/10.
**Impacto:** De 8.5/10 a 9.0/10

#### 5.4.1 Features Grid del Ecosistema

**Archivo nuevo:** `templates/partials/_homepage-features.html.twig`

**Justificacion:** El parcial `_features.html.twig` actual depende de datos dinamicos de
`HomepageDataService` (FeatureCard entities). Si no hay entities, la seccion esta vacia.
Necesitamos un fallback robusto con features estaticas i18n que funcione SIEMPRE.

**12 features universales del ecosistema:**

```
1. Panel inteligente — Tu negocio en una pantalla
2. Copiloto IA — 11 agentes especializados por sector
3. Firma digital — Validez legal europea integrada
4. Facturacion y cobros — Stripe + Bizum + SEPA sin comisiones ocultas
5. Catalogo digital — Tienda con QR, reservas y pagos online
6. Busqueda legal IA — 8 fuentes oficiales, jurisprudencia al instante
7. CRM integrado — Contactos, oportunidades y pipeline de ventas
8. Editor visual — Crea tu web sin programar (GrapesJS)
9. SEO automatico — Schema.org, hreflang, datos estructurados
10. Multi-idioma — ES, CA, EN con prefijo de idioma automatico
11. Seguridad — RGPD, cifrado, aislamiento por tenant, servidor EU
12. Escalabilidad — 10 verticales, activa lo que necesites sin migrar
```

**Formato:** Grid 4x3 desktop, 2x6 tablet, 1x12 mobile. Cada feature:
icono jaraba_icon + titulo + descripcion corta.

#### 5.4.2 FAQ Expandido

**Archivo:** `templates/partials/_faq-homepage.html.twig` (modificar)

Expandir de 6 a 10 preguntas. Agregar:
7. "¿Cuanto cuesta? ¿Hay plan gratuito?"
8. "¿Puedo migrar mis datos desde otra plataforma?"
9. "¿Funciona para mi sector especifico?"
10. "¿Que integraciones tiene?"

Actualizar JSON-LD FAQPage con las 4 preguntas nuevas.

---

### 5.5 FASE 4: Mobile-First + Reveal Animations {#55-fase-4}

**Objetivo:** Criterios 13 (animations), 14 (tracking), 15 (mobile) a 10/10.
**Impacto:** De 9.0/10 a 9.5/10

#### 5.5.1 Reveal Animations Completas

**Accion:** Agregar `reveal-element reveal-fade-up` a TODOS los parciales de homepage que
no lo tengan actualmente:
- `_hero.html.twig` — NO (hero debe estar visible al cargar)
- `_homepage-pain-points.html.twig` — SI (nuevo, incluido)
- `_vertical-selector.html.twig` — YA TIENE
- `_how-it-works.html.twig` — YA TIENE
- `_features.html.twig` / `_homepage-features.html.twig` — SI
- `_product-demo.html.twig` — SI (agregar)
- `_homepage-pricing-preview.html.twig` — SI (nuevo, incluido)
- `_homepage-comparison.html.twig` — SI (nuevo, incluido)
- `_lead-magnet.html.twig` — SI (agregar)
- `_cross-pollination.html.twig` — YA TIENE
- `_testimonials.html.twig` — YA TIENE
- `_faq-homepage.html.twig` — SI (agregar)
- `_cta-banner-final.html.twig` — SI (agregar)

**Stagger en grids:** Agregar `style="--reveal-delay: {{ loop.index0 * 0.06 }}s;"` a items de:
- Pain points cards
- Features grid items
- Pricing cards
- FAQ items

#### 5.5.2 Tracking Completo

**Verificacion:** Ejecutar `php scripts/validation/validate-funnel-tracking.php` para detectar
CTAs sin `data-track-cta`. Agregar atributos faltantes.

**Nuevos eventos a trackear:**
- `pain_points_read` — Scroll a seccion pain points
- `pricing_preview_click` — Click en card de pricing preview
- `comparison_view` — Scroll a seccion comparativa
- `sticky_cta_click` — Click en sticky CTA

#### 5.5.3 Mobile-First Optimization

**Verificaciones:**
1. Touch targets: TODOS los botones >= 48px height en mobile
2. Font size: body >= 16px (evitar zoom iOS)
3. Sticky CTA: padding-bottom safe area (env(safe-area-inset-bottom))
4. No horizontal scroll en ninguna seccion
5. Images: width 100%, max-width, aspect-ratio reservado (evitar CLS)
6. Grid→stack: todos los grids → single column en < 640px

---

### 5.6 FASE 5: MetaSitios Diferenciados {#56-fase-5}

**Objetivo:** Personalizar homepage por dominio para 10/10 completo.
**Impacto:** De 9.5/10 a 10/10

#### 5.6.1 Arquitectura de Variantes

Actualmente la bifurcacion es binaria: `is_ped` (true/false).
Necesitamos ampliar a 4 variantes:

```twig
{% set is_ped = meta_site.group_id|default(0) == 7 %}
{% set is_jaraba_impact = meta_site.group_id|default(0) == X %}
{% set is_pde = meta_site.group_id|default(0) == Y %}
{% set homepage_variant = is_ped ? 'ped' : (is_jaraba_impact ? 'impact' : (is_pde ? 'pde' : 'generic')) %}
```

**Alternativa (mas escalable):** Almacenar `homepage_variant` en SiteConfig entity.
Cada SiteConfig tiene un campo `homepage_variant` (string: ped/impact/pde/generic).
Esto se inyecta en preprocess y el template usa:

```twig
{% if homepage_variant == 'ped' %}
  {# Flujo PED: AIDA corporativo #}
{% elseif homepage_variant == 'impact' %}
  {# Flujo Impact: emprendimiento + comercio + agro #}
{% elseif homepage_variant == 'pde' %}
  {# Flujo PDE: hub SaaS principal #}
{% else %}
  {# Flujo generico #}
{% endif %}
```

#### 5.6.2 Contenido por Variante

| Seccion | PED (pepejaraba.com) | Impact (jarabaimpact.com) | PDE (plataformadeecosistemas.es) |
|---------|---------------------|--------------------------|----------------------------------|
| Hero headline | "Empleo, justicia y negocio digital" | "Digitaliza tu negocio con IA" | "El ecosistema digital completo" |
| Pain points | Institucionales (FSE, justificacion) | Comerciales (herramientas sueltas) | Universales (los 4 actuales) |
| Verticales destacados | JarabaLex + B2G | Emprendimiento + Comercio + Agro | Todos (selector 6 cards) |
| Pricing preview | JarabaLex + Empleabilidad + B2G | ComercioConecta + AgroConecta + Formacion | Los 3 mas populares (dinamico) |
| Comparativa | Jaraba vs Aranzadi/vLex | Jaraba vs Shopify + herramientas | Jaraba vs agencia/consultor |
| Testimonios | B2G + Legal | Emprendimiento + Comercio | Cross-vertical |
| Tono visual | Azul corporativo + dorado | Naranja impulso + verde | Gradiente ecosistema |

#### 5.6.3 Video Hero por MetaSitio (Veo AI)

Para cada metasitio, generar un video hero con Veo AI:
- **PED:** Institucional, despachos, aulas (tono corporativo)
- **Impact:** Tiendas, campos, oficinas startup (tono dinamico)
- **PDE:** Collage de todos los verticales (tono hub)

---

## 6. Inventario de Archivos Afectados {#6-inventario}

### 6.1 Archivos a Crear

| Archivo | Tipo | Fase | Proposito |
|---------|------|------|----------|
| `templates/partials/_homepage-pain-points.html.twig` | Twig | F0 | 4 pain points universales del ecosistema |
| `templates/partials/_homepage-pricing-preview.html.twig` | Twig | F0 | Preview de precios 3 verticales desde MetaSitePricingService |
| `templates/partials/_homepage-comparison.html.twig` | Twig | F2 | Comparativa ecosistema vs alternativas |
| `templates/partials/_homepage-features.html.twig` | Twig | F3 | 12 features del ecosistema con fallback estatico i18n |
| `scss/components/_homepage-pain-points.scss` | SCSS | F0 | Estilos pain points grid |
| `scss/components/_homepage-pricing.scss` | SCSS | F0 | Estilos pricing preview cards |
| `scss/components/_homepage-comparison.scss` | SCSS | F2 | Estilos tabla comparativa responsive |
| `scss/components/_homepage-features.scss` | SCSS | F3 | Estilos features grid 4x3 |
| `scss/bundles/homepage-elevation.scss` | SCSS | F0 | Bundle CSS para estilos nuevos de homepage |
| `css/bundles/homepage-elevation.css` | CSS | F0 | Output compilado del bundle |
| `scripts/validation/validate-homepage-completeness.php` | PHP | F4 | Validador HOMEPAGE-COMPLETENESS-001 |
| `scripts/validation/validate-homepage-pricing-coherence.php` | PHP | F4 | Validador HOMEPAGE-PRICING-COHERENCE-001 |

### 6.2 Archivos a Modificar

| Archivo | Tipo | Fase | Cambio |
|---------|------|------|--------|
| `templates/page--front.html.twig` | Twig | F0-F5 | Incluir nuevos parciales, reorganizar orden, ampliar variantes |
| `templates/partials/_hero.html.twig` | Twig | F1 | Badge urgencia, trust PED |
| `templates/partials/_testimonials.html.twig` | Twig | F2 | Soporte para variable `homepage_testimonials` con fallback |
| `templates/partials/_faq-homepage.html.twig` | Twig | F3 | Expandir a 10 preguntas, actualizar JSON-LD |
| `templates/partials/_lead-magnet.html.twig` | Twig | F4 | Agregar reveal-element |
| `templates/partials/_product-demo.html.twig` | Twig | F4 | Agregar reveal-element |
| `templates/partials/_cta-banner-final.html.twig` | Twig | F4 | Agregar reveal-element |
| `ecosistema_jaraba_theme.theme` | PHP | F0 | Theme settings: homepage_sticky_text, homepage_sticky_enabled |
| `ecosistema_jaraba_theme.theme` | PHP | F0 | preprocess_page: inyectar `homepage_pain_points`, verificar `ped_pricing` |
| `ecosistema_jaraba_theme.libraries.yml` | YAML | F0 | Registrar library `bundle-homepage-elevation` |
| `package.json` | JSON | F0 | Agregar build:homepage al pipeline de build |
| `scss/main.scss` | SCSS | F0-F3 | @use de nuevos componentes SCSS |
| `scripts/validation/validate-all.sh` | Bash | F4 | Registrar nuevos validadores |

### 6.3 Archivos NO Afectados (ya correctos)

| Archivo | Razon |
|---------|-------|
| `_header.html.twig` | Megamenu segmenta correctamente |
| `_footer.html.twig` | Configurable desde UI, cumple directrices |
| `_copilot-fab.html.twig` | FAB de ventas con contexto avatar |
| `_whatsapp-fab.html.twig` | WhatsApp sticky mobile |
| `_how-it-works.html.twig` | 8/10, no necesita cambios en F0-F4 |
| `_landing-sticky-cta.html.twig` | Reutilizable directamente |
| `_seo-schema.html.twig` | Schema.org correcto |
| MetaSiteResolverService | Arquitectura correcta, no necesita cambios |
| HomepageDataService | Provee datos dinamicos, mantener |
| MetaSitePricingService | Fuente de precios, no modificar |

---

## 7. Especificaciones Tecnicas Detalladas {#7-especificaciones-tecnicas}

### 7.1 Bundle SCSS: homepage-elevation

**Archivo:** `scss/bundles/homepage-elevation.scss`
```scss
// Homepage Elevation Bundle — HOMEPAGE-ELEVATION-001
// Estilos para las secciones nuevas de la homepage 10/10
// Compilado: npm run build:homepage → css/bundles/homepage-elevation.css

@use '../variables' as *;
@use '../components/homepage-pain-points';
@use '../components/homepage-pricing';
@use '../components/homepage-comparison';   // F2
@use '../components/homepage-features';     // F3
```

**Library en .libraries.yml:**
```yaml
bundle-homepage-elevation:
  css:
    theme:
      css/bundles/homepage-elevation.css: {}
```

**Build script en package.json:**
```json
"build:homepage": "sass scss/bundles/homepage-elevation.scss css/bundles/homepage-elevation.css --style=compressed --no-source-map"
```

Agregar a `build` script:
```json
"build": "npm run build:css && npm run build:admin && npm run build:routes && npm run build:bundles && npm run build:components && npm run build:homepage && npm run build:js"
```

### 7.2 Theme Settings Nuevos

**Archivo:** `ecosistema_jaraba_theme.theme`, dentro de `form_system_theme_settings_alter()`

```php
// Homepage Sticky CTA
$form['homepage_conversion'] = [
  '#type' => 'details',
  '#title' => t('Homepage — Conversion'),
  '#group' => 'ecosistema_tabs',
];
$form['homepage_conversion']['homepage_sticky_enabled'] = [
  '#type' => 'checkbox',
  '#title' => t('Activar Sticky CTA en homepage'),
  '#default_value' => theme_get_setting('homepage_sticky_enabled') ?? TRUE,
];
$form['homepage_conversion']['homepage_sticky_text'] = [
  '#type' => 'textfield',
  '#title' => t('Texto de urgencia del Sticky CTA'),
  '#default_value' => theme_get_setting('homepage_sticky_text') ?? '14 dias gratis — Sin tarjeta de credito',
  '#states' => [
    'visible' => [':input[name="homepage_sticky_enabled"]' => ['checked' => TRUE]],
  ],
];
```

**Directriz:** Estos campos permiten al admin modificar el texto del sticky CTA
sin tocar codigo. Cumple con el requisito de "valores configurables desde la UI de Drupal".

### 7.3 Preprocess Hook Actualizaciones

**Archivo:** `ecosistema_jaraba_theme.theme`, funcion `ecosistema_jaraba_theme_preprocess_page()`

```php
// Inyectar datos para secciones nuevas de homepage
if ($is_front) {
  // Pain points (estaticos i18n)
  $variables['homepage_pain_points'] = [
    ['icon_cat' => 'ui', 'icon_name' => 'puzzle', 'color' => 'naranja-impulso',
     'title' => t('Herramientas desconectadas'),
     'description' => t('Usas 5-10 herramientas para gestionar tu negocio. Ninguna habla con las demas.')],
    // ... 3 mas
  ];

  // Pricing preview (desde MetaSitePricingService)
  // $ped_pricing ya se inyecta — verificar que incluye starter_price
  // para jarabalex, comercioconecta, emprendimiento

  // Homepage variant para F5
  $variables['homepage_variant'] = $meta_site['site_config']?->get('homepage_variant') ?? 'generic';
}
```

**Directriz ZERO-REGION-001:** Toda variable via `hook_preprocess_page()`.
El controller NO devuelve datos para la homepage.

### 7.4 CSS Responsive Breakpoints

Siguiendo el sistema existente del tema:

```scss
// Breakpoints (ya definidos en _variables.scss)
$breakpoint-sm: 640px;   // Mobile landscape
$breakpoint-md: 768px;   // Tablet
$breakpoint-lg: 1024px;  // Desktop
$breakpoint-xl: 1280px;  // Wide

// Uso en componentes nuevos:
.homepage-pain-points__grid {
  display: grid;
  grid-template-columns: 1fr;              // Mobile: 1 columna
  gap: var(--ej-spacing-lg, 24px);

  @media (min-width: $breakpoint-md) {
    grid-template-columns: repeat(2, 1fr); // Tablet: 2 columnas
  }
  @media (min-width: $breakpoint-lg) {
    grid-template-columns: repeat(4, 1fr); // Desktop: 4 columnas
  }
}
```

### 7.5 Reveal Animation Pattern

Patron estandar para TODAS las secciones nuevas:

```twig
<section class="homepage-{section} reveal-element reveal-fade-up" ...>
```

En grids con items:
```twig
{% for item in items %}
  <div class="homepage-{section}__card reveal-element reveal-fade-up"
       style="--reveal-delay: {{ loop.index0 * 0.06 }}s;">
```

El JS `scroll-animations.js` (ya adjunto en `page--front.html.twig`) detecta
`.reveal-element` via IntersectionObserver y agrega clase `.is-revealed`.

**Accesibilidad:** `prefers-reduced-motion: reduce` desactiva animaciones
(ya implementado en el CSS existente).

---

## 8. Tabla de Correspondencia con Directrices {#8-tabla-correspondencia}

| Directriz | Aplicacion en este plan | Verificacion |
|-----------|------------------------|--------------|
| CSS-VAR-ALL-COLORS-001 | TODOS los colores en SCSS via `var(--ej-*, fallback)`. Cero hex hardcoded | `grep -r '#[0-9a-fA-F]{3,6}' scss/components/_homepage-*` debe dar 0 |
| ICON-CONVENTION-001 | TODOS los iconos via `jaraba_icon('cat', 'name', {variant: 'duotone'})` | `validate-icon-references.php` |
| ICON-DUOTONE-001 | Variante duotone por defecto en TODOS los iconos | Revision manual |
| ICON-COLOR-001 | Solo colores de paleta: azul-corporativo, naranja-impulso, verde-innovacion, white, neutral | Revision manual |
| SCSS-001 | `@use '../variables' as *;` en CADA parcial SCSS nuevo | `validate-scss-variables.php` |
| SCSS-COMPILE-VERIFY-001 | Tras CADA edicion SCSS, verificar timestamp CSS > SCSS | `validate-compiled-assets.php` |
| SCSS-COLORMIX-001 | Usar `color-mix(in srgb, ...)` en vez de `rgba()` | Revision manual |
| SCSS-COMPILETIME-001 | Variables en `color.scale/adjust/change` DEBEN ser hex, NUNCA `var()` | Compilacion sin error |
| SCSS-COMPONENT-BUILD-001 | Bundle `homepage-elevation.css` en pipeline `npm run build` | `package.json` verificado |
| ZERO-REGION-001 | Variables via `hook_preprocess_page()`, NUNCA controller | Revision codigo |
| ZERO-REGION-003 | `#attached` en preprocess, NO en controller | Revision codigo |
| ROUTE-LANGPREFIX-001 | URLs via `path()` o `{{ ped_urls.x }}`. NUNCA hardcoded | `validate-twig-langprefix.php` |
| TWIG-INCLUDE-ONLY-001 | TODOS los `{% include %}` con keyword `only` | `validate-twig-include-only.php` |
| TWIG-SYNTAX-LINT-001 | Sin dobles comas, sin `{#` anidado | `validate-twig-syntax.php` pre-commit |
| NO-HARDCODE-PRICE-001 | Precios desde `ped_pricing`, NUNCA EUR en template | `validate-no-hardcoded-prices.php` |
| PRICING-4TIER-001 | 4 tiers (Free/Starter/Professional/Enterprise) | `validate-pricing-tiers.php` |
| ANNUAL-DISCOUNT-001 | "2 meses gratis" = 20% descuento anual | `validate-pricing-tiers.php` CHECK 3 |
| MARKETING-TRUTH-001 | "14 dias gratis" DEBE coincidir con Stripe trial | `validate-marketing-truth.php` |
| LANDING-CONVERSION-SCORE-001 | 15 criterios cumplidos al 10/10 | `validate-homepage-completeness.php` (NUEVO) |
| FUNNEL-COMPLETENESS-001 | TODOS los CTAs con `data-track-cta` + `data-track-position` | `validate-funnel-tracking.php` |
| CTA-DESTINATION-001 | TODOS los CTAs apuntan a rutas existentes | `validate-cta-destinations.php` |
| VIDEO-HERO-001 | Video con IntersectionObserver + prefers-reduced-motion + saveData | Revision JS |
| LEAD-MAGNET-CRM-001 | Lead magnet crea CRM Contact + Opportunity | `validate-lead-magnet-crm.php` |
| OBSERVER-SCROLL-ROOT-001 | IntersectionObserver en sticky CTA | Revision JS |
| DOC-GUARD-001 | Master docs con Edit incremental, NO Write | Pre-commit hook |
| IMPLEMENTATION-CHECKLIST-001 | Checklist complitud + integridad + consistencia + coherencia | Este documento |
| RUNTIME-VERIFY-001 | 5 dependencias runtime verificadas | Seccion 10 |
| PIPELINE-E2E-001 | 4 capas L1-L4 verificadas | Seccion 10 |
| DOMAIN-ROUTE-CACHE-001 | Cada hostname tiene Domain entity | Ya configurado |
| TENANT-001 | Queries filtran por tenant | N/A (homepage es publica) |

---

## 9. Validadores y Salvaguardas Nuevas {#9-validadores-salvaguardas}

### 9.1 Validador HOMEPAGE-COMPLETENESS-001

**Archivo:** `scripts/validation/validate-homepage-completeness.php`

**Proposito:** Verificar que la homepage cumple los 15 criterios de LANDING-CONVERSION-SCORE-001
adaptados al contexto de homepage.

**Checks (15):**

```
CHECK 1: page--front.html.twig incluye _hero.html.twig o similar
CHECK 2: Hero tiene urgency badge ({% trans %}14 dias{% endtrans %} o similar)
CHECK 3: Existe _homepage-pain-points.html.twig Y se incluye en page--front
CHECK 4: Existe _how-it-works.html.twig Y se incluye
CHECK 5: Existe _homepage-features.html.twig O _features.html.twig Y se incluye
CHECK 6: Existe _homepage-comparison.html.twig Y se incluye
CHECK 7: Existe _testimonials.html.twig Y se incluye
CHECK 8: Existe _lead-magnet.html.twig Y se incluye
CHECK 9: Existe _homepage-pricing-preview.html.twig Y se incluye
CHECK 10: Existe _faq-homepage.html.twig Y se incluye, JSON-LD presente
CHECK 11: Existe _cta-banner-final.html.twig Y se incluye
CHECK 12: Existe _landing-sticky-cta.html.twig Y se incluye en page--front
CHECK 13: >= 8 secciones con reveal-element clase
CHECK 14: >= 10 CTAs con data-track-cta
CHECK 15: Sticky CTA tiene touch target >= 44px (CSS check)
```

**Registro en validate-all.sh:**
```bash
run_check "HOMEPAGE-COMPLETENESS-001" "Homepage 10/10 completeness" \
  php "$SCRIPT_DIR/validate-homepage-completeness.php"
```

### 9.2 Validador HOMEPAGE-PRICING-COHERENCE-001

**Archivo:** `scripts/validation/validate-homepage-pricing-coherence.php`

**Proposito:** Verificar que los precios mostrados en la homepage coinciden con los
configurados en SaasPlan entities.

**Checks:**
```
CHECK 1: _homepage-pricing-preview.html.twig usa ped_pricing (no hardcoded)
CHECK 2: ped_pricing se inyecta en preprocess_page para is_front
CHECK 3: MetaSitePricingService tiene datos para los 3 verticales mostrados
CHECK 4: Textos "desde X EUR" coinciden con starter_price real
CHECK 5: "14 dias gratis" coincide con trial_period (cross-check MARKETING-TRUTH-001)
```

### 9.3 Validador HOMEPAGE-METASITE-COVERAGE-001

**Archivo:** `scripts/validation/validate-homepage-metasite-coverage.php`

**Proposito:** Verificar que cada metasitio (PED, Impact, PDE) tiene homepage diferenciada.

**Checks (implementar en F5):**
```
CHECK 1: SiteConfig entity existe para cada dominio
CHECK 2: SiteConfig.homepage_variant esta definido (no null)
CHECK 3: page--front.html.twig tiene rama para cada variant
CHECK 4: Hero headline es diferente por variant
CHECK 5: Pricing preview muestra verticales relevantes al variant
```

### 9.4 Resumen de Nuevos Validadores

| ID | Archivo | Tipo | Fase | Checks |
|----|---------|------|------|--------|
| HOMEPAGE-COMPLETENESS-001 | `validate-homepage-completeness.php` | run_check | F4 | 15 |
| HOMEPAGE-PRICING-COHERENCE-001 | `validate-homepage-pricing-coherence.php` | run_check | F0 | 5 |
| HOMEPAGE-METASITE-COVERAGE-001 | `validate-homepage-metasite-coverage.php` | run_check | F5 | 5 |

**Total safeguards tras implementacion:** 83 + 3 = 86 scripts de validacion

### 9.5 Actualizacion del Meta-Safeguard

El validador `VALIDATOR-COVERAGE-001` (`validate-validator-coverage.php`) detecta
automaticamente scripts huerfanos. Los 3 nuevos validadores DEBEN registrarse en
`validate-all.sh` para pasar el meta-safeguard.

---

## 10. Plan de Verificacion RUNTIME-VERIFY-001 {#10-verificacion}

### 10.1 Verificacion por Fase

Tras completar CADA fase, ejecutar:

```bash
# 1. CSS compilado (timestamp > SCSS)
php scripts/validation/validate-compiled-assets.php

# 2. Twig syntax lint
php scripts/validation/validate-twig-syntax.php

# 3. Icon references resolve
php scripts/validation/validate-icon-references.php

# 4. Funnel tracking completeness
php scripts/validation/validate-funnel-tracking.php

# 5. No hardcoded prices
php scripts/validation/validate-no-hardcoded-prices.php

# 6. Marketing truth
php scripts/validation/validate-marketing-truth.php

# 7. Full suite
bash scripts/validation/validate-all.sh
```

### 10.2 PIPELINE-E2E-001 (4 capas)

| Capa | Verificacion | Comando |
|------|-------------|---------|
| L1: Service | MetaSitePricingService inyectado en preprocess | Grep en .theme |
| L2: Controller/Preprocess | Variables `homepage_pain_points`, `ped_pricing` pasadas | `var_dump` en preprocess |
| L3: hook_theme() | Variables declaradas en hook_theme si aplica | N/A (parciales, no templates con variables registradas) |
| L4: Template | Parciales incluidos con `only`, textos renderizados | curl + grep en HTML output |

### 10.3 Verificacion Visual (Manual)

1. Acceder a `https://jaraba-saas.lndo.site/` (flujo generico)
2. Verificar que TODAS las secciones nuevas se renderizan
3. Scroll completo: sticky CTA aparece tras 30vh
4. Click en sticky CTA: navega a registro
5. Responsive: verificar en 320px, 768px, 1024px
6. `prefers-reduced-motion`: verificar que animaciones se desactivan
7. Verificar que precios NO estan hardcoded (cambiar un precio en admin y verificar que cambia en homepage)

### 10.4 Verificacion SEO

1. Schema.org: Verificar JSON-LD FAQPage actualizado (10 preguntas)
2. Hreflang: Tags presentes en `<head>`
3. Geo: Tags `geo.region`, `geo.placename` presentes
4. OG Image: Fallback cascade funciona
5. Title: H1 unico en toda la pagina

---

## 11. Glosario {#11-glosario}

| Sigla | Significado |
|-------|-------------|
| AIDA | Atencion, Interes, Deseo, Accion — modelo clasico de conversion |
| BEM | Block Element Modifier — metodologia de nomenclatura CSS |
| B2G | Business to Government — modelo de negocio para instituciones publicas |
| CLS | Cumulative Layout Shift — metrica Core Web Vitals |
| CRM | Customer Relationship Management — gestion de relaciones con clientes |
| CSP | Content Security Policy — cabecera de seguridad HTTP |
| CTA | Call to Action — elemento que invita al usuario a realizar una accion |
| DI | Dependency Injection — patron de inyeccion de dependencias |
| DOM | Document Object Model — representacion del HTML en el navegador |
| FAB | Floating Action Button — boton flotante de accion |
| FAQ | Frequently Asked Questions — preguntas frecuentes |
| FSE | Fondo Social Europeo — programa de financiacion europea |
| GDPR | General Data Protection Regulation — reglamento de proteccion de datos (RGPD) |
| JSON-LD | JSON for Linking Data — formato de datos estructurados para SEO |
| LCP | Largest Contentful Paint — metrica Core Web Vitals |
| PDE | Plataforma de Ecosistemas — dominio plataformadeecosistemas.es |
| PED | Plataforma de Ecosistemas Digitales — marca paraguas |
| RGPD | Reglamento General de Proteccion de Datos — version europea de GDPR |
| SCSS | Sassy CSS — preprocesador CSS |
| SEO | Search Engine Optimization — optimizacion para motores de busqueda |
| SRI | Subresource Integrity — verificacion de integridad de recursos externos |
| SSE | Server-Sent Events — protocolo de comunicacion unidireccional |
| SSOT | Single Source of Truth — fuente unica de verdad |
| TAM | Total Addressable Market — mercado total direccionable |
| TOC | Table of Contents — tabla de contenidos |
| UTM | Urchin Tracking Module — parametros de seguimiento en URLs |
| WCAG | Web Content Accessibility Guidelines — directrices de accesibilidad |

---

## Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-21 | 1.0 | Creacion inicial — Auditoria exhaustiva + Plan 6 fases |

---

**Dependencias con otros documentos:**
- `20260320-Plan_Elevacion_Landings_Verticales_Clase_Mundial_10_10_v1_Claude.md` — Referencia 10/10
- `2026-03-06_Plan_Implementacion_Elevacion_MetaSitio_Corporativo_Clase_Mundial_v1.md` — PED elevation
- `20260226-Plan_Implementacion_MetaSitio_Remediacion_Integral_v1.md` — MetaSite architecture
- `20260321a-Plan_Implementacion_Activacion_IA_Embudo_Ventas_CRM_v1_Claude.md` — CRM pipeline
