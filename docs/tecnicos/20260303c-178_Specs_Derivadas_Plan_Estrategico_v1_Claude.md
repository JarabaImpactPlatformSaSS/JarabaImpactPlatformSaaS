
ESPECIFICACIONES TECNICAS DERIVADAS
DEL PLAN ESTRATEGICO DE LANZAMIENTO SaaS
5 Modulos Nuevos para Implementacion por Claude Code
JARABA IMPACT PLATFORM
Plataforma de Ecosistemas Digitales S.L.
Campo	Valor
Codigo	178_Specs_Derivadas_Plan_Estrategico_v1
Version	1.0
Fecha	03 Marzo 2026
Estado	Especificacion Tecnica para Implementacion
Origen	20260303c-Plan_Estrategico_Lanzamiento_SaaS_v1
Verificado contra	Estado del SaaS v1.0.0 (28-02-2026)
Stack	PHP 8.4 + Drupal 11 + MariaDB 10.11 + Redis 7.4 + Qdrant
Prioridad	CRITICA - Bloquea lanzamiento Fase 0
 
 
INDICE DE CONTENIDOS
1. Analisis de Gaps: Plan Estrategico vs. SaaS Existente	1
1.1 Metodologia de Deteccion de Gaps	1
1.2 Resultado del Cruce	1
1.3 Gaps que YA Tienen Especificacion (Solo Activar)	1
2. GAP-1: jaraba_vertical_brand — Submarinas con Periscopio	1
2.1 Resumen del Modulo	1
2.2 Modelo de Datos	1
2.2.1 Config Entity: vertical_brand_config	1
2.2.2 Config Entity: vertical_brand_page	1
2.3 Servicio Principal: VerticalBrandService	1
2.4 Integracion con Theming	1
2.5 Route Subscriber para Subdominios	1
2.6 Configuracion por Vertical (YAML)	1
2.7 Schema.org y JSON-LD por Sub-Marca	1
2.8 Tests Requeridos	1
3. GAP-2: jaraba_product_analytics — KPIs Pre-PMF	1
3.1 Resumen del Modulo	1
3.2 Modelo de Datos	1
3.2.1 Entidad: product_event	1
3.2.2 Entidad: product_metric_snapshot	1
3.3 Definicion de Activation Criteria por Vertical	1
3.4 Servicio de Tracking	1
3.5 Cron ETL: Calculo Diario de Metricas	1
3.6 Dashboard Pre-PMF	1
3.7 API REST	1
3.8 JS SDK para Tracking Frontend	1
4. GAP-3: jaraba_feature_gates — Feature Gating por Plan	1
4.1 Modelo de Datos	1
4.1.1 Config Entity: feature_gate	1
4.1.2 Content Entity: feature_usage_counter	1
4.2 Servicio Principal: FeatureGateService	1
4.3 Twig Extension para Frontend	1
4.4 Configuracion por Vertical (Empleabilidad)	1
5. GAP-4: jaraba_pilot_manager — Gestion de Pilotos Institucionales	1
5.1 Modelo de Datos	1
5.1.1 Content Entity: pilot_program	1
5.1.2 Content Entity: pilot_tenant	1
5.1.3 Content Entity: pilot_feedback	1
5.2 Dashboard de Piloto	1
5.3 ECA: Automatizaciones del Piloto	1
6. GAP-5: jaraba_piil_bridge — Puente PIIL/STO y Metricas ESF	1
6.1 Resumen del Modulo	1
6.2 Modelo de Datos	1
6.2.1 Content Entity: piil_program_config	1
6.2.2 Content Entity: piil_participant	1
6.3 Servicio de Export STO	1
6.4 Dashboard PIIL	1
6.5 API REST	1
7. Roadmap de Implementacion	1
7.1 Estimacion Total	1
8. Mapa de Dependencias e Integracion	1
8.1 Integracion con Modulos Existentes	1
9. Estrategia de Testing	1
10. Checklist de Verificacion RUNTIME-VERIFY-001	1
11. Nota Final	1

 
1. Analisis de Gaps: Plan Estrategico vs. SaaS Existente
Este documento resulta del cruce sistematico entre las 13 secciones del Plan Estrategico de Lanzamiento (20260303c) y las 306+ especificaciones tecnicas existentes del Ecosistema Jaraba. El objetivo es identificar lo que FALTA en el SaaS para ejecutar la estrategia definida.

1.1 Metodologia de Deteccion de Gaps
Se han cruzado los 7 principios de ejecucion y las 4 fases del Go-to-Market con el inventario real de modulos (95 custom, 57 habilitados) y las especificaciones documentadas. Para cada requisito estrategico se verifico: (a) existe especificacion?, (b) existe codigo?, (c) esta habilitado?, (d) cubre el requisito completo?

1.2 Resultado del Cruce
Se identifican 5 gaps tecnicos que NO tienen especificacion existente y que son BLOQUEANTES para la ejecucion del plan:

#	Gap Identificado	Modulo Propuesto	Origen Estrategico	Prioridad	Horas Est.
GAP-1	Submarinas con Periscopio: Verticales como productos independientes	jaraba_vertical_brand	Seccion 6 + Principio 3	P0 CRITICA	120-160h
GAP-2	KPIs Pre-PMF: Activation, Retention, TTV, NPS	jaraba_product_analytics	Seccion 11 + Principio 4	P0 CRITICA	100-140h
GAP-3	Feature gating por plan de precios en runtime	jaraba_feature_gates	Seccion 4 + Doc 158	P0 CRITICA	80-100h
GAP-4	Gestion de pilotos institucionales (LOI, Go/No-Go)	jaraba_pilot_manager	Seccion 7 Fase 0	P0 CRITICA	60-80h
GAP-5	Bridge PIIL/STO: Export datos y metricas ESF	jaraba_piil_bridge	Seccion 7 + Manual STO	P1 ALTA	80-120h

1.3 Gaps que YA Tienen Especificacion (Solo Activar)
Estos elementos estan mencionados en el Plan Estrategico y YA tienen especificaciones tecnicas completas. Solo requieren habilitacion y configuracion, NO nueva especificacion:

Componente	Doc Existente	Estado Actual	Accion Requerida
Onboarding Product-Led	110_Platform_Onboarding_ProductLed_v1	Definido (inactivo)	Habilitar jaraba_onboarding + configurar flows por vertical
Customer Success + Health Score	113_Platform_Customer_Success_v1	Definido (inactivo)	Habilitar jaraba_success + implementar Health Score Engine
Support + SLA	Modulo jaraba_support	Definido (inactivo)	Habilitar como P0 para pilotos
Notificaciones multicanal	76/98_Notifications_System	Definido (inactivo)	Habilitar jaraba_notifications
PWA Mobile	109_Platform_PWA_Mobile_v1	Definido (inactivo)	Habilitar P1 (critico rural)
Admin Center Premium	104_SaaS_Admin_Center_Premium_v1	Definido (inactivo)	Habilitar con FOC integration
Stripe Billing	134_Platform_Stripe_Billing_v1	Definido (inactivo)	Configurar planes Starter/Pro/Enterprise
Webhooks salientes	03/06_Core (ECA-WH-001)	Especificado en ECA	Implementar dispatcher + UI gestion por tenant
 
