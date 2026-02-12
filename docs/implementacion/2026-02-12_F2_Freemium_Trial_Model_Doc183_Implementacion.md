# Plan de Implementacion: F2 — Freemium & Trial Model (Doc 183)
## Limites Verticales, Triggers de Upgrade y Conversion PLG

**Fecha de creacion:** 2026-02-12
**Ultima actualizacion:** 2026-02-12
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Plan de Implementacion — Cierre de Gap F2
**Codigo:** IMPL-F2-FREEMIUM-v1
**Fase del Plan Maestro:** F2 de 12 (PLAN-20260128-GAPS-v1, §7)
**Documento de Especificacion:** `183_Freemium_Trial_Model_v1`

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Analisis de Estado Actual](#2-analisis-de-estado-actual)
3. [Gaps Identificados](#3-gaps-identificados)
4. [Arquitectura de la Solucion](#4-arquitectura-de-la-solucion)
5. [Componente 1: FreemiumVerticalLimit ConfigEntity](#5-componente-1-freemiumverticallimit-configentity)
6. [Componente 2: UpgradeTriggerService](#6-componente-2-upgradetriggerservice)
7. [Componente 3: Parcial Twig _upgrade-trigger](#7-componente-3-parcial-twig-_upgrade-trigger)
8. [Componente 4: SCSS _upgrade-trigger.scss](#8-componente-4-scss-_upgrade-triggerscss)
9. [Componente 5: Integracion con Servicios Existentes](#9-componente-5-integracion-con-servicios-existentes)
10. [Seed Data: Catalogo de Limites por Vertical](#10-seed-data-catalogo-de-limites-por-vertical)
11. [Tabla de Correspondencia con Especificaciones](#11-tabla-de-correspondencia-con-especificaciones)
12. [Checklist de Cumplimiento de Directrices](#12-checklist-de-cumplimiento-de-directrices)
13. [Plan de Verificacion](#13-plan-de-verificacion)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### Proposito

Esta fase implementa el modelo freemium diferenciado por vertical, que es el pilar de la estrategia PLG (Product-Led Growth) del SaaS. El objetivo es que cada vertical tenga limites freemium especificos que se configuran desde la UI de Drupal sin tocar codigo, y que se disparen triggers contextuales de upgrade en los momentos de mayor intencion de compra.

### Que resuelve

| Problema Actual | Solucion F2 |
|-----------------|-------------|
| Los limites de plan (`UsageLimitsService::PLAN_LIMITS`) son genericos y estan hardcodeados en PHP | ConfigEntity `FreemiumVerticalLimit` administrable desde `/admin/structure/freemium-limits` |
| No hay diferenciacion de limites por vertical (AgroConecta vs Empleabilidad tienen los mismos limites) | Limites especificos: AgroConecta = 5 productos free, ComercioConecta = 10 productos free |
| No existen triggers contextuales de upgrade (primera venta, feature bloqueada, etc.) | `UpgradeTriggerService` con 5 tipos de trigger y tracking de conversion |
| No hay UI de upgrade cuando se alcanza un limite | Parcial Twig `_upgrade-trigger.html.twig` con slide-panel y comparativa visual |

### Inversion Estimada

| Concepto | Horas |
|----------|-------|
| ConfigEntity + Schema + Form + ListBuilder + Seed Data | 4-6h |
| UpgradeTriggerService | 3-4h |
| Parcial Twig + SCSS | 3-4h |
| Integracion con servicios existentes | 3-4h |
| Verificacion y testing | 2-3h |
| Documentacion | 1-2h |
| **TOTAL** | **16-23h** |

---

## 2. Analisis de Estado Actual

### 2.1 Infraestructura Existente (Lo que ya funciona)

La plataforma dispone de una infraestructura de billing **madura y production-ready**:

| Servicio | Ubicacion | Estado | Funcion |
|----------|-----------|--------|---------|
| `TenantSubscriptionService` | `ecosistema_jaraba_core` + `jaraba_billing` | Operativo | Ciclo de vida: trial 14d, active, past_due, suspended, cancelled |
| `ReverseTrialService` | `jaraba_billing` | Operativo | Trial inverso: 14 dias a Pro, auto-downgrade a Starter |
| `PlanValidator` | `ecosistema_jaraba_core` | Operativo | Validacion de limites genericos (productores, storage, AI queries) |
| `UsageLimitsService` | `ecosistema_jaraba_core` | Operativo | Deteccion de uso, alertas 75%/90%/100%, sugerencias upgrade |
| `FeatureAccessService` | `jaraba_billing` | Operativo | Acceso feature = plan base + addons activos |
| `TenantMeteringService` | `jaraba_billing` | Operativo | Medicion de 8 metricas (api_calls, ai_tokens, storage, etc.) |
| `PricingRuleEngine` | `jaraba_billing` | Operativo | Pricing dinamico: flat, tiered, volume, package |
| `DunningService` | `jaraba_billing` | Operativo | Secuencia de cobro 6 pasos con restricciones progresivas |
| `SaasPlan` | `ecosistema_jaraba_core` | Operativo | ContentEntity con `PLAN_LIMITS` hardcodeado |
| 26 endpoints REST | `jaraba_billing` | Operativo | API completa de billing, usage, addons |
| 88 unit tests | `jaraba_billing/tests` | Operativo | 304 assertions validando todo el stack |

### 2.2 Limites Actuales (Genericos, sin vertical)

Los limites actuales estan definidos como constantes PHP en dos servicios:

**`UsageLimitsService::PLAN_LIMITS`** (ecosistema_jaraba_core):
```
starter:      products=25, orders_month=100, storage_mb=500, api_calls_day=1000, team_members=1
professional: products=100, orders_month=500, storage_mb=2000, api_calls_day=5000, team_members=5
business:     products=500, orders_month=2500, storage_mb=10000, api_calls_day=25000, team_members=15
enterprise:   all=-1 (ilimitado)
```

**`SaasPlan::PLAN_LIMITS`** (idéntico, duplicado):
```
starter:      products=25, orders_month=100, storage_mb=500, api_calls_day=1000, team_members=1
...
```

**Problema critico**: Estos limites son identicos para todas las verticales. Doc 183 requiere que AgroConecta tenga 5 productos free, ComercioConecta tenga 10, Empleabilidad tenga 1 diagnostico, etc.

---

## 3. Gaps Identificados

| # | Gap | Severidad | Solucion |
|---|-----|-----------|----------|
| G1 | Limites freemium identicos para todas las verticales | CRITICA | ConfigEntity `FreemiumVerticalLimit` |
| G2 | Limites hardcodeados en constantes PHP (no editables desde UI) | CRITICA | Migrar a ConfigEntity administrable |
| G3 | No existen triggers contextuales de upgrade | ALTA | `UpgradeTriggerService` |
| G4 | No hay modal de upgrade al alcanzar limite | ALTA | Parcial Twig + SCSS con slide-panel |
| G5 | `PlanValidator` no consulta limites por vertical | MEDIA | Extender con inyeccion de `FreemiumVerticalLimit` |
| G6 | `UsageLimitsService::getCurrentUsage()` usa datos simulados (rand) | MEDIA | Conectar con `TenantMeteringService` real |

---

## 4. Arquitectura de la Solucion

### 4.1 Diagrama de Componentes

```
FreemiumVerticalLimit (ConfigEntity)
├── Almacena: vertical + plan + feature_key + limit_value
├── Administrable desde: /admin/structure/freemium-limits
└── 15+ registros seed (5 verticales x 3+ features)
       │
       ▼
UpgradeTriggerService (Nuevo servicio)
├── fire(type, tenant, context) → Registra evento + devuelve modal data
├── Tipos: limit_reached, feature_blocked, first_sale, competition, time_on_platform
├── Depende de: FreemiumVerticalLimit, TenantMeteringService
└── Almacena: upgrade_trigger_events (tabla custom)
       │
       ▼
_upgrade-trigger.html.twig (Parcial Twig)
├── Slide-panel con comparativa Free vs Pro
├── Icono duotone: jaraba_icon('actions', 'rocket', ...)
├── i18n: todos los textos con {% trans %}
└── Estilos: _upgrade-trigger.scss con var(--ej-*)
```

### 4.2 Patron de Resolucion de Limites

```
1. Accion del usuario (ej: crear producto)
       │
       ▼
2. PlanValidator.enforceLimit(tenant, 'add_product')
       │
       ▼
3. Resolucion de limite:
   a) ¿Existe FreemiumVerticalLimit para (vertical + plan + feature)?
      → SI: usar ese limite
      → NO: fallback a SaasPlan::PLAN_LIMITS generico
       │
       ▼
4. Si limite alcanzado:
   a) UpgradeTriggerService.fire('limit_reached', tenant, {feature: 'products'})
   b) Devolver render array con parcial _upgrade-trigger.html.twig
```

### 4.3 Decision: ConfigEntity vs ContentEntity

**ConfigEntity** (elegido) porque:
- Los limites freemium son definidos por el equipo del SaaS, no por los tenants
- Se exportan a Git via `config:export` y llegan a produccion via `config:import`
- No requieren Field UI ni Views (son datos de configuracion de negocio)
- Patron identico a `Feature`, `AIAgent`, `EcaFlowDefinition` ya existentes

---

## 5. Componente 1: FreemiumVerticalLimit ConfigEntity

### 5.1 Propiedades

| Propiedad | Tipo | Descripcion | Ejemplo |
|-----------|------|-------------|---------|
| `id` | string | ID unico: `{vertical}_{plan}_{feature}` | `agroconecta_free_products` |
| `label` | string | Nombre legible | `AgroConecta Free: Productos` |
| `vertical` | string | Machine name de la vertical | `agroconecta` |
| `plan` | string | Machine name del plan | `free`, `starter`, `profesional` |
| `feature_key` | string | Clave del recurso limitado | `products`, `orders_per_month`, `copilot_uses_per_month` |
| `limit_value` | integer | Valor del limite (-1 = ilimitado) | `5` |
| `description` | string | Descripcion para el admin | `Maximo 5 productos en plan gratuito` |
| `upgrade_message` | string | Mensaje mostrado al alcanzar limite | `Has alcanzado el limite de 5 productos...` |
| `expected_conversion` | float | Tasa de conversion esperada (0-1) | `0.35` |
| `weight` | integer | Orden de visualizacion | `0` |
| `status` | boolean | Activo/Inactivo | `true` |

### 5.2 Archivos a Crear

```
ecosistema_jaraba_core/
├── src/Entity/FreemiumVerticalLimitInterface.php
├── src/Entity/FreemiumVerticalLimit.php
├── src/Form/FreemiumVerticalLimitForm.php
├── src/FreemiumVerticalLimitListBuilder.php
├── config/schema/ecosistema_jaraba_core.freemium_vertical_limit.schema.yml
└── config/install/
    ├── ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_free_products.yml
    ├── ecosistema_jaraba_core.freemium_vertical_limit.agroconecta_free_orders_per_month.yml
    ├── ... (15+ archivos)
```

### 5.3 Navegacion Admin

- **Menu**: `/admin/structure/freemium-limits` (parent: `system.admin_structure`, weight: -2)
- **Accion**: Boton "Añadir limite freemium" en la coleccion
- **Permiso**: `administer freemium limits` (restrict access: TRUE)

---

## 6. Componente 2: UpgradeTriggerService

### 6.1 Proposito

Servicio central que se invoca cuando ocurre un evento que deberia incentivar el upgrade. Registra el evento para analytics y devuelve los datos necesarios para renderizar el modal de upgrade.

### 6.2 Tipos de Trigger (segun Doc 183 §4)

| Tipo | Cuando se Dispara | Mensaje Patron | Conversion Esperada |
|------|-------------------|----------------|---------------------|
| `limit_reached` | Usuario intenta crear recurso y el limite esta al 100% | `Has alcanzado el limite de {N} {recurso}. Desbloquea mas con {plan}.` | 35% |
| `feature_blocked` | Usuario hace clic en feature no disponible en su plan | `{Feature} puede hacer esto por ti. [Desbloquear]` | 28% |
| `first_sale` | Tenant registra su primera orden completada | `¡Felicidades por tu primera venta! Reduce tu comision al 5% con Pro.` | 42% |
| `competition_visible` | Dashboard muestra tenants similares con plan superior | `{N} negocios similares usan features Pro.` | 22% |
| `time_on_platform` | Tenant lleva 30 dias en la plataforma sin upgrade | `Llevas 30 dias. ¿Listo para el siguiente nivel?` | 18% |

### 6.3 Metodo Principal

```php
public function fire(string $type, TenantInterface $tenant, array $context = []): array
```

**Retorna:**
```php
[
    'should_show' => true,
    'trigger_type' => 'limit_reached',
    'title' => 'Has alcanzado tu limite',
    'message' => 'Has alcanzado el limite de 5 productos...',
    'icon' => ['category' => 'actions', 'name' => 'rocket', 'variant' => 'duotone', 'color' => 'naranja-impulso'],
    'current_plan' => ['name' => 'Free', 'features' => [...]],
    'recommended_plan' => ['name' => 'Starter', 'price' => '29€/mes', 'features' => [...]],
    'cta_primary' => ['text' => 'Upgrade ahora', 'url' => '/tenant/upgrade?plan=starter'],
    'cta_secondary' => ['text' => 'Recordarme despues', 'action' => 'dismiss'],
    'expected_conversion' => 0.35,
]
```

### 6.4 Registro en services.yml

```yaml
ecosistema_jaraba_core.upgrade_trigger:
  class: Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService
  arguments:
    - '@entity_type.manager'
    - '@database'
    - '@logger.channel.ecosistema_jaraba_core'
```

---

## 7. Componente 3: Parcial Twig _upgrade-trigger

### 7.1 Ubicacion

`web/themes/custom/ecosistema_jaraba_theme/templates/partials/_upgrade-trigger.html.twig`

### 7.2 Uso desde Cualquier Pagina

```twig
{% if upgrade_trigger and upgrade_trigger.should_show %}
  {% include '@ecosistema_jaraba_theme/partials/_upgrade-trigger.html.twig' with {
    trigger: upgrade_trigger
  } %}
{% endif %}
```

### 7.3 Estructura del Parcial

El parcial renderiza un slide-panel con:
1. **Header**: Icono duotone + titulo traducible
2. **Mensaje**: Texto contextualizado por vertical y recurso
3. **Comparativa**: Dos columnas "Plan Actual" vs "Plan Recomendado"
4. **CTAs**: Boton primario (upgrade) + boton secundario (dismiss)
5. **Social proof**: Conversion rate de tenants similares (opcional)

### 7.4 Cumplimiento i18n

Todos los textos usan `{% trans %}`:
- Titulos: `{% trans %}Desbloquea mas posibilidades{% endtrans %}`
- Botones: `{% trans %}Upgrade ahora{% endtrans %}`, `{% trans %}Recordarme despues{% endtrans %}`
- Labels: `{% trans %}Tu plan actual{% endtrans %}`, `{% trans %}Plan recomendado{% endtrans %}`

---

## 8. Componente 4: SCSS _upgrade-trigger.scss

### 8.1 Ubicacion

`web/modules/custom/ecosistema_jaraba_core/scss/_upgrade-trigger.scss`

### 8.2 Directrices Cumplidas

| Directriz | Cumplimiento |
|-----------|-------------|
| Solo `var(--ej-*)` para colores | Si: `var(--ej-color-impulse, #FF8C42)` |
| Dart Sass moderno | Si: `@use 'sass:color'`, `@use 'variables' as *` |
| Mobile-first | Si: Breakpoints 576px, 768px, 992px |
| Variables inyectables | Si: Todos los colores via CSS Custom Properties |
| No CSS directo | Si: SCSS compilado a CSS |
| Import en main.scss | Si: `@use 'upgrade-trigger'` |

---

## 9. Componente 5: Integracion con Servicios Existentes

### 9.1 PlanValidator — Resolucion de Limites Vertical-Aware

Se extiende el metodo `enforceLimit()` existente para consultar primero `FreemiumVerticalLimit`:

```
enforceLimit(tenant, action, params)
  ├── Obtener vertical del tenant
  ├── Obtener plan del tenant
  ├── Buscar FreemiumVerticalLimit(vertical, plan, feature_key)
  │   ├── SI existe → usar limit_value de la ConfigEntity
  │   └── NO existe → fallback a SaasPlan::PLAN_LIMITS (comportamiento actual)
  └── Si limite alcanzado → UpgradeTriggerService.fire('limit_reached', ...)
```

### 9.2 hook_entity_insert — Trigger "Primera Venta"

Se anade un hook en `ecosistema_jaraba_core.module` para detectar la primera orden completada:

```php
function ecosistema_jaraba_core_commerce_order_update(OrderInterface $order) {
  // Solo si la transicion es a 'completed'
  // Contar ordenes del tenant
  // Si es la primera → UpgradeTriggerService.fire('first_sale', ...)
}
```

---

## 10. Seed Data: Catalogo de Limites por Vertical

### 10.1 AgroConecta

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| `products` | 5 | -1 | -1 |
| `orders_per_month` | 10 | -1 | -1 |
| `copilot_uses_per_month` | 3 | 30 | -1 |
| `photos_per_product` | 1 | 5 | 10 |
| `commission_pct` | 10 | 8 | 5 |

### 10.2 ComercioConecta

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| `products` | 10 | -1 | -1 |
| `qr_codes` | 1 | 10 | -1 |
| `flash_offers_active` | 1 | 10 | -1 |

### 10.3 ServiciosConecta

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| `services` | 3 | -1 | -1 |
| `bookings_per_month` | 10 | -1 | -1 |

### 10.4 Empleabilidad

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| `diagnostics` | 1 | -1 | -1 |
| `offers_visible_per_day` | 10 | -1 | -1 |
| `cv_builder` | 1 | 5 | -1 |

### 10.5 Emprendimiento

| Feature | Free | Starter | Pro |
|---------|------|---------|-----|
| `bmc_drafts` | 1 | -1 | -1 |
| `calculadora_uses` | 1 | -1 | -1 |

**Total: 15 features x 3 planes = 45 configuraciones YAML**

---

## 11. Tabla de Correspondencia con Especificaciones

| Seccion Doc 183 | Componente F2 | Archivo(s) | Estado |
|------------------|--------------|------------|--------|
| §2 Estrategia General | Modelo hibrido Freemium+Trial | Existente (ReverseTrialService) | Implementado |
| §3.1 Limites AgroConecta | FreemiumVerticalLimit seed data | `config/install/...agroconecta_*.yml` | A implementar |
| §3.2 Limites ComercioConecta | FreemiumVerticalLimit seed data | `config/install/...comercioconecta_*.yml` | A implementar |
| §3.3 Limites ServiciosConecta | FreemiumVerticalLimit seed data | `config/install/...serviciosconecta_*.yml` | A implementar |
| §3.4 Limites Empleabilidad | FreemiumVerticalLimit seed data | `config/install/...empleabilidad_*.yml` | A implementar |
| §3.5 Limites Emprendimiento | FreemiumVerticalLimit seed data | `config/install/...emprendimiento_*.yml` | A implementar |
| §4 Triggers: limite alcanzado | UpgradeTriggerService type=limit_reached | `src/Service/UpgradeTriggerService.php` | A implementar |
| §4 Triggers: feature bloqueada | UpgradeTriggerService type=feature_blocked | `src/Service/UpgradeTriggerService.php` | A implementar |
| §4 Triggers: primera venta | UpgradeTriggerService type=first_sale | `src/Service/UpgradeTriggerService.php` + hook | A implementar |
| §4 Triggers: competencia | UpgradeTriggerService type=competition_visible | `src/Service/UpgradeTriggerService.php` | A implementar |
| §4 Triggers: tiempo plataforma | UpgradeTriggerService type=time_on_platform | `src/Service/UpgradeTriggerService.php` | A implementar |
| (Plan Maestro §7.3) Modal upgrade | Parcial _upgrade-trigger.html.twig | `templates/partials/_upgrade-trigger.html.twig` | A implementar |

---

## 12. Checklist de Cumplimiento de Directrices

### 12.1 Codigo

| Directriz | Cumple | Detalle |
|-----------|--------|---------|
| ConfigEntity para datos de configuracion | Si | FreemiumVerticalLimit sigue patron Feature.php |
| AdminHtmlRouteProvider para rutas | Si | Rutas CRUD auto-generadas |
| 4 YAML files (routing auto, menu, action) | Si | links.menu.yml + links.action.yml + permissions.yml |
| Navegacion en /admin/structure | Si | `/admin/structure/freemium-limits` |
| Hooks nativos Drupal (no ECA UI) | Si | `hook_commerce_order_update()` |
| Sin hardcodear configuraciones de negocio | Si | Limites en ConfigEntity, no en constantes PHP |

### 12.2 Frontend

| Directriz | Cumple | Detalle |
|-----------|--------|---------|
| i18n: `$this->t()` en PHP | Si | Todos los mensajes traducibles |
| i18n: `{% trans %}` en Twig | Si | Todos los textos del parcial |
| SCSS: Solo `var(--ej-*)` | Si | Variables inyectables con fallbacks |
| Dart Sass moderno | Si | `@use 'sass:color'`, `color.scale()` |
| Parciales Twig con `{% include %}` | Si | `_upgrade-trigger.html.twig` reutilizable |
| Modal slide-panel para acciones | Si | Usa componente slide-panel del tema |
| Mobile-first layout | Si | Breakpoints progresivos |
| Iconos SVG duotone | Si | `jaraba_icon('actions', 'rocket', ...)` |
| No emojis en interfaz | Si | Solo iconos SVG |

### 12.3 IA

No aplica directamente a esta fase (los triggers de upgrade no invocan LLMs).

### 12.4 Theming

| Directriz | Cumple | Detalle |
|-----------|--------|---------|
| Variables inyectables desde UI Drupal | Si | Colores via `var(--ej-color-impulse)` etc. |
| No CSS directo | Si | SCSS compilado |
| Import en main.scss | Si | `@use 'upgrade-trigger'` |
| Paleta oficial Jaraba | Si | impulse, corporate, innovation, success |

---

## 13. Plan de Verificacion

### 13.1 Verificacion Funcional

1. Acceder a `/admin/structure/freemium-limits` → debe mostrar tabla con 45 limites
2. Editar un limite → formulario con campos vertical, plan, feature_key, limit_value
3. Crear un nuevo limite → debe guardarse y aparecer en la lista
4. Eliminar un limite → debe desaparecer

### 13.2 Verificacion de Integracion

1. Con `drush eval`, verificar que `FreemiumVerticalLimit::load()` retorna datos
2. Verificar conteo: 45 registros (5 verticales x ~3 features x 3 planes)
3. Verificar que el `UpgradeTriggerService` esta registrado en el contenedor

### 13.3 Verificacion Visual

1. En navegador, verificar que el modal de upgrade se renderiza correctamente
2. Comprobar responsive en movil (Chrome DevTools)
3. Comprobar que los iconos duotone se muestran

---

## 14. Registro de Cambios

| Version | Fecha | Cambio |
|---------|-------|--------|
| 1.0.0 | 2026-02-12 | Creacion inicial del plan de implementacion |
