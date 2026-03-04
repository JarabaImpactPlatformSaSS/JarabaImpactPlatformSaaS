<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * HubSpot CRM connector.
 *
 * GAP-CRM: Syncs contacts and deals to HubSpot via API v3.
 * SECRET-MGMT-001: API key from getenv(), NEVER in config/sync/.
 * API-WHITELIST-001: Only defined fields are synced.
 */
class HubSpotConnector implements CrmConnectorInterface {

  /**
   * HubSpot API v3 base URL.
   */
  protected const API_BASE = 'https://api.hubapi.com';

  /**
   * Allowed contact fields for sync (API-WHITELIST-001).
   */
  protected const ALLOWED_CONTACT_FIELDS = [
    'email', 'firstname', 'lastname', 'phone', 'company',
    'jobtitle', 'website', 'city', 'country',
  ];

  /**
   * Allowed deal fields for sync (API-WHITELIST-001).
   */
  protected const ALLOWED_DEAL_FIELDS = [
    'dealname', 'amount', 'pipeline', 'dealstage',
    'closedate', 'description',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ClientInterface $httpClient,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'hubspot';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'HubSpot';
  }

  /**
   * {@inheritdoc}
   */
  public function syncContact(int $userId): bool {
    $apiKey = $this->getApiKey();
    if (!$apiKey) {
      return FALSE;
    }

    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user) {
        return FALSE;
      }

      $properties = [
        'email' => $user->getEmail(),
        'firstname' => $user->get('field_first_name')->value ?? '',
        'lastname' => $user->get('field_last_name')->value ?? '',
      ];

      // Filter to allowed fields only.
      $properties = array_intersect_key($properties, array_flip(self::ALLOWED_CONTACT_FIELDS));

      $response = $this->httpClient->request('POST', self::API_BASE . '/crm/v3/objects/contacts', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => ['properties' => $properties],
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode >= 200 && $statusCode < 300) {
        $this->logger->info('HubSpot contact synced for user @id', ['@id' => $userId]);
        return TRUE;
      }

      // 409 = contact already exists, try update.
      if ($statusCode === 409) {
        return $this->updateHubSpotContact($apiKey, $user->getEmail(), $properties);
      }

      $this->logger->warning('HubSpot contact sync failed for user @id: HTTP @code', [
        '@id' => $userId,
        '@code' => $statusCode,
      ]);
      return FALSE;
    }
    catch (\Throwable $e) {
      $this->logger->error('HubSpot sync error: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDeal(int $subscriptionId): bool {
    $apiKey = $this->getApiKey();
    if (!$apiKey) {
      return FALSE;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('crm_opportunity');
      $opportunity = $storage->load($subscriptionId);
      if (!$opportunity) {
        return FALSE;
      }

      $properties = [
        'dealname' => $opportunity->label() ?? 'Deal #' . $subscriptionId,
        'amount' => $opportunity->get('value')->value ?? 0,
        'dealstage' => $this->mapStageToHubSpot($opportunity->get('stage')->value ?? ''),
      ];

      $properties = array_intersect_key($properties, array_flip(self::ALLOWED_DEAL_FIELDS));

      $response = $this->httpClient->request('POST', self::API_BASE . '/crm/v3/objects/deals', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => ['properties' => $properties],
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode >= 200 && $statusCode < 300) {
        $this->logger->info('HubSpot deal synced for opportunity @id', ['@id' => $subscriptionId]);
        return TRUE;
      }

      return FALSE;
    }
    catch (\Throwable $e) {
      $this->logger->error('HubSpot deal sync error: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): array {
    $apiKey = $this->getApiKey();
    if (!$apiKey) {
      return [
        'connected' => FALSE,
        'last_sync' => NULL,
        'error' => 'API key not configured',
        'stats' => ['contacts_synced' => 0, 'deals_synced' => 0],
      ];
    }

    try {
      $response = $this->httpClient->request('GET', self::API_BASE . '/crm/v3/objects/contacts?limit=1', [
        'headers' => ['Authorization' => 'Bearer ' . $apiKey],
        'http_errors' => FALSE,
      ]);

      return [
        'connected' => $response->getStatusCode() === 200,
        'last_sync' => \Drupal::state()->get('jaraba_crm.hubspot.last_sync'),
        'error' => $response->getStatusCode() !== 200 ? 'HTTP ' . $response->getStatusCode() : NULL,
        'stats' => [
          'contacts_synced' => (int) \Drupal::state()->get('jaraba_crm.hubspot.contacts_synced', 0),
          'deals_synced' => (int) \Drupal::state()->get('jaraba_crm.hubspot.deals_synced', 0),
        ],
      ];
    }
    catch (\Throwable $e) {
      return [
        'connected' => FALSE,
        'last_sync' => NULL,
        'error' => $e->getMessage(),
        'stats' => ['contacts_synced' => 0, 'deals_synced' => 0],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(array $credentials): bool {
    $token = $credentials['api_key'] ?? '';
    if (!$token) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('GET', self::API_BASE . '/crm/v3/objects/contacts?limit=1', [
        'headers' => ['Authorization' => 'Bearer ' . $token],
        'http_errors' => FALSE,
        'timeout' => 10,
      ]);
      return $response->getStatusCode() === 200;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Gets the HubSpot API key from environment.
   *
   * SECRET-MGMT-001: NEVER from config/sync/.
   */
  protected function getApiKey(): ?string {
    $key = getenv('HUBSPOT_API_KEY');
    return $key ?: NULL;
  }

  /**
   * Updates an existing HubSpot contact by email.
   */
  protected function updateHubSpotContact(string $apiKey, string $email, array $properties): bool {
    try {
      $response = $this->httpClient->request('PATCH', self::API_BASE . '/crm/v3/objects/contacts/' . urlencode($email) . '?idProperty=email', [
        'headers' => [
          'Authorization' => 'Bearer ' . $apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => ['properties' => $properties],
        'http_errors' => FALSE,
      ]);
      return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Maps internal pipeline stage to HubSpot deal stage.
   */
  protected function mapStageToHubSpot(string $stage): string {
    $map = [
      'lead' => 'qualifiedtobuy',
      'qualified' => 'presentationscheduled',
      'proposal' => 'decisionmakerboughtin',
      'negotiation' => 'contractsent',
      'closed_won' => 'closedwon',
      'closed_lost' => 'closedlost',
    ];
    return $map[$stage] ?? 'appointmentscheduled';
  }

}
