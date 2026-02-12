<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeBase;

/**
 * Plugin para video interactivo con checkpoints y quizzes.
 *
 * Estructura: Define un reproductor de video con puntos de interaccion
 * (checkpoints) que pausan el video para mostrar quizzes, informacion
 * o decisiones al usuario. Soporta chapters para navegacion.
 *
 * Logica: Los checkpoints se disparan por timestamp. Cada checkpoint
 * puede contener un quiz (evaluado), un overlay informativo o una
 * decision que afecta la progresion. La puntuacion se calcula
 * ponderando los quizzes de cada checkpoint.
 *
 * Sintaxis: Sigue el patron plugin @InteractiveType con anotacion,
 * extiende InteractiveTypeBase y sobreescribe getSchema(), render(),
 * calculateScore() y getXapiVerbs().
 *
 * @InteractiveType(
 *   id = "interactive_video",
 *   label = @Translation("Video Interactivo"),
 *   description = @Translation("Video con checkpoints, quizzes en timestamps y chapters de navegación."),
 *   category = "media",
 *   icon = "media/play-circle",
 *   weight = 10
 * )
 */
class InteractiveVideo extends InteractiveTypeBase
{

    /**
     * {@inheritdoc}
     *
     * Esquema del contenido de video interactivo.
     * Define la estructura JSON para video_url, chapters y checkpoints.
     */
    public function getSchema(): array
    {
        return [
            'video_url' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'URL del video (YouTube, Vimeo o archivo directo)',
            ],
            'poster_url' => [
                'type' => 'string',
                'description' => 'URL de la imagen de portada',
            ],
            'chapters' => [
                'type' => 'array',
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'title' => ['type' => 'string', 'required' => TRUE],
                    'start_time' => ['type' => 'number', 'required' => TRUE],
                    'end_time' => ['type' => 'number'],
                ],
            ],
            'checkpoints' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'timestamp' => ['type' => 'number', 'required' => TRUE],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['quiz', 'overlay', 'decision'],
                        'required' => TRUE,
                    ],
                    'title' => ['type' => 'string'],
                    'content' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'id' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                    'correct' => ['type' => 'boolean'],
                                    'feedback' => ['type' => 'string'],
                                ],
                            ],
                            'text' => ['type' => 'string'],
                            'image_url' => ['type' => 'string'],
                            'required' => ['type' => 'boolean', 'default' => TRUE],
                        ],
                    ],
                    'points' => ['type' => 'integer', 'default' => 1],
                ],
            ],
            'settings' => [
                'type' => 'object',
                'properties' => [
                    'passing_score' => ['type' => 'integer', 'default' => 70],
                    'allow_skip_checkpoints' => ['type' => 'boolean', 'default' => FALSE],
                    'allow_rewind' => ['type' => 'boolean', 'default' => TRUE],
                    'autoplay' => ['type' => 'boolean', 'default' => FALSE],
                    'show_chapters' => ['type' => 'boolean', 'default' => TRUE],
                    'show_progress' => ['type' => 'boolean', 'default' => TRUE],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Valida datos especificos del video interactivo.
     * Verifica URL del video y que los checkpoints tengan timestamps validos.
     */
    public function validate(array $data): array
    {
        $errors = parent::validate($data);

        if (empty($data['video_url'])) {
            $errors['video_url'] = $this->t('La URL del video es requerida.');
        }

        if (!empty($data['checkpoints'])) {
            foreach ($data['checkpoints'] as $index => $checkpoint) {
                if (!isset($checkpoint['timestamp']) || $checkpoint['timestamp'] < 0) {
                    $errors["checkpoints.$index.timestamp"] = $this->t(
                        'El checkpoint @index requiere un timestamp válido.',
                        ['@index' => $index + 1]
                    );
                }
                if (empty($checkpoint['type'])) {
                    $errors["checkpoints.$index.type"] = $this->t(
                        'El checkpoint @index requiere un tipo (quiz, overlay, decision).',
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
     * Renderiza el reproductor de video interactivo.
     * Prepara los datos de checkpoints y chapters para el motor JS.
     */
    public function render(array $data, array $settings = []): array
    {
        return [
            '#theme' => 'interactive_video',
            '#video_url' => $data['video_url'] ?? '',
            '#poster_url' => $data['poster_url'] ?? '',
            '#chapters' => $data['chapters'] ?? [],
            '#checkpoints' => $data['checkpoints'] ?? [],
            '#settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Calcula la puntuacion basada en los checkpoints tipo quiz respondidos.
     * Los checkpoints tipo overlay y decision no puntuan directamente.
     */
    public function calculateScore(array $data, array $responses): array
    {
        $checkpoints = $data['checkpoints'] ?? [];
        $totalPoints = 0;
        $earnedPoints = 0;
        $details = [];

        foreach ($checkpoints as $checkpoint) {
            // Solo puntuan los checkpoints tipo quiz.
            if ($checkpoint['type'] !== 'quiz') {
                continue;
            }

            $checkpointId = $checkpoint['id'];
            $points = $checkpoint['points'] ?? 1;
            $totalPoints += $points;

            $userAnswer = $responses[$checkpointId] ?? NULL;
            $isCorrect = $this->checkCheckpointAnswer($checkpoint, $userAnswer);

            if ($isCorrect) {
                $earnedPoints += $points;
            }

            $details[$checkpointId] = [
                'correct' => $isCorrect,
                'user_answer' => $userAnswer,
                'correct_answer' => $this->getCheckpointCorrectAnswer($checkpoint),
                'points_earned' => $isCorrect ? $points : 0,
                'timestamp' => $checkpoint['timestamp'],
            ];
        }

        $percentage = $this->calculatePercentage((float) $earnedPoints, (float) $totalPoints);
        $passingScore = (float) ($data['settings']['passing_score'] ?? 70);

        return [
            'score' => $percentage,
            'max_score' => 100,
            'passed' => $this->determinePassed($percentage, $passingScore),
            'raw_score' => $earnedPoints,
            'raw_max' => $totalPoints,
            'details' => $details,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Verbos xAPI especificos para video interactivo.
     * Incluye interacted para checkpoints y progressed para chapters.
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'interacted', 'completed', 'passed', 'failed', 'progressed'];
    }

    /**
     * Verifica la respuesta de un checkpoint.
     *
     * @param array $checkpoint
     *   Los datos del checkpoint.
     * @param mixed $userAnswer
     *   La respuesta del usuario.
     *
     * @return bool
     *   TRUE si la respuesta es correcta.
     */
    protected function checkCheckpointAnswer(array $checkpoint, mixed $userAnswer): bool
    {
        if ($userAnswer === NULL) {
            return FALSE;
        }

        $options = $checkpoint['content']['options'] ?? [];
        foreach ($options as $option) {
            if ($option['id'] === $userAnswer && !empty($option['correct'])) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Obtiene la respuesta correcta de un checkpoint.
     *
     * @param array $checkpoint
     *   Los datos del checkpoint.
     *
     * @return string|null
     *   El ID de la opcion correcta, o NULL.
     */
    protected function getCheckpointCorrectAnswer(array $checkpoint): ?string
    {
        $options = $checkpoint['content']['options'] ?? [];
        foreach ($options as $option) {
            if (!empty($option['correct'])) {
                return $option['id'];
            }
        }
        return NULL;
    }

}
