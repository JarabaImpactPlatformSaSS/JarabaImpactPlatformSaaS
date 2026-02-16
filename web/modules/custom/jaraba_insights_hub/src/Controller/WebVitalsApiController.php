<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para Core Web Vitals (RUM).
 *
 * PROPOSITO:
 * Expone endpoints para recopilar datos de Real User Monitoring (RUM)
 * desde los navegadores y para consultar metricas agregadas.
 *
 * FUNCIONALIDADES:
 * - POST para recopilar metricas individuales (anonimo, rate limited)
 * - GET para obtener resumen agregado de Web Vitals
 * - Validacion de API key o CSRF token en recopilacion
 * - Clasificacion automatica por umbrales de Google (good/needs-improvement/poor)
 *
 * RUTAS:
 * - POST /api/v1/insights/web-vitals -> collect()
 * - GET /api/v1/insights/web-vitals/summary -> summary()
 *
 * @package Drupal\jaraba_insights_hub\Controller
 */
class WebVitalsApiController extends ControllerBase {

  /**
   * El servicio recopilador de Web Vitals.
   *
   * @var \Drupal\jaraba_insights_hub\Service\WebVitalsCollectorService
   */
  protected $collector;

  /**
   * El servicio agregador de Web Vitals.
   *
   * @var \Drupal\jaraba_insights_hub\Service\WebVitalsAggregatorService
   */
  protected $aggregator;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * La factoria de configuracion.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->collector = $container->get('jaraba_insights_hub.web_vitals_collector');
    $instance->aggregator = $container->get('jaraba_insights_hub.web_vitals_aggregator');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Recopila datos de Core Web Vitals desde el navegador.
   *
   * POST /api/v1/insights/web-vitals
   *
   * Endpoint anonimo que recibe metricas individuales de RUM.
   * Valida la peticion mediante API key en header X-Insights-Key
   * o mediante token CSRF estandar de Drupal.
   *
   * Body JSON esperado:
   * {
   *   "metrics": [
   *     {
   *       "name": "LCP|INP|CLS|FCP|TTFB",
   *       "value": 1234.56,
   *       "rating": "good|needs-improvement|poor",
   *       "page_url": "/ruta/pagina",
   *       "device_type": "desktop|mobile|tablet",
   *       "connection_type": "4g|3g|wifi",
   *       "navigation_type": "navigate|reload|back_forward",
   *       "visitor_id": "abc123"
   *     }
   *   ],
   *   "tenant_id": 1
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {processed: int}}.
   */
  public function collect(Request $request): JsonResponse {
    try {
      // Validar autenticacion: API key o CSRF token.
      if (!$this->validateCollectionRequest($request)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Unauthorized',
        ], 401);
      }

      // Parsear body.
      $content = json_decode($request->getContent(), TRUE);
      if (!$content || empty($content['metrics']) || !is_array($content['metrics'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere un array de metricas.'),
        ], 400);
      }

      $tenant_id = (int) ($content['tenant_id'] ?? 0);
      $metrics = $content['metrics'];

