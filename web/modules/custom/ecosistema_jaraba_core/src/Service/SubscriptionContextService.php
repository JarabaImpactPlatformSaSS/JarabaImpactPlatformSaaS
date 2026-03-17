<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Resuelve el contexto completo de suscripción para un usuario.
 *
 * Agrega datos de plan, features incluidos/bloqueados, uso vs límites,
 * y path de upgrade. Usado por SubscriptionProfileSection y dashboards
 * para mostrar la UI de PLG (Product-Led Growth).
 *
 * PLG-UPGRADE-UI-001 §6.1
 * OPTIONAL-CROSSMODULE-001: jaraba_billing services inyectados como @?
 * TENANT-BRIDGE-001: TenantContextService para resolver tenant del usuario.
 */
class SubscriptionContextService {

  use StringTranslationTrait;

  /**
   * Mapa de feature keys → labels humanos + iconos.
   *
   * Centralizado aquí para evitar duplicación. Cada feature tiene:
   * - label: Texto traducible mostrado al usuario
   * - icon_cat: Categoría de icono (ICON-CONVENTION-001)
   * - icon_name: Nombre del icono
   */
  protected const FEATURE_LABELS = [
    // Transversales.
    'soporte_email' => ['label' => 'Soporte por email', 'icon_cat' => 'ui', 'icon_name' => 'mail'],
    'soporte_chat' => ['label' => 'Soporte por chat en vivo', 'icon_cat' => 'ui', 'icon_name' => 'chat'],
    'soporte_dedicado' => ['label' => 'Soporte dedicado', 'icon_cat' => 'ui', 'icon_name' => 'headset'],
    'soporte_telefono' => ['label' => 'Soporte telefónico', 'icon_cat' => 'ui', 'icon_name' => 'phone'],
    'analiticas_basicas' => ['label' => 'Analíticas básicas', 'icon_cat' => 'analytics', 'icon_name' => 'chart-bar'],
    'analiticas_avanzadas' => ['label' => 'Analíticas avanzadas', 'icon_cat' => 'analytics', 'icon_name' => 'chart-line'],
    'dominio_personalizado' => ['label' => 'Dominio personalizado', 'icon_cat' => 'ui', 'icon_name' => 'globe'],
    'marca_blanca' => ['label' => 'Marca blanca', 'icon_cat' => 'ui', 'icon_name' => 'palette'],
    'api_access' => ['label' => 'Acceso API completo', 'icon_cat' => 'ui', 'icon_name' => 'code'],
    'webhooks' => ['label' => 'Webhooks', 'icon_cat' => 'ui', 'icon_name' => 'webhook'],
    'firma_digital' => ['label' => 'Firma digital', 'icon_cat' => 'business', 'icon_name' => 'signature'],
    'trazabilidad_basica' => ['label' => 'Trazabilidad básica', 'icon_cat' => 'business', 'icon_name' => 'route'],
    'trazabilidad_avanzada' => ['label' => 'Trazabilidad avanzada', 'icon_cat' => 'business', 'icon_name' => 'route'],
    'agentes_ia_limitados' => ['label' => 'Agentes IA (limitados)', 'icon_cat' => 'ai', 'icon_name' => 'sparkles'],
    'agentes_ia_completos' => ['label' => 'Agentes IA completos', 'icon_cat' => 'ai', 'icon_name' => 'sparkles'],
    // Emprendimiento.
    'calculadora_madurez' => ['label' => 'Calculadora de madurez digital', 'icon_cat' => 'analytics', 'icon_name' => 'gauge'],
    'bmc_ia' => ['label' => 'Business Model Canvas con IA', 'icon_cat' => 'business', 'icon_name' => 'grid'],
    'validacion_mvp' => ['label' => 'Validación MVP (Lean Startup)', 'icon_cat' => 'business', 'icon_name' => 'experiment'],
    'mentoring_1a1' => ['label' => 'Mentoría individual', 'icon_cat' => 'business', 'icon_name' => 'users'],
    'proyecciones_financieras' => ['label' => 'Proyecciones financieras', 'icon_cat' => 'analytics', 'icon_name' => 'chart-line'],
    'health_score' => ['label' => 'Health Score empresarial', 'icon_cat' => 'analytics', 'icon_name' => 'heart-pulse'],
    'credenciales_digitales' => ['label' => 'Credenciales digitales', 'icon_cat' => 'business', 'icon_name' => 'badge'],
    'copilot_ia' => ['label' => 'Copilot IA básico', 'icon_cat' => 'ai', 'icon_name' => 'sparkles'],
    'copilot_proactivo' => ['label' => 'Copilot proactivo con IA', 'icon_cat' => 'ai', 'icon_name' => 'sparkles'],
    'email_nurturing' => ['label' => 'Email nurturing automatizado', 'icon_cat' => 'ui', 'icon_name' => 'mail'],
    'acceso_financiacion' => ['label' => 'Acceso a financiación', 'icon_cat' => 'business', 'icon_name' => 'banknotes'],
    'niveles_expertise' => ['label' => 'Niveles de expertise', 'icon_cat' => 'business', 'icon_name' => 'trophy'],
    'journey_personalizado' => ['label' => 'Itinerario personalizado', 'icon_cat' => 'business', 'icon_name' => 'map'],
    'motor_experimentos_ab' => ['label' => 'Motor de experimentos A/B', 'icon_cat' => 'analytics', 'icon_name' => 'split'],
    'puentes_cross_vertical' => ['label' => 'Integraciones cross-vertical', 'icon_cat' => 'ui', 'icon_name' => 'link'],
    'cross_sell' => ['label' => 'Recomendaciones de productos', 'icon_cat' => 'business', 'icon_name' => 'shopping-cart'],
    'analytics' => ['label' => 'Panel de analíticas', 'icon_cat' => 'analytics', 'icon_name' => 'chart-bar'],
    'ab_testing' => ['label' => 'Testing A/B avanzado', 'icon_cat' => 'analytics', 'icon_name' => 'split'],
    'premium_blocks' => ['label' => 'Bloques premium del Page Builder', 'icon_cat' => 'ui', 'icon_name' => 'layout'],
    'white_label' => ['label' => 'Solución marca blanca', 'icon_cat' => 'ui', 'icon_name' => 'palette'],
  ];

