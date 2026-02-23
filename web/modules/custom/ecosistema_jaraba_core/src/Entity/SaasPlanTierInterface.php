<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface para la entidad SaasPlanTier.
 *
 * Define el contrato para los tiers de planes SaaS configurables.
 * Cada tier representa un nivel de plan (starter, professional, enterprise)
 * con sus aliases, precios Stripe y orden de jerarquia.
 */
interface SaasPlanTierInterface extends ConfigEntityInterface {

  /**
   * Obtiene el machine name del tier.
   *
   * @return string
   *   El tier (starter, professional, enterprise).
   */
  public function getTierKey(): string;

  /**
   * Establece el machine name del tier.
   *
   * @param string $tier_key
   *   El tier.
   *
   * @return $this
   */
  public function setTierKey(string $tier_key): SaasPlanTierInterface;

  /**
   * Obtiene los aliases del tier para normalizacion.
   *
   * @return array
   *   Lista de aliases (ej: ['pro', 'profesional', 'professional']).
   */
  public function getAliases(): array;

  /**
   * Establece los aliases del tier.
   *
   * @param array $aliases
   *   Lista de aliases.
   *
   * @return $this
   */
  public function setAliases(array $aliases): SaasPlanTierInterface;

  /**
   * Obtiene el Stripe Price ID mensual.
   *
   * @return string
   *   El Stripe Price ID mensual.
   */
  public function getStripePriceMonthly(): string;

  /**
   * Establece el Stripe Price ID mensual.
   *
   * @param string $stripe_price_monthly
   *   El Stripe Price ID mensual.
   *
   * @return $this
   */
  public function setStripePriceMonthly(string $stripe_price_monthly): SaasPlanTierInterface;

  /**
   * Obtiene el Stripe Price ID anual.
   *
   * @return string
   *   El Stripe Price ID anual.
   */
  public function getStripePriceYearly(): string;

  /**
   * Establece el Stripe Price ID anual.
   *
   * @param string $stripe_price_yearly
   *   El Stripe Price ID anual.
   *
   * @return $this
   */
  public function setStripePriceYearly(string $stripe_price_yearly): SaasPlanTierInterface;

  /**
   * Obtiene el peso para ordenacion jerarquica.
   *
   * @return int
   *   El peso (menor = tier inferior).
   */
  public function getWeight(): int;

  /**
   * Establece el peso para ordenacion.
   *
   * @param int $weight
   *   El peso.
   *
   * @return $this
   */
  public function setWeight(int $weight): SaasPlanTierInterface;

  /**
   * Obtiene la descripcion del tier.
   *
   * @return string
   *   La descripcion.
   */
  public function getDescription(): string;

  /**
   * Establece la descripcion del tier.
   *
   * @param string $description
   *   La descripcion.
   *
   * @return $this
   */
  public function setDescription(string $description): SaasPlanTierInterface;

}
