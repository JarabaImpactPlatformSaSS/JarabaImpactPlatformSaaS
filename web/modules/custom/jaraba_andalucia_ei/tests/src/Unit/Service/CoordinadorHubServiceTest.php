<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
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

}
