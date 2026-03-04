<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion VerticalBrandConfig.
 *
 * Gestiona sub-marca de cada vertical: nombre publico, tagline,
 * colores, iconos, SEO. Soporta la estrategia "Submarinas con Periscopio"
 * con 4 niveles de revelacion.
 *
 * ID = vertical canonico (ej: empleabilidad, emprendimiento).
 *
 * @ConfigEntityType(
 *   id = "vertical_brand",
 *   label = @Translation("Vertical Brand"),
 *   label_collection = @Translation("Vertical Brands"),
 *   label_singular = @Translation("vertical brand"),
 *   label_plural = @Translation("vertical brands"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\ListBuilder\VerticalBrandListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\VerticalBrandForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\VerticalBrandForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "vertical_brand",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "vertical",
 *     "public_name",
 *     "tagline",
 *     "description",
 *     "icon_category",
 *     "icon_name",
 *     "primary_color",
 *     "secondary_color",
 *     "hero_image_url",
 *     "og_image_url",
 *     "seo_title_template",
 *     "seo_description",
 *     "schema_org_type",
 *     "revelation_level",
 *     "landing_route",
 *     "enabled",
 *   },
 *   links = {
 *     "collection" = "/admin/config/ecosistema-jaraba/vertical-brands",
 *     "add-form" = "/admin/config/ecosistema-jaraba/vertical-brands/add",
 *     "edit-form" = "/admin/config/ecosistema-jaraba/vertical-brands/{vertical_brand}/edit",
 *     "delete-form" = "/admin/config/ecosistema-jaraba/vertical-brands/{vertical_brand}/delete",
 *   },
 * )
 */
class VerticalBrandConfig extends ConfigEntityBase {

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $vertical = '';

  /**
   * @var string
   */
  protected $public_name = '';

  /**
   * @var string
   */
  protected $tagline = '';

  /**
   * @var string
   */
  protected $description = '';

  /**
   * @var string
   */
  protected $icon_category = 'vertical';

  /**
   * @var string
   */
  protected $icon_name = '';

  /**
   * @var string
   */
  protected $primary_color = '';

  /**
   * @var string
   */
  protected $secondary_color = '';

  /**
   * @var string
   */
  protected $hero_image_url = '';

  /**
   * @var string
   */
  protected $og_image_url = '';

  /**
   * @var string
   */
  protected $seo_title_template = '{page_title} | Jaraba';

  /**
   * @var string
   */
  protected $seo_description = '';

  /**
   * @var string
   */
  protected $schema_org_type = 'Organization';

  /**
   * @var string
   */
  protected $revelation_level = 'landing';

  /**
   * @var string
   */
  protected $landing_route = '';

  /**
   * @var bool
   */
  protected $enabled = TRUE;

  public function getVertical(): string {
    return $this->vertical;
  }

  public function getPublicName(): string {
    return $this->public_name !== '' ? $this->public_name : $this->label;
  }

  public function getTagline(): string {
    return $this->tagline;
  }

  public function getDescription(): string {
    return $this->description;
  }

  public function getIconCategory(): string {
    return $this->icon_category;
  }

  public function getIconName(): string {
    return $this->icon_name;
  }

  public function getPrimaryColor(): string {
    return $this->primary_color;
  }

  public function getSecondaryColor(): string {
    return $this->secondary_color;
  }

  public function getHeroImageUrl(): string {
    return $this->hero_image_url;
  }

  public function getOgImageUrl(): string {
    return $this->og_image_url;
  }

  public function getSeoTitleTemplate(): string {
    return $this->seo_title_template;
  }

  public function getSeoDescription(): string {
    return $this->seo_description;
  }

  public function getSchemaOrgType(): string {
    return $this->schema_org_type;
  }

  public function getRevelationLevel(): string {
    return $this->revelation_level;
  }

  public function getLandingRoute(): string {
    return $this->landing_route;
  }

  public function isEnabled(): bool {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('module', 'ecosistema_jaraba_core');
    return $this;
  }

}
