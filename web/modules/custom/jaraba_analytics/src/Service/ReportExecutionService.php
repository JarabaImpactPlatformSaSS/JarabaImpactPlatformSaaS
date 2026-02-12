<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de ejecución de informes personalizados.
 *
 * PROPÓSITO:
 * Ejecuta informes de analytics construyendo consultas dinámicas según el
 * tipo de informe, métricas, filtros y rango de fechas configurados.
 * También gestiona el envío de resultados por email a los destinatarios.
 *
 * LÓGICA:
 * - executeReport(): carga el CustomReport y delega a buildMetricsSummary
 *   o buildEventBreakdown según el report_type.
 * - buildMetricsSummary(): agrega métricas de la tabla analytics_event.
 * - buildEventBreakdown(): desglosa eventos por tipo para el período.
 * - sendReportEmail(): formatea los resultados y los envía por email.
 * - getDateRangeBounds(): convierte un date_range string a timestamps.
 *
 * RELACIONES:
 * - Consume CustomReport entity para obtener configuración.
 * - Consume tabla analytics_event para consultar datos.
 * - Consume MailManager para envío de emails.
 * - Consumido por ReportApiController para ejecutar bajo demanda.
 */
class ReportExecutionService {

  /**
   * Gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Conexión a la base de datos.
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
   * Gestor de plugins de correo.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * Constructor del servicio de ejecución de informes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Database\Connection $database
   *   Conexión a la base de datos.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para analytics.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Gestor de plugins de correo para el envío de informes.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LoggerInterface $logger,
    MailManagerInterface $mail_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * Ejecuta un informe personalizado y devuelve los resultados.
   *
   * Carga la entidad CustomReport, construye la consulta según el tipo
   * de informe configurado, y actualiza la fecha de última ejecución.
   *
   * @param int $reportId
   *   ID de la entidad CustomReport a ejecutar.
   *
   * @return array
   *   Array con los resultados del informe. Contiene 'error' en caso de fallo.
   */
  public function executeReport(int $reportId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('custom_report');
      /** @var \Drupal\jaraba_analytics\Entity\CustomReport|null $report */
      $report = $storage->load($reportId);

      if (!$report) {
        return ['error' => 'Informe no encontrado.'];
      }

      $reportType = $report->get('report_type')->value ?? 'metrics_summary';
      $tenantId = $report->get('tenant_id')->target_id ? (int) $report->get('tenant_id')->target_id : 0;
      $dateRange = $report->get('date_range')->value ?? 'last_30_days';
      $metrics = $report->getMetrics();
      $filters = $report->getFilters();

      $results = match ($reportType) {
        'metrics_summary' => $this->buildMetricsSummary($tenantId, $dateRange, $metrics),
        'event_breakdown' => $this->buildEventBreakdown($tenantId, $dateRange, $filters),
        'conversion' => $this->buildMetricsSummary($tenantId, $dateRange, ['conversions', 'conversion_rate']),
        'retention' => $this->buildMetricsSummary($tenantId, $dateRange, ['returning_users', 'retention_rate']),
        default => $this->buildMetricsSummary($tenantId, $dateRange, $metrics),
      };

      // Actualizar fecha de última ejecución.
      $report->set('last_executed', date('Y-m-d\TH:i:s'));
      $report->save();

      $this->logger->info('Informe "@name" (ID: @id) ejecutado correctamente.', [
        '@name' => $report->label(),
        '@id' => $reportId,
      ]);

      return [
        'report_id' => $reportId,
        'report_name' => $report->label(),
        'report_type' => $reportType,
        'date_range' => $dateRange,
        'executed_at' => date('Y-m-d\TH:i:s'),
        'results' => $results,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error ejecutando informe @id: @error', [
        '@id' => $reportId,
        '@error' => $e->getMessage(),
      ]);
      return ['error' => 'Error ejecutando el informe: ' . $e->getMessage()];
    }
  }

  /**
   * Construye un resumen de métricas agregadas desde analytics_event.
   *
   * Consulta la tabla analytics_event para el tenant y rango de fechas
   * indicados, y calcula las métricas solicitadas (pageviews, sessions,
   * unique_users, etc.).
   *
   * @param int $tenantId
   *   ID del tenant para filtrar eventos.
   * @param string $dateRange
   *   Identificador del rango de fechas (today, last_7_days, etc.).
   * @param array $metrics
   *   Array de nombres de métricas a calcular.
   *
   * @return array
   *   Array asociativo con los valores de cada métrica solicitada.
   */
  public function buildMetricsSummary(int $tenantId, string $dateRange, array $metrics): array {
    $bounds = $this->getDateRangeBounds($dateRange);
    $results = [];

    try {
      $query = $this->database->select('analytics_event', 'ae');
      $query->condition('ae.created', $bounds['start'], '>=');
      $query->condition('ae.created', $bounds['end'], '<=');

      if ($tenantId > 0) {
        $query->condition('ae.tenant_id', $tenantId);
      }

      // Calcular métricas básicas.
      $query->addExpression('COUNT(*)', 'total_events');
      $query->addExpression('COUNT(DISTINCT ae.session_id)', 'sessions');
      $query->addExpression('COUNT(DISTINCT ae.uid)', 'unique_users');
      $result = $query->execute()->fetchAssoc();

      $allMetrics = [
        'total_events' => (int) ($result['total_events'] ?? 0),
        'sessions' => (int) ($result['sessions'] ?? 0),
        'unique_users' => (int) ($result['unique_users'] ?? 0),
        'pageviews' => (int) ($result['total_events'] ?? 0),
        'bounce_rate' => 0.0,
        'avg_duration' => 0.0,
        'conversions' => 0,
        'conversion_rate' => 0.0,
        'returning_users' => 0,
        'retention_rate' => 0.0,
      ];

      // Si no se especifican métricas, devolver todas.
      if (empty($metrics)) {
        return $allMetrics;
      }

      foreach ($metrics as $metric) {
        if (isset($allMetrics[$metric])) {
          $results[$metric] = $allMetrics[$metric];
        }
      }

      return $results ?: $allMetrics;
    }
    catch (\Exception $e) {
      $this->logger->error('Error construyendo resumen de métricas: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Construye un desglose de eventos por tipo para el período indicado.
   *
   * Agrupa los eventos de la tabla analytics_event por event_type y
   * devuelve el conteo de cada tipo.
   *
   * @param int $tenantId
   *   ID del tenant para filtrar eventos.
   * @param string $dateRange
   *   Identificador del rango de fechas.
   * @param array $filters
   *   Array asociativo con filtros adicionales (event_type, page_path, etc.).
   *
   * @return array
   *   Array de arrays con 'event_type' y 'count' para cada tipo de evento.
   */
  public function buildEventBreakdown(int $tenantId, string $dateRange, array $filters): array {
    $bounds = $this->getDateRangeBounds($dateRange);

    try {
      $query = $this->database->select('analytics_event', 'ae');
      $query->addField('ae', 'event_type');
      $query->addExpression('COUNT(*)', 'event_count');
      $query->condition('ae.created', $bounds['start'], '>=');
      $query->condition('ae.created', $bounds['end'], '<=');

      if ($tenantId > 0) {
        $query->condition('ae.tenant_id', $tenantId);
      }

      // Aplicar filtros adicionales.
      if (!empty($filters['event_type'])) {
        $query->condition('ae.event_type', $filters['event_type']);
      }

      $query->groupBy('ae.event_type');
      $query->orderBy('event_count', 'DESC');

      $results = [];
      $rows = $query->execute()->fetchAll();
      foreach ($rows as $row) {
        $results[] = [
          'event_type' => $row->event_type,
          'count' => (int) $row->event_count,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error construyendo desglose de eventos: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Envía los resultados de un informe por email a los destinatarios.
   *
   * Carga el CustomReport, formatea los resultados en texto plano y
   * envía un email a cada destinatario configurado.
   *
   * @param int $reportId
   *   ID de la entidad CustomReport.
   * @param array $results
   *   Resultados del informe ya ejecutado.
   *
   * @return bool
   *   TRUE si se envió al menos un email correctamente.
   */
  public function sendReportEmail(int $reportId, array $results): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('custom_report');
      /** @var \Drupal\jaraba_analytics\Entity\CustomReport|null $report */
      $report = $storage->load($reportId);

      if (!$report) {
        return FALSE;
      }

      $recipients = $report->getRecipients();
      if (empty($recipients)) {
        $this->logger->info('Informe @id sin destinatarios configurados.', [
          '@id' => $reportId,
        ]);
        return FALSE;
      }

      // Formatear resultados para el email.
      $body = $this->formatReportResults($report->label(), $results);

      $sent = FALSE;
      foreach ($recipients as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          continue;
        }

        $params = [
          'subject' => 'Informe: ' . $report->label(),
          'body' => $body,
        ];

        $result = $this->mailManager->mail(
          'jaraba_analytics',
          'custom_report',
          $email,
          'es',
          $params,
          NULL,
          TRUE
        );

        if ($result['result'] ?? FALSE) {
          $sent = TRUE;
        }
      }

      $this->logger->info('Informe "@name" enviado a @count destinatarios.', [
        '@name' => $report->label(),
        '@count' => count($recipients),
      ]);

      return $sent;
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando informe @id por email: @error', [
        '@id' => $reportId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Convierte un identificador de rango de fechas a timestamps de inicio y fin.
   *
   * @param string $dateRange
   *   Identificador del rango: today, yesterday, last_7_days, last_30_days,
   *   last_90_days o custom.
   *
   * @return array
   *   Array asociativo con claves 'start' y 'end' como timestamps Unix.
   */
  public function getDateRangeBounds(string $dateRange): array {
    $now = time();
    $todayStart = strtotime('today midnight');
    $todayEnd = strtotime('tomorrow midnight') - 1;

    return match ($dateRange) {
      'today' => [
        'start' => $todayStart,
        'end' => $todayEnd,
      ],
      'yesterday' => [
        'start' => strtotime('yesterday midnight'),
        'end' => $todayStart - 1,
      ],
      'last_7_days' => [
        'start' => strtotime('-7 days midnight'),
        'end' => $now,
      ],
      'last_30_days' => [
        'start' => strtotime('-30 days midnight'),
        'end' => $now,
      ],
      'last_90_days' => [
        'start' => strtotime('-90 days midnight'),
        'end' => $now,
      ],
      default => [
        'start' => strtotime('-30 days midnight'),
        'end' => $now,
      ],
    };
  }

  /**
   * Formatea los resultados del informe en texto legible.
   *
   * @param string $reportName
   *   Nombre del informe.
   * @param array $results
   *   Resultados del informe.
   *
   * @return string
   *   Texto formateado con los resultados.
   */
  protected function formatReportResults(string $reportName, array $results): string {
    $lines = [];
    $lines[] = 'Informe: ' . $reportName;
    $lines[] = 'Fecha de ejecución: ' . date('d/m/Y H:i:s');
    $lines[] = str_repeat('-', 50);

    if (isset($results['results']) && is_array($results['results'])) {
      foreach ($results['results'] as $key => $value) {
        if (is_array($value)) {
          $lines[] = $key . ': ' . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        else {
          $lines[] = $key . ': ' . $value;
        }
      }
    }
    else {
      foreach ($results as $key => $value) {
        if (is_array($value)) {
          $lines[] = $key . ': ' . json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        else {
          $lines[] = $key . ': ' . $value;
        }
      }
    }

    return implode("\n", $lines);
  }

}
