# Plan: Sistema de Salvaguardas — Control de Complitud, Integridad, Consistencia y Coherencia

**Fecha**: 2026-03-04
**Version**: 1.1.0
**Estado**: IMPLEMENTADO + AUTO-REMEDIACION
**Autor**: Claude (Opus 4.6) + Pepe Jaraba

---

## Contexto

La auditoria de clase mundial (26 gaps, 11 dimensiones) revelo que multiples implementaciones
pasaron CI pero tenian defectos de "ultima milla": servicios huerfanos sin consumidor,
CSS desincronizado, preprocess hooks faltantes, tests funcionales ausentes. El problema
no es la calidad del codigo individual, sino la FALTA DE VERIFICACION SISTEMATICA de que
las piezas estan CONECTADAS y COMPLETAS.

### Bugs reales que motivaron este plan:
1. **8 CopilotBridgeServices huerfanos** — codigo existia, nadie lo consumia
2. **CSS desincronizado** — SCSS editado pero CSS no recompilado
3. **2 template_preprocess_ faltantes** — entities renderizaban sin variables preparadas
4. **Modulo sin tests funcionales** — 100+ rutas en agroconecta, 0 tests de acceso
5. **Servicios registrados sin verificar wiring end-to-end** — Currency/Timezone

### Estado previo de validacion automatizada:
- 8 workflows GitHub Actions (CI, deploy, security-scan, fitness-functions, backups)
- 6 scripts validacion (services-di, routing, query-chains, entity-integrity, config-sync, validate-all)
- 508 tests PHP (397 Unit, 74 Kernel, 37 Functional) + 16 Cypress E2E
- PHPStan L6 + security rules, PHPCS, ESLint, Stylelint
- **GAPS CRITICOS**: No pre-commit hooks, no deteccion servicios huerfanos, no verificacion
  compiled assets en CI, no tenant isolation check, 13 modulos sin tests

---

## Arquitectura del Sistema de Salvaguardas — 5 Capas

```
Capa 5: Controles de Proceso (IMPLEMENTATION-CHECKLIST-001 en CLAUDE.md)
Capa 4: Runtime Self-Checks (hook_requirements en /admin/reports/status)
Capa 3: CI Pipeline Gates (ci.yml + fitness-functions.yml)
Capa 2: Pre-commit Hooks (Husky + lint-staged, shift-left)
Capa 1: Nuevos Validators (scripts/validation/ — fundamento)
```

---

## CAPA 1: Scripts de Validacion (Implementados)

### 1A. validate-service-consumers.php (SERVICE-ORPHAN-001)
- **Archivo**: `scripts/validation/validate-service-consumers.php`
- **Funcion**: Detecta servicios definidos en `*.services.yml` que no son consumidos por ningun otro componente
- **Algoritmo**: Escanea services.yml > busca consumidores en @args, $container->get(), \Drupal::service() > excluye terminales > reporta huerfanos
- **Resultado**: Detecta los 918 servicios del ecosistema, identifica 128 terminales, verifica 692 consumidos
- **Bugs que habria detectado**: #1 (8 CopilotBridges huerfanos)

### 1B. validate-compiled-assets.php (ASSET-FRESHNESS-001)
- **Archivo**: `scripts/validation/validate-compiled-assets.php`
- **Funcion**: Verifica que CSS compilado tiene timestamp >= SCSS fuente
- **Algoritmo**: Mapea 23+ pares SCSS->CSS > compara filemtime > verifica parciales _*.scss vs main.css
- **Bugs que habria detectado**: #2 (CSS desincronizado)

### 1C. validate-test-coverage-map.php (TEST-COVERAGE-MAP-001)
- **Archivo**: `scripts/validation/validate-test-coverage-map.php`
- **Funcion**: Detecta modulos sin cobertura de tests apropiada a su complejidad
- **Reglas**: >10 rutas=Functional, entities=Kernel, services=Unit
- **Resultado**: Analiza 92 modulos, identifica gaps especificos
- **Bugs que habria detectado**: #4 (agroconecta sin functional tests)

