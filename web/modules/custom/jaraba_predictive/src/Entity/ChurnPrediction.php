<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Prediccion de Churn (APPEND-ONLY — ENTITY-APPEND-001).
 *
 * ESTRUCTURA:
 *   Entidad de contenido de solo adicion que registra cada prediccion
 *   de riesgo de churn para una organizacion. Una vez creada, no se
 *   modifica ni se elimina. Almacena el score de riesgo, factores
 *   contribuyentes, acciones recomendadas y snapshot de features.
 *
 * LOGICA:
 *   - APPEND-ONLY (ENTITY-APPEND-001): solo formulario "default" + "delete",
 *     sin edit. No implementa EntityChangedInterface.
 *   - risk_score es un entero 0-100 que indica probabilidad de churn.
 *   - risk_level categoriza el riesgo en low/medium/high/critical.
 *   - contributing_factors y recommended_actions almacenan JSON.
 *   - features_snapshot captura el estado de features al momento del calculo.
 *   - accuracy_confidence es un decimal 0.00-1.00 indicando confianza.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion, AUDIT-CONS-005).
 *
 * @ContentEntityType(
 *   id = "churn_prediction",
 *   label = @Translation("Prediccion de Churn"),
 *   label_collection = @Translation("Predicciones de Churn"),
 *   label_singular = @Translation("prediccion de churn"),
 *   label_plural = @Translation("predicciones de churn"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_predictive\ListBuilder\ChurnPredictionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_predictive\Access\ChurnPredictionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "churn_prediction",
 *   admin_permission = "administer predictions",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/churn-predictions",
 *     "canonical" = "/admin/content/churn-predictions/{churn_prediction}",
 *     "delete-form" = "/admin/content/churn-predictions/{churn_prediction}/delete",
 *   },
 * )
 */
class ChurnPrediction extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion a la que pertenece esta prediccion de churn.'))
      ->setRequired(TRUE)
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

    // Campo 2: risk_score — puntuacion de riesgo (0-100).
    $fields['risk_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuacion de riesgo'))
      ->setDescription(t('Puntuacion de riesgo de churn de 0 (sin riesgo) a 100 (churn seguro).'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 3: risk_level — nivel de riesgo categorizado.
    $fields['risk_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Nivel de riesgo'))
      ->setDescription(t('Categoria del nivel de riesgo de churn.'))
      ->setRequired(TRUE)
      ->setDefaultValue('low')
      ->setSetting('allowed_values', [
        'low' => 'Bajo',
        'medium' => 'Medio',
        'high' => 'Alto',
        'critical' => 'Critico',
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

    // Campo 4: contributing_factors — factores contribuyentes (JSON).
    $fields['contributing_factors'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Factores contribuyentes'))
      ->setDescription(t('Factores que contribuyen al riesgo de churn en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 5: recommended_actions — acciones recomendadas (JSON).
    $fields['recommended_actions'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Acciones recomendadas'))
      ->setDescription(t('Acciones recomendadas para mitigar el riesgo de churn en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 6: predicted_churn_date — fecha estimada de churn.
    $fields['predicted_churn_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha estimada de churn'))
      ->setDescription(t('Fecha en la que se estima que ocurrira el churn.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: model_version — version del modelo utilizado.
    $fields['model_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version del modelo'))
      ->setDescription(t('Identificador de la version del modelo predictivo utilizado.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 8: accuracy_confidence — confianza de la prediccion (0.00-1.00).
    $fields['accuracy_confidence'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Confianza'))
      ->setDescription(t('Nivel de confianza de la prediccion (0.00 a 1.00).'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 3)
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

    // Campo 9: features_snapshot — snapshot de features al momento del calculo (JSON).
    $fields['features_snapshot'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Snapshot de features'))
      ->setDescription(t('Datos de features utilizados para el calculo en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: calculated_at — momento del calculo.
    $fields['calculated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de calculo'))
      ->setDescription(t('Fecha y hora en que se realizo el calculo de la prediccion.'))
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
      ->setDescription(t('Marca temporal de creacion del registro de prediccion.'))
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

    $schema['indexes']['churn_prediction__tenant_id'] = ['tenant_id'];
    $schema['indexes']['churn_prediction__risk_score'] = ['risk_score'];
    $schema['indexes']['churn_prediction__risk_level'] = ['risk_level'];
    $schema['indexes']['churn_prediction__model_version'] = ['model_version'];

    return $schema;
  }

}
