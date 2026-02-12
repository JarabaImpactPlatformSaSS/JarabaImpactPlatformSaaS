<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para CredentialManagerService.
 *
 * Verifica la logica de obtencion, guardado, listado y eliminacion
 * de credenciales de plataformas de pixel tracking por tenant.
 *
 * @covers \Drupal\jaraba_pixels\Service\CredentialManagerService
 * @group jaraba_pixels
 */
class CredentialManagerServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected CredentialManagerService $service;

  /**
   * Mock de la conexion de base de datos.
   */
  protected Connection $database;

  /**
   * Mock del servicio de contexto de tenant.
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    // Configurar tenant por defecto.
    $this->tenantContext->method('getCurrentTenantId')->willReturn(1);

    $this->service = new CredentialManagerService(
      $this->database,
      $this->tenantContext,
    );
  }

  /**
   * Tests que getCredential devuelve NULL cuando no hay credencial.
   */
  public function testGetCredentialReturnsNullWhenNotFound(): void {
    $select = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($select);

    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);
    $statement->method('fetchAssoc')->willReturn(FALSE);

    $result = $this->service->getCredential('meta', 1);

    $this->assertNull($result);
  }

  /**
   * Tests que getCredential devuelve array con datos cuando existe.
   */
  public function testGetCredentialReturnsDataWhenFound(): void {
    $select = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($select);

    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);
    $statement->method('fetchAssoc')->willReturn([
      'platform' => 'meta',
      'pixel_id' => 'px-123',
      'access_token' => 'token-abc',
      'tenant_id' => '1',
    ]);

    $result = $this->service->getCredential('meta', 1);

    $this->assertNotNull($result);
    $this->assertEquals('meta', $result['platform']);
    $this->assertEquals('px-123', $result['pixel_id']);
  }

  /**
   * Tests que getAllCredentials devuelve array vacio sin credenciales.
   */
  public function testGetAllCredentialsReturnsEmptyArray(): void {
    $select = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($select);

    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);
    $statement->method('fetchAll')->willReturn([]);

    $result = $this->service->getAllCredentials(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que saveCredential guarda correctamente.
   */
  public function testSaveCredentialSuccess(): void {
    $merge = $this->createMock(Merge::class);

    $this->database->expects($this->once())
      ->method('merge')
      ->willReturn($merge);

    $merge->method('key')->willReturnSelf();
    $merge->method('fields')->willReturnSelf();
    $merge->method('execute')->willReturn(NULL);

    $result = $this->service->saveCredential('meta', [
      'pixel_id' => 'px-456',
      'access_token' => 'token-new',
    ], 1);

    $this->assertTrue($result);
  }

  /**
   * Tests que deleteCredential elimina correctamente.
   */
  public function testDeleteCredentialSuccess(): void {
    $delete = $this->createMock(Delete::class);

    $this->database->expects($this->once())
      ->method('delete')
      ->willReturn($delete);

    $delete->expects($this->atLeastOnce())
      ->method('condition')
      ->willReturnSelf();
    $delete->expects($this->once())
      ->method('execute');

    $result = $this->service->deleteCredential('meta', 1);

    $this->assertTrue($result);
  }

}
