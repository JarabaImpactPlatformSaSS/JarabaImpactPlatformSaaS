<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controlador del Dashboard de Administración del Tenant.
 *
 * PROPÓSITO:
 * Proporcionar una vista consolidada para los administradores de Tenant
 * que incluye:
 * - Estado de suscripción y plan actual
 * - Métricas de uso (productores, almacenamiento, contenido)
 * - Acciones rápidas (billing, miembros, configuración)
 * - Información técnica (dominio, grupo)
 *
 * ACCESO:
 * Solo usuarios con rol 'tenant_admin' y que tengan un Tenant asociado
 * pueden acceder a esta página.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\TenantContextService
 */
class TenantDashboardController extends ControllerBase
{

    /**
     * El servicio de contexto del Tenant.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * Constructor del controlador.
     *
     * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
     *   El servicio de contexto del Tenant.
     */
    public function __construct(TenantContextService $tenant_context)
    {
        $this->tenantContext = $tenant_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context')
        );
    }

    /**
     * Renderiza el dashboard principal del Tenant.
     *
     * FLUJO:
     * 1. Obtener el Tenant del usuario actual
     * 2. Verificar acceso y existencia
     * 3. Calcular métricas de uso
     * 4. Preparar datos para la plantilla
     * 5. Renderizar con theme 'ecosistema_jaraba_tenant_dashboard'
     *
     * @return array
     *   Array de renderizado de Drupal.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     *   Si el usuario no tiene un Tenant asociado.
     */
    public function dashboard(): array
    {
        // =====================================================================
        // PASO 1: OBTENER TENANT ACTUAL
        // =====================================================================
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            throw new AccessDeniedHttpException(
                $this->t('No tienes una organización asociada. Contacta con soporte.')
            );
        }

        // =====================================================================
        // PASO 2: OBTENER DATOS RELACIONADOS
        // =====================================================================
        $plan = $tenant->getSubscriptionPlan();
        $group = $tenant->getGroup();
        $domain = $tenant->getDomainEntity();
        $vertical = $tenant->getVertical();

        // =====================================================================
        // PASO 3: CALCULAR MÉTRICAS DE USO
        // =====================================================================
        $metrics = $this->tenantContext->getUsageMetrics($tenant);

        // =====================================================================
        // PASO 4: DETERMINAR ESTADO DE SUSCRIPCIÓN
        // =====================================================================
        $subscriptionStatus = $tenant->getSubscriptionStatus();
        $isOnTrial = $tenant->isOnTrial();

        $statusLabels = [
            'trial' => $this->t('Periodo de prueba'),
            'active' => $this->t('Activa'),
            'past_due' => $this->t('Pago pendiente'),
            'suspended' => $this->t('Suspendida'),
            'cancelled' => $this->t('Cancelada'),
        ];

        $subscriptionStatusLabel = $statusLabels[$subscriptionStatus] ?? $subscriptionStatus;

        // Calcular días restantes de trial
        $trialDaysRemaining = 0;
        if ($isOnTrial) {
            $trialEndsAt = $tenant->getTrialEndsAt();
            if ($trialEndsAt) {
                $now = new \DateTime();
                $end = new \DateTime('@' . $trialEndsAt);
                $diff = $now->diff($end);
                $trialDaysRemaining = max(0, $diff->days);
            }
        }

        // =====================================================================
        // PASO 5: PREPARAR ACCIONES RÁPIDAS
        // =====================================================================
        $quickActions = $this->buildQuickActions($tenant, $group);

        // =====================================================================
        // PASO 6: CONSTRUIR RENDER ARRAY
        // =====================================================================
        return [
            '#theme' => 'ecosistema_jaraba_tenant_dashboard',
            '#tenant' => $tenant,
            '#plan' => $plan,
            '#group' => $group,
            '#domain' => $domain,
            '#vertical' => $vertical,
            '#metrics' => $metrics,
            '#subscription_status' => $subscriptionStatus,
            '#subscription_status_label' => $subscriptionStatusLabel,
            '#is_on_trial' => $isOnTrial,
            '#trial_days_remaining' => $trialDaysRemaining,
            '#quick_actions' => $quickActions,
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['tenant:' . $tenant->id()],
                'max-age' => 300, // 5 minutos de cache
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/tenant_dashboard',
                ],
            ],
        ];
    }

    /**
     * Construye las acciones rápidas disponibles para el tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant actual.
     * @param \Drupal\group\Entity\GroupInterface|null $group
     *   El grupo asociado.
     *
     * @return array
     *   Array de acciones con 'title', 'url', 'icon', 'description'.
     */
    protected function buildQuickActions($tenant, $group): array
    {
        $actions = [];

        // Gestionar Suscripción (Stripe Portal)
        $actions[] = [
            'title' => $this->t('Gestionar Suscripción'),
            'url' => Url::fromRoute('ecosistema_jaraba_core.api.stripe.portal'),
            'icon' => 'credit-card',
            'description' => $this->t('Cambiar plan, actualizar pago, ver facturas'),
            'method' => 'POST', // El portal de Stripe requiere POST
        ];

        // Ver Miembros del Grupo
        if ($group) {
            $actions[] = [
                'title' => $this->t('Gestionar Miembros'),
                'url' => Url::fromUri('internal:/group/' . $group->id() . '/members'),
                'icon' => 'users',
                'description' => $this->t('Añadir o eliminar productores'),
            ];
        }

        // Crear Contenido
        $actions[] = [
            'title' => $this->t('Crear Contenido'),
            'url' => Url::fromUri('internal:/node/add'),
            'icon' => 'plus-circle',
            'description' => $this->t('Añadir productos, artículos, etc.'),
        ];

        // Configuración del Tenant
        $actions[] = [
            'title' => $this->t('Configuración'),
            'url' => Url::fromRoute('entity.tenant.edit_form', ['tenant' => $tenant->id()]),
            'icon' => 'settings',
            'description' => $this->t('Editar datos de la organización'),
        ];

        return $actions;
    }

    /**
     * Título dinámico para la página del dashboard.
     *
     * @return string
     *   El título de la página.
     */
    public function getTitle(): string
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if ($tenant) {
            return $this->t('Dashboard: @name', ['@name' => $tenant->getName()]);
        }

        return $this->t('Mi Organización');
    }

    /**
     * Página para cambiar de plan (upgrade/downgrade).
     *
     * Muestra todos los planes disponibles y permite al tenant seleccionar
     * uno nuevo. El cambio real se procesa a través de Stripe.
     *
     * @return array
     *   Array de renderizado de Drupal.
     */
    public function changePlan(): array
    {
        // Obtener tenant actual
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            throw new AccessDeniedHttpException(
                $this->t('No tienes una organización asociada.')
            );
        }

        $currentPlan = $tenant->getSubscriptionPlan();

        // Cargar todos los planes disponibles
        $planStorage = $this->entityTypeManager()->getStorage('saas_plan');
        $allPlans = $planStorage->loadMultiple();

        // Preparar planes para el template
        $plans = [];
        foreach ($allPlans as $plan) {
            $isCurrent = $currentPlan && $currentPlan->id() === $plan->id();
            $limits = $plan->getLimits();

            $plans[] = [
                'id' => $plan->id(),
                'name' => $plan->label(),
                'description' => '', // SaasPlan no tiene descripción, se deja vacío
                'monthly_price' => $plan->getPriceMonthly(),
                'yearly_price' => $plan->getPriceYearly(),
                'max_producers' => $limits['productores'] ?? 0,
                'max_storage_mb' => $limits['almacenamiento_mb'] ?? 0,
                'features' => $plan->getFeatures(),
                'is_current' => $isCurrent,
                'stripe_price_id' => $plan->getStripePriceId(),
            ];
        }

        // Ordenar por precio mensual
        usort($plans, fn($a, $b) => $a['monthly_price'] <=> $b['monthly_price']);

        return [
            '#theme' => 'ecosistema_jaraba_change_plan',
            '#tenant' => $tenant,
            '#current_plan' => $currentPlan,
            '#plans' => $plans,
            '#back_url' => Url::fromRoute('ecosistema_jaraba_core.tenant.dashboard'),
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['tenant:' . $tenant->id(), 'config:saas_plan_list'],
                'max-age' => 300,
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/tenant_dashboard',
                ],
            ],
        ];
    }

}
