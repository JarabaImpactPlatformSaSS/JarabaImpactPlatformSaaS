# Remediacion de Cambios de Gemini: Auditoria CSRF, XSS y Consistencia

**Fecha:** 2026-02-20
**Sesion:** Auditoria exhaustiva y correccion de ~40 archivos modificados por otra IA (Gemini)
**Reglas nuevas:** CSRF-API-001, TWIG-XSS-001, TM-CAST-001, PWA-META-001
**Aprendizaje #102**

---

## Contexto

Otra IA (Gemini) realizo cambios masivos en ~40 archivos del proyecto. Una auditoria exhaustiva revelo 5 problemas criticos (seguridad, bugs, pagina rota), ~15 mejoras validas que debieron conservarse, y ~18 archivos que debieron revertirse o corregirse. La sesion incluyo la correccion de dos errores runtime descubiertos post-remediacion.

---

## Lecciones Aprendidas

### 1. Las rutas API llamadas via fetch() DEBEN usar `_csrf_request_header_token`, NO `_csrf_token`

**Situacion:** El copiloto IA en `contextual-copilot.js` llamaba a `/api/v1/copilot/chat` con `fetch()`. La ruta tenia `_csrf_token: 'TRUE'` que requiere un token en el query string (`?token=abc123`). El JS nunca enviaba este token, causando un 403 silencioso que se mostraba al usuario como "Lo siento, ha ocurrido un error."

**Aprendizaje:** En Drupal, hay dos mecanismos CSRF:
- `_csrf_token: 'TRUE'` -- Requiere `?token=<csrf_token>` en el query string. Pensado para enlaces HTML generados por Drupal con `\Drupal\Core\Url`.
- `_csrf_request_header_token: 'TRUE'` -- Requiere el header `X-CSRF-Token: <token>`. Pensado para APIs llamadas via `fetch()` o AJAX.

Las rutas API que se consumen desde JavaScript DEBEN usar `_csrf_request_header_token` y el JS debe:
1. Obtener el token de `/session/token`
2. Cachearlo en memoria
3. Enviarlo como header `X-CSRF-Token`

**Regla CSRF-API-001:** Toda ruta API (`/api/v1/*`) que requiera CSRF DEBE usar `_csrf_request_header_token: 'TRUE'` (NO `_csrf_token`). El JavaScript DEBE obtener el token de `Drupal.url('session/token')` y enviarlo como header `X-CSRF-Token`.

**Patron JS correcto:**
```javascript
let csrfTokenCache = null;
function getCsrfToken() {
  if (csrfTokenCache) return Promise.resolve(csrfTokenCache);
  return fetch(Drupal.url('session/token'))
    .then(resp => resp.text())
    .then(token => { csrfTokenCache = token; return token; });
}
```

### 2. Contenido de usuario en Twig DEBE usar `|safe_html`, NUNCA `|raw`

**Situacion:** Gemini cambio 4 campos de contenido de usuario en `job-posting-detail.html.twig` de un patron seguro a `|raw`. Esto exponia la plataforma a ataques XSS inyectados via descripciones de ofertas de empleo.

**Aprendizaje:** Drupal proporciona el filtro `|safe_html` que sanitiza HTML peligroso (scripts, event handlers) mientras permite tags seguros (p, strong, ul, li, etc.). El filtro `|raw` bypasea TODA la sanitizacion.

**Regla TWIG-XSS-001:** Campos de contenido de usuario en Twig DEBEN usar `|safe_html` (NUNCA `|raw`). Solo se permite `|raw` para:
- JSON-LD schema (`schema_org_json_ld|raw`) generado por el sistema
- HTML generado completamente por PHP del backend (no user input)
- CSS/JS inline auto-generado

### 3. TranslatableMarkup NO debe pasarse a `t()` de nuevo

**Situacion:** En `TemplatePickerController.php`, los labels de categoria se creaban con `$this->t('Hero')` (que genera un objeto `TranslatableMarkup`). Estos objetos se pasaban a templates Twig donde, en ciertos contextos de renderizado con prefijo de idioma (`/es/...`), Drupal intentaba traducirlos de nuevo, causando `InvalidArgumentException: $string ("Hero") must be a string`.

