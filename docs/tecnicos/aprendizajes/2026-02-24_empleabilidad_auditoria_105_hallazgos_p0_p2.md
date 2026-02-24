# Aprendizaje #113: Empleabilidad — Auditoria 105 Hallazgos y Correccion P0/P1/P2

**Fecha:** 2026-02-24
**Contexto:** Revision exhaustiva del vertical Empleabilidad desde 15 perspectivas senior (negocio, carreras profesionales, finanzas, marketing, arquitectura SaaS, ingenieria software, UX, Drupal, web, theming, GrapesJS, SEO/GEO, IA) produjo 105 hallazgos. Se corrigieron los 7 P0, 1 P1 y 2 P2 en este sprint.
**Impacto:** Vertical Empleabilidad — Seguridad, integridad de negocio, compliance legal, consistencia de marca, i18n

---

## 1. Problema

Una auditoria multidimensional del vertical Empleabilidad (candidato, job board, diagnostico, copilot IA, emails, SCSS, JS frontend, controladores PHP, entidades, configuracion freemium) revelo 105 hallazgos agrupados en 9 categorias, con una puntuacion global de 7.0/10.

### Hallazgos P0 (Criticos) — 7

| # | Hallazgo | Categoria |
|---|----------|-----------|
| P0-1 | Fraude tier Starter: limites identicos al tier Free pese a upgrade_messages que prometen mejoras | Negocio |
| P0-2 | XSS via `innerHTML` sin sanitizar en 8 puntos de `agent-fab.js` | Seguridad |
| P0-3 | 6 endpoints POST/DELETE sin CSRF token (`X-CSRF-Token`) | Seguridad |
| P0-4 | Bypass de acceso en `EmployerController` — 3 metodos sin verificacion de ownership | Seguridad |
| P0-5 | API field injection en `CandidateApiController.updateProfile()` — aceptaba cualquier campo | Seguridad |
| P0-6 | 7 emails MJML sin direccion postal ni preheader (CAN-SPAM) | Legal |
| P0-7 | 3 colores primarios diferentes: `#1565C0` (tokens), `#2563eb` (emails), `#FF8C42` (SCSS fallbacks) | Marca |

### Hallazgos P1 (Altos) — 1

| # | Hallazgo | Categoria |
|---|----------|-----------|
| P1-1 | 31 strings de interfaz hardcoded sin `|t` / `{% trans %}` en 3 templates Twig | i18n |

### Hallazgos P2 (Medios) — 2

| # | Hallazgo | Categoria |
|---|----------|-----------|
| P2-1 | `|striptags()|raw` en `candidate-profile-view.html.twig:59` — viola TWIG-XSS-001 | Seguridad |
| P2-2 | `|striptags()|raw` en `commerce-product--geo.html.twig:104` — viola TWIG-XSS-001 | Seguridad |

---

## 2. Diagnostico

### P0-1 — Fraude tier Starter

**Causa raiz:** `plan_features.empleabilidad_starter.yml` definia limites identicos al tier Free:
- `cv_builder: 1` (Free = 1), `job_applications_per_day: 3` (Free = 3), `copilot_messages_per_day: 5` (Free = 5)

Mientras tanto, los `upgrade_message` en `freemium_vertical_limit.empleabilidad_free_*.yml` prometian al usuario:
- "hasta 15 candidaturas diarias" (Free = 3, Starter real = 3)
- "hasta 25 consultas diarias" (Free = 5, Starter real = 5)

**Efecto:** Un usuario que pagaba por Starter no recibia ninguna mejora sobre el tier gratuito. El upsell era fraudulento.

### P0-2 — XSS via innerHTML

**Causa raiz:** `agent-fab.js` asignaba respuestas del servidor directamente al DOM con `innerHTML` sin sanitizar. 8 puntos afectados:
- Respuestas de chat del copilot
- Datos de onboarding (greeting, phase_name)
- Indicador de fase de autoconocimiento
- Pasos del itinerario
- CTAs y motivaciones
- Mensajes proactivos

Un atacante con acceso al backend o via inyeccion en la respuesta API podia ejecutar scripts arbitrarios en el contexto del usuario.

### P0-3 — CSRF ausente

