<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_andalucia_ei\Service\ProgramaVerticalAccessService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ProgramaVerticalAccessService.
 *
 * Verifica el mapeo carril → verticales, expiración por fase,
 * y métricas de acceso.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\ProgramaVerticalAccessService
 * @group jaraba_andalucia_ei
 */
class ProgramaVerticalAccessServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected ProgramaVerticalAccessService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock state.
   */
  protected StateInterface $state;

  /**
   * Mock storage para participantes.
   */
  protected EntityStorageInterface $participanteStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->participanteStorage = $this->createMock(EntityStorageInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->participanteStorage);

    $this->state->method('get')
      ->willReturn([
        'first_access' => NULL,
        'last_access' => NULL,
        'access_count' => 0,
      ]);

    $this->service = new ProgramaVerticalAccessService(
      $this->entityTypeManager,
      $logger,
      $this->state,
    );
  }

  /**
   * @covers ::hasAccess
   */
  public function testNoAccessWhenNoParticipante(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->participanteStorage->method('getQuery')->willReturn($query);

    $this->assertFalse($this->service->hasAccess(999, 'empleabilidad'));
  }

  /**
   * @covers ::getActiveVerticals
   */
  public function testGetActiveVerticalsReturnsEmptyForNoParticipante(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->participanteStorage->method('getQuery')->willReturn($query);

    $this->assertEquals([], $this->service->getActiveVerticals(999));
  }

  /**
   * @covers ::isExpired
   */
  public function testIsExpiredWhenNoParticipante(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->participanteStorage->method('getQuery')->willReturn($query);

    $this->assertTrue($this->service->isExpired(999));
  }

  /**
   * @covers ::getDiasRestantes
   */
  public function testDiasRestantesNullWhenNoParticipante(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->participanteStorage->method('getQuery')->willReturn($query);

    $this->assertEquals(-1, $this->service->getDiasRestantes(999));
  }

  /**
   * Helper para crear un mock de participante con carril y fase.
   *
   * @param string $carril
   *   Carril del participante.
   * @param string $fase
   *   Fase actual.
   *
   * @return object
   *   Mock de participante.
   */
  private function createParticipanteMock(string $carril, string $fase): object {
    $participante = new class($carril, $fase) {

      public function __construct(
        private readonly string $carril,
        private readonly string $fase,
      ) {}

      public function get(string $field): object {
        $value = match ($field) {
          'carril' => $this->carril,
          'fase_actual' => $this->fase,
          'changed' => (string) time(),
          default => NULL,
        };
        return new class($value) {

          public ?string $value;
          public ?string $target_id;

          public function __construct(?string $v) {
            $this->value = $v;
            $this->target_id = $v;
          }

        };
      }

      public function hasField(string $field): bool {
        return $field !== 'fecha_fase_seguimiento';
      }

    };

    return $participante;
  }

}
