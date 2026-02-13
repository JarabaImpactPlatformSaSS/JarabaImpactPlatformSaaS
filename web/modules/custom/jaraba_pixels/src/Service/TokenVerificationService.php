<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para verificar tokens de plataformas y enviar alertas.
 *
 * Verifica periódicamente que los tokens de acceso estén válidos
 * y envía notificaciones cuando están próximos a expirar.
 */
class TokenVerificationService
{

    /**
     * Días previos para alerta de expiración.
     */
    protected const EXPIRATION_WARNING_DAYS = 7;

    /**
     * Clave de estado para última verificación.
     */
    protected const STATE_KEY = 'jaraba_pixels.last_token_check';

    /**
     * Gestor de credenciales.
     *
     * @var \Drupal\jaraba_pixels\Service\CredentialManagerService
     */
    protected CredentialManagerService $credentialManager;

    /**
     * Servicio de mail.
     *
     * @var \Drupal\Core\Mail\MailManagerInterface
     */
    protected MailManagerInterface $mailManager;

    /**
     * Servicio de estado.
     *
     * @var \Drupal\Core\State\StateInterface
     */
    protected StateInterface $state;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Cliente HTTP para verificación en vivo.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * Constructor.
     */
    public function __construct(
        CredentialManagerService $credential_manager,
        MailManagerInterface $mail_manager,
        StateInterface $state,
        $logger_factory,
        ClientInterface $http_client,
    ) {
        $this->credentialManager = $credential_manager;
        $this->mailManager = $mail_manager;
        $this->state = $state;
        $this->logger = $logger_factory->get('jaraba_pixels.tokens');
        $this->httpClient = $http_client;
    }

    /**
     * Verifica todas las credenciales y envía alertas si es necesario.
     *
     * @return array
     *   Resultado de la verificación.
     */
    public function verifyAllCredentials(): array
    {
        $results = [
            'checked' => 0,
            'expired' => [],
            'expiring_soon' => [],
            'valid' => [],
            'errors' => [],
        ];

        // Obtener todos los tenants con credenciales.
        $tenants = $this->getAllTenantIds();

        foreach ($tenants as $tenant_id) {
            $credentials = $this->credentialManager->getAllCredentials($tenant_id);

            foreach ($credentials as $platform => $cred) {
                if (empty($cred['access_token'])) {
                    continue;
                }

                $results['checked']++;
                $status = $this->verifyCredential($platform, $cred);

                if ($status === 'expired') {
                    $results['expired'][] = [
                        'tenant_id' => $tenant_id,
                        'platform' => $platform,
                    ];
                } elseif ($status === 'expiring_soon') {
                    $results['expiring_soon'][] = [
                        'tenant_id' => $tenant_id,
                        'platform' => $platform,
                        'days_left' => $this->getDaysUntilExpiration($cred),
                    ];
                } else {
                    $results['valid'][] = [
                        'tenant_id' => $tenant_id,
                        'platform' => $platform,
                    ];
                }
            }
        }

        // Enviar alertas si hay tokens expirados o próximos a expirar.
        if (!empty($results['expired']) || !empty($results['expiring_soon'])) {
            $this->sendExpirationAlert($results);
        }

        // Guardar timestamp de última verificación.
        $this->state->set(self::STATE_KEY, time());

        $this->logger->info('Token verification complete: @checked checked, @expired expired, @expiring expiring soon', [
            '@checked' => $results['checked'],
            '@expired' => count($results['expired']),
            '@expiring' => count($results['expiring_soon']),
        ]);

        return $results;
    }

    /**
     * Verifica una credencial específica.
     *
     * @param string $platform
     *   Nombre de la plataforma.
     * @param array $credential
     *   Datos de la credencial.
     *
     * @return string
     *   Estado: 'valid', 'expired', 'expiring_soon'.
     */
    protected function verifyCredential(string $platform, array $credential): string
    {
        // Verificar fecha de expiración si está disponible.
        if (!empty($credential['expires_at'])) {
            $expires = strtotime($credential['expires_at']);
            $now = time();
            $days_left = ($expires - $now) / 86400;

            if ($days_left <= 0) {
                return 'expired';
            }

            if ($days_left <= self::EXPIRATION_WARNING_DAYS) {
                return 'expiring_soon';
            }
        }

        // V2.1: Llamada de prueba a la API de cada plataforma.
        if (!$this->verifyPlatformToken($platform, $credential)) {
            return 'expired';
        }

        return 'valid';
    }

    /**
     * Verifica un token haciendo una llamada ligera a la API de la plataforma.
     *
     * Cada plataforma tiene un endpoint de verificación diferente:
     * - Meta: GET /me?access_token=... (Graph API introspection).
     * - Google: POST /debug/mp/collect (Measurement Protocol debug).
     * - LinkedIn: GET /v2/me (perfil del titular del token).
     * - TikTok: GET /open_api/v1.3/pixel/list/ (verifica acceso).
     *
     * @param string $platform
     *   Nombre de la plataforma (meta, google, linkedin, tiktok).
     * @param array $credential
     *   Datos de la credencial con pixel_id, access_token, api_secret.
     *
     * @return bool
     *   TRUE si el token responde correctamente.
     */
    protected function verifyPlatformToken(string $platform, array $credential): bool
    {
        $token = $credential['access_token'] ?? '';
        $pixelId = $credential['pixel_id'] ?? '';

        if (empty($token)) {
            return FALSE;
        }

        try {
            $valid = match ($platform) {
                'meta' => $this->verifyMetaToken($pixelId, $token),
                'google' => $this->verifyGoogleToken($pixelId, $credential['api_secret'] ?? ''),
                'linkedin' => $this->verifyLinkedInToken($token),
                'tiktok' => $this->verifyTikTokToken($pixelId, $token),
                default => TRUE,
            };

            if (!$valid) {
                $this->logger->warning('Token de @platform no superó la verificación en vivo.', [
                    '@platform' => $platform,
                ]);
            }

            return $valid;
        } catch (\Exception $e) {
            $this->logger->error('Error verificando token de @platform: @msg', [
                '@platform' => $platform,
                '@msg' => $e->getMessage(),
            ]);
            // En caso de error de red, no invalidar el token.
            return TRUE;
        }
    }

