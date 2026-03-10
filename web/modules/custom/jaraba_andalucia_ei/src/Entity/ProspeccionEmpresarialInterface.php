<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad ProspeccionEmpresarial.
 *
 * Registra actividades de prospección e intermediación con empresas
 * del programa Andalucía +ei (PIIL CV 2025).
 */
interface ProspeccionEmpresarialInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Obtiene el nombre de la empresa.
   *
   * @return string
   *   Nombre o razón social.
   */
  public function getEmpresaNombre(): string;

  /**
   * Obtiene el CIF de la empresa.
   *
   * @return string
   *   CIF/NIF de la empresa.
   */
  public function getCif(): string;

  /**
   * Obtiene el sector de actividad.
   *
   * @return string
   *   Sector o cadena vacía.
   */
  public function getSector(): string;

  /**
   * Obtiene el estado de la prospección.
   *
   * @return string
   *   Estado: lead, contactado, interesado, colaborador, descartado.
   */
  public function getEstado(): string;

  /**
   * Establece el estado de la prospección.
   *
   * @param string $estado
   *   Nuevo estado.
   *
   * @return $this
   */
  public function setEstado(string $estado): self;

  /**
   * Obtiene el tipo de colaboración.
   *
   * @return string
   *   Tipo: practicas, contratacion, formacion_dual, emprendimiento.
   */
  public function getTipoColaboracion(): string;

  /**
   * Indica si la empresa es colaboradora activa.
   *
   * @return bool
   *   TRUE si estado es 'colaborador'.
   */
  public function isColaboradorActivo(): bool;

}
