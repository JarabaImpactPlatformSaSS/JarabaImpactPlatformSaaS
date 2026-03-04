<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion ActivationCriteriaConfig.
 *
 * Almacena criterios de activacion pre-PMF por vertical y umbrales
 * de metricas de producto: activation rate, retention D30, NPS, churn.
 *
 * Convencion de ID: {vertical}_activation
 *
 * @ConfigEntityType(
 *   id = "activation_criteria",
 *   label = @Translation("Activation Criteria"),
 *   label_collection = @Translation("Activation Criteria"),
 *   label_singular = @Translation("activation criteria config"),
 *   label_plural = @Translation("activation criteria configs"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\ListBuilder\ActivationCriteriaListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_analytics\Form\ActivationCriteriaForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\ActivationCriteriaForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "activation_criteria",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "vertical",
 *     "criteria",
 *     "activation_threshold",
 *     "retention_d30_threshold",
 *     "nps_threshold",
 *     "churn_threshold",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/activation-criteria",
 *     "add-form" = "/admin/structure/activation-criteria/add",
 *     "edit-form" = "/admin/structure/activation-criteria/{activation_criteria}/edit",
 *     "delete-form" = "/admin/structure/activation-criteria/{activation_criteria}/delete",
 *   },
 * )
 */
class ActivationCriteriaConfig extends ConfigEntityBase {

  /**
   * The config ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * Vertical canonico (VERTICAL-CANONICAL-001).
   *
   * @var string
   */
  protected $vertical = '';

  /**
   * Criterios de activacion serializados.
   *
   * Cada criterio: {event_type: string, min_count: int, within_days: int}.
   *
   * @var array
   */
  protected $criteria = [];

  /**
   * Umbral de tasa de activacion (0-1).
   *
   * @var float
   */
  protected $activation_threshold = 0.40;

  /**
   * Umbral de retencion D30 (0-1).
   *
   * @var float
   */
  protected $retention_d30_threshold = 0.25;

  /**
   * Umbral NPS (-100 a 100).
   *
   * @var int
   */
  protected $nps_threshold = 40;

  /**
   * Umbral de churn mensual (0-1).
   *
   * @var float
   */
  protected $churn_threshold = 0.05;

  /**
   * Get the vertical.
   */
  public function getVertical(): string {
    return $this->vertical;
  }

  /**
   * Set the vertical.
   */
  public function setVertical(string $vertical): static {
    $this->vertical = $vertical;
    return $this;
  }

  /**
   * Get activation criteria.
   *
   * @return array
   *   Array of criteria definitions.
   */
  public function getCriteria(): array {
    return $this->criteria;
  }

  /**
   * Set activation criteria.
   */
  public function setCriteria(array $criteria): static {
    $this->criteria = $criteria;
    return $this;
  }

  /**
   * Get activation threshold.
   */
  public function getActivationThreshold(): float {
    return $this->activation_threshold;
  }

  /**
   * Get retention D30 threshold.
   */
  public function getRetentionD30Threshold(): float {
    return $this->retention_d30_threshold;
  }

  /**
   * Get NPS threshold.
   */
  public function getNpsThreshold(): int {
    return $this->nps_threshold;
  }

  /**
   * Get churn threshold.
   */
  public function getChurnThreshold(): float {
    return $this->churn_threshold;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('module', 'jaraba_analytics');
    return $this;
  }

}
