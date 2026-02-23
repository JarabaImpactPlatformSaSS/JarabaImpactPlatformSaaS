<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion SaasPlanTier.
 *
 * Almacena los tiers de planes SaaS (starter, professional, enterprise)
 * con sus aliases para normalizacion, Stripe Price IDs y jerarquia.
 * Es la fuente de verdad para la resolucion de nombres de plan.
 *
 * Convencion de ID: {tier_key}
 * Ejemplo: starter, professional, enterprise
 *
 * @ConfigEntityType(
 *   id = "saas_plan_tier",
 *   label = @Translation("SaaS Plan Tier"),
 *   label_collection = @Translation("SaaS Plan Tiers"),
 *   label_singular = @Translation("SaaS plan tier"),
 *   label_plural = @Translation("SaaS plan tiers"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\SaasPlanTierListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanTierForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanTierForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "plan_tier",
 *   admin_permission = "administer saas plans",
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
 *     "tier_key",
 *     "aliases",
 *     "stripe_price_monthly",
 *     "stripe_price_yearly",
 *     "description",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/jaraba/plan-tiers",
 *     "add-form" = "/admin/config/jaraba/plan-tiers/add",
 *     "edit-form" = "/admin/config/jaraba/plan-tiers/{saas_plan_tier}/edit",
 *     "delete-form" = "/admin/config/jaraba/plan-tiers/{saas_plan_tier}/delete",
 *   },
 * )
 */
class SaasPlanTier extends ConfigEntityBase implements SaasPlanTierInterface {

  /**
   * El ID del tier (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * Nombre legible del tier.
   *
   * @var string
   */
  protected $label;

  /**
   * Clave canonica del tier.
   *
   * @var string
   */
  protected $tier_key = '';

  /**
   * Aliases para normalizacion de nombres de plan.
   *
   * @var array
   */
  protected $aliases = [];

  /**
   * Stripe Price ID mensual.
   *
   * @var string
   */
  protected $stripe_price_monthly = '';

  /**
   * Stripe Price ID anual.
   *
   * @var string
   */
  protected $stripe_price_yearly = '';

  /**
   * Descripcion del tier para administradores.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Peso para ordenacion jerarquica.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getTierKey(): string {
    return $this->tier_key ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTierKey(string $tier_key): SaasPlanTierInterface {
    $this->tier_key = $tier_key;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliases(): array {
    return $this->aliases ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function setAliases(array $aliases): SaasPlanTierInterface {
    $this->aliases = $aliases;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStripePriceMonthly(): string {
    return $this->stripe_price_monthly ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setStripePriceMonthly(string $stripe_price_monthly): SaasPlanTierInterface {
    $this->stripe_price_monthly = $stripe_price_monthly;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStripePriceYearly(): string {
    return $this->stripe_price_yearly ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setStripePriceYearly(string $stripe_price_yearly): SaasPlanTierInterface {
    $this->stripe_price_yearly = $stripe_price_yearly;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return $this->weight ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight(int $weight): SaasPlanTierInterface {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description): SaasPlanTierInterface {
    $this->description = $description;
    return $this;
  }

}
