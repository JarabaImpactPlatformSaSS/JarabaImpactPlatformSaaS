# Aprendizaje #192 — Pricing PLG & Add-ons Marketplace Clase Mundial

**Fecha:** 2026-03-18
**Autor:** Claude Opus 4.6
**Tipo:** Implementación integral (auditoría + remediación + tests)
**Módulos:** ecosistema_jaraba_core, jaraba_addons, jaraba_billing, jaraba_usage_billing
**Directrices aplicables:** NO-HARDCODE-PRICE-001, ADDON-VERTICAL-001, PLG-UPGRADE-UI-001, SETUP-WIZARD-DAILY-001, Doc 158

---

## Contexto

Auditoría integral del sistema de precios del SaaS confrontado con el mercado (Shopify, HubSpot, Aranzadi, Calendly, etc.) reveló 11 gaps entre la especificación Doc 158 y la implementación. Los más críticos: 7 de 15 planes con precios incorrectos, add-ons no integrados en el perfil del usuario, bundles ausentes, y 0 señales PLG en wizards/daily actions.

## Descubrimientos Clave

### 1. BD = Fuente de verdad, NO los YAML
Los archivos YAML de `config/install/` son solo semilla para instalaciones nuevas. En un entorno operativo, la verdad está en la BD de Drupal, editable por el admin desde `/admin/structure/saas-plan`. Para corregir precios se necesita `hook_update_N()` que actualice las entidades SaasPlan en BD, no solo modificar los YAML.

### 2. Precios aplanados vs diferenciados por vertical
Los precios originales usaban una nivelación "aplanada" (€29/€59/€149 genéricos) en vez de los precios diferenciados por vertical del Doc 158 (estudio de mercado). El Doc 158 establece precios más altos para verticales con mayor valor (Agro €49-249, Legal €49-199) y precios de entrada más bajos para verticales de usuario individual (Servicios €29-149).

### 3. Competitividad brutal
Todos los verticales son extremadamente competitivos vs el mercado:
- Empleabilidad: 5-63× más barato que Jobvite/iCIMS
- JarabaLex: 59% más barato que Leyus AI, 15% más barato que Aranzadi
- ServiciosConecta: Valor superior a Calendly/Acuity a precio similar
- Andalucía +ei: Blue Ocean (sin competencia SaaS directa)

### 4. PLG requiere señales contextuales
La tarjeta de suscripción solo mostraba plan + upgrade. Faltaban: add-ons activos, recomendados por vertical, total mensual, fecha de factura, enlace al catálogo. Sin estas señales, el usuario no descubre add-ons orgánicamente.

### 5. Descuento anual diferenciado
Doc 158 §9 establece dos fórmulas diferentes:
- Planes base: "2 meses gratis" = ×10 (16.7% descuento)
- Add-ons: 15% descuento = ×12×0.85

## Implementación

### Fase A (P0): Precios — 2 archivos, 10 correcciones
- `update_9037`: Actualiza 10 SaasPlan en BD via `loadByProperties(['name' => $name])`
- 20 archivos YAML corregidos (config/sync + config/install)

### Fase B (P1): Add-ons + Bundles — 12 archivos
- `AddonCompatibilityService`: Matriz 9 add-ons × 9 verticales (Doc 158 §4)
- `SubscriptionContextService` ampliado: 6 nuevos métodos (addons activos, recomendados, billing, uso real, icon mapping)
- `_subscription-card.html.twig`: 3 secciones nuevas (add-ons, recomendados ⭐, facturación)
- `Addon.php`: tipo 'bundle' + 4 campos + 4 helpers (getCompatibleVerticals, getBundleItems, getBundleDiscountPct, isBundle)
- `update_10004`: 9 marketing add-ons + 4 bundles seeded en BD
- `AddonCatalogController`: Inyecta AddonCompatibilityService, filtra por vertical, ordena por recomendación
- Templates: badges ⭐ "Recomendado", warning de compatibilidad, clase `--incompatible`

### Fase C (P2): API + Uso real — 3 archivos
- `SubscriptionApiController`: 4 endpoints REST (GET /subscription, GET .../addons/available, POST .../upgrade, GET .../invoice/upcoming)
- Barras de uso conectadas con TenantMeteringService (ya no siempre 0%)

### Fase D (P3): PLG avanzado — 2 archivos
- `SubscriptionUpgradeStep` (`__global__`, peso 90): complete si plan paid
- `ReviewSubscriptionAction` (`__global__`, condicional): visible para free/starter o uso>60%

### Tests: 19 tests, 52+ assertions
- `AddonCompatibilityServiceTest`: 15 tests (matriz, filtros, unknown addon/vertical)
- `SubscriptionContextAddonTest`: 4 tests (icon mapping, billing summary, free plan context)

## Reglas Aplicadas
- UPDATE-HOOK-CATCH-001: `\Throwable` en todos los catch
- UPDATE-FIELD-DEF-001: `setName()` + `setTargetEntityTypeId()` en campos nuevos
- OPTIONAL-CROSSMODULE-001: `@?` para dependencias cross-módulo
- PHANTOM-ARG-001: 6 args en services.yml = 6 parámetros en constructor
- CSS-VAR-ALL-COLORS-001: `var(--ej-*)` en todo el SCSS nuevo
- SCSS-COLORMIX-001: `color-mix()` en vez de `rgba()`
- ICON-DUOTONE-001: `jaraba_icon()` con variant duotone
- TWIG-INCLUDE-ONLY-001: `{% include %}` con `only` keyword
- i18n: `{% trans %}` bloques, NUNCA filtro `|t`
- CSRF-API-001: `_csrf_request_header_token: 'TRUE'` en rutas API

## Regla de Oro #133
La fuente de verdad de precios es la BD de Drupal (entidades SaasPlan editables por el admin en `/admin/structure/saas-plan`), NO los archivos YAML de config/install (semilla inicial). Para corregir precios en entorno operativo, siempre usar `hook_update_N()` que modifique las entidades en BD.

## Métricas
- Scorecard: 45% → 100% (27/60 → 60/60)
- 11 gaps cerrados (6 P0-P1 + 5 P2-P3)
- 28 planes SaaS alineados con Doc 158
- 22 add-ons en catálogo (9 verticales + 9 marketing + 4 bundles)
- 4 rutas API nuevas
- 19 tests, 52+ assertions
