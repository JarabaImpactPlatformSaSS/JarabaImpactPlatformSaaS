<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para CredentialManagerService.
 *
 * Verifica la logica de obtencion, guardado, listado y eliminacion
 * de credenciales de plataformas de pixel tracking por tenant.
 *
 * Uses a testable subclass to avoid \Drupal static calls in
 * encrypt/decrypt which are not available in unit test context.
 *
 * @covers \Drupal\jaraba_pixels\Service\CredentialManagerService
 * @group jaraba_pixels
 */
class CredentialManagerServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test (testable subclass).
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

    // The service calls getCurrentTenant()->id(), not getCurrentTenantId().
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('id')->willReturn(1);
    $this->tenantContext->method('getCurrentTenant')->willReturn($tenant);

    $this->service = new TestableCredentialManagerService(
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
      'api_secret' => 'secret-xyz',
      'tenant_id' => '1',
    ]);

    $result = $this->service->getCredential('meta', 1);

    $this->assertNotNull($result);
    $this->assertEquals('meta', $result['platform']);
    $this->assertEquals('px-123', $result['pixel_id']);
    // The testable subclass decrypt() returns the value as-is.
    $this->assertEquals('token-abc', $result['access_token']);
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
    // The service calls fetchAllAssoc(), not fetchAll().
    $statement->method('fetchAllAssoc')->willReturn([]);

    $result = $this->service->getAllCredentials(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que saveCredential guarda correctamente.
   *
   * The service's saveCredential() first calls getCredential() to check
   * if a credential exists (via select query), then inserts or updates.
   * When getCredential returns NULL, it uses insert().
   */
  public function testSaveCredentialSuccess(): void {
    // First call: getCredential() does a select to check existing.
    $select = $this->createMock(Select::class);
    $selectStatement = $this->createMock(StatementInterface::class);

    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($selectStatement);
    // No existing credential found -> triggers insert path.
    $selectStatement->method('fetchAssoc')->willReturn(FALSE);

    $this->database->method('select')->willReturn($select);

    // Second call: insert() for the new credential.
    $insert = $this->createMock(Insert::class);
    $insert->method('fields')->willReturnSelf();
    $insert->method('execute')->willReturn('1');

    $this->database->expects($this->once())
      ->method('insert')
      ->with('pixel_credentials')
      ->willReturn($insert);

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

/**
 * Testable subclass that overrides encrypt/decrypt to avoid \Drupal calls.
 *
 * The production CredentialManagerService uses \Drupal::service('settings')
 * for encryption keys, which is unavailable in unit tests. This subclass
 * makes encrypt/decrypt pass-through so we can test the CRUD logic.
 */
class TestableCredentialManagerService extends CredentialManagerService {

  /**
   * {@inheritdoc}
   */
  protected function encrypt(?string $value): ?string {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function decrypt(?string $value): ?string {
    return $value;
  }

}
