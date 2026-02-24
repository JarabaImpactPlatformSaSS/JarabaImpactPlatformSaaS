<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de contenido FOC Metric Snapshot.
 *
 * PROPÓSITO:
 * Captura el estado de todas las métricas SaaS calculadas en un momento dado.
 * Permite análisis histórico, trending y detección de anomalías.
 *
 * ESTRUCTURA:
 * ═══════════════════════════════════════════════════════════════════════════
 * Cada snapshot captura métricas a nivel de:
 * - platform: Métricas globales del ecosistema
 * - vertical: Métricas por vertical de negocio
 * - tenant: Métricas por inquilino individual
 *
 * FRECUENCIA:
 * - Snapshots diarios automáticos via cron
 * - Snapshots manuales para auditorías
 *
 * MÉTRICAS CAPTURADAS:
 * - MRR, ARR: Ingresos recurrentes
 * - Churn Rate: Tasa de pérdida
 * - NRR, GRR: Retención neta y bruta
 * - CAC, LTV: Unit economics
 * - Gross Margin: Margen bruto
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * @ContentEntityType(
 *   id = "foc_metric_snapshot",
 *   label = @Translation("Snapshot de Métricas FOC"),
 *   label_collection = @Translation("Snapshots de Métricas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "foc_metric_snapshot",
 *   admin_permission = "create metric snapshots",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/foc/snapshots",
 *     "canonical" = "/admin/foc/snapshot/{foc_metric_snapshot}",
 *   },
 *   field_ui_base_route = "entity.foc_metric_snapshot.settings",
 * )
 */
class FocMetricSnapshot extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // ═══════════════════════════════════════════════════════════════════════
        // IDENTIFICACIÓN DEL SNAPSHOT
        // ═══════════════════════════════════════════════════════════════════════

        $fields['snapshot_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha del Snapshot'))
            ->setDescription(t('Fecha en que se capturaron las métricas.'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'datetime_default',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['scope_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Alcance'))
            ->setDescription(t('Nivel de agregación del snapshot.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'platform' => 'Plataforma',
                'vertical' => 'Vertical',
                'tenant' => 'Tenant',
            ])
            ->setDefaultValue('platform')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['scope_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID del Alcance'))
            ->setDescription(t('ID del vertical o tenant. NULL si es platform.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // MÉTRICAS DE INGRESOS
        // ═══════════════════════════════════════════════════════════════════════

        $fields['mrr'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('MRR'))
            ->setDescription(t('Monthly Recurring Revenue - Ingresos mensuales recurrentes.'))
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['arr'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('ARR'))
            ->setDescription(t('Annual Recurring Revenue - MRR × 12.'))
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['gross_margin'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Gross Margin'))
            ->setDescription(t('(Revenue - COGS) / Revenue × 100. Benchmark: 70-85%.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // MÉTRICAS DE RETENCIÓN
        // ═══════════════════════════════════════════════════════════════════════

        $fields['churn_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Churn Rate'))
            ->setDescription(t('Tasa de cancelación mensual. Benchmark: <5% anual.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['nrr'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('NRR'))
            ->setDescription(t('Net Revenue Retention. Benchmark: >100% (ideal 110-120%).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['grr'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('GRR'))
            ->setDescription(t('Gross Revenue Retention. Benchmark: 85-95%.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // UNIT ECONOMICS
        // ═══════════════════════════════════════════════════════════════════════

        $fields['cac'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('CAC'))
            ->setDescription(t('Customer Acquisition Cost - Coste de adquisición.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 9,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['ltv'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('LTV'))
            ->setDescription(t('Customer Lifetime Value - Valor de vida del cliente.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['ltv_cac_ratio'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Ratio LTV:CAC'))
            ->setDescription(t('LTV / CAC. Benchmark: ≥3:1 (ideal 5:1).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['cac_payback_months'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('CAC Payback'))
            ->setDescription(t('Meses para recuperar el CAC. Benchmark: <12 meses.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 1)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // CONTADORES
        // ═══════════════════════════════════════════════════════════════════════

        $fields['active_customers'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Clientes Activos'))
            ->setDescription(t('Número de clientes/tenants activos.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 13,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['new_customers'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Nuevos Clientes'))
            ->setDescription(t('Clientes adquiridos en el período.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 14,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['churned_customers'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Clientes Perdidos'))
            ->setDescription(t('Clientes que cancelaron en el período.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // METADATOS
        // ═══════════════════════════════════════════════════════════════════════

        $fields['metadata'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Metadatos'))
            ->setDescription(t('JSON con datos adicionales contextuales.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        return $fields;
    }

}
