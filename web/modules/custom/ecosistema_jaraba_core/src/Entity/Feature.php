<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuración Feature.
 *
 * Una Feature representa una funcionalidad que puede ser habilitada
 * o deshabilitada por Vertical. Permite gestión zero-code desde admin.
 *
 * @ConfigEntityType(
 *   id = "feature",
 *   label = @Translation("Feature"),
 *   label_collection = @Translation("Features"),
 *   label_singular = @Translation("feature"),
 *   label_plural = @Translation("features"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\FeatureListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\FeatureForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\FeatureForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "feature",
 *   admin_permission = "administer features",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "category",
 *     "icon",
 *     "weight",
 *     "status",
 *     "base_cost_monthly",
 *     "unit_cost",
 *     "cost_category",
 *     "usage_metric",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/features",
 *     "add-form" = "/admin/structure/features/add",
 *     "edit-form" = "/admin/structure/features/{feature}/edit",
 *     "delete-form" = "/admin/structure/features/{feature}/delete",
 *   },
 * )
 */
class Feature extends ConfigEntityBase implements FeatureInterface
{

    /**
     * El ID de la feature (machine name).
     *
     * @var string
     */
    protected $id;

    /**
     * El nombre visible de la feature.
     *
     * @var string
     */
    protected $label;

    /**
     * Descripción de la feature.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Categoría para agrupar features.
     *
     * @var string
     */
    protected $category = 'general';

    /**
     * Nombre del icono (opcional).
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Peso para ordenación.
     *
     * @var int
     */
    protected $weight = 0;

    // =========================================================================
    // FINOPS COST FIELDS
    // =========================================================================

    /**
     * Coste base mensual de la feature (€).
     *
     * @var float
     */
    protected $base_cost_monthly = 0.0;

    /**
     * Coste por unidad de uso (€).
     *
     * @var float
     */
    protected $unit_cost = 0.0;

    /**
     * Categoría de coste para agrupación FinOps.
     *
     * @var string
     */
    protected $cost_category = 'compute';

    /**
     * Tipo de métrica de uso (api_calls, rag_queries, etc.).
     *
     * @var string
     */
    protected $usage_metric = '';

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(string $description): FeatureInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return $this->category ?? 'general';
    }

    /**
     * {@inheritdoc}
     */
    public function setCategory(string $category): FeatureInterface
    {
        $this->category = $category;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setIcon(string $icon): FeatureInterface
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWeight(): int
    {
        return $this->weight ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setWeight(int $weight): FeatureInterface
    {
        $this->weight = $weight;
        return $this;
    }

    // =========================================================================
    // FINOPS COST METHODS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function getBaseCostMonthly(): float
    {
        return (float) ($this->base_cost_monthly ?? 0.0);
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseCostMonthly(float $cost): FeatureInterface
    {
        $this->base_cost_monthly = $cost;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnitCost(): float
    {
        return (float) ($this->unit_cost ?? 0.0);
    }

    /**
     * {@inheritdoc}
     */
    public function setUnitCost(float $cost): FeatureInterface
    {
        $this->unit_cost = $cost;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCostCategory(): string
    {
        return $this->cost_category ?? 'compute';
    }

    /**
     * {@inheritdoc}
     */
    public function setCostCategory(string $category): FeatureInterface
    {
        $this->cost_category = $category;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsageMetric(): string
    {
        return $this->usage_metric ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setUsageMetric(string $metric): FeatureInterface
    {
        $this->usage_metric = $metric;
        return $this;
    }

}
