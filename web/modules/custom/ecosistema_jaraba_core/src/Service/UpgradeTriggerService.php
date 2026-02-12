<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio central de triggers de upgrade para el modelo freemium.
 *
 * Se invoca cuando ocurre un evento que deberia incentivar el upgrade
 * de un tenant (limite alcanzado, feature bloqueada, primera venta,
 * competencia visible, tiempo en plataforma).
 *
 * Consulta los limites configurados en FreemiumVerticalLimit ConfigEntity
 * para devolver datos contextualizados por vertical y plan. Registra
 * cada disparo para analytics de conversion.
 *
 * DIRECTRICES:
 * - Strings traducibles con $this->t() (i18n)
 * - Integra con FreemiumVerticalLimit ConfigEntity (F2)
 * - Doc 183 §4: 5 tipos de trigger con tasas de conversion esperadas
 *
 * @see docs/implementacion/2026-02-12_F2_Freemium_Trial_Model_Doc183_Implementacion.md
 */
class UpgradeTriggerService
{

    use StringTranslationTrait;

    /**
     * Tipos de trigger soportados con sus tasas de conversion por defecto.
     */
    protected const TRIGGER_TYPES = [
        'limit_reached' => 0.35,
        'feature_blocked' => 0.28,
        'first_sale' => 0.42,
        'competition_visible' => 0.22,
        'time_on_platform' => 0.18,
    ];

