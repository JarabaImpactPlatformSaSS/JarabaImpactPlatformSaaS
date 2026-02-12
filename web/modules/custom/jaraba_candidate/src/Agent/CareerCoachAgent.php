<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Agent;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_candidate\Service\CandidateProfileService;

/**
 * Career Coach AI Agent for Candidates.
 *
 * Provides personalized career guidance, CV analysis, interview tips,
 * and learning recommendations based on candidate's profile and goals.
 */
class CareerCoachAgent
{

    use StringTranslationTrait;

    /**
     * The profile service.
     */
    protected CandidateProfileService $profileService;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Constructor.
     */
    public function __construct(
        CandidateProfileService $profile_service,
        AccountProxyInterface $current_user
    ) {
        $this->profileService = $profile_service;
        $this->currentUser = $current_user;
    }

    /**
     * Gets agent metadata.
     */
    public function getAgentInfo(): array
    {
        return [
            'id' => 'career_coach',
            'name' => $this->t('Coach de Carrera'),
            'description' => $this->t('Tu asistente personal para impulsar tu carrera profesional'),
            'icon' => '🎯',
            'color' => '#0ea5e9',
            'capabilities' => [
                'cv_analysis' => $this->t('Análisis de CV'),
                'interview_tips' => $this->t('Tips de entrevista'),
                'skill_gaps' => $this->t('Identificar gaps de habilidades'),
                'learning_path' => $this->t('Ruta de formación personalizada'),
                'job_matching' => $this->t('Recomendaciones de empleo'),
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
                'id' => 'analyze_profile',
                'label' => $this->t('Analizar mi perfil'),
                'icon' => '📊',
                'description' => $this->t('Obtén un análisis completo de tu perfil profesional'),
            ],
            [
                'id' => 'improve_cv',
                'label' => $this->t('Mejorar mi CV'),
                'icon' => '📝',
                'description' => $this->t('Sugerencias para optimizar tu currículum'),
            ],
            [
                'id' => 'interview_prep',
                'label' => $this->t('Preparar entrevista'),
                'icon' => '🎤',
                'description' => $this->t('Tips y simulación de entrevista para una oferta'),
            ],
            [
                'id' => 'skill_gaps',
                'label' => $this->t('Detectar gaps'),
                'icon' => '🔍',
                'description' => $this->t('Identifica qué habilidades te faltan para tu objetivo'),
            ],
            [
                'id' => 'suggest_courses',
                'label' => $this->t('Recomendar formación'),
                'icon' => '🎓',
                'description' => $this->t('Cursos personalizados para tu desarrollo'),
            ],
            [
                'id' => 'motivation',
                'label' => $this->t('Motivación'),
                'icon' => '💪',
                'description' => $this->t('Recibe apoyo y motivación en tu búsqueda'),
            ],
        ];
    }

    /**
     * Executes an agent action.
     */
    public function executeAction(string $action_id, array $context = []): array
    {
        $user_id = (int) $this->currentUser->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        $profileData = $profile ? [
            'full_name' => $profile->getFullName(),
            'headline' => $profile->getHeadline(),
            'summary' => $profile->getSummary(),
            'experience_years' => $profile->getExperienceYears(),
            'city' => $profile->getCity(),
        ] : [];

        switch ($action_id) {
            case 'analyze_profile':
                return $this->analyzeProfile($profileData);

            case 'improve_cv':
                return $this->suggestCvImprovements($profileData);

            case 'interview_prep':
                return $this->prepareInterview($profileData, $context['job_id'] ?? NULL);

            case 'skill_gaps':
                return $this->identifySkillGaps($profileData, $context['target_role'] ?? NULL);

            case 'suggest_courses':
                return $this->suggestCourses($profileData);

            case 'motivation':
                return $this->provideMotivation($profileData);

            default:
                return [
                    'success' => FALSE,
                    'message' => $this->t('Acción no reconocida'),
                ];
        }
    }

