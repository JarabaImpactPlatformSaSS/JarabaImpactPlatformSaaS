<?php

namespace Drupal\jaraba_page_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\jaraba_page_builder\PageTemplateInterface;

/**
 * Define la entidad de configuración para Plantillas de Página.
 *
 * PROPÓSITO:
 * Las plantillas son predefinidas por el sistema y determinan la estructura
 * de las páginas que los tenants pueden crear. NO son editables por tenants.
 *
 * DIRECTRIZ:
 * Es una Config Entity porque:
 * - Son definidas por el sistema, no por usuarios
 * - Se exportan con config sync
 * - Definen la estructura (schema) de los datos
 *
 * @ConfigEntityType(
 *   id = "page_template",
 *   label = @Translation("Plantilla de Página"),
 *   label_collection = @Translation("Plantillas de Página"),
 *   label_singular = @Translation("plantilla de página"),
 *   label_plural = @Translation("plantillas de página"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_page_builder\PageTemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_page_builder\Form\PageTemplateForm",
 *       "edit" = "Drupal\jaraba_page_builder\Form\PageTemplateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "template",
 *   admin_permission = "administer page templates",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "category",
 *     "twig_template",
 *     "fields_schema",
 *     "plans_required",
 *     "is_premium",
 *     "preview_image",
 *     "preview_data",
 *     "weight",
 *   },
 *   links = {
 *     "collection" = "/admin/config/jaraba/page-builder/templates",
 *     "add-form" = "/admin/config/jaraba/page-builder/templates/add",
 *     "edit-form" = "/admin/config/jaraba/page-builder/templates/{page_template}/edit",
 *     "delete-form" = "/admin/config/jaraba/page-builder/templates/{page_template}/delete",
 *   },
 * )
 */
class PageTemplate extends ConfigEntityBase implements PageTemplateInterface
{

    /**
     * El ID de la plantilla.
     *
     * @var string
     */
    protected $id;

    /**
     * El nombre visible de la plantilla.
     *
     * @var string
     */
    protected $label;

    /**
     * Descripción de la plantilla.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Categoría de la plantilla.
     *
     * @var string
     */
    protected $category = 'landing';

    /**
     * Ruta al template Twig.
     *
     * @var string
     */
    protected $twig_template = '';

    /**
     * JSON Schema para los campos configurables.
     *
     * @var array
     */
    protected $fields_schema = [];

    /**
     * Planes requeridos para usar esta plantilla.
     *
     * @var array
     */
    protected $plans_required = ['starter', 'professional', 'enterprise'];

    /**
     * Si es una plantilla premium (Aceternity/Magic UI).
     *
     * @var bool
     */
    protected $is_premium = FALSE;

    /**
     * Ruta a la imagen de preview.
     *
     * @var string
     */
    protected $preview_image = '';

    /**
     * Datos curados para preview que replican las miniaturas PNG.
     *
     * @var array
     */
    protected $preview_data = [];

    /**
     * Peso para ordenar.
     *
     * @var int
     */
    protected $weight = 0;

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
    public function getCategory(): string
    {
        return $this->category ?? 'landing';
    }

    /**
     * {@inheritdoc}
     */
    public function getTwigTemplate(): string
    {
        return $this->twig_template ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsSchema(): array
    {
        return $this->fields_schema ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPlansRequired(): array
    {
        return $this->plans_required ?? ['starter', 'professional', 'enterprise'];
    }

    /**
     * {@inheritdoc}
     */
    public function isPremium(): bool
    {
        return (bool) ($this->is_premium ?? FALSE);
    }

    /**
     * {@inheritdoc}
     *
     * Implementa fallback automático: si no hay preview_image configurado,
     * intenta detectar el PNG por convención de nombre (ID del template).
     */
    public function getPreviewImage(): string
    {
        // Prioridad 1: Valor explícito en configuración
        if (!empty($this->preview_image)) {
            return $this->preview_image;
        }

        // Prioridad 2: Auto-detección por convención de nombre
        // El ID del template (ej: accordion_content) se convierte a
        // nombre de archivo (ej: accordion-content.png)
        $basePath = '/modules/custom/jaraba_page_builder/images/previews/';
        $filename = str_replace('_', '-', $this->id()) . '.png';
        $fullPath = DRUPAL_ROOT . $basePath . $filename;

        if (file_exists($fullPath)) {
            return $basePath . $filename;
        }

        return '';
    }

    /**
     * Alias de getPreviewImage para compatibilidad con templates.
     *
     * @return string
     *   Ruta a la imagen de thumbnail.
     */
    public function getThumbnail(): string
    {
        return $this->getPreviewImage();
    }

    /**
     * Verifica si el plan dado tiene acceso a esta plantilla.
     *
     * @param string $plan_id
     *   El ID del plan a verificar.
     *
     * @return bool
     *   TRUE si el plan tiene acceso.
     */
    public function isAvailableForPlan(string $plan_id): bool
    {
        $plans = $this->getPlansRequired();
        return in_array($plan_id, $plans, TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function getPreviewData(): array
    {
        return $this->preview_data ?? [];
    }

}