**Causa raiz:** 6 endpoints POST/DELETE consumidos via `fetch()` no incluian header `X-CSRF-Token`:
1. `POST /api/v1/copilot/employability/chat` (employability-copilot.js)
2. `POST /empleabilidad/diagnostico` (employability-diagnostic.js)
3. `POST /api/v1/self-discovery/copilot/context` (agent-fab.js)
4. `POST /api/v1/job-board/agent-rating` (agent-fab.js)
5. `POST /api/v1/copilot/employability/proactive` (agent-fab.js)
6. `POST /api/v1/candidate/skills` y `DELETE /api/v1/candidate/skills/{id}` (skills-manager.js)

**Regla violada:** CSRF-API-001

### P0-4 — Bypass de acceso EmployerController

**Causa raiz:** Tres metodos en `EmployerController.php` no verificaban que el empleador autenticado fuera el propietario del job posting:
- `jobApplications(JobPostingInterface)` — Cualquier empleador autenticado podia ver candidaturas de ofertas ajenas
- `applicationDetail(JobApplicationInterface)` — Acceso sin verificar al detalle de candidaturas
- `updateStatus(JobApplicationInterface)` — Cambio de estado de candidaturas ajenas

### P0-5 — API field injection

**Causa raiz:** `CandidateApiController::updateProfile()` iteraba `$data` del request JSON sin filtrar contra una whitelist, ejecutando `$profile->set($field, $value)` para cualquier campo que existiera en la entidad. Un atacante podia modificar campos protegidos como `status`, `role`, `created`, `uid`.

### P0-6 — CAN-SPAM

**Causa raiz:** Las 7 plantillas MJML de emails de empleabilidad carecian de:
- `<mj-preview>` (preheader) — requerido para accesibilidad y UX
- Direccion postal fisica — requerido por CAN-SPAM Act y RGPD

### P0-7 — Color primario inconsistente

**Causa raiz:** Tres fuentes de verdad diferentes para el color primario:
- Design token config: `#1565C0` (Professional Blue) — SSOT correcto
- Emails MJML: `#2563eb` (Tailwind Blue 600) — incorrecto
- SCSS fallbacks: `#FF8C42` (Naranja Impulso) — completamente incorrecto, es el color de otra marca

### P1-1 — Textos sin traducir

**Causa raiz:** Tres templates contenian strings hardcoded en espanol sin `|t`:
- `copilot-insights-dashboard.html.twig` — 15 labels de KPIs y secciones
- `email-base.html.twig` — 8 strings (saludo, links, footer)
- `commerce-product--geo.html.twig` — 8 strings (disponibilidad, breadcrumbs, botones)

### P2-1 y P2-2 — |raw en templates

**Causa raiz:** Dos templates usaban `|striptags('<tags>')|raw` para renderizar HTML de usuario. Segun TWIG-XSS-001, el patron correcto es `|safe_html` que usa el filtro XSS de Drupal con whitelist de tags segura.

---

## 3. Solucion Implementada

### Fase 1: P0 — Criticos (7 correcciones)

**3.1 Starter tier — Limites corregidos (P0-1)**

Fichero: `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.plan_features.empleabilidad_starter.yml`

| Limite | Antes (= Free) | Despues |
|--------|----------------|---------|
| cv_builder | 1 | 5 |
| job_applications_per_day | 3 | 15 |
| copilot_messages_per_day | 5 | 25 |
| job_alerts | 1 | 5 |
| offers_visible_per_day | 10 | 25 |
| diagnostics | 1 | 3 |

Anadidas features diferenciadas: `cv_builder_advanced`, `job_alerts_email`.

**3.2 XSS innerHTML sanitizado (P0-2)**

Fichero: `jaraba_job_board/js/agent-fab.js` — 8 puntos corregidos

Patron aplicado en cada punto:
```javascript
// ANTES (vulnerable)
msg.innerHTML = response.message;

// DESPUES (seguro)
msg.innerHTML = '<div class="...">' + Drupal.checkPlain(response.message)
  .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
  .replace(/\n/g, '<br>') + '</div>';
```

Todas las variables de datos del servidor pasan por `Drupal.checkPlain()` antes de insercion en el DOM. Valores numericos pasan por `parseInt()`.

**3.3 CSRF tokens anadidos (P0-3)**

4 ficheros JS modificados con el mismo patron:

