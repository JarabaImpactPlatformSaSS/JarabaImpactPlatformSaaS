# Aprendizaje #156 — Sistema de Validacion Arquitectonica Automatizada — 6 Scripts + 4 Puntos de Control

**Fecha:** 2026-03-03
**Contexto:** Tras activar 41 modulos, 3 categorias de errores runtime pasaron desapercibidos por PHPStan/PHPCS/PHPUnit: DI type mismatches (22+ servicios), route-controller mismatches (16 metodos), query chain bugs (QUERY-CHAIN-001). Se implemento un sistema de validacion cruzada YAML↔PHP con auto-descubrimiento dinamico.
**Reglas de oro:** #94 (validacion cruzada inter-archivo), #95 (ECA-EVENT-001 anotacion completa), #96 (pre-existing violations in baseline)

---

## Problema

Los 3 pilares de calidad existentes (PHPStan Level 6, PHPCS Drupal Coding Standards, PHPUnit) tienen puntos ciegos criticos:

1. **PHPStan** analiza PHP pero NO cruza `services.yml` contra constructor type hints. Un servicio inyectando `@logger.factory` (LoggerChannelFactory) donde el constructor espera `LoggerInterface` pasa PHPStan pero explota en runtime con TypeError.
2. **PHPCS** valida estilo y convenciones, no coherencia inter-archivo. Una ruta referenciando `Namespace\Controller::methodName` que no existe pasa PHPCS pero mata `drush cr` con error fatal.
3. **PHPUnit** necesita Kernel tests para cada servicio. Con 80+ modulos, la cobertura insuficiente deja pasar bugs como `->addExpression()->execute()` (QUERY-CHAIN-001) donde el mock con `willReturnSelf()` oculta que el metodo real retorna string.
4. **ECA plugins** con `@EcaEvent` annotation incompleta (sin `event_name`) generan warnings en runtime aunque tengan el valor en `definitions()`.

## Solucion: 6 Scripts de Validacion Estatica Cross-File

### Script 1: `validate-services-di.php` (DI-TYPE-001)
- Parsea TODOS los `*.services.yml` (glob dinamico, no lista hardcoded)
- Para cada `@service_ref`, resuelve la clase esperada via mapping conocido (~30 servicios core)
- Compara contra constructor type hints del PHP (regex sobre use statements + constructor params)
- **Heuristica clave**: `@logger.factory` solo valido si param acepta `LoggerChannelFactoryInterface`, no `LoggerInterface`
- 781 servicios analizados, 0 errores, 1 warning (QueueFactory)

### Script 2: `validate-routing.php` (ROUTE-CTRL-001)
- Parsea TODOS los `*.routing.yml`
- Para `_controller`, `_form`, `_title_callback`: extrae FQCN::method, resuelve PSR-4, verifica existencia
- **Leccion critica**: inicialmente 72 falsos positivos por intentar validar clases de core/contrib (`Drupal\Core\*`, `Drupal\system\*`). Solucion: filtro `isCustomClass()` que solo valida namespaces `jaraba_*` y `ecosistema_*`
- 2127 referencias de ruta verificadas

### Script 3: `validate-entity-integrity.php` (ENTITY-INTEG-001)
- 6 checks: ENTITY-001, AUDIT-CONS-001, ENTITY-PREPROCESS-001, FIELD-UI-SETTINGS-TAB-001, VIEWS-DATA-001, ECA-EVENT-001
- **ECA-EVENT-001 (añadido como salvaguarda)**: verifica que toda clase con `@EcaEvent` tiene `event_name` en la anotacion — no basta con tenerlo en `definitions()`
- Descubrio 6 violaciones pre-existentes: 3 missing SettingsForm, 3 missing admin_permission

### Script 4: `validate-query-chains.php` (QUERY-CHAIN-001)
- Detecta patron peligroso: `->addExpression(...)->execute()`, `->join(...)->execute()`, `->leftJoin(...)->execute()`
- Strips comments, splits por `;`, regex multilinea
- Patron: `->(?:addExpression|(?:left|inner)?[Jj]oin|addField)\([^)]*\)\s*->`

### Script 5: `validate-config-sync.sh` (CONFIG-SYNC-001)
- Detecta config/install YMLs de modulos activos que faltan en config/sync
- **False positive corregido**: `ecosistema_jaraba_theme` (tema, no modulo) necesita skip explicito

### Script 6: `validate-all.sh` (orquestador)
- Modos: `--fast` (<3s, para pre-commit) y `--full` (completo)
- `--fast`: MODULE-ORPHAN-001 + ROUTE-CTRL-001 + QUERY-CHAIN-001
- `--full`: todos los 6 checks
- Exit code = numero de checks fallidos

## Integracion en 4 Puntos de Control

1. **Pre-commit hook**: Condicional — solo ejecuta si archivos staged incluyen `*.services.yml`, `*.routing.yml`, `src/Entity/*.php`, `core.extension.yml`
2. **CI pipeline** (`ci.yml`): Step "Architectural validation" antes de PHPStan
3. **Deploy pipeline** (`deploy.yml`): Step pre-deploy antes de rsync
4. **Lando tooling**: `lando validate` y `lando validate-fast`

## Violaciones Pre-existentes Corregidas

