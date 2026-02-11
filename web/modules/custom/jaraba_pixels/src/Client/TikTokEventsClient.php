<?php

namespace Drupal\jaraba_pixels\Client;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para TikTok Events API.
 *
 * Envía eventos server-side a TikTok para tracking
 * de conversiones en campañas publicitarias.
 */
class TikTokEventsClient
{

    /**
     * URL base de la API de TikTok.
     */
    protected const API_BASE = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

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
        $this->logger = $logger_factory->get('jaraba_pixels.tiktok');
    }

    /**
     * Envía un evento a TikTok Events API.
     *
     * @param string $pixel_code
     *   Código del pixel de TikTok.
     * @param string $access_token
     *   Token de acceso.
     * @param array $event_data
     *   Datos del evento formateados.
     * @param bool $test_mode
     *   Si es TRUE, marca el evento como test.
     *
     * @return array
     *   Resultado con success, code, response.
     */
    public function sendEvent(
        string $pixel_code,
        string $access_token,
        array $event_data,
        bool $test_mode = FALSE,
    ): array {
        $body = [
            'pixel_code' => $pixel_code,
            'event' => $event_data['event_name'] ?? 'PageView',
            'event_id' => $event_data['event_id'] ?? uniqid('tt_'),
            'timestamp' => date('Y-m-d\TH:i:s', $event_data['timestamp'] ?? time()),
            'context' => [
                'page' => [
                    'url' => $event_data['page_url'] ?? '',
                ],
                'user' => $this->formatUserData($event_data['user_data'] ?? []),
            ],
        ];

        // Propiedades del evento.
        if (!empty($event_data['properties'])) {
            $body['properties'] = $event_data['properties'];
        }

        // Modo test.
        if ($test_mode) {
            $body['test_event_code'] = 'TEST' . uniqid();
        }

        try {
            $response = $this->httpClient->post(self::API_BASE, [
                'headers' => [
                    'Access-Token' => $access_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $code = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), TRUE);

            // TikTok devuelve code 0 en éxito dentro del body.
            $success = ($responseBody['code'] ?? -1) === 0;

            return [
                'success' => $success,
                'code' => $code,
                'response' => $responseBody,
                'error' => $success ? NULL : ($responseBody['message'] ?? 'Error desconocido'),
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

            $this->logger->warning('TikTok Events API request failed: @message', [
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
     * Envía múltiples eventos en batch.
     *
     * @param string $pixel_code
     *   Código del pixel.
     * @param string $access_token
     *   Token de acceso.
     * @param array $events
     *   Array de eventos.
     * @param bool $test_mode
     *   Modo de test.
     *
     * @return array
     *   Resultado.
     */
    public function sendBatch(
        string $pixel_code,
        string $access_token,
        array $events,
        bool $test_mode = FALSE,
    ): array {
        $body = [
            'pixel_code' => $pixel_code,
            'batch' => array_map(function ($event) {
                return [
                    'event' => $event['event_name'] ?? 'PageView',
                    'event_id' => $event['event_id'] ?? uniqid('tt_'),
                    'timestamp' => date('Y-m-d\TH:i:s', $event['timestamp'] ?? time()),
                    'context' => [
                        'page' => ['url' => $event['page_url'] ?? ''],
                        'user' => $this->formatUserData($event['user_data'] ?? []),
                    ],
                    'properties' => $event['properties'] ?? [],
                ];
            }, $events),
        ];

        if ($test_mode) {
            $body['test_event_code'] = 'TEST' . uniqid();
        }

        try {
            $response = $this->httpClient->post(self::API_BASE, [
                'headers' => [
                    'Access-Token' => $access_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
                'timeout' => 30,
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), TRUE);
            $success = ($responseBody['code'] ?? -1) === 0;

            return [
                'success' => $success,
                'code' => $response->getStatusCode(),
                'response' => $responseBody,
                'events_received' => count($events),
                'error' => $success ? NULL : ($responseBody['message'] ?? 'Error desconocido'),
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'success' => FALSE,
                'code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                'events_received' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Formatea datos de usuario para TikTok.
     *
     * @param array $user_data
     *   Datos del usuario.
     *
     * @return array
     *   Datos formateados.
     */
    protected function formatUserData(array $user_data): array
    {
        $formatted = [];

        // Email hasheado (SHA256).
        if (!empty($user_data['email'])) {
            $formatted['email'] = hash('sha256', strtolower(trim($user_data['email'])));
        }

        // Phone hasheado.
        if (!empty($user_data['phone'])) {
            $formatted['phone'] = hash('sha256', preg_replace('/[^0-9]/', '', $user_data['phone']));
        }

        // External ID.
        if (!empty($user_data['user_id'])) {
            $formatted['external_id'] = hash('sha256', (string) $user_data['user_id']);
        }

        // IP address.
        if (!empty($user_data['ip_address'])) {
            $formatted['ip'] = $user_data['ip_address'];
        }

        // User agent.
        if (!empty($user_data['user_agent'])) {
            $formatted['user_agent'] = $user_data['user_agent'];
        }

        return $formatted;
    }

    /**
     * Verifica las credenciales.
     *
     * @param string $pixel_code
     *   Código del pixel.
     * @param string $access_token
     *   Token de acceso.
     *
     * @return array
     *   Resultado de la verificación.
     */
    public function verifyCredentials(string $pixel_code, string $access_token): array
    {
        // Enviar un evento PageView de test.
        $test_event = [
            'event_name' => 'PageView',
            'event_id' => 'test_' . uniqid(),
            'timestamp' => time(),
            'page_url' => 'https://jaraba.test/pixel-test',
            'user_data' => [
                'ip_address' => '127.0.0.1',
                'user_agent' => 'JarabaPixelTest/1.0',
            ],
        ];

        $result = $this->sendEvent($pixel_code, $access_token, $test_event, TRUE);

        return [
            'valid' => $result['success'],
            'message' => $result['success']
                ? 'Conexión verificada correctamente.'
                : ($result['error'] ?? 'Error de conexión desconocido.'),
            'details' => $result,
        ];
    }

}
