<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeBase;

/**
 * Plugin para escenarios ramificados con nodos de decision.
 *
 * Estructura: Define un arbol de decisiones con nodos conectados. Cada nodo
 * presenta informacion contextual y opciones de decision que llevan a
 * diferentes nodos hijos, creando multiples caminos posibles.
 *
 * Logica: El usuario navega por el arbol eligiendo opciones. Cada opcion
 * puede otorgar puntos segun su calidad. La puntuacion final es la suma
 * de puntos obtenidos dividida por el maximo posible del mejor camino.
 * Los nodos terminales (end_nodes) cierran la narrativa.
 *
 * Sintaxis: Plugin @InteractiveType con category "interactive".
 *
 * @InteractiveType(
 *   id = "branching_scenario",
 *   label = @Translation("Escenario Ramificado"),
 *   description = @Translation("Escenario de decisiones con múltiples caminos y puntuación por calidad de elecciones."),
 *   category = "interactive",
 *   icon = "education/git-branch",
 *   weight = 30
 * )
 */
class BranchingScenario extends InteractiveTypeBase
{

    /**
     * {@inheritdoc}
     *
     * Esquema del escenario ramificado.
     * Define nodos, opciones, conexiones y nodos terminales.
     */
    public function getSchema(): array
    {
        return [
            'start_node' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'ID del nodo inicial',
            ],
            'nodes' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'title' => ['type' => 'string', 'required' => TRUE],
                    'content' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string', 'required' => TRUE],
                            'image_url' => ['type' => 'string'],
                            'video_url' => ['type' => 'string'],
                        ],
                    ],
                    'options' => [
                        'type' => 'array',
                        'items' => [
                            'id' => ['type' => 'string', 'required' => TRUE],
                            'text' => ['type' => 'string', 'required' => TRUE],
                            'target_node' => ['type' => 'string', 'required' => TRUE],
                            'points' => ['type' => 'integer', 'default' => 0],
                            'feedback' => ['type' => 'string'],
                        ],
                    ],
                    'is_end' => ['type' => 'boolean', 'default' => FALSE],
                    'end_message' => ['type' => 'string'],
                    'end_type' => [
                        'type' => 'string',
                        'enum' => ['success', 'partial', 'failure'],
                        'default' => 'partial',
                    ],
                ],
            ],
            'settings' => [
                'type' => 'object',
                'properties' => [
                    'passing_score' => ['type' => 'integer', 'default' => 60],
                    'show_score_per_decision' => ['type' => 'boolean', 'default' => FALSE],
                    'allow_restart' => ['type' => 'boolean', 'default' => TRUE],
                    'show_path_visualization' => ['type' => 'boolean', 'default' => TRUE],
                    'max_optimal_score' => ['type' => 'integer', 'default' => 100],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Valida la estructura del escenario ramificado.
     * Verifica que el nodo inicial exista y que haya al menos un nodo terminal.
     */
    public function validate(array $data): array
    {
        $errors = parent::validate($data);

        $nodes = $data['nodes'] ?? [];
        if (empty($nodes)) {
            $errors['nodes'] = $this->t('Se requiere al menos un nodo.');
            return $errors;
        }

        // Verificar que el nodo inicial existe.
        $nodeIds = array_column($nodes, 'id');
        if (!empty($data['start_node']) && !in_array($data['start_node'], $nodeIds, TRUE)) {
            $errors['start_node'] = $this->t('El nodo inicial no existe en la lista de nodos.');
        }

        // Verificar que hay al menos un nodo terminal.
        $hasEndNode = FALSE;
        foreach ($nodes as $node) {
            if (!empty($node['is_end'])) {
                $hasEndNode = TRUE;
                break;
            }
        }
        if (!$hasEndNode) {
            $errors['nodes.end'] = $this->t('Se requiere al menos un nodo terminal.');
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     *
     * Renderiza el escenario ramificado.
     */
    public function render(array $data, array $settings = []): array
    {
        return [
            '#theme' => 'interactive_branching_scenario',
            '#start_node' => $data['start_node'] ?? '',
            '#nodes' => $data['nodes'] ?? [],
            '#settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Calcula la puntuacion basada en las decisiones tomadas.
     * Las respuestas contienen el camino seguido: array de decision IDs.
     */
    public function calculateScore(array $data, array $responses): array
    {
        $nodes = $data['nodes'] ?? [];
        $nodesById = [];
        foreach ($nodes as $node) {
            $nodesById[$node['id']] = $node;
        }

        $earnedPoints = 0;
        $details = [];
        $path = $responses['path'] ?? [];

        // Recorrer el camino tomado y sumar puntos.
        foreach ($path as $decision) {
            $nodeId = $decision['node_id'] ?? '';
            $optionId = $decision['option_id'] ?? '';

            if (!isset($nodesById[$nodeId])) {
                continue;
            }

            $node = $nodesById[$nodeId];
            $options = $node['options'] ?? [];

            foreach ($options as $option) {
                if ($option['id'] === $optionId) {
                    $points = $option['points'] ?? 0;
                    $earnedPoints += $points;

                    $details[$nodeId] = [
                        'option_chosen' => $optionId,
                        'points_earned' => $points,
                        'feedback' => $option['feedback'] ?? '',
                    ];
                    break;
                }
            }
        }

        // Calcular el maximo posible del mejor camino.
        $maxOptimalScore = (float) ($data['settings']['max_optimal_score'] ?? 100);
        $percentage = $maxOptimalScore > 0
            ? round(($earnedPoints / $maxOptimalScore) * 100, 2)
            : 0.0;

        // Limitar al 100%.
        $percentage = min($percentage, 100.0);
        $passingScore = (float) ($data['settings']['passing_score'] ?? 60);

        return [
            'score' => $percentage,
            'max_score' => 100,
            'passed' => $this->determinePassed($percentage, $passingScore),
            'raw_score' => $earnedPoints,
            'raw_max' => (int) $maxOptimalScore,
            'details' => $details,
            'path_length' => count($path),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Verbos xAPI para escenario: incluye interacted por decision.
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'interacted', 'completed', 'passed', 'failed'];
    }

}
