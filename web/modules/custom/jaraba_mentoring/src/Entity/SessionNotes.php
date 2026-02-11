<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Session Notes.
 *
 * Notas estructuradas de cada sesión de mentoría.
 *
 * SPEC: 32_Emprendimiento_Mentoring_Sessions_v1
 *
 * @ContentEntityType(
 *   id = "session_notes",
 *   label = @Translation("Notas de Sesión"),
 *   label_collection = @Translation("Notas de Sesiones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "session_notes",
 *   admin_permission = "manage sessions",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class SessionNotes extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Sesión'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentoring_session');

        $fields['template_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Template'))
            ->setSetting('allowed_values', [
                'general' => 'General',
                'diagnostic' => 'Diagnóstico',
                'action_plan' => 'Plan de Acción',
                'review' => 'Revisión',
            ])
            ->setDefaultValue('general');

        $fields['topics_discussed'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Temas Tratados'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['key_insights'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Insights Clave'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['challenges_identified'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Desafíos Identificados'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['resources_shared'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Recursos Compartidos'))
            ->setCardinality(-1)
            ->setDisplayConfigurable('form', TRUE);

        $fields['next_session_focus'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Foco Próxima Sesión'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_shared_with_mentee'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Compartido con Emprendedor'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 25,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        return $fields;
    }

}
