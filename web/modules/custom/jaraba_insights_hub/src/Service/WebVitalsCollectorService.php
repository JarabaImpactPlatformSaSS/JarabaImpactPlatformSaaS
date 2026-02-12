<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de recoleccion de metricas Core Web Vitals via RUM.
 *
 * Recibe datos de metricas de rendimiento enviados desde el navegador
 * del usuario (Real User Monitoring) y los almacena como entidades
 * WebVitalsMetric. Cada metrica individual se persiste con su
 * contexto completo (dispositivo, conexion, navegador).
 *
 * ARQUITECTURA:
 * - Recibe datos via endpoint REST (beacon/fetch).
 * - Valida campos requeridos y rangos validos.
 * - Calcula rating automaticamente segun umbrales de Google.
 * - Multi-tenant: resuelve tenant_id desde TenantContextService.
 */
class WebVitalsCollectorService {

  /**
   * Metricas validas de Core Web Vitals.
   */
  protected const VALID_METRICS = ['LCP', 'INP', 'CLS', 'FCP', 'TTFB'];

  /**
   * Tipos de dispositivo validos.
   */
  protected const VALID_DEVICES = ['desktop', 'mobile', 'tablet'];

  /**
   * Umbrales de rendimiento por metrica.
   *
   * Formato: [umbral_bueno, umbral_pobre].
   * - Valor <= umbral_bueno: 'good'
   * - Valor <= umbral_pobre: 'needs-improvement'
   * - Valor > umbral_pobre: 'poor'
   */
  protected const THRESHOLDS = [
    'LCP' => [2500, 4000],
    'INP' => [200, 500],
    'CLS' => [0.1, 0.25],
    'FCP' => [1800, 3000],
    'TTFB' => [800, 1800],
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto del tenant actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Recolecta y almacena una metrica Web Vital desde datos RUM.
   *
   * Valida los datos recibidos, calcula el rating y persiste
   * como entidad WebVitalsMetric asociada al tenant actual.
   *
   * @param array $data
   *   Datos de la metrica. Claves esperadas:
   *   - page_url: (string) URL de la pagina.
   *   - metric_name: (string) LCP, INP, CLS, FCP o TTFB.
   *   - metric_value: (float) Valor medido.
   *   - device_type: (string) desktop, mobile o tablet.
   *   - connection_type: (string) 4g, 3g, wifi, etc.
   *   - browser: (string) Identificador del navegador.
   *
   * @return bool
   *   TRUE si la metrica se almaceno correctamente, FALSE en caso contrario.
   */
  public function collect(array $data): bool {
    if (!$this->validateMetric($data)) {
      return FALSE;
    }

    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      $this->logger->warning('Web Vitals: no se pudo resolver el tenant actual.');
      return FALSE;
    }

    $tenantId = (int) $tenant->id();
    $metricName = $data['metric_name'];
    $metricValue = (float) $data['metric_value'];
    $rating = $this->calculateRating($metricName, $metricValue);

    try {
      $storage = $this->entityTypeManager->getStorage('web_vitals_metric');
      $entity = $storage->create([
        'tenant_id' => $tenantId,
        'page_url' => $data['page_url'],
        'metric_name' => $metricName,
        'metric_value' => $metricValue,
        'metric_rating' => $rating,
        'device_type' => $data['device_type'] ?? NULL,
        'connection_type' => $data['connection_type'] ?? NULL,
        'navigation_type' => $data['navigation_type'] ?? NULL,
        'visitor_id' => $data['visitor_id'] ?? NULL,
      ]);
      $entity->save();

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error almacenando Web Vital @metric para tenant @tenant: @error', [
        '@metric' => $metricName,
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Valida que los datos de una metrica sean correctos.
   *
   * Verifica campos requeridos (page_url, metric_name, metric_value),
   * que metric_name sea una metrica valida y que metric_value este
   * dentro de un rango aceptable.
   *
   * @param array $data
   *   Datos de la metrica a validar.
   *
   * @return bool
   *   TRUE si los datos son validos.
   */
  public function validateMetric(array $data): bool {
    // Verificar campos requeridos.
    if (empty($data['page_url']) || empty($data['metric_name'])) {
      $this->logger->debug('Web Vitals validacion: faltan campos requeridos (page_url, metric_name).');
      return FALSE;
    }

    if (!isset($data['metric_value'])) {
      $this->logger->debug('Web Vitals validacion: falta metric_value.');
      return FALSE;
    }

    // Verificar metrica valida.
    if (!in_array($data['metric_name'], self::VALID_METRICS, TRUE)) {
      $this->logger->debug('Web Vitals validacion: metrica no valida "@metric".', [
        '@metric' => $data['metric_name'],
      ]);
      return FALSE;
    }

    // Verificar valor numerico y no negativo.
    $value = (float) $data['metric_value'];
    if ($value < 0) {
      $this->logger->debug('Web Vitals validacion: metric_value negativo (@value).', [
        '@value' => $value,
      ]);
      return FALSE;
    }

    // Verificar rangos razonables por metrica (evitar datos basura).
    // LCP/INP/FCP/TTFB en ms, CLS es un ratio sin unidad.
    $maxValues = [
      'LCP' => 60000,
      'INP' => 60000,
      'CLS' => 100,
      'FCP' => 60000,
      'TTFB' => 60000,
    ];

    $metricName = $data['metric_name'];
    if ($value > ($maxValues[$metricName] ?? 60000)) {
      $this->logger->debug('Web Vitals validacion: valor fuera de rango @metric=@value.', [
        '@metric' => $metricName,
        '@value' => $value,
      ]);
      return FALSE;
    }

    // Verificar device_type si se proporciona.
    if (!empty($data['device_type']) && !in_array($data['device_type'], self::VALID_DEVICES, TRUE)) {
      $this->logger->debug('Web Vitals validacion: device_type no valido "@device".', [
        '@device' => $data['device_type'],
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Calcula el rating de una metrica segun umbrales de Google.
   *
   * @param string $metricName
   *   Nombre de la metrica (LCP, INP, CLS, FCP, TTFB).
   * @param float $value
   *   Valor medido.
   *
   * @return string
   *   Rating: 'good', 'needs-improvement' o 'poor'.
   */
  protected function calculateRating(string $metricName, float $value): string {
    $thresholds = self::THRESHOLDS[$metricName] ?? [2500, 4000];

    if ($value <= $thresholds[0]) {
      return 'good';
    }

    if ($value <= $thresholds[1]) {
      return 'needs-improvement';
    }

    return 'poor';
  }

}
