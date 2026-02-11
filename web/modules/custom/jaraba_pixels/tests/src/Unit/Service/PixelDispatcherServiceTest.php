<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_analytics\Service\ConsentService;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\jaraba_pixels\Service\EventMapperService;
use Drupal\jaraba_pixels\Service\PixelDispatcherService;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PixelDispatcherService.
 *
 * @group jaraba_pixels
 * @coversDefaultClass \Drupal\jaraba_pixels\Service\PixelDispatcherService
 */
class PixelDispatcherServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_pixels\Service\PixelDispatcherService
   */
  protected PixelDispatcherService $service;

  /**
   * Mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * Mocked event mapper service.
   *
   * @var \Drupal\jaraba_pixels\Service\EventMapperService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventMapper;

  /**
   * Mocked credential manager service.
   *
   * @var \Drupal\jaraba_pixels\Service\CredentialManagerService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $credentialManager;

  /**
   * Mocked consent service.
   *
   * @var \Drupal\jaraba_analytics\Service\ConsentService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $consentService;

  /**
   * Mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mocked HTTP client.
   *
   * @var \GuzzleHttp\Client|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * Mocked insert query for the log table.
   *
   * @var \Drupal\Core\Database\Query\Insert|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logInsertQuery;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->eventMapper = $this->createMock(EventMapperService::class);
    $this->credentialManager = $this->createMock(CredentialManagerService::class);
    $this->consentService = $this->createMock(ConsentService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->httpClient = $this->createMock(Client::class);

    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')
      ->with('jaraba_pixels')
      ->willReturn($this->logger);

    // Set up the insert query mock for the log table.
    $this->logInsertQuery = $this->createMock(Insert::class);
    $this->logInsertQuery->method('fields')->willReturnSelf();
    $this->logInsertQuery->method('values')->willReturnSelf();
    $this->logInsertQuery->method('execute')->willReturn(1);

    $this->database->method('insert')
      ->with('pixel_event_log')
      ->willReturn($this->logInsertQuery);

    $this->service = new PixelDispatcherService(
      $this->database,
      $this->eventMapper,
      $this->credentialManager,
      $this->consentService,
      $loggerFactory,
      $this->httpClient,
    );
  }

  /**
   * Creates a mock analytics event entity.
   *
   * @param array $fields
   *   Array of field name => value pairs.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   Mocked entity.
   */
  protected function createMockEvent(array $fields): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('analytics_event');

    $entity->method('hasField')->willReturnCallback(
      function (string $field_name) use ($fields) {
        return isset($fields[$field_name]);
      }
    );

    $entity->method('get')->willReturnCallback(
      function (string $field_name) use ($fields) {
        $fieldItem = new \stdClass();
        $fieldItem->value = $fields[$field_name] ?? NULL;
        return $fieldItem;
      }
    );

    return $entity;
  }

  /**
   * Tests that dispatch skips when no marketing consent.
   *
   * @covers ::dispatch
   */
  public function testDispatchSkipsWhenNoMarketingConsent(): void {
    $entity = $this->createMockEvent([
      'event_id' => 'evt-001',
      'event_type' => 'page_view',
      'tenant_id' => 1,
      'visitor_id' => 'visitor-abc',
      'session_id' => 'session-xyz',
      'page_url' => 'https://example.com/page',
      'page_title' => 'Test Page',
      'referrer' => '',
      'user_agent' => 'Mozilla/5.0',
      'ip_hash' => '192.168.1.1',
      'created' => time(),
      'value' => NULL,
      'currency' => 'EUR',
      'items' => '',
      'custom_data' => '',
    ]);

    $this->consentService->expects($this->once())
      ->method('hasConsent')
      ->with('visitor-abc', 'marketing')
      ->willReturn(FALSE);

    // Verify logEvent is called - the insert with 'skipped' status.
    $this->logInsertQuery->expects($this->once())
      ->method('fields')
      ->with($this->callback(function ($fields) {
        return isset($fields['status']) && $fields['status'] === 'skipped';
      }))
      ->willReturnSelf();

    $this->service->dispatch($entity);
  }

  /**
   * Tests that dispatch returns early when no tenant_id.
   *
   * @covers ::dispatch
   */
  public function testDispatchSkipsWhenNoTenantId(): void {
    $entity = $this->createMockEvent([
      'event_id' => 'evt-002',
      'event_type' => 'page_view',
      'tenant_id' => 0,
      'visitor_id' => 'visitor-abc',
      'session_id' => 'session-xyz',
      'page_url' => '',
      'page_title' => '',
      'referrer' => '',
      'user_agent' => '',
      'ip_hash' => '',
      'created' => time(),
      'value' => NULL,
      'currency' => 'EUR',
      'items' => '',
      'custom_data' => '',
    ]);

    // Consent should never be checked because we return early.
    $this->consentService->expects($this->never())
      ->method('hasConsent');

    // Credential manager should never be called.
    $this->credentialManager->expects($this->never())
      ->method('getEnabledCredentials');

    $this->service->dispatch($entity);
  }

  /**
   * Tests that dispatch calls all enabled platforms.
   *
   * @covers ::dispatch
   */
  public function testDispatchCallsAllEnabledPlatforms(): void {
    $entity = $this->createMockEvent([
      'event_id' => 'evt-003',
      'event_type' => 'purchase',
      'tenant_id' => 1,
      'visitor_id' => 'visitor-abc',
      'session_id' => 'session-xyz',
      'page_url' => 'https://example.com/checkout',
      'page_title' => 'Checkout',
      'referrer' => '',
      'user_agent' => 'Mozilla/5.0',
      'ip_hash' => '10.0.0.1',
      'created' => time(),
      'value' => 99.99,
      'currency' => 'EUR',
      'items' => '',
      'custom_data' => '',
    ]);

    $this->consentService->method('hasConsent')->willReturn(TRUE);

    $this->credentialManager->expects($this->once())
      ->method('getEnabledCredentials')
      ->with(1)
      ->willReturn([
        'meta' => [
          'pixel_id' => 'px-123',
          'access_token' => 'token-meta',
        ],
        'google' => [
          'pixel_id' => 'G-ABCDEF',
          'api_secret' => 'secret-google',
        ],
      ]);

    $this->eventMapper->method('mapEvent')
      ->willReturnMap([
        ['purchase', 'meta', 'Purchase'],
        ['purchase', 'google', 'purchase'],
      ]);

    $this->eventMapper->method('formatMetaPayload')
      ->willReturn([
        'event_name' => 'Purchase',
        'event_time' => time(),
        'event_id' => 'evt-003',
        'event_source_url' => 'https://example.com/checkout',
        'action_source' => 'website',
        'user_data' => [],
        'custom_data' => [],
      ]);

    $this->eventMapper->method('formatGooglePayload')
      ->willReturn([
        'client_id' => 'visitor-abc',
        'events' => [
          ['name' => 'purchase', 'params' => []],
        ],
      ]);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(200);

    // HTTP client should be called twice (once for Meta, once for Google).
    $this->httpClient->expects($this->exactly(2))
      ->method('post')
      ->willReturn($response);

    $this->service->dispatch($entity);
  }

  /**
   * Tests sendToMeta returns success on HTTP 200.
   *
   * Uses reflection to access the protected method.
   *
   * @covers ::sendToMeta
   */
  public function testSendToMetaReturnsSuccessOn200(): void {
    $this->eventMapper->method('formatMetaPayload')
      ->willReturn([
        'event_name' => 'Purchase',
        'event_time' => time(),
        'event_id' => 'evt-meta-1',
        'event_source_url' => 'https://example.com',
        'action_source' => 'website',
        'user_data' => [],
        'custom_data' => [],
      ]);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(200);

    $this->httpClient->expects($this->once())
      ->method('post')
      ->willReturn($response);

    $reflection = new \ReflectionMethod($this->service, 'sendToMeta');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, [
      'pixel_id' => 'px-123',
      'access_token' => 'token-abc',
    ], [
      'event_type' => 'purchase',
      'timestamp' => time(),
      'page_url' => 'https://example.com',
    ], 'evt-meta-1');

    $this->assertTrue($result['success']);
    $this->assertSame(200, $result['code']);
    $this->assertNull($result['error']);
  }

  /**
   * Tests sendToMeta returns error when pixel_id is empty.
   *
   * @covers ::sendToMeta
   */
  public function testSendToMetaReturnsMissingCredentials(): void {
    $reflection = new \ReflectionMethod($this->service, 'sendToMeta');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, [
      'pixel_id' => '',
      'access_token' => 'token-abc',
    ], [
      'event_type' => 'purchase',
    ], 'evt-meta-2');

    $this->assertFalse($result['success']);
    $this->assertSame(0, $result['code']);
    $this->assertSame('Missing credentials', $result['error']);
  }

  /**
   * Tests sendToGoogle returns success on HTTP 204.
   *
   * Google Measurement Protocol returns 204 No Content on success.
   *
   * @covers ::sendToGoogle
   */
  public function testSendToGoogleReturnsSuccessOn204(): void {
    $this->eventMapper->method('formatGooglePayload')
      ->willReturn([
        'client_id' => 'visitor-xyz',
        'events' => [
          ['name' => 'purchase', 'params' => []],
        ],
      ]);

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(204);

    $this->httpClient->expects($this->once())
      ->method('post')
      ->willReturn($response);

    $reflection = new \ReflectionMethod($this->service, 'sendToGoogle');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, [
      'pixel_id' => 'G-ABCDEF',
      'api_secret' => 'secret-123',
    ], [
      'event_type' => 'purchase',
      'visitor_id' => 'visitor-xyz',
      'page_url' => 'https://example.com',
      'page_title' => 'Test',
    ]);

    $this->assertTrue($result['success']);
    $this->assertSame(204, $result['code']);
    $this->assertNull($result['error']);
  }

}
