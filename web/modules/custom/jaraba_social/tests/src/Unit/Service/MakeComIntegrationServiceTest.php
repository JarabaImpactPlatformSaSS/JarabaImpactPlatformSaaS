<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_social\Service\MakeComIntegrationService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests para MakeComIntegrationService.
 *
 * @covers \Drupal\jaraba_social\Service\MakeComIntegrationService
 * @group jaraba_social
 */
class MakeComIntegrationServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected MakeComIntegrationService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del config factory.
   */
  protected ConfigFactoryInterface&MockObject $configFactory;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mock del storage de social_post.
   */
  protected EntityStorageInterface&MockObject $postStorage;

  /**
   * Mock de la configuracion.
   */
  protected ImmutableConfig&MockObject $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->postStorage = $this->createMock(EntityStorageInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('social_post')
      ->willReturn($this->postStorage);

    $this->configFactory
      ->method('get')
      ->with('jaraba_social.make_com_settings')
      ->willReturn($this->config);

    $this->service = new MakeComIntegrationService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests que publishViaWebhook falla cuando no hay configuracion de webhook.
   *
   * @covers ::publishViaWebhook
   */
  public function testPublishViaWebhookNoConfig(): void {
    // Crear un mock de post que retorna tenant_id.
    $tenantIdField = $this->createMock(FieldItemListInterface::class);
    $tenantIdField->target_id = 42;

    $post = $this->createMock(ContentEntityInterface::class);
    $post->method('get')
      ->willReturnCallback(function (string $field) use ($tenantIdField) {
        if ($field === 'tenant_id') {
          return $tenantIdField;
        }
        return $this->createMock(FieldItemListInterface::class);
      });

    $this->postStorage
      ->method('load')
      ->with(1)
      ->willReturn($post);

    // Sin webhook configurado.
    $this->config
      ->method('get')
      ->willReturn(NULL);

    $this->logger
      ->expects($this->once())
      ->method('warning');

    $result = $this->service->publishViaWebhook(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no configurado', $result['message']);
  }

  /**
   * Tests que testConnection falla cuando no hay webhook configurado.
   *
   * @covers ::testConnection
   */
  public function testTestConnectionFails(): void {
    // Sin webhook configurado.
    $this->config
      ->method('get')
      ->willReturn(NULL);

    $this->logger
      ->expects($this->once())
      ->method('warning');

    $result = $this->service->testConnection(42);

    $this->assertFalse($result);
  }

  /**
   * Tests que processIncomingWebhook procesa datos validos correctamente.
   *
   * @covers ::processIncomingWebhook
   */
  public function testProcessIncomingWebhook(): void {
    $post = $this->createMock(ContentEntityInterface::class);
    $post->expects($this->atLeastOnce())
      ->method('set');
    $post->expects($this->once())
      ->method('save');

    // Mock para get('external_ids').
    $externalIdsField = $this->createMock(FieldItemListInterface::class);
    $externalIdsField->method('getValue')->willReturn([]);

    $post->method('get')
      ->willReturnCallback(function (string $field) use ($externalIdsField) {
        if ($field === 'external_ids') {
          return $externalIdsField;
        }
        return $this->createMock(FieldItemListInterface::class);
      });

    $this->postStorage
      ->method('load')
      ->with(5)
      ->willReturn($post);

    $data = [
      'post_id' => 5,
      'platform' => 'instagram',
      'external_id' => 'ig_123456',
      'status' => 'success',
    ];

    $result = $this->service->processIncomingWebhook($data);

    $this->assertTrue($result['processed']);
    $this->assertSame(5, $result['post_id']);
  }

}