2. GAP-1: jaraba_vertical_brand — Submarinas con Periscopio
CONTEXTO ESTRATEGICO
El Plan Estrategico define que el problema #1 del SaaS es 'Demasiado Ancho': 10 verticales confunden al comprador.
Solucion: Cada vertical se presenta como producto INDEPENDIENTE con sub-marca, landing, onboarding y pitch propios.
La plataforma integrada solo se revela progresivamente cuando aporta valor al comprador especifico.
SIN ESTE MODULO, no se puede ejecutar el Go-to-Market. Es BLOQUEANTE para Fase 0.

2.1 Resumen del Modulo
Campo	Valor
Nombre	jaraba_vertical_brand
Tipo	Modulo Drupal 11 custom
Dependencias	jaraba_core, jaraba_tenant, jaraba_onboarding, ecosistema_jaraba_theme
Responsabilidad	Presentar cada vertical como producto independiente con identidad propia
Patron	Service + Config Entity + Twig preprocessor + Route subscriber
Estimacion	120-160 horas de desarrollo

2.2 Modelo de Datos
2.2.1 Config Entity: vertical_brand_config
Configuracion de marca por vertical. Una instancia por vertical activa.

Campo	Tipo	Requerido	Descripcion
id	VARCHAR(32)	Si	Machine name: empleabilidad, agroconecta, comercioconecta...
label	VARCHAR(255)	Si	Nombre comercial: Jaraba Emplea, AgroConecta...
tagline	VARCHAR(500)	Si	Propuesta de valor en 1 linea
subdomain	VARCHAR(100)	Si	Subdominio: emplea, agro, comercio, servicios, lex
logo_path	VARCHAR(500)	No	Ruta a logo SVG de la sub-marca
icon_class	VARCHAR(100)	Si	Clase icono jaraba_icon(): briefcase, leaf, store...
color_primary	VARCHAR(7)	Si	Color primario HEX de la sub-marca
color_secondary	VARCHAR(7)	Si	Color secundario HEX
industry_preset	VARCHAR(50)	No	Preset de industria del doc 101/102
landing_node_id	INT	No	Node ID de la landing page (Page Builder)
onboarding_template_id	VARCHAR(32)	No	ID del template de onboarding (doc 110)
target_avatars	JSON	Si	Array de avatar_types validos para esta vertical
features_highlight	JSON	Si	Top 5 features para pitch (titulo + descripcion)
social_proof	JSON	No	Testimonios, logos, metricas para landing
cta_primary_text	VARCHAR(100)	Si	Texto del CTA principal: Prueba gratis 30 dias
cta_primary_url	VARCHAR(255)	Si	URL del CTA: /registro?vertical=empleabilidad
seo_title	VARCHAR(70)	Si	Meta title para SEO
seo_description	VARCHAR(160)	Si	Meta description para SEO
schema_org_type	VARCHAR(100)	Si	Tipo Schema.org: EmploymentAgency, Store...
revelation_level	INT (1-4)	Si	Nivel maximo de revelacion por defecto
status	BOOLEAN	Si	Activo/Inactivo
weight	INT	Si	Orden de presentacion

2.2.2 Config Entity: vertical_brand_page
Paginas asociadas a cada sub-marca para SEO/GEO.
Campo	Tipo	Requerido	Descripcion
id	SERIAL	Si	ID auto-incremental
vertical_brand_id	VARCHAR(32) FK	Si	Referencia a vertical_brand_config
page_type	ENUM	Si	landing|features|pricing|faq|demo|case_study
node_id	INT FK	Si	Node ID en Drupal (Page Builder content)
path_alias	VARCHAR(255)	Si	Path: /emplea/funcionalidades, /agro/precios
seo_title	VARCHAR(70)	Si	Title tag
seo_description	VARCHAR(160)	Si	Meta description
og_image	VARCHAR(500)	No	Open Graph image URL

2.3 Servicio Principal: VerticalBrandService
<?php
// jaraba_vertical_brand/src/Service/VerticalBrandService.php

namespace Drupal\jaraba_vertical_brand\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_tenant\Service\TenantContextServiceInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class VerticalBrandService implements VerticalBrandServiceInterface {

  // Mapeo subdominio -> vertical_brand_config.id
  private const SUBDOMAIN_MAP = [
    'emplea' => 'empleabilidad',
    'emprende' => 'emprendimiento',
    'agro' => 'agroconecta',
    'comercio' => 'comercioconecta',
    'servicios' => 'serviciosconecta',
    'lex' => 'jarabalex',
  ];

  public function __construct(
    private ConfigFactoryInterface $configFactory,
    private TenantContextServiceInterface $tenantContext,
    private RequestStack $requestStack,
  ) {}

  /**
   * Detecta la vertical activa por subdominio o parametro.
   * Prioridad: 1) Route param, 2) Subdominio, 3) Tenant vertical, 4) NULL
   */
  public function detectCurrentVerticalBrand(): ?VerticalBrandConfigInterface {
    $request = $this->requestStack->getCurrentRequest();
    
    // 1. Route parameter explicito (?vertical=agroconecta)
    if ($verticalId = $request->query->get('vertical')) {
      return $this->loadBrandConfig($verticalId);
    }
    
    // 2. Subdominio (emplea.jarabaimpact.com)
    $host = $request->getHost();
    if (preg_match('/^([a-z0-9-]+)\.jarabaimpact\.com$/', $host, $m)) {
      $subdomain = $m[1];
      if (isset(self::SUBDOMAIN_MAP[$subdomain])) {
        return $this->loadBrandConfig(self::SUBDOMAIN_MAP[$subdomain]);
      }
    }
    
    // 3. Contexto del tenant actual
    $tenant = $this->tenantContext->getCurrentTenant();
    if ($tenant && $vertical = $tenant->getVerticalId()) {
      return $this->loadBrandConfig($vertical);
    }
    
    return NULL; // Muestra plataforma completa (nivel 4)
  }

  /**
   * Determina nivel de revelacion para el usuario actual.
   * Level 1: Solo vertical (landing publica)
   * Level 2: Vertical + add-ons marketing (trial/demo)
   * Level 3: Ecosistema cross-vertical (usuario con plan activo)
   * Level 4: Plataforma completa (enterprise/institucional)
   */
  public function getRevelationLevel(): int {
    $user = \Drupal::currentUser();
    
    if ($user->isAnonymous()) return 1;
    
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) return 1;
    
    $plan = $tenant->getPlanType();
    $roles = $user->getRoles();
    
    if (in_array('platform_admin', $roles) ||
        in_array('institutional_admin', $roles)) return 4;
    if ($plan === 'enterprise') return 3;
    if (in_array($plan, ['pro', 'growth'])) return 2;
    
    return 1; // Starter o trial
  }

  /**
   * Filtra menu de navegacion segun nivel de revelacion.
   */
  public function filterNavigation(array $menuItems): array {
    $level = $this->getRevelationLevel();
    $brand = $this->detectCurrentVerticalBrand();
    
    return array_filter($menuItems, function($item) use ($level, $brand) {
      $itemLevel = $item['revelation_level'] ?? 1;
      if ($itemLevel > $level) return FALSE;
      if ($level <= 2 && $brand) {
        return ($item['vertical'] ?? NULL) === $brand->id() ||
               ($item['vertical'] ?? NULL) === 'core';
      }
      return TRUE;
    });
  }

  public function loadBrandConfig(string $id): ?VerticalBrandConfigInterface {
    $config = $this->configFactory
      ->get('jaraba_vertical_brand.brand.' . $id);
    return $config->isNew() ? NULL : new VerticalBrandConfig($config);
  }
}

