<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Interface para la entidad MaterialDidacticoEi.
 *
 * Materiales y recursos didácticos vinculables a acciones formativas
 * del programa Andalucía +ei.
 */
interface MaterialDidacticoEiInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Tipos de material válidos.
   */
  public const TIPOS_MATERIAL = [
    'documento' => 'Documento',
    'video' => 'Vídeo',
    'presentacion' => 'Presentación',
    'guia' => 'Guía práctica',
    'ejercicio' => 'Ejercicio/Actividad',
    'evaluacion' => 'Evaluación',
    'recurso_externo' => 'Recurso externo',
  ];

  /**
   * Obtiene el título del material.
   */
  public function getTitulo(): string;

  /**
   * Obtiene el tipo de material.
   */
  public function getTipoMaterial(): string;

  /**
   * Obtiene la duración estimada en horas.
   */
  public function getDuracionEstimada(): float;

}
