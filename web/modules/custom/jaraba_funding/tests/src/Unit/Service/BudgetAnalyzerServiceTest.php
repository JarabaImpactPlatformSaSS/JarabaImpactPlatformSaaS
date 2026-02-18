<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_funding\Service\BudgetAnalyzerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for BudgetAnalyzerService.
 *
 * @coversDefaultClass \Drupal\jaraba_funding\Service\BudgetAnalyzerService
 * @group jaraba_funding
 */
class BudgetAnalyzerServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_funding\Service\BudgetAnalyzerService
   */
  protected BudgetAnalyzerService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock tenant context.
   *
   * @var object|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

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
    $this->tenantContext = new \stdClass();
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new BudgetAnalyzerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Creates a mock entity with field values.
   *
   * @param array $fields
   *   Associative array of field_name => value or field_name => object.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   A mock entity.
   */
  protected function createMockEntity(array $fields): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();

    $entity->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        if (isset($fields[$field_name]) && is_object($fields[$field_name])) {
          return $fields[$field_name];
        }
        $value = $fields[$field_name] ?? NULL;
        return (object) ['value' => $value];
      });

    return $entity;
  }

  /**
   * @covers ::calculateBudget
   */
  public function testCalculateBudgetReturnsBreakdown(): void {
    $application = $this->createMockEntity([
      'amount_requested' => 100000,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with(1)
      ->willReturn($application);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_application')
      ->willReturn($storage);

    $result = $this->service->calculateBudget(1);

    $this->assertTrue($result['success']);
    $this->assertArrayHasKey('breakdown', $result);
    $this->assertArrayHasKey('total', $result);
    $this->assertEquals(100000, $result['total']);

    // Verify the percentage-based breakdown.
    $breakdown = $result['breakdown'];
    $this->assertEquals(40000.00, $breakdown['personal']);
    $this->assertEquals(20000.00, $breakdown['equipamiento']);
    $this->assertEquals(15000.00, $breakdown['servicios_externos']);
    $this->assertEquals(5000.00, $breakdown['viajes']);
    $this->assertEquals(10000.00, $breakdown['materiales']);
    $this->assertEquals(10000.00, $breakdown['costes_indirectos']);

    // Verify breakdown sums to total.
    $breakdown_total = array_sum($breakdown);
    $this->assertEquals(100000.00, $breakdown_total);
  }

  /**
   * @covers ::calculateBudget
   */
  public function testCalculateBudgetReturnsErrorWhenApplicationNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_application')
      ->willReturn($storage);

    $result = $this->service->calculateBudget(999);

    $this->assertFalse($result['success']);
    $this->assertArrayHasKey('error', $result);
    $this->assertEquals('Solicitud no encontrada.', $result['error']);
  }

  /**
   * @covers ::calculateBudget
   */
  public function testCalculateBudgetHandlesException(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Storage unavailable'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error al calcular presupuesto'),
        $this->anything(),
      );

    $result = $this->service->calculateBudget(1);

    $this->assertFalse($result['success']);
    $this->assertEquals('Error interno al calcular presupuesto.', $result['error']);
  }

  /**
   * @covers ::calculateBudget
   */
  public function testCalculateBudgetWithZeroAmount(): void {
    $application = $this->createMockEntity([
      'amount_requested' => 0,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with(2)
      ->willReturn($application);

    $this->entityTypeManager->method('getStorage')
      ->with('funding_application')
      ->willReturn($storage);

    $result = $this->service->calculateBudget(2);

    $this->assertTrue($result['success']);
    $this->assertEquals(0, $result['total']);
    foreach ($result['breakdown'] as $category => $amount) {
      $this->assertEquals(0.00, $amount, "Category '$category' should be 0 for zero total.");
    }
  }

  /**
   * @covers ::validateEligibility
   */
  public function testValidateEligibilityReturnsEligibleWhenValid(): void {
    $application = $this->createMockEntity([
      'amount_requested' => 50000,
      'opportunity_id' => (object) ['target_id' => 10],
    ]);

    $deadline = (new \DateTime('+30 days'))->format('Y-m-d');
    $opportunity = $this->createMockEntity([
      'max_amount' => 100000,
      'deadline' => $deadline,
      'status' => 'open',
    ]);

    $appStorage = $this->createMock(EntityStorageInterface::class);
    $appStorage->method('load')
      ->with(1)
      ->willReturn($application);

    $oppStorage = $this->createMock(EntityStorageInterface::class);
    $oppStorage->method('load')
      ->with(10)
      ->willReturn($opportunity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entity_type) use ($appStorage, $oppStorage) {
        return match ($entity_type) {
          'funding_application' => $appStorage,
          'funding_opportunity' => $oppStorage,
        };
      });

    $result = $this->service->validateEligibility(1);

    $this->assertTrue($result['eligible']);
    $this->assertEmpty($result['issues']);
  }

  /**
   * @covers ::validateEligibility
   */
  public function testValidateEligibilityDetectsExceededAmount(): void {
    $application = $this->createMockEntity([
      'amount_requested' => 200000,
      'opportunity_id' => (object) ['target_id' => 10],
    ]);

    $deadline = (new \DateTime('+30 days'))->format('Y-m-d');
    $opportunity = $this->createMockEntity([
      'max_amount' => 100000,
      'deadline' => $deadline,
      'status' => 'open',
    ]);

    $appStorage = $this->createMock(EntityStorageInterface::class);
    $appStorage->method('load')
      ->with(1)
      ->willReturn($application);

    $oppStorage = $this->createMock(EntityStorageInterface::class);
    $oppStorage->method('load')
      ->with(10)
      ->willReturn($opportunity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entity_type) use ($appStorage, $oppStorage) {
        return match ($entity_type) {
          'funding_application' => $appStorage,
          'funding_opportunity' => $oppStorage,
        };
      });

    $result = $this->service->validateEligibility(1);

    $this->assertFalse($result['eligible']);
    $this->assertNotEmpty($result['issues']);
    $this->assertStringContainsString('supera el maximo', $result['issues'][0]);
  }

  /**
   * @covers ::validateEligibility
   */
  public function testValidateEligibilityDetectsClosedOpportunity(): void {
    $application = $this->createMockEntity([
      'amount_requested' => 50000,
      'opportunity_id' => (object) ['target_id' => 10],
    ]);

    $deadline = (new \DateTime('+30 days'))->format('Y-m-d');
    $opportunity = $this->createMockEntity([
      'max_amount' => 100000,
      'deadline' => $deadline,
      'status' => 'closed',
    ]);

    $appStorage = $this->createMock(EntityStorageInterface::class);
    $appStorage->method('load')
      ->with(1)
      ->willReturn($application);

    $oppStorage = $this->createMock(EntityStorageInterface::class);
    $oppStorage->method('load')
      ->with(10)
      ->willReturn($opportunity);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entity_type) use ($appStorage, $oppStorage) {
        return match ($entity_type) {
          'funding_application' => $appStorage,
          'funding_opportunity' => $oppStorage,
        };
      });

    $result = $this->service->validateEligibility(1);

    $this->assertFalse($result['eligible']);
    $this->assertNotEmpty($result['issues']);
    // Find the issue about the opportunity not being open.
    $closedIssue = array_filter($result['issues'], function ($issue) {
      return str_contains($issue, 'no esta abierta');
    });
    $this->assertNotEmpty($closedIssue);
  }

  /**
   * @covers ::validateEligibility
   */
  public function testValidateEligibilityReturnsErrorWhenApplicationNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $result = $this->service->validateEligibility(999);

    $this->assertFalse($result['eligible']);
    $this->assertContains('Solicitud no encontrada.', $result['issues']);
  }

  /**
   * @covers ::validateEligibility
   */
  public function testValidateEligibilityReturnsErrorWhenOpportunityNotFound(): void {
    $application = $this->createMockEntity([
      'amount_requested' => 50000,
      'opportunity_id' => (object) ['target_id' => 999],
    ]);

    $appStorage = $this->createMock(EntityStorageInterface::class);
    $appStorage->method('load')
      ->with(1)
      ->willReturn($application);

    $oppStorage = $this->createMock(EntityStorageInterface::class);
    $oppStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entity_type) use ($appStorage, $oppStorage) {
        return match ($entity_type) {
          'funding_application' => $appStorage,
          'funding_opportunity' => $oppStorage,
        };
      });

    $result = $this->service->validateEligibility(1);

    $this->assertFalse($result['eligible']);
    $this->assertContains('Convocatoria asociada no encontrada.', $result['issues']);
  }

}
