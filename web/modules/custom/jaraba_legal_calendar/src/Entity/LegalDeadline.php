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
 * Define la entidad Plazo Legal (LegalDeadline).
 *
 * Representa un plazo procesal, tributario, contractual o administrativo
 * vinculado a un expediente. Soporta computo automatico de plazos
 * procesales (LEC Art. 130-136) y tributarios (LGT Art. 48).
 *
 * @ContentEntityType(
 *   id = "legal_deadline",
 *   label = @Translation("Plazo Legal"),
 *   label_collection = @Translation("Plazos Legales"),
 *   label_singular = @Translation("plazo legal"),
 *   label_plural = @Translation("plazos legales"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_calendar\ListBuilder\LegalDeadlineListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_calendar\Form\LegalDeadlineForm",
 *       "add" = "Drupal\jaraba_legal_calendar\Form\LegalDeadlineForm",
 *       "edit" = "Drupal\jaraba_legal_calendar\Form\LegalDeadlineForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_calendar\Access\LegalDeadlineAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_deadline",
 *   admin_permission = "manage legal deadlines",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-deadlines",
 *     "add-form" = "/admin/content/legal-deadlines/add",
 *     "canonical" = "/admin/content/legal-deadlines/{legal_deadline}",
 *     "edit-form" = "/admin/content/legal-deadlines/{legal_deadline}/edit",
 *     "delete-form" = "/admin/content/legal-deadlines/{legal_deadline}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_calendar.settings",
 * )
 */
class LegalDeadline extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

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
      ->setDescription(new TranslatableMarkup('Expediente asociado al plazo.'))
      ->setSetting('target_type', 'client_case')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Descripcion del Plazo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['deadline_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de Plazo'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'procesal' => new TranslatableMarkup('Procesal'),
        'tributario' => new TranslatableMarkup('Tributario'),
        'contractual' => new TranslatableMarkup('Contractual'),
        'administrativo' => new TranslatableMarkup('Administrativo'),
        'custom' => new TranslatableMarkup('Personalizado'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['legal_basis'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Base Legal'))
      ->setDescription(new TranslatableMarkup('Ej: LEC Art. 405, LGT Art. 48'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['due_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Vencimiento'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_computed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Calculado Automaticamente'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['base_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha Base'))
      ->setDescription(new TranslatableMarkup('Fecha base para computo (ej: fecha notificacion).'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['computation_rule'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Regla de Computo'))
      ->setDescription(new TranslatableMarkup('Ej: 20_dias_habiles, 30_dias_naturales'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['alert_days_before'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Dias de Alerta'))
      ->setDescription(new TranslatableMarkup('Dias de antelacion para alerta.'))
      ->setDefaultValue(3)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Responsable'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => new TranslatableMarkup('Pendiente'),
        'in_progress' => new TranslatableMarkup('En progreso'),
        'completed' => new TranslatableMarkup('Completado'),
        'overdue' => new TranslatableMarkup('Vencido'),
        'cancelled' => new TranslatableMarkup('Cancelado'),
      ])
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Completado'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
