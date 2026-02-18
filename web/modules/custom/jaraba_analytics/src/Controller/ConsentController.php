<?php

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_analytics\Service\ConsentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para API REST de consentimientos GDPR.
 *
 * Endpoints:
 * - GET /api/v1/consent/status: Estado actual del visitante.
 * - POST /api/v1/consent/grant: Guardar consentimiento.
 * - POST /api/v1/consent/revoke: Revocar consentimiento.
 * AUDIT-CONS-N07: Added API versioning prefix.
 */
class ConsentController extends ControllerBase
{

    /**
     * Consent service.
     *
     * @var \Drupal\jaraba_analytics\Service\ConsentService
     */
    protected ConsentService $consentService;

    /**
     * Constructor del controlador.
     *
     * @param \Drupal\jaraba_analytics\Service\ConsentService $consent_service
     *   Consent service.
     */
    public function __construct(ConsentService $consent_service)
    {
        $this->consentService = $consent_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_analytics.consent'),
        );
    }

    /**
     * GET: Obtener estado de consentimiento.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Estado del consentimiento.
     */
    public function status(Request $request): JsonResponse
    {
        $visitor_id = $request->cookies->get('jaraba_visitor_id');

        if (!$visitor_id) {
            return new JsonResponse([
                'has_consent' => FALSE,
                'categories' => [
                    'necessary' => TRUE,
                    'functional' => FALSE,
                    'analytics' => FALSE,
                    'marketing' => FALSE,
                ],
                'banner_required' => TRUE,
            ]);
        }

        $record = $this->consentService->getConsent($visitor_id);

        if (!$record) {
            return new JsonResponse([
                'has_consent' => FALSE,
                'categories' => [
                    'necessary' => TRUE,
                    'functional' => FALSE,
                    'analytics' => FALSE,
                    'marketing' => FALSE,
                ],
                'banner_required' => TRUE,
            ]);
        }

        return new JsonResponse([
            'has_consent' => TRUE,
            'categories' => $record->getAllConsents(),
            'policy_version' => $record->get('policy_version')->value,
            'granted_at' => $record->get('granted_at')->value,
            'banner_required' => FALSE,
        ]);
    }

    /**
     * POST: Guardar consentimiento.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultado de la operación.
     */
    public function grant(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE);

        if (!$content) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Invalid JSON payload.',
            ], 400);
        }

        // Obtener o generar visitor_id.
        $visitor_id = $request->cookies->get('jaraba_visitor_id');
        if (!$visitor_id) {
            $visitor_id = bin2hex(random_bytes(16));
        }

        // Extraer categorías del request.
        $categories = [
            'analytics' => $content['analytics'] ?? FALSE,
            'marketing' => $content['marketing'] ?? FALSE,
            'functional' => $content['functional'] ?? TRUE,
        ];

        // Obtener tenant_id del header o config.
        $tenant_id = $request->headers->get('X-Tenant-ID') ? (int) $request->headers->get('X-Tenant-ID') : NULL;

        try {
            $record = $this->consentService->grantConsent($categories, $visitor_id, $tenant_id);

            return new JsonResponse([
                'success' => TRUE,
                'visitor_id' => $visitor_id,
                'categories' => $record->getAllConsents(),
                'message' => 'Consent saved successfully.',
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_analytics')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST: Revocar consentimiento.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultado de la operación.
     */
    public function revoke(Request $request): JsonResponse
    {
        $visitor_id = $request->cookies->get('jaraba_visitor_id');

        if (!$visitor_id) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'No visitor ID found.',
            ], 400);
        }

        try {
            $this->consentService->revokeConsent($visitor_id);

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Consent revoked successfully.',
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_analytics')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

}
