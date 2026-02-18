<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\ServiciosConectaJourneyProgressionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ServiciosConectaJourneyProgressionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ServiciosConectaJourneyProgressionService
 */
class ServiciosConectaJourneyProgressionServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected StateInterface $state;
  protected Connection $database;
  protected LoggerInterface $logger;
  protected ServiciosConectaJourneyProgressionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock container for t() and \Drupal::time().
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $time = $this->createMock(\Drupal\Component\Datetime\TimeInterface::class);
    $time->method('getRequestTime')->willReturn(time());
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    // Constructor: (EntityTypeManagerInterface, StateInterface, Connection, LoggerInterface)
    $this->service = new ServiciosConectaJourneyProgressionService(
      $this->entityTypeManager,
      $this->state,
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests that evaluate returns NULL when no rules match.
   *
   * @covers ::evaluate
   */
  public function testEvaluateReturnsNullWhenNoRulesMatch(): void {
    $userId = 123;

    // No dismissed rules.
    $this->state->method('get')->willReturn([]);

    // All entity storage calls throw (no entities configured in unit test).
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Entity type not found'));

    $result = $this->service->evaluate($userId);

    $this->assertNull($result);
  }

  /**
   * Tests that dismissAction stores the rule and clears pending cache.
   *
   * @covers ::dismissAction
   */
  public function testDismissAction(): void {
    $userId = 123;
    $ruleId = 'no_services';

    $this->state->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn([]);

    $this->state->expects($this->atLeastOnce())
      ->method('set')
      ->with(
        $this->equalTo("serviciosconecta_proactive_dismissed_{$userId}"),
        $this->equalTo([$ruleId])
      );

    $this->state->expects($this->once())
      ->method('delete')
      ->with("serviciosconecta_proactive_pending_{$userId}");

    $this->service->dismissAction($userId, $ruleId);
  }

}
