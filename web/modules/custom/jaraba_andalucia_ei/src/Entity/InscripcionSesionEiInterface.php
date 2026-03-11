<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad InscripcionSesionEi.
 *
 * Gestiona la inscripción de un participante a una sesión programada
 * del programa Andalucía +ei, incluyendo control de asistencia y
 * cómputo de horas.
 */
interface InscripcionSesionEiInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Estados posibles de la inscripción.
   */
  const ESTADO_INSCRITO = 'inscrito';
  const ESTADO_CONFIRMADO = 'confirmado';
  const ESTADO_ASISTIO = 'asistio';
  const ESTADO_NO_ASISTIO = 'no_asistio';
  const ESTADO_CANCELADO = 'cancelado';
  const ESTADO_JUSTIFICADO = 'justificado';

  /**
   * Lista de todos los estados válidos.
   */
  const ESTADOS = [
    self::ESTADO_INSCRITO,
    self::ESTADO_CONFIRMADO,
    self::ESTADO_ASISTIO,
    self::ESTADO_NO_ASISTIO,
    self::ESTADO_CANCELADO,
    self::ESTADO_JUSTIFICADO,
  ];

  /**
   * Obtiene el ID de la sesión programada.
   *
   * @return int|null
   *   ID de la sesión programada o NULL si no está asignada.
   */
  public function getSesionId(): ?int;

  /**
   * Obtiene el ID del participante.
   *
   * @return int|null
   *   ID del participante o NULL si no está asignado.
   */
  public function getParticipanteId(): ?int;

  /**
   * Obtiene el estado actual de la inscripción.
   *
   * @return string
   *   Uno de los valores de ESTADOS.
   */
  public function getEstado(): string;

  /**
   * Indica si la asistencia ha sido verificada.
   *
   * @return bool
   *   TRUE si la asistencia ha sido verificada por un coordinador.
   */
  public function isAsistenciaVerificada(): bool;

  /**
   * Obtiene las horas computadas para esta inscripción.
   *
   * @return float
   *   Horas computadas (0 si no se han registrado).
   */
  public function getHorasComputadas(): float;

  /**
   * Obtiene el ID de la actuación STO vinculada.
   *
   * @return int|null
   *   ID de la actuación STO o NULL si no está vinculada.
   */
  public function getActuacionStoId(): ?int;

}
