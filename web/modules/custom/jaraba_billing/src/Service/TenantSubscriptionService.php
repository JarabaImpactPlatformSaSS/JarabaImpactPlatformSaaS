<?php

namespace Drupal\jaraba_billing\Service;

use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
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
    ) {
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
     * Cancels a tenant subscription.
     *
     * BIZ-02: Deferred cancellation stores the end-of-period date so access
     * continues until the billing period ends.
     *
     * BIZ-002 v6.3.0: Uses entity field instead of State API.
     */
    public function cancelSubscription(TenantInterface $tenant, bool $immediate = FALSE): TenantInterface
    {
        if ($immediate) {
            $tenant->setSubscriptionStatus(TenantInterface::STATUS_CANCELLED);
            $tenant->set('cancel_at', NULL);
        } else {
            // Deferred: schedule cancellation at end of current billing period.
            $tenant->set(
                'cancel_at',
                (new \DateTime('+30 days'))->format('Y-m-d\TH:i:s')
            );
        }

        $tenant->save();

        $this->logger->info('Suscripción cancelada para tenant @id (inmediata: @immediate)', [
            '@id' => $tenant->id(),
            '@immediate' => $immediate ? 'sí' : 'no',
        ]);

        return $tenant;
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

        $this->logger->info('Plan cambiado para tenant @id: @old -> @new', [
            '@id' => $tenant->id(),
            '@old' => $old_plan ? $old_plan->getName() : 'ninguno',
            '@new' => $new_plan->getName(),
        ]);

        return $tenant;
    }

}
