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
   * Tipos de sesión válidos alineados con normativa PIIL (BBRR Art. 2).
   *
   * Sprint 14: Reestructuración — separa orientación laboral (Fase Atención)
   * de orientación para la inserción (Fase Inserción). Las sesiones formativas
   * son HIJAS de AccionFormativaEi y requieren accion_formativa_id obligatorio.
   */
  public const TIPOS_SESION = [
    'orientacion_laboral_individual' => 'Orientación laboral individual',
    'orientacion_laboral_grupal' => 'Orientación laboral grupal',
    'orientacion_insercion_individual' => 'Orientación para la inserción individual',
    'orientacion_insercion_grupal' => 'Orientación para la inserción grupal',
    'sesion_formativa' => 'Sesión formativa',
    'tutoria_seguimiento' => 'Tutoría de seguimiento',
  ];

  /**
   * Mapa de fase PIIL por tipo de sesión.
   *
   * Permite el cómputo automático de la fase en preSave().
   */
  public const FASE_POR_TIPO = [
    'orientacion_laboral_individual' => 'atencion',
    'orientacion_laboral_grupal' => 'atencion',
    'orientacion_insercion_individual' => 'insercion',
    'orientacion_insercion_grupal' => 'insercion',
    'sesion_formativa' => 'atencion',
    'tutoria_seguimiento' => 'transversal',
  ];

  /**
   * Tipos de sesión legacy para migración de datos.
   *
   * @deprecated Solo para scripts de migración. No usar en código nuevo.
   */
  public const TIPOS_SESION_LEGACY_MAP = [
    'formacion_presencial' => 'sesion_formativa',
    'formacion_online' => 'sesion_formativa',
    'orientacion_individual' => 'orientacion_laboral_individual',
    'orientacion_grupal' => 'orientacion_laboral_grupal',
    'tutoria' => 'tutoria_seguimiento',
    'taller' => 'orientacion_laboral_grupal',
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
   * Obtiene la fase PIIL de la sesión (atencion/insercion/transversal).
   */
  public function getFasePiil(): string;

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
