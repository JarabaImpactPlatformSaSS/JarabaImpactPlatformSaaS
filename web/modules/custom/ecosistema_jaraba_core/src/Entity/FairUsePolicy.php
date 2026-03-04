<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion FairUsePolicy.
 *
 * Almacena las politicas de Fair Use por tier: umbrales de alerta,
 * acciones de enforcement, precios de overage, tolerancia burst y
 * periodo de gracia. Protege margen Enterprise sin valores hardcoded.
 *
 * Convencion de ID: '_global' (fallback) o '{tier}' (override por tier).
 *
 * @ConfigEntityType(
 *   id = "fair_use_policy",
 *   label = @Translation("Fair Use Policy"),
 *   label_collection = @Translation("Fair Use Policies"),
 *   label_singular = @Translation("fair use policy"),
 *   label_plural = @Translation("fair use policies"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\FairUsePolicyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\FairUsePolicyForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\FairUsePolicyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "fair_use_policy",
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
 *     "tier",
 *     "warning_thresholds",
 *     "enforcement_actions",
 *     "overage_unit_prices",
 *     "burst_tolerance_pct",
 *     "grace_period_hours",
 *     "description",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/jaraba/fair-use-policies",
 *     "add-form" = "/admin/config/jaraba/fair-use-policies/add",
 *     "edit-form" = "/admin/config/jaraba/fair-use-policies/{fair_use_policy}/edit",
 *     "delete-form" = "/admin/config/jaraba/fair-use-policies/{fair_use_policy}/delete",
 *   },
 * )
 */
class FairUsePolicy extends ConfigEntityBase {

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
   * Tier al que aplica ('_global' para fallback).
   *
   * @var string
   */
  protected $tier = '_global';

  /**
   * Umbrales de alerta como porcentajes (ej: [70, 85, 95]).
   *
   * @var array
   */
  protected $warning_thresholds = [];

  /**
   * Acciones de enforcement por recurso y nivel.
   *
   * Estructura: recurso => { warning => accion, critical => accion, exceeded => accion }
   * Acciones: warn, throttle, soft_block, hard_block.
   *
   * @var array
   */
  protected $enforcement_actions = [];

  /**
   * Precios de overage por metrica (EUR).
   *
   * Estructura: metrica => precio float.
   *
   * @var array
   */
  protected $overage_unit_prices = [];

  /**
   * Porcentaje de tolerancia burst sobre limite.
   *
   * @var int
   */
  protected $burst_tolerance_pct = 0;

  /**
   * Horas de gracia tras primer breach.
   *
   * @var int
   */
  protected $grace_period_hours = 6;

  /**
   * Descripcion para administradores.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Obtiene el tier.
   */
  public function getTier(): string {
    return $this->tier ?? '_global';
  }

  /**
   * Establece el tier.
   */
  public function setTier(string $tier): self {
    $this->tier = $tier;
    return $this;
  }

  /**
   * Obtiene los umbrales de alerta.
   *
   * @return array
   *   Lista de porcentajes ordenados ascendente.
   */
  public function getWarningThresholds(): array {
    $thresholds = $this->warning_thresholds ?? [];
    sort($thresholds);
    return $thresholds;
  }

  /**
   * Establece los umbrales de alerta.
   */
  public function setWarningThresholds(array $thresholds): self {
    $this->warning_thresholds = array_map('intval', $thresholds);
    return $this;
  }

  /**
   * Obtiene las acciones de enforcement.
   *
   * @return array
   *   Map de recurso => {warning => accion, critical => accion, exceeded => accion}.
   */
  public function getEnforcementActions(): array {
    return $this->enforcement_actions ?? [];
  }

  /**
   * Obtiene la accion de enforcement para un recurso y nivel.
   *
   * @param string $resource
   *   El recurso (ej: ai_queries, copilot_uses_per_month).
   * @param string $level
   *   El nivel (warning, critical, exceeded).
   *
   * @return string
   *   La accion (warn, throttle, soft_block, hard_block). Default: warn.
   */
  public function getEnforcementAction(string $resource, string $level): string {
    return $this->enforcement_actions[$resource][$level]
      ?? $this->enforcement_actions['_default'][$level]
      ?? 'warn';
  }

  /**
   * Establece las acciones de enforcement.
   */
  public function setEnforcementActions(array $actions): self {
    $this->enforcement_actions = $actions;
    return $this;
  }

  /**
   * Obtiene los precios de overage.
   *
   * @return array
   *   Map de metrica => precio EUR.
   */
  public function getOverageUnitPrices(): array {
    return $this->overage_unit_prices ?? [];
  }

  /**
   * Obtiene el precio de overage para una metrica.
   *
   * @param string $metric
   *   La metrica.
   * @param float $default
   *   Precio por defecto si no existe.
   *
   * @return float
   *   Precio unitario en EUR.
   */
  public function getOverageUnitPrice(string $metric, float $default = 0.0): float {
    return (float) ($this->overage_unit_prices[$metric] ?? $default);
  }

  /**
   * Establece los precios de overage.
   */
  public function setOverageUnitPrices(array $prices): self {
    $this->overage_unit_prices = $prices;
    return $this;
  }

  /**
   * Obtiene el porcentaje de tolerancia burst.
   */
  public function getBurstTolerancePct(): int {
    return $this->burst_tolerance_pct ?? 0;
  }

  /**
   * Establece el porcentaje de tolerancia burst.
   */
  public function setBurstTolerancePct(int $pct): self {
    $this->burst_tolerance_pct = $pct;
    return $this;
  }

  /**
   * Obtiene las horas de gracia.
   */
  public function getGracePeriodHours(): int {
    return $this->grace_period_hours ?? 6;
  }

  /**
   * Establece las horas de gracia.
   */
  public function setGracePeriodHours(int $hours): self {
    $this->grace_period_hours = $hours;
    return $this;
  }

  /**
   * Obtiene la descripcion.
   */
  public function getDescription(): string {
    return $this->description ?? '';
  }

  /**
   * Establece la descripcion.
   */
  public function setDescription(string $description): self {
    $this->description = $description;
    return $this;
  }

}
