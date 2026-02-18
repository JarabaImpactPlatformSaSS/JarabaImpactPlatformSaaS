<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface para la entidad SolicitudEi.
 */
interface SolicitudEiInterface extends ContentEntityInterface, EntityChangedInterface
{

    /**
     * Gets the applicant's full name.
     */
    public function getNombre(): string;

    /**
     * Gets the applicant's email.
     */
    public function getEmail(): string;

    /**
     * Gets the applicant's phone.
     */
    public function getTelefono(): string;

    /**
     * Gets the province.
     */
    public function getProvincia(): string;

    /**
     * Gets the current status.
     */
    public function getEstado(): string;

    /**
     * Sets the status.
     */
    public function setEstado(string $estado): static;

    /**
     * Gets the inferred colectivo.
     */
    public function getColectivoInferido(): ?string;

    /**
     * Sets the inferred colectivo.
     */
    public function setColectivoInferido(string $colectivo): static;

    /**
     * Infers the colectivo based on form data.
     */
    public function inferirColectivo(): string;

}
