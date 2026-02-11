<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Entity\FocAlert;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de alertas prescriptivas.
 *
 * PROPÓSITO:
 * Genera alertas financieras basadas en umbrales configurables y
 * proporciona playbooks de acción para cada tipo de alerta.
 *
 * MATRIZ DE ALERTAS:
 * ═══════════════════════════════════════════════════════════════════════════
 * | Tipo              | Condición            | Severidad | Playbook          |
 * |-------------------|----------------------|-----------|-------------------|
 * | churn_risk        | LTV:CAC < 3          | WARNING   | Churn Prevention  |
 * | mrr_drop          | MRR cae >10% MoM     | CRITICAL  | Revenue Recovery  |
 * | ltv_cac_warning   | LTV:CAC < 1          | CRITICAL  | Customer Success  |
 * | payback_exceeded  | Payback > 12 meses   | WARNING   | CAC Optimization  |
 * | margin_alert      | Gross Margin < 70%   | WARNING   | Cost Review       |
 * | expansion_opp     | LTV:CAC > 5          | INFO      | Upsell Campaign   |
 * ═══════════════════════════════════════════════════════════════════════════
 */
class AlertService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\jaraba_foc\Service\MetricsCalculatorService $metricsCalculator
     *   El servicio de cálculo de métricas.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   El factory de configuración.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del módulo.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected MetricsCalculatorService $metricsCalculator,
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Obtiene la configuración de umbrales.
     */
    protected function getConfig()
    {
        return $this->configFactory->get('jaraba_foc.settings');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // EVALUACIÓN DE ALERTAS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Evalúa todas las condiciones de alerta para la plataforma.
     *
     * Este método debe ejecutarse periódicamente via cron.
     *
     * @return array
     *   Array de alertas generadas.
     */
    public function evaluateAllAlerts(): array
    {
        $alerts = [];

        // Evaluar alertas a nivel de plataforma
        $alerts = array_merge($alerts, $this->evaluatePlatformAlerts());

        // Evaluar alertas por tenant
        $tenantsMetrics = $this->metricsCalculator->getTenantAnalytics();
        foreach ($tenantsMetrics as $tenantMetrics) {
            $tenantAlerts = $this->evaluateTenantAlerts($tenantMetrics);
            $alerts = array_merge($alerts, $tenantAlerts);
        }

        $this->logger->info('Evaluación de alertas completada: @count alertas generadas', [
            '@count' => count($alerts),
        ]);

        return $alerts;
    }

    /**
     * Evalúa alertas a nivel de plataforma.
     *
     * @return array
     *   Alertas de plataforma.
     */
    protected function evaluatePlatformAlerts(): array
    {
        $alerts = [];
        $config = $this->getConfig();

        // MRR Drop
        $mrrDropThreshold = $config->get('alert_mrr_drop_threshold') ?? 10;
        // TODO: Comparar MRR actual vs anterior para detectar caídas

        // Gross Margin
        $grossMargin = (float) $this->metricsCalculator->calculateGrossMargin();
        if ($grossMargin < 70) {
            $alerts[] = $this->createAlert([
                'title' => 'Margen Bruto por debajo del benchmark',
                'alert_type' => 'margin_alert',
                'severity' => $grossMargin < 60 ? FocAlert::SEVERITY_CRITICAL : FocAlert::SEVERITY_WARNING,
                'message' => "El margen bruto de la plataforma ({$grossMargin}%) está por debajo del benchmark de 70%.",
                'metric_value' => $grossMargin . '%',
                'threshold' => '70%',
                'playbook' => $this->getPlaybook('margin_review'),
            ]);
        }

        return $alerts;
    }

    /**
     * Evalúa alertas para un tenant específico.
     *
     * @param array $tenantMetrics
     *   Métricas del tenant.
     *
     * @return array
     *   Alertas del tenant.
     */
    protected function evaluateTenantAlerts(array $tenantMetrics): array
    {
        $alerts = [];
        $config = $this->getConfig();

        $tenantId = $tenantMetrics['id'] ?? NULL;
        $tenantName = $tenantMetrics['name'] ?? 'Tenant';
        $ltvCacRatio = (float) ($tenantMetrics['ltv_cac_ratio'] ?? 0);
        $paybackMonths = (float) ($tenantMetrics['payback_months'] ?? 0);

        $ltvCacMin = $config->get('alert_ltv_cac_min') ?? 3;

        // LTV:CAC Warning (< 3)
        if ($ltvCacRatio > 0 && $ltvCacRatio < $ltvCacMin) {
            $severity = $ltvCacRatio < 1 ? FocAlert::SEVERITY_CRITICAL : FocAlert::SEVERITY_WARNING;
            $alertType = $ltvCacRatio < 1 ? 'ltv_cac_warning' : 'churn_risk';

            $alerts[] = $this->createAlert([
                'title' => "Tenant '$tenantName' con ratio LTV:CAC bajo",
                'alert_type' => $alertType,
                'severity' => $severity,
                'message' => "El tenant '$tenantName' tiene un ratio LTV:CAC de {$ltvCacRatio}:1, por debajo del umbral de {$ltvCacMin}:1.",
                'related_tenant' => $tenantId,
                'metric_value' => $ltvCacRatio . ':1',
                'threshold' => $ltvCacMin . ':1',
                'playbook' => $this->getPlaybook($severity === FocAlert::SEVERITY_CRITICAL ? 'customer_success' : 'churn_prevention'),
            ]);
        }

        // Payback Exceeded (> 12 meses)
        if ($paybackMonths > 12) {
            $alerts[] = $this->createAlert([
                'title' => "Tenant '$tenantName' con payback excesivo",
                'alert_type' => 'payback_exceeded',
                'severity' => $paybackMonths > 18 ? FocAlert::SEVERITY_CRITICAL : FocAlert::SEVERITY_WARNING,
                'message' => "El tenant '$tenantName' tiene un CAC payback de {$paybackMonths} meses, superior al benchmark de 12 meses.",
                'related_tenant' => $tenantId,
                'metric_value' => $paybackMonths . ' meses',
                'threshold' => '12 meses',
                'playbook' => $this->getPlaybook('cac_optimization'),
            ]);
        }

        // Expansion Opportunity (LTV:CAC > 5 = VIP)
        if ($ltvCacRatio >= 5) {
            $alerts[] = $this->createAlert([
                'title' => "Oportunidad de expansión: '$tenantName'",
                'alert_type' => 'expansion_opportunity',
                'severity' => FocAlert::SEVERITY_INFO,
                'message' => "El tenant '$tenantName' es VIP con ratio LTV:CAC de {$ltvCacRatio}:1. Candidato para upsell.",
                'related_tenant' => $tenantId,
                'metric_value' => $ltvCacRatio . ':1',
                'threshold' => '5:1',
                'playbook' => $this->getPlaybook('upsell_campaign'),
            ]);
        }

        return $alerts;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PLAYBOOKS PRESCRIPTIVOS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Obtiene el playbook para un tipo de alerta.
     *
     * @param string $playbookType
     *   Tipo de playbook: churn_prevention, customer_success, etc.
     *
     * @return string
     *   Texto del playbook con acciones recomendadas.
     */
    protected function getPlaybook(string $playbookType): string
    {
        $playbooks = [
            'churn_prevention' => <<<PLAYBOOK
## Playbook: Prevención de Churn

### Acciones Inmediatas (24-48h)
1. **Customer Success Call**: Programar llamada de seguimiento con el tenant
2. **Health Check**: Revisar uso de la plataforma en últimos 30 días
3. **Feedback Survey**: Enviar encuesta de satisfacción breve

### Acciones a Corto Plazo (1-2 semanas)
1. **Value Review**: Demostrar ROI obtenido con la plataforma
2. **Feature Adoption**: Identificar features no utilizadas que podrían aportar valor
3. **Training Session**: Ofrecer sesión de capacitación gratuita

### Automatización (ActiveCampaign)
- Trigger: Automation "Churn Prevention"
- Email 1: "Queremos conocer tu experiencia"
- Email 2: "Nuevas funcionalidades que podrían interesarte"
PLAYBOOK,

            'customer_success' => <<<PLAYBOOK
## Playbook: Intervención Customer Success (CRÍTICO)

### Acciones Inmediatas (24h)
1. **Escalación**: Notificar a Manager de Customer Success
2. **Direct Contact**: Llamada personal del Account Manager
3. **Issue Discovery**: Identificar problemas bloqueantes

### Análisis de Causa Raíz
1. ¿Ha habido tickets de soporte sin resolver?
2. ¿Ha disminuido el uso de la plataforma?
3. ¿Hay quejas recientes del equipo del tenant?

### Ofertas de Retención
- Descuento temporal (máximo 20% por 3 meses)
- Servicios adicionales sin coste
- Extensión de contrato con condiciones mejoradas
PLAYBOOK,

            'cac_optimization' => <<<PLAYBOOK
## Playbook: Optimización de CAC

### Análisis del Coste de Adquisición
1. Revisar canales de adquisición de este tenant
2. Comparar CAC vs tenants similares
3. Identificar ineficiencias en el funnel

### Acciones de Optimización
1. **Si CAC alto por canal**: Reasignar presupuesto de marketing
2. **Si conversion bajo**: Optimizar landing pages y demos
3. **Si ciclo largo**: Automatizar nurturing con ActiveCampaign

### Métricas a Monitorizar
- Coste por lead por canal
- Tasa de conversión demo→cliente
- Tiempo medio de cierre
PLAYBOOK,

            'margin_review' => <<<PLAYBOOK
## Playbook: Revisión de Margen

### Análisis de COGS
1. Revisar costes de infraestructura (IONOS)
2. Analizar consumo de APIs externas (OpenAI, Qdrant)
3. Evaluar costes de procesamiento de pagos (Stripe)

### Acciones de Optimización
1. **Renegociar contratos**: Infraestructura, SaaS
2. **Optimizar uso de APIs**: Caching, batching
3. **Revisar pricing**: ¿Márgenes adecuados por plan?

### Cost Allocation
- Ejecutar análisis de Cost Allocation por tenant
- Identificar "noisy neighbors" que consumen más recursos
PLAYBOOK,

            'upsell_campaign' => <<<PLAYBOOK
## Playbook: Campaña de Upsell (VIP)

### Identificación de Oportunidades
1. ¿El tenant está en el plan más alto disponible?
2. ¿Está usando features premium?
3. ¿Tiene espacio para más productores/cuotas?

### Acciones Comerciales
1. **Account Review**: Presentar informe de uso y valor
2. **Upgrade Proposal**: Proponer plan superior con beneficios claros
3. **Cross-sell**: Ofertar servicios complementarios

### Automatización (ActiveCampaign)
- Trigger: Automation "VIP Upsell"
- Email: "Has desbloqueado beneficios exclusivos VIP"
PLAYBOOK,
        ];

        return $playbooks[$playbookType] ?? 'Consultar con el equipo de Customer Success.';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GESTIÓN DE ALERTAS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Crea una alerta en el sistema.
     *
     * @param array $data
     *   Datos de la alerta.
     *
     * @return int
     *   ID de la alerta creada.
     */
    public function createAlert(array $data): int
    {
        // Verificar si ya existe alerta abierta del mismo tipo para el mismo tenant
        if ($this->hasDuplicateAlert($data)) {
            $this->logger->debug('Alerta duplicada ignorada: @type para tenant @tenant', [
                '@type' => $data['alert_type'],
                '@tenant' => $data['related_tenant'] ?? 'platform',
            ]);
            return 0;
        }

        $storage = $this->entityTypeManager->getStorage('foc_alert');

        $alert = $storage->create([
            'title' => $data['title'],
            'alert_type' => $data['alert_type'],
            'severity' => $data['severity'] ?? FocAlert::SEVERITY_WARNING,
            'status' => FocAlert::STATUS_OPEN,
            'message' => $data['message'] ?? '',
            'related_tenant' => $data['related_tenant'] ?? NULL,
            'metric_value' => $data['metric_value'] ?? '',
            'threshold' => $data['threshold'] ?? '',
            'playbook' => $data['playbook'] ?? '',
            'playbook_executed' => FALSE,
        ]);

        $alert->save();

        $this->logger->info('Alerta creada: @id - @title', [
            '@id' => $alert->id(),
            '@title' => $data['title'],
        ]);

        return (int) $alert->id();
    }

    /**
     * Verifica si existe una alerta duplicada abierta.
     *
     * @param array $data
     *   Datos de la nueva alerta.
     *
     * @return bool
     *   TRUE si ya existe una alerta similar abierta.
     */
    protected function hasDuplicateAlert(array $data): bool
    {
        $storage = $this->entityTypeManager->getStorage('foc_alert');

        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('alert_type', $data['alert_type'])
            ->condition('status', [FocAlert::STATUS_OPEN, FocAlert::STATUS_ACKNOWLEDGED], 'IN');

        if (!empty($data['related_tenant'])) {
            $query->condition('related_tenant', $data['related_tenant']);
        }

        $existing = $query->execute();

        return !empty($existing);
    }

    /**
     * Obtiene alertas abiertas.
     *
     * @param string|null $severity
     *   Filtrar por severidad (opcional).
     *
     * @return array
     *   Array de entidades FocAlert.
     */
    public function getOpenAlerts(?string $severity = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('foc_alert');

        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', FocAlert::STATUS_OPEN)
            ->sort('created', 'DESC');

        if ($severity) {
            $query->condition('severity', $severity);
        }

        $ids = $query->execute();

        return $ids ? $storage->loadMultiple($ids) : [];
    }

    /**
     * Resuelve una alerta.
     *
     * @param int $alertId
     *   ID de la alerta.
     *
     * @return bool
     *   TRUE si se resolvió correctamente.
     */
    public function resolveAlert(int $alertId): bool
    {
        $storage = $this->entityTypeManager->getStorage('foc_alert');
        /** @var \Drupal\jaraba_foc\Entity\FocAlert|null $alert */
        $alert = $storage->load($alertId);

        if (!$alert) {
            return FALSE;
        }

        $alert->set('status', FocAlert::STATUS_RESOLVED);
        $alert->set('resolved_at', \Drupal::time()->getRequestTime());
        $alert->save();

        $this->logger->info('Alerta @id resuelta', ['@id' => $alertId]);

        return TRUE;
    }

}