| Regla | Archivo | Fix |
|-------|---------|-----|
| ROUTE-CTRL-001 | jaraba_ai_agents | Creado `AgentSettingsForm.php` |
| ROUTE-CTRL-001 | jaraba_credentials | Creado `CredentialsSettingsForm.php` |
| ROUTE-CTRL-001 | jaraba_mentoring | Creado `MentoringSettingsForm.php` |
| AUDIT-CONS-001 | NegotiationSession.php | Añadido `admin_permission` |
| AUDIT-CONS-001 | AnalyticsDaily.php | Añadido `admin_permission` |
| AUDIT-CONS-001 | AnalyticsEvent.php | Añadido `admin_permission` |

## Bugs Runtime Adicionales Descubiertos y Corregidos

| Archivo | Bug | Fix |
|---------|-----|-----|
| ConversationCreatedEvent.php | `@EcaEvent` sin `event_name` | Añadido `event_name = "jaraba_messaging.conversation_created"` |
| MessageSentEvent.php | `@EcaEvent` sin `event_name` | Añadido `event_name = "jaraba_messaging.message_sent"` |
| MessageReadEvent.php | `@EcaEvent` sin `event_name` | Añadido `event_name = "jaraba_messaging.message_read"` |
| ChurnPredictorService.php | `$wContract` undefined, `$contractScore` no incluido en risk | Añadido `$wContract` y incluido en formula |

## Lecciones Clave

1. **PHPStan no es suficiente para coherencia inter-archivo**: Los errores mas peligrosos en Drupal cruzan fronteras YAML↔PHP. Necesitas validacion estatica que entienda ambos mundos.
2. **False positives envenenan la confianza**: 72 falsos positivos de core/contrib harian que nadie confie en el script. El filtro `isCustomClass()` fue critico.
3. **Las violaciones pre-existentes invalidan CI**: Si introduces un validador que falla en la baseline existente, CI se rompe. Corregir las 6 violaciones pre-existentes ANTES de integrar en CI.
4. **Las anotaciones Drupal son contratos**: `@EcaEvent` sin `event_name` en la anotacion genera warnings aunque `definitions()` lo tenga. La anotacion es el contrato publico.
5. **Auto-descubrimiento > listas hardcoded**: Todos los scripts usan `glob()` para descubrir modulos, servicios, rutas y entidades. Al crear un modulo nuevo, se valida automaticamente.
6. **Mock `willReturnSelf()` oculta bugs**: PHPUnit con `willReturnSelf()` para `addExpression()` y `join()` crea falsa confianza — estos metodos retornan strings en Drupal real.

## Reglas de Oro

- **#94**: ARCH-VALIDATE-001 — Validacion arquitectonica automatizada: los errores mas peligrosos cruzan fronteras YAML↔PHP (services.yml → constructor, routing.yml → controller). PHPStan/PHPCS no los detectan. Implementar scripts de validacion cruzada con auto-descubrimiento via glob, integrados en pre-commit (--fast <3s), CI y deploy (--full). Corregir violaciones pre-existentes ANTES de integrar en CI.
- **#95**: ECA-EVENT-001 — Todo plugin `@EcaEvent` DEBE tener `event_name` en la ANOTACION, no solo en `definitions()`. Sin `event_name` en la anotacion, ECA genera warnings en runtime al construir el event map. El validador `validate-entity-integrity.php` lo detecta automaticamente.
- **#96**: BASELINE-CLEAN-001 — Al introducir un nuevo validador, SIEMPRE ejecutar primero contra el codebase existente y corregir todas las violaciones pre-existentes. Un validador que falla en la baseline invalida CI para todo el equipo. Patron: (1) crear script, (2) ejecutar, (3) corregir violaciones, (4) verificar exit 0, (5) integrar en CI.

## Archivos Creados/Modificados

| Archivo | Accion |
|---------|--------|
| `scripts/validation/validate-services-di.php` | NUEVO (DI cross-validation) |
| `scripts/validation/validate-routing.php` | NUEVO (route-controller cross-validation) |
| `scripts/validation/validate-entity-integrity.php` | NUEVO (6 entity convention checks) |
| `scripts/validation/validate-query-chains.php` | NUEVO (QUERY-CHAIN-001 detection) |
| `scripts/validation/validate-config-sync.sh` | NUEVO (config drift detection) |
| `scripts/validation/validate-all.sh` | NUEVO (orchestrator --fast/--full) |
| `.git/hooks/pre-commit` | EDITADO (conditional architectural validation) |
| `.github/workflows/ci.yml` | EDITADO (new step before PHPStan) |
| `.github/workflows/deploy.yml` | EDITADO (new step pre-deploy) |
| `.lando.yml` | EDITADO (validate + validate-fast tooling) |
| `jaraba_ai_agents/.../AgentSettingsForm.php` | NUEVO (fix ROUTE-CTRL-001) |
| `jaraba_credentials/.../CredentialsSettingsForm.php` | NUEVO (fix ROUTE-CTRL-001) |
| `jaraba_mentoring/.../MentoringSettingsForm.php` | NUEVO (fix ROUTE-CTRL-001) |
| 3 Entity files | EDITADO (admin_permission) |
| 3 ECA Plugin files | EDITADO (event_name in annotation) |
| `ChurnPredictorService.php` | EDITADO ($wContract + risk formula) |
| `phpstan-baseline.neon` | REGENERADO |
