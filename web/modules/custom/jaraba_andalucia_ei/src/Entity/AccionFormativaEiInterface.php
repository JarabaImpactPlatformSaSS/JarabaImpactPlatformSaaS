<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface for AccionFormativaEi entities.
 *
 * Representa una acción formativa del programa Andalucía +ei que
 * requiere aprobación VoBo del SAE para ser legalmente válida.
 */
interface AccionFormativaEiInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface, RevisionLogInterface {

  /**
   * Tipos de formación válidos según normativa PIIL.
   */
  public const TIPOS_FORMACION = [
    'presencial' => 'Presencial',
    'online_sincrona' => 'Online síncrona',
    'online_asincrona' => 'Online asíncrona',
    'mixta' => 'Mixta',
    'taller_practico' => 'Taller práctico',
    'mentoria_grupal' => 'Mentoría grupal',
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
   * Categorías temáticas para clasificación.
   */
  public const CATEGORIAS = [
    'competencias_digitales' => 'Competencias digitales',
    'emprendimiento' => 'Emprendimiento',
    'empleabilidad' => 'Empleabilidad',
    'idiomas' => 'Idiomas',
    'habilidades_blandas' => 'Habilidades blandas',
    'sector_especifico' => 'Sector específico',
    'orientacion' => 'Orientación',
    'tutoria' => 'Tutoría',
  ];

  /**
   * Carriles del programa.
   */
  public const CARRILES = [
    'impulso_digital' => 'Impulso Digital',
    'acelera_pro' => 'Acelera Pro',
    'hibrido' => 'Híbrido',
    'comun' => 'Común (todos los carriles)',
  ];

  /**
   * Estados de la acción formativa.
   */
  public const ESTADOS = [
    'borrador' => 'Borrador',
    'pendiente_vobo' => 'Pendiente VoBo SAE',
    'vobo_enviado' => 'VoBo enviado al SAE',
    'vobo_aprobado' => 'VoBo aprobado',
    'vobo_rechazado' => 'VoBo rechazado',
    'en_subsanacion' => 'En subsanación',
    'en_ejecucion' => 'En ejecución',
    'finalizada' => 'Finalizada',
  ];

  /**
   * Obtiene el título de la acción formativa.
   */
  public function getTitulo(): string;

  /**
   * Obtiene el tipo de formación.
   */
  public function getTipoFormacion(): string;

  /**
   * Obtiene las horas previstas.
   */
  public function getHorasPrevistas(): float;

  /**
   * Obtiene el carril al que aplica.
   */
  public function getCarril(): string;

  /**
   * Obtiene el estado actual.
   */
  public function getEstado(): string;

  /**
   * Indica si requiere VoBo del SAE.
   */
  public function requiereVoboSae(): bool;

  /**
   * Indica si el VoBo está aprobado.
   */
  public function isVoboAprobado(): bool;

  /**
   * Indica si la acción puede ejecutarse (VoBo aprobado o no requerido).
   */
  public function canExecute(): bool;

  /**
   * Obtiene la categoría temática.
   */
  public function getCategoria(): string;

  /**
   * Obtiene la modalidad.
   */
  public function getModalidad(): string;

  /**
   * Obtiene el ID del curso LMS asociado (si existe).
   */
  public function getCourseId(): ?int;

}
