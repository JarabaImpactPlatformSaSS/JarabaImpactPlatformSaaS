<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\JarabaLexCrossVerticalBridgeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for JarabaLexCrossVerticalBridgeService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\JarabaLexCrossVerticalBridgeService
 * @group ecosistema_jaraba_core
 */
class JarabaLexCrossVerticalBridgeServiceTest extends UnitTestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexCrossVerticalBridgeService
   */
  protected JarabaLexCrossVerticalBridgeService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new JarabaLexCrossVerticalBridgeService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests that BRIDGES constant defines exactly 4 bridges.
   *
   * @covers ::__construct
   */
  public function testBridgesCountIsFour(): void {
    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    $this->assertCount(4, $bridges);
  }

  /**
   * Tests that all 4 bridge IDs are present.
   *
   * @covers ::__construct
   */
  public function testAllBridgeIdsExist(): void {
    $expectedBridgeIds = [
      'emprendimiento_legal',
      'empleabilidad_legal',
      'fiscal_compliance',
      'formacion_continua',
    ];

    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    foreach ($expectedBridgeIds as $bridgeId) {
      $this->assertArrayHasKey($bridgeId, $bridges, "Bridge '$bridgeId' must exist.");
    }
  }

  /**
   * Tests each bridge has required fields.
   *
   * @covers ::__construct
   */
  public function testBridgesHaveRequiredFields(): void {
    $requiredFields = [
      'id',
      'vertical',
      'icon_category',
      'icon_name',
      'color',
      'message',
      'cta_label',
      'cta_url',
      'condition',
      'priority',
    ];

    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    foreach ($bridges as $bridgeId => $bridge) {
      foreach ($requiredFields as $field) {
        $this->assertArrayHasKey(
          $field,
          $bridge,
          "Bridge '$bridgeId' must have field '$field'.",
        );
      }
    }
  }

  /**
   * Tests bridge target verticals are correct.
   *
   * @covers ::__construct
   */
  public function testBridgeTargetVerticals(): void {
    $expectedVerticals = [
      'emprendimiento_legal' => 'emprendimiento',
      'empleabilidad_legal' => 'empleabilidad',
      'fiscal_compliance' => 'fiscal',
      'formacion_continua' => 'formacion',
    ];

    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    foreach ($expectedVerticals as $bridgeId => $expectedVertical) {
      $this->assertSame(
        $expectedVertical,
        $bridges[$bridgeId]['vertical'],
        "Bridge '$bridgeId' must target vertical '$expectedVertical'.",
      );
    }
  }

  /**
   * Tests that bridge priorities are positive integers.
   *
   * @covers ::__construct
   */
  public function testBridgePrioritiesArePositive(): void {
    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    foreach ($bridges as $bridgeId => $bridge) {
      $this->assertIsInt($bridge['priority'], "Bridge '$bridgeId' priority must be int.");
      $this->assertGreaterThan(0, $bridge['priority'], "Bridge '$bridgeId' priority must be positive.");
    }
  }

  /**
   * Tests that evaluateBridges returns maximum 2 bridges.
   *
   * We verify this by examining the service code which slices to 2.
   *
   * @covers ::evaluateBridges
   */
  public function testEvaluateBridgesReturnsMaxTwoBridges(): void {
    // Without a Drupal container, getDismissedBridges and evaluateCondition
    // will fail. The conditions check against Drupal services/entityManager.
    // We verify via reflection that array_slice($bridges, 0, 2) is used,
    // and also test with a partial mock.
    $service = $this->getMockBuilder(JarabaLexCrossVerticalBridgeService::class)
      ->setConstructorArgs([$this->entityTypeManager, $this->logger])
      ->onlyMethods([])
      ->getMock();

    // Use reflection to invoke evaluateBridges with controlled conditions.
    // Override getDismissedBridges and evaluateCondition via subclass.
    $testService = new class($this->entityTypeManager, $this->logger) extends JarabaLexCrossVerticalBridgeService {

      protected function getDismissedBridges(int $userId): array {
        return [];
      }

      protected function evaluateCondition(int $userId, string $condition): bool {
        // All conditions match.
        return TRUE;
      }

    };

    $result = $testService->evaluateBridges(1);

    $this->assertIsArray($result);
    $this->assertLessThanOrEqual(2, count($result), 'evaluateBridges must return max 2 bridges.');
    $this->assertCount(2, $result);
  }

  /**
   * Tests that evaluateBridges sorts by priority (lowest first).
   *
   * @covers ::evaluateBridges
   */
  public function testEvaluateBridgesSortsByPriority(): void {
    $testService = new class($this->entityTypeManager, $this->logger) extends JarabaLexCrossVerticalBridgeService {

      protected function getDismissedBridges(int $userId): array {
        return [];
      }

      protected function evaluateCondition(int $userId, string $condition): bool {
        return TRUE;
      }

    };

    $result = $testService->evaluateBridges(1);

    // The 2 returned should be the ones with lowest priority values.
    // emprendimiento_legal=10, fiscal_compliance=15 are lowest.
    $this->assertSame('emprendimiento_legal', $result[0]['id']);
    $this->assertSame('fiscal_compliance', $result[1]['id']);
  }

  /**
   * Tests that dismissed bridges are excluded from results.
   *
   * @covers ::evaluateBridges
   */
  public function testEvaluateBridgesExcludesDismissed(): void {
    $testService = new class($this->entityTypeManager, $this->logger) extends JarabaLexCrossVerticalBridgeService {

      protected function getDismissedBridges(int $userId): array {
        // User dismissed emprendimiento_legal and fiscal_compliance.
        return ['emprendimiento_legal', 'fiscal_compliance'];
      }

      protected function evaluateCondition(int $userId, string $condition): bool {
        return TRUE;
      }

    };

    $result = $testService->evaluateBridges(1);

    $returnedIds = array_column($result, 'id');
    $this->assertNotContains('emprendimiento_legal', $returnedIds);
    $this->assertNotContains('fiscal_compliance', $returnedIds);
  }

  /**
   * Tests that evaluateBridges returns empty when no conditions match.
   *
   * @covers ::evaluateBridges
   */
  public function testEvaluateBridgesReturnsEmptyWhenNoConditionsMatch(): void {
    $testService = new class($this->entityTypeManager, $this->logger) extends JarabaLexCrossVerticalBridgeService {

      protected function getDismissedBridges(int $userId): array {
        return [];
      }

      protected function evaluateCondition(int $userId, string $condition): bool {
        return FALSE;
      }

    };

    $result = $testService->evaluateBridges(1);
    $this->assertEmpty($result);
  }

  /**
   * Tests presentBridge returns bridge data for valid ID.
   *
   * @covers ::presentBridge
   */
  public function testPresentBridgeReturnsDataForValidId(): void {
    $result = $this->service->presentBridge(1, 'fiscal_compliance');

    $this->assertNotEmpty($result);
    $this->assertSame('fiscal_compliance', $result['id']);
    $this->assertSame('fiscal', $result['vertical']);
    $this->assertArrayHasKey('message', $result);
    $this->assertArrayHasKey('cta_label', $result);
  }

  /**
   * Tests presentBridge returns empty array for invalid ID.
   *
   * @covers ::presentBridge
   */
  public function testPresentBridgeReturnsEmptyForInvalidId(): void {
    $result = $this->service->presentBridge(1, 'nonexistent_bridge');
    $this->assertEmpty($result);
  }

  /**
   * Tests trackBridgeResponse logs the response.
   *
   * @covers ::trackBridgeResponse
   */
  public function testTrackBridgeResponseLogsResponse(): void {
    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('JarabaLex bridge'),
        $this->callback(function (array $context) {
          return $context['@bridge'] === 'fiscal_compliance'
            && $context['@user'] === 1
            && $context['@response'] === 'accepted';
        }),
      );

    $this->service->trackBridgeResponse(1, 'fiscal_compliance', 'accepted');
  }

  /**
   * Tests bridge condition strings match expected values.
   *
   * @covers ::__construct
   */
  public function testBridgeConditionStrings(): void {
    $expectedConditions = [
      'emprendimiento_legal' => 'legal_plus_entrepreneur_interest',
      'empleabilidad_legal' => 'active_job_seeker',
      'fiscal_compliance' => 'fiscal_search_activity',
      'formacion_continua' => 'high_search_activity',
    ];

    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    foreach ($expectedConditions as $bridgeId => $expectedCondition) {
      $this->assertSame(
        $expectedCondition,
        $bridges[$bridgeId]['condition'],
        "Bridge '$bridgeId' must have condition '$expectedCondition'.",
      );
    }
  }

  /**
   * Tests bridge CTA URLs are non-empty strings starting with '/'.
   *
   * @covers ::__construct
   */
  public function testBridgeCtaUrlsAreValid(): void {
    $reflection = new \ReflectionClass(JarabaLexCrossVerticalBridgeService::class);
    $bridges = $reflection->getConstant('BRIDGES');

    foreach ($bridges as $bridgeId => $bridge) {
      $this->assertNotEmpty($bridge['cta_url'], "Bridge '$bridgeId' cta_url must not be empty.");
      $this->assertStringStartsWith('/', $bridge['cta_url'], "Bridge '$bridgeId' cta_url must start with '/'.");
    }
  }

}
