<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_crm\Service\ActivityService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para ActivityService.
 *
 * @covers \Drupal\jaraba_crm\Service\ActivityService
 * @group jaraba_crm
 */
class ActivityServiceTest extends UnitTestCase {

  /**
   * El entity type manager mockeado.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El storage mockeado para la entidad activity.
   */
  protected EntityStorageInterface $storage;

  /**
   * El servicio bajo prueba.
   */
  protected ActivityService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_activity')
      ->willReturn($this->storage);

    $this->service = new ActivityService(
      $this->entityTypeManager,
    );
  }

  /**
   * Tests create() crea una entidad actividad con los valores correctos.
   */
  public function testCreateReturnsActivityEntity(): void {
    $values = [
      'type' => 'call',
      'subject' => 'Llamada de seguimiento',
      'contact_id' => 15,
      'tenant_id' => 1,
    ];

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $v) use ($values): bool {
        return $v['type'] === 'call'
          && $v['subject'] === 'Llamada de seguimiento'
          && $v['contact_id'] === 15;
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
      ->with(25)
      ->willReturn($entity);

    $result = $this->service->load(25);

    $this->assertSame($entity, $result);
  }

  /**
   * Tests load() devuelve NULL cuando la actividad no existe.
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
   * Tests getContactTimeline() devuelve actividades de un contacto.
   */
  public function testGetContactTimelineReturnsActivities(): void {
    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity2 = $this->createMock(ContentEntityInterface::class);
    $entity3 = $this->createMock(ContentEntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2, 3 => 3]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2, 3 => 3])
      ->willReturn([$entity1, $entity2, $entity3]);

    $result = $this->service->getContactTimeline(15, 50);

    $this->assertCount(3, $result);
  }

  /**
   * Tests getContactTimeline() devuelve array vacio cuando no hay actividades.
   */
  public function testGetContactTimelineReturnsEmptyWhenNoActivities(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([])
      ->willReturn([]);

    $result = $this->service->getContactTimeline(999);

    $this->assertSame([], $result);
  }

  /**
   * Tests logCall() crea una actividad de tipo llamada.
   */
  public function testLogCallCreatesCallActivity(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $v): bool {
        return $v['type'] === 'call'
          && $v['contact_id'] === 15
          && $v['subject'] === 'Llamada comercial'
          && $v['duration'] === 300
          && $v['notes'] === 'Cliente interesado';
      }))
      ->willReturn($entity);

    $result = $this->service->logCall(15, 'Llamada comercial', 300, 'Cliente interesado');

    $this->assertSame($entity, $result);
  }

  /**
   * Tests logEmail() crea una actividad de tipo email.
   */
  public function testLogEmailCreatesEmailActivity(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $v): bool {
        return $v['type'] === 'email'
          && $v['contact_id'] === 20
          && $v['subject'] === 'Propuesta enviada';
      }))
      ->willReturn($entity);

    $result = $this->service->logEmail(20, 'Propuesta enviada');

    $this->assertSame($entity, $result);
  }

  /**
   * Tests count() devuelve el numero total de actividades.
   */
  public function testCountReturnsTotalActivities(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(156);

    $this->storage->method('getQuery')
      ->willReturn($query);

    $result = $this->service->count();

    $this->assertSame(156, $result);
  }

}
