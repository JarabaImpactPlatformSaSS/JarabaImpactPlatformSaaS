<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_security_compliance\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_security_compliance\Entity\SecurityPolicy;
use Drupal\jaraba_security_compliance\Service\PolicyEnforcerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PolicyEnforcerService.
 *
 * @group jaraba_security_compliance
 * @coversDefaultClass \Drupal\jaraba_security_compliance\Service\PolicyEnforcerService
 */
class PolicyEnforcerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected PolicyEnforcerService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked entity storage.
   */
  protected EntityStorageInterface&MockObject $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('security_policy_v2')
      ->willReturn($this->storage);

    $this->service = new PolicyEnforcerService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getActivePolicies returns active policies.
   *
   * @covers ::getActivePolicies
   */
  public function testGetActivePoliciesReturnsActivePolicies(): void {
    $policy = $this->createMock(SecurityPolicy::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('notExists')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([1 => $policy, 2 => $policy]);

    $result = $this->service->getActivePolicies();
    $this->assertCount(2, $result);
  }

  /**
   * Tests getActivePolicies returns empty array when no policies exist.
   *
   * @covers ::getActivePolicies
   */
  public function testGetActivePoliciesReturnsEmptyWhenNoPolicies(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('notExists')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getActivePolicies();
    $this->assertEmpty($result);
  }

  /**
   * Tests isPolicyCompliant returns true when compliant.
   *
   * @covers ::isPolicyCompliant
   */
  public function testIsPolicyCompliantReturnsTrueWhenCompliant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(1);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->isPolicyCompliant('access_control');
    $this->assertTrue($result);
  }

  /**
   * Tests isPolicyCompliant returns false when not compliant.
   *
   * @covers ::isPolicyCompliant
   */
  public function testIsPolicyCompliantReturnsFalseWhenNotCompliant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->isPolicyCompliant('access_control');
    $this->assertFalse($result);
  }

  /**
   * Tests getViolations returns violations for missing policies.
   *
   * @covers ::getViolations
   */
  public function testGetViolationsReturnsMissingPolicies(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // No policies exist for any type.
    $query->method('execute')->willReturn(0);

    $this->storage->method('getQuery')->willReturn($query);

    $violations = $this->service->getViolations();

    // Should have 5 violations (one for each required type).
    $this->assertCount(5, $violations);

    // Verify access_control is critical.
    $accessControlViolation = array_filter($violations, fn($v) => $v['policy_type'] === 'access_control');
    $this->assertNotEmpty($accessControlViolation);
    $first = reset($accessControlViolation);
    $this->assertEquals('critical', $first['severity']);
  }

  /**
   * Tests getViolations returns empty when all policies exist.
   *
   * @covers ::getViolations
   */
  public function testGetViolationsReturnsEmptyWhenAllPoliciesExist(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    // All policies exist.
    $query->method('execute')->willReturn(1);

    $this->storage->method('getQuery')->willReturn($query);

    $violations = $this->service->getViolations();
    $this->assertEmpty($violations);
  }

  /**
   * Tests getActivePolicies handles exceptions gracefully.
   *
   * @covers ::getActivePolicies
   */
  public function testGetActivePoliciesHandlesExceptions(): void {
    $this->storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getActivePolicies();
    $this->assertEmpty($result);
  }

}
