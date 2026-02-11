<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

/**
 * Interfaz para Agentes de IA.
 *
 * PROPÓSITO:
 * Define el contrato que todos los agentes IA deben implementar.
 * Asegura una API consistente para el AgentOrchestrator y la UI.
 *
 * MÉTODOS REQUERIDOS:
 * - execute(): Ejecutar una acción con contexto
 * - getAvailableActions(): Listar acciones disponibles
 * - getAgentId(): Identificador único
 * - setTenantContext(): Configurar contexto multi-tenant
 * - getLabel(): Nombre para mostrar
 * - getDescription(): Descripción de capacidades
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
interface AgentInterface
{

    /**
     * Ejecuta una acción con el contexto dado.
     *
     * @param string $action
     *   La acción a ejecutar (ej.: 'social_post', 'email_promo').
     * @param array $context
     *   Datos de contexto para la ejecución de la acción.
     *
     * @return array
     *   Array de resultado con claves:
     *   - success: bool - Si la ejecución fue exitosa.
     *   - data: array (en éxito) - Datos generados.
     *   - error: string (en fallo) - Mensaje de error.
     */
    public function execute(string $action, array $context): array;

    /**
     * Retorna la lista de acciones disponibles para este agente.
     *
     * @return array
     *   Array de acciones con sus metadatos:
     *   - label: string - Nombre para mostrar.
     *   - description: string - Descripción de la acción.
     *   - requires: array - Campos de contexto requeridos.
     *   - optional: array - Campos opcionales.
     *   - tier: string - Tier de modelo recomendado (fast/balanced/premium).
     */
    public function getAvailableActions(): array;

    /**
     * Retorna el identificador único para este agente.
     *
     * @return string
     *   El ID del agente (ej.: 'marketing_multi', 'content_writer').
     */
    public function getAgentId(): string;

    /**
     * Establece el contexto de tenant para operaciones multi-tenant.
     *
     * Debe llamarse antes de execute() para asegurar que el agente
     * use la configuración correcta de Brand Voice y vertical.
     *
     * @param string $tenantId
     *   El ID del tenant/grupo.
     * @param string $vertical
     *   El vertical del negocio (empleo, emprendimiento, comercio, etc.).
     */
    public function setTenantContext(string $tenantId, string $vertical): void;

    /**
     * Retorna el nombre de presentación del agente.
     *
     * @return string
     *   Nombre legible para mostrar en UI.
     */
    public function getLabel(): string;

    /**
     * Retorna la descripción del agente.
     *
     * @return string
     *   Descripción de las capacidades del agente.
     */
    public function getDescription(): string;

}
