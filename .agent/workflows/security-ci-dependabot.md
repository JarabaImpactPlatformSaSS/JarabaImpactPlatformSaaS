---
description: Proceso para diagnosticar fallos del Security Scan CI y remediar alertas Dependabot
---

# Security CI + Dependabot Remediation

Procedimiento para resolver fallos en el workflow `security-scan.yml` y remediar vulnerabilidades Dependabot.

## Pre-requisitos

- Acceso a GitHub CLI (`gh`) con permisos de Actions y Dependabot
- Acceso al repositorio para editar workflows y package files

## Diagnóstico de Security Scan

### 1. Identificar el fallo

```bash
# Listar runs recientes del Security Scan
gh run list --workflow "Security Scan" --limit 5

# Ver detalle del run fallido
gh run view {run_id}

# Descargar logs completos
gh api repos/{owner}/{repo}/actions/runs/{run_id}/attempts/1/logs > logs.zip
```

### 2. Verificar secrets

> [!CAUTION]
> **REGLA AUDIT-SEC-N17:** Todo workflow que use secrets para URLs DEBE validarlos antes de usarlos. Un secret vacío produce errores crípticos (ej: Docker exit code 3 sin contexto).

```yaml
# Patrón obligatorio antes de OWASP ZAP:
- name: Validate STAGING_URL secret
  run: |
    if [ -z "${{ secrets.STAGING_URL }}" ]; then
      echo "::error::STAGING_URL not configured."
      exit 1
    fi
```

### 3. Verificar DNS del target

Si el secret está configurado pero ZAP falla con `Name or service not known`:
- El dominio no tiene registro DNS
- Buscar la URL correcta en `architecture.yaml`, `deploy-*.yml`, o `scripts/golive/`
- Actualizar: `gh secret set STAGING_URL --body "https://dominio-correcto.com"`

### 4. Relanzar workflow

```bash
gh workflow run "Security Scan"
gh run list --workflow "Security Scan" --limit 1
gh run watch {new_run_id} --exit-status
```

## Remediation Dependabot

### 1. Inventariar alertas abiertas

```bash
# Resumen por severidad
gh api repos/{owner}/{repo}/dependabot/alerts \
  --jq '[.[] | select(.state == "open")] | group_by(.security_advisory.severity) | map({severity: .[0].security_advisory.severity, count: length})'

# Detalle de critical/high
gh api repos/{owner}/{repo}/dependabot/alerts \
  --jq '.[] | select(.state == "open" and (.security_advisory.severity == "critical" or .security_advisory.severity == "high")) | {number, package: .dependency.package.name, severity: .security_advisory.severity, patched: .security_vulnerability.first_patched_version.identifier, manifest: .dependency.manifest_path}'
```

### 2. Estrategia por tipo

| Tipo | Estrategia |
|------|-----------|
| **Dependencia directa en package.json** | Editar versión directamente |
| **Dependencia en lockfile** | `npm audit fix --package-lock-only` en el directorio del módulo |
| **Major bump necesario** | `npm audit fix --force --package-lock-only` |
| **Transitiva bloqueada por upstream** | Añadir `overrides` en package.json |
| **web/core/yarn.lock** | Dismiss con `fix_started` y comentario documentado |

### 3. Aplicar fixes

```bash
# Para lockfiles de módulos contrib:
cd web/modules/contrib/{module}/
npm audit fix --package-lock-only

# Si requiere major bump:
npm audit fix --package-lock-only --force

# Para dependencias transitivas bloqueadas:
# Añadir en package.json:
# "overrides": { "paquete": "^version_parcheada" }
# Luego: npm install --package-lock-only
```

### 4. Dismiss alertas no resolubles

```bash
# Para web/core/yarn.lock (Drupal upstream):
gh api --method PATCH repos/{owner}/{repo}/dependabot/alerts/{alert_number} \
  -f state=dismissed \
  -f dismissed_reason=fix_started \
  -f dismissed_comment="Managed by Drupal core upstream. Will resolve on next core update."
```

> [!CAUTION]
> **REGLA AUDIT-SEC-N18:** Critical/high DEBEN resolverse en <48h. Solo dismiss para paquetes genuinamente no controlables (Drupal core, framework upstream).

### 5. Verificar resultado

```bash
# Confirmar 0 alertas abiertas
gh api repos/{owner}/{repo}/dependabot/alerts \
  --jq '[.[] | select(.state == "open")] | length'
```

## Priorización

1. **Critical** — Resolver inmediatamente (paquetes raíz primero, las transitivas se cierran solas)
2. **High** — Re-evaluar tras resolver critical (muchas se cierran en cadena)
3. **Medium/Low** — npm audit fix + overrides si necesario
4. **Upstream** — Dismiss documentado

## Documentos de Referencia

- [Aprendizajes Security CI + Dependabot](docs/tecnicos/aprendizajes/2026-02-14_security_ci_dependabot_remediation.md)
- [Directrices v24.0.0 - Sección 4.7.1](docs/00_DIRECTRICES_PROYECTO.md) — Reglas AUDIT-SEC-N17, AUDIT-SEC-N18
- [Arquitectura v24.0.0 - Sección 10.4](docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md) — Security CI Automatizado
