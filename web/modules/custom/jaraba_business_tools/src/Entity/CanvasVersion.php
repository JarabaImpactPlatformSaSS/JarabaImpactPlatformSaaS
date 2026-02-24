<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Canvas Version entity for historical snapshots.
 *
 * @ContentEntityType(
 *   id = "canvas_version",
 *   label = @Translation("Canvas Version"),
 *   label_collection = @Translation("Canvas Versions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_business_tools\Access\CanvasVersionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "canvas_version",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/canvas-versions",
 *   },
 *   field_ui_base_route = "entity.canvas_version.settings",
 * )
 */
class CanvasVersion extends ContentEntityBase
{

    /**
     * Gets the parent canvas ID.
     */
    public function getCanvasId(): int
    {
        return (int) $this->get('canvas_id')->target_id;
    }

    /**
     * Gets the version number.
     */
    public function getVersionNumber(): int
    {
        return (int) $this->get('version_number')->value;
    }

    /**
     * Gets the snapshot JSON of all blocks.
     */
    public function getSnapshot(): array
    {
        $value = $this->get('snapshot')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Gets the change summary.
     */
    public function getChangeSummary(): ?string
    {
        return $this->get('change_summary')->value;
    }

    /**
     * Gets the user who created this version.
     */
    public function getCreatedBy(): int
    {
        return (int) $this->get('created_by')->target_id;
    }

    /**
     * Gets the creation timestamp.
     */
    public function getCreatedTime(): int
    {
        return (int) $this->get('created')->value;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['canvas_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Canvas'))
            ->setDescription(t('Parent Business Model Canvas.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'business_model_canvas');

        $fields['version_number'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Version Number'))
            ->setDescription(t('Version number (1, 2, 3...).'))
            ->setRequired(TRUE)
            ->setSetting('min', 1);

        $fields['snapshot'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Snapshot'))
            ->setDescription(t('JSON snapshot of all blocks at this version.'))
            ->setRequired(TRUE);

        $fields['change_summary'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Change Summary'))
            ->setDescription(t('Summary of changes in this version.'))
            ->setSetting('max_length', 500);

        $fields['created_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Created By'))
            ->setDescription(t('User who created this version.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('Timestamp of version creation.'));

        return $fields;
    }

}
