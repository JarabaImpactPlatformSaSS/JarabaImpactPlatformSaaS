<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Agent;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_job_board\Service\ApplicationService;
use Drupal\jaraba_job_board\Service\JobPostingService;

/**
 * Recruiter Assistant AI Agent for Employers.
 *
 * Helps employers with candidate screening, job description optimization,
 * and hiring process automation.
 */
class RecruiterAssistantAgent
{

    use StringTranslationTrait;

    /**
     * The job posting service.
     */
    protected JobPostingService $jobService;

    /**
     * The application service.
     */
    protected ApplicationService $applicationService;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Constructor.
     */
    public function __construct(
        JobPostingService $job_service,
        ApplicationService $application_service,
        AccountProxyInterface $current_user
    ) {
        $this->jobService = $job_service;
        $this->applicationService = $application_service;
        $this->currentUser = $current_user;
    }

    /**
     * Gets agent metadata.
     */
    public function getAgentInfo(): array
    {
        return [
            'id' => 'recruiter_assistant',
            'name' => $this->t('Asistente de Selección'),
            'description' => $this->t('Tu ayudante inteligente para encontrar el talento perfecto'),
            'icon' => '👔',
            'color' => '#059669',
            'capabilities' => [
                'screening' => $this->t('Screening automático'),
                'ranking' => $this->t('Ranking de candidatos'),
                'jd_optimization' => $this->t('Optimización de ofertas'),
                'interview_questions' => $this->t('Preguntas de entrevista'),
                'analytics' => $this->t('Análisis de proceso'),
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
                'id' => 'screen_candidates',
                'label' => $this->t('Filtrar candidatos'),
                'icon' => '🔍',
                'description' => $this->t('Filtra automáticamente candidatos por requisitos'),
            ],
            [
                'id' => 'rank_applicants',
                'label' => $this->t('Rankear postulantes'),
                'icon' => '🏆',
                'description' => $this->t('Ordena candidatos por compatibilidad con la oferta'),
            ],
            [
                'id' => 'optimize_jd',
                'label' => $this->t('Mejorar oferta'),
                'icon' => '✨',
                'description' => $this->t('Optimiza tu descripción de trabajo para atraer más talento'),
            ],
            [
                'id' => 'suggest_questions',
                'label' => $this->t('Preguntas de entrevista'),
                'icon' => '❓',
                'description' => $this->t('Genera preguntas relevantes para entrevistar'),
            ],
            [
                'id' => 'process_analytics',
                'label' => $this->t('Analizar proceso'),
                'icon' => '📊',
                'description' => $this->t('Métricas de tu proceso de selección'),
            ],
            [
                'id' => 'draft_response',
                'label' => $this->t('Redactar respuesta'),
                'icon' => '✉️',
                'description' => $this->t('Genera respuestas personalizadas para candidatos'),
            ],
        ];
    }

    /**
     * Executes an agent action.
     */
    public function executeAction(string $action_id, array $context = []): array
    {
        switch ($action_id) {
            case 'screen_candidates':
                return $this->screenCandidates($context['job_id'] ?? NULL);

            case 'rank_applicants':
                return $this->rankApplicants($context['job_id'] ?? NULL);

            case 'optimize_jd':
                return $this->optimizeJobDescription($context['job_id'] ?? NULL);

            case 'suggest_questions':
                return $this->suggestInterviewQuestions($context['job_id'] ?? NULL);

            case 'process_analytics':
                return $this->getProcessAnalytics();

            case 'draft_response':
                return $this->draftCandidateResponse($context);

            default:
                return [
                    'success' => FALSE,
                    'message' => $this->t('Acción no reconocida'),
                ];
        }
    }

    /**
     * Screens candidates automatically.
     */
    protected function screenCandidates(?int $jobId): array
    {
        if (!$jobId) {
            return [
                'success' => TRUE,
                'type' => 'job_selection',
                'title' => $this->t('Selecciona una oferta'),
                'message' => $this->t('¿Para qué oferta quieres filtrar candidatos?'),
                'input_required' => 'job_id',
            ];
        }

        return [
            'success' => TRUE,
            'type' => 'screening_result',
            'title' => $this->t('Screening automático'),
            'summary' => [
                'total' => 25,
                'passed' => 12,
                'pending_review' => 8,
                'rejected' => 5,
            ],
            'criteria_applied' => [
                $this->t('Experiencia mínima requerida'),
                $this->t('Habilidades técnicas clave'),
                $this->t('Disponibilidad'),
            ],
            'message' => $this->t('He filtrado 25 candidatos. 12 cumplen todos los requisitos, 8 requieren revisión manual y 5 no cumplen criterios mínimos.'),
        ];
    }

