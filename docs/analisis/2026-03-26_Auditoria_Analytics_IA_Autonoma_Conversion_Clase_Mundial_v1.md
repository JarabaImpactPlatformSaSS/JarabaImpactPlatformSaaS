# Auditoria: Analytics Integrado, IA Autonoma y Optimizacion de Conversion

| Campo | Valor |
|---|---|
| Version | 1.0.0 |
| Fecha | 2026-03-26 |
| Autor | Claude Opus 4.6 (1M context) |
| Score actual | 4/10 |
| Score objetivo | 10/10 |
| Modulos auditados | jaraba_analytics, jaraba_ab_testing, jaraba_pixels, jaraba_heatmap, jaraba_copilot_v2, jaraba_ai_agents, jaraba_customer_success, ecosistema_jaraba_core |

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Inventario de lo que YA EXISTE](#2-inventario-de-lo-que-ya-existe)
3. [Gap "Codigo existe vs Usuario experimenta"](#3-gap-codigo-existe-vs-usuario-experimenta)
4. [Lo que FALTA para 10/10](#4-lo-que-falta-para-1010)
5. [Arquitectura Propuesta: Analytics Hub Integrado](#5-arquitectura-propuesta-analytics-hub-integrado)
6. [Arquitectura Propuesta: Agente IA Autonomo de Conversion](#6-arquitectura-propuesta-agente-ia-autonomo-de-conversion)
7. [Score por Dimension (15 criterios)](#7-score-por-dimension-15-criterios)
8. [Medidas de Salvaguarda](#8-medidas-de-salvaguarda)
9. [Tabla de Correspondencia Specs](#9-tabla-de-correspondencia-specs)
10. [Glosario](#10-glosario)

---

## 1. Resumen Ejecutivo

**Score actual: 4/10.** La plataforma Jaraba Impact Platform dispone de una infraestructura de analytics sorprendentemente completa a nivel de backend: 8 modulos custom, 67+ ficheros PHP, 12+ entities, 30+ servicios, 9 templates Twig y 25+ rutas API. Sin embargo, el administrador de un tenant NO puede acceder a esta informacion desde su hub. Debe salir a GA4, Meta Business Suite o la consola de Drupal admin para consultar metricas basicas.

**El problema central no es la falta de codigo, sino la desconexion entre el backend y la experiencia del usuario final.** Los datos se recogen (analytics_event, copilot_funnel_event, tracking_event), se agregan (AnalyticsAggregatorService, ProductMetricsAggregatorService) y se analizan (CausalAnalyticsService, StatisticalEngineService), pero todo permanece encapsulado en rutas de administracion Drupal (/admin/*) o en endpoints API sin consumidor frontend en el hub.

**Oportunidad critica:** Con el 80% del backend ya construido, el salto de 4/10 a 10/10 requiere fundamentalmente trabajo de integracion frontend + orquestacion IA, no nuevos servicios desde cero.

### Impacto de negocio del gap actual

- **Churn silencioso**: El admin no ve alertas de caida de trafico o conversion. Se entera cuando ya es tarde
- **Valor percibido bajo**: Un SaaS sin dashboard analytics interno se percibe como "basico" frente a competidores (Wix Analytics, Shopify Analytics, HubSpot)
- **Desaprovechamiento de IA**: CausalAnalyticsService y ProactiveInsightsService generan insights que NADIE lee porque no hay canal de entrega al admin
- **AB testing infrautilizado**: Motor estadistico completo (z-score, chi-cuadrado, intervalos de confianza) pero solo accesible via /admin/ab-testing

---

## 2. Inventario de lo que YA EXISTE

### 2.1 Modulo `jaraba_analytics` — Motor Central

| Componente | Tipo | Ubicacion | Estado |
|---|---|---|---|
| AnalyticsEvent | ContentEntity | `src/Entity/AnalyticsEvent.php` | Funcional. Tabla analytics_event. Almacena eventos individuales con event_type, session_id, url, referrer, UTM |
| AnalyticsDaily | ContentEntity | `src/Entity/AnalyticsDaily.php` | Funcional. Agregacion diaria precalculada |
| FunnelDefinition | ContentEntity | `src/Entity/FunnelDefinition.php` | Funcional. Define pasos del funnel con matching secuencial |
| CohortDefinition | ContentEntity | `src/Entity/CohortDefinition.php` | Funcional. Define cohortes para analisis de retencion |
| CustomReport | ContentEntity | `src/Entity/CustomReport.php` | Funcional. Reportes personalizados |
| ScheduledReport | ContentEntity | `src/Entity/ScheduledReport.php` | Funcional. Reportes programados |
| DashboardWidget | ContentEntity | `src/Entity/DashboardWidget.php` | Funcional. Widgets configurables |
| AnalyticsDashboard | ContentEntity | `src/Entity/AnalyticsDashboard.php` | Funcional. Dashboards personalizados |
| ActivationCriteriaConfig | ConfigEntity | `src/Entity/ActivationCriteriaConfig.php` | Funcional. Criterios de activacion por vertical |
| ProductMetricSnapshot | ContentEntity | `src/Entity/ProductMetricSnapshot.php` | Funcional. Snapshots diarios de metricas |
| AnalyticsService | Service | `src/Service/AnalyticsService.php` | Funcional. Tracking + consulta + cache |
| AnalyticsAggregatorService | Service | `src/Service/AnalyticsAggregatorService.php` | Funcional. Agregacion analytics_event -> analytics_daily via cron |
| FunnelTrackingService | Service | `src/Service/FunnelTrackingService.php` | Funcional. Matching secuencial de eventos contra funnels |
| CohortAnalysisService | Service | `src/Service/CohortAnalysisService.php` | Funcional. Curvas de retencion por cohorte |
| RetentionCalculatorService | Service | `src/Service/RetentionCalculatorService.php` | Funcional. D7/D30 retencion |
| NpsSurveyService | Service | `src/Service/NpsSurveyService.php` | Funcional. NPS 0-10, frecuencia max 90 dias |
| ActivationTrackingService | Service | `src/Service/ActivationTrackingService.php` | Funcional. Criterios activacion por vertical |
| ProductMetricsAggregatorService | Service | `src/Service/ProductMetricsAggregatorService.php` | Funcional. Combina activacion+retencion+NPS+churn en snapshots |
| ReportExecutionService | Service | `src/Service/ReportExecutionService.php` | Funcional. Ejecuta reportes custom |
| ReportSchedulerService | Service | `src/Service/ReportSchedulerService.php` | Funcional. Programa ejecucion de reportes |
| DashboardManagerService | Service | `src/Service/DashboardManagerService.php` | Funcional. CRUD de dashboards |
| AnalyticsDataService | Service | `src/Service/AnalyticsDataService.php` | Funcional. Capa de acceso a datos |
| ConsentService | Service | `src/Service/ConsentService.php` | Funcional. Consentimiento GDPR |
| KillCriteriaAlertService | Service | `src/Service/KillCriteriaAlertService.php` | Funcional. Alertas de criterios criticos |
| GrantTrackingService | Service | `src/Service/GrantTrackingService.php` | Funcional. Tracking de subvenciones |
| InstitutionalReportService | Service | `src/Service/InstitutionalReportService.php` | Funcional. Reportes institucionales |
| DailyAggregationWorker | QueueWorker | `src/Plugin/QueueWorker/DailyAggregationWorker.php` | Funcional. Proceso cron de agregacion |
| AnalyticsDashboardController | Controller | `src/Controller/AnalyticsDashboardController.php` | Funcional. Ruta /admin/jaraba/analytics |
| AnalyticsApiController | Controller | `src/Controller/AnalyticsApiController.php` | Funcional. 6+ endpoints JSON API |
| FunnelApiController | Controller | `src/Controller/FunnelApiController.php` | Funcional. CRUD + calculo funnels via API |
| CohortApiController | Controller | `src/Controller/CohortApiController.php` | Funcional. CRUD + retencion cohortes via API |
| DashboardWidgetApiController | Controller | `src/Controller/DashboardWidgetApiController.php` | Funcional. Widgets API |
| DashboardBuilderController | Controller | `src/Controller/DashboardBuilderController.php` | Funcional. Constructor de dashboards |
| ReportApiController | Controller | `src/Controller/ReportApiController.php` | Funcional. Ejecucion de reportes via API |
| AnalyticsExportController | Controller | `src/Controller/AnalyticsExportController.php` | Funcional. Exportacion CSV/JSON |
| ScheduledReportController | Controller | `src/Controller/ScheduledReportController.php` | Funcional. Gestion reportes programados |
| PrePmfDashboardController | Controller | `src/Controller/PrePmfDashboardController.php` | Funcional. Dashboard pre-product-market-fit |
| ProgramDashboardController | Controller | `src/Controller/ProgramDashboardController.php` | Funcional. Dashboard de programa |

**Templates (9):**
- `analytics-dashboard.html.twig` — Dashboard principal
- `analytics-dashboard-builder.html.twig` — Constructor de dashboards
- `analytics-dashboard-list.html.twig` — Lista de dashboards
- `analytics-dashboard-view.html.twig` — Vista de dashboard individual
- `analytics-report-wizard.html.twig` — Asistente de reportes
- `analytics-scheduled-reports.html.twig` — Reportes programados
- `cohort-analysis.html.twig` — Analisis de cohortes
- `funnel-analysis.html.twig` — Analisis de embudos
- `programa-dashboard.html.twig` — Dashboard de programa

**Rutas API (25+):** Eventos, batch, dashboard KPIs, realtime, funnel, top pages, traffic sources, funnels CRUD, cohorts CRUD, reportes, widgets, exportacion.

### 2.2 Modulo `jaraba_ab_testing` — Motor de Experimentacion

| Componente | Tipo | Estado |
|---|---|---|
| ABExperiment | ContentEntity | Funcional. Experimentos con nombre, hipotesis, estado, fecha |
| ABVariant | ContentEntity | Funcional. Variantes con peso, cambios CSS/JS |
| ExperimentExposure | ContentEntity | Funcional. Registro de exposiciones por usuario |
| ExperimentResult | ContentEntity | Funcional. Resultados agregados por variante |
| StatisticalEngineService | Service | Funcional. Z-score, chi-cuadrado, intervalos confianza, tamano muestral |
| ExperimentAggregatorService | Service | Funcional. Agregacion de resultados |
| ExperimentOrchestratorService | Service | Funcional. Auto-evaluacion + declaracion de ganador + email |
| VariantAssignmentService | Service | Funcional. Asignacion aleatoria de variantes |
| ExposureTrackingService | Service | Funcional. Tracking de exposiciones |
| ResultCalculationService | Service | Funcional. Calculo de resultados |
| OnboardingExperimentService | Service | Funcional. Experimentos de onboarding |
| ABTestingDashboardController | Controller | Funcional. Solo en /admin/ab-testing |
| ABTestingApiController | Controller | Funcional. API REST |
| ABTestingProfileSection | UserProfileSection | Funcional. Seccion en perfil hub |

### 2.3 Modulo `jaraba_pixels` — Tracking Externo Multi-Plataforma

| Componente | Tipo | Estado |
|---|---|---|
| TrackingPixel | ContentEntity | Funcional. Configuracion de pixels por tenant |
| TrackingEvent | ContentEntity | Funcional. Eventos enviados a plataformas externas |
| ConsentRecord | ContentEntity | Funcional. Registro de consentimiento GDPR |
| PixelDispatcherService | Service | Funcional. Dispatch server-side a GA4, Meta CAPI, LinkedIn, TikTok |
| BatchProcessorService | Service | Funcional. Proceso en lote de eventos |
| ConsentManagementService | Service | Funcional. Gestion de consentimiento |
| PixelHealthCheckService | Service | Funcional. Verificacion de salud de pixels |
| GoogleMeasurementClient | Client | Funcional. GA4 Measurement Protocol v2 |
| MetaCapiClient | Client | Funcional. Meta Conversions API |
| LinkedInCapiClient | Client | Funcional. LinkedIn Conversions API |
| TikTokEventsClient | Client | Funcional. TikTok Events API |
| PixelStatsController | Controller | Funcional. Estadisticas de envio |
| AnalyticsPixelsProfileSection | UserProfileSection | Funcional. Seccion en perfil hub |

### 2.4 Modulo `jaraba_heatmap` — Mapas de Calor

| Componente | Tipo | Estado |
|---|---|---|
| HeatmapCollectorService | Service | Funcional. Recoleccion de datos click/scroll/move |
| HeatmapAggregatorService | Service | Funcional. Agregacion de datos |
| HeatmapScreenshotService | Service | Funcional. Capturas de pagina para overlay |
| HeatmapEventProcessor | QueueWorker | Funcional. Procesamiento asincrono |
| HeatmapDashboardController | Controller | Funcional. Dashboard de heatmaps |
| HeatmapApiController | Controller | Funcional. API de datos |
| HeatmapCollectorController | Controller | Funcional. Endpoint de recoleccion |

### 2.5 Modulo `jaraba_ai_agents` — IA Analitica

| Componente | Tipo | Estado |
|---|---|---|
| CausalAnalyticsService | Service | Funcional. 4 tipos de consulta LLM: diagnostic, counterfactual, predictive, prescriptive |
| ProactiveInsightsService | Service | Funcional. Cron 6h, 5 tipos insight: usage_anomaly, churn_risk, content_gap, seo_opportunity, quota_warning |
| CausalAnalyticsDashboardController | Controller | Funcional. Solo en ruta admin |
| PromptExperimentService | Service | Funcional. Experimentacion de prompts |

### 2.6 Modulo `jaraba_copilot_v2` — Tracking Embudo Copilot

| Componente | Tipo | Estado |
|---|---|---|
| CopilotFunnelTrackingService | Service | Funcional. Tabla copilot_funnel_event, 8 tipos evento |
| CopilotAnalyticsController | Controller | Funcional. Dashboard admin de uso del copilot |
| copilot-analytics-dashboard.html.twig | Template | Funcional. Solo admin |

### 2.7 Modulo `ecosistema_jaraba_core` — Analytics Tenant

| Componente | Tipo | Estado |
|---|---|---|
| TenantAnalyticsService | Service | Funcional. Sales trend, MRR, customers, top products |
| TenantAnalyticsController | Controller | Funcional. Dashboard + API de tendencias |
| AdminCenterAnalyticsService | Service | Funcional. Analytics del admin center |
| AnalyticsEventController | Controller | Funcional. Endpoint para eventos |

### 2.8 Modulo `jaraba_page_builder` — Analytics de Paginas

| Componente | Tipo | Estado |
|---|---|---|
| ExternalAnalyticsService | Service | Funcional. GA4 Measurement Protocol + Search Console API |
| ExperimentService | Service | Funcional. Experimentos A/B de paginas |
| AnalyticsDashboardController | Controller | Funcional. Dashboard especifico de Page Builder |

### 2.9 Modulo `jaraba_customer_success` — Metricas de Exito

| Componente | Tipo | Estado |
|---|---|---|
| EngagementScoringService | Service | Funcional. Score de engagement basado en analytics_event |
| ChurnPredictionService | Service | Funcional. Prediccion de churn |
| HealthScoreCalculatorService | Service | Funcional. Score de salud del tenant |
| VerticalRetentionService | Service | Funcional. Retencion por vertical |

### 2.10 Frontend JavaScript

| Componente | Ubicacion | Estado |
|---|---|---|
| funnel-analytics.js | `ecosistema_jaraba_theme/js/funnel-analytics.js` | Funcional. Tracking CTA, forms, time-on-page, demo tabs via sendBeacon |

### 2.11 Resumen Cuantitativo

| Metrica | Valor |
|---|---|
| Modulos involucrados | 8 |
| Entities (Content + Config) | 15+ |
| Services | 35+ |
| Controllers | 18+ |
| Templates Twig | 12+ |
| Rutas API | 25+ |
| Tests unitarios | 6+ ficheros |
| Clientes externos | 4 (GA4, Meta, LinkedIn, TikTok) |
| QueueWorkers | 2 (DailyAggregation, HeatmapEvent) |

---

## 3. Gap "Codigo existe vs Usuario experimenta"

Este es el gap fundamental. Todo el inventario de la seccion 2 existe y funciona en backend, pero el administrador del tenant NO lo experimenta porque falta la capa de integracion con el hub frontend.

### 3.1 Dashboard Analytics: EXISTE pero NO en el Hub

**Situacion actual:**
- `jaraba_analytics.dashboard` apunta a `/admin/jaraba/analytics` — ruta admin de Drupal, no accesible desde el hub del tenant
- `analytics-dashboard.html.twig` EXISTE pero se renderiza en contexto admin con toolbar, no en layout limpio de hub
- `TenantAnalyticsController::analyticsPage()` genera datos completos (sales, MRR, customers, AI tokens, usage limits) pero su ruta tampoco esta integrada en el hub
- NO existe ruta `/mi-analytics` ni equivalente en el hub del usuario

**Impacto:** El admin tiene que navegar a /admin/jaraba/analytics (si sabe que existe) para ver metricas basicas. La mayoria nunca descubre esta ruta.

### 3.2 CausalAnalyticsService: EXISTE pero NO Accesible

**Situacion actual:**
- El servicio funciona: acepta queries en lenguaje natural, las procesa con LLM premium (Opus), devuelve factores causales + recomendaciones + confianza
- `CausalAnalyticsDashboardController` existe pero su ruta esta en zona admin
- El Copilot NO tiene bridge para consultar analytics causales — un admin no puede preguntar "por que cayeron mis conversiones esta semana"

**Impacto:** Capacidad de IA premium infrautilizada. El admin tiene que adivinar causas en vez de preguntar.

### 3.3 ProactiveInsightsService: Genera Insights que NADIE Lee

**Situacion actual:**
- Cron cada 6h genera insights: usage_anomaly, churn_risk, content_gap, seo_opportunity, quota_warning
- Los insights se ALMACENAN pero NO se notifican al admin
- No hay widget de insights en el dashboard del hub
- No hay email ni notificacion push cuando se detecta una anomalia

**Impacto:** El sistema detecta problemas proactivamente pero el admin nunca se entera. Es como tener un medico que diagnostica pero no comunica el diagnostico.

### 3.4 A/B Testing: Motor Completo pero Solo Admin

**Situacion actual:**
- `ABTestingDashboardController` funciona en `/admin/ab-testing` (zona admin Drupal)
- `ABTestingProfileSection` existe como seccion en el perfil del hub — esto es un AVANCE
- `ExperimentOrchestratorService` declara ganadores y envia email
- Pero NO hay auto-apply del ganador: cuando un experimento gana, el admin tiene que aplicar manualmente los cambios

**Impacto:** El admin tiene acceso al A/B testing via perfil hub (mejor que otros), pero falta auto-aplicacion.

### 3.5 Funnel Visual: Datos Sin Visualizacion en Hub

**Situacion actual:**
- `FunnelTrackingService` calcula conversion rate, drop-off por paso
- `funnel-analysis.html.twig` existe pero en contexto admin
- `FunnelApiController` tiene endpoints completos (list, create, calculate)
- NO hay componente visual de embudo en el hub (barras decrecientes con porcentajes)

**Impacto:** Los datos de embudo se calculan pero el admin no puede ver visualmente donde pierde usuarios.

### 3.6 Heatmaps: Infraestructura Sin UI Accesible

**Situacion actual:**
- `HeatmapCollectorService` recolecta clicks, scroll, movimiento
- `HeatmapDashboardController` existe pero en ruta admin
- Integracion con screenshots para overlay
- NO accesible desde el hub del tenant

**Impacto:** Datos de interaccion valiosos que el admin no puede consultar.

### 3.7 Tabla Resumen de Gaps

| Componente | Backend | Frontend Hub | Notificacion | Auto-accion |
|---|---|---|---|---|
| Dashboard metricas | SI (completo) | NO | NO | N/A |
| Embudo conversion | SI (calculo) | NO (visual) | NO | NO |
| A/B Testing | SI (motor) | PARCIAL (perfil) | SI (email) | NO (auto-apply) |
| Heatmaps | SI (recoleccion) | NO | NO | N/A |
| Insights proactivos | SI (generacion) | NO | NO | NO |
| Analytics causal IA | SI (servicio) | NO | NO | NO |
| Copilot analytics bridge | NO | NO | NO | NO |
| Alertas anomalias | PARCIAL (KillCriteria) | NO | NO | NO |
| Recomendaciones CTA | NO | NO | NO | NO |
| Agente autonomo conversion | NO | NO | NO | NO |

---

## 4. Lo que FALTA para 10/10

### Gap 1: Dashboard Analytics en Hub del Tenant

**Que falta:** Ruta `/mi-analytics` con layout limpio (Zero Region), 5 tabs (overview, funnel, AB testing, insights IA, SEO), integracion Chart.js, datos inyectados via preprocess.

**Componentes necesarios:**
- `AnalyticsDashboardController` nuevo (o adaptacion del existente para hub)
- Template `page--mi-analytics.html.twig` con layout Zero Region
- SCSS `_mi-analytics.scss` para charts y KPIs
- Library `route-mi-analytics` en `.libraries.yml`
- `hook_preprocess_page()` para inyectar datos
- `hook_theme()` para declarar variables
- UserProfileSection: `analytics_dashboard` (weight 45)

**Esfuerzo estimado:** 3-4 dias (80% backend existe, falta wiring)

### Gap 2: IA Proactiva de Conversion Accesible

**Que falta:** Extension de ProactiveInsightsService con 3 nuevos tipos de insight especificos de conversion: `conversion_drop` (caida de conversion > 15% vs semana anterior), `traffic_spike` (pico inusual de trafico), `funnel_bottleneck` (paso del funnel con drop-off > 50%).

**Componentes necesarios:**
- `ConversionInsightsService` — analiza datos analytics_event, detecta anomalias con algoritmo IQR
- Extension de ProactiveInsightsService con nuevos tipos
- Widget de insights en dashboard hub
- Notificacion al admin via EiMultichannelNotificationService

**Esfuerzo estimado:** 2-3 dias

### Gap 3: Agente IA Autonomo de Conversion

**Que falta:** Un agente Gen 2 que ejecuta semanalmente, analiza los ultimos 7 dias de datos, genera un reporte con recomendaciones priorizadas, y auto-aplica ganadores de A/B tests cuando la confianza supera el 95%.

**Componentes necesarios:**
- `ConversionOptimizationAgent extends SmartBaseAgent`
- `doExecute()`: recopila datos -> analisis LLM -> recomendaciones -> auto-apply
- Integracion con ExperimentOrchestratorService para auto-apply
- Cron semanal + email de resumen

**Esfuerzo estimado:** 3-4 dias

### Gap 4: Alertas de Anomalias en Tiempo Real

**Que falta:** Sistema de deteccion de anomalias que notifique al admin inmediatamente (no cada 6h) cuando hay una caida significativa de trafico, conversion o disponibilidad.

**Componentes necesarios:**
- Extension de KillCriteriaAlertService con umbrales configurables
- Integracion con EiMultichannelNotificationService (email + in-app)
- Widget de alertas activas en el dashboard hub

**Esfuerzo estimado:** 1-2 dias

### Gap 5: Recomendaciones CTA Basadas en Datos

**Que falta:** Servicio que analiza el rendimiento de CTAs (datos ya recogidos por funnel-analytics.js) y sugiere cambios: texto, color, posicion, basandose en los mejores performers.

**Componentes necesarios:**
- `CtaRecommendationService` — analiza TrackingEvent por CTA
- Integracion con CausalAnalyticsService para explicaciones
- Recomendaciones visibles en dashboard hub tab "insights"

**Esfuerzo estimado:** 2 dias

### Gap 6: Auto-Apply de Ganador A/B

**Que falta:** Cuando ExperimentOrchestratorService declara un ganador con confianza > 95%, aplicar automaticamente la variante ganadora como default.

**Componentes necesarios:**
- Extension de ExperimentOrchestratorService con logica auto-apply
- Registro de audit trail (que se aplico, cuando, reversion posible)
- Notificacion al admin con opcion de revertir

**Esfuerzo estimado:** 1-2 dias

### Gap 7: Embudo Visual Interactivo

**Que falta:** Componente frontend de embudo con barras horizontales decrecientes, porcentajes de conversion/drop-off por paso, click para drill-down.

**Componentes necesarios:**
- Componente JS vanilla con Drupal.behaviors
- SCSS para barras de embudo con colores gradientes
- API ya existe (FunnelApiController)
- Integracion en tab "funnel" del dashboard hub

**Esfuerzo estimado:** 1-2 dias

### Resumen de Gaps

| # | Gap | Impacto | Esfuerzo | Dependencias |
|---|---|---|---|---|
| 1 | Dashboard en hub | CRITICO — sin esto nada es visible | 3-4 dias | Ninguna |
| 2 | IA proactiva conversion | ALTO — deteccion temprana | 2-3 dias | Gap 1 |
| 3 | Agente autonomo | ALTO — automatizacion | 3-4 dias | Gap 2 |
| 4 | Alertas anomalias | MEDIO-ALTO — reaccion rapida | 1-2 dias | Gap 1 |
| 5 | Recomendaciones CTA | MEDIO — optimizacion continua | 2 dias | Gap 1 |
| 6 | Auto-apply AB winner | MEDIO — cierre de loop | 1-2 dias | Ninguna |
| 7 | Embudo visual | MEDIO — comprension visual | 1-2 dias | Gap 1 |

**Total estimado:** 13-19 dias de desarrollo (4 sprints de 1 semana)

---

## 5. Arquitectura Propuesta: Analytics Hub Integrado

### 5.1 Ruta y Layout

```
/mi-analytics                -> Tab: Overview (default)
/mi-analytics/funnel         -> Tab: Embudo de Conversion
/mi-analytics/ab-testing     -> Tab: Experimentos A/B
/mi-analytics/insights       -> Tab: Insights IA
/mi-analytics/seo            -> Tab: SEO y Trafico
```

Patron Zero Region: `page--mi-analytics.html.twig` con `{{ clean_content }}` y `{{ clean_messages }}`.

### 5.2 Flujo de Datos

```
funnel-analytics.js (browser)
    |
    v
/api/v1/analytics/event (AnalyticsApiController)
    |
    v
analytics_event table
    |
    v (cron)
AnalyticsAggregatorService -> analytics_daily table
    |
    v (cron 6h)
ProductMetricsAggregatorService -> product_metric_snapshot table
    |
    v (request)
AnalyticsDashboardController (nuevo, hub)
    |
    v (preprocess)
hook_preprocess_page() -> $variables['analytics_data']
    |
    v
mi-analytics.html.twig -> Chart.js + KPI cards + funnel visual
```

### 5.3 Tabs del Dashboard

**Tab 1 — Overview:**
- KPI cards: Visitas totales (7d/30d), Tasa conversion, Tiempo medio, Paginas/sesion
- Grafico de tendencias (linea, 30 dias)
- Top 5 paginas por visitas
- Fuentes de trafico (pie chart)
- Widget de alertas activas

**Tab 2 — Embudo de Conversion:**
- Selector de funnel (FunnelDefinition entities)
- Visualizacion barras decrecientes con % conversion y % drop-off
- Comparativa periodo anterior
- Detalle por paso (click para expandir)

**Tab 3 — Experimentos A/B:**
- Lista de experimentos activos con estado y progreso
- KPIs: variantes, exposiciones, tasa conversion por variante
- Indicador de significancia estadistica
- Boton "aplicar ganador" cuando confianza > 95%

**Tab 4 — Insights IA:**
- Insights proactivos recientes (ProactiveInsightsService)
- Formulario de consulta causal ("?Por que cayeron las conversiones?")
- Respuesta LLM con factores causales, recomendaciones, confianza
- Historial de consultas previas

**Tab 5 — SEO y Trafico:**
- Datos de Search Console (ExternalAnalyticsService)
- Posiciones medias, clicks, impresiones
- Paginas sin meta description (seo_opportunity insights)
- Performance scores

### 5.4 Inyeccion de Datos (Zero Region)

```php
// En hook_preprocess_page() del tema:
if ($route === 'jaraba_analytics.hub_dashboard') {
  $analytics = \Drupal::service('jaraba_analytics.analytics_service');
  $tenantId = $variables['tenant_id'];
  $variables['analytics_overview'] = $analytics->getOverviewData($tenantId, 30);
  $variables['analytics_funnels'] = $analytics->getActiveFunnels($tenantId);
  $variables['analytics_experiments'] = $abService->getActiveExperiments($tenantId);
  $variables['analytics_insights'] = $insightsService->getRecentInsights($tenantId, 10);
  $variables['#attached']['drupalSettings']['analyticsHub'] = [
    'apiBase' => '/api/v1/analytics',
    'refreshInterval' => 60000,
    'chartColors' => [
      'primary' => 'var(--ej-azul-corporativo)',
      'secondary' => 'var(--ej-naranja-impulso)',
      'success' => 'var(--ej-verde-innovacion)',
    ],
  ];
}
```

---

## 6. Arquitectura Propuesta: Agente IA Autonomo de Conversion

### 6.1 Clase Principal

```
ConversionOptimizationAgent extends SmartBaseAgent
  |
  +-- doExecute()
  |     |
  |     +-- recopilarDatos() -> ultimos 7 dias analytics_event + product_metric_snapshot
  |     +-- analizarTendencias() -> LLM balanced tier
  |     +-- generarRecomendaciones() -> LLM premium tier
  |     +-- autoAplicarGanadores() -> ExperimentOrchestratorService
  |     +-- generarReporte() -> email + insight entity
  |
  +-- getAgentId(): 'conversion_optimization'
  +-- getRequiredCapabilities(): ['analytics_read', 'ab_testing_write']
```

### 6.2 Flujo de Ejecucion

1. **Cron semanal** (lunes 8:00): Supervisor lanza el agente via queue
2. **Recopilacion** (N1-N2 cascade): Lee analytics_daily, product_metric_snapshot, ab_experiment_result de los ultimos 7 dias
3. **Analisis** (N3): LLM balanced analiza tendencias, detecta anomalias, compara con semana anterior
4. **Recomendaciones** (N4): LLM premium genera 3-5 recomendaciones priorizadas con impacto estimado
5. **Auto-apply** (condicional): Si hay experimento con confianza > 95%, aplica variante ganadora. Registra en audit trail
6. **Reporte**: Crea ProactiveInsight entity + envia email resumen al admin del tenant

### 6.3 Salvaguardas del Agente

- **NUNCA** auto-aplica sin confianza > 95% (Z-score > 1.96)
- **SIEMPRE** crea snapshot de estado pre-aplicacion para reversion
- **LIMITE** de 1 auto-aplicacion por semana por tenant
- **NOTIFICACION** obligatoria al admin con boton "Revertir" en email
- **AUDIT-TRAIL** completo: que cambio, por que, datos que lo justifican
- **KILL-SWITCH** global via State API: `jaraba_conversion_agent.enabled`

### 6.4 Integracion con Copilot

El Copilot podra responder preguntas como:
- "Como van mis conversiones esta semana?" -> ConversionInsightsService
- "Por que bajo el trafico el martes?" -> CausalAnalyticsService (diagnostic)
- "Que pasaria si subo el precio un 10%?" -> CausalAnalyticsService (counterfactual)
- "Que debo mejorar para vender mas?" -> ConversionOptimizationAgent (prescriptive)

Bridge necesario en `CopilotBridgeService` de cada vertical:
```php
'analytics' => [
  'conversion_rate_7d' => $analyticsService->getConversionRate($tenantId, 7),
  'traffic_trend' => $analyticsService->getTrafficTrend($tenantId, 7),
  'active_experiments' => $abService->getActiveExperimentsSummary($tenantId),
  'recent_insights' => $insightsService->getRecentInsights($tenantId, 5),
],
```

---

## 7. Score por Dimension (15 criterios)

| # | Criterio | Actual | Objetivo | Detalle |
|---|---|---|---|---|
| 1 | Dashboard analytics en hub | 1/10 | 10/10 | Existe backend, falta frontend hub. Solo /admin/* |
| 2 | KPIs en tiempo real | 3/10 | 9/10 | API realtime existe, no se consume en hub |
| 3 | Embudo visual | 2/10 | 9/10 | FunnelTrackingService funcional, sin visualizacion hub |
| 4 | A/B Testing accesible | 5/10 | 10/10 | Motor completo + ProfileSection, falta auto-apply |
| 5 | Heatmaps accesibles | 2/10 | 8/10 | Backend completo, sin acceso hub |
| 6 | Insights IA proactivos | 3/10 | 10/10 | ProactiveInsights genera pero no notifica |
| 7 | Analytics causal IA | 3/10 | 9/10 | CausalAnalytics funcional, sin acceso usuario |
| 8 | Copilot analytics bridge | 1/10 | 9/10 | No hay bridge de analytics en copilot |
| 9 | Alertas anomalias | 2/10 | 9/10 | KillCriteria existe, sin notificacion real-time |
| 10 | Recomendaciones CTA | 1/10 | 8/10 | Datos se recogen, no se analizan para recomendar |
| 11 | Auto-apply AB winner | 2/10 | 9/10 | Orchestrator declara ganador, no aplica |
| 12 | Agente autonomo conversion | 0/10 | 9/10 | No existe. Componentes base si |
| 13 | Exportacion/reportes | 6/10 | 9/10 | ScheduledReport + Export existen, falta UI hub |
| 14 | NPS integrado | 5/10 | 8/10 | NpsSurveyService funcional, falta dashboard |
| 15 | Multi-tenant aislamiento | 7/10 | 10/10 | TenantContext se usa, falta verificar en todas las queries hub |

**Score medio actual: 2.9/10**
**Score medio objetivo: 9.1/10**

---

## 8. Medidas de Salvaguarda

### 8.1 Salvaguardas de Datos

| ID | Regla | Validacion |
|---|---|---|
| ANALYTICS-TENANT-001 | TODA query de analytics DEBE filtrar por tenant_id | `validate-tenant-isolation.php` |
| ANALYTICS-CONSENT-001 | Tracking SOLO con consentimiento GDPR activo | ConsentService.check() antes de cada track |
| ANALYTICS-PII-001 | NUNCA almacenar IP completa en analytics_event. Solo hash | AI-GUARDRAILS-PII-001 |
| ANALYTICS-RETENTION-001 | Datos granulares > 90 dias se eliminan. Solo agregados permanecen | Cron de limpieza |

### 8.2 Salvaguardas de IA

| ID | Regla | Validacion |
|---|---|---|
| AGENT-AUTOAPPLY-001 | Auto-apply SOLO con confianza > 95% y min 100 exposiciones | StatisticalEngineService.isSignificant() |
| AGENT-LIMIT-001 | Maximo 1 auto-aplicacion por semana por tenant | State API check |
| AGENT-REVERT-001 | TODA auto-aplicacion DEBE ser revertible en 1 click | Snapshot pre-aplicacion |
| AGENT-AUDIT-001 | TODA decision del agente queda en audit trail | LCIS-AUDIT-001 |

### 8.3 Salvaguardas de Rendimiento

| ID | Regla | Validacion |
|---|---|---|
| ANALYTICS-CACHE-001 | Dashboard KPIs cacheados 60s. No query en cada pageview | Cache backend con tags |
| ANALYTICS-BATCH-001 | Eventos de funnel-analytics.js via sendBeacon (non-blocking) | Ya implementado |
| ANALYTICS-AGGREGATE-001 | Consultas de dashboard usan analytics_daily, NUNCA analytics_event raw | AnalyticsDataService |
| ANALYTICS-QUEUE-001 | Agregacion pesada via QueueWorker, NUNCA en request | DailyAggregationWorker |

### 8.4 Salvaguardas de Frontend

| ID | Regla | Validacion |
|---|---|---|
| ANALYTICS-XSS-001 | Datos de API insertados via textContent, NUNCA innerHTML sin sanitizar | INNERHTML-XSS-001 |
| ANALYTICS-CSRF-001 | API endpoints con _csrf_request_header_token | Ya implementado en routing.yml |
| ANALYTICS-ZERO-001 | Dashboard en hub via Zero Region pattern | ZERO-REGION-001 |
| ANALYTICS-CHART-001 | Chart.js via library local (NO CDN) | CSP compliance |

---

## 9. Tabla de Correspondencia Specs

| Spec ID | Titulo | Descripcion | Componentes | Sprint |
|---|---|---|---|---|
| SPEC-ANA-001 | Dashboard Analytics Hub | Ruta /mi-analytics con 5 tabs, KPIs, Chart.js, Zero Region | AnalyticsDashboardController (hub), mi-analytics.html.twig, _mi-analytics.scss | K |
| SPEC-ANA-002 | Embudo Visual Interactivo | Componente barras decrecientes con % conversion/drop-off, drill-down | funnel-visual.js, _funnel-visual.scss, FunnelApiController (existente) | N |
| SPEC-ANA-003 | IA Proactiva Conversion | 3 nuevos insight types + widget en dashboard + notificacion admin | ConversionInsightsService, ProactiveInsightsService extension | L |
| SPEC-ANA-004 | Copilot Analytics Bridge | Copilot responde preguntas de analytics con datos reales del tenant | CopilotBridgeService extension, CausalAnalyticsService integration | L |
| SPEC-ANA-005 | Agente Autonomo Conversion | SmartBaseAgent semanal, recomendaciones + auto-apply AB winner | ConversionOptimizationAgent, cron semanal, email resumen | M |
| SPEC-ANA-006 | Auto-Apply AB Winner | Aplicar variante ganadora automaticamente con confianza > 95% | ExperimentOrchestratorService extension, audit trail, revert | M |
| SPEC-ANA-007 | Alertas Anomalias | Deteccion y notificacion inmediata de caidas de trafico/conversion | KillCriteriaAlertService extension, EiMultichannelNotificationService | N |
| SPEC-ANA-008 | Recomendaciones CTA | Analisis de rendimiento CTA + sugerencias basadas en datos | CtaRecommendationService, CausalAnalyticsService | L |
| SPEC-ANA-009 | Widget Daily Actions Analytics | Analytics summary en daily actions del admin hub | DailyActionsRegistry tagged service, AnalyticsDataService | K |
| SPEC-ANA-010 | Reportes Hub Accesibles | Exportacion CSV/JSON + scheduled reports desde UI hub | AnalyticsExportController (existente), ScheduledReportController, slide-panel | N |

---

## 10. Glosario

| Sigla | Significado |
|---|---|
| AB | A/B Testing — Experimentacion con dos o mas variantes para comparar rendimiento |
| API | Application Programming Interface — Interfaz de programacion de aplicaciones |
| CAPI | Conversions API — API server-side de Meta/LinkedIn/TikTok para tracking sin cookies |
| CRM | Customer Relationship Management — Gestion de relaciones con clientes |
| CSRF | Cross-Site Request Forgery — Falsificacion de peticiones entre sitios |
| CSS | Cascading Style Sheets — Hojas de estilo en cascada |
| CTA | Call To Action — Llamada a la accion (boton, enlace, formulario) |
| CSV | Comma-Separated Values — Valores separados por comas (formato de exportacion) |
| D7/D30 | Day 7/Day 30 — Retencion a 7 y 30 dias respectivamente |
| DI | Dependency Injection — Inyeccion de dependencias |
| DNT | Do Not Track — Cabecera HTTP que indica que el usuario no desea ser rastreado |
| DOM | Document Object Model — Modelo de objetos del documento |
| FSE | Fondo Social Europeo — Financiacion del programa Andalucia +EI |
| GA4 | Google Analytics 4 — Plataforma de analytics de Google |
| GDPR | General Data Protection Regulation — Reglamento General de Proteccion de Datos |
| HMAC | Hash-based Message Authentication Code — Codigo de autenticacion de mensajes basado en hash |
| HTML | HyperText Markup Language — Lenguaje de marcado de hipertexto |
| HTTP | HyperText Transfer Protocol — Protocolo de transferencia de hipertexto |
| IA | Inteligencia Artificial |
| IQR | Interquartile Range — Rango intercuartilico (metodo de deteccion de anomalias) |
| JS | JavaScript |
| JSON | JavaScript Object Notation — Notacion de objetos JavaScript |
| KPI | Key Performance Indicator — Indicador clave de rendimiento |
| LCIS | Legal Coherence Intelligence System — Sistema de coherencia legal |
| LLM | Large Language Model — Modelo de lenguaje grande |
| MRR | Monthly Recurring Revenue — Ingreso recurrente mensual |
| NPS | Net Promoter Score — Indice de promotores netos (satisfaccion cliente) |
| PIIL | Programa de Incentivos para la Insercion Laboral |
| PII | Personally Identifiable Information — Informacion de identificacion personal |
| PHP | PHP: Hypertext Preprocessor — Lenguaje de programacion server-side |
| REST | Representational State Transfer — Transferencia de estado representacional |
| RPO | Recovery Point Objective — Objetivo de punto de recuperacion |
| SaaS | Software as a Service — Software como servicio |
| SCSS | Sassy CSS — Preprocesador de CSS |
| SEO | Search Engine Optimization — Optimizacion para motores de busqueda |
| SMTP | Simple Mail Transfer Protocol — Protocolo simple de transferencia de correo |
| SQL | Structured Query Language — Lenguaje de consulta estructurado |
| SSOT | Single Source of Truth — Fuente unica de verdad |
| SVG | Scalable Vector Graphics — Graficos vectoriales escalables |
| UI | User Interface — Interfaz de usuario |
| URL | Uniform Resource Locator — Localizador uniforme de recursos |
| UTM | Urchin Tracking Module — Parametros de seguimiento de campanas |
| UUID | Universally Unique Identifier — Identificador unico universal |
| XSS | Cross-Site Scripting — Scripting entre sitios (vulnerabilidad de seguridad) |
