<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

/**
 * Interface para herramientas ejecutables por agentes IA.
 *
 * Las herramientas son acciones concretas que los agentes pueden invocar
 * durante la ejecución de workflows. Cada herramienta encapsula una
 * operación específica (enviar email, crear entidad, buscar, etc.).
 *
 * ARQUITECTURA:
 * - Las herramientas se registran en ToolRegistry
 * - WorkflowExecutorService las invoca durante executeStep()
 * - Cada herramienta valida sus inputs y retorna resultados estructurados
 */
interface ToolInterface
{

    /**
     * Retorna el ID único de la herramienta.
     *
     * @return string
     *   ID en formato snake_case (ej: 'send_email', 'create_entity').
     */
    public function getId(): string;

    /**
     * Retorna el nombre legible de la herramienta.
     *
     * @return string
     *   Nombre para mostrar en UI.
     */
    public function getLabel(): string;

    /**
     * Retorna la descripción de la herramienta.
     *
     * @return string
     *   Descripción para documentación y ayuda contextual.
     */
    public function getDescription(): string;

    /**
     * Define los parámetros requeridos por la herramienta.
     *
     * @return array
     *   Array de definiciones de parámetros:
     *   [
     *     'param_name' => [
     *       'type' => 'string|int|array|bool',
     *       'required' => true|false,
     *       'description' => 'Descripción del parámetro',
     *       'default' => valor_por_defecto,
     *     ],
     *   ]
     */
    public function getParameters(): array;

    /**
     * Indica si la herramienta requiere aprobación para ejecutarse.
     *
     * Herramientas con efectos irreversibles (enviar email, eliminar,
     * publicar) deben retornar TRUE.
     *
     * @return bool
     *   TRUE si requiere aprobación explícita.
     */
    public function requiresApproval(): bool;

    /**
     * Valida los parámetros de entrada.
     *
     * @param array $params
     *   Parámetros a validar.
     *
     * @return array
     *   Array de errores de validación. Vacío si válido.
     */
    public function validate(array $params): array;

    /**
     * Ejecuta la herramienta.
     *
     * @param array $params
     *   Parámetros de entrada validados.
     * @param array $context
     *   Contexto de ejecución (tenant_id, user, workflow_id, etc.).
     *
     * @return array
     *   Resultado de la ejecución:
     *   [
     *     'success' => bool,
     *     'data' => array (datos de salida),
     *     'error' => string|null (mensaje de error si falla),
     *   ]
     */
    public function execute(array $params, array $context = []): array;

}