    /**
     * Analyzes candidate profile.
     */
    protected function analyzeProfile(array $profile): array
    {
        if (empty($profile)) {
            return [
                'success' => TRUE,
                'response_type' => 'guidance',
                'title' => $this->t('¡Empecemos por tu perfil!'),
                'message' => $this->t('Para darte recomendaciones personalizadas, primero necesito conocerte mejor. Completa tu perfil profesional y volveremos a hablar.'),
                'cta' => [
                    'label' => $this->t('Crear mi perfil'),
                    'url' => '/my-profile/edit',
                ],
            ];
        }

        $completeness = $this->calculateProfileCompleteness($profile);

        return [
            'success' => TRUE,
            'response_type' => 'analysis',
            'title' => $this->t('Análisis de tu perfil'),
            'data' => [
                'completeness' => $completeness,
                'strengths' => [
                    $this->t('@years años de experiencia', ['@years' => $profile['experience_years'] ?? 0]),
                ],
                'improvements' => $completeness < 80 ? [
                    $this->t('Añade más detalle a tu resumen profesional'),
                    $this->t('Incluye tus habilidades técnicas'),
                    $this->t('Añade experiencia laboral detallada'),
                ] : [],
            ],
            'message' => $completeness >= 80
                ? $this->t('¡Excelente! Tu perfil está muy completo. Estás listo para destacar ante los empleadores.')
                : $this->t('Tu perfil tiene potencial pero puede mejorar. Completa los campos faltantes para aumentar tu visibilidad.'),
        ];
    }

    /**
     * Suggests CV improvements.
     */
    protected function suggestCvImprovements(array $profile): array
    {
        return [
            'success' => TRUE,
            'response_type' => 'tips',
            'title' => $this->t('Mejoras para tu CV'),
            'tips' => [
                [
                    'icon' => '✨',
                    'title' => $this->t('Titular impactante'),
                    'content' => $this->t('Tu titular actual puede mejorarse. Prueba algo como: "Profesional con @years+ años en [tu sector]"', ['@years' => $profile['experience_years'] ?? 0]),
                ],
                [
                    'icon' => '📊',
                    'title' => $this->t('Logros cuantificables'),
                    'content' => $this->t('Añade números a tus logros: "Aumenté ventas un 25%" es más potente que "Mejoré las ventas"'),
                ],
                [
                    'icon' => '🔑',
                    'title' => $this->t('Palabras clave'),
                    'content' => $this->t('Incluye palabras clave del sector para pasar los filtros de los ATS (sistemas de seguimiento de candidatos)'),
                ],
            ],
        ];
    }