    /**
     * Mapa de planes y su siguiente plan recomendado para upgrade.
     */
    protected const PLAN_UPGRADE_PATH = [
        'free' => 'starter',
        'starter' => 'profesional',
        'profesional' => 'business',
        'business' => 'enterprise',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Dispara un trigger de upgrade para un tenant.
     *
     * Evalua si el trigger debe mostrarse, registra el evento para analytics
     * y devuelve los datos necesarios para renderizar el modal de upgrade.
     *
     * @param string $type
     *   Tipo de trigger: limit_reached, feature_blocked, first_sale,
     *   competition_visible, time_on_platform.
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant para el que se dispara el trigger.
     * @param array $context
     *   Contexto adicional del trigger:
     *   - feature_key: (string) Recurso que dispara el trigger.
     *   - current_usage: (int) Uso actual del recurso.
     *   - feature_name: (string) Nombre legible de la feature bloqueada.
     *   - order_id: (string) ID de la primera orden (para first_sale).
     *   - competitors_count: (int) Numero de competidores (para competition_visible).
     *   - days_on_platform: (int) Dias en la plataforma (para time_on_platform).
     *
     * @return array
     *   Array con los datos del trigger:
     *   - should_show: (bool) Si el trigger debe mostrarse.
     *   - trigger_type: (string) Tipo de trigger.
     *   - title: (string) Titulo traducido.
     *   - message: (string) Mensaje contextualizado.
     *   - icon: (array) Datos del icono duotone.
     *   - current_plan: (array) Datos del plan actual.
     *   - recommended_plan: (array) Datos del plan recomendado.
     *   - cta_primary: (array) CTA principal (upgrade).
     *   - cta_secondary: (array) CTA secundario (dismiss).
     *   - expected_conversion: (float) Tasa de conversion esperada.
     */
    public function fire(string $type, TenantInterface $tenant, array $context = []): array
    {
        // Validar tipo de trigger.
        if (!isset(self::TRIGGER_TYPES[$type])) {
            $this->logger->warning('Tipo de trigger de upgrade desconocido: @type', [
                '@type' => $type,
            ]);
            return ['should_show' => FALSE];
        }

        // Obtener vertical y plan del tenant.
        $vertical = $tenant->getVertical();
        $plan = $tenant->getSubscriptionPlan();

        if (!$vertical || !$plan) {
            return ['should_show' => FALSE];
        }

        $verticalId = $vertical->id();
        $planId = $plan->id();

        // Enterprise no recibe triggers de upgrade.
        if ($planId === 'enterprise') {
            return ['should_show' => FALSE];
        }

        // Construir respuesta segun tipo de trigger.
        $result = $this->buildTriggerResponse($type, $verticalId, $planId, $tenant, $context);

        // Registrar el evento para analytics.
        if ($result['should_show']) {
            $this->recordTriggerEvent($type, $tenant->id(), $verticalId, $planId, $context);
        }

        return $result;
    }

    /**
     * Construye la respuesta del trigger segun el tipo.
     *
     * @param string $type
     *   Tipo de trigger.
     * @param string $verticalId
     *   ID de la vertical.
     * @param string $planId
     *   ID del plan actual.
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param array $context
     *   Contexto adicional.
     *
     * @return array
     *   Datos del trigger para renderizado.
     */
    protected function buildTriggerResponse(string $type, string $verticalId, string $planId, TenantInterface $tenant, array $context): array
    {
        $recommendedPlanId = self::PLAN_UPGRADE_PATH[$planId] ?? NULL;
        if (!$recommendedPlanId) {
            return ['should_show' => FALSE];
        }

        // Cargar plan recomendado.
        $recommendedPlan = $this->entityTypeManager->getStorage('saas_plan')->load($recommendedPlanId);
        $currentPlan = $tenant->getSubscriptionPlan();

        // Obtener limite freemium si aplica.
        $freemiumLimit = NULL;
        $featureKey = $context['feature_key'] ?? '';
        if ($featureKey && in_array($type, ['limit_reached', 'feature_blocked'])) {
            $freemiumLimit = $this->getVerticalLimit($verticalId, $planId, $featureKey);
        }

        // Obtener mensaje y titulo segun tipo.
        $titleAndMessage = $this->getTitleAndMessage($type, $verticalId, $planId, $freemiumLimit, $context);

        // Obtener icono segun tipo.
        $icon = $this->getIconForType($type);

        // Calcular tasa de conversion (override desde FreemiumVerticalLimit si existe).
        $expectedConversion = self::TRIGGER_TYPES[$type];
        if ($freemiumLimit && $freemiumLimit->getExpectedConversion() > 0) {
            $expectedConversion = $freemiumLimit->getExpectedConversion();
        }

        return [
            'should_show' => TRUE,
            'trigger_type' => $type,
            'title' => $titleAndMessage['title'],
            'message' => $titleAndMessage['message'],
            'icon' => $icon,
            'current_plan' => [
                'id' => $planId,
                'name' => $currentPlan ? $currentPlan->label() : $planId,
            ],
            'recommended_plan' => [
                'id' => $recommendedPlanId,
                'name' => $recommendedPlan ? $recommendedPlan->label() : $recommendedPlanId,
            ],
            'cta_primary' => [
                'text' => $this->t('Actualizar ahora'),
                'url' => '/tenant/upgrade?plan=' . $recommendedPlanId,
            ],
            'cta_secondary' => [
                'text' => $this->t('Recordarme despues'),
                'action' => 'dismiss',
            ],
            'expected_conversion' => $expectedConversion,
        ];
    }

    /**
     * Obtiene el limite freemium para una combinacion vertical+plan+feature.
     *
     * @param string $verticalId
     *   ID de la vertical.
     * @param string $planId
     *   ID del plan.
     * @param string $featureKey
     *   Clave del recurso.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\FreemiumVerticalLimitInterface|null
     *   El limite o NULL si no existe.
     */
    public function getVerticalLimit(string $verticalId, string $planId, string $featureKey)
    {
        $limitId = $verticalId . '_' . $planId . '_' . $featureKey;
        $limit = $this->entityTypeManager
            ->getStorage('freemium_vertical_limit')
            ->load($limitId);

        if ($limit && $limit->status()) {
            return $limit;
        }

        return NULL;
    }

    /**
     * Obtiene el valor numerico del limite para una combinacion dada.
     *
     * Metodo de conveniencia para que otros servicios (como UsageLimitsService
     * o PlanValidator) puedan consultar limites verticales sin cargar toda
     * la entidad.
     *
     * @param string $verticalId
     *   ID de la vertical.
     * @param string $planId
     *   ID del plan.
     * @param string $featureKey
     *   Clave del recurso.
     * @param int $fallback
     *   Valor por defecto si no hay limite configurado.
     *
     * @return int
     *   El valor del limite (-1 = ilimitado, 0 = no incluido).
     */
    public function getLimitValue(string $verticalId, string $planId, string $featureKey, int $fallback = -1): int
    {
        $limit = $this->getVerticalLimit($verticalId, $planId, $featureKey);
        return $limit ? $limit->getLimitValue() : $fallback;
    }

    /**
     * Genera titulo y mensaje contextualizados por tipo de trigger.
     *
     * @param string $type
     *   Tipo de trigger.
     * @param string $verticalId
     *   ID de la vertical.
     * @param string $planId
     *   ID del plan actual.
     * @param mixed $freemiumLimit
     *   Entidad FreemiumVerticalLimit o NULL.
     * @param array $context
     *   Contexto adicional.
     *
     * @return array
     *   Array con 'title' y 'message'.
     */
    protected function getTitleAndMessage(string $type, string $verticalId, string $planId, $freemiumLimit, array $context): array
    {
        $recommendedPlanId = self::PLAN_UPGRADE_PATH[$planId] ?? 'starter';

        switch ($type) {
            case 'limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 0);
                $featureKey = $context['feature_key'] ?? '';

                // Usar upgrade_message del limite si existe.
                if ($freemiumLimit && $freemiumLimit->getUpgradeMessage()) {
                    return [
                        'title' => $this->t('Has alcanzado tu limite'),
                        'message' => $freemiumLimit->getUpgradeMessage(),
                    ];
                }

                return [
                    'title' => $this->t('Has alcanzado tu limite'),
                    'message' => $this->t('Has alcanzado el limite de @limit @feature. Desbloquea mas con el plan @plan.', [
                        '@limit' => $limitValue,
                        '@feature' => $featureKey,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'feature_blocked':
                $featureName = $context['feature_name'] ?? $this->t('esta funcion');
                return [
                    'title' => $this->t('Funcion no disponible'),
                    'message' => $this->t('@feature puede hacer esto por ti. Desbloquea esta funcionalidad con el plan @plan.', [
                        '@feature' => $featureName,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'first_sale':
                return [
                    'title' => $this->t('¡Felicidades por tu primera venta!'),
                    'message' => $this->t('Has completado tu primera venta. Reduce tu comision y accede a mas herramientas con el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'competition_visible':
                $count = $context['competitors_count'] ?? 0;
                return [
                    'title' => $this->t('Otros negocios ya usan Pro'),
                    'message' => $this->t('@count negocios similares al tuyo ya utilizan funcionalidades avanzadas. No te quedes atras.', [
                        '@count' => $count,
                    ]),
                ];

            case 'time_on_platform':
                $days = $context['days_on_platform'] ?? 30;
                return [
                    'title' => $this->t('¿Listo para el siguiente nivel?'),
                    'message' => $this->t('Llevas @days dias en la plataforma. Descubre todo lo que puedes hacer con el plan @plan.', [
                        '@days' => $days,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            default:
                return [
                    'title' => $this->t('Mejora tu plan'),
                    'message' => $this->t('Descubre las ventajas de actualizar tu plan.'),
                ];
        }
    }

    /**
     * Devuelve la configuracion de icono para cada tipo de trigger.
     *
     * Usa el sistema de iconos del tema: jaraba_icon(category, name, options).
     *
     * @param string $type
     *   Tipo de trigger.
     *
     * @return array
     *   Array con category, name, variant y color.
     */
    protected function getIconForType(string $type): array
    {
        $icons = [
            'limit_reached' => [
                'category' => 'actions',
                'name' => 'rocket',
                'variant' => 'duotone',
                'color' => 'naranja-impulso',
            ],
            'feature_blocked' => [
                'category' => 'actions',
                'name' => 'lock',
                'variant' => 'duotone',
                'color' => 'azul-corporativo',
            ],
            'first_sale' => [
                'category' => 'actions',
                'name' => 'celebration',
                'variant' => 'duotone',
                'color' => 'verde-innovacion',
            ],
            'competition_visible' => [
                'category' => 'actions',
                'name' => 'trending-up',
                'variant' => 'duotone',
                'color' => 'naranja-impulso',
            ],
            'time_on_platform' => [
                'category' => 'actions',
                'name' => 'clock',
                'variant' => 'duotone',
                'color' => 'azul-corporativo',
            ],
        ];

        return $icons[$type] ?? $icons['limit_reached'];
    }

    /**
     * Registra un evento de trigger para analytics de conversion.
     *
     * @param string $type
     *   Tipo de trigger.
     * @param string $tenantId
     *   ID del tenant.
     * @param string $verticalId
     *   ID de la vertical.
     * @param string $planId
     *   ID del plan actual.
     * @param array $context
     *   Contexto adicional (se guarda como JSON).
     */
    protected function recordTriggerEvent(string $type, string $tenantId, string $verticalId, string $planId, array $context): void
    {
        try {
            $this->database->insert('upgrade_trigger_events')
                ->fields([
                    'trigger_type' => $type,
                    'tenant_id' => $tenantId,
                    'vertical' => $verticalId,
                    'plan' => $planId,
                    'feature_key' => $context['feature_key'] ?? '',
                    'context_data' => json_encode($context, JSON_UNESCAPED_UNICODE),
                    'converted' => 0,
                    'created' => \Drupal::time()->getRequestTime(),
                ])
                ->execute();
        }
        catch (\Exception $e) {
            $this->logger->error('Error al registrar trigger de upgrade: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Marca un evento de trigger como convertido.
     *
     * Se invoca cuando el tenant efectivamente realiza el upgrade
     * despues de haber visto un trigger.
     *
     * @param string $tenantId
     *   ID del tenant.
     * @param string $triggerType
     *   Tipo de trigger que se convirtio.
     *
     * @return int
     *   Numero de registros actualizados.
     */
    public function markConverted(string $tenantId, string $triggerType): int
    {
        try {
            return (int) $this->database->update('upgrade_trigger_events')
                ->fields([
                    'converted' => 1,
                    'converted_at' => \Drupal::time()->getRequestTime(),
                ])
                ->condition('tenant_id', $tenantId)
                ->condition('trigger_type', $triggerType)
                ->condition('converted', 0)
                ->orderBy('created', 'DESC')
                ->execute();
        }
        catch (\Exception $e) {
            $this->logger->error('Error al marcar conversion: @error', [
                '@error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtiene estadisticas de conversion por tipo de trigger.
     *
     * @param string $verticalId
     *   Filtro por vertical (vacio = todas).
     * @param int $daysBack
     *   Dias hacia atras para el calculo (default 30).
     *
     * @return array
     *   Array indexado por trigger_type con:
     *   - total: Disparos totales.
     *   - converted: Disparos convertidos.
     *   - rate: Tasa de conversion real.
     */
    public function getConversionStats(string $verticalId = '', int $daysBack = 30): array
    {
        $stats = [];
        $since = \Drupal::time()->getRequestTime() - ($daysBack * 86400);

        try {
            $query = $this->database->select('upgrade_trigger_events', 'ute')
                ->fields('ute', ['trigger_type'])
                ->condition('ute.created', $since, '>=');

            if ($verticalId) {
                $query->condition('ute.vertical', $verticalId);
            }

            $query->addExpression('COUNT(*)', 'total');
            $query->addExpression('SUM(ute.converted)', 'converted');
            $query->groupBy('ute.trigger_type');

            $results = $query->execute();
            foreach ($results as $row) {
                $total = (int) $row->total;
                $converted = (int) $row->converted;
                $stats[$row->trigger_type] = [
                    'total' => $total,
                    'converted' => $converted,
                    'rate' => $total > 0 ? round($converted / $total, 4) : 0,
                ];
            }
        }
        catch (\Exception $e) {
            $this->logger->error('Error al obtener stats de conversion: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

}
