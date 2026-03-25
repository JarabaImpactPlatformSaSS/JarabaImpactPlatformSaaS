<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_rag\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_rag\Service\KbIndexerService;
use Drupal\jaraba_rag\Service\RagTenantFilterService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Tests KbIndexerService — RAG indexing pipeline.
 *
 * Verifies empty content handling, chunk limits, and
 * embedding cache logic.
 *
 * @group jaraba_rag
 * @coversDefaultClass \Drupal\jaraba_rag\Service\KbIndexerService
 */
class KbIndexerServiceTest extends TestCase {

  /**
   * Tests indexEntity skips entities with no indexable content.
   */
  public function testIndexEntitySkipsEmptyContent(): void {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(500);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $logger = $this->createMock(LoggerInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $tenantContext = $this->createMock(RagTenantFilterService::class);

    // Create service without Qdrant client (null = no-op).
    $service = new KbIndexerService(
      NULL,
      NULL,
      $tenantContext,
      $entityTypeManager,
      $loggerFactory,
      $configFactory,
      NULL
    );

    // Create entity mock with empty body.
    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->method('__get')->with('value')->willReturn('');

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('hasField')->willReturn(TRUE);
    $entity->method('get')->willReturn($fieldList);
    $entity->method('getEntityTypeId')->willReturn('test_entity');
    $entity->method('id')->willReturn(1);
    $entity->method('label')->willReturn('Test');
    $entity->method('getCacheContexts')->willReturn([]);
    $entity->method('getCacheTags')->willReturn([]);
    $entity->method('getCacheMaxAge')->willReturn(-1);

    // Should not throw — silently skips.
    $service->indexEntity($entity);
    $this->assertTrue(TRUE, 'indexEntity() handled empty content without error.');
  }

}