    /**
     * Verifica token de Meta via Graph API introspection.
     */
    protected function verifyMetaToken(string $pixelId, string $accessToken): bool
    {
        $response = $this->httpClient->get('https://graph.facebook.com/v18.0/me', [
            'query' => ['access_token' => $accessToken],
            'timeout' => 5,
            'connect_timeout' => 3,
            'http_errors' => FALSE,
        ]);

        return $response->getStatusCode() === 200;
    }

    /**
     * Verifica credenciales de Google Measurement Protocol via debug endpoint.
     */
    protected function verifyGoogleToken(string $measurementId, string $apiSecret): bool
    {
        if (empty($apiSecret)) {
            return FALSE;
        }

        $response = $this->httpClient->post('https://www.google-analytics.com/debug/mp/collect', [
            'query' => [
                'measurement_id' => $measurementId,
                'api_secret' => $apiSecret,
            ],
            'json' => [
                'client_id' => 'jaraba_verify_' . time(),
                'events' => [
                    ['name' => 'page_view', 'params' => []],
                ],
            ],
            'timeout' => 5,
            'connect_timeout' => 3,
            'http_errors' => FALSE,
        ]);

        if ($response->getStatusCode() !== 200) {
            return FALSE;
        }

        $body = json_decode($response->getBody()->getContents(), TRUE);

        // El endpoint debug devuelve validationMessages; sin errores = válido.
        return empty($body['validationMessages']);
    }

    /**
     * Verifica token de LinkedIn via /v2/me.
     */
    protected function verifyLinkedInToken(string $accessToken): bool
    {
        $response = $this->httpClient->get('https://api.linkedin.com/v2/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
            'timeout' => 5,
            'connect_timeout' => 3,
            'http_errors' => FALSE,
        ]);

        return $response->getStatusCode() === 200;
    }

    /**
     * Verifica token de TikTok via pixel list endpoint.
     */
    protected function verifyTikTokToken(string $pixelCode, string $accessToken): bool
    {
        $response = $this->httpClient->get('https://business-api.tiktok.com/open_api/v1.3/pixel/list/', [
            'headers' => [
                'Access-Token' => $accessToken,
            ],
            'query' => [
                'advertiser_id' => $pixelCode,
            ],
            'timeout' => 5,
            'connect_timeout' => 3,
            'http_errors' => FALSE,
        ]);

        if ($response->getStatusCode() !== 200) {
            return FALSE;
        }

        $body = json_decode($response->getBody()->getContents(), TRUE);

        return ($body['code'] ?? -1) === 0;
    }

    /**
     * Obtiene días hasta expiración.
     *
     * @param array $credential
     *   Datos de la credencial.
     *
     * @return int
     *   Días hasta expiración, o -1 si no hay fecha.
     */
    protected function getDaysUntilExpiration(array $credential): int
    {
        if (empty($credential['expires_at'])) {
            return -1;
        }

        $expires = strtotime($credential['expires_at']);
        return (int) ceil(($expires - time()) / 86400);
    }

    /**
     * Envía alerta de expiración por email.
     *
     * @param array $results
     *   Resultados de la verificación.
     */
    public function sendExpirationAlert(array $results): void
    {
        // Obtener email del admin.
        $admin_email = \Drupal::config('system.site')->get('mail');

        if (empty($admin_email)) {
            $this->logger->warning('No admin email configured for token alerts');
            return;
        }

        $params = [
            'expired' => $results['expired'],
            'expiring_soon' => $results['expiring_soon'],
        ];

        try {
            $result = $this->mailManager->mail(
                'jaraba_pixels',
                'token_expiration_alert',
                $admin_email,
                'es',
                $params,
                NULL,
                TRUE
            );

            if ($result['result']) {
                $this->logger->info('Token expiration alert sent to @email', [
                    '@email' => $admin_email,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to send token alert: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verifica si debe ejecutarse la verificación diaria.
     *
     * @return bool
     *   TRUE si han pasado 24h desde la última verificación.
     */
    public function shouldRunDailyCheck(): bool
    {
        $last_check = $this->state->get(self::STATE_KEY, 0);
        $interval = 86400; // 24 horas.

        return (time() - $last_check) >= $interval;
    }

    /**
     * Obtiene todos los IDs de tenant con credenciales.
     *
     * @return array
     *   Array de tenant IDs.
     */
    protected function getAllTenantIds(): array
    {
        try {
            $query = \Drupal::database()
                ->select('pixel_credentials', 'pc')
                ->distinct()
                ->fields('pc', ['tenant_id']);

            return $query->execute()->fetchCol();
        } catch (\Exception $e) {
            return [];
        }
    }

}