  /**
   * Mapa de limit keys → labels humanos para barras de uso.
   */
  protected const LIMIT_LABELS = [
    'copilot_sessions_daily' => 'Consultas IA hoy',
    'hypotheses_active' => 'Hipótesis activas',
    'mentoring_sessions_monthly' => 'Sesiones de mentoría este mes',
    'experiments_monthly' => 'Experimentos este mes',
    'productores' => 'Productores',
    'storage_gb' => 'Almacenamiento (GB)',
    'ai_queries' => 'Consultas IA este mes',
    'max_pages' => 'Páginas publicadas',
  ];

  /**
   * Tier hierarchy para determinar qué features están en tiers superiores.
   */
  protected const TIER_HIERARCHY = ['starter', 'professional', 'enterprise'];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?TenantContextService $tenantContext,
    protected ?TenantBridgeService $tenantBridge,
    protected ?PlanResolverService $planResolver,
  ) {}

  /**
   * Resuelve el contexto completo de suscripción para un usuario.
   *
   * @param int $uid
   *   User ID.
   *
   * @return array
   *   SubscriptionContext array. Vacío si el usuario no tiene tenant/plan.
   */
  public function getContextForUser(int $uid): array {
    try {
      return $this->doResolve($uid);
    }
    catch (\Throwable $e) {
      // PRESAVE-RESILIENCE-001: nunca romper el perfil por un error de billing.
      return [];
    }
  }

  /**
   * Lógica interna de resolución.
   */
  protected function doResolve(int $uid): array {
    // 1. Resolver tenant del usuario.
    $tenant = $this->resolveTenant($uid);
    if (!$tenant) {
      return [];
    }

    // 2. Cargar plan de suscripción.
    $planRef = $tenant->get('subscription_plan')->entity ?? NULL;
    if (!$planRef) {
      return $this->buildFreePlanContext($tenant);
    }

    $planName = $planRef->getName();
    $vertical = $tenant->get('vertical')->entity;
    $verticalKey = $vertical ? $vertical->get('machine_name')->value : '_default';

    // 3. Resolver tier normalizado.
    $tier = $this->resolveTier($planName);

    // 4. Resolver features incluidos en el plan actual.
    $currentFeatures = [];
    if ($this->planResolver) {
      $featuresEntity = $this->planResolver->getFeatures($verticalKey, $tier);
      if ($featuresEntity) {
        $currentFeatures = $featuresEntity->getFeatures();
      }
    }

    // 5. Resolver features de tiers superiores (bloqueados).
    $lockedFeatures = $this->resolveLockedFeatures($verticalKey, $tier, $currentFeatures);

    // 6. Resolver uso actual vs límites.
    $usage = $this->resolveUsage($tenant, $verticalKey, $tier);

    // 7. Resolver upgrade path.
    $upgrade = $this->resolveUpgradePath($verticalKey, $tier, $lockedFeatures);

    // 8. Resolver estado de suscripción.
    $subscriptionStatus = $tenant->get('subscription_status')->value ?? 'active';
    $trialEnds = $tenant->hasField('trial_ends') ? $tenant->get('trial_ends')->value : NULL;
    $trialDaysRemaining = 0;
    if ($trialEnds && $subscriptionStatus === 'trial') {
      $trialDaysRemaining = max(0, (int) ceil(((int) $trialEnds - time()) / 86400));
    }

    return [
      'plan' => [
        'id' => $planRef->id(),
        'name' => $planName,
        'tier' => $tier,
        'tier_label' => $this->getTierLabel($tier),
        'vertical' => $verticalKey,
        'vertical_label' => $vertical ? $vertical->label() : '',
        'price_monthly' => $planRef->getPriceMonthly(),
        'is_free' => $planRef->isFree(),
      ],
      'subscription' => [
        'status' => $subscriptionStatus,
        'status_label' => $this->getStatusLabel($subscriptionStatus),
        'trial_ends' => $trialEnds ? date('d/m/Y', (int) $trialEnds) : NULL,
        'trial_days_remaining' => $trialDaysRemaining,
      ],
      'features' => [
        'included' => $this->mapFeatureLabels($currentFeatures),
        'locked' => $lockedFeatures,
      ],
      'usage' => $usage,
      'upgrade' => $upgrade,
    ];
  }

  /**
   * Resolver tenant desde UID.
   *
   * TENANT-BRIDGE-001: Usa TenantBridgeService::getTenantForUser().
   */
  protected function resolveTenant(int $uid) {
    // Método directo via TenantBridgeService.
    if ($this->tenantBridge) {
      try {
        return $this->tenantBridge->getTenantForUser($uid);
      }
      catch (\Throwable) {
        // Silenciar — usuario sin tenant.
      }
    }

    return NULL;
  }

  /**
   * Normaliza nombre de plan a tier key.
   */
  protected function resolveTier(string $planName): string {
    $lower = strtolower($planName);
    if (str_contains($lower, 'enterprise')) {
      return 'enterprise';
    }
    if (str_contains($lower, 'profesional') || str_contains($lower, 'professional') || str_contains($lower, 'premium')) {
      return 'professional';
    }
    return 'starter';
  }

  /**
   * Resuelve features bloqueados (disponibles en tiers superiores).
   */
  protected function resolveLockedFeatures(string $vertical, string $currentTier, array $currentFeatures): array {
    if (!$this->planResolver) {
      return [];
    }

    $currentIndex = array_search($currentTier, self::TIER_HIERARCHY, TRUE);
    if ($currentIndex === FALSE) {
      $currentIndex = 0;
    }

    $locked = [];
    // Recorrer tiers superiores.
    for ($i = $currentIndex + 1; $i < count(self::TIER_HIERARCHY); $i++) {
      $nextTier = self::TIER_HIERARCHY[$i];
      $nextFeatures = $this->planResolver->getFeatures($vertical, $nextTier);
      if (!$nextFeatures) {
        continue;
      }

      foreach ($nextFeatures->getFeatures() as $feature) {
        // Solo incluir features que NO están en el plan actual.
        if (!in_array($feature, $currentFeatures, TRUE) && !isset($locked[$feature])) {
          $featureInfo = self::FEATURE_LABELS[$feature] ?? NULL;
          $locked[$feature] = [
            'key' => $feature,
            'label' => $featureInfo ? $this->t($featureInfo['label']) : ucfirst(str_replace('_', ' ', $feature)),
            'icon_cat' => $featureInfo['icon_cat'] ?? 'ui',
            'icon_name' => $featureInfo['icon_name'] ?? 'lock',
            'available_in' => $this->getTierLabel($nextTier),
            'available_in_tier' => $nextTier,
          ];
        }
      }
    }

    return array_values($locked);
  }

  /**
   * Resuelve uso actual vs límites del plan.
   */
  protected function resolveUsage($tenant, string $vertical, string $tier): array {
    if (!$this->planResolver) {
      return [];
    }

    $featuresEntity = $this->planResolver->getFeatures($vertical, $tier);
    if (!$featuresEntity) {
      return [];
    }

    $limits = $featuresEntity->getLimits();
    $usage = [];

    foreach ($limits as $key => $limit) {
      // -1 = ilimitado, no mostrar barra.
      if ($limit === -1) {
        continue;
      }

      $label = self::LIMIT_LABELS[$key] ?? ucfirst(str_replace('_', ' ', $key));
      $current = 0; // Default — el conteo real se resuelve en el dashboard.
      $percentage = $limit > 0 ? min(100, (int) round(($current / $limit) * 100)) : 0;

      $usage[] = [
        'key' => $key,
        'label' => $this->t($label),
        'current' => $current,
        'limit' => $limit,
        'percentage' => $percentage,
        'status' => $percentage >= 80 ? 'danger' : ($percentage >= 60 ? 'warning' : 'normal'),
      ];
    }

    return $usage;
  }

  /**
   * Resuelve el path de upgrade (siguiente tier + precio + checkout URL).
   */
  protected function resolveUpgradePath(string $vertical, string $currentTier, array $lockedFeatures): array {
    $currentIndex = array_search($currentTier, self::TIER_HIERARCHY, TRUE);
    if ($currentIndex === FALSE || $currentIndex >= count(self::TIER_HIERARCHY) - 1) {
      // Ya es Enterprise — no hay upgrade.
      return ['available' => FALSE];
    }

    $nextTier = self::TIER_HIERARCHY[$currentIndex + 1];

    // Buscar SaasPlan para el siguiente tier de esta vertical.
    $nextPlanId = NULL;
    $nextPrice = 0;
    try {
      $plans = $this->entityTypeManager->getStorage('saas_plan')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->execute();

      $planEntities = $this->entityTypeManager->getStorage('saas_plan')->loadMultiple($plans);
      foreach ($planEntities as $plan) {
        $planVertical = $plan->getVertical();
        $planVerticalKey = $planVertical ? $planVertical->get('machine_name')->value : '';
        $planTier = $this->resolveTier($plan->getName());

        if ($planVerticalKey === $vertical && $planTier === $nextTier) {
          $nextPlanId = $plan->id();
          $nextPrice = $plan->getPriceMonthly();
          break;
        }
      }
    }
    catch (\Throwable) {
      // Silenciar — si falla, no mostrar upgrade.
    }

    // TODOS los planes (incluido Enterprise) → checkout directo con Stripe.
    // El precio es público y el usuario sabe lo que obtiene.
    $checkoutUrl = '';
    if ($nextPlanId) {
      try {
        $checkoutUrl = Url::fromRoute('jaraba_billing.checkout', ['saas_plan' => $nextPlanId])->toString();
      }
      catch (\Throwable) {
        // Ruta no disponible.
      }
    }

    $pricingUrl = '';
    try {
      $pricingUrl = Url::fromRoute('ecosistema_jaraba_core.pricing.page')->toString();
    }
    catch (\Throwable) {
      // Fallback silencioso.
    }

    return [
      'available' => TRUE,
      'next_tier' => $nextTier,
      'next_tier_label' => $this->getTierLabel($nextTier),
      'next_tier_price' => $nextPrice,
      'features_unlocked_count' => count($lockedFeatures),
      'checkout_url' => $checkoutUrl,
      'pricing_url' => $pricingUrl,
    ];
  }

  /**
   * Mapea feature keys a arrays con labels humanos.
   */
  protected function mapFeatureLabels(array $featureKeys): array {
    $result = [];
    foreach ($featureKeys as $key) {
      $info = self::FEATURE_LABELS[$key] ?? NULL;
      $result[] = [
        'key' => $key,
        'label' => $info ? $this->t($info['label']) : ucfirst(str_replace('_', ' ', $key)),
        'icon_cat' => $info['icon_cat'] ?? 'ui',
        'icon_name' => $info['icon_name'] ?? 'check',
      ];
    }
    return $result;
  }

  /**
   * Label humano para tier key.
   */
  protected function getTierLabel(string $tier): string {
    return match ($tier) {
      'starter' => (string) $this->t('Starter'),
      'professional' => (string) $this->t('Profesional'),
      'enterprise' => (string) $this->t('Enterprise'),
      default => ucfirst($tier),
    };
  }

  /**
   * Label humano para estado de suscripción.
   */
  protected function getStatusLabel(string $status): string {
    return match ($status) {
      'active' => (string) $this->t('Activa'),
      'trial' => (string) $this->t('Período de prueba'),
      'past_due' => (string) $this->t('Pago pendiente'),
      'suspended' => (string) $this->t('Suspendida'),
      'cancelled' => (string) $this->t('Cancelada'),
      default => ucfirst($status),
    };
  }

  /**
   * Contexto para usuario sin plan (free).
   */
  protected function buildFreePlanContext($tenant): array {
    return [
      'plan' => [
        'id' => 0,
        'name' => (string) $this->t('Sin plan'),
        'tier' => 'free',
        'tier_label' => (string) $this->t('Gratuito'),
        'vertical' => '',
        'vertical_label' => '',
        'price_monthly' => 0,
        'is_free' => TRUE,
      ],
      'subscription' => [
        'status' => 'none',
        'status_label' => (string) $this->t('Sin suscripción'),
        'trial_ends' => NULL,
        'trial_days_remaining' => 0,
      ],
      'features' => [
        'included' => [],
        'locked' => [],
      ],
      'usage' => [],
      'upgrade' => [
        'available' => TRUE,
        'next_tier' => 'starter',
        'next_tier_label' => (string) $this->t('Starter'),
        'next_tier_price' => 0,
        'features_unlocked_count' => 0,
        'checkout_url' => '',
        'pricing_url' => $this->resolveRoute('ecosistema_jaraba_core.pricing.page') ?? '',
      ],
    ];
  }

  /**
   * Helper para resolver ruta a URL.
   */
  protected function resolveRoute(string $route): ?string {
    try {
      return Url::fromRoute($route)->toString();
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
