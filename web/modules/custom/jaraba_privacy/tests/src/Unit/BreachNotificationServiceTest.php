<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_privacy\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_privacy\Service\BreachNotificationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para BreachNotificationService.
 *
 * Verifica el registro de brechas, evaluación de impacto, notificación AEPD,
 * timeline y cierre de incidentes según RGPD Art. 33-34.
 *
 * @group jaraba_privacy
 * @coversDefaultClass \Drupal\jaraba_privacy\Service\BreachNotificationService
 */
class BreachNotificationServiceTest extends UnitTestCase {

  protected BreachNotificationService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected ConfigFactoryInterface $configFactory;
  protected MailManagerInterface $mailManager;
  protected QueueFactory $queueFactory;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Configuración por defecto.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['breach_notification_hours', 72],
      ['dpo_email', 'dpo@test.com'],
      ['dpo_name', 'Test DPO'],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_privacy.settings')
      ->willReturn($config);

    $this->service = new BreachNotificationService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
      $this->mailManager,
      $this->queueFactory,
      $this->logger,
    );
  }

  /**
   * Verifica las constantes de severidad.
   */
  public function testSeverityConstants(): void {
    $this->assertEquals('low', BreachNotificationService::SEVERITY_LOW);
    $this->assertEquals('medium', BreachNotificationService::SEVERITY_MEDIUM);
    $this->assertEquals('high', BreachNotificationService::SEVERITY_HIGH);
    $this->assertEquals('critical', BreachNotificationService::SEVERITY_CRITICAL);
  }

  /**
   * Verifica las constantes de estado del ciclo de vida.
   */
  public function testStatusConstants(): void {
    $this->assertEquals('detected', BreachNotificationService::STATUS_DETECTED);
    $this->assertEquals('assessing', BreachNotificationService::STATUS_ASSESSING);
    $this->assertEquals('notified_aepd', BreachNotificationService::STATUS_NOTIFIED_AEPD);
    $this->assertEquals('notified_users', BreachNotificationService::STATUS_NOTIFIED_USERS);
    $this->assertEquals('remediating', BreachNotificationService::STATUS_REMEDIATING);
    $this->assertEquals('closed', BreachNotificationService::STATUS_CLOSED);
  }

  /**
   * Verifica que assessImpact lanza excepción para brecha inexistente.
   *
   * @covers ::assessImpact
   */
  public function testAssessImpactThrowsOnInvalidBreach(): void {
    $this->expectException(\RuntimeException::class);
    $this->service->assessImpact('invalid_id');
  }

  /**
   * Verifica que notifyAepd lanza excepción para brecha inexistente.
   *
   * @covers ::notifyAepd
   */
  public function testNotifyAepdThrowsOnInvalidBreach(): void {
    $this->expectException(\RuntimeException::class);
    $this->service->notifyAepd('invalid_id');
  }

  /**
   * Verifica que closeIncident lanza excepción para brecha inexistente.
   *
   * @covers ::closeIncident
   */
  public function testCloseIncidentThrowsOnInvalidBreach(): void {
    $this->expectException(\RuntimeException::class);
    $this->service->closeIncident('invalid_id', 'Causa', 'Plan', 1);
  }

  /**
   * Verifica que getBreachTimeline devuelve array vacío para brecha inexistente.
   *
   * @covers ::getBreachTimeline
   */
  public function testGetBreachTimelineReturnsEmptyForInvalidBreach(): void {
    $result = $this->service->getBreachTimeline('invalid_id');
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que notifyAffectedUsers no falla con brecha inexistente.
   *
   * @covers ::notifyAffectedUsers
   */
  public function testNotifyAffectedUsersHandlesInvalidBreach(): void {
    // No debe lanzar excepción, simplemente retornar.
    $this->service->notifyAffectedUsers('invalid_id', 'Test message');
    $this->assertTrue(TRUE);
  }

}
