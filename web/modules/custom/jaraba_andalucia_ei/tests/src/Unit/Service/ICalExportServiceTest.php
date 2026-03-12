<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_andalucia_ei\Entity\SesionProgramadaEiInterface;
use Drupal\jaraba_andalucia_ei\Service\ICalExportService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ICalExportService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ICalExportService
 * @group jaraba_andalucia_ei
 */
class ICalExportServiceTest extends UnitTestCase {

  protected ICalExportService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $sesionStorage;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->sesionStorage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('sesion_programada_ei')
      ->willReturn($this->sesionStorage);

    $this->service = new ICalExportService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * El feed vacío debe contener la estructura VCALENDAR mínima válida.
   *
   * @covers ::generarFeedTenant
   * @covers ::buildVCalendar
   */
  public function testGenerarFeedTenantSinSesionesProduceVcalendarValido(): void {
    $query = $this->createQueryMock([]);
    $this->sesionStorage->method('getQuery')->willReturn($query);

    $feed = $this->service->generarFeedTenant(42);

    $this->assertStringContainsString('BEGIN:VCALENDAR', $feed);
    $this->assertStringContainsString('VERSION:2.0', $feed);
    $this->assertStringContainsString('PRODID:-//Jaraba Impact Platform//Andalucia EI//ES', $feed);
    $this->assertStringContainsString('CALSCALE:GREGORIAN', $feed);
    $this->assertStringContainsString('METHOD:PUBLISH', $feed);
    $this->assertStringContainsString('END:VCALENDAR', $feed);
    $this->assertStringContainsString('BEGIN:VTIMEZONE', $feed);
    $this->assertStringContainsString('TZID:Europe/Madrid', $feed);
    $this->assertStringContainsString('END:VTIMEZONE', $feed);
    // Sin sesiones, no debe haber VEVENTs.
    $this->assertStringNotContainsString('BEGIN:VEVENT', $feed);
  }

  /**
   * El feed debe usar CRLF (\r\n) según RFC 5545.
   *
   * @covers ::generarFeedTenant
   */
  public function testGenerarFeedUsaLineEndingsCrlf(): void {
    $query = $this->createQueryMock([]);
    $this->sesionStorage->method('getQuery')->willReturn($query);

    $feed = $this->service->generarFeedTenant(1);

    // RFC 5545 §3.1: cada línea DEBE terminar en CRLF.
    $this->assertStringContainsString("\r\n", $feed);
    // No debe haber LF solitarios (líneas incompletas).
    // Eliminar los \r\n y verificar que no quedan \n sueltos.
    $sinCrlf = str_replace("\r\n", '', $feed);
    $this->assertStringNotContainsString("\n", $sinCrlf);
  }

  /**
   * Una sesión con datos completos genera un VEVENT con todos los campos clave.
   *
   * @covers ::generarFeedTenant
   * @covers ::buildVEvent
   */
  public function testGenerarFeedConSesionProduceVeventConCamposObligatorios(): void {
    $sesion = $this->createSesionMock(
      id: 7,
      titulo: 'Orientación individual',
      fecha: '2026-05-15',
      horaInicio: '10:00',
      horaFin: '11:00',
      tipoSesion: 'orientacion_laboral_individual',
      modalidad: 'presencial',
      estado: 'confirmada',
      fasePrograma: 'atencion',
      maxPlazas: 10,
      plazasOcupadas: 3,
    );

    $query = $this->createQueryMock([7]);
    $this->sesionStorage->method('getQuery')->willReturn($query);
    $this->sesionStorage->method('loadMultiple')
      ->with([7])
      ->willReturn([7 => $sesion]);

    $feed = $this->service->generarFeedTenant(1);

    $this->assertStringContainsString('BEGIN:VEVENT', $feed);
    $this->assertStringContainsString('END:VEVENT', $feed);
    $this->assertStringContainsString('UID:sesion-7@jaraba-saas.lndo.site', $feed);
    $this->assertStringContainsString('DTSTART;TZID=Europe/Madrid:20260515T100000', $feed);
    $this->assertStringContainsString('DTEND;TZID=Europe/Madrid:20260515T110000', $feed);
    $this->assertStringContainsString('SUMMARY:Orientación individual', $feed);
    $this->assertStringContainsString('STATUS:CONFIRMED', $feed);
    $this->assertStringContainsString('DESCRIPTION:', $feed);
    $this->assertStringContainsString('Tipo: Orientación laboral individual', $feed);
  }

