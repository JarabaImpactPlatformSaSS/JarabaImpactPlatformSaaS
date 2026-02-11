<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\AnalyticsService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Unit tests for the AnalyticsService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\AnalyticsService
 */
class AnalyticsServiceTest extends TestCase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestStack;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked logger factory.
   *
   * @var object|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mocked tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $tenantContext;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_analytics\Service\AnalyticsService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->loggerFactory = new class ($this->logger) {

      private LoggerInterface $logger;

      public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
      }

      public function get(string $channel): LoggerInterface {
        return $this->logger;
      }

    };

    $this->service = new AnalyticsService(
      $this->database,
      $this->entityTypeManager,
      $this->currentUser,
      $this->requestStack,
      $this->cache,
      $this->loggerFactory,
      $this->tenantContext,
    );
  }

  /**
   * Helper: create a mock Request with headers, query, session, and cookies.
   *
   * @param array $options
   *   Optional overrides: 'uri', 'user_agent', 'referer', 'client_ip',
   *   'session', 'query'.
   *
   * @return \Symfony\Component\HttpFoundation\Request|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked request.
   */
  protected function createMockRequest(array $options = []) {
    $request = $this->createMock(Request::class);

    $uri = $options['uri'] ?? 'https://example.com/page';
    $userAgent = $options['user_agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';
    $referer = $options['referer'] ?? NULL;
    $clientIp = $options['client_ip'] ?? '10.0.0.1';

    $headers = new HeaderBag([
      'User-Agent' => $userAgent,
      'Referer' => $referer,
    ]);
    $request->headers = $headers;

    $queryParams = $options['query'] ?? [];
    $request->query = new InputBag($queryParams);

    $cookies = new InputBag();
    $request->cookies = $cookies;

    $request->method('getUri')->willReturn($uri);
    $request->method('getClientIp')->willReturn($clientIp);

    // Session handling.
    $session = $options['session'] ?? $this->createMock(SessionInterface::class);
    $request->method('getSession')->willReturn($session);

    return $request;
  }

  /**
   * Tests that trackEvent creates an entity with correct fields.
   *
   * @covers ::trackEvent
   */
  public function testTrackEventCreatesEntity(): void {
    $session = $this->createMock(SessionInterface::class);
    $session->method('has')
      ->with('jaraba_analytics_session_id')
      ->willReturn(TRUE);
    $session->method('get')
      ->with('jaraba_analytics_session_id')
      ->willReturn('existing-session-id');

    $request = $this->createMockRequest([
      'uri' => 'https://tenant.jaraba.io/productos',
      'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120',
      'referer' => 'https://google.com',
      'client_ip' => '192.168.1.50',
      'session' => $session,
    ]);

    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->currentUser->method('isAuthenticated')->willReturn(TRUE);
    $this->currentUser->method('id')->willReturn(7);

    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    // Mock entity storage to capture create() call.
    $storage = $this->createMock(EntityStorageInterface::class);

    // Simulate the entity having a save() method.
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save'])
      ->getMock();
    $entity->expects($this->once())->method('save');

    $storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['event_type'] === 'click'
          && $values['user_id'] === 7
          && $values['session_id'] === 'existing-session-id'
          && $values['browser'] === 'Chrome'
          && $values['os'] === 'Windows'
          && $values['device_type'] === 'desktop'
          && $values['referrer'] === 'https://google.com'
          && $values['page_url'] === 'https://tenant.jaraba.io/productos';
      }))
      ->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_event')
      ->willReturn($storage);

    $result = $this->service->trackEvent('click', [], 5);

    $this->assertNotNull($result);
  }

  /**
   * Tests that trackPageView passes page_url from the request.
   *
   * @covers ::trackPageView
   */
  public function testTrackPageViewSetsPageUrl(): void {
    $session = $this->createMock(SessionInterface::class);
    $session->method('has')->willReturn(TRUE);
    $session->method('get')->willReturn('sess-abc');

    $request = $this->createMockRequest([
      'uri' => 'https://coop.jaraba.io/dashboard',
      'session' => $session,
      'client_ip' => '10.10.10.10',
    ]);

    $this->requestStack->method('getCurrentRequest')->willReturn($request);
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['save'])
      ->getMock();
    $entity->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['event_type'] === 'page_view'
          && $values['page_url'] === 'https://coop.jaraba.io/dashboard';
      }))
      ->willReturn($entity);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_event')
      ->willReturn($storage);

    $result = $this->service->trackPageView();

    $this->assertNotNull($result);
  }

  /**
   * Tests that trackEvent returns NULL when storage throws an exception.
   *
   * @covers ::trackEvent
   */
  public function testTrackEventReturnsNullOnException(): void {
    $request = $this->createMockRequest();
    $session = $this->createMock(SessionInterface::class);
    $session->method('has')->willReturn(FALSE);
    $request->method('getSession')->willReturn($session);

    $this->requestStack->method('getCurrentRequest')->willReturn($request);
    $this->currentUser->method('isAuthenticated')->willReturn(FALSE);
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('create')
      ->willThrowException(new \RuntimeException('DB connection failed'));

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_event')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error tracking event'),
        $this->arrayHasKey('@message')
      );

    $result = $this->service->trackEvent('page_view', []);

    $this->assertNull($result);
  }

  /**
   * Tests that detectTenantId uses TenantContextService.
   *
   * @covers ::detectTenantId
   */
  public function testDetectTenantIdUsesContextService(): void {
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('id')->willReturn(42);

    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $reflection = new \ReflectionMethod($this->service, 'detectTenantId');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service);

    $this->assertSame(42, $result);
  }

  /**
   * Tests that detectTenantId returns null when no tenant is active.
   *
   * @covers ::detectTenantId
   */
  public function testDetectTenantIdReturnsNullWhenNoTenant(): void {
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $reflection = new \ReflectionMethod($this->service, 'detectTenantId');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service);

    $this->assertNull($result);
  }

  /**
   * Tests that hashIp truncates the last octet before hashing.
   *
   * @covers ::hashIp
   */
  public function testHashIpTruncatesLastOctet(): void {
    $reflection = new \ReflectionMethod($this->service, 'hashIp');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, '192.168.1.100');

    // The method zeroes the last octet: 192.168.1.0
    $expected = hash('sha256', '192.168.1.0');

    $this->assertSame($expected, $result);
  }

  /**
   * Tests that hashIp returns null for null input.
   *
   * @covers ::hashIp
   */
  public function testHashIpReturnsNullForNull(): void {
    $reflection = new \ReflectionMethod($this->service, 'hashIp');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, NULL);

    $this->assertNull($result);
  }

  /**
   * Tests that getSessionId generates a new ID when session lacks the key.
   *
   * @covers ::getSessionId
   */
  public function testGetSessionIdGeneratesNew(): void {
    $session = $this->createMock(SessionInterface::class);
    $session->method('has')
      ->with('jaraba_analytics_session_id')
      ->willReturn(FALSE);

    $session->expects($this->once())
      ->method('set')
      ->with(
        'jaraba_analytics_session_id',
        $this->isType('string')
      );

    $request = $this->createMock(Request::class);
    $request->method('getSession')->willReturn($session);

    // We need to set this on the requestStack used by the service instance.
    // Re-create the service with a fresh requestStack that returns our request.
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn($request);

    $service = new AnalyticsService(
      $this->database,
      $this->entityTypeManager,
      $this->currentUser,
      $requestStack,
      $this->cache,
      $this->loggerFactory,
      $this->tenantContext,
    );

    $reflection = new \ReflectionMethod($service, 'getSessionId');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($service);

    // bin2hex(random_bytes(16)) produces a 32-character hex string.
    $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result);
  }

  /**
   * Tests that parseUserAgent detects mobile devices.
   *
   * @covers ::parseUserAgent
   */
  public function testParseUserAgentDetectsMobile(): void {
    $reflection = new \ReflectionMethod($this->service, 'parseUserAgent');
    $reflection->setAccessible(TRUE);

    $mobileUa = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    $result = $reflection->invoke($this->service, $mobileUa);

    $this->assertIsArray($result);
    $this->assertSame('mobile', $result['device_type']);
    $this->assertArrayHasKey('browser', $result);
    $this->assertArrayHasKey('os', $result);
  }

}
