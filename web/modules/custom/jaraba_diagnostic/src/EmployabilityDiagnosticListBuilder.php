<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista admin de diagnosticos de empleabilidad.
 *
 * PROPOSITO:
 * Muestra la tabla administrativa de diagnosticos en
 * /admin/content/employability-diagnostics con columnas:
 * usuario, perfil, score, fecha.
 */
class EmployabilityDiagnosticListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['user'] = $this->t('Usuario');
    $header['profile_type'] = $this->t('Perfil');
    $header['score'] = $this->t('Score');
    $header['primary_gap'] = $this->t('Gap principal');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_diagnostic\Entity\EmployabilityDiagnostic $entity */
    $row['id'] = $entity->id();

    // Nombre del usuario propietario.
    $owner = $entity->getOwner();
    $row['user'] = $owner ? $owner->getDisplayName() : $this->t('Anonimo');

    $row['profile_type'] = $entity->getProfileLabel();
    $row['score'] = number_format($entity->getScore(), 1);
    $row['primary_gap'] = $entity->getPrimaryGap();
    $row['created'] = \Drupal::service('date.formatter')
      ->format($entity->get('created')->value, 'short');

    return $row + parent::buildRow($entity);
  }

}
