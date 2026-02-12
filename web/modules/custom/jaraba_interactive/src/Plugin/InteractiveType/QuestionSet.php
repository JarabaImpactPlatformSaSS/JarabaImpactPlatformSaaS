<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin\InteractiveType;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeBase;

/**
 * Plugin para cuestionarios y evaluaciones.
 *
 * @InteractiveType(
 *   id = "question_set",
 *   label = @Translation("Cuestionario"),
 *   description = @Translation("Conjunto de preguntas con múltiples tipos: opción múltiple, verdadero/falso, respuesta corta."),
 *   category = "assessment",
 *   icon = "education/graduation-cap",
 *   weight = 0
 * )
 */
class QuestionSet extends InteractiveTypeBase
{

    /**
     * {@inheritdoc}
     */
    public function getSchema(): array
    {
        return [
            'questions' => [
                'type' => 'array',
                'required' => TRUE,
                'items' => [
                    'id' => ['type' => 'string', 'required' => TRUE],
                    'type' => [
                        'type' => 'string',
                        'enum' => ['multiple_choice', 'true_false', 'short_answer', 'fill_blanks'],
                        'required' => TRUE,
                    ],
                    'text' => ['type' => 'string', 'required' => TRUE],
                    'options' => [
                        'type' => 'array',
                        'items' => [
                            'id' => ['type' => 'string'],
                            'text' => ['type' => 'string'],
                            'correct' => ['type' => 'boolean'],
                            'feedback' => ['type' => 'string'],
                        ],
                    ],
                    'correct_answer' => ['type' => 'string'],
                    'points' => ['type' => 'integer', 'default' => 1],
                    'hint' => ['type' => 'string'],
                    'explanation' => ['type' => 'string'],
                ],
            ],
            'settings' => [
                'type' => 'object',
                'properties' => [
                    'passing_score' => ['type' => 'integer', 'default' => 70],
                    'max_attempts' => ['type' => 'integer', 'default' => 3],
                    'randomize_questions' => ['type' => 'boolean', 'default' => FALSE],
                    'randomize_options' => ['type' => 'boolean', 'default' => FALSE],
                    'show_feedback' => [
                        'type' => 'string',
                        'enum' => ['immediate', 'end', 'never'],
                        'default' => 'immediate',
                    ],
                    'time_limit' => ['type' => 'integer', 'default' => 0],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $data, array $settings = []): array
    {
        return [
            '#theme' => 'interactive_question_set',
            '#questions' => $data['questions'] ?? [],
            '#settings' => array_merge($settings, $data['settings'] ?? []),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function calculateScore(array $data, array $responses): array
    {
        $questions = $data['questions'] ?? [];
        $totalPoints = 0;
        $earnedPoints = 0;
        $details = [];

        foreach ($questions as $question) {
            $questionId = $question['id'];
            $points = $question['points'] ?? 1;
            $totalPoints += $points;

            $userAnswer = $responses[$questionId] ?? NULL;
            $isCorrect = $this->checkAnswer($question, $userAnswer);

            if ($isCorrect) {
                $earnedPoints += $points;
            }

            $details[$questionId] = [
                'correct' => $isCorrect,
                'user_answer' => $userAnswer,
                'correct_answer' => $this->getCorrectAnswer($question),
                'points_earned' => $isCorrect ? $points : 0,
                'feedback' => $this->getFeedback($question, $isCorrect),
            ];
        }

        $percentage = $this->calculatePercentage($earnedPoints, $totalPoints);
        $passingScore = $data['settings']['passing_score'] ?? 70;

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
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'answered', 'completed', 'passed', 'failed'];
    }

    /**
     * Verifica si una respuesta es correcta.
     */
    protected function checkAnswer(array $question, $userAnswer): bool
    {
        switch ($question['type']) {
            case 'multiple_choice':
                foreach ($question['options'] ?? [] as $option) {
                    if ($option['id'] === $userAnswer && !empty($option['correct'])) {
                        return TRUE;
                    }
                }
                return FALSE;

            case 'true_false':
                return $question['correct_answer'] === $userAnswer;

            case 'short_answer':
                $correct = strtolower(trim($question['correct_answer'] ?? ''));
                $answer = strtolower(trim($userAnswer ?? ''));
                return $correct === $answer;

            default:
                return FALSE;
        }
    }

    /**
     * Obtiene la respuesta correcta de una pregunta.
     */
    protected function getCorrectAnswer(array $question): mixed
    {
        switch ($question['type']) {
            case 'multiple_choice':
                foreach ($question['options'] ?? [] as $option) {
                    if (!empty($option['correct'])) {
                        return $option['id'];
                    }
                }
                break;

            default:
                return $question['correct_answer'] ?? NULL;
        }
        return NULL;
    }

    /**
     * Obtiene el feedback para una respuesta.
     */
    protected function getFeedback(array $question, bool $isCorrect): string
    {
        if ($isCorrect) {
            return (string) t('¡Correcto!');
        }

        if (!empty($question['explanation'])) {
            return $question['explanation'];
        }

        return (string) t('Respuesta incorrecta.');
    }

}
