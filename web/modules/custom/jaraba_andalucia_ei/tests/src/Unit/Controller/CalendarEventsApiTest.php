<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController;
use Drupal\jaraba_andalucia_ei\Service\CoordinadorHubService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests calendar events API endpoint.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Controller\CoordinadorHubApiController
 * @group jaraba_andalucia_ei
 */
class CalendarEventsApiTest extends UnitTestCase {

  protected CoordinadorHubApiController $controller;
  protected CoordinadorHubService $hubService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->hubService = $this->createMock(CoordinadorHubService::class);

    // Use reflection to instantiate controller without full DI.
    $this->controller = new class (
      $this->createMock(EntityTypeManagerInterface::class),
      $this->hubService,
      $this->createMock(LoggerInterface::class),
    ) extends CoordinadorHubApiController {
      // Override to avoid tenant context dependency.
    };
  }

  /**
   * @covers ::calendarEvents
   */
  public function testCalendarEventsRequiresStartEnd(): void {
    $request = new Request();
    $response = $this->controller->calendarEvents($request);

    $this->assertSame(400, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertFalse($data['success']);
    $this->assertStringContainsString('start', $data['message']);
  }

  /**
   * @covers ::calendarEvents
   */
  public function testCalendarEventsRejectsInvalidTipoSesion(): void {
    $request = new Request([
      'start' => '2026-03-01',
      'end' => '2026-03-31',
      'tipo_sesion' => 'invalid_tipo',
    ]);

    $response = $this->controller->calendarEvents($request);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::calendarEvents
   */
  public function testCalendarEventsRejectsInvalidModalidad(): void {
    $request = new Request([
      'start' => '2026-03-01',
      'end' => '2026-03-31',
      'modalidad' => 'telepresencial',
    ]);

    $response = $this->controller->calendarEvents($request);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::calendarEvents
   */
  public function testCalendarEventsRejectsInvalidEstado(): void {
    $request = new Request([
      'start' => '2026-03-01',
      'end' => '2026-03-31',
      'estado' => 'inexistente',
    ]);

    $response = $this->controller->calendarEvents($request);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::calendarEvents
   */
  public function testCalendarEventsRejectsInvalidFase(): void {
    $request = new Request([
      'start' => '2026-03-01',
      'end' => '2026-03-31',
      'fase_programa' => 'invalid_fase',
    ]);

    $response = $this->controller->calendarEvents($request);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::calendarEvents
   */
  public function testCalendarEventsReturnsFlat(): void {
    $events = [
      [
        'id' => 1,
        'title' => 'Sesion test',
        'start' => '2026-03-15T09:00:00',
        'end' => '2026-03-15T10:00:00',
      ],
    ];

    $this->hubService->method('getCalendarEvents')
      ->willReturn($events);

    $request = new Request(['start' => '2026-03-01', 'end' => '2026-03-31']);
    $response = $this->controller->calendarEvents($request);

    $this->assertSame(200, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    // FullCalendar expects flat array, not wrapped.
    $this->assertIsArray($data);
    $this->assertSame(1, $data[0]['id']);
  }

  /**
   * @covers ::rescheduleSession
   */
  public function testRescheduleRequiresNewDate(): void {
    $request = Request::create('/api/v1/andalucia-ei/hub/session/1/reschedule', 'POST', [], [], [], [], '{}');

    $response = $this->controller->rescheduleSession(1, $request);
    $this->assertSame(400, $response->getStatusCode());
  }

  /**
   * @covers ::rescheduleSession
   */
  public function testRescheduleRejectsInvalidDateFormat(): void {
    $request = Request::create(
      '/api/v1/andalucia-ei/hub/session/1/reschedule', 'POST',
      [], [], [], [],
      json_encode(['newDate' => '15/03/2026'])
    );

    $response = $this->controller->rescheduleSession(1, $request);
    $this->assertSame(400, $response->getStatusCode());
    $data = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('formato', mb_strtolower($data['message']));
  }

}
