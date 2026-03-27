<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Unit;

use Drupal\jaraba_whatsapp\Service\WhatsAppApiService;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for WhatsApp webhook signature verification.
 *
 * @group jaraba_whatsapp
 */
class WhatsAppWebhookControllerTest extends TestCase {

  /**
   * Tests payload parsing extracts correct fields.
   */
  public function testParseIncomingPayload(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn('v21.0');
    $configFactory->method('get')->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);

    $service = new WhatsAppApiService($httpClient, $configFactory, $logger);

    $payload = [
      'object' => 'whatsapp_business_account',
      'entry' => [[
        'changes' => [[
          'value' => [
            'messaging_product' => 'whatsapp',
            'metadata' => [
              'phone_number_id' => 'PHONE123',
            ],
            'contacts' => [['profile' => ['name' => 'Maria']]],
            'messages' => [[
              'from' => '34612345678',
              'id' => 'wamid.ABC123',
              'timestamp' => '1711000000',
              'type' => 'text',
              'text' => ['body' => 'Hola, me interesa el programa'],
            ]],
          ],
        ]],
      ]],
    ];

    $parsed = $service->parseIncomingPayload($payload);

    self::assertSame('34612345678', $parsed['phone']);
    self::assertSame('wamid.ABC123', $parsed['message_id']);
    self::assertSame('Hola, me interesa el programa', $parsed['body']);
    self::assertSame('text', $parsed['type']);
    self::assertSame('Maria', $parsed['name']);
    self::assertSame('PHONE123', $parsed['phone_number_id']);
  }

  /**
   * Tests empty payload returns empty array.
   */
  public function testEmptyPayload(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn('v21.0');
    $configFactory->method('get')->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);

    $service = new WhatsAppApiService($httpClient, $configFactory, $logger);

    $parsed = $service->parseIncomingPayload([]);

    self::assertSame([], $parsed);
  }

  /**
   * Tests phone number formatting.
   */
  public function testPhoneFormatting(): void {
    $httpClient = $this->createMock(ClientInterface::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn('v21.0');
    $configFactory->method('get')->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);

    $service = new WhatsAppApiService($httpClient, $configFactory, $logger);

    self::assertSame('34612345678', $service->formatPhoneNumber('+34 612 345 678'));
    self::assertSame('34612345678', $service->formatPhoneNumber('34612345678'));
    self::assertSame('34612345678', $service->formatPhoneNumber('612345678'));
  }

}
