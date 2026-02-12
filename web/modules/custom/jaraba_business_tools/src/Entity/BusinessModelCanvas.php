<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Business Model Canvas entity.
 *
 * SPEC: 36_Emprendimiento_Business_Model_Canvas_v1
 *
 * @ContentEntityType(
 *   id = "business_model_canvas",
 *   label = @Translation("Canvas de Modelo de Negocio"),
 *   label_collection = @Translation("Canvas de Modelo de Negocio"),
 *   label_singular = @Translation("canvas"),
 *   label_plural = @Translation("canvas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count canvas",
 *     plural = "@count canvas",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\jaraba_business_tools\BusinessModelCanvasViewBuilder",
 *     "list_builder" = "Drupal\jaraba_business_tools\BusinessModelCanvasListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_business_tools\Form\BusinessModelCanvasForm",
 *       "add" = "Drupal\jaraba_business_tools\Form\BusinessModelCanvasForm",
 *       "edit" = "Drupal\jaraba_business_tools\Form\BusinessModelCanvasForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_business_tools\Access\BusinessModelCanvasAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "business_model_canvas",
 *   admin_permission = "administer business model canvas",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/business-canvas",
 *     "add-form" = "/admin/content/business-canvas/add",
 *     "canonical" = "/admin/content/business-canvas/{business_model_canvas}",
 *     "edit-form" = "/admin/content/business-canvas/{business_model_canvas}/edit",
 *     "delete-form" = "/admin/content/business-canvas/{business_model_canvas}/delete",
 *   },
 *   field_ui_base_route = "entity.business_model_canvas.settings",
 * )
 */
class BusinessModelCanvas extends ContentEntityBase implements BusinessModelCanvasInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        $value = $this->get('title')->value ?? '';
        return (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle(string $title): self
    {
        $this->set('title', $title);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): ?string
    {
        return $this->get('description')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getSector(): string
    {
        return $this->get('sector')->value ?? 'otros';
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessStage(): string
    {
        return $this->get('business_stage')->value ?? 'idea';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): int
    {
        return (int) $this->get('version')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementVersion(): self
    {
        $this->set('version', $this->getVersion() + 1);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompletenessScore(): float
    {
        return (float) $this->get('completeness_score')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCoherenceScore(): ?float
    {
        $value = $this->get('coherence_score')->value;
        return $value !== NULL ? (float) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function setCoherenceScore(float $score): self
    {
        $this->set('coherence_score', $score);
        $this->set('last_ai_analysis', date('Y-m-d\TH:i:s'));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? 'draft';
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus(string $status): self
    {
        $this->set('status', $status);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isTemplate(): bool
    {
        return (bool) $this->get('is_template')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedWith(): array
    {
        $value = $this->get('shared_with')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function addCollaborator(int $uid): self
    {
        $shared = $this->getSharedWith();
        if (!in_array($uid, $shared)) {
            $shared[] = $uid;
            $this->set('shared_with', json_encode($shared));
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDiagnosticId(): ?int
    {
        $value = $this->get('business_diagnostic_id')->value;
        return $value ? (int) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Title'))
            ->setDescription(t('The name of the business/project.'))
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

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Description'))
            ->setDescription(t('Brief description of the business.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['sector'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Sector'))
            ->setDescription(t('Business sector.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'comercio' => t('Comercio'),
                'servicios' => t('Servicios'),
                'hosteleria' => t('Hostelería'),
                'agro' => t('Agroalimentario'),
                'tech' => t('Tecnología'),
                'industria' => t('Industria'),
                'otros' => t('Otros'),
            ])
            ->setDefaultValue('otros')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['business_stage'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Business Stage'))
            ->setDescription(t('Current stage of the business.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'idea' => t('Idea'),
                'validacion' => t('Validación'),
                'crecimiento' => t('Crecimiento'),
                'escalado' => t('Escalado'),
            ])
            ->setDefaultValue('idea')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Program'))
            ->setDescription(t('Associated program for template visibility.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 11,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['business_diagnostic_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Source Diagnostic'))
            ->setDescription(t('Linked business diagnostic.'))
            ->setSetting('target_type', 'business_diagnostic')
            ->setDisplayConfigurable('form', TRUE);

        $fields['version'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Version'))
            ->setDescription(t('Current version number.'))
            ->setDefaultValue(1)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_template'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Is Template'))
            ->setDescription(t('Whether this canvas is a reusable template.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['template_source_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Template Source'))
            ->setDescription(t('Template this canvas was derived from.'))
            ->setSetting('target_type', 'business_model_canvas')
            ->setDisplayConfigurable('form', TRUE);

        $fields['completeness_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Completeness Score'))
            ->setDescription(t('Percentage of canvas completion (0-100).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['coherence_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Coherence Score'))
            ->setDescription(t('AI-calculated coherence score (0-100).'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('view', TRUE);

        $fields['last_ai_analysis'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Last AI Analysis'))
            ->setDescription(t('Timestamp of last AI validation.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['shared_with'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Shared With'))
            ->setDescription(t('JSON array of collaborator UIDs.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Status'))
            ->setDescription(t('Canvas status.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'draft' => t('Draft'),
                'active' => t('Active'),
                'archived' => t('Archived'),
            ])
            ->setDefaultValue('draft')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time when the canvas was created.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time when the canvas was last edited.'))
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

}
