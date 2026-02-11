<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * SEC-08: Event subscriber para inyectar headers de seguridad CORS y CSP.
 *
 * ESTRUCTURA:
 * Escucha el evento RESPONSE del kernel de Symfony para inyectar
 * headers de seguridad en TODAS las respuestas HTTP de la plataforma.
 *
 * LÓGICA DE NEGOCIO:
 * - CORS: Configurable desde /admin/config/system/rate-limits
 *   con fallback a orígenes restrictivos (no wildcard '*')
 * - CSP: Política de seguridad de contenido que previene XSS
 * - X-Frame-Options: Previene clickjacking
 * - HSTS: Fuerza HTTPS en producción
 *
 * RELACIONES:
 * - SecurityHeadersSubscriber <- RateLimitSettingsForm (configuración)
 * - SecurityHeadersSubscriber -> KernelEvents::RESPONSE
 *
 * @see docs/tecnicos/auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md (SEC-08)
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{

    /**
     * Factoría de configuración de Drupal.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Factoría de configuración para leer orígenes CORS permitidos.
     */
    public function __construct(ConfigFactoryInterface $configFactory)
    {
        $this->configFactory = $configFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        // Prioridad alta para asegurar que se aplican siempre.
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 100],
        ];
    }

    /**
     * Inyecta headers de seguridad en la respuesta HTTP.
     *
     * FLUJO:
     * 1. Lee configuración de orígenes CORS permitidos
     * 2. Inyecta CORS headers (restrictivos, no wildcard)
     * 3. Inyecta CSP header con política base
     * 4. Inyecta X-Frame-Options y HSTS
     *
     * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
     *   El evento de respuesta del kernel.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();
        $config = $this->configFactory->get('ecosistema_jaraba_core.security_headers');

        // ═══════════════════════════════════════════════════
        // CORS HEADERS
        // Configurables desde la interfaz de administración.
        // Fallback a 'self' (mismo origen) si no hay config.
        // ═══════════════════════════════════════════════════
        $allowedOrigins = $config->get('cors.allowed_origins') ?: '';
        $origin = $request->headers->get('Origin');

        if ($origin && !empty($allowedOrigins)) {
            $originsArray = array_map('trim', explode(',', $allowedOrigins));
            if (in_array($origin, $originsArray, TRUE) || in_array('*', $originsArray, TRUE)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Tenant-ID, X-Webhook-Token, X-Webhook-Signature');
                $response->headers->set('Access-Control-Max-Age', '86400');
            }
        }

        // ═══════════════════════════════════════════════════
        // CSP (Content Security Policy)
        // Política base que permite scripts de CDNs conocidos
        // y estilos inline necesarios para Drupal.
        // ═══════════════════════════════════════════════════
        $cspEnabled = $config->get('csp.enabled') ?? TRUE;
        if ($cspEnabled) {
            $cspPolicy = $config->get('csp.policy') ?: "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: blob: *.stripe.com images.unsplash.com; connect-src 'self' api.stripe.com api.openai.com api.anthropic.com; frame-src 'self' js.stripe.com";
            $response->headers->set('Content-Security-Policy', $cspPolicy);
        }

        // ═══════════════════════════════════════════════════
        // HEADERS ADICIONALES DE SEGURIDAD
        // ═══════════════════════════════════════════════════
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS solo en producción (no en desarrollo local).
        $hstsEnabled = $config->get('hsts.enabled') ?? FALSE;
        if ($hstsEnabled) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }

}
