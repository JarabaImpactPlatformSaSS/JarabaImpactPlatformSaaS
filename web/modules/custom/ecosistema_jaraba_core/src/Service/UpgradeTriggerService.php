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
        // Plan Elevación Empleabilidad v1 — Fase 5.
        'engagement_high' => 0.40,
        'first_milestone' => 0.42,
        'external_validation' => 0.45,
        'status_change' => 0.50,
        // Plan Elevación Emprendimiento v2 — Fase 7 (G7).
        'canvas_completed' => 0.38,
        'first_hypothesis_validated' => 0.42,
        'mentor_matched' => 0.35,
        'experiment_success' => 0.40,
        'funding_eligible' => 0.45,
        // Plan Elevación JarabaLex v1 — Fase 4.
        'search_limit_reached' => 0.40,
        'alert_limit_reached' => 0.30,
        'citation_blocked' => 0.35,
        'digest_blocked' => 0.25,
        'api_blocked' => 0.15,
        // AgroConecta v1 — Fase 0+1.
        'agro_products_limit_reached' => 0.35,
        'agro_orders_per_month_limit_reached' => 0.30,
        'agro_copilot_uses_per_month_limit_reached' => 0.28,
        'agro_photos_per_product_limit_reached' => 0.20,
        'agro_traceability_qr_limit_reached' => 0.30,
        'agro_partner_hub_limit_reached' => 0.22,
        'agro_analytics_advanced_limit_reached' => 0.18,
        'agro_demand_forecaster_limit_reached' => 0.15,
        // ComercioConecta v1 — Fase 2.
        'comercio_product_limit_reached' => 0.35,
        'comercio_flash_offer_limit_reached' => 0.30,
        'comercio_qr_limit_reached' => 0.28,
        'comercio_copilot_limit_reached' => 0.28,
        'comercio_analytics_gate' => 0.18,
        'comercio_pos_gate' => 0.15,
        'comercio_seo_gate' => 0.20,
        // ServiciosConecta v1 — Fase 1.
        'servicios_services_limit_reached' => 0.35,
        'servicios_bookings_per_month_limit_reached' => 0.30,
        'servicios_calendar_sync_limit_reached' => 0.28,
        'servicios_buzon_confianza_limit_reached' => 0.25,
        'servicios_firma_digital_limit_reached' => 0.22,
        'servicios_ai_triage_limit_reached' => 0.28,
        'servicios_video_conferencing_limit_reached' => 0.20,
        'servicios_analytics_dashboard_limit_reached' => 0.18,
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
        $triggerTypesWithLimits = [
            'limit_reached', 'feature_blocked',
            'search_limit_reached', 'alert_limit_reached',
            'citation_blocked', 'digest_blocked', 'api_blocked',
            'comercio_product_limit_reached', 'comercio_flash_offer_limit_reached',
            'comercio_qr_limit_reached', 'comercio_copilot_limit_reached',
            'comercio_analytics_gate', 'comercio_pos_gate', 'comercio_seo_gate',
            // ServiciosConecta v1 — Fase 1.
            'servicios_services_limit_reached', 'servicios_bookings_per_month_limit_reached',
            'servicios_calendar_sync_limit_reached', 'servicios_buzon_confianza_limit_reached',
            'servicios_firma_digital_limit_reached', 'servicios_ai_triage_limit_reached',
            'servicios_video_conferencing_limit_reached', 'servicios_analytics_dashboard_limit_reached',
        ];
        if ($featureKey && in_array($type, $triggerTypesWithLimits)) {
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

            // Plan Elevación JarabaLex v1 — Fase 5.
            case 'search_limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 0);
                return [
                    'title' => $this->t('Has alcanzado tu limite de busquedas legales'),
                    'message' => $this->t('Has realizado @limit busquedas juridicas este mes. Con el plan @plan tendras busquedas ilimitadas en CENDOJ, BOE, DGT y fuentes europeas.', [
                        '@limit' => $limitValue,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'alert_limit_reached':
                return [
                    'title' => $this->t('Limite de alertas juridicas alcanzado'),
                    'message' => $this->t('Has alcanzado el maximo de alertas activas. Con el plan @plan podras configurar alertas ilimitadas y recibir notificaciones en tiempo real.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'citation_blocked':
                return [
                    'title' => $this->t('Insercion de citas no disponible'),
                    'message' => $this->t('La insercion automatica de citas legales en expedientes esta disponible desde el plan @plan. Incluye 4 formatos: formal, resumida, bibliografica y nota al pie.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'digest_blocked':
                return [
                    'title' => $this->t('Digest semanal no disponible'),
                    'message' => $this->t('El digest semanal personalizado con las resoluciones mas relevantes para tu practica esta disponible desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'api_blocked':
                return [
                    'title' => $this->t('API REST no disponible'),
                    'message' => $this->t('El acceso a la API REST de inteligencia legal para integraciones esta disponible en el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            // AgroConecta v1 — Fase 0+1.
            case 'agro_products_limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 5);
                return [
                    'title' => $this->t('Has alcanzado el límite de productos'),
                    'message' => $this->t('Tu plan actual te permite publicar hasta @limit productos. Actualiza a Starter para publicar hasta 25 productos y aumentar tus ventas.', [
                        '@limit' => $limitValue,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_orders_per_month_limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 10);
                return [
                    'title' => $this->t('Has alcanzado el límite de pedidos mensuales'),
                    'message' => $this->t('¡Tu negocio está creciendo! Has recibido @limit pedidos este mes. Actualiza tu plan para procesar pedidos sin límites y no perder ninguna venta.', [
                        '@limit' => $limitValue,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_copilot_uses_per_month_limit_reached':
                return [
                    'title' => $this->t('Has agotado tus consultas al Copiloto IA'),
                    'message' => $this->t('Has utilizado todas tus consultas de inteligencia artificial este mes. El plan @plan te ofrece mucha más capacidad para optimizar tu producción y ventas.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_photos_per_product_limit_reached':
                return [
                    'title' => $this->t('Límite de fotos por producto alcanzado'),
                    'message' => $this->t('Las imágenes venden. El plan @plan te permite subir más fotos por producto para mostrar la calidad de tu género desde todos los ángulos.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_traceability_qr_limit_reached':
                return [
                    'title' => $this->t('Trazabilidad QR no disponible'),
                    'message' => $this->t('La generación de códigos QR de trazabilidad para el consumidor final está disponible en el plan @plan. Genera confianza mostrando el origen de tus productos.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_partner_hub_limit_reached':
                return [
                    'title' => $this->t('Acceso al Partner Hub B2B restringido'),
                    'message' => $this->t('Conecta con distribuidores y restaurantes a través del Partner Hub. Esta funcionalidad exclusiva está disponible en el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_analytics_advanced_limit_reached':
                return [
                    'title' => $this->t('Analítica avanzada no disponible'),
                    'message' => $this->t('Toma decisiones basadas en datos. Los informes detallados de tendencias de mercado y comportamiento del consumidor están disponibles en el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'agro_demand_forecaster_limit_reached':
                return [
                    'title' => $this->t('Predicción de demanda no disponible'),
                    'message' => $this->t('Adelántate al mercado. La herramienta de predicción de demanda basada en IA para planificar tu producción está disponible en el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            // ComercioConecta v1 — Fase 2.
            case 'comercio_product_limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 10);
                return [
                    'title' => $this->t('Has alcanzado tu limite de productos'),
                    'message' => $this->t('Has publicado @limit productos. Con el plan @plan podras publicar productos ilimitados y llegar a mas clientes.', [
                        '@limit' => $limitValue,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'comercio_flash_offer_limit_reached':
                return [
                    'title' => $this->t('Limite de ofertas flash alcanzado'),
                    'message' => $this->t('Has alcanzado el maximo de ofertas flash activas. Con el plan @plan podras crear ofertas ilimitadas para atraer mas clientes.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'comercio_qr_limit_reached':
                return [
                    'title' => $this->t('Codigos QR no disponibles'),
                    'message' => $this->t('Los codigos QR dinamicos para tu escaparate estan disponibles desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'comercio_copilot_limit_reached':
                return [
                    'title' => $this->t('Has agotado tus consultas IA del mes'),
                    'message' => $this->t('Has utilizado todas tus consultas de IA este mes. Con el plan @plan tendras mas consultas para optimizar tu negocio.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'comercio_analytics_gate':
                return [
                    'title' => $this->t('Analytics avanzados no disponibles'),
                    'message' => $this->t('Los analytics avanzados de ventas y clientes estan disponibles desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'comercio_pos_gate':
                return [
                    'title' => $this->t('Integracion TPV no disponible'),
                    'message' => $this->t('La integracion con tu Terminal Punto de Venta esta disponible desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'comercio_seo_gate':
                return [
                    'title' => $this->t('Auditoria SEO no disponible'),
                    'message' => $this->t('La auditoria SEO local automatizada esta disponible desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            // ServiciosConecta v1 — Fase 1.
            case 'servicios_services_limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 5);
                return [
                    'title' => $this->t('Has alcanzado tu limite de servicios publicados'),
                    'message' => $this->t('Has publicado @limit servicios. Con el plan @plan podras publicar servicios ilimitados y llegar a mas clientes.', [
                        '@limit' => $limitValue,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_bookings_per_month_limit_reached':
                $limitValue = $freemiumLimit ? $freemiumLimit->getLimitValue() : ($context['limit_value'] ?? 20);
                return [
                    'title' => $this->t('Has alcanzado tu limite de reservas mensuales'),
                    'message' => $this->t('Has recibido @limit reservas este mes. Con el plan @plan tendras reservas ilimitadas para hacer crecer tu consulta.', [
                        '@limit' => $limitValue,
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_calendar_sync_limit_reached':
                return [
                    'title' => $this->t('Sincronizacion de calendario no disponible'),
                    'message' => $this->t('La sincronizacion con Google Calendar y Outlook esta disponible desde el plan @plan. Evita dobles reservas y gestiona tu agenda profesional.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_buzon_confianza_limit_reached':
                return [
                    'title' => $this->t('Buzon de Confianza no disponible'),
                    'message' => $this->t('El Buzon de Confianza para comunicacion segura con tus clientes esta disponible desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_firma_digital_limit_reached':
                return [
                    'title' => $this->t('Firma digital no disponible'),
                    'message' => $this->t('La firma digital de documentos y contratos esta disponible desde el plan @plan. Cierra acuerdos de forma segura y legal.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_ai_triage_limit_reached':
                return [
                    'title' => $this->t('Triaje IA no disponible'),
                    'message' => $this->t('El triaje inteligente con IA para clasificar y priorizar consultas esta disponible desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_video_conferencing_limit_reached':
                return [
                    'title' => $this->t('Videoconsulta no disponible'),
                    'message' => $this->t('Las videoconsultas integradas para atender a tus clientes en remoto estan disponibles desde el plan @plan.', [
                        '@plan' => $recommendedPlanId,
                    ]),
                ];

            case 'servicios_analytics_dashboard_limit_reached':
                return [
                    'title' => $this->t('Dashboard de analytics no disponible'),
                    'message' => $this->t('El dashboard de analytics avanzados con metricas de rendimiento de tu consulta esta disponible desde el plan @plan.', [
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

        // Plan Elevación JarabaLex v1 — Fase 5.
        $icons['search_limit_reached'] = [
            'category' => 'actions',
            'name' => 'search',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['alert_limit_reached'] = [
            'category' => 'actions',
            'name' => 'bell',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['citation_blocked'] = [
            'category' => 'actions',
            'name' => 'quote',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['digest_blocked'] = [
            'category' => 'actions',
            'name' => 'mail',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['api_blocked'] = [
            'category' => 'actions',
            'name' => 'code',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];

        $icons['api_blocked'] = [
            'category' => 'actions',
            'name' => 'code',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];

        // AgroConecta v1 — Fase 0+1.
        $icons['agro_products_limit_reached'] = [
            'category' => 'commerce',
            'name' => 'shopping-bag',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['agro_orders_per_month_limit_reached'] = [
            'category' => 'commerce',
            'name' => 'shopping-cart',
            'variant' => 'duotone',
            'color' => 'verde-innovacion',
        ];
        $icons['agro_copilot_uses_per_month_limit_reached'] = [
            'category' => 'actions',
            'name' => 'robot',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['agro_photos_per_product_limit_reached'] = [
            'category' => 'actions',
            'name' => 'image',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['agro_traceability_qr_limit_reached'] = [
            'category' => 'actions',
            'name' => 'qr-code',
            'variant' => 'duotone',
            'color' => 'verde-innovacion',
        ];
        $icons['agro_partner_hub_limit_reached'] = [
            'category' => 'actions',
            'name' => 'network',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['agro_analytics_advanced_limit_reached'] = [
            'category' => 'analytics',
            'name' => 'chart-bar',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['agro_demand_forecaster_limit_reached'] = [
            'category' => 'actions',
            'name' => 'trending-up',
            'variant' => 'duotone',
            'color' => 'verde-innovacion',
        ];

        // ComercioConecta v1 — Fase 2.
        $icons['comercio_product_limit_reached'] = [
            'category' => 'commerce',
            'name' => 'shopping-bag',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['comercio_flash_offer_limit_reached'] = [
            'category' => 'commerce',
            'name' => 'flash',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['comercio_qr_limit_reached'] = [
            'category' => 'actions',
            'name' => 'qr-code',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['comercio_copilot_limit_reached'] = [
            'category' => 'actions',
            'name' => 'robot',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['comercio_analytics_gate'] = [
            'category' => 'analytics',
            'name' => 'chart-bar',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['comercio_pos_gate'] = [
            'category' => 'commerce',
            'name' => 'terminal',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['comercio_seo_gate'] = [
            'category' => 'actions',
            'name' => 'search',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];

        // ServiciosConecta v1 — Fase 1.
        $icons['servicios_services_limit_reached'] = [
            'category' => 'services',
            'name' => 'briefcase',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['servicios_bookings_per_month_limit_reached'] = [
            'category' => 'services',
            'name' => 'calendar-check',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['servicios_calendar_sync_limit_reached'] = [
            'category' => 'actions',
            'name' => 'calendar-sync',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['servicios_buzon_confianza_limit_reached'] = [
            'category' => 'actions',
            'name' => 'shield-check',
            'variant' => 'duotone',
            'color' => 'verde-innovacion',
        ];
        $icons['servicios_firma_digital_limit_reached'] = [
            'category' => 'actions',
            'name' => 'pen-tool',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['servicios_ai_triage_limit_reached'] = [
            'category' => 'actions',
            'name' => 'robot',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
        ];
        $icons['servicios_video_conferencing_limit_reached'] = [
            'category' => 'actions',
            'name' => 'video',
            'variant' => 'duotone',
            'color' => 'naranja-impulso',
        ];
        $icons['servicios_analytics_dashboard_limit_reached'] = [
            'category' => 'analytics',
            'name' => 'chart-bar',
            'variant' => 'duotone',
            'color' => 'azul-corporativo',
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
     * Obtiene contexto de upgrade para inyeccion en el Copiloto IA.
     *
     * Devuelve informacion sobre el plan actual, features cerca del limite
     * (>80% de uso) y el plan recomendado con beneficios especificos.
     * Se invoca desde CopilotOrchestratorService para enriquecer el
     * system prompt con nudges de upgrade contextuales.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant actual.
     * @param string $copilotMode
     *   Modo activo del copiloto (para beneficios contextuales).
     *
     * @return array
     *   Array con:
     *   - has_upgrade_context: (bool) Si hay contexto relevante.
     *   - current_plan: (string) Nombre del plan actual.
     *   - recommended_plan: (string) Nombre del plan recomendado.
     *   - features_near_limit: (array) Features con uso >80%.
     *   - upgrade_benefit: (string) Beneficio contextualizado al modo.
     *   - prompt_snippet: (string) Texto listo para inyectar en system prompt.
     */
    public function getUpgradeContext(TenantInterface $tenant, string $copilotMode = ''): array
    {
        $result = ['has_upgrade_context' => FALSE];

        $vertical = $tenant->getVertical();
        $plan = $tenant->getSubscriptionPlan();

        if (!$vertical || !$plan) {
            return $result;
        }

        $verticalId = $vertical->id();
        $planId = $plan->id();

        // Enterprise no necesita upgrade context.
        if ($planId === 'enterprise') {
            return $result;
        }

        $recommendedPlanId = self::PLAN_UPGRADE_PATH[$planId] ?? NULL;
        if (!$recommendedPlanId) {
            return $result;
        }

        // Buscar features cerca del limite (>80% uso).
        $featuresNearLimit = $this->findFeaturesNearLimit($verticalId, $planId);

        if (empty($featuresNearLimit)) {
            return $result;
        }

        // Cargar nombres de planes.
        $currentPlanLabel = $plan->label() ?: $planId;
        $recommendedPlan = $this->entityTypeManager->getStorage('saas_plan')->load($recommendedPlanId);
        $recommendedPlanLabel = $recommendedPlan ? $recommendedPlan->label() : $recommendedPlanId;

        // Beneficio contextual por modo de copiloto.
        $upgradeBenefit = $this->getUpgradeBenefitForMode($copilotMode, $recommendedPlanLabel);

        // Construir snippet para system prompt.
        $featureNames = array_map(fn($f) => $f['label'], $featuresNearLimit);
        $promptSnippet = sprintf(
            "CONTEXTO DE PLAN: El usuario esta en el plan %s. Estas funcionalidades estan cerca del limite: %s. "
            . "Si es relevante en la conversacion, menciona de forma natural que el plan %s ofrece mas capacidad. "
            . "%s No seas insistente; solo menciona el upgrade si encaja con lo que el usuario esta pidiendo.",
            $currentPlanLabel,
            implode(', ', $featureNames),
            $recommendedPlanLabel,
            $upgradeBenefit
        );

        return [
            'has_upgrade_context' => TRUE,
            'current_plan' => $currentPlanLabel,
            'recommended_plan' => $recommendedPlanLabel,
            'features_near_limit' => $featuresNearLimit,
            'upgrade_benefit' => $upgradeBenefit,
            'prompt_snippet' => $promptSnippet,
        ];
    }

    /**
     * Busca features con uso superior al 80% del limite.
     *
     * @param string $verticalId
     *   ID de la vertical.
     * @param string $planId
     *   ID del plan.
     *
     * @return array
     *   Array de features cerca del limite con 'feature_key', 'label',
     *   'limit', 'usage', 'percentage'.
     */
    protected function findFeaturesNearLimit(string $verticalId, string $planId): array
    {
        $nearLimit = [];

        try {
            $storage = $this->entityTypeManager->getStorage('freemium_vertical_limit');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('vertical', $verticalId)
                ->condition('plan', $planId)
                ->condition('status', TRUE)
                ->execute();

            if (empty($ids)) {
                return [];
            }

            $limits = $storage->loadMultiple($ids);

            foreach ($limits as $limit) {
                $limitValue = $limit->getLimitValue();
                // Skip unlimited features.
                if ($limitValue <= 0) {
                    continue;
                }

                $featureKey = $limit->get('feature_key');
                $label = $limit->label() ?: $featureKey;

                // Obtener uso actual via state (simplificado).
                $usageKey = "freemium_usage_{$verticalId}_{$featureKey}";
                $currentUsage = (int) \Drupal::state()->get($usageKey, 0);

                $percentage = ($currentUsage / $limitValue) * 100;

                if ($percentage >= 80) {
                    $nearLimit[] = [
                        'feature_key' => $featureKey,
                        'label' => (string) $label,
                        'limit' => $limitValue,
                        'usage' => $currentUsage,
                        'percentage' => round($percentage, 1),
                    ];
                }
            }
        }
        catch (\Exception $e) {
            $this->logger->warning('Error buscando features near limit: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return $nearLimit;
    }

    /**
     * Devuelve un beneficio de upgrade contextualizado al modo del copiloto.
     *
     * @param string $mode
     *   Modo activo del copiloto.
     * @param string $planName
     *   Nombre del plan recomendado.
     *
     * @return string
     *   Frase de beneficio contextual.
     */
    protected function getUpgradeBenefitForMode(string $mode, string $planName): string
    {
        $benefits = [
            'coach' => 'Con %s tendras sesiones ilimitadas con el Coach Emocional para acompanarte en todo momento.',
            'consultor' => 'El plan %s incluye consultas ilimitadas con el Consultor Tactico para guiarte paso a paso.',
            'sparring' => 'Con %s podras practicar tu pitch ilimitadamente con el Sparring Partner.',
            'cfo' => 'El plan %s desbloquea analisis financieros ilimitados con el CFO Sintetico.',
            'fiscal' => 'Con %s tendras acceso ilimitado al experto tributario para resolver todas tus dudas fiscales.',
            'laboral' => 'El plan %s incluye consultas ilimitadas sobre Seguridad Social y obligaciones laborales.',
            'devil' => 'Con %s podras usar el Abogado del Diablo sin restricciones para fortalecer tu propuesta.',
            'vpc_designer' => 'Con %s desbloqueas VPC Designer avanzado con Fit Score detallado.',
            'customer_discovery' => 'Con %s accedes a guiones de entrevista avanzados y analisis Mom Test.',
            'pattern_expert' => 'Con %s obtienes deteccion de patrones BMG y senales de pivot.',
            'pivot_advisor' => 'Con %s recibes asesoramiento personalizado de pivot con data historica.',
            // Plan Elevación JarabaLex v1 — Fase 5.
            'legal_search' => 'Con %s tendras busquedas juridicas ilimitadas en 8 fuentes nacionales y europeas.',
            'legal_alerts' => 'El plan %s desbloquea alertas juridicas ilimitadas con notificaciones en tiempo real.',
            'legal_citations' => 'Con %s podras insertar citas legales automaticas en todos tus expedientes.',
            'legal_digest' => 'El plan %s incluye un digest semanal personalizado con las resoluciones mas relevantes.',
            'legal_copilot' => 'Con %s accedes al asistente juridico IA sin restricciones para consultas avanzadas.',
            // ServiciosConecta v1 — Fase 1.
            'servicios_bookings' => 'Con %s tendras reservas ilimitadas para hacer crecer tu consulta profesional.',
            'servicios_calendar' => 'El plan %s incluye sincronizacion bidireccional con Google Calendar y Outlook.',
            'servicios_buzon' => 'Con %s desbloqueas el Buzon de Confianza para comunicacion segura con clientes.',
            'servicios_firma' => 'El plan %s incluye firma digital de documentos y contratos.',
            'servicios_triage' => 'Con %s accedes al triaje IA para clasificar y priorizar consultas automaticamente.',
            'servicios_video' => 'El plan %s desbloquea videoconsultas integradas para atencion remota.',
            'servicios_analytics' => 'Con %s accedes al dashboard de analytics avanzados de tu consulta.',
        ];

        $template = $benefits[$mode] ?? 'El plan %s desbloquea funcionalidades avanzadas.';
        return sprintf($template, $planName);
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
