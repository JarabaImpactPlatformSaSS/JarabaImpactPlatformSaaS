<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

        $pricing = [];
        foreach ($tiers as $tier) {
            $tierKey = $tier->getTierKey();
            $features = $this->planResolver->getFeatures($vertical, $tierKey);

            $rawFeatures = $features ? $features->getFeatures() : [];

            // Starter sin features explícitas → inyectar features base.
            if (empty($rawFeatures) && $tierKey === 'starter') {
                $rawFeatures = ['basic_profile', 'community', 'one_vertical', 'email_support'];
            }

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

        return [
            'from_price' => (string) $this->t('0€/mes'),
            'from_label' => (string) $this->t('Empieza gratis'),
            'features_highlights' => $this->formatFeatureLabels($highlights),
            'cta_url' => '/planes',
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
                'tier_key' => 'starter',
                'label' => (string) $this->t('Starter'),
                'description' => (string) $this->t('Para empezar a explorar la plataforma'),
                'features' => [
                    (string) $this->t('Perfil básico'),
                    (string) $this->t('Acceso a la comunidad'),
                    (string) $this->t('1 vertical incluida'),
                    (string) $this->t('Soporte por email'),
                ],
                'limits' => ['max_pages' => 3, 'storage_gb' => 1],
                'is_recommended' => FALSE,
                'stripe_price_monthly' => '',
                'stripe_price_yearly' => '',
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
            ],
        ];
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
            'white_label' => $this->t('Marca blanca'),
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
