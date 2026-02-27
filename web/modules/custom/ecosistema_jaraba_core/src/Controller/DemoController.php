<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Drupal\ecosistema_jaraba_core\Service\AbTestService;
use Drupal\jaraba_ai_agents\Agent\StorytellingAgent;
use Drupal\ecosistema_jaraba_core\Service\DemoFeatureGateService;
use Drupal\ecosistema_jaraba_core\Service\DemoInteractiveService;
use Drupal\ecosistema_jaraba_core\Service\DemoJourneyProgressionService;
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
        protected DemoFeatureGateService $featureGate,
        protected DemoJourneyProgressionService $journeyProgression,
        protected ?AbTestService $abTestService = NULL,
        protected ?StorytellingAgent $storytellingAgent = NULL,
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
            $container->get('ecosistema_jaraba_core.demo_feature_gate'),
            $container->get('ecosistema_jaraba_core.demo_journey_progression'),
            $container->has('ecosistema_jaraba_core.ab_test') ? $container->get('ecosistema_jaraba_core.ab_test') : NULL,
            $container->has('jaraba_ai_agents.storytelling_agent') ? $container->get('jaraba_ai_agents.storytelling_agent') : NULL,
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

        // S7-05: Social proof — cuántas personas están explorando.
        $activeDemoCount = $this->demoService->getActiveDemoCount();

        return [
            '#theme' => 'demo_landing',
            '#profiles' => $profiles,
            '#active_demo_count' => $activeDemoCount,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/demo-landing'],
            ],
            '#cache' => [
                'max-age' => 60,
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

        // S7-03: A/B testing — asignar variantes para la sesión.
        $abVariants = [];
        if ($this->abTestService) {
            $abVariants = [
                'demo_landing_cta' => $this->abTestService->getVariant('demo_landing_cta'),
                'demo_profile_order' => $this->abTestService->getVariant('demo_profile_order'),
                'demo_conversion_modal_timing' => $this->abTestService->getVariant('demo_conversion_modal_timing'),
            ];
        }

        // Generar datos de demo (S1-04: incluir IP para tracking en tabla).
        $demoData = $this->demoService->generateDemoSession(
            $profileId,
            $sessionId,
            $request->getClientIp() ?? '',
            $abVariants,
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

        // S5-01: Feature gate — limitar funcionalidades demo.
        $featureMap = [
            'generate_story' => 'story_generations_per_session',
            'view_products' => 'products_viewed_per_session',
        ];
        if (isset($featureMap[$action])) {
            $gate = $this->featureGate->check($sessionId, $featureMap[$action]);
            if (!$gate['allowed']) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => (string) $this->t('Has alcanzado el límite de esta funcionalidad en la demo.'),
                    'feature_limited' => TRUE,
                    'remaining' => 0,
                ], 429);
            }
            $this->featureGate->recordUsage($sessionId, $featureMap[$action]);
        }

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

        // S5-11: Validar formato de session_id.
        if (!$this->isValidSessionId($sessionId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('ID de sesión inválido.'),
            ], 400);
        }

        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Sesión no encontrada.'),
            ], 404);
        }

        // S5-02: Incluir nudges de conversión proactivos.
        $nudges = $this->journeyProgression->evaluateNudges($sessionId);

        return new JsonResponse([
            'success' => TRUE,
            'session' => $session,
            'nudges' => $nudges,
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

        // S5-13: Código HTTP apropiado según resultado.
        $statusCode = !empty($result['success']) ? 200 : 422;
        return new JsonResponse($result, $statusCode);
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

        // S5-11: Validar formato de session_id.
        if (!$this->isValidSessionId($sessionId)) {
            $demoUrl = Url::fromRoute('ecosistema_jaraba_core.demo_landing')->toString();
            return [
                '#markup' => '<div class="demo-error">'
                    . (string) $this->t('ID de sesión inválido. <a href="@url">Iniciar nueva demo</a>', ['@url' => $demoUrl])
                    . '</div>',
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

        // S5-02: Evaluar nudges de conversión proactivos.
        $nudges = $this->journeyProgression->evaluateNudges($sessionId);

        // S7-07: Progressive disclosure level.
        $disclosure = $this->journeyProgression->getDisclosureLevel($sessionId);

        return [
            '#theme' => 'demo_dashboard_view',
            '#session' => $session,
            '#nudges' => $nudges,
            '#disclosure' => $disclosure,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/demo-dashboard',
                ],
                'drupalSettings' => [
                    'demo' => [
                        'sessionId' => $sessionId,
                        'salesHistory' => $session['sales_history'],
                        'metrics' => $session['metrics'],
                        'nudges' => $nudges,
                        'expires' => $session['expires'] ?? 0,
                        'disclosure' => $disclosure,
                    ],
                ],
            ],
            // S5-12: Cache metadata explícita.
            '#cache' => [
                'max-age' => 0,
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
        // S6-12: Escenarios delegados al servicio.
        $scenarios = $this->demoService->getAiScenarios();

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

        // S5-11: Validar formato de session_id.
        if (!$this->isValidSessionId($sessionId)) {
            $demoUrl = Url::fromRoute('ecosistema_jaraba_core.demo_landing')->toString();
            return [
                '#markup' => '<div class="demo-error">'
                    . (string) $this->t('ID de sesión inválido. <a href="@url">Iniciar nueva demo</a>', ['@url' => $demoUrl])
                    . '</div>',
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

        // S5-01: Feature gate — limitar generaciones de historias.
        $storyGate = $this->featureGate->check($sessionId, 'story_generations_per_session');
        if (!$storyGate['allowed']) {
            return [
                '#theme' => 'demo_ai_storytelling',
                '#session' => $session,
                '#generated_story' => (string) $this->t('Has alcanzado el límite de generaciones de historia en esta demo. Crea tu cuenta para acceso ilimitado.'),
                '#story_limited' => TRUE,
                '#attached' => [
                    'library' => ['ecosistema_jaraba_core/demo-storytelling'],
                ],
            ];
        }
        $this->featureGate->recordUsage($sessionId, 'story_generations_per_session');

        // Registrar acción de valor.
        $this->demoService->trackDemoAction($sessionId, 'generate_story');

        // S7-04: Intentar storytelling con IA real, fallback a hardcoded.
        $profile = $session['profile'];
        $tenantName = $session['tenant_name'];
        $story = NULL;
        $isAiGenerated = FALSE;

        if ($this->storytellingAgent) {
            try {
                $result = $this->storytellingAgent->execute('brand_story', [
                    'brand_name' => $tenantName,
                    'vertical' => $profile['vertical'] ?? 'agroconecta',
                    'founding_context' => $profile['description'] ?? '',
                    'values' => (string) $this->t('Innovación, sostenibilidad, comunidad'),
                ]);

                if (!empty($result['success']) && !empty($result['content'])) {
                    $story = Xss::filter($result['content']);
                    $isAiGenerated = TRUE;
                }
            }
            catch (\Exception $e) {
                $this->getLogger('demo_controller')->warning(
                    'Storytelling agent failed for session @session: @error',
                    ['@session' => $sessionId, '@error' => $e->getMessage()]
                );
            }
        }

        // S6-12: Fallback a historias hardcoded.
        if (!$story) {
            $story = $this->demoService->getDemoStory($profile['id'], $tenantName);
        }

        return [
            '#theme' => 'demo_ai_storytelling',
            '#session' => $session,
            '#generated_story' => $story,
            '#ai_generated' => $isAiGenerated,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/demo-storytelling'],
            ],
        ];
    }

    /**
     * API: Descarta un nudge de conversión.
     *
     * S5-02: Endpoint para que el frontend descarte nudges proactivos.
     * Rate limit: 30 req/min por IP (reutiliza demo_track).
     * CSRF: obligatorio via routing.yml.
     */
    public function dismissNudge(Request $request): JsonResponse
    {
        // Rate limiting.
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
        $nudgeId = $data['nudge_id'] ?? '';

        // Validar session_id.
        if (!is_string($sessionId) || !$this->isValidSessionId($sessionId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('ID de sesión inválido.'),
            ], 400);
        }

        // Validar nudge_id (solo letras minúsculas y guiones bajos).
        if (!is_string($nudgeId) || !preg_match('/^[a-z_]+$/', $nudgeId)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('ID de nudge inválido.'),
            ], 400);
        }

        $this->journeyProgression->dismissNudge($sessionId, $nudgeId);

        return new JsonResponse(['success' => TRUE]);
    }

}
