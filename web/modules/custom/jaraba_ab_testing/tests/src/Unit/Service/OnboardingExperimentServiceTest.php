<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ab_testing\Service\OnboardingExperimentService;
use Drupal\jaraba_ab_testing\Service\VariantAssignmentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for OnboardingExperimentService.
 *
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\OnboardingExperimentService
 * @group jaraba_ab_testing
 */
class OnboardingExperimentServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_ab_testing\Service\OnboardingExperimentService
   */
  protected OnboardingExperimentService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock variant assignment service.
   *
   * @var \Drupal\jaraba_ab_testing\Service\VariantAssignmentService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected VariantAssignmentService|MockObject $variantAssignment;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock experiment storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $experimentStorage;

  /**
   * Mock variant storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $variantStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->variantAssignment = $this->createMock(VariantAssignmentService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->experimentStorage = $this->createMock(EntityStorageInterface::class);
    $this->variantStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['ab_experiment', $this->experimentStorage],
        ['ab_variant', $this->variantStorage],
      ]);

    $this->service = new OnboardingExperimentService(
      $this->entityTypeManager,
      $this->variantAssignment,
      $this->logger,
    );
  }

  /**
   * Helper to create a mock field item list with a single value.
   *
   * @param mixed $value
   *   The value to return from the field.
   *
   * @return object
   *   A mock object that acts as a field item list.
   */
  protected function createFieldValue(mixed $value): object {
    return (object) ['value' => $value];
  }

  /**
   * Helper to create a mock entity reference field.
   *
   * @param mixed $targetId
   *   The target_id to return.
   *
   * @return object
   *   A mock object that acts as an entity reference field.
   */
  protected function createEntityReferenceField(mixed $targetId): object {
    return (object) ['target_id' => $targetId];
  }

  /**
   * Creates a mock experiment entity.
   *
   * @param int $id
   *   The experiment ID.
   * @param string $machineName
   *   The machine name.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mock experiment.
   */
  protected function createMockExperiment(int $id, string $machineName): MockObject {
    $experiment = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'get'])
      ->getMock();

    $experiment->method('id')->willReturn($id);
    $experiment->method('get')->willReturnCallback(function (string $field) use ($machineName) {
      return match ($field) {
        'machine_name' => $this->createFieldValue($machineName),
        default => $this->createFieldValue(NULL),
      };
    });

    return $experiment;
  }

  /**
   * Creates a mock variant entity.
   *
   * @param int $id
   *   The variant ID.
   * @param string $label
   *   The variant label.
   * @param int $visitors
   *   Number of visitors.
   * @param int $conversions
   *   Number of conversions.
   * @param bool $isControl
   *   Whether this is the control variant.
   * @param string $configuration
   *   JSON configuration string.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   The mock variant.
   */
  protected function createMockVariant(
    int $id,
    string $label,
    int $visitors = 0,
    int $conversions = 0,
    bool $isControl = FALSE,
    string $configuration = '{}',
  ): MockObject {
    $variant = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'get', 'hasField'])
      ->getMock();

    $variant->method('id')->willReturn($id);
    $variant->method('hasField')->willReturnCallback(function (string $field) {
      return in_array($field, ['configuration', 'label', 'visitors', 'conversions', 'is_control'], TRUE);
    });
    $variant->method('get')->willReturnCallback(
      function (string $field) use ($label, $visitors, $conversions, $isControl, $configuration) {
        return match ($field) {
          'label' => $this->createFieldValue($label),
          'visitors' => $this->createFieldValue($visitors),
          'conversions' => $this->createFieldValue($conversions),
          'is_control' => $this->createFieldValue($isControl),
          'configuration' => $this->createFieldValue($configuration),
          default => $this->createFieldValue(NULL),
        };
      }
    );

    return $variant;
  }

  /**
   * Sets up the experiment query to return given IDs.
   *
   * @param array $ids
   *   The experiment IDs to return from the query.
   */
  protected function setupExperimentQuery(array $ids): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn($ids);

    $this->experimentStorage
      ->method('getQuery')
      ->willReturn($query);
  }

  /**
   * Tests that getActiveOnboardingExperiment() returns null when none active.
   *
   * @covers ::getActiveOnboardingExperiment
   */
  public function testGetActiveOnboardingExperimentReturnsNull(): void {
    $this->setupExperimentQuery([]);

    $result = $this->service->getActiveOnboardingExperiment();

    $this->assertNull($result);
  }

  /**
   * Tests that getActiveOnboardingExperiment() finds a matching experiment.
   *
   * @covers ::getActiveOnboardingExperiment
   */
  public function testGetActiveOnboardingExperimentFindsMatch(): void {
    $experimentId = 10;
    $this->setupExperimentQuery([$experimentId]);

    $mockExperiment = $this->createMockExperiment($experimentId, 'onboarding_flow_v2');

    $this->experimentStorage
      ->method('load')
      ->with($experimentId)
      ->willReturn($mockExperiment);

    $result = $this->service->getActiveOnboardingExperiment();

    $this->assertNotNull($result);
    $this->assertSame($experimentId, $result->id());
  }

  /**
   * Tests that assignVariant() returns null when no active experiment exists.
   *
   * @covers ::assignVariant
   */
  public function testAssignVariantReturnsNullNoExperiment(): void {
    $this->setupExperimentQuery([]);

    $this->logger
      ->expects($this->once())
      ->method('debug');

    $result = $this->service->assignVariant(42);

    $this->assertNull($result);
  }

  /**
   * Tests that trackConversion() silently returns when event type is irrelevant.
   *
   * Since the service does not explicitly validate event types, this test
   * verifies that trackConversion() returns silently when there is no active
   * experiment (the most common scenario for invalid/irrelevant events).
   *
   * @covers ::trackConversion
   */
  public function testTrackConversionValidatesEventType(): void {
    // No active experiment -- trackConversion should return silently.
    $this->setupExperimentQuery([]);

    // recordConversion on variantAssignment should never be called.
    $this->variantAssignment
      ->expects($this->never())
      ->method('recordConversion');

    $this->service->trackConversion(42, 'nonexistent_event_type', []);
  }

  /**
   * Tests that getOnboardingMetrics() returns data per variant.
   *
   * @covers ::getOnboardingMetrics
   */
  public function testGetOnboardingMetricsReturnsData(): void {
    $experimentId = 10;

    // Set up experiment query.
    $this->setupExperimentQuery([$experimentId]);

    $mockExperiment = $this->createMockExperiment($experimentId, 'onboarding_flow_v2');
    $this->experimentStorage
      ->method('load')
      ->with($experimentId)
      ->willReturn($mockExperiment);

    // Set up variant query.
    $variantQuery = $this->createMock(QueryInterface::class);
    $variantQuery->method('accessCheck')->willReturnSelf();
    $variantQuery->method('condition')->willReturnSelf();
    $variantQuery->method('execute')->willReturn([100, 101]);

    $this->variantStorage
      ->method('getQuery')
      ->willReturn($variantQuery);

    // Create mock variants with metrics.
    $controlVariant = $this->createMockVariant(
      id: 100,
      label: 'Control',
      visitors: 200,
      conversions: 50,
      isControl: TRUE,
    );
    $treatmentVariant = $this->createMockVariant(
      id: 101,
      label: 'Treatment A',
      visitors: 180,
      conversions: 72,
      isControl: FALSE,
    );

    $this->variantStorage
      ->method('loadMultiple')
      ->with([100, 101])
      ->willReturn([$controlVariant, $treatmentVariant]);

    $metrics = $this->service->getOnboardingMetrics();

    $this->assertCount(2, $metrics);

    // Check control variant metrics.
    $this->assertSame(100, $metrics[0]['variant_id']);
    $this->assertSame('Control', $metrics[0]['variant_name']);
    $this->assertSame(200, $metrics[0]['visitors']);
    $this->assertSame(50, $metrics[0]['conversions']);
    $this->assertSame(0.25, $metrics[0]['conversion_rate']);
    $this->assertTrue($metrics[0]['is_control']);

    // Check treatment variant metrics.
    $this->assertSame(101, $metrics[1]['variant_id']);
    $this->assertSame('Treatment A', $metrics[1]['variant_name']);
    $this->assertSame(180, $metrics[1]['visitors']);
    $this->assertSame(72, $metrics[1]['conversions']);
    $this->assertSame(0.4, $metrics[1]['conversion_rate']);
    $this->assertFalse($metrics[1]['is_control']);
  }

}
