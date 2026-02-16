<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_dr\Service\IncidentCommunicatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para IncidentCommunicatorService.
 *
 * @coversDefaultClass \Drupal\jaraba_dr\Service\IncidentCommunicatorService
 * @group jaraba_dr
 */
class IncidentCommunicatorServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected IncidentCommunicatorService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['notification_channels', ['email', 'slack']],
      ['escalation_timeout_minutes', 30],
    ]);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('jaraba_dr.settings')->willReturn($config);
    $mailManager = $this->createMock(MailManagerInterface::class);
    $queueFactory = $this->createMock(QueueFactory::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new IncidentCommunicatorService(
      $entityTypeManager,
      $configFactory,
      $mailManager,
      $queueFactory,
      $logger,
    );
  }

  /**
   * Verifica que notifyIncident devuelve cero (stub).
   *
   * @covers ::notifyIncident
   */
  public function testNotifyIncidentReturnsZero(): void {
    $result = $this->service->notifyIncident(1, 'Test notification');
    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que checkEscalations devuelve cero (stub).
   *
   * @covers ::checkEscalations
   */
  public function testCheckEscalationsReturnsZero(): void {
    $result = $this->service->checkEscalations();
    $this->assertEquals(0, $result);
  }

}
