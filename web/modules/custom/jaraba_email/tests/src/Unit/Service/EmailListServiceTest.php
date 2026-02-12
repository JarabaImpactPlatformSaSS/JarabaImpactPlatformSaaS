<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_email\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_email\Service\EmailListService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EmailListService.
 *
 * @covers \Drupal\jaraba_email\Service\EmailListService
 * @group jaraba_email
 */
class EmailListServiceTest extends UnitTestCase {

  protected EmailListService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new EmailListService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests obtener listas de un tenant específico.
   */
  public function testGetListsForTenantWithId(): void {
    $list1 = $this->createMock(ContentEntityInterface::class);
    $list2 = $this->createMock(ContentEntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$list1, $list2]);

    $this->entityTypeManager->method('getStorage')
      ->with('email_list')
      ->willReturn($storage);

    $result = $this->service->getListsForTenant(5);

    $this->assertCount(2, $result);
    $this->assertSame($list1, $result[0]);
    $this->assertSame($list2, $result[1]);
  }

  /**
   * Tests obtener listas sin tenant retorna todas las activas.
   */
  public function testGetListsForTenantWithoutIdReturnsAll(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('loadMultiple')->with([])->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('email_list')
      ->willReturn($storage);

    $result = $this->service->getListsForTenant(NULL);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests crear lista con opciones por defecto.
   */
  public function testCreateListDefaultOptions(): void {
    $list = $this->createMock(ContentEntityInterface::class);
    $list->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['name'] === 'Lista de prueba'
          && $values['type'] === 'static'
          && $values['double_optin'] === TRUE
          && $values['is_active'] === TRUE
          && $values['tenant_id'] === NULL;
      }))
      ->willReturn($list);

    $this->entityTypeManager->method('getStorage')
      ->with('email_list')
      ->willReturn($storage);

    $result = $this->service->createList('Lista de prueba');

    $this->assertSame($list, $result);
  }

  /**
   * Tests actualizar conteo de suscriptores con lista inexistente.
   */
  public function testUpdateSubscriberCountListNotFound(): void {
    $listStorage = $this->createMock(EntityStorageInterface::class);
    $listStorage->method('load')->with(999)->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['email_list', $listStorage],
      ]);

    // No debe lanzar excepción; simplemente retorna sin hacer nada.
    $this->service->updateSubscriberCount(999);

    // Si llegamos aquí, el test pasó (no hubo excepción).
    $this->assertTrue(TRUE);
  }

  /**
   * Tests actualizar conteo de suscriptores exitosamente.
   */
  public function testUpdateSubscriberCountSuccess(): void {
    $list = $this->createMock(ContentEntityInterface::class);
    $list->expects($this->once())
      ->method('set')
      ->with('subscriber_count', 15);
    $list->expects($this->once())->method('save');

    $subscriberQuery = $this->createMock(QueryInterface::class);
    $subscriberQuery->method('condition')->willReturnSelf();
    $subscriberQuery->method('accessCheck')->willReturnSelf();
    $subscriberQuery->method('count')->willReturnSelf();
    $subscriberQuery->method('execute')->willReturn(15);

    $listStorage = $this->createMock(EntityStorageInterface::class);
    $listStorage->method('load')->with(1)->willReturn($list);

    $subscriberStorage = $this->createMock(EntityStorageInterface::class);
    $subscriberStorage->method('getQuery')->willReturn($subscriberQuery);

    $this->entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['email_list', $listStorage],
        ['email_subscriber', $subscriberStorage],
      ]);

    $this->service->updateSubscriberCount(1);
  }

}
