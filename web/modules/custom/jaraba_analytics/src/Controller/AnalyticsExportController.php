<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
   * Constructor del controlador de exportacion.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Conexion a base de datos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   */
  public function __construct(Connection $database, LoggerInterface $logger) {
    $this->database = $database;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('logger.factory')->get('jaraba_analytics'),
    );
  }

  /**
   * GET /api/v1/analytics/export/csv.
   *
   * Exporta eventos de analytics como archivo CSV descargable.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta con contenido CSV y cabeceras de descarga.
   */
  public function exportCsv(Request $request): Response {
    $params = $this->extractParams($request);

    if ($params['error']) {
      return new Response($params['error'], 400, ['Content-Type' => 'text/plain']);
    }

    $rows = $this->queryEvents($params);

    $csv = $this->buildCsvContent($rows, ',');

    $filename = sprintf(
      'analytics_export_%d_%s_%s.csv',
      $params['tenant_id'],
      $params['date_from'],
      $params['date_to']
    );

    $this->logger->info('Exportacion CSV generada para tenant @tid: @count registros.', [
      '@tid' => $params['tenant_id'],
      '@count' => count($rows),
    ]);

    return new Response($csv, 200, [
      'Content-Type' => 'text/csv; charset=utf-8',
      'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
      'Cache-Control' => 'no-cache, no-store, must-revalidate',
      'Pragma' => 'no-cache',
    ]);
  }

  /**
   * GET /api/v1/analytics/export/excel.
   *
   * Exporta eventos de analytics como archivo compatible con Excel.
   * Utiliza CSV con BOM UTF-8 y separador punto y coma para
   * compatibilidad nativa con Microsoft Excel.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Respuesta con contenido CSV Excel-compatible y cabeceras de descarga.
   */
  public function exportExcel(Request $request): Response {
    $params = $this->extractParams($request);

    if ($params['error']) {
      return new Response($params['error'], 400, ['Content-Type' => 'text/plain']);
    }

    $rows = $this->queryEvents($params);

    // BOM UTF-8 para que Excel detecte la codificacion correctamente.
    $bom = "\xEF\xBB\xBF";
    $csv = $bom . $this->buildCsvContent($rows, ';');

    $filename = sprintf(
      'analytics_export_%d_%s_%s.xlsx.csv',
      $params['tenant_id'],
      $params['date_from'],
      $params['date_to']
    );

    $this->logger->info('Exportacion Excel generada para tenant @tid: @count registros.', [
      '@tid' => $params['tenant_id'],
      '@count' => count($rows),
    ]);

    return new Response($csv, 200, [
      'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
      'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
      'Cache-Control' => 'no-cache, no-store, must-revalidate',
      'Pragma' => 'no-cache',
    ]);
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
    $tenantId = $request->query->get('tenant_id');

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
   * @param array $params
   *   Parametros extraidos (tenant_id, date_from, date_to, metric_type).
   *
   * @return array
   *   Array de filas con datos de eventos.
   */
  protected function queryEvents(array $params): array {
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

    // Limitar a 50.000 filas para evitar problemas de memoria.
    $query->range(0, 50000);

    $result = $query->execute();
    $rows = [];

    foreach ($result as $row) {
      $rows[] = [
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

    return $rows;
  }

  /**
   * Construye el contenido CSV a partir de las filas de datos.
   *
   * @param array $rows
   *   Array de filas (cada fila es un array asociativo).
   * @param string $separator
   *   Separador de columnas (',' para CSV estandar, ';' para Excel).
   *
   * @return string
   *   Contenido CSV completo como cadena.
   */
  protected function buildCsvContent(array $rows, string $separator): string {
    $output = fopen('php://temp', 'r+');

    // Escribir cabeceras.
    fputcsv($output, self::CSV_HEADERS, $separator);

    // Escribir filas de datos.
    foreach ($rows as $row) {
      fputcsv($output, array_values($row), $separator);
    }

    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    return $csv;
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
