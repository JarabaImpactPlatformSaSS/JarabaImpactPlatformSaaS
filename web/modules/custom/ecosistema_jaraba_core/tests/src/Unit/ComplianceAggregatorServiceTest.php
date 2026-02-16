<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ComplianceAggregatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ComplianceAggregatorService.
 *
 * ESTRUCTURA:
 * Verifica el servicio agregador cross-modulo que calcula KPIs de compliance
 * de jaraba_privacy, jaraba_legal y jaraba_dr.
 *
 * LOGICA:
 * - Score global (0-100) calculado por ponderacion equitativa de 9 KPIs.
 * - Grado (A-F) derivado del score.
 * - Alertas generadas cuando KPIs caen por debajo de umbrales.
 * - Modulos no instalados reportan 'not_available'.
 *
 * RELACIONES:
 * - ComplianceAggregatorService (SUT)
 * - CompliancePanelController (consumidor)
 *
 * Spec: Plan Stack Compliance Legal N1 — FASE 12.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ComplianceAggregatorService
 */
class ComplianceAggregatorServiceTest extends UnitTestCase {

  protected ComplianceAggregatorService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Service with no satellite modules installed (all NULL).
    $this->service = new ComplianceAggregatorService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Verifica que getComplianceOverview retorna estructura correcta.
   *
   * @covers ::getComplianceOverview
   */
  public function testGetComplianceOverviewReturnsExpectedStructure(): void {
    $overview = $this->service->getComplianceOverview();

    $this->assertIsArray($overview);
    $this->assertArrayHasKey('score', $overview);
    $this->assertArrayHasKey('grade', $overview);
    $this->assertArrayHasKey('kpis', $overview);
    $this->assertArrayHasKey('alerts', $overview);
    $this->assertArrayHasKey('modules', $overview);
  }

  /**
   * Verifica que sin modulos instalados el score es 0 y grado F.
   *
   * @covers ::getComplianceOverview
   */
  public function testNoModulesInstalledScoreIsZero(): void {
    $overview = $this->service->getComplianceOverview();

    $this->assertEquals(0, $overview['score']);
    $this->assertEquals('F', $overview['grade']);
  }

  /**
   * Verifica que los 9 KPIs se reportan sin modulos instalados.
   *
   * @covers ::getComplianceOverview
   */
  public function testNineKpisReportedWithoutModules(): void {
    $overview = $this->service->getComplianceOverview();

    $this->assertCount(9, $overview['kpis']);
    foreach ($overview['kpis'] as $kpi) {
      $this->assertEquals('not_available', $kpi['status']);
      $this->assertArrayHasKey('key', $kpi);
      $this->assertArrayHasKey('label', $kpi);
      $this->assertArrayHasKey('value', $kpi);
      $this->assertArrayHasKey('module', $kpi);
    }
  }

  /**
   * Verifica que el estado de modulos se reporta correctamente.
   *
   * @covers ::getComplianceOverview
   */
  public function testModuleStatusReportedCorrectly(): void {
    $overview = $this->service->getComplianceOverview();

    $this->assertCount(3, $overview['modules']);
    $moduleKeys = array_column($overview['modules'], 'key');
    $this->assertContains('jaraba_privacy', $moduleKeys);
    $this->assertContains('jaraba_legal', $moduleKeys);
    $this->assertContains('jaraba_dr', $moduleKeys);

    // All should be not installed since no satellite services injected.
    foreach ($overview['modules'] as $mod) {
      $this->assertFalse($mod['installed']);
    }
  }

  /**
   * Verifica que no se generan alertas sin modulos instalados.
   *
   * @covers ::getComplianceOverview
   */
  public function testNoAlertsWithoutModules(): void {
    $overview = $this->service->getComplianceOverview();

    $this->assertIsArray($overview['alerts']);
    $this->assertEmpty($overview['alerts']);
  }

  /**
   * Verifica el mapeo de score a grado.
   *
   * @covers ::getComplianceOverview
   * @dataProvider gradeDataProvider
   */
  public function testScoreToGradeMapping(int $score, string $expectedGrade): void {
    // Usamos reflection para testear el metodo privado scoreToGrade.
    $reflection = new \ReflectionClass($this->service);
    $method = $reflection->getMethod('scoreToGrade');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, $score);
    $this->assertEquals($expectedGrade, $result);
  }

  /**
   * Data provider para grades.
   */
  public static function gradeDataProvider(): array {
    return [
      'score 95 => A' => [95, 'A'],
      'score 90 => A' => [90, 'A'],
      'score 89 => B' => [89, 'B'],
      'score 75 => B' => [75, 'B'],
      'score 74 => C' => [74, 'C'],
      'score 60 => C' => [60, 'C'],
      'score 59 => D' => [59, 'D'],
      'score 40 => D' => [40, 'D'],
      'score 39 => F' => [39, 'F'],
      'score 0 => F' => [0, 'F'],
    ];
  }

  /**
   * Verifica que cada KPI tiene el modulo correcto asignado.
   *
   * @covers ::getComplianceOverview
   */
  public function testKpiModuleAssignment(): void {
    $overview = $this->service->getComplianceOverview();

    $kpisByModule = [];
    foreach ($overview['kpis'] as $kpi) {
      $kpisByModule[$kpi['module']][] = $kpi['key'];
    }

    // 3 KPIs por modulo.
    $this->assertCount(3, $kpisByModule['jaraba_privacy'] ?? []);
    $this->assertCount(3, $kpisByModule['jaraba_legal'] ?? []);
    $this->assertCount(3, $kpisByModule['jaraba_dr'] ?? []);
  }

  /**
   * Verifica que la estructura de KPI tiene todos los campos requeridos.
   *
   * @covers ::getComplianceOverview
   */
  public function testKpiStructure(): void {
    $overview = $this->service->getComplianceOverview();
    $requiredKeys = ['key', 'label', 'value', 'status', 'module'];

    foreach ($overview['kpis'] as $index => $kpi) {
      foreach ($requiredKeys as $key) {
        $this->assertArrayHasKey($key, $kpi, "KPI #{$index} missing key: {$key}");
      }
    }
  }

}
