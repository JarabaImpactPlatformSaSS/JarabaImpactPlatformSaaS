<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_integrations\Unit\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_integrations\Service\OpenApiSpecService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for OpenApiSpecService.
 *
 * @group jaraba_integrations
 * @coversDefaultClass \Drupal\jaraba_integrations\Service\OpenApiSpecService
 */
class OpenApiSpecServiceTest extends UnitTestCase {

  protected OpenApiSpecService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   *
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->service = new OpenApiSpecService($this->entityTypeManager, $this->entityFieldManager);
  }

  /**
   * @covers ::generateSpec
   */
  public function testGenerateSpecStructure(): void {
    $this->entityTypeManager->method('hasDefinition')->willReturn(FALSE);

    $spec = $this->service->generateSpec();

    $this->assertEquals('3.0.3', $spec['openapi']);
    $this->assertArrayHasKey('info', $spec);
    $this->assertArrayHasKey('paths', $spec);
    $this->assertArrayHasKey('components', $spec);
    $this->assertEquals('Jaraba Impact Platform API', $spec['info']['title']);
    $this->assertEquals('1.0.0', $spec['info']['version']);
  }

  /**
   * @covers ::getAuthSchemas
   */
  public function testGetAuthSchemas(): void {
    $schemas = $this->service->getAuthSchemas();

    $this->assertArrayHasKey('oauth2', $schemas);
    $this->assertArrayHasKey('hmac', $schemas);
    $this->assertArrayHasKey('csrfToken', $schemas);
    $this->assertEquals('oauth2', $schemas['oauth2']['type']);
    $this->assertEquals('apiKey', $schemas['hmac']['type']);
  }

  /**
   * @covers ::getEntitySchemas
   */
  public function testGetEntitySchemasNoEntities(): void {
    $this->entityTypeManager->method('hasDefinition')->willReturn(FALSE);

    $schemas = $this->service->getEntitySchemas();

    $this->assertIsArray($schemas);
    $this->assertEmpty($schemas);
  }

}
