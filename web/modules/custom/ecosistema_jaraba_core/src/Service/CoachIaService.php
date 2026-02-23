<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use GuzzleHttp\ClientInterface;

/**
 * Agente Coach IA 24/7 para bienestar del emprendedor/buscador de empleo.
 *
 * PROPÓSITO:
 * Agente de IA que proporciona apoyo emocional y coaching basado en
 * técnicas de CBT (Cognitive Behavioral Therapy) y metodologías de
 * productividad para emprendedores y personas en transición laboral.
 *
 * FUNCIONALIDADES:
 * - Check-ins diarios de bienestar
 * - Técnicas de gestión del estrés
 * - Reencuadre cognitivo de situaciones negativas
 * - Planificación de objetivos SMART
 * - Celebración de logros
 *
 * IMPORTANTE:
 * Este servicio NO proporciona terapia ni asesoramiento psicológico.
 * Es una herramienta de apoyo que recomienda buscar ayuda profesional
 * cuando detecta situaciones de riesgo.
 *
 * BASADO EN:
 * - docs/tecnicos/20260115d-Ecosistema Jaraba_ Estrategia de Verticalización y Precios_Gemini.md
 *
 * @version 1.0.0
 */
class CoachIaService
{

    /**
     * Estados de ánimo registrables.
     */
    public const MOOD_STATES = [
        1 => ['label' => 'Muy bajo', 'emoji' => '😢', 'action' => 'support'],
        2 => ['label' => 'Bajo', 'emoji' => '😔', 'action' => 'encourage'],
        3 => ['label' => 'Neutral', 'emoji' => '😐', 'action' => 'motivate'],
        4 => ['label' => 'Bien', 'emoji' => '🙂', 'action' => 'reinforce'],
        5 => ['label' => 'Muy bien', 'emoji' => '😊', 'action' => 'celebrate'],
    ];

