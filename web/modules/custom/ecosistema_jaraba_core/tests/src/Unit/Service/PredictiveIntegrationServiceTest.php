<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\PredictiveIntegrationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\PredictiveIntegrationService
 * @group ecosistema_jaraba_core
 */
class PredictiveIntegrationServiceTest extends UnitTestCase {

  /**
   * @covers ::getLeadEnrichment
   */
  public function testGetLeadEnrichmentReturnsEmptyWhenNoScorer(): void {
    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->getLeadEnrichment(1);

    $this->assertSame(0, $result['score']);
    $this->assertSame('cold', $result['qualification']);
    $this->assertFalse($result['is_hot']);
    $this->assertFalse($result['is_sales_ready']);
    $this->assertSame('low', $result['recommended_priority']);
    $this->assertSame('', $result['calculated_at']);
    $this->assertSame([], $result['breakdown']);
  }

  /**
   * @covers ::getChurnRisk
   */
  public function testGetChurnRiskReturnsEmptyWhenNoPredictor(): void {
    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->getChurnRisk(1);

    $this->assertSame(0, $result['risk_score']);
    $this->assertSame('low', $result['risk_level']);
    $this->assertFalse($result['is_high_risk']);
    $this->assertFalse($result['is_critical']);
    $this->assertFalse($result['needs_retention']);
    $this->assertSame([], $result['contributing_factors']);
    $this->assertSame([], $result['recommended_actions']);
  }

  /**
   * @covers ::getRevenueForecast
   */
  public function testGetRevenueForecastReturnsEmptyWhenNoEngine(): void {
    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->getRevenueForecast();

    $this->assertSame(0.0, $result['predicted_value']);
    $this->assertSame('', $result['forecast_date']);
    $this->assertSame(0.0, $result['confidence_low']);
    $this->assertSame(0.0, $result['confidence_high']);
    $this->assertSame('', $result['model_version']);
  }

  /**
   * @covers ::detectAnomalies
   */
  public function testDetectAnomaliesReturnsEmptyWhenNoDetector(): void {
    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
    );

    $result = $service->detectAnomalies('mrr');

    $this->assertSame([], $result['anomalies']);
    $this->assertSame(0, $result['data_points_analyzed']);
  }

  /**
   * @covers ::triggerRetention
   */
  public function testTriggerRetentionLogsWhenNoWorkflow(): void {
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Retention workflow not available'),
        $this->anything(),
      );

    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $logger,
    );

    $service->triggerRetention(1, 80, 'high');
  }

  /**
   * @covers ::getTopLeads
   */
  public function testGetTopLeadsReturnsEmptyWhenNoScorer(): void {
    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
    );

    $this->assertSame([], $service->getTopLeads());
  }

  /**
   * @covers ::getHighRiskTenants
   */
  public function testGetHighRiskTenantsReturnsEmptyWhenNoPredictor(): void {
    $service = new PredictiveIntegrationService(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(LoggerInterface::class),
    );

    $this->assertSame([], $service->getHighRiskTenants());
  }

}
