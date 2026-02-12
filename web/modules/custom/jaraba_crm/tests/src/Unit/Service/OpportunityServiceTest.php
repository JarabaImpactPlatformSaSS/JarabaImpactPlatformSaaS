<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_crm\Service\OpportunityService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para OpportunityService.
 *
 * @covers \Drupal\jaraba_crm\Service\OpportunityService
 * @group jaraba_crm
 */
class OpportunityServiceTest extends UnitTestCase {

  /**
   * El entity type manager mockeado.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El storage mockeado para la entidad opportunity.
   */
  protected EntityStorageInterface $storage;

  /**
   * El servicio bajo prueba.
   */
  protected OpportunityService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_opportunity')
      ->willReturn($this->storage);

    $this->service = new OpportunityService(
      $this->entityTypeManager,
    );
  }

  /**
   * Tests create() crea una entidad oportunidad con los valores correctos.
   */
  public function testCreateReturnsOpportunityEntity(): void {
    $values = [
      'title' => 'Venta de licencias',
      'company_id' => 5,
      'stage' => 'qualification',
      'value' => 15000.00,
      'tenant_id' => 1,
    ];

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $v) use ($values): bool {
        return $v['title'] === 'Venta de licencias'
          && $v['stage'] === 'qualification'
          && $v['value'] === 15000.00;
      }))
      ->willReturn($entity);

    $result = $this->service->create($values);

    $this->assertSame($entity, $result);
  }

  /**
   * Tests load() devuelve la entidad cuando existe.
   */
  public function testLoadReturnsEntityWhenFound(): void {
    $entity = $this->createMock(ContentEntityInterface::class);

    $this->storage->expects($this->once())
      ->method('load')
      ->with(12)
      ->willReturn($entity);

    $result = $this->service->load(12);

    $this->assertSame($entity, $result);
  }

  /**
   * Tests load() devuelve NULL cuando la oportunidad no existe.
   */
  public function testLoadReturnsNullWhenNotFound(): void {
    $this->storage->expects($this->once())
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->load(999);

    $this->assertNull($result);
  }

  /**
   * Tests moveToStage() actualiza el stage de la oportunidad.
   */
  public function testMoveToStageUpdatesStage(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('set')
      ->with('stage', 'negotiation');
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('load')
      ->with(10)
      ->willReturn($entity);

    $result = $this->service->moveToStage(10, 'negotiation');

    $this->assertTrue($result);
  }

  /**
   * Tests moveToStage() devuelve FALSE cuando la oportunidad no existe.
   */
  public function testMoveToStageReturnsFalseWhenNotFound(): void {
    $this->storage->expects($this->once())
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->moveToStage(999, 'closed_won');

    $this->assertFalse($result);
  }

  /**
   * Tests getByStage() devuelve oportunidades agrupadas por stage.
   */
  public function testGetByStageReturnsGroupedOpportunities(): void {
    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity1->method('get')
      ->willReturnCallback(function (string $field) {
        if ($field === 'stage') {
          return (object) ['value' => 'qualification'];
        }
        return (object) ['value' => NULL];
      });

    $entity2 = $this->createMock(ContentEntityInterface::class);
    $entity2->method('get')
      ->willReturnCallback(function (string $field) {
        if ($field === 'stage') {
          return (object) ['value' => 'negotiation'];
        }
        return (object) ['value' => NULL];
      });

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2])
      ->willReturn([$entity1, $entity2]);

    $result = $this->service->getByStage();

    $this->assertIsArray($result);
  }

  /**
   * Tests count() devuelve el numero total de oportunidades.
   */
  public function testCountReturnsTotalOpportunities(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(28);

    $this->storage->method('getQuery')
      ->willReturn($query);

    $result = $this->service->count();

    $this->assertSame(28, $result);
  }

}
