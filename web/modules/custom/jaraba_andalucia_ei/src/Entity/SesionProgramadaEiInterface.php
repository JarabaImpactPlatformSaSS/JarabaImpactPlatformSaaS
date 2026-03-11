<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for SesionProgramadaEi entities.
 *
 * Representa una sesión programada dentro del programa Andalucía +ei.
 * Las sesiones pueden estar vinculadas a una acción formativa o ser
 * independientes (orientación, tutoría).
 */
interface SesionProgramadaEiInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Tipos de sesión válidos.
   */
  public const TIPOS_SESION = [
    'formacion_presencial' => 'Formación presencial',
    'formacion_online' => 'Formación online',
    'orientacion_individual' => 'Orientación individual',
    'orientacion_grupal' => 'Orientación grupal',
    'tutoria' => 'Tutoría',
    'taller' => 'Taller',
  ];

  /**
   * Modalidades de impartición.
   */
  public const MODALIDADES = [
    'presencial' => 'Presencial',
    'online' => 'Online',
    'mixta' => 'Mixta',
  ];

  /**
   * Estados posibles de la sesión.
   */
  public const ESTADOS = [
    'programada' => 'Programada',
    'confirmada' => 'Confirmada',
    'en_curso' => 'En curso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
    'aplazada' => 'Aplazada',
  ];

  /**
   * Fases del programa Andalucía +ei.
   */
  public const FASES_PROGRAMA = [
    'acogida' => 'Acogida',
    'diagnostico' => 'Diagnóstico',
    'atencion' => 'Atención',
    'insercion' => 'Inserción',
    'seguimiento' => 'Seguimiento',
    'transversal' => 'Transversal',
  ];

  /**
   * Obtiene el título de la sesión.
   */
  public function getTitulo(): string;

  /**
   * Obtiene el tipo de sesión.
   */
  public function getTipoSesion(): string;

  /**
   * Obtiene la fecha de la sesión.
   */
  public function getFecha(): ?string;

  /**
   * Obtiene la hora de inicio (HH:MM).
   */
  public function getHoraInicio(): string;

  /**
   * Obtiene la hora de fin (HH:MM).
   */
  public function getHoraFin(): string;

  /**
   * Obtiene la modalidad.
   */
  public function getModalidad(): string;

  /**
   * Obtiene el estado actual.
   */
  public function getEstado(): string;

  /**
   * Obtiene la fase del programa.
   */
  public function getFasePrograma(): string;

  /**
   * Obtiene el máximo de plazas.
   */
  public function getMaxPlazas(): int;

  /**
   * Obtiene las plazas ocupadas.
   */
  public function getPlazasOcupadas(): int;

  /**
   * Obtiene las plazas disponibles.
   */
  public function getPlazasDisponibles(): int;

  /**
   * Indica si la sesión es grupal.
   */
  public function isGrupal(): bool;

  /**
   * Indica si hay plazas disponibles.
   */
  public function hayPlazasDisponibles(): bool;

  /**
   * Obtiene la duración en horas calculada desde hora_inicio y hora_fin.
   */
  public function getDuracionHoras(): float;

  /**
   * Indica si la sesión es recurrente.
   */
  public function isRecurrente(): bool;

}
