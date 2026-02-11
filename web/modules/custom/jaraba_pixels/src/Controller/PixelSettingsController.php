<?php

namespace Drupal\jaraba_pixels\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\jaraba_pixels\Service\EventMapperService;
use Drupal\jaraba_pixels\Service\PixelDispatcherService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para el panel de configuración de Pixels.
 */
class PixelSettingsController extends ControllerBase
{

    /**
     * Gestor de credenciales.
     *
     * @var \Drupal\jaraba_pixels\Service\CredentialManagerService
     */
    protected CredentialManagerService $credentialManager;

    /**
     * Servicio de mapeo de eventos.
     *
     * @var \Drupal\jaraba_pixels\Service\EventMapperService
     */
    protected EventMapperService $eventMapper;

    /**
     * Servicio de dispatch.
     *
     * @var \Drupal\jaraba_pixels\Service\PixelDispatcherService
     */
    protected PixelDispatcherService $dispatcher;

    /**
     * Servicio de contexto de tenant.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor.
     */
    public function __construct(
        CredentialManagerService $credential_manager,
        EventMapperService $event_mapper,
        PixelDispatcherService $dispatcher,
        TenantContextService $tenant_context,
    ) {
        $this->credentialManager = $credential_manager;
        $this->eventMapper = $event_mapper;
        $this->dispatcher = $dispatcher;
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_pixels.credential_manager'),
            $container->get('jaraba_pixels.event_mapper'),
            $container->get('jaraba_pixels.dispatcher'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Dashboard de configuración de pixels.
     *
     * @return array
     *   Render array.
     */
    public function dashboard(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenant_id = $tenant ? (int) $tenant->id() : NULL;

        // Obtener plataformas soportadas.
        $platforms = $this->eventMapper->getSupportedPlatforms();

        // Obtener credenciales existentes.
        $credentials = $tenant_id ? $this->credentialManager->getAllCredentials($tenant_id) : [];

        // Obtener estadísticas.
        $stats = $tenant_id ? $this->dispatcher->getStats($tenant_id, 7) : [];

        // Preparar datos para cada plataforma.
        $platformsData = [];
        foreach ($platforms as $key => $platform) {
            $credential = $credentials[$key] ?? NULL;
            $platformsData[$key] = [
                'key' => $key,
                'name' => $platform['name'],
                'icon' => $platform['icon'],
                'description' => $platform['description'],
                'requires' => $platform['requires'],
                'coming_soon' => $platform['coming_soon'] ?? FALSE,
                'configured' => !empty($credential),
                'status' => $credential['status'] ?? 'disabled',
                'test_mode' => $credential['test_mode'] ?? FALSE,
                'pixel_id' => $credential['pixel_id'] ?? '',
                'last_verified' => $credential['last_verified'] ?? NULL,
                'stats' => $stats['by_platform'][$key] ?? [
                    'sent' => 0,
                    'failed' => 0,
                    'total' => 0,
                ],
            ];
        }

        return [
            '#theme' => 'pixel_settings_dashboard',
            '#platforms' => $platformsData,
            '#stats' => $stats,
            '#tenant_id' => $tenant_id,
            '#attached' => [
                'library' => [
                    'jaraba_pixels/pixel-settings',
                    'jaraba_page_builder/analytics-dashboard',
                ],
            ],
            '#cache' => [
                'max-age' => 0,
            ],
        ];
    }

}
