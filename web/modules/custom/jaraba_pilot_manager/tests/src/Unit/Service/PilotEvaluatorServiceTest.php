<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pilot_manager\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_pilot_manager\Service\PilotEvaluatorService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PilotEvaluatorService.
 *
 * @group jaraba_pilot_manager
 * @coversDefaultClass \Drupal\jaraba_pilot_manager\Service\PilotEvaluatorService
 */
class PilotEvaluatorServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_pilot_manager\Service\PilotEvaluatorService
   */
  protected PilotEvaluatorService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->service = new PilotEvaluatorService(
      $this->entityTypeManager,
      NULL,
      NULL,
      NULL,
    );
  }

  /**
   * Tests evaluatePilot with no tenants.
   *
   * @covers ::evaluatePilot
   */
  public function testEvaluatePilotWithNoTenants(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $program = $this->createProgramMock(1);

    $result = $this->service->evaluatePilot($program);

    $this->assertSame(1, $result['program_id']);
    $this->assertSame(0, $result['total_enrolled']);
    $this->assertSame(0, $result['total_converted']);
    $this->assertSame(0.0, $result['conversion_rate']);
    $this->assertSame(0.0, $result['avg_nps']);
  }

  /**
   * Tests getConversionProbability returns value in 0-1 range.
   *
   * @covers ::getConversionProbability
   */
  public function testGetConversionProbabilityRange(): void {
    // Test with zero scores.
    $tenant = $this->createTenantMock(0, 0, 0, FALSE);
    $probability = $this->service->getConversionProbability($tenant);
    $this->assertGreaterThanOrEqual(0.0, $probability);
    $this->assertLessThanOrEqual(1.0, $probability);
    $this->assertSame(0.0, $probability);

    // Test with max scores.
    $tenant = $this->createTenantMock(100, 100, 100, TRUE);
    $probability = $this->service->getConversionProbability($tenant);
    $this->assertGreaterThanOrEqual(0.0, $probability);
    $this->assertLessThanOrEqual(1.0, $probability);
    $this->assertSame(1.0, $probability);
  }

  /**
   * Tests getConversionProbability with partial scores.
   *
   * @covers ::getConversionProbability
   */
  public function testGetConversionProbabilityPartial(): void {
    // 50 activation, 60 retention, 40 engagement, onboarded.
    // Score = (50*0.3) + (60*0.3) + (40*0.25) + 15 = 15+18+10+15 = 58.
    // Probability = 58/100 = 0.58.
    $tenant = $this->createTenantMock(50, 60, 40, TRUE);
    $probability = $this->service->getConversionProbability($tenant);
    $this->assertEqualsWithDelta(0.58, $probability, 0.001);
  }

  /**
   * Tests evaluateTenant extracts correct metrics.
   *
   * @covers ::evaluateTenant
   */
  public function testEvaluateTenant(): void {
    $tenant = $this->createTenantMock(75.5, 80.0, 60.0, TRUE);

    $result = $this->service->evaluateTenant($tenant);

    $this->assertSame(1, $result['tenant_id']);
    $this->assertSame(75.5, $result['activation_score']);
    $this->assertSame(80.0, $result['retention_d30']);
    $this->assertSame(60.0, $result['engagement_score']);
    $this->assertSame('low', $result['churn_risk']);
    $this->assertTrue($result['onboarding_completed']);
  }

  /**
   * Creates a mock pilot program entity.
   *
   * MOCK-DYNPROP-001: Uses anonymous class with typed properties.
   *
   * @param int $id
   *   The program ID.
   *
   * @return object
   *   Mock program object.
   */
  protected function createProgramMock(int $id): object {
    return new class($id) {

      public function __construct(protected int $programId) {}

      /**
       *
       */
      public function id(): int {
        return $this->programId;
      }

    };
  }

  /**
   * Creates a mock pilot tenant entity.
   *
   * MOCK-DYNPROP-001: Uses anonymous class with typed properties.
   *
   * @param float $activation
   *   Activation score.
   * @param float $retention
   *   Retention D30.
   * @param float $engagement
   *   Engagement score.
   * @param bool $onboarded
   *   Whether onboarding is completed.
   *
   * @return object
   *   Mock tenant object.
   */
  protected function createTenantMock(float $activation, float $retention, float $engagement, bool $onboarded): object {
    return new class($activation, $retention, $engagement, $onboarded) {
      protected array $fields;

      public function __construct(float $activation, float $retention, float $engagement, bool $onboarded) {
        $this->fields = [
          'activation_score' => (object) ['value' => $activation],
          'retention_d30' => (object) ['value' => $retention],
          'engagement_score' => (object) ['value' => $engagement],
          'onboarding_completed' => (object) ['value' => $onboarded],
          'churn_risk' => (object) ['value' => 'low'],
          'status' => (object) ['value' => 'active'],
        ];
      }

      /**
       *
       */
      public function id(): int {
        return 1;
      }

      /**
       *
       */
      public function get(string $field): object {
        return $this->fields[$field] ?? (object) ['value' => NULL];
      }

    };
  }

}
