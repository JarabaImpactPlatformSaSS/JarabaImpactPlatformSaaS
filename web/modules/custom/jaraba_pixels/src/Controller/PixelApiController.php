<?php

namespace Drupal\jaraba_pixels\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_pixels\Client\GoogleMeasurementClient;
use Drupal\jaraba_pixels\Client\LinkedInCapiClient;
use Drupal\jaraba_pixels\Client\MetaCapiClient;
use Drupal\jaraba_pixels\Client\TikTokEventsClient;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\jaraba_pixels\Service\PixelDispatcherService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para las APIs REST de gestión de pixels.
 */
class PixelApiController extends ControllerBase
{

    /**
     * Gestor de credenciales.
     *
     * @var \Drupal\jaraba_pixels\Service\CredentialManagerService
     */
    protected CredentialManagerService $credentialManager;

    /**
     * Servicio de dispatch.
     *
     * @var \Drupal\jaraba_pixels\Service\PixelDispatcherService
     */
    protected PixelDispatcherService $dispatcher;

    /**
     * Cliente de Meta.
     *
     * @var \Drupal\jaraba_pixels\Client\MetaCapiClient
     */
    protected MetaCapiClient $metaClient;

    /**
     * Cliente de Google.
     *
     * @var \Drupal\jaraba_pixels\Client\GoogleMeasurementClient
     */
    protected GoogleMeasurementClient $googleClient;

    /**
     * Cliente de LinkedIn.
     *
     * @var \Drupal\jaraba_pixels\Client\LinkedInCapiClient
     */
    protected LinkedInCapiClient $linkedinClient;