```javascript
// Helper cacheado (una sola peticion por sesion)
var _csrfTokenPromise = null;
function getCsrfToken() {
  if (!_csrfTokenPromise) {
    _csrfTokenPromise = fetch('/session/token')
      .then(function (r) { return r.text(); });
  }
  return _csrfTokenPromise;
}

// Uso en cada fetch POST/DELETE
getCsrfToken().then(function (csrfToken) {
  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(payload)
  });
});
```

Ficheros: `employability-copilot.js`, `employability-diagnostic.js`, `agent-fab.js`, `skills-manager.js`.

**3.4 Ownership verification (P0-4)**

Fichero: `jaraba_job_board/src/Controller/EmployerController.php` — 3 metodos corregidos

```php
// Patron aplicado en jobApplications(), applicationDetail(), updateStatus()
$user_id = (int) $this->currentUser()->id();
if ((int) $job_posting->get('employer_id')->value !== $user_id) {
    throw new AccessDeniedHttpException();
}
```

**3.5 API field whitelist (P0-5)**

Fichero: `jaraba_candidate/src/Controller/CandidateApiController.php`

```php
private const ALLOWED_PROFILE_FIELDS = [
    'first_name', 'last_name', 'headline', 'summary',
    'city', 'country', 'phone', 'experience_years',
    'desired_salary_min', 'desired_salary_max',
    'availability', 'job_search_status',
    'remote_preference', 'relocation_willing',
    'linkedin_url', 'github_url', 'portfolio_url', 'website_url',
];

// updateProfile() ahora filtra:
foreach ($data as $field => $value) {
    if (in_array($field, self::ALLOWED_PROFILE_FIELDS, TRUE)
        && $profile->hasField($field)) {
        $profile->set($field, $value);
    }
}
```

**3.6 CAN-SPAM compliance (P0-6)**

7 plantillas MJML editadas (21 cambios totales = 3 por archivo):

1. Preheader: `<mj-preview>` con texto contextual por tipo de email
2. Direccion postal: `Jaraba Impact Platform — Poligono Industrial Juncaril, C/ Abedul s/n, 18220 Albolote, Granada, Espana`
3. Color primario unificado (ver P0-7)

Ficheros:
- `seq_onboarding_welcome.mjml`
- `job_match.mjml`
- `application_sent.mjml`
- `seq_interview_prep.mjml`
- `seq_engagement_reactivation.mjml`
- `candidate_shortlisted.mjml`
- `seq_upsell_starter.mjml`

**3.7 Color primario unificado (P0-7)**

| Canal | Antes | Despues | Ficheros |
|-------|-------|---------|----------|
| SCSS fallbacks | `#FF8C42` | `#1565C0` | `_employability-pages.scss` (7 puntos) |
| Email MJML | `#2563eb` | `#1565C0` | 7 plantillas MJML |
| JS diagnostic | `#4F46E5` | `#1565C0` | `employability-diagnostic.js:249` |
| JS SCSS box-shadow | `rgba(255,140,66,0.1)` | `rgba(21,101,192,0.1)` | `_employability-pages.scss:477` |

SSOT: `config/sync/ecosistema_jaraba_core.design_token_config.vertical_empleabilidad.yml` → primary `#1565C0`.

### Fase 2: P1 — Textos sin traducir (31 strings)

**3.8 copilot-insights-dashboard.html.twig — 15 strings**

```twig
{# ANTES #}
<span class="kpi-label">Costo Total</span>

{# DESPUES #}
<span class="kpi-label">{{ 'Costo Total'|t }}</span>
```

Patron `|t` aplicado a: Analisis de Copilotos IA, subtitulo temporal, Conversaciones, Tasa Resolucion, Rating Promedio, Costo Total, Intents Mas Frecuentes, No hay datos suficientes, Preguntas Mas Comunes, Sin datos todavia, Consultas Sin Resolver, Posibles gaps, Todas las consultas resueltas, Uso de Tokens, Input, Output, Modelo, Costo estimado.

**3.9 email-base.html.twig — 8 strings**

Patron `|t` aplicado a: Hola, Match, Ver detalles (default), Consejo, Mis Candidaturas, Mis Alertas, Buscar Empleos, Empleabilidad Digital, Gestionar notificaciones.

**3.10 commerce-product--geo.html.twig — 8 strings**

Patron `|t` aplicado a: Consultar precio (default), Disponible, Agotado, Descripcion del Producto, Especificaciones, Anadir al Carrito, Inicio, Productos.

### Fase 3: P2 — |raw → |safe_html

