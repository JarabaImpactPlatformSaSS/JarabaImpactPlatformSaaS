<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_events\Service\EventAnalyticsService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para EventAnalyticsService.
 *
 * Verifica el calculo de metricas de rendimiento de eventos,
 * metricas agregadas por tenant y funnels de conversion.
 *
 * @covers \Drupal\jaraba_events\Service\EventAnalyticsService
 * @group jaraba_events
 */
class EventAnalyticsServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected EventAnalyticsService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del contexto de tenant.
   */
  protected TenantContextService&MockObject $tenantContext;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

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
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);

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

    $this->service = new EventAnalyticsService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests que getEventPerformance retorna array vacio cuando el evento no existe.
   *
   * @covers ::getEventPerformance
   */
  public function testGetEventPerformanceReturnsEmptyWhenEventNotFound(): void {
    $this->eventStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->getEventPerformance(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que getEventPerformance calcula metricas correctamente con registros.
   *
   * @covers ::getEventPerformance
   */
  public function testGetEventPerformanceCalculatesMetrics(): void {
    // Mock del evento.
    $event = $this->createMockEvent(100, 'Webinar IA', 'webinar', '2026-03-15T10:00:00', 'published');

    $this->eventStorage
      ->method('load')
      ->with(1)
      ->willReturn($event);

    // Mock de la query de registros.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([10, 20, 30]);

    $this->registrationStorage
      ->method('getQuery')
      ->willReturn($query);

    // Crear 3 registros mock: 1 attended (pagado), 1 confirmed, 1 cancelled.
    $reg1 = $this->createMockRegistration('attended', 'paid', 25.00, 4, 'web');
    $reg2 = $this->createMockRegistration('confirmed', 'pending_payment', 0.0, 0, 'linkedin');
    $reg3 = $this->createMockRegistration('cancelled', 'free', 0.0, 0, 'web');

    $this->registrationStorage
      ->method('loadMultiple')
      ->with([10, 20, 30])
      ->willReturn([$reg1, $reg2, $reg3]);

    $result = $this->service->getEventPerformance(1);

    $this->assertSame(1, $result['event']['id']);
    $this->assertSame('Webinar IA', $result['event']['title']);
    // 2 registros no cancelados (attended + confirmed).
    $this->assertSame(2, $result['registrations']);
    // 1 attended de 2 activos = 50%.
    $this->assertSame(50.0, $result['attendance_rate']);
    // 2 de 100 plazas = 2%.
    $this->assertSame(2.0, $result['fill_rate']);
    // Solo reg1 esta pagado con 25.00.
    $this->assertSame(25.0, $result['revenue']);
    // Solo reg1 tiene rating 4.
    $this->assertSame(4.0, $result['avg_rating']);
    // Fuentes: web=2 (reg1+reg3), linkedin=1.
    $this->assertArrayHasKey('web', $result['conversion_sources']);
    $this->assertArrayHasKey('linkedin', $result['conversion_sources']);
  }

  /**
   * Tests que getConversionFunnel retorna funnel con 4 etapas.
   *
   * @covers ::getConversionFunnel
   */
  public function testGetConversionFunnelReturnsCorrectStructure(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([1, 2, 3, 4]);

    $this->registrationStorage
      ->method('getQuery')
      ->willReturn($query);

    // 4 registros: confirmed, confirmed, attended (con rating), cancelled.
    $reg1 = $this->createMockRegistration('confirmed', 'free', 0.0, 0, 'web');
    $reg2 = $this->createMockRegistration('confirmed', 'free', 0.0, 0, 'web');
    $reg3 = $this->createMockRegistration('attended', 'paid', 10.0, 5, 'web');
    $reg4 = $this->createMockRegistration('cancelled', 'free', 0.0, 0, 'web');

    $this->registrationStorage
      ->method('loadMultiple')
      ->willReturn([$reg1, $reg2, $reg3, $reg4]);

    $funnel = $this->service->getConversionFunnel(1);

    $this->assertCount(4, $funnel);
    // Etapa 1: Registros (3 no cancelados).
    $this->assertSame('Registros', $funnel[0]['label']);
    $this->assertSame(3, $funnel[0]['count']);
    $this->assertSame(100.0, $funnel[0]['rate']);
    // Etapa 2: Confirmados (confirmed + attended = 3).
    $this->assertSame('Confirmados', $funnel[1]['label']);
    $this->assertSame(3, $funnel[1]['count']);
    // Etapa 3: Asistieron (1 attended).
    $this->assertSame('Asistieron', $funnel[2]['label']);
    $this->assertSame(1, $funnel[2]['count']);
    // Etapa 4: Feedback (1 con rating > 0).
    $this->assertSame('Feedback', $funnel[3]['label']);
    $this->assertSame(1, $funnel[3]['count']);
  }

  /**
   * Tests que getConversionFunnel devuelve conteos cero sin registros.
   *
   * @covers ::getConversionFunnel
   */
  public function testGetConversionFunnelEmptyRegistrations(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->registrationStorage
      ->method('getQuery')
      ->willReturn($query);

    $funnel = $this->service->getConversionFunnel(999);

    $this->assertCount(4, $funnel);
    $this->assertSame(0, $funnel[0]['count']);
    $this->assertSame(100.0, $funnel[0]['rate']);
    $this->assertSame(0, $funnel[1]['count']);
    $this->assertSame(0.0, $funnel[1]['rate']);
  }

  /**
   * Tests que getTenantEventMetrics retorna metricas vacias cuando no hay eventos.
   *
   * @covers ::getTenantEventMetrics
   */
  public function testGetTenantEventMetricsReturnsEmptyWhenNoEvents(): void {
    // Query para eventos del tenant: sin resultados.
    $eventQuery = $this->createMock(QueryInterface::class);
    $eventQuery->method('accessCheck')->willReturnSelf();
    $eventQuery->method('condition')->willReturnSelf();
    $eventQuery->method('execute')->willReturn([]);

    $this->eventStorage
      ->method('getQuery')
      ->willReturn($eventQuery);

    $metrics = $this->service->getTenantEventMetrics(42, '30d');

    $this->assertSame(0, $metrics['total_events']);
    $this->assertSame(0, $metrics['total_registrations']);
    $this->assertSame(0.0, $metrics['total_revenue']);
    $this->assertSame(0.0, $metrics['avg_attendance_rate']);
    $this->assertSame(0.0, $metrics['avg_fill_rate']);
    $this->assertIsArray($metrics['events_by_type']);
    $this->assertEmpty($metrics['events_by_type']);
  }

  /**
   * Crea un mock de evento de marketing.
   *
   * @param int $max_attendees
   *   Aforo maximo.
   * @param string $title
   *   Titulo del evento.
   * @param string $event_type
   *   Tipo de evento.
   * @param string $start_date
   *   Fecha de inicio ISO.
   * @param string $status
   *   Estado del evento.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad de evento.
   */
  protected function createMockEvent(int $max_attendees, string $title, string $event_type, string $start_date, string $status): MockObject {
    $event = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $event->method('id')->willReturn(1);

    $fields = [
      'title' => $title,
      'event_type' => $event_type,
      'start_date' => $start_date,
      'status_event' => $status,
      'max_attendees' => $max_attendees,
    ];

    $event->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        $field = new \stdClass();
        $field->value = $fields[$field_name] ?? NULL;
        return $field;
      });

    return $event;
  }

  /**
   * Crea un mock de registro de evento con los datos proporcionados.
   *
   * @param string $status
   *   Estado del registro.
   * @param string $payment_status
   *   Estado del pago.
   * @param float $amount_paid
   *   Importe pagado.
   * @param int $rating
   *   Valoracion del asistente.
   * @param string $source
   *   Fuente de registro.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad de registro.
   */
  protected function createMockRegistration(string $status, string $payment_status, float $amount_paid, int $rating, string $source): MockObject {
    $registration = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $fields = [
      'registration_status' => $status,
      'payment_status' => $payment_status,
      'amount_paid' => $amount_paid,
      'rating' => $rating,
      'source' => $source,
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
