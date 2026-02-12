<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the FieldExit entity for Customer Discovery tracking.
 *
 * Siguiendo la metodología de Steve Blank ("Get Out of the Building"),
 * esta entidad registra cada interacción con clientes potenciales
 * fuera de la oficina para validar hipótesis de negocio.
 *
 * @ContentEntityType(
 *   id = "field_exit",
 *   label = @Translation("Field Exit"),
 *   label_collection = @Translation("Field Exits"),
 *   label_singular = @Translation("field exit"),
 *   label_plural = @Translation("field exits"),
 *   label_count = @PluralTranslation(
 *     singular = "@count field exit",
 *     plural = "@count field exits",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "field_exit",
 *   admin_permission = "administer field exits",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "contact_name",
 *     "owner" = "entrepreneur_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/field-exits",
 *     "add-form" = "/admin/content/field-exits/add",
 *     "canonical" = "/admin/content/field-exits/{field_exit}",
 *     "edit-form" = "/admin/content/field-exits/{field_exit}/edit",
 *     "delete-form" = "/admin/content/field-exits/{field_exit}/delete",
 *   },
 *   field_ui_base_route = "entity.field_exit.settings",
 * )
 */
class FieldExit extends ContentEntityBase implements FieldExitInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function label(): ?string
    {
        $name = $this->get('contact_name')->value ?? '';
        $type = $this->get('exit_type')->value ?? '';
        return "{$name} ({$type})";
    }

    /**
     * {@inheritdoc}
     */
    public function getExitType(): string
    {
        return $this->get('exit_type')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getContactsCount(): int
    {
        return (int) ($this->get('contacts_count')->value ?? 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getLearnings(): string
    {
        return $this->get('learnings')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHypothesisValidated(): ?bool
    {
        $value = $this->get('hypothesis_validated')->value;
        return $value !== NULL ? (bool) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Emprendedor propietario
        $fields['entrepreneur_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Entrepreneur'))
            ->setDescription(t('The entrepreneur who made this field exit.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => -10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de salida
        $fields['exit_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Exit Type'))
            ->setDescription(t('Type of customer discovery interaction.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'interview' => t('Customer Interview'),
                'observation' => t('Field Observation'),
                'survey' => t('Survey/Questionnaire'),
                'focus_group' => t('Focus Group'),
                'prototype_test' => t('Prototype Test'),
                'sales_test' => t('Sales Pitch Test'),
                'landing_page' => t('Landing Page Test'),
                'event' => t('Networking Event'),
                'cold_call' => t('Cold Call/Outreach'),
                'other' => t('Other'),
            ])
            ->setDefaultValue('interview')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre del contacto
        $fields['contact_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Contact Name'))
            ->setDescription(t('Name of the person or company contacted.'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Número de contactos en esta salida
        $fields['contacts_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Contacts Count'))
            ->setDescription(t('Number of people contacted in this exit.'))
            ->setDefaultValue(1)
            ->setSetting('min', 1)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Aprendizajes clave
        $fields['learnings'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Key Learnings'))
            ->setDescription(t('What did you learn from this interaction?'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 3,
                'settings' => [
                    'rows' => 4,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Hipótesis que se estaba validando
        $fields['hypothesis_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Related Hypothesis'))
            ->setDescription(t('The hypothesis being validated with this exit.'))
            ->setSetting('target_type', 'hypothesis')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 4,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿La hipótesis fue validada?
        $fields['hypothesis_validated'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Hypothesis Validated'))
            ->setDescription(t('Did this exit validate or invalidate the hypothesis?'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'boolean',
                'weight' => 5,
                'settings' => [
                    'format' => 'yes-no',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de la salida
        $fields['exit_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Exit Date'))
            ->setDescription(t('When this field exit occurred.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'datetime_default',
                'weight' => 6,
            ])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Timestamps
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the entity was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time that the entity was last edited.'));

        return $fields;
    }

}