**Aprendizaje:** `TranslatableMarkup` implementa `__toString()`, pero si se pasa como argumento a otra llamada de `t()` o a funciones que esperan un string estricto, Drupal 11 lanza una excepcion. La solucion es cast a `(string)` en el punto de asignacion.

**Regla TM-CAST-001:** Los valores de `$this->t()` que se pasan a render arrays o a templates Twig como variables de renderizado DEBEN castearse a `(string)` en el controlador. Ejemplo: `$labels = ['hero' => (string) $this->t('Hero')]`.

### 4. PWA requiere AMBOS meta tags: `apple-mobile-web-app-capable` y `mobile-web-app-capable`

**Situacion:** Gemini reemplazo `apple-mobile-web-app-capable` con `mobile-web-app-capable`. iOS Safari SOLO reconoce el prefijo `apple-`. Chrome/Android usa el estandar W3C.

**Aprendizaje:** Para soporte PWA completo, se necesitan ambos meta tags:
- `<meta name="apple-mobile-web-app-capable" content="yes">` (iOS Safari)
- `<meta name="mobile-web-app-capable" content="yes">` (Chrome/Android)

**Regla PWA-META-001:** La funcion `addPwaMetaTags()` en `PageAttachmentsHooks.php` DEBE incluir AMBOS meta tags: el prefijo Apple para iOS Safari y el estandar W3C para Chrome/Android.

### 5. Verificar roles especificos, NO `authenticated`

**Situacion:** En `ecosistema_jaraba_theme.theme`, Gemini uso `in_array('authenticated', $user->getRoles())` para mostrar el dashboard de candidato. En Drupal, TODOS los usuarios logueados tienen el rol `authenticated`, incluyendo administradores, reclutadores, mentores, etc.

**Aprendizaje:** Drupal asigna automaticamente el rol `authenticated` a todo usuario con sesion activa. Para logica condicional de UI, se DEBEN verificar roles especificos (`candidate`, `jobseeker`, `recruiter`) en lugar de `authenticated`.

### 6. No hardcodear URLs de Drupal, usar `Url::fromRoute()`

**Situacion:** Gemini uso `"/user/$uid/edit"` directamente en el theme preprocess. Esto rompe con prefijos de idioma (`/es/user/123/edit`), subdirectorios, y cambios de estructura de rutas.

**Aprendizaje:** SIEMPRE usar `\Drupal\Core\Url::fromRoute('entity.user.edit_form', ['user' => $uid])->toString()` para generar URLs internas. Esto respeta prefijos de idioma, rutas reescritas, y base paths.

### 7. Escapar siempre datos de usuario en HTML de emails

**Situacion:** En el email de bienvenida, `$displayName` se interpolaba directamente en HTML sin escapar. Un nombre de usuario como `<script>alert('xss')</script>` ejecutaria codigo en clientes de email que renderizan HTML.

**Aprendizaje:** Aunque muchos clientes de email sanitizan HTML, NUNCA se debe confiar en esto. Usar `\Drupal\Component\Utility\Html::escape()` para todo dato de usuario incluido en HTML de emails.

### 8. Protocolo de remediacion multi-IA: Auditar, Clasificar, Actuar

**Situacion:** La remediacion de cambios de otra IA requirio un protocolo sistematico para no perder mejoras validas ni propagar bugs.

**Aprendizaje:** El protocolo efectivo es:
1. **CLASIFICAR** cada archivo en 3 categorias: REVERT (roto/peligroso), FIX (parcialmente correcto), KEEP (valido)
2. **REVERT** primero (git checkout HEAD -- archivos), para restaurar la base segura
3. **FIX** despues (ediciones manuales), corrigiendo selectivamente
4. **VERIFICAR** cada fix con cache rebuild y test manual
5. **DOCUMENTAR** todas las reglas descubiertas como aprendizajes

