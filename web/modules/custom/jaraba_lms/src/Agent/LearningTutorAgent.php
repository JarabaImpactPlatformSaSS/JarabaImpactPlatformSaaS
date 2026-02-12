<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Agent;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_lms\Service\EnrollmentService;

/**
 * Learning Tutor AI Agent for Students.
 *
 * Provides personalized learning guidance, doubt resolution,
 * motivation and adaptive learning path recommendations.
 */
class LearningTutorAgent
{

    use StringTranslationTrait;

    /**
     * The enrollment service.
     */
    protected EnrollmentService $enrollmentService;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Constructor.
     */
    public function __construct(
        EnrollmentService $enrollment_service,
        AccountProxyInterface $current_user
    ) {
        $this->enrollmentService = $enrollment_service;
        $this->currentUser = $current_user;
    }

    /**
     * Gets agent metadata.
     */
    public function getAgentInfo(): array
    {
        return [
            'id' => 'learning_tutor',
            'name' => $this->t('Tutor de Aprendizaje'),
            'description' => $this->t('Tu compañero de estudio disponible 24/7'),
            'icon' => '📚',
            'color' => '#f59e0b',
            'capabilities' => [
                'doubt_resolution' => $this->t('Resolución de dudas'),
                'learning_path' => $this->t('Ruta personalizada'),
                'study_tips' => $this->t('Técnicas de estudio'),
                'progress_analysis' => $this->t('Análisis de progreso'),
                'motivation' => $this->t('Motivación'),
            ],
        ];
    }

    /**
     * Available actions for this agent.
     */
    public function getAvailableActions(): array
    {
        return [
            [
                'id' => 'ask_question',
                'label' => $this->t('Tengo una duda'),
                'icon' => '❓',
                'description' => $this->t('Pregúntame sobre el contenido de tu curso'),
            ],
            [
                'id' => 'explain_concept',
                'label' => $this->t('Explícame esto'),
                'icon' => '💡',
                'description' => $this->t('Te explico cualquier concepto de forma sencilla'),
            ],
            [
                'id' => 'suggest_path',
                'label' => $this->t('Mi ruta de aprendizaje'),
                'icon' => '🗺️',
                'description' => $this->t('Ruta personalizada según tus objetivos'),
            ],
            [
                'id' => 'study_tips',
                'label' => $this->t('Técnicas de estudio'),
                'icon' => '🧠',
                'description' => $this->t('Métodos para aprender más efectivamente'),
            ],
            [
                'id' => 'progress_review',
                'label' => $this->t('Revisar mi progreso'),
                'icon' => '📊',
                'description' => $this->t('Análisis de tu avance y recomendaciones'),
            ],
            [
                'id' => 'motivation_boost',
                'label' => $this->t('Necesito motivación'),
                'icon' => '🚀',
                'description' => $this->t('Un empujón para seguir adelante'),
            ],
        ];
    }

    /**
     * Executes an agent action.
     */
    public function executeAction(string $action_id, array $context = []): array
    {
        $userId = (int) $this->currentUser->id();

        switch ($action_id) {
            case 'ask_question':
                return $this->handleQuestion($context['question'] ?? NULL);

            case 'explain_concept':
                return $this->explainConcept($context['concept'] ?? NULL);

            case 'suggest_path':
                return $this->suggestLearningPath($userId);

            case 'study_tips':
                return $this->provideStudyTips();

            case 'progress_review':
                return $this->reviewProgress($userId);

            case 'motivation_boost':
                return $this->provideMotivation($userId);

            default:
                return [
                    'success' => FALSE,
                    'message' => $this->t('Acción no reconocida'),
                ];
        }
    }

    /**
     * Handles a question from the student.
     */
    protected function handleQuestion(?string $question): array
    {
        if (!$question) {
            return [
                'success' => TRUE,
                'type' => 'input_required',
                'title' => $this->t('¿Cuál es tu duda?'),
                'message' => $this->t('Escríbeme tu pregunta y te ayudaré a resolverla.'),
                'input_required' => 'question',
                'placeholder' => $this->t('Ej: ¿Qué es una variable en programación?'),
            ];
        }

        return [
            'success' => TRUE,
            'type' => 'answer',
            'title' => $this->t('Respuesta a tu pregunta'),
            'question' => $question,
            'answer' => $this->t('Esta es una explicación detallada basada en el contenido de tu curso actual. [Respuesta generada por IA según contexto]'),
            'related_resources' => [
                ['title' => $this->t('Lección relacionada'), 'url' => '#'],
                ['title' => $this->t('Ejercicio práctico'), 'url' => '#'],
            ],
            'follow_up' => $this->t('¿Necesitas que te lo explique de otra manera?'),
        ];
    }

