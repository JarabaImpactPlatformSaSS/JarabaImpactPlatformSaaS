<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_agroconecta_core\Entity\AnalyticsDailyAgro;

/**
 * Servicio de Analítica y KPIs para AgroConecta.
 *
 * LÓGICA:
 * - Agrega datos de ventas, logística y marketing.
 * - Genera snapshots diarios para el dashboard.
 * - Calcula tendencias y comparativas.
 *
 * F7 — Doc 57.
 */
class AgroAnalyticsService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Genera el snapshot diario para un productor o tenant.
   */
  public function generateDailySnapshot(int $tenantId, ?int $producerId = NULL, string $date = NULL): AnalyticsDailyAgro {
    if (!$date) {
      $date = date('Y-m-d', strtotime('yesterday'));
    }

    $data = $this->collectMetrics($tenantId, $producerId, $date);

    $storage = $this->entityTypeManager->getStorage('analytics_daily_agro');
    
    // Buscar si ya existe para actualizar, o crear nuevo.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('date', $date);
    
    if ($producerId) {
      $query->condition('uid', $producerId); // Usamos uid como owner (productor)
    } else {
      $query->condition('uid', 0); // Snapshot global del tenant
    }

    $ids = $query->execute();
    
    if (!empty($ids)) {
      $entity = $storage->load(reset($ids));
    } else {
      $entity = $storage->create([
        'tenant_id' => $tenantId,
        'uid' => $producerId ?? 0,
        'date' => $date,
      ]);
    }

    // Mapear métricas recolectadas.
    foreach ($data as $key => $value) {
      if ($entity->hasField($key)) {
        $entity->set($key, $value);
      }
    }

    $entity->save();
    return $entity;
  }

  /**
   * Obtiene un resumen de analítica de los últimos N días.
   */
  public function getRecentAnalytics(int $tenantId, ?int $producerId = NULL, int $days = 7): array {
    $storage = $this->entityTypeManager->getStorage('analytics_daily_agro');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->sort('date', 'DESC')
      ->range(0, $days);

    if ($producerId) {
      $query->condition('uid', $producerId);
    } else {
      $query->condition('uid', 0);
    }

    $ids = $query->execute();
    if (empty($ids)) return [];

    $snapshots = $storage->loadMultiple($ids);
    $summary = [];

    foreach ($snapshots as $snapshot) {
      $summary[] = [
        'date' => $snapshot->get('date')->value,
        'gmv' => (float) $snapshot->getGmv(),
        'orders' => (int) $snapshot->getOrdersCount(),
        'qr_scans' => (int) $snapshot->get('qr_scans')->value,
        'shipments' => (int) $snapshot->get('shipments_count')->value,
        'cold_alerts' => (int) $snapshot->get('cold_chain_alerts')->value,
      ];
    }

    return array_reverse($summary);
  }

  /**
   * Recolecta métricas reales desde la base de datos.
   */
  protected function collectMetrics(int $tenantId, ?int $producerId, string $date): array {
    $metrics = [];
    $start = $date . ' 00:00:00';
    $end = $date . ' 23:59:59';

    // 1. Ventas (GMV y Pedidos).
    $query = $this->database->select('agro_suborder', 's')
      ->fields('s', ['subtotal', 'shipping_amount'])
      ->condition('s.tenant_id', $tenantId)
      ->condition('s.created', [strtotime($start), strtotime($end)], 'BETWEEN');
    
    if ($producerId) {
      $query->condition('s.producer_id', $producerId);
    }

    $orders = $query->execute()->fetchAll();
    $metrics['gmv'] = array_sum(array_column($orders, 'subtotal'));
    $metrics['orders_count'] = count($orders);
    $metrics['shipping_revenue'] = array_sum(array_column($orders, 'shipping_amount'));
    $metrics['aov'] = $metrics['orders_count'] > 0 ? $metrics['gmv'] / $metrics['orders_count'] : 0;

    // 2. Logística (Fase 7).
    $ship_query = $this->database->select('agro_shipment', 'sh')
      ->fields('sh', ['id', 'is_refrigerated', 'state'])
      ->condition('sh.tenant_id', $tenantId)
      ->condition('sh.created', [strtotime($start), strtotime($end)], 'BETWEEN');
    
    // El shipment no tiene producer_id directo, filtramos por sub_order_id.
    if ($producerId) {
      $ship_query->join('agro_suborder', 'so', 'sh.sub_order_id = so.id');
      $ship_query->condition('so.producer_id', $producerId);
    }

    $shipments = $ship_query->execute()->fetchAll();
    $metrics['shipments_count'] = count($shipments);
    
    // Alertas de frío (Incidencias en envíos refrigerados).
    $cold_alerts = 0;
    foreach ($shipments as $sh) {
      if ($sh->is_refrigerated && $sh->state === 'exception') {
        $cold_alerts++;
      }
    }
    $metrics['cold_chain_alerts'] = $cold_alerts;

    // 3. Marketing (QR).
    $qr_query = $this->database->select('qr_scan_event', 'qse')
      ->condition('qse.created', [strtotime($start), strtotime($end)], 'BETWEEN');
    
    if ($producerId) {
      $qr_query->join('qr_code_agro', 'qca', 'qse.qr_id = qca.id');
      $qr_query->condition('qca.target_entity_type', 'producer_profile');
      $qr_query->condition('qca.target_entity_id', $producerId);
    }

    $metrics['qr_scans'] = (int) $qr_query->countQuery()->execute()->fetchField();

    return $metrics;
  }

}