    /**
     * Prepares for interview.
     */
    protected function prepareInterview(array $profile, ?int $jobId): array
    {
        return [
            'success' => TRUE,
            'response_type' => 'interview_prep',
            'title' => $this->t('Preparación para entrevista'),
            'sections' => [
                [
                    'title' => $this->t('Preguntas frecuentes'),
                    'items' => [
                        $this->t('Háblame de ti'),
                        $this->t('¿Cuáles son tus fortalezas?'),
                        $this->t('¿Por qué quieres este puesto?'),
                        $this->t('¿Dónde te ves en 5 años?'),
                    ],
                ],
                [
                    'title' => $this->t('Tips de presentación'),
                    'items' => [
                        $this->t('Investiga la empresa antes de la entrevista'),
                        $this->t('Prepara 3 preguntas para el entrevistador'),
                        $this->t('Llega 10 minutos antes'),
                        $this->t('Vístete acorde a la cultura de la empresa'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Identifies skill gaps.
     */
    protected function identifySkillGaps(array $profile, ?string $targetRole): array
    {
        return [
            'success' => TRUE,
            'response_type' => 'skill_analysis',
            'title' => $this->t('Análisis de habilidades'),
            'message' => $this->t('Para identificar tus gaps, dime qué puesto o sector te interesa y analizaré qué habilidades necesitas desarrollar.'),
            'input_required' => 'target_role',
            'placeholder' => $this->t('Ej: Desarrollador Full Stack, Marketing Digital...'),
        ];
    }

    /**
     * Suggests courses.
     */
    protected function suggestCourses(array $profile): array
    {
        return [
            'success' => TRUE,
            'response_type' => 'courses',
            'title' => $this->t('Formación recomendada'),
            'message' => $this->t('Basándome en tu perfil y las tendencias del mercado, te recomiendo estos cursos:'),
            'courses' => [
                [
                    'title' => $this->t('Habilidades digitales esenciales'),
                    'duration' => '4h',
                    'level' => $this->t('Básico'),
                    'url' => '/courses',
                ],
                [
                    'title' => $this->t('Comunicación efectiva'),
                    'duration' => '6h',
                    'level' => $this->t('Intermedio'),
                    'url' => '/courses',
                ],
            ],
            'cta' => [
                'label' => $this->t('Ver todos los cursos'),
                'url' => '/courses',
            ],
        ];
    }

    /**
     * Provides motivation.
     */
    protected function provideMotivation(array $profile): array
    {
        $name = $profile['full_name'] ?? $this->t('amigo/a');

        $messages = [
            $this->t('¡@name, cada paso que das te acerca a tu objetivo! La constancia es la clave del éxito.', ['@name' => $name]),
            $this->t('Recuerda: cada "no" te acerca al "sí" que necesitas. ¡No te rindas, @name!', ['@name' => $name]),
            $this->t('@name, tu experiencia y habilidades son valiosas. El trabajo ideal está buscándote tanto como tú a él.', ['@name' => $name]),
        ];

        return [
            'success' => TRUE,
            'response_type' => 'motivation',
            'title' => $this->t('💪 ¡Tú puedes!'),
            'message' => $messages[array_rand($messages)],
            'tips' => [
                $this->t('Dedica 30 minutos al día a buscar ofertas'),
                $this->t('Actualiza tu perfil semanalmente'),
                $this->t('Celebra cada pequeño avance'),
            ],
        ];
    }
    /**
     * Calculates profile completeness.
     */
    protected function calculateProfileCompleteness(array $profile): int
    {
        $fields = ['full_name', 'headline', 'summary', 'experience_years', 'city'];
        $completed = 0;

        foreach ($fields as $field) {
            if (!empty($profile[$field])) {
                $completed++;
            }
        }

        return (int) (($completed / count($fields)) * 100);
    }

    /**
     * Detects specific skill/profile gaps based on Lucía Framework.
     *
     * Maps gaps to recommended itineraries:
     * - Gap: LinkedIn → "Tu Marca Personal Digital"
     * - Gap: CV Performance → "Impacto y Filtros ATS"
     * - Gap: Digital Fluency → "Herramientas de Colaboración en la Nube"
     * - Gap: Advanced IA → "Productividad con IA Generativa"
     *
     * @return array
     *   Array of detected gaps with priority.
     */
    public function detectGaps(): array
    {
        $user_id = (int) $this->currentUser->id();
        $profile = $this->profileService->getProfileByUserId($user_id);
        $gaps = [];

        if (!$profile) {
            return [
                ['id' => 'no_profile', 'name' => $this->t('Perfil inexistente'), 'priority' => 1, 'icon' => '👤'],
            ];
        }

        // Check LinkedIn presence
        $linkedinUrl = $profile->get('linkedin_url')->value ?? '';
        if (empty($linkedinUrl)) {
            $gaps[] = [
                'id' => 'linkedin',
                'name' => $this->t('Presencia en LinkedIn'),
                'priority' => 1,
                'icon' => '🔗',
                'description' => $this->t('Tu perfil profesional no está conectado con LinkedIn. El 87% de las ofertas nunca se publican - circulan en redes profesionales.'),
            ];
        }

        // Check headline quality
        $headline = $profile->getHeadline() ?? '';
        if (empty($headline) || strlen($headline) < 30) {
            $gaps[] = [
                'id' => 'headline',
                'name' => $this->t('Titular profesional'),
                'priority' => 2,
                'icon' => '✏️',
                'description' => $this->t('Tu titular profesional está vacío o es muy corto. Es lo primero que ven los reclutadores.'),
            ];
        }

        // Check summary/about
        $summary = $profile->getSummary() ?? '';
        if (empty($summary) || strlen($summary) < 100) {
            $gaps[] = [
                'id' => 'cv_summary',
                'name' => $this->t('Resumen profesional'),
                'priority' => 2,
                'icon' => '📝',
                'description' => $this->t('Tu resumen profesional necesita más detalle para mostrar tu propuesta de valor única.'),
            ];
        }

        // Check completion percent for digital skills indicator
        $completionPercent = $profile->getCompletionPercent();
        if ($completionPercent < 70) {
            $gaps[] = [
                'id' => 'digital_fluency',
                'name' => $this->t('Fluidez digital'),
                'priority' => 3,
                'icon' => '☁️',
                'description' => $this->t('Un perfil más completo demuestra dominio de herramientas digitales. Completa todas las secciones.'),
            ];
        }

        // Check diagnostic gaps if available
        $diagnosticGapsJson = $profile->get('diagnostic_gaps')->value ?? '';
        if (!empty($diagnosticGapsJson)) {
            $diagnosticGaps = json_decode($diagnosticGapsJson, TRUE);
            if (is_array($diagnosticGaps) && in_array('ia', $diagnosticGaps)) {
                $gaps[] = [
                    'id' => 'ai_literacy',
                    'name' => $this->t('Competencia en IA'),
                    'priority' => 4,
                    'icon' => '🤖',
                    'description' => $this->t('Las habilidades en IA Generativa son el diferenciador clave en 2026.'),
                ];
            }
        } else {
            // Default: AI gap for profiles without diagnostic data
            $gaps[] = [
                'id' => 'ai_literacy',
                'name' => $this->t('Competencia en IA'),
                'priority' => 4,
                'icon' => '🤖',
                'description' => $this->t('Las habilidades en IA Generativa son el diferenciador clave en 2026. Añádelas a tu perfil.'),
            ];
        }

        // Sort by priority
        usort($gaps, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $gaps;
    }

    /**
     * Gets personalized itineraries based on detected gaps.
     *
     * @param array $gaps
     *   Array of detected gaps.
     *
     * @return array
     *   Array of recommended itineraries with courses.
     */
    public function getItinerariesForGaps(array $gaps): array
    {
        $itineraries = [];

        $itineraryDefinitions = [
            'linkedin' => [
                'id' => 'marca_personal',
                'name' => $this->t('Tu Marca Personal Digital'),
                'icon' => '🌟',
                'color' => '#0077b5',
                'duration' => '4h',
                'steps' => [
                    $this->t('Optimiza tu foto y banner de LinkedIn'),
                    $this->t('Crea un titular que destaque tu propuesta de valor'),
                    $this->t('Escribe un "Acerca de" que cuente tu historia profesional'),
                    $this->t('Solicita recomendaciones de colegas'),
                ],
                'course' => [
                    'title' => $this->t('LinkedIn para Profesionales +40'),
                    'duration' => '4h',
                    'url' => '/courses',
                ],
            ],
            'headline' => [
                'id' => 'marca_personal',
                'name' => $this->t('Tu Marca Personal Digital'),
                'icon' => '🌟',
                'color' => '#0077b5',
                'duration' => '2h',
                'steps' => [
                    $this->t('Define tu propuesta de valor única'),
                    $this->t('Investiga titulares de profesionales exitosos en tu sector'),
                    $this->t('Crea 3 versiones y pide feedback'),
                ],
                'course' => [
                    'title' => $this->t('Personal Branding Masterclass'),
                    'duration' => '2h',
                    'url' => '/courses',
                ],
            ],
            'cv_summary' => [
                'id' => 'filtros_ats',
                'name' => $this->t('Impacto y Filtros ATS'),
                'icon' => '🎯',
                'color' => '#10b981',
                'duration' => '3h',
                'steps' => [
                    $this->t('Analiza tu CV actual con nuestra herramienta IA'),
                    $this->t('Añade palabras clave de tu sector'),
                    $this->t('Cuantifica tus logros (números, porcentajes)'),
                    $this->t('Adapta el CV a cada oferta objetivo'),
                ],
                'course' => [
                    'title' => $this->t('CV que Pasan los Filtros ATS'),
                    'duration' => '3h',
                    'url' => '/courses',
                ],
            ],
            'digital_fluency' => [
                'id' => 'colaboracion_nube',
                'name' => $this->t('Herramientas de Colaboración en la Nube'),
                'icon' => '☁️',
                'color' => '#3b82f6',
                'duration' => '6h',
                'steps' => [
                    $this->t('Domina Google Workspace o Microsoft 365'),
                    $this->t('Aprende a colaborar en documentos en tiempo real'),
                    $this->t('Gestiona proyectos con herramientas como Trello o Asana'),
                    $this->t('Comunícate eficazmente en equipos remotos'),
                ],
                'course' => [
                    'title' => $this->t('Productividad en la Nube para Profesionales'),
                    'duration' => '6h',
                    'url' => '/courses',
                ],
            ],
            'ai_literacy' => [
                'id' => 'ia_generativa',
                'name' => $this->t('Productividad con IA Generativa'),
                'icon' => '🤖',
                'color' => '#8b5cf6',
                'duration' => '4h',
                'steps' => [
                    $this->t('Entiende qué es la IA Generativa y cómo usarla'),
                    $this->t('Aprende a escribir prompts efectivos'),
                    $this->t('Automatiza tareas repetitivas con IA'),
                    $this->t('Evalúa críticamente las respuestas de la IA'),
                ],
                'course' => [
                    'title' => $this->t('IA Generativa para el Profesional Moderno'),
                    'duration' => '4h',
                    'url' => '/courses',
                ],
            ],
            'no_profile' => [
                'id' => 'crear_perfil',
                'name' => $this->t('Crea tu Perfil Profesional'),
                'icon' => '👤',
                'color' => '#f59e0b',
                'duration' => '30min',
                'steps' => [
                    $this->t('Completa tus datos básicos'),
                    $this->t('Añade tu experiencia profesional'),
                    $this->t('Describe tus habilidades clave'),
                ],
                'course' => NULL,
            ],
        ];

        foreach ($gaps as $gap) {
            $gapId = $gap['id'];
            if (isset($itineraryDefinitions[$gapId])) {
                $itinerary = $itineraryDefinitions[$gapId];
                $itinerary['gap'] = $gap;
                // Avoid duplicates by checking ID
                $exists = array_filter($itineraries, fn($i) => $i['id'] === $itinerary['id']);
                if (empty($exists)) {
                    $itineraries[] = $itinerary;
                }
            }
        }

        return array_slice($itineraries, 0, 3); // Max 3 itineraries
    }

    /**
     * Gets suggested products/courses based on phase and gaps.
     *
     * @param int $phase
     *   Current career phase (1-5).
     * @param array $gaps
     *   Detected gaps.
     *
     * @return array
     *   Array of recommended products.
     */
    public function getSuggestedProducts(int $phase, array $gaps): array
    {
        $products = [];

        // Base products by phase
        $phaseProducts = [
            1 => [
                [
                    'title' => $this->t('Habilidades Digitales Esenciales'),
                    'description' => $this->t('El fundamento para profesionales +40 que quieren destacar en el mundo digital'),
                    'duration' => '4h',
                    'price' => 'Gratis',
                    'icon' => '🎓',
                    'url' => '/courses',
                    'highlight' => TRUE,
                ],
            ],
            2 => [
                [
                    'title' => $this->t('CV Builder con IA'),
                    'description' => $this->t('Crea un CV optimizado para filtros ATS en minutos'),
                    'duration' => '2h',
                    'price' => '€29',
                    'icon' => '📄',
                    'url' => '/courses',
                    'highlight' => TRUE,
                ],
            ],
            3 => [
                [
                    'title' => $this->t('Certificación en Herramientas Cloud'),
                    'description' => $this->t('Domina Google Workspace y Microsoft 365'),
                    'duration' => '8h',
                    'price' => '€49',
                    'icon' => '☁️',
                    'url' => '/courses',
                    'highlight' => TRUE,
                ],
            ],
            4 => [
                [
                    'title' => $this->t('Simulador de Entrevistas IA'),
                    'description' => $this->t('Practica con preguntas personalizadas para tu sector'),
                    'duration' => 'Ilimitado',
                    'price' => '€19/mes',
                    'icon' => '🎤',
                    'url' => '/courses',
                    'highlight' => TRUE,
                ],
            ],
            5 => [
                [
                    'title' => $this->t('Mentoría 1:1 con Expertos'),
                    'description' => $this->t('Sesiones personalizadas con profesionales senior de tu sector'),
                    'duration' => '1h/semana',
                    'price' => '€99/mes',
                    'icon' => '💎',
                    'url' => '/premium/mentorship',
                    'highlight' => TRUE,
                ],
            ],
        ];

        $products = $phaseProducts[$phase] ?? [];

        // Add gap-specific products
        foreach ($gaps as $gap) {
            if ($gap['id'] === 'ai_literacy') {
                $products[] = [
                    'title' => $this->t('IA Generativa para Profesionales'),
                    'description' => $this->t('El curso más demandado de 2026'),
                    'duration' => '4h',
                    'price' => '€39',
                    'icon' => '🤖',
                    'url' => '/courses',
                    'highlight' => FALSE,
                ];
            }
        }

        return array_slice($products, 0, 2); // Max 2 products
    }

    /**
     * Generates a natural language tutor message based on profile and phase.
     *
     * @param string $userName
     *   User's first name.
     * @param int $phase
     *   Current career phase (1-5).
     * @param int $completeness
     *   Profile completeness percentage.
     * @param array $gaps
     *   Detected gaps.
     *
     * @return string
     *   Natural language message from the career coach.
     */
    public function getTutorMessage(string $userName, int $phase, int $completeness, array $gaps): string
    {
        $gapCount = count($gaps);
        $firstName = explode(' ', $userName)[0] ?? $userName;

        $messages = [
            1 => $this->t('@name, entiendo que dar el primer paso puede parecer abrumador. Pero créeme: cada profesional que hoy destaca empezó exactamente donde tú estás ahora. 

Lo que veo en tu situación es que tu perfil profesional está prácticamente invisible para los reclutadores. ¿Sabías que el 87% de las ofertas nunca se publican? Circulan en redes profesionales, y necesitas estar ahí.

He preparado un itinerario personalizado para ti. No tienes que hacerlo todo hoy - empezaremos con lo más impactante primero.', ['@name' => $firstName]),

            2 => $this->t('@name, veo que ya has dado los primeros pasos - ¡eso es fantástico! Tu perfil está al @pct%, lo cual significa que estás en el camino correcto.

Sin embargo, he detectado @count áreas donde podemos mejorar significativamente tu visibilidad. Los perfiles con menos del 50% de completitud reciben 5 veces menos visitas de reclutadores.

Vamos a trabajar juntos en los puntos que más impacto tendrán en tu empleabilidad.', ['@name' => $firstName, '@pct' => $completeness, '@count' => $gapCount]),

            3 => $this->t('¡Vas muy bien, @name! Con un perfil al @pct%, ya estás por encima de la media. Ahora es el momento de diferenciarte.

He analizado tu perfil y veo oportunidades claras de mejora. Los candidatos que completan certificaciones digitales reciben 3x más respuestas a sus candidaturas.

Te propongo un plan de acción específico basado en las tendencias actuales del mercado laboral.', ['@name' => $firstName, '@pct' => $completeness]),

            4 => $this->t('@name, tu perfil está listo para competir en las grandes ligas. Con un @pct% de completitud, ya estás captando la atención de reclutadores.

Ahora necesitamos pulir los detalles que marcan la diferencia: optimizar tu CV para los filtros ATS, prepararte para entrevistas de alto nivel, y posicionarte para roles mejor remunerados.

Hagamos que cada aplicación cuente.', ['@name' => $firstName, '@pct' => $completeness]),

            5 => $this->t('¡Enhorabuena, @name! Tu perfil está en el top 10% de candidatos. Eres lo que llamamos un perfil "magnético" - los reclutadores te buscan a ti.

En esta fase, nos centramos en crecimiento salarial, especialización premium y construir tu marca personal como referente en tu sector. 

¿Listo para el siguiente nivel?', ['@name' => $firstName]),
        ];

        $message = $messages[$phase] ?? $messages[1];
        return (string) $message;
    }


    /**
     * Diagnoses user's career stage using the Lucía Framework (5 phases).
     *
     * Based on 2025-2026 best practices for career coaching AI:
     * - Personalized assessment based on profile completeness
     * - Contextual nudges that address specific gaps
     * - Learning paths aligned with career goals
     * - Emphasis on digital fluency for 45+ professionals
     *
     * @return array
     *   Structured diagnosis with phase, nudge, itinerary, and next action.
     */
    public function diagnoseCareerStage(): array
    {
        $user_id = (int) $this->currentUser->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        // Calculate key metrics
        $completeness = 0;
        $hasLinkedIn = FALSE;
        $hasHeadline = FALSE;
        $experienceYears = 0;
        $userName = $this->t('profesional');

        if ($profile) {
            $profileData = [
                'full_name' => $profile->getFullName(),
                'headline' => $profile->getHeadline(),
                'summary' => $profile->getSummary(),
                'experience_years' => $profile->getExperienceYears(),
                'city' => $profile->getCity(),
            ];
            $completeness = $this->calculateProfileCompleteness($profileData);
            $hasLinkedIn = !empty($profile->get('linkedin_url')->value ?? NULL);
            $hasHeadline = !empty($profileData['headline']);
            $experienceYears = (int) ($profileData['experience_years'] ?? 0);
            $userName = $profileData['full_name'] ?: $userName;
        }

        // Determine phase based on Lucía Framework
        if ($completeness < 20 && !$hasLinkedIn) {
            // Phase 1: Invisible
            return [
                'phase' => 1,
                'phase_name' => 'Invisible',
                'phase_emoji' => '👻',
                'completeness' => $completeness,
                'nudge' => $this->t('@name, tu perfil profesional está prácticamente invisible. Los reclutadores no pueden encontrarte. ¿Sabías que el 87% de las ofertas nunca se publican? Se comparten en redes profesionales donde tú no estás.', ['@name' => $userName]),
                'itinerary' => [
                    'name' => $this->t('Tu Marca Personal Digital'),
                    'steps' => [
                        $this->t('Crear un perfil profesional completo'),
                        $this->t('Añadir foto profesional'),
                        $this->t('Definir tu titular (headline) impactante'),
                    ],
                ],
                'next_action' => [
                    'label' => $this->t('Completar mi perfil'),
                    'url' => '/my-profile/edit',
                    'icon' => '✏️',
                ],
                'motivation' => $this->t('El primer paso es el más importante. ¡Vamos a hacerlo juntos!'),
            ];
        } elseif ($completeness < 50) {
            // Phase 2: Disconnected
            return [
                'phase' => 2,
                'phase_name' => 'Desconectado',
                'phase_emoji' => '📡',
                'completeness' => $completeness,
                'nudge' => $this->t('@name, tienes un perfil pero está incompleto. Los perfiles con menos del 50%% de completitud reciben 5 veces menos visitas de reclutadores.', ['@name' => $userName]),
                'itinerary' => [
                    'name' => $this->t('Activación de Señal'),
                    'steps' => [
                        $this->t('Completar resumen profesional'),
                        $this->t('Añadir experiencia laboral'),
                        $this->t('Incluir habilidades clave'),
                    ],
                ],
                'next_action' => [
                    'label' => $this->t('Mejorar mi perfil'),
                    'url' => '/my-profile/edit',
                    'icon' => '📊',
                ],
                'motivation' => $this->t('Estás en el camino correcto. Cada campo que completes aumenta tu visibilidad.'),
            ];
        } elseif ($completeness < 75) {
            // Phase 3: Building
            return [
                'phase' => 3,
                'phase_name' => 'Construyendo',
                'phase_emoji' => '🏗️',
                'completeness' => $completeness,
                'nudge' => $this->t('¡Vas muy bien, @name! Tu perfil está al @pct%%. Ahora es el momento de diferenciarte con formación y certificaciones.', ['@name' => $userName, '@pct' => $completeness]),
                'itinerary' => [
                    'name' => $this->t('Credenciales Verificables'),
                    'steps' => [
                        $this->t('Completar un curso de habilidades digitales'),
                        $this->t('Obtener tu primera certificación'),
                        $this->t('Actualizar CV con nuevas competencias'),
                    ],
                ],
                'next_action' => [
                    'label' => $this->t('Ver cursos recomendados'),
                    'url' => '/courses',
                    'icon' => '🎓',
                ],
                'motivation' => $this->t('Los candidatos con certificaciones reciben 3x más respuestas a sus candidaturas.'),
            ];
        } elseif ($completeness < 90) {
            // Phase 4: Competitive
            return [
                'phase' => 4,
                'phase_name' => 'Competitivo',
                'phase_emoji' => '🏆',
                'completeness' => $completeness,
                'nudge' => $this->t('@name, tu perfil está casi listo para destacar. Ahora enfoquémonos en optimizar tu CV para los filtros ATS y preparar entrevistas.', ['@name' => $userName]),
                'itinerary' => [
                    'name' => $this->t('Impacto y Filtros ATS'),
                    'steps' => [
                        $this->t('Analizar compatibilidad CV-oferta'),
                        $this->t('Practicar entrevistas con IA'),
                        $this->t('Aplicar a ofertas recomendadas'),
                    ],
                ],
                'next_action' => [
                    'label' => $this->t('Preparar entrevista'),
                    'url' => '#interview_prep',
                    'icon' => '🎤',
                ],
                'motivation' => $this->t('Estás en el top 20%% de candidatos. ¡El siguiente paso es conseguir esa entrevista!'),
            ];
        } else {
            // Phase 5: Magnetic
            return [
                'phase' => 5,
                'phase_name' => 'Magnético',
                'phase_emoji' => '🧲',
                'completeness' => $completeness,
                'nudge' => $this->t('¡Felicidades, @name! Tu perfil es de élite. Ahora enfoquémonos en crecimiento salarial y especialización premium.', ['@name' => $userName]),
                'itinerary' => [
                    'name' => $this->t('Crecimiento y Especialización'),
                    'steps' => [
                        $this->t('Explorar ofertas premium'),
                        $this->t('Networking con líderes del sector'),
                        $this->t('Mentoría y desarrollo avanzado'),
                    ],
                ],
                'next_action' => [
                    'label' => $this->t('Ver ofertas premium'),
                    'url' => '/jobs?type=premium',
                    'icon' => '💎',
                ],
                'motivation' => $this->t('Los profesionales magnéticos no buscan trabajo, el trabajo los encuentra a ellos.'),
            ];
        }
    }

    /**
     * Gets a soft suggestion for upselling (max 1 per session).
     *
     * Based on 2025-2026 best practices:
     * - Never interrupt active conversations
     * - Contextual and helpful, not pushy
     * - Clearly linked to user's goals
     *
     * @param array $context
     *   Current page/session context.
     *
     * @return array|null
     *   Soft suggestion or NULL if not appropriate.
     */
    public function getSoftSuggestion(array $context = []): ?array
    {
        $diagnosis = $this->diagnoseCareerStage();
        $phase = $diagnosis['phase'];

        // Only suggest upgrades to users in phases 3-5 (actively engaged)
        if ($phase < 3) {
            return NULL;
        }

        // Training suggestions based on phase
        $suggestions = [
            3 => [
                'suggestion_type' => 'training',
                'icon' => '🎓',
                'message' => $this->t('¿Sabías que los candidatos con certificaciones reciben 3x más contactos? Te recomiendo este curso de 4h:'),
                'cta' => [
                    'label' => $this->t('Habilidades Digitales Esenciales'),
                    'url' => '/courses',
                ],
            ],
            4 => [
                'suggestion_type' => 'feature',
                'icon' => '✨',
                'message' => $this->t('Tu perfil está listo para el siguiente nivel. Con el plan Profesional podrías:'),
                'benefits' => [
                    $this->t('Análisis de CV con IA avanzada'),
                    $this->t('Simulación de entrevistas ilimitada'),
                    $this->t('Alertas de ofertas prioritarias'),
                ],
                'cta' => [
                    'label' => $this->t('Explorar plan Profesional'),
                    'url' => '/pricing',
                ],
            ],
            5 => [
                'suggestion_type' => 'premium',
                'icon' => '💎',
                'message' => $this->t('Como profesional magnético, tienes acceso a oportunidades exclusivas:'),
                'cta' => [
                    'label' => $this->t('Mentoría 1:1 con expertos'),
                    'url' => '/premium/mentorship',
                ],
            ],
        ];

        return $suggestions[$phase] ?? NULL;
    }

    /**
     * Generates the onboarding welcome message.
     *
     * This is shown when the FAB panel opens for the first time in a session.
     *
     * @return array
     *   Onboarding message with diagnosis summary.
     */
    public function getOnboardingMessage(): array
    {
        $diagnosis = $this->diagnoseCareerStage();
        $user_id = (int) $this->currentUser->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        $userName = $profile ? ($profile->getFullName() ?: $this->currentUser->getDisplayName()) : $this->currentUser->getDisplayName();
        $nameParts = explode(' ', $userName);
        $firstName = $nameParts[0] ?? $userName;

        return [
            'message_type' => 'onboarding',
            'greeting' => $this->t('¡Hola, @name!', ['@name' => $firstName]),
            'phase_indicator' => [
                'phase' => $diagnosis['phase'],
                'name' => $diagnosis['phase_name'],
                'emoji' => $diagnosis['phase_emoji'],
                'completeness' => $diagnosis['completeness'],
            ],
            'main_message' => $diagnosis['nudge'],
            'itinerary' => $diagnosis['itinerary'],
            'primary_action' => $diagnosis['next_action'],
            'motivation' => $diagnosis['motivation'],
            'follow_up' => $this->t('¿Te gustaría que te ayude a avanzar al siguiente nivel?'),
        ];
    }

}
