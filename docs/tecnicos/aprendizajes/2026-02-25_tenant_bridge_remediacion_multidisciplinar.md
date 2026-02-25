# Aprendizaje #122 — Remediacion Tenant 11 Fases: TenantBridge + Billing + Isolation + CI

**Fecha:** 2026-02-25
**Modulos afectados:** `ecosistema_jaraba_core`, `jaraba_page_builder`, `jaraba_billing`
**Contexto:** Remediacion sistematica de la confusion Tenant vs Group detectada en la auditoria profunda de logica de negocio y tecnica del SaaS. La plataforma usaba Tenant IDs en storages de Group y viceversa en 14 puntos del codigo de billing, acceso y quotas.
**Impacto:** 14 bugs de billing entity type corregidos, acceso cross-tenant eliminado, CI con Kernel tests operativo, 5 tests nuevos
**Commits:** `96dc2bb4` (remediacion 11 fases), `0bd84663` (fix tests group→tenant rename)
**Reglas de oro:** #35, #36, #37

---

## 1. Problema

### Confusion sistematica Tenant vs Group

La plataforma tiene dos tipos de entidad relacionados pero distintos:
- **Tenant:** Propietario de billing (suscripciones, pagos, quotas, planes Stripe).
- **Group:** Propietario de aislamiento de contenido (paginas, archivos, permisos de grupo).

El codigo de billing cargaba `$this->entityTypeManager->getStorage('group')->load($tenantId)` — usando Tenant IDs para cargar entidades Group. Esto funcionaba accidentalmente cuando los IDs coincidian (tenant_id=1, group_id=1), pero fallaba con datos reales donde los IDs divergen.

### 14 puntos afectados en 6 ficheros

| Fichero | Instancias | Tipo de error |
|---------|-----------|---------------|
| `QuotaManagerService.php` | 3 | `getStorage('group')` con tenant logic |
| `BillingController.php` | 4 | `getStorage('group')` en billing |
| `BillingService.php` | 3 | `getStorage('group')` en suscripciones |
| `StripeWebhookController.php` | 2 | `getStorage('group')` en webhooks |
| `SaasPlan.php` | 2 | `getStorage('group')` en plan management |

### Acceso cross-tenant

`PageContentAccessControlHandler` no verificaba que la entidad perteneciera al tenant del usuario actual para operaciones `update`/`delete`. Un usuario autenticado de Tenant A podia teoricamente editar paginas de Tenant B.

### Ausencia de Kernel tests en CI

El pipeline de CI solo ejecutaba Unit tests. Los Kernel tests (que requieren BD real) estaban definidos pero no se ejecutaban, dejando sin validar la integracion de schemas, queries y servicios con DI real.

---

## 2. Solucion por Fases

### Fase 1 — TenantBridgeService (nuevo servicio)

Servicio centralizado para resolver entre Tenant y Group:

```php
class TenantBridgeService {
  public function getTenantForGroup(GroupInterface $group): TenantInterface;
  public function getGroupForTenant(TenantInterface $tenant): GroupInterface;
  public function getTenantIdForGroup(int $groupId): int;
  public function getGroupIdForTenant(int $tenantId): int;
}
```

**Decision de diseno:** Servicio dedicado en lugar de metodos en las entidades. Permite inyeccion, mockeo facil, y punto unico de cambio si la relacion Tenant↔Group evoluciona.

### Fase 2 — Registro en services.yml

```yaml
ecosistema_jaraba_core.tenant_bridge:
  class: Drupal\ecosistema_jaraba_core\Service\TenantBridgeService
  arguments: ['@entity_type.manager']
```

### Fase 3 — QuotaManagerService migrado al bridge

De `$this->entityTypeManager->getStorage('group')->load($tenantId)` a `$this->tenantBridge->getTenantForGroup($group)`. El bridge resuelve la indirection correctamente.

### Fase 4 — Billing entity type fix (BillingController)

Todas las llamadas a `getStorage('group')` en contexto de billing cambiadas a `getStorage('tenant')`.

### Fase 5 — BillingService fix

Mismo patron: `getStorage('group')` → `getStorage('tenant')` en servicios de suscripcion.

### Fase 6 — StripeWebhookController fix

Webhooks de Stripe ahora cargan la entidad Tenant correcta.

### Fase 7 — SaasPlan entity type fix

Plan management opera sobre Tenant entities.

### Fase 8 — PageContentAccessControlHandler con DI

