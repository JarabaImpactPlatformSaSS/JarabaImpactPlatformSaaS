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
 * - AUDIT-SEC-N16: Permissions-Policy restrictiva (camera, microphone off;
 *   geolocation y payment solo self)
 * - AUDIT-SEC-N17: Referrer-Policy strict-origin-when-cross-origin
 * - AUDIT-SEC-N18: X-Permitted-Cross-Domain-Policies: none
 *
 * RELACIONES:
 * - SecurityHeadersSubscriber <- SecurityHeadersSettingsForm (configuración)
 * - SecurityHeadersSubscriber -> KernelEvents::RESPONSE
 *
 * @see docs/tecnicos/auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md (SEC-08)
 * @see docs/implementacion/20260213-Plan_Remediacion_Auditoria_Integral_v1.md (SEC-N16, SEC-N17, SEC-N18)
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
        return [
                // Security headers run early (100) to be present on all responses.
                // Vary: Host runs late (-10) to append AFTER FinishResponseSubscriber
                // (priority 0) which sets Vary: Cookie.
            KernelEvents::RESPONSE => [
                ['onKernelResponse', 100],
                ['onAddVaryHost', -10],
            ],
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

        $request = $event->getRequest();
        if ($request === NULL) {
            return;
        }
        $response = $event->getResponse();
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
        // CSP-POLICY-001: Built as structured directives for maintainability.
        // Admin UI override (csp.policy config) takes full precedence.
        //
        // Domains justified:
        //   google/gstatic: reCAPTCHA v3 (captcha.captcha_point.user_login_form)
        //   stripe: Embedded Checkout + webhooks (STRIPE-CHECKOUT-001)
        //   googleapis: Google Fonts + Gemini API
        //   jsdelivr/unpkg: GrapesJS + vendor libs
        //   unsplash: stock images in Page Builder
        // ═══════════════════════════════════════════════════
        $cspEnabled = $config->get('csp.enabled') ?? TRUE;
        if ($cspEnabled) {
            $cspPolicy = $config->get('csp.policy') ?: implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com www.google.com www.gstatic.com",
                "style-src 'self' 'unsafe-inline' fonts.googleapis.com",
                "font-src 'self' fonts.gstatic.com",
                "img-src 'self' data: blob: *.stripe.com images.unsplash.com www.gstatic.com",
                "connect-src 'self' api.stripe.com api.openai.com api.anthropic.com generativelanguage.googleapis.com www.google.com",
                "frame-src 'self' js.stripe.com www.google.com",
            ]);
            $response->headers->set('Content-Security-Policy', $cspPolicy);
        }

        // ═══════════════════════════════════════════════════
        // HEADERS ADICIONALES DE SEGURIDAD
        // ═══════════════════════════════════════════════════
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // AUDIT-SEC-N17: Referrer-Policy — controla qué información de referrer
        // se envía con las solicitudes. strict-origin-when-cross-origin envía el
        // origen completo en same-origin, solo el origen en cross-origin HTTPS,
        // y nada en downgrade HTTPS→HTTP.
        $referrerPolicy = $config->get('referrer_policy') ?: 'strict-origin-when-cross-origin';
        $response->headers->set('Referrer-Policy', $referrerPolicy);

        // AUDIT-SEC-N16: Permissions-Policy — restringe acceso a APIs del
        // navegador. camera y microphone deshabilitados; geolocation y payment
        // permitidos solo para el propio origen (necesarios para click-and-collect
        // y checkout Stripe respectivamente).
        $permissionsPolicy = $config->get('permissions_policy') ?: 'camera=(), microphone=(), geolocation=(self), payment=(self)';
        $response->headers->set('Permissions-Policy', $permissionsPolicy);

        // AUDIT-SEC-N18: X-Permitted-Cross-Domain-Policies — previene que
        // Adobe Flash/Acrobat carguen datos cross-domain desde este servidor.
        // Valor 'none' bloquea todas las políticas cross-domain.
        $crossDomainPolicies = $config->get('cross_domain_policies') ?: 'none';
        $response->headers->set('X-Permitted-Cross-Domain-Policies', $crossDomainPolicies);

        // HSTS solo en producción (no en desarrollo local).
        $hstsEnabled = $config->get('hsts.enabled') ?? FALSE;
        if ($hstsEnabled) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

    }

    /**
     * Appends Vary: Host for multi-tenant reverse proxy cache isolation.
     *
     * Runs AFTER FinishResponseSubscriber (priority 0) which sets Vary: Cookie.
     * Without this, Traefik/Varnish/CDN can serve a cached response from one
     * subdomain to another.
     */
    public function onAddVaryHost(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $response = $event->getResponse();
        $vary = $response->headers->get('Vary', '');
        if (stripos($vary, 'Host') === FALSE) {
            $response->headers->set('Vary', $vary ? $vary . ', Host' : 'Host');
        }

    }

}
