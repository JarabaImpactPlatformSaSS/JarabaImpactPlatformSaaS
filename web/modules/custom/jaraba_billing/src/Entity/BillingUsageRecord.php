<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Registro de Uso de Billing.
 *
 * Registros de uso medido por tenant. Entidad append-only (sin edit/delete).
 * Los ajustes se realizan mediante registros compensatorios.
 *
 * @ContentEntityType(
 *   id = "billing_usage_record",
 *   label = @Translation("Registro de Uso"),
 *   label_collection = @Translation("Registros de Uso"),
 *   label_singular = @Translation("registro de uso"),
 *   label_plural = @Translation("registros de uso"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_billing\ListBuilder\BillingUsageRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\BillingUsageRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "billing_usage_record",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "metric_key",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/billing-usage/{billing_usage_record}",
 *     "add-form" = "/admin/content/billing-usage/add",
 *     "collection" = "/admin/content/billing-usage",
 *   },
 *   field_ui_base_route = "jaraba_billing.billing_usage_record.settings",
 * )
 */
class BillingUsageRecord extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metric_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Métrica'))
      ->setDescription(t('Clave de la métrica medida (ej: api_calls, storage_gb, users).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Cantidad'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue('0.0000')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Unidad'))
      ->setDescription(t('Unidad de medida (ej: calls, gb, seats).'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Inicio de Periodo'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fin de Periodo'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Origen'))
      ->setDescription(t('Sistema que registró el uso (metering, manual, stripe).'))
      ->setDefaultValue('metering')
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_usage_record_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Usage Record ID'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subscription_item_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Subscription Item ID'))
      ->setDescription(t('ID del subscription item en Stripe (si_xxx).'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reported_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Reportado a Stripe'))
      ->setDescription(t('Timestamp de cuándo se envió a Stripe. NULL = pendiente.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['idempotency_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave de Idempotencia'))
      ->setDescription(t('Clave única para prevenir registros duplicados.'))
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Facturado'))
      ->setDescription(t('Indica si este uso ya ha sido facturado.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_period'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Periodo de Facturación'))
      ->setDescription(t('Periodo en formato YYYY-MM.'))
      ->setSetting('max_length', 7)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadatos'))
      ->setDescription(t('JSON con datos adicionales del registro de uso.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Registro'));

    return $fields;
  }

}
