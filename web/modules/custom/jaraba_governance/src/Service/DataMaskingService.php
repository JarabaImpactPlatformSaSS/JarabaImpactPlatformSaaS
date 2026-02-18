<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Service for data masking in dev/staging environments.
 *
 * Applies configurable masking rules to PII fields across the database.
 * Supports email templating, faker-based name, NIF, IBAN, phone, and address
 * generation. Designed to run on database copies, NOT production.
 */
class DataMaskingService {

  /**
   * Constructs a DataMaskingService.
   */
  public function __construct(
    protected readonly Connection $database,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Applies masking rules to all PII fields in the database.
   *
   * WARNING: This operation is destructive and should ONLY be run
   * on staging/dev database copies, never on production.
   *
   * @return array
   *   Stats with masked field counts per rule type.
   */
  public function maskDatabase(): array {
    $config = $this->configFactory->get('jaraba_governance.settings');
    $rules = $config->get('masking_rules') ?? [];
    $stats = [];

    // Map masking rules to database tables/columns.
    $fieldMappings = [
      'email' => [
        ['table' => 'users_field_data', 'column' => 'mail', 'id_column' => 'uid'],
        ['table' => 'users_field_data', 'column' => 'init', 'id_column' => 'uid'],
      ],
      'nombre' => [
        ['table' => 'users_field_data', 'column' => 'name', 'id_column' => 'uid'],
      ],
    ];

    foreach ($rules as $ruleKey => $ruleValue) {
      $count = 0;
      $mappings = $fieldMappings[$ruleKey] ?? [];

      foreach ($mappings as $mapping) {
        $count += $this->maskField($mapping['table'], $mapping['column'], $ruleValue, $mapping['id_column']);
      }

      $stats[$ruleKey] = [
        'rule' => $ruleValue,
        'masked_count' => $count,
      ];

      $this->logger->info('Data masking: @key applied @rule to @count records.', [
        '@key' => $ruleKey,
        '@rule' => $ruleValue,
        '@count' => $count,
      ]);
    }

    return $stats;
  }

  /**
   * Masks a single field across all rows in a table.
   *
   * @param string $table
   *   Database table name.
   * @param string $column
   *   Column name to mask.
   * @param string $rule
   *   Masking rule pattern.
   * @param string $idColumn
   *   The ID column name for value generation.
   *
   * @return int
   *   Number of records masked.
   */
  public function maskField(string $table, string $column, string $rule, string $idColumn = 'id'): int {
    if (!$this->database->schema()->tableExists($table)) {
      return 0;
    }

    try {
      $rows = $this->database->select($table, 't')
        ->fields('t', [$idColumn])
        ->execute()
        ->fetchCol();

      $count = 0;
      foreach ($rows as $id) {
        $maskedValue = $this->generateFakeValue($rule, (int) $id);
        $this->database->update($table)
          ->fields([$column => $maskedValue])
          ->condition($idColumn, $id)
          ->execute();
        $count++;
      }

      return $count;
    }
    catch (\Exception $e) {
      $this->logger->error('Masking failed for @table.@column: @msg', [
        '@table' => $table,
        '@column' => $column,
        '@msg' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Generates a fake/masked value for a given rule type.
   *
   * @param string $rule
   *   The masking rule. Can be a template (e.g., 'user{id}@test.jaraba.dev')
   *   or a faker type (e.g., 'faker_name', 'faker_nif').
   * @param int $id
   *   Entity ID used for deterministic generation.
   *
   * @return string
   *   The generated fake value.
   */
  public function generateFakeValue(string $rule, int $id): string {
    // Template-based rules (e.g., 'user{id}@test.jaraba.dev').
    if (str_contains($rule, '{id}')) {
      return str_replace('{id}', (string) $id, $rule);
    }

    // Faker-based rules.
    $seed = $id * 7919; // Deterministic prime-based seed.
    return match ($rule) {
      'faker_name' => $this->fakeName($seed),
      'faker_nif' => $this->fakeNif($seed),
      'faker_iban' => $this->fakeIban($seed),
      'faker_phone' => $this->fakePhone($seed),
      'faker_address' => $this->fakeAddress($seed),
      default => 'masked_' . $id,
    };
  }

  /**
   * Generates a fake Spanish-style name.
   */
  protected function fakeName(int $seed): string {
    $names = ['Ana', 'Carlos', 'Maria', 'Jose', 'Laura', 'Pedro', 'Carmen', 'Miguel', 'Sofia', 'Antonio'];
    $surnames = ['Garcia', 'Martinez', 'Lopez', 'Sanchez', 'Gonzalez', 'Rodriguez', 'Fernandez', 'Perez', 'Ruiz', 'Diaz'];
    return $names[$seed % count($names)] . ' ' . $surnames[($seed >> 4) % count($surnames)] . ' ' . $surnames[($seed >> 8) % count($surnames)];
  }

  /**
   * Generates a fake NIF (Spanish tax ID).
   */
  protected function fakeNif(int $seed): string {
    $number = str_pad((string) (abs($seed) % 99999999), 8, '0', STR_PAD_LEFT);
    $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
    $letter = $letters[(int) $number % 23];
    return $number . $letter;
  }

  /**
   * Generates a fake IBAN.
   */
  protected function fakeIban(int $seed): string {
    $bank = str_pad((string) (abs($seed) % 9999), 4, '0', STR_PAD_LEFT);
    $branch = str_pad((string) (abs($seed >> 4) % 9999), 4, '0', STR_PAD_LEFT);
    $account = str_pad((string) (abs($seed >> 8) % 9999999999), 10, '0', STR_PAD_LEFT);
    return 'ES00 ' . $bank . ' ' . $branch . ' 00 ' . $account;
  }

  /**
   * Generates a fake phone number.
   */
  protected function fakePhone(int $seed): string {
    $prefix = ['600', '610', '620', '630', '640', '650', '660', '670', '680', '690'];
    $number = str_pad((string) (abs($seed) % 999999), 6, '0', STR_PAD_LEFT);
    return $prefix[$seed % count($prefix)] . $number;
  }

  /**
   * Generates a fake address.
   */
  protected function fakeAddress(int $seed): string {
    $streets = ['Calle Mayor', 'Avenida de la Constitucion', 'Calle Real', 'Paseo del Prado', 'Calle San Juan', 'Avenida de Andalucia', 'Calle Nueva', 'Plaza de Espana'];
    $cities = ['Madrid', 'Barcelona', 'Sevilla', 'Valencia', 'Malaga', 'Jaen', 'Cordoba', 'Granada'];
    $number = abs($seed) % 200 + 1;
    return $streets[$seed % count($streets)] . ' ' . $number . ', ' . $cities[($seed >> 4) % count($cities)];
  }

}
