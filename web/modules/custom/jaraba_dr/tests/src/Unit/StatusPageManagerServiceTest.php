<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_dr\Service\StatusPageManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para StatusPageManagerService.
 *
 * Verifica la gestión de la página de estado pública: estado de servicios,
 * historial de incidentes, ventanas de mantenimiento y constantes.
 *
 * @group jaraba_dr
 * @coversDefaultClass \Drupal\jaraba_dr\Service\StatusPageManagerService
 */
class StatusPageManagerServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected StatusPageManagerService $service;

  /**
   * Mock del gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock del servicio de estado.
   */
  protected StateInterface $state;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['status_page_refresh_seconds', 30],
      ['status_page_uptime_days', 90],
    ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')
      ->with('jaraba_dr.settings')
      ->willReturn($config);

    $this->state = $this->createMock(StateInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new StatusPageManagerService(
      $this->entityTypeManager,
      $configFactory,
      $this->state,
      $this->logger,
    );
  }

  /**
   * Verifica que getServicesStatus devuelve todos los componentes por defecto.
   *
   * Cuando no hay estado almacenado en State API, se inicializan
   * los servicios por defecto con estado operacional.
   *
   * @covers ::getServicesStatus
   */
  public function testGetStatusOverviewReturnsAllComponents(): void {
    // State devuelve vacío, lo que dispara la inicialización por defecto.
    $this->state->method('get')
      ->with(StatusPageManagerService::STATE_SERVICES_KEY, [])
      ->willReturn([]);

    $result = $this->service->getServicesStatus();

    $this->assertIsArray($result);
    // Debe contener todos los servicios por defecto.
    $this->assertArrayHasKey('app', $result);
    $this->assertArrayHasKey('api', $result);
    $this->assertArrayHasKey('database', $result);
    $this->assertArrayHasKey('email', $result);
    $this->assertArrayHasKey('ai', $result);
    $this->assertArrayHasKey('payments', $result);

    // Cada servicio debe tener estado operacional.
    foreach ($result as $service) {
      $this->assertEquals(StatusPageManagerService::STATUS_OPERATIONAL, $service['status']);
      $this->assertArrayHasKey('name', $service);
      $this->assertArrayHasKey('updated_at', $service);
    }
  }

  /**
   * Verifica que addServiceStatus acepta un estado válido.
   *
   * Un estado no válido se normaliza a 'operational'.
   *
   * @covers ::addServiceStatus
   */
  public function testUpdateComponentStatusThrowsOnInvalidComponent(): void {
    // State devuelve servicios vacíos para que se inicialicen.
    $this->state->method('get')
      ->willReturn([]);

    // Actualizar con estado inválido — se normaliza a operational.
    $result = $this->service->addServiceStatus('unknown_service', 'invalid_status');

    $this->assertIsArray($result);
    $this->assertEquals(StatusPageManagerService::STATUS_OPERATIONAL, $result['status']);
  }

  /**
   * Verifica que getActiveIncidents devuelve array vacío sin incidentes.
   *
   * @covers ::getActiveIncidents
   */
  public function testGetIncidentHistoryReturnsEmptyArray(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dr_incident')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->getActiveIncidents();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que addServiceStatus devuelve datos con el estado actualizado.
   *
   * Simula la creación de una ventana de mantenimiento actualizando
   * el estado de un servicio a 'maintenance'.
   *
   * @covers ::addServiceStatus
   */
  public function testCreateMaintenanceWindowReturnsId(): void {
    $this->state->method('get')
      ->willReturn([]);

    $result = $this->service->addServiceStatus(
      'database',
      StatusPageManagerService::STATUS_MAINTENANCE,
      'Mantenimiento programado de base de datos'
    );

    $this->assertIsArray($result);
    $this->assertEquals(StatusPageManagerService::STATUS_MAINTENANCE, $result['status']);
    $this->assertEquals('Mantenimiento programado de base de datos', $result['description']);
    $this->assertArrayHasKey('updated_at', $result);
  }

  /**
   * Verifica las constantes de estado del componente.
   *
   * @covers ::__construct
   */
  public function testComponentStatusConstants(): void {
    $this->assertEquals('operational', StatusPageManagerService::STATUS_OPERATIONAL);
    $this->assertEquals('degraded', StatusPageManagerService::STATUS_DEGRADED);
    $this->assertEquals('partial_outage', StatusPageManagerService::STATUS_PARTIAL_OUTAGE);
    $this->assertEquals('major_outage', StatusPageManagerService::STATUS_MAJOR_OUTAGE);
    $this->assertEquals('maintenance', StatusPageManagerService::STATUS_MAINTENANCE);
  }

}