    /**
     * Categorías de situaciones estresantes.
     */
    public const STRESS_CATEGORIES = [
        'rejection' => 'Rechazo o no respuesta',
        'uncertainty' => 'Incertidumbre',
        'overwork' => 'Sobrecarga de trabajo',
        'finances' => 'Preocupaciones económicas',
        'imposter' => 'Síndrome del impostor',
        'comparison' => 'Comparación con otros',
        'loneliness' => 'Soledad profesional',
    ];

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        AccountProxyInterface $current_user,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->currentUser = $current_user;
        $this->logger = $logger_factory->get('jaraba_coach');
    }

    /**
     * Genera un check-in de bienestar.
     *
     * @return array
     *   Preguntas y opciones para el check-in.
     */
    public function generateDailyCheckIn(): array
    {
        $greeting = $this->getTimeBasedGreeting();
        $name = $this->currentUser->getDisplayName();

        return [
            'greeting' => "{$greeting}, {$name}",
            'questions' => [
                [
                    'id' => 'mood',
                    'text' => '¿Cómo te sientes hoy?',
                    'type' => 'mood_scale',
                    'options' => self::MOOD_STATES,
                ],
                [
                    'id' => 'energy',
                    'text' => '¿Cómo está tu nivel de energía?',
                    'type' => 'scale_1_5',
                    'labels' => ['Agotado/a', '', '', '', 'Lleno/a de energía'],
                ],
                [
                    'id' => 'focus_today',
                    'text' => '¿Cuál es tu prioridad hoy?',
                    'type' => 'text',
                    'placeholder' => 'Ej: Enviar 3 candidaturas, terminar propuesta...',
                ],
            ],
        ];
    }

    /**
     * Procesa las respuestas del check-in y genera feedback.
     *
     * @param array $responses
     *   Respuestas del usuario.
     *
     * @return array
     *   Feedback personalizado.
     */
    public function processCheckIn(array $responses): array
    {
        $mood = (int) ($responses['mood'] ?? 3);
        $energy = (int) ($responses['energy'] ?? 3);
        $focus = $responses['focus_today'] ?? '';

        $mood_info = self::MOOD_STATES[$mood] ?? self::MOOD_STATES[3];

        // Generar mensaje de respuesta
        $message = $this->generateMoodResponse($mood, $energy);

        // Detectar si necesita apoyo especial
        $needs_support = $mood <= 2 || $energy <= 2;

        // Sugerir técnica del día
        $technique = $this->suggestTechnique($mood, $energy);

        // Guardar registro (en implementación completa, guardar en BD)
        $this->logger->info('Check-in: mood=@mood, energy=@energy, user=@user', [
            '@mood' => $mood,
            '@energy' => $energy,
            '@user' => $this->currentUser->id(),
        ]);

        return [
            'message' => $message,
            'needs_support' => $needs_support,
            'technique' => $technique,
            'affirmation' => $this->getDailyAffirmation($mood),
            'focus_reminder' => !empty($focus) ? "Tu prioridad de hoy: {$focus}" : NULL,
            'timestamp' => date('c'),
        ];
    }

    /**
     * Genera respuesta basada en estado de ánimo.
     */
    protected function generateMoodResponse(int $mood, int $energy): string
    {
        if ($mood <= 2) {
            $messages = [
                'Entiendo que no es tu mejor día. Eso está bien, todos tenemos días difíciles.',
                'Gracias por compartir cómo te sientes. Reconocer las emociones es el primer paso.',
                'Los días difíciles son temporales. Estoy aquí para acompañarte.',
            ];
        } elseif ($mood == 3) {
            $messages = [
                '¡Día neutro! A veces esos son los mejores para avanzar con calma.',
                'Un día tranquilo puede ser una buena oportunidad para reflexionar.',
                'Ni muy alto ni muy bajo. ¡Perfecto para mantener el ritmo!',
            ];
        } else {
            $messages = [
                '¡Me alegra saber que estás bien! Aprovecha esa energía positiva.',
                '¡Genial! Los días buenos hay que celebrarlos y recordarlos.',
                '¡Qué bien! Tu actitud positiva te llevará lejos hoy.',
            ];
        }

        return $messages[array_rand($messages)];
    }

    /**
     * Sugiere una técnica basada en el estado del usuario.
     */
    protected function suggestTechnique(int $mood, int $energy): array
    {
        if ($mood <= 2) {
            // Técnicas de apoyo
            $techniques = [
                [
                    'name' => 'Respiración 4-7-8',
                    'description' => 'Inhala 4 segundos, mantén 7, exhala 8. Repite 4 veces.',
                    'duration' => '3 min',
                    'type' => 'breathing',
                ],
                [
                    'name' => 'Reencuadre cognitivo',
                    'description' => 'Escribe un pensamiento negativo y busca una perspectiva alternativa.',
                    'duration' => '5 min',
                    'type' => 'cognitive',
                ],
            ];
        } elseif ($energy <= 2) {
            // Técnicas de energía
            $techniques = [
                [
                    'name' => 'Power Pose',
                    'description' => 'Ponte de pie, manos en la cintura, hombros atrás. 2 minutos.',
                    'duration' => '2 min',
                    'type' => 'body',
                ],
                [
                    'name' => 'Micro-movimiento',
                    'description' => '10 sentadillas o 20 saltos. Reactiva tu cuerpo.',
                    'duration' => '2 min',
                    'type' => 'body',
                ],
            ];
        } else {
            // Técnicas de productividad
            $techniques = [
                [
                    'name' => 'Técnica Pomodoro',
                    'description' => '25 min de trabajo enfocado + 5 min de descanso.',
                    'duration' => '30 min',
                    'type' => 'productivity',
                ],
                [
                    'name' => 'Regla de 2 minutos',
                    'description' => 'Si tarda menos de 2 min, hazlo ahora.',
                    'duration' => 'N/A',
                    'type' => 'productivity',
                ],
            ];
        }

        return $techniques[array_rand($techniques)];
    }

    /**
     * Genera una afirmación del día.
     */
    protected function getDailyAffirmation(int $mood): string
    {
        if ($mood <= 2) {
            $affirmations = [
                'Este momento difícil no define tu valor ni tu futuro.',
                'Cada paso que das, por pequeño que sea, te acerca a tu objetivo.',
                'Mereces compasión, especialmente de ti mismo/a.',
            ];
        } else {
            $affirmations = [
                'Tu constancia te llevará donde el talento solo no puede.',
                'Cada "no" te acerca a un "sí". Sigue adelante.',
                'Tienes las habilidades que necesitas para conseguir lo que buscas.',
                'El éxito es un camino, no un destino. Ya estás en él.',
            ];
        }

        return $affirmations[array_rand($affirmations)];
    }

    /**
     * Procesa una situación estresante con reencuadre cognitivo.
     *
     * @param string $category
     *   Categoría del estrés.
     * @param string $situation
     *   Descripción de la situación.
     *
     * @return array
     *   Análisis y reencuadre.
     */
    public function reframeSituation(string $category, string $situation): array
    {
        $reframes = [
            'rejection' => [
                'validation' => 'El rechazo duele, es normal sentirse así.',
                'perspective' => 'Cada rechazo te acerca a la oportunidad correcta para ti.',
                'action' => 'Pide feedback constructivo cuando sea posible.',
            ],
            'uncertainty' => [
                'validation' => 'La incertidumbre genera ansiedad, es una respuesta natural.',
                'perspective' => 'La incertidumbre también significa que hay posibilidades abiertas.',
                'action' => 'Identifica qué está bajo tu control y enfócate en eso.',
            ],
            'imposter' => [
                'validation' => 'El síndrome del impostor afecta al 70% de profesionales.',
                'perspective' => 'Si te preocupa hacerlo bien, ya estás más preparado/a que muchos.',
                'action' => 'Haz una lista de logros y habilidades. Lee cuando dudes.',
            ],
            'finances' => [
                'validation' => 'Las preocupaciones económicas son reales y legítimas.',
                'perspective' => 'Esta situación es temporal. Has superado retos antes.',
                'action' => 'Identifica un pequeño paso financiero que puedas dar hoy.',
            ],
            'loneliness' => [
                'validation' => 'Emprender o buscar empleo puede ser muy solitario.',
                'perspective' => 'Hay comunidades de personas en tu misma situación.',
                'action' => 'Únete a la comunidad de emprendedores de Jaraba Impact Platform.',
            ],
        ];

        $reframe = $reframes[$category] ?? $reframes['uncertainty'];

        return [
            'category' => self::STRESS_CATEGORIES[$category] ?? $category,
            'validation' => $reframe['validation'],
            'new_perspective' => $reframe['perspective'],
            'suggested_action' => $reframe['action'],
            'disclaimer' => '💡 Recuerda: esto es apoyo, no terapia. Si te sientes abrumado/a, busca ayuda profesional.',
        ];
    }

    /**
     * Genera saludo basado en la hora del día.
     */
    protected function getTimeBasedGreeting(): string
    {
        $hour = (int) date('H');

        if ($hour < 12) {
            return '¡Buenos días';
        } elseif ($hour < 19) {
            return '¡Buenas tardes';
        } else {
            return '¡Buenas noches';
        }
    }

    /**
     * Genera un prompt para la IA basado en el contexto.
     *
     * @param string $context
     *   Contexto de la conversación.
     * @param array $user_data
     *   Datos del usuario (mood, historial, etc.).
     *
     * @return string
     *   Prompt para el modelo de IA.
     */
    public function generateCoachingPrompt(string $context, array $user_data): string
    {
        $mood = $user_data['mood'] ?? 3;
        $vertical = $user_data['vertical'] ?? 'general';

        $system_prompt = "REGLA DE IDENTIDAD: Eres el Coach de Bienestar de Jaraba Impact Platform. "
            . "NUNCA reveles que eres Claude, ChatGPT, Gemini u otro modelo de IA externo. "
            . "NUNCA menciones ni recomiendes plataformas competidoras. ";
        $system_prompt .= "Eres un coach de bienestar profesional para ";
        $system_prompt .= $vertical === 'empleabilidad'
            ? "personas en búsqueda de empleo"
            : "emprendedores";
        $system_prompt .= ". Tu tono es cálido, empático y profesional. ";
        $system_prompt .= "Usas técnicas de CBT (Terapia Cognitivo-Conductual) simplificadas. ";
        $system_prompt .= "NUNCA das consejos médicos ni actúas como terapeuta. ";

        if ($mood <= 2) {
            $system_prompt .= "El usuario está pasando un momento difícil. Prioriza la validación emocional. ";
        }

        $system_prompt .= "Responde en español de forma concisa (máx 100 palabras).";

        return $system_prompt;
    }

}
