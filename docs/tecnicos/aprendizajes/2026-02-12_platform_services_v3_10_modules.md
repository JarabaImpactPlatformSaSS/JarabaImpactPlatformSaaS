# Platform Services v3 — 10 Módulos Dedicados

**Fecha:** 2026-02-12
**Tipo:** Implementación masiva
**Módulos afectados:** jaraba_agent_flows, jaraba_pwa, jaraba_onboarding, jaraba_usage_billing, jaraba_integrations, jaraba_customer_success, jaraba_tenant_knowledge, jaraba_security_compliance, jaraba_analytics, jaraba_whitelabel
**Archivos totales:** 542

---

## Contexto

El plan Platform Services v1 (20260210) propuso crear 10 módulos dedicados transversales para servicios de plataforma (Docs 108-117). La implementación real hasta ese momento había distribuido funcionalidad en módulos existentes, alcanzando ~72% de completitud. La decisión fue implementar los 10 módulos como módulos Drupal 11 independientes, activables/desactivables, con calidad clase mundial.

## Resultado

542 archivos implementados en 10 módulos (6 nuevos + 4 extendidos):

| Módulo | Tipo | Archivos | Entidades | Servicios | Controllers |
|--------|------|----------|-----------|-----------|-------------|
| jaraba_agent_flows | Nuevo | 38 | 3 | 5 | 2 |
| jaraba_pwa | Nuevo | 32 | 2 | 5 | 2 |
| jaraba_onboarding | Nuevo | 34 | 2 | 5 | 2 |
| jaraba_usage_billing | Nuevo | 36 | 3 | 5+QW | 2 |
| jaraba_integrations | Extendido | 66 | 4 | 8 | 7 |
| jaraba_customer_success | Extendido | 65 | 5 | 6 | 5 |
| jaraba_tenant_knowledge | Extendido | 91 | 9 | 8 | 8 |
| jaraba_security_compliance | Nuevo | 40 | 3 | 4 | 3 |
| jaraba_analytics | Extendido | 86 | 9 | 9 | 10 |
| jaraba_whitelabel | Nuevo+migración | 54 | 4 | 5 | 5 |
| **Total** | | **542** | **44** | **60+** | **46** |

## Lecciones aprendidas

### 1. Patrón Controller en Drupal 11 + PHP 8.4 (CONTROLLER-001)

**Problema:** Controllers que extienden `ControllerBase` y declaran `protected EntityTypeManagerInterface $entityTypeManager` en el constructor con promoted properties causan `Cannot redeclare property` en PHP 8.4, ya que `ControllerBase` ya define la propiedad.

**Solución:** Dos patrones válidos:
- **Patrón A (assignment):** Constructor con parámetro NO promoted, asignar manualmente: `$this->entityTypeManager = $entity_type_manager;`
- **Patrón B (lazy-loading):** Usar `$this->entityTypeManager()` method call (lazy-loading desde ControllerBase). No requiere constructor assignment.

**Regla:** NUNCA usar promoted properties en constructores de Controllers que extienden ControllerBase para propiedades que ya existen en la clase padre.

### 2. Migración de entidades entre módulos (MIGRATION-001)

**Problema:** Mover entidades (AuditLog, ComplianceAssessment, Reseller, PushSubscription) de `ecosistema_jaraba_core` a módulos dedicados sin romper datos existentes.

**Solución:**
- Crear las entidades en el módulo destino con el mismo `id` en la anotación `@ContentEntityType`
- El hook_install del nuevo módulo maneja la migración de tabla si los datos existen
- En ecosistema_jaraba_core, mantener alias de backward-compatibility si otros módulos referencian las entidades originales

**Regla:** Siempre verificar cross-references antes de migrar. Usar `drush entity:updates` para validar schema.

### 3. EventSubscriber para resolución de dominio White-Label (WHITELABEL-001)

**Problema:** Resolver configuración white-label basada en el dominio de la request entrante, antes de que los controllers procesen.

**Solución:** `WhitelabelRequestSubscriber` implementa `EventSubscriberInterface`, escucha `KernelEvents::REQUEST` con prioridad alta. Resuelve el dominio custom, carga la config del tenant correspondiente, y la adjunta al request attributes para uso posterior.

**Patrón:**
```php
public static function getSubscribedEvents(): array {
  return [KernelEvents::REQUEST => ['onRequest', 100]];
}
```

