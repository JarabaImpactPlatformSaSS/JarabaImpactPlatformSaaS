<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_events\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_events\Service\EventLandingService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para EventLandingService.
 *
 * Verifica la construccion de datos para landing pages de eventos,
 * generacion de Schema.org JSON-LD y obtencion de eventos relacionados.
 *
 * @covers \Drupal\jaraba_events\Service\EventLandingService
 * @group jaraba_events
 */
class EventLandingServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected EventLandingService $service;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->eventStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function (string $entity_type_id) {
        return match ($entity_type_id) {
          'marketing_event' => $this->eventStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new EventLandingService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * Tests que generateSchemaOrg genera estructura JSON-LD para evento online.
   *
   * @covers ::generateSchemaOrg
   */
  public function testGenerateSchemaOrgOnlineEvent(): void {
    $event = $this->createMockEvent([
      'schema_type' => 'Event',
      'format' => 'online',
      'title' => 'Webinar de Marketing Digital',
      'short_desc' => 'Aprende estrategias de marketing',
      'meta_description' => '',
      'start_date' => '2026-04-15T10:00:00',
      'end_date' => '2026-04-15T12:00:00',
      'meeting_url' => 'https://zoom.us/j/123',
      'location' => '',
      'is_free' => TRUE,
      'price' => 0,
      'max_attendees' => 100,
      'created' => '2026-01-01T00:00:00',
    ]);

    $event->method('getSpotsRemaining')->willReturn(50);

    $schema = $this->service->generateSchemaOrg($event);

    $this->assertSame('https://schema.org', $schema['@context']);
    $this->assertSame('Event', $schema['@type']);
    $this->assertSame('Webinar de Marketing Digital', $schema['name']);
    $this->assertSame('2026-04-15T10:00:00', $schema['startDate']);
    $this->assertSame('2026-04-15T12:00:00', $schema['endDate']);
    $this->assertSame('https://schema.org/OnlineEventAttendanceMode', $schema['eventAttendanceMode']);
    $this->assertSame('VirtualLocation', $schema['location']['@type']);
    $this->assertTrue($schema['isAccessibleForFree']);
    $this->assertSame('0', $schema['offers']['price']);
    $this->assertSame(100, $schema['maximumAttendeeCapacity']);
    $this->assertSame(50, $schema['remainingAttendeeCapacity']);
  }

  /**
   * Tests que generateSchemaOrg genera estructura correcta para evento presencial.
   *
   * @covers ::generateSchemaOrg
   */
  public function testGenerateSchemaOrgPresencialEvent(): void {
    $event = $this->createMockEvent([
      'schema_type' => 'BusinessEvent',
      'format' => 'presencial',
      'title' => 'Feria Empleo Jaraba',
      'short_desc' => 'Feria de empleo en Jaraba',
      'meta_description' => 'Gran feria de empleo',
      'start_date' => '2026-05-20T09:00:00',
      'end_date' => '',
      'meeting_url' => '',
      'location' => 'Centro de Congresos de Jaraba',
      'is_free' => FALSE,
      'price' => 15.50,
      'max_attendees' => 0,
      'created' => '2026-02-01T00:00:00',
    ]);

    $event->method('getSpotsRemaining')->willReturn(NULL);

    $schema = $this->service->generateSchemaOrg($event);

    $this->assertSame('BusinessEvent', $schema['@type']);
    $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $schema['eventAttendanceMode']);
    $this->assertSame('Place', $schema['location']['@type']);
    $this->assertSame('Centro de Congresos de Jaraba', $schema['location']['name']);
    $this->assertSame('15.50', $schema['offers']['price']);
    $this->assertSame('EUR', $schema['offers']['priceCurrency']);
    // Sin max_attendees, no debe incluir capacidad.
    $this->assertArrayNotHasKey('maximumAttendeeCapacity', $schema);
    // Sin end_date, no debe incluirla.
    $this->assertArrayNotHasKey('endDate', $schema);
  }

  /**
   * Tests que getRelatedEvents retorna array vacio cuando no hay eventos relacionados.
   *
   * @covers ::getRelatedEvents
   */
  public function testGetRelatedEventsReturnsEmptyWhenNone(): void {
    $event = $this->createMockEvent([
      'tenant_id_target' => 42,
    ]);
    $event->method('id')->willReturn(1);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->eventStorage
      ->method('getQuery')
      ->willReturn($query);

    $related = $this->service->getRelatedEvents($event, 3);

    $this->assertIsArray($related);
    $this->assertEmpty($related);
  }

