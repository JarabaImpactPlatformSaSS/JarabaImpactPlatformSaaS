<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\EmprendimientoFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Service\UpgradeTriggerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para EmprendimientoFeatureGateService.
 *
 * COBERTURA:
 * Verifica la logica de gating de features por plan para el vertical
 * Emprendimiento. Cada feature tiene limites configurados en
 * FreemiumVerticalLimit y los usos se rastrean por dia.
 *
 * ESCENARIOS VERIFICADOS:
 * - Feature habilitada para plan correcto (dentro del limite)
 * - Feature denegada para plan incorrecto (limit_value = 0)
 * - Feature ilimitada (limit_value = -1)
 * - Feature denegada cuando se alcanza el limite diario
 * - Aislamiento cross-vertical (tenant A no accede a features de tenant B)
 * - Comportamiento por defecto para features desconocidas (sin config)
 * - Mapeo de upgrade de planes (free -> starter -> profesional -> business)
 * - Registro de uso (insert y update)
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\EmprendimientoFeatureGateService
 */
class FeatureGateServiceTest extends UnitTestCase {

  /**
   * Servicio bajo prueba.
   */
  protected EmprendimientoFeatureGateService $service;

  /**
   * Mock de UpgradeTriggerService.
   */
  protected $upgradeTrigger;

  /**
   * Mock de la conexion a base de datos.
   */
  protected $database;

  /**
   * Mock del usuario actual.
   */
  protected $currentUser;

  /**
   * Mock del logger.
   */
  protected $logger;

  /**
   * Mock del servicio de contexto de tenant.
   */
  protected $tenantContext;

  /**
   * Mock del schema de base de datos.
   */
  protected $schema;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->upgradeTrigger = $this->createMock(UpgradeTriggerService::class);
    $this->database = $this->createMock(Connection::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);

    // Schema mock: table always exists to avoid CREATE TABLE calls.
    $this->schema = $this->createMock(Schema::class);
    $this->schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($this->schema);

