<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ab_testing\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_ab_testing\Service\VariantAssignmentService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests unitarios para VariantAssignmentService.
 *
 * Verifica la logica de asignacion de variantes, registro de
 * conversiones y consulta de asignaciones actuales en experimentos A/B.
 *
 * @coversDefaultClass \Drupal\jaraba_ab_testing\Service\VariantAssignmentService
 * @group jaraba_ab_testing
 */
class VariantAssignmentServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_ab_testing\Service\VariantAssignmentService
   */
  protected VariantAssignmentService $service;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock del request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected RequestStack|MockObject $requestStack;

  /**
   * Mock del canal de log.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock del storage de experimentos.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $experimentStorage;

  /**
   * Mock del storage de variantes.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $variantStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->experimentStorage = $this->createMock(EntityStorageInterface::class);
    $this->variantStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['ab_experiment', $this->experimentStorage],
        ['ab_variant', $this->variantStorage],
      ]);

    // Simular request con cookies.
    $request = new Request();
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->service = new VariantAssignmentService(
      $this->entityTypeManager,
      $this->requestStack,
      $this->logger,
    );
  }

  /**
   * Helper para crear un mock de campo con un valor simple.
   *
   * @param mixed $value
   *   El valor que devolvera el campo.
   *
   * @return object
   *   Un objeto que actua como field item list.
   */
  protected function createFieldValue(mixed $value): object {
    return (object) ['value' => $value];
  }

  /**
   * Configura un query mock que devuelve los IDs especificados.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $storage
   *   El mock de storage al que asociar el query.
   * @param array $ids
   *   Los IDs que devolvera el query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   *   El mock de query configurado.
   */
  protected function setupQuery(EntityStorageInterface|MockObject $storage, array $ids): QueryInterface|MockObject {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn($ids);

    $storage
      ->method('getQuery')
      ->willReturn($query);

    return $query;
  }

  /**
   * Verifica que assignVariant() devuelve NULL cuando el experimento no existe.
   *
   * @covers ::assignVariant
   */
  public function testAssignVariantReturnsNullWhenExperimentNotFound(): void {
    $this->setupQuery($this->experimentStorage, []);

    $result = $this->service->assignVariant('nonexistent_experiment');

    $this->assertNull($result);
  }

  /**
   * Verifica que assignVariant() devuelve NULL cuando no hay variantes activas.
   *
   * @covers ::assignVariant
   */
  public function testAssignVariantReturnsNullWhenNoVariants(): void {
    // Experimento existe.
    $this->setupQuery($this->experimentStorage, [1]);

    $experiment = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'get'])
      ->getMock();
    $experiment->method('id')->willReturn(1);
    $experiment->method('get')->willReturnCallback(function (string $field) {
      return match ($field) {
        'status' => $this->createFieldValue('active'),
        'machine_name' => $this->createFieldValue('test_experiment'),
        default => $this->createFieldValue(NULL),
      };
    });

    $this->experimentStorage->method('load')->with(1)->willReturn($experiment);

    // No hay variantes.
    $this->setupQuery($this->variantStorage, []);

    $result = $this->service->assignVariant('test_experiment');

    $this->assertNull($result);
  }

  /**
   * Verifica que recordConversion() devuelve FALSE con experimento inexistente.
   *
   * @covers ::recordConversion
   */
  public function testRecordConversionReturnsFalseWhenExperimentNotFound(): void {
    $this->setupQuery($this->experimentStorage, []);

    $result = $this->service->recordConversion('nonexistent_experiment', 0.0);

    $this->assertFalse($result);
  }

  /**
   * Verifica que getCurrentAssignment() devuelve NULL sin asignacion previa.
   *
   * @covers ::getCurrentAssignment
   */
  public function testGetCurrentAssignmentReturnsNullWithNoAssignment(): void {
    $result = $this->service->getCurrentAssignment('test_experiment');

    $this->assertNull($result);
  }

}