  /**
   * Tests que getRelatedEvents retorna datos formateados de eventos.
   *
   * @covers ::getRelatedEvents
   */
  public function testGetRelatedEventsReturnsFormattedData(): void {
    $event = $this->createMockEvent([
      'tenant_id_target' => 10,
    ]);
    $event->method('id')->willReturn(1);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([2]);

    // Crear un evento relacionado mock.
    $relatedEvent = $this->createMockEvent([
      'title' => 'Taller complementario',
      'slug' => 'taller-complementario',
      'event_type' => 'workshop',
      'format' => 'online',
      'start_date' => '2026-06-01T10:00:00',
      'short_desc' => 'Taller de habilidades',
      'is_free' => TRUE,
      'price' => 0,
    ]);
    $relatedEvent->method('id')->willReturn(2);
    $relatedEvent->method('getSpotsRemaining')->willReturn(20);

    $this->eventStorage
      ->method('getQuery')
      ->willReturn($query);

    $this->eventStorage
      ->method('loadMultiple')
      ->with([2])
      ->willReturn([$relatedEvent]);

    $related = $this->service->getRelatedEvents($event, 3);

    $this->assertCount(1, $related);
    $this->assertSame(2, $related[0]['id']);
    $this->assertSame('Taller complementario', $related[0]['title']);
    $this->assertSame('taller-complementario', $related[0]['slug']);
    $this->assertSame('workshop', $related[0]['event_type']);
    $this->assertTrue($related[0]['is_free']);
    $this->assertSame(20, $related[0]['spots_remaining']);
  }

  /**
   * Tests que buildLandingData construye la estructura completa de datos.
   *
   * @covers ::buildLandingData
   */
  public function testBuildLandingDataReturnsCompleteStructure(): void {
    // Evento futuro publicado.
    $futureDate = date('Y-m-d\TH:i:s', strtotime('+30 days'));
    $event = $this->createMockEvent([
      'title' => 'Conferencia SaaS',
      'slug' => 'conferencia-saas',
      'event_type' => 'conference',
      'format' => 'online',
      'start_date' => $futureDate,
      'end_date' => '',
      'timezone' => 'Europe/Madrid',
      'description' => '<p>Gran conferencia SaaS</p>',
      'short_desc' => 'Conferencia sobre SaaS',
      'speakers' => 'Juan, Ana',
      'meeting_url' => 'https://meet.example.com/123',
      'location' => '',
      'max_attendees' => 200,
      'current_attendees' => 50,
      'is_free' => TRUE,
      'price' => 0,
      'early_bird_price' => 0,
      'early_bird_deadline' => '',
      'featured' => FALSE,
      'status_event' => 'published',
      'schema_type' => 'Event',
      'meta_description' => 'Meta conferencia SaaS',
      'created' => '2026-01-15T00:00:00',
      'tenant_id_target' => 5,
    ]);
    $event->method('id')->willReturn(10);
    $event->method('getSpotsRemaining')->willReturn(150);

    // Mock para getRelatedEvents: sin resultados.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->eventStorage
      ->method('getQuery')
      ->willReturn($query);

    $data = $this->service->buildLandingData($event);

    // Verificar estructura general.
    $this->assertArrayHasKey('event', $data);
    $this->assertArrayHasKey('schema_org', $data);
    $this->assertArrayHasKey('meta_tags', $data);
    $this->assertArrayHasKey('related_events', $data);
    $this->assertArrayHasKey('countdown', $data);
    $this->assertArrayHasKey('registration_open', $data);
    $this->assertArrayHasKey('spots_remaining', $data);

    // Evento futuro y publicado: registro abierto.
    $this->assertTrue($data['registration_open']);
    // Evento futuro: countdown no debe ser null.
    $this->assertNotNull($data['countdown']);
    $this->assertSame(150, $data['spots_remaining']);
    $this->assertSame('Conferencia SaaS', $data['event']['title']);
  }

  /**
   * Crea un mock de evento de marketing con campos configurables.
   *
   * @param array $fields
   *   Mapa de campo => valor para el mock.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad de evento.
   */
  protected function createMockEvent(array $fields): MockObject {
    $event = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);

    $event->method('get')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        // Campo especial para tenant_id con target_id.
        if ($field_name === 'tenant_id') {
          $field = new \stdClass();
          $field->target_id = $fields['tenant_id_target'] ?? NULL;
          $field->value = $fields['tenant_id_target'] ?? NULL;
          return $field;
        }

        // Campo especial para meeting_url con uri.
        if ($field_name === 'meeting_url') {
          $field = new \stdClass();
          $field->uri = $fields['meeting_url'] ?? '';
          $field->value = $fields['meeting_url'] ?? '';
          return $field;
        }

        // Campo especial para image: siempre vacio en tests.
        if ($field_name === 'image') {
          $field = $this->createMock(FieldItemListInterface::class);
          $field->method('isEmpty')->willReturn(TRUE);
          return $field;
        }

        $field = new \stdClass();
        $field->value = $fields[$field_name] ?? NULL;
        return $field;
      });

    return $event;
  }

}