---

## Resumen de Cambios

| Categoria | Archivos | Accion |
|-----------|----------|--------|
| REVERT (Template Picker + AgroConecta) | 16 | `git checkout HEAD --` |
| REVERT (CSS compilados modulo) | 2 | `git checkout HEAD --` |
| REVERT (CSS compilados tema) | 2 | `git checkout HEAD --` |
| REVERT (SCSS template picker) | 1 + 1 borrado | `git checkout HEAD --` + `rm` |
| FIX (main.scss) | 1 | Restaurar import `template-picker` |
| FIX (PageAttachmentsHooks.php) | 1 | Anadir ambos PWA meta tags |
| FIX (jaraba_job_board.routing.yml) | 1 | Restaurar `_csrf_token: 'TRUE'` |
| FIX (job-posting-detail.html.twig) | 1 | `|raw` a `|safe_html` en 4 campos |
| FIX (jaraba_candidate.module) | 1 | Documentar reuso intencional CV templates |
| FIX (ecosistema_jaraba_theme.theme) | 1 | URLs y roles corregidos |
| FIX (ecosistema_jaraba_core.module) | 1 | XSS escape en email + i18n |
| FIX (OnboardingController.php) | 1 | i18n en mensaje de registro |
| FIX (TemplatePickerController.php) | 1 | Cast `(string)` en TranslatableMarkup |
| FIX (contextual-copilot.js) | 1 | CSRF token + `?_format=json` |
| FIX (copilot_v2.routing.yml) | 1 | `_csrf_request_header_token` |
| KEEP (mejoras validas de Gemini) | ~15 | Sin cambios |
| SCSS Recompilacion | 1 | `npm run build:css` |
| **Total** | **~40** | |

---

## Archivos Modificados

| Archivo | Cambio Principal |
|---------|-----------------|
| `jaraba_page_builder/templates/page-builder-template-picker.html.twig` | Revertido (CSS classes rotas) |
| `jaraba_page_builder/config/install/*.yml` (10 AgroConecta) | Revertidos (schema flat restaurado) |
| `jaraba_page_builder/js/grapesjs-jaraba-thumbnails.js` | Revertido |
| `jaraba_page_builder/css/*.css` (2) | Revertidos |
| `ecosistema_jaraba_theme/css/*.css` (2) | Revertidos + recompilados |
| `ecosistema_jaraba_theme/scss/main.scss` | Import restaurado |
| `ecosistema_jaraba_core/src/Hook/PageAttachmentsHooks.php` | PWA meta tags duales |
| `jaraba_job_board/jaraba_job_board.routing.yml` | CSRF restaurado |
| `jaraba_job_board/templates/job-posting-detail.html.twig` | XSS fix (`\|safe_html`) |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | URLs + roles fix |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.module` | XSS email escape |
| `ecosistema_jaraba_core/src/Controller/OnboardingController.php` | i18n fix |
| `jaraba_page_builder/src/Controller/TemplatePickerController.php` | TranslatableMarkup cast |
| `ecosistema_jaraba_core/js/contextual-copilot.js` | CSRF token + format fix |
| `jaraba_copilot_v2/jaraba_copilot_v2.routing.yml` | `_csrf_request_header_token` |

## Resultado

| Metrica | Antes | Despues |
|---------|-------|---------|
| Template Picker funcional | Roto (CSS/JS mismatch) | Funcional |
| Copilot IA (autenticado) | Error 403 (CSRF missing) | HTTP 200 OK |
| XSS en ofertas empleo | 4 campos con `\|raw` | 4 campos con `\|safe_html` |
| PWA iOS Safari | Meta tag eliminado | Ambos meta tags presentes |
| Role check dashboard | Todos los autenticados | Solo candidate/jobseeker |
| URLs hardcoded en theme | `"/user/$uid/edit"` | `Url::fromRoute()` |
| Email XSS | $displayName sin escapar | `Html::escape()` aplicado |
| AgroConecta templates | Schema roto (nested) | Schema original restaurado |
