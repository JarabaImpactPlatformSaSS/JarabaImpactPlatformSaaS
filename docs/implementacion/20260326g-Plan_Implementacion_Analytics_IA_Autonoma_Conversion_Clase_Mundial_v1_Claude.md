# Plan de Implementacion: Analytics Integrado + IA Autonoma de Conversion

| Campo | Valor |
|---|---|
| Version | 1.0.0 |
| Fecha | 2026-03-26 |
| Autor | Claude Opus 4.6 (1M context) |
| Auditoria base | `docs/analisis/2026-03-26_Auditoria_Analytics_IA_Autonoma_Conversion_Clase_Mundial_v1.md` |
| Score inicial | 4/10 |
| Score objetivo | 10/10 |
| Sprints | 4 (K, L, M, N) |
| Specs | SPEC-ANA-001 a SPEC-ANA-010 |

---

## Tabla de Contenidos

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Principios Arquitectonicos](#2-principios-arquitectonicos)
3. [Pre-Implementacion Checklist](#3-pre-implementacion-checklist)
4. [Sprint K: Dashboard Analytics en Hub](#4-sprint-k-dashboard-analytics-en-hub)
5. [Sprint L: IA Proactiva de Conversion](#5-sprint-l-ia-proactiva-de-conversion)
6. [Sprint M: Agente Autonomo de Conversion](#6-sprint-m-agente-autonomo-de-conversion)
7. [Sprint N: Embudo Visual + Alertas](#7-sprint-n-embudo-visual--alertas)
8. [Medidas de Salvaguarda](#8-medidas-de-salvaguarda)
9. [Tabla de Correspondencia Specs](#9-tabla-de-correspondencia-specs)
10. [Tabla de Cumplimiento Directrices](#10-tabla-de-cumplimiento-directrices)
11. [Verificacion Post-Implementacion](#11-verificacion-post-implementacion)
12. [Glosario](#12-glosario)

---

## 1. Objetivos y Alcance

### 1.1 Objetivo Principal

Integrar el abundante backend de analytics existente (35+ servicios, 15+ entities, 25+ rutas API) en una experiencia de usuario accesible desde el hub del tenant, complementada con un agente IA autonomo que optimiza conversiones de forma proactiva.

### 1.2 Entregables

1. **Dashboard Analytics en Hub** — Ruta `/mi-analytics` con 5 tabs, KPIs en tiempo real, graficos Chart.js, embudo visual (SPEC-ANA-001, SPEC-ANA-002)
2. **IA Proactiva de Conversion** — Deteccion de anomalias, insights automaticos, bridge con Copilot para preguntas de analytics (SPEC-ANA-003, SPEC-ANA-004, SPEC-ANA-008)
3. **Agente Autonomo de Conversion** — SmartBaseAgent semanal que analiza, recomienda y auto-aplica ganadores A/B (SPEC-ANA-005, SPEC-ANA-006)
4. **Embudo Visual + Alertas** — Componente visual de embudo, alertas de anomalias en tiempo real, widget en daily actions (SPEC-ANA-007, SPEC-ANA-009, SPEC-ANA-010)

### 1.3 Fuera de Alcance

- Reimplementacion de servicios backend existentes (ya funcionan)
- Migracion de datos historicos de GA4 a analytics_event
- Integracion con herramientas BI externas (Looker, Metabase)
- Heatmaps overlay visual completo (requiere sprint independiente)

### 1.4 Dependencias Existentes Aprovechadas

| Modulo | Servicios reutilizados | Entities reutilizadas |
|---|---|---|
| jaraba_analytics | AnalyticsService, AnalyticsAggregatorService, FunnelTrackingService, CohortAnalysisService, RetentionCalculatorService, NpsSurveyService, ProductMetricsAggregatorService, AnalyticsDataService, ReportExecutionService | AnalyticsEvent, AnalyticsDaily, FunnelDefinition, ProductMetricSnapshot |
| jaraba_ab_testing | StatisticalEngineService, ExperimentAggregatorService, ExperimentOrchestratorService, VariantAssignmentService | ABExperiment, ABVariant, ExperimentResult |
| jaraba_ai_agents | CausalAnalyticsService, ProactiveInsightsService | (usa analytics entities) |
| jaraba_copilot_v2 | CopilotFunnelTrackingService | (tabla directa copilot_funnel_event) |
| jaraba_pixels | PixelDispatcherService | TrackingEvent |
| jaraba_customer_success | EngagementScoringService, HealthScoreCalculatorService | (scores calculados) |
| ecosistema_jaraba_core | TenantAnalyticsService, TenantContextService | (datos tenant) |
| jaraba_page_builder | ExternalAnalyticsService | (GA4 + Search Console) |

---

## 2. Principios Arquitectonicos

### 2.1 Patrones Obligatorios

| Patron | Aplicacion en este plan |
|---|---|
| ZERO-REGION-001 | Dashboard /mi-analytics usa `{{ clean_content }}`, datos via `hook_preprocess_page()` |
| ZERO-REGION-002 | NUNCA pasar entity objects como non-# keys en render arrays del dashboard |
| ZERO-REGION-003 | drupalSettings via `$variables['#attached']` en preprocess, NO en controller |
| CSS-VAR-ALL-COLORS-001 | Todos los colores de graficos usan `var(--ej-*)` con fallback |
| ICON-CONVENTION-001 | `jaraba_icon('analytics', 'nombre', {...})` para todos los iconos del dashboard |
| TENANT-001 | TODA query de analytics filtra por tenant_id. Sin excepciones |
| TENANT-002 | tenant_id via `ecosistema_jaraba_core.tenant_context`, NUNCA ad-hoc |
| SLIDE-PANEL-RENDER-002 | Detalle de experimento, insight, reporte abierto en slide-panel con `_controller:` |
| PREMIUM-FORMS-PATTERN-001 | Formulario de configuracion de funnels extiende PremiumEntityFormBase |
| OPTIONAL-CROSSMODULE-001 | Referencias a jaraba_ab_testing, jaraba_ai_agents, jaraba_heatmap son `@?` |
| AGENT-GEN2-PATTERN-001 | ConversionOptimizationAgent extiende SmartBaseAgent, override doExecute() |
| SMART-AGENT-CONSTRUCTOR-001 | 10 args constructor (6 core + 4 optional @?) |
| MODEL-ROUTING-CONFIG-001 | Analisis de tendencias = balanced (Sonnet 4.6), recomendaciones = premium (Opus 4.6) |
| TWIG-INCLUDE-ONLY-001 | Parciales del dashboard con `{% include ... only %}` |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute(), NUNCA hardcoded |
| INNERHTML-XSS-001 | Datos de API via Drupal.checkPlain() antes de innerHTML |
| CSRF-API-001 | Endpoints mutacion con `_csrf_request_header_token: 'TRUE'` |

### 2.2 Stack Tecnologico

- **Graficos:** Chart.js v4 (local, NO CDN) — line, bar, doughnut, funnel custom
- **Datos:** APIs JSON existentes + nuevos endpoints ligeros
- **Cache:** CacheBackendInterface con tags `analytics_dashboard:{tenant_id}`, max-age 60s
- **Notificaciones:** EiMultichannelNotificationService (email + in-app)
- **IA:** CausalAnalyticsService (Opus 4.6) + ProactiveInsightsService (cron) + nuevo ConversionOptimizationAgent

---

## 3. Pre-Implementacion Checklist

### 3.1 Verificaciones de Backend Existente

- [ ] Confirmar que `analytics_event` y `analytics_daily` tienen datos recientes (ultimos 7 dias)
- [ ] Verificar que AnalyticsAggregatorService se ejecuta en cron correctamente
- [ ] Confirmar que FunnelTrackingService calcula conversiones con datos reales
- [ ] Verificar que ProactiveInsightsService genera insights (revisar State `jaraba_proactive_insights.last_run`)
- [ ] Confirmar que StatisticalEngineService devuelve resultados correctos con datos de test
- [ ] Verificar que CausalAnalyticsService se conecta correctamente al provider LLM

### 3.2 Verificaciones de Infraestructura

- [ ] Chart.js v4 disponible en `web/libraries/chart.js/` o como library Drupal
- [ ] Permiso `access jaraba analytics` existe y esta asignado a roles admin de tenant
- [ ] `funnel-analytics.js` esta activo y enviando eventos a `/api/v1/analytics/event`
- [ ] Redis cache funcional para tags `analytics_*`

### 3.3 Ficheros a Crear (nuevos)

| Fichero | Modulo/Tema | Proposito |
|---|---|---|
| `src/Controller/AnalyticsHubController.php` | jaraba_analytics | Controller hub /mi-analytics |
| `templates/analytics-hub.html.twig` | jaraba_analytics | Template principal del hub |
| `templates/partials/_analytics-overview.html.twig` | jaraba_analytics | Tab overview |
| `templates/partials/_analytics-funnel.html.twig` | jaraba_analytics | Tab embudo |
| `templates/partials/_analytics-experiments.html.twig` | jaraba_analytics | Tab A/B |
| `templates/partials/_analytics-insights.html.twig` | jaraba_analytics | Tab insights IA |
| `templates/partials/_analytics-seo.html.twig` | jaraba_analytics | Tab SEO |
| `src/Service/ConversionInsightsService.php` | jaraba_analytics | Deteccion anomalias conversion |
| `src/Service/CtaRecommendationService.php` | jaraba_analytics | Recomendaciones de CTA |
| `src/Agent/ConversionOptimizationAgent.php` | jaraba_ai_agents | Agente autonomo conversion |
| `js/analytics-hub.js` | jaraba_analytics | Comportamientos Chart.js + tabs |
| `js/funnel-visual.js` | jaraba_analytics | Componente visual de embudo |
| `scss/routes/_mi-analytics.scss` | ecosistema_jaraba_theme | Estilos del dashboard hub |
| `page--mi-analytics.html.twig` | ecosistema_jaraba_theme | Template pagina Zero Region |
| `src/UserProfile/Section/AnalyticsDashboardProfileSection.php` | ecosistema_jaraba_core | Seccion analytics en perfil |

### 3.4 Ficheros a Modificar (existentes)

| Fichero | Cambio |
|---|---|
| `jaraba_analytics.routing.yml` | Anadir rutas /mi-analytics/* |
| `jaraba_analytics.libraries.yml` | Anadir libraries analytics-hub, funnel-visual |
| `jaraba_analytics.module` | hook_theme() para nuevos templates |
| `ecosistema_jaraba_theme.theme` | hook_preprocess_page() para datos analytics |
| `ecosistema_jaraba_theme.libraries.yml` | Library route-mi-analytics |
| `jaraba_ai_agents.services.yml` | Registrar ConversionOptimizationAgent |
| `jaraba_ai_agents.module` | hook_cron() para agente semanal |
| `ecosistema_jaraba_core.services.yml` | Registrar AnalyticsDashboardProfileSection |
| `ProactiveInsightsService.php` | Anadir 3 tipos insight de conversion |
| `ExperimentOrchestratorService.php` | Anadir logica auto-apply con salvaguardas |

---

## 4. Sprint K: Dashboard Analytics en Hub

**Duracion estimada:** 3-4 dias
**Specs:** SPEC-ANA-001, SPEC-ANA-009

### 4.1 AnalyticsHubController — /mi-analytics con 5 tabs

**Fichero:** `web/modules/custom/jaraba_analytics/src/Controller/AnalyticsHubController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\AnalyticsService;
use Drupal\jaraba_analytics\Service\AnalyticsDataService;
use Drupal\jaraba_analytics\Service\FunnelTrackingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para el Analytics Hub en el frontend del tenant.
 *
 * Patron Zero Region: devuelve render array minimo.
 * Los datos se inyectan via hook_preprocess_page() en el tema.
 *
 * SPEC-ANA-001: Dashboard Analytics Hub.
 */
class AnalyticsHubController extends ControllerBase {

  protected TenantContextService $tenantContext;
  protected AnalyticsService $analyticsService;
  protected ?AnalyticsDataService $dataService = NULL;
  protected ?FunnelTrackingService $funnelService = NULL;

  public function __construct(
    TenantContextService $tenant_context,
    AnalyticsService $analytics_service,
    ?AnalyticsDataService $data_service = NULL,
    ?FunnelTrackingService $funnel_service = NULL,
  ) {
    $this->tenantContext = $tenant_context;
    $this->analyticsService = $analytics_service;
    $this->dataService = $data_service;
    $this->funnelService = $funnel_service;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('jaraba_analytics.analytics_service'),
      $container->has('jaraba_analytics.data_service') ? $container->get('jaraba_analytics.data_service') : NULL,
      $container->has('jaraba_analytics.funnel_tracking') ? $container->get('jaraba_analytics.funnel_tracking') : NULL,
    );
  }

  /**
   * Renderiza el hub de analytics.
   *
   * Los datos reales se inyectan en hook_preprocess_page()
   * siguiendo ZERO-REGION-001.
   */
  public function dashboard(Request $request): array {
    $tab = $request->query->get('tab', 'overview');
    $validTabs = ['overview', 'funnel', 'ab-testing', 'insights', 'seo'];
    if (!in_array($tab, $validTabs, TRUE)) {
      $tab = 'overview';
    }

    return [
      '#type' => 'markup',
      '#markup' => '',
      '#attached' => [
        'library' => [
          'jaraba_analytics/analytics-hub',
        ],
      ],
    ];
  }

}
```

**Ruta a anadir en `jaraba_analytics.routing.yml`:**

```yaml
# Hub de Analytics para tenants (SPEC-ANA-001).
jaraba_analytics.hub_dashboard:
  path: '/mi-analytics'
  defaults:
    _controller: '\Drupal\jaraba_analytics\Controller\AnalyticsHubController::dashboard'
    _title: 'Mis Analytics'
  requirements:
    _permission: 'access jaraba analytics'
  options:
    _admin_route: FALSE
```

### 4.2 Template analytics-hub.html.twig

**Fichero:** `web/modules/custom/jaraba_analytics/templates/analytics-hub.html.twig`

Estructura del template principal con 5 tabs navegables. Cada tab es un parcial incluido con `only`:

- Header con titulo, selector de periodo (7d/30d/90d), boton exportar
- Nav pills para tabs con `position: sticky`
- Tab overview: 4 KPI cards + grafico tendencias + top pages + fuentes trafico
- Tab funnel: selector de funnel + barras decrecientes + comparativa
- Tab AB testing: lista experimentos + KPIs + significancia
- Tab insights: insights recientes + formulario consulta causal
- Tab SEO: posiciones Search Console + oportunidades

Textos con `{% trans %}...{% endtrans %}`.
Iconos con `jaraba_icon('analytics', ...)` y variante duotone.
Colores via `var(--ej-azul-corporativo)`, `var(--ej-naranja-impulso)`, `var(--ej-verde-innovacion)`.

### 4.3 SCSS _mi-analytics.scss

**Fichero:** `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_mi-analytics.scss`

Estructura de estilos:

```scss
@use '../variables' as *;

// SPEC-ANA-001: Analytics Hub styles
// CSS-VAR-ALL-COLORS-001: todos los colores via var(--ej-*)

.analytics-hub {
  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--ej-spacing-lg, 1.5rem);
  }

  &__kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--ej-spacing-md, 1rem);
    margin-bottom: var(--ej-spacing-xl, 2rem);
  }

  &__kpi-card {
    background: var(--ej-surface-card, #fff);
    border-radius: var(--ej-radius-lg, 12px);
    padding: var(--ej-spacing-lg, 1.5rem);
    border: 1px solid var(--ej-border-subtle, #e5e7eb);
    // ... sombra, transiciones
  }

  &__chart-container {
    background: var(--ej-surface-card, #fff);
    border-radius: var(--ej-radius-lg, 12px);
    padding: var(--ej-spacing-lg, 1.5rem);
    min-height: 300px;
  }

  &__tabs {
    display: flex;
    flex-wrap: wrap;
    gap: var(--ej-spacing-sm, 0.5rem);
    position: sticky;
    top: 0;
    z-index: 10;
    // STICKY-NAV-WRAP-001
  }

  &__funnel-bar {
    // Barras decrecientes del embudo visual
    height: 48px;
    border-radius: var(--ej-radius-md, 8px);
    transition: width 0.6s ease;
    // Colores gradientes via CSS custom properties
  }
}

// Responsive
@media (max-width: 768px) {
  .analytics-hub__kpi-grid {
    grid-template-columns: 1fr;
  }
}
```

**Compilacion:** Anadir a `build:routes` en package.json del tema. Verificar timestamp CSS > SCSS tras compilar (SCSS-COMPILE-VERIFY-001).

### 4.4 Routing + hook_theme + Library

**hook_theme() en `jaraba_analytics.module`:**

```php
// En jaraba_analytics_theme():
$hooks['analytics_hub'] = [
  'variables' => [
    'active_tab' => 'overview',
    'tenant_id' => NULL,
    'overview_data' => [],
    'funnel_data' => [],
    'experiments_data' => [],
    'insights_data' => [],
    'seo_data' => [],
    'period' => 30,
  ],
  'template' => 'analytics-hub',
];
```

**Library en `jaraba_analytics.libraries.yml`:**

```yaml
analytics-hub:
  version: 1.0.0
  css:
    theme:
      css/analytics-hub.css: {}
  js:
    js/analytics-hub.js: {}
  dependencies:
    - core/drupal
    - core/once
    - core/drupalSettings
    # Chart.js como library local
    - jaraba_analytics/chart-js

chart-js:
  version: 4.4.0
  js:
    js/vendor/chart.min.js: { minified: true }

funnel-visual:
  version: 1.0.0
  js:
    js/funnel-visual.js: {}
  dependencies:
    - core/drupal
    - core/once
```

**hook_page_attachments_alter() para ruta:**

```php
function ecosistema_jaraba_theme_page_attachments_alter(array &$attachments) {
  $route = \Drupal::routeMatch()->getRouteName();
  if ($route === 'jaraba_analytics.hub_dashboard') {
    $attachments['#attached']['library'][] = 'ecosistema_jaraba_theme/route-mi-analytics';
  }
}
```

**hook_preprocess_page() para datos (ZERO-REGION-001):**

```php
if ($route_name === 'jaraba_analytics.hub_dashboard') {
  try {
    $tenantId = $variables['tenant_id'] ?? NULL;
    if (!$tenantId && \Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenantId = $tenantContext->getCurrentTenantId();
    }

    if ($tenantId && \Drupal::hasService('jaraba_analytics.analytics_service')) {
      $analytics = \Drupal::service('jaraba_analytics.analytics_service');
      $period = (int) \Drupal::request()->query->get('period', 30);
      $period = min(90, max(7, $period));

      $variables['analytics_overview'] = $analytics->getOverviewData($tenantId, $period);
      $variables['analytics_period'] = $period;
    }

    // AB Testing data (OPTIONAL-CROSSMODULE-001)
    if (\Drupal::hasService('jaraba_ab_testing.experiment_aggregator')) {
      try {
        $aggregator = \Drupal::service('jaraba_ab_testing.experiment_aggregator');
        $variables['analytics_experiments'] = $aggregator->getActiveSummary($tenantId);
      }
      catch (\Throwable $e) {
        $variables['analytics_experiments'] = [];
      }
    }

    // Insights IA (OPTIONAL-CROSSMODULE-001)
    if (\Drupal::hasService('jaraba_ai_agents.proactive_insights')) {
      try {
        $insights = \Drupal::service('jaraba_ai_agents.proactive_insights');
        $variables['analytics_insights'] = $insights->getRecentForTenant($tenantId, 10);
      }
      catch (\Throwable $e) {
        $variables['analytics_insights'] = [];
      }
    }

    $variables['#attached']['drupalSettings']['analyticsHub'] = [
      'apiBase' => '/api/v1/analytics',
      'tenantId' => $tenantId,
      'period' => $period ?? 30,
      'refreshInterval' => 60000,
    ];
  }
  catch (\Throwable $e) {
    \Drupal::logger('jaraba_analytics')->warning(
      'Error loading analytics hub data: @msg',
      ['@msg' => $e->getMessage()]
    );
  }
}
```

### 4.5 UserProfileSection: analytics_dashboard (weight 45)

**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/UserProfile/Section/AnalyticsDashboardProfileSection.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Dashboard Analytics" en perfil de usuario.
 *
 * SPEC-ANA-009: Acceso rapido al dashboard desde el hub.
 * OPTIONAL-CROSSMODULE-001: jaraba_analytics es modulo opcional.
 */
class AnalyticsDashboardProfileSection extends AbstractUserProfileSection {

  public function getId(): string {
    return 'analytics_dashboard';
  }

  public function getTitle(int $uid): string {
    return (string) $this->t('Dashboard Analytics');
  }

  public function getSubtitle(int $uid): string {
    return (string) $this->t('Metricas de trafico, conversion y rendimiento');
  }

  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart-line'];
  }

  public function getColor(): string {
    return 'corporativo';
  }

  public function getWeight(): int {
    return 45;
  }

  public function getRoute(): string {
    return 'jaraba_analytics.hub_dashboard';
  }

  public function isVisible(int $uid): bool {
    $account = $this->entityTypeManager->getStorage('user')->load($uid);
    return $account && $account->hasPermission('access jaraba analytics');
  }

}
```

Registrar en `ecosistema_jaraba_core.services.yml` con tag `user_profile_section`.

---

## 5. Sprint L: IA Proactiva de Conversion

**Duracion estimada:** 2-3 dias
**Specs:** SPEC-ANA-003, SPEC-ANA-004, SPEC-ANA-008

### 5.1 ConversionInsightsService

**Fichero:** `web/modules/custom/jaraba_analytics/src/Service/ConversionInsightsService.php`

**Proposito:** Analizar datos de analytics_event y detectar anomalias de conversion usando metodo IQR (Interquartile Range) sin depender de LLM para la deteccion basica.

**Metodos principales:**

```php
/**
 * Detecta anomalias de conversion para un tenant.
 *
 * @param string $tenantId
 *   ID del tenant (TENANT-001).
 * @param int $days
 *   Periodo de analisis.
 *
 * @return array<string, mixed>
 *   Lista de anomalias detectadas con tipo, severidad, datos.
 */
public function detectAnomalies(string $tenantId, int $days = 7): array;

/**
 * Analiza el rendimiento de CTAs de un tenant.
 *
 * Usa datos de analytics_event con event_type='cta_click'.
 * Agrupa por CTA label/position y calcula CTR.
 *
 * @return array<string, mixed>
 *   Rankings de CTAs con CTR, posicion, recomendaciones.
 */
public function analyzeCtas(string $tenantId, int $days = 30): array;

/**
 * Genera recomendaciones basadas en datos.
 *
 * No usa LLM. Reglas deterministicas:
 * - conversion_drop > 15%: alerta alta
 * - traffic_spike > 200%: alerta media (puede ser ataque)
 * - funnel_bottleneck drop-off > 50%: alerta alta
 * - cta_low_performance CTR < 1%: sugerencia cambio
 */
public function generateRecommendations(string $tenantId): array;
```

**Tipos de anomalia:**

| Tipo | Condicion | Severidad |
|---|---|---|
| `conversion_drop` | Tasa conversion ultimos 7d < 85% de la media 30d | Alta |
| `traffic_spike` | Visitas dia > media + 2*IQR | Media |
| `traffic_drop` | Visitas dia < media - 1.5*IQR | Alta |
| `funnel_bottleneck` | Drop-off en paso > 50% | Alta |
| `cta_underperform` | CTR de CTA < 1% con > 100 impresiones | Media |
| `bounce_spike` | Tasa rebote > 80% en pagina con > 50 visitas | Media |

**Dependencias (DI):**
- `@jaraba_analytics.analytics_service` (requerido)
- `@jaraba_analytics.funnel_tracking` (requerido)
- `@database` (requerido)
- `@logger.channel.jaraba_analytics` (requerido)
- `@?jaraba_analytics.data_service` (opcional)

### 5.2 Copilot Bridge Analytics

**Modificacion:** Extender cada `CopilotBridgeService` de las 10 verticales para incluir datos de analytics en el contexto de grounding.

**En cada CopilotBridgeService::getContext():**

```php
// Seccion analytics del bridge (OPTIONAL-CROSSMODULE-001).
$analyticsContext = [];
if (\Drupal::hasService('jaraba_analytics.analytics_service')) {
  try {
    $analytics = \Drupal::service('jaraba_analytics.analytics_service');
    $analyticsContext = [
      'conversion_rate_7d' => $analytics->getConversionRate($tenantId, 7),
      'total_visits_7d' => $analytics->getTotalVisits($tenantId, 7),
      'top_pages' => $analytics->getTopPages($tenantId, 5),
      'traffic_trend' => $analytics->getTrafficTrend($tenantId, 7),
    ];
  }
  catch (\Throwable $e) {
    // Non-critical: copilot funciona sin analytics.
  }
}
```

**Integracion con CausalAnalyticsService:**

El Copilot detectara intenciones de tipo `analytics_causal` y delegara a CausalAnalyticsService:

- "por que cayeron mis ventas" -> `diagnostic`
- "que pasaria si subo precios" -> `counterfactual`
- "como van mis conversiones" -> `prescriptive`
- "cuanto trafico tendre la proxima semana" -> `predictive`

Deteccion de intencion via regex (COPILOT-LEAD-CAPTURE-001 patron):

```php
$analyticsPatterns = [
  'diagnostic' => '/(?:por\s*qu[eé]|caus[ao]|raz[oó]n|baj[aoó]|ca[iy][oó]|descend)/iu',
  'counterfactual' => '/(?:qu[eé]\s*pasar[ií]a|si\s*(?:sub|baj|cambi)|hipot[eé]tic)/iu',
  'predictive' => '/(?:cu[aá]nto|previsi[oó]n|pr[oó]xim|futuro|proyecc)/iu',
  'prescriptive' => '/(?:c[oó]mo\s*(?:mejor|aument|optim)|qu[eé]\s*debo|recomiend|sugier)/iu',
];
```

### 5.3 Extension de ProactiveInsightsService

**Modificacion:** `web/modules/custom/jaraba_ai_agents/src/Service/ProactiveInsightsService.php`

Anadir 3 nuevos tipos de insight especificos de conversion al metodo de generacion:

```php
/**
 * Insight types ampliados con conversion.
 *
 * Existentes: usage_anomaly, churn_risk, content_gap,
 *   seo_opportunity, quota_warning.
 *
 * Nuevos (SPEC-ANA-003):
 * - conversion_drop: Tasa conversion < 85% media 30d.
 * - traffic_spike: Trafico > media + 2*IQR.
 * - funnel_bottleneck: Drop-off paso > 50%.
 */
protected function generateInsightsForTenant(string $tenantId): array {
  $insights = [];

  // ... insights existentes ...

  // Nuevos insights de conversion (SPEC-ANA-003).
  if (\Drupal::hasService('jaraba_analytics.conversion_insights')) {
    try {
      $conversionService = \Drupal::service('jaraba_analytics.conversion_insights');
      $anomalies = $conversionService->detectAnomalies($tenantId, 7);
      foreach ($anomalies as $anomaly) {
        $insights[] = [
          'type' => $anomaly['type'],
          'severity' => $anomaly['severity'],
          'title' => $anomaly['title'],
          'description' => $anomaly['description'],
          'data' => $anomaly['data'],
          'tenant_id' => $tenantId,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Conversion insights failed for tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  return $insights;
}
```

### 5.4 Notificacion al Admin cuando hay Anomalia

**Patron:** Cuando ProactiveInsightsService genera un insight con severidad `alta`, notificar inmediatamente al admin del tenant.

**Integracion con EiMultichannelNotificationService (OPTIONAL-CROSSMODULE-001):**

```php
// Tras generar insights con severidad alta:
if ($insight['severity'] === 'alta' && \Drupal::hasService('jaraba_andalucia_ei.multichannel_notification')) {
  try {
    $notifier = \Drupal::service('jaraba_andalucia_ei.multichannel_notification');
    $notifier->send([
      'type' => 'analytics_alert',
      'recipient_uid' => $tenantAdminUid,
      'title' => $insight['title'],
      'body' => $insight['description'],
      'channels' => ['email', 'in_app'],
      'priority' => 'high',
      'action_url' => '/mi-analytics?tab=insights',
    ]);
  }
  catch (\Throwable $e) {
    // Fallback: solo log. Notificacion no es critica.
    $this->logger->info('Insight notification skipped: @msg', ['@msg' => $e->getMessage()]);
  }
}
```

**Alternativa si EiMultichannelNotificationService no esta disponible:** Envio directo via MailManager con template `jaraba_analytics_alert`.

---

## 6. Sprint M: Agente Autonomo de Conversion

**Duracion estimada:** 3-4 dias
**Specs:** SPEC-ANA-005, SPEC-ANA-006

### 6.1 ConversionOptimizationAgent extends SmartBaseAgent

**Fichero:** `web/modules/custom/jaraba_ai_agents/src/Agent/ConversionOptimizationAgent.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\jaraba_ai_agents\Base\SmartBaseAgent;

/**
 * Agente autonomo de optimizacion de conversion (SPEC-ANA-005).
 *
 * Ejecuta semanalmente via cron:
 * 1. Recopila datos de analytics_daily + product_metric_snapshot (7 dias).
 * 2. Analiza tendencias con LLM balanced (Sonnet 4.6).
 * 3. Genera recomendaciones priorizadas con LLM premium (Opus 4.6).
 * 4. Auto-aplica ganadores A/B con confianza > 95%.
 * 5. Genera reporte y envia email resumen.
 *
 * AGENT-GEN2-PATTERN-001: Extiende SmartBaseAgent, override doExecute().
 * SMART-AGENT-CONSTRUCTOR-001: 10 args (6 core + 4 optional @?).
 * MODEL-ROUTING-CONFIG-001: balanced para analisis, premium para recomendaciones.
 *
 * Salvaguardas:
 * - AGENT-AUTOAPPLY-001: Solo con confianza > 95% y min 100 exposiciones.
 * - AGENT-LIMIT-001: Max 1 auto-aplicacion por semana por tenant.
 * - AGENT-REVERT-001: Snapshot pre-aplicacion para reversion.
 * - AGENT-AUDIT-001: Audit trail completo.
 * - Kill switch: State 'jaraba_conversion_agent.enabled'.
 */
class ConversionOptimizationAgent extends SmartBaseAgent {

  public function getAgentId(): string {
    return 'conversion_optimization';
  }

  public function getAgentName(): string {
    return 'Agente de Optimizacion de Conversion';
  }

  public function getRequiredCapabilities(): array {
    return ['analytics_read', 'ab_testing_read', 'ab_testing_write'];
  }
}
```

### 6.2 doExecute(): Flujo Completo

```php
protected function doExecute(array $input): array {
  $tenantId = $input['tenant_id'] ?? NULL;
  if (!$tenantId) {
    return ['success' => FALSE, 'error' => 'tenant_id requerido'];
  }

  // Kill switch global.
  if (!$this->state->get('jaraba_conversion_agent.enabled', TRUE)) {
    return ['success' => FALSE, 'error' => 'Agente deshabilitado via kill switch'];
  }

  $result = [
    'success' => TRUE,
    'tenant_id' => $tenantId,
    'timestamp' => date('c'),
    'analysis' => [],
    'recommendations' => [],
    'auto_applied' => [],
  ];

  // Paso 1: Recopilar datos (N1-N2 cascade).
  $data = $this->gatherData($tenantId);

  // Paso 2: Analizar tendencias (balanced tier - Sonnet 4.6).
  $analysis = $this->analyzeTrends($data, $tenantId);
  $result['analysis'] = $analysis;

  // Paso 3: Generar recomendaciones (premium tier - Opus 4.6).
  $recommendations = $this->generateRecommendations($analysis, $tenantId);
  $result['recommendations'] = $recommendations;

  // Paso 4: Auto-apply ganadores A/B (con salvaguardas).
  $autoApplied = $this->autoApplyWinners($tenantId);
  $result['auto_applied'] = $autoApplied;

  // Paso 5: Persistir reporte como ProactiveInsight entity.
  $this->persistReport($result, $tenantId);

  // Paso 6: Enviar email resumen.
  $this->sendWeeklySummary($result, $tenantId);

  return $result;
}
```

**Metodo gatherData():**

```php
protected function gatherData(string $tenantId): array {
  $data = ['period' => 7, 'tenant_id' => $tenantId];

  // Analytics diarios (ultimos 7 dias).
  if (\Drupal::hasService('jaraba_analytics.analytics_service')) {
    try {
      $analytics = \Drupal::service('jaraba_analytics.analytics_service');
      $data['daily_metrics'] = $analytics->getDailyMetrics($tenantId, 7);
      $data['conversion_rate'] = $analytics->getConversionRate($tenantId, 7);
      $data['top_pages'] = $analytics->getTopPages($tenantId, 10);
      $data['traffic_sources'] = $analytics->getTrafficSources($tenantId, 7);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Analytics data gather failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  // Product metrics snapshots.
  if (\Drupal::hasService('jaraba_analytics.product_metrics_aggregator')) {
    try {
      $metrics = \Drupal::service('jaraba_analytics.product_metrics_aggregator');
      $data['product_metrics'] = $metrics->getRecentSnapshots($tenantId, 7);
    }
    catch (\Throwable $e) {
      // Non-critical.
    }
  }

  // Experimentos A/B activos.
  if (\Drupal::hasService('jaraba_ab_testing.experiment_aggregator')) {
    try {
      $experiments = \Drupal::service('jaraba_ab_testing.experiment_aggregator');
      $data['experiments'] = $experiments->getActiveWithResults($tenantId);
    }
    catch (\Throwable $e) {
      $data['experiments'] = [];
    }
  }

  // Semana anterior para comparativa.
  if (\Drupal::hasService('jaraba_analytics.analytics_service')) {
    try {
      $analytics = \Drupal::service('jaraba_analytics.analytics_service');
      $data['previous_week'] = [
        'conversion_rate' => $analytics->getConversionRate($tenantId, 7, 7),
        'daily_metrics' => $analytics->getDailyMetrics($tenantId, 7, 7),
      ];
    }
    catch (\Throwable $e) {
      $data['previous_week'] = [];
    }
  }

  return $data;
}
```

### 6.3 Auto-apply AB Winner cuando Confianza > 95%

```php
/**
 * Auto-aplica ganadores de A/B tests con salvaguardas.
 *
 * AGENT-AUTOAPPLY-001: Solo confianza > 95% y min 100 exposiciones.
 * AGENT-LIMIT-001: Max 1 auto-aplicacion por semana por tenant.
 * AGENT-REVERT-001: Snapshot pre-aplicacion.
 */
protected function autoApplyWinners(string $tenantId): array {
  $applied = [];

  // Verificar limite semanal.
  $lastAutoApply = $this->state->get("conversion_agent.last_auto_apply.{$tenantId}", 0);
  if ((time() - $lastAutoApply) < 604800) { // 7 dias.
    return $applied;
  }

  if (!\Drupal::hasService('jaraba_ab_testing.experiment_orchestrator')) {
    return $applied;
  }

  try {
    $orchestrator = \Drupal::service('jaraba_ab_testing.experiment_orchestrator');
    $statistical = \Drupal::service('jaraba_ab_testing.statistical_engine');

    $experiments = $this->entityTypeManager
      ->getStorage('ab_experiment')
      ->loadByProperties([
        'tenant_id' => $tenantId,
        'status' => 'running',
      ]);

    foreach ($experiments as $experiment) {
      $results = $statistical->analyzeExperiment($experiment);

      if (!$results || !isset($results['confidence'])) {
        continue;
      }

      // Verificar salvaguardas.
      $confidence = (float) $results['confidence'];
      $totalExposures = (int) ($results['total_exposures'] ?? 0);

      if ($confidence >= 0.95 && $totalExposures >= 100 && isset($results['winner_variant_id'])) {
        // Crear snapshot pre-aplicacion (AGENT-REVERT-001).
        $snapshot = [
          'experiment_id' => $experiment->id(),
          'pre_state' => $experiment->toArray(),
          'timestamp' => time(),
          'agent' => 'conversion_optimization',
        ];
        $this->state->set(
          "conversion_agent.snapshot.{$experiment->id()}",
          json_encode($snapshot, JSON_UNESCAPED_UNICODE)
        );

        // Aplicar ganador via orchestrator.
        $orchestrator->applyWinner($experiment, $results['winner_variant_id']);

        $applied[] = [
          'experiment_id' => $experiment->id(),
          'experiment_name' => $experiment->label(),
          'winner_variant_id' => $results['winner_variant_id'],
          'confidence' => $confidence,
          'exposures' => $totalExposures,
        ];

        // Registrar audit trail (AGENT-AUDIT-001).
        $this->logger->info(
          'Auto-applied AB winner: experiment @eid, variant @vid, confidence @conf',
          [
            '@eid' => $experiment->id(),
            '@vid' => $results['winner_variant_id'],
            '@conf' => round($confidence * 100, 1) . '%',
          ]
        );

        // Solo 1 auto-aplicacion por ejecucion (AGENT-LIMIT-001).
        $this->state->set("conversion_agent.last_auto_apply.{$tenantId}", time());
        break;
      }
    }
  }
  catch (\Throwable $e) {
    $this->logger->error('Auto-apply failed: @msg', ['@msg' => $e->getMessage()]);
  }

  return $applied;
}
```

### 6.4 Cron Semanal: Ejecutar Agente + Enviar Resumen

**En `jaraba_ai_agents.module` hook_cron():**

```php
/**
 * Ejecuta el agente de conversion semanalmente (lunes).
 *
 * SPEC-ANA-005: Cron semanal.
 * SUPERVISOR-SLEEP-001: Via QueueWorker, no hot-loop.
 */
function jaraba_ai_agents_cron_conversion_agent(): void {
  $state = \Drupal::state();

  // Solo lunes (1 = Monday en date('N')).
  if (date('N') !== '1') {
    return;
  }

  // Respetar intervalo semanal.
  $lastRun = (int) $state->get('conversion_agent.last_cron', 0);
  if ((time() - $lastRun) < 518400) { // 6 dias para margen.
    return;
  }

  if (!$state->get('jaraba_conversion_agent.enabled', TRUE)) {
    return;
  }

  $state->set('conversion_agent.last_cron', time());

  // Encolar para cada tenant activo via QueueWorker.
  $queue = \Drupal::queue('conversion_optimization_agent');
  try {
    $tenants = \Drupal::entityTypeManager()
      ->getStorage('group')
      ->loadByProperties(['status' => 1]);

    foreach ($tenants as $tenant) {
      $queue->createItem([
        'tenant_id' => $tenant->id(),
        'triggered_by' => 'cron_weekly',
      ]);
    }
  }
  catch (\Throwable $e) {
    \Drupal::logger('jaraba_ai_agents')->error(
      'Conversion agent cron failed: @msg',
      ['@msg' => $e->getMessage()]
    );
  }
}
```

**Email de resumen semanal:**

```php
protected function sendWeeklySummary(array $result, string $tenantId): void {
  if (empty($result['recommendations']) && empty($result['auto_applied'])) {
    return; // Nada que reportar.
  }

  try {
    $tenantAdmin = $this->getTenantAdminEmail($tenantId);
    if (!$tenantAdmin) {
      return;
    }

    $mailManager = \Drupal::service('plugin.manager.mail');
    $params = [
      'subject' => 'Resumen semanal de conversion - ' . date('d/m/Y'),
      'analysis' => $result['analysis'],
      'recommendations' => $result['recommendations'],
      'auto_applied' => $result['auto_applied'],
      'dashboard_url' => '/mi-analytics?tab=insights',
    ];

    $mailManager->mail(
      'jaraba_ai_agents',
      'conversion_weekly_summary',
      $tenantAdmin,
      'es',
      $params
    );
  }
  catch (\Throwable $e) {
    $this->logger->warning('Weekly summary email failed: @msg', ['@msg' => $e->getMessage()]);
  }
}
```

---

## 7. Sprint N: Embudo Visual + Alertas

**Duracion estimada:** 2-3 dias
**Specs:** SPEC-ANA-002, SPEC-ANA-007, SPEC-ANA-010

### 7.1 Componente Embudo Visual con Pasos Configurables

**Fichero:** `web/modules/custom/jaraba_analytics/js/funnel-visual.js`

```javascript
/**
 * @file
 * Componente visual de embudo de conversion.
 *
 * SPEC-ANA-002: Barras horizontales decrecientes con % conversion/drop-off.
 * Consume datos de FunnelApiController via drupalSettings.
 *
 * Patron: Drupal.behaviors, vanilla JS, NO frameworks.
 * URLs API via drupalSettings (ROUTE-LANGPREFIX-001).
 * XSS: Drupal.checkPlain() para datos de API (INNERHTML-XSS-001).
 */
(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.funnelVisual = {
    attach: function (context) {
      once('funnel-visual', '.analytics-hub__funnel-container', context)
        .forEach(function (container) {
          var funnelId = container.dataset.funnelId;
          if (!funnelId) return;

          var apiBase = (drupalSettings.analyticsHub || {}).apiBase || '/api/v1/analytics';
          var url = apiBase + '/funnels/' + encodeURIComponent(funnelId) + '/calculate';

          fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
          })
          .then(function (response) { return response.json(); })
          .then(function (data) {
            if (data.steps) {
              renderFunnel(container, data.steps);
            }
          })
          .catch(function (err) {
            container.textContent = Drupal.t('Error al cargar datos del embudo.');
          });
        });
    }
  };

  function renderFunnel(container, steps) {
    var maxVisitors = steps[0] ? steps[0].visitors : 1;
    var html = '';

    steps.forEach(function (step, index) {
      var widthPct = Math.max(10, (step.visitors / maxVisitors) * 100);
      var conversionPct = index > 0
        ? ((step.visitors / steps[index - 1].visitors) * 100).toFixed(1)
        : '100.0';
      var dropOffPct = index > 0
        ? (100 - parseFloat(conversionPct)).toFixed(1)
        : '0.0';

      // Colores gradientes del azul al naranja.
      var hue = 210 - (index * (210 - 25) / Math.max(steps.length - 1, 1));

      html += '<div class="funnel-step" style="--funnel-width: ' + widthPct + '%; --funnel-hue: ' + hue + '">';
      html += '<div class="funnel-step__bar" style="width: ' + widthPct + '%">';
      html += '<span class="funnel-step__label">' + Drupal.checkPlain(step.name) + '</span>';
      html += '<span class="funnel-step__count">' + step.visitors.toLocaleString() + '</span>';
      html += '</div>';
      html += '<div class="funnel-step__meta">';
      html += '<span class="funnel-step__conversion">' + conversionPct + '% ' + Drupal.t('conversion') + '</span>';
      if (index > 0) {
        html += '<span class="funnel-step__dropoff">' + dropOffPct + '% ' + Drupal.t('abandono') + '</span>';
      }
      html += '</div>';
      html += '</div>';
    });

    container.innerHTML = html;
  }

})(Drupal, drupalSettings, once);
```

**SCSS del embudo (dentro de `_mi-analytics.scss`):**

```scss
.funnel-step {
  margin-bottom: var(--ej-spacing-sm, 0.5rem);

  &__bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 48px;
    padding: 0 var(--ej-spacing-md, 1rem);
    border-radius: var(--ej-radius-md, 8px);
    background: color-mix(in srgb, var(--ej-azul-corporativo, #233D63) 80%, var(--ej-naranja-impulso, #FF8C42) calc(var(--funnel-hue, 210) / 210 * 100%));
    color: var(--ej-text-on-primary, #fff);
    font-weight: 600;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
  }

  &__meta {
    display: flex;
    gap: var(--ej-spacing-md, 1rem);
    padding: var(--ej-spacing-xs, 0.25rem) var(--ej-spacing-md, 1rem);
    font-size: 0.85rem;
  }

  &__conversion {
    color: var(--ej-verde-innovacion, #00A9A5);
    font-weight: 500;
  }

  &__dropoff {
    color: var(--ej-text-muted, #6b7280);
  }
}
```

### 7.2 Alertas en Tiempo Real via EiMultichannelNotificationService

**Arquitectura:**

El sistema de alertas opera en 3 niveles:

| Nivel | Frecuencia | Deteccion | Canal |
|---|---|---|---|
| Critico | Cada request (muestra 1%) | KillCriteriaAlertService (existente) | Email inmediato + in-app |
| Alto | Cada 6h (ProactiveInsights cron) | ConversionInsightsService::detectAnomalies() | Email + in-app |
| Informativo | Semanal (agente) | ConversionOptimizationAgent::doExecute() | Email resumen |

**Extension de KillCriteriaAlertService con umbrales de analytics:**

```php
/**
 * Nuevos criterios kill para analytics (SPEC-ANA-007).
 */
protected function getAnalyticsKillCriteria(): array {
  return [
    'zero_traffic_24h' => [
      'label' => 'Trafico cero en 24h',
      'check' => function (string $tenantId): bool {
        // Si analytics_daily del ultimo dia tiene 0 page_views.
        return $this->hasZeroTraffic($tenantId, 1);
      },
      'severity' => 'critical',
      'message' => 'No se ha registrado trafico en las ultimas 24 horas.',
    ],
    'conversion_collapse' => [
      'label' => 'Colapso de conversion (< 25% de media)',
      'check' => function (string $tenantId): bool {
        return $this->isConversionCollapsed($tenantId);
      },
      'severity' => 'critical',
      'message' => 'La tasa de conversion ha caido por debajo del 25% de la media.',
    ],
    'error_rate_spike' => [
      'label' => 'Pico de errores (> 5% requests)',
      'check' => function (string $tenantId): bool {
        return $this->hasErrorRateSpike($tenantId);
      },
      'severity' => 'high',
      'message' => 'La tasa de errores supera el 5% de las peticiones.',
    ],
  ];
}
```

**Widget de alertas activas en el dashboard hub:**

```twig
{# _analytics-alerts-widget.html.twig #}
{% if active_alerts is not empty %}
<div class="analytics-hub__alerts">
  <h3 class="analytics-hub__alerts-title">
    {{ jaraba_icon('status', 'alert-triangle', { variant: 'duotone', color: 'naranja-impulso', size: '20px' }) }}
    {% trans %}Alertas activas{% endtrans %}
  </h3>
  {% for alert in active_alerts %}
  <div class="analytics-hub__alert analytics-hub__alert--{{ alert.severity }}">
    <strong>{{ alert.title }}</strong>
    <p>{{ alert.description }}</p>
    <time datetime="{{ alert.created }}">{{ alert.created|date('d/m/Y H:i') }}</time>
  </div>
  {% endfor %}
</div>
{% endif %}
```

### 7.3 Widget de Analytics en Daily Actions del Admin

**Fichero:** Nuevo tagged service para DailyActionsRegistry.

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\DailyAction;

use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: resumen de analytics del dia.
 *
 * SPEC-ANA-009: Widget de analytics en daily actions.
 * SETUP-WIZARD-DAILY-001: Tagged service via CompilerPass.
 */
class AnalyticsSummaryDailyAction implements DailyActionInterface {

  public function getId(): string {
    return 'analytics_summary';
  }

  public function getTitle(): string {
    return (string) t('Resumen de Analytics');
  }

  public function getDescription(): string {
    return (string) t('Revisa tus metricas clave de hoy');
  }

  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart-bar'];
  }

  public function getColor(): string {
    return 'corporativo';
  }

  public function getWeight(): int {
    return 15; // Prioridad alta en daily actions.
  }

  public function getRoute(): string {
    return 'jaraba_analytics.hub_dashboard';
  }

  public function getApplicableVerticals(): array {
    // Disponible para todas las verticales comerciales.
    return [
      'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
      'jarabalex', 'serviciosconecta', 'formacion', 'demo',
    ];
  }

  public function isCompleted(string $contextId): bool {
    // Se considera "completado" si el admin visito /mi-analytics hoy.
    $lastVisit = \Drupal::state()->get("analytics.last_hub_visit.{$contextId}", 0);
    return (time() - $lastVisit) < 86400;
  }

  public function getBadgeCount(string $contextId): int {
    // Numero de alertas activas sin resolver.
    if (\Drupal::hasService('jaraba_analytics.conversion_insights')) {
      try {
        $insights = \Drupal::service('jaraba_analytics.conversion_insights');
        $anomalies = $insights->detectAnomalies($contextId, 1);
        return count($anomalies);
      }
      catch (\Throwable $e) {
        return 0;
      }
    }
    return 0;
  }

}
```

Registrar en `jaraba_analytics.services.yml` con tag `daily_action`.

---

## 8. Medidas de Salvaguarda

### 8.1 Salvaguardas de Multi-Tenancy

| ID | Regla | Donde se aplica | Validacion |
|---|---|---|---|
| ANALYTICS-TENANT-001 | TODA query filtra por tenant_id | Todos los servicios de analytics | `validate-tenant-isolation.php` |
| ANALYTICS-TENANT-002 | Dashboard hub SOLO muestra datos del tenant actual | AnalyticsHubController + preprocess | TenantContextService assertion |
| ANALYTICS-TENANT-003 | API endpoints verifican tenant match | AnalyticsApiController, FunnelApiController | AccessControlHandler + tenant check |

### 8.2 Salvaguardas de IA

| ID | Regla | Donde se aplica |
|---|---|---|
| AGENT-AUTOAPPLY-001 | Auto-apply SOLO con confianza > 95% y min 100 exposiciones | ConversionOptimizationAgent::autoApplyWinners() |
| AGENT-LIMIT-001 | Max 1 auto-aplicacion por semana por tenant | State check en autoApplyWinners() |
| AGENT-REVERT-001 | Snapshot pre-aplicacion almacenado en State API | autoApplyWinners() antes de aplicar |
| AGENT-AUDIT-001 | Toda decision en logger con datos justificativos | Cada paso del agente |
| AGENT-KILL-001 | Kill switch global via State `jaraba_conversion_agent.enabled` | doExecute() entrada |
| AI-GUARDRAILS-PII-001 | Datos analytics NUNCA incluyen PII en prompts LLM | gatherData() anonimiza |

### 8.3 Salvaguardas de Rendimiento

| ID | Regla | Implementacion |
|---|---|---|
| ANALYTICS-CACHE-001 | Dashboard KPIs cacheados 60s | CacheBackendInterface + tags |
| ANALYTICS-AGGREGATE-001 | Hub usa analytics_daily, NUNCA raw analytics_event | AnalyticsDataService |
| ANALYTICS-QUEUE-001 | Procesamiento pesado via QueueWorker | DailyAggregationWorker, ConversionAgentWorker |
| ANALYTICS-BATCH-001 | Eventos frontend via sendBeacon (non-blocking) | funnel-analytics.js (existente) |
| ANALYTICS-LIMIT-001 | API rate limit 100 requests/min por tenant | Middleware en routing |

### 8.4 Salvaguardas de Seguridad

| ID | Regla | Implementacion |
|---|---|---|
| ANALYTICS-XSS-001 | Datos API sanitizados con Drupal.checkPlain() | funnel-visual.js, analytics-hub.js |
| ANALYTICS-CSRF-001 | Endpoints mutacion con _csrf_request_header_token | routing.yml (existente) |
| ANALYTICS-PERM-001 | Permiso `access jaraba analytics` requerido | routing.yml requirements |
| ANALYTICS-CONSENT-001 | Tracking respeta GDPR consent | ConsentService.check() |

### 8.5 Salvaguardas de Integridad de Datos

| ID | Regla | Implementacion |
|---|---|---|
| ANALYTICS-DNT-001 | Respetar Do Not Track header | funnel-analytics.js (existente) |
| ANALYTICS-RETENTION-001 | Datos raw > 90 dias eliminados | Cron de limpieza |
| ANALYTICS-IDEMPOTENT-001 | Agregacion diaria idempotente | DailyAggregationWorker check duplicados |

---

## 9. Tabla de Correspondencia Specs

| Spec ID | Titulo | Sprint | Ficheros principales | Dependencias |
|---|---|---|---|---|
| SPEC-ANA-001 | Dashboard Analytics Hub | K | AnalyticsHubController.php, analytics-hub.html.twig, _mi-analytics.scss, analytics-hub.js | jaraba_analytics (existente) |
| SPEC-ANA-002 | Embudo Visual Interactivo | N | funnel-visual.js, _analytics-funnel.html.twig | FunnelApiController (existente) |
| SPEC-ANA-003 | IA Proactiva Conversion | L | ConversionInsightsService.php, ProactiveInsightsService extension | jaraba_analytics, jaraba_ai_agents |
| SPEC-ANA-004 | Copilot Analytics Bridge | L | CopilotBridgeService extensions (10 verticales) | jaraba_copilot_v2, CausalAnalyticsService |
| SPEC-ANA-005 | Agente Autonomo Conversion | M | ConversionOptimizationAgent.php, QueueWorker | jaraba_ai_agents, jaraba_analytics, jaraba_ab_testing |
| SPEC-ANA-006 | Auto-Apply AB Winner | M | ExperimentOrchestratorService extension | jaraba_ab_testing, StatisticalEngineService |
| SPEC-ANA-007 | Alertas Anomalias | N | KillCriteriaAlertService extension, EiMultichannelNotificationService | jaraba_analytics, notificaciones |
| SPEC-ANA-008 | Recomendaciones CTA | L | CtaRecommendationService.php | jaraba_analytics, analytics_event data |
| SPEC-ANA-009 | Widget Daily Actions | K | AnalyticsSummaryDailyAction.php, AnalyticsDashboardProfileSection.php | ecosistema_jaraba_core |
| SPEC-ANA-010 | Reportes Hub Accesibles | N | AnalyticsExportController (existente), slide-panel integracion | jaraba_analytics |

---

## 10. Tabla de Cumplimiento Directrices

| # | Directriz | Cumplimiento | Detalle |
|---|---|---|---|
| 1 | ZERO-REGION-001 | SI | Dashboard usa clean_content, datos via preprocess |
| 2 | ZERO-REGION-002 | SI | No se pasan entity objects como non-# keys |
| 3 | ZERO-REGION-003 | SI | drupalSettings via $variables['#attached'] en preprocess |
| 4 | CSS-VAR-ALL-COLORS-001 | SI | Todos los colores via var(--ej-*) con fallback |
| 5 | ICON-CONVENTION-001 | SI | jaraba_icon() con categoria analytics, variante duotone |
| 6 | ICON-DUOTONE-001 | SI | Variante default duotone para todos los iconos |
| 7 | TENANT-001 | SI | Toda query filtra por tenant_id |
| 8 | TENANT-002 | SI | tenant_id via ecosistema_jaraba_core.tenant_context |
| 9 | OPTIONAL-CROSSMODULE-001 | SI | @? para jaraba_ab_testing, jaraba_ai_agents, jaraba_heatmap |
| 10 | AGENT-GEN2-PATTERN-001 | SI | ConversionOptimizationAgent extiende SmartBaseAgent |
| 11 | SMART-AGENT-CONSTRUCTOR-001 | SI | 10 args (6 core + 4 optional) |
| 12 | MODEL-ROUTING-CONFIG-001 | SI | balanced para analisis, premium para recomendaciones |
| 13 | TWIG-INCLUDE-ONLY-001 | SI | Parciales con {% include ... only %} |
| 14 | ROUTE-LANGPREFIX-001 | SI | URLs via Url::fromRoute() y drupalSettings |
| 15 | INNERHTML-XSS-001 | SI | Drupal.checkPlain() en funnel-visual.js |
| 16 | CSRF-API-001 | SI | _csrf_request_header_token en endpoints mutacion |
| 17 | PREMIUM-FORMS-PATTERN-001 | SI | Forms de configuracion extienden PremiumEntityFormBase |
| 18 | SLIDE-PANEL-RENDER-002 | SI | Detalle insight/experimento en slide-panel con _controller: |
| 19 | CONTROLLER-READONLY-001 | SI | No readonly en properties heredadas de ControllerBase |
| 20 | PRESAVE-RESILIENCE-001 | SI | hasService() + try-catch en servicios opcionales |
| 21 | SCSS-COMPILE-VERIFY-001 | SI | Verificar timestamp CSS > SCSS tras compilar |
| 22 | SCSS-001 | SI | @use '../variables' as * en cada parcial |
| 23 | SCSS-COLORMIX-001 | SI | color-mix() para transparencias |
| 24 | TWIG-URL-RENDER-ARRAY-001 | SI | url() solo dentro de {{ }}, no concatenado |
| 25 | SUPERVISOR-SLEEP-001 | SI | Agente via QueueWorker, no hot-loop |
| 26 | LABEL-NULLSAFE-001 | SI | ->label() ?? fallback en displays |
| 27 | ANALYTICS-CONSENT-001 | SI | ConsentService check antes de tracking |
| 28 | AI-GUARDRAILS-PII-001 | SI | No PII en prompts LLM |
| 29 | LCIS-AUDIT-001 | SI | Audit trail en decisiones del agente |
| 30 | SETUP-WIZARD-DAILY-001 | SI | DailyAction tagged service via CompilerPass |
| 31 | CASCADE-SEARCH-001 | SI | Agente usa N1-N4 cascade para datos |
| 32 | UPDATE-HOOK-CATCH-001 | SI | \Throwable en try-catch de hooks |
| 33 | PIPELINE-E2E-001 | SI | Verificacion 4 capas Service-Controller-hook_theme-Template |
| 34 | FUNNEL-COMPLETENESS-001 | SI | CTAs con data-track-cta + data-track-position |

---

## 11. Verificacion Post-Implementacion

### 11.1 RUNTIME-VERIFY-001

| Capa | Verificacion | Comando/Metodo |
|---|---|---|
| PHP | Servicios registrados y resolvibles | `drush ev "var_dump(\Drupal::hasService('jaraba_analytics.conversion_insights'))"` |
| PHP | Ruta /mi-analytics accesible (200) | `curl -s -o /dev/null -w "%{http_code}" https://jaraba-saas.lndo.site/es/mi-analytics` |
| Twig | Template renderiza sin WSOD | Navegar a /mi-analytics en browser |
| SCSS | CSS compilado timestamp > SCSS | `stat web/themes/.../css/routes/mi-analytics.css` vs `stat web/themes/.../scss/routes/_mi-analytics.scss` |
| JS | drupalSettings.analyticsHub presente | Inspeccionar drupalSettings en consola browser |
| JS | Chart.js renderiza graficos | Visual check de graficos en tab overview |
| JS | funnel-visual.js renderiza barras | Visual check en tab funnel |
| DB | analytics_event tiene datos recientes | `drush sqlq "SELECT COUNT(*) FROM analytics_event WHERE created > UNIX_TIMESTAMP() - 86400"` |
| Cron | ProactiveInsights genera insights | `drush ev "print_r(\Drupal::state()->get('jaraba_proactive_insights.last_run'))"` |
| Cron | Agente conversion encolado | `drush queue:list` buscando conversion_optimization_agent |

### 11.2 PIPELINE-E2E-001

| Capa | Componente | Verificacion |
|---|---|---|
| L1 Service | ConversionInsightsService | Inyectado en AnalyticsHubController via DI |
| L1 Service | ConversionOptimizationAgent | Registrado en services.yml, resolvible |
| L2 Controller | AnalyticsHubController::dashboard() | Devuelve render array con library attached |
| L3 hook_theme | jaraba_analytics_theme() | Declara variables analytics_hub con todos los campos |
| L4 Template | analytics-hub.html.twig | Incluye 5 parciales con only, textos trans |
| L4 Template | page--mi-analytics.html.twig | Layout Zero Region con clean_content |
| L5 SCSS | _mi-analytics.scss | Compilado, var(--ej-*), responsive |
| L6 JS | analytics-hub.js | Drupal.behaviors, Chart.js inicializado |
| L7 drupalSettings | analyticsHub.apiBase | Presente en DOM via preprocess |

### 11.3 IMPLEMENTATION-CHECKLIST-001

- [ ] Servicios registrados en services.yml Y consumidos por controller/preprocess
- [ ] Rutas en routing.yml apuntan a clases/metodos existentes
- [ ] AccessControlHandler verifica tenant match (TENANT-ISOLATION-ACCESS-001)
- [ ] hook_theme() declara TODAS las variables usadas en templates
- [ ] SCSS compilado, library registrada, hook_page_attachments_alter activo
- [ ] Tests unitarios para ConversionInsightsService y ConversionOptimizationAgent
- [ ] Tests kernel para nueva entity si aplica
- [ ] Config export si nuevas config entities
- [ ] Validadores: `php scripts/validation/validate-entity-integrity.php`
- [ ] Validadores: `php scripts/validation/validate-tenant-isolation.php`
- [ ] Validadores: `php scripts/validation/validate-optional-deps.php`
- [ ] Validadores: `php scripts/validation/validate-phantom-args.php`
- [ ] Pre-commit hooks pasan sin errores

### 11.4 Criterios de Aceptacion por Sprint

**Sprint K:**
- [ ] /mi-analytics renderiza con layout Zero Region
- [ ] 5 tabs navegables con URL params
- [ ] KPI cards muestran datos reales del tenant
- [ ] Grafico de tendencias con Chart.js funcional
- [ ] UserProfileSection visible en hub
- [ ] DailyAction muestra badge con alertas pendientes

**Sprint L:**
- [ ] ConversionInsightsService detecta anomalias con datos reales
- [ ] Copilot responde "como van mis conversiones" con datos del tenant
- [ ] ProactiveInsightsService genera los 3 nuevos tipos de insight
- [ ] Admin recibe notificacion cuando hay anomalia alta

**Sprint M:**
- [ ] ConversionOptimizationAgent ejecuta semanalmente via cron
- [ ] Genera reporte con recomendaciones LLM
- [ ] Auto-aplica ganador AB con confianza > 95% (verificar con test)
- [ ] Email resumen semanal recibido por admin

**Sprint N:**
- [ ] Embudo visual renderiza barras con datos reales
- [ ] Alertas en tiempo real notifican al admin
- [ ] Widget de daily actions muestra resumen analytics
- [ ] Exportacion CSV/JSON funciona desde hub

---

## 12. Glosario

| Sigla | Significado |
|---|---|
| AB | A/B Testing — Experimentacion con variantes para comparar rendimiento |
| API | Application Programming Interface — Interfaz de programacion de aplicaciones |
| CAPI | Conversions API — API server-side de Meta/LinkedIn/TikTok |
| CRM | Customer Relationship Management — Gestion de relaciones con clientes |
| CSRF | Cross-Site Request Forgery — Falsificacion de peticiones entre sitios |
| CSS | Cascading Style Sheets — Hojas de estilo en cascada |
| CTA | Call To Action — Llamada a la accion |
| CSV | Comma-Separated Values — Formato de exportacion tabular |
| CTR | Click-Through Rate — Tasa de clics sobre impresiones |
| D7/D30 | Day 7 / Day 30 — Metricas de retencion a 7 y 30 dias |
| DI | Dependency Injection — Inyeccion de dependencias |
| DNT | Do Not Track — Preferencia de no-rastreo del navegador |
| DOM | Document Object Model — Modelo de objetos del documento |
| GA4 | Google Analytics 4 — Plataforma de analytics de Google |
| GDPR | General Data Protection Regulation — Reglamento de Proteccion de Datos |
| HTML | HyperText Markup Language — Lenguaje de marcado web |
| HTTP | HyperText Transfer Protocol — Protocolo de transferencia web |
| IA | Inteligencia Artificial |
| IQR | Interquartile Range — Rango intercuartilico para deteccion de anomalias |
| JS | JavaScript — Lenguaje de programacion del navegador |
| JSON | JavaScript Object Notation — Formato de intercambio de datos |
| KPI | Key Performance Indicator — Indicador clave de rendimiento |
| LCIS | Legal Coherence Intelligence System — Sistema de coherencia legal |
| LLM | Large Language Model — Modelo de lenguaje grande |
| MRR | Monthly Recurring Revenue — Ingreso mensual recurrente |
| NPS | Net Promoter Score — Indice de satisfaccion del cliente |
| PHP | PHP: Hypertext Preprocessor — Lenguaje server-side |
| PII | Personally Identifiable Information — Datos de identificacion personal |
| REST | Representational State Transfer — Arquitectura de APIs web |
| SaaS | Software as a Service — Software como servicio |
| SCSS | Sassy CSS — Preprocesador CSS |
| SEO | Search Engine Optimization — Optimizacion para buscadores |
| SMTP | Simple Mail Transfer Protocol — Protocolo de correo electronico |
| SQL | Structured Query Language — Lenguaje de consultas a bases de datos |
| SSOT | Single Source of Truth — Fuente unica de verdad |
| SVG | Scalable Vector Graphics — Graficos vectoriales escalables |
| UI | User Interface — Interfaz de usuario |
| URL | Uniform Resource Locator — Direccion web |
| UTM | Urchin Tracking Module — Parametros de tracking de campanas |
| UUID | Universally Unique Identifier — Identificador unico universal |
| WSOD | White Screen of Death — Pantalla blanca por error critico de PHP |
| XSS | Cross-Site Scripting — Inyeccion de scripts maliciosos |
