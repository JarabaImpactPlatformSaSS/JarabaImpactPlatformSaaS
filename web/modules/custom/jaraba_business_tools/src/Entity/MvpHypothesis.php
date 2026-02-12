<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the MVP Hypothesis entity for Lean Startup validation.
 *
 * SPEC: 37_Emprendimiento_MVP_Validation_v1
 *
 * @ContentEntityType(
 *   id = "mvp_hypothesis",
 *   label = @Translation("Hipótesis MVP"),
 *   label_collection = @Translation("Hipótesis MVP"),
 *   label_singular = @Translation("hipótesis"),
 *   label_plural = @Translation("hipótesis"),
 *   label_count = @PluralTranslation(
 *     singular = "@count hipótesis",
 *     plural = "@count hipótesis",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_business_tools\MvpHypothesisListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_business_tools\Form\MvpHypothesisForm",
 *       "add" = "Drupal\jaraba_business_tools\Form\MvpHypothesisForm",
 *       "edit" = "Drupal\jaraba_business_tools\Form\MvpHypothesisForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_business_tools\Access\MvpHypothesisAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "mvp_hypothesis",
 *   admin_permission = "administer business model canvas",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "hypothesis",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/mvp-hypotheses",
 *     "add-form" = "/admin/content/mvp-hypotheses/add",
 *     "canonical" = "/admin/content/mvp-hypotheses/{mvp_hypothesis}",
 *     "edit-form" = "/admin/content/mvp-hypotheses/{mvp_hypothesis}/edit",
 *     "delete-form" = "/admin/content/mvp-hypotheses/{mvp_hypothesis}/delete",
 *   },
 *   field_ui_base_route = "entity.mvp_hypothesis.settings",
 * )
 */
class MvpHypothesis extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Experiment types.
     */
    public const TYPE_LANDING_PAGE = 'landing_page';
    public const TYPE_SMOKE_TEST = 'smoke_test';
    public const TYPE_CONCIERGE = 'concierge';
    public const TYPE_WIZARD_OF_OZ = 'wizard_of_oz';
    public const TYPE_INTERVIEW = 'interview';
    public const TYPE_SURVEY = 'survey';
    public const TYPE_PROTOTYPE = 'prototype';

    /**
     * Result statuses.
     */
    public const RESULT_PENDING = 'pending';
    public const RESULT_VALIDATED = 'validated';
    public const RESULT_INVALIDATED = 'invalidated';
    public const RESULT_INCONCLUSIVE = 'inconclusive';

    /**
     * Gets the hypothesis statement.
     */
    public function getHypothesis(): string
    {
        return $this->get('hypothesis')->value ?? '';
    }

    /**
     * Gets the canvas ID this hypothesis is linked to.
     */
    public function getCanvasId(): ?int
    {
        $value = $this->get('canvas_id')->target_id;
        return $value ? (int) $value : NULL;
    }

    /**
     * Gets the experiment type.
     */
    public function getExperimentType(): string
    {
        return $this->get('experiment_type')->value ?? self::TYPE_INTERVIEW;
    }

    /**
     * Gets the success criteria.
     */
    public function getSuccessCriteria(): string
    {
        return $this->get('success_criteria')->value ?? '';
    }

    /**
     * Gets the minimum success threshold.
     */
    public function getMinSuccessThreshold(): ?float
    {
        $value = $this->get('min_success_threshold')->value;
        return $value !== NULL ? (float) $value : NULL;
    }

    /**
     * Gets experiment results data.
     */
    public function getResultsData(): array
    {
        $value = $this->get('results_data')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Sets experiment results data.
     */
    public function setResultsData(array $data): self
    {
        $this->set('results_data', json_encode($data));
        return $this;
    }

    /**
     * Gets the result status.
     */
    public function getResultStatus(): string
    {
        return $this->get('result_status')->value ?? self::RESULT_PENDING;
    }

    /**
     * Sets the result status.
     */
    public function setResultStatus(string $status): self
    {
        $this->set('result_status', $status);
        return $this;
    }

    /**
     * Gets the actual result value.
     */
    public function getActualResult(): ?float
    {
        $value = $this->get('actual_result')->value;
        return $value !== NULL ? (float) $value : NULL;
    }

    /**
     * Gets learnings from the experiment.
     */
    public function getLearnings(): ?string
    {
        return $this->get('learnings')->value;
    }

    /**
     * Gets the pivot decision if any.
     */
    public function getPivotDecision(): ?string
    {
        return $this->get('pivot_decision')->value;
    }

    /**
     * Checks if the hypothesis was validated.
     */
    public function isValidated(): bool
    {
        return $this->getResultStatus() === self::RESULT_VALIDATED;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['canvas_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Canvas'))
            ->setDescription(t('Linked Business Model Canvas.'))
            ->setSetting('target_type', 'business_model_canvas')
            ->setDisplayConfigurable('form', TRUE);

        $fields['hypothesis'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Hypothesis'))
            ->setDescription(t('The hypothesis statement to validate.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['target_segment'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Target Segment'))
            ->setDescription(t('Customer segment this hypothesis applies to.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['experiment_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Experiment Type'))
            ->setDescription(t('Type of validation experiment.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_LANDING_PAGE => t('Landing Page Test'),
                self::TYPE_SMOKE_TEST => t('Smoke Test'),
                self::TYPE_CONCIERGE => t('Concierge MVP'),
                self::TYPE_WIZARD_OF_OZ => t('Wizard of Oz'),
                self::TYPE_INTERVIEW => t('Customer Interview'),
                self::TYPE_SURVEY => t('Survey'),
                self::TYPE_PROTOTYPE => t('Prototype Test'),
            ])
            ->setDefaultValue(self::TYPE_INTERVIEW)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['success_criteria'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Success Criteria'))
            ->setDescription(t('What defines success for this experiment.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['min_success_threshold'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Minimum Success Threshold'))
            ->setDescription(t('Numeric threshold for success (e.g., 30 for 30% conversion).'))
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['sample_size'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sample Size'))
            ->setDescription(t('Number of participants/data points needed.'))
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Start Date'))
            ->setDescription(t('Experiment start date.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['end_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('End Date'))
            ->setDescription(t('Experiment end date.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['results_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Results Data'))
            ->setDescription(t('JSON with experiment results data.'));

        $fields['actual_result'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Actual Result'))
            ->setDescription(t('Actual numeric result of the experiment.'))
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('view', TRUE);

        $fields['result_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Result Status'))
            ->setDescription(t('Validation result.'))
            ->setSetting('allowed_values', [
                self::RESULT_PENDING => t('Pending'),
                self::RESULT_VALIDATED => t('Validated'),
                self::RESULT_INVALIDATED => t('Invalidated'),
                self::RESULT_INCONCLUSIVE => t('Inconclusive'),
            ])
            ->setDefaultValue(self::RESULT_PENDING)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['learnings'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Learnings'))
            ->setDescription(t('Key learnings from this experiment.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['pivot_decision'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Pivot Decision'))
            ->setDescription(t('Decision made based on results.'))
            ->setSetting('allowed_values', [
                'persevere' => t('Persevere - Continue current direction'),
                'pivot' => t('Pivot - Change direction'),
                'kill' => t('Kill - Abandon this path'),
                'iterate' => t('Iterate - Small adjustments'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('Creation timestamp.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('Last modification timestamp.'));

        return $fields;
    }

}
