<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Client;

use Drupal\jaraba_pixels\Client\MetaCapiClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for MetaCapiClient.
 *
 * Tests the Meta Conversions API HTTP client including successful
 * event sending, batch operations, error handling, and credential
 * verification. Uses GuzzleHttp MockHandler for realistic HTTP mocking.
 *
 * @coversDefaultClass \Drupal\jaraba_pixels\Client\MetaCapiClient
 * @group jaraba_pixels
 */
class MetaCapiClientTest extends TestCase {

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Logger factory stub.
   */
  protected object $loggerFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);

    // MetaCapiClient constructor expects a logger factory with ->get().
    $this->loggerFactory = new class($this->logger) {

      private LoggerInterface $logger;

      public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
      }

      public function get(string $channel): LoggerInterface {
        return $this->logger;
      }

    };
  }

  /**
   * Creates a MetaCapiClient with a MockHandler for the HTTP client.
   *
   * @param array $queue
   *   Queue of Response/Exception objects for the MockHandler.
   * @param array &$history
   *   Reference to capture request history.
   *
   * @return \Drupal\jaraba_pixels\Client\MetaCapiClient
   *   Configured client instance.
   */
  protected function createClientWithMockHandler(array $queue, array &$history = []): MetaCapiClient {
    $mock = new MockHandler($queue);
    $handlerStack = HandlerStack::create($mock);
    $handlerStack->push(Middleware::history($history));

    $httpClient = new GuzzleClient(['handler' => $handlerStack]);

    return new MetaCapiClient($httpClient, $this->loggerFactory);
  }

  /**
   * Tests sendEvent returns success for a 200 response.
   *
   * @covers ::sendEvent
   */
  public function testSendEventReturnsSuccessOn200(): void {
    $responseBody = json_encode(['events_received' => 1, 'messages' => []]);
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], $responseBody)],
      $history,
    );

    $result = $client->sendEvent('PIXEL123', 'test-token', [
      'event_name' => 'Purchase',
      'event_time' => 1700000000,
    ]);

    $this->assertTrue($result['success']);
    $this->assertSame(200, $result['code']);
    $this->assertNull($result['error']);
    $this->assertIsArray($result['response']);
    $this->assertSame(1, $result['response']['events_received']);

    // Verify the request was sent to the correct URL.
    $this->assertCount(1, $history);
    $requestUri = (string) $history[0]['request']->getUri();
    $this->assertStringContainsString('PIXEL123/events', $requestUri);
    $this->assertStringContainsString('access_token=test-token', $requestUri);
  }

  /**
   * Tests sendEvent includes test_event_code when provided.
   *
   * @covers ::sendEvent
   */
  public function testSendEventIncludesTestEventCode(): void {
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], json_encode([]))],
      $history,
    );

    $client->sendEvent(
      'PIXEL123',
      'test-token',
      ['event_name' => 'PageView'],
      'TEST_CODE_42',
    );

    // Parse the request body to verify test_event_code.
    $body = json_decode((string) $history[0]['request']->getBody(), TRUE);
    $this->assertArrayHasKey('test_event_code', $body);
    $this->assertSame('TEST_CODE_42', $body['test_event_code']);
  }

  /**
   * Tests sendEvent omits test_event_code when NULL.
   *
   * @covers ::sendEvent
   */
  public function testSendEventOmitsTestEventCodeWhenNull(): void {
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], json_encode([]))],
      $history,
    );

    $client->sendEvent('PIXEL123', 'test-token', ['event_name' => 'PageView']);

    $body = json_decode((string) $history[0]['request']->getBody(), TRUE);
    $this->assertArrayNotHasKey('test_event_code', $body);
  }

  /**
   * Tests sendEvent handles RequestException with error body.
   *
   * @covers ::sendEvent
   */
  public function testSendEventHandlesRequestExceptionWithErrorBody(): void {
    $errorBody = json_encode(['error' => ['message' => 'Invalid pixel ID']]);
    $client = $this->createClientWithMockHandler([
      new RequestException(
        'Client error: 400',
        new GuzzleRequest('POST', 'https://graph.facebook.com'),
        new Response(400, [], $errorBody),
      ),
    ]);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Meta CAPI request failed'),
        $this->anything(),
      );

    $result = $client->sendEvent('BAD_PIXEL', 'bad-token', ['event_name' => 'PageView']);

    $this->assertFalse($result['success']);
    $this->assertSame(400, $result['code']);
    $this->assertSame('Invalid pixel ID', $result['error']);
  }

  /**
   * Tests sendEvent handles RequestException without response.
   *
   * @covers ::sendEvent
   */
  public function testSendEventHandlesExceptionWithoutResponse(): void {
    $client = $this->createClientWithMockHandler([
      new RequestException(
        'Connection timeout',
        new GuzzleRequest('POST', 'https://graph.facebook.com'),
      ),
    ]);

    $result = $client->sendEvent('PIXEL123', 'test-token', ['event_name' => 'PageView']);

    $this->assertFalse($result['success']);
    $this->assertSame(0, $result['code']);
    $this->assertSame('Connection timeout', $result['error']);
  }

  /**
   * Tests sendBatch sends multiple events in one request.
   *
   * @covers ::sendBatch
   */
  public function testSendBatchSendsMultipleEvents(): void {
    $responseBody = json_encode(['events_received' => 3]);
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], $responseBody)],
      $history,
    );

    $events = [
      ['event_name' => 'PageView', 'event_time' => 1700000000],
      ['event_name' => 'Lead', 'event_time' => 1700000001],
      ['event_name' => 'Purchase', 'event_time' => 1700000002],
    ];

    $result = $client->sendBatch('PIXEL123', 'test-token', $events);

    $this->assertTrue($result['success']);
    $this->assertSame(200, $result['code']);
    $this->assertSame(3, $result['events_received']);
    $this->assertNull($result['error']);

    // Verify all 3 events were included in the request body.
    $body = json_decode((string) $history[0]['request']->getBody(), TRUE);
    $this->assertCount(3, $body['data']);
  }

  /**
   * Tests sendBatch handles error and reports zero events received.
   *
   * @covers ::sendBatch
   */
  public function testSendBatchHandlesErrorWithZeroEventsReceived(): void {
    $client = $this->createClientWithMockHandler([
      new RequestException(
        'Server error',
        new GuzzleRequest('POST', 'https://graph.facebook.com'),
        new Response(500, [], ''),
      ),
    ]);

    $result = $client->sendBatch('PIXEL123', 'test-token', [
      ['event_name' => 'PageView'],
    ]);

    $this->assertFalse($result['success']);
    $this->assertSame(500, $result['code']);
    $this->assertSame(0, $result['events_received']);
    $this->assertStringContainsString('Server error', $result['error']);
  }

  /**
   * Tests verifyCredentials returns valid on success.
   *
   * @covers ::verifyCredentials
   */
  public function testVerifyCredentialsReturnsValidOnSuccess(): void {
    $responseBody = json_encode(['events_received' => 1]);
    $client = $this->createClientWithMockHandler([
      new Response(200, [], $responseBody),
    ]);

    $result = $client->verifyCredentials('PIXEL123', 'valid-token');

    $this->assertTrue($result['valid']);
    $this->assertStringContainsString('verificada', $result['message']);
    $this->assertIsArray($result['details']);
  }

  /**
   * Tests verifyCredentials returns invalid on failure.
   *
   * @covers ::verifyCredentials
   */
  public function testVerifyCredentialsReturnsInvalidOnFailure(): void {
    $client = $this->createClientWithMockHandler([
      new RequestException(
        'Unauthorized',
        new GuzzleRequest('POST', 'https://graph.facebook.com'),
        new Response(401, [], json_encode([
          'error' => ['message' => 'Invalid OAuth access token'],
        ])),
      ),
    ]);

    $result = $client->verifyCredentials('PIXEL123', 'bad-token');

    $this->assertFalse($result['valid']);
    $this->assertNotEmpty($result['message']);
    $this->assertIsArray($result['details']);
  }

  /**
   * Tests sendEvent uses correct API base URL with pixel ID.
   *
   * @covers ::sendEvent
   */
  public function testSendEventUsesCorrectApiUrl(): void {
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], json_encode([]))],
      $history,
    );

    $client->sendEvent('MY_PIXEL', 'token', ['event_name' => 'PageView']);

    $requestUri = (string) $history[0]['request']->getUri();
    $this->assertStringContainsString('graph.facebook.com/v18.0/MY_PIXEL/events', $requestUri);
  }

  /**
   * Tests sendBatch includes test_event_code when provided.
   *
   * @covers ::sendBatch
   */
  public function testSendBatchIncludesTestEventCode(): void {
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], json_encode(['events_received' => 1]))],
      $history,
    );

    $client->sendBatch('PIXEL123', 'token', [['event_name' => 'PageView']], 'BATCH_TEST');

    $body = json_decode((string) $history[0]['request']->getBody(), TRUE);
    $this->assertSame('BATCH_TEST', $body['test_event_code']);
  }

  /**
   * Tests verifyCredentials sends TEST_VERIFY as test_event_code.
   *
   * @covers ::verifyCredentials
   */
  public function testVerifyCredentialsSendsTestEventCode(): void {
    $history = [];
    $client = $this->createClientWithMockHandler(
      [new Response(200, [], json_encode([]))],
      $history,
    );

    $client->verifyCredentials('PIXEL123', 'token');

    $body = json_decode((string) $history[0]['request']->getBody(), TRUE);
    $this->assertSame('TEST_VERIFY', $body['test_event_code']);
  }

}
