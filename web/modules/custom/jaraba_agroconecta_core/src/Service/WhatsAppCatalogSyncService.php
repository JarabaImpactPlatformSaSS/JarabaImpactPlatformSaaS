<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Sincroniza productos de AgroConecta con el catálogo de WhatsApp Commerce.
 *
 * Usa la Meta Graph API v18.0 para mantener el catálogo de WhatsApp
 * actualizado con los productos activos del marketplace.
 *
 * Doc: https://developers.facebook.com/docs/commerce-platform/catalog/
 */
class WhatsAppCatalogSyncService {

  /**
   * Meta Graph API base URL.
   */
  protected const GRAPH_API_URL = 'https://graph.facebook.com/v18.0';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?WhatsAppApiService $whatsAppApi,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza todos los productos activos con el catálogo de WhatsApp.
   *
   * @param string $catalogId
   *   ID del catálogo en Meta Commerce Manager.
   * @param int $tenantId
   *   ID del tenant cuyos productos sincronizar.
   *
   * @return array{synced: int, errors: int, skipped: int}
   *   Resumen de la sincronización.
   */
  public function syncProducts(string $catalogId, int $tenantId): array {
    $result = ['synced' => 0, 'errors' => 0, 'skipped' => 0];

    if ($this->whatsAppApi === null) {
      $this->logger->warning('WhatsAppApiService no disponible para sync.');
      return $result;
    }

    try {
      $productIds = $this->entityTypeManager->getStorage('product_agro')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', TRUE)
        ->condition('tenant_id', $tenantId)
        ->execute();

      $products = $this->entityTypeManager->getStorage('product_agro')
        ->loadMultiple($productIds);

      $batch = [];
      foreach ($products as $product) {
        $price = (float) ($product->get('price')->value ?? 0);
        if ($price <= 0) {
          $result['skipped']++;
          continue;
        }

        $imageUrl = '';
        if ($product->hasField('images') && !$product->get('images')->isEmpty()) {
          $imageEntity = $product->get('images')->entity;
          if ($imageEntity !== null) {
            $imageUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($imageEntity->getFileUri());
          }
        }

        $batch[] = [
          'method' => 'UPDATE',
          'retailer_id' => 'agro_' . $product->id(),
          'data' => [
            'name' => $product->label() ?? '',
            'description' => strip_tags((string) ($product->get('description')->value ?? '')),
            'availability' => 'in stock',
            'price' => (int) round($price * 100),
            'currency' => 'EUR',
            'image_link' => $imageUrl,
            'url' => '',
          ],
        ];

        if (count($batch) >= 20) {
          $batchResult = $this->sendBatch($catalogId, $batch);
          $result['synced'] += $batchResult['synced'];
          $result['errors'] += $batchResult['errors'];
          $batch = [];
        }
      }

      if (count($batch) > 0) {
        $batchResult = $this->sendBatch($catalogId, $batch);
        $result['synced'] += $batchResult['synced'];
        $result['errors'] += $batchResult['errors'];
      }

      $this->logger->info('WhatsApp catalog sync: @synced synced, @errors errors, @skipped skipped.', [
        '@synced' => $result['synced'],
        '@errors' => $result['errors'],
        '@skipped' => $result['skipped'],
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp catalog sync failed: @message', ['@message' => $e->getMessage()]);
      $result['errors']++;
    }

    return $result;
  }

  /**
   * Envía un batch de productos a la API de Meta.
   *
   * @param string $catalogId
   *   ID del catálogo.
   * @param array<int, array<string, mixed>> $items
   *   Items a enviar.
   *
   * @return array{synced: int, errors: int}
   *   Resultado del batch.
   */
  protected function sendBatch(string $catalogId, array $items): array {
    $result = ['synced' => 0, 'errors' => 0];

    try {
      $accessToken = $this->getAccessToken();

      if ($accessToken === '') {
        $result['errors'] = count($items);
        return $result;
      }

      $url = self::GRAPH_API_URL . '/' . $catalogId . '/items_batch';
      $response = \Drupal::httpClient()->post($url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'item_type' => 'PRODUCT_ITEM',
          'requests' => $items,
        ],
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      if (is_array($data) && isset($data['num_handled_items'])) {
        $result['synced'] = (int) $data['num_handled_items'];
      }
      else {
        $result['errors'] = count($items);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Batch send error: @msg', ['@msg' => $e->getMessage()]);
      $result['errors'] = count($items);
    }

    return $result;
  }

  /**
   * Obtiene el access token de WhatsApp.
   */
  protected function getAccessToken(): string {
    $token = getenv('WHATSAPP_ACCESS_TOKEN');
    return is_string($token) && $token !== '' ? $token : '';
  }

}
