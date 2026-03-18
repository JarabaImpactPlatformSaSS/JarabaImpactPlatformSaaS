# Aprendizaje #191 — PHANTOM-ARG-001 v2 + Pre-commit Safeguard + ACCESS-RETURN-TYPE-001

> Fecha: 2026-03-18 | Módulos: 22 módulos custom, Drupal core + 7 contrib | Reglas: PHANTOM-ARG-001, ACCESS-RETURN-TYPE-001, HUSKY-EXEC-001

## Contexto

Tres líneas de trabajo convergieron en esta sesión: (1) hardening del validador de argumentos fantasma en services.yml con detección bidireccional y 12 tests de regresión, (2) descubrimiento de que el pre-commit hook llevaba semanas inactivo por permisos incorrectos, y (3) migración masiva de return types en 68 AccessControlHandlers para cumplir PHPStan Level 6.

Adicionalmente, actualización de Drupal 11 core + 7 módulos contrib (ai, commerce, eca, webform, geocoder, geofield, domain, bpmn_io) — 773 archivos.

## Hallazgos

### 1. PHANTOM-ARG-001 v2 — Detección Bidireccional + Parser Fix

**Problema original**: `validate-phantom-args.php` v1 solo detectaba args extra en YAML (YAML > constructor). No detectaba args faltantes (YAML < required params).

**Bug del parser**: Una trailing comma seguida de inline comment en el constructor PHP era parseada como parámetro adicional:

```php
public function __construct(
  private readonly EntityTypeManagerInterface $entityTypeManager,
  private readonly Connection $database, // AUDIT-CONS-N10: added
) {
```

El regex capturaba `// AUDIT-CONS-N10: added` como un parámetro extra, generando 7 falsos positivos en servicios de feature gates.

**Fix aplicado**:
- Strip inline comments (`// ...`) del bloque de parámetros antes de parsear
- Filtrar entradas que no contienen `$` (no son variables PHP válidas)
- Detección bidireccional: phantom (extra en YAML) + missing (faltante en YAML vs required sin default)

**3 violaciones reales corregidas**:
- `AgroAnalyticsService`: faltaban `@database` y `@logger.factory`
- `StripeInvoiceService`: faltaba `@http_client`
- `QuoteEstimatorService`: faltaba `@http_client`

**Arquitectura testeable**: El script usa un main guard (`basename($argv[0])`) que permite importar las funciones de parsing desde tests unitarios sin ejecutar el main. 12 tests de regresión cubren: trailing commas, inline comments, optional params, nested parens, empty constructors, block comments, single-line constructors.

### 2. Pre-commit Hook Silenciosamente Inactivo

**Descubrimiento**: `.husky/pre-commit` tenía permisos `644` (no ejecutable). Git ignora hooks sin permiso de ejecución **sin ningún warning ni error** — simplemente no los ejecuta.

**Impacto**: Todas las validaciones pre-commit (SCSS compile check, doc guard, lint-staged) NO se ejecutaban. Los commits pasaban sin verificación local.

**Fix**: `chmod 755 .husky/pre-commit`

**Optimización lint-staged**: Al activar el hook, se optimizó la configuración para que cambios en `*.services.yml` disparen solo los 4 validadores DI relevantes (validate-phantom-args, validate-optional-deps, validate-circular-deps, validate-logger-injection) en lugar del validate-all.sh completo. Tiempo: ~3s vs ~20s.

### 3. ACCESS-RETURN-TYPE-001 — 68 Handlers Migrados

**Root cause**: `EntityAccessControlHandler::checkAccess()` en Drupal core retorna `AccessResultInterface`. Los handlers custom que declaran `): AccessResult` (tipo más restrictivo) causan error PHPStan Level 6:

```
Return type (AccessResult) of method X::checkAccess() should be compatible
with return type (AccessResultInterface) of method Y::checkAccess()
```

**Migración**: 68 `AccessControlHandler` en 22 módulos custom actualizados de `AccessResult` a `AccessResultInterface` como return type de `checkAccess()`.

### 4. Drupal Core + Contrib Update

773 archivos actualizados:
- Drupal 11 core (security patches + htmx + navigation + views + workspaces)
- ai module (major: Gen 2 agents, observability, chatbot UI refresh)
- commerce (checkout + order + promotion)
- eca (migrate + workflow + render hooks)
- webform, geocoder, geofield, domain, bpmn_io, modeler_api

## Reglas Nuevas/Actualizadas

### PHANTOM-ARG-001 v2 (actualizada)
- **Antes**: Solo detectaba args extra en YAML
- **Ahora**: Detección bidireccional — phantom (YAML > constructor) Y missing (YAML < required)
- **Parser robusto**: Strip inline comments, filter non-variable entries, handle trailing commas
- **Validación**: `php scripts/validation/validate-phantom-args.php`
- **Tests**: 12 regression tests en `tests/src/Unit/Validation/PhantomArgsTest.php`

### HUSKY-EXEC-001 (nueva)
Git ignora hooks sin permisos de ejecución SILENCIOSAMENTE. Verificar siempre:
```bash
ls -la .husky/pre-commit  # debe ser -rwxr-xr-x
```

### ACCESS-RETURN-TYPE-001 (completada)
`checkAccess()` DEBE declarar `: AccessResultInterface` (NO `: AccessResult`). 68/68 handlers migrados. PHPStan Level 6 limpio.

### LINT-STAGED-DI-001 (nueva)
Cambios en `*.services.yml` disparan 4 validadores DI específicos via lint-staged, no el suite completo. Mantiene el pre-commit rápido (~3s).

## Regla de Oro #132

> El pre-commit hook DEBE ser ejecutable (`chmod +x`). Git ignora hooks sin permisos de ejecución SILENCIOSAMENTE — sin warning, sin error. Verificar con `ls -la .husky/pre-commit`. Un safeguard que no se ejecuta es peor que no tener safeguard: da falsa sensación de seguridad.

## Cross-refs

- PHANTOM-ARG-001 v1: `docs/tecnicos/aprendizajes/2026-03-04_*.md`
- OPTIONAL-CROSSMODULE-001: misma sesión de hardening DI
- CONTAINER-DEPS-002: validador de dependencias circulares
- LOGGER-INJECT-001: validador de inyección de logger
- Safeguard System: `memory/safeguard-canvas.md`, CLAUDE.md § SAFEGUARD SYSTEM
- ACCESS-RETURN-TYPE-001: CLAUDE.md § PHP rules
- Scripts: `scripts/validation/validate-phantom-args.php`, `scripts/validation/validate-all.sh`
- Pre-commit: `.husky/pre-commit`, `package.json` (lint-staged config)
