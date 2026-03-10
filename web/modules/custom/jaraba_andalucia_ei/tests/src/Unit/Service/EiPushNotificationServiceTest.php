<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\EiPushNotificationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EiPushNotificationService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\EiPushNotificationService
 * @group jaraba_andalucia_ei
 */
class EiPushNotificationServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected EiPushNotificationService $service;

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
      ->willReturn($this->storage);

    $this->service = new EiPushNotificationService(
      $this->entityTypeManager,
      $this->logger,
      NULL,
      NULL,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionCorrecta(): void {
    $this->assertInstanceOf(EiPushNotificationService::class, $this->service);
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConServiciosOpcionales(): void {
    $pushService = new class {
      public function sendToUser(int $uid, array $data): void {}
    };
    $notificationService = new class {
      public function sendEmail(int $uid, array $data): void {}
    };

    $service = new EiPushNotificationService(
      $this->entityTypeManager,
      $this->logger,
      $pushService,
      $notificationService,
    );

    $this->assertInstanceOf(EiPushNotificationService::class, $service);
  }

  /**
   * Verifica que EVENT_TYPES contiene las claves esperadas.
   *
   * @covers ::EVENT_TYPES
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function eventTypesContieneClavesEsperadas(): void {
    $expectedKeys = [
      'firma_pendiente',
      'firma_completada',
      'cambio_fase',
      'sesion_mentoring',
      'badge_obtenido',
      'pill_dia',
      'match_empresa',
      'derivacion_urgente',
    ];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, EiPushNotificationService::EVENT_TYPES);
    }

    $this->assertCount(count($expectedKeys), EiPushNotificationService::EVENT_TYPES);
  }

  /**
   * Verifica estructura de cada tipo de evento.
   *
   * @covers ::EVENT_TYPES
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function eventTypesEstructuraCorrecta(): void {
    foreach (EiPushNotificationService::EVENT_TYPES as $key => $eventType) {
      $this->assertArrayHasKey('label', $eventType, "EventType '$key' debe tener 'label'.");
      $this->assertArrayHasKey('prioridad', $eventType, "EventType '$key' debe tener 'prioridad'.");
      $this->assertArrayHasKey('canales', $eventType, "EventType '$key' debe tener 'canales'.");
      $this->assertIsArray($eventType['canales'], "EventType '$key' canales debe ser array.");
      $this->assertArrayHasKey('icono', $eventType, "EventType '$key' debe tener 'icono'.");
      $this->assertArrayHasKey('titulo_template', $eventType, "EventType '$key' debe tener 'titulo_template'.");
    }
  }

  /**
   * Verifica que derivacion_urgente tiene prioridad critica.
   *
   * @covers ::EVENT_TYPES
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function derivacionUrgenteEsCritica(): void {
    $this->assertEquals('critica', EiPushNotificationService::EVENT_TYPES['derivacion_urgente']['prioridad']);
  }

  /**
   * @covers ::notificar
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function notificarTipoDesconocidoNoLanzaExcepcion(): void {
    // Debe logear warning pero no lanzar excepcion.
    $this->logger->expects($this->atLeastOnce())
      ->method('warning');

    $this->service->notificar(1, 'tipo_inexistente', []);

    // Si llegamos aqui sin excepcion, el test pasa.
    $this->assertTrue(TRUE);
  }

  /**
   * @covers ::notificarMasivo
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function notificarMasivoSinServiciosDevuelveCero(): void {
    // Sin pushService ni notificationService, las notificaciones se "envian"
    // pero sin canal efectivo. El metodo no lanza excepcion.
    // guardarNotificacionInterna puede fallar silenciosamente.
    $result = $this->service->notificarMasivo([], 'firma_pendiente');

    $this->assertIsInt($result);
    $this->assertEquals(0, $result);
  }

  /**
   * @covers ::getPendientesNoLeidas
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getPendientesNoLeidasSinServicioDevuelveArray(): void {
    $query = $this->createMock(\Drupal\Core\Entity\Query\QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getPendientesNoLeidas(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
