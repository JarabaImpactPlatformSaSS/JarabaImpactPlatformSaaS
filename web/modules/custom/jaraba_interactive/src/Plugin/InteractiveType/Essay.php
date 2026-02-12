<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeBase;

/**
 * Plugin para ensayos y respuestas largas con evaluacion IA.
 *
 * Estructura: Define un prompt de escritura con rubrica de evaluacion,
 * limites de longitud y criterios de calificacion. Soporta evaluacion
 * automatica por IA y evaluacion manual por instructor.
 *
 * Logica: El usuario escribe un ensayo siguiendo el prompt. La
 * evaluacion puede ser automatica (via IA usando la rubrica definida)
 * o manual. La puntuacion se basa en los criterios de la rubrica,
 * cada uno con peso configurable.
 *
 * Sintaxis: Plugin @InteractiveType con category "assessment".
 *
 * @InteractiveType(
 *   id = "essay",
 *   label = @Translation("Ensayo"),
 *   description = @Translation("Ensayo con prompt, rúbrica de evaluación, límites de extensión y evaluación IA."),
 *   category = "assessment",
 *   icon = "education/pencil-line",
 *   weight = 50
 * )
 */
class Essay extends InteractiveTypeBase
{

    /**
     * {@inheritdoc}
     *
     * Esquema del contenido de ensayo.
     * Define prompt, rubrica de evaluacion y limites.
     */
    public function getSchema(): array
    {
        return [
            'prompt' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Consigna o tema del ensayo',
            ],
            'instructions' => [
                'type' => 'string',
                'description' => 'Instrucciones detalladas para el estudiante',
            ],
            'rubric' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'criterion' => ['type' => 'string', 'required' => TRUE],
                    'description' => ['type' => 'string'],
                    'max_points' => ['type' => 'integer', 'default' => 10],
                    'weight' => ['type' => 'number', 'default' => 1.0],
                    'levels' => [
                        'type' => 'array',
                        'items' => [
                            'label' => ['type' => 'string'],
                            'points' => ['type' => 'integer'],
                            'description' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'reference_material' => [
                'type' => 'string',
                'description' => 'Material de referencia para la IA evaluadora',
            ],
            'settings' => [
                'type' => 'object',
                'properties' => [
                    'passing_score' => ['type' => 'integer', 'default' => 60],
                    'min_words' => ['type' => 'integer', 'default' => 100],
                    'max_words' => ['type' => 'integer', 'default' => 2000],
                    'evaluation_mode' => [
                        'type' => 'string',
                        'enum' => ['ai', 'manual', 'hybrid'],
                        'default' => 'ai',
                    ],
                    'allow_draft' => ['type' => 'boolean', 'default' => TRUE],
                    'show_rubric' => ['type' => 'boolean', 'default' => TRUE],
                    'show_word_count' => ['type' => 'boolean', 'default' => TRUE],
                    'plagiarism_check' => ['type' => 'boolean', 'default' => FALSE],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Valida que exista el prompt y al menos un criterio de rubrica.
     */
    public function validate(array $data): array
    {
        $errors = parent::validate($data);

        if (empty($data['prompt'])) {
            $errors['prompt'] = $this->t('El prompt del ensayo es requerido.');
        }

        if (empty($data['rubric'])) {
            $errors['rubric'] = $this->t('Se requiere al menos un criterio de rúbrica.');
        }
        else {
            foreach ($data['rubric'] as $index => $criterion) {
                if (empty($criterion['criterion'])) {
                    $errors["rubric.$index.criterion"] = $this->t(
                        'El criterio @index requiere un nombre.',
                        ['@index' => $index + 1]
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     *
     * Renderiza el editor de ensayo.
     */
    public function render(array $data, array $settings = []): array
    {
        return [
            '#theme' => 'interactive_essay',
            '#prompt' => $data['prompt'] ?? '',
            '#instructions' => $data['instructions'] ?? '',
            '#rubric' => $data['rubric'] ?? [],
            '#settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Calcula la puntuacion basada en las evaluaciones de rubrica.
     * Las respuestas contienen el texto del ensayo y las puntuaciones
     * por criterio (si evaluacion IA/manual ya fue completada).
     */
    public function calculateScore(array $data, array $responses): array
    {
        $rubric = $data['rubric'] ?? [];
        $criterionScores = $responses['criterion_scores'] ?? [];
        $essayText = $responses['text'] ?? '';

        // Verificar limites de longitud.
        $wordCount = str_word_count($essayText);
        $minWords = $data['settings']['min_words'] ?? 100;
        $maxWords = $data['settings']['max_words'] ?? 2000;

        $lengthValid = $wordCount >= $minWords && $wordCount <= $maxWords;

        $totalWeightedMax = 0.0;
        $totalWeightedEarned = 0.0;
        $details = [];

        foreach ($rubric as $criterion) {
            $criterionId = $criterion['id'];
            $maxPoints = $criterion['max_points'] ?? 10;
            $weight = $criterion['weight'] ?? 1.0;

            $earned = $criterionScores[$criterionId] ?? 0;
            $earned = min($earned, $maxPoints);

            $totalWeightedMax += $maxPoints * $weight;
            $totalWeightedEarned += $earned * $weight;

            $details[$criterionId] = [
                'criterion' => $criterion['criterion'],
                'points_earned' => $earned,
                'max_points' => $maxPoints,
                'weight' => $weight,
            ];
        }

        $percentage = $this->calculatePercentage($totalWeightedEarned, $totalWeightedMax);
        $passingScore = (float) ($data['settings']['passing_score'] ?? 60);

        return [
            'score' => $percentage,
            'max_score' => 100,
            'passed' => $this->determinePassed($percentage, $passingScore) && $lengthValid,
            'raw_score' => round($totalWeightedEarned, 2),
            'raw_max' => round($totalWeightedMax, 2),
            'word_count' => $wordCount,
            'length_valid' => $lengthValid,
            'details' => $details,
            'evaluation_mode' => $data['settings']['evaluation_mode'] ?? 'ai',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Verbos xAPI para ensayo: incluye drafted y scored.
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'completed', 'scored', 'passed', 'failed'];
    }

}
