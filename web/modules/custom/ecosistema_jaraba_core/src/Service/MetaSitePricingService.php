<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface;

/**
 * Provides pricing data for meta-site templates.
 *
 * Reads from SaasPlanTier and SaasPlanFeatures ConfigEntities to build
 * presentation-ready pricing data for landing pages and the /planes page.
 *
 * Resolution cascade (via PlanResolverService):
 *   1. Specific: {vertical}_{tier} (e.g. agroconecta_professional)
 *   2. Default: _default_{tier} (e.g. _default_professional)
 *   3. NULL (no config found → fallback hardcoded)
 *
 * DIRECTRIZ: PLAN-CASCADE-001, PLAN-RESOLVER-001
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\PlanResolverService
 * @see \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTier
 * @see \Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures
 */
class MetaSitePricingService
{

    use StringTranslationTrait;

    /**
     * Constructs a MetaSitePricingService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   The entity type manager.
     * @param \Drupal\ecosistema_jaraba_core\Service\PlanResolverService $planResolver
     *   The plan resolver service for cascade feature resolution.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected PlanResolverService $planResolver,
    ) {
    }

    /**
     * Gets complete pricing data for all tiers, for a specific vertical.
     *
     * Returns a structured array ready for pricing-page.html.twig
     * and _landing-pricing-preview.html.twig. Tiers are sorted by weight
     * (ascending) from the SaasPlanTier ConfigEntity.
     *
     * @param string $vertical
     *   Machine name of the vertical (e.g. 'agroconecta', '_default').
     *
     * @return array
     *   Array of tier data, each containing:
     *   - tier_key: string (e.g. 'starter')
     *   - label: string (e.g. 'Starter')
     *   - description: string
     *   - features: string[] (human-readable feature labels)
     *   - features_raw: string[] (machine names for programmatic checks)
     *   - limits: array (key => int)
     *   - is_recommended: bool (TRUE for 'professional')
     *   - stripe_price_monthly: string
     *   - stripe_price_yearly: string
     */
    public function getPricingPreview(string $vertical): array
    {
        $storage = $this->entityTypeManager->getStorage('saas_plan_tier');
        /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface[] $tiers */
        $tiers = $storage->loadMultiple();

        if (empty($tiers)) {
            return $this->getFallbackTiers();
        }

        // Sort by weight ascending.
        uasort($tiers, static fn(SaasPlanTierInterface $a, SaasPlanTierInterface $b) => $a->getWeight() <=> $b->getWeight());

        // Load SaasPlan ContentEntities for this vertical to get EUR prices.
        $priceMap = $this->loadVerticalPrices($vertical);

        $pricing = [];
        foreach ($tiers as $tier) {
            $tierKey = $tier->getTierKey();
            $features = $this->planResolver->getFeatures($vertical, $tierKey);

            $rawFeatures = $features ? $features->getFeatures() : [];

            // Starter sin features explícitas → inyectar features base.
            if (empty($rawFeatures) && $tierKey === 'starter') {
                $rawFeatures = ['basic_profile', 'community', 'one_vertical', 'email_support'];
            }

            // EUR prices from SaasPlan ContentEntities (indicative, Stripe is financial truth).
            $tierPrices = $priceMap[$tierKey] ?? [];

            $pricing[] = [
                'tier_key' => $tierKey,
                'label' => (string) $tier->label(),
                'description' => (string) $tier->getDescription(),
                'features' => $this->formatFeatureLabels($rawFeatures),
                'features_raw' => $rawFeatures,
                'limits' => $features ? $features->getLimits() : [],
                'is_recommended' => ($tierKey === 'professional'),
                'stripe_price_monthly' => $tier->getStripePriceMonthly(),
                'stripe_price_yearly' => $tier->getStripePriceYearly(),
                'price_monthly' => $tierPrices['price_monthly'] ?? 0.0,
                'price_yearly' => $tierPrices['price_yearly'] ?? 0.0,
                'saas_plan_id' => $tierPrices['saas_plan_id'] ?? NULL,
            ];
        }

        return $pricing;
    }

