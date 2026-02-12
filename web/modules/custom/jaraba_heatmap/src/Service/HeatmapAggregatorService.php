<?php

namespace Drupal\jaraba_heatmap\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de agregación de datos de heatmap.
 *
 * Ejecuta procesamiento diario vía cron para:
 * - Agregar eventos raw en buckets de 5% x 50px
 * - Calcular métricas de scroll depth
 * - Purgar eventos antiguos según retención
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 */
class HeatmapAggregatorService
{

    /**
     * Ancho de cada bucket X en porcentaje.
     */
    const BUCKET_X_SIZE = 5;

    /**
     * Alto de cada bucket Y en píxeles.
     */
    const BUCKET_Y_SIZE = 50;

    /**
     * Conexión a base de datos.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   Conexión a base de datos.
     * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
     *   Factory de canales de log.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   Factory de configuración.
     */
    public function __construct(
        Connection $database,
        LoggerChannelFactoryInterface $logger_factory,
        ConfigFactoryInterface $config_factory
    ) {
        $this->database = $database;
        $this->logger = $logger_factory->get('jaraba_heatmap');
        $this->configFactory = $config_factory;
    }

    /**
     * Ejecuta la agregación diaria de eventos.
     *
     * Procesa eventos del día anterior y genera datos agregados
     * en buckets para visualización eficiente.
     *
     * @return int
     *   Número de registros agregados creados.
     */
    public function aggregateDaily(): int
    {
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $yesterday_start = strtotime($yesterday . ' 00:00:00');
        $yesterday_end = strtotime($yesterday . ' 23:59:59');

        $this->logger->info('Iniciando agregación de heatmaps para @date', [
            '@date' => $yesterday,
        ]);

        $count = 0;

        // Agregar clicks y movements por separado.
        foreach (['click', 'move'] as $event_type) {
            $count += $this->aggregateEventType($event_type, $yesterday, $yesterday_start, $yesterday_end);
        }

        // Agregar datos de scroll.
        $count += $this->aggregateScrollDepth($yesterday, $yesterday_start, $yesterday_end);

        $this->logger->info('Agregación completada: @count registros creados', [
            '@count' => $count,
        ]);

        return $count;
    }

    /**
     * Agrega un tipo de evento específico en buckets.
     *
     * @param string $event_type
     *   Tipo de evento (click, move).
     * @param string $date
     *   Fecha de agregación (YYYY-MM-DD).
     * @param int $start_ts
     *   Timestamp inicio del día.
     * @param int $end_ts
     *   Timestamp fin del día.
     *
     * @return int
     *   Registros creados.
     */
    protected function aggregateEventType(string $event_type, string $date, int $start_ts, int $end_ts): int
    {
        // Consultar eventos agrupados por tenant, página, bucket y dispositivo.
        $query = $this->database->select('heatmap_events', 'he');
        $query->fields('he', ['tenant_id', 'page_path', 'device_type']);
        $query->addExpression('FLOOR(he.x_percent / ' . self::BUCKET_X_SIZE . ')', 'x_bucket');
        $query->addExpression('FLOOR(he.y_pixel / ' . self::BUCKET_Y_SIZE . ')', 'y_bucket');
        $query->addExpression('COUNT(*)', 'event_count');
        $query->addExpression('COUNT(DISTINCT he.session_id)', 'unique_sessions');
        $query->condition('he.event_type', $event_type);
        $query->condition('he.created_at', $start_ts, '>=');
        $query->condition('he.created_at', $end_ts, '<=');
        $query->groupBy('he.tenant_id');
        $query->groupBy('he.page_path');
        $query->groupBy('he.device_type');
        $query->groupBy('x_bucket');
        $query->groupBy('y_bucket');

        $results = $query->execute()->fetchAll();

        if (empty($results)) {
            return 0;
        }

        // Batch insert de agregados.
        $insert_query = $this->database->insert('heatmap_aggregated')
            ->fields([
                'tenant_id',
                'page_path',
                'event_type',
                'x_bucket',
                'y_bucket',
                'device_type',
                'event_count',
                'unique_sessions',
                'date',
            ]);

        $count = 0;
        foreach ($results as $row) {
            $insert_query->values([
                'tenant_id' => (int) $row->tenant_id,
                'page_path' => $row->page_path,
                'event_type' => $event_type,
                'x_bucket' => (int) $row->x_bucket,
                'y_bucket' => (int) $row->y_bucket,
                'device_type' => $row->device_type,
                'event_count' => (int) $row->event_count,
                'unique_sessions' => (int) $row->unique_sessions,
                'date' => $date,
            ]);
            $count++;
        }

        $insert_query->execute();
        return $count;
    }

