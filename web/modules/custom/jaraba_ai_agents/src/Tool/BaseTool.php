<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Psr\Log\LoggerInterface;

/**
 * Clase base abstracta para herramientas IA.
 *
 * Proporciona implementación común para reducir boilerplate
 * en herramientas concretas.
 */
abstract class BaseTool implements ToolInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function requiresApproval(): bool
    {
        // Por defecto, no requiere aprobación.
        return FALSE;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $params): array
    {
        $errors = [];
        $definitions = $this->getParameters();

        foreach ($definitions as $name => $config) {
            $required = $config['required'] ?? FALSE;
            $type = $config['type'] ?? 'string';

            if ($required && !isset($params[$name])) {
                $errors[] = "Parameter '{$name}' is required.";
                continue;
            }

            if (isset($params[$name])) {
                $value = $params[$name];
                $valid = match ($type) {
                    'string' => is_string($value),
                    'int', 'integer' => is_int($value) || is_numeric($value),
                    'bool', 'boolean' => is_bool($value),
                    'array' => is_array($value),
                    default => TRUE,
                };

                if (!$valid) {
                    $errors[] = "Parameter '{$name}' must be of type {$type}.";
                }
            }
        }

        return $errors;
    }

    /**
     * Helper para logging de ejecución.
     *
     * @param string $message
     *   Mensaje a loguear.
     * @param array $context
     *   Contexto del mensaje.
     */
    protected function log(string $message, array $context = []): void
    {
        $this->logger->info("[Tool:{$this->getId()}] {$message}", $context);
    }

    /**
     * Helper para retornar éxito.
     *
     * @param array $data
     *   Datos de salida.
     *
     * @return array
     *   Resultado exitoso.
     */
    protected function success(array $data = []): array
    {
        return [
            'success' => TRUE,
            'data' => $data,
        ];
    }

    /**
     * Helper para retornar error.
     *
     * @param string $message
     *   Mensaje de error.
     *
     * @return array
     *   Resultado fallido.
     */
    protected function error(string $message): array
    {
        return [
            'success' => FALSE,
            'error' => $message,
        ];
    }

}
