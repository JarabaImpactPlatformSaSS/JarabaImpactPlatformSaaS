<?php

namespace Drupal\ecosistema_jaraba_core\EventSubscriber;

use Drupal\ecosistema_jaraba_core\Service\FinOpsTrackingService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber para tracking de requests por tenant.
 *
 * BE-09: Refactored to use proper DI instead of \Drupal::service().
 *
 * Registra cada request HTTP en la tabla finops_usage_log
 * asociándolo al tenant activo. Se ejecuta en el evento
 * TERMINATE para no impactar el tiempo de respuesta.
 */
class RequestTrackingSubscriber implements EventSubscriberInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        protected readonly FinOpsTrackingService $finopsTracking,
        protected readonly ?TenantContextService $tenantContext = NULL,
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        // Ejecutar en TERMINATE para no impactar performance
        return [
            KernelEvents::TERMINATE => ['onKernelTerminate', 0],
        ];
    }

    /**
     * Registra el request cuando la respuesta ha sido enviada.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $request = $event->getRequest();

        // No trackear rutas internas de Drupal
        $path = $request->getPathInfo();
        if ($this->shouldSkipPath($path)) {
            return;
        }

        // Obtener tenant ID
        $tenant_id = $this->getTenantId($request);
        if (empty($tenant_id)) {
            return;
        }

        // Calcular tiempo de respuesta aproximado
        $start_time = $request->server->get('REQUEST_TIME_FLOAT', microtime(TRUE));
        $response_time = (microtime(TRUE) - $start_time) * 1000;

        // Registrar el request
        $this->finopsTracking->trackApiRequest(
            $tenant_id,
            $path,
            $response_time
        );
    }

    /**
     * Determina si un path debe excluirse del tracking.
     */
    protected function shouldSkipPath(string $path): bool
    {
        $skip_patterns = [
            '/admin/',        // Admin routes
            '/cron',          // Cron
            '/batch',         // Batch operations
            '/update.php',    // Updates
            '/install.php',   // Install
            '/core/',         // Core assets
            '/sites/',        // Site assets
            '/themes/',       // Theme assets
            '/modules/',      // Module assets
            '/_',             // Internal routes
        ];

        foreach ($skip_patterns as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Obtiene el tenant ID del request.
     */
    protected function getTenantId($request): string
    {
        // 1. Intentar desde header X-Tenant-ID
        $header_tenant = $request->headers->get('X-Tenant-ID');
        if (!empty($header_tenant)) {
            return $header_tenant;
        }

        // 2. Intentar desde el servicio TenantContextService (DI)
        try {
            if ($this->tenantContext && method_exists($this->tenantContext, 'getCurrentTenantId')) {
                $tenant_id = $this->tenantContext->getCurrentTenantId();
                if (!empty($tenant_id)) {
                    return $tenant_id;
                }
            }
        } catch (\Exception $e) {
            // Context service may fail during bootstrap
        }

        // 4. Fallback: usar 'default' para requests sin tenant
        return 'default';
    }

}