Refactorizado para implementar `EntityHandlerInterface`:
- `createInstance()` inyecta `TenantContextService`
- `isSameTenant()` verifica `(int) entity.tenant_id === (int) currentTenantId`
- Politica: view=publico si publicado, update/delete=isSameTenant() + permiso

### Fase 9 — DefaultAccessControlHandler → DefaultEntityAccessControlHandler

Rename para seguir convencion Drupal. Actualizada la referencia `access` en la anotacion `@ContentEntityType`.

### Fase 10 — PathProcessor + TenantContextService

- `PathProcessorPageContent` inyecta `TenantContextService` (`@?` opcional) para filtrar por tenant en la resolucion de paths.
- `TenantContextService::getCurrentTenantId()` retorna `?int` (NULL cuando el usuario no tiene grupo, no lanza excepcion).

### Fase 11 — CI + Tests + Cleanup

- Job `kernel-test` en GitHub Actions con servicio MariaDB 10.11.
- 5 tests: `TenantBridgeServiceTest` (Unit), `QuotaManagerServiceTest` actualizado (Unit), `PageContentAccessTest` (Kernel), `PathProcessorPageContentTest` (Kernel), `DefaultEntityAccessControlHandlerTest` (Unit rename).
- Scripts de mantenimiento movidos de `web/` a `scripts/maintenance/` (seguridad).
- `test_paths.sh` limpiado.

---

## 3. Reglas Derivadas

### TENANT-BRIDGE-001 (P0)
Todo servicio que necesite resolver entre Tenant y Group DEBE usar `TenantBridgeService`. NUNCA cargar `getStorage('group')` con Tenant IDs ni viceversa.

### TENANT-ISOLATION-ACCESS-001 (P0)
Todo AccessControlHandler de entidades con `tenant_id` DEBE verificar `isSameTenant()` para update/delete. Las paginas publicadas (view) son publicas.

### CI-KERNEL-001 (P0)
El pipeline de CI DEBE ejecutar Unit + Kernel tests. El job kernel-test requiere MariaDB 10.11.

---

## 4. Patron Replicable

### Como crear un servicio bridge entre entity types

1. **Identificar la confusion:** Buscar `getStorage('entity_a')` donde se pasan IDs de entity_b.
2. **Crear el bridge service:** Metodos bidireccionales (A→B, B→A) con error handling.
3. **Registrar en services.yml:** Dependencia de `entity_type.manager`.
4. **Migrar consumidores:** Reemplazar `getStorage()` directo por llamadas al bridge.
5. **Actualizar tests:** Los mocks de `EntityTypeManagerInterface` deben reflejar los nuevos entity type keys.

### Como anadir tenant isolation a un access handler

1. **Implementar `EntityHandlerInterface`:** `createInstance()` con DI.
2. **Inyectar `TenantContextService`:** Para resolver el tenant actual.
3. **Implementar `isSameTenant()`:** `(int) entity.tenant_id === (int) currentTenantId`.
4. **Manejar NULL:** `getCurrentTenantId()` puede retornar NULL (usuario sin grupo).
5. **Politica clara:** view=publico, update/delete=mismo tenant + permiso.

---

## 5. Verificacion

```bash
# Verificar que no quedan getStorage('group') en contexto de billing
grep -rn "getStorage('group')" web/modules/custom/jaraba_billing/ --include="*.php"
# Debe retornar 0 resultados (billing solo usa 'tenant')

# Verificar que TenantBridgeService esta registrado
grep -rn "tenant_bridge" web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml

# Verificar tenant isolation en access handler
grep -rn "isSameTenant" web/modules/custom/jaraba_page_builder/src/ --include="*.php"

# Ejecutar tests
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Kernel

# Verificar CI pipeline tiene kernel-test job
grep -rn "kernel-test" .github/workflows/
```

---

## 6. Referencias

- **Directrices:** v70.0.0 (3 reglas nuevas: TENANT-BRIDGE-001, TENANT-ISOLATION-ACCESS-001, CI-KERNEL-001)
- **Arquitectura:** v71.0.0 (2 ASCII boxes: TENANT BRIDGE + TENANT ISOLATION)
- **Flujo:** v25.0.0 (3 patrones + reglas de oro #35, #36, #37)
- **Indice:** v94.0.0 (bloque HAL-01 a HAL-04)
- **Auditoria base:** `docs/tecnicos/auditorias/20260223-Auditoria_Profunda_Logica_Negocio_Tecnica_SaaS_v1_Codex.md`
- **Plan remediacion:** `docs/implementacion/20260223-Plan_Remediacion_Auditoria_Logica_Negocio_Tecnica_SaaS_v1_Codex.md`
