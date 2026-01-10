<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface para la entidad Vertical.
 *
 * Una Vertical representa un segmento de negocio especializado
 * (AgroConecta, FormaTech, TurismoLocal, etc.)
 */
interface VerticalInterface extends ContentEntityInterface
{

    /**
     * Obtiene el nombre de la vertical.
     *
     * @return string
     *   El nombre de la vertical.
     */
    public function getName(): string;

    /**
     * Establece el nombre de la vertical.
     *
     * @param string $name
     *   El nombre de la vertical.
     *
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Obtiene el machine_name de la vertical.
     *
     * @return string
     *   El identificador único de la vertical.
     */
    public function getMachineName(): string;

    /**
     * Obtiene la descripción de la vertical.
     *
     * @return string|null
     *   La descripción o NULL si no está definida.
     */
    public function getDescription(): ?string;

    /**
     * Obtiene las features habilitadas para esta vertical.
     *
     * @return array
     *   Lista de features habilitadas.
     */
    public function getEnabledFeatures(): array;

    /**
     * Verifica si una feature está habilitada.
     *
     * @param string $feature
     *   El identificador de la feature.
     *
     * @return bool
     *   TRUE si la feature está habilitada.
     */
    public function hasFeature(string $feature): bool;

    /**
     * Obtiene la configuración de tema por defecto.
     *
     * @return array
     *   Configuración de tema (colores, tipografía, etc.)
     */
    public function getThemeSettings(): array;

}
