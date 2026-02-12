<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_email\Service\CampaignService;
use Drupal\jaraba_email\Service\SequenceManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para SequenceManagerService.
 *
 * @covers \Drupal\jaraba_email\Service\SequenceManagerService
 * @group jaraba_email
 */
class SequenceManagerServiceTest extends UnitTestCase {

  protected SequenceManagerService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected CampaignService $campaignService;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->campaignService = $this->createMock(CampaignService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new SequenceManagerService(
      $this->entityTypeManager,
      $this->campaignService,
      $this->logger,
    );
  }

  /**
   * Tests inscribir suscriptor en secuencia inactiva.
   */
  public function testEnrollSubscriberInactiveSequence(): void {
    $sequence = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $isActiveField = new \stdClass();
    $isActiveField->value = FALSE;
    $sequence->method('get')->willReturnMap([
      ['is_active', $isActiveField],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($sequence);

    $this->entityTypeManager->method('getStorage')
      ->with('email_sequence')
      ->willReturn($storage);

    $result = $this->service->enrollSubscriber(100, 1);
    $this->assertFalse($result);
  }

  /**
   * Tests inscribir en secuencia inexistente.
   */
  public function testEnrollSubscriberNonExistentSequence(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('email_sequence')
      ->willReturn($storage);

    $result = $this->service->enrollSubscriber(100, 999);
    $this->assertFalse($result);
  }

  /**
   * Tests salir de secuencia.
   */
  public function testExitSequence(): void {
    $sequence = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $currentEnrolledField = new \stdClass();
    $currentEnrolledField->value = 5;
    $sequence->method('get')->willReturnMap([
      ['currently_enrolled', $currentEnrolledField],
    ]);
    $sequence->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($sequence);

    $this->entityTypeManager->method('getStorage')
      ->with('email_sequence')
      ->willReturn($storage);

    $result = $this->service->exitSequence(100, 1, 'goal_reached');
    $this->assertTrue($result);
  }

  /**
   * Tests procesar cola vacia.
   */
  public function testProcessQueueEmpty(): void {
    $result = $this->service->processQueue(50);
    $this->assertArrayHasKey('processed', $result);
    $this->assertArrayHasKey('errors', $result);
    $this->assertEquals(0, $result['processed']);
  }

}