    /**
     * Gets the "from price" display data for a vertical's landing page.
     *
     * Used in the pricing preview section of vertical landing pages.
     * Returns data for the lowest-cost tier (usually 'starter' / free).
     *
     * @param string $vertical
     *   Machine name of the vertical.
     *
     * @return array
     *   Array with:
     *   - from_price: string (e.g. '0€/mes')
     *   - from_label: string (e.g. 'Empieza gratis')
     *   - features_highlights: string[] (up to 4 feature names)
     *   - cta_url: string (URL to /planes or /user/register)
     */
    public function getFromPrice(string $vertical): array
    {
        $starterFeatures = $this->planResolver->getFeatures($vertical, 'starter');

        // Get first 4 highlight features for display.
        $highlights = $starterFeatures
            ? array_slice($starterFeatures->getFeatures(), 0, 4)
            : [];

        // Load the cheapest price for display (free or starter).
        $priceMap = $this->loadVerticalPrices($vertical);
        $starterPrice = $priceMap['free']['price_monthly'] ?? $priceMap['starter']['price_monthly'] ?? 0.0;
        $fromPrice = $starterPrice > 0
            ? (string) $this->t('@price€/mes', ['@price' => number_format($starterPrice, 0)])
            : (string) $this->t('0€/mes');
        $fromLabel = $starterPrice > 0
            ? (string) $this->t('Desde @price€/mes', ['@price' => number_format($starterPrice, 0)])
            : (string) $this->t('Empieza gratis');

        // ROUTE-LANGPREFIX-001: Always use Url::fromRoute(), never hardcoded paths.
        $validVerticals = ['empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta', 'jarabalex', 'serviciosconecta', 'formacion'];
        if (in_array($vertical, $validVerticals, TRUE)) {
            $ctaUrl = Url::fromRoute('ecosistema_jaraba_core.pricing.vertical', ['vertical_key' => $vertical])->toString();
        }
        else {
            $ctaUrl = Url::fromRoute('ecosistema_jaraba_core.pricing.page')->toString();
        }

        return [
            'from_price' => $fromPrice,
            'from_price_display' => number_format($starterPrice, 0),
            'from_label' => $fromLabel,
            'features_highlights' => $this->formatFeatureLabels($highlights),
            'cta_url' => $ctaUrl,
        ];
    }

    /**
     * Returns fallback tier data when no SaasPlanTier entities are configured.
     *
     * Ensures the pricing page always renders, even in fresh installs
     * or test environments without ConfigEntity data.
     *
     * @return array
     *   Array of 3 fallback tiers (Starter, Profesional, Empresa).
     */
    protected function getFallbackTiers(): array
    {
        return [
            [
                'tier_key' => 'free',
                'label' => (string) $this->t('Free'),
                'description' => (string) $this->t('Para explorar la plataforma sin compromiso'),
                'features' => [
                    (string) $this->t('Perfil básico'),
                    (string) $this->t('Acceso a la comunidad'),
                    (string) $this->t('Copilot IA limitado'),
                ],
                'features_raw' => ['basic_profile', 'community', 'copilot_ia'],
                'limits' => ['max_pages' => 1, 'storage_gb' => 0.5],
                'is_recommended' => FALSE,
                'stripe_price_monthly' => '',
                'stripe_price_yearly' => '',
                'price_monthly' => 0.0,
                'price_yearly' => 0.0,
                'saas_plan_id' => NULL,
            ],
            [
                'tier_key' => 'starter',
                'label' => (string) $this->t('Starter'),
                'description' => (string) $this->t('Para empezar a explorar la plataforma'),
                'features' => [
                    (string) $this->t('Todo lo del plan Free'),
                    (string) $this->t('1 vertical incluida'),
                    (string) $this->t('Soporte por email'),
                ],
                'features_raw' => ['basic_profile', 'community', 'one_vertical', 'email_support'],
                'limits' => ['max_pages' => 3, 'storage_gb' => 1],
                'is_recommended' => FALSE,
                'stripe_price_monthly' => '',
                'stripe_price_yearly' => '',
                'price_monthly' => 19.0,
                'price_yearly' => 182.0,
                'saas_plan_id' => NULL,
            ],
            [
                'tier_key' => 'professional',
                'label' => (string) $this->t('Profesional'),
                'description' => (string) $this->t('Para profesionales y negocios en crecimiento'),
                'features' => [
                    (string) $this->t('Todo lo del plan Starter'),
                    (string) $this->t('IA integrada ilimitada'),
                    (string) $this->t('Todas las verticales'),
                    (string) $this->t('Analytics avanzado'),
                    (string) $this->t('Soporte prioritario'),
                ],
                'limits' => ['max_pages' => 25, 'storage_gb' => 10],
                'is_recommended' => TRUE,
                'stripe_price_monthly' => '',
                'stripe_price_yearly' => '',
                'price_monthly' => 29.0,
                'price_yearly' => 290.0,
            ],
            [
                'tier_key' => 'enterprise',
                'label' => (string) $this->t('Empresa'),
                'description' => (string) $this->t('Para instituciones y grandes organizaciones'),
                'features' => [
                    (string) $this->t('Todo lo del plan Profesional'),
                    (string) $this->t('Multi-tenant dedicado'),
                    (string) $this->t('SLA garantizado'),
                    (string) $this->t('Onboarding personalizado'),
                    (string) $this->t('API completa'),
                    (string) $this->t('Account manager dedicado'),
                ],
                'limits' => ['max_pages' => -1, 'storage_gb' => -1],
                'is_recommended' => FALSE,
                'stripe_price_monthly' => '',
                'stripe_price_yearly' => '',
                'price_monthly' => 99.0,
                'price_yearly' => 990.0,
            ],
        ];
    }

