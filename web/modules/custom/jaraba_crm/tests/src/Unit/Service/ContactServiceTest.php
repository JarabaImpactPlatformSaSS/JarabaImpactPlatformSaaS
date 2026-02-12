<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_crm\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para ContactService.
 *
 * @covers \Drupal\jaraba_crm\Service\ContactService
 * @group jaraba_crm
 */
class ContactServiceTest extends UnitTestCase {

  /**
   * El entity type manager mockeado.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El storage mockeado para la entidad contact.
   */
  protected EntityStorageInterface $storage;

  /**
   * El servicio bajo prueba.
   */
  protected ContactService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('crm_contact')
      ->willReturn($this->storage);

    $this->service = new ContactService(
      $this->entityTypeManager,
    );
  }

  /**
   * Tests create() crea una entidad contacto con los valores correctos.
   */
  public function testCreateReturnsContactEntity(): void {
    $values = [
      'first_name' => 'Carlos',
      'last_name' => 'Garcia',
      'email' => 'carlos@example.com',
      'company_id' => 10,
      'tenant_id' => 1,
    ];

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())
      ->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $v) use ($values): bool {
        return $v['first_name'] === 'Carlos'
          && $v['last_name'] === 'Garcia'
          && $v['email'] === 'carlos@example.com'
          && $v['company_id'] === 10;
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
      ->with(7)
      ->willReturn($entity);

    $result = $this->service->load(7);

    $this->assertSame($entity, $result);
  }

  /**
   * Tests load() devuelve NULL cuando el contacto no existe.
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
   * Tests getByCompany() devuelve los contactos de una empresa.
   */
  public function testGetByCompanyReturnsContacts(): void {
    $entity1 = $this->createMock(ContentEntityInterface::class);
    $entity2 = $this->createMock(ContentEntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([3 => 3, 8 => 8]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([3 => 3, 8 => 8])
      ->willReturn([$entity1, $entity2]);

    $result = $this->service->getByCompany(10);

    $this->assertCount(2, $result);
  }

  /**
   * Tests getByCompany() devuelve array vacio cuando la empresa no tiene contactos.
   */
  public function testGetByCompanyReturnsEmptyWhenNoContacts(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')
      ->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([])
      ->willReturn([]);

    $result = $this->service->getByCompany(999);

    $this->assertSame([], $result);
  }

  /**
   * Tests calculateEngagementScore() devuelve la puntuacion de engagement.
   */
  public function testCalculateEngagementScoreReturnsScore(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')
      ->willReturnCallback(function (string $fieldName) {
        if ($fieldName === 'engagement_score') {
          return (object) ['value' => 85];
        }
        return (object) ['value' => NULL];
      });

    $this->storage->method('load')
      ->with(42)
      ->willReturn($entity);

    $result = $this->service->calculateEngagementScore(42);

    $this->assertIsInt($result);
  }

  /**
   * Tests count() devuelve el numero total de contactos.
   */
  public function testCountReturnsTotalContacts(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(42);

    $this->storage->method('getQuery')
      ->willReturn($query);

    $result = $this->service->count();

    $this->assertSame(42, $result);
  }

}