**3.11 candidate-profile-view.html.twig:59**

```twig
{# ANTES — vulnerable #}
{{ profile.summary|striptags('<p><br><strong><em><ul><ol><li><a>')|raw }}

{# DESPUES — seguro #}
{{ profile.summary|safe_html }}
```

**3.12 commerce-product--geo.html.twig:104**

```twig
{# ANTES — vulnerable #}
{{ product.body.value|striptags('<p>...<tbody>')|raw }}

{# DESPUES — seguro #}
{{ product.body.value|safe_html }}
```

---

## 4. Reglas Derivadas

### FREEMIUM-TIER-001: Coherencia limites Free → Starter

Los limites del tier Starter DEBEN ser estrictamente superiores a los del tier Free para cada metrica. Al crear o modificar `plan_features.{vertical}_{tier}.yml`, verificar que cada limite supere el valor correspondiente en `freemium_vertical_limit.{vertical}_free_{metric}.yml`. Los `upgrade_message` en los limites Free DEBEN reflejar los valores reales del Starter.

**Checklist de verificacion:**
```
Para cada upgrade_message en freemium_vertical_limit.{vertical}_free_*.yml:
  1. Extraer el numero prometido (ej: "hasta 15 candidaturas")
  2. Comparar con plan_features.{vertical}_starter.yml
  3. DEBEN coincidir: plan_features.limit >= numero_prometido
```

### INNERHTML-XSS-001: Nunca innerHTML con datos del servidor sin sanitizar

Todo dato recibido de una respuesta API que se inserte en el DOM via `innerHTML`, `outerHTML`, o template literals DEBE pasar por `Drupal.checkPlain()` antes de la insercion. Los valores numericos DEBEN pasar por `parseInt()` o `parseFloat()`. Solo se permite HTML generado client-side (tags `<strong>`, `<br>`, `<em>`) post-sanitizacion.

**Patron seguro:**
```javascript
var safeText = Drupal.checkPlain(serverData)
  .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
  .replace(/\n/g, '<br>');
container.innerHTML = '<div>' + safeText + '</div>';
```

### CSRF-JS-CACHE-001: Cache del token CSRF

El token CSRF obtenido de `/session/token` DEBE cachearse en una variable de modulo para evitar multiples peticiones. Usar el patron de promise cacheada:

```javascript
var _csrfTokenPromise = null;
function getCsrfToken() {
  if (!_csrfTokenPromise) {
    _csrfTokenPromise = fetch('/session/token')
      .then(function (r) { return r.text(); });
  }
  return _csrfTokenPromise;
}
```

### API-WHITELIST-001: Whitelist de campos en endpoints de actualizacion

Todo endpoint que acepte campos dinamicos del request para actualizar una entidad DEBE definir una constante `ALLOWED_FIELDS` y filtrar el input antes de ejecutar `$entity->set()`. Nunca iterar directamente sobre `$data` del request.

---

## 5. Verificacion de Compliance con Directrices

Se verifico que las correcciones cumplen con las directrices del proyecto:

| Directriz | Estado | Evidencia |
|-----------|--------|-----------|
| CSRF-API-001 | CUMPLE | Token cacheado + header en 6 endpoints |
| TWIG-XSS-001 | CUMPLE | `\|safe_html` reemplaza `\|raw` en 2 templates |
| Dart Sass moderno | CUMPLE | Sin `@import`, sin `darken()`/`lighten()` |
| SCSS Injectable Variables | CUMPLE | Solo `var(--ej-*)` con fallbacks, sin SCSS vars propias en modulos |
| Design Token SSOT | CUMPLE | Fallbacks `#1565C0` alineados con config YAML |
| `hook_preprocess_html()` | CUMPLE | Body classes via hook, no `attributes.addClass()` |
| Zero Region Policy | CUMPLE | `page--empleabilidad.html.twig` usa `clean_content` |
| Textos traducibles | CUMPLE | 31 strings migradas a `\|t` |
| Modales para CRUD | CUMPLE | `data-dialog-type="modal"` en acciones |
| Tenant sin admin theme | CUMPLE | `restrict access: true`, rutas sin `_admin_route` |

---

## 6. Ficheros Modificados (22 ficheros)

