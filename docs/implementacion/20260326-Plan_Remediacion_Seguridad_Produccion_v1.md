# Plan de Remediacion de Seguridad — Produccion IONOS

**Fecha de creacion:** 2026-03-26 10:30
**Ultima actualizacion:** 2026-03-26 10:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion / Seguridad
**Auditoria origen:** [20260326-Auditoria_Seguridad_Produccion_IONOS_v1_Claude.md](../tecnicos/auditorias/20260326-Auditoria_Seguridad_Produccion_IONOS_v1_Claude.md)
**Documentos fuente:** 00_DIRECTRICES_PROYECTO.md v166.0.0, 00_FLUJO_TRABAJO_CLAUDE.md v115.0.0, CLAUDE.md v1.10.0

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Requisitos Previos](#2-requisitos-previos)
3. [Fase 1 — Fixes Inmediatos (Dia 1)](#3-fase-1--fixes-inmediatos-dia-1)
   - 3.1 [SEC-C04: Validacion protocolo URLs en Copilot JS](#31-sec-c04-validacion-protocolo-urls-en-copilot-js)
   - 3.2 [SEC-C02: HMAC secret adjuntos via getenv()](#32-sec-c02-hmac-secret-adjuntos-via-getenv)
   - 3.3 [SEC-A07: Cast int en tenant ID comparison](#33-sec-a07-cast-int-en-tenant-id-comparison)
   - 3.4 [SEC-A08: Actualizar google/protobuf](#34-sec-a08-actualizar-googleprotobuf)
   - 3.5 [SEC-C05: Sanitizar SuccessCase text_long](#35-sec-c05-sanitizar-successcase-text_long)
   - 3.6 [SEC-A05: Sanitizar InfoPagesController content](#36-sec-a05-sanitizar-infopagescontroller-content)
   - 3.7 [SEC-A06: striptags en map_embed jarabalex](#37-sec-a06-striptags-en-map_embed-jarabalex)
4. [Fase 2 — Fixes Criticos Estructurales (Semana 1)](#4-fase-2--fixes-criticos-estructurales-semana-1)
   - 4.1 [SEC-C01: Migrar Claude API key a getenv()](#41-sec-c01-migrar-claude-api-key-a-getenv)
   - 4.2 [SEC-C03: checkPII() bidireccional en copilot y agents](#42-sec-c03-checkpii-bidireccional-en-copilot-y-agents)
   - 4.3 [SEC-A01: Fingerprint SSH fijo en CI workflows](#43-sec-a01-fingerprint-ssh-fijo-en-ci-workflows)
   - 4.4 [SEC-A09 + SEC-M08: Promover validators a run_check](#44-sec-a09--sec-m08-promover-validators-a-run_check)
5. [Fase 3 — Hardening Infraestructura (Sprint Siguiente)](#5-fase-3--hardening-infraestructura-sprint-siguiente)
   - 5.1 [SEC-M02: Sleep en workers Supervisor](#51-sec-m02-sleep-en-workers-supervisor)
   - 5.2 [SEC-M03: MariaDB hardening](#52-sec-m03-mariadb-hardening)
   - 5.3 [SEC-M05: Redis bind + users.acl](#53-sec-m05-redis-bind--usersacl)
   - 5.4 [SEC-A10: Validator validate-twig-raw-audit.php](#54-sec-a10-validator-validate-twig-raw-auditphp)
   - 5.5 [SEC-M04: Cifrado backups + capas 2-3](#55-sec-m04-cifrado-backups--capas-2-3)
   - 5.6 [SEC-A02 + SEC-A03 + SEC-M01: Nginx + PHP hardening](#56-sec-a02--sec-a03--sec-m01-nginx--php-hardening)
6. [Fase 4 — Mejoras Medio Plazo](#6-fase-4--mejoras-medio-plazo)
   - 6.1 [SEC-A04: CSP nonces + separacion frontend/admin](#61-sec-a04-csp-nonces--separacion-frontendadmin)
   - 6.2 [Nuevos validators de seguridad](#62-nuevos-validators-de-seguridad)
   - 6.3 [Medidas de salvaguarda adicionales](#63-medidas-de-salvaguarda-adicionales)
7. [Tabla de Correspondencia con Especificaciones Tecnicas](#7-tabla-de-correspondencia-con-especificaciones-tecnicas)
8. [Tabla de Cumplimiento de Directrices](#8-tabla-de-cumplimiento-de-directrices)
9. [Verificacion RUNTIME-VERIFY-001](#9-verificacion-runtime-verify-001)
10. [Nuevas Reglas para CLAUDE.md](#10-nuevas-reglas-para-claudemd)
11. [Glosario de Terminos](#11-glosario-de-terminos)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este plan detalla la remediacion de 29 hallazgos de seguridad identificados en la [auditoria del 2026-03-26](../tecnicos/auditorias/20260326-Auditoria_Seguridad_Produccion_IONOS_v1_Claude.md) del servidor IONOS Dedicated de Jaraba Impact Platform. El plan se organiza en 4 fases con prioridad descendente:

| Fase | Plazo | Hallazgos | Esfuerzo estimado |
|------|-------|-----------|-------------------|
| **Fase 1** | Dia 1 (inmediato) | 7 fixes (5 CRIT + 2 ALTA) | ~2h |
| **Fase 2** | Semana 1 | 4 fixes estructurales (1 CRIT + 2 ALTA + 1 accion) | ~8h |
| **Fase 3** | Sprint siguiente | 6 fixes infraestructura (5 MEDIA + 1 ALTA) | ~18h |
| **Fase 4** | Medio plazo | 3 mejoras (1 ALTA + validators + salvaguardas) | ~40h |

### Criterios de exito

- 0 hallazgos CRITICOS abiertos tras Fase 1
- 0 hallazgos ALTOS abiertos tras Fase 2
- Todos los validators de seguridad como `run_check` en CI
- Cobertura automatica de `|raw`, `checkPII()` y secrets en config/sync

---

## 2. Requisitos Previos

### 2.1 Entorno de desarrollo

Todos los cambios se implementan y prueban en Lando (dev local) antes de desplegar a produccion. Comandos dentro del contenedor Docker via `lando`:

```bash
# Verificar entorno
lando drush status
lando php -v  # PHP 8.4
```

### 2.2 Variables de entorno necesarias en produccion

Las siguientes variables de entorno DEBEN existir en el servidor IONOS antes de desplegar las Fases 1-2:

| Variable | Proposito | Generacion |
|----------|-----------|------------|
| `CLAUDE_API_KEY` | API key de Anthropic para Claude | Copiar desde panel de Anthropic |
| `SUPPORT_ATTACHMENT_HMAC_SECRET` | HMAC para URLs de adjuntos | `openssl rand -hex 32` |
| `DEPLOY_HOST_KNOWN_HOSTS` | Fingerprint SSH del servidor | `ssh-keyscan -p 2222 -H 82.223.204.169` (una sola vez) |

### 2.3 Rama de trabajo

```bash
git checkout -b security/remediation-2026-03-26
```

### 2.4 Directrices de aplicacion obligatorias

Todo el codigo de este plan DEBE cumplir:

- **SECRET-MGMT-001:** Secrets via `getenv()` en `settings.secrets.php`. NUNCA en config/sync
- **AUDIT-SEC-003:** NUNCA `|raw` en Twig sin sanitizacion previa en servidor
- **INNERHTML-XSS-001:** `Drupal.checkPlain()` o `escapeHtml()` para datos de API insertados via innerHTML
- **ACCESS-STRICT-001:** Comparaciones ownership con `(int)..===(int)`, NUNCA `==`
- **AI-GUARDRAILS-PII-001:** Deteccion PII bidireccional (input + output)
- **CSRF-API-001:** Rutas API POST/PATCH/DELETE con `_csrf_request_header_token: 'TRUE'`
- **TENANT-ISOLATION-ACCESS-001:** Todo AccessControlHandler verifica tenant match
- **PHP 8.4:** `declare(strict_types=1)`, respeto CONTROLLER-READONLY-001 y DRUPAL11-001
- **Textos:** SIEMPRE traducibles via `$this->t()` en PHP, `Drupal.t()` en JS, `{% trans %}` en Twig
- **SCSS:** Variables `var(--ej-*, fallback)`, Dart Sass moderno con `@use`, compilar y verificar
- **Templates:** Zero-region pattern, `clean_content`, body classes via `hook_preprocess_html()`

---

## 3. Fase 1 — Fixes Inmediatos (Dia 1)

### 3.1 SEC-C04: Validacion protocolo URLs en Copilot JS

**Hallazgo:** XSS via `javascript:` en parseMarkdown del copilot contextual.

**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/js/contextual-copilot.js`

**Logica del cambio:**

La funcion `parseMarkdown()` genera enlaces HTML a partir de respuestas del LLM sin validar el protocolo de la URL. El fix introduce una funcion `isSafeUrl()` que solo permite protocolos `http:` y `https:`, rechazando `javascript:`, `data:`, `vbscript:` y cualquier otro protocolo potencialmente peligroso.

Se aplica en DOS puntos:
1. **Links Markdown** `[texto](url)` — linea ~112
2. **ACTION links** `[ACTION:label|url]` — linea ~83

**Implementacion:**

```javascript
/**
 * Valida que una URL tiene protocolo seguro (http/https).
 *
 * Previene XSS via javascript:, data:, vbscript: en enlaces
 * generados desde respuestas del LLM.
 *
 * @param {string} url - URL a validar.
 * @return {boolean} TRUE si la URL es segura.
 */
function isSafeUrl(url) {
  try {
    var parsed = new URL(url, window.location.href);
    return ['http:', 'https:'].indexOf(parsed.protocol) !== -1;
  } catch (e) {
    return false;
  }
}
```

En la seccion de links Markdown, antes del `push`:

```javascript
if (!isSafeUrl(url)) {
  // Devolver texto plano sin enlace
  return escapeHtml(match);
}
```

En la seccion de ACTION links, misma validacion antes del `push`.

**Verificacion RUNTIME-VERIFY-001:**

1. Abrir copilot en `https://jaraba-saas.lndo.site/`
2. Verificar que links normales `[texto](https://...)` funcionan
3. Verificar que `[click](javascript:alert(1))` se renderiza como texto plano, NO como enlace
4. Verificar en DevTools que no hay errores JS en consola

**Directrices cumplidas:** INNERHTML-XSS-001, AUDIT-SEC-003, JS: Vanilla JS + Drupal.behaviors

---

### 3.2 SEC-C02: HMAC secret adjuntos via getenv()

**Hallazgo:** Secret HMAC hardcodeado con valor por defecto inseguro.

**Archivos a modificar:**
- `web/modules/custom/jaraba_support/src/Service/AttachmentUrlService.php`
- `config/deploy/settings.secrets.php`

**Logica del cambio:**

El fallback `'jaraba_support_default_key'` se elimina. En su lugar: (1) `settings.secrets.php` inyecta el secret via `getenv()` como config override, (2) el servicio lee el secret del config y lanza excepcion si esta vacio en produccion.

**Implementacion en `settings.secrets.php`:**

```php
// Jaraba Support — HMAC secret para URLs de adjuntos.
if ($attachment_hmac = getenv('SUPPORT_ATTACHMENT_HMAC_SECRET')) {
  $config['jaraba_support.settings']['attachment_hmac_secret'] = $attachment_hmac;
}
```

**Implementacion en `AttachmentUrlService.php`:**

Reemplazar:
```php
->get('attachment_hmac_secret') ?? 'jaraba_support_default_key';
```
Por:
```php
->get('attachment_hmac_secret');
if (empty($secret)) {
  $this->logger->error('SUPPORT_ATTACHMENT_HMAC_SECRET not configured. Attachment URLs cannot be signed.');
  throw new \RuntimeException('Attachment HMAC secret not configured.');
}
```

**En servidor IONOS (variable de entorno):**

```bash
# Generar secret aleatorio de 256 bits:
openssl rand -hex 32
# Resultado ejemplo: a3b7c9d1e5f2...
# Anadir a /etc/environment o al mecanismo de env vars del servidor
```

**Verificacion RUNTIME-VERIFY-001:**

1. En dev local: configurar variable `SUPPORT_ATTACHMENT_HMAC_SECRET=test_secret_dev_only` en `.lando.yml` o `.env`
2. Crear un ticket de soporte con adjunto
3. Verificar que la URL de descarga funciona con el secret correcto
4. Verificar que la URL falla con un secret incorrecto (respuesta 403)
5. Verificar en logs que no aparece el secret hardcodeado

**Directrices cumplidas:** SECRET-MGMT-001, AUDIT-SEC-001

---

### 3.3 SEC-A07: Cast int en tenant ID comparison

**Hallazgo:** Type mismatch `string === int` siempre FALSE en access handler.

**Archivo a modificar:** `web/modules/custom/jaraba_interactive/src/InteractiveContentAccessControlHandler.php`

**Logica del cambio:**

`EntityInterface::id()` devuelve `string` en MariaDB con auto-increment. El parametro `$tenantId` esta tipado como `int`. La comparacion `=== ` entre tipos diferentes es siempre FALSE en PHP con `strict_types=1`. Se anade cast `(int)` a ambos lados para garantizar comparacion correcta.

**Implementacion:**

Reemplazar (linea ~96):
```php
if ($membership->getGroup()->id() === $tenantId) {
```
Por:
```php
if ((int) $membership->getGroup()->id() === (int) $tenantId) {
```

**Patron correcto de referencia:** `jaraba_rag/src/Service/RagTenantFilterService.php:285`

**Verificacion RUNTIME-VERIFY-001:**

1. Como usuario de un tenant, acceder a contenido interactivo propio — debe permitir acceso
2. Verificar que contenido interactivo de OTRO tenant NO es accesible (aislamiento multi-tenant)
3. PHPStan L6 debe pasar sin errores nuevos

**Directrices cumplidas:** ACCESS-STRICT-001, TENANT-ISOLATION-ACCESS-001

---

### 3.4 SEC-A08: Actualizar google/protobuf

**Hallazgo:** Vulnerabilidad DoS en google/protobuf v4.33.5.

**Comando:**

```bash
lando composer update google/protobuf --with-dependencies
```

**Verificacion:**

```bash
lando composer audit  # No debe reportar google/protobuf
lando composer show google/protobuf  # Debe ser >= 4.33.6
```

**Directrices cumplidas:** Gestion de dependencias, CI security gates

---

### 3.5 SEC-C05: Sanitizar SuccessCase text_long

**Hallazgo:** Stored XSS via campos `text_long` con `|raw` sin sanitizar.

**Archivo a modificar:** `web/modules/custom/jaraba_success_cases/src/Controller/CaseStudyLandingController.php`

**Logica del cambio:**

Los campos `challenge_before`, `solution_during` y `result_after` se pasan como `.value` crudo al template. Se aplica `Xss::filterAdmin()` para permitir HTML basico (negritas, listas, enlaces) pero bloquear scripts, iframes y otros elementos peligrosos.

**Implementacion (lineas ~241-243):**

Reemplazar:
```php
'challenge_before' => $case->get('challenge_before')->value ?? '',
'solution_during' => $case->get('solution_during')->value ?? '',
'result_after' => $case->get('result_after')->value ?? '',
```
Por:
```php
'challenge_before' => Xss::filterAdmin($case->get('challenge_before')->value ?? ''),
'solution_during' => Xss::filterAdmin($case->get('solution_during')->value ?? ''),
'result_after' => Xss::filterAdmin($case->get('result_after')->value ?? ''),
```

Anadir `use Drupal\Component\Utility\Xss;` en la seccion de imports si no existe.

**Verificacion RUNTIME-VERIFY-001:**

1. Navegar a una pagina de caso de exito con contenido en los 3 campos
2. Verificar que el HTML basico (negritas, parrafos, listas) se renderiza correctamente
3. Verificar que intentos de inyeccion `<script>alert(1)</script>` son filtrados
4. Verificar que la maquetacion visual no se rompe

**Directrices cumplidas:** AUDIT-SEC-003, SUCCESS-CASES-001

---

### 3.6 SEC-A05: Sanitizar InfoPagesController content

**Hallazgo:** XSS en `content|raw` de InfoPagesController.

**Archivo a modificar:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/InfoPagesController.php`

**Logica del cambio:**

Aplicar el mismo patron de sanitizacion que `StaticPageController::sanitizeContent()`. Se aplica `Xss::filterAdmin()` a `content` antes de pasarlo al template.

**Implementacion:**

En el metodo `about()`, reemplazar:
```php
$content = theme_get_setting('about_content', 'ecosistema_jaraba_theme') ?: '';
```
Por:
```php
$content = Xss::filterAdmin(theme_get_setting('about_content', 'ecosistema_jaraba_theme') ?: '');
```

Aplicar identico cambio en `contact()` si usa el mismo patron.

**Verificacion RUNTIME-VERIFY-001:**

1. Navegar a `/about` — el contenido debe renderizarse correctamente
2. Si hay HTML basico, debe mantenerse
3. Scripts inyectados deben ser filtrados

**Directrices cumplidas:** AUDIT-SEC-003

---

### 3.7 SEC-A06: striptags en map_embed jarabalex

**Hallazgo:** XSS en `map_embed|raw` sin filtro striptags.

**Archivo a modificar:** `web/modules/custom/jaraba_page_builder/templates/blocks/verticals/jarabalex/map.html.twig`

**Logica del cambio:**

Aplicar el mismo patron `|striptags('<iframe>')` que usan los otros 6 bloques de mapa del modulo. Esto permite solo `<iframe>` (necesario para embeds de Google Maps) y elimina cualquier otro tag HTML.

**Implementacion (linea ~22):**

Reemplazar:
```twig
{{ map_embed|raw }}
```
Por:
```twig
{{ map_embed|striptags('<iframe>')|raw }}
```

**Verificacion RUNTIME-VERIFY-001:**

1. Verificar que el mapa embebido de jarabalex sigue renderizandose correctamente
2. Verificar consistencia con otros bloques de mapa del modulo

**Directrices cumplidas:** AUDIT-SEC-003, TWIG-RAW-AUDIT-001 (propuesta)

---

## 4. Fase 2 — Fixes Criticos Estructurales (Semana 1)

### 4.1 SEC-C01: Migrar Claude API key a getenv()

**Hallazgo:** Claude API key almacenada en BD via Key module en lugar de variable de entorno.

**Archivos a modificar:**
- `config/deploy/settings.secrets.php`
- `web/modules/custom/jaraba_copilot_v2/src/Service/ClaudeApiService.php`
- `config/sync/key.key.claude_api_key.yml`

**Logica del cambio:**

La migracion tiene 3 pasos: (1) anadir override en `settings.secrets.php` via `getenv('CLAUDE_API_KEY')`, (2) modificar `ClaudeApiService::getApiKey()` para leer directamente del config override (sin pasar por Key module), (3) actualizar la key entity YAML con `key_provider: env`.

**Paso 1 — settings.secrets.php:**

```php
// Claude API — migrado de Key module config a env.
if ($claude_api_key = getenv('CLAUDE_API_KEY')) {
  $config['jaraba_copilot_v2.settings']['claude_api_key_value'] = $claude_api_key;
}
```

**Paso 2 — ClaudeApiService.php:**

Modificar `getApiKey()` para leer de config override primero:

```php
protected function getApiKey(): string {
  // Prioridad 1: Config override via settings.secrets.php (getenv).
  $keyFromConfig = $this->configFactory->get('jaraba_copilot_v2.settings')->get('claude_api_key_value');
  if (!empty($keyFromConfig)) {
    return $keyFromConfig;
  }

  // Prioridad 2: Key module (legacy, para migracion gradual).
  $keyId = $this->configFactory->get('jaraba_copilot_v2.settings')->get('claude_api_key');
  if ($keyId && $this->keyRepository) {
    $key = $this->keyRepository->getKey($keyId);
    if ($key) {
      return $key->getKeyValue();
    }
  }

  throw new \RuntimeException('Claude API key not configured. Set CLAUDE_API_KEY environment variable.');
}
```

**Paso 3 — key.key.claude_api_key.yml:**

```yaml
key_provider: env
key_provider_settings:
  env_variable: CLAUDE_API_KEY
```

**En servidor IONOS:**

Anadir `CLAUDE_API_KEY=sk-ant-...` a las variables de entorno del servidor.

**Verificacion RUNTIME-VERIFY-001:**

1. En dev local: configurar `CLAUDE_API_KEY` en entorno Lando
2. Probar copilot contextual — debe responder normalmente
3. Verificar en logs que no hay errores de API key
4. `lando drush config:export --diff` — verificar que la key NO aparece en el export
5. PHPStan L6 sin errores nuevos

**Directrices cumplidas:** SECRET-MGMT-001, KEY-DEPRECATED-001

---

### 4.2 SEC-C03: checkPII() bidireccional en copilot y agents

**Hallazgo:** PII no verificado en input al LLM, solo en output.

**Archivos a modificar:**
- `web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php`
- `web/modules/custom/jaraba_ai_agents/src/Agent/SmartBaseAgent.php`

**Logica del cambio:**

El guardrail PII actual solo filtra output (`maskOutputPII()`). Se anade verificacion en input antes de enviar al LLM:

1. **CopilotOrchestratorService:** En `chat()` antes de `callProvider()`, invocar `checkPII()` en el mensaje del usuario. Si detecta PII bloqueado, devolver respuesta informativa sin enviar al LLM. Si detecta PII enmascarable, usar version enmascarada.

2. **SmartBaseAgent:** En `execute()` o `doExecute()`, verificar PII en el prompt/contexto antes de `callAiApiWithTools()`.

**Implementacion en CopilotOrchestratorService::chat():**

Antes de la llamada a `callProvider()` (linea ~260):

```php
// AI-GUARDRAILS-PII-001: Verificar PII en input ANTES de enviar al LLM.
if ($this->guardrailsService) {
  try {
    $piiResult = $this->guardrailsService->checkPII($message);
    if (!empty($piiResult['has_pii'])) {
      if (!empty($piiResult['blocked'])) {
        $this->logger->warning('PII blocked in copilot input: @types', [
          '@types' => implode(', ', $piiResult['detected_types'] ?? []),
        ]);
        return [
          'text' => $this->t('Por seguridad, no puedo procesar mensajes con datos personales identificativos (DNI, NIE, IBAN, telefono, etc.). Por favor, reformula tu consulta sin incluir estos datos.'),
          'pii_blocked' => TRUE,
        ];
      }
      // Usar version enmascarada si hay PII detectado pero no bloqueado.
      $message = $piiResult['masked'] ?? $message;
    }
  }
  catch (\Throwable $e) {
    // Guardrail failure no debe bloquear la funcionalidad.
    $this->logger->error('PII check failed: @error', ['@error' => $e->getMessage()]);
  }
}
```

**Implementacion en SmartBaseAgent:**

Patron analogo en `execute()` antes de `callAiApiWithTools()`, usando el servicio `$this->guardrailsService` (inyectado como `@?jaraba_ai_agents.guardrails_service`).

**Verificacion RUNTIME-VERIFY-001:**

1. Enviar al copilot un mensaje con DNI: "Mi DNI es 12345678A" — debe bloquear y mostrar mensaje informativo
2. Enviar al copilot un mensaje con NIE: "Quiero registrar el NIE X1234567A" — debe bloquear
3. Enviar al copilot un mensaje con IBAN: "Mi IBAN es ES1234567890123456789012" — debe bloquear
4. Enviar un mensaje normal sin PII — debe funcionar con normalidad
5. Verificar en logs de Drupal que los eventos PII se registran

**Directrices cumplidas:** AI-GUARDRAILS-PII-001, RGPD Art. 44-46, PRESAVE-RESILIENCE-001 (try-catch)

---

### 4.3 SEC-A01: Fingerprint SSH fijo en CI workflows

**Hallazgo:** `ssh-keyscan` dinamico sin verificacion de fingerprint en 3 workflows.

**Archivos a modificar:**
- `.github/workflows/deploy.yml`
- `.github/workflows/daily-backup.yml`
- `.github/workflows/verify-backups.yml`

**Logica del cambio:**

Reemplazar el patron inseguro de `ssh-keyscan` por un fingerprint pre-almacenado en GitHub Secrets.

**Paso 1 — Obtener fingerprint (una sola vez, verificar en persona):**

```bash
ssh-keyscan -p 2222 -H 82.223.204.169
# Copiar el resultado completo (todas las lineas) al clipboard
```

**Paso 2 — Crear GitHub Secret:**

Nombre: `DEPLOY_HOST_KNOWN_HOSTS`
Valor: resultado del ssh-keyscan

**Paso 3 — Modificar los 3 workflows:**

Reemplazar en cada workflow:
```yaml
- name: Add server to known hosts
  run: |
    mkdir -p ~/.ssh
    ssh-keyscan -p ${{ env.DEPLOY_PORT }} -H ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts
```
Por:
```yaml
- name: Add server to known hosts (verified fingerprint)
  run: |
    mkdir -p ~/.ssh
    echo "${{ secrets.DEPLOY_HOST_KNOWN_HOSTS }}" >> ~/.ssh/known_hosts
```

**Verificacion:**

1. Ejecutar workflow de deploy en una rama de test
2. Verificar que la conexion SSH funciona con el fingerprint fijo
3. Verificar que no hay warnings de `known_hosts` en los logs del workflow

**Directrices cumplidas:** SSH-FINGERPRINT-001 (propuesta)

---

### 4.4 SEC-A09 + SEC-M08: Promover validators a run_check

**Hallazgo:** Validators de seguridad criticos como `warn_check` en CI.

**Archivo a modificar:** `scripts/validation/validate-all.sh`

**Cambios:**

1. Linea ~635: Cambiar `warn_check "API-CONTRACT-001"` por `run_check "API-CONTRACT-001"`
2. Linea ~632: Cambiar `warn_check "ACCESS-HANDLER-IMPL-001"` por `run_check "ACCESS-HANDLER-IMPL-001"`

**Verificacion:**

```bash
lando bash -c "bash scripts/validation/validate-all.sh --checklist web/modules/custom/ecosistema_jaraba_core"
# Debe ejecutar ambos como run_check (fallo bloquea el script)
```

**Directrices cumplidas:** VALIDATOR-SECURITY-RUNCHECK-001 (propuesta)

---

## 5. Fase 3 — Hardening Infraestructura (Sprint Siguiente)

### 5.1 SEC-M02: Sleep en workers Supervisor

**Archivo a modificar:** `config/deploy/supervisor-ai-workers.conf`

**Cambios en cada `command=`:**

```ini
; ANTES:
command=/var/www/jaraba/vendor/bin/drush queue:run a2a_task_worker --time-limit=300
; DESPUES:
command=bash -c '/var/www/jaraba/vendor/bin/drush queue:run a2a_task_worker --time-limit=300; sleep 30'

; Repetir para insights (sleep 45), quality (sleep 60), scheduled (sleep 60)
```

**Verificacion en produccion:**

```bash
# Tras deploy, verificar que los workers no consumen CPU excesiva:
top -b -n1 | grep drush
# Debe mostrar consumo bajo cuando las colas estan vacias
```

**Directrices cumplidas:** SUPERVISOR-SLEEP-001

---

### 5.2 SEC-M03: MariaDB hardening

**Archivo a modificar:** `config/deploy/mariadb/my.cnf`

**Cambios:**

```ini
[mysqld]
# Seguridad de red
bind-address = 127.0.0.1

# Prevenir LOAD DATA LOCAL INFILE
local-infile = 0

# Deshabilitar event scheduler (superficie de ataque)
event_scheduler = OFF

# Aumentar retencion de binlogs para PITR extendido
expire_logs_days = 7
```

**Verificacion en produccion:**

```bash
mysql -u root -p -e "SHOW VARIABLES LIKE 'bind_address';"
# Debe mostrar 127.0.0.1
mysql -u root -p -e "SHOW VARIABLES LIKE 'local_infile';"
# Debe mostrar OFF
```

---

### 5.3 SEC-M05: Redis bind + users.acl

**Archivo a modificar:** `infrastructure/ha/redis/redis.conf`

**Cambios:**

```conf
# Cambiar:
bind 0.0.0.0
# Por:
bind 127.0.0.1
```

**Archivo a crear:** `infrastructure/ha/redis/users.acl` (template de referencia)

```conf
# REDIS-ACL-001: 3 usuarios con permisos granulares.
# Passwords DEBEN configurarse como variables de entorno.
# Este archivo es un TEMPLATE — los passwords reales se inyectan en deploy.

# Usuario default (Drupal): solo keys jaraba_*, sin comandos peligrosos
user default on >PLACEHOLDER_DEFAULT_PASSWORD ~jaraba_* &* +@all -@dangerous -@admin

# Usuario admin: mantenimiento, todos los permisos
user admin on >PLACEHOLDER_ADMIN_PASSWORD ~* &* +@all

# Usuario monitor: read-only
user monitor on >PLACEHOLDER_MONITOR_PASSWORD ~* &* +@read -@write -@admin -@dangerous
```

---

### 5.4 SEC-A10: Validator validate-twig-raw-audit.php

**Archivo a crear:** `scripts/validation/validate-twig-raw-audit.php`

**Logica:**

El validator escanea todos los archivos `.html.twig` en `web/modules/custom/` y `web/themes/custom/` buscando usos de `|raw`. Para cada uso:

1. Extrae el nombre de la variable (ej: `content` en `{{ content|raw }}`)
2. Verifica si el uso esta en la allowlist de usos seguros conocidos (JSON-LD con `|json_encode|raw`, SVG inline hardcodeado, `|striptags(...)|raw`)
3. Si no esta en la allowlist, busca en el controller/preprocess asociado si existe una llamada a `check_markup()`, `Xss::filter*()`, `Markup::create()`, o `sanitize*()` para esa variable
4. Reporta como WARNING los usos no verificables

**Allowlist (usos seguros conocidos):**

```php
$safePatterns = [
  '|json_encode|raw',           // JSON-LD Schema.org
  '|striptags(\'<iframe>\')|raw', // Map embeds
  'jaraba_icon(|raw',            // SVG iconos
  '<!-- AUDIT-SEC: -->',         // Usos auditados manualmente con comentario
];
```

**Registro en validate-all.sh:**

```bash
warn_check "TWIG-RAW-AUDIT-001" "validate-twig-raw-audit.php"
```

> Nota: se registra como `warn_check` inicialmente hasta que la allowlist este completa. Migrar a `run_check` cuando todos los usos esten verificados.

---

### 5.5 SEC-M04: Cifrado backups + capas 2-3

**Archivo a modificar:** `.github/workflows/daily-backup.yml`

**Cambios principales:**

1. Reemplazar fallback mysqldump por `drush sql-dump` (evita credenciales en `ps aux`)
2. Cifrar backups con GPG:

```bash
# En el step de backup:
ssh $DEPLOY_USER@$DEPLOY_HOST -p $DEPLOY_PORT \
  "cd /var/www/jaraba && vendor/bin/drush sql-dump --gzip \
   | gpg --symmetric --cipher-algo AES256 --batch --passphrase-file /root/.backup-passphrase \
   > ~/backups/daily/jaraba-$(date +%Y%m%d-%H%M).sql.gz.gpg"
```

3. Anadir sync a Hetzner S3 via `rclone`:

```bash
# Step adicional:
ssh $DEPLOY_USER@$DEPLOY_HOST -p $DEPLOY_PORT \
  "rclone sync ~/backups/daily/ hetzner:jaraba-backups/daily/ --max-age 24h"
```

---

### 5.6 SEC-A02 + SEC-A03 + SEC-M01: Nginx + PHP hardening

**Archivo a crear:** `config/deploy/php/20-security-prod.ini`

```ini
; PHP Security Hardening — Produccion IONOS
; Referencia: SEC-A03, SEC-M01

expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/jaraba/php-error.log

; Session security
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.gc_maxlifetime = 1440

; Funciones peligrosas (defense-in-depth con PHPStan bans)
; Nota: Drupal necesita proc_open para composer y drush.
; No deshabilitar funciones que Drupal requiere en runtime.
```

**Archivo a modificar:** `config/deploy/nginx-jaraba-common.conf`

Anadir al inicio del archivo (dentro de `http {}` o incluido globalmente):

```nginx
# SEC-A02: Hardening Nginx
server_tokens off;

# Rate limiting (fuera de server blocks)
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
```

Anadir en los server blocks, location para login:

```nginx
location ~* ^/(es/|en/)?(user/login|user/register) {
    limit_req zone=login burst=10 nodelay;
    try_files $uri /index.php?$query_string;
}
```

**Modificar en deploy.yml** el step de sync PHP config para incluir el nuevo archivo:

```yaml
- name: Sync PHP security config
  run: |
    scp -P ${{ env.DEPLOY_PORT }} \
      config/deploy/php/20-security-prod.ini \
      ${{ env.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }}:/etc/php/8.4/fpm/conf.d/20-security-prod.ini
```

---

## 6. Fase 4 — Mejoras Medio Plazo

### 6.1 SEC-A04: CSP nonces + separacion frontend/admin

**Estrategia:**

Separar la CSP en dos perfiles:

1. **CSP Frontend publico:** Sin `unsafe-eval`, sin CDNs publicas, con nonces para scripts inline legitimos de Drupal. Solo `'self'` + dominios de confianza (js.stripe.com, connect.facebook.net).

2. **CSP Admin/Editor:** Mantiene `unsafe-eval` para GrapesJS (que lo requiere para su motor de templates). Solo aplica a rutas que empiezan por `/admin/` o `/node/*/edit` o rutas del canvas editor.

**Implementacion en SecurityHeadersSubscriber:**

```php
public function onKernelResponse(ResponseEvent $event): void {
  $request = $event->getRequest();
  $path = $request->getPathInfo();

  // Determinar perfil CSP segun la ruta.
  $isAdmin = str_starts_with($path, '/admin/')
    || str_contains($path, '/edit')
    || str_contains($path, '/canvas');

  $scriptSrc = $isAdmin
    ? "script-src 'self' 'unsafe-inline' 'unsafe-eval'"
    : "script-src 'self' 'nonce-{$nonce}' js.stripe.com connect.facebook.net";

  // ... resto del CSP
}
```

**Nota:** Requiere verificar que GrapesJS 5.7 realmente necesita `unsafe-eval` con la version actual. Algunas versiones mas recientes han eliminado esta dependencia.

### 6.2 Nuevos validators de seguridad

| Validator | Descripcion | Tipo | Esfuerzo |
|-----------|-------------|------|----------|
| `validate-twig-raw-audit.php` | Audita usos de `\|raw` en Twig | warn_check (inicialmente) | 4h |
| `validate-ai-pii-guardrails.php` | Verifica `checkPII()` antes de `callProvider()`/`callAiApiWithTools()` | run_check | 4h |
| `validate-secrets-in-config-sync.php` | Busca valores no vacios en campos sensibles de config/sync YAML | run_check | 2h |
| `validate-raw-query-audit.php` | Lista `->query(` fuera de `.install` con `$` interpolado | warn_check | 2h |
| `validate-js-xss-audit.php` | Detecta `innerHTML` sin `escapeHtml`/`checkPlain` previo | warn_check | 4h |

### 6.3 Medidas de salvaguarda adicionales

Las siguientes medidas de salvaguarda se recomiendan para proteccion futura del SaaS:

#### 6.3.1 SAFEGUARD-SECURITY-REGRESSION-001: Test de regresion para cada fix

Cada fix de seguridad de esta auditoria DEBE tener un test asociado que verifique que la vulnerabilidad no se reintroduce:

| Fix | Test sugerido |
|-----|---------------|
| SEC-C04 (javascript: URL) | Unit test en `contextual-copilot.test.js` que verifique `isSafeUrl()` rechaza `javascript:`, `data:` |
| SEC-C05 (SuccessCase XSS) | Kernel test que verifique que `<script>` en `challenge_before` es filtrado |
| SEC-A07 (type mismatch) | Kernel test que verifique acceso con tenant ID como string vs int |
| SEC-C03 (PII input) | Unit test que verifique que copilot bloquea mensajes con DNI/NIE/IBAN |

#### 6.3.2 SAFEGUARD-SECURITY-REVIEW-001: Revision de seguridad en PRs

Anadir label `security-review-required` automatica via GitHub Action cuando un PR modifica:
- Archivos `*.routing.yml` (nuevas rutas)
- AccessControlHandler files
- Templates con `|raw`
- Archivos JS con `innerHTML`
- `settings.secrets.php` o `trivy.yaml`

#### 6.3.3 SAFEGUARD-PII-AUDIT-TRAIL-001: Log de PII detectado

Cada deteccion de PII (input o output) debe generar un registro auditable para cumplimiento RGPD:
- Timestamp
- Tipo de PII detectado (DNI, NIE, IBAN, telefono)
- Accion tomada (blocked, masked)
- User ID (anonimizado)
- Canal (copilot, agent, MCP)

#### 6.3.4 SAFEGUARD-DEPENDENCY-ALERT-001: Alerta automatica de dependencias

Anadir GitHub Dependabot con notificacion inmediata para vulnerabilidades CRITICAL/HIGH en dependencias PHP y JS. Actualmente existe Trivy pero solo en CI — Dependabot provee alertas proactivas sin necesidad de ejecutar el pipeline.

#### 6.3.5 SAFEGUARD-SECRET-ROTATION-001: Rotacion periodica de secrets

Implementar recordatorio automatico (cron mensual) que verifique la antiguedad de los secrets de produccion y genere alerta cuando un secret lleve mas de 90 dias sin rotarse. Secrets cubiertos: `CLAUDE_API_KEY`, `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, `SUPPORT_ATTACHMENT_HMAC_SECRET`, `REDIS_PASSWORD`.

---

## 7. Tabla de Correspondencia con Especificaciones Tecnicas

| Hallazgo | Archivo(s) principal(es) | Modulo(s) afectado(s) | Tipo de cambio | Regla CLAUDE.md |
|----------|--------------------------|----------------------|----------------|-----------------|
| SEC-C01 | `ClaudeApiService.php`, `settings.secrets.php`, `key.key.claude_api_key.yml` | jaraba_copilot_v2 | PHP + YAML + config | SECRET-MGMT-001, KEY-DEPRECATED-001 |
| SEC-C02 | `AttachmentUrlService.php`, `settings.secrets.php` | jaraba_support | PHP + config | SECRET-MGMT-001, AUDIT-SEC-001 |
| SEC-C03 | `CopilotOrchestratorService.php`, `SmartBaseAgent.php` | jaraba_copilot_v2, jaraba_ai_agents | PHP | AI-GUARDRAILS-PII-001 |
| SEC-C04 | `contextual-copilot.js` | ecosistema_jaraba_core | JS | INNERHTML-XSS-001 |
| SEC-C05 | `CaseStudyLandingController.php` | jaraba_success_cases | PHP | AUDIT-SEC-003 |
| SEC-A01 | `deploy.yml`, `daily-backup.yml`, `verify-backups.yml` | CI/CD | YAML | SSH-FINGERPRINT-001 |
| SEC-A02 | `nginx-jaraba-common.conf` | Infra | Nginx conf | NGINX-HARDENING-001 |
| SEC-A03 | `php.ini`, `20-security-prod.ini` | Infra | INI | OWASP A05 |
| SEC-A04 | `SecurityHeadersSubscriber.php` | ecosistema_jaraba_core | PHP | CSP-POLICY-001 |
| SEC-A05 | `InfoPagesController.php` | ecosistema_jaraba_core | PHP | AUDIT-SEC-003 |
| SEC-A06 | `map.html.twig` | jaraba_page_builder | Twig | AUDIT-SEC-003 |
| SEC-A07 | `InteractiveContentAccessControlHandler.php` | jaraba_interactive | PHP | ACCESS-STRICT-001 |
| SEC-A08 | `composer.lock` | Deps | Composer | GHSA-p2gh-cfq4-4wjc |
| SEC-A09 | `validate-all.sh` | Safeguard | Shell | CSRF-API-001 |
| SEC-A10 | Nuevo `validate-twig-raw-audit.php` | Safeguard | PHP | TWIG-RAW-AUDIT-001 |
| SEC-M01 | Nuevo `20-security-prod.ini` | Infra | INI | OWASP A05 |
| SEC-M02 | `supervisor-ai-workers.conf` | Infra | Conf | SUPERVISOR-SLEEP-001 |
| SEC-M03 | `my.cnf` | Infra | MariaDB conf | CWE-16 |
| SEC-M04 | `daily-backup.yml` | CI/CD | YAML | BACKUP-3LAYER-001, BACKUP-ENCRYPT-001 |
| SEC-M05 | `redis.conf`, `users.acl` | Infra | Redis conf | REDIS-ACL-001 |
| SEC-M06 | `trivy.yaml` | Safeguard | YAML | SECRET-MGMT-001 |
| SEC-M07 | `validate-all.sh` | Safeguard | Shell | AUDIT-SEC-002 |
| SEC-M08 | `validate-all.sh` | Safeguard | Shell | TENANT-ISOLATION-ACCESS-001 |

---

## 8. Tabla de Cumplimiento de Directrices

| Directriz | Estado actual | Post-remediacion | Fase |
|-----------|--------------|-------------------|------|
| SECRET-MGMT-001 | PARCIAL (Claude key en DB) | COMPLETO | Fase 2 |
| KEY-DEPRECATED-001 | FALLO (Claude key usa Key module) | COMPLETO | Fase 2 |
| AI-GUARDRAILS-PII-001 | PARCIAL (solo output) | COMPLETO (bidireccional) | Fase 2 |
| INNERHTML-XSS-001 | FALLO (no valida protocolo URL) | COMPLETO | Fase 1 |
| AUDIT-SEC-003 | PARCIAL (3 puntos sin sanitizar) | COMPLETO | Fase 1 |
| ACCESS-STRICT-001 | PARCIAL (1 type mismatch) | COMPLETO | Fase 1 |
| TENANT-ISOLATION-ACCESS-001 | PARCIAL (validator warn) | COMPLETO (run_check) | Fase 2 |
| CSRF-API-001 | PARCIAL (validator warn) | COMPLETO (run_check) | Fase 2 |
| SUPERVISOR-SLEEP-001 | FALLO (4/5 workers sin sleep) | COMPLETO | Fase 3 |
| BACKUP-3LAYER-001 | PARCIAL (solo capa 1 local) | COMPLETO (3 capas + cifrado) | Fase 3 |
| REDIS-ACL-001 | PARCIAL (users.acl no en repo) | COMPLETO | Fase 3 |
| CSP-POLICY-001 | PARCIAL (unsafe-inline/eval) | MEJORADO (separacion) | Fase 4 |
| AUDIT-SEC-001 (Stripe) | PASS | PASS | N/A |
| CSRF-LOGIN-FIX-001 | PASS | PASS | N/A |
| VARY-HOST-001 | PASS | PASS | N/A |

---

## 9. Verificacion RUNTIME-VERIFY-001

Tras completar cada fase, verificar las 5 dependencias runtime:

### Fase 1 — Checklist post-implementacion

- [ ] JS: `contextual-copilot.js` cargado correctamente (sin errores en consola)
- [ ] JS: Links del copilot con `https://` funcionan, `javascript:` bloqueados
- [ ] PHP: `AttachmentUrlService` lee secret de env (verificar en logs)
- [ ] PHP: `InteractiveContentAccessControlHandler` permite acceso al tenant correcto
- [ ] PHP: `CaseStudyLandingController` renderiza HTML basico, filtra scripts
- [ ] PHP: `InfoPagesController` renderiza contenido sanitizado
- [ ] Twig: `map.html.twig` renderiza iframe de Google Maps
- [ ] Composer: `google/protobuf >= 4.33.6` instalado
- [ ] CI: `composer audit` sin vulnerabilidades

### Fase 2 — Checklist post-implementacion

- [ ] PHP: Copilot responde con normalidad usando Claude API via env
- [ ] PHP: Copilot bloquea mensajes con DNI/NIE/IBAN
- [ ] PHP: Agentes Gen 2 bloquean PII en input
- [ ] CI: SSH connection funciona con fingerprint fijo en los 3 workflows
- [ ] CI: `validate-api-contract.php` como `run_check` falla si falta CSRF
- [ ] CI: `validate-access-handler-impl.php` como `run_check` falla si falta tenant check

### Fase 3 — Checklist post-implementacion

- [ ] Servidor: Workers Supervisor no consumen CPU excesiva con colas vacias
- [ ] Servidor: MariaDB solo escucha en 127.0.0.1
- [ ] Servidor: Redis solo escucha en 127.0.0.1
- [ ] CI: Backups cifrados con GPG
- [ ] Servidor: `expose_php = Off`, `display_errors = Off`
- [ ] Servidor: `server_tokens off` en Nginx
- [ ] Servidor: Rate limiting activo para `/user/login`

---

## 10. Nuevas Reglas para CLAUDE.md

Las siguientes reglas se proponen para incorporar a CLAUDE.md tras la remediacion:

```
### Reglas derivadas de Auditoria 2026-03-26

- PII-INPUT-GUARD-001: Todo input a LLM externo DEBE pasar por checkPII() antes de callProvider()/callAiApiWithTools(). Output ya cubierto por maskOutputPII()
- TWIG-RAW-AUDIT-001: Toda variable Twig con |raw DEBE tener sanitizacion verificable en controller/preprocess (check_markup, Xss::filter*, Markup::create). Validacion: validate-twig-raw-audit.php
- URL-PROTOCOL-VALIDATE-001: URLs de datos externos (LLM, API, user input) insertadas en href DEBEN validar protocolo (http:/https: exclusivamente). Previene javascript: XSS
- SSH-FINGERPRINT-001: Workflows CI/CD DEBEN usar fingerprint SSH pre-almacenado en Secrets. NUNCA ssh-keyscan dinamico
- NGINX-HARDENING-001: Config Nginx del repo DEBE incluir server_tokens off, ssl_protocols TLSv1.2/1.3, limit_req para login/API
- BACKUP-ENCRYPT-001: Backups SQL DEBEN cifrarse con GPG antes de almacenar (local o remoto)
- VALIDATOR-SECURITY-RUNCHECK-001: Validators de CSRF, access handlers y route permissions DEBEN ser run_check. NUNCA warn_check
```

---

## 11. Glosario de Terminos

| Termino | Definicion |
|---------|-----------|
| **ACL** | Access Control List — mecanismo de Redis 8.0 para permisos granulares por usuario |
| **AEPD** | Agencia Espanola de Proteccion de Datos — regulador RGPD en Espana |
| **Allowlist** | Lista de usos conocidos y verificados como seguros (opuesto a blacklist) |
| **CSP** | Content Security Policy — header HTTP que restringe fuentes de contenido ejecutable |
| **CWE** | Common Weakness Enumeration — catalogo estandar de debilidades de software |
| **DAST** | Dynamic Application Security Testing — pruebas de seguridad en runtime |
| **Fingerprint SSH** | Huella digital unica del servidor SSH para verificar su identidad |
| **GPG** | GNU Privacy Guard — herramienta de cifrado asimetrico y simetrico |
| **HMAC** | Hash-based Message Authentication Code — firma criptografica de integridad |
| **Key module** | Modulo Drupal para gestionar claves API con providers (config, env, file) |
| **Nonce CSP** | Valor aleatorio unico por request usado para autorizar scripts inline en CSP |
| **PII** | Personally Identifiable Information — DNI, NIE, IBAN, telefono, etc. |
| **RGPD** | Reglamento General de Proteccion de Datos — legislacion europea de privacidad (2018) |
| **SAST** | Static Application Security Testing — analisis de seguridad sobre codigo fuente |
| **TOFU** | Trust On First Use — modelo de confianza en primera conexion SSH |
| **XSS** | Cross-Site Scripting — inyeccion de scripts maliciosos en paginas web |

---

## 12. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-03-26 | 1.0.0 | IA Asistente (Claude Opus 4.6) | Plan inicial: 4 fases, 23 acciones, 7 reglas nuevas, 5 salvaguardas. Basado en auditoria 20260326 |
