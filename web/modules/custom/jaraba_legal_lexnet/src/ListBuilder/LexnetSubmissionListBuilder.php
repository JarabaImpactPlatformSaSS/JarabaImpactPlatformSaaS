<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de envios LexNET en admin.
 */
class LexnetSubmissionListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['subject'] = $this->t('Asunto');
    $header['submission_type'] = $this->t('Tipo');
    $header['court'] = $this->t('Organo Judicial');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $statusLabels = [
      'draft' => $this->t('Borrador'),
      'submitting' => $this->t('Enviando'),
      'submitted' => $this->t('Enviado'),
      'confirmed' => $this->t('Confirmado'),
      'rejected' => $this->t('Rechazado'),
      'error' => $this->t('Error'),
    ];

    $status = $entity->get('status')->value;
    $created = $entity->get('created')->value;

    $row['subject'] = mb_substr($entity->get('subject')->value ?? '', 0, 80);
    $row['submission_type'] = $entity->get('submission_type')->value ?? '';
    $row['court'] = mb_substr($entity->get('court')->value ?? '', 0, 50);
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '';

    return $row + parent::buildRow($entity);
  }

}