    /**
     * Loads SaasPlan ContentEntities for a vertical and returns price map by tier weight.
     *
     * Maps each tier to its EUR prices by matching SaasPlan weight ranges
     * to tier keys: weight 0-9 = starter, 10-19 = starter/professional (lowest),
     * 20+ = professional/enterprise.
     *
     * @param string $vertical
     *   Machine name of the vertical.
     *
     * @return array
     *   Map of tier_key => ['price_monthly' => float, 'price_yearly' => float].
     */
    protected function loadVerticalPrices(string $vertical): array
    {
        $priceMap = [];

        try {
            // Resolve vertical entity by machine_name.
            $verticals = $this->entityTypeManager->getStorage('vertical')
                ->loadByProperties(['machine_name' => $vertical, 'status' => TRUE]);

            if (empty($verticals)) {
                return $priceMap;
            }

            $verticalEntity = reset($verticals);

            // Load active plans for this vertical, sorted by weight.
            $planIds = $this->entityTypeManager->getStorage('saas_plan')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('vertical', $verticalEntity->id())
                ->condition('status', TRUE)
                ->sort('weight', 'ASC')
                ->execute();

            $plans = $this->entityTypeManager->getStorage('saas_plan')
                ->loadMultiple($planIds);

            // Assign plans to tiers by position.
            // Con plan Free (weight=0): Free, Starter, Professional, Enterprise.
            // Sin plan Free: Starter, Professional, Enterprise.
            $firstPlanPrice = 0.0;
            $firstPlan = reset($plans);
            if ($firstPlan) {
                $firstPlanPrice = (float) $firstPlan->getPriceMonthly();
            }
            $tierKeys = $firstPlanPrice <= 0.0
                ? ['free', 'starter', 'professional', 'enterprise']
                : ['starter', 'professional', 'enterprise'];
            $index = 0;
            foreach ($plans as $plan) {
                if ($index >= count($tierKeys)) {
                    break;
                }
                $priceMap[$tierKeys[$index]] = [
                    'price_monthly' => (float) $plan->getPriceMonthly(),
                    'price_yearly' => (float) $plan->getPriceYearly(),
                    'saas_plan_id' => (int) $plan->id(),
                ];
                $index++;
            }
        }
        catch (\Exception $e) {
            // PRESAVE-RESILIENCE-001: pricing data is non-critical, never crash.
        }

        return $priceMap;
    }

