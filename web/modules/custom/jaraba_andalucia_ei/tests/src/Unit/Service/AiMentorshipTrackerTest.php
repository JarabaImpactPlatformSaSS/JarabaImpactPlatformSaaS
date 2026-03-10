<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\AiMentorshipTracker;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AiMentorshipTracker.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AiMentorshipTracker
 * @group jaraba_andalucia_ei
 */
class AiMentorshipTrackerTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AiMentorshipTracker $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock database connection.
   */
  protected Connection $database;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock time service.
   */
  protected TimeInterface $time;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);

    $this->time->method('getRequestTime')
      ->willReturn(1709000000);

    $this->service = new AiMentorshipTracker(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
      $this->configFactory,
      $this->time,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionCorrecta(): void {
    $this->assertInstanceOf(AiMentorshipTracker::class, $this->service);
  }

  /**
   * @covers ::getResumenHorasIa
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenHorasIaDevuelveArrayConClavesEsperadas(): void {
    // Mock the select query chain.
    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('addExpression')->willReturn('alias');

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn([
      'total_horas' => '5.50',
      'sesiones_count' => '3',
      'ultima_sesion' => '1709000000',
    ]);

    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('jaraba_andalucia_ei_ia_audit', 'a')
      ->willReturn($selectQuery);

    $result = $this->service->getResumenHorasIa(1);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('total_horas', $result);
    $this->assertArrayHasKey('sesiones_count', $result);
    $this->assertArrayHasKey('ultima_sesion', $result);
    $this->assertEquals(5.50, $result['total_horas']);
    $this->assertEquals(3, $result['sesiones_count']);
    $this->assertEquals(1709000000, $result['ultima_sesion']);
  }

  /**
   * @covers ::getResumenHorasIa
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getResumenHorasIaSinDatosDevuelveCeros(): void {
    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('addExpression')->willReturn('alias');

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn([
      'total_horas' => NULL,
      'sesiones_count' => '0',
      'ultima_sesion' => NULL,
    ]);

    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->willReturn($selectQuery);

    $result = $this->service->getResumenHorasIa(999);

    $this->assertIsArray($result);
    $this->assertEquals(0.0, $result['total_horas']);
    $this->assertEquals(0, $result['sesiones_count']);
    $this->assertNull($result['ultima_sesion']);
  }

  /**
   * @covers ::registrarSesionIa
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function registrarSesionIaParticipanteNoEncontrado(): void {
    $this->storage->method('load')->willReturn(NULL);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $this->configFactory->method('get')->willReturn($config);

    $result = $this->service->registrarSesionIa(999, 30.0);

    $this->assertFalse($result);
  }

}