    /**
     * Cliente de TikTok.
     *
     * @var \Drupal\jaraba_pixels\Client\TikTokEventsClient
     */
    protected TikTokEventsClient $tiktokClient;

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
        PixelDispatcherService $dispatcher,
        MetaCapiClient $meta_client,
        GoogleMeasurementClient $google_client,
        LinkedInCapiClient $linkedin_client,
        TikTokEventsClient $tiktok_client,
        TenantContextService $tenant_context,
    ) {
        $this->credentialManager = $credential_manager;
        $this->dispatcher = $dispatcher;
        $this->metaClient = $meta_client;
        $this->googleClient = $google_client;
        $this->linkedinClient = $linkedin_client;
        $this->tiktokClient = $tiktok_client;
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_pixels.credential_manager'),
            $container->get('jaraba_pixels.dispatcher'),
            $container->get('jaraba_pixels.meta_client'),
            $container->get('jaraba_pixels.google_client'),
            $container->get('jaraba_pixels.linkedin_client'),
            $container->get('jaraba_pixels.tiktok_client'),
            $container->get('ecosistema_jaraba_core.tenant_context'),
        );
    }

    /**
     * Guarda credenciales de una plataforma.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function saveCredential(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['platform'])) {
            return new JsonResponse(['error' => 'Platform is required'], 400);
        }

        $platform = $data['platform'];
        unset($data['platform']);

        // Validar plataforma.
        $validPlatforms = ['meta', 'google', 'linkedin', 'tiktok'];
        if (!in_array($platform, $validPlatforms)) {
            return new JsonResponse(['error' => 'Invalid platform'], 400);
        }

        // Validar campos requeridos según plataforma.
        $required = match ($platform) {
            'meta' => ['pixel_id', 'access_token'],
            'google' => ['pixel_id', 'api_secret'],
            default => ['pixel_id'],
        };

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Field {$field} is required"], 400);
            }
        }

        // Guardar credencial.
        $success = $this->credentialManager->saveCredential($platform, $data);

        if (!$success) {
            return new JsonResponse(['error' => 'Failed to save credential'], 500);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Credenciales guardadas correctamente.',
        ]);
    }

    /**
     * Obtiene credenciales de una plataforma.
     *
     * @param string $platform
     *   Plataforma.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function getCredential(string $platform): JsonResponse
    {
        $credential = $this->credentialManager->getCredential($platform);

        if (!$credential) {
            return new JsonResponse(['configured' => FALSE]);
        }

        // Ocultar tokens sensibles en la respuesta.
        $safe_credential = [
            'configured' => TRUE,
            'platform' => $credential['platform'],
            'pixel_id' => $credential['pixel_id'],
            'status' => $credential['status'],
            'test_mode' => (bool) $credential['test_mode'],
            'test_event_code' => $credential['test_event_code'] ?? '',
            'last_verified' => $credential['last_verified'],
            // Indicar si hay tokens configurados sin revelarlos.
            'has_access_token' => !empty($credential['access_token']),
            'has_api_secret' => !empty($credential['api_secret']),
        ];

        return new JsonResponse($safe_credential);
    }

    /**
     * Elimina credenciales de una plataforma.
     *
     * @param string $platform
     *   Plataforma.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function deleteCredential(string $platform): JsonResponse
    {
        $success = $this->credentialManager->deleteCredential($platform);

        if (!$success) {
            return new JsonResponse(['error' => 'Failed to delete credential'], 500);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Credenciales eliminadas.',
        ]);
    }

    /**
     * Prueba la conexión con una plataforma.
     *
     * @param string $platform
     *   Plataforma.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function testConnection(string $platform): JsonResponse
    {
        $credential = $this->credentialManager->getCredential($platform);

        if (!$credential) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => 'No hay credenciales configuradas para esta plataforma.',
            ]);
        }

        $result = match ($platform) {
            'meta' => $this->metaClient->verifyCredentials(
                $credential['pixel_id'],
                $credential['access_token']
            ),
            'google' => $this->googleClient->verifyCredentials(
                $credential['pixel_id'],
                $credential['api_secret']
            ),
            'linkedin' => $this->linkedinClient->verifyCredentials(
                $credential['pixel_id'],
                $credential['access_token']
            ),
            'tiktok' => $this->tiktokClient->verifyCredentials(
                $credential['pixel_id'],
                $credential['access_token']
            ),
            default => ['valid' => FALSE, 'message' => $this->t('Plataforma no soportada para test.')],
        };

        // Si fue exitoso, actualizar timestamp de verificación.
        if (!empty($result['valid'])) {
            $this->credentialManager->markAsVerified($platform);
        }

        return new JsonResponse([
            'success' => $result['valid'] ?? FALSE,
            'message' => $result['message'] ?? 'Error desconocido.',
        ]);
    }

    /**
     * Obtiene estadísticas de eventos enviados.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function getStats(Request $request): JsonResponse
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return new JsonResponse(['error' => 'No tenant context'], 400);
        }

        $days = (int) $request->query->get('days', 7);
        $days = min(max($days, 1), 30); // Limitar entre 1 y 30 días.

        $stats = $this->dispatcher->getStats((int) $tenant->id(), $days);

        return new JsonResponse($stats);
    }

    /**
     * Devuelve el formulario HTML de configuración para slide-panel.
     *
     * @param string $platform
     *   Plataforma a configurar.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   HTML del formulario.
     */
    public function getConfigForm(string $platform): Response
    {
        // Obtener credenciales existentes.
        $credential = $this->credentialManager->getCredential($platform);

        // Configuración según plataforma.
        $config = $this->getPlatformConfig($platform);

        // Construir HTML del formulario.
        $html = $this->buildConfigFormHtml($platform, $config, $credential);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Obtiene la configuración de campos para cada plataforma.
     *
     * @param string $platform
     *   Nombre de la plataforma.
     *
     * @return array
     *   Configuración de la plataforma.
     */
    protected function getPlatformConfig(string $platform): array
    {
        return match ($platform) {
            'meta' => [
                'name' => 'Meta (Facebook/Instagram)',
                'description' => $this->t('Conversions API para Facebook e Instagram Ads.'),
                'docs_url' => 'https://developers.facebook.com/docs/marketing-api/conversions-api',
                'fields' => [
                    'pixel_id' => [
                        'label' => $this->t('Pixel ID'),
                        'type' => 'text',
                        'placeholder' => '1234567890123456',
                        'help' => $this->t('El ID de tu pixel de Meta (16 dígitos).'),
                        'required' => TRUE,
                    ],
                    'access_token' => [
                        'label' => $this->t('Access Token'),
                        'type' => 'password',
                        'placeholder' => 'EAAxxxxxxx...',
                        'help' => $this->t('Token de acceso de la API de Conversiones.'),
                        'required' => TRUE,
                    ],
                    'test_mode' => [
                        'label' => $this->t('Modo de prueba'),
                        'type' => 'checkbox',
                        'help' => $this->t('Activa para enviar eventos al Events Manager de pruebas.'),
                    ],
                    'test_event_code' => [
                        'label' => $this->t('Código de evento de prueba'),
                        'type' => 'text',
                        'placeholder' => 'TEST12345',
                        'help' => $this->t('Código opcional para Events Manager test.'),
                    ],
                ],
            ],
            'google' => [
                'name' => 'Google (Ads/GA4)',
                'description' => $this->t('Measurement Protocol para Google Ads y Analytics 4.'),
                'docs_url' => 'https://developers.google.com/analytics/devguides/collection/protocol/ga4',
                'fields' => [
                    'pixel_id' => [
                        'label' => $this->t('Measurement ID'),
                        'type' => 'text',
                        'placeholder' => 'G-XXXXXXXXXX',
                        'help' => $this->t('ID de medición de GA4 (comienza con G-).'),
                        'required' => TRUE,
                    ],
                    'api_secret' => [
                        'label' => $this->t('API Secret'),
                        'type' => 'password',
                        'placeholder' => 'xxxxxxxxxxxxxxx',
                        'help' => $this->t('Secreto de API de Measurement Protocol.'),
                        'required' => TRUE,
                    ],
                    'test_mode' => [
                        'label' => $this->t('Modo de prueba'),
                        'type' => 'checkbox',
                        'help' => $this->t('Activa para validar eventos sin enviarlos.'),
                    ],
                ],
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'description' => $this->t('Conversions API para LinkedIn Ads.'),
                'docs_url' => 'https://learn.microsoft.com/en-us/linkedin/marketing/integrations/conversions-api',
                'fields' => [
                    'pixel_id' => [
                        'label' => $this->t('Partner ID'),
                        'type' => 'text',
                        'placeholder' => '123456',
                        'help' => $this->t('ID de partner de LinkedIn.'),
                        'required' => TRUE,
                    ],
                    'access_token' => [
                        'label' => $this->t('Access Token'),
                        'type' => 'password',
                        'placeholder' => 'AQxxxxxxx...',
                        'help' => $this->t('Token de acceso OAuth 2.0.'),
                        'required' => TRUE,
                    ],
                    'test_mode' => [
                        'label' => $this->t('Modo de prueba'),
                        'type' => 'checkbox',
                        'help' => $this->t('Activa para validar sin enviar eventos.'),
                    ],
                ],
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'description' => $this->t('Events API para TikTok Ads.'),
                'docs_url' => 'https://business-api.tiktok.com/portal/docs?id=1771101027431425',
                'fields' => [
                    'pixel_id' => [
                        'label' => $this->t('Pixel Code'),
                        'type' => 'text',
                        'placeholder' => 'CXXXXXXXXXXXXXXXXX',
                        'help' => $this->t('Código del pixel de TikTok.'),
                        'required' => TRUE,
                    ],
                    'access_token' => [
                        'label' => $this->t('Access Token'),
                        'type' => 'password',
                        'placeholder' => 'xxxxxxxxxxxxxxx',
                        'help' => $this->t('Token de acceso de la API de eventos.'),
                        'required' => TRUE,
                    ],
                    'test_mode' => [
                        'label' => $this->t('Modo de prueba'),
                        'type' => 'checkbox',
                        'help' => $this->t('Activa para eventos de prueba.'),
                    ],
                ],
            ],
            default => [
                'name' => ucfirst($platform),
                'description' => $this->t('Plataforma no configurada.'),
                'docs_url' => '',
                'fields' => [],
            ],
        };
    }

    /**
     * Construye el HTML del formulario de configuración.
     *
     * @param string $platform
     *   Nombre de la plataforma.
     * @param array $config
     *   Configuración de la plataforma.
     * @param array|null $credential
     *   Credenciales existentes.
     *
     * @return string
     *   HTML del formulario.
     */
    protected function buildConfigFormHtml(string $platform, array $config, ?array $credential): string
    {
        $isConfigured = !empty($credential);

        $html = '<div class="pixel-config-form">';

        // Header con descripción.
        $html .= '<div class="pixel-config-form__header">';
        $html .= '<p class="pixel-config-form__description">' . $config['description'] . '</p>';
        if ($config['docs_url']) {
            $html .= '<a href="' . $config['docs_url'] . '" target="_blank" rel="noopener" class="pixel-config-form__docs-link">';
            $html .= $this->t('Ver documentación') . ' →';
            $html .= '</a>';
        }
        $html .= '</div>';

        // Formulario.
        $html .= '<form class="pixel-config-form__form" data-platform="' . $platform . '">';
        $html .= '<input type="hidden" name="platform" value="' . $platform . '">';

        // Campos dinámicos.
        foreach ($config['fields'] as $fieldName => $fieldConfig) {
            $value = $credential[$fieldName] ?? '';
            $required = $fieldConfig['required'] ?? FALSE;
            $requiredAttr = $required ? 'required' : '';
            $requiredLabel = $required ? ' <span class="pixel-config-form__required">*</span>' : '';

            $html .= '<div class="pixel-config-form__field">';
            $html .= '<label class="pixel-config-form__label" for="' . $fieldName . '">';
            $html .= $fieldConfig['label'] . $requiredLabel;
            $html .= '</label>';

            if ($fieldConfig['type'] === 'checkbox') {
                $checked = !empty($value) ? 'checked' : '';
                $html .= '<label class="pixel-config-form__checkbox">';
                $html .= '<input type="checkbox" name="' . $fieldName . '" id="' . $fieldName . '" ' . $checked . '>';
                $html .= '<span class="pixel-config-form__checkbox-label">' . $fieldConfig['help'] . '</span>';
                $html .= '</label>';
            } else {
                // Para campos password, no mostrar valor si ya está configurado.
                $displayValue = ($fieldConfig['type'] === 'password' && $isConfigured && $value)
                    ? ''
                    : htmlspecialchars($value);
                $placeholder = ($fieldConfig['type'] === 'password' && $isConfigured && $value)
                    ? $this->t('••••••••• (guardado)')
                    : ($fieldConfig['placeholder'] ?? '');

                $html .= '<input type="' . $fieldConfig['type'] . '" ';
                $html .= 'name="' . $fieldName . '" ';
                $html .= 'id="' . $fieldName . '" ';
                $html .= 'value="' . $displayValue . '" ';
                $html .= 'placeholder="' . htmlspecialchars($placeholder) . '" ';
                $html .= 'class="pixel-config-form__input" ';
                $html .= $requiredAttr . '>';

                if (!empty($fieldConfig['help']) && $fieldConfig['type'] !== 'checkbox') {
                    $html .= '<span class="pixel-config-form__help">' . $fieldConfig['help'] . '</span>';
                }
            }

            $html .= '</div>';
        }

        // Botones de acción.
        $html .= '<div class="pixel-config-form__actions">';
        if ($isConfigured) {
            $html .= '<button type="button" class="btn btn--danger btn--outline js-delete-credential" data-platform="' . $platform . '">';
            $html .= $this->t('Eliminar');
            $html .= '</button>';
        }
        $html .= '<button type="submit" class="btn btn--primary">';
        $html .= $isConfigured ? $this->t('Actualizar') : $this->t('Guardar');
        $html .= '</button>';
        $html .= '</div>';

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

}
