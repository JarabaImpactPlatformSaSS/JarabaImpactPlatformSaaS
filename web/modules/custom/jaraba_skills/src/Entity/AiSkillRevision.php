<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AiSkillRevision.
 *
 * Almacena snapshots históricos de habilidades IA para rollback y auditoría.
 * Se crean automáticamente cada vez que se edita un AiSkill.
 *
 * @ContentEntityType(
 *   id = "ai_skill_revision",
 *   label = @Translation("Revisión de Habilidad IA"),
 *   label_collection = @Translation("Revisiones de Habilidades IA"),
 *   label_singular = @Translation("revisión de habilidad"),
 *   label_plural = @Translation("revisiones de habilidades"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_skills\AiSkillRevisionListBuilder",
 *     "access" = "Drupal\jaraba_skills\AiSkillAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ai_skill_revision",
 *   admin_permission = "administer ai skills",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ai-skills/{ai_skill}/revisions/{ai_skill_revision}",
 *     "collection" = "/admin/content/ai-skills/{ai_skill}/revisions",
 *   },
 *   field_ui_base_route = "entity.ai_skill_revision.settings",
 * )
 */
class AiSkillRevision extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al skill padre.
        $fields['skill_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Habilidad'))
            ->setDescription(t('La habilidad de la que esta es una revisión.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'ai_skill')
            ->setDisplayConfigurable('view', TRUE);

        // Número de revisión.
        $fields['revision_number'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Número de Revisión'))
            ->setDescription(t('Número secuencial de esta revisión.'))
            ->setRequired(TRUE)
            ->setDefaultValue(1)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre de la skill en este punto.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre de la habilidad en esta revisión.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('view', TRUE);

        // Contenido/Instrucciones en este punto.
        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Instrucciones'))
            ->setDescription(t('Instrucciones de la habilidad en esta revisión.'))
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de skill.
        $fields['skill_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de Habilidad'))
            ->setDescription(t('Tipo jerárquico (core, vertical, agent, tenant).'))
            ->setSetting('max_length', 50)
            ->setDisplayConfigurable('view', TRUE);

        // Prioridad en este punto.
        $fields['priority'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Prioridad'))
            ->setDescription(t('Prioridad de la habilidad en esta revisión.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // Estado activo en este punto.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Si la habilidad estaba activa en esta revisión.'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Usuario que realizó el cambio.
        $fields['changed_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Modificado por'))
            ->setDescription(t('Usuario que guardó esta revisión.'))
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        // Resumen del cambio (opcional).
        $fields['change_summary'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Resumen del Cambio'))
            ->setDescription(t('Descripción breve del cambio realizado.'))
            ->setSetting('max_length', 500)
            ->setDisplayConfigurable('view', TRUE);

        // Timestamp de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha y hora de creación de la revisión.'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene el ID del skill padre.
     */
    public function getSkillId(): ?int
    {
        return $this->get('skill_id')->target_id ? (int) $this->get('skill_id')->target_id : NULL;
    }

    /**
     * Obtiene el número de revisión.
     */
    public function getRevisionNumber(): int
    {
        return (int) ($this->get('revision_number')->value ?? 1);
    }

    /**
     * Obtiene el nombre de la skill en esta revisión.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el contenido de la skill en esta revisión.
     */
    public function getContent(): string
    {
        return $this->get('content')->value ?? '';
    }

    /**
     * Obtiene el tipo de skill.
     */
    public function getSkillType(): string
    {
        return $this->get('skill_type')->value ?? 'core';
    }

    /**
     * Obtiene la prioridad.
     */
    public function getPriority(): int
    {
        return (int) ($this->get('priority')->value ?? 0);
    }

    /**
     * Verifica si estaba activa.
     */
    public function wasActive(): bool
    {
        return (bool) ($this->get('is_active')->value ?? TRUE);
    }

    /**
     * Obtiene el resumen del cambio.
     */
    public function getChangeSummary(): string
    {
        return $this->get('change_summary')->value ?? '';
    }

    /**
     * Obtiene la fecha de creación como timestamp.
     */
    public function getCreatedTime(): int
    {
        return (int) ($this->get('created')->value ?? 0);
    }

}
