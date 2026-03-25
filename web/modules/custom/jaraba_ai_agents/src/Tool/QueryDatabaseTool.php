<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para ejecutar consultas SELECT de solo lectura.
 *
 * SEGURIDAD (defense-in-depth, 6 capas):
 * 1. Strip SQL comments before validation.
 * 2. Whitelist: only SELECT allowed (regex after comment stripping).
 * 3. Blacklist: forbidden keywords (INSERT, DROP, INTO OUTFILE, SLEEP, etc.).
 * 4. Table blacklist: sensitive tables blocked (users, sessions, key_value).
 * 5. Forced LIMIT: max 100 rows to prevent memory exhaustion.
 * 6. REQUIERE APROBACION: human-in-the-loop via requiresApproval().
 */
class QueryDatabaseTool extends BaseTool {

  /**
   * Maximum rows returned per query.
   */
  protected const MAX_ROWS = 100;

  /**
   * Forbidden SQL keywords — any occurrence blocks the query.
   *
   * @var array<string>
   */
  protected const FORBIDDEN_KEYWORDS = [
        // DML mutations.
    'INSERT',
    'UPDATE',
    'DELETE',
    'REPLACE',
    'MERGE',
        // DDL.
    'DROP',
    'ALTER',
    'TRUNCATE',
    'CREATE',
    'RENAME',
        // DCL.
    'GRANT',
    'REVOKE',
        // Procedure / dynamic execution.
    'CALL',
    'EXEC',
    'EXECUTE',
    'PREPARE',
        // Data exfiltration.
    'INTO\s+OUTFILE',
    'INTO\s+DUMPFILE',
    'LOAD_FILE',
    'LOAD\s+DATA',
        // DoS vectors.
    'BENCHMARK',
    'SLEEP',
        // Information leakage.
    'INFORMATION_SCHEMA',
    'PERFORMANCE_SCHEMA',
    'MYSQL\.',
  ];

  /**
   * Sensitive tables that must never be queried.
   *
   * @var array<string>
   */
  protected const BLOCKED_TABLES = [
    'users',
    'users_field_data',
    'sessions',
    'key_value',
    'key_value_expire',
    'flood',
    'watchdog',
    'cache_',
    'semaphore',
    'batch',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected LoggerInterface $logger,
    protected Connection $database,
  ) {
    parent::__construct($logger);
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'query_database';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Consultar Base de Datos';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Ejecuta consultas SELECT de solo lectura con parametros seguros contra la base de datos. Maximo ' . self::MAX_ROWS . ' filas por consulta.';
  }

  /**
   * {@inheritdoc}
   */
  public function getParameters(): array {
    return [
      'query' => [
        'type' => 'string',
        'required' => TRUE,
        'description' => 'Consulta SQL SELECT a ejecutar (solo lectura, max ' . self::MAX_ROWS . ' filas).',
      ],
      'params' => [
        'type' => 'array',
        'required' => FALSE,
        'description' => 'Parametros para la consulta preparada (array asociativo).',
        'default' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function requiresApproval(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $params): array {
    $errors = parent::validate($params);

    if (!isset($params['query'])) {
      return $errors;
    }

    $query = trim($params['query']);

    // Layer 1: Strip SQL comments to prevent bypass via /* INSERT */.
    $cleaned = $this->stripSqlComments($query);

    // Layer 2: Whitelist — must start with SELECT.
    if (!preg_match('/^\s*SELECT\b/i', $cleaned)) {
      $errors[] = 'Only SELECT queries are allowed. Query must start with SELECT.';
    }

    // Layer 3: Blacklist — forbidden keywords after comment stripping.
    $upperCleaned = strtoupper($cleaned);
    foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
      $pattern = '/\b' . $keyword . '\b/';
      if (preg_match($pattern, $upperCleaned)) {
        $displayKeyword = str_replace('\\s+', ' ', $keyword);
        $errors[] = "Forbidden SQL keyword detected: {$displayKeyword}. Only read-only SELECT queries are permitted.";
      }
    }

    // Layer 3b: Reject semicolons to prevent multi-statement injection.
    if (str_contains($cleaned, ';')) {
      $errors[] = 'Multiple statements are not allowed. Remove semicolons from the query.';
    }

    // Layer 4: Table blacklist — prevent access to sensitive tables.
    $tableErrors = $this->validateTables($cleaned);
    if ($tableErrors !== []) {
      $errors = array_merge($errors, $tableErrors);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $params, array $context = []): array {
    $query = trim($params['query']);
    $queryParams = $params['params'] ?? [];

    // Layer 5: Force LIMIT to prevent memory exhaustion.
    $query = $this->enforceLimit($query);

    $this->log('Executing read-only query: @query', ['@query' => $query]);

    try {
      $statement = $this->database->query($query, $queryParams);
      $results = $statement->fetchAll(\PDO::FETCH_ASSOC);

      return $this->success([
        'query' => $query,
        'row_count' => count($results),
        'results' => $results,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->warning('[Tool:query_database] Query failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $this->error('Database query failed: ' . $e->getMessage());
    }
  }

  /**
   * Strips SQL comments to prevent validation bypass.
   *
   * Removes both block comments and line comments
   * so that hidden keywords inside comments are exposed.
   *
   * @param string $sql
   *   Raw SQL string.
   *
   * @return string
   *   SQL with comments removed.
   */
  protected function stripSqlComments(string $sql): string {
    // Remove block comments /* ... */.
    $sql = (string) preg_replace('/\/\*.*?\*\//s', ' ', $sql);
    // Remove line comments -- ... and # ...
    $sql = (string) preg_replace('/(--|#)[^\n]*/', ' ', $sql);
    return $sql;
  }

  /**
   * Validates that no sensitive tables are referenced.
   *
   * @param string $sql
   *   SQL with comments already stripped.
   *
   * @return array<string>
   *   Validation error messages, empty if valid.
   */
  protected function validateTables(string $sql): array {
    $errors = [];
    $lowerSql = strtolower($sql);

    foreach (self::BLOCKED_TABLES as $table) {
      // Match table name in common SQL positions:
      // FROM {table}, JOIN {table}, FROM table, JOIN table.
      $patterns = [
        '/\b' . preg_quote($table, '/') . '\b/',
        '/\{' . preg_quote($table, '/') . '[^}]*\}/',
      ];
      foreach ($patterns as $pattern) {
        if (preg_match($pattern, $lowerSql)) {
          $errors[] = "Access to table '{$table}' is blocked for security reasons.";
          break;
        }
      }
    }

    return $errors;
  }

  /**
   * Enforces a maximum LIMIT on the query.
   *
   * If the query already has a LIMIT clause, it is capped at MAX_ROWS.
   * If no LIMIT exists, one is appended.
   *
   * @param string $sql
   *   The SQL query.
   *
   * @return string
   *   The SQL query with LIMIT enforced.
   */
  protected function enforceLimit(string $sql): string {
    $maxRows = self::MAX_ROWS;

    // Check if query already has a LIMIT clause.
    if (preg_match('/\bLIMIT\s+(\d+)/i', $sql, $matches)) {
      $existingLimit = (int) $matches[1];
      if ($existingLimit > $maxRows) {
        // Cap the existing LIMIT.
        $sql = (string) preg_replace(
              '/\bLIMIT\s+\d+/i',
              "LIMIT {$maxRows}",
              $sql
          );
      }
      return $sql;
    }

    // Append LIMIT if none exists.
    return rtrim($sql) . " LIMIT {$maxRows}";
  }

}
