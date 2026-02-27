<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService;
use Drupal\ecosistema_jaraba_core\Service\GuidedTourService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para la demo interactiva.
 *
 * PROPÓSITO:
 * Gestiona las rutas públicas de demo que permiten a usuarios
 * anónimos experimentar la plataforma sin registro.
 *
 * SEGURIDAD (S1-01 / S1-02 / S1-03):
 * - FloodInterface para rate limiting por IP en todos los endpoints.
 * - Validación de input con whitelist de acciones y formato de session_id.
 * - CSRF obligatorio en rutas POST (configurado en routing.yml).
 *
 * Q1 2027 - Gap P0: Instant Value
 */
class DemoController extends ControllerBase
{

    /**
     * Rate limits por endpoint (peticiones/minuto por IP).
     */
    protected const RATE_LIMIT_START = 10;
    protected const RATE_LIMIT_TRACK = 30;
    protected const RATE_LIMIT_SESSION = 20;
    protected const RATE_LIMIT_CONVERT = 5;

    /**
     * Acciones permitidas para tracking (whitelist S1-02).
     */
    protected const ALLOWED_ACTIONS = [
        'view_dashboard',
        'generate_story',
        'browse_marketplace',
        'view_categories',
        'view_products',
        'click_cta',
        'scroll_section',
        'page_view',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected DemoInteractiveService $demoService,
        protected GuidedTourService $tourService,
        protected FloodInterface $flood,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('ecosistema_jaraba_core.demo_interactive'),
            $container->get('ecosistema_jaraba_core.guided_tours'),
            $container->get('flood'),
        );
    }

    /**
     * Comprueba rate limiting y devuelve 429 si se excede.
     *
     * Patrón canónico: PublicCopilotController (RATE-LIMIT-001).
     *
     * @param string $floodName
     *   Identificador del flood event.
     * @param int $threshold
     *   Máximo de peticiones por ventana.
     * @param string $clientIp
     *   IP del cliente.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|null
     *   JsonResponse 429 si se excede el límite, NULL si OK.
     */
    protected function checkRateLimit(string $floodName, int $threshold, string $clientIp): ?JsonResponse
    {
        if (!$this->flood->isAllowed($floodName, $threshold, 60, $clientIp)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Has alcanzado el límite de peticiones. Inténtalo de nuevo en un minuto.'),
                'rate_limited' => TRUE,
            ], 429);
        }
        $this->flood->register($floodName, 60, $clientIp);
        return NULL;
    }

    /**
     * Valida que un session_id tenga el formato esperado.
     *
     * Formato: demo_ + 16 hex chars (bin2hex de 8 bytes).
     */
    protected function isValidSessionId(string $sessionId): bool
    {
        return (bool) preg_match('/^demo_[a-f0-9]{16}$/', $sessionId);
    }

    /**
     * Página principal de selección de demo.
     */
    public function demoLanding(): array
    {
        $profiles = $this->demoService->getDemoProfiles();

        return [
            '#theme' => 'demo_landing',
            '#profiles' => $profiles,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/demo-landing'],
            ],
            '#cache' => [
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Inicia una sesión de demo.
     *
     * Rate limit: 10 req/min por IP (S1-01).
     * Validación: profileId ya validado por regex en routing.yml.
     */
    public function startDemo(Request $request, string $profileId): array
    {
        // S1-01: Rate limiting.
        $rateLimited = $this->checkRateLimit(
            'demo_start',
            self::RATE_LIMIT_START,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return [
                '#markup' => '<div class="demo-error">' . (string) $this->t('Demasiadas solicitudes. Inténtalo de nuevo en un minuto.') . '</div>',
            ];
        }

        // S1-02: Validar que el profileId existe en los perfiles registrados.
        $profile = $this->demoService->getDemoProfile($profileId);
        if (!$profile) {
            return [
                '#markup' => '<div class="demo-error">' . (string) $this->t('Perfil de demo no válido.') . '</div>',
            ];
        }

        // Generar ID de sesión único.
        $sessionId = 'demo_' . bin2hex(random_bytes(8));

        // Generar datos de demo (S1-04: incluir IP para tracking en tabla).
        $demoData = $this->demoService->generateDemoSession(
            $profileId,
            $sessionId,
            $request->getClientIp() ?? '',
        );

        if (isset($demoData['error'])) {
            return [
                '#markup' => '<div class="demo-error">' . (string) $this->t('Error al crear la demo.') . '</div>',
            ];
        }

        // Obtener tour recomendado.
        $tour = $this->tourService->getTour('demo_welcome');

        return [
            '#theme' => 'demo_dashboard',
            '#session_id' => $sessionId,
            '#profile' => $demoData['profile'],
            '#tenant_name' => $demoData['tenant_name'],
            '#metrics' => $demoData['metrics'],
            '#products' => $demoData['products'],
            '#sales_history' => $demoData['sales_history'],
            '#magic_actions' => $demoData['magic_moment_actions'],
            '#tour' => $tour ? $this->tourService->getTourDriverJS($tour) : NULL,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/demo-dashboard',
                    'ecosistema_jaraba_core/charts',
                ],
                'drupalSettings' => [
                    'demo' => [
                        'sessionId' => $sessionId,
                        'salesHistory' => $demoData['sales_history'],
                    ],
                ],
            ],
            '#cache' => [
                'max-age' => 0,
            ],
        ];
    }

    /**
     * API: Registra una acción en la demo.
     *
     * Rate limit: 30 req/min por IP (S1-01).
     * Validación: session_id formato + action whitelist (S1-02).
     * CSRF: obligatorio via routing.yml (S1-03).
     */
    public function trackAction(Request $request): JsonResponse
    {
        // S1-01: Rate limiting.
        $rateLimited = $this->checkRateLimit(
            'demo_track',
            self::RATE_LIMIT_TRACK,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return $rateLimited;
        }

        $data = json_decode($request->getContent(), TRUE);
        if (!is_array($data)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Formato de datos inválido.'),
            ], 400);
        }

        $sessionId = $data['session_id'] ?? '';
        $action = $data['action'] ?? '';
        $metadata = $data['metadata'] ?? [];

        // S1-02: Validar formato de session_id.
        if (!is_string($sessionId) || !$this->isValidSessionId($sessionId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('ID de sesión inválido.'),
            ], 400);
        }

        // S1-02: Validar acción contra whitelist.
        if (!is_string($action) || !in_array($action, self::ALLOWED_ACTIONS, TRUE)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Acción no permitida.'),
            ], 400);
        }

        // S1-02: Sanitizar metadata (solo strings y números, max 10 keys).
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $metadata = array_slice($metadata, 0, 10);
        $metadata = array_filter($metadata, fn($v) => is_string($v) || is_numeric($v));

        $this->demoService->trackDemoAction($sessionId, $action, $metadata);

        // Calcular TTFV si es una acción de valor.
        $ttfv = $this->demoService->calculateTTFV($sessionId);

        return new JsonResponse([
            'success' => TRUE,
            'ttfv_seconds' => $ttfv,
        ]);
    }

    /**
     * API: Obtiene datos de sesión.
     *
     * Rate limit: 20 req/min por IP (S1-01).
     */
    public function getSessionData(Request $request, string $sessionId): JsonResponse
    {
        // S1-01: Rate limiting.
        $rateLimited = $this->checkRateLimit(
            'demo_session',
            self::RATE_LIMIT_SESSION,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return $rateLimited;
        }

        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Sesión no encontrada.'),
            ], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'session' => $session,
        ]);
    }

    /**
     * Convierte demo a registro real.
     *
     * Rate limit: 5 req/min por IP — endpoint más restrictivo (S1-01).
     * Validación: session_id formato + email formato (S1-02).
     * CSRF: obligatorio via routing.yml (S1-03).
     *
     * NOTA: NO crea usuarios directamente. Genera un token HMAC temporal
     * que redirige al flujo de onboarding real (/registro/{vertical}).
     */
    public function convertToReal(Request $request): JsonResponse
    {
        // S1-01: Rate limiting estricto (endpoint sensible).
        $rateLimited = $this->checkRateLimit(
            'demo_convert',
            self::RATE_LIMIT_CONVERT,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return $rateLimited;
        }

        $data = json_decode($request->getContent(), TRUE);
        if (!is_array($data)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Formato de datos inválido.'),
            ], 400);
        }

        $sessionId = $data['session_id'] ?? '';
        $email = $data['email'] ?? '';

        // S1-02: Validar formato session_id.
        if (!is_string($sessionId) || !$this->isValidSessionId($sessionId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('ID de sesión inválido.'),
            ], 400);
        }

        // S1-02: Validar formato email.
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Dirección de email inválida.'),
            ], 400);
        }

        $result = $this->demoService->convertToRealAccount($sessionId, $email);

        return new JsonResponse($result);
    }

    /**
     * Dashboard de demo interactivo.
     *
     * Rate limit: 20 req/min por IP (S1-01).
     * S1-06: URL generada via Url::fromRoute() (ROUTE-LANGPREFIX-001).
     */
    public function demoDashboard(Request $request, string $sessionId): array
    {
        // S1-01: Rate limiting.
        $rateLimited = $this->checkRateLimit(
            'demo_dashboard',
            self::RATE_LIMIT_SESSION,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return [
                '#markup' => '<div class="demo-error">' . (string) $this->t('Demasiadas solicitudes. Inténtalo de nuevo en un minuto.') . '</div>',
            ];
        }

        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            // S1-06: URL via Url::fromRoute() en lugar de hardcoded (ROUTE-LANGPREFIX-001).
            $demoUrl = Url::fromRoute('ecosistema_jaraba_core.demo_landing')->toString();
            return [
                '#markup' => '<div class="demo-expired">'
                    . (string) $this->t('La sesión de demo ha expirado. <a href="@url">Iniciar nueva demo</a>', ['@url' => $demoUrl])
                    . '</div>',
            ];
        }

        // Registrar acción.
        $this->demoService->trackDemoAction($sessionId, 'view_dashboard');

        return [
            '#theme' => 'demo_dashboard_view',
            '#session' => $session,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/demo-dashboard',
                    'ecosistema_jaraba_core/charts',
                ],
                'drupalSettings' => [
                    'demo' => [
                        'sessionId' => $sessionId,
                        'salesHistory' => $session['sales_history'],
                        'metrics' => $session['metrics'],
                    ],
                ],
            ],
        ];
    }

    /**
     * AI Playground — demo pública interactiva de capacidades IA.
     *
     * Muestra un widget de copiloto con escenarios preconfigurados
     * para usuarios anónimos. Rate-limited via PublicCopilotController.
     *
     * S1-06: Endpoint copilot generado via Url::fromRoute() (ROUTE-LANGPREFIX-001).
     */
    public function aiPlayground(): array
    {
        $scenarios = [
            [
                'id' => 'marketing',
                'title' => (string) $this->t('Marketing Digital'),
                'description' => (string) $this->t('Genera ideas de campañas, contenido para redes sociales y estrategias de marketing.'),
                'icon' => 'campaign',
                'prompt' => (string) $this->t('Necesito ideas para una campaña en redes sociales para una marca de alimentación ecológica dirigida a millennials.'),
            ],
            [
                'id' => 'legal',
                'title' => (string) $this->t('Consulta Legal'),
                'description' => (string) $this->t('Obtén orientación sobre cuestiones legales para emprendedores y empresas.'),
                'icon' => 'gavel',
                'prompt' => (string) $this->t('¿Cuáles son los requisitos legales para crear una cooperativa en España?'),
            ],
            [
                'id' => 'employment',
                'title' => (string) $this->t('Empleabilidad'),
                'description' => (string) $this->t('Optimiza tu CV, prepara entrevistas y descubre itinerarios profesionales.'),
                'icon' => 'work',
                'prompt' => (string) $this->t('Ayúdame a optimizar mi CV para un puesto de marketing digital. Tengo 3 años de experiencia.'),
            ],
            [
                'id' => 'entrepreneurship',
                'title' => (string) $this->t('Emprendimiento'),
                'description' => (string) $this->t('Valida ideas de negocio, construye tu canvas y planifica tu lanzamiento.'),
                'icon' => 'rocket_launch',
                'prompt' => (string) $this->t('Quiero validar una idea SaaS para gestión de restaurantes. ¿Por dónde empiezo?'),
            ],
        ];

        // S1-06: Generar URL via Url::fromRoute() (ROUTE-LANGPREFIX-001).
        $copilotEndpoint = Url::fromRoute('jaraba_copilot_v2.api.public_chat')->toString();

        return [
            '#theme' => 'demo_ai_playground',
            '#scenarios' => $scenarios,
            '#copilot_endpoint' => $copilotEndpoint,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/demo-ai-playground',
                ],
                'drupalSettings' => [
                    'demoPlayground' => [
                        'scenarios' => $scenarios,
                        'copilotEndpoint' => $copilotEndpoint,
                        'maxMessages' => 10,
                    ],
                ],
            ],
            '#cache' => [
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Generación de historia con IA (demo).
     *
     * Rate limit: 10 req/min por IP (S1-01).
     */
    public function demoAiStorytelling(Request $request, string $sessionId): array
    {
        // S1-01: Rate limiting.
        $rateLimited = $this->checkRateLimit(
            'demo_storytelling',
            self::RATE_LIMIT_START,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return [
                '#markup' => '<div class="demo-error">' . (string) $this->t('Demasiadas solicitudes. Inténtalo de nuevo en un minuto.') . '</div>',
            ];
        }

        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            // S1-06: URL via Url::fromRoute() (ROUTE-LANGPREFIX-001).
            $demoUrl = Url::fromRoute('ecosistema_jaraba_core.demo_landing')->toString();
            return [
                '#markup' => '<div class="demo-expired">'
                    . (string) $this->t('La sesión de demo ha expirado. <a href="@url">Iniciar nueva demo</a>', ['@url' => $demoUrl])
                    . '</div>',
            ];
        }

        // Registrar acción de valor.
        $this->demoService->trackDemoAction($sessionId, 'generate_story');

        // Generar historia demo (sintética, por perfil).
        $profile = $session['profile'];
        $tenantName = $session['tenant_name'];

        $demoStories = [
            'producer' => (string) $this->t(
                '**@name** representa la tradición olivarera de más de tres generaciones. En las laderas de Sierra Mágina, donde el sol y la brisa mediterránea crean el microclima perfecto, nuestros olivos centenarios producen un aceite de oliva virgen extra de calidad excepcional. Cada gota cuenta la historia de una familia comprometida con la excelencia.',
                ['@name' => $tenantName],
            ),
            'winery' => (string) $this->t(
                '**@name** nace de la pasión por el terruño y la tradición vinícola. En nuestros viñedos, cultivados con métodos sostenibles, las variedades autóctonas encuentran la expresión perfecta de un territorio único. Cada botella es un viaje sensorial que captura la esencia de nuestra tierra.',
                ['@name' => $tenantName],
            ),
            'cheese' => (string) $this->t(
                'En **@name**, cada queso es el resultado de un proceso artesanal transmitido de generación en generación. Nuestros maestros queseros seleccionan la mejor leche de ganaderías locales para crear productos únicos que honran la tradición y deleitan los paladares más exigentes.',
                ['@name' => $tenantName],
            ),
            'buyer' => (string) $this->t(
                '**@name** es un comprador exigente que valora la calidad y la procedencia de los productos. A través de nuestra plataforma, accede directamente a productores locales, apoyando la economía circular y disfrutando de la frescura y autenticidad que solo lo artesanal puede ofrecer.',
                ['@name' => $tenantName],
            ),
            'jobseeker' => (string) $this->t(
                '**@name** está construyendo una carrera profesional orientada al impacto. Con herramientas de IA que optimizan su currículum y sugieren itinerarios formativos, cada paso es más estratégico. La plataforma conecta talento con empresas que comparten valores de sostenibilidad e innovación social.',
                ['@name' => $tenantName],
            ),
            'startup' => (string) $this->t(
                '**@name** nació con la misión de transformar su sector a través de la tecnología y la innovación. Desde la validación de la idea hasta la captación de clientes, nuestra plataforma acompaña cada fase del emprendimiento con métricas inteligentes, marketing automatizado y una comunidad de mentores.',
                ['@name' => $tenantName],
            ),
            'lawfirm' => (string) $this->t(
                '**@name** combina la solidez de la tradición jurídica con la eficiencia de las herramientas digitales. Gestión inteligente de expedientes, análisis de jurisprudencia con IA y comunicación segura con clientes: así es como un despacho moderno marca la diferencia en Andalucía.',
                ['@name' => $tenantName],
            ),
            'servicepro' => (string) $this->t(
                '**@name** ofrece servicios profesionales de alta calidad respaldados por la confianza de sus clientes. La plataforma le permite gestionar citas, generar presupuestos inteligentes con IA y construir una reputación sólida basada en reseñas verificadas y trabajo bien hecho.',
                ['@name' => $tenantName],
            ),
            'socialimpact' => (string) $this->t(
                '**@name** trabaja cada día para generar un impacto positivo en la comunidad. Con herramientas de medición de impacto social, gestión de programas y comunicación transparente, nuestra plataforma ayuda a organizaciones como esta a amplificar su labor y atraer colaboradores comprometidos.',
                ['@name' => $tenantName],
            ),
            'creator' => (string) $this->t(
                '**@name** crea contenido que inspira, educa y conecta. Con un editor avanzado, analíticas de audiencia y optimización SEO asistida por IA, cada publicación alcanza a más lectores. La plataforma es el hogar perfecto para creadores que quieren profesionalizar su labor editorial.',
                ['@name' => $tenantName],
            ),
            'academy' => (string) $this->t(
                '**@name** forma a los profesionales del mañana con cursos online de primer nivel. Desde la creación de contenido didáctico con IA hasta el seguimiento del progreso de cada alumno, nuestra plataforma LMS ofrece una experiencia de aprendizaje que transforma conocimiento en oportunidades.',
                ['@name' => $tenantName],
            ),
        ];

        $story = $demoStories[$profile['id']] ?? (string) $this->t('Historia generada por IA para @name.', ['@name' => $tenantName]);

        return [
            '#theme' => 'demo_ai_storytelling',
            '#session' => $session,
            '#generated_story' => $story,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/demo-storytelling'],
            ],
        ];
    }

}
