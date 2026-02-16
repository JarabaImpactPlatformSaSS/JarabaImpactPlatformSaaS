<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_dr\Service\DrTestRunnerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para DrTestRunnerService.
 *
 * @coversDefaultClass \Drupal\jaraba_dr\Service\DrTestRunnerService
 * @group jaraba_dr
 */
class DrTestRunnerServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected DrTestRunnerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('jaraba_dr.settings')->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new DrTestRunnerService(
      $entityTypeManager,
      $configFactory,
      $logger,
    );
  }

  /**
   * Verifica que executeTest devuelve cero (stub).
   *
   * @covers ::executeTest
   */
  public function testExecuteTestReturnsZero(): void {
    $result = $this->service->executeTest('full_dr', 'Test DR completo', 1);
    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que runScheduledTests devuelve cero (stub).
   *
   * @covers ::runScheduledTests
   */
  public function testRunScheduledTestsReturnsZero(): void {
    $result = $this->service->runScheduledTests();
    $this->assertEquals(0, $result);
  }

}
