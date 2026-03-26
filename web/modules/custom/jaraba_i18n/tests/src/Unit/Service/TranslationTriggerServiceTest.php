<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_i18n\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\jaraba_i18n\Service\TranslationTriggerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para TranslationTriggerService.
 *
 * @group jaraba_i18n
 * @coversDefaultClass \Drupal\jaraba_i18n\Service\TranslationTriggerService
 */
class TranslationTriggerServiceTest extends TestCase {

  private QueueFactory&MockObject $queueFactory;
  private QueueInterface&MockObject $queue;
  private LoggerInterface&MockObject $logger;
  private TranslationTriggerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->queue = $this->createMock(QueueInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->queueFactory->method('get')
      ->with(TranslationTriggerService::QUEUE_NAME)
      ->willReturn($this->queue);

    $this->service = new TranslationTriggerService(
      $this->queueFactory,
      $this->logger,
    );
  }

  /**
   * @covers ::getSupportedEntityTypes
   */
  public function testGetSupportedEntityTypesReturnsAllTiers(): void {
    $types = $this->service->getSupportedEntityTypes();

    static::assertContains('page_content', $types);
    static::assertContains('content_article', $types);
    static::assertContains('site_config', $types);
    static::assertContains('tenant_faq', $types);
    static::assertContains('tenant_policy', $types);
    static::assertNotContains('vertical', $types);
    static::assertNotContains('tenant', $types);
  }

  /**
   * @covers ::isCanvasEntity
   */
  public function testIsCanvasEntityForPageContent(): void {
    static::assertTrue($this->service->isCanvasEntity('page_content'));
    static::assertTrue($this->service->isCanvasEntity('content_article'));
    static::assertFalse($this->service->isCanvasEntity('site_config'));
  }

  /**
   * @covers ::isTextEntity
   */
  public function testIsTextEntityForSiteConfig(): void {
    static::assertTrue($this->service->isTextEntity('site_config'));
    static::assertTrue($this->service->isTextEntity('tenant_faq'));
    static::assertFalse($this->service->isTextEntity('page_content'));
  }

  /**
   * @covers ::getTranslatableFields
   */
  public function testGetTranslatableFieldsReturnsCorrectFields(): void {
    $fields = $this->service->getTranslatableFields('site_config');
    static::assertContains('site_name', $fields);
    static::assertContains('site_tagline', $fields);
    static::assertCount(8, $fields);

    $fields = $this->service->getTranslatableFields('tenant_faq');
    static::assertContains('question', $fields);
    static::assertContains('answer', $fields);
    static::assertContains('question_variants', $fields);

    static::assertSame([], $this->service->getTranslatableFields('nonexistent'));
  }

  /**
   * @covers ::handleEntityChange
   */
  public function testInsertPageContentEnqueues(): void {
    $entity = $this->createEntityMock('page_content');

    $this->queue->expects(static::once())
      ->method('createItem')
      ->with(static::callback(function (array $data): bool {
        return $data['entity_type'] === 'page_content'
          && $data['entity_id'] === 42
          && $data['tier'] === 'canvas';
      }));

    $this->service->handleEntityChange($entity, TRUE);
  }

  /**
   * @covers ::handleEntityChange
   */
  public function testInsertTextEntityEnqueuesWithTextTier(): void {
    $entity = $this->createEntityMock('site_config');

    $this->queue->expects(static::once())
      ->method('createItem')
      ->with(static::callback(function (array $data): bool {
        return $data['entity_type'] === 'site_config'
          && $data['tier'] === 'text';
      }));

    $this->service->handleEntityChange($entity, TRUE);
  }

  /**
   * @covers ::handleEntityChange
   */
  public function testUnsupportedEntityTypeDoesNotEnqueue(): void {
    $entity = $this->createEntityMock('vertical');

    $this->queue->expects(static::never())->method('createItem');

    $this->service->handleEntityChange($entity, TRUE);
  }

  /**
   * @covers ::handleEntityChange
   */
  public function testSyncingEntityDoesNotEnqueue(): void {
    $entity = $this->createEntityMock('page_content', syncing: TRUE);

    $this->queue->expects(static::never())->method('createItem');

    $this->service->handleEntityChange($entity, TRUE);
  }

  /**
   * @covers ::handleEntityChange
   */
  public function testNonDefaultTranslationDoesNotEnqueue(): void {
    $entity = $this->createEntityMock('page_content', isDefault: FALSE);

    $this->queue->expects(static::never())->method('createItem');

    $this->service->handleEntityChange($entity, TRUE);
  }

  /**
   * Tests de update con $entity->original requieren Kernel tests
   * por la restriccion de PHP 8.4 en dynamic properties sobre mocks
   * de interfaces (MOCK-DYNPROP-001).
   *
   * @covers ::handleEntityChange
   */
  public function testUpdateWithoutOriginalDoesNotEnqueue(): void {
    $entity = $this->createEntityMock('page_content');

    $this->queue->expects(static::never())->method('createItem');

    $this->service->handleEntityChange($entity, FALSE);
  }

  /**
   * @covers ::enqueue
   */
  public function testEnqueueCreatesCanvasQueueItem(): void {
    $entity = $this->createEntityMock('page_content', entityId: 99);

    $this->queue->expects(static::once())
      ->method('createItem')
      ->with(static::callback(function (array $data): bool {
        return $data['entity_type'] === 'page_content'
          && $data['entity_id'] === 99
          && $data['tier'] === 'canvas'
          && array_key_exists('changed_time', $data);
      }));

    $this->service->enqueue($entity);
  }

  /**
   * @covers ::enqueue
   */
  public function testEnqueueCreatesTextQueueItem(): void {
    $entity = $this->createEntityMock('tenant_faq', entityId: 55);

    $this->queue->expects(static::once())
      ->method('createItem')
      ->with(static::callback(function (array $data): bool {
        return $data['entity_type'] === 'tenant_faq'
          && $data['entity_id'] === 55
          && $data['tier'] === 'text';
      }));

    $this->service->enqueue($entity);
  }

  /**
   * Crea un mock de ContentEntityInterface con configuracion comun.
   */
  private function createEntityMock(
    string $entityTypeId,
    int $entityId = 42,
    bool $syncing = FALSE,
    bool $isDefault = TRUE,
  ): ContentEntityInterface&MockObject {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entityTypeId);
    $entity->method('id')->willReturn($entityId);
    $entity->method('isSyncing')->willReturn($syncing);
    $entity->method('isDefaultTranslation')->willReturn($isDefault);
    $entity->method('hasField')->willReturn(TRUE);

    $changedField = new class {

      public int $value = 1000;

    };
    $entity->method('get')->willReturn($changedField);

    return $entity;
  }

}
