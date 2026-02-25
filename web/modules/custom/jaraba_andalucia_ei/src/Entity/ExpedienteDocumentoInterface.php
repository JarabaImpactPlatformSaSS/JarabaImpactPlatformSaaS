<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad ExpedienteDocumento.
 *
 * Representa un documento dentro del expediente de un participante
 * del programa Andalucía +ei.
 */
interface ExpedienteDocumentoInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Obtiene el título del documento.
   *
   * @return string
   *   Nombre descriptivo del documento.
   */
  public function getTitulo(): string;

  /**
   * Establece el título del documento.
   *
   * @param string $titulo
   *   Nombre descriptivo.
   *
   * @return $this
   */
  public function setTitulo(string $titulo): self;

  /**
   * Obtiene la categoría del documento.
   *
   * @return string
   *   Categoría (sto_dni, programa_contrato, tarea_cv, etc.).
   */
  public function getCategoria(): string;

  /**
   * Obtiene el ID del participante asociado.
   *
   * @return int|null
   *   ID del participante o NULL si no está vinculado.
   */
  public function getParticipanteId(): ?int;

  /**
   * Obtiene el estado de revisión del documento.
   *
   * @return string
   *   Estado: pendiente, en_revision, aprobado, rechazado, requiere_cambios.
   */
  public function getEstadoRevision(): string;

  /**
   * Establece el estado de revisión.
   *
   * @param string $estado
   *   Nuevo estado de revisión.
   *
   * @return $this
   */
  public function setEstadoRevision(string $estado): self;

  /**
   * Obtiene el ID de referencia del documento en el vault.
   *
   * @return string|null
   *   ID del SecureDocument en jaraba_legal_vault.
   */
  public function getArchivoVaultId(): ?string;

  /**
   * Indica si el documento está firmado digitalmente.
   *
   * @return bool
   *   TRUE si está firmado.
   */
  public function isFirmado(): bool;

  /**
   * Indica si el documento es requerido para STO.
   *
   * @return bool
   *   TRUE si es obligatorio para STO.
   */
  public function isRequeridoSto(): bool;

  /**
   * Obtiene la puntuación de revisión IA.
   *
   * @return float|null
   *   Puntuación 0-100 o NULL si no evaluado.
   */
  public function getRevisionIaScore(): ?float;

}
