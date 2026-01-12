<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface para la entidad Feature.
 */
interface FeatureInterface extends ConfigEntityInterface
{

    /**
     * Obtiene la descripción de la feature.
     *
     * @return string
     *   La descripción.
     */
    public function getDescription(): string;

    /**
     * Establece la descripción de la feature.
     *
     * @param string $description
     *   La descripción.
     *
     * @return $this
     */
    public function setDescription(string $description): FeatureInterface;

    /**
     * Obtiene la categoría de la feature.
     *
     * @return string
     *   La categoría.
     */
    public function getCategory(): string;

    /**
     * Establece la categoría de la feature.
     *
     * @param string $category
     *   La categoría.
     *
     * @return $this
     */
    public function setCategory(string $category): FeatureInterface;

    /**
     * Obtiene el icono de la feature.
     *
     * @return string
     *   El nombre del icono.
     */
    public function getIcon(): string;

    /**
     * Establece el icono de la feature.
     *
     * @param string $icon
     *   El nombre del icono.
     *
     * @return $this
     */
    public function setIcon(string $icon): FeatureInterface;

    /**
     * Obtiene el peso para ordenación.
     *
     * @return int
     *   El peso.
     */
    public function getWeight(): int;

    /**
     * Establece el peso para ordenación.
     *
     * @param int $weight
     *   El peso.
     *
     * @return $this
     */
    public function setWeight(int $weight): FeatureInterface;

    // =========================================================================
    // FINOPS COST FIELDS
    // =========================================================================

    /**
     * Obtiene el coste base mensual de la feature.
     *
     * @return float
     *   El coste en euros.
     */
    public function getBaseCostMonthly(): float;

    /**
     * Establece el coste base mensual de la feature.
     *
     * @param float $cost
     *   El coste en euros.
     *
     * @return $this
     */
    public function setBaseCostMonthly(float $cost): FeatureInterface;

    /**
     * Obtiene el coste unitario por uso.
     *
     * @return float
     *   El coste por unidad en euros.
     */
    public function getUnitCost(): float;

    /**
     * Establece el coste unitario por uso.
     *
     * @param float $cost
     *   El coste por unidad en euros.
     *
     * @return $this
     */
    public function setUnitCost(float $cost): FeatureInterface;

    /**
     * Obtiene la categoría de coste.
     *
     * @return string
     *   La categoría (compute, storage, ai, api, bandwidth).
     */
    public function getCostCategory(): string;

    /**
     * Establece la categoría de coste.
     *
     * @param string $category
     *   La categoría de coste.
     *
     * @return $this
     */
    public function setCostCategory(string $category): FeatureInterface;

    /**
     * Obtiene el tipo de métrica de uso.
     *
     * @return string
     *   El tipo de métrica (api_calls, rag_queries, storage_mb, etc.).
     */
    public function getUsageMetric(): string;

    /**
     * Establece el tipo de métrica de uso.
     *
     * @param string $metric
     *   El tipo de métrica.
     *
     * @return $this
     */
    public function setUsageMetric(string $metric): FeatureInterface;

}