    /**
     * Explains a concept.
     */
    protected function explainConcept(?string $concept): array
    {
        if (!$concept) {
            return [
                'success' => TRUE,
                'type' => 'input_required',
                'title' => $this->t('¿Qué concepto quieres entender?'),
                'message' => $this->t('Dime qué tema te gustaría que te explicara.'),
                'input_required' => 'concept',
            ];
        }

        return [
            'success' => TRUE,
            'type' => 'explanation',
            'title' => $this->t('Explicación: @concept', ['@concept' => $concept]),
            'sections' => [
                [
                    'title' => $this->t('¿Qué es?'),
                    'content' => $this->t('Definición simple y clara del concepto.'),
                ],
                [
                    'title' => $this->t('Ejemplo práctico'),
                    'content' => $this->t('Un ejemplo del mundo real para entenderlo mejor.'),
                ],
                [
                    'title' => $this->t('Por qué es importante'),
                    'content' => $this->t('Cómo se aplica en el contexto profesional.'),
                ],
            ],
            'analogy' => $this->t('Piensa en ello como... [analogía simple]'),
        ];
    }

    /**
     * Suggests personalized learning path.
     */
    protected function suggestLearningPath(int $userId): array
    {
        return [
            'success' => TRUE,
            'type' => 'learning_path',
            'title' => $this->t('Tu ruta de aprendizaje personalizada'),
            'current_level' => $this->t('Intermedio'),
            'goal' => $this->t('Desarrollador Full Stack'),
            'steps' => [
                [
                    'order' => 1,
                    'title' => $this->t('Completa "Fundamentos de JavaScript"'),
                    'status' => 'in_progress',
                    'progress' => 65,
                    'estimated' => '2h restantes',
                ],
                [
                    'order' => 2,
                    'title' => $this->t('Iniciar "React Básico"'),
                    'status' => 'locked',
                    'reason' => $this->t('Completa el paso anterior'),
                ],
                [
                    'order' => 3,
                    'title' => $this->t('Proyecto práctico: To-Do App'),
                    'status' => 'locked',
                ],
            ],
            'estimated_completion' => $this->t('3 semanas a tu ritmo actual'),
        ];
    }

    /**
     * Provides study tips.
     */
    protected function provideStudyTips(): array
    {
        return [
            'success' => TRUE,
            'type' => 'tips',
            'title' => $this->t('Técnicas de estudio efectivas'),
            'tips' => [
                [
                    'icon' => '🍅',
                    'title' => $this->t('Técnica Pomodoro'),
                    'content' => $this->t('Estudia 25 minutos, descansa 5. Cada 4 ciclos, descansa 15-30 minutos.'),
                ],
                [
                    'icon' => '🔄',
                    'title' => $this->t('Repetición espaciada'),
                    'content' => $this->t('Revisa el material a intervalos crecientes: 1 día, 3 días, 1 semana, 2 semanas.'),
                ],
                [
                    'icon' => '🎯',
                    'title' => $this->t('Práctica activa'),
                    'content' => $this->t('No solo leas: intenta resolver problemas, crear proyectos, enseñar a otros.'),
                ],
                [
                    'icon' => '📝',
                    'title' => $this->t('Toma notas activas'),
                    'content' => $this->t('Reformula los conceptos con tus propias palabras.'),
                ],
            ],
            'personalized_tip' => $this->t('Basándome en tu historial, te recomiendo sesiones de estudio de 30-45 minutos por la mañana.'),
        ];
    }

    /**
     * Reviews student progress.
     */
    protected function reviewProgress(int $userId): array
    {
        return [
            'success' => TRUE,
            'type' => 'progress_review',
            'title' => $this->t('Tu progreso de aprendizaje'),
            'summary' => [
                'courses_active' => 2,
                'courses_completed' => 3,
                'total_hours' => 47,
                'current_streak' => 5,
            ],
            'this_week' => [
                'hours_studied' => 6.5,
                'lessons_completed' => 8,
                'exercises_passed' => 12,
            ],
            'strengths' => [
                $this->t('Excelente constancia (5 días seguidos)'),
                $this->t('Alta tasa de aprobación en ejercicios (89%)'),
            ],
            'areas_to_improve' => [
                $this->t('Intenta completar las lecciones sin pausas largas'),
            ],
            'next_milestone' => [
                'title' => $this->t('Certificado en Fundamentos'),
                'remaining' => '35%',
            ],
        ];
    }

    /**
     * Provides motivation boost.
     */
    protected function provideMotivation(int $userId): array
    {
        $messages = [
            $this->t('¡Cada lección que completas te acerca más a tus metas! Ya has recorrido un gran camino.'),
            $this->t('Los mejores profesionales también fueron principiantes. La diferencia está en no rendirse.'),
            $this->t('5 días de racha de estudio 🔥 ¡Eso demuestra compromiso real con tu crecimiento!'),
        ];

        return [
            'success' => TRUE,
            'type' => 'motivation',
            'title' => $this->t('💪 ¡Ánimo, campeón/a!'),
            'message' => $messages[array_rand($messages)],
            'achievements' => [
                ['icon' => '🔥', 'text' => $this->t('5 días de racha')],
                ['icon' => '📚', 'text' => $this->t('47 horas de estudio')],
                ['icon' => '🏆', 'text' => $this->t('3 cursos completados')],
            ],
            'challenge' => [
                'title' => $this->t('Reto del día'),
                'description' => $this->t('Completa 2 lecciones hoy y desbloquea un badge especial'),
            ],
        ];
    }

}
