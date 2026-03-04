<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Admin Center Settings — Configuración global de plataforma.
 *
 * Secciones:
 *   1. General: nombre plataforma, dominio, email soporte, idioma, timezone.
 *   2. Planes: CRUD de SaasPlan (lectura vía entity storage).
 *   3. Integraciones: estado de Stripe, Email, Slack, Analytics, AI.
 *   4. API Keys: listado, creación, revocación (roadmap).
 *
 * F6 — Doc 181 / Spec f104 §FASE 7.
 */
class AdminCenterSettingsService {

  use StringTranslationTrait;

  /**
   * Config object name for admin center settings.
   */
  const CONFIG_NAME = 'ecosistema_jaraba_core.admin_center_settings';

  /**
   * Integration definition keys.
   */
  const INTEGRATIONS = [
    'stripe' => [
      'label' => 'Stripe',
      'config' => 'ecosistema_jaraba_core.stripe',
      'check_keys' => ['public_key', 'secret_key'],
      'icon_category' => 'business',
      'icon_name' => 'credit-card',
    ],
    'email' => [
      'label' => 'Email (SMTP)',
      'config' => 'system.mail',
      'check_keys' => [],
      'icon_category' => 'ui',
      'icon_name' => 'mail',
    ],
    'slack' => [
      'label' => 'Slack',
      'config' => 'ecosistema_jaraba_core.alerting',
      'check_keys' => ['slack_webhook_url'],
      'icon_category' => 'ui',
      'icon_name' => 'chat',
    ],
    'analytics' => [
      'label' => 'Analytics',
      'config' => 'ecosistema_jaraba_core.admin_center_settings',
      'check_keys' => ['analytics_id'],
      'icon_category' => 'analytics',
      'icon_name' => 'chart-bar',
    ],
    'ai' => [
      'label' => 'AI Providers',
      'config' => 'ecosistema_jaraba_core.admin_center_settings',
      'check_keys' => ['ai_provider'],
      'icon_category' => 'ai',
      'icon_name' => 'sparkle',
    ],
  ];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  // ===========================================================================
  // GENERAL SETTINGS
  // ===========================================================================

  /**
   * Get all general platform settings.
   *
   * @return array
   *   Associative array of settings.
   */
  public function getGeneralSettings(): array {
    $config = $this->configFactory->get(self::CONFIG_NAME);

    return [
      'platform_name' => $config->get('platform_name') ?: 'Jaraba Impact Platform',
      'primary_domain' => $config->get('primary_domain') ?: '',
      'support_email' => $config->get('support_email') ?: '',
      'default_language' => $config->get('default_language') ?: 'es',
      'timezone' => $config->get('timezone') ?: 'Europe/Madrid',
      'logo_url' => $config->get('logo_url') ?: '',
    ];
  }

  /**
   * Save general platform settings.
   *
   * @param array $values
   *   Key-value pairs to save.
   *
   * @return bool
   *   TRUE on success.
   */
  public function saveGeneralSettings(array $values): bool {
    $allowed = ['platform_name', 'primary_domain', 'support_email', 'default_language', 'timezone', 'logo_url'];
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);

    foreach ($values as $key => $value) {
      if (in_array($key, $allowed, TRUE)) {
        $config->set($key, $value);
      }
    }

    $config->save();
    $this->logger->info('Admin Center settings updated: @keys', [
      '@keys' => implode(', ', array_keys($values)),
    ]);