2.4 Integracion con Theming
El modulo inyecta variables CSS de la sub-marca en el theme ecosistema_jaraba_theme:
// jaraba_vertical_brand/jaraba_vertical_brand.module

/**
 * Implements hook_preprocess_html().
 * Inyecta CSS custom properties de la sub-marca activa.
 */
function jaraba_vertical_brand_preprocess_html(&$variables) {
  $brand = \Drupal::service('jaraba_vertical_brand.manager')
    ->detectCurrentVerticalBrand();
  
  if ($brand) {
    $variables['attributes']['class'][] = 'vertical-brand--' . $brand->id();
    $variables['attributes']['data-vertical'] = $brand->id();
    $variables['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => ':root {' .
          '--ej-brand-primary: ' . $brand->getColorPrimary() . ';' .
          '--ej-brand-secondary: ' . $brand->getColorSecondary() . ';' .
          '--ej-brand-name: "' . $brand->label() . '";' .
        '}',
      ],
      'vertical_brand_css',
    ];
  }
  
  // Nivel de revelacion como data attribute
  $level = \Drupal::service('jaraba_vertical_brand.manager')
    ->getRevelationLevel();
  $variables['attributes']['data-revelation-level'] = $level;
}

2.5 Route Subscriber para Subdominios
// jaraba_vertical_brand/src/Routing/VerticalBrandRouteSubscriber.php

namespace Drupal\jaraba_vertical_brand\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class VerticalBrandRouteSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection) {
    // Rutas de landing por vertical
    $verticals = ['empleabilidad','emprendimiento','agroconecta',
      'comercioconecta','serviciosconecta','jarabalex'];
    
    foreach ($verticals as $vertical) {
      $route = new \Symfony\Component\Routing\Route(
        '/' . $vertical,
        [
          '_controller' => 
            '\Drupal\jaraba_vertical_brand\Controller\LandingController::view',
          'vertical_id' => $vertical,
        ],
        ['_access' => 'TRUE']
      );
      $collection->add('jaraba_vertical_brand.landing.' . $vertical, $route);
    }
  }
}

2.6 Configuracion por Vertical (YAML)
Ejemplo de configuracion para la vertical Empleabilidad (Fase 0):
# config/install/jaraba_vertical_brand.brand.empleabilidad.yml
id: empleabilidad
label: 'Jaraba Emplea'
tagline: 'Itinerarios de empleo inteligentes con IA'
subdomain: emplea
icon_class: briefcase
color_primary: '#2E7D32'
color_secondary: '#66BB6A'
industry_preset: employment_services
target_avatars: ['job_seeker', 'employer', 'program_manager']
features_highlight:
  - title: 'Matching IA candidato-oferta'
    description: 'Algoritmo que cruza 23 variables para recomendar las mejores ofertas'
  - title: 'LMS con certificaciones Open Badge 3.0'
    description: 'Cursos con video H5P y badges verificables'
  - title: 'CV Builder con IA'
    description: 'Genera CV profesional en 5 minutos'
  - title: 'Panel PIIL con metricas ESF'
    description: 'Dashboard para justificacion de subvenciones institucionales'
  - title: 'Alertas de empleo personalizadas'
    description: 'Notificaciones cuando aparecen ofertas compatibles'
cta_primary_text: 'Prueba gratis 30 dias'
cta_primary_url: '/registro?vertical=empleabilidad'
seo_title: 'Jaraba Emplea - Plataforma de Empleo con IA para Entidades PIIL'
seo_description: 'Gestion de itinerarios de empleo con matching IA, LMS certificado y metricas PIIL. Desde 29 euros/mes.'
schema_org_type: EmploymentAgency
revelation_level: 2
status: true
weight: 0

2.7 Schema.org y JSON-LD por Sub-Marca
// jaraba_vertical_brand/src/Service/VerticalBrandSchemaService.php

public function buildJsonLd(VerticalBrandConfigInterface $brand): array {
  return [
    '@context' => 'https://schema.org',
    '@type' => $brand->getSchemaOrgType(),
    'name' => $brand->label(),
    'description' => $brand->getTagline(),
    'url' => 'https://' . $brand->getSubdomain() . '.jarabaimpact.com',
    'brand' => [
      '@type' => 'Brand',
      'name' => $brand->label(),
    ],
    'provider' => [
      '@type' => 'Organization',
      'name' => 'Plataforma de Ecosistemas Digitales S.L.',
      'url' => 'https://plataformadeecosistemas.com',
    ],
    'areaServed' => [
      '@type' => 'Country',
      'name' => 'Spain',
    ],
    'offers' => [
      '@type' => 'AggregateOffer',
      'priceCurrency' => 'EUR',
      'lowPrice' => '29',
      'highPrice' => '199',
    ],
  ];
}

2.8 Tests Requeridos
Test	Tipo	Que Verifica
VerticalBrandServiceTest	Unit	Deteccion por subdominio, param, tenant context
RevelationLevelTest	Unit	Niveles 1-4 segun rol, plan, autenticacion
NavigationFilterTest	Unit	Filtrado de menu segun nivel de revelacion
VerticalBrandConfigTest	Kernel	CRUD de config entity, validacion campos
LandingControllerTest	Functional	Renderizado landing, CSS variables inyectadas
SubdomainRoutingTest	Functional	emplea.jarabaimpact.com resuelve correctamente
SchemaOrgOutputTest	Functional	JSON-LD valido en <head> de landing
 
3. GAP-2: jaraba_product_analytics — KPIs Pre-PMF
CONTEXTO ESTRATEGICO
Principio 4 del Plan: 'METRICAS, NO OPINIONES'. Activation >40%, D30 >25%, NPS >40, churn <5%.
Kill criteria: Si activation <20% o D30 <15% o NPS <10 => PIVOTAR.
EXISTENTE: FOC mide metricas financieras (MRR, ARR, Churn monetario). Customer Success (113) mide health scores.
FALTA: Metricas de PRODUCTO: activation funnels, retention cohorts, Time-to-Value, feature adoption.
Sin este modulo, no se puede evaluar Go/No-Go en Fase 0. Es BLOQUEANTE.

3.1 Resumen del Modulo
Campo	Valor
Nombre	jaraba_product_analytics
Tipo	Modulo Drupal 11 custom
Dependencias	jaraba_core, jaraba_tenant, jaraba_onboarding
Responsabilidad	Tracking de eventos de producto, calculo de metricas pre-PMF, dashboards de retention
Patron	Event Subscriber + Cron ETL + Custom Entity + Dashboard blocks
Estimacion	100-140 horas

3.2 Modelo de Datos
3.2.1 Entidad: product_event
Evento de producto inmutable (append-only). Alta frecuencia, particionado por fecha.
Campo	Tipo	Descripcion
id	BIGINT AUTO_INCREMENT	ID secuencial (partitioning key)
event_type	VARCHAR(100) INDEX	signup, activation, feature_use, session_start, session_end, nps_response...
user_id	INT INDEX	UID del usuario (0 si anonimo)
tenant_id	INT INDEX	Group ID del tenant
vertical_id	VARCHAR(32)	Vertical activa al momento del evento
session_id	VARCHAR(64)	Session ID para agrupar eventos
event_data	JSON	Payload especifico del evento
created	TIMESTAMP INDEX	Momento exacto del evento (UTC)

