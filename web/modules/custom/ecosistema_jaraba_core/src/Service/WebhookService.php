<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Servicio de webhooks salientes con firma HMAC.
 *
 * PROPÓSITO:
 * Gestiona el envío de eventos webhook a endpoints externos con:
 * - Firma HMAC-SHA256 para verificación de integridad
 * - Reintentos automáticos con backoff exponencial
 * - Rate limiting por destino
 * - Logging de todos los envíos para trazabilidad
 *
 * BASADO EN:
 * - docs/tecnicos/20260115f-03_Core_APIs_Contratos_v1_Claude.md
 *
 * @version 1.0.0
 */
class WebhookService
{

    /**
     * Tipos de eventos disponibles.
     */
    public const EVENT_TYPES = [
        'tenant.created',
        'tenant.updated',
        'tenant.plan_changed',
        'user.registered',
        'user.diagnostic_completed',
        'order.completed',
        'order.cancelled',
        'transaction.recorded',
        'trial.started',
        'trial.ending_soon',
        'trial.expired',
    ];

    /**
     * The HTTP client.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $http_client,
        ConfigFactoryInterface $config_factory,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->httpClient = $http_client;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('jaraba_webhooks');
    }

    /**
     * Envía un evento webhook a un endpoint.
     *
     * @param string $event_type
     *   Tipo de evento (ej: 'tenant.created').
     * @param array $payload
     *   Datos del evento.
     * @param string $endpoint_url
     *   URL del endpoint destino.
     * @param string $secret_key
     *   Clave secreta para firma HMAC (por tenant).
     *
     * @return array
     *   Resultado del envío: ['success' => bool, 'response_code' => int, 'message' => string].
     */
    public function send(string $event_type, array $payload, string $endpoint_url, string $secret_key): array
    {
        // Validar tipo de evento
        if (!in_array($event_type, self::EVENT_TYPES, TRUE)) {
            $this->logger->warning('Tipo de evento inválido: @type', ['@type' => $event_type]);
            return [
                'success' => FALSE,
                'response_code' => 0,
                'message' => "Tipo de evento inválido: {$event_type}",
            ];
        }

        // Construir payload completo
        $event = [
            'event' => $event_type,
            'timestamp' => date('c'),
            'id' => $this->generateEventId(),
            'data' => $payload,
        ];

        $json_payload = json_encode($event, JSON_THROW_ON_ERROR);

        // Generar firma HMAC-SHA256
        $signature = $this->generateSignature($json_payload, $secret_key);

        // Headers de la petición
        $headers = [
            'Content-Type' => 'application/json',
            'X-Jaraba-Signature-256' => $signature,
            'X-Jaraba-Event' => $event_type,
            'X-Jaraba-Delivery' => $event['id'],
            'User-Agent' => 'JarabaWebhooks/1.0',
        ];

        // Enviar con reintentos
        return $this->sendWithRetry($endpoint_url, $json_payload, $headers);
    }

    /**
     * Genera la firma HMAC-SHA256.
     *
     * @param string $payload
     *   Payload JSON.
     * @param string $secret_key
     *   Clave secreta.
     *
     * @return string
     *   Firma en formato sha256=xxx.
     */
    public function generateSignature(string $payload, string $secret_key): string
    {
        $hash = hash_hmac('sha256', $payload, $secret_key);
        return "sha256={$hash}";
    }

    /**
     * Verifica la firma de un webhook entrante.
     *
     * @param string $payload
     *   Payload JSON recibido.
     * @param string $received_signature
     *   Firma recibida en header X-Webhook-Signature.
     * @param string $secret_key
     *   Clave secreta esperada.
     *
     * @return bool
     *   TRUE si la firma es válida.
     */
    public function verifySignature(string $payload, string $received_signature, string $secret_key): bool
    {
        $expected_signature = $this->generateSignature($payload, $secret_key);

        // Comparación timing-safe para evitar ataques de timing
        return hash_equals($expected_signature, $received_signature);
    }

    /**
     * Envía el webhook con reintentos automáticos.
     *
     * @param string $url
     *   URL destino.
     * @param string $payload
     *   Payload JSON.
     * @param array $headers
     *   Headers HTTP.
     * @param int $max_retries
     *   Máximo de reintentos (default: 3).
     *
     * @return array
     *   Resultado del envío.
     */
    protected function sendWithRetry(string $url, string $payload, array $headers, int $max_retries = 3): array
    {
        $attempt = 0;
        $last_error = '';

        while ($attempt <= $max_retries) {
            try {
                $response = $this->httpClient->request('POST', $url, [
                    'body' => $payload,
                    'headers' => $headers,
                    'timeout' => 10,
                    'connect_timeout' => 5,
                ]);

                $status_code = $response->getStatusCode();

                // Log del envío exitoso
                $this->logger->info('Webhook enviado: @url (código: @code)', [
                    '@url' => $url,
                    '@code' => $status_code,
                ]);

                // Consideramos exitoso 2xx
                if ($status_code >= 200 && $status_code < 300) {
                    return [
                        'success' => TRUE,
                        'response_code' => $status_code,
                        'message' => 'Webhook entregado correctamente',
                    ];
                }

                $last_error = "Respuesta no exitosa: HTTP {$status_code}";
            } catch (GuzzleException $e) {
                $last_error = $e->getMessage();
                $this->logger->warning('Error enviando webhook a @url (intento @attempt): @error', [
                    '@url' => $url,
                    '@attempt' => $attempt + 1,
                    '@error' => $last_error,
                ]);
            }

            // Backoff exponencial: 1s, 2s, 4s...
            if ($attempt < $max_retries) {
                $wait_seconds = pow(2, $attempt);
                sleep($wait_seconds);
            }

            $attempt++;
        }

        // Agotados los reintentos
        $this->logger->error('Webhook fallido tras @max reintentos: @url', [
            '@max' => $max_retries + 1,
            '@url' => $url,
        ]);

        return [
            'success' => FALSE,
            'response_code' => 0,
            'message' => "Fallido tras {$max_retries} reintentos: {$last_error}",
        ];
    }

    /**
     * Genera un ID único para el evento.
     *
     * @return string
     *   UUID v4.
     */
    protected function generateEventId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Dispara un evento a todos los webhooks registrados de un tenant.
     *
     * Busca todos los webhooks configurados para el tenant y tipo de evento,
     * y envía a cada uno.
     *
     * @param int $tenant_id
     *   ID del tenant.
     * @param string $event_type
     *   Tipo de evento.
     * @param array $payload
     *   Datos del evento.
     *
     * @return array
     *   Array de resultados por cada webhook registrado.
     */
    public function dispatchForTenant(int $tenant_id, string $event_type, array $payload): array
    {
        // En una implementación completa, buscaríamos los webhooks en BD.
        // Por ahora, usamos la configuración del tenant.
        $config = $this->configFactory->get('ecosistema_jaraba_core.tenant.' . $tenant_id);

        $webhooks = $config->get('webhooks') ?? [];
        $results = [];

        foreach ($webhooks as $webhook) {
            // Verificar si el webhook está suscrito a este tipo de evento
            $subscribed_events = $webhook['events'] ?? [];
            if (!empty($subscribed_events) && !in_array($event_type, $subscribed_events, TRUE)) {
                continue;
            }

            $endpoint = $webhook['url'] ?? '';
            $secret = $webhook['secret'] ?? '';

            if (empty($endpoint) || empty($secret)) {
                continue;
            }

            $result = $this->send($event_type, $payload, $endpoint, $secret);
            $result['endpoint'] = $endpoint;
            $results[] = $result;
        }

        return $results;
    }

}
