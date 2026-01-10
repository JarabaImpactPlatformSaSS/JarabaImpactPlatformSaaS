<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface para la entidad AIAgent.
 */
interface AIAgentInterface extends ConfigEntityInterface
{

    /**
     * Obtiene la descripción del agente.
     *
     * @return string
     *   La descripción.
     */
    public function getDescription(): string;

    /**
     * Establece la descripción del agente.
     *
     * @param string $description
     *   La descripción.
     *
     * @return $this
     */
    public function setDescription(string $description): AIAgentInterface;

    /**
     * Obtiene el ID del servicio Drupal.
     *
     * @return string
     *   El service ID.
     */
    public function getServiceId(): string;

    /**
     * Establece el ID del servicio Drupal.
     *
     * @param string $serviceId
     *   El service ID.
     *
     * @return $this
     */
    public function setServiceId(string $serviceId): AIAgentInterface;

    /**
     * Obtiene el icono del agente.
     *
     * @return string
     *   El nombre del icono.
     */
    public function getIcon(): string;

    /**
     * Establece el icono del agente.
     *
     * @param string $icon
     *   El nombre del icono.
     *
     * @return $this
     */
    public function setIcon(string $icon): AIAgentInterface;

    /**
     * Obtiene el color del agente.
     *
     * @return string
     *   El color en hex.
     */
    public function getColor(): string;

    /**
     * Establece el color del agente.
     *
     * @param string $color
     *   El color en hex.
     *
     * @return $this
     */
    public function setColor(string $color): AIAgentInterface;

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
    public function setWeight(int $weight): AIAgentInterface;

}
