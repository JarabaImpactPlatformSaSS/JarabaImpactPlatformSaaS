<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_governance\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\StatementInterface;
use Drupal\jaraba_governance\Service\DataMaskingService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the DataMaskingService.
 *
 * @coversDefaultClass \Drupal\jaraba_governance\Service\DataMaskingService
 * @group jaraba_governance
 */
class DataMaskingServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected DataMaskingService $service;

  /**
   * Mocked database connection.
   */
  protected Connection $database;

  /**
   * Mocked config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mocked logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DataMaskingService(
      $this->database,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Tests generateFakeValue with template-based rule containing {id}.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithTemplate(): void {
    $result = $this->service->generateFakeValue('user{id}@test.jaraba.dev', 42);

    $this->assertSame('user42@test.jaraba.dev', $result);
  }

  /**
   * Tests generateFakeValue with faker_name rule.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithFakerName(): void {
    $result = $this->service->generateFakeValue('faker_name', 1);

    // Should return a Spanish-style name: "FirstName Surname Surname".
    $this->assertNotEmpty($result);
    $parts = explode(' ', $result);
    $this->assertCount(3, $parts, 'Fake name should have 3 parts (name + 2 surnames).');
  }

  /**
   * Tests generateFakeValue with faker_nif rule produces valid NIF format.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithFakerNif(): void {
    $result = $this->service->generateFakeValue('faker_nif', 5);

    // NIF: 8 digits + 1 letter.
    $this->assertMatchesRegularExpression('/^\d{8}[A-Z]$/', $result);
  }

  /**
   * Tests generateFakeValue with faker_iban rule produces IBAN format.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithFakerIban(): void {
    $result = $this->service->generateFakeValue('faker_iban', 10);

    // Should start with 'ES00'.
    $this->assertStringStartsWith('ES00', $result);
  }

  /**
   * Tests generateFakeValue with faker_phone rule.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithFakerPhone(): void {
    $result = $this->service->generateFakeValue('faker_phone', 3);

    // Spanish mobile: 6XX + 6 digits = 9 digits total.
    $this->assertMatchesRegularExpression('/^6\d{8}$/', $result);
  }

  /**
   * Tests generateFakeValue with faker_address rule.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithFakerAddress(): void {
    $result = $this->service->generateFakeValue('faker_address', 7);

    // Should contain a street name and a city.
    $this->assertNotEmpty($result);
    $this->assertStringContainsString(',', $result, 'Address should contain a comma separating street and city.');
  }

  /**
   * Tests generateFakeValue with unknown rule returns default masked value.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueWithUnknownRuleFallsBackToDefault(): void {
    $result = $this->service->generateFakeValue('unknown_rule', 99);

    $this->assertSame('masked_99', $result);
  }

  /**
   * Tests generateFakeValue is deterministic for same id.
   *
   * @covers ::generateFakeValue
   */
  public function testGenerateFakeValueIsDeterministic(): void {
    $result1 = $this->service->generateFakeValue('faker_name', 42);
    $result2 = $this->service->generateFakeValue('faker_name', 42);

    $this->assertSame($result1, $result2, 'Same ID should produce same fake value (deterministic).');
  }

  /**
   * Tests maskField returns 0 when table does not exist.
   *
   * @covers ::maskField
   */
  public function testMaskFieldReturnsZeroForNonexistentTable(): void {
    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')
      ->with('nonexistent_table')
      ->willReturn(FALSE);

    $this->database->method('schema')->willReturn($schema);

    $result = $this->service->maskField('nonexistent_table', 'column', 'user{id}@test.dev');

    $this->assertSame(0, $result);
  }

  /**
   * Tests maskField processes rows and returns count.
   *
   * @covers ::maskField
   */
  public function testMaskFieldProcessesRowsSuccessfully(): void {
    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    // Mock select query that returns 3 rows.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchCol')->willReturn([1, 2, 3]);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('users_field_data', 't')
      ->willReturn($selectQuery);

    // Mock update query.
    $updateQuery = $this->createMock(Update::class);
    $updateQuery->method('fields')->willReturnSelf();
    $updateQuery->method('condition')->willReturnSelf();
    $updateQuery->method('execute')->willReturn(1);

    $this->database->method('update')
      ->with('users_field_data')
      ->willReturn($updateQuery);

    $result = $this->service->maskField('users_field_data', 'mail', 'user{id}@test.dev', 'uid');

    $this->assertSame(3, $result);
  }

  /**
   * Tests maskField returns 0 and logs error on database exception.
   *
   * @covers ::maskField
   */
  public function testMaskFieldReturnsZeroOnException(): void {
    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    $this->database->method('select')
      ->willThrowException(new \Exception('DB connection lost'));

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Masking failed'),
        $this->anything()
      );

    $result = $this->service->maskField('users_field_data', 'mail', 'user{id}@test.dev', 'uid');

    $this->assertSame(0, $result);
  }

  /**
   * Tests maskDatabase with configured rules.
   *
   * @covers ::maskDatabase
   */
  public function testMaskDatabaseProcessesConfiguredRules(): void {
    // Configure masking rules.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->with('masking_rules')
      ->willReturn([
        'email' => 'user{id}@test.jaraba.dev',
        'nombre' => 'faker_name',
      ]);

    $this->configFactory->method('get')
      ->with('jaraba_governance.settings')
      ->willReturn($config);

    // Mock schema check -- all tables exist.
    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    // Select returns 2 rows each time.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchCol')->willReturn([1, 2]);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);
    $this->database->method('select')->willReturn($selectQuery);

    $updateQuery = $this->createMock(Update::class);
    $updateQuery->method('fields')->willReturnSelf();
    $updateQuery->method('condition')->willReturnSelf();
    $updateQuery->method('execute')->willReturn(1);
    $this->database->method('update')->willReturn($updateQuery);

    $stats = $this->service->maskDatabase();

    $this->assertArrayHasKey('email', $stats);
    $this->assertArrayHasKey('nombre', $stats);
    $this->assertSame('user{id}@test.jaraba.dev', $stats['email']['rule']);
    // Email has 2 field mappings (mail, init), each with 2 rows = 4 total.
    $this->assertSame(4, $stats['email']['masked_count']);
    // Nombre has 1 field mapping (name), with 2 rows.
    $this->assertSame(2, $stats['nombre']['masked_count']);
  }

}
