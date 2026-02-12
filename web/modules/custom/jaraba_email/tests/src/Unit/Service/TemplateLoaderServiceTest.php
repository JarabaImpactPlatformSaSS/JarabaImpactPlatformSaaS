<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\jaraba_email\Service\MjmlCompilerService;
use Drupal\jaraba_email\Service\TemplateLoaderService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para TemplateLoaderService.
 *
 * @covers \Drupal\jaraba_email\Service\TemplateLoaderService
 * @group jaraba_email
 */
class TemplateLoaderServiceTest extends UnitTestCase {

  protected TemplateLoaderService $service;
  protected MjmlCompilerService $mjmlCompiler;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mjmlCompiler = $this->createMock(MjmlCompilerService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TemplateLoaderService(
      $this->mjmlCompiler,
      $this->logger,
    );
  }

  /**
   * Tests cargar template con ID inválido lanza excepción.
   */
  public function testLoadInvalidTemplateIdThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Template ID no válido: INVALID_999');

    $this->service->load('INVALID_999');
  }

  /**
   * Tests obtener plantillas disponibles retorna el catálogo completo.
   */
  public function testGetAvailableTemplatesReturnsCatalog(): void {
    $templates = $this->service->getAvailableTemplates();

    $this->assertIsArray($templates);
    $this->assertNotEmpty($templates);

    // Verificar que existen plantillas de autenticación.
    $this->assertArrayHasKey('AUTH_001', $templates);
    $this->assertArrayHasKey('AUTH_002', $templates);

    // Verificar estructura de cada plantilla.
    foreach ($templates as $id => $info) {
      $this->assertArrayHasKey('id', $info);
      $this->assertArrayHasKey('description', $info);
      $this->assertArrayHasKey('file', $info);
      $this->assertEquals($id, $info['id']);
    }
  }

  /**
   * Tests obtener plantillas incluye todas las categorías.
   */
  public function testGetAvailableTemplatesIncludesAllCategories(): void {
    $templates = $this->service->getAvailableTemplates();

    // Verificar que contiene plantillas de billing.
    $this->assertArrayHasKey('BILL_001', $templates);
    $this->assertStringContainsString('billing/', $templates['BILL_001']['file']);

    // Verificar que contiene plantillas de marketplace.
    $this->assertArrayHasKey('MKTP_001', $templates);
    $this->assertStringContainsString('marketplace/', $templates['MKTP_001']['file']);

    // Verificar que contiene plantillas de empleabilidad.
    $this->assertArrayHasKey('EMPL_001', $templates);
    $this->assertStringContainsString('empleabilidad/', $templates['EMPL_001']['file']);
  }

  /**
   * Tests preview con template ID inválido lanza excepción.
   */
  public function testPreviewInvalidTemplateIdThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);

    $this->service->preview('NONEXISTENT_ID');
  }

  /**
   * Tests que el catálogo de plantillas tiene descripciones no vacías.
   */
  public function testAllTemplatesHaveNonEmptyDescriptions(): void {
    $templates = $this->service->getAvailableTemplates();

    foreach ($templates as $id => $info) {
      $this->assertNotEmpty(
        $info['description'],
        "La plantilla {$id} no tiene descripción."
      );
    }
  }

}
