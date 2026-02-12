<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_pixels\Service\ConsentManagementService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ConsentManagementService.
 *
 * Verifica la lógica de registro, consulta, revocación y
 * comprobación de consentimiento de visitantes.
 *
 * @covers \Drupal\jaraba_pixels\Service\ConsentManagementService
 * @group jaraba_pixels
 */
class ConsentManagementServiceTest extends UnitTestCase {

  /**
   * Mock del gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock del storage de consent_record.
   */
  protected EntityStorageInterface $storage;

  /**
   * Servicio bajo test.
   */
  protected ConsentManagementService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('consent_record')
      ->willReturn($this->storage);

    $this->service = new ConsentManagementService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que recordConsent crea una entidad y devuelve TRUE.
   */
  public function testRecordConsentCreatesEntityAndReturnsTrue(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['visitor_id'] === 'visitor-123'
          && $values['tenant_id'] === 1
          && $values['consent_type'] === 'analytics'
          && $values['status'] === 'granted'
          && $values['ip_address'] === '192.168.1.1';
      }))
      ->willReturn($entity);

    $result = $this->service->recordConsent(
      'visitor-123',
      1,
      'analytics',
      'granted',
      ['ip_address' => '192.168.1.1'],
    );

    $this->assertTrue($result);
  }

  /**
   * Tests que recordConsent devuelve FALSE cuando falla el save.
   */
  public function testRecordConsentReturnsFalseOnError(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('save')->willThrowException(new \Exception('Error de base de datos'));

    $this->storage->method('create')->willReturn($entity);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Error registrando consentimiento'),
        $this->anything(),
      );

    $result = $this->service->recordConsent('visitor-123', 1, 'analytics', 'granted');

    $this->assertFalse($result);
  }

  /**
   * Tests que recordConsent incluye contexto completo cuando se proporciona.
   */
  public function testRecordConsentWithFullContext(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['ip_address'] === '10.0.0.1'
          && $values['user_agent'] === 'Mozilla/5.0'
          && $values['consent_version'] === '2.1';
      }))
      ->willReturn($entity);

    $result = $this->service->recordConsent(
      'visitor-456',
      2,
      'marketing',
      'granted',
      [
        'ip_address' => '10.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'consent_version' => '2.1',
      ],
    );

    $this->assertTrue($result);
  }

  /**
   * Tests que getConsent devuelve array vacío cuando no hay registros.
   */
  public function testGetConsentReturnsEmptyArrayWhenNoRecords(): void {
    $this->storage->method('loadByProperties')
      ->with([
        'visitor_id' => 'visitor-unknown',
        'tenant_id' => 1,
      ])
      ->willReturn([]);

    $result = $this->service->getConsent('visitor-unknown', 1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getConsent devuelve registros consolidados por tipo.
   */
  public function testGetConsentReturnsConsolidatedRecords(): void {
    // Crear dos entidades mock: una de analytics y una de marketing.
    $analyticsEntity = $this->createConsentEntityMock('analytics', 'granted', '1.0', 1000);
    $marketingEntity = $this->createConsentEntityMock('marketing', 'denied', '1.0', 2000);

    $this->storage->method('loadByProperties')
      ->willReturn([$analyticsEntity, $marketingEntity]);

    $result = $this->service->getConsent('visitor-123', 1);

    $this->assertCount(2, $result);
    $this->assertArrayHasKey('analytics', $result);
    $this->assertArrayHasKey('marketing', $result);
    $this->assertEquals('granted', $result['analytics']['status']);
    $this->assertEquals('denied', $result['marketing']['status']);
  }

  /**
   * Tests que getConsent mantiene solo el registro más reciente por tipo.
   */
  public function testGetConsentKeepsLatestRecordPerType(): void {
    // Dos registros de analytics: el primero granted, el segundo withdrawn.
    $olderEntity = $this->createConsentEntityMock('analytics', 'granted', '1.0', 1000);
    $newerEntity = $this->createConsentEntityMock('analytics', 'withdrawn', '1.1', 2000);

    $this->storage->method('loadByProperties')
      ->willReturn([$olderEntity, $newerEntity]);

    $result = $this->service->getConsent('visitor-123', 1);

    $this->assertCount(1, $result);
    $this->assertEquals('withdrawn', $result['analytics']['status']);
    $this->assertEquals('1.1', $result['analytics']['consent_version']);
    $this->assertEquals(2000, $result['analytics']['created']);
  }

  /**
   * Tests que getConsent devuelve array vacío en caso de excepción.
   */
  public function testGetConsentReturnsEmptyArrayOnError(): void {
    $this->storage->method('loadByProperties')
      ->willThrowException(new \Exception('Error de consulta'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getConsent('visitor-123', 1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que revokeConsent crea una entidad con estado withdrawn.
   */
  public function testRevokeConsentCreatesWithdrawnRecord(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->expects($this->once())->method('save');

    $this->storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) {
        return $values['visitor_id'] === 'visitor-123'
          && $values['tenant_id'] === 1
          && $values['consent_type'] === 'marketing'
          && $values['status'] === 'withdrawn'
          && isset($values['revoked_at']);
      }))
      ->willReturn($entity);

    $result = $this->service->revokeConsent('visitor-123', 1, 'marketing');

    $this->assertTrue($result);
  }

  /**
   * Tests que revokeConsent devuelve FALSE cuando falla.
   */
  public function testRevokeConsentReturnsFalseOnError(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('save')->willThrowException(new \Exception('Error al guardar'));

    $this->storage->method('create')->willReturn($entity);

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->revokeConsent('visitor-123', 1, 'marketing');

    $this->assertFalse($result);
  }

  /**
   * Tests que hasConsent devuelve TRUE cuando el consentimiento está granted.
   */
  public function testHasConsentReturnsTrueWhenGranted(): void {
    $entity = $this->createConsentEntityMock('analytics', 'granted', '1.0', 1000);

    $this->storage->method('loadByProperties')
      ->willReturn([$entity]);

    $result = $this->service->hasConsent('visitor-123', 1, 'analytics');

    $this->assertTrue($result);
  }

  /**
   * Tests que hasConsent devuelve FALSE cuando el consentimiento está denied.
   */
  public function testHasConsentReturnsFalseWhenDenied(): void {
    $entity = $this->createConsentEntityMock('marketing', 'denied', '1.0', 1000);

    $this->storage->method('loadByProperties')
      ->willReturn([$entity]);

    $result = $this->service->hasConsent('visitor-123', 1, 'marketing');

    $this->assertFalse($result);
  }

  /**
   * Tests que hasConsent devuelve FALSE cuando el consentimiento fue withdrawn.
   */
  public function testHasConsentReturnsFalseWhenWithdrawn(): void {
    $entity = $this->createConsentEntityMock('analytics', 'withdrawn', '1.0', 1000);

    $this->storage->method('loadByProperties')
      ->willReturn([$entity]);

    $result = $this->service->hasConsent('visitor-123', 1, 'analytics');

    $this->assertFalse($result);
  }

  /**
   * Tests que hasConsent devuelve FALSE cuando no hay registros para el tipo.
   */
  public function testHasConsentReturnsFalseWhenNoRecordForType(): void {
    $entity = $this->createConsentEntityMock('analytics', 'granted', '1.0', 1000);

    $this->storage->method('loadByProperties')
      ->willReturn([$entity]);

    // Preguntamos por 'marketing', pero solo existe 'analytics'.
    $result = $this->service->hasConsent('visitor-123', 1, 'marketing');

    $this->assertFalse($result);
  }

  /**
   * Tests que hasConsent devuelve FALSE cuando no hay registros.
   */
  public function testHasConsentReturnsFalseWhenNoRecords(): void {
    $this->storage->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->hasConsent('visitor-123', 1, 'analytics');

    $this->assertFalse($result);
  }

  /**
   * Crea un mock de entidad consent_record con los valores indicados.
   *
   * @param string $consentType
   *   Tipo de consentimiento.
   * @param string $status
   *   Estado del consentimiento.
   * @param string $consentVersion
   *   Versión del consentimiento.
   * @param int $created
   *   Timestamp de creación.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Mock de la entidad.
   */
  protected function createConsentEntityMock(string $consentType, string $status, string $consentVersion, int $created): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);

    $fieldMap = [
      'consent_type' => $this->createFieldItemListMock($consentType),
      'status' => $this->createFieldItemListMock($status),
      'consent_version' => $this->createFieldItemListMock($consentVersion),
      'created' => $this->createFieldItemListMock((string) $created),
    ];

    $entity->method('get')
      ->willReturnCallback(function (string $fieldName) use ($fieldMap) {
        return $fieldMap[$fieldName] ?? $this->createFieldItemListMock(NULL);
      });

    return $entity;
  }

  /**
   * Crea un mock de FieldItemListInterface con un valor.
   *
   * Uses an anonymous class because PHPUnit mock objects generated from
   * interfaces do not support dynamic property access ($mock->value)
   * in PHP 8.2+.
   *
   * @param string|null $value
   *   Valor del campo.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Mock del field item list.
   */
  protected function createFieldItemListMock(?string $value): FieldItemListInterface {
    $fieldItemList = $this->createMock(FieldItemListInterface::class);
    $fieldItemList->method('__get')
      ->willReturnCallback(function (string $name) use ($value) {
        if ($name === 'value') {
          return $value;
        }
        return NULL;
      });
    // Mock __isset so the null coalescing operator (??) works correctly.
    // Without this, $mock->value ?? '' always returns '' because
    // isset($mock->value) calls __isset which returns false by default.
    $fieldItemList->method('__isset')
      ->willReturnCallback(function (string $name) use ($value) {
        if ($name === 'value') {
          return $value !== NULL;
        }
        return FALSE;
      });
    return $fieldItemList;
  }

}
