# Plan: Elevacion a Clase Mundial 10/10 — Performance + A11y + SEO
# Fecha: 2026-03-21 | Sprint: Performance Elevation
# Estado: En ejecucion

## Contexto

PageSpeed Insights (21/03/2026):
- **Movil**: 68/100 (Rendimiento), 94 (A11y), 100 (Best Practices), 92 (SEO)
- **Desktop**: 94/100 (Rendimiento), 94 (A11y), 100 (Best Practices), 92 (SEO)

### Causa raiz movil (68/100)
- FCP 3.2s (rojo) — CSS render-blocking 230KB
- LCP 5.6s (rojo) — Logo PNG 6250x4419 (448KB) mostrado a 48px
- Speed Index 6.5s (rojo) — Consecuencia de FCP+LCP

### Infraestructura existente (verificada, NO duplicar)
| Sistema | Regla | Estado |
|---------|-------|--------|
| Responsive images AVIF+WebP | HAL-AI-13 | ✅ Implementado |
| Critical CSS injection | PERF-02 | ✅ Implementado |
| CWV tracking | HAL-AI-10 | ✅ Implementado |
| CSS code splitting | HAL-AI-12 | ✅ Implementado |
| Lazy loading default | AUDIT-PERF-N16 | ✅ Implementado |
| prefers-reduced-motion | WCAG 2.1 SC 2.2.2 | ✅ Implementado (15+ SCSS, 7+ JS) |
| Focus trap slide-panel | GAP-A11Y-001 | ✅ Implementado |
| Canonical links | SEO-001 | ✅ Implementado |
| OG + Twitter Cards | SEO-003 | ✅ Implementado |
| BreadcrumbList Schema | SEO-007 | ✅ Implementado |
| Google Fonts display=swap | PERF-03 | ✅ Implementado |

## Gaps reales identificados y corregidos

### 1. Logo sobredimensionado (impacto: -447KB, LCP -3s)
- **Antes**: PNG 6250x4419px = 448KB, sin WebP, sin width/height
- **Despues**: PNG 135x96px = 4.2KB, WebP 2.6KB, `<picture>` con fetchpriority
- **Archivos**: `_header-classic.html.twig`, `images/EcosistemaJaraba_icono_v6.{png,webp}`
- **Estado**: ✅ Aplicado (pendiente deploy produccion)

### 2. width/height en logos de todos los headers (impacto: CLS)
- **Archivos**: `_header-{minimal,split,hero,centered}.html.twig`, `_footer.html.twig`
- **Estado**: ✅ Aplicado

### 3. Preconnect + DNS-prefetch (impacto: FCP -300ms)
- Google Fonts, GTM, Stripe
- **Archivo**: `html.html.twig`
- **Estado**: ✅ Aplicado

### 4. Sitemap en robots.txt (impacto: SEO indexacion)
- **Archivo**: `web/robots.txt`
- **Estado**: ✅ Aplicado

### 5. Nginx optimizacion produccion (impacto: cache + compresion)
- Gzip, cache headers, WebP content negotiation
- Cache headers ya correctos para CSS/JS del tema (1y + immutable)
- **Estado**: Verificado — ya configurado correctamente

### 6. Critical CSS en produccion
- Infraestructura existe pero requiere `npm run build:critical` con URLs produccion
- **Estado**: Pendiente verificacion en produccion

## Verificacion (cache headers actuales produccion)
```
CSS tema: Cache-Control: max-age=31536000, public, immutable ✅
Google Fonts: display=swap ✅
SPF: v=spf1 include:_spf-eu.ionos.com ~all ✅
DMARC: v=DMARC1; p=none (gestionado por IONOS CNAME) ⚠️
SSL: Let's Encrypt E7, valido hasta 18/06/2026 ✅
Stripe webhooks: Activos con HMAC verification ✅
```

## Impacto estimado post-deploy

| Metrica | Antes | Despues (est.) | Target |
|---------|-------|----------------|--------|
| Movil Performance | 68 | 85-92 | >80 |
| Desktop Performance | 94 | 96-99 | >95 |
| Accesibilidad | 94 | 94-96 | >90 |
| SEO | 92 | 95-98 | >90 |
| Best Practices | 100 | 100 | 100 |

## Leccion aprendida
El SaaS tenia infraestructura de rendimiento sofisticada (HAL-AI-10/12/13, PERF-02) pero
el mayor cuello de botella era un asset estatico basico: un logo PNG de 448KB sin optimizar.
SIEMPRE verificar assets estaticos antes de asumir que faltan sistemas complejos.

## Glosario
- **FCP**: First Contentful Paint
- **LCP**: Largest Contentful Paint
- **CLS**: Cumulative Layout Shift
- **CWV**: Core Web Vitals
- **HAL-AI**: Historial de Aprendizajes Lighthouse AI
- **PERF**: Performance optimization rule
- **OG**: Open Graph (meta tags para redes sociales)
- **DMARC**: Domain-based Message Authentication, Reporting & Conformance
- **SPF**: Sender Policy Framework
- **HMAC**: Hash-based Message Authentication Code