    return TRUE;
  }

  // ===========================================================================
  // BILLING PLANS
  // ===========================================================================

  /**
   * List all SaaS plans.
   *
   * @return array
   *   Array of plan data.
   */
  public function listPlans(): array {
    $plans = [];

    try {
      $storage = $this->entityTypeManager->getStorage('saas_plan');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('weight', 'ASC')
        ->execute();

      foreach ($storage->loadMultiple($ids) as $plan) {
        $plans[] = $this->serializePlan($plan);
      }
    }
    catch (\Exception $e) {
      // SaasPlan entity may not exist — return empty.
      $this->logger->warning('Error listing SaaS plans: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $plans;
  }

  /**
   * Get a single plan by ID.
   *
   * @param int $planId
   *   Plan entity ID.
   *
   * @return array|null
   *   Plan data or NULL.
   */
  public function getPlan(int $planId): ?array {
    try {
      $plan = $this->entityTypeManager->getStorage('saas_plan')->load($planId);
      return $plan ? $this->serializePlan($plan) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Serialize a plan entity to API-friendly array.
   */
  protected function serializePlan(object $plan): array {
    $data = [
      'id' => (int) $plan->id(),
      'label' => $plan->label(),
      'status' => (bool) ($plan->get('status')->value ?? TRUE),
    ];

    // Safe field access for optional fields.
    $fieldMap = [
      'field_price_monthly' => 'price_monthly',
      'field_price_annual' => 'price_annual',
      'field_user_limit' => 'user_limit',
      'field_storage_gb' => 'storage_gb',
      'field_stripe_price_id' => 'stripe_price_id',
      'field_features' => 'features',
    ];

    foreach ($fieldMap as $fieldName => $key) {
      try {
        if ($plan->hasField($fieldName) && !$plan->get($fieldName)->isEmpty()) {
          $value = $plan->get($fieldName)->value;
          $data[$key] = is_numeric($value) ? (float) $value : $value;
        }
      }
      catch (\Exception $e) {
        // Field does not exist on this entity bundle.
      }
    }

    return $data;
  }

  // ===========================================================================
  // INTEGRATIONS
  // ===========================================================================

  /**
   * Get status of all integrations.
   *
   * @return array
   *   Array of integration status objects.
   */
  public function getIntegrationsStatus(): array {
    $integrations = [];

    foreach (self::INTEGRATIONS as $id => $def) {
      $status = 'not_configured';
      $details = '';

      try {
        $config = $this->configFactory->get($def['config']);
        $hasValues = TRUE;

        foreach ($def['check_keys'] as $key) {
          if (empty($config->get($key))) {
            $hasValues = FALSE;
            break;
          }
        }

        if (!empty($def['check_keys']) && $hasValues) {
          $status = 'active';
          $details = $this->t('Configured');
        }
        elseif (!empty($def['check_keys'])) {
          $status = 'not_configured';
          $details = $this->t('Pending configuration');
        }
        else {
          // No check keys → use Drupal default (e.g. mail system).
          $status = 'active';
          $details = $this->t('Using default');
        }
      }
      catch (\Exception $e) {
        $status = 'error';
        $details = $e->getMessage();
      }

      $integrations[] = [
        'id' => $id,
        'label' => $def['label'],
        'status' => $status,
        'details' => (string) $details,
        'icon_category' => $def['icon_category'],
        'icon_name' => $def['icon_name'],
      ];
    }

    return $integrations;
  }

  // ===========================================================================
  // API KEYS (Roadmap — stored in config for MVP)
  // ===========================================================================

  /**
   * List API keys from config.
   *
   * @return array
   *   Array of API key records.
   */
  public function listApiKeys(): array {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    return $config->get('api_keys') ?: [];
  }

  /**
   * Create a new API key.
   *
   * @param string $label
   *   Human-readable label.
   * @param string $scope
   *   Scope: read, write, admin.
   *
   * @return array
   *   The created key record including the plaintext key (shown once).
   */
  public function createApiKey(string $label, string $scope = 'read'): array {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    $keys = $config->get('api_keys') ?: [];

    // Generate secure random key.
    $plainKey = 'jrb_' . bin2hex(random_bytes(24));
    $hashedKey = hash('sha256', $plainKey);

    $record = [
      'id' => bin2hex(random_bytes(8)),
      'label' => $label,
      'scope' => in_array($scope, ['read', 'write', 'admin'], TRUE) ? $scope : 'read',
      'key_prefix' => substr($plainKey, 0, 8) . '...',
      'key_hash' => $hashedKey,
      'created' => date('c'),
      'last_used' => NULL,
      'status' => 'active',
    ];

    $keys[] = $record;
    $config->set('api_keys', $keys)->save();

    $this->logger->info('API key created: @label (scope: @scope)', [
      '@label' => $label,
      '@scope' => $scope,
    ]);

    // Return with plaintext key (shown only once).
    $record['key'] = $plainKey;
    return $record;
  }

  /**
   * Revoke an API key.
   *
   * @param string $keyId
   *   The key ID to revoke.
   *
   * @return bool
   *   TRUE if revoked.
   */
  public function revokeApiKey(string $keyId): bool {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    $keys = $config->get('api_keys') ?: [];

    foreach ($keys as &$key) {
      if ($key['id'] === $keyId && $key['status'] === 'active') {
        $key['status'] = 'revoked';
        $config->set('api_keys', $keys)->save();

        $this->logger->info('API key revoked: @id', ['@id' => $keyId]);
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * GAP-API-KEYS: Rotate an API key (revoke old, create new with same label/scope).
   *
   * @param string $keyId
   *   The key ID to rotate.
   *
   * @return array|null
   *   The new key record with plaintext key, or NULL if not found.
   */
  public function rotateApiKey(string $keyId): ?array {
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    $keys = $config->get('api_keys') ?: [];

    $label = NULL;
    $scope = 'read';

    // Find and revoke the old key.
    foreach ($keys as &$key) {
      if ($key['id'] === $keyId && $key['status'] === 'active') {
        $label = $key['label'];
        $scope = $key['scope'] ?? 'read';
        $key['status'] = 'revoked';
        break;
      }
    }

    if ($label === NULL) {
      return NULL;
    }

    // Save revocation.
    $config->set('api_keys', $keys)->save();

    // Create new key with same label and scope.
    $newRecord = $this->createApiKey($label . ' (rotated)', $scope);

    $this->logger->info('API key rotated: @old_id → @new_id', [
      '@old_id' => $keyId,
      '@new_id' => $newRecord['id'],
    ]);

    return $newRecord;
  }

  /**
   * GAP-API-KEYS: Validate an API key against stored hashes.
   *
   * SECRET-MGMT-001: Uses hash_equals for timing-safe comparison.
   *
   * @param string $apiKey
   *   The plaintext API key to validate.
   *
   * @return array|null
   *   The key record if valid and active, or NULL.
   */
  public function validateApiKey(string $apiKey): ?array {
    if (strlen($apiKey) < 32 || !str_starts_with($apiKey, 'jrb_')) {
      return NULL;
    }

    $hashedInput = hash('sha256', $apiKey);
    $keys = $this->listApiKeys();

    foreach ($keys as &$key) {
      if ($key['status'] === 'active' && hash_equals($key['key_hash'], $hashedInput)) {
        // Update last_used timestamp.
        $key['last_used'] = date('c');
        $config = $this->configFactory->getEditable(self::CONFIG_NAME);
        $allKeys = $config->get('api_keys') ?: [];
        foreach ($allKeys as &$stored) {
          if ($stored['id'] === $key['id']) {
            $stored['last_used'] = $key['last_used'];
            break;
          }
        }
        $config->set('api_keys', $allKeys)->save();

        return $key;
      }
    }

    return NULL;
  }

  // ===========================================================================
  // FULL SETTINGS PAYLOAD
  // ===========================================================================

  /**
   * Get complete settings data for the settings page.
   *
   * @return array
   *   All settings sections combined.
   */
  public function getSettingsOverview(): array {
    return [
      'general' => $this->getGeneralSettings(),
      'plans' => $this->listPlans(),
      'integrations' => $this->getIntegrationsStatus(),
      'api_keys' => $this->listApiKeys(),
    ];
  }

}
