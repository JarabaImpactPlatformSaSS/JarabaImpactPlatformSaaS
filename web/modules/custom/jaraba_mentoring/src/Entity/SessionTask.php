<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Session Task.
 *
 * Representa una tarea asignada durante una sesión de mentoría.
 * Puede ser asignada por el mentor al mentee o viceversa.
 *
 * SPEC: 32_Emprendimiento_Mentoring_Sessions_v1
 *
 * @ContentEntityType(
 *   id = "session_task",
 *   label = @Translation("Tarea de Sesión"),
 *   label_collection = @Translation("Tareas de Sesión"),
 *   label_singular = @Translation("tarea de sesión"),
 *   label_plural = @Translation("tareas de sesión"),
 *   label_count = @PluralTranslation(
 *     singular = "@count tarea",
 *     plural = "@count tareas",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "session_task",
 *   admin_permission = "manage sessions",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 * )
 */
class SessionTask extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === Relación con Sesión ===
        $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Sesión'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentoring_session')
            ->setDisplayConfigurable('view', TRUE);

        // === Información de la Tarea ===
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Asignación ===
        $fields['assigned_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Asignada por'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Asignada a'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        $fields['assignee_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Asignado'))
            ->setSetting('allowed_values', [
                'mentor' => 'Mentor',
                'mentee' => 'Emprendedor',
            ])
            ->setDefaultValue('mentee')
            ->setDisplayConfigurable('view', TRUE);

        // === Fechas ===
        $fields['due_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Límite'))
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Estado ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'pending' => 'Pendiente',
                'in_progress' => 'En Progreso',
                'completed' => 'Completada',
                'cancelled' => 'Cancelada',
            ])
            ->setDefaultValue('pending')
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['completed_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Completada'));

        // === Prioridad ===
        $fields['priority'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Prioridad'))
            ->setSetting('allowed_values', [
                'low' => 'Baja',
                'medium' => 'Media',
                'high' => 'Alta',
            ])
            ->setDefaultValue('medium')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Checks if the task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->get('status')->value === 'completed';
    }

    /**
     * Marks the task as completed.
     */
    public function complete(): self
    {
        $this->set('status', 'completed');
        $this->set('completed_at', date('Y-m-d\TH:i:s'));
        return $this;
    }

}
