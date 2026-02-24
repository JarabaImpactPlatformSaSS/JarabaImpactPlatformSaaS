<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Forecast (APPEND-ONLY — ENTITY-APPEND-001).
 *
 * ESTRUCTURA:
 *   Entidad de contenido de solo adicion que registra cada prevision
 *   financiera (MRR, ARR, revenue, users). Una vez creada, no se
 *   modifica ni se elimina. Almacena el valor predicho, intervalo de
 *   confianza y, opcionalmente, el valor real para validacion posterior.
 *
 * LOGICA:
 *   - APPEND-ONLY (ENTITY-APPEND-001): solo formulario "default" + "delete",
 *     sin edit. No implementa EntityChangedInterface.
 *   - forecast_type indica la metrica predicha (mrr/arr/revenue/users).
 *   - period indica la granularidad temporal (monthly/quarterly/yearly).
 *   - confidence_low/confidence_high definen el intervalo de confianza.
 *   - actual_value se rellena a posteriori para calcular precision del modelo.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion, AUDIT-CONS-005). NOT required.
 *
 * @ContentEntityType(
 *   id = "forecast",
 *   label = @Translation("Forecast"),
 *   label_collection = @Translation("Forecasts"),
 *   label_singular = @Translation("forecast"),
 *   label_plural = @Translation("forecasts"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_predictive\ListBuilder\ForecastListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_predictive\Access\ForecastAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "forecast",
 *   admin_permission = "administer predictions",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/forecasts",
 *     "canonical" = "/admin/content/forecasts/{forecast}",
 *     "delete-form" = "/admin/content/forecasts/{forecast}/delete",
 *   },
 *   field_ui_base_route = "entity.forecast.settings",
 * )
 */
class Forecast extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005). NOT required.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion a la que pertenece este forecast. NULL = plataforma global.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 2: forecast_type — tipo de metrica predicha.
    $fields['forecast_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de forecast'))
      ->setDescription(t('Metrica financiera o de negocio que se predice.'))
      ->setRequired(TRUE)
      ->setDefaultValue('mrr')
      ->setSetting('allowed_values', [
        'mrr' => 'MRR',
        'arr' => 'ARR',
        'revenue' => 'Revenue',
        'users' => 'Usuarios',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 3: period — periodo temporal de la prevision.
    $fields['period'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Periodo'))
      ->setDescription(t('Granularidad temporal de la prevision.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'monthly' => 'Mensual',
        'quarterly' => 'Trimestral',
        'yearly' => 'Anual',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 4: forecast_date — fecha de la prevision.
    $fields['forecast_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha del forecast'))
      ->setDescription(t('Fecha para la que se realiza la prevision.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 5: predicted_value — valor predicho.
    $fields['predicted_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor predicho'))
      ->setDescription(t('Valor numerico predicho por el modelo.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 6: confidence_low — limite inferior del intervalo de confianza.
    $fields['confidence_low'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Confianza (inferior)'))
      ->setDescription(t('Limite inferior del intervalo de confianza.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: confidence_high — limite superior del intervalo de confianza.
    $fields['confidence_high'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Confianza (superior)'))
      ->setDescription(t('Limite superior del intervalo de confianza.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 8: actual_value — valor real (rellenado a posteriori).
    $fields['actual_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor real'))
      ->setDescription(t('Valor real observado, rellenado a posteriori para validacion del modelo.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 9: model_version — version del modelo utilizado.
    $fields['model_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version del modelo'))
      ->setDescription(t('Identificador de la version del modelo de forecasting utilizado.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: calculated_at — momento del calculo.
    $fields['calculated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de calculo'))
      ->setDescription(t('Fecha y hora en que se realizo el calculo del forecast.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 11: created — fecha de creacion (NO changed — append-only).
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion del registro de forecast.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['forecast__tenant_id'] = ['tenant_id'];
    $schema['indexes']['forecast__forecast_type'] = ['forecast_type'];
    $schema['indexes']['forecast__period'] = ['period'];
    $schema['indexes']['forecast__model_version'] = ['model_version'];

    return $schema;
  }

}
