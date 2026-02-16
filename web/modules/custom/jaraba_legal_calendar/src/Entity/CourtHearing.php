<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Senalamiento Judicial (CourtHearing).
 *
 * Representa un senalamiento de vista oral, audiencia previa, juicio u otro
 * acto procesal con fecha, hora, juzgado y opcion de videoconferencia.
 *
 * @ContentEntityType(
 *   id = "court_hearing",
 *   label = @Translation("Senalado Judicial"),
 *   label_collection = @Translation("Senalados Judiciales"),
 *   label_singular = @Translation("senalado judicial"),
 *   label_plural = @Translation("senalados judiciales"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_calendar\ListBuilder\CourtHearingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_calendar\Form\CourtHearingForm",
 *       "add" = "Drupal\jaraba_legal_calendar\Form\CourtHearingForm",
 *       "edit" = "Drupal\jaraba_legal_calendar\Form\CourtHearingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_calendar\Access\CourtHearingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "court_hearing",
 *   admin_permission = "manage legal hearings",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/court-hearings",
 *     "add-form" = "/admin/content/court-hearings/add",
 *     "canonical" = "/admin/content/court-hearings/{court_hearing}",
 *     "edit-form" = "/admin/content/court-hearings/{court_hearing}/edit",
 *     "delete-form" = "/admin/content/court-hearings/{court_hearing}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_calendar.settings",
 * )
 */
class CourtHearing extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setSetting('target_type', 'client_case')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Titulo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hearing_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Actuacion'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'vista_oral' => new TranslatableMarkup('Vista Oral'),
        'audiencia_previa' => new TranslatableMarkup('Audiencia Previa'),
        'juicio' => new TranslatableMarkup('Juicio'),
        'comparecencia' => new TranslatableMarkup('Comparecencia'),
        'declaracion' => new TranslatableMarkup('Declaracion'),
        'reconocimiento' => new TranslatableMarkup('Reconocimiento'),
        'medidas_cautelares' => new TranslatableMarkup('Medidas Cautelares'),
        'ejecucion' => new TranslatableMarkup('Ejecucion'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['court'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Organo Judicial'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['courtroom'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Sala'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha y Hora'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estimated_duration_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Duracion Estimada (min)'))
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Direccion'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_virtual'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Telematico'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['virtual_url'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('URL Videoconferencia'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('scheduled')
      ->setSetting('allowed_values', [
        'scheduled' => new TranslatableMarkup('Programado'),
        'confirmed' => new TranslatableMarkup('Confirmado'),
        'postponed' => new TranslatableMarkup('Aplazado'),
        'completed' => new TranslatableMarkup('Celebrado'),
        'cancelled' => new TranslatableMarkup('Suspendido'),
      ])
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['outcome'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Resultado'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas de Preparacion'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