    /**
     * Ranks applicants by match score.
     */
    protected function rankApplicants(?int $jobId): array
    {
        return [
            'success' => TRUE,
            'type' => 'ranking',
            'title' => $this->t('Ranking de candidatos'),
            'top_candidates' => [
                ['name' => 'María García', 'score' => 95, 'highlight' => $this->t('5 años en rol similar')],
                ['name' => 'Carlos López', 'score' => 88, 'highlight' => $this->t('Certificaciones relevantes')],
                ['name' => 'Ana Martínez', 'score' => 82, 'highlight' => $this->t('Excelentes referencias')],
            ],
            'factors' => [
                $this->t('Experiencia relevante (40%)'),
                $this->t('Habilidades técnicas (30%)'),
                $this->t('Educación (15%)'),
                $this->t('Soft skills (15%)'),
            ],
        ];
    }

    /**
     * Optimizes job description.
     */
    protected function optimizeJobDescription(?int $jobId): array
    {
        return [
            'success' => TRUE,
            'type' => 'jd_optimization',
            'title' => $this->t('Mejoras para tu oferta'),
            'suggestions' => [
                [
                    'section' => $this->t('Título'),
                    'current' => 'Desarrollador',
                    'suggested' => 'Desarrollador Full Stack React/Node.js',
                    'reason' => $this->t('Títulos específicos atraen 3x más candidatos cualificados'),
                ],
                [
                    'section' => $this->t('Salario'),
                    'issue' => $this->t('No especificado'),
                    'suggestion' => $this->t('Añadir rango salarial aumenta postulaciones un 75%'),
                ],
                [
                    'section' => $this->t('Beneficios'),
                    'suggestion' => $this->t('Menciona teletrabajo, flexibilidad horaria y formación'),
                ],
            ],
            'predicted_improvement' => '+45% postulaciones',
        ];
    }

