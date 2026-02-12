<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_integrations\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_integrations\Service\AppApprovalService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AppApprovalService.
 *
 * @covers \Drupal\jaraba_integrations\Service\AppApprovalService
 * @group jaraba_integrations
 */
class AppApprovalServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected MailManagerInterface $mailManager;
  protected LoggerInterface $logger;
  protected AppApprovalService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new AppApprovalService(
      $this->entityTypeManager,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Tests submitForReview returns FALSE for non-existent connector.
   */
  public function testSubmitForReviewNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($storage);

    $this->assertFalse($this->service->submitForReview(999));
  }

  /**
   * Tests submitForReview succeeds for draft connector.
   */
  public function testSubmitForReviewFromDraft(): void {
    $statusField = $this->createMock(FieldItemListInterface::class);
    $statusField->value = AppApprovalService::STATUS_DRAFT;

    $connector = $this->createMock(ContentEntityInterface::class);
    $connector->method('get')
      ->willReturnMap([
        ['approval_status', $statusField],
      ]);
    $connector->expects($this->atLeastOnce())->method('set');
    $connector->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($connector);
    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($storage);

    $this->assertTrue($this->service->submitForReview(1));
  }

  /**
   * Tests approve sets correct status.
   */
  public function testApprove(): void {
    $connector = $this->createMock(ContentEntityInterface::class);
    $connector->expects($this->atLeastOnce())->method('set');
    $connector->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($connector);
    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($storage);

    $this->assertTrue($this->service->approve(1, 'Aprobado'));
  }

  /**
   * Tests reject sets correct status and reason.
   */
  public function testReject(): void {
    $connector = $this->createMock(ContentEntityInterface::class);
    $connector->expects($this->atLeastOnce())->method('set');
    $connector->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($connector);
    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($storage);

    $this->assertTrue($this->service->reject(1, 'Falta documentacion'));
  }

}
