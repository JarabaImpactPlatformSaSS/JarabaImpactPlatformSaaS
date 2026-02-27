<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use Drupal\ecosistema_jaraba_core\Service\ReviewModerationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ReviewModerationService.
 *
 * Verifica transiciones de estado, rechazo de estados invalidos,
 * STATUS_FIELD_MAP y disparos de recalculacion de agregacion.
 *
 * @covers \Drupal\ecosistema_jaraba_core\Service\ReviewModerationService
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewModerationServiceTest extends TestCase {

  private ReviewModerationService $service;
  private EntityTypeManagerInterface&MockObject $entityTypeManager;
  private AccountProxyInterface&MockObject $currentUser;
  private LoggerInterface&MockObject $logger;
  private ReviewAggregationService&MockObject $aggregationService;

  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn('1');
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->aggregationService = $this->createMock(ReviewAggregationService::class);

    $this->service = new ReviewModerationService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->logger,
      $this->aggregationService,
    );
  }

  /**
   * Tests moderate() rechaza estado invalido.
   */
  public function testModerateRejectsInvalidStatus(): void {
    $this->logger->expects($this->once())->method('error');
    $result = $this->service->moderate('comercio_review', 1, 'invalid_status');
    $this->assertFalse($result);
  }

  /**
   * Tests moderate() con entidad no encontrada.
   */
  public function testModerateEntityNotFound(): void {
    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('comercio_review')
      ->willReturn($storage);

    $result = $this->service->moderate('comercio_review', 999, 'approved');
    $this->assertFalse($result);
  }

  /**
   * Tests moderate() con transicion exitosa a approved.
   */
  public function testModerateApproveSuccess(): void {
    $fieldItem = $this->createMock(FieldItemListInterface::class);
    $fieldItem->value = 'pending';

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')
      ->with('status')
      ->willReturn($fieldItem);
    $entity->expects($this->once())->method('set')
      ->with('status', 'approved');
    $entity->expects($this->once())->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($entity);
    $this->entityTypeManager->method('getStorage')
      ->with('comercio_review')
      ->willReturn($storage);

    // Debe disparar recalculacion de agregacion.
    $this->aggregationService->expects($this->once())
      ->method('recalculateForReviewEntity')
      ->with($entity);

    $result = $this->service->moderate('comercio_review', 1, 'approved');
    $this->assertTrue($result);
  }

  /**
   * Tests moderate() sin cambio (mismo estado) devuelve TRUE sin save.
   */
  public function testModerateSameStatusNoSave(): void {
    // Usar anonymous class en vez de mock para evitar problemas con
    // propiedades dinamicas en PHPUnit 11 + PHP 8.4.
    $fieldItem = new class {
      public string $value = 'approved';
    };

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')
      ->with('status')
      ->willReturn($fieldItem);
    $entity->expects($this->never())->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn($entity);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $result = $this->service->moderate('comercio_review', 1, 'approved');
    $this->assertTrue($result);
  }

  /**
   * Tests moderate() hacia 'flagged' NO dispara recalculacion.
   */
  public function testModerateFlaggedNoRecalculation(): void {
    $fieldItem = $this->createMock(FieldItemListInterface::class);
    $fieldItem->value = 'pending';

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')
      ->with('status')
      ->willReturn($fieldItem);
    $entity->expects($this->once())->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn($entity);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    // NO debe disparar recalculacion para pending->flagged.
    $this->aggregationService->expects($this->never())
      ->method('recalculateForReviewEntity');

    $result = $this->service->moderate('comercio_review', 1, 'flagged');
    $this->assertTrue($result);
  }

  /**
   * Tests getStatusFieldName() devuelve el nombre correcto por tipo.
   *
   * @dataProvider statusFieldMapProvider
   */
  public function testGetStatusFieldName(string $entityTypeId, string $expected): void {
    $this->assertSame($expected, $this->service->getStatusFieldName($entityTypeId));
  }

  /**
   * Data provider para STATUS_FIELD_MAP.
   */
  public static function statusFieldMapProvider(): array {
    return [
      'comercio_review' => ['comercio_review', 'status'],
      'review_agro' => ['review_agro', 'state'],
      'review_servicios' => ['review_servicios', 'status'],
      'session_review' => ['session_review', 'review_status'],
      'course_review' => ['course_review', 'review_status'],
      'content_comment' => ['content_comment', 'review_status'],
      'unknown type fallback' => ['unknown_entity', 'review_status'],
    ];
  }

  /**
   * Tests getSupportedEntityTypes() devuelve los 6 tipos.
   */
  public function testGetSupportedEntityTypes(): void {
    $types = $this->service->getSupportedEntityTypes();
    $this->assertCount(6, $types);
    $this->assertContains('comercio_review', $types);
    $this->assertContains('review_agro', $types);
    $this->assertContains('review_servicios', $types);
    $this->assertContains('session_review', $types);
    $this->assertContains('course_review', $types);
    $this->assertContains('content_comment', $types);
  }

  /**
   * Tests moderate() para review_agro usa campo 'state'.
   */
  public function testModerateAgroUsesStateField(): void {
    $fieldItem = $this->createMock(FieldItemListInterface::class);
    $fieldItem->value = 'pending';

    $entity = $this->createMock(ContentEntityInterface::class);
    // Debe usar 'state' (no 'status') para review_agro.
    $entity->method('get')
      ->with('state')
      ->willReturn($fieldItem);
    $entity->expects($this->once())->method('set')
      ->with('state', 'approved');
    $entity->expects($this->once())->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn($entity);
    $this->entityTypeManager->method('getStorage')
      ->with('review_agro')
      ->willReturn($storage);

    $this->aggregationService->expects($this->once())
      ->method('recalculateForReviewEntity');

    $result = $this->service->moderate('review_agro', 1, 'approved');
    $this->assertTrue($result);
  }

  /**
   * Tests flagReview() delega a moderate() con STATUS_FLAGGED.
   */
  public function testFlagReviewDelegatesToModerate(): void {
    // moderate() fallara porque entidad no existe — verificamos que se intenta.
    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $result = $this->service->flagReview('comercio_review', 1, 'Contenido ofensivo');
    $this->assertFalse($result);
  }

  /**
   * Tests moderate() con estados validos.
   *
   * @dataProvider validStatusProvider
   */
  public function testModerateAcceptsValidStatuses(string $status): void {
    $fieldItem = $this->createMock(FieldItemListInterface::class);
    $fieldItem->value = 'pending';

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')->willReturn($fieldItem);
    $entity->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn($entity);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $result = $this->service->moderate('comercio_review', 1, $status);
    $this->assertTrue($result);
  }

  /**
   * Data provider para estados validos.
   */
  public static function validStatusProvider(): array {
    return [
      'pending' => ['pending'],
      'approved' => ['approved'],
      'rejected' => ['rejected'],
      'flagged' => ['flagged'],
    ];
  }

}
