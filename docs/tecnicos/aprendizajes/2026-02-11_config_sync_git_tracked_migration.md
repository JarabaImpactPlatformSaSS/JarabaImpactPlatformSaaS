# Aprendizaje #60: Migrar Config Sync a Directorio Git-Tracked

**Fecha:** 2026-02-11
**Contexto:** El directorio config sync de Drupal estaba en `web/sites/default/files/config_HASH/sync/` (gitignored), impidiendo que `config:import` en produccion resolviera entidades Key y demas configuracion. Se migro a `config/sync/` en la raiz del proyecto.
**Modulos afectados:** `jaraba_rag` (settings override), deploy pipeline

---

## 1. Resumen

La configuracion de Drupal (589 archivos YML + traducciones en/es) se exportaba a un directorio dentro de `web/sites/default/files/` que estaba excluido por `.gitignore`. Esto causaba que `drush config:import` en produccion (IONOS) no encontrara las configuraciones, incluyendo las entidades Key (`qdrant_api`, `openai_api`, `anthropic_api`, `google_gemini_api_key`). El workaround era inyectar credenciales directamente via `settings.local.php` desde el pipeline CI/CD.

La solucion: mover el config sync a `config/sync/` en la raiz del repositorio (git-tracked) y sobrescribir `$settings['config_sync_directory']` desde `settings.jaraba_rag.php` (que ya se incluye en ambos entornos).

---

## 2. Archivos Creados

| Archivo | Proposito |
|---------|-----------|
| `config/sync/*.yml` (589 archivos) | Config sync de Drupal completo, git-tracked |
| `config/sync/language/en/*.yml` | Traducciones config overrides (ingles) |
| `config/sync/language/es/*.yml` | Traducciones config overrides (espanol) |

## 3. Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `web/modules/custom/jaraba_rag/config/settings.jaraba_rag.php` | +override `$settings['config_sync_directory'] = '../config/sync'` al inicio |
| `.github/workflows/deploy.yml` | +step "Sync site UUID for config import" antes de config:import |

---

## 4. Lecciones Aprendidas

### DEPLOY-001: Config Sync debe estar en Git

**Problema:** El directorio por defecto de Drupal para config sync (`web/sites/default/files/config_HASH/sync/`) cae dentro de `.gitignore` (`web/sites/*/files`). En un deploy git-based, estos archivos nunca llegan al servidor.

**Solucion:** Mover a `config/sync/` en la raiz y sobrescribir `$settings['config_sync_directory']` en un archivo PHP que SI este trackeado y se incluya en settings.php.

**Regla:** En todo proyecto Drupal con deploy git-based, el config sync DEBE estar fuera de `web/sites/*/files/`.

### DEPLOY-002: UUID del Sitio debe Coincidir para config:import

**Problema:** Si el UUID del sitio en produccion difiere del UUID en `config/sync/system.site.yml`, `drush config:import` rechaza TODOS los cambios con error "Site UUID mismatch".

**Solucion:** Anadir un step en el pipeline que compare UUIDs y fuerce el UUID del sitio si difieren. Tras la primera sincronizacion, este step es no-op (idempotente).

**Regla:** Todo pipeline que ejecute `config:import` en un entorno diferente al de export debe incluir sincronizacion de UUID como prerequisito.

### DEPLOY-003: Override de config_sync_directory via Include PHP

**Problema:** `settings.php` no esta en git (cada entorno tiene el suyo), asi que no se puede modificar ahi.

**Solucion:** Usar `settings.jaraba_rag.php` que ya se incluye via `settings.php` (linea 882) DESPUES de `config_sync_directory` (linea 879). El principio "last write wins" de PHP permite sobrescribirlo.

**Patron:** Para overrides que apliquen a todos los entornos, usar un archivo PHP que:
1. Este trackeado en git
2. Se incluya desde `settings.php` (no condicionado a entorno)
3. Se cargue DESPUES del valor que sobrescribe

### DEPLOY-004: Key Entities con key_provider: config en Repo Privado

**Decision:** Las 4 entidades Key contienen API keys reales con `key_provider: config`. Al commitear `config/sync/`, estos valores quedan en el historial git.

**Justificacion:** El repositorio es privado con acceso limitado al equipo. La alternativa (migrar a `key_provider: env`) requiere env vars en Lando, `.env`, GitHub Secrets y overrides en `settings.local.php` — complejidad excesiva para el beneficio.

**Mejora futura:** Migrar a `key_provider: env` y gestionar las claves via variables de entorno en cada entorno.

---

## 5. Flujo de Deploy Actualizado

```
git push main
    |
    v
GitHub Actions: test -> deploy
    |
    v
1. git reset --hard origin/main
   -> trae config/sync/ + settings.jaraba_rag.php actualizado
    |
    v
2. settings.jaraba_rag.php se carga
   -> sobrescribe config_sync_directory = '../config/sync'
    |
    v
3. Step "Sync site UUID"
   -> compara UUID config vs site, fuerza si difieren
    |
    v
4. drush config:import -y
   -> lee config/sync/ -> importa 589+ configs
   -> entidades Key (qdrant_api, openai_api, etc.) quedan en BD
    |
    v
5. Key module resuelve claves via BD
   -> QdrantDirectClient, AI providers funcionan sin workarounds
```

## 6. Verificacion

- **Local:** `lando drush config:status` -> "No differences between DB and sync directory"
- **Path:** `lando drush status --fields=config-sync` -> `../config/sync`
- **UUID:** `31e467f0-a3f3-4fa7-bd61-222cc2ce07a3` (coincide en system.site.yml)
- **Deploy:** Pipeline muestra "Config import successful" y "UUIDs coinciden"
- **Health:** `curl -sf https://plataformadeecosistemas.com/api/v1/platform/status | jq .components.qdrant` -> `{"status": "ok"}`

---

## 7. Contexto Historico

| Fecha | Estado |
|-------|--------|
| 2026-01-10 | Deploy inicial IONOS. Config sync no llega (gitignored) |
| 2026-01-11 | Workaround: inyectar credenciales Qdrant en settings.local.php via CI/CD |
| 2026-02-11 | **Solucion definitiva**: Config sync en `config/sync/` (git-tracked) |

---

## 8. Reglas Derivadas

| Regla | Descripcion |
|-------|-------------|
| **DEPLOY-001** | Config sync DEBE estar fuera de `web/sites/*/files/` en deploys git-based |
| **DEPLOY-002** | Incluir sincronizacion de UUID como prerequisito de `config:import` en todo pipeline |
| **DEPLOY-003** | Usar archivos PHP incluidos (git-tracked) para overrides cross-entorno |
| **DEPLOY-004** | API keys en config son aceptables en repos privados; migrar a env vars como mejora futura |
