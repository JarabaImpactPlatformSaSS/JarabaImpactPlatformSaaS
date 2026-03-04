<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Salesforce CRM connector.
 *
 * GAP-CRM: Syncs contacts and deals to Salesforce via REST API.
 * SECRET-MGMT-001: Credentials from getenv(), NEVER in config/sync/.
 * API-WHITELIST-001: Only defined fields are synced.
 */
class SalesforceConnector implements CrmConnectorInterface {

  /**
   * Allowed Salesforce Contact fields (API-WHITELIST-001).
   */
  protected const ALLOWED_CONTACT_FIELDS = [
    'Email', 'FirstName', 'LastName', 'Phone', 'Title',
    'MailingCity', 'MailingCountry', 'Description',
  ];

  /**
   * Allowed Salesforce Opportunity fields (API-WHITELIST-001).
   */
  protected const ALLOWED_OPPORTUNITY_FIELDS = [
    'Name', 'Amount', 'StageName', 'CloseDate', 'Description',
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
    return 'salesforce';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Salesforce';
  }

  /**
   * {@inheritdoc}
   */
  public function syncContact(int $userId): bool {
    $auth = $this->authenticate();
    if (!$auth) {
      return FALSE;
    }

    try {
      $user = $this->entityTypeManager->getStorage('user')->load($userId);
      if (!$user) {
        return FALSE;
      }

      $data = [
        'Email' => $user->getEmail(),
        'FirstName' => $user->get('field_first_name')->value ?? '',
        'LastName' => $user->get('field_last_name')->value ?? $user->getDisplayName(),
      ];

      $data = array_intersect_key($data, array_flip(self::ALLOWED_CONTACT_FIELDS));

      $response = $this->httpClient->request('POST', $auth['instance_url'] . '/services/data/v59.0/sobjects/Contact/', [
        'headers' => [
          'Authorization' => 'Bearer ' . $auth['access_token'],
          'Content-Type' => 'application/json',
        ],
        'json' => $data,
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode === 201 || $statusCode === 200) {
        $this->logger->info('Salesforce contact synced for user @id', ['@id' => $userId]);
        return TRUE;
      }

      $this->logger->warning('Salesforce contact sync failed for user @id: HTTP @code', [
        '@id' => $userId,
        '@code' => $statusCode,
      ]);
      return FALSE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Salesforce sync error: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncDeal(int $subscriptionId): bool {
    $auth = $this->authenticate();
    if (!$auth) {
      return FALSE;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('crm_opportunity');
      $opportunity = $storage->load($subscriptionId);
      if (!$opportunity) {
        return FALSE;
      }

      $data = [
        'Name' => $opportunity->label() ?? 'Opportunity #' . $subscriptionId,
        'Amount' => (float) ($opportunity->get('value')->value ?? 0),
        'StageName' => $this->mapStageToSalesforce($opportunity->get('stage')->value ?? ''),
        'CloseDate' => date('Y-m-d', strtotime('+30 days')),
      ];

      $data = array_intersect_key($data, array_flip(self::ALLOWED_OPPORTUNITY_FIELDS));

      $response = $this->httpClient->request('POST', $auth['instance_url'] . '/services/data/v59.0/sobjects/Opportunity/', [
        'headers' => [
          'Authorization' => 'Bearer ' . $auth['access_token'],
          'Content-Type' => 'application/json',
        ],
        'json' => $data,
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode === 201 || $statusCode === 200) {
        $this->logger->info('Salesforce opportunity synced: @id', ['@id' => $subscriptionId]);
        return TRUE;
      }
      return FALSE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Salesforce deal sync error: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): array {
    $auth = $this->authenticate();

    return [
      'connected' => $auth !== NULL,
      'last_sync' => \Drupal::state()->get('jaraba_crm.salesforce.last_sync'),
      'error' => $auth === NULL ? 'Authentication failed' : NULL,
      'stats' => [
        'contacts_synced' => (int) \Drupal::state()->get('jaraba_crm.salesforce.contacts_synced', 0),
        'deals_synced' => (int) \Drupal::state()->get('jaraba_crm.salesforce.deals_synced', 0),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testConnection(array $credentials): bool {
    $clientId = $credentials['client_id'] ?? '';
    $clientSecret = $credentials['client_secret'] ?? '';
    $username = $credentials['username'] ?? '';
    $password = $credentials['password'] ?? '';

    if (!$clientId || !$clientSecret || !$username || !$password) {
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('POST', 'https://login.salesforce.com/services/oauth2/token', [
        'form_params' => [
          'grant_type' => 'password',
          'client_id' => $clientId,
          'client_secret' => $clientSecret,
          'username' => $username,
          'password' => $password,
        ],
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
   * Authenticates with Salesforce OAuth2 password flow.
   *
   * SECRET-MGMT-001: Credentials from getenv().
   *
   * @return array|null
   *   Auth response with access_token and instance_url, or NULL.
   */
  protected function authenticate(): ?array {
    $clientId = getenv('SALESFORCE_CLIENT_ID');
    $clientSecret = getenv('SALESFORCE_CLIENT_SECRET');
    $username = getenv('SALESFORCE_USERNAME');
    $password = getenv('SALESFORCE_PASSWORD');

    if (!$clientId || !$clientSecret || !$username || !$password) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('POST', 'https://login.salesforce.com/services/oauth2/token', [
        'form_params' => [
          'grant_type' => 'password',
          'client_id' => $clientId,
          'client_secret' => $clientSecret,
          'username' => $username,
          'password' => $password,
        ],
        'http_errors' => FALSE,
        'timeout' => 15,
      ]);

      if ($response->getStatusCode() !== 200) {
        return NULL;
      }

      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (\Throwable $e) {
      $this->logger->error('Salesforce auth error: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Maps internal pipeline stage to Salesforce stage name.
   */
  protected function mapStageToSalesforce(string $stage): string {
    $map = [
      'lead' => 'Prospecting',
      'qualified' => 'Qualification',
      'proposal' => 'Proposal/Price Quote',
      'negotiation' => 'Negotiation/Review',
      'closed_won' => 'Closed Won',
      'closed_lost' => 'Closed Lost',
    ];
    return $map[$stage] ?? 'Prospecting';
  }

}
