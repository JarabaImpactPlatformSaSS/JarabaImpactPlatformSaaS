<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_whatsapp\Entity\WaConversationInterface;
use Drupal\jaraba_whatsapp\Service\WhatsAppApiService;
use Drupal\jaraba_whatsapp\Service\WhatsAppConversationService;
use Drupal\jaraba_whatsapp\Service\WhatsAppEscalationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for WhatsAppEscalationService.
 *
 * @group jaraba_whatsapp
 * @coversDefaultClass \Drupal\jaraba_whatsapp\Service\WhatsAppEscalationService
 */
class WhatsAppEscalationServiceTest extends TestCase {

  protected WhatsAppEscalationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $conversationService = $this->createMock(WhatsAppConversationService::class);
    $apiService = $this->createMock(WhatsAppApiService::class);
    $mailManager = $this->createMock(MailManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new WhatsAppEscalationService(
      $entityTypeManager,
      $conversationService,
      $apiService,
      $mailManager,
      $logger,
    );
  }

  /**
   * Tests multimedia auto-escalation.
   */
  public function testMultimediaEscalation(): void {
    $conversation = $this->createMock(WaConversationInterface::class);
    $conversation->method('getMessageCount')->willReturn(1);

    $result = $this->service->checkAutoEscalation($conversation, 'some text', 'image');

    self::assertTrue($result['escalate']);
    self::assertStringContainsString('multimedia', $result['reason']);
  }

  /**
   * Tests keyword escalation.
   */
  public function testKeywordEscalation(): void {
    $conversation = $this->createMock(WaConversationInterface::class);
    $conversation->method('getMessageCount')->willReturn(1);

    $result = $this->service->checkAutoEscalation($conversation, 'Tengo una queja sobre el servicio', 'text');

    self::assertTrue($result['escalate']);
    self::assertStringContainsString('queja', $result['reason']);
  }

  /**
   * Tests normal text does not escalate.
   */
  public function testNoEscalationForNormalText(): void {
    $conversation = $this->createMock(WaConversationInterface::class);
    $conversation->method('getMessageCount')->willReturn(2);

    $result = $this->service->checkAutoEscalation($conversation, 'Hola, me interesa el programa', 'text');

    self::assertFalse($result['escalate']);
  }

  /**
   * Tests long conversation escalation.
   */
  public function testLongConversationEscalation(): void {
    $conversation = $this->createMock(WaConversationInterface::class);
    $conversation->method('getMessageCount')->willReturn(10);

    $result = $this->service->checkAutoEscalation($conversation, 'otra pregunta', 'text');

    self::assertTrue($result['escalate']);
    self::assertStringContainsString('sin conversion', $result['reason']);
  }

}
