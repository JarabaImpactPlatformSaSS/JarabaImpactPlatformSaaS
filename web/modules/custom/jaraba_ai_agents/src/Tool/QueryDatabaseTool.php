<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para ejecutar consultas SELECT de solo lectura.
 *
 * SEGURIDAD:
 * - Solo permite sentencias SELECT.
 * - Rechaza INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, GRANT, REVOKE.
 * - Usa consultas parametrizadas para prevenir SQL injection.
 * - REQUIERE APROBACION: Si, porque accede directamente a la base de datos.
 */
class QueryDatabaseTool extends BaseTool
{

    /**
     * Sentencias SQL prohibidas (todo excepto SELECT).
     *
     * @var array<string>
     */
    protected const FORBIDDEN_STATEMENTS = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'DROP',
        'ALTER',
        'TRUNCATE',
        'CREATE',
        'GRANT',
        'REVOKE',
        'REPLACE',
        'MERGE',
        'CALL',
        'EXEC',
        'EXECUTE',
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
    public function getId(): string
    {
        return 'query_database';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Consultar Base de Datos';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Ejecuta consultas SELECT de solo lectura con parametros seguros contra la base de datos.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            'query' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Consulta SQL SELECT a ejecutar.',
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
    public function requiresApproval(): bool
    {
        return TRUE;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $params): array
    {
        $errors = parent::validate($params);

        if (isset($params['query'])) {
            $query = trim($params['query']);

            // Validate the query starts with SELECT.
            if (!preg_match('/^\s*SELECT\b/i', $query)) {
                $errors[] = "Only SELECT queries are allowed. Query must start with SELECT.";
            }

            // Check for forbidden statements anywhere in the query.
            $upperQuery = strtoupper($query);
            foreach (self::FORBIDDEN_STATEMENTS as $statement) {
                if (preg_match('/\b' . $statement . '\b/', $upperQuery)) {
                    $errors[] = "Forbidden SQL statement detected: {$statement}. Only read-only SELECT queries are permitted.";
                }
            }

            // Reject semicolons to prevent multi-statement injection.
            if (str_contains($query, ';')) {
                $errors[] = "Multiple statements are not allowed. Remove semicolons from the query.";
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params, array $context = []): array
    {
        $query = trim($params['query']);
        $queryParams = $params['params'] ?? [];

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
            return $this->error('Database query failed: ' . $e->getMessage());
        }
    }

}
