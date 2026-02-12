<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_copilot_v2\Service\FeatureUnlockService;
use Drupal\jaraba_copilot_v2\Service\ExperimentLibraryService;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService;
use Drupal\ecosistema_jaraba_core\Service\RateLimiterService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para el Copiloto v2.
 *
 * Proporciona endpoints REST para:
 * - Estado de desbloqueo
 * - Modos del copiloto disponibles
 * - Contexto del emprendedor
 * - Chat con el copiloto (integrado con Claude API)
 */
class CopilotApiController extends ControllerBase
{

    /**
     * Feature unlock service.
     */
    protected FeatureUnlockService $featureUnlock;

    /**
     * Experiment library service.
     */
    protected ExperimentLibraryService $experimentLibrary;

    /**
     * Copilot Orchestrator service.
     */
    protected CopilotOrchestratorService $copilotOrchestrator;

    /**
     * AI Usage Limit service.
     */
    protected AIUsageLimitService $aiUsageLimit;

    /**
     * Rate limiter service.
     */
    protected RateLimiterService $rateLimiter;

    /**
     * Tenant context service.
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     */
    public function __construct(
        FeatureUnlockService $featureUnlock,
        ExperimentLibraryService $experimentLibrary,
        CopilotOrchestratorService $copilotOrchestrator,
        AIUsageLimitService $aiUsageLimit,
        RateLimiterService $rateLimiter,
        TenantContextService $tenantContext
    ) {
        $this->featureUnlock = $featureUnlock;
        $this->experimentLibrary = $experimentLibrary;
        $this->copilotOrchestrator = $copilotOrchestrator;
        $this->aiUsageLimit = $aiUsageLimit;
        $this->rateLimiter = $rateLimiter;
        $this->tenantContext = $tenantContext;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_copilot_v2.feature_unlock'),
            $container->get('jaraba_copilot_v2.experiment_library'),
            $container->get('jaraba_copilot_v2.copilot_orchestrator'),
            $container->get('ecosistema_jaraba_core.ai_usage_limit'),
            $container->get('ecosistema_jaraba_core.rate_limiter'),
            $container->get('ecosistema_jaraba_core.tenant_context')
        );
    }

    /**
     * Obtiene el estado de desbloqueo para el usuario actual.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con el estado de desbloqueo.
     */
    public function unlockStatus(): JsonResponse
    {
        $status = $this->featureUnlock->getUnlockStatus();

        return new JsonResponse([
            'success' => TRUE,
            'data' => $status,
        ]);
    }

    /**
     * Obtiene los modos del Copiloto disponibles.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con los modos y su disponibilidad.
     */
    public function getModes(): JsonResponse
    {
        $modes = $this->featureUnlock->getAvailableCopilotModes();

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'modes' => $modes,
                'total_modes' => count($modes),
                'available_count' => count(array_filter($modes, fn($m) => $m['available'])),
            ],
        ]);
    }

    /**
     * Obtiene el contexto del emprendedor para el Copiloto.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con el contexto completo.
     */
    public function getContext(): JsonResponse
    {
        $uid = $this->currentUser()->id();

        // Cargar perfil del emprendedor
        try {
            $profiles = $this->entityTypeManager()
                ->getStorage('entrepreneur_profile')
                ->loadByProperties(['user_id' => $uid]);

            $profile = $profiles ? reset($profiles) : NULL;
        } catch (\Exception $e) {
            $profile = NULL;
        }

        if (!$profile) {
            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'has_profile' => FALSE,
                    'message' => $this->t('No tienes un perfil de emprendedor. Completa el DIME para comenzar.'),
                    'next_action' => 'dime_test',
                ],
            ]);
        }

        // Construir contexto
        $context = [
            'has_profile' => TRUE,
            'entrepreneur' => [
                'name' => $profile->label(),
                'carril' => $profile->get('carril')->value ?? 'IMPULSO',
                'dime_score' => $profile->get('dime_score')->value ?? 0,
                'current_week' => $this->featureUnlock->getUnlockStatus()['current_week'],
            ],
            'unlock_status' => $this->featureUnlock->getUnlockStatus(),
            'available_modes' => $this->featureUnlock->getAvailableCopilotModes(),
            'experiment_categories' => $this->experimentLibrary->getCategoriesWithStatus($profile),
        ];

        return new JsonResponse([
            'success' => TRUE,
            'data' => $context,
        ]);
    }

    /**
     * Endpoint de chat con el Copiloto.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request con mensaje del usuario.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta del Copiloto.
     */
    public function chat(Request $request): JsonResponse
    {
        // AI-01: Rate limiting por usuario para proteger contra abuso.
        $userId = (string) $this->currentUser()->id();
        $rateLimitResult = $this->rateLimiter->consume($userId, 'ai');
        if (!$rateLimitResult['allowed']) {
            $response = new JsonResponse([
                'success' => FALSE,
                'error' => 'Demasiadas solicitudes. Por favor, inténtalo de nuevo más tarde.',
            ], 429);
            foreach ($this->rateLimiter->getHeaders($rateLimitResult) as $header => $value) {
                $response->headers->set($header, $value);
            }
            return $response;
        }

        $content = json_decode($request->getContent(), TRUE);
        $message = $content['message'] ?? '';
        $requestedMode = $content['mode'] ?? NULL;

        if (empty($message)) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('El mensaje no puede estar vacío.'),
            ], 400);
        }

        // =====================================================================
        // VERIFICAR LÍMITES DE USO DE IA
        // =====================================================================
        $tenant = $this->tenantContext->getCurrentTenant();
        if ($tenant) {
            $aiLimitCheck = $this->aiUsageLimit->checkLimit($tenant);

            if ($aiLimitCheck['status'] === 'blocked' && !$aiLimitCheck['can_use_ai']) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $aiLimitCheck['message'],
                    'ai_limit_exceeded' => TRUE,
                    'usage_percent' => $aiLimitCheck['usage_percent'],
                    'upgrade_required' => TRUE,
                    'plan_tier' => $aiLimitCheck['plan_tier'],
                ], 429);
            }
        }

        // Obtener contexto del emprendedor
        $context = $this->getEntrepreneurContext();

        // Si se especificó un modo, usarlo directamente
        if ($requestedMode !== NULL) {
            // Verificar si el modo está disponible
            if (!$this->featureUnlock->isCopilotModeAvailable($requestedMode)) {
                $modeConfig = FeatureUnlockService::COPILOT_MODES[$requestedMode] ?? [];
                $unlockWeek = $modeConfig['unlock_week'] ?? 0;

                return new JsonResponse([
                    'success' => FALSE,
                    'error' => $this->t('El modo @mode estará disponible en la Semana @week.', [
                        '@mode' => $modeConfig['label'] ?? $requestedMode,
                        '@week' => $unlockWeek,
                    ]),
                    'locked_mode' => $requestedMode,
                    'unlock_week' => $unlockWeek,
                ], 403);
            }

            // Llamar al orquestador con modo específico
            $response = $this->copilotOrchestrator->chat($message, $context, $requestedMode);
            $detectedMode = $requestedMode;
            $modeDetection = NULL;
        } else {
            // Usar detección automática con ModeDetectorService (scoring avanzado)
            $response = $this->copilotOrchestrator->detectAndChat($message, $context);
            $detectedMode = $response['mode'] ?? 'consultor';
            $modeDetection = $response['mode_detection'] ?? NULL;

            // Verificar si el modo detectado está disponible
            if (!$this->featureUnlock->isCopilotModeAvailable($detectedMode)) {
                $modeConfig = FeatureUnlockService::COPILOT_MODES[$detectedMode] ?? [];
                $unlockWeek = $modeConfig['unlock_week'] ?? 0;

                // Fallback a modo 'coach' que siempre está disponible
                $fallbackMode = 'coach';
                $response = $this->copilotOrchestrator->chat($message, $context, $fallbackMode);
                $detectedMode = $fallbackMode;
                $modeDetection['fallback_reason'] = "Modo original '$detectedMode' bloqueado hasta semana $unlockWeek";
            }
        }

        $modeConfig = FeatureUnlockService::COPILOT_MODES[$detectedMode] ?? [];

        $responseData = [
            'response' => $response['text'] ?? '',
            'mode_used' => $detectedMode,
            'mode_label' => $modeConfig['label'] ?? $detectedMode,
            'mode_icon' => $modeConfig['icon'] ?? '🤖',
            'suggestions' => $response['suggestions'] ?? [],
            'api_configured' => $this->copilotOrchestrator->isConfigured(),
            'error' => $response['error'] ?? FALSE,
        ];

        // Incluir info de detección de modo si está disponible (útil para debugging)
        if ($modeDetection !== NULL) {
            $responseData['mode_detection'] = $modeDetection;
        }

        // =====================================================================
        // REGISTRAR USO DE TOKENS Y VERIFICAR ALERTAS
        // =====================================================================
        if ($tenant) {
            // Registrar tokens usados (estimación basada en longitud)
            $tokensIn = (int) ceil(mb_strlen($message) / 4);
            $tokensOut = (int) ceil(mb_strlen($response['text'] ?? '') / 4);
            $this->aiUsageLimit->recordUsage($tenant, $tokensIn, $tokensOut);

            // Verificar si debe enviar alerta de warning
            $newStatus = $this->aiUsageLimit->checkLimit($tenant);
            if ($newStatus['status'] === 'warning' && !$this->hasAlertBeenSentToday($tenant)) {
                $this->sendUsageWarningEmail($tenant, $newStatus);
            }

            // Añadir info de uso al response
            $responseData['ai_usage'] = [
                'tokens_used' => $tokensIn + $tokensOut,
                'usage_percent' => $newStatus['usage_percent'],
                'status' => $newStatus['status'],
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $responseData,
        ]);
    }

    /**
     * Verifica si ya se envió alerta hoy para este tenant.
     */
    protected function hasAlertBeenSentToday($tenant): bool
    {
        $key = 'ai_usage_alert_' . $tenant->id() . '_' . date('Y-m-d');
        return (bool) \Drupal::state()->get($key, FALSE);
    }

    /**
     * Envía email de warning cuando el tenant alcanza el 80% del límite.
     */
    protected function sendUsageWarningEmail($tenant, array $aiStatus): void
    {
        // Marcar que ya se envió hoy
        $key = 'ai_usage_alert_' . $tenant->id() . '_' . date('Y-m-d');
        \Drupal::state()->set($key, TRUE);

        // Obtener admin del tenant
        $adminUser = $tenant->getAdminUser();
        if (!$adminUser) {
            return;
        }

        $email = $adminUser->getEmail();
        if (empty($email)) {
            return;
        }

        // Enviar email usando hook_mail existente
        $mailManager = \Drupal::service('plugin.manager.mail');
        $params = [
            'tenant_name' => $tenant->getName(),
            'admin_name' => $adminUser->getDisplayName(),
            'percentage' => round($aiStatus['usage_percent']),
            'current' => number_format($aiStatus['tokens_used']),
            'max' => number_format($aiStatus['tokens_limit']),
            'limit_type' => 'tokens de IA',
            'upgrade_url' => \Drupal\Core\Url::fromRoute('ecosistema_jaraba_core.tenant.change_plan', [], ['absolute' => TRUE])->toString(),
        ];

        $mailManager->mail(
            'ecosistema_jaraba_core',
            'usage_limit_alert',
            $email,
            $adminUser->getPreferredLangcode(),
            $params,
            NULL,
            TRUE
        );

        \Drupal::logger('ecosistema_jaraba_core')->info(
            'AI usage warning email sent to @email for tenant @tenant (@percent%)',
            [
                '@email' => $email,
                '@tenant' => $tenant->getName(),
                '@percent' => round($aiStatus['usage_percent']),
            ]
        );
    }

    /**
     * Detecta el modo apropiado basándose en el mensaje.
     *
     * @param string $message
     *   Mensaje del usuario.
     *
     * @return string
     *   Modo detectado.
     */
    protected function detectMode(string $message): string
    {
        $messageLower = mb_strtolower($message);

        foreach (FeatureUnlockService::COPILOT_MODES as $mode => $config) {
            $triggers = $config['triggers'] ?? [];
            foreach ($triggers as $trigger) {
                if (str_contains($messageLower, mb_strtolower($trigger))) {
                    return $mode;
                }
            }
        }

        // Default: consultor táctico
        return 'consultor';
    }

    /**
     * Obtiene el contexto del emprendedor actual.
     *
     * @return array
     *   Contexto para el prompt del Copiloto.
     */
    protected function getEntrepreneurContext(): array
    {
        $uid = $this->currentUser()->id();

        try {
            $profiles = $this->entityTypeManager()
                ->getStorage('entrepreneur_profile')
                ->loadByProperties(['user_id' => $uid]);

            $profile = $profiles ? reset($profiles) : NULL;
        } catch (\Exception $e) {
            $profile = NULL;
        }

        if (!$profile) {
            return [];
        }

        return [
            'name' => $profile->label(),
            'carril' => $profile->get('carril')->value ?? 'IMPULSO',
            'phase' => $profile->get('phase')->value ?? 'INVENTARIO',
            'week' => $this->featureUnlock->getProfileWeek($profile),
            'sector' => $profile->get('sector')->value ?? '',
            'idea' => $profile->get('idea_description')->value ?? '',
            'blockages' => $this->parseBlockages($profile->get('detected_blockages')->value ?? ''),
            'dime' => [
                'total' => (int) ($profile->get('dime_score')->value ?? 0),
                'digital' => (int) ($profile->get('dime_digital')->value ?? 0),
                'idea' => (int) ($profile->get('dime_idea')->value ?? 0),
                'mercado' => (int) ($profile->get('dime_mercado')->value ?? 0),
                'emocional' => (int) ($profile->get('dime_emocional')->value ?? 0),
            ],
        ];
    }

    /**
     * Parsea los bloqueos detectados.
     */
    protected function parseBlockages(string $value): array
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

}

