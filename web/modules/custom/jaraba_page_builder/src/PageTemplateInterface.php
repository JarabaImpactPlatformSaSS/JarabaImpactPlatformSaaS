<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface para la entidad PageTemplate.
 */
interface PageTemplateInterface extends ConfigEntityInterface
{

    /**
     * Obtiene la descripción de la plantilla.
     *
     * @return string
     *   La descripción.
     */
    public function getDescription(): string;

    /**
     * Obtiene la categoría de la plantilla.
     *
     * @return string
     *   La categoría (hero, features, landing, etc.).
     */
    public function getCategory(): string;

    /**
     * Obtiene la ruta al template Twig.
     *
     * @return string
     *   La ruta al template.
     */
    public function getTwigTemplate(): string;

    /**
     * Obtiene el JSON Schema de los campos configurables.
     *
     * @return array
     *   El schema de campos.
     */
    public function getFieldsSchema(): array;

    /**
     * Obtiene los planes requeridos para esta plantilla.
     *
     * @return array
     *   Array de IDs de planes.
     */
    public function getPlansRequired(): array;

    /**
     * Indica si es una plantilla premium.
     *
     * @return bool
     *   TRUE si es premium.
     */
    public function isPremium(): bool;

    /**
     * Obtiene la imagen de preview.
     *
     * @return string
     *   Ruta a la imagen.
     */
    public function getPreviewImage(): string;

    /**
     * Obtiene los datos curados de preview.
     *
     * Estos datos son idénticos a los usados en la miniatura PNG
     * para garantizar fidelidad visual en el preview live.
     *
     * @return array
     *   Array de datos de preview o vacío si no hay curados.
     */
    public function getPreviewData(): array;

}