Particionado por mes en MariaDB 10.11:
-- Particion por rango de fecha para rendimiento
ALTER TABLE product_event PARTITION BY RANGE (UNIX_TIMESTAMP(created)) (
  PARTITION p202603 VALUES LESS THAN (UNIX_TIMESTAMP('2026-04-01')),
  PARTITION p202604 VALUES LESS THAN (UNIX_TIMESTAMP('2026-05-01')),
  PARTITION p202605 VALUES LESS THAN (UNIX_TIMESTAMP('2026-06-01')),
  PARTITION pmax VALUES LESS THAN MAXVALUE
);

3.2.2 Entidad: product_metric_snapshot
Snapshot diario de metricas agregadas. Calculado por cron a las 02:00 UTC.
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
snapshot_date	DATE UNIQUE INDEX	Fecha del snapshot
tenant_id	INT INDEX	Tenant (0 = plataforma global)
vertical_id	VARCHAR(32)	Vertical (NULL = todas)
total_signups	INT	Registros totales acumulados
new_signups_today	INT	Registros nuevos ese dia
activated_users	INT	Usuarios que completaron activation criteria
activation_rate	DECIMAL(5,2)	activated / total_signups * 100
d1_retention	DECIMAL(5,2)	% usuarios activos dia 1 post-registro
d7_retention	DECIMAL(5,2)	% usuarios activos dia 7
d30_retention	DECIMAL(5,2)	% usuarios activos dia 30
avg_ttv_seconds	INT	Tiempo medio hasta primera accion de valor
nps_score	DECIMAL(4,1)	NPS del periodo (-100 a 100)
nps_responses	INT	Numero de respuestas NPS
feature_adoption_rate	DECIMAL(5,2)	% usuarios usando 3+ core features
dau	INT	Daily Active Users
wau	INT	Weekly Active Users
mau	INT	Monthly Active Users
dau_mau_ratio	DECIMAL(5,2)	Stickiness: DAU/MAU * 100

3.3 Definicion de Activation Criteria por Vertical
Cada vertical define que constituye 'activacion' (Aha! moment):
Vertical	Activation Criteria	Condicion Tecnica
Empleabilidad (Job Seeker)	Ver ofertas recomendadas O aplicar a primera oferta	event: page_visit:/jobs/recommended OR application.count >= 1
Empleabilidad (Employer)	Publicar primera oferta de empleo	event: job_offer.created AND status = published
Emprendimiento	Completar diagnostico de negocio	event: diagnostic_completed AND score IS NOT NULL
AgroConecta	Anadir primer producto al catalogo	event: product.created AND product.count >= 1
ComercioConecta	Publicar tienda online	event: store.status = published
ServiciosConecta	Recibir primera reserva	event: booking.created

3.4 Servicio de Tracking
<?php
// jaraba_product_analytics/src/Service/ProductEventTracker.php

namespace Drupal\jaraba_product_analytics\Service;

class ProductEventTracker implements ProductEventTrackerInterface {

  /**
   * Registra un evento de producto.
   * CRITICO: Async via queue para no impactar rendimiento.
   */
  public function track(string $eventType, array $data = []): void {
    $event = [
      'event_type' => $eventType,
      'user_id' => (int) \Drupal::currentUser()->id(),
      'tenant_id' => $this->tenantContext->getCurrentTenantId() ?? 0,
      'vertical_id' => $this->verticalBrand
        ->detectCurrentVerticalBrand()?->id(),
      'session_id' => session_id() ?: 'cli_' . getmypid(),
      'event_data' => $data,
      'created' => date('Y-m-d H:i:s'),
    ];
    
    // Encolar para insercion batch (no bloquea request)
    $this->queueFactory->get('product_event_insert')
      ->createItem($event);
  }

  /**
   * Eventos predefinidos con semantic names.
   */
  public function trackSignup(int $userId, string $source): void {
    $this->track('signup', ['source' => $source]);
  }

  public function trackActivation(int $userId, string $criterion): void {
    $this->track('activation', ['criterion' => $criterion]);
  }

  public function trackFeatureUse(string $feature): void {
    $this->track('feature_use', ['feature' => $feature]);
  }

  public function trackNpsResponse(int $score, ?string $comment): void {
    $this->track('nps_response', [
      'score' => $score,
      'comment' => $comment,
    ]);
  }

  public function trackTimeToValue(int $seconds): void {
    $this->track('time_to_value', ['seconds' => $seconds]);
  }
}

3.5 Cron ETL: Calculo Diario de Metricas
// jaraba_product_analytics/src/Cron/MetricSnapshotCalculator.php

/**
 * Ejecuta a las 02:00 UTC via Ultimate Cron.
 * Calcula metricas agregadas del dia anterior.
 */
public function calculateDailySnapshot(string $date): void {
  $yesterday = $date ?: date('Y-m-d', strtotime('-1 day'));
  
  // Activation Rate
  $totalSignups = $this->countEvents('signup', NULL, $yesterday);
  $activated = $this->countEvents('activation', NULL, $yesterday);
  $activationRate = $totalSignups > 0
    ? ($activated / $totalSignups) * 100 : 0;
  
  // D1/D7/D30 Retention (cohorte-based)
  $d1 = $this->calculateRetention(1, $yesterday);
  $d7 = $this->calculateRetention(7, $yesterday);
  $d30 = $this->calculateRetention(30, $yesterday);
  
  // Time-to-Value (median)
  $ttv = $this->calculateMedianTTV($yesterday);
  
  // NPS
  $nps = $this->calculateNPS($yesterday);
  
  // Feature Adoption (3+ core features)
  $featureAdoption = $this->calculateFeatureAdoption($yesterday);
  
  // DAU/WAU/MAU
  $dau = $this->countUniqueActiveUsers($yesterday, $yesterday);
  $wau = $this->countUniqueActiveUsers(
    date('Y-m-d', strtotime('-7 days', strtotime($yesterday))),
    $yesterday
  );
  $mau = $this->countUniqueActiveUsers(
    date('Y-m-d', strtotime('-30 days', strtotime($yesterday))),
    $yesterday
  );
  
  // Guardar snapshot
  $this->saveSnapshot([...]);
}

private function calculateRetention(int $dayN, string $refDate): float {
  $cohortDate = date('Y-m-d',
    strtotime('-' . $dayN . ' days', strtotime($refDate)));
  $cohortSize = $this->countEvents('signup', $cohortDate, $cohortDate);
  if ($cohortSize === 0) return 0.0;
  
  $active = $this->countActiveFromCohort($cohortDate, $refDate);
  return ($active / $cohortSize) * 100;
}

3.6 Dashboard Pre-PMF
Ruta: /admin/jaraba/product-analytics
Bloques del dashboard:
Bloque	Metrica	Visualizacion	Alarma Roja
Activation Rate	% usuarios activados	Gauge + sparkline 30d	< 20% (kill criteria)
D7 Retention	% activos dia 7	Line chart cohort	< 30%
D30 Retention	% activos dia 30	Line chart cohort	< 15% (kill criteria)
Time-to-Value	Mediana TTV en minutos	Histogram + trend	> 30 min
NPS Score	Net Promoter Score	Gauge + distribucion	< 10 (kill criteria)
Feature Adoption	% usando 3+ features	Bar chart por feature	< 30%
DAU/MAU Ratio	Stickiness diaria	Line chart 30d	< 10%
Funnel de Activacion	Signup -> Profile -> First Action -> Activation	Funnel chart	Caida >60% en cualquier paso

