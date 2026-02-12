<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeBase;

/**
 * Plugin para presentaciones interactivas con slides.
 *
 * Estructura: Define una presentacion con multiples slides, cada una con
 * layout configurable, contenido multimedia y quizzes opcionales embebidos.
 * Soporta navegacion libre o secuencial segun configuracion.
 *
 * Logica: Cada slide puede marcar required=true para forzar interaccion
 * antes de avanzar. Los quizzes embebidos en slides contribuyen a la
 * puntuacion global. La navegacion puede ser libre o secuencial.
 *
 * Sintaxis: Plugin @InteractiveType con category "interactive".
 *
 * @InteractiveType(
 *   id = "course_presentation",
 *   label = @Translation("Presentación"),
 *   description = @Translation("Presentación interactiva con slides, layouts configurables y quizzes embebidos."),
 *   category = "interactive",
 *   icon = "education/presentation",
 *   weight = 20
 * )
 */
class CoursePresentation extends InteractiveTypeBase
{

    /**
     * {@inheritdoc}
     *
     * Esquema del contenido de presentacion.
     * Define estructura de slides con layouts y quizzes embebidos.
     */
    public function getSchema(): array
    {
        return [
            'slides' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'title' => ['type' => 'string', 'required' => TRUE],
                    'layout' => [
                        'type' => 'string',
                        'enum' => ['full', 'split', 'title_only', 'media_left', 'media_right', 'quiz'],
                        'default' => 'full',
                    ],
                    'content' => [
                        'type' => 'object',
                        'properties' => [
                            'text' => ['type' => 'string'],
                            'html' => ['type' => 'string'],
                            'image_url' => ['type' => 'string'],
                            'video_url' => ['type' => 'string'],
                            'code' => ['type' => 'string'],
                            'code_language' => ['type' => 'string'],
                        ],
                    ],
                    'quiz' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'type' => [
                                'type' => 'string',
                                'enum' => ['multiple_choice', 'true_false'],
                            ],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'id' => ['type' => 'string'],
                                    'text' => ['type' => 'string'],
                                    'correct' => ['type' => 'boolean'],
                                    'feedback' => ['type' => 'string'],
                                ],
                            ],
                            'points' => ['type' => 'integer', 'default' => 1],
                        ],
                    ],
                    'required' => ['type' => 'boolean', 'default' => FALSE],
                    'speaker_notes' => ['type' => 'string'],
                ],
            ],
            'settings' => [
                'type' => 'object',
                'properties' => [
                    'passing_score' => ['type' => 'integer', 'default' => 70],
                    'navigation' => [
                        'type' => 'string',
                        'enum' => ['free', 'sequential'],
                        'default' => 'free',
                    ],
                    'show_progress' => ['type' => 'boolean', 'default' => TRUE],
                    'show_slide_numbers' => ['type' => 'boolean', 'default' => TRUE],
                    'enable_keyboard' => ['type' => 'boolean', 'default' => TRUE],
                    'auto_advance' => ['type' => 'boolean', 'default' => FALSE],
                    'auto_advance_delay' => ['type' => 'integer', 'default' => 5],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Valida que exista al menos una slide.
     */
    public function validate(array $data): array
    {
        $errors = parent::validate($data);

        $slides = $data['slides'] ?? [];
        if (empty($slides)) {
            $errors['slides'] = $this->t('Se requiere al menos una slide.');
        }

        foreach ($slides as $index => $slide) {
            if (empty($slide['title'])) {
                $errors["slides.$index.title"] = $this->t(
                    'La slide @index requiere un título.',
                    ['@index' => $index + 1]
                );
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     *
     * Renderiza la presentacion interactiva.
     */
    public function render(array $data, array $settings = []): array
    {
        return [
            '#theme' => 'interactive_course_presentation',
            '#slides' => $data['slides'] ?? [],
            '#settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Calcula la puntuacion basada en quizzes embebidos en slides.
     * Solo las slides con quiz contribuyen a la puntuacion.
     */
    public function calculateScore(array $data, array $responses): array
    {
        $slides = $data['slides'] ?? [];
        $totalPoints = 0;
        $earnedPoints = 0;
        $details = [];

        foreach ($slides as $slide) {
            // Solo puntuan las slides con quiz.
            if (empty($slide['quiz'])) {
                continue;
            }

            $slideId = $slide['id'];
            $points = $slide['quiz']['points'] ?? 1;
            $totalPoints += $points;

            $userAnswer = $responses[$slideId] ?? NULL;
            $isCorrect = $this->checkQuizAnswer($slide['quiz'], $userAnswer);

            if ($isCorrect) {
                $earnedPoints += $points;
            }

            $details[$slideId] = [
                'correct' => $isCorrect,
                'user_answer' => $userAnswer,
                'correct_answer' => $this->getQuizCorrectAnswer($slide['quiz']),
                'points_earned' => $isCorrect ? $points : 0,
            ];
        }

        // Si no hay quizzes, la puntuacion es 100% por completar la presentacion.
        if ($totalPoints === 0) {
            return [
                'score' => 100.0,
                'max_score' => 100,
                'passed' => TRUE,
                'raw_score' => 0,
                'raw_max' => 0,
                'details' => [],
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
     * Verbos xAPI para presentacion: incluye progressed por slide.
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'answered', 'completed', 'passed', 'failed', 'progressed'];
    }

    /**
     * Verifica la respuesta de un quiz embebido.
     *
     * @param array $quiz
     *   Los datos del quiz.
     * @param mixed $userAnswer
     *   La respuesta del usuario.
     *
     * @return bool
     *   TRUE si la respuesta es correcta.
     */
    protected function checkQuizAnswer(array $quiz, mixed $userAnswer): bool
    {
        if ($userAnswer === NULL) {
            return FALSE;
        }

        $options = $quiz['options'] ?? [];
        foreach ($options as $option) {
            if ($option['id'] === $userAnswer && !empty($option['correct'])) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Obtiene la respuesta correcta de un quiz embebido.
     *
     * @param array $quiz
     *   Los datos del quiz.
     *
     * @return string|null
     *   El ID de la opcion correcta.
     */
    protected function getQuizCorrectAnswer(array $quiz): ?string
    {
        foreach ($quiz['options'] ?? [] as $option) {
            if (!empty($option['correct'])) {
                return $option['id'];
            }
        }
        return NULL;
    }

}
