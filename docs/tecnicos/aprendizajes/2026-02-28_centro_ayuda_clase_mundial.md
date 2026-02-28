# Aprendizaje #151 — Centro de Ayuda Clase Mundial: De Infraestructura Vacía a Experiencia Completa

**Fecha:** 2026-02-28
**Severidad:** Alta
**Impacto:** La ruta `/ayuda` tenía infraestructura completa (rutas, controlador, templates, SCSS 694 LOC, JS, FAQ bot) pero ZERO contenido visible — violación directa de RUNTIME-VERIFY-001.

## Problema

El módulo `jaraba_tenant_knowledge` implementaba Help Center con:
- HelpCenterController con métodos de renderizado
- Template help-center.html.twig con condicionales Twig
- _help-center.scss con 694 líneas de estilos
- help-center.js con autocompletado y scroll
- FAQ Bot widget integrado

Pero el usuario experimentaba:
- Hero vacío con "0 artículos disponibles"
- Todas las secciones ocultas por `{% if categories is not empty %}`
- CTA sin botones de acción
- Bot FAQ sin sugerencias
- Categorías obsoletas de e-commerce (shipping, returns, payment)

## Solución: 8 Fases de Implementación

### Fase 1: Nuevo sistema de categorías (8 SaaS)
```
getting_started, account, features, billing, ai_copilot, integrations, security, troubleshooting
```
Reemplaza categorías e-commerce irrelevantes para SaaS multi-vertical.

### Fase 2: 25 FAQs seed via update_10003
- `tenant_id = NULL` (no 0) para contenido platform-wide
- **Descubrimiento**: `entity_reference` con `target_id = 0` almacena NULL en BD
- Idempotencia: `->notExists('tenant_id')` en vez de `->condition('tenant_id', 0)`
- Migración categorías: SQL directo sobre `_field_data` (TRANSLATABLE-FIELDDATA-001)

### Fase 3: Controller refactorizado
- `getCategoryMeta()` — método extraído para evitar duplicación
- `buildFaqPageSchema()` — JSON-LD FAQPage
- `buildBreadcrumbSchema()` — JSON-LD BreadcrumbList
- `buildHelpCenterSeoHead()` — OG/Twitter meta tags
- `getKbArticleCount()` — cross-link con `/ayuda/kb`
- `searchApi()` — búsqueda unificada FAQ + KB

### Fase 4: Búsqueda unificada
- `hasDefinition('kb_article')` para tipo opcional cross-módulo
- KB usa slug-based URLs: `Url::fromRoute('...kb.article', ['slug' => $slug])`
- Campo `type` en JSON para distinguir FAQ de KB en autocompletado
- JS NUNCA construye URLs de fallback

### Fase 5: Template rediseñado
- Trust signals: "Respuestas instantáneas", "Soporte 24/7", "IA integrada"
- Quick links: 4 enlaces con `Url::fromRoute()` (nunca hardcoded)
- KB cross-link: visible solo si `kb_articles_count > 0`
- CTA: botones /contacto + /soporte
- Animaciones: `data-animate="fade-in-up"` con IntersectionObserver

### Fase 6: SCSS ~270 LOC nuevas
- `.help-center__trust-signals`, `.help-quick-link`, `.help-center__kb-promo`
- Animaciones con `prefers-reduced-motion: reduce` + `.no-js` fallback
- Todas las clases BEM + colores via `var(--ej-*, fallback)`

## 11 Gaps de Auditoría RUNTIME-VERIFY-001

| # | Gap | Causa Raíz |
|---|-----|-----------|
| 1 | CSS faltante `.help-autocomplete__type--kb` | JS generaba clase sin SCSS correspondiente |
| 2-3 | URLs hardcodeadas en quick links y contact channels | Violación ROUTE-LANGPREFIX-001 — sitio usa `/es/` |
| 4 | Template hardcoded `/ayuda/kb` | Variable `kb_url` no existía |
| 5-6 | JS con URLs fallback hardcodeadas | Eliminados — early return si drupalSettings ausente |
| 7 | KB article URL con entity ID en vez de slug | Ruta usa `{slug}` parámetro |
| 9 | Animaciones `opacity:0` sin fallback no-JS | Contenido invisible sin JavaScript |
| 10 | `target_id=0` → NULL en BD | Entity reference no almacena 0, almacena NULL |
| 11 | Categorías desordenadas | Reordenadas contra `getCategoryMeta()` keys |

## Reglas Derivadas

- **HELP-CENTER-SEED-001** (P1): Seed data via update hooks con tenant_id=NULL, notExists() para idempotencia
- **UNIFIED-SEARCH-001** (P1): APIs cross-entity con hasDefinition(), Url::fromRoute(), campo type, JS sin fallback

## Reglas de Oro

- **#86**: `entity_reference` con `target_id=0` almacena NULL en BD — para idempotencia usar `->notExists('field')` no `->condition('field', 0)`
- **#87**: Búsqueda unificada cross-entity: `hasDefinition()` guard + `Url::fromRoute()` para todas las URLs + JS sin fallback URLs (si servidor no provee URL, omitir resultado)

## Archivos Modificados (10)

1. `jaraba_tenant_knowledge/src/Entity/TenantFaq.php` — 8 categorías SaaS
2. `jaraba_tenant_knowledge/src/Controller/HelpCenterController.php` — refactor completo
3. `jaraba_tenant_knowledge/templates/help-center.html.twig` — rediseño
4. `ecosistema_jaraba_core/scss/_help-center.scss` — ~270 LOC nuevas
5. `jaraba_tenant_knowledge/js/help-center.js` — drupalSettings + animations
6. `jaraba_tenant_knowledge/jaraba_tenant_knowledge.install` — update_10003
7. `jaraba_tenant_knowledge/jaraba_tenant_knowledge.module` — hook_theme vars
8. `jaraba_tenant_knowledge/jaraba_tenant_knowledge.libraries.yml` — drupalSettings dep
9. `ecosistema_jaraba_theme/templates/partials/_footer.html.twig` — link /ayuda
10. CSS compilados (help-center.css + ecosistema-jaraba-core.css)

## Lección Clave

> La diferencia entre "el código existe" y "el usuario lo experimenta" requiere verificación en CADA capa de la cadena: PHP → Twig → SCSS → CSS compilado → JS → drupalSettings → DOM final. Un gap en cualquier eslabón rompe la experiencia completa.
