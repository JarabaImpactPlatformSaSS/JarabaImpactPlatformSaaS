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
      public function __construct(
        private readonly int $id,
        private readonly array $fieldValues,
      ) {}

      public function id(): int {
        return $this->id;
      }

      public function label(): ?string {
        return "Participante Test #{$this->id}";
      }

      public function get(string $fieldName): object {
        $value = $this->fieldValues[$fieldName] ?? NULL;
        // Para entity_reference fields, tambien exponer target_id.
        return new class($value) {
          public function __construct(public readonly mixed $value) {}
        };
      }

      public function getCacheContexts(): array {
        return [];
      }

      public function getCacheTags(): array {
        return ["programa_participante_ei:{$this->id}"];
      }

      public function getCacheMaxAge(): int {
        return -1;
      }
    };
  }

}
