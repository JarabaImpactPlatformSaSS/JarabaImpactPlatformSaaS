<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_andalucia_ei\Service\AccesoProgramaService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AccesoProgramaService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AccesoProgramaService
 * @group jaraba_andalucia_ei
 */
class AccesoProgramaServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AccesoProgramaService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * Mock current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock tenant context.
   */
  protected object $tenantContext;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // MOCK-DYNPROP-001: Anonymous class for tenant context.
    $this->tenantContext = new class() {
      private ?int $tenantId = 5;

      /**
       *
       */
      public function getCurrentTenantId(): ?int {
        return $this->tenantId;
      }

      /**
       *
       */
      public function setTenantId(?int $id): void {
        $this->tenantId = $id;
      }

    };

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->storage);

    $this->service = new AccesoProgramaService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->tenantContext,
      $this->logger,
    );
  }

  /**
   * @covers ::puedeAccederPortalParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function puedeAccederPortalParticipanteConParticipanteActivoDevuelveTrue(): void {
    $account = $this->createAccountMock(10, [], []);

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
    ]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $this->assertTrue($this->service->puedeAccederPortalParticipante($account));
  }

  /**
   * @covers ::puedeAccederPortalParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function puedeAccederPortalParticipanteSinParticipanteDevuelveFalse(): void {
    $account = $this->createAccountMock(20, [], []);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $this->assertFalse($this->service->puedeAccederPortalParticipante($account));
  }

  /**
   * @covers ::puedeAccederDashboardCoordinador
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function puedeAccederDashboardCoordinadorConPermisoAdminDevuelveTrue(): void {
    $account = $this->createAccountMock(1, ['administer andalucia ei'], []);

    $this->assertTrue($this->service->puedeAccederDashboardCoordinador($account));
  }

  /**
   * @covers ::puedeAccederDashboardCoordinador
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function puedeAccederDashboardCoordinadorConPermisoViewDevuelveTrue(): void {
    $account = $this->createAccountMock(2, ['view programa participante ei'], []);

    $this->assertTrue($this->service->puedeAccederDashboardCoordinador($account));
  }

  /**
   * @covers ::puedeAccederDashboardCoordinador
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function puedeAccederDashboardCoordinadorSinPermisosDevuelveFalse(): void {
    $account = $this->createAccountMock(3, [], []);

    $this->assertFalse($this->service->puedeAccederDashboardCoordinador($account));
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRolProgramaUsuarioDevuelveCoordinadorConPermisoAdmin(): void {
    $account = $this->createAccountMock(1, ['administer andalucia ei'], ['authenticated']);

    $this->assertSame('coordinador', $this->service->getRolProgramaUsuario($account));
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRolProgramaUsuarioDevuelveOrientadorConRol(): void {
    $account = $this->createAccountMock(2, [], ['authenticated', 'orientador_ei']);

    $this->assertSame('orientador', $this->service->getRolProgramaUsuario($account));
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRolProgramaUsuarioDevuelveFormadorConRol(): void {
    $account = $this->createAccountMock(3, [], ['authenticated', 'formador_ei']);

    $this->assertSame('formador', $this->service->getRolProgramaUsuario($account));
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRolProgramaUsuarioDevuelveParticipanteConRegistroActivo(): void {
    $account = $this->createAccountMock(4, [], ['authenticated']);

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
    ]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $this->assertSame('participante', $this->service->getRolProgramaUsuario($account));
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRolProgramaUsuarioDevuelveAlumniConFaseAlumni(): void {
    $account = $this->createAccountMock(5, [], ['authenticated']);

    $participante = $this->createParticipanteMock(2, [
      'fase_actual' => 'alumni',
    ]);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([2 => 2]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(2)->willReturn($participante);

    $this->assertSame('alumni', $this->service->getRolProgramaUsuario($account));
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getRolProgramaUsuarioDevuelveNoneSinRegistro(): void {
    $account = $this->createAccountMock(6, [], ['authenticated']);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $this->assertSame('none', $this->service->getRolProgramaUsuario($account));
  }

  /**
   * @covers ::getParticipanteActivo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getParticipanteActivoFiltraPorTenant(): void {
    $account = $this->createAccountMock(10, [], []);

    $participante = $this->createParticipanteMock(1, [
      'fase_actual' => 'atencion',
      'tenant_id_target' => 5,
    ]);

    $conditionCalls = [];
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnCallback(function (string $field) use ($query, &$conditionCalls) {
      $conditionCalls[] = $field;
      return $query;
    });
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1]);

    $this->storage->method('getQuery')->willReturn($query);
    $this->storage->method('load')->with(1)->willReturn($participante);

    $result = $this->service->getParticipanteActivo($account);

    $this->assertNotNull($result);
    // Verify tenant_id condition was applied.
    $this->assertContains('tenant_id', $conditionCalls);
  }

  /**
   * Crea un mock de cuenta de usuario.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   */
  protected function createAccountMock(int $uid, array $permissions, array $roles): AccountInterface {
    return new class($uid, $permissions, $roles) implements AccountInterface {

      public function __construct(
        private readonly int $uid,
        private readonly array $permissions,
        private readonly array $roles,
      ) {}

      /**
       *
       */
      public function id() {
        return $this->uid;
      }

      /**
       *
       */
      public function getRoles($exclude_locked_roles = FALSE) {
        return $this->roles;
      }

      /**
       *
       */
      public function hasPermission(string $permission) {
        return in_array($permission, $this->permissions, TRUE);
      }

      /**
       *
       */
      public function isAuthenticated() {
        return TRUE;
      }

      /**
       *
       */
      public function isAnonymous() {
        return FALSE;
      }

      /**
       *
       */
      public function getPreferredLangcode($fallback_to_default = TRUE) {
        return 'es';
      }

      /**
       *
       */
      public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
        return 'es';
      }

      /**
       *
       */
      public function getAccountName() {
        return "user_{$this->uid}";
      }

      /**
       *
       */
      public function getDisplayName() {
        return "User {$this->uid}";
      }

      /**
       *
       */
      public function getEmail() {
        return "user{$this->uid}@test.com";
      }

      /**
       *
       */
      public function getTimeZone() {
        return 'Europe/Madrid';
      }

      /**
       *
       */
      public function getLastAccessedTime() {
        return time();
      }

    };
  }

  /**
   * Crea un mock de participante.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   * TEST-CACHE-001: Cache metadata implementado.
   */
  protected function createParticipanteMock(int $id, array $fieldValues): object {
    return new class($id, $fieldValues) {

      public function __construct(
        private readonly int $id,
        private readonly array $fieldValues,
      ) {}

      /**
       *
       */
      public function id(): int {
        return $this->id;
      }

      /**
       *
       */
      public function label(): ?string {
        return "Test #{$this->id}";
      }

      /**
       *
       */
      public function get(string $fieldName): object {
        if ($fieldName === 'tenant_id') {
          $targetId = $this->fieldValues['tenant_id_target'] ?? NULL;
          return new class($targetId) {
            public mixed $target_id;

            public function __construct(mixed $t) {
              $this->target_id = $t;
            }

          };
        }
        $value = $this->fieldValues[$fieldName] ?? NULL;
        return new class($value) {

          public function __construct(public readonly mixed $value) {}

        };
      }

      /**
       *
       */
      public function set(string $fieldName, mixed $value): static {
        return $this;
      }

      /**
       *
       */
      public function save(): int {
        return 1;
      }

      /**
       *
       */
      public function getOwner(): ?object {
        return NULL;
      }

      /**
       *
       */
      public function getCacheContexts(): array {
        return [];
      }

      /**
       *
       */
      public function getCacheTags(): array {
        return ["programa_participante_ei:{$this->id}"];
      }

      /**
       *
       */
      public function getCacheMaxAge(): int {
        return -1;
      }

    };
  }

}
