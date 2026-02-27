# Plan de Implementacion: Demo Vertical 100% Clase Mundial

**Fecha de creacion:** 2026-02-27 14:30
**Ultima actualizacion:** 2026-02-27 14:30
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Documento fuente:** `docs/analisis/2026-02-27_Auditoria_Demo_Vertical_Clase_Mundial_v2.md`
**Hallazgos a resolver:** 67 (4 criticos, 15 altos, 27 medios, 21 bajos)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Objetivos y Metricas de Exito](#2-objetivos)
3. [Sprint 5: Seguridad + A11Y + Wire Services](#3-sprint-5)
4. [Sprint 6: i18n + Arquitectura + GDPR](#4-sprint-6)
5. [Sprint 7: PLG Excellence](#5-sprint-7)
6. [Dependencias y Riesgos](#6-dependencias)
7. [Criterios de Aceptacion Globales](#7-criterios)

---

## 1. Resumen Ejecutivo

Este plan continua la remediacion de la vertical demo desde el 60% alcanzado tras 4 sprints previos hasta el objetivo de 95%+ clase mundial. Se organiza en 3 sprints adicionales con 39 items de trabajo.

| Sprint | Foco | Items | Score objetivo |
|--------|------|-------|---------------|
| S5 | Seguridad + A11Y + Wire Services | 16 | 60% → 78% |
| S6 | i18n + Arquitectura + GDPR | 13 | 78% → 88% |
| S7 | PLG Excellence | 10 | 88% → 95%+ |

---

## 2. Objetivos y Metricas de Exito

### 2.1 Metricas tecnicas

| Metrica | Valor actual | Objetivo |
|---------|-------------|----------|
| Vulnerabilidades XSS | 1 (`\|raw`) | 0 |
| ARIA violations (axe) | 10+ | 0 criticos, ≤ 3 menores |
| Strings sin i18n | ~230 | 0 |
| Dead code services | 2 | 0 |
| Template duplication | 80% | < 20% |
| SCSS duplication | 2 archivos | 1 canonical |
| Feature gate enforcement | 0% | 100% |
| Nudge delivery | 0% | 100% |
| Analytics structured | 0% | 100% |
| A/B test points | 0 | ≥ 3 |

### 2.2 Metricas PLG

| Metrica | Valor actual | Objetivo |
|---------|-------------|----------|
| TTFV medible | No | Si (persistido) |
| Conversion funnel tracked | No | Si (6 stages) |
| Session countdown visible | No | Si |
| Social proof rendered | No | Si |
| GDPR compliant | No | Si |

---

## 3. Sprint 5: Seguridad + A11Y + Wire Services

**Prioridad:** P0 (inmediato)
**Hallazgos resueltos:** HAL-DEMO-BE-01, BE-02, BE-09, BE-10, BE-11, BE-13, FE-01, FE-02, FE-03, FE-04, FE-05, FE-09, FE-11, FE-13, FE-14, CFG-01

### S5-01: Wire DemoFeatureGateService en DemoController (CRITICO)

**Hallazgo:** HAL-DEMO-BE-01
**Archivos:** `DemoController.php`, `ecosistema_jaraba_core.services.yml`

**Implementacion detallada:**

1. Anadir `DemoFeatureGateService` como dependencia inyectada en `DemoController`:
   ```php
   public function __construct(
       // ... existing deps ...
       protected DemoFeatureGateService $featureGate,
   ) {}
   ```

2. En `create()`, resolver `$container->get('ecosistema_jaraba_core.demo_feature_gate')`.

3. En `trackAction()`, antes de ejecutar la accion:
   ```php
   // Mapear action → feature.
   $featureMap = [
       'generate_story' => 'story_generations_per_session',
       'ai_chat' => 'ai_messages_per_session',
       'view_products' => 'products_viewed_per_session',
   ];
   $feature = $featureMap[$actionId] ?? NULL;
   if ($feature) {
       $check = $this->featureGate->check($sessionId, $feature);
       if (!$check['allowed']) {
           return new JsonResponse([
               'success' => FALSE,
               'error' => $this->t('Has alcanzado el limite de @feature para esta demo. Crea una cuenta para acceso completo.', ['@feature' => $feature]),
               'limit' => $check['limit'],
               'remaining' => 0,
           ], 429);
       }
       $this->featureGate->recordUsage($sessionId, $feature);
   }
   ```

4. En `demoAiStorytelling()`, verificar `story_generations_per_session`.
5. Anadir `#cache => ['max-age' => 0]` al response de storytelling.

**Criterio de aceptacion:** Feature gates enforced. Requests over-limit retornan HTTP 429 con mensaje i18n.

---

### S5-02: Wire DemoJourneyProgressionService en DemoController (CRITICO)

**Hallazgo:** HAL-DEMO-BE-02
**Archivos:** `DemoController.php`, `ecosistema_jaraba_core.services.yml`

**Implementacion detallada:**

1. Inyectar `DemoJourneyProgressionService` en `DemoController`.

2. En `demoDashboard()`, evaluar nudges y pasar a template:
   ```php
   $nudges = $this->journeyProgression->evaluateNudges($sessionId);
   $build['#nudges'] = $nudges;
   ```

3. En `getSessionData()` (API), incluir nudges en response:
   ```php
   $result['nudges'] = $this->journeyProgression->evaluateNudges($sessionId);
   ```

4. Crear endpoint dismiss nudge:
   - Ruta: `POST /api/v1/demo/nudge/dismiss`
   - CSRF required
   - Controller method: `dismissNudge(Request $request)`

5. Actualizar `demo-dashboard.html.twig` y `demo-dashboard-view.html.twig`:
   - Renderizar nudge FAB:
     ```twig
     {% if nudges|length > 0 %}
       {% set nudge = nudges|first %}
       <div class="demo-nudge-fab demo-nudge-fab--{{ nudge.channel }}"
            data-nudge-id="{{ nudge.id }}"
            role="complementary"
            aria-label="{{ 'Sugerencia de conversion'|t }}">
         <p class="demo-nudge-fab__message">{{ nudge.message }}</p>
         <a href="{{ nudge.cta_url }}" class="demo-nudge-fab__cta">{{ nudge.cta_label }}</a>
         <button class="demo-nudge-fab__dismiss" aria-label="{{ 'Cerrar'|t }}"
                 data-demo-dismiss-nudge>&times;</button>
       </div>
     {% endif %}
     ```

6. Anadir SCSS para `.demo-nudge-fab` con variantes `--fab_expand` y `--fab_dot`.
7. Anadir JS behavior para dismiss con CSRF token.

**Criterio de aceptacion:** Nudges renderizados en dashboard. Dismiss persiste. Nudges evaluados segun reglas de prioridad.

---

### S5-03: Crear 3 Twig templates de Page Builder (CRITICO)

**Hallazgo:** HAL-DEMO-CFG-01
**Directorio:** `web/modules/custom/jaraba_page_builder/templates/blocks/demo/`

**Implementacion detallada:**

1. Crear directorio `templates/blocks/demo/`.

2. **`demo-showcase-landing.html.twig`:**
   - Renderizar hero con `hero_title`, `hero_subtitle`
   - Grid de perfiles con loop `{% for profile in profiles %}`
   - Seccion metricas condicional `{% if metrics_enabled %}`
   - Playground IA condicional `{% if ai_playground_enabled %}`
   - CTA final con `cta_text` y `cta_url`
   - Usar design tokens `var(--ej-*, fallback)`
   - BEM: `.pb-demo-showcase`, `__hero`, `__profiles`, `__metrics`, `__ai`, `__cta`

3. **`demo-vertical-comparison.html.twig`:**
   - Renderizar tabla responsive con `<table>` semantico
   - Headers de verticales con colores configurados
   - Rows de features con `✓` / `—` segun availability
   - `overflow-x: auto` en wrapper para mobile
   - BEM: `.pb-demo-comparison`, `__table`, `__header`, `__row`, `__check`

4. **`demo-ai-capabilities.html.twig`:**
   - Grid de capabilities con iconos
   - Playground interactivo condicional con prompts sugeridos
   - Fondo oscuro/gradiente/claro segun `background`
   - BEM: `.pb-demo-ai`, `__grid`, `__capability`, `__playground`, `__prompt`

5. Registrar templates en `hook_theme()` del modulo page_builder o via Twig namespace `@jaraba_page_builder`.

**Criterio de aceptacion:** Page Builder pages using these templates render correctly. Templates follow BEM, use design tokens, are responsive.

---

### S5-04: Eliminar XSS `|raw` en storytelling (CRITICO)

**Hallazgo:** HAL-DEMO-FE-01
**Archivo:** `demo-ai-storytelling.html.twig` linea 42

**Implementacion detallada:**

1. En `DemoController::demoAiStorytelling()`, sanitizar la historia antes de pasar a template:
   ```php
   use Drupal\Component\Utility\Xss;
   $allowedTags = ['p', 'strong', 'em', 'ul', 'ol', 'li', 'h2', 'h3', 'br'];
   $sanitizedStory = Xss::filter($story, $allowedTags);
   $build['#generated_story'] = ['#markup' => $sanitizedStory];
   ```

2. En template, reemplazar `{{ generated_story|raw }}` por:
   ```twig
   {{ generated_story }}
   ```
   Si se pasa como render array con `#markup`, Drupal lo sanitiza automaticamente.

**Criterio de aceptacion:** `|raw` eliminado. Story renderiza con tags permitidos. `<script>` tags son stripped.

---

### S5-05: ARIA completo en modal de conversion

**Hallazgo:** HAL-DEMO-FE-02
**Archivos:** `demo-dashboard.html.twig`, `demo-dashboard.js`

**Implementacion detallada:**

1. En template, actualizar modal:
   ```html
   <div class="demo-convert-modal" role="dialog" aria-modal="true"
        aria-labelledby="demo-modal-title" aria-describedby="demo-modal-desc"
        hidden>
     <div class="demo-convert-modal__overlay" data-demo-convert-close></div>
     <div class="demo-convert-modal__content">
       <h2 id="demo-modal-title">{{ 'Crear cuenta'|t }}</h2>
       <p id="demo-modal-desc">{{ 'Registrate para guardar tu progreso'|t }}</p>
       <label for="demo-convert-email" class="visually-hidden">{{ 'Correo electronico'|t }}</label>
       <input id="demo-convert-email" type="email" required ...>
       <button type="submit" ...>{{ 'Registrarme'|t }}</button>
       <button class="demo-convert-modal__close" aria-label="{{ 'Cerrar'|t }}" data-demo-convert-close>&times;</button>
     </div>
   </div>
   ```

2. En JS, implementar focus trap:
   ```javascript
   // Open: save trigger, set hidden=false, move focus to first focusable
   // Close: restore focus to trigger, set hidden=true
   // Escape key: close
   // Tab: trap within modal focusable elements
   ```

**Criterio de aceptacion:** axe-core reports 0 violations on modal. Focus trapped. Escape closes. Focus restored.

---

### S5-06: aria-live en chat + labels en inputs

**Hallazgos:** HAL-DEMO-FE-03, HAL-DEMO-FE-04
**Archivos:** `demo-ai-playground.html.twig`, `demo-dashboard.html.twig`

**Implementacion:**

1. Chat output: `<div data-chat-output aria-live="polite" role="log">`.
2. Chat input: Add `<label for="demo-chat-input" class="visually-hidden">{{ 'Escribe tu mensaje'|t }}</label>`.
3. Email input: Add `<label for="demo-convert-email" class="visually-hidden">{{ 'Correo electronico'|t }}</label>`.

---

### S5-07: Canvas chart accesible

**Hallazgo:** HAL-DEMO-FE-05
**Archivo:** `demo-dashboard.html.twig` linea 137

**Implementacion:**

```html
<canvas id="salesChart" role="img" aria-label="{{ 'Grafico de tendencia de ventas de los ultimos 7 dias'|t }}">
  <p>{{ 'Datos de ventas no disponibles visualmente. Consulta las metricas numericas arriba.'|t }}</p>
</canvas>
```

---

### S5-08: prefers-reduced-motion

**Hallazgo:** HAL-DEMO-FE-09
**Archivo:** `_demo.scss`

**Implementacion:**

Anadir al final de `_demo.scss`:
```scss
@media (prefers-reduced-motion: reduce) {
  .demo-profile-card,
  .demo-magic-action-card,
  .demo-metric-card,
  .demo-convert-cta,
  .demo-nudge-fab {
    transition: none !important;
    animation: none !important;
  }
}
```

---

### S5-09: Loading/typing indicators

**Hallazgo:** HAL-DEMO-FE-11
**Archivos:** `demo-ai-playground.js`, `demo-dashboard.js`, `_demo.scss`

**Implementacion:**

1. En `demo-ai-playground.js`, despues de enviar mensaje:
   ```javascript
   // Append typing indicator
   const typing = document.createElement('div');
   typing.className = 'demo-playground__typing';
   typing.setAttribute('aria-label', Drupal.t('La IA esta pensando'));
   typing.innerHTML = '<span></span><span></span><span></span>';
   chatOutput.appendChild(typing);
   ```

2. En `demo-dashboard.js`, en `convertDemo()`:
   ```javascript
   submitBtn.disabled = true;
   submitBtn.textContent = Drupal.t('Registrando...');
   ```

3. SCSS para typing indicator:
   ```scss
   .demo-playground__typing {
     display: flex;
     gap: 4px;
     padding: 1rem;
     span {
       width: 8px; height: 8px;
       border-radius: 50%;
       background: var(--ej-color-innovation, #00A9A5);
       animation: demo-typing-bounce 1.2s infinite;
       &:nth-child(2) { animation-delay: 0.2s; }
       &:nth-child(3) { animation-delay: 0.4s; }
     }
   }
   @keyframes demo-typing-bounce {
     0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
     40% { transform: scale(1); opacity: 1; }
   }
   ```

---

### S5-10: Error feedback en conversion

**Hallazgo:** HAL-DEMO-FE-13
**Archivo:** `demo-dashboard.js`

**Implementacion:**

En el catch de `convertDemo()`:
```javascript
catch (error) {
  const errorEl = modal.querySelector('[data-convert-error]');
  if (errorEl) {
    errorEl.textContent = Drupal.t('Error al registrar. Intentalo de nuevo.');
    errorEl.removeAttribute('hidden');
  }
  submitBtn.disabled = false;
  submitBtn.textContent = originalText;
}
```

Anadir en template: `<div data-convert-error class="demo-convert-modal__error" role="alert" hidden></div>`.

---

### S5-11: Session ID validation en rutas faltantes

**Hallazgo:** HAL-DEMO-BE-09
**Archivo:** `DemoController.php`

**Implementacion:**

Anadir al inicio de `getSessionData()`, `demoDashboard()`, `demoAiStorytelling()`:
```php
if (!$this->isValidSessionId($sessionId)) {
    return new JsonResponse(['error' => 'Invalid session'], 400);
    // o para page routes: redirect to demo landing
}
```

---

### S5-12: Cache metadata en demoDashboard

**Hallazgo:** HAL-DEMO-BE-10

Anadir:
```php
$build['#cache'] = ['max-age' => 0];
```

---

### S5-13: HTTP status codes en convertToReal

**Hallazgo:** HAL-DEMO-BE-11

```php
$statusCode = $result['success'] ? 200 : 422;
return new JsonResponse($result, $statusCode);
```

---

### S5-14: completeTour guard anonymous

**Hallazgo:** HAL-DEMO-BE-13

```php
public function completeTour(string $tourId): void {
    if (!isset(self::TOURS[$tourId])) { return; }
    $userId = (int) \Drupal::currentUser()->id();
    if ($userId === 0) { return; } // Anonymous: skip DB write
    // ... existing logic
}
```

---

### S5-15: Keydown handlers en scenario cards

**Hallazgo:** HAL-DEMO-FE-14

```javascript
card.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
    }
});
```

---

### S5-16: Close button aria-label

**Hallazgo:** HAL-DEMO-FE-15

Cambiar `<button>&times;</button>` por `<button aria-label="{{ 'Cerrar'|t }}">&times;</button>`.

---

## 4. Sprint 6: i18n + Arquitectura + GDPR

**Prioridad:** P1 (consolidacion)
**Hallazgos resueltos:** HAL-DEMO-BE-03, BE-05, BE-06, BE-07, BE-08, BE-12, FE-06, FE-07, FE-08, FE-10, CFG-02, CFG-04, CFG-07, PLG-GDPR

### S6-01: StringTranslationTrait en DemoInteractiveService

**Hallazgo:** HAL-DEMO-BE-05
**Archivo:** `DemoInteractiveService.php`

**Implementacion:**

1. Anadir `use StringTranslationTrait;`.
2. Convertir `DEMO_PROFILES` de constante a metodo:
   ```php
   protected function getDemoProfiles(): array {
       return [
           'producer' => [
               'name' => (string) $this->t('Productor de Aceite'),
               'description' => (string) $this->t('Experimenta como seria gestionar tu cooperativa...'),
               'vertical' => 'agroconecta',
               'icon' => 'agriculture',
           ],
           // ... 10 mas
       ];
   }
   ```
3. Igual para `SYNTHETIC_PRODUCTS`, `demoNames()`, `getMagicMomentActions()`.
4. Reemplazar `self::DEMO_PROFILES` por `$this->getDemoProfiles()` en todos los call sites.

---

### S6-02: StringTranslationTrait en GuidedTourService

**Hallazgo:** HAL-DEMO-BE-06

Igual que S6-01: convertir constante `TOURS` a metodo `getTours()` con strings traducidos.

---

### S6-03: Deprecar SandboxTenantService

**Hallazgos:** HAL-DEMO-BE-03, BE-04, CFG-07

1. Marcar clase con `@deprecated Use DemoInteractiveService instead.`
2. Anadir `trigger_error('SandboxTenantService is deprecated', E_USER_DEPRECATED)` en constructor.
3. Actualizar `SandboxController` para redirigir a rutas demo.
4. No eliminar aun — solo deprecar para backward compat.

---

### S6-04: Mobile-first SCSS refactor

**Hallazgo:** HAL-DEMO-FE-06

1. Reescribir `_demo.scss` con breakpoints mobile-first:
   ```scss
   // Mobile base (default)
   .demo-profile-grid { grid-template-columns: 1fr; }

   @media (min-width: 768px) {
     .demo-profile-grid { grid-template-columns: repeat(2, 1fr); }
   }
   @media (min-width: 1024px) {
     .demo-profile-grid { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
   }
   ```
2. Anadir breakpoints: 768px (tablet), 1024px (tablet landscape), 1440px (desktop large).

---

### S6-05: DRY templates con partials

**Hallazgo:** HAL-DEMO-FE-08

1. Extraer partials compartidos:
   - `_demo-metrics.html.twig` (metricas cards)
   - `_demo-chart.html.twig` (canvas chart)
   - `_demo-cta.html.twig` (CTA conversion)
   - `_demo-magic-moment.html.twig` (acciones magic moment)

2. Refactorizar `demo-dashboard.html.twig` y `demo-dashboard-view.html.twig` para usar:
   ```twig
   {% include '@ecosistema_jaraba_core/partials/_demo-metrics.html.twig' with {
       metrics: metrics,
   } only %}
   ```

---

### S6-06: DRY SCSS — eliminar duplicacion playground

**Hallazgo:** HAL-DEMO-FE-07

1. Elegir `_demo.scss` (modulo) como canonical.
2. Eliminar `_demo-playground.scss` (theme) o convertir a imports.
3. Unificar nombres de tokens CSS.

---

### S6-07: Unificar tokens CSS

**Hallazgo:** HAL-DEMO-FE-10

Definir tokens canonicos para demo:
```scss
// Canonical demo tokens (scoped to .demo-*)
.demo-landing, .demo-dashboard, .demo-storytelling, .demo-playground {
  --demo-bg: var(--ej-bg-secondary, #f8fafc);
  --demo-surface: var(--ej-bg-surface, #ffffff);
  --demo-text: var(--ej-text-primary, #1e293b);
  --demo-text-muted: var(--ej-text-muted, #64748b);
  --demo-border: var(--ej-color-border, #e2e8f0);
}
```

---

### S6-08: Permisos admin demo

**Hallazgo:** HAL-DEMO-CFG-02

Anadir en `ecosistema_jaraba_core.permissions.yml`:
```yaml
administer demo configuration:
  title: 'Administrar configuracion de demo'
  description: 'Permite gestionar perfiles, limites y tokens de la experiencia demo.'
  restrict access: true

view demo analytics:
  title: 'Ver analiticas de demo'
  description: 'Permite consultar metricas de conversion y uso de la experiencia demo.'
```

---

### S6-09: GDPR consent para sesiones anonimas

**Hallazgo:** PLG-GDPR

1. Anadir banner de consentimiento en `demo-landing.html.twig`:
   ```twig
   <div class="demo-privacy-notice" role="region" aria-label="{{ 'Aviso de privacidad'|t }}">
     <p>{{ 'Al iniciar la demo, aceptas que almacenemos datos anonimos de tu sesion durante 1 hora para mejorar la experiencia.'|t }}</p>
     <a href="{{ path('entity.node.canonical', { node: privacy_node_id }) }}">{{ 'Politica de privacidad'|t }}</a>
   </div>
   ```

2. En `DemoInteractiveService`, anonimizar IP antes de almacenar (hash con salt diario):
   ```php
   $hashedIp = hash('sha256', $clientIp . date('Y-m-d') . $this->getSalt());
   ```

---

### S6-10: Race condition fix con transaccion

**Hallazgo:** HAL-DEMO-BE-07

```php
$transaction = $this->database->startTransaction();
try {
    $row = $this->database->select('demo_sessions', 'ds')
        ->fields('ds', ['session_data'])
        ->condition('session_id', $sessionId)
        ->forUpdate()
        ->execute()
        ->fetchField();
    // ... modify and update ...
} catch (\Exception $e) {
    // Transaction auto-rolls back on exception.
}
```

---

### S6-11: Email HMAC token en conversion

**Hallazgo:** HAL-DEMO-BE-08

Reemplazar email en query params por HMAC token temporal:
```php
$token = hash_hmac('sha256', $sessionId . $email, Settings::getHashSalt());
$url = Url::fromRoute('ecosistema_jaraba_core.onboarding.register', [], [
    'query' => [
        'demo_token' => $token,
        'demo_session' => $sessionId,
    ],
]);
```

---

### S6-12: Mover business logic a servicios

**Hallazgo:** HAL-DEMO-BE-12

1. Mover stories de `DemoController::demoAiStorytelling()` a `DemoInteractiveService::getDemoStory()`.
2. Mover AI scenarios de `DemoController::aiPlayground()` a `DemoInteractiveService::getAiScenarios()`.

---

### S6-13: Config entity para rate limits

**Hallazgo:** HAL-DEMO-CFG-04

Crear `ecosistema_jaraba_core.demo_settings` config:
```yaml
rate_limits:
  start: 10
  track: 30
  session: 20
  convert: 5
session_ttl: 3600
```

---

## 5. Sprint 7: PLG Excellence

**Prioridad:** P2 (diferenciacion competitiva)
**Hallazgos resueltos:** PLG-AB, PLG-ANALYTICS, PLG-SOCIAL, PLG-URGENCY, PLG-PROGRESSIVE, CFG-03, CFG-05, CFG-06, CFG-08, FE-12

### S7-01: Tabla demo_analytics + pipeline agregacion

**Hallazgo:** HAL-DEMO-CFG-05

1. Schema:
   ```php
   'demo_analytics' => [
       'fields' => [
           'date' => ['type' => 'varchar', 'length' => 10],
           'vertical' => ['type' => 'varchar', 'length' => 32],
           'profile_id' => ['type' => 'varchar', 'length' => 32],
           'sessions_started' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'ttfv_avg_seconds' => ['type' => 'float', 'default' => 0],
           'ttfv_p50_seconds' => ['type' => 'float', 'default' => 0],
           'ttfv_p95_seconds' => ['type' => 'float', 'default' => 0],
           'conversions' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'funnel_landing' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'funnel_profile_select' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'funnel_dashboard_view' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'funnel_value_action' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'funnel_conversion_attempt' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
           'funnel_conversion_success' => ['type' => 'int', 'unsigned' => TRUE, 'default' => 0],
       ],
       'primary key' => ['date', 'vertical', 'profile_id'],
       'indexes' => ['date' => ['date'], 'vertical' => ['vertical']],
   ];
   ```

2. En `cleanupExpiredSessions()`, agregar datos antes de eliminar:
   ```php
   // 1. Read expired sessions
   // 2. Aggregate by date + vertical + profile
   // 3. UPSERT into demo_analytics
   // 4. Delete expired sessions
   ```

---

### S7-02: Event dispatch para lifecycle demo

**Hallazgo:** HAL-DEMO-CFG-03

1. Crear `DemoSessionEvent`:
   ```php
   class DemoSessionEvent extends Event {
       const CREATED = 'demo.session.created';
       const VALUE_ACTION = 'demo.session.value_action';
       const CONVERSION = 'demo.session.conversion';
       const EXPIRED = 'demo.session.expired';
   }
   ```

2. Dispatch en `DemoInteractiveService`:
   - `generateDemoSession()` → dispatch `CREATED`
   - `trackDemoAction()` si es value action → dispatch `VALUE_ACTION`
   - `convertToRealAccount()` → dispatch `CONVERSION`
   - `cleanupExpiredSessions()` → dispatch `EXPIRED` per session

---

### S7-03: A/B testing integration

**Hallazgo:** PLG-AB

1. Inyectar `AbTestService` en `DemoController`.
2. Definir experimentos:
   - `demo_landing_cta`: variantes de CTA text
   - `demo_profile_order`: random vs popularity order
   - `demo_conversion_modal_timing`: inmediato vs despues de 2 value actions

3. Almacenar variante en `session_data`:
   ```php
   $session['ab_variants'] = [
       'demo_landing_cta' => $this->abTestService->getVariant('demo_landing_cta'),
       // ...
   ];
   ```

---

### S7-04: Storytelling con IA real

**Hallazgos:** HAL-DEMO-CFG-08, HAL-DEMO-FE-12

1. Inyectar `StorytellingAgent` (optional `@?`) en `DemoController`.
2. En `demoAiStorytelling()`:
   ```php
   if ($this->storytellingAgent) {
       $prompt = $this->buildStoryPrompt($session);
       $result = $this->storytellingAgent->execute([
           'prompt' => $prompt,
           'vertical' => $session['profile']['vertical'],
           'context' => ['demo' => TRUE, 'tenant_name' => $session['tenant_name']],
       ]);
       if (!empty($result['output'])) {
           $story = Xss::filter($result['output'], $allowedTags);
       }
   }
   // Fallback to hardcoded stories if agent unavailable
   ```

3. "Regenerar" button calls real API instead of alert().

---

### S7-05: Social proof counters

**Hallazgo:** PLG-SOCIAL

1. Crear metodo `DemoInteractiveService::getActiveDemoCount()`:
   ```php
   public function getActiveDemoCount(): int {
       return (int) $this->database->select('demo_sessions', 'ds')
           ->condition('expires', time(), '>')
           ->countQuery()
           ->execute()
           ->fetchField();
   }
   ```

2. Renderizar en landing:
   ```twig
   <div class="demo-social-proof" aria-label="{{ 'Prueba social'|t }}">
     {{ '@count personas estan explorando la demo ahora'|t({'@count': active_demo_count}) }}
   </div>
   ```

---

### S7-06: Session countdown timer

**Hallazgo:** PLG-URGENCY

1. Pasar `session.expires` timestamp a JS via `drupalSettings`.
2. JS countdown:
   ```javascript
   const updateCountdown = () => {
       const remaining = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
       const minutes = Math.floor(remaining / 60);
       const seconds = remaining % 60;
       countdownEl.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
       if (remaining <= 300) { // 5 min warning
           countdownEl.classList.add('demo-countdown--warning');
       }
   };
   setInterval(updateCountdown, 1000);
   ```

---

### S7-07: Progressive disclosure mejorado

**Hallazgo:** PLG-PROGRESSIVE

1. Definir milestones:
   - Level 1 (initial): Dashboard + metricas
   - Level 2 (1 action): Products + AI storytelling unlock
   - Level 3 (3 actions): AI playground unlock
   - Level 4 (TTFV reached): Full features + conversion prompt

2. Renderizar locked sections with glass overlay:
   ```twig
   {% if demo_level < 3 %}
     <div class="demo-locked-overlay">
       <p>{{ 'Completa @remaining acciones mas para desbloquear'|t({'@remaining': 3 - actions_count}) }}</p>
     </div>
   {% endif %}
   ```

---

### S7-08: Cleanup con retencion/agregacion

**Hallazgo:** HAL-DEMO-CFG-06

Modificar `cleanupExpiredSessions()`:
1. SELECT expired sessions
2. Aggregate into `demo_analytics` table
3. DELETE expired sessions
4. Log aggregation stats

---

### S7-09: Config entity para rate limits

Crear form admin para `ecosistema_jaraba_core.demo_settings`:
- Rate limits editables
- Session TTL configurable
- Feature gate limits editables
- Perfiles activos/inactivos toggle

---

### S7-10: Print styles

**Hallazgo:** PERF-05

```scss
@media print {
  .demo-nudge-fab,
  .demo-convert-cta,
  .demo-convert-modal,
  .demo-playground__chat-input-row { display: none; }
  .demo-dashboard { background: white; }
  .demo-metric-card { break-inside: avoid; border: 1px solid #ccc; }
}
```

---

## 6. Dependencias y Riesgos

| Riesgo | Mitigacion |
|--------|-----------|
| StorytellingAgent no disponible sin API key | Fallback a stories hardcoded (existente) |
| AbTestService no configurado | Guard con `hasService()` |
| Qdrant no disponible para social proof | Count from DB (ya implementado) |
| GDPR compliance requiere revision legal | Implementar mecanismo tecnico; revision legal posterior |
| Mobile-first SCSS refactor puede romper layout | Regression testing visual antes/despues |

---

## 7. Criterios de Aceptacion Globales

### Sprint 5
- [ ] `php -l` sin errores en todos los archivos PHP modificados
- [ ] axe-core: 0 critical/serious violations en paginas demo
- [ ] Feature gates enforced: AI messages capped at 10, stories at 3
- [ ] Nudges renderizan en dashboard con dismiss funcional
- [ ] `|raw` eliminado de storytelling template
- [ ] PB templates renderizan en Page Builder editor

### Sprint 6
- [ ] `drush locale:check` no reporta strings sin traducir en demo
- [ ] `SandboxTenantService` marcado @deprecated
- [ ] SCSS mobile-first: layout correcto en 320px, 768px, 1024px, 1440px
- [ ] Template LOC reducido 30%+ via partials
- [ ] IP anonimizada en demo_sessions
- [ ] Admin puede ver/editar rate limits en UI

### Sprint 7
- [ ] `demo_analytics` tabla con datos historicos preservados
- [ ] A/B variant assignado en session data
- [ ] Social proof counter renderiza en landing
- [ ] Countdown timer visible en dashboard
- [ ] Storytelling genera contenido real con AI agent (si disponible)
- [ ] Event subscribers pueden escuchar lifecycle events

---

*Fin del plan. Version 1.0.0 — 39 items, 3 sprints, objetivo 95%+ clase mundial.*
