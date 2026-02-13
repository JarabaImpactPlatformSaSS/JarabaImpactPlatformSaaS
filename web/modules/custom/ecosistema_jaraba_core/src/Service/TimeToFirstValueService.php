<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio de métricas Time-to-First-Value (TTFV).
 *
 * PROPÓSITO:
 * Mide y optimiza el tiempo que tarda un nuevo usuario/tenant
 * en obtener valor de la plataforma.
 *
 * Q2 2026 - Sprint 5-6: Predictive Onboarding
 */
class TimeToFirstValueService
{

    /**
     * Eventos de valor clave.
     */
    public const EVENT_SIGNUP = 'signup';
    public const EVENT_EMAIL_VERIFIED = 'email_verified';
    public const EVENT_PROFILE_COMPLETE = 'profile_complete';
    public const EVENT_FIRST_PRODUCT = 'first_product';
    public const EVENT_FIRST_SALE = 'first_sale';
    public const EVENT_FIRST_CUSTOMER = 'first_customer';
    public const EVENT_STORE_CONFIGURED = 'store_configured';
    public const EVENT_PAYMENT_CONNECTED = 'payment_connected';

    /**
     * Objetivos de TTFV por evento (en minutos).
     */
    protected const TTFV_TARGETS = [
        self::EVENT_EMAIL_VERIFIED => 5,
        self::EVENT_PROFILE_COMPLETE => 15,
        self::EVENT_STORE_CONFIGURED => 30,
        self::EVENT_FIRST_PRODUCT => 60,
        self::EVENT_PAYMENT_CONNECTED => 120,
        self::EVENT_FIRST_CUSTOMER => 1440, // 24h
        self::EVENT_FIRST_SALE => 4320, // 72h
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Registra un evento de valor para un tenant.
     */
    public function trackEvent(string $tenantId, string $event, array $metadata = []): void
    {
        $this->database->insert('ttfv_events')
            ->fields([
                    'tenant_id' => $tenantId,
                    'event' => $event,
                    'metadata' => json_encode($metadata),
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Obtiene el TTFV para un tenant.
     *
     * @param string $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Métricas de TTFV con eventos, tiempos y análisis.
     */
    public function getTTFVMetrics(string $tenantId): array
    {
        $events = $this->getEventTimeline($tenantId);

        if (empty($events)) {
            return [
                'status' => 'no_data',
                'events' => [],
                'ttfv_minutes' => NULL,
            ];
        }

        $signupTime = $events[self::EVENT_SIGNUP] ?? NULL;
        $metrics = [
            'status' => 'active',
            'signup_at' => $signupTime,
            'events' => [],
            'ttfv_minutes' => NULL,
            'first_value_event' => NULL,
        ];

        foreach ($events as $event => $timestamp) {
            if ($event === self::EVENT_SIGNUP) {
                continue;
            }

            $minutesSinceSignup = $signupTime ? round(($timestamp - $signupTime) / 60, 1) : NULL;
            $target = self::TTFV_TARGETS[$event] ?? NULL;

            $metrics['events'][$event] = [
                'timestamp' => $timestamp,
                'minutes_since_signup' => $minutesSinceSignup,
                'target_minutes' => $target,
                'status' => $this->getEventStatus($minutesSinceSignup, $target),
            ];

            // Primera venta = primer valor real.
            if ($event === self::EVENT_FIRST_SALE && $minutesSinceSignup !== NULL) {
                $metrics['ttfv_minutes'] = $minutesSinceSignup;
                $metrics['first_value_event'] = $event;
            }
        }

        // Si no hay venta, usar primer producto como valor.
        if ($metrics['ttfv_minutes'] === NULL && isset($metrics['events'][self::EVENT_FIRST_PRODUCT])) {
            $metrics['ttfv_minutes'] = $metrics['events'][self::EVENT_FIRST_PRODUCT]['minutes_since_signup'];
            $metrics['first_value_event'] = self::EVENT_FIRST_PRODUCT;
        }

        $metrics['analysis'] = $this->analyzeJourney($metrics);

        return $metrics;
    }

    /**
     * Obtiene la timeline de eventos.
     */
    protected function getEventTimeline(string $tenantId): array
    {
        $results = $this->database->select('ttfv_events', 'te')
            ->fields('te', ['event', 'created'])
            ->condition('tenant_id', $tenantId)
            ->orderBy('created', 'ASC')
            ->execute()
            ->fetchAll();

        $events = [];
        foreach ($results as $row) {
            if (!isset($events[$row->event])) {
                $events[$row->event] = (int) $row->created;
            }
        }

        return $events;
    }

    /**
     * Determina el estado de un evento vs su target.
     */
    protected function getEventStatus(?float $actual, ?float $target): string
    {
        if ($actual === NULL) {
            return 'pending';
        }

        if ($target === NULL) {
            return 'completed';
        }

        if ($actual <= $target) {
            return 'on_target';
        } elseif ($actual <= $target * 1.5) {
            return 'delayed';
        } else {
            return 'at_risk';
        }
    }

    /**
     * Analiza el journey y genera insights.
     */
    protected function analyzeJourney(array $metrics): array
    {
        $analysis = [
            'health_score' => 100,
            'bottlenecks' => [],
            'recommendations' => [],
        ];

        foreach ($metrics['events'] as $event => $data) {
            if ($data['status'] === 'at_risk') {
                $analysis['health_score'] -= 20;
                $analysis['bottlenecks'][] = [
                    'event' => $event,
                    'message' => $this->getBottleneckMessage($event),
                ];
            } elseif ($data['status'] === 'delayed') {
                $analysis['health_score'] -= 10;
            }
        }

        // Eventos pendientes.
        $completedEvents = array_keys($metrics['events']);
        $allEvents = array_keys(self::TTFV_TARGETS);
        $pendingEvents = array_diff($allEvents, $completedEvents);

        foreach ($pendingEvents as $event) {
            $analysis['recommendations'][] = $this->getEventRecommendation($event);
        }

        $analysis['health_score'] = max(0, $analysis['health_score']);

        return $analysis;
    }

    /**
     * Obtiene mensaje de bottleneck.
     */
    protected function getBottleneckMessage(string $event): string
    {
        $messages = [
            self::EVENT_EMAIL_VERIFIED => 'Usuario no verificó su email a tiempo',
            self::EVENT_PROFILE_COMPLETE => 'Perfil incompleto frena el progreso',
            self::EVENT_STORE_CONFIGURED => 'Configuración de tienda retrasada',
            self::EVENT_FIRST_PRODUCT => 'Tiempo excesivo para añadir primer producto',
            self::EVENT_PAYMENT_CONNECTED => 'Conexión de pagos pendiente',
        ];

        return $messages[$event] ?? 'Evento retrasado';
    }

    /**
     * Obtiene recomendación para completar un evento.
     */
    protected function getEventRecommendation(string $event): array
    {
        $recommendations = [
            self::EVENT_EMAIL_VERIFIED => [
                'action' => 'resend_email',
                'message' => 'Verifica tu email para continuar',
                'priority' => 'high',
            ],
            self::EVENT_PROFILE_COMPLETE => [
                'action' => 'complete_profile',
                'message' => 'Completa tu perfil para desbloquear funciones',
                'priority' => 'high',
            ],
            self::EVENT_STORE_CONFIGURED => [
                'action' => 'configure_store',
                'message' => 'Configura tu tienda para empezar a vender',
                'priority' => 'medium',
            ],
            self::EVENT_FIRST_PRODUCT => [
                'action' => 'add_product',
                'message' => 'Añade tu primer producto',
                'priority' => 'high',
            ],
            self::EVENT_PAYMENT_CONNECTED => [
                'action' => 'connect_stripe',
                'message' => 'Conecta Stripe para recibir pagos',
                'priority' => 'medium',
            ],
            self::EVENT_FIRST_CUSTOMER => [
                'action' => 'share_store',
                'message' => 'Comparte tu tienda para conseguir clientes',
                'priority' => 'medium',
            ],
        ];

        return $recommendations[$event] ?? [
            'action' => 'continue',
            'message' => 'Continúa con el onboarding',
            'priority' => 'low',
        ];
    }

    /**
     * Obtiene métricas agregadas de TTFV para el dashboard.
     *
     * @param int|null $tenantId
     *   AUDIT-SEC-N13: ID del tenant para filtrar métricas.
     *   NULL retorna solo métricas del tenant actual.
     */
    public function getAggregateTTFVMetrics(?int $tenantId = NULL): array
    {
        // AUDIT-SEC-N13: Resolver tenant desde contexto si no se proporciona.
        if ($tenantId === NULL) {
            $tenantId = $this->currentUser->id() > 0
                ? (int) (\Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenantId() ?? 0)
                : 0;
        }

        // Promedios de TTFV por evento (últimos 30 días).
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);

        $args = [':since' => $thirtyDaysAgo];
        $tenantFilter = '';
        if ($tenantId > 0) {
            $tenantFilter = 'AND e.tenant_id = :tenant_id';
            $args[':tenant_id'] = $tenantId;
        }

        $results = $this->database->query("
      SELECT
        event,
        AVG(minutes_to_event) as avg_minutes,
        COUNT(*) as count
      FROM (
        SELECT
          e.event,
          (e.created - s.created) / 60 as minutes_to_event
        FROM {ttfv_events} e
        INNER JOIN {ttfv_events} s
          ON e.tenant_id = s.tenant_id
          AND s.event = 'signup'
        WHERE e.created > :since
          AND e.event != 'signup'
          {$tenantFilter}
      ) subquery
      GROUP BY event
    ", $args)->fetchAll();

        $metrics = [];
        foreach ($results as $row) {
            $target = self::TTFV_TARGETS[$row->event] ?? NULL;
            $metrics[$row->event] = [
                'avg_minutes' => round($row->avg_minutes, 1),
                'count' => (int) $row->count,
                'target_minutes' => $target,
                'performance' => $target ? round(($target / $row->avg_minutes) * 100, 1) : NULL,
            ];
        }

        return $metrics;
    }

}
