# Aprendizaje #175 — ENV-PARITY-001: Dev/Prod Parity Safeguard

**Fecha:** 2026-03-11
**Contexto:** Analisis de infraestructura para servidor IONOS AMD EPYC Zen 5 (AE12-128 NVMe) revelo ausencia de control de paridad dev/prod en el sistema de salvaguarda (17 scripts, 5 capas). Implementado como script #18.

## Problema

El sistema de salvaguarda tenia 17 scripts de validacion cubriendo integridad de codigo, arquitectura, seguridad, theming y deploy readiness. Pero NO verificaba que la configuracion del entorno de desarrollo (Lando) fuera coherente con la de produccion (IONOS). Esto crea una clase de bugs que solo aparecen en produccion:

- PHP extension disponible en dev pero no declarada en composer.json
- Settings PHP diferentes (max_input_vars 1000 vs 5000)
- OPcache con `validate_timestamps=0` en produccion sin invalidation explicita en deploy
- Paths de filesystem hardcodeados para un entorno
- Code paths condicionales (`getenv('LANDO')`) sin equivalente produccion

## Solucion

`scripts/validation/validate-env-parity.php` — 14 checks:

1. **PHP version**: cruza 6 fuentes (.lando.yml, composer.json, php.ini, runbook, deploy.yml, architecture.yaml)
2. **Extensiones**: verifica que todas las ext-* esten en composer.json
3. **MariaDB version**: coherencia entre .lando.yml y produccion
4. **Redis version**: coherencia
5. **PHP config**: 5 settings criticos (memory_limit, max_input_vars, opcache, timezone, upload)
6. **MariaDB my.cnf**: existencia de config produccion
7. **OPcache invalidation**: strategy en deploy pipeline
8. **Supervisor workers**: coherencia con architecture.yaml
9. **Filesystem paths**: coherencia con produccion
10. **Multi-domain**: trusted_hosts vs Nginx vs Domain entities
11. **Code paths**: deteccion de `getenv('LANDO')` y similares
12. **composer.lock**: freshness vs composer.json
13. **Reverse proxy**: coherencia Nginx vs Traefik
14. **Wildcard SSL**: strategy para *.plataformadeecosistemas.com

## Lecciones Aprendidas

### 1. PHP `glob('**')` NO es recursivo
```php
// INCORRECTO — solo expande en bash, no en PHP
$files = glob($root . '/**/src/**/*.php');

// CORRECTO — RecursiveDirectoryIterator
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
);
$phpFiles = new RegexIterator($iterator, '/\.php$/', RegexIterator::MATCH);
```

### 2. Extensiones FPM-only
`opcache` y `redis` solo se cargan en el contexto FPM, no en CLI. Cuando el script corre en CLI (como en CI), estas extensiones no estan disponibles. Categorizarlas como WARN, no ERROR.

### 3. Traefik en docs vs en config ejecutable
Menciones de Traefik en documentacion (.md) son WARN. En config ejecutable (docker-compose.yml, deploy scripts) son ERROR si el codebase tiene config Nginx completa.

### 4. OPcache invalidation gap
`validate_timestamps=0` en produccion es correcto para rendimiento, pero REQUIERE invalidation explicita en el deploy pipeline. Sin ella, codigo viejo persiste en cache OPcache hasta que los workers FPM mueren. Fix: `drush php:eval "opcache_reset()"` tras cache:rebuild (nota: CLI vs FPM scope — el reset en CLI solo limpia el cache del proceso CLI, no del FPM pool).

### 5. composer.json ext-* como documentacion ejecutable
Declarar extensiones en composer.json no solo documenta requisitos sino que:
- CI falla temprano si falta una extension
- `composer install` en produccion verifica
- Nuevos desarrolladores ven requisitos al instalar

## Ficheros

### Creados
- `scripts/validation/validate-env-parity.php` (~900 LOC)
- `config/deploy/mariadb/my.cnf` (produccion MariaDB tuning)
- `docs/tecnicos/20260311b_Analisis_Runbook_AMD_v2_Claude.md`

### Modificados
- `scripts/validation/validate-all.sh` (+ENV-PARITY-001 + 3 skip_checks)
- `.github/workflows/deploy.yml` (+OPcache invalidation)
- `composer.json` (+12 ext-* requirements)
- `php.ini` (+max_input_vars 5000)

## Regla de Oro #116
**Dev/prod parity previene bugs produccion-only.** `validate-env-parity.php` cruza 6+ fuentes de verdad para cada dimension (version, config, paths, domains). Un bug que solo aparece en produccion es el mas caro de depurar — la paridad automatizada lo previene.

## Cross-refs
- Directrices v125.0.0, Arquitectura v113.0.0, Indice v154.0.0, Flujo v78.0.0
- DEPLOY-READY-001 (complementario: readiness vs parity)
- SECRET-MGMT-001 (secrets via getenv())
- Runbook: `docs/tecnicos/20260311a-131b_Server_Implementation_Runbook_AMD_v2_Claude.md`
- Analisis: `docs/tecnicos/20260311b_Analisis_Runbook_AMD_v2_Claude.md`
