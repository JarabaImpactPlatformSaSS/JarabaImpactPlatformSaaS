<?php

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Servicio para mapear eventos internos a formato de cada plataforma.
 *
 * Implementa el mapeo según Spec 178 para garantizar compatibilidad
 * con Meta CAPI, Google Measurement Protocol, LinkedIn y TikTok.
 */
class EventMapperService
{
    use StringTranslationTrait;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
     *   El servicio de traducción.
     */
    public function __construct(TranslationInterface $string_translation)
    {
        $this->setStringTranslation($string_translation);
    }

    /**
     * Mapeo de eventos internos a Meta (CAPI/Pixel).
     */
    protected const META_EVENT_MAP = [
        'page_view' => 'PageView',
        'lead' => 'Lead',
        'signup' => 'CompleteRegistration',
        'add_to_cart' => 'AddToCart',
        'view_content' => 'ViewContent',
        'initiate_checkout' => 'InitiateCheckout',
        'add_payment_info' => 'AddPaymentInfo',
        'purchase' => 'Purchase',
        'search' => 'Search',
        'contact' => 'Contact',
        'subscribe' => 'Subscribe',
        'start_trial' => 'StartTrial',
        'complete_registration' => 'CompleteRegistration',
    ];

    /**
     * Mapeo de eventos internos a Google (GA4/Ads).
     */
    protected const GOOGLE_EVENT_MAP = [
        'page_view' => 'page_view',
        'lead' => 'generate_lead',
        'signup' => 'sign_up',
        'add_to_cart' => 'add_to_cart',
        'view_content' => 'view_item',
        'initiate_checkout' => 'begin_checkout',
        'add_payment_info' => 'add_payment_info',
        'purchase' => 'purchase',
        'search' => 'search',
        'contact' => 'generate_lead',
        'subscribe' => 'sign_up',
        'start_trial' => 'sign_up',
        'complete_registration' => 'sign_up',
    ];

    /**
     * Mapeo de eventos internos a LinkedIn.
     */
    protected const LINKEDIN_EVENT_MAP = [
        'page_view' => 'PageView',
        'lead' => 'Lead',
        'signup' => 'SignUp',
        'purchase' => 'Conversion',
    ];

    /**
     * Mapeo de eventos internos a TikTok.
     */
    protected const TIKTOK_EVENT_MAP = [
        'page_view' => 'PageView',
        'lead' => 'SubmitForm',
        'signup' => 'Registration',
        'add_to_cart' => 'AddToCart',
        'purchase' => 'PlaceAnOrder',
    ];

    /**
     * Obtiene el nombre del evento para una plataforma específica.
     *
     * @param string $internal_event
     *   Nombre del evento interno.
     * @param string $platform
     *   Plataforma destino (meta, google, linkedin, tiktok).
     *
     * @return string|null
     *   Nombre del evento en la plataforma o NULL si no está mapeado.
     */
    public function mapEvent(string $internal_event, string $platform): ?string
    {
        $map = $this->getEventMap($platform);
        return $map[$internal_event] ?? NULL;
    }

    /**
     * Obtiene el mapeo completo para una plataforma.
     *
     * @param string $platform
     *   Plataforma destino.
     *
     * @return array
     *   Array de mapeo [interno => plataforma].
     */
    public function getEventMap(string $platform): array
    {
        return match ($platform) {
            'meta' => self::META_EVENT_MAP,
            'google' => self::GOOGLE_EVENT_MAP,
            'linkedin' => self::LINKEDIN_EVENT_MAP,
            'tiktok' => self::TIKTOK_EVENT_MAP,
            default => [],
        };
    }

    /**
     * Transforma el payload del evento para Meta CAPI.
     *
     * @param array $analytics_data
     *   Datos del evento de analytics.
     * @param string $event_id
     *   UUID del evento para deduplicación.
     *
     * @return array
     *   Payload formateado para Meta CAPI.
     */
    public function formatMetaPayload(array $analytics_data, string $event_id): array
    {
        $event_name = $this->mapEvent($analytics_data['event_type'] ?? 'page_view', 'meta');
        if (!$event_name) {
            return [];
        }

        return [
            'event_name' => $event_name,
            'event_time' => $analytics_data['timestamp'] ?? time(),
            'event_id' => $event_id,
            'event_source_url' => $analytics_data['page_url'] ?? '',
            'action_source' => 'website',
            'user_data' => $this->formatMetaUserData($analytics_data),
            'custom_data' => $this->formatMetaCustomData($analytics_data),
        ];
    }

