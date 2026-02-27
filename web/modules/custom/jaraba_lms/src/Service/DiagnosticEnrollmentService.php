<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Auto-Enrollment Post-Diagnóstico.
 *
 * Implementa ECA-LMS-001: Inscripción automática basada en
 * el resultado del Diagnóstico Express de Empleabilidad.
 */
class DiagnosticEnrollmentService
{

    /**
     * Database connection.
     */
    protected Connection $database;

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Mapeo de perfiles de diagnóstico a learning paths.
     */
    const PROFILE_PATH_MAPPING = [
        // Perfil "Invisible" - Mayor brecha digital
        'invisible' => [
            'linkedin' => 'ruta_linkedin_basico',
            'cv' => 'ruta_cv_moderno',
            'search_strategy' => 'ruta_busqueda_empleo',
            'all' => 'ruta_transformacion_digital',
        ],
        // Perfil "Desconectado" - Conocimientos pero sin presencia
        'desconectado' => [
            'linkedin' => 'ruta_linkedin_intermedio',
            'cv' => 'ruta_cv_ats',
            'search_strategy' => 'ruta_networking_digital',
            'all' => 'ruta_presencia_profesional',
        ],
        // Perfil "En construcción" - Avanzando
        'construccion' => [
            'linkedin' => 'ruta_linkedin_avanzado',
            'cv' => 'ruta_portfolio_digital',
            'search_strategy' => 'ruta_marca_personal',
            'all' => 'ruta_optimizacion_perfil',
        ],
        // Perfil "Competitivo" - Casi listo
        'competitivo' => [
            'linkedin' => 'ruta_linkedin_experto',
            'cv' => 'ruta_cv_sector',
            'search_strategy' => 'ruta_entrevistas',
            'all' => 'ruta_diferenciacion',
        ],
        // Perfil "Magnético" - Excelente (refuerzo)
        'magnetico' => [
            'linkedin' => 'ruta_thought_leadership',
            'cv' => 'ruta_executive_cv',
            'search_strategy' => 'ruta_headhunting',
            'all' => 'ruta_liderazgo_digital',
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Procesa el resultado de un diagnóstico y crea enrollments automáticos.
     *
     * @param int $userId
     *   ID del usuario.
     * @param string $profileType
     *   Tipo de perfil: invisible, desconectado, construccion, competitivo, magnetico.
     * @param string $primaryGap
     *   Brecha principal: linkedin, cv, search_strategy, all.
     * @param array $diagnosticData
     *   Datos adicionales del diagnóstico.
     *
     * @return array
     *   Resultado del enrollment.
     */
    public function processPostDiagnostic(int $userId, string $profileType, string $primaryGap, array $diagnosticData = []): array
    {
        $result = [
            'success' => FALSE,
            'enrollments' => [],
            'credits_awarded' => 0,
            'message' => '',
        ];

        // Validar perfil
        if (!isset(self::PROFILE_PATH_MAPPING[$profileType])) {
            $result['message'] = "Perfil no válido: $profileType";
            return $result;
        }

        // Obtener learning path recomendada
        $pathMachineName = self::PROFILE_PATH_MAPPING[$profileType][$primaryGap]
            ?? self::PROFILE_PATH_MAPPING[$profileType]['all'];

        // Buscar learning path
        $learningPath = $this->findLearningPath($pathMachineName);
        if (!$learningPath) {
            $result['message'] = "Learning path no encontrada: $pathMachineName";
            $this->logger->warning('Learning path not found: @path', ['@path' => $pathMachineName]);
            return $result;
        }

        // Obtener cursos de la ruta
        $courseIds = json_decode($learningPath->courses ?? '[]', TRUE) ?: [];
        if (empty($courseIds)) {
            $result['message'] = 'La ruta de aprendizaje no tiene cursos asignados';
            return $result;
        }

        // Crear enrollment para el primer curso
        $firstCourseId = $courseIds[0];
        $enrollmentResult = $this->createEnrollment($userId, $firstCourseId, $learningPath->id, $diagnosticData);

        if ($enrollmentResult['success']) {
            $result['enrollments'][] = $enrollmentResult;

            // Otorgar créditos iniciales (+50)
            $credits = $this->awardDiagnosticCredits($userId, $profileType);
            $result['credits_awarded'] = $credits;

            // Queue webhook ActiveCampaign
            $this->queueActiveCampaignTag($userId, 'lms_enrolled', [
                'profile_type' => $profileType,
                'primary_gap' => $primaryGap,
                'course_id' => $firstCourseId,
            ]);

            // Queue email de bienvenida (delay 1 hora)
            $this->queueWelcomeEmail($userId, $learningPath, $firstCourseId);

            $result['success'] = TRUE;
            $result['message'] = 'Inscripción automática completada';

            $this->logger->info('Auto-enrollment completed for user @user: profile=@profile, gap=@gap, course=@course', [
                '@user' => $userId,
                '@profile' => $profileType,
                '@gap' => $primaryGap,
                '@course' => $firstCourseId,
            ]);
        } else {
            $result['message'] = $enrollmentResult['message'] ?? 'Error creando enrollment';
        }

        return $result;
    }

    /**
     * Busca una learning path por machine_name.
     */
    protected function findLearningPath(string $machineName): ?object
    {
        if (!$this->database->schema()->tableExists('learning_path')) {
            return NULL;
        }

        return $this->database->select('learning_path', 'lp')
            ->fields('lp')
            ->condition('machine_name', $machineName)
            ->condition('is_active', 1)
            ->execute()
            ->fetchObject() ?: NULL;
    }

    /**
     * Crea un enrollment para un usuario en un curso.
     */
    protected function createEnrollment(int $userId, int $courseId, int $learningPathId, array $diagnosticData): array
    {
        try {
            // Verificar si ya existe enrollment
            $storage = $this->entityTypeManager->getStorage('lms_enrollment');
            $existing = $storage->loadByProperties([
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);

            if (!empty($existing)) {
                return [
                    'success' => TRUE,
                    'enrollment_id' => reset($existing)->id(),
                    'message' => 'Ya existe enrollment para este curso',
                    'already_enrolled' => TRUE,
                ];
            }

            // Crear nuevo enrollment
            $enrollment = $storage->create([
                'user_id' => $userId,
                'course_id' => $courseId,
                'enrollment_type' => 'diagnostic',
                'status' => 'active',
                'enrolled_at' => time(),
                'source' => 'diagnostic',
                'metadata' => json_encode([
                    'learning_path_id' => $learningPathId,
                    'diagnostic_data' => $diagnosticData,
                    'auto_enrolled' => TRUE,
                ]),
            ]);
            $enrollment->save();

            return [
                'success' => TRUE,
                'enrollment_id' => $enrollment->id(),
                'message' => 'Enrollment creado exitosamente',
                'already_enrolled' => FALSE,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error creating enrollment: @error', ['@error' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Otorga créditos por completar diagnóstico.
     */
    protected function awardDiagnosticCredits(int $userId, string $profileType): int
    {
        try {
            if (!\Drupal::hasService('ecosistema_jaraba_core.impact_credit')) {
                return 0;
            }

            $creditService = \Drupal::service('ecosistema_jaraba_core.impact_credit');

            // +50 por completar diagnóstico
            $creditService->awardCredits($userId, 'complete_diagnostic', NULL, [
                'profile_type' => $profileType,
            ]);

            // +50 adicionales por inscribirse automáticamente
            $creditService->awardCredits($userId, 'lms_enrolled', NULL, [
                'source' => 'diagnostic',
            ]);

            return 100; // 50 + 50
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Encola tag de ActiveCampaign.
     */
    protected function queueActiveCampaignTag(int $userId, string $tag, array $data): void
    {
        try {
            $queue = \Drupal::queue('activecampaign_tag');
            $queue->createItem([
                'user_id' => $userId,
                'tag' => $tag,
                'data' => $data,
                'queued_at' => time(),
            ]);
        } catch (\Exception $e) {
            // Log pero no bloquear
        }
    }

    /**
     * Encola email de bienvenida con delay.
     */
    protected function queueWelcomeEmail(int $userId, object $learningPath, int $courseId): void
    {
        try {
            $queue = \Drupal::queue('lms_welcome_email');
            $queue->createItem([
                'user_id' => $userId,
                'learning_path_id' => $learningPath->id,
                'learning_path_title' => $learningPath->title,
                'course_id' => $courseId,
                'send_after' => time() + 3600, // Delay 1 hora
                'queued_at' => time(),
            ]);
        } catch (\Exception $e) {
            // Log pero no bloquear
        }
    }

    /**
     * Registra resultado de diagnóstico.
     *
     * @param int $userId
     *   ID del usuario.
     * @param array $answers
     *   Respuestas del diagnóstico.
     *
     * @return array
     *   Resultado con profile_type y primary_gap.
     */
    public function saveDiagnosticResult(int $userId, array $answers): array
    {
        $this->ensureTablesExist();

        // Calcular perfil basado en respuestas
        $result = $this->calculateProfile($answers);

        // Guardar en BD
        $this->database->merge('diagnostic_result')
            ->keys(['user_id' => $userId])
            ->fields([
                'user_id' => $userId,
                'profile_type' => $result['profile_type'],
                'primary_gap' => $result['primary_gap'],
                'scores' => json_encode($result['scores']),
                'answers' => json_encode($answers),
                'completed_at' => time(),
            ])
            ->execute();

        return $result;
    }

    /**
     * Calcula el perfil basado en respuestas.
     */
    protected function calculateProfile(array $answers): array
    {
        // Scoring simplificado por categoría
        $scores = [
            'linkedin' => 0,
            'cv' => 0,
            'search_strategy' => 0,
        ];

        // Calcular scores por categoría (0-100)
        foreach ($answers as $questionId => $answer) {
            $category = $this->getQuestionCategory($questionId);
            if ($category && isset($scores[$category])) {
                $scores[$category] += $this->scoreAnswer($answer);
            }
        }

        // Normalizar scores
        foreach ($scores as $cat => $score) {
            $scores[$cat] = min(100, max(0, $score));
        }

        // Calcular score total
        $totalScore = array_sum($scores) / count($scores);

        // Determinar perfil
        $profileType = match (TRUE) {
            $totalScore < 20 => 'invisible',
            $totalScore < 40 => 'desconectado',
            $totalScore < 60 => 'construccion',
            $totalScore < 80 => 'competitivo',
            default => 'magnetico',
        };

        // Determinar gap principal (el de menor score)
        $primaryGap = array_keys($scores, min($scores))[0] ?? 'all';

        return [
            'profile_type' => $profileType,
            'primary_gap' => $primaryGap,
            'scores' => $scores,
            'total_score' => $totalScore,
        ];
    }

    /**
     * Obtiene categoría de una pregunta.
     */
    protected function getQuestionCategory(string $questionId): ?string
    {
        // Mapeo de IDs de pregunta a categoría
        $mapping = [
            'q_linkedin_profile' => 'linkedin',
            'q_linkedin_connections' => 'linkedin',
            'q_linkedin_activity' => 'linkedin',
            'q_cv_updated' => 'cv',
            'q_cv_format' => 'cv',
            'q_cv_achievements' => 'cv',
            'q_job_search_active' => 'search_strategy',
            'q_job_search_channels' => 'search_strategy',
            'q_job_search_applications' => 'search_strategy',
        ];

        return $mapping[$questionId] ?? NULL;
    }

    /**
     * Puntúa una respuesta.
     */
    protected function scoreAnswer(mixed $answer): int
    {
        if (is_bool($answer)) {
            return $answer ? 20 : 0;
        }
        if (is_numeric($answer)) {
            return min(20, (int) $answer * 4);
        }
        if (is_string($answer)) {
            return match ($answer) {
                'excellent', 'always', 'yes' => 20,
                'good', 'often' => 15,
                'average', 'sometimes' => 10,
                'poor', 'rarely' => 5,
                'none', 'never', 'no' => 0,
                default => 10,
            };
        }
        return 0;
    }

    /**
     * Asegura que existan las tablas necesarias.
     */
    protected function ensureTablesExist(): void
    {
        $schema = $this->database->schema();

        if (!$schema->tableExists('diagnostic_result')) {
            $schema->createTable('diagnostic_result', [
                'fields' => [
                    'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
                    'user_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                    'profile_type' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
                    'primary_gap' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE],
                    'scores' => ['type' => 'text'],
                    'answers' => ['type' => 'text'],
                    'completed_at' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
                ],
                'primary key' => ['id'],
                'unique keys' => ['user_id' => ['user_id']],
                'indexes' => ['profile_type' => ['profile_type']],
            ]);
        }
    }

}
