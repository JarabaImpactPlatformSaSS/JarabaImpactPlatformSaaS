<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_crm\Service\CompanyService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para CompanyService.
 *
 * @covers \Drupal\jaraba_crm\Service\CompanyService
 * @group jaraba_crm
 */
class CompanyServiceTest extends UnitTestCase {

  /**
   * El entity type manager mockeado.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El storage mockeado para la entidad company.
   */
  protected EntityStorageInterface $storage;

  /**
   * El servicio bajo prueba.
   */
  protected CompanyService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_company')
      ->willReturn($this->storage);

    $this->service = new CompanyService(
      $this->entityTypeManager,
    );
  }

  /**
   * Tests create() crea una entidad company con los valores correctos.
   */
  public function testCreateReturnsCompanyEntity(): void {
    $values = [
      'name' => 'Empresa Test S.L.',
      'email' => 'info@empresa-test.com',
      'tenant_id' => 1,
    ];

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $v) use ($values): bool {
        return $v['name'] === $values['name']
          && $v['email'] === $values['email']
          && $v['tenant_id'] === 1;
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
      ->with(42)
      ->willReturn($entity);

    $result = $this->service->load(42);

    $this->assertSame($entity, $result);
  }

  /**
   * Tests load() devuelve NULL cuando la entidad no existe.
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
   * Tests list() devuelve un array de entidades.
   */
  public function testListReturnsEntities(): void {
    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity2 = $this->createMock(ContentEntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([1 => 1, 2 => 2])
      ->willReturn([$entity1, $entity2]);

    $result = $this->service->list([], 50);

    $this->assertCount(2, $result);
  }

  /**
   * Tests list() devuelve array vacio cuando no hay resultados.
   */
  public function testListReturnsEmptyArrayWhenNoResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([])
      ->willReturn([]);

    $result = $this->service->list();

    $this->assertSame([], $result);
  }

  /**
   * Tests search() devuelve empresas que coinciden con la busqueda.
   */
  public function testSearchReturnsMatchingEntities(): void {
    $entity = $this->createMock(ContentEntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([5 => 5]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([5 => 5])
      ->willReturn([$entity]);

    $result = $this->service->search('Empresa', 20);

    $this->assertCount(1, $result);
  }

  /**
   * Tests count() devuelve el numero total de empresas.
   */
  public function testCountReturnsTotalCompanies(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(15);

    $this->storage->method('getQuery')
      ->willReturn($query);

    $result = $this->service->count();

    $this->assertSame(15, $result);
  }

}
