<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// AUDIT-PERF-N06: StreamedResponse to avoid buffering entire CSV in memory.
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controlador de exportacion de datos de analytics (G116-5).
 *
 * PROPOSITO:
 * Proporciona endpoints REST para descargar datos de analytics
 * en formato CSV y Excel (CSV con BOM UTF-8 para compatibilidad).
 *
 * ENDPOINTS:
 * - GET /api/v1/analytics/export/csv   - Exportar como CSV.
 * - GET /api/v1/analytics/export/excel - Exportar como Excel (CSV con BOM).
 *
 * PARAMETROS QUERY:
 * - tenant_id   (requerido) - ID del tenant.
 * - date_from   (opcional)  - Fecha inicio (Y-m-d). Por defecto: 30 dias atras.
 * - date_to     (opcional)  - Fecha fin (Y-m-d). Por defecto: hoy.
 * - metric_type (opcional)  - Filtrar por tipo de evento.
 *
 * SEGURIDAD:
 * - Requiere permiso 'export analytics data'.
 * - Los datos se filtran estrictamente por tenant_id.
 */
class AnalyticsExportController extends ControllerBase {

  /**
   * Cabeceras CSV comunes para la exportacion de eventos.
   *
   * @var string[]
   */
  protected const CSV_HEADERS = [
    'ID',
    'Fecha',
    'Tipo de Evento',
    'URL de Pagina',
    'Dispositivo',
    'Navegador',
    'Sistema Operativo',
    'Pais',
    'Region',
    'UTM Source',
    'UTM Medium',
    'UTM Campaign',
    'Referrer',
    'Session ID',
  ];

