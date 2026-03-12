<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\AlertasNormativasService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AlertasNormativasService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AlertasNormativasService
 * @group jaraba_andalucia_ei
 */
class AlertasNormativasServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AlertasNormativasService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para participantes.
   */
  protected EntityStorageInterface $participanteStorage;

  /**
   * Mock query.
   */
  protected QueryInterface $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->participanteStorage = $this->createMock(EntityStorageInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $this->query = $this->createMock(QueryInterface::class);

    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('exists')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();

    $this->participanteStorage->method('getQuery')->willReturn($this->query);

    // hasDefinition devuelve FALSE por defecto para evitar queries a actuacion_sto.
    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'programa_participante_ei') {
          return $this->participanteStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new AlertasNormativasService(
      $this->entityTypeManager,
      $logger,
    );
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasSinTenantDevuelveVacio(): void {
    // TENANT-001: Sin tenantId, getAlertas() DEBE devolver vacío.
    $alertas = $this->service->getAlertas();
    $this->assertEmpty($alertas);

    $alertas = $this->service->getAlertas(NULL);
    $this->assertEmpty($alertas);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasSinParticipantesDevuelveVacio(): void {
    $this->query->method('execute')->willReturn([]);
    $this->participanteStorage->method('loadMultiple')->willReturn([]);

    $alertas = $this->service->getAlertas(1);
    $this->assertEmpty($alertas);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaAcuerdoYDaciPendienteSemana1(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'semana_actual' => 1,
      'acuerdo_participacion_firmado' => FALSE,
      'daci_firmado' => FALSE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'carril_a',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $this->assertNotEmpty($alertas);

    // Alerta de Acuerdo de Participación.
    $acuerdoAlerta = $this->findAlertaByTipo($alertas, 'acuerdo_participacion_pendiente');
    $this->assertNotNull($acuerdoAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_ALTO, $acuerdoAlerta['nivel']);

    // Alerta de DACI (documento separado).
    $daciAlerta = $this->findAlertaByTipo($alertas, 'daci_pendiente');
    $this->assertNotNull($daciAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_ALTO, $daciAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasAcuerdoYDaciPendienteSemana2EsCritico(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'semana_actual' => 2,
      'acuerdo_participacion_firmado' => FALSE,
      'daci_firmado' => FALSE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'carril_a',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    // Ambos documentos pendientes = CRITICO en semana 2.
    $acuerdoAlerta = $this->findAlertaByTipo($alertas, 'acuerdo_participacion_pendiente');
    $this->assertNotNull($acuerdoAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $acuerdoAlerta['nivel']);

    $daciAlerta = $this->findAlertaByTipo($alertas, 'daci_pendiente');
    $this->assertNotNull($daciAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $daciAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaFseEntradaPendienteSemana2(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'semana_actual' => 2,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => FALSE,
      'carril' => 'carril_a',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $fseAlerta = $this->findAlertaByTipo($alertas, 'fse_entrada_pendiente');
    $this->assertNotNull($fseAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_ALTO, $fseAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasFseEntradaSemana4EsCritico(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'diagnostico',
      'semana_actual' => 4,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => FALSE,
      'carril' => 'carril_a',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $fseAlerta = $this->findAlertaByTipo($alertas, 'fse_entrada_pendiente');
    $this->assertNotNull($fseAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $fseAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaCarrilPendienteSemana3(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'diagnostico',
      'semana_actual' => 3,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => '',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $carrilAlerta = $this->findAlertaByTipo($alertas, 'carril_pendiente');
    $this->assertNotNull($carrilAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_ALTO, $carrilAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraCarrilPendienteEnAcogida(): void {
    // En fase acogida, no se genera alerta de carril pendiente aunque semana >= 3.
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'semana_actual' => 5,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => '',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $carrilAlerta = $this->findAlertaByTipo($alertas, 'carril_pendiente');
    $this->assertNull($carrilAlerta);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaHorasInsuficientesSemana30(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 30,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'carril_b',
      'horas_orientacion_ind' => 3.0,
      'horas_orientacion_grup' => 1.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 20.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $orientAlerta = $this->findAlertaByTipo($alertas, 'horas_orientacion_insuficientes');
    $this->assertNotNull($orientAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_MEDIO, $orientAlerta['nivel']);

    $formAlerta = $this->findAlertaByTipo($alertas, 'horas_formacion_insuficientes');
    $this->assertNotNull($formAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_MEDIO, $formAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasHorasInsuficientesSemana36EsCritico(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 36,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'carril_a',
      'horas_orientacion_ind' => 2.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 30.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $orientAlerta = $this->findAlertaByTipo($alertas, 'horas_orientacion_insuficientes');
    $this->assertNotNull($orientAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $orientAlerta['nivel']);

    $formAlerta = $this->findAlertaByTipo($alertas, 'horas_formacion_insuficientes');
    $this->assertNotNull($formAlerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $formAlerta['nivel']);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraHorasInsuficientesSiFaseNoEsAtencion(): void {
    // En fase insercion, semana 35, no genera alertas de horas.
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'insercion',
      'semana_actual' => 35,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'carril_a',
      'horas_orientacion_ind' => 2.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 10.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $orientAlerta = $this->findAlertaByTipo($alertas, 'horas_orientacion_insuficientes');
    $this->assertNull($orientAlerta);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasOrdenadosPorGravedad(): void {
    // Participante con multiples alertas de distinto nivel.
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 36,
      'acuerdo_participacion_firmado' => FALSE,
      'daci_firmado' => FALSE,
      'fse_entrada_completado' => FALSE,
      'carril' => '',
      'horas_orientacion_ind' => 0.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 0.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $this->assertGreaterThan(2, count($alertas));

    // Verificar orden: critico primero, luego alto, luego medio.
    $nivelOrder = [
      AlertasNormativasService::NIVEL_CRITICO => 0,
      AlertasNormativasService::NIVEL_ALTO => 1,
      AlertasNormativasService::NIVEL_MEDIO => 2,
      AlertasNormativasService::NIVEL_INFO => 3,
    ];

    for ($i = 0; $i < count($alertas) - 1; $i++) {
      $this->assertLessThanOrEqual(
        $nivelOrder[$alertas[$i + 1]['nivel']],
        $nivelOrder[$alertas[$i]['nivel']],
        'Las alertas deben estar ordenadas por gravedad descendente.',
      );
    }
  }

  /**
   * @covers ::getResumenAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenAlertasAgrupaCorrectamente(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 36,
      'acuerdo_participacion_firmado' => FALSE,
      'daci_firmado' => FALSE,
      'fse_entrada_completado' => FALSE,
      'carril' => '',
      'horas_orientacion_ind' => 2.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 20.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $resumen = $this->service->getResumenAlertas(1);

    $this->assertArrayHasKey(AlertasNormativasService::NIVEL_CRITICO, $resumen);
    $this->assertArrayHasKey(AlertasNormativasService::NIVEL_ALTO, $resumen);
    $this->assertArrayHasKey(AlertasNormativasService::NIVEL_MEDIO, $resumen);
    $this->assertArrayHasKey(AlertasNormativasService::NIVEL_INFO, $resumen);

    // Debe haber al menos alertas criticas (DACI semana >=2, FSE+ semana >=4, horas semana >=36).
    $this->assertGreaterThan(0, $resumen[AlertasNormativasService::NIVEL_CRITICO]);
    $total = array_sum($resumen);
    $this->assertGreaterThan(0, $total);
  }

  /**
   * @covers ::getResumenAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenAlertasSinParticipantesDevuelveCeros(): void {
    $this->query->method('execute')->willReturn([]);
    $this->participanteStorage->method('loadMultiple')->willReturn([]);

    $resumen = $this->service->getResumenAlertas(1);

    $this->assertEquals(0, $resumen[AlertasNormativasService::NIVEL_CRITICO]);
    $this->assertEquals(0, $resumen[AlertasNormativasService::NIVEL_ALTO]);
    $this->assertEquals(0, $resumen[AlertasNormativasService::NIVEL_MEDIO]);
    $this->assertEquals(0, $resumen[AlertasNormativasService::NIVEL_INFO]);
  }

  /**
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasParticipanteSinProblemasNoGeneraAlertas(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 10,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'carril_a',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 3.0,
      'horas_mentoria_ia' => 1.0,
      'horas_mentoria_humana' => 2.0,
      'horas_formacion' => 55.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);
    $this->assertEmpty($alertas);
  }

  /**
   * Alerta 8: sto_registro_plazo — 15+ días sin registro STO genera alerta.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaStoRegistroPlazoSemana15(): void {
    // Fecha de inicio hace 16 días → supera el umbral de 15 días.
    $fechaInicio = date('Y-m-d\TH:i:s', strtotime('-16 days'));

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 4,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'fecha_inicio_programa' => $fechaInicio,
      'fecha_alta_sto' => '',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'sto_registro_plazo');
    $this->assertNotNull($alerta, 'Debe generarse alerta sto_registro_plazo tras 16 días sin STO.');
    $this->assertEquals(AlertasNormativasService::NIVEL_ALTO, $alerta['nivel']);
  }

  /**
   * Alerta 8: sto_registro_plazo — 30+ días sin STO es CRITICO.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasStoRegistroPlazo30DiasEsCritico(): void {
    // Fecha de inicio hace 31 días → nivel CRITICO.
    $fechaInicio = date('Y-m-d\TH:i:s', strtotime('-31 days'));

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 7,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'fecha_inicio_programa' => $fechaInicio,
      'fecha_alta_sto' => '',
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'sto_registro_plazo');
    $this->assertNotNull($alerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $alerta['nivel']);
  }

  /**
   * Alerta 8: Con fecha_alta_sto rellena no se genera alerta.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraStoRegistroSiFechaAltaRellenada(): void {
    $fechaInicio = date('Y-m-d\TH:i:s', strtotime('-20 days'));
    $fechaAlta = date('Y-m-d\TH:i:s', strtotime('-18 days'));

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 4,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'fecha_inicio_programa' => $fechaInicio,
      'fecha_alta_sto' => $fechaAlta,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'sto_registro_plazo');
    $this->assertNull($alerta, 'Con fecha_alta_sto rellena no debe generarse alerta STO.');
  }

  /**
   * Alerta 9: orientacion_individual_insuficiente en fase atencion semana 12.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaOrientacionIndividualInsuficienteSemana12(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 12,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 1.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'orientacion_individual_insuficiente');
    $this->assertNotNull($alerta, 'Con < 2h orientación individual en semana 12 debe generarse alerta.');
    $this->assertEquals(AlertasNormativasService::NIVEL_MEDIO, $alerta['nivel']);
  }

  /**
   * Alerta 9: orientacion_individual_insuficiente es CRITICO en semana 20+.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasOrientacionIndividualInsuficienteSemana20EsCritico(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 20,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 0.5,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'orientacion_individual_insuficiente');
    $this->assertNotNull($alerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $alerta['nivel']);
  }

  /**
   * Alerta 9: Con 2h orientación individual exactas no se genera alerta.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraOrientacionSi2HorasExactas(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 15,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 2.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'orientacion_individual_insuficiente');
    $this->assertNull($alerta, 'Con exactamente 2h orientación individual no debe generarse alerta.');
  }

  /**
   * Alerta 9: En fase acogida semana 12 no se genera alerta de orientación.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraOrientacionEnFaseAcogida(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'acogida',
      'semana_actual' => 14,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 0.0,
      'horas_orientacion_grup' => 0.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'orientacion_individual_insuficiente');
    $this->assertNull($alerta, 'En fase acogida no debe generarse alerta de orientación individual.');
  }

  /**
   * Alerta 10: asistencia_insuficiente — <75% en fase atencion semana 20.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaAsistenciaInsuficienteSemana20(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 20,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 2.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
      'asistencia_porcentaje' => 70.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'asistencia_insuficiente');
    $this->assertNotNull($alerta, 'Con asistencia 70% en semana 20 debe generarse alerta.');
    $this->assertEquals(AlertasNormativasService::NIVEL_MEDIO, $alerta['nivel']);
  }

  /**
   * Alerta 10: asistencia_insuficiente es CRITICO en semana 30+.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasAsistenciaInsuficienteSemana30EsCritico(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 30,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 2.0,
      'horas_mentoria_ia' => 1.0,
      'horas_mentoria_humana' => 2.0,
      'horas_formacion' => 55.0,
      'asistencia_porcentaje' => 60.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'asistencia_insuficiente');
    $this->assertNotNull($alerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $alerta['nivel']);
  }

  /**
   * Alerta 10: Sin horas de formación no se genera alerta de asistencia.
   *
   * Si horas_formacion=0, no hay formación activa y la alerta no aplica.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraAsistenciaSinHorasFormacion(): void {
    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'semana_actual' => 25,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 2.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 0.0,
      'asistencia_porcentaje' => 50.0,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $alertas = $this->service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'asistencia_insuficiente');
    $this->assertNull($alerta, 'Sin horas_formacion > 0 no debe generarse alerta de asistencia.');
  }

  /**
   * Alerta 11: indicadores_6m_pendientes a los 5+ meses post-salida.
   *
   * La alerta 11 usa una segunda query interna sobre participantes con
   * fse_salida_completado=TRUE. Se configura hasDefinition() y una segunda
   * llamada a loadMultiple() para simular esa segunda pasada.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasDetectaIndicadores6mPendientes(): void {
    // Fecha de fin hace 155 días (~5.2 meses).
    $fechaFin = date('Y-m-d\TH:i:s', strtotime('-155 days'));

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'seguimiento',
      'semana_actual' => 50,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 2.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
      'fse_salida_completado' => TRUE,
      'indicadores_6m_completado' => FALSE,
      'fecha_fin_programa' => $fechaFin,
    ]);

    // Primera query: participantes activos (fase != baja).
    // Segunda query: participantes con fse_salida_completado (alerta 11).
    // El mock de query usa willReturn([1]) para ambas ejecuciones.
    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    // hasDefinition('programa_participante_ei') debe devolver TRUE
    // para que el bloque de alerta 11 se ejecute.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(function (string $type): bool {
        return $type === 'programa_participante_ei';
      });
    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->participanteStorage);

    $logger = $this->createMock(LoggerInterface::class);
    $service = new AlertasNormativasService($this->entityTypeManager, $logger);

    $alertas = $service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'indicadores_6m_pendientes');
    $this->assertNotNull($alerta, 'Con 5+ meses post-salida sin indicadores_6m debe generarse alerta.');
    $this->assertEquals(AlertasNormativasService::NIVEL_ALTO, $alerta['nivel']);
  }

  /**
   * Alerta 11: 6+ meses post-salida es CRITICO.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasIndicadores6mPendientes6MesesEsCritico(): void {
    // Fecha de fin hace 185 días (~6.2 meses).
    $fechaFin = date('Y-m-d\TH:i:s', strtotime('-185 days'));

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'seguimiento',
      'semana_actual' => 55,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 2.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
      'fse_salida_completado' => TRUE,
      'indicadores_6m_completado' => FALSE,
      'fecha_fin_programa' => $fechaFin,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(function (string $type): bool {
        return $type === 'programa_participante_ei';
      });
    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->participanteStorage);

    $logger = $this->createMock(LoggerInterface::class);
    $service = new AlertasNormativasService($this->entityTypeManager, $logger);

    $alertas = $service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'indicadores_6m_pendientes');
    $this->assertNotNull($alerta);
    $this->assertEquals(AlertasNormativasService::NIVEL_CRITICO, $alerta['nivel']);
  }

  /**
   * Alerta 11: Con indicadores_6m_completado=TRUE no se genera alerta.
   *
   * @covers ::getAlertas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getAlertasNoGeneraIndicadores6mSiYaCompletados(): void {
    $fechaFin = date('Y-m-d\TH:i:s', strtotime('-190 days'));

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'seguimiento',
      'semana_actual' => 55,
      'acuerdo_participacion_firmado' => TRUE,
      'daci_firmado' => TRUE,
      'fse_entrada_completado' => TRUE,
      'carril' => 'hibrido',
      'horas_orientacion_ind' => 5.0,
      'horas_orientacion_grup' => 2.0,
      'horas_mentoria_ia' => 0.0,
      'horas_mentoria_humana' => 0.0,
      'horas_formacion' => 55.0,
      'fse_salida_completado' => TRUE,
      'indicadores_6m_completado' => TRUE,
      'fecha_fin_programa' => $fechaFin,
    ]);

    $this->query->method('execute')->willReturn([1]);
    $this->participanteStorage->method('loadMultiple')->willReturn([$participante]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(function (string $type): bool {
        return $type === 'programa_participante_ei';
      });
    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->participanteStorage);

    $logger = $this->createMock(LoggerInterface::class);
    $service = new AlertasNormativasService($this->entityTypeManager, $logger);

    $alertas = $service->getAlertas(1);

    $alerta = $this->findAlertaByTipo($alertas, 'indicadores_6m_pendientes');
    $this->assertNull($alerta, 'Con indicadores_6m_completado=TRUE no debe generarse alerta.');
  }

  /**
   * Busca una alerta por tipo en el array de alertas.
   */
  protected function findAlertaByTipo(array $alertas, string $tipo): ?array {
    foreach ($alertas as $alerta) {
      if ($alerta['tipo'] === $tipo) {
        return $alerta;
      }
    }
    return NULL;
  }

  /**
   * Crea mock de participante para AlertasNormativasService.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   * TEST-CACHE-001: Cache metadata.
   */
  protected function createParticipanteMock(int $id, array $fieldValues): object {
    return new class($id, $fieldValues) {

      /**
       * Constructor.
       */
      public function __construct(
        private readonly int $id,
        private readonly array $fieldValues,
      ) {}

      /**
       * Devuelve el ID del participante.
       */
      public function id(): int {
        return $this->id;
      }

      /**
       * Devuelve la etiqueta del participante.
       */
      public function label(): ?string {
        return "Participante Test #{$this->id}";
      }

      /**
       * Simula EntityInterface::get() devolviendo el valor del campo.
       */
      public function get(string $fieldName): object {
        $value = $this->fieldValues[$fieldName] ?? NULL;
        return new class($value) {

          /**
           * Constructor.
           */
          public function __construct(public readonly mixed $value) {}

        };
      }

      /**
       * TEST-CACHE-001: Contextos de cache.
       *
       * @return array<string>
       *   Lista de contextos de cache.
       */
      public function getCacheContexts(): array {
        return [];
      }

      /**
       * TEST-CACHE-001: Etiquetas de cache.
       *
       * @return array<string>
       *   Lista de etiquetas de cache.
       */
      public function getCacheTags(): array {
        return ["programa_participante_ei:{$this->id}"];
      }

      /**
       * TEST-CACHE-001: Tiempo máximo de cache.
       */
      public function getCacheMaxAge(): int {
        return -1;
      }

    };
  }

}
