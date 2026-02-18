<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_connector_sdk\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\group\Entity\GroupInterface;
use Drupal\jaraba_connector_sdk\Service\MarketplaceService;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\jaraba_integrations\Entity\ConnectorInstallation;
use Drupal\jaraba_integrations\Service\ConnectorInstallerService;
use Drupal\jaraba_integrations\Service\ConnectorRegistryService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MarketplaceService.
 *
 * Covers certified connector listing, detail retrieval, rating validation,
 * install/uninstall flows, configuration, and status retrieval.
 *
 * @coversDefaultClass \Drupal\jaraba_connector_sdk\Service\MarketplaceService
 * @group jaraba_connector_sdk
 */
class MarketplaceServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected MarketplaceService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock tenant context.
   */
  protected TenantContextService $tenantContext;

  /**
   * Mock connector registry.
   */
  protected ConnectorRegistryService $connectorRegistry;

  /**
   * Mock connector installer.
   */
  protected ConnectorInstallerService $connectorInstaller;

  /**
   * Mock connector entity storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->connectorRegistry = $this->createMock(ConnectorRegistryService::class);
    $this->connectorInstaller = $this->createMock(ConnectorInstallerService::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($this->storage);

    // Set up a mock Drupal container with a state service so that
    // serializeConnector() -> getAverageRating() -> \Drupal::state() works.
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn([]);
    $container = new ContainerBuilder();
    $container->set('state', $state);
    \Drupal::setContainer($container);

    $this->service = new MarketplaceService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->connectorRegistry,
      $this->connectorInstaller,
    );
  }

  /**
   * Creates a mock Connector entity.
   *
   * @param int $id
   *   Connector ID.
   * @param string $name
   *   Connector name.
   * @param array $extra
   *   Extra field values.
   *
   * @return \Drupal\jaraba_integrations\Entity\Connector|\PHPUnit\Framework\MockObject\MockObject
   *   Mock connector.
   */
  protected function createMockConnector(int $id, string $name, array $extra = []): Connector {
    $connector = $this->createMock(Connector::class);
    $connector->method('id')->willReturn($id);
    $connector->method('getName')->willReturn($name);
    $connector->method('getCategory')->willReturn($extra['category'] ?? 'crm');
    $connector->method('getIcon')->willReturn($extra['icon'] ?? 'default');
    $connector->method('getAuthType')->willReturn($extra['auth_type'] ?? 'oauth2');
    $connector->method('getPublishStatus')->willReturn($extra['publish_status'] ?? 'certified');
    $connector->method('getConfigSchema')->willReturn($extra['config_schema'] ?? []);

    $connector->method('get')->willReturnCallback(function (string $field) use ($extra): object {
      $fieldObj = new \stdClass();
      $fieldObj->value = $extra[$field] ?? '';
      return $fieldObj;
    });

    return $connector;
  }

  /**
   * Creates a mock TenantInterface with a group that returns the given ID.
   *
   * @param int $groupId
   *   The group ID to return.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\TenantInterface|\PHPUnit\Framework\MockObject\MockObject
   *   Mock tenant.
   */
  protected function createMockTenant(int $groupId): TenantInterface {
    $group = $this->createMock(GroupInterface::class);
    $group->method('id')->willReturn($groupId);

    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('getGroup')->willReturn($group);

    return $tenant;
  }

  // -----------------------------------------------------------------------
  // getDetail() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::getDetail
   */
  public function testGetDetailReturnsNullForNonexistentConnector(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->getDetail(999);

    $this->assertNull($result);
  }

  /**
   * @covers ::getDetail
   */
  public function testGetDetailReturnsNullForNonConnectorEntity(): void {
    // Storage returns something that is NOT a Connector instance.
    $genericEntity = $this->getMockBuilder(\stdClass::class)->getMock();
    $this->storage->method('load')->with(1)->willReturn($genericEntity);

    $result = $this->service->getDetail(1);

    $this->assertNull($result);
  }

  /**
   * @covers ::getDetail
   */
  public function testGetDetailReturnsFullConnectorData(): void {
    $connector = $this->createMockConnector(5, 'Hubspot CRM', [
      'category' => 'crm',
      'machine_name' => 'hubspot_crm',
      'description' => 'Sync contacts with Hubspot',
      'version' => '2.1.0',
      'provider' => 'Jaraba',
      'install_count' => 42,
      'logo_url' => 'https://example.com/logo.png',
      'auth_type' => 'oauth2',
      'docs_url' => 'https://docs.example.com',
      'supported_events' => '["contact.created","deal.updated"]',
      'config_schema' => ['api_key' => 'string'],
    ]);

    $this->storage->method('load')->with(5)->willReturn($connector);

    $result = $this->service->getDetail(5);

    $this->assertNotNull($result);
    $this->assertEquals(5, $result['id']);
    $this->assertEquals('Hubspot CRM', $result['name']);
    $this->assertArrayHasKey('config_schema', $result);
    $this->assertArrayHasKey('docs_url', $result);
    $this->assertArrayHasKey('supported_events', $result);
  }

  // -----------------------------------------------------------------------
  // rate() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::rate
   */
  public function testRateRejectsBelowMinimum(): void {
    $result = $this->service->rate(1, 10, 0);

    $this->assertFalse($result);
  }

  /**
   * @covers ::rate
   */
  public function testRateRejectsAboveMaximum(): void {
    $result = $this->service->rate(1, 10, 6);

    $this->assertFalse($result);
  }

  /**
   * @covers ::rate
   */
  public function testRateRejectsNonexistentConnector(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->rate(999, 10, 4);

    $this->assertFalse($result);
  }

  // -----------------------------------------------------------------------
  // install() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::install
   */
  public function testInstallReturnsErrorWhenConnectorNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->install(999);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('not found', $result['error']);
  }

  /**
   * @covers ::install
   */
  public function testInstallReturnsErrorWhenNoTenantContext(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    // No tenant available.
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->install(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Tenant', $result['error']);
  }

  /**
   * @covers ::install
   */
  public function testInstallReturnsErrorWhenInstallerFails(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    // Set up tenant context with proper TenantInterface mock.
    $tenant = $this->createMockTenant(42);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);
    $this->connectorInstaller->method('install')->willReturn(NULL);

    $result = $this->service->install(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('failed', $result['error']);
  }

  /**
   * @covers ::install
   */
  public function testInstallReturnsSuccessOnValidInstallation(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    // Set up tenant context with proper TenantInterface mock.
    $tenant = $this->createMockTenant(42);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    // Mock installation entity.
    $installation = $this->createMock(ConnectorInstallation::class);
    $installation->method('id')->willReturn(100);
    $installation->method('getInstallationStatus')->willReturn('active');

    $this->connectorInstaller->method('install')->willReturn($installation);

    $result = $this->service->install(1);

    $this->assertTrue($result['success']);
    $this->assertEquals(100, $result['installation_id']);
    $this->assertEquals('active', $result['status']);
  }

  // -----------------------------------------------------------------------
  // uninstall() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::uninstall
   */
  public function testUninstallReturnsFalseWhenConnectorNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->uninstall(999);

    $this->assertFalse($result);
  }

  /**
   * @covers ::uninstall
   */
  public function testUninstallReturnsFalseWhenNoTenantContext(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->uninstall(1);

    $this->assertFalse($result);
  }

  // -----------------------------------------------------------------------
  // configure() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::configure
   */
  public function testConfigureReturnsFalseWhenConnectorNotFound(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->configure(999, ['key' => 'value']);

    $this->assertFalse($result);
  }

  /**
   * @covers ::configure
   */
  public function testConfigureReturnsFalseWhenNoInstallation(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    // Set up tenant context with proper TenantInterface mock.
    $tenant = $this->createMockTenant(42);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);
    $this->connectorInstaller->method('getInstallation')->willReturn(NULL);

    $result = $this->service->configure(1, ['key' => 'value']);

    $this->assertFalse($result);
  }

  // -----------------------------------------------------------------------
  // getStatus() tests
  // -----------------------------------------------------------------------

  /**
   * @covers ::getStatus
   */
  public function testGetStatusReturnsNotFoundForNonexistentConnector(): void {
    $this->storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->getStatus(999);

    $this->assertFalse($result['installed']);
    $this->assertEquals('not_found', $result['status']);
    $this->assertEquals(999, $result['connector_id']);
  }

  /**
   * @covers ::getStatus
   */
  public function testGetStatusReturnsNoTenantWhenNoContext(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->getStatus(1);

    $this->assertFalse($result['installed']);
    $this->assertEquals('no_tenant', $result['status']);
  }

  /**
   * @covers ::getStatus
   */
  public function testGetStatusReturnsNotInstalledWhenNoInstallation(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    // Set up tenant context with proper TenantInterface mock.
    $tenant = $this->createMockTenant(42);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);
    $this->connectorInstaller->method('getInstallation')->willReturn(NULL);

    $result = $this->service->getStatus(1);

    $this->assertFalse($result['installed']);
    $this->assertEquals('not_installed', $result['status']);
  }

  /**
   * @covers ::getStatus
   */
  public function testGetStatusReturnsInstalledWithDetails(): void {
    $connector = $this->createMockConnector(1, 'Test');
    $this->storage->method('load')->with(1)->willReturn($connector);

    // Set up tenant context with proper TenantInterface mock.
    $tenant = $this->createMockTenant(42);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $installation = $this->createMock(ConnectorInstallation::class);
    $installation->method('id')->willReturn(77);
    $installation->method('getInstallationStatus')->willReturn('active');

    $this->connectorInstaller->method('getInstallation')->willReturn($installation);

    $result = $this->service->getStatus(1);

    $this->assertTrue($result['installed']);
    $this->assertEquals('active', $result['status']);
    $this->assertEquals(1, $result['connector_id']);
    $this->assertEquals(77, $result['installation_id']);
  }

}
