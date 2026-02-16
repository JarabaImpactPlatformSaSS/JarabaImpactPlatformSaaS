<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad CourtHearing.
 */
class CourtHearingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'title' => $this->t('Senalado'),
      'hearing_type' => $this->t('Tipo'),
      'court' => $this->t('Juzgado'),
      'scheduled_at' => $this->t('Fecha'),
      'status' => $this->t('Estado'),
      'case_id' => $this->t('Expediente'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->label();
    $row['hearing_type'] = $entity->get('hearing_type')->value ?? '';
    $row['court'] = $entity->get('court')->value ?? '';
    $scheduled = $entity->get('scheduled_at')->value;
    $row['scheduled_at'] = $scheduled ? date('d/m/Y H:i', strtotime($scheduled)) : '';
    $row['status'] = $entity->get('status')->value ?? '';
    $case = $entity->get('case_id')->entity;
    $row['case_id'] = $case ? $case->label() : '';
    return $row + parent::buildRow($entity);
  }

}
