<?php

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio principal de Analytics.
 *
 * Proporciona métodos para tracking de eventos, consulta de métricas,
 * y generación de reportes.
 */
class AnalyticsService
{

    /**
     * Conexión a base de datos.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected Connection $database;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Usuario actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Request stack.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * Cache backend.
     *
     * @var \Drupal\Core\Cache\CacheBackendInterface
     */
    protected CacheBackendInterface $cache;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

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
        Connection $database,
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        RequestStack $request_stack,
        CacheBackendInterface $cache,
        $logger_factory,
        TenantContextService $tenant_context,
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->requestStack = $request_stack;
        $this->cache = $cache;
        $this->logger = $logger_factory->get('jaraba_analytics');
        $this->tenantContext = $tenant_context;
    }

    /**
     * Registra un evento de analytics.
     *
     * @param string $event_type
     *   Tipo de evento (page_view, add_to_cart, purchase, etc.).
     * @param array $data
     *   Datos específicos del evento.
     * @param int|null $tenant_id
     *   ID del tenant. Si es null, se detecta del contexto.
     *
     * @return \Drupal\jaraba_analytics\Entity\AnalyticsEvent|null
     *   La entidad creada o null si falla.
     */
    public function trackEvent(string $event_type, array $data, ?int $tenant_id = NULL)
    {
        try {
            $request = $this->requestStack->getCurrentRequest();

            // Detectar tenant si no se proporciona.
            if ($tenant_id === NULL) {
                $tenant_id = $this->detectTenantId();
            }

            // Extraer información del user agent.
            $userAgent = $request?->headers->get('User-Agent') ?? '';
            $deviceInfo = $this->parseUserAgent($userAgent);

            // Generar o recuperar IDs de sesión/visitante.
            $sessionId = $this->getSessionId();
            $visitorId = $this->getVisitorId();

            // Crear la entidad.
            $storage = $this->entityTypeManager->getStorage('analytics_event');
            $event = $storage->create([
                'tenant_id' => $tenant_id,
                'event_type' => $event_type,
                'event_data' => $data,
                'user_id' => $this->currentUser->isAuthenticated() ? $this->currentUser->id() : NULL,
                'session_id' => $sessionId,
                'visitor_id' => $visitorId,
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'referrer' => $request?->headers->get('Referer'),
                'page_url' => $data['page_url'] ?? $request?->getUri(),
                'utm_source' => $data['utm_source'] ?? $request?->query->get('utm_source'),
                'utm_medium' => $data['utm_medium'] ?? $request?->query->get('utm_medium'),
                'utm_campaign' => $data['utm_campaign'] ?? $request?->query->get('utm_campaign'),
                'utm_content' => $data['utm_content'] ?? $request?->query->get('utm_content'),
                'utm_term' => $data['utm_term'] ?? $request?->query->get('utm_term'),
                'ip_hash' => $this->hashIp($request?->getClientIp()),
                'country' => $data['country'] ?? NULL,
                'region' => $data['region'] ?? NULL,
            ]);

            $event->save();

            return $event;
        } catch (\Exception $e) {
            $this->logger->error('Error tracking event: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Registra un page view.
     *
     * @param array $data
     *   Datos adicionales del page view.
     *
     * @return \Drupal\jaraba_analytics\Entity\AnalyticsEvent|null
     *   La entidad creada.
     */
    public function trackPageView(array $data = [])
    {
        $request = $this->requestStack->getCurrentRequest();
        $data['page_url'] = $data['page_url'] ?? $request?->getUri();
        $data['page_title'] = $data['page_title'] ?? '';

        return $this->trackEvent('page_view', $data);
    }

    /**
     * Registra un evento e-commerce.
     *
     * @param string $event
     *   Tipo de evento (add_to_cart, purchase, etc.).
     * @param array $items
     *   Items del carrito/pedido.
     * @param array $extra
     *   Datos adicionales (order_id, value, etc.).
     *
     * @return \Drupal\jaraba_analytics\Entity\AnalyticsEvent|null
     *   La entidad creada.
     */
    public function trackEcommerceEvent(string $event, array $items, array $extra = [])
    {
        $data = array_merge([
            'items' => $items,
            'item_count' => count($items),
        ], $extra);

        return $this->trackEvent($event, $data);
    }

    /**
     * Obtiene métricas diarias para un tenant.
     *
     * @param int $tenant_id
     *   ID del tenant.
     * @param string $start_date
     *   Fecha inicio (Y-m-d).
     * @param string $end_date
     *   Fecha fin (Y-m-d).
     *
     * @return array
     *   Array de métricas diarias.
     */
    public function getDailyMetrics(int $tenant_id, string $start_date, string $end_date): array
    {
        $cacheKey = "analytics_daily:{$tenant_id}:{$start_date}:{$end_date}";

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached->data;
        }

        $storage = $this->entityTypeManager->getStorage('analytics_daily');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('tenant_id', $tenant_id)
            ->condition('date', $start_date, '>=')
            ->condition('date', $end_date, '<=')
            ->sort('date', 'ASC');

        $ids = $query->execute();
        $entities = $storage->loadMultiple($ids);

        $metrics = [];
        foreach ($entities as $entity) {
            $metrics[] = [
                'date' => $entity->get('date')->value,
                'page_views' => (int) $entity->get('page_views')->value,
                'unique_visitors' => (int) $entity->get('unique_visitors')->value,
                'sessions' => (int) $entity->get('sessions')->value,
                'bounce_rate' => (float) $entity->get('bounce_rate')->value,
                'conversion_rate' => (float) $entity->get('conversion_rate')->value,
                'total_revenue' => (float) $entity->get('total_revenue')->value,
            ];
        }

        // Cache por 5 minutos.
        $this->cache->set($cacheKey, $metrics, time() + 300);

        return $metrics;
    }

    /**
     * Obtiene el número de visitantes en tiempo real.
     *
     * @param int $tenant_id
     *   ID del tenant.
     *
     * @return int
     *   Número de visitantes activos (últimos 5 minutos).
     */
    public function getRealtimeVisitors(int $tenant_id): int
    {
        $threshold = time() - 300;

        $count = $this->database->select('analytics_event', 'ae')
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.created', $threshold, '>=')
            ->countQuery()
            ->execute()
            ->fetchField();

        return (int) $count;
    }

    /**
     * Obtiene las top páginas por visitas.
     *
     * @param int $tenant_id
     *   ID del tenant.
     * @param int $limit
     *   Número máximo de resultados.
     * @param string $start_date
     *   Fecha inicio.
     * @param string $end_date
     *   Fecha fin.
     *
     * @return array
     *   Array de páginas con conteo.
     */
    public function getTopPages(int $tenant_id, int $limit = 10, string $start_date = '', string $end_date = ''): array
    {
        $query = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['page_url'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'page_view');

        if ($start_date) {
            $query->condition('ae.created', strtotime($start_date), '>=');
        }
        if ($end_date) {
            $query->condition('ae.created', strtotime($end_date), '<=');
        }

        $query->addExpression('COUNT(*)', 'views');
        $query->groupBy('ae.page_url');
        $query->orderBy('views', 'DESC');
        $query->range(0, $limit);

        return $query->execute()->fetchAll();
    }

    /**
     * Obtiene fuentes de tráfico.
     *
     * @param int $tenant_id
     *   ID del tenant.
     * @param string $start_date
     *   Fecha inicio.
     * @param string $end_date
     *   Fecha fin.
     *
     * @return array
     *   Array de fuentes con conteo.
     */
    public function getTrafficSources(int $tenant_id, string $start_date = '', string $end_date = ''): array
    {
        $query = $this->database->select('analytics_event', 'ae')
            ->fields('ae', ['utm_source', 'referrer'])
            ->condition('ae.tenant_id', $tenant_id)
            ->condition('ae.event_type', 'page_view');

        if ($start_date) {
            $query->condition('ae.created', strtotime($start_date), '>=');
        }
        if ($end_date) {
            $query->condition('ae.created', strtotime($end_date), '<=');
        }

        $query->addExpression('COUNT(*)', 'count');
        $query->groupBy('ae.utm_source');
        $query->groupBy('ae.referrer');
        $query->orderBy('count', 'DESC');
        $query->range(0, 10);

        return $query->execute()->fetchAll();
    }

    /**
     * Detecta el tenant ID del contexto actual.
     *
     * @return int|null
     *   ID del tenant o null.
     */
    protected function detectTenantId(): ?int
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        return $tenant ? (int) $tenant->id() : NULL;
    }

    /**
     * Obtiene o genera el session ID.
     *
     * @return string
     *   ID de sesión.
     */
    protected function getSessionId(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();

        if ($session && $session->has('jaraba_analytics_session_id')) {
            return $session->get('jaraba_analytics_session_id');
        }

        $sessionId = bin2hex(random_bytes(16));

        if ($session) {
            $session->set('jaraba_analytics_session_id', $sessionId);
        }

        return $sessionId;
    }

    /**
     * Obtiene o genera el visitor ID.
     *
     * @return string
     *   ID del visitante (persistente via cookie).
     */
    protected function getVisitorId(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $cookies = $request?->cookies;

        if ($cookies && $cookies->has('jaraba_visitor_id')) {
            return $cookies->get('jaraba_visitor_id');
        }

        // Generar nuevo visitor ID.
        return bin2hex(random_bytes(16));
    }

    /**
     * Hashea una IP para cumplimiento GDPR.
     *
     * @param string|null $ip
     *   Dirección IP.
     *
     * @return string|null
     *   IP hasheada.
     */
    protected function hashIp(?string $ip): ?string
    {
        if (!$ip) {
            return NULL;
        }

        // Truncar último octeto para IPv4 o últimos 80 bits para IPv6.
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            $truncated = implode('.', $parts);
        } else {
            // IPv6: truncar últimos 80 bits.
            $truncated = substr($ip, 0, 19) . '::';
        }

        return hash('sha256', $truncated);
    }

    /**
     * Parsea user agent para extraer información del dispositivo.
     *
     * @param string $userAgent
     *   User agent string.
     *
     * @return array
     *   Array con device_type, browser, os.
     */
    protected function parseUserAgent(string $userAgent): array
    {
        $result = [
            'device_type' => 'desktop',
            'browser' => 'unknown',
            'os' => 'unknown',
        ];

        // Detectar dispositivo.
        if (preg_match('/mobile|android|iphone|ipad|ipod/i', $userAgent)) {
            $result['device_type'] = preg_match('/ipad|tablet/i', $userAgent) ? 'tablet' : 'mobile';
        }

        // Detectar navegador.
        if (preg_match('/Chrome\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Chrome';
        } elseif (preg_match('/Firefox\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Firefox';
        } elseif (preg_match('/Safari\/(\d+)/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $result['browser'] = 'Safari';
        } elseif (preg_match('/Edge\/(\d+)/i', $userAgent)) {
            $result['browser'] = 'Edge';
        }

        // Detectar OS.
        if (preg_match('/Windows/i', $userAgent)) {
            $result['os'] = 'Windows';
        } elseif (preg_match('/Mac OS/i', $userAgent)) {
            $result['os'] = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $result['os'] = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $result['os'] = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $result['os'] = 'iOS';
        }

        return $result;
    }

}
