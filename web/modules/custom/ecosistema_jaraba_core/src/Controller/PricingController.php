<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;
use Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador para la API pública de precios y página /planes.
 *
 * Proporciona:
 * - Endpoints JSON API consumidos por widgets y comparadores externos
 * - Página HTML /planes con pricing dinámico desde ConfigEntities
 *
 * DIRECTRICES:
 * - i18n: $this->t() en todos los textos facing del controlador.
 * - LEGAL-ROUTE-001: URL en español (/planes), SEO-friendly.
 * - PLAN-CASCADE-001: Datos resueltos vía PlanResolverService cascade.
 * - Zero Region Policy: Template usa clean_content, no page.content.
 *
 * @example
 * GET /api/v1/pricing/agroconecta
 * Retorna los planes disponibles con precios para AgroConecta.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService
 */
class PricingController extends ControllerBase
{

    /**
     * The meta-site pricing service (optional, may be NULL in older installs).
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService|null
     */
    protected ?MetaSitePricingService $pricingService = NULL;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        if ($container->has('ecosistema_jaraba_core.metasite_pricing')) {
            $instance->pricingService = $container->get('ecosistema_jaraba_core.metasite_pricing');
        }
        return $instance;
    }

    /**
     * Obtiene los planes de precios para una vertical.
     *
     * Retorna un JSON con todos los planes activos para la vertical
     * especificada, incluyendo precios, límites y características.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $vertical
     *   La vertical para la que obtener los planes.
     *
     * @return \Drupal\Core\Cache\CacheableJsonResponse
     *   Respuesta JSON cacheable con los planes.
     */
    public function getPricing(VerticalInterface $vertical): CacheableJsonResponse
    {
        // Cargar todos los planes activos para esta vertical
        $planStorage = $this->entityTypeManager()->getStorage('saas_plan');

        $query = $planStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', TRUE)
            ->condition('vertical', $vertical->id())
            ->sort('weight', 'ASC');

        $planIds = $query->execute();
        $plans = $planStorage->loadMultiple($planIds);

        // Construir respuesta
        $response = [
            'vertical' => [
                'id' => $vertical->id(),
                'name' => $vertical->getName(),
                'machine_name' => $vertical->getMachineName(),
            ],
            'currency' => 'EUR',
            'trial_days' => 14,
            'plans' => [],
        ];

        foreach ($plans as $plan) {
            $limits = $plan->getLimits();
            $features = $plan->getFeatures();

            $planData = [
                'id' => $plan->id(),
                'name' => $plan->getName(),
                'description' => $plan->get('description')->value ?? '',
                'pricing' => [
                    'monthly' => [
                        'amount' => (float) $plan->getMonthlyPrice(),
                        'currency' => 'EUR',
                    ],
                    'yearly' => [
                        'amount' => (float) $plan->getYearlyPrice(),
                        'monthly_equivalent' => round($plan->getYearlyPrice() / 12, 2),
                        'currency' => 'EUR',
                        'savings_percent' => $this->calculateYearlySavings($plan),
                    ],
                ],
                'limits' => [
                    'producers' => $limits['productores'] ?? 0,
                    'producers_unlimited' => ($limits['productores'] ?? 0) === -1,
                    'storage_gb' => $limits['storage_gb'] ?? 0,
                    'storage_unlimited' => ($limits['storage_gb'] ?? 0) === -1,
                    'ai_queries' => $limits['ai_queries'] ?? 0,
                    'ai_queries_unlimited' => ($limits['ai_queries'] ?? 0) === -1,
                ],
                'features' => $this->formatFeatures($features),
                'is_free' => $plan->isFree(),
                'is_recommended' => $plan->get('recommended')->value ?? FALSE,
                'cta_url' => $plan->isFree()
                    ? '/registro/' . $vertical->getMachineName() . '?plan=' . $plan->id()
                    : Url::fromRoute('jaraba_billing.checkout', ['saas_plan' => $plan->id()])->toString(),
            ];

            $response['plans'][] = $planData;
        }

        // Crear respuesta cacheable
        $cacheableResponse = new CacheableJsonResponse($response);

        // Configurar metadata de cache
        $cacheMetadata = new CacheableMetadata();
        $cacheMetadata->setCacheMaxAge(3600); // 1 hora
        $cacheMetadata->setCacheTags(['saas_plan_list', 'vertical:' . $vertical->id()]);
        $cacheMetadata->setCacheContexts(['url.path']);

        $cacheableResponse->addCacheableDependency($cacheMetadata);
        $cacheableResponse->addCacheableDependency($vertical);

        foreach ($plans as $plan) {
            $cacheableResponse->addCacheableDependency($plan);
        }

        return $cacheableResponse;
    }

    /**
     * Obtiene todos los planes de todas las verticales.
     *
     * Útil para páginas de comparación general.
     *
     * @return \Drupal\Core\Cache\CacheableJsonResponse
     *   Respuesta JSON con todos los planes agrupados por vertical.
     */
    public function getAllPricing(): CacheableJsonResponse
    {
        $verticalStorage = $this->entityTypeManager()->getStorage('vertical');
        $planStorage = $this->entityTypeManager()->getStorage('saas_plan');

        // Cargar todas las verticales activas
        $verticals = $verticalStorage->loadByProperties(['status' => TRUE]);

        $response = [
            'currency' => 'EUR',
            'trial_days' => 14,
            'verticals' => [],
        ];

        $cacheMetadata = new CacheableMetadata();
        $cacheMetadata->setCacheMaxAge(3600);
        $cacheMetadata->setCacheTags(['saas_plan_list', 'vertical_list']);

        foreach ($verticals as $vertical) {
            $plans = $planStorage->loadByProperties([
                'status' => TRUE,
                'vertical' => $vertical->id(),
            ]);

            $verticalData = [
                'id' => $vertical->id(),
                'name' => $vertical->getName(),
                'machine_name' => $vertical->getMachineName(),
                'description' => $vertical->getDescription(),
                'plans' => [],
            ];

            foreach ($plans as $plan) {
                $verticalData['plans'][] = [
                    'id' => $plan->id(),
                    'name' => $plan->getName(),
                    'monthly_price' => (float) $plan->getMonthlyPrice(),
                    'yearly_price' => (float) $plan->getYearlyPrice(),
                    'is_free' => $plan->isFree(),
                ];

                $cacheMetadata->addCacheableDependency($plan);
            }

            $response['verticals'][] = $verticalData;
            $cacheMetadata->addCacheableDependency($vertical);
        }

        $cacheableResponse = new CacheableJsonResponse($response);
        $cacheableResponse->addCacheableDependency($cacheMetadata);

        return $cacheableResponse;
    }

    /**
     * Calcula el porcentaje de ahorro del plan anual.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
     *   El plan a calcular.
     *
     * @return int
     *   Porcentaje de ahorro (0-100).
     */
    protected function calculateYearlySavings($plan): int
    {
        $monthly = $plan->getMonthlyPrice();
        $yearly = $plan->getYearlyPrice();

        if ($monthly <= 0) {
            return 0;
        }

        $yearlyIfMonthly = $monthly * 12;
        $savings = (($yearlyIfMonthly - $yearly) / $yearlyIfMonthly) * 100;

        return (int) round($savings);
    }

    /**
     * Formatea las features para la respuesta JSON.
     *
     * @param array $features
     *   Array de machine names de features.
     *
     * @return array
     *   Array formateado con nombres legibles.
     */
    protected function formatFeatures(array $features): array
    {
        // Mapeo de machine names a nombres legibles
        $featureLabels = [
            'trazabilidad_basica' => 'Trazabilidad básica',
            'trazabilidad_avanzada' => 'Trazabilidad avanzada',
            'firma_digital' => 'Firma digital de documentos',
            'qr_codes' => 'Códigos QR verificables',
            'ai_assistant' => 'Asistente de IA',
            'ai_agents' => 'Agentes de IA especializados',
            'api_access' => 'Acceso a API',
            'webhooks' => 'Webhooks personalizados',
            'custom_branding' => 'Marca personalizada',
            'priority_support' => 'Soporte prioritario',
            'analytics' => 'Analytics y reportes',
            'export_data' => 'Exportación de datos',
            'multi_user' => 'Multi-usuario',
            'sso' => 'Single Sign-On (SSO)',
            'integrations' => 'Integraciones externas',
        ];

        $formatted = [];

        foreach ($features as $feature) {
            $formatted[] = [
                'id' => $feature,
                'name' => $featureLabels[$feature] ?? ucfirst(str_replace('_', ' ', $feature)),
                'included' => TRUE,
            ];
        }

        return $formatted;
    }

    /**
     * Renders the /planes hub page with a grid of verticals.
     *
     * Each vertical card shows the starting price and links to /{vertical}/planes.
     * This replaces the generic 3-tier view to guide users to vertical-specific
     * pricing pages where cascade resolution provides accurate data.
     *
     * @return array
     *   Render array with pricing_hub_page theme.
     */
    public function pricingPage(): array
    {
        $verticalLabels = $this->getVerticalLabels();
        $verticalTaglines = $this->getVerticalTaglines();
        $verticalIcons = $this->getVerticalIcons();

        // 7 priority verticals with public pricing pages.
        $priorityVerticals = [
            'empleabilidad', 'emprendimiento', 'comercioconecta',
            'agroconecta', 'jarabalex', 'serviciosconecta', 'formacion',
        ];

        $verticals = [];
        foreach ($priorityVerticals as $key) {
            $fromPrice = $this->pricingService
                ? $this->pricingService->getFromPrice($key)
                : ['from_label' => $this->t('Empieza gratis')];

            $icon = $verticalIcons[$key] ?? ['category' => 'verticals', 'name' => $key];

            $verticals[] = [
                'key' => $key,
                'label' => $verticalLabels[$key] ?? ucfirst($key),
                'tagline' => $verticalTaglines[$key] ?? '',
                'icon_category' => $icon['category'],
                'icon_name' => $icon['name'],
                'from_label' => $fromPrice['from_label'] ?? $this->t('Empieza gratis'),
                'from_price' => $fromPrice['from_price'] ?? $this->t('0€/mes'),
                'plans_url' => Url::fromRoute('ecosistema_jaraba_core.pricing.vertical', ['vertical_key' => $key])->toString(),
            ];
        }

        // NO-HARDCODE-PRICE-001: Overview tiers with "Desde X€/mes" — minimum
        // price across all verticals for each tier. '_default' has no SaasPlan
        // entities, so we compute min prices from the 7 priority verticals.
        $overviewTiers = [];
        if ($this->pricingService) {
            // Collect prices from all priority verticals per tier.
            $tierMinPrices = [];
            foreach ($priorityVerticals as $vKey) {
                $vTiers = $this->pricingService->getPricingPreview($vKey);
                foreach ($vTiers as $tier) {
                    $tk = $tier['tier_key'];
                    $pm = (float) ($tier['price_monthly'] ?? 0);
                    if ($pm > 0 && (!isset($tierMinPrices[$tk]) || $pm < $tierMinPrices[$tk])) {
                        $tierMinPrices[$tk] = $pm;
                    }
                }
            }

            // Build overview tiers with min prices from SaasPlanTier ConfigEntities.
            $tierEntities = $this->pricingService->getPricingPreview(
                $priorityVerticals[0] ?? 'empleabilidad'
            );
            foreach ($tierEntities as $tier) {
                $tk = $tier['tier_key'];
                $overviewTiers[] = [
                    'label' => $tier['label'],
                    'tier_key' => $tk,
                    'price_monthly' => $tierMinPrices[$tk] ?? 0.0,
                    'is_recommended' => $tier['is_recommended'],
                    'description' => $tier['description'],
                ];
            }
        }

        return [
            '#theme' => 'pricing_hub_page',
            '#verticals' => $verticals,
            '#overview_tiers' => $overviewTiers,
            '#page_title' => $this->t('Elige tu vertical y encuentra el plan perfecto'),
            '#page_subtitle' => $this->t('7 soluciones verticalizadas. Sin permanencia. Cancela cuando quieras.'),
            '#guarantee_text' => $this->getPlgGuaranteeText(),
            '#tax_disclaimer' => $this->getPlgTaxDisclaimer(),
            '#faq_items' => $this->getPricingFaq(),
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/global',
                    'ecosistema_jaraba_theme/pricing-hub',
                    'ecosistema_jaraba_theme/pricing-page',
                ],
            ],
            '#cache' => [
                'tags' => [
                    'config:saas_plan_tier_list',
                    'config:saas_plan_features_list',
                    'saas_plan_list',
                ],
                'contexts' => ['languages:language_content'],
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Renders the /planes/{vertical_key} pricing page for a specific vertical.
     *
     * Loads all SaasPlanTier entities and enriches each with features/limits
     * specific to the given vertical via PlanResolverService cascade.
     * Includes EUR prices from SaasPlan ContentEntities.
     *
     * VERT-PRICING-001: Route defined in ecosistema_jaraba_core.routing.yml
     * with regex constraint for the 7 priority verticals.
     *
     * @param string $vertical_key
     *   Machine name of the vertical (e.g. 'agroconecta', 'jarabalex').
     *
     * @return array
     *   Render array with pricing_page theme.
     */
    public function verticalPricingPage(string $vertical_key): array
    {
        // Get tiers with vertical-specific features via cascade resolution.
        $tiers = $this->pricingService
            ? $this->pricingService->getPricingPreview($vertical_key)
            : [];

        // Vertical display names for SEO and UX.
        $verticalLabels = $this->getVerticalLabels();
        $verticalLabel = $verticalLabels[$vertical_key] ?? ucfirst($vertical_key);

        return [
            '#theme' => 'pricing_page',
            '#tiers' => $tiers,
            '#vertical_key' => $vertical_key,
            '#vertical_label' => $verticalLabel,
            '#page_title' => $this->t('Planes @vertical', ['@vertical' => $verticalLabel]),
            '#page_subtitle' => $this->t('Elige el plan que mejor se adapta a tu negocio. 14 días de prueba gratis en todos los planes.'),
            '#guarantee_text' => $this->getPlgGuaranteeText(),
            '#tax_disclaimer' => $this->getPlgTaxDisclaimer(),
            '#faq_items' => $this->getVerticalPricingFaq($vertical_key),
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/global',
                    'ecosistema_jaraba_theme/pricing-page',
                ],
            ],
            '#cache' => [
                'tags' => [
                    'config:saas_plan_tier_list',
                    'config:saas_plan_features_list',
                    'saas_plan_list',
                ],
                'contexts' => ['languages:language_content', 'url.path'],
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Title callback for the vertical pricing page.
     *
     * @param string $vertical_key
     *   Machine name of the vertical.
     *
     * @return string
     *   Page title.
     */
    public function verticalPricingTitle(string $vertical_key): string
    {
        $labels = $this->getVerticalLabels();
        $label = $labels[$vertical_key] ?? ucfirst($vertical_key);
        return (string) $this->t('Planes y Precios — @vertical', ['@vertical' => $label]);
    }

    /**
     * Returns the canonical display labels for each vertical.
     *
     * @return array
     *   Map of machine_name => display label.
     */
    protected function getVerticalLabels(): array
    {
        return [
            'empleabilidad' => 'Empleabilidad',
            'emprendimiento' => 'Emprendimiento',
            'comercioconecta' => 'ComercioConecta',
            'agroconecta' => 'AgroConecta',
            'jarabalex' => 'JarabaLex',
            'serviciosconecta' => 'ServiciosConecta',
            'formacion' => 'Formación',
            'andalucia_ei' => 'Andalucía +ei',
            'jaraba_content_hub' => 'Content Hub',
            'demo' => 'Demo',
        ];
    }

    /**
     * Returns taglines for each vertical.
     *
     * @return array
     *   Map of machine_name => tagline string.
     */
    protected function getVerticalTaglines(): array
    {
        return [
            'empleabilidad' => (string) $this->t('Impulsa tu carrera profesional con IA'),
            'emprendimiento' => (string) $this->t('Valida y lanza tu idea de negocio'),
            'comercioconecta' => (string) $this->t('Tu tienda online con marketplace integrado'),
            'agroconecta' => (string) $this->t('Trazabilidad y comercio agroalimentario'),
            'jarabalex' => (string) $this->t('Gestión legal inteligente para despachos'),
            'serviciosconecta' => (string) $this->t('Gestiona y promociona tus servicios'),
            'formacion' => (string) $this->t('Tu academia online con IA y gamificación'),
        ];
    }

    /**
     * Returns icon references for each vertical.
     *
     * @return array
     *   Map of machine_name => ['category' => string, 'name' => string].
     */
    protected function getVerticalIcons(): array
    {
        return [
            'empleabilidad' => ['category' => 'verticals', 'name' => 'empleabilidad'],
            'emprendimiento' => ['category' => 'verticals', 'name' => 'emprendimiento'],
            'comercioconecta' => ['category' => 'verticals', 'name' => 'comercioconecta'],
            'agroconecta' => ['category' => 'verticals', 'name' => 'agroconecta'],
            'jarabalex' => ['category' => 'verticals', 'name' => 'jarabalex'],
            'serviciosconecta' => ['category' => 'verticals', 'name' => 'serviciosconecta'],
            'formacion' => ['category' => 'verticals', 'name' => 'formacion'],
        ];
    }

    /**
     * Builds FAQ items specific to a vertical pricing page.
     *
     * Includes the general FAQ plus vertical-specific questions.
     *
     * @param string $vertical_key
     *   Machine name of the vertical.
     *
     * @return array
     *   Array of FAQ items with 'question' and 'answer' keys.
     */
    /**
     * Obtiene el texto de garantía PLG desde Theme Settings (configurable desde UI).
     *
     * Falls back a texto por defecto si no está configurado.
     * Directriz: textos PLG configurables desde Appearance > Ecosistema Jaraba Theme.
     */
    protected function getPlgGuaranteeText(): string
    {
        try {
            $setting = theme_get_setting('plg_guarantee_text', 'ecosistema_jaraba_theme');
            if (is_string($setting) && $setting !== '') {
                return $setting;
            }
        }
        catch (\Throwable) {
            // Theme settings no disponibles.
        }
        return (string) $this->t('14 días de prueba gratis. Sin permanencia. Cancela cuando quieras.');
    }

    protected function getVerticalPricingFaq(string $vertical_key): array
    {
        // Start with general FAQ items.
        $faq = $this->getPricingFaq();

        // Add vertical-specific question.
        $verticalLabels = $this->getVerticalLabels();
        $label = $verticalLabels[$vertical_key] ?? ucfirst($vertical_key);

        $faq[] = [
            'question' => $this->t('¿Puedo combinar @vertical con otros verticales?', ['@vertical' => $label]),
            'answer' => $this->t('Sí. Tu cuenta te da acceso a la plataforma completa. Puedes activar funcionalidades de otros verticales según tu plan.'),
        ];

        return $faq;
    }

    /**
     * Builds the FAQ items for the pricing page.
     *
     * DIRECTRIZ: i18n — $this->t() en todos los textos.
     *
     * @return array
     *   Array of FAQ items with 'question' and 'answer' keys.
     */
    /**
     * Gets the tax disclaimer text from Theme Settings.
     *
     * NO-HARDCODE-PRICE-001: Configurable from Appearance > Ecosistema Jaraba
     * Theme > PLG / Textos de conversión > Aviso fiscal.
     */
    protected function getPlgTaxDisclaimer(): string
    {
        try {
            // @phpstan-ignore-next-line theme_get_setting deprecated in 11.3, keep for compat.
            $setting = theme_get_setting('plg_tax_disclaimer', 'ecosistema_jaraba_theme');
            if (is_string($setting) && $setting !== '') {
                return $setting;
            }
        }
        catch (\Throwable) {
            // Theme settings unavailable.
        }
        return (string) $this->t('Todos los precios se muestran en EUR sin IVA. El IVA aplicable (21%% en España peninsular) se calculará y desglosará en el proceso de contratación según la normativa vigente. Para Canarias, Ceuta y Melilla se aplican los impuestos indirectos correspondientes (IGIC/IPSI).');
    }

    /**
     * Returns pricing FAQ items.
     *
     * NO-HARDCODE-PRICE-001: Reads from Theme Settings first (plg_pricing_faq
     * JSON field). Falls back to hardcoded defaults only if Theme Settings
     * is empty or invalid JSON.
     *
     * @return array
     *   Array of FAQ items with 'question' and 'answer' keys.
     */
    protected function getPricingFaq(): array
    {
        // Try Theme Settings first (configurable from admin UI).
        try {
            // @phpstan-ignore-next-line theme_get_setting deprecated in 11.3, keep for compat.
            $jsonFaq = theme_get_setting('plg_pricing_faq', 'ecosistema_jaraba_theme');
            if (is_string($jsonFaq) && $jsonFaq !== '') {
                $parsed = json_decode($jsonFaq, TRUE);
                if (is_array($parsed) && $parsed !== [] && isset($parsed[0]['question'])) {
                    return $parsed;
                }
            }
        }
        catch (\Throwable) {
            // Theme settings unavailable.
        }

        // Fallback: hardcoded defaults (only used if no UI config).
        return [
            [
                'question' => $this->t('¿Puedo empezar gratis?'),
                'answer' => $this->t('Sí. Todos los verticales incluyen un plan gratuito para que explores la plataforma sin compromiso. Los planes de pago incluyen 14 días de prueba gratis.'),
            ],
            [
                'question' => $this->t('¿Puedo cambiar de plan en cualquier momento?'),
                'answer' => $this->t('Sí. Puedes actualizar o cambiar tu plan en cualquier momento desde tu panel de control. Los cambios se aplican inmediatamente.'),
            ],
            [
                'question' => $this->t('¿Qué métodos de pago aceptáis?'),
                'answer' => $this->t('Aceptamos tarjetas de crédito/débito (Visa, Mastercard, AMEX), SEPA (domiciliación bancaria), Apple Pay, Google Pay y Bizum.'),
            ],
            [
                'question' => $this->t('¿Hay permanencia o compromiso?'),
                'answer' => $this->t('No. Puedes cancelar o pausar tu suscripción cuando quieras. Si cancelas, mantienes acceso hasta el final del período facturado.'),
            ],
            [
                'question' => $this->t('¿Ofrecéis descuento para instituciones o grandes organizaciones?'),
                'answer' => $this->t('Sí. El plan Enterprise incluye precios personalizados según volumen. Contacta con nosotros para recibir una propuesta a medida.'),
            ],
            [
                'question' => $this->t('¿Mis datos están seguros?'),
                'answer' => $this->t('Totalmente. Cumplimos el RGPD al 100%%. Nuestros servidores están en la Unión Europea (IONOS, Alemania), con cifrado SSL en tránsito, backups diarios y aislamiento por tenant.'),
            ],
            [
                'question' => $this->t('¿Puedo exportar mis datos?'),
                'answer' => $this->t('Sí. Desde tu panel de control puedes exportar tus datos en formato CSV y JSON en cualquier momento. Los planes Professional y Enterprise incluyen acceso completo a la API REST.'),
            ],
            [
                'question' => $this->t('¿Qué soporte técnico incluye cada plan?'),
                'answer' => $this->t('Free: documentación. Starter: email. Professional: email y chat con respuesta en 24h. Enterprise: gestor dedicado, soporte telefónico y SLA garantizado.'),
            ],
            [
                'question' => $this->t('¿Cómo funciona la facturación?'),
                'answer' => $this->t('Procesamos los pagos de forma segura a través de Stripe. Recibirás facturas automáticas en PDF cada mes o año según tu ciclo.'),
            ],
            [
                'question' => $this->t('¿Puedo probar el plan Professional gratis?'),
                'answer' => $this->t('Sí. Todos los planes de pago ofrecen 14 días de prueba gratis. Puedes cancelar en cualquier momento antes de que termine la prueba sin ningún cargo.'),
            ],
        ];
    }

}
