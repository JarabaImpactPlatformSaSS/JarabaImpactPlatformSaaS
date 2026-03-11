<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for PlanFormativoEi entities.
 *
 * Representa un plan formativo del programa Andalucia +ei que agrupa
 * acciones formativas por carril y verifica cumplimiento de minimos
 * de horas de formacion (>=50h) y orientacion (>=10h).
 */
interface PlanFormativoEiInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Carriles del programa.
   */
  public const CARRILES = [
    'impulso_digital' => 'Impulso Digital',
    'acelera_pro' => 'Acelera Pro',
    'hibrido' => 'Hibrido',
  ];

  /**
   * Estados del plan formativo.
   */
  public const ESTADOS = [
    'borrador' => 'Borrador',
    'activo' => 'Activo',
    'completado' => 'Completado',
    'archivado' => 'Archivado',
  ];

  /**
   * Obtiene el titulo del plan formativo.
   */
  public function getTitulo(): string;

  /**
   * Obtiene el carril del plan.
   */
  public function getCarril(): string;

  /**
   * Obtiene el estado actual del plan.
   */
  public function getEstado(): string;

  /**
   * Obtiene las horas de formacion previstas (computed).
   */
  public function getHorasFormacionPrevistas(): float;

  /**
   * Obtiene las horas de orientacion previstas (computed).
   */
  public function getHorasOrientacionPrevistas(): float;

  /**
   * Obtiene las horas totales previstas (computed).
   */
  public function getHorasTotalesPrevistas(): float;

  /**
   * Indica si cumple el minimo de 50h de formacion.
   */
  public function cumpleMinimosFormacion(): bool;

  /**
   * Indica si cumple el minimo de 10h de orientacion.
   */
  public function cumpleMinimosOrientacion(): bool;

  /**
   * Indica si cumple ambos minimos (formacion y orientacion).
   */
  public function cumpleMinimos(): bool;

  /**
   * Obtiene los IDs de acciones formativas del plan.
   *
   * @return array
   *   Array de IDs (puede incluir metadatos de orden).
   */
  public function getAccionFormativaIds(): array;

}
