<?php

namespace Drupal\ecosistema_jaraba_core\EventSubscriber;

use Drupal\ecosistema_jaraba_core\Service\FinOpsTrackingService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber para tracking de requests por tenant.
 *
 * PROPÓSITO:
 * Registra cada request HTTP en la tabla finops_usage_log
 * asociándolo al tenant activo. Se ejecuta en el evento
 * TERMINATE para no impactar el tiempo de respuesta.
 *
 * LÓGICA:
 * 1. Obtiene el tenant_id del request (header o sesión)
 * 2. Registra el request con endpoint y tiempo de respuesta
 * 3. Los datos se usan en el Dashboard FinOps
 */
class RequestTrackingSubscriber implements EventSubscriberInterface
{

    /**
     * Servicio de tracking FinOps.
     */
    protected FinOpsTrackingService $finopsTracking;

    /**
     * Constructor.
     */
    public function __construct(FinOpsTrackingService $finopsTracking)
    {
        $this->finopsTracking = $finopsTracking;
    }

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

        // 2. Intentar desde el servicio TenantContextService
        try {
            $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            if ($tenant_context && method_exists($tenant_context, 'getCurrentTenantId')) {
                $tenant_id = $tenant_context->getCurrentTenantId();
                if (!empty($tenant_id)) {
                    return $tenant_id;
                }
            }
        } catch (\Exception $e) {
            // Service may not exist
        }

        // 3. Intentar desde el dominio (Domain Access)
        try {
            $domain_negotiator = \Drupal::service('domain.negotiator');
            if ($domain_negotiator) {
                $active_domain = $domain_negotiator->getActiveDomain();
                if ($active_domain) {
                    return $active_domain->id();
                }
            }
        } catch (\Exception $e) {
            // Domain module may not be active
        }

        // 4. Fallback: usar 'default' para requests sin tenant
        return 'default';
    }

}
