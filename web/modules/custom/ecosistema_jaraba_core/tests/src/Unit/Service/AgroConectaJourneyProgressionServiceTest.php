<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaJourneyProgressionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AgroConectaJourneyProgressionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AgroConectaJourneyProgressionService
 */
class AgroConectaJourneyProgressionServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected StateInterface $state;
  protected Connection $database;
  protected LoggerInterface $logger;
  protected AgroConectaJourneyProgressionService $service;

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

    $this->service = new AgroConectaJourneyProgressionService(
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

    // All condition checks will fail because:
    // - checkNoActivity: state returns 0 (no last activity recorded).
    // - checkIncompleteCatalog: entityTypeManager throws (no agro_product storage).
    // - All other conditions are wrapped in try/catch and return FALSE on exception.
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Entity type not found'));

    $result = $this->service->evaluate($userId);

    $this->assertNull($result);
  }

  /**
   * Tests that dismissAction stores the rule in dismissed list.
   *
   * @covers ::dismissAction
   */
  public function testDismissAction(): void {
    $userId = 123;
    $ruleId = 'inactivity_discovery';

    $this->state->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn([]);

    $this->state->expects($this->atLeastOnce())
      ->method('set')
      ->with(
        $this->equalTo("agroconecta_proactive_dismissed_{$userId}"),
        $this->equalTo([$ruleId])
      );

    $this->state->expects($this->once())
      ->method('delete')
      ->with("agroconecta_proactive_pending_{$userId}");

    $this->service->dismissAction($userId, $ruleId);
  }

}
