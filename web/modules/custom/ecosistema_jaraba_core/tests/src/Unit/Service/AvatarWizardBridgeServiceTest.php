<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService;
use Drupal\ecosistema_jaraba_core\Service\AvatarWizardBridgeService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult;
use Drupal\Tests\UnitTestCase;

/**
 * Tests del servicio AvatarWizardBridgeService.
 *
 * Verifica la cascada JourneyState → AvatarDetection → NULL y el mapping
 * de nomenclatura dual (español JourneyState / inglés AvatarDetection).
 *
 * SETUP-WIZARD-DAILY-001 + OPTIONAL-CROSSMODULE-001
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\AvatarWizardBridgeService
 * @group ecosistema_jaraba_core
 */
class AvatarWizardBridgeServiceTest extends UnitTestCase {

  protected AccountProxyInterface $currentUser;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected AvatarDetectionService $avatarDetection;
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('isAuthenticated')->willReturn(TRUE);
    $this->currentUser->method('id')->willReturn(42);

    // EntityTypeManager — default: no journey_state definition.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')->willReturn(FALSE);

    $this->avatarDetection = $this->getMockBuilder(AvatarDetectionService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['detect'])
      ->getMock();

    $this->tenantContext = $this->getMockBuilder(TenantContextService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getCurrentTenantId'])
      ->getMock();
    $this->tenantContext->method('getCurrentTenantId')->willReturn(100);
  }

