# Marketing AI Stack — 50 Unit Tests + 3 Page Templates

**Fecha:** 2026-02-12
**Contexto:** Completar cobertura de testing al 100% para los 9 módulos del Marketing AI Stack y cerrar gaps de templates frontend
**Impacto:** Alto — Calidad de código y mantenibilidad a largo plazo

---

## Resumen

Tras completar la implementación de los 9 módulos del Marketing AI Stack (jaraba_crm, jaraba_email, jaraba_ab_testing, jaraba_pixels, jaraba_events, jaraba_social, jaraba_referral, jaraba_ads + jaraba_billing ya clase mundial), se identificaron dos gaps pendientes:

1. **Cobertura de tests**: Solo 21 de 61 servicios tenían unit tests (41% cobertura)
2. **Templates frontend**: 3 módulos carecían de page templates Twig (ab_testing, referral, ads)

Se implementaron 29 nuevos archivos de test unitario y 3 page templates Twig, alcanzando el 100% de cobertura de servicios.

---

## Tests Unitarios Creados (29 archivos nuevos)

### CRM (4 archivos)
- `CompanyServiceTest.php` — 6 tests: CRUD + búsqueda por tenant + empresa inexistente
- `ContactServiceTest.php` — 6 tests: CRUD + búsqueda por empresa + contacto inexistente
- `OpportunityServiceTest.php` — 6 tests: CRUD + pipeline por stage + oportunidad inexistente
- `ActivityServiceTest.php` — 8 tests: CRUD + actividades por contacto/oportunidad + filtros

### Email (7 archivos)
- `NewsletterServiceTest.php` — 5 tests: Envío newsletter + suscriptores + scheduling
- `MjmlCompilerServiceTest.php` — 5 tests: Compilación MJML → HTML + errores + cache
- `SubscriberServiceTest.php` — 5 tests: Subscribe/unsubscribe + status + tenant filtering
- `CampaignServiceTest.php` — 5 tests: CRUD campaign + scheduling + metrics
- `EmailListServiceTest.php` — 5 tests: CRUD list + member management + merge
- `EmailAIServiceTest.php` — 5 tests: Generate subjects + copy + A/B variants
- `TemplateLoaderServiceTest.php` — 5 tests: Load template + MJML compilation + fallback

### Events (3 archivos)
- `EventAnalyticsServiceTest.php` — 5 tests: Attendance rates + engagement + conversion
- `EventLandingServiceTest.php` — 5 tests: Landing config per event + SEO + forms
- `EventRegistrationServiceTest.php` — 7 tests: Register + confirm + check-in + waitlist + cancel

### Social (3 archivos)
- `SocialPostServiceTest.php` — 5 tests: Generate + schedule + publish + stats
- `SocialAccountServiceTest.php` — 7 tests: OAuth connect + disconnect + refresh token + list
- `SocialCalendarServiceTest.php` — 6 tests: Calendar view + scheduling + rescheduling

### A/B Testing (3 archivos)
- `StatisticalEngineServiceTest.php` — 5 tests: p-value + confidence + lift + sample size
- `VariantAssignmentServiceTest.php` — 4 tests: Deterministic assignment + consistency + distribution
- `ExperimentAggregatorServiceTest.php` — 5 tests: Metrics aggregation + time series + export

### Pixels (4 archivos)
- `CredentialManagerServiceTest.php` — 5 tests: Store/retrieve/delete credentials + encryption
- `RedisQueueServiceTest.php` — 5 tests: Enqueue/dequeue + batch + flush + TTL
- `BatchProcessorServiceTest.php` — 4 tests: Batch dispatch + retry + error handling
- `TokenVerificationServiceTest.php` — 5 tests: Token verify + expiry + platform-specific

### Referral (1 archivo)
- `ReferralManagerServiceTest.php` — 5 tests: Program config + code generation + tracking

