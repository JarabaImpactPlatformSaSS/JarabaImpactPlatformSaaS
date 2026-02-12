<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface para la entidad FreemiumVerticalLimit.
 *
 * Define el contrato para los limites freemium especificos por
 * vertical y plan del ecosistema SaaS.
 */
interface FreemiumVerticalLimitInterface extends ConfigEntityInterface
{

    /**
     * Obtiene el machine name de la vertical.
     *
     * @return string
     *   La vertical (agroconecta, comercioconecta, etc.).
     */
    public function getVertical(): string;

    /**
     * Establece la vertical.
     *
     * @param string $vertical
     *   La vertical.
     *
     * @return $this
     */
    public function setVertical(string $vertical): FreemiumVerticalLimitInterface;

    /**
     * Obtiene el machine name del plan.
     *
     * @return string
     *   El plan (free, starter, profesional, business, enterprise).
     */
    public function getPlan(): string;

    /**
     * Establece el plan.
     *
     * @param string $plan
     *   El plan.
     *
     * @return $this
     */
    public function setPlan(string $plan): FreemiumVerticalLimitInterface;

    /**
     * Obtiene la clave del recurso limitado.
     *
     * @return string
     *   La clave (products, orders_per_month, copilot_uses_per_month, etc.).
     */
    public function getFeatureKey(): string;

    /**
     * Establece la clave del recurso limitado.
     *
     * @param string $feature_key
     *   La clave.
     *
     * @return $this
     */
    public function setFeatureKey(string $feature_key): FreemiumVerticalLimitInterface;

    /**
     * Obtiene el valor del limite.
     *
     * @return int
     *   El limite (-1 = ilimitado, 0 = no incluido).
     */
    public function getLimitValue(): int;

    /**
     * Establece el valor del limite.
     *
     * @param int $limit_value
     *   El valor del limite.
     *
     * @return $this
     */
    public function setLimitValue(int $limit_value): FreemiumVerticalLimitInterface;

    /**
     * Obtiene la descripcion del limite.
     *
     * @return string
     *   La descripcion.
     */
    public function getDescription(): string;

    /**
     * Establece la descripcion.
     *
     * @param string $description
     *   La descripcion.
     *
     * @return $this
     */
    public function setDescription(string $description): FreemiumVerticalLimitInterface;

    /**
     * Obtiene el mensaje mostrado al alcanzar el limite.
     *
     * @return string
     *   El mensaje de upgrade.
     */
    public function getUpgradeMessage(): string;

    /**
     * Establece el mensaje de upgrade.
     *
     * @param string $upgrade_message
     *   El mensaje.
     *
     * @return $this
     */
    public function setUpgradeMessage(string $upgrade_message): FreemiumVerticalLimitInterface;

    /**
     * Obtiene la tasa de conversion esperada (0-1).
     *
     * @return float
     *   La tasa de conversion.
     */
    public function getExpectedConversion(): float;

    /**
     * Establece la tasa de conversion esperada.
     *
     * @param float $expected_conversion
     *   La tasa (0-1).
     *
     * @return $this
     */
    public function setExpectedConversion(float $expected_conversion): FreemiumVerticalLimitInterface;

    /**
     * Obtiene el peso para ordenacion.
     *
     * @return int
     *   El peso.
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
    public function setWeight(int $weight): FreemiumVerticalLimitInterface;

}