### 4. Pipeline de Usage Billing (USAGE-001)

**Patrón implementado:**
```
UsageEvent → hourly aggregate → daily aggregate → monthly aggregate → Stripe sync
```

- `UsageIngestionService`: Ingesta con idempotency key para evitar duplicados
- `UsageAggregatorService`: Agregación por ventana temporal vía Drupal Queue API
- `UsageStripeSyncService`: Sync automático a Stripe Billing usage records
- `UsageAlertService`: Alertas por umbral configurable por tenant/métrica

**Regla:** Los UsageEvents son append-only (nunca se modifican ni eliminan). Las agregaciones se calculan incrementalmente.

### 5. Extensión de módulos existentes vs creación nuevos (ARCH-001)

**Decisión:** 4 de los 10 módulos se implementaron como extensiones de módulos existentes (jaraba_integrations, jaraba_customer_success, jaraba_tenant_knowledge, jaraba_analytics) porque:
- Ya tenían infraestructura base (entidades, services.yml, routing.yml)
- Compartían dominio funcional
- No justificaba la complejidad de crear un módulo separado y luego referenciar el existente

**Criterio para decidir:** Si el módulo existente ya tiene >3 entidades y >3 servicios en el mismo dominio funcional, extender. Si es funcionalidad completamente nueva o requiere migración, crear nuevo.

### 6. Verificación de patrones en 542 archivos (QA-002)

**Proceso de verificación post-implementación:**
1. `declare(strict_types=1)` en todos los PHP files — verificado con grep
2. Constructor pattern en Controllers — verificado manualmente (7 controllers con lazy-loading validados)
3. CSS tokens `var(--ej-*)` — verificado en todos los CSS
4. `Drupal.behaviors` + `once()` — verificado en todos los JS
5. Templates Twig sin `page.content` — verificado
6. Entidades con `EntityChangedTrait` y `tenant_id` — verificado

**Regla:** Después de implementación masiva (>100 archivos), ejecutar verificación de patrones automatizada antes de considerar completado.

### 7. Service Worker PWA con estrategias de cache (PWA-001)

**Patrón:** Service Worker implementa múltiples estrategias de cache:
- `CacheFirst` para assets estáticos (CSS, JS, imágenes)
- `NetworkFirst` para API calls y páginas dinámicas
- `StaleWhileRevalidate` para contenido semi-estático (templates, configs)
- `NetworkOnly` para operaciones críticas (pagos, autenticación)

`PwaCacheStrategyService` configura las estrategias por ruta/recurso. `PwaSyncManagerService` gestiona la cola de acciones pendientes cuando offline.

---

## Reglas generadas

| Regla | Descripción |
|-------|-------------|
| CONTROLLER-001 | No usar promoted properties en Controllers que extienden ControllerBase para propiedades heredadas |
| MIGRATION-001 | Verificar cross-references antes de migrar entidades entre módulos |
| WHITELABEL-001 | EventSubscriber con prioridad alta (100) para resolución dominio pre-controller |
| USAGE-001 | UsageEvents append-only, agregaciones incrementales |
| ARCH-001 | Extender módulo existente si >3 entities + >3 services en mismo dominio |
| QA-002 | Verificación automatizada de patrones post-implementación masiva |
| PWA-001 | Service Worker con estrategias cache diferenciadas por tipo de recurso |

---

## Archivos de referencia

- **Patrón entidad:** `jaraba_whitelabel/src/Entity/WhitelabelConfig.php`
- **Patrón servicio:** `jaraba_whitelabel/src/Service/ConfigResolverService.php`
- **Patrón controller:** `jaraba_whitelabel/src/Controller/BrandingWizardController.php`
- **Patrón EventSubscriber:** `jaraba_whitelabel/src/EventSubscriber/WhitelabelRequestSubscriber.php`
- **Patrón QueueWorker:** `jaraba_usage_billing/src/Plugin/QueueWorker/UsageAggregationWorker.php`
- **Patrón template:** `jaraba_whitelabel/templates/jaraba-branding-wizard.html.twig`
- **Patrón test:** `jaraba_whitelabel/tests/src/Unit/Service/ConfigResolverServiceTest.php`
- **Doc implementación:** `docs/implementacion/20260212-Plan_Implementacion_Platform_Services_f108_f117_v3.md`
