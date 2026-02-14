# Security CI + Dependabot Remediation

**Fecha:** 2026-02-14
**Sesión:** Security CI operativo + Dependabot 42→0
**Reglas nuevas:** AUDIT-SEC-N17, AUDIT-SEC-N18

---

## Contexto

El workflow `security-scan.yml` (OWASP ZAP Baseline) fallaba con exit code 3 en cada ejecución programada (cron daily 02:00 UTC). Paralelamente, Dependabot reportaba 42 vulnerabilidades abiertas (2 critical, 14 high, 15 moderate, 11 low).

---

## Lecciones Aprendidas

### 1. Secrets vacíos causan fallos crípticos en GitHub Actions

**Situación:** El job OWASP ZAP fallaba con `The process '/usr/bin/docker' failed with exit code 3`. El mensaje no indicaba la causa real.

**Aprendizaje:** ZAP recibía `-t` sin argumento porque `${{ secrets.STAGING_URL }}` estaba vacío. Docker exit code 3 es genérico. Solo leyendo los logs completos del contenedor se encontró `Target must start with 'http://' or 'https://'`.

**Regla AUDIT-SEC-N17:** Todo workflow que use secrets para URLs o credenciales DEBE incluir un paso de validación previo:
```yaml
- name: Validate STAGING_URL secret
  run: |
    if [ -z "${{ secrets.STAGING_URL }}" ]; then
      echo "::error::STAGING_URL secret is not configured."
      exit 1
    fi
```

### 2. DNS resolution no es lo mismo que secret configurado

**Situación:** Tras configurar `STAGING_URL=https://staging.jaraba.io`, ZAP seguía fallando: `staging.jaraba.io: Name or service not known`.

**Aprendizaje:** El dominio referenciado en `deploy-staging.yml` no tenía registro DNS real. El dominio operativo es `plataformadeecosistemas.com`. Siempre verificar que el dominio resuelve antes de configurar como target de DAST.

**Regla:** Verificar resolución DNS del target antes de configurar secrets de scanning.

### 3. Dependabot critical/high se resuelven en cadena

**Situación:** De 42 alertas, las 2 critical eran webpack (leaflet-geoman-free) y @babel/traverse (ai_ckeditor). Al parchearlas, 10 de las 14 high se cerraron automáticamente (eran dependencias transitivas de las mismas).

**Aprendizaje:** Priorizar siempre los paquetes raíz. Las dependencias transitivas se resuelven solas al actualizar el paquete que las importa. Resultado: 42→4 con solo 2 ediciones.

**Regla AUDIT-SEC-N18:** Resolver critical/high primero; re-evaluar el conteo tras cada push antes de abordar las siguientes.

### 4. npm overrides para dependencias transitivas bloqueadas

**Situación:** `diff@7.0.0` (CVE-2026-24001) era dependencia transitiva de `mocha@11.7.5`, que requiere `diff: ^7.0.0`. El parche está en `diff@8.0.3` (fuera del rango semver). `npm audit fix` y `--force` no resolvían.

**Aprendizaje:** Cuando el paquete padre no ha actualizado su rango de dependencia, la única opción sin esperar upstream es usar `overrides` en package.json:
```json
"overrides": {
  "diff": "^8.0.3"
}
```
Esto fuerza la versión parcheada ignorando el rango del padre.

**Regla:** Para dependencias transitivas bloqueadas por semver del padre, usar `overrides` (npm) o `resolutions` (yarn).

### 5. web/core/yarn.lock es territorio Drupal upstream

**Situación:** webpack `5.102.1` en `web/core/yarn.lock` (CVE-2025-68458, low). Actualizarlo localmente se sobreescribiría con cada `composer update drupal/core`.

**Aprendizaje:** Archivos de lock en `web/core/` los mantiene el equipo de Drupal. Modificarlos crea drift que se pierde en la siguiente actualización de core. La acción correcta es dismiss con razón documentada y esperar el fix upstream.

**Regla:** Nunca modificar `web/core/yarn.lock` directamente. Dismiss Dependabot con `fix_started` y comentario explicativo.

### 6. devDependencies en contrib no afectan producción

**Situación:** Las 42 vulnerabilidades estaban todas en `devDependencies` de módulos contrib (webpack, babel, mocha, tar, lodash, preact, diff). Ninguna se servía al usuario final.

**Aprendizaje:** Los paquetes en `devDependencies` solo se usan durante build/test, no en runtime. El riesgo real es bajo, pero Dependabot no distingue contexto de uso. Aun así, deben parchearse para mantener postura de seguridad limpia y evitar ruido en auditorías.

**Regla:** Parchear todas las alertas independientemente del contexto, pero priorizar por severidad real (critical > high > medium > low).

---

## Resumen de Cambios

| Archivo | Cambio |
|---------|--------|
| `.github/workflows/security-scan.yml` | Paso validación STAGING_URL pre-ZAP |
| `leaflet-geoman-free/package.json` | webpack 5.36.2→^5.76.0, lodash 4.17.21→^4.17.23 |
| `ai_ckeditor/package.json` | overrides diff ^8.0.3, ckeditor5-dev-utils ^49.0.2, ckeditor5 ^47.5.0 |
| `ai_ckeditor/package-lock.json` | @babel/traverse 7.21.4→7.29.0, tar eliminado, diff 7.0.0→8.0.3 |
| `paragraphs/css/package-lock.json` | lodash 4.17.21→4.17.23 |
| `bpmn_io/build/package-lock.json` | preact 10.26.9→10.28.3 |
| GitHub Secret `STAGING_URL` | Configurado con `https://plataformadeecosistemas.com` |
| Dependabot alert #16 | Dismissed (webpack web/core — Drupal upstream) |

## Resultado

| Métrica | Antes | Después |
|---------|-------|---------|
| OWASP ZAP | Fail (exit 3) | Pass |
| Dependabot Critical | 2 | 0 |
| Dependabot High | 14 | 0 |
| Dependabot Medium | 15 | 0 |
| Dependabot Low | 11 | 0 (1 dismissed) |
| Security Scan workflow | Fail | Pass (4/4 jobs) |
| Deploy to IONOS | - | Pass (tests + deploy + smoke) |
