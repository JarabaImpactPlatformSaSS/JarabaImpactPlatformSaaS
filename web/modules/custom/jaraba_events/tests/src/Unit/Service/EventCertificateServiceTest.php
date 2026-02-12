<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_events\Service\EventCertificateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para EventCertificateService.
 *
 * Verifica la generación, obtención de datos y verificación
 * de certificados de asistencia a eventos.
 *
 * @covers \Drupal\jaraba_events\Service\EventCertificateService
 * @group jaraba_events
 */
class EventCertificateServiceTest extends TestCase {

  /**
   * Servicio bajo test.
   */
  protected EventCertificateService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mock del storage de registros.
   */
  protected EntityStorageInterface&MockObject $registrationStorage;

  /**
   * Mock del storage de eventos.
   */
  protected EntityStorageInterface&MockObject $eventStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->registrationStorage = $this->createMock(EntityStorageInterface::class);
    $this->eventStorage = $this->createMock(EntityStorageInterface::class);

    // Configurar el entity type manager para devolver los storages correctos.
    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function (string $entity_type_id) {
        return match ($entity_type_id) {
          'event_registration' => $this->registrationStorage,
          'marketing_event' => $this->eventStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new EventCertificateService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que generateCertificate devuelve error cuando el registro no existe.
   *
   * @covers ::generateCertificate
   */
  public function testGenerateCertificateNotFound(): void {
    $this->registrationStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        'Intento de generar certificado para registro inexistente: @id',
        $this->callback(function (array $context): bool {
          return $context['@id'] === 999;
        })
      );

    $result = $this->service->generateCertificate(999);

    $this->assertFalse($result['success']);
    $this->assertEmpty($result['certificate_url']);
    $this->assertEmpty($result['certificate_data']);
    $this->assertEquals('Registro no encontrado.', $result['error']);
  }

  /**
   * Tests que getCertificateData devuelve array vacío cuando el registro no existe.
   *
   * @covers ::getCertificateData
   */
  public function testGetCertificateDataEmpty(): void {
    $this->registrationStorage
      ->method('load')
      ->with(888)
      ->willReturn(NULL);

    $result = $this->service->getCertificateData(888);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que verifyCertificate devuelve inválido cuando el código no existe.
   *
   * @covers ::verifyCertificate
   */
  public function testVerifyCertificateInvalid(): void {
    $this->registrationStorage
      ->method('loadByProperties')
      ->with(['ticket_code' => 'INVALID-CODE'])
      ->willReturn([]);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        'Verificación de certificado fallida: código @code no encontrado.',
        $this->callback(function (array $context): bool {
          return $context['@code'] === 'INVALID-CODE';
        })
      );

    $result = $this->service->verifyCertificate('INVALID-CODE');

    $this->assertFalse($result['valid']);
    $this->assertEmpty($result['certificate_data']);
    $this->assertEquals('Certificado no encontrado con el código proporcionado.', $result['error']);
  }

  /**
   * Tests que verifyCertificate devuelve error cuando el código está vacío.
   *
   * @covers ::verifyCertificate
   */
  public function testVerifyCertificateEmptyCode(): void {
    $result = $this->service->verifyCertificate('');

    $this->assertFalse($result['valid']);
    $this->assertEquals('Código de verificación vacío.', $result['error']);
  }

  /**
   * Tests que generateCertificate falla cuando el registro no tiene estado 'attended'.
   *
   * @covers ::generateCertificate
   */
  public function testGenerateCertificateNotAttended(): void {
    $registration = $this->createMockRegistration('confirmed');

    $this->registrationStorage
      ->method('load')
      ->with(42)
      ->willReturn($registration);

    $result = $this->service->generateCertificate(42);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('confirmed', $result['error']);
  }

  /**
   * Crea un mock de registro con un estado determinado.
   *
   * @param string $status
   *   Estado del registro.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad de registro.
   */
  protected function createMockRegistration(string $status): MockObject {
    $registration = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $statusField = $this->createMock(FieldItemListInterface::class);
    $statusField->value = $status;

    $registration->method('get')
      ->willReturnCallback(function (string $field_name) use ($statusField) {
        if ($field_name === 'registration_status') {
          return $statusField;
        }
        $field = $this->createMock(FieldItemListInterface::class);
        $field->value = NULL;
        return $field;
      });

    return $registration;
  }

}
