<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\CoordinadorHubService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for CoordinadorHubService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\CoordinadorHubService
 * @group jaraba_andalucia_ei
 */
class CoordinadorHubServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;
  protected CoordinadorHubService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new CoordinadorHubService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::changeParticipantPhase
   */
  public function testChangePhaseRejectsInvalidPhase(): void {
    $result = $this->service->changeParticipantPhase(1, 'invalid_phase');
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no valida', $result['message']);
  }

  /**
   * @covers ::changeParticipantPhase
   */
  public function testChangePhaseAcceptsValidPhases(): void {
    $validPhases = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'];

    foreach ($validPhases as $phase) {
      // Mock entity loading that returns null (not found).
      $storage = $this->createMock(EntityStorageInterface::class);
      $storage->method('load')->willReturn(NULL);
      $this->entityTypeManager->method('getStorage')
        ->with('programa_participante_ei')
        ->willReturn($storage);

      $result = $this->service->changeParticipantPhase(999, $phase);
      // Will fail because entity not found, but not because of invalid phase.
      $this->assertStringNotContainsString('no valida', $result['message']);
    }
  }

  /**
   * @covers ::rejectSolicitud
   */
  public function testRejectSolicitudNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('solicitud_ei')
      ->willReturn($storage);

    $result = $this->service->rejectSolicitud(999, 'Motivo de prueba');
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrada', $result['message']);
  }

  /**
   * @covers ::getSolicitudes
   */
  public function testGetSolicitudesReturnsEmptyOnError(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \RuntimeException('Storage error'));

    $result = $this->service->getSolicitudes(NULL);
    $this->assertSame([], $result['items']);
    $this->assertSame(0, $result['total']);
  }

  /**
   * @covers ::getHubKpis
   */
  public function testGetHubKpisReturnsZerosOnError(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \RuntimeException('Storage error'));

    $kpis = $this->service->getHubKpis(NULL);
    $this->assertSame(0, $kpis['active_participants']);
    $this->assertSame(0, $kpis['pending_solicitudes']);
    $this->assertSame(0, $kpis['completed_sessions']);
    $this->assertSame(0, $kpis['insertion_rate']);
  }

  /**
   * @covers ::getUpcomingSessions
   */
  public function testGetUpcomingSessionsReturnsEmptyWithoutEntity(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('mentoring_session')
      ->willReturn(FALSE);

    $result = $this->service->getUpcomingSessions(NULL);
    $this->assertSame([], $result);
  }

  /**
   * @covers ::getCalendarEvents
   */
  public function testGetCalendarEventsReturnsEmptyWithoutEntity(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('sesion_programada_ei')
      ->willReturn(FALSE);

    $result = $this->service->getCalendarEvents(NULL, '2026-03-01', '2026-03-31');
    $this->assertSame([], $result);
  }

  /**
   * @covers ::getCalendarEvents
   */
  public function testGetCalendarEventsHandlesException(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->with('sesion_programada_ei')
      ->willReturn(TRUE);

    $this->entityTypeManager->method('getStorage')
      ->with('sesion_programada_ei')
      ->willThrowException(new \RuntimeException('Storage error'));

    $result = $this->service->getCalendarEvents(1, '2026-03-01', '2026-03-31');
    $this->assertSame([], $result);
  }

  /**
   * @covers ::rescheduleSession
   */
  public function testRescheduleSessionNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(999)->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')
      ->with('sesion_programada_ei')
      ->willReturn($storage);

    $result = $this->service->rescheduleSession(999, '2026-04-01', '10:00', '11:00', NULL);
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrada', $result['message']);
  }

  /**
   * @covers ::rescheduleSession
   */
  public function testRescheduleSessionRejectsCompletedSessions(): void {
    $sesion = new class () {

      /**
       *
       */
      public function get(string $field): object {
        return match ($field) {
          'estado' => (object) ['value' => 'completada'],
          'tenant_id' => (object) ['target_id' => NULL],
          default => (object) ['value' => NULL],
        };
      }

    };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($sesion);
    $this->entityTypeManager->method('getStorage')
      ->with('sesion_programada_ei')
      ->willReturn($storage);

    $result = $this->service->rescheduleSession(1, '2026-04-01', '10:00', '11:00', NULL);
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('completadas o canceladas', $result['message']);
  }

  /**
   * @covers ::rescheduleSession
   */
  public function testRescheduleSessionRejectsTenantMismatch(): void {
    $sesion = new class () {

      /**
       *
       */
      public function get(string $field): object {
        return match ($field) {
          'estado' => (object) ['value' => 'programada'],
          'tenant_id' => (object) ['target_id' => 5],
          default => (object) ['value' => NULL],
        };
      }

    };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(1)->willReturn($sesion);
    $this->entityTypeManager->method('getStorage')
      ->with('sesion_programada_ei')
      ->willReturn($storage);

    // ACCESS-STRICT-001: tenant 10 !== entity tenant 5.
    $result = $this->service->rescheduleSession(1, '2026-04-01', '10:00', '11:00', 10);
    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrada', $result['message']);
  }

}