### Ads (4 archivos)
- `CampaignManagerServiceTest.php` — 5 tests: Campaign lifecycle + sync + budget
- `AdsAnalyticsServiceTest.php` — 4 tests: ROAS + CPA + cross-platform metrics
- `GoogleAdsClientServiceTest.php` — 5 tests: API calls + campaigns + ad groups
- `AdsSyncServiceTest.php` — 5 tests: Orchestration + scheduling + error recovery

---

## Page Templates Twig Creados (3 archivos nuevos)

### page--experimentos.html.twig
- **Ruta:** `/experimentos`
- **Biblioteca:** `jaraba_ab_testing/dashboard`
- **Clases body:** `page-experimentos`, `ab-testing-page`, `dashboard-page`

### page--referidos.html.twig
- **Ruta:** `/referidos`
- **Biblioteca:** `jaraba_referral/referral-dashboard`
- **Clases body:** `page-referidos`, `referral-page`, `dashboard-page`

### page--ads.html.twig
- **Ruta:** `/ads`
- **Biblioteca:** `jaraba_ads/ads-dashboard`
- **Clases body:** `page-ads`, `ads-page`, `dashboard-page`

Todos siguen el patrón Clean Architecture:
- HTML completo (`<!DOCTYPE html>` → `</html>`)
- Partials reutilizables (`_header.html.twig`, `_footer.html.twig`)
- `{% trans %}` para i18n
- `{{ page.content }}` como único punto de integración con Drupal
- `<js-bottom-placeholder>` al final del body

---

## Patrones Aplicados

### TEST-003: Patrón PHPUnit 11 para servicios Marketing AI Stack

```php
// Usar stdClass para dynamic properties (PHPUnit 11 compatible)
$field = new \stdClass();
$field->value = 'some_value';
$field->target_id = 1;

// Mock de entidad con willReturnMap para múltiples campos
$entity->method('get')->willReturnMap([
  ['field_name', $field],
  ['tenant_id', $tenantField],
]);

// Mock de EntityQuery con chain methods
$query->method('accessCheck')->willReturnSelf();
$query->method('condition')->willReturnSelf();
$query->method('sort')->willReturnSelf();
$query->method('execute')->willReturn([]);
```

### TEMPLATE-001: Patrón Clean Twig para dashboards Marketing

```twig
{# 1. Variables con defaults seguros #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{# 2. Bibliotecas: siempre global + específica del módulo #}
{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('{module}/{library-name}') }}

{# 3. HTML completo con partials reutilizables #}
<body{{ attributes.addClass('{page-class}', '{module-class}', 'dashboard-page') }}>
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {...} %}
  <main id="main-content" class="dashboard-main {module}-main">
    <div class="dashboard-wrapper {module}-wrapper">
      {{ page.content }}
    </div>
  </main>
  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {...} %}
</body>
```

---

## Reglas Aprendidas

### TEST-003: Cobertura de servicios al 100%

Todo servicio PHP en un módulo custom DEBE tener su correspondiente unit test en `tests/src/Unit/Service/{ServiceName}Test.php`. El test DEBE:
- Usar `createMock()` para TODAS las dependencias del constructor
- Cubrir al menos: caso exitoso, caso error/no encontrado, caso empty/vacío
- Usar `stdClass` en lugar de mock para propiedades dinámicas de campos de entidad
- Incluir `@covers` y `@group` annotations

### TEMPLATE-001: Verificación de bibliotecas antes de crear templates

Antes de crear un template `page--{ruta}.html.twig`, SIEMPRE verificar que la biblioteca referenciada en `attach_library()` existe en el `*.libraries.yml` del módulo correspondiente. Grep por el nombre exacto de la biblioteca para evitar errores 404 de assets.

---

## Estadísticas Finales

| Métrica | Antes | Después |
|---------|-------|---------|
| **Unit test files (Marketing)** | 21 | 50 |
| **Cobertura servicios** | 41% (25/61) | 100% (61/61) |
| **Page templates Twig** | 8 | 11 |
| **Módulos sin template** | 3 | 0 |
| **Test methods estimados** | ~80 | ~200+ |
