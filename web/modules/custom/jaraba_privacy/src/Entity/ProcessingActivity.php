<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD PROCESSING ACTIVITY — Registro de Actividades de Tratamiento (RAT).
 *
 * ESTRUCTURA:
 * Content Entity que implementa el Registro de Actividades de Tratamiento
 * exigido por el RGPD Art. 30. Cada actividad documenta qué datos se tratan,
 * con qué finalidad, base legal, destinatarios y medidas de seguridad.
 *
 * LÓGICA DE NEGOCIO:
 * - Obligatorio para organizaciones con más de 250 empleados o que traten
 *   datos de categorías especiales, pero recomendable para todas.
 * - Cada vertical puede tener actividades de tratamiento predefinidas.
 * - Se puede indicar si requiere DPIA (Evaluación de Impacto).
 * - Las transferencias internacionales se documentan con base legal.
 *
 * RELACIONES:
 * - tenant_id → Group (referencia al tenant propietario)
 *
 * Spec: Doc 183 §5.1. Plan: FASE 1, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "processing_activity",
 *   label = @Translation("Processing Activity"),
 *   label_collection = @Translation("Processing Activities"),
 *   label_singular = @Translation("processing activity"),
 *   label_plural = @Translation("processing activities"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_privacy\ListBuilder\ProcessingActivityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_privacy\Form\ProcessingActivityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_privacy\Access\ProcessingActivityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "processing_activity",
 *   admin_permission = "administer privacy",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "activity_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/processing-activity/{processing_activity}",
 *     "add-form" = "/admin/content/processing-activity/add",
 *     "edit-form" = "/admin/content/processing-activity/{processing_activity}/edit",
 *     "delete-form" = "/admin/content/processing-activity/{processing_activity}/delete",
 *     "collection" = "/admin/content/processing-activities",
 *   },
 *   field_ui_base_route = "jaraba_privacy.processing_activity.settings",
 * )
 */
class ProcessingActivity extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece esta actividad de tratamiento.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- IDENTIFICACIÓN ---

    $fields['activity_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre de la actividad'))
      ->setDescription(new TranslatableMarkup('Nombre descriptivo de la actividad de tratamiento.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['purpose'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Finalidad'))
      ->setDescription(new TranslatableMarkup('Descripción de la finalidad del tratamiento de datos.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 1, 'type' => 'text_textarea'])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['legal_basis'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Base legal'))
      ->setDescription(new TranslatableMarkup('Base jurídica del tratamiento (RGPD Art. 6).'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'consent' => new TranslatableMarkup('Consentimiento (Art. 6.1.a)'),
        'contract' => new TranslatableMarkup('Ejecución de contrato (Art. 6.1.b)'),
        'legal_obligation' => new TranslatableMarkup('Obligación legal (Art. 6.1.c)'),
        'vital_interest' => new TranslatableMarkup('Interés vital (Art. 6.1.d)'),
        'public_interest' => new TranslatableMarkup('Interés público (Art. 6.1.e)'),
        'legitimate_interest' => new TranslatableMarkup('Interés legítimo (Art. 6.1.f)'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS TRATADOS ---

    $fields['data_categories'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Categorías de datos'))
      ->setDescription(new TranslatableMarkup('JSON con las categorías de datos personales tratados.'))
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data_subjects'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Categorías de interesados'))
      ->setDescription(new TranslatableMarkup('JSON con las categorías de personas cuyos datos se tratan.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipients'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Destinatarios'))
      ->setDescription(new TranslatableMarkup('JSON con los destinatarios de los datos tratados.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TRANSFERENCIAS ---

    $fields['international_transfers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Transferencias internacionales'))
      ->setDescription(new TranslatableMarkup('JSON con detalles de transferencias internacionales de datos.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RETENCIÓN Y SEGURIDAD ---

    $fields['retention_period'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Plazo de conservación'))
      ->setDescription(new TranslatableMarkup('Período de retención de los datos (ej: "5 años", "mientras dure la relación contractual").'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['security_measures'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Medidas de seguridad'))
      ->setDescription(new TranslatableMarkup('JSON con las medidas técnicas y organizativas de seguridad.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DPIA ---

    $fields['dpia_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Requiere DPIA'))
      ->setDescription(new TranslatableMarkup('Indica si esta actividad requiere Evaluación de Impacto (DPIA).'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dpia_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Referencia DPIA'))
      ->setDescription(new TranslatableMarkup('Referencia al documento DPIA si se ha realizado.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERTICAL Y ESTADO ---

    $fields['vertical'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Vertical'))
      ->setDescription(new TranslatableMarkup('Vertical del ecosistema asociada a esta actividad.'))
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Activa'))
      ->setDescription(new TranslatableMarkup('Indica si esta actividad de tratamiento está vigente.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creación del registro.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'))
      ->setDescription(new TranslatableMarkup('Fecha de última modificación.'));

    return $fields;
  }

  /**
   * Comprueba si esta actividad está activa.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

  /**
   * Comprueba si requiere DPIA.
   */
  public function requiresDpia(): bool {
    return (bool) $this->get('dpia_required')->value;
  }

}