  /**
   * Una sesión aplazada tiene STATUS:TENTATIVE y descripción con aviso.
   *
   * @covers ::buildVEvent
   */
  public function testSesionAplazadaTieneStatusTentativeYAvisoDescripcion(): void {
    $sesion = $this->createSesionMock(
      id: 9,
      titulo: 'Taller de CV',
      fecha: '2026-06-01',
      horaInicio: '09:00',
      horaFin: '10:00',
      tipoSesion: 'orientacion_laboral_grupal',
      modalidad: 'online',
      estado: 'aplazada',
      fasePrograma: 'atencion',
      maxPlazas: 20,
      plazasOcupadas: 15,
    );

    $query = $this->createQueryMock([9]);
    $this->sesionStorage->method('getQuery')->willReturn($query);
    $this->sesionStorage->method('loadMultiple')
      ->with([9])
      ->willReturn([9 => $sesion]);

    $feed = $this->service->generarFeedTenant(2);

    $this->assertStringContainsString('STATUS:TENTATIVE', $feed);
    // La descripción iCal usa \n escapado como literal '\n' (RFC 5545 §3.3.11).
    $this->assertStringContainsString('SESIÓN APLAZADA', $feed);
  }

  /**
   * Una sesión sin fecha devuelve feed sin VEVENT (buildVEvent retorna NULL).
   *
   * @covers ::buildVEvent
   */
  public function testSesionSinFechaNoProduceVevent(): void {
    $sesion = $this->createSesionMock(
      id: 11,
      titulo: 'Sin fecha',
      fecha: NULL,
      horaInicio: '10:00',
      horaFin: '11:00',
      tipoSesion: 'tutoria_seguimiento',
      modalidad: 'presencial',
      estado: 'programada',
      fasePrograma: 'seguimiento',
      maxPlazas: 1,
      plazasOcupadas: 0,
    );

    $query = $this->createQueryMock([11]);
    $this->sesionStorage->method('getQuery')->willReturn($query);
    $this->sesionStorage->method('loadMultiple')
      ->with([11])
      ->willReturn([11 => $sesion]);

    $feed = $this->service->generarFeedTenant(3);

    $this->assertStringNotContainsString('BEGIN:VEVENT', $feed);
  }

  /**
   * Construye un mock de SesionProgramadaEiInterface.
   *
   * Sigue MOCK-METHOD-001: solo usa métodos declarados en la interface.
   * Sigue TEST-CACHE-001: implementa los 3 métodos de cache metadata.
   */
  protected function createSesionMock(
    int $id,
    string $titulo,
    ?string $fecha,
    string $horaInicio,
    string $horaFin,
    string $tipoSesion,
    string $modalidad,
    string $estado,
    string $fasePrograma,
    int $maxPlazas,
    int $plazasOcupadas,
  ): SesionProgramadaEiInterface {
    $sesion = $this->createMock(SesionProgramadaEiInterface::class);

    $sesion->method('id')->willReturn($id);
    $sesion->method('getTitulo')->willReturn($titulo);
    $sesion->method('getFecha')->willReturn($fecha);
    $sesion->method('getHoraInicio')->willReturn($horaInicio);
    $sesion->method('getHoraFin')->willReturn($horaFin);
    $sesion->method('getTipoSesion')->willReturn($tipoSesion);
    $sesion->method('getModalidad')->willReturn($modalidad);
    $sesion->method('getEstado')->willReturn($estado);
    $sesion->method('getFasePrograma')->willReturn($fasePrograma);
    $sesion->method('getMaxPlazas')->willReturn($maxPlazas);
    $sesion->method('getPlazasOcupadas')->willReturn($plazasOcupadas);

    // hasField/get para campos opcionales de LOCATION y ORGANIZER.
    // Por defecto no tienen ninguno de los campos opcionales configurados.
    $sesion->method('hasField')->willReturn(FALSE);

    // TEST-CACHE-001.
    $sesion->method('getCacheContexts')->willReturn([]);
    $sesion->method('getCacheTags')->willReturn(['sesion_programada_ei:' . $id]);
    $sesion->method('getCacheMaxAge')->willReturn(-1);

    return $sesion;
  }

  /**
   * Crea un mock de QueryInterface encadenado.
   *
   * QUERY-CHAIN-001: addExpression/join devuelven string, no $this.
   * Los métodos de filtro/orden sí devuelven $this (willReturnSelf).
   *
   * @param array|int $result
   *   Resultado de execute().
   * @param bool $isCount
   *   Si es una query de conteo.
   */
  protected function createQueryMock(array|int $result, bool $isCount = FALSE): QueryInterface {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();

    if ($isCount) {
      $query->method('count')->willReturnSelf();
    }

    $query->method('execute')->willReturn($result);

    return $query;
  }

}
