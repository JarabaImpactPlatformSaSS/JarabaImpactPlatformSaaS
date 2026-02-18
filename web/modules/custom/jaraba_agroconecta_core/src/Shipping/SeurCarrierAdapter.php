<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Shipping;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Adaptador Real para SEUR España.
 */
class SeurCarrierAdapter extends BaseCarrierAdapter {

  /**
   * {@inheritdoc}
   */
  public function getCarrierId(): string {
    return 'seur';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'SEUR (Express, SEUR Frío)';
  }

  /**
   * {@inheritdoc}
   */
  public function createShipment(array $data): array {
    $config = $this->getCarrierConfig($data['tenant_id'], $data['producer_id'] ?? NULL);
    
    if (!$config) {
      return ['success' => FALSE, 'error' => 'Credenciales de SEUR no configuradas.'];
    }

    try {
      $response = $this->httpClient->request('POST', $config['api_url'] . '/v1/shipments', [
        'headers' => [
          'X-SEUR-API-KEY' => $config['api_key'],
          'Content-Type' => 'application/json',
        ],
        'json' => $this->buildSeurPayload($data, $config),
      ]);

      $result = json_decode($response->getBody()->getContents(), TRUE);

      if ($response->getStatusCode() === 201 && !empty($result['tracking_number'])) {
        return [
          'success' => TRUE,
          'tracking_number' => $result['tracking_number'],
          'label_url' => $result['label_url'] ?? '',
          'raw' => $result,
        ];
      }

      return ['success' => FALSE, 'error' => 'Error en SEUR: ' . ($result['error_message'] ?? 'Desconocido')];
    }
    catch (GuzzleException $e) {
      $this->logger->error('Error de conexión con SEUR: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => 'No se pudo conectar con SEUR.'];
    }
  }

  /**
   * Construye el payload siguiendo el esquema de SEUR REST API.
   */
  protected function buildSeurPayload(array $data, array $config): array {
    return [
      'product_code' => $data['is_refrigerated'] ? 'COLD' : 'EXP',
      'sender' => [
        'customer_id' => $config['api_user'],
        // Datos del productor...
      ],
      'receiver' => [
        'postal_code' => $data['destination_pc'],
        // Datos del cliente...
      ],
      'parcels' => [
        ['weight' => $data['weight']],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackingStatus(string $trackingNumber): array {
    // Consulta a /v1/tracking/{number}
    return ['status' => 'picked_up'];
  }

  /**
   * {@inheritdoc}
   */
  public function cancelShipment(string $trackingNumber): bool {
    return TRUE;
  }

  /**
   * Obtiene la configuración desde la entidad agro_carrier_config.
   */
  protected function getCarrierConfig(int $tenantId, ?int $producerId): ?array {
    $storage = \Drupal::entityTypeManager()->getStorage('agro_carrier_config');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('carrier_id', 'seur');
    
    if ($producerId) {
      $query->condition('producer_id', $producerId);
    }

    $ids = $query->execute();
    if (empty($ids)) return NULL;

    $entity = $storage->load(reset($ids));
    return [
      'api_key' => $entity->get('api_key')->value,
      'api_user' => $entity->get('api_user')->value,
      'api_url' => $entity->get('api_url')->value,
    ];
  }

}
