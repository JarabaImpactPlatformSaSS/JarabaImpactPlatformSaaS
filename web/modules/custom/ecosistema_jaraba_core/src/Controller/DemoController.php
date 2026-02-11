<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
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
 * Q1 2027 - Gap P0: Instant Value
 */
class DemoController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected DemoInteractiveService $demoService,
        protected GuidedTourService $tourService,
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
        );
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
     */
    public function startDemo(Request $request, string $profileId): array
    {
        // Generar ID de sesión único.
        $sessionId = 'demo_' . bin2hex(random_bytes(8));

        // Generar datos de demo.
        $demoData = $this->demoService->generateDemoSession($profileId, $sessionId);

        if (isset($demoData['error'])) {
            return [
                '#markup' => '<div class="demo-error">' . $demoData['error'] . '</div>',
            ];
        }

        // Obtener tour recomendado.
        $tour = $this->tourService->getTour('seller_welcome');

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
     */
    public function trackAction(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        $sessionId = $data['session_id'] ?? '';
        $action = $data['action'] ?? '';
        $metadata = $data['metadata'] ?? [];

        if (empty($sessionId) || empty($action)) {
            return new JsonResponse(['error' => 'Datos incompletos'], 400);
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
     */
    public function getSessionData(Request $request, string $sessionId): JsonResponse
    {
        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            return new JsonResponse(['error' => 'Sesión no encontrada'], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'session' => $session,
        ]);
    }

    /**
     * Convierte demo a registro real.
     */
    public function convertToReal(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        $sessionId = $data['session_id'] ?? '';
        $email = $data['email'] ?? '';

        if (empty($sessionId) || empty($email)) {
            return new JsonResponse(['error' => 'Datos incompletos'], 400);
        }

        $result = $this->demoService->convertToRealAccount($sessionId, $email);

        return new JsonResponse($result);
    }

    /**
     * Dashboard de demo interactivo.
     */
    public function demoDashboard(Request $request, string $sessionId): array
    {
        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            return [
                '#markup' => '<div class="demo-expired">La sesión de demo ha expirado. <a href="/demo">Iniciar nueva demo</a></div>',
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
     * Generación de historia con IA (demo).
     */
    public function demoAiStorytelling(Request $request, string $sessionId): array
    {
        $session = $this->demoService->getDemoSession($sessionId);

        if (!$session) {
            return ['#markup' => 'Sesión expirada'];
        }

        // Registrar acción de valor.
        $this->demoService->trackDemoAction($sessionId, 'generate_story');

        // Generar historia demo.
        $profile = $session['profile'];
        $tenantName = $session['tenant_name'];

        $demoStories = [
            'producer' => "**{$tenantName}** representa la tradición olivarera de más de tres generaciones. En las laderas de Sierra Mágina, donde el sol y la brisa mediterránea crean el microclima perfecto, nuestros olivos centenarios producen un aceite de oliva virgen extra de calidad excepcional. Cada gota cuenta la historia de una familia comprometida con la excelencia.",
            'winery' => "**{$tenantName}** nace de la pasión por el terruño y la tradición vinícola. En nuestros viñedos, cultivados con métodos sostenibles, las variedades autóctonas encuentran la expresión perfecta de un territorio único. Cada botella es un viaje sensorial que captura la esencia de nuestra tierra.",
            'cheese' => "En **{$tenantName}**, cada queso es el resultado de un proceso artesanal transmitido de generación en generación. Nuestros maestros queseros seleccionan la mejor leche de ganaderías locales para crear productos únicos que honran la tradición y deleitan los paladares más exigentes.",
        ];

        $story = $demoStories[$profile['id']] ?? 'Historia generada por IA...';

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
