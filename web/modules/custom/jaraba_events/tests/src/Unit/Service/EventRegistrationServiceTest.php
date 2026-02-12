<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_email\Service\SequenceManagerService;
use Drupal\jaraba_events\Entity\EventRegistration;
use Drupal\jaraba_events\Entity\MarketingEvent;
use Drupal\jaraba_events\Service\EventRegistrationService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para EventRegistrationService.
 *
 * Verifica el flujo completo de registro de asistentes: registro,
 * confirmacion por token, cancelacion, check-in y estadisticas.
 *
 * @covers \Drupal\jaraba_events\Service\EventRegistrationService
 * @group jaraba_events
 */
class EventRegistrationServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected EventRegistrationService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del usuario actual.
   */
  protected AccountProxyInterface&MockObject $currentUser;

  /**
   * Mock del contexto de tenant.
   */
  protected TenantContextService&MockObject $tenantContext;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mock del servicio de secuencias de email.
   */
  protected SequenceManagerService&MockObject $sequenceManager;

  /**
   * Mock del storage de eventos.
   */
  protected EntityStorageInterface&MockObject $eventStorage;

  /**
   * Mock del storage de registros.
   */
  protected EntityStorageInterface&MockObject $registrationStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->sequenceManager = $this->createMock(SequenceManagerService::class);

    $this->eventStorage = $this->createMock(EntityStorageInterface::class);
    $this->registrationStorage = $this->createMock(EntityStorageInterface::class);

    // Configurar el entity type manager para devolver los storages correctos.
    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function (string $entity_type_id) {
        return match ($entity_type_id) {
          'marketing_event' => $this->eventStorage,
          'event_registration' => $this->registrationStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->currentUser
      ->method('id')
      ->willReturn(1);

    $this->service = new EventRegistrationService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->tenantContext,
      $this->logger,
      $this->sequenceManager,
    );
  }

  /**
   * Tests que register lanza excepcion cuando el evento no existe.
   *
   * @covers ::register
   */
  public function testRegisterThrowsExceptionWhenEventNotFound(): void {
    $this->eventStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->expectException(\Drupal\jaraba_events\Exception\EventNotOpenException::class);
    $this->expectExceptionMessage('El evento solicitado no existe.');

    $this->service->register(999, ['name' => 'Test', 'email' => 'test@test.com']);
  }

  /**
   * Tests que register lanza excepcion cuando el evento no esta publicado.
   *
   * @covers ::register
   */
  public function testRegisterThrowsExceptionWhenEventNotPublished(): void {
    $event = $this->createMockEvent('draft', TRUE, 100, 0);
    $event->method('label')->willReturn('Evento Borrador');

    $this->eventStorage
      ->method('load')
      ->with(1)
      ->willReturn($event);

    $this->expectException(\Drupal\jaraba_events\Exception\EventNotOpenException::class);
    $this->expectExceptionMessage('no est');

    $this->service->register(1, ['name' => 'Test', 'email' => 'test@test.com']);
  }

  /**
   * Tests que register lanza excepcion cuando el email ya esta registrado.
   *
   * @covers ::register
   */
  public function testRegisterThrowsDuplicateException(): void {
    $event = $this->createMockEvent('published', TRUE, 100, 0);

    $this->eventStorage
      ->method('load')
      ->with(1)
      ->willReturn($event);

    // Query de duplicados: retorna 1 (ya registrado).
    $duplicateQuery = $this->createMock(QueryInterface::class);
    $duplicateQuery->method('accessCheck')->willReturnSelf();
    $duplicateQuery->method('condition')->willReturnSelf();
    $duplicateQuery->method('count')->willReturnSelf();
    $duplicateQuery->method('execute')->willReturn(1);

    $this->registrationStorage
      ->method('getQuery')
      ->willReturn($duplicateQuery);

    $this->expectException(\Drupal\jaraba_events\Exception\DuplicateRegistrationException::class);

    $this->service->register(1, ['name' => 'Duplicado', 'email' => 'dup@test.com']);
  }

  /**
   * Tests que confirmByToken lanza excepcion con token invalido.
   *
   * @covers ::confirmByToken
   */
  public function testConfirmByTokenThrowsExceptionForInvalidToken(): void {
    $this->registrationStorage
      ->method('loadByProperties')
      ->with(['confirmation_token' => 'invalid_token_xyz'])
      ->willReturn([]);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Token de confirmaci');

    $this->service->confirmByToken('invalid_token_xyz');
  }

  /**
   * Tests que confirmByToken confirma un registro pendiente.
   *
   * @covers ::confirmByToken
   */
  public function testConfirmByTokenConfirmsPendingRegistration(): void {
    $registration = $this->createMock(EventRegistration::class);

    $statusField = new \stdClass();
    $statusField->value = 'pending';

    $nameField = new \stdClass();
    $nameField->value = 'Juan Garcia';

    $eventIdField = new \stdClass();
    $eventIdField->target_id = 1;

    $registration->method('get')
      ->willReturnCallback(function (string $field_name) use ($statusField, $nameField, $eventIdField) {
        return match ($field_name) {
          'registration_status' => $statusField,
          'attendee_name' => $nameField,
          'event_id' => $eventIdField,
          default => new \stdClass(),
        };
      });

    $registration->expects($this->once())
      ->method('set')
      ->with('registration_status', 'confirmed');

    $registration->expects($this->once())
      ->method('save');

    $this->registrationStorage
      ->method('loadByProperties')
      ->with(['confirmation_token' => 'valid_token_abc123'])
      ->willReturn([$registration]);

    $this->logger->expects($this->once())
      ->method('info');

    $result = $this->service->confirmByToken('valid_token_abc123');

    $this->assertSame($registration, $result);
  }

  /**
   * Tests que getEventStats calcula estadisticas correctamente con registros.
   *
   * @covers ::getEventStats
   */
  public function testGetEventStatsCalculatesCorrectly(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3, 4, 5]);

    $this->registrationStorage
      ->method('getQuery')
      ->willReturn($query);

    // 5 registros con diferentes estados.
    $regs = [
      $this->createMockRegistration('confirmed', 'free', 0.0, 0),
      $this->createMockRegistration('attended', 'paid', 25.0, 5),
      $this->createMockRegistration('attended', 'paid', 30.0, 4),
      $this->createMockRegistration('waitlisted', 'pending_payment', 0.0, 0),
      $this->createMockRegistration('cancelled', 'free', 0.0, 0),
    ];

    $this->registrationStorage
      ->method('loadMultiple')
      ->willReturn($regs);

    $stats = $this->service->getEventStats(1);

    // 4 no cancelados.
    $this->assertSame(4, $stats['total_registrations']);
    $this->assertSame(1, $stats['confirmed']);
    $this->assertSame(2, $stats['attended']);
    $this->assertSame(1, $stats['waitlisted']);
    $this->assertSame(1, $stats['cancelled']);
    $this->assertSame(0, $stats['no_show']);
    // Revenue: 25 + 30 = 55.
    $this->assertSame(55.0, $stats['revenue']);
    // Rating promedio: (5 + 4) / 2 = 4.5.
    $this->assertSame(4.5, $stats['average_rating']);
    // Tasa asistencia: 2 attended de (1 confirmed + 2 attended + 0 no_show) = 66.7%.
    $this->assertSame(66.7, $stats['attendance_rate']);
  }

  /**
   * Tests que getEventStats retorna metricas cero cuando no hay registros.
   *
   * @covers ::getEventStats
   */
  public function testGetEventStatsReturnsZerosWhenEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->registrationStorage
      ->method('getQuery')
      ->willReturn($query);

    $stats = $this->service->getEventStats(999);

    $this->assertSame(0, $stats['total_registrations']);
    $this->assertSame(0, $stats['confirmed']);
    $this->assertSame(0, $stats['attended']);
    $this->assertSame(0.0, $stats['attendance_rate']);
    $this->assertSame(0.0, $stats['average_rating']);
    $this->assertSame(0.0, $stats['revenue']);
  }

  /**
   * Crea un mock de evento de marketing con parametros basicos.
   *
   * @param string $status
   *   Estado del evento (published, draft, etc.).
   * @param bool $is_free
   *   Si el evento es gratuito.
   * @param int $max_attendees
   *   Aforo maximo.
   * @param int $current_attendees
   *   Asistentes actuales.
   *
   * @return \Drupal\jaraba_events\Entity\MarketingEvent&\PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad de evento.
   */
  protected function createMockEvent(string $status, bool $is_free, int $max_attendees, int $current_attendees): MarketingEvent&MockObject {
    $event = $this->createMock(MarketingEvent::class);

    $fields = [
      'status_event' => $status,
      'is_free' => $is_free,
      'max_attendees' => $max_attendees,
      'current_attendees' => $current_attendees,
    ];

    $event->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        if ($field_name === 'tenant_id') {
          $field = new \stdClass();
          $field->target_id = 1;
          return $field;
        }
        if ($field_name === 'email_sequence_id') {
          $field = $this->createMock(\Drupal\Core\Field\FieldItemListInterface::class);
          $field->method('isEmpty')->willReturn(TRUE);
          return $field;
        }
        $field = new \stdClass();
        $field->value = $fields[$field_name] ?? NULL;
        return $field;
      });

    $event->method('hasField')
      ->willReturn(TRUE);

    return $event;
  }

  /**
   * Crea un mock de registro de evento.
   *
   * @param string $status
   *   Estado del registro.
   * @param string $payment_status
   *   Estado del pago.
   * @param float $amount_paid
   *   Importe pagado.
   * @param int $rating
   *   Valoracion del asistente.
   *
   * @return \Drupal\jaraba_events\Entity\EventRegistration&\PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad de registro.
   */
  protected function createMockRegistration(string $status, string $payment_status, float $amount_paid, int $rating): EventRegistration&MockObject {
    $registration = $this->createMock(EventRegistration::class);

    $fields = [
      'registration_status' => $status,
      'payment_status' => $payment_status,
      'amount_paid' => $amount_paid,
      'rating' => $rating,
    ];

    $registration->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        $field = new \stdClass();
        $field->value = $fields[$field_name] ?? NULL;
        return $field;
      });

    return $registration;
  }

}
