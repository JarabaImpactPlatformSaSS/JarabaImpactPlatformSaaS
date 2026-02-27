<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_ai_agents\Service\AiRiskClassificationService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AiRiskClassificationService.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\AiRiskClassificationService
 * @group jaraba_ai_agents
 */
class AiRiskClassificationServiceTest extends TestCase {

  protected AiRiskClassificationService $service;

  protected function setUp(): void {
    parent::setUp();

    $logger = $this->createMock(LoggerChannelInterface::class);
    $this->service = new AiRiskClassificationService($logger);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyHighRiskRecruitment(): void {
    $result = $this->service->classify('legal_copilot', 'recruitment_assessment');

    $this->assertSame('high', $result['risk_level']);
    $this->assertTrue($result['documentation_required']);
    $this->assertTrue($result['human_oversight_required']);
    $this->assertTrue($result['transparency_required']);
    $this->assertContains('documentation', $result['requirements']);
    $this->assertContains('human_oversight', $result['requirements']);
    $this->assertStringContainsString('Employment', $result['eu_annex']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyHighRiskLegalAnalysis(): void {
    $result = $this->service->classify('legal_copilot', 'legal_analysis');

    $this->assertSame('high', $result['risk_level']);
    $this->assertStringContainsString('Justice', $result['eu_annex']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyHighRiskFinancialAdvice(): void {
    $result = $this->service->classify('sales_agent', 'financial_advice');

    $this->assertSame('high', $result['risk_level']);
    $this->assertStringContainsString('Credit', $result['eu_annex']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyLimitedRiskChatbot(): void {
    $result = $this->service->classify('support_agent', 'chatbot');

    $this->assertSame('limited', $result['risk_level']);
    $this->assertFalse($result['documentation_required']);
    $this->assertFalse($result['human_oversight_required']);
    $this->assertTrue($result['transparency_required']);
    $this->assertContains('transparency_label', $result['requirements']);
    $this->assertSame('Art. 50 (Transparency)', $result['eu_annex']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyLimitedRiskContentGeneration(): void {
    $result = $this->service->classify('marketing_agent', 'content_generation');

    $this->assertSame('limited', $result['risk_level']);
    $this->assertTrue($result['transparency_required']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyMinimalRiskDefaultAction(): void {
    $result = $this->service->classify('any_agent', 'analytics_report');

    $this->assertSame('minimal', $result['risk_level']);
    $this->assertFalse($result['documentation_required']);
    $this->assertFalse($result['human_oversight_required']);
    $this->assertFalse($result['transparency_required']);
    $this->assertEmpty($result['requirements']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyElevatedVerticalPushesToLimited(): void {
    $result = $this->service->classify('any_agent', 'unknown_action', [
      'vertical' => 'empleabilidad',
    ]);

    $this->assertSame('limited', $result['risk_level']);
    $this->assertTrue($result['transparency_required']);
  }

  /**
   * @covers ::classify
   */
  public function testClassifyJarabalexVerticalElevated(): void {
    $result = $this->service->classify('legal_copilot', 'unknown_action', [
      'vertical' => 'jarabalex',
    ]);

    $this->assertSame('limited', $result['risk_level']);
  }

  /**
   * @covers ::classifyAll
   */
  public function testClassifyAllMultipleActions(): void {
    $actions = ['chatbot', 'legal_analysis', 'analytics_report'];
    $results = $this->service->classifyAll('test_agent', $actions);

    $this->assertCount(3, $results);
    $this->assertSame('limited', $results['chatbot']['risk_level']);
    $this->assertSame('high', $results['legal_analysis']['risk_level']);
    $this->assertSame('minimal', $results['analytics_report']['risk_level']);
  }

  /**
   * @covers ::getHighestRisk
   */
  public function testGetHighestRiskReturnsHighest(): void {
    $classifications = [
      'chatbot' => ['risk_level' => 'limited'],
      'legal_analysis' => ['risk_level' => 'high'],
      'analytics' => ['risk_level' => 'minimal'],
    ];

    $this->assertSame('high', $this->service->getHighestRisk($classifications));
  }

  /**
   * @covers ::getHighestRisk
   */
  public function testGetHighestRiskEmptyReturnsMinimal(): void {
    $this->assertSame('minimal', $this->service->getHighestRisk([]));
  }

  /**
   * @covers ::getHighRiskActions
   */
  public function testGetHighRiskActionsNotEmpty(): void {
    $actions = $this->service->getHighRiskActions();

    $this->assertNotEmpty($actions);
    $this->assertContains('legal_analysis', $actions);
    $this->assertContains('recruitment_assessment', $actions);
    $this->assertContains('financial_advice', $actions);
  }

  /**
   * @covers ::getLimitedRiskActions
   */
  public function testGetLimitedRiskActionsNotEmpty(): void {
    $actions = $this->service->getLimitedRiskActions();

    $this->assertNotEmpty($actions);
    $this->assertContains('chatbot', $actions);
    $this->assertContains('content_generation', $actions);
  }

  /**
   * @covers ::classify
   */
  public function testAllHighRiskActionsClassifyCorrectly(): void {
    foreach ($this->service->getHighRiskActions() as $action) {
      $result = $this->service->classify('test', $action);
      $this->assertSame(
        'high',
        $result['risk_level'],
        "Action '{$action}' should be classified as high risk",
      );
    }
  }

  /**
   * @covers ::classify
   */
  public function testAllLimitedRiskActionsClassifyCorrectly(): void {
    foreach ($this->service->getLimitedRiskActions() as $action) {
      $result = $this->service->classify('test', $action);
      $this->assertSame(
        'limited',
        $result['risk_level'],
        "Action '{$action}' should be classified as limited risk",
      );
    }
  }

}