    /**
     * Agrega datos de scroll depth por página.
     *
     * @param string $date
     *   Fecha de agregación.
     * @param int $start_ts
     *   Timestamp inicio del día.
     * @param int $end_ts
     *   Timestamp fin del día.
     *
     * @return int
     *   Registros creados.
     */
    protected function aggregateScrollDepth(string $date, int $start_ts, int $end_ts): int
    {
        // Consultar máximo scroll depth por sesión.
        $query = $this->database->select('heatmap_events', 'he');
        $query->fields('he', ['tenant_id', 'page_path', 'device_type', 'session_id']);
        $query->addExpression('MAX(he.scroll_depth)', 'max_depth');
        $query->condition('he.event_type', 'scroll');
        $query->condition('he.created_at', $start_ts, '>=');
        $query->condition('he.created_at', $end_ts, '<=');
        $query->isNotNull('he.scroll_depth');
        $query->groupBy('he.tenant_id');
        $query->groupBy('he.page_path');
        $query->groupBy('he.device_type');
        $query->groupBy('he.session_id');

        $results = $query->execute()->fetchAll();

        if (empty($results)) {
            return 0;
        }

        // Agrupar por página y dispositivo.
        $aggregated = [];
        foreach ($results as $row) {
            $key = $row->tenant_id . '|' . $row->page_path . '|' . $row->device_type;
            if (!isset($aggregated[$key])) {
                $aggregated[$key] = [
                    'tenant_id' => (int) $row->tenant_id,
                    'page_path' => $row->page_path,
                    'device_type' => $row->device_type,
                    'depth_25' => 0,
                    'depth_50' => 0,
                    'depth_75' => 0,
                    'depth_100' => 0,
                    'total_depth' => 0,
                    'total_sessions' => 0,
                ];
            }

            $depth = (int) $row->max_depth;
            if ($depth >= 25) {
                $aggregated[$key]['depth_25']++;
            }
            if ($depth >= 50) {
                $aggregated[$key]['depth_50']++;
            }
            if ($depth >= 75) {
                $aggregated[$key]['depth_75']++;
            }
            if ($depth >= 100) {
                $aggregated[$key]['depth_100']++;
            }
            $aggregated[$key]['total_depth'] += $depth;
            $aggregated[$key]['total_sessions']++;
        }

        // Insertar registros.
        $insert_query = $this->database->insert('heatmap_scroll_depth')
            ->fields([
                'tenant_id',
                'page_path',
                'depth_25',
                'depth_50',
                'depth_75',
                'depth_100',
                'avg_max_depth',
                'total_sessions',
                'device_type',
                'date',
            ]);

        $count = 0;
        foreach ($aggregated as $data) {
            $avg_depth = $data['total_sessions'] > 0
                ? round($data['total_depth'] / $data['total_sessions'], 2)
                : 0;

            $insert_query->values([
                'tenant_id' => $data['tenant_id'],
                'page_path' => $data['page_path'],
                'depth_25' => $data['depth_25'],
                'depth_50' => $data['depth_50'],
                'depth_75' => $data['depth_75'],
                'depth_100' => $data['depth_100'],
                'avg_max_depth' => $avg_depth,
                'total_sessions' => $data['total_sessions'],
                'device_type' => $data['device_type'],
                'date' => $date,
            ]);
            $count++;
        }

        $insert_query->execute();
        return $count;
    }

    /**
     * Purga eventos raw antiguos según retención.
     *
     * @param int|null $days
     *   Días de retención. Si NULL, lee de config (default: 7).
     *
     * @return int
     *   Número de eventos eliminados.
     */
    public function purgeOldEvents(?int $days = NULL): int
    {
        if ($days === NULL) {
            $config = $this->configFactory->get('jaraba_heatmap.settings');
            $days = (int) ($config->get('retention_raw_days') ?: 7);
        }

        $cutoff = strtotime("-{$days} days");

        $deleted = $this->database->delete('heatmap_events')
            ->condition('created_at', $cutoff, '<')
            ->execute();

        if ($deleted > 0) {
            $this->logger->info('Purgados @count eventos raw anteriores a @days días', [
                '@count' => $deleted,
                '@days' => $days,
            ]);
        }

        return $deleted;
    }

