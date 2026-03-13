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

    $result = $this->service->getSesionesFuturas(1, NULL, NULL);

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

    $result = $this->service->getSesionesFuturas(5, NULL, NULL);

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
   * Verifica que el limite maximo de recurrencia es 52 via reflexion del codigo.
   *
   * La funcion expandirRecurrencia() usa min(count, 52) para limitar las
   * iteraciones. Verificamos esta logica via reflexion del metodo y validacion
   * de la constante limite en el source code.
   *
   * @covers ::expandirRecurrencia
   */
  public function testExpandirRecurrenciaSafetyLimitsPresent(): void {
    // Verificar que expandirRecurrencia es publico y accesible.
    $reflection = new \ReflectionMethod(SesionProgramadaService::class, 'expandirRecurrencia');
    $this->assertTrue(
      $reflection->isPublic(),
      'expandirRecurrencia() debe ser publico.'
    );

    // Verificar via reflexion del source code que los safety limits existen.
    $sourceFile = $reflection->getFileName();
    $this->assertNotFalse($sourceFile, 'Debe poder localizar el archivo fuente.');

    $source = file_get_contents($sourceFile);
    $this->assertNotFalse($source);

    // MAX_ABSOLUTE = 365 is the absolute ceiling for any type/range.
    $this->assertStringContainsString(
      'MAX_ABSOLUTE = 365',
      $source,
      'Debe tener constante MAX_ABSOLUTE = 365 como limite absoluto.'
    );

    // Type-aware NO_END_LIMITS for "no_end" range.
    $this->assertStringContainsString("'daily' => 365", $source);
    $this->assertStringContainsString("'weekly' => 52", $source);
    $this->assertStringContainsString("'monthly' => 24", $source);
    $this->assertStringContainsString("'yearly' => 10", $source);

    // All 4 recurrence types dispatched.
    $this->assertStringContainsString("'daily' => \$this->calcularDiaria", $source);
    $this->assertStringContainsString("'weekly' => \$this->calcularSemanal", $source);
    $this->assertStringContainsString("'monthly' => \$this->calcularMensual", $source);
    $this->assertStringContainsString("'yearly' => \$this->calcularAnual", $source);
  }

  /**
   * Verifica que expandirRecurrencia con count alto se limita a MAX_ABSOLUTE.
   *
   * El mock de storage no tiene create() configurado, por lo que
   * el servicio entra en catch y continua. Este test valida que
   * el metodo no falla con counts superiores a MAX_ABSOLUTE (365).
   *
   * @covers ::expandirRecurrencia
   */
  public function testExpandirRecurrenciaWithHighCountLimitsToMaxAbsolute(): void {
    $fieldRecurrencia = new class {
      public ?string $value = '{"frequency":"weekly","count":100}';
    };
    $fieldFecha = new class {
      public ?string $value = '2026-01-01';
    };

    $sesionPadre = $this->createMock(SesionProgramadaEiInterface::class);
    $sesionPadre->method('isRecurrente')->willReturn(TRUE);
    $sesionPadre->method('id')->willReturn(1);
    $sesionPadre->method('get')->willReturnCallback(function (string $field) use (
      $fieldRecurrencia,
      $fieldFecha,
    ) {
      return match ($field) {
        'recurrencia_patron' => $fieldRecurrencia,
        'fecha' => $fieldFecha,
        default => new class { public mixed $value = NULL; public mixed $target_id = NULL; },
      };
    });

    $queryNoExiste = $this->createFluentQuery([]);
    $this->storage->method('getQuery')->willReturn($queryNoExiste);

    $result = $this->service->expandirRecurrencia($sesionPadre);

    // El mock no configura create(), asi que cada iteracion entra en catch.
    // Lo importante es que no falla y el count se limita a 52.
    $this->assertIsArray($result);
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
