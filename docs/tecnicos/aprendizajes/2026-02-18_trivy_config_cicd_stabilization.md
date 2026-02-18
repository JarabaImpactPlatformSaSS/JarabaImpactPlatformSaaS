# Estabilizacion Trivy Config y Deploy Smoke Test Resiliente

**Fecha:** 2026-02-18
**Sesion:** CI/CD fix — Trivy config keys + Deploy smoke test fallback
**Reglas nuevas:** CICD-TRIVY-001, CICD-DEPLOY-001

---

## Contexto

Los workflows Security Scan (Trivy) y Deploy to IONOS fallaban en cada push a main. Trivy reportaba vulnerabilidades en codigo de terceros (vendor/, web/core/, web/modules/contrib/) porque sus exclusiones no se aplicaban. El smoke test de deploy hacia `exit 1` inmediatamente cuando el secret `PRODUCTION_URL` no estaba configurado, sin intentar la verificacion alternativa por SSH.

---

## Lecciones Aprendidas

### 1. Trivy ignora silenciosamente claves de configuracion invalidas

**Situacion:** trivy.yaml usaba `exclude-dirs` y `exclude-files` como claves. Trivy las ignoraba sin error ni warning, escaneando vendor/, web/core/, web/modules/contrib/ y encontrando vulnerabilidades en codigo de terceros.

**Aprendizaje:** Las claves correctas en Trivy v0.50+ son `skip-dirs` y `skip-files`, y DEBEN estar anidadas dentro del bloque `scan:` (config path: `scan.skip-dirs`). Ponerlas al nivel raiz del YAML tambien es ignorado.

**Regla CICD-TRIVY-001:** Toda configuracion de Trivy DEBE usar la estructura `scan.skip-dirs` / `scan.skip-files`. Verificar en los logs de CI que "Number of language-specific files" coincide con lo esperado (no archivos de vendor/contrib).

### 2. Config keys at root level vs nested under scan:

**Situacion:** Primer intento de fix puso `skip-dirs:` al nivel raiz del YAML. Trivy lo cargo (`INFO Loaded file_path="trivy.yaml"`) pero las exclusiones seguian sin aplicarse (23 archivos en vez de ~15).

**Aprendizaje:** La precedencia de config en Trivy es: CLI flags > Env vars > Config file. Pero dentro del config file, `skip-dirs` DEBE ir bajo `scan:` porque su `ConfigName` interno es `scan.skip-dirs`.

**Formato correcto:**
```yaml
scan:
  scanners:
    - vuln
    - secret
  skip-dirs:
    - vendor
    - web/core
    - web/modules/contrib
```

### 3. Deploy smoke test debe tener fallback SSH cuando faltan secrets

**Situacion:** El smoke test de deploy hacia `exit 1` inmediatamente si `PRODUCTION_URL` estaba vacio, sin intentar la verificacion alternativa por SSH+Drush que ya estaba implementada mas abajo.

**Aprendizaje:** Los smoke tests deben ser resilientes ante secrets no configurados. La verificacion SSH (drush status --field=bootstrap) es igual de valida que un HTTP health check y no requiere PRODUCTION_URL.

**Regla CICD-DEPLOY-001:** Todo smoke test que dependa de un secret de URL DEBE implementar un fallback (SSH, drush, etc.) antes de fallar. Emitir `::warning::` en vez de `::error::` cuando el fallback tiene exito.

---

## Resumen de Cambios

| Archivo | Cambio |
|---------|--------|
| `trivy.yaml` | Reestructurado con bloque `scan:` conteniendo `scanners`, `skip-dirs`, `skip-files` |
| `.github/workflows/deploy.yml` | Smoke test reescrito con logica if/else: PRODUCTION_URL -> HTTP check -> SSH fallback |

## Resultado

| Metrica | Antes | Despues |
|---------|-------|---------|
| Security Scan (Trivy) | Fail | Pass |
| Deploy to IONOS | Fail | Pass |
| Vulnerabilidades vendor/contrib | Reportadas | Excluidas correctamente |
| Smoke test sin PRODUCTION_URL | Fail (exit 1) | Pass (SSH fallback) |