    /**
     * Purga datos agregados antiguos.
     *
     * @param int|null $days
     *   Días de retención. Si NULL, lee de config (default: 90).
     *
     * @return int
     *   Número de registros eliminados.
     */
    public function purgeOldAggregated(?int $days = NULL): int
    {
        if ($days === NULL) {
            $config = $this->configFactory->get('jaraba_heatmap.settings');
            $days = (int) ($config->get('retention_aggregated_days') ?: 90);
        }

        $cutoff_date = date('Y-m-d', strtotime("-{$days} days"));

        $deleted_agg = $this->database->delete('heatmap_aggregated')
            ->condition('date', $cutoff_date, '<')
            ->execute();

        $deleted_scroll = $this->database->delete('heatmap_scroll_depth')
            ->condition('date', $cutoff_date, '<')
            ->execute();

        $total = $deleted_agg + $deleted_scroll;

        if ($total > 0) {
            $this->logger->info('Purgados @count registros agregados anteriores a @days días', [
                '@count' => $total,
                '@days' => $days,
            ]);
        }

        return $total;
    }

    /**
     * Detecta anomalías comparando métricas del día anterior con la media de 7 días.
     *
     * Para cada página y tenant, compara el total de eventos del día anterior
     * contra la media de los 7 días anteriores. Genera alerta si:
     * - Caída > threshold_drop (default 50%)
     * - Pico > threshold_spike (default 200%)
     *
     * Ref: Spec 20260130a §11.3
     *
     * @return array
     *   Array de anomalías detectadas, cada una con:
     *   - tenant_id, page_path, yesterday_count, avg_count, type ('drop'|'spike'), ratio
     */
    public function detectAnomalies(): array
    {
        $config = $this->configFactory->get('jaraba_heatmap.settings');
        $threshold_drop = (float) ($config->get('threshold_drop') ?: 50) / 100;
        $threshold_spike = (float) ($config->get('threshold_spike') ?: 200) / 100;

        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $week_ago = date('Y-m-d', strtotime('-8 days'));
        $two_days_ago = date('Y-m-d', strtotime('-2 days'));

        // Obtener conteos del día anterior por tenant+página.
        $yesterday_query = $this->database->select('heatmap_aggregated', 'ha');
        $yesterday_query->fields('ha', ['tenant_id', 'page_path']);
        $yesterday_query->addExpression('SUM(ha.event_count)', 'total_events');
        $yesterday_query->condition('ha.date', $yesterday);
        $yesterday_query->groupBy('ha.tenant_id');
        $yesterday_query->groupBy('ha.page_path');

        $yesterday_data = [];
        foreach ($yesterday_query->execute()->fetchAll() as $row) {
            $key = $row->tenant_id . '|' . $row->page_path;
            $yesterday_data[$key] = [
                'tenant_id' => (int) $row->tenant_id,
                'page_path' => $row->page_path,
                'count' => (int) $row->total_events,
            ];
        }

        if (empty($yesterday_data)) {
            return [];
        }

        // Obtener media de los 7 días previos (excluyendo ayer).
        $avg_query = $this->database->select('heatmap_aggregated', 'ha');
        $avg_query->fields('ha', ['tenant_id', 'page_path']);
        $avg_query->addExpression('SUM(ha.event_count) / COUNT(DISTINCT ha.date)', 'avg_events');
        $avg_query->condition('ha.date', $week_ago, '>=');
        $avg_query->condition('ha.date', $two_days_ago, '<=');
        $avg_query->groupBy('ha.tenant_id');
        $avg_query->groupBy('ha.page_path');

        $avg_data = [];
        foreach ($avg_query->execute()->fetchAll() as $row) {
            $key = $row->tenant_id . '|' . $row->page_path;
            $avg_data[$key] = (float) $row->avg_events;
        }

        // Comparar y detectar anomalías.
        $anomalies = [];
        foreach ($yesterday_data as $key => $data) {
            $avg = $avg_data[$key] ?? 0;
            if ($avg <= 0) {
                continue;
            }

            $ratio = $data['count'] / $avg;

            if ($ratio < (1 - $threshold_drop)) {
                $anomalies[] = [
                    'tenant_id' => $data['tenant_id'],
                    'page_path' => $data['page_path'],
                    'yesterday_count' => $data['count'],
                    'avg_count' => round($avg, 1),
                    'type' => 'drop',
                    'ratio' => round($ratio, 3),
                ];
            }
            elseif ($ratio > $threshold_spike) {
                $anomalies[] = [
                    'tenant_id' => $data['tenant_id'],
                    'page_path' => $data['page_path'],
                    'yesterday_count' => $data['count'],
                    'avg_count' => round($avg, 1),
                    'type' => 'spike',
                    'ratio' => round($ratio, 3),
                ];
            }
        }

        if (!empty($anomalies)) {
            $this->logger->warning('Detectadas @count anomalías en heatmaps: @details', [
                '@count' => count($anomalies),
                '@details' => json_encode(array_map(fn($a) => $a['page_path'] . ' (' . $a['type'] . ' ' . ($a['ratio'] * 100) . '%)', $anomalies)),
            ]);
        }

        return $anomalies;
    }

}
