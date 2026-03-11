<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Drupal\jaraba_andalucia_ei\Service\SesionProgramadaService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para SesionProgramadaService.
 *
 * Verifica consultas de sesiones futuras y expansion de recurrencia
 * con limites de seguridad.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\SesionProgramadaService
 * @group jaraba_andalucia_ei
 */
class SesionProgramadaServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected SesionProgramadaService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para sesion_programada_ei.
   */
  protected EntityStorageInterface $storage;

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
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('sesion_programada_ei')
      ->willReturn($this->storage);

    $this->service = new SesionProgramadaService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Helper para crear un mock de QueryInterface con chaining fluido.
   *
   * @param mixed $executeResult
   *   Resultado que devolvera execute().
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Mock del query.
   */
  private function createFluentQuery(mixed $executeResult): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($executeResult);

    return $query;
  }

  /**
   * @covers ::getSesionesFuturas
   */
  public function testGetSesionesFuturasReturnsEmptyWhenNoSessions(): void {
    $query = $this->createFluentQuery([]);
    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getSesionesFuturas(1);

    $this->assertSame([], $result, 'Debe devolver array vacio cuando no hay sesiones futuras.');
  }

  /**
   * @covers ::getSesionesFuturas
   */
  public function testGetSesionesFuturasReturnsSessions(): void {
    $query = $this->createFluentQuery([10, 20]);

    $sesion1 = $this->createMock(SesionProgramadaEiInterface::class);
    $sesion2 = $this->createMock(SesionProgramadaEiInterface::class);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('loadMultiple')
      ->with([10, 20])
      ->willReturn([10 => $sesion1, 20 => $sesion2]);

    $result = $this->service->getSesionesFuturas(5);

    $this->assertCount(2, $result, 'Debe devolver las sesiones encontradas.');
  }

  /**
   * @covers ::expandirRecurrencia
   */
  public function testExpandirRecurrenciaReturnsEmptyForNonRecurrente(): void {
    $sesionPadre = $this->createMock(SesionProgramadaEiInterface::class);
    $sesionPadre->method('isRecurrente')->willReturn(FALSE);

    $result = $this->service->expandirRecurrencia($sesionPadre);

    $this->assertSame([], $result, 'Debe devolver array vacio para sesion no recurrente.');
  }

  /**
   * @covers ::expandirRecurrencia
   */
  public function testExpandirRecurrenciaMaxLimitIs52(): void {
    // Verificar que count se limita a 52 maximo, incluso si el patron pide mas.
    // Usamos reflexion para validar la logica del min(count, 52).
    $reflection = new \ReflectionMethod(SesionProgramadaService::class, 'expandirRecurrencia');
    $this->assertTrue(
      $reflection->isPublic(),
      'expandirRecurrencia() debe ser publico.'
    );

    // Verificar el limite mediante la creacion de un patron con count=100.
    // La sesion padre devuelve un patron JSON con count > 52.
    $fieldRecurrencia = new class {
      public ?string $value = '{"frequency":"weekly","count":100}';
    };
    $fieldFecha = new class {
      public ?string $value = '2026-01-01';
    };
    $fieldTenantId = new class {
      public ?int $target_id = 1;
    };
    $fieldAccionFormativaId = new class {
      public ?int $target_id = 5;
    };
    $fieldLugarDescripcion = new class {
      public ?string $value = 'Sala A';
    };
    $fieldLugarUrl = new class {
      public ?string $value = 'https://meet.example.com';
    };
    $fieldFacilitadorId = new class {
      public ?int $target_id = 10;
    };
    $fieldFacilitadorNombre = new class {
      public ?string $value = 'Juan Perez';
    };

    $sesionPadre = $this->createMock(SesionProgramadaEiInterface::class);
    $sesionPadre->method('isRecurrente')->willReturn(TRUE);
    $sesionPadre->method('id')->willReturn(1);
    $sesionPadre->method('getOwnerId')->willReturn(1);
    $sesionPadre->method('getTitulo')->willReturn('Sesion Test');
    $sesionPadre->method('getTipoSesion')->willReturn('formacion_presencial');
    $sesionPadre->method('getFasePrograma')->willReturn('atencion');
    $sesionPadre->method('getHoraInicio')->willReturn('09:00');
    $sesionPadre->method('getHoraFin')->willReturn('11:00');
    $sesionPadre->method('getModalidad')->willReturn('presencial');
    $sesionPadre->method('getMaxPlazas')->willReturn(20);
    $sesionPadre->method('get')->willReturnCallback(function (string $field) use (
      $fieldRecurrencia,
      $fieldFecha,
      $fieldTenantId,
      $fieldAccionFormativaId,
      $fieldLugarDescripcion,
      $fieldLugarUrl,
      $fieldFacilitadorId,
      $fieldFacilitadorNombre,
    ) {
      return match ($field) {
        'recurrencia_patron' => $fieldRecurrencia,
        'fecha' => $fieldFecha,
        'tenant_id' => $fieldTenantId,
        'accion_formativa_id' => $fieldAccionFormativaId,
        'lugar_descripcion' => $fieldLugarDescripcion,
        'lugar_url' => $fieldLugarUrl,
        'facilitador_id' => $fieldFacilitadorId,
        'facilitador_nombre' => $fieldFacilitadorNombre,
        default => new class { public mixed $value = NULL; public mixed $target_id = NULL; },
      };
    });

    // Query para verificar si ya existen sesiones hijas: devuelve vacio (no duplicadas).
    $queryNoExiste = $this->createFluentQuery([]);

    // Contador de sesiones creadas via storage->create().
    $createCount = 0;
    $sesionHija = $this->createMock(SesionProgramadaEiInterface::class);
    $sesionHija->method('save')->willReturn(1);

    $this->storage->method('getQuery')->willReturn($queryNoExiste);
    $this->storage->method('create')->willReturnCallback(function () use (&$createCount, $sesionHija) {
      $createCount++;
      return $sesionHija;
    });

    $result = $this->service->expandirRecurrencia($sesionPadre);

    // Con count=100 el servicio aplica min(100, 52) = 52 iteraciones.
    $this->assertCount(52, $result, 'El maximo de sesiones generadas debe ser 52.');
    $this->assertSame(52, $createCount, 'Se deben crear exactamente 52 sesiones hijas.');
  }

  /**
   * @covers ::expandirRecurrencia
   */
  public function testExpandirRecurrenciaInvalidJsonReturnsEmpty(): void {
    $fieldRecurrencia = new class {
      public ?string $value = '{invalid json!!!}';
    };

    $sesionPadre = $this->createMock(SesionProgramadaEiInterface::class);
    $sesionPadre->method('isRecurrente')->willReturn(TRUE);
    $sesionPadre->method('id')->willReturn(5);
    $sesionPadre->method('get')->willReturnCallback(function (string $field) use ($fieldRecurrencia) {
      return match ($field) {
        'recurrencia_patron' => $fieldRecurrencia,
        default => new class { public mixed $value = NULL; },
      };
    });

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Patrón de recurrencia inválido'),
        $this->anything()
      );

    $result = $this->service->expandirRecurrencia($sesionPadre);

    $this->assertSame([], $result, 'Debe devolver array vacio con JSON invalido.');
  }

  /**
   * @covers ::expandirRecurrencia
   */
  public function testExpandirRecurrenciaEmptyPatronReturnsEmpty(): void {
    $fieldRecurrencia = new class {
      public ?string $value = NULL;
    };

    $sesionPadre = $this->createMock(SesionProgramadaEiInterface::class);
    $sesionPadre->method('isRecurrente')->willReturn(TRUE);
    $sesionPadre->method('get')->willReturnCallback(function (string $field) use ($fieldRecurrencia) {
      return match ($field) {
        'recurrencia_patron' => $fieldRecurrencia,
        default => new class { public mixed $value = NULL; },
      };
    });

    $result = $this->service->expandirRecurrencia($sesionPadre);

    $this->assertSame([], $result, 'Debe devolver array vacio con patron vacio.');
  }

  /**
   * @covers ::getSesionesPorTenant
   */
  public function testGetSesionesPorTenantEmpty(): void {
    $query = $this->createFluentQuery([]);
    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getSesionesPorTenant(1);

    $this->assertSame([], $result, 'Debe devolver array vacio cuando no hay sesiones para el tenant.');
  }

}
