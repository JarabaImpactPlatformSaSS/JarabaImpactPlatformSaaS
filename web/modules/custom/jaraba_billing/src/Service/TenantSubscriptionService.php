<?php

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Psr\Log\LoggerInterface;

/**
 * BE-03 + BIZ-02: Service for tenant subscription lifecycle management.
 *
 * Extracted from TenantManager to follow Single Responsibility Principle.
 * Handles: trial start, activation, suspension, cancellation, plan changes,
 * grace periods, trial expiration, and deferred cancellation scheduling.
 *
 * BIZ-002 v6.3.0: Migrated grace_period_ends and cancel_at from Drupal\State
 * to entity fields for auditable, rebuild-tolerant persistence.
 */
class TenantSubscriptionService
{

    /**
     * Default grace period in days for past_due before suspension.
     */
    protected const GRACE_PERIOD_DAYS = 7;

    public function __construct(
        protected PlanValidator $planValidator,
        protected LoggerInterface $logger,
        protected ?PlanResolverService $planResolver = NULL,
    ) {
    }

    /**
     * Lazy-loads StripeSubscriptionService to break circular dependency.
     */
    protected function getStripeSubscription(): ?StripeSubscriptionService {
      if (\Drupal::hasService('jaraba_billing.stripe_subscription')) {
        return \Drupal::service('jaraba_billing.stripe_subscription');
      }
      return NULL;
    }

    /**
     * Starts trial period for a tenant.
     */
    public function startTrial(TenantInterface $tenant, int $days = 14): TenantInterface
    {
        $trial_ends = new \DateTime("+{$days} days");

        $tenant->setSubscriptionStatus(TenantInterface::STATUS_TRIAL);
        $tenant->set('trial_ends', $trial_ends->format('Y-m-d\TH:i:s'));
        $tenant->save();

        $this->logger->info('Trial iniciado para tenant @id: @days días', [
            '@id' => $tenant->id(),
            '@days' => $days,
        ]);

        return $tenant;
    }

    /**
     * Activates a tenant subscription.
     */
    public function activateSubscription(TenantInterface $tenant): TenantInterface
    {
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_ACTIVE);
        $tenant->set('trial_ends', NULL);
        $tenant->save();

        $this->logger->info('Suscripción activada para tenant @id', [
            '@id' => $tenant->id(),
        ]);