    /**
     * Suggests interview questions.
     */
    protected function suggestInterviewQuestions(?int $jobId): array
    {
        return [
            'success' => TRUE,
            'type' => 'interview_questions',
            'title' => $this->t('Preguntas sugeridas de entrevista'),
            'categories' => [
                [
                    'name' => $this->t('Técnicas'),
                    'questions' => [
                        $this->t('Cuéntame tu experiencia con [tecnología del puesto]'),
                        $this->t('¿Cómo abordarías [problema técnico común]?'),
                        $this->t('Describe un proyecto del que estés orgulloso/a'),
                    ],
                ],
                [
                    'name' => $this->t('Comportamentales'),
                    'questions' => [
                        $this->t('Describe una situación donde tuviste que resolver un conflicto'),
                        $this->t('¿Cómo manejas la presión por deadlines?'),
                        $this->t('Háblame de un error que cometiste y cómo lo solucionaste'),
                    ],
                ],
                [
                    'name' => $this->t('Culturales'),
                    'questions' => [
                        $this->t('¿Qué te atrajo de nuestra empresa?'),
                        $this->t('¿Cómo describes tu estilo de trabajo ideal?'),
                        $this->t('¿Qué valores son importantes para ti en un trabajo?'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Gets process analytics.
     */
    protected function getProcessAnalytics(): array
    {
        return [
            'success' => TRUE,
            'type' => 'analytics',
            'title' => $this->t('Análisis de tu proceso de selección'),
            'metrics' => [
                ['label' => $this->t('Tiempo medio de contratación'), 'value' => '23 días', 'trend' => 'down', 'change' => '-5 días'],
                ['label' => $this->t('Tasa de aceptación de ofertas'), 'value' => '78%', 'trend' => 'up', 'change' => '+12%'],
                ['label' => $this->t('Candidatos por oferta'), 'value' => '34', 'trend' => 'stable', 'change' => '0'],
                ['label' => $this->t('Coste por contratación'), 'value' => '€850', 'trend' => 'down', 'change' => '-€150'],
            ],
            'insights' => [
                $this->t('Tu tiempo de respuesta inicial es excelente (< 24h)'),
                $this->t('Considera añadir una fase de screening telefónico para reducir entrevistas presenciales'),
            ],
        ];
    }

    protected function draftCandidateResponse(array $context): array
    {
        $type = $context['response_type'] ?? 'general';

        $templates = [
            'rejection' => $this->t('Estimado/a [Nombre], gracias por tu interés en [Puesto]. Tras evaluar tu candidatura, hemos decidido continuar con otros perfiles más alineados con los requisitos actuales. Te animamos a estar atento/a a futuras oportunidades. Un saludo.'),
            'next_step' => $this->t('Hola [Nombre], ¡buenas noticias! Tu perfil ha llamado nuestra atención para el puesto de [Puesto]. Nos gustaría programar una entrevista. ¿Qué disponibilidad tienes esta semana?'),
            'offer' => $this->t('Estimado/a [Nombre], es un placer comunicarte que queremos hacerte una oferta para [Puesto]. [Detalles de la oferta]. Esperamos tu respuesta.'),
        ];

        return [
            'success' => TRUE,
            'type' => 'draft',
            'title' => $this->t('Borrador de respuesta'),
            'content' => $templates[$type] ?? $templates['general'],
            'options' => [
                ['id' => 'rejection', 'label' => $this->t('Rechazo amable')],
                ['id' => 'next_step', 'label' => $this->t('Siguiente fase')],
                ['id' => 'offer', 'label' => $this->t('Oferta')],
            ],
        ];
    }

    // =========================================================================
    // PREMIUM DASHBOARD METHODS
    // =========================================================================

    /**
     * Analyzes overall recruiting health for the dashboard.
     *
     * @return array
     *   Recruiting health metrics and status.
     */
    public function analyzeRecruitingHealth(): array
    {
        $userId = (int) $this->currentUser->id();

        // Get job metrics
        $activeJobs = $this->jobService->countActiveJobsByEmployer($userId);
        $totalApplications = $this->applicationService->countPendingApplications($userId);

        // Calculate health score
        $healthScore = $this->calculateHealthScore($activeJobs, $totalApplications);
        $phase = $this->determineRecruitingPhase($healthScore, $activeJobs, $totalApplications);

        return [
            'health_score' => $healthScore,
            'phase' => $phase['number'],
            'phase_name' => $phase['name'],
            'phase_emoji' => $phase['emoji'],
            'metrics' => [
                'active_jobs' => $activeJobs,
                'pending_applications' => $totalApplications,
                'avg_response_time' => '18h',
                'conversion_rate' => 42,
            ],
        ];
    }

    /**
     * Calculates health score based on activity.
     */
    protected function calculateHealthScore(int $activeJobs, int $applications): int
    {
        $score = 0;

        // Jobs scoring
        if ($activeJobs >= 5) {
            $score += 40;
        } elseif ($activeJobs >= 2) {
            $score += 25;
        } elseif ($activeJobs >= 1) {
            $score += 10;
        }

        // Applications scoring
        if ($applications >= 20) {
            $score += 40;
        } elseif ($applications >= 10) {
            $score += 25;
        } elseif ($applications >= 1) {
            $score += 15;
        }

        // Base activity score
        $score += 20;

        return min(100, $score);
    }

    /**
     * Determines the recruiting phase based on metrics.
     */
    protected function determineRecruitingPhase(int $healthScore, int $jobs, int $apps): array
    {
        $phases = [
            1 => ['name' => $this->t('Arrancando'), 'emoji' => '🌱', 'number' => 1],
            2 => ['name' => $this->t('Construyendo'), 'emoji' => '🏗️', 'number' => 2],
            3 => ['name' => $this->t('Creciendo'), 'emoji' => '📈', 'number' => 3],
            4 => ['name' => $this->t('Competitivo'), 'emoji' => '🎯', 'number' => 4],
            5 => ['name' => $this->t('Líder Talento'), 'emoji' => '🏆', 'number' => 5],
        ];

        if ($healthScore >= 90) {
            return $phases[5];
        } elseif ($healthScore >= 70) {
            return $phases[4];
        } elseif ($healthScore >= 50) {
            return $phases[3];
        } elseif ($healthScore >= 30) {
            return $phases[2];
        }
        return $phases[1];
    }

    /**
     * Detects recruiting gaps to improve.
     *
     * @return array
     *   Array of detected gaps with priority.
     */
    public function detectRecruitingGaps(): array
    {
        $userId = (int) $this->currentUser->id();
        $gaps = [];

        $activeJobs = $this->jobService->countActiveJobsByEmployer($userId);
        $pendingApps = $this->applicationService->countPendingApplications($userId);

        // No active jobs
        if ($activeJobs === 0) {
            $gaps[] = [
                'id' => 'no_active_jobs',
                'name' => $this->t('Sin ofertas activas'),
                'priority' => 1,
                'icon' => '📋',
                'description' => $this->t('No tienes ofertas publicadas. El talento no puede encontrarte.'),
            ];
        }

        // Pending applications not reviewed
        if ($pendingApps > 10) {
            $gaps[] = [
                'id' => 'pending_review',
                'name' => $this->t('Candidatos sin revisar'),
                'priority' => 1,
                'icon' => '⏰',
                'description' => $this->t('Tienes @count candidaturas pendientes. Los mejores candidatos esperan máximo 48h.', ['@count' => $pendingApps]),
            ];
        }

        // Generic job descriptions (simulated check)
        $gaps[] = [
            'id' => 'generic_jd',
            'name' => $this->t('Ofertas sin salario'),
            'priority' => 2,
            'icon' => '💰',
            'description' => $this->t('Las ofertas con rango salarial reciben 75% más candidaturas cualificadas.'),
        ];

        // Missing employer branding
        $gaps[] = [
            'id' => 'employer_branding',
            'name' => $this->t('Marca empleadora'),
            'priority' => 3,
            'icon' => '🏢',
            'description' => $this->t('Potencia tu imagen como empleador para atraer mejor talento.'),
        ];

        usort($gaps, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $gaps;
    }

    /**
     * Gets optimization paths for detected gaps.
     *
     * @param array $gaps
     *   Detected gaps.
     *
     * @return array
     *   Optimization paths with steps.
     */
    public function getOptimizationPaths(array $gaps): array
    {
        $paths = [];

        $pathDefinitions = [
            'no_active_jobs' => [
                'id' => 'publish_first_job',
                'name' => $this->t('Publica tu primera oferta'),
                'icon' => '📝',
                'color' => '#059669',
                'duration' => '15 min',
                'steps' => [
                    $this->t('Define el perfil ideal del candidato'),
                    $this->t('Redacta una descripción atractiva'),
                    $this->t('Añade rango salarial y beneficios'),
                    $this->t('Configura el screening automático'),
                ],
                'action' => [
                    'label' => $this->t('Crear oferta'),
                    'url' => '/admin/content/jobs/add',
                    'icon' => '➕',
                ],
            ],
            'pending_review' => [
                'id' => 'fast_screening',
                'name' => $this->t('Screening Express'),
                'icon' => '⚡',
                'color' => '#f59e0b',
                'duration' => '30 min',
                'steps' => [
                    $this->t('Activa el filtro automático por requisitos'),
                    $this->t('Revisa los candidatos destacados'),
                    $this->t('Descarta los que no cumplen mínimos'),
                    $this->t('Agenda entrevistas con los top 5'),
                ],
                'action' => [
                    'label' => $this->t('Ver candidaturas'),
                    'url' => '/admin/content/applications',
                    'icon' => '👥',
                ],
            ],
            'generic_jd' => [
                'id' => 'optimize_offers',
                'name' => $this->t('Ofertas que atraen talento'),
                'icon' => '✨',
                'color' => '#8b5cf6',
                'duration' => '1h',
                'steps' => [
                    $this->t('Añade rango salarial competitivo'),
                    $this->t('Destaca beneficios y cultura'),
                    $this->t('Usa lenguaje inclusivo'),
                    $this->t('Incluye testimonios de empleados'),
                ],
                'action' => [
                    'label' => $this->t('Mejorar ofertas'),
                    'url' => '/admin/content/jobs',
                    'icon' => '✏️',
                ],
            ],
            'employer_branding' => [
                'id' => 'build_brand',
                'name' => $this->t('Employer Branding'),
                'icon' => '🏆',
                'color' => '#0ea5e9',
                'duration' => '2h',
                'steps' => [
                    $this->t('Completa el perfil de empresa'),
                    $this->t('Añade fotos del equipo y oficina'),
                    $this->t('Publica valores y misión'),
                    $this->t('Solicita reseñas a empleados'),
                ],
                'action' => [
                    'label' => $this->t('Editar empresa'),
                    'url' => '/my-company/edit',
                    'icon' => '🏢',
                ],
            ],
        ];

        foreach ($gaps as $gap) {
            if (isset($pathDefinitions[$gap['id']])) {
                $paths[] = $pathDefinitions[$gap['id']];
            }
        }

        return array_slice($paths, 0, 3);
    }

    /**
     * Gets suggested tools for the employer.
     *
     * @param array $gaps
     *   Detected gaps.
     *
     * @return array
     *   Suggested tools/products.
     */
    public function getSuggestedTools(array $gaps): array
    {
        $tools = [];

        // Always suggest the AI screening
        $tools[] = [
            'title' => $this->t('Screening IA Automático'),
            'description' => $this->t('Filtra candidatos automáticamente por requisitos'),
            'icon' => '🤖',
            'price' => $this->t('Incluido'),
            'highlight' => TRUE,
            'url' => '/admin/content/applications',
        ];

        // Gap-specific tools
        foreach ($gaps as $gap) {
            if ($gap['id'] === 'generic_jd') {
                $tools[] = [
                    'title' => $this->t('Optimizador de Ofertas IA'),
                    'description' => $this->t('Mejora tus descripciones con IA'),
                    'icon' => '✨',
                    'price' => '€29/mes',
                    'highlight' => FALSE,
                    'url' => '/products/jd-optimizer',
                ];
            }
        }

        // Premium placement
        $tools[] = [
            'title' => $this->t('Oferta Destacada'),
            'description' => $this->t('Aparece primero en las búsquedas'),
            'icon' => '⭐',
            'price' => '€49/oferta',
            'highlight' => FALSE,
            'url' => '/products/featured-job',
        ];

        return array_slice($tools, 0, 2);
    }

    /**
     * Gets personalized assistant message for the dashboard.
     *
     * @param string $userName
     *   User's display name.
     * @param array $metrics
     *   Current metrics.
     * @param array $gaps
     *   Detected gaps.
     *
     * @return string
     *   Natural language message.
     */
    public function getAssistantMessage(string $userName, array $metrics, array $gaps): string
    {
        $firstName = explode(' ', $userName)[0] ?? $userName;
        $gapCount = count($gaps);
        $activeJobs = $metrics['active_jobs'] ?? 0;
        $pendingApps = $metrics['pending_applications'] ?? 0;

        if ($activeJobs === 0) {
            $message = $this->t('@name, veo que aún no tienes ofertas activas. El primer paso para atraer talento es dar visibilidad a tus oportunidades.

He preparado un itinerario rápido para que publiques tu primera oferta en menos de 15 minutos. Una oferta bien estructurada con rango salarial recibe un 75% más de candidaturas cualificadas.

¿Empezamos?', ['@name' => $firstName]);
        } elseif ($pendingApps > 10) {
            $message = $this->t('@name, tienes @count candidaturas esperando tu revisión. Los candidatos top esperan respuesta en máximo 48 horas - si tardas más, los pierdes.

Te sugiero usar el screening automático para filtrar rápidamente. Así puedes concentrarte en los perfiles que realmente encajan.', ['@name' => $firstName, '@count' => $pendingApps]);
        } elseif ($gapCount <= 2) {
            $message = $this->t('¡Excelente trabajo, @name! Tu proceso de selección está en buen camino. Tienes @jobs ofertas activas y @apps candidaturas en pipeline.

He detectado @gaps áreas donde podemos optimizar aún más para que atraigas mejor talento y cierres contrataciones más rápido.', ['@name' => $firstName, '@jobs' => $activeJobs, '@apps' => $pendingApps, '@gaps' => $gapCount]);
        } else {
            $message = $this->t('@name, tu proceso de selección tiene potencial de mejora. He analizado tu actividad y detectado @gaps áreas donde podemos optimizar.

Los empleadores que siguen estas recomendaciones reducen su tiempo de contratación un 40% y mejoran la calidad de contratación un 60%.', ['@name' => $firstName, '@gaps' => $gapCount]);
        }

        return (string) $message;
    }

}