      // Validar limite maximo de metricas por peticion (proteccion contra abuso).
      if (count($metrics) > 50) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Maximo 50 metricas por peticion.'),
        ], 400);
      }

      // Validar cada metrica.
      $allowed_metrics = ['LCP', 'INP', 'CLS', 'FCP', 'TTFB'];
      $valid_metrics = [];

      foreach ($metrics as $metric) {
        if (
          empty($metric['name']) ||
          !in_array($metric['name'], $allowed_metrics, TRUE) ||
          !isset($metric['value']) ||
          !is_numeric($metric['value'])
        ) {
          continue;
        }

        $valid_metrics[] = [
          'name' => $metric['name'],
          'value' => (float) $metric['value'],
          'rating' => $metric['rating'] ?? $this->calculateRating($metric['name'], (float) $metric['value']),
          'page_url' => mb_substr($metric['page_url'] ?? '', 0, 500),
          'device_type' => $metric['device_type'] ?? 'desktop',
          'connection_type' => $metric['connection_type'] ?? '',
          'navigation_type' => $metric['navigation_type'] ?? 'navigate',
          'visitor_id' => mb_substr($metric['visitor_id'] ?? '', 0, 64),
        ];
      }

      if (empty($valid_metrics)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Ninguna metrica valida en la peticion.'),
        ], 400);
      }

      // Procesar las metricas.
      $processed = $this->collector->processMetrics($tenant_id, $valid_metrics);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'processed' => $processed,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al recopilar Web Vitals: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al procesar las metricas.'),
      ], 500);
    }
  }

  /**
   * Devuelve un resumen agregado de Core Web Vitals.
   *
   * GET /api/v1/insights/web-vitals/summary
   *
   * Query params:
   * - date_range: 7d|30d|90d (default: 30d)
   * - device: desktop|mobile|tablet (opcional)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {...}}.
   */
  public function summary(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      // Parsear parametros.
      $date_range = $request->query->get('date_range', '30d');
      $allowed_ranges = ['7d', '30d', '90d'];
      if (!in_array($date_range, $allowed_ranges, TRUE)) {
        $date_range = '30d';
      }

      $device = $request->query->get('device');
      $allowed_devices = ['desktop', 'mobile', 'tablet'];
      if ($device !== NULL && !in_array($device, $allowed_devices, TRUE)) {
        $device = NULL;
      }

      $vitals_summary = $this->aggregator->getAggregatedVitals($tenant_id, $date_range, $device);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $vitals_summary,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al obtener resumen de Web Vitals: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener resumen de Web Vitals.'),
      ], 500);
    }
  }

  /**
   * Valida la peticion de recopilacion de metricas.
   *
   * Acepta dos mecanismos de autenticacion:
   * 1. Header X-Insights-Key con la API key configurada en settings
   * 2. Token CSRF estandar de Drupal (X-CSRF-Token)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return bool
   *   TRUE si la peticion es valida.
   */
  protected function validateCollectionRequest(Request $request): bool {
    // Opcion 1: API key en header.
    $api_key = $request->headers->get('X-Insights-Key');
    if ($api_key) {
      $config = $this->configFactory->get('jaraba_insights_hub.settings');
      $configured_key = $config->get('web_vitals_api_key');
      if ($configured_key && hash_equals($configured_key, $api_key)) {
        return TRUE;
      }
    }

    // Opcion 2: CSRF token de Drupal (para peticiones desde el frontend propio).
    $csrf_token = $request->headers->get('X-CSRF-Token');
    if ($csrf_token) {
      /** @var \Drupal\Core\Access\CsrfTokenGenerator $csrf_generator */
      $csrf_generator = \Drupal::service('csrf_token');
      if ($csrf_generator->validate($csrf_token, 'insights-web-vitals')) {
        return TRUE;
      }
    }

    // Opcion 3: Permitir si el Origin/Referer coincide con el sitio actual
    // (proteccion basica CORS para scripts inline propios).
    $origin = $request->headers->get('Origin', $request->headers->get('Referer', ''));
    if ($origin) {
      $site_url = $request->getSchemeAndHttpHost();
      if (str_starts_with($origin, $site_url)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Calcula el rating de una metrica basado en umbrales de Google.
   *
   * @param string $metric_name
   *   Nombre de la metrica (LCP, INP, CLS, FCP, TTFB).
   * @param float $value
   *   Valor medido.
   *
   * @return string
   *   Rating: 'good', 'needs-improvement' o 'poor'.
   */
  protected function calculateRating(string $metric_name, float $value): string {
    // Umbrales oficiales de Google (mayo 2024).
    $thresholds = [
      'LCP' => ['good' => 2500, 'poor' => 4000],
      'INP' => ['good' => 200, 'poor' => 500],
      'CLS' => ['good' => 0.1, 'poor' => 0.25],
      'FCP' => ['good' => 1800, 'poor' => 3000],
      'TTFB' => ['good' => 800, 'poor' => 1800],
    ];

    if (!isset($thresholds[$metric_name])) {
      return 'needs-improvement';
    }

    $t = $thresholds[$metric_name];

    if ($value <= $t['good']) {
      return 'good';
    }

    if ($value > $t['poor']) {
      return 'poor';
    }

    return 'needs-improvement';
  }

}
