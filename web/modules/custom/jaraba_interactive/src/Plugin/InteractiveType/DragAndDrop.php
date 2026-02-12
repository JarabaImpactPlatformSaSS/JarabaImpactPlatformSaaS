<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeBase;

/**
 * Plugin para ejercicios de arrastrar y soltar.
 *
 * Estructura: Define zonas de destino (drop zones) e items arrastrables
 * (draggables). Cada item tiene una o varias zonas correctas donde debe
 * ser colocado. Soporta matching 1:1 y 1:N.
 *
 * Logica: El usuario arrastra items a zonas. La puntuacion se calcula
 * verificando si cada item fue colocado en su zona correcta. Los items
 * no colocados cuentan como incorrectos.
 *
 * Sintaxis: Plugin @InteractiveType con category "assessment".
 *
 * @InteractiveType(
 *   id = "drag_and_drop",
 *   label = @Translation("Arrastrar y Soltar"),
 *   description = @Translation("Ejercicios de emparejamiento arrastrando elementos a zonas de destino."),
 *   category = "assessment",
 *   icon = "ui/move",
 *   weight = 40
 * )
 */
class DragAndDrop extends InteractiveTypeBase
{

    /**
     * {@inheritdoc}
     *
     * Esquema del contenido drag-and-drop.
     * Define zonas de destino, items arrastrables y reglas de matching.
     */
    public function getSchema(): array
    {
        return [
            'background_image' => [
                'type' => 'string',
                'description' => 'URL de imagen de fondo opcional',
            ],
            'drop_zones' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'label' => ['type' => 'string', 'required' => TRUE],
                    'position' => [
                        'type' => 'object',
                        'properties' => [
                            'x' => ['type' => 'number'],
                            'y' => ['type' => 'number'],
                            'width' => ['type' => 'number'],
                            'height' => ['type' => 'number'],
                        ],
                    ],
                    'accepts_multiple' => ['type' => 'boolean', 'default' => FALSE],
                    'hint' => ['type' => 'string'],
                ],
            ],
            'draggables' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'text' => ['type' => 'string'],
                    'image_url' => ['type' => 'string'],
                    'correct_zones' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                        'required' => TRUE,
                    ],
                    'feedback_correct' => ['type' => 'string'],
                    'feedback_incorrect' => ['type' => 'string'],
                ],
            ],
            'settings' => [
                'type' => 'object',
                'properties' => [
                    'passing_score' => ['type' => 'integer', 'default' => 70],
                    'max_attempts' => ['type' => 'integer', 'default' => 3],
                    'show_feedback' => [
                        'type' => 'string',
                        'enum' => ['immediate', 'end', 'never'],
                        'default' => 'end',
                    ],
                    'randomize_draggables' => ['type' => 'boolean', 'default' => TRUE],
                    'snap_to_zone' => ['type' => 'boolean', 'default' => TRUE],
                    'highlight_zones' => ['type' => 'boolean', 'default' => TRUE],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Valida que existan zonas y items arrastrables.
     */
    public function validate(array $data): array
    {
        $errors = parent::validate($data);

        if (empty($data['drop_zones'])) {
            $errors['drop_zones'] = $this->t('Se requiere al menos una zona de destino.');
        }

        if (empty($data['draggables'])) {
            $errors['draggables'] = $this->t('Se requiere al menos un elemento arrastrable.');
        }

        // Verificar que las zonas correctas existan.
        $zoneIds = array_column($data['drop_zones'] ?? [], 'id');
        foreach ($data['draggables'] ?? [] as $index => $item) {
            foreach ($item['correct_zones'] ?? [] as $zoneId) {
                if (!in_array($zoneId, $zoneIds, TRUE)) {
                    $errors["draggables.$index.correct_zones"] = $this->t(
                        'El item "@text" referencia una zona inexistente: @zone.',
                        ['@text' => $item['text'] ?? $index, '@zone' => $zoneId]
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     *
     * Renderiza el ejercicio de arrastrar y soltar.
     */
    public function render(array $data, array $settings = []): array
    {
        return [
            '#theme' => 'interactive_drag_and_drop',
            '#drop_zones' => $data['drop_zones'] ?? [],
            '#draggables' => $data['draggables'] ?? [],
            '#background_image' => $data['background_image'] ?? '',
            '#settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Calcula la puntuacion verificando colocacion de items en zonas.
     * Las respuestas son un mapa: {draggable_id: zone_id}.
     */
    public function calculateScore(array $data, array $responses): array
    {
        $draggables = $data['draggables'] ?? [];
        $totalItems = count($draggables);
        $correctItems = 0;
        $details = [];

        foreach ($draggables as $item) {
            $itemId = $item['id'];
            $correctZones = $item['correct_zones'] ?? [];
            $userZone = $responses[$itemId] ?? NULL;

            $isCorrect = $userZone !== NULL && in_array($userZone, $correctZones, TRUE);

            if ($isCorrect) {
                $correctItems++;
            }

            $details[$itemId] = [
                'correct' => $isCorrect,
                'user_zone' => $userZone,
                'correct_zones' => $correctZones,
                'feedback' => $isCorrect
                    ? ($item['feedback_correct'] ?? (string) $this->t('¡Correcto!'))
                    : ($item['feedback_incorrect'] ?? (string) $this->t('Incorrecto.')),
            ];
        }

        $percentage = $this->calculatePercentage((float) $correctItems, (float) $totalItems);
        $passingScore = (float) ($data['settings']['passing_score'] ?? 70);

        return [
            'score' => $percentage,
            'max_score' => 100,
            'passed' => $this->determinePassed($percentage, $passingScore),
            'raw_score' => $correctItems,
            'raw_max' => $totalItems,
            'details' => $details,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Verbos xAPI para drag-and-drop.
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'interacted', 'completed', 'passed', 'failed'];
    }

}
