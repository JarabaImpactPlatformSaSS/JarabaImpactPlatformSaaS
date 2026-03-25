<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService;
use Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService;
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
class TenantDashboardController extends ControllerBase {

  /**
   * El servicio de contexto del Tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Servicio de límites de uso de IA.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService
   */
  protected AIUsageLimitService $aiUsageLimit;

  /**
   * Servicio de pricing para resolución en cascada de tiers.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService|null
   */
  protected ?MetaSitePricingService $pricingService = NULL;

  /**
   * Constructor del controlador.
   *
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
   *   El servicio de contexto del Tenant.
   * @param \Drupal\ecosistema_jaraba_core\Service\AIUsageLimitService $ai_usage_limit
   *   El servicio de límites de IA.
   * @param \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService|null $pricing_service
   *   El servicio de pricing (opcional para retrocompatibilidad).
   */
  public function __construct(
    TenantContextService $tenant_context,
    AIUsageLimitService $ai_usage_limit,
    ?MetaSitePricingService $pricing_service = NULL,
  ) {
    $this->tenantContext = $tenant_context;
    $this->aiUsageLimit = $ai_usage_limit;
    $this->pricingService = $pricing_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static(
          $container->get('ecosistema_jaraba_core.tenant_context'),
          $container->get('ecosistema_jaraba_core.ai_usage_limit'),
          $container->has('ecosistema_jaraba_core.metasite_pricing')
              ? $container->get('ecosistema_jaraba_core.metasite_pricing')
              : NULL,
      );
    return $instance;
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
  public function dashboard(): array {
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

    // Calcular días restantes de trial.
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
    // PASO 5: OBTENER MÉTRICAS DE USO DE IA
    // =====================================================================
    $aiUsage = $this->aiUsageLimit->checkLimit($tenant);

    // =====================================================================
    // PASO 6: PREPARAR ACCIONES RÁPIDAS
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
      '#ai_usage' => $aiUsage,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['tenant:' . $tenant->id()],
    // 5 minutos de cache
        'max-age' => 300,
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
  protected function buildQuickActions($tenant, $group): array {
    $actions = [];

    // Gestionar Suscripción (Stripe Portal)
    $actions[] = [
      'title' => $this->t('Gestionar Suscripción'),
      'url' => Url::fromRoute('ecosistema_jaraba_core.api.stripe.portal'),
      'icon' => 'credit-card',
      'description' => $this->t('Cambiar plan, actualizar pago, ver facturas'),
    // El portal de Stripe requiere POST.
      'method' => 'POST',
    ];

    // Ver Miembros del Grupo.
    if ($group) {
      $actions[] = [
        'title' => $this->t('Gestionar Miembros'),
        'url' => Url::fromUri('internal:/group/' . $group->id() . '/members'),
        'icon' => 'users',
        'description' => $this->t('Añadir o eliminar productores'),
      ];
    }

    // Crear Contenido.
    $actions[] = [
      'title' => $this->t('Crear Contenido'),
      'url' => Url::fromUri('internal:/node/add'),
      'icon' => 'plus-circle',
      'description' => $this->t('Añadir productos, artículos, etc.'),
    ];

    // Configuración del Tenant.
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
  public function getTitle(): string {
    $tenant = $this->tenantContext->getCurrentTenant();

    if ($tenant) {
      return $this->t('Dashboard: @name', ['@name' => $tenant->getName()]);
    }

    return $this->t('Mi Organización');
  }

  /**
   * Página para cambiar de plan (upgrade/downgrade).
   *
   * Reutiliza MetaSitePricingService (resolución en cascada) para obtener
   * tiers con features humanas, descripciones y precios correctos.
   * Usa las clases CSS .pricing-* del tema público para diseño glassmorphism.
   *
   * @return array
   *   Array de renderizado de Drupal.
   */
  public function changePlan(): array {
    $tenant = $this->tenantContext->getCurrentTenant();

    if (!$tenant) {
      throw new AccessDeniedHttpException(
            $this->t('No tienes una organización asociada.')
        );
    }

    // Resolver el vertical del tenant para filtrar planes relevantes.
    $vertical = $tenant->getVertical();
    $verticalKey = $vertical ? $vertical->getMachineName() : '_default';

    $verticalLabels = [
      'empleabilidad' => 'Empleabilidad',
      'emprendimiento' => 'Emprendimiento',
      'comercioconecta' => 'ComercioConecta',
      'agroconecta' => 'AgroConecta',
      'jarabalex' => 'JarabaLex',
      'serviciosconecta' => 'ServiciosConecta',
      'formacion' => 'Formación',
      'andalucia_ei' => 'Andalucía +ei',
      'jaraba_content_hub' => 'Content Hub',
    ];
    $verticalLabel = $verticalLabels[$verticalKey] ?? ucfirst($verticalKey);

    // Obtener tiers vía MetaSitePricingService (cascada: specific → default → fallback).
    $tiers = $this->pricingService
            ? $this->pricingService->getPricingPreview($verticalKey)
            : [];

    // Determinar el tier actual del tenant comparando saas_plan_id.
    $currentPlan = $tenant->getSubscriptionPlan();
    $currentPlanId = $currentPlan ? (int) $currentPlan->id() : 0;
    $currentTierKey = '';

    foreach ($tiers as &$tier) {
      if ($tier['saas_plan_id'] && (int) $tier['saas_plan_id'] === $currentPlanId) {
        $tier['is_current'] = TRUE;
        $currentTierKey = $tier['tier_key'];
      }
      else {
        $tier['is_current'] = FALSE;
      }
      // Añadir precio del plan actual para comparación upgrade/downgrade en template.
      $tier['current_plan_price'] = $currentPlan ? (float) $currentPlan->getPriceMonthly() : 0.0;
    }
    unset($tier);

    // Texto de garantía PLG desde theme settings.
    $guaranteeText = $this->getChangePlanGuaranteeText();

    return [
      '#theme' => 'ecosistema_jaraba_change_plan',
      '#tiers' => $tiers,
      '#current_tier_key' => $currentTierKey,
      '#vertical_key' => $verticalKey,
      '#vertical_label' => $verticalLabel,
      '#page_title' => $this->t('Cambiar de plan'),
      '#page_subtitle' => $this->t('Elige el plan que mejor se adapte a las necesidades de tu organización.'),
      '#current_plan_label' => $currentTierKey
        ? $verticalLabel . ' ' . ucfirst($currentTierKey)
        : '',
      '#guarantee_text' => $guaranteeText,
      '#faq_items' => $this->getChangePlanFaq(),
      '#back_url' => Url::fromRoute('ecosistema_jaraba_core.tenant.dashboard'),
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'tenant:' . $tenant->id(),
          'config:saas_plan_tier_list',
          'config:saas_plan_features_list',
          'saas_plan_list',
        ],
        'max-age' => 300,
      ],
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/pricing-page',
        ],
      ],
    ];
  }

  /**
   * Texto de garantía para la página de cambio de plan.
   *
   * @return string
   *   Texto de garantía PLG.
   */
  protected function getChangePlanGuaranteeText(): string {
    try {
      $setting = theme_get_setting('plg_guarantee_text', 'ecosistema_jaraba_theme');
      if (is_string($setting) && $setting !== '') {
        return $setting;
      }
    }
    catch (\Throwable) {
      // Theme settings no disponibles.
    }
    return (string) $this->t('Sin permanencia. Cambia o cancela tu plan en cualquier momento.');
  }

  /**
   * FAQ específicas para el contexto de cambio de plan.
   *
   * @return array
   *   Array de FAQ con 'question' y 'answer'.
   */
  protected function getChangePlanFaq(): array {
    return [
          [
            'question' => $this->t('¿Se aplica algún coste por cambiar de plan?'),
            'answer' => $this->t('Si mejoras a un plan superior, se te cobrará la diferencia prorrateada hasta el final del ciclo de facturación actual. Si reduces tu plan, el cambio se aplica al próximo ciclo sin coste adicional.'),
          ],
          [
            'question' => $this->t('¿Cuándo se aplica el cambio?'),
            'answer' => $this->t('Los upgrades se aplican inmediatamente y tendrás acceso a todas las funcionalidades del nuevo plan al instante. Los downgrades se aplican al inicio del siguiente período de facturación.'),
          ],
          [
            'question' => $this->t('¿Se mantienen mis datos al cambiar de plan?'),
            'answer' => $this->t('Sí. Todos tus datos, configuraciones y contenido se mantienen intactos. Si reduces a un plan con menos almacenamiento, solo se limita la creación de nuevo contenido — el existente permanece accesible.'),
          ],
          [
            'question' => $this->t('¿Puedo volver a mi plan anterior?'),
            'answer' => $this->t('Sí. Puedes cambiar entre planes en cualquier momento. No hay permanencia ni penalizaciones por cambio.'),
          ],
          [
            'question' => $this->t('¿Qué métodos de pago aceptáis?'),
            'answer' => $this->t('Aceptamos tarjetas de crédito/débito (Visa, Mastercard, AMEX) y SEPA (domiciliación bancaria) a través de Stripe, nuestro procesador de pagos seguro.'),
          ],
    ];
  }

}
