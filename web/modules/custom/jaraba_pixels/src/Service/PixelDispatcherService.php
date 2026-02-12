<?php

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jaraba_ads\Service\ConversionTrackingService;
use Drupal\jaraba_analytics\Service\ConsentService;
use Drupal\jaraba_pixels\Client\LinkedInCapiClient;
use Drupal\jaraba_pixels\Client\TikTokEventsClient;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio principal para dispatch de eventos a pixels de marketing.
 *
 * Orquesta el envío de eventos a Meta CAPI, Google Measurement Protocol,
 * y otras plataformas, respetando el consentimiento del usuario.
 */
class PixelDispatcherService
{

    /**
     * Conexión a base de datos.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Servicio de mapeo de eventos.
     *
     * @var \Drupal\jaraba_pixels\Service\EventMapperService
     */
    protected EventMapperService $eventMapper;

    /**
     * Gestor de credenciales.
     *
     * @var \Drupal\jaraba_pixels\Service\CredentialManagerService
     */
    protected CredentialManagerService $credentialManager;

    /**
     * Servicio de consentimiento GDPR.
     *
     * @var \Drupal\jaraba_analytics\Service\ConsentService
     */
    protected ConsentService $consentService;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Cliente HTTP.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * Servicio de tracking de conversiones offline de jaraba_ads.
     *
     * @var \Drupal\jaraba_ads\Service\ConversionTrackingService|null
     */
    protected ?ConversionTrackingService $conversionTracking;

    /**
     * Constructor.
     */
    public function __construct(
        Connection $database,
        EventMapperService $event_mapper,
        CredentialManagerService $credential_manager,
        ConsentService $consent_service,
        $logger_factory,
        ClientInterface $http_client,
        ?ConversionTrackingService $conversion_tracking = NULL,
    ) {
        $this->database = $database;
        $this->eventMapper = $event_mapper;
        $this->credentialManager = $credential_manager;
        $this->consentService = $consent_service;
        $this->logger = $logger_factory->get('jaraba_pixels');
        $this->httpClient = $http_client;
        $this->conversionTracking = $conversion_tracking;
    }

    /**
     * Dispatch un evento de analytics a todas las plataformas configuradas.
     *
     * @param \Drupal\Core\Entity\EntityInterface $analytics_event
     *   Entidad del evento de analytics.
     */
    public function dispatch(EntityInterface $analytics_event): void
    {
        // Extraer datos del evento.
        $data = $this->extractEventData($analytics_event);
        if (empty($data)) {
            return;
        }

        $tenant_id = $data['tenant_id'] ?? NULL;
        if (!$tenant_id) {
            return;
        }

        // Verificar consentimiento de marketing.
        if (!$this->hasMarketingConsent($data['visitor_id'] ?? '', $tenant_id)) {
            $this->logEvent($tenant_id, 'all', $data['event_id'], $data['event_type'], '', 'skipped', NULL, 'No marketing consent');
            return;
        }

        // Obtener credenciales habilitadas.
        $credentials = $this->credentialManager->getEnabledCredentials($tenant_id);
        if (empty($credentials)) {
            return;
        }

        // Dispatch a cada plataforma.
        foreach ($credentials as $platform => $credential) {
            $this->dispatchToPlatform($platform, $credential, $data);
        }

        // Reenviar eventos de conversión al módulo de ads para ROAS tracking.
        $this->forwardConversionToAds($data);
    }

