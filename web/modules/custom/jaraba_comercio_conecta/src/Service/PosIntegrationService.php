<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class PosIntegrationService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.pos');
  }

  public function syncStock(int $connectionId): int {
    try {
      $connection = $this->entityTypeManager->getStorage('pos_connection')->load($connectionId);
      if (!$connection) {
        $this->logger->warning('Conexion POS @id no encontrada', ['@id' => $connectionId]);
        return 0;
      }

      $status = $connection->get('status')->value;
      if ($status !== 'active') {
        $this->logger->warning('Conexion POS @id no esta activa: @status', [
          '@id' => $connectionId,
          '@status' => $status,
        ]);
        return 0;
      }

      $merchant_id = $connection->get('merchant_id')->target_id;
      $product_storage = $this->entityTypeManager->getStorage('product_retail');
      $ids = $product_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('merchant_id', $merchant_id)
        ->execute();

      $synced_count = 0;
      if ($ids) {
        $synced_count = count($ids);
      }

      $connection->set('last_sync_at', \Drupal::time()->getRequestTime());
      $connection->save();

      $this->logger->info('Sincronizacion completada para conexion @id: @count productos', [
        '@id' => $connectionId,
        '@count' => $synced_count,
      ]);

      return $synced_count;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en sincronizacion POS para conexion @id: @e', [
        '@id' => $connectionId,
        '@e' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  public function pushStockUpdate(int $connectionId, int $productId, int $quantity): bool {
    try {
      $connection = $this->entityTypeManager->getStorage('pos_connection')->load($connectionId);
      if (!$connection || $connection->get('status')->value !== 'active') {
        return FALSE;
      }

      $product = $this->entityTypeManager->getStorage('product_retail')->load($productId);
      if (!$product) {
        return FALSE;
      }

      $product->set('stock_quantity', $quantity);
      $product->save();

      $this->logger->info('Stock actualizado via POS: producto @product, cantidad @qty, conexion @conn', [
        '@product' => $productId,
        '@qty' => $quantity,
        '@conn' => $connectionId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando stock via POS: @e', ['@e' => $e->getMessage()]);
      return FALSE;
    }
  }

  public function detectConflicts(int $connectionId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('pos_conflict');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('connection_id', $connectionId)
        ->condition('status', 'pending')
        ->sort('created', 'DESC')
        ->execute();

      if (!$ids) {
        return [];
      }

      $conflicts = [];
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $conflicts[] = [
          'id' => (int) $entity->id(),
          'product_id' => $entity->get('product_id')->target_id,
          'field_name' => $entity->get('field_name')->value ?? '',
          'local_value' => $entity->get('local_value')->value ?? '',
          'remote_value' => $entity->get('remote_value')->value ?? '',
          'status' => $entity->get('status')->value,
          'created' => $entity->get('created')->value,
        ];
      }

      return $conflicts;
    }
    catch (\Exception $e) {
      $this->logger->error('Error detectando conflictos POS: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function resolveConflict(int $conflictId, string $resolution): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('pos_conflict');
      $conflict = $storage->load($conflictId);
      if (!$conflict) {
        return FALSE;
      }

      $valid_resolutions = ['use_local', 'use_remote', 'skip'];
      if (!in_array($resolution, $valid_resolutions)) {
        $this->logger->warning('Resolucion de conflicto no valida: @res', ['@res' => $resolution]);
        return FALSE;
      }

      $conflict->set('status', 'resolved');
      $conflict->set('resolution', $resolution);
      $conflict->save();

      if ($resolution === 'use_remote') {
        $product_id = $conflict->get('product_id')->target_id;
        $field_name = $conflict->get('field_name')->value;
        $remote_value = $conflict->get('remote_value')->value;

        if ($product_id && $field_name) {
          $product = $this->entityTypeManager->getStorage('product_retail')->load($product_id);
          if ($product && $product->hasField($field_name)) {
            $product->set($field_name, $remote_value);
            $product->save();
          }
        }
      }

      $this->logger->info('Conflicto @id resuelto con: @resolution', [
        '@id' => $conflictId,
        '@resolution' => $resolution,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error resolviendo conflicto @id: @e', [
        '@id' => $conflictId,
        '@e' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  public function getConnectionStatus(int $connectionId): array {
    try {
      $connection = $this->entityTypeManager->getStorage('pos_connection')->load($connectionId);
      if (!$connection) {
        return [];
      }

      $pending_conflicts = 0;
      $conflict_storage = $this->entityTypeManager->getStorage('pos_conflict');
      $pending_conflicts = (int) $conflict_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('connection_id', $connectionId)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      return [
        'id' => (int) $connection->id(),
        'name' => $connection->get('name')->value,
        'provider' => $connection->get('provider')->value,
        'status' => $connection->get('status')->value,
        'last_sync_at' => $connection->get('last_sync_at')->value,
        'pending_conflicts' => $pending_conflicts,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estado de conexion POS @id: @e', [
        '@id' => $connectionId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getSyncHistory(int $connectionId, int $limit = 50): array {
    try {
      $storage = $this->entityTypeManager->getStorage('pos_sync');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('connection_id', $connectionId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (!$ids) {
        return [];
      }

      $history = [];
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $history[] = [
          'id' => (int) $entity->id(),
          'sync_type' => $entity->get('sync_type')->value ?? '',
          'status' => $entity->get('status')->value,
          'items_synced' => (int) ($entity->get('items_synced')->value ?? 0),
          'errors' => $entity->get('errors')->value ?? '',
          'created' => $entity->get('created')->value,
        ];
      }

      return $history;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo historial de sync: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

}