### 1D. validate-tenant-isolation.php (TENANT-CHECK-001)
- **Archivo**: `scripts/validation/validate-tenant-isolation.php`
- **Funcion**: Verifica que AccessControlHandlers para entities con tenant_id incluyen verificacion de tenant
- **Resultado**: Analiza 286 entities con tenant_id

### 1E. validate-entity-integrity.php (MEJORADO)
- **Cambio**: CHECK 3 (ENTITY-PREPROCESS-001) elevado de WARNING a ERROR
- **Bugs que habria detectado**: #3 (2 preprocess faltantes)

### 1F. validate-container-deps.php (CONTAINER-DEPS-001) — v1.1
- **Archivo**: `scripts/validation/validate-container-deps.php`
- **Funcion**: Verifica que todas las dependencias en services.yml (especialmente logger channels) esten definidas
- **Algoritmo**: Parsea 93 services.yml > extrae service IDs definidos > encuentra referencias @service > excluye core/contrib + @? opcionales > reporta rotas
- **Resultado**: Analiza 938 servicios definidos, 77 logger channels
- **Check rapido**: Incluido en modo --fast (pre-commit) por su criticidad
- **Bugs que habria detectado**: jaraba_candidate.copilot_bridge → logger.channel.jaraba_candidate (container compilation failure)

---

## CAPA 2: Pre-commit Hooks

### Archivos creados:
- `package.json` (raiz) — devDependencies: husky 9.x, lint-staged 15.x
- `.husky/pre-commit` — ejecuta `npx lint-staged`
- `.lintstagedrc.json` — configuracion por extension de archivo

### Checks ejecutados en pre-commit (~5-10s):
| Extension | Check |
|-----------|-------|
| `*.php` | PHPStan analyse |
| `*.scss` | validate-compiled-assets.php |
| `00_*.md` | verify-doc-integrity.sh |
| `*.services.yml` | validate-all.sh --fast |
| `*.routing.yml` | validate-all.sh --fast |

### Instalacion:
```bash
cd /ruta/proyecto
npm install   # instala husky + lint-staged
npx husky     # configura git hooks
```

---

## CAPA 3: CI Pipeline — Integracion

### ci.yml (Job "lint"):
- `validate-all.sh --full` ahora incluye los 5 nuevos validators (full mode)
- `validate-service-consumers.php` standalone (continue-on-error: true por volumen actual)
- `validate-compiled-assets.php` standalone (bloqueante)
- `validate-test-coverage-map.php` standalone (continue-on-error: true)
- `validate-container-deps.php` standalone (bloqueante — previene container compilation failures)

### fitness-functions.yml:
- Nuevo Job 5: **Service & Tenant Integrity**
  - Orphaned services check (continue-on-error: true)
  - Tenant isolation check (continue-on-error: true)
  - Asset freshness check (bloqueante)
  - Container dependencies check (bloqueante)
- Fitness Report actualizado con nueva fila

### deploy.yml — AUTO-REMEDIACION (v1.1):
- Step "Auto-remediate entity/field schema" reemplaza verificacion pasiva
- **4 fases**: Detect → Auto-remediate → Cache rebuild → Final verify
- Aplica `installEntityType()`, `installFieldStorageDefinition()`, `updateFieldStorageDefinition()` automaticamente
- Respeta UPDATE-FIELD-DEF-001 (`setName()` + `setTargetEntityTypeId()`)
- Si persisten mismatches tras auto-remediacion: **BLOQUEA deployment** (exit 1)
- Schema Sync en hook_requirements elevado a REQUIREMENT_ERROR

### validate-all.sh:
- Nuevo modo `--checklist <path>` para verificacion post-implementacion por modulo
- 5 nuevos checks en modo `--full`, CONTAINER-DEPS-001 tambien en modo `--fast`

---

## CAPA 4: Runtime Self-Checks (hook_requirements)

### ecosistema_jaraba_core.install:
1. **Critical Services**: Verifica 4 servicios clave en container (tenant_context, tenant_bridge, currency, tenant_timezone)
2. **Tenant Integrity**: Verifica que todo Tenant tiene Group asociado via TenantBridge
3. **Schema Sync**: Verifica que entity definitions estan sincronizadas con DB

