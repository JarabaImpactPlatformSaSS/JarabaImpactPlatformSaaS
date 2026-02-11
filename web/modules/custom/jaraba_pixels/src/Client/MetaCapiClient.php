<?php

namespace Drupal\jaraba_pixels\Client;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para Meta Conversions API (CAPI).
 *
 * Envía eventos server-side a Meta (Facebook/Instagram) para
 * tracking preciso que evita ad-blockers.
 */
class MetaCapiClient
{

    /**
     * URL base de la API de Meta.
     */
    protected const API_BASE = 'https://graph.facebook.com/v18.0';

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
        $this->logger = $logger_factory->get('jaraba_pixels.meta');
    }

    /**
     * Envía un evento a Meta CAPI.
     *
     * @param string $pixel_id
     *   ID del pixel de Meta.
     * @param string $access_token
     *   Token de acceso.
     * @param array $event_data
     *   Datos del evento formateados.
     * @param string|null $test_event_code
     *   Código de evento de test (opcional).
     *
     * @return array
     *   Resultado con success, code, response.
     */
    public function sendEvent(
        string $pixel_id,
        string $access_token,
        array $event_data,
        ?string $test_event_code = NULL,
    ): array {
        $url = self::API_BASE . "/{$pixel_id}/events";

        $body = [
            'data' => [$event_data],
        ];

        if ($test_event_code) {
            $body['test_event_code'] = $test_event_code;
        }

        try {
            $response = $this->httpClient->post($url, [
                'query' => ['access_token' => $access_token],
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

            $this->logger->warning('Meta CAPI request failed: @message', [
                '@message' => $e->getMessage(),
            ]);

            return [
                'success' => FALSE,
                'code' => $code,
                'response' => $errorBody,
                'error' => $errorBody['error']['message'] ?? $e->getMessage(),
            ];
        }
    }

    /**
     * Envía múltiples eventos en batch.
     *
     * @param string $pixel_id
     *   ID del pixel.
     * @param string $access_token
     *   Token de acceso.
     * @param array $events
     *   Array de eventos formateados.
     * @param string|null $test_event_code
     *   Código de test.
     *
     * @return array
     *   Resultado con success, code, response.
     */
    public function sendBatch(
        string $pixel_id,
        string $access_token,
        array $events,
        ?string $test_event_code = NULL,
    ): array {
        $url = self::API_BASE . "/{$pixel_id}/events";

        $body = [
            'data' => $events,
        ];

        if ($test_event_code) {
            $body['test_event_code'] = $test_event_code;
        }

        try {
            $response = $this->httpClient->post($url, [
                'query' => ['access_token' => $access_token],
                'json' => $body,
                'timeout' => 30,
                'connect_timeout' => 5,
            ]);

            $code = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), TRUE);

            return [
                'success' => $code >= 200 && $code < 300,
                'code' => $code,
                'response' => $responseBody,
                'events_received' => $responseBody['events_received'] ?? 0,
                'error' => NULL,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

            return [
                'success' => FALSE,
                'code' => $code,
                'events_received' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica las credenciales enviando un evento de test.
     *
     * @param string $pixel_id
     *   ID del pixel.
     * @param string $access_token
     *   Token de acceso.
     *
     * @return array
     *   Resultado de la verificación.
     */
    public function verifyCredentials(string $pixel_id, string $access_token): array
    {
        // Enviar un evento PageView de test.
        $test_event = [
            'event_name' => 'PageView',
            'event_time' => time(),
            'event_id' => 'test_' . uniqid(),
            'event_source_url' => 'https://jaraba.test/pixel-test',
            'action_source' => 'website',
            'user_data' => [
                'client_ip_address' => '127.0.0.1',
                'client_user_agent' => 'JarabaPixelTest/1.0',
            ],
        ];

        // Usar un test_event_code ficticio para que no afecte métricas reales.
        $result = $this->sendEvent($pixel_id, $access_token, $test_event, 'TEST_VERIFY');

        return [
            'valid' => $result['success'],
            'message' => $result['success']
                ? 'Conexión verificada correctamente.'
                : ($result['error'] ?? 'Error de conexión desconocido.'),
            'details' => $result,
        ];
    }

}
