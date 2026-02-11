<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para el Dashboard del Tenant (Self-Service Portal).
 *
 * PROPÓSITO:
 * Proporciona a los tenants una vista de sus propias métricas y
 * permite configuraciones self-service sin intervención del admin.
 *
 * MÉTRICAS MOSTRADAS:
 * - MRR propio del tenant (desde plan de suscripción)
 * - Número de clientes (usuarios asociados)
 * - Número de productos (si aplica por vertical)
 * - Ventas del mes (desde transacciones FOC)
 * - Estado de suscripción
 * - Días de trial restantes
 */
class TenantSelfServiceController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantContextService $tenantContext,
        protected Connection $database,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context'),
            $container->get('database')
        );
    }

    /**
     * Página principal del dashboard del tenant.
     *
     * @return array
     *   Render array con el dashboard.
     */
    public function dashboard(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return [
                '#markup' => $this->t('No tienes un tenant asignado. Contacta con el administrador.'),
            ];
        }

        // Obtener métricas reales del tenant.
        $metrics = $this->getTenantMetrics($tenant);
        $subscriptionInfo = $this->getSubscriptionInfo($tenant);
        $recentActivity = $this->getRecentActivity($tenant);

        $build = [
            '#theme' => 'tenant_self_service_dashboard',
            '#tenant' => $tenant,
            '#metrics' => $metrics,
            '#subscription_info' => $subscriptionInfo,
            '#recent_activity' => $recentActivity,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/tenant-dashboard',
                ],
            ],
        ];

        return $build;
    }

    /**
     * Obtiene métricas reales del tenant.
     */
    protected function getTenantMetrics($tenant): array
    {
        $tenantId = $tenant->id();

        // Obtener MRR desde el plan de suscripción.
        $plan = $tenant->getSubscriptionPlan();
        $mrr = 0;
        if ($plan && method_exists($plan, 'getMonthlyPrice')) {
            $mrr = $plan->getMonthlyPrice();
        } elseif ($plan && $plan->hasField('monthly_price')) {
            $mrr = (float) ($plan->get('monthly_price')->value ?? 0);
        }

        // Obtener métricas de uso desde TenantContextService.
        $usageMetrics = $this->tenantContext->getUsageMetrics($tenant);

        // Extraer contadores de las métricas estructuradas.
        $membersCount = $usageMetrics['productores']['count'] ?? 0;
        $contentCount = $usageMetrics['contenido']['count'] ?? 0;

        // Obtener ventas del mes desde FOC (si el módulo está habilitado).
        $salesMonth = $this->getMonthSales($tenantId);

        // Calcular tendencias (comparando con mes anterior).
        $salesTrend = $this->calculateTrend($tenantId, 'sales');

        return [
            'mrr' => [
                'value' => '€' . number_format($mrr, 2, ',', '.'),
                'raw_value' => $mrr,
                'label' => $this->t('MRR'),
                'description' => $this->t('Ingresos Mensuales Recurrentes'),
                'trend' => 0,
                'icon' => '💰',
            ],
            'members' => [
                'value' => $membersCount,
                'label' => $this->t('Miembros'),
                'description' => $this->t('Usuarios asociados'),
                'trend' => 0,
                'icon' => '👥',
            ],
            'content' => [
                'value' => $contentCount,
                'label' => $this->t('Contenido'),
                'description' => $this->t('Elementos creados'),
                'trend' => 0,
                'icon' => '📦',
            ],
            'sales_month' => [
                'value' => '€' . number_format($salesMonth, 2, ',', '.'),
                'raw_value' => $salesMonth,
                'label' => $this->t('Ventas del Mes'),
                'description' => $this->t('Total de ventas este mes'),
                'trend' => $salesTrend,
                'icon' => '📈',
            ],
        ];
    }

    /**
     * Obtiene información de la suscripción del tenant.
     */
    protected function getSubscriptionInfo($tenant): array
    {
        $plan = $tenant->getSubscriptionPlan();
        $status = $tenant->get('subscription_status')->value ?? 'trial';

        $info = [
            'plan_name' => $plan ? $plan->label() : $this->t('Sin plan'),
            'status' => $status,
            'status_label' => $this->getStatusLabel($status),
            'status_class' => $this->getStatusClass($status),
            'is_trial' => $status === 'trial',
            'trial_days_remaining' => 0,
            'next_billing_date' => NULL,
        ];

        // Calcular días de trial restantes.
        if ($status === 'trial' && $tenant->hasField('trial_ends')) {
            $trialEnds = $tenant->get('trial_ends')->value;
            if ($trialEnds) {
                $trialEndsTimestamp = strtotime($trialEnds);
                $daysRemaining = max(0, ceil(($trialEndsTimestamp - time()) / 86400));
                $info['trial_days_remaining'] = (int) $daysRemaining;
            }
        }

        // Próxima fecha de facturación.
        if ($tenant->hasField('next_billing_date') && !$tenant->get('next_billing_date')->isEmpty()) {
            $info['next_billing_date'] = $tenant->get('next_billing_date')->value;
        }

        return $info;
    }

    /**
     * Obtiene actividad reciente del tenant.
     */
    protected function getRecentActivity($tenant): array
    {
        $tenantId = $tenant->id();
        $activities = [];

        // Intentar obtener transacciones recientes del FOC.
        try {
            if ($this->database->schema()->tableExists('financial_transaction')) {
                $results = $this->database->select('financial_transaction', 'ft')
                    ->fields('ft', ['id', 'type', 'amount', 'currency', 'created'])
                    ->condition('tenant_id', $tenantId)
                    ->orderBy('created', 'DESC')
                    ->range(0, 5)
                    ->execute();

                foreach ($results as $row) {
                    $activities[] = [
                        'type' => $row->type,
                        'description' => $this->getActivityDescription($row->type, $row->amount, $row->currency),
                        'date' => date('d M Y H:i', $row->created),
                        'amount' => '€' . number_format((float) $row->amount, 2, ',', '.'),
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silenciar errores si la tabla no existe.
        }

        // Si no hay actividad, mostrar mensaje.
        if (empty($activities)) {
            $activities[] = [
                'type' => 'info',
                'description' => $this->t('No hay actividad reciente'),
                'date' => '',
                'amount' => '',
            ];
        }

        return $activities;
    }

    /**
     * Obtiene ventas del mes actual.
     */
    protected function getMonthSales(int|string $tenantId): float
    {
        $startOfMonth = strtotime('first day of this month midnight');
        $sales = 0.0;

        try {
            if ($this->database->schema()->tableExists('financial_transaction')) {
                $query = $this->database->select('financial_transaction', 'ft');
                $query->condition('tenant_id', $tenantId);
                $query->condition('type', ['sale', 'order', 'payment'], 'IN');
                $query->condition('created', $startOfMonth, '>=');
                $query->addExpression('SUM(amount)', 'total');
                $result = $query->execute()->fetchField();

                $sales = (float) ($result ?? 0);
            }
        } catch (\Exception $e) {
            // Silenciar errores.
        }

        return $sales;
    }

    /**
     * Calcula tendencia comparando con período anterior.
     */
    protected function calculateTrend(int|string $tenantId, string $type): int
    {
        // Placeholder - en futuras versiones calcular tendencia real.
        return 0;
    }

    /**
     * Obtiene etiqueta de estado legible.
     */
    protected function getStatusLabel(string $status): string
    {
        return match ($status) {
            'trial' => (string) $this->t('Prueba gratuita'),
            'active' => (string) $this->t('Activo'),
            'past_due' => (string) $this->t('Pago pendiente'),
            'suspended' => (string) $this->t('Suspendido'),
            'cancelled' => (string) $this->t('Cancelado'),
            default => ucfirst($status),
        };
    }

    /**
     * Obtiene clase CSS para el estado.
     */
    protected function getStatusClass(string $status): string
    {
        return match ($status) {
            'trial' => 'status--trial',
            'active' => 'status--active',
            'past_due' => 'status--warning',
            'suspended', 'cancelled' => 'status--danger',
            default => 'status--default',
        };
    }

    /**
     * Genera descripción de actividad.
     */
    protected function getActivityDescription(string $type, float $amount, string $currency): string
    {
        return match ($type) {
            'sale' => (string) $this->t('Venta realizada'),
            'order' => (string) $this->t('Nuevo pedido'),
            'payment' => (string) $this->t('Pago recibido'),
            'refund' => (string) $this->t('Reembolso procesado'),
            default => ucfirst($type),
        };
    }

    /**
     * Página de configuración del tenant.
     */
    public function settings(): array
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            return [
                '#markup' => $this->t('No tienes un tenant asignado.'),
            ];
        }

        $plan = $tenant->getSubscriptionPlan();

        return [
            '#theme' => 'tenant_self_service_settings',
            '#tenant' => $tenant,
            '#current_plan' => $plan,
            '#settings_sections' => [
                'domain' => [
                    'title' => $this->t('Dominio Personalizado'),
                    'description' => $this->t('Configura tu propio dominio para acceder a tu tienda.'),
                    'status' => 'available',
                    'icon' => '🌐',
                ],
                'plan' => [
                    'title' => $this->t('Plan y Facturación'),
                    'description' => $this->t('Gestiona tu plan y método de pago.'),
                    'status' => 'available',
                    'icon' => '💳',
                    'link' => '/tenant/change-plan',
                ],
                'api_keys' => [
                    'title' => $this->t('API Keys'),
                    'description' => $this->t('Genera claves de API para integraciones.'),
                    'status' => 'available',
                    'icon' => '🔑',
                ],
                'webhooks' => [
                    'title' => $this->t('Webhooks'),
                    'description' => $this->t('Configura notificaciones automáticas a tus sistemas.'),
                    'status' => 'available',
                    'icon' => '🔗',
                ],
            ],
        ];
    }

}