        return $tenant;
    }

    /**
     * Suspends a tenant.
     */
    public function suspendTenant(TenantInterface $tenant, string $reason = ''): TenantInterface
    {
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_SUSPENDED);
        $tenant->save();

        $this->logger->warning('Tenant @id suspendido: @reason', [
            '@id' => $tenant->id(),
            '@reason' => $reason ?: 'Sin especificar',
        ]);

        return $tenant;
    }

    /**
     * BIZ-02: Marks a tenant as past_due and starts grace period.
     *
     * Called when a Stripe payment fails. The tenant retains access for
     * GRACE_PERIOD_DAYS before automatic suspension via cron.
     *
     * BIZ-002 v6.3.0: Uses entity field instead of State API.
     */
    public function markPastDue(TenantInterface $tenant): TenantInterface
    {
        $tenant->setSubscriptionStatus(TenantInterface::STATUS_PAST_DUE);

        $graceEnds = new \DateTime('+' . self::GRACE_PERIOD_DAYS . ' days');
        $tenant->set('grace_period_ends', $graceEnds->format('Y-m-d\TH:i:s'));
        $tenant->save();

        $this->logger->warning('Tenant @id marcado como past_due. Gracia hasta @date.', [
            '@id' => $tenant->id(),
            '@date' => $graceEnds->format('Y-m-d'),
        ]);

        return $tenant;
    }

    /**
     * BIZ-02: Checks if a tenant's grace period has expired.
     *
     * BIZ-002 v6.3.0: Reads from entity field instead of State API.
     */
    public function isGracePeriodExpired(TenantInterface $tenant): bool
    {
        $graceEnds = $tenant->get('grace_period_ends')->value;
        if (!$graceEnds) {
            return TRUE;
        }

        return new \DateTime() > new \DateTime($graceEnds);
    }

    /**
     * BIZ-02: Checks if a tenant's trial has expired.
     */
    public function isTrialExpired(TenantInterface $tenant): bool
    {
        if ($tenant->getSubscriptionStatus() !== TenantInterface::STATUS_TRIAL) {
            return FALSE;
        }

        $trialEnds = $tenant->getTrialEndsAt();
        if (!$trialEnds) {
            return TRUE;
        }

        return new \DateTime() > new \DateTime($trialEnds->format('Y-m-d\TH:i:s'));
    }

    /**
     * BIZ-02: Checks if a deferred cancellation date has arrived.
     *
     * BIZ-002 v6.3.0: Reads from entity field instead of State API.
     */
    public function isDeferredCancellationDue(TenantInterface $tenant): bool
    {
        $cancelAt = $tenant->get('cancel_at')->value;
        if (!$cancelAt) {
            return FALSE;
        }

        return new \DateTime() >= new \DateTime($cancelAt);
    }

    /**
     * BIZ-02: Processes expired trials and grace periods (called from cron).
     *
     * Returns the count of tenants processed for monitoring.
     *
     * BIZ-002 v6.3.0: Clears entity fields instead of State keys.
     */
    public function processExpiredSubscriptions(array $tenants): int
    {
        $processed = 0;

        foreach ($tenants as $tenant) {
            $status = $tenant->getSubscriptionStatus();

            // Expire trials.
            if ($status === TenantInterface::STATUS_TRIAL && $this->isTrialExpired($tenant)) {
                $this->suspendTenant($tenant, 'Trial expirado');
                $processed++;
                continue;
            }

            // Suspend past_due tenants after grace period.
            if ($status === TenantInterface::STATUS_PAST_DUE && $this->isGracePeriodExpired($tenant)) {
                $this->suspendTenant($tenant, 'Periodo de gracia expirado');
                $tenant->set('grace_period_ends', NULL);
                $tenant->save();
                $processed++;
                continue;
            }

            // Execute deferred cancellations.
            if ($status === TenantInterface::STATUS_ACTIVE && $this->isDeferredCancellationDue($tenant)) {
                $tenant->setSubscriptionStatus(TenantInterface::STATUS_CANCELLED);
                $tenant->set('cancel_at', NULL);
                $tenant->save();
                $this->logger->info('Cancelación diferida ejecutada para tenant @id.', [
                    '@id' => $tenant->id(),
                ]);
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Changes the subscription plan for a tenant.
     *
     * GAP-C03: Synchronizes plan change with Stripe. If Stripe update
     * fails, the local change is reverted to maintain consistency.
     *
     * @throws \InvalidArgumentException
     *   If plan change validation fails.
     */
    public function changePlan(TenantInterface $tenant, SaasPlanInterface $new_plan): TenantInterface
    {
        $validation = $this->planValidator->validatePlanChange($tenant, $new_plan);

        if (!$validation['valid']) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Plan change validation failed for tenant %s: %s',
                    $tenant->id(),
                    implode(', ', $validation['errors'])
                )
            );
        }

        $old_plan = $tenant->getSubscriptionPlan();
        $tenant->setSubscriptionPlan($new_plan);
        $tenant->save();

        // GAP-C03: Sync with Stripe — revert local if Stripe fails.
        $stripeSubId = $tenant->hasField('stripe_subscription_id')
            ? $tenant->get('stripe_subscription_id')->value
            : NULL;

        $stripeSvc = $stripeSubId ? $this->getStripeSubscription() : NULL;
        if ($stripeSvc) {
            $newPriceId = $this->resolveStripePriceId($new_plan, $tenant);
            if ($newPriceId) {
                try {
                    $stripeSvc->updateSubscription($stripeSubId, $newPriceId);
                    $this->logger->info('Stripe subscription @sub updated to price @price for tenant @id.', [
                        '@sub' => $stripeSubId,
                        '@price' => $newPriceId,
                        '@id' => $tenant->id(),
                    ]);
                }
                catch (\Throwable $e) {
                    // Revert local change.
                    if ($old_plan) {
                        $tenant->setSubscriptionPlan($old_plan);
                    }
                    $tenant->save();
                    $this->logger->error('Stripe plan change failed for tenant @id, reverted local: @msg', [
                        '@id' => $tenant->id(),
                        '@msg' => $e->getMessage(),
                    ]);
                    throw new \RuntimeException(
                        'Stripe plan update failed: ' . $e->getMessage(),
                        (int) $e->getCode(),
                        $e
                    );
                }
            }
        }

        $this->logger->info('Plan cambiado para tenant @id: @old -> @new', [
            '@id' => $tenant->id(),
            '@old' => $old_plan ? $old_plan->getName() : 'ninguno',
            '@new' => $new_plan->getName(),
        ]);

        return $tenant;
    }

    /**
     * Cancels a tenant subscription with Stripe synchronization.
     *
     * GAP-C03: Cancels the Stripe subscription at period end before
     * marking the local subscription as cancelled.
     */
    public function cancelSubscription(TenantInterface $tenant, bool $immediate = FALSE): TenantInterface
    {
        // GAP-C03: Cancel in Stripe first.
        $stripeSubId = $tenant->hasField('stripe_subscription_id')
            ? $tenant->get('stripe_subscription_id')->value
            : NULL;

        $stripeSvc = $stripeSubId ? $this->getStripeSubscription() : NULL;
        if ($stripeSvc) {
            try {
                $stripeSvc->cancelSubscription($stripeSubId, $immediate);
                $this->logger->info('Stripe subscription @sub cancelled for tenant @id (immediate: @imm).', [
                    '@sub' => $stripeSubId,
                    '@id' => $tenant->id(),
                    '@imm' => $immediate ? 'yes' : 'no',
                ]);
            }
            catch (\Throwable $e) {
                $this->logger->error('Stripe cancellation failed for tenant @id: @msg', [
                    '@id' => $tenant->id(),
                    '@msg' => $e->getMessage(),
                ]);
                // Continue with local cancellation — Stripe can be reconciled manually.
            }
        }

        // Local cancellation (existing logic).
        if ($immediate) {
            $tenant->setSubscriptionStatus(TenantInterface::STATUS_CANCELLED);
            $tenant->set('cancel_at', NULL);
        } else {
            $tenant->set(
                'cancel_at',
                (new \DateTime('+30 days'))->format('Y-m-d\TH:i:s')
            );
        }

        $tenant->save();

        $this->logger->info('Suscripcion cancelada para tenant @id (inmediata: @immediate)', [
            '@id' => $tenant->id(),
            '@immediate' => $immediate ? 'si' : 'no',
        ]);

        return $tenant;
    }

    /**
     * Resolves the Stripe Price ID for a plan + vertical combination.
     *
     * Uses PlanResolverService to find the Stripe Price ID from
     * SaasPlanTier ConfigEntities.
     */
    protected function resolveStripePriceId(SaasPlanInterface $plan, TenantInterface $tenant): ?string {
        if (!$this->planResolver) {
            return NULL;
        }

        try {
            $vertical = $tenant->hasField('vertical')
                ? ($tenant->get('vertical')->entity?->get('machine_name') ?? 'demo')
                : 'demo';
            $tier = $plan->get('tier')->value ?? $plan->id();
            $tierConfig = $this->planResolver->getTierConfig($vertical, $tier);

            return $tierConfig?->get('stripe_price_monthly') ?? NULL;
        }
        catch (\Throwable $e) {
            $this->logger->warning('Could not resolve Stripe Price ID for plan @plan: @msg', [
                '@plan' => $plan->id(),
                '@msg' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

}
