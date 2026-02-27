<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface;
use Drupal\jaraba_agroconecta_core\Shipping\CarrierManager;
use Drupal\jaraba_pwa\Service\PlatformPushService;
use Drupal\jaraba_ai_agents\Attribute\AgentTool;
use Psr\Log\LoggerInterface;

/**
 * Servicio central de envíos y logística para AgroConecta.
 *
 * LÓGICA:
 * - Calcula tarifas basadas en origen (productor) y destino (cliente).
 * - Soporta cadena de frío (multiplicador de tarifa).
 * - Gestiona zonas de envío dinámicas.
 *
 * F5 — Doc 51 §4.
 */
class AgroShippingService
{

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
    protected CarrierManager $carrierManager,
    protected ?PlatformPushService $pushService,
  ) {
  }

  /**
   * Crea un envío físico en el transportista seleccionado.
   */
  public function createCarrierShipment(AgroShipmentInterface $shipment): array
  {
    $carrierId = $shipment->getCarrierId();
    $adapter = $this->carrierManager->getCarrier($carrierId);

    if (!$adapter) {
      throw new \InvalidArgumentException("Transportista '$carrierId' no soportado.");
    }

    // Preparar datos para el carrier.
    $data = [
      'shipment_id' => $shipment->id(),
      'weight' => $shipment->get('weight_value')->value,
      'is_refrigerated' => $shipment->isRefrigerated(),
      // Aquí se añadirían direcciones del productor y cliente reales.
    ];

    $response = $adapter->createShipment($data);

    if ($response['success']) {
      $shipment->set('tracking_number', $response['tracking_number']);
      $shipment->set('label_url', $response['label_url']);
      $shipment->set('state', 'label_created');
      $shipment->set('label_generated_at', date('Y-m-d\TH:i:s'));
      $shipment->save();

      // Notificar al cliente vía PWA (Fase F10).
      $this->notifyCustomerOfShipment($shipment);
    }

    return $response;
  }

  /**
   * Sincroniza el estado del tracking desde el carrier.
   */
  public function syncTracking(AgroShipmentInterface $shipment): array
  {
    $trackingNumber = $shipment->getTrackingNumber();
    if (!$trackingNumber) {
      return ['success' => FALSE, 'message' => 'Sin número de seguimiento.'];
    }

    $adapter = $this->carrierManager->getCarrier($shipment->getCarrierId());
    if (!$adapter)
      return ['success' => FALSE];

    $status = $adapter->getTrackingStatus($trackingNumber);

    // Aquí se actualizaría el estado del shipment basado en el status del adapter.

    return $status;
  }

  /**
   * Calcula el coste de envío para un conjunto de productos y un destino.
   *
   * @param array $items
   *   Array de items [['product_id' => ID, 'quantity' => QTY], ...].
   * @param string $postalCode
   *   Código postal de destino.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Desglose de costes por productor.
   */
  #[AgentTool(
    name: 'agro_calculate_shipping',
    description: 'Calcula tarifas de envío para productos agroalimentarios según destino.',
    parameters: [
      'items' => ['type' => 'array', 'description' => 'Lista de productos y cantidades'],
      'postalCode' => ['type' => 'string', 'description' => 'Código postal de destino'],
      'tenantId' => ['type' => 'integer', 'description' => 'ID del tenant']
    ]
  )]
  public function calculateRates(array $items, string $postalCode, int $tenantId): array
  {
    $results = [];
    $producers_data = $this->groupItemsByProducer($items);

    foreach ($producers_data as $producer_id => $data) {
      $producer_profile = $this->entityTypeManager->getStorage('producer_profile')->load($producer_id);
      if (!$producer_profile)
        continue;

      $origin_pc = $producer_profile->get('field_postal_code')->value;
      $zone = $this->resolveZone($origin_pc, $postalCode, $tenantId, (int) $producer_id);

      if (!$zone) {
        $results[$producer_id] = ['error' => $this->t('No se realizan envíos a esta zona.')];
        continue;
      }

      $rate = $this->findBestRate($zone->id(), $data['total_weight'], $data['needs_cold'], (int) $producer_id);

      if (!$rate) {
        $results[$producer_id] = ['error' => $this->t('No hay tarifas disponibles para el peso especificado.')];
        continue;
      }

      $cost = (float) $rate->get('base_rate')->value + ($data['total_weight'] * (float) $rate->get('per_kg_rate')->value);

      // Aplicar umbral de envío gratis.
      $free_threshold = $rate->get('free_shipping_threshold')->value;
      if ($free_threshold > 0 && $data['subtotal'] >= $free_threshold) {
        $cost = 0.00;
      }

      $results[$producer_id] = [
        'cost' => $cost,
        'carrier' => $rate->get('carrier_id')->value,
        'service' => $rate->get('service_code')->value,
        'zone_name' => $zone->label(),
        'needs_cold' => $data['needs_cold'],
      ];
    }

    return $results;
  }

  /**
   * Agrupa los items por productor y calcula pesos/totales.
   */
  protected function groupItemsByProducer(array $items): array
  {
    $groups = [];
    foreach ($items as $item) {
      $product = $this->entityTypeManager->getStorage('product_agro')->load($item['product_id']);
      if (!$product)
        continue;

      $producer_id = $product->get('producer_id')->target_id;
      if (!isset($groups[$producer_id])) {
        $groups[$producer_id] = [
          'total_weight' => 0,
          'subtotal' => 0,
          'needs_cold' => FALSE,
        ];
      }

      $groups[$producer_id]['total_weight'] += (float) $product->get('field_weight')->value * $item['quantity'];
      $groups[$producer_id]['subtotal'] += (float) $product->get('field_price')->value * $item['quantity'];
      if ($product->get('field_requires_cold')->value) {
        $groups[$producer_id]['needs_cold'] = TRUE;
      }
    }
    return $groups;
  }

  /**
   * Resuelve la zona de envío según origen y destino.
   */
  protected function resolveZone(string $originPc, string $destinationPc, int $tenantId, int $producerId): ?\Drupal\Core\Entity\EntityInterface
  {
    $storage = $this->entityTypeManager->getStorage('agro_shipping_zone');

    // Buscar zonas específicas del productor primero, luego globales del tenant.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', TRUE)
      ->sort('sort_order', 'ASC');

    $group = $query->orConditionGroup()
      ->condition('producer_id', $producerId)
      ->condition('producer_id', NULL, 'IS NULL');
    $query->condition($group);

    $ids = $query->execute();
    if (empty($ids))
      return NULL;

    $zones = $storage->loadMultiple($ids);
    foreach ($zones as $zone) {
      if ($this->matchZoneData($zone, $originPc, $destinationPc)) {
        return $zone;
      }
    }

    return NULL;
  }

  /**
   * Verifica si un CP matchea con los datos de la zona.
   */
  protected function matchZoneData($zone, $originPc, $destinationPc): bool
  {
    $data = array_map('trim', explode(',', $zone->get('zone_data')->value));
    $type = $zone->get('zone_type')->value;

    switch ($type) {
      case 'postal_codes':
        // Soporte básico: matchea los 2 primeros dígitos (provincia) o CP exacto.
        foreach ($data as $pattern) {
          if (str_starts_with($destinationPc, $pattern))
            return TRUE;
        }
        break;

      case 'provinces':
        $prov = substr($destinationPc, 0, 2);
        return in_array($prov, $data);
    }

    return FALSE;
  }

  /**
   * Busca la mejor tarifa (la más barata disponible) para una zona y peso.
   */
  protected function findBestRate(int $zoneId, float $weight, bool $needsCold, int $producerId): ?\Drupal\Core\Entity\EntityInterface
  {
    $storage = $this->entityTypeManager->getStorage('agro_shipping_rate');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('zone_id', $zoneId)
      ->condition('status', TRUE)
      ->condition('is_refrigerated', $needsCold)
      ->condition('min_weight', $weight, '<=')
      ->sort('base_rate', 'ASC')
      ->range(0, 1);

    // Filter by max_weight if set.
    $or = $query->orConditionGroup()
      ->condition('max_weight', $weight, '>=')
      ->condition('max_weight', NULL, 'IS NULL');
    $query->condition($or);

    $ids = $query->execute();
    return !empty($ids) ? $storage->load(reset($ids)) : NULL;
  }

  /**
   * Envía notificación push al cliente sobre su envío.
   */
  protected function notifyCustomerOfShipment(AgroShipmentInterface $shipment): void
  {
    if ($this->pushService === NULL) {
      return;
    }
    try {
      $suborder = $shipment->get('sub_order_id')->entity;
      if (!$suborder)
        return;

      $order = $suborder->get('order_id')->entity;
      if (!$order)
        return;

      $userId = (int) $order->get('uid')->target_id;
      if (!$userId)
        return;

      $this->pushService->sendToUser(
        $userId,
        $this->t('¡Tu pedido AgroConecta está listo!'),
        $this->t('El envío @num ha sido registrado con @carrier.', [
          '@num' => $shipment->getShipmentNumber(),
          '@carrier' => strtoupper($shipment->getCarrierId()),
        ]),
        ['url' => '/my-orders/' . $order->id()]
      );
    } catch (\Exception $e) {
      $this->logger->error('Failed to send shipment push: @error', ['@error' => $e->getMessage()]);
    }
  }

}