3.7 API REST
Metodo	Endpoint	Descripcion
POST	/api/v1/analytics/events	Registrar evento (tambien via JS SDK)
GET	/api/v1/analytics/metrics/current	Metricas actuales del tenant
GET	/api/v1/analytics/metrics/history?from=&to=	Serie temporal de snapshots
GET	/api/v1/analytics/retention/cohort?period=weekly	Tabla cohort de retencion
GET	/api/v1/analytics/funnel?steps=signup,profile,activation	Datos de funnel
GET	/api/v1/analytics/nps/current	NPS actual con distribucion
GET	/api/v1/analytics/features/adoption	Adopcion por feature

3.8 JS SDK para Tracking Frontend
// jaraba_product_analytics/js/product-analytics.js

(function (Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.productAnalytics = {
    attach: function (context) {
      if (context !== document) return;
      
      const config = drupalSettings.jarabaProductAnalytics || {};
      const endpoint = config.endpoint || '/api/v1/analytics/events';
      const sessionStart = Date.now();
      
      // Track page view automatico
      track('page_view', { path: window.location.pathname });
      
      // Track Time-to-Value (primer clic en accion de valor)
      document.querySelectorAll('[data-ttv-action]')
        .forEach(function(el) {
          el.addEventListener('click', function() {
            const seconds = Math.round(
              (Date.now() - sessionStart) / 1000);
            track('time_to_value', { seconds: seconds });
          }, { once: true });
        });
      
      function track(eventType, data) {
        if (navigator.sendBeacon) {
          navigator.sendBeacon(endpoint, JSON.stringify({
            event_type: eventType,
            event_data: data,
            timestamp: new Date().toISOString()
          }));
        }
      }
      
      // Exponer globalmente para tracking manual
      window.JarabaAnalytics = { track: track };
    }
  };
})(Drupal, drupalSettings);
 
4. GAP-3: jaraba_feature_gates — Feature Gating por Plan
CONTEXTO ESTRATEGICO
Doc 158 define planes Starter/Pro/Enterprise con diferentes features por vertical.
EXISTENTE: RBAC (doc 04) controla permisos por ROL, no por PLAN DE PRECIOS.
EXISTENTE: Stripe Billing (doc 134) gestiona suscripciones pero no gate features en runtime.
FALTA: Un servicio que en runtime diga: 'Este tenant tiene plan Starter -> NO puede acceder a AI Copilot'.
Sin esto, cualquier trial/starter tiene acceso a TODO. No hay diferenciacion comercial.

4.1 Modelo de Datos
4.1.1 Config Entity: feature_gate
Campo	Tipo	Descripcion
id	VARCHAR(64)	Machine name: ai_copilot, advanced_matching, page_builder_pro...
label	VARCHAR(255)	Nombre visible: Copilot IA, Matching Avanzado...
description	TEXT	Descripcion para upgrade prompt
vertical	VARCHAR(32)	Vertical: empleabilidad, agroconecta... o 'core' si transversal
gate_type	ENUM	boolean (on/off), quota (limite numerico), tier (nivel minimo)
plans_allowed	JSON	{'starter': false, 'pro': true, 'enterprise': true}
quota_limits	JSON	{'starter': 10, 'pro': 100, 'enterprise': -1} (-1 = ilimitado)
addon_id	VARCHAR(64)	ID del add-on que desbloquea (jaraba_crm, jaraba_email...)
upgrade_cta_text	VARCHAR(200)	Texto CTA: Desbloquea el Copilot IA con el plan Pro
upgrade_cta_url	VARCHAR(255)	URL de upgrade: /cuenta/plan?upgrade=pro
status	BOOLEAN	Gate activo/inactivo

4.1.2 Content Entity: feature_usage_counter
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
tenant_id	INT INDEX	Tenant que consume
feature_gate_id	VARCHAR(64) INDEX	Feature consumida
period	VARCHAR(7)	Periodo: 2026-03 (mensual)
usage_count	INT	Consumo acumulado en el periodo
quota_limit	INT	Limite vigente en el periodo (-1 = ilimitado)
last_used	TIMESTAMP	Ultimo uso

4.2 Servicio Principal: FeatureGateService
<?php
// jaraba_feature_gates/src/Service/FeatureGateService.php

namespace Drupal\jaraba_feature_gates\Service;

class FeatureGateService implements FeatureGateServiceInterface {

  /**
   * Verifica si el tenant actual puede acceder a una feature.
   * PUNTO UNICO DE DECISION. Todos los modulos llaman aqui.
   *
   * @return FeatureAccessResult con allowed, reason, upgradeCta
   */
  public function checkAccess(string $featureId): FeatureAccessResult {
    $gate = $this->loadGate($featureId);
    if (!$gate || !$gate->isActive()) {
      return FeatureAccessResult::allowed(); // No gate = permitido
    }
    
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return FeatureAccessResult::denied('No tenant context');
    }
    
    $plan = $tenant->getPlanType() ?? 'starter';
    
    // Check plan-based gate
    $plansAllowed = $gate->getPlansAllowed();
    if (!($plansAllowed[$plan] ?? false)) {
      // Check if unlocked via add-on
      if ($gate->getAddonId() &&
          $this->addonIsActive($tenant, $gate->getAddonId())) {
        return FeatureAccessResult::allowed();
      }
      return FeatureAccessResult::denied(
        'Plan ' . $plan . ' no incluye ' . $gate->label(),
        $gate->getUpgradeCtaText(),
        $gate->getUpgradeCtaUrl()
      );
    }
    
    // Check quota-based gate
    if ($gate->getGateType() === 'quota') {
      $counter = $this->getUsageCounter($tenant->id(), $featureId);
      $limit = $gate->getQuotaLimits()[$plan] ?? 0;
      if ($limit !== -1 && $counter->getUsageCount() >= $limit) {
        return FeatureAccessResult::quotaExceeded(
          $counter->getUsageCount(), $limit,
          $gate->getUpgradeCtaText(),
          $gate->getUpgradeCtaUrl()
        );
      }
    }
    
    return FeatureAccessResult::allowed();
  }

  /**
   * Incrementa contador de uso. Llamar DESPUES de verificar acceso.
   */
  public function incrementUsage(string $featureId): void {
    $tenant = $this->tenantContext->getCurrentTenant();
    $period = date('Y-m');
    $counter = $this->getOrCreateCounter(
      $tenant->id(), $featureId, $period);
    $counter->increment();
    $counter->save();
    
    // Cache invalidation
    $this->cacheTagsInvalidator->invalidateTags(
      ['feature_usage:' . $tenant->id()]
    );
  }

  /**
   * Helper rapido para Twig: {{ feature_allowed('ai_copilot') }}
   */
  public function isAllowed(string $featureId): bool {
    return $this->checkAccess($featureId)->isAllowed();
  }
}

4.3 Twig Extension para Frontend
// jaraba_feature_gates/src/Twig/FeatureGateTwigExtension.php

public function getTests() {
  return [
    new TwigTest('feature_allowed',
      [$this->gateService, 'isAllowed']),
  ];
}

// USO EN TWIG:
// {% if 'ai_copilot' is feature_allowed %}
//   {{ render_copilot() }}
// {% else %}
//   {% include '@ecosistema_jaraba_theme/components/upgrade-prompt.html.twig'
//     with { feature: 'ai_copilot' } %}
// {% endif %}

