<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\EiBadgeBridgeService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EiBadgeBridgeService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\EiBadgeBridgeService
 * @group jaraba_andalucia_ei
 */
class EiBadgeBridgeServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected EiBadgeBridgeService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
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
      ->willReturnCallback(function (string $type) {
        return $this->storage;
      });

    $this->service = new EiBadgeBridgeService(
      $this->entityTypeManager,
      $this->logger,
      NULL,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionCorrecta(): void {
    $this->assertInstanceOf(EiBadgeBridgeService::class, $this->service);
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConBadgeAwardService(): void {
    $badgeAwardService = new class {

      /**
       *
       */
      public function getBadgesForEntity(string $type, int $id): array {
        return [];
      }

    };

    $service = new EiBadgeBridgeService(
      $this->entityTypeManager,
      $this->logger,
      $badgeAwardService,
    );

    $this->assertInstanceOf(EiBadgeBridgeService::class, $service);
  }

  /**
   * Verifica que BADGE_MILESTONES contiene las claves esperadas.
   *
   * @covers ::BADGE_MILESTONES
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function badgeMilestonesContieneClavesEsperadas(): void {
    $expectedKeys = [
      'ei_primera_semana',
      'ei_diagnostico_completado',
      'ei_orientacion_10h',
      'ei_formacion_25h',
      'ei_formacion_completa',
      'ei_insercion',
      'ei_emprendimiento',
      'ei_alumni',
      'ei_mentor_peer',
    ];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, EiBadgeBridgeService::BADGE_MILESTONES);
    }

    $this->assertCount(count($expectedKeys), EiBadgeBridgeService::BADGE_MILESTONES);
  }

  /**
   * Verifica estructura de cada milestone.
   *
   * @covers ::BADGE_MILESTONES
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function badgeMilestonesEstructuraCorrecta(): void {
    foreach (EiBadgeBridgeService::BADGE_MILESTONES as $key => $milestone) {
      $this->assertArrayHasKey('label', $milestone, "Milestone '$key' debe tener 'label'.");
      $this->assertArrayHasKey('campo', $milestone, "Milestone '$key' debe tener 'campo'.");
      $this->assertArrayHasKey('umbral', $milestone, "Milestone '$key' debe tener 'umbral'.");
      $this->assertArrayHasKey('tipo', $milestone, "Milestone '$key' debe tener 'tipo'.");
      $this->assertArrayHasKey('icono', $milestone, "Milestone '$key' debe tener 'icono'.");
      $this->assertArrayHasKey('orden', $milestone, "Milestone '$key' debe tener 'orden'.");
    }
  }

  /**
   * @covers ::evaluarYEmitirBadges
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function evaluarYEmitirBadgesParticipanteNoEncontrado(): void {
    $this->storage->method('load')->willReturn(NULL);

    $result = $this->service->evaluarYEmitirBadges(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getBadgesParticipante
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getBadgesParticipanteSinServicioYSinEntidad(): void {
    // Sin badgeAwardService, intenta leer de entidad.
    // Participante sin campo badges_obtenidos.
    $participante = new class {

      /**
       *
       */
      public function id(): int {
        return 1;
      }

      /**
       *
       */
      public function hasField(string $name): bool {
        return FALSE;
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
        return [];
      }

      /**
       *
       */
      public function getCacheMaxAge(): int {
        return -1;
      }

    };

    $this->storage->method('load')->willReturn($participante);

    $result = $this->service->getBadgesParticipante(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getProximoHito
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getProximoHitoParticipanteNoEncontrado(): void {
    $this->storage->method('load')->willReturn(NULL);

    $result = $this->service->getProximoHito(999);

    $this->assertNull($result);
  }

}
