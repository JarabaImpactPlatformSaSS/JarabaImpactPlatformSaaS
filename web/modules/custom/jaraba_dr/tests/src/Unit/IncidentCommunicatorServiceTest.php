<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\jaraba_dr\Service\IncidentCommunicatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para IncidentCommunicatorService.
 *
 * Verifica la comunicación multi-canal de incidentes DR: notificaciones,
 * log de comunicaciones, escalados, constantes de canales y envío por email.
 *
 * @group jaraba_dr
 * @coversDefaultClass \Drupal\jaraba_dr\Service\IncidentCommunicatorService
 */
class IncidentCommunicatorServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected IncidentCommunicatorService $service;

  /**
   * Mock del gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock de la factoría de configuración.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock del gestor de mail.
   */
  protected MailManagerInterface $mailManager;

  /**
   * Mock de la factoría de colas.
   */
  protected QueueFactory $queueFactory;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['notification_channels', ['email']],
      ['escalation_timeout_minutes', 30],
      ['notification_email_recipients', 'admin@test.com'],
      ['slack_webhook_url', ''],
      ['notification_webhook_url', ''],
    ]);

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')
      ->with('jaraba_dr.settings')
      ->willReturn($config);

    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new IncidentCommunicatorService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->mailManager,
      $this->queueFactory,
      $this->logger,
    );
  }

  /**
   * Verifica que notifyIncident devuelve 0 para un incidente inexistente.
   *
   * Cuando el ID del incidente no existe en la base de datos,
   * el resultado debe ser 0 notificaciones enviadas.
   *
   * @covers ::notifyIncident
   */
  public function testNotifyIncidentReturnsZeroForInvalidIncident(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('dr_incident')
      ->willReturn($storage);

    // Incidente no encontrado.
    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->notifyIncident(999, 'Mensaje de prueba');

    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que addCommunicationLog devuelve FALSE para incidente inválido.
   *
   * @covers ::addCommunicationLog
   */
  public function testAddCommunicationLogReturnsFalseForInvalidIncident(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('dr_incident')
      ->willReturn($storage);

    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->addCommunicationLog(999, 'Mensaje test', 'email');

    $this->assertFalse($result);
  }

  /**
   * Verifica que checkEscalations devuelve 0 cuando no hay incidentes activos.
   *
   * @covers ::checkEscalations
   */
  public function testCheckEscalationsReturnsZeroWhenNoActiveIncidents(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dr_incident')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->checkEscalations();

    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que getIncidentCommunicationHistory devuelve vacío para inválido.
   *
   * @covers ::getIncidentCommunicationHistory
   */
  public function testGetIncidentCommunicationHistoryReturnsEmptyForInvalid(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('dr_incident')
      ->willReturn($storage);

    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->getIncidentCommunicationHistory(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica las constantes de canales de comunicación.
   *
   * @covers ::__construct
   */
  public function testChannelConstants(): void {
    $this->assertEquals('email', IncidentCommunicatorService::CHANNEL_EMAIL);
    $this->assertEquals('slack', IncidentCommunicatorService::CHANNEL_SLACK);
    $this->assertEquals('webhook', IncidentCommunicatorService::CHANNEL_WEBHOOK);
  }

  /**
   * Verifica que sendNotification con canal email intenta enviar.
   *
   * Cuando existe un incidente válido P2 (no urgente), la notificación
   * se encola en lugar de enviarse directamente.
   *
   * @covers ::notifyIncident
   */
  public function testSendNotificationWithEmailChannel(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('dr_incident')
      ->willReturn($storage);

    // Crear mock de incidente con severidad P2.
    $incident = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $incident->method('id')->willReturn('5');

    // Mock de campos del incidente.
    $severityField = new \stdClass();
    $severityField->value = 'p2_major';
    $titleField = new \stdClass();
    $titleField->value = 'Test incident';

    $incident->method('get')->willReturnMap([
      ['severity', $severityField],
      ['title', $titleField],
    ]);

    $storage->method('load')
      ->with(5)
      ->willReturn($incident);

    // Para P2, se encola — verificar que la cola se usa.
    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->atLeastOnce())
      ->method('createItem');

    $this->queueFactory->method('get')
      ->with('jaraba_dr_incident_notification')
      ->willReturn($queue);

    // El addCommunicationLog necesita cargar de nuevo el incidente.
    $communicationLogField = new \stdClass();
    $communicationLogField->value = '[]';
    $incident->method('getCommunicationLogDecoded')->willReturn([]);

    $result = $this->service->notifyIncident(5, 'Incidente de prueba P2');

    $this->assertGreaterThan(0, $result);
  }

}