### jaraba_ai_agents.install:
1. **Model Routing Config**: Verifica jaraba_ai_agents.model_routing existe
2. **API Keys**: Verifica que al menos 1 provider tiene API key
3. **Tool Registry**: Verifica que el servicio ToolRegistry esta disponible

### ecosistema_jaraba_theme.install:
1. **CSS Freshness**: filemtime() de main CSS vs SCSS
2. **Vendor Libraries**: Verifica accesibilidad de GrapesJS y otros

---

## CAPA 5: Controles de Proceso

### CLAUDE.md:
- Nueva seccion `IMPLEMENTATION-CHECKLIST-001` con 4 dimensiones:
  - Complitud, Integridad, Consistencia, Coherencia
- Nueva seccion `SAFEGUARD SYSTEM` con tabla de 5 capas
- Version actualizada a 1.2.0

### validate-all.sh --checklist:
- Nuevo modo que acepta path de modulo
- Verifica las 4 dimensiones especificamente para ese modulo
- Output: tabla con cada check y PASS/FAIL

---

## Impacto

| Bug pasado | Validator que lo habria detectado | Capa(s) | Accion |
|------------|-----------------------------------|---------|----|
| 8 CopilotBridges huerfanos | validate-service-consumers.php | 1+3 | Detecta |
| CSS desincronizado | validate-compiled-assets.php | 1+2+3+4 | Detecta+Bloquea |
| 2 preprocess faltantes | validate-entity-integrity.php (mejorado) | 1+3 | Detecta+Bloquea |
| Agroconecta sin tests | validate-test-coverage-map.php | 1+3 | Detecta |
| Wiring incompleto | validate-service-consumers.php + hook_requirements | 1+4 | Detecta |
| Tenant isolation gaps | validate-tenant-isolation.php | 1+3 | Detecta |
| Docs erosionados | verify-doc-integrity.sh en pre-commit | 2 | Bloquea commit |
| Logger channel no definido | validate-container-deps.php | 1+2+3 | Bloquea CI+commit |
| Schema DB desincronizado | deploy.yml auto-remediate + hook_requirements | 3+4 | **Auto-remedia** |

**Resultado**: Cada bug real queda cubierto por al menos 2 capas de defensa. El deploy pipeline ahora **auto-remedia** mismatches de schema en lugar de solo reportarlos.

---

## Archivos Creados/Modificados

### Creados (9):
1. `scripts/validation/validate-service-consumers.php` — SERVICE-ORPHAN-001
2. `scripts/validation/validate-compiled-assets.php` — ASSET-FRESHNESS-001
3. `scripts/validation/validate-test-coverage-map.php` — TEST-COVERAGE-MAP-001
4. `scripts/validation/validate-tenant-isolation.php` — TENANT-CHECK-001
5. `scripts/validation/validate-container-deps.php` — CONTAINER-DEPS-001
6. `package.json` (raiz) — Husky + lint-staged
7. `.husky/pre-commit` — Hook script
8. `.lintstagedrc.json` — Configuracion lint-staged
9. `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.install` — Theme requirements

### Modificados (10):
1. `scripts/validation/validate-entity-integrity.php` — CHECK 3 WARNING→ERROR
2. `scripts/validation/validate-all.sh` — +5 checks, +--checklist mode, CONTAINER-DEPS-001 en fast
3. `.github/workflows/ci.yml` — +4 validators en lint job (incl. container-deps bloqueante)
4. `.github/workflows/fitness-functions.yml` — +1 job service-integrity, +container deps check
5. `.github/workflows/deploy.yml` — Auto-remediacion schema (4 fases: detect→fix→rebuild→verify)
6. `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install` — +3 requirements, schema→ERROR
7. `web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.install` — +hook_requirements
8. `web/modules/custom/jaraba_candidate/jaraba_candidate.services.yml` — +logger.channel.jaraba_candidate
9. `CLAUDE.md` — +IMPLEMENTATION-CHECKLIST-001, +SAFEGUARD SYSTEM
10. `docs/implementacion/2026-03-04_Plan_Sistema_Salvaguardas_Control_Complitud_v1.md` — v1.1 auto-remediacion
