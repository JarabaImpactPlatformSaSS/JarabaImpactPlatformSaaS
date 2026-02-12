<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\jaraba_email\Service\SendGridClientService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para SendGridClientService.
 *
 * @covers \Drupal\jaraba_email\Service\SendGridClientService
 * @group jaraba_email
 */
class SendGridClientServiceTest extends UnitTestCase {

  protected SendGridClientService $service;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['sendgrid_api_key', NULL],
      ['sendgrid_webhook_key', 'test_webhook_key'],
      ['default_from_email', 'test@example.com'],
      ['default_from_name', 'Test Sender'],
    ]);

    $this->configFactory->method('get')
      ->with('jaraba_email.settings')
      ->willReturn($config);

    $this->service = new SendGridClientService(
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests enviar email sin API key configurada.
   */
  public function testSendEmailWithoutApiKey(): void {
    $result = $this->service->sendEmail('to@example.com', 'Test', '<p>Test</p>');
    $this->assertFalse($result['success']);
    $this->assertEquals('API key not configured', $result['error']);
  }

  /**
   * Tests procesar evento webhook.
   */
  public function testProcessWebhookEvent(): void {
    $event = [
      'event' => 'delivered',
      'email' => 'user@example.com',
      'timestamp' => 1234567890,
    ];

    $result = $this->service->processWebhookEvent($event);
    $this->assertTrue($result['processed']);
    $this->assertEquals('delivered', $result['event']);
    $this->assertEquals('user@example.com', $result['email']);
  }

  /**
   * Tests envio batch vacio.
   */
  public function testSendBatchEmpty(): void {
    $result = $this->service->sendBatch([]);
    $this->assertEquals(0, $result['sent']);
    $this->assertEquals(0, $result['failed']);
  }

  /**
   * Tests validacion de webhook sin clave.
   */
  public function testValidateWebhookWithoutKey(): void {
    // Usar config sin webhook key.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['sendgrid_webhook_key', NULL],
    ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_email.settings')
      ->willReturn($config);

    $service = new SendGridClientService($configFactory, $this->logger);

    $result = $service->validateWebhookSignature('sig', 'ts', 'payload');
    $this->assertFalse($result);
  }

}
