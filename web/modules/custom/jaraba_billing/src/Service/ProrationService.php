<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Calculates proration previews for subscription changes.
 *
 * GAP-PRORATION: Uses Stripe's upcoming invoice API with
 * subscription_items override to preview prorated amounts
 * before the user confirms an upgrade/downgrade.
 */
class ProrationService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly StripeConnectService $stripeConnect,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Previews proration for a plan change.
   *
   * Uses Stripe's /invoices/upcoming endpoint with subscription_items
   * to calculate what the user would pay/be credited for a mid-cycle
   * upgrade or downgrade.
   *
   * @param string $subscriptionId
   *   Current Stripe subscription ID.
   * @param string $newPriceId
   *   The new price ID to switch to.
   *
   * @return array
   *   Proration preview with keys:
   *   - type: 'upgrade' or 'downgrade'
   *   - current_amount: Current plan amount per period.
   *   - new_amount: New plan amount per period.
   *   - credit: Credit for unused time on current plan.
   *   - charge: Prorated charge for new plan.
   *   - net_amount: Net amount to pay (positive) or credit (negative).
   *   - currency: ISO 4217 currency code.
   *   - proration_date: Timestamp of proration.
   *   - line_items: Detailed line items.
   *   - next_billing_date: Next billing cycle date.
   *
   * @throws \RuntimeException
   *   If subscription or calculation fails.
   */
  public function previewProration(string $subscriptionId, string $newPriceId): array {
    // Get current subscription details.
    $subscription = $this->stripeConnect->stripeRequest(
      'GET',
      '/subscriptions/' . $subscriptionId
    );

    $currentItemId = $subscription['items']['data'][0]['id'] ?? NULL;
    $currentPriceId = $subscription['items']['data'][0]['price']['id'] ?? NULL;
    $currentAmount = ($subscription['items']['data'][0]['price']['unit_amount'] ?? 0) / 100;
    $customerId = $subscription['customer'] ?? NULL;

    if (!$currentItemId || !$customerId) {
      throw new \RuntimeException('Invalid subscription data for proration preview.');
    }

    $prorationDate = time();

    // Preview upcoming invoice with the proposed change.
    $upcoming = $this->stripeConnect->stripeRequest('GET', '/invoices/upcoming', [
      'customer' => $customerId,
      'subscription' => $subscriptionId,
      'subscription_items' => [
        [
          'id' => $currentItemId,
          'price' => $newPriceId,
        ],
      ],
      'subscription_proration_date' => $prorationDate,
    ]);

    // Get the new price details.
    $newPrice = $this->stripeConnect->stripeRequest('GET', '/prices/' . $newPriceId);
    $newAmount = ($newPrice['unit_amount'] ?? 0) / 100;

    // Extract proration line items.
    $lineItems = [];
    $credit = 0;
    $charge = 0;

    foreach (($upcoming['lines']['data'] ?? []) as $line) {
      $amount = ($line['amount'] ?? 0) / 100;
      $isProration = !empty($line['proration']);

      $lineItems[] = [
        'description' => $line['description'] ?? '',
        'amount' => $amount,
        'quantity' => $line['quantity'] ?? 1,
        'is_proration' => $isProration,
        'period_start' => $line['period']['start'] ?? NULL,
        'period_end' => $line['period']['end'] ?? NULL,
      ];

      if ($isProration) {
        if ($amount < 0) {
          $credit += abs($amount);
        }
        else {
          $charge += $amount;
        }
      }
    }

    $netAmount = ($upcoming['amount_due'] ?? 0) / 100;
    $currency = strtoupper($upcoming['currency'] ?? 'EUR');

    return [
      'type' => $newAmount > $currentAmount ? 'upgrade' : 'downgrade',
      'current_price_id' => $currentPriceId,
      'new_price_id' => $newPriceId,
      'current_amount' => $currentAmount,
      'new_amount' => $newAmount,
      'credit' => round($credit, 2),
      'charge' => round($charge, 2),
      'net_amount' => round($netAmount, 2),
      'currency' => $currency,
      'proration_date' => $prorationDate,
      'line_items' => $lineItems,
      'next_billing_date' => $subscription['current_period_end'] ?? NULL,
      'subtotal' => ($upcoming['subtotal'] ?? 0) / 100,
      'tax' => ($upcoming['tax'] ?? 0) / 100,
      'total' => ($upcoming['total'] ?? 0) / 100,
    ];
  }

  /**
   * Resolves a tenant's subscription ID from tenant ID.
   *
   * @param int|string $tenantId
   *   The tenant entity ID.
   *
   * @return string|null
   *   Stripe subscription ID or NULL.
   */
  public function getSubscriptionIdForTenant(int|string $tenantId): ?string {
    try {
      $storage = $this->entityTypeManager->getStorage('tenant');
      $tenant = $storage->load($tenantId);
      if ($tenant && $tenant->hasField('stripe_subscription_id')) {
        return $tenant->get('stripe_subscription_id')->value;
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Could not resolve subscription for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

}
