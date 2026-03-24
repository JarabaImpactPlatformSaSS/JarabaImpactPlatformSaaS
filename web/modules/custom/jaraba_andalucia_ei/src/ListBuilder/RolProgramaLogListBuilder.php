<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder for RolProgramaLog entities.
 */
class RolProgramaLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildHeader(): array {
    $header = [];
    $header['user_id'] = $this->t('Usuario');
    $header['rol_programa'] = $this->t('Rol');
    $header['accion'] = $this->t('Acción');
    $header['assigned_by'] = $this->t('Asignado por');
    $header['motivo'] = $this->t('Motivo');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $row = [];
    $row['user_id'] = '';
    $row['rol_programa'] = $entity->get('rol_programa')->value ?? '';
    $row['accion'] = $entity->get('accion')->value ?? '';
    $row['assigned_by'] = '';
    $row['motivo'] = mb_substr((string) ($entity->get('motivo')->value ?? ''), 0, 80);
    $row['created'] = '';

    try {
      $userId = $entity->get('user_id')->target_id ?? NULL;
      if ($userId !== NULL) {
        $user = \Drupal::entityTypeManager()->getStorage('user')->load($userId);
        $row['user_id'] = $user !== NULL ? $user->getDisplayName() : "uid:$userId";
      }

      $assignedBy = $entity->get('assigned_by')->target_id ?? NULL;
      if ($assignedBy !== NULL) {
        $assignerUser = \Drupal::entityTypeManager()->getStorage('user')->load($assignedBy);
        $row['assigned_by'] = $assignerUser !== NULL ? $assignerUser->getDisplayName() : "uid:$assignedBy";
      }

      $created = $entity->get('created')->value ?? NULL;
      if ($created !== NULL) {
        $row['created'] = \Drupal::service('date.formatter')->format((int) $created, 'short');
      }
    }
    catch (\Throwable) {
    }

    return $row + parent::buildRow($entity);
  }

}
