<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AiSkill.
 *
 * Representa una habilidad que puede ser asignada a agentes IA.
 * Las habilidades se resuelven jerárquicamente: Core → Vertical → Agent → Tenant.
 *
 * @ContentEntityType(
 *   id = "ai_skill",
 *   label = @Translation("Habilidad IA"),
 *   label_collection = @Translation("Habilidades IA"),
 *   label_singular = @Translation("habilidad IA"),
 *   label_plural = @Translation("habilidades IA"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_skills\AiSkillListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_skills\Form\AiSkillForm",
 *       "add" = "Drupal\jaraba_skills\Form\AiSkillForm",
 *       "edit" = "Drupal\jaraba_skills\Form\AiSkillForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_skills\AiSkillAccessControlHandler",
 *   },
 *   base_table = "ai_skill",
 *   admin_permission = "administer ai skills",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.ai_skill.admin_form",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ai-skills/{ai_skill}",
 *     "add-form" = "/admin/content/ai-skills/add",
 *     "edit-form" = "/admin/content/ai-skills/{ai_skill}/edit",
 *     "delete-form" = "/admin/content/ai-skills/{ai_skill}/delete",
 *     "collection" = "/admin/content/ai-skills",
 *   },
 * )
 */
class AiSkill extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre de la habilidad.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre identificador de la habilidad.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de skill (jerarquía).
        $fields['skill_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Habilidad'))
            ->setDescription(t('Nivel jerárquico de la habilidad.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'core' => 'Core (Global)',
                'vertical' => 'Vertical (Industria)',
                'agent' => 'Agent (Rol específico)',
                'tenant' => 'Tenant (Cliente)',
            ])
            ->setDefaultValue('core')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -9,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Contenido/Instrucciones para el LLM.
        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Instrucciones'))
            ->setDescription(t('Instrucciones detalladas que el LLM debe seguir.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
                'settings' => [
                    'rows' => 10,
                ],
            ])
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Vertical asociada (para tipo 'vertical').
        $fields['vertical_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Vertical'))
            ->setDescription(t('ID de la vertical (ej: emprendimiento, empleabilidad).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de agente (para tipo 'agent').
        $fields['agent_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de Agente'))
            ->setDescription(t('ID del tipo de agente (ej: sales, support, diagnostic).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tenant asociado (para tipo 'tenant').
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Organización a la que pertenece esta habilidad.'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Prioridad (para ordenar habilidades del mismo nivel).
        $fields['priority'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Prioridad'))
            ->setDescription(t('Orden de aplicación (mayor = primero).'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado activo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Solo las habilidades activas se incluyen en prompts.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        // === A/B TESTING ===

        // ID del experimento (agrupa variantes del mismo skill).
        $fields['experiment_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID de Experimento'))
            ->setDescription(t('Para A/B Testing: ID que agrupa variantes del mismo skill. Vacío = no participa en experimento.'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Variante del experimento (A, B, C, etc.).
        $fields['experiment_variant'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Variante'))
            ->setDescription(t('Identificador de variante (ej: A, B, control, tratamiento).'))
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene el contenido/instrucciones de la habilidad.
     */
    public function getContent(): string
    {
        return $this->get('content')->value ?? '';
    }

    /**
     * Obtiene el tipo de habilidad.
     */
    public function getSkillType(): string
    {
        return $this->get('skill_type')->value ?? 'core';
    }

    /**
     * Verifica si la habilidad está activa.
     */
    public function isActive(): bool
    {
        return (bool) ($this->get('is_active')->value ?? TRUE);
    }

}
