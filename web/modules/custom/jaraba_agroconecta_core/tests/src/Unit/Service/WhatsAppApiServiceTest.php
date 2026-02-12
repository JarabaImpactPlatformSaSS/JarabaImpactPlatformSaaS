<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agroconecta_core\Service\WhatsAppApiService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para WhatsAppApiService.
 *
 * Verifica verificacion de webhook, formateo de numeros de telefono,
 * plantillas soportadas, envio de mensajes y constantes de API.
 *
 * @coversDefaultClass \Drupal\jaraba_agroconecta_core\Service\WhatsAppApiService
 * @group jaraba_agroconecta_core
 */
class WhatsAppApiServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   */
  private WhatsAppApiService $service;

  /**
   * Mock del HTTP client.
   */
  private ClientInterface&MockObject $httpClient;

  /**
   * Mock del config factory.
   */
  private ConfigFactoryInterface&MockObject $configFactory;

  /**
   * Mock del entity type manager.
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * Mock de la configuracion inmutable.
   */
  private ImmutableConfig&MockObject $config;

  /**
   * Mock del storage de mensajes WhatsApp.
   */
  private EntityStorageInterface&MockObject $whatsappMessageStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->whatsappMessageStorage = $this->createMock(EntityStorageInterface::class);

    // Configure default config mock.
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->config->method('get')->willReturnMap([
      ['whatsapp_phone_number_id', '123456789'],
      ['whatsapp_access_token', 'test_access_token_abc123'],
      ['whatsapp_app_secret', 'test_app_secret_xyz789'],
    ]);

    $this->configFactory->method('get')
      ->with('jaraba_agroconecta_core.whatsapp')
      ->willReturn($this->config);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityTypeId): EntityStorageInterface {
        return match ($entityTypeId) {
          'whatsapp_message_agro' => $this->whatsappMessageStorage,
          default => throw new \InvalidArgumentException("Unexpected entity type: $entityTypeId"),
        };
      });

    $this->service = new WhatsAppApiService(
      $this->httpClient,
      $this->configFactory,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  // =========================================================================
  // WEBHOOK SIGNATURE VERIFICATION TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testVerifyWebhookSignatureValid(): void {
    $payload = '{"entry":[{"changes":[]}]}';
    $secret = 'test_app_secret_xyz789';
    $expectedHash = hash_hmac('sha256', $payload, $secret);
    $signature = 'sha256=' . $expectedHash;

    $result = $this->service->verifyWebhookSignature($payload, $signature);

    $this->assertTrue($result, 'Valid HMAC signature should return TRUE');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testVerifyWebhookSignatureInvalid(): void {
    $payload = '{"entry":[{"changes":[]}]}';
    $signature = 'sha256=invalid_signature_that_does_not_match';

    $result = $this->service->verifyWebhookSignature($payload, $signature);

    $this->assertFalse($result, 'Invalid HMAC signature should return FALSE');
  }

  // =========================================================================
  // PHONE NUMBER FORMATTING TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testFormatPhoneNumberAddsCountryCode(): void {
    $result = $this->service->formatPhoneNumber('612345678');

    $this->assertSame('+34612345678', $result, 'Local number should get +34 country code prepended');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testFormatPhoneNumberAlreadyFormatted(): void {
    $result = $this->service->formatPhoneNumber('+34612345678');

    $this->assertSame('+34612345678', $result, 'Already formatted number should remain unchanged');
  }

  // =========================================================================
  // SUPPORTED TEMPLATES TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSupportedTemplatesContainsCartReminder(): void {
    $reflection = new \ReflectionClass(WhatsAppApiService::class);
    $constant = $reflection->getConstant('SUPPORTED_TEMPLATES');

    $this->assertIsArray($constant);
    $this->assertContains('cart_reminder', $constant, 'SUPPORTED_TEMPLATES should include cart_reminder');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSupportedTemplatesContainsOrderConfirmation(): void {
    $reflection = new \ReflectionClass(WhatsAppApiService::class);
    $constant = $reflection->getConstant('SUPPORTED_TEMPLATES');

    $this->assertIsArray($constant);
    $this->assertContains('order_confirmation', $constant, 'SUPPORTED_TEMPLATES should include order_confirmation');
  }

  // =========================================================================
  // HANDLE INCOMING MESSAGE TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testHandleIncomingMessageEmptyPayload(): void {
    $result = $this->service->handleIncomingMessage([]);

    $this->assertArrayHasKey('processed', $result);
    $this->assertFalse($result['processed'], 'Empty payload should not be processed');
  }

  // =========================================================================
  // SEND TEXT MESSAGE TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSendTextMessageReturnsStructure(): void {
    // Mock HTTP response from WhatsApp Cloud API.
    $responseBody = json_encode([
      'messaging_product' => 'whatsapp',
      'contacts' => [['wa_id' => '34612345678']],
      'messages' => [['id' => 'wamid.HBgNMzQ2MTIzNDU2NzgVAgA']],
    ]);

    $response = new Response(200, [], $responseBody);
    $this->httpClient->method('request')
      ->willReturn($response);

    $result = $this->service->sendTextMessage('+34612345678', 'Hola, tu pedido ha sido enviado');

    $this->assertArrayHasKey('success', $result);
    $this->assertArrayHasKey('message_id', $result);
    $this->assertTrue($result['success'], 'Successful send should return success=TRUE');
    $this->assertNotEmpty($result['message_id'], 'Successful send should include a message_id');
  }

  // =========================================================================
  // SEND TEMPLATE MESSAGE TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testSendTemplateMessageInvalidTemplate(): void {
    $result = $this->service->sendTemplateMessage(
      '+34612345678',
      'nonexistent_template',
      ['param1' => 'value1'],
    );

    $this->assertArrayHasKey('success', $result);
    $this->assertFalse($result['success'], 'Invalid template should return success=FALSE');
    $this->assertArrayHasKey('error', $result);
    $this->assertStringContainsString('Unsupported template', $result['error']);
  }

  // =========================================================================
  // API CONSTANT TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testApiBaseUrlConstant(): void {
    $reflection = new \ReflectionClass(WhatsAppApiService::class);
    $constant = $reflection->getConstant('API_BASE_URL');

    $this->assertIsString($constant);
    $this->assertStringContainsString('graph.facebook.com', $constant, 'API_BASE_URL should point to Meta Graph API');
  }

}
