<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\ServiciosConectaJourneyProgressionService;
use Drupal\jaraba_journey\Service\JourneyStateManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ServiciosConectaJourneyProgressionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ServiciosConectaJourneyProgressionService
 */
class ServiciosConectaJourneyProgressionServiceTest extends UnitTestCase {

  protected JourneyStateManagerService $journeyStateManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected StateInterface $state;
  protected LoggerInterface $logger;
  protected ServiciosConectaJourneyProgressionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->journeyStateManager = $this->createMock(JourneyStateManagerService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock container for t()
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->service = new ServiciosConectaJourneyProgressionService(
      $this->journeyStateManager,
      $this->entityTypeManager,
      $this->state,
      $this->logger
    );
  }

  /**
   * Tests evaluating rules for service limit reached.
   *
   * @covers ::evaluate
   */
  public function testEvaluateServiceLimitReached(): void {
    $userId = 123;

    // Mock Journey State: activation
    $this->journeyStateManager->method('getCurrentPhase')->with($userId, 'servicios_conecta')->willReturn('activation');

    // Mock Service Count = 3 (limit)
    $serviceQuery = $this->createMock(QueryInterface::class);
    $serviceQuery->method('accessCheck')->willReturnSelf();
    $serviceQuery->method('condition')->willReturnSelf();
    $serviceQuery->method('count')->willReturn($serviceQuery);
    $serviceQuery->method('execute')->willReturn(3);

    $serviceStorage = $this->createMock(EntityStorageInterface::class);
    $serviceStorage->method('getQuery')->willReturn($serviceQuery);
    $this->entityTypeManager->method('getStorage')->with('service_offering')->willReturn($serviceStorage);

    // Act
    $result = $this->service->evaluate($userId);

    // Assert
    $this->assertIsArray($result);
    $this->assertEquals('upgrade_suggestion', $result['rule_id']);
  }

}
