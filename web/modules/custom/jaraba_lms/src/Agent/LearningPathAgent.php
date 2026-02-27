<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Agent\SmartBaseAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Drupal\jaraba_lms\Service\CourseService;
use Drupal\jaraba_lms\Service\EnrollmentService;
use Psr\Log\LoggerInterface;

/**
 * Learning Path Agent — Gen 2 (S3-04: HAL-AI-07).
 *
 * Replaces LearningTutorAgent with full AI integration via SmartBaseAgent.
 * Provides personalized learning guidance, doubt resolution,
 * concept explanations, study tips, and progress reviews.
 *
 * Model routing:
 * - ask_question, study_tips → fast tier (Haiku)
 * - explain_concept, progress_review, suggest_path → balanced tier (Sonnet)
 */
class LearningPathAgent extends SmartBaseAgent
{

    /**
     * The enrollment service.
     */
    protected EnrollmentService $enrollmentService;

    /**
     * The course service.
     */
    protected CourseService $courseService;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ModelRouterService $modelRouter,
        EnrollmentService $enrollmentService,
        CourseService $courseService,
        $promptBuilder = NULL,
        ?ToolRegistry $toolRegistry = NULL,
        ?ProviderFallbackService $providerFallback = NULL,
        ?ContextWindowManager $contextWindowManager = NULL,
    ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->setModelRouter($modelRouter);
        $this->setToolRegistry($toolRegistry);
        $this->setProviderFallback($providerFallback);
        $this->setContextWindowManager($contextWindowManager);
        $this->enrollmentService = $enrollmentService;
        $this->courseService = $courseService;
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'learning_path';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Tutor de Aprendizaje';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Agente IA que guia al estudiante con rutas personalizadas, resolucion de dudas y analisis de progreso.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            [
                'id' => 'ask_question',
                'label' => 'Resolver duda',
                'description' => 'Responde dudas del estudiante usando RAG sobre contenido del curso.',
                'complexity' => 'low',
            ],
            [
                'id' => 'explain_concept',
                'label' => 'Explicar concepto',
                'description' => 'Explica un concepto con nivel adaptado al progreso del estudiante.',
                'complexity' => 'medium',
            ],
            [
                'id' => 'suggest_path',
                'label' => 'Sugerir ruta',
                'description' => 'Sugiere proximos cursos basado en completados y skills declarados.',
                'complexity' => 'medium',
            ],
            [
                'id' => 'study_tips',
                'label' => 'Tecnicas de estudio',
                'description' => 'Genera tecnicas de estudio personalizadas.',
                'complexity' => 'low',
            ],
            [
                'id' => 'progress_review',
                'label' => 'Revision de progreso',
                'description' => 'Analiza progreso y genera resumen con recomendaciones.',
                'complexity' => 'medium',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $action, array $context): array
    {
        return match ($action) {
            'ask_question' => $this->executeAskQuestion($context),
            'explain_concept' => $this->executeExplainConcept($context),
            'suggest_path' => $this->executeSuggestPath($context),
            'study_tips' => $this->executeStudyTips($context),
            'progress_review' => $this->executeProgressReview($context),
            default => ['success' => FALSE, 'error' => "Accion no soportada: {$action}"],
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBrandVoice(): string
    {
        return 'Eres un tutor de aprendizaje experto y paciente. Tu tono es cercano, motivador y pedagogico. Adaptas las explicaciones al nivel del estudiante. Siempre ofreces ejemplos practicos y animas al alumno a seguir aprendiendo.';
    }

    /**
     * Resolves a student question using AI.
     */
    protected function executeAskQuestion(array $context): array
    {
        $question = $context['question'] ?? $context['input'] ?? '';
        if (empty($question)) {
            return [
                'success' => TRUE,
                'type' => 'input_required',
                'input_required' => 'question',
            ];
        }

        $studentContext = $this->buildStudentContext($context);

        $prompt = "El estudiante pregunta: \"{$question}\"\n\n"
            . "{$studentContext}\n\n"
            . "Responde de forma clara y pedagogica. Si es posible, incluye un ejemplo practico. "
            . "Responde en formato JSON: {\"answer\": \"...\", \"related_topics\": [\"...\"], \"difficulty_level\": \"basico|intermedio|avanzado\"}";

        return $this->callAiApi($prompt, ['require_speed' => TRUE]);
    }

    /**
     * Explains a concept adapted to student level.
     */
    protected function executeExplainConcept(array $context): array
    {
        $concept = $context['concept'] ?? $context['input'] ?? '';
        if (empty($concept)) {
            return [
                'success' => TRUE,
                'type' => 'input_required',
                'input_required' => 'concept',
            ];
        }

        $studentContext = $this->buildStudentContext($context);

        $prompt = "Explica el concepto \"{$concept}\" al estudiante.\n\n"
            . "{$studentContext}\n\n"
            . "Estructura tu explicacion en:\n"
            . "1. Definicion simple\n"
            . "2. Ejemplo del mundo real\n"
            . "3. Por que es importante\n"
            . "4. Una analogia sencilla\n\n"
            . "Responde en JSON: {\"definition\": \"...\", \"example\": \"...\", \"importance\": \"...\", \"analogy\": \"...\"}";

        return $this->callAiApi($prompt);
    }

    /**
     * Suggests learning path based on enrolled and completed courses.
     */
    protected function executeSuggestPath(array $context): array
    {
        $userId = (int) ($context['user_id'] ?? 0);
        $enrollmentData = $this->getEnrollmentData($userId);
        $availableCourses = $this->getAvailableCourses();

        $prompt = "Genera una ruta de aprendizaje personalizada para este estudiante.\n\n"
            . "Cursos completados y en progreso:\n{$enrollmentData}\n\n"
            . "Cursos disponibles:\n{$availableCourses}\n\n"
            . "Sugiere los proximos 3-5 cursos en orden, explicando por que cada uno es relevante.\n"
            . "Responde en JSON: {\"path\": [{\"course\": \"...\", \"reason\": \"...\", \"priority\": 1}], \"estimated_weeks\": N}";

        return $this->callAiApi($prompt);
    }

    /**
     * Generates personalized study tips.
     */
    protected function executeStudyTips(array $context): array
    {
        $studentContext = $this->buildStudentContext($context);

        $prompt = "Genera tecnicas de estudio personalizadas para este estudiante.\n\n"
            . "{$studentContext}\n\n"
            . "Incluye:\n"
            . "- 3-4 tecnicas concretas con instrucciones paso a paso\n"
            . "- Un horario sugerido basado en su patron de actividad\n"
            . "- Un consejo personalizado basado en sus fortalezas y debilidades\n\n"
            . "Responde en JSON: {\"techniques\": [{\"name\": \"...\", \"steps\": [\"...\"], \"benefit\": \"...\"}], \"schedule_tip\": \"...\", \"personal_advice\": \"...\"}";

        return $this->callAiApi($prompt, ['require_speed' => TRUE]);
    }

    /**
     * Reviews student progress with AI analysis.
     */
    protected function executeProgressReview(array $context): array
    {
        $userId = (int) ($context['user_id'] ?? 0);
        $enrollmentData = $this->getEnrollmentData($userId);

        $prompt = "Analiza el progreso de este estudiante y genera un informe motivador.\n\n"
            . "Datos de matriculacion:\n{$enrollmentData}\n\n"
            . "Genera:\n"
            . "1. Resumen del progreso (logros, areas fuertes)\n"
            . "2. Areas de mejora (sin ser negativo, enfocado en oportunidad)\n"
            . "3. Proximo hito alcanzable\n"
            . "4. Mensaje motivador personalizado\n\n"
            . "Responde en JSON: {\"summary\": \"...\", \"strengths\": [\"...\"], \"opportunities\": [\"...\"], \"next_milestone\": \"...\", \"motivation\": \"...\"}";

        return $this->callAiApi($prompt);
    }

    /**
     * Builds student context string for AI prompts.
     */
    protected function buildStudentContext(array $context): string
    {
        $userId = (int) ($context['user_id'] ?? 0);
        $courseId = (int) ($context['course_id'] ?? 0);
        $parts = [];

        if ($userId > 0) {
            try {
                $enrollments = $this->enrollmentService->getUserEnrollments($userId);
                $completed = 0;
                $active = 0;
                foreach ($enrollments as $enrollment) {
                    $status = $enrollment->get('status')->value ?? '';
                    if ($status === 'completed') {
                        $completed++;
                    }
                    else {
                        $active++;
                    }
                }
                $parts[] = "Cursos activos: {$active}, Completados: {$completed}";
            }
            catch (\Exception $e) {
                // Non-critical — continue without enrollment data.
            }
        }

        if ($courseId > 0 && $userId > 0) {
            try {
                $enrollment = $this->enrollmentService->getEnrollment($userId, $courseId);
                if ($enrollment) {
                    $progress = $enrollment->getProgressPercent();
                    $parts[] = "Progreso en curso actual: {$progress}%";
                }
            }
            catch (\Exception $e) {
                // Non-critical.
            }
        }

        return !empty($parts) ? "Contexto del estudiante:\n- " . implode("\n- ", $parts) : '';
    }

    /**
     * Gets enrollment data as text for AI context.
     */
    protected function getEnrollmentData(int $userId): string
    {
        if ($userId <= 0) {
            return 'Sin datos de matriculacion disponibles.';
        }

        try {
            $enrollments = $this->enrollmentService->getUserEnrollments($userId);
            if (empty($enrollments)) {
                return 'El estudiante no tiene matriculaciones activas.';
            }

            $lines = [];
            foreach ($enrollments as $enrollment) {
                $courseName = $enrollment->label() ?? 'Curso';
                $progress = $enrollment->getProgressPercent() ?? 0;
                $status = $enrollment->get('status')->value ?? 'active';
                $lines[] = "- {$courseName}: {$progress}% ({$status})";
            }
            return implode("\n", $lines);
        }
        catch (\Exception $e) {
            return 'Error al cargar datos de matriculacion.';
        }
    }

    /**
     * Gets available courses as text for AI context.
     */
    protected function getAvailableCourses(): string
    {
        try {
            $courses = $this->courseService->getPublishedCourses(20);
            if (empty($courses)) {
                return 'No hay cursos disponibles actualmente.';
            }

            $lines = [];
            foreach ($courses as $course) {
                $title = $course->label() ?? 'Curso';
                $difficulty = '';
                if ($course->hasField('difficulty_level')) {
                    $difficulty = $course->get('difficulty_level')->value ?? '';
                }
                $line = "- {$title}";
                if ($difficulty) {
                    $line .= " ({$difficulty})";
                }
                $lines[] = $line;
            }
            return implode("\n", $lines);
        }
        catch (\Exception $e) {
            return 'Error al cargar catalogo de cursos.';
        }
    }

}