    /**
     * Formatea user_data para Meta (con hashing).
     */
    protected function formatMetaUserData(array $data): array
    {
        $user_data = [];

        // Hash de email según requisitos de Meta.
        if (!empty($data['user_email'])) {
            $user_data['em'] = [hash('sha256', strtolower(trim($data['user_email'])))];
        }

        // Hash de IP (primeros 3 octetos para GDPR).
        if (!empty($data['ip_address'])) {
            $user_data['client_ip_address'] = $data['ip_address'];
        }

        // User agent.
        if (!empty($data['user_agent'])) {
            $user_data['client_user_agent'] = $data['user_agent'];
        }

        // FBC y FBP cookies si existen.
        if (!empty($data['fbc'])) {
            $user_data['fbc'] = $data['fbc'];
        }
        if (!empty($data['fbp'])) {
            $user_data['fbp'] = $data['fbp'];
        }

        return $user_data;
    }

    /**
     * Formatea custom_data para Meta.
     */
    protected function formatMetaCustomData(array $data): array
    {
        $custom_data = [];

        // Valor de conversión.
        if (!empty($data['value'])) {
            $custom_data['value'] = (float) $data['value'];
            $custom_data['currency'] = $data['currency'] ?? 'EUR';
        }

        // Contenido visualizado/comprado.
        if (!empty($data['items'])) {
            $custom_data['contents'] = array_map(function ($item) {
                return [
                    'id' => $item['id'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'item_price' => $item['price'] ?? 0,
                ];
            }, $data['items']);
            $custom_data['num_items'] = count($data['items']);
        }

        // Content IDs.
        if (!empty($data['content_ids'])) {
            $custom_data['content_ids'] = $data['content_ids'];
        }

        // Content type.
        if (!empty($data['content_type'])) {
            $custom_data['content_type'] = $data['content_type'];
        }

        return $custom_data;
    }

    /**
     * Transforma el payload del evento para Google Measurement Protocol.
     *
     * @param array $analytics_data
     *   Datos del evento de analytics.
     * @param string $client_id
     *   Client ID del usuario.
     *
     * @return array
     *   Payload formateado para Google MP.
     */
    public function formatGooglePayload(array $analytics_data, string $client_id): array
    {
        $event_name = $this->mapEvent($analytics_data['event_type'] ?? 'page_view', 'google');
        if (!$event_name) {
            return [];
        }

        $params = [
            'page_location' => $analytics_data['page_url'] ?? '',
            'page_title' => $analytics_data['page_title'] ?? '',
        ];

        // Parámetros de e-commerce.
        if (!empty($analytics_data['value'])) {
            $params['value'] = (float) $analytics_data['value'];
            $params['currency'] = $analytics_data['currency'] ?? 'EUR';
        }

        if (!empty($analytics_data['items'])) {
            $params['items'] = array_map(function ($item) {
                return [
                    'item_id' => $item['id'] ?? '',
                    'item_name' => $item['name'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'price' => $item['price'] ?? 0,
                ];
            }, $analytics_data['items']);
        }

        return [
            'client_id' => $client_id,
            'events' => [
                [
                    'name' => $event_name,
                    'params' => $params,
                ],
            ],
        ];
    }

    /**
     * Lista de plataformas soportadas.
     *
     * @return array
     *   Array con info de cada plataforma.
     */
    public function getSupportedPlatforms(): array
    {
        return [
            'meta' => [
                'name' => 'Meta (Facebook/Instagram)',
                'icon' => 'facebook',
                'description' => $this->t('Conversions API para Facebook e Instagram Ads.'),
                'requires' => ['pixel_id', 'access_token'],
            ],
            'google' => [
                'name' => 'Google (Ads/GA4)',
                'icon' => 'google',
                'description' => $this->t('Measurement Protocol para Google Ads y Analytics 4.'),
                'requires' => ['measurement_id', 'api_secret'],
            ],
            'linkedin' => [
                'name' => 'LinkedIn',
                'icon' => 'linkedin',
                'description' => $this->t('Conversions API para LinkedIn Ads.'),
                'requires' => ['partner_id', 'access_token'],
            ],
            'tiktok' => [
                'name' => 'TikTok',
                'icon' => 'tiktok',
                'description' => $this->t('Events API para TikTok Ads.'),
                'requires' => ['pixel_code', 'access_token'],
            ],
        ];
    }

}
