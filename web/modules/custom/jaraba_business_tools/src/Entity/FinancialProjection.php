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
 * Defines the Financial Projection entity.
 *
 * SPEC: 38_Emprendimiento_Financial_Projections_v1
 *
 * @ContentEntityType(
 *   id = "financial_projection",
 *   label = @Translation("Proyección Financiera"),
 *   label_collection = @Translation("Proyecciones Financieras"),
 *   label_singular = @Translation("proyección"),
 *   label_plural = @Translation("proyecciones"),
 *   label_count = @PluralTranslation(
 *     singular = "@count proyección",
 *     plural = "@count proyecciones",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_business_tools\FinancialProjectionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_business_tools\Form\FinancialProjectionForm",
 *       "add" = "Drupal\jaraba_business_tools\Form\FinancialProjectionForm",
 *       "edit" = "Drupal\jaraba_business_tools\Form\FinancialProjectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_business_tools\Access\FinancialProjectionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "financial_projection",
 *   admin_permission = "administer business model canvas",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/financial-projections",
 *     "add-form" = "/admin/content/financial-projections/add",
 *     "canonical" = "/admin/content/financial-projections/{financial_projection}",
 *     "edit-form" = "/admin/content/financial-projections/{financial_projection}/edit",
 *     "delete-form" = "/admin/content/financial-projections/{financial_projection}/delete",
 *   },
 *   field_ui_base_route = "entity.financial_projection.settings",
 * )
 */
class FinancialProjection extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Scenario types.
     */
    public const SCENARIO_PESSIMISTIC = 'pessimistic';
    public const SCENARIO_REALISTIC = 'realistic';
    public const SCENARIO_OPTIMISTIC = 'optimistic';

    /**
     * Gets the projection title.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Gets the linked canvas ID.
     */
    public function getCanvasId(): ?int
    {
        $value = $this->get('canvas_id')->target_id;
        return $value ? (int) $value : NULL;
    }

    /**
     * Gets the projection period in months.
     */
    public function getPeriodMonths(): int
    {
        return (int) $this->get('period_months')->value;
    }

    /**
     * Gets monthly revenue projections.
     */
    public function getRevenueProjections(): array
    {
        $value = $this->get('revenue_projections')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Sets monthly revenue projections.
     */
    public function setRevenueProjections(array $projections): self
    {
        $this->set('revenue_projections', json_encode($projections));
        return $this;
    }

    /**
     * Gets monthly cost projections.
     */
    public function getCostProjections(): array
    {
        $value = $this->get('cost_projections')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Sets monthly cost projections.
     */
    public function setCostProjections(array $projections): self
    {
        $this->set('cost_projections', json_encode($projections));
        return $this;
    }

    /**
     * Gets fixed costs breakdown.
     */
    public function getFixedCosts(): array
    {
        $value = $this->get('fixed_costs')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Gets variable costs as percentage of revenue.
     */
    public function getVariableCostPercentage(): float
    {
        return (float) $this->get('variable_cost_percentage')->value;
    }

    /**
     * Gets the initial investment required.
     */
    public function getInitialInvestment(): float
    {
        return (float) $this->get('initial_investment')->value;
    }

    /**
     * Gets the current scenario type.
     */
    public function getScenario(): string
    {
        return $this->get('scenario')->value ?? self::SCENARIO_REALISTIC;
    }

    /**
     * Gets the break-even month (calculated).
     */
    public function getBreakEvenMonth(): ?int
    {
        $value = $this->get('break_even_month')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * Sets the break-even month.
     */
    public function setBreakEvenMonth(?int $month): self
    {
        $this->set('break_even_month', $month);
        return $this;
    }

    /**
     * Gets the monthly cash flow.
     */
    public function getCashFlowProjections(): array
    {
        $revenues = $this->getRevenueProjections();
        $costs = $this->getCostProjections();
        $cashFlow = [];

        $runningTotal = -$this->getInitialInvestment();

        for ($i = 0; $i < count($revenues); $i++) {
            $monthRevenue = $revenues[$i] ?? 0;
            $monthCost = $costs[$i] ?? 0;
            $monthProfit = $monthRevenue - $monthCost;
            $runningTotal += $monthProfit;

            $cashFlow[] = [
                'month' => $i + 1,
                'revenue' => $monthRevenue,
                'costs' => $monthCost,
                'profit' => $monthProfit,
                'cumulative' => $runningTotal,
            ];
        }

        return $cashFlow;
    }

    /**
     * Calculates total projected revenue.
     */
    public function getTotalRevenue(): float
    {
        return array_sum($this->getRevenueProjections());
    }

    /**
     * Calculates total projected costs.
     */
    public function getTotalCosts(): float
    {
        return array_sum($this->getCostProjections());
    }

    /**
     * Calculates net profit.
     */
    public function getNetProfit(): float
    {
        return $this->getTotalRevenue() - $this->getTotalCosts() - $this->getInitialInvestment();
    }

    /**
     * Calculates ROI percentage.
     */
    public function getRoi(): float
    {
        $investment = $this->getInitialInvestment();
        if ($investment <= 0) {
            return 0;
        }
        return ($this->getNetProfit() / $investment) * 100;
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
            ->setDescription(t('Projection title.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['canvas_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Canvas'))
            ->setDescription(t('Linked Business Model Canvas.'))
            ->setSetting('target_type', 'business_model_canvas')
            ->setDisplayConfigurable('form', TRUE);

        $fields['scenario'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Scenario'))
            ->setDescription(t('Projection scenario type.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::SCENARIO_PESSIMISTIC => t('Pessimistic'),
                self::SCENARIO_REALISTIC => t('Realistic'),
                self::SCENARIO_OPTIMISTIC => t('Optimistic'),
            ])
            ->setDefaultValue(self::SCENARIO_REALISTIC)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['period_months'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Projection Period (Months)'))
            ->setDescription(t('Number of months to project.'))
            ->setRequired(TRUE)
            ->setDefaultValue(12)
            ->setSetting('min', 1)
            ->setSetting('max', 60)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['initial_investment'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Initial Investment'))
            ->setDescription(t('Initial investment required (€).'))
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['revenue_projections'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Revenue Projections'))
            ->setDescription(t('JSON array of monthly revenue projections.'))
            ->setDefaultValue('[]');

        $fields['cost_projections'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Cost Projections'))
            ->setDescription(t('JSON array of monthly cost projections.'))
            ->setDefaultValue('[]');

        $fields['fixed_costs'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Fixed Costs'))
            ->setDescription(t('JSON breakdown of fixed costs.'))
            ->setDefaultValue('{}');

        $fields['variable_cost_percentage'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Variable Cost %'))
            ->setDescription(t('Variable costs as percentage of revenue.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['break_even_month'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Break-Even Month'))
            ->setDescription(t('Calculated month when break-even is reached.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['assumptions'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Assumptions'))
            ->setDescription(t('Key assumptions for this projection.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notes'))
            ->setDescription(t('Additional notes.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'));

        return $fields;
    }

}
