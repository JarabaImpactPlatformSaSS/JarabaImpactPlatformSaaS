<?php

namespace Drupal\jaraba_pixels\Client;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para Google Measurement Protocol (GA4).
 *
 * Envía eventos server-side a Google Analytics 4 y Google Ads
 * para tracking preciso que evita ad-blockers.
 */
class GoogleMeasurementClient
{

    /**
     * URL de producción del Measurement Protocol.
     */
    protected const API_URL = 'https://www.google-analytics.com/mp/collect';

    /**
     * URL de debug del Measurement Protocol.
     */
    protected const DEBUG_URL = 'https://www.google-analytics.com/debug/mp/collect';

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
        $this->logger = $logger_factory->get('jaraba_pixels.google');
    }

    /**
     * Envía un evento a Google Measurement Protocol.
     *
     * @param string $measurement_id
     *   ID de medición (G-XXXXXXX).
     * @param string $api_secret
     *   API secret del stream.
     * @param string $client_id
     *   Client ID del usuario.
     * @param array $event
     *   Evento con name y params.
     * @param bool $debug
     *   Si debe usar el endpoint de debug.
     *
     * @return array
     *   Resultado con success, code, response.
     */
    public function sendEvent(
        string $measurement_id,
        string $api_secret,
        string $client_id,
        array $event,
        bool $debug = FALSE,
    ): array {
        $url = $debug ? self::DEBUG_URL : self::API_URL;

        $payload = [
            'client_id' => $client_id,
            'events' => [$event],
        ];

        try {
            $response = $this->httpClient->post($url, [
                'query' => [
                    'measurement_id' => $measurement_id,
                    'api_secret' => $api_secret,
                ],
                'json' => $payload,
                'timeout' => 10,
                'connect_timeout' => 5,
            ]);

            $code = $response->getStatusCode();
            $responseBody = NULL;

            // En modo debug, Google devuelve información de validación.
            if ($debug) {
                $responseBody = json_decode($response->getBody()->getContents(), TRUE);
            }

            // El endpoint de producción devuelve 204 No Content en éxito.
            return [
                'success' => $code >= 200 && $code < 300,
                'code' => $code,
                'response' => $responseBody,
                'error' => NULL,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

            $this->logger->warning('Google MP request failed: @message', [
                '@message' => $e->getMessage(),
            ]);

            return [
                'success' => FALSE,
                'code' => $code,
                'response' => NULL,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envía múltiples eventos.
     *
     * @param string $measurement_id
     *   ID de medición.
     * @param string $api_secret
     *   API secret.
     * @param string $client_id
     *   Client ID.
     * @param array $events
     *   Array de eventos.
     *
     * @return array
     *   Resultado.
     */
    public function sendBatch(
        string $measurement_id,
        string $api_secret,
        string $client_id,
        array $events,
    ): array {
        $payload = [
            'client_id' => $client_id,
            'events' => $events,
        ];

        try {
            $response = $this->httpClient->post(self::API_URL, [
                'query' => [
                    'measurement_id' => $measurement_id,
                    'api_secret' => $api_secret,
                ],
                'json' => $payload,
                'timeout' => 30,
                'connect_timeout' => 5,
            ]);

            $code = $response->getStatusCode();

            return [
                'success' => $code >= 200 && $code < 300,
                'code' => $code,
                'events_sent' => count($events),
                'error' => NULL,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return [
                'success' => FALSE,
                'code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                'events_sent' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica las credenciales usando el endpoint de debug.
     *
     * @param string $measurement_id
     *   ID de medición.
     * @param string $api_secret
     *   API secret.
     *
     * @return array
     *   Resultado de la verificación.
     */
    public function verifyCredentials(string $measurement_id, string $api_secret): array
    {
        // Enviar un evento de test al endpoint de debug.
        $test_event = [
            'name' => 'page_view',
            'params' => [
                'page_location' => 'https://jaraba.test/pixel-test',
                'page_title' => 'Pixel Test Page',
                'debug_mode' => TRUE,
            ],
        ];

        $result = $this->sendEvent(
            $measurement_id,
            $api_secret,
            'test_client_' . uniqid(),
            $test_event,
            TRUE // Usar endpoint de debug.
        );

        // Analizar la respuesta de validación.
        $valid = $result['success'];
        $message = 'Conexión verificada correctamente.';

        if (!$valid) {
            $message = $result['error'] ?? 'Error de conexión desconocido.';
        } elseif (!empty($result['response']['validationMessages'])) {
            // El debug endpoint devuelve mensajes de validación.
            $validationMessages = $result['response']['validationMessages'];
            if (!empty($validationMessages)) {
                $valid = FALSE;
                $message = $validationMessages[0]['description'] ?? 'Error de validación.';
            }
        }

        return [
            'valid' => $valid,
            'message' => $message,
            'details' => $result,
        ];
    }

}
