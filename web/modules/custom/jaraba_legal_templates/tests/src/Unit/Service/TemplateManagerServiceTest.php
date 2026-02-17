<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_templates\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_legal_templates\Service\TemplateManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TemplateManagerService.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_templates\Service\TemplateManagerService
 * @group jaraba_legal_templates
 */
class TemplateManagerServiceTest extends UnitTestCase {

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\jaraba_legal_templates\Service\TemplateManagerService
   */
  protected TemplateManagerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TemplateManagerService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests listByType returns serialized templates filtered by type.
   *
   * @covers ::listByType
   * @covers ::serializeTemplate
   */
  public function testListByType(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $template1 = $this->createTemplateMock(1, 'uuid-1', 'Demanda Civil', 'civil', FALSE, TRUE, 5, [], '1709312400');
    $template2 = $this->createTemplateMock(2, 'uuid-2', 'Contestacion Civil', 'civil', FALSE, TRUE, 3, [], '1709398800');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$template1, $template2]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('legal_template')
      ->willReturn($storage);

    $result = $this->service->listByType('civil');

    $this->assertCount(2, $result);
    $this->assertSame(1, $result[0]['id']);
    $this->assertSame('Demanda Civil', $result[0]['name']);
    $this->assertSame('civil', $result[0]['template_type']);
    $this->assertTrue($result[0]['is_active']);
    $this->assertSame(2, $result[1]['id']);
    $this->assertSame('Contestacion Civil', $result[1]['name']);
  }

  /**
   * Tests getSystemTemplates returns only system-flagged templates.
   *
   * @covers ::getSystemTemplates
   * @covers ::serializeTemplate
   */
  public function testGetSystemTemplates(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([10]);

    $sysTemplate = $this->createTemplateMock(10, 'uuid-sys', 'Poder General', 'notarial', TRUE, TRUE, 100, [], '1709312400');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([10])
      ->willReturn([$sysTemplate]);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('legal_template')
      ->willReturn($storage);

    $result = $this->service->getSystemTemplates();

    $this->assertCount(1, $result);
    $this->assertTrue($result[0]['is_system']);
    $this->assertSame('Poder General', $result[0]['name']);
    $this->assertSame('notarial', $result[0]['template_type']);
  }

  /**
   * Tests renderTemplate resolves {{ campo.subcampo }} merge fields.
   *
   * @covers ::renderTemplate
   */
  public function testRenderTemplate(): void {
    $body = 'Estimado/a {{ cliente.nombre }}, su expediente {{ expediente.referencia }} ha sido actualizado.';
    $data = [
      'cliente' => [
        'nombre' => 'Maria Garcia',
        'nif' => '12345678A',
      ],
      'expediente' => [
        'referencia' => 'EXP-2025-001',
        'tipo' => 'civil',
      ],
    ];

    $result = $this->service->renderTemplate($body, $data);

    $this->assertSame(
      'Estimado/a Maria Garcia, su expediente EXP-2025-001 ha sido actualizado.',
      $result,
    );
  }

  /**
   * Tests renderTemplate leaves unresolved merge fields intact.
   *
   * @covers ::renderTemplate
   */
  public function testRenderTemplateUnresolvedFields(): void {
    $body = 'Nombre: {{ cliente.nombre }}, Telefono: {{ cliente.telefono }}';
    $data = [
      'cliente' => [
        'nombre' => 'Juan Lopez',
        // No 'telefono' key.
      ],
    ];

    $result = $this->service->renderTemplate($body, $data);

    $this->assertSame(
      'Nombre: Juan Lopez, Telefono: {{ cliente.telefono }}',
      $result,
    );
  }

  /**
   * Tests renderTemplate handles simple (non-nested) merge fields.
   *
   * @covers ::renderTemplate
   */
  public function testRenderTemplateSimpleFields(): void {
    $body = 'Fecha: {{ fecha }}, Ciudad: {{ ciudad }}';
    $data = [
      'fecha' => '2025-03-15',
      'ciudad' => 'Madrid',
    ];

    $result = $this->service->renderTemplate($body, $data);

    $this->assertSame('Fecha: 2025-03-15, Ciudad: Madrid', $result);
  }

  /**
   * Tests serializeTemplate returns the expected structure.
   *
   * @covers ::serializeTemplate
   */
  public function testSerializeTemplate(): void {
    $template = $this->createTemplateMock(
      5, 'uuid-5', 'Contrato Laboral', 'laboral', FALSE, TRUE, 12,
      ['empleado.nombre', 'empresa.cif'],
      '1709312400',
    );

    // Access serializeTemplate via a reflection since it takes an entity.
    $result = $this->service->serializeTemplate($template);

    $this->assertSame(5, $result['id']);
    $this->assertSame('uuid-5', $result['uuid']);
    $this->assertSame('Contrato Laboral', $result['name']);
    $this->assertSame('laboral', $result['template_type']);
    $this->assertFalse($result['is_system']);
    $this->assertTrue($result['is_active']);
    $this->assertSame(12, $result['usage_count']);
    $this->assertArrayHasKey('merge_fields', $result);
    $this->assertArrayHasKey('created', $result);
  }

  /**
   * Tests listByType returns empty array on exception.
   *
   * @covers ::listByType
   */
  public function testListByTypeReturnsEmptyOnException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Storage error'));

    $this->entityTypeManager
      ->method('getStorage')
      ->with('legal_template')
      ->willReturn($storage);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->listByType('civil');

    $this->assertSame([], $result);
  }

  /**
   * Helper to create a mock field item with a value property.
   */
  protected function createFieldItem(mixed $value): object {
    $field = new \stdClass();
    $field->value = $value;
    return $field;
  }

  /**
   * Helper to create a mock field item with getValue() returning array.
   */
  protected function createFieldItemList(array $values): object {
    $field = $this->createMock(\stdClass::class);
    $field->method('getValue')->willReturn($values);
    return $field;
  }

  /**
   * Creates a mock template entity.
   */
  protected function createTemplateMock(
    int $id,
    string $uuid,
    string $name,
    string $templateType,
    bool $isSystem,
    bool $isActive,
    int $usageCount,
    array $mergeFields,
    string $created,
  ): object {
    $template = $this->createMock(\stdClass::class);
    $template->method('id')->willReturn($id);
    $template->method('uuid')->willReturn($uuid);

    $mergeFieldsItemList = $this->createMock(\stdClass::class);
    $mergeFieldsItemList->method('getValue')->willReturn(
      !empty($mergeFields) ? [$mergeFields] : [],
    );

    $fieldMap = [
      'name' => $this->createFieldItem($name),
      'template_type' => $this->createFieldItem($templateType),
      'is_system' => $this->createFieldItem($isSystem),
      'is_active' => $this->createFieldItem($isActive),
      'usage_count' => $this->createFieldItem($usageCount),
      'merge_fields' => $mergeFieldsItemList,
      'created' => $this->createFieldItem($created),
    ];

    $template->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? $this->createFieldItem(NULL);
    });

    return $template;
  }

}
