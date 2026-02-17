<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class ShippingRetailService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.shipping');
  }

  public function calculateShippingCost(int $shippingMethodId, float $weight, string $postalCode): float {
    try {
      $method = $this->entityTypeManager->getStorage('shipping_method_retail')->load($shippingMethodId);
      if (!$method || !$method->get('is_active')->value) {
        return 0.0;
      }

      $base_price = (float) $method->get('base_price')->value;
      $free_above = (float) $method->get('free_above')->value;

      $zone_surcharge = $this->getZoneSurcharge($postalCode);
      $weight_surcharge = $weight > 5 ? ($weight - 5) * 0.50 : 0.0;

      $total = $base_price + $zone_surcharge + $weight_surcharge;

      return round($total, 2);
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando coste de envio: @e', ['@e' => $e->getMessage()]);
      return 0.0;
    }
  }

  public function getAvailableMethods(string $postalCode): array {
    try {
      $storage = $this->entityTypeManager->getStorage('shipping_method_retail');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('is_active', TRUE)
        ->sort('base_price', 'ASC')
        ->execute();

      if (!$ids) {
        return [];
      }

      $methods = $storage->loadMultiple($ids);
      $available = [];

      foreach ($methods as $method) {
        $available[] = [
          'id' => (int) $method->id(),
          'name' => $method->get('name')->value,
          'base_price' => (float) $method->get('base_price')->value,
          'free_above' => (float) $method->get('free_above')->value,
          'estimated_days_min' => (int) $method->get('estimated_days_min')->value,
          'estimated_days_max' => (int) $method->get('estimated_days_max')->value,
        ];
      }

      return $available;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo metodos de envio: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function createShipment(int $orderId, int $carrierId, int $methodId, array $data): ?ContentEntityInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('shipment_retail');
      $values = [
        'order_id' => $orderId,
        'carrier_id' => $carrierId,
        'shipping_method_id' => $methodId,
        'status' => 'pending',
        'tracking_number' => $data['tracking_number'] ?? '',
        'tracking_url' => $data['tracking_url'] ?? '',
        'weight_kg' => $data['weight_kg'] ?? 0,
        'dimensions' => $data['dimensions'] ?? '',
        'shipping_cost' => $data['shipping_cost'] ?? 0,
        'estimated_delivery' => $data['estimated_delivery'] ?? NULL,
        'notes' => $data['notes'] ?? '',
      ];

      $shipment = $storage->create($values);
      $shipment->save();

      $this->logger->info('Envio creado para pedido @order con transportista @carrier', [
        '@order' => $orderId,
        '@carrier' => $carrierId,
      ]);

      return $shipment;
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando envio para pedido @order: @e', [
        '@order' => $orderId,
        '@e' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  public function updateTrackingStatus(int $shipmentId, string $status): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('shipment_retail');
      $shipment = $storage->load($shipmentId);
      if (!$shipment) {
        return FALSE;
      }

      $valid_statuses = ['pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'returned', 'failed'];
      if (!in_array($status, $valid_statuses)) {
        $this->logger->warning('Estado de envio no valido: @status', ['@status' => $status]);
        return FALSE;
      }

      $shipment->set('status', $status);
      $shipment->save();

      $this->logger->info('Envio @id actualizado a estado @status', [
        '@id' => $shipmentId,
        '@status' => $status,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando estado de envio @id: @e', [
        '@id' => $shipmentId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  public function getShipmentByOrder(int $orderId): ?ContentEntityInterface {
    try {
      $storage = $this->entityTypeManager->getStorage('shipment_retail');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('order_id', $orderId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!$ids) {
        return NULL;
      }

      return $storage->load(reset($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo envio para pedido @order: @e', [
        '@order' => $orderId,
        '@e' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  public function getTrackingUrl(int $shipmentId): ?string {
    try {
      $storage = $this->entityTypeManager->getStorage('shipment_retail');
      $shipment = $storage->load($shipmentId);
      if (!$shipment) {
        return NULL;
      }

      $tracking_url = $shipment->get('tracking_url')->value;
      if ($tracking_url) {
        return $tracking_url;
      }

      $tracking_number = $shipment->get('tracking_number')->value;
      if (!$tracking_number) {
        return NULL;
      }

      $carrier = $shipment->get('carrier_id')->entity;
      if (!$carrier) {
        return NULL;
      }

      $pattern = $carrier->get('tracking_url_pattern')->value;
      if (!$pattern) {
        return NULL;
      }

      return str_replace('{tracking_number}', $tracking_number, $pattern);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo URL de tracking para envio @id: @e', [
        '@id' => $shipmentId,
        '@e' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  protected function getZoneSurcharge(string $postalCode): float {
    try {
      $storage = $this->entityTypeManager->getStorage('shipping_zone');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('is_active', TRUE)
        ->execute();

      if (!$ids) {
        return 0.0;
      }

      $zones = $storage->loadMultiple($ids);
      foreach ($zones as $zone) {
        $postal_codes = $zone->get('postal_codes')->value ?? '';
        $codes = array_map('trim', explode(',', $postal_codes));
        $prefix = substr($postalCode, 0, 2);

        foreach ($codes as $code) {
          if ($code === $postalCode || $code === $prefix) {
            return (float) $zone->get('surcharge')->value;
          }
        }
      }

      return 0.0;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo recargo de zona: @e', ['@e' => $e->getMessage()]);
      return 0.0;
    }
  }

}
