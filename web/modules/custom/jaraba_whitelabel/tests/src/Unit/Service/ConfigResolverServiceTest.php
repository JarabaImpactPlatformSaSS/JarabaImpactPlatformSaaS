<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whitelabel\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_whitelabel\Entity\WhitelabelConfig;
use Drupal\jaraba_whitelabel\Service\ConfigResolverService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Unit tests for ConfigResolverService.
 *
 * @group jaraba_whitelabel
 * @coversDefaultClass \Drupal\jaraba_whitelabel\Service\ConfigResolverService
 */
class ConfigResolverServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected ConfigResolverService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked request stack.
   */
  protected RequestStack&MockObject $requestStack;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked whitelabel config storage.
   */
  protected EntityStorageInterface&MockObject $configStorage;

  /**
   * Mocked custom domain storage.
   */
  protected EntityStorageInterface&MockObject $domainStorage;

  /**
   * Mocked request.
   */
  protected Request&MockObject $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->configStorage = $this->createMock(EntityStorageInterface::class);
    $this->domainStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['whitelabel_config', $this->configStorage],
        ['custom_domain', $this->domainStorage],
      ]);

    $this->request = $this->createMock(Request::class);
    $this->requestStack->method('getCurrentRequest')
      ->willReturn($this->request);

    $this->service = new ConfigResolverService(
      $this->entityTypeManager,
      $this->requestStack,
      $this->logger,
    );
  }

  /**
   * Tests resolving config by explicit tenant ID.
   *
   * @covers ::resolveConfig
   */
  public function testResolveConfigByTenantId(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([42 => 42]);

    $this->configStorage->method('getQuery')->willReturn($query);

    $configEntity = $this->createMock(WhitelabelConfig::class);
    $configEntity->method('id')->willReturn(42);
    $configEntity->method('get')->willReturnCallback(function (string $field) {
      $map = [
        'config_key' => (object) ['value' => 'tenant_5_config'],
        'tenant_id' => (object) ['target_id' => 5],
        'logo_url' => (object) ['value' => 'https://example.com/logo.png'],
        'favicon_url' => (object) ['value' => 'https://example.com/favicon.ico'],
        'company_name' => (object) ['value' => 'Acme Corp'],
        'primary_color' => (object) ['value' => '#FF8C42'],
        'secondary_color' => (object) ['value' => '#2D6A4F'],
        'custom_css' => (object) ['value' => '.header { color: red; }'],
        'custom_footer_html' => (object) ['value' => '<p>Footer</p>'],
        'hide_powered_by' => (object) ['value' => 1],
        'config_status' => (object) ['value' => 'active'],
      ];
      return $map[$field] ?? (object) ['value' => NULL];
    });

    $this->configStorage->method('load')
      ->with(42)
      ->willReturn($configEntity);

    $result = $this->service->resolveConfig(5);

    $this->assertIsArray($result);
    $this->assertSame(42, $result['id']);
    $this->assertSame('tenant_5_config', $result['config_key']);
    $this->assertSame(5, $result['tenant_id']);
    $this->assertSame('https://example.com/logo.png', $result['logo_url']);
    $this->assertSame('Acme Corp', $result['company_name']);
    $this->assertSame('#FF8C42', $result['primary_color']);
    $this->assertSame('#2D6A4F', $result['secondary_color']);
    $this->assertTrue($result['hide_powered_by']);
    $this->assertSame('active', $result['config_status']);
  }

  /**
   * Tests that resolving config for non-existent tenant returns NULL.
   *
   * @covers ::resolveConfig
   */
  public function testResolveConfigNotFound(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->configStorage->method('getQuery')->willReturn($query);

    $result = $this->service->resolveConfig(999);
    $this->assertNull($result);
  }

  /**
   * Tests resolving config by domain via custom_domain entity lookup.
   *
   * @covers ::getConfigByDomain
   */
  public function testGetConfigByDomain(): void {
    // Domain lookup query.
    $domainQuery = $this->createMock(QueryInterface::class);
    $domainQuery->method('accessCheck')->willReturnSelf();
    $domainQuery->method('condition')->willReturnSelf();
    $domainQuery->method('range')->willReturnSelf();
    $domainQuery->method('execute')->willReturn([10 => 10]);

    $this->domainStorage->method('getQuery')->willReturn($domainQuery);

    // Domain entity.
    $tenantIdField = new \stdClass();
    $tenantIdField->target_id = 7;

    $domainEntity = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $domainEntity->method('get')->willReturnMap([
      ['tenant_id', $tenantIdField],
    ]);

    $this->domainStorage->method('load')
      ->with(10)
      ->willReturn($domainEntity);

    // Config lookup query (for the resolved tenant ID).
    $configQuery = $this->createMock(QueryInterface::class);
    $configQuery->method('accessCheck')->willReturnSelf();
    $configQuery->method('condition')->willReturnSelf();
    $configQuery->method('range')->willReturnSelf();
    $configQuery->method('execute')->willReturn([50 => 50]);

    $this->configStorage->method('getQuery')->willReturn($configQuery);

    $configEntity = $this->createMock(WhitelabelConfig::class);
    $configEntity->method('id')->willReturn(50);
    $configEntity->method('get')->willReturnCallback(function (string $field) {
      $map = [
        'config_key' => (object) ['value' => 'custom_domain_config'],
        'tenant_id' => (object) ['target_id' => 7],
        'logo_url' => (object) ['value' => 'https://custom.example.com/logo.png'],
        'favicon_url' => (object) ['value' => NULL],
        'company_name' => (object) ['value' => 'Custom Inc'],
        'primary_color' => (object) ['value' => '#333'],
        'secondary_color' => (object) ['value' => '#666'],
        'custom_css' => (object) ['value' => ''],
        'custom_footer_html' => (object) ['value' => ''],
        'hide_powered_by' => (object) ['value' => 0],
        'config_status' => (object) ['value' => 'active'],
      ];
      return $map[$field] ?? (object) ['value' => NULL];
    });

    $this->configStorage->method('load')
      ->with(50)
      ->willReturn($configEntity);

    $result = $this->service->getConfigByDomain('custom.example.com');

    $this->assertIsArray($result);
    $this->assertSame(7, $result['tenant_id']);
    $this->assertSame('Custom Inc', $result['company_name']);
  }

  /**
   * Tests that getConfigByDomain returns NULL when domain is not found.
   *
   * @covers ::getConfigByDomain
   */
  public function testGetConfigByDomainNotFound(): void {
    $domainQuery = $this->createMock(QueryInterface::class);
    $domainQuery->method('accessCheck')->willReturnSelf();
    $domainQuery->method('condition')->willReturnSelf();
    $domainQuery->method('range')->willReturnSelf();
    $domainQuery->method('execute')->willReturn([]);

    $this->domainStorage->method('getQuery')->willReturn($domainQuery);

    $result = $this->service->getConfigByDomain('unknown.example.com');
    $this->assertNull($result);
  }

  /**
   * Tests isWhitelabeled returns TRUE when request has whitelabel_config.
   *
   * @covers ::isWhitelabeled
   */
  public function testIsWhitelabeled(): void {
    $attributes = new \Symfony\Component\HttpFoundation\ParameterBag([
      'whitelabel_config' => ['tenant_id' => 5, 'company_name' => 'Test'],
    ]);

    $this->request->attributes = $attributes;

    $this->assertTrue($this->service->isWhitelabeled());
  }

  /**
   * Tests isWhitelabeled returns FALSE when no whitelabel_config on request.
   *
   * @covers ::isWhitelabeled
   */
  public function testIsWhitelabeledFalse(): void {
    $attributes = new \Symfony\Component\HttpFoundation\ParameterBag([]);
    $this->request->attributes = $attributes;

    $this->assertFalse($this->service->isWhitelabeled());
  }

  /**
   * Tests isWhitelabeled returns FALSE when no request available.
   *
   * @covers ::isWhitelabeled
   */
  public function testIsWhitelabeledNoRequest(): void {
    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')->willReturn(NULL);

    $service = new ConfigResolverService(
      $this->entityTypeManager,
      $requestStack,
      $this->logger,
    );

    $this->assertFalse($service->isWhitelabeled());
  }

  /**
   * Tests that resolveConfig catches exceptions and logs them.
   *
   * @covers ::resolveConfig
   */
  public function testResolveConfigExceptionIsLogged(): void {
    $this->configStorage->method('getQuery')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error resolving whitelabel config'),
        $this->callback(function (array $ctx): bool {
          return $ctx['@message'] === 'Storage error';
        })
      );

    $result = $this->service->resolveConfig(1);
    $this->assertNull($result);
  }

  /**
   * Tests resolveConfig with NULL tenant ID falls back to request domain.
   *
   * @covers ::resolveConfig
   */
  public function testResolveConfigAutoDetectsFromRequest(): void {
    $this->request->method('getHost')
      ->willReturn('unknown-host.example.com');

    // Domain lookup will find nothing.
    $domainQuery = $this->createMock(QueryInterface::class);
    $domainQuery->method('accessCheck')->willReturnSelf();
    $domainQuery->method('condition')->willReturnSelf();
    $domainQuery->method('range')->willReturnSelf();
    $domainQuery->method('execute')->willReturn([]);

    $this->domainStorage->method('getQuery')->willReturn($domainQuery);

    $result = $this->service->resolveConfig(NULL);
    $this->assertNull($result);
  }

}