    /**
     * Dispatch a una plataforma específica.
     *
     * @param string $platform
     *   Plataforma destino.
     * @param array $credential
     *   Credenciales de la plataforma.
     * @param array $data
     *   Datos del evento.
     */
    protected function dispatchToPlatform(string $platform, array $credential, array $data): void
    {
        $event_id = $data['event_id'] ?? $this->generateEventId();
        $platform_event = $this->eventMapper->mapEvent($data['event_type'], $platform);

        if (!$platform_event) {
            // Evento no mapeado para esta plataforma.
            $this->logEvent(
                $data['tenant_id'],
                $platform,
                $event_id,
                $data['event_type'],
                '',
                'skipped',
                NULL,
                'Event not mapped for platform'
            );
            return;
        }

        try {
            $result = match ($platform) {
                'meta' => $this->sendToMeta($credential, $data, $event_id),
                'google' => $this->sendToGoogle($credential, $data),
                'linkedin' => $this->sendToLinkedIn($credential, $data, $event_id),
                'tiktok' => $this->sendToTikTok($credential, $data, $event_id),
                default => ['success' => FALSE, 'code' => 0, 'error' => 'Platform not supported'],
            };

            $status = $result['success'] ? 'sent' : 'failed';
            $this->logEvent(
                $data['tenant_id'],
                $platform,
                $event_id,
                $data['event_type'],
                $platform_event,
                $status,
                $result['code'] ?? NULL,
                $result['error'] ?? NULL
            );

            if (!$result['success']) {
                $this->logger->warning('Pixel dispatch failed for @platform: @error', [
                    '@platform' => $platform,
                    '@error' => $result['error'] ?? 'Unknown error',
                ]);
            }
        } catch (\Exception $e) {
            $this->logEvent(
                $data['tenant_id'],
                $platform,
                $event_id,
                $data['event_type'],
                $platform_event,
                'failed',
                500,
                $e->getMessage()
            );
            $this->logger->error('Exception during pixel dispatch: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envía evento a Meta CAPI.
     *
     * @param array $credential
     *   Credenciales de Meta.
     * @param array $data
     *   Datos del evento.
     * @param string $event_id
     *   ID del evento para deduplicación.
     *
     * @return array
     *   Resultado con success, code, error.
     */
    protected function sendToMeta(array $credential, array $data, string $event_id): array
    {
        $pixel_id = $credential['pixel_id'];
        $access_token = $credential['access_token'];

        if (empty($pixel_id) || empty($access_token)) {
            return ['success' => FALSE, 'code' => 0, 'error' => 'Missing credentials'];
        }

        $payload = $this->eventMapper->formatMetaPayload($data, $event_id);
        if (empty($payload)) {
            return ['success' => FALSE, 'code' => 0, 'error' => 'Failed to format payload'];
        }

        // Construir el request body.
        $body = [
            'data' => [$payload],
        ];

        // Añadir test_event_code si está en modo test.
        if (!empty($credential['test_mode']) && !empty($credential['test_event_code'])) {
            $body['test_event_code'] = $credential['test_event_code'];
        }

        $url = "https://graph.facebook.com/v18.0/{$pixel_id}/events";

        try {
            $response = $this->httpClient->post($url, [
                'query' => ['access_token' => $access_token],
                'json' => $body,
                'timeout' => 10,
            ]);

            $code = $response->getStatusCode();
            return [
                'success' => $code >= 200 && $code < 300,
                'code' => $code,
                'error' => NULL,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            return [
                'success' => FALSE,
                'code' => $code,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envía evento a Google Measurement Protocol.
     *
     * @param array $credential
     *   Credenciales de Google.
     * @param array $data
     *   Datos del evento.
     *
     * @return array
     *   Resultado con success, code, error.
     */
    protected function sendToGoogle(array $credential, array $data): array
    {
        $measurement_id = $credential['pixel_id']; // Usamos pixel_id para almacenar measurement_id.
        $api_secret = $credential['api_secret'];

        if (empty($measurement_id) || empty($api_secret)) {
            return ['success' => FALSE, 'code' => 0, 'error' => 'Missing credentials'];
        }

        // Client ID del visitante.
        $client_id = $data['visitor_id'] ?? $this->generateEventId();

        $payload = $this->eventMapper->formatGooglePayload($data, $client_id);
        if (empty($payload)) {
            return ['success' => FALSE, 'code' => 0, 'error' => 'Failed to format payload'];
        }

        $url = 'https://www.google-analytics.com/mp/collect';

        try {
            $response = $this->httpClient->post($url, [
                'query' => [
                    'measurement_id' => $measurement_id,
                    'api_secret' => $api_secret,
                ],
                'json' => $payload,
                'timeout' => 10,
            ]);

            $code = $response->getStatusCode();
            // Google MP devuelve 204 No Content en éxito.
            return [
                'success' => $code >= 200 && $code < 300,
                'code' => $code,
                'error' => NULL,
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $code = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            return [
                'success' => FALSE,
                'code' => $code,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extrae datos relevantes de un evento de analytics.
     *
     * @param \Drupal\Core\Entity\EntityInterface $event
     *   Entidad del evento.
     *
     * @return array
     *   Datos extraídos.
     */
    protected function extractEventData(EntityInterface $event): array
    {
        // Verificar que es una entidad de analytics.
        if ($event->getEntityTypeId() !== 'analytics_event') {
            return [];
        }

        return [
            'event_id' => $event->get('event_id')->value ?? $this->generateEventId(),
            'event_type' => $event->get('event_type')->value ?? 'page_view',
            'tenant_id' => (int) $event->get('tenant_id')->value,
            'visitor_id' => $event->get('visitor_id')->value ?? '',
            'session_id' => $event->get('session_id')->value ?? '',
            'page_url' => $event->get('page_url')->value ?? '',
            'page_title' => $event->get('page_title')->value ?? '',
            'referrer' => $event->get('referrer')->value ?? '',
            'user_agent' => $event->get('user_agent')->value ?? '',
            'ip_address' => $event->get('ip_hash')->value ?? '',
            'timestamp' => $event->get('created')->value ?? time(),
            'value' => $event->get('value')->value ?? NULL,
            'currency' => $event->get('currency')->value ?? 'EUR',
            'items' => $this->parseJsonField($event, 'items'),
            'custom_data' => $this->parseJsonField($event, 'custom_data'),
        ];
    }

    /**
     * Parsea un campo JSON de la entidad.
     */
    protected function parseJsonField(EntityInterface $event, string $field): array
    {
        if (!$event->hasField($field)) {
            return [];
        }
        $value = $event->get($field)->value ?? '';
        if (empty($value)) {
            return [];
        }
        try {
            return json_decode($value, TRUE) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Verifica si el visitante tiene consentimiento de marketing.
     *
     * Consulta el ConsentService de jaraba_analytics para verificar
     * si el visitante ha otorgado consentimiento para marketing.
     *
     * @param string $visitor_id
     *   ID único del visitante.
     * @param int $tenant_id
     *   ID del tenant (no usado actualmente, reservado para multi-tenant).
     *
     * @return bool
     *   TRUE si tiene consentimiento de marketing.
     */
    protected function hasMarketingConsent(string $visitor_id, int $tenant_id): bool
    {
        if (empty($visitor_id)) {
            return FALSE;
        }

        try {
            return $this->consentService->hasConsent($visitor_id, 'marketing');
        } catch (\Exception $e) {
            // En caso de error, no enviar (fail-safe para GDPR).
            $this->logger->warning('Error checking consent: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
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
     * Registra un evento en el log.
     */
    protected function logEvent(
        int $tenant_id,
        string $platform,
        string $event_id,
        string $event_type,
        string $platform_event,
        string $status,
        ?int $response_code,
        ?string $error_message,
    ): void {
        try {
            $this->database->insert('pixel_event_log')
                ->fields([
                    'tenant_id' => $tenant_id,
                    'platform' => $platform,
                    'event_id' => $event_id,
                    'event_type' => $event_type,
                    'platform_event' => $platform_event,
                    'status' => $status,
                    'response_code' => $response_code,
                    'error_message' => $error_message ? substr($error_message, 0, 500) : NULL,
                    'payload_hash' => hash('sha256', $event_id . $event_type),
                    'created' => time(),
                ])
                ->execute();
        } catch (\Exception $e) {
            // Log silently fails - not critical.
        }
    }

    /**
     * Obtiene estadísticas de eventos enviados.
     *
     * @param int $tenant_id
     *   ID del tenant.
     * @param int $days
     *   Número de días a consultar.
     *
     * @return array
     *   Estadísticas agregadas.
     */
    public function getStats(int $tenant_id, int $days = 7): array
    {
        $since = strtotime("-{$days} days");

        // Total por plataforma y estado.
        $results = $this->database->query("
      SELECT platform, status, COUNT(*) as count
      FROM {pixel_event_log}
      WHERE tenant_id = :tenant_id AND created > :since
      GROUP BY platform, status
    ", [':tenant_id' => $tenant_id, ':since' => $since])->fetchAll();

        $stats = [
            'by_platform' => [],
            'by_status' => [
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
            ],
            'total' => 0,
        ];

        foreach ($results as $row) {
            if (!isset($stats['by_platform'][$row->platform])) {
                $stats['by_platform'][$row->platform] = [
                    'sent' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'total' => 0,
                ];
            }
            $stats['by_platform'][$row->platform][$row->status] = (int) $row->count;
            $stats['by_platform'][$row->platform]['total'] += (int) $row->count;
            $stats['by_status'][$row->status] = ($stats['by_status'][$row->status] ?? 0) + (int) $row->count;
            $stats['total'] += (int) $row->count;
        }

        return $stats;
    }

    /**
     * Envía evento a LinkedIn Conversions API.
     *
     * @param array $credential
     *   Credenciales de LinkedIn.
     * @param array $data
     *   Datos del evento.
     * @param string $event_id
     *   ID del evento para deduplicación.
     *
     * @return array
     *   Resultado con success, code, error.
     */
    protected function sendToLinkedIn(array $credential, array $data, string $event_id): array
    {
        $partner_id = $credential['pixel_id'];
        $access_token = $credential['access_token'];

        if (empty($partner_id) || empty($access_token)) {
            return ['success' => FALSE, 'code' => 0, 'error' => 'Missing credentials'];
        }

        $event_data = [
            'event_id' => $event_id,
            'timestamp' => $data['timestamp'] ?? time(),
            'value' => $data['value'] ?? 0,
            'currency' => $data['currency'] ?? 'EUR',
            'conversion' => $this->eventMapper->mapEvent($data['event_type'], 'linkedin'),
        ];

        // Añadir datos de usuario si están disponibles.
        if (!empty($data['email']) || !empty($data['user_id'])) {
            $event_data['user_data'] = [
                'email' => $data['email'] ?? '',
                'user_id' => $data['user_id'] ?? '',
            ];
        }

        try {
            /** @var \Drupal\jaraba_pixels\Client\LinkedInCapiClient $client */
            $client = \Drupal::service('jaraba_pixels.linkedin_client');
            $result = $client->sendEvent(
                $partner_id,
                $access_token,
                $event_data,
                !empty($credential['test_mode'])
            );

            return [
                'success' => $result['success'] ?? FALSE,
                'code' => $result['code'] ?? 0,
                'error' => $result['error'] ?? NULL,
            ];
        } catch (\Exception $e) {
            return [
                'success' => FALSE,
                'code' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Envía evento a TikTok Events API.
     *
     * @param array $credential
     *   Credenciales de TikTok.
     * @param array $data
     *   Datos del evento.
     * @param string $event_id
     *   ID del evento para deduplicación.
     *
     * @return array
     *   Resultado con success, code, error.
     */
    protected function sendToTikTok(array $credential, array $data, string $event_id): array
    {
        $pixel_code = $credential['pixel_id'];
        $access_token = $credential['access_token'];

        if (empty($pixel_code) || empty($access_token)) {
            return ['success' => FALSE, 'code' => 0, 'error' => 'Missing credentials'];
        }

        $event_data = [
            'event_id' => $event_id,
            'event' => $this->eventMapper->mapEvent($data['event_type'], 'tiktok'),
            'timestamp' => $data['timestamp'] ?? time(),
            'page_url' => $data['page_url'] ?? '',
            'value' => $data['value'] ?? 0,
            'currency' => $data['currency'] ?? 'EUR',
        ];

        // Añadir datos de usuario si están disponibles.
        if (!empty($data['email']) || !empty($data['visitor_id'])) {
            $event_data['user_data'] = [
                'email' => $data['email'] ?? '',
                'external_id' => $data['visitor_id'] ?? '',
                'ip' => $data['ip_address'] ?? '',
                'user_agent' => $data['user_agent'] ?? '',
            ];
        }

        try {
            /** @var \Drupal\jaraba_pixels\Client\TikTokEventsClient $client */
            $client = \Drupal::service('jaraba_pixels.tiktok_client');
            $result = $client->sendEvent(
                $pixel_code,
                $access_token,
                $event_data,
                !empty($credential['test_mode'])
            );

            return [
                'success' => $result['success'] ?? FALSE,
                'code' => $result['code'] ?? 0,
                'error' => $result['error'] ?? NULL,
            ];
        } catch (\Exception $e) {
            return [
                'success' => FALSE,
                'code' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reenvía eventos de conversión al módulo de ads para ROAS tracking.
     *
     * LÓGICA: Si el evento es un tipo de conversión (purchase, add_to_cart,
     *   complete_registration, etc.), lo registra en el ConversionTrackingService
     *   de jaraba_ads para que pueda calcular ROAS por campaña.
     *
     * @param array $data
     *   Datos del evento de analytics.
     */
    protected function forwardConversionToAds(array $data): void
    {
        if (!$this->conversionTracking) {
            return;
        }

        $conversionEvents = [
            'purchase',
            'add_to_cart',
            'complete_registration',
            'subscribe',
            'start_trial',
            'lead',
        ];

        $eventType = $data['event_type'] ?? '';
        if (!in_array($eventType, $conversionEvents, TRUE)) {
            return;
        }

        try {
            $tenantId = (int) ($data['tenant_id'] ?? 0);
            if ($tenantId <= 0) {
                return;
            }

            $this->conversionTracking->recordConversion($tenantId, 'pixel', [
                'event_name' => $eventType,
                'event_time' => $data['timestamp'] ?? time(),
                'email_hash' => !empty($data['ip_address']) ? hash('sha256', $data['ip_address']) : '',
                'conversion_value' => $data['value'] ?? NULL,
                'currency' => $data['currency'] ?? 'EUR',
                'order_id' => $data['event_id'] ?? '',
            ]);
        }
        catch (\Exception $e) {
            // No bloquear el dispatch por errores de ads tracking.
            $this->logger->warning('Error reenviando conversión a ads: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
