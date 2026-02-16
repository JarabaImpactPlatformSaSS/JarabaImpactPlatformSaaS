<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad LegalDeadline.
 */
class LegalDeadlineListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'title' => $this->t('Plazo'),
      'deadline_type' => $this->t('Tipo'),
      'due_date' => $this->t('Vencimiento'),
      'status' => $this->t('Estado'),
      'case_id' => $this->t('Expediente'),
      'assigned_to' => $this->t('Responsable'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->label();
    $row['deadline_type'] = $entity->get('deadline_type')->value ?? '';
    $due = $entity->get('due_date')->value;
    $row['due_date'] = $due ? date('d/m/Y H:i', strtotime($due)) : '';
    $row['status'] = $entity->get('status')->value ?? '';
    $case = $entity->get('case_id')->entity;
    $row['case_id'] = $case ? $case->label() : '';
    $user = $entity->get('assigned_to')->entity;
    $row['assigned_to'] = $user ? $user->getDisplayName() : '';
    return $row + parent::buildRow($entity);
  }

}
