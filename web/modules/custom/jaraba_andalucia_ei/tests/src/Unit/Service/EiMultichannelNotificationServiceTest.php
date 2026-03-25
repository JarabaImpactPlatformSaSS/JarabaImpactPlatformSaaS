<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_andalucia_ei\Service\EiMultichannelNotificationService;
use Drupal\jaraba_andalucia_ei\Service\EiPushNotificationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para EiMultichannelNotificationService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\EiMultichannelNotificationService
 * @group jaraba_andalucia_ei
 */
class EiMultichannelNotificationServiceTest extends UnitTestCase {

  protected EiMultichannelNotificationService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $participanteStorage;
  protected LoggerInterface $logger;
  protected EiPushNotificationService $pushService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->participanteStorage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->pushService = $this->createMock(EiPushNotificationService::class);

    $this->entityTypeManager->method('getStorage')
      ->with('programa_participante_ei')
      ->willReturn($this->participanteStorage);
  }

  /**
   * Notificar con participante inexistente devuelve todos los canales FALSE.
   *
   * @covers ::notificar
   */
  public function testNotificarParticipanteNoEncontradoDevuelveTodosFalse(): void {
    $service = $this->buildService();
    $this->participanteStorage->method('load')->with(999)->willReturn(NULL);

    $this->logger->expects($this->once())->method('warning');

    $result = $service->notificar(999, 'cambio_fase', ['titulo' => 'Test']);

    $this->assertFalse($result['push']);
    $this->assertFalse($result['wa']);
    $this->assertFalse($result['app']);
  }

  /**
   * Notificar enruta 'sesion_recordatorio_24h' a push + wa según CHANNEL_ROUTING.
   * Sin WhatsApp service, wa queda FALSE; con push service, push queda TRUE.
   *
   * @covers ::notificar
   */
  public function testNotificarEnrutaRecordatorio24hAPushYWa(): void {
    // Con pushService disponible, sin whatsapp, sin in-app.
    $service = $this->buildService(pushService: $this->pushService);

    $participante = $this->createParticipanteMock(uid: 10, tenantId: 1);
    $this->participanteStorage->method('load')->with(10)->willReturn($participante);

    $this->pushService->expects($this->once())
      ->method('notificar')
      ->with(10, 'sesion_recordatorio_24h', $this->isType('array'));

    $result = $service->notificar(10, 'sesion_recordatorio_24h', [
      'titulo' => 'Recordatorio mañana',
    ]);

    $this->assertTrue($result['push']);
    // Sin whatsapp service → FALSE aunque el tipo lo incluye.
    $this->assertFalse($result['wa']);
    // 'sesion_recordatorio_24h' no tiene 'app' en su routing → FALSE.
    $this->assertFalse($result['app']);
  }

  /**
   * GDPR: WhatsApp NO se envía si acepta_whatsapp es FALSE.
   *
   * @covers ::notificar
   */
  public function testNotificarNoEnviaWhatsAppSinConsentimientoGdpr(): void {
    $whatsappService = new class {
      public int $callCount = 0;

      /**
       *
       */
      public function sendTextMessage(string $phone, string $msg): array {
        $this->callCount++;
        return ['success' => TRUE];
      }

    };

    $service = $this->buildService(whatsappService: $whatsappService);

    // Participante sin consentimiento WhatsApp.
    $participante = $this->createParticipanteMock(
      uid: 20,
      tenantId: 2,
      aceptaWhatsapp: FALSE,
      telefono: '+34600111222',
    );
    $this->participanteStorage->method('load')->with(20)->willReturn($participante);

    $result = $service->notificar(20, 'firma_pendiente', ['titulo' => 'Firma tu documento']);

    $this->assertFalse($result['wa']);
    $this->assertEquals(0, $whatsappService->callCount, 'sendTextMessage NO debe llamarse sin consentimiento GDPR');
  }

  /**
   * GDPR: WhatsApp SÍ se envía si acepta_whatsapp es TRUE y hay teléfono.
   *
   * @covers ::notificar
   */
  public function testNotificarEnviaWhatsAppConConsentimientoYTelefono(): void {
    $whatsappService = new class {
      public int $callCount = 0;

      /**
       *
       */
      public function sendTextMessage(string $phone, string $msg): array {
        $this->callCount++;
        return ['success' => TRUE];
      }

    };

    $service = $this->buildService(whatsappService: $whatsappService);

    // Participante con consentimiento y teléfono.
    $participante = $this->createParticipanteMock(
      uid: 21,
      tenantId: 2,
      aceptaWhatsapp: TRUE,
      telefono: '+34600333444',
    );
    $this->participanteStorage->method('load')->with(21)->willReturn($participante);

    $result = $service->notificar(21, 'firma_pendiente', ['titulo' => 'Firma tu documento']);

    $this->assertTrue($result['wa']);
    $this->assertEquals(1, $whatsappService->callCount);
  }

  /**
   * NotificarMasivo agrega los resultados de cada participante.
   *
   * Con 3 participantes y push disponible, el resumen push debe ser 3.
   *
   * @covers ::notificarMasivo
   */
  public function testNotificarMasivoAgregaResultadosPorCanal(): void {
    $service = $this->buildService(pushService: $this->pushService);

    // Tres participantes distintos: usar willReturnMap para evitar que el
    // último with() en el bucle sobreescriba los anteriores.
    $loadMap = [];
    foreach ([30, 31, 32] as $id) {
      $p = $this->createParticipanteMock(uid: $id + 100, tenantId: 5);
      $loadMap[] = [$id, $p];
    }
    $this->participanteStorage->method('load')->willReturnMap($loadMap);

    // pushService debe llamarse 3 veces.
    $this->pushService->expects($this->exactly(3))->method('notificar');

    $resumen = $service->notificarMasivo([30, 31, 32], 'cambio_fase', [
      'titulo' => 'Has avanzado de fase',
    ]);

    $this->assertEquals(3, $resumen['total']);
    $this->assertEquals(3, $resumen['push']);
    $this->assertEquals(0, $resumen['wa']);
    $this->assertEquals(0, $resumen['app']);
  }

  /**
   * NotificarMasivo con lista vacía devuelve resumen con ceros y total 0.
   *
   * @covers ::notificarMasivo
   */
  public function testNotificarMasivoListaVaciaDevuelveCeros(): void {
    $service = $this->buildService();

    $resumen = $service->notificarMasivo([], 'badge_obtenido', []);

    $this->assertEquals(0, $resumen['total']);
    $this->assertEquals(0, $resumen['push']);
    $this->assertEquals(0, $resumen['wa']);
    $this->assertEquals(0, $resumen['app']);
  }

  /**
   * Construye el servicio con dependencias opcionales configurables.
   */
  protected function buildService(
    ?EiPushNotificationService $pushService = NULL,
    ?object $whatsappService = NULL,
    ?object $notificationService = NULL,
  ): EiMultichannelNotificationService {
    return new EiMultichannelNotificationService(
      $this->entityTypeManager,
      $this->logger,
      $pushService,
      $whatsappService,
      $notificationService,
    );
  }

  /**
   * Crea un mock de participante.
   *
   * Sigue MOCK-METHOD-001: se usa ProgramaParticipanteEiInterface porque
   * incluye getOwnerId() (de EntityOwnerInterface), hasField() y get().
   * Sigue TEST-CACHE-001.
   * Sigue MOCK-DYNPROP-001: las propiedades tipadas se definen en clases
   * anónimas que implementan FieldItemListInterface, no como dynamic props.
   */
  protected function createParticipanteMock(
    int $uid,
    int $tenantId,
    bool $aceptaWhatsapp = FALSE,
    string $telefono = '',
  ): ProgramaParticipanteEiInterface {
    $participante = $this->createMock(ProgramaParticipanteEiInterface::class);
    $participante->method('getOwnerId')->willReturn($uid);

    // TEST-CACHE-001.
    $participante->method('getCacheContexts')->willReturn([]);
    $participante->method('getCacheTags')->willReturn(['programa_participante_ei:' . $uid]);
    $participante->method('getCacheMaxAge')->willReturn(-1);

    // El servicio accede a ->target_id, ->value directamente.
    // Clases anónimas con typed properties (MOCK-DYNPROP-001).
    $tenantFieldReal = new class($tenantId) {
      public int $target_id;

      public function __construct(int $tid) {
        $this->target_id = $tid;
      }

    };

    // Campo acepta_whatsapp: el servicio lee ->value como bool.
    $waField = new class($aceptaWhatsapp) {
      public bool $value;

      public function __construct(bool $v) {
        $this->value = $v;
      }

    };

    // Campo telefono: el servicio lee ->value (string) e isEmpty().
    $telefonoField = new class($telefono) {
      public string $value;

      public function __construct(string $v) {
        $this->value = $v;
      }

      /**
       *
       */
      public function isEmpty(): bool {
        return $this->value === '';
      }

    };

    $participante->method('hasField')
      ->willReturnMap([
        ['acepta_whatsapp', TRUE],
        ['telefono', TRUE],
      ]);

    $participante->method('get')
      ->willReturnMap([
        ['tenant_id', $tenantFieldReal],
        ['acepta_whatsapp', $waField],
        ['telefono', $telefonoField],
      ]);

    return $participante;
  }

}