    $this->service = new EmprendimientoFeatureGateService(
      $this->upgradeTrigger,
      $this->database,
      $this->currentUser,
      $this->logger,
      $this->tenantContext,
    );
  }

  /**
   * Helper: crea un mock de limite freemium (FreemiumVerticalLimit).
   *
   * @param int $limitValue
   *   Valor del limite (-1 = ilimitado, 0 = no incluido, N = limite).
   * @param string $upgradeMessage
   *   Mensaje de upgrade.
   *
   * @return object
   *   Mock de la entidad de limite.
   */
  protected function createLimitEntityMock(int $limitValue, string $upgradeMessage): object {
    return new class($limitValue, $upgradeMessage) {

      public function __construct(
        protected int $limitValue,
        protected string $upgradeMessage,
      ) {}

      public function get(string $field): int|string {
        return match ($field) {
          'limit_value' => $this->limitValue,
          'upgrade_message' => $this->upgradeMessage,
          default => '',
        };
      }

    };
  }

  /**
   * Helper: configura el mock de getUsageCount via database select.
   *
   * @param int $usageCount
   *   El numero de usos a devolver.
   */
  protected function mockUsageCount(int $usageCount): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($usageCount);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('emprendimiento_feature_usage', 'u')
      ->willReturn($select);
  }

  // =========================================================================
  // TESTS: Feature habilitada para plan correcto
  // =========================================================================

  /**
   * Verifica que check() devuelve allowed cuando no hay limite configurado.
   *
   * Sin FreemiumVerticalLimit = permitido sin restriccion.
   *
   * @covers ::check
   */
  public function testCheckReturnsAllowedWhenNoLimitConfigured(): void {
    $this->upgradeTrigger->method('getVerticalLimit')
      ->with('emprendimiento', 'starter', 'hypotheses_active')
      ->willReturn(NULL);

    $result = $this->service->check(1, 'hypotheses_active', 'starter');

    $this->assertTrue($result->allowed);
    $this->assertSame('hypotheses_active', $result->featureKey);
    $this->assertSame('starter', $result->currentPlan);
  }

  /**
   * Verifica que check() devuelve allowed con remaining cuando bajo el limite.
   *
   * Usuario con 3 de 10 usos = allowed con 7 remaining.
   *
   * @covers ::check
   */
  public function testCheckReturnsAllowedWithRemainingWhenUnderLimit(): void {
    $limitEntity = $this->createLimitEntityMock(10, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $this->mockUsageCount(3);

    $result = $this->service->check(1, 'experiments_monthly', 'starter');

    $this->assertTrue($result->allowed);
    $this->assertSame(7, $result->remaining);
    $this->assertSame(10, $result->limit);
    $this->assertSame(3, $result->used);
  }

  // =========================================================================
  // TESTS: Feature ilimitada (limit_value = -1)
  // =========================================================================

  /**
   * Verifica que check() devuelve allowed ilimitado para limit_value = -1.
   *
   * @covers ::check
   */
  public function testCheckReturnsAllowedForUnlimitedFeature(): void {
    $limitEntity = $this->createLimitEntityMock(-1, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'hypotheses_active', 'business');

    $this->assertTrue($result->allowed);
    $this->assertSame(-1, $result->remaining);
    $this->assertSame(-1, $result->limit);
  }

  // =========================================================================
  // TESTS: Feature denegada para plan incorrecto (limit_value = 0)
  // =========================================================================

  /**
   * Verifica que check() devuelve denied cuando feature no incluida en plan.
   *
   * limit_value = 0 significa que la feature no esta disponible en este plan.
   *
   * @covers ::check
   */
  public function testCheckReturnsDeniedForFeatureNotIncludedInPlan(): void {
    $limitEntity = $this->createLimitEntityMock(0, 'Esta funcion no esta disponible en tu plan actual.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'mentoring_sessions_monthly', 'free');

    $this->assertFalse($result->allowed);
    $this->assertSame(0, $result->remaining);
    $this->assertSame('starter', $result->upgradePlan);
    $this->assertSame('Esta funcion no esta disponible en tu plan actual.', $result->upgradeMessage);
  }

  // =========================================================================
  // TESTS: Feature denegada cuando se alcanza el limite
  // =========================================================================

  /**
   * Verifica que check() devuelve denied cuando el limite diario se alcanza.
   *
   * @covers ::check
   */
  public function testCheckReturnsDeniedWhenDailyLimitReached(): void {
    $limitEntity = $this->createLimitEntityMock(5, 'Has alcanzado el limite de tu plan.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    // Mock: user has exhausted all 5 uses.
    $this->mockUsageCount(5);

    // Mock tenant context for upgrade trigger (non-critical, may fail silently).
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->check(1, 'copilot_sessions_daily', 'free');

    $this->assertFalse($result->allowed);
    $this->assertSame(0, $result->remaining);
    $this->assertSame(5, $result->limit);
    $this->assertSame(5, $result->used);
    $this->assertSame('Has alcanzado el limite de tu plan.', $result->upgradeMessage);
  }

  /**
   * Verifica que check() devuelve denied cuando se supera el limite.
   *
   * @covers ::check
   */
  public function testCheckReturnsDeniedWhenUsageExceedsLimit(): void {
    $limitEntity = $this->createLimitEntityMock(3, 'Limite superado.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    // Mock: user somehow has more uses than limit.
    $this->mockUsageCount(10);
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->check(1, 'bmc_drafts', 'free');

    $this->assertFalse($result->allowed);
    $this->assertSame(0, $result->remaining);
  }

  // =========================================================================
  // TESTS: Mapeo de upgrade de planes
  // =========================================================================

  /**
   * Verifica que el plan de upgrade sigue la secuencia correcta.
   *
   * @dataProvider planUpgradeDataProvider
   * @covers ::check
   */
  public function testUpgradePlanMapping(string $currentPlan, string $expectedUpgrade): void {
    $limitEntity = $this->createLimitEntityMock(0, 'Upgrade required.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'copilot_sessions_daily', $currentPlan);

    $this->assertFalse($result->allowed);
    $this->assertSame($expectedUpgrade, $result->upgradePlan);
  }

  /**
   * Data provider para mapeo de planes de upgrade.
   *
   * @return array
   *   Casos: [plan_actual, plan_upgrade_esperado].
   */
  public static function planUpgradeDataProvider(): array {
    return [
      'free -> starter' => ['free', 'starter'],
      'starter -> profesional' => ['starter', 'profesional'],
      'profesional -> business' => ['profesional', 'business'],
    ];
  }

  // =========================================================================
  // TESTS: Comportamiento por defecto para features desconocidas
  // =========================================================================

  /**
   * Verifica que una feature sin configuracion de limite se permite.
   *
   * Cuando no hay FreemiumVerticalLimit configurado para una combinacion
   * vertical+plan+feature, el acceso se permite sin restriccion.
   *
   * @covers ::check
   */
  public function testUnknownFeatureIsAllowedByDefault(): void {
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn(NULL);

    $result = $this->service->check(1, 'completely_unknown_feature', 'free');

    $this->assertTrue($result->allowed);
    $this->assertSame('completely_unknown_feature', $result->featureKey);
  }

  // =========================================================================
  // TESTS: Cross-vertical isolation
  // =========================================================================

  /**
   * Verifica que el servicio usa el vertical correcto (emprendimiento).
   *
   * El servicio de emprendimiento siempre consulta limites para
   * vertical='emprendimiento', independientemente del feature_key.
   *
   * @covers ::check
   */
  public function testServiceUsesCorrectVerticalId(): void {
    $this->upgradeTrigger->expects($this->once())
      ->method('getVerticalLimit')
      ->with('emprendimiento', 'free', 'hypotheses_active')
      ->willReturn(NULL);

    $this->service->check(1, 'hypotheses_active', 'free');
  }

  /**
   * Verifica que distintos verticales tienen limites independientes.
   *
   * Un limite configurado para emprendimiento NO afecta a otros verticales.
   * Este test verifica que el vertical correcto se pasa al UpgradeTriggerService.
   *
   * @covers ::check
   */
  public function testVerticalIsolationInLimitQuery(): void {
    // Mock: emprendimiento has limit of 5 for hypotheses_active.
    $limitEntity = $this->createLimitEntityMock(5, '');
    $this->upgradeTrigger->expects($this->once())
      ->method('getVerticalLimit')
      ->with(
        $this->equalTo('emprendimiento'),
        $this->anything(),
        $this->anything(),
      )
      ->willReturn($limitEntity);

    $this->mockUsageCount(2);

    $result = $this->service->check(1, 'hypotheses_active', 'starter');

    $this->assertTrue($result->allowed);
    $this->assertSame(3, $result->remaining);
  }

  // =========================================================================
  // TESTS: Todas las features del vertical emprendimiento
  // =========================================================================

  /**
   * Verifica que todas las features del vertical emprendimiento son gateables.
   *
   * @dataProvider emprendimientoFeatureKeysProvider
   * @covers ::check
   */
  public function testAllEmprendimientoFeaturesAreGateable(string $featureKey): void {
    $limitEntity = $this->createLimitEntityMock(0, 'Not available.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(99, $featureKey, 'free');

    $this->assertFalse(
      $result->allowed,
      "Feature '$featureKey' should be denied when limit_value=0.",
    );
  }

  /**
   * Data provider para features del vertical emprendimiento.
   *
   * @return array
   *   Array de feature keys.
   */
  public static function emprendimientoFeatureKeysProvider(): array {
    return [
      'hypotheses_active' => ['hypotheses_active'],
      'experiments_monthly' => ['experiments_monthly'],
      'copilot_sessions_daily' => ['copilot_sessions_daily'],
      'mentoring_sessions_monthly' => ['mentoring_sessions_monthly'],
      'bmc_drafts' => ['bmc_drafts'],
      'calculadora_uses' => ['calculadora_uses'],
    ];
  }

  // =========================================================================
  // TESTS: recordUsage()
  // =========================================================================

  /**
   * Verifica que recordUsage() inserta un nuevo registro cuando no existe.
   *
   * @covers ::recordUsage
   */
  public function testRecordUsageInsertsNewRecordWhenNoneExists(): void {
    // Mock select: no existing record.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchObject')->willReturn(FALSE);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('emprendimiento_feature_usage', 'u')
      ->willReturn($select);

    // Mock insert.
    $insert = $this->createMock(Insert::class);
    $insert->method('fields')->willReturnSelf();
    $insert->expects($this->once())->method('execute');

    $this->database->method('insert')
      ->with('emprendimiento_feature_usage')
      ->willReturn($insert);

    $this->logger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Feature usage recorded'),
        $this->anything(),
      );

    $this->service->recordUsage(1, 'hypotheses_active');
  }

  /**
   * Verifica que recordUsage() incrementa el contador existente.
   *
   * @covers ::recordUsage
   */
  public function testRecordUsageUpdatesExistingRecord(): void {
    // Mock select: existing record with usage_count = 3.
    $existing = (object) ['id' => 42, 'usage_count' => 3];
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchObject')->willReturn($existing);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('emprendimiento_feature_usage', 'u')
      ->willReturn($select);

    // Mock update: should set usage_count to 4.
    $update = $this->createMock(Update::class);
    $update->method('fields')
      ->with(['usage_count' => 4])
      ->willReturnSelf();
    $update->method('condition')->willReturnSelf();
    $update->expects($this->once())->method('execute');

    $this->database->method('update')
      ->with('emprendimiento_feature_usage')
      ->willReturn($update);

    $this->service->recordUsage(1, 'experiments_monthly');
  }

  // =========================================================================
  // TESTS: getUserPlan()
  // =========================================================================

  /**
   * Verifica que getUserPlan() devuelve 'free' cuando no hay tenant.
   *
   * @covers ::getUserPlan
   */
  public function testGetUserPlanReturnsFreeWhenNoTenant(): void {
    $this->tenantContext->method('getCurrentTenant')
      ->willReturn(NULL);

    $result = $this->service->getUserPlan(1);
    $this->assertSame('free', $result);
  }

  /**
   * Verifica que getUserPlan() devuelve 'free' cuando el servicio lanza excepcion.
   *
   * @covers ::getUserPlan
   */
  public function testGetUserPlanReturnsFreeOnException(): void {
    $this->tenantContext->method('getCurrentTenant')
      ->willThrowException(new \Exception('Service unavailable'));

    $result = $this->service->getUserPlan(1);
    $this->assertSame('free', $result);
  }

  // =========================================================================
  // TESTS: FeatureGateResult Value Object
  // =========================================================================

  /**
   * Verifica que el FeatureGateResult devuelve datos correctos via toArray().
   *
   * @covers ::check
   */
  public function testFeatureGateResultToArray(): void {
    $limitEntity = $this->createLimitEntityMock(10, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $this->mockUsageCount(3);

    $result = $this->service->check(1, 'experiments_monthly', 'starter');

    $array = $result->toArray();

    $this->assertTrue($array['allowed']);
    $this->assertSame(7, $array['remaining']);
    $this->assertSame(10, $array['limit']);
    $this->assertSame(3, $array['used']);
    $this->assertSame('experiments_monthly', $array['feature_key']);
    $this->assertSame('starter', $array['current_plan']);
    $this->assertSame('', $array['upgrade_message']);
    $this->assertSame('', $array['upgrade_plan']);
  }

  /**
   * Verifica que FeatureGateResult denied incluye datos de upgrade.
   *
   * @covers ::check
   */
  public function testFeatureGateResultDeniedIncludesUpgradeData(): void {
    $limitEntity = $this->createLimitEntityMock(0, 'Mejora tu plan para desbloquear.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $result = $this->service->check(1, 'copilot_sessions_daily', 'free');

    $array = $result->toArray();

    $this->assertFalse($array['allowed']);
    $this->assertSame('starter', $array['upgrade_plan']);
    $this->assertSame('Mejora tu plan para desbloquear.', $array['upgrade_message']);
    $this->assertSame('free', $array['current_plan']);
  }

  // =========================================================================
  // TESTS: Edge cases
  // =========================================================================

  /**
   * Verifica que check() con usage exactamente en el limite devuelve denied.
   *
   * Boundary test: 5/5 usos = denied.
   *
   * @covers ::check
   */
  public function testCheckDeniedAtExactLimit(): void {
    $limitEntity = $this->createLimitEntityMock(5, 'Limite alcanzado.');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $this->mockUsageCount(5);
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $result = $this->service->check(1, 'hypotheses_active', 'free');

    $this->assertFalse($result->allowed);
    $this->assertSame(0, $result->remaining);
  }

  /**
   * Verifica que check() con usage justo debajo del limite devuelve allowed.
   *
   * Boundary test: 4/5 usos = allowed con 1 remaining.
   *
   * @covers ::check
   */
  public function testCheckAllowedJustUnderLimit(): void {
    $limitEntity = $this->createLimitEntityMock(5, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $this->mockUsageCount(4);

    $result = $this->service->check(1, 'hypotheses_active', 'starter');

    $this->assertTrue($result->allowed);
    $this->assertSame(1, $result->remaining);
    $this->assertSame(5, $result->limit);
    $this->assertSame(4, $result->used);
  }

  /**
   * Verifica que check() con zero usage devuelve el limite completo.
   *
   * @covers ::check
   */
  public function testCheckWithZeroUsageReturnsFullLimit(): void {
    $limitEntity = $this->createLimitEntityMock(20, '');
    $this->upgradeTrigger->method('getVerticalLimit')
      ->willReturn($limitEntity);

    $this->mockUsageCount(0);

    $result = $this->service->check(1, 'experiments_monthly', 'profesional');

    $this->assertTrue($result->allowed);
    $this->assertSame(20, $result->remaining);
    $this->assertSame(20, $result->limit);
    $this->assertSame(0, $result->used);
  }

}