4.4 Configuracion por Vertical (Empleabilidad)
Feature	Starter (29 EUR)	Pro (79 EUR)	Enterprise (149 EUR)	Gate Type
Basic Job Board	Si	Si	Si	boolean
LMS basico (5 cursos)	Si	Si	Si	quota
CV Builder	Si	Si	Si	boolean
AI Matching basico	Si	Si	Si	boolean
AI Matching avanzado (23 variables)	No	Si	Si	boolean
AI Copilot (chat asistente)	No	Si	Si	boolean
LMS ilimitado	No	Si	Si	quota
Credenciales Open Badge 3.0	No	Si	Si	boolean
Job Alerts automaticas	No	Si	Si	boolean
Dashboard PIIL con export ESF	No	No	Si	boolean
API REST completa	No	No	Si	boolean
White Label	No	No	Si	boolean
Consultas AI/mes	10	100	Ilimitado	quota
Ofertas activas simultaneas	5	50	Ilimitado	quota
 
5. GAP-4: jaraba_pilot_manager — Gestion de Pilotos Institucionales
CONTEXTO ESTRATEGICO
Fase 0 (Meses 1-3): Pilotar con 5-10 entidades PIIL. Gate criteria: Activation >40%, D30 >25%, NPS >40.
Kill criteria definidos: Si activation <20% o D30 <15% => PIVOTAR.
FALTA: Un sistema para gestionar el ciclo de vida completo del piloto: prospecto -> LOI -> onboarding -> feedback -> Go/No-Go.
Sin esto, la gestion de pilotos es manual en hojas de calculo. Inaceptable para demostrar profesionalismo a instituciones.

5.1 Modelo de Datos
5.1.1 Content Entity: pilot_program
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
label	VARCHAR(255)	Nombre: Piloto PIIL Cordoba Q2 2026
vertical_id	VARCHAR(32)	Vertical: empleabilidad
phase	ENUM	prospect|loi_sent|loi_signed|onboarding|active|evaluating|completed|cancelled
start_date	DATE	Fecha inicio prevista
end_date	DATE	Fecha fin prevista
target_tenants	INT	Objetivo de tenants: 5
target_users	INT	Objetivo de usuarios: 50
gate_activation	DECIMAL(5,2)	Umbral activation rate: 40.00
gate_d30_retention	DECIMAL(5,2)	Umbral D30: 25.00
gate_nps	DECIMAL(4,1)	Umbral NPS: 40.0
kill_activation	DECIMAL(5,2)	Kill criteria activation: 20.00
kill_d30_retention	DECIMAL(5,2)	Kill criteria D30: 15.00
kill_nps	DECIMAL(4,1)	Kill criteria NPS: 10.0
decision	ENUM	pending|go|no_go|pivot
decision_notes	TEXT	Notas de la decision Go/No-Go
owner_uid	INT	UID del responsable (Pepe)

5.1.2 Content Entity: pilot_tenant
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
pilot_program_id	INT FK	Referencia al programa piloto
tenant_id	INT FK	Referencia al Group/tenant
entity_name	VARCHAR(255)	Nombre entidad: Asociacion PIIL Cordoba
contact_name	VARCHAR(255)	Persona de contacto
contact_email	VARCHAR(255)	Email de contacto
contact_phone	VARCHAR(50)	Telefono
status	ENUM	prospect|contacted|interested|loi_sent|loi_signed|onboarding|active|churned
loi_sent_date	DATE	Fecha envio LOI
loi_signed_date	DATE	Fecha firma LOI
onboarded_date	DATE	Fecha onboarding completado
users_registered	INT	Usuarios registrados en la entidad
users_activated	INT	Usuarios que completaron activation
last_feedback_date	DATE	Ultima sesion de feedback
nps_individual	INT	NPS de esta entidad
notes	TEXT	Notas libres

5.1.3 Content Entity: pilot_feedback
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
pilot_tenant_id	INT FK	Referencia a pilot_tenant
feedback_date	DATE	Fecha de la sesion de feedback
feedback_type	ENUM	weekly_call|survey|support_ticket|spontaneous
satisfaction_score	INT (1-10)	Puntuacion de satisfaccion
issues_reported	JSON	Array de issues [{title, severity, status}]
features_requested	JSON	Array de features solicitadas
positive_highlights	TEXT	Lo que funciona bien (textual)
action_items	JSON	Acciones derivadas [{action, owner, deadline, status}]
recorded_by_uid	INT	UID de quien registro el feedback

5.2 Dashboard de Piloto
Ruta: /admin/jaraba/pilots/{pilot_program_id}
Bloques del dashboard:
Bloque	Contenido	Fuente de Datos
Status Board	Kanban de entidades por fase (prospect -> active)	pilot_tenant.status
KPI Gauges	Activation Rate, D30 Retention, NPS vs Gate Criteria	jaraba_product_analytics snapshots
Traffic Light	VERDE (gate met), AMARILLO (entre gate y kill), ROJO (kill criteria)	Calculo en tiempo real
Timeline	Cronograma del piloto con hitos	pilot_program + pilot_tenant dates
Feedback Log	Lista cronologica de feedback con issues abiertos	pilot_feedback
Go/No-Go Card	Boton de decision con checklist automatico	Evaluacion contra gates

5.3 ECA: Automatizaciones del Piloto
ID	Trigger	Accion
ECA-PILOT-001	pilot_tenant.status = onboarding + 48h sin actividad	Email recordatorio al contacto + alerta dashboard
ECA-PILOT-002	Viernes 17:00 durante piloto activo	Generar snapshot semanal + email resumen a owner
ECA-PILOT-003	NPS individual < kill_nps	Alerta ROJA en dashboard + crear tarea urgente
ECA-PILOT-004	Todas las pilot_tenant.status = active + 30 dias	Notificar que es momento de evaluar Go/No-Go
ECA-PILOT-005	pilot_program.decision = go	Crear plan de expansion Fase 1 + notificar equipo
 
6. GAP-5: jaraba_piil_bridge — Puente PIIL/STO y Metricas ESF
CONTEXTO ESTRATEGICO
Fase 0 depende de entidades PIIL como primeros pilotos. Estas entidades DEBEN reportar al STO (Servicio Telematico de Orientacion) del SAE.
El Manual STO v1.2 (13/05/2025) documenta: Fichas Tecnicas, Alta Participantes, Acompanamiento, DACI, Incidencias.
EXISTENTE: Empleabilidad entities (candidatos, ofertas, cursos). Impact Metrics (doc 24).
FALTA: Export de datos en formato compatible STO. Metricas especificas PIIL. Generacion de informes ESF.
Sin esto, las entidades PIIL tendran que introducir datos DOS VECES (plataforma + STO). Friction = abandono.

6.1 Resumen del Modulo
Campo	Valor
Nombre	jaraba_piil_bridge
Tipo	Modulo Drupal 11 custom
Dependencias	jaraba_core, jaraba_tenant, empleabilidad modules (08-24)
Responsabilidad	Export datos compatible STO, metricas PIIL, informes ESF
Estimacion	80-120 horas