| Fichero | Accion | Fase |
|---------|--------|------|
| `ecosistema_jaraba_core/config/install/...plan_features.empleabilidad_starter.yml` | Limites corregidos | P0-1 |
| `jaraba_job_board/js/agent-fab.js` | XSS sanitizado (8 puntos) + CSRF (3 endpoints) | P0-2, P0-3 |
| `jaraba_candidate/js/employability-copilot.js` | CSRF token anadido | P0-3 |
| `jaraba_diagnostic/js/employability-diagnostic.js` | CSRF token + color `#4F46E5`→`#1565C0` | P0-3, P0-7 |
| `jaraba_candidate/js/skills-manager.js` | CSRF token anadido (POST + DELETE) | P0-3 |
| `jaraba_job_board/src/Controller/EmployerController.php` | Ownership verification (3 metodos) | P0-4 |
| `jaraba_candidate/src/Controller/CandidateApiController.php` | Whitelist 17 campos | P0-5 |
| `jaraba_email/templates/mjml/empleabilidad/seq_onboarding_welcome.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `jaraba_email/templates/mjml/empleabilidad/job_match.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `jaraba_email/templates/mjml/empleabilidad/application_sent.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `jaraba_email/templates/mjml/empleabilidad/seq_interview_prep.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `jaraba_email/templates/mjml/empleabilidad/seq_engagement_reactivation.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `jaraba_email/templates/mjml/empleabilidad/candidate_shortlisted.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `jaraba_email/templates/mjml/empleabilidad/seq_upsell_starter.mjml` | Preheader + direccion + color | P0-6, P0-7 |
| `ecosistema_jaraba_theme/scss/_employability-pages.scss` | 7x `#FF8C42`→`#1565C0` + rgba fix | P0-7 |
| `jaraba_candidate/templates/copilot-insights-dashboard.html.twig` | 15 strings → `\|t` | P1-1 |
| `jaraba_job_board/templates/email/email-base.html.twig` | 8 strings → `\|t` | P1-1 |
| `ecosistema_jaraba_theme/templates/commerce/commerce-product--geo.html.twig` | 8 strings → `\|t` + `\|raw`→`\|safe_html` | P1-1, P2-2 |
| `jaraba_candidate/templates/candidate-profile-view.html.twig` | `\|raw`→`\|safe_html` | P2-1 |

---

## 7. Hallazgos Pendientes (para sprints posteriores)

Los 95 hallazgos restantes (P3/P4) se documentaron en la auditoria original. Los mas relevantes:

| # | Tipo | Descripcion |
|---|------|-------------|
| P3-1 | Performance | `EmployerController::dashboard()` ejecuta N+1 queries para contar aplicaciones |
| P3-2 | Performance | `EmployerController::jobs()` carga todas las ofertas 2 veces (query filtrada + `loadByProperties`) |
| P3-3 | SEO | Falta JSON-LD schema en paginas de detalle de empleo |
| P3-4 | UX | Endpoints stub (`getExperiences`, `addExperience`) retornan datos vacios sin implementacion |
| P3-5 | Testing | 0 tests unitarios/kernel para controladores y servicios del vertical |
| P3-6 | Emojis | 3 templates con emojis inline sin `jaraba_icon()` |
| P3-7 | JS i18n | `push-sw.js` con strings hardcoded (limitacion Service Worker) |
| P3-8 | JS i18n | `skills-manager.js:72,119` con error messages sin `Drupal.t()` |

---

## 8. Leccion Clave

**La coherencia de limites entre tiers de un modelo freemium es una invariante de negocio critica que debe verificarse automaticamente.** Cuando el sistema de upsell promete mejoras (via `upgrade_message`) que no se materializan en los `plan_features`, el usuario que paga recibe exactamente lo mismo que el usuario gratuito, destruyendo la propuesta de valor y exponiendo a la empresa a reclamaciones.

**Regla FREEMIUM-TIER-001** establece que toda modificacion de limites Free o Starter debe validar la cadena completa: `freemium_vertical_limit.upgrade_message` ↔ `plan_features.limits` ↔ `plan_features.features`. Idealmente, un test automatizado (Kernel test) deberia verificar esta invariante.

**Adicionalmente, las auditorias multidimensionales (15 roles) detectan patrones de riesgo que escapan a revisiones especializadas.** El hallazgo de fraude de tier no habria sido encontrado por un ingeniero de seguridad ni por un ingeniero frontend — requirio la perspectiva de un analista de negocio/finanzas que cruzo los datos de configuracion del freemium con los mensajes de marketing.
