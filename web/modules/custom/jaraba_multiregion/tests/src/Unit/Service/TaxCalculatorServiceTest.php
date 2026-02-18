<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_multiregion\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_multiregion\Service\RegionManagerService;
use Drupal\jaraba_multiregion\Service\TaxCalculatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for TaxCalculatorService.
 *
 * @coversDefaultClass \Drupal\jaraba_multiregion\Service\TaxCalculatorService
 * @group jaraba_multiregion
 */
class TaxCalculatorServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_multiregion\Service\TaxCalculatorService
   */
  protected TaxCalculatorService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock region manager service.
   *
   * @var \Drupal\jaraba_multiregion\Service\RegionManagerService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $regionManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock entity storage for tax_rule.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->regionManager = $this->createMock(RegionManagerService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('tax_rule')
      ->willReturn($this->storage);

    $this->service = new TaxCalculatorService(
      $this->entityTypeManager,
      $this->regionManager,
      $this->logger,
    );
  }

  /**
   * Helper: builds a mock tax_rule entity with standard_rate and optional fields.
   *
   * @param float $standardRate
   *   The standard VAT rate percentage.
   * @param float|null $digitalServicesRate
   *   The digital services rate percentage, or NULL.
   * @param string|null $effectiveTo
   *   The effective_to date string, or NULL.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   A mock entity.
   */
  protected function createMockTaxRule(float $standardRate, ?float $digitalServicesRate = NULL, ?string $effectiveTo = NULL): object {
    $entity = $this->createMock(ContentEntityInterface::class);

    $entity->method('get')
      ->willReturnCallback(function (string $fieldName) use ($standardRate, $digitalServicesRate, $effectiveTo) {
        return match ($fieldName) {
          'standard_rate' => (object) ['value' => $standardRate],
          'digital_services_rate' => (object) ['value' => $digitalServicesRate],
          'effective_to' => (object) ['value' => $effectiveTo],
          default => (object) ['value' => NULL],
        };
      });

    return $entity;
  }

  /**
   * Helper: configures storage mock to return a tax rule for getTaxRule().
   *
   * @param object|null $entity
   *   The entity to return from load(), or NULL.
   * @param int[] $ids
   *   The IDs returned by the query.
   */
  protected function setupStorageForGetTaxRule(?object $entity, array $ids = [1]): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn($entity !== NULL ? $ids : []);

    $this->storage->method('getQuery')->willReturn($query);

    if ($entity !== NULL) {
      $this->storage->method('load')
        ->with(reset($ids))
        ->willReturn($entity);
    }
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateSameCountryAppliesLocalVat(): void {
    $taxRule = $this->createMockTaxRule(21.0);
    $this->setupStorageForGetTaxRule($taxRule);

    $result = $this->service->calculate('ES', 'ES', FALSE, NULL, 100.0);

    $this->assertSame(21.0, $result['rate']);
    $this->assertSame(21.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertSame('', $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateSameCountryNoRuleReturnsZero(): void {
    $this->setupStorageForGetTaxRule(NULL);

    $result = $this->service->calculate('ES', 'ES', FALSE, NULL, 100.0);

    $this->assertSame(0.0, $result['rate']);
    $this->assertSame(0.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertStringContainsString('Sin regla fiscal configurada', $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateB2bReverseChargeIntraEu(): void {
    // ES seller -> DE buyer (B2B with valid VAT). DE is in the static EU list,
    // so isEuMember returns TRUE without querying storage. We still need storage
    // mock set up because the constructor already bound it.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->calculate('ES', 'DE', TRUE, 'DE123456789', 500.0);

    $this->assertSame(0.0, $result['rate']);
    $this->assertSame(0.0, $result['amount']);
    $this->assertTrue($result['reverse_charge']);
    $this->assertSame('Art. 196 Directiva 2006/112/CE', $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateB2cOssUsesDestinationCountryRate(): void {
    // ES seller -> DE buyer (B2C). DE is EU member via static list.
    // getTaxRule('DE') should return the buyer country's rule.
    $taxRule = $this->createMockTaxRule(19.0, 19.0);
    $this->setupStorageForGetTaxRule($taxRule);

    $result = $this->service->calculate('ES', 'DE', FALSE, NULL, 200.0);

    $this->assertSame(19.0, $result['rate']);
    $this->assertSame(38.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertStringContainsString('Regimen OSS', (string) $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateB2cOssFallsBackToStandardRate(): void {
    // digital_services_rate is NULL, so it should fallback to standard_rate.
    $taxRule = $this->createMockTaxRule(20.0, NULL);
    $this->setupStorageForGetTaxRule($taxRule);

    $result = $this->service->calculate('ES', 'FR', FALSE, NULL, 150.0);

    $this->assertSame(20.0, $result['rate']);
    $this->assertSame(30.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateB2cOssNoRuleReturnsZero(): void {
    $this->setupStorageForGetTaxRule(NULL);

    $result = $this->service->calculate('ES', 'DE', FALSE, NULL, 200.0);

    $this->assertSame(0.0, $result['rate']);
    $this->assertSame(0.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertStringContainsString('Sin regla fiscal para pais destino', $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateNonEuExportExempt(): void {
    // ES seller -> US buyer (non-EU). US is not in EU_MEMBER_STATES,
    // and the fallback query returns empty.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->calculate('ES', 'US', FALSE, NULL, 1000.0);

    $this->assertSame(0.0, $result['rate']);
    $this->assertSame(0.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertStringContainsString('Exportacion fuera UE', (string) $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateB2bNonEuExportExempt(): void {
    // ES seller -> US buyer (B2B, non-EU). Even with a VAT number,
    // non-EU buyers get the export exemption path, not reverse charge.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->calculate('ES', 'US', TRUE, 'US123456', 1000.0);

    $this->assertSame(0.0, $result['rate']);
    $this->assertSame(0.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertStringContainsString('Exportacion fuera UE', (string) $result['article']);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateExceptionReturnsErrorResult(): void {
    // Create a tax rule entity that passes getTaxRule()'s effective_to check
    // but throws when calculate() accesses standard_rate.
    $brokenEntity = $this->createMock(ContentEntityInterface::class);
    $brokenEntity->method('get')
      ->willReturnCallback(function (string $fieldName) {
        if ($fieldName === 'effective_to') {
          return (object) ['value' => NULL];
        }
        throw new \RuntimeException('Database connection lost');
      });

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([99]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(99)->willReturn($brokenEntity);

    $this->logger->expects($this->atLeastOnce())
      ->method('error');

    $result = $this->service->calculate('ES', 'ES', FALSE, NULL, 100.0);

    $this->assertSame(0.0, $result['rate']);
    $this->assertSame(0.0, $result['amount']);
    $this->assertFalse($result['reverse_charge']);
    $this->assertStringContainsString('Error en calculo fiscal', $result['article']);
  }

  /**
   * @covers ::isEuMember
   */
  public function testIsEuMemberReturnsTrueForStaticMember(): void {
    $this->assertTrue($this->service->isEuMember('ES'));
    $this->assertTrue($this->service->isEuMember('DE'));
    $this->assertTrue($this->service->isEuMember('FR'));
    $this->assertTrue($this->service->isEuMember('IT'));
  }

  /**
   * @covers ::isEuMember
   */
  public function testIsEuMemberReturnsFalseForNonMember(): void {
    // US is not in the static list, so fallback query is triggered.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->storage->method('getQuery')->willReturn($query);

    $this->assertFalse($this->service->isEuMember('US'));
  }

  /**
   * @covers ::isReverseCharge
   */
  public function testIsReverseChargeReturnsTrueForIntraEuB2b(): void {
    // No storage query needed; DE is in the static EU list.
    $result = $this->service->isReverseCharge('ES', 'DE', TRUE, 'DE123456789');

    $this->assertTrue($result);
  }

  /**
   * @covers ::isReverseCharge
   */
  public function testIsReverseChargeReturnsFalseForSameCountry(): void {
    $result = $this->service->isReverseCharge('ES', 'ES', TRUE, 'ES12345678A');

    $this->assertFalse($result);
  }

  /**
   * @covers ::isReverseCharge
   */
  public function testIsReverseChargeReturnsFalseForB2c(): void {
    $result = $this->service->isReverseCharge('ES', 'DE', FALSE, NULL);

    $this->assertFalse($result);
  }

  /**
   * @covers ::isReverseCharge
   */
  public function testIsReverseChargeReturnsFalseForNonEuBuyer(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->isReverseCharge('ES', 'US', TRUE, 'US123456');

    $this->assertFalse($result);
  }

  /**
   * @covers ::calculate
   */
  public function testCalculateSameCountryRoundsAmountCorrectly(): void {
    // 21% of 99.99 = 20.9979 -> rounded to 21.0
    $taxRule = $this->createMockTaxRule(21.0);
    $this->setupStorageForGetTaxRule($taxRule);

    $result = $this->service->calculate('ES', 'ES', FALSE, NULL, 99.99);

    $this->assertSame(21.0, $result['rate']);
    $this->assertSame(21.0, $result['amount']);
  }

  /**
   * @covers ::getOssStatus
   */
  public function testGetOssStatusForEuSellerWithOssRegistered(): void {
    $region = $this->createMock(ContentEntityInterface::class);
    $region->method('hasField')
      ->with('oss_registered')
      ->willReturn(TRUE);

    $ossField = (object) ['value' => TRUE];
    $region->method('get')
      ->with('oss_registered')
      ->willReturn($ossField);

    $this->regionManager->method('getRegion')->willReturn($region);

    $result = $this->service->getOssStatus('ES');

    $this->assertSame(10000.0, $result['threshold_eur']);
    $this->assertSame('ES', $result['seller_country']);
    $this->assertTrue($result['is_eu_seller']);
    $this->assertTrue($result['oss_registered']);
  }

  /**
   * @covers ::getOssStatus
   */
  public function testGetOssStatusForNonEuSellerReturnsNotApplicable(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $this->storage->method('getQuery')->willReturn($query);

    $this->regionManager->method('getRegion')->willReturn(NULL);

    $result = $this->service->getOssStatus('US');

    $this->assertSame('US', $result['seller_country']);
    $this->assertFalse($result['is_eu_seller']);
    $this->assertFalse($result['oss_registered']);
  }

}
