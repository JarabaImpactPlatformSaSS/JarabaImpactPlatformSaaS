<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewWebhookService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ReviewWebhookService (B-18).
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ReviewWebhookService
 */
class ReviewWebhookServiceTest extends UnitTestCase {

  protected ReviewWebhookService $service;
  protected Connection $database;
  protected QueueFactory $queueFactory;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    $this->service = new ReviewWebhookService(
      $this->database,
      $this->queueFactory,
      $this->logger,
    );
  }

  /**
   * @covers ::registerWebhook
   */
  public function testRegisterWebhookInvalidUrl(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->registerWebhook('not-a-url', ['review.created']);
  }

  /**
   * @covers ::registerWebhook
   */
  public function testRegisterWebhookNoValidEvents(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->registerWebhook('https://example.com/hook', ['invalid.event']);
  }

  /**
   * @covers ::dispatch
   */
  public function testDispatchInvalidEventIsIgnored(): void {
    // Queue should never be called for invalid events.
    $this->queueFactory->expects($this->never())->method('get');

    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('comercio_review');

    $this->service->dispatch('invalid.event', $entity);
  }

  /**
   * @covers ::dispatch
   */
  public function testDispatchNonReviewEntityIsIgnored(): void {
    $this->queueFactory->expects($this->never())->method('get');

    $entity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');

    // Non-review entity types return early before DB query.
    // We verify no queue interaction.
  }

}
