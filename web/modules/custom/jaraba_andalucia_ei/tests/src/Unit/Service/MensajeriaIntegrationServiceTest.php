<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\MensajeriaIntegrationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para MensajeriaIntegrationService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\MensajeriaIntegrationService
 * @group jaraba_andalucia_ei
 */
class MensajeriaIntegrationServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected MensajeriaIntegrationService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new MensajeriaIntegrationService(
      $this->entityTypeManager,
      NULL,
      $this->logger,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConMessagingNull(): void {
    $service = new MensajeriaIntegrationService(
      $this->entityTypeManager,
      NULL,
      $this->logger,
    );

    $this->assertInstanceOf(MensajeriaIntegrationService::class, $service);
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConMessagingService(): void {
    $messagingService = new class {

      /**
       *
       */
      public function createConversation(array $participants, string $title, string $type, string $context, string $contextId): array {
        return ['id' => 1];
      }

    };

    $service = new MensajeriaIntegrationService(
      $this->entityTypeManager,
      $messagingService,
      $this->logger,
    );

    $this->assertInstanceOf(MensajeriaIntegrationService::class, $service);
  }

  /**
   * @covers ::crearConversacionMentoring
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function crearConversacionMentoringDevuelveNullSinMessaging(): void {
    $result = $this->service->crearConversacionMentoring(1, 2);
    $this->assertNull($result);
  }

  /**
   * @covers ::enviarMensajeSistema
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function enviarMensajeSistemaNoLanzaExcepcionSinMessaging(): void {
    // Sin messagingService, no debe lanzar excepcion.
    $this->service->enviarMensajeSistema(1, 'Test message');
    $this->addToAssertionCount(1);
  }

}
