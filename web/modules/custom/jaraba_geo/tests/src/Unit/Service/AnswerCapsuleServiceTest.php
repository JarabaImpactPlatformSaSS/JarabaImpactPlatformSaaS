<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_geo\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_geo\Service\AnswerCapsuleService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests para AnswerCapsuleService.
 *
 * @group jaraba_geo
 * @coversDefaultClass \Drupal\jaraba_geo\Service\AnswerCapsuleService
 */
class AnswerCapsuleServiceTest extends TestCase {

  /**
   * Mock del entity type manager.
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock de la config factory.
   */
  private ConfigFactoryInterface&MockObject $configFactory;

  /**
   * El servicio bajo test.
   */
  private AnswerCapsuleService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->service = new AnswerCapsuleService(
      $this->entityTypeManager,
      $this->configFactory,
    );
  }

  /**
   * Crea un mock basico de EntityInterface.
   *
   * @param string $label
   *   Etiqueta de la entidad.
   * @param string $bundle
   *   Bundle de la entidad.
   * @param string $entityTypeId
   *   ID del tipo de entidad.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad.
   */
  private function createEntityMock(
    string $label = 'Aceite de Oliva Premium',
    string $bundle = 'product',
    string $entityTypeId = 'node',
  ): ContentEntityInterface&MockObject {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('label')->willReturn($label);
    $entity->method('bundle')->willReturn($bundle);
    $entity->method('getEntityTypeId')->willReturn($entityTypeId);
    $entity->method('hasField')->willReturn(FALSE);

    return $entity;
  }

  /**
   * Tests que generateCapsule retorna un array con todas las claves esperadas.
   *
   * @covers ::generateCapsule
   */
  public function testGenerateCapsuleReturnsArray(): void {
    $entity = $this->createEntityMock();

    $capsule = $this->service->generateCapsule($entity, 'what');

    $this->assertIsArray($capsule);
    $this->assertArrayHasKey('answer', $capsule);
    $this->assertArrayHasKey('context', $capsule);
    $this->assertArrayHasKey('details', $capsule);
    $this->assertArrayHasKey('intent_type', $capsule);
    $this->assertArrayHasKey('entity_type', $capsule);
    $this->assertArrayHasKey('bundle', $capsule);
  }

  /**
   * Tests que la capsula para intencion 'what' genera una respuesta valida.
   *
   * @covers ::generateCapsule
   */
  public function testGenerateCapsuleForWhatIntent(): void {
    $entity = $this->createEntityMock('Aceite de Jaen', 'product');

    $capsule = $this->service->generateCapsule($entity, 'what');

    $this->assertSame('what', $capsule['intent_type']);
    $this->assertStringContainsString('Aceite de Jaen', $capsule['answer']);
    $this->assertStringContainsString('producto', $capsule['answer']);
    $this->assertSame('product', $capsule['bundle']);
    $this->assertSame('node', $capsule['entity_type']);
  }

  /**
   * Tests que generateAllCapsules retorna capsulas para multiples intenciones.
   *
   * @covers ::generateAllCapsules
   */
  public function testGenerateAllCapsulesReturnsMultiple(): void {
    $entity = $this->createEntityMock('Cooperativa Sierra Magina', 'cooperativa');

    $capsules = $this->service->generateAllCapsules($entity);

    $this->assertIsArray($capsules);
    $this->assertCount(5, $capsules);
    $this->assertArrayHasKey('what', $capsules);
    $this->assertArrayHasKey('where', $capsules);
    $this->assertArrayHasKey('how', $capsules);
    $this->assertArrayHasKey('why', $capsules);
    $this->assertArrayHasKey('who', $capsules);

    // Cada capsula debe tener la estructura completa.
    foreach ($capsules as $intent => $capsule) {
      $this->assertArrayHasKey('answer', $capsule, "Capsula '{$intent}' debe tener 'answer'.");
      $this->assertArrayHasKey('context', $capsule, "Capsula '{$intent}' debe tener 'context'.");
      $this->assertSame($intent, $capsule['intent_type']);
    }
  }

  /**
   * Tests la generacion de respuesta para intencion 'where'.
   *
   * @covers ::generateCapsule
   */
  public function testGenerateCapsuleForWhereIntent(): void {
    $entity = $this->createEntityMock('Bodega La Mancha', 'productor');

    $capsule = $this->service->generateCapsule($entity, 'where');

    $this->assertSame('where', $capsule['intent_type']);
    $this->assertStringContainsString('Bodega La Mancha', $capsule['answer']);
  }

  /**
   * Tests la generacion de respuesta para intencion 'how'.
   *
   * @covers ::generateCapsule
   */
  public function testGenerateCapsuleForHowIntent(): void {
    $entity = $this->createEntityMock('Queso Manchego DOP');

    $capsule = $this->service->generateCapsule($entity, 'how');

    $this->assertSame('how', $capsule['intent_type']);
    $this->assertStringContainsString('Queso Manchego DOP', $capsule['answer']);
    $this->assertStringContainsString('plataforma', $capsule['answer']);
  }

  /**
   * Tests la generacion de respuesta para intencion 'why'.
   *
   * @covers ::generateCapsule
   */
  public function testGenerateCapsuleForWhyIntent(): void {
    $entity = $this->createEntityMock('Jamon Iberico');

    $capsule = $this->service->generateCapsule($entity, 'why');

    $this->assertSame('why', $capsule['intent_type']);
    $this->assertStringContainsString('Jamon Iberico', $capsule['answer']);
    $this->assertStringContainsString('calidad', $capsule['answer']);
  }

  /**
   * Tests que el contexto contiene texto de autoridad.
   *
   * @covers ::generateCapsule
   */
  public function testContextContainsAuthoritySignal(): void {
    $entity = $this->createEntityMock();

    $capsule = $this->service->generateCapsule($entity);

    $this->assertStringContainsString('Verificado en Jaraba Impact Platform', $capsule['context']);
  }

  /**
   * Tests que bundle 'cooperativa' genera respuesta especifica.
   *
   * @covers ::generateCapsule
   */
  public function testWhatAnswerForCooperativa(): void {
    $entity = $this->createEntityMock('Coop Sierra Sur', 'cooperativa');

    $capsule = $this->service->generateCapsule($entity, 'what');

    $this->assertStringContainsString('cooperativa agroalimentaria', $capsule['answer']);
  }

  /**
   * Tests que un bundle desconocido usa la descripcion truncada.
   *
   * @covers ::generateCapsule
   */
  public function testWhatAnswerForUnknownBundle(): void {
    $entity = $this->createEntityMock('Articulo Ejemplo', 'article');

    $capsule = $this->service->generateCapsule($entity, 'what');

    // Para bundle desconocido, el answer comienza con el label.
    $this->assertStringStartsWith('Articulo Ejemplo:', $capsule['answer']);
  }

}
