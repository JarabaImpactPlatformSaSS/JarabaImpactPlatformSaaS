<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de contenido FOC Alert.
 *
 * PROPÓSITO:
 * Almacena alertas prescriptivas generadas por el sistema FOC.
 * Las alertas incluyen información sobre qué hacer (playbook)
 * además de notificar el problema.
 *
 * TIPOS DE ALERTAS:
 * ═══════════════════════════════════════════════════════════════════════════
 * - churn_risk: Tenant en riesgo de cancelación (LTV:CAC < 3)
 * - mrr_drop: Caída de MRR superior al umbral
 * - payment_failed: Pago fallido reiteradamente
 * - margin_alert: Margen bruto por debajo del benchmark
 * - expansion_opportunity: Oportunidad de upsell detectada
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * @ContentEntityType(
 *   id = "foc_alert",
 *   label = @Translation("Alerta FOC"),
 *   label_collection = @Translation("Alertas FOC"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "foc_alert",
 *   admin_permission = "administer foc",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/foc/alerts",
 *     "canonical" = "/admin/foc/alert/{foc_alert}",
 *   },
 * )
 */
class FocAlert extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Severidades de alerta.
     */
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Estados de alerta.
     */
    public const STATUS_OPEN = 'open';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // ═══════════════════════════════════════════════════════════════════════
        // IDENTIFICACIÓN DE LA ALERTA
        // ═══════════════════════════════════════════════════════════════════════

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título descriptivo de la alerta.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['alert_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Alerta'))
            ->setDescription(t('Clasificación del tipo de alerta.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'churn_risk' => 'Riesgo de Churn',
                'mrr_drop' => 'Caída de MRR',
                'payment_failed' => 'Pago Fallido',
                'margin_alert' => 'Alerta de Margen',
                'expansion_opportunity' => 'Oportunidad de Expansión',
                'ltv_cac_warning' => 'LTV:CAC Bajo',
                'payback_exceeded' => 'Payback Excedido',
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['severity'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Severidad'))
            ->setDescription(t('Nivel de urgencia de la alerta.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::SEVERITY_INFO => 'Informativa',
                self::SEVERITY_WARNING => 'Advertencia',
                self::SEVERITY_CRITICAL => 'Crítica',
            ])
            ->setDefaultValue(self::SEVERITY_WARNING)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado actual de la alerta.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_OPEN => 'Abierta',
                self::STATUS_ACKNOWLEDGED => 'Reconocida',
                self::STATUS_RESOLVED => 'Resuelta',
                self::STATUS_DISMISSED => 'Descartada',
            ])
            ->setDefaultValue(self::STATUS_OPEN)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // CONTEXTO DE LA ALERTA
        // ═══════════════════════════════════════════════════════════════════════

        $fields['message'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Mensaje'))
            ->setDescription(t('Descripción detallada del problema detectado.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'string',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['related_tenant'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant Relacionado'))
            ->setDescription(t('Tenant afectado por esta alerta.'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['metric_value'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Valor de Métrica'))
            ->setDescription(t('Valor actual de la métrica que disparó la alerta.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['threshold'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Umbral'))
            ->setDescription(t('Umbral configurado que se ha superado.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // PLAYBOOK (ACCIÓN PRESCRIPTIVA)
        // ═══════════════════════════════════════════════════════════════════════

        $fields['playbook'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Playbook'))
            ->setDescription(t('Acciones recomendadas para resolver esta alerta.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'string',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['playbook_executed'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Playbook Ejecutado'))
            ->setDescription(t('Indica si el playbook automatizado ya se ejecutó.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'boolean',
                'weight' => 9,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // ═══════════════════════════════════════════════════════════════════════
        // METADATOS
        // ═══════════════════════════════════════════════════════════════════════

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creada'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificada'));

        $fields['resolved_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Resuelta en'))
            ->setDescription(t('Timestamp de resolución de la alerta.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

}
