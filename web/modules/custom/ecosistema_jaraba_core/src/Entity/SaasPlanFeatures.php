<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion SaasPlanFeatures.
 *
 * Almacena las features y limites configurables por combinacion
 * vertical+tier. Es la fuente de verdad para que puede hacer
 * un tenant segun su vertical y plan.
 *
 * Convencion de ID: {vertical}_{tier} o _default_{tier}
 * Ejemplo: empleabilidad_starter, _default_professional
 *
 * @ConfigEntityType(
 *   id = "saas_plan_features",
 *   label = @Translation("SaaS Plan Features"),
 *   label_collection = @Translation("SaaS Plan Features"),
 *   label_singular = @Translation("SaaS plan features config"),
 *   label_plural = @Translation("SaaS plan features configs"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\SaasPlanFeaturesListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanFeaturesForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanFeaturesForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "plan_features",
 *   admin_permission = "administer saas plans",
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
 *     "tier",
 *     "features",
 *     "limits",
 *     "description",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/jaraba/plan-features",
 *     "add-form" = "/admin/config/jaraba/plan-features/add",
 *     "edit-form" = "/admin/config/jaraba/plan-features/{saas_plan_features}/edit",
 *     "delete-form" = "/admin/config/jaraba/plan-features/{saas_plan_features}/delete",
 *   },
 * )
 */
class SaasPlanFeatures extends ConfigEntityBase {

  /**
   * El ID de la configuracion (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * Nombre legible.
   *
   * @var string
   */
  protected $label;

  /**
   * Machine name de la vertical (_default para fallback global).
   *
   * @var string
   */
  protected $vertical = '_default';

  /**
   * Machine name del tier (starter, professional, enterprise).
   *
   * @var string
   */
  protected $tier = '';

  /**
   * Lista de features habilitadas para esta combinacion.
   *
   * @var array
   */
  protected $features = [];

  /**
   * Limites numericos por recurso (key → int).
   *
   * @var array
   */
  protected $limits = [];

  /**
   * Descripcion para administradores.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Obtiene la vertical.
   *
   * @return string
   *   La vertical (_default, empleabilidad, etc.).
   */
  public function getVertical(): string {
    return $this->vertical ?? '_default';
  }

  /**
   * Establece la vertical.
   *
   * @param string $vertical
   *   La vertical.
   *
   * @return $this
   */
  public function setVertical(string $vertical): self {
    $this->vertical = $vertical;
    return $this;
  }

  /**
   * Obtiene el tier.
   *
   * @return string
   *   El tier.
   */
  public function getTier(): string {
    return $this->tier ?? '';
  }

  /**
   * Establece el tier.
   *
   * @param string $tier
   *   El tier.
   *
   * @return $this
   */
  public function setTier(string $tier): self {
    $this->tier = $tier;
    return $this;
  }

  /**
   * Obtiene las features habilitadas.
   *
   * @return array
   *   Lista de features (strings).
   */
  public function getFeatures(): array {
    return $this->features ?? [];
  }

  /**
   * Establece las features.
   *
   * @param array $features
   *   Lista de features.
   *
   * @return $this
   */
  public function setFeatures(array $features): self {
    $this->features = $features;
    return $this;
  }

  /**
   * Obtiene los limites numericos.
   *
   * @return array
   *   Map de key => int.
   */
  public function getLimits(): array {
    return $this->limits ?? [];
  }

  /**
   * Obtiene un limite especifico.
   *
   * @param string $key
   *   Clave del limite.
   * @param int $default
   *   Valor por defecto si no existe.
   *
   * @return int
   *   El valor del limite.
   */
  public function getLimit(string $key, int $default = 0): int {
    return (int) ($this->limits[$key] ?? $default);
  }

  /**
   * Establece los limites.
   *
   * @param array $limits
   *   Map de key => int.
   *
   * @return $this
   */
  public function setLimits(array $limits): self {
    $this->limits = $limits;
    return $this;
  }

  /**
   * Verifica si una feature esta habilitada.
   *
   * @param string $feature
   *   Machine name de la feature.
   *
   * @return bool
   *   TRUE si esta habilitada.
   */
  public function hasFeature(string $feature): bool {
    return in_array($feature, $this->getFeatures(), TRUE);
  }

  /**
   * Obtiene la descripcion.
   *
   * @return string
   *   La descripcion.
   */
  public function getDescription(): string {
    return $this->description ?? '';
  }

  /**
   * Establece la descripcion.
   *
   * @param string $description
   *   La descripcion.
   *
   * @return $this
   */
  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

}