  /**
   * Crea un JourneyState mock con avatar_type dado.
   */
  protected function mockJourneyState(string $avatarType): void {
    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->value = $avatarType;

    // Clase anónima typed para evitar MOCK-DYNPROP-001.
    $journeyState = new class ($avatarType) {

      private string $avatarType;

      public function __construct(string $avatarType) {
        $this->avatarType = $avatarType;
      }

      /**
       *
       */
      public function get(string $field): object {
        return new class ($this->avatarType) {

          public ?string $value;

          public function __construct(string $val) {
            $this->value = $val;
          }

        };
      }

    };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$journeyState]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(fn(string $type) => $type === 'journey_state');
    $this->entityTypeManager->method('getStorage')->willReturn($storage);
  }

  /**
   * Configura AvatarDetection para devolver un avatar específico.
   */
  protected function mockAvatarDetection(string $avatarType, ?string $vertical = NULL): void {
    $this->avatarDetection = $this->getMockBuilder(AvatarDetectionService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['detect'])
      ->getMock();
    $this->avatarDetection->method('detect')->willReturn(
      new AvatarDetectionResult(
        avatarType: $avatarType,
        vertical: $vertical,
        detectionSource: 'role',
        programaOrigen: NULL,
        confidence: 0.8,
      ),
    );
  }

  /**
   *
   */
  protected function createBridge(
    ?AvatarDetectionService $avatarDetection = NULL,
    ?TenantContextService $tenantContext = NULL,
  ): AvatarWizardBridgeService {
    return new AvatarWizardBridgeService(
      $this->currentUser,
      $this->entityTypeManager,
      $avatarDetection,
      $tenantContext ?? $this->tenantContext,
    );
  }

  // =========================================================================
  // Nivel 1: JourneyState (avatar persistido)
  // =========================================================================

  /**
   * JourneyState con 'emprendedor' → mapea a entrepreneur_tools.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateEmprendedorMapsToEntrepreneur(): void {
    $this->mockJourneyState('emprendedor');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('entrepreneur_tools', $mapping->wizardId);
    $this->assertEquals('entrepreneur_tools', $mapping->dashboardId);
    $this->assertEquals('entrepreneur', $mapping->avatarType);
    $this->assertEquals('emprendimiento', $mapping->vertical);
    $this->assertEquals(100, $mapping->contextId, 'Tenant-scoped');
  }

  /**
   * JourneyState con 'job_seeker' → mapea a candidato_empleo.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateJobSeekerMapsToCandidate(): void {
    $this->mockJourneyState('job_seeker');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('candidato_empleo', $mapping->wizardId);
    $this->assertEquals('candidato_empleo', $mapping->dashboardId);
    $this->assertEquals('jobseeker', $mapping->avatarType);
    $this->assertEquals(42, $mapping->contextId, 'User-scoped');
  }

  /**
   * JourneyState con 'comerciante' → mapea a merchant_comercio.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateComerciante(): void {
    $this->mockJourneyState('comerciante');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('merchant_comercio', $mapping->wizardId);
    $this->assertEquals('merchant', $mapping->avatarType);
    $this->assertEquals('comercioconecta', $mapping->vertical);
  }

  /**
   * JourneyState con 'productor' → mapea a producer_agro.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateProductor(): void {
    $this->mockJourneyState('productor');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('producer_agro', $mapping->wizardId);
    $this->assertEquals('producer', $mapping->avatarType);
  }

  /**
   * JourneyState con 'profesional' → mapea a provider_servicios.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateProfesional(): void {
    $this->mockJourneyState('profesional');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('provider_servicios', $mapping->wizardId);
    $this->assertEquals('profesional', $mapping->avatarType);
    $this->assertEquals('serviciosconecta', $mapping->vertical);
  }

  /**
   * JourneyState con 'estudiante' → mapea a learner_lms.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateEstudiante(): void {
    $this->mockJourneyState('estudiante');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('learner_lms', $mapping->wizardId);
    $this->assertEquals('student', $mapping->avatarType);
    $this->assertEquals(42, $mapping->contextId, 'User-scoped');
  }

  /**
   * JourneyState con 'mentor' → identidad directa.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateMentor(): void {
    $this->mockJourneyState('mentor');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('mentor', $mapping->wizardId);
    $this->assertEquals('mentor', $mapping->avatarType);
  }

  /**
   * JourneyState 'pending' → se ignora, cae al nivel 2.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStatePendingFallsToDetection(): void {
    $this->mockJourneyState('pending');
    $this->mockAvatarDetection('entrepreneur', 'emprendimiento');
    $bridge = $this->createBridge(avatarDetection: $this->avatarDetection);

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('entrepreneur_tools', $mapping->wizardId);
  }

  // =========================================================================
  // Nivel 2: AvatarDetection (fallback contextual)
  // =========================================================================

  /**
   * Sin JourneyState, AvatarDetection 'jobseeker' funciona.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testFallbackToAvatarDetection(): void {
    // entityTypeManager sin journey_state definition (default).
    $this->mockAvatarDetection('jobseeker', 'empleabilidad');
    $bridge = $this->createBridge(avatarDetection: $this->avatarDetection);

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('candidato_empleo', $mapping->wizardId);
    $this->assertEquals('jaraba_candidate.dashboard', $mapping->dashboardRoute);
  }

  /**
   * Sin JourneyState, AvatarDetection 'merchant' funciona con tenant scope.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testFallbackMerchantTenantScoped(): void {
    $this->mockAvatarDetection('merchant', 'comercioconecta');
    $bridge = $this->createBridge(avatarDetection: $this->avatarDetection);

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('merchant_comercio', $mapping->wizardId);
    $this->assertEquals(100, $mapping->contextId);
  }

  // =========================================================================
  // Degradación grácil
  // =========================================================================

  /**
   * Avatar 'general' en AvatarDetection → NULL.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testGeneralAvatarReturnsNull(): void {
    $this->mockAvatarDetection('general');
    $this->avatarDetection = $this->getMockBuilder(AvatarDetectionService::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['detect'])
      ->getMock();
    $this->avatarDetection->method('detect')->willReturn(
      AvatarDetectionResult::createDefault(),
    );
    $bridge = $this->createBridge(avatarDetection: $this->avatarDetection);

    $this->assertNull($bridge->resolveForCurrentUser());
  }

  /**
   * Sin AvatarDetection ni JourneyState → NULL.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testNoServicesReturnsNull(): void {
    $bridge = $this->createBridge(avatarDetection: NULL);
    $this->assertNull($bridge->resolveForCurrentUser());
  }

  /**
   * Usuario no autenticado → NULL.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testUnauthenticatedReturnsNull(): void {
    $unauth = $this->createMock(AccountProxyInterface::class);
    $unauth->method('isAuthenticated')->willReturn(FALSE);

    $bridge = new AvatarWizardBridgeService(
      $unauth,
      $this->entityTypeManager,
      $this->avatarDetection,
      $this->tenantContext,
    );

    $this->assertNull($bridge->resolveForCurrentUser());
  }

  /**
   * Tenant-scoped sin TenantContext → fallback a uid.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testTenantScopedWithoutTenantContextFallsBackToUid(): void {
    $this->mockJourneyState('emprendedor');

    $bridge = new AvatarWizardBridgeService(
      $this->currentUser,
      $this->entityTypeManager,
      NULL,
      NULL,
    );

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals(42, $mapping->contextId, 'Fallback a uid');
  }

  /**
   * JourneyState con avatar en nomenclatura inglesa (legacy) funciona.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateWithEnglishAvatar(): void {
    $this->mockJourneyState('jobseeker');
    $bridge = $this->createBridge();

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('candidato_empleo', $mapping->wizardId);
    $this->assertEquals('jobseeker', $mapping->avatarType);
  }

  /**
   * JourneyState tiene prioridad sobre AvatarDetection.
   *
   * @covers ::resolveForCurrentUser
   */
  public function testJourneyStateTakesPriorityOverDetection(): void {
    // JourneyState dice 'comerciante' (merchant).
    $this->mockJourneyState('comerciante');
    // AvatarDetection diría 'entrepreneur' (diferente contexto URL).
    $this->mockAvatarDetection('entrepreneur', 'emprendimiento');

    $bridge = $this->createBridge(avatarDetection: $this->avatarDetection);

    $mapping = $bridge->resolveForCurrentUser();

    $this->assertNotNull($mapping);
    $this->assertEquals('merchant_comercio', $mapping->wizardId, 'JourneyState wins');
  }

}
