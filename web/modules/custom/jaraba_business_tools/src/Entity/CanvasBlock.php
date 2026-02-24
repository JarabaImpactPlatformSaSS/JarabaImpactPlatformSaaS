<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Canvas Block entity.
 *
 * Represents each of the 9 blocks in a Business Model Canvas.
 *
 * @ContentEntityType(
 *   id = "canvas_block",
 *   label = @Translation("Canvas Block"),
 *   label_collection = @Translation("Canvas Blocks"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\jaraba_business_tools\Access\CanvasBlockAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "canvas_block",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class CanvasBlock extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Block type constants matching the 9 BMC blocks.
     */
    public const TYPE_CUSTOMER_SEGMENTS = 'customer_segments';
    public const TYPE_VALUE_PROPOSITIONS = 'value_propositions';
    public const TYPE_CHANNELS = 'channels';
    public const TYPE_CUSTOMER_RELATIONSHIPS = 'customer_relationships';
    public const TYPE_REVENUE_STREAMS = 'revenue_streams';
    public const TYPE_KEY_RESOURCES = 'key_resources';
    public const TYPE_KEY_ACTIVITIES = 'key_activities';
    public const TYPE_KEY_PARTNERS = 'key_partners';
    public const TYPE_COST_STRUCTURE = 'cost_structure';

    /**
     * Gets all valid block types.
     */
    public static function getBlockTypes(): array
    {
        return [
            self::TYPE_CUSTOMER_SEGMENTS => t('Customer Segments'),
            self::TYPE_VALUE_PROPOSITIONS => t('Value Propositions'),
            self::TYPE_CHANNELS => t('Channels'),
            self::TYPE_CUSTOMER_RELATIONSHIPS => t('Customer Relationships'),
            self::TYPE_REVENUE_STREAMS => t('Revenue Streams'),
            self::TYPE_KEY_RESOURCES => t('Key Resources'),
            self::TYPE_KEY_ACTIVITIES => t('Key Activities'),
            self::TYPE_KEY_PARTNERS => t('Key Partners'),
            self::TYPE_COST_STRUCTURE => t('Cost Structure'),
        ];
    }

    /**
     * Gets the parent canvas ID.
     */
    public function getCanvasId(): int
    {
        return (int) $this->get('canvas_id')->target_id;
    }

    /**
     * Gets the block type.
     */
    public function getBlockType(): string
    {
        return $this->get('block_type')->value ?? '';
    }

    /**
     * Gets the items array (post-its).
     */
    public function getItems(): array
    {
        $value = $this->get('items')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Sets the items array.
     */
    public function setItems(array $items): self
    {
        $this->set('items', json_encode($items));
        return $this;
    }

    /**
     * Adds a new item to the block.
     */
    public function addItem(string $text, string $color = '#FFE082', int $priority = 0): self
    {
        $items = $this->getItems();
        $items[] = [
            'id' => \Drupal::service('uuid')->generate(),
            'text' => $text,
            'color' => $color,
            'priority' => $priority,
            'validated' => FALSE,
            'created_at' => date('c'),
            'position' => ['x' => 0, 'y' => count($items) * 40],
        ];
        return $this->setItems($items);
    }

    /**
     * Removes an item by its ID.
     */
    public function removeItem(string $itemId): self
    {
        $items = array_filter($this->getItems(), fn($item) => $item['id'] !== $itemId);
        return $this->setItems(array_values($items));
    }

    /**
     * Updates an item by its ID.
     */
    public function updateItem(string $itemId, array $updates): self
    {
        $items = $this->getItems();
        foreach ($items as &$item) {
            if ($item['id'] === $itemId) {
                $item = array_merge($item, $updates);
                break;
            }
        }
        return $this->setItems($items);
    }

    /**
     * Gets the notes for this block.
     */
    public function getNotes(): ?string
    {
        return $this->get('notes')->value;
    }

    /**
     * Sets the notes for this block.
     */
    public function setNotes(?string $notes): self
    {
        $this->set('notes', $notes);
        return $this;
    }

    /**
     * Gets AI suggestions for this block.
     */
    public function getAiSuggestions(): array
    {
        $value = $this->get('ai_suggestions')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Sets AI suggestions for this block.
     */
    public function setAiSuggestions(array $suggestions): self
    {
        $this->set('ai_suggestions', json_encode($suggestions));
        return $this;
    }

    /**
     * Checks if this block is validated by a mentor.
     */
    public function isValidated(): bool
    {
        return (bool) $this->get('is_validated')->value;
    }

    /**
     * Gets the item count.
     */
    public function getItemCount(): int
    {
        return count($this->getItems());
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
            ->setSetting('target_type', 'business_model_canvas')
            ->setDisplayConfigurable('form', TRUE);

        $fields['block_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Block Type'))
            ->setDescription(t('Type of canvas block.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', self::getBlockTypes())
            ->setDisplayConfigurable('view', TRUE);

        $fields['items'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Items'))
            ->setDescription(t('JSON array of post-it items.'))
            ->setDefaultValue('[]')
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notes'))
            ->setDescription(t('Additional notes for this block.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['ai_suggestions'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('AI Suggestions'))
            ->setDescription(t('JSON array of AI-generated suggestions.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_validated'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Validated'))
            ->setDescription(t('Whether this block is validated by a mentor.'))
            ->setDefaultValue(FALSE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('Last modification time.'));

        return $fields;
    }

}
