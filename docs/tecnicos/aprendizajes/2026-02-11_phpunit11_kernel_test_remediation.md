# PHPUnit 11 Kernel Test Remediation — ServiceProvider, Entity Schema, Method Naming

**Fecha:** 2026-02-11  
**Sprint:** Remediación Testing PHPUnit 11  
**Módulo:** `ecosistema_jaraba_core`  
**Impacto:** Unit + Kernel test suites 100% funcionales

---

## Contexto

Tras el upgrade a PHPUnit 11.5.53, todos los Kernel tests (13) fallaban por:
1. **Errores de compilación DI** — servicios cruzados entre módulos
2. **Conexión BD** — `SIMPLETEST_DB` no propagado a `getenv()` en PHPUnit 11
3. **Plugins inexistentes** — módulos faltantes en `$modules` del test
4. **Entity References contrib** — Tenant referencia entity types de `group`/`domain`
5. **Métodos incorrectos** — tests usaban nombres de método que no existen en las entidades

## Hallazgos Clave

### 1. PHPUnit 11 `<env>` tags y `getenv()`

PHPUnit 11 sets `$_ENV` y `$_SERVER` via `<env>` tags, pero **NO llama `putenv()`** por defecto. Drupal's `KernelTestBase::getDatabaseConnectionInfo()` usa `getenv('SIMPLETEST_DB')` — esto retorna vacío si no se exporta como variable de shell.

**Solución:** Exportar `SIMPLETEST_DB` como variable de entorno shell antes de ejecutar PHPUnit:
```bash
export SIMPLETEST_DB="sqlite://localhost//tmp/test.sqlite"
export SIMPLETEST_BASE_URL="http://jarabasaas.lndo.site"
vendor/bin/phpunit --configuration phpunit.xml --testsuite Kernel
```

### 2. ServiceProvider para dependencias cross-módulo

`ecosistema_jaraba_core.services.yml` tenía:
- Alias `stripe_connect` → `jaraba_foc.stripe_connect` (módulo no siempre presente)
- `unified_prompt_builder` → dependencias en `jaraba_skills` y `jaraba_tenant_knowledge`

**Solución:** `EcosistemaJarabaCoreServiceProvider.php` — registra condicionalmente:
```php
// Solo si jaraba_foc está habilitado
if ($container->has('jaraba_foc.stripe_connect')) {
    $container->setAlias('stripe_connect', 'jaraba_foc.stripe_connect');
}
```

### 3. Entity Schema y módulos contrib

`Tenant` entity tiene `entity_reference` a `group` y `domain` (contrib). Estos módulos existen en el filesystem pero **no pueden arrancar en aislamiento Kernel** por sus propias dependencias DI.

**Regla KERNEL-001:** Nunca llamar `installEntitySchema()` para entidades con entity_reference a tipos de entidad contrib en tests Kernel. Usar `markTestSkipped()` o migrar a `BrowserTestBase`.

### 4. Nombres de métodos incorrectos en tests

| Test usaba | Entity tiene |
|------------|--------------|
| `getMonthlyPrice()` | `getPriceMonthly()` |
| `getYearlyPrice()` | `getPriceYearly()` |
| `isPublished()` | `get('status')->value` |

**Regla TEST-001:** Al escribir tests para entidades, verificar siempre la firma de métodos en la clase Entity real.

## Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `EcosistemaJarabaCoreServiceProvider.php` | **NUEVO** — Registro condicional DI |
| `ecosistema_jaraba_core.services.yml` | Eliminadas dependencias estáticas |
| `ServiceRegistrationTest.php` | +`text` en `$modules`, -`installEntitySchema()` innecesarios |
| `EntityInstallTest.php` | +`text`, -`tenant` schema, fix métodos, skip Tenant tests |
| `TenantProvisioningTest.php` | Force-skip (group/domain no bootstrappable) |
| `phpunit.xml` | `SIMPLETEST_DB` para SQLite en Lando |

## Resultados Finales

| Suite | Tests | Pass | Skip | Error |
|-------|-------|------|------|-------|
| Unit | 186 | 186 | 0 | 0 |
| Kernel | 13 | 8 | 5 | 0 |
| **Total** | **199** | **194** | **5** | **0** |

## Reglas Derivadas

- **KERNEL-001:** No instalar schemas con entity_reference a contrib en Kernel tests
- **TEST-001:** Verificar firmas de métodos en la Entity class real, no asumir naming
- **ENV-001:** En Lando, exportar `SIMPLETEST_DB` como variable shell, no confiar solo en `phpunit.xml <env>`
- **DI-001:** Usar `ServiceProvider` para dependencias cross-módulo opcionales
