<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador para la API pública de precios.
 *
 * Proporciona endpoints que pueden ser consumidos por:
 * - Páginas de pricing públicas
 * - Widgets embebidos
 * - Comparadores externos
 *
 * Todos los endpoints son públicos y con cache agresivo.
 *
 * @example
 * GET /api/v1/pricing/agroconecta
 * Retorna los planes disponibles con precios para AgroConecta.
 */
class PricingController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static();
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
                'cta_url' => '/registro/' . $vertical->getMachineName() . '?plan=' . $plan->id(),
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

}
