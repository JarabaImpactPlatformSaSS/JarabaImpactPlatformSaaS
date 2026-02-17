<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Constructor de listado para la entidad Participante de Programa.
 *
 * Estructura: Extiende EntityListBuilder para mostrar participantes
 *   en tabla administrativa con referencias a programa y usuario.
 *
 * Logica: Muestra ID, nombre del programa (via referencia), nombre
 *   del participante (via referencia de usuario), fecha de inscripcion,
 *   estado, resultado laboral y operaciones.
 */
class ProgramParticipantListBuilder extends EntityListBuilder {

  /**
   * El gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['program'] = $this->t('Programa');
    $header['participant'] = $this->t('Participante');
    $header['enrollment_date'] = $this->t('Fecha inscripcion');
    $header['status'] = $this->t('Estado');
    $header['employment_outcome'] = $this->t('Resultado laboral');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_institutional\Entity\ProgramParticipant $entity */

    // Cargar el nombre del programa via referencia de entidad.
    $programName = '';
    if ($entity->hasField('program_id') && !$entity->get('program_id')->isEmpty()) {
      $program = $entity->get('program_id')->entity;
      $programName = $program ? $program->label() : $this->t('(eliminado)');
    }

    // Cargar el nombre visible del usuario via referencia.
    $userName = '';
    if ($entity->hasField('user_id') && !$entity->get('user_id')->isEmpty()) {
      $user = $entity->get('user_id')->entity;
      $userName = $user ? $user->getDisplayName() : $this->t('(eliminado)');
    }

    $row['id'] = $entity->id();
    $row['program'] = $programName;
    $row['participant'] = $userName;
    $row['enrollment_date'] = $entity->get('enrollment_date')->value ?? '';
    $row['status'] = $this->t('@status', ['@status' => $entity->get('status')->value ?? '']);
    $row['employment_outcome'] = $this->t('@outcome', ['@outcome' => $entity->get('employment_outcome')->value ?? '']);

    return $row + parent::buildRow($entity);
  }

}
