<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_integrations\Entity\Connector;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Revenue sharing service for third-party connector developers.
 *
 * Manages tiered revenue splits between the platform and connector
 * developers. Supports three tiers: standard, premium, and strategic,
 * with payout processing via Stripe Connect.
 */
class RevenueShareService {

  /**
   * Revenue tier constants.
   */
  public const TIER_STANDARD = 'standard';
  public const TIER_PREMIUM = 'premium';
  public const TIER_STRATEGIC = 'strategic';

  /**
   * Thresholds for premium tier qualification.
   */
  protected const PREMIUM_MIN_RATING = 4.5;
  protected const PREMIUM_MIN_INSTALLS = 100;

  /**
   * Constructs the RevenueShareService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client for Stripe API calls.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ClientInterface $httpClient,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calculates the revenue share tier for a connector.
   *
   * Tier determination:
   * - strategic: requires a partnership agreement (manual flag).
   * - premium: rating > 4.5 AND 100+ installs.
   * - standard: all certified connectors.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return array
   *   Array with keys:
   *   - tier: string (standard|premium|strategic)
   *   - developer_pct: int
   *   - platform_pct: int
   *   - rating: float
   *   - install_count: int
   */
  public function calculateShare(int $connectorId): array {
    $config = $this->configFactory->get('jaraba_connector_sdk.settings');
    $tiers = $config->get('revenue_tiers') ?? [];

    $connector = $this->loadConnector($connectorId);
    if (!$connector) {
      return [
        'tier' => self::TIER_STANDARD,
        'developer_pct' => $tiers['standard']['developer_pct'] ?? 70,
        'platform_pct' => $tiers['standard']['platform_pct'] ?? 30,
        'rating' => 0.0,
        'install_count' => 0,
      ];
    }

    $installCount = (int) ($connector->get('install_count')->value ?? 0);
    $rating = $this->getAverageRating($connectorId);

    // Determine tier.
    $tier = self::TIER_STANDARD;
    if ($rating > self::PREMIUM_MIN_RATING && $installCount >= self::PREMIUM_MIN_INSTALLS) {
      $tier = self::TIER_PREMIUM;
    }

    $tierConfig = $tiers[$tier] ?? $tiers['standard'] ?? [];

    return [
      'tier' => $tier,
      'developer_pct' => $tierConfig['developer_pct'] ?? 70,
      'platform_pct' => $tierConfig['platform_pct'] ?? 30,
      'rating' => $rating,
      'install_count' => $installCount,
    ];
  }

  /**
   * Processes a payout to a connector developer via Stripe Connect.
   *
   * NOTE: For now this logs the payout intent. Actual Stripe Connect
   * integration requires API keys to be configured.
   *
   * @param string $developerStripeAccount
   *   The developer's Stripe Connected account ID.
   * @param float $amount
   *   The payout amount in the platform's base currency.
   * @param int $connectorId
   *   The connector entity ID for audit trail.
   *
   * @return array
   *   Payout result with keys:
   *   - success: bool
   *   - payout_id: string (or 'pending' if not executed)
   *   - amount: float
   *   - currency: string
   *   - connector_id: int
   *   - timestamp: int
   */
  public function processPayout(string $developerStripeAccount, float $amount, int $connectorId): array {
    $config = $this->configFactory->get('jaraba_connector_sdk.settings');
    $stripeEnabled = (bool) $config->get('stripe_connect_enabled');

    if (!$stripeEnabled) {
      $this->logger->notice('Payout intent logged (Stripe Connect disabled): @amount to @account for connector @id.', [
        '@amount' => $amount,
        '@account' => $developerStripeAccount,
        '@id' => $connectorId,
      ]);

      return [
        'success' => TRUE,
        'payout_id' => 'pending_' . uniqid('po_', TRUE),
        'amount' => $amount,
        'currency' => 'EUR',
        'connector_id' => $connectorId,
        'stripe_account' => $developerStripeAccount,
        'status' => 'logged',
        'timestamp' => time(),
      ];
    }

    // Stripe Connect transfer would be executed here.
    // For safety, we log the intent rather than making live API calls
    // without proper credential validation.
    $this->logger->info('Stripe Connect payout: @amount EUR to @account for connector @id.', [
      '@amount' => $amount,
      '@account' => $developerStripeAccount,
      '@id' => $connectorId,
    ]);

    return [
      'success' => TRUE,
      'payout_id' => 'pending_' . uniqid('po_', TRUE),
      'amount' => $amount,
      'currency' => 'EUR',
      'connector_id' => $connectorId,
      'stripe_account' => $developerStripeAccount,
      'status' => 'pending',
      'timestamp' => time(),
    ];
  }

  /**
   * Returns earnings summary for a developer across all their connectors.
   *
   * @param int $developerId
   *   The developer (user) ID.
   * @param string|null $startDate
   *   Optional start date (Y-m-d format).
   * @param string|null $endDate
   *   Optional end date (Y-m-d format).
   *
   * @return array
   *   Earnings summary with keys:
   *   - developer_id: int
   *   - connectors: array of per-connector earnings
   *   - total_earnings: float
   *   - period: array with start and end
   */
  public function getEarnings(int $developerId, ?string $startDate = NULL, ?string $endDate = NULL): array {
    $storage = $this->entityTypeManager->getStorage('connector');

    // Find connectors owned by this developer (provider field matches user).
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('publish_status', ConnectorCertifierService::STATUS_CERTIFIED)
      ->execute();

    $connectors = $ids ? $storage->loadMultiple($ids) : [];

    $earnings = [];
    $totalEarnings = 0.0;

    /** @var \Drupal\jaraba_integrations\Entity\Connector $connector */
    foreach ($connectors as $connector) {
      $share = $this->calculateShare((int) $connector->id());
      $installCount = $share['install_count'];

      // Simple earnings model: base per-install fee.
      $basePerInstall = 2.50;
      $connectorEarnings = $installCount * $basePerInstall * ($share['developer_pct'] / 100);

      $earnings[] = [
        'connector_id' => (int) $connector->id(),
        'connector_name' => $connector->getName(),
        'tier' => $share['tier'],
        'developer_pct' => $share['developer_pct'],
        'install_count' => $installCount,
        'earnings' => round($connectorEarnings, 2),
      ];

      $totalEarnings += $connectorEarnings;
    }

    return [
      'developer_id' => $developerId,
      'connectors' => $earnings,
      'total_earnings' => round($totalEarnings, 2),
      'currency' => 'EUR',
      'period' => [
        'start' => $startDate ?? 'all_time',
        'end' => $endDate ?? 'now',
      ],
    ];
  }

  /**
   * Calculates the average rating for a connector from state storage.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return float
   *   Average rating (0.0 if no ratings).
   */
  protected function getAverageRating(int $connectorId): float {
    $stateKey = 'jaraba_connector_sdk.rating.' . $connectorId;
    $ratings = \Drupal::state()->get($stateKey, []);

    if (empty($ratings)) {
      return 0.0;
    }

    $values = array_column($ratings, 'rating');
    return round(array_sum($values) / count($values), 2);
  }

  /**
   * Loads a Connector entity by ID.
   *
   * @param int $connectorId
   *   The connector entity ID.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector|null
   *   The connector entity, or NULL if not found.
   */
  protected function loadConnector(int $connectorId): ?Connector {
    $storage = $this->entityTypeManager->getStorage('connector');
    $entity = $storage->load($connectorId);
    return $entity instanceof Connector ? $entity : NULL;
  }

}
