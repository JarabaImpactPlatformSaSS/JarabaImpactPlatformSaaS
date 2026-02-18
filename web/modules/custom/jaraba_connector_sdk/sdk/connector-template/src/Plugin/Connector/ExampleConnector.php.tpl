<?php

declare(strict_types=1);

namespace Drupal\{{machine_name}}\Plugin\Connector;

use Drupal\jaraba_connector_sdk\Sdk\BaseConnector;

/**
 * {{name}} connector implementation.
 */
class {{class_name}}Connector extends BaseConnector {

  /**
   * {@inheritdoc}
   */
  public function install(): bool {
    // TODO: Implement installation logic (provision API tokens, etc.).
    return parent::install();
  }

  /**
   * {@inheritdoc}
   */
  public function configure(array $settings): bool {
    // TODO: Validate and store connector-specific settings.
    return parent::configure($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function sync(): array {
    // TODO: Implement data synchronisation with the external service.
    return [
      'synced' => 0,
      'errors' => [],
      'timestamp' => time(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function handleWebhook(array $payload): array {
    // TODO: Process incoming webhook payloads.
    return ['status' => 'ok'];
  }

  /**
   * {@inheritdoc}
   */
  public function getManifest(): array {
    return [
      'machine_name' => '{{machine_name}}',
      'display_name' => '{{name}}',
      'version' => '1.0.0',
      'category' => 'custom',
      'capabilities' => [
        'oauth2' => FALSE,
        'webhooks' => FALSE,
        'sync' => FALSE,
        'realtime' => FALSE,
      ],
      'config_fields' => [
        'api_key' => [
          'type' => 'string',
          'label' => 'API Key',
          'required' => TRUE,
        ],
      ],
      'permissions' => [
        'read_data' => TRUE,
        'write_data' => FALSE,
      ],
    ];
  }

}
