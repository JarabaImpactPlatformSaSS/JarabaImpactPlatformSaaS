<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\ComercioConectaJourneyProgressionService;
use Drupal\jaraba_journey\Service\JourneyStateManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ComercioConectaJourneyProgressionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ComercioConectaJourneyProgressionService
 */
class ComercioConectaJourneyProgressionServiceTest extends UnitTestCase {

  protected JourneyStateManagerService $journeyStateManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected StateInterface $state;
  protected LoggerInterface $logger;
  protected ComercioConectaJourneyProgressionService $service;

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

    $this->service = new ComercioConectaJourneyProgressionService(
      $this->journeyStateManager,
      $this->entityTypeManager,
      $this->state,
      $this->logger
    );
  }

  /**
   * Tests evaluating rules for product limit reached.
   *
   * @covers ::evaluate
   */
  public function testEvaluateProductLimitReached(): void {
    $userId = 123;

    // Mock Journey State: activation
    $this->journeyStateManager->method('getCurrentPhase')->with($userId, 'comercio_conecta')->willReturn('activation');

    // Mock Product Count = 10 (limit)
    $productQuery = $this->createMock(QueryInterface::class);
    $productQuery->method('accessCheck')->willReturnSelf();
    $productQuery->method('condition')->willReturnSelf();
    $productQuery->method('count')->willReturn($productQuery);
    $productQuery->method('execute')->willReturn(10);

    $productStorage = $this->createMock(EntityStorageInterface::class);
    $productStorage->method('getQuery')->willReturn($productQuery);
    $this->entityTypeManager->method('getStorage')->with('product_retail')->willReturn($productStorage);

    // Act
    $result = $this->service->evaluate($userId);

    // Assert
    $this->assertIsArray($result);
    $this->assertEquals('upgrade_suggestion', $result['rule_id']);
  }

}
