<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ecosistema_jaraba_core\Service\FiscalComplianceService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for FiscalComplianceService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\FiscalComplianceService
 */
class FiscalComplianceServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
  }

  /**
   * Creates a service instance with optional fiscal module services.
   */
  protected function createService(
    ?object $hashService = NULL,
    ?object $remisionService = NULL,
    ?object $faceClient = NULL,
    ?object $paymentStatusService = NULL,
    ?object $certificateManager = NULL,
  ): FiscalComplianceService {
    $service = new FiscalComplianceService(
      $this->entityTypeManager,
      $this->logger,
      $hashService,
      $remisionService,
      $faceClient,
      $paymentStatusService,
      $certificateManager,
    );
    $service->setStringTranslation($this->getStringTranslationStub());
    return $service;
  }

  /**
   * Tests perfect score when no fiscal modules are installed.
   *
   * @covers ::calculateScore
   */
  public function testPerfectScoreWhenNoModulesInstalled(): void {
    $service = $this->createService();
    $result = $service->calculateScore('1');

    $this->assertSame(100, $result['score']);
    $this->assertSame('A', $result['grade']);
    $this->assertEmpty($result['alerts']);
    $this->assertSame('1', $result['tenant_id']);
    $this->assertArrayHasKey('calculated_at', $result);
  }

  /**
   * Tests all factors return not_applicable when modules absent.
   *
   * @covers ::calculateScore
   */
  public function testAllFactorsNotApplicableWhenModulesAbsent(): void {
    $service = $this->createService();
    $result = $service->calculateScore('1');

    foreach ($result['factors'] as $factor) {
      $this->assertSame('not_applicable', $factor['status']);
      $this->assertSame(20, $factor['score']);
      $this->assertSame(20, $factor['max']);
    }
  }

  /**
   * Tests VeriFactu chain OK gives 20 points.
   *
   * @covers ::calculateScore
   */
  public function testVerifactuChainOkGives20Points(): void {
    $hashService = $this->createMock(FiscalHashServiceInterface::class);
    $chainResult = new class {

      public function toArray(): array {
        return ['is_valid' => TRUE];
      }

    };
    $hashService->method('verifyChainIntegrity')->willReturn($chainResult);

    // Mock AEAT remision query (no overdue records).
    $this->mockEntityQuery('verifactu_invoice_record', 0);

    $service = $this->createService(hashService: $hashService);
    $result = $service->calculateScore('1');

    $this->assertSame(20, $result['factors']['verifactu_chain']['score']);
    $this->assertSame('ok', $result['factors']['verifactu_chain']['status']);
  }

  /**
   * Tests VeriFactu broken chain gives 0 points + critical alert.
   *
   * @covers ::calculateScore
   */
  public function testVerifactuBrokenChainGives0Points(): void {
    $hashService = $this->createMock(FiscalHashServiceInterface::class);
    $chainResult = new class {

      public function toArray(): array {
        return ['is_valid' => FALSE, 'error_message' => 'Chain broken at record 42'];
      }

    };
    $hashService->method('verifyChainIntegrity')->willReturn($chainResult);

    // Mock AEAT remision query.
    $this->mockEntityQuery('verifactu_invoice_record', 0);

    $service = $this->createService(hashService: $hashService);
    $result = $service->calculateScore('1');

    $this->assertSame(0, $result['factors']['verifactu_chain']['score']);
    $this->assertSame('critical', $result['factors']['verifactu_chain']['status']);
    $this->assertNotEmpty($result['alerts']);
  }

  /**
   * Tests certificate expired gives 0 points.
   *
   * @covers ::calculateScore
   */
  public function testCertificateExpiredGives0Points(): void {
    $certManager = $this->createMock(FiscalCertificateManagerInterface::class);
    $certResult = new class {

      public function toArray(): array {
        return ['is_valid' => FALSE, 'error' => 'Certificate expired'];
      }

    };
    $certManager->method('validateTenantCertificate')->willReturn($certResult);

    $service = $this->createService(certificateManager: $certManager);
    $result = $service->calculateScore('1');

    $this->assertSame(0, $result['factors']['certificates']['score']);
    $this->assertSame('critical', $result['factors']['certificates']['status']);
  }

  /**
   * Tests certificate near expiry gives 10 points.
   *
   * @covers ::calculateScore
   */
  public function testCertificateNearExpiryGives10Points(): void {
    $certManager = $this->createMock(FiscalCertificateManagerInterface::class);
    $certResult = new class {

      public function toArray(): array {
        return ['is_valid' => TRUE, 'days_remaining' => 15];
      }

    };
    $certManager->method('validateTenantCertificate')->willReturn($certResult);

    $service = $this->createService(certificateManager: $certManager);
    $result = $service->calculateScore('1');

    $this->assertSame(10, $result['factors']['certificates']['score']);
    $this->assertSame('warning', $result['factors']['certificates']['status']);
  }

  /**
   * Tests grade boundaries.
   *
   * @covers ::calculateScore
   * @dataProvider gradeProvider
   */
  public function testGradeBoundaries(int $expectedScore, string $expectedGrade): void {
    // We test the grade logic by verifying the mapping.
    // Use reflection to test the private method.
    $service = $this->createService();
    $reflection = new \ReflectionMethod($service, 'scoreToGrade');
    $reflection->setAccessible(TRUE);

    $this->assertSame($expectedGrade, $reflection->invoke($service, $expectedScore));
  }

  /**
   * Data provider for grade boundaries.
   */
  public static function gradeProvider(): array {
    return [
      '100 = A' => [100, 'A'],
      '90 = A' => [90, 'A'],
      '89 = B' => [89, 'B'],
      '70 = B' => [70, 'B'],
      '69 = C' => [69, 'C'],
      '50 = C' => [50, 'C'],
      '49 = D' => [49, 'D'],
      '30 = D' => [30, 'D'],
      '29 = F' => [29, 'F'],
      '0 = F' => [0, 'F'],
    ];
  }

  /**
   * Tests getInstalledModules returns correct booleans.
   *
   * @covers ::getInstalledModules
   */
  public function testGetInstalledModulesReflectsInjection(): void {
    $hashService = $this->createMock(FiscalHashServiceInterface::class);
    $faceClient = $this->createMock(\stdClass::class);

    $service = $this->createService(
      hashService: $hashService,
      faceClient: $faceClient,
    );

    $modules = $service->getInstalledModules();
    $this->assertTrue($modules['verifactu']);
    $this->assertTrue($modules['facturae']);
    $this->assertFalse($modules['einvoice_b2b']);
  }

  /**
   * Tests getComplianceSummary returns compact format.
   *
   * @covers ::getComplianceSummary
   */
  public function testGetComplianceSummaryFormat(): void {
    $service = $this->createService();
    $summary = $service->getComplianceSummary('1');

    $this->assertArrayHasKey('score', $summary);
    $this->assertArrayHasKey('grade', $summary);
    $this->assertArrayHasKey('alert_count', $summary);
    $this->assertSame(100, $summary['score']);
    $this->assertSame(0, $summary['alert_count']);
  }

  /**
   * Tests service exception handling returns 0 score.
   *
   * @covers ::calculateScore
   */
  public function testHashServiceExceptionGives0Points(): void {
    $hashService = $this->createMock(FiscalHashServiceInterface::class);
    $hashService->method('verifyChainIntegrity')
      ->willThrowException(new \RuntimeException('Connection failed'));

    // Mock AEAT remision query.
    $this->mockEntityQuery('verifactu_invoice_record', 0);

    $service = $this->createService(hashService: $hashService);
    $result = $service->calculateScore('1');

    $this->assertSame(0, $result['factors']['verifactu_chain']['score']);
    $this->assertSame('error', $result['factors']['verifactu_chain']['status']);
  }

  /**
   * Helper to mock entity query returning a count.
   */
  protected function mockEntityQuery(string $entityType, int $count): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($count);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with($entityType)
      ->willReturn($storage);
  }

}

/**
 * Temporary interface for mocking hash service.
 */
interface FiscalHashServiceInterface {

  public function verifyChainIntegrity();

}

/**
 * Temporary interface for mocking certificate manager.
 */
interface FiscalCertificateManagerInterface {

  public function validateTenantCertificate(string $tenantId);

}
