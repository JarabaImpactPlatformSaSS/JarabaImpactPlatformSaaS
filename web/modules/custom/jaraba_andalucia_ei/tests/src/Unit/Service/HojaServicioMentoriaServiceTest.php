<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\jaraba_andalucia_ei\Service\HojaServicioMentoriaService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para HojaServicioMentoriaService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\HojaServicioMentoriaService
 * @group jaraba_andalucia_ei
 */
class HojaServicioMentoriaServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected HojaServicioMentoriaService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock expediente service.
   */
  protected ExpedienteService $expedienteService;

  /**
   * Mock storage para mentoring_session.
   */
  protected EntityStorageInterface $sessionStorage;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->expedienteService = $this->createMock(ExpedienteService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->sessionStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'mentoring_session') {
          return $this->sessionStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new HojaServicioMentoriaService(
      $this->entityTypeManager,
      $this->expedienteService,
      NULL,
      $this->logger,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConBrandedPdfNull(): void {
    $service = new HojaServicioMentoriaService(
      $this->entityTypeManager,
      $this->expedienteService,
      NULL,
      $this->logger,
    );

    $this->assertInstanceOf(HojaServicioMentoriaService::class, $service);
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConTodosLosServicios(): void {
    $brandedPdf = new class {
      public function generateReport(array $data, ?int $tenantId = NULL): string {
        return 'private://reports/test.pdf';
      }
    };

    $service = new HojaServicioMentoriaService(
      $this->entityTypeManager,
      $this->expedienteService,
      $brandedPdf,
      $this->logger,
    );

    $this->assertInstanceOf(HojaServicioMentoriaService::class, $service);
  }

  /**
   * @covers ::generarHojaServicio
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarHojaServicioDevuelveNullCuandoSessionNoExiste(): void {
    $this->sessionStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Session @id not found'),
        $this->equalTo(['@id' => 999]),
      );

    $result = $this->service->generarHojaServicio(999);
    $this->assertNull($result);
  }

  /**
   * @covers ::generarHojaServicio
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarHojaServicioDevuelveNullCuandoSessionNoCompletada(): void {
    $session = $this->createSessionMock('in_progress', 'some notes');

    $this->sessionStorage->method('load')
      ->with(1)
      ->willReturn($session);

    $result = $this->service->generarHojaServicio(1);
    $this->assertNull($result);
  }

  /**
   * @covers ::generarHojaServicio
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarHojaServicioDevuelveNullCuandoSinNotas(): void {
    $session = $this->createSessionMock('completed', '');

    $this->sessionStorage->method('load')
      ->with(2)
      ->willReturn($session);

    $result = $this->service->generarHojaServicio(2);
    $this->assertNull($result);
  }

  /**
   * Crea mock de session con status y notas.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   */
  protected function createSessionMock(string $status, string $notes): object {
    return new class($status, $notes) {
      public function __construct(
        private readonly string $status,
        private readonly string $notes,
      ) {}

      public function get(string $fieldName): object {
        $values = [
          'status' => $this->status,
          'session_notes' => $this->notes,
          'mentor_id' => NULL,
          'mentee_id' => NULL,
          'tenant_id' => NULL,
          'session_number' => 1,
          'scheduled_start' => '2026-03-10T10:00:00',
          'scheduled_end' => '2026-03-10T11:00:00',
          'actual_start' => NULL,
          'actual_end' => NULL,
          'session_type' => 'followup',
          'objectives_worked' => NULL,
          'agreements' => NULL,
          'next_steps' => NULL,
        ];

        $value = $values[$fieldName] ?? NULL;

        return new class($value) {
          public mixed $value;
          public mixed $target_id;
          public mixed $entity;

          public function __construct(mixed $val) {
            $this->value = $val;
            $this->target_id = $val;
            $this->entity = NULL;
          }

          public function isEmpty(): bool {
            return $this->value === NULL || $this->value === '';
          }
        };
      }

      public function id(): int {
        return 1;
      }

      public function set(string $fieldName, mixed $value): void {}

      public function save(): void {}
    };
  }

}
