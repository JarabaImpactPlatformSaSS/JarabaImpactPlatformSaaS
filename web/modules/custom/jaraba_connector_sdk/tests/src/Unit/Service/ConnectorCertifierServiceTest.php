<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_connector_sdk\Unit\Service;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_connector_sdk\Service\ConnectorCertifierService;
use Drupal\jaraba_integrations\Entity\Connector;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ConnectorCertifierService.
 *
 * @group jaraba_connector_sdk
 * @coversDefaultClass \Drupal\jaraba_connector_sdk\Service\ConnectorCertifierService
 */
class ConnectorCertifierServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected LoggerInterface $logger;
  protected ConnectorCertifierService $service;
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('connector')
      ->willReturn($this->storage);

    // Mock container for t().
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->service = new ConnectorCertifierService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests that submitForCertification changes connector status to 'testing'.
   *
   * @covers ::submitForCertification
   */
  public function testSubmitForCertificationChangesStatus(): void {
    $connector = $this->createMock(Connector::class);
    $connector->method('getName')->willReturn('Test Connector');
    $connector->method('getCategory')->willReturn('analytics');

    // Mock the field value objects.
    $machineNameField = new \stdClass();
    $machineNameField->value = 'test_connector';

    $versionField = new \stdClass();
    $versionField->value = '1.0.0';

    $connector->method('get')
      ->willReturnCallback(function (string $field) use ($machineNameField, $versionField) {
        return match ($field) {
          'machine_name' => $machineNameField,
          'version' => $versionField,
          default => (object) ['value' => ''],
        };
      });

    $connector->expects($this->once())
      ->method('set')
      ->with('publish_status', ConnectorCertifierService::STATUS_TESTING);

    $connector->expects($this->once())->method('save');

    $this->storage->method('load')->with(1)->willReturn($connector);

    $result = $this->service->submitForCertification(1, 42);

    $this->assertEquals(ConnectorCertifierService::STATUS_TESTING, $result['status']);
    $this->assertEquals(1, $result['connector_id']);
    $this->assertEquals(42, $result['developer_id']);
    $this->assertTrue($result['manifest_valid']);
  }

  /**
   * Tests that certify only succeeds when all tests pass.
   *
   * @covers ::certify
   */
  public function testCertifyOnlyWhenAllTestsPass(): void {
    $connector = $this->createMock(Connector::class);
    $connector->method('getName')->willReturn('Good Connector');
    $connector->method('getCategory')->willReturn('crm');

    $machineNameField = new \stdClass();
    $machineNameField->value = 'good_connector';

    $versionField = new \stdClass();
    $versionField->value = '2.0.0';

    $configSchemaField = new \stdClass();
    $configSchemaField->value = '{}';

    $connector->method('get')
      ->willReturnCallback(function (string $field) use ($machineNameField, $versionField, $configSchemaField) {
        return match ($field) {
          'machine_name' => $machineNameField,
          'version' => $versionField,
          'config_schema' => $configSchemaField,
          default => (object) ['value' => ''],
        };
      });

    $connector->method('getConfigSchema')->willReturn([]);

    // Expects publish_status to be set to 'certified'.
    $connector->expects($this->once())
      ->method('set')
      ->with('publish_status', ConnectorCertifierService::STATUS_CERTIFIED);

    $connector->expects($this->once())->method('save');

    $this->storage->method('load')->with(5)->willReturn($connector);

    $result = $this->service->certify(5);
    $this->assertTrue($result);
  }

  /**
   * Tests that suspend sets status and logs the reason.
   *
   * @covers ::suspend
   */
  public function testSuspendSetsStatusAndReason(): void {
    $connector = $this->createMock(Connector::class);
    $connector->method('getName')->willReturn('Bad Connector');

    $connector->expects($this->once())
      ->method('set')
      ->with('publish_status', ConnectorCertifierService::STATUS_SUSPENDED);

    $connector->expects($this->once())->method('save');

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('suspended'),
        $this->callback(function (array $context) {
          return $context['@reason'] === 'Security vulnerability found.';
        })
      );

    $this->storage->method('load')->with(10)->willReturn($connector);

    $result = $this->service->suspend(10, 'Security vulnerability found.');
    $this->assertTrue($result);
  }

}
