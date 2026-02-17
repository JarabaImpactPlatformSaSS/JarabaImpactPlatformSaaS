<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_calendar\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_legal_calendar\Service\DeadlineCalculatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for DeadlineCalculatorService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_calendar\Service\DeadlineCalculatorService
 * @group jaraba_legal_calendar
 */
class DeadlineCalculatorServiceTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_legal_calendar\Service\DeadlineCalculatorService
   */
  protected DeadlineCalculatorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DeadlineCalculatorService(
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests computeDeadline with a predefined rule (contestacion_demanda = 20 dias habiles).
   *
   * @covers ::computeDeadline
   * @covers ::calculate
   * @covers ::isBusinessDay
   */
  public function testCalculateDeadline(): void {
    // 2025-01-06 is a Monday, also Dia de Reyes (holiday).
    // Start from 2025-01-07 Tuesday: 5 business days should skip weekends.
    $baseDate = new \DateTimeImmutable('2025-01-07');

    // Use dynamic rule: 5 business days.
    $result = $this->service->computeDeadline($baseDate, '5_dias_habiles');

    // 2025-01-07 (Tue) + 5 business days = Jan 8 (W), 9 (Th), 10 (F), 13 (M), 14 (Tu).
    $this->assertSame('2025-01-14', $result->format('Y-m-d'));

    // Also test a predefined rule: recurso_reposicion = 5 dias habiles (same result).
    $result2 = $this->service->computeDeadline($baseDate, 'recurso_reposicion');
    $this->assertSame('2025-01-14', $result2->format('Y-m-d'));
  }

  /**
   * Tests that August is treated as non-working for business days (LEC 130.2).
   *
   * @covers ::isBusinessDay
   * @covers ::computeDeadline
   * @covers ::calculate
   */
  public function testAugustIsNonWorking(): void {
    // A Wednesday in August should be non-business.
    $augustDate = new \DateTimeImmutable('2025-08-06');
    $this->assertFalse($this->service->isBusinessDay($augustDate));

    // A Monday in August should also be non-business.
    $augustMonday = new \DateTimeImmutable('2025-08-04');
    $this->assertFalse($this->service->isBusinessDay($augustMonday));

    // Compute from July 28 (Monday) with 5 business days:
    // July 28 is base. +1 day = Jul 29 (Tu, OK=1), Jul 30 (W, OK=2),
    // Jul 31 (Th, OK=3), Aug 1 (Fr, August=skip), ...entire August skipped...
    // Sep 1 (Mon, OK=4), Sep 2 (Tu, OK=5).
    $julyBase = new \DateTimeImmutable('2025-07-28');
    $result = $this->service->computeDeadline($julyBase, '5_dias_habiles');
    $this->assertSame('2025-09-02', $result->format('Y-m-d'));
  }

  /**
   * Tests that weekends are correctly skipped for business days.
   *
   * @covers ::isBusinessDay
   */
  public function testWeekendSkipping(): void {
    // Saturday.
    $saturday = new \DateTimeImmutable('2025-02-01');
    $this->assertFalse($this->service->isBusinessDay($saturday));

    // Sunday.
    $sunday = new \DateTimeImmutable('2025-02-02');
    $this->assertFalse($this->service->isBusinessDay($sunday));

    // Monday (not August, not holiday).
    $monday = new \DateTimeImmutable('2025-02-03');
    $this->assertTrue($this->service->isBusinessDay($monday));

    // Friday (not August, not holiday).
    $friday = new \DateTimeImmutable('2025-02-07');
    $this->assertTrue($this->service->isBusinessDay($friday));
  }

  /**
   * Tests computeDeadline with natural days (dias_naturales).
   *
   * @covers ::computeDeadline
   * @covers ::calculate
   */
  public function testNaturalDaysIncludeWeekends(): void {
    $base = new \DateTimeImmutable('2025-03-01');
    $result = $this->service->computeDeadline($base, '20_dias_naturales');
    $this->assertSame('2025-03-21', $result->format('Y-m-d'));
  }

  /**
   * Tests computeDeadline with months unit.
   *
   * @covers ::computeDeadline
   * @covers ::calculate
   */
  public function testMonthsUnit(): void {
    $base = new \DateTimeImmutable('2025-03-15');
    // recurso_contencioso = 2 meses.
    $result = $this->service->computeDeadline($base, 'recurso_contencioso');
    $this->assertSame('2025-05-15', $result->format('Y-m-d'));
  }

  /**
   * Tests that an unrecognized rule returns the base date unchanged.
   *
   * @covers ::computeDeadline
   */
  public function testUnknownRuleReturnsBaseDate(): void {
    $base = new \DateTimeImmutable('2025-06-01');

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->service->computeDeadline($base, 'regla_inventada');
    $this->assertSame('2025-06-01', $result->format('Y-m-d'));
  }

  /**
   * Tests national holidays are non-business days.
   *
   * @covers ::isBusinessDay
   */
  public function testNationalHolidaysAreNonWorking(): void {
    // January 1st (New Year) - Wednesday in 2025.
    $newYear = new \DateTimeImmutable('2025-01-01');
    $this->assertFalse($this->service->isBusinessDay($newYear));

    // May 1st (Labour Day) - Thursday in 2025.
    $labourDay = new \DateTimeImmutable('2025-05-01');
    $this->assertFalse($this->service->isBusinessDay($labourDay));

    // December 25 (Christmas) - Thursday in 2025.
    $christmas = new \DateTimeImmutable('2025-12-25');
    $this->assertFalse($this->service->isBusinessDay($christmas));
  }

}
