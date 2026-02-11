<?php

namespace Drupal\jaraba_pixels\Client;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para LinkedIn Conversions API.
 *
 * Envía eventos server-side a LinkedIn para tracking
 * de conversiones en campañas publicitarias.
 */
class LinkedInCapiClient
{

    /**
     * URL base de la API de LinkedIn.
     */
    protected const API_BASE = 'https://api.linkedin.com/rest/conversionEvents';

    /**
     * Cliente HTTP.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        $logger_factory,
    ) {
        $this->httpClient = $http_client;
        $this->logger = $logger_factory->get('jaraba_pixels.linkedin');
    }

    /**
     * Envía un evento a LinkedIn Conversions API.
     *
     * @param string $partner_id
     *   ID del partner de LinkedIn.
     * @param string $access_token
     *   Token de acceso OAuth 2.0.
     * @param array $event_data
     *   Datos del evento formateados.
     * @param bool $test_mode
     *   Si es TRUE, marca el evento como test.
     *
     * @return array
     *   Resultado con success, code, response.
     */
    public function sendEvent(
        string $partner_id,
        string $access_token,
        array $event_data,
        bool $test_mode = FALSE,
    ): array {
        $body = [
            'conversion' => $event_data['conversion'] ?? 'urn:li:lyndaConversion:' . $partner_id,
            'conversionHappenedAt' => ($event_data['timestamp'] ?? time()) * 1000, // LinkedIn usa ms
            'conversionValue' => [
                'amount' => (string) ($event_data['value'] ?? '0'),
                'currencyCode' => $event_data['currency'] ?? 'EUR',
            ],
            'eventId' => $event_data['event_id'] ?? uniqid('li_'),
        ];

        // Datos de usuario para matching.
        if (!empty($event_data['user_data'])) {
            $body['user'] = $this->formatUserData($event_data['user_data']);
        }

        try {
            $response = $this->httpClient->post(self::API_BASE, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json',
                    'X-Restli-Protocol-Version' => '2.0.0',
                    'LinkedIn-Version' => '202312',
                ],
                'json' => $body,
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $code = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), TRUE);

            return [
                'success' => $code >= 200 && $code < 300,
                'code' => $code,
                'response' => $responseBody,
                'error' => NULL,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            $errorBody = NULL;

            if ($e->hasResponse()) {
                try {
                    $errorBody = json_decode($e->getResponse()->getBody()->getContents(), TRUE);
                } catch (\Exception $parseError) {
                    // Ignorar errores de parseo.
                }
            }

            $this->logger->warning('LinkedIn CAPI request failed: @message', [
                '@message' => $e->getMessage(),
            ]);

            return [
                'success' => FALSE,
                'code' => $code,
                'response' => $errorBody,
                'error' => $errorBody['message'] ?? $e->getMessage(),
            ];
        }
    }

    /**
     * Formatea datos de usuario para LinkedIn.
     *
     * @param array $user_data
     *   Datos del usuario.
     *
     * @return array
     *   Datos formateados para LinkedIn.
     */
    protected function formatUserData(array $user_data): array
    {
        $formatted = [];

        // Email hasheado (SHA256).
        if (!empty($user_data['email'])) {
            $formatted['userIds'] = [
                [
                    'idType' => 'SHA256_EMAIL',
                    'idValue' => hash('sha256', strtolower(trim($user_data['email']))),
                ],
            ];
        }

        // First party ID.
        if (!empty($user_data['user_id'])) {
            $formatted['userInfo'] = [
                'firstName' => $user_data['first_name'] ?? '',
                'lastName' => $user_data['last_name'] ?? '',
            ];
        }

        return $formatted;
    }

    /**
     * Verifica las credenciales.
     *
     * @param string $partner_id
     *   ID del partner.
     * @param string $access_token
     *   Token de acceso.
     *
     * @return array
     *   Resultado de la verificación.
     */
    public function verifyCredentials(string $partner_id, string $access_token): array
    {
        // LinkedIn no tiene endpoint de verificación directo,
        // intentamos hacer un GET de las conversiones del partner.
        try {
            $response = $this->httpClient->get(
                'https://api.linkedin.com/v2/partners/' . $partner_id,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $access_token,
                        'LinkedIn-Version' => '202312',
                    ],
                    'timeout' => 10,
                ]
            );

            return [
                'valid' => $response->getStatusCode() === 200,
                'message' => 'Conexión verificada correctamente.',
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'valid' => FALSE,
                'message' => 'Error de autenticación: ' . $e->getMessage(),
            ];
        }
    }

}
