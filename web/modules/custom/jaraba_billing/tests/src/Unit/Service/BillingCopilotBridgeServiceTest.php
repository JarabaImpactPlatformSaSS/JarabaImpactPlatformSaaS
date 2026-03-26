<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_billing\Service\BillingCopilotBridgeService;
use Drupal\jaraba_billing\Service\RevenueMetricsService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_billing\Service\BillingCopilotBridgeService
 * @group jaraba_billing
 */
class BillingCopilotBridgeServiceTest extends UnitTestCase {

  /**
   * @covers ::getVerticalKey
   */
  public function testGetVerticalKeyReturnsGlobal(): void {
    $service = $this->createService();
    $this->assertSame('__global__', $service->getVerticalKey());
  }

  /**
   * @covers ::getRelevantContext
   */
  public function testGetRelevantContextReturnsDefaultOnError(): void {
    $revenueMetrics = $this->createMock(RevenueMetricsService::class);
    $revenueMetrics->method('getDashboardSnapshot')
      ->willThrowException(new \RuntimeException('DB unavailable'));

    $service = $this->createService($revenueMetrics);
    $context = $service->getRelevantContext(1);

    $this->assertSame('billing', $context['vertical']);
    $this->assertFalse($context['has_billing_data']);
    $this->assertSame([], $context['revenue_snapshot']);
    $this->assertSame([], $context['recent_invoices']);
    $this->assertSame(0.0, $context['churn_rate']);
  }

  /**
   * @covers ::getSoftSuggestion
   */
  public function testSoftSuggestionReturnsAlertOnHighChurn(): void {
    $revenueMetrics = $this->createMock(RevenueMetricsService::class);
    $revenueMetrics->method('getDashboardSnapshot')
      ->willReturn([
        'churn_rate' => 8.5,
        'mrr' => 5000,
        'arr' => 60000,
        'active_subscriptions' => 50,
      ]);

    $service = $this->createService($revenueMetrics);
    $suggestion = $service->getSoftSuggestion(1);

    $this->assertNotNull($suggestion);
    $this->assertSame('high_churn_rate', $suggestion['trigger']);
    $this->assertStringContainsString('8.5%', $suggestion['message']);
  }

  /**
   * @covers ::getSoftSuggestion
   */
  public function testSoftSuggestionReturnsNullOnLowChurn(): void {
    $revenueMetrics = $this->createMock(RevenueMetricsService::class);
    $revenueMetrics->method('getDashboardSnapshot')
      ->willReturn(['churn_rate' => 2.0]);

    $service = $this->createService($revenueMetrics);
    $this->assertNull($service->getSoftSuggestion(1));
  }

  /**
   * Creates service instance with optional mock overrides.
   */
  protected function createService(?RevenueMetricsService $revenueMetrics = NULL): BillingCopilotBridgeService {
    return new BillingCopilotBridgeService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
      $revenueMetrics ?? $this->createMock(RevenueMetricsService::class),
    );
  }

}