    /**
     * Maps feature machine names to translated human-readable labels.
     *
     * Centraliza el mapeo de nombres técnicos → labels de UI para que tanto
     * la página /planes como los previews de landing usen la misma fuente.
     * Los machine names desconocidos se formatean automáticamente
     * (ucfirst + guiones bajos → espacios).
     *
     * DIRECTRIZ: i18n — Todos los labels pasan por $this->t().
     *
     * @param string[] $machineNames
     *   Machine names from SaasPlanFeatures config entity.
     *
     * @return string[]
     *   Human-readable, translated labels.
     */
    protected function formatFeatureLabels(array $machineNames): array
    {
        $map = [
            // Base / Starter.
            'basic_profile' => $this->t('Perfil básico'),
            'community' => $this->t('Acceso a la comunidad'),
            'one_vertical' => $this->t('1 vertical incluida'),
            'email_support' => $this->t('Soporte por email'),
            // Platform features.
            'seo_advanced' => $this->t('SEO Avanzado'),
            'ab_testing' => $this->t('A/B Testing'),
            'analytics' => $this->t('Analytics y reportes'),
            'schema_org' => $this->t('Schema.org estructurado'),
            'premium_blocks' => $this->t('Bloques premium'),
            'api_access' => $this->t('Acceso a API'),
            'personalizacion_marca' => $this->t('Personalización de colores y logo'),
            'white_label' => $this->t('Dominio propio + marca blanca (servicio profesional)'),
            // Domain features.
            'trazabilidad_basica' => $this->t('Trazabilidad básica'),
            'trazabilidad_avanzada' => $this->t('Trazabilidad avanzada'),
            'firma_digital' => $this->t('Firma digital'),
            'qr_codes' => $this->t('QR verificables'),
            'ai_assistant' => $this->t('Asistente de IA'),
            'ai_agents' => $this->t('Agentes de IA'),
            'webhooks' => $this->t('Webhooks'),
            'custom_branding' => $this->t('Marca personalizada'),
            'priority_support' => $this->t('Soporte prioritario'),
            'export_data' => $this->t('Exportación de datos'),
            'multi_user' => $this->t('Multi-usuario'),
            'sso' => $this->t('Single Sign-On'),
            'integrations' => $this->t('Integraciones externas'),
            'copilot' => $this->t('Copiloto IA'),
            'copilot_unlimited' => $this->t('Copiloto IA ilimitado'),
            'legal_ai' => $this->t('Investigación legal IA'),
            'marketplace' => $this->t('Marketplace incluido'),
            'ecommerce' => $this->t('Tienda online'),
            // JarabaLex features.
            'legal_search' => $this->t('Búsqueda legal inteligente'),
            'legal_alerts' => $this->t('Alertas jurídicas'),
            'legal_citations' => $this->t('Citaciones cruzadas'),
            'legal_calendar' => $this->t('Agenda de plazos'),
            'legal_vault' => $this->t('Bóveda documental'),
            'legal_billing' => $this->t('Facturación legal'),
            'legal_templates' => $this->t('Plantillas de documentos'),
            'legal_lexnet' => $this->t('Integración LexNET'),
            // Formacion / LMS features.
            'course_builder' => $this->t('Constructor de cursos'),
            'student_portal' => $this->t('Portal del alumno'),
            'basic_certificates' => $this->t('Certificados automáticos'),
            'learning_paths' => $this->t('Rutas de aprendizaje'),
            'gamification' => $this->t('Gamificación'),
            'xapi_tracking' => $this->t('Seguimiento xAPI'),
            // Empleabilidad features.
            'cv_builder_advanced' => $this->t('CV Builder avanzado'),
            'job_alerts_email' => $this->t('Alertas de empleo'),
            'diagnostico_competencias' => $this->t('Diagnóstico de competencias'),
            'cv_builder_ia' => $this->t('CV Builder con IA'),
            'matching_inteligente' => $this->t('Matching inteligente'),
            'simulador_entrevistas' => $this->t('Simulador de entrevistas'),
            'credenciales_digitales' => $this->t('Credenciales digitales'),
            'health_score' => $this->t('Health Score profesional'),
            'copilot_ia' => $this->t('Copiloto IA'),
            'rutas_formativas' => $this->t('Rutas formativas'),
            // Emprendimiento features.
            'calculadora_madurez' => $this->t('Calculadora de madurez'),
            'bmc_ia' => $this->t('Business Model Canvas con IA'),
            'validacion_mvp' => $this->t('Validación MVP'),
            'mastermind_grupal' => $this->t('Mastermind Grupal'),
            // Andalucia +ei features.
            'formacion_certificada' => $this->t('Formación certificada'),
            'expediente_digital' => $this->t('Expediente digital'),
            // Shared features.
            'soporte_email' => $this->t('Soporte por email'),
            'soporte_chat' => $this->t('Soporte por chat'),
            'soporte_dedicado' => $this->t('Email prioritario 24h'),
            // Comercio/Agro features.
            'calendar_sync' => $this->t('Sincronización de calendario'),
            'buzon_confianza' => $this->t('Buzón de confianza'),
            'traceability_qr' => $this->t('Trazabilidad QR'),
            'seo_local_audit' => $this->t('Auditoría SEO local'),
        ];

        $labels = [];
        foreach ($machineNames as $name) {
            $labels[] = isset($map[$name])
                ? (string) $map[$name]
                : ucfirst(str_replace('_', ' ', $name));
        }

        return $labels;
    }

}
