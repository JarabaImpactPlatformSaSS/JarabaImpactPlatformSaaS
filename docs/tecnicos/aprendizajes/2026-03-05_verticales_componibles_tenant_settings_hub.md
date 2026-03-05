# Aprendizaje #161 — Verticales Componibles + Tenant Settings Hub + Stripe Sync

**Fecha:** 2026-03-05
**Contexto:** Implementacion de verticales componibles como add-ons + hub unificado de configuracion tenant + sincronizacion bidireccional con Stripe.

## Hallazgos

### 1. Verticales como Add-ons (ADDON-VERTICAL-001)
- Addon entity soporta `addon_type='vertical'` con campo `vertical_ref` (string, machine_name del vertical)
- `TenantVerticalService` resuelve TODOS los verticales activos por tenant: primario del grupo + addon subscriptions
- `FeatureAccessService` verifica features via `hasActiveAddonSubscription()` con fallback a TenantAddon legacy
- `BaseAgent::getAddonVerticalsContext()` enriquece prompts IA con verticales addon via `\Drupal::hasService()` lazy-load
- 9 verticales seed (todos excepto demo) con precios escalonados

### 2. Tagged Service Registry (TENANT-SETTINGS-HUB-001)
- `TenantSettingsRegistry` + `TenantSettingsSectionPass` CompilerPass recolecta servicios tagged
- 6 secciones: Domain, Plan, Branding, Design, ApiKeys, Webhooks
- Cada seccion implementa `TenantSettingsSectionInterface` (getId/getLabel/getWeight/buildSection)
- Nuevas secciones se registran solo con tag en services.yml — escalabilidad sin tocar core
- Patron reutilizable para cualquier subsistema extensible

### 3. Stripe Sync Bidireccional
- `TenantSubscriptionService::changePlan()` sincroniza con Stripe via `updateSubscription()`
- Revert local si Stripe falla (mantiene consistencia)
- Lazy-load via `getStripeSubscription()` + `\Drupal::hasService()` para romper circular dep

### 4. Tenant Token Override
- `ThemeTokenService` auto-resuelve tenant_id desde `TenantContextService` si no se pasa parametro
- CSS custom properties inyectados en `hook_preprocess_html` como `<style id="jaraba-tenant-tokens">:root {...}</style>`
- Theme preprocess NUNCA debe crashear — try-catch obligatorio

### 5. AI Vertical Enrichment
- `BaseAgent::getVerticalContext()` ahora incluye verticales addon
- `getAddonVerticalsContext()` con hasService() check + try-catch

## Reglas de Oro
- **#102**: Tagged registry pattern (CompilerPass + tagged services) para secciones extensibles de modulos. Nuevas secciones se registran con solo un tag en services.yml sin tocar codigo core.
- **#103**: Verticales como addons (addon_type='vertical' + TenantVerticalService multi-vertical resolution + AI context enrichment). Lazy-load con hasService() para deps opcionales cross-module.

## Validaciones
- validate-optional-deps.php: OK (todas las deps cross-module con @?)
- validate-circular-deps.php: OK (0 ciclos, lazy-load resuelve TenantSubscription↔Stripe)
- validate-logger-injection.php: OK (598 servicios consistentes)
- validate-entity-integrity.php: OK
- validate-routing.php: OK (2162 rutas validadas)
- verify-doc-integrity.sh: OK (4 docs superan umbrales)

## Cross-refs
- Directrices v111.0.0, Arquitectura v100.0.0, Indice v140.0.0, Flujo v64.0.0
- MEMORY: `memory/verticales-componibles.md` (detalle completo)
