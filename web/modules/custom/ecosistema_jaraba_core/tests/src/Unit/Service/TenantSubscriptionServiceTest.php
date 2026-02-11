<?php

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\PlanValidator;
use Drupal\ecosistema_jaraba_core\Service\TenantSubscriptionService;
use Psr\Log\LoggerInterface;

/**
 * Tests for the TenantSubscriptionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\TenantSubscriptionService
 */
class TenantSubscriptionServiceTest extends UnitTestCase
{

    protected TenantSubscriptionService $service;
    protected PlanValidator $planValidator;
    protected LoggerInterface $logger;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->planValidator = $this->createMock(PlanValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new TenantSubscriptionService(
            $this->planValidator,
            $this->logger,
        );
    }

    /**
     * Tests startTrial sets correct status and trial end date.
     *
     * @covers ::startTrial
     */
    public function testStartTrialSetsStatusAndDate(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        $tenant->expects($this->once())
            ->method('setSubscriptionStatus')
            ->with(TenantInterface::STATUS_TRIAL);

        $tenant->expects($this->once())
            ->method('set')
            ->with(
                'trial_ends',
                $this->callback(function ($value) {
                    $date = \DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
                    $now = new \DateTime();
                    $diff = $date->diff($now)->days;
                    // Should be approximately 14 days from now.
                    return $diff >= 13 && $diff <= 15;
                })
            );

        $tenant->expects($this->once())->method('save');
        $tenant->method('id')->willReturn('1');

        $result = $this->service->startTrial($tenant);
        $this->assertSame($tenant, $result);
    }

    /**
     * Tests startTrial with custom trial days.
     *
     * @covers ::startTrial
     */
    public function testStartTrialCustomDays(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        $tenant->expects($this->once())
            ->method('set')
            ->with(
                'trial_ends',
                $this->callback(function ($value) {
                    $date = \DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
                    $now = new \DateTime();
                    $diff = $date->diff($now)->days;
                    return $diff >= 29 && $diff <= 31;
                })
            );

        $tenant->method('setSubscriptionStatus')->with(TenantInterface::STATUS_TRIAL);
        $tenant->method('save');
        $tenant->method('id')->willReturn('1');

        $this->service->startTrial($tenant, 30);
    }

    /**
     * Tests activateSubscription sets correct status and clears trial.
     *
     * @covers ::activateSubscription
     */
    public function testActivateSubscriptionClearsTrialDate(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        $tenant->expects($this->once())
            ->method('setSubscriptionStatus')
            ->with(TenantInterface::STATUS_ACTIVE);

        $tenant->expects($this->once())
            ->method('set')
            ->with('trial_ends', NULL);

        $tenant->expects($this->once())->method('save');
        $tenant->method('id')->willReturn('1');

        $result = $this->service->activateSubscription($tenant);
        $this->assertSame($tenant, $result);
    }

    /**
     * Tests suspendTenant sets suspended status and logs reason.
     *
     * @covers ::suspendTenant
     */
    public function testSuspendTenantWithReason(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        $tenant->expects($this->once())
            ->method('setSubscriptionStatus')
            ->with(TenantInterface::STATUS_SUSPENDED);

        $tenant->expects($this->once())->method('save');
        $tenant->method('id')->willReturn('42');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('suspendido'),
                $this->callback(function ($context) {
                    return $context['@reason'] === 'payment_overdue';
                })
            );

        $this->service->suspendTenant($tenant, 'payment_overdue');
    }

    /**
     * Tests cancelSubscription with immediate=true sets cancelled status.
     *
     * @covers ::cancelSubscription
     */
    public function testImmediateCancellation(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        $tenant->expects($this->once())
            ->method('setSubscriptionStatus')
            ->with(TenantInterface::STATUS_CANCELLED);

        $tenant->expects($this->once())->method('save');
        $tenant->method('id')->willReturn('1');

        $this->service->cancelSubscription($tenant, TRUE);
    }

    /**
     * Tests cancelSubscription with immediate=false does NOT change status.
     *
     * @covers ::cancelSubscription
     */
    public function testDeferredCancellation(): void
    {
        $tenant = $this->createMock(TenantInterface::class);

        $tenant->expects($this->never())
            ->method('setSubscriptionStatus');

        $tenant->expects($this->once())->method('save');
        $tenant->method('id')->willReturn('1');

        $this->service->cancelSubscription($tenant, FALSE);
    }

    /**
     * Tests changePlan with valid validation.
     *
     * @covers ::changePlan
     */
    public function testChangePlanSuccess(): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $newPlan = $this->createMock(SaasPlanInterface::class);
        $oldPlan = $this->createMock(SaasPlanInterface::class);

        $this->planValidator->method('validatePlanChange')
            ->with($tenant, $newPlan)
            ->willReturn(['valid' => TRUE, 'errors' => []]);

        $tenant->method('getSubscriptionPlan')->willReturn($oldPlan);
        $tenant->expects($this->once())
            ->method('setSubscriptionPlan')
            ->with($newPlan);
        $tenant->expects($this->once())->method('save');
        $tenant->method('id')->willReturn('1');
        $oldPlan->method('getName')->willReturn('Basic');
        $newPlan->method('getName')->willReturn('Pro');

        $result = $this->service->changePlan($tenant, $newPlan);
        $this->assertSame($tenant, $result);
    }

    /**
     * Tests changePlan with failed validation throws exception.
     *
     * @covers ::changePlan
     */
    public function testChangePlanValidationFails(): void
    {
        $tenant = $this->createMock(TenantInterface::class);
        $newPlan = $this->createMock(SaasPlanInterface::class);

        $this->planValidator->method('validatePlanChange')
            ->with($tenant, $newPlan)
            ->willReturn([
                'valid' => FALSE,
                'errors' => ['Usage exceeds new plan limits'],
            ]);

        $tenant->method('id')->willReturn('5');
        $tenant->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Usage exceeds new plan limits');

        $this->service->changePlan($tenant, $newPlan);
    }

}
