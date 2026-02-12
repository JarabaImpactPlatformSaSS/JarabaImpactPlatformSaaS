<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interfaz para plugins de tipo interactivo.
 */
interface InteractiveTypeInterface extends PluginInspectionInterface
{

    /**
     * Obtiene el label del tipo.
     *
     * @return string
     *   El label traducido.
     */
    public function getLabel(): string;

    /**
     * Obtiene la descripción del tipo.
     *
     * @return string
     *   La descripción traducida.
     */
    public function getDescription(): string;

    /**
     * Obtiene el esquema JSON del contenido.
     *
     * Define la estructura de datos que almacena este tipo.
     *
     * @return array
     *   El esquema como array asociativo.
     */
    public function getSchema(): array;

    /**
     * Valida los datos del contenido.
     *
     * @param array $data
     *   Los datos a validar.
     *
     * @return array
     *   Array de errores (vacío si es válido).
     */
    public function validate(array $data): array;

    /**
     * Renderiza el contenido para el player.
     *
     * @param array $data
     *   Los datos del contenido.
     * @param array $settings
     *   La configuración del contenido.
     *
     * @return array
     *   El render array.
     */
    public function render(array $data, array $settings = []): array;

    /**
     * Calcula la puntuación de las respuestas.
     *
     * @param array $data
     *   Los datos del contenido.
     * @param array $responses
     *   Las respuestas del usuario.
     *
     * @return array
     *   Array con 'score', 'max_score', 'passed', 'details'.
     */
    public function calculateScore(array $data, array $responses): array;

    /**
     * Obtiene los verbos xAPI relevantes para este tipo.
     *
     * @return array
     *   Array de verbos xAPI (ej: ['attempted', 'completed', 'passed']).
     */
    public function getXapiVerbs(): array;

    /**
     * Obtiene el icono del tipo.
     *
     * @return array
     *   Array ['category' => 'ui', 'name' => 'question'].
     */
    public function getIcon(): array;

}
