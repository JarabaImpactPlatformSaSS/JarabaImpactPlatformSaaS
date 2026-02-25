<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\DocumentoRevisionIaService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the DocumentoRevisionIaService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\DocumentoRevisionIaService
 * @group jaraba_andalucia_ei
 */
class DocumentoRevisionIaServiceTest extends UnitTestCase {

  /**
   * Tests that prompts exist for evaluable categories.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function promptsExistForEvaluableCategories(): void {
    $evaluable = [
      'tarea_cv',
      'tarea_carta',
      'tarea_plan_empleo',
      'tarea_proyecto',
      'tarea_diagnostico',
      'tarea_entregable',
    ];

    foreach ($evaluable as $cat) {
      $this->assertArrayHasKey(
        $cat,
        DocumentoRevisionIaService::PROMPTS_POR_CATEGORIA,
        "Missing prompt for category: $cat",
      );
    }
  }

  /**
   * Tests that non-evaluable categories have no prompt.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function noPromptsForNonEvaluableCategories(): void {
    $nonEvaluable = [
      'sto_dni',
      'sto_empadronamiento',
      'programa_contrato',
      'cert_formacion',
    ];

    foreach ($nonEvaluable as $cat) {
      $this->assertArrayNotHasKey(
        $cat,
        DocumentoRevisionIaService::PROMPTS_POR_CATEGORIA,
        "Unexpected prompt for non-evaluable category: $cat",
      );
    }
  }

  /**
   * Tests buildPrompt includes Jaraba identity (AI-IDENTITY-001).
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function buildPromptIncludesJarabaIdentity(): void {
    $service = $this->createServiceWithNullAi();
    $method = new \ReflectionMethod($service, 'buildPrompt');

    $prompt = $method->invoke($service, 'tarea_cv', 'Mi CV');

    $this->assertStringContainsString('Fundación Jaraba', $prompt);
    $this->assertStringContainsString('Andalucía +ei', $prompt);
    $this->assertStringContainsString('Mi CV', $prompt);
    $this->assertStringContainsString('JSON', $prompt);
  }

  /**
   * Tests parseResponse with valid JSON.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function parseResponseHandlesValidJson(): void {
    $service = $this->createServiceWithNullAi();
    $method = new \ReflectionMethod($service, 'parseResponse');

    $json = json_encode([
      'score' => 75,
      'puntos_fuertes' => ['Buena estructura'],
      'areas_mejora' => ['Añadir más detalle'],
      'sugerencias' => ['Revisar formato'],
    ]);

    $result = $method->invoke($service, $json);

    $this->assertEquals(75.0, $result['score']);
    $this->assertCount(1, $result['puntos_fuertes']);
    $this->assertCount(1, $result['areas_mejora']);
    $this->assertCount(1, $result['sugerencias']);
  }

  /**
   * Tests parseResponse returns defaults for invalid JSON.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function parseResponseReturnsDefaultsForInvalidJson(): void {
    $service = $this->createServiceWithNullAi();
    $method = new \ReflectionMethod($service, 'parseResponse');

    $result = $method->invoke($service, 'not valid json at all');

    $this->assertEquals(50.0, $result['score']);
    $this->assertEmpty($result['puntos_fuertes']);
    $this->assertEmpty($result['areas_mejora']);
    $this->assertEmpty($result['sugerencias']);
  }

  /**
   * Tests parseResponse strips markdown code fences.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function parseResponseStripsCodeFences(): void {
    $service = $this->createServiceWithNullAi();
    $method = new \ReflectionMethod($service, 'parseResponse');

    $response = "```json\n" . json_encode([
      'score' => 88,
      'puntos_fuertes' => [],
      'areas_mejora' => [],
      'sugerencias' => [],
    ]) . "\n```";

    $result = $method->invoke($service, $response);

    $this->assertEquals(88.0, $result['score']);
  }

  /**
   * Tests solicitarRevision when AI provider is null.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarRevisionWithNullAiMarksPendiente(): void {
    $doc = $this->createMock(\Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface::class);
    $doc->method('getCategoria')->willReturn('tarea_cv');
    $doc->expects($this->once())->method('setEstadoRevision')->with('pendiente');
    $doc->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($doc);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);

    $logger = $this->createMock(LoggerInterface::class);

    $service = new DocumentoRevisionIaService($entityTypeManager, NULL, $logger);
    $result = $service->solicitarRevision(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('human review', $result['error']);
  }

  /**
   * Tests solicitarRevision returns error for non-evaluable category.
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarRevisionRejectsNonEvaluableCategory(): void {
    $doc = $this->createMock(\Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface::class);
    $doc->method('getCategoria')->willReturn('sto_dni');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($doc);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);

    $logger = $this->createMock(LoggerInterface::class);

    $service = new DocumentoRevisionIaService($entityTypeManager, NULL, $logger);
    $result = $service->solicitarRevision(1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('not evaluable', $result['error']);
  }

  /**
   * Creates a service instance with null AI provider.
   */
  protected function createServiceWithNullAi(): DocumentoRevisionIaService {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    return new DocumentoRevisionIaService($entityTypeManager, NULL, $logger);
  }

}