  /**
   * Conexion a base de datos.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Canal de log.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor del controlador de exportacion.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Conexion a base de datos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
   *   Servicio de contexto de tenant.
   */
  public function __construct(Connection $database, LoggerInterface $logger, TenantContextService $tenant_context) {
    $this->database = $database;
    $this->logger = $logger;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')->get('jaraba_analytics'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * GET /api/v1/analytics/export/csv.
   *
   * Exporta eventos de analytics como archivo CSV descargable.
   *
   * AUDIT-PERF-N06: Replaced in-memory buffer with StreamedResponse.
   * Rows are written directly to php://output in batches, keeping
   * memory usage O(batch_size) instead of O(total_rows).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   StreamedResponse con contenido CSV y cabeceras de descarga.
   */
  public function exportCsv(Request $request): Response {
    $params = $this->extractParams($request);

    if ($params['error']) {
      return new Response($params['error'], 400, ['Content-Type' => 'text/plain']);
    }

    $filename = sprintf(
      'analytics_export_%d_%s_%s.csv',
      $params['tenant_id'],
      $params['date_from'],
      $params['date_to']
    );

    $this->logger->info('Exportacion CSV iniciada para tenant @tid.', [
      '@tid' => $params['tenant_id'],
    ]);

    // AUDIT-PERF-N06: Stream CSV rows directly to output, never buffer all in memory.
    return $this->buildStreamedCsvResponse($params, ',', $filename, 'text/csv; charset=utf-8');
  }

  /**
   * GET /api/v1/analytics/export/excel.
   *
   * Exporta eventos de analytics como archivo compatible con Excel.
   * Utiliza CSV con BOM UTF-8 y separador punto y coma para
   * compatibilidad nativa con Microsoft Excel.
   *
   * AUDIT-PERF-N06: Replaced in-memory buffer with StreamedResponse.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   StreamedResponse con contenido CSV Excel-compatible y cabeceras de descarga.
   */
  public function exportExcel(Request $request): Response {
    $params = $this->extractParams($request);

    if ($params['error']) {
      return new Response($params['error'], 400, ['Content-Type' => 'text/plain']);
    }

    $filename = sprintf(
      'analytics_export_%d_%s_%s.xlsx.csv',
      $params['tenant_id'],
      $params['date_from'],
      $params['date_to']
    );

    $this->logger->info('Exportacion Excel iniciada para tenant @tid.', [
      '@tid' => $params['tenant_id'],
    ]);

    // AUDIT-PERF-N06: Stream CSV rows directly to output with BOM prefix.
    return $this->buildStreamedCsvResponse($params, ';', $filename, 'application/vnd.ms-excel; charset=utf-8', TRUE);
  }

  /**
   * Extrae y valida los parametros de la peticion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Peticion HTTP.
   *
   * @return array
   *   Array con tenant_id, date_from, date_to, metric_type y error.
   */
  protected function extractParams(Request $request): array {
    $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

    if (!$tenantId || !is_numeric($tenantId)) {
      return [
        'tenant_id' => 0,
        'date_from' => '',
        'date_to' => '',
        'metric_type' => '',
        'error' => 'El parametro tenant_id es obligatorio y debe ser numerico.',
      ];
    }

    $dateFrom = $request->query->get('date_from', date('Y-m-d', strtotime('-30 days')));
    $dateTo = $request->query->get('date_to', date('Y-m-d'));
    $metricType = $request->query->get('metric_type', '');

    // Validar formato de fechas.
    if (!$this->isValidDate($dateFrom) || !$this->isValidDate($dateTo)) {
      return [
        'tenant_id' => (int) $tenantId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'metric_type' => $metricType,
        'error' => 'Las fechas deben tener formato Y-m-d (ej: 2026-01-15).',
      ];
    }

    return [
      'tenant_id' => (int) $tenantId,
      'date_from' => $dateFrom,
      'date_to' => $dateTo,
      'metric_type' => $metricType,
      'error' => NULL,
    ];
  }

  /**
   * Consulta eventos de analytics filtrados por los parametros dados.
   *
   * AUDIT-PERF-N06: Returns a Generator that yields rows one-by-one from
   * the DB cursor. Combined with StreamedResponse, this keeps memory at
   * O(1) per row instead of O(N) for the full result set.
   *
   * @param array $params
   *   Parametros extraidos (tenant_id, date_from, date_to, metric_type).
   *
   * @return \Generator
   *   Generator que produce filas de datos de eventos una a una.
   */
  protected function queryEvents(array $params): \Generator {
    $startTs = strtotime($params['date_from'] . ' 00:00:00');
    $endTs = strtotime($params['date_to'] . ' 23:59:59');

    $query = $this->database->select('analytics_event', 'ae')
      ->fields('ae', [
        'id',
        'created',
        'event_type',
        'page_url',
        'device_type',
        'browser',
        'os',
        'country',
        'region',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'referrer',
        'session_id',
      ])
      ->condition('ae.tenant_id', $params['tenant_id'])
      ->condition('ae.created', $startTs, '>=')
      ->condition('ae.created', $endTs, '<=')
      ->orderBy('ae.created', 'DESC');

    // Filtrar por tipo de evento si se especifica.
    if (!empty($params['metric_type'])) {
      $query->condition('ae.event_type', $params['metric_type']);
    }

    // AUDIT-PERF-N06: Limitar filas y usar generator para no cargar todo en memoria.
    $query->range(0, 50000);

    $result = $query->execute();

    // Yield fila por fila desde el cursor DB (memoria O(1) en lugar de O(N)).
    return (function () use ($result) {
      foreach ($result as $row) {
        yield [
          'id' => $row->id,
          'created' => date('Y-m-d H:i:s', (int) $row->created),
          'event_type' => $row->event_type ?? '',
          'page_url' => $row->page_url ?? '',
          'device_type' => $row->device_type ?? '',
          'browser' => $row->browser ?? '',
          'os' => $row->os ?? '',
          'country' => $row->country ?? '',
          'region' => $row->region ?? '',
          'utm_source' => $row->utm_source ?? '',
          'utm_medium' => $row->utm_medium ?? '',
          'utm_campaign' => $row->utm_campaign ?? '',
          'referrer' => $row->referrer ?? '',
          'session_id' => $row->session_id ?? '',
        ];
      }
    })();
  }

  /**
   * Builds a StreamedResponse that writes CSV rows directly to php://output.
   *
   * AUDIT-PERF-N06: Replaces the old buildCsvContent() which accumulated
   * all rows (up to 50K) in a php://temp buffer and then read everything
   * into a single string (8-15MB RAM). This version streams rows in
   * batches of 500, keeping peak memory usage under 1MB regardless of
   * total row count.
   *
   * @param array $params
   *   Validated request parameters (tenant_id, date_from, date_to, metric_type).
   * @param string $separator
   *   Column separator (',' for standard CSV, ';' for Excel).
   * @param string $filename
   *   Filename for the Content-Disposition header.
   * @param string $contentType
   *   MIME type for the Content-Type header.
   * @param bool $withBom
   *   Whether to prepend UTF-8 BOM (for Excel compatibility).
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   *   Streamed response that writes CSV directly to the client.
   */
  protected function buildStreamedCsvResponse(array $params, string $separator, string $filename, string $contentType, bool $withBom = FALSE): StreamedResponse {
    // AUDIT-PERF-N06: Batch size for flushing output buffer.
    $batchSize = 500;

    $response = new StreamedResponse(function () use ($params, $separator, $withBom, $batchSize) {
      $output = fopen('php://output', 'w');

      // BOM UTF-8 para que Excel detecte la codificacion correctamente.
      if ($withBom) {
        fwrite($output, "\xEF\xBB\xBF");
      }

      // Escribir cabeceras.
      fputcsv($output, self::CSV_HEADERS, $separator);

      // AUDIT-PERF-N06: Iterate the generator from queryEvents() and
      // flush every $batchSize rows to keep memory constant at O(batch).
      $rowCount = 0;
      $rows = $this->queryEvents($params);
      foreach ($rows as $row) {
        fputcsv($output, array_values($row), $separator);
        $rowCount++;

        if ($rowCount % $batchSize === 0) {
          flush();
        }
      }

      fclose($output);

      $this->logger->info('Exportacion streaming completada para tenant @tid: @count registros.', [
        '@tid' => $params['tenant_id'],
        '@count' => $rowCount,
      ]);
    });

    $response->headers->set('Content-Type', $contentType);
    $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
    $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
    $response->headers->set('Pragma', 'no-cache');

    return $response;
  }

  /**
   * Valida que una cadena tenga formato de fecha Y-m-d.
   *
   * @param string $date
   *   Cadena de fecha a validar.
   *
   * @return bool
   *   TRUE si el formato es valido.
   */
  protected function isValidDate(string $date): bool {
    $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
    return $dateObj !== FALSE && $dateObj->format('Y-m-d') === $date;
  }

}
