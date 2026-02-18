<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\ComercioConectaJourneyProgressionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ComercioConectaJourneyProgressionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ComercioConectaJourneyProgressionService
 */
class ComercioConectaJourneyProgressionServiceTest extends UnitTestCase {

  protected StateInterface $state;
  protected LoggerInterface $logger;
  protected TimeInterface $time;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected ComercioConectaJourneyProgressionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(time());
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Mock container for t()
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->service = new ComercioConectaJourneyProgressionService(
      $this->state,
      $this->logger,
      $this->time,
      $this->entityTypeManager,
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
    $ruleKey = 'upgrade_suggestion';

    $this->state->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn([]);

    $this->state->expects($this->atLeastOnce())
      ->method('set')
      ->with(
        $this->equalTo("comercioconecta_proactive_dismissed_{$userId}"),
        $this->equalTo([$ruleKey])
      );

    $this->state->expects($this->once())
      ->method('delete')
      ->with("comercioconecta_proactive_pending_{$userId}");

    $this->service->dismissAction($userId, $ruleKey);
  }

}