6.2 Modelo de Datos
6.2.1 Content Entity: piil_program_config
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
tenant_id	INT FK	Tenant de la entidad PIIL
expediente_number	VARCHAR(50)	Numero expediente SAE: SC/ICJ/0050/2024
entity_cif	VARCHAR(20)	CIF de la entidad gestora
entity_name	VARCHAR(255)	Razon social
program_start	DATE	Fecha inicio actuacion
program_end	DATE	Fecha fin actuacion
target_participants	INT	Objetivo participantes totales
target_insertions	INT	Objetivo inserciones laborales
target_provinces	JSON	Provincias y cuotas: {'Cordoba': 200, 'Malaga': 150}
collective_type	VARCHAR(100)	Colectivo: Jovenes, Mayores 45, Discapacidad...
sto_credentials_encrypted	TEXT	Credenciales STO cifradas (AES-256 via getenv key)
auto_sync_enabled	BOOLEAN	Sincronizacion automatica con STO
last_sync_date	TIMESTAMP	Ultima sincronizacion exitosa

6.2.2 Content Entity: piil_participant
Extension de candidate_profile (doc 15) con campos PIIL especificos:
Campo	Tipo	Descripcion
id	INT AUTO_INCREMENT	ID
candidate_profile_id	INT FK	Referencia a candidate_profile (doc 15)
piil_program_id	INT FK	Programa PIIL asociado
sto_participant_id	VARCHAR(50)	ID del participante en STO (si sincronizado)
nie_nif	VARCHAR(20)	NIE/NIF del participante
province	VARCHAR(50)	Provincia asignada
enrollment_date	DATE	Fecha alta en programa
orientation_hours	DECIMAL(5,1)	Horas de orientacion completadas (min 10h PIIL)
training_hours	DECIMAL(5,1)	Horas de formacion completadas (min 50h PIIL)
accompaniment_hours	DECIMAL(5,1)	Horas de acompanamiento
insertion_status	ENUM	not_inserted|inserted_contract|inserted_self_employed
insertion_date	DATE	Fecha insercion laboral
insertion_contract_type	VARCHAR(50)	Tipo contrato: indefinido, temporal, formacion...
daci_signed	BOOLEAN	Declaracion Ausencia Conflicto Intereses firmada
daci_date	DATE	Fecha firma DACI
sync_status	ENUM	pending|synced|error
sync_error_message	TEXT	Mensaje de error de sincronizacion

6.3 Servicio de Export STO
<?php
// jaraba_piil_bridge/src/Service/StoExportService.php

namespace Drupal\jaraba_piil_bridge\Service;

class StoExportService implements StoExportServiceInterface {

  /**
   * Genera export en formato compatible con el STO del SAE.
   * Formato: Excel (.xlsx) con estructura de Ficha Tecnica.
   * Ref: Manual_Representante_Entidad_STO_INTEGRALES_ICJ_052025.pdf
   */
  public function exportFichaTecnica(
    int $piilProgramId
  ): ExportResult {
    $program = $this->loadProgram($piilProgramId);
    $participants = $this->loadParticipants($piilProgramId);
    
    $data = [
      'datos_entidad' => [
        'cif' => $program->getCif(),
        'razon_social' => $program->getEntityName(),
        'expediente' => $program->getExpedienteNumber(),
        'fecha_inicio' => $program->getProgramStart(),
        'fecha_fin' => $program->getProgramEnd(),
        'objetivo_participantes' => $program->getTargetParticipants(),
        'objetivo_inserciones' => $program->getTargetInsertions(),
      ],
      'provincias' => $program->getTargetProvinces(),
      'personal_tecnico' => $this->loadStaffForProgram($piilProgramId),
      'participantes' => array_map(
        fn($p) => $this->mapParticipantToStoFormat($p),
        $participants
      ),
    ];
    
    return $this->excelExporter->generateStoFormat($data);
  }

  /**
   * Genera informe ESF (Fondo Social Europeo) para justificacion.
   */
  public function exportInformeESF(
    int $piilProgramId,
    string $periodStart,
    string $periodEnd
  ): ExportResult {
    $metrics = [
      'participantes_atendidos' => $this->countParticipants(
        $piilProgramId, $periodStart, $periodEnd),
      'inserciones_logradas' => $this->countInsertions(
        $piilProgramId, $periodStart, $periodEnd),
      'tasa_insercion' => $this->calculateInsertionRate($piilProgramId),
      'horas_orientacion_total' => $this->sumOrientationHours($piilProgramId),
      'horas_formacion_total' => $this->sumTrainingHours($piilProgramId),
      'distribucion_provincial' => $this->getProvincialDistribution($piilProgramId),
      'distribucion_colectivo' => $this->getCollectiveDistribution($piilProgramId),
      'coste_por_participante' => $this->calculateCostPerParticipant($piilProgramId),
      'coste_por_insercion' => $this->calculateCostPerInsertion($piilProgramId),
    ];
    
    return $this->pdfExporter->generateEsfReport($metrics, $program);
  }
}

6.4 Dashboard PIIL
Ruta: /admin/jaraba/piil/{piil_program_id}
Bloque	Metrica	Visualizacion
Participantes	Total vs objetivo, nuevos esta semana	Gauge + progress bar
Inserciones	Logradas vs objetivo, tasa de insercion %	Gauge + trend line
Horas Orientacion	Total horas impartidas vs requeridas (10h PIIL)	Stacked bar
Horas Formacion	Total horas completadas vs requeridas (50h PIIL)	Stacked bar
Distribucion Provincial	Participantes por provincia vs cuota	Mapa + tabla
DACI Status	% participantes con DACI firmada	Donut chart
Sync STO	Estado sincronizacion, errores pendientes	Status cards
Timeline Programa	Hitos del programa con fechas clave	Gantt simplificado

6.5 API REST
Metodo	Endpoint	Descripcion
GET	/api/v1/piil/programs	Listar programas PIIL del tenant
GET	/api/v1/piil/programs/{id}	Detalle de programa con metricas
GET	/api/v1/piil/programs/{id}/participants	Participantes con filtros
POST	/api/v1/piil/programs/{id}/participants	Alta participante
PATCH	/api/v1/piil/participants/{id}	Actualizar participante
POST	/api/v1/piil/programs/{id}/export/ficha-tecnica	Export Ficha Tecnica STO
POST	/api/v1/piil/programs/{id}/export/informe-esf	Export Informe ESF PDF
POST	/api/v1/piil/programs/{id}/sync-sto	Forzar sincronizacion STO
GET	/api/v1/piil/programs/{id}/metrics	Metricas PIIL en tiempo real
 
7. Roadmap de Implementacion
PRINCIPIO: FOCO ANTES QUE AMPLITUD
Los 5 modulos NO se implementan en paralelo. Se priorizan por dependencia con Fase 0.
Semana 1-4: GAP-3 (feature_gates) + GAP-1 (vertical_brand) = base comercial
Semana 5-8: GAP-2 (product_analytics) + GAP-4 (pilot_manager) = instrumentacion piloto
Semana 9-12: GAP-5 (piil_bridge) = integracion institucional

Sprint	Semanas	Modulo	Entregables	Horas
Sprint 1	1-2	jaraba_feature_gates	Entidad feature_gate + FeatureGateService + Twig extension + tests unitarios	40h
Sprint 2	3-4	jaraba_vertical_brand	Config entity + VerticalBrandService + theme integration + YAML empleabilidad	50h
Sprint 3	3-4	jaraba_feature_gates	Config para Empleabilidad completa + UI admin + quota counters + tests kernel	40h
Sprint 4	5-6	jaraba_vertical_brand	Landing controller + subdomain routing + Schema.org + tests funcionales	50h
Sprint 5	5-6	jaraba_product_analytics	Entidad product_event + ProductEventTracker + queue worker + JS SDK	50h
Sprint 6	7-8	jaraba_product_analytics	MetricSnapshotCalculator + dashboard pre-PMF + APIs + tests	50h
Sprint 7	7-8	jaraba_pilot_manager	Entidades pilot_program/tenant/feedback + dashboard Kanban	40h
Sprint 8	9-10	jaraba_pilot_manager	ECAs automatizacion + Go/No-Go evaluator + tests + integracion con product_analytics	40h
Sprint 9	9-10	jaraba_piil_bridge	Entidades piil_program/participant + StoExportService	40h
Sprint 10	11-12	jaraba_piil_bridge	Dashboard PIIL + ESF report + sync + APIs + tests + QA integral	50h

