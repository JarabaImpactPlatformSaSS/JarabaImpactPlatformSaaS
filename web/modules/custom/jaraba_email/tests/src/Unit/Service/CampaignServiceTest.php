<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_email\Service\CampaignService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para CampaignService.
 *
 * @covers \Drupal\jaraba_email\Service\CampaignService
 * @group jaraba_email
 */
class CampaignServiceTest extends UnitTestCase {

  protected CampaignService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected MailManagerInterface $mailManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new CampaignService(
      $this->entityTypeManager,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Tests enviar campaña inexistente retorna error.
   */
  public function testSendCampaignNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $result = $this->service->sendCampaign(999);

    $this->assertFalse($result['success']);
    $this->assertEquals('Campaña no encontrada.', $result['error']);
  }

  /**
   * Tests enviar campaña que no puede enviarse en su estado actual.
   */
  public function testSendCampaignCannotSend(): void {
    $campaign = $this->createMock(ContentEntityInterface::class);
    $campaign->method('canSend')->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $result = $this->service->sendCampaign(1);

    $this->assertFalse($result['success']);
    $this->assertEquals('La campaña no puede enviarse en su estado actual.', $result['error']);
  }

  /**
   * Tests programar campaña inexistente retorna FALSE.
   */
  public function testScheduleCampaignNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $result = $this->service->scheduleCampaign(999, '2026-03-01T10:00:00');

    $this->assertFalse($result);
  }

  /**
   * Tests programar campaña exitosamente.
   */
  public function testScheduleCampaignSuccess(): void {
    $campaign = $this->createMock(ContentEntityInterface::class);
    $campaign->method('canSend')->willReturn(TRUE);
    $campaign->method('getName')->willReturn('Campaña Test');
    $campaign->expects($this->atLeastOnce())->method('set');
    $campaign->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($campaign);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $result = $this->service->scheduleCampaign(1, '2026-03-01T10:00:00');

    $this->assertTrue($result);
  }

  /**
   * Tests envío de prueba con campaña inexistente retorna FALSE.
   */
  public function testSendTestCampaignNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('email_campaign')
      ->willReturn($storage);

    $result = $this->service->sendTest(999, 'test@example.com');

    $this->assertFalse($result);
  }

}
