<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_resources\Entity\DigitalKit;
use Drupal\jaraba_resources\Entity\MembershipPlan;
use Drupal\jaraba_resources\Entity\UserSubscription;
use Psr\Log\LoggerInterface;

/**
 * Service for managing user subscriptions.
 */
class SubscriptionService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a new SubscriptionService.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser,
        $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->logger = $loggerFactory->get('jaraba_resources');
    }

    /**
     * Gets the current user's active subscription.
     */
    public function getCurrentSubscription(?int $userId = NULL): ?UserSubscription
    {
        $userId = $userId ?? $this->currentUser->id();

        $storage = $this->entityTypeManager->getStorage('user_subscription');
        $subscriptions = $storage->loadByProperties([
            'user_id' => $userId,
            'subscription_status' => [
                UserSubscription::STATUS_ACTIVE,
                UserSubscription::STATUS_TRIAL,
            ],
        ]);

        return !empty($subscriptions) ? reset($subscriptions) : NULL;
    }

    /**
     * Gets the user's current plan type.
     */
    public function getUserPlanType(?int $userId = NULL): string
    {
        $subscription = $this->getCurrentSubscription($userId);

        if (!$subscription) {
            return MembershipPlan::TYPE_FREE;
        }

        $plan = $subscription->getPlan();
        return $plan ? $plan->getPlanType() : MembershipPlan::TYPE_FREE;
    }

    /**
     * Checks if user can access a digital kit.
     */
    public function canAccessKit(DigitalKit $kit, ?int $userId = NULL): bool
    {
        $requiredLevel = $kit->getAccessLevel();

        // Free kits are always accessible.
        if ($requiredLevel === DigitalKit::ACCESS_FREE) {
            return TRUE;
        }

        $userPlanType = $this->getUserPlanType($userId);

        $accessHierarchy = [
            DigitalKit::ACCESS_FREE => 0,
            DigitalKit::ACCESS_STARTER => 1,
            DigitalKit::ACCESS_PROFESSIONAL => 2,
            DigitalKit::ACCESS_ENTERPRISE => 3,
        ];

        $planHierarchy = [
            MembershipPlan::TYPE_FREE => 0,
            MembershipPlan::TYPE_STARTER => 1,
            MembershipPlan::TYPE_PROFESSIONAL => 2,
            MembershipPlan::TYPE_ENTERPRISE => 3,
        ];

        $required = $accessHierarchy[$requiredLevel] ?? 0;
        $userLevel = $planHierarchy[$userPlanType] ?? 0;

        return $userLevel >= $required;
    }

    /**
     * Gets all available plans.
     */
    public function getAvailablePlans(): array
    {
        $storage = $this->entityTypeManager->getStorage('membership_plan');

        return $storage->loadByProperties([
            'status' => 'active',
        ]);
    }

    /**
     * Creates a subscription for a user.
     */
    public function createSubscription(
        int $userId,
        int $planId,
        ?string $stripeSubscriptionId = NULL,
        ?string $stripeCustomerId = NULL
    ): UserSubscription {
        // Check if user already has active subscription.
        $existing = $this->getCurrentSubscription($userId);
        if ($existing) {
            throw new \RuntimeException('User already has an active subscription');
        }

        // Load plan to get billing interval.
        $plan = $this->entityTypeManager->getStorage('membership_plan')->load($planId);
        if (!$plan) {
            throw new \InvalidArgumentException('Plan not found');
        }

        // Calculate period end.
        $periodEnd = $this->calculatePeriodEnd($plan->getBillingInterval());

        $subscription = UserSubscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'subscription_status' => UserSubscription::STATUS_ACTIVE,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'stripe_customer_id' => $stripeCustomerId,
            'current_period_start' => date('Y-m-d\TH:i:s'),
            'current_period_end' => $periodEnd,
            'usage_reset_at' => date('Y-m-d\TH:i:s'),
        ]);
        $subscription->save();

        $this->logger->info('Created subscription @id for user @uid to plan @plan', [
            '@id' => $subscription->id(),
            '@uid' => $userId,
            '@plan' => $plan->getName(),
        ]);

        return $subscription;
    }

    /**
     * Creates a trial subscription.
     */
    public function createTrialSubscription(int $userId, int $planId, int $trialDays = 14): UserSubscription
    {
        $existing = $this->getCurrentSubscription($userId);
        if ($existing) {
            throw new \RuntimeException('User already has an active subscription');
        }

        $trialEnd = date('Y-m-d\TH:i:s', strtotime("+{$trialDays} days"));

        $subscription = UserSubscription::create([
            'user_id' => $userId,
            'plan_id' => $planId,
            'subscription_status' => UserSubscription::STATUS_TRIAL,
            'current_period_start' => date('Y-m-d\TH:i:s'),
            'current_period_end' => $trialEnd,
            'trial_end' => $trialEnd,
            'usage_reset_at' => date('Y-m-d\TH:i:s'),
        ]);
        $subscription->save();

        $this->logger->info('Created trial subscription for user @uid', ['@uid' => $userId]);

        return $subscription;
    }

    /**
     * Cancels a subscription.
     */
    public function cancelSubscription(UserSubscription $subscription, bool $immediately = FALSE): void
    {
        if ($immediately) {
            $subscription->set('subscription_status', UserSubscription::STATUS_CANCELLED);
        }
        // Otherwise, let it expire at end of period.

        $subscription->set('cancelled_at', date('Y-m-d\TH:i:s'));
        $subscription->save();

        $this->logger->info('Cancelled subscription @id', ['@id' => $subscription->id()]);
    }

    /**
     * Upgrades a subscription to a new plan.
     */
    public function upgradePlan(UserSubscription $subscription, int $newPlanId): void
    {
        $oldPlan = $subscription->getPlan();
        $newPlan = $this->entityTypeManager->getStorage('membership_plan')->load($newPlanId);

        if (!$newPlan) {
            throw new \InvalidArgumentException('Plan not found');
        }

        if (!$newPlan->isHigherThan($oldPlan)) {
            throw new \RuntimeException('Can only upgrade to a higher plan');
        }

        $subscription->set('plan_id', $newPlanId);
        $subscription->save();

        $this->logger->info('Upgraded subscription @id from @old to @new', [
            '@id' => $subscription->id(),
            '@old' => $oldPlan->getName(),
            '@new' => $newPlan->getName(),
        ]);
    }

    /**
     * Renews a subscription for a new period.
     */
    public function renewSubscription(UserSubscription $subscription): void
    {
        $plan = $subscription->getPlan();
        if (!$plan) {
            return;
        }

        $periodEnd = $this->calculatePeriodEnd($plan->getBillingInterval());

        $subscription->set('current_period_start', date('Y-m-d\TH:i:s'));
        $subscription->set('current_period_end', $periodEnd);
        $subscription->set('subscription_status', UserSubscription::STATUS_ACTIVE);
        $subscription->resetMonthlyUsage();
        $subscription->save();

        $this->logger->info('Renewed subscription @id', ['@id' => $subscription->id()]);
    }

    /**
     * Checks and expires past-due subscriptions.
     */
    public function processExpiredSubscriptions(): int
    {
        $storage = $this->entityTypeManager->getStorage('user_subscription');

        $query = $storage->getQuery()
            ->condition('subscription_status', [
                UserSubscription::STATUS_ACTIVE,
                UserSubscription::STATUS_TRIAL,
            ], 'IN')
            ->condition('current_period_end', date('Y-m-d\TH:i:s'), '<')
            ->accessCheck(FALSE);

        $ids = $query->execute();
        $count = 0;

        foreach ($storage->loadMultiple($ids) as $subscription) {
            $subscription->set('subscription_status', UserSubscription::STATUS_EXPIRED);
            $subscription->save();
            $count++;

            $this->logger->info('Expired subscription @id', ['@id' => $subscription->id()]);
        }

        return $count;
    }

    /**
     * Resets monthly usage for all subscriptions.
     */
    public function resetMonthlyUsage(): int
    {
        $storage = $this->entityTypeManager->getStorage('user_subscription');

        // Find subscriptions that haven't been reset this month.
        $firstOfMonth = date('Y-m-01\T00:00:00');

        $query = $storage->getQuery()
            ->condition('subscription_status', UserSubscription::STATUS_ACTIVE)
            ->condition('usage_reset_at', $firstOfMonth, '<')
            ->accessCheck(FALSE);

        $ids = $query->execute();
        $count = 0;

        foreach ($storage->loadMultiple($ids) as $subscription) {
            $subscription->resetMonthlyUsage();
            $subscription->save();
            $count++;
        }

        return $count;
    }

    /**
     * Calculates period end based on billing interval.
     */
    protected function calculatePeriodEnd(string $interval): string
    {
        $modifier = match ($interval) {
            MembershipPlan::INTERVAL_MONTHLY => '+1 month',
            MembershipPlan::INTERVAL_QUARTERLY => '+3 months',
            MembershipPlan::INTERVAL_YEARLY => '+1 year',
            MembershipPlan::INTERVAL_LIFETIME => '+100 years',
            default => '+1 month',
        };

        return date('Y-m-d\TH:i:s', strtotime($modifier));
    }

    /**
     * Gets user's remaining mentoring sessions.
     */
    public function getRemainingMentoringSessions(?int $userId = NULL): int
    {
        $subscription = $this->getCurrentSubscription($userId);

        if (!$subscription) {
            return 0;
        }

        return $subscription->getRemainingMentoringSessions();
    }

    /**
     * Uses one mentoring session from the user's quota.
     */
    public function useMentoringSession(?int $userId = NULL): bool
    {
        $subscription = $this->getCurrentSubscription($userId);

        if (!$subscription || $subscription->getRemainingMentoringSessions() <= 0) {
            return FALSE;
        }

        $subscription->useMentoringSession();
        $subscription->save();

        return TRUE;
    }

}