7.1 Estimacion Total
Modulo	Horas Min	Horas Max	Complejidad	Riesgo
jaraba_feature_gates	80	100	Media	Bajo (patron conocido)
jaraba_vertical_brand	120	160	Alta	Medio (requiere DNS + theme)
jaraba_product_analytics	100	140	Alta	Medio (rendimiento con volumen)
jaraba_pilot_manager	60	80	Media-Baja	Bajo (CRUD + dashboard)
jaraba_piil_bridge	80	120	Alta	Alto (formato STO no documentado publicamente)
TOTAL	440	600	-	-

8. Mapa de Dependencias e Integracion
Diagrama de dependencias entre los 5 modulos nuevos y los modulos existentes:
                    ┌──────────────────────────┐
                    │    jaraba_core            │
                    │  jaraba_tenant            │
                    │  ecosistema_jaraba_theme  │
                    └──────────┬───────────────┘
                               │
              ┌────────────────┼────────────────────┐
              │                │                    │
   ┌──────────▼──────┐  ┌─────▼──────────┐  ┌─────▼──────────────┐
   │ jaraba_feature  │  │ jaraba_vertical│  │ jaraba_product     │
   │ _gates          │  │ _brand         │  │ _analytics         │
   │ (GAP-3)         │  │ (GAP-1)        │  │ (GAP-2)            │
   └──────┬──────────┘  └────────────────┘  └──────┬─────────────┘
          │                                        │
          │         ┌──────────────────┐           │
          └────────►│ jaraba_pilot     │◄──────────┘
                    │ _manager (GAP-4) │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │ jaraba_piil      │
                    │ _bridge (GAP-5)  │
                    └──────────────────┘

  Modulos existentes a HABILITAR en paralelo:
  jaraba_support + jaraba_sla + jaraba_notifications
  jaraba_onboarding (configurar per-vertical)
  jaraba_stripe (Stripe Billing con planes)

8.1 Integracion con Modulos Existentes
Modulo Nuevo	Se Integra Con	Tipo Integracion	Detalle
jaraba_vertical_brand	jaraba_tenant (07)	Service injection	Usa TenantContextService para resolver vertical
jaraba_vertical_brand	jaraba_onboarding (110)	Config reference	Cada brand referencia un onboarding_template_id
jaraba_vertical_brand	ecosistema_jaraba_theme (05)	CSS variables	Inyecta --ej-brand-* via hook_preprocess_html
jaraba_vertical_brand	jaraba_geo (164)	Schema.org	Genera JSON-LD especifico por sub-marca
jaraba_product_analytics	jaraba_onboarding (110)	Event tracking	Track eventos de onboarding completion
jaraba_product_analytics	jaraba_foc_metrics	Data feed	Alimenta metricas de producto al FOC
jaraba_feature_gates	jaraba_stripe (134)	Plan sync	Lee plan_type de Stripe subscription
jaraba_feature_gates	RBAC (04)	Complemento	Feature gates + RBAC = control completo
jaraba_pilot_manager	jaraba_product_analytics	Metrics read	Lee snapshots para evaluar gate criteria
jaraba_piil_bridge	Empleabilidad (08-24)	Entity extend	Extiende candidate_profile con campos PIIL
jaraba_piil_bridge	Impact Metrics (24)	Report extend	Anade metricas ESF a informes de impacto
 
9. Estrategia de Testing
Siguiendo el estandar del proyecto: PHPStan Level 6, cobertura minima 80%, PHPCS Drupal.

Modulo	Unit Tests	Kernel Tests	Functional Tests	Cobertura Min
jaraba_feature_gates	FeatureGateServiceTest, FeatureAccessResultTest, QuotaCounterTest	FeatureGateConfigTest, UsageCounterEntityTest	FeatureGateControllerTest, TwigExtensionTest	85%
jaraba_vertical_brand	VerticalBrandServiceTest, RevelationLevelTest, NavigationFilterTest	BrandConfigEntityTest, BrandPageEntityTest	LandingControllerTest, SubdomainRoutingTest, SchemaOrgTest	80%
jaraba_product_analytics	ProductEventTrackerTest, MetricCalculatorTest, RetentionCalcTest	ProductEventEntityTest, SnapshotEntityTest	AnalyticsDashboardTest, EventApiTest, JsSdkIntegrationTest	80%
jaraba_pilot_manager	PilotGateEvaluatorTest, PilotStatusTransitionTest	PilotProgramEntityTest, PilotTenantEntityTest, PilotFeedbackEntityTest	PilotDashboardTest, GoNoGoFlowTest	80%
jaraba_piil_bridge	StoExportServiceTest, EsfReportServiceTest, ParticipantMapperTest	PiilProgramEntityTest, PiilParticipantEntityTest	PiilDashboardTest, ExportApiTest, SyncServiceTest	80%

10. Checklist de Verificacion RUNTIME-VERIFY-001
Cada modulo debe pasar la verificacion end-to-end antes de merge:

Check	Descripcion	Herramienta
PHP compila sin errores	php -l en todos los archivos .php del modulo	CI: lint-php
PHPStan Level 6 pasa	0 errores en analisis estatico	CI: phpstan
PHPCS Drupal/DrupalPractice	0 violaciones de coding standards	CI: phpcs
Tests pasan	phpunit --group=modulo_name	CI: phpunit
Cobertura >= 80%	phpunit --coverage-html	CI: coverage
Entidades instalables	drush en && drush updb sin errores	Manual/CI
Config exportable	drush cex sin diff inesperado	Manual
Permisos correctos	Roles pueden/no pueden segun spec	Functional test
Multi-tenant aislado	Datos de tenant A NO visibles en tenant B	Kernel test
Frontend renderiza	CSS variables aplicadas, JS sin errores consola	Manual + Cypress
API responde	Endpoints devuelven 200/403 segun permisos	Functional test
Cache funciona	Redis tags se invalidan correctamente	Manual
Rendimiento OK	< 200ms response time en endpoints principales	Manual/k6

11. Nota Final
FILOSOFIA SIN HUMO
Estas 5 especificaciones son el MINIMO necesario para ejecutar la Fase 0 del Plan Estrategico.
No son 'nice-to-have'. Sin jaraba_vertical_brand no hay Go-to-Market. Sin jaraba_product_analytics no hay Go/No-Go.
Sin jaraba_feature_gates no hay diferenciacion comercial. Sin jaraba_pilot_manager no hay gestion profesional.
Sin jaraba_piil_bridge no hay propuesta de valor para entidades PIIL.
Estimacion total: 440-600 horas. Timeline: 12 semanas con 1 senior developer dedicado.
Prioridad: BLOQUEANTE para lanzamiento. Implementar ANTES de contactar primer piloto.
--- Fin del Documento ---
Jaraba Impact Platform (c) 2026 - Plataforma de Ecosistemas Digitales S.L.
