<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\HumanMentorshipTracker;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para HumanMentorshipTracker.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\HumanMentorshipTracker
 * @group jaraba_andalucia_ei
 */
class HumanMentorshipTrackerTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected HumanMentorshipTracker $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage para mentoring_session.
   */
  protected EntityStorageInterface $sessionStorage;

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
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->sessionStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'mentoring_session') {
          return $this->sessionStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new HumanMentorshipTracker(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionExitosa(): void {
    $this->assertInstanceOf(HumanMentorshipTracker::class, $this->service);
  }

  /**
   * @covers ::registrarSesionHumana
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function registrarSesionHumanaNoLanzaExcepcionCuandoSessionNoExiste(): void {
    $this->sessionStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    // No debe lanzar excepcion — simplemente retorna sin efecto.
    $this->service->registrarSesionHumana(999);

    // Si llegamos aqui sin excepcion, el test pasa.
    $this->addToAssertionCount(1);
  }

  /**
   * @covers ::getHorasHumanas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getHorasHumanasDevuelveCeroCuandoParticipanteNoExiste(): void {
    $participanteStorage = $this->createMock(EntityStorageInterface::class);
    $participanteStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($participanteStorage) {
        if ($type === 'programa_participante_ei') {
          return $participanteStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $service = new HumanMentorshipTracker(
      $entityTypeManager,
      $this->logger,
    );

    $result = $service->getHorasHumanas(999);
    $this->assertSame(0.0, $result);
  }

}
