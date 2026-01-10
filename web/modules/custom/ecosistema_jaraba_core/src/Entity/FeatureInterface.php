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

}
