<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad AlertRuleAgro.
 *
 * Regla de alerta configurable para el dashboard operativo.
 * Evalúa métricas diarias contra umbrales y genera notificaciones
 * cuando se superan los límites configurados.
 *
 * TIPOS DE ALERTA:
 * - stock_bajo: Producto con stock < umbral
 * - pedido_estancado: Pedido sin avanzar > N horas
 * - rating_drop: Rating medio cae bajo umbral
 * - gmv_drop: GMV del día cae bajo % del promedio
 * - cancelaciones_high: % cancelaciones excede umbral
 *
 * @ContentEntityType(
 *   id = "alert_rule_agro",
 *   label = @Translation("Regla Alerta Agro"),
 *   label_collection = @Translation("Reglas Alerta Agro"),
 *   label_singular = @Translation("regla de alerta agro"),
 *   label_plural = @Translation("reglas de alerta agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\AlertRuleAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\AlertRuleAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\AlertRuleAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\AlertRuleAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AlertRuleAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "alert_rule_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.alert_rule_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-alert-rules/{alert_rule_agro}",
 *     "add-form" = "/admin/content/agro-alert-rules/add",
 *     "edit-form" = "/admin/content/agro-alert-rules/{alert_rule_agro}/edit",
 *     "delete-form" = "/admin/content/agro-alert-rules/{alert_rule_agro}/delete",
 *     "collection" = "/admin/content/agro-alert-rules",
 *   },
 * )
 */
class AlertRuleAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['metric'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Métrica'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'gmv' => t('GMV diario'),
                'orders_count' => t('Nº pedidos'),
                'aov' => t('Ticket medio'),
                'conversion_rate' => t('Tasa conversión'),
                'avg_rating' => t('Rating medio'),
                'orders_cancelled' => t('Pedidos cancelados'),
                'stock_low' => t('Stock bajo'),
                'order_stalled' => t('Pedido estancado'),
                'qr_scans' => t('Escaneos QR'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -9])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['condition'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Condición'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'lt' => t('Menor que'),
                'lte' => t('Menor o igual'),
                'gt' => t('Mayor que'),
                'gte' => t('Mayor o igual'),
                'drop_pct' => t('Cae más del % respecto a media'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -8])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['threshold'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Umbral'))
            ->setDescription(t('Valor umbral para la condición.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['severity'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Severidad'))
            ->setSetting('allowed_values', [
                'info' => t('ℹ Info'),
                'warning' => t('⚠ Aviso'),
                'critical' => t('🔴 Crítico'),
            ])
            ->setDefaultValue('warning')
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notify_channels'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Canales de notificación'))
            ->setDescription(t('JSON array: ["email", "dashboard", "slack"].'))
            ->setDefaultValue('["dashboard"]')
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -5, 'settings' => ['rows' => 2]])
            ->setDisplayConfigurable('form', TRUE);

        $fields['last_triggered'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Última activación'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('view', TRUE);

        $fields['trigger_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Veces activada'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }
    public function getMetric(): string
    {
        return $this->get('metric')->value ?? '';
    }
    public function getCondition(): string
    {
        return $this->get('condition')->value ?? '';
    }
    public function getThreshold(): float
    {
        return (float) ($this->get('threshold')->value ?? 0);
    }
    public function getSeverity(): string
    {
        return $this->get('severity')->value ?? 'warning';
    }
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }
}
