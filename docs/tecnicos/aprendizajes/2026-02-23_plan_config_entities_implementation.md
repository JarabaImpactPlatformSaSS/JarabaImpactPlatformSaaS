# Precios Configurables v2.1 - Implementacion SaasPlanTier + SaasPlanFeatures

**Fecha**: 2026-02-23
**Scope**: Remediacion de arquitectura de precios hardcodeados
**Ticket**: Precios Configurables v2.1

## Problema

Tres puntos de hardcodeo de capacidades de plan:

1. **QuotaManagerService** (jaraba_page_builder): Array `$capabilities` con starter/professional/enterprise hardcodeado en PHP
2. **SaasPlan ContentEntity**: Features como `list_string` con 15 valores fijos en `baseFieldDefinitions()`
3. **SaasPlan.limits**: JSON libre sin validacion de schema

Cualquier cambio de plan, feature o limite requeria deploy y cambio de codigo.

## Solucion

Dos nuevas ConfigEntities como fuente de verdad, editables desde UI:

### SaasPlanTier (`saas_plan_tier`)
- Config prefix: `ecosistema_jaraba_core.plan_tier.*`
- Almacena: tier_key, aliases (para normalizacion), Stripe Price IDs, weight
- 3 seed configs: starter, professional, enterprise
- Ruta admin: `/admin/config/jaraba/plan-tiers`

### SaasPlanFeatures (`saas_plan_features`)
- Config prefix: `ecosistema_jaraba_core.plan_features.*`
- Almacena: vertical, tier, features (lista), limits (mapa key->int)
- 18 seed configs: 3 defaults + 5 verticales x 3 tiers
- Ruta admin: `/admin/config/jaraba/plan-features`
- Cascade: especifico (vertical+tier) -> default (_default+tier) -> NULL

### PlanResolverService
- Central broker: `ecosistema_jaraba_core.plan_resolver`
- `normalize(planName)`: Resuelve aliases a tier key canonico
- `getFeatures(vertical, tier)`: Cascade especifico -> default -> NULL
- `checkLimit(vertical, tier, limitKey)`: Limite numerico con default
- `hasFeature(vertical, tier, featureKey)`: Boolean feature check
- `resolveFromStripePriceId(priceId)`: Resolucion inversa Stripe -> tier
- `getPlanCapabilities(vertical, tier)`: Array plano compatible con QuotaManager

## Integraciones

| Consumidor | Inyeccion | Patron |
|-----------|-----------|--------|
| QuotaManagerService | `@?ecosistema_jaraba_core.plan_resolver` | Lee de PlanResolver, fallback a array hardcoded |
| PlanValidator | `@?ecosistema_jaraba_core.plan_resolver` | Fuente adicional en cascade FreemiumVerticalLimit -> PlanFeatures -> SaasPlan |
| BillingWebhookController | `$container->has()` check | Resuelve tier desde Stripe Price ID en webhook |

## Patrones Aplicados

- **CONFIG-SEED-001**: Update hook `9019` con `FileStorage` + `load()` check + `create()->save()`
- **Optional injection** (`@?`): Todos los consumidores cross-module usan nullable
- **AdminHtmlRouteProvider**: Rutas CRUD auto-generadas desde anotacion de entidad
- **Body classes en hook_preprocess_html()**: Clase `page-plan-admin` para SCSS

## Reglas Aprendidas

1. `AdminHtmlRouteProvider` genera rutas automaticamente desde los `links` de la anotacion `@ConfigEntityType`. No hace falta editar routing.yml para rutas CRUD basicas.

2. Para ConfigEntities con campos tipo `sequence` (arrays), la propiedad PHP debe inicializarse como `protected $field = []` y el schema debe tener `type: sequence` con inner `type: string`.

3. El patron de cascade para features (especifico -> default -> null) es escalable: cada vertical puede tener configs parciales y el default cubre los gaps.

4. `resolveEffectiveLimit()` en PlanValidator ahora tiene 3 fuentes en cascada:
   - FreemiumVerticalLimit (via UpgradeTriggerService) - mayor prioridad
   - SaasPlanFeatures (via PlanResolverService) - prioridad media
   - SaasPlan fallback - menor prioridad

5. El alias map en PlanResolverService se construye lazily y tiene `resetAliasCache()` para invalidacion. Considerar cache tag invalidation via hook si se modifica frecuentemente.

6. **CONFIG-SCHEMA-001 (Critical):** Cuando un campo de ConfigEntity tiene keys dinamicos que varian por vertical (ej. `limits` con keys como `products`, `photos_per_product`, `commission_pct` para AgroConecta vs `max_pages`, `basic_templates` para Page Builder), el schema DEBE usar `type: sequence` con inner `type: integer`, NO `type: mapping` con keys fijos. `mapping` requiere declarar TODOS los keys posibles; cualquier key no declarado lanza `SchemaIncompleteException` en Kernel tests (donde `ConfigSchemaChecker` valida estrictamente). Este error no se detecta en Unit tests ni en runtime, solo en Kernel tests del CI.
   ```yaml
   # CORRECTO — acepta cualquier key string → integer
   limits:
     type: sequence
     sequence:
       type: integer

   # INCORRECTO — falla si el YAML tiene keys no declarados
   limits:
     type: mapping
     mapping:
       max_pages:
         type: integer
       # Falta: products, photos_per_product → SchemaIncompleteException
   ```

## Archivos Creados

- `src/Entity/SaasPlanTierInterface.php`
- `src/Entity/SaasPlanTier.php`
- `src/Entity/SaasPlanFeatures.php`
- `src/Form/SaasPlanTierForm.php`
- `src/Form/SaasPlanFeaturesForm.php`
- `src/SaasPlanTierListBuilder.php`
- `src/SaasPlanFeaturesListBuilder.php`
- `src/Service/PlanResolverService.php`
- `src/Commands/PlanValidationCommands.php`
- `config/schema/ecosistema_jaraba_core.plan_tier.schema.yml`
- `config/schema/ecosistema_jaraba_core.plan_features.schema.yml`
- `scss/_plan-admin.scss`
- `tests/src/Kernel/PlanConfigContractTest.php`
- 21 seed YAMLs en `config/install/`

## Archivos Editados

- `ecosistema_jaraba_core.services.yml` (PlanResolver + Drush command)
- `ecosistema_jaraba_core.install` (update_9019)
- `ecosistema_jaraba_core.links.menu.yml` (2 menu links)
- `ecosistema_jaraba_core.links.action.yml` (2 action links)
- `scss/main.scss` (import _plan-admin)
- `jaraba_billing/jaraba_billing.services.yml` (PlanResolver in PlanValidator)
- `jaraba_billing/src/Service/PlanValidator.php` (PlanResolver injection + cascade)
- `jaraba_billing/src/Controller/BillingWebhookController.php` (PlanResolver injection)
- `jaraba_page_builder/jaraba_page_builder.services.yml` (PlanResolver in QuotaManager)
- `jaraba_page_builder/src/Service/QuotaManagerService.php` (PlanResolver integration)
- `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` (body classes)

## Verificacion

```bash
# Import seed data to existing installations
lando drush updatedb -y

# Clear cache
lando drush cr

# Validate plan configurations
lando drush jaraba:validate-plans

# Run contract tests
lando ssh -c "cd /app && vendor/bin/phpunit --group plan-config-contract --testdox"

# Compile SCSS
lando ssh -c "cd /app/web/modules/custom/ecosistema_jaraba_core && npx sass scss/main.scss css/ecosistema-jaraba-core.css --style=compressed"
```
