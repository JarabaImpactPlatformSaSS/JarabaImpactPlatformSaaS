<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class ClickCollectService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.click_collect');
  }

  public function getPickupLocations(int $merchantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('merchant_profile');
      $merchant = $storage->load($merchantId);
      if (!$merchant) {
        return [];
      }

      $locations = [];
      $locations[] = [
        'id' => $merchantId,
        'name' => $merchant->get('business_name')->value ?? '',
        'address' => $merchant->get('address')->value ?? '',
        'city' => $merchant->get('city')->value ?? '',
        'postal_code' => $merchant->get('postal_code')->value ?? '',
        'phone' => $merchant->get('phone')->value ?? '',
        'pickup_enabled' => TRUE,
      ];

      return $locations;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo puntos de recogida para comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function createPickupReservation(int $orderId, int $merchantId, string $pickupTime): bool {
    try {
      $order_storage = $this->entityTypeManager->getStorage('order_retail');
      $order = $order_storage->load($orderId);
      if (!$order) {
        $this->logger->warning('Pedido @id no encontrado para reserva de recogida', ['@id' => $orderId]);
        return FALSE;
      }

      $order->set('shipping_method', 'click_collect');
      $order->set('notes', 'Recogida en tienda: ' . $pickupTime);
      $order->save();

      $this->logger->info('Reserva de recogida creada para pedido @order en comerciante @merchant, hora: @time', [
        '@order' => $orderId,
        '@merchant' => $merchantId,
        '@time' => $pickupTime,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando reserva de recogida: @e', ['@e' => $e->getMessage()]);
      return FALSE;
    }
  }

  public function confirmPickup(int $orderId): bool {
    try {
      $order_storage = $this->entityTypeManager->getStorage('order_retail');
      $order = $order_storage->load($orderId);
      if (!$order) {
        return FALSE;
      }

      $order->set('status', 'delivered');
      $order->save();

      $this->logger->info('Recogida confirmada para pedido @id', ['@id' => $orderId]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error confirmando recogida para pedido @id: @e', [
        '@id' => $orderId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  public function getAvailableSlots(int $merchantId, string $date): array {
    try {
      $slots = [];
      $start_hour = 9;
      $end_hour = 20;

      for ($hour = $start_hour; $hour < $end_hour; $hour++) {
        $time_from = sprintf('%02d:00', $hour);
        $time_to = sprintf('%02d:00', $hour + 1);
        $slots[] = [
          'time_from' => $time_from,
          'time_to' => $time_to,
          'label' => $time_from . ' - ' . $time_to,
          'available' => TRUE,
        ];
      }

      return [
        'date' => $date,
        'merchant_id' => $merchantId,
        'slots' => $slots,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo franjas horarias: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

}
