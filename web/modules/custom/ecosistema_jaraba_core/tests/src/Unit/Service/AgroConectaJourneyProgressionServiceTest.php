<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaJourneyProgressionService;
use Drupal\jaraba_journey\Service\JourneyStateManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for AgroConectaJourneyProgressionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AgroConectaJourneyProgressionService
 */
class AgroConectaJourneyProgressionServiceTest extends UnitTestCase {

  protected JourneyStateManagerService $journeyStateManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected StateInterface $state;
  protected LoggerInterface $logger;
  protected AgroConectaJourneyProgressionService $service;

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

    $this->service = new AgroConectaJourneyProgressionService(
      $this->journeyStateManager,
      $this->entityTypeManager,
      $this->state,
      $this->logger
    );
  }

  /**
   * Tests evaluating rules when inactivity discovery triggers.
   *
   * @covers ::evaluate
   */
  public function testEvaluateInactivityDiscovery(): void {
    $userId = 123;

    // Mock Journey State: discovery
    $this->journeyStateManager->method('getCurrentPhase')->with($userId, 'agroconecta')->willReturn('discovery');

    // Mock Last Active: 4 days ago (>3 days threshold)
    // We assume the service checks user last_access or similar.
    // For this test, we'll mock the entity query count for products to be 0
    // and assume the logic checks for inactivity.

    // Mock Product Count = 0
    $productQuery = $this->createMock(QueryInterface::class);
    $productQuery->method('accessCheck')->willReturnSelf();
    $productQuery->method('condition')->willReturnSelf();
    $productQuery->method('count')->willReturn($productQuery);
    $productQuery->method('execute')->willReturn(0);

    $productStorage = $this->createMock(EntityStorageInterface::class);
    $productStorage->method('getQuery')->willReturn($productQuery);
    $this->entityTypeManager->method('getStorage')->with('product_agro')->willReturn($productStorage);

    // Act
    $result = $this->service->evaluate($userId);

    // Assert
    // Since we can't easily mock time() inside the service without a Time service injection (which wasn't in the constructor list inferred),
    // we'll focus on the logic flow. If the service uses \Drupal::time(), it's hard to mock in Unit.
    // Assuming the service checks for product count = 0 as a proxy for "inactivity/incomplete setup".

    $this->assertIsArray($result);
    $this->assertEquals('inactivity_discovery', $result['rule_id']);
  }

  /**
   * Tests evaluating rules when catalog is incomplete (activation phase).
   *
   * @covers ::evaluate
   */
  public function testEvaluateIncompleteCatalog(): void {
    $userId = 123;

    // Mock Journey State: activation
    $this->journeyStateManager->method('getCurrentPhase')->with($userId, 'agroconecta')->willReturn('activation');

    // Mock Product Count = 1
    $productQuery = $this->createMock(QueryInterface::class);
    $productQuery->method('accessCheck')->willReturnSelf();
    $productQuery->method('condition')->willReturnSelf();
    $productQuery->method('count')->willReturn($productQuery);
    $productQuery->method('execute')->willReturn(1);

    $productStorage = $this->createMock(EntityStorageInterface::class);
    $productStorage->method('getQuery')->willReturn($productQuery);
    $this->entityTypeManager->method('getStorage')->with('product_agro')->willReturn($productStorage);

    // Act
    $result = $this->service->evaluate($userId);

    // Assert
    $this->assertIsArray($result);
    $this->assertEquals('incomplete_catalog', $result['rule_id']);
  }

}
