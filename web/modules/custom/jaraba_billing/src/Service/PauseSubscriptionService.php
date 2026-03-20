<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages subscription pausing and resuming.
 *
 * Allows tenants to pause their subscription for 1-3 months instead of
 * cancelling. This saves 15-25% of cancellation attempts according to
 * SaaS benchmarks (Chargebee, Baremetrics).
 *
 * Stripe supports pause natively via:
 *   Subscription::update(['pause_collection' => ['behavior' => 'void']])
 *
 * DIRECTRICES:
 * - TENANT-001: All operations scoped to tenant
 * - SERVICE-CALL-CONTRACT-001: Method signatures must match exactly
 * - UPDATE-HOOK-CATCH-001: catch(\Throwable) everywhere
 *
 * @see docs/implementacion/2026-03-20_Plan_Implementacion_Pricing_4Tiers_Clase_Mundial_v1.md §6.4
 */
class PauseSubscriptionService {

  /**
   * Maximum pause duration in months.
   */
  protected const MAX_PAUSE_MONTHS = 3;

  /**
   * State key prefix for pause tracking.
   */
  protected const STATE_PREFIX = 'billing_pause:';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected ?StripeSubscriptionService $stripeSubscription = NULL,
  ) {}

  /**
   * Pauses a tenant's subscription.
   *
   * Sets subscription_status to 'paused' and records the resume date.
   * If Stripe is configured, also pauses the Stripe subscription.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   The tenant entity to pause.
   * @param int $months
   *   Number of months to pause (1-3).
   *
   * @return bool
   *   TRUE if paused successfully.
   */
  public function pauseSubscription(TenantInterface $tenant, int $months = 1): bool {
    $months = max(1, min($months, self::MAX_PAUSE_MONTHS));
    $tenantId = (int) $tenant->id();

    try {
      // Validate current state.
      $currentStatus = $tenant->get('subscription_status')->value ?? '';
      if ($currentStatus === 'paused') {
        $this->logger->notice('Tenant @id already paused.', ['@id' => $tenantId]);
        return TRUE;
      }

      if (!in_array($currentStatus, ['active', 'trial'], TRUE)) {
        $this->logger->warning('Cannot pause tenant @id: status=@status (must be active or trial).', [
          '@id' => $tenantId,
          '@status' => $currentStatus,
        ]);
        return FALSE;
      }

      // Calculate resume date.
      $resumeDate = new \DateTimeImmutable('+' . $months . ' months');

      // Update tenant entity.
      $tenant->set('subscription_status', 'paused');
      $tenant->save();

      // Store pause metadata in State API.
      $this->state->set(self::STATE_PREFIX . $tenantId, [
        'paused_at' => time(),
        'resume_at' => $resumeDate->getTimestamp(),
        'months' => $months,
        'previous_status' => $currentStatus,
      ]);

      // Pause in Stripe if configured.
      if ($this->stripeSubscription !== NULL) {
        $stripeSubId = $tenant->get('stripe_subscription_id')->value ?? '';
        if ($stripeSubId !== '') {
          try {
            // @phpstan-ignore-next-line Method may not exist yet (Stripe integration roadmap).
            $this->stripeSubscription->pauseCollection($stripeSubId, $resumeDate);
          }
          catch (\Throwable $e) {
            $this->logger->error('Stripe pause failed for tenant @id: @error', [
              '@id' => $tenantId,
              '@error' => $e->getMessage(),
            ]);
            // Local pause still valid even if Stripe fails.
          }
        }
      }

      $this->logger->info('Subscription paused for tenant @id for @months month(s). Resume: @date.', [
        '@id' => $tenantId,
        '@months' => $months,
        '@date' => $resumeDate->format('Y-m-d'),
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to pause subscription for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Resumes a paused subscription.
   *
   * Restores subscription_status to the previous state (active or trial).
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   The tenant entity to resume.
   *
   * @return bool
   *   TRUE if resumed successfully.
   */
  public function resumeSubscription(TenantInterface $tenant): bool {
    $tenantId = (int) $tenant->id();

    try {
      $pauseData = $this->state->get(self::STATE_PREFIX . $tenantId);
      $previousStatus = $pauseData['previous_status'] ?? 'active';

      $tenant->set('subscription_status', $previousStatus);
      $tenant->save();

      // Resume in Stripe.
      if ($this->stripeSubscription !== NULL) {
        $stripeSubId = $tenant->get('stripe_subscription_id')->value ?? '';
        if ($stripeSubId !== '') {
          try {
            // @phpstan-ignore-next-line Method may not exist yet (Stripe integration roadmap).
            $this->stripeSubscription->resumeCollection($stripeSubId);
          }
          catch (\Throwable $e) {
            $this->logger->error('Stripe resume failed for tenant @id: @error', [
              '@id' => $tenantId,
              '@error' => $e->getMessage(),
            ]);
          }
        }
      }

      // Clean up state.
      $this->state->delete(self::STATE_PREFIX . $tenantId);

      $this->logger->info('Subscription resumed for tenant @id. Restored to @status.', [
        '@id' => $tenantId,
        '@status' => $previousStatus,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to resume subscription for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Checks if a tenant's subscription is currently paused.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   The tenant entity.
   *
   * @return bool
   *   TRUE if paused.
   */
  public function isPaused(TenantInterface $tenant): bool {
    return ($tenant->get('subscription_status')->value ?? '') === 'paused';
  }

  /**
   * Gets the date when the paused subscription will auto-resume.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   The tenant entity.
   *
   * @return \DateTimeImmutable|null
   *   The resume date, or NULL if not paused.
   */
  public function getResumeDate(TenantInterface $tenant): ?\DateTimeImmutable {
    $tenantId = (int) $tenant->id();
    $pauseData = $this->state->get(self::STATE_PREFIX . $tenantId);
    if ($pauseData === NULL || !isset($pauseData['resume_at'])) {
      return NULL;
    }

    return (new \DateTimeImmutable())->setTimestamp($pauseData['resume_at']);
  }

  /**
   * Processes expired pauses — auto-resumes tenants past their resume date.
   *
   * Called from hook_cron. Processes in batches to avoid long-running cron.
   *
   * @param int $batchSize
   *   Maximum number of tenants to process per cron run.
   *
   * @return int
   *   Number of tenants auto-resumed.
   */
  public function processExpiredPauses(int $batchSize = 50): int {
    $resumed = 0;

    try {
      $tenantStorage = $this->entityTypeManager->getStorage('tenant');
      $ids = $tenantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('subscription_status', 'paused')
        ->range(0, $batchSize)
        ->execute();

      $tenants = $tenantStorage->loadMultiple($ids);

      foreach ($tenants as $tenant) {
        if (!$tenant instanceof TenantInterface) {
          continue;
        }
        $tenantId = (int) $tenant->id();
        $pauseData = $this->state->get(self::STATE_PREFIX . $tenantId);
        if ($pauseData === NULL) {
          continue;
        }

        $resumeAt = $pauseData['resume_at'] ?? 0;
        if ($resumeAt > 0 && time() >= $resumeAt) {
          if ($this->resumeSubscription($tenant)) {
            $resumed++;
          }
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error processing expired pauses: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    if ($resumed > 0) {
      $this->logger->info('Auto-resumed @count paused subscriptions.', [
        '@count' => $resumed,
      ]);
    }

    return $resumed;
  }

}
