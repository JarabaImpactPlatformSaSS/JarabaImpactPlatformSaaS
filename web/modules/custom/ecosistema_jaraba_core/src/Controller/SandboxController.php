<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Flood\FloodInterface;
use Drupal\ecosistema_jaraba_core\Service\SandboxTenantService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para la API de Sandbox/Demo.
 *
 * Endpoints para crear y gestionar sandboxes temporales.
 *
 * SEGURIDAD (S1-01 / S1-02 / S1-03):
 * - FloodInterface para rate limiting por IP en todos los endpoints.
 * - Validación de input con whitelist de templates.
 * - CSRF obligatorio en rutas POST (configurado en routing.yml).
 */
class SandboxController extends ControllerBase
{

    /**
     * Rate limits por endpoint (peticiones/minuto por IP).
     */
    protected const RATE_LIMIT_CREATE = 5;
    protected const RATE_LIMIT_TRACK = 30;
    protected const RATE_LIMIT_CONVERT = 3;
    protected const RATE_LIMIT_READ = 20;

    /**
     * Constructor.
     */
    public function __construct(
        protected SandboxTenantService $sandboxService,
        protected FloodInterface $flood,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('ecosistema_jaraba_core.sandbox_tenant'),
            $container->get('flood'),
        );
    }

    /**
     * Comprueba rate limiting y devuelve 429 si se excede.
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
     * POST /api/v1/sandbox/create - Crear nuevo sandbox.
     *
     * Rate limit: 5 req/min (S1-01). CSRF obligatorio (S1-03).
     * Validación: template contra lista de disponibles (S1-02).
     */
    public function createSandbox(Request $request): JsonResponse
    {
        $rateLimited = $this->checkRateLimit(
            'sandbox_create',
            self::RATE_LIMIT_CREATE,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return $rateLimited;
        }

        $data = json_decode($request->getContent(), TRUE);
        $template = $data['template'] ?? 'agroconecta';

        // S1-02: Validar template contra la lista de disponibles.
        $availableTemplates = $this->sandboxService->getAvailableTemplates();
        $templateIds = array_column($availableTemplates, 'id');
        if (!in_array($template, $templateIds, TRUE)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Plantilla no válida.'),
            ], 400);
        }

        $sandbox = $this->sandboxService->createSandbox($template);

        return new JsonResponse([
            'success' => TRUE,
            'sandbox' => $sandbox,
            'message' => (string) $this->t('Sandbox creado correctamente. Expira en 24 horas.'),
        ]);
    }

    /**
     * GET /api/v1/sandbox/{id} - Obtener datos del sandbox.
     *
     * Rate limit: 20 req/min (S1-01).
     */
    public function get(Request $request, string $id): JsonResponse
    {
        $rateLimited = $this->checkRateLimit(
            'sandbox_get',
            self::RATE_LIMIT_READ,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return $rateLimited;
        }

        $sandbox = $this->sandboxService->getSandbox($id);

        if (!$sandbox) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Sandbox no encontrado o expirado.'),
            ], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'sandbox' => $sandbox,
        ]);
    }

    /**
     * POST /api/v1/sandbox/{id}/track - Registrar engagement.
     *
     * Rate limit: 30 req/min (S1-01). CSRF obligatorio (S1-03).
     */
    public function track(Request $request, string $id): JsonResponse
    {
        $rateLimited = $this->checkRateLimit(
            'sandbox_track',
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

        $action = $data['action'] ?? '';
        $metadata = $data['metadata'] ?? [];

        // S1-02: Validar que action no esté vacío y sea string.
        if (!is_string($action) || $action === '') {
            return new JsonResponse([
                'success' => FALSE,
                'error' => (string) $this->t('Acción requerida.'),
            ], 400);
        }

        // S1-02: Sanitizar metadata.
        if (!is_array($metadata)) {
            $metadata = [];
        }
        $metadata = array_slice($metadata, 0, 10);

        $this->sandboxService->trackEngagement($id, $action, $metadata);

        return new JsonResponse([
            'success' => TRUE,
            'message' => (string) $this->t('Acción registrada.'),
        ]);
    }

    /**
     * POST /api/v1/sandbox/{id}/convert - Convertir a cuenta real.
     *
     * Rate limit: 3 req/min — el más restrictivo (S1-01). CSRF obligatorio (S1-03).
     *
     * NOTA S1-03: Este endpoint YA NO crea usuarios directamente.
     * Delega al flujo de onboarding real via token HMAC.
     */
    public function convert(Request $request, string $id): JsonResponse
    {
        $rateLimited = $this->checkRateLimit(
            'sandbox_convert',
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

        $result = $this->sandboxService->convertToAccount($id, $data);

        return new JsonResponse($result);
    }

    /**
     * GET /api/v1/sandbox/templates - Listar templates disponibles.
     *
     * Rate limit: 20 req/min (S1-01).
     */
    public function templates(Request $request): JsonResponse
    {
        $rateLimited = $this->checkRateLimit(
            'sandbox_templates',
            self::RATE_LIMIT_READ,
            $request->getClientIp() ?? 'unknown',
        );
        if ($rateLimited) {
            return $rateLimited;
        }

        $templates = $this->sandboxService->getAvailableTemplates();

        return new JsonResponse([
            'success' => TRUE,
            'templates' => $templates,
        ]);
    }

    /**
     * GET /api/v1/sandbox/stats - Estadísticas de sandboxes.
     *
     * Requiere permiso admin (configurado en routing.yml).
     */
    public function stats(): JsonResponse
    {
        $stats = $this->sandboxService->getStatistics();

        return new JsonResponse([
            'success' => TRUE,
            'statistics' => $stats,
        ]);
    }

}
